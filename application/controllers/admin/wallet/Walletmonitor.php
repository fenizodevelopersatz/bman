<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin ▸ Finance ▸ Wallet Monitor
 * Free custodial-wallet monitor: for every user with an on-chain deposit
 * address, read the real BEP-20 balance and compare it with our DB record.
 * A positive difference = funds arrived on-chain but are not yet credited —
 * the admin can Reconcile to credit the internal USDT balance (audited).
 *
 * Reads all chain values from the active Token Settings (no hardcoding);
 * balance reads are read-only, reconcile writes are Super-Admin only.
 */
class Walletmonitor extends CI_Controller
{
    private $is_super = false;

    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper(['url','security']);
        $this->load->model('Admin_model');
        $this->load->model('Custodialwallet_model', 'cw');

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
        $this->is_super = ($user && $user->admin_roll == '1');
    }

    private function _json($data = [], $code = 200)
    {
        $this->output->set_status_header($code)
                     ->set_content_type('application/json')
                     ->set_output(json_encode($data));
    }

    private function _adminId()
    {
        $id = (int)$this->session->userdata('admin_userid');
        if (!$id) $id = (int)$this->session->userdata('user_id');
        return $id;
    }

    /* ------------------------------- page ------------------------------- */
    public function index()
    {
        $data['title']      = 'Wallet Monitor';
        $data['card_tilte'] = 'Custodial Wallet Monitor (on-chain vs database)';
        $data['is_super']   = $this->is_super;
        // wallets with the owning user's USDT record; balances loaded via AJAX
        $data['wallets'] = $this->db->select('w.user_id, w.wallet_address, u.username, u.email, COALESCE(uw.usd_balance,0) AS db_usdt')
                                    ->from('user_wallet w')
                                    ->join('users u', 'u.id = w.user_id', 'left')
                                    ->join('user_wallets uw', 'uw.user_id = w.user_id', 'left')
                                    ->order_by('w.user_id', 'ASC')
                                    ->get()->result_array();
        $this->load->view('admin/wallet/wallet_monitor', $data);
    }

    /* ---------------- AJAX: live on-chain check for one user ------------- */
    public function check($user_id)
    {
        if (!$this->input->is_ajax_request()) show_404();
        try {
            $m = $this->cw->monitor((int)$user_id, $this->_adminId());
            if (!$m) return $this->_json(['status' => 'error', 'message' => 'No address'], 404);
            return $this->_json(['status' => 'success', 'data' => $m]);
        } catch (Exception $e) {
            return $this->_json(['status' => 'error', 'message' => $e->getMessage()], 502);
        }
    }

    /* ------------- AJAX: reconcile a positive difference (Super) --------- */
    public function reconcile($user_id)
    {
        if (!$this->input->is_ajax_request()) show_404();
        if (!$this->is_super) {
            return $this->_json(['status' => 'error', 'message' => 'Super Admin only.'], 403);
        }
        try {
            list($ok, $msg) = $this->cw->reconcile((int)$user_id, $this->_adminId(),
                $this->input->post('note', true));
            return $this->_json(['status' => $ok ? 'success' : 'error', 'message' => $msg], $ok ? 200 : 422);
        } catch (Exception $e) {
            return $this->_json(['status' => 'error', 'message' => $e->getMessage()], 502);
        }
    }

    /* ------------------ AJAX: generate address for a user --------------- */
    public function generate($user_id)
    {
        if (!$this->input->is_ajax_request()) show_404();
        if (!$this->is_super) {
            return $this->_json(['status' => 'error', 'message' => 'Super Admin only.'], 403);
        }
        try {
            $row = $this->cw->ensureAddress((int)$user_id);
            if (!$row) return $this->_json(['status' => 'error', 'message' => 'Could not generate address.'], 500);
            return $this->_json(['status' => 'success', 'address' => $row['wallet_address']]);
        } catch (Exception $e) {
            return $this->_json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /* -------------------------- AJAX: monitor log ----------------------- */
    public function log()
    {
        if (!$this->input->is_ajax_request()) show_404();
        return $this->_json(['status' => 'success', 'rows' => $this->cw->monitorLog(0, 200)]);
    }

    /* ------------- AJAX: run the deposit listener (auto-detect) ---------- *
     * Detects incoming USDT via BscScan API / RPC and credits confirmed
     * deposits. No private key involved. Super-Admin (writes the ledger).    */
    public function scan_deposits()
    {
        if (!$this->input->is_ajax_request()) show_404();
        if (!$this->is_super) {
            return $this->_json(['status' => 'error', 'message' => 'Super Admin only.'], 403);
        }
        $this->load->model('Depositlistener_model', 'listener');
        $only = (int)$this->input->post('user_id');
        $res = $this->listener->scan($only ?: null);
        return $this->_json([
            'status'  => !empty($res['ok']) ? 'success' : 'error',
            'message' => $res['message'] ?? 'done',
        ], !empty($res['ok']) ? 200 : 422);
    }

    /* ------------------------ AJAX: deposits list ----------------------- */
    public function deposits()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $this->load->model('Depositlistener_model', 'listener');
        return $this->_json(['status' => 'success', 'rows' => $this->listener->deposits(0, 200)]);
    }
}
