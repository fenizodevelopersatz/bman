<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin ▸ Finance ▸ On-Chain Transactions
 * Live wallet balances + a server-side, filterable history of every
 * blockchain-facing transaction, with a rich per-tx detail modal (stored fields
 * + live RPC enrichment). Backed by Onchaintx_model + `onchain_transactions`.
 *
 * See docs/13_ONCHAIN_TRANSACTIONS.md.
 */
class Onchaintx extends CI_Controller
{
    private $is_super = false;

    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper(['url']);
        $this->load->model('Admin_model');
        $this->load->model('Onchaintx_model', 'tx');

        if (!$this->session->userdata('admin_logged_in')) redirect('aaddmmiinn/login');
        $user = $this->Admin_model->get_user($this->session->userdata('admin_userid'));
        if ($user && $user->admin_roll == '1') {
            $perm = json_decode($user->permission_pages, true);
            if (empty($perm['wallet_management'])) {
                $this->session->set_flashdata('error', 'Access Denied: You do not have permission.');
                redirect('admin');
            }
        }
        $this->is_super = ($user && $user->admin_roll == '1');
    }

    private function _json($data, $code = 200)
    {
        $this->output->set_status_header($code)
                     ->set_content_type('application/json')
                     ->set_output(json_encode($data));
    }

    private function _explorer()
    {
        $ts = $this->db->select('explorer_url')->get_where('token_settings', ['status' => 1])->row_array();
        return rtrim($ts['explorer_url'] ?? 'https://bscscan.com', '/');
    }

    /* -------------------------------- page ------------------------------- */
    public function index()
    {
        $data['title']        = 'On-Chain Transactions';
        $data['card_tilte']   = 'On-Chain Wallet Transactions';
        $data['is_super']     = $this->is_super;
        $data['balances']     = $this->tx->walletTotals();
        $data['options']      = $this->tx->filterOptions();
        $data['explorer_url'] = $this->_explorer();
        $this->load->view('admin/wallet/onchain_transactions', $data);
    }

    /* --------------------- AJAX: server-side grid page ------------------- */
    public function list()
    {
        if (!$this->input->is_ajax_request()) show_404();

        $f = [
            'wallet'         => $this->input->post('wallet', true),
            'network'        => $this->input->post('network', true),
            'status'         => $this->input->post('status', true),
            'tx_type'        => $this->input->post('tx_type', true),
            'token'          => $this->input->post('token', true),
            'user_id'        => $this->input->post('user_id', true),
            'block_number'   => $this->input->post('block_number', true),
            'tx_hash'        => $this->input->post('tx_hash', true),
            'wallet_address' => $this->input->post('wallet_address', true),
            'reference_id'   => $this->input->post('reference_id', true),
            'date_from'      => $this->input->post('date_from', true),
            'date_to'        => $this->input->post('date_to', true),
            'gas_min'        => $this->input->post('gas_min', true),
            'gas_max'        => $this->input->post('gas_max', true),
            'search'         => $this->input->post('search', true),
        ];

        $page   = max(1, (int)$this->input->post('page'));
        $limit  = min(200, max(5, (int)$this->input->post('limit') ?: 25));
        $offset = ($page - 1) * $limit;
        $sort   = $this->input->post('sort', true) ?: 'created_at';
        $dir    = $this->input->post('dir', true) ?: 'DESC';

        $total = $this->tx->count($f);
        $rows  = $this->tx->filter($f, $limit, $offset, $sort, $dir);

        return $this->_json([
            'status'      => 'success',
            'rows'        => $rows,
            'total'       => $total,
            'page'        => $page,
            'limit'       => $limit,
            'pages'       => (int)ceil($total / $limit),
            'explorer'    => $this->_explorer(),
        ]);
    }

    /* ---------------- AJAX: full detail (stored + live chain) ------------ */
    public function detail()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $id  = (int)$this->input->get('id');
        $row = $this->tx->get($id);
        if (!$row) return $this->_json(['status' => 'error', 'message' => 'Not found'], 404);

        $chain = $this->tx->enrichFromChain($row['tx_hash'] ?? null);

        return $this->_json([
            'status'   => 'success',
            'tx'       => $row,
            'chain'    => $chain,
            'explorer' => $this->_explorer(),
        ]);
    }

    /* --------------------- download a simple receipt -------------------- */
    public function receipt($id)
    {
        $row = $this->tx->get((int)$id);
        if (!$row) show_404();
        $explorer = $this->_explorer();

        $lines = [];
        $lines[] = 'ON-CHAIN TRANSACTION RECEIPT';
        $lines[] = str_repeat('=', 48);
        foreach ([
            'Tx Hash' => $row['tx_hash'], 'Network' => $row['network'], 'Status' => $row['status'],
            'Type' => $row['tx_type'], 'Wallet' => $row['wallet_type'],
            'From' => $row['from_address'], 'To' => $row['to_address'],
            'User' => $row['username'] ? ('#'.$row['user_id'].' '.$row['username']) : $row['user_id'],
            'Token' => $row['token_symbol'], 'Amount' => $row['amount'],
            'Block' => $row['block_number'], 'Confirmations' => $row['confirmation_count'],
            'Gas Fee (BNB)' => $row['gas_fee_total'], 'Created' => $row['created_at'],
        ] as $k => $v) {
            $lines[] = str_pad($k, 18) . ': ' . ($v ?? '-');
        }
        if (!empty($row['tx_hash'])) $lines[] = 'Explorer          : ' . $explorer . '/tx/' . $row['tx_hash'];

        $this->output
            ->set_content_type('text/plain')
            ->set_header('Content-Disposition: attachment; filename="tx-receipt-'.(int)$id.'.txt"')
            ->set_output(implode(PHP_EOL, $lines) . PHP_EOL);
    }
}
