<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin ▸ Staking ▸ Special ROI Structure
 * Year-wise ROI (Monthly ROI % + Maturity %, years 1..7) for Special Offer
 * packages. Same access model as the normal ROI Structure page; every edit is
 * audited via Specialroi_model.
 */
class Specialroi extends CI_Controller
{
    private $can_edit = true;

    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper(['url', 'security']);
        $this->load->model('Admin_model');
        $this->load->model('staking/Specialroi_model', 'specialroi');

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
        $id = (int) $this->session->userdata('admin_userid');
        if (!$id) $id = (int) $this->session->userdata('user_id');
        return $id;
    }

    public function index()
    {
        $packages = $this->specialroi->specialPackages();
        $grid = [];
        foreach ($packages as $p) {
            $grid[] = ['package' => $p, 'years' => $this->specialroi->forPackage((int) $p['id'])];
        }

        $data['title']      = 'Special ROI Structure';
        $data['card_tilte'] = 'Special Offer ROI — year-wise Monthly ROI % + Maturity %';
        $data['grid']       = $grid;
        $data['max_years']  = Specialroi_model::MAX_YEARS;
        $data['can_edit']   = $this->can_edit;
        $data['audit']      = $this->specialroi->auditLog(50);
        $this->load->view('admin/staking/special_roi', $data);
    }

    /** AJAX: save one year row for a package. */
    public function save()
    {
        if (!$this->input->is_ajax_request()) show_404();
        if (!$this->can_edit) {
            return $this->_json(['status' => 'error', 'message' => 'Special ROI edits are Super-Admin only.'], 403);
        }

        $packageId = (int) $this->input->post('package_id');
        $year      = (int) $this->input->post('year_number');
        if (!$packageId || !$year) {
            return $this->_json(['status' => 'error', 'message' => 'Missing package or year.'], 422);
        }
        // Guard: only special packages may have special ROI.
        if (!in_array($packageId, $this->specialroi->specialPackageIds(), true)) {
            return $this->_json(['status' => 'error', 'message' => 'That package is not a Special Offer package.'], 422);
        }

        list($ok, $msg) = $this->specialroi->saveYear($packageId, $year, [
            'monthly_roi_percent' => $this->input->post('monthly_roi_percent', true),
            'maturity_percent'    => $this->input->post('maturity_percent', true),
            'is_active'           => $this->input->post('is_active', true) ? 1 : 0,
        ], $this->_adminId());

        if (!$ok) return $this->_json(['status' => 'error', 'message' => $msg], 422);
        return $this->_json(['status' => 'success', 'message' => 'Year ' . $year . ' saved.']);
    }
}
