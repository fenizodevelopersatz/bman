<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * DashboardStats_model — aggregation queries for the redesigned admin dashboard.
 * Every method returns a plain array (no HTML). Read-only.
 *
 * Revenue Summary and Total Reinvestments are intentionally NOT implemented here —
 * no fee/profit tracking or reinvestment concept exists anywhere in the platform
 * today (confirmed by full-codebase search). Add once a real definition exists.
 */
class DashboardStats_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Onchaintx_model', 'onchain');
        $this->load->model('staking/Rankreport_model', 'rankreport');
    }

    /* ============================== header stats ============================== */

    public function headerStats()
    {
        $members = $this->db->select("COUNT(*) AS total, SUM(status = 1) AS active, SUM(status != 1 OR status IS NULL) AS inactive", false)
                             ->get('users')->row_array();

        $totalStaking = (string) ($this->db->select_sum('stake_amount', 's')
            ->where_in('status', ['active', 'processing'])
            ->get('user_stakes')->row()->s ?: '0');

        $totalDeposits = (string) ($this->db->select_sum('amount_usdt', 's')
            ->where('status', 'credited')
            ->get('wallet_deposits')->row()->s ?: '0');

        $usdtWithdrawn = (string) ($this->db->select_sum('net_amount', 's')
            ->where('status', 'approved')
            ->get('withdrawals')->row()->s ?: '0');

        $bmanWithdrawn = (string) ($this->db->select_sum('net_amount', 's')
            ->where('status', 'completed')
            ->get('bman_withdraw_requests')->row()->s ?: '0');

        $bonusPaid = (string) ($this->db->select_sum('credit', 's')
            ->where('wallet_type', 'bonus')
            ->where_in('reference_type', ['bonus', 'rank_reward'])
            ->get('wallet_ledger')->row()->s ?: '0');

        return [
            'members_total'    => (int) ($members['total'] ?? 0),
            'members_active'   => (int) ($members['active'] ?? 0),
            'members_inactive' => (int) ($members['inactive'] ?? 0),
            'total_staking_bman' => $totalStaking,
            'total_deposits_usdt' => $totalDeposits,
            // Two different assets — never sum these into one figure.
            'total_withdrawals_usdt' => $usdtWithdrawn,
            'total_withdrawals_bman' => $bmanWithdrawn,
            'total_bonus_paid_bman' => $bonusPaid,
        ];
    }

    /** Platform-wide wallet balances — thin passthrough, not reimplemented. */
    public function walletTotals()
    {
        return $this->onchain->walletTotals();
    }

    /* ============================== staking analytics ============================== */

    public function stakingAnalytics()
    {
        $rows = $this->db->select('status, COUNT(*) AS n, COALESCE(SUM(stake_amount),0) AS total, COALESCE(AVG(stake_amount),0) AS avg_amt', false)
                          ->group_by('status')->get('user_stakes')->result_array();

        $byStatus = [];
        $totalCount = 0;
        $totalAmount = 0.0;
        foreach ($rows as $r) {
            $byStatus[$r['status']] = (int) $r['n'];
            $totalCount += (int) $r['n'];
            $totalAmount += (float) $r['total'];
        }

        // 'matured' is never set by any live cron (maturity crons operate on
        // staking_swap_orders/roi_staking_management instead) — compute maturity
        // from the date directly rather than a status value nothing ever writes.
        $reachedMaturity = (int) $this->db->where_in('status', ['active', 'processing'])
            ->where('maturity_date <=', date('Y-m-d'))
            ->count_all_results('user_stakes');
        $stillActive = (int) $this->db->where_in('status', ['active', 'processing'])
            ->where('maturity_date >', date('Y-m-d'))
            ->count_all_results('user_stakes');

        return [
            'active'            => $stillActive,
            'reached_maturity'  => $reachedMaturity,
            'withdrawn'         => (int) ($byStatus['withdrawn'] ?? 0),
            'cancelled'         => (int) ($byStatus['cancelled'] ?? 0),
            'total_stakes'      => $totalCount,
            'average_stake'     => $totalCount > 0 ? round($totalAmount / $totalCount, 4) : 0,
        ];
    }

    public function packageDistribution()
    {
        return $this->db->select('sp.id, sp.name, sp.stake_amount AS package_amount, COUNT(us.id) AS stakes, COALESCE(SUM(us.stake_amount),0) AS total_staked', false)
                         ->from('staking_packages sp')
                         ->join('user_stakes us', 'us.package_id = sp.id', 'left')
                         ->group_by('sp.id')
                         ->order_by('sp.sort_order', 'ASC')
                         ->order_by('sp.stake_amount', 'ASC')
                         ->get()->result_array();
    }

    /* ============================== binary mlm ============================== */

    public function binarySummary()
    {
        $vol = $this->db->select('COALESCE(SUM(left_volume),0) AS left_volume, COALESCE(SUM(right_volume),0) AS right_volume', false)
                         ->get('staking_group_volume')->row_array();

        $carry = $this->db->select('COALESCE(SUM(left_carry),0) AS left_carry, COALESCE(SUM(right_carry),0) AS right_carry', false)
                           ->get('binary_carry')->row_array();

        $matching = $this->db->select('COALESCE(SUM(earning_amount + staking_amount),0) AS total_matching', false)
                              ->get('staking_matching_payouts')->row_array();

        $today = $this->db->select('COALESCE(SUM(earning_amount + staking_amount),0) AS s', false)
                           ->where('DATE(created_at)', date('Y-m-d'))
                           ->get('staking_matching_payouts')->row_array();

        $weekAgo = date('Y-m-d', strtotime('-7 days'));
        $weekly = $this->db->select('COALESCE(SUM(earning_amount + staking_amount),0) AS s', false)
                            ->where('created_at >=', $weekAgo . ' 00:00:00')
                            ->get('staking_matching_payouts')->row_array();

        return [
            'left_volume'      => (string) $vol['left_volume'],
            'right_volume'     => (string) $vol['right_volume'],
            'left_carry'       => (string) $carry['left_carry'],
            'right_carry'      => (string) $carry['right_carry'],
            'total_matching'   => (string) $matching['total_matching'],
            'today_matching'   => (string) $today['s'],
            'weekly_matching'  => (string) $weekly['s'],
        ];
    }

    /** 30-day trend: new registrations vs matching payouts, dense-bucketed by day. */
    public function binaryGrowth($days = 30)
    {
        $days = max(1, (int) $days);
        $from = date('Y-m-d', strtotime("-{$days} days"));

        $regRows = $this->db->select("DATE(register_date) AS d, COUNT(*) AS n", false)
                             ->where('register_date >=', $from . ' 00:00:00')
                             ->group_by('d')->get('users')->result_array();

        $payRows = $this->db->select("DATE(created_at) AS d, COALESCE(SUM(earning_amount + staking_amount),0) AS n", false)
                             ->where('created_at >=', $from . ' 00:00:00')
                             ->group_by('d')->get('staking_matching_payouts')->result_array();

        $regByDay = [];
        foreach ($regRows as $r) $regByDay[$r['d']] = (int) $r['n'];
        $payByDay = [];
        foreach ($payRows as $r) $payByDay[$r['d']] = (float) $r['n'];

        $labels = [];
        $registrations = [];
        $matchingPayouts = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} days"));
            $labels[] = $d;
            $registrations[] = $regByDay[$d] ?? 0;
            $matchingPayouts[] = $payByDay[$d] ?? 0;
        }

        return ['labels' => $labels, 'registrations' => $registrations, 'matching_payouts' => $matchingPayouts];
    }

    /* ============================== rank achievement ============================== */

    public function rankSummary()
    {
        return [
            'distribution' => $this->rankreport->distribution(),
            'headline'     => $this->rankreport->headline(),
        ];
    }

    /* ============================== withdrawal center ============================== */

    public function withdrawalCenter()
    {
        $bman = $this->db->select("status, COUNT(*) AS n, COALESCE(SUM(net_amount),0) AS total", false)
                          ->group_by('status')->get('bman_withdraw_requests')->result_array();
        $bmanByStatus = [];
        foreach ($bman as $r) $bmanByStatus[$r['status']] = ['count' => (int) $r['n'], 'total' => (string) $r['total']];

        $usdt = $this->db->select("status, COUNT(*) AS n, COALESCE(SUM(net_amount),0) AS total", false)
                          ->group_by('status')->get('withdrawals')->result_array();
        $usdtByStatus = [];
        foreach ($usdt as $r) $usdtByStatus[$r['status']] = ['count' => (int) $r['n'], 'total' => (string) $r['total']];

        $todayBman = (int) $this->db->where('DATE(created_at)', date('Y-m-d'))->count_all_results('bman_withdraw_requests');
        $todayUsdt = (int) $this->db->where('DATE(created_at)', date('Y-m-d'))->count_all_results('withdrawals');

        return [
            'bman' => [
                'pending'   => $bmanByStatus['pending']['count'] ?? 0,
                'approved'  => $bmanByStatus['approved']['count'] ?? 0,
                'completed' => $bmanByStatus['completed']['count'] ?? 0,
                'rejected'  => $bmanByStatus['rejected']['count'] ?? 0,
                'total_withdrawn' => $bmanByStatus['completed']['total'] ?? '0',
            ],
            'usdt' => [
                'pending'  => $usdtByStatus['pending']['count'] ?? 0,
                'approved' => $usdtByStatus['approved']['count'] ?? 0,
                'rejected' => $usdtByStatus['rejected']['count'] ?? 0,
                'total_withdrawn' => $usdtByStatus['approved']['total'] ?? '0',
            ],
            'today_requests' => $todayBman + $todayUsdt,
        ];
    }

    /* ============================== KYC ============================== */

    public function kycMonitor()
    {
        // kyc_applications is the canonical table for this dashboard — the
        // parallel user_kyc (India PAN/Aadhaar) table is not included.
        $rows = $this->db->select('status, COUNT(*) AS n', false)
                          ->group_by('status')->get('kyc_applications')->result_array();
        $byStatus = [];
        foreach ($rows as $r) $byStatus[$r['status']] = (int) $r['n'];

        $expired = (int) $this->db->where('doc_expiry_date IS NOT NULL', null, false)
                                   ->where('doc_expiry_date <=', date('Y-m-d'))
                                   ->count_all_results('kyc_applications');

        return [
            'pending'  => ($byStatus['pending'] ?? 0) + ($byStatus['under_review'] ?? 0),
            'approved' => $byStatus['approved'] ?? 0,
            'rejected' => ($byStatus['rejected'] ?? 0) + ($byStatus['resubmitted'] ?? 0),
            'expired'  => $expired,
        ];
    }

    /* ============================== support ============================== */

    public function supportCenter()
    {
        $row = $this->db->select("
            SUM(status = 0) AS pending_count,
            SUM(status = 1) AS open_count,
            SUM(status = 2) AS closed_count", false)
            ->get('support')->row_array();

        $today = (int) $this->db->where('DATE(date)', date('Y-m-d'))->count_all_results('support');

        return [
            'pending' => (int) ($row['pending_count'] ?? 0),
            'open'    => (int) ($row['open_count'] ?? 0),
            'closed'  => (int) ($row['closed_count'] ?? 0),
            'today'   => $today,
            // No real priority field exists (ticket_status is free text, almost
            // always empty) — intentionally not reporting a "priority" count.
        ];
    }

    /* ============================== live activity feed ============================== */

    public function activityFeed($limit = 20)
    {
        $limit = max(1, min(100, (int) $limit));

        $ledger = $this->db->select("wl.user_id, u.username, wl.reference_type, wl.credit AS amount, wl.wallet_type, wl.created_at", false)
                            ->from('wallet_ledger wl')
                            ->join('users u', 'u.id = wl.user_id', 'left')
                            ->where('wl.credit >', 0)
                            ->where_in('wl.reference_type', ['stake_purchase', 'bonus', 'binary_matching'])
                            ->order_by('wl.id', 'DESC')->limit($limit)->get()->result_array();

        $ranks = $this->db->select("urh.user_id, u.username, r.name AS rank_name, urh.achieved_at", false)
                           ->from('user_rank_history urh')
                           ->join('users u', 'u.id = urh.user_id', 'left')
                           ->join('staking_ranks r', 'r.id = urh.new_rank_id', 'left')
                           ->order_by('urh.id', 'DESC')->limit($limit)->get()->result_array();

        $events = [];
        foreach ($ledger as $r) {
            $label = [
                'stake_purchase'  => 'purchased',
                'bonus'           => 'received bonus',
                'binary_matching' => 'received matching bonus',
            ][$r['reference_type']] ?? 'received';
            $events[] = [
                'text' => ($r['username'] ?: ('User #' . $r['user_id'])) . ' ' . $label . ' ' . number_format((float) $r['amount'], 2) . ' BMAN',
                'at'   => $r['created_at'],
            ];
        }
        foreach ($ranks as $r) {
            $events[] = [
                'text' => ($r['username'] ?: ('User #' . $r['user_id'])) . ' achieved ' . strtoupper($r['rank_name'] ?? 'a new') . ' Rank',
                'at'   => $r['achieved_at'],
            ];
        }

        usort($events, function ($a, $b) { return strcmp($b['at'], $a['at']); });
        return array_slice($events, 0, $limit);
    }

    /* ============================== system health ============================== */

    public function systemHealth()
    {
        // Database — if this query runs at all, the connection is up.
        $dbOk = true;
        try {
            $this->db->query('SELECT 1');
        } catch (Exception $e) {
            $dbOk = false;
        }

        // Cron — most recent run per job name.
        $cronRows = $this->db->order_by('id', 'DESC')->limit(200)->get('cron_execution_log')->result_array();
        $latestByJob = [];
        foreach ($cronRows as $r) {
            if (!isset($latestByJob[$r['cron_name']])) $latestByJob[$r['cron_name']] = $r;
        }
        $cronJobs = [];
        foreach ($latestByJob as $name => $r) {
            $cronJobs[] = [
                'name' => $name,
                'status' => $r['status'],
                'last_run' => $r['created_at'],
                'minutes_ago' => (int) round((time() - strtotime($r['created_at'])) / 60),
            ];
        }

        // RPC — success rate over the last 24h.
        $rpc = $this->db->select('COUNT(*) AS total, SUM(ok) AS ok_count', false)
                        ->where('created_at >=', date('Y-m-d H:i:s', strtotime('-24 hours')))
                        ->get('rpc_sync_log')->row_array();
        $rpcTotal = (int) ($rpc['total'] ?? 0);
        $rpcOk = (int) ($rpc['ok_count'] ?? 0);

        // SMTP — configuration presence only, no live connectivity test exists.
        $email = $this->db->get('email_config')->row_array();
        $smtpConfigured = $email && !empty($email['host']) && !empty($email['from_mail']) && ($email['smtp_status'] ?? '0') === '1';

        // Storage — live filesystem call, not DB-backed.
        $free = @disk_free_space(FCPATH);
        $total = @disk_total_space(FCPATH);

        return [
            'database' => ['ok' => $dbOk],
            'cron' => $cronJobs,
            'rpc' => [
                'total_24h' => $rpcTotal,
                'ok_24h'    => $rpcOk,
                'success_rate' => $rpcTotal > 0 ? round($rpcOk * 100 / $rpcTotal, 1) : null,
            ],
            'smtp' => ['configured' => $smtpConfigured],
            'storage' => [
                'free_bytes'  => $free !== false ? (float) $free : null,
                'total_bytes' => $total !== false ? (float) $total : null,
                'used_percent' => ($free !== false && $total !== false && $total > 0)
                    ? round((($total - $free) / $total) * 100, 1) : null,
            ],
        ];
    }

    /* ============================== sidebar "new" counts ============================== */

    /** Record that this admin has viewed a badge category's page — clears its badge. */
    public function markSeen($adminId, $category)
    {
        $adminId = (int) $adminId;
        if (!$adminId || !$category) return;
        $this->db->query(
            "INSERT INTO admin_badge_seen (admin_id, category, last_seen_at) VALUES (?, ?, NOW())
             ON DUPLICATE KEY UPDATE last_seen_at = NOW()",
            [$adminId, (string) $category]
        );
    }

    private function _lastSeen($adminId, $category)
    {
        $row = $this->db->get_where('admin_badge_seen', ['admin_id' => (int) $adminId, 'category' => $category])->row_array();
        return $row['last_seen_at'] ?? '1970-01-01 00:00:00';
    }

    /** True live pending totals — used by Admin Alerts, unaffected by "seen" state. */
    public function pendingCounts()
    {
        $bmanPending = (int) $this->db->where('status', 'pending')->count_all_results('bman_withdraw_requests');
        $usdtPending = (int) $this->db->where('status', 'pending')->count_all_results('withdrawals');
        $kycPending = (int) $this->db->where_in('status', ['pending', 'under_review'])->count_all_results('kyc_applications');
        $supportPending = (int) $this->db->where('status', 0)->count_all_results('support');

        return [
            'withdrawals' => $bmanPending + $usdtPending,
            'kyc'         => $kycPending,
            'support'     => $supportPending,
        ];
    }

    /**
     * Badge count = pending items created SINCE the admin last visited that
     * section — not just a live pending count. Visiting the page (markSeen)
     * clears the badge; it only climbs again as new items arrive afterward.
     */
    public function sidebarCounts($adminId = 0)
    {
        $wdSeen = $this->_lastSeen($adminId, 'withdrawals');
        $kycSeen = $this->_lastSeen($adminId, 'kyc');
        $supportSeen = $this->_lastSeen($adminId, 'support');

        $bmanPending = (int) $this->db->where('status', 'pending')->where('created_at >', $wdSeen)
            ->count_all_results('bman_withdraw_requests');
        $usdtPending = (int) $this->db->where('status', 'pending')->where('created_at >', $wdSeen)
            ->count_all_results('withdrawals');
        $kycPending = (int) $this->db->where_in('status', ['pending', 'under_review'])->where('created_at >', $kycSeen)
            ->count_all_results('kyc_applications');
        $supportPending = (int) $this->db->where('status', 0)->where('date >', $supportSeen)
            ->count_all_results('support');

        return [
            'withdrawals' => $bmanPending + $usdtPending,
            'kyc'         => $kycPending,
            'support'     => $supportPending,
        ];
    }

    /* ============================== online members ============================== */

    /**
     * "Active in Chat" — last_active_at is a chat-poll heartbeat only (single
     * writer: Genealogycontroller's Direct Chat tab), not a sitewide presence
     * signal. Deliberately not labeled "Online Members" anywhere downstream.
     */
    public function onlineMembers($windowSeconds = 300)
    {
        $since = date('Y-m-d H:i:s', time() - max(30, (int) $windowSeconds));
        return (int) $this->db->where('last_active_at >=', $since)->count_all_results('users');
    }
}
