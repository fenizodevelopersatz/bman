<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * StakingPurchasecron — automatic USDT ↔ BMAN staking purchase processing.
 *
 * The ONLY on-chain movements are:
 *   USDT  user  ── on-chain ─▶ admin   (the buyer pays)
 *   BMAN  admin ── on-chain ─▶ user    (principal + optional 25% bonus)
 * Everything else (splitting BMAN into exchange/earning/staking/bonus) is an
 * INTERNAL ledger operation on the one custodial address — no extra chain tx.
 *
 * 4 independent cron steps (each: 0 = pending, 1 = completed):
 *   1. gas_cron_status   — Gas fee BNB  admin → user (so the user can pay USDT gas)
 *   2. usdt_cron_status  — USDT payment user → admin        [on-chain]
 *   3. bonus_cron_status — 25% bonus BMAN admin → user      [on-chain] → credit bonus wallet
 *   4. bman_cron_status  — principal BMAN admin → user      [on-chain]
 *                          then split INTERNALLY per coin_distribution_option into
 *                          exchange / earning / staking / bonus wallets.
 *
 * Coin distribution options (1-7) — INTERNAL split of the principal only:
 *   1: 100% exchange
 *   2:  90% exchange, 10% bonus
 *   3:  80% exchange, 10% earning, 10% bonus
 *   4:  80% exchange, 10% earning, 10% staking
 *   5:  90% exchange, 10% earning
 *   6:  90% exchange, 10% staking
 *   7:  70% exchange, 10% earning, 10% staking, 10% bonus
 *
 * Sequencing: usdt waits for gas=1; bonus + bman wait for usdt=1. Each step only
 * completes after `minimum_confirmations` on-chain confirmations. Wallet credits
 * go through Walletledger_model (idempotent on tx_hash+wallet_type) so re-running
 * the cron never double-credits. Failure detail is stored per step in
 * *_cron_status_message for the Cron Lab UI.
 *
 * Run it hourly:
 *   CLI  :  php index.php stakingpurchasecron run
 *   HTTP :  /staking-purchase-cron?token=YOUR_CRON_TOKEN
 */
class StakingPurchasecron extends CI_Controller
{
    private $log_prefix = '[STAKING_PURCHASE_CRON]';
    private $_cfg_cache = null;

    public function run()
    {
        // CLI always allowed; over HTTP require the cron token
        if (!is_cli()) {
            $expected = $this->config->item('cron_token');
            if (!$expected || $this->input->get('token', true) !== $expected) {
                show_404();
            }
        }

        $this->load->model('Walletledger_model', 'L');

        try {
            $result = [
                'status'  => 'success',
                'message' => 'Staking purchase cron completed',
                'details' => ['steps' => $this->_processAllPendingSteps()],
                'ran_at'  => date('Y-m-d H:i:s'),
            ];
        } catch (Exception $e) {
            log_message('error', $this->log_prefix . ' ' . $e->getMessage());
            $result = ['status' => 'error', 'message' => $e->getMessage(), 'ran_at' => date('Y-m-d H:i:s')];
        }

        echo json_encode($result) . PHP_EOL;
    }

    /* =====================================================================
     * Chain / explorer helpers — same token_settings columns + Etherscan-V2
     * (multichain) format as the proven Depositlistener_model.
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

    /* =====================================================================
     * Order processing
     * ===================================================================== */

    /** Pending orders = any of the 4 cron steps still 0. Excludes fully-settled. */
    private function _getPendingOrders()
    {
        return $this->db->select(
                'id, ref, user_id, package_id, user_address, admin_address, status, ' .
                'usdt_amount, bman_amount, bonus_bman, coin_distribution_option, ' .
                'gas_cron_status, usdt_cron_status, bonus_cron_status, bman_cron_status, ' .
                'gas_tx_hash, usdt_tx_hash, bonus_tx_hash, bman_tx_hash'
            )
            ->group_start()
                ->where('gas_cron_status', 0)
                ->or_where('usdt_cron_status', 0)
                ->or_where('bonus_cron_status', 0)
                ->or_where('bman_cron_status', 0)
            ->group_end()
            ->where('status !=', 'cancelled')
            ->order_by('id', 'ASC')
            ->limit(50)
            ->get('staking_swap_orders')
            ->result_array();
    }

    private function _processAllPendingSteps()
    {
        $orders  = $this->_getPendingOrders();
        $summary = [
            'total_orders' => count($orders),
            'gas'   => ['processed' => 0, 'failed' => 0],
            'usdt'  => ['processed' => 0, 'failed' => 0],
            'bonus' => ['processed' => 0, 'failed' => 0],
            'bman'  => ['processed' => 0, 'failed' => 0],
        ];

        foreach ($orders as $order) {
            try {
                $this->_processOrderSteps($order, $summary);
            } catch (Exception $e) {
                log_message('error', $this->log_prefix . ' order ' . $order['id'] . ': ' . $e->getMessage());
            }
        }
        return $summary;
    }

    /** One order through the 4 sequential steps. In-memory state advances so a
     *  single run can carry an order from gas → usdt → bonus → bman. */
    private function _processOrderSteps(&$order, &$summary)
    {
        // 1) Gas fee (BNB admin → user)
        if ((int)$order['gas_cron_status'] === 0) {
            $this->_detectGasFee($order) ? $summary['gas']['processed']++ : $summary['gas']['failed']++;
        }

        // 2) USDT payment (user → admin) — needs gas first
        if ((int)$order['usdt_cron_status'] === 0) {
            $this->_detectUsdtPayment($order) ? $summary['usdt']['processed']++ : $summary['usdt']['failed']++;
        }

        // 3) 25% bonus BMAN (admin → user) — credit bonus wallet. Needs USDT first.
        if ((int)$order['bonus_cron_status'] === 0) {
            $this->_detectBonusBman($order) ? $summary['bonus']['processed']++ : $summary['bonus']['failed']++;
        }

        // 4) Principal BMAN (admin → user) → split internally per option. Needs USDT first.
        if ((int)$order['bman_cron_status'] === 0) {
            $this->_detectAndDistributeBman($order) ? $summary['bman']['processed']++ : $summary['bman']['failed']++;
        }

        $this->_checkAndCompleteOrder($order);
    }

    /* ------------------------------- Step 1: gas ------------------------------- */

    /** Detect BNB gas credited to the user (admin → user). Broad amount range —
     *  the exact top-up varies with gas price. Confirmations enforced. */
    private function _detectGasFee(&$order)
    {
        $option = (int)($order['coin_distribution_option'] ?? 0);
        if ($option < 1 || $option > 7) {
            return $this->_fail($order['id'], 'gas', 'Invalid coin_distribution_option: ' . $option . ' (must be 1-7)');
        }

        $user   = strtolower($order['user_address']);
        $minConf = $this->_minConfirmations();
        $head    = $this->_currentBlock();
        if ($head === 0) return $this->_fail($order['id'], 'gas', 'Cannot read current block height');

        $data = $this->_apiGet([
            'module' => 'account', 'action' => 'txlist', 'address' => $user,
            'startblock' => 0, 'endblock' => 99999999, 'page' => 1, 'offset' => 50, 'sort' => 'desc',
        ]);
        if (!isset($data['result']) || !is_array($data['result']) || empty($data['result'])) {
            return $this->_fail($order['id'], 'gas', 'No BNB transactions found for user address yet');
        }

        foreach ($data['result'] as $tx) {
            $to    = strtolower($tx['to'] ?? '');
            $value = (float)($tx['value'] ?? 0) / 1e18; // native BNB is decimal wei on txlist
            $isErr = ($tx['isError'] ?? '0') === '1';
            if ($isErr || $to !== $user || $value < 0.0001 || $value > 0.05) continue;

            $conf = $head - (int)($tx['blockNumber'] ?? 0);
            if ($conf < $minConf) return $this->_fail($order['id'], 'gas', "Gas TX pending confirmations: $conf/$minConf");

            $hash = strtolower($tx['hash'] ?? '');
            $this->_recordOnchain($order, $hash, strtolower($tx['from'] ?? ''), $to, (string)$tx['value'], 'gas_fee', null, $conf, (int)$tx['blockNumber']);
            $this->db->where('id', $order['id'])->update('staking_swap_orders', [
                'gas_tx_hash' => $hash, 'gas_cron_status' => 1, 'gas_cron_status_message' => null,
                'status' => 'pending_usdt', 'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $order['gas_cron_status'] = 1;
            $order['status'] = 'pending_usdt';
            log_message('info', $this->log_prefix . " gas CONFIRMED order {$order['id']} ($conf conf, $value BNB): $hash");
            return true;
        }
        return $this->_fail($order['id'], 'gas', 'BNB gas credit (0.0001–0.05) to user not found yet');
    }

    /* ------------------------------- Step 2: usdt ------------------------------ */

    /** Detect USDT user → admin, amount ≥ order usdt_amount. Needs gas confirmed. */
    private function _detectUsdtPayment(&$order)
    {
        if ((int)$order['gas_cron_status'] !== 1) {
            return $this->_fail($order['id'], 'usdt', 'Waiting for gas fee step first');
        }

        $cfg      = $this->_cfg();
        $contract = trim((string)($cfg['usdt_contract'] ?? ''));
        $decimals = (int)($cfg['usdt_decimals'] ?? 18);
        if ($contract === '') return $this->_fail($order['id'], 'usdt', 'USDT contract not configured');

        $user = strtolower($order['user_address']);
        $admin = strtolower($order['admin_address']);
        $expected = (float)($order['usdt_amount'] ?? 0);
        $minConf  = $this->_minConfirmations();
        $head     = $this->_currentBlock();
        if ($head === 0) return $this->_fail($order['id'], 'usdt', 'Cannot read current block height');

        $data = $this->_apiGet([
            'module' => 'account', 'action' => 'tokentx', 'contractaddress' => $contract,
            'address' => $user, 'startblock' => 0, 'endblock' => 99999999, 'page' => 1, 'offset' => 50, 'sort' => 'desc',
        ]);
        if (!isset($data['result']) || !is_array($data['result']) || empty($data['result'])) {
            return $this->_fail($order['id'], 'usdt', 'No USDT transfers found for user address yet');
        }

        foreach ($data['result'] as $tx) {
            $from = strtolower($tx['from'] ?? '');
            $to   = strtolower($tx['to'] ?? '');
            $raw  = (string)($tx['value'] ?? '0');
            $amount = (float)bcdiv($raw, bcpow('10', (string)$decimals, 0), 8);
            if ($from !== $user || $to !== $admin || $amount + 1e-8 < $expected * 0.99) continue;

            $conf = $head - (int)($tx['blockNumber'] ?? 0);
            if ($conf < $minConf) return $this->_fail($order['id'], 'usdt', "USDT TX pending confirmations: $conf/$minConf");

            $hash = strtolower($tx['hash'] ?? '');
            $this->_recordOnchain($order, $hash, $from, $to, $raw, 'deposit', 'usdt', $conf, (int)$tx['blockNumber']);
            $this->db->where('id', $order['id'])->update('staking_swap_orders', [
                'usdt_tx_hash' => $hash, 'usdt_cron_status' => 1, 'usdt_cron_status_message' => null,
                'status' => 'pending_bman', 'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $order['usdt_cron_status'] = 1;
            $order['status'] = 'pending_bman';
            log_message('info', $this->log_prefix . " usdt CONFIRMED order {$order['id']} ($conf conf, $amount USDT): $hash");
            return true;
        }
        return $this->_fail($order['id'], 'usdt', "USDT user → admin (≥$expected) not found yet");
    }

    /* ------------------------------ Step 3: bonus ------------------------------ */

    /** Detect the 25% bonus BMAN (admin → user) and credit the bonus wallet. */
    private function _detectBonusBman(&$order)
    {
        $bonus = (float)($order['bonus_bman'] ?? 0);
        if ($bonus <= 0) { // nothing to do
            $this->db->where('id', $order['id'])->update('staking_swap_orders',
                ['bonus_cron_status' => 1, 'bonus_cron_status_message' => null]);
            $order['bonus_cron_status'] = 1;
            return true;
        }
        if ((int)$order['usdt_cron_status'] !== 1) {
            return $this->_fail($order['id'], 'bonus', 'Waiting for USDT payment step first');
        }

        $tx = $this->_findAdminToUserBman($order, $bonus, 'bonus');
        if ($tx === null) return false; // message already recorded

        // credit bonus wallet internally
        $this->_credit($order, 'bonus', $bonus, $tx['hash']);
        $this->db->where('id', $order['id'])->update('staking_swap_orders', [
            'bonus_tx_hash' => $tx['hash'], 'bonus_cron_status' => 1, 'bonus_cron_status_message' => null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $order['bonus_cron_status'] = 1;
        $order['bonus_tx_hash'] = $tx['hash'];
        log_message('info', $this->log_prefix . " bonus BMAN CONFIRMED order {$order['id']} (+$bonus bonus): {$tx['hash']}");
        return true;
    }

    /* --------------------------- Step 4: principal BMAN ------------------------ */

    /** Detect the principal BMAN (admin → user) then split INTERNALLY per option. */
    private function _detectAndDistributeBman(&$order)
    {
        if ((int)$order['usdt_cron_status'] !== 1) {
            return $this->_fail($order['id'], 'bman', 'Waiting for USDT payment step first');
        }

        $principal = (float)$order['bman_amount'];
        $tx = $this->_findAdminToUserBman($order, $principal, 'bman');
        if ($tx === null) return false;

        // Record the on-chain hash first so all internal slices reference it.
        $this->db->where('id', $order['id'])->update('staking_swap_orders', [
            'bman_tx_hash' => $tx['hash'], 'bman_cron_status' => 1, 'bman_cron_status_message' => null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $order['bman_cron_status'] = 1;
        $order['bman_tx_hash'] = $tx['hash'];

        // INTERNAL split of the principal into the 5-wallet ledger (one custodial address).
        foreach (['exchange', 'earning', 'staking', 'bonus'] as $wallet) {
            $amount = $this->_walletShare($order, $wallet);
            if ($amount > 0) $this->_credit($order, $wallet, $amount, $tx['hash']);
        }

        log_message('info', $this->log_prefix . " principal BMAN CONFIRMED order {$order['id']} ($principal split opt "
            . (int)$order['coin_distribution_option'] . "): {$tx['hash']}");
        return true;
    }

    /* ------------------------------- shared bits ------------------------------- */

    /** Find an admin → user BMAN transfer of ~$want (1% tol). Returns ['hash'=>..]
     *  after confirmations, or null (recording the reason on $step). Skips the
     *  bonus tx when matching the principal and vice-versa via amount matching. */
    private function _findAdminToUserBman(&$order, $want, $step)
    {
        $cfg      = $this->_cfg();
        $contract = trim((string)($cfg['bman_contract'] ?? ''));
        $decimals = (int)($cfg['bman_decimals'] ?? 18);
        if ($contract === '') { $this->_fail($order['id'], $step, 'BMAN contract not configured'); return null; }

        $user  = strtolower($order['user_address']);
        $admin = strtolower($order['admin_address']);
        $minConf = $this->_minConfirmations();
        $head    = $this->_currentBlock();
        if ($head === 0) { $this->_fail($order['id'], $step, 'Cannot read current block height'); return null; }

        // Avoid matching the SAME tx for both bonus and principal.
        $skip = $step === 'bman' ? strtolower((string)($order['bonus_tx_hash'] ?? '')) : strtolower((string)($order['bman_tx_hash'] ?? ''));

        $data = $this->_apiGet([
            'module' => 'account', 'action' => 'tokentx', 'contractaddress' => $contract,
            'address' => $admin, 'startblock' => 0, 'endblock' => 99999999, 'page' => 1, 'offset' => 100, 'sort' => 'desc',
        ]);
        if (!isset($data['result']) || !is_array($data['result']) || empty($data['result'])) {
            $this->_fail($order['id'], $step, 'No BMAN transfers found for admin address yet'); return null;
        }

        foreach ($data['result'] as $tx) {
            $from = strtolower($tx['from'] ?? '');
            $to   = strtolower($tx['to'] ?? '');
            $hash = strtolower($tx['hash'] ?? '');
            $raw  = (string)($tx['value'] ?? '0');
            $amount = (float)bcdiv($raw, bcpow('10', (string)$decimals, 0), 8);
            if ($hash === $skip || $from !== $admin || $to !== $user) continue;
            if ($amount + 1e-8 < $want * 0.99 || $amount > $want * 1.01 + 1e-8) continue;

            $conf = $head - (int)($tx['blockNumber'] ?? 0);
            if ($conf < $minConf) { $this->_fail($order['id'], $step, "BMAN TX pending confirmations: $conf/$minConf"); return null; }

            $this->_recordOnchain($order, $hash, $from, $to, $raw, 'transfer', $step === 'bonus' ? 'bonus' : 'exchange', $conf, (int)$tx['blockNumber']);
            return ['hash' => $hash];
        }
        $this->_fail($order['id'], $step, ucfirst($step) . " BMAN admin → user (≈$want) not found yet");
        return null;
    }

    /** Percentage of the PRINCIPAL that goes to a wallet, per coin_distribution_option. */
    private function _walletShare(&$order, $wallet)
    {
        $pct = [
            1 => ['exchange' => 100, 'earning' => 0,  'staking' => 0,  'bonus' => 0],
            2 => ['exchange' => 90,  'earning' => 0,  'staking' => 0,  'bonus' => 10],
            3 => ['exchange' => 80,  'earning' => 10, 'staking' => 0,  'bonus' => 10],
            4 => ['exchange' => 80,  'earning' => 10, 'staking' => 10, 'bonus' => 0],
            5 => ['exchange' => 90,  'earning' => 10, 'staking' => 0,  'bonus' => 0],
            6 => ['exchange' => 90,  'earning' => 0,  'staking' => 10, 'bonus' => 0],
            7 => ['exchange' => 70,  'earning' => 10, 'staking' => 10, 'bonus' => 10],
        ];
        $opt = (int)($order['coin_distribution_option'] ?? 1);
        $row = $pct[$opt] ?? $pct[1];
        return (float)$order['bman_amount'] * (($row[$wallet] ?? 0) / 100);
    }

    /** Credit a wallet through the canonical ledger (idempotent on tx_hash+wallet_type). */
    private function _credit(&$order, $wallet, $amount, $tx_hash)
    {
        if ($amount <= 0) return;
        list($ok, $info) = $this->L->credit(
            (int)$order['user_id'], $wallet, $amount, 'stake_purchase',
            [
                'tx_hash'      => $tx_hash ?: null,
                'reference_id' => $order['ref'] ?? ('ORDER-' . $order['id']),
                'description'  => ucfirst($wallet) . ' allocation ' . $amount . ' BMAN (order ' . $order['id'] . ')',
            ]
        );
        if (!$ok) log_message('error', $this->log_prefix . " ledger credit FAILED $wallet user {$order['user_id']}: $info");
        else      log_message('info',  $this->log_prefix . " credited $wallet (+$amount BMAN) user {$order['user_id']} [$info]");
    }

    /** Append an audit row to onchain_transactions (history only; never drives logic). */
    private function _recordOnchain(&$order, $hash, $from, $to, $rawAmount, $txType, $walletType, $conf, $block)
    {
        // Skip if this tx_hash already recorded for this wallet_type
        if ($hash) {
            $dupe = $this->db->where(['tx_hash' => $hash, 'wallet_type' => $walletType])->count_all_results('onchain_transactions');
            if ($dupe > 0) return;
        }
        $this->db->insert('onchain_transactions', [
            'tx_hash' => $hash, 'wallet_type' => $walletType, 'tx_type' => $txType, 'status' => 'confirmed',
            'from_address' => $from, 'to_address' => $to, 'user_id' => $order['user_id'],
            'amount' => $rawAmount, 'block_number' => $block, 'confirmation_count' => $conf,
            'reference_type' => 'stake_purchase', 'reference_id' => $order['ref'] ?? ('ORDER-' . $order['id']),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /** Mark an order complete once all 4 steps are done. */
    private function _checkAndCompleteOrder(&$order)
    {
        $done = (int)$order['gas_cron_status'] === 1
             && (int)$order['usdt_cron_status'] === 1
             && (int)$order['bonus_cron_status'] === 1
             && (int)$order['bman_cron_status'] === 1;
        if (!$done || $order['status'] === 'swap_completed') return;

        $this->db->insert('user_stakes', [
            'user_id'      => $order['user_id'],
            'package_id'   => $order['package_id'] ?? 0,
            'bman_amount'  => (float)$order['bman_amount'],
            'bonus_bman'   => (float)($order['bonus_bman'] ?? 0),
            'status'       => 'active',
            'activated_at' => date('Y-m-d H:i:s'),
            'created_at'   => date('Y-m-d H:i:s'),
        ]);
        $this->db->where('id', $order['id'])->update('staking_swap_orders', [
            'status' => 'swap_completed', 'cron_status' => 'completed', 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $order['status'] = 'swap_completed';
        log_message('info', $this->log_prefix . " order {$order['id']} COMPLETED — stake activated.");
    }

    /** Record a per-step failure/waiting message and return false. */
    private function _fail($orderId, $step, $message)
    {
        $this->db->where('id', $orderId)->update('staking_swap_orders',
            [$step . '_cron_status_message' => substr($message, 0, 500)]);
        return false;
    }
}
