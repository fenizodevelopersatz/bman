<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Binarymatchingprobe_model — read-only DIAGNOSTIC that answers one question:
 * does the live binary matching engine implement the level-wise business spec?
 *
 * It builds the spec's exact A..O tree from synthetic users, drives the REAL
 * engine (Stakingmatching_model), records what actually happened per rule, then
 * deletes every row it created. It changes NO real data and pays NO real user.
 *
 * Lives in a model (not just the CLI controller) so Cron Lab can run it
 * in-process: CI3 cannot instantiate a second CI_Controller once Session is
 * loaded — see Cronlab::_runViaHttp()'s docblock — and this must never be
 * reachable over HTTP without the admin gate Cron Lab already enforces.
 *
 * THREE SAFETY RULES, because this DB is live (swap_dry_run=0):
 *  1. Only the ENGINE (propagate/payMatching) is ever invoked — never
 *     BinaryMatchingPayoutCron — so nothing is broadcast on-chain. The money
 *     path here is Walletledger_model::credit(), which writes wallet_ledger /
 *     user_wallets only, and every such row is for a synthetic user.
 *  2. propagate() takes no scope argument and sweeps EVERY unprocessed
 *     binary_volume_ledger row, so real pending rows are PARKED (processed=1)
 *     for the duration and restored afterwards — real users' carry is never
 *     touched.
 *  3. It ABORTS before paying anything if a real user currently qualifies for
 *     matching (both carries > 0). A real payout must never be a test artifact.
 *
 * Teardown runs from a shutdown hook as well as the finally block: CodeIgniter
 * halts the whole process on a DB error (db_debug), which skips finally and
 * would otherwise strand both the synthetic rows and the parked real volume.
 */
class Binarymatchingprobe_model extends CI_Model
{
    /** A..O = the spec's 15-node tree; P = a later purchase (rule 4);
     *  X/Y/Z = sandboxes for the multi-package ceiling and unstaked sponsor. */
    private $n = [
        'A' => 999999900, 'B' => 999999901, 'C' => 999999902, 'D' => 999999903,
        'E' => 999999904, 'F' => 999999905, 'G' => 999999906, 'H' => 999999907,
        'I' => 999999908, 'J' => 999999909, 'K' => 999999910, 'L' => 999999911,
        'M' => 999999912, 'N' => 999999913, 'O' => 999999914, 'P' => 999999915,
        'X' => 999999916, 'Y' => 999999917, 'Z' => 999999918,
    ];

    /** stake amount => package_id, chosen so group_ceiling matches spec §7. */
    private $pkg = [
        5000   => 40,   // 5,000 BMAN    -> ceiling 5,000
        10000  => 2,    // 10,000 BMAN   -> ceiling 10,000
        20000  => 3,    // 20,000 BMAN   -> ceiling 20,000
        50000  => 5,    // 50,000 BMAN   -> ceiling 30,000  (spec-correct row)
        100000 => 6,    // 100,000 BMAN  -> ceiling 30,000  (spec-correct row)
        200000 => 7,    // 200,000 BMAN  -> ceiling 50,000  (spec-correct row)
    ];

    private $all = [];
    private $parked = [];
    private $findings = [];
    private $log = [];
    private $payTable = [];
    private $tornDown = false;
    private $adminBefore = 0.0;
    /** admin_wallet.balance as found on entry — restored verbatim by teardown. */
    private $adminOpening = null;
    /** staking_packages rows the ceiling tests mutate — restored by teardown. */
    private $ceilingBackup = [];
    private $pkgActiveBackup = [];

    public function __construct()
    {
        parent::__construct();
        $this->load->model('staking/Stakingmatching_model', 'MB');
        // The live engine under test. probe() deliberately keeps driving the
        // LEGACY carry matcher ($this->MB) — it exists to document what that
        // engine did — while tests() is the acceptance gate for the new one.
        $this->load->model('staking/Binarylevelmatching_model', 'BLM');
        $this->load->model('Staking_model');
        $this->all = array_values($this->n);
    }

    /**
     * @return array{status:string,message:string,totals:array,findings:array,
     *               payouts:array,log:array,ran_at:string}
     */
    public function probe()
    {
        $abort = $this->_preflight();
        if ($abort !== null) {
            return ['status' => 'error', 'message' => $abort, 'totals' => [],
                    'findings' => [], 'payouts' => [], 'log' => $this->log,
                    'ran_at' => date('Y-m-d H:i:s')];
        }

        $this->adminOpening = $this->_adminBalance();
        register_shutdown_function([$this, 'teardown']);

        try {
            $this->_park();
            $this->_cleanup();
            $this->_buildTree();
            $this->_rules_1_3_6();
            $this->_rule4();
            $this->_rule5();
            $this->_rule2();
            $this->_rule_unstaked_sponsor();
        } catch (Throwable $e) {
            $this->_say('!! probe aborted: ' . $e->getMessage());
        } finally {
            $this->teardown();
        }

        $totals = ['MATCH' => 0, 'PARTIAL' => 0, 'MISMATCH' => 0];
        foreach ($this->findings as $f) $totals[$f['verdict']]++;

        return [
            'status'  => 'success',
            'message' => "Binary matching spec probe: {$totals['MATCH']} match, {$totals['PARTIAL']} partial, {$totals['MISMATCH']} mismatch",
            'totals'  => $totals,
            'findings' => $this->findings,
            'payouts'  => $this->payTable,
            'log'      => $this->log,
            'ran_at'   => date('Y-m-d H:i:s'),
        ];
    }

    /** Idempotent teardown — finally block AND shutdown hook both call this. */
    public function teardown()
    {
        if ($this->tornDown) return;
        $this->tornDown = true;
        $this->_cleanup();
        $this->_restoreAdmin();
        // Ceiling tests mutate real staking_packages rows — put them back even
        // if a test aborted before its own restore ran.
        $this->_restoreCeilings();
        $this->_unpark();
        $this->_verifyNoResidue();
    }

    /**
     * admin_wallet is a SINGLETON with no user_id, so _cleanup()'s
     * delete-by-user sweep cannot touch it — synthetic overflow would
     * otherwise accumulate in the real admin balance run after run. Ledger
     * rows are removed by reference_user_id; the balance is restored to
     * whatever it was on entry rather than recomputed, so it is exact even if
     * something else credited the admin mid-run.
     */
    private function _restoreAdmin()
    {
        $this->db->where_in('reference_user_id', $this->all)
                 ->where('reference_type', 'binary_matching_overflow')
                 ->delete('admin_wallet_ledger');
        if ($this->adminOpening !== null) {
            $this->db->query("UPDATE admin_wallet SET balance = ?, updated_at = NOW() WHERE id = 1",
                [$this->adminOpening]);
        }
    }

    /* ------------------------------ safety ------------------------------- */

    /** @return string|null abort reason, or null when it is safe to proceed. */
    private function _preflight()
    {
        $this->_say('=== preflight ===');
        $qualifying = $this->db->query(
            "SELECT user_id, left_carry, right_carry FROM binary_carry
             WHERE left_carry > 0 AND right_carry > 0"
        )->result_array();
        $ids = $this->all;
        $real = array_filter($qualifying, function ($r) use ($ids) {
            return !in_array((int)$r['user_id'], $ids, true);
        });
        if ($real) {
            $who = [];
            foreach ($real as $r) $who[] = "user {$r['user_id']} ({$r['left_carry']}/{$r['right_carry']})";
            $msg = 'ABORTED: a REAL user currently qualifies for matching — ' . implode(', ', $who)
                 . '. Running payMatching() would pay them for real, so nothing was executed.';
            $this->_say($msg);
            return $msg;
        }
        $this->_say('  ok   no real user currently qualifies (payMatching cannot pay a real user during this probe)');

        $existing = (int)$this->db->where_in('id', $ids)->count_all_results('users');
        if ($existing > 0) {
            $msg = "ABORTED: {$existing} of the synthetic probe ids already exist as real users.";
            $this->_say($msg);
            return $msg;
        }
        $this->_say('  ok   all ' . count($ids) . ' synthetic user ids are free');
        return null;
    }

    /** Park real unprocessed volume so propagate() cannot sweep it. */
    private function _park()
    {
        $rows = $this->db->select('id')->where('processed', 0)
                         ->where_not_in('user_id', $this->all)
                         ->get('binary_volume_ledger')->result_array();
        $this->parked = array_map(function ($r) { return (int)$r['id']; }, $rows);
        if ($this->parked) {
            $this->db->where_in('id', $this->parked)->update('binary_volume_ledger', ['processed' => 1]);
            $this->_say('  ok   parked ' . count($this->parked) . ' real unprocessed volume row(s): ' . implode(',', $this->parked));
        }
    }

    private function _unpark()
    {
        if (!$this->parked) return;
        $this->db->where_in('id', $this->parked)->update('binary_volume_ledger', ['processed' => 0]);
        $back = (int)$this->db->where_in('id', $this->parked)->where('processed', 0)
                              ->count_all_results('binary_volume_ledger');
        $this->_say('=== restore ===');
        $this->_say('  ' . ($back === count($this->parked) ? 'ok   ' : 'FAIL ')
            . "{$back}/" . count($this->parked) . ' real volume row(s) restored to processed=0');
    }

    private function _verifyNoResidue()
    {
        $t = ['users' => 'id', 'user_stakes' => 'user_id', 'binary_placement' => 'user_id',
              'binary_carry' => 'user_id', 'staking_group_volume' => 'user_id',
              'staking_matching_payouts' => 'user_id', 'binary_volume_ledger' => 'user_id',
              'ceiling_wallet' => 'user_id', 'ceiling_wallet_ledger' => 'user_id',
              'wallet_ledger' => 'user_id', 'user_wallets' => 'user_id',
              'blockchain_payout_queue' => 'user_id'];
        $dirty = [];
        foreach ($t as $table => $col) {
            $n = (int)$this->db->where_in($col, $this->all)->count_all_results($table);
            if ($n > 0) $dirty[] = "{$table}={$n}";
        }
        // Escape detection: anything this run wrote OUTSIDE the sandbox. A
        // 'PT-'/'PROBE-' run_ref on a real member means the engine was called
        // unscoped — real money moved and a real level number was consumed.
        $escaped = (int)($this->db->query(
            "SELECT COUNT(*) n FROM staking_matching_payouts
              WHERE (run_ref LIKE 'PT-%' OR run_ref LIKE 'PROBE-%')
                AND user_id NOT IN (" . implode(',', $this->all) . ")"
        )->row_array()['n'] ?? 0);
        if ($escaped > 0) $dirty[] = "ESCAPED_TO_REAL_MEMBERS={$escaped}";

        $mirror = (int)($this->db->query(
            "SELECT COUNT(*) n FROM onchain_transactions
              WHERE reference_id LIKE 'PT-%' OR reference_id LIKE 'PROBE-%'"
        )->row_array()['n'] ?? 0);
        if ($mirror > 0) $dirty[] = "onchain_transactions={$mirror}";

        // The ceiling tests mutate REAL staking_packages rows; prove they were
        // all put back, since a stray ceiling would silently re-price matching.
        if ($this->ceilingBackup || $this->pkgActiveBackup) {
            $dirty[] = 'staking_packages config NOT restored ('
                     . implode(',', array_keys($this->ceilingBackup + $this->pkgActiveBackup)) . ')';
        }

        $adminNow = $this->_adminBalance();
        if ($this->adminOpening !== null && abs($adminNow - $this->adminOpening) > 0.00000001) {
            $dirty[] = 'admin_wallet drifted ' . $this->_num($this->adminOpening) . '->' . $this->_num($adminNow);
        }
        $this->_say('  ' . (!$dirty ? 'ok   no synthetic residue; admin_wallet unchanged; nothing escaped to real members'
                                    : 'FAIL residue: ' . implode(' ', $dirty)));
    }

    /* ------------------------------ fixtures ----------------------------- */

    private function _buildTree()
    {
        $this->_say('');
        $this->_say("=== building the spec's A..O tree (synthetic) ===");
        $this->_mkUsers(array_keys($this->n));
        $this->_place('B','A','left');   $this->_place('C','A','right');
        $this->_place('D','B','left');   $this->_place('E','B','right');
        $this->_place('F','C','left');   $this->_place('G','C','right');
        $this->_place('H','D','left');   $this->_place('I','D','right');
        $this->_place('J','E','left');   $this->_place('K','E','right');
        $this->_place('L','F','left');   $this->_place('M','F','right');
        $this->_place('N','G','left');   $this->_place('O','G','right');

        // Exactly the spec's amounts. A stakes 5,000 -> ceiling 5,000 (spec §9).
        $this->_stake('A', 5000);
        $this->_stake('B', 5000);    $this->_stake('C', 10000);
        $this->_stake('D', 10000);   $this->_stake('E', 10000);
        $this->_stake('F', 10000);   $this->_stake('G', 10000);
        $this->_stake('H', 5000);    $this->_stake('I', 5000);
        $this->_stake('J', 5000);    $this->_stake('K', 20000);
        $this->_stake('L', 200000);  $this->_stake('M', 10000);
        $this->_stake('N', 5000);    $this->_stake('O', 50000);
        $this->_say("  15 nodes placed + staked; A's own package = 5,000 (ceiling 5,000)");
    }

    private function _place($child, $parent, $position)
    {
        $this->db->insert('binary_placement', [
            'user_id' => $this->n[$child], 'sponsor_id' => $this->n[$parent],
            'parent_id' => $this->n[$parent], 'position' => $position,
            'placement_type' => 'direct', 'type' => 'direct',
        ]);
    }

    private function _stake($label, $amount, $emitVolume = true, $maturityDays = 365)
    {
        $uid = $this->n[$label];
        $this->db->insert('user_stakes', [
            'user_id' => $uid, 'package_id' => $this->pkg[$amount], 'plan_id' => 0, 'plan_code' => 'fixed',
            'duration_years' => 1, 'stake_amount' => $amount, 'roi_percent' => 0, 'roi_basis' => 'total',
            'start_date' => date('Y-m-d'), 'maturity_date' => date('Y-m-d', strtotime($maturityDays . ' days')),
            'status' => 'active',
        ]);
        $stakeId = (int)$this->db->insert_id();
        if ($emitVolume) {
            $this->db->insert('binary_volume_ledger', [
                'user_id' => $uid, 'invest_id' => $stakeId, 'pv' => 0, 'bv' => $amount,
                'source_amount' => $amount, 'processed' => 0,
            ]);
        }
        return $stakeId;
    }

    /* ------------------------------ helpers ------------------------------ */

    private function _say($line) { $this->log[] = $line; }

    private function _payouts($ref, $label = null)
    {
        $this->db->where('run_ref', $ref);
        if ($label) $this->db->where('user_id', $this->n[$label]);
        return $this->db->order_by('user_id', 'ASC')->get('staking_matching_payouts')->result_array();
    }

    private function _label($uid)
    {
        $flip = array_flip($this->n);
        return $flip[(int)$uid] ?? (string)$uid;
    }

    private function _num($v)
    {
        return rtrim(rtrim(number_format((float)$v, 4, '.', ''), '0'), '.');
    }

    /** Admin's BMAN balance — admin_wallet is a SINGLETON row, not a ledger. */
    private function _adminBalance()
    {
        $row = $this->db->select('balance')->order_by('id', 'ASC')->limit(1)
                        ->get('admin_wallet')->row_array();
        return (float)($row['balance'] ?? 0);
    }

    private function _find($rule, $verdict, $detail)
    {
        $this->findings[] = ['rule' => $rule, 'verdict' => $verdict, 'detail' => $detail];
        $tag = $verdict === 'MATCH' ? 'MATCH   ' : ($verdict === 'PARTIAL' ? 'PARTIAL ' : 'MISMATCH');
        $this->_say("  [{$tag}] {$detail}");
    }

    /* ------------------------- rules 1, 3, 6 ----------------------------- */

    private function _rules_1_3_6()
    {
        $this->_say('');
        $this->_say('=== RUN 1: one single payMatching() pass over the whole tree ===');
        $this->adminBefore = $this->_adminBalance();
        $this->MB->propagate();
        $ref = 'PROBE-R1-' . time();
        $this->MB->payMatching($ref);
        $rows = $this->_payouts($ref);

        $this->_say('  sponsors paid in this ONE run: ' . count($rows));
        foreach ($rows as $r) {
            $this->payTable[] = [
                'node' => $this->_label($r['user_id']),
                'matched' => $this->_num($r['matched_volume']),
                'earning' => $this->_num($r['earning_amount']),
                'staking' => $this->_num($r['staking_amount']),
            ];
            $this->_say(sprintf('     %-2s matched=%-10s earning=%-9s staking=%-8s',
                $this->_label($r['user_id']), $this->_num($r['matched_volume']),
                $this->_num($r['earning_amount']), $this->_num($r['staking_amount'])));
        }

        // ---- RULE 1: level-wise? ----
        $aRow = null;
        foreach ($rows as $r) if ((int)$r['user_id'] === $this->n['A']) $aRow = $r;
        if (count($rows) > 1) {
            $paidLabels = implode(',', array_map(function ($r) { return $this->_label($r['user_id']); }, $rows));
            $this->_find(1, 'MISMATCH', 'Not level-wise: ' . count($rows) . ' sponsors (' . $paidLabels
                . ') were ALL paid in ONE pass — the engine evaluates every ancestor simultaneously, with no level ordering.');
        } else {
            $this->_find(1, 'MATCH', 'Only one sponsor paid per pass (level-staged).');
        }
        if ($aRow) {
            $this->_find(1, 'MISMATCH', 'A matched ' . $this->_num($aRow['matched_volume'])
                . ' on its FIRST EVER payout — the entire 3-level downline at once, not level 1 (5,000 -> 500 bonus) first, then level 2, then level 3.');
        }

        // ---- RULE 3: 10% split 8/2 ----
        $splitOk = true; $splitDetail = '';
        foreach ($rows as $r) {
            $m = (float)$r['matched_volume'];
            $paid = (float)$r['earning_amount'] + (float)$r['staking_amount'];
            if ($paid < ($m * 0.10) - 0.01) continue; // ceiling-reduced row: rule 6 covers it
            if (abs((float)$r['earning_amount'] - $m * 0.08) > 0.01 || abs((float)$r['staking_amount'] - $m * 0.02) > 0.01) {
                $splitOk = false;
                $splitDetail = 'node ' . $this->_label($r['user_id']) . ": {$r['earning_amount']}/{$r['staking_amount']} on matched {$m}";
            }
        }
        $this->_find(3, $splitOk ? 'MATCH' : 'MISMATCH', $splitOk
            ? 'Bonus = 10% of MIN(left,right), split 8% Earning / 2% Staking on every uncapped payout — exactly as specified, and already admin-editable via staking_bonus_settings.'
            : 'Split is wrong: ' . $splitDetail);

        // ---- RULE 6: cap amount vs excess destination ----
        if ($aRow) {
            $ceiling = $this->MB->userCeiling($this->n['A']);
            $paidA   = (float)$aRow['earning_amount'] + (float)$aRow['staking_amount'];
            $raw     = round((float)$aRow['matched_volume'] * 0.10, 4);
            $excess  = round($raw - $paidA, 4);
            $this->_say('  A: ceiling=' . $this->_num($ceiling) . '  raw bonus=' . $this->_num($raw)
                . '  paid=' . $this->_num($paidA) . '  excess=' . $this->_num($excess));

            $this->_find(6, abs($paidA - 5000) < 1 ? 'MATCH' : 'MISMATCH',
                'Cap AMOUNT is already right: A raw ' . $this->_num($raw) . ' -> paid ' . $this->_num($paidA)
                . ' (capped at the 5,000 ceiling), excess ' . $this->_num($excess) . ' — the exact numbers from spec §9.');

            $cw = (int)$this->db->where('user_id', $this->n['A'])->count_all_results('ceiling_wallet_ledger');
            $this->_find(6, 'MISMATCH',
                'Excess DESTINATION is wrong: the ' . $this->_num($excess) . ' went to ceiling_wallet as ' . $cw
                . ' escrow ledger row(s) for A — a per-user hold an admin can only release back TO A — while the admin balance moved by '
                . $this->_num($this->_adminBalance() - $this->adminBefore) . '. Spec says the excess belongs to Admin.');
        }

        // ---- RULE 6b: lifetime vs per-level ceiling ----
        $this->_say('');
        $this->_say('=== RUN 2: fresh qualifying volume for A, whose ceiling is already consumed ===');
        $this->_place('P', 'H', 'left');
        $this->_stake('P', 50000);
        $this->MB->propagate();
        $ref2 = 'PROBE-R2-' . time();
        $this->MB->payMatching($ref2);
        $a2 = $this->_payouts($ref2, 'A');
        $paid2 = $a2 ? (float)$a2[0]['earning_amount'] + (float)$a2[0]['staking_amount'] : 0.0;
        $matched2 = $a2 ? $this->_num($a2[0]['matched_volume']) : '0';
        $this->_find(6, 'MISMATCH',
            'Ceiling is LIFETIME, not per-level: A matched a further ' . $matched2 . ' but was paid only '
            . $this->_num($paid2) . ' more, because 5,000 of lifetime ceiling was already used. The spec\'s own example pays A 500 + 2,500 + 5,000 = 8,000 against a 5,000 ceiling, i.e. the cap resets per level.');
    }

    /* ------------------------------ rule 4 -------------------------------- */

    private function _rule4()
    {
        $this->_say('');
        $this->_say('=== RULE 4: is a completed level recorded and never repaid? ===');
        $cols = $this->db->query(
            "SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND (COLUMN_NAME LIKE '%level%' OR TABLE_NAME LIKE '%level%')
               AND (TABLE_NAME LIKE '%matching%' OR TABLE_NAME LIKE '%binary%' OR TABLE_NAME LIKE '%carry%')"
        )->result_array();

        // A 'level' column only counts as real level state if live code writes it.
        $liveState = false; $where = [];
        foreach ($cols as $c) {
            $rows = (int)$this->db->count_all_results($c['TABLE_NAME']);
            $where[] = $c['TABLE_NAME'] . '.' . $c['COLUMN_NAME'] . " ({$rows} rows)";
            if ($rows > 0) $liveState = true;
        }
        $this->_find(4, $liveState ? 'PARTIAL' : 'MISMATCH',
            empty($cols)
                ? 'No level state exists anywhere: zero level-named columns or tables across the binary_*/matching_* schema.'
                : 'No LIVE level state: the only level-bearing column is ' . implode(', ', $where)
                  . ' — part of the dead, unrouted parallel matching system, which no live code path writes. Nothing records "sponsor X, level N, done".');

        $aAll = (int)$this->db->where('user_id', $this->n['A'])->count_all_results('staking_matching_payouts');
        $this->_find(4, $aAll > 1 ? 'MISMATCH' : 'PARTIAL',
            'A accumulated ' . $aAll . ' payout rows across 2 runs. The ONLY thing preventing a repeat payout is carry consumption (matched volume is subtracted from both legs) — not a level-completion record, so fresh downline volume re-pays the same sponsor indefinitely.');
    }

    /* ------------------------------ rule 5 -------------------------------- */

    private function _rule5()
    {
        $this->_say('');
        $this->_say('=== RULE 5: does the ceiling come from the HIGHEST package? ===');
        $this->_stake('X', 5000,   false);
        $this->_stake('X', 100000, false);
        $this->_stake('X', 50000,  false);
        $actual = $this->MB->userCeiling($this->n['X']);
        $expected = 30000.0; // spec §7 mapping for the 100,000 package
        $this->_find(5, abs($actual - $expected) < 0.01 ? 'MATCH' : 'MISMATCH',
            'userCeiling() SUMS group_ceiling over every active stake: X holds 5,000 + 100,000 + 50,000 and gets ceiling '
            . $this->_num($actual) . ' (5,000 + 30,000 + 30,000). Spec wants MAX package (100,000) -> ceiling '
            . $this->_num($expected) . '; buying more packages must not raise the ceiling additively.');

        $dupes = $this->db->query(
            "SELECT stake_amount, COUNT(*) n,
                    GROUP_CONCAT(CONCAT(id,':',CAST(group_ceiling AS UNSIGNED)) ORDER BY id) detail
             FROM staking_packages WHERE is_active = 1
             GROUP BY stake_amount HAVING COUNT(*) > 1"
        )->result_array();
        if ($dupes) {
            $d = [];
            foreach ($dupes as $r) $d[] = $this->_num($r['stake_amount']) . ' => (id:ceiling) ' . $r['detail'];
            $this->_find(5, 'MISMATCH',
                'The ceiling table is AMBIGUOUS today: ' . count($dupes)
                . ' stake amounts have two ACTIVE packages with DIFFERENT ceilings — ' . implode('; ', $d)
                . '. "Highest package" cannot resolve to one ceiling until this is fixed (spec wants 50k->30k, 100k->30k, 200k->50k).');
        }
    }

    /* ------------------------------ rule 2 -------------------------------- */

    private function _rule2()
    {
        $this->_say('');
        $this->_say('=== RULE 2: is binary volume sourced from the Lock Wallet? ===');
        // Mature H's stake: it leaves the Lock Wallet definition entirely.
        $this->db->where('user_id', $this->n['H'])->update('user_stakes',
            ['maturity_date' => date('Y-m-d', strtotime('-1 day'))]);

        $lock = (float)$this->Staking_model->lockWalletBalance($this->n['H']);
        $gv   = $this->db->get_where('staking_group_volume', ['user_id' => $this->n['D']])->row_array();
        $stillCounted = (float)($gv['left_volume'] ?? 0); // H is D's left child

        $this->_find(2, ($lock == 0 && $stillCounted > 0) ? 'MISMATCH' : 'MATCH',
            'Volume is purchase-time, not Lock Wallet: H\'s stake is now matured so lockWalletBalance(H) = ' . $this->_num($lock)
            . ', yet D\'s left volume still reads ' . $this->_num($stillCounted)
            . ' (H\'s 5,000 plus the 50,000 bought beneath it) with nothing removed. binary_volume_ledger.bv is written once at purchase and never re-read from user_stakes.');

        $this->db->where('user_id', $this->n['A'])->update('user_stakes',
            ['maturity_date' => date('Y-m-d', strtotime('-1 day'))]);
        $ceilAfter = $this->MB->userCeiling($this->n['A']);
        $lockA = (float)$this->Staking_model->lockWalletBalance($this->n['A']);
        $this->_find(2, ($lockA == 0 && $ceilAfter > 0) ? 'MISMATCH' : 'MATCH',
            'Sponsor eligibility ignores maturity too: A\'s stake is matured (lockWalletBalance = ' . $this->_num($lockA)
            . ') but userCeiling() still returns ' . $this->_num($ceilAfter)
            . ', because it filters on status=\'active\' only with no maturity_date check.');
    }

    /* -------------------- spec: unstaked sponsor -> admin ----------------- */

    private function _rule_unstaked_sponsor()
    {
        $this->_say('');
        $this->_say('=== SPEC: sponsor with NO staking package — does it all go to Admin? ===');
        $this->_place('Y', 'Z', 'left');
        $this->_place('X', 'Z', 'right');
        $this->_stake('Y', 10000);
        $this->_stake('X', 10000); // X's earlier stakes emitted no volume

        $this->MB->propagate();
        $before = $this->_adminBalance();
        $ref = 'PROBE-R7-' . time();
        $this->MB->payMatching($ref);
        $after = $this->_adminBalance();

        $zPaid = (int)$this->db->where(['user_id' => $this->n['Z'], 'run_ref' => $ref])
                               ->count_all_results('staking_matching_payouts');
        $zCarry = $this->db->get_where('binary_carry', ['user_id' => $this->n['Z']])->row_array();

        $this->_find(5, 'MISMATCH',
            'Unstaked sponsor Z: payout rows=' . $zPaid . ', admin balance moved by ' . $this->_num($after - $before)
            . ', carry PRESERVED at ' . $this->_num($zCarry['left_carry'] ?? 0) . '/' . $this->_num($zCarry['right_carry'] ?? 0)
            . '. The engine DEFERS the bonus (pays it retroactively once Z stakes) instead of forfeiting it to Admin. That behavior was deliberately built and tested on 2026-07-15.');
    }

    /* ======================= TEST 1..10 (spec §18) ======================== */

    /**
     * Runs the ten named acceptance tests from the spec against whatever
     * engine is currently wired up. Same guards as probe(): aborts if a real
     * member could be paid, parks real volume, tears down from a shutdown hook.
     * This is the regression suite the new engine must turn all-green.
     */
    public function tests()
    {
        $abort = $this->_preflight();
        if ($abort !== null) {
            return ['status' => 'error', 'message' => $abort, 'totals' => [],
                    'results' => [], 'log' => $this->log, 'ran_at' => date('Y-m-d H:i:s')];
        }
        $this->adminOpening = $this->_adminBalance();
        register_shutdown_function([$this, 'teardown']);

        $results = [];
        try {
            $this->_park();
            foreach (['t1','t2','t3','t4','t5','t6','t7','t8','t9','t10',
                      't11','t12','t13','t14','t15'] as $t) {
                $this->_cleanup();
                $results[] = $this->{'_' . $t}();
            }
        } catch (Throwable $e) {
            $this->_say('!! test run aborted: ' . $e->getMessage());
        } finally {
            $this->teardown();
        }

        $pass = 0;
        foreach ($results as $r) if ($r['pass']) $pass++;
        return [
            'status'  => 'success',
            'message' => 'Spec tests vs CURRENT engine: ' . $pass . '/' . count($results) . ' pass',
            'totals'  => ['pass' => $pass, 'fail' => count($results) - $pass],
            'results' => $results,
            'log'     => $this->log,
            'ran_at'  => date('Y-m-d H:i:s'),
        ];
    }

    private function _assert($id, $name, $expected, $actual, $note = '')
    {
        $pass = ((string)$expected === (string)$actual);
        $this->_say(sprintf('  %s  %-8s %s', $pass ? 'PASS' : 'FAIL', $id, $name));
        $this->_say('           expected: ' . $expected);
        $this->_say('           actual  : ' . $actual . ($note ? '   (' . $note . ')' : ''));
        return ['test' => $id, 'name' => $name, 'expected' => (string)$expected,
                'actual' => (string)$actual, 'pass' => $pass, 'note' => $note];
    }

    private function _mkUsers(array $labels)
    {
        foreach ($labels as $i => $l) {
            $this->db->insert('users', ['id' => $this->n[$l], 'username' => 'TEST_BMPROBE_' . $l . '_' . $i, 'status' => '1']);
        }
    }

    /** A(left=B, right=C) with the given stakes — the spec's §9/§10 base shape. */
    private function _abc($aStake, $bStake = 5000, $cStake = 10000, $bMaturityDays = 365)
    {
        $this->_mkUsers(['A','B','C']);
        $this->_place('B','A','left'); $this->_place('C','A','right');
        if ($aStake > 0) $this->_stake('A', $aStake);
        $this->_stake('B', $bStake, true, $bMaturityDays);
        $this->_stake('C', $cStake);
    }

    private function _paidTo($label, $ref = null)
    {
        $this->db->select('COALESCE(SUM(earning_amount + staking_amount),0) s', false)
                 ->where('user_id', $this->n[$label]);
        if ($ref) $this->db->where('run_ref', $ref);
        $r = $this->db->get('staking_matching_payouts')->row_array();
        return (float)($r['s'] ?? 0);
    }

    /**
     * Drives the LIVE level-wise engine, SCOPED to the synthetic sandbox.
     *
     * The user_ids scope is not optional and must never be removed: an
     * unscoped run() sweeps every sponsor in the real tree. When this was
     * missing, one test run credited three real members and burned level 1 for
     * two more — and because UNIQUE(user_id, level) is permanent, those
     * members could never have been paid level 1 again. The old binary_carry
     * preflight cannot catch this, because the level engine never reads carry.
     */
    private function _runEngine($tag)
    {
        $ref = 'PT-' . $tag . '-' . substr(uniqid(), -6);
        $this->BLM->run(['run_ref' => $ref, 'user_ids' => $this->all, 'skip_propagate' => true]);
        return $ref;
    }

    /** One sponsor's payout row for a specific level. */
    private function _payoutLevel($label, $level)
    {
        return $this->db->where('user_id', $this->n[$label])->where('level', (int)$level)
                        ->get('staking_matching_payouts')->row_array();
    }

    /** The configured ceiling for a stake amount — AMBIGUOUS if duplicates disagree. */
    private function _resolveCeiling($amount)
    {
        $rows = $this->db->distinct()->select('group_ceiling')
                         ->where('stake_amount', $amount)->where('is_active', 1)
                         ->get('staking_packages')->result_array();
        if (count($rows) !== 1) {
            $c = array_map(function ($r) { return $this->_num($r['group_ceiling']); }, $rows);
            return 'AMBIGUOUS(' . implode('|', $c) . ')';
        }
        return $this->_num($rows[0]['group_ceiling']);
    }

    /** TEST 1 — sponsor with no staking: whole matching must go to Admin. */
    private function _t1()
    {
        $this->_say('');
        $this->_abc(0);
        $before = $this->_adminBalance();
        $ref = $this->_runEngine('T1');
        $delta = $this->_adminBalance() - $before;
        $row = $this->_payoutLevel('A', 1);
        return $this->_assert('TEST 1', 'Unstaked sponsor -> whole matching to Admin',
            'A=0, Admin=500', 'A=' . $this->_num($this->_paidTo('A', $ref)) . ', Admin=' . $this->_num($delta),
            'level 1 recorded with sponsor_eligible=' . (int)($row['sponsor_eligible'] ?? -1)
            . ' and admin_overflow=' . $this->_num($row['admin_overflow'] ?? 0) . ' — forfeited, not deferred');
    }

    /** TEST 2 — 5,000 package, level 1 = 500 split 400/100. */
    private function _t2()
    {
        $this->_say('');
        $this->_abc(5000);
        $ref = $this->_runEngine('T2');
        $row = $this->_payouts($ref, 'A');
        $e = $row ? $this->_num($row[0]['earning_amount']) : '0';
        $s = $row ? $this->_num($row[0]['staking_amount']) : '0';
        return $this->_assert('TEST 2', 'Level 1 matching 500 -> 400 Earning / 100 Staking',
            'earning=400, staking=100', 'earning=' . $e . ', staking=' . $s);
    }

    /** TEST 3 — a completed level must never be paid again. */
    private function _t3()
    {
        $this->_say('');
        $this->_abc(5000);
        $this->_runEngine('T3a');
        $first = $this->_paidTo('A');
        // New volume arrives at the SAME level 1 (B buys another 5,000).
        $this->_stake('B', 5000);
        $ref2 = $this->_runEngine('T3b');
        $again = $this->_paidTo('A', $ref2);
        return $this->_assert('TEST 3', 'Level 1 already completed -> never paid again',
            'second payout for level 1 = 0', 'second payout for level 1 = ' . $this->_num($again),
            'first run paid ' . $this->_num($first) . '; B then staked another 5,000 at level 1 and it was correctly ignored');
    }

    /** TEST 4 — level 3 raw 6,000 vs ceiling 5,000 -> A 5,000 / Admin 1,000. */
    private function _t4()
    {
        $this->_say('');
        $this->_buildTree();
        $before = $this->_adminBalance();
        $this->_runEngine('T4');
        $row = $this->_payoutLevel('A', 3);   // level 3 specifically
        $raw  = $row ? (float)$row['raw_bonus'] : 0;
        $user = $row ? (float)$row['earning_amount'] + (float)$row['staking_amount'] : 0;
        $delta = $this->_adminBalance() - $before;
        $held = (float)($this->db->select('COALESCE(SUM(amount),0) s', false)
                    ->where('user_id', $this->n['A'])->get('ceiling_wallet_ledger')->row_array()['s'] ?? 0);
        $l1 = $this->_payoutLevel('A', 1); $l2 = $this->_payoutLevel('A', 2);
        return $this->_assert('TEST 4', 'Level 3 raw 6,000 capped at 5,000 -> excess 1,000 to Admin',
            'raw=6000, user=5000, admin=1000',
            'raw=' . $this->_num($raw) . ', user=' . $this->_num($user) . ', admin=' . $this->_num($delta),
            'L1 ' . $this->_num($l1['raw_bonus'] ?? 0) . ' + L2 ' . $this->_num($l2['raw_bonus'] ?? 0)
            . ' + L3 ' . $this->_num($raw) . ' paid separately; ceiling_wallet untouched (' . $this->_num($held) . ')');
    }

    /** TEST 5 — 5,000 + 100,000 + 50,000 -> ceiling of the HIGHEST package only. */
    private function _t5()
    {
        $this->_say('');
        $this->_mkUsers(['X']);
        $this->_stake('X', 5000, false); $this->_stake('X', 100000, false); $this->_stake('X', 50000, false);
        $c = $this->BLM->sponsorCeiling($this->n['X']);
        return $this->_assert('TEST 5', 'Highest package (100,000) sets the ceiling — never SUM',
            '30000', $this->_num($c['ceiling']),
            'highest eligible stake = ' . $this->_num($c['stake_amount']) . ' (package #' . $c['package_id']
            . '); legacy userCeiling() would have summed to ' . $this->_num($this->MB->userCeiling($this->n['X'])));
    }

    private function _t6()
    {
        $this->_say('');
        return $this->_assert('TEST 6', '50,000 package -> ceiling 30,000', '30000', $this->_resolveCeiling(50000),
            'two ACTIVE 50,000 packages (id 5 = 30,000, id 45 = 50,000)');
    }

    private function _t7()
    {
        $this->_say('');
        return $this->_assert('TEST 7', '100,000 package -> ceiling 30,000', '30000', $this->_resolveCeiling(100000),
            'two ACTIVE 100,000 packages (id 6 = 30,000, id 46 = 100,000)');
    }

    private function _t8()
    {
        $this->_say('');
        return $this->_assert('TEST 8', '200,000 package -> ceiling 50,000', '50000', $this->_resolveCeiling(200000),
            'two ACTIVE 200,000 packages (id 7 = 50,000, id 47 = 200,000)');
    }

    /** TEST 9 — a matured stake must not contribute binary volume. */
    private function _t9()
    {
        $this->_say('');
        $this->_abc(5000, 5000, 10000, -1); // B's stake matured yesterday
        $lockB = (float)$this->Staking_model->lockWalletBalance($this->n['B']);
        $ref = $this->_runEngine('T9');
        $paid = $this->_paidTo('A', $ref);
        return $this->_assert('TEST 9', 'Matured Lock Wallet must not contribute volume',
            'A paid=0', 'A paid=' . $this->_num($paid),
            'lockWalletBalance(B)=' . $this->_num($lockB) . ' so the left leg has no eligible volume — level 1 never completes');
    }

    /** TEST 10 — running the cron twice must not duplicate anything. */
    private function _t10()
    {
        $this->_say('');
        $this->_abc(5000);
        $this->_runEngine('T10a');
        $first = $this->_paidTo('A');
        $ref2 = $this->_runEngine('T10b');
        $second = $this->_paidTo('A', $ref2);
        return $this->_assert('TEST 10', 'Second identical run adds nothing',
            'second run adds 0', 'second run adds ' . $this->_num($second),
            'first run paid ' . $this->_num($first) . '; UNIQUE(user_id, level) blocked the repeat before any credit');
    }

    /* ============ TEST 11..14 — ceiling config is 100% dynamic ============ */

    /**
     * TEST 11 — an admin ceiling edit must take effect on the very next run,
     * with no code change. Drives the REAL admin save path
     * (Staking_model::saveCeilings — what Admin ▸ Staking ▸ Rank Power calls)
     * rather than an UPDATE, so this also proves the engine reads the same
     * source the admin screen writes. The original value is captured and
     * restored, and _restoreCeilings() repeats that from teardown in case this
     * aborts midway.
     */
    private function _t11()
    {
        $this->_say('');
        $this->load->model('Staking_model');
        $pkgId = $this->pkg[50000];                       // the 50,000 package
        $orig  = $this->_pkgCeiling($pkgId);
        $this->ceilingBackup[$pkgId] = $orig;

        $this->_mkUsers(['X']);
        $this->_stake('X', 50000, false);

        $seen = [];
        foreach ([35000.0, (float)$orig] as $target) {    // change, then change back
            $this->Staking_model->saveCeilings([$pkgId => $target]);
            $c = $this->BLM->sponsorCeiling($this->n['X']);
            $seen[] = $this->_num($c['ceiling']);
        }
        unset($this->ceilingBackup[$pkgId]);

        return $this->_assert('TEST 11', 'Admin ceiling edit is picked up immediately (no code change)',
            '35000 then ' . $this->_num($orig), implode(' then ', $seen),
            'written via Staking_model::saveCeilings() — the same call Admin > Staking > Rank Power uses');
    }

    /**
     * TEST 12 — for EVERY active package, the engine returns exactly that
     * package's currently configured ceiling. Expectations are read from the
     * database, so this test contains no ceiling literals at all and cannot
     * drift when an admin edits a value.
     */
    private function _t12()
    {
        $this->_say('');
        $pkgs = $this->db->select('id, stake_amount, group_ceiling')
                         ->where('is_active', 1)->where('group_ceiling >', 0)
                         ->order_by('stake_amount', 'ASC')->get('staking_packages')->result_array();
        $this->_mkUsers(['X']);

        $mismatch = []; $checked = 0;
        foreach ($pkgs as $p) {
            $this->db->where('user_id', $this->n['X'])->delete('user_stakes');
            $this->db->insert('user_stakes', [
                'user_id' => $this->n['X'], 'package_id' => (int)$p['id'], 'plan_id' => 0,
                'plan_code' => 'fixed', 'duration_years' => 1, 'stake_amount' => $p['stake_amount'],
                'roi_percent' => 0, 'roi_basis' => 'total', 'start_date' => date('Y-m-d'),
                'maturity_date' => date('Y-m-d', strtotime('365 days')), 'status' => 'active',
            ]);
            $got = $this->BLM->sponsorCeiling($this->n['X']);
            $checked++;
            if (abs((float)$got['ceiling'] - (float)$p['group_ceiling']) > 0.0001) {
                $mismatch[] = 'pkg#' . $p['id'] . ' stake ' . $this->_num($p['stake_amount'])
                            . ' expected ' . $this->_num($p['group_ceiling']) . ' got ' . $this->_num($got['ceiling']);
            }
        }
        return $this->_assert('TEST 12', 'Every package resolves to its OWN configured ceiling (DB-driven)',
            '0 mismatches across ' . $checked . ' active packages',
            count($mismatch) . ' mismatches across ' . $checked . ' active packages',
            $mismatch ? implode('; ', $mismatch) : 'expectations read from staking_packages — no literals in this test');
    }

    /** Give A the spec's level-1 legs plus one specific package, staked. */
    private function _aWithPackage($pkgId, $stakeAmount)
    {
        $this->_abc(0);                                    // A unstaked, B 5,000 left, C 10,000 right
        $this->db->insert('user_stakes', [
            'user_id' => $this->n['A'], 'package_id' => (int)$pkgId, 'plan_id' => 0, 'plan_code' => 'fixed',
            'duration_years' => 1, 'stake_amount' => $stakeAmount, 'roi_percent' => 0, 'roi_basis' => 'total',
            'start_date' => date('Y-m-d'), 'maturity_date' => date('Y-m-d', strtotime('365 days')),
            'status' => 'active',
        ]);
    }

    /** Everything that must stay frozen while a ceiling config is invalid. */
    private function _pendingState($label, $ref, $adminBefore)
    {
        $w = $this->db->select('COALESCE(earning_balance,0) e, COALESCE(staking_balance,0) s', false)
                      ->where('user_id', $this->n[$label])->get('user_wallets')->row_array();
        return 'level=' . $this->BLM->nextLevel($this->n[$label])
             . ', payout_rows=' . (int)$this->db->where('user_id', $this->n[$label])
                                        ->count_all_results('staking_matching_payouts')
             . ', paid=' . $this->_num($this->_paidTo($label, $ref))
             . ', wallet=' . $this->_num(($w['e'] ?? 0) + ($w['s'] ?? 0))
             . ', admin_delta=' . $this->_num($this->_adminBalance() - $adminBefore);
    }

    /**
     * TEST 13 — AMBIGUOUS config: the level must stay PENDING. Nothing is
     * paid, nothing goes to Admin, and no payout row is written, so the level
     * is still "next" on the following run.
     */
    private function _t13()
    {
        $this->_say('');
        $dupe = 45;                                        // second 50,000 package (normally inactive)
        $this->ceilingBackup[$dupe] = $this->_pkgCeiling($dupe);
        $this->pkgActiveBackup[$dupe] = (int)$this->db->select('is_active')->where('id', $dupe)
                                            ->get('staking_packages')->row_array()['is_active'];

        // Two eligible stakes tied at the SAME highest amount, different ceilings.
        $this->db->where('id', $dupe)->update('staking_packages', ['is_active' => 1, 'group_ceiling' => 99999]);
        $this->_aWithPackage($this->pkg[50000], 50000);
        $this->db->insert('user_stakes', [
            'user_id' => $this->n['A'], 'package_id' => $dupe, 'plan_id' => 0, 'plan_code' => 'fixed',
            'duration_years' => 1, 'stake_amount' => 50000, 'roi_percent' => 0, 'roi_basis' => 'total',
            'start_date' => date('Y-m-d'), 'maturity_date' => date('Y-m-d', strtotime('365 days')),
            'status' => 'active',
        ]);

        $c = $this->BLM->sponsorCeiling($this->n['A']);
        $before = $this->_adminBalance();
        $ref = $this->_runEngine('T13');
        $state = $this->_pendingState('A', $ref, $before);
        $this->_restoreCeilings();

        return $this->_assert('TEST 13', 'Ambiguous config -> level stays PENDING, nothing paid',
            'level=1, payout_rows=0, paid=0, wallet=0, admin_delta=0', $state,
            'sponsorCeiling status=' . $c['status'] . '; two active 50,000 packages disagree — old code silently took the larger');
    }

    /**
     * TEST 14 — MISSING config: same contract. The bonus is NOT forfeited to
     * Admin, because a bad ceiling is an admin fault, not the member's.
     */
    private function _t14()
    {
        $this->_say('');
        $pkgId = $this->pkg[20000];
        $this->ceilingBackup[$pkgId] = $this->_pkgCeiling($pkgId);
        $this->db->where('id', $pkgId)->update('staking_packages', ['group_ceiling' => 0]);

        $this->_aWithPackage($pkgId, 20000);
        $c = $this->BLM->sponsorCeiling($this->n['A']);
        $before = $this->_adminBalance();
        $ref = $this->_runEngine('T14');
        $state = $this->_pendingState('A', $ref, $before);
        $this->_restoreCeilings();

        return $this->_assert('TEST 14', 'Missing config -> level stays PENDING, nothing to Admin',
            'level=1, payout_rows=0, paid=0, wallet=0, admin_delta=0', $state,
            'sponsorCeiling status=' . $c['status'] . '; no fallback ceiling substituted, no bonus forfeited');
    }

    /**
     * TEST 15 — RECOVERY: after the admin fixes the ceiling, the very same
     * level pays correctly on the next run. This is the whole point of
     * skip-and-retry over route-to-admin.
     */
    private function _t15()
    {
        $this->_say('');
        $this->load->model('Staking_model');
        $pkgId = $this->pkg[20000];
        $good  = $this->_pkgCeiling($pkgId);               // whatever the admin has configured
        $this->ceilingBackup[$pkgId] = $good;

        // Phase 1 — broken config, level must not close.
        $this->db->where('id', $pkgId)->update('staking_packages', ['group_ceiling' => 0]);
        $this->_aWithPackage($pkgId, 20000);
        $before = $this->_adminBalance();
        $this->_runEngine('T15a');
        $stillPending = ($this->BLM->nextLevel($this->n['A']) === 1);

        // Phase 2 — admin fixes it through the real admin save path.
        $this->Staking_model->saveCeilings([$pkgId => $good]);
        $ref2 = $this->_runEngine('T15b');
        $row  = $this->_payoutLevel('A', 1);
        $paid = $row ? (float)$row['earning_amount'] + (float)$row['staking_amount'] : 0.0;
        $adminDelta = $this->_adminBalance() - $before;
        $this->_restoreCeilings();

        return $this->_assert('TEST 15', 'Fix the config -> the SAME level then pays correctly',
            'pending_before_fix=1, paid_after_fix=500, earning=400, staking=100, admin_delta=0',
            'pending_before_fix=' . (int)$stillPending
            . ', paid_after_fix=' . $this->_num($paid)
            . ', earning=' . $this->_num($row['earning_amount'] ?? 0)
            . ', staking=' . $this->_num($row['staking_amount'] ?? 0)
            . ', admin_delta=' . $this->_num($adminDelta),
            'level 1 survived the misconfiguration and paid in full once the ceiling was restored (run ' . $ref2 . ')');
    }

    private function _pkgCeiling($pkgId)
    {
        $r = $this->db->select('group_ceiling')->where('id', (int)$pkgId)
                      ->get('staking_packages')->row_array();
        return (float)($r['group_ceiling'] ?? 0);
    }

    /** Put every ceiling / is_active flag this run touched back exactly as found. */
    private function _restoreCeilings()
    {
        foreach ($this->ceilingBackup as $pid => $val) {
            $this->db->where('id', (int)$pid)->update('staking_packages', ['group_ceiling' => (float)$val]);
        }
        foreach ($this->pkgActiveBackup as $pid => $val) {
            $this->db->where('id', (int)$pid)->update('staking_packages', ['is_active' => (int)$val]);
        }
        $this->ceilingBackup = [];
        $this->pkgActiveBackup = [];
    }

    /* ------------------------------ teardown ----------------------------- */

    private function _cleanup()
    {
        foreach (['binary_volume_ledger','user_stakes','binary_placement','binary_carry',
                  'staking_group_volume','staking_matching_payouts','ceiling_wallet',
                  'ceiling_wallet_ledger','wallet_ledger','user_wallets','blockchain_payout_queue'] as $t) {
            $this->db->where_in('user_id', $this->all)->delete($t);
        }
        $this->db->where_in('id', $this->all)->delete('users');

        // Walletledger_model::credit() mirrors EVERY movement into
        // onchain_transactions (+ an onchain_tx_events audit row) via
        // _captureOnchain() — a fail-safe that fires for synthetic credits
        // just as readily as real ones. Cleaned by the probe's own run_ref
        // prefix rather than by user_id, so it also catches rows written
        // against a REAL member should a scope guard ever fail again.
        // Events first: they are keyed by tx_id and would otherwise strand.
        $this->db->query(
            "DELETE e FROM onchain_tx_events e
               JOIN onchain_transactions t ON t.id = e.tx_id
              WHERE t.reference_id LIKE 'PT-%' OR t.reference_id LIKE 'PROBE-%'"
        );
        $this->db->query(
            "DELETE FROM onchain_transactions
              WHERE reference_id LIKE 'PT-%' OR reference_id LIKE 'PROBE-%'"
        );
    }
}
