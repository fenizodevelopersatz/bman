<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin ▸ Staking ▸ Bonus & Matching
 * Proposal §7 (Bonus Coin System): staking bonus default %, the 60-day / 50%
 * bonus-wallet reduction rule, and the transfer restrictions (direct left /
 * right sponsored member only, email OTP + transfer password).
 * Proposal §9 (Binary Matching Bonus): total 10% split as 8% Earning Coin +
 * 2% Staking Coin — all percentages admin-adjustable (split must equal total).
 */
class Bonussettings extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper(['url','security']);
        $this->load->model('Admin_model');
        $this->load->model('Staking_model', 'staking');

        if (!$this->session->userdata('admin_logged_in')) {
            redirect('admin/login');
        }
        $user = $this->Admin_model->get_user($this->session->userdata('admin_userid'));
        if ($user && $user->admin_roll == '1') {
            $permissions = json_decode($user->permission_pages, true);
            if (empty($permissions['staking_management']) && empty($permissions['commission_settings'])) {
                $this->session->set_flashdata('error', 'Access Denied: You do not have permission.');
                redirect('admin');
            }
        }
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
        $data['title']      = 'Bonus & Matching Settings';
        $data['card_tilte'] = 'Bonus Coin System & Binary Matching Bonus';
        $data['settings']   = $this->staking->bonusSettings();
        $this->load->view('admin/staking/bonus_settings', $data);
    }

    /* ------------------------- AJAX: save all --------------------------- */
    public function save()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $fields = [
            'bonus_percent_default',
            'reduction_enabled','reduction_interval_days','reduction_percent',
            'transfer_enabled','transfer_to_direct_left','transfer_to_direct_right',
            'transfer_require_email_otp','transfer_require_transfer_password',
            'matching_total_percent','matching_earning_percent','matching_staking_percent',
        ];
        $data = [];
        foreach ($fields as $f) {
            $v = $this->input->post($f, true);
            if ($v !== null) $data[$f] = $v;
        }
        list($ok, $msg) = $this->staking->saveBonusSettings($data, $this->_adminId());
        return $this->_json(['status' => $ok ? 'success' : 'error', 'message' => $msg], $ok ? 200 : 422);
    }

    /* ---- AJAX: push the default bonus % to every package (§7 helper) ---- */
    public function apply_to_packages()
    {
        if (!$this->input->is_ajax_request()) show_404();
        list($ok, $msg) = $this->staking->applyBonusDefaultToPackages();
        return $this->_json(['status' => $ok ? 'success' : 'error', 'message' => $msg], $ok ? 200 : 422);
    }
}
