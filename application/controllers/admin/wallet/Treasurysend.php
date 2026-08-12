<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin ▸ Finance ▸ Treasury Direct Send — sends NEW BMAN straight from the
 * Treasury wallet to a chosen user's on-chain address (manual reward /
 * airdrop / adjustment). No internal ledger wallet is touched; see
 * Treasurysend_model's class doc for why this is a separate feature from
 * the wallet-transfer settlement engine.
 */
class Treasurysend extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper(['url', 'security']);
        $this->load->model('Admin_model');
        if (!$this->session->userdata('admin_logged_in')) redirect('aaddmmiinn/login');
        $user = $this->Admin_model->get_user($this->session->userdata('admin_userid'));
        if ($user && $user->admin_roll == '1') {
            $perm = json_decode($user->permission_pages, true);
            if (empty($perm['wallet_management'])) {
                $this->session->set_flashdata('error', 'Access Denied: You do not have permission.');
                redirect('admin');
            }
        }
        $this->load->model('wallet/Treasurysend_model', 'TDS');
    }

    private function _json($d, $c = 200)
    {
        $this->output->set_status_header($c)->set_content_type('application/json')->set_output(json_encode($d));
    }
    private function _adminId()
    {
        $id = (int)$this->session->userdata('admin_userid');
        if (!$id) $id = (int)$this->session->userdata('user_id');
        return $id;
    }

    public function index()
    {
        $data['title']      = 'Treasury Direct Send';
        $data['card_tilte'] = 'Treasury Direct Send';
        $data['settings']   = $this->TDS->settings();
        $data['history']    = $this->TDS->history(100, 0);
        $this->load->view('admin/wallet/treasury_send', $data);
    }

    /** AJAX (Select2): searchable active-member list, only members with an on-chain wallet. */
    public function users()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $q = trim((string)$this->input->get('q', true));
        $page = max(1, (int)$this->input->get('page'));
        $per = 20; $offset = ($page - 1) * $per;

        $this->db->from('users u')->join('user_wallet w', 'w.user_id = u.id', 'inner')->where('u.status', '1');
        if ($q !== '') {
            $this->db->group_start()->like('u.username', $q)->or_like('u.referral_id', $q)
                     ->or_like('u.email', $q)->or_like('u.id', $q)->group_end();
        }
        $total = $this->db->count_all_results('', false);
        $rows = $this->db->select('u.id, u.username, u.referral_id, u.email')
                         ->order_by('u.id', 'ASC')->limit($per, $offset)->get()->result_array();

        $results = [];
        foreach ($rows as $u) {
            $label = '#'.$u['id'].' '.$u['username'].' ('.$u['referral_id'].')';
            if (!empty($u['email'])) $label .= ' · '.$u['email'];
            $results[] = ['id' => (int)$u['id'], 'text' => $label];
        }
        return $this->_json(['results' => $results, 'pagination' => ['more' => ($offset + count($rows)) < $total]]);
    }

    public function send()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $out = $this->TDS->send(
            (int)$this->input->post('user_id'),
            $this->input->post('amount', true),
            $this->input->post('reason', true),
            $this->_adminId()
        );
        if (empty($out['ok'])) return $this->_json(['status' => 'error', 'message' => $out['message']], 422);
        return $this->_json(['status' => 'success', 'message' => $out['message'], 'data' => $out]);
    }

    public function updateSettings()
    {
        if (!$this->input->is_ajax_request()) show_404();
        list($ok, $msg) = $this->TDS->updateSettings([
            'enabled' => $this->input->post('enabled') ? 1 : 0,
            'dry_run' => $this->input->post('dry_run') ? 1 : 0,
            'min_treasury_reserve' => $this->input->post('min_treasury_reserve', true) !== '' ? $this->input->post('min_treasury_reserve', true) : '0',
        ], $this->_adminId());
        return $this->_json(['status' => $ok ? 'success' : 'error', 'message' => $msg]);
    }
}
