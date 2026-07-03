<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Wallettransfer — User Internal Wallet Transfer Controller
 * --------------------------------------------------------
 * Route: user/transfer_wallet  (GET page + POST submit)
 * Route: user/transfer_wallet/set_transfer_password (POST)
 * Route: user/transfer_wallet/history_json (GET JSON)
 * Route: user/transfer_wallet/balance_json  (GET JSON)
 *
 * Allowed pairs (USDT is NEVER a source or destination — blockchain only):
 *   Exchange <-> Earning, Exchange <-> Staking, Exchange <-> Bonus
 *   Earning  <-> Bonus
 *   Bonus    <-> Staking
 *   Staking  <-> Exchange, Staking <-> Bonus
 */
class Wallettransfer extends CI_Controller
{
    // Allowed internal wallets (USDT excluded — blockchain only)
    private $allowed_wallets = ['exchange', 'earning', 'staking', 'bonus'];

    // Valid transfer pairs [from => [to, ...]]
    private $allowed_pairs = [
        'exchange' => ['earning', 'staking', 'bonus'],
        'earning'  => ['exchange', 'bonus'],
        'staking'  => ['exchange', 'bonus'],
        'bonus'    => ['exchange', 'earning', 'staking'],
    ];

    // -----------------------------------------------------------------------
    public function __construct()
    {
        parent::__construct();

        $this->load->library(['session', 'form_validation']);
        $this->load->helper(['url', 'form']);
        $this->load->database();

        if (!($this->session->userdata('user_logged_in') && $this->session->userdata('user_login'))) {
            redirect('user/in');
        }

        $language = $this->session->userdata('site_lang') ?? 'english';
        $this->config->set_item('language', $language);
        $this->lang->load('common', $language);

        $this->load->model('Walletledger_model', 'ledger');
    }

    // =======================================================================
    // PAGE: GET /user/transfer_wallet
    // =======================================================================
    public function index()
    {
        $uid = (int) $this->session->userdata('user_userid');

        // Wallet balances (all 4 internal wallets)
        $balances = $this->ledger->balances($uid);
        $internal = [];
        foreach ($this->allowed_wallets as $w) {
            $internal[$w] = isset($balances[$w]) ? (float) $balances[$w] : 0.0;
        }

        // Transfer password set?
        $user = $this->db->select('transfer_password, username, email')
                         ->get_where('users', ['id' => $uid])->row_array();
        $has_transfer_password = !empty($user['transfer_password']);

        // Paged history
        $per_page = 15;
        $page     = max(1, (int) ($this->input->get('page') ?? 1));
        $offset   = ($page - 1) * $per_page;

        $from_filter   = $this->input->get('from_wallet');
        $to_filter     = $this->input->get('to_wallet');
        $status_filter = $this->input->get('status');
        $date_from     = $this->input->get('date_from');
        $date_to       = $this->input->get('date_to');

        $this->db->where('user_id', $uid);
        if ($from_filter && in_array($from_filter, $this->allowed_wallets, true))
            $this->db->where('from_wallet', $from_filter);
        if ($to_filter && in_array($to_filter, $this->allowed_wallets, true))
            $this->db->where('to_wallet', $to_filter);
        if ($status_filter && in_array($status_filter, ['completed','failed','reversed'], true))
            $this->db->where('status', $status_filter);
        if ($date_from) $this->db->where('DATE(created_at) >=', $date_from);
        if ($date_to)   $this->db->where('DATE(created_at) <=', $date_to);
        $total = $this->db->count_all_results('wallet_internal_transfer');

        $this->db->where('user_id', $uid);
        if ($from_filter && in_array($from_filter, $this->allowed_wallets, true))
            $this->db->where('from_wallet', $from_filter);
        if ($to_filter && in_array($to_filter, $this->allowed_wallets, true))
            $this->db->where('to_wallet', $to_filter);
        if ($status_filter && in_array($status_filter, ['completed','failed','reversed'], true))
            $this->db->where('status', $status_filter);
        if ($date_from) $this->db->where('DATE(created_at) >=', $date_from);
        if ($date_to)   $this->db->where('DATE(created_at) <=', $date_to);
        $history = $this->db
            ->order_by('id', 'DESC')
            ->limit($per_page, $offset)
            ->get('wallet_internal_transfer')
            ->result_array();

        $pages = $total > 0 ? (int) ceil($total / $per_page) : 1;

        $data = [
            'title'                => 'Wallet Transfer',
            'internal_balances'    => $internal,
            'has_transfer_password'=> $has_transfer_password,
            'history'              => $history,
            'total'                => $total,
            'page'                 => $page,
            'pages'                => $pages,
            'per_page'             => $per_page,
            'from_filter'          => $from_filter,
            'to_filter'            => $to_filter,
            'status_filter'        => $status_filter,
            'date_from'            => $date_from,
            'date_to'              => $date_to,
            'allowed_wallets'      => $this->allowed_wallets,
            'allowed_pairs_json'   => json_encode($this->allowed_pairs),
            'user'                 => $user,
        ];

        $this->load->view('user/wallet/transfer_wallet', $data);
    }

    // =======================================================================
    // POST: /user/transfer_wallet/do_transfer
    // =======================================================================
    public function do_transfer()
    {
        if (!$this->input->is_ajax_request()) show_404();

        $uid = (int) $this->session->userdata('user_userid');
        if (!$uid) return $this->_json(['status' => 'error', 'message' => 'Not logged in'], 401);

        if (defined('DEMOVERSION') && DEMOVERSION === true)
            return $this->_json(['status' => 'error', 'message' => 'Disabled in demo mode.'], 403);

        // ---- Input ---
        $from   = strtolower(trim((string) $this->input->post('from_wallet', true)));
        $to     = strtolower(trim((string) $this->input->post('to_wallet', true)));
        $amount = trim((string) $this->input->post('amount', true));
        $note   = trim((string) $this->input->post('note', true));
        $pin    = (string) $this->input->post('transfer_password', true);

        // ---- Validate pair ---
        if (!in_array($from, $this->allowed_wallets, true))
            return $this->_json(['status' => 'error', 'message' => 'Invalid source wallet.'], 422);

        if (!in_array($to, $this->allowed_wallets, true))
            return $this->_json(['status' => 'error', 'message' => 'Invalid destination wallet.'], 422);

        if ($from === $to)
            return $this->_json(['status' => 'error', 'message' => 'Source and destination wallets must be different.'], 422);

        if (!in_array($to, $this->allowed_pairs[$from], true))
            return $this->_json(['status' => 'error', 'message' => "Transfer from {$from} to {$to} is not allowed."], 422);

        // ---- Validate amount ---
        if (!is_numeric($amount) || bccomp($amount, '0', 8) <= 0)
            return $this->_json(['status' => 'error', 'message' => 'Enter a valid amount greater than 0.'], 422);

        if (bccomp($amount, '0.00000001', 8) < 0)
            return $this->_json(['status' => 'error', 'message' => 'Amount too small.'], 422);

        // ---- Validate transfer PIN ---
        $user = $this->db->select('transfer_password, username')
                         ->get_where('users', ['id' => $uid])->row_array();

        if (empty($user['transfer_password']))
            return $this->_json(['status' => 'error', 'message' => 'Set a Transfer Password first from Profile Settings.'], 403);

        $pinOk = password_verify($pin, $user['transfer_password'])
               || md5($pin) === $user['transfer_password'];
        if (!$pinOk)
            return $this->_json(['status' => 'error', 'message' => 'Incorrect Transfer Password.'], 422);

        // ---- Check balance ---
        $balances = $this->ledger->balances($uid);
        $src_bal  = (string) ($balances[$from] ?? '0');
        if (bccomp($src_bal, $amount, 8) < 0)
            return $this->_json(['status' => 'error', 'message' => 'Insufficient ' . ucfirst($from) . ' Wallet balance.'], 422);

        // ---- Generate reference ---
        $ref = 'WTF-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid($uid, true)), 0, 8));

        // ---- Double-entry ledger transfer ---
        $desc = "Internal Transfer: " . ucfirst($from) . " → " . ucfirst($to);
        if ($note) $desc .= ' (' . substr($note, 0, 100) . ')';

        $opts = [
            'reference_id' => $ref,
            'description'  => $desc,
        ];

        // Debit source wallet
        list($ok1, $debit_id) = $this->ledger->debit($uid, $from, $amount, 'wallet_transfer', $opts);
        if (!$ok1)
            return $this->_json(['status' => 'error', 'message' => 'Transfer failed (debit): ' . $debit_id], 500);

        // Credit destination wallet
        list($ok2, $credit_id) = $this->ledger->credit($uid, $to, $amount, 'wallet_transfer', $opts);
        if (!$ok2) {
            // Reverse the debit: re-credit the source
            $this->ledger->credit($uid, $from, $amount, 'wallet_transfer', [
                'reference_id' => $ref . '-REV',
                'description'  => 'Transfer reversal: credit failed',
            ]);
            return $this->_json(['status' => 'error', 'message' => 'Transfer failed (credit): ' . $credit_id], 500);
        }

        // ---- Record in audit table ---
        $this->db->insert('wallet_internal_transfer', [
            'ref'              => $ref,
            'user_id'          => $uid,
            'from_wallet'      => $from,
            'to_wallet'        => $to,
            'amount'           => $amount,
            'fee'              => '0',
            'net_amount'       => $amount,
            'status'           => 'completed',
            'description'      => $note ? substr($note, 0, 255) : null,
            'debit_ledger_id'  => (int) $debit_id,
            'credit_ledger_id' => (int) $credit_id,
            'ip_address'       => $this->input->ip_address(),
            'user_agent'       => substr((string) $this->input->user_agent(), 0, 255),
            'created_at'       => date('Y-m-d H:i:s'),
        ]);

        // ---- Return updated balances ---
        $new_balances = $this->ledger->balances($uid);
        $internal = [];
        foreach ($this->allowed_wallets as $w) {
            $internal[$w] = isset($new_balances[$w]) ? (float) $new_balances[$w] : 0.0;
        }

        return $this->_json([
            'status'    => 'success',
            'message'   => 'Transfer successful! Ref: ' . $ref,
            'ref'       => $ref,
            'balances'  => $internal,
        ]);
    }

    // =======================================================================
    // POST: /user/transfer_wallet/set_transfer_password
    // =======================================================================
    public function set_transfer_password()
    {
        if (!$this->input->is_ajax_request()) show_404();

        $uid = (int) $this->session->userdata('user_userid');
        if (!$uid) return $this->_json(['status' => 'error', 'message' => 'Not logged in'], 401);

        if (defined('DEMOVERSION') && DEMOVERSION === true)
            return $this->_json(['status' => 'error', 'message' => 'Disabled in demo mode.'], 403);

        $login_pass = (string) $this->input->post('login_password', true);
        $new_pin    = (string) $this->input->post('new_transfer_password', true);
        $confirm    = (string) $this->input->post('confirm_transfer_password', true);

        if (strlen($new_pin) < 4)
            return $this->_json(['status' => 'error', 'message' => 'Transfer PIN must be at least 4 characters.'], 422);

        if ($new_pin !== $confirm)
            return $this->_json(['status' => 'error', 'message' => 'Transfer PIN and confirmation do not match.'], 422);

        // Verify login password first
        $user = $this->db->select('password, transfer_password')
                         ->get_where('users', ['id' => $uid])->row_array();

        if (!$user)
            return $this->_json(['status' => 'error', 'message' => 'User not found.'], 404);

        $loginOk = password_verify($login_pass, $user['password'])
                 || md5($login_pass) === $user['password'];

        if (!$loginOk)
            return $this->_json(['status' => 'error', 'message' => 'Login password is incorrect.'], 422);

        // Save hashed transfer PIN
        $hash = password_hash($new_pin, PASSWORD_DEFAULT);
        $this->db->where('id', $uid)->update('users', [
            'transfer_password'        => $hash,
            'transfer_password_set_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->_json(['status' => 'success', 'message' => 'Transfer Password set successfully!']);
    }

    // =======================================================================
    // GET: /user/transfer_wallet/balance_json  (AJAX balance refresh)
    // =======================================================================
    public function balance_json()
    {
        $uid = (int) $this->session->userdata('user_userid');
        if (!$uid) return $this->_json(['status' => 'error', 'message' => 'Unauthorized'], 401);

        $balances = $this->ledger->balances($uid);
        $internal = [];
        foreach ($this->allowed_wallets as $w) {
            $internal[$w] = isset($balances[$w]) ? (float) $balances[$w] : 0.0;
        }
        return $this->_json(['status' => 'success', 'balances' => $internal]);
    }

    // =======================================================================
    // GET: /user/transfer_wallet/history_json  (AJAX history)
    // =======================================================================
    public function history_json()
    {
        $uid = (int) $this->session->userdata('user_userid');
        if (!$uid) return $this->_json(['status' => 'error', 'message' => 'Unauthorized'], 401);

        $page     = max(1, (int) ($this->input->get('page') ?? 1));
        $per_page = 10;
        $offset   = ($page - 1) * $per_page;

        $rows = $this->db
            ->where('user_id', $uid)
            ->order_by('id', 'DESC')
            ->limit($per_page, $offset)
            ->get('wallet_internal_transfer')
            ->result_array();

        return $this->_json(['status' => 'success', 'history' => $rows]);
    }

    // =======================================================================
    // Helper
    // =======================================================================
    private function _json(array $data, $code = 200)
    {
        $data['csrfName'] = $this->security->get_csrf_token_name();
        $data['csrfHash'] = $this->security->get_csrf_hash();
        return $this->output
            ->set_status_header($code)
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }
}
