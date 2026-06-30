<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class  Mailsettings extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');
        $this->load->model('Admin_model');

        if (!$this->session->userdata('logged_in')) {
            redirect('admin/login');
        }

        $user = $this->Admin_model->get_user($this->session->userdata('userid'));

        if ($user->admin_roll == '1') {
            $permissions = json_decode($user->permission_pages, true);
            if (empty($permissions['mail_settings'])) {
                $this->session->set_flashdata('error', 'Access Denied: You do not have permission.');
                redirect('admin');
            }
        }

    }
    /*
    |--------------------------------------------------------------------------
    | Mail List View
    |--------------------------------------------------------------------------
    */
    public function index() {
        $this->data['title'] = 'Mail Settings';
        $this->data['card_title'] = 'Edit SMTP Mail Settings';
        $this->data['emailinfo'] = $this->db->query("SELECT * FROM email_config where id = '1'")->row();
        $this->load->view('admin/settings/mail-edit-settings', $this->data);
    }


    public function update(){
        $this->form_validation->set_rules('host', 'Host Name', 'required|trim');
        $this->form_validation->set_rules('username', 'User Name', 'required|trim');
        $this->form_validation->set_rules('password', 'Password', 'required|trim');
        $this->form_validation->set_rules('secure', 'Secure Type', 'required|trim');
        $this->form_validation->set_rules('port', 'Port', 'required|integer');
        $this->form_validation->set_rules('sendfrom', 'Mail Send From', 'required|valid_email');
        $this->form_validation->set_rules('sendname', 'Mail Sender Name', 'required|trim');

        if ($this->form_validation->run() == FALSE) {
            $errors = $this->form_validation->error_array();
            echo json_encode(['status' => false, 'errors' => $errors]);
            exit;
        } else {
            $data = [
                'host' => $this->input->post('host'),
                'username' => $this->input->post('username'),
                'password' => $this->input->post('password'),
                'smtpsecure' => $this->input->post('secure'),
                'port' => $this->input->post('port'),
                'from_mail' => $this->input->post('sendfrom'),
                'from_name' => $this->input->post('sendname'),
                'smtp_status' => $this->input->post('smtp_status') ? "1" : "0",
                'updated_name' => date('Y-m-d H:i:s')
            ];

            $update =  $this->db->update('email_config', $data, ['id' => 1]);

            if ($update) {
                $response = array(
                    'status' => true,
                    'message' => "SMTP Settings update Successfully"
                );
            } else {
                $response = array(
                    'status' => false,
                    'message' => "SMTP Settings update faild"
                );
            }

            echo json_encode($response);
        }
    }

}