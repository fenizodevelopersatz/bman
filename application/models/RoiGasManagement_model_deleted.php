<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * ROI Gas Management Model
 * Handles gas fees and failed transaction retries
 */
class RoiGasManagement_model extends CI_Model
{
    /**
     * Record gas fee for ROI transaction
     */
    public function recordGasFee($data)
    {
        $gas_data = [
            'user_id'         => (int)$data['user_id'],
            'roi_audit_id'    => (int)$data['roi_audit_id'],
            'transaction_type' => $data['transaction_type'],
            'gas_fee_usdt'    => (float)$data['gas_fee_usdt'],
            'gas_fee_bman'    => (float)($data['gas_fee_bman'] ?? 0),
            'gas_price_gwei'  => (float)($data['gas_price_gwei'] ?? 0),
            'gas_limit'       => (int)($data['gas_limit'] ?? 0),
            'block_number'    => (int)($data['block_number'] ?? 0),
            'tx_hash'         => $data['tx_hash'] ?? null,
            'status'          => $data['status'] ?? 'pending',
            'payment_date'    => date('Y-m-d')
        ];

        $this->db->insert('roi_gas_fees', $gas_data);
        return $this->db->insert_id();
    }

    /**
     * Get current gas budget status
     */
    public function getGasBudgetStatus()
    {
        $today = date('Y-m-d');
        $current_month_start = date('Y-m-01');

        return [
            'daily' => $this->db
                ->where('budget_type', 'daily')
                ->where('period_start', $today)
                ->get('roi_gas_budget')
                ->row_array(),
            'monthly' => $this->db
                ->where('budget_type', 'monthly')
                ->where('period_start', $current_month_start)
                ->get('roi_gas_budget')
                ->row_array()
        ];
    }

    /**
     * Check if gas fee budget exceeded
     */
    public function isBudgetExceeded($amount_usdt)
    {
        $budget = $this->getGasBudgetStatus();

        if (!empty($budget['daily'])) {
            $remaining = (float)$budget['daily']['remaining_usdt'];
            if ($remaining < $amount_usdt) {
                return ['exceeded' => true, 'type' => 'daily', 'remaining' => $remaining];
            }
        }

        if (!empty($budget['monthly'])) {
            $remaining = (float)$budget['monthly']['remaining_usdt'];
            if ($remaining < $amount_usdt) {
                return ['exceeded' => true, 'type' => 'monthly', 'remaining' => $remaining];
            }
        }

        return ['exceeded' => false];
    }

    /**
     * Log failed transaction for retry
     */
    public function logFailedTransaction($data)
    {
        $failed_data = [
            'user_id'          => (int)$data['user_id'],
            'roi_audit_id'     => (int)$data['roi_audit_id'],
            'roi_amount'       => (float)$data['roi_amount'],
            'failure_reason'   => $data['failure_reason'],
            'failure_code'     => $data['failure_code'] ?? null,
            'gas_fee_issue'    => (int)($data['gas_fee_issue'] ?? 0),
            'max_retries'      => 3,
            'next_retry_at'    => date('Y-m-d H:i:s', strtotime('+6 hours')),
            'status'           => 'failed'
        ];

        $this->db->insert('roi_failed_transactions', $failed_data);
        return $this->db->insert_id();
    }

    /**
     * Get failed transactions ready for retry
     */
    public function getFailedTransactionsForRetry()
    {
        $now = date('Y-m-d H:i:s');

        return $this->db
            ->select('rft.*, rda.roi_amount, rda.plan_type, rda.user_id')
            ->from('roi_failed_transactions rft')
            ->join('roi_distribution_audit rda', 'rft.roi_audit_id = rda.id', 'left')
            ->where('rft.status', 'pending_retry')
            ->where('rft.next_retry_at <=', $now)
            ->where('rft.retry_count <', 3)
            ->order_by('rft.next_retry_at', 'ASC')
            ->limit(50)
            ->get()
            ->result_array();
    }

    /**
     * Update failed transaction retry count
     */
    public function updateFailedTransactionRetry($id, $retry_count)
    {
        $next_retry = date('Y-m-d H:i:s', strtotime('+' . (($retry_count + 1) * 2) . ' hours'));

        $this->db->update('roi_failed_transactions', [
            'retry_count'    => $retry_count + 1,
            'last_retry_at'  => date('Y-m-d H:i:s'),
            'next_retry_at'  => $next_retry,
            'status'         => $retry_count >= 2 ? 'pending_retry' : 'pending_retry'
        ], ['id' => (int)$id]);

        return $this->db->affected_rows();
    }

    /**
     * Mark failed transaction as resolved
     */
    public function markFailedTransactionResolved($id, $notes = '')
    {
        $this->db->update('roi_failed_transactions', [
            'status'        => 'resolved',
            'resolved_at'   => date('Y-m-d H:i:s'),
            'resolution_notes' => $notes
        ], ['id' => (int)$id]);

        return $this->db->affected_rows();
    }

    /**
     * Get gas fee summary
     */
    public function getGasFeeSummary($from_date = null, $to_date = null)
    {
        $from = $from_date ?? date('Y-m-d', strtotime('-30 days'));
        $to   = $to_date ?? date('Y-m-d');

        return $this->db
            ->select('
                DATE(payment_date) as payment_date,
                COUNT(*) as count,
                SUM(gas_fee_usdt) as total_gas_usdt,
                SUM(gas_fee_bman) as total_gas_bman,
                status
            ')
            ->where('DATE(payment_date) >=', $from)
            ->where('DATE(payment_date) <=', $to)
            ->group_by('payment_date', 'status')
            ->order_by('payment_date', 'DESC')
            ->get('roi_gas_fees')
            ->result_array();
    }

    /**
     * Get user gas fee history
     */
    public function getUserGasFeeHistory($user_id, $limit = 50)
    {
        return $this->db
            ->select('rgf.*, rda.roi_amount, rda.plan_type, rda.roi_type')
            ->from('roi_gas_fees rgf')
            ->join('roi_distribution_audit rda', 'rgf.roi_audit_id = rda.id', 'left')
            ->where('rgf.user_id', (int)$user_id)
            ->order_by('rgf.payment_date', 'DESC')
            ->limit($limit)
            ->get()
            ->result_array();
    }

    /**
     * Get pending gas fees (not yet paid)
     */
    public function getPendingGasFees()
    {
        return $this->db
            ->where('status', 'pending')
            ->where('DATE(payment_date) <=', date('Y-m-d', strtotime('-1 day')))
            ->order_by('payment_date', 'ASC')
            ->get('roi_gas_fees')
            ->result_array();
    }

    /**
     * Deduct gas fees from admin hot wallet
     */
    public function deductGasFee($user_id, $gas_fee_usdt, $gas_fee_bman = 0)
    {
        // This would be implemented based on your wallet system
        // For now, just log it
        $this->db->update('roi_gas_fees', [
            'status' => 'paid',
            'paid_at' => date('Y-m-d H:i:s')
        ], ['user_id' => $user_id, 'status' => 'pending']);

        return true;
    }

    /**
     * Get failed transactions statistics
     */
    public function getFailedTransactionStats()
    {
        return [
            'total_failed' => $this->db
                ->where('status', 'failed')
                ->count_all_results('roi_failed_transactions'),
            'pending_retry' => $this->db
                ->where('status', 'pending_retry')
                ->count_all_results('roi_failed_transactions'),
            'resolved' => $this->db
                ->where('status', 'resolved')
                ->count_all_results('roi_failed_transactions'),
            'by_reason' => $this->db
                ->select('failure_code, COUNT(*) as count')
                ->group_by('failure_code')
                ->get('roi_failed_transactions')
                ->result_array()
        ];
    }
}
