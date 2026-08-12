<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * BmanWithdrawCollectCron — automated on-chain BMAN collection for withdrawal
 * requests, ahead of admin approval.
 *
 * Per request, two legs, in strict sequence, using the treasury + user
 * custodial keys:
 *
 *   1. gas     : treasury -> user      (BNB)   — fund the user's custodial
 *                wallet with just enough gas for the collect leg below.
 *   2. collect : user -> treasury_wallet (BMAN) — signed with the user's own
 *                custodial key, moving exactly request_amount BMAN into the
 *                same wallet admin already manually pays USDT out of.
 *
 * Each leg: if not yet sent -> broadcast + store its tx_hash; else -> verify
 * that hash has minimum_confirmations blocks (and did not revert), then mark
 * the step done. Status stays 'processing' the whole time the cron owns the
 * request (see Bmanwithdraw_model::claim_for_collection()). Once collect
 * confirms, the pending lock on the user's balance is converted into a real
 * debit (Bmanwithdraw_model::confirm_collected()) and the request moves to
 * 'pending' — admin then manually approves (pays USDT, unchanged from
 * before this cron existed, and closes the request out in one step — see
 * Bmanwithdraw_model::approve_and_complete()) or rejects (BMAN is credited
 * back — see Bmanwithdraw_model::reject()).
 *
 * This does NOT send the USDT payout. That leg stays exactly as it was:
 * a Super Admin reveals the treasury key and sends it manually, then pastes
 * the tx_hash into "Complete". Only the BMAN COLLECTION leg is automated
 * here — see docs/2026-08-12_bman_withdraw_collect_cron.md.
 *
 * SAFETY: ships disabled + dry-run by default. Nothing here broadcasts a
 * real transaction until an admin sets, on the active token_settings row:
 *   bman_withdraw_collect_enabled = 1  AND  bman_withdraw_collect_dry_run = 0
 * (mirrors StakingPurchasecron's swap_enabled/swap_dry_run — deliberately a
 * SEPARATE pair of flags, so enabling the already-tested staking-purchase
 * cron never silently also arms this brand-new one.)
 *
 * Run it every minute (BSC ~3s blocks):
 *   CLI  :  php index.php BmanWithdrawCollectCron run
 *   HTTP :  /bman-withdraw-collect-cron?token=YOUR_CRON_TOKEN
 */
class BmanWithdrawCollectCron extends CI_Controller
{
    private $log_prefix = '[BMAN_WITHDRAW_COLLECT_CRON]';
    private $_cfg_cache = null;

    public function run()
    {
        if (!is_cli()) {
            $expected = $this->config->item('cron_token');
            if (!$expected || $this->input->get('token', true) !== $expected) {
                show_404();
            }
        }

        $this->load->model('withdraw/Bmanwithdraw_model', 'wd');
        $this->load->model('Tokenmaster_model', 'tokens');
        $this->load->model('GasFeeSettings_model', 'gasSettings');
        $this->load->model('GasFeeLedger_model', 'gasLedger');
        $this->load->model('Custodialwallet_model', 'custodial');
        $this->load->library('web3bman');

        try {
            $result = [
                'status'  => 'success',
                'message' => 'BMAN withdraw collect cron completed',
                'mode'    => $this->_isDryRun() ? 'DRY_RUN' : ($this->_isEnabled() ? 'LIVE' : 'DISABLED'),
                'details' => ['steps' => $this->_processAllPending()],
                'ran_at'  => date('Y-m-d H:i:s'),
            ];
        } catch (Exception $e) {
            log_message('error', $this->log_prefix . ' ' . $e->getMessage());
            $result = ['status' => 'error', 'message' => $e->getMessage(), 'ran_at' => date('Y-m-d H:i:s')];
        }

        echo json_encode($result) . PHP_EOL;
    }

    /* ============================ config / chain ============================ */

    private function _cfg()
    {
        if ($this->_cfg_cache === null) {
            $this->_cfg_cache = $this->db->get_where('token_settings', ['status' => 1])->row_array() ?: [];
        }
        return $this->_cfg_cache;
    }

    private function _isEnabled() { return (int) ($this->_cfg()['bman_withdraw_collect_enabled'] ?? 0) === 1; }
    private function _isDryRun()  { return (int) ($this->_cfg()['bman_withdraw_collect_dry_run'] ?? 1) === 1; }
    private function _minConfirmations() { $n = (int) ($this->_cfg()['minimum_confirmations'] ?? 0); return $n > 0 ? $n : 12; }

    /** BNB needed for one BEP-20 transfer (the collect leg the gas leg is funding). */
    private function _gasNeededBnb()
    {
        $p = $this->gasSettings->resolve('token_transfer');
        $gwei = $p['gas_price_gwei'];
        if ($gwei === null) { $cfg = $this->_cfg(); $gwei = (float) ($cfg['gas_price'] ?: 5); }
        return $p['gas_limit'] * $gwei * 1e-9 * $p['buffer_multiplier'];
    }

    private function _apiUrl(array $params)
    {
        $cfg = $this->_cfg();
        $api = trim((string) ($cfg['explorer_api_url'] ?? '')) ?: 'https://api.etherscan.io/v2/api';
        $params = array_merge([
            'chainid' => (int) ($cfg['chain_id'] ?? 56),
            'apikey'  => trim((string) ($cfg['explorer_api_key'] ?? '')),
        ], $params);
        return $api . '?' . http_build_query($params);
    }

    private function _apiGet(array $params, $timeout = 25)
    {
        $ch = curl_init($this->_apiUrl($params));
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => $timeout]);
        $raw = curl_exec($ch);
        curl_close($ch);
        return json_decode((string) $raw, true);
    }

    private function _currentBlock()
    {
        $cfg = $this->_cfg();
        $rpc = trim((string) ($cfg['rpc_url'] ?? ''));
        if ($rpc !== '') {
            $payload = json_encode(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'eth_blockNumber', 'params' => []]);
            $ch = curl_init($rpc);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_POSTFIELDS => $payload,
            ]);
            $raw = curl_exec($ch);
            curl_close($ch);
            $j = json_decode((string) $raw, true);
            if (isset($j['result'])) return (int) hexdec((string) $j['result']);
        }
        $j = $this->_apiGet(['module' => 'proxy', 'action' => 'eth_blockNumber']);
        if (isset($j['result'])) return (int) hexdec((string) $j['result']);
        return 0;
    }

    /** Confirmations of a tx hash: >=0 confirmations (0=pending), -1 reverted. DRYRUN hashes count as fully confirmed. */
    private function _txConfirmations($hash, $head)
    {
        if (empty($hash)) return 0;
        if (strpos($hash, 'DRYRUN') === 0) return $this->_minConfirmations();

        $tx = $this->_apiGet(['module' => 'proxy', 'action' => 'eth_getTransactionByHash', 'txhash' => $hash]);
        $blk = $tx['result']['blockNumber'] ?? null;
        if (empty($blk)) return 0;

        $rc = $this->_apiGet(['module' => 'proxy', 'action' => 'eth_getTransactionReceipt', 'txhash' => $hash]);
        $st = $rc['result']['status'] ?? null;
        if ($st !== null && hexdec($st) === 0) return -1;

        return max(0, $head - (int) hexdec($blk));
    }

    /* ============================= keys / addresses ============================= */

    private function _treasuryKey() { return $this->tokens->treasuryPrivateKey(); }

    private function _userAddress($userId)
    {
        $w = $this->custodial->ensureAddress((int) $userId);
        return $w['wallet_address'] ?? null;
    }

    private function _userKey($userId)
    {
        $w = $this->db->select('private_key')->where('user_id', (int) $userId)
                      ->get('user_wallet')->row_array();
        if (empty($w['private_key'])) return null;
        try { return $this->web3bman->decryptKey($w['private_key']); }
        catch (Exception $e) { return null; }
    }

    /* ============================ request loop ============================ */

    private function _processAllPending()
    {
        if (!$this->_isEnabled() && !$this->_isDryRun()) {
            return ['skipped' => 'bman_withdraw_collect_enabled = 0'];
        }

        $requests = $this->wd->claim_for_collection(25);
        $head     = $this->_currentBlock();
        $summary  = [
            'total_requests' => count($requests),
            'gas'     => ['processed' => 0, 'waiting' => 0, 'failed' => 0],
            'collect' => ['processed' => 0, 'waiting' => 0, 'failed' => 0],
        ];
        if ($head === 0 && !$this->_isDryRun()) {
            return array_merge($summary, ['error' => 'cannot read current block height']);
        }

        foreach ($requests as $request) {
            try { $this->_processRequest($request, $head, $summary); }
            catch (Exception $e) { log_message('error', $this->log_prefix . ' request ' . $request['id'] . ': ' . $e->getMessage()); }
        }
        return $summary;
    }

    private function _processRequest(&$request, $head, &$summary)
    {
        // Status stays 'processing' for the whole time the cron owns this
        // request — no separate visible 'collecting' status. It moves
        // straight to 'pending' once both legs confirm — see confirm_collected().
        if ((int) $request['gas_cron_status'] === 0)     $this->_tally($this->_stepGas($request, $head),     $summary['gas']);
        if ((int) $request['collect_cron_status'] === 0) $this->_tally($this->_stepCollect($request, $head), $summary['collect']);
    }

    private function _tally($result, &$bucket)
    {
        if ($result === true)          $bucket['processed']++;
        elseif ($result === 'waiting') $bucket['waiting']++;
        else                            $bucket['failed']++;
    }

    /* ------------------------------- gas ------------------------------- */

    private function _stepGas(&$request, $head)
    {
        $userAddress = $this->_userAddress($request['user_id']);
        if (!$userAddress) return $this->_fail($request['id'], 'gas', 'Could not resolve/create user custodial wallet');

        // Already broadcast -> verify confirmations
        if (!empty($request['gas_tx_hash'])) {
            return $this->_verifyLeg($request, 'gas', $request['gas_tx_hash'], $head, function () use (&$request, $userAddress) {
                $this->_recordOnchain($request, $request['gas_tx_hash'], $this->_cfg()['treasury_wallet'] ?? null, $userAddress,
                    $this->_gasNeededBnb() * 2, 'gas_funding', 'gas');
                $this->wd->set_cron_fields($request['id'], ['gas_cron_status' => 1, 'gas_cron_status_message' => null]);
                $request['gas_cron_status'] = 1;
            });
        }

        $need = $this->_gasNeededBnb();

        // Skip funding if the user already holds enough BNB (live only).
        if (!$this->_isDryRun()) {
            try {
                if ((float) $this->web3bman->getBnbBalance($userAddress) >= $need) {
                    $this->wd->set_cron_fields($request['id'], ['gas_cron_status' => 1, 'gas_cron_status_message' => null]);
                    $request['gas_cron_status'] = 1;
                    log_message('info', $this->log_prefix . " gas: user {$userAddress} already funded (request {$request['id']})");
                    return true;
                }
            } catch (Exception $e) { /* fall through to send */ }
        }

        // Broadcast gas: treasury -> user (send ~2x need so a small reserve remains)
        return $this->_broadcast($request, 'gas', function () use ($userAddress, $need) {
            $key = $this->_treasuryKey();
            if (!$key) throw new RuntimeException('treasury key unavailable');
            $r = $this->web3bman->sendBnb($key, $userAddress, sprintf('%.8f', $need * 2));
            return $r['tx_hash'];
        }, 'gas_tx_hash');
    }

    /* ------------------------------- collect ------------------------------- */

    private function _stepCollect(&$request, $head)
    {
        if ((int) $request['gas_cron_status'] !== 1) {
            return $this->_waiting($request['id'], 'collect', 'Waiting for gas step first');
        }

        $cfg = $this->_cfg();
        $treasuryWallet = trim((string) ($cfg['treasury_wallet'] ?? ''));
        if ($treasuryWallet === '') return $this->_fail($request['id'], 'collect', 'Treasury wallet not configured');

        if (!empty($request['collect_tx_hash'])) {
            return $this->_verifyLeg($request, 'collect', $request['collect_tx_hash'], $head, function () use (&$request, $treasuryWallet) {
                $userAddress = $this->_userAddress($request['user_id']);
                // Same reference_type/reference_id the admin's manual "Complete"
                // step already uses for the USDT leg — so this shows up
                // alongside it in Admin History under one reference_id.
                // wallet_type is a fixed ENUM ('usdt','exchange','earning',
                // 'staking','bonus','gas','treasury') with no 'bman' member —
                // an invalid value here silently stores as '' rather than
                // erroring (confirmed against a live insert), so use the
                // request's own source_wallet (currently always 'exchange',
                // the only wallet this flow allows) instead of a literal.
                $this->_recordOnchain($request, $request['collect_tx_hash'], $userAddress, $treasuryWallet,
                    $request['request_amount'], 'withdrawal_collect', $request['source_wallet']);

                $result = $this->wd->confirm_collected($request['id'], $request['collect_tx_hash']);
                if (!empty($result['error'])) {
                    throw new RuntimeException('confirm_collected failed: ' . $result['error']);
                }
            });
        }

        $bmanContract = trim((string) ($cfg['bman_contract'] ?? ''));
        if ($bmanContract === '') return $this->_fail($request['id'], 'collect', 'BMAN contract not configured');

        $userAddress = $this->_userAddress($request['user_id']);
        if (!$userAddress) return $this->_fail($request['id'], 'collect', 'Could not resolve user custodial wallet');

        // Broadcast BMAN: user (custodial key) -> treasury_wallet
        return $this->_broadcast($request, 'collect', function () use ($request, $bmanContract, $treasuryWallet) {
            $key = $this->_userKey($request['user_id']);
            if (!$key) throw new RuntimeException('user custodial key unavailable');
            $r = $this->web3bman->sendToken($key, $treasuryWallet, (string) $request['request_amount'], $bmanContract);
            return $r['tx_hash'];
        }, 'collect_tx_hash');
    }

    /* ----------------------------- primitives ----------------------------- */

    /** Broadcast a leg: dry-run stores a DRYRUN hash; live invokes $send(). Persists the hash immediately. */
    private function _broadcast(&$request, $step, callable $send, $hashCol)
    {
        try {
            if ($this->_isDryRun()) {
                $hash = 'DRYRUN-' . $step . '-' . $request['request_no'];
            } else {
                $hash = $send();
                if (empty($hash)) throw new RuntimeException('empty tx hash from broadcast');
            }
            $this->wd->set_cron_fields($request['id'], [
                $hashCol => $hash,
                $step . '_cron_status_message' => 'Broadcast sent, awaiting confirmations',
            ]);
            $request[$hashCol] = $hash;
            log_message('info', $this->log_prefix . " $step BROADCAST request {$request['id']}: $hash");

            $policy = $this->gasSettings->resolve($step === 'gas' ? 'gas_funding' : 'token_transfer');
            $userAddress = $this->_userAddress($request['user_id']);
            $treasuryWallet = $this->_cfg()['treasury_wallet'] ?? null;
            $fromTo = ($step === 'collect')
                ? [$userAddress, $treasuryWallet]
                : [$treasuryWallet, $userAddress];
            $this->gasLedger->recordBroadcast(
                $step, 'bman_withdrawal', $request['request_no'] ?? ('WD-' . $request['id']), $request['user_id'],
                $hash, $fromTo[0], $fromTo[1], $policy
            );

            // In dry-run the hash "confirms" instantly — process it in the same run.
            if ($this->_isDryRun()) {
                switch ($step) {
                    case 'gas':     return $this->_stepGas($request, PHP_INT_MAX);
                    case 'collect': return $this->_stepCollect($request, PHP_INT_MAX);
                }
            }
            return true;
        } catch (Exception $e) {
            return $this->_fail($request['id'], $step, 'Broadcast failed: ' . $e->getMessage());
        }
    }

    /** Verify a leg's tx hash reached minConf (and did not revert); run $onConfirmed. */
    private function _verifyLeg(&$request, $step, $hash, $head, callable $onConfirmed)
    {
        $conf = $this->_txConfirmations($hash, $head);
        if ($conf === -1) {
            $this->wd->set_cron_fields($request['id'], [$step . '_tx_hash' => null]);
            return $this->_fail($request['id'], $step, 'On-chain tx reverted — will rebroadcast');
        }
        $min = $this->_minConfirmations();
        if ($conf < $min) {
            return $this->_waiting($request['id'], $step, "Awaiting confirmations: $conf/$min");
        }
        $onConfirmed();
        log_message('info', $this->log_prefix . " $step CONFIRMED request {$request['id']} ($conf conf): $hash");
        return true;
    }

    /**
     * Append an audit row to onchain_transactions, same reference_type/id the
     * admin's manual "Complete" step already uses for the USDT leg — so a
     * settled withdrawal shows BOTH legs (this collection + the manual USDT
     * payout) side by side under one reference_id in Admin History.
     */
    private function _recordOnchain(&$request, $hash, $from, $to, $amount, $txType, $walletType)
    {
        if ($hash) {
            $dupe = $this->db->where(['tx_hash' => $hash, 'wallet_type' => $walletType])->count_all_results('onchain_transactions');
            if ($dupe > 0) return;
        }
        $this->db->insert('onchain_transactions', [
            'tx_hash' => $hash, 'wallet_type' => $walletType, 'tx_type' => $txType, 'status' => 'processing',
            'from_address' => $from ? strtolower((string) $from) : null,
            'to_address' => $to ? strtolower((string) $to) : null,
            'user_id' => $request['user_id'], 'amount' => $amount,
            'reference_type' => 'bman_withdrawal', 'reference_id' => (string) $request['id'],
            'linked_withdrawal_id' => $request['id'],
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $onchainId = (int) $this->db->insert_id();
        if ($onchainId && $hash) {
            $this->gasLedger->linkOnchainTx($hash, $onchainId);
        }
    }

    /** Real error — counts as failed, retried next run (status stays as-is). */
    private function _fail($requestId, $step, $message)
    {
        $this->wd->set_cron_fields($requestId, [$step . '_cron_status_message' => substr($message, 0, 500)]);
        return false;
    }

    /** Normal in-progress state — not an error, counts as 'waiting'. */
    private function _waiting($requestId, $step, $message)
    {
        $this->wd->set_cron_fields($requestId, [$step . '_cron_status_message' => substr($message, 0, 500)]);
        return 'waiting';
    }
}
