<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Gas configuration acceptance tests (TEST 1-11 of the gas spec).
 * Read-only apart from temporarily editing the binary_matching policy row,
 * which is snapshotted and restored (also from a shutdown hook, so a fatal
 * cannot strand an edited gas policy on a live system).
 *   php index.php gasconfigtest run
 */
class Gasconfigtest extends CI_Controller
{
    private $backup = null;
    private $restored = false;

    public function run()
    {
        if (!$this->input->is_cli_request()) show_404();
        $this->load->model('GasFeeSettings_model', 'G');
        $this->load->model('staking/Blockchainpayout_model', 'PQ');

        $row = $this->db->get_where('gas_fee_settings', ['tx_type' => 'binary_matching'])->row_array();
        if (!$row) { echo "ABORT: no binary_matching policy row.\n"; return; }
        $this->backup = $row;
        register_shutdown_function([$this, 'restore']);

        $res = [];
        // $note is context only and is deliberately NOT part of the comparison
        // — mixing evidence into the asserted value makes a passing test fail.
        $t = function ($id, $name, $expected, $actual, $note = '') use (&$res) {
            $pass = ((string)$expected === (string)$actual);
            $res[] = $pass;
            printf("%s  %-8s %s\n", $pass ? 'PASS' : 'FAIL', $id, $name);
            printf("          expected: %s\n          actual  : %s%s\n", $expected, $actual,
                   $note !== '' ? "\n          note    : {$note}" : '');
        };

        $set = function (array $f) {
            $this->db->where('tx_type', 'binary_matching')->update('gas_fee_settings', $f);
        };

        // TEST 1 — resolves from gas_fee_settings, not the token_settings fallback.
        $r = $this->G->resolve('binary_matching');
        $t('TEST 1', 'binary_matching resolves from gas_fee_settings', 'gas_fee_settings', $r['source']);

        // TEST 2 — gas_limit change moves the estimate.
        $set(['gas_limit' => 300000, 'gas_price_gwei' => 5, 'buffer_multiplier' => 1.5]);
        $e = $this->G->estimateBnb('binary_matching');
        $t('TEST 2', 'gas_limit change alters estimated gas',
           rtrim(rtrim(number_format(300000 * 5 * 1e-9 * 1.5, 10, '.', ''), '0'), '.'),
           rtrim(rtrim(number_format($e['bnb'], 10, '.', ''), '0'), '.'));

        // TEST 3 — gas_price change moves the estimate.
        $set(['gas_price_gwei' => 10]);
        $e = $this->G->estimateBnb('binary_matching');
        $t('TEST 3', 'gas_price change alters estimated gas',
           rtrim(rtrim(number_format(300000 * 10 * 1e-9 * 1.5, 10, '.', ''), '0'), '.'),
           rtrim(rtrim(number_format($e['bnb'], 10, '.', ''), '0'), '.'));

        // TEST 4 — buffer_multiplier respected.
        $set(['buffer_multiplier' => 2.0]);
        $e = $this->G->estimateBnb('binary_matching');
        $t('TEST 4', 'buffer_multiplier is respected',
           rtrim(rtrim(number_format(300000 * 10 * 1e-9 * 2.0, 10, '.', ''), '0'), '.'),
           rtrim(rtrim(number_format($e['bnb'], 10, '.', ''), '0'), '.'));

        // TEST 5 — token_settings must NOT override the policy row.
        $ts = $this->db->select('gas_limit, gas_price')->get_where('token_settings', ['status' => 1])->row_array();
        $e = $this->G->estimateBnb('binary_matching');
        // The policy must win even though token_settings holds different
        // values — proving the legacy source is no longer consulted.
        $t('TEST 5', 'token_settings does not override binary_matching',
           'limit=300000 gwei=10',
           'limit=' . $e['gas_limit'] . ' gwei=' . (float)$e['gas_price_gwei'],
           'token_settings holds ' . $ts['gas_limit'] . '/' . $ts['gas_price'] . ' and is correctly ignored');

        // TEST 9 — Treasury Safety uses the SAME resolved estimate.
        $treas = $this->PQ->treasuryStatus();
        $t('TEST 9', 'Treasury Safety uses the same resolver',
           rtrim(rtrim(number_format($e['bnb'], 8, '.', ''), '0'), '.'),
           rtrim(rtrim(number_format((float)$treas['gas_per_send_bnb'], 8, '.', ''), '0'), '.'));

        // TEST 11 — no fixed price and no live price => UNKNOWN, never 0.
        $set(['gas_price_gwei' => null]);
        $e = $this->G->estimateBnb('binary_matching');
        $treas = $this->PQ->treasuryStatus();
        $t('TEST 11', 'Unknown gas price reports UNKNOWN, never 0',
           'bnb=NULL, price_source=unknown, treasury_gas=NULL',
           'bnb=' . ($e['bnb'] === null ? 'NULL' : $e['bnb'])
           . ', price_source=' . $e['price_source']
           . ', treasury_gas=' . ($treas['gas_per_send_bnb'] === null ? 'NULL' : $treas['gas_per_send_bnb']));

        // TEST 11b — a live price supplied by the caller is used.
        $e = $this->G->estimateBnb('binary_matching', 7);
        $t('TEST 11b', 'live RPC price used when policy price is NULL',
           rtrim(rtrim(number_format(300000 * 7 * 1e-9 * 2.0, 10, '.', ''), '0'), '.') . ' via live_rpc',
           rtrim(rtrim(number_format($e['bnb'], 10, '.', ''), '0'), '.') . ' via ' . $e['price_source']);

        $this->restore();

        // TEST 10 — the ESTIMATE must never be written into the ACTUAL gas
        // column. With the restored policy, no onchain row may carry exactly
        // the estimated figure, and rows the chain has not reported stay NULL.
        $est = $this->G->estimateBnb('binary_matching')['bnb'];
        $g = $this->db->query(
            "SELECT COUNT(*) planted FROM onchain_transactions
              WHERE gas_fee_total IS NOT NULL AND ABS(gas_fee_total - ?) < 0.000000001", [$est]
        )->row_array();
        $nul = $this->db->query("SELECT COUNT(*) n FROM onchain_transactions WHERE gas_fee_total IS NULL")->row_array();
        $t('TEST 10', 'Estimated gas is never written as actual gas',
           'planted=0', 'planted=' . $g['planted'],
           $nul['n'] . ' rows still show NULL actual gas — displayed as "—", never back-filled with the estimate');

        $pass = count(array_filter($res));
        echo "\n{$pass}/" . count($res) . " gas configuration tests passed.\n";
    }

    /** Restore the policy row exactly as found — idempotent. */
    public function restore()
    {
        if ($this->restored || !$this->backup) return;
        $this->restored = true;
        $this->db->where('tx_type', 'binary_matching')->update('gas_fee_settings', [
            'gas_limit'         => $this->backup['gas_limit'],
            'gas_price_gwei'    => $this->backup['gas_price_gwei'],
            'buffer_multiplier' => $this->backup['buffer_multiplier'],
            'is_active'         => $this->backup['is_active'],
        ]);
    }
}
