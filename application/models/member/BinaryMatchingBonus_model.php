<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class BinaryMatchingBonus_model extends CI_Model
{
    const MAX_BONUS_LEVELS = 10;
    const TOTAL_BONUS_PERCENT = 0.10;      // 10%
    const EARNING_WALLET_PERCENT = 0.08;   // 8%
    const STAKING_WALLET_PERCENT = 0.02;   // 2%

    public function __construct()
    {
        parent::__construct();
        $this->load->model('member/BinaryModel');
    }

    /**
     * Calculate binary matching bonuses for a staking purchase
     * Main entry point for bonus calculation
     *
     * @param int $purchase_id The staking purchase ID
     * @param int $buyer_id The user who made the purchase
     * @param decimal $purchase_amount The BMAN amount purchased
     * @return array ['status' => true/false, 'bonus_count' => n, 'total_bonus' => amount, 'errors' => []]
     */
    public function calculateBonuses($purchase_id, $buyer_id, $purchase_amount)
    {
        if (!$purchase_id || !$buyer_id || $purchase_amount <= 0) {
            return ['status' => false, 'message' => 'Invalid purchase parameters'];
        }

        // Get buyer's sponsor (parent in binary tree)
        $sponsor_id = $this->BinaryModel->getUserSponsor($buyer_id);
        if (!$sponsor_id) {
            return ['status' => true, 'bonus_count' => 0, 'total_bonus' => 0, 'message' => 'No sponsor found'];
        }

        try {
            $this->db->trans_start();

            // Start processing queue entry
            $this->db->insert('binary_matching_queue', [
                'purchase_id' => $purchase_id,
                'status' => 'PROCESSING',
                'started_at' => date('Y-m-d H:i:s'),
            ]);

            $result = [
                'status' => true,
                'purchase_id' => $purchase_id,
                'bonus_count' => 0,
                'total_bonus' => 0,
                'bonuses_by_level' => [],
                'errors' => [],
            ];

            // Calculate bonuses for each level up the tree
            for ($level = 1; $level <= self::MAX_BONUS_LEVELS; $level++) {
                $bonus = $this->calculateBonusAtLevel($sponsor_id, $level, $purchase_id, $buyer_id, $purchase_amount);

                if ($bonus['status'] === false) {
                    if ($level === 1) {
                        // Level 1 failure is critical
                        $result['errors'][] = $bonus['message'];
                        break;
                    } else {
                        // Level 2+ can fail gracefully
                        continue;
                    }
                }

                if ($bonus['qualifying_volume'] <= 0) {
                    // No more qualifiers at this level, stop upline
                    break;
                }

                $result['bonuses_by_level'][$level] = $bonus;
                $result['total_bonus'] += $bonus['bonus_total'];
                $result['bonus_count']++;
            }

            // Update queue status
            $this->db->update('binary_matching_queue', [
                'status' => ($result['bonus_count'] > 0) ? 'COMPLETED' : 'FAILED',
                'levels_processed' => $result['bonus_count'],
                'completed_at' => date('Y-m-d H:i:s'),
            ], ['purchase_id' => $purchase_id]);

            $this->db->trans_complete();

            return $result;

        } catch (Exception $e) {
            $this->db->trans_rollback();
            return [
                'status' => false,
                'message' => 'Exception: ' . $e->getMessage(),
                'bonus_count' => 0,
                'total_bonus' => 0,
            ];
        }
    }

    /**
     * Calculate bonus at a specific level in the upline
     *
     * @param int $current_node_id The node to calculate for
     * @param int $level The level (1=direct parent, 2=grandparent, etc)
     * @param int $purchase_id Source purchase ID
     * @param int $buyer_id The user who purchased
     * @param decimal $purchase_amount The purchase amount
     * @return array Bonus details
     */
    private function calculateBonusAtLevel($current_node_id, $level, $purchase_id, $buyer_id, $purchase_amount)
    {
        // Get the node at this level
        $node = $this->BinaryModel->getUserById($current_node_id);
        if (!$node) {
            return [
                'status' => false,
                'message' => "Node not found at level $level",
            ];
        }

        // Get left and right leg volumes
        $left_volume = $this->getBinaryLegVolume($current_node_id, 'left', $level);
        $right_volume = $this->getBinaryLegVolume($current_node_id, 'right', $level);

        // Qualifying volume is the minimum of the two legs
        $qualifying_volume = min($left_volume, $right_volume);

        if ($qualifying_volume <= 0) {
            return [
                'status' => true,
                'bonus_recipient_id' => $current_node_id,
                'level' => $level,
                'left_volume' => $left_volume,
                'right_volume' => $right_volume,
                'qualifying_volume' => 0,
                'bonus_total' => 0,
                'bonus_earning' => 0,
                'bonus_staking' => 0,
            ];
        }

        // Calculate bonus amounts
        $bonus_total = $qualifying_volume * self::TOTAL_BONUS_PERCENT;
        $bonus_earning = $bonus_total * self::EARNING_WALLET_PERCENT / self::TOTAL_BONUS_PERCENT;
        $bonus_staking = $bonus_total * self::STAKING_WALLET_PERCENT / self::TOTAL_BONUS_PERCENT;

        // Check ceiling wallet constraints
        $ceiling_held = $this->checkAndApplyCeiling($current_node_id, $bonus_earning, $bonus_staking);

        // Log the bonus calculation
        $this->db->insert('binary_matching_bonus_ledger', [
            'purchase_id' => $purchase_id,
            'bonus_recipient_id' => $current_node_id,
            'bonus_payer_id' => $buyer_id,
            'level' => $level,
            'left_leg_volume' => $left_volume,
            'right_leg_volume' => $right_volume,
            'qualifying_volume' => $qualifying_volume,
            'bonus_amount_total' => $bonus_total,
            'bonus_earning' => $bonus_earning,
            'bonus_staking' => $bonus_staking,
            'status' => ($ceiling_held > 0) ? 'HELD_CEILING' : 'CALCULATED',
            'ceiling_amount_held' => $ceiling_held,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Move to next level (get the sponsor)
        $next_sponsor = $this->BinaryModel->getUserSponsor($current_node_id);
        if ($next_sponsor && $level < self::MAX_BONUS_LEVELS) {
            // Will continue to next level
        }

        return [
            'status' => true,
            'bonus_recipient_id' => $current_node_id,
            'level' => $level,
            'left_volume' => $left_volume,
            'right_volume' => $right_volume,
            'qualifying_volume' => $qualifying_volume,
            'bonus_total' => $bonus_total,
            'bonus_earning' => $bonus_earning,
            'bonus_staking' => $bonus_staking,
            'ceiling_held' => $ceiling_held,
            'next_node' => $next_sponsor,
        ];
    }

    /**
     * Get the total investment volume in a binary leg
     * Recursively sums all exchange wallet balances in one direction
     *
     * @param int $node_id Starting node
     * @param string $direction 'left' or 'right'
     * @param int $max_depth How deep to go (for this version, 1 level only)
     * @return decimal Total volume
     */
    private function getBinaryLegVolume($node_id, $direction, $max_depth = 1)
    {
        $total = 0;

        // Get immediate children in this direction
        $this->db->select('users.id, users.exchange_balance');
        $this->db->from('users');
        $this->db->join('binary_placement', 'users.id = binary_placement.user_id', 'left');
        $this->db->where('binary_placement.parent_id', $node_id);
        $this->db->where('binary_placement.position', ucfirst($direction));
        $child = $this->db->get()->row();

        if (!$child) {
            return 0;
        }

        // Add this child's exchange wallet
        $total += (float) $child->exchange_balance;

        // If we can go deeper, recurse into this child's leg
        if ($max_depth > 1) {
            $total += $this->getBinaryLegVolume($child->id, $direction, $max_depth - 1);
        }

        return $total;
    }

    /**
     * Check ceiling wallet constraints and apply holds if needed
     * Returns amount that was held due to ceiling
     *
     * @param int $user_id User to check ceiling for
     * @param decimal $earning_amount Earning wallet amount
     * @param decimal $staking_amount Staking wallet amount
     * @return decimal Amount held in ceiling
     */
    private function checkAndApplyCeiling($user_id, $earning_amount, $staking_amount)
    {
        // Get user's current ceiling wallet balance
        $ceiling = $this->db->get_where('ceiling_wallet', ['user_id' => $user_id])->row();
        $current_ceiling = $ceiling ? (float)$ceiling->balance : 0;

        // Get user's package/threshold config
        $user = $this->db->get_where('users', ['id' => $user_id])->row();
        if (!$user) {
            return 0;
        }

        // Get ceiling config for this package (simplified: use total exchange as proxy)
        $config = $this->db->query(
            "SELECT * FROM ceiling_wallet_config
             WHERE threshold_amount <= ?
             ORDER BY threshold_amount DESC LIMIT 1",
            [(int)$user->exchange_balance]
        )->row();

        if (!$config) {
            // No ceiling applies
            return 0;
        }

        $ceiling_cap = (float) $config->ceiling_cap;
        $new_ceiling = $current_ceiling + $earning_amount;

        if ($new_ceiling > $ceiling_cap) {
            $held_amount = $new_ceiling - $ceiling_cap;

            // Hold the excess in admin ceiling wallet
            $this->db->insert('ceiling_wallet_ledger', [
                'user_id' => $user_id,
                'transaction_type' => 'HOLD',
                'amount' => $held_amount,
                'from_wallet' => 'EARNING',
                'reason' => 'CEILING_THRESHOLD_EXCEEDED',
                'triggered_by' => 'BinaryMatchingBonus_model',
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            // Transfer to admin ceiling
            $this->db->update('admin_ceiling_wallet', [
                'balance' => $this->db->raw('balance + ' . $held_amount),
                'total_received' => $this->db->raw('total_received + ' . $held_amount),
                'updated_at' => date('Y-m-d H:i:s'),
            ], ['id' => 1]);

            // Update user's ceiling wallet held amount
            $this->db->update('users', [
                'ceiling_wallet_held' => $this->db->raw('ceiling_wallet_held + ' . $held_amount),
            ], ['id' => $user_id]);

            return (float) $held_amount;
        }

        return 0;
    }

    /**
     * Get bonus ledger for a specific user
     * @param int $user_id
     * @return array All bonuses received by this user
     */
    public function getBonusesForUser($user_id)
    {
        return $this->db->get_where('binary_matching_bonus_ledger',
            ['bonus_recipient_id' => $user_id])->result_array();
    }

    /**
     * Get bonus stats for dashboard
     * @param int $user_id
     * @return array Summary stats
     */
    public function getBonusStats($user_id)
    {
        $result = $this->db->query(
            "SELECT
                COUNT(*) as total_bonuses,
                SUM(bonus_earning) as total_earning,
                SUM(bonus_staking) as total_staking,
                SUM(ceiling_amount_held) as total_held,
                SUM(bonus_earning + bonus_staking) as total_received
            FROM binary_matching_bonus_ledger
            WHERE bonus_recipient_id = ? AND status IN ('DISTRIBUTED', 'HELD_CEILING')",
            [$user_id]
        )->row();

        return [
            'total_bonuses' => (int)($result->total_bonuses ?? 0),
            'total_earning' => (float)($result->total_earning ?? 0),
            'total_staking' => (float)($result->total_staking ?? 0),
            'total_held_ceiling' => (float)($result->total_held ?? 0),
            'total_received' => (float)($result->total_received ?? 0),
        ];
    }
}
