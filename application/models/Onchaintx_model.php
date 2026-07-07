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

    /* ------------------------- recorder (for new txs) ---------------------- */

    /** Insert an on-chain transaction row (called by the reduction/deposit flows). */
    public function record(array $data)
    {
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
        $this->db->insert('onchain_transactions', $data);
        return (int)$this->db->insert_id();
    }
}
