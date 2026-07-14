<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Binary Matching Bonus Cron - SIMPLIFIED SINGLE CRON
 *
 * Combines bonus calculation + payout processing into ONE cycle
 * Run every 2-3 minutes
 *
 * Usage:
 *   php index.php cron binarymatchingcron_simple process
 *   curl https://your-site.com/cron/binarymatchingcron_simple/process?token=XXX
 */
class BinaryMatchingCron_Simple extends CI_Controller
{
    private $log_prefix = '[BINARY_MATCHING_CRON]';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('member/BinaryMatchingBonus_model');
        $this->load->model('member/BonusTransactionQueue_model');
        $this->load->model('member/CeilingWallet_model');
        $this->load->model('member/StakingBonusIntegration_model', 'bonus_integration');

        // Restrict access to CLI or authorized IPs
        if (!$this->input->is_cli_request()) {
            $allowed_ips = ['127.0.0.1', '::1'];
            $client_ip = $this->input->ip_address();
            if (!in_array($client_ip, $allowed_ips)) {
                http_response_code(403);
                die('Access denied');
            }
        }
    }

    /**
     * Single unified process: Calculate bonuses AND process payouts
     * Run every 2-3 minutes
     *
     * Usage:
     *   php index.php cron binarymatchingcron_simple process
     */
    public function process()
    {
        log_message('info', $this->log_prefix . ' Starting cycle');

        $cycle_result = [
            'timestamp' => date('Y-m-d H:i:s'),
            'status' => 'success',
            'bonuses' => [
                'pending' => 0,
                'calculated' => 0,
                'failed' => 0,
            ],
            'payouts' => [
                'pending' => 0,
                'broadcasted' => 0,
                'confirmed' => 0,
                'failed' => 0,
            ],
            'alerts' => [],
        ];

        try {
            // ==== PHASE 1: CALCULATE BONUSES ====
            $cycle_result['bonuses'] = $this->calculatePendingBonuses();

            // ==== PHASE 2: BROADCAST & VERIFY PAYOUTS ====
            $cycle_result['payouts'] = $this->processPayouts();

            // ==== PHASE 3: CHECK HEALTH ====
            $health = $this->checkHealth();
            if (!$health['healthy']) {
                $cycle_result['alerts'] = $health['alerts'];
                $cycle_result['status'] = 'warning';
            }

        } catch (Exception $e) {
            log_message('error', $this->log_prefix . ' Exception: ' . $e->getMessage());
            $cycle_result['status'] = 'error';
            $cycle_result['error'] = $e->getMessage();
        }

        log_message('info', $this->log_prefix . ' Cycle complete: ' . json_encode($cycle_result));
        echo json_encode($cycle_result) . PHP_EOL;
    }

    /**
     * Phase 1: Calculate bonuses for all pending staking purchases
     * @return array Results
     */
    private function calculatePendingBonuses()
    {
        $result = [
            'pending' => 0,
            'calculated' => 0,
            'failed' => 0,
            'details' => [],
        ];

        // Get staking purchases that need bonus calculation
        $pending = $this->getPendingPurchases();
        $result['pending'] = count($pending);

        foreach ($pending as $purchase) {
            try {
                // Calculate bonuses
                $bonus_result = $this->BinaryMatchingBonus_model->calculateBonuses(
                    $purchase->id,
                    $purchase->user_id,
                    $purchase->amount
                );

                if ($bonus_result['status']) {
                    $result['calculated']++;

                    // Queue the payouts
                    $this->queueBonusPayouts($bonus_result);

                    $result['details'][] = [
                        'purchase_id' => $purchase->id,
                        'bonuses' => $bonus_result['bonus_count'],
                        'total' => $bonus_result['total_bonus'],
                    ];

                    // Mark as processed
                    $this->db->update('staking_swap_orders',
                        ['bman_bonus_processed' => 1],
                        ['id' => $purchase->id]
                    );

                } else {
                    $result['failed']++;
                    log_message('error', $this->log_prefix . ' Bonus calc failed for purchase ' . $purchase->id);
                }

            } catch (Exception $e) {
                $result['failed']++;
                log_message('error', $this->log_prefix . ' Exception for purchase ' . $purchase->id . ': ' . $e->getMessage());
            }
        }

        return $result;
    }

    /**
     * Phase 2: Broadcast and verify all payouts
     * @return array Results
     */
    private function processPayouts()
    {
        $result = [
            'pending' => 0,
            'broadcasted' => 0,
            'confirmed' => 0,
            'failed' => 0,
            'gas_alert' => false,
        ];

        // Check admin gas balance
        $admin_wallet = $this->db->get_where('admin_ceiling_wallet', ['id' => 1])->row();
        if (!$admin_wallet) {
            return $result;
        }

        $gas_balance = (float)$admin_wallet->gas_balance;
        if ($gas_balance < 0.1) {
            $result['gas_alert'] = true;
            log_message('error', $this->log_prefix . ' ALERT: Admin gas balance low: ' . $gas_balance);
        }

        // Process each pending/retrying payout
        $payouts = $this->db->query(
            "SELECT * FROM blockchain_payout_queue
             WHERE status IN ('PENDING', 'RETRYING', 'BROADCASTED')
             ORDER BY created_at ASC
             LIMIT 100"
        )->result_array();

        $result['pending'] = count($payouts);

        foreach ($payouts as $payout) {
            try {
                $status = $this->processSinglePayout($payout, $gas_balance);

                if ($status['action'] === 'broadcasted') {
                    $result['broadcasted']++;
                    $gas_balance -= (float)($status['gas_used'] ?? 0);
                } elseif ($status['action'] === 'confirmed') {
                    $result['confirmed']++;
                } elseif ($status['action'] === 'failed') {
                    $result['failed']++;
                }

            } catch (Exception $e) {
                $result['failed']++;
                log_message('error', $this->log_prefix . ' Payout exception: ' . $e->getMessage());
            }
        }

        // Update admin gas balance
        if ($gas_balance != (float)$admin_wallet->gas_balance) {
            $this->db->update('admin_ceiling_wallet',
                ['gas_balance' => $gas_balance],
                ['id' => 1]
            );
        }

        return $result;
    }

    /**
     * Process a single payout: broadcast if pending, or verify if broadcasted
     * @param array $payout
     * @param float $available_gas
     * @return array Action taken
     */
    private function processSinglePayout($payout, $available_gas)
    {
        $queue_id = $payout['id'];
        $status = $payout['status'];

        if ($status === 'PENDING') {
            // Broadcast new payout
            $estimated_gas = $this->estimateGasCost((float)$payout['amount']);

            if ($available_gas < $estimated_gas) {
                // Not enough gas
                $this->db->update('blockchain_payout_queue',
                    [
                        'status' => 'RETRYING',
                        'retry_count' => $payout['retry_count'] + 1,
                        'last_error' => 'Insufficient gas',
                    ],
                    ['id' => $queue_id]
                );

                return [
                    'action' => 'retry',
                    'reason' => 'insufficient_gas',
                    'gas_used' => 0,
                ];
            }

            // Broadcast to blockchain
            $tx_hash = $this->broadcastPayout(
                $payout['to_user_id'],
                $payout['amount'],
                $payout['wallet_type']
            );

            if (!$tx_hash) {
                $this->db->update('blockchain_payout_queue',
                    [
                        'status' => 'RETRYING',
                        'retry_count' => $payout['retry_count'] + 1,
                        'last_error' => 'Broadcast failed',
                    ],
                    ['id' => $queue_id]
                );

                return ['action' => 'failed', 'reason' => 'broadcast_failed'];
            }

            // Success: mark as broadcasted
            $this->db->update('blockchain_payout_queue',
                [
                    'status' => 'BROADCASTED',
                    'transaction_hash' => $tx_hash,
                    'gas_used' => $estimated_gas,
                ],
                ['id' => $queue_id]
            );

            return ['action' => 'broadcasted', 'tx_hash' => $tx_hash, 'gas_used' => $estimated_gas];

        } elseif ($status === 'BROADCASTED') {
            // Verify confirmation
            $confirmations = $this->getConfirmations($payout['transaction_hash']);

            if ($confirmations >= 12) {
                // Confirmed!
                $this->db->update('blockchain_payout_queue',
                    [
                        'status' => 'CONFIRMED',
                        'updated_at' => date('Y-m-d H:i:s'),
                    ],
                    ['id' => $queue_id]
                );

                // Credit user's wallet
                $this->creditUserWallet(
                    $payout['to_user_id'],
                    $payout['amount'],
                    $payout['wallet_type']
                );

                // Mark bonus as distributed
                if ($payout['bonus_ledger_id']) {
                    $this->db->update('binary_matching_bonus_ledger',
                        [
                            'status' => 'DISTRIBUTED',
                            'distributed_at' => date('Y-m-d H:i:s'),
                        ],
                        ['id' => $payout['bonus_ledger_id']]
                    );
                }

                return ['action' => 'confirmed', 'confirmations' => $confirmations];

            } elseif ($confirmations === -1) {
                // Reverted on-chain, retry
                $this->db->update('blockchain_payout_queue',
                    [
                        'status' => 'RETRYING',
                        'retry_count' => $payout['retry_count'] + 1,
                        'last_error' => 'Transaction reverted on-chain',
                    ],
                    ['id' => $queue_id]
                );

                return ['action' => 'retry', 'reason' => 'reverted'];

            } else {
                // Still waiting for confirmations
                return ['action' => 'waiting', 'confirmations' => $confirmations];
            }
        }

        return ['action' => 'unknown'];
    }

    /**
     * Check system health
     * @return array Health status
     */
    private function checkHealth()
    {
        $alerts = [];
        $healthy = true;

        $admin_wallet = $this->db->get_where('admin_ceiling_wallet', ['id' => 1])->row();
        if ($admin_wallet && (float)$admin_wallet->gas_balance < 0.1) {
            $alerts[] = 'CRITICAL: Admin gas balance low (' . $admin_wallet->gas_balance . ')';
            $healthy = false;
        }

        $failed_payouts = $this->db->query(
            "SELECT COUNT(*) as count FROM blockchain_payout_queue WHERE status = 'FAILED' AND retry_count >= 3"
        )->row();
        if ($failed_payouts && $failed_payouts->count > 5) {
            $alerts[] = 'WARNING: ' . $failed_payouts->count . ' payouts failed after max retries';
            $healthy = false;
        }

        return [
            'healthy' => $healthy,
            'alerts' => $alerts,
        ];
    }

    // ====== HELPERS ======

    private function getPendingPurchases()
    {
        return $this->db->query(
            "SELECT p.id, p.user_id, p.amount
             FROM staking_swap_orders p
             WHERE p.status = 'confirmed'
             AND (p.bman_bonus_processed IS NULL OR p.bman_bonus_processed = 0)
             ORDER BY p.created_at ASC
             LIMIT 20"
        )->result();
    }

    private function queueBonusPayouts($bonus_result)
    {
        if (empty($bonus_result['bonuses_by_level'])) {
            return;
        }

        foreach ($bonus_result['bonuses_by_level'] as $level => $bonus) {
            $recipient_id = $bonus['bonus_recipient_id'];
            $ledger_row = $this->db->query(
                "SELECT id FROM binary_matching_bonus_ledger
                 WHERE purchase_id = ? AND bonus_recipient_id = ? AND level = ?
                 ORDER BY id DESC LIMIT 1",
                [$bonus_result['purchase_id'], $recipient_id, $level]
            )->row();

            if (!$ledger_row) continue;

            if ($bonus['bonus_earning'] > 0) {
                $this->BonusTransactionQueue_model->queuePayout(
                    $ledger_row->id,
                    $recipient_id,
                    'EARNING',
                    $bonus['bonus_earning']
                );
            }

            if ($bonus['bonus_staking'] > 0) {
                $this->BonusTransactionQueue_model->queuePayout(
                    $ledger_row->id,
                    $recipient_id,
                    'STAKING',
                    $bonus['bonus_staking']
                );
            }
        }
    }

    private function estimateGasCost($amount)
    {
        return 0.001 + ($amount * 0.001);
    }

    private function broadcastPayout($user_id, $amount, $wallet_type)
    {
        // TODO: Integrate with actual blockchain API
        $tx_hash = 'TXH_' . bin2hex(random_bytes(16));
        log_message('info', $this->log_prefix . " Broadcast: User $user_id, $amount BMAN → $wallet_type");
        return $tx_hash;
    }

    private function creditUserWallet($user_id, $amount, $wallet_type)
    {
        if ($wallet_type === 'EARNING') {
            $this->db->update('users',
                ['exchange_balance' => $this->db->raw('exchange_balance + ' . $amount)],
                ['id' => $user_id]
            );
        } elseif ($wallet_type === 'STAKING') {
            $this->db->update('users',
                ['staking_wallet' => $this->db->raw('staking_wallet + ' . $amount)],
                ['id' => $user_id]
            );
        }
    }

    private function getConfirmations($tx_hash)
    {
        // TODO: Integrate with blockchain explorer API
        // For now, simulate: new txs get 0, old txs get 12+
        if (strpos($tx_hash, 'TXH_') === 0) {
            // Simulated, treat as confirmed
            return 12;
        }
        return 0;
    }
}
