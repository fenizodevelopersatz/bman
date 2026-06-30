<?php defined('BASEPATH') OR exit('No direct script access allowed');

class AdminBankVerification extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        // TODO: protect with your admin auth check
        // if (!$this->session->userdata('is_admin')) redirect('admin/login');        
        $this->load->database();
        $this->load->helper(['url', 'security']);
        $this->load->model('Bank_model', 'bank');
    }

    /* ---------- helpers ---------- */
    private function _json($data = [], $code = 200)
    {
        $this->output->set_status_header($code)
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }

    private function _csrf()
    {
        return [
            'name' => $this->security->get_csrf_token_name(),
            'hash' => $this->security->get_csrf_hash(),
        ];
    }

    private function _adminId()
    {
        $id = (int) $this->session->userdata('user_id');
        if (!$id)
            $id = (int) $this->session->userdata('userid');
        return $id;
    }

    /* ---------- page ---------- */
    public function index()
    {
        $data['title'] = 'Bank Verification Requests';
        $data['card_tilte'] = 'Bank Verification Requests';
        $this->load->view('admin/bank/bank_list', $data);
    }

    /* ---------- datatable source ---------- */
    public function list()
    {
        // statuses in your table: pending, approved (you can also add rejected/under_review)
        $rows = $this->bank->getQueue(['pending', 'under_review', 'resubmitted']);

        $data = [];
        foreach ($rows as $r) {
            $badge = $this->_statusBadge($r['status']);

            $userCol = '#' . $r['user_id'] . ' — ' . html_escape($r['username'] ?: $r['email']);

            $bankText = '';
            if (!empty($r['upi_id'])) {
                $bankText .= 'UPI: <b>' . html_escape($r['upi_id']) . '</b><br>';
            }
            $bankText .= 'A/C: <b>' . html_escape($this->_maskAccount($r['account_number'])) . '</b><br>';
            $bankText .= 'IFSC: <b>' . html_escape($r['ifsc']) . '</b><br>';
            $bankText .= 'Bank: <b>' . html_escape($r['bank_name']) . '</b>';

            $summary = '
                <div>
                  ' . $badge . ' • Submitted: ' . html_escape($r['submitted_at'] ?: $r['created_at']) . '
                  <div class="text-muted" style="font-size:12px;margin-top:4px;">' . $bankText . '</div>
                </div>
            ';

            $act = '
              <button type="button" class="btn btn-sm btn-primary btn-view" data-id="' . $r['id'] . '">Review</button>
            ';

            $data[] = [
                $r['id'],
                $userCol,
                html_escape($r['email']),
                $summary,
                $act
            ];
        }

        return $this->_json([
            'data' => $data,
            'csrf' => $this->_csrf(),
        ]);
    }

    /* ---------- single request (preview) ---------- */
    public function show($id)
    {
        if (!$this->input->is_ajax_request())
            show_404();

        $row = $this->bank->getById((int) $id);
        if (!$row)
            return $this->_json(['status' => 'error', 'message' => 'Not found', 'csrf' => $this->_csrf()], 404);

        return $this->_json([
            'status' => 'success',
            'bank' => $row,
            'csrf' => $this->_csrf(),
        ]);
    }

    /* ---------- approve/reject/under_review ---------- */
    public function decision($id)
    {
        // if (!$this->input->is_ajax_request())
        //     show_404();

        $status = $this->input->post('status', true);  // approved|rejected|under_review|pending
        $notes = $this->input->post('notes', true);

        if (!in_array($status, ['approved', 'rejected', 'under_review', 'pending'], true)) {
            return $this->_json(['status' => 'error', 'message' => 'Invalid status', 'csrf' => $this->_csrf()], 422);
        }

        $ok = $this->bank->setStatus(
            (int) $id,
            $status,
            $this->_adminId(),
            $notes ?: null
        );

        if (!$ok)
            return $this->_json(['status' => 'error', 'message' => 'Update failed', 'csrf' => $this->_csrf()], 500);

        return $this->_json(['status' => 'success', 'message' => 'Decision saved', 'csrf' => $this->_csrf()]);
    }

    /* ---------- export CSV (current queue) ---------- */
    public function export_csv()
    {
        $rows = $this->bank->getQueue(['pending', 'under_review', 'resubmitted']);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=bank_queue_' . date('Ymd_His') . '.csv');

        $out = fopen('php://output', 'w');
        fputcsv($out, [
            'ID',
            'User ID',
            'Email',
            'Holder',
            'Bank',
            'Account',
            'IFSC',
            'UPI',
            'Status',
            'Submitted',
            'Note'
        ]);

        foreach ($rows as $r) {
            fputcsv($out, [
                $r['id'],
                $r['user_id'],
                $r['email'],
                $r['holder_name'],
                $r['bank_name'],
                $r['account_number'],
                $r['ifsc'],
                $r['upi_id'],
                $r['status'],
                $r['submitted_at'] ?: $r['created_at'],
                $r['note'],
            ]);
        }

        fclose($out);
        exit;
    }

    /* ---------- view helpers ---------- */
    private function _statusBadge($status)
    {
        $map = [
            'approved' => 'badge-light-success',
            'rejected' => 'badge-light-danger',
            'under_review' => 'badge-light-warning',
            'resubmitted' => 'badge-light-primary',
            'pending' => 'badge-light-info',
        ];
        $cls = $map[$status] ?? 'badge-light';
        return '<span class="badge ' . $cls . '">' . strtoupper($status) . '</span>';
    }

    private function _maskAccount($acc)
    {
        $acc = (string) $acc;
        $len = strlen($acc);
        if ($len <= 4)
            return $acc;
        return str_repeat('•', max(0, $len - 4)) . substr($acc, -4);
    }
}
