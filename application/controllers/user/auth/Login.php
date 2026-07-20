<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Login extends CI_Controller
{

	public function __construct()
	{
		parent::__construct();
		$this->load->helper('cookie');
		$this->load->helper('captcha');

		if ($this->session->userdata('user_logged_in') && $this->session->userdata('user_login')) {
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

					$uid = $result_get['data']->id;
					$this->session->set_userdata('user_get_id', $uid);

					$user_row = $this->db->get_where('users', ['id' => (int) $uid])->row();
					$needs_2fa   = $user_row && (int) $user_row->twofa_status === 1;
					$needs_email = $user_row && (int) $user_row->email_verify_status === 1;

					if ($needs_2fa || $needs_email) {

						$this->session->set_userdata('twofa_required', $needs_2fa ? 1 : 0);
						$this->session->set_userdata('email_verify_required', $needs_email ? 1 : 0);
						$this->session->set_userdata('otp_required', 1);

						if ($needs_email) {
							$this->sender_otp($uid);
						}

					} else {

						// Neither factor is enabled for this user — log them in immediately.
						$this->session->set_userdata('otp_required', 0);
						$this->_complete_login($user_row);

					}

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

			$otp_required = $this->session->userdata('otp_required');

			if ($otp_required) {

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

						$expected = $this->session->userdata('sender_otp');
						$verify = ($expected !== null && $expected !== '' && (string) $otp === (string) $expected);

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

				$needs_2fa   = (int) $this->session->userdata('twofa_required') === 1;
				$needs_email = (int) $this->session->userdata('email_verify_required') === 1;

				$expected_otp = $this->session->userdata('sender_otp');
				$verify_1 = $needs_2fa ? $this->twofachecker($admin_id, $twofaOTP) : true;
				$verify_2 = $needs_email
					? ($expected_otp !== null && $expected_otp !== '' && (string) $emailOTP === (string) $expected_otp)
					: true;

				if ($verify_1 && $verify_2) {

					$this->session->set_userdata('verify_payment_page', "ok");
					$this->session->set_userdata('sender_otp', "");
					$response = array(
						'status' => true,
						'message' => "Verify Successfully"
					);

					$result = $this->db->query("SELECT * FROM users where id = '" . $admin_id . "' ")->row();
					$this->_complete_login($result);

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

		$needs_email = (int) $this->session->userdata('email_verify_required') === 1;
		$send_otp = $this->session->userdata('sender_otp');

		if ($needs_email && $send_otp == "") {
			$this->sender_otp($this->session->userdata('user_get_id'));
		}

		$this->data['verify_type'] = '0';
		$this->data['title'] = 'Verify Page';
		$this->data['action'] = base_url() . "user/auth/success";
		$this->data['show_twofa_code'] = (int) $this->session->userdata('twofa_required') === 1;
		$this->data['show_email_code'] = $needs_email;

		if ($needs_email) {
			$user_row = $this->db->get_where('users', ['id' => (int) $this->session->userdata('user_get_id')])->row();
			$this->data['admin_mail'] = $user_row ? $user_row->email : '';
		}

		$this->load->view('user/auth/login', $this->data);

	}



	public function sender_otp($userid)
	{

		$random_number = sprintf("%06d", random_string('numeric', 6));


		if ($random_number) {

			$this->load->model('member/Mlm_model');

			// member id comes from the `users` table (login), not admin_members.
			$user_row  = $this->db->query("SELECT * FROM `users` where id = '" . $userid . "' ")->row();
			$useremail = $user_row ? $user_row->email : '';

			if ($useremail) {
				$mailid = "7";
				$mail_subject_data = $this->db->query("SELECT * FROM email_template where id = '" . $mailid . "' ")->row();
				if ($mail_subject_data) {
					$subject = $mail_subject_data->subject;
					$message = str_replace('[temp_otp]', $random_number, $mail_subject_data->temp_content);
					$this->Mlm_model->sendmail($useremail, $subject, $message);
				}
				email_log($random_number, $useremail, 'email_verify');
			}

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



	// Finalizes a login: sets the full authenticated session (shared by the
	// no-verification-required path in index() and the OTP success path in
	// finelVerify()).
	private function _complete_login($result)
	{
		if (!$result) return;

		$array = array(
			"user_logged_in" => TRUE,
			"user_full_name" => $result->username,
			"user_userid" => $result->id,
			"user_email" => $result->email,
			"user_login" => TRUE
		);

		$this->session->set_userdata('remember_me', true);

		setcookie("remember_me", md5($result->id), time() + (60 * 2), '/');
		if (isset($_COOKIE['remember_me'])) {
			$array['cookiee'] = $_COOKIE['remember_me'];
		}

		$this->session->set_userdata($array);
	}

	private function twofachecker($admin_id, $oneCode)
	{
		$this->load->library('Google_authendicator');
		$user = $this->db->query("SELECT * FROM `users` WHERE id = '" . $admin_id . "'")->row();

		if (!$user || !$user->twofa_secret) {
			return false;
		}

		$ga = new Google_authendicator();
		return $ga->verifyCode($user->twofa_secret, $oneCode, 2);
	}


}
