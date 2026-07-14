<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin: Binary Matching Bonus Management & History
 * Display bonus calculations, ceiling wallet, and cron status
 */
class BinaryMatchingAdmin extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('member/BinaryMatchingBonus_model');
        $this->load->model('member/BonusTransactionQueue_model');
        $this->load->model('member/CeilingWallet_model');

        // Check admin access
        if (!$this->session->userdata('is_admin')) {
            redirect('login');
        }
    }

    /**
     * Dashboard: Overview & Summary
     * GET: /admin/binary-matching/dashboard
     */
    public function dashboard()
    {
        $data = [
            'page_title' => 'Binary Matching Bonus Dashboard',
            'summary' => $this->getSummary(),
            'cron_status' => $this->getCronStatus(),
            'level_distribution' => $this->getLevelDistribution(),
            'ceiling_status' => $this->CeilingWallet_model->getAdminCeilingSummary(),
            'payout_queue' => $this->BonusTransactionQueue_model->getQueueStatus(),
            'recent_bonuses' => $this->getRecentBonuses(10),
        ];

        $this->load->view('admin/binary_matching/dashboard', $data);
    }

    /**
     * Detailed Bonus History Table
     * GET: /admin/binary-matching/history
     */
    public function history()
    {
        $page = $this->input->get('page', true) ? (int)$this->input->get('page') : 1;
        $level_filter = $this->input->get('level', true);
        $status_filter = $this->input->get('status', true);
        $user_filter = $this->input->get('user_id', true);
        $per_page = 50;
        $offset = ($page - 1) * $per_page;

        // Build query
        $this->db->select('
            bmb.id, bmb.purchase_id, bmb.bonus_recipient_id, bmb.bonus_payer_id,
            bmb.level, bmb.left_leg_volume, bmb.right_leg_volume, bmb.qualifying_volume,
            bmb.bonus_amount_total, bmb.bonus_earning, bmb.bonus_staking,
            bmb.status, bmb.ceiling_amount_held, bmb.created_at, bmb.distributed_at,
            u_recipient.username as recipient_name, u_recipient.exchange_balance,
            u_payer.username as payer_name
        ');
        $this->db->from('binary_matching_bonus_ledger bmb');
        $this->db->join('users u_recipient', 'bmb.bonus_recipient_id = u_recipient.id', 'left');
        $this->db->join('users u_payer', 'bmb.bonus_payer_id = u_payer.id', 'left');

        if ($level_filter) {
            $this->db->where('bmb.level', (int)$level_filter);
        }
        if ($status_filter) {
            $this->db->where('bmb.status', $status_filter);
        }
        if ($user_filter) {
            $this->db->where('bmb.bonus_recipient_id', (int)$user_filter);
        }

        $this->db->order_by('bmb.created_at', 'DESC');
        $this->db->limit($per_page, $offset);

        $bonuses = $this->db->get()->result_array();

        // Get total count
        $count_query = $this->db->select('COUNT(*) as total')
            ->from('binary_matching_bonus_ledger bmb');
        if ($level_filter) $count_query->where('bmb.level', (int)$level_filter);
        if ($status_filter) $count_query->where('bmb.status', $status_filter);
        if ($user_filter) $count_query->where('bmb.bonus_recipient_id', (int)$user_filter);
        $total = $count_query->get()->row()->total;

        $data = [
            'page_title' => 'Binary Matching Bonus History',
            'bonuses' => $bonuses,
            'current_page' => $page,
            'per_page' => $per_page,
            'total' => $total,
            'total_pages' => ceil($total / $per_page),
            'level_filter' => $level_filter,
            'status_filter' => $status_filter,
            'user_filter' => $user_filter,
        ];

        $this->load->view('admin/binary_matching/history', $data);
    }

    /**
     * User Ceiling Wallet Details
     * GET: /admin/binary-matching/ceiling-wallets
     */
    public function ceiling_wallets()
    {
        $this->db->select('
            cw.user_id, cw.balance as current_hold, cw.hold_type, cw.hold_reason,
            u.username, u.exchange_balance, cfg.ceiling_cap, cfg.threshold_amount
        ');
        $this->db->from('ceiling_wallet cw');
        $this->db->join('users u', 'cw.user_id = u.id', 'left');
        $this->db->join('ceiling_wallet_config cfg', 'u.exchange_balance >= cfg.threshold_amount', 'left');
        $this->db->order_by('cw.balance', 'DESC');

        $ceiling_wallets = $this->db->get()->result_array();

        $data = [
            'page_title' => 'Ceiling Wallet Management',
            'ceiling_wallets' => $ceiling_wallets,
            'admin_ceiling' => $this->CeilingWallet_model->getAdminCeilingSummary(),
            'ceiling_ledger' => $this->getCeilingLedger(20),
        ];

        $this->load->view('admin/binary_matching/ceiling_wallets', $data);
    }

    /**
     * Blockchain Payout Queue Monitor
     * GET: /admin/binary-matching/payout-queue
     */
    public function payout_queue()
    {
        $status_filter = $this->input->get('status', true);
        $page = $this->input->get('page', true) ? (int)$this->input->get('page') : 1;
        $per_page = 50;
        $offset = ($page - 1) * $per_page;

        $this->db->select('
            bpq.id, bpq.bonus_ledger_id, bpq.wallet_type, bpq.to_user_id, bpq.amount,
            bpq.transaction_hash, bpq.status, bpq.retry_count, bpq.last_error,
            bpq.gas_used, bpq.created_at, bpq.updated_at,
            u.username
        ');
        $this->db->from('blockchain_payout_queue bpq');
        $this->db->join('users u', 'bpq.to_user_id = u.id', 'left');

        if ($status_filter) {
            $this->db->where('bpq.status', $status_filter);
        }

        $this->db->order_by('bpq.created_at', 'DESC');
        $this->db->limit($per_page, $offset);

        $payouts = $this->db->get()->result_array();

        // Status summary
        $status_summary = $this->db->query(
            "SELECT status, COUNT(*) as count, SUM(amount) as total
             FROM blockchain_payout_queue
             GROUP BY status"
        )->result_array();

        $data = [
            'page_title' => 'Payout Queue Monitor',
            'payouts' => $payouts,
            'status_summary' => $status_summary,
            'current_page' => $page,
            'per_page' => $per_page,
            'status_filter' => $status_filter,
            'admin_gas' => $this->db->get_where('admin_ceiling_wallet', ['id' => 1])->row(),
        ];

        $this->load->view('admin/binary_matching/payout_queue', $data);
    }

    /**
     * Cron Execution History & Logs
     * GET: /admin/binary-matching/cron-logs
     */
    public function cron_logs()
    {
        $logs = $this->db->query(
            "SELECT * FROM binary_matching_queue
             ORDER BY created_at DESC
             LIMIT 50"
        )->result_array();

        $data = [
            'page_title' => 'Cron Execution Logs',
            'logs' => $logs,
            'next_run' => $this->calculateNextCronRun(),
            'frequency' => '3 minutes',
            'total_runs_today' => $this->db->query(
                "SELECT COUNT(*) as count FROM binary_matching_queue
                 WHERE DATE(created_at) = CURDATE()"
            )->row()->count,
        ];

        $this->load->view('admin/binary_matching/cron_logs', $data);
    }

    /**
     * Manual Actions
     */
    public function retry_failed_payouts()
    {
        $count = $this->BonusTransactionQueue_model->retryFailedPayouts();

        $response = [
            'status' => true,
            'message' => "Retried $count failed payouts",
            'count' => $count,
        ];

        header('Content-Type: application/json');
        echo json_encode($response);
    }

    public function release_ceiling_hold($user_id)
    {
        $user_id = (int)$user_id;
        $result = $this->CeilingWallet_model->releaseCeilingHold($user_id);

        if (isset($result['error'])) {
            $response = ['status' => false, 'message' => $result['error']];
        } else {
            $response = [
                'status' => true,
                'message' => 'Ceiling hold released',
                'released' => $result['released'],
                'remaining' => $result['remaining'],
            ];
        }

        header('Content-Type: application/json');
        echo json_encode($response);
    }

    public function add_admin_gas()
    {
        $amount = $this->input->post('amount', true);
        $amount = (float)$amount;

        if ($amount <= 0) {
            echo json_encode(['status' => false, 'message' => 'Invalid amount']);
            return;
        }

        $result = $this->CeilingWallet_model->addAdminGas($amount);

        header('Content-Type: application/json');
        echo json_encode([
            'status' => $result,
            'message' => $result ? "Added $amount BNB to admin gas" : 'Failed to add gas',
        ]);
    }

    // ====== PRIVATE HELPERS ======

    private function getSummary()
    {
        $today = date('Y-m-d');

        return [
            'total_bonuses' => $this->db->query(
                "SELECT COUNT(*) as count FROM binary_matching_bonus_ledger
                 WHERE DATE(created_at) = ?"
            , [$today])->row()->count,

            'total_distributed' => $this->db->query(
                "SELECT SUM(bonus_earning + bonus_staking) as total FROM binary_matching_bonus_ledger
                 WHERE DATE(created_at) = ?"
            , [$today])->row()->total ?? 0,

            'ceiling_holds' => $this->db->query(
                "SELECT COUNT(*) as count FROM binary_matching_bonus_ledger
                 WHERE DATE(created_at) = ? AND status = 'HELD_CEILING'"
            , [$today])->row()->count,

            'ceiling_amount' => $this->db->query(
                "SELECT SUM(ceiling_amount_held) as total FROM binary_matching_bonus_ledger
                 WHERE DATE(created_at) = ?"
            , [$today])->row()->total ?? 0,

            'pending_payouts' => $this->db->query(
                "SELECT COUNT(*) as count FROM blockchain_payout_queue
                 WHERE status IN ('PENDING', 'RETRYING')"
            )->row()->count,
        ];
    }

    private function getCronStatus()
    {
        $last_run = $this->db->query(
            "SELECT * FROM binary_matching_queue ORDER BY created_at DESC LIMIT 1"
        )->row();

        return [
            'last_run' => $last_run ? $last_run->created_at : 'Never',
            'last_status' => $last_run ? $last_run->status : 'N/A',
            'levels_processed' => $last_run ? $last_run->levels_processed : 0,
            'next_run' => $this->calculateNextCronRun(),
            'frequency' => 'Every 3 minutes',
            'runs_today' => $this->db->query(
                "SELECT COUNT(*) as count FROM binary_matching_queue
                 WHERE DATE(created_at) = CURDATE()"
            )->row()->count,
        ];
    }

    private function getLevelDistribution()
    {
        return $this->db->query(
            "SELECT
                level,
                COUNT(*) as recipients,
                SUM(bonus_earning) as total_earning,
                SUM(bonus_staking) as total_staking,
                SUM(bonus_amount_total) as total_bonus,
                COUNT(CASE WHEN status = 'DISTRIBUTED' THEN 1 END) as confirmed,
                COUNT(CASE WHEN status = 'HELD_CEILING' THEN 1 END) as held
            FROM binary_matching_bonus_ledger
            WHERE DATE(created_at) = CURDATE()
            GROUP BY level
            ORDER BY level ASC"
        )->result_array();
    }

    private function getRecentBonuses($limit = 10)
    {
        return $this->db->query(
            "SELECT
                bmb.id, bmb.level, bmb.bonus_recipient_id, bmb.qualifying_volume,
                bmb.bonus_earning, bmb.bonus_staking, bmb.status, bmb.created_at,
                u.username
            FROM binary_matching_bonus_ledger bmb
            JOIN users u ON bmb.bonus_recipient_id = u.id
            ORDER BY bmb.created_at DESC
            LIMIT ?",
            [$limit]
        )->result_array();
    }

    private function getCeilingLedger($limit = 20)
    {
        return $this->db->query(
            "SELECT
                cwl.id, cwl.user_id, cwl.transaction_type, cwl.amount,
                cwl.from_wallet, cwl.reason, cwl.created_at,
                u.username
            FROM ceiling_wallet_ledger cwl
            JOIN users u ON cwl.user_id = u.id
            ORDER BY cwl.created_at DESC
            LIMIT ?",
            [$limit]
        )->result_array();
    }

    private function calculateNextCronRun()
    {
        $current_minute = (int)date('i');
        $next_interval = ceil($current_minute / 3) * 3;

        if ($next_interval >= 60) {
            $next_time = strtotime('+1 hour', strtotime(date('Y-m-d H:00:00')));
        } else {
            $next_time = strtotime(date('Y-m-d H:' . str_pad($next_interval, 2, '0', STR_PAD_LEFT) . ':00'));
        }

        return date('Y-m-d H:i:s', $next_time);
    }
}
