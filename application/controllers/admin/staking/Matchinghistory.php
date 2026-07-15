<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin ▸ Staking ▸ Binary Matching History.
 * First admin-facing view of Stakingmatching_model's own audit trail
 * (staking_matching_payouts) plus the binary_matching_queue run history —
 * both already existed but were never exposed in any screen. Read-only
 * except for "Run Matching Now", which drives the same Matchingqueue_model
 * path the cron uses, so a manual run and a scheduled one share one history.
 */
class Matchinghistory extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper(['url']);
        $this->load->library('session');
        $this->load->model('Admin_model');
        $this->load->model('staking/Matchingqueue_model', 'MQ');
        $this->load->model('staking/Stakingmatching_model', 'MB');
        $this->load->model('staking/Ceilingwallet_model', 'CW');
    }

    private function _requireAdmin()
    {
        if (!$this->session->userdata('admin_logged_in')) redirect('admin/login');
        $user = $this->Admin_model->get_user($this->session->userdata('admin_userid'));
        if ($user && $user->admin_roll == '1') {
            $perm = json_decode($user->permission_pages, true);
            if (empty($perm['staking_management']) && empty($perm['finance_management'])) {
                $this->session->set_flashdata('error', 'Access Denied: You do not have permission.');
                redirect('admin');
            }
        }
    }

    private function _json($d, $c = 200)
    {
        $this->output->set_status_header($c)->set_content_type('application/json')->set_output(json_encode($d));
    }

    /** Same convention as Onchaintx::_explorer() / Withdraw.php. */
    private function _explorer()
    {
        $ts = $this->db->select('explorer_url')->get_where('token_settings', ['status' => 1])->row_array();
        return rtrim($ts['explorer_url'] ?? 'https://bscscan.com', '/');
    }

    /** Binary Matching History dashboard: KPIs, engine run log, payout audit log. */
    public function index()
    {
        $this->_requireAdmin();
        $data = $this->_pageData();
        $this->load->view('admin/staking/matching_history', $data);
    }

    /** AJAX refresh payload for the matching history page. */
    public function snapshot()
    {
        $this->_requireAdmin();
        if (!$this->input->is_ajax_request()) show_404();
        return $this->_json(['status' => 'success'] + $this->_pageData());
    }

    private function _pageData()
    {
        $totals = $this->db->select(
            "COALESCE(SUM(matched_volume),0) AS matched_volume, " .
            "COALESCE(SUM(earning_amount),0) AS earning_paid, " .
            "COALESCE(SUM(staking_amount),0) AS staking_paid", false
        )->get('staking_matching_payouts')->row_array();

        $ceiling = $this->db->select_sum('amount', 'held')
            ->where('tx_type', 'CEILING_HOLD')->where('reference_type', 'binary_matching')
            ->get('ceiling_wallet_ledger')->row_array();

        return [
            'title'            => 'Binary Matching History',
            'matched_volume'   => (float)($totals['matched_volume'] ?? 0),
            'earning_paid'     => (float)($totals['earning_paid'] ?? 0),
            'staking_paid'     => (float)($totals['staking_paid'] ?? 0),
            'ceiling_diverted' => (float)($ceiling['held'] ?? 0),
            'runs'             => $this->MQ->recent(50),
            'payouts'          => $this->_payoutHistory(300),
            'explorer_url'     => $this->_explorer(),
        ];
    }

    /** staking_matching_payouts joined to users + its (possibly still-pending) on-chain payout row. */
    private function _payoutHistory($limit)
    {
        $this->db->select(
                'smp.id, smp.user_id, smp.matched_volume, smp.total_percent, smp.earning_amount, ' .
                'smp.staking_amount, smp.left_before, smp.right_before, smp.run_ref, smp.created_at, ' .
                'u.username, u.referral_id, ' .
                'q.status AS payout_status, q.tx_hash AS payout_tx_hash, q.amount AS payout_amount'
            )
            ->from('staking_matching_payouts smp')
            ->join('users u', 'u.id = smp.user_id', 'left')
            ->join('blockchain_payout_queue q', "q.reference_type = 'staking_matching_payout' AND q.reference_id = smp.id", 'left')
            ->order_by('smp.id', 'DESC')->limit((int)$limit);
        $rows = $this->db->get()->result_array();
        return $this->_withCeilingContext($rows);
    }

    /**
     * Enrich each payout row with the recipient's CURRENT ceiling/held/own-stake
     * snapshot (not what it was at payout time — this is "where do they stand
     * right now", for admin eligibility review). Memoized per user_id since the
     * same recipient often appears across many rows.
     */
    private function _withCeilingContext(array $rows)
    {
        $this->load->model('Walletledger_model', 'WL');
        $cache = [];
        foreach ($rows as &$r) {
            $uid = (int)$r['user_id'];
            if (!isset($cache[$uid])) {
                $ceiling = $this->MB->userCeiling($uid);
                $paid = $this->MB->matchingPaidToDate($uid);
                $held = (float)($this->CW->balance($uid)['held_balance'] ?? 0);
                $wallet = $this->WL->balances($uid);
                $ownStake = (float)($this->db->select_sum('stake_amount')->where('user_id', $uid)
                                              ->where('status', 'active')->get('user_stakes')->row()->stake_amount ?? 0);
                $cache[$uid] = [
                    'ceiling_amount'    => round($ceiling, 4),
                    'ceiling_remaining' => round(max(0.0, $ceiling - $paid), 4),
                    'ceiling_held'      => round($held, 4),
                    'own_stake_amount'  => round($ownStake, 4),
                    'matching_eligible' => $ceiling > 0,
                    'wallet_usdt'       => round((float)($wallet['usdt'] ?? 0), 4),
                    'wallet_exchange'   => round((float)($wallet['exchange'] ?? 0), 4),
                    'wallet_earning'    => round((float)($wallet['earning'] ?? 0), 4),
                    'wallet_staking'    => round((float)($wallet['staking'] ?? 0), 4),
                    'wallet_bonus'      => round((float)($wallet['bonus'] ?? 0), 4),
                ];
            }
            $r += $cache[$uid];
        }
        return $rows;
    }

    /** Admin-triggered engine run (AJAX) — the same path BinaryMatchingPayoutCron uses. */
    public function run_now()
    {
        $this->_requireAdmin();
        if (!$this->input->is_ajax_request()) show_404();
        $result = $this->MQ->runClaimed(['enqueued_by' => (int)$this->session->userdata('admin_userid')]);
        return $this->_json(['status' => 'success', 'result' => $result]);
    }
}
