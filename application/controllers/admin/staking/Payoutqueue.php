<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin ▸ Staking ▸ Binary Matching Payout Queue.
 * Browser + admin-operable retry for blockchain_payout_queue. Retry only
 * resets a FAILED/RETRY row's on-chain state (Blockchainpayout_model::retry)
 * — the internal wallet credit already happened when the matching engine
 * ran, long before this row existed, so retry can never double-credit;
 * the next BinaryMatchingPayoutCron tick rebroadcasts.
 */
class Payoutqueue extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper(['url']);
        $this->load->library('session');
        $this->load->model('Admin_model');
        $this->load->model('staking/Blockchainpayout_model', 'PQ');
    }

    private function _requireAdmin()
    {
        if (!$this->session->userdata('admin_logged_in')) redirect('admin/login');
        $user = $this->Admin_model->get_user($this->session->userdata('admin_userid'));
        if ($user && $user->admin_roll == '1') {
            $perm = json_decode($user->permission_pages, true);
            if (empty($perm['staking_management']) && empty($perm['wallet_management'])) {
                $this->session->set_flashdata('error', 'Access Denied: You do not have permission.');
                redirect('admin');
            }
        }
    }

    private function _json($d, $c = 200)
    {
        $this->output->set_status_header($c)->set_content_type('application/json')->set_output(json_encode($d));
    }

    private function _explorer()
    {
        $ts = $this->db->select('explorer_url')->get_where('token_settings', ['status' => 1])->row_array();
        return rtrim($ts['explorer_url'] ?? 'https://bscscan.com', '/');
    }

    public function index()
    {
        $this->_requireAdmin();
        $status = $this->input->get('status', true);

        $data['title']        = 'Binary Matching Payout Queue';
        $data['summary']      = $this->PQ->summary();
        $data['rows']         = $this->PQ->list(['limit' => 300, 'status' => $status ?: null]);
        $data['status_filter']= $status;
        $data['explorer_url'] = $this->_explorer();
        $this->load->view('admin/staking/payout_queue', $data);
    }

    /** Reset a FAILED/RETRY payout back to PENDING (AJAX) — never credits anything. */
    public function retry($id)
    {
        $this->_requireAdmin();
        if (!$this->input->is_ajax_request()) show_404();
        list($ok, $msg) = $this->PQ->retry((int)$id, (int)$this->session->userdata('admin_userid'));
        return $this->_json(['status' => $ok ? 'success' : 'error', 'message' => $msg], $ok ? 200 : 422);
    }
}
