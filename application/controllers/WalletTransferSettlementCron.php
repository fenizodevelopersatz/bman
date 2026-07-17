<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * WalletTransferSettlementCron — sweeps wallet_internal_transfer rows queued
 * for on-chain settlement by Wallettransferservice_model::execute() and sends
 * real BMAN from the Treasury wallet to each resolved destination address.
 *
 * Run frequently (each pass only claims up to
 * wallet_transfer_settlement_settings.max_batch_size rows, so a short
 * interval is safe):
 *   CLI  : php index.php wallettransfersettlementcron run
 *   HTTP : /wallet-transfer-settlement-cron?token=<cron_token>
 *
 * A thin transport wrapper — all logic lives in
 * Walletsettlementcron_model::run() so Cron Lab and admin pages drive the
 * identical code path without an HTTP round-trip.
 *
 * Safe by default: wallet_transfer_settlement_settings.enabled=0 makes run()
 * a no-op, and dry_run=1 (also the default) works the queue with synthetic
 * DRYRUN- hashes and never broadcasts until an admin deliberately flips both
 * switches.
 */
class WalletTransferSettlementCron extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('wallet/Walletsettlementcron_model', 'settlecron');
    }

    public function run()
    {
        if (!is_cli()) {
            $expected = $this->config->item('cron_token');
            if (!$expected || $this->input->get('token', true) !== $expected) show_404();
        }
        @set_time_limit(0);

        $res = $this->settlecron->run();

        $this->output->set_content_type('application/json')->set_output(json_encode($res));
    }

    public function test()
    {
        $s = $this->settlecron->settings();
        $pending = $this->db->where('settlement_status', 'pending')->count_all_results('wallet_internal_transfer');
        echo json_encode([
            'status' => true, 'message' => 'Wallet Transfer Settlement Cron operational',
            'enabled' => (bool)$s['enabled'], 'dry_run' => (bool)$s['dry_run'],
            'pending_in_queue' => $pending, 'now' => date('Y-m-d H:i:s'),
        ]);
    }
}
