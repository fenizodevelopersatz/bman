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

    /**
     * Returns the latest open withdrawal request for the user.
     * Open means it is still in the admin workflow and should block new requests.
     */
    public function open_request($user_id, $source_wallet = 'bman')
    {
        return $this->db->select('wr.*, u.username, u.referral_id')
            ->from('bman_withdraw_requests wr')
            ->join('users u', 'u.id = wr.user_id', 'left')
            ->where('wr.user_id', (int) $user_id)
            ->where('wr.source_wallet', $source_wallet)
            ->where_in('wr.status', ['pending', 'processing', 'under_review', 'approved'])
            ->order_by('wr.id', 'DESC')
            ->limit(1)
            ->get()
            ->row_array();
    }

    public function has_open_request($user_id, $source_wallet = 'bman')
    {
        return !empty($this->open_request($user_id, $source_wallet));
    }

    public function admin_history($filters = [], $limit = 100, $offset = 0)
    {
        $this->db->select('
            wr.*,
            u.username,
            u.email,
            u.referral_id,
            u.status AS user_status,
            ub.holder_name,
            ub.bank_name,
            ub.account_number,
            ub.ifsc,
            ub.upi_id,
            ub.status AS bank_status
        ');
        $this->db->from('bman_withdraw_requests wr');
        $this->db->join('users u', 'u.id = wr.user_id', 'left');
        $this->db->join('user_bank ub', "ub.user_id = wr.user_id AND ub.status = 'approved'", 'left');

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
                ->or_like('wr.withdraw_address', $q)
                ->or_like('ub.holder_name', $q)
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

    /**
     * Create a withdrawal request with instant lock.
     * Must be called within a transaction with GET_LOCK on user_id.
     * Validates balance, creates request row, creates lock ledger entries.
     */
    public function create_request(array $data)
    {
        $user_id = (int) $data['user_id'];
        $source_wallet = $data['source_wallet'];
        $request_amount = (float) $data['request_amount'];
        $fee_amount = (float) ($data['fee_amount'] ?? 0);
        $net_amount = (float) ($data['net_amount'] ?? $request_amount - $fee_amount);
        $withdraw_address = trim((string) $data['withdraw_address']);
        $bman_usdt_rate = (float) ($data['bman_usdt_rate'] ?? (1 / (token_info()->currency_value ?? 1)));
        $usdt_amount = $net_amount * $bman_usdt_rate;

        // Validate amount > 0
        if ($request_amount <= 0) {
            return ['error' => 'Amount must be greater than 0'];
        }

        // Validate address format (simple check)
        if (strlen($withdraw_address) < 20 || strlen($withdraw_address) > 120) {
            return ['error' => 'Invalid address format'];
        }

        // Validate address is not a platform custodial address
        $custodial = site_settings('token_settings', 'platform_custodial_address') ?? '';
        if (!empty($custodial) && strcasecmp($custodial, $withdraw_address) === 0) {
            return ['error' => 'Withdraw address cannot be your platform custodial address'];
        }

        // Check available balance (matured, unlocked)
        if ($source_wallet === 'mixed') {
            $available = $this->available_balance($user_id);
        } else {
            $available = $this->wallet_balance($user_id, $source_wallet);
        }

        if ($available < $request_amount) {
            return ['error' => 'Insufficient matured balance'];
        }

        // Create request row
        $now = date('Y-m-d H:i:s');
        $request_no = 'BWM-' . date('YmdHis') . '-' . random_int(1000, 9999);

        $insert = [
            'request_no' => $request_no,
            'user_id' => $user_id,
            'source_wallet' => $source_wallet,
            'request_amount' => $request_amount,
            'fee_amount' => $fee_amount,
            'net_amount' => $net_amount,
            'bman_usdt_rate' => $bman_usdt_rate,
            'usdt_amount' => $usdt_amount,
            'withdraw_address' => $withdraw_address,
            'remark' => trim((string) ($data['remark'] ?? '')),
            'status' => 'pending',
            'created_at' => $now,
        ];

        $this->db->insert('bman_withdraw_requests', $insert);
        $request_id = $this->db->insert_id();
        if (!$request_id) {
            return ['error' => 'Failed to create request'];
        }

        // Create lock ledger entries (instant lock)
        if ($source_wallet === 'mixed') {
            // Allocate across wallets by priority: bonus → earning → exchange → staking
            $result = $this->_lock_allocate($user_id, $request_id, $request_amount);
            if (!empty($result['error'])) {
                return $result;
            }
        } else {
            // Lock single wallet
            $this->db->insert('bman_wallet_ledger', [
                'user_id' => $user_id,
                'wallet' => $source_wallet,
                'entry_type' => 'lock',
                'ref_type' => 'withdrawal',
                'ref_id' => $request_id,
                'amount' => $request_amount,
                'status' => 'active',
                'remark' => "Withdrawal request #{$request_no}",
                'created_at' => $now,
            ]);

            // Track allocation for reporting
            $this->db->insert('bman_withdraw_allocations', [
                'request_id' => $request_id,
                'wallet' => $source_wallet,
                'amount' => $request_amount,
                'created_at' => $now,
            ]);
        }

        return array_merge($insert, ['id' => $request_id]);
    }

    /**
     * Allocate lock amount across wallets by priority for 'mixed' requests.
     * Priority: bonus → earning → exchange → staking
     */
    private function _lock_allocate($user_id, $request_id, $total_amount)
    {
        $priority = ['bonus', 'earning', 'exchange', 'staking'];
        $remaining = $total_amount;
        $now = date('Y-m-d H:i:s');

        foreach ($priority as $wallet) {
            if ($remaining <= 0) break;

            // Get withdrawable balance for this wallet (matured, unlocked)
            $available = $this->wallet_balance($user_id, $wallet);
            $to_lock = min($available, $remaining);

            if ($to_lock > 0) {
                // Create lock ledger entry
                $this->db->insert('bman_wallet_ledger', [
                    'user_id' => $user_id,
                    'wallet' => $wallet,
                    'entry_type' => 'lock',
                    'ref_type' => 'withdrawal',
                    'ref_id' => $request_id,
                    'amount' => $to_lock,
                    'status' => 'active',
                    'remark' => "Withdrawal allocation",
                    'created_at' => $now,
                ]);

                // Track allocation
                $this->db->insert('bman_withdraw_allocations', [
                    'request_id' => $request_id,
                    'wallet' => $wallet,
                    'amount' => $to_lock,
                    'created_at' => $now,
                ]);

                $remaining -= $to_lock;
            }
        }

        if ($remaining > 0) {
            return ['error' => 'Could not allocate full amount across wallets'];
        }

        return ['success' => true];
    }

    public function get_request($id)
    {
        return $this->db->select('
            wr.*,
            u.username,
            u.email,
            u.referral_id,
            u.status AS user_status,
            ub.holder_name,
            ub.bank_name,
            ub.account_number,
            ub.ifsc,
            ub.upi_id,
            ub.status AS bank_status
        ')
            ->from('bman_withdraw_requests wr')
            ->join('users u', 'u.id = wr.user_id', 'left')
            ->join('user_bank ub', "ub.user_id = wr.user_id AND ub.status = 'approved'", 'left')
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

    /**
     * Approve a pending request. Legal: pending → approved
     * Validates current status, updates request, logs action.
     */
    public function approve($request_id, $admin_id, $admin_remark = '')
    {
        $request_id = (int) $request_id;
        $admin_id = (int) $admin_id;

        $row = $this->db->get_where('bman_withdraw_requests', ['id' => $request_id])->row_array();
        if (!$row) {
            return ['error' => 'Request not found'];
        }

        // Validate legal transition: pending → approved
        if ($row['status'] !== 'pending') {
            return ['error' => "Cannot approve from status '{$row['status']}'. Only 'pending' requests can be approved."];
        }

        $now = date('Y-m-d H:i:s');
        $updated = $this->db->update('bman_withdraw_requests', [
            'status' => 'approved',
            'approved_by' => $admin_id,
            'approved_at' => $now,
            'admin_remark' => $admin_remark,
        ], ['id' => $request_id, 'status' => 'pending']);

        if (!$updated) {
            return ['error' => 'Failed to approve (may have been updated by another admin)'];
        }

        $this->log_action($request_id, $admin_id, 'admin_approve', 'pending', 'approved', $admin_remark);
        return ['success' => true];
    }

    /**
     * Mark as processing. Legal: approved → processing
     * Validates current status, updates request.
     */
    public function mark_processing($request_id, $admin_id, $admin_remark = '')
    {
        $request_id = (int) $request_id;
        $admin_id = (int) $admin_id;

        $row = $this->db->get_where('bman_withdraw_requests', ['id' => $request_id])->row_array();
        if (!$row) {
            return ['error' => 'Request not found'];
        }

        // Validate legal transition: approved → processing
        if ($row['status'] !== 'approved') {
            return ['error' => "Cannot mark processing from status '{$row['status']}'. Only 'approved' requests can be marked processing."];
        }

        $now = date('Y-m-d H:i:s');
        $updated = $this->db->update('bman_withdraw_requests', [
            'status' => 'processing',
            'admin_remark' => $admin_remark,
        ], ['id' => $request_id, 'status' => 'approved']);

        if (!$updated) {
            return ['error' => 'Failed to mark processing (may have been updated by another admin)'];
        }

        $this->log_action($request_id, $admin_id, 'admin_processing', 'approved', 'processing', $admin_remark);
        return ['success' => true];
    }

    /**
     * Complete a request (mark as completed with tx_hash).
     * Legal: processing → completed
     * Converts lock to debit (permanent BMAN removal).
     */
    public function complete($request_id, $admin_id, $tx_hash, $admin_remark = '')
    {
        $request_id = (int) $request_id;
        $admin_id = (int) $admin_id;
        $tx_hash = trim((string) $tx_hash);

        if (empty($tx_hash)) {
            return ['error' => 'Transaction hash is required'];
        }

        $row = $this->db->get_where('bman_withdraw_requests', ['id' => $request_id])->row_array();
        if (!$row) {
            return ['error' => 'Request not found'];
        }

        // Validate legal transition: processing → completed
        if ($row['status'] !== 'processing') {
            return ['error' => "Cannot complete from status '{$row['status']}'. Only 'processing' requests can be completed."];
        }

        $now = date('Y-m-d H:i:s');

        // Convert locks to debits (each lock row becomes a debit row)
        $locks = $this->db->get_where('bman_wallet_ledger', [
            'ref_type' => 'withdrawal',
            'ref_id' => $request_id,
            'entry_type' => 'lock',
            'status' => 'active',
        ])->result_array();

        foreach ($locks as $lock) {
            // Mark lock as consumed (released state for completion flow)
            $this->db->update('bman_wallet_ledger', [
                'status' => 'reversed',
                'remark' => "Withdrawal completed at {$now}",
            ], ['id' => $lock['id']]);

            // Create debit entry (permanent removal)
            $this->db->insert('bman_wallet_ledger', [
                'user_id' => $row['user_id'],
                'wallet' => $lock['wallet'],
                'entry_type' => 'debit',
                'ref_type' => 'withdrawal',
                'ref_id' => $request_id,
                'amount' => $lock['amount'],
                'status' => 'active',
                'remark' => "Withdrawal completed - {$tx_hash}",
                'created_at' => $now,
            ]);
        }

        // Update request
        $updated = $this->db->update('bman_withdraw_requests', [
            'status' => 'completed',
            'tx_hash' => $tx_hash,
            'completed_at' => $now,
            'admin_remark' => $admin_remark,
        ], ['id' => $request_id, 'status' => 'processing']);

        if (!$updated) {
            return ['error' => 'Failed to complete (may have been updated by another admin)'];
        }

        $this->log_action($request_id, $admin_id, 'admin_complete', 'processing', 'completed', "tx_hash: {$tx_hash}");
        return ['success' => true];
    }

    /**
     * Reject a request. Legal: pending/approved → rejected
     * Releases locks back to user.
     */
    public function reject($request_id, $admin_id, $admin_remark = '')
    {
        $request_id = (int) $request_id;
        $admin_id = (int) $admin_id;

        $row = $this->db->get_where('bman_withdraw_requests', ['id' => $request_id])->row_array();
        if (!$row) {
            return ['error' => 'Request not found'];
        }

        // Validate legal transitions: pending/approved → rejected
        if (!in_array($row['status'], ['pending', 'approved'], true)) {
            return ['error' => "Cannot reject from status '{$row['status']}'. Only 'pending' or 'approved' requests can be rejected."];
        }

        $now = date('Y-m-d H:i:s');

        // Release all active locks (set to reversed status)
        $this->db->update('bman_wallet_ledger', [
            'status' => 'reversed',
            'remark' => "Withdrawal rejected - {$admin_remark}",
        ], [
            'ref_type' => 'withdrawal',
            'ref_id' => $request_id,
            'entry_type' => 'lock',
            'status' => 'active',
        ]);

        // Update request
        $updated = $this->db->update('bman_withdraw_requests', [
            'status' => 'rejected',
            'approved_by' => $admin_id,
            'approved_at' => $now,
            'admin_remark' => $admin_remark,
        ], ['id' => $request_id, 'status' => $row['status']]);

        if (!$updated) {
            return ['error' => 'Failed to reject (may have been updated by another admin)'];
        }

        $this->log_action($request_id, $admin_id, 'admin_reject', $row['status'], 'rejected', $admin_remark);
        return ['success' => true];
    }

    /**
     * Mark as failed. Legal: processing → failed
     * Releases locks back to user on failure.
     */
    public function mark_failed($request_id, $admin_id, $error_reason = '')
    {
        $request_id = (int) $request_id;
        $admin_id = (int) $admin_id;

        $row = $this->db->get_where('bman_withdraw_requests', ['id' => $request_id])->row_array();
        if (!$row) {
            return ['error' => 'Request not found'];
        }

        // Validate legal transition: processing → failed
        if ($row['status'] !== 'processing') {
            return ['error' => "Cannot mark failed from status '{$row['status']}'. Only 'processing' requests can fail."];
        }

        $now = date('Y-m-d H:i:s');

        // Release all active locks
        $this->db->update('bman_wallet_ledger', [
            'status' => 'reversed',
            'remark' => "Withdrawal failed - {$error_reason}",
        ], [
            'ref_type' => 'withdrawal',
            'ref_id' => $request_id,
            'entry_type' => 'lock',
            'status' => 'active',
        ]);

        // Update request
        $updated = $this->db->update('bman_withdraw_requests', [
            'status' => 'failed',
            'admin_remark' => $error_reason,
        ], ['id' => $request_id, 'status' => 'processing']);

        if (!$updated) {
            return ['error' => 'Failed to mark as failed (may have been updated by another admin)'];
        }

        $this->log_action($request_id, $admin_id, 'system_failed', 'processing', 'failed', $error_reason);
        return ['success' => true];
    }

    /**
     * Get allocations for a request (which wallet contributed what).
     */
    public function get_allocations($request_id)
    {
        return $this->db->get_where('bman_withdraw_allocations', ['request_id' => (int) $request_id])
            ->result_array();
    }
}
