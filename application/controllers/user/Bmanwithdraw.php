<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bmanwithdraw extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper(['url']);
        $this->load->model('withdraw/Bmanwithdraw_model', 'bmanwithdraw');

        if (!$this->session->userdata('user_logged_in')) {
            redirect('user/in');
        }
    }

    public function index()
    {
        $user_id = (int) $this->session->userdata('user_userid');
        $this->data['title'] = 'BMAN Withdrawal';
        $this->data['card_title'] = 'Manual BMAN Withdrawal';
        $this->data['settings'] = $this->bmanwithdraw->settings();
        $this->data['wallets'] = $this->bmanwithdraw->wallet_snapshot($user_id);
        $this->data['history'] = $this->bmanwithdraw->user_history($user_id, 100);
        $this->load->view('user/withdraw/bman_withdraw', $this->data);
    }

    public function request()
    {
        if (!$this->input->is_ajax_request()) show_404();

        $user_id = (int) $this->session->userdata('user_userid');
        if (!$user_id) return $this->_json(['status' => false, 'message' => 'Login required']);

        $source_wallet = strtolower(trim((string) $this->input->post('source_wallet', true)));
        $amount = (float) $this->input->post('amount', true);
        $withdraw_address = trim((string) $this->input->post('withdraw_address', true));
        $remark = trim((string) $this->input->post('remark', true));
        $settings = $this->bmanwithdraw->settings();
        $wallets = $this->bmanwithdraw->wallet_snapshot($user_id);

        $map = ['exchange', 'earning', 'staking', 'bonus'];
        if (!in_array($source_wallet, $map, true)) {
            return $this->_json(['status' => false, 'message' => 'Invalid source wallet']);
        }
        if (!$this->bmanwithdraw->source_allowed($source_wallet)) {
            return $this->_json(['status' => false, 'message' => 'This wallet is disabled for withdrawal by admin']);
        }
        if ((int) ($settings['withdraw_status'] ?? 0) !== 1) {
            return $this->_json(['status' => false, 'message' => 'Withdrawals are currently disabled']);
        }
        if ($amount <= 0) {
            return $this->_json(['status' => false, 'message' => 'Enter a valid amount']);
        }
        if ($settings['min_withdraw'] > 0 && $amount < $settings['min_withdraw']) {
            return $this->_json(['status' => false, 'message' => 'Minimum withdraw is ' . $settings['min_withdraw']]);
        }
        if ($settings['max_withdraw'] > 0 && $amount > $settings['max_withdraw']) {
            return $this->_json(['status' => false, 'message' => 'Maximum withdraw is ' . $settings['max_withdraw']]);
        }
        if (empty($withdraw_address)) {
            return $this->_json(['status' => false, 'message' => 'Withdrawal address is required']);
        }
        if ($wallets[$source_wallet] < $amount) {
            return $this->_json(['status' => false, 'message' => 'Insufficient wallet balance']);
        }

        $fee = (float) $settings['withdraw_fee'];
        $net = max(0, $amount - $fee);

        $this->db->trans_start();
        $request = $this->bmanwithdraw->create_request([
            'user_id' => $user_id,
            'source_wallet' => $source_wallet,
            'request_amount' => $amount,
            'fee_amount' => $fee,
            'net_amount' => $net,
            'withdraw_address' => $withdraw_address,
            'remark' => $remark,
        ]);
        if (!$request) {
            $this->db->trans_rollback();
            return $this->_json(['status' => false, 'message' => 'Unable to create request']);
        }

        $this->db->insert('wallet_withdraw_holds', [
            'user_id' => $user_id,
            'request_id' => $request['id'],
            'wallet_type' => $source_wallet,
            'hold_amount' => $amount,
            'released_amount' => 0,
            'status' => 'held',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->bmanwithdraw->log_action($request['id'], 0, 'user_request', null, 'pending', 'User created withdrawal request');
        $this->db->trans_complete();

        $history = $this->bmanwithdraw->user_history($user_id, 100);
        return $this->_json(['status' => true, 'message' => 'Withdrawal request submitted', 'request' => $request, 'history' => $history]);
    }

    public function history()
    {
        $user_id = (int) $this->session->userdata('user_userid');
        return $this->_json(['status' => true, 'history' => $this->bmanwithdraw->user_history($user_id, 100)]);
    }

    private function _json($data)
    {
        $this->output->set_content_type('application/json')->set_output(json_encode($data));
    }
}
