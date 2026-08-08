<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin review queue for the public "Contact Us / Account Unlock" form
 * (application/controllers/user/auth/Contact.php). The 'unlock' decision is
 * the only place in the codebase that clears users.account_frozen back to 0
 * — see Common_model::userloginVerify() for where it gets set to 1 and blocks
 * login in the first place.
 */
class ContactRequests extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        if ($this->session->userdata('admin_logged_in') && $this->session->userdata('admin_login')) {
            $this->lang->load('common', $this->session->userdata('language'));
        } else {
            redirect('admin/login');
        }
    }

    private function _json($data = [], $code = 200)
    {
        $this->output->set_status_header($code)
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }

    private function _adminId()
    {
        return (int) $this->session->userdata('admin_userid');
    }

    /* ---------- page ---------- */
    public function index()
    {
        $data['title'] = 'Contact Requests';
        $data['card_tilte'] = 'Contact Requests';
        // Opening the list clears the sidebar "new contact requests" badge.
        $this->load->model('admin/DashboardStats_model', 'dashstats');
        $this->dashstats->markSeen((int) $this->session->userdata('admin_userid'), 'contact');
        $this->load->view('admin/contact/list', $data);
    }

    /* ---------- datatable source ---------- */
    public function list()
    {
        $rows = $this->db
            ->select('cr.*, u.username, u.referral_id, u.account_frozen')
            ->from('contact_requests cr')
            ->join('users u', 'u.id = cr.user_id', 'left')
            ->order_by('cr.status', 'ASC')
            ->order_by('cr.created_at', 'DESC')
            ->get()->result_array();

        $data = [];
        foreach ($rows as $r) {
            $statusBadge = ($r['status'] === 'pending')
                ? '<span class="badge badge-light-warning">Pending</span>'
                : '<span class="badge badge-light-success">Resolved</span>';

            $frozenBadge = '';
            if ($r['user_id']) {
                $frozenBadge = ((int) ($r['account_frozen'] ?? 0) === 1)
                    ? ' <span class="badge badge-light-danger">Frozen</span>'
                    : ' <span class="badge badge-light-success">Active</span>';
            }

            $userCol = $r['user_id']
                ? '#' . $r['user_id'] . ' — ' . html_escape($r['username'] ?: $r['email']) . $frozenBadge
                : '<span class="text-muted">No matching account</span>';

            $messagePreview = html_escape(mb_strlen($r['message']) > 120 ? mb_substr($r['message'], 0, 120) . '…' : $r['message']);

            $attachment = $r['attachment_path']
                ? '<a href="' . base_url('assets/images/contact/' . rawurlencode($r['attachment_path'])) . '" target="_blank">View attachment</a>'
                : '<span class="text-muted">—</span>';

            $act = '<button type="button" class="btn btn-sm btn-primary btn-view" data-id="' . $r['id'] . '">Review</button>';

            $data[] = [
                $r['id'],
                $userCol,
                html_escape($r['email']),
                $messagePreview,
                $attachment,
                $statusBadge,
                html_escape($r['created_at']),
                $act,
            ];
        }

        return $this->_json(['data' => $data]);
    }

    /* ---------- single request detail ---------- */
    public function show($id)
    {
        if (!$this->input->is_ajax_request())
            show_404();

        $row = $this->db
            ->select('cr.*, u.username, u.referral_id, u.account_frozen')
            ->from('contact_requests cr')
            ->join('users u', 'u.id = cr.user_id', 'left')
            ->where('cr.id', (int) $id)
            ->get()->row_array();

        if (!$row)
            return $this->_json(['status' => 'error', 'message' => 'Not found'], 404);

        return $this->_json(['status' => 'success', 'request' => $row]);
    }

    /* ---------- unlock / resolve / reject ---------- */
    public function decision($id)
    {
        $action = $this->input->post('action', true); // unlock|resolve|reject
        $notes = $this->input->post('notes', true);

        if (!in_array($action, ['unlock', 'resolve', 'reject'], true)) {
            return $this->_json(['status' => 'error', 'message' => 'Invalid action'], 422);
        }

        $row = $this->db->get_where('contact_requests', ['id' => (int) $id])->row_array();
        if (!$row) {
            return $this->_json(['status' => 'error', 'message' => 'Not found'], 404);
        }

        if ($action === 'unlock') {
            if (!$row['user_id']) {
                return $this->_json(['status' => 'error', 'message' => 'This request has no matching account to unlock.'], 422);
            }
            $this->db->where('id', (int) $row['user_id'])->update('users', [
                'account_frozen' => 0,
                'account_frozen_at' => null,
            ]);
        }

        $this->db->where('id', (int) $id)->update('contact_requests', [
            'status' => 'resolved',
            'admin_notes' => $notes ?: null,
            'resolved_by' => $this->_adminId(),
            'resolved_at' => date('Y-m-d H:i:s'),
        ]);

        $messages = [
            'unlock' => 'Account unlocked and request marked resolved.',
            'resolve' => 'Request marked resolved.',
            'reject' => 'Request closed.',
        ];

        return $this->_json(['status' => 'success', 'message' => $messages[$action]]);
    }
}
