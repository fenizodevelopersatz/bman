<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin ▸ Dashboard (v2) — real BMAN/staking/binary data, replaces the legacy
 * Administrator.php dashboard which was wired to unrelated `history`/
 * `user_investment` tables. No permission_pages gate, matching the old
 * dashboard's behavior (accessible to every logged-in admin).
 */
class Dashboard extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper(['url', 'security']);
        $this->load->model('admin/DashboardStats_model', 'stats');

        if (!$this->session->userdata('admin_logged_in')) {
            redirect('admin/login');
        }
    }

    private function _json($data = [], $code = 200)
    {
        $this->output->set_status_header($code)
                     ->set_content_type('application/json')
                     ->set_output(json_encode($data));
    }

    public function index()
    {
        $data['title'] = 'Dashboard';
        $this->load->view('admin/dashboard/dashboard_v2', $data);
    }

    public function stats()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $this->_json([
            'status'  => true,
            'header'  => $this->stats->headerStats(),
            'wallets' => $this->stats->walletTotals(),
            'online_in_chat' => $this->stats->onlineMembers(),
        ]);
    }

    public function staking_analytics()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $this->_json(['status' => true, 'data' => $this->stats->stakingAnalytics()]);
    }

    public function package_distribution()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $this->_json(['status' => true, 'rows' => $this->stats->packageDistribution()]);
    }

    public function binary_summary()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $this->_json(['status' => true, 'data' => $this->stats->binarySummary()]);
    }

    public function binary_growth()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $days = (int) ($this->input->get('days', true) ?: 30);
        $this->_json(['status' => true, 'data' => $this->stats->binaryGrowth($days)]);
    }

    public function rank_summary()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $this->_json(['status' => true, 'data' => $this->stats->rankSummary()]);
    }

    public function withdrawal_center()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $this->_json(['status' => true, 'data' => $this->stats->withdrawalCenter()]);
    }

    public function kyc_monitor()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $this->_json(['status' => true, 'data' => $this->stats->kycMonitor()]);
    }

    public function support_center()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $this->_json(['status' => true, 'data' => $this->stats->supportCenter()]);
    }

    public function activity_feed()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $limit = (int) ($this->input->get('limit', true) ?: 20);
        $this->_json(['status' => true, 'rows' => $this->stats->activityFeed($limit)]);
    }

    public function system_health()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $this->_json(['status' => true, 'data' => $this->stats->systemHealth()]);
    }

    /** Admin Alerts — derived from data already gathered elsewhere, no separate query. */
    public function alerts()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $counts = $this->stats->pendingCounts();
        $health = $this->stats->systemHealth();

        $alerts = [];
        if ($counts['withdrawals'] > 0) {
            $alerts[] = ['level' => 'warning', 'text' => $counts['withdrawals'] . ' Withdrawal Request(s) Pending', 'href' => base_url('admin/bman-withdrawals')];
        }
        if ($counts['kyc'] > 0) {
            $alerts[] = ['level' => 'warning', 'text' => $counts['kyc'] . ' KYC Request(s) Pending', 'href' => base_url('admin/kyc')];
        }
        if ($counts['support'] > 0) {
            $alerts[] = ['level' => 'warning', 'text' => $counts['support'] . ' Support Ticket(s) Waiting', 'href' => base_url('support')];
        }
        foreach ($health['cron'] as $job) {
            if ($job['status'] !== 'success' && $job['status'] !== 'ok') {
                $alerts[] = ['level' => 'danger', 'text' => 'Cron "' . $job['name'] . '" failed ' . $job['minutes_ago'] . ' minute(s) ago', 'href' => null];
            }
        }

        $this->_json(['status' => true, 'alerts' => $alerts]);
    }

    public function sidebar_counts()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $adminId = (int) $this->session->userdata('admin_userid');
        $this->_json(['status' => true, 'data' => $this->stats->sidebarCounts($adminId)]);
    }
}
