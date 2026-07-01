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
        // pending + under_review (+ resubmitted if you want)
        $rows = $this->db->select('k.*, u.email, u.name, u.username, u.id AS uid')
                         ->from('kyc_applications k')
                         ->join('users u','u.id = k.user_id','left')
                         ->where_in('k.status', ['pending','under_review','resubmitted'])
                         ->order_by('k.created_at','DESC')
                         ->get()->result_array();

        $data = [];
        foreach ($rows as $r) {
            $badge = $this->_statusBadge($r['status']);
            $userCol = '#'.$r['uid'].' — '.html_escape($r['name'] ?: $r['username']);
            $summary = '<div>'.$badge.' Doc: '.html_escape(ucwords(str_replace('_',' ',$r['doc_type'])))
                     .' • Country: '.html_escape($r['country_iso2'])
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

    /* ---------- single KYC (preview) ---------- */
    public function show($id) {
        if (!$this->input->is_ajax_request()) show_404();

        $kyc = $this->db->get_where('kyc_applications', ['id' => (int)$id])->row_array();
        if (!$kyc) return $this->_json(['status'=>'error','message'=>'Not found'],404);

        $user = $this->db->get_where('users', ['id' => (int)$kyc['user_id']])->row_array();

        return $this->_json([
            'status' => 'success',
            'kyc'    => $kyc,
            'user'   => [
                'id'       => (int)$user['id'],
                'email'    => $user['email'] ?? '',
                'name'     => $user['name'] ?? '',
                'username' => $user['username'] ?? '',
                'kyc_status' => $user['kyc_status'] ?? '',
            ],
            'csrf' => $this->_csrf(),
        ]);
    }

    /* ---------- approve/reject/under_review ---------- */
    public function decision($id) {
        if (!$this->input->is_ajax_request()) show_404();

        $status = $this->input->post('status', true); // approved|rejected|under_review
        $notes  = $this->input->post('notes', true);
        $rej    = $this->input->post('rejection_code', true);

        if (!in_array($status, ['approved','rejected','under_review'], true)) {
            return $this->_json(['status'=>'error','message'=>'Invalid status','csrf'=>$this->_csrf()], 422);
        }

        // update kyc_applications via model
        $ok = $this->kyc->setStatus(
            (int)$id,
            $status,
            $this->_adminId(),
            $notes ?: null,
            $rej ?: null
        );
        if (!$ok) return $this->_json(['status'=>'error','message'=>'Update failed','csrf'=>$this->_csrf()], 500);

        // sync users table
        $kyc = $this->db->get_where('kyc_applications', ['id' => (int)$id])->row_array();
        if ($kyc) $this->users->setKycStatus((int)$kyc['user_id'], $status);

        return $this->_json(['status'=>'success','message'=>'Decision saved','csrf'=>$this->_csrf()]);
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
