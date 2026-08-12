<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin ▸ Finance ▸ Gas Fee Transactions
 *
 * Dedicated page for viewing all on-chain gas fee data.
 * Backed by GasFeeTx_model + onchain_transactions table.
 */
class Gasfeepage extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper(['url', 'security']);
        $this->load->model('Admin_model');
        $this->load->model('GasFeeTx_model', 'gas');
        $this->load->model('GasFeeSettings_model', 'gasSettings');
        $this->load->model('GasFeeLedger_model', 'gasLedger');
        $this->load->model('WalletTracker_model', 'tracker');

        if (!$this->session->userdata('admin_logged_in')) {
            redirect('aaddmmiinn/login');
        }
        $user = $this->Admin_model->get_user($this->session->userdata('admin_userid'));
        if ($user && $user->admin_roll == '1') {
            $perm = json_decode($user->permission_pages, true);
            if (empty($perm['wallet_management'])) {
                $this->session->set_flashdata('error', 'Access Denied: You do not have permission.');
                redirect('admin');
            }
        }
    }

    private function _json($data, $code = 200)
    {
        $this->output->set_status_header($code)
                     ->set_content_type('application/json')
                     ->set_output(json_encode($data));
    }

    private function _adminId()
    {
        return (int) $this->session->userdata('admin_userid');
    }

    /**
     * Resolve each gas row's reference_type/reference_id back to the real
     * business transaction it belongs to (which order, purchase, ROI payment,
     * etc.) — reuses WalletTracker_model's existing catalog/resolver so the
     * labels match the All Transactions page instead of duplicating them.
     */
    private function _enrichRows(array $rows)
    {
        $catalog = $this->tracker->reference_types();
        foreach ($rows as &$r) {
            $type = $r['reference_type'] ?? '';
            $meta = $catalog[$type] ?? null;
            $r['reference_label'] = $meta['label'] ?? ($type !== '' ? ucfirst(str_replace('_', ' ', $type)) : null);
            $r['source'] = $type !== '' ? $this->tracker->summarize_source($r) : null;
        }
        unset($r);
        return $rows;
    }

    /* ------------------------- Gas Fee Settings (policy) ------------------------- */

    public function settings()
    {
        $data['title']       = 'Gas Fee Settings';
        $data['policies']    = $this->gasSettings->all();
        $data['total_spent'] = $this->gasLedger->totalNativeSpent();
        $this->load->view('admin/wallet/gas_fee_settings', $data);
    }

    public function save_settings()
    {
        if (!$this->input->is_ajax_request()) show_404();

        $gwei = $this->input->post('gas_price_gwei', true);
        $fields = [
            'gas_limit'         => (int) $this->input->post('gas_limit'),
            'gas_price_gwei'    => ($gwei !== null && $gwei !== '') ? (float) $gwei : null,
            'buffer_multiplier' => (float) $this->input->post('buffer_multiplier'),
            'is_active'         => $this->input->post('is_active') ? 1 : 0,
        ];
        $ok = $this->gasSettings->save((int) $this->input->post('id'), $fields, $this->_adminId());
        $this->_json(['status' => (bool) $ok, 'message' => $ok ? 'Saved.' : 'Policy not found.']);
    }

    /** AJAX: recent gas_fee_settings changes (module-scoped slice of the generic admin_settings_audit trail). */
    public function settings_audit()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $rows = $this->db->select('a.*, adm.admin_name')
                          ->from('admin_settings_audit a')
                          ->join('admin_members adm', 'adm.id = a.changed_by', 'left')
                          ->where('a.module', 'gas_fee_settings')
                          ->order_by('a.created_at', 'DESC')->limit(200)
                          ->get()->result_array();
        $this->_json(['status' => true, 'rows' => $rows]);
    }

    /* ----------------------------- page ------------------------------ */

    public function index()
    {
        $data['title']            = 'Gas Fee Transactions';
        $data['options']          = $this->gas->filterOptions();
        $data['stats']            = $this->gas->gasStats();
        $data['reference_labels'] = array_map(function ($m) { return $m['label']; }, $this->tracker->reference_types());
        $this->load->view('admin/wallet/gas_fee_transactions', $data);
    }

    /* ----------------------------- AJAX ------------------------------ */

    /** Paginated list of gas-fee-carrying transactions. */
    public function list()
    {
        if (!$this->input->is_ajax_request()) show_404();

        $f = [
            'user_id'        => $this->input->post('user_id', true),
            'network'        => $this->input->post('network', true),
            'status'         => $this->input->post('status', true),
            'tx_type'        => $this->input->post('tx_type', true),
            'reference_type' => $this->input->post('reference_type', true),
            'has_gas'        => $this->input->post('has_gas', true),
            'tx_hash'        => $this->input->post('tx_hash', true),
            'date_from'      => $this->input->post('date_from', true),
            'date_to'        => $this->input->post('date_to', true),
            'gas_min'        => $this->input->post('gas_min', true),
            'gas_max'        => $this->input->post('gas_max', true),
            'search'         => $this->input->post('search', true),
        ];
        $page  = max(1, (int)$this->input->post('page') ?: 1);
        $limit = min(200, max(10, (int)$this->input->post('limit') ?: 50));
        $offset = ($page - 1) * $limit;

        $total = $this->gas->count($f);
        $rows  = $this->_enrichRows($this->gas->list($f, $limit, $offset));

        $this->_json([
            'status' => true,
            'total'  => $total,
            'page'   => $page,
            'limit'  => $limit,
            'pages'  => (int)ceil($total / $limit),
            'rows'   => $rows,
        ]);
    }

    /** Live BSC gas price from RPC. */
    public function live_gas()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $this->_json(['status' => true, 'data' => $this->gas->liveGasPrice()]);
    }

    /** Gas summary stats for dashboard cards. */
    public function stats()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $this->_json(['status' => true, 'data' => $this->gas->gasStats()]);
    }

    /* ----------------------------- Export ------------------------------ */

    public function export($format = 'csv')
    {
        $f = [
            'user_id'        => $this->input->get('user_id', true),
            'network'        => $this->input->get('network', true),
            'status'         => $this->input->get('status', true),
            'reference_type' => $this->input->get('reference_type', true),
            'has_gas'        => $this->input->get('has_gas', true),
            'tx_hash'        => $this->input->get('tx_hash', true),
            'date_from'      => $this->input->get('date_from', true),
            'date_to'        => $this->input->get('date_to', true),
            'search'         => $this->input->get('search', true),
        ];

        $rows = $this->_enrichRows($this->gas->exportList($f));
        foreach ($rows as &$r) {
            $r['reference_label'] = $r['reference_label'] ?? '';
            $r['source_ref'] = $r['source']['ref'] ?? ($r['source']['request_no'] ?? ($r['source']['run_ref'] ?? ''));
        }
        unset($r);
        $stamp = date('Y-m-d_His');

        $cols = [
            'created_at'      => 'Date',
            'user_id'         => 'User ID',
            'username'        => 'Username',
            'tx_hash'         => 'Tx Hash',
            'network'         => 'Network',
            'tx_type'         => 'Type',
            'reference_label' => 'For (Transaction)',
            'source_ref'      => 'Reference',
            'gas_used'        => 'Gas Used',
            'gas_price_gwei'  => 'Gas Price (Gwei)',
            'gas_fee_total'   => 'Gas Fee (BNB)',
            'status'          => 'Status',
            'block_number'    => 'Block',
        ];

        if ($format === 'csv') {
            $out = fopen('php://temp', 'r+');
            fputcsv($out, array_values($cols));
            foreach ($rows as $r) {
                $line = [];
                foreach (array_keys($cols) as $c) $line[] = $r[$c] ?? '';
                fputcsv($out, $line);
            }
            rewind($out);
            $csv = stream_get_contents($out);
            fclose($out);
            return force_download('gas_fee_transactions_' . $stamp . '.csv', $csv);
        }

        if ($format === 'excel') {
            $html = '<table border="1"><tr>';
            foreach ($cols as $h) $html .= '<th>' . htmlspecialchars($h) . '</th>';
            $html .= '</tr>';
            foreach ($rows as $r) {
                $html .= '<tr>';
                foreach (array_keys($cols) as $c) $html .= '<td>' . htmlspecialchars($r[$c] ?? '') . '</td>';
                $html .= '</tr>';
            }
            $html .= '</table>';
            return force_download('gas_fee_transactions_' . $stamp . '.xls', $html);
        }

        show_404();
    }
}
