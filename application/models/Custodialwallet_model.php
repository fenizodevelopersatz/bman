<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Custodialwallet_model — internal (off-chain) BMAN/USDT ledger.
 * -------------------------------------------------------------------
 * In a CUSTODIAL platform the exchange holds ONE set of on-chain wallets
 * (treasury / deposit / gas — configured in Master → Token Settings). Each
 * user has a *custodial* address for receiving deposits, but their spendable
 * balance is an INTERNAL ledger number, not an on-chain balance.
 *
 * Giving a user BMAN (purchase credit, ROI, bonus, matching, admin grant) is
 * therefore just a signed ledger entry in `wallet_transactions` — it needs
 * NO private key and touches NO blockchain. A private key is only ever needed
 * when a user WITHDRAWS to their own external wallet, and even then it is the
 * TREASURY/GAS wallet key (used by Web3bman::sendToken), never a per-user key.
 *
 * This model is the single place that credits/debits internal balances so the
 * math stays consistent with the existing Wallet_model reads.
 *
 * Wallet keys (map to the four §3A wallets): exchange · earning · staking · bonus.
 */
class Custodialwallet_model extends CI_Model
{
    private $wallets = ['exchange', 'earning', 'staking', 'bonus'];

    /* ----------------------------- balances ----------------------------- */

    /** ⚠️ DEPRECATED: Use on-chain transactions only (see getOnchainTransactions) */
    public function balance($user_id, $wallet = 'exchange')
    {
        // if (!in_array($wallet, $this->wallets, true)) return '0';
        // $row = $this->db->select("...", false)
        //     ->where('user_id', (int)$user_id)
        //     ->get('wallet_transactions')->row_array();
        // return bcsub(...);

        return '0';  // No internal ledger balance - use on-chain only
    }

    public function balances($user_id)
    {
        $out = [];
        foreach ($this->wallets as $w) $out[$w] = $this->balance($user_id, $w);
        return $out;
    }

    /* ⚠️ DEPRECATED: Internal ledger removed - use on-chain transactions only */

    /**
     * ⚠️ DEPRECATED: Credit to internal wallet removed
     * Use on-chain blockchain transactions instead
     */
    public function credit($user_id, $amount, $wallet = 'exchange', $source = 'admin_credit')
    {
        // wallet_transactions table no longer used - on-chain only
        return 0;
    }

    /**
     * ⚠️ DEPRECATED: Debit from internal wallet removed
     * Use on-chain blockchain transactions instead
     */
    public function debit($user_id, $amount, $wallet = 'exchange', $source = 'internal')
    {
        // wallet_transactions table no longer used - on-chain only
        return 0;
    }

    /** Internal wallet-to-wallet move for one user (e.g. Exchange → Staking). */
    public function move($user_id, $amount, $from_wallet, $to_wallet, $source = 'internal_move')
    {
        $this->db->trans_start();
        $this->debit($user_id, $amount, $from_wallet, $source);
        $this->credit($user_id, $amount, $to_wallet, $source);
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    /** All five wallet balances (proposal §3): USDT + the four §3A buckets. */
    public function fiveWallets($user_id)
    {
        return array_merge(
            ['usdt' => $this->usdtBalance($user_id)],
            $this->balances($user_id)
        );
    }

    /* =================== CUSTODIAL ON-CHAIN ADDRESS ==================== */

    /** The user's custodial deposit address row (user_wallet), or null. */
    public function walletRow($user_id)
    {
        return $this->db->get_where('user_wallet', ['user_id' => (int)$user_id])->row_array() ?: null;
    }

    /**
     * Ensure the user has ONE unique on-chain deposit address. Returns the
     * user_wallet row. If a row already exists it is returned untouched
     * (existing ETH_MASTER wallets are respected). If none exists a fresh
     * BEP-20 wallet is generated LOCALLY via Web3bman (no external ADROX
     * dependency), its key AES-encrypted, a QR PNG rendered with the existing
     * InfiQr generator, and the row inserted with a uniqueness guard.
     */
    public function ensureAddress($user_id)
    {
        $user_id = (int)$user_id;
        $existing = $this->walletRow($user_id);
        if ($existing) return $existing;

        $this->load->library('web3bman');

        // generate until the address is unique in user_wallet (practically 1 try)
        $tries = 0;
        do {
            $w = $this->web3bman->generateWallet();      // ['address','private_key']
            $addr = $w['address'];
            $dupe = $this->db->where('wallet_address', $addr)->count_all_results('user_wallet');
            $tries++;
        } while ($dupe > 0 && $tries < 5);
        if ($dupe > 0) return null;

        // QR (reuse the existing InfiQr wrapper) — assets/images/qr_image/<addr>qr_code.png
        $qr_url = '';
        try {
            $this->load->library('gloabals');
            $dir = 'assets/images/qr_image/';
            if (!is_dir('./'.$dir)) @mkdir('./'.$dir, 0777, true);
            $file = $dir.$addr.'qr_code.png';
            $this->gloabals->generate($addr, 'png', $file);
            $qr_url = base_url($file);
        } catch (Exception $e) {
            $qr_url = ''; // QR is best-effort; address still stored
        }

        $this->db->insert('user_wallet', [
            'user_id'        => $user_id,
            'wallet_address' => $addr,
            'mnemonic'       => '',                                   // local wallet: no mnemonic stored
            'private_key'    => $this->web3bman->encryptKey($w['private_key']), // AES, not ADROX
            'wallet_qrimage' => $qr_url,
        ]);
        return $this->walletRow($user_id);
    }

    /* ==================== ON-CHAIN vs DB MONITOR ===================== */

    /** Internal USDT balance we credited to the user (our DB record). */
    public function usdtBalance($user_id)
    {
        $row = $this->db->get_where('user_wallets', ['user_id' => (int)$user_id])->row_array();
        return $row ? (string)$row['usd_balance'] : '0';
    }

    /**
     * Read the real on-chain balances of the user's address (USDT, BMAN, BNB)
     * and compare the USDT balance against our DB record. Returns null when the
     * user has no address. ⚠️ NO logging for user balance checks (only admin reconcile logs).
     */
    public function monitor($user_id, $changed_by = null)
    {
        $wallet = $this->walletRow($user_id);
        if (!$wallet) return null;
        $addr = $wallet['wallet_address'];

        $this->load->library('web3bman');
        $this->load->model('Tokenmaster_model', 'tokens');
        $cfg = $this->tokens->activeSettings();
        $usdt_contract = $cfg['usdt_contract'] ?? null;

        $onchain_usdt = $usdt_contract ? $this->web3bman->getTokenBalance($addr, $usdt_contract) : '0';
        $onchain_bnb  = $this->web3bman->getBnbBalance($addr);
        $onchain_bman = ($cfg['bman_contract'] ?? null) ? $this->web3bman->getTokenBalance($addr) : '0';

        $db_usdt = $this->usdtBalance($user_id);
        $diff = bcsub((string)$onchain_usdt, (string)$db_usdt, 8);

        // ⚠️ REMOVED: wallet_monitor_log insert — was causing table bloat with every user balance check
        // Only admin reconciliation logs (see reconcile() method below)

        return [
            'address'       => $addr,
            'qr'            => $wallet['wallet_qrimage'],
            'onchain_usdt'  => (string)$onchain_usdt,
            'onchain_bnb'   => (string)$onchain_bnb,
            'onchain_bman'  => (string)$onchain_bman,
            'db_usdt'       => (string)$db_usdt,
            'difference'    => $diff,        // >0 => on-chain funds not yet credited
            'has_pending'   => bccomp($diff, '0', 8) > 0,
        ];
    }

    /**
     * Reconcile a positive difference: credit it to the user's internal USDT
     * balance (user_wallets.usd_balance), record a custodial_deposits row and a
     * reconcile log entry. Idempotent-ish: only credits a strictly positive
     * difference. Returns [ok, message].
     */
    public function reconcile($user_id, $changed_by, $note = null)
    {
        $m = $this->monitor($user_id, $changed_by);
        if (!$m) return [false, 'User has no custodial address.'];
        if (bccomp($m['difference'], '0', 8) <= 0) {
            return [true, 'No positive difference to credit (already reconciled).'];
        }
        $amount = $m['difference'];

        $this->db->trans_start();
        // upsert user_wallets.usd_balance += amount
        $row = $this->db->get_where('user_wallets', ['user_id' => (int)$user_id])->row_array();
        if ($row) {
            $this->db->where('user_id', (int)$user_id)->update('user_wallets', [
                'usd_balance' => bcadd((string)$row['usd_balance'], $amount, 8),
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);
        } else {
            $this->db->insert('user_wallets', [
                'user_id' => (int)$user_id, 'usd_balance' => $amount,
                'usd_pending' => 0, 'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
        $this->db->insert('custodial_deposits', [
            'user_id' => (int)$user_id, 'address' => $m['address'], 'token' => 'USDT',
            'amount'  => $amount, 'onchain_confirmed' => 1, 'credited' => 1,
            'source'  => 'monitor', 'note' => $note ? substr($note, 0, 255) : 'reconciled from on-chain',
            'credited_at' => date('Y-m-d H:i:s'),
        ]);
        // ⚠️ REMOVED: wallet_monitor_log — no longer used (on-chain only)
        $this->db->trans_complete();
        if (!$this->db->trans_status()) return [false, 'Database error.'];
        return [true, 'Credited '.$amount.' USDT to the internal balance.'];
    }

    /* -------------------------- histories -------------------------- */

    /**
     * Get deposits for a user: BOTH confirmed (wallet_deposits) AND pending (on-chain but not credited).
     * Pending deposits show immediately without waiting for DepositListener cron.
     *
     * @param int $user_id User ID
     * @param int $limit Max results (default 50)
     * @return array Deposits with status: 'credited' | 'pending_confirmation' | 'needs_confirmation'
     */
    public function deposits($user_id, $limit = 50)
    {
        $user_id = (int)$user_id;
        $limit = (int)$limit;

        $all_deposits = [];

        // 1. CONFIRMED deposits: wallet_deposits written by Depositlistener_model
        $confirmed = $this->db->where('user_id', $user_id)
                              ->order_by('id', 'DESC')
                              ->get('wallet_deposits')
                              ->result_array();

        if (!empty($confirmed)) {
            foreach ($confirmed as $r) {
                $all_deposits[] = [
                    'id' => $r['id'] ?? null,
                    'token' => $r['token'] ?? 'USDT',
                    'amount_usdt' => $r['amount_usdt'] ?? 0,
                    'status' => 'credited',  // ✅ Already in database
                    'tx_hash' => $r['tx_hash'] ?? '',
                    'detected_at' => $r['credited_at'] ?? ($r['created_at'] ?? ''),
                    'confirmed_at' => $r['credited_at'] ?? '',
                    'wallet_address' => $r['wallet_address'] ?? '',
                    'network' => $r['network'] ?? 'bsc',
                    'block_number' => $r['block_number'] ?? null,
                    'confirmations' => $r['confirmations'] ?? null,
                    'source' => 'wallet_deposits',  // DEBUG: show where data came from
                ];
            }
        }

        // 2. PENDING deposits: On-chain confirmed (15+ blocks) but NOT YET credited to wallet_deposits
        // This shows deposits immediately without waiting for DepositListener cron
        $user_wallet = $this->walletRow($user_id);
        if ($user_wallet && !empty($user_wallet['wallet_address'])) {
            $pending = $this->db
                ->select('octs.*, "pending_confirmation" as status')
                ->from('onchain_transactions octs')
                ->where('octs.to_address', strtolower($user_wallet['wallet_address']))
                ->where('octs.status', 'confirmed')
                ->where('octs.confirmation_count >=', 15) // Min confirmations for USDT
                ->join('wallet_deposits wd', 'octs.tx_hash = wd.tx_hash', 'left')
                ->where('wd.id IS NULL', null, false)  // NOT already credited
                ->order_by('octs.created_at', 'DESC')
                ->get()
                ->result_array();

            if (!empty($pending)) {
                foreach ($pending as $p) {
                    $all_deposits[] = [
                        'id' => null,  // Not in wallet_deposits yet
                        'token' => 'USDT',
                        'amount_usdt' => (float)($p['value'] ?? 0),
                        'status' => 'pending_confirmation',  // ⚠️ On-chain, waiting to be credited
                        'tx_hash' => $p['tx_hash'] ?? '',
                        'detected_at' => $p['created_at'] ?? '',
                        'confirmed_at' => $p['created_at'] ?? '',
                        'wallet_address' => $p['to_address'] ?? '',
                        'network' => $p['network'] ?? 'bsc',
                        'block_number' => $p['block_number'] ?? null,
                        'confirmations' => $p['confirmation_count'] ?? 0,
                        'source' => 'onchain_transactions',  // DEBUG: pending, needs manual credit
                    ];
                }
            }
        }

        // 3. Sort all deposits by date (newest first) and return top N
        usort($all_deposits, function($a, $b) {
            return strtotime($b['detected_at'] ?? '0') - strtotime($a['detected_at'] ?? '0');
        });

        return array_slice($all_deposits, 0, $limit);
    }

    /**
     * Get ONLY pending deposits (on-chain but not credited).
     * Useful for "refresh" button or auto-detect pending credits.
     *
     * @param int $user_id User ID
     * @return array Pending deposits
     */
    public function getPendingDeposits($user_id)
    {
        $user_id = (int)$user_id;
        $user_wallet = $this->walletRow($user_id);

        if (!$user_wallet || empty($user_wallet['wallet_address'])) {
            return [];
        }

        return $this->db
            ->select('octs.*')
            ->from('onchain_transactions octs')
            ->where('octs.to_address', strtolower($user_wallet['wallet_address']))
            ->where('octs.status', 'confirmed')
            ->where('octs.confirmation_count >=', 15)
            ->join('wallet_deposits wd', 'octs.tx_hash = wd.tx_hash', 'left')
            ->where('wd.id IS NULL', null, false)
            ->order_by('octs.created_at', 'DESC')
            ->get()
            ->result_array();
    }

    /**
     * Record complete on-chain transaction details in onchain_transactions table
     * Called when Etherscan API returns full transaction data
     * Stores: from_address, to_address, amount, gas, timestamp, block, etc.
     */
    public function recordOnchainTransaction($tx_hash, $etherscan_tx_data, $user_id = null)
    {
        if (empty($tx_hash) || !is_array($etherscan_tx_data)) {
            return false;
        }

        // Extract all available data from Etherscan response
        $tx_hash = strtolower(trim($tx_hash));
        $from = strtolower($etherscan_tx_data['from'] ?? '');
        $to = strtolower($etherscan_tx_data['to'] ?? '');

        // Convert wei to decimal (assuming 18 decimals for USDT)
        $value_wei = isset($etherscan_tx_data['value']) ? (string)$etherscan_tx_data['value'] : '0';
        $amount = bcdiv($value_wei, bcpow('10', '18', 0), 18); // wei to USDT

        $block = isset($etherscan_tx_data['blockNumber']) ? (int)$etherscan_tx_data['blockNumber'] : 0;
        $timestamp = isset($etherscan_tx_data['timeStamp']) ? (int)$etherscan_tx_data['timeStamp'] : 0;
        $gas_used = isset($etherscan_tx_data['gasUsed']) ? (int)$etherscan_tx_data['gasUsed'] : 0;
        $status_rep = isset($etherscan_tx_data['statusRep']) ? (int)$etherscan_tx_data['statusRep'] : 1;
        $tx_index = isset($etherscan_tx_data['transactionIndex']) ? (int)$etherscan_tx_data['transactionIndex'] : 0;

        // Determine if incoming or outgoing
        $is_incoming = ($to === strtolower($etherscan_tx_data['to'] ?? ''));
        $tx_type = $is_incoming ? 'deposit' : 'transfer';

        // Check if already exists
        $existing = $this->db->where('tx_hash', $tx_hash)
            ->get('onchain_transactions')
            ->row_array();

        // Map to actual table columns (not value, but amount)
        $data = [
            'tx_hash' => $tx_hash,
            'from_address' => $from,
            'to_address' => $to,
            'amount' => $amount,  // ✓ Use 'amount' not 'value'
            'tx_type' => $tx_type,
            'block_number' => $block,
            'confirmation_count' => 0,
            'status' => $status_rep === 1 ? 'confirmed' : 'failed',
            'network' => 'bsc',
            'tx_index' => $tx_index,  // ✓ Use 'tx_index' not 'transaction_index'
            'gas_used' => $gas_used,
            'block_timestamp' => $timestamp > 0 ? date('Y-m-d H:i:s', $timestamp) : date('Y-m-d H:i:s'),
            'created_at' => $timestamp > 0 ? date('Y-m-d H:i:s', $timestamp) : date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($user_id) {
            $data['user_id'] = (int)$user_id;
        }

        if ($existing) {
            // Update existing record with newly fetched details
            return $this->db->where('tx_hash', $tx_hash)
                ->update('onchain_transactions', $data) > 0;
        } else {
            // Insert new record
            return $this->db->insert('onchain_transactions', $data);
        }
    }

    /**
     * Populate balance_before and balance_after using Etherscan tokentx API
     * Fetches historical balance snapshots for each transaction
     */
    public function populateBalanceSnapshots($user_id = null, $limit = 100)
    {
        // Load token settings (API key and URL)
        $settings = $this->db->select('explorer_api_url, explorer_api_key, usdt_contract, chain_id, usdt_decimals')
            ->from('token_settings')
            ->where('network', 'mainnet')
            ->limit(1)
            ->get()
            ->row_array();

        if (!$settings || empty($settings['explorer_api_key'])) {
            log_message('error', 'Cannot populate balances: API key not configured');
            return ['success' => false, 'updated' => 0];
        }

        // Find transactions with missing balance_before or balance_after
        $query = $this->db->from('onchain_transactions')
            ->where('status', 'confirmed')
            ->where('amount >', 0);

        if ($user_id) {
            $query->where('user_id', (int)$user_id);
        }

        $txs = $query->order_by('block_number', 'DESC')
            ->limit($limit)
            ->get()
            ->result_array();

        if (empty($txs)) {
            return ['success' => true, 'updated' => 0, 'message' => 'No transactions need balance updates'];
        }

        $api_url = trim($settings['explorer_api_url']);
        $api_key = trim($settings['explorer_api_key']);
        $usdt_contract = $settings['usdt_contract'];
        $chain_id = (int)$settings['chain_id'];
        $decimals = (int)($settings['usdt_decimals'] ?? 18);

        $updated = 0;

        foreach ($txs as $tx) {
            $to_addr = strtolower($tx['to_address'] ?? '');
            if (empty($to_addr)) continue;

            try {
                // Use Etherscan tokentx API to get historical token transfers for this address
                $q = http_build_query([
                    'chainid' => $chain_id,
                    'module' => 'account',
                    'action' => 'tokentx',
                    'contractaddress' => $usdt_contract,
                    'address' => $to_addr,
                    'page' => 1,
                    'offset' => 100,
                    'sort' => 'desc',
                    'apikey' => $api_key,
                ]);

                $ch = curl_init($api_url . '?' . $q);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 15,
                    CURLOPT_SSL_VERIFYPEER => false,
                ]);
                $response = curl_exec($ch);
                curl_close($ch);

                $data = json_decode($response, true);
                if (!isset($data['result']) || !is_array($data['result'])) {
                    continue;
                }

                // Find this transaction in the history and calculate balances
                $balance = '0';
                $found = false;

                foreach (array_reverse($data['result']) as $hist_tx) {
                    $hist_hash = strtolower($hist_tx['hash'] ?? '');

                    // Calculate running balance
                    $value = isset($hist_tx['value']) && $hist_tx['value'] !== '0x0'
                        ? bcdiv((string)$hist_tx['value'], bcpow('10', (string)$decimals, 0), 18)
                        : '0';

                    $is_to = strtolower($hist_tx['to'] ?? '') === $to_addr;

                    // Found our transaction - capture BEFORE balance
                    if ($hist_hash === strtolower($tx['tx_hash'])) {
                        $balance_before = $balance;
                        $balance_after = $is_to
                            ? bcadd((string)$balance, (string)$value, 18)
                            : bcsub((string)$balance, (string)$value, 18);

                        if (bccomp($balance_after, '0', 18) < 0) {
                            $balance_after = '0';
                        }

                        // Update database
                        $update_data = [
                            'balance_before' => $balance_before,
                            'balance_after' => $balance_after,
                            'updated_at' => date('Y-m-d H:i:s'),
                        ];

                        $this->db->where('id', $tx['id'])->update('onchain_transactions', $update_data);
                        $updated++;

                        log_message('info', "Updated balances for TX {$tx['tx_hash']}: before={$balance_before}, after={$balance_after}");
                        $found = true;
                        break;
                    }

                    // Update running balance for next transaction
                    if ($is_to) {
                        $balance = bcadd((string)$balance, (string)$value, 18);
                    } else {
                        $balance = bcsub((string)$balance, (string)$value, 18);
                        if (bccomp($balance, '0', 18) < 0) {
                            $balance = '0';
                        }
                    }
                }

                usleep(100000); // 0.1s rate limiting
            } catch (Exception $e) {
                log_message('error', "Failed to populate balance for TX {$tx['tx_hash']}: " . $e->getMessage());
            }
        }

        return ['success' => true, 'updated' => $updated, 'message' => "Updated $updated transaction(s) with balance snapshots"];
    }

    /**
     * Bulk enrich all recent wallet deposits with full Etherscan transaction data
     * Called from wallet_check endpoint to populate onchain_transactions with complete details
     * Uses existing token_settings API configuration
     */
    public function enrichAllRecentDeposits($user_id = null, $limit = 50)
    {
        // Load token settings (API key and URL)
        $settings = $this->db->select('explorer_api_url, explorer_api_key, usdt_contract, chain_id, usdt_decimals')
            ->from('token_settings')
            ->where('network', 'mainnet')
            ->limit(1)
            ->get()
            ->row_array();

        if (!$settings || empty($settings['explorer_api_key'])) {
            log_message('error', 'Cannot enrich deposits: API key not configured');
            return ['success' => false, 'message' => 'API key not configured'];
        }

        // Get custodial wallets to check
        $query = $this->db->from('user_wallet');
        if ($user_id) {
            $query->where('user_id', (int)$user_id);
        }
        $wallets = $query->limit($limit)->get()->result_array();

        if (empty($wallets)) {
            return ['success' => true, 'enriched' => 0, 'message' => 'No wallets to enrich'];
        }

        $api_url = trim($settings['explorer_api_url']);
        $api_key = trim($settings['explorer_api_key']);
        $usdt_contract = $settings['usdt_contract'];
        $chain_id = (int)$settings['chain_id'];
        $decimals = (int)($settings['usdt_decimals'] ?? 18);

        $enriched_count = 0;
        $failed_count = 0;

        foreach ($wallets as $wallet) {
            $wallet_addr = $wallet['wallet_address'];
            $user_id_val = (int)$wallet['user_id'];

            log_message('info', "Enriching deposits for wallet {$wallet_addr}");

            // Call Etherscan API for this wallet
            $q = http_build_query([
                'chainid' => $chain_id,
                'module' => 'account',
                'action' => 'tokentx',
                'contractaddress' => $usdt_contract,
                'address' => $wallet_addr,
                'page' => 1,
                'offset' => 50,
                'sort' => 'desc',
                'apikey' => $api_key,
            ]);

            $ch = curl_init($api_url . '?' . $q);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 25,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($http_code !== 200 || !$response) {
                log_message('error', "Etherscan API failed for {$wallet_addr}: HTTP {$http_code}");
                $failed_count++;
                continue;
            }

            $data = json_decode($response, true);
            if (!isset($data['result']) || !is_array($data['result'])) {
                log_message('warning', "No transactions found for {$wallet_addr}");
                continue;
            }

            // Process each transaction
            foreach ($data['result'] as $tx) {
                // Only store incoming transfers
                if (strtolower($tx['to'] ?? '') !== strtolower($wallet_addr)) {
                    continue;
                }

                try {
                    if ($this->recordOnchainTransaction($tx['hash'], $tx, $user_id_val)) {
                        $enriched_count++;
                        log_message('info', "Enriched TX: {$tx['hash']} (from: {$tx['from']}, to: {$tx['to']})");
                    }
                } catch (Exception $e) {
                    log_message('error', "Failed to record TX {$tx['hash']}: " . $e->getMessage());
                }

                usleep(50000); // 0.05s rate limiting
            }

            usleep(100000); // 0.1s delay between wallets
        }

        return [
            'success' => true,
            'enriched' => $enriched_count,
            'failed' => $failed_count,
            'message' => "Enriched {$enriched_count} transaction(s)",
        ];
    }

    /**
     * Enrich transaction with Etherscan API details (from_address, to_address, value, balances)
     * Only called when balance mismatch is detected to avoid paid API hits
     * Uses API key from token_settings table (admin configured)
     */
    public function enrichTransactionFromEtherscan($tx_hash, $user_wallet_address = '')
    {
        $tx_hash = trim($tx_hash);
        if (empty($tx_hash)) return false;

        // Load Etherscan API config from token_settings table
        $settings = $this->db->select('explorer_api_url, explorer_api_key')
            ->from('token_settings')
            ->where('network', 'mainnet')
            ->limit(1)
            ->get()
            ->row_array();

        if (!$settings || empty($settings['explorer_api_key'])) {
            log_message('error', 'Etherscan API key not configured in token_settings');
            return false;
        }

        $api_key = trim($settings['explorer_api_key']);
        $api_url = trim($settings['explorer_api_url']) ?: 'https://api.bscscan.com/api';

        // 1. Fetch transaction details (from, to, value, input)
        $tx_data = $this->_fetchFromEtherscan($api_url, $api_key, 'eth_getTransactionByHash', ['txhash' => $tx_hash]);
        if (!$tx_data) return false;

        $tx = $tx_data['result'];
        $enriched = [
            'from' => strtolower($tx['from'] ?? ''),
            'to'   => strtolower($tx['to'] ?? ''),
            'value' => isset($tx['value']) ? hexdec($tx['value']) : 0,
            'gas_used' => isset($tx['gas']) ? hexdec($tx['gas']) : 0,
            'block_number' => isset($tx['blockNumber']) ? hexdec($tx['blockNumber']) : 0,
            'balance_before' => null,
            'balance_after' => null,
        ];

        // 2. Fetch transaction receipt for gas usage and block details
        $receipt_data = $this->_fetchFromEtherscan($api_url, $api_key, 'eth_getTransactionReceipt', ['txhash' => $tx_hash]);
        if ($receipt_data && isset($receipt_data['result']['gasUsed'])) {
            $enriched['gas_used'] = hexdec($receipt_data['result']['gasUsed']);
        }

        // 3. Fetch account balance at block height (before transaction)
        if (!empty($user_wallet_address) && isset($tx['blockNumber'])) {
            $block_before = hexdec($tx['blockNumber']) - 1;
            $balance_before_data = $this->_fetchFromEtherscan(
                $api_url,
                $api_key,
                'eth_getBalance',
                [
                    'address' => strtolower($user_wallet_address),
                    'tag' => '0x' . dechex($block_before)
                ]
            );
            if ($balance_before_data && isset($balance_before_data['result'])) {
                // Convert from wei to USDT (assuming 18 decimals)
                $enriched['balance_before'] = hexdec($balance_before_data['result']) / 1e18;
            }
        }

        // 4. Fetch account balance at block height (after transaction)
        if (!empty($user_wallet_address) && isset($tx['blockNumber'])) {
            $balance_after_data = $this->_fetchFromEtherscan(
                $api_url,
                $api_key,
                'eth_getBalance',
                [
                    'address' => strtolower($user_wallet_address),
                    'tag' => '0x' . dechex(hexdec($tx['blockNumber']))
                ]
            );
            if ($balance_after_data && isset($balance_after_data['result'])) {
                // Convert from wei to USDT (assuming 18 decimals)
                $enriched['balance_after'] = hexdec($balance_after_data['result']) / 1e18;
            }
        }

        return $enriched;
    }

    /**
     * Helper: Make Etherscan API call
     */
    private function _fetchFromEtherscan($api_url, $api_key, $action, $params = [])
    {
        $query_params = array_merge($params, [
            'module'  => 'proxy',
            'action'  => $action,
            'apikey'  => $api_key,
        ]);

        $url = $api_url . '?' . http_build_query($query_params);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200 || !$response) {
            log_message('error', "Etherscan API error (action={$action}): HTTP {$http_code}");
            return false;
        }

        $data = json_decode($response, true);
        if (!isset($data['result'])) {
            log_message('error', "Etherscan API: No result for action={$action}");
            return false;
        }

        return $data;
    }

    /**
     * Update transaction with fetched Etherscan data
     * Called when balance mismatch is detected
     */
    public function updateTransactionFromEtherscan($tx_hash, $user_wallet_address = '')
    {
        $enriched = $this->enrichTransactionFromEtherscan($tx_hash, $user_wallet_address);
        if (!$enriched) return false;

        // Update onchain_transactions with fetched from/to addresses and balance snapshots
        $update_data = [
            'from_address' => $enriched['from'],
            'to_address'   => $enriched['to'],
            'value'        => $enriched['value'],
            'updated_at'   => date('Y-m-d H:i:s'),
        ];

        // Add balance snapshots if available
        if (!is_null($enriched['balance_before'])) {
            $update_data['balance_before'] = $enriched['balance_before'];
        }
        if (!is_null($enriched['balance_after'])) {
            $update_data['balance_after'] = $enriched['balance_after'];
        }

        $updated = $this->db->where('tx_hash', $tx_hash)->update('onchain_transactions', $update_data);

        if ($updated > 0) {
            log_message('info', "Enriched TX {$tx_hash}: from={$enriched['from']}, to={$enriched['to']}, before={$enriched['balance_before']}, after={$enriched['balance_after']}");
        }

        return $updated > 0;
    }

    /**
     * Detect balance mismatch and enrich transaction data from Etherscan
     * Only called when balance changes to minimize API calls
     * Fetches complete transaction data including balance_before and balance_after
     */
    public function reconcileWithEtherscan($user_id, $before_balance, $after_balance)
    {
        $user_id = (int)$user_id;

        // No mismatch, no need to call expensive API
        if (bccomp((string)$before_balance, (string)$after_balance, 8) === 0) {
            return ['mismatch' => false, 'updated' => []];
        }

        $user_wallet = $this->walletRow($user_id);
        if (!$user_wallet || empty($user_wallet['wallet_address'])) {
            return ['mismatch' => true, 'error' => 'User has no custodial address'];
        }

        $wallet_addr = strtolower($user_wallet['wallet_address']);

        // Find recent transactions with missing data (from/to addresses OR balance snapshots)
        $incomplete = $this->db
            ->from('onchain_transactions')
            ->where('status', 'confirmed')
            ->group_start()
            ->where('to_address', $wallet_addr)
            ->or_where('from_address', $wallet_addr)
            ->group_end()
            ->group_start()  // Need to update if ANY of these are missing
            ->where('(from_address IS NULL OR from_address = "")', null, false)
            ->or_where('(to_address IS NULL OR to_address = "")', null, false)
            ->or_where('(balance_before IS NULL)', null, false)
            ->or_where('(balance_after IS NULL)', null, false)
            ->group_end()
            ->order_by('created_at', 'DESC')
            ->limit(10)
            ->get()
            ->result_array();

        if (empty($incomplete)) {
            return ['mismatch' => true, 'info' => 'No incomplete transactions found'];
        }

        $updated = [];
        $failed = [];

        log_message('info', "Balance mismatch detected for user {$user_id}: {$before_balance} vs {$after_balance}. Enriching " . count($incomplete) . " transactions from Etherscan");

        foreach ($incomplete as $tx) {
            if ($this->updateTransactionFromEtherscan($tx['tx_hash'], $wallet_addr)) {
                $updated[] = $tx['tx_hash'];
            } else {
                $failed[] = $tx['tx_hash'];
            }
            // Small delay to avoid rate limiting
            usleep(100000); // 0.1s
        }

        return [
            'mismatch' => true,
            'updated'  => $updated,
            'failed'   => $failed,
            'count'    => count($updated),
            'message' => count($updated) . ' enriched, ' . count($failed) . ' failed',
        ];
    }

    /**
     * ✅ NEW: Fetch wallet history from on-chain transactions ONLY
     * Uses tx_type field from onchain_transactions table
     * Type-wise filter: deposit, transfer, bonus, earn, etc.
     */
    public function getOnchainTransactions($user_id, $filters = [], $page = 1, $per_page = 20)
    {
        $user_id = (int)$user_id;
        $page = max(1, (int)$page);
        $per_page = max(1, min((int)$per_page, 100));
        $offset = ($page - 1) * $per_page;

        // Get user's custodial wallet address
        $user_wallet = $this->walletRow($user_id);
        if (!$user_wallet || empty($user_wallet['wallet_address'])) {
            return [
                'rows' => [],
                'counts' => ['ALL' => 0, 'INCOMING' => 0, 'OUTGOING' => 0],
                'paging' => ['page' => 1, 'pages' => 0, 'total' => 0],
            ];
        }

        $wallet_addr = strtolower($user_wallet['wallet_address']);

        // Build total count query
        $this->db->from('onchain_transactions');
        $this->db->where('status', 'confirmed');
        $this->db->group_start();
        $this->db->where('to_address', $wallet_addr);
        $this->db->or_where('from_address', $wallet_addr);
        $this->db->group_end();
        $total = $this->db->count_all_results();
        $pages = ceil($total / $per_page);

        // Get paginated results
        $query = $this->db
            ->from('onchain_transactions')
            ->where('status', 'confirmed')
            ->group_start()
            ->where('to_address', $wallet_addr)
            ->or_where('from_address', $wallet_addr)
            ->group_end()
            ->order_by('block_number', 'DESC')
            ->order_by('created_at', 'DESC')
            ->limit($per_page, $offset);

        $rows = $query->get()->result_array();

        // Transform rows for display
        $transactions = [];
        foreach ($rows as $tx) {
            $is_incoming = strtolower($tx['to_address'] ?? '') === $wallet_addr;
            $tx_type = strtoupper($tx['tx_type'] ?? 'transfer');

            $transactions[] = [
                'id' => $tx['id'] ?? null,
                'tx_hash' => $tx['tx_hash'] ?? '',
                'type' => $is_incoming ? 'CREDIT' : 'DEBIT',
                'flow' => $is_incoming ? 'CREDIT' : 'DEBIT',
                'title' => ucfirst($tx['tx_type'] ?? 'Transfer'),
                'amount' => $tx['value'] ?? 0,
                'status' => 'SUCCESS',
                'from_address' => $tx['from_address'] ?? '',
                'to_address' => $tx['to_address'] ?? '',
                'block_number' => $tx['block_number'] ?? 0,
                'confirmation_count' => $tx['confirmation_count'] ?? 0,
                'created_at' => $tx['created_at'] ?? '',
                'network' => $tx['network'] ?? 'bsc',
                'tx_type' => $tx_type,
            ];
        }

        // Count by direction
        $counts = $this->db
            ->select('
                COUNT(*) as all_count,
                SUM(CASE WHEN to_address = "'.$wallet_addr.'" THEN 1 ELSE 0 END) as incoming,
                SUM(CASE WHEN from_address = "'.$wallet_addr.'" THEN 1 ELSE 0 END) as outgoing
            ')
            ->from('onchain_transactions')
            ->where('status', 'confirmed')
            ->group_start()
            ->where('to_address', $wallet_addr)
            ->or_where('from_address', $wallet_addr)
            ->group_end()
            ->get()
            ->row_array();

        return [
            'rows' => $transactions,
            'counts' => [
                'ALL' => (int)($counts['all_count'] ?? 0),
                'INCOMING' => (int)($counts['incoming'] ?? 0),
                'OUTGOING' => (int)($counts['outgoing'] ?? 0),
            ],
            'paging' => [
                'page' => $page,
                'pages' => $pages,
                'total' => $total,
            ],
        ];
    }
}
