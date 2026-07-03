<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin ▸ Finance ▸ Internal Wallet Transfers (doc 9)
 * Read-only grid of user-initiated internal wallet-to-wallet transfers, with
 * filters and a detail modal. Money moves live in wallet_ledger.
 */
class Internaltransfers extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper(['url','security']);
        $this->load->model('Admin_model');
        if (!$this->session->userdata('admin_logged_in')) redirect('admin/login');
        $user = $this->Admin_model->get_user($this->session->userdata('admin_userid'));
        if ($user && $user->admin_roll == '1') {
            $perm = json_decode($user->permission_pages, true);
            if (empty($perm['wallet_management']) && empty($perm['finance_wallet_transfer'])) {
                $this->session->set_flashdata('error', 'Access Denied: You do not have permission.');
                redirect('admin');
            }
        }
        $this->load->model('wallet/Wallettransfer_model', 'WT');
    }

    public function index()
    {
        $f = [
            'reference' => $this->input->get('reference', true),
            'user'      => $this->input->get('user', true),
            'wallet'    => $this->input->get('wallet', true),
            'status'    => $this->input->get('status', true),
            'date_from' => $this->input->get('date_from', true),
            'date_to'   => $this->input->get('date_to', true),
        ];
        $data['transfers']  = $this->WT->adminList($f, 200, 0);
        $data['filters']    = $f;
        $data['title']      = 'Internal Wallet Transfers';
        $data['card_tilte'] = 'Internal Wallet Transfers (user → own wallets)';
        $this->load->view('admin/wallet/internal_transfers', $data);
    }

    public function detail()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $ref = $this->input->get('ref', true);
        $d = $this->WT->detail($ref);
        $this->output->set_content_type('application/json')
                     ->set_output(json_encode(['status' => $d ? 'success' : 'error', 'data' => $d]));
    }
}
