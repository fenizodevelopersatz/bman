<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Profile extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();

        // CI core Security class is available as `$this->security` (do not load as a library).
        $this->load->library(['session', 'form_validation']);
        $this->load->helper(['url', 'form']);
        $this->load->database();

        if (!($this->session->userdata('logged_in') && $this->session->userdata('user_login'))) {
            redirect('user/in');
        }

        $language = $this->session->userdata('site_lang') ?? 'english';
        $this->config->set_item('language', $language);
        $this->lang->load('common', $language);

        $this->load->model('member/Users_model');
        $this->load->model(['Kyc_model' => 'kyc', 'member/Users_model' => 'users']);
    }

    // ----------------------------- PAGE -----------------------------
    public function settings()
    {
        $uid = (int) $this->session->userdata('userid');
        $user = $this->Users_model->get_user($uid);

        $kyc = $this->Users_model->get_kyc($uid);
        $bank = $this->Users_model->get_bank($uid);

        // fallback data
        $prefs = $this->Users_model->get_email_prefs($uid);
        if (!$prefs) {
            $prefs = [
                'success_payments' => 0,
                'payouts' => 1,
                'product_commission' => 0,
                'refund_alerts' => 0,
                'invoice_payments' => 1,
            ];
        }

        // Rank progress (example) => make dynamic with your own logic
        $rankPercent = 48;

        // Withdraw eligibility (example logic)
        $kycOk = isset($kyc['status']) && $kyc['status'] === 'approved';
        $bankOk = isset($bank['status']) && $bank['status'] === 'approved';
        $eligible = (is_withdraw_eligible($uid)) ? 'Eligible' : 'Not Eligible';


        $data = [];

        $current = $this->kyc->getByUser($uid);
        $status = $current['status'] ?? 'none';
        $readOnly = in_array($status, ['pending', 'approved'], true);

        $userid = $this->session->userdata('userid');
        // $data['kyc'] = $kyc;
        $data = [
            'kyc' => $current,
            'user' => $this->db->get_where('users', ['id' => $uid])->row_array(),
            'doc_types' => ['passport' => 'Passport', 'national_id' => 'National ID', 'driver_license' => 'Driver License'],
            'countries' => $this->_countries(),
            'status' => $current ? $current['status'] : '',
            'read_only' => $readOnly,
            'user_id' => $userid
        ];

        $data['title'] = "Profile Settings";
        $data['user'] = $user;

        $data['bank'] = $bank;
        $data['prefs'] = $prefs;

        $data['rankPercent'] = $rankPercent;
        $data['eligibleText'] = $eligible;

        $data['csrfName'] = $this->security->get_csrf_token_name();
        $data['csrfHash'] = $this->security->get_csrf_hash();
        $this->load->view('user/profile/view', $data);
    }


    private function _countries()
    {
        // Minimal list or pull from a countries table; keep ISO2 codes
        return [
            'IN' => 'India',
            'US' => 'United States',
            'GB' => 'United Kingdom',
            'AE' => 'United Arab Emirates',
            // …
        ];
    }


    // Backward compatible route: `user/edit-profile`
    public function edit()
    {
        return $this->settings();
    }

    // ----------------------------- JSON HELPER -----------------------------
    private function _json(array $data, $code = 200)
    {
        $data['csrfName'] = $this->security->get_csrf_token_name();
        $data['csrfHash'] = $this->security->get_csrf_hash();
        return $this->output
            ->set_status_header($code)
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }

    // ----------------------------- PROFILE UPDATE -----------------------------
    public function profile_update()
    {
        if (!$this->input->is_ajax_request())
            show_404();

        $uid = (int) $this->session->userdata('userid');
        if (!$uid)
            return $this->_json(["status" => "error", "message" => "Not logged in"], 401);

        $this->form_validation->set_rules('first_name', 'First Name', 'required|trim');
        $this->form_validation->set_rules('last_name', 'Last Name', 'required|trim');
        $this->form_validation->set_rules('contact', 'Phone', 'required|trim');
        $this->form_validation->set_rules('country', 'Country', 'required|trim');
        $this->form_validation->set_rules('time_zone', 'Timezone', 'required|trim');

        if ($this->form_validation->run() == FALSE) {
            return $this->_json(["status" => "error", "message" => strip_tags(validation_errors())], 422);
        }

        $data = [
            'first_name' => $this->input->post('first_name', true),
            'last_name' => $this->input->post('last_name', true),
            'contact' => $this->input->post('contact', true),
            'country' => $this->input->post('country', true),
            'time_zone' => $this->input->post('time_zone', true),
            'updated_date' => date('Y-m-d H:i:s')
        ];

        // upload avatar
        if (!empty($_FILES['profile_img']['name'])) {

            if (ENABLE_SITE_UPLOAD_FUNCTION !== true) {
                $this->_dbg('profile_upload_images1', 'Uploads disabled');
                return false;
            }

            $config['upload_path'] = './assets/images/';
            $config['allowed_types'] = 'jpg|jpeg|png|webp';
            $config['max_size'] = 2048;
            $ext = pathinfo($_FILES['profile_img']['name'], PATHINFO_EXTENSION);
            $filename = 'profile_' . uniqid() . '.' . $ext;
            $config['file_name'] = $filename;

            $this->load->library('upload', $config);

            if ($this->upload->do_upload('profile_img')) {
                $data['profile_img'] = $filename;
            } else {
                return $this->_json(["status" => "error", "message" => $this->upload->display_errors('', '')], 422);
            }
        }

        $ok = $this->Users_model->update_user($uid, $data);
        if (!$ok)
            return $this->_json(["status" => "error", "message" => "Profile update failed"], 500);

        return $this->_json(["status" => "success", "message" => "Profile updated"]);
    }

    // ----------------------------- KYC SUBMIT -----------------------------
    public function kyc_submit()
    {
        if (!$this->input->is_ajax_request())
            show_404();

        $uid = (int) $this->session->userdata('userid');
        if (!$uid)
            return $this->_json(["status" => "error", "message" => "Not logged in"], 401);

        $this->form_validation->set_rules('full_name_pan', 'Full Name (PAN)', 'required|trim');
        $this->form_validation->set_rules('pan_number', 'PAN Number', 'required|trim|min_length[6]');
        $this->form_validation->set_rules('dob', 'DOB', 'required|trim');
        $this->form_validation->set_rules('aadhaar_last4', 'Aadhaar Last4', 'required|trim|exact_length[4]');
        $this->form_validation->set_rules('address', 'Address', 'required|trim');
        $this->form_validation->set_rules('city', 'City', 'required|trim');
        $this->form_validation->set_rules('state', 'State', 'required|trim');
        $this->form_validation->set_rules('pincode', 'Pincode', 'required|trim');

        if ($this->form_validation->run() == FALSE) {
            return $this->_json(["status" => "error", "message" => strip_tags(validation_errors())], 422);
        }

        $data = [
            'full_name_pan' => $this->input->post('full_name_pan', true),
            'pan_number' => strtoupper($this->input->post('pan_number', true)),
            'dob' => $this->input->post('dob', true),
            'aadhaar_last4' => $this->input->post('aadhaar_last4', true),
            'address' => $this->input->post('address', true),
            'city' => $this->input->post('city', true),
            'state' => $this->input->post('state', true),
            'pincode' => $this->input->post('pincode', true),
            'status' => 'pending',
            'submitted_at' => date('Y-m-d H:i:s'),
            'reviewer_note' => 'Admin will review documents within 24–48 hrs.'
        ];

        // uploads
        $uploadDir = './assets/kyc/';
        if (!is_dir($uploadDir))
            @mkdir($uploadDir, 0777, true);

        $config['upload_path'] = $uploadDir;
        $config['allowed_types'] = 'jpg|jpeg|png|webp|pdf';
        $config['max_size'] = 2048;

        $this->load->library('upload', $config);

        if (!empty($_FILES['pan_doc']['name'])) {

            if (ENABLE_SITE_UPLOAD_FUNCTION !== true) {
                $this->_dbg('profile_upload_images2', 'Uploads disabled');
                return false;
            }

            $config['file_name'] = 'pan_' . $uid . '_' . uniqid();
            $this->upload->initialize($config);
            if ($this->upload->do_upload('pan_doc')) {
                $data['pan_doc'] = 'assets/kyc/' . $this->upload->data('file_name');
            } else {
                return $this->_json(["status" => "error", "message" => $this->upload->display_errors('', '')], 422);
            }
        }

        if (!empty($_FILES['aadhaar_doc']['name'])) {

            if (ENABLE_SITE_UPLOAD_FUNCTION !== true) {
                $this->_dbg('profile_upload_images3', 'Uploads disabled');
                return false;
            }

            $config['file_name'] = 'aadhaar_' . $uid . '_' . uniqid();
            $this->upload->initialize($config);
            if ($this->upload->do_upload('aadhaar_doc')) {
                $data['aadhaar_doc'] = 'assets/kyc/' . $this->upload->data('file_name');
            } else {
                return $this->_json(["status" => "error", "message" => $this->upload->display_errors('', '')], 422);
            }
        }

        $ok = $this->Users_model->upsert_kyc($uid, $data);
        if (!$ok)
            return $this->_json(["status" => "error", "message" => "KYC submit failed"], 500);

        return $this->_json(["status" => "success", "message" => "KYC submitted successfully"]);
    }

    // ----------------------------- BANK SAVE -----------------------------
    public function bank_save()
    {
        if (!$this->input->is_ajax_request())
            show_404();

        $uid = (int) $this->session->userdata('userid');
        if (!$uid)
            return $this->_json(["status" => "error", "message" => "Not logged in"], 401);

        $this->form_validation->set_rules('holder_name', 'Account Holder', 'required|trim');
        $this->form_validation->set_rules('bank_name', 'Bank Name', 'required|trim');
        $this->form_validation->set_rules('account_number', 'Account Number', 'required|trim|min_length[6]');
        $this->form_validation->set_rules('ifsc', 'IFSC', 'required|trim|min_length[6]');

        if ($this->form_validation->run() == FALSE) {
            return $this->_json(["status" => "error", "message" => strip_tags(validation_errors())], 422);
        }

        $data = [
            'holder_name' => $this->input->post('holder_name', true),
            'bank_name' => $this->input->post('bank_name', true),
            'account_number' => $this->input->post('account_number', true),
            'ifsc' => strtoupper($this->input->post('ifsc', true)),
            'upi_id' => $this->input->post('upi_id', true),
            'status' => 'pending',
            'submitted_at' => date('Y-m-d H:i:s'),
        ];

        $ok = $this->Users_model->upsert_bank($uid, $data);
        if (!$ok)
            return $this->_json(["status" => "error", "message" => "Bank save failed"], 500);

        return $this->_json(["status" => "success", "message" => "Bank details saved. Pending approval."]);
    }

    // ----------------------------- SECURITY: PASSWORD UPDATE -----------------------------
    public function update_password()
    {
        if (!$this->input->is_ajax_request())
            show_404();

        $uid = (int) $this->session->userdata('userid');
        if (!$uid)
            return $this->_json(['status' => 'error', 'message' => 'Not logged in'], 401);

        if (DEMOVERSION === true)
            return $this->_json(['status' => 'error', 'message' => 'This feature is disabled in demo mode'], 403);


        $cur = (string) $this->input->post('currentpassword', true);
        $new = (string) $this->input->post('newpassword', true);
        $con = (string) $this->input->post('confirmpassword', true);

        if ($new !== $con)
            return $this->_json(['status' => 'error', 'message' => 'Passwords do not match'], 422);
        if (strlen($new) < 8)
            return $this->_json(['status' => 'error', 'message' => 'Password must be at least 8 characters'], 422);

        $user = $this->Users_model->get_user($uid);
        if (!$user)
            return $this->_json(['status' => 'error', 'message' => 'User not found'], 404);

        $ok = password_verify($cur, $user['password']) || md5($cur) === $user['password'];
        if (!$ok)
            return $this->_json(['status' => 'error', 'message' => 'Current password is incorrect'], 422);

        $hash = password_hash($new, PASSWORD_DEFAULT);
        $this->Users_model->update_user($uid, ['password' => $hash, 'updated_date' => date('Y-m-d H:i:s')]);

        return $this->_json(['status' => 'success', 'message' => 'Password updated']);
    }

    // ----------------------------- NOTIFICATIONS -----------------------------
    public function update_email_preferences()
    {
        if (!$this->input->is_ajax_request())
            show_404();

        $uid = (int) $this->session->userdata('userid');
        if (!$uid)
            return $this->_json(['status' => 'error', 'message' => 'Unauthorized'], 401);

        $pref = $this->input->post('pref', true) ?: [];

        $data = [
            'success_payments' => !empty($pref['success_payments']) ? 1 : 0,
            'payouts' => !empty($pref['payouts']) ? 1 : 0,
            'product_commission' => !empty($pref['product_commission']) ? 1 : 0,
            'refund_alerts' => !empty($pref['refund_alerts']) ? 1 : 0,
            'invoice_payments' => !empty($pref['invoice_payments']) ? 1 : 0,
            'prefs_updated_at' => date('Y-m-d H:i:s'),
        ];

        // at least one enabled
        if (!$data['success_payments'] && !$data['payouts'] && !$data['product_commission'] && !$data['refund_alerts'] && !$data['invoice_payments']) {
            return $this->_json(['status' => 'error', 'message' => 'Select at least one notification option.'], 422);
        }

        $ok = $this->Users_model->update_email_prefs($uid, $data);
        if (!$ok)
            return $this->_json(['status' => 'error', 'message' => 'Could not save preferences.'], 500);

        return $this->_json(['status' => 'success', 'message' => 'Notification settings saved']);
    }

    // ----------------------------- DANGER: REQUEST DELETE -----------------------------
    public function request_delete()
    {
        if (!$this->input->is_ajax_request())
            show_404();

        if (DEMOVERSION === true)
            return $this->_json(['status' => 'error', 'message' => 'This feature is disabled in demo mode'], 403);

        $uid = (int) $this->session->userdata('userid');
        if (!$uid)
            return $this->_json(['status' => 'error', 'message' => 'Unauthorized'], 401);

        if ($this->Users_model->has_pending_action($uid, 'REQUEST_DELETE')) {
            return $this->_json(['status' => 'error', 'message' => 'Delete request already pending.'], 422);
        }

        $reason = trim($this->input->post('reason', true));
        $this->Users_model->create_action($uid, 'REQUEST_DELETE', $reason);

        // update the status=2, status=0(init), status=1(active)
        $this->db->where('id', $uid);
        $this->db->update('users', ['status' => 2]);

        return $this->_json(['status' => 'success', 'message' => 'Delete request submitted. Admin approval required.']);
    }

    // ----------------------------- DANGER: FREEZE WITHDRAW -----------------------------
    public function freeze_withdraw()
    {
        if (!$this->input->is_ajax_request())
            show_404();

        if (DEMOVERSION === true)
            return $this->_json(['status' => 'error', 'message' => 'This feature is disabled in demo mode'], 403);

        $uid = (int) $this->session->userdata('userid');
        if (!$uid)
            return $this->_json(['status' => 'error', 'message' => 'Unauthorized'], 401);

        if ($this->Users_model->has_pending_action($uid, 'FREEZE_WITHDRAW')) {
            return $this->_json(['status' => 'error', 'message' => 'Freeze request already pending.'], 422);
        }

        $reason = trim($this->input->post('reason', true));
        $this->Users_model->create_action($uid, 'FREEZE_WITHDRAW', $reason);

        return $this->_json(['status' => 'success', 'message' => 'Withdraw freeze request submitted.']);
    }
}
