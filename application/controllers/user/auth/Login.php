<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Login extends CI_Controller
{

	public function __construct()
	{
		parent::__construct();
		$this->load->helper('cookie');
		$this->load->helper('captcha');

		if ($this->session->userdata('logged_in') && $this->session->userdata('user_login')) {
			redirect('user/main');
		}

		$language = $this->session->userdata('site_lang') ?? 'english';
		$this->config->set_item('language', $language);
		$this->lang->load('common', $language);

	}


	public function index()
	{


		if ($this->input->post()) {

			$this->form_validation->set_rules('useremail', 'User Email', 'trim|required|callback_username_check');
			$this->form_validation->set_rules('password', 'Password', 'trim|required');
			$site_captcha_status = site_settings('captcha', 'status');
			if ($site_captcha_status) {
				$this->form_validation->set_rules('g-recaptcha-response', 'captcha', 'trim|required|xss_clean|callback_captcha_check');
			}


			if ($this->form_validation->run() == FALSE) {

				$errors = $this->form_validation->error_array();
				echo json_encode(['status' => false, 'errors' => $errors]);
				exit;

			} else {

				$username = $this->input->post('useremail');
				$password = $this->input->post('password');
				$remember = $this->input->post('remember');

				$result_get = $this->common_model->userloginVerify($username, $password);
				$result_status = $result_get['status'];

				if ($result_status) {

					$this->sender_otp($result_get['data']->id);

					$userarray = array(
						"user_get_id" => $result_get['data']->id,
					);
					$this->session->set_userdata($userarray);

					$response = [
						'status' => true,
						'message' => 'login successfuly',
					];

					$this->output
						->set_status_header(200)
						->set_content_type('application/json')
						->set_output(json_encode($response));
					exit;

				} else {

					echo json_encode(['status' => false, 'errors' => $result_get['message']]);
					exit;
				}
			}
		} else {

			$send_otp = $this->session->userdata('sender_otp');

			if ($send_otp) {

				$this->auth_verify();

			} else {

				$this->data['verify_type'] = '1';
				$this->data['action'] = base_url() . "user/in";
				$this->load->view('user/auth/login', $this->data);

			}


		}

	}
	/*
	|--------------------------------------------------------------------------
	| VERIFY  Forgot Password
	|--------------------------------------------------------------------------
	*/
	public function forgot()
	{
		$this->data['action'] = base_url() . "user/forgot";
		$this->load->view('user/auth/forgot', $this->data);
	}
	/*
	|--------------------------------------------------------------------------
	| VERIFY  OTP
	|--------------------------------------------------------------------------
	*/
	public function verifyotp()
	{

		if ($this->input->post()) {

			$raw = file_get_contents("php://input");
			$data = json_decode($raw);


			if ($data) {

				$otp = $data->otp;
				$method = $data->method;

				$admin_id = $this->session->userdata('user_get_id');

				if ($otp) {

					if ($method == "email_otp") {

						$verify = true;
						//emailVerify($admin_id,'email_verify',$otp);

						if ($verify) {

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

						$verify = $this->twofachecker($admin_id, $otp);


						if ($verify) {

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

	public function finelVerify()
	{
		if ($this->input->server('REQUEST_METHOD') === 'POST') {
			$postData = $this->input->post();

			if (!empty($postData)) {

				$admin_id = $this->session->userdata('user_get_id');
				$emailOTP = $this->input->post('emailOTP');
				$twofaOTP = $this->input->post('twofaOTP');

				$verify_1 = $this->twofachecker($admin_id, $twofaOTP);
				$verify_2 = true;

				if ($verify_1 && $verify_2) {

					$this->session->set_userdata('verify_payment_page', "ok");

					$this->session->set_userdata('sender_otp', "");
					$response = array(
						'status' => true,
						'message' => "Verify Successfully"
					);

					$result = $this->db->query("SELECT * FROM users where id = '" . $admin_id . "' ")->row();
					$array = array(
						"logged_in" => TRUE,
						"full_name" => $result->username,
						"userid" => $result->id,
						"Email" => $result->email,
						"admin_login" => FALSE,
						"user_login" => TRUE
					);

					$userarray = array(
						"userid" => $result->id,
						"logindate" => date('Y-m-d H:i:s'),
						"LoggedIn" => $result->id,
						"admin_user" => $result->id,
						"ip_address" => $_SERVER['REMOTE_ADDR']
					);


					$this->session->set_userdata('remember_me', true);

					$cookie = array(
						'name' => 'remember_me',
						'value' => '1212',
						'expire' => '1209600',
						'domain' => base_url(),
						'path' => base_url() . 'admin'
					);
					if ($result) {
						setcookie("remember_me", md5($result->id), time() + (60 * 2), '/');

						if (isset($_COOKIE['remember_me'])) {
							$array['cookiee'] = $_COOKIE['remember_me'];
							$condition = "cookiee=" . "'" . md5($result->id) . "'";
						}

						$this->session->set_userdata($array);
					}

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
			$response = array(
				'status' => false,
				'message' => "Invalid request method!"
			);
			echo json_encode($response);
		}
	}



	public function auth_verify()
	{

		$send_otp = $this->session->userdata('sender_otp');

		if ($send_otp == "") {
			$this->sender_otp();
		}

		$admin_id = '1';
		$this->data['verify_type'] = '0';
		$this->data['title'] = 'Verify Page';
		$this->data['action'] = base_url() . "user/auth/success";
		$this->load->view('user/auth/login', $this->data);

	}



	public function sender_otp($userid)
	{

		$random_number = sprintf("%06d", random_string('numeric', 6));


		if ($random_number) {

			$this->load->model('member/Mlm_model');
			$useremail = $this->db->query("SELECT * FROM `admin_members` where id = '" . $userid . "' ")->row()->admin_email;
			$mailid = "7";
			$mail_subject_data = $this->db->query("SELECT * FROM email_template where id = '" . $mailid . "' ")->row();
			$createddate = date('Y-m-d H:i:s');
			$subject = $mail_subject_data->subject;
			$message = str_replace('[temp_otp]', $random_number, $mail_subject_data->temp_content);
			$this->Mlm_model->sendmail($useremail, $subject, $message);

			email_log($random_number, $useremail, 'email_verify');
			$this->session->set_userdata('sender_otp', $random_number);
			$this->session->set_userdata('user_get_id', $userid);
			return true;
		}

	}

	public function username_check($str)
	{

		$query = $this->db->query("SELECT * FROM `users` where 
            email = '" . $str . "'  ");
		if ($query->num_rows() > 0)
			return true;
		else {
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
		} else {
			$this->form_validation->set_message('captcha_check', ucwords($this->lang->line('errorcaptcha')));
			return false;
		}

	}



	private function twofachecker($admin_id, $oneCode)
	{

		// $this->load->library('Google_authendicator');
		// $admin_auth = $this->db->query("SELECT * FROM `admin_members` where  id= '".$admin_id."' ")->row()->auth_key;
		// $ga = new Google_authendicator();	

		// $checkResult = $ga->verifyCode($admin_auth, $oneCode, 2);
		// if($checkResult) {
		// return true;
		// } else {
		// return false;
		// }
		return true;
	}


}
