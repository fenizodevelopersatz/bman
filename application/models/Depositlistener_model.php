<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Depositlistener_model — automatic USDT deposit detection (the "wallet monitor").
 * -------------------------------------------------------------------
 * Reads the blockchain (read-only, NO private key) and detects incoming USDT
 * (BEP-20) transfers to our users' custodial addresses, then credits their
 * internal Exchange wallet once the transfer has enough confirmations.
 *
 * HOW IT LISTENS (see docs/8_WALLET_DEPOSIT_WITHDRAW.md for the deep dive):
 *   1. Every BEP-20 transfer emits a `Transfer(address from, address to,
 *      uint256 value)` event. Its topic0 (event signature hash) is constant:
 *        keccak256("Transfer(address,address,uint256)")
 *        = 0xddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef
 *   2. We call JSON-RPC `eth_getLogs` on the USDT contract, filtering
 *      topics = [Transfer, null (any sender), <our user addresses>]. topic[2]
 *      accepts an ARRAY of addresses → one call matches ALL our users.
 *   3. Each returned log gives tx_hash, block, the recipient (topic2) and the
 *      amount (data / 10^decimals). We store it in wallet_deposits (UNIQUE
 *      tx_hash+log_index → never double-recorded).
 *   4. confirmations = currentBlock − log.block. When >= the configured
 *      minimum_confirmations, we credit the user's Exchange wallet via the
 *      ledger (USDT→BMAN at the active rate) — exactly once (unique tx_hash in
 *      wallet_ledger). No private key is used anywhere here.
 *
 * All chain values (RPC, USDT contract, decimals, min confirmations, rate)
 * come from the active Master → Token Settings row.
 */
class Depositlistener_model extends CI_Model
{
    /** keccak256("Transfer(address,address,uint256)") */
    const TRANSFER_TOPIC = '0xddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef';
    /** cap per scan to respect public-RPC eth_getLogs range limits */
    const MAX_BLOCK_SPAN = 2000;

    private function rpc($url, $method, $params)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['jsonrpc'=>'2.0','id'=>1,'method'=>$method,'params'=>$params]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        if ($raw === false) throw new RuntimeException('RPC transport error: '.$err);
        $j = json_decode($raw, true);
        if (isset($j['error'])) throw new RuntimeException('RPC error: '.($j['error']['message'] ?? json_encode($j['error'])));
        return $j['result'] ?? null;
    }

    private function httpGetJson($url, $timeout = 25)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        if ($raw === false) throw new RuntimeException('HTTP transport error: '.$err);
        $j = json_decode($raw, true);
        if (!is_array($j)) throw new RuntimeException('Invalid JSON response from explorer API');
        return $j;
    }

    private function hexdec_str($hex)
    {
        $hex = ltrim((string)$hex, '0x');
        return $hex === '' ? '0' : gmp_strval(gmp_init($hex, 16), 10);
    }
    private function topicToAddress($topic)
    {
        // 32-byte topic → last 20 bytes, checksummed lower is fine for matching
        return '0x'.substr(ltrim($topic, '0x'), 24);
    }
    private function pad32addr($addr)
    {
        return '0x'.str_pad(strtolower(preg_replace('/^0x/', '', $addr)), 64, '0', STR_PAD_LEFT);
    }

    private function scanState($network, $token = 'USDT')
    {
        $row = $this->db->get_where('wallet_scan_state', ['network'=>$network,'token'=>$token])->row_array();
        return $row ?: null;
    }
    private function setScanState($network, $token, $block)
    {
        $exists = $this->scanState($network, $token);
        if ($exists) {
            $this->db->where('id', $exists['id'])->update('wallet_scan_state', ['last_block'=>$block]);
        } else {
            $this->db->insert('wallet_scan_state', ['network'=>$network,'token'=>$token,'last_block'=>$block]);
        }
    }

    /**
     * One scan pass. Returns a summary array. Safe to call repeatedly (cron):
     * detection and crediting are both idempotent via unique constraints.
     * Provider comes from Token Settings `deposit_scan_mode`:
     *   - 'bscscan' (default): BscScan/Etherscan token-transfer API (free key;
     *      works on any host — public dataseed RPCs block eth_getLogs).
     *   - 'rpc': eth_getLogs on a log-capable node (archive / paid RPC).
     *
     * @param int|null $only_user  scan just one user's address (on-demand button)
     */
    public function scan($only_user = null)
    {
        $this->load->model('Tokenmaster_model', 'tokens');
        $this->load->model('Walletledger_model', 'ledger');

        $cfg = $this->tokens->activeSettings();
        if (!$cfg) return ['ok'=>false,'message'=>'No active Token Settings.'];
        if (empty($cfg['usdt_contract'])) return ['ok'=>false,'message'=>'USDT contract not set in Token Settings.'];

        // our custodial addresses (optionally one user)
        $this->db->select('user_id, wallet_address')->from('user_wallet');
        if ($only_user) $this->db->where('user_id', (int)$only_user);
        $wallets = $this->db->get()->result_array();
        if (!$wallets) return ['ok'=>true,'detected'=>0,'credited'=>0,'message'=>'No addresses to scan.'];

        $mode = $cfg['deposit_scan_mode'] ?? 'bscscan';
        
        try {
            if ($mode === 'rpc') {
                $current = (int)hexdec($this->rpc($cfg['rpc_url'], 'eth_blockNumber', []));
                $detected = $this->detectViaRpc($cfg, $wallets, $current);
            } else {
                $detected = $this->detectViaBscscan($cfg, $wallets);                
                $current = $this->currentBlockForConfirmations($cfg);
            }
        } catch (Exception $e) {
            return ['ok'=>false, 'detected'=>0, 'credited'=>0, 'message'=>$e->getMessage()];
        }

        $credited = $this->creditConfirmed($current, (int)$cfg['minimum_confirmations'], $cfg, $only_user);

        // ✓ NEW: Enrich detected deposits with full Etherscan data (from, to, gas, timestamp, etc.)
        $enriched = 0;
        try {
            $this->load->model('Custodialwallet_model', 'cw');
            $enrichment = $this->cw->enrichAllRecentDeposits($only_user, 100);
            $enriched = $enrichment['enriched'] ?? 0;
            log_message('info', "[Depositlistener] Enriched {$enriched} transaction(s) with Etherscan data");
        } catch (Exception $e) {
            log_message('error', '[Depositlistener] Enrichment failed: ' . $e->getMessage());
        }

        return ['ok'=>true, 'detected'=>$detected, 'credited'=>$credited, 'enriched'=>$enriched,
                'message'=>"Detected $detected new deposit(s), credited $credited, enriched $enriched."];
    }

    /**
     * On-demand BMAN token deposit scan (mirrors scan() but for the platform's
     * own BMAN contract). Unlike USDT — which only ever lands in the USDT
     * wallet, with conversion happening exclusively at Staking Package
     * Purchase time — BMAN sent in from an external wallet IS the Exchange
     * wallet's asset, so a confirmed BMAN deposit credits Exchange directly.
     *
     * On-demand only: called from the "Check On-chain Balance" button
     * (Profile::wallet_check). No cron path.
     */
    public function scanBman($only_user = null)
    {
        $this->load->model('Tokenmaster_model', 'tokens');
        $this->load->model('Walletledger_model', 'ledger');

        $cfg = $this->tokens->activeSettings();
        if (!$cfg) return ['ok'=>false,'message'=>'No active Token Settings.'];
        if (empty($cfg['bman_contract'])) return ['ok'=>false,'message'=>'BMAN contract not set in Token Settings.'];
        if (empty($cfg['bman_enabled'])) return ['ok'=>false,'message'=>'BMAN deposits are currently disabled.'];

        $this->db->select('user_id, wallet_address')->from('user_wallet');
        if ($only_user) $this->db->where('user_id', (int)$only_user);
        $wallets = $this->db->get()->result_array();
        if (!$wallets) return ['ok'=>true,'detected'=>0,'credited'=>0,'skipped'=>0,'message'=>'No addresses to scan.'];

        $mode = $cfg['deposit_scan_mode'] ?? 'bscscan';
        $decimals = (int)$cfg['bman_decimals'];

        try {
            if ($mode === 'rpc') {
                $current = (int)hexdec($this->rpc($cfg['rpc_url'], 'eth_blockNumber', []));
                $detected = $this->detectViaRpc($cfg, $wallets, $current, 'BMAN', $cfg['bman_contract'], $decimals);
            } else {
                $detected = $this->detectViaBscscan($cfg, $wallets, 'BMAN', $cfg['bman_contract'], $decimals);
                $current = $this->currentBlockForConfirmations($cfg);
            }
        } catch (Exception $e) {
            return ['ok'=>false, 'detected'=>0, 'credited'=>0, 'skipped'=>0, 'message'=>$e->getMessage()];
        }

        $result = $this->creditConfirmedBman($current, (int)$cfg['minimum_confirmations'], $cfg, $only_user);

        $msg = "Detected $detected new BMAN deposit(s), credited {$result['credited']}";
        if ($result['skipped'] > 0) $msg .= ", {$result['skipped']} outside the configured transfer limits";
        $msg .= '.';

        return ['ok'=>true, 'detected'=>$detected, 'credited'=>$result['credited'],
                'skipped'=>$result['skipped'], 'message'=>$msg];
    }

    private function currentBlockForConfirmations($cfg)
    {
        $rpcUrl = trim((string)($cfg['rpc_url'] ?? ''));
        if ($rpcUrl !== '') {
            try {
                return (int)hexdec($this->rpc($rpcUrl, 'eth_blockNumber', []));
            } catch (Exception $e) {
                log_message('error', '[Depositlistener] RPC block head failed: '.$e->getMessage());
            }
        }

        $key = trim((string)($cfg['explorer_api_key'] ?? ''));
        $api = trim((string)($cfg['explorer_api_url'] ?? ''));
        if ($key !== '' && $api !== '') {
            $q = http_build_query([
                'chainid' => (int)($cfg['chain_id'] ?? 56),
                'module' => 'proxy',
                'action' => 'eth_blockNumber',
                'apikey' => $key,
            ]);
            $j = $this->httpGetJson($api.'?'.$q);
            if (isset($j['result'])) {
                return (int)hexdec((string)$j['result']);
            }
        }

        throw new RuntimeException('Unable to read current block height from RPC or explorer API.');
    }

    /** Insert a detected deposit if new (unique tx_hash+log_index). */
    private function recordDeposit($uid, $address, $tx, $logIndex, $block, $amount, $network, $token = 'USDT', $fromAddress = null)
    {
        if (bccomp($amount, '0', 8) <= 0) return 0;
        $exists = $this->db->where(['tx_hash'=>$tx,'log_index'=>(int)$logIndex])
                           ->count_all_results('wallet_deposits');
        if ($exists > 0) return 0;
        $this->db->insert('wallet_deposits', [
            'user_id'=>$uid, 'wallet_address'=>$address, 'from_address'=>$fromAddress, 'tx_hash'=>$tx, 'log_index'=>(int)$logIndex,
            'block_number'=>$block, 'token'=>$token,
            'amount_usdt'=>($token === 'USDT' ? $amount : 0),
            'amount_bman'=>($token === 'BMAN' ? $amount : 0),
            'network'=>$network, 'status'=>'pending',
        ]);
        return 1;
    }

    /**
     * BscScan / Etherscan-v2 provider: tokentx per address (free API key).
     * Defaults to USDT for backward compatibility; pass $tokenSymbol/$contract/
     * $decimals to scan a different BEP-20 token (e.g. BMAN) against the same
     * custodial addresses.
     */
    private function detectViaBscscan($cfg, $wallets, $tokenSymbol = 'USDT', $contract = null, $decimals = null)
    {
        $key = trim((string)($cfg['explorer_api_key'] ?? ''));
        if ($key === '') {
            throw new RuntimeException('Deposit auto-detect needs a BscScan/Etherscan API key — set it in Master → Token Settings (or switch scan mode to a log-capable RPC).');
        }
        $api = $cfg['explorer_api_url'] ?: 'https://api.etherscan.io/v2/api';
        $contract = $contract ?: $cfg['usdt_contract'];
        $chain = (int)$cfg['chain_id'];
        $decimals = $decimals ?? (int)$cfg['usdt_decimals'];
        $detected = 0;

        foreach ($wallets as $w) {
            $q = http_build_query([
                'chainid' => $chain, 'module' => 'account', 'action' => 'tokentx',
                'contractaddress' => $contract, 'address' => $w['wallet_address'],
                'page' => 1, 'offset' => 50, 'sort' => 'desc', 'apikey' => $key,
            ]);
            $ch = curl_init($api.'?'.$q);
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>25]);
            $raw = curl_exec($ch); curl_close($ch);
            $j = json_decode($raw, true);
            if (!isset($j['result']) || !is_array($j['result'])) continue; // no txns / rate-limited

            foreach ($j['result'] as $t) {
                // only incoming transfers TO our address
                if (strtolower($t['to'] ?? '') !== strtolower($w['wallet_address'])) continue;
                $amount = bcdiv((string)$t['value'], bcpow('10', (string)$decimals, 0), 8);

                $detected += $this->recordDeposit(
                    (int)$w['user_id'], $w['wallet_address'], $t['hash'],
                    isset($t['transactionIndex']) ? $t['transactionIndex'] : 0,
                    isset($t['blockNumber']) ? (int)$t['blockNumber'] : null,
                    $amount, $cfg['network'], $tokenSymbol,
                    isset($t['from']) ? strtolower($t['from']) : null
                );
            }
        }
        return $detected;
    }

    /**
     * RPC provider: eth_getLogs on a log-capable node. Defaults to USDT; pass
     * $tokenSymbol/$contract/$decimals to scan a different token contract.
     */
    private function detectViaRpc($cfg, $wallets, $current, $tokenSymbol = 'USDT', $contract = null, $decimals = null)
    {
        $contract = $contract ?: $cfg['usdt_contract'];
        $decimals = $decimals ?? (int)$cfg['usdt_decimals'];

        $state = $this->scanState($cfg['network'], $tokenSymbol);
        $from = $state ? ((int)$state['last_block'] + 1) : max(0, $current - self::MAX_BLOCK_SPAN);
        if ($from > $current) $from = $current;
        if ($current - $from > self::MAX_BLOCK_SPAN) $from = $current - self::MAX_BLOCK_SPAN;

        $topicAddrs = array_map(function ($w) { return $this->pad32addr($w['wallet_address']); }, $wallets);
        $addrByLower = [];
        foreach ($wallets as $w) $addrByLower[strtolower($w['wallet_address'])] = (int)$w['user_id'];

        $logs = $this->rpc($cfg['rpc_url'], 'eth_getLogs', [[
            'fromBlock' => '0x'.dechex($from), 'toBlock' => '0x'.dechex($current),
            'address' => $contract,
            'topics' => [self::TRANSFER_TOPIC, null, $topicAddrs],
        ]]);
        $logs = is_array($logs) ? $logs : [];

        $detected = 0;
        foreach ($logs as $log) {
            $to = strtolower($this->topicToAddress($log['topics'][2]));
            if (!isset($addrByLower[$to])) continue;
            $amount = bcdiv($this->hexdec_str($log['data']), bcpow('10', (string)$decimals, 0), 8);
            $detected += $this->recordDeposit(
                $addrByLower[$to], $this->topicToAddress($log['topics'][2]),
                $log['transactionHash'], isset($log['logIndex']) ? (int)hexdec($log['logIndex']) : 0,
                isset($log['blockNumber']) ? (int)hexdec($log['blockNumber']) : null,
                $amount, $cfg['network'], $tokenSymbol,
                isset($log['topics'][1]) ? $this->topicToAddress($log['topics'][1]) : null
            );
        }
        $this->setScanState($cfg['network'], $tokenSymbol, $current);
        return $detected;
    }

    /**
     * Move deposits through confirming → confirmed → credited.
     *
     * BUSINESS RULE (2026-07): a confirmed deposit credits the user's **USDT
     * Wallet ONLY**. There is NO automatic USDT→BMAN conversion, NO Exchange
     * wallet credit, and NO staking / ROI / bonus / binary / rank side effects.
     * USDT→BMAN conversion happens exclusively during a Staking Package Purchase.
     *
     * The ledger row (wallet_ledger) is the wallet_transaction; wallet_deposits
     * is the deposit_history. Crediting is idempotent (unique tx_hash,wallet).
     */
    private function creditConfirmed($current, $minConf, $cfg, $only_user = null)
    {
        $this->load->model('Walletledger_model', 'ledger');

        $this->db->where_in('status', ['pending','confirming','confirmed'])
                 ->where('token', 'USDT');
        if ($only_user) $this->db->where('user_id', (int)$only_user);
        $rows = $this->db->get('wallet_deposits')->result_array();

        $credited = 0;
        foreach ($rows as $d) {
            $conf = $d['block_number'] !== null ? max(0, $current - (int)$d['block_number']) : 0;
            $status = $conf >= $minConf ? 'confirmed' : 'confirming';
            $this->db->where('id', $d['id'])->update('wallet_deposits',
                ['confirmations' => $conf, 'status' => $status]);
            if ($conf < $minConf) continue;

            // Credit the USDT wallet ONLY (records total_deposit_usdt in the ledger).
            list($ok1) = $this->ledger->credit($d['user_id'], 'usdt', $d['amount_usdt'], 'deposit', [
                'tx_hash' => $d['tx_hash'], 'reference_id' => (string)$d['id'],
                'description' => 'USDT deposit '.$d['amount_usdt'].' (BEP-20)',
            ]);
            if (!$ok1) continue;

            // amount_bman stays 0 — no conversion at deposit time.
            $this->db->where('id', $d['id'])->update('wallet_deposits', [
                'amount_bman' => 0, 'status' => 'credited', 'credited_at' => date('Y-m-d H:i:s'),
            ]);

            // The generic ledger→onchain_transactions hook (_captureOnchain)
            // has no from/to address to work with, so the mirror row it just
            // inserted has both as NULL — which silently excludes it from the
            // Wallet History table (that view filters by address match) unless
            // the separate, best-effort Etherscan enrichment step happens to
            // patch it later. Backfill from what we captured at detection time,
            // same as creditConfirmedBman() already does below.
            if (!empty($d['from_address'])) {
                $this->db->where('tx_hash', $d['tx_hash'])
                    ->where('wallet_type', 'usdt')
                    ->update('onchain_transactions', [
                        'from_address' => $d['from_address'],
                        'to_address' => $d['wallet_address'],
                    ]);
            }

            $credited++;
        }
        return $credited;
    }

    /**
     * Move BMAN deposits through confirming → confirmed → credited, crediting
     * the Exchange wallet. Idempotent the same way as USDT: Walletledger_model
     * enforces a UNIQUE(tx_hash, wallet_type) constraint, so the same on-chain
     * transfer can never be credited twice even under a race.
     *
     * bman_min_transfer / bman_max_transfer (Token Settings) are enforced only
     * when configured above 0 — a value of 0 means that bound is disabled,
     * matching how every other *_transfer/_deposit/_withdrawal threshold in
     * this table behaves (there is no existing convention anywhere in this
     * codebase where 0 means "block everything").
     */
    private function creditConfirmedBman($current, $minConf, $cfg, $only_user = null)
    {
        $this->load->model('Walletledger_model', 'ledger');

        $this->db->where_in('status', ['pending','confirming','confirmed'])
                 ->where('token', 'BMAN');
        if ($only_user) $this->db->where('user_id', (int)$only_user);
        $rows = $this->db->get('wallet_deposits')->result_array();

        $min = (float)($cfg['bman_min_transfer'] ?? 0);
        $max = (float)($cfg['bman_max_transfer'] ?? 0);

        $credited = 0;
        $skipped = 0;
        foreach ($rows as $d) {
            $conf = $d['block_number'] !== null ? max(0, $current - (int)$d['block_number']) : 0;
            $status = $conf >= $minConf ? 'confirmed' : 'confirming';
            $this->db->where('id', $d['id'])->update('wallet_deposits',
                ['confirmations' => $conf, 'status' => $status]);
            if ($conf < $minConf) continue;

            // Every internal payout (binary matching, ROI, wallet transfer
            // settlement, admin transfer — any of them) is broadcast from the
            // ONE configured treasury wallet (Tokenmaster_model::activeSettings()
            // — "the single active configuration every engine must read"). A
            // transfer arriving FROM that same address is therefore never a
            // genuine external deposit: it's money this platform already sent
            // and already credited internally through whichever subsystem
            // authorized the send. Crediting it again here would double-count
            // it into Exchange on top of its real destination wallet.
            $treasury = strtolower((string) ($cfg['treasury_wallet'] ?? ''));
            $sender   = strtolower((string) ($d['from_address'] ?? ''));
            if ($treasury !== '' && $sender === $treasury) {
                log_message('info', "[Depositlistener] BMAN deposit id={$d['id']} tx_hash={$d['tx_hash']} sender is the treasury wallet — internal payout, not an external deposit. Not credited.");
                $this->db->where('id', $d['id'])->update('wallet_deposits', ['status' => 'failed']);
                $skipped++;
                continue;
            }

            $amount = (float) $d['amount_bman'];
            if (($min > 0 && $amount < $min) || ($max > 0 && $amount > $max)) {
                log_message('info', "[Depositlistener] BMAN deposit id={$d['id']} amount={$amount} outside configured limits (min={$min}, max={$max}) — not credited.");
                $this->db->where('id', $d['id'])->update('wallet_deposits', ['status' => 'failed']);
                $skipped++;
                continue;
            }

            list($ok1) = $this->ledger->credit($d['user_id'], 'exchange', $d['amount_bman'], 'deposit', [
                'tx_hash' => $d['tx_hash'], 'reference_id' => (string)$d['id'],
                'description' => 'BMAN deposit '.$d['amount_bman'].' (BEP-20)',
            ]);
            if (!$ok1) continue;

            $this->db->where('id', $d['id'])->update('wallet_deposits', [
                'status' => 'credited', 'credited_at' => date('Y-m-d H:i:s'),
            ]);

            // The generic ledger→onchain_transactions hook (_captureOnchain)
            // has no from/to address to work with, so the mirror row it just
            // inserted has both as NULL — which silently excludes it from the
            // Wallet History table (that view filters by address match).
            // Backfill from what we captured at detection time.
            if (!empty($d['from_address'])) {
                $this->db->where('tx_hash', $d['tx_hash'])
                    ->where('wallet_type', 'exchange')
                    ->update('onchain_transactions', [
                        'from_address' => $d['from_address'],
                        'to_address' => $d['wallet_address'],
                    ]);
            }

            $credited++;
        }
        return ['credited' => $credited, 'skipped' => $skipped];
    }

    /* ------------------------------ reads ------------------------------ */

    public function deposits($user_id = 0, $limit = 100)
    {
        if ($user_id) $this->db->where('user_id', (int)$user_id);
        return $this->db->order_by('id', 'DESC')->limit((int)$limit)
                        ->get('wallet_deposits')->result_array();
    }
}
