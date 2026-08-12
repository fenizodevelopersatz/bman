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

        if (!$this->session->userdata('admin_logged_in')) redirect('aaddmmiinn/login');
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

    /** CLI controller segment for each cron (php index.php <controller> process [id]). */
    private $cronControllers = [
        'RoiMonthlyDistribution_cron' => 'roimonthlydistribution_cron',
        'RoiMaturityPayment_cron'     => 'roimaturitypayment_cron',
    ];

    /**
     * Trigger a cron as a fresh CLI subprocess, rather than instantiating a
     * second CI_Controller in-process OR curling its own route.
     *
     * Why not in-process: CodeIgniter 3's CI_Controller::__construct() rebuilds
     * every already-loaded "superobject" class from a global is_loaded()
     * registry — once the Session library is loaded (every admin controller
     * loads it), a second controller instance fails to re-resolve it ("Unable
     * to locate the specified class: Session.php").
     *
     * Why not internal HTTP any more: this app is served by `php -S`
     * (single-threaded — exactly one worker), so a request that curls its own
     * base_url can never be answered; the admin's request holds the only
     * worker while curl waits, and every Send Now click died at the 60s curl
     * timeout with 0 bytes. A CLI child gives the same fresh top-level run
     * with no second web worker needed — see RoiDistribution_cron::_call().
     */
    private function _runCron($class, $onlyId = null)
    {
        if (!isset($this->cronControllers[$class])) {
            return ['status' => false, 'error' => "Unknown cron: {$class}"];
        }

        $php = (defined('PHP_BINARY') && PHP_BINARY && stripos(basename(PHP_BINARY), 'php') === 0)
            ? PHP_BINARY : 'php';
        $cmd = '"' . $php . '" index.php ' . $this->cronControllers[$class] . ' process'
             . ($onlyId ? ' ' . (int)$onlyId : '') . ' 2>&1';

        $old = getcwd();
        chdir(FCPATH);
        exec($cmd, $lines, $exit);
        chdir($old);

        $raw = trim(implode("\n", (array) $lines));
        $decoded = json_decode($raw, true);
        if ($decoded === null && $lines) {
            // tolerate stray notices before the leg's single JSON line
            $decoded = json_decode(trim((string) end($lines)), true);
        }
        if ($decoded !== null) return $decoded;
        return ['status' => false, 'error' => "CLI run of {$class} failed (exit {$exit})", 'raw' => substr($raw, 0, 2000)];
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

        // Real gas comes from the tx receipts on onchain_transactions (filled
        // in by Chainsync_model::verifyTx once the sync cron confirms each
        // send) — roi_staking_management.total_gas_paid is never written by
        // the ROI crons, so summing that column always showed 0.
        $gasRow = $this->db->select('COALESCE(SUM(gas_fee_total),0) AS g', false)
            ->where('reference_type', 'roi')
            ->get('onchain_transactions')->row_array();

        return [
            'total_paid'      => (float)($row['total_paid'] ?? 0),
            'total_remaining' => (float)($row['total_remaining'] ?? 0),
            'total_gas_paid'  => (float)($gasRow['g'] ?? 0),
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
