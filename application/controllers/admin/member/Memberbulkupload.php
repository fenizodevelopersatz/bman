<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin ▸ Members Management ▸ Bulk Upload.
 *
 * Upload one Excel/CSV sheet and create many members from it. Two-phase on
 * purpose: `stage` parses + validates the whole file and shows the admin
 * exactly what will happen, `import` then creates the accounts. Nothing
 * reaches `users` until the admin confirms.
 *
 * The BMAN column is queued, not sent here — see MemberBulkBmanCron.
 *
 * Permission: the same `member_management` gate as Membermanagement, so a
 * sub-admin who can create one member can create many, and one who cannot,
 * cannot.
 */
class Memberbulkupload extends CI_Controller
{
    /** Anything else is rejected before a single byte is parsed. */
    private $allowedExtensions = ['xlsx', 'xlsm', 'csv', 'txt'];
    private $maxUploadBytes = 8388608;   // 8 MB — a 1000-row sheet is ~100 KB

    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper(['url', 'security']);
        $this->load->model('Admin_model');

        if (!$this->session->userdata('admin_logged_in')) {
            redirect('admin/login');
        }

        $user = $this->Admin_model->get_user($this->session->userdata('admin_userid'));
        if ($user && $user->admin_roll == '1') {
            $permissions = json_decode($user->permission_pages, true);
            if (empty($permissions['member_management'])) {
                $this->session->set_flashdata('error', 'Access Denied: You do not have permission.');
                redirect('admin');
            }
        }

        $this->load->model('member/Memberbulkupload_model', 'bulk');
    }

    private function _json($data, $code = 200)
    {
        $this->output->set_status_header($code)
                     ->set_content_type('application/json')
                     ->set_output(json_encode($data));
    }

    private function _adminId()
    {
        return (int)$this->session->userdata('admin_userid');
    }

    /* =============================== pages =============================== */

    public function index()
    {
        $this->load->model('member/Memberbulkbmancron_model', 'bulkbman');

        $data = [
            'title'        => 'Bulk Member Upload',
            'card_tilte'   => 'Create many members from one Excel / CSV sheet',
            'settings'     => $this->bulk->settings(),
            'batches'      => $this->bulk->batches(25),
            'bman_pending' => $this->bulkbman->pendingCount(),
            'cron_state'   => $this->bulkbman->state(),
        ];
        $this->load->view('admin/member/bulk_upload', $data);
    }

    /** Batch detail: every parsed row with its validation / import / BMAN state. */
    public function batch($batchId)
    {
        $batch = $this->bulk->batch($batchId);
        if (!$batch) show_404();

        $data = [
            'title'      => 'Bulk Upload · '.$batch['ref'],
            'card_tilte' => 'Batch detail',
            'batch'      => $batch,
            'rows'       => $this->bulk->rows($batchId),
        ];
        $this->load->view('admin/member/bulk_upload_batch', $data);
    }

    /* =============================== actions ============================= */

    /**
     * Parse + validate the uploaded sheet. Reads straight from PHP's temp path
     * and never moves the file under uploads/ — the sheet holds plaintext
     * passwords, so the less time it exists on disk the better. PHP deletes the
     * temp file when this request ends.
     */
    public function stage()
    {
        if (!$this->input->is_ajax_request()) show_404();
        @set_time_limit(0);   // bcrypting a 1000-row sheet takes real time

        $file = $_FILES['sheet'] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return $this->_json(['status' => 'error', 'message' => 'Choose a file to upload.'], 422);
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return $this->_json(['status' => 'error', 'message' => $this->_uploadError($file['error'])], 422);
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            return $this->_json(['status' => 'error', 'message' => 'That file was not received as an upload.'], 422);
        }
        if ($file['size'] > $this->maxUploadBytes) {
            return $this->_json(['status' => 'error', 'message' => 'That file is larger than '.round($this->maxUploadBytes / 1048576).' MB.'], 422);
        }

        $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $this->allowedExtensions, true)) {
            return $this->_json(['status' => 'error', 'message' => 'Upload an .xlsx or .csv file (got ".'.$ext.'").'], 422);
        }

        $res = $this->bulk->stage($file['tmp_name'], [
            'original_name'    => $file['name'],
            'extension'        => $ext,
            'default_password' => (string)$this->input->post('default_password'),
            'send_bman'        => $this->input->post('send_bman') ? 1 : 0,
            'wallet_type'      => (string)$this->input->post('wallet_type'),
        ], $this->_adminId());

        if (empty($res['ok'])) {
            return $this->_json(['status' => 'error', 'message' => $res['message']], 422);
        }

        return $this->_json([
            'status'   => 'success',
            'message'  => $res['message'],
            'batch_id' => $res['batch_id'],
            'summary'  => $res['summary'],
            'rows'     => $this->bulk->rows($res['batch_id']),
        ]);
    }

    /** Create the accounts for every valid row of a staged batch. */
    public function import()
    {
        if (!$this->input->is_ajax_request()) show_404();
        @set_time_limit(0);

        $res = $this->bulk->import((int)$this->input->post('batch_id'), $this->_adminId());
        if (empty($res['ok'])) {
            return $this->_json(['status' => 'error', 'message' => $res['message'], 'data' => $res], 422);
        }
        return $this->_json(['status' => 'success', 'message' => $res['message'], 'data' => $res]);
    }

    public function cancel()
    {
        if (!$this->input->is_ajax_request()) show_404();
        list($ok, $msg) = $this->bulk->cancel((int)$this->input->post('batch_id'));
        return $this->_json(['status' => $ok ? 'success' : 'error', 'message' => $msg], $ok ? 200 : 422);
    }

    /** Put one failed BMAN transfer back in the cron's queue. */
    public function requeue()
    {
        if (!$this->input->is_ajax_request()) show_404();
        list($ok, $msg) = $this->bulk->requeueBman((int)$this->input->post('row_id'));
        return $this->_json(['status' => $ok ? 'success' : 'error', 'message' => $msg], $ok ? 200 : 422);
    }

    public function updateSettings()
    {
        if (!$this->input->is_ajax_request()) show_404();

        $batchSize = (int)$this->input->post('max_batch_size', true);
        $maxRows   = (int)$this->input->post('max_rows_per_file', true);

        list($ok, $msg) = $this->bulk->updateSettings([
            'enabled'                => $this->input->post('enabled') ? 1 : 0,
            'dry_run'                => $this->input->post('dry_run') ? 1 : 0,
            'credit_exchange_wallet' => $this->input->post('credit_exchange_wallet') ? 1 : 0,
            'wallet_type'            => $this->input->post('wallet_type', true),
            'min_treasury_reserve' => $this->input->post('min_treasury_reserve', true) !== '' ? $this->input->post('min_treasury_reserve', true) : '0',
            'max_batch_size'       => $batchSize > 0 ? min($batchSize, 500) : 20,
            'max_rows_per_file'    => $maxRows > 0 ? min($maxRows, 20000) : 1000,
        ], $this->_adminId());

        return $this->_json(['status' => $ok ? 'success' : 'error', 'message' => $msg]);
    }

    /* ============================== template ============================= */

    /**
     * The starter sheet, as a real .xlsx workbook (add ?format=csv for CSV).
     *
     * The `bman` values are written as NUMBERS so Excel right-aligns them and
     * the admin can type over them arithmetically; everything else is written
     * as text, which is what keeps a referral code like 001234 from losing its
     * leading zeros the moment the file is opened.
     */
    public function template()
    {
        // A real sponsor code from this install, so the example rows are usable
        // as-is instead of pointing at a referral that does not exist.
        $sample = $this->db->select('referral_id')->where('status', '1')
            ->where('referral_id IS NOT NULL', null, false)
            ->order_by('id', 'ASC')->limit(1)->get('users')->row_array();
        $sponsor = $sample['referral_id'] ?? 'NEXMAN100001';

        $rows = [
            Memberbulkupload_model::$templateColumns,
            ['john_doe', 'john@example.com', 'ChangeMe123', $sponsor, 100, 'exchange'],
            ['jane_doe', 'jane@example.com', '',            $sponsor, 250.5, 'earning'],
            ['alex_roy', 'alex@example.com', '',            $sponsor, '', ''],
        ];

        if (strtolower((string)$this->input->get('format', true)) === 'csv') {
            return $this->_csv('member-bulk-upload-template.csv', $rows);
        }

        $this->load->library('sheetwriter');
        try {
            $this->sheetwriter->download($rows, 'member-bulk-upload-template.xlsx', 'Members');
        } catch (Throwable $e) {
            // ext-zip missing: a CSV the admin can still work with beats an error page.
            log_message('error', '[member_bulk_upload] xlsx template failed: '.$e->getMessage());
            return $this->_csv('member-bulk-upload-template.csv', $rows);
        }
    }

    /** Shared CSV emitter (UTF-8 BOM so Excel opens it in the right encoding). */
    private function _csv($filename, array $rows)
    {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="'.str_replace('"', '', $filename).'"');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        $stream = fopen('php://output', 'w');
        fputs($stream, "\xEF\xBB\xBF");
        foreach ($rows as $row) fputcsv($stream, $row);
        fclose($stream);
        exit;
    }

    /**
     * Export one batch's result as .xlsx, including every rejection reason
     * (add ?format=csv for CSV).
     *
     * Row number, member id and ledger id go out as integers and the BMAN
     * amount as a float, so the sheet is sortable and summable. Everything
     * else stays text — a wallet address or tx hash must never be reinterpreted
     * as a number.
     */
    public function export($batchId)
    {
        $batch = $this->bulk->batch($batchId);
        if (!$batch) show_404();

        $rows = [[
            'Row', 'Username', 'Email', 'Reference ID', 'Placed Under', 'Position', 'BMAN', 'Wallet Type', 'Status',
            'Member ID', 'New Referral ID', 'Wallet Address', 'BMAN Status', 'Tx Hash',
            'Exchange Ledger ID', 'Credited At', 'Message',
        ]];
        foreach ($this->bulk->rows($batchId) as $r) {
            $rows[] = [
                (int)$r['row_number'], $r['username'], $r['email'], $r['reference_id'],
                $r['placed_under_referral'] ?: ($r['placed_under'] ? '#'.$r['placed_under'] : ''),
                $r['placed_position'] ? strtoupper($r['placed_position']).($r['placed_type'] === 'auto' ? ' (spillover)' : '') : '',
                (float)$r['bman_amount'],
                $r['wallet_type'] ?: 'exchange',
                strtoupper($r['status']),
                $r['user_id'] !== null ? (int)$r['user_id'] : '',
                $r['referral_id'], $r['wallet_address'],
                strtoupper($r['bman_status']), $r['bman_tx_hash'],
                $r['bman_ledger_id'] !== null ? (int)$r['bman_ledger_id'] : '',
                $r['bman_credited_at'],
                $r['error_message'] ?: $r['bman_error'],
            ];
        }

        if (strtolower((string)$this->input->get('format', true)) === 'csv') {
            return $this->_csv('bulk-upload-'.$batch['ref'].'.csv', $rows);
        }

        $this->load->library('sheetwriter');
        try {
            $this->sheetwriter->download($rows, 'bulk-upload-'.$batch['ref'].'.xlsx', 'Batch '.$batch['ref']);
        } catch (Throwable $e) {
            log_message('error', '[member_bulk_upload] xlsx export failed: '.$e->getMessage());
            return $this->_csv('bulk-upload-'.$batch['ref'].'.csv', $rows);
        }
    }

    private function _uploadError($code)
    {
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:  return 'That file is larger than the server\'s upload limit.';
            case UPLOAD_ERR_PARTIAL:    return 'The upload was interrupted — try again.';
            case UPLOAD_ERR_NO_TMP_DIR: return 'The server has no temp directory configured for uploads.';
            case UPLOAD_ERR_CANT_WRITE: return 'The server could not write the uploaded file to disk.';
            default:                    return 'The upload failed (error code '.(int)$code.').';
        }
    }
}
