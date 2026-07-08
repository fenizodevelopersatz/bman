<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin ▸ Finance ▸ Internal Wallet Transfers (doc 9)
 * Read-only grid of user-initiated internal wallet-to-wallet transfers, with
 * filters and a detail modal. Money moves live in wallet_ledger.
 */
class Internaltransfers extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper(['url','security']);
        $this->load->model('Admin_model');
        if (!$this->session->userdata('admin_logged_in')) redirect('admin/login');
        $user = $this->Admin_model->get_user($this->session->userdata('admin_userid'));
        if ($user && $user->admin_roll == '1') {
            $perm = json_decode($user->permission_pages, true);
            if (empty($perm['wallet_management']) && empty($perm['finance_wallet_transfer'])) {
                $this->session->set_flashdata('error', 'Access Denied: You do not have permission.');
                redirect('admin');
            }
        }
        $this->load->model('wallet/Wallettransfer_model', 'WT');
    }

    public function index()
    {
        $f = [
            'reference' => $this->input->get('reference', true),
            'user'      => $this->input->get('user', true),
            'wallet'    => $this->input->get('wallet', true),
            'status'    => $this->input->get('status', true),
            'date_from' => $this->input->get('date_from', true),
            'date_to'   => $this->input->get('date_to', true),
        ];
        $data['transfers']  = $this->WT->adminList($f, 200, 0);
        $data['filters']    = $f;
        // Users are now loaded on demand via Select2 AJAX (users()), so the page
        // no longer dumps hundreds of <option>s into the DOM.
        $data['title']      = 'Internal Wallet Transfers';
        $data['card_tilte'] = 'Internal Wallet Transfers';
        $this->load->view('admin/wallet/internal_transfers', $data);
    }

    private function _json($d, $c = 200)
    {
        $this->output->set_status_header($c)->set_content_type('application/json')->set_output(json_encode($d));
    }
    private function _adminId()
    {
        $id = (int)$this->session->userdata('admin_userid');
        if (!$id) $id = (int)$this->session->userdata('user_id');
        return $id;
    }

    /**
     * AJAX (Select2): searchable, paginated active-member list.
     * GET q (term), page. Returns {results:[{id,text}], pagination:{more}}.
     * Default (empty q) returns the first page of all members.
     */
    public function users()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $q    = trim((string)$this->input->get('q', true));
        $page = max(1, (int)$this->input->get('page'));
        $per  = 20;
        $offset = ($page - 1) * $per;

        $this->db->from('users')->where('status', '1');
        if ($q !== '') {
            $this->db->group_start()
                     ->like('username', $q)->or_like('referral_id', $q)
                     ->or_like('email', $q)->or_like('id', $q)
                     ->group_end();
        }
        $total = $this->db->count_all_results('', false);   // keep the builder state
        $rows  = $this->db->select('id, username, referral_id, email')
                          ->order_by('id', 'ASC')->limit($per, $offset)->get()->result_array();

        $results = [];
        foreach ($rows as $u) {
            $label = '#'.$u['id'].' '.$u['username'].' ('.$u['referral_id'].')';
            if (!empty($u['email'])) $label .= ' · '.$u['email'];
            $results[] = ['id' => (int)$u['id'], 'text' => $label];
        }
        return $this->_json([
            'results'    => $results,
            'pagination' => ['more' => ($offset + count($rows)) < $total],
        ]);
    }

    /**
     * AJAX (Select2): valid RECIPIENTS for the chosen source user + From wallet.
     * Only the source's downline (exchange/earning/staking) or direct sponsor
     * (bonus) are returned — the admin cannot pick an invalid recipient.
     * GET sender_id, from_wallet, q. Returns {results:[{id,text}], pagination:{more:false}}.
     */
    public function recipients()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $sender = (int)$this->input->get('sender_id');
        $from   = (string)$this->input->get('from_wallet', true);
        $q      = (string)$this->input->get('q', true);
        if (!$sender || $from === '') return $this->_json(['results' => [], 'pagination' => ['more' => false]]);

        $this->load->model('wallet/Wallettransferservice_model', 'svc');
        $rows = $this->svc->recipientOptions($sender, $from, $q, 20);
        $results = [];
        foreach ($rows as $u) {
            $label = '#'.$u['id'].' '.($u['name'] ?: $u['username']).' ('.$u['referral_id'].')';
            if (!empty($u['email'])) $label .= ' · '.$u['email'];
            $results[] = ['id' => (int)$u['id'], 'text' => $label];
        }
        return $this->_json(['results' => $results, 'pagination' => ['more' => false]]);
    }

    /** AJAX: the four internal wallet balances for a user. */
    public function balances()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $uid = (int)$this->input->post('user_id');
        if (!$uid) return $this->_json(['status'=>'error','message'=>'Select a user.'], 422);
        return $this->_json(['status'=>'success','balances'=>$this->WT->walletBalances($uid)]);
    }

    /**
     * AJAX: admin performs an internal transfer WITHOUT the user-side gates
     * (no KYC, no transfer password). mode = self (between one user's wallets)
     * or member (user A → user B). All movement stays on the ledger + audited.
     */
    public function do_transfer()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $mode = $this->input->post('mode', true);
        $sender = (int)$this->input->post('sender_id');
        if (!$sender) return $this->_json(['status'=>'error','message'=>'Select the source user.'], 422);
        $ipTag = 'admin#'.$this->_adminId().'@'.$this->input->ip_address();

        // Same centralized engine as the User Panel — admin follows the EXACT
        // same wallet/downline/sponsor/balance rules (via=admin only skips the
        // User-Panel KYC + transfer-password gates, never the movement rules).
        $this->load->model('wallet/Wallettransferservice_model', 'svc');
        $out = $this->svc->execute([
            'mode'            => $mode === 'self' ? 'internal' : 'member',
            'source_user_id'  => $sender,
            'from_wallet'     => $this->input->post('from_wallet', true),
            'to_wallet'       => $this->input->post('to_wallet', true),
            'recipient'       => $this->input->post('recipient_id', true),
            'amount'          => $this->input->post('amount', true),
            'via'             => 'admin',
            'actor_id'        => $this->_adminId(),
            'note'            => $this->input->post('note', true),
            'idempotency_key' => $this->input->post('idempotency_key', true),
            'request_id'      => $this->input->post('request_id', true),
            'ip'              => $ipTag, 'ua' => 'admin-panel',
        ]);
        if (empty($out['ok'])) return $this->_json(['status'=>'error','code'=>$out['code'] ?? '','message'=>$out['message']], 422);
        return $this->_json(['status'=>'success','message'=>'Transfer completed. Ref: '.$out['ref'], 'reference'=>$out['ref'],
            'balances'=>$this->WT->walletBalances($sender)]);
    }

    /** AJAX: shared pre-submit preview (rules + balances) via the one engine. */
    public function preview()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $this->load->model('wallet/Wallettransferservice_model', 'svc');
        return $this->_json($this->svc->preview([
            'mode'           => $this->input->post('mode', true) === 'self' ? 'internal' : 'member',
            'source_user_id' => (int)$this->input->post('sender_id'),
            'from_wallet'    => $this->input->post('from_wallet', true),
            'to_wallet'      => $this->input->post('to_wallet', true),
            'recipient'      => $this->input->post('recipient_id', true),
            'amount'         => $this->input->post('amount', true),
        ]));
    }

    /** AJAX: shared transaction-detail modal data (enriched — same shape both panels). */
    public function tx_detail()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $this->load->model('wallet/Wallettransferservice_model', 'svc');
        $d = $this->svc->detailEnriched($this->input->get('ref', true));
        return $this->_json(['ok'=>(bool)$d, 'data'=>$d]);
    }

    public function detail()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $ref = $this->input->get('ref', true);
        $d = $this->WT->detail($ref);
        $this->output->set_content_type('application/json')
                     ->set_output(json_encode(['status' => $d ? 'success' : 'error', 'data' => $d]));
    }
}
