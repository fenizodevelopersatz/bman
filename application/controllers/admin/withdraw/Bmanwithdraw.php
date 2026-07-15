<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bmanwithdraw extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library(['session', 'form_validation']);
        $this->load->helper(['url']);
        $this->load->model('Admin_model');
        $this->load->model('withdraw/Bmanwithdraw_model', 'bmanwithdraw');

        if (!$this->session->userdata('admin_logged_in')) {
            redirect('admin/login');
        }
    }

    public function index()
    {
        $filters = [
            'q' => $this->input->get('q', true),
            'status' => $this->input->get('status', true),
            'source_wallet' => $this->input->get('source_wallet', true),
        ];
        $this->data['title'] = 'BMAN Withdrawal Requests';
        $this->data['card_tilte'] = 'Manual BMAN Withdrawals';
        $this->data['rows'] = $this->bmanwithdraw->admin_history($filters, 100, 0);
        $this->data['filters'] = $filters;
        $this->load->view('admin/withdraw/bman_list', $this->data);
    }

    public function view($id)
    {
        $this->data['title'] = 'BMAN Withdrawal Review';
        $this->data['card_tilte'] = 'Review Withdrawal';
        $this->data['row'] = $this->bmanwithdraw->get_request((int) $id);
        if (empty($this->data['row'])) show_404();
        $this->load->view('admin/withdraw/bman_view', $this->data);
    }

    public function update($id)
    {
        $id = (int) $id;
        $row = $this->bmanwithdraw->get_request($id);
        if (!$row) show_404();

        $status = strtolower(trim((string) $this->input->post('status', true)));
        $tx_hash = trim((string) $this->input->post('tx_hash', true));
        $admin_remark = trim((string) $this->input->post('admin_remark', true));
        $admin_id = (int) $this->session->userdata('admin_userid');
        $new_status = $row['status'];

        $this->db->trans_start();
        if ($status === 'approved' && !empty($tx_hash)) {
            $new_status = 'completed';
            $wallet_col = $row['source_wallet'] . '_balance';
            if ($this->db->table_exists('user_wallets')) {
                $wallet_row = $this->db->select($wallet_col)->get_where('user_wallets', ['user_id' => $row['user_id']])->row();
                $current = (float) ($wallet_row->{$wallet_col} ?? 0);
                $after = max(0, $current - (float) $row['request_amount']);
                $this->db->where('user_id', $row['user_id'])->update('user_wallets', [$wallet_col => $after]);
                if ($this->db->table_exists('wallet_ledger')) {
                    $this->db->insert('wallet_ledger', [
                        'user_id' => $row['user_id'],
                        'wallet_type' => $row['source_wallet'],
                        'credit' => 0,
                        'debit' => $row['request_amount'],
                        'balance_after' => $after,
                        'reference_type' => 'withdrawal',
                        'reference_id' => (string) $id,
                        'description' => 'Manual BMAN withdrawal',
                        'tx_hash' => $tx_hash,
                        'created_by' => $admin_id,
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            }
            $this->db->where('id', $id)->update('bman_withdraw_requests', [
                'status' => 'completed',
                'tx_hash' => $tx_hash,
                'admin_remark' => $admin_remark,
                'completed_at' => date('Y-m-d H:i:s'),
            ]);
            $this->db->insert('onchain_transactions', [
                'tx_hash' => $tx_hash,
                'network' => 'bsc',
                'chain_id' => 56,
                'wallet_type' => $row['source_wallet'],
                'tx_type' => 'withdrawal',
                'status' => 'confirmed',
                'to_address' => $row['withdraw_address'],
                'user_id' => $row['user_id'],
                'admin_id' => $admin_id,
                'token_symbol' => 'BMAN',
                'amount' => $row['net_amount'],
                'reference_type' => 'bman_withdrawal',
                'reference_id' => (string) $id,
                'linked_withdrawal_id' => $id,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } elseif ($status === 'rejected') {
            $new_status = 'rejected';
            $this->db->where('id', $id)->update('bman_withdraw_requests', [
                'status' => 'rejected',
                'admin_remark' => $admin_remark,
            ]);
            $this->db->where('request_id', $id)->update('wallet_withdraw_holds', [
                'status' => 'released',
                'released_amount' => $row['request_amount'],
                'released_at' => date('Y-m-d H:i:s'),
            ]);
        } else {
            $this->db->where('id', $id)->update('bman_withdraw_requests', [
                'status' => $status ?: $row['status'],
                'admin_remark' => $admin_remark,
            ]);
            $new_status = $status ?: $row['status'];
        }
        $this->bmanwithdraw->log_action($id, $admin_id, 'admin_update', $row['status'], $new_status, $admin_remark);
        $this->db->trans_complete();

        redirect('admin/bman-withdrawals/view/' . $id);
    }
}
