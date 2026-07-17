<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Rankreport_model — the six rank reports (§REPORTS).
 * ----------------------------------------------------------------------------
 *   top_earners     Top Rank Earners            — who has been paid the most
 *   distribution    Rank Distribution           — members per rank
 *   progress        Rank Progress               — how close to the next rank
 *   reward_summary  Rank Reward Summary         — paid/pending/failed per rank
 *   power_summary   Rank Power Summary          — this cycle's power spread
 *   incentive_qual  Group Incentive Qualification — who gets paid this cycle
 *
 * Every report returns a flat array of associative rows plus a column map, so
 * one export path (CSV / Excel / PDF) serves all six without per-report code.
 * Read-only — nothing here writes.
 */
class Rankreport_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('staking/Rankpower_model', 'power');
    }

    /** Report registry: key => [label, columns]. Drives the UI and the exports. */
    public function catalogue()
    {
        return [
            'distribution' => [
                'label'   => 'Rank Distribution',
                'hint'    => 'How many members hold each rank, and what share of the base that is.',
                'columns' => ['tier_level'=>'Tier','rank_name'=>'Rank','members'=>'Members',
                              'percent'=>'% of ranked','required_group_volume'=>'Volume required',
                              'group_incentive'=>'Group incentive'],
            ],
            'top_earners' => [
                'label'   => 'Top Rank Earners',
                'hint'    => 'Members ranked by total rank reward actually paid.',
                'columns' => ['user_id'=>'User ID','username'=>'Username','email'=>'Email',
                              'rank_name'=>'Rank','bman_paid'=>'BMAN paid','usdt_paid'=>'USDT paid',
                              'rewards'=>'Rewards','last_paid'=>'Last paid'],
            ],
            'progress' => [
                'label'   => 'Rank Progress',
                'hint'    => 'Current rank, group volume, and distance to the next rank. '
                           . 'Volume here is <b>as of the last rank cron pass</b> (hourly), not live — '
                           . 'it is read from the stored figure so this report stays cheap instead of '
                           . 'walking every member\'s tree. Open a member from Rank History for a live figure.',
                'columns' => ['user_id'=>'User ID','username'=>'Username','current_rank'=>'Current rank',
                              'group_volume'=>'Group volume','next_rank'=>'Next rank',
                              'next_required'=>'Next needs','shortfall'=>'Shortfall','percent'=>'Progress %'],
            ],
            'reward_summary' => [
                'label'   => 'Rank Reward Summary',
                'hint'    => 'Reward issuance per rank, split by status. Watch the failed column.',
                'columns' => ['rank_name'=>'Rank','reward_type'=>'Type','paid'=>'Paid',
                              'pending'=>'Pending','failed'=>'Failed','total_amount'=>'Amount paid'],
            ],
            'power_summary' => [
                'label'   => 'Rank Power Summary',
                'hint'    => 'This cycle only. Power resets every cycle and never affects achievement rank.',
                'columns' => ['power_rank'=>'Power rank','tier_level'=>'Tier','members'=>'Members',
                              'qualified'=>'Qualified','total_volume'=>'Cycle volume'],
            ],
            'incentive_qual' => [
                'label'   => 'Group Incentive Qualification',
                'hint'    => 'Who qualifies this cycle, and at what level. Driven by POWER rank, never by achievement rank.',
                'columns' => ['user_id'=>'User ID','username'=>'Username','achievement_rank'=>'Achievement rank',
                              'power_rank'=>'Power rank','total_volume'=>'Cycle volume',
                              'group_incentive'=>'Incentive payable','qualified'=>'Qualified'],
            ],
        ];
    }

    /** Dispatch by key. Unknown key → empty. */
    public function run($key, $filters = [], $limit = 500)
    {
        switch ($key) {
            case 'distribution':    return $this->distribution();
            case 'top_earners':     return $this->topEarners($filters, $limit);
            case 'progress':        return $this->progress($filters, $limit);
            case 'reward_summary':  return $this->rewardSummary($filters);
            case 'power_summary':   return $this->powerSummary($filters);
            case 'incentive_qual':  return $this->incentiveQualification($filters, $limit);
            default:                return [];
        }
    }

    /* ------------------------- 1. distribution ------------------------- */

    public function distribution()
    {
        $rows = $this->db->select('r.tier_level, r.name AS rank_name, r.required_group_volume,
                                   r.group_incentive, COUNT(ur.id) AS members')
                         ->from('staking_ranks r')
                         ->join('user_ranks ur', 'ur.highest_rank_id = r.id', 'left')
                         ->group_by('r.id')
                         ->order_by('r.tier_level', 'DESC')
                         ->get()->result_array();

        $total = 0;
        foreach ($rows as $r) $total += (int)$r['members'];
        foreach ($rows as &$r) {
            $r['percent'] = $total > 0 ? round((int)$r['members'] * 100 / $total, 2) : 0;
        }
        return $rows;
    }

    /* -------------------------- 2. top earners ------------------------- */

    public function topEarners($filters = [], $limit = 500)
    {
        $this->db->select("rr.user_id, u.username, u.email,
                           COALESCE(hr.name,'Unranked') AS rank_name,
                           SUM(CASE WHEN rr.reward_type='bman' THEN rr.reward_amount ELSE 0 END) AS bman_paid,
                           SUM(CASE WHEN rr.reward_type='usdt' THEN rr.reward_amount ELSE 0 END) AS usdt_paid,
                           COUNT(*) AS rewards, MAX(rr.rewarded_at) AS last_paid", false)
                 ->from('rank_rewards rr')
                 ->join('users u', 'u.id = rr.user_id', 'left')
                 ->join('user_ranks ur', 'ur.user_id = rr.user_id', 'left')
                 ->join('staking_ranks hr', 'hr.id = ur.highest_rank_id', 'left')
                 ->where('rr.reward_status', 'paid');

        if (!empty($filters['from'])) $this->db->where('rr.rewarded_at >=', $filters['from'].' 00:00:00');
        if (!empty($filters['to']))   $this->db->where('rr.rewarded_at <=', $filters['to'].' 23:59:59');
        if (!empty($filters['rank_id'])) $this->db->where('rr.rank_id', (int)$filters['rank_id']);

        return $this->db->group_by('rr.user_id')
                        ->order_by('bman_paid', 'DESC')->order_by('usdt_paid', 'DESC')
                        ->limit((int)$limit)->get()->result_array();
    }

    /* --------------------------- 3. progress --------------------------- */

    /**
     * Distance to the next rank. Reads the stored group_volume rather than
     * recomputing every member's legs — the cron refreshes it each pass, so this
     * stays a cheap report instead of a full tree walk per row.
     */
    public function progress($filters = [], $limit = 500)
    {
        $this->db->select('ur.user_id, u.username, ur.group_volume,
                           cr.name AS current_rank, cr.tier_level')
                 ->from('user_ranks ur')
                 ->join('users u', 'u.id = ur.user_id', 'left')
                 ->join('staking_ranks cr', 'cr.id = ur.highest_rank_id', 'left');
        if (!empty($filters['rank_id'])) $this->db->where('ur.highest_rank_id', (int)$filters['rank_id']);
        $rows = $this->db->order_by('ur.group_volume', 'DESC')
                         ->limit((int)$limit)->get()->result_array();

        $ladder = $this->db->where('is_active', 1)->order_by('tier_level', 'ASC')
                           ->get('staking_ranks')->result_array();

        foreach ($rows as &$r) {
            $tier = $r['tier_level'] !== null ? (int)$r['tier_level'] : -1;
            $next = null;
            foreach ($ladder as $l) {
                if ((int)$l['tier_level'] > $tier) { $next = $l; break; }
            }
            if (!$next) {
                $r['next_rank'] = '—'; $r['next_required'] = '0';
                $r['shortfall'] = '0'; $r['percent'] = 100;
                continue;
            }
            $need = (string)$next['required_group_volume'];
            $have = (string)$r['group_volume'];
            $r['next_rank']     = $next['name'];
            $r['next_required'] = $need;
            $r['shortfall']     = bccomp($have, $need, 8) >= 0 ? '0' : bcsub($need, $have, 8);
            $r['percent']       = bccomp($need, '0', 8) > 0
                                  ? min(100, round((float)bcmul(bcdiv($have, $need, 8), '100', 4), 2))
                                  : 100;
        }
        return $rows;
    }

    /* ------------------------ 4. reward summary ------------------------ */

    public function rewardSummary($filters = [])
    {
        $this->db->select("rr.rank_name, rr.reward_type,
                           SUM(rr.reward_status='paid')    AS paid,
                           SUM(rr.reward_status='pending') AS pending,
                           SUM(rr.reward_status='failed')  AS failed,
                           SUM(CASE WHEN rr.reward_status='paid' THEN rr.reward_amount ELSE 0 END) AS total_amount", false)
                 ->from('rank_rewards rr')
                 ->join('staking_ranks r', 'r.id = rr.rank_id', 'left');
        if (!empty($filters['from'])) $this->db->where('rr.created_at >=', $filters['from'].' 00:00:00');
        if (!empty($filters['to']))   $this->db->where('rr.created_at <=', $filters['to'].' 23:59:59');
        return $this->db->group_by(['rr.rank_id','rr.reward_type'])
                        ->order_by('r.tier_level', 'DESC')->order_by('rr.reward_type', 'ASC')
                        ->get()->result_array();
    }

    /* ------------------------- 5. power summary ------------------------ */

    public function powerSummary($filters = [])
    {
        $cycle_id = !empty($filters['cycle_id']) ? (int)$filters['cycle_id'] : null;
        if (!$cycle_id) {
            $c = $this->power->currentCycle();
            if (!$c) return [];
            $cycle_id = (int)$c['id'];
        }
        return $this->db->select("COALESCE(r.name,'No power rank') AS power_rank,
                                  COALESCE(r.tier_level,-1) AS tier_level,
                                  COUNT(*) AS members,
                                  SUM(p.qualified) AS qualified,
                                  SUM(p.total_volume) AS total_volume", false)
                        ->from('user_rank_power p')
                        ->join('staking_ranks r', 'r.id = p.power_rank_id', 'left')
                        ->where('p.cycle_id', $cycle_id)
                        ->group_by('p.power_rank_id')
                        ->order_by('tier_level', 'DESC')
                        ->get()->result_array();
    }

    /* -------------------- 6. incentive qualification ------------------- */

    /**
     * Who is paid group incentive this cycle. Deliberately shows the
     * achievement rank alongside the power rank so the difference is visible:
     * a GOLD achiever with SILVER power is paid SILVER.
     */
    public function incentiveQualification($filters = [], $limit = 500)
    {
        $cycle_id = !empty($filters['cycle_id']) ? (int)$filters['cycle_id'] : null;
        if (!$cycle_id) {
            $c = $this->power->currentCycle();
            if (!$c) return [];
            $cycle_id = (int)$c['id'];
        }

        $this->db->select("p.user_id, u.username,
                           COALESCE(hr.name,'Unranked')      AS achievement_rank,
                           COALESCE(pr.name,'No power rank') AS power_rank,
                           p.total_volume,
                           CASE WHEN p.qualified = 1 THEN COALESCE(pr.group_incentive,0) ELSE 0 END AS group_incentive,
                           CASE WHEN p.qualified = 1 THEN 'Yes' ELSE 'No' END AS qualified", false)
                 ->from('user_rank_power p')
                 ->join('users u', 'u.id = p.user_id', 'left')
                 ->join('staking_ranks pr', 'pr.id = p.power_rank_id', 'left')
                 ->join('user_ranks ur', 'ur.user_id = p.user_id', 'left')
                 ->join('staking_ranks hr', 'hr.id = ur.highest_rank_id', 'left')
                 ->where('p.cycle_id', $cycle_id);

        if (isset($filters['qualified']) && $filters['qualified'] !== '') {
            $this->db->where('p.qualified', (int)$filters['qualified']);
        }
        return $this->db->order_by('pr.tier_level', 'DESC')->order_by('p.total_volume', 'DESC')
                        ->limit((int)$limit)->get()->result_array();
    }

    /* ---------------------------- headline ----------------------------- */

    /** Small stat tiles for the reports landing page. */
    public function headline()
    {
        $cycle = $this->power->currentCycle();
        return [
            'ranked_members' => (int)$this->db->count_all_results('user_ranks'),
            'promotions'     => (int)$this->db->count_all_results('user_rank_history'),
            'promotions_24h' => (int)$this->db->where('achieved_at >=', date('Y-m-d H:i:s', strtotime('-24 hours')))
                                              ->count_all_results('user_rank_history'),
            'rewards_paid'   => (int)$this->db->where('reward_status','paid')->count_all_results('rank_rewards'),
            'rewards_failed' => (int)$this->db->where('reward_status','failed')->count_all_results('rank_rewards'),
            'rewards_pending'=> (int)$this->db->where('reward_status','pending')->count_all_results('rank_rewards'),
            'bman_paid'      => (string)($this->db->select_sum('reward_amount','s')
                                    ->where(['reward_status'=>'paid','reward_type'=>'bman'])
                                    ->get('rank_rewards')->row()->s ?: '0'),
            'usdt_paid'      => (string)($this->db->select_sum('reward_amount','s')
                                    ->where(['reward_status'=>'paid','reward_type'=>'usdt'])
                                    ->get('rank_rewards')->row()->s ?: '0'),
            'certificates'   => (int)$this->db->count_all_results('rank_certificates'),
            'cycle'          => $cycle,
            'cycle_qualified'=> $cycle ? (int)$this->db->where(['cycle_id'=>$cycle['id'],'qualified'=>1])
                                                       ->count_all_results('user_rank_power') : 0,
        ];
    }
}
