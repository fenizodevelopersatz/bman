<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * RoiMonitor — Admin dashboard for ROI maturity cron monitoring.
 *
 * Shows:
 * - ROI transfer statistics (pending, confirmed, failed)
 * - Recent transfer attempts (grid with tx_hash, status, date)
 * - Transfer retry interface
 * - Manual ROI broadcast testing
 */
class RoiMonitor extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('RoiMaturity_model', 'roi');
        $this->load->library('api_response');
        // Add admin auth check here if needed
    }

    /**
     * Main dashboard view
     */
    public function index()
    {
        $data = [];

        // Get ROI transfer statistics
        $data['stats'] = $this->roi->getTransferStats();

        // Get recent transfers
        $data['recent_transfers'] = $this->db
            ->select('srp.id, srp.user_id, srp.amount, srp.credit_date, srp.transfer_status,
                      srp.tx_hash, srp.transferred_at, u.username, sp.name as package_name')
            ->from('staking_roi_payouts srp')
            ->join('users u', 'u.id = srp.user_id', 'left')
            ->join('staking_packages sp', 'sp.id = us.package_id', 'left')
            ->join('user_stakes us', 'us.id = srp.stake_id', 'left')
            ->where('srp.transfer_status IS NOT NULL', null, false)
            ->order_by('srp.transferred_at', 'DESC')
            ->limit(50)
            ->get()
            ->result_array();

        // Get failed transfers
        $data['failed_transfers'] = $this->db
            ->select('srp.id, srp.user_id, srp.amount, srp.error_message, u.username')
            ->from('staking_roi_payouts srp')
            ->join('users u', 'u.id = srp.user_id', 'left')
            ->where('srp.transfer_status', 'failed')
            ->order_by('srp.created_at', 'DESC')
            ->limit(20)
            ->get()
            ->result_array();

        // Get pending broadcast ROIs
        $data['pending_broadcast'] = $this->db
            ->select('srp.id, srp.user_id, srp.amount, srp.credit_date, u.username')
            ->from('staking_roi_payouts srp')
            ->join('users u', 'u.id = srp.user_id', 'left')
            ->where('srp.status', 'pending')
            ->where('(srp.transfer_status IS NULL OR srp.transfer_status = "pending_broadcast")', null, false)
            ->where('srp.credit_date <=', date('Y-m-d'))
            ->order_by('srp.credit_date', 'ASC')
            ->limit(20)
            ->get()
            ->result_array();

        $this->load->view('admin/staking/roi_monitor', $data);
    }

    /**
     * API endpoint: Get transfer statistics (JSON)
     */
    public function api_stats()
    {
        $stats = $this->roi->getTransferStats();
        $this->api_response->success($stats);
    }

    /**
     * API endpoint: Get recent transfers grid (JSON)
     */
    public function api_recent()
    {
        $limit = (int)$this->input->get('limit', true) ?? 50;
        $offset = (int)$this->input->get('offset', true) ?? 0;

        $transfers = $this->db
            ->select('srp.id, srp.user_id, srp.amount, srp.credit_date, srp.transfer_status,
                      srp.tx_hash, srp.transferred_at, srp.block_number, srp.confirmation_count,
                      u.username, sp.name as package_name')
            ->from('staking_roi_payouts srp')
            ->join('users u', 'u.id = srp.user_id', 'left')
            ->join('staking_packages sp', 'sp.id = us.package_id', 'left')
            ->join('user_stakes us', 'us.id = srp.stake_id', 'left')
            ->where('srp.transfer_status IS NOT NULL', null, false)
            ->order_by('srp.transferred_at', 'DESC')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->result_array();

        $total = $this->db
            ->select('COUNT(*) as cnt')
            ->from('staking_roi_payouts')
            ->where('transfer_status IS NOT NULL', null, false)
            ->get()
            ->row_array()['cnt'];

        $this->api_response->success([
            'transfers' => $transfers,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
        ]);
    }

    /**
     * API endpoint: Get failed transfers (JSON)
     */
    public function api_failed()
    {
        $failed = $this->db
            ->select('srp.id, srp.user_id, srp.amount, srp.error_message, srp.created_at, u.username')
            ->from('staking_roi_payouts srp')
            ->join('users u', 'u.id = srp.user_id', 'left')
            ->where('srp.transfer_status', 'failed')
            ->order_by('srp.created_at', 'DESC')
            ->limit(50)
            ->get()
            ->result_array();

        $this->api_response->success(['transfers' => $failed]);
    }

    /**
     * API endpoint: Retry a failed transfer
     * POST roi_id
     */
    public function api_retry_single()
    {
        $roiId = (int)$this->input->post('roi_id', true);
        if (!$roiId) {
            $this->api_response->error('Missing roi_id', 400);
            return;
        }

        $roi = $this->db->get_where('staking_roi_payouts', ['id' => $roiId])->row_array();
        if (!$roi) {
            $this->api_response->error('ROI not found', 404);
            return;
        }

        if ($roi['status'] !== 'pending') {
            $this->api_response->error('ROI is not pending (already paid or cancelled)', 400);
            return;
        }

        try {
            // Reset to pending_broadcast to allow retry
            $this->db->update('staking_roi_payouts', [
                'transfer_status' => 'pending_broadcast',
                'error_message' => null,
            ], ['id' => $roiId]);

            // Re-attempt
            $this->roi->processSingleRoi($roi);
            $this->api_response->success(['roi_id' => $roiId, 'message' => 'Retry initiated']);

        } catch (Exception $e) {
            $this->api_response->error('Retry failed: '.$e->getMessage(), 500);
        }
    }

    /**
     * API endpoint: Retry all failed transfers
     */
    public function api_retry_all()
    {
        $result = $this->roi->retryFailedTransfers();
        $this->api_response->success($result);
    }

    /**
     * API endpoint: Mark ROI as reverted (don't retry)
     * POST roi_id, reason
     */
    public function api_mark_reverted()
    {
        $roiId = (int)$this->input->post('roi_id', true);
        $reason = $this->input->post('reason', true) ?? '';

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

    /**
     * API endpoint: View ROI transfer audit log
     * GET roi_id or user_id
     */
    public function api_audit_log()
    {
        $roiId = (int)$this->input->get('roi_id', true);
        $userId = (int)$this->input->get('user_id', true);

        $query = $this->db
            ->select('*')
            ->from('staking_roi_transfer_log')
            ->order_by('created_at', 'DESC');

        if ($roiId) {
            $query->where('roi_payout_id', $roiId);
        } elseif ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->limit(100);
        }

        $log = $query->get()->result_array();
        $this->api_response->success(['log' => $log]);
    }

    /**
     * View: Display transfer details
     * GET transfer_id
     */
    public function view_transfer()
    {
        $transferId = (int)$this->input->get('id', true);
        if (!$transferId) show_404();

        $transfer = $this->db->get_where('staking_roi_payouts', ['id' => $transferId])->row_array();
        if (!$transfer) show_404();

        // Get associated log entries
        $log = $this->db
            ->select('*')
            ->from('staking_roi_transfer_log')
            ->where('roi_payout_id', $transferId)
            ->order_by('created_at', 'DESC')
            ->get()
            ->result_array();

        // Get on-chain transaction if hash exists
        $onchain = null;
        if ($transfer['tx_hash']) {
            $onchain = $this->db->get_where('onchain_transactions', ['tx_hash' => $transfer['tx_hash']])->row_array();
        }

        $data = [
            'transfer' => $transfer,
            'log' => $log,
            'onchain' => $onchain,
        ];

        $this->load->view('admin/staking/roi_transfer_detail', $data);
    }

    /**
     * Test endpoint: Manually trigger ROI processing
     * Useful for testing; requires admin auth
     */
    public function test_process()
    {
        // Add admin auth check here
        try {
            $result = $this->roi->processMaturityCycle();
            echo json_encode($result) . PHP_EOL;
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]) . PHP_EOL;
        }
    }

    /**
     * Test endpoint: Manually trigger confirmation sync
     */
    public function test_sync_confirmations()
    {
        // Add admin auth check here
        try {
            $result = $this->roi->syncConfirmationsFromChain();
            echo json_encode($result) . PHP_EOL;
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]) . PHP_EOL;
        }
    }
}
