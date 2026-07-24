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

        if (!$this->session->userdata('admin_logged_in')) {
            redirect('admin/login');
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

    /* ----------------------------- page ------------------------------ */

    public function index()
    {
        $data['title']   = 'Gas Fee Transactions';
        $data['options'] = $this->gas->filterOptions();
        $data['stats']   = $this->gas->gasStats();
        $this->load->view('admin/wallet/gas_fee_transactions', $data);
    }

    /* ----------------------------- AJAX ------------------------------ */

    /** Paginated list of gas-fee-carrying transactions. */
    public function list()
    {
        if (!$this->input->is_ajax_request()) show_404();

        $f = [
            'user_id'   => $this->input->post('user_id', true),
            'network'   => $this->input->post('network', true),
            'status'    => $this->input->post('status', true),
            'tx_type'   => $this->input->post('tx_type', true),
            'tx_hash'   => $this->input->post('tx_hash', true),
            'date_from' => $this->input->post('date_from', true),
            'date_to'   => $this->input->post('date_to', true),
            'gas_min'   => $this->input->post('gas_min', true),
            'gas_max'   => $this->input->post('gas_max', true),
            'search'    => $this->input->post('search', true),
        ];
        $page  = max(1, (int)$this->input->post('page') ?: 1);
        $limit = min(200, max(10, (int)$this->input->post('limit') ?: 50));
        $offset = ($page - 1) * $limit;

        $total = $this->gas->count($f);
        $rows  = $this->gas->list($f, $limit, $offset);

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
            'user_id'   => $this->input->get('user_id', true),
            'network'   => $this->input->get('network', true),
            'status'    => $this->input->get('status', true),
            'tx_hash'   => $this->input->get('tx_hash', true),
            'date_from' => $this->input->get('date_from', true),
            'date_to'   => $this->input->get('date_to', true),
            'search'    => $this->input->get('search', true),
        ];

        $rows = $this->gas->exportList($f);
        $stamp = date('Y-m-d_His');

        $cols = [
            'created_at'    => 'Date',
            'user_id'       => 'User ID',
            'username'      => 'Username',
            'tx_hash'       => 'Tx Hash',
            'network'       => 'Network',
            'tx_type'       => 'Type',
            'gas_used'      => 'Gas Used',
            'gas_price_gwei'=> 'Gas Price (Gwei)',
            'gas_fee_total' => 'Gas Fee (BNB)',
            'status'        => 'Status',
            'block_number'  => 'Block',
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
