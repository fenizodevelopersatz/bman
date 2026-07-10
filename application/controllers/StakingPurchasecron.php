<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * StakingPurchasecron — automatic USDT ↔ BMAN staking purchase processing
 *
 * Granular cron status tracking with 8 independent TX steps:
 * 1. gas_cron_status: Gas Fee (0.0008 BNB admin → user)
 * 2. usdt_cron_status: USDT Payment (100 USDT user → admin)
 * 3. bonus_cron_status: Bonus BMAN (if configured)
 * 4. bman_exchange_cron_status: BMAN to exchange wallet (ALWAYS)
 * 5. bman_earning_cron_status: BMAN to earning wallet (optional - options 3,5,7)
 * 6. bman_staking_cron_status: BMAN to staking wallet (optional - options 4,6,7)
 * 7. bman_bonus_cron_status: BMAN to bonus wallet (optional - options 2,3,7)
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
 * Cron Logic:
 * - CHECK each cron_status field BEFORE processing (only process if = 0)
 * - SKIP already-completed steps (cron_status = 1)
 * - SKIP unused wallet steps based on coin_distribution_option
 * - Mark unused wallets as cron_status = 1 so they're not re-checked
 * - Query excludes fully-completed orders (all relevant steps = 1)
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

            // Process each order's pending steps independently
            $output['steps'] = $this->_processAllPendingSteps();

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
     * Main processing loop: check each order's pending steps
     */
    private function _processAllPendingSteps()
    {
        $orders = $this->_getPendingOrders();
        $summary = [
            'total_orders' => count($orders),
            'gas' => ['processed' => 0, 'failed' => 0],
            'usdt' => ['processed' => 0, 'failed' => 0],
            'bonus' => ['processed' => 0, 'failed' => 0],
            'bman_exchange' => ['processed' => 0, 'failed' => 0],
            'bman_earning' => ['processed' => 0, 'failed' => 0],
            'bman_staking' => ['processed' => 0, 'failed' => 0],
            'bman_bonus' => ['processed' => 0, 'failed' => 0],
        ];

        foreach ($orders as $order) {
            try {
                $this->_processOrderSteps($order, $summary);
            } catch (Exception $e) {
                log_message('error', $this->log_prefix . ' Error processing order ' . $order['id'] . ': ' . $e->getMessage());
            }
        }

        return $summary;
    }

    /**
     * Get all orders with ANY pending cron_status
     * Excludes fully-completed orders
     */
    private function _getPendingOrders()
    {
        return $this->db->select(
                'id, user_id, user_address, admin_address, bman_amount, bonus_bman, coin_distribution_option, ' .
                'gas_cron_status, usdt_cron_status, bonus_cron_status, ' .
                'bman_exchange_cron_status, bman_earning_cron_status, bman_staking_cron_status, bman_bonus_cron_status'
            )
            ->where('status IN (\'pending_gas_fee\', \'pending_usdt\', \'pending_bman\')', null, false)
            ->or_where('status = \'swap_completed\' AND (gas_cron_status = 0 OR usdt_cron_status = 0 OR bonus_cron_status = 0 OR ' .
                'bman_exchange_cron_status = 0 OR bman_earning_cron_status = 0 OR bman_staking_cron_status = 0 OR bman_bonus_cron_status = 0)', null, false)
            ->limit(50)
            ->get('staking_swap_orders')
            ->result_array();
    }

    /**
     * Process all pending steps for one order
     */
    private function _processOrderSteps(&$order, &$summary)
    {
        // Step 1: Gas Fee (always required)
        if ($order['gas_cron_status'] == 0) {
            if ($this->_detectAndRecordGasFee($order)) {
                $summary['gas']['processed']++;
            } else {
                $summary['gas']['failed']++;
            }
        }

        // Step 2: USDT Payment (always required)
        if ($order['usdt_cron_status'] == 0) {
            if ($this->_detectAndRecordUsdtPayment($order)) {
                $summary['usdt']['processed']++;
            } else {
                $summary['usdt']['failed']++;
            }
        }

        // Step 3: Bonus BMAN (always required for fairness tracking)
        if ($order['bonus_cron_status'] == 0) {
            if ($this->_detectAndRecordBonusBman($order)) {
                $summary['bonus']['processed']++;
            } else {
                $summary['bonus']['failed']++;
            }
        }

        // Step 4-7: BMAN Distribution to wallets (only process if status is pending_bman or swap_completed)
        if (in_array($order['status'] ?? null, ['pending_bman', 'swap_completed'])) {
            $this->_processWalletDistributions($order, $summary);
        }
    }

    /**
     * Process wallet distributions based on coin_distribution_option
     * Each wallet (exchange, earning, staking, bonus) has independent tracking
     */
    private function _processWalletDistributions(&$order, &$summary)
    {
        $option = (int)($order['coin_distribution_option'] ?? 1);

        // Step 4: BMAN to Exchange (always required in all options)
        if ($order['bman_exchange_cron_status'] == 0) {
            if ($this->_detectAndDistributeBmanToExchange($order)) {
                $summary['bman_exchange']['processed']++;
            } else {
                $summary['bman_exchange']['failed']++;
            }
        }

        // Step 5: BMAN to Earning (optional - options 3, 5, 7)
        if ($this->_shouldProcessEarning($option)) {
            if ($order['bman_earning_cron_status'] == 0) {
                if ($this->_detectAndDistributeBmanToEarning($order)) {
                    $summary['bman_earning']['processed']++;
                } else {
                    $summary['bman_earning']['failed']++;
                }
            }
        } else {
            // Mark as not needed for this option
            if ($order['bman_earning_cron_status'] != 1) {
                $this->db->where('id', $order['id'])->update('staking_swap_orders', [
                    'bman_earning_cron_status' => 1,
                ]);
            }
        }

        // Step 6: BMAN to Staking (optional - options 4, 6, 7)
        if ($this->_shouldProcessStaking($option)) {
            if ($order['bman_staking_cron_status'] == 0) {
                if ($this->_detectAndDistributeBmanToStaking($order)) {
                    $summary['bman_staking']['processed']++;
                } else {
                    $summary['bman_staking']['failed']++;
                }
            }
        } else {
            // Mark as not needed for this option
            if ($order['bman_staking_cron_status'] != 1) {
                $this->db->where('id', $order['id'])->update('staking_swap_orders', [
                    'bman_staking_cron_status' => 1,
                ]);
            }
        }

        // Step 7: BMAN to Bonus Wallet (optional - options 2, 3, 7)
        if ($this->_shouldProcessBonusWallet($option)) {
            if ($order['bman_bonus_cron_status'] == 0) {
                if ($this->_detectAndDistributeBmanToBonus($order)) {
                    $summary['bman_bonus']['processed']++;
                } else {
                    $summary['bman_bonus']['failed']++;
                }
            }
        } else {
            // Mark as not needed for this option
            if ($order['bman_bonus_cron_status'] != 1) {
                $this->db->where('id', $order['id'])->update('staking_swap_orders', [
                    'bman_bonus_cron_status' => 1,
                ]);
            }
        }

        // Check if order is now complete
        $this->_checkAndCompleteOrder($order);
    }

    /**
     * Determine if this option uses earning wallet
     * Options 3, 5, 7 use earning
     */
    private function _shouldProcessEarning($option)
    {
        return in_array($option, [3, 5, 7]);
    }

    /**
     * Determine if this option uses staking wallet
     * Options 4, 6, 7 use staking
     */
    private function _shouldProcessStaking($option)
    {
        return in_array($option, [4, 6, 7]);
    }

    /**
     * Determine if this option uses bonus wallet
     * Options 2, 3, 7 use bonus
     */
    private function _shouldProcessBonusWallet($option)
    {
        return in_array($option, [2, 3, 7]);
    }

    /**
     * Step 1: Detect gas fee payment (0.0008 BNB to user)
     */
    private function _detectAndRecordGasFee(&$order)
    {
        $user_address = strtolower($order['user_address']);
        $config = $this->db->get_where('token_settings', ['status' => 1])->row_array();
        $api_key = $config['etherscan_api_key'] ?? '';
        $etherscan_url = $config['etherscan_url'] ?? 'https://api.bscscan.com';

        // Query Etherscan for BNB transfers TO user
        $url = $etherscan_url . '/api?module=account&action=txlist&address=' . $user_address
             . '&startblock=0&endblock=99999999&sort=desc&apikey=' . $api_key;

        try {
            $response = @file_get_contents($url);
            if (!$response) {
                $msg = 'Etherscan API no response for gas fee detection';
                $this->_recordFailureMessage($order['id'], 'gas', $msg);
                return false;
            }

            $data = json_decode($response, true);
            if (!is_array($data) || !isset($data['result']) || !is_array($data['result']) || empty($data['result'])) {
                $msg = 'No BNB transactions found on Etherscan for user address';
                $this->_recordFailureMessage($order['id'], 'gas', $msg);
                return false;
            }

            // Look for BNB transfer to user (0.0005 - 0.01 BNB)
            foreach ($data['result'] as $tx) {
                $to = strtolower($tx['to'] ?? '');
                $value = (float)hexdec($tx['value'] ?? 0) / 1e18;

                if ($to === $user_address && $value >= 0.0005 && $value <= 0.01) {
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

                    // Update order: set gas_tx_hash, gas_cron_status=1, clear message, status→pending_usdt
                    $this->db->where('id', $order['id'])->update('staking_swap_orders', [
                        'gas_tx_hash' => $tx_hash,
                        'gas_cron_status' => 1,
                        'gas_cron_status_message' => null,
                        'status' => 'pending_usdt',
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);

                    log_message('info', $this->log_prefix . ' Gas fee detected for order ' . $order['id'] . ': ' . $tx_hash);
                    return true;
                }
            }

            $msg = 'Gas fee TX (0.0005-0.01 BNB) not found on Etherscan yet';
            $this->_recordFailureMessage($order['id'], 'gas', $msg);
            return false;
        } catch (Exception $e) {
            $msg = 'Exception: ' . $e->getMessage();
            $this->_recordFailureMessage($order['id'], 'gas', $msg);
            return false;
        }
    }

    /**
     * Step 2: Detect USDT payment (100 USDT user → admin)
     */
    private function _detectAndRecordUsdtPayment(&$order)
    {
        $user_address = strtolower($order['user_address']);
        $admin_address = strtolower($order['admin_address']);
        $config = $this->db->get_where('token_settings', ['status' => 1])->row_array();
        $usdt_contract = strtolower($config['usdt_address'] ?? '');
        $api_key = $config['etherscan_api_key'] ?? '';
        $etherscan_url = $config['etherscan_url'] ?? 'https://api.bscscan.com';

        try {
            // Query Etherscan for USDT transfers FROM user TO admin
            $url = $etherscan_url . '/api?module=account&action=tokentx&contractaddress=' . $usdt_contract
                 . '&address=' . $user_address . '&startblock=0&endblock=99999999&sort=desc&apikey=' . $api_key;

            $response = @file_get_contents($url);
            if (!$response) {
                $msg = 'Etherscan API no response for USDT detection';
                $this->_recordFailureMessage($order['id'], 'usdt', $msg);
                return false;
            }

            $data = json_decode($response, true);
            if (!is_array($data) || !isset($data['result']) || !is_array($data['result']) || empty($data['result'])) {
                $msg = 'No USDT transfers found on Etherscan for user address';
                $this->_recordFailureMessage($order['id'], 'usdt', $msg);
                return false;
            }

            // Look for USDT transfer from user to admin
            foreach ($data['result'] as $tx) {
                $from = strtolower($tx['from'] ?? '');
                $to = strtolower($tx['to'] ?? '');
                $value = $tx['value'] ?? 0;

                if ($from === $user_address && $to === $admin_address) {
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

                    // Update order: set usdt_tx_hash, usdt_cron_status=1, clear message, status→pending_bman
                    $this->db->where('id', $order['id'])->update('staking_swap_orders', [
                        'usdt_tx_hash' => $tx_hash,
                        'usdt_cron_status' => 1,
                        'usdt_cron_status_message' => null,
                        'status' => 'pending_bman',
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);

                    log_message('info', $this->log_prefix . ' USDT payment detected for order ' . $order['id'] . ': ' . $tx_hash);
                    return true;
                }
            }

            $msg = 'USDT transfer from user to admin not found on Etherscan yet';
            $this->_recordFailureMessage($order['id'], 'usdt', $msg);
            return false;
        } catch (Exception $e) {
            $msg = 'Exception: ' . $e->getMessage();
            $this->_recordFailureMessage($order['id'], 'usdt', $msg);
            return false;
        }
    }

    /**
     * Step 3: Detect bonus BMAN (if configured)
     */
    private function _detectAndRecordBonusBman(&$order)
    {
        $user_address = strtolower($order['user_address']);
        $admin_address = strtolower($order['admin_address']);
        $config = $this->db->get_where('token_settings', ['status' => 1])->row_array();
        $bman_contract = strtolower($config['bman_address'] ?? '');
        $api_key = $config['etherscan_api_key'] ?? '';
        $etherscan_url = $config['etherscan_url'] ?? 'https://api.bscscan.com';

        $bonus_amount = (float)($order['bonus_bman'] ?? 0);
        if ($bonus_amount == 0) {
            // No bonus configured, mark as complete
            if ($order['bonus_cron_status'] != 1) {
                $this->db->where('id', $order['id'])->update('staking_swap_orders', [
                    'bonus_cron_status' => 1,
                    'bonus_cron_status_message' => null,
                ]);
            }
            return true;
        }

        try {
            // Query Etherscan for BMAN transfers FROM admin TO user
            $url = $etherscan_url . '/api?module=account&action=tokentx&contractaddress=' . $bman_contract
                 . '&address=' . $admin_address . '&startblock=0&endblock=99999999&sort=desc&apikey=' . $api_key;

            $response = @file_get_contents($url);
            if (!$response) {
                $msg = 'Etherscan API no response for bonus BMAN detection';
                $this->_recordFailureMessage($order['id'], 'bonus', $msg);
                return false;
            }

            $data = json_decode($response, true);
            if (!is_array($data) || !isset($data['result']) || !is_array($data['result']) || empty($data['result'])) {
                $msg = 'No BMAN transfers found on Etherscan for admin address';
                $this->_recordFailureMessage($order['id'], 'bonus', $msg);
                return false;
            }

            // Look for bonus BMAN transfer from admin to user
            foreach ($data['result'] as $tx) {
                $from = strtolower($tx['from'] ?? '');
                $to = strtolower($tx['to'] ?? '');

                if ($from === $admin_address && $to === $user_address) {
                    $tx_hash = strtolower($tx['hash'] ?? '');

                    // Record in onchain_transactions
                    $this->db->insert('onchain_transactions', [
                        'tx_hash' => $tx_hash,
                        'from_address' => $from,
                        'to_address' => $to,
                        'amount' => $tx['value'] ?? 0,
                        'tx_type' => 'transfer',
                        'status' => 'processing',
                        'block_number' => $tx['blockNumber'] ?? 0,
                        'user_id' => $order['user_id'],
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);

                    // Update order: set bonus_tx_hash, bonus_cron_status=1, clear message
                    $this->db->where('id', $order['id'])->update('staking_swap_orders', [
                        'bonus_tx_hash' => $tx_hash,
                        'bonus_cron_status' => 1,
                        'bonus_cron_status_message' => null,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);

                    log_message('info', $this->log_prefix . ' Bonus BMAN detected for order ' . $order['id'] . ': ' . $tx_hash);
                    return true;
                }
            }

            $msg = 'Bonus BMAN transfer from admin to user not found on Etherscan yet';
            $this->_recordFailureMessage($order['id'], 'bonus', $msg);
            return false;
        } catch (Exception $e) {
            $msg = 'Exception: ' . $e->getMessage();
            $this->_recordFailureMessage($order['id'], 'bonus', $msg);
            return false;
        }
    }

    /**
     * Step 4: Detect BMAN to Exchange Wallet (always required in all options)
     */
    private function _detectAndDistributeBmanToExchange(&$order)
    {
        $user_address = strtolower($order['user_address']);
        $admin_address = strtolower($order['admin_address']);
        $config = $this->db->get_where('token_settings', ['status' => 1])->row_array();
        $bman_contract = strtolower($config['bman_address'] ?? '');
        $api_key = $config['etherscan_api_key'] ?? '';
        $etherscan_url = $config['etherscan_url'] ?? 'https://api.bscscan.com';

        try {
            // Query Etherscan for BMAN transfers FROM admin TO user
            $url = $etherscan_url . '/api?module=account&action=tokentx&contractaddress=' . $bman_contract
                 . '&address=' . $admin_address . '&startblock=0&endblock=99999999&sort=desc&apikey=' . $api_key;

            $response = @file_get_contents($url);
            if (!$response) {
                $msg = 'Etherscan API no response for exchange wallet BMAN detection';
                $this->_recordFailureMessage($order['id'], 'bman_exchange', $msg);
                return false;
            }

            $data = json_decode($response, true);
            if (!is_array($data) || !isset($data['result']) || !is_array($data['result']) || empty($data['result'])) {
                $msg = 'No BMAN transfers found on Etherscan for admin address';
                $this->_recordFailureMessage($order['id'], 'bman_exchange', $msg);
                return false;
            }

            // Look for BMAN transfer from admin to user
            foreach ($data['result'] as $tx) {
                $from = strtolower($tx['from'] ?? '');
                $to = strtolower($tx['to'] ?? '');
                $value = $tx['value'] ?? 0;

                if ($from === $admin_address && $to === $user_address) {
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

                    // Update order: set exchange TX hash, bman_exchange_cron_status=1, clear message
                    $this->db->where('id', $order['id'])->update('staking_swap_orders', [
                        'bman_exchange_tx_hash' => $tx_hash,
                        'bman_exchange_cron_status' => 1,
                        'bman_exchange_cron_status_message' => null,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);

                    // Distribute to exchange wallet (% based on option)
                    $this->_updateWalletLedger($order, 'exchange');

                    log_message('info', $this->log_prefix . ' BMAN to exchange wallet detected for order ' . $order['id'] . ': ' . $tx_hash);
                    return true;
                }
            }

            $msg = 'BMAN transfer from admin to user not found on Etherscan yet';
            $this->_recordFailureMessage($order['id'], 'bman_exchange', $msg);
            return false;
        } catch (Exception $e) {
            $msg = 'Exception: ' . $e->getMessage();
            $this->_recordFailureMessage($order['id'], 'bman_exchange', $msg);
            return false;
        }
    }

    /**
     * Step 5: Detect BMAN to Earning Wallet (optional - options 3, 5, 7)
     */
    private function _detectAndDistributeBmanToEarning(&$order)
    {
        try {
            // Same TX hash as exchange (one bulk transfer), just different ledger entry
            $earning_bman = $this->_calculateBmanForWallet($order, 'earning');
            if ($earning_bman == 0) {
                return true; // Not used in this option
            }

            // For this option, earning wallet gets its slice
            $this->db->where('id', $order['id'])->update('staking_swap_orders', [
                'bman_earning_tx_hash' => $this->_getExchangeTxHash($order),
                'bman_earning_cron_status' => 1,
                'bman_earning_cron_status_message' => null,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $this->_updateWalletLedger($order, 'earning');

            log_message('info', $this->log_prefix . ' BMAN to earning wallet distributed for order ' . $order['id']);
            return true;
        } catch (Exception $e) {
            $msg = 'Exception: ' . $e->getMessage();
            $this->_recordFailureMessage($order['id'], 'bman_earning', $msg);
            return false;
        }
    }

    /**
     * Step 6: Detect BMAN to Staking Wallet (optional - options 4, 6, 7)
     */
    private function _detectAndDistributeBmanToStaking(&$order)
    {
        try {
            // Same TX hash as exchange, just different ledger entry
            $staking_bman = $this->_calculateBmanForWallet($order, 'staking');
            if ($staking_bman == 0) {
                return true; // Not used in this option
            }

            // For this option, staking wallet gets its slice
            $this->db->where('id', $order['id'])->update('staking_swap_orders', [
                'bman_staking_tx_hash' => $this->_getExchangeTxHash($order),
                'bman_staking_cron_status' => 1,
                'bman_staking_cron_status_message' => null,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $this->_updateWalletLedger($order, 'staking');

            log_message('info', $this->log_prefix . ' BMAN to staking wallet distributed for order ' . $order['id']);
            return true;
        } catch (Exception $e) {
            $msg = 'Exception: ' . $e->getMessage();
            $this->_recordFailureMessage($order['id'], 'bman_staking', $msg);
            return false;
        }
    }

    /**
     * Step 7: Detect BMAN to Bonus Wallet (optional - options 2, 3, 7)
     */
    private function _detectAndDistributeBmanToBonus(&$order)
    {
        try {
            // Same TX hash as exchange, just different ledger entry
            $bonus_bman_wallet = $this->_calculateBmanForWallet($order, 'bonus');
            if ($bonus_bman_wallet == 0) {
                return true; // Not used in this option
            }

            // For this option, bonus wallet gets its slice
            $this->db->where('id', $order['id'])->update('staking_swap_orders', [
                'bman_bonus_tx_hash' => $this->_getExchangeTxHash($order),
                'bman_bonus_cron_status' => 1,
                'bman_bonus_cron_status_message' => null,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $this->_updateWalletLedger($order, 'bonus');

            log_message('info', $this->log_prefix . ' BMAN to bonus wallet distributed for order ' . $order['id']);
            return true;
        } catch (Exception $e) {
            $msg = 'Exception: ' . $e->getMessage();
            $this->_recordFailureMessage($order['id'], 'bman_bonus', $msg);
            return false;
        }
    }

    /**
     * Get the exchange TX hash (all wallets get their portion from same TX)
     */
    private function _getExchangeTxHash(&$order)
    {
        $row = $this->db->select('bman_exchange_tx_hash')
            ->where('id', $order['id'])
            ->get('staking_swap_orders')
            ->row_array();
        return $row['bman_exchange_tx_hash'] ?? '';
    }

    /**
     * Calculate BMAN amount for a specific wallet based on option
     */
    private function _calculateBmanForWallet(&$order, $wallet)
    {
        $option = (int)($order['coin_distribution_option'] ?? 1);
        $bman_amount = (float)$order['bman_amount'];

        $percentages = [
            1 => ['exchange' => 100, 'earning' => 0,  'staking' => 0,  'bonus' => 0],
            2 => ['exchange' => 90,  'earning' => 0,  'staking' => 0,  'bonus' => 10],
            3 => ['exchange' => 80,  'earning' => 10, 'staking' => 0,  'bonus' => 10],
            4 => ['exchange' => 80,  'earning' => 10, 'staking' => 10, 'bonus' => 0],
            5 => ['exchange' => 90,  'earning' => 10, 'staking' => 0,  'bonus' => 0],
            6 => ['exchange' => 90,  'earning' => 0,  'staking' => 10, 'bonus' => 0],
            7 => ['exchange' => 70,  'earning' => 10, 'staking' => 10, 'bonus' => 10],
        ];

        $dist = $percentages[$option] ?? $percentages[1];
        $percent = $dist[$wallet] ?? 0;
        return $bman_amount * ($percent / 100);
    }

    /**
     * Update wallet_ledger with BMAN distribution
     */
    private function _updateWalletLedger(&$order, $wallet_type)
    {
        $amount = $this->_calculateBmanForWallet($order, $wallet_type);
        if ($amount <= 0) {
            return;
        }

        // Map wallet type to ledger column
        $column = $wallet_type . '_wallet'; // exchange_wallet, earning_wallet, staking_wallet, bonus_wallet

        $this->db->where('user_id', $order['user_id'])
            ->set($column, "{$column} + {$amount}", false)
            ->update('wallet_ledger');

        log_message('info', $this->log_prefix . ' Updated ' . $wallet_type . ' wallet with ' . $amount . ' BMAN for user ' . $order['user_id']);
    }

    /**
     * Record failure message for a cron step
     * Stores error details in cron_status_message column for debugging
     */
    private function _recordFailureMessage($order_id, $step, $message)
    {
        // Map step name to message column
        $column = $step . '_cron_status_message';

        // Truncate message if too long (TEXT max is usually safe, but be cautious)
        $message = substr($message, 0, 500);

        $this->db->where('id', $order_id)->update('staking_swap_orders', [
            $column => $message,
        ]);

        log_message('error', $this->log_prefix . ' Step "' . $step . '" failure for order ' . $order_id . ': ' . $message);
    }

    /**
     * Check if order is complete and finalize it
     */
    private function _checkAndCompleteOrder(&$order)
    {
        $option = (int)($order['coin_distribution_option'] ?? 1);

        // Check all required steps
        $all_complete =
            $order['gas_cron_status'] == 1 &&
            $order['usdt_cron_status'] == 1 &&
            $order['bonus_cron_status'] == 1 &&
            $order['bman_exchange_cron_status'] == 1;

        // Check optional steps based on option
        if ($this->_shouldProcessEarning($option)) {
            $all_complete = $all_complete && ($order['bman_earning_cron_status'] == 1);
        }
        if ($this->_shouldProcessStaking($option)) {
            $all_complete = $all_complete && ($order['bman_staking_cron_status'] == 1);
        }
        if ($this->_shouldProcessBonusWallet($option)) {
            $all_complete = $all_complete && ($order['bman_bonus_cron_status'] == 1);
        }

        if ($all_complete) {
            // Create user_stakes record
            $this->db->insert('user_stakes', [
                'user_id' => $order['user_id'],
                'package_id' => $order['package_id'] ?? 0,
                'bman_amount' => (float)$order['bman_amount'],
                'bonus_bman' => (float)($order['bonus_bman'] ?? 0),
                'status' => 'active',
                'activated_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            // Update order status to swap_completed
            $this->db->where('id', $order['id'])->update('staking_swap_orders', [
                'status' => 'swap_completed',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            log_message('info', $this->log_prefix . ' Order ' . $order['id'] . ' completed successfully. User stakes created.');
        }
    }
}
