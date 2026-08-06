<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * BinaryMatchingPayoutCron — runs the binary matching engine on a schedule and
 * takes every resulting payout ON-CHAIN, with a treasury balance precheck and
 * admin-retryable transfers.
 *
 * Stakingmatching_model (the tree-walk + min(left,right) + per-user ceiling
 * cap engine) is untouched — it already computes and internally ledger-credits
 * the correct level-wise amounts. This controller only adds four things on top:
 *
 *   (a) run    : queue-track one engine run via Matchingqueue_model (advisory-
 *                locked, so two ticks can never double-process the same carry)
 *   (b) enqueue: scan staking_matching_payouts for any payout not yet linked to
 *                an on-chain payout row, and enqueue one combined BMAN send per
 *                user (earning + staking together — one custodial address per
 *                user, same shape as how a stake purchase is already one
 *                on-chain transfer internally split across wallet buckets).
 *                Scanning the source table (not just what step (a) just ran)
 *                makes this self-healing across a crash between (a) and (b).
 *   (c) drain  : broadcast PENDING/RETRY payouts, but only after checking the
 *                TREASURY's own BNB (gas) + BMAN balance can afford them —
 *                oldest-first, stopping the batch at the first unaffordable
 *                row rather than skipping ahead to cherry-pick smaller ones.
 *   (d) confirm: poll PROCESSING payouts for confirmations; mark CONFIRMED
 *                (+ an onchain_transactions audit row) or FAILED (reverted).
 *
 * Admin Retry (Blockchainpayout_model::retry) only resets a FAILED/RETRY row's
 * state — it never re-credits, since the internal wallet credit already
 * happened synchronously inside payMatching() before any payout row existed.
 *
 * Run every 5 minutes (carry accumulates continuously regardless of cadence):
 *   CLI  : php index.php BinaryMatchingPayoutCron run
 *   HTTP : /binary-matching-payout-cron?token=YOUR_CRON_TOKEN
 *
 * $mode = 'watch' (CLI only): instead of one pass, keep polling every 5s (up to
 * 10 minutes) until nothing is left in flight — useful for interactively
 * carrying a real match through enqueue -> broadcast -> confirmations without
 * re-invoking the cron by hand every few seconds. Deliberately NOT the default:
 * the real crontab entry and Cron Lab's "Run now" both rely on the existing
 * single-pass behavior, for the same reason StakingPurchasecron's watch mode
 * isn't its default (looping inside a scheduled tick would stack up
 * overlapping long-running processes).
 *   CLI: php index.php BinaryMatchingPayoutCron run watch
 */
class BinaryMatchingPayoutCron extends CI_Controller
{
    private $log_prefix = '[BINARY_MATCHING_PAYOUT_CRON]';
    private $_cfg_cache = null;

    public function run($mode = null)
    {
        if (!is_cli()) {
            $expected = $this->config->item('cron_token');
            if (!$expected || $this->input->get('token', true) !== $expected) {
                show_404();
            }
        }

        $this->load->model('staking/Matchingqueue_model', 'MQ');
        $this->load->model('staking/Blockchainpayout_model', 'PQ');
        $this->load->model('Tokenmaster_model', 'tokens');
        $this->load->model('Onchaintx_model', 'octx');
        $this->load->library('web3bman');

        $watch = is_cli() && $mode === 'watch';

        $start = microtime(true);
        try {
            $result = $watch ? $this->_runUntilSettled() : $this->_runOnce();
        } catch (Exception $e) {
            log_message('error', $this->log_prefix . ' ' . $e->getMessage());
            $result = ['status' => 'error', 'message' => $e->getMessage(), 'ran_at' => date('Y-m-d H:i:s')];
        }

        $this->load->model('CronLog_model', 'cronlog');
        $this->cronlog->record('binary_matching_payout', $result['status'] ?? 'unknown', $result, (int) round((microtime(true) - $start) * 1000));

        echo json_encode($result) . PHP_EOL;
    }

    /** One pass: run the engine, enqueue, drain, confirm. */
    private function _runOnce()
    {
        $engine  = $this->MQ->runClaimed();
        $enqueue = $this->_enqueuePayouts();
        $drain   = $this->_drainPending();
        $confirm = $this->_confirmProcessing();
        // confirm.failed is a real, unambiguous outcome (an on-chain tx
        // reverted) — unlike drain.held, which also covers normal, self-
        // resolving backpressure (swaps disabled, transient RPC hiccup,
        // insufficient treasury balance pending refill), so it stays out of
        // this check to avoid tagging routine conditions as errors.
        $hadFailure = (int)($confirm['failed'] ?? 0) > 0;
        return [
            'status'  => $hadFailure ? 'error' : 'success',
            'message' => $hadFailure
                ? 'Binary matching payout cron completed with failures — see confirm.failed detail'
                : 'Binary matching payout cron completed',
            'mode'    => $this->_isDryRun() ? 'DRY_RUN' : 'LIVE',
            'details' => ['engine' => $engine, 'enqueued' => $enqueue, 'drain' => $drain, 'confirm' => $confirm],
            'ran_at'  => date('Y-m-d H:i:s'),
        ];
    }

    /** Poll every 5s (up to 10 minutes) until nothing is still in flight. */
    private function _runUntilSettled($pollSeconds = 5, $maxSeconds = 600)
    {
        $start = time();
        $pass = 0;
        $lastDetails = [];

        do {
            $pass++;
            $engine  = $this->MQ->runClaimed();
            $enqueue = $this->_enqueuePayouts();
            $drain   = $this->_drainPending();
            $confirm = $this->_confirmProcessing();
            $lastDetails = ['engine' => $engine, 'enqueued' => $enqueue, 'drain' => $drain, 'confirm' => $confirm];

            $remaining = $this->_countInFlight();
            fwrite(STDOUT, sprintf(
                "[pass %d, %ds elapsed] engine:%s enqueued:%d drained:%d confirmed:%d failed:%d — %d payout(s) still in flight\n",
                $pass, time() - $start, $engine['status'] ?? 'skipped',
                $enqueue['enqueued'] ?? 0, $drain['sent'] ?? 0, $confirm['confirmed'] ?? 0, $confirm['failed'] ?? 0,
                $remaining
            ));

            if ($remaining === 0) {
                return [
                    'status' => 'success', 'message' => 'All binary matching payouts settled',
                    'mode' => $this->_isDryRun() ? 'DRY_RUN' : 'LIVE',
                    'passes' => $pass, 'elapsed_seconds' => time() - $start,
                    'details' => $lastDetails, 'ran_at' => date('Y-m-d H:i:s'),
                ];
            }

            if (time() - $start + $pollSeconds < $maxSeconds) sleep($pollSeconds);
        } while (time() - $start < $maxSeconds);

        return [
            'status' => 'timeout', 'message' => "Timed out after {$maxSeconds}s — run again (or keep watching) to continue.",
            'mode' => $this->_isDryRun() ? 'DRY_RUN' : 'LIVE',
            'passes' => $pass, 'elapsed_seconds' => time() - $start,
            'details' => $lastDetails, 'ran_at' => date('Y-m-d H:i:s'),
        ];
    }

    /** Everything that still needs a future tick: queued engine runs, unlinked payouts, in-flight broadcasts. */
    private function _countInFlight()
    {
        $engineBusy = (int)$this->db->where_in('status', ['PENDING', 'PROCESSING'])->count_all_results('binary_matching_queue');
        $unlinked = (int)($this->db->query(
            "SELECT COUNT(*) n FROM staking_matching_payouts smp
             WHERE (smp.earning_amount + smp.staking_amount) > 0
               AND NOT EXISTS (SELECT 1 FROM blockchain_payout_queue q
                               WHERE q.reference_type='staking_matching_payout' AND q.reference_id=smp.id)"
        )->row_array()['n'] ?? 0);
        $inFlight = (int)$this->db->where_in('status', ['PENDING', 'RETRY', 'PROCESSING'])->count_all_results('blockchain_payout_queue');
        return $engineBusy + $unlinked + $inFlight;
    }

    /* ============================ config / chain ============================ */
    /* Mirrors StakingPurchasecron's exact helpers — this codebase's established
     * convention is independent per-cron copies of this small RPC/explorer glue
     * rather than one shared library (Onchaintx_model and Web3bman each already
     * have their own separate copy too). */

    private function _cfg()
    {
        if ($this->_cfg_cache === null) {
            $this->_cfg_cache = $this->db->get_where('token_settings', ['status' => 1])->row_array() ?: [];
        }
        return $this->_cfg_cache;
    }

    private function _isDryRun() { return (int)($this->_cfg()['swap_dry_run'] ?? 1) === 1; }
    private function _isEnabled() { return (int)($this->_cfg()['swap_enabled'] ?? 0) === 1; }

    /** BNB needed for one BEP-20 transfer (gas_limit x gas_price gwei x 1.5 buffer). */
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
     * Confirmations of a specific tx hash (same algorithm as StakingPurchasecron,
     * but takes the confirmation threshold per-row instead of one global value
     * since each payout carries its own required_confs):
     *   >= 0  confirmations (0 = pending / not yet mined)
     *   -1    reverted / failed on-chain
     */
    private function _txConfirmations($hash, $head, $requiredConfs)
    {
        if (empty($hash)) return 0;
        if (strpos($hash, 'DRYRUN') === 0) return $requiredConfs;

        $tx = $this->_apiGet(['module' => 'proxy', 'action' => 'eth_getTransactionByHash', 'txhash' => $hash]);
        $blk = $tx['result']['blockNumber'] ?? null;
        if (empty($blk)) return 0; // pending / unknown

        $rc = $this->_apiGet(['module' => 'proxy', 'action' => 'eth_getTransactionReceipt', 'txhash' => $hash]);
        $st = $rc['result']['status'] ?? null;
        if ($st !== null && hexdec($st) === 0) return -1; // reverted

        return max(0, $head - (int)hexdec($blk));
    }

    /* ============================ phase b: enqueue ============================ */

    /**
     * Idempotent, crash-safe: scans staking_matching_payouts (the engine's own
     * audit table) for any row with no linked blockchain_payout_queue entry yet,
     * rather than trusting in-memory state from the run just performed — so a
     * crash between running the engine and reaching this step can never
     * permanently strand a payout's on-chain leg.
     */
    /* protected (not private): Binarymatchingpayouttest extends this controller
     * to exercise phases (b)/(c)/(d) in isolation without invoking phase (a)
     * against real data — see that file for why. The optional user_id opt is
     * for that test's isolation (scopes the scan to one synthetic user rather
     * than sweeping every real unlinked payout); production calls pass no opts. */
    protected function _enqueuePayouts($opts = [])
    {
        $cfg = $this->_cfg();
        $requiredConfs = (int)($cfg['minimum_confirmations'] ?? 0) ?: 3;

        $userFilter = !empty($opts['user_id']) ? $this->db->escape((int)$opts['user_id']) : null;
        $rows = $this->db->query(
            "SELECT smp.id, smp.user_id, smp.earning_amount, smp.staking_amount, smp.run_ref
             FROM staking_matching_payouts smp
             WHERE (smp.earning_amount + smp.staking_amount) > 0
               " . ($userFilter !== null ? "AND smp.user_id = {$userFilter}" : '') . "
               AND NOT EXISTS (
                 SELECT 1 FROM blockchain_payout_queue q
                 WHERE q.reference_type = 'staking_matching_payout' AND q.reference_id = smp.id
               )
             ORDER BY smp.id ASC LIMIT 200"
        )->result_array();

        $enqueued = 0; $skipped = 0;
        foreach ($rows as $r) {
            $uw = $this->db->select('wallet_address')->where('user_id', (int)$r['user_id'])
                           ->get('user_wallet')->row_array();
            if (empty($uw['wallet_address'])) {
                log_message('error', $this->log_prefix." user {$r['user_id']} has no on-chain wallet address — payout for staking_matching_payouts#{$r['id']} skipped, will retry next tick");
                $skipped++;
                continue;
            }
            $this->db->insert('blockchain_payout_queue', [
                'payout_ref'     => 'MBP-' . $r['run_ref'] . '-U' . $r['user_id'],
                'user_id'        => (int)$r['user_id'],
                'token'          => 'BMAN',
                'amount'         => round((float)$r['earning_amount'] + (float)$r['staking_amount'], 8),
                'to_address'     => $uw['wallet_address'],
                'purpose'        => 'binary_matching',
                'reference_type' => 'staking_matching_payout',
                'reference_id'   => (string)$r['id'],
                'status'         => 'PENDING',
                'required_confs' => $requiredConfs,
            ]);
            $enqueued++;
        }
        return ['scanned' => count($rows), 'enqueued' => $enqueued, 'skipped_no_wallet' => $skipped];
    }

    /* ============================ phase c: drain ============================ */

    /**
     * Broadcasts PENDING/RETRY payouts, gated on a treasury-balance precheck.
     * One balance snapshot per batch (not per row); walks oldest-first and
     * stops the whole batch at the first row the treasury can't afford, so an
     * earlier/larger legitimate payout is never starved in favor of a later,
     * smaller one. A broadcast-level failure (bad RPC, nonce clash) is
     * row-specific and does not stop the batch.
     */
    protected function _drainPending($opts = [])
    {
        if (!empty($opts['user_id'])) $this->db->where('user_id', (int)$opts['user_id']);
        $rows = $this->db->where_in('status', ['PENDING', 'RETRY'])
                         ->order_by('created_at', 'ASC')->order_by('id', 'ASC')
                         ->limit(30)->get('blockchain_payout_queue')->result_array();
        if (!$rows) return ['processed' => 0, 'sent' => 0, 'held' => 0];

        $dryRun = !empty($opts['force_dry_run']) || $this->_isDryRun();
        $cfg = $this->_cfg();
        $treasuryAddr = $cfg['treasury_wallet'] ?? '';

        if ($dryRun) {
            $sent = 0;
            foreach ($rows as $row) {
                $claimed = $this->db->where('id', $row['id'])->where_in('status', ['PENDING', 'RETRY'])
                                    ->update('blockchain_payout_queue', [
                                        'status'          => 'PROCESSING',
                                        'tx_hash'         => 'DRYRUN-' . $row['payout_ref'],
                                        'from_address'    => $treasuryAddr ?: 'DRYRUN-TREASURY',
                                        'last_attempt_at' => date('Y-m-d H:i:s'),
                                        'precheck_json'   => json_encode(['dry_run' => true, 'checked_at' => date('Y-m-d H:i:s')]),
                                    ]);
                if ($this->db->affected_rows() === 1) $sent++;
            }
            return ['processed' => count($rows), 'sent' => $sent, 'held' => 0, 'mode' => 'DRY_RUN'];
        }

        if (!$this->_isEnabled()) {
            return ['processed' => count($rows), 'sent' => 0, 'held' => count($rows), 'skipped' => 'swap_enabled = 0'];
        }

        try {
            $remainingBnb  = (float)$this->web3bman->getBnbBalance($treasuryAddr);
            $remainingBman = (float)$this->web3bman->getTokenBalance($treasuryAddr);
        } catch (Exception $e) {
            foreach ($rows as $row) {
                $this->_markRetry($row['id'], 'RPC unreachable: ' . $e->getMessage(), [
                    'rpc_ok' => false, 'checked_at' => date('Y-m-d H:i:s'),
                ]);
            }
            return ['processed' => count($rows), 'sent' => 0, 'held' => count($rows), 'error' => 'rpc_unreachable'];
        }

        $gasPerSend = $this->_gasNeededBnb();
        $sent = 0; $held = 0;

        foreach ($rows as $row) {
            $needBman = (float)$row['amount'];
            $snapshot = [
                'checked_at' => date('Y-m-d H:i:s'), 'treasury_address' => $treasuryAddr,
                'treasury_bnb_balance' => $remainingBnb, 'treasury_bman_balance' => $remainingBman,
                'gas_needed_bnb' => $gasPerSend, 'amount_needed_bman' => $needBman, 'rpc_ok' => true,
            ];

            if ($remainingBnb < $gasPerSend || $remainingBman < $needBman) {
                $snapshot['result'] = $remainingBnb < $gasPerSend ? 'insufficient_bnb' : 'insufficient_bman';
                $this->_markRetry($row['id'], sprintf(
                    'Insufficient treasury balance: need %s BNB / %s BMAN, have %s BNB / %s BMAN',
                    $gasPerSend, $needBman, $remainingBnb, $remainingBman
                ), $snapshot);
                $held++;
                break; // FIFO fairness: stop the batch here rather than skip ahead
            }

            $claimed = $this->db->where('id', $row['id'])->where_in('status', ['PENDING', 'RETRY'])
                                ->update('blockchain_payout_queue', [
                                    'status'          => 'PROCESSING',
                                    'precheck_json'   => json_encode($snapshot + ['result' => 'ok']),
                                    'last_attempt_at' => date('Y-m-d H:i:s'),
                                ]);
            if ($this->db->affected_rows() !== 1) continue; // lost the claim race to another process

            try {
                $treasuryKey = $this->tokens->treasuryPrivateKey();
                if (!$treasuryKey) throw new RuntimeException('treasury key unavailable');
                $r = $this->web3bman->sendToken($treasuryKey, $row['to_address'], (string)$row['amount']);
                if (empty($r['tx_hash'])) throw new RuntimeException('empty tx hash from broadcast');
                $this->db->where('id', $row['id'])->update('blockchain_payout_queue', [
                    'tx_hash' => $r['tx_hash'], 'from_address' => $treasuryAddr,
                ]);
                $remainingBnb -= $gasPerSend;
                $remainingBman -= $needBman;
                $sent++;
            } catch (Exception $e) {
                $retryCount = (int)$row['retry_count'] + 1;
                $status = $retryCount < (int)$row['max_retries'] ? 'RETRY' : 'FAILED';
                $this->db->where('id', $row['id'])->update('blockchain_payout_queue', [
                    'status'      => $status,
                    'retry_count' => $retryCount,
                    'last_error'  => substr('Broadcast failed: ' . $e->getMessage(), 0, 255),
                ]);
                $held++;
            }
        }
        return ['processed' => count($rows), 'sent' => $sent, 'held' => $held];
    }

    /** Balance-shortfall / RPC-outage hold: does NOT burn retry_count (systemic, not the row's fault). */
    private function _markRetry($id, $error, array $snapshot)
    {
        $this->db->where('id', $id)->update('blockchain_payout_queue', [
            'status'          => 'RETRY',
            'last_error'      => substr($error, 0, 255),
            'precheck_json'   => json_encode($snapshot),
            'last_attempt_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /* ============================ phase d: confirm ============================ */

    protected function _confirmProcessing($opts = [])
    {
        if (!empty($opts['user_id'])) $this->db->where('user_id', (int)$opts['user_id']);
        $rows = $this->db->where('status', 'PROCESSING')->order_by('id', 'ASC')->limit(50)
                         ->get('blockchain_payout_queue')->result_array();
        if (!$rows) return ['checked' => 0, 'confirmed' => 0, 'failed' => 0];

        $head = $this->_currentBlock();
        $confirmed = 0; $failed = 0;
        foreach ($rows as $row) {
            $conf = $this->_txConfirmations($row['tx_hash'], $head, (int)$row['required_confs']);

            if ($conf === -1) {
                $this->db->where('id', $row['id'])->update('blockchain_payout_queue', [
                    'status' => 'FAILED', 'tx_hash' => null, 'last_error' => 'On-chain transaction reverted',
                ]);
                $failed++;
                continue;
            }
            if ($conf < (int)$row['required_confs']) continue; // still awaiting confirmations

            $meta = $this->_cfg();
            $txId = $this->octx->upsertByReference('binary_matching_payout', (string)$row['id'], [
                'tx_hash'            => $row['tx_hash'],
                'network'            => $meta['network'] ?? 'mainnet',
                'chain_id'           => (int)($meta['chain_id'] ?? 56),
                'wallet_type'        => 'earning', // majority-share bucket, same convention as other combined-transfer summary rows
                'tx_type'            => 'transfer',
                'status'             => 'confirmed',
                'from_address'       => strtolower((string)$row['from_address']),
                'to_address'         => strtolower((string)$row['to_address']),
                'user_id'            => $row['user_id'],
                'token_symbol'       => 'BMAN',
                'token_name'         => $meta['bman_name'] ?? 'BMAN Token',
                'token_contract'     => $meta['bman_contract'] ?? null,
                'token_decimals'     => (int)($meta['bman_decimals'] ?? 18),
                'amount'             => $row['amount'],
                'block_number'       => $head - $conf,
                'confirmation_count' => $conf,
            ]);

            $this->db->where('id', $row['id'])->update('blockchain_payout_queue', [
                'status'        => 'CONFIRMED',
                'block_number'  => $head - $conf,
                'confirmations' => $conf,
                'confirmed_at'  => date('Y-m-d H:i:s'),
                'onchain_tx_id' => $txId ?: null,
            ]);
            $confirmed++;
        }
        return ['checked' => count($rows), 'confirmed' => $confirmed, 'failed' => $failed];
    }
}
