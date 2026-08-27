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

    /** Same idea, member session — mirrors Login.php::_complete_login()'s exact keys. */
    public function user($userId = 2)
    {
        $this->load->library('session');
        $this->load->database();
        $u = $this->db->get_where('users', ['id' => (int) $userId])->row();
        if (!$u) { echo 'user not found'; return; }

        $this->session->set_userdata([
            'user_logged_in' => true,
            'user_full_name' => $u->username,
            'user_userid'    => $u->id,
            'user_email'     => $u->email,
            'user_login'     => true,
        ]);

        redirect('user/transfer_wallet');
    }
}
