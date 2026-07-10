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
    private $_cfg_cache = null;

    /* =====================================================================
     * Chain/explorer helpers — mirror the proven Depositlistener_model so the
     * SAME token_settings columns + Etherscan-V2 (multichain) format are used.
     * The old code invented columns (etherscan_api_key/url, usdt_address,
     * bman_address) and omitted chainid, so every lookup returned empty.
     * ===================================================================== */

    /** Active chain/token settings (cached for the run). */
    private function _cfg()
    {
        if ($this->_cfg_cache === null) {
            $this->_cfg_cache = $this->db->get_where('token_settings', ['status' => 1])->row_array() ?: [];
        }
        return $this->_cfg_cache;
    }

    /** Build an Etherscan-V2 explorer API URL (multichain → needs chainid). */
    private function _apiUrl(array $params)
    {
        $cfg = $this->_cfg();
        $api = trim((string)($cfg['explorer_api_url'] ?? '')) ?: 'https://api.etherscan.io/v2/api';
        $params = array_merge([
            'chainid' => (int)($cfg['chain_id'] ?? 56),
            'apikey'  => trim((string)($cfg['explorer_api_key'] ?? '')),
        ], $params);
        return $api . '?' . http_build_query($params);
    }

    /** GET JSON from the explorer API via cURL. Returns decoded array or null. */
    private function _apiGet(array $params, $timeout = 25)
    {
        $ch = curl_init($this->_apiUrl($params));
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => $timeout]);
        $raw = curl_exec($ch);
        curl_close($ch);
        return json_decode((string)$raw, true);
    }

    /** Current chain head — RPC first (fast/reliable), else explorer proxy. 0 on failure. */
    private function _currentBlock()
    {
        $cfg = $this->_cfg();
        $rpc = trim((string)($cfg['rpc_url'] ?? ''));
        if ($rpc !== '') {
            $payload = json_encode(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'eth_blockNumber', 'params' => []]);
            $ch = curl_init($rpc);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_POSTFIELDS => $payload,
            ]);
            $raw = curl_exec($ch);
            curl_close($ch);
            $j = json_decode((string)$raw, true);
            if (isset($j['result'])) return (int)hexdec((string)$j['result']);
        }
        $j = $this->_apiGet(['module' => 'proxy', 'action' => 'eth_blockNumber']);
        if (isset($j['result'])) return (int)hexdec((string)$j['result']);
        return 0;
    }

    /** Confirmations required before crediting (token_settings.minimum_confirmations, default 12). */
    private function _minConfirmations()
    {
        $n = (int)($this->_cfg()['minimum_confirmations'] ?? 0);
        return $n > 0 ? $n : 12;
    }

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
                'id, user_id, user_address, admin_address, package_id, status, ' .
                'usdt_amount, bman_amount, bonus_bman, coin_distribution_option, ' .
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
     * Step 1: Detect gas fee payment (calculated per-user based on purchase)
     * ONLY credits after 12 block confirmations
     */
    private function _detectAndRecordGasFee(&$order)
    {
        // FIRST: Validate coin_distribution_option_id is valid
        $option = (int)($order['coin_distribution_option'] ?? 0);
        if ($option < 1 || $option > 7) {
            $msg = 'Invalid coin_distribution_option_id: ' . $option . ' (must be 1-7)';
            $this->_recordFailureMessage($order['id'], 'gas', $msg);
            return false;
        }

        $user_address = strtolower($order['user_address']);
        $required_confirmations = $this->_minConfirmations();

        // Expected gas fee for this user's purchase (reference/record only — NOT a
        // strict match gate; the admin's actual BNB top-up varies with gas price).
        $expected_gas_bnb = $this->_calculateGasFeForUser($order);

        // Accept any sane BNB gas credit to the user (covers 0.0005–0.02 BNB).
        $min_bnb = 0.0001;
        $max_bnb = 0.05;

        // Current chain head for confirmation math
        $current_block = $this->_currentBlock();
        if ($current_block == 0) {
            $msg = 'Cannot read current block height (RPC + explorer both failed)';
            $this->_recordFailureMessage($order['id'], 'gas', $msg);
            return false;
        }

        try {
            // BNB (native) transfers TO the user address
            $data = $this->_apiGet([
                'module' => 'account', 'action' => 'txlist', 'address' => $user_address,
                'startblock' => 0, 'endblock' => 99999999, 'page' => 1, 'offset' => 50, 'sort' => 'desc',
            ]);

            if (!is_array($data) || !isset($data['result']) || !is_array($data['result']) || empty($data['result'])) {
                $msg = 'No BNB transactions found on explorer for user address yet';
                $this->_recordFailureMessage($order['id'], 'gas', $msg);
                return false;
            }

            foreach ($data['result'] as $tx) {
                $to = strtolower($tx['to'] ?? '');
                // Native BNB value is decimal wei on the txlist endpoint
                $value = (float)$tx['value'] / 1e18;
                $tx_block = (int)($tx['blockNumber'] ?? 0);
                $tx_hash = strtolower($tx['hash'] ?? '');
                $is_error = ($tx['isError'] ?? '0') === '1';

                if (!$is_error && $to === $user_address && $value >= $min_bnb && $value <= $max_bnb) {
                    $confirmations = $current_block - $tx_block;
                    if ($confirmations < $required_confirmations) {
                        $msg = "Gas fee TX pending confirmations: $confirmations/$required_confirmations";
                        $this->_recordFailureMessage($order['id'], 'gas', $msg);
                        return false;
                    }

                    $this->db->insert('onchain_transactions', [
                        'tx_hash' => $tx_hash,
                        'from_address' => strtolower($tx['from'] ?? ''),
                        'to_address' => $to,
                        'amount' => $tx['value'],
                        'tx_type' => 'gas_fee',
                        'status' => 'completed',
                        'block_number' => $tx_block,
                        'confirmations' => $confirmations,
                        'user_id' => $order['user_id'],
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);

                    // Gas received → advance to pending_usdt (update in-memory too so the
                    // same cron run can progress USDT/BMAN steps for this order).
                    $this->db->where('id', $order['id'])->update('staking_swap_orders', [
                        'gas_tx_hash' => $tx_hash,
                        'gas_cron_status' => 1,
                        'gas_cron_status_message' => null,
                        'status' => 'pending_usdt',
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $order['gas_cron_status'] = 1;
                    $order['status'] = 'pending_usdt';

                    log_message('info', $this->log_prefix . ' Gas CONFIRMED order ' . $order['id']
                        . ' (' . $confirmations . ' conf, ' . $value . ' BNB, expected ~' . sprintf('%.6f', $expected_gas_bnb) . '): ' . $tx_hash);
                    return true;
                }
            }

            $msg = 'Gas BNB credit (0.0001–0.05 BNB) to user not found on explorer yet';
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
        // Gas must be confirmed first (user needs BNB to pay the USDT transfer gas).
        if ((int)$order['gas_cron_status'] !== 1) {
            $this->_recordFailureMessage($order['id'], 'usdt', 'Waiting for gas fee step to complete first');
            return false;
        }

        $user_address = strtolower($order['user_address']);
        $admin_address = strtolower($order['admin_address']);
        $cfg = $this->_cfg();
        $usdt_contract = trim((string)($cfg['usdt_contract'] ?? ''));
        $usdt_decimals = (int)($cfg['usdt_decimals'] ?? 18);
        $required_confirmations = $this->_minConfirmations();
        $expected_usdt = (float)($order['usdt_amount'] ?? 0);

        if ($usdt_contract === '') {
            $this->_recordFailureMessage($order['id'], 'usdt', 'USDT contract not configured in token_settings');
            return false;
        }

        $current_block = $this->_currentBlock();
        if ($current_block == 0) {
            $this->_recordFailureMessage($order['id'], 'usdt', 'Cannot read current block height');
            return false;
        }

        try {
            // USDT (BEP-20) transfers involving the user address
            $data = $this->_apiGet([
                'module' => 'account', 'action' => 'tokentx', 'contractaddress' => $usdt_contract,
                'address' => $user_address, 'startblock' => 0, 'endblock' => 99999999,
                'page' => 1, 'offset' => 50, 'sort' => 'desc',
            ]);

            if (!is_array($data) || !isset($data['result']) || !is_array($data['result']) || empty($data['result'])) {
                $this->_recordFailureMessage($order['id'], 'usdt', 'No USDT transfers found on explorer for user address yet');
                return false;
            }

            foreach ($data['result'] as $tx) {
                $from = strtolower($tx['from'] ?? '');
                $to = strtolower($tx['to'] ?? '');
                $raw = (string)($tx['value'] ?? '0');
                $amount = (float)bcdiv($raw, bcpow('10', (string)$usdt_decimals, 0), 8);
                $tx_block = (int)($tx['blockNumber'] ?? 0);
                $tx_hash = strtolower($tx['hash'] ?? '');

                // Must be user → admin, amount at least the order's USDT cost (1% tolerance).
                if ($from === $user_address && $to === $admin_address && $amount + 1e-8 >= $expected_usdt * 0.99) {
                    $confirmations = $current_block - $tx_block;
                    if ($confirmations < $required_confirmations) {
                        $this->_recordFailureMessage($order['id'], 'usdt', "USDT TX pending confirmations: $confirmations/$required_confirmations");
                        return false;
                    }

                    $this->db->insert('onchain_transactions', [
                        'tx_hash' => $tx_hash,
                        'from_address' => $from,
                        'to_address' => $to,
                        'amount' => $raw,
                        'tx_type' => 'deposit',
                        'status' => 'completed',
                        'block_number' => $tx_block,
                        'confirmations' => $confirmations,
                        'user_id' => $order['user_id'],
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);

                    // USDT received by admin → advance to pending_bman (in-memory too).
                    $this->db->where('id', $order['id'])->update('staking_swap_orders', [
                        'usdt_tx_hash' => $tx_hash,
                        'usdt_cron_status' => 1,
                        'usdt_cron_status_message' => null,
                        'status' => 'pending_bman',
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $order['usdt_cron_status'] = 1;
                    $order['status'] = 'pending_bman';

                    log_message('info', $this->log_prefix . ' USDT CONFIRMED order ' . $order['id']
                        . ' (' . $confirmations . ' conf, ' . $amount . ' USDT ≥ ' . $expected_usdt . '): ' . $tx_hash);
                    return true;
                }
            }

            $this->_recordFailureMessage($order['id'], 'usdt', 'USDT transfer user → admin (≥' . $expected_usdt . ') not found on explorer yet');
            return false;
        } catch (Exception $e) {
            $this->_recordFailureMessage($order['id'], 'usdt', 'Exception: ' . $e->getMessage());
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
        $cfg = $this->_cfg();
        $bman_contract = trim((string)($cfg['bman_contract'] ?? ''));
        $bman_decimals = (int)($cfg['bman_decimals'] ?? 18);
        $required_confirmations = $this->_minConfirmations();

        $bonus_amount = (float)($order['bonus_bman'] ?? 0);
        if ($bonus_amount == 0) {
            // No bonus configured, mark as complete
            if ($order['bonus_cron_status'] != 1) {
                $this->db->where('id', $order['id'])->update('staking_swap_orders', [
                    'bonus_cron_status' => 1,
                    'bonus_cron_status_message' => null,
                ]);
                $order['bonus_cron_status'] = 1;
            }
            return true;
        }

        if ($bman_contract === '') {
            $this->_recordFailureMessage($order['id'], 'bonus', 'BMAN contract not configured in token_settings');
            return false;
        }

        $current_block = $this->_currentBlock();
        if ($current_block == 0) {
            $this->_recordFailureMessage($order['id'], 'bonus', 'Cannot read current block height');
            return false;
        }

        try {
            // BMAN (BEP-20) transfers involving the admin address
            $data = $this->_apiGet([
                'module' => 'account', 'action' => 'tokentx', 'contractaddress' => $bman_contract,
                'address' => $admin_address, 'startblock' => 0, 'endblock' => 99999999,
                'page' => 1, 'offset' => 100, 'sort' => 'desc',
            ]);

            if (!is_array($data) || !isset($data['result']) || !is_array($data['result']) || empty($data['result'])) {
                $this->_recordFailureMessage($order['id'], 'bonus', 'No BMAN transfers found on explorer for admin address yet');
                return false;
            }

            // Bonus BMAN transfer admin → user, amount ≈ bonus_bman (1% tolerance)
            foreach ($data['result'] as $tx) {
                $from = strtolower($tx['from'] ?? '');
                $to = strtolower($tx['to'] ?? '');
                $raw = (string)($tx['value'] ?? '0');
                $amount = (float)bcdiv($raw, bcpow('10', (string)$bman_decimals, 0), 8);
                $tx_block = (int)($tx['blockNumber'] ?? 0);

                if ($from === $admin_address && $to === $user_address && $amount + 1e-8 >= $bonus_amount * 0.99) {
                    $confirmations = $current_block - $tx_block;
                    if ($confirmations < $required_confirmations) {
                        $this->_recordFailureMessage($order['id'], 'bonus', "Bonus BMAN TX pending confirmations: $confirmations/$required_confirmations");
                        return false;
                    }

                    $tx_hash = strtolower($tx['hash'] ?? '');
                    $this->db->insert('onchain_transactions', [
                        'tx_hash' => $tx_hash,
                        'from_address' => $from,
                        'to_address' => $to,
                        'amount' => $raw,
                        'tx_type' => 'transfer',
                        'status' => 'completed',
                        'block_number' => $tx_block,
                        'confirmations' => $confirmations,
                        'user_id' => $order['user_id'],
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);

                    $this->db->where('id', $order['id'])->update('staking_swap_orders', [
                        'bonus_tx_hash' => $tx_hash,
                        'bonus_cron_status' => 1,
                        'bonus_cron_status_message' => null,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $order['bonus_cron_status'] = 1;

                    log_message('info', $this->log_prefix . ' Bonus BMAN CONFIRMED order ' . $order['id']
                        . ' (' . $confirmations . ' conf, ' . $amount . ' BMAN): ' . $tx_hash);
                    return true;
                }
            }

            $this->_recordFailureMessage($order['id'], 'bonus', 'Bonus BMAN transfer admin → user (≈' . $bonus_amount . ') not found on explorer yet');
            return false;
        } catch (Exception $e) {
            $this->_recordFailureMessage($order['id'], 'bonus', 'Exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Step 4: Detect BMAN to Exchange Wallet (always required in all options)
     * ONLY credits wallet after on-chain confirmation with sufficient block confirmations
     */
    private function _detectAndDistributeBmanToExchange(&$order)
    {
        // USDT must be confirmed first — we only release BMAN after the buyer paid.
        if ((int)$order['usdt_cron_status'] !== 1) {
            $this->_recordFailureMessage($order['id'], 'bman_exchange', 'Waiting for USDT payment step to complete first');
            return false;
        }

        $user_address = strtolower($order['user_address']);
        $admin_address = strtolower($order['admin_address']);
        $cfg = $this->_cfg();
        $bman_contract = trim((string)($cfg['bman_contract'] ?? ''));
        $bman_decimals = (int)($cfg['bman_decimals'] ?? 18);
        $required_confirmations = $this->_minConfirmations();
        $principal_bman = (float)$order['bman_amount'];
        $bonus_bman = (float)($order['bonus_bman'] ?? 0);

        if ($bman_contract === '') {
            $this->_recordFailureMessage($order['id'], 'bman_exchange', 'BMAN contract not configured in token_settings');
            return false;
        }

        $current_block = $this->_currentBlock();
        if ($current_block == 0) {
            $this->_recordFailureMessage($order['id'], 'bman_exchange', 'Cannot read current block height');
            return false;
        }

        try {
            // BMAN (BEP-20) transfers involving the admin address
            $data = $this->_apiGet([
                'module' => 'account', 'action' => 'tokentx', 'contractaddress' => $bman_contract,
                'address' => $admin_address, 'startblock' => 0, 'endblock' => 99999999,
                'page' => 1, 'offset' => 100, 'sort' => 'desc',
            ]);

            if (!is_array($data) || !isset($data['result']) || !is_array($data['result']) || empty($data['result'])) {
                $this->_recordFailureMessage($order['id'], 'bman_exchange', 'No BMAN transfers found on explorer for admin address yet');
                return false;
            }

            // Match the PRINCIPAL BMAN transfer (admin → user, amount ≈ bman_amount).
            // Amount matching separates it from the smaller bonus transfer (bonus_bman).
            $bonus_tx = strtolower((string)($order['bonus_tx_hash'] ?? ''));
            foreach ($data['result'] as $tx) {
                $from = strtolower($tx['from'] ?? '');
                $to = strtolower($tx['to'] ?? '');
                $raw = (string)($tx['value'] ?? '0');
                $amount = (float)bcdiv($raw, bcpow('10', (string)$bman_decimals, 0), 8);
                $tx_block = (int)($tx['blockNumber'] ?? 0);
                $tx_hash = strtolower($tx['hash'] ?? '');

                // skip the bonus transfer explicitly; require amount ≈ principal (1% tol)
                $is_principal = ($amount + 1e-8 >= $principal_bman * 0.99) && ($amount <= $principal_bman * 1.01 + 1e-8);
                if ($tx_hash === $bonus_tx) continue;

                if ($from === $admin_address && $to === $user_address && $is_principal) {
                    $confirmations = $current_block - $tx_block;
                    if ($confirmations < $required_confirmations) {
                        $this->_recordFailureMessage($order['id'], 'bman_exchange', "BMAN TX pending confirmations: $confirmations/$required_confirmations");
                        return false;
                    }

                    $this->db->insert('onchain_transactions', [
                        'tx_hash' => $tx_hash,
                        'from_address' => $from,
                        'to_address' => $to,
                        'amount' => $raw,
                        'tx_type' => 'transfer',
                        'status' => 'completed',
                        'block_number' => $tx_block,
                        'confirmations' => $confirmations,
                        'user_id' => $order['user_id'],
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);

                    // Record the on-chain hash FIRST (so _getExchangeTxHash / earning /
                    // staking / bonus slices all reference it), then credit exchange slice.
                    $this->db->where('id', $order['id'])->update('staking_swap_orders', [
                        'bman_exchange_tx_hash' => $tx_hash,
                        'bman_exchange_cron_status' => 1,
                        'bman_exchange_cron_status_message' => null,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                    $order['bman_exchange_cron_status'] = 1;

                    $this->_updateWalletLedger($order, 'exchange');

                    log_message('info', $this->log_prefix . ' Principal BMAN CONFIRMED order ' . $order['id']
                        . ' (' . $confirmations . ' conf, ' . $amount . ' BMAN): ' . $tx_hash);
                    return true;
                }
            }

            $this->_recordFailureMessage($order['id'], 'bman_exchange', 'Principal BMAN transfer admin → user (≈' . $principal_bman . ') not found on explorer yet');
            return false;
        } catch (Exception $e) {
            $this->_recordFailureMessage($order['id'], 'bman_exchange', 'Exception: ' . $e->getMessage());
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
     * Calculate gas fee for this user's specific purchase
     * Gas fee = base fee + (percentage of USDT amount)
     */
    private function _calculateGasFeForUser(&$order)
    {
        $base_gas_fee = 0.0005; // Base gas fee in BNB
        $usdt_to_bnb_rate = 0.00025; // ~0.25 BNB per 1000 USDT (configurable)

        // USDT cost of this specific order (now selected in _getPendingOrders)
        $usdt_cost = (float)($order['usdt_amount'] ?? 0);

        // Calculate additional gas based on transaction size
        $additional_gas = ($usdt_cost / 1000) * $usdt_to_bnb_rate;

        // Total: base + percentage
        $total_gas = $base_gas_fee + $additional_gas;

        // Cap between 0.0005 and 0.01 BNB
        return max(0.0005, min(0.01, $total_gas));
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
     * Credit a user's BMAN distribution slice through the canonical ledger.
     *
     * Balances live in `user_wallets` (exchange_balance/earning_balance/…), and the
     * ONLY safe writer is Walletledger_model::credit(), which updates the balance +
     * appends a wallet_ledger journal row + row-locks + enforces a UNIQUE(tx_hash,
     * wallet_type) idempotency guard. We back each credit with the on-chain BMAN
     * tx_hash so re-running the cron never double-credits.
     */
    private function _updateWalletLedger(&$order, $wallet_type)
    {
        $amount = $this->_calculateBmanForWallet($order, $wallet_type);
        if ($amount <= 0) {
            return;
        }

        $this->load->model('Walletledger_model', 'L');

        // on-chain BMAN transfer hash backing this credit (idempotency key)
        $tx_hash = $this->_getExchangeTxHash($order);

        list($ok, $info) = $this->L->credit(
            (int)$order['user_id'],
            $wallet_type, // exchange | earning | staking | bonus → mapped to *_balance
            $amount,
            'stake_purchase',
            [
                'tx_hash'      => $tx_hash ?: null,
                'reference_id' => 'ORDER-' . $order['id'],
                'description'  => ucfirst($wallet_type) . ' allocation ' . $amount . ' BMAN (order ' . $order['id'] . ')',
            ]
        );

        if (!$ok) {
            log_message('error', $this->log_prefix . ' Ledger credit FAILED for ' . $wallet_type . ' user ' . $order['user_id'] . ': ' . $info);
        } else {
            log_message('info', $this->log_prefix . ' Credited ' . $wallet_type . ' (+' . $amount . ' BMAN) user ' . $order['user_id'] . ' [' . $info . ']');
        }
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
