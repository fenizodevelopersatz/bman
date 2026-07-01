<?php defined('BASEPATH') OR exit('No direct script access allowed');

class AdminKyc extends CI_Controller
{
    public function __construct() {
        parent::__construct();
        // TODO: gate this controller behind your admin auth/role middleware
        $this->load->model(['Kyc_model' => 'kyc', 'member/Users_model' => 'users']);
        $this->load->database();
        $this->load->helper(['url','security']);
    }

    /* ---------- helpers ---------- */
    private function _json($data = [], $code = 200) {
        $this->output->set_status_header($code)
                     ->set_content_type('application/json')
                     ->set_output(json_encode($data));
    }
    private function _csrf() {
        return [
            'name' => $this->security->get_csrf_token_name(),
            'hash' => $this->security->get_csrf_hash(),
        ];
    }
    private function _adminId() {
        // your app sometimes uses user_id and sometimes userid
        $id = (int)$this->session->userdata('user_id');
        if (!$id) $id = (int)$this->session->userdata('admin_userid');
        return $id;
    }

    /* ---------- page ---------- */
    public function index() {
        $data['title']      = 'Members KYC List';
        $data['card_tilte'] = 'Members KYC List';
        // Table will be filled by AJAX; keep page fast
        $this->load->view('admin/kyc_list', $data);
    }

    /* ---------- datatable source ---------- */
    public function list() {
        // NEW: list ALL requests by default, with optional Status / Document Type filters and a
        // combined search (Name, Email, Phone or Document Number). Empty filters => everything.
        $status  = $this->input->get('status', true);
        $docType = $this->input->get('doc_type', true);
        $q       = trim((string)$this->input->get('q', true));

        $this->db->select('k.*, u.email, u.name, u.username, u.contact AS phone, u.id AS uid')
                 ->from('kyc_applications k')
                 ->join('users u','u.id = k.user_id','left');

        if (in_array($status, ['pending','under_review','resubmitted','approved','rejected'], true)) {
            $this->db->where('k.status', $status);
        }
        if (in_array($docType, ['passport','national_id','driver_license'], true)) {
            $this->db->where('k.doc_type', $docType);
        }
        if ($q !== '') {
            $this->db->group_start()
                     ->like('u.name', $q)
                     ->or_like('u.username', $q)
                     ->or_like('u.email', $q)
                     ->or_like('u.contact', $q)
                     ->or_like('k.doc_number', $q)
                     ->group_end();
        }

        $rows = $this->db->order_by('k.created_at','DESC')->get()->result_array();

        $data = [];
        foreach ($rows as $r) {
            $badge = $this->_statusBadge($r['status']);
            $userCol = '#'.$r['uid'].' — '.html_escape($r['name'] ?: $r['username'])
                     .( $r['phone'] ? ' <span class="text-muted">('.html_escape($r['phone']).')</span>' : '' );
            $summary = '<div>'.$badge.' Doc: '.html_escape($this->_docLabel($r['doc_type']))
                     .' • No: '.html_escape($r['doc_number'])
                     .' • Submitted: '.html_escape($r['created_at']).'</div>';
            $act = '<button type="button" class="btn btn-sm btn-primary btn-view" data-id="'.$r['id'].'">Review</button>';

            $data[] = [
                $userCol,
                html_escape($r['email']),
                $act,
                $summary
            ];
        }
        return $this->_json([
            'data' => $data,
            'csrf' => $this->_csrf(),
        ]);
    }

    // NEW: map internal doc_type enum values to the spec's display labels.
    private function _docLabel($t) {
        $map = ['national_id' => 'Aadhaar Id', 'driver_license' => 'Driving License', 'passport' => 'Passport'];
        return $map[$t] ?? ucwords(str_replace('_',' ', (string)$t));
    }

    /* ---------- single KYC (preview) ---------- */
    public function show($id) {
        if (!$this->input->is_ajax_request()) show_404();

        $kyc = $this->db->get_where('kyc_applications', ['id' => (int)$id])->row_array();
        if (!$kyc) return $this->_json(['status'=>'error','message'=>'Not found'],404);

        $user = $this->db->get_where('users', ['id' => (int)$kyc['user_id']])->row_array();

        // NEW: pull the full status history (reviewer, date, action, remarks) for this application.
        $history = $this->kyc->history((int)$id);

        return $this->_json([
            'status' => 'success',
            'kyc'    => $kyc,
            'user'   => [
                'id'       => (int)$user['id'],
                'email'    => $user['email'] ?? '',
                'name'     => $user['name'] ?? '',
                'username' => $user['username'] ?? '',
                'phone'    => $user['contact'] ?? '',
                'kyc_status' => $user['kyc_status'] ?? '',
            ],
            'history' => $history,
            'csrf' => $this->_csrf(),
        ]);
    }

    /* ---------- approve/reject/under_review ---------- */
    public function decision($id) {
        if (!$this->input->is_ajax_request()) show_404();

        // NEW: action-based transitions validated by the backend state machine.
        // Preferred input: action = start_review | approve | request_resubmission.
        // Backward compatible: legacy status = under_review|approved|rejected is mapped to an action.
        $action = $this->input->post('action', true);
        $notes  = $this->input->post('notes', true); // resubmission reason / remarks

        if (!$action) {
            $legacy = $this->input->post('status', true);
            $map = ['under_review' => 'start_review', 'approved' => 'approve', 'rejected' => 'request_resubmission'];
            $action = isset($map[$legacy]) ? $map[$legacy] : '';
        }

        if (!in_array($action, ['start_review','approve','request_resubmission'], true)) {
            return $this->_json(['status'=>'error','message'=>'Invalid action','csrf'=>$this->_csrf()], 422);
        }

        $adminId = $this->_adminId();

        // Validate + apply the transition, then log it (all inside the model's state machine).
        list($ok, $result, $userId) = array_pad(
            $this->kyc->applyAdminAction((int)$id, $action, $adminId, $notes ?: null), 3, null
        );
        if (!$ok) {
            return $this->_json(['status'=>'error','message'=>$result,'csrf'=>$this->_csrf()], 422);
        }

        // Sync users.kyc_status with the new canonical state.
        if ($userId) $this->users->setKycStatus((int)$userId, $this->kyc->toDb($result));

        return $this->_json(['status'=>'success','message'=>'Status updated to '.$result,'state'=>$result,'csrf'=>$this->_csrf()]);
    }

    /* ---------- export CSV (current queue) ---------- */
    public function export_csv() {
        $rows = $this->db->select('k.*, u.email, u.name, u.username, u.id AS uid')
                         ->from('kyc_applications k')
                         ->join('users u','u.id = k.user_id','left')
                         ->where_in('k.status', ['pending','under_review','resubmitted'])
                         ->order_by('k.created_at','DESC')->get()->result_array();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=kyc_queue_'.date('Ymd_His').'.csv');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['KYC ID','User ID','Name','Username','Email','Status','Country','Doc Type','Submitted','Notes']);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['id'],
                $r['uid'],
                $r['name'],
                $r['username'],
                $r['email'],
                $r['status'],
                $r['country_iso2'],
                $r['doc_type'],
                $r['created_at'],
                $r['review_notes'],
            ]);
        }
        fclose($out);
        exit;
    }

    /* ---------- small view helper ---------- */
    private function _statusBadge($status) {
        $map = [
            'approved'     => 'badge-light-success',
            'rejected'     => 'badge-light-danger',
            'under_review' => 'badge-light-warning',
            'resubmitted'  => 'badge-light-primary',
            'pending'      => 'badge-light-info',
        ];
        $cls = $map[$status] ?? 'badge-light';
        return '<span class="badge '.$cls.'">'.strtoupper($status).'</span>';
    }
}
