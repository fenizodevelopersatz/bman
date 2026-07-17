<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Memberrank_model — member-facing data for the Rank & Rewards page.
 * ----------------------------------------------------------------------------
 * Composes the BMAN Rank Achievement System (§10) + Rank Power (§11) into the
 * shape the user page and the right-hand panel need. It contains NO rank logic
 * of its own — every number comes from the canonical services:
 *
 *   staking/Rankcalculator_model  group volume + binary-tree traversal
 *   staking/Rankachievement_model current rank, next-rank progress, plan match
 *   staking/Rankpower_model       60-day cycle + group-incentive eligibility
 *   staking/Rankreward_model      rewards + certificates
 *
 * Tables read: staking_ranks · staking_rank_requirements · user_ranks ·
 *              user_rank_power · user_rank_history · rank_rewards ·
 *              rank_certificates · staking_swap_orders (via the calculator)
 *
 * REPLACES the old pairing engine (application/models/user/RankModel.php).
 * There are NO pairs, PV, weak-leg BV, carry forward or cycle matching here —
 * the new system has no such concepts. Rank is earned on downline group volume
 * plus a left/right team-rank matrix.
 *
 * TWO ENTRY POINTS, deliberately different in cost:
 *   pageData($uid)  full + LIVE volume — one page, accuracy matters
 *   sidebar($uid)   cheap + STORED volume — rendered on 14 pages, so it must
 *                   not walk the genealogy tree on every request. The stored
 *                   figure is refreshed each hourly cron pass.
 */
class Memberrank_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('staking/Rankachievement_model', 'rk_engine');
        $this->load->model('staking/Rankcalculator_model', 'rk_calc');
        $this->load->model('staking/Rankpower_model', 'rk_power');
        $this->load->model('staking/Rankreward_model', 'rk_rewards');
    }

    /* ========================= FULL PAGE DATA ========================== */

    /**
     * Everything the Rank & Rewards page renders.
     *
     * @return array{
     *   rank:array, next:?array, volume:array, power:array, incentive:array,
     *   ladder:array, history:array, rewards:array, certificates:array
     * }
     */
    public function pageData($user_id)
    {
        $user_id = (int)$user_id;

        $current = $this->rk_engine->currentRank($user_id);
        $volume  = $this->rk_calc->calculateGroupVolume($user_id);   // live
        $next    = $this->rk_engine->nextRankProgress($user_id);

        return [
            'rank'         => $this->_rankBlock($current),
            'next'         => $next,                 // null when already CHALLENGER
            'volume'       => $volume,
            'power'        => $this->_powerBlock($user_id),
            'incentive'    => $this->_incentiveBlock($user_id),
            'ladder'       => $this->ladder($user_id),
            'history'      => $this->rk_engine->userHistory($user_id),
            'rewards'      => $this->rk_rewards->rewards(['user_id' => $user_id], 100),
            'certificates' => $this->rk_rewards->certificates(['user_id' => $user_id], 100),
        ];
    }

    /* ---------------------------- blocks ------------------------------ */

    /**
     * The tier_level=0 row (UN RANK) — used as the display placeholder for a
     * member who hasn't achieved any real rank yet. Shown, not awarded: the
     * badge/name come from here purely so the widget has something to render
     * besides a plain dot; `has_rank` stays false either way.
     */
    private function _unrankedFallback()
    {
        static $row = false;
        if ($row === false) {
            $row = $this->db->where('tier_level', 0)->limit(1)->get('staking_ranks')->row_array() ?: null;
        }
        return $row;
    }

    /** Achievement rank — permanent, never downgraded. */
    private function _rankBlock($current)
    {
        if (!$current || empty($current['highest_rank'])) {
            $un = $this->_unrankedFallback();
            return [
                'has_rank'    => false,
                'name'        => $un['name'] ?? 'UN RANK',
                'tier'        => null,
                'badge_image' => $un['badge_image'] ?? null,
                'badge_color' => $un['badge_color'] ?? '#9e9e9e',
                'achieved_at' => null,
            ];
        }
        return [
            'has_rank'    => true,
            'name'        => $current['highest_rank'],
            'tier'        => (int)$current['highest_tier'],
            'badge_image' => $current['badge_image'] ?: null,
            'badge_color' => $current['badge_color'] ?: '#6E56CF',
            'achieved_at' => $current['achieved_at'],
        ];
    }

    /**
     * Rank Power — current 60-day cycle only. Never lifetime volume, and it
     * never affects the permanent rank.
     */
    private function _powerBlock($user_id)
    {
        $settings = $this->rk_power->settings();
        $cycle    = $this->rk_power->currentCycle();

        $out = [
            'enabled'      => !empty($settings['is_enabled']),
            'cycle_days'   => (int)$settings['cycle_days'],
            'has_cycle'    => (bool)$cycle,
            'cycle_no'     => null, 'start_date' => null, 'end_date' => null,
            'days_left'    => null, 'days_elapsed' => null, 'cycle_percent' => 0,
            'rank'         => null, 'tier' => null, 'badge_color' => '#9e9e9e',
            'left_volume'  => '0', 'right_volume' => '0', 'total_volume' => '0',
            'qualified'    => false, 'calculated_at' => null,
        ];
        if (!$cycle) return $out;

        $out['cycle_no']   = (int)$cycle['cycle_no'];
        $out['start_date'] = $cycle['start_date'];
        $out['end_date']   = $cycle['end_date'];

        $today   = strtotime(date('Y-m-d'));
        $start   = strtotime($cycle['start_date']);
        $end     = strtotime($cycle['end_date']);
        $total_d = max(1, (int)round(($end - $start) / 86400) + 1);
        $elapsed = min($total_d, max(0, (int)floor(($today - $start) / 86400) + 1));

        $out['days_left']     = max(0, (int)ceil(($end - $today) / 86400));
        $out['days_elapsed']  = $elapsed;
        $out['cycle_percent'] = (int)round($elapsed * 100 / $total_d);

        $p = $this->rk_power->userPower($user_id, $cycle['id']);
        if ($p) {
            $out['rank']          = $p['power_rank'];
            $out['tier']          = $p['tier_level'] !== null ? (int)$p['tier_level'] : null;
            $out['badge_color']   = $p['badge_color'] ?: '#9e9e9e';
            $out['left_volume']   = $p['left_volume'];
            $out['right_volume']  = $p['right_volume'];
            $out['total_volume']  = $p['total_volume'];
            $out['qualified']     = (bool)$p['qualified'];
            $out['calculated_at'] = $p['calculated_at'];
        }
        return $out;
    }

    /**
     * Group Incentive eligibility. §11: this is driven by POWER rank, never by
     * the permanent achievement rank — a GOLD achiever whose power this cycle
     * is SILVER is paid the SILVER incentive.
     */
    private function _incentiveBlock($user_id)
    {
        $settings = $this->rk_power->settings();
        $row      = $this->rk_power->incentiveRankFor($user_id);
        $power_driven = !empty($settings['is_enabled']) && !empty($settings['controls_group_incentive']);

        return [
            'qualified'    => (bool)$row,
            'paid_on'      => $power_driven ? 'power' : 'achievement',
            'rank'         => $row ? $row['name'] : null,
            'amount'       => $row ? (string)$row['group_incentive'] : '0',
            'min_tier'     => (int)$settings['min_power_tier'],
        ];
    }

    /* ---------------------------- ladder ------------------------------ */

    /**
     * All 11 ranks with per-rank state for the badge wall / rank path.
     * A rank is `achieved` when the member's permanent tier reaches it —
     * and because ranks are permanent, achieved never flips back to locked.
     */
    public function ladder($user_id)
    {
        $current = $this->rk_engine->currentRank($user_id);
        $tier    = ($current && $current['highest_tier'] !== null) ? (int)$current['highest_tier'] : -1;

        $this->load->model('Staking_model', 'rk_staking');
        $ranks = $this->rk_staking->ranks(true);

        // When was each rank reached? (for the badge tooltip / journey)
        $when = [];
        foreach ($this->rk_engine->userHistory($user_id) as $h) {
            $when[(int)$h['new_rank_id']] = $h['achieved_at'];
        }

        $out = [];
        foreach ($ranks as $r) {
            $t = (int)$r['tier_level'];
            $out[] = [
                'id'              => (int)$r['id'],
                'tier'            => $t,
                'name'            => $r['name'],
                'required_volume' => $r['required_group_volume'],
                'group_incentive' => $r['group_incentive'],
                'reward_bman'     => $r['reward_bman'],
                'reward_usdt'     => $r['reward_usdt'],
                'reward_note'     => $r['reward_description'],
                'badge_image'     => $r['badge_image'],
                'badge_color'     => $r['badge_color'] ?: '#9e9e9e',
                'benefits'        => [
                    'badge'       => (bool)$r['benefit_badge'],
                    'certificate' => (bool)$r['benefit_certificate'],
                    'reward'      => (bool)$r['benefit_reward'],
                    'recognition' => (bool)$r['benefit_recognition'],
                ],
                'plans'       => $this->_plansFor($r['requirements']),
                'achieved'    => $t <= $tier,
                'is_current'  => $t === $tier,
                'is_next'     => $t === $tier + 1,
                'achieved_at' => isset($when[(int)$r['id']]) ? $when[(int)$r['id']] : null,
            ];
        }
        return $out;
    }

    /**
     * Group requirement rows into readable plans.
     * Plans are OR routes · options inside a plan are OR · conditions inside
     * one option are AND. A member needs ANY ONE plan, never all of them.
     */
    private function _plansFor($requirements)
    {
        $plans = [];
        foreach ((array)$requirements as $q) {
            $plans[(int)$q['plan_no']][(int)$q['option_no']][] = [
                'side' => $q['side'],
                'qty'  => (int)$q['required_qty'],
                'rank' => isset($q['required_rank_name']) ? $q['required_rank_name'] : '?',
            ];
        }
        ksort($plans);
        foreach ($plans as &$opts) ksort($opts);
        return $plans;
    }

    /* ======================= RIGHT PANEL (cheap) ======================= */

    /**
     * Compact rank state for the shared right-hand panel.
     *
     * Rendered on ~14 pages, so this deliberately avoids the live tree walk:
     * it reads `user_ranks.group_volume`, which the hourly cron refreshes. The
     * page body uses the live figure; a small lag here is the right trade.
     *
     * Fail-soft: if the rank tables are missing (migration not yet run) it
     * returns a neutral, unranked state rather than fataling every page.
     */
    public function sidebar($user_id)
    {
        $user_id = (int)$user_id;
        $un = $this->_unrankedFallback();
        $out = [
            'available'       => false,
            'has_rank'        => false,
            'name'            => $un['name'] ?? 'UN RANK',
            'tier'            => null,
            'badge_image'     => $un['badge_image'] ?? null,
            'badge_color'     => $un['badge_color'] ?? '#9e9e9e',
            'next_rank'       => null,
            'progress'        => 0,
            'group_volume'    => '0',
            'required_volume' => '0',
            'power_rank'      => null,
            'power_qualified' => false,
            'days_left'       => null,
            'incentive'       => '0',
        ];

        try {
            if (!$this->db->table_exists('user_ranks')) return $out;
            $out['available'] = true;

            $row = $this->db->select('ur.group_volume, ur.highest_rank_id,
                                      r.name, r.tier_level, r.badge_image, r.badge_color')
                            ->from('user_ranks ur')
                            ->join('staking_ranks r', 'r.id = ur.highest_rank_id', 'left')
                            ->where('ur.user_id', $user_id)
                            ->get()->row_array();

            $tier = -1;
            if ($row && $row['name'] !== null) {
                $out['has_rank']     = true;
                $out['name']         = $row['name'];
                $out['tier']         = (int)$row['tier_level'];
                $out['badge_image']  = $row['badge_image'];
                $out['badge_color']  = $row['badge_color'] ?: '#6E56CF';
                $tier = (int)$row['tier_level'];
            }
            $out['group_volume'] = $row ? (string)$row['group_volume'] : '0';

            // Next rank up the ladder + volume progress toward it.
            $next = $this->db->where('is_active', 1)->where('tier_level >', $tier)
                             ->order_by('tier_level', 'ASC')->limit(1)
                             ->get('staking_ranks')->row_array();
            if ($next) {
                $need = (string)$next['required_group_volume'];
                $have = $out['group_volume'];
                $out['next_rank']       = $next['name'];
                $out['required_volume'] = $need;
                $out['progress'] = bccomp($need, '0', 8) > 0
                    ? (int)min(100, floor((float)bcmul(bcdiv($have, $need, 8), '100', 4)))
                    : 100;
            } else {
                $out['progress'] = 100;   // already CHALLENGER
            }

            // Power rank for the open cycle (2 cheap indexed lookups).
            $cycle = $this->db->where('status', 'open')->order_by('cycle_no', 'DESC')
                              ->get('staking_rank_power_cycles')->row_array();
            if ($cycle) {
                $out['days_left'] = max(0, (int)ceil(
                    (strtotime($cycle['end_date']) - strtotime(date('Y-m-d'))) / 86400));
                $p = $this->db->select('p.qualified, r.name, r.group_incentive')
                              ->from('user_rank_power p')
                              ->join('staking_ranks r', 'r.id = p.power_rank_id', 'left')
                              ->where('p.user_id', $user_id)
                              ->where('p.cycle_id', $cycle['id'])
                              ->get()->row_array();
                if ($p) {
                    $out['power_rank']      = $p['name'];
                    $out['power_qualified'] = !empty($p['qualified']);
                    $out['incentive']       = !empty($p['qualified']) ? (string)$p['group_incentive'] : '0';
                }
            }
        } catch (Throwable $e) {
            log_message('error', '[member_rank sidebar] ' . $e->getMessage());
        }
        return $out;
    }
}
