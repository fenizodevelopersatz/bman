<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Binarylevelmatching_model — LEVEL-WISE binary matching distribution.
 * ---------------------------------------------------------------------------
 * Replaces Stakingmatching_model::payMatching()'s carry-forward matcher (which
 * paid every ancestor in one depth-blind pass) with the level-by-level model:
 *
 *   for each sponsor, for level N = 1, 2, 3, ... in order:
 *     left  = CUMULATIVE eligible Lock Wallet BMAN, levels 1..N, left leg
 *     right = same on the right leg
 *     matched  = MIN(left, right)
 *     raw      = matched x matching_total_percent            (10%)
 *     ceiling  = group_ceiling of the sponsor's HIGHEST eligible package
 *                — FRESH EVERY LEVEL, never a lifetime total
 *     user     = MIN(raw, ceiling)   (0 if the sponsor holds no eligible stake)
 *     admin    = raw - user
 *     user splits 8%/2% into the Earning / Staking wallets
 *
 * Four things this deliberately does NOT do, each a rule the old engine broke:
 *
 *  - It never reads binary_carry / binary_volume_ledger for money. Volume is
 *    recomputed LIVE from user_stakes every run, so a matured stake stops
 *    counting the day it matures. (Stakingmatching_model::propagate() still
 *    runs, purely to keep staking_group_volume / binary_carry populated for the
 *    dashboards and genealogy pages that read them.)
 *  - It never consumes or decrements volume. Level N legitimately re-counts
 *    levels 1..N-1 — that is what makes level 3 pay on 60,000 rather than
 *    35,000 — so there is nothing to subtract, and no way for a paid level to
 *    erase volume the next level still needs.
 *  - It never defers. A sponsor with no eligible stake forfeits the bonus to
 *    Admin at that level, permanently; it is not held for a future purchase.
 *  - It never puts overflow in the user's ceiling_wallet. Excess is Admin
 *    income, credited to admin_wallet + admin_wallet_ledger in the SAME
 *    transaction as the member's credits.
 *
 * Idempotency is a database guarantee, not a code convention: the payout row
 * (UNIQUE(user_id, level)) is INSERTed before any wallet is touched, so a
 * second concurrent or repeated run collides with the key and abandons the
 * level with no money moved. See _payLevel().
 *
 * Scope: binary matching only. ROI, staking purchase, Lock Wallet creation,
 * plans/packages, the wallet distribution matrix, rank systems, gas/on-chain
 * delivery and every other cron are untouched — the on-chain leg still runs
 * exactly as before, in BinaryMatchingPayoutCron, off this table.
 */
class Binarylevelmatching_model extends CI_Model
{
    /** Safety bound on the level walk (a tree this deep is a data bug). */
    private $maxLevels = 50;

    /** The platform-wide Lock Wallet definition — mirrors Staking_model. */
    private $lockWalletStatuses = ['active', 'processing'];

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Walletledger_model', 'L');
    }

    /**
     * Full run. Signature-compatible with Stakingmatching_model::run() so
     * Matchingqueue_model can call either.
     *
     * @return array summary
     */
    public function run($opts = [])
    {
        $ref = !empty($opts['run_ref']) ? $opts['run_ref']
             : 'MB-' . date('Ymd-His') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

        // Keep the legacy volume bookkeeping alive: binary_carry and
        // staking_group_volume feed the admin genealogy tree, dashboard stats
        // and member history pages. They no longer drive a single payment.
        $propagated = 0;
        if (empty($opts['skip_propagate'])) {
            $this->load->model('staking/Stakingmatching_model', 'MB');
            $propagated = $this->MB->propagate();
        }

        $s = $this->db->get_where('staking_bonus_settings', ['id' => 1])->row_array();
        $pct = [
            'total'   => $s ? (float)$s['matching_total_percent']   : 10.0,
            'earning' => $s ? (float)$s['matching_earning_percent'] : 8.0,
            'staking' => $s ? (float)$s['matching_staking_percent'] : 2.0,
        ];
        if ($pct['total'] <= 0) {
            return ['run_ref' => $ref, 'propagated' => $propagated, 'error' => 'matching_total_percent is 0 — nothing to pay'];
        }

        $sponsors = $this->_candidateSponsors($opts);

        $levelsPaid = 0; $users = []; $totMatched = 0.0; $totEarn = 0.0; $totStk = 0.0; $totAdmin = 0.0; $skipped = 0;
        foreach ($sponsors as $uid) {
            $res = $this->processSponsor((int)$uid, $ref, $pct);
            $levelsPaid += $res['levels_paid'];
            $skipped    += $res['skipped'];
            if ($res['levels_paid'] > 0) {
                $users[$uid] = true;
                $totMatched += $res['matched'];
                $totEarn    += $res['earning'];
                $totStk     += $res['staking'];
                $totAdmin   += $res['admin'];
            }
        }

        return [
            'run_ref'        => $ref,
            'propagated'     => $propagated,
            'sponsors_seen'  => count($sponsors),
            'levels_paid'    => $levelsPaid,
            'paid_users'     => count($users),
            'matched_volume' => round($totMatched, 4),
            'earning_paid'   => round($totEarn, 4),
            'staking_paid'   => round($totStk, 4),
            'admin_overflow' => round($totAdmin, 4),
            'skipped_dupes'  => $skipped,
        ];
    }

    /**
     * Every user who has at least one placed child — deterministic order.
     *
     * Two optional scopes, both narrowing and never widening:
     *   'user_id'  — a single sponsor (admin "re-run for this member").
     *   'user_ids' — an explicit whitelist. Used by the spec test harness to
     *                confine a run to its synthetic sandbox: without it, run()
     *                sweeps EVERY sponsor in the tree, so a test would credit
     *                real members and burn their level numbers.
     */
    private function _candidateSponsors($opts = [])
    {
        if (!empty($opts['user_ids']) && is_array($opts['user_ids'])) {
            $ids = array_values(array_filter(array_map('intval', $opts['user_ids'])));
            if (!$ids) return [];
            $rows = $this->db->query(
                "SELECT DISTINCT parent_id AS uid FROM binary_placement
                  WHERE parent_id IN (" . implode(',', $ids) . ") ORDER BY parent_id ASC"
            )->result_array();
            return array_map(function ($r) { return (int)$r['uid']; }, $rows);
        }
        if (!empty($opts['user_id'])) return [(int)$opts['user_id']];
        $rows = $this->db->query(
            "SELECT DISTINCT parent_id AS uid FROM binary_placement
              WHERE parent_id IS NOT NULL AND parent_id > 0 ORDER BY parent_id ASC"
        )->result_array();
        return array_map(function ($r) { return (int)$r['uid']; }, $rows);
    }

    /**
     * Pay every already-complete, not-yet-paid level for one sponsor, in
     * ascending order — level N is never evaluated before N-1 has been paid.
     */
    public function processSponsor($userId, $runRef, $pct = null)
    {
        if ($pct === null) {
            $s = $this->db->get_where('staking_bonus_settings', ['id' => 1])->row_array();
            $pct = ['total'   => $s ? (float)$s['matching_total_percent']   : 10.0,
                    'earning' => $s ? (float)$s['matching_earning_percent'] : 8.0,
                    'staking' => $s ? (float)$s['matching_staking_percent'] : 2.0];
        }

        $out = ['levels_paid' => 0, 'skipped' => 0, 'matched' => 0.0,
                'earning' => 0.0, 'staking' => 0.0, 'admin' => 0.0];

        $u = $this->db->select('status')->get_where('users', ['id' => (int)$userId])->row_array();
        if (!$u || (string)$u['status'] !== '1') return $out;

        $legs = $this->legVolumesByDepth((int)$userId);
        if (!$legs) return $out;

        $level = $this->nextLevel((int)$userId);
        for ($guard = 0; $guard < $this->maxLevels; $guard++, $level++) {
            if (!$this->levelComplete($legs, $level)) break;

            $vol = $this->cumulativeVolume($legs, $level);
            $paid = $this->_payLevel((int)$userId, $level, $vol, $runRef, $pct);

            if ($paid === null) { $out['skipped']++; break; } // lost the idempotency race
            $out['levels_paid']++;
            $out['matched'] += $paid['matched'];
            $out['earning'] += $paid['earning'];
            $out['staking'] += $paid['staking'];
            $out['admin']   += $paid['admin'];
        }
        return $out;
    }

    /* ------------------------- level / volume reads ------------------------ */

    /**
     * The next level to evaluate = (highest level already paid) + 1.
     *
     * COALESCE over MAX so legacy carry-engine rows (level IS NULL) are
     * ignored: a member paid by the old engine starts cleanly at level 1 under
     * the new one rather than being locked out.
     */
    public function nextLevel($userId)
    {
        $r = $this->db->query(
            "SELECT COALESCE(MAX(level), 0) + 1 AS nxt FROM staking_matching_payouts WHERE user_id = ?",
            [(int)$userId]
        )->row_array();
        return max(1, (int)($r['nxt'] ?? 1));
    }

    /**
     * Eligible Lock Wallet BMAN under one sponsor, grouped by leg and depth.
     *
     * Depth 1 = the sponsor's direct children; every deeper node inherits the
     * SIDE of the depth-1 ancestor it descends from, which is what makes "left
     * leg" mean the whole left subtree rather than just left-positioned nodes.
     *
     * Eligibility is the platform Lock Wallet rule verbatim — status
     * active|processing AND maturity_date in the future — so a matured stake
     * silently stops contributing with no extra bookkeeping. Users with no
     * eligible stake join to NULL and contribute 0 while still occupying their
     * position (they can never make a level "complete" on their own — see
     * levelComplete()).
     *
     * @return array [side => [depth => volume]]
     */
    public function legVolumesByDepth($userId)
    {
        $sql =
           "WITH RECURSIVE tree AS (
                SELECT bp.user_id, bp.position AS side, 1 AS depth
                  FROM binary_placement bp
                 WHERE bp.parent_id = ?
                UNION ALL
                SELECT c.user_id, t.side, t.depth + 1
                  FROM binary_placement c
                  JOIN tree t ON c.parent_id = t.user_id
                 WHERE t.depth < ?
            )
            SELECT t.side, t.depth, COALESCE(SUM(lw.locked), 0) AS volume
              FROM (SELECT DISTINCT user_id, side, depth FROM tree) t
              LEFT JOIN (
                    SELECT user_id, SUM(stake_amount) AS locked
                      FROM user_stakes
                     WHERE status IN ('" . implode("','", $this->lockWalletStatuses) . "')
                       AND maturity_date > CURDATE()
                     GROUP BY user_id
              ) lw ON lw.user_id = t.user_id
             GROUP BY t.side, t.depth";

        $rows = $this->db->query($sql, [(int)$userId, (int)$this->maxLevels])->result_array();

        $legs = ['left' => [], 'right' => []];
        foreach ($rows as $r) {
            $side = $r['side'] === 'right' ? 'right' : 'left';
            $legs[$side][(int)$r['depth']] = (float)$r['volume'];
        }
        return $legs;
    }

    /**
     * A level is complete when BOTH legs have real eligible volume at exactly
     * that depth.
     *
     * Deliberately volume-based, not "all 2^N positions filled": auto-placement
     * fills left-first, so a strict positional rule would permanently stall
     * most real trees at level 1 while a single right-side slot stayed empty.
     * Requiring volume (not merely a placed user) also means an unstaked node
     * cannot unlock a level on its own — matching only ever pays on money that
     * is actually locked.
     */
    public function levelComplete(array $legs, $level)
    {
        $level = (int)$level;
        if ($level < 1 || $level > $this->maxLevels) return false;
        return !empty($legs['left'][$level]) && !empty($legs['right'][$level]);
    }

    /** Cumulative eligible volume for levels 1..$level, per leg. */
    public function cumulativeVolume(array $legs, $level)
    {
        $sum = ['left' => 0.0, 'right' => 0.0];
        foreach (['left', 'right'] as $side) {
            for ($d = 1; $d <= (int)$level; $d++) {
                $sum[$side] += (float)($legs[$side][$d] ?? 0);
            }
        }
        return $sum;
    }

    /* ---------------------------- ceiling read ---------------------------- */

    /**
     * The ceiling for one sponsor: group_ceiling of their HIGHEST eligible
     * staking package — never the SUM of their packages.
     *
     * "Highest" is by stake_amount, so buying a smaller package later can
     * never lower the ceiling, and buying a second identical one can never
     * raise it. Eligibility uses the same Lock Wallet rule as the volume read,
     * so a matured package stops setting the ceiling exactly when it stops
     * contributing volume.
     *
     * @return array{ceiling: float, package_id: ?int, stake_amount: float, eligible: bool}
     */
    public function sponsorCeiling($userId)
    {
        $row = $this->db->query(
            "SELECT sp.id AS package_id, sp.group_ceiling, us.stake_amount
               FROM user_stakes us
               JOIN staking_packages sp ON sp.id = us.package_id
              WHERE us.user_id = ?
                AND us.status IN ('" . implode("','", $this->lockWalletStatuses) . "')
                AND us.maturity_date > CURDATE()
              ORDER BY us.stake_amount DESC, sp.group_ceiling DESC
              LIMIT 1",
            [(int)$userId]
        )->row_array();

        if (!$row) return ['ceiling' => 0.0, 'package_id' => null, 'stake_amount' => 0.0, 'eligible' => false];

        return [
            'ceiling'      => (float)$row['group_ceiling'],
            'package_id'   => (int)$row['package_id'],
            'stake_amount' => (float)$row['stake_amount'],
            'eligible'     => true,
        ];
    }

    /* ------------------------------ payout -------------------------------- */

    /**
     * One level, atomically. Returns null when the level was already paid
     * (idempotency collision) — the caller stops there rather than skipping
     * ahead, since levels must stay strictly ordered.
     *
     * ORDER MATTERS: the payout row goes in FIRST, before any wallet credit.
     * UNIQUE(user_id, level) then makes a duplicate physically impossible, and
     * INSERT IGNORE turns the collision into affected_rows = 0 instead of a
     * fatal DB error — so a second cron tick abandons the level having moved
     * nothing. Crediting first (what the old engine did) can only ever be
     * detected after the money has already left.
     */
    private function _payLevel($userId, $level, array $vol, $runRef, array $pct)
    {
        $matched = min($vol['left'], $vol['right']);
        if ($matched <= 0) return ['matched' => 0.0, 'earning' => 0.0, 'staking' => 0.0, 'admin' => 0.0];

        $raw  = round($matched * $pct['total'] / 100, 4);
        $ceil = $this->sponsorCeiling($userId);

        // No eligible stake -> the sponsor earns nothing at this level and the
        // whole bonus is Admin income. Never deferred, never carried.
        $user  = $ceil['eligible'] ? min($raw, (float)$ceil['ceiling']) : 0.0;
        $admin = round($raw - $user, 4);

        // Split the PAYABLE amount 8/2 (not the raw bonus) so a capped payout
        // still lands 80/20 across the two wallets. The staking share is the
        // remainder, so the two credits always re-sum to exactly $user.
        $earn = round($user * $pct['earning'] / $pct['total'], 4);
        $stk  = round($user - $earn, 4);

        $this->db->trans_begin();

        $this->db->query(
            "INSERT IGNORE INTO staking_matching_payouts
                (user_id, level, matched_volume, total_percent, raw_bonus,
                 earning_amount, staking_amount, ceiling_applied, admin_overflow,
                 highest_package_id, sponsor_eligible, left_before, right_before, run_ref, created_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())",
            [(int)$userId, (int)$level, $matched, $pct['total'], $raw,
             $earn, $stk, (float)$ceil['ceiling'], $admin,
             $ceil['package_id'], $ceil['eligible'] ? 1 : 0,
             $vol['left'], $vol['right'], $runRef]
        );
        if ($this->db->affected_rows() !== 1) {
            $this->db->trans_rollback();
            log_message('debug', '[BM_LEVEL] user ' . $userId . ' level ' . $level . ' already paid — skipped');
            return null;
        }
        $payoutId = (int)$this->db->insert_id();

        if ($earn > 0) {
            list($ok) = $this->L->credit((int)$userId, 'earning', $earn, 'binary_matching', [
                'reference_id'  => $runRef,
                'description'   => 'Binary matching L' . $level . ' — ' . $pct['earning'] . '% of ' . number_format($matched) . ' matched BV',
                'skip_maturity' => true,
            ]);
            if (!$ok) { $this->db->trans_rollback(); return null; }
        }
        if ($stk > 0) {
            list($ok) = $this->L->credit((int)$userId, 'staking', $stk, 'binary_matching', [
                'reference_id'  => $runRef,
                'description'   => 'Binary matching L' . $level . ' — ' . $pct['staking'] . '% of ' . number_format($matched) . ' matched BV',
                'skip_maturity' => true,
            ]);
            if (!$ok) { $this->db->trans_rollback(); return null; }
        }

        if ($admin > 0 && !$this->_creditAdmin($admin, $userId, $level, $raw, $user, $ceil, $payoutId)) {
            $this->db->trans_rollback();
            return null;
        }

        if ($this->db->trans_status() === false) { $this->db->trans_rollback(); return null; }
        $this->db->trans_commit();

        return ['matched' => $matched, 'earning' => $earn, 'staking' => $stk, 'admin' => $admin];
    }

    /**
     * Admin overflow -> admin_wallet balance + admin_wallet_ledger audit row,
     * inside the caller's transaction.
     *
     * Upsert, not UPDATE: admin_wallet ships with no rows, and the existing
     * "UPDATE admin_wallet ... WHERE id = 1" in Bonusreduction_model silently
     * credits nothing when the row is absent. Overflow must never evaporate
     * that way, so the row is created on first use if the migration has not
     * run. Never an on-chain send: these tokens are already in the treasury
     * and simply never leave it, so a transfer would burn gas to move nothing.
     */
    private function _creditAdmin($amount, $sponsorId, $level, $raw, $userPaid, array $ceil, $payoutId)
    {
        $this->db->query(
            "INSERT INTO admin_wallet (id, balance) VALUES (1, ?)
             ON DUPLICATE KEY UPDATE balance = balance + VALUES(balance), updated_at = NOW()",
            [$amount]
        );

        $row = $this->db->select('balance')->get_where('admin_wallet', ['id' => 1])->row_array();
        if (!$row) return false;

        $why = $ceil['eligible']
            ? 'over ceiling ' . rtrim(rtrim(number_format((float)$ceil['ceiling'], 4, '.', ''), '0'), '.')
              . ' (pkg ' . number_format((float)$ceil['stake_amount']) . ')'
            : 'sponsor holds no eligible staking package';

        return (bool)$this->db->insert('admin_wallet_ledger', [
            'credit'            => $amount,
            'debit'             => 0,
            'balance_after'     => (float)$row['balance'],
            'reference_type'    => 'binary_matching_overflow',
            'reference_user_id' => (int)$sponsorId,
            'description'       => 'Binary matching L' . $level . ' overflow — raw '
                                 . rtrim(rtrim(number_format((float)$raw, 4, '.', ''), '0'), '.')
                                 . ', member paid ' . rtrim(rtrim(number_format((float)$userPaid, 4, '.', ''), '0'), '.')
                                 . ', ' . $why . ' [payout #' . (int)$payoutId . ']',
        ]);
    }

    /* ------------------------------- reads -------------------------------- */

    /** Levels already paid for one sponsor (admin/audit reads). */
    public function paidLevels($userId)
    {
        return $this->db->where('user_id', (int)$userId)->where('level IS NOT NULL', null, false)
                        ->order_by('level', 'ASC')->get('staking_matching_payouts')->result_array();
    }
}
