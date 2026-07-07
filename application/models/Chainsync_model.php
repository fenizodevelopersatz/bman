<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Chainsync_model — efficient blockchain balance sync + transaction verification.
 * ---------------------------------------------------------------------------
 * Cost model: **free RPC is primary; the BscScan API is a fallback used ONLY
 * when a balance change is detected.**
 *
 *   syncAddress():  RPC eth_getBalance (BNB) + balanceOf (BEP-20) → compare to
 *                   stored balance. No diff → stop (no API cost). Diff → pull the
 *                   BscScan tokentx history from the cached last_block, import new
 *                   transfers (dedupe on tx_hash+log_index), update the stored
 *                   balance + cursor.
 *   verifyTx():     RPC getTransaction + receipt + blockNumber → status,
 *                   confirmations, gas; reconcile the DB; detect reorgs; log every
 *                   state change with the RPC endpoint used.
 *   Multi-RPC failover, and every attempt / balance change / RPC failure / API
 *   call is written to rpc_sync_log.
 *
 * See docs/14_ONCHAIN_SYNC_LIFECYCLE.md.
 */
class Chainsync_model extends CI_Model
{
    /** Free BSC RPC endpoints tried in order (token_settings.rpc_url first). */
    private $freeRpc = [
        'https://bsc-dataseed.binance.org',
        'https://bsc-dataseed1.defibit.io',
        'https://bsc-dataseed1.ninicoin.io',
        'https://bsc.publicnode.com',
        'https://rpc.ankr.com/bsc',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Onchaintx_model', 'octx');
    }

    private function cfg()
    {
        static $c = null;
        if ($c === null) $c = $this->db->get_where('token_settings', ['status' => 1])->row_array() ?: [];
        return $c;
    }

    public function endpoints()
    {
        $cfg = $this->cfg();
        $list = [];
        if (!empty($cfg['rpc_url'])) $list[] = $cfg['rpc_url'];
        foreach ($this->freeRpc as $u) $list[] = $u;
        return array_values(array_unique($list));
    }

    private function minConf()
    {
        return max(1, (int)($this->cfg()['minimum_confirmations'] ?? 15));
    }

    /* ------------------------- RPC with failover ------------------------- */

    /**
     * One JSON-RPC call, trying each endpoint until one answers.
     * @return array ['result'=>mixed,'endpoint'=>url] or ['error'=>msg,'endpoint'=>null]
     */
    public function rpc($method, array $params = [], $runId = null)
    {
        foreach ($this->endpoints() as $url) {
            $t0 = microtime(true);
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode(['jsonrpc'=>'2.0','id'=>1,'method'=>$method,'params'=>$params]),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            $raw = curl_exec($ch);
            $err = curl_error($ch);
            curl_close($ch);
            $ms = (int)round((microtime(true) - $t0) * 1000);

            if ($raw === false) {
                $this->logSync(['run_id'=>$runId,'scope'=>'failover','endpoint'=>$url,'api_used'=>'rpc',
                    'ok'=>0,'message'=>'transport: '.substr($err,0,180),'duration_ms'=>$ms]);
                continue; // try next endpoint
            }
            $j = json_decode($raw, true);
            if (!is_array($j) || isset($j['error']) || !array_key_exists('result', $j)) {
                $this->logSync(['run_id'=>$runId,'scope'=>'failover','endpoint'=>$url,'api_used'=>'rpc',
                    'ok'=>0,'message'=>'rpc error: '.substr(json_encode($j['error'] ?? 'no result'),0,180),'duration_ms'=>$ms]);
                continue;
            }
            return ['result' => $j['result'], 'endpoint' => $url];
        }
        return ['error' => 'all RPC endpoints failed', 'endpoint' => null];
    }

    private function hx($h) { return ($h === null || $h === '') ? null : gmp_strval(gmp_init($h, 16), 10); }

    /* ----------------------------- balances ------------------------------ */

    /** Raw BNB balance (wei) + the endpoint that answered. */
    public function getBnbRaw($addr, $runId = null)
    {
        $r = $this->rpc('eth_getBalance', [$addr, 'latest'], $runId);
        return isset($r['result']) ? ['raw' => $this->hx($r['result']), 'endpoint' => $r['endpoint']] : ['raw' => null, 'endpoint' => null];
    }

    /** Raw BEP-20 balanceOf + the endpoint that answered. */
    public function getTokenRaw($addr, $contract, $runId = null)
    {
        $data = '0x70a08231' . str_pad(strtolower(preg_replace('/^0x/', '', $addr)), 64, '0', STR_PAD_LEFT);
        $r = $this->rpc('eth_call', [['to'=>$contract,'data'=>$data], 'latest'], $runId);
        return isset($r['result']) ? ['raw' => $this->hx($r['result']), 'endpoint' => $r['endpoint']] : ['raw' => null, 'endpoint' => null];
    }

    private function human($raw, $decimals) { return $raw === null ? null : bcdiv((string)$raw, bcpow('10', (string)$decimals, 0), $decimals); }

    /* ----------------------- transaction verification -------------------- */

    /**
     * Verify one transaction against the chain: status, confirmations, gas, block.
     * Reconciles the DB row, detects reorgs, logs a state-change event.
     */
    public function verifyTx($idOrRow, $runId = null)
    {
        $row = is_array($idOrRow) ? $idOrRow : $this->octx->get((int)$idOrRow);
        if (!$row || empty($row['tx_hash'])) return ['ok' => false, 'reason' => 'no tx hash'];
        $hash = $row['tx_hash'];

        $tx = $this->rpc('eth_getTransactionByHash', [$hash], $runId);
        if (!isset($tx['result']) || $tx['result'] === null) {
            // not found yet (pending) or wrong network
            $this->logSync(['run_id'=>$runId,'scope'=>'verify','endpoint'=>$tx['endpoint'],'api_used'=>'rpc',
                'ok'=>1,'message'=>'tx not yet on-chain (pending)']);
            return ['ok' => true, 'status' => 'pending'];
        }
        $endpoint = $tx['endpoint'];
        $rc   = $this->rpc('eth_getTransactionReceipt', [$hash], $runId);
        $head = $this->rpc('eth_blockNumber', [], $runId);

        $chainBlock = $this->hx($tx['result']['blockNumber'] ?? null);
        $curBlock   = isset($head['result']) ? $this->hx($head['result']) : null;
        $conf       = ($chainBlock !== null && $curBlock !== null) ? (int)($curBlock - $chainBlock) : null;
        $recStatus  = isset($rc['result']['status']) ? $rc['result']['status'] : null;
        $gasUsed    = isset($rc['result']['gasUsed']) ? $this->hx($rc['result']['gasUsed']) : null;
        $gasPrice   = $this->hx($tx['result']['gasPrice'] ?? null);
        $feeWei     = ($gasUsed !== null && $gasPrice !== null) ? bcmul($gasUsed, $gasPrice, 0) : null;

        $status = $recStatus === '0x1' ? 'confirmed' : ($recStatus === '0x0' ? 'reverted' : 'pending');
        $minConf = $this->minConf();
        if ($status === 'confirmed' && $conf !== null && $conf < $minConf) $status = 'processing';

        // reorg detection: stored block differs from chain block
        $reorg = (!empty($row['block_number']) && $chainBlock !== null && (string)$row['block_number'] !== (string)$chainBlock);

        $fields = [
            'status'             => $status,
            'block_number'       => $chainBlock,
            'confirmation_count' => $conf,
            'gas_used'           => $gasUsed,
            'gas_price'          => $gasPrice,
            'gas_fee_total'      => $feeWei !== null ? bcdiv($feeWei, bcpow('10','18',0), 18) : null,
            'nonce'              => $this->hx($tx['result']['nonce'] ?? null),
            'tx_index'           => $this->hx($tx['result']['transactionIndex'] ?? null),
            'last_verified_at'   => date('Y-m-d H:i:s'),
            'rpc_endpoint'       => $endpoint,
        ];
        if ($reorg) $fields['reorg_count'] = (int)$row['reorg_count'] + 1;
        if ($status === 'confirmed' && empty($row['finalized_at'])) $fields['finalized_at'] = date('Y-m-d H:i:s');

        $this->db->where('id', $row['id'])->update('onchain_transactions', $fields);

        // audit event with the RPC endpoint that produced the change
        if ((string)$row['status'] !== $status || $reorg) {
            $this->octx->logEvent($row['id'], $hash, $reorg ? 'reverted' : 'status_change', [
                'old_status' => $row['status'], 'new_status' => $status, 'confirmations' => $conf,
                'actor_type' => 'system', 'rpc_endpoint' => $endpoint,
                'detail' => $reorg ? ('reorg: db block '.$row['block_number'].' → chain '.$chainBlock) : null,
            ]);
        }

        $this->logSync(['run_id'=>$runId,'scope'=>'verify','endpoint'=>$endpoint,'api_used'=>'rpc','ok'=>1,
            'message'=>"status={$status} conf={$conf}".($reorg?' REORG':''),'tx_imported'=>0]);

        return ['ok'=>true,'status'=>$status,'confirmations'=>$conf,'reorg'=>$reorg,'block'=>$chainBlock,'endpoint'=>$endpoint];
    }

    /* ------------------- efficient RPC-first balance sync ---------------- */

    /**
     * Sync one address for one token. RPC-first; BscScan only on a balance diff.
     * @return array summary
     */
    public function syncAddress($addr, $token, $contract, $decimals, $userId = null, $runId = null)
    {
        $t0 = microtime(true);
        // 1) latest balance via free RPC
        $bal = $contract ? $this->getTokenRaw($addr, $contract, $runId) : $this->getBnbRaw($addr, $runId);
        if ($bal['raw'] === null) {
            $this->logSync(['run_id'=>$runId,'scope'=>'balance_sync','address'=>$addr,'token'=>$token,
                'api_used'=>'rpc','ok'=>0,'message'=>'balance read failed (all RPC down)','duration_ms'=>(int)round((microtime(true)-$t0)*1000)]);
            return ['ok'=>false,'reason'=>'rpc_down'];
        }
        $rawNow = $bal['raw'];
        $humanNow = $this->human($rawNow, $decimals);

        // 2) compare to stored
        $stored = $this->db->get_where('wallet_balance_sync', ['address'=>$addr,'token'=>$token])->row_array();
        $rawPrev = $stored ? (string)$stored['last_balance_raw'] : null;
        $diff = ($rawPrev === null) ? true : (bccomp($rawNow, $rawPrev, 0) !== 0);

        // 3) no diff → do NOT touch the paid explorer API
        if (!$diff) {
            $this->upsertBalance($addr,$token,$contract,$humanNow,$rawNow,$stored['last_block']??null,$stored['last_tx_hash']??null,$userId);
            $this->logSync(['run_id'=>$runId,'scope'=>'balance_sync','address'=>$addr,'token'=>$token,'endpoint'=>$bal['endpoint'],
                'api_used'=>'rpc','ok'=>1,'diff_detected'=>0,'balance_before'=>$stored['last_balance']??null,'balance_after'=>$humanNow,
                'message'=>'no change — explorer API skipped','duration_ms'=>(int)round((microtime(true)-$t0)*1000)]);
            return ['ok'=>true,'diff'=>false,'balance'=>$humanNow,'api'=>'rpc'];
        }

        // 4) diff → pull tx history from BscScan (fallback), import new, then store
        $imported = 0; $api = 'rpc'; $note = 'balance changed (RPC only — explorer not configured)';
        if ($contract) { // BEP-20 history via tokentx
            $res = $this->bscscanTokenTx($addr, $contract, (int)($stored['last_block'] ?? 0), $runId);
            if ($res['ok']) {
                $api = 'bscscan';
                foreach ($res['txs'] as $t) $imported += $this->importTransfer($t, $addr, $token, $userId, $runId) ? 1 : 0;
                $note = 'balance changed — imported '.$imported.' tx via BscScan';
            } else {
                $note = 'balance changed — '.$res['reason'].' (RPC balance stored)';
            }
        }

        $lastBlock = $stored['last_block'] ?? null;
        $lastHash  = $stored['last_tx_hash'] ?? null;
        if (!empty($res['txs'][0])) { $lastBlock = $res['txs'][0]['blockNumber'] ?? $lastBlock; $lastHash = $res['txs'][0]['hash'] ?? $lastHash; }
        $this->upsertBalance($addr,$token,$contract,$humanNow,$rawNow,$lastBlock,$lastHash,$userId);

        $this->logSync(['run_id'=>$runId,'scope'=>'import','address'=>$addr,'token'=>$token,'endpoint'=>$bal['endpoint'],
            'api_used'=>$api,'ok'=>1,'diff_detected'=>1,'balance_before'=>$stored['last_balance']??null,'balance_after'=>$humanNow,
            'tx_imported'=>$imported,'message'=>$note,'duration_ms'=>(int)round((microtime(true)-$t0)*1000)]);

        return ['ok'=>true,'diff'=>true,'balance'=>$humanNow,'imported'=>$imported,'api'=>$api];
    }

    private function upsertBalance($addr,$token,$contract,$human,$raw,$block,$hash,$userId)
    {
        $data = ['user_id'=>$userId,'contract'=>$contract,'last_balance'=>$human,'last_balance_raw'=>$raw,
                 'last_block'=>$block ?: null,'last_tx_hash'=>$hash,'last_synced_at'=>date('Y-m-d H:i:s')];
        $exists = $this->db->get_where('wallet_balance_sync', ['address'=>$addr,'token'=>$token])->row_array();
        if ($exists) {
            $data['sync_count'] = (int)$exists['sync_count'] + 1;
            $this->db->where('id', $exists['id'])->update('wallet_balance_sync', $data);
        } else {
            $this->db->insert('wallet_balance_sync', array_merge(['address'=>$addr,'token'=>$token,'sync_count'=>1], $data));
        }
    }

    /** BscScan/Etherscan-v2 tokentx (fallback). Reads key/url from Token Settings. */
    public function bscscanTokenTx($addr, $contract, $startBlock, $runId = null)
    {
        $cfg = $this->cfg();
        $key = trim((string)($cfg['explorer_api_key'] ?? ''));
        if ($key === '') return ['ok'=>false,'reason'=>'BscScan API key not set','txs'=>[]];
        $api = $cfg['explorer_api_url'] ?: 'https://api.etherscan.io/v2/api';
        $chain = (int)($cfg['chain_id'] ?? 56);
        $q = http_build_query([
            'chainid'=>$chain,'module'=>'account','action'=>'tokentx','contractaddress'=>$contract,
            'address'=>$addr,'startblock'=>max(0,(int)$startBlock),'endblock'=>999999999,
            'page'=>1,'offset'=>50,'sort'=>'desc','apikey'=>$key,
        ]);
        $t0 = microtime(true);
        $ch = curl_init($api.'?'.$q);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>15,CURLOPT_SSL_VERIFYPEER=>true]);
        $raw = curl_exec($ch); $err = curl_error($ch); curl_close($ch);
        $this->logSync(['run_id'=>$runId,'scope'=>'import','address'=>$addr,'token'=>'','endpoint'=>$api,'api_used'=>'bscscan',
            'ok'=>($raw!==false)?1:0,'message'=>($raw!==false)?'tokentx fetched':('transport: '.substr($err,0,150)),
            'duration_ms'=>(int)round((microtime(true)-$t0)*1000)]);
        if ($raw === false) return ['ok'=>false,'reason'=>'explorer transport error','txs'=>[]];
        $j = json_decode($raw, true);
        if (!is_array($j) || ($j['status'] ?? '0') !== '1' || !is_array($j['result'] ?? null)) {
            return ['ok'=>false,'reason'=>'explorer: '.substr((string)($j['message'] ?? 'no result'),0,120),'txs'=>[]];
        }
        return ['ok'=>true,'txs'=>$j['result']];
    }

    /** Import one BEP-20 transfer, deduped on (tx_hash, log_index). */
    private function importTransfer(array $t, $addr, $token, $userId, $runId)
    {
        $hash = $t['hash'] ?? null; $logIx = $t['transactionIndex'] ?? ($t['logIndex'] ?? '0');
        if (!$hash) return false;
        $refId = $hash.':'.$logIx;
        $dupe = $this->db->where('tx_hash',$hash)->where('reference_id',$refId)->count_all_results('onchain_transactions');
        if ($dupe > 0) return false;

        $dec = (int)($t['tokenDecimal'] ?? 18);
        $amt = $this->human($t['value'] ?? '0', $dec);
        $incoming = strtolower($t['to'] ?? '') === strtolower($addr);

        $this->octx->capture([
            'tx_hash'        => $hash,
            'network'        => 'mainnet',
            'chain_id'       => (int)($this->cfg()['chain_id'] ?? 56),
            'wallet_type'    => strtolower($token) === 'usdt' ? 'usdt' : 'exchange',
            'tx_type'        => $incoming ? 'deposit' : 'transfer_out',
            'status'         => 'confirmed',
            'from_address'   => $t['from'] ?? null,
            'to_address'     => $t['to'] ?? null,
            'user_id'        => $userId,
            'token_symbol'   => $t['tokenSymbol'] ?? $token,
            'token_name'     => $t['tokenName'] ?? null,
            'token_contract' => $t['contractAddress'] ?? null,
            'token_decimals' => $dec,
            'amount'         => $amt,
            'block_number'   => $t['blockNumber'] ?? null,
            'confirmation_count' => $t['confirmations'] ?? null,
            'gas_used'       => $t['gasUsed'] ?? null,
            'gas_price'      => $t['gasPrice'] ?? null,
            'reference_type' => 'import',
            'reference_id'   => $refId,
            '_event'         => ['actor_type'=>'system','detail'=>'imported via BscScan tokentx'],
        ]);
        return true;
    }

    /* --------------- confirmation follow-up until finality --------------- */

    /** Re-verify still-pending/processing on-chain txs; advance confirmations. */
    public function finalizeConfirmations($runId = null, $limit = 50)
    {
        $rows = $this->db->select('id, tx_hash, status, block_number, reorg_count, finalized_at')
            ->from('onchain_transactions')
            ->where('tx_hash IS NOT NULL', null, false)
            ->where_in('status', ['pending','processing','broadcasting'])
            ->order_by('id','DESC')->limit((int)$limit)->get()->result_array();
        $done = 0;
        foreach ($rows as $r) { $this->verifyTx($r, $runId); $done++; }
        return $done;
    }

    /* --------------------- scalable batch rotation ----------------------- */

    /** Cursor config (batch size, position); creates the singleton if missing. */
    public function cursor()
    {
        $c = $this->db->get_where('wallet_sync_cursor', ['id' => 1])->row_array();
        if (!$c) {
            $this->db->insert('wallet_sync_cursor', ['id' => 1, 'batch_size' => 200]);
            $c = $this->db->get_where('wallet_sync_cursor', ['id' => 1])->row_array();
        }
        return $c;
    }

    /**
     * Atomically claim the next rotation window (SELECT … FOR UPDATE on the
     * cursor). Concurrent workers each get a distinct window; the cursor persists
     * across restarts (resume from where it stopped). Wraps to a new full pass
     * when the end is reached.
     */
    public function claimBatch($workerId, $batchOverride = null)
    {
        $this->db->trans_begin();
        $cur = $this->db->query("SELECT * FROM wallet_sync_cursor WHERE id = 1 FOR UPDATE")->row_array();
        if (!$cur) { $this->db->trans_rollback(); $this->cursor(); return $this->claimBatch($workerId, $batchOverride); }

        $batch = $batchOverride ? max(1, (int)$batchOverride) : max(1, (int)$cur['batch_size']);
        $last  = (int)$cur['last_user_id'];
        $cycle = (int)$cur['cycle_count'];
        $wrapped = false;

        $rows = $this->db->select('user_id, wallet_address')->from('user_wallet')
            ->where('user_id >', $last)->where('wallet_address IS NOT NULL', null, false)
            ->order_by('user_id', 'ASC')->limit($batch)->get()->result_array();

        if (empty($rows)) { // reached the end → start a new full pass
            $wrapped = true; $cycle++;
            $rows = $this->db->select('user_id, wallet_address')->from('user_wallet')
                ->where('wallet_address IS NOT NULL', null, false)
                ->order_by('user_id', 'ASC')->limit($batch)->get()->result_array();
        }
        $newLast = !empty($rows) ? (int)$rows[count($rows) - 1]['user_id'] : 0;

        $this->db->where('id', 1)->update('wallet_sync_cursor', [
            'last_user_id' => $newLast, 'cycle_count' => $cycle,
            'worker_id' => $workerId, 'locked_at' => date('Y-m-d H:i:s'), 'last_run_at' => date('Y-m-d H:i:s'),
        ]);
        $this->db->trans_commit();

        return ['rows'=>$rows, 'cursor'=>$newLast, 'cycle'=>$cycle, 'batch_size'=>$batch, 'wrapped'=>$wrapped];
    }

    /** Addresses with an unsettled on-chain tx — processed first (priority). */
    public function pendingAddresses($limit = 50)
    {
        return $this->db->distinct()->select('uw.user_id, uw.wallet_address')
            ->from('onchain_transactions o')
            ->join('user_wallet uw', 'uw.user_id = o.user_id', 'inner')
            ->where_in('o.status', ['pending','processing','broadcasting'])
            ->where('uw.wallet_address IS NOT NULL', null, false)
            ->limit((int)$limit)->get()->result_array();
    }

    /**
     * One batch run: verify pending txs, sync priority (pending) addresses, then
     * the claimed rotation window. RPC-first; deduped within the run; metrics
     * logged to rpc_sync_log (scope='batch').
     */
    public function syncBatch($workerId, $batchOverride = null, $runId = null)
    {
        $t0 = microtime(true);
        $runId = $runId ?: ('batch_' . date('YmdHis') . '_' . substr(md5(uniqid('', true)), 0, 6));
        $usdtC = $this->cfg()['usdt_contract'] ?? null;

        $verified = $this->finalizeConfirmations($runId, 100);   // retry/advance pending
        $priority = $this->pendingAddresses(50);                 // priority first
        $claim    = $this->claimBatch($workerId, $batchOverride);

        $m = ['processed'=>0,'skipped'=>0,'balance_changed'=>0,'tx_imported'=>0,'bscscan_calls'=>0,'rpc_only'=>0,'rpc_failures'=>0];
        $seen = [];
        foreach (array_merge($priority, $claim['rows']) as $u) {
            $addr = $u['wallet_address'];
            if (isset($seen[$addr])) { $m['skipped']++; continue; }   // no address twice in a run
            $seen[$addr] = 1;
            $uid  = (int)$u['user_id'];
            $bnb  = $this->syncAddress($addr, 'BNB',  null,  18, $uid, $runId);
            $usdt = $usdtC ? $this->syncAddress($addr, 'USDT', $usdtC, 18, $uid, $runId) : ['diff'=>false,'api'=>'rpc'];
            $m['processed']++;
            foreach ([$bnb, $usdt] as $r) {
                if (empty($r['ok']) && ($r['reason'] ?? '') === 'rpc_down') $m['rpc_failures']++;
                if (!empty($r['diff'])) $m['balance_changed']++;
                if (($r['api'] ?? 'rpc') === 'bscscan') $m['bscscan_calls']++; else $m['rpc_only']++;
                $m['tx_imported'] += (int)($r['imported'] ?? 0);
            }
        }
        $ms = (int)round((microtime(true) - $t0) * 1000);
        $this->logSync(['run_id'=>$runId,'scope'=>'batch','api_used'=>'rpc','ok'=>1,'duration_ms'=>$ms,'tx_imported'=>$m['tx_imported'],
            'message'=>"worker={$workerId} cursor={$claim['cursor']} cycle={$claim['cycle']}".($claim['wrapped']?' WRAP':'')
                       ." processed={$m['processed']} skipped={$m['skipped']} changed={$m['balance_changed']}"
                       ." bscscan={$m['bscscan_calls']} rpc_only={$m['rpc_only']} rpc_fail={$m['rpc_failures']}"]);

        return ['run_id'=>$runId,'worker_id'=>$workerId,'txs_verified'=>$verified,'cursor'=>$claim['cursor'],
                'cycle'=>$claim['cycle'],'wrapped'=>$claim['wrapped'],'batch_size'=>$claim['batch_size'],
                'priority_count'=>count($priority),'metrics'=>$m,'took_ms'=>$ms];
    }

    private function logSync(array $d)
    {
        try {
            $this->db->insert('rpc_sync_log', [
                'run_id'=>$d['run_id']??null,'scope'=>$d['scope']??'sync','address'=>$d['address']??null,
                'token'=>$d['token']??null,'endpoint'=>$d['endpoint']??null,'api_used'=>$d['api_used']??'rpc',
                'ok'=>$d['ok']??1,'diff_detected'=>$d['diff_detected']??null,
                'balance_before'=>$d['balance_before']??null,'balance_after'=>$d['balance_after']??null,
                'tx_imported'=>$d['tx_imported']??0,'message'=>isset($d['message'])?substr($d['message'],0,250):null,
                'duration_ms'=>$d['duration_ms']??null,'created_at'=>date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) { /* logging must never break sync */ }
    }
}
