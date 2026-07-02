<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin ▸ Master ▸ Token Settings
 * Single source of truth for blockchain configuration: network (Mainnet /
 * Testnet, one active), BMAN + USDT tokens, BMAN↔USDT exchange rate,
 * platform wallets, smart contracts and chain parameters.
 *
 * Role rules: Super Admin (admin_roll = 1) may add/edit (rate, contracts,
 * wallets, RPC, chain id) and activate; other admins view + enable/disable
 * only. Every change is audited with old/new values, admin, date and IP.
 */
class Tokenmaster extends CI_Controller
{
    /** true when the logged-in admin may modify configuration */
    private $is_super = false;

    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper(['url','security']);
        $this->load->model('Admin_model');
        $this->load->model('Tokenmaster_model', 'tokens');

        if (!$this->session->userdata('admin_logged_in')) {
            redirect('admin/login');
        }
        $user = $this->Admin_model->get_user($this->session->userdata('admin_userid'));
        if ($user && $user->admin_roll == '1') {
            $permissions = json_decode($user->permission_pages, true);
            // accept the module's own key OR the legacy payment-settings key
            if (empty($permissions['token_settings_master']) && empty($permissions['payment_settings'])) {
                $this->session->set_flashdata('error', 'Access Denied: You do not have permission.');
                redirect('admin');
            }
        }
        $this->is_super = ($user && $user->admin_roll == '1');
    }

    private function _json($data = [], $code = 200)
    {
        $this->output->set_status_header($code)
                     ->set_content_type('application/json')
                     ->set_output(json_encode($data));
    }

    private function _adminId()
    {
        $id = (int)$this->session->userdata('admin_userid');
        if (!$id) $id = (int)$this->session->userdata('user_id');
        return $id;
    }

    private function _requireSuper()
    {
        if ($this->is_super) return true;
        $this->_json(['status' => 'error',
            'message' => 'Super Admin only — you may view and enable/disable configurations.'], 403);
        return false;
    }

    /* ------------------------------- page ------------------------------- */
    public function index()
    {
        $data['title']      = 'Token Settings';
        $data['card_tilte'] = 'Token Settings (Master)';
        $data['settings']   = $this->tokens->settingsList();
        $data['is_super']   = $this->is_super;
        $this->load->view('admin/master/token_settings', $data);
    }

    /* -------------------- AJAX: create / edit (Super) -------------------- */
    public function save()
    {
        if (!$this->input->is_ajax_request()) show_404();
        if (!$this->_requireSuper()) return;

        $id = (int)$this->input->post('id');
        $data = $this->input->post(null, true); // all fields, XSS-cleaned
        unset($data['id']);

        // Token logo upload (optional) — existing style: assets/img/token/
        if (!empty($_FILES['bman_logo_file']['name'])) {
            $dir = './assets/img/token/';
            if (!is_dir($dir)) @mkdir($dir, 0755, true);
            $this->load->library('upload', [
                'upload_path'   => $dir,
                'allowed_types' => 'jpg|jpeg|png|gif|svg|webp',
                'max_size'      => 2048,
                'file_name'     => 'bman_logo_'.time(),
            ]);
            if (!$this->upload->do_upload('bman_logo_file')) {
                return $this->_json(['status' => 'error',
                    'message' => strip_tags($this->upload->display_errors())], 422);
            }
            $up = $this->upload->data();
            $data['bman_logo'] = 'assets/img/token/'.$up['file_name'];
        }

        list($ok, $res) = $this->tokens->saveSetting(
            $data, $this->_adminId(), $this->input->ip_address(), $id);
        if (!$ok) return $this->_json(['status' => 'error', 'message' => $res], 422);
        return $this->_json(['status' => 'success',
            'message' => $id ? 'Configuration updated.' : 'Configuration added.', 'id' => $res]);
    }

    /* --------------------- AJAX: activate (Super only) ------------------- */
    public function activate($id)
    {
        if (!$this->input->is_ajax_request()) show_404();
        if (!$this->_requireSuper()) return;
        list($ok, $msg) = $this->tokens->setActive((int)$id, $this->_adminId(), $this->input->ip_address());
        return $this->_json(['status' => $ok ? 'success' : 'error', 'message' => $msg], $ok ? 200 : 422);
    }

    /* ------------- AJAX: enable/disable (any admin with access) ---------- */
    public function toggle($id)
    {
        if (!$this->input->is_ajax_request()) show_404();
        $active = (int)$this->input->post('active');
        list($ok, $msg) = $this->tokens->toggleSetting((int)$id, $active, $this->_adminId(), $this->input->ip_address());
        return $this->_json(['status' => $ok ? 'success' : 'error', 'message' => $msg], $ok ? 200 : 422);
    }

    /* --------------------------- AJAX: audit log ------------------------- */
    public function audit()
    {
        if (!$this->input->is_ajax_request()) show_404();
        return $this->_json(['status' => 'success', 'rows' => $this->tokens->auditLog()]);
    }

    /* ------------- AJAX: generate a fresh wallet (Super only) ------------ *
     * Offline key generation for a platform wallet (treasury/gas/etc). The
     * key is returned once for the admin to store securely — it is NOT saved
     * to the database. Addresses (public) go in Token Settings; keys do not.  */
    public function generate_wallet()
    {
        if (!$this->input->is_ajax_request()) show_404();
        if (!$this->_requireSuper()) return;
        try {
            $this->load->library('web3bman');
            $w = $this->web3bman->generateWallet();
            $this->tokens->auditLog(0); // no-op keeps model warm; audit the action:
            $this->db->insert('token_settings_audit', [
                'setting_id' => null, 'action' => 'wallet_generated',
                'old_value' => null, 'new_value' => json_encode(['address' => $w['address']]),
                'changed_by' => $this->_adminId(), 'ip_address' => $this->input->ip_address(),
            ]);
            // private key returned to the browser once; never stored server-side
            return $this->_json(['status' => 'success', 'address' => $w['address'], 'private_key' => $w['private_key']]);
        } catch (Exception $e) {
            return $this->_json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /* -------------- AJAX: read on-chain balances (read only) ------------- */
    public function check_balance()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $address = trim((string)$this->input->post('address', true));
        if (!preg_match('/^0x[a-fA-F0-9]{40}$/', $address)) {
            return $this->_json(['status' => 'error', 'message' => 'Enter a valid 0x… address.'], 422);
        }
        try {
            $this->load->library('web3bman');
            $active = $this->tokens->activeSettings();
            $bnb = $this->web3bman->getBnbBalance($address);
            $token = $active['bman_contract'] ? $this->web3bman->getTokenBalance($address) : null;
            return $this->_json([
                'status'  => 'success',
                'message' => 'BNB: '.$bnb.($token !== null ? ' · '.$active['bman_symbol'].': '.$token
                             : ' · (set BMAN contract to read token balance)'),
            ]);
        } catch (Exception $e) {
            return $this->_json(['status' => 'error', 'message' => $e->getMessage()], 502);
        }
    }

    /* ------------------- AJAX: test RPC connection ----------------------- *
     * JSON-RPC eth_chainId against the given URL; reports latency and
     * whether the returned chain id matches the configured one.             */
    public function test_rpc()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $rpc = trim((string)$this->input->post('rpc_url', true));
        $expected = (int)$this->input->post('chain_id');
        if (!$rpc || !preg_match('#^https?://#i', $rpc)) {
            return $this->_json(['status' => 'error', 'message' => 'Enter a valid http(s) RPC URL.'], 422);
        }

        $start = microtime(true);
        $ch = curl_init($rpc);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode(['jsonrpc' => '2.0', 'method' => 'eth_chainId', 'params' => [], 'id' => 1]),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body = curl_exec($ch);
        $err  = curl_error($ch);
        curl_close($ch);
        $ms = (int)round((microtime(true) - $start) * 1000);

        if ($body === false) {
            return $this->_json(['status' => 'error', 'message' => 'RPC unreachable: '.$err.' ('.$ms.' ms)'], 422);
        }
        $json = json_decode($body, true);
        if (!isset($json['result'])) {
            return $this->_json(['status' => 'error', 'message' => 'RPC responded but not with a chain id ('.$ms.' ms).'], 422);
        }
        $chain = hexdec($json['result']);
        $match = $expected ? ($chain === $expected) : true;
        return $this->_json([
            'status'  => $match ? 'success' : 'error',
            'message' => 'RPC OK — chain id '.$chain.' in '.$ms.' ms.'
                        .($expected ? ($match ? ' Matches the configured chain.' : ' DOES NOT match configured chain '.$expected.'!') : ''),
        ], $match ? 200 : 422);
    }
}
