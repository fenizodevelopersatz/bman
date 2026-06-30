<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Membertheme  (Settings -> Member Panel Theme)
 * -------------------------------------------------------------------
 * Independent theme engine for the authenticated member dashboard.
 * Stored in site_settings (settings_type = 'member_theme'). Never touches
 * the Landing Page theme (landing_settings).
 */
class Membertheme extends CI_Controller
{
    /** whitelist of editable keys */
    private $keys = array(
        'mode',                 // light | dark | auto
        'user_switch',          // 1|0 — show the sun/moon toggle for members
        'primary','secondary','accent',
        'highlight_primary','highlight_accent','hover_highlight','active_highlight',
        'gradient_start','gradient_end',
        'success','warning','danger','info',
    );

    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');
        $this->load->model('Admin_model');

        if (!$this->session->userdata('logged_in')) {
            redirect('admin/login');
        }
        $user = $this->Admin_model->get_user($this->session->userdata('userid'));
        if ($user && $user->admin_roll == '1') {
            $permissions = json_decode($user->permission_pages, true);
            // allow its own key OR the general site_settings permission
            if (empty($permissions['member_theme']) && empty($permissions['site_settings'])) {
                $this->session->set_flashdata('error', 'Access Denied: You do not have permission.');
                redirect('admin');
            }
        }
    }

    public function index()
    {
        foreach ($this->keys as $k) {
            $this->data['mt_' . $k] = site_settings('member_theme', $k);
        }
        $this->data['title'] = 'Member Panel Theme';
        $this->load->view('admin/settings/member-theme', $this->data);
    }

    public function update()
    {
        $mode = $this->input->post('mode', true);
        if (!in_array($mode, array('light', 'dark', 'auto'))) { $mode = 'light'; }
        $this->set('mode', $mode);

        // member sun/moon toggle on/off
        $this->set('user_switch', $this->input->post('user_switch') ? '1' : '0');

        foreach ($this->keys as $k) {
            if ($k === 'mode' || $k === 'user_switch') continue;
            $val = $this->input->post($k, true);
            if ($val === null || $val === '') continue;
            // basic colour validation (hex or rgb/rgba)
            if (!preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$|^rg\(.+\)$|^rgba?\(.+\)$/', $val)) {
                continue; // skip invalid values, keep existing
            }
            $this->set($k, $val);
        }

        echo json_encode(array('status' => true, 'message' => 'Member panel theme saved'));
        exit;
    }

    public function reset_default()
    {
        $defaults = array(
            'mode' => 'light', 'primary' => '#6D4AFF', 'secondary' => '#FFC94A',
            'accent' => '#A855F7', 'highlight_primary' => '#6D4AFF', 'highlight_accent' => '#A855F7',
            'hover_highlight' => '#5a3df0', 'active_highlight' => '#6D4AFF',
            'gradient_start' => '#6D4AFF', 'gradient_end' => '#A855F7',
            'success' => '#1BC5BD', 'warning' => '#FFA800', 'danger' => '#F64E60', 'info' => '#8950FC',
        );
        foreach ($defaults as $k => $v) { $this->set($k, $v); }
        echo json_encode(array('status' => true, 'message' => 'Reset to default'));
        exit;
    }

    /** upsert one member_theme key */
    private function set($key, $value)
    {
        $exists = $this->db->get_where('site_settings',
            array('settings_type' => 'member_theme', 'settings_name' => $key))->row();
        if ($exists) {
            $this->db->where('settings_type', 'member_theme')->where('settings_name', $key)
                     ->update('site_settings', array('settings_value' => $value));
        } else {
            $this->db->insert('site_settings', array(
                'settings_type' => 'member_theme', 'settings_name' => $key, 'settings_value' => $value));
        }
    }
}
