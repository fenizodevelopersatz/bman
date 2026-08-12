<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Login extends CI_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see https://codeigniter.com/user_guide/general/urls.html
	 */
	public function __construct() {
		parent::__construct();
		$this->load->helper('cookie');
		$this->load->helper('captcha');

        if($this->session->userdata('admin_logged_in') && $this->session->userdata('admin_login')) {
            redirect('admin');
         } 

		 $language = $this->session->userdata('site_lang') ?? 'english';
		 $this->config->set_item('language', $language);
	}

	public function switch_language($lang = "english") {
        $this->session->set_userdata('site_lang', $lang);
        redirect($_SERVER['HTTP_REFERER']); 
    }

	private function _isAjax() {
		return $this->input->is_ajax_request() || 
		       (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
		       (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
	}

	public function index()
	{
		if($this->input->post())
		{
			$this->form_validation->set_rules('username', 'Username', 'trim|required|callback_username_check');
			$this->form_validation->set_rules('password', 'Password', 'trim|required');
            $site_captcha_status = site_settings('captcha','status');
            if($site_captcha_status) {
                $this->form_validation->set_rules('g-recaptcha-response', 'captcha', 'trim|required|xss_clean|callback_captcha_check');
            }

			if ($this->form_validation->run() == FALSE) {
                $errors = $this->form_validation->error_array();
                if ($this->_isAjax()) {
                    echo json_encode(['status' => false, 'errors' => $errors]);
                    exit;
                }
                $this->data['verify_type'] = '1';
                $this->data['action'] = base_url()."aaddmmiinn/login";
                $this->load->view('admin/login', $this->data);
                return;
			} else {
                $username = $this->input->post('username');
                $password = $this->input->post('password');
				$remember = $this->input->post('remember');

				$result_get = $this->common_model->loginVerify($username,$password);
                $result_status = $result_get['status'];

				if ($result_status) {
					$twofaEnabled = (bool)site_settings('user_settings', 'admin_twofa_login');
					$emailOtpEnabled = (bool)site_settings('user_settings', 'admin_email_otp_login');

					if (!$twofaEnabled && !$emailOtpEnabled) {
						$this->_completeLogin($result_get['data']);
					} else {
						$this->session->set_userdata([
							'user_get_id' => (int)$result_get['data']->id,
							'pending_admin_verification' => true,
							'admin_twofa_required' => $twofaEnabled,
							'admin_email_otp_required' => $emailOtpEnabled,
						]);
						if ($emailOtpEnabled) {
							$this->sender_otp($result_get['data']->id);
						}
					}

                    if ($this->_isAjax()) {
                        echo json_encode(['status' => true, 'message' => "login successfuly"]);
                        exit;
                    }
                    if ($this->session->userdata('pending_admin_verification')) {
                        redirect('aaddmmiinn/login');
                    } else {
                        redirect('admin');
                    }
				} else {
                    if ($this->_isAjax()) {
                        echo json_encode(['status' => false, 'errors' => $result_get['message']]);
                        exit;
                    }
                    $errMsg = is_array($result_get['message']) ? implode(', ', $result_get['message']) : $result_get['message'];
                    $this->session->set_flashdata('error', $errMsg);
                    $this->data['verify_type'] = '1';
                    $this->data['action'] = base_url()."aaddmmiinn/login";
                    $this->load->view('admin/login', $this->data);
                    return;
				}
			}
		}
		else {
			
			  if($this->session->userdata('pending_admin_verification')){

				$this->auth_verify();

			  } else {

				$this->data['verify_type'] = '1';
                $this->data['action'] = base_url()."aaddmmiinn/login";
				$this->load->view('admin/login',$this->data);

			  }
					

		}
		
	}

	/*
    |--------------------------------------------------------------------------
    | VERIFY  OTP
    |--------------------------------------------------------------------------
    */
    public function verifyotp(){
        
        if($this->input->post()){

            $raw = file_get_contents("php://input");
            $data = json_decode($raw);

			
            if($data){
			
                $otp = $data->otp;
                $method = $data->method;

                $admin_id =  $this->session->userdata('user_get_id');

                if($otp){

                    if($method == "email_otp"){

                        $verify = emailVerify($admin_id,'email_verify',$otp);
                        
                    if($verify){

                        $return = array(
                            'status' => true,
                            'message' => "Verify Sucessfully"
                        );
                        
                    } else {

                        $return = array(
                            'status' => false,
                            'message' => "invalide Email OTP"
                        );

                    }

                    } else {

                        $verify = $this->twofachecker($admin_id,$otp);

                        
                    if($verify){

                        $return = array(
                            'status' => true,
                            'message' => "Verify Successfully"
                        );
                        
                    } else {

                        $return = array(
                            'status' => false,
                            'message' => "invalide Two-Factor OTP"
                        );

                    }

                    }


                } else {

                    $return = array(
                        'status' => false,
                        'message' => "invalide OTP"
                    );

                }

            } else {
                
                $return = array(
                    'status' => false,
                    'message' => "invalide OTP"
                );
            }


        } else {
        
            $return = array(
                'status' => false,
                'message' => "invalide request"
            );

        }

        echo json_encode($return);

    }

	public function finelVerify() {
		// Check if request is POST
		if ($this->input->server('REQUEST_METHOD') === 'POST') {
			// Get raw POST data
			$postData = $this->input->post();
			
			if (!empty($postData)) {
			
				$admin_id =  $this->session->userdata('user_get_id');
				$emailOTP = $this->input->post('emailOTP');
				$twofaOTP = $this->input->post('twofaOTP');
		
                $verify_1 = !$this->session->userdata('admin_twofa_required')
					|| $this->twofachecker($admin_id,$twofaOTP);
                $verify_2 = !$this->session->userdata('admin_email_otp_required')
					|| emailVerify($admin_id,'email_verify',$emailOTP);

				if($verify_1 && $verify_2){

					$this->session->set_userdata('verify_payment_page',"ok");

					$this->session->set_userdata('sender_otp',"");
					$response = array(
						'status' => true,
						'message' => "Verify Successfully"
					);

					$result = $this->db->get_where('admin_members', ['id' => (int)$admin_id])->row();
					$this->_completeLogin($result);

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
 


	public function auth_verify(){
			
		$admin_id = (int)$this->session->userdata('user_get_id');
		$this->data['verify_type'] = '0';
		$this->data['title'] = 'Verify Page';
		$this->data['admin_mail'] = $this->db->get_where('admin_members', ['id' => $admin_id])->row()->admin_email;
		$this->data['twofa_required'] = (bool)$this->session->userdata('admin_twofa_required');
		$this->data['email_otp_required'] = (bool)$this->session->userdata('admin_email_otp_required');
		$this->data['action'] = base_url()."aaddmmiinn/login/success";
		$this->load->view('admin/login',$this->data);
		
	}

	public function logout() {
		// Clear only the admin session keys so a logged-in user in another tab stays logged in.
		$this->session->unset_userdata(array(
			'admin_logged_in', 'admin_userid', 'admin_full_name',
			'admin_email', 'admin_userlevel', 'admin_login',
			'admin_logindate', 'admin_ip_address'
		));
		redirect('aaddmmiinn/login');
	}


	
public function sender_otp($userid){

    $random_number = sprintf("%06d", random_string('numeric', 6));
	

    if($random_number){

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
	    $this->session->set_userdata('user_get_id',$userid);
        return true;
    }

}

    public function username_check($str)
	{
		
        $query = $this->db->query("SELECT * FROM `admin_members` where 
            admin_email = '".$str."'  ");
		if ($query->num_rows()>0) 
			return true;
		else{
			$this->form_validation->set_message('username_check', 'This invalid details of %s');
			return false;
		}
		
	}

	public function captcha_check($str)
	{
		$this->load->library('recaptcha');
		$response = $this->recaptcha->verifyResponse($str);
		if (isset($response['success']) and $response['success'] === true) {
			return true;  
		}
		else
		{	
			$this->form_validation->set_message('captcha_check', ucwords($this->lang->line('errorcaptcha')));
			return false;
		}

	}
		


	private function twofachecker($admin_id,$oneCode){
		$this->load->library('Google_authendicator');
		$admin = $this->db->get_where('admin_members', ['id' => (int)$admin_id])->row();
		if (!$admin || empty($admin->auth_key)) return false;
		$ga = new Google_authendicator();
		return (bool)$ga->verifyCode($admin->auth_key, $oneCode, 2);
	}

	private function _completeLogin($result)
	{
		if (!$result) return false;
		$this->session->set_userdata([
			'admin_logged_in' => true,
			'admin_full_name' => $result->admin_name,
			'admin_userid' => $result->id,
			'admin_email' => $result->admin_email,
			'admin_userlevel' => $result->admin_roll,
			'admin_login' => true,
			'admin_logindate' => date('Y-m-d H:i:s'),
			'admin_ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
			'remember_me' => true,
		]);
		$this->session->unset_userdata([
			'pending_admin_verification', 'admin_twofa_required',
			'admin_email_otp_required', 'sender_otp', 'user_get_id'
		]);
		setcookie('remember_me', md5($result->id.$result->admin_name), time() + 120, '/');
		return true;
	}
		
	
}
