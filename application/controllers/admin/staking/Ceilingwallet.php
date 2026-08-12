<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin ▸ Staking ▸ Ceiling Wallet (system-only).
 * View total + user-wise ceiling-held balances and the release history, and
 * release / adjust held amounts. The Ceiling Wallet is backend-only; members
 * never see it. All writes go through Ceilingwallet_model (ledger-based).
 */
class Ceilingwallet extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper(['url']);
        $this->load->library('session');
        $this->load->model('staking/Ceilingwallet_model', 'CW');
        $this->load->model('Admin_model');
    }

    private function _requireAdmin()
    {
        if (!$this->session->userdata('admin_logged_in')) redirect('aaddmmiinn/login');
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

    /** Ceiling Wallet dashboard: total held, user-wise, and history. */
    public function index()
    {
        $this->_requireAdmin();
        $data['title']        = 'Ceiling Wallet';
        $data['total_held']   = $this->CW->totalHeld();
        $data['held_by_user'] = $this->CW->heldByUser(['limit' => 500]);
        $data['history']      = $this->CW->history(['limit' => 300]);
        $this->load->view('admin/staking/ceiling_wallet', $data);
    }

    /** Release a held amount back to the member (AJAX). */
    public function release()
    {
        $this->_requireAdmin();
        if (!$this->input->is_ajax_request()) show_404();
        $uid    = (int)$this->input->post('user_id', true);
        $amount = (float)$this->input->post('amount', true);
        $wallet = $this->input->post('credit_wallet', true) ?: 'earning';
        $note   = $this->input->post('note', true);
        if ($uid <= 0 || $amount <= 0) return $this->_json(['status' => 'error', 'message' => 'Invalid user or amount.'], 422);

        list($ok, $res) = $this->CW->release($uid, $amount, [
            'credit_wallet'  => in_array($wallet, ['earning','staking','exchange','bonus'], true) ? $wallet : 'earning',
            'reference_type' => 'admin_release',
            'reference_id'   => 'REL-'.date('YmdHis'),
            'description'    => $note ?: 'Admin ceiling release',
            'created_by'     => (int)$this->session->userdata('admin_userid'),
        ]);
        return $this->_json($ok ? ['status' => 'success', 'ledger_id' => $res]
                                : ['status' => 'error', 'message' => $res], $ok ? 200 : 422);
    }

    /** Manual adjustment (signed): +into ceiling, -out of ceiling (AJAX). */
    public function adjust()
    {
        $this->_requireAdmin();
        if (!$this->input->is_ajax_request()) show_404();
        $uid    = (int)$this->input->post('user_id', true);
        $amount = (float)$this->input->post('amount', true);
        $note   = $this->input->post('note', true);
        if ($uid <= 0 || $amount == 0) return $this->_json(['status' => 'error', 'message' => 'Invalid user or amount.'], 422);

        list($ok, $res) = $this->CW->adjust($uid, $amount, [
            'reference_type' => 'admin_adjust',
            'reference_id'   => 'ADJ-'.date('YmdHis'),
            'description'    => $note ?: 'Admin ceiling adjustment',
            'created_by'     => (int)$this->session->userdata('admin_userid'),
        ]);
        return $this->_json($ok ? ['status' => 'success', 'ledger_id' => $res]
                                : ['status' => 'error', 'message' => $res], $ok ? 200 : 422);
    }
}
