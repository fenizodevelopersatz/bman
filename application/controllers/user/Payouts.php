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

//         $id = (int) $this->session->userdata('user_userid');
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

    // POST: user/payouts/request  (AJAX) - Step 1: Validate & Send OTP
    public function request()
    {
        $isAjax = strtolower($this->input->server('HTTP_X_REQUESTED_WITH') ?? '') === 'xmlhttprequest';

        $id = (int) $this->session->userdata('user_userid');
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

        // ✅ Generate OTP for payout verification
        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $otp_expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        // Store OTP in session
        $this->session->set_userdata('payout_otp', $otp);
        $this->session->set_userdata('payout_otp_expiry', $otp_expiry);

        // Store payout details in session for later processing
        $this->session->set_userdata('payout_pending', [
            'user_id' => $id,
            'amount' => $amount,
            'fee' => $withdraw_fee,
            'method' => $method,
            'remark' => $remark,
        ]);

        // Send OTP email using template
        $this->load->model('user/Mlm_model', 'common_model');

        $template = $this->db->get_where('email_template', ['subject' => 'Verify Your Payout Request - OTP'])->row();
        if (!$template) {
            $this->session->unset_userdata(['payout_otp', 'payout_otp_expiry', 'payout_pending']);
            return $this->_json(['success' => false, 'message' => 'Email template not configured'], $isAjax);
        }

        $subject = $template->subject;
        $message = str_replace('[USERNAME]', $user->username, $template->temp_content);
        $message = str_replace('[OTP]', $otp, $message);
        $message = str_replace('[AMOUNT]', $amount, $message);
        $message = str_replace('[METHOD]', $method, $message);

        $email_sent = $this->common_model->sendmail($user->email, $subject, $message);

        if (!$email_sent) {
            $this->session->unset_userdata(['payout_otp', 'payout_otp_expiry', 'payout_pending']);
            return $this->_json(['success' => false, 'message' => 'Failed to send OTP email'], $isAjax);
        }

        return $this->_json([
            'success' => true,
            'message' => 'OTP sent to your email. Please verify to complete payout request.',
            'require_otp' => true,
            'email' => substr($user->email, 0, 3) . '***' . substr($user->email, -10),
        ], $isAjax);
    }

    // POST: user/payouts/verify-otp  (AJAX) - Step 2: Verify OTP & Create Request
    public function verify_otp()
    {
        $isAjax = strtolower($this->input->server('HTTP_X_REQUESTED_WITH') ?? '') === 'xmlhttprequest';

        $id = (int) $this->session->userdata('user_userid');
        if (!$id)
            return $this->_json(['success' => false, 'message' => 'Login required'], $isAjax);

        $otp = trim((string) $this->input->post('otp', true));
        if (!$otp || strlen($otp) !== 6)
            return $this->_json(['success' => false, 'message' => 'Invalid OTP format'], $isAjax);

        // Check OTP validity
        $session_otp = $this->session->userdata('payout_otp');
        $otp_expiry = $this->session->userdata('payout_otp_expiry');
        $payout_pending = $this->session->userdata('payout_pending');

        if (!$session_otp || !$payout_pending) {
            return $this->_json(['success' => false, 'message' => 'No pending payout request'], $isAjax);
        }

        if (strtotime($otp_expiry) < time()) {
            $this->session->unset_userdata(['payout_otp', 'payout_otp_expiry', 'payout_pending']);
            return $this->_json(['success' => false, 'message' => 'OTP expired'], $isAjax);
        }

        if ((string) $otp !== (string) $session_otp) {
            return $this->_json(['success' => false, 'message' => 'Invalid OTP'], $isAjax);
        }

        // ✅ OTP verified - Now create the withdraw request
        $result = $this->payoutModel->create_withdraw_request($payout_pending);

        if (!$result['success']) {
            return $this->_json(['success' => false, 'message' => $result['message'] ?? 'Failed to create request'], $isAjax);
        }

        // Clear OTP session data
        $this->session->unset_userdata(['payout_otp', 'payout_otp_expiry', 'payout_pending']);

        // ✅ Return updated snapshot
        $snap = $this->payoutModel->get_payout_snapshot($id);

        return $this->_json([
            'success' => true,
            'message' => 'Withdraw request submitted successfully',
            'insert' => $result['insert'],
            'payout' => $snap['payout'],
            'payouts' => $snap['payouts'],
            'history' => $snap['history'],
        ], $isAjax);
    }

    private function _json($payload, $isAjax = true)
    {
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($payload));
    }
}
