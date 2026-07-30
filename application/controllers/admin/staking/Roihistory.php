<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin — ROI Distribution History (real data source: roi_staking_management +
 * onchain_transactions, populated by RoiMonthlyDistribution_cron / RoiMaturityPayment_cron).
 */
class Roihistory extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->model('Admin_model');

        if (!$this->session->userdata('admin_logged_in')) redirect('admin/login');
        $user = $this->Admin_model->get_user($this->session->userdata('admin_userid'));
        if ($user && $user->admin_roll == '1') {
            $perm = json_decode($user->permission_pages, true);
            if (empty($perm['staking_management']) && empty($perm['wallet_management'])) {
                $this->session->set_flashdata('error', 'Access Denied: You do not have permission.');
                redirect('admin');
            }
        }
    }

    private function _json($data, $code = 200)
    {
        $this->output->set_status_header($code)
                     ->set_content_type('application/json')
                     ->set_output(json_encode($data));
    }

    /** route (public HTTP endpoint) for each cron — kept in sync with routes.php */
    private $cronRoutes = [
        'RoiMonthlyDistribution_cron' => 'roi-monthly-distribution-process',
        'RoiMaturityPayment_cron'     => 'roi-maturity-payment-process',
    ];

    /**
     * Trigger a cron via an internal HTTP call to its own route, rather than
     * instantiating a second CI_Controller in-process. CodeIgniter 3's
     * CI_Controller::__construct() rebuilds every already-loaded "superobject"
     * class from a global is_loaded() registry — once the Session library is
     * loaded (every admin controller loads it), a second controller instance
     * fails to re-resolve it ("Unable to locate the specified class:
     * Session.php"). Hitting the existing, already-working route as a fresh
     * top-level request avoids that entirely.
     */
    private function _runCron($class, $onlyId = null)
    {
        if (!isset($this->cronRoutes[$class])) {
            return ['status' => false, 'error' => "Unknown cron: {$class}"];
        }

        $params = [];
        $token = $this->config->item('cron_token');
        if ($token) $params['token'] = $token;
        if ($onlyId) $params['record_id'] = (int)$onlyId;

        $url = base_url($this->cronRoutes[$class]);
        if ($params) $url .= '?' . http_build_query($params);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_HTTPHEADER     => ['X-Requested-With: XMLHttpRequest'],
        ]);
        $output = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($output === false) {
            return ['status' => false, 'error' => "Internal request to {$class} failed: {$err}"];
        }
        $decoded = json_decode($output, true);
        return $decoded !== null ? $decoded : ['status' => false, 'error' => 'Non-JSON output', 'raw' => $output];
    }

    public function index()
    {
        $data = [
            'title'   => 'ROI Distribution History',
            'summary' => $this->_summary(),
            'failed'  => $this->_failedRecords(),
            'upcoming'=> $this->_upcoming(),
        ];
        $this->load->view('admin/staking/roi_history', $data);
    }

    private function _summary()
    {
        $row = $this->db->select("
                SUM(total_paid_amount)  AS total_paid,
                SUM(remaining_to_pay)   AS total_remaining,
                SUM(total_gas_paid)     AS total_gas_paid,
                SUM(CASE WHEN overall_status = 'completed' THEN 1 ELSE 0 END) AS completed_count,
                SUM(CASE WHEN overall_status IN ('active','in_progress') THEN 1 ELSE 0 END) AS active_count,
                SUM(CASE WHEN error_message IS NOT NULL THEN 1 ELSE 0 END) AS failed_count,
                COUNT(*) AS total_count", false)
            ->get('roi_staking_management')->row_array();

        $distCount = (int)$this->db->where('reference_type', 'roi')
            ->count_all_results('onchain_transactions');

        return [
            'total_paid'      => (float)($row['total_paid'] ?? 0),
            'total_remaining' => (float)($row['total_remaining'] ?? 0),
            'total_gas_paid'  => (float)($row['total_gas_paid'] ?? 0),
            'completed_count' => (int)($row['completed_count'] ?? 0),
            'active_count'    => (int)($row['active_count'] ?? 0),
            'failed_count'    => (int)($row['failed_count'] ?? 0),
            'total_count'     => (int)($row['total_count'] ?? 0),
            'distributions'   => $distCount,
        ];
    }

    private function _failedRecords()
    {
        return $this->db->select('r.id, r.user_id, r.ref, r.plan_type, r.overall_status,
                r.regular_payments_completed, r.regular_payment_count, r.fixed_maturity_date,
                r.next_payment_date, r.error_message, r.updated_at, u.username, u.email', false)
            ->from('roi_staking_management r')
            ->join('users u', 'u.id = r.user_id', 'left')
            ->where('r.error_message IS NOT NULL', null, false)
            ->order_by('r.updated_at', 'DESC')
            ->limit(100)
            ->get()->result_array();
    }

    private function _upcoming()
    {
        $now = date('Y-m-d H:i:s');
        $in7 = date('Y-m-d H:i:s', strtotime('+7 days'));
        return $this->db->select('r.id, r.user_id, r.ref, r.plan_type, r.overall_status,
                r.fixed_payment_amount, r.fixed_maturity_date, r.regular_payment_amount,
                r.next_payment_date, u.username', false)
            ->from('roi_staking_management r')
            ->join('users u', 'u.id = r.user_id', 'left')
            ->where('r.overall_status !=', 'completed')
            ->where('r.next_payment_date >=', $now)
            ->where('r.next_payment_date <=', $in7)
            ->order_by('r.next_payment_date', 'ASC')
            ->limit(50)
            ->get()->result_array();
    }

    /** AJAX: paginated distribution event log (real credited payments). */
    public function list()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $page  = max(1, (int)$this->input->post('page'));
        $limit = 25;
        $offset = ($page - 1) * $limit;

        $type   = $this->input->post('tx_type', true);   // roi_monthly | roi_maturity | principal_return
        $userId = (int)$this->input->post('user_id');

        $applyFilters = function () use ($type, $userId) {
            $this->db->from('onchain_transactions o')
                ->join('users u', 'u.id = o.user_id', 'left')
                ->where('o.reference_type', 'roi');
            if ($type)   $this->db->where('o.tx_type', $type);
            if ($userId) $this->db->where('o.user_id', $userId);
        };

        $applyFilters();
        $total = $this->db->count_all_results();

        $applyFilters();
        $rows = $this->db->select('o.id, o.user_id, o.tx_type, o.amount, o.wallet_type, o.status,
                o.tx_hash, o.reference_id, o.gas_fee_total, o.created_at, u.username, u.email', false)
            ->order_by('o.created_at', 'DESC')->limit($limit, $offset)->get()->result_array();

        return $this->_json(['status' => 'success', 'rows' => $rows, 'total' => $total, 'page' => $page, 'limit' => $limit]);
    }

    /** AJAX: paginated listing of every staking + ROI record (default view, no search needed). */
    public function records()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $page  = max(1, (int)$this->input->post('page'));
        $limit = 25;
        $offset = ($page - 1) * $limit;
        $status    = $this->input->post('status', true); // active | in_progress | completed | failed
        $isSpecial = (int)$this->input->post('is_special'); // 1 → only Special Offer records

        $applyFilters = function () use ($status, $isSpecial) {
            $this->db->from('roi_staking_management r')->join('users u', 'u.id = r.user_id', 'left');
            if ($isSpecial) {
                $this->db->where('r.is_special', 1);
            }
            if ($status === 'failed') {
                $this->db->where('r.error_message IS NOT NULL', null, false);
            } elseif ($status) {
                $this->db->where('r.overall_status', $status);
            }
        };

        $applyFilters();
        $total = $this->db->count_all_results();

        $applyFilters();
        $rows = $this->db->select('r.id, r.user_id, r.ref, r.plan_type, r.overall_status,
                r.principal_amount, r.total_roi_amount, r.total_paid_amount, r.remaining_to_pay,
                r.regular_payments_completed, r.regular_payment_count,
                r.fixed_status, r.fixed_payment_amount, r.fixed_maturity_date,
                r.is_special, r.duration_years, r.special_schedule_json, r.special_maturity_percent,
                r.next_payment_date, r.error_message, r.created_at, u.username, u.email', false)
            ->order_by('r.created_at', 'DESC')->limit($limit, $offset)->get()->result_array();

        return $this->_json(['status' => 'success', 'rows' => $rows, 'total' => $total, 'page' => $page, 'limit' => $limit]);
    }

    /**
     * Find a user's ROI records for the manual-send panel. Accepts a numeric
     * user_id or a username/email search term. Shows every record (not just
     * failed ones) so admin can manually trigger a send for support cases.
     */
    public function lookup_user()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $q = trim((string)$this->input->post('q', true));
        if ($q === '') return $this->_json(['status' => 'error', 'message' => 'Enter a user ID, username, or email'], 422);

        $this->db->select('id, username, email')->from('users');
        if (ctype_digit($q)) {
            $this->db->where('id', (int)$q);
        } else {
            $this->db->group_start()->like('username', $q)->or_like('email', $q)->group_end();
        }
        $users = $this->db->limit(10)->get()->result_array();
        if (!$users) return $this->_json(['status' => 'success', 'users' => [], 'records' => []]);

        $userIds = array_column($users, 'id');
        $records = $this->db->select('r.id, r.user_id, r.ref, r.plan_type, r.overall_status,
                r.principal_amount, r.total_roi_amount, r.total_paid_amount, r.remaining_to_pay,
                r.regular_payments_completed, r.regular_payment_count,
                r.fixed_status, r.fixed_payment_amount, r.fixed_maturity_date,
                r.next_payment_date, r.error_message', false)
            ->from('roi_staking_management r')
            ->where_in('r.user_id', $userIds)
            ->order_by('r.created_at', 'DESC')
            ->get()->result_array();

        return $this->_json(['status' => 'success', 'users' => $users, 'records' => $records]);
    }

    /**
     * Manually trigger a send for one roi_staking_management record — used both
     * for retrying a failed record and for support-driven "send this user's ROI
     * now" requests. Schedule-respecting: the underlying crons only credit
     * whatever is actually due today, so this can never pay ahead of schedule.
     */
    public function retry($id)
    {
        if (!$this->input->is_ajax_request()) show_404();
        $id = (int)$id;
        $rec = $this->db->get_where('roi_staking_management', ['id' => $id])->row_array();
        if (!$rec) return $this->_json(['status' => 'error', 'message' => 'Record not found'], 404);

        $out = [
            'monthly'  => $this->_runCron('RoiMonthlyDistribution_cron', $id),
            'maturity' => $this->_runCron('RoiMaturityPayment_cron', $id),
        ];

        return $this->_json(['status' => 'success', 'message' => 'Send executed for record #' . $id, 'data' => $out]);
    }

    /** Retry every failed record at once (full unscoped cron run — safe/idempotent). */
    public function retry_all()
    {
        if (!$this->input->is_ajax_request()) show_404();

        $out = [
            'monthly'  => $this->_runCron('RoiMonthlyDistribution_cron'),
            'maturity' => $this->_runCron('RoiMaturityPayment_cron'),
        ];

        return $this->_json(['status' => 'success', 'message' => 'Retry-all executed', 'data' => $out]);
    }
}
