<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * User ROI History Controller
 * Displays ROI distributions and staking history
 */
class RoiHistory extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->model('RoiAudit_model');

        if (!$this->session->userdata('user_id')) {
            redirect('user/login');
        }
    }

    /**
     * Display ROI history for logged-in user
     */
    public function index()
    {
        $user_id = $this->session->userdata('user_id');
        $page = max(1, (int)$this->input->get('page', true) ?? 1);
        $limit = 20;
        $offset = ($page - 1) * $limit;

        // Get user's ROI history
        $roi_history = $this->db
            ->select('*,
                IF(plan_type="regular", roi_amount/24,
                   IF(plan_type="fixed", roi_amount,
                      roi_amount)) as monthly_equivalent')
            ->where('user_id', $user_id)
            ->where('status', 'success')
            ->order_by('payment_date', 'DESC')
            ->limit($limit, $offset)
            ->get('roi_distribution_audit')
            ->result_array();

        // Get summary statistics
        $summary = $this->db
            ->select('
                SUM(roi_amount) as total_roi,
                SUM(CASE WHEN roi_type="monthly" THEN roi_amount ELSE 0 END) as monthly_roi,
                SUM(CASE WHEN roi_type="maturity" THEN roi_amount ELSE 0 END) as maturity_roi,
                COUNT(*) as total_count
            ')
            ->where('user_id', $user_id)
            ->where('status', 'success')
            ->get('roi_distribution_audit')
            ->row_array();

        // Get total count for pagination
        $total_count = $this->db
            ->where('user_id', $user_id)
            ->where('status', 'success')
            ->count_all_results('roi_distribution_audit');

        $data = [
            'title'       => 'ROI History',
            'roi_history' => $roi_history,
            'summary'     => $summary ?? ['total_roi' => 0, 'monthly_roi' => 0, 'maturity_roi' => 0],
            'page'        => $page,
            'limit'       => $limit,
            'total_pages' => ceil($total_count / $limit)
        ];

        $this->load->view('user/staking/roi_history', $data);
    }

    /**
     * Get ROI summary via AJAX
     */
    public function get_summary()
    {
        if (!$this->input->is_ajax_request()) show_404();

        $user_id = $this->session->userdata('user_id');

        $summary = $this->db
            ->select('
                SUM(roi_amount) as total_roi,
                SUM(CASE WHEN roi_type="monthly" THEN roi_amount ELSE 0 END) as monthly_roi,
                SUM(CASE WHEN roi_type="maturity" THEN roi_amount ELSE 0 END) as maturity_roi,
                COUNT(*) as total_distributions,
                MAX(payment_date) as last_payment
            ')
            ->where('user_id', $user_id)
            ->where('status', 'success')
            ->get('roi_distribution_audit')
            ->row_array();

        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'data' => $summary]);
    }

    /**
     * Get ROI by plan type
     */
    public function get_by_plan($plan_type = '')
    {
        if (!$this->input->is_ajax_request()) show_404();

        $user_id = $this->session->userdata('user_id');

        $query = $this->db
            ->select('
                plan_type,
                COUNT(*) as count,
                SUM(roi_amount) as total
            ')
            ->where('user_id', $user_id)
            ->where('status', 'success')
            ->group_by('plan_type');

        if (!empty($plan_type)) {
            $query->where('plan_type', $plan_type);
        }

        $result = $query->get('roi_distribution_audit')->result_array();

        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'data' => $result]);
    }
}
