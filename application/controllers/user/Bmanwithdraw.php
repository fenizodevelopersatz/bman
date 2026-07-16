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
        $this->data['breakdowns'] = $this->bmanwithdraw->maturity_breakdown($user_id);
        $this->data['upcoming'] = $this->bmanwithdraw->upcoming_unlocks($user_id);
        $this->data['maturity_rules'] = $this->bmanwithdraw->maturity_rules();
        $this->data['history'] = $this->bmanwithdraw->user_history($user_id, 100);
        $this->load->view('user/withdraw/bman_withdraw', $this->data);
    }

    public function request()
    {
        if (!$this->input->is_ajax_request()) show_404();

        $user_id = (int) $this->session->userdata('user_userid');
        if (!$user_id) return $this->_json(['status' => false, 'message' => 'Login required']);

        $source_wallet = 'bman';
        $amount = (float) $this->input->post('withdraw_bman', true);
        $withdraw_address = trim((string) $this->input->post('wallet_address', true));
        $platform_address = trim((string) $this->input->post('platform_address', true));
        $remark = trim((string) $this->input->post('remark', true));
        $settings = $this->bmanwithdraw->settings();
        $available = (float) $this->bmanwithdraw->available_balance($user_id);

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
        if (strlen($withdraw_address) < 20 || strlen($withdraw_address) > 120) {
            return $this->_json(['status' => false, 'message' => 'Wallet address required']);
        }
        if (!empty($platform_address) && strcasecmp($platform_address, $withdraw_address) === 0) {
            return $this->_json(['status' => false, 'message' => 'Withdraw address must be different from your platform address']);
        }
        if ($available < $amount) {
            return $this->_json(['status' => false, 'message' => 'Insufficient matured balance']);
        }

        $fee = (float) (site_settings('withdraw_settings', 'withdraw_fee_usdt') ?: $settings['withdraw_fee']);
        $bman_rate = (float) (token_info()->currency_value ?? 0);
        $bman_price = $bman_rate > 0 ? (1 / $bman_rate) : 0;
        $gross_usdt = $amount * $bman_price;
        $net = max(0, $gross_usdt - $fee);
        if ($gross_usdt <= $fee) {
            return $this->_json(['status' => false, 'message' => 'Withdrawal amount too small. Net payout becomes zero.']);
        }

        $this->db->trans_start();
        $request = $this->bmanwithdraw->create_request([
            'user_id' => $user_id,
            'source_wallet' => $source_wallet,
            'request_amount' => $amount,
            'fee_amount' => $fee,
            'net_amount' => $net,
            'withdraw_address' => $withdraw_address,
            'platform_address' => $platform_address,
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
        return $this->_json([
            'status' => true,
            'message' => 'Withdrawal request submitted and sent for admin review',
            'request' => $request,
            'history' => $history,
            'available_balance' => $available - $amount,
            'gross_usdt' => $gross_usdt,
            'net_usdt' => $net,
            'processing_fee_usdt' => $fee,
        ]);
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
