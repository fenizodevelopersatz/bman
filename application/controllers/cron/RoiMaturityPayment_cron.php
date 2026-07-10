<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * ROI Maturity Payment CRON
 * Runs daily, processes maturity payments when date is reached
 * Handles Fixed and Combo plan final payments
 */

class RoiMaturityPayment_cron extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('RoiStakingManagement_model', 'roi_mgmt');
    }

    /**
     * Main CRON: Process maturity ROI distributions
     */
    public function process()
    {
        try {
            echo json_encode([
                'status' => true,
                'message' => 'ROI Maturity Payment CRON',
                'timestamp' => date('Y-m-d H:i:s'),
                'results' => $this->processMaturityPayments()
            ]);

        } catch (Exception $e) {
            echo json_encode(['status' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Process all pending maturity payments
     */
    private function processMaturityPayments()
    {
        $records = $this->roi_mgmt->getPendingMaturityPayments();

        $processed = 0;
        $failed = 0;
        $processedRecords = [];

        foreach ($records as $record) {
            try {
                $amount = $record['fixed_payment_amount'];

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
                $txHash = 'roi-maturity-' . $record['id'] . '-' . date('YmdHis');
                $this->db->insert('onchain_transactions', [
                    'staking_swap_orders_id' => $record['staking_swap_orders_id'],
                    'user_id' => $record['user_id'],
                    'tx_type' => 'roi_maturity_final',
                    'amount' => $amount,
                    'token' => 'BMAN',
                    'from_wallet' => 'admin',
                    'to_wallet' => 'earning',
                    'status' => 'completed',
                    'tx_hash' => $txHash,
                    'description' => "Maturity ROI payout for staking order " . $record['staking_swap_orders_id'],
                    'created_at' => date('Y-m-d H:i:s'),
                ]);

                // Update maturity payment status
                $this->roi_mgmt->updateMaturityStatus($record['id'], 'completed', $txHash);

                // Update total paid
                $this->roi_mgmt->updateTotalPaid($record['id'], $amount);

                $processedRecords[] = [
                    'record_id' => $record['id'],
                    'user_id' => $record['user_id'],
                    'plan_type' => $record['plan_type'],
                    'amount' => $amount,
                    'maturity_date' => $record['fixed_maturity_date'],
                    'status' => 'completed'
                ];

                $processed++;

            } catch (Exception $e) {
                // Mark as failed
                $this->roi_mgmt->updateMaturityStatus($record['id'], 'failed');
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
     * Test endpoint - check system status
     */
    public function test()
    {
        $nextMaturity = $this->db->select('MIN(fixed_maturity_date) as next_date')
                                 ->where('fixed_status', 'pending')
                                 ->where('fixed_maturity_date <=', date('Y-m-d H:i:s', strtotime('+30 days')))
                                 ->get('roi_staking_management')
                                 ->row_array();

        echo json_encode([
            'status' => true,
            'message' => 'ROI Maturity Payment CRON is operational',
            'current_date' => date('Y-m-d H:i:s'),
            'next_maturity_payment' => $nextMaturity['next_date'] ?? 'None in next 30 days'
        ]);
    }
}
?>
