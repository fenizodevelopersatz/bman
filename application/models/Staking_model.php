<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Staking_model
 * -------------------------------------------------------------------
 * Packages / Plans / ROI matrix / Rank Achievement config for the BMAN
 * staking module. Reference: docs/6_STAKING_PACKAGES_PLANS_ROI.md.
 *
 * Business rules enforced here (doc §9):
 *  - ROI % >= 0; Fixed basis = total, Regular basis = monthly.
 *  - ROI edits are versioned (old row is_active=0, new effective-dated row)
 *    and every change writes staking_roi_audit.
 *  - Combo split must total 100.
 *  - A package with stakes cannot be deleted — only disabled.
 */
class Staking_model extends CI_Model
{
    /* =============================== PACKAGES =============================== */

    public function packages($active_only = false)
    {
        if ($active_only) $this->db->where('is_active', 1);
        return $this->db->order_by('sort_order','ASC')->order_by('stake_amount','ASC')
                        ->get('staking_packages')->result_array();
    }

    public function package($id)
    {
        return $this->db->get_where('staking_packages', ['id' => (int)$id])->row_array();
    }

    /** Insert or update. Returns [ok(bool), message|id]. */
    public function savePackage($data, $id = 0)
    {
        $row = [
            'name'          => trim((string)($data['name'] ?? '')),
            'stake_amount'  => (float)($data['stake_amount'] ?? 0),
            'bonus_percent' => (float)($data['bonus_percent'] ?? 25),
            'group_ceiling' => (float)($data['group_ceiling'] ?? 0),
            'sort_order'    => (int)($data['sort_order'] ?? 0),
        ];
        if ($row['name'] === '')          return [false, 'Package name is required.'];
        if ($row['stake_amount'] <= 0)    return [false, 'Stake amount must be greater than 0.'];
        if ($row['bonus_percent'] < 0)    return [false, 'Bonus % cannot be negative.'];
        if ($row['group_ceiling'] < 0)    return [false, 'Group ceiling cannot be negative.'];

        // stake_amount is unique — check against other rows
        $this->db->where('stake_amount', $row['stake_amount']);
        if ($id) $this->db->where('id !=', (int)$id);
        if ($this->db->count_all_results('staking_packages') > 0) {
            return [false, 'A package with this stake amount already exists.'];
        }

        if ($id) {
            $this->db->where('id', (int)$id)->update('staking_packages', $row);
            return [true, (int)$id];
        }
        if (isset($data['is_active'])) $row['is_active'] = (int)!!$data['is_active'];
        $this->db->insert('staking_packages', $row);
        return [true, (int)$this->db->insert_id()];
    }

    public function togglePackage($id, $active)
    {
        $this->db->where('id', (int)$id)->update('staking_packages', ['is_active' => (int)!!$active]);
        return $this->db->affected_rows() >= 0;
    }

    /** Delete is blocked while stakes reference the package (doc §9). */
    public function deletePackage($id)
    {
        $id = (int)$id;
        $stakes = $this->db->where('package_id', $id)->count_all_results('user_stakes');
        if ($stakes > 0) {
            return [false, 'Package has '.$stakes.' stake(s) — disable it instead of deleting.'];
        }
        $this->db->where('package_id', $id)->delete('staking_roi_structure');
        $this->db->where('id', $id)->delete('staking_packages');
        return [true, 'Package deleted.'];
    }

    public function reorderPackages(array $ids)
    {
        foreach (array_values($ids) as $i => $id) {
            $this->db->where('id', (int)$id)->update('staking_packages', ['sort_order' => $i + 1]);
        }
        return true;
    }

    /* ================================ PLANS ================================ */

    public function plans($active_only = false)
    {
        if ($active_only) $this->db->where('is_active', 1);
        $plans = $this->db->order_by('sort_order','ASC')->get('staking_plans')->result_array();
        $terms = $this->db->get('staking_plan_terms')->result_array();
        foreach ($plans as &$p) {
            $p['terms'] = array_values(array_filter($terms, function ($t) use ($p) {
                return (int)$t['plan_id'] === (int)$p['id'];
            }));
        }
        return $plans;
    }

    /** Update a plan's rule fields (withdraw rules, credit days, combo split). */
    public function savePlan($id, $data)
    {
        $plan = $this->db->get_where('staking_plans', ['id' => (int)$id])->row_array();
        if (!$plan) return [false, 'Plan not found.'];

        $row = [];
        foreach (['credit_days'] as $k) {
            if (array_key_exists($k, $data)) $row[$k] = trim((string)$data[$k]) ?: null;
        }
        foreach (['withdraw_frequency_days'] as $k) {
            if (array_key_exists($k, $data)) $row[$k] = max(0, (int)$data[$k]);
        }
        foreach (['min_withdraw_bman','max_withdraw_bman','min_withdraw_usdt','max_withdraw_usdt',
                  'combo_fixed_pct','combo_regular_pct'] as $k) {
            if (array_key_exists($k, $data) && $data[$k] !== '') {
                if ((float)$data[$k] < 0) return [false, str_replace('_',' ',$k).' cannot be negative.'];
                $row[$k] = (float)$data[$k];
            }
        }
        if (array_key_exists('withdraw_after_maturity', $data)) {
            $row['withdraw_after_maturity'] = (int)!!$data['withdraw_after_maturity'];
        }

        // credit_days must be comma-separated day numbers 1-31 (e.g. "5,15,25")
        if (!empty($row['credit_days'])) {
            foreach (explode(',', $row['credit_days']) as $d) {
                $d = trim($d);
                if (!ctype_digit($d) || (int)$d < 1 || (int)$d > 31) {
                    return [false, 'Credit days must be day numbers 1–31, comma separated (e.g. 5,15,25).'];
                }
            }
        }
        // min <= max guards
        if (isset($row['min_withdraw_bman'], $row['max_withdraw_bman'])
            && $row['min_withdraw_bman'] > $row['max_withdraw_bman']) {
            return [false, 'Min withdraw BMAN cannot exceed max.'];
        }
        if (isset($row['min_withdraw_usdt'], $row['max_withdraw_usdt'])
            && $row['min_withdraw_usdt'] > $row['max_withdraw_usdt']) {
            return [false, 'Min withdraw USDT cannot exceed max.'];
        }
        // Combo split must total 100 (doc §9)
        if ($plan['code'] === 'combo') {
            $f = isset($row['combo_fixed_pct'])   ? $row['combo_fixed_pct']   : (float)$plan['combo_fixed_pct'];
            $r = isset($row['combo_regular_pct']) ? $row['combo_regular_pct'] : (float)$plan['combo_regular_pct'];
            if (abs(($f + $r) - 100) > 0.001) {
                return [false, 'Combo split must total 100% (Fixed '.$f.' + Regular '.$r.' = '.($f+$r).').'];
            }
        }

        if ($row) $this->db->where('id', (int)$id)->update('staking_plans', $row);
        return [true, 'Plan updated.'];
    }

    public function togglePlan($id, $active)
    {
        $this->db->where('id', (int)$id)->update('staking_plans', ['is_active' => (int)!!$active]);
        return true;
    }

    /** Enable/disable the offered durations for a plan. $years e.g. [2,3,5]. */
    public function savePlanTerms($plan_id, array $years)
    {
        $plan_id = (int)$plan_id;
        $years   = array_filter(array_map('intval', $years)); // drop empty sentinel values
        $allowed = [2, 3, 5];
        foreach ($years as $y) {
            if (!in_array($y, $allowed, true)) return [false, 'Invalid duration: '.$y.' years.'];
        }
        foreach ($allowed as $y) {
            $on = in_array($y, $years, true) ? 1 : 0;
            $exists = $this->db->get_where('staking_plan_terms',
                        ['plan_id' => $plan_id, 'duration_years' => $y])->row_array();
            if ($exists) {
                $this->db->where('id', $exists['id'])->update('staking_plan_terms', ['is_active' => $on]);
            } elseif ($on) {
                $this->db->insert('staking_plan_terms',
                    ['plan_id' => $plan_id, 'duration_years' => $y, 'is_active' => 1]);
            }
        }
        return [true, 'Durations updated.'];
    }

    /* ============================== ROI MATRIX ============================== */

    /** Grid rows: one per package, cells keyed "fixed_2","regular_5", … */
    public function roiGrid()
    {
        $packages = $this->packages();
        $cells = $this->db->where('is_active', 1)->get('staking_roi_structure')->result_array();
        $map = [];
        foreach ($cells as $c) {
            $map[$c['package_id']][$c['plan_code'].'_'.$c['duration_years']] = $c;
        }
        foreach ($packages as &$p) {
            $p['roi'] = isset($map[$p['id']]) ? $map[$p['id']] : [];
        }
        return $packages;
    }

    /**
     * Versioned write of one matrix cell (doc §7.2):
     * deactivate the current row, insert a new effective-dated row, audit it.
     * Skips silently when the value is unchanged. Returns [ok, message].
     */
    public function saveRoiCell($package_id, $plan_code, $duration_years, $percent, $effective_from, $admin_id, $note = null)
    {
        $package_id = (int)$package_id;
        $duration_years = (int)$duration_years;
        $percent = (float)$percent;

        if (!in_array($plan_code, ['fixed','regular'], true)) return [false, 'Invalid plan.'];
        if (!in_array($duration_years, [2,3,5], true))        return [false, 'Invalid duration.'];
        if ($percent < 0)                                     return [false, 'ROI % must be >= 0.'];
        if (!$this->package($package_id))                     return [false, 'Package not found.'];
        $eff = date('Y-m-d', strtotime($effective_from ?: 'now'));

        $current = $this->db->get_where('staking_roi_structure', [
            'package_id' => $package_id, 'plan_code' => $plan_code,
            'duration_years' => $duration_years, 'is_active' => 1,
        ])->row_array();

        if ($current && abs((float)$current['roi_percent'] - $percent) < 0.0005) {
            return [true, 'unchanged'];
        }

        if ($current) {
            $this->db->where('id', $current['id'])->update('staking_roi_structure', ['is_active' => 0]);
        }
        $this->db->insert('staking_roi_structure', [
            'package_id'     => $package_id,
            'plan_code'      => $plan_code,
            'duration_years' => $duration_years,
            'roi_percent'    => $percent,
            'roi_basis'      => $plan_code === 'fixed' ? 'total' : 'monthly',
            'effective_from' => $eff,
            'is_active'      => 1,
            'created_by'     => (int)$admin_id,
        ]);
        $new_id = (int)$this->db->insert_id();

        $this->db->insert('staking_roi_audit', [
            'roi_id'         => $new_id,
            'package_id'     => $package_id,
            'plan_code'      => $plan_code,
            'duration_years' => $duration_years,
            'old_percent'    => $current ? $current['roi_percent'] : null,
            'new_percent'    => $percent,
            'changed_by'     => (int)$admin_id,
            'note'           => $note ? substr($note, 0, 255) : null,
        ]);
        return [true, 'saved'];
    }

    /**
     * Resolve the ROI cell a new stake must snapshot (doc §1 key rule).
     * For combo, returns both halves. NULL when the cell is missing/inactive.
     */
    public function resolveRoi($package_id, $plan_code, $duration_years)
    {
        if ($plan_code === 'combo') {
            $fixed   = $this->resolveRoi($package_id, 'fixed', $duration_years);
            $regular = $this->resolveRoi($package_id, 'regular', $duration_years);
            return ($fixed && $regular) ? ['fixed' => $fixed, 'regular' => $regular] : null;
        }
        return $this->db->where([
                    'package_id' => (int)$package_id, 'plan_code' => $plan_code,
                    'duration_years' => (int)$duration_years, 'is_active' => 1,
                ])
                ->where('effective_from <=', date('Y-m-d'))
                ->order_by('effective_from', 'DESC')
                ->get('staking_roi_structure')->row_array() ?: null;
    }

    /** Version history for one cell (newest first) or for everything. */
    public function roiHistory($package_id = 0, $plan_code = '', $duration_years = 0, $limit = 200)
    {
        $this->db->select('r.*, p.name AS package_name, p.stake_amount')
                 ->from('staking_roi_structure r')
                 ->join('staking_packages p', 'p.id = r.package_id', 'left');
        if ($package_id)     $this->db->where('r.package_id', (int)$package_id);
        if ($plan_code)      $this->db->where('r.plan_code', $plan_code);
        if ($duration_years) $this->db->where('r.duration_years', (int)$duration_years);
        return $this->db->order_by('r.created_at','DESC')->limit((int)$limit)
                        ->get()->result_array();
    }

    /** Audit log (who changed what, old -> new). */
    public function roiAudit($limit = 200)
    {
        return $this->db->select('a.*, p.name AS package_name, adm.admin_name AS admin_name')
                        ->from('staking_roi_audit a')
                        ->join('staking_packages p', 'p.id = a.package_id', 'left')
                        ->join('admin_members adm', 'adm.id = a.changed_by', 'left')
                        ->order_by('a.created_at','DESC')->limit((int)$limit)
                        ->get()->result_array();
    }

    /* ================================ RANKS ================================ */

    public function ranks($active_only = false)
    {
        if ($active_only) $this->db->where('is_active', 1);
        $ranks = $this->db->order_by('tier_level','ASC')->get('staking_ranks')->result_array();
        $reqs  = $this->db->where('is_active', 1)
                          ->order_by('plan_no','ASC')->order_by('option_no','ASC')->order_by('side','ASC')
                          ->get('staking_rank_requirements')->result_array();
        $names = array_column($ranks, 'name', 'id');
        foreach ($ranks as &$r) {
            $r['requirements'] = [];
            foreach ($reqs as $q) {
                if ((int)$q['rank_id'] !== (int)$r['id']) continue;
                $q['required_rank_name'] = isset($names[$q['required_rank_id']]) ? $names[$q['required_rank_id']] : '?';
                $r['requirements'][] = $q;
            }
        }
        return $ranks;
    }

    public function rank($id)
    {
        return $this->db->get_where('staking_ranks', ['id' => (int)$id])->row_array();
    }

    /** Update a rank's incentive/benefit fields (names & tiers are fixed by the proposal). */
    public function saveRank($id, $data)
    {
        $rank = $this->rank($id);
        if (!$rank) return [false, 'Rank not found.'];

        $row = [];
        if (array_key_exists('group_incentive', $data)) {
            if ((float)$data['group_incentive'] < 0) return [false, 'Group incentive cannot be negative.'];
            $row['group_incentive'] = (float)$data['group_incentive'];
        }
        if (array_key_exists('badge_color', $data)) {
            $row['badge_color'] = trim((string)$data['badge_color']) ?: null;
        }
        foreach (['benefit_badge','benefit_certificate','benefit_reward','benefit_recognition'] as $k) {
            if (array_key_exists($k, $data)) $row[$k] = (int)!!$data[$k];
        }
        if ($row) $this->db->where('id', (int)$id)->update('staking_ranks', $row);
        return [true, 'Rank updated.'];
    }

    public function toggleRank($id, $active)
    {
        $this->db->where('id', (int)$id)->update('staking_ranks', ['is_active' => (int)!!$active]);
        return true;
    }

    /**
     * Replace the requirement rows of one qualification plan of a rank.
     * $rows: [['option_no'=>1,'side'=>'left','required_qty'=>2,'required_rank_id'=>4], …]
     * An empty $rows clears the plan.
     */
    public function saveRankRequirements($rank_id, $plan_no, array $rows)
    {
        $rank_id = (int)$rank_id;
        $plan_no = (int)$plan_no;
        if (!$this->rank($rank_id))          return [false, 'Rank not found.'];
        if (!in_array($plan_no, [1,2,3], true)) return [false, 'Invalid plan number.'];

        $clean = [];
        foreach ($rows as $r) {
            $side = isset($r['side']) ? $r['side'] : '';
            $qty  = isset($r['required_qty']) ? (int)$r['required_qty'] : 0;
            $req  = isset($r['required_rank_id']) ? (int)$r['required_rank_id'] : 0;
            $opt  = isset($r['option_no']) ? max(1, (int)$r['option_no']) : 1;
            if (!in_array($side, ['left','right'], true)) return [false, 'Side must be left or right.'];
            if ($qty < 1)                                 return [false, 'Quantity must be at least 1.'];
            if (!$this->rank($req))                       return [false, 'Required rank not found.'];
            if ($req === $rank_id)                        return [false, 'A rank cannot require itself.'];
            $key = $opt.'_'.$side;
            if (isset($clean[$key])) return [false, 'Duplicate '.$side.' condition in option '.$opt.'.'];
            $clean[$key] = [
                'rank_id' => $rank_id, 'plan_no' => $plan_no, 'option_no' => $opt,
                'side' => $side, 'required_qty' => $qty, 'required_rank_id' => $req, 'is_active' => 1,
            ];
        }

        $this->db->trans_start();
        $this->db->where(['rank_id' => $rank_id, 'plan_no' => $plan_no])->delete('staking_rank_requirements');
        if ($clean) $this->db->insert_batch('staking_rank_requirements', array_values($clean));
        $this->db->trans_complete();
        return $this->db->trans_status() ? [true, 'Requirements saved.'] : [false, 'Database error.'];
    }

    /* ====================== RANK POWER (proposal §11) ====================== */
    /* Separate from Achievement Rank · resets every 60 days · controls
       group-incentive qualification. Admin configures the rules and manages
       the cycle; the per-user evaluation engine is the user-side phase. */

    public function powerSettings()
    {
        return $this->db->get_where('staking_rank_power_settings', ['id' => 1])->row_array();
    }

    public function savePowerSettings($data, $admin_id)
    {
        $row = [];
        if (array_key_exists('is_enabled', $data))   $row['is_enabled'] = (int)!!$data['is_enabled'];
        if (array_key_exists('cycle_days', $data)) {
            $d = (int)$data['cycle_days'];
            if ($d < 1 || $d > 365) return [false, 'Reset cycle must be 1–365 days.'];
            $row['cycle_days'] = $d;
        }
        if (array_key_exists('controls_group_incentive', $data)) {
            $row['controls_group_incentive'] = (int)!!$data['controls_group_incentive'];
        }
        if (array_key_exists('min_power_tier', $data)) {
            $t = (int)$data['min_power_tier'];
            if ($t < 0 || $t > 10) return [false, 'Minimum power tier must be 0–10.'];
            $row['min_power_tier'] = $t;
        }
        if (array_key_exists('auto_open_next_cycle', $data)) {
            $row['auto_open_next_cycle'] = (int)!!$data['auto_open_next_cycle'];
        }
        if (!$row) return [false, 'Nothing to save.'];
        $row['updated_by'] = (int)$admin_id;
        $this->db->where('id', 1)->update('staking_rank_power_settings', $row);
        return [true, 'Rank Power settings saved.'];
    }

    public function currentPowerCycle()
    {
        return $this->db->where('status', 'open')->order_by('cycle_no', 'DESC')
                        ->get('staking_rank_power_cycles')->row_array() ?: null;
    }

    public function powerCycles($limit = 50)
    {
        $cycles = $this->db->order_by('cycle_no', 'DESC')->limit((int)$limit)
                           ->get('staking_rank_power_cycles')->result_array();
        foreach ($cycles as &$c) {
            $c['user_count'] = $this->db->where('cycle_id', $c['id'])->count_all_results('user_rank_power');
        }
        return $cycles;
    }

    /**
     * Reset (§11): close the open cycle and open the next one starting today,
     * ending after cycle_days. Power ranks reset implicitly — user_rank_power
     * rows belong to the closed cycle; the new cycle starts empty.
     */
    public function resetPowerCycle($admin_id)
    {
        $settings = $this->powerSettings();
        if (!$settings) return [false, 'Rank Power settings missing — run db/staking_rank_power.sql.'];

        $open = $this->currentPowerCycle();
        $this->db->trans_start();
        if ($open) {
            $this->db->where('id', $open['id'])->update('staking_rank_power_cycles', [
                'status' => 'closed', 'closed_at' => date('Y-m-d H:i:s'),
            ]);
        }
        $last_no = (int)$this->db->select_max('cycle_no')->get('staking_rank_power_cycles')->row()->cycle_no;
        $this->db->insert('staking_rank_power_cycles', [
            'cycle_no'   => $last_no + 1,
            'start_date' => date('Y-m-d'),
            'end_date'   => date('Y-m-d', strtotime('+'.(int)$settings['cycle_days'].' days')),
            'status'     => 'open',
            'opened_by'  => (int)$admin_id,
        ]);
        $this->db->trans_complete();
        if (!$this->db->trans_status()) return [false, 'Database error.'];
        return [true, 'Cycle #'.($last_no + 1).' opened ('.$settings['cycle_days'].' days). '
                     .($open ? 'Cycle #'.$open['cycle_no'].' closed — power ranks reset.' : 'First cycle started.')];
    }

    /* ========== BONUS COIN (§7) & BINARY MATCHING BONUS (§9) ============== */

    public function bonusSettings()
    {
        return $this->db->get_where('staking_bonus_settings', ['id' => 1])->row_array();
    }

    public function saveBonusSettings($data, $admin_id)
    {
        $row = [];

        // §7 staking bonus default %
        if (array_key_exists('bonus_percent_default', $data)) {
            $v = (float)$data['bonus_percent_default'];
            if ($v < 0 || $v > 100) return [false, 'Staking bonus % must be 0–100.'];
            $row['bonus_percent_default'] = $v;
        }

        // §7 reduction rule (every N days, X% of bonus wallet reduced)
        if (array_key_exists('reduction_enabled', $data)) $row['reduction_enabled'] = (int)!!$data['reduction_enabled'];
        if (array_key_exists('reduction_interval_days', $data)) {
            $d = (int)$data['reduction_interval_days'];
            if ($d < 1 || $d > 365) return [false, 'Reduction interval must be 1–365 days.'];
            $row['reduction_interval_days'] = $d;
        }
        if (array_key_exists('reduction_percent', $data)) {
            $v = (float)$data['reduction_percent'];
            if ($v < 0 || $v > 100) return [false, 'Reduction % must be 0–100.'];
            $row['reduction_percent'] = $v;
        }

        // §7 transfer rule (direct left/right only + security)
        foreach (['transfer_enabled','transfer_to_direct_left','transfer_to_direct_right',
                  'transfer_require_email_otp','transfer_require_transfer_password'] as $k) {
            if (array_key_exists($k, $data)) $row[$k] = (int)!!$data[$k];
        }
        $t_on    = array_key_exists('transfer_enabled', $row) ? $row['transfer_enabled'] : null;
        $t_left  = array_key_exists('transfer_to_direct_left', $row) ? $row['transfer_to_direct_left'] : null;
        $t_right = array_key_exists('transfer_to_direct_right', $row) ? $row['transfer_to_direct_right'] : null;
        if ($t_on === 1 && $t_left === 0 && $t_right === 0) {
            return [false, 'Transfer is enabled but no recipient side is allowed — allow left, right or disable transfers.'];
        }

        // §9 matching bonus: earning + staking must equal the total
        $cur = $this->bonusSettings();
        $tot = array_key_exists('matching_total_percent', $data)   ? (float)$data['matching_total_percent']   : (float)$cur['matching_total_percent'];
        $ear = array_key_exists('matching_earning_percent', $data) ? (float)$data['matching_earning_percent'] : (float)$cur['matching_earning_percent'];
        $stk = array_key_exists('matching_staking_percent', $data) ? (float)$data['matching_staking_percent'] : (float)$cur['matching_staking_percent'];
        if ($tot < 0 || $tot > 100 || $ear < 0 || $stk < 0) return [false, 'Matching percentages must be 0–100.'];
        if (abs(($ear + $stk) - $tot) > 0.001) {
            return [false, 'Matching split must equal the total ('.$ear.' + '.$stk.' = '.($ear+$stk).', total '.$tot.').'];
        }
        if (array_key_exists('matching_total_percent', $data))   $row['matching_total_percent'] = $tot;
        if (array_key_exists('matching_earning_percent', $data)) $row['matching_earning_percent'] = $ear;
        if (array_key_exists('matching_staking_percent', $data)) $row['matching_staking_percent'] = $stk;

        if (!$row) return [false, 'Nothing to save.'];
        $row['updated_by'] = (int)$admin_id;
        $this->db->where('id', 1)->update('staking_bonus_settings', $row);
        return [true, 'Bonus & matching settings saved.'];
    }

    /** Push the global default bonus % onto every package (§7 convenience). */
    public function applyBonusDefaultToPackages()
    {
        $s = $this->bonusSettings();
        if (!$s) return [false, 'Settings missing — run db/staking_bonus_settings.sql.'];
        $this->db->update('staking_packages', ['bonus_percent' => (float)$s['bonus_percent_default']]);
        return [true, 'All packages set to '.(float)$s['bonus_percent_default'].'% bonus.'];
    }

    /* ================= GROUP INCENTIVE CEILING (proposal §12) ============== */

    /**
     * Bulk update of stake → ceiling. $rows: [package_id => ceiling].
     * Ceiling values live on staking_packages.group_ceiling.
     */
    public function saveCeilings(array $rows)
    {
        $updated = 0;
        foreach ($rows as $pid => $ceiling) {
            if ($ceiling === '' || $ceiling === null) continue;
            if ((float)$ceiling < 0) return [false, 'Ceiling cannot be negative.'];
            $pkg = $this->package((int)$pid);
            if (!$pkg) return [false, 'Package #'.(int)$pid.' not found.'];
            if (abs((float)$pkg['group_ceiling'] - (float)$ceiling) < 0.00005) continue;
            $this->db->where('id', (int)$pid)
                     ->update('staking_packages', ['group_ceiling' => (float)$ceiling]);
            $updated++;
        }
        return [true, $updated ? $updated.' ceiling(s) updated.' : 'No values changed.'];
    }
}
