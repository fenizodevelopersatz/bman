<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class BusinessReport extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library(['session', 'form_validation']);
        $this->load->helper(['url', 'security']);
        $this->load->database();

        if (!$this->session->userdata('logged_in'))
            redirect('admin/login');
    }

    public function index()
    {
        $data['title'] = 'Binary MLM Business Report';
        $data['card_title'] = 'Binary MLM Business Report';
        $data['cfg'] = $this->db->get_where('commission_config', ['id' => 1])->row();

        // active packages to choose from
        $data['packages'] = $this->db->select('id,package_name,bv,direct_commission,pair_commission,pair_commission_type,daily_max_pairs,matching_bonus_json')
            ->from('package_config')->where('status', '1')->order_by('package_name', 'asc')->get()->result();

        $this->load->view('admin/calculator/business_report', $data);
    }

    // keep your existing JS url: admin/binary-business-report/run
    public function run()
    {
        $this->simulate();
    }

    // Ajax: POST -> returns JSON
    public function simulate()
    {
        // ----- Inputs -----
        $currency = $this->input->post('currency', true) ?: 'USD';
        $levels = (int) $this->input->post('levels', true); // binary depth
        $package_id = (int) $this->input->post('package_id', true); // NEW

        // manual fallbacks (used only if no package selected OR missing data)
        $join_pv = (float) $this->input->post('join_pv', true);
        $add_pv = (float) $this->input->post('add_pv', true);
        $manual_sponsor = (float) $this->input->post('sponsor_pct', true);    // %
        $pair_ratio_in = $this->input->post('pair_ratio', true) ?: '1:1';   // 1:1,1:2,2:1
        $binary_type_in = $this->input->post('binary_type', true) ?: 'percent';
        $binary_percent_in = (float) $this->input->post('binary_percent', true);
        $binary_amount_in = (float) $this->input->post('binary_amount', true);

        $match_levels_in = (int) $this->input->post('match_levels', true);
        $match_percent_in = (float) $this->input->post('match_percent', true);

        $cap_value = (float) $this->input->post('cap_value', true);
        $cap_on_sponsor = $this->input->post('cap_sponsor') === '1';
        $cap_on_binary = $this->input->post('cap_binary') === '1';
        $cap_on_match = $this->input->post('cap_match') === '1';

        $admin_charges = (float) $this->input->post('admin_charges', true);
        $tds_pct = (float) $this->input->post('tds_pct', true);

        if ($levels < 1) {
            return $this->_out(false, 'Levels must be >= 1');
        }

        // ----- Global switches/ratio/type -----
        $cfg = $this->db->get_where('commission_config', ['id' => 1])->row();
        if (!$cfg)
            return $this->_out(false, 'Commission config missing');

        // validate / pick ratio (global)
        $pair_ratio = !empty($cfg->binary_pair_ratio) ? $cfg->binary_pair_ratio : $pair_ratio_in;
        if (!preg_match('/^(1:1|1:2|2:1)$/', $pair_ratio))
            $pair_ratio = '1:1';
        [$rL, $rR] = array_map('intval', explode(':', $pair_ratio));
        $weak_unit = min($rL, $rR);

        // ----- Package (if provided) -----
        $pkg = null;
        if ($package_id > 0) {
            $pkg = $this->db->select('id,package_name,bv,direct_commission,pair_commission,pair_commission_type,daily_max_pairs,matching_bonus_json')
                ->from('package_config')->where('id', $package_id)->where('status', '1')->get()->row();
            if (!$pkg)
                return $this->_out(false, 'Invalid package');
        }

        // ----- Base PV per member (use package BV if available; else manual PV) -----
        $pv_per_member = ($pkg && (float) $pkg->bv > 0) ? (float) $pkg->bv : max(0, $join_pv + $add_pv);

        // ----- Tree math -----
        $total_members = pow(2, $levels + 1) - 2; // excluding root
        $left_members = pow(2, $levels) - 1;
        $right_members = $left_members;

        $left_volume = $left_members * $pv_per_member;
        $right_volume = $right_members * $pv_per_member;

        $pairs_members = (int) floor(min($left_members / $rL, $right_members / $rR));

        // Apply package daily cap (per receiver) as a simple planner cap
        if ($pkg && (int) $pkg->daily_max_pairs > 0) {
            $pairs_members = min($pairs_members, (int) $pkg->daily_max_pairs);
        }

        $base_volume = $pairs_members * $weak_unit * $pv_per_member;

        // ----- Sponsor / Direct (only if enabled globally) -----
        $direct_enabled = (int) $cfg->direct_commission_status === 1;
        $direct_type = !empty($cfg->direct_commission_type) ? $cfg->direct_commission_type : 'percent';
        $direct_val = ($pkg && (float) $pkg->direct_commission > 0) ? (float) $pkg->direct_commission
            : max(0, $manual_sponsor);
        $direct_signups = 2; // planner assumption: 2 directs (L & R)

        $sponsor_bonus = 0.0;
        if ($direct_enabled && $direct_val > 0) {
            if ($direct_type === 'amount') {
                $sponsor_bonus = $direct_val * $direct_signups;
            } else {
                $sponsor_bonus = ($pv_per_member * $direct_signups) * ($direct_val / 100.0);
            }
        }

        // ----- Binary pair (only if enabled globally) -----
        $binary_enabled = (int) $cfg->binary_commission_status === 1;
        $pair_type = $pkg && !empty($pkg->pair_commission_type) ? $pkg->pair_commission_type
            : (!empty($cfg->binary_pair_type) ? $cfg->binary_pair_type : 'percent');
        $pair_val = ($pkg && (float) $pkg->pair_commission > 0) ? (float) $pkg->pair_commission
            : (($binary_type_in === 'amount') ? $binary_amount_in : $binary_percent_in);

        $binary_bonus = 0.0;
        if ($binary_enabled && $pair_val > 0 && $pairs_members > 0) {
            if ($pair_type === 'amount') {
                $binary_bonus = $pairs_members * $pair_val;
            } else {
                $binary_bonus = $base_volume * ($pair_val / 100.0);
            }
        }

        // ----- Matching bonus over binary (only if enabled globally) -----
        $matching_enabled = (int) $cfg->matching_bonus_status === 1;
        $matching_bonus = 0.0;
        $matching_levels_used = 0;
        $matching_schema = [];

        if ($matching_enabled) {
            if ($pkg && !empty($pkg->matching_bonus_json)) {
                $levels_arr = json_decode($pkg->matching_bonus_json, true);
                if (is_array($levels_arr)) {
                    foreach ($levels_arr as $pct) {
                        $pct = (float) $pct;
                        if ($pct <= 0)
                            continue;
                        $matching_bonus += $binary_bonus * ($pct / 100.0);
                        $matching_schema[] = $pct;
                    }
                    $matching_levels_used = count($matching_schema);
                }
            } else {
                // fallback to simple “same % per level” input if package has no schema
                $matching_levels_used = max(0, $match_levels_in);
                if ($matching_levels_used > 0 && $match_percent_in > 0) {
                    $matching_bonus = $binary_bonus * (($matching_levels_used * $match_percent_in) / 100.0);
                    $matching_schema = array_fill(0, $matching_levels_used, $match_percent_in);
                }
            }
        }

        // (Optional) Own commission (global switch exists)
        // $own_bonus = 0.0;
        // if ((int)$cfg->own_commission_status === 1 && $pkg && (float)$pkg->own_commission > 0) {
        //     // assume percent (your schema only stores a number)
        //     $own_bonus = $pv_per_member * ((float)$pkg->own_commission/100.0);
        // }

        $own_bonus = 0.0;
        // (Optional) Own commission (global switch exists)
        if (
            (int) $cfg->own_commission_status === 1 &&
            $pkg &&
            isset($pkg->own_commission) &&
            is_numeric($pkg->own_commission) &&
            (float) $pkg->own_commission > 0
        ) {
            $own_bonus = $pv_per_member * ((float) $pkg->own_commission / 100.0);
        }

        // ----- Gross payout before cap -----
        $gross_bonus = $sponsor_bonus + $binary_bonus + $matching_bonus + $own_bonus;

        // ----- Apply capping to selected components -----
        $capped_part = ($cap_on_sponsor ? $sponsor_bonus : 0)
            + ($cap_on_binary ? $binary_bonus : 0)
            + ($cap_on_match ? $matching_bonus : 0);

        $uncapped_part = $gross_bonus - $capped_part;
        $capped_award = $capped_part;
        $flushed = 0.0;

        if ($cap_value > 0 && $capped_part > $cap_value) {
            $flushed = $capped_part - $cap_value;
            $capped_award = $cap_value;
        }
        $total_after_capping = $uncapped_part + $capped_award;

        // ----- Expenses (admin + TDS on paid) -----
        $admin_amount = $total_after_capping * ($admin_charges / 100.0);
        $tds_amount = $total_after_capping * ($tds_pct / 100.0);
        $expenses = $admin_amount + $tds_amount;

        // ----- Simple revenue model -----
        $revenue = $total_members * $pv_per_member;
        $company_profit = $revenue - ($total_after_capping + $expenses);

        // ----- Summary -----
        $summary = [
            'currency' => $currency,
            'package_id' => $package_id,
            'package_name' => $pkg ? $pkg->package_name : null,

            'levels' => $levels,
            'total_members' => $total_members,
            'left_members' => $left_members,
            'right_members' => $right_members,

            'pv_per_member' => round($pv_per_member, 2),
            'left_volume' => round($left_volume, 2),
            'right_volume' => round($right_volume, 2),
            'weak_unit' => $weak_unit,
            'pair_ratio' => "$rL:$rR",
            'eligible_pairs' => $pairs_members,
            'base_volume' => round($base_volume, 2),

            // sponsor/direct
            'direct_enabled' => $direct_enabled,
            'direct_type' => $direct_type,
            'sponsor_val' => $direct_val,
            'sponsor_bonus' => round($sponsor_bonus, 2),

            // binary
            'binary_enabled' => $binary_enabled,
            'binary_type' => $pair_type,
            'binary_percent' => $pair_type === 'percent' ? round($pair_val, 2) : 0,
            'binary_amount' => $pair_type === 'amount' ? round($pair_val, 2) : 0,
            'binary_bonus' => round($binary_bonus, 2),

            // matching
            'matching_enabled' => $matching_enabled,
            'matching_levels' => $matching_levels_used,
            'matching_schema' => $matching_schema, // e.g., [10,2,4,6]
            'matching_bonus' => round($matching_bonus, 2),

            // own
            'own_bonus' => round($own_bonus, 2),

            // capping/expenses
            'flushed_amount' => round($flushed, 2),
            'total_bonus_after_capping' => round($total_after_capping, 2),
            'admin_charges_pct' => round($admin_charges, 2),
            'tds_pct' => round($tds_pct, 2),
            'admin_charges' => round($admin_amount, 2),
            'tds' => round($tds_amount, 2),
            'expenses' => round($expenses, 2),

            'revenue' => round($revenue, 2),
            'company_profit' => round($company_profit, 2),

            'cap_value' => round($cap_value, 2),
            'cap_scope' => ['sponsor' => $cap_on_sponsor, 'binary' => $cap_on_binary, 'matching' => $cap_on_match],
        ];

        // charts
        $charts = [
            'payoutVsExpenses' => [
                'labels' => ['Total Bonus (after cap)', 'Expenses', 'Company Profit'],
                'data' => [round($total_after_capping, 2), round($expenses, 2), round($company_profit, 2)]
            ],
            'payoutBreakdown' => [
                'labels' => ['Sponsor', 'Binary', 'Matching', 'Own'],
                'data' => [round($sponsor_bonus, 2), round($binary_bonus, 2), round($matching_bonus, 2), round($own_bonus, 2)]
            ],
            'revenueBreakdown' => [
                'labels' => ['Revenue', 'Paid Bonus', 'Expenses'],
                'data' => [round($revenue, 2), round($total_after_capping, 2), round($expenses, 2)]
            ]
        ];

        return $this->_out(true, 'OK', ['summary' => $summary, 'charts' => $charts]);
    }

    private function _out($ok, $msg, $data = [])
    {
        $this->output->set_content_type('application/json')
            ->set_output(json_encode(['status' => $ok, 'message' => $msg, 'data' => $data]));
    }
}
