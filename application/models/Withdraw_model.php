<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Withdraw_model extends CI_Model
{

    public function get_user_balance($user_id)
    {
        return get_member_wallet_total_commission_balance($user_id);
    }

    public function get_withdraw_settings($type = 'withdraw_settings')
    {
        $settings = $this->db->where('settings_type', $type)
            ->get('site_settings')
            ->result();

        $result = [];
        foreach ($settings as $s) {
            $result[$s->settings_name] = $s->settings_value;
        }
        return (object) $result;
    }

    public function get_total_withdraw($user_id, $period = 'daily')
    {
        $this->db->select_sum('amount')->where('user_id', $user_id)->where('status !=', 'rejected');

        if ($period === 'daily') {
            $this->db->where('DATE(created_at)', date('Y-m-d'));
        } elseif ($period === 'monthly') {
            $this->db->where('MONTH(created_at)', date('m'))->where('YEAR(created_at)', date('Y'));
        }

        return (float) $this->db->get('withdraw_requests')->row()->amount ?? 0;
    }


    /* ================= BALANCE ================= */

    public function has_sufficient_balance($user_id, $amount)
    {
        $balance = get_member_wallet_total_commission_balance($user_id);

        return $balance >= $amount;
    }

    /* ================= LIMIT CHECK ================= */

    public function check_limits($user_id, $amount, $settings)
    {
        // Daily
        if ($settings->withdraw_daily_limit > 0) {
            $daily = $this->get_withdraw_sum($user_id, 'DAY');
            if (($daily + $amount) > $settings->withdraw_daily_limit) {
                return false;
            }
        }

        // Monthly
        if ($settings->withdraw_monthly_limit > 0) {
            $monthly = $this->get_withdraw_sum($user_id, 'MONTH');
            if (($monthly + $amount) > $settings->withdraw_monthly_limit) {
                return false;
            }
        }

        return true;
    }

    private function get_withdraw_sum($user_id, $type)
    {
        $this->db->select_sum('amount')
            ->where('user_id', $user_id)
            ->where('status !=', 'rejected');

        if ($type === 'DAY') {
            $this->db->where('DATE(created_at)', date('Y-m-d'));
        }

        if ($type === 'MONTH') {
            $this->db->where('MONTH(created_at)', date('m'))
                ->where('YEAR(created_at)', date('Y'));
        }

        $value = (float) $this->db->get('withdraw_requests')->row()->amount;
        // echo $this->db->last_query();
        return $value ?? 0;
    }

    /* ================= CREATE REQUEST ================= */
    public function create_request($user_id, $amount, $settings)
    {
        $this->db->trans_start();

        // Calculate fee
        if ($settings->withdraw_amount_type === '1') {
            $fee = $settings->withdraw_fee > 0
                ? ($amount * $settings->withdraw_fee / 100)
                : 0;
        } else {
            $fee = $settings->withdraw_fee ?? 0;
        }

        $net_amount = $amount - $fee;

        // 1️⃣ Deduct from total_commission wallet
        $this->db->set('balance_paise', 'balance_paise - ' . (int) $amount * 100, false)
            ->where('member_id', $user_id)
            ->where('type', 'total_commission')
            ->where('balance_paise >=', $amount * 100)
            ->update('wallets');

        // If deduction failed → rollback
        if ($this->db->affected_rows() !== 1) {
            $this->db->trans_rollback();
            return false; // insufficient balance
        }

        // 2️⃣ Get bank details from users table
        $user = $this->db->select('account_holder, account_number, branch, ifsc_code')
            ->where('id', $user_id)
            ->get('users')
            ->row();

        $payment_json = json_encode([
            'account_holder' => $user->account_holder ?? '',
            'account_number' => $user->account_number ?? '',
            'branch' => $user->branch ?? '',
            'ifsc_code' => $user->ifsc_code ?? ''
        ]);

        // 2️ Create withdraw request
        $this->db->insert('withdraw_requests', [
            'user_id' => $user_id,
            'amount' => $amount,
            'fee' => $fee,
            'net_amount' => $net_amount,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
            'payment_details' => $payment_json
        ]);

        $this->db->trans_complete();
        return $this->db->trans_status();
    }



    public function has_pending_request($user_id)
    {
        $this->db->where('user_id', $user_id);
        $this->db->where('status', 'pending');
        $query = $this->db->get('withdraw_requests');
        return $query->num_rows() > 0;
    }


    public function approve_withdraw_request($request_id, $aproved_by)
    {
        $this->db->trans_start();
        $this->db
            ->where('id', $request_id)
            ->where('status', 'pending')
            ->update('withdraw_requests', [
                'status' => 'approved',
                'approved_by' => $aproved_by,
                'approved_at' => date('Y-m-d H:i:s')
            ]);

        $this->db->trans_complete();
        return $this->db->trans_status();
    }



    public function get_count($user_id, $status, $from_date, $to_date)
    {
        $this->db->from('withdraw_requests');
        $this->db->where('user_id', $user_id);

        if (!empty($status)) {
            $this->db->where('status', $status);
        }

        if (!empty($from_date)) {
            $this->db->where('DATE(created_at) >=', $from_date);
        }

        if (!empty($to_date)) {
            $this->db->where('DATE(created_at) <=', $to_date);
        }

        return $this->db->count_all_results();
    }

    public function get_list($limit, $start, $user_id, $status, $from_date, $to_date)
    {
        $this->db->from('withdraw_requests');
        $this->db->where('user_id', $user_id);

        if (!empty($status)) {
            $this->db->where('status', $status);
        }

        if (!empty($from_date)) {
            $this->db->where('DATE(created_at) >=', $from_date);
        }

        if (!empty($to_date)) {
            $this->db->where('DATE(created_at) <=', $to_date);
        }

        $this->db->order_by('id', 'DESC');
        $this->db->limit($limit, $start);

        return $this->db->get()->result_array();
    }


}
