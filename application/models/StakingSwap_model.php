<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class StakingSwap_model extends CI_Model
{
    private $admin_wallet;
    private $usdt_contract;
    private $bman_contract;
    private $rpc_url;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Custodialwallet_model', 'wallet');
        $this->load->model('Tokenmaster_model', 'tokens');

        // Config
        $config = $this->db->get_where('token_settings', ['status'=>1])->row_array();
        $this->admin_wallet = $config['contract_wallet'] ?? '';
        $this->usdt_contract = $config['usdt_address'] ?? '';
        $this->bman_contract = $config['bman_address'] ?? '';
        $this->rpc_url = $config['rpc_endpoint'] ?? '';
    }

    /**
     * Create a new staking swap order
     * status: 'pending_gas_fee' → waiting for admin to send gas
     *
     * $package_data should include:
     *   - usdt_cost: USDT amount needed
     *   - bman_amount: BMAN to receive
     *   - bonus_bman: Bonus BMAN (optional)
     *   - plan_code: 'fixed', 'variable', etc.
     *   - plan_id: staking plan ID
     *   - duration_years: duration in years (2, 3, 5, etc.)
     *   - coin_distribution_option_id: wallet type (exchange=1, staking=2, earning=3, bonus=4)
     */
    public function createSwapOrder($user_id, $package_id, $package_data)
    {
        $user = $this->db->get_where('users', ['id'=>$user_id])->row_array();
        if (!$user || !$user['user_wallet_public_key']) {
            return ['status'=>false, 'message'=>'User wallet not found'];
        }

        $user_address = strtolower($user['user_wallet_public_key']);
        $admin_address = strtolower($this->admin_wallet);

        // Calculate USDT cost and BMAN amount
        $usdt_amount = (float)$package_data['usdt_cost'];
        $bman_amount = (float)$package_data['bman_amount'];
        $bonus_bman = (float)($package_data['bonus_bman'] ?? 0);

        // Plan details
        $plan_code = $package_data['plan_code'] ?? 'fixed';
        $plan_id = (int)($package_data['plan_id'] ?? 0);
        $duration_years = (int)($package_data['duration_years'] ?? 1);
        $coin_dist_option_id = (int)($package_data['coin_distribution_option_id'] ?? 1); // 1=exchange, 2=staking, 3=earning, 4=bonus

        // Estimate gas fee (in BNB)
        $gas_fee = $this->estimateGasFee();

        // Generate unique order reference
        $order_ref = 'SSO-' . date('YmdHis') . '-' . $user_id;

        // Create swap order
        $order_data = [
            'ref' => $order_ref,
            'user_id' => $user_id,
            'package_id' => $package_id,
            'user_address' => $user_address,
            'admin_address' => $admin_address,
            'usdt_amount' => $usdt_amount,
            'bman_amount' => $bman_amount,
            'bonus_bman' => $bonus_bman,
            'exchange_rate' => $bman_amount / ($usdt_amount ?: 1),
            'plan_code' => $plan_code,
            'plan_id' => $plan_id,
            'duration_years' => $duration_years,
            'coin_distribution_option' => $coin_dist_option_id,
            'status' => 'pending_gas_fee',
            'cron_status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $this->db->insert('staking_swap_orders', $order_data);
        $order_id = $this->db->insert_id();

        return [
            'status' => true,
            'order_id' => $order_id,
            'order_ref' => $order_ref,
            'user_address' => $user_address,
            'gas_fee_bnb' => $gas_fee,
            'usdt_amount' => $usdt_amount,
            'bman_amount' => $bman_amount,
            'plan_code' => $plan_code,
            'duration_years' => $duration_years,
            'coin_distribution_option_id' => $coin_dist_option_id,
            'message' => "Order created. Admin needs to send {$gas_fee} BNB for gas fees."
        ];
    }

    /**
     * Estimate gas fee in BNB for USDT transfer
     * Typical USDT transfer gas: ~65,000-70,000 gas
     * At gwei prices, usually 0.0008 - 0.0015 BNB
     */
    public function estimateGasFee()
    {
        try {
            $gas_limit = 70000; // Standard USDT transfer

            // Get current gas price from RPC
            $response = $this->_callRpc('eth_gasPrice', []);

            if (!$response || $response['error']) {
                // Fallback to typical gas price (50 gwei)
                $gas_price_wei = 50 * 1e9;
            } else {
                $gas_price_wei = hexdec($response['result']) ?? (50 * 1e9);
            }

            // Calculate: gas_limit * gas_price / 1e18
            $fee_wei = $gas_limit * $gas_price_wei;
            $fee_bnb = $fee_wei / 1e18;

            // Round up to nearest 0.0001
            return ceil($fee_bnb * 10000) / 10000;
        } catch (Exception $e) {
            log_message('error', 'Gas fee estimation error: ' . $e->getMessage());
            return 0.001; // Default fallback
        }
    }

    /**
     * Get swap order details
     */
    public function getOrder($order_id)
    {
        return $this->db->get_where('staking_swap_orders', ['id'=>$order_id])->row_array();
    }

    /**
     * Get swap order by reference
     */
    public function getOrderByRef($order_ref)
    {
        return $this->db->get_where('staking_swap_orders', ['ref'=>$order_ref])->row_array();
    }

    /**
     * Monitor for admin's gas fee payment (BNB to user wallet)
     * Called periodically by cron or manual check
     */
    public function detectGasFeePaid($order_id)
    {
        $order = $this->getOrder($order_id);
        if (!$order) return false;

        if ($order['status'] !== 'pending_gas_fee') {
            return false; // Already paid or completed
        }

        $user_address = strtolower($order['user_address']);

        // Check Etherscan for recent BNB transfers TO user
        $result = $this->_getEtherscanBNBTransfers($user_address);

        if (!$result || empty($result['result'])) {
            return false;
        }

        // Look for payment from admin
        foreach ($result['result'] as $tx) {
            $to = strtolower($tx['to']);
            $from = strtolower($tx['from']);
            $value = hexdec($tx['value']) / 1e18; // Convert to BNB

            if ($to === $user_address && $value >= 0.0005) {
                // Found a BNB payment!
                $tx_hash = $tx['hash'];

                // Record in onchain_transactions
                $this->wallet->recordOnchainTransaction($tx_hash, [
                    'from' => $from,
                    'to' => $to,
                    'value' => $value * 1e18, // Keep as wei
                    'hash' => $tx_hash,
                    'blockNumber' => $tx['blockNumber'] ?? '0',
                    'timeStamp' => $tx['timeStamp'] ?? time(),
                ], $order['user_id'], 'gas_fee');

                // Update order
                $this->db->where('id', $order_id)->update('staking_swap_orders', [
                    'gas_tx_hash' => $tx_hash,
                    'status' => 'pending_usdt',
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                log_message('info', "Gas fee detected for order {$order_id}: {$tx_hash}");
                return true;
            }
        }

        return false;
    }

    /**
     * Detect user's USDT payment to admin
     * Called when user initiates the transfer
     */
    public function detectUsdtPayment($order_id)
    {
        $order = $this->getOrder($order_id);
        if (!$order || $order['status'] !== 'pending_usdt') {
            return false;
        }

        // Search for USDT transfers from user to admin
        $result = $this->_getEtherscanTokenTransfers(
            $order['user_address'],
            $order['admin_address'],
            $this->usdt_contract
        );

        if (!$result || empty($result['result'])) {
            return false;
        }

        foreach ($result['result'] as $tx) {
            $from = strtolower($tx['from']);
            $to = strtolower($tx['to']);
            $value = $tx['value']; // Already in token decimals from Etherscan
            $tx_hash = strtolower($tx['hash']);

            if ($from === strtolower($order['user_address']) &&
                $to === strtolower($order['admin_address'])) {

                // Record USDT payment
                $this->wallet->recordOnchainTransaction($tx_hash, [
                    'from' => $from,
                    'to' => $to,
                    'value' => $value,
                    'hash' => $tx_hash,
                    'blockNumber' => $tx['blockNumber'] ?? '0',
                    'timeStamp' => $tx['timeStamp'] ?? time(),
                ], $order['user_id'], 'deposit');

                // Update order to pending admin transfer
                $this->db->where('id', $order_id)->update('staking_swap_orders', [
                    'usdt_tx_hash' => $tx_hash,
                    'status' => 'pending_bman',
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                log_message('info', "USDT payment detected for order {$order_id}: {$tx_hash}");
                return true;
            }
        }

        return false;
    }

    /**
     * Detect admin's BMAN transfer to user (completes the swap)
     */
    public function detectBmanTransfer($order_id)
    {
        $order = $this->getOrder($order_id);
        if (!$order || $order['status'] !== 'pending_bman') {
            return false;
        }

        // Search for BMAN transfers from admin to user
        $result = $this->_getEtherscanTokenTransfers(
            $order['admin_address'],
            $order['user_address'],
            $this->bman_contract
        );

        if (!$result || empty($result['result'])) {
            return false;
        }

        foreach ($result['result'] as $tx) {
            $from = strtolower($tx['from']);
            $to = strtolower($tx['to']);
            $value = $tx['value'];
            $tx_hash = strtolower($tx['hash']);

            if ($from === strtolower($order['admin_address']) &&
                $to === strtolower($order['user_address'])) {

                // Record BMAN transfer
                $this->wallet->recordOnchainTransaction($tx_hash, [
                    'from' => $from,
                    'to' => $to,
                    'value' => $value,
                    'hash' => $tx_hash,
                    'blockNumber' => $tx['blockNumber'] ?? '0',
                    'timeStamp' => $tx['timeStamp'] ?? time(),
                ], $order['user_id'], 'transfer');

                // Update order to completed
                $this->db->where('id', $order_id)->update('staking_swap_orders', [
                    'bman_tx_hash' => $tx_hash,
                    'status' => 'swap_completed',
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

                // Create staking record (activate immediately)
                $this->_createStakingRecord($order['user_id'], $order['package_id'], $order['bman_amount'], $order['bonus_bman']);

                // Credit BMAN balance to user wallet
                $this->_creditBmanBalance($order['user_id'], $order['bman_amount'] + $order['bonus_bman']);

                log_message('info', "BMAN transfer detected for order {$order_id}: {$tx_hash}, staking activated");
                return true;
            }
        }

        return false;
    }

    /**
     * Create staking record when swap completes
     */
    private function _createStakingRecord($user_id, $package_id, $bman_amount, $bonus_bman)
    {
        $staking_data = [
            'user_id' => $user_id,
            'package_id' => $package_id,
            'bman_amount' => $bman_amount,
            'bonus_bman' => $bonus_bman,
            'status' => 'active',
            'activated_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $this->db->insert('user_stakes', $staking_data);

        // Credit staking balance
        $this->_creditStakingBalance($user_id, $bman_amount);
    }

    /**
     * Credit BMAN exchange balance to user wallet
     */
    private function _creditBmanBalance($user_id, $amount)
    {
        // Update wallet balance
        $this->db->where('user_id', $user_id)
            ->set('exchange', "exchange + {$amount}", false)
            ->update('wallet_ledger');
    }

    /**
     * Credit BMAN staking balance
     */
    private function _creditStakingBalance($user_id, $amount)
    {
        $this->db->where('user_id', $user_id)
            ->set('staking', "staking + {$amount}", false)
            ->update('wallet_ledger');
    }

    /**
     * Get BNB transfers from Etherscan (for gas fee detection)
     */
    private function _getEtherscanBNBTransfers($address)
    {
        $config = $this->db->get_where('token_settings', ['status'=>1])->row_array();
        $api_key = $config['etherscan_api_key'] ?? '';
        $etherscan_url = $config['etherscan_url'] ?? 'https://api.bscscan.com';

        $url = $etherscan_url . '/api?module=account&action=txlist&address=' . $address
             . '&startblock=0&endblock=99999999&sort=desc&apikey=' . $api_key;

        $response = file_get_contents($url);
        return json_decode($response, true);
    }

    /**
     * Get token transfers from Etherscan (for USDT/BMAN payments)
     */
    private function _getEtherscanTokenTransfers($from_addr, $to_addr, $contract)
    {
        $config = $this->db->get_where('token_settings', ['status'=>1])->row_array();
        $api_key = $config['etherscan_api_key'] ?? '';
        $etherscan_url = $config['etherscan_url'] ?? 'https://api.bscscan.com';

        $url = $etherscan_url . '/api?module=account&action=tokentx&contractaddress=' . $contract
             . '&address=' . $from_addr . '&startblock=0&endblock=99999999&sort=desc&apikey=' . $api_key;

        $response = file_get_contents($url);
        return json_decode($response, true);
    }

    /**
     * Call Ethereum RPC (for gas price estimation)
     */
    private function _callRpc($method, $params = [])
    {
        if (!$this->rpc_url) {
            return null;
        }

        $payload = [
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => $params,
            'id' => 1,
        ];

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($payload),
            ]
        ]);

        $response = @file_get_contents($this->rpc_url, false, $context);
        return $response ? json_decode($response, true) : null;
    }
}
