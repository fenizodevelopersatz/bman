<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Wallet_sync — Real-time on-chain balance check + instant deposit crediting.
 *
 * Provides user-facing endpoints to:
 * 1. Check their on-chain balance vs database (detect unconfirmed deposits)
 * 2. Manually trigger deposit scan (for their address)
 * 3. Fetch live wallet history with sync status
 *
 * Integrates with:
 * - Chainsync_model for balance sync
 * - Depositlistener_model for deposit detection
 * - Walletledger_model for ledger reads
 */
class Wallet_sync extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Walletledger_model', 'ledger');
        $this->load->model('Chainsync_model', 'chainsync');
        $this->load->model('Depositlistener_model', 'deposit');
        $this->load->library('api_response');
    }

    /**
     * AJAX endpoint: Check user's on-chain balances vs database.
     * Useful button on wallet page: "Sync Balances" / "Check On-Chain"
     *
     * Returns:
     * {
     *   "on_chain": { "usdt": "100.5000", "bnb": "0.5", "bman": "500" },
     *   "database": { "usdt": "100.5000", ... },
     *   "synced": true/false,
     *   "pending_deposits": [ { tx_hash, amount, blocks_until_confirmed } ],
     *   "last_sync": "2026-02-26 14:30:45"
     * }
     */
    public function check_balance()
    {
        $user_id = (int) $this->session->userdata('user_userid');
        if (!$user_id) {
            $this->api_response->error('Not logged in', 401);
            return;
        }

        try {
            // 1. Get user's custodial address
            $address = $this->db
                ->select('wallet_address')
                ->from('user_wallet')
                ->where('user_id', $user_id)
                ->get()
                ->row_array();

            if (!$address || empty($address['wallet_address'])) {
                $this->api_response->error('User has no custodial wallet configured', 400);
                return;
            }

            $wallet = $address['wallet_address'];

            // 2. Get on-chain balances (USDT, BNB, BMAN)
            $onchain = $this->getOnchainBalances($wallet);

            // 3. Get database balances
            $db = $this->ledger->balances($user_id);

            // 4. Get pending deposits (confirmed on-chain but not credited yet)
            $pending = $this->getPendingDeposits($user_id, $wallet);

            // 5. Get last sync time
            $lastSync = $this->db
                ->select('MAX(created_at) as last_sync')
                ->from('onchain_transactions')
                ->where('user_id', $user_id)
                ->get()
                ->row_array()['last_sync'] ?? null;

            $response = [
                'on_chain' => $onchain,
                'database' => $db,
                'wallet_address' => $wallet,
                'synced' => $this->isBalanceSynced($onchain, $db),
                'pending_deposits' => $pending,
                'last_sync' => $lastSync,
                'sync_now_available' => true,
            ];

            $this->api_response->success($response);

        } catch (Exception $e) {
            $this->api_response->error('Balance check failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * AJAX endpoint: Manually trigger deposit scan for this user.
     * Call when user wants to check for pending deposits immediately.
     *
     * Returns:
     * {
     *   "status": "success",
     *   "message": "Found 2 confirmed deposits, credited...",
     *   "deposits_found": 2,
     *   "deposits_credited": 2,
     *   "new_balance": "250.5000",
     *   "tx_hashes": ["0x...", "0x..."]
     * }
     */
    public function scan_deposits()
    {
        $user_id = (int) $this->session->userdata('user_userid');
        if (!$user_id) {
            $this->api_response->error('Not logged in', 401);
            return;
        }

        try {
            // Run deposit scan for this user only
            $result = $this->deposit->scan($user_id);

            if (!isset($result['ok']) || !$result['ok']) {
                $this->api_response->error($result['message'] ?? 'Scan failed', 500);
                return;
            }

            // Get updated balance
            $bal = $this->ledger->balances($user_id);

            $this->api_response->success([
                'status' => 'success',
                'message' => $result['message'] ?? 'Scan completed',
                'deposits_found' => $result['detected'] ?? 0,
                'deposits_credited' => $result['credited'] ?? 0,
                'new_balance_usdt' => $bal['usdt'] ?? 0,
                'new_balance_bman' => $bal['earning'] ?? 0,
                'tx_hashes' => $result['tx_hashes'] ?? [],
            ]);

        } catch (Exception $e) {
            $this->api_response->error('Deposit scan failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * AJAX endpoint: Get wallet history with real-time sync info.
     * Extends the existing history with on-chain confirmation status.
     *
     * Query params: type, status, from, to
     *
     * Returns:
     * {
     *   "history": [ { id, type, amount, status, tx_hash, confirmed_at, ... } ],
     *   "balance_summary": { usdt, bman, earning, staking, bonus },
     *   "wallet_synced": true/false,
     *   "last_sync": "2026-02-26 14:35:00"
     * }
     */
    public function history()
    {
        $user_id = (int) $this->session->userdata('user_userid');
        if (!$user_id) {
            $this->api_response->error('Not logged in', 401);
            return;
        }

        try {
            $this->load->model('Wallet_model', 'wallet');

            // Filters
            $filters = [
                'type'   => strtolower(trim((string) $this->input->get('type'))),
                'status' => trim((string) $this->input->get('status')),
                'from'   => trim((string) $this->input->get('from')),
                'to'     => trim((string) $this->input->get('to')),
            ];

            $page = max(1, (int) $this->input->get('page', true) ?? 1);
            $limit = (int) $this->input->get('limit', true) ?? 20;
            $offset = ($page - 1) * $limit;

            // Get history with on-chain enrichment
            $history = $this->getWalletHistory($user_id, $filters, $limit, $offset);

            // Get current balances
            $bal = $this->ledger->balances($user_id);

            // Check sync status
            $onchain = $this->getOnchainBalances(
                $this->db->get_where('user_wallet', ['user_id' => $user_id])->row_array()['wallet_address'] ?? null
            );

            $response = [
                'history' => $history,
                'balance_summary' => $bal,
                'wallet_synced' => $this->isBalanceSynced($onchain, $bal),
                'last_sync' => $this->db
                    ->select('MAX(created_at) as ts')
                    ->from('onchain_transactions')
                    ->where('user_id', $user_id)
                    ->get()
                    ->row_array()['ts'] ?? null,
                'page' => $page,
                'limit' => $limit,
            ];

            $this->api_response->success($response);

        } catch (Exception $e) {
            $this->api_response->error('History fetch failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get on-chain balances for a wallet address (USDT, BNB, BMAN).
     * Uses Web3bman library (read-only, no signing).
     *
     * @param string $address Wallet address (0x...)
     * @return array ['usdt'=>'...', 'bnb'=>'...', 'bman'=>'...']
     */
    private function getOnchainBalances($address)
    {
        if (!$address) {
            return ['usdt' => '0', 'bnb' => '0', 'bman' => '0'];
        }

        try {
            $this->load->library('web3bman');
            $cfg = $this->db->get_where('token_settings', ['status' => 1])->row_array();

            $result = [
                'usdt' => $this->web3bman->getTokenBalance(
                    $address,
                    $cfg['usdt_contract'] ?? null
                ) ?? '0',
                'bman' => $this->web3bman->getTokenBalance(
                    $address,
                    $cfg['bman_contract'] ?? null
                ) ?? '0',
                'bnb' => $this->web3bman->getBnbBalance($address) ?? '0',
            ];

            return $result;
        } catch (Exception $e) {
            log_message('error', '[Wallet_sync] On-chain balance check failed: ' . $e->getMessage());
            return ['usdt' => '?', 'bnb' => '?', 'bman' => '?']; // Indicate error
        }
    }

    /**
     * Check if balances are in sync (on-chain ≈ database).
     *
     * @return bool True if within tolerance (0.01 threshold)
     */
    private function isBalanceSynced($onchain, $db)
    {
        if (!$onchain || !$db) return false;

        $threshold = 0.01;
        return (abs((float)$onchain['usdt'] - (float)$db['usdt']) < $threshold) &&
               (abs((float)$onchain['bman'] - (float)($db['earning'] + $db['staking'] + $db['bonus'])) < $threshold);
    }

    /**
     * Get pending deposits (confirmed on-chain but not yet credited to DB).
     *
     * @return array Pending deposits with tx_hash, amount, blocks_until_confirmed
     */
    private function getPendingDeposits($user_id, $wallet)
    {
        // Find on-chain transactions to this address that haven't been credited yet
        $pending = $this->db
            ->select('octs.tx_hash, octs.value as amount, octs.confirmation_count, octs.status')
            ->from('onchain_transactions octs')
            ->where('octs.to_address', strtolower($wallet))
            ->where('octs.status', 'confirmed')
            ->where('octs.confirmation_count >=', 15) // Min confirmations
            ->join('wallet_ledger wl', 'octs.tx_hash = wl.reference_id', 'left')
            ->where('wl.id IS NULL', null, false) // Not yet in ledger
            ->get()
            ->result_array();

        return $pending;
    }

    /**
     * Get wallet history with on-chain enrichment.
     * Joins with onchain_transactions to show blockchain status.
     *
     * @return array History records with tx_hash, status, block_number, etc.
     */
    private function getWalletHistory($user_id, $filters, $limit, $offset)
    {
        $this->load->model('Wallet_model', 'wallet');

        // Get ledger history (all transactions)
        $query = $this->db
            ->select('wl.*, octs.block_number, octs.confirmation_count, octs.status as onchain_status, octs.gas_fee')
            ->from('wallet_ledger wl')
            ->join('onchain_transactions octs', 'wl.reference_id = octs.tx_hash', 'left')
            ->where('wl.user_id', $user_id);

        // Apply filters
        if (!empty($filters['type'])) {
            $query->where('wl.reference_type', $filters['type']);
        }
        if (!empty($filters['status'])) {
            $query->where('wl.status', $filters['status']);
        }
        if (!empty($filters['from'])) {
            $query->where('DATE(wl.created_at) >=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $query->where('DATE(wl.created_at) <=', $filters['to']);
        }

        $history = $query
            ->order_by('wl.created_at', 'DESC')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->result_array();

        return $history;
    }
}
