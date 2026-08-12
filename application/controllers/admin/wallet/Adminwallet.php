<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin ▸ Finance ▸ Admin (Bonus) Wallet
 * The reclaimed-bonus pool + the Bonus Wallet 60-day reduction history.
 * Shows the admin_wallet balance, the current reduction settings, and every
 * reduction (bonus_reduction_log). Super-Admin can Preview / Run the reduction.
 *
 * Reduction logic lives in Bonusreduction_model (shared with Bonusreductioncron).
 * See docs/11_ADMIN_WALLET_MANAGEMENT.md.
 */
class Adminwallet extends CI_Controller
{
    private $is_super = false;

    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper(['url']);
        $this->load->model('Admin_model');
        $this->load->model('Bonusreduction_model', 'reduction');

        if (!$this->session->userdata('admin_logged_in')) {
            redirect('aaddmmiinn/login');
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

    /* ------------------------------- page ------------------------------- */
    public function index()
    {
        $settings = $this->db->get_where('staking_bonus_settings', ['id' => 1])->row_array();
        $ts = $this->db->select('explorer_url')->get_where('token_settings', ['status' => 1])->row_array();

        $data['title']        = 'Admin Bonus Wallet';
        $data['card_tilte']   = 'Bonus Wallet Reduction — Admin Pool & History';
        $data['is_super']     = $this->is_super;
        $data['wallet']       = $this->reduction->adminWallet();
        $data['totals']       = $this->reduction->totals();
        $data['settings']     = $settings ?: [];
        $data['admin_addr']   = $this->reduction->adminAddress();
        $data['explorer_url'] = rtrim($ts['explorer_url'] ?? 'https://bscscan.com', '/');
        $data['history']      = $this->reduction->history(200);

        $this->load->view('admin/wallet/admin_wallet', $data);
    }

    /* ------------- AJAX: preview or run the reduction (Super) ------------ */
    public function run_now()
    {
        if (!$this->input->is_ajax_request()) show_404();
        if (!$this->is_super) {
            return $this->_json(['status' => 'error', 'message' => 'Super Admin only.'], 403);
        }
        // dry=1 → preview (no writes); dry=0 → execute per the reduction_onchain setting.
        $dry = $this->input->post('dry', true) === '1';
        $out = $this->reduction->run([
            'force_dry_run' => $dry,
            'triggered_by'  => 'admin',
        ]);
        return $this->_json($out, ($out['status'] ?? '') === 'success' ? 200 : 422);
    }
}
