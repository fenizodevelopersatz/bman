<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin ▸ Staking ▸ ROI Structure
 * Inline-editable matrix (rows = packages, cols = Fixed 2/3/5Y + Regular
 * 2/3/5Y). Every save is versioned (effective-dated row) and audited.
 * Editing is Super-Admin only per the proposal business rules; permission-
 * restricted sub-admins additionally need the `staking_roi_edit` key.
 * Doc §6 screen 3, §7.2 flow.
 */
class Roistructure extends CI_Controller
{
    /** true when the logged-in admin may write ROI cells */
    private $can_edit = true;

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
            if (empty($permissions['staking_management']) && empty($permissions['package_settings'])) {
                $this->session->set_flashdata('error', 'Access Denied: You do not have permission.');
                redirect('admin');
            }
        }
        // ROI edits are Super-Admin only (proposal). In this app admin_roll
        // '1' is the Super Admin (see admin_members seed); sub-admins get the
        // grid read-only.
        $this->can_edit = ($user && $user->admin_roll == '1');
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
        $data['title']      = 'ROI Structure';
        $data['card_tilte'] = 'ROI Structure (Package × Plan × Duration)';
        $data['grid']       = $this->staking->roiGrid();
        $data['can_edit']   = $this->can_edit;
        $this->load->view('admin/staking/roi_structure', $data);
    }

    /* --------------- AJAX: bulk save of edited matrix cells ------------- *
     * POST: effective_from=YYYY-MM-DD, note=?, cells=JSON
     *       [{package_id, plan_code, duration_years, percent}, …]         */
    public function save()
    {
        if (!$this->input->is_ajax_request()) show_404();
        if (!$this->can_edit) {
            return $this->_json(['status' => 'error', 'message' => 'ROI edits are Super-Admin only.'], 403);
        }

        $cells = json_decode((string)$this->input->post('cells'), true);
        if (!is_array($cells) || !$cells) {
            return $this->_json(['status' => 'error', 'message' => 'No changes to save.'], 422);
        }
        $eff  = $this->input->post('effective_from', true);
        if ($eff && strtotime($eff) === false) {
            return $this->_json(['status' => 'error', 'message' => 'Invalid effective-from date.'], 422);
        }
        $note = $this->input->post('note', true);
        $admin_id = $this->_adminId();

        $saved = 0; $errors = [];
        foreach ($cells as $c) {
            list($ok, $msg) = $this->staking->saveRoiCell(
                isset($c['package_id']) ? $c['package_id'] : 0,
                isset($c['plan_code']) ? $c['plan_code'] : '',
                isset($c['duration_years']) ? $c['duration_years'] : 0,
                isset($c['percent']) ? $c['percent'] : -1,
                $eff, $admin_id, $note
            );
            if ($ok) { if ($msg === 'saved') $saved++; }
            else $errors[] = $msg;
        }
        if ($errors) {
            return $this->_json(['status' => 'error',
                'message' => 'Saved '.$saved.' cell(s); errors: '.implode(' ', array_unique($errors))], 422);
        }
        return $this->_json(['status' => 'success',
            'message' => $saved ? $saved.' cell(s) saved (versioned + audited).' : 'No values changed.']);
    }

    /* ------------------- AJAX: version history for a cell --------------- */
    public function history()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $rows = $this->staking->roiHistory(
            (int)$this->input->get('package_id'),
            $this->input->get('plan_code', true) ?: '',
            (int)$this->input->get('duration_years')
        );
        return $this->_json(['status' => 'success', 'rows' => $rows]);
    }

    /* --------------------------- AJAX: audit log ------------------------ */
    public function audit()
    {
        if (!$this->input->is_ajax_request()) show_404();
        return $this->_json(['status' => 'success', 'rows' => $this->staking->roiAudit()]);
    }
}
