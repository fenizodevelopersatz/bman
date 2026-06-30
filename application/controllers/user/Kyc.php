<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Kyc extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Kyc_model' => 'kyc', 'member/Users_model' => 'users']);
        $this->load->helper(['url', 'security']);
        $this->load->library('form_validation');
        $this->load->library('upload');
    }

    public function index()
    {
        $uid = (int) $this->session->userdata('userid');
        if (!$uid)
            redirect('auth/login');

        $current = $this->kyc->getByUser($uid);
        $status = $current['status'] ?? 'none';
        $readOnly = in_array($status, ['pending', 'approved'], true);

        $userid = $this->session->userdata('userid');

        $data = [
            'kyc' => $current,
            'user' => $this->db->get_where('users', ['id' => $uid])->row_array(),
            'doc_types' => ['passport' => 'Passport', 'national_id' => 'National ID', 'driver_license' => 'Driver License'],
            'countries' => $this->_countries(),
            'status' => $current ? $current['status'] : '',
            'read_only' => $readOnly,
            'user_id' => $userid
        ];

        $this->load->view('user/account/kyc_form', $data);
    }


    public function submit()
    {
        if (!$this->input->is_ajax_request())
            show_404();

        $uid = (int) $this->session->userdata('userid');
        if (!$uid)
            return $this->_json(['status' => 'error', 'message' => 'Unauthorized'], 401);

        // Get existing application (to keep old URLs if user didn't reupload)
        $existing = $this->kyc->getByUser($uid);
        if ($existing && in_array($existing['status'], ['pending', 'approved'], true)) {
            return $this->_json([
                'status' => 'error',
                'message' => 'Your KYC is ' . $existing['status'] . ' and cannot be edited. Please wait for the review result.',
            ], 403);
        }

        // ------- Validate required fields -------
        $this->load->library('form_validation');
        $this->form_validation->set_rules('full_name', 'Full Name', 'required|min_length[2]|max_length[150]');
        $this->form_validation->set_rules('dob', 'DOB', 'required');
        $this->form_validation->set_rules('country_iso2', 'Country', 'required|exact_length[2]');
        $this->form_validation->set_rules('nationality_iso2', 'Nationality', 'required|exact_length[2]');
        $this->form_validation->set_rules('addr_line1', 'Address Line 1', 'required');
        $this->form_validation->set_rules('addr_city', 'City', 'required');
        $this->form_validation->set_rules('addr_postal', 'Postal', 'required');
        $this->form_validation->set_rules('doc_type', 'Document Type', 'required|in_list[passport,national_id,driver_license]');
        $this->form_validation->set_rules('doc_number', 'Document Number', 'required');
        $this->form_validation->set_rules('doc_issue_country', 'Issuing Country', 'required|exact_length[2]');
        $this->form_validation->set_rules('consent', 'Consent', 'required|in_list[1]');

        if (!$this->form_validation->run()) {
            return $this->_json(['status' => 'error', 'message' => strip_tags(validation_errors())], 422);
        }

        // ------- Upload files -------
        $upload_path = FCPATH . 'uploads/kyc/' . $uid . '/';
        if (!is_dir($upload_path))
            @mkdir($upload_path, 0775, true);

        $this->load->library('upload');
        $cfg = [
            'upload_path' => $upload_path,
            'allowed_types' => 'jpg|jpeg|png|webp|gif|pdf|jfif|heic|heif',
            'max_size' => 8192, // 8MB
            'encrypt_name' => true,
            'remove_spaces' => true,
            'file_ext_tolower' => true,
            'detect_mime' => true,
        ];

        $doUpload = function ($field) use ($cfg) {
            if (empty($_FILES[$field]['name']))
                return [true, null]; // not uploaded            
            $this->upload->initialize($cfg, TRUE);

            if (ENABLE_SITE_UPLOAD_FUNCTION !== true) {
                $this->_dbg('kyc_upload_images1', 'Uploads disabled');
                return false;
            }

            if (!$this->upload->do_upload($field)) {
                return [false, strip_tags($this->upload->display_errors('', ''))];
            }
            $data = $this->upload->data();
            $uid = (int) $this->session->userdata('userid');
            return [true, base_url('uploads/kyc/' . $uid . '/' . $data['file_name'])];
        };

        list($ok, $url_front) = $doUpload('doc_front');
        if (!$ok)
            return $this->_json(['status' => 'error', 'message' => $url_front], 422);
        list($ok, $url_back) = $doUpload('doc_back');
        if (!$ok)
            return $this->_json(['status' => 'error', 'message' => $url_back], 422);
        list($ok, $url_self) = $doUpload('selfie');
        if (!$ok)
            return $this->_json(['status' => 'error', 'message' => $url_self], 422);
        list($ok, $url_proof) = $doUpload('proof_address');
        if (!$ok)
            return $this->_json(['status' => 'error', 'message' => $url_proof], 422);

        if (!$url_front)
            $url_front = $existing['doc_front_url'] ?? null;
        if (!$url_back)
            $url_back = $existing['doc_back_url'] ?? null;
        if (!$url_self)
            $url_self = $existing['selfie_url'] ?? null;
        if (!$url_proof)
            $url_proof = $existing['proof_address_url'] ?? null;

        $p = $this->input->post(NULL, true);
        $docType = $p['doc_type'];

        // Server-side required files
        if (empty($url_front) || empty($url_self)) {
            return $this->_json(['status' => 'error', 'message' => 'Please upload ID Front and a Selfie.'], 422);
        }
        if (in_array($docType, ['national_id', 'driver_license']) && empty($url_back)) {
            return $this->_json(['status' => 'error', 'message' => 'Please upload the back side of your document.'], 422);
        }

        // ------- Payload -------
        $payload = [
            'country_iso2' => strtoupper($p['country_iso2']),
            'full_name' => $p['full_name'],
            'dob' => $p['dob'],
            'gender' => $p['gender'] ?? 'unspecified',
            'nationality_iso2' => strtoupper($p['nationality_iso2']),

            'addr_line1' => $p['addr_line1'],
            'addr_line2' => $p['addr_line2'] ?? null,
            'addr_city' => $p['addr_city'],
            'addr_region' => $p['addr_region'] ?? null,
            'addr_postal' => $p['addr_postal'],

            'doc_type' => $docType,
            'doc_number' => $p['doc_number'],
            'doc_issue_country' => strtoupper($p['doc_issue_country']),
            'doc_issue_date' => $p['doc_issue_date'] ?: null,
            'doc_expiry_date' => $p['doc_expiry_date'] ?: null,

            'doc_front_url' => $url_front,
            'doc_back_url' => $url_back,
            'selfie_url' => $url_self,
            'proof_address_url' => $url_proof,

            'is_pep' => !empty($p['is_pep']) ? 1 : 0,
            'consent' => !empty($p['consent']) ? 1 : 0,
            'status' => 'pending',
        ];

        $kyc_id = $this->kyc->createOrUpdate($uid, $payload);

        $this->users->setKycStatus($uid, 'pending');
        $this->db->where('id', $uid)->update('users', ['kyc_last_submitted_at' => date('Y-m-d H:i:s')]);

        return $this->_json([
            'status' => 'success',
            'message' => 'KYC submitted successfully. We will review your documents.',
            'kyc_id' => $kyc_id,
            'csrf' => [
                'name' => $this->security->get_csrf_token_name(),
                'hash' => $this->security->get_csrf_hash(),
            ],
        ]);
    }


    public function upload()
    {
        if (!$this->input->is_ajax_request())
            show_404();
        $uid = $this->session->userdata('userid');
        if (!$uid)
            return $this->_json(['status' => 'error', 'message' => 'Unauthorized'], 401);

        if (ENABLE_SITE_UPLOAD_FUNCTION !== true) {
            $this->_dbg('kyc_upload_images2', 'Uploads disabled');
            return $this->_json(['status' => 'error', 'message' => 'Uploads disabled'], 401);
        }

        // Configure upload
        $config = [
            'upload_path' => FCPATH . 'uploads/kyc/' . $uid . '/',
            'allowed_types' => 'jpg|jpeg|png|webp|gif|pdf|jfif|heic|heif',
            'encrypt_name' => true,
            'remove_spaces' => true,
            'file_ext_tolower' => true,
            'detect_mime' => true,
            'max_size' => 8192, // 8MB
        ];
        if (!is_dir($config['upload_path']))
            @mkdir($config['upload_path'], 0775, true);
        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('file')) {
            return $this->_json(['status' => 'error', 'message' => $this->upload->display_errors('', '')], 422);
        }
        $data = $this->upload->data();
        $url = base_url('uploads/kyc/' . $uid . '/' . $data['file_name']);

        return $this->_json(['status' => 'success', 'url' => $url]);
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

    private function _json($payload = [], $code = 200)
    {
        $this->output->set_status_header($code)->set_content_type('application/json')->set_output(json_encode($payload));
    }
}
