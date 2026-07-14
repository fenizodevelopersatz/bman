<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * StakingPurchasecron — FULL AUTO-BROADCASTER for USDT ↔ BMAN staking purchases.
 *
 * 100% on-chain, no manual transfers. Per order the cron both BROADCASTS and
 * VERIFIES four legs, in strict sequence, using the treasury + user custodial keys:
 *
 *   1. gas   : treasury → user   (BNB)   — calc required gas, fund the user wallet
 *   2. usdt  : user → admin      (USDT)  — signed with the user's custodial key
 *   3. bman  : treasury → user   (BMAN)  — principal, then INTERNAL split per option
 *   4. bonus : treasury → user   (BMAN)  — 25% bonus → bonus wallet
 *
 * Each leg: if not yet sent → broadcast + store its tx_hash; else → verify that
 * hash has `minimum_confirmations` blocks (and did not revert), then mark the step
 * done and (for bman/bonus) credit user_wallets via Walletledger_model. Legs are
 * gated: usdt waits gas=1; bman + bonus wait usdt=1. So an order settles over a few
 * runs (one confirmation window per leg).
 *
 * SAFETY: token_settings.swap_dry_run = 1 simulates everything (DRYRUN-* hashes,
 * instant "confirmation") and still credits internally — so the whole flow can be
 * exercised without moving real funds. Real broadcasts happen ONLY when
 * swap_dry_run = 0 and swap_enabled = 1.
 *
 * Crediting authority: this cron is the ONLY place balances are credited, and ONLY
 * after on-chain confirmation. Admin "Retry" must never credit — it only clears the
 * error so this cron re-broadcasts the missing leg.
 *
 * Run it every minute (BSC ~3s blocks):
 *   CLI  :  php index.php stakingpurchasecron run
 *   HTTP :  /staking-purchase-cron?token=YOUR_CRON_TOKEN
 */
class StakingPurchasecron extends CI_Controller
{
    private $log_prefix = '[STAKING_PURCHASE_CRON]';
    private $_cfg_cache = null;

    /**
     * $mode = 'watch' (CLI only): instead of exiting after one pass, keep polling
     * and re-processing every few seconds until every pending order has settled
     * all four legs (or a timeout is hit). Useful for interactive testing so one
     * command carries an order through gas → usdt → bonus → bman without you
     * manually re-invoking it each confirmation window.
     *
     * Deliberately NOT the default: the real crontab entry
     * (`* * * * * php index.php stakingpurchasecron run`) and Cron Lab's "Run now"
     * both rely on the existing single-pass behavior — looping inside an HTTP
     * request would block a web worker, and looping inside every minute-cron
     * invocation would stack up overlapping long-running processes.
     */
    public function run($mode = null)
    {
        if (!is_cli()) {
            $expected = $this->config->item('cron_token');
            if (!$expected || $this->input->get('token', true) !== $expected) {
                show_404();
            }
        }

        $this->load->model('Walletledger_model', 'L');
        $this->load->model('Tokenmaster_model', 'tokens');
        $this->load->model('member/StakingBonusIntegration_model', 'bonus_integration');
        $this->load->library('web3bman');

        $watch = is_cli() && $mode === 'watch';

        try {
            if (!$watch) {
                $result = [
                    'status'  => 'success',
                    'message' => 'Staking purchase cron completed',
                    'mode'    => $this->_isDryRun() ? 'DRY_RUN' : 'LIVE',
                    'details' => ['steps' => $this->_processAllPendingSteps()],
                    'ran_at'  => date('Y-m-d H:i:s'),
                ];
            } else {
                $result = $this->_runUntilSettled();
            }
        } catch (Exception $e) {
            log_message('error', $this->log_prefix . ' ' . $e->getMessage());
            $result = ['status' => 'error', 'message' => $e->getMessage(), 'ran_at' => date('Y-m-d H:i:s')];
        }

        echo json_encode($result) . PHP_EOL;
    }

    /** Poll every 5s (up to 10 minutes) until no order still has a pending leg. */
    private function _runUntilSettled($pollSeconds = 5, $maxSeconds = 600)
    {
        $start = time();
        $pass = 0;
        $lastSteps = [];

        do {
            $pass++;
            $lastSteps = $this->_processAllPendingSteps();
            $remaining = count($this->_getPendingOrders());

            fwrite(STDOUT, sprintf(
                "[pass %d, %ds elapsed] gas:%d/%d usdt:%d/%d bonus:%d/%d bman:%d/%d — %d order(s) still pending\n",
                $pass, time() - $start,
                $lastSteps['gas']['processed']   ?? 0, $lastSteps['gas']['waiting']   ?? 0,
                $lastSteps['usdt']['processed']  ?? 0, $lastSteps['usdt']['waiting']  ?? 0,
                $lastSteps['bonus']['processed'] ?? 0, $lastSteps['bonus']['waiting'] ?? 0,
                $lastSteps['bman']['processed']  ?? 0, $lastSteps['bman']['waiting']  ?? 0,
                $remaining
            ));

            if ($remaining === 0) {
                return [
                    'status' => 'success', 'message' => 'All pending orders settled',
                    'mode' => $this->_isDryRun() ? 'DRY_RUN' : 'LIVE',
                    'passes' => $pass, 'elapsed_seconds' => time() - $start,
                    'details' => ['steps' => $lastSteps], 'ran_at' => date('Y-m-d H:i:s'),
                ];
            }

            if (time() - $start + $pollSeconds < $maxSeconds) sleep($pollSeconds);
        } while (time() - $start < $maxSeconds);

        return [
            'status' => 'timeout', 'message' => "Timed out after {$maxSeconds}s waiting for confirmations — run again to continue.",
            'mode' => $this->_isDryRun() ? 'DRY_RUN' : 'LIVE',
            'passes' => $pass, 'elapsed_seconds' => time() - $start,
            'details' => ['steps' => $lastSteps], 'ran_at' => date('Y-m-d H:i:s'),
        ];
    }

    /* ============================ config / chain ============================ */

    private function _cfg()
    {
        if ($this->_cfg_cache === null) {
            $this->_cfg_cache = $this->db->get_where('token_settings', ['status' => 1])->row_array() ?: [];
        }
        return $this->_cfg_cache;
    }

    private function _isDryRun() { return (int)($this->_cfg()['swap_dry_run'] ?? 1) === 1; }
    private function _isEnabled() { return (int)($this->_cfg()['swap_enabled'] ?? 0) === 1; }
    private function _minConfirmations() { $n = (int)($this->_cfg()['minimum_confirmations'] ?? 0); return $n > 0 ? $n : 12; }

    /** BNB needed for one BEP-20 transfer (gas_limit × gas_price gwei × 1.5 buffer). */
    private function _gasNeededBnb()
    {
        $cfg = $this->_cfg();
        $gasLimit = (float)($cfg['gas_limit'] ?: 210000);
        $gwei     = (float)($cfg['gas_price'] ?: 5);
        return $gasLimit * $gwei * 1e-9 * 1.5;
    }

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

    private function _apiGet(array $params, $timeout = 25)
    {
        $ch = curl_init($this->_apiUrl($params));
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => $timeout]);
        $raw = curl_exec($ch);
        curl_close($ch);
        return json_decode((string)$raw, true);
    }

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

    /**
     * Confirmations of a specific tx hash:
     *   >= 0  confirmations (0 = pending / not yet mined)
     *   -1    reverted / failed on-chain
     * DRYRUN hashes are treated as fully confirmed.
     */
    private function _txConfirmations($hash, $head)
    {
        if (empty($hash)) return 0;
        if (strpos($hash, 'DRYRUN') === 0) return $this->_minConfirmations();

        $tx = $this->_apiGet(['module' => 'proxy', 'action' => 'eth_getTransactionByHash', 'txhash' => $hash]);
        $blk = $tx['result']['blockNumber'] ?? null;
        if (empty($blk)) return 0; // pending / unknown

        $rc = $this->_apiGet(['module' => 'proxy', 'action' => 'eth_getTransactionReceipt', 'txhash' => $hash]);
        $st = $rc['result']['status'] ?? null;
        if ($st !== null && hexdec($st) === 0) return -1; // reverted

        return max(0, $head - (int)hexdec($blk));
    }

    /* ============================= keys ============================= */

    private function _treasuryKey()
    {
        return $this->tokens->treasuryPrivateKey();
    }

    private function _userKey($order)
    {
        $w = $this->db->select('private_key')->where('user_id', (int)$order['user_id'])
                      ->get('user_wallet')->row_array();
        if (empty($w['private_key'])) return null;
        try { return $this->web3bman->decryptKey($w['private_key']); }
        catch (Exception $e) { return null; }
    }

    /* ============================ order loop ============================ */

    private function _getPendingOrders()
    {
        return $this->db->select(
                'id, ref, user_id, package_id, plan_id, plan_code, duration_years, ' .
                'user_address, admin_address, status, ' .
                'usdt_amount, bman_amount, bonus_bman, coin_distribution_option, ' .
                'gas_cron_status, usdt_cron_status, bonus_cron_status, bman_cron_status, ' .
                'gas_tx_hash, usdt_tx_hash, bonus_tx_hash, bman_tx_hash'
            )
            ->group_start()
                ->where('gas_cron_status', 0)->or_where('usdt_cron_status', 0)
                ->or_where('bonus_cron_status', 0)->or_where('bman_cron_status', 0)
            ->group_end()
            ->where('status !=', 'cancelled')
            ->order_by('id', 'ASC')->limit(50)
            ->get('staking_swap_orders')->result_array();
    }

    private function _processAllPendingSteps()
    {
        // Self-heal first: an order whose 4 legs all reached 1 but whose finalize
        // step (_checkAndCompleteOrder) never ran — e.g. a crash between the last
        // leg confirming and finalization — is invisible to _getPendingOrders()
        // forever (it no longer has any leg at 0), so it would stay stuck at its
        // last pending_* status with no user_stakes row even though the on-chain
        // delivery already genuinely completed. Sweep for that class of order on
        // every run so it can't get permanently orphaned. Pure bookkeeping, no
        // broadcasting, so it runs regardless of swap_enabled/dry_run.
        $healed = $this->_sweepStuckCompletions();

        // Broadcasting requires the swap to be enabled (unless simulating).
        if (!$this->_isEnabled() && !$this->_isDryRun()) {
            return ['skipped' => 'swap_enabled = 0', 'healed_stuck_orders' => $healed];
        }

        $orders  = $this->_getPendingOrders();
        $head    = $this->_currentBlock();
        $summary = [
            'total_orders' => count($orders),
            'healed_stuck_orders' => $healed,
            'gas'   => ['processed' => 0, 'waiting' => 0, 'failed' => 0],
            'usdt'  => ['processed' => 0, 'waiting' => 0, 'failed' => 0],
            'bonus' => ['processed' => 0, 'waiting' => 0, 'failed' => 0],
            'bman'  => ['processed' => 0, 'waiting' => 0, 'failed' => 0],
        ];
        if ($head === 0 && !$this->_isDryRun()) {
            return array_merge($summary, ['error' => 'cannot read current block height']);
        }

        foreach ($orders as $order) {
            try { $this->_processOrder($order, $head, $summary); }
            catch (Exception $e) { log_message('error', $this->log_prefix . ' order ' . $order['id'] . ': ' . $e->getMessage()); }
        }
        return $summary;
    }

    /** Finalize any order whose 4 legs are all done but status/user_stakes never caught up. */
    private function _sweepStuckCompletions()
    {
        $orders = $this->db->select(
                'id, ref, user_id, package_id, plan_id, plan_code, duration_years, ' .
                'status, bman_amount, bonus_bman, bman_tx_hash, coin_distribution_option, ' .
                'gas_cron_status, usdt_cron_status, bonus_cron_status, bman_cron_status'
            )
            ->where('gas_cron_status', 1)->where('usdt_cron_status', 1)
            ->where('bonus_cron_status', 1)->where('bman_cron_status', 1)
            ->where('status !=', 'swap_completed')
            ->where('status !=', 'cancelled')
            ->get('staking_swap_orders')->result_array();

        foreach ($orders as $order) {
            try {
                $this->_checkAndCompleteOrder($order);
                log_message('info', $this->log_prefix . " healed stuck order {$order['id']} (all legs done, finalize step had not run)");
            } catch (Exception $e) {
                log_message('error', $this->log_prefix . " sweep order {$order['id']}: " . $e->getMessage());
            }
        }
        return count($orders);
    }

    private function _processOrder(&$order, $head, &$summary)
    {
        if ((int)$order['gas_cron_status'] === 0)   $this->_tally($this->_stepGas($order, $head),   $summary['gas']);
        if ((int)$order['usdt_cron_status'] === 0)  $this->_tally($this->_stepUsdt($order, $head),  $summary['usdt']);
        if ((int)$order['bonus_cron_status'] === 0) $this->_tally($this->_stepBonus($order, $head), $summary['bonus']);
        if ((int)$order['bman_cron_status'] === 0)  $this->_tally($this->_stepBman($order, $head),  $summary['bman']);
        $this->_checkAndCompleteOrder($order);
    }

    /** Map a step result to the summary bucket: true=processed, 'waiting'=waiting, false=failed. */
    private function _tally($result, &$bucket)
    {
        if ($result === true)            $bucket['processed']++;
        elseif ($result === 'waiting')   $bucket['waiting']++;
        else                             $bucket['failed']++;
    }

    /* ------------------------------- gas ------------------------------- */

    private function _stepGas(&$order, $head)
    {
        $option = (int)($order['coin_distribution_option'] ?? 0);
        if ($option < 1 || $option > 7) {
            return $this->_fail($order['id'], 'gas', 'Invalid coin_distribution_option: ' . $option);
        }

        // Already broadcast → verify confirmations
        if (!empty($order['gas_tx_hash'])) {
            return $this->_verifyLeg($order, 'gas', $order['gas_tx_hash'], $head, function () use (&$order) {
                $this->_setOrder($order, ['gas_cron_status' => 1, 'gas_cron_status_message' => null, 'status' => 'pending_usdt']);
            });
        }

        $need = $this->_gasNeededBnb();
        $user = $order['user_address'];

        // Skip funding if the user already holds enough BNB (live only).
        if (!$this->_isDryRun()) {
            try {
                if ((float)$this->web3bman->getBnbBalance($user) >= $need) {
                    $this->_setOrder($order, ['gas_cron_status' => 1, 'gas_cron_status_message' => null, 'status' => 'pending_usdt']);
                    log_message('info', $this->log_prefix . " gas: user {$user} already funded (order {$order['id']})");
                    return true;
                }
            } catch (Exception $e) { /* fall through to send */ }
        }

        // Broadcast gas: treasury → user (send ~2× need so a small reserve remains)
        return $this->_broadcast($order, 'gas', function () use ($order, $user, $need) {
            $key = $this->_treasuryKey();
            if (!$key) throw new RuntimeException('treasury key unavailable');
            $r = $this->web3bman->sendBnb($key, $user, sprintf('%.8f', $need * 2));
            return $r['tx_hash'];
        }, 'gas_tx_hash');
    }

    /* ------------------------------- usdt ------------------------------- */

    private function _stepUsdt(&$order, $head)
    {
        if ((int)$order['gas_cron_status'] !== 1) {
            return $this->_waiting($order['id'], 'usdt', 'Waiting for gas step first');
        }

        if (!empty($order['usdt_tx_hash'])) {
            return $this->_verifyLeg($order, 'usdt', $order['usdt_tx_hash'], $head, function () use (&$order) {
                $this->_recordOnchain($order, $order['usdt_tx_hash'], $order['user_address'], $order['admin_address'],
                    $order['usdt_amount'], 'deposit', 'usdt');
                $this->_setOrder($order, ['usdt_cron_status' => 1, 'usdt_cron_status_message' => null, 'status' => 'pending_bman']);
            });
        }

        $cfg = $this->_cfg();
        $usdtContract = trim((string)($cfg['usdt_contract'] ?? ''));
        if ($usdtContract === '') return $this->_fail($order['id'], 'usdt', 'USDT contract not configured');

        // Broadcast USDT: user (custodial key) → admin
        return $this->_broadcast($order, 'usdt', function () use ($order, $usdtContract) {
            $key = $this->_userKey($order);
            if (!$key) throw new RuntimeException('user custodial key unavailable');
            $r = $this->web3bman->sendToken($key, $order['admin_address'], (string)$order['usdt_amount'], $usdtContract);
            return $r['tx_hash'];
        }, 'usdt_tx_hash');
    }

    /* ------------------------------- bonus ------------------------------- */

    private function _stepBonus(&$order, $head)
    {
        $bonus = (float)($order['bonus_bman'] ?? 0);
        if ($bonus <= 0) { $this->_setOrder($order, ['bonus_cron_status' => 1, 'bonus_cron_status_message' => null]); return true; }
        if ((int)$order['usdt_cron_status'] !== 1) {
            return $this->_waiting($order['id'], 'bonus', 'Waiting for USDT step first');
        }

        if (!empty($order['bonus_tx_hash'])) {
            return $this->_verifyLeg($order, 'bonus', $order['bonus_tx_hash'], $head, function () use (&$order, $bonus) {
                $this->_recordOnchain($order, $order['bonus_tx_hash'], $order['admin_address'], $order['user_address'],
                    $bonus, 'transfer', 'bonus');
                $this->_credit($order, 'bonus', $bonus, $order['bonus_tx_hash']);
                $this->_setOrder($order, ['bonus_cron_status' => 1, 'bonus_cron_status_message' => null]);
            });
        }

        $cfg = $this->_cfg();
        $bmanContract = trim((string)($cfg['bman_contract'] ?? ''));
        if ($bmanContract === '') return $this->_fail($order['id'], 'bonus', 'BMAN contract not configured');

        return $this->_broadcast($order, 'bonus', function () use ($order, $bonus, $bmanContract) {
            $key = $this->_treasuryKey();
            if (!$key) throw new RuntimeException('treasury key unavailable');
            $r = $this->web3bman->sendToken($key, $order['user_address'], (string)$bonus, $bmanContract);
            return $r['tx_hash'];
        }, 'bonus_tx_hash');
    }

    /* --------------------------- principal BMAN --------------------------- */

    private function _stepBman(&$order, $head)
    {
        if ((int)$order['usdt_cron_status'] !== 1) {
            return $this->_waiting($order['id'], 'bman', 'Waiting for USDT step first');
        }

        if (!empty($order['bman_tx_hash'])) {
            return $this->_verifyLeg($order, 'bman', $order['bman_tx_hash'], $head, function () use (&$order) {
                $this->_recordOnchain($order, $order['bman_tx_hash'], $order['admin_address'], $order['user_address'],
                    $order['bman_amount'], 'transfer', 'exchange');
                // INTERNAL split of the principal into the ledger (one custodial address).
                foreach (['exchange', 'earning', 'staking', 'bonus'] as $wallet) {
                    $amount = $this->_walletShare($order, $wallet);
                    if ($amount > 0) $this->_credit($order, $wallet, $amount, $order['bman_tx_hash']);
                }
                $this->_setOrder($order, ['bman_cron_status' => 1, 'bman_cron_status_message' => null]);

                // Trigger binary matching bonus calculation. No extra dupe-guard needed:
                // _processOrder() only calls _stepBman() while bman_cron_status === 0,
                // and that was just set to 1 above, so this callback can only run once
                // per order regardless.
                $this->_triggerBinaryMatchingBonus($order);
            });
        }

        $cfg = $this->_cfg();
        $bmanContract = trim((string)($cfg['bman_contract'] ?? ''));
        if ($bmanContract === '') return $this->_fail($order['id'], 'bman', 'BMAN contract not configured');

        return $this->_broadcast($order, 'bman', function () use ($order, $bmanContract) {
            $key = $this->_treasuryKey();
            if (!$key) throw new RuntimeException('treasury key unavailable');
            $r = $this->web3bman->sendToken($key, $order['user_address'], (string)$order['bman_amount'], $bmanContract);
            return $r['tx_hash'];
        }, 'bman_tx_hash');
    }

    /**
     * Best-effort trigger — the BMAN principal has already been delivered and
     * credited by this point, so a failure here must never take down the cron.
     *
     * BinaryMatchingBonus_model currently writes to a `binary_matching_queue.
     * purchase_id` column that doesn't exist in this schema (confirmed: the real
     * table only has run_ref/status/scope/attempts/...), so this call reliably
     * fails. A plain try/catch does NOT protect against that — CodeIgniter's
     * default db_debug behavior halts the whole script on a DB error instead of
     * throwing a catchable Exception. Toggling db_debug off for just this call
     * makes a failed query return false instead, so the try/catch below actually
     * works. This does NOT fix the binary-matching-bonus feature itself — that
     * needs its own dedicated pass (queue table schema vs. model expectations)
     * if/when that feature is wanted.
     */
    private function _triggerBinaryMatchingBonus(&$order)
    {
        $prevDebug = $this->db->db_debug;
        $this->db->db_debug = false;
        try {
            $bonus_result = $this->bonus_integration->onStakingBmanTransferConfirmed(
                $order['id'],
                $order['user_id'],
                (float)$order['bman_amount'],
                $order['bman_tx_hash'] ?? 'DRYRUN'
            );
            if (!empty($bonus_result['status'])) {
                log_message('info', $this->log_prefix . " Binary matching bonus triggered for order {$order['id']}");
            } else {
                log_message('error', $this->log_prefix . " Binary matching bonus not triggered (order {$order['id']}): " . ($bonus_result['message'] ?? 'unknown'));
            }
        } catch (Throwable $e) {
            log_message('error', $this->log_prefix . " Binary matching bonus error (order {$order['id']}): " . $e->getMessage());
        } finally {
            $this->db->db_debug = $prevDebug;
        }
    }

    /* ----------------------------- primitives ----------------------------- */

    /**
     * Broadcast a leg: dry-run stores a DRYRUN hash; live invokes $send() which
     * returns the real tx_hash. The hash is persisted immediately (so a crash
     * can't cause a double-send). Confirmation happens on a later run.
     */
    private function _broadcast(&$order, $step, callable $send, $hashCol)
    {
        try {
            if ($this->_isDryRun()) {
                $hash = 'DRYRUN-' . $step . '-' . $order['ref'];
            } else {
                $hash = $send();
                if (empty($hash)) throw new RuntimeException('empty tx hash from broadcast');
            }
            $this->_setOrder($order, [$hashCol => $hash, $step . '_cron_status_message' => 'Broadcast sent, awaiting confirmations']);
            log_message('info', $this->log_prefix . " $step BROADCAST order {$order['id']}: $hash");

            // In dry-run the hash "confirms" instantly — process it in the same run.
            if ($this->_isDryRun()) {
                switch ($step) {
                    case 'gas':   return $this->_stepGas($order, PHP_INT_MAX);
                    case 'usdt':  return $this->_stepUsdt($order, PHP_INT_MAX);
                    case 'bonus': return $this->_stepBonus($order, PHP_INT_MAX);
                    case 'bman':  return $this->_stepBman($order, PHP_INT_MAX);
                }
            }
            return true;
        } catch (Exception $e) {
            return $this->_fail($order['id'], $step, 'Broadcast failed: ' . $e->getMessage());
        }
    }

    /** Verify a leg's tx hash reached minConf (and did not revert); run $onConfirmed. */
    private function _verifyLeg(&$order, $step, $hash, $head, callable $onConfirmed)
    {
        $conf = $this->_txConfirmations($hash, $head);
        if ($conf === -1) {
            // reverted → clear hash so a later run (or admin Retry) rebroadcasts
            $this->_setOrder($order, [$step . '_tx_hash' => null]);
            return $this->_fail($order['id'], $step, 'On-chain tx reverted — will rebroadcast');
        }
        $min = $this->_minConfirmations();
        if ($conf < $min) {
            return $this->_waiting($order['id'], $step, "Awaiting confirmations: $conf/$min");
        }
        $onConfirmed();
        log_message('info', $this->log_prefix . " $step CONFIRMED order {$order['id']} ($conf conf): $hash");
        return true;
    }

    /** Percentage of the PRINCIPAL for a wallet, per coin_distribution_option. */
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

    /** Append an audit row to onchain_transactions (history only). */
    private function _recordOnchain(&$order, $hash, $from, $to, $amount, $txType, $walletType)
    {
        if ($hash) {
            $dupe = $this->db->where(['tx_hash' => $hash, 'wallet_type' => $walletType])->count_all_results('onchain_transactions');
            if ($dupe > 0) return;
        }
        $this->db->insert('onchain_transactions', [
            'tx_hash' => $hash, 'wallet_type' => $walletType, 'tx_type' => $txType, 'status' => 'confirmed',
            'from_address' => strtolower((string)$from), 'to_address' => strtolower((string)$to),
            'user_id' => $order['user_id'], 'amount' => $amount,
            'reference_type' => 'stake_purchase', 'reference_id' => $order['ref'] ?? ('ORDER-' . $order['id']),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function _checkAndCompleteOrder(&$order)
    {
        $done = (int)$order['gas_cron_status'] === 1 && (int)$order['usdt_cron_status'] === 1
             && (int)$order['bonus_cron_status'] === 1 && (int)$order['bman_cron_status'] === 1;
        if (!$done || $order['status'] === 'swap_completed') return;

        // Activate the stake once (idempotent on swap_order_id). ROI terms live in
        // roi_staking_management (created at purchase); mirror them into user_stakes.
        if ((int)$this->db->where('swap_order_id', (int)$order['id'])->count_all_results('user_stakes') === 0) {
            $roi = $this->db->where('staking_swap_orders_id', (int)$order['id'])
                            ->get('roi_staking_management')->row_array();
            $planCode = in_array((string)($order['plan_code'] ?? ''), ['fixed', 'regular', 'combo'], true)
                ? $order['plan_code'] : 'fixed';
            $years    = (int)($order['duration_years'] ?: 1);
            $maturity = !empty($roi['fixed_maturity_date'])
                ? date('Y-m-d', strtotime($roi['fixed_maturity_date']))
                : date('Y-m-d', strtotime("+{$years} years"));

            $this->db->insert('user_stakes', [
                'user_id'                => (int)$order['user_id'],
                'package_id'             => (int)($order['package_id'] ?? 0),
                'plan_id'                => (int)($order['plan_id'] ?? 0),
                'plan_code'              => $planCode,
                'duration_years'         => $years,
                'stake_amount'           => (float)$order['bman_amount'],
                'roi_percent'            => (float)($roi['roi_rate_percent'] ?? 0),
                'roi_basis'              => $planCode === 'fixed' ? 'total' : 'monthly',
                'bonus_amount'           => (float)($order['bonus_bman'] ?? 0),
                'distribution_option_id' => (int)($order['coin_distribution_option'] ?? 1),
                'start_date'             => date('Y-m-d'),
                'maturity_date'          => $maturity,
                'status'                 => 'active',
                'tx_hash'                => $order['bman_tx_hash'] ?? null,
                'network'                => $this->_cfg()['network'] ?? 'mainnet',
                'chain_status'           => 'confirmed',
                'swap_order_id'          => (int)$order['id'],
                'created_at'             => date('Y-m-d H:i:s'),
            ]);
            if ($roi) $this->db->where('id', $roi['id'])->update('roi_staking_management', ['overall_status' => 'active']);
        }

        $this->_setOrder($order, ['status' => 'swap_completed', 'cron_status' => 'completed']);
        log_message('info', $this->log_prefix . " order {$order['id']} COMPLETED — stake activated.");
    }

    /** Persist order fields + mirror them into the in-memory $order for this run. */
    private function _setOrder(&$order, array $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id', $order['id'])->update('staking_swap_orders', $data);
        foreach ($data as $k => $v) $order[$k] = $v;
    }

    /** Real error — counts as failed. */
    private function _fail($orderId, $step, $message)
    {
        $this->db->where('id', $orderId)->update('staking_swap_orders',
            [$step . '_cron_status_message' => substr($message, 0, 500)]);
        return false;
    }

    /** Normal in-progress state (gated on a prior step, or awaiting confirmations).
     *  NOT an error — counts as 'waiting' so Cron Lab doesn't read it as a failure. */
    private function _waiting($orderId, $step, $message)
    {
        $this->db->where('id', $orderId)->update('staking_swap_orders',
            [$step . '_cron_status_message' => substr($message, 0, 500)]);
        return 'waiting';
    }
}
