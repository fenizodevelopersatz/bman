<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class BinaryMatchingCron extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('member/BinaryMatchingBonus_model');
        $this->load->model('member/BonusTransactionQueue_model');
        $this->load->model('member/CeilingWallet_model');

        // Restrict access to CLI or authorized cron IPs only
        if (!$this->input->is_cli_request()) {
            // In production, check auth token or IP whitelist
            $allowed_ips = ['127.0.0.1', '::1'];
            $client_ip = $this->input->ip_address();
            if (!in_array($client_ip, $allowed_ips)) {
                http_response_code(403);
                die('Access denied');
            }
        }
    }

    /**
     * Main orchestrator: Calculate bonuses for all pending purchases
     * Run every 5 minutes via cron
     *
     * Usage:
     *   php index.php cron binarymatchingcron process_bonuses
     *   OR
     *   curl https://your-site.com/cron/binarymatchingcron/process_bonuses?cron_token=XXX
     */
    public function process_bonuses()
    {
        log_message('info', '[BinaryMatchingCron] Starting bonus processing cycle');

        // Get pending staking purchases (those confirmed but bonuses not yet calculated)
        $pending = $this->getPendingPurchases();

        if (empty($pending)) {
            echo json_encode([
                'status' => 'success',
                'message' => 'No pending purchases',
                'processed' => 0,
            ]);
            return;
        }

        $result = [
            'status' => 'success',
            'total_purchases' => count($pending),
            'processed' => 0,
            'failed' => 0,
            'details' => [],
        ];

        foreach ($pending as $purchase) {
            $calc_result = $this->BinaryMatchingBonus_model->calculateBonuses(
                $purchase->id,
                $purchase->user_id,
                $purchase->amount
            );

            if ($calc_result['status']) {
                $result['processed']++;

                // Queue payouts for blockchain
                $this->queuePayoutsFromBonus($calc_result);

                $result['details'][] = [
                    'purchase_id' => $purchase->id,
                    'status' => 'calculated',
                    'bonus_count' => $calc_result['bonus_count'],
                    'total_bonus' => $calc_result['total_bonus'],
                ];
            } else {
                $result['failed']++;
                $result['details'][] = [
                    'purchase_id' => $purchase->id,
                    'status' => 'failed',
                    'error' => $calc_result['message'] ?? 'Unknown error',
                ];
            }
        }

        log_message('info', "[BinaryMatchingCron] Processed $result[processed]/$result[total_purchases] purchases");

        echo json_encode($result);
    }

    /**
     * Process queued payouts (blockchain broadcasts)
     * Run every 1-2 minutes
     *
     * Usage:
     *   php index.php cron binarymatchingcron process_payouts
     */
    public function process_payouts()
    {
        log_message('info', '[BinaryMatchingCron] Starting payout processing');

        $result = $this->BonusTransactionQueue_model->processPendingPayouts();

        log_message('info', "[BinaryMatchingCron] Processed: $result[processed], Failed: $result[failed], Pending: $result[pending]");

        if ($result['gas_alert']) {
            log_message('error', '[BinaryMatchingCron] ALERT: Admin gas balance critically low!');
        }

        echo json_encode($result);
    }

    /**
     * Check and release ceiling holds
     * Run once per day or when triggered
     *
     * Usage:
     *   php index.php cron binarymatchingcron release_ceiling_holds
     */
    public function release_ceiling_holds()
    {
        log_message('info', '[BinaryMatchingCron] Checking ceiling holds for release');

        // Get users with ceiling holds
        $holds = $this->db->query(
            "SELECT DISTINCT user_id FROM ceiling_wallet WHERE balance > 0 LIMIT 50"
        )->result_array();

        $result = [
            'status' => 'success',
            'checked' => count($holds),
            'released' => 0,
            'details' => [],
        ];

        foreach ($holds as $hold) {
            $user_id = $hold['user_id'];

            // Check if user's ceiling has changed (package upgrade, new purchase)
            $release_result = $this->CeilingWallet_model->releaseCeilingHold($user_id);

            if (!isset($release_result['error'])) {
                if ($release_result['released'] > 0) {
                    $result['released']++;
                    $result['details'][] = [
                        'user_id' => $user_id,
                        'released' => $release_result['released'],
                        'remaining' => $release_result['remaining'],
                    ];
                }
            }
        }

        log_message('info', "[BinaryMatchingCron] Released ceiling holds from $result[released] users");

        echo json_encode($result);
    }

    /**
     * Health check and monitoring
     * Run every hour
     *
     * Usage:
     *   php index.php cron binarymatchingcron health_check
     */
    public function health_check()
    {
        $health = [
            'status' => 'healthy',
            'timestamp' => date('Y-m-d H:i:s'),
            'database' => $this->checkDatabaseHealth(),
            'queue_status' => $this->BonusTransactionQueue_model->getQueueStatus(),
            'admin_ceiling' => $this->CeilingWallet_model->getAdminCeilingSummary(),
            'alerts' => [],
        ];

        // Check for issues
        if ($health['admin_ceiling']['gas_alert']) {
            $health['alerts'][] = 'CRITICAL: Admin gas balance is low';
            $health['status'] = 'warning';
        }

        $queue_status = $health['queue_status'];
        if (isset($queue_status['FAILED']) && $queue_status['FAILED']['count'] > 10) {
            $health['alerts'][] = 'WARNING: More than 10 failed payouts in queue';
            $health['status'] = 'warning';
        }

        echo json_encode($health);
    }

    /**
     * Full cycle: bonuses + payouts + ceiling releases
     * Run every 5 minutes for complete processing
     *
     * Usage:
     *   php index.php cron binarymatchingcron full_cycle
     */
    public function full_cycle()
    {
        log_message('info', '[BinaryMatchingCron] Starting full processing cycle');

        $cycle_result = [
            'timestamp' => date('Y-m-d H:i:s'),
            'cycle' => [],
        ];

        // Step 1: Calculate bonuses
        $this->BinaryMatchingBonus_model->calculateBonuses(0, 0, 0); // Check for pending
        $pending = $this->getPendingPurchases();
        $cycle_result['cycle']['bonuses_pending'] = count($pending);

        // Step 2: Process payouts
        $payout_result = $this->BonusTransactionQueue_model->processPendingPayouts();
        $cycle_result['cycle']['payouts'] = $payout_result;

        // Step 3: Check ceiling health
        $ceiling = $this->CeilingWallet_model->getAdminCeilingSummary();
        $cycle_result['cycle']['ceiling_health'] = [
            'total_held' => $ceiling['total_held'],
            'gas_balance' => $ceiling['gas_balance'],
            'gas_alert' => $ceiling['gas_alert'],
        ];

        log_message('info', '[BinaryMatchingCron] Cycle complete');

        echo json_encode($cycle_result);
    }

    // ====== PRIVATE HELPER METHODS ======

    /**
     * Get staking purchases pending bonus calculation
     * @return array Pending purchases
     */
    private function getPendingPurchases()
    {
        // Assume staking purchases are in a table, get those with status='pending'
        return $this->db->query(
            "SELECT p.id, p.user_id, p.amount, p.created_at
             FROM staking_purchase p
             WHERE p.status = 'confirmed'
             AND NOT EXISTS (
                SELECT 1 FROM binary_matching_queue bq
                WHERE bq.purchase_id = p.id
             )
             ORDER BY p.created_at ASC
             LIMIT 20"
        )->result();
    }

    /**
     * Convert calculated bonuses into payout queue entries
     * @param array $calc_result Result from calculateBonuses()
     */
    private function queuePayoutsFromBonus($calc_result)
    {
        if (empty($calc_result['bonuses_by_level'])) {
            return;
        }

        foreach ($calc_result['bonuses_by_level'] as $level => $bonus) {
            $recipient_id = $bonus['bonus_recipient_id'];
            $ledger_id = $this->db->query(
                "SELECT id FROM binary_matching_bonus_ledger
                 WHERE purchase_id = ? AND bonus_recipient_id = ? AND level = ?",
                [$calc_result['purchase_id'], $recipient_id, $level]
            )->row()->id ?? null;

            if (!$ledger_id) {
                continue;
            }

            // Queue earning wallet payout
            if ($bonus['bonus_earning'] > 0 && $bonus['ceiling_held'] <= $bonus['bonus_earning']) {
                $this->BonusTransactionQueue_model->queuePayout(
                    $ledger_id,
                    $recipient_id,
                    'EARNING',
                    $bonus['bonus_earning']
                );
            }

            // Queue staking wallet payout
            if ($bonus['bonus_staking'] > 0) {
                $this->BonusTransactionQueue_model->queuePayout(
                    $ledger_id,
                    $recipient_id,
                    'STAKING',
                    $bonus['bonus_staking']
                );
            }

            // If ceiling held, don't queue until released
            if ($bonus['ceiling_held'] > 0) {
                log_message('info', "Bonus for user $recipient_id held in ceiling: " . $bonus['ceiling_held']);
            }
        }
    }

    /**
     * Basic database health check
     * @return array Health status
     */
    private function checkDatabaseHealth()
    {
        try {
            $result = $this->db->query("SELECT 1")->result();
            return ['status' => 'connected', 'latency' => 'unknown'];
        } catch (Exception $e) {
            return ['status' => 'error', 'error' => $e->getMessage()];
        }
    }
}
