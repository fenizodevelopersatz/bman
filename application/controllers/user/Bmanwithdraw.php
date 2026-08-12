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
        $this->data['open_request'] = $this->bmanwithdraw->open_request($user_id, null);
        $this->data['history'] = $this->bmanwithdraw->user_history($user_id, 100);
        $this->load->view('user/withdraw/bman_withdraw', $this->data);
    }

    public function request()
    {
        if (!$this->input->is_ajax_request()) show_404();

        $user_id = (int) $this->session->userdata('user_userid');
        if (!$user_id) return $this->_json(['status' => false, 'message' => 'Login required']);

        $source_wallet = 'exchange';
        $amount_raw = trim((string) $this->input->post('withdraw_bman', true));
        $amount = is_numeric($amount_raw) ? Money::floor8($amount_raw) : '0';
        $withdraw_address = trim((string) $this->input->post('wallet_address', true));
        $remark = trim((string) $this->input->post('remark', true));
        $settings = $this->bmanwithdraw->settings();
        $available = $this->bmanwithdraw->available_balance($user_id);
        $open_request = $this->bmanwithdraw->open_request($user_id, null);
        $limit_usage = $this->bmanwithdraw->limit_usage($user_id);

        // Validation: withdrawals enabled
        if ((int) ($settings['withdraw_status'] ?? 0) !== 1) {
            return $this->_json(['status' => false, 'message' => 'Withdrawals are currently disabled']);
        }

        if (empty($settings['withdraw_allowed_exchange'])) {
            return $this->_json(['status' => false, 'message' => 'Exchange Wallet withdrawals are disabled by admin']);
        }

        // Validation: no open request in progress
        if (!empty($open_request)) {
            return $this->_json([
                'status' => false,
                'message' => 'You already have a withdrawal request in progress',
                'open_request' => $open_request,
            ]);
        }

        // Validation: amount > 0
        if (Money::cmp($amount, '0') <= 0) {
            return $this->_json(['status' => false, 'message' => 'Enter a valid amount']);
        }

        // Validation: meets minimum USDT requirement
        $bman_rate = (string) (token_info()->currency_value ?? '0');
        $bman_price = Money::cmp($bman_rate, '0') > 0 ? Money::div('1', $bman_rate) : '0';
        $gross_usdt = Money::floor6(Money::mul($amount, $bman_price));
        $fee = (string) ($settings['withdraw_fee'] ?? 0);
        // USDT payout always rounds DOWN — never let rounding hand out dust the platform doesn't have.
        $net_usdt = Money::floorZero(Money::floor6(Money::sub($gross_usdt, $fee)));
        $min_withdraw = (string) ($settings['min_withdraw'] ?? '0');

        if (Money::cmp($gross_usdt, $min_withdraw) < 0) {
            $required_bman = Money::cmp($bman_price, '0') > 0 ? Money::div($min_withdraw, $bman_price, 4) : '0';
            return $this->_json(['status' => false, 'message' => "Minimum withdrawal is {$min_withdraw} USDT (about {$required_bman} BMAN)"]);
        }

        // Validation: meets maximum USDT request amount
        $max_withdraw = (string) ($settings['max_withdraw'] ?? '0');
        if (Money::cmp($max_withdraw, '0') > 0 && Money::cmp($gross_usdt, $max_withdraw) > 0) {
            return $this->_json(['status' => false, 'message' => "Maximum withdrawal is {$max_withdraw} USDT"]);
        }

        // Validation: net payout > 0
        if (Money::cmp($gross_usdt, $fee) <= 0) {
            return $this->_json(['status' => false, 'message' => 'Withdrawal amount too small. Net payout becomes zero.']);
        }

        $daily_limit = (string) ($settings['withdraw_daily_limit'] ?? '0');
        $monthly_limit = (string) ($settings['withdraw_monthly_limit'] ?? '0');
        $daily_used = (string) ($limit_usage['daily_usdt'] ?? '0');
        $monthly_used = (string) ($limit_usage['monthly_usdt'] ?? '0');

        if (Money::cmp($daily_limit, '0') > 0 && Money::cmp(Money::add($daily_used, $gross_usdt), $daily_limit) > 0) {
            return $this->_json(['status' => false, 'message' => "Daily withdrawal limit reached. Used {$daily_used} USDT of {$daily_limit} USDT."]);
        }

        if (Money::cmp($monthly_limit, '0') > 0 && Money::cmp(Money::add($monthly_used, $gross_usdt), $monthly_limit) > 0) {
            return $this->_json(['status' => false, 'message' => "Monthly withdrawal limit reached. Used {$monthly_used} USDT of {$monthly_limit} USDT."]);
        }

        // Validation: address format
        if (strlen($withdraw_address) < 20 || strlen($withdraw_address) > 120) {
            return $this->_json(['status' => false, 'message' => 'Wallet address required (20-120 chars)']);
        }

        // Validation: address is not platform custodial
        $custodial = site_settings('token_settings', 'platform_custodial_address') ?? '';
        if (!empty($custodial) && strcasecmp($custodial, $withdraw_address) === 0) {
            return $this->_json(['status' => false, 'message' => 'Withdraw address must be different from platform address']);
        }

        // Validation: sufficient matured balance
        if (Money::cmp($available, $amount) < 0) {
            return $this->_json(['status' => false, 'message' => 'Insufficient matured balance']);
        }

        // Use transaction with lock to prevent double-spending
        $this->db->trans_start();

        // Re-check open request inside transaction (another request might have just been created)
        $open_request = $this->bmanwithdraw->open_request($user_id, null);
        if (!empty($open_request)) {
            $this->db->trans_rollback();
            return $this->_json([
                'status' => false,
                'message' => 'You already have a withdrawal request in progress',
                'open_request' => $open_request,
            ]);
        }

        $limit_usage = $this->bmanwithdraw->limit_usage($user_id);
        $daily_used = (string) ($limit_usage['daily_usdt'] ?? '0');
        $monthly_used = (string) ($limit_usage['monthly_usdt'] ?? '0');
        if (Money::cmp($daily_limit, '0') > 0 && Money::cmp(Money::add($daily_used, $gross_usdt), $daily_limit) > 0) {
            $this->db->trans_rollback();
            return $this->_json(['status' => false, 'message' => "Daily withdrawal limit reached. Used {$daily_used} USDT of {$daily_limit} USDT."]);
        }
        if (Money::cmp($monthly_limit, '0') > 0 && Money::cmp(Money::add($monthly_used, $gross_usdt), $monthly_limit) > 0) {
            $this->db->trans_rollback();
            return $this->_json(['status' => false, 'message' => "Monthly withdrawal limit reached. Used {$monthly_used} USDT of {$monthly_limit} USDT."]);
        }

        // Create request with instant ledger lock
        $request = $this->bmanwithdraw->create_request([
            'user_id' => $user_id,
            'source_wallet' => $source_wallet,
            'request_amount' => $amount,
            'fee_amount' => $fee,
            'net_amount' => $net_usdt,
            'bman_usdt_rate' => $bman_price,
            'withdraw_address' => $withdraw_address,
            'remark' => $remark,
        ]);

        if (!empty($request['error'])) {
            $this->db->trans_rollback();
            return $this->_json(['status' => false, 'message' => $request['error']]);
        }

        $this->bmanwithdraw->log_action($request['id'], 0, 'user_request', null, 'processing', 'User created withdrawal request');
        $this->db->trans_complete();

        if (!$this->db->trans_status()) {
            return $this->_json(['status' => false, 'message' => 'Database error while processing request']);
        }

        // Fetch allocations for display
        $allocations = $this->bmanwithdraw->get_allocations($request['id']);

        $history = $this->bmanwithdraw->user_payout_history($user_id, 100);
        $fresh_open_request = $this->bmanwithdraw->open_request($user_id, null);
        return $this->_json([
            'status' => true,
            'message' => 'Withdrawal request submitted and sent for admin review',
            'request' => $request,
            'open_request' => $fresh_open_request ?: $request,
            'allocations' => $allocations,
            'history' => $history,
            'available_balance' => $this->bmanwithdraw->available_balance($user_id),
            'limit_usage' => $this->bmanwithdraw->limit_usage($user_id),
            'gross_usdt' => $gross_usdt,
            'net_usdt' => $net_usdt,
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
