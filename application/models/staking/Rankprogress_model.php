<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Rankprogress_model — THE single source of rank progress/qualification data.
 * ---------------------------------------------------------------------------
 * STRICTLY READ-ONLY. Opening any page that calls this must never award a
 * rank, write history, credit a wallet or run the cron — only
 * RankAchievementCron may create achievements.
 *
 * It builds one structured payload consumed by every rank surface (admin
 * dashboard, admin member details, user rank page, genealogy Rank Details),
 * so no controller, view or JavaScript ever re-implements a rank rule.
 *
 * Nothing here defines a threshold, a rank name, a quantity or a plan shape:
 *   thresholds  -> staking_ranks.group_incentive
 *   plans       -> staking_rank_requirements (plan_no / option_no / side /
 *                  required_qty / required_rank_id)
 *   own volume  -> Rankcalculator_model::calculateBonusVolume() (reused, not
 *                  copied) = SUM(earning_amount + staking_amount) over
 *                  staking_matching_payouts for that member, lifetime.
 *
 * That sum is the AGREED Group Incentive definition: the member's own
 * lifetime credited Binary Matching bonus. admin_overflow and raw_bonus are
 * separate columns and are never summed, and a payout row exists only once a
 * level is credited — so ceiling excess, projected and pending amounts are
 * excluded by construction rather than by a filter that could rot.
 */
class Rankprogress_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('staking/Rankcalculator_model', 'calc');
    }

    /** All ranks, tier-ordered. Cached per request — every caller needs them. */
    private $ranksCache = null;
    private function ranks()
    {
        if ($this->ranksCache === null) {
            $this->ranksCache = $this->db->order_by('tier_level', 'ASC')->get('staking_ranks')->result_array();
        }
        return $this->ranksCache;
    }

    private function rankById($id)
    {
        foreach ($this->ranks() as $r) if ((int)$r['id'] === (int)$id) return $r;
        return null;
    }

    /**
     * Requirements for one rank, grouped by plan.
     * @return array<int, array> plan_no => rows
     */
    public function planRequirements($rankId)
    {
        $rows = $this->db->where('rank_id', (int)$rankId)->where('is_active', 1)
                         ->order_by('plan_no', 'ASC')->order_by('option_no', 'ASC')->order_by('side', 'ASC')
                         ->get('staking_rank_requirements')->result_array();
        $byPlan = [];
        foreach ($rows as $r) $byPlan[(int)$r['plan_no']][] = $r;
        return $byPlan;
    }

    /**
     * Evaluate every configured plan for one rank against a member's tree.
     *
     * $tiers comes from Rankcalculator_model::tierCounts() — CUMULATIVE
     * per-leg counts ("how many at tier >= N"), fetched once per member so a
     * rank with three plans costs one tree walk, not one per requirement.
     *
     * A plan passes only when ALL of its requirements pass. Failed
     * requirements are returned too — the member must be able to see exactly
     * what is missing, never just "FAIL".
     */
    public function evaluatePlans($rankId, array $tiers)
    {
        $out = [];
        foreach ($this->planRequirements($rankId) as $planNo => $reqs) {
            $items = []; $allOk = true;
            foreach ($reqs as $r) {
                $need    = (int)$r['required_qty'];
                $side    = ((string)$r['side'] === 'right') ? 'right' : 'left';
                $reqRank = $this->rankById($r['required_rank_id']);
                $tier    = $reqRank ? (int)$reqRank['tier_level'] : null;

                // Unresolvable rank reference: report it, never silently pass.
                $current = ($tier === null) ? 0 : (int)($tiers[$side][$tier] ?? 0);
                $ok = ($tier !== null) && ($current >= $need);
                if (!$ok) $allOk = false;

                $items[] = [
                    'requirement_id' => (int)$r['id'],
                    'option_no'      => (int)$r['option_no'],
                    'side'           => $side,
                    'required_qty'   => $need,
                    'required_rank_id'   => (int)$r['required_rank_id'],
                    'required_rank_name' => $reqRank['name'] ?? ('#' . $r['required_rank_id']),
                    'required_rank_tier' => $tier,
                    'current_qty'    => $current,
                    'shortfall'      => max(0, $need - $current),
                    'status'         => $ok ? 'PASS' : 'FAIL',
                    'error'          => $tier === null ? 'required_rank_id does not resolve to a configured rank' : null,
                ];
            }
            $out[] = [
                'plan_no'      => (int)$planNo,
                'status'       => ($items && $allOk) ? 'PASS' : 'FAIL',
                'requirements' => $items,
            ];
        }
        return $out;
    }

    /**
     * The complete rank picture for one member.
     *
     * "Next rank" is the next tier ABOVE the member's current one, so the
     * hierarchy is respected — progress is never reported against a rank they
     * could skip to.
     */
    public function forUser($userId)
    {
        $userId = (int)$userId;
        $u = $this->db->select('id, username, referral_id')->get_where('users', ['id' => $userId])->row_array();
        if (!$u) return null;

        $ranks = $this->ranks();
        if (!$ranks) return null;

        // ---- own lifetime credited matching (reused, not recalculated) ----
        $vol     = $this->calc->calculateBonusVolume($userId);
        $earning = (float)$vol['earning_volume'];
        $staking = (float)$vol['staking_volume'];
        $total   = (float)$vol['total_volume'];

        // ---- current rank -------------------------------------------------
        $ur = $this->db->get_where('user_ranks', ['user_id' => $userId])->row_array();
        $current = $ur ? $this->rankById($ur['current_rank_id']) : null;
        if (!$current) $current = $ranks[0];              // tier 0 baseline (UN RANK)
        $currentTier = (int)$current['tier_level'];

        // ---- next rank = next configured tier above the current one -------
        $next = null;
        foreach ($ranks as $r) {
            if ((int)$r['tier_level'] > $currentTier && (int)$r['is_active'] === 1) { $next = $r; break; }
        }

        $target   = $next ?: $current;                    // already at the top: report against current
        $required = (float)$target['group_incentive'];
        $remaining = max(0.0, $required - $total);
        $pct = $required > 0 ? min(100.0, round($total / $required * 100, 2)) : 100.0;

        // ---- plan evaluation against the target rank ----------------------
        $tiers = $this->calc->tierCounts($userId);
        $plans = $this->evaluatePlans((int)$target['id'], $tiers);

        $qualifying = null;
        foreach ($plans as $p) if ($p['status'] === 'PASS') { $qualifying = $p['plan_no']; break; }

        // Qualification needs BOTH the threshold and at least one plan.
        $meetsIncentive = $total >= $required;
        $qualifies = $next !== null && $meetsIncentive && $qualifying !== null;

        return [
            'user' => ['id' => $userId, 'name' => $u['username'], 'uid' => $u['referral_id'] ?: ('#' . $userId)],

            'current_rank'    => $current['name'],
            'current_tier'    => $currentTier,
            'current_rank_id' => (int)$current['id'],
            'rank_achieved_date' => $ur['achieved_at'] ?? null,

            'next_rank'    => $next['name'] ?? null,
            'next_tier'    => $next ? (int)$next['tier_level'] : null,
            'next_rank_id' => $next ? (int)$next['id'] : null,
            'at_max_rank'  => $next === null,

            'group_incentive' => [
                'required'   => round($required, 4),
                'achieved'   => round($total, 4),
                'remaining'  => round($remaining, 4),
                'percentage' => $pct,
                'met'        => $meetsIncentive,
            ],

            'matching' => [
                'earning' => round($earning, 4),
                'staking' => round($staking, 4),
                'total'   => round($total, 4),
            ],

            'plans'           => $plans,
            'qualifying_plan' => $qualifying,
            'qualifies_now'   => $qualifies,
            'blocked_reason'  => $qualifies || $next === null ? null
                                 : (!$meetsIncentive
                                    ? 'Group Incentive below the configured requirement'
                                    : 'No configured plan is fully satisfied'),

            'history' => $this->history($userId),
        ];
    }

    /** Achievement history from the existing table — schema columns only. */
    public function history($userId, $limit = 50)
    {
        $rows = $this->db->where('user_id', (int)$userId)
                         ->order_by('achieved_at', 'DESC')->order_by('id', 'DESC')
                         ->limit((int)$limit)->get('user_rank_history')->result_array();
        $out = [];
        foreach ($rows as $r) {
            $old = $r['old_rank_id'] ? $this->rankById($r['old_rank_id']) : null;
            $new = $this->rankById($r['new_rank_id']);
            $out[] = [
                'achieved_at'       => $r['achieved_at'],
                'previous_rank'     => $old['name'] ?? '—',
                'new_rank'          => $new['name'] ?? ('#' . $r['new_rank_id']),
                'tier'              => $new ? (int)$new['tier_level'] : null,
                'achieved_volume'   => (float)$r['achieved_volume'],
                'earning_volume'    => (float)$r['earning_volume'],
                'staking_volume'    => (float)$r['staking_volume'],
                'left_volume'       => (float)$r['left_volume'],
                'right_volume'      => (float)$r['right_volume'],
                'qualification_plan'=> $r['qualification_plan'],
                'source'            => $r['source'],
            ];
        }
        return $out;
    }

    /** Members per rank, for the admin dashboard KPIs. One query, no N+1. */
    public function rankDistribution()
    {
        $counts = [];
        foreach ($this->db->query(
            "SELECT current_rank_id rid, COUNT(*) n FROM user_ranks GROUP BY current_rank_id"
        )->result_array() as $r) {
            $counts[(int)$r['rid']] = (int)$r['n'];
        }

        // Members with no user_ranks row are at the baseline tier, not "no
        // rank" — a distinction the spec calls out explicitly.
        $ranked = array_sum($counts);
        $totalUsers = (int)$this->db->count_all_results('users');

        $out = [];
        foreach ($this->ranks() as $i => $r) {
            $n = $counts[(int)$r['id']] ?? 0;
            if ($i === 0) $n += max(0, $totalUsers - $ranked);   // baseline absorbs the unranked
            $out[] = [
                'rank_id' => (int)$r['id'], 'name' => $r['name'],
                'tier' => (int)$r['tier_level'],
                'group_incentive' => (float)$r['group_incentive'],
                'is_active' => (int)$r['is_active'],
                'members' => $n,
            ];
        }
        return ['total_users' => $totalUsers, 'ranks' => $out];
    }
}
