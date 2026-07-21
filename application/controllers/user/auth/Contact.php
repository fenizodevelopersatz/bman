<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Public "Contact Us / Account Unlock" page. Deliberately NOT gated by a
 * login check — a frozen account has its login blocked (see
 * Common_model::userloginVerify), so this is the only page such a user can
 * still reach. Anyone signed in already can also use it without issue.
 */
class Contact extends CI_Controller
{

	public function __construct()
	{
		parent::__construct();
		$this->load->helper('url');

		$language = $this->session->userdata('site_lang') ?? 'english';
		$this->config->set_item('language', $language);
		$this->lang->load('common', $language);
	}

	public function index()
	{
		if ($this->input->post()) {

			$this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email|callback_email_exists');
			$this->form_validation->set_rules('message', 'Message', 'trim|required|min_length[10]');

			if ($this->form_validation->run() == FALSE) {
				echo json_encode(['status' => false, 'errors' => $this->form_validation->error_array()]);
				exit;
			}

			$email = $this->input->post('email', true);
			$message = $this->input->post('message', true);

			$user = $this->db->get_where('users', ['email' => $email])->row();

			$attachment = null;
			if (!empty($_FILES['attachment']['name'])) {

				if (ENABLE_SITE_UPLOAD_FUNCTION !== true) {
					echo json_encode(['status' => false, 'errors' => ['attachment' => 'File uploads are currently disabled.']]);
					exit;
				}

				$config['upload_path'] = './assets/images/contact/';
				$config['allowed_types'] = 'jpg|jpeg|png|webp|pdf';
				$config['max_size'] = 5120; // 5MB
				$ext = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
				$filename = 'contact_' . uniqid() . '.' . $ext;
				$config['file_name'] = $filename;

				$this->load->library('upload', $config);

				if ($this->upload->do_upload('attachment')) {
					$attachment = $filename;
				} else {
					echo json_encode(['status' => false, 'errors' => ['attachment' => $this->upload->display_errors('', '')]]);
					exit;
				}
			}

			$this->db->insert('contact_requests', [
				'user_id' => $user ? (int) $user->id : null,
				'email' => $email,
				'message' => $message,
				'attachment_path' => $attachment,
				'status' => 'pending',
				'created_at' => date('Y-m-d H:i:s'),
			]);

			echo json_encode(['status' => true, 'message' => "Thanks — we've received your message and will get back to you shortly."]);
			exit;

		} else {

			$this->data['action'] = base_url() . 'user/contact';
			$this->load->view('user/auth/contact', $this->data);

		}
	}

	public function email_exists($str)
	{
		$query = $this->db->get_where('users', ['email' => $str]);
		if ($query->num_rows() > 0) {
			return true;
		}
		$this->form_validation->set_message('email_exists', 'We could not find an account with that email.');
		return false;
	}
}
