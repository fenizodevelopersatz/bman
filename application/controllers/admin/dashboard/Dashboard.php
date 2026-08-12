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
            redirect('aaddmmiinn/login');
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

    public function hot_wallet()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $this->_json(['status' => true, 'data' => $this->stats->hotWalletBalance()]);
    }

    public function bonus_reduction()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $this->_json(['status' => true, 'data' => $this->stats->bonusReductionSummary()]);
    }

    public function roi_liability()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $this->_json(['status' => true, 'data' => $this->stats->roiLiability()]);
    }

    public function roi_liability_periods()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $this->_json(['status' => true, 'data' => $this->stats->roiLiabilityByPeriod()]);
    }

    public function treasury()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $this->_json(['status' => true, 'data' => $this->stats->treasuryDashboard()]);
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

    public function package_distribution_detail()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $months = $this->input->get('months', true);
        $this->_json(['status' => true, 'rows' => $this->stats->packageDistributionDetailed($months !== '' ? (int) $months : null)]);
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

    /** Platform-wide version of the member dashboard's "User Activity & Coin Trend" chart. */
    public function activity_trend()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $range = (string) $this->input->get('range', true);
        if (!in_array($range, ['daily', 'monthly', 'yearly'], true)) $range = 'monthly';
        $this->load->model('user/Dashboardchart_model', 'dashchart');
        $data = $this->dashchart->platformTrend($range);
        $this->_json(['status' => true] + $data);
    }

    public function rank_summary()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $this->_json(['status' => true, 'data' => $this->stats->rankSummary()]);
    }

    public function rank_members($id)
    {
        if (!$this->input->is_ajax_request()) show_404();
        $this->_json(['status' => true, 'rows' => $this->stats->rankMembers((int) $id)]);
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

    private function _buildAlerts(array $counts, array $health)
    {
        $alerts = [];
        if ($counts['withdrawals'] > 0) {
            $alerts[] = ['level' => 'warning', 'text' => $counts['withdrawals'] . ' Withdrawal Request(s) Pending', 'href' => base_url('admin/bman-withdrawals')];
        }
        // KYC pending count intentionally not surfaced here — already visible
        // in the KYC Monitoring card below; kept the alerts strip focused on
        // things needing urgent admin action rather than duplicating it.
        if ($counts['support'] > 0) {
            $alerts[] = ['level' => 'warning', 'text' => $counts['support'] . ' Support Ticket(s) Waiting', 'href' => base_url('support')];
        }
        foreach ($health['cron'] as $job) {
            if ($job['status'] !== 'success' && $job['status'] !== 'ok') {
                $alerts[] = ['level' => 'danger', 'text' => 'Cron "' . $job['name'] . '" failed ' . $job['minutes_ago'] . ' minute(s) ago', 'href' => null];
            }
        }
        return $alerts;
    }

    /** Admin Alerts — derived from data already gathered elsewhere, no separate query. */
    public function alerts()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $this->_json(['status' => true, 'alerts' => $this->_buildAlerts($this->stats->pendingCounts(), $this->stats->systemHealth())]);
    }

    public function sidebar_counts()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $adminId = (int) $this->session->userdata('admin_userid');
        $this->_json(['status' => true, 'data' => $this->stats->sidebarCounts($adminId)]);
    }

    public function notifications()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $this->_json(['status' => true, 'items' => $this->stats->notificationList(10)]);
    }

    public function gas_stats()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $this->_json(['status' => true, 'data' => $this->stats->gasStats()]);
    }

    /**
     * Combined poll — one round trip instead of four separate ones (sidebar
     * badges, bell notifications, admin alerts, system health). Fired by a
     * single site-wide timer in common_script.php; each piece of UI that
     * cares about a given field just reads it off this one response.
     */
    public function poll()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $adminId = (int) $this->session->userdata('admin_userid');
        $health = $this->stats->systemHealth();
        $this->_json([
            'status'         => true,
            'sidebar_counts' => $this->stats->sidebarCounts($adminId),
            'notifications'  => $this->stats->notificationList(10),
            'alerts'         => $this->_buildAlerts($this->stats->pendingCounts(), $health),
            'system_health'  => $health,
        ]);
    }
}

