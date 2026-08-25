<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin ▸ All Transactions
 * One page over every money movement: internal transfers, on-chain deposits/
 * withdrawals, staking purchases, ROI distribution, binary matching bonus, and
 * the instant staking bonus — all already unified in wallet_ledger (each row
 * enriched with its on-chain mirror) via WalletTracker_model. A second tab
 * shows the Cron Run Log (CronLog_model) for the scheduled jobs behind ROI
 * distribution and matching-bonus payouts.
 */
class AllTransactions extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper(['url', 'security']);
        $this->load->model('Admin_model');
        $this->load->model('WalletTracker_model', 'tracker');
        $this->load->model('CronLog_model', 'cronlog');

        if (!$this->session->userdata('admin_logged_in')) {
            redirect('aaddmmiinn/login');
        }
        $user = $this->Admin_model->get_user($this->session->userdata('admin_userid'));
        if ($user && $user->admin_roll == '1') {
            $permissions = json_decode($user->permission_pages, true);
            if (empty($permissions['wallet_management'])) {
                $this->session->set_flashdata('error', 'Access Denied: You do not have permission.');
                redirect('admin');
            }
        }
    }

    private function _json($data = [], $code = 200)
    {
        $this->output->set_status_header($code)
                     ->set_content_type('application/json')
                     ->set_output(json_encode($data));
    }

    /* ------------------------------- page ------------------------------- */
    public function index()
    {
        $data['title'] = 'All Transactions';
        $this->load->view('admin/all_transactions', $data);
    }

    /** Catalog of reference types + categories for the filter dropdowns. */
    public function options()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $this->_json([
            'status'          => true,
            'reference_types' => $this->tracker->reference_types(),
            'categories'      => $this->tracker->categories(),
            'wallets'         => ['usdt', 'exchange', 'earning', 'staking', 'bonus'],
            'explorer_url'    => $this->_explorer(),
        ]);
    }

    /** Active chain's block explorer base URL (e.g. https://bscscan.com), same convention as Onchaintx.php. */
    private function _explorer()
    {
        $ts = $this->db->select('explorer_url')->get_where('token_settings', ['status' => 1])->row_array();
        return rtrim($ts['explorer_url'] ?? 'https://bscscan.com', '/');
    }

    private function _filters()
    {
        $get = function ($k) { return $this->input->get($k, true); };
        return array_filter([
            'user_id'        => $get('user_id'),
            'wallet_type'    => $get('wallet_type'),
            'reference_type' => $get('reference_type'),
            'category'       => $get('category'),
            'direction'      => $get('direction'),
            'date_from'      => $get('date_from'),
            'date_to'        => $get('date_to'),
            'search'         => $get('search'),
            'tx_hash'        => $get('tx_hash'),
        ], function ($v) { return $v !== '' && $v !== null; });
    }

    /** Paginated, filterable list of every wallet_ledger movement. */
    public function list()
    {
        if (!$this->input->is_ajax_request()) show_404();

        $filters = $this->_filters();
        $page    = max(1, (int) $this->input->get('page', true) ?: 1);
        $limit   = min(200, max(1, (int) ($this->input->get('limit', true) ?: 50)));
        $offset  = ($page - 1) * $limit;

        $rows = $this->tracker->list_transactions($filters, $limit, $offset);

        $userIds = array_values(array_unique(array_map(function ($r) { return (int) $r['user_id']; }, $rows)));
        $users = [];
        if ($userIds) {
            $this->db->select('id, username, email')->where_in('id', $userIds);
            foreach ($this->db->get('users')->result_array() as $u) {
                $users[(int) $u['id']] = $u;
            }
        }

        foreach ($rows as &$r) {
            $r['avatar']   = user_profile_image($r['user_id']);
            $r['username'] = $users[(int) $r['user_id']]['username'] ?? null;
            $r['email']    = $users[(int) $r['user_id']]['email'] ?? null;
        }
        unset($r);

        $this->_json([
            'status' => true,
            'total'  => $this->tracker->count_transactions($filters),
            'page'   => $page,
            'limit'  => $limit,
            'rows'   => $rows,
        ]);
    }

    /** Single ledger row with full source record + on-chain mirror. */
    public function detail()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $id = (int) $this->input->get('id', true);
        if (!$id) return $this->_json(['status' => false, 'message' => 'id required'], 400);

        $row = $this->tracker->transaction_detail($id);
        if (!$row) return $this->_json(['status' => false, 'message' => 'Not found'], 404);

        $row['avatar'] = user_profile_image($row['user_id']);
        $row['user'] = $this->db
            ->select('id, username, name, first_name, last_name, email, referral_id')
            ->get_where('users', ['id' => (int)$row['user_id']])
            ->row_array() ?: null;

        // The other leg of an internal transfer (e.g. wallet_transfer) — identify
        // who received/sent it, not just the raw user_id.
        if (!empty($row['related_ledger'])) {
            $relIds = array_values(array_unique(array_map(function ($r) { return (int) $r['user_id']; }, $row['related_ledger'])));
            $relUsers = [];
            if ($relIds) {
                $this->db->select('id, username, email')->where_in('id', $relIds);
                foreach ($this->db->get('users')->result_array() as $u) {
                    $relUsers[(int) $u['id']] = $u;
                }
            }
            foreach ($row['related_ledger'] as &$rl) {
                $rl['username'] = $relUsers[(int) $rl['user_id']]['username'] ?? null;
                $rl['email']    = $relUsers[(int) $rl['user_id']]['email'] ?? null;
            }
            unset($rl);
        }

        $this->_json(['status' => true, 'row' => $row, 'explorer_url' => $this->_explorer()]);
    }

    /** Cron Run Log tab — every scheduled-job execution, most recent first. */
    public function cron_log()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $limit  = min(300, max(1, (int) ($this->input->get('limit', true) ?: 50)));
        $name   = $this->input->get('cron_name', true) ?: null;
        $page   = max(1, (int) $this->input->get('page', true) ?: 1);
        $offset = ($page - 1) * $limit;

        $this->_json([
            'status' => true,
            'total'  => $this->cronlog->count_recent($name),
            'page'   => $page,
            'limit'  => $limit,
            'rows'   => $this->cronlog->recent($limit, $name, $offset),
        ]);
    }

    /**
     * Export the current (filtered) transaction list as CSV / Excel / PDF.
     *   csv   — real CSV
     *   excel — an Excel-readable HTML table sent as .xls (no PhpSpreadsheet
     *           in this project; Excel opens this natively)
     *   pdf   — a print-ready HTML page; use the browser print dialog to save
     *           as PDF (no PDF library is vendored here)
     * Same convention as admin/staking/Rankmanagement.php::export().
     */
    public function export($format = 'csv')
    {
        $filters = $this->_filters();
        $rows = $this->tracker->list_transactions($filters, 10000, 0);
        foreach ($rows as &$r) {
            $r['onchain_status'] = $r['onchain']['status'] ?? '';
            $r['gas_fee_total']  = $r['onchain']['gas_fee_total'] ?? '';
            $r['signed_amount']  = ($r['direction'] === 'credit' ? '+' : '-') . $r['amount'];
        }
        unset($r);

        $cols = [
            'created_at'     => 'When',
            'user_id'        => 'User ID',
            'type_label'     => 'Type',
            'signed_amount'  => 'Amount',
            'balance_after'  => 'Balance After',
            'description'    => 'Description',
            'tx_hash'        => 'Tx Hash',
            'onchain_status' => 'Chain Status',
            'gas_fee_total'  => 'Gas Fee (BNB)',
        ];
        $label = 'All Transactions';
        $stamp = date('Y-m-d_His');

        if ($format === 'csv') {
            $out = fopen('php://temp', 'r+');
            fputcsv($out, array_values($cols));
            foreach ($rows as $r) {
                $line = [];
                foreach (array_keys($cols) as $c) $line[] = isset($r[$c]) ? $r[$c] : '';
                fputcsv($out, $line);
            }
            rewind($out);
            $csv = stream_get_contents($out);
            fclose($out);
            return force_download($this->_slug($label) . '_' . $stamp . '.csv', $csv);
        }

        if ($format === 'excel') {
            $html = $this->load->view('admin/all_transactions_export',
                        ['rows' => $rows, 'cols' => $cols, 'label' => $label, 'print' => false], true);
            return force_download($this->_slug($label) . '_' . $stamp . '.xls', $html);
        }

        if ($format === 'pdf') {
            return $this->load->view('admin/all_transactions_export',
                        ['rows' => $rows, 'cols' => $cols, 'label' => $label, 'print' => true]);
        }

        show_404();
    }

    private function _slug($s)
    {
        return strtolower(preg_replace('/[^a-z0-9]+/i', '_', trim($s)));
    }

    /**
     * Live BSC gas price from the active RPC in token_settings.
     * Returns { available, gwei, slow, standard, fast, wei }
     * Never throws — errors logged only, so they never interrupt transactions.
     */
    public function live_gas()
    {
        if (!$this->input->is_ajax_request()) show_404();
        try {
            $this->load->model('GasFeeTx_model', 'gastx');
            $data = $this->gastx->liveGasPrice();
        } catch (Throwable $e) {
            log_message('error', '[AllTransactions::live_gas] ' . $e->getMessage());
            $data = ['available' => false, 'reason' => 'Exception'];
        }
        $this->_json(['status' => true, 'data' => $data]);
    }

    /**
     * Gas summary stats (today / month / avg gwei / failed) — used by the
     * gas summary card on the All Transactions page.
     */
    public function gas_summary()
    {
        if (!$this->input->is_ajax_request()) show_404();
        try {
            $this->load->model('GasFeeTx_model', 'gastx');
            $data = $this->gastx->gasStats();
        } catch (Throwable $e) {
            log_message('error', '[AllTransactions::gas_summary] ' . $e->getMessage());
            $data = [];
        }
        $this->_json(['status' => true, 'data' => $data]);
    }
}
