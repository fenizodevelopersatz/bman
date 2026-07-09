<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * StakingPurchasecron — automatic USDT ↔ BMAN staking purchase processing
 *
 * Monitors staking_swap_orders and processes 3 steps:
 * 1. Gas Fee Payment (admin → user): 0.0008 BNB
 * 2. USDT Payment (user → admin): 100 USDT
 * 3. BMAN Distribution (admin → user): 1000 BMAN distributed per coin_distribution_option
 *
 * Flow:
 * - Creates order → pending_gas_fee (cron_status_gas=0)
 * - Detects gas fee → records TX, cron_status_gas=1, status→pending_usdt
 * - Detects USDT → records TX, cron_status_usdt=1, status→pending_bman
 * - Detects BMAN → distributes per option, cron_status_bman=1, status→swap_completed
 *
 * Coin Distribution Options (1-7):
 * 1: 100% exchange
 * 2: 90% exchange, 10% bonus
 * 3: 80% exchange, 10% earning, 10% bonus
 * 4: 80% exchange, 10% earning, 10% staking
 * 5: 90% exchange, 10% earning
 * 6: 90% exchange, 10% staking
 * 7: 70% exchange, 10% earning, 10% staking, 10% bonus
 *
 * Run it hourly:
 *   CLI  :  php index.php stakingpurchasecron run
 *   HTTP :  /staking-purchase-cron?token=YOUR_CRON_TOKEN
 */
class StakingPurchasecron extends CI_Controller
{
    private $log_prefix = '[STAKING_PURCHASE_CRON]';

    public function run()
    {
        // CLI always allowed; over HTTP require cron token
        if (!is_cli()) {
            $expected = $this->config->item('cron_token');
            if (!$expected || $this->input->get('token', true) !== $expected) {
                show_404();
            }
        }

        $this->load->model('Custodialwallet_model', 'wallet');

        try {
            $output = [];

            // Process Step 1: Gas fee detection
            $output['gas_fee'] = $this->_processGasFeeCron();

            // Process Step 2: USDT payment detection
            $output['usdt_payment'] = $this->_processUsdtPaymentCron();

            // Process Step 3: BMAN distribution detection
            $output['bman_transfer'] = $this->_processBmanTransferCron();

            $result = [
                'status' => 'success',
                'message' => 'Staking purchase cron completed',
                'details' => $output,
                'ran_at' => date('Y-m-d H:i:s'),
            ];
        } catch (Exception $e) {
            $result = [
                'status' => 'error',
                'message' => $e->getMessage(),
                'ran_at' => date('Y-m-d H:i:s'),
            ];
            log_message('error', $this->log_prefix . ' ' . $e->getMessage());
        }

        echo json_encode($result) . PHP_EOL;
    }

    /**
     * Step 1: Detect gas fee payments (BNB to user)
     */
    private function _processGasFeeCron()
    {
        $orders = $this->db->select('id, user_id, user_address, gas_tx_hash')
            ->where('status', 'pending_gas_fee')
            ->where('cron_status_gas', 0)
            ->limit(50)
            ->get('staking_swap_orders')
            ->result_array();

        $processed = 0;
        $failed = 0;

        foreach ($orders as $order) {
            try {
                if ($this->_detectAndRecordGasFee($order)) {
                    $processed++;
                } else {
                    $failed++;
                }
            } catch (Exception $e) {
                log_message('error', $this->log_prefix . ' Gas fee detection failed for order ' . $order['id'] . ': ' . $e->getMessage());
                $failed++;
            }
        }

        return [
            'processed' => $processed,
            'failed' => $failed,
            'total' => count($orders),
        ];
    }

    /**
     * Detect gas fee payment and record it
     */
    private function _detectAndRecordGasFee($order)
    {
        $user_address = strtolower($order['user_address']);
        $config = $this->db->get_where('token_settings', ['status' => 1])->row_array();
        $api_key = $config['etherscan_api_key'] ?? '';
        $etherscan_url = $config['etherscan_url'] ?? 'https://api.bscscan.com';

        // Get recent BNB transfers to user
        $url = $etherscan_url . '/api?module=account&action=txlist&address=' . $user_address
             . '&startblock=0&endblock=99999999&sort=desc&apikey=' . $api_key;

        $response = @file_get_contents($url);
        if (!$response) {
            return false;
        }

        $data = json_decode($response, true);
        if (empty($data['result'])) {
            return false;
        }

        // Look for BNB transfer (gas fee)
        foreach ($data['result'] as $tx) {
            $to = strtolower($tx['to'] ?? '');
            $value = (float)hexdec($tx['value'] ?? 0) / 1e18;

            if ($to === $user_address && $value >= 0.0005 && $value <= 0.01) {
                // Found gas fee payment
                $tx_hash = strtolower($tx['hash'] ?? '');

                // Record in onchain_transactions
                $this->db->insert('onchain_transactions', [
                    'tx_hash' => $tx_hash,
                    'from_address' => strtolower($tx['from'] ?? ''),
                    'to_address' => $to,
                    'amount' => $value * 1e18,
                    'tx_type' => 'gas_fee',
                    'status' => 'processing',
                    'block_number' => $tx['blockNumber'] ?? 0,
                    'user_id' => $order['user_id'],
                    'created_at' => date('Y-m-d H:i:s'),
                ]);

                // Update order
                $this->db->where('id', $order['id'])->update('staking_swap_orders', [
                    'gas_tx_hash' => $tx_hash,
                    'cron_status_gas' => 1,
                    'status' => 'pending_usdt',
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                log_message('info', $this->log_prefix . ' Gas fee detected for order ' . $order['id'] . ': ' . $tx_hash);
                return true;
            }
        }

        return false;
    }

    /**
     * Step 2: Detect USDT payments (user to admin)
     */
    private function _processUsdtPaymentCron()
    {
        $orders = $this->db->select('id, user_id, user_address, admin_address, usdt_amount')
            ->where('status', 'pending_usdt')
            ->where('cron_status_usdt', 0)
            ->limit(50)
            ->get('staking_swap_orders')
            ->result_array();

        $processed = 0;
        $failed = 0;

        foreach ($orders as $order) {
            try {
                if ($this->_detectAndRecordUsdtPayment($order)) {
                    $processed++;
                } else {
                    $failed++;
                }
            } catch (Exception $e) {
                log_message('error', $this->log_prefix . ' USDT detection failed for order ' . $order['id'] . ': ' . $e->getMessage());
                $failed++;
            }
        }

        return [
            'processed' => $processed,
            'failed' => $failed,
            'total' => count($orders),
        ];
    }

    /**
     * Detect USDT payment and record it
     */
    private function _detectAndRecordUsdtPayment($order)
    {
        $user_address = strtolower($order['user_address']);
        $admin_address = strtolower($order['admin_address']);
        $config = $this->db->get_where('token_settings', ['status' => 1])->row_array();
        $usdt_contract = strtolower($config['usdt_address'] ?? '');
        $api_key = $config['etherscan_api_key'] ?? '';
        $etherscan_url = $config['etherscan_url'] ?? 'https://api.bscscan.com';

        // Get USDT transfers from user
        $url = $etherscan_url . '/api?module=account&action=tokentx&contractaddress=' . $usdt_contract
             . '&address=' . $user_address . '&startblock=0&endblock=99999999&sort=desc&apikey=' . $api_key;

        $response = @file_get_contents($url);
        if (!$response) {
            return false;
        }

        $data = json_decode($response, true);
        if (empty($data['result'])) {
            return false;
        }

        // Look for USDT transfer to admin
        foreach ($data['result'] as $tx) {
            $from = strtolower($tx['from'] ?? '');
            $to = strtolower($tx['to'] ?? '');
            $value = $tx['value'] ?? 0;

            if ($from === $user_address && $to === $admin_address) {
                // Found USDT payment
                $tx_hash = strtolower($tx['hash'] ?? '');

                // Record in onchain_transactions
                $this->db->insert('onchain_transactions', [
                    'tx_hash' => $tx_hash,
                    'from_address' => $from,
                    'to_address' => $to,
                    'amount' => $value,
                    'tx_type' => 'deposit',
                    'status' => 'processing',
                    'block_number' => $tx['blockNumber'] ?? 0,
                    'user_id' => $order['user_id'],
                    'created_at' => date('Y-m-d H:i:s'),
                ]);

                // Update order
                $this->db->where('id', $order['id'])->update('staking_swap_orders', [
                    'usdt_tx_hash' => $tx_hash,
                    'cron_status_usdt' => 1,
                    'status' => 'pending_bman',
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                log_message('info', $this->log_prefix . ' USDT payment detected for order ' . $order['id'] . ': ' . $tx_hash);
                return true;
            }
        }

        return false;
    }

    /**
     * Step 3: Detect BMAN transfers (admin to user) + distribute per option
     */
    private function _processBmanTransferCron()
    {
        $orders = $this->db->select('id, user_id, user_address, admin_address, bman_amount, bonus_bman, coin_distribution_option')
            ->where('status', 'pending_bman')
            ->where('cron_status_bman', 0)
            ->limit(50)
            ->get('staking_swap_orders')
            ->result_array();

        $processed = 0;
        $failed = 0;

        foreach ($orders as $order) {
            try {
                if ($this->_detectAndDistributeBman($order)) {
                    $processed++;
                } else {
                    $failed++;
                }
            } catch (Exception $e) {
                log_message('error', $this->log_prefix . ' BMAN detection failed for order ' . $order['id'] . ': ' . $e->getMessage());
                $failed++;
            }
        }

        return [
            'processed' => $processed,
            'failed' => $failed,
            'total' => count($orders),
        ];
    }

    /**
     * Detect BMAN transfer and distribute to wallets per option
     */
    private function _detectAndDistributeBman($order)
    {
        $user_address = strtolower($order['user_address']);
        $admin_address = strtolower($order['admin_address']);
        $config = $this->db->get_where('token_settings', ['status' => 1])->row_array();
        $bman_contract = strtolower($config['bman_address'] ?? '');
        $api_key = $config['etherscan_api_key'] ?? '';
        $etherscan_url = $config['etherscan_url'] ?? 'https://api.bscscan.com';

        // Get BMAN transfers from admin
        $url = $etherscan_url . '/api?module=account&action=tokentx&contractaddress=' . $bman_contract
             . '&address=' . $admin_address . '&startblock=0&endblock=99999999&sort=desc&apikey=' . $api_key;

        $response = @file_get_contents($url);
        if (!$response) {
            return false;
        }

        $data = json_decode($response, true);
        if (empty($data['result'])) {
            return false;
        }

        // Look for BMAN transfer to user
        foreach ($data['result'] as $tx) {
            $from = strtolower($tx['from'] ?? '');
            $to = strtolower($tx['to'] ?? '');
            $value = $tx['value'] ?? 0;

            if ($from === $admin_address && $to === $user_address) {
                // Found BMAN transfer
                $tx_hash = strtolower($tx['hash'] ?? '');

                // Record in onchain_transactions
                $this->db->insert('onchain_transactions', [
                    'tx_hash' => $tx_hash,
                    'from_address' => $from,
                    'to_address' => $to,
                    'amount' => $value,
                    'tx_type' => 'transfer',
                    'status' => 'processing',
                    'block_number' => $tx['blockNumber'] ?? 0,
                    'user_id' => $order['user_id'],
                    'created_at' => date('Y-m-d H:i:s'),
                ]);

                // Distribute BMAN to wallets based on coin_distribution_option (1-7)
                $this->_distributeBmanToWallets($order);

                // Create staking record
                $this->db->insert('user_stakes', [
                    'user_id' => $order['user_id'],
                    'package_id' => $order['package_id'] ?? 0,
                    'bman_amount' => (float)$order['bman_amount'],
                    'bonus_bman' => (float)($order['bonus_bman'] ?? 0),
                    'status' => 'active',
                    'activated_at' => date('Y-m-d H:i:s'),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);

                // Update order
                $this->db->where('id', $order['id'])->update('staking_swap_orders', [
                    'bman_tx_hash' => $tx_hash,
                    'cron_status_bman' => 1,
                    'status' => 'swap_completed',
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                log_message('info', $this->log_prefix . ' BMAN transfer detected and distributed for order ' . $order['id'] . ': ' . $tx_hash);
                return true;
            }
        }

        return false;
    }

    /**
     * Distribute BMAN to wallets based on coin_distribution_option (1-7)
     */
    private function _distributeBmanToWallets($order)
    {
        $option = (int)($order['coin_distribution_option'] ?? 1);
        $bman_amount = (float)$order['bman_amount'];

        // Distribution percentages for each option
        $distribution_map = [
            1 => ['exchange' => 100, 'earning' => 0,  'staking' => 0,  'bonus' => 0],
            2 => ['exchange' => 90,  'earning' => 0,  'staking' => 0,  'bonus' => 10],
            3 => ['exchange' => 80,  'earning' => 10, 'staking' => 0,  'bonus' => 10],
            4 => ['exchange' => 80,  'earning' => 10, 'staking' => 10, 'bonus' => 0],
            5 => ['exchange' => 90,  'earning' => 10, 'staking' => 0,  'bonus' => 0],
            6 => ['exchange' => 90,  'earning' => 0,  'staking' => 10, 'bonus' => 0],
            7 => ['exchange' => 70,  'earning' => 10, 'staking' => 10, 'bonus' => 10],
        ];

        $percentages = $distribution_map[$option] ?? $distribution_map[1];

        // Apply distribution to wallet_ledger
        foreach ($percentages as $wallet => $percent) {
            if ($percent > 0) {
                $amount = $bman_amount * ($percent / 100);
                $this->db->where('user_id', $order['user_id'])
                    ->set($wallet, "{$wallet} + {$amount}", false)
                    ->update('wallet_ledger');
            }
        }

        log_message('info', $this->log_prefix . ' Distributed ' . $bman_amount . ' BMAN per option ' . $option . ' to user ' . $order['user_id']);
    }
}
