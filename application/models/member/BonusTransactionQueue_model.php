<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class BonusTransactionQueue_model extends CI_Model
{
    const MAX_RETRIES = 3;
    const GAS_ALERT_THRESHOLD = 0.1;  // Alert when admin gas < 0.1
    const RETRY_DELAY_MINUTES = 5;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Queue a bonus payout for blockchain broadcast
     *
     * @param int $bonus_ledger_id Which bonus this is for
     * @param int $to_user_id Recipient user ID
     * @param string $wallet_type 'EARNING' or 'STAKING' or 'CEILING'
     * @param decimal $amount Amount to transfer
     * @return array ['status' => true/false, 'queue_id' => n]
     */
    public function queuePayout($bonus_ledger_id, $to_user_id, $wallet_type, $amount)
    {
        if ($amount <= 0) {
            return ['status' => false, 'message' => 'Invalid amount'];
        }

        try {
            $this->db->insert('blockchain_payout_queue', [
                'bonus_ledger_id' => $bonus_ledger_id,
                'wallet_type' => $wallet_type,
                'to_user_id' => $to_user_id,
                'amount' => $amount,
                'status' => 'PENDING',
                'retry_count' => 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            return [
                'status' => true,
                'queue_id' => $this->db->insert_id(),
                'message' => 'Payout queued',
            ];
        } catch (Exception $e) {
            return [
                'status' => false,
                'message' => 'Failed to queue: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Process pending payouts
     * Called by cron job periodically
     *
     * @return array ['processed' => n, 'failed' => n, 'pending' => n, 'gas_alert' => true/false]
     */
    public function processPendingPayouts()
    {
        // Check admin gas balance first
        $admin_wallet = $this->db->get_where('admin_ceiling_wallet', ['id' => 1])->row();
        if (!$admin_wallet) {
            return ['status' => false, 'message' => 'Admin wallet not found'];
        }

        $gas_balance = (float) $admin_wallet->gas_balance;
        $gas_alert = $gas_balance < self::GAS_ALERT_THRESHOLD;

        if ($gas_alert) {
            log_message('error', '[BonusQueue] ALERT: Admin gas balance low: ' . $gas_balance);
        }

        // Get pending/retrying payouts
        $payouts = $this->db->query(
            "SELECT * FROM blockchain_payout_queue
             WHERE status IN ('PENDING', 'RETRYING')
             AND (retry_count < ? OR status = 'PENDING')
             ORDER BY created_at ASC
             LIMIT 50",
            [self::MAX_RETRIES]
        )->result_array();

        $result = [
            'processed' => 0,
            'failed' => 0,
            'pending' => count($payouts),
            'gas_alert' => $gas_alert,
            'details' => [],
        ];

        foreach ($payouts as $payout) {
            $status = $this->processSinglePayout($payout, $gas_balance);

            if ($status['success']) {
                $result['processed']++;
            } else {
                $result['failed']++;
            }

            $result['details'][] = $status;

            // Deduct gas from admin wallet
            if ($status['gas_used'] > 0) {
                $gas_balance -= (float) $status['gas_used'];
                $this->updateAdminGasBalance($status['gas_used']);
            }
        }

        return $result;
    }

    /**
     * Process a single payout entry
     *
     * @param array $payout The payout queue entry
     * @param decimal $available_gas How much gas is available
     * @return array Status of this payout
     */
    private function processSinglePayout($payout, $available_gas)
    {
        $queue_id = $payout['id'];
        $bonus_ledger_id = $payout['bonus_ledger_id'];
        $user_id = $payout['to_user_id'];
        $amount = (float) $payout['amount'];
        $wallet_type = $payout['wallet_type'];

        try {
            // Estimate gas cost (simplified)
            $estimated_gas = $this->estimateGasCost($amount);

            if ($available_gas < $estimated_gas) {
                // Not enough gas, mark for retry
                $this->db->update('blockchain_payout_queue', [
                    'status' => 'RETRYING',
                    'retry_count' => $payout['retry_count'] + 1,
                    'last_error' => 'Insufficient gas balance',
                    'updated_at' => date('Y-m-d H:i:s'),
                ], ['id' => $queue_id]);

                return [
                    'queue_id' => $queue_id,
                    'success' => false,
                    'reason' => 'insufficient_gas',
                    'gas_used' => 0,
                    'error' => 'Insufficient gas balance: ' . $available_gas . ' < ' . $estimated_gas,
                ];
            }

            // Simulate blockchain payout
            $tx_hash = $this->broadcastPayout($user_id, $amount, $wallet_type);

            if (!$tx_hash) {
                // Broadcast failed, retry
                $this->db->update('blockchain_payout_queue', [
                    'status' => ($payout['retry_count'] < self::MAX_RETRIES) ? 'RETRYING' : 'FAILED',
                    'retry_count' => $payout['retry_count'] + 1,
                    'last_error' => 'Broadcast failed',
                    'updated_at' => date('Y-m-d H:i:s'),
                ], ['id' => $queue_id]);

                return [
                    'queue_id' => $queue_id,
                    'success' => false,
                    'reason' => 'broadcast_failed',
                    'gas_used' => 0,
                    'error' => 'Failed to broadcast transaction',
                ];
            }

            // Success: update wallet and mark as distributed
            $this->db->update('blockchain_payout_queue', [
                'status' => 'CONFIRMED',
                'transaction_hash' => $tx_hash,
                'gas_used' => $estimated_gas,
                'updated_at' => date('Y-m-d H:i:s'),
            ], ['id' => $queue_id]);

            // Credit the user's wallet based on type
            $this->creditUserWallet($user_id, $amount, $wallet_type);

            // Mark bonus as distributed
            if ($bonus_ledger_id) {
                $this->db->update('binary_matching_bonus_ledger', [
                    'status' => 'DISTRIBUTED',
                    'distributed_at' => date('Y-m-d H:i:s'),
                ], ['id' => $bonus_ledger_id]);
            }

            return [
                'queue_id' => $queue_id,
                'success' => true,
                'reason' => 'success',
                'tx_hash' => $tx_hash,
                'gas_used' => $estimated_gas,
                'amount' => $amount,
                'wallet_type' => $wallet_type,
                'user_id' => $user_id,
            ];

        } catch (Exception $e) {
            $this->db->update('blockchain_payout_queue', [
                'status' => ($payout['retry_count'] < self::MAX_RETRIES) ? 'RETRYING' : 'FAILED',
                'retry_count' => $payout['retry_count'] + 1,
                'last_error' => $e->getMessage(),
                'updated_at' => date('Y-m-d H:i:s'),
            ], ['id' => $queue_id]);

            return [
                'queue_id' => $queue_id,
                'success' => false,
                'reason' => 'exception',
                'gas_used' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Estimate gas cost for a payout
     * Simplified: assume fixed base + percentage
     *
     * @param decimal $amount Payout amount
     * @return decimal Estimated gas cost
     */
    private function estimateGasCost($amount)
    {
        // Base gas (0.001) + 0.1% of amount
        $base_gas = 0.001;
        $percentage_gas = $amount * 0.001;
        return $base_gas + $percentage_gas;
    }

    /**
     * Broadcast a payout transaction to blockchain
     * PLACEHOLDER: In production, this integrates with actual blockchain API
     *
     * @param int $user_id Recipient
     * @param decimal $amount BMAN to transfer
     * @param string $wallet_type Destination wallet type
     * @return string Transaction hash, or false if failed
     */
    private function broadcastPayout($user_id, $amount, $wallet_type)
    {
        // TODO: Integrate with actual blockchain API (Ethereum, BSC, etc)
        // For now, simulate success

        // Generate simulated tx hash
        $tx_hash = 'TXH_' . bin2hex(random_bytes(16));

        log_message('info', "[BonusQueue] Broadcast: User $user_id, $amount BMAN to $wallet_type, tx: $tx_hash");

        return $tx_hash;
    }

    /**
     * Credit a user's wallet with bonus amount
     *
     * @param int $user_id User receiving bonus
     * @param decimal $amount Amount to add
     * @param string $wallet_type 'EARNING', 'STAKING', or 'CEILING'
     * @return bool Success
     */
    private function creditUserWallet($user_id, $amount, $wallet_type)
    {
        if ($wallet_type === 'EARNING') {
            $this->db->update('users', [
                'exchange_balance' => $this->db->raw('exchange_balance + ' . $amount),
            ], ['id' => $user_id]);
        } elseif ($wallet_type === 'STAKING') {
            $this->db->update('users', [
                'staking_wallet' => $this->db->raw('staking_wallet + ' . $amount),
            ], ['id' => $user_id]);
        } elseif ($wallet_type === 'CEILING') {
            $this->db->insert('ceiling_wallet', [
                'user_id' => $user_id,
                'balance' => $amount,
                'hold_type' => 'EARNING',
                'hold_reason' => 'CEILING_THRESHOLD',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return true;
    }

    /**
     * Update admin gas balance
     * @param decimal $gas_used Amount of gas used
     */
    private function updateAdminGasBalance($gas_used)
    {
        $this->db->update('admin_ceiling_wallet', [
            'gas_balance' => $this->db->raw('gas_balance - ' . $gas_used),
        ], ['id' => 1]);
    }

    /**
     * Get queue status summary
     * @return array Summary of pending/failed/completed payouts
     */
    public function getQueueStatus()
    {
        $result = $this->db->query(
            "SELECT
                status,
                COUNT(*) as count,
                SUM(amount) as total_amount,
                MAX(created_at) as latest
            FROM blockchain_payout_queue
            GROUP BY status"
        )->result_array();

        return array_reduce($result, function($carry, $row) {
            $carry[$row['status']] = [
                'count' => (int)$row['count'],
                'total_amount' => (float)$row['total_amount'],
                'latest' => $row['latest'],
            ];
            return $carry;
        }, []);
    }

    /**
     * Retry failed payouts (manual admin trigger)
     * @param int $max_count Max number to retry
     * @return int Number retried
     */
    public function retryFailedPayouts($max_count = 10)
    {
        $this->db->update('blockchain_payout_queue', [
            'status' => 'RETRYING',
            'retry_count' => 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ], [
            'status' => 'FAILED',
        ]);

        return $this->db->affected_rows();
    }
}
