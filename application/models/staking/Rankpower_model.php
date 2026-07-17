<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Rankpower_model — RankPowerService (§11).
 * ----------------------------------------------------------------------------
 * Rank Power is a SEPARATE, DYNAMIC rank that governs Group Incentive and
 * leadership-pool qualification. It has nothing to do with the permanent
 * achievement rank and must never write to user_ranks.
 *
 *   Achievement Rank = GOLD, Power Rank = SILVER  →  member gets SILVER incentive.
 *
 * Rules:
 *   - counts ONLY staking volume completed INSIDE the current cycle window
 *     (never lifetime volume — that is the achievement rank's job)
 *   - resets every cycle: a new cycle starts with no user_rank_power rows, so
 *     power falls back to nothing until the member re-earns it
 *   - cycle length is admin-configurable (staking_rank_power_settings.cycle_days,
 *     default 60)
 *
 * CYCLE WINDOWS are contiguous and exactly cycle_days long, inclusive:
 *     #1  01 Jan → 01 Mar      (60 days: 01 Jan is day 1, 01 Mar is day 60)
 *     #2  02 Mar → 30 Apr
 *     #3  01 May → 29 Jun
 * Each cycle starts the day after the previous one ends, so no staking order
 * can fall between two cycles or be counted by both.
 *
 * POWER RANK is volume-driven: the highest rank whose required_group_volume is
 * covered by this cycle's total downline volume. The Plan-1/2/3 team matrix is
 * deliberately NOT applied here — §11 defines power purely as current-cycle
 * business, and the matrix belongs to the permanent achievement ladder.
 */
class Rankpower_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('staking/Rankcalculator_model', 'calc');
        $this->load->model('staking/Rankaudit_model', 'audit');
    }

    /* ============================= settings ============================= */

    public function settings()
    {
        $s = $this->db->get_where('staking_rank_power_settings', ['id' => 1])->row_array();
        if (!$s) {
            // Fall back to the documented defaults rather than fataling if the
            // settings row was never seeded.
            $s = ['id' => 1, 'is_enabled' => 1, 'cycle_days' => 60,
                  'controls_group_incentive' => 1, 'min_power_tier' => 0,
                  'auto_open_next_cycle' => 1];
        }
        return $s;
    }

    /* ============================== cycles ============================== */

    /** The open cycle, or null when none has been started. */
    public function currentCycle()
    {
        return $this->db->where('status', 'open')->order_by('cycle_no', 'DESC')
                        ->get('staking_rank_power_cycles')->row_array() ?: null;
    }

    public function cycle($id)
    {
        return $this->db->get_where('staking_rank_power_cycles', ['id' => (int)$id])->row_array() ?: null;
    }

    public function cycles($limit = 50)
    {
        $rows = $this->db->order_by('cycle_no', 'DESC')->limit((int)$limit)
                         ->get('staking_rank_power_cycles')->result_array();
        foreach ($rows as &$c) {
            $c['user_count']      = $this->db->where('cycle_id', $c['id'])
                                             ->count_all_results('user_rank_power');
            $c['qualified_count'] = $this->db->where(['cycle_id' => $c['id'], 'qualified' => 1])
                                             ->count_all_results('user_rank_power');
        }
        return $rows;
    }

    /**
     * Open the first cycle if none exists, and roll to the next one when the
     * open cycle has expired. Idempotent — the daily cron calls this blind.
     *
     * @return array{opened:bool, closed:?int, cycle:?array, message:string}
     */
    public function ensureCycle($admin_id = null)
    {
        $s = $this->settings();
        if (empty($s['is_enabled'])) {
            return ['opened' => false, 'closed' => null, 'cycle' => $this->currentCycle(),
                    'message' => 'Rank Power is disabled — no cycle action taken.'];
        }

        $days  = max(1, (int)$s['cycle_days']);
        $open  = $this->currentCycle();
        $today = date('Y-m-d');

        // No cycle at all → open #1 starting today.
        if (!$open) {
            $cycle = $this->_openCycle($today, $days, $admin_id);
            return ['opened' => true, 'closed' => null, 'cycle' => $cycle,
                    'message' => 'Cycle #' . $cycle['cycle_no'] . ' opened (' . $days . ' days).'];
        }

        // Still running.
        if ($today <= $open['end_date']) {
            return ['opened' => false, 'closed' => null, 'cycle' => $open,
                    'message' => 'Cycle #' . $open['cycle_no'] . ' runs to ' . $open['end_date'] . '.'];
        }

        // Expired but auto-roll is off — leave it for an admin to reset by hand.
        if (empty($s['auto_open_next_cycle'])) {
            return ['opened' => false, 'closed' => null, 'cycle' => $open,
                    'message' => 'Cycle #' . $open['cycle_no'] . ' expired on ' . $open['end_date']
                                 . '; auto-roll is off, so it stays open until an admin resets it.'];
        }

        // Expired → close it and start the next one the very next day, so the
        // windows stay contiguous even if the cron missed a few days.
        $this->db->trans_begin();
        $this->db->where('id', $open['id'])->update('staking_rank_power_cycles', [
            'status' => 'closed', 'closed_at' => date('Y-m-d H:i:s'),
        ]);
        $next_start = date('Y-m-d', strtotime($open['end_date'] . ' +1 day'));
        $cycle = $this->_openCycle($next_start, $days, $admin_id, false);
        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return ['opened' => false, 'closed' => null, 'cycle' => $open,
                    'message' => 'Database error rolling the cycle.'];
        }
        $this->db->trans_commit();

        $this->audit->log('cycle_opened', [
            'changed_by' => $admin_id,
            'old_value'  => 'Cycle #' . $open['cycle_no'],
            'new_value'  => 'Cycle #' . $cycle['cycle_no'],
            'note'       => $cycle['start_date'] . ' → ' . $cycle['end_date']
                            . ' · power reset for all members',
        ]);

        return ['opened' => true, 'closed' => (int)$open['cycle_no'], 'cycle' => $cycle,
                'message' => 'Cycle #' . $open['cycle_no'] . ' closed; #' . $cycle['cycle_no']
                             . ' opened ' . $cycle['start_date'] . ' → ' . $cycle['end_date']
                             . '. Power ranks reset.'];
    }

    /** Insert one cycle. $wrap=false when the caller already opened a txn. */
    private function _openCycle($start, $days, $admin_id, $wrap = true)
    {
        $last = (int)$this->db->select_max('cycle_no')
                              ->get('staking_rank_power_cycles')->row()->cycle_no;
        $row = [
            'cycle_no'   => $last + 1,
            'start_date' => $start,
            // inclusive window: start is day 1, so the last day is start+days-1
            'end_date'   => date('Y-m-d', strtotime($start . ' +' . ($days - 1) . ' days')),
            'status'     => 'open',
            'opened_by'  => $admin_id !== null ? (int)$admin_id : null,
        ];
        $this->db->insert('staking_rank_power_cycles', $row);
        $row['id'] = (int)$this->db->insert_id();
        return $row;
    }

    /* ========================= power calculation ======================== */

    /**
     * Recalculate one member's power rank for the open cycle.
     * Writes user_rank_power only. NEVER touches user_ranks.
     *
     * @return array{status:string, user_id:int, power_rank:?string, qualified:bool,
     *               left_volume:string, right_volume:string, total_volume:string}
     */
    public function processUserPower($user_id, $cycle = null, $ranks = null)
    {
        $user_id = (int)$user_id;
        $cycle   = $cycle ?: $this->currentCycle();

        $out = ['status' => 'success', 'user_id' => $user_id, 'power_rank' => null,
                'qualified' => false, 'left_volume' => '0', 'right_volume' => '0',
                'total_volume' => '0'];

        if (!$cycle) { $out['status'] = 'error'; return $out; }

        $s     = $this->settings();
        $ranks = $ranks ?: $this->rankLadder();

        // Cycle-windowed volume ONLY. The whole end_date is in scope.
        $vol = $this->calc->calculateGroupVolume(
            $user_id,
            $cycle['start_date'] . ' 00:00:00',
            $cycle['end_date'] . ' 23:59:59'
        );
        $out['left_volume']  = $vol['left_volume'];
        $out['right_volume'] = $vol['right_volume'];
        $out['total_volume'] = $vol['total_volume'];

        // Highest rank this cycle's volume covers ($ranks is tier DESC).
        $power_id = null; $power_tier = null; $power_name = null;
        foreach ($ranks as $r) {
            if (bccomp($vol['total_volume'], (string)$r['required_group_volume'], 8) >= 0) {
                $power_id   = (int)$r['id'];
                $power_tier = (int)$r['tier_level'];
                $power_name = $r['name'];
                break;
            }
        }

        $qualified = ($power_id !== null && $power_tier >= (int)$s['min_power_tier']) ? 1 : 0;
        $now = date('Y-m-d H:i:s');

        $row = [
            'power_rank_id' => $power_id,
            'left_volume'   => $vol['left_volume'],
            'right_volume'  => $vol['right_volume'],
            'total_volume'  => $vol['total_volume'],
            'qualified'     => $qualified,
            'calculated_at' => $now,
        ];

        $existing = $this->db->get_where('user_rank_power', [
            'user_id' => $user_id, 'cycle_id' => (int)$cycle['id'],
        ])->row_array();

        if ($existing) {
            // achieved_at marks when this power level was first reached this cycle.
            if ($power_id !== null && (int)$existing['power_rank_id'] !== $power_id) {
                $row['achieved_at'] = $now;
            }
            $this->db->where('id', $existing['id'])->update('user_rank_power', $row);
        } else {
            $row['user_id']     = $user_id;
            $row['cycle_id']    = (int)$cycle['id'];
            $row['achieved_at'] = $power_id !== null ? $now : null;
            $this->db->insert('user_rank_power', $row);
        }

        $out['power_rank'] = $power_name;
        $out['qualified']  = (bool)$qualified;
        return $out;
    }

    /** Active ranks, tier DESC — the ladder both engines walk. */
    public function rankLadder()
    {
        return $this->db->where('is_active', 1)->order_by('tier_level', 'DESC')
                        ->get('staking_ranks')->result_array();
    }

    /* ==================== GROUP INCENTIVE INTEGRATION =================== */

    /**
     * THE integration point for Group Incentive. Group Incentive must qualify on
     * Rank Power, never on the permanent achievement rank — a GOLD achiever
     * whose power is SILVER this cycle receives the SILVER incentive.
     *
     * Returns the rank row to pay against, or null when the member does not
     * qualify this cycle. Any group-incentive calculation should read the
     * amount from ['group_incentive'] on the returned row.
     *
     * When Rank Power is disabled, or is configured not to control group
     * incentive (controls_group_incentive = 0), this falls back to the
     * permanent achievement rank — that is the admin explicitly opting out.
     */
    public function incentiveRankFor($user_id)
    {
        $s = $this->settings();

        if (empty($s['is_enabled']) || empty($s['controls_group_incentive'])) {
            $row = $this->db->select('r.*')
                            ->from('user_ranks ur')
                            ->join('staking_ranks r', 'r.id = ur.highest_rank_id')
                            ->where('ur.user_id', (int)$user_id)
                            ->get()->row_array();
            return $row ?: null;
        }

        $cycle = $this->currentCycle();
        if (!$cycle) return null;

        $row = $this->db->select('r.*, p.qualified, p.total_volume')
                        ->from('user_rank_power p')
                        ->join('staking_ranks r', 'r.id = p.power_rank_id')
                        ->where('p.user_id', (int)$user_id)
                        ->where('p.cycle_id', (int)$cycle['id'])
                        ->get()->row_array();

        if (!$row || empty($row['qualified'])) return null;
        return $row;
    }

    /** Convenience: the incentive amount payable this cycle (0 when unqualified). */
    public function incentiveAmountFor($user_id)
    {
        $r = $this->incentiveRankFor($user_id);
        return $r ? (string)$r['group_incentive'] : '0';
    }

    /* ============================== reads =============================== */

    /** Power leaderboard for a cycle. */
    public function cyclePowers($cycle_id, $filters = [], $limit = 100, $offset = 0)
    {
        $this->_powerFilters($filters);
        return $this->db->select('p.*, u.username, u.email, r.name AS power_rank,
                                  r.tier_level, r.badge_color, r.group_incentive')
                        ->from('user_rank_power p')
                        ->join('users u', 'u.id = p.user_id', 'left')
                        ->join('staking_ranks r', 'r.id = p.power_rank_id', 'left')
                        ->where('p.cycle_id', (int)$cycle_id)
                        ->order_by('r.tier_level', 'DESC')
                        ->order_by('p.total_volume', 'DESC')
                        ->limit((int)$limit, (int)$offset)
                        ->get()->result_array();
    }

    public function cyclePowersCount($cycle_id, $filters = [])
    {
        $this->_powerFilters($filters);
        return $this->db->from('user_rank_power p')
                        ->join('staking_ranks r', 'r.id = p.power_rank_id', 'left')
                        ->where('p.cycle_id', (int)$cycle_id)
                        ->count_all_results();
    }

    private function _powerFilters($f)
    {
        if (!empty($f['rank_id']))  $this->db->where('p.power_rank_id', (int)$f['rank_id']);
        if (isset($f['qualified']) && $f['qualified'] !== '') {
            $this->db->where('p.qualified', (int)$f['qualified']);
        }
        if (!empty($f['user_id']))  $this->db->where('p.user_id', (int)$f['user_id']);
    }

    /** A member's power row for the open cycle. */
    public function userPower($user_id, $cycle_id = null)
    {
        if (!$cycle_id) {
            $c = $this->currentCycle();
            if (!$c) return null;
            $cycle_id = $c['id'];
        }
        return $this->db->select('p.*, r.name AS power_rank, r.tier_level, r.badge_color,
                                  r.group_incentive, c.cycle_no, c.start_date, c.end_date')
                        ->from('user_rank_power p')
                        ->join('staking_ranks r', 'r.id = p.power_rank_id', 'left')
                        ->join('staking_rank_power_cycles c', 'c.id = p.cycle_id')
                        ->where('p.user_id', (int)$user_id)
                        ->where('p.cycle_id', (int)$cycle_id)
                        ->get()->row_array() ?: null;
    }
}
