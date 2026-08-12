<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin ▸ Staking ▸ Swap Orders
 * View on-chain USDT<->BMAN swap orders and retry parked ones
 * (failed_gas / failed_usdt / failed_credit) via Swapengine_model::resume().
 */
class Swaporders extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper(['url']);
        $this->load->model('Admin_model');
        $this->load->model('staking/Swapengine_model', 'SW');
        if (!$this->session->userdata('admin_logged_in')) redirect('aaddmmiinn/login');
        $user = $this->Admin_model->get_user($this->session->userdata('admin_userid'));
        if ($user && $user->admin_roll == '1') {
            $perm = json_decode($user->permission_pages, true);
            if (empty($perm['staking_management']) && empty($perm['wallet_management'])) {
                $this->session->set_flashdata('error', 'Access Denied: You do not have permission.');
                redirect('admin');
            }
        }
    }

    public function index()
    {
        $data['title']      = 'Swap Orders';
        $data['card_tilte'] = 'On-chain USDT ⇄ BMAN Swap Orders';
        $cfg = $this->db->select('swap_enabled, swap_dry_run, swap_auto_gas')
                        ->get_where('token_settings', ['status'=>1])->row_array();
        $data['cfg']    = $cfg ?: ['swap_enabled'=>0,'swap_dry_run'=>1,'swap_auto_gas'=>1];
        $data['orders'] = $this->db->select('o.*, u.username, u.referral_id, u.email', false)
                                   ->from('staking_swap_orders o')
                                   ->join('users u', 'u.id = o.user_id', 'left')
                                   ->order_by('o.id', 'DESC')->limit(300)->get()->result_array();
        $this->load->view('admin/staking/swap_orders', $data);
    }

    /** AJAX: retry a parked order. */
    public function retry($id)
    {
        if (!$this->input->is_ajax_request()) show_404();
        list($ok, $msg) = $this->SW->resume((int)$id);
        $this->output->set_content_type('application/json')
                     ->set_output(json_encode(['status' => $ok ? 'success' : 'error', 'message' => $msg]));
    }

    /** AJAX: deliver the BMAN on-chain for one order. */
    public function deliver($id)
    {
        if (!$this->input->is_ajax_request()) show_404();
        list($ok, $msg) = $this->SW->deliverBman((int)$id);
        $this->output->set_content_type('application/json')
                     ->set_output(json_encode(['status' => $ok ? 'success' : 'error', 'message' => $msg]));
    }

    /**
     * Cron / CLI: deliver BMAN on-chain for every completed order that still
     * needs it. CLI, or HTTP with ?token=<cron_token>.
     *   php index.php admin/staking/swaporders deliver_cron
     */
    public function deliver_cron()
    {
        if (!is_cli()) {
            $expected = $this->config->item('cron_token');
            if (!$expected || $this->input->get('token', true) !== $expected) show_404();
        }
        $res = $this->SW->deliverPending();
        echo json_encode(['status' => 'success', 'result' => $res, 'ran_at' => date('Y-m-d H:i:s')]).PHP_EOL;
    }
}
