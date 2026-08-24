<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Dashboardchart_model — live data for the member dashboard's
 * "User Activity & Coin Trend" chart.
 * ----------------------------------------------------------------------------
 * Five series, all LIVE from the database (the chart previously read a static
 * assets/user_v2/data/dashboard_chart.json that described itself as "Dummy
 * dashboard finance data"):
 *
 *   active_users     TEAM-SCOPED. COUNT(DISTINCT user_id) with a wallet
 *                    movement in the bucket, across the downline. "How many
 *                    of my team are active" only means something across a
 *                    team — a personal count is always 0 or 1.
 *   bonus_used       PERSONAL. Despite the field name (kept because
 *                    platformTrend()/admin still uses the original meaning —
 *                    see _applyLedger()'s docblock), the member dashboard
 *                    shows this as the session user's actual running BONUS
 *                    WALLET BALANCE, not debit activity. A member who has
 *                    received bonus but never spent any of it has a real,
 *                    nonzero balance — showing 0 there (the original
 *                    "debits so far" meaning) reads as "you have nothing"
 *                    when they clearly do.
 *   staking_done     PERSONAL. Same story as bonus_used: the member
 *                    dashboard shows the session user's running STAKING
 *                    WALLET BALANCE, not a count of completed
 *                    staking_swap_orders purchases. A member's staking
 *                    wallet can be funded by a binary-matching bonus split
 *                    with zero actual purchases behind it — the purchase
 *                    count reads as "you have nothing staked" when there is
 *                    real BMAN sitting in that wallet.
 *   earning_coin     PERSONAL. The session user's own earning wallet CREDITS
 *                    from line / matching / rank bonuses.
 *   coin_withdrawal  PERSONAL. The session user's own completed BMAN
 *                    withdrawals.
 *
 * SCOPE — active_users (and left_investment/right_investment, the chart's two
 * trend-line series) are TEAM-scoped: the member's own binary downline, not
 * the whole platform and not just themselves — "how many of my team are
 * active" / left-vs-right leg investment only mean something across a team.
 * Change TEAM_SCOPE to false to make active_users platform-wide instead.
 *
 * Every OTHER tile (bonus_used, staking_done, earning_coin, coin_withdrawal)
 * is PERSONAL, always — regardless of TEAM_SCOPE or downline size. They sit
 * directly beside the member's own wallet balance cards on the dashboard, so
 * a team total there reads as the member's own money when it wasn't
 * (originally reported: 8,000 BMAN of TEAM earning-bonus credits shown next
 * to a 12,000 BMAN PERSONAL earning wallet balance — two different people's
 * money, mistaken for one figure). _applyPersonal() computes all four from
 * the member's own user_id and always runs, even when the team-wide block in
 * trend() is skipped for an empty downline.
 *
 * bonus_used/staking_done went a step further after that same fix (personal
 * scope alone wasn't enough): once scoped to the member, both were still
 * reading 0 for a real account holding 25,000 BMAN bonus / 3,000 BMAN
 * staking — correctly, per their ORIGINAL definitions (debit sum / purchase
 * count), just not what a member reading "Bonus Used" next to their own
 * Bonus Wallet card would expect. Both are now the member's running WALLET
 * BALANCE instead — see _walletBalanceSeries(). A balance is a point-in-time
 * snapshot, not a per-bucket flow, so unlike every other series here it
 * can't be summed within a bucket or across buckets (see trend()'s summary
 * override and _walletBalanceSeries()'s docblock).
 *
 * SPEED
 *  - FIVE queries per range: one team-scoped wallet_ledger pass (for
 *    active_users only) + one team-scoped recursive CTE (leg investments),
 *    plus a personal-scoped pass reusing the exact same three aggregation
 *    helpers with $ids forced to the member's own id — so the personal
 *    figures can never drift from the team/platform figures' business rules
 *    (the bonus_reduction exclusion, the swap_completed status pair, etc).
 *  - The downline is resolved through Rankcalculator_model, which loads the
 *    genealogy ONCE and walks it in memory (3 queries for any team size)
 *    instead of one query per node.
 *  - Results are cached per (user, range) for CACHE_TTL seconds. The dashboard
 *    is the most-hit page in the panel and this data does not need to be
 *    second-accurate.
 *  - Requires db/2026-07-17_dashboard_chart_indexes.sql — without it every
 *    bucket query full-scans wallet_ledger.
 *
 * Buckets are always dense: gaps are filled with zeros in PHP so the chart shows
 * a real flat line rather than silently collapsing the x-axis.
 */
class Dashboardchart_model extends CI_Model
{
    /** Scope the figures to the member's downline. false = whole platform. */
    const TEAM_SCOPE = true;

    /** Seconds to cache a (user, range) series. */
    const CACHE_TTL = 300;

    /** Guard: an enormous IN() list is worse than no filter at all. */
    const MAX_TEAM_IDS = 5000;

    /**
     * Earning-wallet credits that count as "earning coin" — the line / matching
     * bonus the member received.
     *
     * 'binary_matching' is what the live data actually contains (it is what
     * Stakingmatching_model writes); 'binary_commission' and 'matching_bonus'
     * appear elsewhere in the codebase for the same idea. All are accepted so
     * the series does not silently read zero depending on which writer ran.
     * 'roi' and 'stake_purchase' also credit the earning wallet but are NOT a
     * line/matching bonus, so they stay out.
     */
    private $earning_refs = [
        'binary_matching',      // live data — Stakingmatching_model
        'binary_commission',    // legacy / commission engine
        'matching_bonus',       // alternate spelling in some writers
        'rank_reward',
    ];

    /**
     * Swap-order statuses that mean "the stake actually completed".
     *
     * The engine writes 'swap_completed' — NOT 'completed'. Verified against
     * both the writers (Swapengine_model / StakingSwap_model only ever set
     * active | failed_credit | pending_gas_fee | pending_usdt | pending_bman |
     * swap_completed) and the live table, which contains only 'swap_completed'
     * and 'cancelled'. The comment in db/staking_swap.sql:47 promising
     * "created → usdt_sent → bman_sent → completed" is stale and does not match
     * the code. Both spellings are accepted so this keeps working if the engine
     * is ever aligned to the documented state machine.
     */
    private $done_status = ['swap_completed', 'completed'];

    private $ranges = [
        'daily'   => ['points' => 30, 'label' => 'Last 30 Days'],
        'monthly' => ['points' => 12, 'label' => 'Last 12 Months'],
        'yearly'  => ['points' => 5,  'label' => 'Last 5 Years'],
    ];

    public function __construct()
    {
        parent::__construct();
        $this->load->driver('cache', ['adapter' => 'file']);
    }

    /* ============================== entry ============================== */

    /**
     * One range of the trend, ready for Chart.js.
     *
     * @param int    $userId
     * @param string $range  daily | monthly | yearly
     * @return array{label:string, range:string, points:array, summary:array, cached:bool}
     */
    public function trend($userId, $range = 'monthly')
    {
        $userId = (int) $userId;
        if (!isset($this->ranges[$range])) $range = 'monthly';

        $key = 'dash_trend_' . $userId . '_' . $range . '_' . (self::TEAM_SCOPE ? 't' : 'p');
        $hit = $this->cache->get($key);
        if (is_array($hit)) {
            $hit['cached'] = true;
            return $hit;
        }

        $buckets = $this->_buckets($range);              // dense skeleton, zeros
        $ids     = self::TEAM_SCOPE ? $this->_teamIds($userId) : null;
        $from    = $this->_since($range);

        // Team-wide series: active_users and left/right leg investment — the
        // only two tiles that still mean "the team" (a single member has no
        // "leg" of their own). TEAM_SCOPE with an empty downline means
        // genuinely nothing to show for these — leave them zeroed rather than
        // silently falling back to platform-wide numbers, which would be a lie.
        if (!self::TEAM_SCOPE || $ids === null || !empty($ids)) {
            $this->_applyLedger($buckets, $range, $from, $ids);
            $this->_applyLegInvestments($buckets, $userId, $range, $from, $ids);
        }

        // Personal series: bonus_used, staking_done, earning_coin and
        // coin_withdrawal all read as "my own" activity/money next to the
        // member's own wallet cards, so they're scoped to the SESSION USER
        // alone, not the team — see class docblock SCOPE note. Always runs,
        // regardless of downline size.
        $this->_applyPersonal($buckets, $range, $from, $userId);

        $out = $this->_shape($range, $buckets);

        // bonus_used/staking_done are wallet BALANCES on this member-facing
        // path (see class docblock SCOPE note + _walletBalanceSeries()) —
        // _shape()'s generic summary logic just summed every bucket, which is
        // right for a flow (earning_coin, coin_withdrawal) but wrong for a
        // balance: it would add the same ~steady balance up to 12x over. The
        // current balance is simply the most recent bucket's value.
        $latest = end($buckets);
        reset($buckets);
        $out['summary']['bonus_used']   = $latest['bonus_used'];
        $out['summary']['staking_done'] = $latest['staking_done'];

        $this->cache->save($key, $out, self::CACHE_TTL);
        $out['cached'] = false;
        return $out;
    }

    /** Drop a member's cached series (call after anything that moves money). */
    public function bust($userId)
    {
        foreach (array_keys($this->ranges) as $r) {
            foreach (['t', 'p'] as $scope) {
                $this->cache->delete('dash_trend_' . (int) $userId . '_' . $r . '_' . $scope);
            }
        }
    }

    /**
     * Platform-wide version of trend() — same five series, summed across
     * EVERY user rather than one member's downline. Powers the admin
     * dashboard's "User Activity & Coin Trend" section.
     *
     * Reuses the exact same bucket/aggregation helpers as trend() with $ids
     * forced to null (no user filter), bypassing TEAM_SCOPE entirely rather
     * than repurposing it — flipping that const would also change the
     * member-facing chart's scope, which must stay team-only.
     */
    public function platformTrend($range = 'monthly')
    {
        if (!isset($this->ranges[$range])) $range = 'monthly';

        $key = 'dash_trend_platform_' . $range;
        $hit = $this->cache->get($key);
        if (is_array($hit)) {
            $hit['cached'] = true;
            return $hit;
        }

        $buckets = $this->_buckets($range);
        $from = $this->_since($range);
        $this->_applyLedger($buckets, $range, $from, null);
        $this->_applyStaking($buckets, $range, $from, null);
        $this->_applyWithdrawals($buckets, $range, $from, null);

        $out = $this->_shape($range, $buckets);
        $out['scope'] = 'platform';
        $this->cache->save($key, $out, self::CACHE_TTL);
        $out['cached'] = false;
        return $out;
    }

    /* ============================ team scope =========================== */

    /**
     * The member's downline ids. Reuses Rankcalculator_model, which primes the
     * whole genealogy in one query and BFSes in memory — the naive approach is
     * one query per node.
     *
     * Returns [] for a member with no downline. Never includes the member.
     */
    private function _teamIds($userId)
    {
        $this->load->model('staking/Rankcalculator_model', 'dc_calc');
        $ids = $this->dc_calc->descendants($userId);

        if (count($ids) > self::MAX_TEAM_IDS) {
            // A very large IN() list stops helping. Log it rather than quietly
            // truncating and reporting numbers that are simply wrong.
            log_message('error', '[dashboard_chart] user ' . $userId . ' downline is '
                . count($ids) . ' (> ' . self::MAX_TEAM_IDS . '); series truncated.');
            $ids = array_slice($ids, 0, self::MAX_TEAM_IDS);
        }
        return array_map('intval', $ids);
    }

    /* ============================= buckets ============================= */

    /** SQL expression that maps a date column onto this range's bucket key. */
    private function _bucketSql($range, $col)
    {
        switch ($range) {
            case 'daily':  return "DATE(`{$col}`)";
            case 'yearly': return "DATE_FORMAT(`{$col}`, '%Y')";
            default:       return "DATE_FORMAT(`{$col}`, '%Y-%m')";
        }
    }

    /** Oldest timestamp this range needs. */
    private function _since($range)
    {
        switch ($range) {
            case 'daily':  return date('Y-m-d 00:00:00', strtotime('-29 days'));
            case 'yearly': return date('Y-01-01 00:00:00', strtotime('-4 years'));
            default:       return date('Y-m-01 00:00:00', strtotime('-11 months'));
        }
    }

    /**
     * Dense, zeroed skeleton keyed by bucket — so a quiet day plots as 0 rather
     * than vanishing and distorting the x-axis.
     */
    private function _buckets($range)
    {
        $n = $this->ranges[$range]['points'];
        $out = [];
        for ($i = $n - 1; $i >= 0; $i--) {
            if ($range === 'daily') {
                $ts    = strtotime("-{$i} days");
                $key   = date('Y-m-d', $ts);
                $label = date('M d', $ts);
            } elseif ($range === 'yearly') {
                $ts    = strtotime("-{$i} years");
                $key   = date('Y', $ts);
                $label = date('Y', $ts);
            } else {
                $ts    = strtotime("first day of -{$i} months");
                $key   = date('Y-m', $ts);
                $label = date('M y', $ts);
            }
            $out[$key] = [
                'date' => $label,
                'active_users' => 0, 'bonus_used' => 0.0, 'staking_done' => 0.0,
                'earning_coin' => 0.0, 'coin_withdrawal' => 0.0,
                'left_investment' => 0.0, 'right_investment' => 0.0,
            ];
        }
        return $out;
    }

    /* =========================== aggregations ========================== */

    /**
     * wallet_ledger — three columns in ONE pass: active_users, bonus_used,
     * earning_coin. Shared by three different callers, each keeping a
     * different subset of the result:
     *   - platformTrend() (admin, $ids=null/platform-wide): all three columns
     *     are final — there's no "personal" concept on the platform-wide chart.
     *   - trend()'s team-wide block (member, $ids=downline): only active_users
     *     is kept. bonus_used/earning_coin are thrown away.
     *   - _applyPersonal() (member, $ids=[the member's own id]): only
     *     earning_coin is kept. bonus_used is NOT used from here — the member
     *     dashboard shows bonus_used as a wallet BALANCE now
     *     (_walletBalanceSeries()), not the debit-sum this query computes;
     *     see class docblock SCOPE note. active_users from this call is also
     *     unused (trend()'s team-wide block already set the real one).
     *
     * active_users : anyone with a wallet movement in the bucket. There is no
     *                honest login signal in this schema (users has no last_login
     *                and user_activity_logs has zero writers), so activity is
     *                derived from transactional evidence, which also means the
     *                chart has real history from day one.
     * bonus_used   : bonus-wallet DEBITS, excluding reference_type
     *                'bonus_reduction' — that is the platform's 60-day clawback,
     *                not the member spending their bonus. Including it would
     *                spike the line on reduction runs. (Only meaningful to
     *                platformTrend() now — see above.)
     * earning_coin : earning-wallet CREDITS from line/matching/rank bonuses.
     */
    private function _applyLedger(&$buckets, $range, $from, $ids)
    {
        $b = $this->_bucketSql($range, 'created_at');
        $refs = $this->_inList($this->earning_refs);

        $sql = "SELECT {$b} AS bkt,
                       COUNT(DISTINCT `user_id`) AS active_users,
                       SUM(CASE WHEN `wallet_type` = 'bonus'
                                 AND `reference_type` <> 'bonus_reduction'
                                THEN `debit` ELSE 0 END) AS bonus_used,
                       SUM(CASE WHEN `wallet_type` = 'earning'
                                 AND `reference_type` IN {$refs}
                                THEN `credit` ELSE 0 END) AS earning_coin
                  FROM `wallet_ledger`
                 WHERE `created_at` >= ?";
        $bind = [$from];
        if ($ids !== null) {
            $sql .= ' AND `user_id` IN ' . $this->_intList($ids);
        }
        $sql .= " GROUP BY bkt";

        foreach ($this->db->query($sql, $bind)->result_array() as $r) {
            $k = (string) $r['bkt'];
            if (!isset($buckets[$k])) continue;   // outside the window
            $buckets[$k]['active_users'] = (int) $r['active_users'];
            $buckets[$k]['bonus_used']   = round((float) $r['bonus_used'], 4);
            $buckets[$k]['earning_coin'] = round((float) $r['earning_coin'], 4);
        }
    }

    /**
     * bonus_used, staking_done, earning_coin, coin_withdrawal — all scoped to
     * the SESSION USER alone, not the team (active_users and left/right leg
     * investment are the only series that stay team-scoped; see the class
     * docblock SCOPE note for why).
     *
     * Two different kinds of "personal" combine here:
     *  - earning_coin / coin_withdrawal are FLOW: reuses _applyLedger()'s
     *    earning_coin column and _applyWithdrawals(), both called with $ids
     *    forced to the single-element [$userId] (a one-element IN-list is
     *    equivalent to an equality filter) rather than hand-duplicating their
     *    SQL — so these can never drift from the team/platform figures'
     *    business rules (the swap_completed status pair, the net_amount
     *    column, etc).
     *  - bonus_used / staking_done are BALANCE: see _walletBalanceSeries().
     *    _applyLedger()'s own bonus_used column is deliberately NOT used
     *    here (that's debit-sum, the old meaning — still correct for
     *    platformTrend()/admin, just not what this method needs).
     *
     * Explicitly zeroes all four personal fields on the real buckets first:
     * without that, a bucket where the team had activity but this member
     * personally didn't would keep whatever team figure trend()'s team-wide
     * block wrote instead of correctly showing 0.
     */
    private function _applyPersonal(&$buckets, $range, $from, $userId)
    {
        foreach ($buckets as $k => &$row) {
            $row['bonus_used']      = 0.0;
            $row['staking_done']    = 0.0;
            $row['earning_coin']    = 0.0;
            $row['coin_withdrawal'] = 0.0;
        }
        unset($row);

        $own     = [(int) $userId];
        $scratch = $this->_buckets($range);
        $this->_applyLedger($scratch, $range, $from, $own);
        $this->_applyWithdrawals($scratch, $range, $from, $own);

        $bonusBalance   = $this->_walletBalanceSeries($range, $from, $userId, 'bonus');
        $stakingBalance = $this->_walletBalanceSeries($range, $from, $userId, 'staking');

        foreach ($buckets as $k => &$row) {
            if (isset($scratch[$k])) {
                $row['earning_coin']    = $scratch[$k]['earning_coin'];
                $row['coin_withdrawal'] = $scratch[$k]['coin_withdrawal'];
            }
            $row['bonus_used']   = $bonusBalance[$k]['balance']   ?? 0.0;
            $row['staking_done'] = $stakingBalance[$k]['balance'] ?? 0.0;
        }
        unset($row);
    }

    /**
     * Running wallet_ledger balance per bucket, scoped to one user + one
     * wallet_type. Unlike every other series in this class, a wallet BALANCE
     * isn't something that happened "within" a bucket — it's a point-in-time
     * snapshot that carries forward, so bucket N's value must include every
     * credit/debit up to and including that bucket, not just what landed
     * inside it.
     *
     * Two queries, not one per bucket:
     *  1. The starting balance immediately before the window (everything
     *     with created_at < $from) — a member with wallet history older than
     *     the visible range still needs an accurate starting point, not 0.
     *  2. The NET movement (credit - debit) inside each bucket, then walked
     *     forward in chronological order as a running total. _buckets()
     *     builds its skeleton oldest-first, so plain array iteration order
     *     is already correct — no separate sort needed.
     */
    private function _walletBalanceSeries($range, $from, $userId, $walletType)
    {
        $userId = (int) $userId;

        $startRow = $this->db
            ->query(
                "SELECT SUM(`credit` - `debit`) AS bal FROM `wallet_ledger`
                  WHERE `user_id` = ? AND `wallet_type` = ? AND `created_at` < ?",
                [$userId, $walletType, $from]
            )
            ->row_array();
        $running = (float) ($startRow['bal'] ?? 0);

        $b = $this->_bucketSql($range, 'created_at');
        $sql = "SELECT {$b} AS bkt, SUM(`credit` - `debit`) AS net
                  FROM `wallet_ledger`
                 WHERE `user_id` = ? AND `wallet_type` = ? AND `created_at` >= ?
                 GROUP BY bkt";
        $netByBucket = [];
        foreach ($this->db->query($sql, [$userId, $walletType, $from])->result_array() as $r) {
            $netByBucket[(string) $r['bkt']] = (float) $r['net'];
        }

        $out = $this->_buckets($range);
        foreach ($out as $k => &$row) {
            $running += $netByBucket[$k] ?? 0.0;
            $row['balance'] = round($running, 4);
        }
        unset($row);
        return $out;
    }

    /**
     * staking_swap_orders — "how many user staking done".
     * The status pair is what excludes cancelled / failed / pending / refunded.
     * See $done_status: the engine writes 'swap_completed', not 'completed'.
     *
     * Only caller left is platformTrend() (admin, $ids=null, platform-wide
     * purchase count). The member-facing trend() used to reuse this via
     * _applyPersonal() too, but staking_done there is now a wallet BALANCE
     * (_walletBalanceSeries()), not a purchase count — a member's staking
     * wallet can hold real BMAN from a binary-matching bonus split with zero
     * actual completed purchases behind it, which this query would (rightly,
     * for what IT measures) report as 0. See class docblock SCOPE note.
     */
    private function _applyStaking(&$buckets, $range, $from, $ids)
    {
        $b = $this->_bucketSql($range, 'created_at');
        $done = $this->_inList($this->done_status);
        $sql = "SELECT {$b} AS bkt, COUNT(*) AS staking_done
                  FROM `staking_swap_orders`
                 WHERE `status` IN {$done} AND `cron_status` = 'completed'
                   AND `created_at` >= ?";
        $bind = [$from];
        if ($ids !== null) {
            $sql .= ' AND `user_id` IN ' . $this->_intList($ids);
        }
        $sql .= " GROUP BY bkt";

        foreach ($this->db->query($sql, $bind)->result_array() as $r) {
            $k = (string) $r['bkt'];
            if (isset($buckets[$k])) $buckets[$k]['staking_done'] = (int) $r['staking_done'];
        }
    }

    /**
     * bman_withdraw_requests — coin actually out of the platform.
     * Buckets on completed_at (when the coin left), not created_at (when it was
     * asked for), and counts net_amount — what the member actually received.
     *
     * Called two ways, never team-scoped: platformTrend() (admin) with
     * $ids=null for a platform-wide sum, and _applyPersonal() (member) with
     * $ids=[the member's own id] for their personal sum.
     */
    private function _applyWithdrawals(&$buckets, $range, $from, $ids)
    {
        if (!$this->db->table_exists('bman_withdraw_requests')) return;

        $b = $this->_bucketSql($range, 'completed_at');
        $sql = "SELECT {$b} AS bkt, SUM(`net_amount`) AS coin_withdrawal
                  FROM `bman_withdraw_requests`
                 WHERE `status` = 'completed' AND `completed_at` IS NOT NULL
                   AND `completed_at` >= ?";
        $bind = [$from];
        if ($ids !== null) {
            $sql .= ' AND `user_id` IN ' . $this->_intList($ids);
        }
        $sql .= " GROUP BY bkt";

        foreach ($this->db->query($sql, $bind)->result_array() as $r) {
            $k = (string) $r['bkt'];
            if (isset($buckets[$k])) $buckets[$k]['coin_withdrawal'] = round((float) $r['coin_withdrawal'], 4);
        }
    }

    /**
     * Left/Right leg locked wallet investment totals per bucket.
     * Calculates active, unmatured staking (locked wallet) sums per leg
     * for the team downline over time.
     */
    private function _applyLegInvestments(&$buckets, $userId, $range, $from, $ids)
    {
        if (!$ids) return; // Only for team scope
        if (empty($ids)) return;

        // Recursive CTE to walk the binary tree and accumulate locked wallet per leg
        // Construct DATE_FORMAT manually since _bucketSql doesn't handle qualified column names
        switch ($range) {
            case 'daily':  $bkt_format = "DATE(us.created_at)"; break;
            case 'yearly': $bkt_format = "DATE_FORMAT(us.created_at, '%Y')"; break;
            default:       $bkt_format = "DATE_FORMAT(us.created_at, '%Y-%m')";
        }

        $sql = "
            WITH RECURSIVE downline AS (
                SELECT bp.user_id, bp.parent_id, bp.position AS root_leg
                FROM binary_placement bp
                WHERE bp.parent_id = ?

                UNION ALL

                SELECT c.user_id, c.parent_id, d.root_leg
                FROM binary_placement c
                JOIN downline d ON d.user_id = c.parent_id
            )
            SELECT {$bkt_format} AS bkt, d.root_leg,
                   SUM(us.stake_amount) AS leg_total
            FROM downline d
            JOIN user_stakes us ON us.user_id = d.user_id
                AND us.status IN ('active', 'processing')
                AND us.maturity_date > NOW()
            WHERE us.created_at >= ?
            GROUP BY bkt, d.root_leg
        ";
        $bind = [$userId, $from];

        foreach ($this->db->query($sql, $bind)->result_array() as $r) {
            $k = (string) $r['bkt'];
            if (!isset($buckets[$k])) continue;

            $leg = $r['root_leg'];
            $val = round((float) $r['leg_total'], 4);

            if ($leg === 'left') {
                $buckets[$k]['left_investment'] = $val;
            } elseif ($leg === 'right') {
                $buckets[$k]['right_investment'] = $val;
            }
        }
    }

    /* ============================= helpers ============================= */

    /** Quoted IN list for a fixed, code-owned set of strings. */
    private function _inList(array $vals)
    {
        $out = [];
        foreach ($vals as $v) $out[] = $this->db->escape($v);
        return '(' . implode(',', $out) . ')';
    }

    /** IN list of ints. Cast, so it cannot carry anything but integers. */
    private function _intList(array $ids)
    {
        $ints = array_map('intval', $ids);
        return '(' . implode(',', $ints) . ')';
    }

    /** Final Chart.js-ready payload + headline totals for the stat tiles. */
    private function _shape($range, $buckets)
    {
        $points = array_values($buckets);
        $sum = ['active_users' => 0, 'bonus_used' => 0.0, 'staking_done' => 0,
                'earning_coin' => 0.0, 'coin_withdrawal' => 0.0,
                'left_investment' => 0.0, 'right_investment' => 0.0];

        foreach ($points as $p) {
            // Active users is a DISTINCT count per bucket — summing buckets would
            // count the same member once per day. The headline is the PEAK.
            $sum['active_users']    = max($sum['active_users'], (int) $p['active_users']);
            $sum['bonus_used']     += (float) $p['bonus_used'];
            $sum['staking_done']   += (int) $p['staking_done'];
            $sum['earning_coin']   += (float) $p['earning_coin'];
            $sum['coin_withdrawal']+= (float) $p['coin_withdrawal'];
            $sum['left_investment'] += (float) $p['left_investment'];
            $sum['right_investment']+= (float) $p['right_investment'];
        }
        foreach (['bonus_used','earning_coin','coin_withdrawal','left_investment','right_investment'] as $k) {
            $sum[$k] = round($sum[$k], 4);
        }

        return [
            'range'   => $range,
            'label'   => $this->ranges[$range]['label'],
            // 'mixed': active_users + left/right investment are team-scoped,
            // everything else (bonus_used/staking_done/earning_coin/
            // coin_withdrawal) is personal — see class docblock SCOPE note.
            // platformTrend() overwrites this to 'platform' itself.
            'scope'   => self::TEAM_SCOPE ? 'mixed' : 'platform',
            'points'  => $points,
            'summary' => $sum,
            'cached'  => false,
        ];
    }
}
