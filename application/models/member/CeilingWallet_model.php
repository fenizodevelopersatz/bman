<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class CeilingWallet_model extends CI_Model
{
    /**
     * Get ceiling wallet balance for a user
     * @param int $user_id
     * @return decimal Current ceiling balance
     */
    public function getCeilingBalance($user_id)
    {
        $wallet = $this->db->get_where('ceiling_wallet', ['user_id' => $user_id])->row();
        return $wallet ? (float)$wallet->balance : 0;
    }

    /**
     * Get ceiling config for a user based on their staking level
     * @param int $user_id
     * @return object Ceiling config or null
     */
    public function getCeilingConfig($user_id)
    {
        $user = $this->db->get_where('users', ['id' => $user_id])->row();
        if (!$user) {
            return null;
        }

        $exchange_balance = (float) $user->exchange_balance;

        // Get the highest threshold that applies to this user's balance
        $config = $this->db->query(
            "SELECT * FROM ceiling_wallet_config
             WHERE threshold_amount <= ?
             ORDER BY threshold_amount DESC
             LIMIT 1",
            [$exchange_balance]
        )->row();

        return $config;
    }

    /**
     * Check if user's ceiling has been exceeded
     * @param int $user_id
     * @return bool True if ceiling exceeded
     */
    public function isCeilingExceeded($user_id)
    {
        $user = $this->db->get_where('users', ['id' => $user_id])->row();
        if (!$user) {
            return false;
        }

        $config = $this->getCeilingConfig($user_id);
        if (!$config) {
            return false;
        }

        $ceiling_cap = (float) $config->ceiling_cap;
        $current_ceiling = $this->getCeilingBalance($user_id);

        return $current_ceiling >= $ceiling_cap;
    }

    /**
     * Release ceiling holds for a user
     * Called when user purchases more or upgrades package
     * @param int $user_id
     * @param decimal $amount Amount to release (null = release all)
     * @return array ['released' => amount, 'remaining' => amount]
     */
    public function releaseCeilingHold($user_id, $amount = null)
    {
        $current_balance = $this->getCeilingBalance($user_id);

        if ($current_balance <= 0) {
            return ['released' => 0, 'remaining' => 0];
        }

        $amount_to_release = $amount ? min($amount, $current_balance) : $current_balance;

        try {
            $this->db->trans_start();

            // Update ceiling wallet
            $this->db->update('ceiling_wallet', [
                'balance' => $this->db->raw('balance - ' . $amount_to_release),
                'updated_at' => date('Y-m-d H:i:s'),
            ], ['user_id' => $user_id]);

            // Log the release
            $this->db->insert('ceiling_wallet_ledger', [
                'user_id' => $user_id,
                'transaction_type' => 'RELEASE',
                'amount' => $amount_to_release,
                'from_wallet' => 'EARNING',
                'reason' => $amount ? 'PARTIAL_RELEASE' : 'FULL_RELEASE',
                'triggered_by' => 'CeilingWallet_model',
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            // Transfer released amount back to user's earning wallet
            $this->db->update('users', [
                'exchange_balance' => $this->db->raw('exchange_balance + ' . $amount_to_release),
                'ceiling_wallet_held' => $this->db->raw('ceiling_wallet_held - ' . $amount_to_release),
            ], ['id' => $user_id]);

            // Also update admin ceiling wallet
            $this->db->update('admin_ceiling_wallet', [
                'balance' => $this->db->raw('balance - ' . $amount_to_release),
                'total_distributed' => $this->db->raw('total_distributed + ' . $amount_to_release),
            ], ['id' => 1]);

            $this->db->trans_complete();

            return [
                'released' => (float)$amount_to_release,
                'remaining' => (float)($current_balance - $amount_to_release),
            ];

        } catch (Exception $e) {
            $this->db->trans_rollback();
            return [
                'error' => $e->getMessage(),
                'released' => 0,
                'remaining' => (float)$current_balance,
            ];
        }
    }

    /**
     * Get ceiling ledger for a user
     * @param int $user_id
     * @return array All ceiling transactions
     */
    public function getCeilingLedger($user_id)
    {
        return $this->db->query(
            "SELECT * FROM ceiling_wallet_ledger
             WHERE user_id = ?
             ORDER BY created_at DESC",
            [$user_id]
        )->result_array();
    }

    /**
     * Get admin ceiling wallet summary
     * @return object Admin ceiling wallet info
     */
    public function getAdminCeilingSummary()
    {
        $admin_wallet = $this->db->get_where('admin_ceiling_wallet', ['id' => 1])->row();

        if (!$admin_wallet) {
            return null;
        }

        // Get breakdown by user
        $by_user = $this->db->query(
            "SELECT user_id, SUM(amount) as total_held
             FROM ceiling_wallet_ledger
             WHERE transaction_type = 'HOLD'
             GROUP BY user_id
             ORDER BY total_held DESC
             LIMIT 20"
        )->result_array();

        return [
            'admin_wallet' => $admin_wallet,
            'held_by_user' => $by_user,
            'total_held' => (float)$admin_wallet->balance,
            'gas_balance' => (float)$admin_wallet->gas_balance,
            'gas_alert' => (float)$admin_wallet->gas_balance < 0.1,
        ];
    }

    /**
     * Update admin gas balance
     * Called when admin tops up gas fees
     * @param decimal $amount Gas amount to add
     * @return bool Success
     */
    public function addAdminGas($amount)
    {
        $this->db->update('admin_ceiling_wallet', [
            'gas_balance' => $this->db->raw('gas_balance + ' . $amount),
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => 1]);

        return $this->db->affected_rows() > 0;
    }
}
