<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Assistant extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        // Make sure these are autoloaded or loaded here:
        // $this->load->database();
        // $this->load->library('session');
        $this->output->set_content_type('application/json', 'utf-8');
    }

    /* -------------------- helpers -------------------- */
    private function sym()
    {
        return function_exists('currency_info') ? currency_info()->currency_symbol : '$';
    }

    private function reply($text, $table = null, $status = 200)
    {
        $payload = ['text' => $text];
        if (!empty($table)) $payload['table'] = $table;
        $this->output->set_status_header($status)->set_output(json_encode($payload));
        return;
    }

    /**
     * Your wallet math from history table (safe Query Builder).
     * Earning types MINUS spending types MINUS special mining row.
     */
    private function site_wallet_balance(int $userId): float
    {
        $earningTypes = ['bonus','rank_commission','internel_transfer_received','internel_swap_received','product_commission'];
        $minusTypes   = ['exchange','internel_transfer_debit','site_withdraw','internel_transfer_send','purchase_product'];

        // earnings
        $this->db->select_sum('amount', 'sum')->from('history');
        $this->db->where('user_id', $userId);
        $this->db->where_in('type', $earningTypes);
        $this->db->where('coin_type', '1');
        $earn = (float) ($this->db->get()->row()->sum ?? 0);

        // minus
        $this->db->select_sum('amount', 'sum')->from('history');
        $this->db->where('user_id', $userId);
        $this->db->where_in('type', $minusTypes);
        $this->db->where('coin_type', '1');
        $minus = (float) ($this->db->get()->row()->sum ?? 0);

        // special minus (mining to user-wallet)
        $this->db->select_sum('amount', 'sum')->from('history');
        $this->db->where([
            'user_id' => $userId,
            'type'    => 'mining',
            'hash_id' => 'user-wallet'
        ]);
        $seminus = (float) ($this->db->get()->row()->sum ?? 0);

        $balance = $earn - $minus - $seminus;
        return ($balance <= 0) ? 0.0 : (float) $balance;
    }

    /** Your investment balance from user_investment */
    private function investment_balance(int $userId): float
    {
        $this->db->select_sum('invest_amount', 'sum')->from('user_investment');
        $this->db->where('user_id', $userId);
        $this->db->where('status', '1');
        $this->db->where_in('approve_status', ['0','1']);
        $sum = (float) ($this->db->get()->row()->sum ?? 0);
        return ($sum <= 0) ? 0.0 : (float) $sum;
    }

    /* ------- orders helpers (mirror your dashboard queries) ------- */

    private function orders_totals(int $userId): array
    {
        // total orders
        $this->db->where('user_id', $userId);
        $totalOrders = (int) $this->db->count_all_results('orders');

        // total spent
        $this->db->select_sum('total_amount', 'sum')->from('orders');
        $this->db->where('user_id', $userId);
        $totalSpent = (float) ($this->db->get()->row()->sum ?? 0);

        return [$totalOrders, $totalSpent];
    }

    private function last_order(int $userId): ?array
    {
        $this->db->where('user_id', $userId);
        $this->db->order_by('created_at', 'desc');
        $row = $this->db->get('orders', 1)->row_array();
        return $row ?: null;
    }

    private function my_orders(int $userId, int $limit = 5): array
    {
        $this->db->select('id,total_amount,status,created_at')
                 ->from('orders')
                 ->where('user_id', $userId)
                 ->order_by('created_at', 'DESC')
                 ->limit($limit);
        return $this->db->get()->result_array();
    }

    /* -------------------- transactions from history -------------------- */
    private function last_tx(int $userId): ?array
    {
        // If your column is "date" instead of "created_at", switch the order_by line
        $this->db->select('id,type,amount,coin_type,IFNULL(created_at,date) as created_at', false)
                 ->from('history')
                 ->where('user_id', $userId)
                 ->where('coin_type', '1')
                 ->order_by('created_at', 'DESC')
                 ->limit(1);
        $row = $this->db->get()->row_array();
        return $row ?: null;
    }

    private function recent_tx(int $userId, int $limit = 10): array
    {
        $this->db->select('id,type,status,amount,IFNULL(created_at,date) as created_at', false)
                 ->from('history')
                 ->where('user_id', $userId)
                 ->where('coin_type', '1')
                 ->order_by('created_at', 'DESC')
                 ->limit($limit);
        return $this->db->get()->result_array();
    }

    /* ========================== ENTRY ========================== */
    public function index()
    {
        // AUTH
        $userId = (int) $this->session->userdata('user_id');
        if (!$userId) return $this->reply('Please login.', null, 401);

        // INPUT
        $message   = trim((string) $this->input->post('message', true));
        $intent    = trim((string) $this->input->post('intent', true));
        $paramsRaw = $this->input->post('params', true);
        $params    = json_decode($paramsRaw ?: '{}', true) ?: [];

        /* ----------- INTENTS (using your schema) ----------- */
        if ($intent !== '') {
            switch ($intent) {

                /* BALANCE */
                case 'balance.main': {
                    $amt = $this->site_wallet_balance($userId);
                    return $this->reply('Main balance: '.$this->sym().' '.number_format($amt, 4));
                }
                case 'balance.investment': { // new: investment balance
                    $amt = $this->investment_balance($userId);
                    return $this->reply('Investment balance: '.$this->sym().' '.number_format($amt, 4));
                }

                /* TRANSACTIONS from history */
                case 'tx.last': {
                    $t = $this->last_tx($userId);
                    if ($t) {
                        $txt = sprintf(
                            "Last transaction #%d: %s %.4f, type: %s on %s",
                            $t['id'], $this->sym(), (float)$t['amount'], strtoupper($t['type']),
                            date('d M Y H:i', strtotime($t['created_at']))
                        );
                        return $this->reply($txt);
                    }
                    return $this->reply('No transactions found.');
                }
                case 'tx.recent': {
                    $rows = $this->recent_tx($userId, 10);
                    if ($rows) {
                        $table = [
                            'headers' => ['#','Type','Amount','Date'],
                            'rows' => array_map(function($r){
                                return [
                                    (string)$r['id'],
                                    strtoupper($r['type']),
                                    $this->sym().' '.number_format((float)$r['amount'], 4),
                                    date('d M Y H:i', strtotime($r['created_at'])),
                                ];
                            }, $rows),
                        ];
                        return $this->reply('Recent transactions:', $table);
                    }
                    return $this->reply('No transactions yet.');
                }

                /* ORDERS */
                case 'shop.totals': {
                    [$totalOrders, $totalSpent] = $this->orders_totals($userId);
                    $txt = "Total orders: {$totalOrders}\nTotal spent: ".$this->sym().' '.number_format($totalSpent, 2);
                    return $this->reply($txt);
                }
                case 'shop.lastOrder': {
                    $o = $this->last_order($userId);
                    if ($o) {
                        $txt = sprintf(
                            "Your last order #%d: %s %.2f, status: %s, on %s",
                            $o['id'], $this->sym(), (float)$o['total_amount'],
                            ucfirst($o['status'] ?? 'unknown'),
                            date('d M Y', strtotime($o['created_at']))
                        );
                        return $this->reply($txt);
                    }
                    return $this->reply('You have no orders yet.');
                }
                case 'shop.myOrders': {
                    $rows = $this->my_orders($userId, 5);
                    if ($rows) {
                        $table = [
                            'headers' => ['Order #','Amount','Status','Date'],
                            'rows' => array_map(function ($r) {
                                return [
                                    (string)$r['id'],
                                    $this->sym().' '.number_format((float)$r['total_amount'], 2),
                                    ucfirst($r['status']),
                                    date('d M Y', strtotime($r['created_at'])),
                                ];
                            }, $rows),
                        ];
                        return $this->reply('Your recent orders:', $table);
                    }
                    return $this->reply('No orders yet.');
                }

                /* (Optional) keep your earlier downline intents here once you share table/columns for legs */
            }
        }

        /* -------- free-text fallback -------- */
        if ($message !== '') {
            return $this->reply('Try quick options: Balance → Main / Investment, Transactions → Last / Recent, Shop → Totals / Last Order / My Orders.');
        }

        return $this->reply('Ask me something!');
    }
}
