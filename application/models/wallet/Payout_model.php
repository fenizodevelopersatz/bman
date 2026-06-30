<?php
// defined('BASEPATH') OR exit('No direct script access allowed');

// class Payout_model extends CI_Model
// {
//     public function create_withdraw_request(array $data)
//     {
//         $user_id = (int) ($data['user_id'] ?? 0);
//         $amount = (float) ($data['amount'] ?? 0);
//         $fee = (float) ($data['fee'] ?? 0);
//         $method = strtoupper(trim((string) ($data['method'] ?? 'BANK')));
//         $remark = trim((string) ($data['remark'] ?? ''));

//         if ($user_id <= 0 || $amount <= 0) {
//             return ['success' => false, 'message' => 'Invalid payload'];
//         }

//         // helper: check which table exists
//         $has_withdrawals = $this->db->table_exists('withdrawals');

//         // ✅ create a unique payout id
//         $payout_id = 'WD-' . date('YmdHis') . '-' . $user_id . '-' . mt_rand(100, 999);

//         $now = date('Y-m-d H:i:s');

//         $this->db->trans_start();

//         if ($has_withdrawals) {
//             $insert = [
//                 'user_id' => $user_id,
//                 'amount' => $amount,
//                 'fee' => $fee,
//                 'status' => 'pending',         // match your pending sum query
//                 'method' => $method,
//                 'remark' => $remark,
//                 'type' => 'MANUAL',
//                 'period' => '—',
//                 'payout_id' => $payout_id,
//                 'created_at' => $now,
//             ];
//             $this->db->insert('withdrawals', $insert);
//             $id = (int) $this->db->insert_id();

//         } else {
//             $this->db->trans_complete();
//             return ['success' => false, 'message' => 'No withdraw table found (withdrawals/withdraw_request)'];
//         }

//         // OPTIONAL: if you have a ledger system, you may want to "hold" funds here.
//         // I’m not subtracting wallet balance here because your system likely calculates from a ledger/history.
//         // If you want HOLD logic, tell me your wallet tables (wallet/history/transactions).

//         $this->db->trans_complete();

//         if ($this->db->trans_status() === false) {
//             return ['success' => false, 'message' => 'DB transaction failed'];
//         }

//         return [
//             'success' => true,
//             'insert' => [
//                 'row_id' => $id,
//                 'payout_id' => $payout_id,
//                 'status' => 'PENDING',
//                 'amount' => $amount,
//                 'fee' => $fee,
//                 'method' => $method,
//                 'remark' => $remark,
//                 'created_at' => $now,
//             ],
//         ];
//     }

//     public function get_payout_snapshot($user_id)
//     {
//         $user_id = (int) $user_id;

//         // settings
//         $min_withdraw = (float) str_replace(',', '', site_settings('withdraw_settings', 'min_withdraw'));
//         $max_withdraw = (float) str_replace(',', '', site_settings('withdraw_settings', 'max_withdraw'));
//         $withdraw_fee = (float) str_replace(',', '', site_settings('withdraw_settings', 'withdraw_fee'));
//         $withdraw_daily_limit = (float) str_replace(',', '', site_settings('withdraw_settings', 'withdraw_daily_limit'));
//         $withdraw_monthly_limit = (float) str_replace(',', '', site_settings('withdraw_settings', 'withdraw_monthly_limit'));
//         $withdraw_amount_type = (int) site_settings('withdraw_settings', 'withdraw_amount_type');
//         $auto_withdraw = (int) site_settings('withdraw_settings', 'auto_withdraw');

//         $available_amount = (float) site_wallet_balance($user_id);

//         $pending_amount = 0.0;
//         $paid_total = 0.0;
//         $payouts = [];

//         $has_withdrawals = $this->db->table_exists('withdrawals');

//         if ($has_withdrawals) {
//             $pending_row = $this->db->select('IFNULL(SUM(amount),0) AS s', false)
//                 ->from('withdrawals')
//                 ->where('user_id', $user_id)
//                 ->where_in('status', ['pending', 'processing', 'approved_pending', 'under_review'])
//                 ->get()->row();
//             $pending_amount = (float) ($pending_row->s ?? 0);

//             $paid_row = $this->db->select('IFNULL(SUM(amount),0) AS s', false)
//                 ->from('withdrawals')
//                 ->where('user_id', $user_id)
//                 ->where_in('status', ['paid', 'success', 'completed', 'approved'])
//                 ->get()->row();
//             $paid_total = (float) ($paid_row->s ?? 0);

//             $rows = $this->db->from('withdrawals')
//                 ->where('user_id', $user_id)
//                 ->order_by('id', 'DESC')
//                 ->limit(200)
//                 ->get()->result();

//             foreach ($rows as $r) {
//                 $payouts[] = (object) [
//                     'payout_id' => $r->payout_id ?? ($r->txn_id ?? ('WD-' . $r->id)),
//                     'period' => $r->period ?? '—',
//                     'type' => strtoupper($r->type ?? 'MANUAL'),
//                     'amount' => (float) ($r->amount ?? 0),
//                     'fee' => (float) ($r->fee ?? 0),
//                     'status' => strtoupper($r->status ?? 'PENDING'),
//                     'date' => !empty($r->created_at) ? date('Y-m-d', strtotime($r->created_at)) : date('Y-m-d'),
//                     'note' => $r->remark ?? ($r->note ?? ($r->method ?? '')),
//                     'currency_symbol' => currency_info()->currency_symbol ?? '',
//                 ];
//             }
//         }

//         // since you said all are eligible now:
//         $eligible = true;

//         $payout = (object) [
//             'next_date' => 'Tonight 10:00 PM',
//             'min_withdraw' => $min_withdraw,
//             'processing_fee' => $withdraw_fee,
//             'eligibility' => $eligible,
//             'pending_amount' => $pending_amount,
//             'available_amount' => $available_amount,
//             'paid_total' => $paid_total,
//             'max_withdraw' => $max_withdraw,
//             'daily_limit' => $withdraw_daily_limit,
//             'monthly_limit' => $withdraw_monthly_limit,
//             'amount_type' => $withdraw_amount_type,
//             'auto_withdraw' => $auto_withdraw,
//         ];

//         return ['payout' => $payout, 'payouts' => $payouts];
//     }


//     public function has_active_withdraw_request($user_id)
//     {
//         $user_id = (int) $user_id;

//         $activeStatusesWithdrawals = ['pending', 'processing', 'under_review', 'approved_pending'];
//         $activeStatusesRequests = ['pending', 'processing', 'under_review'];

//         if ($this->db->table_exists('withdrawals')) {
//             $row = $this->db->select('id, payout_id, amount, status, created_at')
//                 ->from('withdrawals')
//                 ->where('user_id', $user_id)
//                 ->where_in('status', $activeStatusesWithdrawals)
//                 ->order_by('id', 'DESC')
//                 ->limit(1)
//                 ->get()->row();

//             return [
//                 'exists' => !empty($row),
//                 'table' => 'withdrawals',
//                 'row' => $row,
//             ];
//         }

//         if ($this->db->table_exists('withdraw_request')) {
//             $row = $this->db->select('id, request_id, amount, status, created_at')
//                 ->from('withdraw_request')
//                 ->where('user_id', $user_id)
//                 ->where_in('status', $activeStatusesRequests)
//                 ->order_by('id', 'DESC')
//                 ->limit(1)
//                 ->get()->row();

//             return [
//                 'exists' => !empty($row),
//                 'table' => 'withdraw_request',
//                 'row' => $row,
//             ];
//         }

//         return ['exists' => false, 'table' => null, 'row' => null];
//     }

// }



defined('BASEPATH') OR exit('No direct script access allowed');

class Payout_model extends CI_Model
{
    public function create_withdraw_request(array $data)
    {
        $user_id = (int) ($data['user_id'] ?? 0);
        $amount = (float) ($data['amount'] ?? 0);
        $fee = (float) ($data['fee'] ?? 0);
        $method = strtoupper(trim((string) ($data['method'] ?? 'BANK')));
        $remark = trim((string) ($data['remark'] ?? ''));

        if ($user_id <= 0 || $amount <= 0) {
            return ['success' => false, 'message' => 'Invalid payload'];
        }

        $has_withdrawals = $this->db->table_exists('withdrawals');
        if (!$has_withdrawals) {
            return ['success' => false, 'message' => 'No withdraw table found (withdrawals/withdraw_request)'];
        }

        $payout_id = 'WD-' . date('YmdHis') . '-' . $user_id . '-' . mt_rand(100, 999);
        $now = date('Y-m-d H:i:s');

        $this->db->trans_start();

        // 1) Insert withdrawals row
        $insert = [
            'user_id' => $user_id,
            'amount' => $amount,
            'fee' => $fee,
            'net_amount' => $fee ? $fee + $amount : $amount,
            'status' => 'pending',
            'method' => $method,
            'remark' => $remark,
            'type' => 'MANUAL',
            'period' => '—',
            'payout_id' => $payout_id,
            'created_at' => $now,
        ];
        $this->db->insert('withdrawals', $insert);
        $id = (int) $this->db->insert_id();

        // 2) Insert into history (your detact_currency logic)
        $notes = $remark ? $remark : ("Withdraw Request ({$method}) - {$payout_id}");
        $this->_detact_currency_history($user_id, $amount, $notes, $payout_id);

        $this->db->trans_complete();

        if ($this->db->trans_status() === false) {
            return ['success' => false, 'message' => 'DB transaction failed'];
        }

        return [
            'success' => true,
            'insert' => [
                'row_id' => $id,
                'payout_id' => $payout_id,
                'status' => 'PENDING',
                'amount' => $amount,
                'fee' => $fee,
                'method' => $method,
                'remark' => $remark,
                'created_at' => $now,
            ],
        ];
    }

    /**
     * ✅ Insert into history like your detect_currency()
     * type = site_withdraw
     */
    private function _detact_currency_history($userid, $amount, $notes, $payout_id = '')
    {
        if ($userid <= 0 || $amount <= 0)
            return false;

        $token_info = token_info();
        $currency_info = currency_info();

        $token_amount = $amount * (float) ($token_info->currency_value ?? 0);

        $deposit_data = [
            "user_id" => $userid,
            "amount" => $amount,
            "type" => 'site_withdraw',
            "date" => date('Y-m-d H:i:s'),
            "history_date" => date('Y-m-d H:i:s'),
            "status" => '1',
            "coin_type" => '1',
            "invest_id" => "",
            "hash_id" => $payout_id ? $payout_id : 'admin withdraw',
            "token_amount" => $token_amount,
            "description" => $notes,
            "coin_id" => $currency_info->id ?? null,
            "token_id" => $token_info->id ?? null,
        ];

        return $this->db->insert("history", $deposit_data);
    }

    public function get_payout_snapshot($user_id)
    {
        $user_id = (int) $user_id;

        // settings
        $min_withdraw = (float) str_replace(',', '', site_settings('withdraw_settings', 'min_withdraw'));
        $max_withdraw = (float) str_replace(',', '', site_settings('withdraw_settings', 'max_withdraw'));
        $withdraw_fee = (float) str_replace(',', '', site_settings('withdraw_settings', 'withdraw_fee'));
        $withdraw_daily_limit = (float) str_replace(',', '', site_settings('withdraw_settings', 'withdraw_daily_limit'));
        $withdraw_monthly_limit = (float) str_replace(',', '', site_settings('withdraw_settings', 'withdraw_monthly_limit'));
        $withdraw_amount_type = (int) site_settings('withdraw_settings', 'withdraw_amount_type');
        $auto_withdraw = (int) site_settings('withdraw_settings', 'auto_withdraw');

        $available_amount = (float) site_wallet_balance($user_id);

        $pending_amount = 0.0;
        $paid_total = 0.0;
        $payouts = [];

        $has_withdrawals = $this->db->table_exists('withdrawals');

        if ($has_withdrawals) {
            $pending_row = $this->db->select('IFNULL(SUM(amount),0) AS s', false)
                ->from('withdrawals')
                ->where('user_id', $user_id)
                ->where_in('status', ['pending', 'processing', 'approved_pending', 'under_review'])
                ->get()->row();
            $pending_amount = (float) ($pending_row->s ?? 0);

            $paid_row = $this->db->select('IFNULL(SUM(amount),0) AS s', false)
                ->from('withdrawals')
                ->where('user_id', $user_id)
                ->where_in('status', ['paid', 'success', 'completed', 'approved'])
                ->get()->row();
            $paid_total = (float) ($paid_row->s ?? 0);

            $rows = $this->db->from('withdrawals')
                ->where('user_id', $user_id)
                ->order_by('id', 'DESC')
                ->limit(200)
                ->get()->result();

            foreach ($rows as $r) {
                $payouts[] = (object) [
                    'payout_id' => $r->payout_id ?? ('WD-' . $r->id),
                    'period' => $r->period ?? '—',
                    'type' => strtoupper($r->type ?? 'MANUAL'),
                    'amount' => (float) ($r->amount ?? 0),
                    'fee' => (float) ($r->fee ?? 0),
                    'status' => strtoupper($r->status ?? 'PENDING'),
                    'date' => !empty($r->created_at) ? date('Y-m-d', strtotime($r->created_at)) : date('Y-m-d'),
                    'note' => $r->remark ?? ($r->method ?? ''),
                    'currency_symbol' => currency_info()->currency_symbol ?? '',
                ];
            }
        }

        // ✅ Fetch full user history (latest 200)
        $history = $this->get_user_history($user_id, 200);

        $eligible = true;

        $payout = (object) [
            'next_date' => 'Tonight 10:00 PM',
            'min_withdraw' => $min_withdraw,
            'processing_fee' => $withdraw_fee,
            'eligibility' => $eligible,
            'pending_amount' => $pending_amount,
            'available_amount' => $available_amount,
            'paid_total' => $paid_total,
            'max_withdraw' => $max_withdraw,
            'daily_limit' => $withdraw_daily_limit,
            'monthly_limit' => $withdraw_monthly_limit,
            'amount_type' => $withdraw_amount_type,
            'auto_withdraw' => $auto_withdraw,
        ];

        return ['payout' => $payout, 'payouts' => $payouts, 'history' => $history];
    }

    /**
     * ✅ All history for user (latest N)
     * You can filter types here if you want (profit, commission, site_withdraw, etc.)
     */
    public function get_user_history($user_id, $limit = 200)
    {
        $user_id = (int) $user_id;
        $limit = (int) $limit;

        $rows = $this->db->select('id,user_id,type,amount,token_amount,coin_type,status,description,hash_id,invest_id,history_date,date')
            ->from('history')
            ->where('user_id', $user_id)
            ->order_by('id', 'DESC')
            ->limit($limit)
            ->get()->result();

        return $rows ?: [];
    }

    public function has_active_withdraw_request($user_id)
    {
        $user_id = (int) $user_id;

        $activeStatusesWithdrawals = ['pending', 'processing', 'under_review', 'approved_pending'];

        if ($this->db->table_exists('withdrawals')) {
            $row = $this->db->select('id, payout_id, amount, status, created_at')
                ->from('withdrawals')
                ->where('user_id', $user_id)
                ->where_in('status', $activeStatusesWithdrawals)
                ->order_by('id', 'DESC')
                ->limit(1)
                ->get()->row();

            return [
                'exists' => !empty($row),
                'table' => 'withdrawals',
                'row' => $row,
            ];
        }

        return ['exists' => false, 'table' => null, 'row' => null];
    }
}
