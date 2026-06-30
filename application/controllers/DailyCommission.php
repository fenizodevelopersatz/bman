<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class DailyCommission extends CI_Controller
{


    public function __construct()
    {

        parent::__construct();
        $this->load->database();

    }

    private function _dbg($tag, $payload = null)
    {
        if (is_array($payload) || is_object($payload)) {
            $payload = json_encode($payload);
        }
        log_message('debug', "[CommissionEngine][$tag] {$payload}");
    }

    // ============================================================================
    // BINARY + PAIR + MATCHING (PACKAGE BASED)  ✅
    // Commission_config: ratio + carry rules
    // Package_config: binary_commission + pair_commission + daily_max_pairs + matching_bonus_json
    // ============================================================================

    /**
     * Binary Earnings
     **/
    public function binary_commission_call()
    {
        // Load config (single row config)
        $cfg = $this->db->get_where('commission_config', ['id' => 1])->row();
        if (!$cfg) {
            echo json_encode(['result' => false, 'msg' => 'Commission config not found']);
            return;
        }

        // Run date (daily cron run date)
        $run_date = date("Y-m-d");

        // =======================
        // 1) Binary Commission
        // =======================
        if ((int) $cfg->binary_commission_status === 1) {

            /**
             * Carry Forward settings (your new setup)
             * These fields should exist in commission_config:
             * - carry_forward_status (0/1)
             * - carry_forward_mode   (Lifetime/Daily/Weekly/Monthly)
             * - carry_forward_cap    (number or null)
             *
             * NOTE: Your process_binary_commission_v2() already reads:
             *  $carry_mode = $cfg->binary_carry_forward
             *  $flush_rule = $cfg->binary_flush_rule
             *
             * So here we just map your ADMIN fields into those keys
             * (backward compatible).
             */

            // 1) Carry forward enable/disable
            $carry_status = isset($cfg->carry_forward_status) ? (int) $cfg->carry_forward_status : 0;

            // 2) Mode => we convert to flush_rule for engine
            // Admin values: Lifetime / Daily / Weekly / Monthly
            $mode = isset($cfg->carry_forward_mode) ? strtolower(trim($cfg->carry_forward_mode)) : 'lifetime';

            // Map admin mode => engine flush rule
            // lifetime = never flush, daily/weekly/monthly flush accordingly
            $flush_rule = 'never';
            if ($mode === 'daily')
                $flush_rule = 'daily';
            if ($mode === 'weekly')
                $flush_rule = 'weekly';
            if ($mode === 'monthly')
                $flush_rule = 'monthly';

            // 3) Cap (optional)
            $cap = null;
            if (isset($cfg->carry_forward_cap) && $cfg->carry_forward_cap !== '' && $cfg->carry_forward_cap !== null) {
                $cap = (float) $cfg->carry_forward_cap;
            }

            // Write into cfg object so your process_binary_commission_v2() can read it
            // carry mode rule: if OFF => none, if ON => both (recommended) or weak_leg as you prefer
            $cfg->binary_carry_forward = ($carry_status === 1) ? 'both' : 'none';
            $cfg->binary_flush_rule = $flush_rule;
            $cfg->carry_forward_cap_bv = $cap;

            // ✅ IMPORTANT: keep method name EXACT
            $this->process_binary_pair_and_matching_v3($run_date);
        }

        $result = [
            'result' => true,
            'msg' => 'Binary & Matching processed',
            'date' => $run_date
        ];
        $this->_dbg('CRON_BINARY_COMMISSION_CALL_DONE', $result);
        echo json_encode($result);
        exit;
    }

    private function process_binary_pair_and_matching_v3($run_date)
    {
        $run_date = date('Y-m-d', strtotime($run_date));

        // -------------------- Global config --------------------
        $cfg = $this->db->get_where('commission_config', ['id' => 1])->row();
        if (!$cfg || (int) ($cfg->binary_commission_status ?? 0) !== 1) {
            $this->_dbg('CRON_SKIP_BINARY', ['run_date' => $run_date, 'reason' => 'binary_commission_status disabled']);
            return;
        }

        // ratio from commission_config
        $ratio = trim((string) ($cfg->binary_pair_ratio ?? '1:1'));
        $parts = explode(':', $ratio);
        $rL = isset($parts[0]) ? (int) $parts[0] : 1;
        $rR = isset($parts[1]) ? (int) $parts[1] : 1;
        if ($rL <= 0)
            $rL = 1;
        if ($rR <= 0)
            $rR = 1;

        // carry rules (commission_config)
        $carry_enabled = (int) ($cfg->carry_forward_status ?? 1) === 1;
        $flush_rule = strtoupper((string) ($cfg->carry_forward_mode ?? 'LIFETIME')); // LIFETIME/DAILY/WEEKLY/MONTHLY
        $carry_cap = (float) ($cfg->carry_forward_cap ?? 0);

        // Basis: BV means use history.type='profit' token_amount for that day
        // If you want PV (investment volume per day), change to PV
        $basis = 'BV';

        $this->_dbg('CRON_BINARY_START', [
            'run_date' => $run_date,
            'ratio' => "{$rL}:{$rR}",
            'carry_enabled' => $carry_enabled ? 1 : 0,
            'flush_rule' => $flush_rule,
            'carry_cap' => $carry_cap,
            'basis' => $basis,
        ]);

        // Active users
        $users = $this->db->select('id')->from('users')->where('status', '1')->get()->result();

        foreach ($users as $u) {
            $user_id = (int) $u->id;

            // ---------------------------------------
            // idempotence per type per day
            // ---------------------------------------
            $already_binary = $this->db->where('user_id', $user_id)
                ->where('type', 'binary_commission')
                ->where('DATE(history_date)', $run_date)
                ->count_all_results('history');

            $already_pair = $this->db->where('user_id', $user_id)
                ->where('type', 'pair_commission')
                ->where('DATE(history_date)', $run_date)
                ->count_all_results('history');

            // If both already inserted, skip everything (including matching)
            if ($already_binary && $already_pair) {
                continue;
            }

            // ---------------------------------------
            // latest active investment = latest active package
            // (same package can repeat, investment id differs)
            // ---------------------------------------
            $inv = $this->db->select('id, package_id')
                ->from('user_investment')
                ->where('user_id', $user_id)
                ->where('status', 1)
                ->order_by('id', 'DESC')
                ->limit(1)
                ->get()->row();

            $invest_id = (int) ($inv->id ?? 0);
            $package_id = (int) ($inv->package_id ?? 0);

            if ($package_id <= 0) {
                $this->_dbg('CRON_SKIP_USER', ['user_id' => $user_id, 'reason' => 'no active package']);
                continue;
            }

            $pkg = $this->db->get_where('package_config', ['id' => $package_id, 'status' => 1])->row();
            if (!$pkg) {
                $this->_dbg('CRON_SKIP_USER', ['user_id' => $user_id, 'package_id' => $package_id, 'reason' => 'package not found']);
                continue;
            }

            // Pair commission must be ON to compute pairs at all
            if ((int) ($pkg->pair_commission_status ?? 0) !== 1) {
                $this->_dbg('CRON_SKIP_USER', ['user_id' => $user_id, 'package_id' => $package_id, 'reason' => 'pair_commission_status off']);
                continue;
            }

            // ---------------------------------------
            // get legs
            // ---------------------------------------
            $left_users = $this->get_leg_users($user_id, 'left');
            $right_users = $this->get_leg_users($user_id, 'right');

            // todays volume
            $L = $this->sum_leg_volume($left_users, $run_date, $basis);
            $R = $this->sum_leg_volume($right_users, $run_date, $basis);
            $left_vol = (float) ($L['volume'] ?? 0);
            $right_vol = (float) ($R['volume'] ?? 0);

            // ---------------------------------------
            // carry
            // ---------------------------------------
            $carry = $this->get_carry_row($user_id);
            $left_carry = (float) ($carry->left_carry ?? 0);
            $right_carry = (float) ($carry->right_carry ?? 0);

            // flush rule
            if ($carry_enabled && !empty($carry->last_flush_at)) {
                $last = new DateTime(date('Y-m-d', strtotime($carry->last_flush_at)));
                $cur = new DateTime($run_date);

                $flush_now = false;
                if ($flush_rule === 'DAILY') {
                    $flush_now = $cur > $last;
                } elseif ($flush_rule === 'WEEKLY') {
                    $flush_now = (int) $cur->format('oW') !== (int) $last->format('oW');
                } elseif ($flush_rule === 'MONTHLY') {
                    $flush_now = $cur->format('Ym') !== $last->format('Ym');
                } else {
                    // LIFETIME
                    $flush_now = false;
                }

                if ($flush_now) {
                    $left_carry = 0;
                    $right_carry = 0;
                }
            }

            if ($carry_enabled) {
                $left_vol += $left_carry;
                $right_vol += $right_carry;
            }

            $this->_dbg('CRON_VOL', [
                'user_id' => $user_id,
                'package_id' => $package_id,
                'invest_id' => $invest_id,
                'left_vol' => $left_vol,
                'right_vol' => $right_vol,
                'left_carry' => $left_carry,
                'right_carry' => $right_carry,
            ]);

            if ($left_vol <= 0 || $right_vol <= 0) {
                $this->save_carry(
                    $user_id,
                    $this->cap_value($left_vol, $carry_cap),
                    $this->cap_value($right_vol, $carry_cap),
                    $run_date
                );
                continue;
            }

            // ---------------------------------------
            // pairs calculation
            // ---------------------------------------
            $pairs = (int) floor(min($left_vol / $rL, $right_vol / $rR));

            // daily max pairs from PACKAGE
            $daily_max_pairs = (int) ($pkg->daily_max_pairs ?? 0);
            if ($daily_max_pairs > 0 && $pairs > $daily_max_pairs) {
                $pairs = $daily_max_pairs;
            }

            if ($pairs <= 0) {
                $this->save_carry(
                    $user_id,
                    $this->cap_value($left_vol, $carry_cap),
                    $this->cap_value($right_vol, $carry_cap),
                    $run_date
                );
                continue;
            }

            // consume matched
            $matched_left = $pairs * $rL;
            $matched_right = $pairs * $rR;

            $rem_left = max(0, $left_vol - $matched_left);
            $rem_right = max(0, $right_vol - $matched_right);

            // store remainder as carry
            $new_left_carry = $carry_enabled ? $this->cap_value($rem_left, $carry_cap) : 0;
            $new_right_carry = $carry_enabled ? $this->cap_value($rem_right, $carry_cap) : 0;

            $this->save_carry($user_id, $new_left_carry, $new_right_carry, $run_date);

            // weak-leg base
            $weak_unit = min($rL, $rR);
            $base_volume = $pairs * $weak_unit;

            $this->_dbg('CRON_PAIR_RESULT', [
                'user_id' => $user_id,
                'pairs' => $pairs,
                'daily_max_pairs' => $daily_max_pairs,
                'base_volume' => $base_volume,
                'matched_left' => $matched_left,
                'matched_right' => $matched_right,
                'rem_left' => $rem_left,
                'rem_right' => $rem_right,
            ]);

            // ---------------------------------------
            // 1) BINARY COMMISSION (package_config.binary_commission)
            // ---------------------------------------
            $binary_token = 0.0;
            $binary_type = strtolower((string) ($pkg->binary_commission_type ?? 'percent'));
            $binary_value = (float) ($pkg->binary_commission ?? 0);

            if ($binary_value > 0) {
                if ($binary_type === 'percent') {
                    $binary_token = ($base_volume * $binary_value) / 100.0;
                } else {
                    $binary_token = $pairs * $binary_value; // per pair amount
                }
            }

            // ---------------------------------------
            // 2) PAIR COMMISSION (package_config.pair_commission)
            // ---------------------------------------
            $pair_token = 0.0;
            $pair_type = strtolower((string) ($pkg->pair_commission_type ?? 'percent'));
            $pair_value = (float) ($pkg->pair_commission ?? 0);

            if ($pair_value > 0) {
                if ($pair_type === 'percent') {
                    $pair_token = ($base_volume * $pair_value) / 100.0;
                } else {
                    $pair_token = $pairs * $pair_value;
                }
            }

            $this->_dbg('CRON_COMM_CALC', [
                'user_id' => $user_id,
                'binary_type' => $binary_type,
                'binary_value' => $binary_value,
                'binary_token' => $binary_token,
                'pair_type' => $pair_type,
                'pair_value' => $pair_value,
                'pair_token' => $pair_token,
            ]);

            // meta common
            $meta_common = [
                "pair_ratio_used" => "{$rL}:{$rR}",
                "pairs_count" => $pairs,
                "basis" => $basis,
                "total_left_users" => $L['user_ids'] ?? '',
                "total_right_users" => $R['user_ids'] ?? '',
                "total_left_invest_ids" => $L['invest_ids'] ?? '',
                "total_right_invest_ids" => $R['invest_ids'] ?? '',
                "invest_id" => $invest_id, // exists in history
                "earn_by" => "token",
            ];

            // Insert BINARY COMMISSION row (type = binary_commission)
            $binary_history_id = 0;
            if (!$already_binary && $binary_token > 0) {
                $binary_usd = $this->token_to_usd($binary_token);

                $meta = $meta_common + [
                    "description" => "Binary Commission Earned",
                ];

                $binary_history_id = $this->credit_history_row(
                    $user_id,
                    $run_date,
                    'binary_commission',
                    $binary_usd,
                    $binary_token,
                    $meta
                );
            } else {
                // if already exists, fetch it for matching reference
                $row = $this->db->select('id')->from('history')
                    ->where('user_id', $user_id)
                    ->where('type', 'binary_commission')
                    ->where('DATE(history_date)', $run_date)
                    ->order_by('id', 'DESC')
                    ->limit(1)->get()->row();
                $binary_history_id = (int) ($row->id ?? 0);
            }

            // Insert PAIR COMMISSION row (type = pair_commission)
            if (!$already_pair && $pair_token > 0) {
                $pair_usd = $this->token_to_usd($pair_token);

                $meta = $meta_common + [
                    "description" => "Pair Commission Earned",
                ];

                $this->credit_history_row(
                    $user_id,
                    $run_date,
                    'pair_commission',
                    $pair_usd,
                    $pair_token,
                    $meta
                );
            }

            // ---------------------------------------
            // 3) MATCHING BONUS (package_config.matching_bonus_json)
            // BASE: (binary + pair) token  ✅
            // ---------------------------------------
            $match_base = (float) $binary_token + (float) $pair_token;
            if ($match_base > 0 && $binary_history_id > 0) {
                $this->process_matching_bonus_from_package(
                    $run_date,
                    $user_id,
                    $package_id,
                    $binary_history_id,
                    $match_base
                );
            }
        }
    }

    /**
     * Recursively Get All Users Under a Leg
     **/
    private function get_leg_users($parent_id, $position)
    {
        $users = [];

        $child = $this->db->get_where('binary_placement', ['parent_id' => $parent_id, 'position' => $position])->row();

        if ($child) {
            $users[] = $child->user_id;

            $users = array_merge($users, $this->get_leg_users($child->user_id, 'left'));
            $users = array_merge($users, $this->get_leg_users($child->user_id, 'right'));
        }

        return $users;
    }

    // ----------------------------------------------------------------------------
// Sum volume for users on a given date.
// PV: investments of that date
// BV: ROI profit token_amount of that date
// ----------------------------------------------------------------------------
    private function sum_leg_volume(array $user_ids, string $run_date, string $basis = 'BV'): array
    {
        if (empty($user_ids)) {
            return ['volume' => 0, 'invest_ids' => '', 'user_ids' => ''];
        }

        $ids_csv = implode(',', $user_ids);
        $this->db->reset_query();

        if (strtoupper($basis) === 'PV') {
            $q = $this->db->select('COALESCE(SUM(invest_amount),0) AS vol, GROUP_CONCAT(id) AS invest_ids', false)
                ->from('user_investment')
                ->where_in('user_id', $user_ids)
                ->where('status', 1)
                ->where('DATE(created_date)', $run_date) // change if your column differs
                ->get()->row();

            return [
                'volume' => (float) ($q->vol ?? 0),
                'invest_ids' => (string) ($q->invest_ids ?? ''),
                'user_ids' => $ids_csv
            ];
        }

        // BV: sum token_amount from ROI rows
        $q = $this->db->select('COALESCE(SUM(token_amount),0) AS vol', false)
            ->from('history')
            ->where_in('user_id', $user_ids)
            ->where('type', 'profit')
            ->where('status', '1')
            ->where('DATE(history_date)', $run_date)
            ->get()->row();

        return [
            'volume' => (float) ($q->vol ?? 0),
            'invest_ids' => '',
            'user_ids' => $ids_csv
        ];
    }

    // ----------------------------------------------------------------------------
// Carry helpers
// ----------------------------------------------------------------------------
    private function get_carry_row($user_id)
    {
        $row = $this->db->get_where('binary_carry', ['user_id' => (int) $user_id])->row();

        if (!$row) {
            $this->db->insert('binary_carry', [
                'user_id' => (int) $user_id,
                'left_carry' => 0,
                'right_carry' => 0,
                'last_flush_at' => null
            ]);
            $row = $this->db->get_where('binary_carry', ['user_id' => (int) $user_id])->row();
        }

        return $row;
    }

    private function save_carry($user_id, $left, $right, $run_date)
    {
        $this->db->update('binary_carry', [
            'left_carry' => (float) $left,
            'right_carry' => (float) $right,
            'last_flush_at' => $run_date,
        ], ['user_id' => (int) $user_id]);
    }

    private function cap_value($val, $cap)
    {
        $val = (float) $val;
        $cap = (float) $cap;
        if ($cap > 0 && $val > $cap)
            return $cap;
        return $val;
    }

    // ----------------------------------------------------------------------------
// Token convert
// ----------------------------------------------------------------------------
    private function token_to_usd($token_amount)
    {
        $token_info = token_info();
        $cv = (float) ($token_info->currency_value ?? 0);
        if ($cv <= 0)
            return 0;
        return (float) $token_amount / $cv;
    }

    // ----------------------------------------------------------------------------
// Generic credit row (only existing columns)
// Returns inserted id (0 if fail)
// ----------------------------------------------------------------------------
    private function credit_history_row($user_id, $run_date, $type, $usd_amount, $token_amount, array $meta)
    {
        $token_info = token_info();
        $currency_info = currency_info();

        // keep times separate by type for easier reading
        $time = '10:00:00';
        if ($type === 'pair_commission')
            $time = '10:05:00';
        if ($type === 'matching_bonus')
            $time = '11:00:00';

        $payload = array_merge([
            "user_id" => (int) $user_id,
            "amount" => round((float) $usd_amount, 6),
            "type" => (string) $type,
            "history_date" => $run_date . ' ' . $time,
            "date" => $run_date . ' ' . $time,
            "status" => '1',
            "coin_type" => '1',
            "token_amount" => round((float) $token_amount, 6),
            "coin_id" => $currency_info->id ?? null,
            "token_id" => $token_info->id ?? null,
        ], $meta);

        $this->db->insert('history', $payload);
        $id = (int) $this->db->insert_id();

        $this->_dbg('CRON_CREDIT', [
            'user_id' => (int) $user_id,
            'type' => (string) $type,
            'usd' => (float) $usd_amount,
            'token' => (float) $token_amount,
            'id' => $id
        ]);

        return $id;
    }

    // ----------------------------------------------------------------------------
// MATCHING from earner PACKAGE (matching_bonus_json)
// Base token = (binary_token + pair_token)
// Uses only existing history columns: from_id, level_count, invest_id, ref_history_id, earn_by
// ----------------------------------------------------------------------------
    private function process_matching_bonus_from_package($run_date, $earner_id, $earner_package_id, $ref_history_id, $base_token)
    {
        $run_date = date('Y-m-d', strtotime($run_date));
        $earner_id = (int) $earner_id;
        $earner_package_id = (int) $earner_package_id;
        $ref_history_id = (int) $ref_history_id;
        $base_token = (float) $base_token;

        $cfg = $this->db->get_where('commission_config', ['id' => 1])->row();
        if (!$cfg || (int) ($cfg->matching_bonus_status ?? 0) !== 1)
            return;

        $pkg = $this->db->get_where('package_config', ['id' => $earner_package_id, 'status' => 1])->row();
        if (!$pkg)
            return;

        $percents = json_decode((string) ($pkg->matching_bonus_json ?? '[]'), true);
        if (!is_array($percents))
            $percents = [];
        $percents = array_values(array_map('floatval', $percents));
        $levels = count($percents);
        if ($levels <= 0)
            return;

        // earner latest invest id (for meta)
        $inv = $this->db->select('id')
            ->from('user_investment')
            ->where('user_id', $earner_id)
            ->where('status', 1)
            ->order_by('id', 'DESC')
            ->limit(1)->get()->row();
        $earner_invest_id = (int) ($inv->id ?? 0);

        $upline_id = $this->get_sponsor_id($earner_id);

        for ($lv = 1; $lv <= $levels && $upline_id; $lv++) {

            $percent = (float) ($percents[$lv - 1] ?? 0);
            if ($percent <= 0) {
                $upline_id = $this->get_sponsor_id($upline_id);
                continue;
            }

            // avoid duplicates
            $exists = $this->db->where('user_id', (int) $upline_id)
                ->where('type', 'matching_bonus')
                ->where('ref_history_id', $ref_history_id)
                ->where('level_count', $lv)
                ->count_all_results('history');

            if ($exists) {
                $upline_id = $this->get_sponsor_id($upline_id);
                continue;
            }

            $bonus_token = ($base_token * $percent) / 100.0;
            if ($bonus_token <= 0) {
                $upline_id = $this->get_sponsor_id($upline_id);
                continue;
            }

            $bonus_usd = $this->token_to_usd($bonus_token);

            $meta = [
                "description" => "Matching Bonus L{$lv} from #{$earner_id}",
                "ref_history_id" => $ref_history_id,
                "from_id" => $earner_id,
                "level_count" => $lv,
                "invest_id" => $earner_invest_id,
                "earn_by" => "token",
            ];

            $this->credit_history_row((int) $upline_id, $run_date, 'matching_bonus', $bonus_usd, $bonus_token, $meta);

            $upline_id = $this->get_sponsor_id($upline_id);
        }
    }

    // ----------------------------------------------------------------------------
    private function get_sponsor_id($user_id)
    {
        $u = $this->db->select('sponser')->from('users')->where('id', (int) $user_id)->get()->row();
        $sid = (int) ($u->sponser ?? 0);
        return $sid > 0 ? $sid : null;
    }




}