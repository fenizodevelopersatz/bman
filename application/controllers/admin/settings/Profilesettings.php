<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Profilesettings extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');
        $this->load->model('Admin_model');

        if (!$this->session->userdata('admin_logged_in')) {
            redirect('admin/login');
        }

        $user = $this->Admin_model->get_user($this->session->userdata('admin_userid'));

        if ($user->admin_roll == '1') {
            $permissions = json_decode($user->permission_pages, true);
            if (empty($permissions['commission_settings'])) {
                $this->session->set_flashdata('error', 'Access Denied: You do not have permission.');
                redirect('admin');
            }
        }

        $this->load->model('settings/Commission_model');
    }
    /*
    |--------------------------------------------------------------------------
    | Commission Settings
    |--------------------------------------------------------------------------
    */
    public function index() {
     $verify_session = $this->session->userdata('verify_payment_page');
   // $verify_session = "ok";
    if($verify_session =="ok"){    
    $this->data['title'] = 'Profile Settings';
    $this->data['card_title'] = 'Profile Settings';
    $this->data['verify_type'] = '1';
    $this->data['admininfo'] = $this->db->query("SELECT * FROM admin_members")->row();
    $this->load->view('admin/settings/profile-edit-settings', $this->data);
    }
    else{
        $send_otp = $this->session->userdata('sender_otp');
                
        if($send_otp == ""){
        $this->sender_otp();
        }

        $admin_id = $this->session->userdata('admin_userid');
        $this->data['verify_type'] = '0';
        $this->data['title'] = 'Profile Edit Verify Page';
        $this->data['admin_mail'] = $this->db->query("SELECT * FROM `admin_members` WHERE id = '".$admin_id."' ")->row()->admin_email;
        $this->load->view('admin/settings/profile-edit-settings',$this->data);
    }
}

public function sender_otp(){

    $random_number = sprintf("%06d", random_string('numeric', 6));

    if($random_number){
        $userid = $this->session->userdata('admin_userid');
       
        $this->load->model('member/Mlm_model');
        $useremail = $this->db->query("SELECT * FROM `admin_members` where id = '".$userid."' ")->row()->admin_email;
		$mailid = "7";
		$mail_subject_data = $this->db->query("SELECT * FROM email_template where id = '".$mailid."' ")->row();
		$createddate = date('Y-m-d H:i:s');
		$subject = $mail_subject_data->subject;
		$message  = str_replace('[temp_otp]', $random_number, $mail_subject_data->temp_content);
		$this->Mlm_model->sendmail($useremail, $subject, $message);


        email_log($random_number,$useremail,'email_verify');
        $this->session->set_userdata('sender_otp',$random_number);
        return true;
    
    }

}

public function finelVerify() {
    // Check if request is POST
    if ($this->input->server('REQUEST_METHOD') === 'POST') {

        // Get raw POST data
        $postData = $this->input->post();
        
        if (!empty($postData)) {
         
            $admin_id = $this->session->userdata('admin_userid');
            $emailOTP = $this->input->post('emailOTP');
            $twofaOTP = $this->input->post('twofaOTP');

            $verify_1 = $this->twofachecker($admin_id,$twofaOTP);
            $verify_2 = emailVerify($admin_id,'email_verify',$emailOTP);

            if($verify_1 && $verify_2){

                $this->session->set_userdata('verify_payment_page',"ok");
                $this->session->set_userdata('sender_otp',"");
                $response = array(
                    'status' => true,
                    'message' => "Verify Successfully"
                );
            } else {

                $this->session->set_flashdata('danger', 'Invalide OTP !');
                $response = array(
                    'status' => false,
                    'message' => "Invalid OTP!"
                );
            }
            

        } else {
            $response = array(
                'status' => false,
                'message' => "Invalid OTP!"
            );
        }

        echo json_encode($response);
    } else {
        // Handle invalid request
        $response = array(
            'status' => false,
            'message' => "Invalid request method!"
        );
        echo json_encode($response);
    }
}

    /*
    |--------------------------------------------------------------------------
    | Update Commission Settings
    |--------------------------------------------------------------------------
    */
    public function update(){

        $this->form_validation->set_rules('admin_name', 'Name', 'required|trim');
        $this->form_validation->set_rules('admin_email', 'Email', 'required|trim');
        
        if ($this->form_validation->run() == FALSE) {

            $this->session->set_flashdata('error', validation_errors());
            redirect('profile-settings');

            

        } else {

            $admin_name = $this->input->post('admin_name');
            $admin_email = $this->input->post('admin_email');

            $data = [
                'admin_name' => $admin_name ,
                'admin_email' => $admin_email,
                'update_date' => date('Y-m-d H:i:s')
            ];

            $update =  $this->db->update('admin_members', $data, ['id' => 1]);

            if ($update) {
                $response = array(
                    'status' => true,
                    'message' => "Profile update Successfully"
                );
            } else {
                $response = array(
                    'status' => false,
                    'message' => "Profile update faild"
                );
            }

            echo json_encode($response);
        }

    }
}