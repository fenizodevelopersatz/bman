<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class  Newsletter extends CI_Controller {

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
            if (empty($permissions['newsletter_markettings'])) {
                $this->session->set_flashdata('error', 'Access Denied: You do not have permission.');
                redirect('admin');
            }
        }

        $this->load->model('settings/Payment_model');
    }

    /*
    |--------------------------------------------------------------------------
    | Mail Index
    |--------------------------------------------------------------------------
    */
       public function index(){

        $this->data['title'] = "News Letter";
        $this->data['card_title'] = "Send Your News Letter to members";
        $this->data['users'] = $this->db->query("SELECT * FROM users where status = '1' ")->result();
		$this->load->view('admin/newsletter/index',$this->data);

    }
    /*
    |--------------------------------------------------------------------------
    | News Letter Send
    |--------------------------------------------------------------------------
    */
    public function send()
    {
        $selected_members = $this->input->post('selected_members'); 
        $mail_subject = $this->input->post('mail_subject'); 
        $mail_content = $this->input->post('mail_content'); 
        

        if (empty($selected_members) || empty($mail_subject) || empty($mail_content)) {
            $response = array(
                'status' => false,
                'message' => "Please enter all inputbox"
            );
            echo json_encode($response);
            exit();
        }

        $emails = $this->getEmailsByIds($selected_members);

        if (empty($emails)) {
            $response = array(
                'status' => false,
                'message' => "No valid email address"
            );
            echo json_encode($response);
            exit();
        }

        $email_recipients = implode(',', $emails);

        $mail_config = $this->db->query("SELECT * FROM `email_config` where id = '1' ")->row();
    
        if($mail_config->smtp_status > 0){
    
        $host = $mail_config->host;
        $smtp_auth = $mail_config->smtp_auth;
        $username = $mail_config->username;
        $password = $mail_config->password;
        $smtpsecure = $mail_config->smtpsecure;
        $port = $mail_config->port;
        $from_name = $mail_config->from_name;
        $from_mail = $mail_config->from_mail;
    
        $mail = new PHPMailer(true);
    
        try {
    
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->SMTPAuth = $smtp_auth;
        $mail->Username = $username;
        $mail->Password = $password;
        $mail->SMTPSecure = $smtpsecure;
        $mail->Port = $port;
        $mail->setFrom($from_mail, $from_name);
        $mail->addAddress($email_recipients);
        $mail->isHTML(true);
        $mail->Subject = $mail_subject;
        $mail->Body    = $mail_content;
    
        $mail->send();
        return true;
        } catch (Exception $e) {
        return false;
        }
    
        } else {
    
        $admin_mail = $mail_config->php_mail;
        $headers = "From: $admin_mail\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=utf-8\r\n";
        mail($email_recipients, $mail_subject, $mail_content, $headers);
        return true;
    
        }
    


        if ($this->email->send()) {

            $response = array(
                'status' => true,
                'message' => "Newsletter sent successfully to all selected members"
            );
            echo json_encode($response);
            exit();

        } else {
            
            $response = array(
                'status' => false,
                'message' => "Newsletter send faild"
            );
            echo json_encode($response);
            exit();
        
        }

        redirect($_SERVER['HTTP_REFERER']);
    }


    /*
    |--------------------------------------------------------------------------
    | User Email Get
    |--------------------------------------------------------------------------
    */
    public function getEmailsByIds($ids){

        $this->db->select('email');
        $this->db->where_in('id', $ids);
        $query = $this->db->get('users'); 
        return array_column($query->result_array(), 'email');

    }

}