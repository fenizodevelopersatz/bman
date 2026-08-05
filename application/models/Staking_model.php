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
            'is_special'    => (int)!!($data['is_special'] ?? 0),
        ];
        if ($row['name'] === '')          return [false, 'Package name is required.'];
        if ($row['stake_amount'] <= 0)    return [false, 'Stake amount must be greater than 0.'];
        if ($row['bonus_percent'] < 0)    return [false, 'Bonus % cannot be negative.'];
        if ($row['group_ceiling'] < 0)    return [false, 'Group ceiling cannot be negative.'];

        // A stake amount is unique WITHIN its kind, not globally: the same amount
        // may exist once as a normal package and once as a special one — e.g.
        // 2,000 BMAN normal alongside 2,000 BMAN special. What stays blocked is
        // two normals, or two specials, on the same amount. This mirrors the
        // composite UNIQUE KEY uq_amount_special (stake_amount, is_special); see
        // db/2026-07-30_package_amount_special_unique.sql.
        $this->db->where('stake_amount', $row['stake_amount'])
                 ->where('is_special', $row['is_special']);
        if ($id) $this->db->where('id !=', (int)$id);
        if ($this->db->count_all_results('staking_packages') > 0) {
            return [false, $row['is_special']
                ? 'A SPECIAL package with this stake amount already exists.'
                : 'A normal package with this stake amount already exists.'];
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
            $p['terms'] = array_values(array_filter($terms, function ($t) use ($p, $active_only) {
                if ((int)$t['plan_id'] !== (int)$p['id']) return false;
                // Respect each duration's own "offered" checkbox too, not just
                // the plan-level toggle — a plan can be active with only some
                // of its 2/3/5-year terms enabled.
                if ($active_only && (int)($t['is_active'] ?? 1) !== 1) return false;
                return true;
            }));
        }
        return $plans;
    }

    /** Update a plan's rule fields (withdraw rules, credit days, combo split). */
    public function savePlan($id, $data, $admin_id = 0)
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

        if ($row) {
            foreach ($row as $field => $newVal) {
                $oldVal = $plan[$field] ?? null;
                if ((string)$oldVal === (string)$newVal) continue;
                $this->db->insert('admin_settings_audit', [
                    'module'     => 'staking_plans',
                    'field_name' => $plan['name'].' — '.$field,
                    'old_value'  => $oldVal === null ? null : (string)$oldVal,
                    'new_value'  => (string)$newVal,
                    'changed_by' => (int)$admin_id,
                ]);
            }
            $this->db->where('id', (int)$id)->update('staking_plans', $row);
        }
        return [true, 'Plan updated.'];
    }

    public function togglePlan($id, $active, $admin_id = 0)
    {
        $plan = $this->db->get_where('staking_plans', ['id' => (int)$id])->row_array();
        $new  = (int)!!$active;
        if ($plan && (int)$plan['is_active'] !== $new) {
            $this->db->insert('admin_settings_audit', [
                'module'     => 'staking_plans',
                'field_name' => ($plan['name'] ?? ('#'.$id)).' — is_active',
                'old_value'  => (string)$plan['is_active'],
                'new_value'  => (string)$new,
                'changed_by' => (int)$admin_id,
            ]);
        }
        $this->db->where('id', (int)$id)->update('staking_plans', ['is_active' => $new]);
        return true;
    }

    /** Enable/disable the offered durations for a plan. $years e.g. [2,3,5]. */
    public function savePlanTerms($plan_id, array $years, $admin_id = 0)
    {
        $plan_id = (int)$plan_id;
        $plan    = $this->db->get_where('staking_plans', ['id' => $plan_id])->row_array();
        $planName = $plan['name'] ?? ('#'.$plan_id);
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
                if ((int)$exists['is_active'] !== $on) {
                    $this->db->insert('admin_settings_audit', [
                        'module'     => 'staking_plans',
                        'field_name' => $planName.' — '.$y.'y duration',
                        'old_value'  => (string)$exists['is_active'],
                        'new_value'  => (string)$on,
                        'changed_by' => (int)$admin_id,
                    ]);
                    $this->db->where('id', $exists['id'])->update('staking_plan_terms', ['is_active' => $on]);
                }
            } elseif ($on) {
                $this->db->insert('admin_settings_audit', [
                    'module'     => 'staking_plans',
                    'field_name' => $planName.' — '.$y.'y duration',
                    'old_value'  => '0',
                    'new_value'  => '1',
                    'changed_by' => (int)$admin_id,
                ]);
                $this->db->insert('staking_plan_terms',
                    ['plan_id' => $plan_id, 'duration_years' => $y, 'is_active' => 1]);
            }
        }
        return [true, 'Durations updated.'];
    }

    public function stakingPlansAuditLog($limit = 200)
    {
        return $this->db->select('a.*, adm.admin_name')
                        ->from('admin_settings_audit a')
                        ->join('admin_members adm', 'adm.id = a.changed_by', 'left')
                        ->where('a.module', 'staking_plans')
                        ->order_by('a.created_at', 'DESC')->limit((int)$limit)
                        ->get()->result_array();
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

    /**
     * Update a rank's incentive / volume / reward / benefit fields.
     * Names & tiers are fixed by the proposal and are not editable.
     *
     * required_group_volume is the downline BMAN needed to HOLD the rank;
     * group_incentive is the amount PAID at that rank. They are different
     * numbers that happen to share a seed value — do not conflate them.
     *
     * Every change is audited (staking_rank_audit) because rank config drives
     * money.
     */
    public function saveRank($id, $data, $admin_id = null)
    {
        $rank = $this->rank($id);
        if (!$rank) return [false, 'Rank not found.'];

        $row = [];
        if (array_key_exists('group_incentive', $data)) {
            if ((float)$data['group_incentive'] < 0) return [false, 'Group incentive cannot be negative.'];
            $row['group_incentive'] = (float)$data['group_incentive'];
        }
        if (array_key_exists('required_group_volume', $data)) {
            if ((float)$data['required_group_volume'] < 0) {
                return [false, 'Required group volume cannot be negative.'];
            }
            $row['required_group_volume'] = (float)$data['required_group_volume'];
        }
        if (array_key_exists('reward_bman', $data)) {
            if ((float)$data['reward_bman'] < 0) return [false, 'BMAN reward cannot be negative.'];
            $row['reward_bman'] = (float)$data['reward_bman'];
        }
        if (array_key_exists('reward_usdt', $data)) {
            if ((float)$data['reward_usdt'] < 0) return [false, 'USDT reward cannot be negative.'];
            $row['reward_usdt'] = (float)$data['reward_usdt'];
        }
        if (array_key_exists('reward_description', $data)) {
            $row['reward_description'] = trim((string)$data['reward_description']) ?: null;
        }
        if (array_key_exists('badge_image', $data)) {
            $row['badge_image'] = trim((string)$data['badge_image']) ?: null;
        }
        if (array_key_exists('badge_color', $data)) {
            $row['badge_color'] = trim((string)$data['badge_color']) ?: null;
        }
        foreach (['benefit_badge','benefit_certificate','benefit_reward','benefit_recognition'] as $k) {
            if (array_key_exists($k, $data)) $row[$k] = (int)!!$data[$k];
        }
        if (!$row) return [true, 'Nothing to change.'];

        $this->db->where('id', (int)$id)->update('staking_ranks', $row);

        $this->_auditRankChange($rank, $row, $admin_id);
        return [true, 'Rank updated.'];
    }

    /** Fields compared as numbers; everything else compares as a string. */
    private $rank_numeric_fields = [
        'group_incentive', 'required_group_volume', 'reward_bman', 'reward_usdt',
        'benefit_badge', 'benefit_certificate', 'benefit_reward', 'benefit_recognition',
    ];

    /**
     * Record what actually changed, field by field, for the audit page.
     *
     * Numeric fields compare as floats so 500 vs "500.00000000" is not logged as
     * a change. Text fields (reward_description, badge_color, badge_image) MUST
     * compare as strings — casting them to float makes every distinct string
     * equal 0, which silently swallowed the change.
     */
    private function _auditRankChange($rank, $row, $admin_id)
    {
        $this->load->model('staking/Rankaudit_model', 'rankaudit');
        foreach ($row as $field => $new) {
            $old = isset($rank[$field]) ? $rank[$field] : null;

            if (in_array($field, $this->rank_numeric_fields, true)) {
                if ((string)(float)$old === (string)(float)$new) continue;
            } else {
                if ((string)$old === (string)$new) continue;
            }

            $this->rankaudit->log('rank_config_changed', [
                'rank_id'    => (int)$rank['id'],
                'old_value'  => $old === null ? '(none)' : (string)$old,
                'new_value'  => $new === null ? '(none)' : (string)$new,
                'changed_by' => $admin_id,
                'note'       => $rank['name'] . ' · ' . $field,
            ]);
        }
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
        $before = $this->bonusSettings(); // snapshot for the audit diff below

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

        foreach ($row as $field => $newVal) {
            if ($field === 'updated_by') continue;
            $oldVal = $before[$field] ?? null;
            if ((string)$oldVal === (string)$newVal) continue; // unchanged, skip
            $this->db->insert('admin_settings_audit', [
                'module' => 'bonus_settings',
                'field_name' => $field,
                'old_value' => $oldVal === null ? null : (string)$oldVal,
                'new_value' => (string)$newVal,
                'changed_by' => (int)$admin_id,
            ]);
        }

        return [true, 'Bonus & matching settings saved.'];
    }

    public function bonusAuditLog($limit = 200)
    {
        return $this->db->select('a.*, adm.admin_name')
                        ->from('admin_settings_audit a')
                        ->join('admin_members adm', 'adm.id = a.changed_by', 'left')
                        ->where('a.module', 'bonus_settings')
                        ->order_by('a.created_at', 'DESC')->limit((int)$limit)
                        ->get()->result_array();
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

    /* =================== STAKING PACKAGE PURCHASE (2026-07) ================= *
     * The ONLY place USDT→BMAN conversion happens. Everything runs in one MySQL
     * transaction. Order of effects (business rules §5–§9):
     *   validate → debit USDT → convert at admin rate → create stake →
     *   credit LOCKED BMAN to Staking wallet → 25% Bonus → ROI schedule →
     *   binary business volume → activate. Complete audit via wallet_ledger.  */

    /**
     * @param array $ctx  user_id, package_id, plan_code(fixed|regular|combo),
     *                    duration_years(2|3|5), [tx_hash], [ip],
     *                    [skip_kyc] (admin/testing bypass).
     * @return array [true, ['stake_id'=>, 'usdt'=>, 'bman'=>, 'bonus'=>]] | [false, error]
     */
    public function purchaseStake(array $ctx)
    {
        $userId   = (int)($ctx['user_id'] ?? 0);
        $pkgId    = (int)($ctx['package_id'] ?? 0);
        $planCode = (string)($ctx['plan_code'] ?? '');
        $years    = (int)($ctx['duration_years'] ?? 0);
        $skipKyc  = !empty($ctx['skip_kyc']);
        $ip       = $ctx['ip'] ?? null;

        // ---- 1. account + KYC ----
        $user = $this->db->select('status, kyc_status')->get_where('users', ['id' => $userId])->row_array();
        if (!$user || (string)$user['status'] !== '1') return [false, 'Your account is not active.'];
        if (!$skipKyc && strtolower((string)($user['kyc_status'] ?? '')) !== 'approved')
            return [false, 'KYC must be approved before purchasing a stake.'];

        // ---- 2. package ----
        $pkg = $this->db->get_where('staking_packages', ['id' => $pkgId, 'is_active' => 1])->row_array();
        if (!$pkg) return [false, 'Selected package is not available.'];

        // ---- 3. plan + term ----
        if (!in_array($planCode, ['fixed','regular','combo'], true)) return [false, 'Invalid plan.'];
        if (!in_array($years, [2,3,5], true))                        return [false, 'Invalid term.'];
        $plan = $this->db->get_where('staking_plans', ['code' => $planCode, 'is_active' => 1])->row_array();
        if (!$plan) return [false, 'Selected plan is not available.'];
        $term = $this->db->get_where('staking_plan_terms',
            ['plan_id' => $plan['id'], 'duration_years' => $years, 'is_active' => 1])->row_array();
        if (!$term) return [false, ucfirst($planCode).' plan does not offer a '.$years.'-year term.'];

        // ---- 4. ROI cell(s) ----
        $roi = $this->resolveRoi($pkgId, $planCode, $years);
        if (!$roi) return [false, 'ROI is not configured for this package / plan / term.'];

        // ---- 5. exchange rate + USDT price of this BMAN package ----
        $this->load->model('Tokenmaster_model', 'tokens');
        $bman = (float)$pkg['stake_amount'];
        $usdt = $this->tokens->convertBmanToUsdt($bman);
        if ($usdt === null || $usdt <= 0) return [false, 'Exchange rate is not configured. Contact admin.'];
        $usdt = round($usdt, 8);

        // ---- 6. USDT wallet balance ----
        $this->load->model('Walletledger_model', 'L');
        $usdtBal = (float)$this->L->balance($userId, 'usdt');
        if ($usdtBal + 1e-8 < $usdt)
            return [false, 'Insufficient USDT balance. Need '.rtrim(rtrim(number_format($usdt,8,'.',''),'0'),'.').' USDT.'];

        $bonusPct   = (float)$pkg['bonus_percent'];
        $bonusBman  = round($bman * $bonusPct / 100, 4);
        $cfg        = $this->tokens->activeSettings();
        $treasury   = $cfg['treasury_wallet'] ?? '';
        $txHash     = !empty($ctx['tx_hash']) ? substr((string)$ctx['tx_hash'],0,120) : null;
        $start      = date('Y-m-d');
        $maturity   = date('Y-m-d', strtotime('+'.$years.' years'));
        $ref        = 'STK-'.date('Ymd').'-'.strtoupper(substr(bin2hex(random_bytes(4)),0,8));

        // header ROI snapshot (combo stores the fixed half as representative)
        if ($planCode === 'combo') { $hdrPct = (float)$roi['fixed']['roi_percent']; $hdrBasis = 'total'; }
        else                        { $hdrPct = (float)$roi['roi_percent'];        $hdrBasis = $roi['roi_basis']; }

        // ============================ TRANSACTION ============================
        $this->db->trans_begin();

        // 6a. debit USDT (payment routed to Treasury wallet)
        list($okD, $rD) = $this->L->debit($userId, 'usdt', $usdt, 'stake_purchase', [
            'reference_id' => $ref, 'tx_hash' => $txHash,
            'description'  => 'Stake purchase '.number_format($bman).' BMAN ('.$planCode.'/'.$years.'y) → Treasury '.($treasury ?: 'n/a').' ['.$ref.']',
        ]);
        if (!$okD) { $this->db->trans_rollback(); return [false, $rD]; }

        // 6a-ii. record the USDT payment routed to the Admin Treasury Wallet
        //        (admin-facing "money received" ledger; user debit is above).
        if ($this->db->table_exists('staking_treasury_payments')) {
            $this->db->insert('staking_treasury_payments', [
                'user_id' => $userId, 'stake_id' => 0, 'ref' => $ref,
                'usdt_amount' => $usdt, 'bman_amount' => $bman,
                'exchange_rate' => (float)($cfg['exchange_rate'] ?? 0),
                'exchange_type' => $cfg['exchange_type'] ?? 'usdt_to_bman',
                'treasury_wallet' => $treasury ?: null, 'tx_hash' => $txHash,
            ]);
            $treasuryPayId = (int)$this->db->insert_id();
        } else { $treasuryPayId = 0; }

        // 6b. create the stake order
        $this->db->insert('user_stakes', [
            'user_id' => $userId, 'package_id' => $pkgId, 'plan_id' => (int)$plan['id'],
            'plan_code' => $planCode, 'duration_years' => $years,
            'stake_amount' => $bman, 'roi_percent' => $hdrPct, 'roi_basis' => $hdrBasis,
            'bonus_amount' => $bonusBman, 'start_date' => $start, 'maturity_date' => $maturity,
            'status' => 'active',
        ]);
        $stakeId = (int)$this->db->insert_id();
        if (!$stakeId) { $this->db->trans_rollback(); return [false, 'Could not create the stake order.']; }
        if (!empty($treasuryPayId)) {
            $this->db->where('id', $treasuryPayId)->update('staking_treasury_payments', ['stake_id' => $stakeId]);
        }

        // 6c. credit LOCKED BMAN into the Staking wallet
        list($okS) = $this->L->credit($userId, 'staking', $bman, 'stake_purchase', [
            'reference_id' => $ref,
            'description'  => 'Locked '.number_format($bman).' BMAN — stake #'.$stakeId,
            'maturity_date' => $maturity,
            'is_matured'    => 0,
        ]);
        if (!$okS) { $this->db->trans_rollback(); return [false, 'Could not credit the Staking wallet.']; }

        // 6d. 25% Bonus Coin → Bonus wallet
        if ($bonusBman > 0) {
            list($okB) = $this->L->credit($userId, 'bonus', $bonusBman, 'bonus', [
                'reference_id' => $ref,
                'description'  => number_format($bonusPct,0).'% staking bonus — stake #'.$stakeId,
                'skip_maturity'=> true,
            ]);
            if (!$okB) { $this->db->trans_rollback(); return [false, 'Could not credit the Bonus wallet.']; }
        }

        // 6e. ROI schedule — on the one real, scheduled system
        // (roi_staking_management), linked via user_stakes_id since this path
        // has no staking_swap_orders row. Previously wrote to
        // staking_roi_payouts, whose only consumer is unreachable from any
        // live schedule — that schedule looked correct but never paid.
        $this->load->model('staking/StakingLifecycle_model', 'lifecycle');
        $fixedPct   = $planCode === 'combo' ? (float)$roi['fixed']['roi_percent']   : ($planCode === 'fixed'   ? (float)$roi['roi_percent'] : 0);
        $monthlyPct = $planCode === 'combo' ? (float)$roi['regular']['roi_percent'] : ($planCode === 'regular' ? (float)$roi['roi_percent'] : 0);
        $this->lifecycle->createRoiRecord($ref, $userId, $planCode, [
            'principal_amount' => $bman,
            'fixed_percent'    => $fixedPct,
            'monthly_percent'  => $monthlyPct,
            'duration_years'   => $years,
            'created_at'       => date('Y-m-d H:i:s'),
            'maturity_date'    => $maturity,
        ], null, $stakeId);

        // 6f. binary business volume (consumed by the rank / matching cron)
        if ($this->db->table_exists('binary_volume_ledger')) {
            $this->db->insert('binary_volume_ledger', [
                'user_id' => $userId, 'invest_id' => $stakeId,
                'pv' => 0, 'bv' => $bman, 'source_amount' => $bman,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        if ($this->db->trans_status() === false) { $this->db->trans_rollback(); return [false, 'Database error — purchase rolled back.']; }
        $this->db->trans_commit();

        return [true, [
            'stake_id' => $stakeId, 'ref' => $ref,
            'usdt' => $usdt, 'bman' => $bman, 'bonus' => $bonusBman,
            'maturity' => $maturity,
        ]];
    }

    /**
     * Re-stake using EXISTING internal wallet balances — coin_distribution_
     * options 2-7 only. No USDT payment, no blockchain leg: the package's
     * BMAN is funded entirely by debiting the required share out of
     * Exchange/Earning/Staking/Bonus per the chosen option's percentages
     * (Walletledger_model::debit() itself refuses an overdraft, so it's the
     * authoritative sufficiency check — the controller's pre-check is only a
     * fast, friendly error shown before this point). Nothing is credited
     * back into those 4 wallets for the principal — the debited BMAN simply
     * *becomes* the new stake, tracked by the user_stakes row itself (which
     * is exactly what Staking_model::lockWalletBalance()/DashboardStats_
     * model::stakingAnalytics() already sum for the Lock Wallet). Only the
     * 25% package bonus is a genuine credit, since that's new money the
     * platform grants, not a re-allocation of what the user already had.
     * Option 1 (100% Exchange, real USDT->BMAN purchase) is NOT handled
     * here — see purchaseStake() / Lendingcontroller::swap_purchase().
     */
    public function restakeFromWallets(array $ctx)
    {
        $userId       = (int)($ctx['user_id'] ?? 0);
        $pkgId        = (int)($ctx['package_id'] ?? 0);
        $planCode     = (string)($ctx['plan_code'] ?? '');
        $years        = (int)($ctx['duration_years'] ?? 0);
        $distOptionId = (int)($ctx['distribution_option_id'] ?? 0);
        $skipKyc      = !empty($ctx['skip_kyc']);

        // ---- 1. account + KYC ----
        $user = $this->db->select('status, kyc_status')->get_where('users', ['id' => $userId])->row_array();
        if (!$user || (string)$user['status'] !== '1') return [false, 'Your account is not active.'];
        if (!$skipKyc && strtolower((string)($user['kyc_status'] ?? '')) !== 'approved')
            return [false, 'KYC must be approved before purchasing a stake.'];

        // ---- 2. package ----
        $pkg = $this->db->get_where('staking_packages', ['id' => $pkgId, 'is_active' => 1])->row_array();
        if (!$pkg) return [false, 'Selected package is not available.'];

        // ---- 3. plan + term ----
        if (!in_array($planCode, ['fixed','regular','combo'], true)) return [false, 'Invalid plan.'];
        if (!in_array($years, [2,3,5], true))                        return [false, 'Invalid term.'];
        $plan = $this->db->get_where('staking_plans', ['code' => $planCode, 'is_active' => 1])->row_array();
        if (!$plan) return [false, 'Selected plan is not available.'];
        $term = $this->db->get_where('staking_plan_terms',
            ['plan_id' => $plan['id'], 'duration_years' => $years, 'is_active' => 1])->row_array();
        if (!$term) return [false, ucfirst($planCode).' plan does not offer a '.$years.'-year term.'];

        // ---- 4. ROI cell(s) ----
        $roi = $this->resolveRoi($pkgId, $planCode, $years);
        if (!$roi) return [false, 'ROI is not configured for this package / plan / term.'];

        // ---- 5. distribution option (2-7 only) ----
        if ($distOptionId < 2 || $distOptionId > 7) return [false, 'Invalid distribution option for re-staking.'];
        $distOption = $this->db->get_where('coin_distribution_options', ['id' => $distOptionId, 'status' => 1])->row_array();
        if (!$distOption) return [false, 'Selected distribution option is not available.'];

        $bman = (float)$pkg['stake_amount'];
        $shares = [];
        foreach (['exchange', 'earning', 'staking', 'bonus'] as $wallet) {
            $pct = (float)($distOption[$wallet . '_percentage'] ?? 0);
            if ($pct > 0) $shares[$wallet] = round($bman * $pct / 100, 4);
        }
        if (!$shares) return [false, 'This distribution option allocates nothing — nothing to re-stake.'];
        // Defensive: admin-configured percentages should sum to 100%; catch a
        // misconfigured option rather than silently locking the wrong amount.
        if (abs(array_sum($shares) - $bman) > 0.01) {
            return [false, 'This distribution option is misconfigured (percentages do not total 100%) — contact admin.'];
        }

        $bonusPct  = (float)$pkg['bonus_percent'];
        $bonusBman = round($bman * $bonusPct / 100, 4);
        $start     = date('Y-m-d');
        $maturity  = date('Y-m-d', strtotime('+'.$years.' years'));
        $ref       = 'RESTAKE-'.date('Ymd').'-'.strtoupper(substr(bin2hex(random_bytes(4)),0,8));

        // Snapshot is_special at purchase time — never re-priced by a later
        // admin edit (same principle as the swap-path mirror in
        // StakingPurchasecron::_checkAndCompleteOrder()).
        $hasSpecialCol = $this->db->field_exists('is_special', 'user_stakes')
            && $this->db->field_exists('is_special', 'staking_packages');
        $isSpecial = $hasSpecialCol ? (int)($pkg['is_special'] ?? 0) : 0;

        // header ROI snapshot (combo stores the fixed half as representative)
        if ($planCode === 'combo') { $hdrPct = (float)$roi['fixed']['roi_percent']; $hdrBasis = 'total'; }
        else                        { $hdrPct = (float)$roi['roi_percent'];        $hdrBasis = $roi['roi_basis']; }

        $this->load->model('Walletledger_model', 'L');

        // ============================ TRANSACTION ============================
        $this->db->trans_begin();

        // 1. Debit the required share from each source wallet.
        foreach ($shares as $wallet => $amount) {
            list($okDebit, $msg) = $this->L->debit($userId, $wallet, $amount, 'stake_purchase', [
                'reference_id' => $ref,
                'description'  => 'Re-stake: '.number_format($amount, 4).' BMAN from '.ucfirst($wallet).' ('.$distOption['option_name'].')',
            ]);
            if (!$okDebit) { $this->db->trans_rollback(); return [false, ucfirst($wallet).' wallet: '.$msg]; }
        }

        // 2. Create the stake order.
        $insertData = [
            'user_id' => $userId, 'package_id' => $pkgId, 'plan_id' => (int)$plan['id'],
            'plan_code' => $planCode, 'duration_years' => $years,
            'stake_amount' => $bman, 'roi_percent' => $hdrPct, 'roi_basis' => $hdrBasis,
            'bonus_amount' => $bonusBman, 'distribution_option_id' => $distOptionId,
            'start_date' => $start, 'maturity_date' => $maturity, 'status' => 'active',
            'chain_status' => 'confirmed',
        ];
        if ($hasSpecialCol) $insertData['is_special'] = $isSpecial;
        $this->db->insert('user_stakes', $insertData);
        $stakeId = (int)$this->db->insert_id();
        if (!$stakeId) { $this->db->trans_rollback(); return [false, 'Could not create the stake order.']; }

        // 3. 25% Bonus Coin → Bonus wallet (new money the platform grants).
        if ($bonusBman > 0) {
            list($okB) = $this->L->credit($userId, 'bonus', $bonusBman, 'bonus', [
                'reference_id' => $ref,
                'description'  => number_format($bonusPct, 0).'% staking bonus — stake #'.$stakeId,
                'skip_maturity'=> true,
            ]);
            if (!$okB) { $this->db->trans_rollback(); return [false, 'Could not credit the Bonus wallet.']; }
        }

        // 4. ROI schedule — on the one real, scheduled system
        // (roi_staking_management), linked via user_stakes_id since this path
        // has no staking_swap_orders row. Previously wrote to
        // staking_roi_payouts, whose only consumer is unreachable from any
        // live schedule — that schedule looked correct but never paid.
        $this->load->model('staking/StakingLifecycle_model', 'lifecycle');
        $fixedPct   = $planCode === 'combo' ? (float)$roi['fixed']['roi_percent']   : ($planCode === 'fixed'   ? (float)$roi['roi_percent'] : 0);
        $monthlyPct = $planCode === 'combo' ? (float)$roi['regular']['roi_percent'] : ($planCode === 'regular' ? (float)$roi['roi_percent'] : 0);
        $this->lifecycle->createRoiRecord($ref, $userId, $planCode, [
            'principal_amount' => $bman,
            'fixed_percent'    => $fixedPct,
            'monthly_percent'  => $monthlyPct,
            'duration_years'   => $years,
            'created_at'       => date('Y-m-d H:i:s'),
            'maturity_date'    => $maturity,
        ], null, $stakeId);

        // 5. binary business volume (consumed by the rank / matching cron).
        if ($this->db->table_exists('binary_volume_ledger')) {
            $this->db->insert('binary_volume_ledger', [
                'user_id' => $userId, 'invest_id' => $stakeId,
                'pv' => 0, 'bv' => $bman, 'source_amount' => $bman,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        if ($this->db->trans_status() === false) { $this->db->trans_rollback(); return [false, 'Database error — purchase rolled back.']; }
        $this->db->trans_commit();

        return [true, [
            'stake_id' => $stakeId, 'ref' => $ref, 'bman' => $bman, 'bonus' => $bonusBman,
            'maturity' => $maturity, 'wallet_deductions' => $shares,
            'distribution_option_name' => $distOption['option_name'],
        ]];
    }

    /**
     * Create the STAKE RECORD immediately for a purchase whose BMAN is being
     * acquired elsewhere (e.g. the on-chain swap credits the Exchange wallet).
     * This does NOT debit USDT or credit bonus — it only creates the user_stakes
     * row + ROI schedule so the purchase is visible in the portfolio at once.
     *
     * Lifecycle: create PROCESSING (lock=false, emit_volume=false) → after the
     * swap settles call activateStake() to lock BMAN (Exchange→Staking), emit
     * binary volume and flip to ACTIVE. On failure call cancelStake().
     *
     * $ctx: user_id, package_id, plan_code, duration_years, ref(optional),
     *       status('processing'|'active', default 'processing'),
     *       lock(bool, default false), emit_volume(bool, default false).
     * Returns [ok, data|message].
     */
    public function materializeStake(array $ctx)
    {
        $userId   = (int)($ctx['user_id'] ?? 0);
        $pkgId    = (int)($ctx['package_id'] ?? 0);
        $planCode = (string)($ctx['plan_code'] ?? '');
        $years    = (int)($ctx['duration_years'] ?? 0);
        $status   = in_array(($ctx['status'] ?? 'processing'), ['processing','active'], true) ? $ctx['status'] : 'processing';
        $doLock   = !empty($ctx['lock']);
        $emitVol  = !empty($ctx['emit_volume']);
        $ref      = !empty($ctx['ref']) ? substr((string)$ctx['ref'], 0, 40)
                    : ('STK-'.date('Ymd').'-'.strtoupper(substr(bin2hex(random_bytes(4)), 0, 8)));

        $pkg = $this->db->get_where('staking_packages', ['id' => $pkgId, 'is_active' => 1])->row_array();
        if (!$pkg) return [false, 'Selected package is not available.'];
        if (!in_array($planCode, ['fixed','regular','combo'], true)) return [false, 'Invalid plan.'];
        if (!in_array($years, [2,3,5], true))                        return [false, 'Invalid term.'];
        $plan = $this->db->get_where('staking_plans', ['code' => $planCode, 'is_active' => 1])->row_array();
        if (!$plan) return [false, 'Selected plan is not available.'];
        $term = $this->db->get_where('staking_plan_terms',
            ['plan_id' => $plan['id'], 'duration_years' => $years, 'is_active' => 1])->row_array();
        if (!$term) return [false, ucfirst($planCode).' plan does not offer a '.$years.'-year term.'];
        $roi = $this->resolveRoi($pkgId, $planCode, $years);
        if (!$roi) return [false, 'ROI is not configured for this package / plan / term.'];

        $bman      = (float)$pkg['stake_amount'];
        $bonusBman = round($bman * (float)$pkg['bonus_percent'] / 100, 4);
        $start     = date('Y-m-d');
        $maturity  = date('Y-m-d', strtotime('+'.$years.' years'));
        if ($planCode === 'combo') { $hdrPct = (float)$roi['fixed']['roi_percent']; $hdrBasis = 'total'; }
        else                        { $hdrPct = (float)$roi['roi_percent'];         $hdrBasis = $roi['roi_basis']; }

        $this->db->trans_begin();

        $this->db->insert('user_stakes', [
            'user_id' => $userId, 'package_id' => $pkgId, 'plan_id' => (int)$plan['id'],
            'plan_code' => $planCode, 'duration_years' => $years,
            'stake_amount' => $bman, 'roi_percent' => $hdrPct, 'roi_basis' => $hdrBasis,
            'bonus_amount' => $bonusBman, 'start_date' => $start, 'maturity_date' => $maturity,
            'status' => $status,
            // On-chain tracking (a purchase is backed by a real blockchain tx).
            'tx_hash'       => $ctx['tx_hash']       ?? null,
            'block_number'  => $ctx['block_number']  ?? null,
            'confirmations' => (int)($ctx['confirmations'] ?? 0),
            'gas_fee'       => $ctx['gas_fee']       ?? null,
            'network'       => $ctx['network']       ?? null,
            'chain_status'  => $ctx['chain_status']  ?? 'pending',
            'onchain_tx_id' => $ctx['onchain_tx_id'] ?? null,
            'swap_order_id' => $ctx['swap_order_id'] ?? null,
        ]);
        $stakeId = (int)$this->db->insert_id();
        if (!$stakeId) { $this->db->trans_rollback(); return [false, 'Could not create the stake order.']; }

        // Immediate PURCHASE HISTORY record (visible in wallet/staking history
        // right away, even while the blockchain confirmation is still pending).
        if ($this->db->table_exists('history')) {
            $this->db->insert('history', [
                'user_id' => $userId, 'amount' => 0, 'token_amount' => $bman,
                'type' => 'staking_purchase', 'history_date' => date('Y-m-d H:i:s'),
                'date' => date('Y-m-d H:i:s'), 'status' => '1', 'hash_id' => $ref,
                'invest_id' => $stakeId, 'coin_type' => 1,
                'description' => 'Staking purchase '.number_format($bman).' BMAN ('.$planCode.'/'.$years.'y) — '.strtoupper($status),
            ]);
        }

        if ($doLock && $bman > 0) {
            $this->load->model('Walletledger_model', 'L');
            list($okT, $rT) = $this->L->transfer($userId, $bman, 'exchange', 'staking', 'stake_purchase', [
                'reference_id' => $ref, 'description' => 'Lock '.number_format($bman).' BMAN — stake #'.$stakeId,
            ]);
            if (!$okT) { $this->db->trans_rollback(); return [false, 'Could not lock BMAN into Staking: '.$rT]; }
        }

        $this->_generateRoiSchedule($stakeId, $userId, $bman, $planCode, $years, $roi, $plan, $start, $maturity);

        if ($emitVol && $this->db->table_exists('binary_volume_ledger')) {
            $this->db->insert('binary_volume_ledger', [
                'user_id' => $userId, 'invest_id' => $stakeId,
                'pv' => 0, 'bv' => $bman, 'source_amount' => $bman, 'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        if ($this->db->trans_status() === false) { $this->db->trans_rollback(); return [false, 'Database error — stake rolled back.']; }
        $this->db->trans_commit();

        return [true, ['stake_id' => $stakeId, 'ref' => $ref, 'bman' => $bman,
                       'bonus' => $bonusBman, 'maturity' => $maturity, 'status' => $status]];
    }

    /**
     * Activate a PROCESSING stake once its funding has settled: lock the acquired
     * BMAN (Exchange→Staking), emit binary volume (once), and set status ACTIVE.
     * Idempotent-ish: skips the lock/volume if already active. Returns [ok, msg].
     */
    public function activateStake($stakeId, $ref = null)
    {
        $stakeId = (int)$stakeId;
        $s = $this->db->get_where('user_stakes', ['id' => $stakeId])->row_array();
        if (!$s) return [false, 'Stake not found.'];
        if ((string)$s['status'] === 'active') return [true, 'already active'];

        $userId = (int)$s['user_id'];
        $bman   = (float)$s['stake_amount'];
        $ref    = $ref ?: ('STK-ACT-'.$stakeId);

        $this->db->trans_begin();

        if ($bman > 0) {
            $this->load->model('Walletledger_model', 'L');
            list($okT, $rT) = $this->L->transfer($userId, $bman, 'exchange', 'staking', 'stake_purchase', [
                'reference_id' => $ref, 'description' => 'Lock '.number_format($bman).' BMAN — stake #'.$stakeId,
            ]);
            if (!$okT) { $this->db->trans_rollback(); return [false, 'Could not lock BMAN into Staking: '.$rT]; }
        }

        if ($this->db->table_exists('binary_volume_ledger')) {
            $already = $this->db->where(['invest_id' => $stakeId])->count_all_results('binary_volume_ledger');
            if ($already === 0) {
                $this->db->insert('binary_volume_ledger', [
                    'user_id' => $userId, 'invest_id' => $stakeId,
                    'pv' => 0, 'bv' => $bman, 'source_amount' => $bman, 'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        $this->db->where('id', $stakeId)->update('user_stakes', ['status' => 'active']);

        if ($this->db->trans_status() === false) { $this->db->trans_rollback(); return [false, 'Activation DB error.']; }
        $this->db->trans_commit();
        return [true, 'activated'];
    }

    /** Cancel a PROCESSING stake whose funding failed: drop its pending ROI rows. */
    public function cancelStake($stakeId, $reason = null)
    {
        $stakeId = (int)$stakeId;
        $s = $this->db->get_where('user_stakes', ['id' => $stakeId])->row_array();
        if (!$s) return [false, 'Stake not found.'];
        if ((string)$s['status'] === 'active') return [false, 'Cannot cancel an active stake here.'];

        $this->db->trans_begin();
        $this->db->where('stake_id', $stakeId)->where('status', 'pending')->delete('staking_roi_payouts');
        $this->db->where('id', $stakeId)->update('user_stakes', ['status' => 'cancelled', 'chain_status' => 'cancelled']);
        if ($this->db->trans_status() === false) { $this->db->trans_rollback(); return [false, 'Cancel DB error.']; }
        $this->db->trans_commit();
        return [true, 'cancelled'];
    }

    /**
     * Confirmation processor (cron): promote PROCESSING stakes to ACTIVE once
     * their on-chain settlement is confirmed. Signal per stake:
     *   - linked swap order 'completed'  → on-chain USDT settled → ACTIVE
     *   - linked swap order 'failed_*'   → cancel the stake
     *   - no linked order / DRYRUN hash  → activate (internal/simulated settle)
     * Returns a summary. Idempotent — already-active stakes are skipped.
     */
    public function confirmProcessingStakes($limit = 50)
    {
        $rows = $this->db->where('status', 'processing')->order_by('id', 'ASC')
                         ->limit((int)$limit)->get('user_stakes')->result_array();
        $activated = 0; $failed = 0; $pending = 0;
        foreach ($rows as $s) {
            $stakeId = (int)$s['id'];
            $orderId = (int)($s['swap_order_id'] ?? 0);
            $txHash  = $s['tx_hash'] ?? null;

            if ($orderId > 0) {
                $o   = $this->db->get_where('staking_swap_orders', ['id' => $orderId])->row_array();
                $ost = $o['status'] ?? '';
                if ($ost === 'completed') {
                    $this->db->where('id', $stakeId)->update('user_stakes', [
                        'chain_status' => 'confirmed',
                        'tx_hash'      => ($o['usdt_tx_hash'] ?? '') ?: $txHash,
                    ]);
                    list($ok) = $this->activateStake($stakeId);
                    $ok ? $activated++ : $failed++;
                } elseif (strpos($ost, 'failed') === 0) {
                    $this->cancelStake($stakeId, 'swap '.$ost);
                    $failed++;
                } else {
                    $pending++;
                }
            } else {
                // No on-chain order linked (internal or dry-run) → settle now.
                list($ok) = $this->activateStake($stakeId);
                if ($ok) { $this->db->where('id', $stakeId)->update('user_stakes', ['chain_status' => 'confirmed']); $activated++; }
                else $pending++;
            }
        }
        return ['checked' => count($rows), 'activated' => $activated, 'failed' => $failed, 'pending' => $pending];
    }

    /**
     * Build the ROI payout rows for a stake.
     *  - fixed  (total)   : one payout at maturity  = stake * pct%.
     *  - regular(monthly) : one payout per month    = stake * pct% (credited on
     *                       the plan's first credit day).
     *  - combo            : fixed on 50% of stake + regular on the other 50%.
     * Rows land in staking_roi_payouts (wallet 'earning', status 'pending').
     */
    private function _generateRoiSchedule($stakeId, $userId, $bman, $planCode, $years, $roi, $plan, $start, $maturity)
    {
        $rows = [];
        $creditDay = 1;
        if (!empty($plan['credit_days'])) {
            $parts = array_map('intval', array_filter(explode(',', $plan['credit_days'])));
            if ($parts) $creditDay = min($parts);
        }

        $addFixed = function ($amountBase, $pct) use (&$rows, $stakeId, $userId, $maturity) {
            $rows[] = ['stake_id'=>$stakeId,'user_id'=>$userId,
                'amount'=>round($amountBase * $pct / 100, 4),
                'credit_date'=>$maturity,'wallet'=>'earning','status'=>'pending',
                'created_at'=>date('Y-m-d H:i:s')];
        };
        $addRegular = function ($amountBase, $monthlyPct) use (&$rows, $stakeId, $userId, $years, $start, $creditDay) {
            $months = $years * 12;
            for ($m = 1; $m <= $months; $m++) {
                $d = date('Y-m-', strtotime($start.' +'.$m.' months'));
                $day = str_pad((string)$creditDay, 2, '0', STR_PAD_LEFT);
                $rows[] = ['stake_id'=>$stakeId,'user_id'=>$userId,
                    'amount'=>round($amountBase * $monthlyPct / 100, 4),
                    'credit_date'=>$d.$day,'wallet'=>'earning','status'=>'pending',
                    'created_at'=>date('Y-m-d H:i:s')];
            }
        };

        if ($planCode === 'fixed') {
            $addFixed($bman, (float)$roi['roi_percent']);
        } elseif ($planCode === 'regular') {
            $addRegular($bman, (float)$roi['roi_percent']);
        } else { // combo: 50/50 split
            $half = $bman / 2;
            $addFixed($half, (float)$roi['fixed']['roi_percent']);
            $addRegular($half, (float)$roi['regular']['roi_percent']);
        }

        if ($rows) $this->db->insert_batch('staking_roi_payouts', $rows);
        return count($rows);
    }

    /* ============================ LOCK WALLET ============================ *
     * Read-only, always-computed: SUM(stake_amount) across a user's staking
     * orders that are still active/processing AND have not yet reached
     * maturity. No separate balance is ever stored. Applies uniformly to
     * fixed/regular/combo and Special packages — the calculation never
     * branches on plan_code/is_special, only the display labels do. */

    /** Statuses still counted as "locked" — 'processing' is defensive: no live
     *  write path uses it today, but the ENUM supports it and nothing should
     *  silently miss it if it's ever used again. */
    private $lockWalletStatuses = ['active', 'processing'];

    /** Lock Wallet balance for one user: live SUM of principal not yet matured. */
    public function lockWalletBalance($userId)
    {
        $row = $this->db->select_sum('stake_amount', 's')
            ->where('user_id', (int)$userId)
            ->where_in('status', $this->lockWalletStatuses)
            ->where('maturity_date >', date('Y-m-d'))
            ->get('user_stakes')->row();
        return (float) ($row->s ?? 0);
    }
}
