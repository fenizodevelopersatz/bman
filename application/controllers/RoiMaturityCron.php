<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * RoiMaturityCron — Entry point for ROI maturity detection and on-chain broadcasting.
 *
 * Runs daily (or as frequently as needed) to:
 *   1. Detect ROIs with credit_date <= TODAY
 *   2. Sign and broadcast blockchain transfers from Treasury to earning wallets
 *   3. Store tx hashes for confirmation tracking
 *   4. Handle retries for failed broadcasts
 *
 * Trigger via:
 *   CLI  : php index.php roimaturitycron run
 *   HTTP : /roi-maturity-cron?token=YOUR_CRON_TOKEN
 */
class RoiMaturityCron extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('RoiMaturity_model', 'roi');
    }

    /**
     * Main entry point for ROI maturity processing.
     *
     * Flow:
     *   1. Sync confirmations from prior broadcasts (pending_confirmation → confirmed)
     *   2. Process new mature ROIs (detect & broadcast)
     *   3. Optionally retry failed transfers
     */
    public function run()
    {
        // CLI always allowed; HTTP requires cron_token
        if (!is_cli()) {
            $expected = $this->config->item('cron_token');
            if (!$expected || $this->input->get('token', true) !== $expected) {
                show_404();
            }
        }

        $start = microtime(true);
        $output = ['status' => 'error', 'ran_at' => date('Y-m-d H:i:s')];

        try {
            // Step 1: Sync confirmations from ChainSync (requires prior ChainSync run)
            $syncResult = $this->roi->syncConfirmationsFromChain();
            $output['sync_confirmed'] = $syncResult['confirmed'];
            $output['sync_failed'] = $syncResult['failed'];

            // Step 2: Process mature ROIs
            $result = $this->roi->processMaturityCycle();
            $output['status'] = $result['ok'] ? 'success' : 'error';
            $output['message'] = $result['message'];
            $output['processed'] = $result['processed'];
            $output['failed'] = $result['failed'];
            if (!empty($result['errors'])) {
                $output['errors'] = $result['errors'];
            }

            // Step 3: Optionally retry failed transfers
            if ($this->input->get('retry') === 'true') {
                $retryResult = $this->roi->retryFailedTransfers();
                $output['retry_result'] = $retryResult;
            }

            $output['duration_ms'] = round((microtime(true) - $start) * 1000, 2);

        } catch (Exception $e) {
            $output['status'] = 'error';
            $output['message'] = $e->getMessage();
            $output['duration_ms'] = round((microtime(true) - $start) * 1000, 2);
        }

        echo json_encode($output) . PHP_EOL;
    }

    /**
     * Retry failed ROI transfers.
     * Useful to call separately from the main cycle.
     */
    public function retry()
    {
        if (!is_cli()) {
            $expected = $this->config->item('cron_token');
            if (!$expected || $this->input->get('token', true) !== $expected) {
                show_404();
            }
        }

        try {
            $result = $this->roi->retryFailedTransfers();
            $output = [
                'status'    => 'success',
                'result'    => $result,
                'ran_at'    => date('Y-m-d H:i:s'),
            ];
        } catch (Exception $e) {
            $output = [
                'status'    => 'error',
                'message'   => $e->getMessage(),
                'ran_at'    => date('Y-m-d H:i:s'),
            ];
        }

        echo json_encode($output) . PHP_EOL;
    }

    /**
     * Admin dashboard: get ROI transfer statistics.
     */
    public function stats()
    {
        $this->load->library('api_response');
        $stats = $this->roi->getTransferStats();
        $this->api_response->success($stats);
    }

    /**
     * Admin action: mark a failed ROI as reverted (won't be retried).
     */
    public function mark_reverted()
    {
        $this->load->library('api_response');

        $roiId = $this->input->get('roi_id', true);
        $reason = $this->input->get('reason', true) ?? '';

        if (!$roiId) {
            $this->api_response->error('Missing roi_id', 400);
            return;
        }

        try {
            $this->roi->markAsReverted($roiId, $reason);
            $this->api_response->success(['roi_id' => $roiId, 'status' => 'reverted']);
        } catch (Exception $e) {
            $this->api_response->error($e->getMessage(), 500);
        }
    }
}
