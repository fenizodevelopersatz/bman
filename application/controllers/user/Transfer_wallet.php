<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Transfer_wallet — user-facing internal wallet transfer (doc 9).
 * Between the four internal ledger wallets only (exchange/earning/staking/
 * bonus). USDT is excluded. Money moves via Walletledger (atomic, row-locked).
 * Routes: user/transfer_wallet · /do_transfer · /set_transfer_password.
 */
class Transfer_wallet extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library(['session', 'form_validation']);
        $this->load->helper(['url','form','security']);
        $this->load->database();
        if (!($this->session->userdata('user_logged_in') && $this->session->userdata('user_login'))) {
            redirect('user/in');
        }
        $this->load->model('member/Users_model');
        $this->load->model('Walletledger_model', 'L');
        $this->load->model('wallet/Wallettransfer_model', 'WT');
    }

    private function _json($d, $c = 200)
    {
        $this->output->set_status_header($c)->set_content_type('application/json')->set_output(json_encode($d));
    }
    private function _uid() { return (int)$this->session->userdata('user_userid'); }

    /* ------------------------------- page ------------------------------- */
    public function index()
    {
        $uid = $this->_uid();
        $all = $this->L->balances($uid);
        $data['internal_balances'] = [
            'exchange' => (float)$all['exchange'], 'earning' => (float)$all['earning'],
            'staking'  => (float)$all['staking'],  'bonus'   => (float)$all['bonus'],
        ];
        $user = $this->Users_model->get_user($uid);
        $data['has_transfer_password'] = !empty($user['transfer_password']);
        $data['kyc_approved'] = (strtolower((string)($user['kyc_status'] ?? '')) === 'approved');
        $data['user'] = $user;

        $f = [
            'from'      => $this->input->get('from_wallet', true),
            'to'        => $this->input->get('to_wallet', true),
            'status'    => $this->input->get('status', true),
            'date_from' => $this->input->get('date_from', true),
            'date_to'   => $this->input->get('date_to', true),
        ];
        $per = 15;
        $page = max(1, (int)$this->input->get('page'));
        $total = $this->WT->historyCount($uid, $f);
        $data['history']   = $this->WT->history($uid, $f, $per, ($page - 1) * $per);
        $data['total']     = $total;
        $data['per_page']  = $per;
        $data['page']      = $page;
        $data['pages']     = max(1, (int)ceil($total / $per));
        $data['from_filter']   = $f['from'] ?: '';
        $data['to_filter']     = $f['to'] ?: '';
        $data['status_filter'] = $f['status'] ?: '';
        $data['date_from']     = $f['date_from'] ?: '';
        $data['date_to']       = $f['date_to'] ?: '';
        $data['allowed_pairs_json'] = json_encode($this->WT->pairs());
        $data['title'] = 'Internal Wallet Transfer';
        $this->load->view('user/wallet/transfer_wallet', $data);
    }

    /* -------------------------- POST: do transfer ----------------------- */
    public function do_transfer()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $uid = $this->_uid();
        if (!$uid) return $this->_json(['status'=>'error','message'=>'Not logged in'], 401);

        // Centralized engine: ALL validation + execution lives in the service
        // (KYC + transfer password are the User-Panel gates it enforces here).
        $this->load->model('wallet/Wallettransferservice_model', 'svc');
        $out = $this->svc->execute([
            'mode'              => $this->input->post('mode', true) === 'self' ? 'internal' : 'member',
            'source_user_id'    => $uid,
            'from_wallet'       => $this->input->post('from_wallet', true),
            'to_wallet'         => $this->input->post('to_wallet', true),
            'recipient'         => $this->input->post('recipient', true),
            'amount'            => $this->input->post('amount', true),
            'via'               => 'user',
            'require_kyc'       => true,
            'transfer_password' => $this->input->post('transfer_password', true),
            'note'              => $this->input->post('note', true),
            'idempotency_key'   => $this->input->post('idempotency_key', true),
            'request_id'        => $this->input->post('request_id', true),
            'ip'                => $this->input->ip_address(),
            'ua'                => $this->input->user_agent(),
        ]);
        if (empty($out['ok'])) return $this->_json(['status'=>'error','code'=>$out['code'] ?? '','message'=>$out['message']], 422);

        $bal = $this->L->balances($uid);
        unset($bal['usdt']);
        return $this->_json(['status'=>'success','message'=>'Sent successfully. Ref: '.$out['ref'],
            'reference'=>$out['ref'], 'balances'=>$bal]);
    }

    /** AJAX: recipient picker — up to 20 active members (name + email),
     *  filtered by an optional query. Excludes the sender. */
    public function search_recipients()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $uid = $this->_uid();
        $q = trim((string)$this->input->post('q', true));
        $this->db->select('id, username, email, referral_id')
                 ->from('users')->where('status', '1')->where('id !=', $uid);
        if ($q !== '') {
            $this->db->group_start()
                     ->like('username', $q)->or_like('email', $q)->or_like('referral_id', $q)
                     ->group_end();
        }
        $rows = $this->db->order_by('id', 'ASC')->limit(20)->get()->result_array();
        return $this->_json(['status' => 'success', 'rows' => $rows]);
    }

    /** AJAX: look up a recipient by referral/username/email → name preview.
     *  (Kept for direct validation; the page uses the picker dropdown.) */
    public function lookup_recipient()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $uid = $this->_uid();
        $to = $this->WT->resolveRecipient($this->input->post('recipient', true));
        if (!$to)                       return $this->_json(['status'=>'error','message'=>'Recipient not found.'], 404);
        if ((int)$to['id'] === $uid)    return $this->_json(['status'=>'error','message'=>'This is your own account.'], 422);
        return $this->_json(['status'=>'success',
            'name' => $to['username'], 'referral' => $to['referral_id']]);
    }

    /* ------------------- POST: set the transfer password ---------------- */
    public function set_transfer_password()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $uid = $this->_uid();
        if (!$uid) return $this->_json(['status'=>'error','message'=>'Not logged in'], 401);

        $login = (string)$this->input->post('login_password', true);
        $new   = (string)$this->input->post('new_transfer_password', true);
        $conf  = (string)$this->input->post('confirm_transfer_password', true);

        $user = $this->Users_model->get_user($uid);
        if (!$user) return $this->_json(['status'=>'error','message'=>'User not found.'], 404);
        if (!(password_verify($login, $user['password']) || md5($login) === $user['password'])) {
            return $this->_json(['status'=>'error','message'=>'Incorrect login password.'], 422);
        }
        if (strlen($new) < 4)   return $this->_json(['status'=>'error','message'=>'Transfer password must be at least 4 characters.'], 422);
        if ($new !== $conf)     return $this->_json(['status'=>'error','message'=>'Passwords do not match.'], 422);

        $this->db->where('id', $uid)->update('users', [
            'transfer_password' => password_hash($new, PASSWORD_DEFAULT),
            'transfer_password_set_at' => date('Y-m-d H:i:s'),
        ]);
        return $this->_json(['status'=>'success','message'=>'Transfer password set.']);
    }
}
