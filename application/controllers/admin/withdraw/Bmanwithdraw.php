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
        $this->load->model('Walletledger_model', 'ledger');

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
        $this->load->model('admin/DashboardStats_model', 'dashstats');
        $this->dashstats->markSeen($this->session->userdata('admin_userid'), 'withdrawals');
        $this->load->view('admin/withdraw/bman_list', $this->data);
    }

    public function view($id)
    {
        $this->data['title'] = 'BMAN Withdrawal Review';
        $this->data['card_tilte'] = 'Review Withdrawal';
        $this->data['row'] = $this->bmanwithdraw->get_request((int) $id);
        if (empty($this->data['row'])) show_404();

        // Load allocations for mixed requests
        $this->data['allocations'] = $this->bmanwithdraw->get_allocations((int) $id);

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

        // Validate legal status transitions
        if (!in_array($status, ['approved', 'processing', 'completed', 'rejected', 'failed'], true)) {
            $this->session->set_flashdata('error', 'Invalid status selected');
            redirect('admin/bman-withdrawals/view/' . $id);
            return;
        }

        $this->db->trans_start();

        $result = null;
        if ($status === 'approved') {
            $result = $this->bmanwithdraw->approve($id, $admin_id, $admin_remark);
        } elseif ($status === 'processing') {
            $result = $this->bmanwithdraw->mark_processing($id, $admin_id, $admin_remark);
        } elseif ($status === 'completed') {
            if (empty($tx_hash)) {
                $this->db->trans_rollback();
                $this->session->set_flashdata('error', 'Transaction hash is required to complete the withdrawal');
                redirect('admin/bman-withdrawals/view/' . $id);
                return;
            }
            $result = $this->bmanwithdraw->complete($id, $admin_id, $tx_hash, $admin_remark);

            // Record on-chain transaction if completion succeeded
            if (empty($result['error'])) {
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
            }
        } elseif ($status === 'rejected') {
            $result = $this->bmanwithdraw->reject($id, $admin_id, $admin_remark);
        } elseif ($status === 'failed') {
            $result = $this->bmanwithdraw->mark_failed($id, $admin_id, $admin_remark);
        }

        if (!empty($result['error'])) {
            $this->db->trans_rollback();
            $this->session->set_flashdata('error', $result['error']);
            redirect('admin/bman-withdrawals/view/' . $id);
            return;
        }

        $this->db->trans_complete();

        if (!$this->db->trans_status()) {
            $this->session->set_flashdata('error', 'Database error while updating request');
            redirect('admin/bman-withdrawals/view/' . $id);
            return;
        }

        $this->session->set_flashdata('success', "Withdrawal request updated to '{$status}'");
        redirect('admin/bman-withdrawals/view/' . $id);
    }
}
