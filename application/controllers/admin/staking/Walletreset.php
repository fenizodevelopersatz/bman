<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Walletreset extends CI_Controller
{
    private $admin_id = null;
    private $is_super = false;

    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper(['url', 'form']);
        $this->load->model('Admin_model');
        $this->load->database();

        if (!$this->session->userdata('admin_logged_in')) {
            redirect('aaddmmiinn/login');
        }

        $user = $this->Admin_model->get_user($this->session->userdata('admin_userid'));
        if (!$user || $user->admin_roll != '1') {
            $this->session->set_flashdata('error', 'Access Denied: Super Admin only.');
            redirect('admin');
        }

        $perm = json_decode($user->permission_pages, true);
        if (empty($perm['staking_management']) && empty($perm['wallet_management'])) {
            $this->session->set_flashdata('error', 'Access Denied: You do not have permission.');
            redirect('admin');
        }

        $this->admin_id = $user->id;
        $this->is_super = true;
    }

    private function _json($data = [], $code = 200)
    {
        $this->output->set_status_header($code)
                     ->set_content_type('application/json')
                     ->set_output(json_encode($data));
    }

    private function _log($action, $user_id, $details = '')
    {
        return $this->db->insert('admin_logs', [
            'admin_id'   => $this->admin_id,
            'action'     => $action,
            'target_id'  => $user_id,
            'details'    => $details,
            'ip_address' => $this->input->ip_address(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function test()
    {
        return $this->_json(['status' => 'success', 'message' => 'Controller loaded', 'admin_id' => $this->admin_id]);
    }

    public function index()
    {
        $data['title']     = 'Wallet & Staking Reset';
        $data['card_title'] = 'Reset Wallet Balances & Staking Records';

        $data['total_orders'] = $this->db->select('COUNT(*) as cnt')
                                         ->from('staking_swap_orders')
                                         ->get()->row()->cnt;

        $data['pending_orders'] = $this->db->select('COUNT(*) as cnt')
                                           ->from('staking_swap_orders')
                                           ->where_in('status', ['created', 'usdt_sent', 'failed_gas', 'failed_usdt'])
                                           ->get()->row()->cnt;

        $data['last_24h'] = $this->db->select('COUNT(*) as cnt')
                                     ->from('staking_swap_orders')
                                     ->where('created_at >=', date('Y-m-d H:i:s', strtotime('-24 hours')))
                                     ->get()->row()->cnt;

        $data['zero_balance_users'] = $this->db->select('COUNT(DISTINCT w.user_id) as cnt')
                                               ->from('user_wallets w')
                                               ->where('w.exchange_balance', 0)
                                               ->where('w.earning_balance', 0)
                                               ->where('w.staking_balance', 0)
                                               ->where('w.bonus_balance', 0)
                                               ->where('w.usd_balance', 0)
                                               ->get()->row()->cnt;

        $this->load->view('admin/staking/wallet_reset', $data);
    }

    public function reset_user()
    {
        if (!$this->input->is_ajax_request()) {
            return $this->_json(['status' => 'error', 'message' => 'AJAX only'], 400);
        }
        $user_id = (int)$this->input->post('user_id', true);
        $confirm = $this->input->post('confirm', true);

        if ($user_id <= 0) return $this->_json(['status' => 'error', 'message' => 'Invalid user ID'], 422);
        if ($confirm !== 'yes') return $this->_json(['status' => 'error', 'message' => 'Confirmation required'], 422);

        $user = $this->db->get_where('users', ['id' => $user_id])->row();
        if (!$user) return $this->_json(['status' => 'error', 'message' => 'User not found'], 404);

        try {
            $this->db->trans_start();
            $order_count = $this->db->where('user_id', $user_id)->count_all_results('staking_swap_orders');

            $this->db->where('user_id', $user_id)->delete('staking_swap_orders');
            $this->db->where('user_id', $user_id)->update('ceiling_wallet', ['held_balance' => 0, 'total_held' => 0, 'total_released' => 0]);
            $this->db->where('user_id', $user_id)->delete('ceiling_wallet_ledger');
            $this->db->where('user_id', $user_id)->where_in('reference_type', ['stake_purchase', 'roi', 'staking_purchase'])->delete('wallet_ledger');
            $this->db->where('user_id', $user_id)->update('user_wallets', ['exchange_balance' => 0, 'earning_balance' => 0, 'staking_balance' => 0, 'bonus_balance' => 0, 'usd_balance' => 0, 'total_deposit_usdt' => 0, 'total_withdraw_usdt' => 0]);

            if (!$this->db->trans_complete()) throw new Exception('Database transaction failed');
            $this->_log('WALLET_RESET_USER', $user_id, "Deleted {$order_count} staking orders and reset all wallet balances");

            return $this->_json(['status' => 'success', 'message' => "Reset all staking & wallet data for user {$user->username}", 'orders_deleted' => $order_count]);
        } catch (Exception $e) {
            $this->db->trans_rollback();
            return $this->_json(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function reset_recent()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $days = (int)$this->input->post('days', true) ?: 7;
        $confirm = $this->input->post('confirm', true);

        if ($days <= 0 || $days > 365) return $this->_json(['status' => 'error', 'message' => 'Invalid days'], 422);
        if ($confirm !== 'yes') return $this->_json(['status' => 'error', 'message' => 'Confirmation required'], 422);

        try {
            $this->db->trans_start();
            $cutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));
            $order_count = $this->db->where('created_at >=', $cutoff)->count_all_results('staking_swap_orders');

            $this->db->where('created_at >=', $cutoff)->delete('staking_swap_orders');
            $this->db->where('created_at >=', $cutoff)->delete('ceiling_wallet_ledger');

            if (!$this->db->trans_complete()) throw new Exception('Database transaction failed');
            $this->_log('WALLET_RESET_RECENT', 0, "Deleted {$order_count} staking orders from last {$days} days");

            return $this->_json(['status' => 'success', 'message' => "Deleted {$order_count} staking orders from the last {$days} days", 'orders_deleted' => $order_count]);
        } catch (Exception $e) {
            $this->db->trans_rollback();
            return $this->_json(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function mark_completed()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $order_id = (int)$this->input->post('order_id', true);

        if ($order_id <= 0) return $this->_json(['status' => 'error', 'message' => 'Invalid order ID'], 422);

        $order = $this->db->get_where('staking_swap_orders', ['id' => $order_id])->row();
        if (!$order) return $this->_json(['status' => 'error', 'message' => 'Order not found'], 404);

        // Only fast-track gas/usdt here — neither leg ever credits a wallet
        // (gas is a BNB funding send, usdt just marks payment received), so
        // flipping them is safe bookkeeping. bman/bonus are deliberately left
        // untouched: StakingPurchasecron::_stepBman() is the only place that
        // credits wallet_ledger for this order, gated on a REAL confirmed
        // on-chain hash. Force-flipping bman_cron_status here (as the status
        // column alone might suggest) would let the cron's stuck-completion
        // sweep activate a real user_stakes row with NO matching ledger
        // credit — reproducing, via this button, the exact "real value with
        // no ledger trace" bug this whole change closes for deliverBman().
        // Leaving usdt_cron_status=1 with bman_tx_hash still empty means the
        // NEXT StakingPurchasecron run (or an admin's now-guarded "Send
        // BMAN" click) broadcasts + confirms + credits bman/bonus normally —
        // same code path, same audit trail, as any other order.
        $update = [
            'status' => 'completed', 'cron_status' => 'completed',
            'gas_cron_status' => 1, 'usdt_cron_status' => 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if (empty($order->gas_tx_hash))  $update['gas_tx_hash']  = 'ADMIN-COMPLETED';
        if (empty($order->usdt_tx_hash)) $update['usdt_tx_hash'] = 'ADMIN-COMPLETED';

        $this->db->where('id', $order_id)->update('staking_swap_orders', $update);
        $this->_log('WALLET_MARK_COMPLETED', $order->user_id,
            "Marked staking order {$order_id} as completed (gas/usdt cron flags force-set; bman/bonus left for the normal credited delivery path)");

        return $this->_json(['status' => 'success', 'message' => "Order {$order_id} marked as completed — BMAN will be delivered and credited by the staking purchase cron on its next pass."]);
    }

    public function get_activity()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $limit = min((int)$this->input->get('limit', true) ?: 50, 500);

        $orders = $this->db->select('o.*, u.username, u.email', false)
                           ->from('staking_swap_orders o')
                           ->join('users u', 'u.id = o.user_id', 'left')
                           ->order_by('o.created_at', 'DESC')
                           ->limit($limit)
                           ->get()->result_array();

        return $this->_json(['status' => 'success', 'data' => $orders, 'count' => count($orders)]);
    }

    public function get_user_wallets()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $user_id = (int)$this->input->get('user_id', true);

        if ($user_id <= 0) return $this->_json(['status' => 'error', 'message' => 'Invalid user ID'], 422);

        $user = $this->db->select('id, username, email, created_at')->get_where('users', ['id' => $user_id])->row_array();
        if (!$user) return $this->_json(['status' => 'error', 'message' => 'User not found'], 404);

        $wallet = $this->db->get_where('user_wallets', ['user_id' => $user_id])->row_array();
        $staking_orders = $this->db->where('user_id', $user_id)->count_all_results('staking_swap_orders');
        $ceiling = $this->db->get_where('ceiling_wallet', ['user_id' => $user_id])->row_array();

        return $this->_json(['status' => 'success', 'user' => $user, 'wallet' => $wallet ?: [], 'staking_orders' => $staking_orders, 'ceiling' => $ceiling ?: ['held_balance' => 0, 'total_held' => 0]]);
    }
}
