<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Register extends CI_Controller {

	public function __construct() {

		parent::__construct();
		$this->load->helper('cookie');
		$this->load->helper('captcha');

        if($this->session->userdata('logged_in') && $this->session->userdata('user_login')) {
          redirect('user/main');
        }

		$language = $this->session->userdata('site_lang') ?? 'english';
		$this->config->set_item('language', $language);
		$this->lang->load('common', $language);

		$this->load->model('member/Mlm_model');

	}

	public function switch_language($lang = "english") {
        $this->session->set_userdata('site_lang', $lang);
        redirect($_SERVER['HTTP_REFERER']);
    }

    private function parse_referral_leg($raw)
    {
        $raw = trim((string)$raw);
        if ($raw === '') return ['', null];

        if (preg_match('/^(L|R)\-(.+)$/i', $raw, $m)) {
            $leg = (strtolower($m[1]) === 'l') ? 'left' : 'right';
            $code = trim($m[2]);
            return [$code, $leg];
        }

        return [$raw, null];
    }

	public function index()
	{
		if($this->input->post())
		{
			$this->form_validation->set_rules('username', 'Username', 'trim|required');
			$this->form_validation->set_rules('sponsor_id', 'Sponsor', 'trim|required');
			$this->form_validation->set_rules('password', 'Password', 'trim|required');

            $site_captcha_status = site_settings('captcha','status');
            if($site_captcha_status) {
                $this->form_validation->set_rules('g-recaptcha-response', 'captcha', 'trim|required|xss_clean|callback_captcha_check');
            }

			if ($this->form_validation->run() == FALSE) {

				$errors = $this->form_validation->error_array();
				$message = implode("<br>", $errors);
				echo json_encode(['status' => false, 'message' => $message]);
				exit;

			} else {

				$sponsor_info_raw = $this->input->post('sponsor_id', true); 
				$username = $this->input->post('username', true);
				$email = $this->input->post('useremail', true);
				$password = $this->input->post('password', true);

				$sponser_leg = $this->input->post('select_lg', true); 

                list($sponsor_code, $legFromPrefix) = $this->parse_referral_leg($sponsor_info_raw);

                if (!empty($legFromPrefix)) {
                    $sponser_leg = $legFromPrefix;
                } else {
                    if (!empty($sponser_leg)) {
                        $sponser_leg = strtolower(trim($sponser_leg));
                        $sponser_leg = ($sponser_leg === 'right') ? 'right' : 'left';
                    }
                }

				if ($this->Mlm_model->usernameExists($username)) {
					echo json_encode(["status" => false, "message" => "Username already taken"]);
					exit();
				}

                $sponsor = $this->db->get_where('users', ['referral_id' => $sponsor_code])->row();
				if (!$sponsor) {
					echo json_encode(["status" => false, "message" => "Sponsor not found !"]);
					exit();
				}

				$sponsor_id = (int)$sponsor->id;

				$user_id = $this->Mlm_model->registerUser($username, $email, $sponsor_id, $sponser_leg, $password);

				if ($user_id) {
					echo json_encode(["status" => true, "message" => "User registered successfully"]);
				} else {
					echo json_encode(["status" => false, "message" => "Registration failed"]);
				}

				exit();
			}
		}
		else {

            $re = $this->input->get('re', true);
            list($sponsor_code, $legFromPrefix) = $this->parse_referral_leg($re);

			$this->data['action'] = base_url()."user/re";

            // send to view to prefill
            $this->data['ref_raw']   = $re;             
            $this->data['ref_code']  = $sponsor_code;   
            $this->data['ref_leg']   = $legFromPrefix;   

			$this->load->view('user/auth/register', $this->data);
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
}
