<?php
//  defined('BASEPATH') OR exit('No direct script access allowed');
// class Payouts extends CI_Controller
// {
//     public function __construct()
//     {
//         parent::__construct();
//         $this->load->model('wallet/Payout_model', 'payoutModel');
//     }

//     // POST: user/payouts/request  (AJAX)
//     public function request()
//     {
//         // allow only AJAX
//         $isAjax = strtolower($this->input->server('HTTP_X_REQUESTED_WITH') ?? '') === 'xmlhttprequest';

//         $id = (int) $this->session->userdata('userid');
//         if (!$id)
//             return $this->_json(['success' => false, 'message' => 'Login required'], $isAjax);

//         $user = $this->db->get_where('users', ['id' => $id])->row();
//         if (!$user)
//             return $this->_json(['success' => false, 'message' => 'Invalid user'], $isAjax);

//         $method = trim((string) $this->input->post('method', true));
//         $amount = (float) $this->input->post('amount', true);
//         $remark = trim((string) $this->input->post('remark', true));

//         if ($amount <= 0)
//             return $this->_json(['success' => false, 'message' => 'Enter valid amount'], $isAjax);

//         // ✅ You said "All are eligible now" → still keep basic guardrails
//         $available_amount = (float) site_wallet_balance($id);
//         if ($amount > $available_amount) {
//             return $this->_json(['success' => false, 'message' => 'Insufficient balance'], $isAjax);
//         }

//         // settings
//         $min_withdraw = (float) str_replace(',', '', site_settings('withdraw_settings', 'min_withdraw'));
//         $max_withdraw = (float) str_replace(',', '', site_settings('withdraw_settings', 'max_withdraw'));
//         $withdraw_fee = (float) str_replace(',', '', site_settings('withdraw_settings', 'withdraw_fee'));

//         if ($min_withdraw > 0 && $amount < $min_withdraw) {
//             return $this->_json(['success' => false, 'message' => 'Minimum withdraw is ' . $min_withdraw], $isAjax);
//         }
//         if ($max_withdraw > 0 && $amount > $max_withdraw) {
//             return $this->_json(['success' => false, 'message' => 'Maximum withdraw is ' . $max_withdraw], $isAjax);
//         }

//         // method validation
//         $allowed = ['BANK', 'UPI'];
//         if (!$method)
//             $method = 'BANK';
//         if (!in_array(strtoupper($method), $allowed, true)) {
//             return $this->_json(['success' => false, 'message' => 'Invalid withdraw method'], $isAjax);
//         }

//         // ✅ Block new request if there is already an active/pending request
//         $active = $this->payoutModel->has_active_withdraw_request($id);

//         if ($active['exists']) {
//             return $this->_json([
//                 'success' => false,
//                 'message' => 'You already have a pending withdrawal request. Please wait until it is processed.',
//                 'active' => $active, // optional debug info
//             ], $isAjax);
//         }

//         $method = strtoupper($method);

//         // ✅ Insert request (table auto detect inside model)
//         $result = $this->payoutModel->create_withdraw_request([
//             'user_id' => $id,
//             'amount' => $amount,
//             'fee' => $withdraw_fee,
//             'method' => $method,
//             'remark' => $remark,
//         ]);

//         if (!$result['success']) {
//             return $this->_json(['success' => false, 'message' => $result['message'] ?? 'Failed'], $isAjax);
//         }

//         // ✅ Return updated summary + history
//         $snap = $this->payoutModel->get_payout_snapshot($id);

//         return $this->_json([
//             'success' => true,
//             'message' => 'Withdraw request submitted',
//             'insert' => $result['insert'],
//             'payout' => $snap['payout'],
//             'payouts' => $snap['payouts'],
//         ], $isAjax);
//     }

//     private function _json($payload, $isAjax = true)
//     {
//         // if not ajax, you can redirect + flash; but you asked ajax, so always JSON
//         $this->output
//             ->set_content_type('application/json')
//             ->set_output(json_encode($payload));
//     }
// }


defined('BASEPATH') OR exit('No direct script access allowed');

class Payouts extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('wallet/Payout_model', 'payoutModel');
    }

    // POST: user/payouts/request  (AJAX)
    public function request()
    {
        $isAjax = strtolower($this->input->server('HTTP_X_REQUESTED_WITH') ?? '') === 'xmlhttprequest';

        $id = (int) $this->session->userdata('userid');
        if (!$id)
            return $this->_json(['success' => false, 'message' => 'Login required'], $isAjax);

        $user = $this->db->get_where('users', ['id' => $id])->row();
        if (!$user)
            return $this->_json(['success' => false, 'message' => 'Invalid user'], $isAjax);

        $method = trim((string) $this->input->post('method', true));
        $amount = (float) $this->input->post('amount', true);
        $remark = trim((string) $this->input->post('remark', true));

        if ($amount <= 0)
            return $this->_json(['success' => false, 'message' => 'Enter valid amount'], $isAjax);

        $available_amount = (float) site_wallet_balance($id);
        if ($amount > $available_amount) {
            return $this->_json(['success' => false, 'message' => 'Insufficient balance'], $isAjax);
        }

        // settings
        $min_withdraw = (float) str_replace(',', '', site_settings('withdraw_settings', 'min_withdraw'));
        $max_withdraw = (float) str_replace(',', '', site_settings('withdraw_settings', 'max_withdraw'));
        $withdraw_fee = (float) str_replace(',', '', site_settings('withdraw_settings', 'withdraw_fee'));

        if ($min_withdraw > 0 && $amount < $min_withdraw) {
            return $this->_json(['success' => false, 'message' => 'Minimum withdraw is ' . $min_withdraw], $isAjax);
        }
        if ($max_withdraw > 0 && $amount > $max_withdraw) {
            return $this->_json(['success' => false, 'message' => 'Maximum withdraw is ' . $max_withdraw], $isAjax);
        }

        // method validation
        $allowed = ['BANK', 'UPI'];
        if (!$method)
            $method = 'BANK';
        $method = strtoupper($method);
        if (!in_array($method, $allowed, true)) {
            return $this->_json(['success' => false, 'message' => 'Invalid withdraw method'], $isAjax);
        }

        // ✅ Block new request if already pending
        $active = $this->payoutModel->has_active_withdraw_request($id);
        if ($active['exists']) {
            return $this->_json([
                'success' => false,
                'message' => 'You already have a pending withdrawal request. Please wait until it is processed.',
                'active' => $active,
            ], $isAjax);
        }

        // ✅ Create request + history deduct in same transaction
        $result = $this->payoutModel->create_withdraw_request([
            'user_id' => $id,
            'amount' => $amount,
            'fee' => $withdraw_fee,
            'method' => $method,
            'remark' => $remark,
        ]);

        if (!$result['success']) {
            return $this->_json(['success' => false, 'message' => $result['message'] ?? 'Failed'], $isAjax);
        }

        // ✅ Return updated snapshot + withdrawals + user history
        $snap = $this->payoutModel->get_payout_snapshot($id);

        return $this->_json([
            'success' => true,
            'message' => 'Withdraw request submitted',
            'insert' => $result['insert'],
            'payout' => $snap['payout'],
            'payouts' => $snap['payouts'],
            'history' => $snap['history'], // ✅ added
        ], $isAjax);
    }

    private function _json($payload, $isAjax = true)
    {
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($payload));
    }
}
