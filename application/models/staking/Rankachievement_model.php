<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Rankachievement_model — RankAchievementEngine.
 * ----------------------------------------------------------------------------
 * Evaluates and promotes the PERMANENT achievement rank (§10).
 *
 * processUserRank($user_id):
 *   1. calculate lifetime binary matching bonus volume (Rankcalculator_model::calculateBonusVolume)
 *   2. load rank definitions                  (staking_ranks)
 *   3. walk ranks highest → lowest
 *   4. validate qualification plans           (staking_rank_requirements)
 *   5. promote if qualified
 *   6. write user_rank_history
 *   7. issue reward                           (Rankreward_model)
 *   8. issue certificate                      (Rankreward_model)
 *   9. notify                                 (Rankreward_model)
 *  10. audit                                  (staking_rank_audit)
 *
 * PERMANENT RANK PROTECTION
 * A rank, once achieved, is never removed or lowered. If a GOLD member's volume
 * collapses to zero they stay GOLD forever. The engine only ever moves a member
 * UP: a computed rank at or below the stored highest tier is discarded.
 * Enforced in one place — _promote() refuses any tier <= highest_tier.
 *
 * IDEMPOTENCY
 * Re-running costs nothing and changes nothing:
 *   - no new qualification  → no write at all
 *   - reward already issued → UNIQUE(user,rank,type) on rank_rewards blocks it
 *   - certificate issued    → UNIQUE(user,rank) on rank_certificates blocks it
 * Rewards/certificates/notifications are re-attempted on every pass (not only
 * on the promoting pass), so a crash between promotion and payout self-heals on
 * the next run instead of silently losing a member's reward.
 */
class Rankachievement_model extends CI_Model
{
    /** @var array<int,array> rank rows keyed by id, tier DESC */
    private $ranks = null;
    /** @var array<int,int> rank_id => tier_level */
    private $rank_tier = null;
    /** @var array<int,array> rank_id => [plan_no => [option_no => [rows]]] */
    private $reqs = null;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('staking/Rankcalculator_model', 'calc');
        $this->load->model('staking/Rankreward_model', 'rewards');
        $this->load->model('staking/Rankaudit_model', 'audit');
    }

    /* ========================= definition loading ======================== */

    /** Load ranks + requirement matrix once per run. */
    public function primeDefinitions()
    {
        if ($this->ranks !== null) return;

        $rows = $this->db->where('is_active', 1)
                         ->order_by('tier_level', 'DESC')
                         ->get('staking_ranks')->result_array();

        $this->ranks = $this->rank_tier = [];
        foreach ($rows as $r) {
            $this->ranks[(int)$r['id']]     = $r;
            $this->rank_tier[(int)$r['id']] = (int)$r['tier_level'];
        }

        // Requirements grouped rank → plan → option. Rows inside one option are
        // AND-ed; options are OR-ed; plans are OR-ed.
        $this->reqs = [];
        $qs = $this->db->where('is_active', 1)->get('staking_rank_requirements')->result_array();
        foreach ($qs as $q) {
            $this->reqs[(int)$q['rank_id']][(int)$q['plan_no']][(int)$q['option_no']][] = $q;
        }
    }

    public function reset()
    {
        $this->ranks = $this->rank_tier = $this->reqs = null;
        $this->calc->reset();
    }

    /* ====================== qualification evaluation ===================== */

    /**
     * Does $tier_counts satisfy ANY qualification plan of $rank_id?
     * A user qualifies if ANY plan succeeds — never all of them.
     *
     * @param array $tier_counts from Rankcalculator_model::tierCounts()
     * @return string|null  "Plan-2 / Option-1" when qualified, null otherwise
     */
    public function matchPlan($rank_id, array $tier_counts)
    {
        $this->primeDefinitions();
        $rank_id = (int)$rank_id;

        // A rank with no requirement rows is volume-only (UN RANK).
        if (empty($this->reqs[$rank_id])) return 'Volume only';

        $plans = $this->reqs[$rank_id];
        ksort($plans);

        foreach ($plans as $plan_no => $options) {
            ksort($options);
            foreach ($options as $option_no => $rows) {
                if ($this->_optionMet($rows, $tier_counts)) {
                    return 'Plan-' . $plan_no . ' / Option-' . $option_no;
                }
            }
        }
        return null;
    }

    /** All rows of one option must hold (left AND right). */
    private function _optionMet(array $rows, array $tier_counts)
    {
        foreach ($rows as $r) {
            $side = ($r['side'] === 'right') ? 'right' : 'left';
            $need = (int)$r['required_qty'];
            $rid  = (int)$r['required_rank_id'];

            // Requirement points at a disabled/missing rank — cannot be met.
            if (!isset($this->rank_tier[$rid])) return false;
            $tier = $this->rank_tier[$rid];

            $have = isset($tier_counts[$side][$tier]) ? (int)$tier_counts[$side][$tier] : 0;
            if ($have < $need) return false;
        }
        return true;
    }

    /* =========================== main entry ============================= */

    /**
     * Evaluate one member and promote if they qualify.
     *
     * @param int  $user_id
     * @param array $opts  ['source' => 'cron'|'admin', 'admin_id' => int|null]
     * @return array{
     *   status:string, user_id:int, promoted:bool,
     *   old_rank:?string, new_rank:?string, plan:?string,
     *   earning_volume:string, staking_volume:string, total_volume:string, message:string
     * }
     */
    public function processUserRank($user_id, $opts = [])
    {
        $this->primeDefinitions();
        $user_id = (int)$user_id;
        $source  = isset($opts['source']) ? $opts['source'] : 'cron';
        $admin   = isset($opts['admin_id']) ? (int)$opts['admin_id'] : null;

        $out = [
            'status' => 'success', 'user_id' => $user_id, 'promoted' => false,
            'old_rank' => null, 'new_rank' => null, 'plan' => null,
            'earning_volume' => '0', 'staking_volume' => '0', 'total_volume' => '0',
            'message' => 'No change.',
        ];

        if ($user_id <= 0) {
            $out['status'] = 'error'; $out['message'] = 'Invalid user id.';
            return $out;
        }

        // ---- Step 1: rank volume = lifetime binary matching bonus (own credits, not downline) ----
        $vol = $this->calc->calculateBonusVolume($user_id);
        $out['earning_volume'] = $vol['earning_volume'];
        $out['staking_volume'] = $vol['staking_volume'];
        $out['total_volume']   = $vol['total_volume'];

        // ---- current state ----
        $current      = $this->currentRank($user_id);
        $highest_tier = $current ? (int)$current['highest_tier'] : -1;
        $out['old_rank'] = $current ? $current['highest_rank'] : null;

        // ---- Steps 2-4: highest → lowest, first qualifying rank wins ----
        $tier_counts = $this->calc->tierCounts($user_id);
        $qualified_id = null; $qualified_plan = null;

        foreach ($this->ranks as $rid => $rank) {          // already tier DESC
            $tier = (int)$rank['tier_level'];

            // Permanent protection: nothing at or below the held rank matters.
            if ($tier <= $highest_tier) break;

            if (bccomp($vol['total_volume'], (string)$rank['required_group_volume'], 8) < 0) {
                continue;
            }
            $plan = $this->matchPlan($rid, $tier_counts);
            if ($plan !== null) {
                $qualified_id   = (int)$rid;
                $qualified_plan = $plan;
                break;
            }
        }

        // ---- Step 5: promote ----
        if ($qualified_id === null) {
            // Self-healing: a member promoted on an earlier pass whose reward or
            // certificate failed gets another attempt here.
            if ($current && $current['highest_rank_id']) {
                $this->rewards->ensureBenefits($user_id, (int)$current['highest_rank_id']);
            }
            $this->_touchVolume($user_id, $vol);
            return $out;
        }

        list($ok, $msg) = $this->_promote($user_id, $qualified_id, $vol, $qualified_plan, $source, $admin);
        if (!$ok) {
            $out['status'] = 'error'; $out['message'] = $msg;
            return $out;
        }

        // ---- Steps 7-9: reward + certificate + notification (post-commit) ----
        $this->rewards->ensureBenefits($user_id, $qualified_id);

        // A promotion changes what this member contributes to their uplines'
        // rank counts — drop the tier cache so uplines later in this same pass
        // see it (the cron orders users deepest-first for exactly this reason).
        $this->calc->flushTiers();

        $out['promoted'] = true;
        $out['new_rank'] = $this->ranks[$qualified_id]['name'];
        $out['plan']     = $qualified_plan;
        $out['message']  = 'Promoted to ' . $out['new_rank'] . ' (' . $qualified_plan . ').';
        return $out;
    }

    /* ============================ promotion ============================= */

    /**
     * Write the promotion. Transactional: user_ranks + user_rank_history + audit
     * land together or not at all.
     *
     * Rewards are deliberately OUTSIDE this transaction — they move money via
     * Walletledger_model, which runs its own transaction and must not be nested.
     * If the process dies after this commit but before the reward, the next cron
     * pass re-issues it via ensureBenefits(); the UNIQUE index keeps that safe.
     */
    private function _promote($user_id, $rank_id, array $vol, $plan, $source, $admin)
    {
        $rank = $this->ranks[$rank_id];
        $now  = date('Y-m-d H:i:s');

        $this->db->trans_begin();

        // Lock the member's rank row so two concurrent batches cannot both promote.
        $existing = $this->db->query(
            'SELECT * FROM user_ranks WHERE user_id = ? FOR UPDATE', [(int)$user_id]
        )->row_array();

        $old_rank_id = $existing ? $existing['highest_rank_id'] : null;

        // Re-check under the lock — a concurrent pass may have promoted already.
        if ($old_rank_id !== null && isset($this->rank_tier[(int)$old_rank_id])
            && $this->rank_tier[(int)$old_rank_id] >= (int)$rank['tier_level']) {
            $this->db->trans_rollback();
            return [true, 'Already at or above this rank.'];
        }

        $row = [
            'current_rank_id' => (int)$rank_id,
            'highest_rank_id' => (int)$rank_id,
            'group_volume'    => $vol['total_volume'],
            'achieved_at'     => $now,
        ];
        if ($existing) {
            $this->db->where('user_id', (int)$user_id)->update('user_ranks', $row);
        } else {
            $row['user_id'] = (int)$user_id;
            $this->db->insert('user_ranks', $row);
        }

        $this->db->insert('user_rank_history', [
            'user_id'            => (int)$user_id,
            'old_rank_id'        => $old_rank_id ? (int)$old_rank_id : null,
            'new_rank_id'        => (int)$rank_id,
            'achieved_volume'    => $vol['total_volume'],
            // left_volume/right_volume are the OLD downline-team-volume metric
            // — no longer computed as part of qualification, so they are
            // simply omitted here and fall back to their column DEFAULT (0)
            // on new rows. Historical rows keep their original, correct
            // meaning. earning_volume/staking_volume record what actually
            // qualified this promotion.
            'earning_volume'     => $vol['earning_volume'],
            'staking_volume'     => $vol['staking_volume'],
            'qualification_plan' => $plan,
            'source'             => $source,
            'achieved_at'        => $now,
        ]);

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return [false, 'Database error during promotion.'];
        }
        $this->db->trans_commit();

        // ---- Step 10: audit (outside the txn; a log write must never
        //      roll back a promotion that already succeeded) ----
        $this->audit->log('rank_promoted', [
            'user_id'    => $user_id,
            'rank_id'    => $rank_id,
            'old_value'  => $old_rank_id && isset($this->ranks[(int)$old_rank_id])
                                ? $this->ranks[(int)$old_rank_id]['name'] : 'Unranked',
            'new_value'  => $rank['name'],
            'changed_by' => $admin,
            'note'       => $plan . ' · volume ' . $vol['total_volume']
                            . ' (Earning ' . $vol['earning_volume'] . ' / Staking ' . $vol['staking_volume'] . ')',
        ]);

        return [true, 'Promoted.'];
    }

    /** Keep the informational volume fresh even when no promotion happens. */
    private function _touchVolume($user_id, array $vol)
    {
        $has = $this->db->where('user_id', (int)$user_id)->count_all_results('user_ranks');
        if ($has) {
            $this->db->where('user_id', (int)$user_id)
                     ->update('user_ranks', ['group_volume' => $vol['total_volume']]);
        }
    }

    /* ============================== reads =============================== */

    /** A member's permanent rank, or null when unranked. */
    public function currentRank($user_id)
    {
        return $this->db->select('ur.*, r.name AS highest_rank, r.tier_level AS highest_tier,
                                  r.badge_image, r.badge_color, c.name AS current_rank')
                        ->from('user_ranks ur')
                        ->join('staking_ranks r', 'r.id = ur.highest_rank_id', 'left')
                        ->join('staking_ranks c', 'c.id = ur.current_rank_id', 'left')
                        ->where('ur.user_id', (int)$user_id)
                        ->get()->row_array() ?: null;
    }

    /* ---------------------------- history ------------------------------ */

    /**
     * Every promotion, newest first — the admin Rank History page.
     * $filters: user_id, rank_id (new rank), plan, source, from, to, q (username/email)
     */
    public function history($filters = [], $limit = 50, $offset = 0)
    {
        $this->_historyFilters($filters);
        return $this->db->select('h.*, u.username, u.email,
                                  nr.name AS new_rank, nr.tier_level, nr.badge_color, nr.badge_image,
                                  orr.name AS old_rank')
                        ->from('user_rank_history h')
                        ->join('users u', 'u.id = h.user_id', 'left')
                        ->join('staking_ranks nr', 'nr.id = h.new_rank_id', 'left')
                        ->join('staking_ranks orr', 'orr.id = h.old_rank_id', 'left')
                        ->order_by('h.achieved_at', 'DESC')->order_by('h.id', 'DESC')
                        ->limit((int)$limit, (int)$offset)
                        ->get()->result_array();
    }

    public function historyCount($filters = [])
    {
        $this->_historyFilters($filters);
        return $this->db->from('user_rank_history h')
                        ->join('users u', 'u.id = h.user_id', 'left')
                        ->count_all_results();
    }

    private function _historyFilters($f)
    {
        if (!empty($f['user_id'])) $this->db->where('h.user_id', (int)$f['user_id']);
        if (!empty($f['rank_id'])) $this->db->where('h.new_rank_id', (int)$f['rank_id']);
        if (!empty($f['source']))  $this->db->where('h.source', $f['source']);
        if (!empty($f['plan']))    $this->db->like('h.qualification_plan', $f['plan']);
        if (!empty($f['from']))    $this->db->where('h.achieved_at >=', $f['from'] . ' 00:00:00');
        if (!empty($f['to']))      $this->db->where('h.achieved_at <=', $f['to'] . ' 23:59:59');
        if (!empty($f['q'])) {
            $this->db->group_start()
                     ->like('u.username', $f['q'])->or_like('u.email', $f['q'])
                     ->group_end();
        }
    }

    /** One member's full rank timeline — the member detail drawer. */
    public function userHistory($user_id)
    {
        return $this->db->select('h.*, nr.name AS new_rank, nr.badge_color, orr.name AS old_rank')
                        ->from('user_rank_history h')
                        ->join('staking_ranks nr', 'nr.id = h.new_rank_id', 'left')
                        ->join('staking_ranks orr', 'orr.id = h.old_rank_id', 'left')
                        ->where('h.user_id', (int)$user_id)
                        ->order_by('h.achieved_at', 'ASC')
                        ->get()->result_array();
    }

    /** Ranked members list for the admin Rank Power / distribution drill-down. */
    public function rankedMembers($filters = [], $limit = 50, $offset = 0)
    {
        if (!empty($filters['rank_id'])) $this->db->where('ur.highest_rank_id', (int)$filters['rank_id']);
        if (!empty($filters['q'])) {
            $this->db->group_start()->like('u.username', $filters['q'])
                     ->or_like('u.email', $filters['q'])->group_end();
        }
        return $this->db->select('ur.*, u.username, u.email, r.name AS rank_name,
                                  r.tier_level, r.badge_color, r.badge_image')
                        ->from('user_ranks ur')
                        ->join('users u', 'u.id = ur.user_id', 'left')
                        ->join('staking_ranks r', 'r.id = ur.highest_rank_id', 'left')
                        ->order_by('r.tier_level', 'DESC')->order_by('ur.achieved_at', 'DESC')
                        ->limit((int)$limit, (int)$offset)->get()->result_array();
    }

    /**
     * What is still missing for the next rank up — powers the user-side
     * progress card and the admin Rank Progress report.
     */
    public function nextRankProgress($user_id)
    {
        $this->primeDefinitions();
        $current = $this->currentRank($user_id);
        $tier    = $current ? (int)$current['highest_tier'] : -1;

        // ranks is tier DESC; the next rank up is the lowest tier above ours.
        $next = null;
        foreach ($this->ranks as $rid => $r) {
            if ((int)$r['tier_level'] > $tier) $next = $r;
        }
        if (!$next) return null;   // already CHALLENGER

        $vol   = $this->calc->calculateBonusVolume($user_id);
        $need  = (string)$next['required_group_volume'];
        $have  = $vol['total_volume'];
        $pct   = bccomp($need, '0', 8) > 0
                 ? min(100, (float)bcmul(bcdiv($have, $need, 8), '100', 4)) : 100;

        $counts = $this->calc->tierCounts($user_id);
        $plan   = $this->matchPlan((int)$next['id'], $counts);

        return [
            'next_rank'       => $next['name'],
            'next_rank_id'    => (int)$next['id'],
            'required_volume' => $need,
            'current_volume'  => $have,
            'volume_percent'  => round($pct, 2),
            'volume_met'      => bccomp($have, $need, 8) >= 0,
            'plan_met'        => $plan !== null,
            'matched_plan'    => $plan,
            'requirements'    => $this->planBreakdown((int)$next['id'], $counts),
        ];
    }

    /**
     * Per-plan "have vs need" detail for one rank. Used by the progress report
     * so a member can see which of the three routes they are closest to.
     */
    public function planBreakdown($rank_id, array $tier_counts)
    {
        $this->primeDefinitions();
        $rank_id = (int)$rank_id;
        if (empty($this->reqs[$rank_id])) return [];

        $out   = [];
        $names = [];
        foreach ($this->ranks as $rid => $r) $names[(int)$rid] = $r['name'];

        foreach ($this->reqs[$rank_id] as $plan_no => $options) {
            foreach ($options as $option_no => $rows) {
                $conds = [];
                $met   = true;
                foreach ($rows as $r) {
                    $side = ($r['side'] === 'right') ? 'right' : 'left';
                    $rid  = (int)$r['required_rank_id'];
                    $tier = isset($this->rank_tier[$rid]) ? $this->rank_tier[$rid] : null;
                    $have = ($tier !== null && isset($tier_counts[$side][$tier]))
                            ? (int)$tier_counts[$side][$tier] : 0;
                    $need = (int)$r['required_qty'];
                    if ($have < $need) $met = false;
                    $conds[] = [
                        'side' => $side, 'need' => $need, 'have' => $have,
                        'rank' => isset($names[$rid]) ? $names[$rid] : '?',
                        'met'  => $have >= $need,
                    ];
                }
                $out[] = [
                    'plan' => (int)$plan_no, 'option' => (int)$option_no,
                    'conditions' => $conds, 'met' => $met,
                ];
            }
        }
        return $out;
    }
}
