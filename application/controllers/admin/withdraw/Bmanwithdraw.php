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
            redirect('aaddmmiinn/login');
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

        // Read-only lookup (walletRow, not ensureAddress) — a review page
        // must not have the side effect of generating a brand-new custodial
        // wallet for a user who doesn't have one yet.
        $this->load->model('Custodialwallet_model', 'custodial');
        $walletRow = $user_id ? $this->custodial->walletRow($user_id) : null;
        $this->data['user_wallet_address'] = $walletRow['wallet_address'] ?? null;

        $this->data['user_profile'] = $this->_withdrawUserProfile($user_id);

        // Sponsor is stored as users.sponser = the sponsor's own numeric
        // users.id (confirmed against real data — NOT their referral_id).
        // Reuses the same profile helper, so name/email/photo resolution
        // (including the default-avatar fallback) stays identical to the
        // member's own profile card above.
        $sponsorId = (int) ($this->data['user_profile']['sponser'] ?? 0);
        $this->data['sponsor_profile'] = $sponsorId ? $this->_withdrawUserProfile($sponsorId) : [];

        $this->data['kyc_application'] = $kyc ?: [];
        $this->data['legacy_kyc'] = $legacy_kyc ?: [];
        $this->data['kyc_documents'] = $this->_kycDocuments($kyc ?: [], $legacy_kyc ?: []);
        $this->data['kyc_history'] = (!empty($kyc['id']) && $this->db->table_exists('kyc_audit_logs'))
            ? $this->kyc->history((int) $kyc['id'])
            : [];

        $this->data['gas_fees'] = $this->_withdrawGasFees($this->data['row']);
        $this->data['withdraw_history'] = $this->bmanwithdraw->history((int) $id);
        $ts = $this->db->select('explorer_url')->get_where('token_settings', ['status' => 1])->row_array();
        $this->data['explorer_url'] = rtrim($ts['explorer_url'] ?? 'https://bscscan.com', '/');

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

    /**
     * Gas fee breakdown for one withdrawal request, split BMAN-side vs
     * USDT-side — the two are tracked completely differently:
     *
     * BMAN side (gas-funding, collect, refund legs): this app broadcasts
     * these itself via BmanWithdrawCollectCron/refund_bman_onchain(), so
     * every broadcast writes a gas_fee_ledger row up front (policy-estimated
     * gas_limit/gas_price), later backfilled with the real gas_used/
     * native_fee_total once Chain Sync verifies the mined receipt.
     * gas_fee_ledger.reference_id is the request_no STRING here (not the
     * numeric id — matches how BmanWithdrawCollectCron/refund_bman_onchain
     * write it; onchain_transactions uses the numeric id instead, an
     * existing inconsistency between the two tables, not introduced here).
     *
     * USDT side (the manual payout leg): admin sends this externally and
     * only pastes the tx_hash back in — this app never broadcasts it, so
     * there is no gas_fee_ledger row for it at all. The onchain_transactions
     * row the controller inserts on Approve/Complete is written with
     * status='confirmed' immediately, so Chain Sync (which only ever
     * touches pending/processing/broadcasting rows) will never backfill its
     * gas fields either — this leg's real gas cost is simply not tracked by
     * this system today. Reports it as untracked rather than pretending
     * a number exists.
     */
    private function _withdrawGasFees(array $row)
    {
        $legRows = $this->db->select('tx_type, tx_hash, status, gas_limit_used, gas_price_wei, gas_used, native_fee_total, created_at, confirmed_at')
            ->where(['reference_type' => 'bman_withdrawal', 'reference_id' => $row['request_no']])
            ->order_by('id', 'ASC')
            ->get('gas_fee_ledger')->result_array();

        // Keyed by leg ('gas'/'collect'/'refund') so the view can pair each
        // one directly with its raw tx_hash/status columns on $row (gas_tx_hash
        // + gas_cron_status, collect_tx_hash + collect_cron_status,
        // refund_tx_hash + refunded_at) without re-searching a list.
        $byLeg = ['gas' => null, 'collect' => null, 'refund' => null];
        foreach ($legRows as $leg) {
            if ($leg['native_fee_total'] !== null) {
                $leg['bnb_fee'] = (float) $leg['native_fee_total'];
                $leg['is_estimate'] = false;
            } elseif ($leg['gas_limit_used'] !== null && $leg['gas_price_wei'] !== null) {
                $leg['bnb_fee'] = ((float) $leg['gas_limit_used'] * (float) $leg['gas_price_wei']) / 1e18;
                $leg['is_estimate'] = true;
            } else {
                $leg['bnb_fee'] = null;
                $leg['is_estimate'] = true;
            }
            if (array_key_exists($leg['tx_type'], $byLeg)) {
                $byLeg[$leg['tx_type']] = $leg;
            }
        }

        $usdtLeg = null;
        if (!empty($row['tx_hash'])) {
            $usdtLeg = $this->db->select('tx_hash, status, gas_used, gas_price, gas_price_gwei, gas_fee_total, created_at')
                ->where(['reference_type' => 'bman_withdrawal', 'reference_id' => (string) $row['id'], 'tx_type' => 'withdrawal'])
                ->get('onchain_transactions')->row_array();
        }

        return ['bman' => $byLeg, 'usdt' => $usdtLeg];
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
            // Cron flow: pending (already collected on-chain) -> approved.
            // Pay + close out in one step, same as 'completed' below — just a
            // different terminal status name. Legacy pending->approved
            // (Bmanwithdraw_model::approve(), no tx_hash) is retired going
            // forward: fresh requests are never born in legacy 'pending'
            // anymore, so this branch only ever sees the cron-collected case.
            if (empty($tx_hash)) {
                $this->db->trans_rollback();
                $this->session->set_flashdata('error', 'Transaction hash is required to approve the withdrawal');
                redirect('admin/bman-withdrawals/view/' . $id);
                return;
            }
            $result = $this->bmanwithdraw->approve_and_complete($id, $admin_id, $tx_hash, $admin_remark);

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
            // Already-collected requests need the real BMAN sent back
            // on-chain FIRST — a broadcast can't be rolled back, so we don't
            // want a later DB failure to look like nothing happened. If the
            // refund send fails, the whole reject fails too (nothing on-chain
            // was sent, safe to retry).
            $refundTxHash = null;
            if ((int) $row['collect_cron_status'] === 1) {
                $refundResult = $this->bmanwithdraw->refund_bman_onchain($id);
                if (!empty($refundResult['error'])) {
                    $this->db->trans_rollback();
                    $this->session->set_flashdata('error', 'BMAN refund failed: ' . $refundResult['error']);
                    redirect('admin/bman-withdrawals/view/' . $id);
                    return;
                }
                $refundTxHash = $refundResult['tx_hash'];
            }
            $result = $this->bmanwithdraw->reject($id, $admin_id, $admin_remark, $refundTxHash);
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
