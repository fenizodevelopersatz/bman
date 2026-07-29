<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * MemberBulkBmanCron — sends the opening BMAN balance to members created by a
 * bulk upload (Admin ▸ Members Management ▸ Bulk Upload).
 *
 * Memberbulkupload_model::import() creates the accounts and leaves each row
 * that carried a `bman` amount at bman_status='pending'. This drains that
 * queue from the Treasury wallet.
 *
 * Run every few minutes (each pass claims at most
 * member_bulk_upload_settings.max_batch_size rows, so a short interval is safe):
 *   CLI  : php index.php memberbulkbmancron run
 *   HTTP : /member-bulk-bman-cron?token=<cron_token>
 *
 * A thin transport wrapper — all logic lives in Memberbulkbmancron_model::run()
 * so Cron Lab and the admin page drive the identical code path without an HTTP
 * round-trip.
 *
 * Safe by default: member_bulk_upload_settings.enabled=0 makes run() a no-op,
 * and dry_run=1 (also the default) works the queue with synthetic DRYRUN-
 * hashes and never broadcasts until an admin deliberately flips both switches.
 */
class MemberBulkBmanCron extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('member/Memberbulkbmancron_model', 'bulkbman');
    }

    public function run()
    {
        if (!is_cli()) {
            $expected = $this->config->item('cron_token');
            if (!$expected || $this->input->get('token', true) !== $expected) show_404();
        }
        @set_time_limit(0);

        $res = $this->bulkbman->run();

        $this->output->set_content_type('application/json')->set_output(json_encode($res));
    }

    public function test()
    {
        $this->load->model('member/Memberbulkupload_model', 'bulk');
        $s = $this->bulk->settings();
        echo json_encode([
            'status' => true, 'message' => 'Member Bulk BMAN Cron operational',
            'enabled' => (bool)$s['enabled'], 'dry_run' => (bool)$s['dry_run'],
            'max_batch_size' => (int)$s['max_batch_size'],
            'pending_in_queue' => $this->bulkbman->pendingCount(),
            'lock' => $this->bulkbman->state(),
            'now' => date('Y-m-d H:i:s'),
        ]);
    }
}
