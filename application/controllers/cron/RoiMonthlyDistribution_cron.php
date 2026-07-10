<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * ROI Monthly Distribution CRON
 * Runs hourly, processes payments on 5th, 15th, 25th of each month
 * Distributes ROI to user earning wallets
 */

class RoiMonthlyDistribution_cron extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('RoiStakingManagement_model', 'roi_mgmt');
        $this->load->model('Onchaintx_model', 'tx');
    }

    /**
     * Main CRON: Process monthly ROI distributions
     */
    public function process()
    {
        try {
            $today = (int)date('d');
            $paymentDays = [5, 15, 25];

            // Check if today is a payment day
            if (!in_array($today, $paymentDays)) {
                echo json_encode(['status' => true, 'message' => 'Not a payment day today', 'today' => $today]);
                return;
            }

            echo json_encode([
                'status' => true,
                'message' => 'Monthly ROI Distribution CRON',
                'timestamp' => date('Y-m-d H:i:s'),
                'payment_day' => $today,
                'results' => $this->processPaymentDay($today)
            ]);

        } catch (Exception $e) {
            echo json_encode(['status' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Process all pending payments for the day
     */
    private function processPaymentDay($day)
    {
        $records = $this->roi_mgmt->getPendingMonthlyPayments($day);

        $processed = 0;
        $failed = 0;
        $processedRecords = [];

        foreach ($records as $record) {
            try {
                $amount = $record['payment_day_' . $day . '_amount'];

                if (!$amount || $amount <= 0) {
                    continue;
                }

                // Credit to user earning wallet
                $this->db->where('user_id', $record['user_id'])
                         ->where('wallet_type', 'earning')
                         ->update('wallet_ledger', [
                             'balance' => $this->db->raw('balance + ' . $amount),
                             'updated_at' => date('Y-m-d H:i:s'),
                         ]);

                // Record transaction
                $txHash = 'roi-monthly-' . $day . '-' . $record['id'] . '-' . date('YmdHis');
                $this->db->insert('onchain_transactions', [
                    'staking_swap_orders_id' => $record['staking_swap_orders_id'],
                    'user_id' => $record['user_id'],
                    'tx_type' => 'roi_monthly_' . $day,
                    'amount' => $amount,
                    'token' => 'BMAN',
                    'from_wallet' => 'admin',
                    'to_wallet' => 'earning',
                    'status' => 'completed',
                    'tx_hash' => $txHash,
                    'description' => "Monthly ROI distribution for day " . $day,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);

                // Update payment status
                $this->roi_mgmt->updatePaymentStatus($record['id'], $day, 'completed', $txHash);

                // Update total paid
                $this->roi_mgmt->updateTotalPaid($record['id'], $amount);

                // Calculate next payment date
                $nextDate = $this->roi_mgmt->calculateNextPayment($record['id'], $record['plan_type']);

                // Update overall status if all payments done
                $this->checkAndUpdateOverallStatus($record['id']);

                $processedRecords[] = [
                    'record_id' => $record['id'],
                    'user_id' => $record['user_id'],
                    'amount' => $amount,
                    'next_payment' => $nextDate
                ];

                $processed++;

            } catch (Exception $e) {
                // Mark as failed
                $this->roi_mgmt->updatePaymentStatus($record['id'], $day, 'failed');
                $this->db->where('id', $record['id'])
                         ->update('roi_staking_management', [
                             'error_message' => $e->getMessage(),
                             'overall_status' => 'failed'
                         ]);
                $failed++;
            }
        }

        return [
            'found' => count($records),
            'processed' => $processed,
            'failed' => $failed,
            'records' => $processedRecords
        ];
    }

    /**
     * Check if all payments completed and update overall status
     */
    private function checkAndUpdateOverallStatus($recordId)
    {
        $record = $this->db->where('id', $recordId)
                          ->get('roi_staking_management')
                          ->row_array();

        if (!$record) return;

        $allMonthlyDone = ($record['payment_day_5_status'] === 'completed' &&
                          $record['payment_day_15_status'] === 'completed' &&
                          $record['payment_day_25_status'] === 'completed');

        if ($allMonthlyDone) {
            if ($record['plan_type'] === 'regular') {
                // Regular plan - all done
                $this->db->where('id', $recordId)
                         ->update('roi_staking_management', ['overall_status' => 'completed']);
            } elseif ($record['plan_type'] === 'combo') {
                // Combo plan - wait for maturity
                $this->db->where('id', $recordId)
                         ->update('roi_staking_management', [
                             'overall_status' => 'in_progress',
                             'next_payment_date' => $record['fixed_maturity_date']
                         ]);
            }
        }
    }

    /**
     * Test endpoint - check today's status
     */
    public function test()
    {
        $today = (int)date('d');
        $paymentDays = [5, 15, 25];

        echo json_encode([
            'status' => true,
            'message' => 'ROI Monthly Distribution CRON is operational',
            'today' => $today,
            'is_payment_day' => in_array($today, $paymentDays),
            'payment_days' => $paymentDays,
            'next_payment_day' => $this->getNextPaymentDay()
        ]);
    }

    private function getNextPaymentDay()
    {
        $today = (int)date('d');
        $paymentDays = [5, 15, 25];

        foreach ($paymentDays as $day) {
            if ($today < $day) {
                return date('Y-m-') . str_pad($day, 2, '0', STR_PAD_LEFT);
            }
        }

        return date('Y-m-', strtotime('+1 month')) . '05';
    }
}
?>
