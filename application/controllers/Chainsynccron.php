<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Chainsynccron — scheduled, scalable blockchain balance sync + confirmation
 * follow-up. RPC-first, cost-optimized, resumable, multi-worker safe.
 *
 * Each run:
 *   1. verify still-pending on-chain txs (retry/advance confirmations),
 *   2. sync priority (pending-tx) addresses first,
 *   3. sync the next rotation window of user addresses (cursor persists across
 *      runs/restarts; concurrent workers claim distinct windows),
 *   4. refresh the treasury address.
 * Free RPC reads balances; BscScan is called ONLY on a balance change.
 *
 *   CLI  : php index.php chainsynccron run
 *   HTTP : /chain-sync-cron?token=<cron_token>[&batch=200&worker=w1]
 *
 * Batch size is configurable in `wallet_sync_cursor.batch_size` (or ?batch=).
 * See docs/14_ONCHAIN_SYNC_LIFECYCLE.md.
 */
class Chainsynccron extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->model('Chainsync_model', 'chain');
    }

    public function run()
    {
        if (!is_cli()) {
            $expected = $this->config->item('cron_token');
            if (!$expected || $this->input->get('token', true) !== $expected) show_404();
        }

        // Stable worker id per host+process (lets several workers run concurrently).
        $worker = $this->input->get('worker', true) ?: ('w-' . substr(md5(gethostname() . '-' . getmypid()), 0, 8));
        $batch  = (int)$this->input->get('batch') ?: null;   // optional override of cursor.batch_size

        // 1–3) verify pending + priority + rotation window
        $out = $this->chain->syncBatch($worker, $batch);

        // 4) keep the treasury address fresh every run
        $cfg = $this->db->select('usdt_contract, treasury_wallet')->get_where('token_settings', ['status' => 1])->row_array() ?: [];
        if (!empty($cfg['treasury_wallet'])) {
            $this->chain->syncAddress($cfg['treasury_wallet'], 'BNB', null, 18, null, $out['run_id']);
            if (!empty($cfg['usdt_contract'])) {
                $this->chain->syncAddress($cfg['treasury_wallet'], 'USDT', $cfg['usdt_contract'], 18, null, $out['run_id']);
            }
        }

        $this->output->set_content_type('application/json')
                     ->set_output(json_encode(array_merge(['status' => 'success'], $out),
                                  JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
    }
}
