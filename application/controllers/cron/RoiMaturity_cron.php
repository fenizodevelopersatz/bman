<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * ROI Maturity CRON
 * Processes staking investments that have reached their maturity date
 * Releases final ROI payouts and records transactions
 *
 * Run hourly or as needed via: GET /cron/roi_maturity/process
 */

class RoiMaturity_cron extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Wallet_model');
        $this->load->model('User_model');
    }

    /**
     * Main CRON: Check & process matured investments
     */
    public function process()
    {
        try {
            echo "=== ROI MATURITY CRON PROCESS ===\n";
            echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

            // Get all staking orders that have matured
            $maturedOrders = $this->db
                ->select('sso.*')
                ->from('staking_swap_orders sso')
                ->where('sso.maturity_date <=', date('Y-m-d H:i:s'))
                ->where('sso.roi_return_status !=', 'completed')
                ->get()
                ->result_array();

            echo "Found " . count($maturedOrders) . " matured staking orders\n\n";

            if (empty($maturedOrders)) {
                echo "No matured orders to process\n";
                return;
            }

            $processedCount = 0;
            $failedCount = 0;

            // Process each matured order
            foreach ($maturedOrders as $order) {
                try {
                    echo "Processing Order ID: {$order['id']} (User: {$order['user_id']})\n";

                    // Calculate ROI details
                    $roiData = $this->calculateMaturityROI($order);

                    if (!$roiData) {
                        throw new Exception("Failed to calculate ROI for order {$order['id']}");
                    }

                    echo "  Principal: {$roiData['principal']} BMAN\n";
                    echo "  ROI Rate: {$roiData['roi_rate']}%\n";
                    echo "  Total ROI Earned: {$roiData['total_roi']} BMAN\n";
                    echo "  Already Paid: {$roiData['already_paid']} BMAN\n";
                    echo "  Remaining: {$roiData['remaining']} BMAN\n";

                    // Update status to processing
                    $this->db->where('id', $order['id'])
                        ->update('staking_swap_orders', [
                            'roi_return_status' => 'in_progress',
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);

                    // Create or update roi_distribution record
                    $this->createROIDistributionRecord($order, $roiData);

                    // Release final ROI to user wallet
                    if ($roiData['remaining'] > 0) {
                        $this->releaseROIToWallet($order, $roiData);
                    }

                    // Record transaction in onchain_transactions
                    $this->recordMaturityTransaction($order, $roiData);

                    // Mark as completed
                    $this->db->where('id', $order['id'])
                        ->update('staking_swap_orders', [
                            'roi_return_status' => 'completed',
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);

                    echo "  ✓ Order {$order['id']} processed successfully\n\n";
                    $processedCount++;

                } catch (Exception $e) {
                    echo "  ✗ Error processing order {$order['id']}: " . $e->getMessage() . "\n\n";

                    // Mark as failed
                    $this->db->where('id', $order['id'])
                        ->update('staking_swap_orders', [
                            'roi_return_status' => 'failed',
                            'error' => $e->getMessage(),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);

                    $failedCount++;
                }
            }

            echo "\n=== CRON SUMMARY ===\n";
            echo "Processed: {$processedCount}\n";
            echo "Failed: {$failedCount}\n";
            echo "Completed at: " . date('Y-m-d H:i:s') . "\n";

        } catch (Exception $e) {
            echo "CRON ERROR: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Calculate total ROI and remaining payout
     */
    private function calculateMaturityROI($order)
    {
        $principal = (float)$order['bman_amount'];
        $roiRate = (float)$order['roi_rate'];
        $durationYears = (int)$order['duration_years'];
        $createdAt = strtotime($order['created_at']);
        $maturityAt = strtotime($order['maturity_date']);
        $daysElapsed = ceil(($maturityAt - $createdAt) / 86400);

        // Total ROI calculation: Principal × (ROI% / 100) × (Days / 365)
        $totalROI = $principal * ($roiRate / 100) * ($daysElapsed / 365);

        // Get already paid ROI from onchain_transactions
        $alreadyPaid = 0;
        $result = $this->db->select('SUM(amount) as total')
            ->from('onchain_transactions')
            ->where('staking_swap_orders_id', $order['id'])
            ->where('tx_type', 'roi')
            ->get()
            ->row_array();

        if ($result && $result['total']) {
            $alreadyPaid = (float)$result['total'];
        }

        $remaining = max(0, $totalROI - $alreadyPaid);

        return [
            'principal' => $principal,
            'roi_rate' => $roiRate,
            'total_roi' => round($totalROI, 8),
            'already_paid' => round($alreadyPaid, 8),
            'remaining' => round($remaining, 8),
            'bonus' => (float)$order['bonus_bman'],
            'days_elapsed' => $daysElapsed,
        ];
    }

    /**
     * Create ROI distribution record
     */
    private function createROIDistributionRecord($order, $roiData)
    {
        $maturityDate = $order['maturity_date'];
        $createdAt = $order['created_at'];

        // Check if record already exists
        $existing = $this->db->where('staking_swap_orders_id', $order['id'])
            ->get('roi_distribution')
            ->row_array();

        $data = [
            'principal_amount' => $roiData['principal'],
            'duration_years' => $order['duration_years'],
            'roi_rate_percent' => $roiData['roi_rate'],
            'total_roi_earned' => $roiData['total_roi'],
            'roi_already_paid' => $roiData['already_paid'],
            'roi_remaining' => $roiData['remaining'],
            'bonus_amount' => $roiData['bonus'],
            'purchase_date' => $createdAt,
            'maturity_date' => $maturityDate,
            'days_elapsed' => $roiData['days_elapsed'],
            'is_matured' => 1,
            'distribution_status' => 'processing',
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            // Update existing record
            $this->db->where('staking_swap_orders_id', $order['id'])
                ->update('roi_distribution', $data);
        } else {
            // Create new record
            $data['staking_swap_orders_id'] = $order['id'];
            $data['user_id'] = $order['user_id'];
            $this->db->insert('roi_distribution', $data);
        }
    }

    /**
     * Release ROI to user's earning wallet
     */
    private function releaseROIToWallet($order, $roiData)
    {
        $userId = (int)$order['user_id'];
        $amount = (float)$roiData['remaining'];

        if ($amount <= 0) return;

        // Credit to user's earning wallet
        $walletUpdate = $this->db->where('user_id', $userId)
            ->where('wallet_type', 'earning')
            ->update('wallet_ledger', [
                'balance' => $this->db->raw('balance + ' . $amount),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        if (!$walletUpdate) {
            throw new Exception("Failed to credit wallet for user {$userId}");
        }
    }

    /**
     * Record transaction in onchain_transactions
     */
    private function recordMaturityTransaction($order, $roiData)
    {
        $amount = $roiData['remaining'];

        if ($amount <= 0) return;

        $txData = [
            'staking_swap_orders_id' => $order['id'],
            'user_id' => $order['user_id'],
            'tx_type' => 'roi_maturity',
            'amount' => $amount,
            'token' => 'BMAN',
            'from_wallet' => 'admin',
            'to_wallet' => 'earning',
            'status' => 'completed',
            'tx_hash' => 'maturity_' . $order['id'] . '_' . date('YmdHis'),
            'description' => "Maturity ROI release for staking order {$order['ref']}",
            'created_at' => date('Y-m-d H:i:s'),
        ];

        if (!$this->db->insert('onchain_transactions', $txData)) {
            throw new Exception("Failed to record transaction for order {$order['id']}");
        }
    }
}
?>
