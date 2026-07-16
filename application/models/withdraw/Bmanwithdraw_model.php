<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bmanwithdraw_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('WalletMaturity_model', 'maturity');
    }

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
        return $this->maturity_breakdown($user_id);
    }

    /**
     * Total withdrawable balance based on matured ledger credits only.
     * This is the source of truth for the available balance shown on the page.
     */
    public function available_balance($user_id)
    {
        $user_id = (int) $user_id;
        $this->db->select('COALESCE(SUM(COALESCE(credit,0) - COALESCE(debit,0)), 0) AS total', false)
            ->from('wallet_ledger')
            ->where('user_id', $user_id)
            ->group_start()
                ->where('credit >', 0)
                ->or_where('debit >', 0)
            ->group_end()
            ->where('is_matured', 1);

        $row = $this->db->get()->row_array();
        return (float) ($row['total'] ?? 0);
    }

    /**
     * Ledger-based balances per wallet (source of truth for withdrawal).
     * Returns total, locked, matured, withdrawable for each BMAN wallet.
     */
    public function maturity_breakdown($user_id)
    {
        $breakdowns = $this->maturity->all_breakdowns($user_id);
        $flat = [];
        foreach ($breakdowns as $wallet => $b) {
            $flat[$wallet] = $b['total'];
            $flat[$wallet . '_locked'] = $b['locked'];
            $flat[$wallet . '_matured'] = $b['matured'];
            $flat[$wallet . '_withdrawable'] = $b['withdrawable'];
        }
        // Keep usdt for display (not a BMAN withdraw source)
        $row = $this->db->get_where('user_wallets', ['user_id' => (int) $user_id])->row_array();
        $flat['usdt'] = (float) ($row['usd_balance'] ?? 0);
        return $flat;
    }

    public function wallet_balance_detail($user_id, $source_wallet)
    {
        return $this->maturity->wallet_breakdown($user_id, $source_wallet);
    }

    public function upcoming_unlocks($user_id, $wallet = null, $limit = 30)
    {
        return $this->maturity->upcoming_unlocks($user_id, $wallet, $limit);
    }

    public function maturity_rules()
    {
        return $this->maturity->rules();
    }

    public function wallet_balance($user_id, $source_wallet)
    {
        return $this->maturity->withdrawable($user_id, $source_wallet);
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
            'platform_address' => trim((string) ($data['platform_address'] ?? '')),
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
