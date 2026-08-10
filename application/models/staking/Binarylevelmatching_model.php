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
        // Alias deliberately NOT "MB": Matchingqueue_model::runClaimed() loads
        // *this* class under alias 'MB' before calling run(). CI_Loader::model()
        // short-circuits a repeat load of an already-used alias name (regardless
        // of target class — system/core/Loader.php ~L268), so re-using 'MB' here
        // silently no-ops and leaves $this->MB pointing at this very object,
        // which has no propagate() — that was the "Call to undefined method
        // Binarylevelmatching_model::propagate()" queue failure.
        $propagated = 0;
        if (empty($opts['skip_propagate'])) {
            $this->load->model('staking/Stakingmatching_model', 'MBLEGACY');
            $propagated = $this->MBLEGACY->propagate();
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

        $levelsPaid = 0; $users = []; $totMatched = 0.0; $totEarn = 0.0; $totStk = 0.0; $totAdmin = 0.0;
        $skipped = 0; $deferred = 0; $deferredDetail = [];
        foreach ($sponsors as $uid) {
            $res = $this->processSponsor((int)$uid, $ref, $pct);
            $levelsPaid += $res['levels_paid'];
            $skipped    += $res['skipped'];
            $deferred   += $res['deferred'];
            foreach ($res['deferred_detail'] as $d) $deferredDetail[] = $d;
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
            // Levels left OPEN because the Admin ceiling config could not be
            // resolved. Surfaced in the cron result (and therefore in Cron Lab
            // and cron_execution_log) so a misconfiguration is visible rather
            // than silently stalling a member's matching for weeks.
            'deferred_levels'  => $deferred,
            'deferred_detail'  => array_slice($deferredDetail, 0, 20),
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

        $out = ['levels_paid' => 0, 'skipped' => 0, 'deferred' => 0, 'deferred_detail' => [],
                'matched' => 0.0, 'earning' => 0.0, 'staking' => 0.0, 'admin' => 0.0];

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

            // Config error (or nothing matched): the level stays OPEN and is
            // re-tried next run. Stop here rather than moving to level N+1 —
            // levels are strictly ordered, so a pending level blocks the ones
            // beneath it until an admin resolves the configuration.
            if (!empty($paid['deferred'])) {
                $out['deferred']++;
                $out['deferred_detail'][] = 'user ' . $userId . ' L' . $level
                    . ' [' . $paid['status'] . '] ' . $paid['detail'];
                break;
            }

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
        // Aggregate the per-member rows rather than running a second CTE, so
        // the map's "contributors" list and the engine's payout volume can
        // never disagree — one query, one definition.
        $legs = ['left' => [], 'right' => []];
        foreach ($this->legMembers($userId) as $m) {
            $side = $m['side'] === 'right' ? 'right' : 'left';
            $d = (int)$m['depth'];
            if (!isset($legs[$side][$d])) $legs[$side][$d] = 0.0;
            $legs[$side][$d] += (float)$m['volume'];
        }
        return $legs;
    }

    /**
     * Every downline member with their leg, depth and eligible Lock Wallet
     * volume — the row-level source behind legVolumesByDepth().
     *
     * Read-only; exists so admin screens can show WHO contributed to a leg
     * without re-deriving the tree walk or the eligibility rule themselves.
     *
     * @return array<int, array{user_id:int, side:string, depth:int, volume:float}>
     */
    public function legMembers($userId)
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
            SELECT t.user_id, t.side, t.depth, COALESCE(lw.locked, 0) AS volume
              FROM (SELECT DISTINCT user_id, side, depth FROM tree) t
              LEFT JOIN (
                    SELECT user_id, SUM(stake_amount) AS locked
                      FROM user_stakes
                     WHERE status IN ('" . implode("','", $this->lockWalletStatuses) . "')
                       AND maturity_date > CURDATE()
                     GROUP BY user_id
              ) lw ON lw.user_id = t.user_id
             ORDER BY t.depth ASC, t.user_id ASC";

        $rows = $this->db->query($sql, [(int)$userId, (int)$this->maxLevels])->result_array();

        return array_map(function ($r) {
            return [
                'user_id' => (int)$r['user_id'],
                'side'    => $r['side'] === 'right' ? 'right' : 'left',
                'depth'   => (int)$r['depth'],
                'volume'  => (float)$r['volume'],
            ];
        }, $rows);
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
     * The ceiling for one sponsor, read LIVE from the Admin Group Incentive
     * Ceiling configuration on every single call.
     *
     * ARCHITECTURE — there is exactly ONE ceiling configuration in this system
     * and this method consumes it, never a copy:
     *
     *   Admin ▸ Staking ▸ Rank Power   (Group Incentive Ceiling editor, §12)
     *   Admin ▸ Staking ▸ Packages     ("Group ceiling" field on each package)
     *          both write ──►  staking_packages.group_ceiling
     *                                    │
     *                                    ▼
     *                          THIS QUERY, every time
     *
     * No ceiling amount is hard-coded, defaulted, CASE'd or cached anywhere in
     * this engine. An admin editing a ceiling changes matching behaviour on the
     * very next run with no code change and no restart.
     *
     * TWO SEPARATE CONCEPTS, deliberately never conflated:
     *   stake_amount   — identifies WHICH ceiling configuration to look up.
     *   group_ceiling  — the actual payout cap. It is NOT derived from, and
     *                    need not equal, the stake amount (a 50,000 package
     *                    can carry any ceiling the admin configures).
     *
     * "Highest" is by stake_amount, so a later smaller purchase can never lower
     * the ceiling and a second identical one can never raise it. Ceilings are
     * never summed. Eligibility uses the same Lock Wallet rule as the volume
     * read, so a matured package stops setting the ceiling exactly when it
     * stops contributing volume.
     *
     * FAILS CLOSED, never guesses. status is one of:
     *   'ok'                — one unambiguous, positive configured ceiling.
     *   'no_stake'          — sponsor holds no eligible package.
     *   'config_missing'    — the package's ceiling is NULL or <= 0.
     *   'config_ambiguous'  — the sponsor holds several eligible packages at
     *                         the SAME highest stake amount whose configured
     *                         ceilings disagree, so "the ceiling for this
     *                         package" has more than one answer. Picking one
     *                         (the old `ORDER BY group_ceiling DESC LIMIT 1`)
     *                         would be a silent guess in the member's or the
     *                         admin's favour depending on sort order.
     * The caller must not pay a sponsor on anything but 'ok'.
     *
     * @return array{ceiling:float, package_id:?int, stake_amount:float,
     *               eligible:bool, status:string, detail:string}
     */
    public function sponsorCeiling($userId)
    {
        $statuses = "'" . implode("','", $this->lockWalletStatuses) . "'";

        // Every eligible stake tied for the sponsor's HIGHEST stake amount,
        // with the ceiling each one's package currently carries.
        $rows = $this->db->query(
            "SELECT sp.id AS package_id, sp.group_ceiling, us.stake_amount
               FROM user_stakes us
               JOIN staking_packages sp ON sp.id = us.package_id
              WHERE us.user_id = ?
                AND us.status IN ({$statuses})
                AND us.maturity_date > CURDATE()
                AND us.stake_amount = (
                      SELECT MAX(us2.stake_amount) FROM user_stakes us2
                       WHERE us2.user_id = us.user_id
                         AND us2.status IN ({$statuses})
                         AND us2.maturity_date > CURDATE())
              ORDER BY sp.id ASC",
            [(int)$userId]
        )->result_array();

        $none = ['ceiling' => 0.0, 'package_id' => null, 'stake_amount' => 0.0, 'eligible' => false];

        if (!$rows) return $none + ['status' => 'no_stake', 'detail' => 'no eligible staking package'];

        $stake = (float)$rows[0]['stake_amount'];
        $first = (int)$rows[0]['package_id'];

        $distinct = []; $invalid = [];
        foreach ($rows as $r) {
            $c = $r['group_ceiling'];
            if ($c === null || (float)$c <= 0) { $invalid[] = (int)$r['package_id']; continue; }
            $distinct[number_format((float)$c, 4, '.', '')] = (float)$c;
        }

        if ($invalid) {
            return ['ceiling' => 0.0, 'package_id' => $first, 'stake_amount' => $stake, 'eligible' => false,
                    'status' => 'config_missing',
                    'detail' => 'no Group Incentive Ceiling configured for package(s) '
                                . implode(',', $invalid) . ' (stake ' . $stake . ')'];
        }
        if (count($distinct) > 1) {
            $ids = array_map(function ($r) { return (int)$r['package_id']; }, $rows);
            return ['ceiling' => 0.0, 'package_id' => $first, 'stake_amount' => $stake, 'eligible' => false,
                    'status' => 'config_ambiguous',
                    'detail' => 'packages ' . implode(',', $ids) . ' all at stake ' . $stake
                                . ' carry different ceilings (' . implode('/', array_values($distinct)) . ')'];
        }

        return [
            'ceiling'      => (float)reset($distinct),
            'package_id'   => $first,
            'stake_amount' => $stake,
            'eligible'     => true,
            'status'       => 'ok',
            'detail'       => '',
        ];
    }

    /* ---------------------------- projection ------------------------------ */

    /**
     * What WOULD this level pay, given the volumes handed in — pure, read-only,
     * writes nothing.
     *
     * This is the single definition of the money split: _payLevel() calls it
     * and then persists the result, and admin visualisations call it to show a
     * projection. Extracted precisely so the formula exists in ONE place — a
     * copy in a controller or (worse) in JavaScript would drift the moment a
     * rule changed. Callers that only want to LOOK must never call _payLevel().
     *
     * Percentages come from staking_bonus_settings and the ceiling from
     * sponsorCeiling(), so nothing here is hard-coded.
     *
     * @return array{matched,raw,ceiling,user,admin,earning,staking,payable,ceil}
     */
    public function projectLevel($userId, array $vol, array $pct = null)
    {
        if ($pct === null) {
            $s = $this->db->get_where('staking_bonus_settings', ['id' => 1])->row_array();
            $pct = ['total'   => $s ? (float)$s['matching_total_percent']   : 10.0,
                    'earning' => $s ? (float)$s['matching_earning_percent'] : 8.0,
                    'staking' => $s ? (float)$s['matching_staking_percent'] : 2.0];
        }

        $matched = min((float)$vol['left'], (float)$vol['right']);
        $ceil    = $this->sponsorCeiling($userId);
        $raw     = $matched > 0 && $pct['total'] > 0 ? round($matched * $pct['total'] / 100, 4) : 0.0;

        // Payable only when the ceiling config resolves to exactly one positive
        // value. 'no_stake' pays the sponsor nothing (whole bonus is Admin's);
        // a config error pays NOBODY and leaves the level open.
        $payable = ($ceil['status'] === 'ok');
        $configError = (!$ceil['eligible'] && $ceil['status'] !== 'no_stake');

        $user  = $payable ? min($raw, (float)$ceil['ceiling']) : 0.0;
        $admin = $configError ? 0.0 : round($raw - $user, 4);

        $earn = round($user * $pct['earning'] / $pct['total'], 4);
        $stk  = round($user - $earn, 4);

        return [
            'matched'      => $matched,
            'raw'          => $raw,
            'ceiling'      => (float)$ceil['ceiling'],
            'user'         => $user,
            'admin'        => $admin,
            'earning'      => $earn,
            'staking'      => $stk,
            'payable'      => $payable,
            'config_error' => $configError,
            'ceil'         => $ceil,
            'pct'          => $pct,
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
        if ($matched <= 0) {
            // Unreachable while levelComplete() requires volume on both legs,
            // but if it ever happens, record nothing and leave the level open
            // rather than closing it with a zero payout.
            return ['deferred' => true, 'status' => 'zero_matched', 'detail' => 'no matched volume'];
        }

        // One definition of the split, shared with admin projections.
        $calc = $this->projectLevel($userId, $vol, $pct);
        $raw  = $calc['raw'];
        $ceil = $calc['ceil'];

        // ---------------------------------------------------------------
        // CONFIG ERROR -> SKIP & RETRY. A missing or ambiguous Group
        // Incentive Ceiling is an ADMIN/system fault, never the member's, so
        // it must not cost them the level. Nothing at all happens here: no
        // payout row (so UNIQUE(user_id, level) stays free and the level is
        // still "next" on the following run), no wallet credit, no admin
        // overflow, no volume touched — and no transaction is even opened.
        // Once the admin fixes the ceiling, the very next cron re-evaluates
        // THIS SAME level and pays it correctly.
        //
        // Contrast with 'no_stake', which is a real business outcome: the
        // sponsor genuinely holds no eligible package, so the bonus IS
        // forfeited to Admin and the level IS closed.
        //
        // Returning before trans_begin() is deliberate — an open transaction
        // that only ever rolls back is a needless lock on live money tables.
        // ---------------------------------------------------------------
        if (!$ceil['eligible'] && $ceil['status'] !== 'no_stake') {
            log_message('error', sprintf(
                '[BM_LEVEL] CEILING CONFIG ERROR — level NOT closed, will retry. '
                . 'sponsor_id=%d level=%d status=%s highest_stake=%s package_id=%s detail=%s '
                . 'unpaid_raw_bonus=%s matched_volume=%s',
                $userId, $level, $ceil['status'], $ceil['stake_amount'],
                $ceil['package_id'] === null ? 'n/a' : $ceil['package_id'],
                $ceil['detail'], $raw, $matched
            ));
            return ['deferred' => true, 'status' => $ceil['status'], 'detail' => $ceil['detail']];
        }

        // The PAYABLE amount is split 8/2 (not the raw bonus) so a capped
        // payout still lands 80/20 across the two wallets, and the staking
        // share is the remainder so the two credits re-sum to exactly $user.
        $user  = $calc['user'];
        $admin = $calc['admin'];
        $earn  = $calc['earning'];
        $stk   = $calc['staking'];

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

        // Per-LEVEL wallet reference, not just the run ref: one run pays many
        // sponsors across many levels, so a bare run_ref cannot identify which
        // ledger rows belong to which level. Admin history links the exact
        // credits for a level off this. Nothing matches wallet_ledger
        // .reference_id by value for binary_matching (verified), so narrowing
        // the format breaks no existing reader.
        $walletRef = $runRef . '-L' . $level;

        if ($earn > 0) {
            list($ok) = $this->L->credit((int)$userId, 'earning', $earn, 'binary_matching', [
                'reference_id'  => $walletRef,
                'description'   => 'Binary matching L' . $level . ' — ' . $pct['earning'] . '% of ' . number_format($matched) . ' matched BV',
                'skip_maturity' => true,
            ]);
            if (!$ok) { $this->db->trans_rollback(); return null; }
        }
        if ($stk > 0) {
            list($ok) = $this->L->credit((int)$userId, 'staking', $stk, 'binary_matching', [
                'reference_id'  => $walletRef,
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
            : ($ceil['status'] === 'no_stake'
                ? 'sponsor holds no eligible staking package'
                : 'CEILING CONFIG ERROR (' . $ceil['status'] . '): ' . $ceil['detail']);

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
