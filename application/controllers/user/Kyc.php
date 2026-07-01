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
        $uid = (int) $this->session->userdata('user_userid');
        if (!$uid)
            redirect('auth/login');

        $current = $this->kyc->getByUser($uid);
        // NEW: resolve the canonical state-machine state and derive editability from it.
        $state = $current ? $this->kyc->fromDb($current['status']) : Kyc_model::S_NOT_SUBMITTED;
        $readOnly = !$this->kyc->canUserEdit($state); // editable only in NOT_SUBMITTED / RESUBMIT_REQUIRED

        $userid = $this->session->userdata('user_userid');

        $data = [
            'kyc' => $current,
            'user' => $this->db->get_where('users', ['id' => $uid])->row_array(),
            // NEW: only the three document types required by the spec (labels map to existing enum values).
            'doc_types' => ['national_id' => 'Aadhaar Id', 'driver_license' => 'Driving License', 'passport' => 'Passport'],
            'countries' => $this->_countries(),
            'state' => $state,          // NEW: canonical state name for the status pill
            'read_only' => $readOnly,
            // NEW: surface the reviewer's resubmission reason when a resubmission is required.
            'reject_reason' => ($state === Kyc_model::S_RESUBMIT_REQUIRED) ? ($current['review_notes'] ?? '') : '',
            'user_id' => $userid
        ];

        $this->load->view('user/account/kyc_form', $data);
    }


    public function submit()
    {
        if (!$this->input->is_ajax_request())
            show_404();

        $uid = (int) $this->session->userdata('user_userid');
        if (!$uid)
            return $this->_json(['status' => 'error', 'message' => 'Unauthorized'], 401);

        // Get existing application (to keep old URLs if user didn't reupload)
        $existing = $this->kyc->getByUser($uid);
        // NEW: state-machine guard — users may upload only when NOT_SUBMITTED or RESUBMIT_REQUIRED.
        $curState = $existing ? $this->kyc->fromDb($existing['status']) : Kyc_model::S_NOT_SUBMITTED;
        if (!$this->kyc->canUserEdit($curState)) {
            return $this->_json([
                'status' => 'error',
                'message' => 'Your KYC is ' . $curState . ' and cannot be edited. Please wait for the review result.',
            ], 403);
        }

        // ------- Validate required fields -------
        // NEW: simplified manual-KYC form — only Document Type + Document Number are text fields;
        // the three images are validated further below. Only the 3 allowed document types are accepted.
        $this->load->library('form_validation');
        $this->form_validation->set_rules('doc_type', 'Document Type', 'required|in_list[passport,national_id,driver_license]');
        $this->form_validation->set_rules('doc_number', 'Document Number', 'required|max_length[80]');

        if (!$this->form_validation->run()) {
            return $this->_json(['status' => 'error', 'message' => strip_tags(validation_errors())], 422);
        }

        // ------- Upload files -------
        $upload_path = FCPATH . 'uploads/kyc/' . $uid . '/';
        if (!is_dir($upload_path))
            @mkdir($upload_path, 0775, true);

        $this->load->library('upload');
        // NEW: restrict to the spec's allowed image formats (JPG, JPEG, PNG, TIFF, GIF) and a 4MB/image cap.
        $cfg = [
            'upload_path' => $upload_path,
            'allowed_types' => 'jpg|jpeg|png|tif|tiff|gif',
            'max_size' => 4096, // 4MB per image
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
            $uid = (int) $this->session->userdata('user_userid');
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

        // ------- Server-side required files: Front, Back and Selfie are ALL mandatory -------
        // NEW: back image is now required for every document type, per the manual-KYC spec.
        if (empty($url_front) || empty($url_back) || empty($url_self)) {
            return $this->_json(['status' => 'error', 'message' => 'Please upload the Front image, Back image and a Selfie with ID.'], 422);
        }

        // NEW: the simplified form no longer collects profile/address fields, but the kyc_applications
        // table keeps them NOT NULL for backward compatibility. Preserve any existing values (so resubmits
        // don't lose data), otherwise fall back to the user's profile / safe defaults.
        $profile = $this->db->get_where('users', ['id' => $uid])->row_array() ?: [];
        $keep = function ($col, $default) use ($existing) {
            return !empty($existing[$col]) ? $existing[$col] : $default; // don't overwrite on resubmit
        };
        $dobVal = $keep('dob', (!empty($profile['dob']) && strtotime($profile['dob'])) ? date('Y-m-d', strtotime($profile['dob'])) : '1970-01-01');

        // ------- Payload -------
        $payload = [
            // Legacy profile columns kept for backward compatibility (auto-filled, not shown on the form).
            'country_iso2'      => $keep('country_iso2', 'IN'),
            'full_name'         => $keep('full_name', ($profile['name'] ?? $profile['username'] ?? 'N/A')),
            'dob'               => $dobVal,
            'gender'            => $keep('gender', 'unspecified'),
            'nationality_iso2'  => $keep('nationality_iso2', 'IN'),
            'addr_line1'        => $keep('addr_line1', ($profile['address'] ?? 'N/A')),
            'addr_line2'        => $existing['addr_line2'] ?? null,
            'addr_city'         => $keep('addr_city', 'N/A'),
            'addr_region'       => $existing['addr_region'] ?? null,
            'addr_postal'       => $keep('addr_postal', 'N/A'),
            'doc_issue_country' => $keep('doc_issue_country', 'IN'),

            // Fields captured by the simplified manual-KYC form.
            'doc_type'          => $docType,
            'doc_number'        => $p['doc_number'],
            'doc_front_url'     => $url_front,
            'doc_back_url'      => $url_back,
            'selfie_url'        => $url_self,
            'proof_address_url' => $url_proof,

            'consent'        => 1,          // NEW: submitting the form implies consent
            'status'         => 'pending',
            'review_notes'   => null,        // NEW: clear previous rejection metadata on (re)submit
            'rejection_code' => null,
        ];

        $kyc_id = $this->kyc->createOrUpdate($uid, $payload);

        // NEW: log the state-machine transition (NOT_SUBMITTED|RESUBMIT_REQUIRED -> PENDING).
        $this->kyc->addAudit($kyc_id, $uid, 'submit',
            $curState . ' -> ' . Kyc_model::S_PENDING . ' (by user)');

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
        $uid = $this->session->userdata('user_userid');
        if (!$uid)
            return $this->_json(['status' => 'error', 'message' => 'Unauthorized'], 401);

        if (ENABLE_SITE_UPLOAD_FUNCTION !== true) {
            $this->_dbg('kyc_upload_images2', 'Uploads disabled');
            return $this->_json(['status' => 'error', 'message' => 'Uploads disabled'], 401);
        }

        // Configure upload
        // NEW: keep this optional handler in sync with the allowed formats/size used by submit().
        $config = [
            'upload_path' => FCPATH . 'uploads/kyc/' . $uid . '/',
            'allowed_types' => 'jpg|jpeg|png|tif|tiff|gif',
            'encrypt_name' => true,
            'remove_spaces' => true,
            'file_ext_tolower' => true,
            'detect_mime' => true,
            'max_size' => 4096, // 4MB per image
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
