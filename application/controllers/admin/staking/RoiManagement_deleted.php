<?php defined('BASEPATH') OR exit('No direct script access allowed');

class RoiManagement extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->model('Admin_model');
        $this->load->model('RoiAudit_model');

        if (!$this->session->userdata('admin_logged_in')) redirect('admin/login');
    }

    public function index()
    {
        $page = max(1, (int)$this->input->get('page', true) ?? 1);
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $filters = [
            'user_id'   => $this->input->get('user_id', true),
            'plan_type' => $this->input->get('plan_type', true),
            'roi_type'  => $this->input->get('roi_type', true),
            'status'    => $this->input->get('status', true),
            'from_date' => $this->input->get('from_date', true),
            'to_date'   => $this->input->get('to_date', true)
        ];

        // Remove empty filters
        $filters = array_filter($filters, function($v) { return $v !== '' && $v !== null; });

        $data = [
            'title' => 'ROI Management',
            'page' => $page,
            'limit' => $limit,
            'filters' => $filters,
            'roi_history' => $this->RoiAudit_model->getROIHistory($filters, $limit, $offset),
            'total_records' => $this->RoiAudit_model->countROIHistory($filters),
            'upcoming_maturity' => $this->RoiAudit_model->getUpcomingMaturityDates(),
            'missed_executions' => $this->RoiAudit_model->getMissedExecutions(),
            'roi_summary' => $this->RoiAudit_model->getROISummaryByDate()
        ];

        $this->load->view('admin/staking/roi_management', $data);
    }

    /**
     * Validate and re-distribute missed ROI
     */
    public function validate_and_retry()
    {
        if (!$this->input->is_ajax_request()) show_404();

        try {
            $pending = $this->RoiAudit_model->getPendingROIForRetry();

            if (empty($pending)) {
                return $this->_json(['status' => 'success', 'message' => 'No pending ROI to retry', 'processed' => 0]);
            }

            $processed = 0;
            $errors = [];

            foreach ($pending as $item) {
                try {
                    // Check if user still exists
                    $user = $this->db->get_where('members', ['id' => $item['user_id']])->row_array();
                    if (!$user) {
                        $this->db->update('roi_distribution_audit', ['status' => 'failed', 'error_message' => 'User not found'], ['id' => $item['id']]);
                        continue;
                    }

                    // Check if stake still exists
                    if ($item['stake_id']) {
                        $stake = $this->db->get_where('user_stakes', ['id' => $item['stake_id']])->row_array();
                        if (!$stake) {
                            $this->db->update('roi_distribution_audit', ['status' => 'failed', 'error_message' => 'Stake not found'], ['id' => $item['id']]);
                            continue;
                        }
                    }

                    // Validate ROI amount
                    if ($item['roi_amount'] <= 0) {
                        $this->db->update('roi_distribution_audit', ['status' => 'failed', 'error_message' => 'Invalid ROI amount'], ['id' => $item['id']]);
                        continue;
                    }

                    // Attempt to redistribute
                    $ledger_data = [
                        'user_id' => $item['user_id'],
                        'wallet_type' => $item['wallet_type'],
                        'transaction_type' => 'roi_'.$item['roi_type'],
                        'amount' => $item['roi_amount'],
                        'reference_id' => $item['stake_id'],
                        'reference_type' => 'roi_retry_'.$item['roi_type'],
                        'note' => "ROI Retry - {$item['plan_type']} {$item['roi_type']} (Rate: {$item['roi_rate_percent']}%)",
                        'created_at' => date('Y-m-d H:i:s')
                    ];

                    $this->load->model('member/Walletledger_model');
                    $ledger_id = $this->Walletledger_model->insert_ledger($ledger_data);

                    // Update audit record
                    $this->db->update('roi_distribution_audit', [
                        'status'      => 'success',
                        'ledger_id'   => $ledger_id,
                        'actual_payment_date' => date('Y-m-d H:i:s'),
                        'retry_count' => ($item['retry_count'] ?? 0) + 1
                    ], ['id' => $item['id']]);

                    $processed++;

                } catch (Throwable $e) {
                    $errors[] = "Stake {$item['stake_id']}: ".$e->getMessage();
                }
            }

            return $this->_json([
                'status' => 'success',
                'message' => "{$processed} ROI records retried successfully",
                'processed' => $processed,
                'errors' => $errors
            ]);

        } catch (Throwable $e) {
            return $this->_json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get user ROI summary
     */
    public function user_summary($user_id = 0)
    {
        if (!$this->input->is_ajax_request()) show_404();

        $user_id = (int)$user_id;
        if (!$user_id) {
            return $this->_json(['status' => 'error', 'message' => 'User ID required'], 400);
        }

        $user = $this->db->get_where('members', ['id' => $user_id])->row_array();
        if (!$user) {
            return $this->_json(['status' => 'error', 'message' => 'User not found'], 404);
        }

        $summary = $this->RoiAudit_model->getUserROISummary($user_id);

        return $this->_json([
            'status' => 'success',
            'user' => ['id' => $user['id'], 'username' => $user['username'], 'email' => $user['email']],
            'summary' => $summary
        ]);
    }

    /**
     * Export ROI history to CSV
     */
    public function export_csv()
    {
        $filters = [
            'user_id'   => $this->input->get('user_id', true),
            'plan_type' => $this->input->get('plan_type', true),
            'roi_type'  => $this->input->get('roi_type', true),
            'status'    => $this->input->get('status', true),
            'from_date' => $this->input->get('from_date', true),
            'to_date'   => $this->input->get('to_date', true)
        ];

        $filters = array_filter($filters);

        $data = $this->RoiAudit_model->getROIHistory($filters, 100000, 0);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="roi_history_'.date('Y-m-d_His').'.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['User ID', 'Username', 'Stake ID', 'Plan Type', 'ROI Type', 'Rate %', 'ROI Amount', 'Payment Date', 'Status', 'Executed At']);

        foreach ($data as $row) {
            fputcsv($output, [
                $row['user_id'],
                $row['username'],
                $row['stake_id'],
                $row['plan_type'],
                $row['roi_type'],
                $row['roi_rate_percent'],
                $row['roi_amount'],
                $row['payment_date'],
                $row['status'],
                $row['actual_payment_date']
            ]);
        }

        fclose($output);
        exit;
    }

    private function _json($data, $code = 200)
    {
        $this->output->set_status_header($code)
                     ->set_content_type('application/json')
                     ->set_output(json_encode($data));
    }
}
