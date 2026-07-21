<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin ▸ Audit Log
 * Unified read-only view over every settings-change audit trail in the app:
 * Staking Plans, ROI Structure, Bonus & Matching Settings, Withdraw Settings
 * (both USD + Token tabs), Coin Distribution, and Token Settings (which also
 * covers the Current Exchange Rate field).
 */
class AdminAuditLog extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper(['url', 'security']);
        $this->load->model('Admin_model');

        if (!$this->session->userdata('admin_logged_in')) {
            redirect('admin/login');
        }
        $user = $this->Admin_model->get_user($this->session->userdata('admin_userid'));
        if ($user && $user->admin_roll == '1') {
            $permissions = json_decode($user->permission_pages, true);
            $allowed = ['staking_management', 'commission_settings', 'package_settings', 'mail_settings'];
            $hasAccess = false;
            foreach ($allowed as $perm) {
                if (!empty($permissions[$perm])) { $hasAccess = true; break; }
            }
            if (!$hasAccess) {
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
        $data['title'] = 'Admin Audit Log';
        $this->load->view('admin/audit_log', $data);
    }

    /* --------------------------- AJAX: combined log ----------------------- */
    public function log()
    {
        if (!$this->input->is_ajax_request()) show_404();

        $this->load->model('Staking_model', 'staking');
        $this->load->model('Coindistribution_model', 'coindist');
        $this->load->model('Tokenmaster_model', 'tokenmaster');

        $limit = 150;
        $rows = [];

        foreach ($this->staking->stakingPlansAuditLog($limit) as $r) {
            $rows[] = $this->_row('staking_plans', 'Staking Plans', $r['field_name'], null,
                                   $r['old_value'], $r['new_value'], $r['admin_name'], $r['changed_by'], $r['created_at']);
        }
        foreach ($this->staking->bonusAuditLog($limit) as $r) {
            $rows[] = $this->_row('bonus_settings', 'Bonus & Matching Settings', $r['field_name'], null,
                                   $r['old_value'], $r['new_value'], $r['admin_name'], $r['changed_by'], $r['created_at']);
        }
        foreach ($this->staking->roiAudit($limit) as $r) {
            $field = ($r['package_name'] ?: ('Package #'.$r['package_id']))
                   . ' — ' . ucfirst($r['plan_code']) . ' ' . $r['duration_years'] . 'y';
            $rows[] = $this->_row('roi_structure', 'ROI Structure', $field, null,
                                   $r['old_percent'], $r['new_percent'], $r['admin_name'], $r['changed_by'], $r['created_at']);
        }
        foreach ($this->coindist->auditLog($limit) as $r) {
            $field = $r['option_name'] ?: ('#'.$r['option_id']);
            $rows[] = $this->_row('coin_distribution', 'Coin Distribution', $field, $r['action'],
                                   $r['old_value'], $r['new_value'], $r['admin_name'], $r['changed_by'], $r['created_at']);
        }
        foreach ($this->tokenmaster->auditLog($limit) as $r) {
            $field = 'Setting #' . $r['setting_id'];
            $rows[] = $this->_row('token_settings', 'Token Settings / Exchange Rate', $field, $r['action'],
                                   $r['old_value'], $r['new_value'], $r['admin_name'], $r['changed_by'], $r['created_at']);
        }

        $withdrawRows = $this->db->select('a.*, adm.admin_name')
                                  ->from('admin_settings_audit a')
                                  ->join('admin_members adm', 'adm.id = a.changed_by', 'left')
                                  ->where_in('a.module', ['withdraw_settings', 'token_withdraw_settings'])
                                  ->order_by('a.created_at', 'DESC')->limit($limit)
                                  ->get()->result_array();
        foreach ($withdrawRows as $r) {
            $label = $r['module'] === 'withdraw_settings' ? 'Withdraw Settings' : 'Token Withdraw Settings';
            $rows[] = $this->_row($r['module'], $label, $r['field_name'], null,
                                   $r['old_value'], $r['new_value'], $r['admin_name'], $r['changed_by'], $r['created_at']);
        }

        usort($rows, function ($a, $b) { return strcmp($b['created_at'], $a['created_at']); });
        $rows = array_slice($rows, 0, 300);

        return $this->_json(['status' => 'success', 'rows' => $rows]);
    }

    private function _row($source, $label, $field, $action, $old, $new, $admin_name, $changed_by, $created_at)
    {
        return [
            'source'      => $source,
            'label'       => $label,
            'field'       => $field,
            'action'      => $action,
            'old_value'   => $old,
            'new_value'   => $new,
            'admin_name'  => $admin_name,
            'changed_by'  => $changed_by,
            'created_at'  => $created_at,
        ];
    }

}
