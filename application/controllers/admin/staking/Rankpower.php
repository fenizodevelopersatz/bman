<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin ▸ Staking ▸ Rank Power & Group Incentive
 * Proposal §11 (Rank Power System): separate from Achievement Rank, resets
 * every 60 days, controls group-incentive qualification — admin configures
 * the rules and manages the reset cycle here.
 * Proposal §12 (Group Incentive Ceiling): stake → ceiling editor (values are
 * stored on staking_packages.group_ceiling).
 */
class Rankpower extends CI_Controller
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
            if (empty($permissions['staking_management']) && empty($permissions['rank_management'])) {
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
        $data['title']      = 'Rank Power & Group Incentive';
        $data['card_tilte'] = 'Rank Power System & Group Incentive Ceiling';
        $data['settings']   = $this->staking->powerSettings();
        $data['cycle']      = $this->staking->currentPowerCycle();
        $data['cycles']     = $this->staking->powerCycles();
        $data['packages']   = $this->staking->packages();
        $data['ranks']      = $this->staking->ranks();
        $this->load->view('admin/staking/rank_power', $data);
    }

    /* ---------------------- AJAX: save §11 settings --------------------- */
    public function save_settings()
    {
        if (!$this->input->is_ajax_request()) show_404();
        list($ok, $msg) = $this->staking->savePowerSettings([
            'is_enabled'               => (int)$this->input->post('is_enabled'),
            'cycle_days'               => $this->input->post('cycle_days', true),
            'controls_group_incentive' => (int)$this->input->post('controls_group_incentive'),
            'min_power_tier'           => $this->input->post('min_power_tier', true),
            'auto_open_next_cycle'     => (int)$this->input->post('auto_open_next_cycle'),
        ], $this->_adminId());
        return $this->_json(['status' => $ok ? 'success' : 'error', 'message' => $msg], $ok ? 200 : 422);
    }

    /* ------------- AJAX: reset — close open cycle, start next ----------- */
    public function reset_cycle()
    {
        if (!$this->input->is_ajax_request()) show_404();
        list($ok, $msg) = $this->staking->resetPowerCycle($this->_adminId());
        return $this->_json(['status' => $ok ? 'success' : 'error', 'message' => $msg], $ok ? 200 : 422);
    }

    /* --------------- AJAX: save §12 ceilings (bulk update) -------------- *
     * POST: ceilings=JSON {package_id: ceiling, …}                         */
    public function save_ceilings()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $rows = json_decode((string)$this->input->post('ceilings'), true);
        if (!is_array($rows) || !$rows) {
            return $this->_json(['status' => 'error', 'message' => 'No ceilings submitted.'], 422);
        }
        list($ok, $msg) = $this->staking->saveCeilings($rows);
        return $this->_json(['status' => $ok ? 'success' : 'error', 'message' => $msg], $ok ? 200 : 422);
    }
}
