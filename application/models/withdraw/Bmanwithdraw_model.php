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
        $allowed_exchange = site_settings('withdraw_settings', 'withdraw_allowed_exchange');
        $allowed_earning = site_settings('withdraw_settings', 'withdraw_allowed_earning');
        $allowed_staking = site_settings('withdraw_settings', 'withdraw_allowed_staking');
        $allowed_bonus = site_settings('withdraw_settings', 'withdraw_allowed_bonus');
        $fee = site_settings('withdraw_settings', 'withdraw_fee_usdt');
        if ($fee === null || $fee === '' || $fee === false) {
            $fee = site_settings('withdraw_settings', 'withdraw_fee');
        }

        return [
            'withdraw_status' => (int) site_settings('withdraw_settings', 'withdraw_status'),
            'min_withdraw' => (float) str_replace(',', '', (string) site_settings('withdraw_settings', 'min_withdraw')),
            'max_withdraw' => (float) str_replace(',', '', (string) site_settings('withdraw_settings', 'max_withdraw')),
            'withdraw_fee' => (float) str_replace(',', '', (string) $fee),
            'withdraw_daily_limit' => (float) str_replace(',', '', (string) site_settings('withdraw_settings', 'withdraw_daily_limit')),
            'withdraw_monthly_limit' => (float) str_replace(',', '', (string) site_settings('withdraw_settings', 'withdraw_monthly_limit')),
            'withdraw_amount_type' => (int) site_settings('withdraw_settings', 'withdraw_amount_type'),
            'auto_withdraw' => (int) site_settings('withdraw_settings', 'auto_withdraw'),
            'withdraw_allowed_exchange' => ($allowed_exchange === null || $allowed_exchange === '' || $allowed_exchange === false) ? 1 : (int) $allowed_exchange,
            'withdraw_allowed_earning' => ($allowed_earning === null || $allowed_earning === '' || $allowed_earning === false) ? 1 : (int) $allowed_earning,
            'withdraw_allowed_staking' => ($allowed_staking === null || $allowed_staking === '' || $allowed_staking === false) ? 0 : (int) $allowed_staking,
            'withdraw_allowed_bonus' => ($allowed_bonus === null || $allowed_bonus === '' || $allowed_bonus === false) ? 0 : (int) $allowed_bonus,
        ];
    }

    public function wallet_snapshot($user_id)
    {
        return $this->maturity_breakdown($user_id);
    }

    /**
     * Exchange Wallet withdrawable balance for BMAN-to-USDT payout conversion.
     * It subtracts active withdrawal locks/debits through WalletMaturity_model.
     * This is the source of truth for the available Exchange balance.
     * Bonus, earning and staking wallets stay visible but are not valid
     * withdrawal conversion sources.
     *
     * Returns a numeric string (BCMath scale 8). Cast to float only where you
     * display it.
     */
    public function available_balance($user_id)
    {
        return $this->wallet_balance((int) $user_id, 'exchange');
    }

    /**
     * Ledger-based balances per wallet (source of truth for withdrawal).
     * Returns total, locked, matured, holds and withdrawable for each BMAN
     * wallet. `_holds` is exposed so callers that spend INSIDE the platform
     * (re-staking) can subtract pending-withdrawal holds without also
     * subtracting the maturity lock, which only gates withdrawals off-platform.
     */
    public function maturity_breakdown($user_id)
    {
        $breakdowns = $this->maturity->all_breakdowns($user_id);
        $flat = [];
        foreach ($breakdowns as $wallet => $b) {
            $flat[$wallet] = $b['total'];
            $flat[$wallet . '_locked'] = $b['locked'];
            $flat[$wallet . '_matured'] = $b['matured'];
            $flat[$wallet . '_holds'] = $b['holds'];
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
     * 'approved' is dual-meaning (see approve_and_complete() vs the legacy
     * approve()): terminal/paid under the cron flow (tx_hash set) but still
     * an in-flight intermediate step under the legacy manual flow (no
     * tx_hash yet). tx_hash presence is the discriminator, not the string.
     */
    public function user_totals($user_id)
    {
        $user_id = (int) $user_id;

        $pending_row = $this->db->select('IFNULL(SUM(request_amount),0) AS s', false)
            ->from('bman_withdraw_requests')
            ->where('user_id', $user_id)
            ->group_start()
                ->where_in('status', ['pending', 'processing'])
                ->or_group_start()
                    ->where('status', 'approved')
                    ->where('tx_hash IS NULL', null, false)
                ->group_end()
            ->group_end()
            ->get()->row();

        $paid_row = $this->db->select('IFNULL(SUM(request_amount),0) AS s', false)
            ->from('bman_withdraw_requests')
            ->where('user_id', $user_id)
            ->group_start()
                ->where('status', 'completed')
                ->or_group_start()
                    ->where('status', 'approved')
                    ->where('tx_hash IS NOT NULL', null, false)
                ->group_end()
            ->group_end()
            ->get()->row();

        return [
            'pending' => (float) ($pending_row->s ?? 0),
            'paid' => (float) ($paid_row->s ?? 0),
        ];
    }

    public function limit_usage($user_id)
    {
        $user_id = (int) $user_id;
        $statuses = ['pending', 'approved', 'processing', 'under_review', 'completed'];

        return [
            'daily_usdt' => (float) $this->_sum_requested_usdt_between($user_id, date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59'), $statuses),
            'monthly_usdt' => (float) $this->_sum_requested_usdt_between($user_id, date('Y-m-01 00:00:00'), date('Y-m-t 23:59:59'), $statuses),
        ];
    }

    private function _sum_requested_usdt_between($user_id, $from, $to, array $statuses)
    {
        $row = $this->db->select('IFNULL(SUM(CASE WHEN bman_usdt_rate > 0 THEN (request_amount * bman_usdt_rate) ELSE usdt_amount END),0) AS s', false)
            ->from('bman_withdraw_requests')
            ->where('user_id', (int) $user_id)
            ->where('created_at >=', $from)
            ->where('created_at <=', $to)
            ->where_in('status', $statuses)
            ->get()
            ->row_array();

        return (string) ($row['s'] ?? '0');
    }

    /**
     * User history shaped for the Payouts page table + detail modal
     * (payout_id/amount/fee/date/... field names the view already expects).
     * 'mixed' requests show their per-wallet split (from bman_withdraw_allocations)
     * in the period column instead of a single wallet name.
     */
    public function user_payout_history($user_id, $limit = 200)
    {
        $rows = $this->user_history($user_id, $limit);
        $symbol = currency_info()->currency_symbol ?? '';

        $ids = array_column($rows, 'id');
        $allocations_by_request = [];
        if ($ids) {
            $alloc_rows = $this->db->select('request_id, wallet, amount')
                ->from('bman_withdraw_allocations')
                ->where_in('request_id', $ids)
                ->get()->result_array();
            foreach ($alloc_rows as $a) {
                $allocations_by_request[$a['request_id']][] = $a;
            }
        }

        $out = [];
        foreach ($rows as $r) {
            $allocations = $allocations_by_request[$r['id']] ?? [];
            if ($allocations) {
                $parts = [];
                foreach ($allocations as $a) {
                    $parts[] = ucfirst($a['wallet']) . ' ' . number_format((float) $a['amount'], 2);
                }
                $period = implode(' + ', $parts);
            } else {
                $period = ucfirst($r['source_wallet']) . ' Wallet';
            }

            $out[] = (object) [
                'payout_id' => $r['request_no'],
                'txn_id' => $r['tx_hash'] ?? null,
                // The member's own BMAN leaving their wallet — the on-chain
                // hash that was missing from this view. Deliberately not
                // exposing gas_tx_hash here: that's the internal treasury→
                // user BNB gas-funding leg, not something the member did or
                // needs to verify. refund_tx_hash lets a rejected member
                // confirm their BMAN genuinely came back on-chain.
                'onchain_hash' => $r['collect_tx_hash'] ?? null,
                'refund_tx_hash' => $r['refund_tx_hash'] ?? null,
                'user_id' => $r['user_id'],

                'amount' => (float) $r['request_amount'],
                'fee' => (float) $r['fee_amount'],
                'net_amount' => (float) $r['net_amount'],
                'usdt_amount' => (float) ($r['usdt_amount'] ?? 0),

                'status' => strtoupper($r['status']),
                'method' => 'BMAN',
                'type' => 'MANUAL',
                'period' => $period,

                'remark' => $r['remark'],
                'note' => $r['remark'] ?: ($r['admin_remark'] ?? ''),

                'admin_review' => $r['admin_remark'] ?? null,
                'approved_at' => $r['approved_at'] ?? null,
                'admin_proof_img' => null,

                'date' => !empty($r['created_at']) ? date('Y-m-d', strtotime($r['created_at'])) : '—',
                'created_at' => !empty($r['created_at']) ? date('d M Y H:i', strtotime($r['created_at'])) : '—',
                'currency_symbol' => $symbol,
            ];
        }
        return $out;
    }

    /**
     * Returns the latest open withdrawal request for the user.
     * Open means it is still in the admin workflow and should block new requests.
     * 'approved' only counts as open while tx_hash is unset (legacy manual
     * flow, still awaiting payout) — under the cron flow 'approved' is
     * already terminal/paid (tx_hash set by approve_and_complete()) and
     * must NOT block a new request.
     */
    public function open_request($user_id, $source_wallet = 'bman')
    {
        $this->db->select('wr.*, u.username, u.referral_id')
            ->from('bman_withdraw_requests wr')
            ->join('users u', 'u.id = wr.user_id', 'left')
            ->where('wr.user_id', (int) $user_id);

        if ($source_wallet !== null && $source_wallet !== '' && $source_wallet !== 'any') {
            $this->db->where('wr.source_wallet', $source_wallet);
        }

        return $this->db->group_start()
                ->where_in('wr.status', ['pending', 'processing', 'under_review'])
                ->or_group_start()
                    ->where('wr.status', 'approved')
                    ->where('wr.tx_hash IS NULL', null, false)
                ->group_end()
            ->group_end()
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
        $source_wallet = strtolower(trim((string) ($data['source_wallet'] ?? 'exchange')));
        $request_amount = Money::floor8($data['request_amount']);
        $withdraw_address = trim((string) $data['withdraw_address']);
        $bman_usdt_rate = isset($data['bman_usdt_rate'])
            ? Money::floor8($data['bman_usdt_rate'])
            : Money::div('1', (string) (token_info()->currency_value ?: '1'));
        $fee_amount = Money::floor8($data['fee_amount'] ?? '0');
        $gross_usdt = Money::floor6(Money::mul($request_amount, $bman_usdt_rate));
        $net_amount = isset($data['net_amount'])
            ? Money::floor6($data['net_amount'])
            : Money::floorZero(Money::floor6(Money::sub($gross_usdt, $fee_amount)));
        // USDT payout always rounds DOWN to 6dp — never let a user extract dust
        // the platform never had by rounding the amount they receive upward.
        $usdt_amount = $net_amount;

        if ($source_wallet !== 'exchange') {
            return ['error' => 'Only Exchange Wallet BMAN can be converted to USDT withdrawal.'];
        }

        // Validate amount > 0
        if (Money::cmp($request_amount, '0') <= 0) {
            return ['error' => 'Amount must be greater than 0'];
        }

        if (Money::cmp($net_amount, '0') <= 0) {
            return ['error' => 'Withdrawal amount too small. Net USDT payout becomes zero.'];
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

        // Check available balance (matured, unlocked) — read fresh here, inside
        // whatever transaction the caller has open, never a value carried
        // from earlier in the request.
        if ($source_wallet === 'mixed') {
            $available = $this->available_balance($user_id);
        } else {
            $available = $this->wallet_balance($user_id, $source_wallet);
        }

        if (Money::cmp($available, $request_amount) < 0) {
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
            // 'processing': the request is live and BmanWithdrawCollectCron will
            // claim it (see claim_for_collection()). Deliberately NOT 'pending' —
            // 'pending' is reserved for post-collection, awaiting-admin-decision
            // (see confirm_collected()).
            'status' => 'processing',
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
     *
     * Dust-safe: every slice is truncated to 8dp and subtracted via BCMath, so
     * the slices sum to $total_amount to the last satoshi. isZero($remaining)
     * is the safety net — if it's ever false, ROLLBACK rather than leave a
     * lock that doesn't add up (the caller runs this inside a transaction).
     */
    private function _lock_allocate($user_id, $request_id, $total_amount)
    {
        $priority = ['bonus', 'earning', 'exchange', 'staking'];
        $remaining = Money::floor8($total_amount);
        $now = date('Y-m-d H:i:s');

        foreach ($priority as $wallet) {
            if (Money::isZero($remaining)) break;

            // Get withdrawable balance for this wallet (matured, unlocked)
            $available = $this->wallet_balance($user_id, $wallet);
            $to_lock = Money::cmp($available, $remaining) < 0 ? $available : $remaining;
            $to_lock = Money::floor8($to_lock);

            if (Money::cmp($to_lock, '0') > 0) {
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

                $remaining = Money::floorZero(Money::sub($remaining, $to_lock));
            }
        }

        if (!Money::isZero($remaining)) {
            return ['error' => 'Could not allocate full amount across wallets'];
        }

        return ['success' => true];
    }

    public function get_request($id)
    {
        return $this->db->select('wr.*, u.username, u.email, u.referral_id, u.status AS user_status')
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

    /** Full status-change audit trail for one request, oldest first. */
    public function history($request_id)
    {
        return $this->db->where('request_id', (int) $request_id)
            ->order_by('id', 'ASC')
            ->get('withdraw_audit_log')
            ->result_array();
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

        // Legacy manual flow only: approved -> processing (mark_processing()) ->
        // completed here, lock still active. The cron-collected flow no longer
        // passes through here — see approve_and_complete() for pending -> approved.
        if ($row['status'] !== 'processing') {
            return ['error' => "Cannot complete from status '{$row['status']}'. Only 'processing' requests can be completed."];
        }

        $now = date('Y-m-d H:i:s');

        // Convert locks to debits (each lock row becomes a debit row). A
        // request that already went through the collection cron has none —
        // confirm_collected() already did this at collection time — so this
        // is a no-op for that path and only fires for the legacy manual flow.
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
        ], ['id' => $request_id, 'status' => $row['status']]);

        if (!$updated) {
            return ['error' => 'Failed to complete (may have been updated by another admin)'];
        }

        $this->log_action($request_id, $admin_id, 'admin_complete', $row['status'], 'completed', "tx_hash: {$tx_hash}");
        return ['success' => true];
    }

    /**
     * Send the collected BMAN back on-chain, treasury -> user's custodial
     * wallet, as part of rejecting an already-collected request. Called by
     * the controller BEFORE reject() touches the DB — a broadcast can't be
     * rolled back, so we don't want a later DB failure to leave "sent but
     * nothing recorded". Idempotent: returns the existing refund_tx_hash if
     * this was already called for the request (e.g. a retried click).
     *
     * Mirrors whether the ORIGINAL collection was real or simulated — not
     * the current token_settings dry-run flag, which may have changed since
     * collection. BmanWithdrawCollectCron never calls sendToken() in
     * dry-run (see _broadcast()): a 'DRYRUN-' collect_tx_hash means no real
     * BMAN ever left the user's wallet, so nothing real needs reversing.
     */
    public function refund_bman_onchain($request_id)
    {
        $request_id = (int) $request_id;
        $row = $this->db->get_where('bman_withdraw_requests', ['id' => $request_id])->row_array();
        if (!$row) return ['error' => 'Request not found'];
        if ((int) $row['collect_cron_status'] !== 1) return ['error' => 'Request was never collected on-chain — nothing to refund'];
        if (!empty($row['refund_tx_hash'])) return ['success' => true, 'tx_hash' => $row['refund_tx_hash']];

        $this->load->model('Tokenmaster_model', 'tokens');
        $this->load->model('Custodialwallet_model', 'custodial');

        $cfg = $this->db->get_where('token_settings', ['status' => 1])->row_array() ?: [];
        $treasuryWallet = trim((string) ($cfg['treasury_wallet'] ?? ''));
        $bmanContract = trim((string) ($cfg['bman_contract'] ?? ''));
        if ($treasuryWallet === '') return ['error' => 'Treasury wallet not configured'];

        $userWallet = $this->custodial->ensureAddress((int) $row['user_id']);
        $userAddress = $userWallet['wallet_address'] ?? null;
        if (!$userAddress) return ['error' => "Could not resolve user #{$row['user_id']}'s custodial wallet"];

        // Mirrors whether the ORIGINAL collection was real or simulated —
        // not the current token_settings dry-run flag, which may have
        // changed since collection. BmanWithdrawCollectCron never calls
        // sendToken() in dry-run (see its _broadcast()): a 'DRYRUN-'
        // collect_tx_hash means no real BMAN ever left the user's wallet,
        // so nothing real needs reversing here either.
        $wasDryRunCollection = strpos((string) ($row['collect_tx_hash'] ?? ''), 'DRYRUN') === 0;

        if ($wasDryRunCollection) {
            $hash = 'DRYRUN-refund-' . $row['request_no'];
        } else {
            if ($bmanContract === '') return ['error' => 'BMAN contract not configured'];

            $treasuryKey = $this->tokens->treasuryPrivateKey();
            if (!$treasuryKey) return ['error' => 'Treasury key unavailable'];

            $this->load->library('web3bman');
            try {
                $sent = $this->web3bman->sendToken($treasuryKey, $userAddress, (string) $row['request_amount'], $bmanContract);
                $hash = $sent['tx_hash'] ?? null;
                if (empty($hash)) return ['error' => 'Empty tx hash from refund broadcast'];
            } catch (Exception $e) {
                return ['error' => 'Refund broadcast failed: ' . $e->getMessage()];
            }

            $this->load->model('GasFeeSettings_model', 'gasSettings');
            $this->load->model('GasFeeLedger_model', 'gasLedger');
            $policy = $this->gasSettings->resolve('token_transfer');
            $this->gasLedger->recordBroadcast(
                'refund', 'bman_withdrawal', $row['request_no'], $row['user_id'],
                $hash, $treasuryWallet, $userAddress, $policy
            );
        }

        $this->db->insert('onchain_transactions', [
            'tx_hash' => $hash, 'wallet_type' => $row['source_wallet'], 'tx_type' => 'withdrawal_refund',
            'status' => 'processing',
            'from_address' => strtolower($treasuryWallet),
            'to_address' => strtolower($userAddress),
            'user_id' => $row['user_id'], 'amount' => $row['request_amount'],
            'reference_type' => 'bman_withdrawal', 'reference_id' => (string) $request_id,
            'linked_withdrawal_id' => $request_id,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $onchainId = (int) $this->db->insert_id();
        if ($onchainId && !$wasDryRunCollection) {
            $this->load->model('GasFeeLedger_model', 'gasLedger');
            $this->gasLedger->linkOnchainTx($hash, $onchainId);
        }

        return ['success' => true, 'tx_hash' => $hash];
    }

    /**
     * Reject a request. Legal: pending/approved → rejected
     *
     * bman_wallet_ledger, not user_wallets.exchange_balance, is what
     * actually gates availability here: WalletMaturity_model::withdrawable()
     * computes `total (raw user_wallets column, a lifetime-cumulative figure
     * that a withdrawal never touches) MINUS holds (SUM of bman_wallet_ledger
     * rows with entry_type IN ('lock','debit') AND status='active')`. So
     * "removing" BMAN for a withdrawal — whether still just locked, or
     * already collected-and-debited — always means placing/keeping an active
     * hold row here, never touching the raw total directly. Confirmed against
     * a real request: crediting exchange_balance directly (an earlier version
     * of this method) double-counted — the debit hold was still active AND
     * subtracting from availability, netting only against the credit by
     * coincidence in the withdrawable formula, while exchange_balance itself
     * was left permanently, visibly inflated.
     *
     * So both reversal paths are the SAME mechanic — reverse whatever active
     * bman_wallet_ledger row(s) exist for this request — just at different
     * points in the lifecycle:
     *   - not yet collected: an active 'lock' row.
     *   - already collected (collect_cron_status=1, via confirm_collected()):
     *     an active 'debit' row.
     * collect_cron_status, not the status string, is the discriminator here —
     * 'pending' means "not yet collected" for a legacy pre-migration row but
     * "already collected, awaiting admin decision" for the cron flow, so the
     * status string alone can't tell them apart.
     */
    public function reject($request_id, $admin_id, $admin_remark = '', $refund_tx_hash = null)
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
        $wasCollected = ((int) $row['collect_cron_status'] === 1);

        // For a collected request, the caller (controller) must have already
        // sent the on-chain refund via refund_bman_onchain() and passed its
        // tx_hash here — the broadcast can't happen inside this DB update.
        if ($wasCollected && empty($refund_tx_hash)) {
            return ['error' => 'Refund transaction hash is required to reject an already-collected request'];
        }

        // Release whichever active hold exists — 'lock' pre-collection,
        // 'debit' post-collection. Same reversal either way.
        $this->db->update('bman_wallet_ledger', [
            'status' => 'reversed',
            'remark' => $wasCollected
                ? "Withdrawal rejected after on-chain collection - refunded on-chain ({$refund_tx_hash}) - {$admin_remark}"
                : "Withdrawal rejected - {$admin_remark}",
        ], [
            'ref_type' => 'withdrawal',
            'ref_id' => $request_id,
            'entry_type' => $wasCollected ? 'debit' : 'lock',
            'status' => 'active',
        ]);

        // Update request
        $updated = $this->db->update('bman_withdraw_requests', array_merge([
            'status' => 'rejected',
            'approved_by' => $admin_id,
            'approved_at' => $now,
            'admin_remark' => $admin_remark,
        ], $wasCollected ? ['refunded_at' => $now, 'refund_tx_hash' => $refund_tx_hash] : []), ['id' => $request_id, 'status' => $row['status']]);

        if (!$updated) {
            return ['error' => 'Failed to reject (may have been updated by another admin)'];
        }

        $this->log_action($request_id, $admin_id, 'admin_reject', $row['status'], 'rejected',
            $wasCollected ? trim($admin_remark . ' [BMAN refunded on-chain, tx_hash: ' . $refund_tx_hash . ']') : $admin_remark);
        return ['success' => true, 'refund_tx_hash' => $refund_tx_hash];
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

    /* =====================================================================
     * BmanWithdrawCollectCron support — cron-only, no admin/user click
     * involved. Mirrors the shape of StakingPurchasecron's order loop, just
     * with two legs (gas, collect) instead of four.
     * ===================================================================== */

    /**
     * Requests the cron still has work to do on: not yet both legs confirmed,
     * not terminal. Deliberately status = 'processing' AND approved_at IS
     * NULL — NEVER just any 'processing' row, since 'processing' is ALSO the
     * separate legacy admin-manual status reached via approved → processing
     * (mark_processing()). Every legacy 'processing' row went through
     * approve() first, which always sets approved_at; a fresh cron-owned
     * request never has it set at this point. Matching on status alone here
     * would sweep up any request an admin already started handling by hand
     * under the old process (this exact bug bit request #2 before the
     * approved_at guard was added — see docs/2026-08-12_bman_withdraw_collect_cron.md).
     */
    public function claim_for_collection($limit = 25)
    {
        return $this->db->select(
                'id, request_no, user_id, source_wallet, request_amount, status, ' .
                'gas_cron_status, gas_tx_hash, collect_cron_status, collect_tx_hash'
            )
            ->group_start()
                ->where('gas_cron_status', 0)->or_where('collect_cron_status', 0)
            ->group_end()
            ->where('status', 'processing')
            ->where('approved_at', null)
            ->order_by('id', 'ASC')->limit((int) $limit)
            ->get('bman_withdraw_requests')->result_array();
    }

    /** Generic column updater for the cron's own tracking fields. */
    public function set_cron_fields($request_id, array $data)
    {
        $this->db->where('id', (int) $request_id)->update('bman_withdraw_requests', $data);
    }

    /**
     * BMAN collection leg confirmed on-chain: convert the active lock(s) into
     * a real debit (same mechanic complete() uses) NOW, since the BMAN has
     * genuinely left the platform's custody at this point — waiting until
     * admin approval to record that would leave the ledger showing BMAN the
     * user no longer actually has. Moves the request to 'pending' (awaiting
     * the admin's approve/reject decision).
     */
    public function confirm_collected($request_id, $tx_hash)
    {
        $request_id = (int) $request_id;
        $row = $this->db->get_where('bman_withdraw_requests', ['id' => $request_id])->row_array();
        if (!$row) return ['error' => 'Request not found'];

        $now = date('Y-m-d H:i:s');

        $locks = $this->db->get_where('bman_wallet_ledger', [
            'ref_type' => 'withdrawal', 'ref_id' => $request_id,
            'entry_type' => 'lock', 'status' => 'active',
        ])->result_array();

        foreach ($locks as $lock) {
            $this->db->update('bman_wallet_ledger', [
                'status' => 'reversed',
                'remark' => "BMAN collected on-chain at {$now}",
            ], ['id' => $lock['id']]);

            $this->db->insert('bman_wallet_ledger', [
                'user_id' => $row['user_id'],
                'wallet' => $lock['wallet'],
                'entry_type' => 'debit',
                'ref_type' => 'withdrawal',
                'ref_id' => $request_id,
                'amount' => $lock['amount'],
                'status' => 'active',
                'remark' => "BMAN collected on-chain - {$tx_hash}",
                'created_at' => $now,
            ]);
        }

        $this->db->where('id', $request_id)->update('bman_withdraw_requests', [
            'status' => 'pending',
            'collect_cron_status' => 1,
            'collect_cron_status_message' => null,
            'collected_at' => $now,
        ]);

        $this->log_action($request_id, 0, 'cron_collected', $row['status'], 'pending', "collect_tx_hash: {$tx_hash}");
        return ['success' => true];
    }

    /**
     * Admin approves a cron-collected request: pay the USDT manually
     * (unchanged — reveal treasury key, send externally, paste tx_hash) and
     * close it out in one step. Legal: pending → approved.
     *
     * No lock→debit conversion here — confirm_collected() already did that
     * at collection time, since 'pending' under this flow is only ever
     * reached post-collection (collect_cron_status=1). Unlike complete(),
     * this never touches bman_wallet_ledger.
     */
    public function approve_and_complete($request_id, $admin_id, $tx_hash, $admin_remark = '')
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

        if ($row['status'] !== 'pending') {
            return ['error' => "Cannot approve from status '{$row['status']}'. Only 'pending' requests can be approved."];
        }

        $now = date('Y-m-d H:i:s');
        $updated = $this->db->update('bman_withdraw_requests', [
            'status' => 'approved',
            'tx_hash' => $tx_hash,
            'approved_by' => $admin_id,
            'approved_at' => $now,
            'completed_at' => $now,
            'admin_remark' => $admin_remark,
        ], ['id' => $request_id, 'status' => 'pending']);

        if (!$updated) {
            return ['error' => 'Failed to approve (may have been updated by another admin)'];
        }

        $this->log_action($request_id, $admin_id, 'admin_approve_complete', 'pending', 'approved', "tx_hash: {$tx_hash}; {$admin_remark}");
        return ['success' => true];
    }
}
