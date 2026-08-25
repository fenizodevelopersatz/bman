<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * TEMPORARY local-only dev harness — sets an authenticated admin session
 * without credentials, for browser-testing against `php -S` + localhost DB.
 * NEVER commit this file. Delete when done.
 */
class ZzTestLogin extends CI_Controller
{
    public function admin($adminId = 1)
    {
        $this->load->library('session');
        $this->load->model('Admin_model');
        $admin = $this->Admin_model->get_user((int) $adminId);
        if (!$admin) { echo 'admin not found'; return; }

        $this->session->set_userdata([
            'admin_logged_in'  => true,
            'admin_full_name'  => $admin->admin_name,
            'admin_userid'     => $admin->id,
            'admin_email'      => $admin->admin_email,
            'admin_userlevel'  => $admin->admin_roll,
            'admin_login'      => true,
            'admin_logindate'  => date('Y-m-d H:i:s'),
            'admin_ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);

        redirect('admin/wallet/admin-wallet');
    }
}
