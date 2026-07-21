<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin ▸ All Transactions
 * One page over every money movement: internal transfers, on-chain deposits/
 * withdrawals, staking purchases, ROI distribution, binary matching bonus, and
 * the instant staking bonus — all already unified in wallet_ledger (each row
 * enriched with its on-chain mirror) via WalletTracker_model. A second tab
 * shows the Cron Run Log (CronLog_model) for the scheduled jobs behind ROI
 * distribution and matching-bonus payouts.
 */
class AllTransactions extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper(['url', 'security']);
        $this->load->model('Admin_model');
        $this->load->model('WalletTracker_model', 'tracker');
        $this->load->model('CronLog_model', 'cronlog');

        if (!$this->session->userdata('admin_logged_in')) {
            redirect('admin/login');
        }
        $user = $this->Admin_model->get_user($this->session->userdata('admin_userid'));
        if ($user && $user->admin_roll == '1') {
            $permissions = json_decode($user->permission_pages, true);
            if (empty($permissions['wallet_management'])) {
                $this->session->set_flashdata('error', 'Access Denied: You do not have permission.');
                redirect('admin');
            }
        }
    }

    private function _json($data = [], $code = 200)
    {
        $this->output->set_status_header($code)
                     ->set_content_type('application/json')
                     ->set_output(json_encode($data));
    }

    /* ------------------------------- page ------------------------------- */
    public function index()
    {
        $data['title'] = 'All Transactions';
        $this->load->view('admin/all_transactions', $data);
    }

    /** Catalog of reference types + categories for the filter dropdowns. */
    public function options()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $this->_json([
            'status'          => true,
            'reference_types' => $this->tracker->reference_types(),
            'categories'      => $this->tracker->categories(),
            'wallets'         => ['usdt', 'exchange', 'earning', 'staking', 'bonus'],
            'explorer_url'    => $this->_explorer(),
        ]);
    }

    /** Active chain's block explorer base URL (e.g. https://bscscan.com), same convention as Onchaintx.php. */
    private function _explorer()
    {
        $ts = $this->db->select('explorer_url')->get_where('token_settings', ['status' => 1])->row_array();
        return rtrim($ts['explorer_url'] ?? 'https://bscscan.com', '/');
    }

    private function _filters()
    {
        $get = function ($k) { return $this->input->get($k, true); };
        return array_filter([
            'user_id'        => $get('user_id'),
            'wallet_type'    => $get('wallet_type'),
            'reference_type' => $get('reference_type'),
            'category'       => $get('category'),
            'direction'      => $get('direction'),
            'date_from'      => $get('date_from'),
            'date_to'        => $get('date_to'),
            'search'         => $get('search'),
            'tx_hash'        => $get('tx_hash'),
        ], function ($v) { return $v !== '' && $v !== null; });
    }

    /** Paginated, filterable list of every wallet_ledger movement. */
    public function list()
    {
        if (!$this->input->is_ajax_request()) show_404();

        $filters = $this->_filters();
        $page    = max(1, (int) $this->input->get('page', true) ?: 1);
        $limit   = min(200, max(1, (int) ($this->input->get('limit', true) ?: 50)));
        $offset  = ($page - 1) * $limit;

        $rows = $this->tracker->list_transactions($filters, $limit, $offset);
        foreach ($rows as &$r) {
            $r['avatar'] = user_profile_image($r['user_id']);
        }

        $this->_json([
            'status' => true,
            'total'  => $this->tracker->count_transactions($filters),
            'page'   => $page,
            'limit'  => $limit,
            'rows'   => $rows,
        ]);
    }

    /** Single ledger row with full source record + on-chain mirror. */
    public function detail()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $id = (int) $this->input->get('id', true);
        if (!$id) return $this->_json(['status' => false, 'message' => 'id required'], 400);

        $row = $this->tracker->transaction_detail($id);
        if (!$row) return $this->_json(['status' => false, 'message' => 'Not found'], 404);

        $row['avatar'] = user_profile_image($row['user_id']);
        $this->_json(['status' => true, 'row' => $row]);
    }

    /** Cron Run Log tab — every scheduled-job execution, most recent first. */
    public function cron_log()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $limit = min(300, max(1, (int) ($this->input->get('limit', true) ?: 100)));
        $name  = $this->input->get('cron_name', true) ?: null;

        $this->_json(['status' => true, 'rows' => $this->cronlog->recent($limit, $name)]);
    }
}
