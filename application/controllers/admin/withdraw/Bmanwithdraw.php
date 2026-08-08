<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bmanwithdraw extends MY_Controller
{
    private $is_super = false;

    public function __construct()
    {
        parent::__construct();
        $this->load->library(['session', 'form_validation']);
        $this->load->helper(['url']);
        $this->load->model('Admin_model');
        $this->load->model('withdraw/Bmanwithdraw_model', 'bmanwithdraw');
        $this->load->model('Walletledger_model', 'ledger');
        $this->load->model('Kyc_model', 'kyc');

        if (!$this->session->userdata('admin_logged_in')) {
            redirect('admin/login');
        }
        $user = $this->Admin_model->get_user($this->session->userdata('admin_userid'));
        $this->is_super = ($user && $user->admin_roll == '1');
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
        $this->data['is_super'] = $this->is_super;

        // Load allocations for mixed requests
        $this->data['allocations'] = $this->bmanwithdraw->get_allocations((int) $id);
        $user_id = (int) ($this->data['row']['user_id'] ?? 0);
        $kyc = ($user_id && $this->db->table_exists('kyc_applications')) ? $this->kyc->getByUser($user_id) : [];
        $legacy_kyc = $this->_legacyKyc($user_id);

        $this->data['user_profile'] = $this->_withdrawUserProfile($user_id);
        $this->data['kyc_application'] = $kyc ?: [];
        $this->data['legacy_kyc'] = $legacy_kyc ?: [];
        $this->data['kyc_documents'] = $this->_kycDocuments($kyc ?: [], $legacy_kyc ?: []);
        $this->data['kyc_history'] = (!empty($kyc['id']) && $this->db->table_exists('kyc_audit_logs'))
            ? $this->kyc->history((int) $kyc['id'])
            : [];

        $this->load->view('admin/withdraw/bman_view', $this->data);
    }

    private function _withdrawUserProfile($user_id)
    {
        $user_id = (int) $user_id;
        if ($user_id <= 0 || !$this->db->table_exists('users')) {
            return [];
        }

        $wanted = [
            'id',
            'name',
            'first_name',
            'last_name',
            'username',
            'email',
            'contact',
            'address',
            'gender',
            'image',
            'profile_img',
            'kyc_status',
            'kyc_last_submitted_at',
            'kyc_verified_at',
            'kyc_reviewer_id',
            'zipcode',
            'dob',
            'status',
            'register_date',
            'sponser',
            'referral_id',
            'country'
        ];
        $fields = array_intersect($wanted, $this->db->list_fields('users'));
        if (!$fields) {
            return [];
        }

        $user = $this->db->select(implode(',', $fields))
            ->get_where('users', ['id' => $user_id])
            ->row_array();
        if (!$user) {
            return [];
        }

        foreach ($wanted as $key) {
            if (!array_key_exists($key, $user)) {
                $user[$key] = '';
            }
        }

        $full_name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        if ($full_name === '') {
            $full_name = trim((string) ($user['name'] ?: $user['username']));
        }

        $user['display_name'] = $full_name;
        $user['profile_photo'] = $this->_publicAssetUrl($user['profile_img'] ?: $user['image'], 'assets/images/');
        if ($user['profile_photo'] === '') {
            $user['profile_photo'] = base_url('assets/images/default-avatar.svg');
        }

        return $user;
    }

    private function _legacyKyc($user_id)
    {
        $user_id = (int) $user_id;
        if ($user_id <= 0 || !$this->db->table_exists('user_kyc')) {
            return [];
        }

        return $this->db->get_where('user_kyc', ['user_id' => $user_id])->row_array() ?: [];
    }

    private function _kycDocuments(array $kyc, array $legacy_kyc)
    {
        $docs = [];

        $add = function ($label, $url) use (&$docs) {
            $url = $this->_publicAssetUrl($url);
            if ($url !== '') {
                $docs[] = [
                    'label' => $label,
                    'url' => $url,
                    'is_image' => (bool) preg_match('/\.(jpe?g|png|webp|gif|svg)(\?.*)?$/i', $url),
                ];
            }
        };

        $add('Document Front', $kyc['doc_front_url'] ?? '');
        $add('Document Back', $kyc['doc_back_url'] ?? '');
        $add('Selfie With ID', $kyc['selfie_url'] ?? '');
        $add('Address Proof', $kyc['proof_address_url'] ?? '');
        $add('PAN Document', $legacy_kyc['pan_doc'] ?? '');
        $add('Aadhaar Document', $legacy_kyc['aadhaar_doc'] ?? '');

        return $docs;
    }

    private function _publicAssetUrl($path, $default_prefix = '')
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }
        // Full URL (possibly a stale pre-domain-change host) OR a known
        // uploads/assets path: re-root onto the CURRENT base_url via media_url()
        // so KYC/proof images never break after a domain change.
        if (preg_match('#^https?://#i', $path) || strpos($path, 'uploads/') !== false || strpos($path, 'assets/') !== false) {
            return media_url($path);
        }

        $path = ltrim(str_replace('\\', '/', $path), '/');
        return base_url($default_prefix . $path);
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
                    'token_symbol' => 'USDT',
                    'amount' => !empty($row['usdt_amount']) ? $row['usdt_amount'] : $row['net_amount'],
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

    /**
     * AJAX: reveal the decrypted treasury private key + wallet address, so a
     * Super Admin can manually send this withdrawal's payout from an external
     * wallet app. Password-gated (Tokenmaster_model::revealTreasuryKey() —
     * separate payout password, rate-limited, every attempt audited). The key
     * is returned once in this response only — never persisted, never logged.
     */
    public function reveal_treasury_key($id)
    {
        if (!$this->input->is_ajax_request()) show_404();
        if (!$this->is_super) {
            $this->output->set_status_header(403)->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => 'Super Admin only.']));
            return;
        }

        $id = (int) $id;
        $row = $this->bmanwithdraw->get_request($id);
        if (!$row) show_404();

        $this->load->model('Tokenmaster_model', 'tokens');
        $password = (string) $this->input->post('payout_password');
        list($ok, $result) = $this->tokens->revealTreasuryKey(
            $password, (int) $this->session->userdata('admin_userid'),
            $this->input->ip_address(), $id
        );

        $this->output->set_content_type('application/json')
            ->set_header('Cache-Control: no-store, no-cache, must-revalidate')
            ->set_output(json_encode($ok
                ? ['status' => 'success', 'address' => $result['address'], 'private_key' => $result['private_key']]
                : ['status' => 'error', 'message' => $result]));
    }
}
