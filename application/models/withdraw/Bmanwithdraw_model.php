<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bmanwithdraw_model extends CI_Model
{
    public function settings()
    {
        return [
            'withdraw_status' => (int) site_settings('withdraw_settings', 'withdraw_status'),
            'min_withdraw' => (float) str_replace(',', '', site_settings('withdraw_settings', 'min_withdraw')),
            'max_withdraw' => (float) str_replace(',', '', site_settings('withdraw_settings', 'max_withdraw')),
            'withdraw_fee' => (float) str_replace(',', '', site_settings('withdraw_settings', 'withdraw_fee')),
            'withdraw_allowed_exchange' => (int) (site_settings('withdraw_settings', 'withdraw_allowed_exchange') ?: 1),
            'withdraw_allowed_earning' => (int) (site_settings('withdraw_settings', 'withdraw_allowed_earning') ?: 1),
            'withdraw_allowed_staking' => (int) (site_settings('withdraw_settings', 'withdraw_allowed_staking') ?: 0),
            'withdraw_allowed_bonus' => (int) (site_settings('withdraw_settings', 'withdraw_allowed_bonus') ?: 0),
        ];
    }

    public function wallet_snapshot($user_id)
    {
        $user_id = (int) $user_id;

        $row = $this->db->get_where('user_wallets', ['user_id' => $user_id])->row_array();
        $row = is_array($row) ? $row : [];

        return [
            'usdt' => (float) ($row['usd_balance'] ?? 0),
            'exchange' => (float) ($row['exchange_balance'] ?? 0),
            'earning' => (float) ($row['earning_balance'] ?? 0),
            'staking' => (float) ($row['staking_balance'] ?? 0),
            'bonus' => (float) ($row['bonus_balance'] ?? 0),
        ];
    }

    public function wallet_balance($user_id, $source_wallet)
    {
        $snap = $this->wallet_snapshot($user_id);
        return (float) ($snap[$source_wallet] ?? 0);
    }

    public function source_allowed($source_wallet)
    {
        $settings = $this->settings();
        $map = [
            'exchange' => $settings['withdraw_allowed_exchange'],
            'earning' => $settings['withdraw_allowed_earning'],
            'staking' => $settings['withdraw_allowed_staking'],
            'bonus' => $settings['withdraw_allowed_bonus'],
        ];

        return !empty($map[$source_wallet]);
    }

    public function user_history($user_id, $limit = 100)
    {
        return $this->db->select('wr.*, u.username, u.referral_id')
            ->from('bman_withdraw_requests wr')
            ->join('users u', 'u.id = wr.user_id', 'left')
            ->where('wr.user_id', (int) $user_id)
            ->order_by('wr.id', 'DESC')
            ->limit((int) $limit)
            ->get()
            ->result_array();
    }

    public function admin_history($filters = [], $limit = 100, $offset = 0)
    {
        $this->db->select('wr.*, u.username, u.email, u.referral_id');
        $this->db->from('bman_withdraw_requests wr');
        $this->db->join('users u', 'u.id = wr.user_id', 'left');

        if (!empty($filters['status'])) {
            $this->db->where('wr.status', $filters['status']);
        }
        if (!empty($filters['source_wallet'])) {
            $this->db->where('wr.source_wallet', $filters['source_wallet']);
        }
        if (!empty($filters['q'])) {
            $q = trim((string) $filters['q']);
            $this->db->group_start()
                ->like('wr.request_no', $q)
                ->or_like('u.username', $q)
                ->or_like('u.email', $q)
                ->or_like('u.referral_id', $q)
                ->group_end();
        }

        return $this->db->order_by('wr.id', 'DESC')
            ->limit((int) $limit, (int) $offset)
            ->get()
            ->result_array();
    }

    public function count_admin_history($filters = [])
    {
        $this->db->from('bman_withdraw_requests wr');
        $this->db->join('users u', 'u.id = wr.user_id', 'left');
        if (!empty($filters['status'])) {
            $this->db->where('wr.status', $filters['status']);
        }
        if (!empty($filters['source_wallet'])) {
            $this->db->where('wr.source_wallet', $filters['source_wallet']);
        }
        if (!empty($filters['q'])) {
            $q = trim((string) $filters['q']);
            $this->db->group_start()
                ->like('wr.request_no', $q)
                ->or_like('u.username', $q)
                ->or_like('u.email', $q)
                ->or_like('u.referral_id', $q)
                ->group_end();
        }
        return (int) $this->db->count_all_results();
    }

    public function create_request(array $data)
    {
        $now = date('Y-m-d H:i:s');
        $request_no = 'BWM-' . date('YmdHis') . '-' . random_int(1000, 9999);

        $insert = [
            'request_no' => $request_no,
            'user_id' => (int) $data['user_id'],
            'source_wallet' => $data['source_wallet'],
            'request_amount' => (float) $data['request_amount'],
            'fee_amount' => (float) $data['fee_amount'],
            'net_amount' => (float) $data['net_amount'],
            'withdraw_address' => trim((string) $data['withdraw_address']),
            'remark' => trim((string) ($data['remark'] ?? '')),
            'status' => 'pending',
            'created_at' => $now,
        ];

        $this->db->insert('bman_withdraw_requests', $insert);
        return $this->db->insert_id() ? array_merge($insert, ['id' => $this->db->insert_id()]) : false;
    }

    public function get_request($id)
    {
        return $this->db->select('wr.*, u.username, u.email, u.referral_id')
            ->from('bman_withdraw_requests wr')
            ->join('users u', 'u.id = wr.user_id', 'left')
            ->where('wr.id', (int) $id)
            ->get()
            ->row_array();
    }

    public function log_action($request_id, $admin_id, $action, $old_status, $new_status, $remarks = '')
    {
        $this->db->insert('withdraw_audit_log', [
            'request_id' => (int) $request_id,
            'admin_id' => (int) $admin_id,
            'action' => $action,
            'old_status' => $old_status,
            'new_status' => $new_status,
            'remarks' => $remarks,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
