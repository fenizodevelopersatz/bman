<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * ROI Maturity CRON
 * Processes staking investments that have reached their maturity date
 * Releases final ROI payouts and records transactions
 *
 * Run hourly or as needed via: GET /cron/roi_maturity/process
 */

class RoiMaturityCron extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        // Models not needed - CRON uses direct database queries
    }

    /**
     * Test endpoint - returns JSON for monitoring
     */
    public function test()
    {
        try {
            $response = [
                'status' => true,
                'message' => 'ROI Maturity CRON is accessible',
                'timestamp' => date('Y-m-d H:i:s'),
                'endpoint' => '/cron/roi_maturity/process',
                'next_step' => 'Run process() endpoint to execute CRON'
            ];
            echo json_encode($response);
        } catch (Exception $e) {
            echo json_encode(['status' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Main CRON: Check & process matured investments
     * Returns JSON for monitoring
     */
    public function process()
    {
        $format = $this->input->get('format') ?? 'text'; // 'text' or 'json'

        try {
            if ($format === 'json') {
                header('Content-Type: application/json');
            }

            $output = "=== ROI MATURITY CRON PROCESS ===\n";
            $output .= "Started at: " . date('Y-m-d H:i:s') . "\n\n";

            // Get all staking orders that have matured
            $maturedOrders = $this->db
                ->select('sso.*')
                ->from('staking_swap_orders sso')
                ->where('sso.maturity_date <=', date('Y-m-d H:i:s'))
                ->where('sso.roi_return_status !=', 'completed')
                ->get()
                ->result_array();

            $output .= "Found " . count($maturedOrders) . " matured staking orders\n\n";

            if (empty($maturedOrders)) {
                $output .= "No matured orders to process\n";
                if ($format === 'json') {
                    echo json_encode(['status' => true, 'message' => 'No matured orders', 'count' => 0]);
                } else {
                    echo $output;
                }
                return;
            }

            $processedCount = 0;
            $failedCount = 0;
            $processedOrders = [];

            // Process each matured order
            foreach ($maturedOrders as $order) {
                try {
                    $output .= "Processing Order ID: {$order['id']} (User: {$order['user_id']})\n";

                    // Calculate ROI details
                    $roiData = $this->calculateMaturityROI($order);

                    if (!$roiData) {
                        throw new Exception("Failed to calculate ROI for order {$order['id']}");
                    }

                    $output .= "  Principal: {$roiData['principal']} BMAN\n";
                    $output .= "  ROI Rate: {$roiData['roi_rate']}%\n";
                    $output .= "  Total ROI Earned: {$roiData['total_roi']} BMAN\n";
                    $output .= "  Already Paid: {$roiData['already_paid']} BMAN\n";
                    $output .= "  Remaining: {$roiData['remaining']} BMAN\n";

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

                    $output .= "  ✓ Order {$order['id']} processed successfully\n\n";
                    $processedOrders[] = [
                        'id' => $order['id'],
                        'user_id' => $order['user_id'],
                        'principal' => $roiData['principal'],
                        'roi_remaining' => $roiData['remaining']
                    ];
                    $processedCount++;

                } catch (Exception $e) {
                    $output .= "  ✗ Error processing order {$order['id']}: " . $e->getMessage() . "\n\n";

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

            $output .= "\n=== CRON SUMMARY ===\n";
            $output .= "Processed: {$processedCount}\n";
            $output .= "Failed: {$failedCount}\n";
            $output .= "Completed at: " . date('Y-m-d H:i:s') . "\n";

            // Output response
            if ($format === 'json') {
                echo json_encode([
                    'status' => true,
                    'message' => 'ROI maturity processing completed',
                    'timestamp' => date('Y-m-d H:i:s'),
                    'stats' => [
                        'found' => count($maturedOrders),
                        'processed' => $processedCount,
                        'failed' => $failedCount
                    ],
                    'orders' => $processedOrders
                ]);
            } else {
                echo $output;
            }

        } catch (Exception $e) {
            if ($format === 'json') {
                echo json_encode(['status' => false, 'error' => $e->getMessage()]);
            } else {
                echo "CRON ERROR: " . $e->getMessage() . "\n";
            }
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
