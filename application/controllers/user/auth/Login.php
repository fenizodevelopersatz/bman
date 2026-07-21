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

					// Validate user_row exists before checking flags
					if (!$user_row) {
						echo json_encode(['status' => false, 'errors' => ['auth' => 'User not found']]);
						exit;
					}

					$needs_2fa   = (int) $user_row->twofa_status === 1;
					$needs_email = (int) $user_row->email_verify_status === 1;

					if ($needs_2fa || $needs_email) {

						$this->session->set_userdata('twofa_required', $needs_2fa ? 1 : 0);
						$this->session->set_userdata('email_verify_required', $needs_email ? 1 : 0);
						$this->session->set_userdata('otp_required', 1);
						$this->session->set_userdata('otp_started_at', time()); // Track when verification started

						if ($needs_email) {
							try {
								$this->sender_otp($uid);
							} catch (\Throwable $e) {
								log_message('error', 'sender_otp() threw: ' . get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
							}
						}

						// Verification required: send the user to the dedicated
						// verification URL.
						$redirect = base_url('user/verify');

					} else {

						// Neither factor is enabled for this user — log them in immediately.
						$this->session->set_userdata('otp_required', 0);
						$this->_complete_login($user_row);
						$redirect = base_url('user/main');

					}

					$response = [
						'status' => true,
						'message' => 'login successfuly',
						'redirect' => $redirect,
					];

					// NOTE: $this->output->set_output(...) only stores the string —
					// it is echoed by $this->output->_display(), which CI3 only
					// invokes automatically from its own top-level flow. exit; here
					// skips that entirely, so that chain silently produced an empty
					// response on every successful login. Plain echo (as used by
					// every other JSON response in this file) actually sends bytes.
					echo json_encode($response);
					exit;

				} else {

					echo json_encode(['status' => false, 'errors' => $result_get['message']]);
					exit;
				}
			}
		} else {

			// GET /user/in ALWAYS shows the username + password form (including on
			// refresh). The Two-Factor & Email Verification step lives on its own
			// URL — /user/verify — handled by verify() below.
			$this->data['verify_type'] = '1';
			$this->data['action'] = base_url() . "user/in";
			$this->load->view('user/auth/login', $this->data);

		}

	}

	/*
	|--------------------------------------------------------------------------
	| Two-Factor & Email Verification page (/user/verify)
	|--------------------------------------------------------------------------
	| Reached only after a successful username + password login that requires a
	| second factor. Loading it without an active verification session (e.g.
	| someone types the URL directly, or the session expired) bounces back to
	| the login form.
	*/
	public function verify()
	{
		$otp_required   = $this->session->userdata('otp_required');
		$user_get_id    = $this->session->userdata('user_get_id');
		$otp_started_at = $this->session->userdata('otp_started_at');

		// No verification in progress → nothing to verify, return to login.
		if (!($otp_required || $user_get_id)) {
			redirect('user/in');
		}

		// Session timeout: 1 hour (3600 seconds).
		$otp_timeout = 3600;
		if ($otp_started_at && (time() - $otp_started_at) > $otp_timeout) {
			$this->_clear_verification_session();
			$this->session->set_flashdata('danger', 'Your verification session has expired. Please sign in again.');
			redirect('user/in');
		}

		$this->auth_verify();
	}
	/*
	|--------------------------------------------------------------------------
	| VERIFY  Forgot Password
	|--------------------------------------------------------------------------
	*/
	public function forgot()
	{
		if ($this->input->post()) {
			$this->output->set_content_type('application/json');

			$email = $this->input->post('email', TRUE);

			if (!$email) {
				echo json_encode(['status' => false, 'message' => 'Email is required']);
				exit;
			}

			$user = $this->db->get_where('users', ['email' => $email])->row();

			if (!$user) {
				echo json_encode(['status' => true, 'message' => 'If this email exists, we will send you password reset instructions']);
				exit;
			}

			try {
				$reset_token = bin2hex(random_bytes(32));
				$token_expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

				$this->db->update('users', [
					'password_reset_token' => $reset_token,
					'password_reset_expiry' => $token_expiry
				], ['id' => $user->id]);

				$this->load->model('member/Mlm_model');
				$reset_link = base_url('user/reset-password?token=' . $reset_token);

				$subject = "Password Reset Request";
				$message = "
					<p>Hello " . htmlspecialchars($user->username) . ",</p>
					<p>We received a request to reset your password. Click the link below to reset it:</p>
					<p><a href='" . $reset_link . "' style='display: inline-block; padding: 10px 20px; background: #6366F1; color: white; text-decoration: none; border-radius: 4px;'>Reset Password</a></p>
					<p>This link will expire in 1 hour.</p>
					<p>If you didn't request this, you can safely ignore this email.</p>
					<p>Best regards,<br>Nexman Team</p>
				";

				$send_result = $this->Mlm_model->sendmail($email, $subject, $message);
				email_log($reset_token, $email, 'password_reset');

				echo json_encode(['status' => true, 'message' => 'If this email exists, we will send you password reset instructions']);
			} catch (Exception $e) {
				log_message('error', 'Forgot password error: ' . $e->getMessage());
				echo json_encode(['status' => false, 'message' => 'An error occurred. Please try again later.']);
			}
			exit;
		}

		$this->data['action'] = base_url() . "user/forgot";
		$this->load->view('user/auth/forgot', $this->data);
	}

	public function reset_password()
	{
		$token = $this->input->get('token');

		if (!$token) {
			show_error('Invalid reset link. Token is missing.', 400);
			return;
		}

		if ($this->input->post()) {
			$this->output->set_content_type('application/json');

			$password = $this->input->post('password');
			$password_confirm = $this->input->post('password_confirm');

			if (!$password || !$password_confirm) {
				echo json_encode(['status' => false, 'message' => 'Password and confirmation are required']);
				exit;
			}

			if ($password !== $password_confirm) {
				echo json_encode(['status' => false, 'message' => 'Passwords do not match']);
				exit;
			}

			if (strlen($password) < 8) {
				echo json_encode(['status' => false, 'message' => 'Password must be at least 8 characters']);
				exit;
			}

			try {
				$user = $this->db->get_where('users', ['password_reset_token' => $token])->row();

				if (!$user) {
					echo json_encode(['status' => false, 'message' => 'Invalid or expired reset link']);
					exit;
				}

				if (strtotime($user->password_reset_expiry) < time()) {
					echo json_encode(['status' => false, 'message' => 'Reset link has expired. Please request a new one.']);
					exit;
				}

				$hashed_password = password_hash($password, PASSWORD_BCRYPT);

				$this->db->update('users', [
					'password' => $hashed_password,
					'password_reset_token' => NULL,
					'password_reset_expiry' => NULL
				], ['id' => $user->id]);

				echo json_encode(['status' => true, 'message' => 'Password reset successfully. You can now login.']);
			} catch (Exception $e) {
				log_message('error', 'Password reset error: ' . $e->getMessage());
				echo json_encode(['status' => false, 'message' => 'An error occurred. Please try again.']);
			}
			exit;
		}

		$user = $this->db->get_where('users', ['password_reset_token' => $token])->row();

		if (!$user) {
			show_error('Invalid or expired reset link', 400);
			return;
		}

		if (strtotime($user->password_reset_expiry) < time()) {
			show_error('Reset link has expired. Please request a new one.', 400);
			return;
		}

		$this->data['token'] = $token;
		$this->data['action'] = base_url() . "user/reset-password?token=" . $token;
		$this->load->view('user/auth/reset_password', $this->data);
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

				// Track OTP verification attempts (max 2 attempts allowed)
				$otp_attempts = (int) $this->session->userdata('otp_attempts') ?? 0;
				$max_attempts = 2;

				$expected_otp = $this->session->userdata('sender_otp');
				$verify_1 = $needs_2fa ? $this->twofachecker($admin_id, $twofaOTP) : true;
				$verify_2 = $needs_email
					? ($expected_otp !== null && $expected_otp !== '' && (string) $emailOTP === (string) $expected_otp)
					: true;

				if ($verify_1 && $verify_2) {

					$this->session->set_userdata('verify_payment_page', "ok");
					$this->session->set_userdata('sender_otp', "");
					$this->session->unset_userdata('otp_attempts');
					$response = array(
						'status' => true,
						'message' => "Verify Successfully"
					);

					$result = $this->db->query("SELECT * FROM users where id = '" . $admin_id . "' ")->row();
					$this->_complete_login($result);

				} else {

					// Increment failed attempt counter
					$otp_attempts++;
					$this->session->set_userdata('otp_attempts', $otp_attempts);

					// After 2 failed attempts, reset login and show login form
					if ($otp_attempts >= $max_attempts) {
						$this->session->set_flashdata('danger', 'Maximum OTP attempts exceeded. Please login again.');
						$this->session->unset_userdata('otp_required');
						$this->session->unset_userdata('twofa_required');
						$this->session->unset_userdata('email_verify_required');
						$this->session->unset_userdata('user_get_id');
						$this->session->unset_userdata('sender_otp');
						$this->session->unset_userdata('otp_attempts');

						$response = array(
							'status' => false,
							'message' => "Maximum attempts exceeded. Please login again.",
							'redirect' => base_url('user/in'),
							'max_attempts_exceeded' => true
						);
					} else {
						$remaining_attempts = $max_attempts - $otp_attempts;
						$this->session->set_flashdata('danger', "Invalid OTP! ({$remaining_attempts} attempts remaining)");
						$response = array(
							'status' => false,
							'message' => "Invalid OTP! ({$remaining_attempts} attempts remaining)"
						);
					}
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

	// Resend OTP via email
	public function resend_otp()
	{
		$user_id = $this->session->userdata('user_get_id');

		if (!$user_id) {
			echo json_encode(['status' => false, 'message' => 'No active verification session']);
			exit;
		}

		$result = $this->sender_otp($user_id);

		if ($result) {
			echo json_encode(['status' => true, 'message' => 'OTP resent to your email']);
		} else {
			echo json_encode(['status' => false, 'message' => 'Failed to resend OTP']);
		}
		exit;
	}

	// Clear verification session and go back to login
	public function back_to_login()
	{
		// Clear all verification flags
		$this->_clear_verification_session();

		redirect('user/in');
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

		// Clear OTP-related session flags after successful login
		$this->session->unset_userdata('otp_required');
		$this->session->unset_userdata('twofa_required');
		$this->session->unset_userdata('email_verify_required');
		$this->session->unset_userdata('user_get_id');
		$this->session->unset_userdata('sender_otp');
	}

	// Clears every session flag tied to an in-progress 2FA / email verification.
	// Shared by the OTP timeout, the "back to login" action, and the refresh
	// guard in index() so they all reset the same set of keys.
	private function _clear_verification_session()
	{
		$this->session->unset_userdata('otp_required');
		$this->session->unset_userdata('user_get_id');
		$this->session->unset_userdata('twofa_required');
		$this->session->unset_userdata('email_verify_required');
		$this->session->unset_userdata('sender_otp');
		$this->session->unset_userdata('otp_started_at');
		$this->session->unset_userdata('otp_attempts');
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
