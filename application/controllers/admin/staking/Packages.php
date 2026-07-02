<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin ▸ Staking ▸ Packages
 * CRUD for fixed stake amounts (5,000 … 500,000 BMAN) incl. bonus %,
 * group-incentive ceiling, sort order and enable/disable.
 * Delete is blocked while stakes exist (soft disable instead) — doc §9.
 */
class Packages extends CI_Controller
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
            // Staking pages accept their own key OR the legacy package-settings key.
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

    /* ------------------------------- page ------------------------------- */
    public function index()
    {
        $data['title']      = 'Staking Packages';
        $data['card_tilte'] = 'Staking Packages';
        $data['packages']   = $this->staking->packages();
        $this->load->view('admin/staking/packages', $data);
    }

    /* ---------------------------- AJAX: save ---------------------------- */
    public function save()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $id = (int)$this->input->post('id');
        list($ok, $res) = $this->staking->savePackage([
            'name'          => $this->input->post('name', true),
            'stake_amount'  => $this->input->post('stake_amount', true),
            'bonus_percent' => $this->input->post('bonus_percent', true),
            'group_ceiling' => $this->input->post('group_ceiling', true),
            'sort_order'    => $this->input->post('sort_order', true),
        ], $id);
        if (!$ok) return $this->_json(['status' => 'error', 'message' => $res], 422);
        return $this->_json(['status' => 'success', 'message' => $id ? 'Package updated.' : 'Package added.', 'id' => $res]);
    }

    /* ------------------------- AJAX: enable/disable --------------------- */
    public function toggle($id)
    {
        if (!$this->input->is_ajax_request()) show_404();
        $active = (int)$this->input->post('active');
        $this->staking->togglePackage((int)$id, $active);
        return $this->_json(['status' => 'success', 'message' => $active ? 'Package enabled.' : 'Package disabled.']);
    }

    /* ---------------------------- AJAX: delete -------------------------- */
    public function delete($id)
    {
        if (!$this->input->is_ajax_request()) show_404();
        list($ok, $msg) = $this->staking->deletePackage((int)$id);
        return $this->_json(['status' => $ok ? 'success' : 'error', 'message' => $msg], $ok ? 200 : 422);
    }

    /* --------------------------- AJAX: reorder -------------------------- */
    public function reorder()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $ids = $this->input->post('ids');
        if (!is_array($ids) || !$ids) return $this->_json(['status' => 'error', 'message' => 'Nothing to reorder.'], 422);
        $this->staking->reorderPackages($ids);
        return $this->_json(['status' => 'success', 'message' => 'Order saved.']);
    }
}
