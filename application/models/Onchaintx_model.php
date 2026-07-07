<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Onchaintx_model — the queryable source for Admin ▸ Finance ▸ On-Chain Transactions.
 * ---------------------------------------------------------------------------
 * Server-side filtering / sorting / pagination over `onchain_transactions`
 * (indexed for large volumes), plus live-from-RPC enrichment for the detail
 * modal (gas used, nonce, tx index, block, confirmations, logs, status) that
 * the stored row does not carry.
 *
 * See docs/13_ONCHAIN_TRANSACTIONS.md.
 */
class Onchaintx_model extends CI_Model
{
    /** Columns a client may sort by (whitelist — never trust raw input in ORDER BY). */
    private $sortable = [
        'created_at', 'amount', 'block_number', 'confirmation_count',
        'gas_fee_total', 'status', 'wallet_type', 'tx_type', 'id',
    ];

    /* ----------------------- dashboard: 5 wallet balances ------------------ */

    /** Platform-wide balance per wallet type (sum of every user's wallet). */
    public function walletTotals()
    {
        $row = $this->db->select("
            COALESCE(SUM(usd_balance),0)      AS usdt,
            COALESCE(SUM(exchange_balance),0) AS exchange,
            COALESCE(SUM(earning_balance),0)  AS earning,
            COALESCE(SUM(staking_balance),0)  AS staking,
            COALESCE(SUM(bonus_balance),0)    AS bonus", false)
            ->get('user_wallets')->row_array();
        return $row ?: ['usdt'=>0,'exchange'=>0,'earning'=>0,'staking'=>0,'bonus'=>0];
    }

    /** Distinct values to populate the filter dropdowns. */
    public function filterOptions()
    {
        $col = function ($c) {
            return array_values(array_filter(array_map(
                function ($r) use ($c) { return $r[$c]; },
                $this->db->distinct()->select($c)->where("$c IS NOT NULL", null, false)
                    ->order_by($c, 'ASC')->get('onchain_transactions')->result_array()
            ), function ($v) { return $v !== null && $v !== ''; }));
        };
        return [
            'wallets'  => ['usdt','exchange','earning','staking','bonus'],
            'networks' => $col('network'),
            'statuses' => ['pending','processing','confirmed','failed','reverted','partial','cancelled'],
            'types'    => $col('tx_type'),
            'tokens'   => $col('token_symbol'),
        ];
    }

    /* ------------------------- server-side grid query ---------------------- */

    private function applyFilters(array $f)
    {
        $this->db->from('onchain_transactions o');

        if (!empty($f['wallet']))      $this->db->where('o.wallet_type', $f['wallet']);
        if (!empty($f['network']))     $this->db->where('o.network', $f['network']);
        if (!empty($f['status']))      $this->db->where('o.status', $f['status']);
        if (!empty($f['tx_type']))     $this->db->where('o.tx_type', $f['tx_type']);
        if (!empty($f['token']))       $this->db->where('o.token_symbol', $f['token']);
        if (!empty($f['user_id']))     $this->db->where('o.user_id', (int)$f['user_id']);
        if (!empty($f['block_number']))$this->db->where('o.block_number', (int)$f['block_number']);
        if (!empty($f['tx_hash']))     $this->db->like('o.tx_hash', $f['tx_hash']);
        if (!empty($f['reference_id']))$this->db->like('o.reference_id', $f['reference_id']);

        if (!empty($f['wallet_address'])) {
            $a = $f['wallet_address'];
            $this->db->group_start()->like('o.from_address', $a)->or_like('o.to_address', $a)->group_end();
        }
        if (!empty($f['date_from'])) $this->db->where('DATE(o.created_at) >=', $f['date_from']);
        if (!empty($f['date_to']))   $this->db->where('DATE(o.created_at) <=', $f['date_to']);
        if (isset($f['gas_min']) && $f['gas_min'] !== '') $this->db->where('o.gas_fee_total >=', (float)$f['gas_min']);
        if (isset($f['gas_max']) && $f['gas_max'] !== '') $this->db->where('o.gas_fee_total <=', (float)$f['gas_max']);

        // Free-text search across tx hash / addresses / user id / block / reference.
        if (!empty($f['search'])) {
            $s = $f['search'];
            $this->db->group_start()
                ->like('o.tx_hash', $s)
                ->or_like('o.from_address', $s)
                ->or_like('o.to_address', $s)
                ->or_like('o.reference_id', $s);
            if (ctype_digit((string)$s)) {
                $this->db->or_where('o.user_id', (int)$s)->or_where('o.block_number', (int)$s);
            }
            $this->db->group_end();
        }
    }

    public function count(array $f = [])
    {
        $this->applyFilters($f);
        return (int)$this->db->count_all_results();
    }

    /** @return array rows joined to the user for display. */
    public function filter(array $f = [], $limit = 25, $offset = 0, $sort = 'created_at', $dir = 'DESC')
    {
        $sort = in_array($sort, $this->sortable, true) ? $sort : 'created_at';
        $dir  = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';

        $this->applyFilters($f);
        return $this->db
            ->select('o.*, u.username, u.email, u.referral_id', false)
            ->join('users u', 'u.id = o.user_id', 'left')
            ->order_by("o.$sort", $dir)
            ->limit((int)$limit, (int)$offset)
            ->get()->result_array();
    }

    /* ---------------------------- single tx detail ------------------------- */

    public function get($id)
    {
        return $this->db->select('o.*, u.username, u.email, u.referral_id', false)
            ->from('onchain_transactions o')
            ->join('users u', 'u.id = o.user_id', 'left')
            ->where('o.id', (int)$id)
            ->get()->row_array();
    }

    /**
     * Live blockchain enrichment for the modal — everything the standard RPC and
     * receipt expose. Fields a plain RPC cannot give (internal txs, execution
     * trace, decoded params, ABI event names) are returned as null with a
     * `needs` hint. Safe/read-only; never throws (returns ['available'=>false]).
     */
    public function enrichFromChain($tx_hash)
    {
        if (empty($tx_hash)) return ['available' => false, 'reason' => 'no tx hash (internal-only transaction)'];

        $ts = $this->db->get_where('token_settings', ['status' => 1])->row_array();
        if (!$ts || empty($ts['rpc_url'])) return ['available' => false, 'reason' => 'no active Token Settings RPC'];
        $rpc = $ts['rpc_url'];

        $tx = $this->rpc($rpc, 'eth_getTransactionByHash', [$tx_hash]);
        if (!$tx) return ['available' => false, 'reason' => 'transaction not found on-chain (pending or wrong network)'];
        $rc  = $this->rpc($rpc, 'eth_getTransactionReceipt', [$tx_hash]);
        $head = $this->rpc($rpc, 'eth_blockNumber', []);

        $hx = function ($h) { return ($h === null || $h === '') ? null : gmp_strval(gmp_init($h, 16), 10); };

        $gasUsed  = $rc ? $hx($rc['gasUsed'] ?? null) : null;
        $gasPrice = $hx($tx['gasPrice'] ?? null);                 // wei
        $blockNo  = $hx($tx['blockNumber'] ?? null);
        $curBlock = $hx($head);
        $confs    = ($blockNo !== null && $curBlock !== null) ? (string)($curBlock - $blockNo) : null;
        $feeWei   = ($gasUsed !== null && $gasPrice !== null) ? bcmul($gasUsed, $gasPrice, 0) : null;

        // status: receipt.status 0x1 success / 0x0 reverted
        $recStatus = $rc ? ($rc['status'] ?? null) : null;
        $chainStatus = $recStatus === '0x1' ? 'confirmed' : ($recStatus === '0x0' ? 'reverted' : 'pending');

        // method selector (first 4 bytes of input) → known name where possible
        $input = $tx['input'] ?? '0x';
        $selector = (strlen($input) >= 10) ? substr($input, 0, 10) : null;
        $known = [
            '0xa9059cbb' => 'transfer(address,uint256)',
            '0x23b872dd' => 'transferFrom(address,address,uint256)',
            '0x095ea7b3' => 'approve(address,uint256)',
        ];
        $methodSig = $selector ? ($known[$selector] ?? null) : null;

        return [
            'available'         => true,
            'from'              => $tx['from'] ?? null,
            'to'                => $tx['to'] ?? null,
            'nonce'             => $hx($tx['nonce'] ?? null),
            'tx_index'          => $hx($tx['transactionIndex'] ?? null),
            'block_number'      => $blockNo,
            'confirmation_count'=> $confs,
            'value_wei'         => $hx($tx['value'] ?? null),
            'gas_limit'         => $hx($tx['gas'] ?? null),
            'gas_used'          => $gasUsed,
            'gas_price_wei'     => $gasPrice,
            'gas_price_gwei'    => $gasPrice !== null ? bcdiv($gasPrice, '1000000000', 9) : null,
            'max_fee_per_gas'   => $hx($tx['maxFeePerGas'] ?? null),
            'max_priority_fee'  => $hx($tx['maxPriorityFeePerGas'] ?? null),
            'gas_fee_bnb'       => $feeWei !== null ? bcdiv($feeWei, '1000000000000000000', 18) : null,
            'chain_status'      => $chainStatus,
            'contract_address'  => $tx['to'] ?? null,
            'input_selector'    => $selector,
            'method_signature'  => $methodSig,
            'input_data'        => $input,
            'logs_count'        => $rc ? count($rc['logs'] ?? []) : 0,
            'logs'              => $rc ? array_slice($rc['logs'] ?? [], 0, 20) : [],
            'return_status_raw' => $recStatus,
            // fields a plain RPC cannot provide:
            'internal_txs'      => null,
            'execution_trace'   => null,
            'decoded_params'    => null,
            'needs'             => 'internal transactions, execution trace and decoded event/param names require a BscScan API key or a debug/trace-capable archive node',
        ];
    }

    /** One read-only JSON-RPC call over curl. Returns decoded result or null. */
    private function rpc($url, $method, array $params = [])
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['jsonrpc'=>'2.0','id'=>1,'method'=>$method,'params'=>$params]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $raw = curl_exec($ch);
        curl_close($ch);
        if ($raw === false) return null;
        $j = json_decode($raw, true);
        return (is_array($j) && array_key_exists('result', $j)) ? $j['result'] : null;
    }

    /* ============================ recorder API ============================ *
     * All methods below are FAIL-SAFE: they never throw. Recording an on-chain
     * transaction must never break the real balance movement that triggered it.
     * ---------------------------------------------------------------------- */

    /**
     * Capture a new on-chain transaction (+ a 'created' audit event).
     * Idempotent for on-chain rows: if a row with the same (tx_hash, wallet_type,
     * reference_type) already exists it is returned instead of duplicated.
     * @return int|false inserted/existing id, or false on any failure.
     */
    public function capture(array $data)
    {
        try {
            $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');

            if (!empty($data['tx_hash'])) {
                $dupe = $this->db->select('id')
                    ->where('tx_hash', $data['tx_hash'])
                    ->where('wallet_type', $data['wallet_type'] ?? null)
                    ->where('reference_type', $data['reference_type'] ?? null)
                    ->get('onchain_transactions')->row_array();
                if ($dupe) return (int)$dupe['id'];
            }

            $event = null;
            if (isset($data['_event'])) { $event = $data['_event']; unset($data['_event']); }

            $this->db->insert('onchain_transactions', $data);
            $id = (int)$this->db->insert_id();
            $this->logEvent($id, $data['tx_hash'] ?? null, 'created', [
                'new_status' => $data['status'] ?? null,
                'actor_type' => $event['actor_type'] ?? 'system',
                'actor_id'   => $event['actor_id'] ?? null,
                'ip_address' => $event['ip_address'] ?? null,
                'detail'     => $event['detail'] ?? null,
            ]);
            return $id;
        } catch (Throwable $e) {
            log_message('error', '[onchain capture] '.$e->getMessage());
            return false;
        }
    }

    /**
     * Attach on-chain result (tx hash, status, gas…) to the row already created
     * for a given wallet_ledger_id, and log a status_change event.
     */
    public function updateByLedgerId($ledgerId, array $fields, array $event = [])
    {
        try {
            if (!$ledgerId) return false;
            $row = $this->db->select('id, status, tx_hash')
                ->get_where('onchain_transactions', ['wallet_ledger_id' => (int)$ledgerId])->row_array();
            if (!$row) return false;

            $fields['updated_at'] = date('Y-m-d H:i:s');
            $this->db->where('id', $row['id'])->update('onchain_transactions', $fields);

            $this->logEvent($row['id'], $fields['tx_hash'] ?? $row['tx_hash'], 'status_change', [
                'old_status'    => $row['status'],
                'new_status'    => $fields['status'] ?? $row['status'],
                'confirmations' => $fields['confirmation_count'] ?? null,
                'actor_type'    => $event['actor_type'] ?? 'system',
                'actor_id'      => $event['actor_id'] ?? null,
                'detail'        => $event['detail'] ?? null,
            ]);
            return (int)$row['id'];
        } catch (Throwable $e) {
            log_message('error', '[onchain updateByLedgerId] '.$e->getMessage());
            return false;
        }
    }

    /**
     * Insert-or-update by (reference_type, reference_id) — for lifecycle rows
     * whose status transitions over time (e.g. a deposit pending→confirmed).
     */
    public function upsertByReference($refType, $refId, array $data, array $event = [])
    {
        try {
            $existing = $this->db->select('id, status')
                ->get_where('onchain_transactions', ['reference_type' => $refType, 'reference_id' => $refId])
                ->row_array();

            if ($existing) {
                $data['updated_at'] = date('Y-m-d H:i:s');
                $this->db->where('id', $existing['id'])->update('onchain_transactions', $data);
                if (isset($data['status']) && $data['status'] !== $existing['status']) {
                    $this->logEvent($existing['id'], $data['tx_hash'] ?? null, 'status_change', [
                        'old_status' => $existing['status'], 'new_status' => $data['status'],
                        'confirmations' => $data['confirmation_count'] ?? null,
                        'actor_type' => $event['actor_type'] ?? 'system', 'actor_id' => $event['actor_id'] ?? null,
                    ]);
                }
                return (int)$existing['id'];
            }
            $data['reference_type'] = $refType;
            $data['reference_id']   = $refId;
            if (!empty($event)) $data['_event'] = $event;
            return $this->capture($data);
        } catch (Throwable $e) {
            log_message('error', '[onchain upsertByReference] '.$e->getMessage());
            return false;
        }
    }

    /** Append one immutable audit event. Never throws. */
    public function logEvent($txId, $txHash, $eventType, array $o = [])
    {
        try {
            $this->db->insert('onchain_tx_events', [
                'tx_id'         => (int)$txId,
                'tx_hash'       => $txHash,
                'event_type'    => $eventType,
                'old_status'    => $o['old_status'] ?? null,
                'new_status'    => $o['new_status'] ?? null,
                'confirmations' => $o['confirmations'] ?? null,
                'detail'        => isset($o['detail']) ? (is_array($o['detail']) ? json_encode($o['detail']) : $o['detail']) : null,
                'actor_type'    => $o['actor_type'] ?? 'system',
                'actor_id'      => $o['actor_id'] ?? null,
                'ip_address'    => $o['ip_address'] ?? null,
                'created_at'    => date('Y-m-d H:i:s'),
            ]);
            return (int)$this->db->insert_id();
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Attach a swap's on-chain delivery hash to the swap credit row(s) already
     * recorded by the ledger observer (matched by reference_id = swap ref).
     * Fail-safe. Also logs a 'linked' + 'broadcast' audit event.
     */
    public function attachSwapDelivery($ref, $deliveryHash, $requestHash = null, $endpoint = null)
    {
        try {
            if (empty($ref)) return false;
            $rows = $this->db->select('id')
                ->where('reference_id', $ref)
                ->where_in('reference_type', ['swap', 'swap_bonus'])
                ->get('onchain_transactions')->result_array();
            foreach ($rows as $r) {
                $this->db->where('id', $r['id'])->update('onchain_transactions', [
                    'delivery_tx_hash' => $deliveryHash,
                    'request_tx_hash'  => $requestHash,
                    'tx_hash'          => $deliveryHash ?: null,
                    'rpc_endpoint'     => $endpoint,
                    'updated_at'       => date('Y-m-d H:i:s'),
                ]);
                $this->logEvent($r['id'], $deliveryHash, 'broadcast', [
                    'detail' => 'swap BMAN delivery broadcast', 'actor_type' => 'system', 'rpc_endpoint' => $endpoint,
                ]);
            }
            return count($rows);
        } catch (Throwable $e) {
            log_message('error', '[onchain attachSwapDelivery] '.$e->getMessage());
            return false;
        }
    }

    /** The audit trail for one transaction (immutable, oldest first). */
    public function events($txId)
    {
        return $this->db->where('tx_id', (int)$txId)->order_by('id', 'ASC')
            ->get('onchain_tx_events')->result_array();
    }

    /** Back-compat alias. */
    public function record(array $data) { return $this->capture($data); }
}
