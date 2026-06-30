<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Cron extends CI_Controller
{


    public function __construct()
    {

        // ini_set('display_errors', 1);
        // ini_set('display_startup_errors', 1);
        // error_reporting(E_ALL);

        parent::__construct();
        $this->load->database();
        $this->load->model('settings/Commission_model');

    }

    private function _dbg($tag, $payload = null)
    {
        if (is_array($payload) || is_object($payload)) {
            $payload = json_encode($payload);
        }
        log_message('debug', "[CommissionEngine][$tag] {$payload}");
    }

    /**
     * Run ROI
     **/
    public function run_roi()
    {
        $today = date("Y-m-d");
        // $today = date('Y-m-d', strtotime($today . ' -1 day'));

        $this->db->select('i.id, i.user_id, i.invest_amount, i.csq_deposit, i.earn_by, i.profit AS roi_percent, i.days_count, i.run_date, i.mature_date, i.package_id, p.package_name');
        $this->db->from('user_investment i');
        $this->db->join('package_config p', 'i.package_id = p.id');
        $this->db->where('i.status', 1);
        $this->db->where('i.days_count >', 0);
        $this->db->where('DATE(i.run_date) <=', $today);
        $investments = $this->db->get()->result();

        $token_info = token_info();
        $currency_info = currency_info();

        foreach ($investments as $row) {

            $investment_id = (int) $row->id;
            $user_id = (int) $row->user_id;
            $invest_amount = (float) $row->invest_amount;
            $csq_deposit = (float) $row->csq_deposit;
            $roi_percent = (float) $row->roi_percent; // ✅ percent
            $days_count = (int) $row->days_count;
            $mature_date = date('Y-m-d', strtotime($row->mature_date));
            $run_date = date('Y-m-d', strtotime($row->run_date));
            $package_name = (string) $row->package_name;
            $package_id = (int) $row->package_id;

            // loop to catch missed days
            while (
                strtotime($run_date) <= strtotime($today)
                && strtotime($run_date) < strtotime($mature_date)
                && $days_count > 0
            ) {

                $daily_roi = round(($invest_amount * $roi_percent) / 100, 6);
                $daily_roi_token = round(($csq_deposit * $roi_percent) / 100, 6);

                // coin_type: keep as 1 (currency) for now (your code always uses 1)
                $coin_type = 1;

                // day_no = already credited + 1 (optional but useful)
                $creditedDaysRow = $this->db->query(
                    "SELECT COUNT(*) AS c FROM roi_credits WHERE invest_id=? AND status=1",
                    [$investment_id]
                )->row();
                $day_no = ((int) ($creditedDaysRow->c ?? 0)) + 1;

                // 1) Insert roi_credits (guarded by unique key invest_id+credit_date)
                $roi_credit = [
                    'user_id' => $user_id,
                    'invest_id' => $investment_id,
                    'package_id' => $package_id,
                    'credit_date' => $run_date,
                    'day_no' => $day_no,
                    'roi_percent' => $roi_percent,
                    'base_amount' => $invest_amount,
                    'amount' => $daily_roi,
                    'token_amount' => $daily_roi_token,
                    'coin_type' => $coin_type,
                    'status' => 1,
                    'note' => "Daily ROI credit ({$package_name})",
                    'created_at' => date('Y-m-d H:i:s'),
                ];

                // Use INSERT IGNORE to avoid duplicate-day credits
                $sql = $this->db->insert_string('roi_credits', $roi_credit);
                $sql = str_replace('INSERT INTO', 'INSERT IGNORE INTO', $sql);
                $this->db->query($sql);

                $inserted = ($this->db->affected_rows() > 0);

                // If already credited for that date, don't reduce days_count again.
                if (!$inserted) {
                    $this->_dbg('CRON_ROI_SKIP_DUPLICATE', ['invest_id' => $investment_id, 'date' => $run_date]);
                    // move to next day without changing days_count
                    $run_date = date('Y-m-d', strtotime($run_date . ' +1 day'));
                    continue;
                }

                $this->_dbg('CRON_ROI_CREDIT', $roi_credit);

                // 2) Insert into history (optional but keeps your existing UI working)
                $history = [
                    "user_id" => $user_id,
                    "amount" => $daily_roi,
                    "type" => "profit",
                    "history_date" => date('Y-m-d H:i:s'),
                    "date" => date('Y-m-d H:i:s', strtotime($run_date)),
                    "status" => '1',
                    "hash_id" => "roi-made",
                    "invest_id" => $investment_id,
                    "description" => token_format($daily_roi_token) . " Lending bonus made",
                    "token_amount" => $daily_roi_token,
                    "coin_id" => $currency_info->id,
                    "token_id" => $token_info->id,
                    "coin_type" => $coin_type,
                ];
                $this->db->insert("history", $history);
                $this->_dbg('CRON_ROI_HISTORY', $history);

                // 3) Update investment run_date + days_count ONLY (do not overwrite roi %)
                $days_count--;
                $next_run_date = date('Y-m-d', strtotime($run_date . ' +1 day'));

                $this->db->where('id', $investment_id);
                $this->db->update('user_investment', [
                    'days_count' => $days_count,
                    'run_date' => $next_run_date
                ]);

                // 4) Mature check
                if ($days_count <= 0 || strtotime($next_run_date) >= strtotime($mature_date)) {
                    $this->package_mature($investment_id);
                    break;
                }

                $run_date = $next_run_date;
            }
        }
    }



    /**
     * Binary Earnings
     **/
    public function binary_commission_call()
    {
        // Load config (single row config)
        $cfg = $this->db->get_where('commission_config', ['id' => 1])->row();
        if (!$cfg) {
            echo json_encode(['result' => false, 'msg' => 'Commission config not found']);
            return;
        }

        // Run date (daily cron run date)
        $run_date = date("Y-m-d");

        // =======================
        // 1) Binary Commission
        // =======================
        if ((int) $cfg->binary_commission_status === 1) {

            /**
             * Carry Forward settings (your new setup)
             * These fields should exist in commission_config:
             * - carry_forward_status (0/1)
             * - carry_forward_mode   (Lifetime/Daily/Weekly/Monthly)
             * - carry_forward_cap    (number or null)
             *
             * NOTE: Your process_binary_commission_v2() already reads:
             *  $carry_mode = $cfg->binary_carry_forward
             *  $flush_rule = $cfg->binary_flush_rule
             *
             * So here we just map your ADMIN fields into those keys
             * (backward compatible).
             */

            // 1) Carry forward enable/disable
            $carry_status = isset($cfg->carry_forward_status) ? (int) $cfg->carry_forward_status : 0;

            // 2) Mode => we convert to flush_rule for engine
            // Admin values: Lifetime / Daily / Weekly / Monthly
            $mode = isset($cfg->carry_forward_mode) ? strtolower(trim($cfg->carry_forward_mode)) : 'lifetime';

            // Map admin mode => engine flush rule
            // lifetime = never flush, daily/weekly/monthly flush accordingly
            $flush_rule = 'never';
            if ($mode === 'daily')
                $flush_rule = 'daily';
            if ($mode === 'weekly')
                $flush_rule = 'weekly';
            if ($mode === 'monthly')
                $flush_rule = 'monthly';

            // 3) Cap (optional)
            $cap = null;
            if (isset($cfg->carry_forward_cap) && $cfg->carry_forward_cap !== '' && $cfg->carry_forward_cap !== null) {
                $cap = (float) $cfg->carry_forward_cap;
            }

            // Write into cfg object so your process_binary_commission_v2() can read it
            // carry mode rule: if OFF => none, if ON => both (recommended) or weak_leg as you prefer
            $cfg->binary_carry_forward = ($carry_status === 1) ? 'both' : 'none';
            $cfg->binary_flush_rule = $flush_rule;
            $cfg->carry_forward_cap_bv = $cap;

            // ✅ IMPORTANT: keep method name EXACT
            $this->process_binary_commission_v2($run_date);
        }

        // =======================
        // 2) Matching Bonus
        // =======================
        if ((int) $cfg->matching_bonus_status === 1) {
            $this->process_matching_bonus($run_date);
        }

        $result = [
            'result' => true,
            'msg' => 'Binary & Matching processed',
            'date' => $run_date
        ];
        $this->_dbg('CRON_BINARY_COMMISSION_CALL_DONE', $result);
        echo json_encode($result);
        exit;
    }


    /**
     * Package Reinvestemnt
     **/
    private function package_mature($investment_id)
    {
        $today = date('Y-m-d');

        $this->db->select('*');
        $this->db->from('user_investment');
        $this->db->where('id', $investment_id);
        $investment = $this->db->get()->row();

        if (!$investment)
            return;

        if (strtotime($investment->mature_date) <= strtotime($today)) {

            if ($investment->reinvest_status == 1) {

                $this->db->select('*');
                $this->db->from('package_config');
                $this->db->where('id', $investment->package_id);
                $package = $this->db->get()->row();

                $invest_date = $investment->mature_date;
                $rundate = date('Y-m-d', strtotime($invest_date . ' +1 day'));
                $maturedate = date('Y-m-d H:i:s', strtotime($invest_date . ' +' . $package->days_duration . ' days'));
                $ending_date = $maturedate;


                $insert_data = array(
                    "user_id" => $investment->user_id,
                    "invest_amount" => $investment->invest_amount,
                    "invest_network" => $investment->invest_network,
                    "status" => '1',
                    "created_date" => $invest_date,
                    "days_count" => $package->days_duration,
                    "profit" => $package->roi,
                    "hash_id" => $investment->hash_id,
                    "run_date" => $rundate,
                    "starting_date" => date('Y-m-d H:i:s', strtotime($invest_date)),
                    "ending_date" => $ending_date,
                    "mature_date" => $maturedate,
                    "type" => "mining",
                    'reinvest_id' => $investment_id,
                    "req_method" => $investment->req_method,
                    "approve_status" => $investment->approve_status,
                    "package_id" => $investment->package_id,
                    "csq_price" => $investment->csq_price,
                    "csq_deposit" => $investment->csq_deposit,
                    "earn_by" => $investment->earn_by,
                    "currency_id" => $investment->currency_id,
                    "token_id" => $investment->token_id,
                );

                $this->db->insert("user_investment", $insert_data);
                $new_invest_id = $this->db->insert_id();

                if ($new_invest_id) {

                    $deposit_data = array(
                        "user_id" => $investment->user_id,
                        "amount" => $investment->invest_amount,
                        "type" => "mining",
                        "history_date" => date('Y-m-d H:i:s', strtotime($invest_date)),
                        "date" => date('Y-m-d H:i:s', strtotime($invest_date)),
                        "status" => '1',
                        "hash_id" => $investment->hash_id,
                        "invest_id" => $new_invest_id,
                        "token_amount" => $investment->csq_deposit,
                        "description" => "Investment Successfully",
                        "coin_id" => $investment->currency_id,
                        "token_id" => $investment->token_id,
                        "coin_type" => '1',
                        "transaction_id" => "reinvestment"
                    );
                    $this->db->insert("history", $deposit_data);

                    $this->db->where('id', $investment_id);
                    $this->db->update('user_investment', [
                        'status' => 2,
                        'recived_status' => 1
                    ]);
                }
            } else {

                $query = $this->db->query("SELECT * FROM package_config WHERE id = ? AND status = '1'", [$investment->package_id]);
                $package = $query->row();

                $deposit_data = array(
                    "user_id" => $investment->user_id,
                    "amount" => $investment->invest_amount,
                    "type" => "release_deposit",
                    "history_date" => date('Y-m-d H:i:s', strtotime($investment->mature_date)),
                    "date" => date('Y-m-d H:i:s', strtotime($investment->mature_date)),
                    "status" => '1',
                    "hash_id" => $investment->hash_id,
                    "invest_id" => $investment->id,
                    "token_amount" => $investment->csq_deposit,
                    "description" => "Package Matured Successfully ( " . $package->package_name . " )",
                    "coin_type" => '1',
                    "token_id" => $investment->token_id,
                    "coin_id" => $investment->currency_id,
                );
                $this->db->insert("history", $deposit_data);

                $this->db->where('id', $investment_id);
                $this->db->update('user_investment', ['status' => 2, 'recived_status' => 0]);
            }
        }
    }

    /**
     * Run Binary Match Bonus (AFTER ROI is Distributed)
     **/
    private function process_binary_commission($run_date)
    {


        $this->db->select('id');
        $this->db->where_in('status', '1');
        $users = $this->db->get('users')->result();

        $token_info = token_info();
        $currency_info = currency_info();

        foreach ($users as $user) {
            $user_id = $user->id;

            $this->db->where('user_id', $user_id);
            $this->db->where('type', 'binary_commission');
            $this->db->where('DATE(history_date)', $run_date);
            $existing_bonus = $this->db->count_all_results('history');



            if ($existing_bonus > 0) {
                continue;
            }



            $left_users = $this->get_leg_users($user_id, 'left');
            $right_users = $this->get_leg_users($user_id, 'right');

            $total_left_users = !empty($left_users) ? implode(',', $left_users) : '';
            $total_right_users = !empty($right_users) ? implode(',', $right_users) : '';

            $left_roi_data = $this->get_users_roi_total($left_users, $run_date);
            $right_roi_data = $this->get_users_roi_total($right_users, $run_date);

            $left_total_earning = $left_roi_data['total_profit_token'];
            $right_total_earning = $right_roi_data['total_profit_token'];

            $left_invest_ids = $left_roi_data['invest_ids'];
            $right_invest_ids = $right_roi_data['invest_ids'];

            $left_total_invest = $left_roi_data['total_invest_amount'];
            $right_total_invest = $right_roi_data['total_invest_amount'];

            $matching_amount = min($left_total_earning, $right_total_earning);

            if ($matching_amount > 0) {
                $this->db->select('binary_commission');
                $config = $this->db->get('commission_config')->row();

                if ($config) {

                    $binary_commission = ($matching_amount * $config->binary_commission) / 100;
                    $binary_commission_usd = $binary_commission / $token_info->currency_value;

                    $commission_data = [
                        "user_id" => $user_id,
                        "amount" => $binary_commission_usd,
                        "type" => "binary_commission",
                        "history_date" => date('Y-m-d', strtotime($run_date)) . ' 10:' . date('i:s', strtotime($run_date)),
                        "date" => date('Y-m-d', strtotime($run_date)) . ' 10:' . date('i:s', strtotime($run_date)),
                        "status" => '1',
                        "coin_type" => '1',
                        "token_amount" => $binary_commission,
                        "description" => "Binary Matching Commission Earned",
                        "total_left_invest" => $left_total_invest,
                        "total_right_invest" => $right_total_invest,
                        "total_left_roi" => $left_total_earning,
                        "total_right_roi" => $right_total_earning,
                        "total_left_users" => $total_left_users,
                        "total_right_users" => $total_right_users,
                        "total_left_invest_ids" => $left_invest_ids,
                        "total_right_invest_ids" => $right_invest_ids,
                        "coin_id" => $currency_info->id,
                        "token_id" => $token_info->id,
                    ];
                    $this->db->insert("history", $commission_data);
                }
            }
        }
    }
    /**
     * Recursively Get All Users Under a Leg
     **/
    private function get_leg_users($parent_id, $position)
    {
        $users = [];

        $child = $this->db->get_where('binary_placement', ['parent_id' => $parent_id, 'position' => $position])->row();

        if ($child) {
            $users[] = $child->user_id;

            $users = array_merge($users, $this->get_leg_users($child->user_id, 'left'));
            $users = array_merge($users, $this->get_leg_users($child->user_id, 'right'));
        }

        return $users;
    }
    /**
     * Get Total ROI, Invest IDs, and Investment Amount for Given Users on a Date
     **/
    private function get_users_roi_total($user_ids, $run_date)
    {
        if (empty($user_ids))
            return ['total_profit' => 0, 'total_profit_token' => 0, 'invest_ids' => '', 'total_invest_amount' => 0];

        $this->db->select('SUM(amount) as total_profit, GROUP_CONCAT(DISTINCT invest_id ORDER BY invest_id ASC) as invest_ids');
        $this->db->where_in('user_id', $user_ids);
        $this->db->where('type', 'profit');
        $this->db->where('DATE(history_date)', $run_date);
        $history_query = $this->db->get('history')->row();

        $this->db->select('SUM(token_amount) as total_profit');
        $this->db->where_in('user_id', $user_ids);
        $this->db->where('type', 'profit');
        $this->db->where('DATE(history_date)', $run_date);
        $history_token_query = $this->db->get('history')->row();

        $this->db->select('SUM(csq_deposit) as total_invest_amount');
        $this->db->where_in('user_id', $user_ids);
        $this->db->where_in('status', '1');
        $investment_query = $this->db->get('user_investment')->row();

        return [
            'total_profit' => $history_query->total_profit ?? 0,
            'total_profit_token' => $history_token_query->total_profit ?? 0,
            'invest_ids' => $history_query->invest_ids ?? '',
            'total_invest_amount' => $investment_query->total_invest_amount ?? 0
        ];
    }
    /**
     * Get Total ROI, Invest IDs, and Investment Amount for Given Users on a Date
     **/
    private function get_users_roi_total_amount($user_ids, $run_date)
    {

        if (empty($user_ids))
            return ['total_invest_amount' => 0];

        $previous_month_date = date('Y-m-01', strtotime("first day of last month", strtotime($run_date)));
        $month = date('m', strtotime($previous_month_date));
        $year = date('Y', strtotime($previous_month_date));

        $this->db->select('SUM(invest_amount) as total_invest_amount');
        $this->db->where_in('user_id', $user_ids);
        $this->db->where('status', '1');
        $this->db->where('YEAR(created_date)', $year);
        $this->db->where('MONTH(created_date)', $month);

        $investment_query = $this->db->get('user_investment')->row();

        return [
            'total_invest_amount' => $investment_query->total_invest_amount ?? 0
        ];
    }

    /**
     * Fetch Users Rank Achievement - Runs Only Once Per Month
     **/
    public function update_all_users_rank()
    {
        $current_month = date('m');
        $current_year = date('Y');

        // Calculate the previous month and year
        $previous_month = date('m', strtotime('first day of last month'));
        $previous_year = date('Y', strtotime('first day of last month'));

        // Set the date range for the previous month
        $start_date = date("Y-m-01 00:00:00", strtotime("first day of last month"));
        $end_date = date("Y-m-t 23:59:59", strtotime("last day of last month"));

        $users = $this->db->query("SELECT * FROM users WHERE status = '1' ORDER BY id DESC")->result();

        foreach ($users as $user) {
            $check_already_bonus = $this->db->query("
                SELECT id FROM history 
                WHERE type = 'rank_commission' 
                AND history_date >= '$start_date' 
                AND history_date <= '$end_date'
                AND user_id = {$user->id}
            ")->row();

            if (!$check_already_bonus) {
                $this->calculate_user_rank($user->id, $previous_month, $previous_year);
            }
        }
    }

    /**
     * Get Rank Achievement
     **/
    function calculate_user_rank($user_id, $month, $year)
    {

        $run_date = date('Y-m-d');

        $left_leg_users = $this->get_leg_users($user_id, 'left');
        $right_leg_users = $this->get_leg_users($user_id, 'right');

        $left_investment_data = $this->get_users_roi_total_amount($left_leg_users, $run_date);
        $right_investment_data = $this->get_users_roi_total_amount($right_leg_users, $run_date);

        $left_leg_total = str_replace(',', '', $left_investment_data['total_invest_amount']);
        $right_leg_total = str_replace(',', '', $right_investment_data['total_invest_amount']);

        $minimum_leg_investment = min($left_leg_total, $right_leg_total);

        $this->db->select('rank_id');
        $this->db->where('id', $user_id);
        $current_rank = $this->db->get('users')->row()->rank_id ?? 0;

        $achieved_rank_get = $this->find_rank_by_user($minimum_leg_investment, $left_leg_users, $right_leg_users);


        if ($achieved_rank_get['achieved_rank'] > 0) {


            $achieved_rank = $achieved_rank_get['achieved_rank'];
            $achieved_left_user = $achieved_rank_get['achieved_left_user'];
            $achieved_right_user = $achieved_rank_get['achieved_right_user'];


            $this->db->set('rank_id', $achieved_rank);
            $this->db->where('id', $user_id);
            $this->db->update('users');

            $this->db->select('rank_bonus, rank_name');
            $this->db->where('id', $achieved_rank);
            $rank_data = $this->db->get('rank_config')->row();

            if ($rank_data) {

                $rank_bonus = $rank_data->rank_bonus;
                $rank_name = $rank_data->rank_name;

                $token_info = token_info();
                $currency_info = currency_info();

                $rank_commission_usd_price = $rank_bonus;
                $rank_commission_token_price = $rank_bonus * $token_info->currency_value;

                $commission_data = [
                    "user_id" => $user_id,
                    "amount" => $rank_commission_usd_price,
                    "type" => "rank_commission",
                    "history_date" => date('Y-m-d H:i:s'),
                    "date" => date('Y-m-d H:i:s'),
                    "status" => '1',
                    "coin_type" => '1',
                    "token_amount" => $rank_commission_token_price,
                    "description" => "Rank Bonus Achieved: " . $rank_name,
                    "total_left_invest" => $left_leg_total,
                    "total_right_invest" => $right_leg_total,

                    "total_left_users" => $achieved_left_user,
                    "total_right_users" => $achieved_right_user,


                    "coin_id" => $currency_info->id,
                    "token_id" => $token_info->id,
                    "hash_id" => $achieved_rank
                ];

                $this->db->insert("history", $commission_data);

            }

        } else {

            if ($current_rank > 0) {
                $this->db->where('id', $user_id);
                $this->db->update('users', ['rank_id' => 0]);
            }

        }

    }
    /**
     * Find Rank Achievement
     **/
    public function find_rank_by_user($minimum_leg_investment, $left_leg_users, $right_leg_users)
    {



        $this->db->select('*');
        $this->db->where('rank_status', 1);
        $this->db->order_by('rank_order', 'ASC');
        $ranks = $this->db->get('rank_config')->result();

        $achieved_rank = 0;
        $achieved_left_user = null;
        $achieved_right_user = null;

        foreach ($ranks as $rank) {

            if ($rank->rank_order == 1) {

                if ($minimum_leg_investment >= $rank->rank_eligibel_amt) {

                    $achieved_rank = $rank->id;
                    $achieved_left_user = !empty($left_leg_users) ? $left_leg_users[0] : null;
                    $achieved_right_user = !empty($right_leg_users) ? $right_leg_users[0] : null;
                }

            } else {

                if (!empty($left_leg_users)) {
                    $this->db->select('id');
                    $this->db->where_in('id', $left_leg_users);
                    $this->db->where('rank_id >=', $rank->rank_order - 1);
                    $this->db->limit(1);
                    $achieved_left_user_get = $this->db->get('users')->row()->id ?? null;
                }

                if (!empty($right_leg_users)) {
                    $this->db->select('id');
                    $this->db->where_in('id', $right_leg_users);
                    $this->db->where('rank_id >=', $rank->rank_order - 1);
                    $this->db->limit(1);
                    $achieved_right_user_get = $this->db->get('users')->row()->id ?? null;
                }
                if ($achieved_left_user_get && $achieved_right_user_get) {
                    $achieved_left_user = $achieved_left_user_get;
                    $achieved_right_user = $achieved_right_user_get;
                    $achieved_rank = $rank->id;
                }
            }
        }
        return [
            'achieved_rank' => $achieved_rank,
            'achieved_left_user' => $achieved_left_user,
            'achieved_right_user' => $achieved_right_user,
        ];
    }


    //*************************** NEW ONE UPDATE METHOD FUNCTION  ********************************************************/

    // /**
    //  * Sum volume for users on a given date.
    //  * $basis: 'PV'  -> use investments volume (invest_amount) of that date
    //  *         'BV'  -> use ROI token earnings (token_amount) of that date
    //  * Returns ['volume'=>float, 'invest_ids'=>csv, 'user_ids'=>csv]
    //  */


    // private function process_binary_commission_v2($run_date)
    // {
    //     $cfg = $this->db->get_where('commission_config', ['id' => 1])->row();
    //     if (!$cfg || !$cfg->binary_commission_status)
    //         return;

    //     $basis = $cfg->binary_pair_on ?: 'PV';                 // PV or BV
    //     $type = $cfg->binary_pair_type ?: 'percent';          // percent / amount

    //     // Ratio units, e.g. "1:2"
    //     $ratio = $cfg->binary_pair_ratio ?: '1:1';
    //     [$rL, $rR] = array_map('intval', explode(':', $ratio));
    //     if ($rL <= 0 || $rR <= 0) {
    //         $rL = 1;
    //         $rR = 1;
    //     }

    //     $carry_mode = $cfg->binary_carry_forward ?: 'weak_leg'; // none/weak_leg/both
    //     $flush_rule = $cfg->binary_flush_rule ?: 'daily';       // never/daily/weekly/monthly

    //     $token_info = token_info();

    //     // All active users
    //     $users = $this->db->select('id')->from('users')->where('status', '1')->get()->result();

    //     foreach ($users as $u) {
    //         $user_id = $u->id;

    //         // Skip if we already credited today (idempotence)
    //         $exists = $this->db->where('user_id', $user_id)
    //             ->where('type', 'binary_commission')
    //             ->where('DATE(history_date)', $run_date)
    //             ->count_all_results('history');
    //         if ($exists)
    //             continue;

    //         // Get both legs
    //         $left_users = $this->get_leg_users($user_id, 'left');
    //         $right_users = $this->get_leg_users($user_id, 'right');


    //         $this->_dbg('get_leg_users', [
    //             'user_id' => $user_id,
    //             'left_users' => $left_users,
    //             'right_users' => $right_users
    //         ]);


    //         // Compute today's raw volumes by basis
    //         $L = $this->sum_leg_volume($left_users, $run_date, $basis);
    //         $R = $this->sum_leg_volume($right_users, $run_date, $basis);

    //         $left_vol = (float) $L['volume'];
    //         $right_vol = (float) $R['volume'];

    //         // Bring in carry + apply flush rule
    //         $carry = $this->get_carry_row($user_id);

    //         $left_carry = (float) $carry->left_carry;
    //         $right_carry = (float) $carry->right_carry;

    //         $this->_dbg('2_CRON_RAW_VOLUME', ['sum_left_roi' => $left_vol, 'sum_right_roi' => $right_vol, 'left_carry' => $left_carry, 'right_carry' => $right_carry, 'condition' => $flush_rule !== 'never' && !empty($carry->last_flush_at)]);

    //         // Reset (flush) according to rule
    //         if ($flush_rule !== 'never' && !empty($carry->last_flush_at)) {
    //             $last = new DateTime($carry->last_flush_at);
    //             $cur = new DateTime($run_date);

    //             $flush_now = false;
    //             if ($flush_rule === 'daily')
    //                 $flush_now = $cur > $last;
    //             if ($flush_rule === 'weekly')
    //                 $flush_now = (int) $cur->format('oW') !== (int) $last->format('oW');
    //             if ($flush_rule === 'monthly')
    //                 $flush_now = $cur->format('Ym') !== $last->format('Ym');

    //             if ($flush_now) {
    //                 $left_carry = $right_carry = 0;
    //             }
    //         }

    //         // Add carry to today’s volume
    //         $left_vol += $left_carry;
    //         $right_vol += $right_carry;

    //         if ($left_vol <= 0 && $right_vol <= 0) {
    //             // nothing to pair, just update flush marker
    //             $this->save_carry($user_id, $left_vol, $right_vol, $run_date);
    //             continue;
    //         }

    //         // Calculate pairs by ratio a:b
    //         $pairs = floor(min($left_vol / $rL, $right_vol / $rR));

    //         $this->_dbg('3_CRON_RAW_VOLUME', ['sum_left_roi' => $left_vol, 'sum_right_roi' => $right_vol, 'left_carry' => $left_carry, 'right_carry' => $right_carry, 'condition' => $flush_rule !== 'never' && !empty($carry->last_flush_at)]);

    //         if ($pairs <= 0) {
    //             // no complete pair, carry forward diffs as per rule
    //             $new_left_carry = $left_vol;
    //             $new_right_carry = $right_vol;

    //             if ($carry_mode === 'none') {
    //                 $new_left_carry = 0;
    //                 $new_right_carry = 0;
    //             }
    //             if ($carry_mode === 'weak_leg') {
    //                 if ($left_vol <= $right_vol) {
    //                     $new_right_carry = 0;
    //                 } else {
    //                     $new_left_carry = 0;
    //                 }
    //             }

    //             $this->_dbg('4_CRON_RAW_VOLUME', ['sum_left_roi' => $left_vol, 'sum_right_roi' => $right_vol, 'left_carry' => $left_carry, 'right_carry' => $right_carry, 'condition' => $flush_rule !== 'never' && !empty($carry->last_flush_at)]);

    //             $this->save_carry($user_id, $new_left_carry, $new_right_carry, $run_date);
    //             continue;
    //         }

    //         // Matched volumes consumed by those pairs
    //         $matched_left = $pairs * $rL;
    //         $matched_right = $pairs * $rR;

    //         // Remaining after matching
    //         $rem_left = $left_vol - $matched_left;
    //         $rem_right = $right_vol - $matched_right;

    //         // Apply carry rule on remainders
    //         $new_left_carry = 0;
    //         $new_right_carry = 0;
    //         if ($carry_mode === 'both') {
    //             $new_left_carry = max(0, $rem_left);
    //             $new_right_carry = max(0, $rem_right);
    //         } elseif ($carry_mode === 'weak_leg') {
    //             if ($rem_left <= $rem_right) {
    //                 $new_left_carry = max(0, $rem_left);
    //             } else {
    //                 $new_right_carry = max(0, $rem_right);
    //             }
    //         }
    //         // 'none' keeps both 0

    //         $this->_dbg('5_CRON_RAW_VOLUME', ['sum_left_roi' => $left_vol, 'sum_right_roi' => $right_vol, 'left_carry' => $left_carry, 'right_carry' => $right_carry, 'condition' => $flush_rule !== 'never' && !empty($carry->last_flush_at)]);

    //         $this->save_carry($user_id, $new_left_carry, $new_right_carry, $run_date);

    //         // Commission base:
    //         // - If percent: apply % on the *weak leg matched volume* (standard practice)
    //         //   weak matched = pairs * min(rL, rR)
    //         // - If amount: fixed amount per pair
    //         $weak_unit = min($rL, $rR);
    //         $base_volume = $pairs * $weak_unit; // in PV/BV units

    //         if ($type === 'percent') {
    //             $percent = (float) $cfg->binary_pair_percent;
    //             $token_amt = ($base_volume * $percent) / 100.0;  // token units if BV; PV has no “token” – we still treat it as points and pay as token by ratio
    //         } else {
    //             $per_pair = (float) $cfg->binary_pair_amount;
    //             $token_amt = $pairs * $per_pair;                 // token units (you can choose to treat as BV-equivalent)
    //         }

    //         // If basis was PV, you may want to convert PV→token at your own internal rate.
    //         // Here we assume PV == token units for payout; adjust as needed.

    //         $this->_dbg('6_CRON_RAW_VOLUME', ['sum_left_roi' => $left_vol, 'sum_right_roi' => $right_vol, 'left_carry' => $left_carry, 'right_carry' => $right_carry, 'condition' => $flush_rule !== 'never' && !empty($carry->last_flush_at)]);

    //         $usd_amt = $this->token_to_usd($token_amt);

    //         // Credit with caps/threshold and extra meta for reporting
    //         $meta = [
    //             "total_left_roi" => ($basis === 'BV') ? $L['volume'] : null,
    //             "total_right_roi" => ($basis === 'BV') ? $R['volume'] : null,
    //             "total_left_invest" => ($basis === 'PV') ? $L['volume'] : null,
    //             "total_right_invest" => ($basis === 'PV') ? $R['volume'] : null,
    //             "total_left_users" => $L['user_ids'],
    //             "total_right_users" => $R['user_ids'],
    //             "total_left_invest_ids" => $L['invest_ids'],
    //             "total_right_invest_ids" => $R['invest_ids'],
    //             "pair_ratio_used" => $ratio,
    //             "pairs_count" => $pairs,
    //             "basis" => $basis,
    //         ];

    //         $this->_dbg('7_CRON_RAW_VOLUME', ['sum_left_roi' => $left_vol, 'sum_right_roi' => $right_vol, 'left_carry' => $left_carry, 'right_carry' => $right_carry, 'condition' => $flush_rule !== 'never' && !empty($carry->last_flush_at)]);

    //         $this->credit_binary_history($user_id, $run_date, $usd_amt, $token_amt, $meta);
    //     }
    // }

    // private function sum_leg_volume(array $user_ids, string $run_date, string $basis = 'BV'): array
    // {
    //     if (empty($user_ids)) {
    //         return ['volume' => 0, 'invest_ids' => '', 'user_ids' => ''];
    //     }

    //     $ids_csv = implode(',', $user_ids);
    //     $this->db->reset_query();

    //     if ($basis === 'PV') {
    //         // Sum the day's investments (or active investments created that day)
    //         $q = $this->db->select('SUM(invest_amount) AS vol, GROUP_CONCAT(id) AS invest_ids', false)
    //             ->from('user_investment')
    //             ->where_in('user_id', $user_ids)
    //             ->where('status', 1)
    //             ->get()->row();
    //         return [
    //             'volume' => (float) ($q->vol ?? 0),
    //             'invest_ids' => (string) ($q->invest_ids ?? ''),
    //             'user_ids' => $ids_csv
    //         ];
    //     }

    //     // BV: use ROI token earnings posted for that date
    //     $q = $this->db->select('SUM(amount) AS vol')
    //         ->from('history')
    //         ->where_in('user_id', $user_ids)
    //         ->where('type', 'profit')                    // your ROI rows
    //         ->where('DATE(history_date)', $run_date)
    //         ->get()->row();

    //     $this->_dbg('1_CRON_SUM_LEG_VOLUME_PAIR_CALC', [
    //         'volume' => (float) ($q->vol ?? 0),
    //         'invest_ids' => '',
    //         'user_ids' => $ids_csv
    //     ]);

    //     return [
    //         'volume' => (float) ($q->vol ?? 0),
    //         'invest_ids' => '',
    //         'user_ids' => $ids_csv
    //     ];
    // }

    // private function get_carry_row($user_id)
    // {
    //     $row = $this->db->get_where('binary_carry', ['user_id' => $user_id])->row();

    //     if (!$row) {
    //         $this->db->insert('binary_carry', [
    //             'user_id' => $user_id,
    //             'left_carry' => 0,
    //             'right_carry' => 0,
    //             'period_key' => null,
    //             'updated_at' => date('Y-m-d H:i:s')
    //         ]);
    //         $row = $this->db->get_where('binary_carry', ['user_id' => $user_id])->row();
    //     }

    //     return $row;
    // }

    // /** Update carry row. */
    // private function save_carry($user_id, $left, $right, $run_date)
    // {
    //     $this->db->update('binary_carry', [
    //         'left_carry' => (float) $left,
    //         'right_carry' => (float) $right,
    //         'last_flush_at' => $run_date
    //     ], ['user_id' => $user_id]);
    // }


    // /** Convert token amount to USD with your helpers. */
    // private function token_to_usd($token_amount)
    // {
    //     $token_info = token_info();
    //     if (empty($token_info->currency_value) || $token_info->currency_value <= 0)
    //         return 0;
    //     return (float) $token_amount / (float) $token_info->currency_value;
    // }

    // /** Credit history row (respects payout caps & threshold). */
    // private function credit_binary_history($user_id, $run_date, $usd_amount, $token_amount, array $meta)
    // {
    //     $token_info = token_info();
    //     $currency_info = currency_info();

    //     // Global caps from config
    //     $cfg = $this->db->get_where('commission_config', ['id' => 1])->row();
    //     $usd_amount = (float) $usd_amount;

    //     // Daily cap
    //     if (!empty($cfg->payout_daily_cap) && $cfg->payout_daily_cap > 0) {
    //         // Sum today's credited binary_commission
    //         $sum = $this->db->select('SUM(amount) AS s')->from('history')
    //             ->where('user_id', $user_id)->where('type', 'binary_commission')
    //             ->where('DATE(history_date)', $run_date)->get()->row();
    //         $already = (float) ($sum->s ?? 0);
    //         $room = (float) $cfg->payout_daily_cap - $already;
    //         if ($room <= 0)
    //             return false;
    //         if ($usd_amount > $room) {
    //             $token_amount = $token_amount * ($room / $usd_amount);
    //             $usd_amount = $room;
    //         }
    //     }

    //     // Min threshold
    //     if (!empty($cfg->payout_threshold_min) && $usd_amount < $cfg->payout_threshold_min) {
    //         return false;
    //     }

    //     $payload = array_merge([
    //         "user_id" => $user_id,
    //         "amount" => round($usd_amount, 6),
    //         "type" => "binary_commission",
    //         "history_date" => $run_date . ' 10:00:00',
    //         "date" => $run_date . ' 10:00:00',
    //         "status" => '1',
    //         "coin_type" => '1',
    //         "token_amount" => round($token_amount, 6),
    //         "description" => "Binary Commission Earned",
    //         "coin_id" => $currency_info->id ?? null,
    //         "token_id" => $token_info->id ?? null,
    //     ], $meta);

    //     $this->db->insert('history', $payload);
    //     return true;
    // }

    //************** matching bonus check  **************************/

    // private function process_matching_bonus($run_date)
    // {
    //     $cfg = $this->db->get_where('commission_config', ['id' => 1])->row();
    //     if (!$cfg || !$cfg->matching_bonus_status)
    //         return;

    //     $levels = (int) $cfg->matching_levels;
    //     if ($levels <= 0)
    //         return;

    //     $percents = array_map('floatval', array_filter(array_map('trim', explode(',', (string) $cfg->matching_percents))));
    //     if (empty($percents))
    //         return;

    //     // Pull all binary commissions posted on run_date
    //     $rows = $this->db->select('user_id, amount, token_amount, id')
    //         ->from('history')
    //         ->where('type', 'binary_commission')
    //         ->where('DATE(history_date)', $run_date)
    //         ->get()->result();

    //     foreach ($rows as $row) {
    //         $earner_id = $row->user_id;
    //         $bin_usd = (float) $row->amount;
    //         $bin_token = (float) $row->token_amount;

    //         // Walk up sponsor chain
    //         $upline_id = $this->get_sponsor_id($earner_id); // You must have this helper; see stub below
    //         for ($lv = 1; $lv <= $levels && $upline_id; $lv++) {

    //             $percent = isset($percents[$lv - 1]) ? (float) $percents[$lv - 1] : 0;
    //             if ($percent <= 0) {
    //                 $upline_id = $this->get_sponsor_id($upline_id);
    //                 continue;
    //             }

    //             // Avoid duplicate if run twice
    //             $exists = $this->db->where('user_id', $upline_id)
    //                 ->where('type', 'matching_bonus')
    //                 ->where('ref_history_id', $row->id)      // reference child’s binary row
    //                 ->count_all_results('history');
    //             if ($exists) {
    //                 $upline_id = $this->get_sponsor_id($upline_id);
    //                 continue;
    //             }

    //             $bonus_token = $bin_token * $percent / 100.0;
    //             $bonus_usd = $this->token_to_usd($bonus_token);

    //             $meta = [
    //                 "description" => "Matching Bonus L{$lv} from #{$earner_id}",
    //                 "ref_history_id" => $row->id,
    //                 "source_user_id" => $earner_id,
    //                 "level_no" => $lv
    //             ];

    //             $this->credit_matching_history($upline_id, $run_date, $bonus_usd, $bonus_token, $meta);

    //             $upline_id = $this->get_sponsor_id($upline_id);
    //         }
    //     }
    // }

    // /** Minimal “credit” for matching (separate type). */
    // private function credit_matching_history($user_id, $run_date, $usd_amount, $token_amount, array $meta)
    // {
    //     $token_info = token_info();
    //     $currency_info = currency_info();

    //     $cfg = $this->db->get_where('commission_config', ['id' => 1])->row();
    //     $usd_amount = (float) $usd_amount;

    //     // You may want to reuse payout caps; here we only check min threshold
    //     if (!empty($cfg->payout_threshold_min) && $usd_amount < $cfg->payout_threshold_min) {
    //         return false;
    //     }

    //     $payload = array_merge([
    //         "user_id" => $user_id,
    //         "amount" => round($usd_amount, 6),
    //         "type" => "matching_bonus",
    //         "history_date" => $run_date . ' 11:00:00',
    //         "date" => $run_date . ' 11:00:00',
    //         "status" => '1',
    //         "coin_type" => '1',
    //         "token_amount" => round($token_amount, 6),
    //         "coin_id" => $currency_info->id ?? null,
    //         "token_id" => $token_info->id ?? null,
    //     ], $meta);

    //     $this->db->insert('history', $payload);
    //     return true;
    // }

    /** Stub: get sponsor/upline id; replace with your actual logic/column name. */
    // private function get_sponsor_id($user_id)
    // {
    //     $u = $this->db->select('sponser')->from('users')->where('id', $user_id)->get()->row();
    //     return $u ? (int) $u->sponser : null;
    // }




// ============================================================================
// BINARY PAIR COMMISSION (PACKAGE-WISE) + MATCHING BONUS (PACKAGE JSON)
// DB: commission_config + package_config
// ============================================================================

    private function process_binary_commission_v2($run_date)
    {
        $run_date = date('Y-m-d', strtotime($run_date));

        $cfg = $this->db->get_where('commission_config', ['id' => 1])->row();
        if (!$cfg || (int) ($cfg->binary_commission_status ?? 0) !== 1) {
            $this->_dbg('CRON_BINARY_SKIP_1', ['reason' => 'binary_commission_status disabled']);
            return;
        }

        // Global settings from commission_config
        $ratio = trim((string) ($cfg->binary_pair_ratio ?? '1:1'));
        $ratioParts = explode(':', $ratio);
        $rL = isset($ratioParts[0]) ? (int) $ratioParts[0] : 1;
        $rR = isset($ratioParts[1]) ? (int) $ratioParts[1] : 1;
        if ($rL <= 0)
            $rL = 1;
        if ($rR <= 0)
            $rR = 1;

        // Carry forward global controls
        $carry_enabled = (int) ($cfg->carry_forward_status ?? 1) === 1;
        $carry_mode = 'both'; // with current DB you don't store weak_leg/none; use both or implement custom logic
        $flush_rule = strtoupper((string) ($cfg->carry_forward_mode ?? 'LIFETIME')); // LIFETIME/DAILY/WEEKLY/MONTHLY
        $carry_cap = (float) ($cfg->carry_forward_cap ?? 0);

        // Basis: your DB doesn't have binary_pair_on, so choose here
        // If you want PV pair on investment: set PV
        // If you want BV pair on ROI profit token: set BV
        $basis = 'BV';

        // All active users
        $users = $this->db->select('id')->from('users')->where('status', '1')->get()->result();

        foreach ($users as $u) {
            $user_id = (int) $u->id;

            // idempotence: already credited today?
            $exists = $this->db->where('user_id', $user_id)
                ->where('type', 'binary_commission')
                ->where('DATE(history_date)', $run_date)
                ->count_all_results('history');
            if ($exists)
                continue;

            // Find latest active package for this user (adjust table/column if needed)
            $inv = $this->db->select('package_id')
                ->from('user_investment')
                ->where('user_id', $user_id)
                ->where('status', 1)
                ->order_by('id', 'DESC')
                ->limit(1)
                ->get()->row();

            $package_id = (int) ($inv->package_id ?? 0);
            if ($package_id <= 0) {
                $this->_dbg('CRON_BINARY_SKIP_2', ['user_id' => $user_id, 'reason' => 'no active package']);
                continue;
            }

            $pkg = $this->db->get_where('package_config', ['id' => $package_id, 'status' => 1])->row();
            if (!$pkg) {
                $this->_dbg('CRON_BINARY_SKIP_3', ['user_id' => $user_id, 'package_id' => $package_id, 'reason' => 'package not found']);
                continue;
            }

            // Pair commission must be ON in package
            if ((int) ($pkg->pair_commission_status ?? 0) !== 1) {
                $this->_dbg('CRON_BINARY_SKIP_4', ['user_id' => $user_id, 'package_id' => $package_id, 'reason' => 'pair_commission_status off']);
                continue;
            }

            // Get legs
            $left_users = $this->get_leg_users($user_id, 'left');
            $right_users = $this->get_leg_users($user_id, 'right');

            // Compute today's volumes
            $L = $this->sum_leg_volume($left_users, $run_date, $basis);
            $R = $this->sum_leg_volume($right_users, $run_date, $basis);

            $left_vol = (float) ($L['volume'] ?? 0);
            $right_vol = (float) ($R['volume'] ?? 0);

            // Carry rows
            $carry = $this->get_carry_row($user_id);
            $left_carry = (float) ($carry->left_carry ?? 0);
            $right_carry = (float) ($carry->right_carry ?? 0);

            // Flush carry if needed (based on carry_forward_mode)
            if ($carry_enabled && !empty($carry->last_flush_at)) {
                $last = new DateTime(date('Y-m-d', strtotime($carry->last_flush_at)));
                $cur = new DateTime($run_date);

                $flush_now = false;
                if ($flush_rule === 'DAILY') {
                    $flush_now = $cur > $last;
                } elseif ($flush_rule === 'WEEKLY') {
                    $flush_now = (int) $cur->format('oW') !== (int) $last->format('oW');
                } elseif ($flush_rule === 'MONTHLY') {
                    $flush_now = $cur->format('Ym') !== $last->format('Ym');
                } else {
                    // LIFETIME => never flush
                    $flush_now = false;
                }

                if ($flush_now) {
                    $left_carry = 0;
                    $right_carry = 0;
                }
            }

            // Add carry to volumes
            if ($carry_enabled) {
                $left_vol += $left_carry;
                $right_vol += $right_carry;
            }

            $this->_dbg('CRON_BINARY_VOL', [
                'user_id' => $user_id,
                'package_id' => $package_id,
                'basis' => $basis,
                'left_vol' => $left_vol,
                'right_vol' => $right_vol,
                'left_carry' => $left_carry,
                'right_carry' => $right_carry,
                'ratio' => "{$rL}:{$rR}",
                'flush_rule' => $flush_rule,
                'carry_enabled' => $carry_enabled ? 1 : 0
            ]);

            if ($left_vol <= 0 || $right_vol <= 0) {
                // update carry marker
                $this->save_carry(
                    $user_id,
                    $this->cap_value($left_vol, $carry_cap),
                    $this->cap_value($right_vol, $carry_cap),
                    $run_date
                );
                continue;
            }

            // pairs
            $pairs = (int) floor(min($left_vol / $rL, $right_vol / $rR));

            // Apply package daily max pairs
            $daily_max_pairs = (int) ($pkg->daily_max_pairs ?? 0);
            if ($daily_max_pairs > 0 && $pairs > $daily_max_pairs) {
                $pairs = $daily_max_pairs;
            }

            if ($pairs <= 0) {
                $this->save_carry(
                    $user_id,
                    $this->cap_value($left_vol, $carry_cap),
                    $this->cap_value($right_vol, $carry_cap),
                    $run_date
                );
                continue;
            }

            // matched
            $matched_left = $pairs * $rL;
            $matched_right = $pairs * $rR;

            $rem_left = max(0, $left_vol - $matched_left);
            $rem_right = max(0, $right_vol - $matched_right);

            // Carry forward remainder (we store both sides)
            $new_left_carry = $carry_enabled ? $rem_left : 0;
            $new_right_carry = $carry_enabled ? $rem_right : 0;

            // cap carry
            $new_left_carry = $this->cap_value($new_left_carry, $carry_cap);
            $new_right_carry = $this->cap_value($new_right_carry, $carry_cap);

            $this->save_carry($user_id, $new_left_carry, $new_right_carry, $run_date);

            // Commission base: weak-leg matched units
            $weak_unit = min($rL, $rR);
            $base_volume = $pairs * $weak_unit;

            // ✅ PAYOUT SETTINGS FROM PACKAGE (pair_commission)
            $pair_type = strtolower((string) ($pkg->pair_commission_type ?? 'percent')); // percent/amount
            $pair_value = (float) ($pkg->pair_commission ?? 0);

            if ($pair_value <= 0) {
                $this->_dbg('CRON_BINARY_SKIP_5', ['user_id' => $user_id, 'reason' => 'pair_commission value = 0']);
                continue;
            }

            if ($pair_type === 'percent') {
                $token_amt = ($base_volume * $pair_value) / 100.0;
            } else {
                $token_amt = $pairs * $pair_value;
            }

            $usd_amt = $this->token_to_usd($token_amt);

            $meta = [
                "package_id" => $package_id,
                "pair_ratio_used" => "{$rL}:{$rR}",
                "pairs_count" => $pairs,
                "basis" => $basis,
                "total_left_users" => $L['user_ids'] ?? '',
                "total_right_users" => $R['user_ids'] ?? '',
                "total_left_invest_ids" => $L['invest_ids'] ?? '',
                "total_right_invest_ids" => $R['invest_ids'] ?? '',
            ];

            $ok = $this->credit_binary_history($user_id, $run_date, $usd_amt, $token_amt, $meta);

            // ✅ if binary credited, do matching from package json
            if ($ok) {
                $this->process_matching_bonus_for_user($run_date, $user_id, $package_id);
            }
        }
    }

    /**
     * Sum volume for users on a given date.
     * PV -> user_investment.invest_amount for that date
     * BV -> history.token_amount where type='profit' for that date
     */
    private function sum_leg_volume(array $user_ids, string $run_date, string $basis = 'BV'): array
    {
        if (empty($user_ids)) {
            return ['volume' => 0, 'invest_ids' => '', 'user_ids' => ''];
        }

        $ids_csv = implode(',', $user_ids);
        $this->db->reset_query();

        if (strtoupper($basis) === 'PV') {
            // IMPORTANT: filter by date (otherwise it sums lifetime)
            $q = $this->db->select('COALESCE(SUM(invest_amount),0) AS vol, GROUP_CONCAT(id) AS invest_ids', false)
                ->from('user_investment')
                ->where_in('user_id', $user_ids)
                ->where('status', 1)
                ->where('DATE(created_date)', $run_date) // change if your column is different
                ->get()->row();

            return [
                'volume' => (float) ($q->vol ?? 0),
                'invest_ids' => (string) ($q->invest_ids ?? ''),
                'user_ids' => $ids_csv
            ];
        }

        // BV: ROI token earnings posted for that date (token_amount)
        $q = $this->db->select('COALESCE(SUM(token_amount),0) AS vol', false)
            ->from('history')
            ->where_in('user_id', $user_ids)
            ->where('type', 'profit')
            ->where('status', '1')
            ->where('DATE(history_date)', $run_date)
            ->get()->row();

        return [
            'volume' => (float) ($q->vol ?? 0),
            'invest_ids' => '',
            'user_ids' => $ids_csv
        ];
    }

    private function get_carry_row($user_id)
    {
        $row = $this->db->get_where('binary_carry', ['user_id' => (int) $user_id])->row();

        if (!$row) {
            $this->db->insert('binary_carry', [
                'user_id' => (int) $user_id,
                'left_carry' => 0,
                'right_carry' => 0,
                'period_key' => null,
                'last_flush_at' => null,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            $row = $this->db->get_where('binary_carry', ['user_id' => (int) $user_id])->row();
        }

        return $row;
    }

    private function save_carry($user_id, $left, $right, $run_date)
    {
        $this->db->update('binary_carry', [
            'left_carry' => (float) $left,
            'right_carry' => (float) $right,
            'last_flush_at' => $run_date,
            'updated_at' => date('Y-m-d H:i:s')
        ], ['user_id' => (int) $user_id]);
    }

    private function cap_value($val, $cap)
    {
        $val = (float) $val;
        $cap = (float) $cap;
        if ($cap > 0 && $val > $cap)
            return $cap;
        return $val;
    }

    private function token_to_usd($token_amount)
    {
        $token_info = token_info();
        $cv = (float) ($token_info->currency_value ?? 0);
        if ($cv <= 0)
            return 0;
        return (float) $token_amount / $cv;
    }

    /**
     * Credit binary history row.
     * NOTE: your commission_config table has NO payout_daily_cap and payout_threshold_min
     * so we do NOT check those (remove warnings).
     */
    private function credit_binary_history($user_id, $run_date, $usd_amount, $token_amount, array $meta)
    {
        $token_info = token_info();
        $currency_info = currency_info();

        $payload = array_merge([
            "user_id" => (int) $user_id,
            "amount" => round((float) $usd_amount, 6),
            "type" => "binary_commission",
            "history_date" => $run_date . ' 10:00:00',
            "date" => $run_date . ' 10:00:00',
            "status" => '1',
            "coin_type" => '1',
            "token_amount" => round((float) $token_amount, 6),
            "description" => "Binary Commission Earned",
            "coin_id" => $currency_info->id ?? null,
            "token_id" => $token_info->id ?? null,
        ], $meta);

        $this->db->insert('history', $payload);
        return $this->db->affected_rows() > 0;
    }

    // ============================================================================
    // MATCHING BONUS (PACKAGE JSON)
    // matching_bonus_json example: [10,2,4,6]
    // ============================================================================

    private function process_matching_bonus_for_user($run_date, $earner_id, $package_id)
    {
        $run_date = date('Y-m-d', strtotime($run_date));
        $earner_id = (int) $earner_id;
        $package_id = (int) $package_id;

        $cfg = $this->db->get_where('commission_config', ['id' => 1])->row();
        if (!$cfg || (int) ($cfg->matching_bonus_status ?? 0) !== 1)
            return;

        $pkg = $this->db->get_where('package_config', ['id' => $package_id, 'status' => 1])->row();
        if (!$pkg)
            return;

        $percents = json_decode((string) ($pkg->matching_bonus_json ?? '[]'), true);
        if (!is_array($percents))
            $percents = [];
        $percents = array_values(array_map('floatval', $percents));
        $levels = count($percents);
        if ($levels <= 0)
            return;

        // Find earner's binary row for the day
        $bin = $this->db->select('id, amount, token_amount')
            ->from('history')
            ->where('user_id', $earner_id)
            ->where('type', 'binary_commission')
            ->where('DATE(history_date)', $run_date)
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get()->row();

        if (!$bin)
            return;

        $bin_token = (float) ($bin->token_amount ?? 0);
        if ($bin_token <= 0)
            return;

        // Walk upline
        $upline_id = $this->get_sponsor_id($earner_id);

        for ($lv = 1; $lv <= $levels && $upline_id; $lv++) {
            $percent = (float) ($percents[$lv - 1] ?? 0);
            if ($percent <= 0) {
                $upline_id = $this->get_sponsor_id($upline_id);
                continue;
            }

            // Prevent duplicates
            $exists = $this->db->where('user_id', (int) $upline_id)
                ->where('type', 'matching_bonus')
                ->where('ref_history_id', (int) $bin->id)
                ->count_all_results('history');
            if ($exists) {
                $upline_id = $this->get_sponsor_id($upline_id);
                continue;
            }

            $bonus_token = $bin_token * $percent / 100.0;
            $bonus_usd = $this->token_to_usd($bonus_token);

            $meta = [
                "description" => "Matching Bonus L{$lv} from #{$earner_id}",
                "ref_history_id" => (int) $bin->id,
                "source_user_id" => $earner_id,
                "level_no" => $lv,
                "package_id" => $package_id
            ];

            $this->credit_matching_history($upline_id, $run_date, $bonus_usd, $bonus_token, $meta);

            $upline_id = $this->get_sponsor_id($upline_id);
        }
    }

    private function process_matching_bonus($run_date)
    {
        $run_date = date('Y-m-d', strtotime($run_date));

        // Global switch only
        $cfg = $this->db->get_where('commission_config', ['id' => 1])->row();
        if (!$cfg || empty($cfg->matching_bonus_status)) {
            return;
        }

        // Cache package matching arrays
        $packages = $this->db->select('id, matching_bonus_json')
            ->from('package_config')
            ->where('status', 1)
            ->get()->result();

        $pkgMatch = [];
        foreach ($packages as $p) {
            $arr = json_decode((string) $p->matching_bonus_json, true);
            if (!is_array($arr))
                $arr = [];
            $pkgMatch[(int) $p->id] = array_map('floatval', $arr);
        }
        if (empty($pkgMatch))
            return;

        // Pull all binary commissions posted on run_date
        $rows = $this->db->select('id, user_id, token_amount')
            ->from('history')
            ->where('type', 'binary_commission')
            ->where('DATE(history_date)', $run_date)
            ->get()->result();

        foreach ($rows as $row) {

            $earner_id = (int) $row->user_id;
            $bin_token = (float) $row->token_amount;

            if ($bin_token <= 0)
                continue; // no token => no matching

            // ✅ latest active investment for earner (same package can repeat, id differs)
            $inv = $this->db->select('id, package_id')
                ->from('user_investment')
                ->where('user_id', $earner_id)
                ->where('status', 1)
                ->order_by('id', 'DESC')
                ->limit(1)
                ->get()->row();

            if (!$inv || empty($inv->package_id))
                continue;

            $package_id = (int) $inv->package_id;
            $percents = $pkgMatch[$package_id] ?? [];
            $levels = count($percents);

            if ($levels <= 0)
                continue;

            // Walk up sponsor chain
            $upline_id = $this->get_sponsor_id($earner_id);

            for ($lv = 1; $lv <= $levels && $upline_id; $lv++) {

                $percent = (float) ($percents[$lv - 1] ?? 0);
                if ($percent <= 0) {
                    $upline_id = $this->get_sponsor_id($upline_id);
                    continue;
                }

                // Avoid duplicate if cron runs twice
                $exists = $this->db->where('user_id', (int) $upline_id)
                    ->where('type', 'matching_bonus')
                    ->where('ref_history_id', (int) $row->id)
                    ->where('level_count', $lv)   // ✅ use existing column
                    ->count_all_results('history');

                if ($exists) {
                    $upline_id = $this->get_sponsor_id($upline_id);
                    continue;
                }

                $bonus_token = ($bin_token * $percent) / 100.0;
                if ($bonus_token <= 0) {
                    $upline_id = $this->get_sponsor_id($upline_id);
                    continue;
                }

                $bonus_usd = $this->token_to_usd($bonus_token);

                $meta = [
                    "description" => "Matching Bonus L{$lv} from #{$earner_id}",
                    "ref_history_id" => (int) $row->id,
                    "from_id" => $earner_id,   // ✅ your column
                    "level_count" => $lv,          // ✅ your column
                    "invest_id" => (int) $inv->id, // ✅ your column exists
                    "earn_by" => "token",
                ];

                $this->credit_matching_history((int) $upline_id, $run_date, $bonus_usd, $bonus_token, $meta);

                $upline_id = $this->get_sponsor_id($upline_id);
            }
        }
    }

    private function credit_matching_history($user_id, $run_date, $usd_amount, $token_amount, array $meta)
    {
        $user_id = (int) $user_id;
        $run_date = date('Y-m-d', strtotime($run_date));

        $token_info = token_info();
        $currency_info = currency_info();

        $payload = array_merge([
            "user_id" => $user_id,
            "amount" => round((float) $usd_amount, 6),
            "type" => "matching_bonus",
            "history_date" => $run_date . ' 11:00:00',
            "date" => $run_date . ' 11:00:00',
            "status" => '1',
            "coin_type" => '1',
            "token_amount" => round((float) $token_amount, 6),
            "coin_id" => $currency_info->id ?? null,
            "token_id" => $token_info->id ?? null,
        ], $meta);

        $this->db->insert('history', $payload);
        return true;
    }

    private function get_sponsor_id($user_id)
    {
        $u = $this->db->select('sponser')->from('users')->where('id', (int) $user_id)->get()->row();
        return $u ? (int) $u->sponser : null;
    }   


}
