<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin ▸ Finance ▸ Wallet Ledger API
 * Unified backend for all wallet_ledger movements linked to source transaction
 * tables, plus maturity/release date management.
 */
class Walletledger extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper(['url']);
        $this->load->model('Admin_model');
        $this->load->model('WalletTracker_model', 'tracker');
        $this->load->model('WalletMaturity_model', 'maturity');

        if (!$this->session->userdata('admin_logged_in')) redirect('aaddmmiinn/login');

        $user = $this->Admin_model->get_user($this->session->userdata('admin_userid'));
        if ($user && $user->admin_roll == '1') {
            $perm = json_decode($user->permission_pages, true);
            if (empty($perm['wallet_management'])) {
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

    private function _filters()
    {
        return [
            'user_id'        => $this->input->post('user_id', true) ?: $this->input->get('user_id', true),
            'wallet_type'    => $this->input->post('wallet_type', true) ?: $this->input->get('wallet_type', true),
            'reference_type' => $this->input->post('reference_type', true) ?: $this->input->get('reference_type', true),
            'category'       => $this->input->post('category', true) ?: $this->input->get('category', true),
            'direction'      => $this->input->post('direction', true) ?: $this->input->get('direction', true),
            'maturity'       => $this->input->post('maturity', true) ?: $this->input->get('maturity', true),
            'date_from'      => $this->input->post('date_from', true) ?: $this->input->get('date_from', true),
            'date_to'        => $this->input->post('date_to', true) ?: $this->input->get('date_to', true),
            'search'         => $this->input->post('search', true) ?: $this->input->get('search', true),
            'tx_hash'        => $this->input->post('tx_hash', true) ?: $this->input->get('tx_hash', true),
        ];
    }

    /** Catalog of reference types + categories for filter dropdowns. */
    public function options()
    {
        $this->_json([
            'status'          => true,
            'reference_types' => $this->tracker->reference_types(),
            'categories'      => $this->tracker->categories(),
            'maturity_rules'  => $this->maturity->rules(),
            'wallets'         => ['usdt', 'exchange', 'earning', 'staking', 'bonus'],
        ]);
    }

    /** Paginated ledger list with source table links. */
    public function list()
    {
        $filters = array_filter($this->_filters(), function ($v) { return $v !== '' && $v !== null; });
        $page    = max(1, (int) ($this->input->post('page', true) ?: $this->input->get('page', true) ?: 1));
        $limit   = min(200, max(1, (int) ($this->input->post('limit', true) ?: $this->input->get('limit', true) ?: 50)));
        $offset  = ($page - 1) * $limit;

        $total = $this->tracker->count_transactions($filters);
        $rows  = $this->tracker->list_transactions($filters, $limit, $offset);

        $this->_json([
            'status' => true,
            'total'  => $total,
            'page'   => $page,
            'limit'  => $limit,
            'rows'   => $rows,
        ]);
    }

    /** Single ledger row with full source record + related entries. */
    public function detail()
    {
        $id = (int) ($this->input->get('id', true) ?: $this->input->post('id', true));
        if (!$id) return $this->_json(['status' => false, 'message' => 'Ledger id required'], 400);

        $row = $this->tracker->transaction_detail($id);
        if (!$row) return $this->_json(['status' => false, 'message' => 'Not found'], 404);

        $this->_json(['status' => true, 'row' => $row]);
    }

    /** Per-user wallet summary + maturity breakdown. */
    public function user_summary()
    {
        $uid = (int) ($this->input->get('user_id', true) ?: $this->input->post('user_id', true));
        if (!$uid) return $this->_json(['status' => false, 'message' => 'user_id required'], 400);

        $this->_json(['status' => true, 'summary' => $this->tracker->user_summary($uid)]);
    }

    /** Credit lots with release dates for a user. */
    public function maturity_credits()
    {
        $uid = (int) ($this->input->post('user_id', true) ?: $this->input->get('user_id', true));
        if (!$uid) return $this->_json(['status' => false, 'message' => 'user_id required'], 400);

        $filters = array_filter([
            'wallet_type' => $this->input->post('wallet_type', true) ?: $this->input->get('wallet_type', true),
            'maturity'    => $this->input->post('maturity', true) ?: $this->input->get('maturity', true),
            'date_from'   => $this->input->post('date_from', true) ?: $this->input->get('date_from', true),
            'date_to'     => $this->input->post('date_to', true) ?: $this->input->get('date_to', true),
        ], function ($v) { return $v !== '' && $v !== null; });

        $page   = max(1, (int) ($this->input->post('page', true) ?: 1));
        $limit  = min(200, max(1, (int) ($this->input->post('limit', true) ?: 50)));
        $offset = ($page - 1) * $limit;

        $this->_json([
            'status' => true,
            'total'  => $this->tracker->count_maturity_credits($uid, $filters),
            'page'   => $page,
            'limit'  => $limit,
            'rows'   => $this->tracker->maturity_credits($uid, $filters, $limit, $offset),
        ]);
    }

    /** Admin: set release date on a credit lot. */
    public function set_maturity()
    {
        if (!$this->input->is_ajax_request()) show_404();

        $id   = (int) $this->input->post('ledger_id', true);
        $date = trim((string) $this->input->post('maturity_date', true));
        if (!$id || !$date) {
            return $this->_json(['status' => false, 'message' => 'ledger_id and maturity_date required'], 400);
        }

        $admin_id = (int) $this->session->userdata('admin_userid');
        list($ok, $res) = $this->maturity->set_credit_maturity($id, $date, $admin_id);
        if (!$ok) return $this->_json(['status' => false, 'message' => $res], 422);

        $this->_json(['status' => true, 'message' => 'Release date updated', 'row' => $this->tracker->enrich_row($res, true)]);
    }

    /** Admin: immediately unlock a credit lot. */
    public function force_mature()
    {
        if (!$this->input->is_ajax_request()) show_404();

        $id = (int) $this->input->post('ledger_id', true);
        if (!$id) return $this->_json(['status' => false, 'message' => 'ledger_id required'], 400);

        $admin_id = (int) $this->session->userdata('admin_userid');
        list($ok, $res) = $this->maturity->force_mature_credit($id, $admin_id);
        if (!$ok) return $this->_json(['status' => false, 'message' => $res], 422);

        $this->_json(['status' => true, 'message' => 'Credit matured', 'row' => $this->tracker->enrich_row($res, true)]);
    }

    /** Run daily maturity flip (same as WalletMaturity_cron). */
    public function run_maturity()
    {
        if (!$this->input->is_ajax_request()) show_404();

        $updated = $this->maturity->process_daily_maturity();
        $this->_json([
            'status'  => true,
            'message' => "Matured {$updated} credit lot(s)",
            'updated' => $updated,
            'ran_at'  => date('Y-m-d H:i:s'),
        ]);
    }
}
