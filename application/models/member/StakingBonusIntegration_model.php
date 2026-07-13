<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Integration layer between StakingPurchasecron and BinaryMatchingBonus
 *
 * Triggered when: A staking purchase's BMAN leg confirms on-chain
 * Actions:
 *   1. Trigger binary matching bonus calculation
 *   2. Queue blockchain payouts (via BonusTransactionQueue)
 *   3. Log all transactions to audit trail
 */
class StakingBonusIntegration_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('member/BinaryMatchingBonus_model');
        $this->load->model('member/BonusTransactionQueue_model');
    }

    /**
     * Called from StakingPurchasecron after BMAN leg is confirmed on-chain
     *
     * Entry point for binary matching calculations
     *
     * @param int $swap_order_id The staking_swap_orders ID
     * @param int $buyer_id The user who purchased
     * @param decimal $bman_amount The BMAN amount they purchased
     * @param string $tx_hash The on-chain transaction hash for the BMAN transfer
     * @return array ['status' => true/false, 'bonus_details' => ...]
     */
    public function onStakingBmanTransferConfirmed($swap_order_id, $buyer_id, $bman_amount, $tx_hash)
    {
        log_message('info', "[StakingBonusIntegration] Starting bonus calc for order $swap_order_id (user $buyer_id, amount $bman_amount)");

        try {
            $this->db->trans_start();

            // Step 1: Calculate binary matching bonuses
            $bonus_result = $this->BinaryMatchingBonus_model->calculateBonuses(
                $swap_order_id,
                $buyer_id,
                $bman_amount
            );

            if (!$bonus_result['status']) {
                log_message('error', "[StakingBonusIntegration] Calculation failed: " . $bonus_result['message']);
                $this->db->trans_rollback();
                return [
                    'status' => false,
                    'message' => 'Bonus calculation failed',
                    'error' => $bonus_result['message'] ?? 'Unknown error',
                ];
            }

            // Step 2: Queue blockchain payouts for each bonus
            $payout_queue_result = $this->queueBonusPayouts($bonus_result, $tx_hash);

            // Step 3: Record integration in audit log
            $this->recordIntegrationLog($swap_order_id, $buyer_id, $bonus_result, $payout_queue_result);

            $this->db->trans_complete();

            log_message('info', "[StakingBonusIntegration] Complete. Bonuses: $bonus_result[bonus_count], Payouts queued: " . count($payout_queue_result['queued']));

            return [
                'status' => true,
                'swap_order_id' => $swap_order_id,
                'bonuses_calculated' => $bonus_result['bonus_count'],
                'total_bonus' => $bonus_result['total_bonus'],
                'payouts_queued' => count($payout_queue_result['queued']),
                'payout_details' => $payout_queue_result['queued'],
            ];

        } catch (Exception $e) {
            $this->db->trans_rollback();
            log_message('error', "[StakingBonusIntegration] Exception: " . $e->getMessage());
            return [
                'status' => false,
                'message' => 'Exception during integration',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Convert calculated bonuses into blockchain payout queue entries
     * Each bonus level → 2 payouts (earning + staking wallets)
     *
     * @param array $bonus_result From BinaryMatchingBonus_model::calculateBonuses()
     * @param string $source_tx_hash The BMAN purchase tx hash (for audit)
     * @return array ['queued' => [...], 'failed' => [...]]
     */
    private function queueBonusPayouts($bonus_result, $source_tx_hash)
    {
        $result = [
            'queued' => [],
            'failed' => [],
        ];

        if (empty($bonus_result['bonuses_by_level'])) {
            return $result;
        }

        foreach ($bonus_result['bonuses_by_level'] as $level => $bonus) {
            $recipient_id = $bonus['bonus_recipient_id'];

            // Get the bonus ledger ID for this calculation
            $ledger_row = $this->db->query(
                "SELECT id FROM binary_matching_bonus_ledger
                 WHERE purchase_id = ? AND bonus_recipient_id = ? AND level = ?
                 ORDER BY id DESC LIMIT 1",
                [$bonus_result['purchase_id'], $recipient_id, $level]
            )->row();

            if (!$ledger_row) {
                $result['failed'][] = [
                    'level' => $level,
                    'recipient' => $recipient_id,
                    'reason' => 'Ledger entry not found',
                ];
                continue;
            }

            $ledger_id = $ledger_row->id;

            // Queue earning wallet payout (8%)
            if ($bonus['bonus_earning'] > 0) {
                $payout_result = $this->BonusTransactionQueue_model->queuePayout(
                    $ledger_id,
                    $recipient_id,
                    'EARNING',
                    $bonus['bonus_earning']
                );

                if ($payout_result['status']) {
                    $result['queued'][] = [
                        'level' => $level,
                        'recipient' => $recipient_id,
                        'wallet' => 'EARNING',
                        'amount' => $bonus['bonus_earning'],
                        'queue_id' => $payout_result['queue_id'],
                        'source_tx' => $source_tx_hash,
                    ];
                } else {
                    $result['failed'][] = [
                        'level' => $level,
                        'recipient' => $recipient_id,
                        'wallet' => 'EARNING',
                        'reason' => $payout_result['message'],
                    ];
                }
            }

            // Queue staking wallet payout (2%)
            if ($bonus['bonus_staking'] > 0) {
                $payout_result = $this->BonusTransactionQueue_model->queuePayout(
                    $ledger_id,
                    $recipient_id,
                    'STAKING',
                    $bonus['bonus_staking']
                );

                if ($payout_result['status']) {
                    $result['queued'][] = [
                        'level' => $level,
                        'recipient' => $recipient_id,
                        'wallet' => 'STAKING',
                        'amount' => $bonus['bonus_staking'],
                        'queue_id' => $payout_result['queue_id'],
                        'source_tx' => $source_tx_hash,
                    ];
                } else {
                    $result['failed'][] = [
                        'level' => $level,
                        'recipient' => $recipient_id,
                        'wallet' => 'STAKING',
                        'reason' => $payout_result['message'],
                    ];
                }
            }

            // Handle ceiling holds (don't queue, but log)
            if ($bonus['ceiling_held'] > 0) {
                log_message('info', "Level $level bonus for user $recipient_id held in ceiling: " . $bonus['ceiling_held']);
            }
        }

        return $result;
    }

    /**
     * Record this integration event in audit log
     * For compliance and debugging
     *
     * @param int $swap_order_id
     * @param int $buyer_id
     * @param array $bonus_result
     * @param array $payout_result
     */
    private function recordIntegrationLog($swap_order_id, $buyer_id, $bonus_result, $payout_result)
    {
        $log_entry = [
            'event_type' => 'STAKING_BONUS_INTEGRATION',
            'swap_order_id' => $swap_order_id,
            'buyer_user_id' => $buyer_id,
            'bonuses_calculated' => $bonus_result['bonus_count'],
            'total_bonus_amount' => $bonus_result['total_bonus'],
            'payouts_queued' => count($payout_result['queued']),
            'payouts_failed' => count($payout_result['failed']),
            'data' => json_encode([
                'bonus_result' => $bonus_result,
                'payout_result' => $payout_result,
            ]),
            'created_at' => date('Y-m-d H:i:s'),
        ];

        // Insert into a log table if it exists, otherwise just log
        $tables = $this->db->list_tables();
        if (in_array('integration_logs', $tables)) {
            $this->db->insert('integration_logs', $log_entry);
        }

        log_message('info', "[StakingBonusIntegration] Log: " . json_encode($log_entry));
    }

    /**
     * Get bonus summary for a staking order
     * @param int $swap_order_id
     * @return array Summary of all bonuses from this order
     */
    public function getOrderBonusSummary($swap_order_id)
    {
        $bonuses = $this->db->query(
            "SELECT
                level,
                bonus_recipient_id,
                SUM(bonus_earning) as total_earning,
                SUM(bonus_staking) as total_staking,
                SUM(bonus_amount_total) as total_bonus,
                COUNT(*) as count,
                GROUP_CONCAT(status) as statuses
            FROM binary_matching_bonus_ledger
            WHERE purchase_id = ?
            GROUP BY level, bonus_recipient_id
            ORDER BY level ASC",
            [$swap_order_id]
        )->result_array();

        return [
            'swap_order_id' => $swap_order_id,
            'total_recipients' => count($bonuses),
            'bonuses_by_level' => $bonuses,
            'total_earnings' => array_sum(array_column($bonuses, 'total_earning')),
            'total_staking' => array_sum(array_column($bonuses, 'total_staking')),
        ];
    }
}
