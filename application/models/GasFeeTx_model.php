<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * GasFeeTx_model — Gas fee analytics and live gas price.
 *
 * Sources: onchain_transactions (gas_fee_total, gas_used, gas_price_gwei)
 * Live gas: fetched from the active token_settings.rpc_url (eth_gasPrice).
 * All query methods are read-only and never throw.
 */
class GasFeeTx_model extends CI_Model
{
    /* ----------------------- filter helpers ----------------------- */

    private function applyFilters(array $f)
    {
        $this->db->from('onchain_transactions o');
        $this->db->join('users u', 'u.id = o.user_id', 'left');
        // A single real broadcast can have more than one onchain_transactions
        // row under the same tx_hash: the "official" tracking row _recordOnchain()
        // inserts (later gas-backfilled by Chainsync) plus one or more auto-
        // created by the generic per-credit capture hook, which never gets
        // gas-backfilled. Verified empirically (2026-08-06, all 27 real
        // multi-row tx_hashes in this DB): whichever row has gas data is always
        // the lowest id for that hash. Dedupe to MIN(id) per tx_hash so a
        // duplicate never shows as a separate, permanently-"pending" phantom
        // transaction next to the real, gas-bearing row for the same broadcast.
        // Parenthesized as one unit — without it, SQL's AND-binds-tighter-than-OR
        // precedence lets this OR "leak" past every subsequent where()/where_in()
        // call below, silently defeating has_gas/status/user_id/etc. filters.
        $this->db->where(
            "(o.id IN (SELECT MIN(id) FROM onchain_transactions WHERE tx_hash IS NOT NULL AND tx_hash != '' GROUP BY tx_hash) OR o.tx_hash IS NULL OR o.tx_hash = '')",
            null, false
        );
        // Every row here is a real broadcast attempt — it either already has a
        // confirmed gas_fee_total, or is still pending/processing and WILL cost
        // gas once confirmed. Previously this hard-filtered to confirmed-only,
        // making in-flight transactions invisible (indistinguishable from ones
        // that never touch the chain at all). Show both; the 'has_gas' filter
        // below lets an admin narrow to just one or the other.
        if (!empty($f['has_gas'])) {
            if ($f['has_gas'] === 'confirmed') {
                $this->db->where('o.gas_fee_total IS NOT NULL', null, false);
            } elseif ($f['has_gas'] === 'pending') {
                $this->db->where('o.gas_fee_total IS NULL', null, false)
                          ->where_in('o.status', ['pending', 'processing']);
            }
        }

        if (!empty($f['user_id']))     $this->db->where('o.user_id', (int)$f['user_id']);
        if (!empty($f['network']))     $this->db->where('o.network', $f['network']);
        if (!empty($f['status']))      $this->db->where('o.status', $f['status']);
        if (!empty($f['tx_type']))     $this->db->where('o.tx_type', $f['tx_type']);
        if (!empty($f['reference_type'])) $this->db->where('o.reference_type', $f['reference_type']);
        if (!empty($f['tx_hash']))     $this->db->like('o.tx_hash', $f['tx_hash']);
        if (!empty($f['date_from']))   $this->db->where('DATE(o.created_at) >=', $f['date_from']);
        if (!empty($f['date_to']))     $this->db->where('DATE(o.created_at) <=', $f['date_to']);
        if (isset($f['gas_min']) && $f['gas_min'] !== '') $this->db->where('o.gas_fee_total >=', (float)$f['gas_min']);
        if (isset($f['gas_max']) && $f['gas_max'] !== '') $this->db->where('o.gas_fee_total <=', (float)$f['gas_max']);

        if (!empty($f['search'])) {
            $s = $f['search'];
            $this->db->group_start()
                ->like('o.tx_hash', $s)
                ->or_like('o.from_address', $s)
                ->or_like('o.to_address', $s)
                ->or_like('u.username', $s);
            if (ctype_digit((string)$s)) {
                $this->db->or_where('o.user_id', (int)$s)
                         ->or_where('o.block_number', (int)$s);
            }
            $this->db->group_end();
        }
    }

    public function count(array $f = [])
    {
        $this->applyFilters($f);
        return (int)$this->db->count_all_results();
    }

    public function list(array $f = [], $limit = 50, $offset = 0)
    {
        $this->applyFilters($f);
        return $this->db
            ->select('o.id, o.tx_hash, o.user_id, u.username, o.network, o.wallet_type, o.tx_type,
                      o.gas_used, (o.gas_price / 1000000000) AS gas_price_gwei, o.gas_fee_total, o.status,
                      o.block_number, o.amount, o.token_symbol, o.from_address, o.to_address,
                      o.reference_type, o.reference_id, o.method_name, o.failure_reason, o.revert_message,
                      o.created_at', false)
            ->order_by('o.created_at', 'DESC')
            ->limit((int)$limit, (int)$offset)
            ->get()->result_array();
    }

    /* ----------------------- aggregate stats ---------------------- */

    /**
     * Gas stats for the dashboard / summary cards.
     * Returns today's gas, this month's gas, avg gas price, failed count.
     */
    public function gasStats()
    {
        $today = date('Y-m-d');
        $monthStart = date('Y-m-01');

        $todayRow = $this->db->query(
            "SELECT COALESCE(SUM(gas_fee_total),0) AS total, COUNT(*) AS cnt
             FROM onchain_transactions
             WHERE DATE(created_at) = ? AND gas_fee_total IS NOT NULL",
            [$today]
        )->row_array();

        $monthRow = $this->db->query(
            "SELECT COALESCE(SUM(gas_fee_total),0) AS total, COUNT(*) AS cnt
             FROM onchain_transactions
             WHERE DATE(created_at) >= ? AND gas_fee_total IS NOT NULL",
            [$monthStart]
        )->row_array();

        $avgRow = $this->db->query(
            "SELECT COALESCE(AVG(gas_price / 1000000000),0) AS avg_gwei
             FROM onchain_transactions
             WHERE gas_price IS NOT NULL AND DATE(created_at) >= ?",
            [$monthStart]
        )->row_array();

        $failedToday = (int)$this->db->query(
            "SELECT COUNT(*) AS cnt FROM onchain_transactions
             WHERE status IN ('failed','reverted') AND DATE(created_at) = ?",
            [$today]
        )->row()->cnt;

        return [
            'gas_today_bnb'    => (float)($todayRow['total'] ?? 0),
            'gas_today_count'  => (int)($todayRow['cnt'] ?? 0),
            'gas_month_bnb'    => (float)($monthRow['total'] ?? 0),
            'gas_month_count'  => (int)($monthRow['cnt'] ?? 0),
            'avg_gas_gwei'     => round((float)($avgRow['avg_gwei'] ?? 0), 2),
            'failed_today'     => $failedToday,
        ];
    }

    /**
     * Filter dropdown options for the gas fee page.
     */
    public function filterOptions()
    {
        $col = function ($c) {
            return array_values(array_filter(array_map(
                function ($r) use ($c) { return $r[$c]; },
                $this->db->distinct()->select($c)
                    ->where("$c IS NOT NULL", null, false)
                    ->order_by($c, 'ASC')
                    ->get('onchain_transactions')->result_array()
            ), function ($v) { return $v !== null && $v !== ''; }));
        };
        return [
            'networks'        => $col('network'),
            'statuses'        => ['pending','processing','confirmed','failed','reverted','partial','cancelled'],
            'types'           => $col('tx_type'),
            'reference_types' => $col('reference_type'),
            'has_gas'         => ['confirmed' => 'Gas confirmed', 'pending' => 'Gas pending'],
        ];
    }

    /* ----------------------- live gas from RPC -------------------- */

    /**
     * Fetch current BSC gas price from configured RPC.
     * Returns ['available'=>true, 'slow'=>.., 'standard'=>.., 'fast'=>.., 'gwei'=>..]
     * or ['available'=>false, 'reason'=>..] on failure.
     * Never throws.
     */
    public function liveGasPrice()
    {
        try {
            $ts = $this->db->get_where('token_settings', ['status' => 1])->row_array();
            if (!$ts || empty($ts['rpc_url'])) {
                return ['available' => false, 'reason' => 'No active RPC configured in token_settings'];
            }
            $rpc = $ts['rpc_url'];

            $raw = $this->_rpc($rpc, 'eth_gasPrice', []);
            if (!$raw) {
                return ['available' => false, 'reason' => 'RPC call failed or timed out'];
            }

            // raw is hex string
            $wei    = gmp_strval(gmp_init($raw, 16), 10);
            $gwei   = bcdiv($wei, '1000000000', 2);
            $gweiF  = (float)$gwei;

            return [
                'available' => true,
                'gwei'      => $gweiF,
                'slow'      => round($gweiF * 0.8, 2),
                'standard'  => $gweiF,
                'fast'      => round($gweiF * 1.25, 2),
                'wei'       => $wei,
                'rpc'       => $rpc,
            ];
        } catch (Throwable $e) {
            log_message('error', '[GasFeeTx_model::liveGasPrice] ' . $e->getMessage());
            return ['available' => false, 'reason' => 'Exception: ' . $e->getMessage()];
        }
    }

    /** One read-only JSON-RPC call. Returns decoded result or null. */
    private function _rpc($url, $method, array $params = [])
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode(['jsonrpc'=>'2.0','id'=>1,'method'=>$method,'params'=>$params]),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $raw = curl_exec($ch);
        curl_close($ch);
        if ($raw === false) return null;
        $j = json_decode($raw, true);
        return (is_array($j) && array_key_exists('result', $j)) ? $j['result'] : null;
    }

    /* ----------------------- export helpers ----------------------- */

    public function exportList(array $f = [], $limit = 10000)
    {
        return $this->list($f, $limit, 0);
    }
}
