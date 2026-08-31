<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin ▸ Staking ▸ Plans
 * One card per plan (Fixed / Regular / Combo): withdrawal rules, monthly
 * credit days, combo 50/50 split, offered durations (2/3/5y), enable/disable.
 * Doc §3.2 / §6 screen 2.
 */
class Plans extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper(['url','security']);
        $this->load->model('Admin_model');
        $this->load->model('Staking_model', 'staking');

        if (!$this->session->userdata('admin_logged_in')) {
            redirect('aaddmmiinn/login');
        }
        $user = $this->Admin_model->get_user($this->session->userdata('admin_userid'));
        if ($user && $user->admin_roll == '1') {
            $permissions = json_decode($user->permission_pages, true);
            if (empty($permissions['staking_management']) && empty($permissions['package_settings'])) {
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
        // Fixed / Regular / Combo are the only plans. There is no separate
        // Special Offer plan any more — "special" is just a badge on a package,
        // and special packages price off the normal ROI matrix like the rest.
        $data['title']      = 'Staking Plans';
        $data['card_tilte'] = 'Staking Plans';
        $data['plans']      = $this->staking->plans();
        $this->load->view('admin/staking/plans', $data);
    }

    /* --------------------- AJAX: save one plan's rules ------------------ */
    public function save($id)
    {
        if (!$this->input->is_ajax_request()) show_404();

        $fields = [
            'credit_days','withdraw_after_maturity','return_principal','withdraw_frequency_days',
            'min_withdraw_bman','max_withdraw_bman','min_withdraw_usdt','max_withdraw_usdt',
            'combo_fixed_pct','combo_regular_pct',
        ];
        $data = [];
        foreach ($fields as $f) {
            $v = $this->input->post($f, true);
            if ($v !== null) $data[$f] = $v;
        }
        list($ok, $msg) = $this->staking->savePlan((int)$id, $data, $this->_adminId());
        if (!$ok) return $this->_json(['status' => 'error', 'message' => $msg], 422);

        // durations arrive as years[] = [2,3,5]
        $years = $this->input->post('years');
        if ($years !== null) {
            list($ok2, $msg2) = $this->staking->savePlanTerms((int)$id, is_array($years) ? $years : [], $this->_adminId());
            if (!$ok2) return $this->_json(['status' => 'error', 'message' => $msg2], 422);
        }
        return $this->_json(['status' => 'success', 'message' => 'Plan updated.']);
    }

    /* ------------------------- AJAX: enable/disable --------------------- */
    public function toggle($id)
    {
        if (!$this->input->is_ajax_request()) show_404();
        $active = (int)$this->input->post('active');
        $this->staking->togglePlan((int)$id, $active, $this->_adminId());
        return $this->_json(['status' => 'success', 'message' => $active ? 'Plan enabled.' : 'Plan disabled.']);
    }

    /* --------------------------- AJAX: audit log ------------------------- */
    public function audit()
    {
        if (!$this->input->is_ajax_request()) show_404();
        return $this->_json(['status' => 'success', 'rows' => $this->staking->stakingPlansAuditLog()]);
    }
}
