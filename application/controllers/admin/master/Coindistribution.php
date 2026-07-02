<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin ▸ Master ▸ Coin Distribution (proposal §3A)
 * Wallet-allocation options for BMAN purchases.
 *
 * Role rules (proposal): Super Admin may add / edit percentages / delete /
 * set default; regular admins may view and enable/disable only. In this app
 * admin_roll == '1' is the Super Admin (see admin_members seed); its
 * permission_pages JSON additionally scopes which pages it can open.
 */
class Coindistribution extends CI_Controller
{
    /** true when the logged-in admin may add/edit/delete/set-default */
    private $is_super = false;

    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper(['url','security']);
        $this->load->model('Admin_model');
        $this->load->model('Coindistribution_model', 'dist');

        if (!$this->session->userdata('admin_logged_in')) {
            redirect('admin/login');
        }
        $user = $this->Admin_model->get_user($this->session->userdata('admin_userid'));
        if ($user && $user->admin_roll == '1') {
            $permissions = json_decode($user->permission_pages, true);
            // accept the module's own key OR the wallet-management key
            if (empty($permissions['coin_distribution_master']) && empty($permissions['wallet_management'])) {
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

    private function _adminId()
    {
        $id = (int)$this->session->userdata('admin_userid');
        if (!$id) $id = (int)$this->session->userdata('user_id');
        return $id;
    }

    private function _requireSuper()
    {
        if ($this->is_super) return true;
        $this->_json(['status' => 'error',
            'message' => 'Super Admin only — you may view and enable/disable options.'], 403);
        return false;
    }

    /* ------------------------------- page ------------------------------- */
    public function index()
    {
        $data['title']      = 'Coin Distribution';
        $data['card_tilte'] = 'Coin Distribution Options (Master)';
        $data['options']    = $this->dist->options();   // table re-filters via AJAX
        $data['is_super']   = $this->is_super;
        $this->load->view('admin/master/coin_distribution', $data);
    }

    /* ------------------- AJAX: filtered list (JSON rows) ----------------- */
    public function list()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $rows = $this->dist->options([
            'status'     => $this->input->get('status', true),
            'is_default' => $this->input->get('is_default', true),
            'q'          => trim((string)$this->input->get('q', true)),
        ]);
        return $this->_json(['status' => 'success', 'rows' => $rows]);
    }

    /* ------------------- AJAX: create / edit (Super Admin) --------------- */
    public function save()
    {
        if (!$this->input->is_ajax_request()) show_404();
        if (!$this->_requireSuper()) return;

        $id = (int)$this->input->post('id');
        list($ok, $res) = $this->dist->saveOption([
            'option_name'         => $this->input->post('option_name', true),
            'description'         => $this->input->post('description', true),
            'exchange_percentage' => $this->input->post('exchange_percentage', true),
            'earning_percentage'  => $this->input->post('earning_percentage', true),
            'staking_percentage'  => $this->input->post('staking_percentage', true),
            'bonus_percentage'    => $this->input->post('bonus_percentage', true),
            'status'              => (int)$this->input->post('status'),
            'is_default'          => (int)$this->input->post('is_default'),
        ], $this->_adminId(), $id);
        if (!$ok) return $this->_json(['status' => 'error', 'message' => $res], 422);
        return $this->_json(['status' => 'success',
            'message' => $id ? 'Option updated.' : 'Option added.', 'id' => $res]);
    }

    /* --------------- AJAX: enable/disable (any admin with access) -------- */
    public function toggle($id)
    {
        if (!$this->input->is_ajax_request()) show_404();
        $active = (int)$this->input->post('active');
        list($ok, $msg) = $this->dist->toggleOption((int)$id, $active, $this->_adminId());
        return $this->_json(['status' => $ok ? 'success' : 'error', 'message' => $msg], $ok ? 200 : 422);
    }

    /* --------------------- AJAX: set default (Super Admin) --------------- */
    public function set_default($id)
    {
        if (!$this->input->is_ajax_request()) show_404();
        if (!$this->_requireSuper()) return;
        list($ok, $msg) = $this->dist->setDefault((int)$id, $this->_adminId());
        return $this->_json(['status' => $ok ? 'success' : 'error', 'message' => $msg], $ok ? 200 : 422);
    }

    /* ----------------------- AJAX: delete (Super Admin) ------------------ */
    public function delete($id)
    {
        if (!$this->input->is_ajax_request()) show_404();
        if (!$this->_requireSuper()) return;
        list($ok, $msg) = $this->dist->deleteOption((int)$id, $this->_adminId());
        return $this->_json(['status' => $ok ? 'success' : 'error', 'message' => $msg], $ok ? 200 : 422);
    }

    /* --------------------------- AJAX: audit log ------------------------- */
    public function audit()
    {
        if (!$this->input->is_ajax_request()) show_404();
        return $this->_json(['status' => 'success', 'rows' => $this->dist->auditLog()]);
    }

    /* --------------------- CSV export (current filters) ------------------ */
    public function export_csv()
    {
        $rows = $this->dist->options([
            'status'     => $this->input->get('status', true),
            'is_default' => $this->input->get('is_default', true),
            'q'          => trim((string)$this->input->get('q', true)),
        ]);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=coin_distribution_options_'.date('Ymd_His').'.csv');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID','Option Name','Description','Exchange %','Earning %','Staking %','Bonus %','Total %','Default','Status','Created At']);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['id'],
                $r['option_name'],
                $r['description'],
                $r['exchange_percentage'],
                $r['earning_percentage'],
                $r['staking_percentage'],
                $r['bonus_percentage'],
                (float)$r['exchange_percentage'] + (float)$r['earning_percentage']
                    + (float)$r['staking_percentage'] + (float)$r['bonus_percentage'],
                $r['is_default'] ? 'Yes' : 'No',
                $r['status'] ? 'Active' : 'Disabled',
                $r['created_at'],
            ]);
        }
        fclose($out);
        exit;
    }
}
