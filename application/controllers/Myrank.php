<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Myrank extends CI_Controller
{


    public function __construct()
    {

        parent::__construct();
        $this->load->database();

    }

    private function _dbg($tag, $payload = null)
    {
        if (is_array($payload) || is_object($payload)) {
            $payload = json_encode($payload);
        }
        log_message('debug', "[CommissionEngine][$tag] {$payload}");
    }

    // /**
    /* ============================================
     * MONTHLY RANK COMMISSION CRON (COPY-PASTE)
     * - Runs only once per month (previous month)
     * - Uses ROI total from history(type='profit') OR roi_credits if exists
     * - Prevents duplicate rank_commission payout
     * ============================================
     * URL: /cron/run_monthly_rank_commission
     */
    public function run_monthly_rank_commission_AI()
    {
        // Previous month window
        $start_date = date("Y-m-01 00:00:00", strtotime("first day of last month"));
        $end_date = date("Y-m-t 23:59:59", strtotime("last day of last month"));
        $run_month = date("Y-m", strtotime("first day of last month")); // e.g. 2026-01

        // Ensure run log table exists (auto-create if not)
        // $this->_ensure_rank_run_log_table();

        $this->_dbg('RANK_STARTED', ['Today' => $start_date]);

        // Lock: run only once per month
        $this->db->query(
            "INSERT IGNORE INTO rank_cron_runs (run_month, started_at, status) VALUES (?, NOW(), 'RUNNING')",
            [$run_month]
        );

        if ($this->db->affected_rows() === 0) {
            echo "Rank commission already executed or running for {$run_month}";
            $this->_dbg('CRON_RANK_no_affected', []);
            return;
        }

        try {
            $users = $this->db->query("SELECT id FROM users WHERE status='1' ORDER BY id ASC")->result();

            foreach ($users as $u) {
                $this->_calculate_user_rank_commission_by_range((int) $u->id, $start_date, $end_date);
            }

            $this->db->where('run_month', $run_month)->update('rank_cron_runs', [
                'status' => 'DONE',
                'finished_at' => date('Y-m-d H:i:s'),
            ]);

            echo "Rank commission CRON completed for {$run_month}";
        } catch (Throwable $e) {

            $this->db->where('run_month', $run_month)->update('rank_cron_runs', [
                'status' => 'FAILED',
                'finished_at' => date('Y-m-d H:i:s'),
                'note' => substr($e->getMessage(), 0, 240),
            ]);

            log_message('error', '[RANK_CRON] ' . $e->getMessage());
            show_error($e->getMessage());
        }
    }

    /**
     * ----------------------------
     * CORE: calculate rank + pay rank_commission (previous month)
     * ----------------------------
     */
    private function _calculate_user_rank_commission_by_range($user_id, $start_date, $end_date)
    {
        // 1) Get left/right leg users (you MUST have placement structure in users table)
        $left_leg_users = $this->_get_leg_users($user_id, 'left');
        $right_leg_users = $this->_get_leg_users($user_id, 'right');

        $this->_dbg('CRON_RANK_LEFT_RIGHT', ['left_leg' => $left_leg_users, 'right_leg' => $right_leg_users]);

        // 2) Compute totals for previous month
        $left_total = $this->_get_users_monthly_volume($left_leg_users, $start_date, $end_date);
        $right_total = $this->_get_users_monthly_volume($right_leg_users, $start_date, $end_date);

        $minimum_leg_investment = min($left_total, $right_total);

        // 3) Current rank
        $current_rank = (int) ($this->db->select('rank_id')->where('id', $user_id)->get('users')->row()->rank_id ?? 0);

        // 4) Find achieved rank (based on minimum leg volume + downline rank rule)
        $achieved = $this->_find_rank_by_user($minimum_leg_investment, $left_leg_users, $right_leg_users);

        if ((int) $achieved['achieved_rank'] <= 0) {
            // optional reset
            if ($current_rank > 0) {
                $this->db->where('id', $user_id)->update('users', ['rank_id' => 0]);
            }
            return;
        }

        $achieved_rank = (int) $achieved['achieved_rank'];
        $ach_left_user = $achieved['achieved_left_user'];
        $ach_right_user = $achieved['achieved_right_user'];

        // 5) Update user rank
        $this->db->where('id', $user_id)->update('users', ['rank_id' => $achieved_rank]);

        // 6) Prevent duplicate payout for the same month+rank
        $already_paid = $this->db->query("
            SELECT id FROM history
            WHERE type='rank_commission'
              AND user_id=?
              AND history_date BETWEEN ? AND ?
              AND hash_id = ?
            LIMIT 1
        ", [$user_id, $start_date, $end_date, $achieved_rank])->row();

        if ($already_paid) {
            $this->_dbg('SKIP_ALREADY_PAID', ['user_id' => $user_id, 'rank_id' => $achieved_rank, 'range' => [$start_date, $end_date]]);
            return;
        }

        // 7) Get rank bonus
        $rank = $this->db->select('rank_bonus, rank_name')->where('id', $achieved_rank)->get('rank_config')->row();
        if (!$rank)
            return;

        $token_info = function_exists('token_info') ? token_info() : null;
        $currency_info = function_exists('currency_info') ? currency_info() : null;

        $rank_bonus = (float) $rank->rank_bonus;
        $token_value = $token_info ? (float) $token_info->currency_value : 0;

        $commission_data = [
            "user_id" => $user_id,
            "amount" => $rank_bonus,
            "type" => "rank_commission",
            "history_date" => date('Y-m-d H:i:s'),
            "date" => date('Y-m-d H:i:s'),
            "status" => '1',
            "coin_type" => '1',
            "token_amount" => round($rank_bonus * $token_value, 6),
            "description" => "Rank Bonus Achieved: {$rank->rank_name} ({$start_date} to {$end_date})",
            "total_left_invest" => $left_total,
            "total_right_invest" => $right_total,
            "total_left_users" => $ach_left_user,
            "total_right_users" => $ach_right_user,
            "coin_id" => $currency_info->id ?? 0,
            "token_id" => $token_info->id ?? 0,
            "hash_id" => $achieved_rank
        ];

        $this->db->insert("history", $commission_data);
        $this->_dbg('CRON_RANK_PAID', $commission_data);
    }

    /**
     * ----------------------------
     * Monthly volume for rank calculation
     * Priority:
     *   1) roi_credits table if exists (history_date)
     *   2) history table profit entries
     * ----------------------------
     */
    private function _get_users_monthly_volume($user_ids, $start_date, $end_date)
    {
        if (empty($user_ids))
            return 0;

        $ids = array_map('intval', $user_ids);
        $in = implode(',', $ids);

        // Prefer roi_credits if exists
        if ($this->db->table_exists('roi_credits')) {
            $row = $this->db->query("
                SELECT COALESCE(SUM(amount),0) AS total_amt
                FROM roi_credits
                WHERE status=1
                  AND user_id IN ($in)
                  AND history_date BETWEEN ? AND ?
            ", [$start_date, $end_date])->row();
            return (float) ($row->total_amt ?? 0);
        }

        // Fallback: history profit
        $row = $this->db->query("
            SELECT COALESCE(SUM(amount),0) AS total_amt
            FROM history
            WHERE status='1'
              AND type='profit'
              AND user_id IN ($in)
              AND history_date BETWEEN ? AND ?
        ", [$start_date, $end_date])->row();

        return (float) ($row->total_amt ?? 0);
    }

    /**
     * ----------------------------
     * Find best achievable rank
     * rank_config rules used:
     *  - rank_status = 1
     *  - rank_order ASC
     *  - rank_eligibel_amt (minimum leg volume)
     * For rank_order > 1:
     *  - Must have at least one downline user in each leg with rank_id >= (rank_order-1)
     * ----------------------------
     */
    private function _find_rank_by_user($minimum_leg_investment, $left_leg_users, $right_leg_users)
    {
        $ranks = $this->db->query("
            SELECT * FROM rank_config
            WHERE rank_status=1
            ORDER BY rank_order ASC
        ")->result();

        $achieved_rank = 0;
        $achieved_left_user = null;
        $achieved_right_user = null;

        foreach ($ranks as $rank) {

            // Must meet min leg volume for any rank
            if ($minimum_leg_investment < (float) $rank->rank_eligibel_amt) {
                continue;
            }

            if ((int) $rank->rank_order === 1) {
                $achieved_rank = (int) $rank->id;
                $achieved_left_user = !empty($left_leg_users) ? $left_leg_users[0] : null;
                $achieved_right_user = !empty($right_leg_users) ? $right_leg_users[0] : null;
                continue;
            }

            // rank_order > 1 requires qualified downline ranks in both legs
            $left_ok = null;
            $right_ok = null;

            if (!empty($left_leg_users)) {
                $left_ok = $this->db->query("
                    SELECT id FROM users
                    WHERE id IN (" . implode(',', array_map('intval', $left_leg_users)) . ")
                      AND rank_id >= ?
                    LIMIT 1
                ", [(int) $rank->rank_order - 1])->row()->id ?? null;
            }

            if (!empty($right_leg_users)) {
                $right_ok = $this->db->query("
                    SELECT id FROM users
                    WHERE id IN (" . implode(',', array_map('intval', $right_leg_users)) . ")
                      AND rank_id >= ?
                    LIMIT 1
                ", [(int) $rank->rank_order - 1])->row()->id ?? null;
            }

            if ($left_ok && $right_ok) {
                $achieved_rank = (int) $rank->id;
                $achieved_left_user = $left_ok;
                $achieved_right_user = $right_ok;
            }
        }

        return [
            'achieved_rank' => $achieved_rank,
            'achieved_left_user' => $achieved_left_user,
            'achieved_right_user' => $achieved_right_user,
        ];
    }

    /**
     * ----------------------------
     * LEG USERS (BFS traversal)
     * Assumptions:
     *  users table has:
     *   - sponser (upline id)  OR parent_id
     *   - position ('left'/'right') OR leg
     *
     * ✅ IMPORTANT:
     * Change these fields to match your DB if needed:
     *   sponsor column: `sponser`
     *   position column: `position`
     * ----------------------------
     */
    private function _get_leg_users($user_id, $side = 'left')
    {
        $side = strtolower($side) === 'right' ? 'right' : 'left';

        $all = [];
        $queue = [];

        // Direct children on that side
        $children = $this->db->query("
            SELECT id FROM users
            WHERE status='1'
              AND sponser = ?
              AND position = ?
        ", [$user_id, $side])->result();

        foreach ($children as $c) {
            $queue[] = (int) $c->id;
            $all[] = (int) $c->id;
        }

        // BFS downline
        while (!empty($queue)) {
            $parent = array_shift($queue);

            $kids = $this->db->query("
                SELECT id FROM users
                WHERE status='1'
                  AND sponser = ?
            ", [$parent])->result();

            foreach ($kids as $k) {
                $kid = (int) $k->id;
                if (!in_array($kid, $all, true)) {
                    $all[] = $kid;
                    $queue[] = $kid;
                }
            }
        }

        return $all;
    }

    /**
     * ----------------------------
     * Auto-create run log table
     * ----------------------------
     */
    private function _ensure_rank_run_log_table()
    {
        if ($this->db->table_exists('rank_cron_runs'))
            return;

        $this->db->query("
            CREATE TABLE `rank_cron_runs` (
              `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
              `run_month` CHAR(7) NOT NULL,
              `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `finished_at` DATETIME NULL,
              `status` ENUM('RUNNING','DONE','FAILED') NOT NULL DEFAULT 'RUNNING',
              `note` VARCHAR(255) DEFAULT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uq_run_month` (`run_month`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }





    /////////////////////////////////////////////////////////



    /**
     * Fetch Users Rank Achievement - Runs Only Once Per Month
     **/
    public function run_monthly_rank_commission()
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
        $this->_dbg('CRON_INVESMENT_QUERY', ['query' => $this->db->last_query()]);

        return [
            'total_invest_amount' => $investment_query->total_invest_amount ?? 0
        ];
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
     * Get Rank Achievement
     **/
    function calculate_user_rank($user_id, $month, $year)
    {

        $run_date = date('Y-m-d');

        $left_leg_users = $this->get_leg_users($user_id, 'left');
        $right_leg_users = $this->get_leg_users($user_id, 'right');

        $this->_dbg('CRON_CALCULATE_USER_RANK', ['left_leg' => $left_leg_users, 'right_leg' => $right_leg_users]);


        $left_investment_data = $this->get_users_roi_total_amount($left_leg_users, $run_date);
        $right_investment_data = $this->get_users_roi_total_amount($right_leg_users, $run_date);



        $left_leg_total = str_replace(',', '', $left_investment_data['total_invest_amount']);
        $right_leg_total = str_replace(',', '', $right_investment_data['total_invest_amount']);

        $this->_dbg("\n CRON_INVESTMENT_DATA", ['left' => $left_investment_data, 'right' => $right_leg_total]);


        $minimum_leg_investment = min($left_leg_total, $right_leg_total);

        $this->db->select('rank_id');
        $this->db->where('id', $user_id);
        $current_rank = $this->db->get('users')->row()->rank_id ?? 0;

        $achieved_rank_get = $this->find_rank_by_user($minimum_leg_investment, $left_leg_users, $right_leg_users);

        echo $achieved_rank_get['achieved_rank'] . "\n";

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
        $achieved_right_user = $achieved_left_user_get = $achieved_left_user = null;

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

        $this->_dbg('find_rank_by_user', [
            'achieved_rank' => $achieved_rank,
            'achieved_left_user' => $achieved_left_user,
            'achieved_right_user' => $achieved_right_user,
        ]);



        return [
            'achieved_rank' => $achieved_rank,
            'achieved_left_user' => $achieved_left_user,
            'achieved_right_user' => $achieved_right_user,
        ];
    }


}