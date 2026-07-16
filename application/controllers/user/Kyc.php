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

        $errors = [];
        $p = $this->input->post(NULL, true) ?: [];
        $addError = function ($key, $msg) use (&$errors) {
            if (!isset($errors[$key])) $errors[$key] = $msg;
        };
        $trim = function ($key) use ($p) {
            return trim((string) ($p[$key] ?? ''));
        };

        $full_name = $trim('full_name');
        $dob = $trim('dob');
        $gender = $trim('gender');
        $country = $trim('country_iso2');
        $nationality = $trim('nationality_iso2');
        $addr1 = $trim('addr_line1');
        $addr2 = $trim('addr_line2');
        $city = $trim('addr_city');
        $region = $trim('addr_region');
        $postal = $trim('addr_postal');
        $doc_type = $trim('doc_type');
        $doc_number = $trim('doc_number');
        $issue_country = $trim('doc_issue_country');
        $issue_date = $trim('doc_issue_date');
        $expiry_date = $trim('doc_expiry_date');
        $consent = (string) ($p['consent'] ?? '');

        if ($full_name === '') $addError('full_name', 'Full name is required.');
        elseif (mb_strlen($full_name) < 3 || mb_strlen($full_name) > 100) $addError('full_name', 'Full name must be between 3 and 100 characters.');
        if ($dob === '') $addError('dob', 'Date of birth is required.');
        elseif ($this->_ageYears($dob) < 18) $addError('dob', 'You must be at least 18 years old.');
        if ($gender === '' || $gender === 'unspecified') $addError('gender', 'Gender is required.');
        if ($country === '') $addError('country_iso2', 'Country of residence is required.');
        if ($nationality === '') $addError('nationality_iso2', 'Nationality is required.');
        if ($addr1 === '') $addError('addr_line1', 'Address is required.');
        if ($city === '') $addError('addr_city', 'City is required.');
        if ($region === '') $addError('addr_region', 'Region / State is required.');
        if ($postal === '') $addError('addr_postal', 'Postal code is required.');
        if ($doc_type === '') $addError('doc_type', 'Please select a document type.');
        if ($doc_number === '') $addError('doc_number', 'Document number is required.');
        elseif (mb_strlen($doc_number) < 5) $addError('doc_number', 'Document number must be at least 5 characters.');
        if ($issue_country === '') $addError('doc_issue_country', 'Issuing country is required.');
        if ($issue_date === '') $addError('doc_issue_date', 'Issue date is required.');
        if ($expiry_date === '') $addError('doc_expiry_date', 'Expiry date is required.');
        elseif ($issue_date !== '' && strtotime($expiry_date) <= strtotime($issue_date)) $addError('doc_expiry_date', 'Expiry date must be after issue date.');
        if ($consent !== '1') $addError('consent', 'You must accept verification consent.');

        if (!in_array($doc_type, ['passport', 'national_id', 'driver_license'], true)) {
            $addError('doc_type', 'Please select a document type.');
        }

        $this->_validateUploadOrKeep('doc_front', $existing['doc_front_url'] ?? '', 'Front image is required.', ['jpg','jpeg','png','webp','gif'], 8, $errors);
        $this->_validateUploadOrKeep('doc_back', $existing['doc_back_url'] ?? '', 'Back image is required.', ['jpg','jpeg','png','webp','gif'], 8, $errors);
        $this->_validateUploadOrKeep('selfie', $existing['selfie_url'] ?? '', 'Selfie image is required.', ['jpg','jpeg','png','webp'], 8, $errors);

        if (!empty($errors)) {
            return $this->_json([
                'status' => 'error',
                'message' => 'Please correct the highlighted fields.',
                'errors' => $errors,
            ], 422);
        }

        $upload_path = FCPATH . 'uploads/kyc/' . $uid . '/';
        if (!is_dir($upload_path)) {
            @mkdir($upload_path, 0775, true);
        }

        $cfg = [
            'upload_path' => $upload_path,
            'allowed_types' => 'jpg|jpeg|png|webp|gif',
            'max_size' => 8192,
            'encrypt_name' => true,
            'remove_spaces' => true,
            'file_ext_tolower' => true,
            'detect_mime' => true,
        ];

        $uploadOne = function ($field) use ($cfg, $uid) {
            if (empty($_FILES[$field]['name'])) {
                return [true, null];
            }
            $this->upload->initialize($cfg, true);
            if (ENABLE_SITE_UPLOAD_FUNCTION !== true) {
                return [false, 'Uploads are disabled.'];
            }
            if (!$this->upload->do_upload($field)) {
                return [false, strip_tags($this->upload->display_errors('', ''))];
            }
            $data = $this->upload->data();
            return [true, base_url('uploads/kyc/' . (int) $uid . '/' . $data['file_name'])];
        };

        list($ok, $url_front) = $uploadOne('doc_front');
        if (!$ok) return $this->_json(['status' => 'error', 'message' => $url_front, 'errors' => ['doc_front' => $url_front]], 422);
        list($ok, $url_back) = $uploadOne('doc_back');
        if (!$ok) return $this->_json(['status' => 'error', 'message' => $url_back, 'errors' => ['doc_back' => $url_back]], 422);
        list($ok, $url_selfie) = $uploadOne('selfie');
        if (!$ok) return $this->_json(['status' => 'error', 'message' => $url_selfie, 'errors' => ['selfie' => $url_selfie]], 422);

        $url_front = $url_front ?: ($existing['doc_front_url'] ?? null);
        $url_back = $url_back ?: ($existing['doc_back_url'] ?? null);
        $url_selfie = $url_selfie ?: ($existing['selfie_url'] ?? null);

        if (empty($url_front) || empty($url_back) || empty($url_selfie)) {
            return $this->_json([
                'status' => 'error',
                'message' => 'Please upload the Front image, Back image and a Selfie with ID.',
            ], 422);
        }

        $payload = [
            'country_iso2'      => $country,
            'full_name'         => $full_name,
            'dob'               => $dob,
            'gender'            => $gender,
            'nationality_iso2'  => $nationality,
            'addr_line1'        => $addr1,
            'addr_line2'        => $addr2 ?: null,
            'addr_city'         => $city,
            'addr_region'       => $region,
            'addr_postal'       => $postal,
            'doc_issue_country' => $issue_country,
            'doc_issue_date'    => $issue_date,
            'doc_expiry_date'   => $expiry_date,
            'doc_type'          => $doc_type,
            'doc_number'        => $doc_number,
            'doc_front_url'     => $url_front,
            'doc_back_url'      => $url_back,
            'selfie_url'        => $url_selfie,
            'consent'           => 1,
            'status'            => 'pending',
            'review_notes'      => null,
            'rejection_code'    => null,
        ];

        $kyc_id = $this->kyc->createOrUpdate($uid, $payload);

        // NEW: log the state-machine transition (NOT_SUBMITTED|RESUBMIT_REQUIRED -> PENDING).
        $this->kyc->addAudit($kyc_id, $uid, 'submit',
            $curState . ' -> ' . Kyc_model::S_PENDING . ' (by user)');

        $this->users->setKycStatus($uid, 'pending');
        $this->db->where('id', $uid)->update('users', ['kyc_last_submitted_at' => date('Y-m-d H:i:s')]);

        return $this->_json([
            'status' => 'success',
            'message' => 'KYC submitted successfully. Verification is under review.',
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

    private function _validateUploadOrKeep($field, $existingUrl, $message, array $exts, $maxMb, array &$errors)
    {
        if (!empty($existingUrl) && empty($_FILES[$field]['name'])) {
            return true;
        }
        if (empty($_FILES[$field]['name'])) {
            $errors[$field] = $message;
            return false;
        }
        $name = strtolower((string) $_FILES[$field]['name']);
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $mime = strtolower((string) ($_FILES[$field]['type'] ?? ''));
        if (!in_array($ext, $exts, true)) {
            $errors[$field] = $message;
            return false;
        }
        if ($mime && $field === 'selfie' && !in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            $errors[$field] = $message;
            return false;
        }
        if ($mime && $field !== 'selfie' && !in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true)) {
            $errors[$field] = $message;
            return false;
        }
        if (((int) ($_FILES[$field]['size'] ?? 0)) > ($maxMb * 1024 * 1024)) {
            $errors[$field] = $message;
            return false;
        }
        return true;
    }

    private function _ageYears($dob)
    {
        $ts = strtotime($dob);
        if (!$ts) return 0;
        $dobDt = new DateTime(date('Y-m-d', $ts));
        $now = new DateTime(date('Y-m-d'));
        return (int) $dobDt->diff($now)->y;
    }

    private function _json($payload = [], $code = 200)
    {
        $this->output->set_status_header($code)->set_content_type('application/json')->set_output(json_encode($payload));
    }
}
