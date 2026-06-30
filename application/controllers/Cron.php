<?php
// defined('BASEPATH') OR exit('No direct script access allowed');

// class Cron extends CI_Controller {


//     public function __construct() {
        
//         ini_set('display_errors', 1);
//         ini_set('display_startup_errors', 1);
//         error_reporting(E_ALL);

//         parent::__construct();
//         $this->load->database();
//         $this->load->model('settings/Commission_model');

//     }
//     /**
//      * Run ROI
//     **/
//     public function run_roi() {
    
//         $this->db->select('i.id, i.user_id, i.invest_amount, i.csq_deposit, i.earn_by, i.profit, i.days_count, i.run_date, i.mature_date, p.roi, p.package_name, p.period, p.days_duration');
//         $this->db->from('user_investment i');
//         $this->db->join('package_config p', 'i.package_id = p.id');
//         $this->db->where('i.status', 1);
//         $this->db->where('i.days_count >', 0);
//         $this->db->where('date(i.run_date) <=', date('Y-m-d')); 
//         $investments = $this->db->get()->result();
    
//         $today = date("Y-m-d");
//         $processed_dates = [];

//         $this->db->select('*');
//         $this->db->where('id','1');
//         $config = $this->db->get('commission_config')->row();
    
//         foreach ($investments as $row) {

//             $investment_id = $row->id;
//             $user_id = $row->user_id;
//             $invest_amount = $row->invest_amount;
//             $roi = $row->roi;
//             $days_count = $row->days_count;
//             $mature_date = $row->mature_date;
//             $run_date = $row->run_date; 
//             $package_name = $row->package_name; 
//             $earn_by = $row->earn_by;
//             $csq_deposit = $row->csq_deposit;
            

//             while (strtotime($run_date) <= strtotime($today) && strtotime($run_date) < strtotime($mature_date) && $days_count > 0) {

//                 $token_info = token_info();
//                 $currency_info = currency_info();
    
//                 $daily_roi = ($invest_amount * $roi) / 100;
//                 $daily_roi_token = ($csq_deposit * $roi) / 100;
    
//                 $new_profit = $daily_roi;
//                 $remaining_days = $days_count - 1;
    
//                 if ($earn_by == "token") {
//                     $earn_type = '1';
//                 } else {
//                     $earn_type = '1';
//                 }

//                 $roi_data = [
//                     "user_id" => $user_id,
//                     "amount" => $new_profit,
//                     "type" => "profit",
//                     "history_date" => date('Y-m-d H:i:s', strtotime($run_date)),
//                     "date" => date('Y-m-d H:i:s', strtotime($run_date)),
//                     "status"  => '1',
//                     "hash_id" => "roi-made",
//                     "invest_id" => $investment_id,
//                     "description" => token_format($daily_roi_token)." Lending bonus made",
//                     "token_amount" => $daily_roi_token,
//                     "coin_id" => $currency_info->id,
//                     "token_id" => $token_info->id,
//                     "coin_type" => $earn_type,
//                 ];
//                 $this->db->insert("history", $roi_data);
    
//                 $next_run_date = date('Y-m-d', strtotime($run_date . ' +1 day'));
//                 $this->db->where('id', $investment_id);
//                 $this->db->update('user_investment', [
//                     'profit' => $new_profit,
//                     'days_count' => $remaining_days,
//                     'run_date' => $next_run_date
//                 ]);
                

//                 /************* PACKAGE MATURE VALIDATION  ****/
//                 if (strtotime($run_date) >= strtotime($mature_date) || $remaining_days <= 0) {
//                     $this->package_mature($investment_id);
//                 }
               
//                 $processed_dates[] = $run_date;
//                 $run_date = date('Y-m-d', strtotime($run_date . ' +1 day'));
//                 $days_count--;
//             }

//         }

//     }
//     /**
//      * Binary Earnings
//     **/
//     public function binary_commission_call(){

//         $this->db->select('*');
//         $this->db->where('id','1');
//         $config = $this->db->get('commission_config')->row();
//         $date = date("Y-m-d");

        
//         if($config->binary_commission_status > 0){
//           $this->process_binary_commission_V2($date); 
//           $this->process_matching_bonus($date);   
//         }
//     }
//     /**
//      * Package Reinvestemnt
//     **/
//     private function package_mature($investment_id) {
//         $today = date('Y-m-d');
        
//         $this->db->select('*');
//         $this->db->from('user_investment');
//         $this->db->where('id', $investment_id);
//         $investment = $this->db->get()->row();
        
//         if (!$investment) return;
    
//         if (strtotime($investment->mature_date) <= strtotime($today)) {
            
//             if ($investment->reinvest_status == 1) {
                
//                 $this->db->select('*');
//                 $this->db->from('package_config');
//                 $this->db->where('id', $investment->package_id);
//                 $package = $this->db->get()->row();
                
//                 $invest_date = $investment->mature_date;
//                 $rundate = date('Y-m-d', strtotime($invest_date . ' +1 day'));
//                 $maturedate = date('Y-m-d H:i:s', strtotime($invest_date . ' +' . $package->days_duration . ' days'));
//                 $ending_date = $maturedate;

                
//                 $insert_data = array(
//                     "user_id" => $investment->user_id,
//                     "invest_amount" => $investment->invest_amount,
//                     "invest_network" => $investment->invest_network,
//                     "status"  => '1',
//                     "created_date" => $invest_date,
//                     "days_count"  => $package->days_duration,
//                     "profit"  => $package->roi,
//                     "hash_id" => $investment->hash_id,
//                     "run_date" => $rundate,
//                     "starting_date" => date('Y-m-d H:i:s', strtotime($invest_date)),
//                     "ending_date" => $ending_date,
//                     "mature_date" => $maturedate,
//                     "type" => "mining",
//                     'reinvest_id' => $investment_id,
//                     "req_method" => $investment->req_method,
//                     "approve_status" => $investment->approve_status,
//                     "package_id" => $investment->package_id,
//                     "csq_price" => $investment->csq_price,
//                     "csq_deposit" => $investment->csq_deposit,
//                     "earn_by" => $investment->earn_by,
//                     "currency_id" => $investment->currency_id,
//                     "token_id" => $investment->token_id,
//                 );
    
//                 $this->db->insert("user_investment", $insert_data);
//                 $new_invest_id = $this->db->insert_id();
    
//                 if ($new_invest_id) {
                    
//                     $deposit_data = array(
//                         "user_id" => $investment->user_id,
//                         "amount" => $investment->invest_amount,
//                         "type" => "mining",
//                         "history_date" => date('Y-m-d H:i:s', strtotime($invest_date)),
//                         "date" => date('Y-m-d H:i:s', strtotime($invest_date)),
//                         "status"  => '1',
//                         "hash_id" => $investment->hash_id,
//                         "invest_id" => $new_invest_id,
//                         "token_amount" => $investment->csq_deposit,
//                         "description" => "Investment Successfully",
//                         "coin_id" => $investment->currency_id,
//                         "token_id" => $investment->token_id,
//                         "coin_type" =>  '1',
//                         "transaction_id" => "reinvestment"
//                     );
//                     $this->db->insert("history", $deposit_data);
    
//                     $this->db->where('id', $investment_id);
//                     $this->db->update('user_investment', [
//                         'status' => 2, 
//                         'recived_status' => 1
//                     ]);
//                 }
//             } else {

//                 $query = $this->db->query("SELECT * FROM package_config WHERE id = ? AND status = '1'", [$investment->package_id]);
//                 $package = $query->row();

//                 $deposit_data = array(
//                     "user_id" => $investment->user_id,
//                     "amount" => $investment->invest_amount,
//                     "type" => "release_deposit",
//                     "history_date" => date('Y-m-d H:i:s', strtotime($investment->mature_date)),
//                     "date" => date('Y-m-d H:i:s', strtotime($investment->mature_date)),
//                     "status"  => '1',
//                     "hash_id" => $investment->hash_id,
//                     "invest_id" => $investment->id,
//                     "token_amount" => $investment->csq_deposit,
//                     "description" => "Package Matured Successfully ( ".$package->package_name." )",
//                     "coin_type" => '1',
//                     "token_id" => $investment->token_id,
//                     "coin_id" =>  $investment->currency_id,
//                 );
//                 $this->db->insert("history", $deposit_data);

//                 $this->db->where('id', $investment_id);
//                 $this->db->update('user_investment', ['status' => 2, 'recived_status' => 0]);
//             }
//         }
//     }

//     /**
//      * Run Binary Match Bonus (AFTER ROI is Distributed)
//     **/
//     private function process_binary_commission($run_date) {
        
        
//         $this->db->select('id');
//         $this->db->where_in('status','1');
//         $users = $this->db->get('users')->result();

//         $token_info = token_info();
//         $currency_info = currency_info();

//         foreach ($users as $user) {
//             $user_id = $user->id;

//             $this->db->where('user_id', $user_id);
//             $this->db->where('type', 'binary_commission');
//             $this->db->where('DATE(history_date)', $run_date);
//             $existing_bonus = $this->db->count_all_results('history');



//             if ($existing_bonus > 0) {
//                 continue; 
//             }
            
         

//             $left_users = $this->get_leg_users($user_id, 'left');
//             $right_users = $this->get_leg_users($user_id, 'right');

//             $total_left_users = !empty($left_users) ? implode(',', $left_users) : '';
//             $total_right_users = !empty($right_users) ? implode(',', $right_users) : '';

//             $left_roi_data = $this->get_users_roi_total($left_users, $run_date);
//             $right_roi_data = $this->get_users_roi_total($right_users, $run_date);

//             $left_total_earning = $left_roi_data['total_profit_token'];
//             $right_total_earning = $right_roi_data['total_profit_token'];

//             $left_invest_ids = $left_roi_data['invest_ids']; 
//             $right_invest_ids = $right_roi_data['invest_ids']; 

//             $left_total_invest = $left_roi_data['total_invest_amount'];
//             $right_total_invest = $right_roi_data['total_invest_amount'];

//             $matching_amount = min($left_total_earning, $right_total_earning);

//             if ($matching_amount > 0) {
//                 $this->db->select('binary_commission');
//                 $config = $this->db->get('commission_config')->row();

//                 if ($config) {

//                     $binary_commission = ($matching_amount * $config->binary_commission) / 100;
//                     $binary_commission_usd =  $binary_commission / $token_info->currency_value;

//                     $commission_data = [
//                         "user_id" => $user_id,
//                         "amount" => $binary_commission_usd,
//                         "type" => "binary_commission",
//                         "history_date" => date('Y-m-d', strtotime($run_date)) . ' 10:' . date('i:s', strtotime($run_date)),
//                         "date" => date('Y-m-d', strtotime($run_date)) . ' 10:' . date('i:s', strtotime($run_date)),
//                         "status" => '1',
//                         "coin_type" => '1',
//                         "token_amount" => $binary_commission,
//                         "description" => "Binary Matching Commission Earned",
//                         "total_left_invest" => $left_total_invest,
//                         "total_right_invest" => $right_total_invest,
//                         "total_left_roi" => $left_total_earning,
//                         "total_right_roi" => $right_total_earning,
//                         "total_left_users" => $total_left_users,  
//                         "total_right_users" => $total_right_users,
//                         "total_left_invest_ids" => $left_invest_ids,   
//                         "total_right_invest_ids" => $right_invest_ids,
//                         "coin_id" => $currency_info->id,
//                         "token_id" => $token_info->id,
//                     ];
//                     $this->db->insert("history", $commission_data);
//                 }
//             }
//         }
//     }
//     /**
//      * Recursively Get All Users Under a Leg
//     **/
//     private function get_leg_users($parent_id, $position) {
//         $users = [];
    
//         $child = $this->db->get_where('binary_placement', ['parent_id' => $parent_id, 'position' => $position])->row();
    
//         if ($child) {
//             $users[] = $child->user_id;
    
//             $users = array_merge($users, $this->get_leg_users($child->user_id, 'left'));
//             $users = array_merge($users, $this->get_leg_users($child->user_id, 'right'));
//         }
    
//         return $users;
//     }
//     /**
//      * Get Total ROI, Invest IDs, and Investment Amount for Given Users on a Date
//     **/
//     private function get_users_roi_total($user_ids, $run_date) {
//         if (empty($user_ids)) return ['total_profit' => 0, 'total_profit_token' => 0, 'invest_ids' => '', 'total_invest_amount' => 0];

//         $this->db->select('SUM(amount) as total_profit, GROUP_CONCAT(DISTINCT invest_id ORDER BY invest_id ASC) as invest_ids');
//         $this->db->where_in('user_id', $user_ids);
//         $this->db->where('type', 'profit');
//         $this->db->where('DATE(history_date)', $run_date);
//         $history_query = $this->db->get('history')->row();

//         $this->db->select('SUM(token_amount) as total_profit');
//         $this->db->where_in('user_id', $user_ids);
//         $this->db->where('type', 'profit');
//         $this->db->where('DATE(history_date)', $run_date);
//         $history_token_query = $this->db->get('history')->row();

//         $this->db->select('SUM(csq_deposit) as total_invest_amount');
//         $this->db->where_in('user_id', $user_ids);
//         $this->db->where_in('status','1');
//         $investment_query = $this->db->get('user_investment')->row();

//         return [
//             'total_profit' => $history_query->total_profit ?? 0,  
//             'total_profit_token' => $history_token_query->total_profit ?? 0,  
//             'invest_ids' => $history_query->invest_ids ?? '',
//             'total_invest_amount' => $investment_query->total_invest_amount ?? 0
//         ];
//     }
//     /**
//      * Get Total ROI, Invest IDs, and Investment Amount for Given Users on a Date
//     **/
//     private function get_users_roi_total_amount($user_ids, $run_date) {

//         if (empty($user_ids)) return ['total_invest_amount' => 0];
        
//         $previous_month_date = date('Y-m-01', strtotime("first day of last month", strtotime($run_date)));
//         $month = date('m', strtotime($previous_month_date)); 
//         $year = date('Y', strtotime($previous_month_date));

//         $this->db->select('SUM(invest_amount) as total_invest_amount');
//         $this->db->where_in('user_id', $user_ids);
//         $this->db->where('status', '1');
//         $this->db->where('YEAR(created_date)', $year);
//         $this->db->where('MONTH(created_date)', $month);
        
//         $investment_query = $this->db->get('user_investment')->row();

//         return [
//             'total_invest_amount' => $investment_query->total_invest_amount ?? 0
//         ];
//     }
    
//    /**
//      * Fetch Users Rank Achievement - Runs Only Once Per Month
//      **/
//     public function update_all_users_rank() {
//         $current_month = date('m');
//         $current_year = date('Y');

//         // Calculate the previous month and year
//         $previous_month = date('m', strtotime('first day of last month'));
//         $previous_year = date('Y', strtotime('first day of last month'));

//         // Set the date range for the previous month
//         $start_date = date("Y-m-01 00:00:00", strtotime("first day of last month"));
//         $end_date = date("Y-m-t 23:59:59", strtotime("last day of last month"));

//         $users = $this->db->query("SELECT * FROM users WHERE status = '1' ORDER BY id DESC")->result();
        
//         foreach ($users as $user) {
//             $check_already_bonus = $this->db->query("
//                 SELECT id FROM history 
//                 WHERE type = 'rank_commission' 
//                 AND history_date >= '$start_date' 
//                 AND history_date <= '$end_date'
//                 AND user_id = {$user->id}
//             ")->row();

//             if (!$check_already_bonus) { 
//                 $this->calculate_user_rank($user->id, $previous_month, $previous_year);
//             }
//         }
//     }

//     /**
//      * Get Rank Achievement
//      **/
//     function calculate_user_rank($user_id, $month, $year) {

//         $run_date = date('Y-m-d');

//         $left_leg_users = $this->get_leg_users($user_id, 'left');
//         $right_leg_users = $this->get_leg_users($user_id, 'right');

//         $left_investment_data = $this->get_users_roi_total_amount($left_leg_users, $run_date);
//         $right_investment_data = $this->get_users_roi_total_amount($right_leg_users, $run_date);
  
//         $left_leg_total = str_replace(',', '', $left_investment_data['total_invest_amount']);
//         $right_leg_total = str_replace(',', '', $right_investment_data['total_invest_amount']);

//         $minimum_leg_investment = min($left_leg_total, $right_leg_total);
    
//         $this->db->select('rank_id');
//         $this->db->where('id', $user_id);
//         $current_rank = $this->db->get('users')->row()->rank_id ?? 0;

//         $achieved_rank_get = $this->find_rank_by_user($minimum_leg_investment,$left_leg_users,$right_leg_users);
  

//         if ($achieved_rank_get['achieved_rank'] > 0) {

     
//             $achieved_rank = $achieved_rank_get['achieved_rank'];
//             $achieved_left_user = $achieved_rank_get['achieved_left_user'];
//             $achieved_right_user = $achieved_rank_get['achieved_right_user'];

            
//             $this->db->set('rank_id', $achieved_rank);
//             $this->db->where('id', $user_id);
//             $this->db->update('users');

//             $this->db->select('rank_bonus, rank_name');
//             $this->db->where('id', $achieved_rank);
//             $rank_data =  $this->db->get('rank_config')->row();

//                 if ($rank_data) {

//                     $rank_bonus = $rank_data->rank_bonus;
//                     $rank_name = $rank_data->rank_name;

//                     $token_info = token_info();
//                     $currency_info = currency_info();
                    
//                     $rank_commission_usd_price = $rank_bonus;
//                     $rank_commission_token_price = $rank_bonus * $token_info->currency_value;
                    
//                     $commission_data = [
//                         "user_id" => $user_id,
//                         "amount" => $rank_commission_usd_price,
//                         "type" => "rank_commission",
//                         "history_date" => date('Y-m-d H:i:s'),
//                         "date" => date('Y-m-d H:i:s'),
//                         "status" => '1',
//                         "coin_type" => '1',
//                         "token_amount" => $rank_commission_token_price,
//                         "description" => "Rank Bonus Achieved: " . $rank_name,
//                         "total_left_invest" => $left_leg_total,
//                         "total_right_invest" => $right_leg_total,

//                         "total_left_users" => $achieved_left_user,
//                         "total_right_users" => $achieved_right_user,


//                         "coin_id" => $currency_info->id,
//                         "token_id" => $token_info->id,
//                         "hash_id" => $achieved_rank
//                     ];

//                     $this->db->insert("history", $commission_data);
            
//                 }

//         }  else {

//         if ($current_rank > 0) {
//             $this->db->where('id', $user_id);
//            $this->db->update('users', ['rank_id' => 0]);
//         }

//         }

//     }
//     /**
//      * Find Rank Achievement
//      **/
//    public function find_rank_by_user($minimum_leg_investment,$left_leg_users,$right_leg_users){



//     $this->db->select('*');
//     $this->db->where('rank_status', 1);
//     $this->db->order_by('rank_order', 'ASC');
//     $ranks = $this->db->get('rank_config')->result();

//     $achieved_rank = 0;
//     $achieved_left_user = null;
//     $achieved_right_user = null;

//     foreach ($ranks as $rank) {
    
//         if ($rank->rank_order == 1) {
   
//             if ($minimum_leg_investment >= $rank->rank_eligibel_amt) {

//                  $achieved_rank = $rank->id;
//                  $achieved_left_user = !empty($left_leg_users) ? $left_leg_users[0] : null;
//                  $achieved_right_user = !empty($right_leg_users) ? $right_leg_users[0] : null;
//             }

//         } else {
            
//             if (!empty($left_leg_users)) {
//                 $this->db->select('id');
//                 $this->db->where_in('id', $left_leg_users);
//                 $this->db->where('rank_id >=', $rank->rank_order - 1);
//                 $this->db->limit(1);
//                 $achieved_left_user_get = $this->db->get('users')->row()->id ?? null;
//             } 

//             if (!empty($right_leg_users)) {
//                 $this->db->select('id');
//                 $this->db->where_in('id', $right_leg_users);
//                 $this->db->where('rank_id >=', $rank->rank_order - 1);
//                 $this->db->limit(1);
//                 $achieved_right_user_get = $this->db->get('users')->row()->id ?? null;
//             } 

//             if ($achieved_left_user_get && $achieved_right_user_get) {

//                 $achieved_left_user  = $achieved_left_user_get;
//                 $achieved_right_user = $achieved_right_user_get;
//                 $achieved_rank = $rank->id;

//             }

//         }

//     }

      
//      return [
//         'achieved_rank' => $achieved_rank,
//         'achieved_left_user' => $achieved_left_user,
//         'achieved_right_user' => $achieved_right_user,
//     ];

    
//    }



   
//    //*************************** NEW ONE UPDATE METHOD FUNCTION  ********************************************************/

//    /**
//      * Sum volume for users on a given date.
//      * $basis: 'PV'  -> use investments volume (invest_amount) of that date
//      *         'BV'  -> use ROI token earnings (token_amount) of that date
//      * Returns ['volume'=>float, 'invest_ids'=>csv, 'user_ids'=>csv]
//      */


//    private function process_binary_commission_v2($run_date)
//     {
//         $cfg = $this->db->get_where('commission_config', ['id'=>1])->row();
//         if (!$cfg || !$cfg->binary_commission_status) return;

//         $basis = $cfg->binary_pair_on ?: 'PV';                 // PV or BV
//         $type  = $cfg->binary_pair_type ?: 'percent';          // percent / amount

//         // Ratio units, e.g. "1:2"
//         $ratio = $cfg->binary_pair_ratio ?: '1:1';
//         [$rL, $rR] = array_map('intval', explode(':', $ratio));
//         if ($rL<=0 || $rR<=0) { $rL=1; $rR=1; }

//         $carry_mode = $cfg->binary_carry_forward ?: 'weak_leg'; // none/weak_leg/both
//         $flush_rule = $cfg->binary_flush_rule ?: 'daily';       // never/daily/weekly/monthly

//         $token_info    = token_info();

//         // All active users
//         $users = $this->db->select('id')->from('users')->where('status','1')->get()->result();

//         foreach ($users as $u) {
//             $user_id = $u->id;

//             // Skip if we already credited today (idempotence)
//             $exists = $this->db->where('user_id', $user_id)
//                 ->where('type', 'binary_commission')
//                 ->where('DATE(history_date)', $run_date)
//                 ->count_all_results('history');
//             if ($exists) continue;

//             // Get both legs
//             $left_users  = $this->get_leg_users($user_id, 'left');
//             $right_users = $this->get_leg_users($user_id, 'right');

//             // Compute today's raw volumes by basis
//             $L = $this->sum_leg_volume($left_users,  $run_date, $basis);
//             $R = $this->sum_leg_volume($right_users, $run_date, $basis);

//             $left_vol  = (float)$L['volume'];
//             $right_vol = (float)$R['volume'];

//             // Bring in carry + apply flush rule
//             $carry = $this->get_carry_row($user_id);

//             $left_carry  = (float)$carry->left_carry;
//             $right_carry = (float)$carry->right_carry;

//             // Reset (flush) according to rule
//             if ($flush_rule !== 'never' && !empty($carry->last_flush_at)) {
//                 $last = new DateTime($carry->last_flush_at);
//                 $cur  = new DateTime($run_date);

//                 $flush_now = false;
//                 if ($flush_rule === 'daily')   $flush_now = $cur > $last;
//                 if ($flush_rule === 'weekly')  $flush_now = (int)$cur->format('oW') !== (int)$last->format('oW');
//                 if ($flush_rule === 'monthly') $flush_now = $cur->format('Ym') !== $last->format('Ym');

//                 if ($flush_now) {
//                     $left_carry = $right_carry = 0;
//                 }
//             }

//             // Add carry to today’s volume
//             $left_vol  += $left_carry;
//             $right_vol += $right_carry;

//             if ($left_vol <= 0 && $right_vol <= 0) {
//                 // nothing to pair, just update flush marker
//                 $this->save_carry($user_id, $left_vol, $right_vol, $run_date);
//                 continue;
//             }

//             // Calculate pairs by ratio a:b
//             $pairs = floor(min($left_vol / $rL, $right_vol / $rR));


//             if ($pairs <= 0) {
//                 // no complete pair, carry forward diffs as per rule
//                 $new_left_carry  = $left_vol;
//                 $new_right_carry = $right_vol;

//                 if ($carry_mode === 'none') { $new_left_carry = 0; $new_right_carry = 0; }
//                 if ($carry_mode === 'weak_leg') {
//                     if ($left_vol <= $right_vol) { $new_right_carry = 0; }
//                     else { $new_left_carry = 0; }
//                 }
//                 $this->save_carry($user_id, $new_left_carry, $new_right_carry, $run_date);
//                 continue;
//             }

//             // Matched volumes consumed by those pairs
//             $matched_left  = $pairs * $rL;
//             $matched_right = $pairs * $rR;

//             // Remaining after matching
//             $rem_left  = $left_vol  - $matched_left;
//             $rem_right = $right_vol - $matched_right;

//             // Apply carry rule on remainders
//             $new_left_carry  = 0;
//             $new_right_carry = 0;
//             if ($carry_mode === 'both') {
//                 $new_left_carry  = max(0, $rem_left);
//                 $new_right_carry = max(0, $rem_right);
//             } elseif ($carry_mode === 'weak_leg') {
//                 if ($rem_left <= $rem_right) {
//                     $new_left_carry = max(0, $rem_left);
//                 } else {
//                     $new_right_carry = max(0, $rem_right);
//                 }
//             }
//             // 'none' keeps both 0

//             $this->save_carry($user_id, $new_left_carry, $new_right_carry, $run_date);

//             // Commission base:
//             // - If percent: apply % on the *weak leg matched volume* (standard practice)
//             //   weak matched = pairs * min(rL, rR)
//             // - If amount: fixed amount per pair
//             $weak_unit   = min($rL, $rR);
//             $base_volume = $pairs * $weak_unit; // in PV/BV units

//             if ($type === 'percent') {
//                 $percent     = (float)$cfg->binary_pair_percent;
//                 $token_amt   = ($base_volume * $percent) / 100.0;  // token units if BV; PV has no “token” – we still treat it as points and pay as token by ratio
//             } else {
//                 $per_pair    = (float)$cfg->binary_pair_amount;
//                 $token_amt   = $pairs * $per_pair;                 // token units (you can choose to treat as BV-equivalent)
//             }

//             // If basis was PV, you may want to convert PV→token at your own internal rate.
//             // Here we assume PV == token units for payout; adjust as needed.

//             $usd_amt = $this->token_to_usd($token_amt);

//             // Credit with caps/threshold and extra meta for reporting
//             $meta = [
//                 "total_left_roi"        => ($basis==='BV') ? $L['volume'] : null,
//                 "total_right_roi"       => ($basis==='BV') ? $R['volume'] : null,
//                 "total_left_invest"     => ($basis==='PV') ? $L['volume'] : null,
//                 "total_right_invest"    => ($basis==='PV') ? $R['volume'] : null,
//                 "total_left_users"      => $L['user_ids'],
//                 "total_right_users"     => $R['user_ids'],
//                 "total_left_invest_ids" => $L['invest_ids'],
//                 "total_right_invest_ids"=> $R['invest_ids'],
//                 "pair_ratio_used"       => $ratio,
//                 "pairs_count"           => $pairs,
//                 "basis"                 => $basis,
//             ];

//             $this->credit_binary_history($user_id, $run_date, $usd_amt, $token_amt, $meta);
//         }
//     }

//     private function sum_leg_volume(array $user_ids, string $run_date, string $basis='BV'): array
//     {
//         if (empty($user_ids)) {
//             return ['volume'=>0, 'invest_ids'=>'', 'user_ids'=>''];
//         }

//         $ids_csv = implode(',', $user_ids);
//         $this->db->reset_query();

//         if ($basis === 'PV') {
//             // Sum the day's investments (or active investments created that day)
//             $q = $this->db->select('SUM(invest_amount) AS vol, GROUP_CONCAT(id) AS invest_ids', false)
//                 ->from('user_investment')
//                 ->where_in('user_id', $user_ids)
//                 ->where('status', 1)
//                 ->get()->row();
//             return [
//                 'volume'     => (float)($q->vol ?? 0),
//                 'invest_ids' => (string)($q->invest_ids ?? ''),
//                 'user_ids'   => $ids_csv
//             ];
//         }

//         // BV: use ROI token earnings posted for that date
//         $q = $this->db->select('SUM(amount) AS vol')
//             ->from('history')
//             ->where_in('user_id', $user_ids)
//             ->where('type', 'profit')                    // your ROI rows
//             ->where('DATE(history_date)', $run_date)
//             ->get()->row();

//         return [
//             'volume'     => (float)($q->vol ?? 0),
//             'invest_ids' => '',
//             'user_ids'   => $ids_csv
//         ];
//     }

//     /** Get or create a carry row for user. */
//     private function get_carry_row($user_id)
//     {
//         $row = $this->db->get_where('binary_carry', ['user_id'=>$user_id])->row();
//         if (!$row) {
//             $this->db->insert('binary_carry', ['user_id'=>$user_id, 'left_carry'=>0, 'right_carry'=>0, 'last_flush_at'=>null]);
//             $row = $this->db->get_where('binary_carry', ['user_id'=>$user_id])->row();
//         }
//         return $row;
//     }

//     /** Update carry row. */
//     private function save_carry($user_id, $left, $right, $run_date)
//     {
//         $this->db->update('binary_carry', [
//             'left_carry'    => (float)$left,
//             'right_carry'   => (float)$right,
//             'last_flush_at' => $run_date
//         ], ['user_id'=>$user_id]);
//     }

//     /** Convert token amount to USD with your helpers. */
//     private function token_to_usd($token_amount)
//     {
//         $token_info    = token_info();
//         if (empty($token_info->currency_value) || $token_info->currency_value <= 0) return 0;
//         return (float)$token_amount / (float)$token_info->currency_value;
//     }

//     /** Credit history row (respects payout caps & threshold). */
//     private function credit_binary_history($user_id, $run_date, $usd_amount, $token_amount, array $meta)
//     {
//         $token_info    = token_info();
//         $currency_info = currency_info();

//         // Global caps from config
//         $cfg = $this->db->get_where('commission_config', ['id'=>1])->row();
//         $usd_amount = (float)$usd_amount;

//         // Daily cap
//         if (!empty($cfg->payout_daily_cap) && $cfg->payout_daily_cap > 0) {
//             // Sum today's credited binary_commission
//             $sum = $this->db->select('SUM(amount) AS s')->from('history')
//                 ->where('user_id',$user_id)->where('type','binary_commission')
//                 ->where('DATE(history_date)',$run_date)->get()->row();
//             $already = (float)($sum->s ?? 0);
//             $room    = (float)$cfg->payout_daily_cap - $already;
//             if ($room <= 0) return false;
//             if ($usd_amount > $room) {
//                 $token_amount = $token_amount * ($room / $usd_amount);
//                 $usd_amount   = $room;
//             }
//         }

//         // Min threshold
//         if (!empty($cfg->payout_threshold_min) && $usd_amount < $cfg->payout_threshold_min) {
//             return false;
//         }

//         $payload = array_merge([
//             "user_id"       => $user_id,
//             "amount"        => round($usd_amount, 6),
//             "type"          => "binary_commission",
//             "history_date"  => $run_date . ' 10:00:00',
//             "date"          => $run_date . ' 10:00:00',
//             "status"        => '1',
//             "coin_type"     => '1',
//             "token_amount"  => round($token_amount, 6),
//             "description"   => "Binary Commission Earned",
//             "coin_id"       => $currency_info->id ?? null,
//             "token_id"      => $token_info->id ?? null,
//         ], $meta);

//         $this->db->insert('history', $payload);
//         return true;
//     }

//     //************** matching bonus check  **************************/

//     private function process_matching_bonus($run_date)
// {
//     $cfg = $this->db->get_where('commission_config', ['id'=>1])->row();
//     if (!$cfg || !$cfg->matching_bonus_status) return;

//     $levels   = (int)$cfg->matching_levels;
//     if ($levels <= 0) return;

//     $percents = array_map('floatval', array_filter(array_map('trim', explode(',', (string)$cfg->matching_percents))));
//     if (empty($percents)) return;

//     // Pull all binary commissions posted on run_date
//     $rows = $this->db->select('user_id, amount, token_amount, id')
//         ->from('history')
//         ->where('type','binary_commission')
//         ->where('DATE(history_date)', $run_date)
//         ->get()->result();

//     foreach ($rows as $row) {
//         $earner_id   = $row->user_id;
//         $bin_usd     = (float)$row->amount;
//         $bin_token   = (float)$row->token_amount;

//         // Walk up sponsor chain
//         $upline_id = $this->get_sponsor_id($earner_id); // You must have this helper; see stub below
//         for ($lv=1; $lv <= $levels && $upline_id; $lv++) {

//             $percent = isset($percents[$lv-1]) ? (float)$percents[$lv-1] : 0;
//             if ($percent <= 0) {
//                 $upline_id = $this->get_sponsor_id($upline_id);
//                 continue;
//             }

//             // Avoid duplicate if run twice
//             $exists = $this->db->where('user_id', $upline_id)
//                 ->where('type','matching_bonus')
//                 ->where('ref_history_id', $row->id)      // reference child’s binary row
//                 ->count_all_results('history');
//             if ($exists) {
//                 $upline_id = $this->get_sponsor_id($upline_id);
//                 continue;
//             }

//             $bonus_token = $bin_token * $percent / 100.0;
//             $bonus_usd   = $this->token_to_usd($bonus_token);

//             $meta = [
//                 "description"    => "Matching Bonus L{$lv} from #{$earner_id}",
//                 "ref_history_id" => $row->id,
//                 "source_user_id" => $earner_id,
//                 "level_no"       => $lv
//             ];

//             $this->credit_matching_history($upline_id, $run_date, $bonus_usd, $bonus_token, $meta);

//             $upline_id = $this->get_sponsor_id($upline_id);
//         }
//     }
// }

// /** Minimal “credit” for matching (separate type). */
// private function credit_matching_history($user_id, $run_date, $usd_amount, $token_amount, array $meta)
// {
//     $token_info    = token_info();
//     $currency_info = currency_info();

//     $cfg = $this->db->get_where('commission_config', ['id'=>1])->row();
//     $usd_amount = (float)$usd_amount;

//     // You may want to reuse payout caps; here we only check min threshold
//     if (!empty($cfg->payout_threshold_min) && $usd_amount < $cfg->payout_threshold_min) {
//         return false;
//     }

//     $payload = array_merge([
//         "user_id"       => $user_id,
//         "amount"        => round($usd_amount, 6),
//         "type"          => "matching_bonus",
//         "history_date"  => $run_date . ' 11:00:00',
//         "date"          => $run_date . ' 11:00:00',
//         "status"        => '1',
//         "coin_type"     => '1',
//         "token_amount"  => round($token_amount, 6),
//         "coin_id"       => $currency_info->id ?? null,
//         "token_id"      => $token_info->id ?? null,
//     ], $meta);

//     $this->db->insert('history', $payload);
//     return true;
// }

// /** Stub: get sponsor/upline id; replace with your actual logic/column name. */
// private function get_sponsor_id($user_id)
// {
//     $u = $this->db->select('sponser')->from('users')->where('id', $user_id)->get()->row();
//     return $u ? (int)$u->sponser : null;
// }
   
// }

////////////////////////////////////////////////////////////
defined('BASEPATH') OR exit('No direct script access allowed');

class Cron extends CI_Controller {


    public function __construct() {
        
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        parent::__construct();
        $this->load->database();
        $this->load->model('settings/Commission_model');

    }
    /**
     * Run ROI
    **/
    public function run_roi() {
    
        $this->db->select('i.id, i.user_id, i.invest_amount, i.csq_deposit, i.earn_by, i.profit, i.days_count, i.run_date, i.mature_date, p.roi, p.package_name, p.period, p.days_duration');
        $this->db->from('user_investment i');
        $this->db->join('package_config p', 'i.package_id = p.id');
        $this->db->where('i.status', 1);
        $this->db->where('i.days_count >', 0);
        $this->db->where('date(i.run_date) <=', date('Y-m-d')); 
        $investments = $this->db->get()->result();
    
        $today = date("Y-m-d");
        $processed_dates = [];

        $this->db->select('*');
        $this->db->where('id','1');
        $config = $this->db->get('commission_config')->row();
    
        foreach ($investments as $row) {

            $investment_id = $row->id;
            $user_id = $row->user_id;
            $invest_amount = $row->invest_amount;
            $roi = $row->roi;
            $days_count = $row->days_count;
            $mature_date = $row->mature_date;
            $run_date = $row->run_date; 
            $package_name = $row->package_name; 
            $earn_by = $row->earn_by;
            $csq_deposit = $row->csq_deposit;
            

            while (strtotime($run_date) <= strtotime($today) && strtotime($run_date) < strtotime($mature_date) && $days_count > 0) {

                $token_info = token_info();
                $currency_info = currency_info();
    
                $daily_roi = ($invest_amount * $roi) / 100;
                $daily_roi_token = ($csq_deposit * $roi) / 100;
    
                $new_profit = $daily_roi;
                $remaining_days = $days_count - 1;
    
                if ($earn_by == "token") {
                    $earn_type = '1';
                } else {
                    $earn_type = '1';
                }

                $roi_data = [
                    "user_id" => $user_id,
                    "amount" => $new_profit,
                    "type" => "profit",
                    "history_date" => date('Y-m-d H:i:s', strtotime($run_date)),
                    "date" => date('Y-m-d H:i:s', strtotime($run_date)),
                    "status"  => '1',
                    "hash_id" => "roi-made",
                    "invest_id" => $investment_id,
                    "description" => token_format($daily_roi_token)." Lending bonus made",
                    "token_amount" => $daily_roi_token,
                    "coin_id" => $currency_info->id,
                    "token_id" => $token_info->id,
                    "coin_type" => $earn_type,
                ];
                $this->db->insert("history", $roi_data);
    
                $next_run_date = date('Y-m-d', strtotime($run_date . ' +1 day'));
                $this->db->where('id', $investment_id);
                $this->db->update('user_investment', [
                    'profit' => $new_profit,
                    'days_count' => $remaining_days,
                    'run_date' => $next_run_date
                ]);
                

                /************* PACKAGE MATURE VALIDATION  ****/
                if (strtotime($run_date) >= strtotime($mature_date) || $remaining_days <= 0) {
                    $this->package_mature($investment_id);
                }
               
                $processed_dates[] = $run_date;
                $run_date = date('Y-m-d', strtotime($run_date . ' +1 day'));
                $days_count--;
            }

        }

    }
    /**
     * Binary Earnings
    **/
    public function binary_commission_call(){
        $cfg = $this->db->get_where('commission_config', ['id'=>1])->row();
        $date = date("Y-m-d");

        if($cfg && (int)$cfg->binary_commission_status > 0){
            $this->process_pair_commission_with_carry($date);
        }
    }

    /**
     * DAILY: settle Binary Pair Commission (pair_commission) using carry forward.
     *
     * Inputs:
     * - commission_config.binary_pair_ratio (ex: 1:1)
     * - commission_config.binary_pair_type (percent|amount) as GLOBAL fallback
     * - commission_config.carry_forward_status (0/1)
     * - commission_config.carry_forward_mode (LIFETIME|DAILY|WEEKLY|MONTHLY)
     * - commission_config.carry_forward_cap (optional BV cap)
     *
     * Per user (receiver) package rules:
     * - package_config.pair_commission_status (0/1)
     * - package_config.pair_commission (percent or amount)
     * - package_config.pair_commission_type (percent|amount)
     * - package_config.daily_max_pairs (0 => unlimited)
     */
    private function process_pair_commission_with_carry(string $run_date)
    {
        $cfg = $this->db->get_where('commission_config', ['id'=>1])->row();
        if (!$cfg || (int)$cfg->binary_commission_status !== 1) return;

        // Parse ratio
        $ratio = !empty($cfg->binary_pair_ratio) ? $cfg->binary_pair_ratio : '1:1';
        [$rL, $rR] = array_map('intval', explode(':', $ratio));
        if ($rL <= 0 || $rR <= 0) { $rL = 1; $rR = 1; }

        $global_pair_type = !empty($cfg->binary_pair_type) ? $cfg->binary_pair_type : 'percent';

        $carry_enabled = ((int)$cfg->carry_forward_status === 1);
        $carry_mode    = !empty($cfg->carry_forward_mode) ? strtoupper($cfg->carry_forward_mode) : 'LIFETIME';
        $carry_cap     = isset($cfg->carry_forward_cap) && $cfg->carry_forward_cap !== null ? (float)$cfg->carry_forward_cap : null;

        // Window for NEW BV since last run (fallback: today only)
        $todayStart = $run_date . ' 00:00:00';
        $todayEnd   = $run_date . ' 23:59:59';

        $users = $this->db->select('id, package_id')->from('users')->where('status','1')->get()->result();
        foreach ($users as $u) {
            $user_id = (int)$u->id;

            // receiver package
            $pkg = null;
            if (!empty($u->package_id)) {
                $pkg = $this->db->get_where('package_config', ['id'=>(int)$u->package_id, 'status'=>'1'])->row();
            }
            if (!$pkg) continue;

            $pair_enabled = isset($pkg->pair_commission_status) ? (int)$pkg->pair_commission_status : 0;
            $pair_val     = isset($pkg->pair_commission) ? (float)$pkg->pair_commission : 0;
            $pair_type    = isset($pkg->pair_commission_type) && $pkg->pair_commission_type ? $pkg->pair_commission_type : $global_pair_type;
            $daily_cap    = isset($pkg->daily_max_pairs) ? (int)$pkg->daily_max_pairs : 0; // 0 unlimited

            if ($pair_enabled !== 1 || $pair_val <= 0) continue;

            // Idempotence: if pair_commission rows already exist today, we will subtract their pairs_count.
            $pairs_paid_today = (int)$this->db->select('COALESCE(SUM(pairs_count),0) AS c', false)
                ->from('history')
                ->where('user_id', $user_id)
                ->where('type', 'pair_commission')
                ->where('DATE(history_date)', $run_date)
                ->get()->row()->c;

            // Load carry row for this scope
            $scope_key = strtolower($carry_mode);
            $carryRow = $this->get_or_create_carry_forward_row($user_id, $scope_key);

            // Flush carry based on mode (if not LIFETIME)
            if ($carry_enabled && $carry_mode !== 'LIFETIME') {
                $this->apply_carry_flush_if_needed($carryRow, $carry_mode, $run_date);
                // reload after potential flush
                $carryRow = $this->get_or_create_carry_forward_row($user_id, $scope_key);
            }

            $left_carry  = $carry_enabled ? (float)$carryRow->left_carry : 0.0;
            $right_carry = $carry_enabled ? (float)$carryRow->right_carry : 0.0;

            // NEW BV posted today to this user (receiver) by leg
            $newLeft  = $this->sum_bv_volume($user_id, 'left',  $todayStart, $todayEnd);
            $newRight = $this->sum_bv_volume($user_id, 'right', $todayStart, $todayEnd);

            $left_total  = $left_carry  + $newLeft;
            $right_total = $right_carry + $newRight;

            // possible pairs (integer)
            $pairs_possible = (int)floor(min($left_total / $rL, $right_total / $rR));
            if ($pairs_possible <= 0) {
                // No pairs today, just update carry totals if enabled
                if ($carry_enabled) {
                    $this->save_carry_forward_row($user_id, $scope_key,
                        $this->apply_cap($left_total, $carry_cap),
                        $this->apply_cap($right_total, $carry_cap),
                        $run_date
                    );
                }
                continue;
            }

            // Pairs to pay today (idempotent)
            // If daily_cap = 0 => unlimited pairs/day
            // Otherwise pay at most (daily_cap - already_paid)
            if ($daily_cap > 0) {
                $pairs_to_pay = max(0, min($pairs_possible, $daily_cap) - $pairs_paid_today);
            } else {
                $pairs_to_pay = max(0, $pairs_possible - $pairs_paid_today);
            }

            if ($pairs_to_pay <= 0) {
                // even if we don't pay, carry still should accumulate
                if ($carry_enabled) {
                    $this->save_carry_forward_row($user_id, $scope_key,
                        $this->apply_cap($left_total, $carry_cap),
                        $this->apply_cap($right_total, $carry_cap),
                        $run_date
                    );
                }
                continue;
            }

            // consume matched BV
            $matched_left  = $pairs_to_pay * $rL;
            $matched_right = $pairs_to_pay * $rR;

            $rem_left  = $left_total  - $matched_left;
            $rem_right = $right_total - $matched_right;

            // Carry-forward store:
            // - If carry is disabled: reset to 0
            // - If carry is enabled: keep remainder (both sides) because UI shows both legs carry
            if ($carry_enabled) {
                $this->save_carry_forward_row($user_id, $scope_key,
                    $this->apply_cap(max(0, $rem_left), $carry_cap),
                    $this->apply_cap(max(0, $rem_right), $carry_cap),
                    $run_date
                );
            } else {
                $this->save_carry_forward_row($user_id, $scope_key, 0, 0, $run_date);
            }

            // Calculate pair commission amount
            // base BV used for percent = matched_left + matched_right
            $base_bv = $matched_left + $matched_right;
            $pair_amt = ($pair_type === 'percent')
                ? ($base_bv * ($pair_val / 100.0))
                : ($pair_val * $pairs_to_pay);

            if ($pair_amt <= 0) continue;

            // Insert history row (pair_commission)
            $currency = currency_info();
            $token    = token_info();

            $payload = [
                'user_id'      => $user_id,
                'amount'       => (float)$pair_amt,
                'token_amount' => 0,
                'type'         => 'pair_commission',
                'history_date' => $run_date . ' 10:00:00',
                'date'         => $run_date . ' 10:00:00',
                'status'       => '1',
                'hash_id'      => 'system',
                'invest_id'    => 0,
                'description'  => "Binary Pair ({$pairs_to_pay} pairs, {$rL}:{$rR})",
                'coin_id'      => $currency->id ?? null,
                'token_id'     => $token->id ?? null,
                'coin_type'    => 1,
                'pairs_count'  => $pairs_to_pay,
                'pair_ratio_used' => "{$rL}:{$rR}",
                'total_left_invest'  => $matched_left,
                'total_right_invest' => $matched_right,
            ];
            $this->db->insert('history', $payload);
            $pair_history_id = (int)$this->db->insert_id();

            // Matching bonus (based on receiver's package matching_bonus_json)
            if ((int)$cfg->matching_bonus_status === 1 && $pair_history_id > 0) {
                $this->pay_matching_bonus_over_pair_income($user_id, $pair_amt, $pair_history_id, $run_date);
            }
        }
    }

    /** Sum BV volume posted to a receiver by leg between timestamps. */
    private function sum_bv_volume(int $receiver_id, string $leg, string $fromTs, string $toTs): float
    {
        $row = $this->db->select('COALESCE(SUM(amount),0) AS s', false)
            ->from('history')
            ->where('user_id', $receiver_id)
            ->where('type', 'bv_volume')
            ->where('leg', $leg)
            ->where('date >=', $fromTs)
            ->where('date <=', $toTs)
            ->get()->row();
        return (float)($row->s ?? 0);
    }

    /** Ensure carry forward row exists for a user+scope. */
    private function get_or_create_carry_forward_row(int $user_id, string $scope_key)
    {
        $row = $this->db->get_where('binary_carry_forward', ['user_id'=>$user_id, 'scope_key'=>$scope_key])->row();
        if (!$row) {
            $this->db->insert('binary_carry_forward', [
                'user_id'    => $user_id,
                'left_carry' => 0,
                'right_carry'=> 0,
                'scope_key'  => $scope_key,
                'updated_at' => date('Y-m-d H:i:s'),
                'last_run_date' => null,
            ]);
            $row = $this->db->get_where('binary_carry_forward', ['user_id'=>$user_id, 'scope_key'=>$scope_key])->row();
        }
        return $row;
    }

    /** Save carry forward values for a user+scope. */
    private function save_carry_forward_row(int $user_id, string $scope_key, float $left, float $right, string $run_date): void
    {
        $exists = $this->db->get_where('binary_carry_forward', ['user_id'=>$user_id, 'scope_key'=>$scope_key])->row();
        $data = [
            'left_carry' => $left,
            'right_carry'=> $right,
            'updated_at' => date('Y-m-d H:i:s'),
            'last_run_date' => $run_date,
        ];
        if ($exists) {
            $this->db->update('binary_carry_forward', $data, ['user_id'=>$user_id, 'scope_key'=>$scope_key]);
        } else {
            $this->db->insert('binary_carry_forward', array_merge(['user_id'=>$user_id, 'scope_key'=>$scope_key], $data));
        }
    }

    /** Apply cap if configured. */
    private function apply_cap(float $v, ?float $cap): float
    {
        if ($cap === null || $cap <= 0) return $v;
        return min($v, $cap);
    }

    /** Flush carry values when mode is DAILY/WEEKLY/MONTHLY and new period starts. */
    private function apply_carry_flush_if_needed($carryRow, string $mode, string $run_date): void
    {
        if (empty($carryRow)) return;
        if (empty($carryRow->last_run_date)) return;

        $last = new DateTime($carryRow->last_run_date);
        $cur  = new DateTime($run_date);

        $flush = false;
        if ($mode === 'DAILY') {
            $flush = $cur->format('Y-m-d') !== $last->format('Y-m-d');
        } elseif ($mode === 'WEEKLY') {
            $flush = $cur->format('oW') !== $last->format('oW');
        } elseif ($mode === 'MONTHLY') {
            $flush = $cur->format('Ym') !== $last->format('Ym');
        }

        if ($flush) {
            $this->db->update('binary_carry_forward', [
                'left_carry' => 0,
                'right_carry'=> 0,
                'updated_at' => date('Y-m-d H:i:s'),
            ], ['user_id'=>(int)$carryRow->user_id, 'scope_key'=>(string)$carryRow->scope_key]);
        }
    }

    /** Matching bonus: pay uplines a % of receiver's pair income based on receiver package matching_bonus_json. */
    private function pay_matching_bonus_over_pair_income(int $receiver_id, float $pair_income, int $ref_history_id, string $run_date): void
    {
        if ($pair_income <= 0) return;

        // receiver's package
        $u = $this->db->select('package_id, sponser')->from('users')->where('id',$receiver_id)->get()->row();
        if (!$u || empty($u->package_id)) return;

        $pkg = $this->db->get_where('package_config', ['id'=>(int)$u->package_id])->row();
        if (!$pkg || empty($pkg->matching_bonus_json)) return;

        $levels = json_decode($pkg->matching_bonus_json, true);
        if (!is_array($levels) || empty($levels)) return;

        $currency = currency_info();
        $token    = token_info();

        // start from receiver's sponsor
        $upline_id = (int)($u->sponser ?? 0);
        $level_no = 1;
        foreach ($levels as $pct) {
            if ($upline_id <= 0) break;

            $pct = (float)$pct;
            if ($pct > 0) {
                // avoid duplicates
                $exists = $this->db->where('user_id', $upline_id)
                    ->where('type', 'matching_bonus')
                    ->where('ref_history_id', $ref_history_id)
                    ->count_all_results('history');

                if (!$exists) {
                    $amt = $pair_income * ($pct / 100.0);
                    if ($amt > 0) {
                        $this->db->insert('history', [
                            'user_id'      => $upline_id,
                            'amount'       => $amt,
                            'token_amount' => 0,
                            'type'         => 'matching_bonus',
                            'history_date' => $run_date . ' 11:00:00',
                            'date'         => $run_date . ' 11:00:00',
                            'status'       => '1',
                            'hash_id'      => 'system',
                            'invest_id'    => 0,
                            'description'  => "Matching Bonus L{$level_no} from #{$receiver_id}",
                            'coin_id'      => $currency->id ?? null,
                            'token_id'     => $token->id ?? null,
                            'coin_type'    => 1,
                            'from_id'      => $receiver_id,
                            'level_count'  => $level_no,
                            'ref_history_id' => $ref_history_id,
                        ]);
                    }
                }
            }

            // climb sponsor chain
            $next = $this->db->select('sponser')->from('users')->where('id',$upline_id)->get()->row();
            $upline_id = $next ? (int)$next->sponser : 0;
            $level_no++;
        }
    }
    /**
     * Package Reinvestemnt
    **/
    private function package_mature($investment_id) {
        $today = date('Y-m-d');
        
        $this->db->select('*');
        $this->db->from('user_investment');
        $this->db->where('id', $investment_id);
        $investment = $this->db->get()->row();
        
        if (!$investment) return;
    
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
                    "status"  => '1',
                    "created_date" => $invest_date,
                    "days_count"  => $package->days_duration,
                    "profit"  => $package->roi,
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
                        "status"  => '1',
                        "hash_id" => $investment->hash_id,
                        "invest_id" => $new_invest_id,
                        "token_amount" => $investment->csq_deposit,
                        "description" => "Investment Successfully",
                        "coin_id" => $investment->currency_id,
                        "token_id" => $investment->token_id,
                        "coin_type" =>  '1',
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
                    "status"  => '1',
                    "hash_id" => $investment->hash_id,
                    "invest_id" => $investment->id,
                    "token_amount" => $investment->csq_deposit,
                    "description" => "Package Matured Successfully ( ".$package->package_name." )",
                    "coin_type" => '1',
                    "token_id" => $investment->token_id,
                    "coin_id" =>  $investment->currency_id,
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
    private function process_binary_commission($run_date) {
        
        
        $this->db->select('id');
        $this->db->where_in('status','1');
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
                    $binary_commission_usd =  $binary_commission / $token_info->currency_value;

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
    private function get_leg_users($parent_id, $position) {
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
    private function get_users_roi_total($user_ids, $run_date) {
        if (empty($user_ids)) return ['total_profit' => 0, 'total_profit_token' => 0, 'invest_ids' => '', 'total_invest_amount' => 0];

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
        $this->db->where_in('status','1');
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
    private function get_users_roi_total_amount($user_ids, $run_date) {

        if (empty($user_ids)) return ['total_invest_amount' => 0];
        
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
    public function update_all_users_rank() {
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
    function calculate_user_rank($user_id, $month, $year) {

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

        $achieved_rank_get = $this->find_rank_by_user($minimum_leg_investment,$left_leg_users,$right_leg_users);
  

        if ($achieved_rank_get['achieved_rank'] > 0) {

     
            $achieved_rank = $achieved_rank_get['achieved_rank'];
            $achieved_left_user = $achieved_rank_get['achieved_left_user'];
            $achieved_right_user = $achieved_rank_get['achieved_right_user'];

            
            $this->db->set('rank_id', $achieved_rank);
            $this->db->where('id', $user_id);
            $this->db->update('users');

            $this->db->select('rank_bonus, rank_name');
            $this->db->where('id', $achieved_rank);
            $rank_data =  $this->db->get('rank_config')->row();

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

        }  else {

        if ($current_rank > 0) {
            $this->db->where('id', $user_id);
           $this->db->update('users', ['rank_id' => 0]);
        }

        }

    }
    /**
     * Find Rank Achievement
     **/
   public function find_rank_by_user($minimum_leg_investment,$left_leg_users,$right_leg_users){



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

                $achieved_left_user  = $achieved_left_user_get;
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

   /**
     * Sum volume for users on a given date.
     * $basis: 'PV'  -> use investments volume (invest_amount) of that date
     *         'BV'  -> use ROI token earnings (token_amount) of that date
     * Returns ['volume'=>float, 'invest_ids'=>csv, 'user_ids'=>csv]
     */


   private function process_binary_commission_v2($run_date)
    {
        $cfg = $this->db->get_where('commission_config', ['id'=>1])->row();
        if (!$cfg || !$cfg->binary_commission_status) return;

        $basis = $cfg->binary_pair_on ?: 'PV';                 // PV or BV
        $type  = $cfg->binary_pair_type ?: 'percent';          // percent / amount

        // Ratio units, e.g. "1:2"
        $ratio = $cfg->binary_pair_ratio ?: '1:1';
        [$rL, $rR] = array_map('intval', explode(':', $ratio));
        if ($rL<=0 || $rR<=0) { $rL=1; $rR=1; }

        $carry_mode = $cfg->binary_carry_forward ?: 'weak_leg'; // none/weak_leg/both
        $flush_rule = $cfg->binary_flush_rule ?: 'daily';       // never/daily/weekly/monthly

        $token_info    = token_info();

        // All active users
        $users = $this->db->select('id')->from('users')->where('status','1')->get()->result();

        foreach ($users as $u) {
            $user_id = $u->id;

            // Skip if we already credited today (idempotence)
            $exists = $this->db->where('user_id', $user_id)
                ->where('type', 'binary_commission')
                ->where('DATE(history_date)', $run_date)
                ->count_all_results('history');
            if ($exists) continue;

            // Get both legs
            $left_users  = $this->get_leg_users($user_id, 'left');
            $right_users = $this->get_leg_users($user_id, 'right');

            // Compute today's raw volumes by basis
            $L = $this->sum_leg_volume($left_users,  $run_date, $basis);
            $R = $this->sum_leg_volume($right_users, $run_date, $basis);

            $left_vol  = (float)$L['volume'];
            $right_vol = (float)$R['volume'];

            // Bring in carry + apply flush rule
            $carry = $this->get_carry_row($user_id);

            $left_carry  = (float)$carry->left_carry;
            $right_carry = (float)$carry->right_carry;

            // Reset (flush) according to rule
            if ($flush_rule !== 'never' && !empty($carry->last_flush_at)) {
                $last = new DateTime($carry->last_flush_at);
                $cur  = new DateTime($run_date);

                $flush_now = false;
                if ($flush_rule === 'daily')   $flush_now = $cur > $last;
                if ($flush_rule === 'weekly')  $flush_now = (int)$cur->format('oW') !== (int)$last->format('oW');
                if ($flush_rule === 'monthly') $flush_now = $cur->format('Ym') !== $last->format('Ym');

                if ($flush_now) {
                    $left_carry = $right_carry = 0;
                }
            }

            // Add carry to today’s volume
            $left_vol  += $left_carry;
            $right_vol += $right_carry;

            if ($left_vol <= 0 && $right_vol <= 0) {
                // nothing to pair, just update flush marker
                $this->save_carry($user_id, $left_vol, $right_vol, $run_date);
                continue;
            }

            // Calculate pairs by ratio a:b
            $pairs = floor(min($left_vol / $rL, $right_vol / $rR));


            if ($pairs <= 0) {
                // no complete pair, carry forward diffs as per rule
                $new_left_carry  = $left_vol;
                $new_right_carry = $right_vol;

                if ($carry_mode === 'none') { $new_left_carry = 0; $new_right_carry = 0; }
                if ($carry_mode === 'weak_leg') {
                    if ($left_vol <= $right_vol) { $new_right_carry = 0; }
                    else { $new_left_carry = 0; }
                }
                $this->save_carry($user_id, $new_left_carry, $new_right_carry, $run_date);
                continue;
            }

            // Matched volumes consumed by those pairs
            $matched_left  = $pairs * $rL;
            $matched_right = $pairs * $rR;

            // Remaining after matching
            $rem_left  = $left_vol  - $matched_left;
            $rem_right = $right_vol - $matched_right;

            // Apply carry rule on remainders
            $new_left_carry  = 0;
            $new_right_carry = 0;
            if ($carry_mode === 'both') {
                $new_left_carry  = max(0, $rem_left);
                $new_right_carry = max(0, $rem_right);
            } elseif ($carry_mode === 'weak_leg') {
                if ($rem_left <= $rem_right) {
                    $new_left_carry = max(0, $rem_left);
                } else {
                    $new_right_carry = max(0, $rem_right);
                }
            }
            // 'none' keeps both 0

            $this->save_carry($user_id, $new_left_carry, $new_right_carry, $run_date);

            // Commission base:
            // - If percent: apply % on the *weak leg matched volume* (standard practice)
            //   weak matched = pairs * min(rL, rR)
            // - If amount: fixed amount per pair
            $weak_unit   = min($rL, $rR);
            $base_volume = $pairs * $weak_unit; // in PV/BV units

            if ($type === 'percent') {
                $percent     = (float)$cfg->binary_pair_percent;
                $token_amt   = ($base_volume * $percent) / 100.0;  // token units if BV; PV has no “token” – we still treat it as points and pay as token by ratio
            } else {
                $per_pair    = (float)$cfg->binary_pair_amount;
                $token_amt   = $pairs * $per_pair;                 // token units (you can choose to treat as BV-equivalent)
            }

            // If basis was PV, you may want to convert PV→token at your own internal rate.
            // Here we assume PV == token units for payout; adjust as needed.

            $usd_amt = $this->token_to_usd($token_amt);

            // Credit with caps/threshold and extra meta for reporting
            $meta = [
                "total_left_roi"        => ($basis==='BV') ? $L['volume'] : null,
                "total_right_roi"       => ($basis==='BV') ? $R['volume'] : null,
                "total_left_invest"     => ($basis==='PV') ? $L['volume'] : null,
                "total_right_invest"    => ($basis==='PV') ? $R['volume'] : null,
                "total_left_users"      => $L['user_ids'],
                "total_right_users"     => $R['user_ids'],
                "total_left_invest_ids" => $L['invest_ids'],
                "total_right_invest_ids"=> $R['invest_ids'],
                "pair_ratio_used"       => $ratio,
                "pairs_count"           => $pairs,
                "basis"                 => $basis,
            ];

            $this->credit_binary_history($user_id, $run_date, $usd_amt, $token_amt, $meta);
        }
    }

    private function sum_leg_volume(array $user_ids, string $run_date, string $basis='BV'): array
    {
        if (empty($user_ids)) {
            return ['volume'=>0, 'invest_ids'=>'', 'user_ids'=>''];
        }

        $ids_csv = implode(',', $user_ids);
        $this->db->reset_query();

        if ($basis === 'PV') {
            // Sum the day's investments (or active investments created that day)
            $q = $this->db->select('SUM(invest_amount) AS vol, GROUP_CONCAT(id) AS invest_ids', false)
                ->from('user_investment')
                ->where_in('user_id', $user_ids)
                ->where('status', 1)
                ->get()->row();
            return [
                'volume'     => (float)($q->vol ?? 0),
                'invest_ids' => (string)($q->invest_ids ?? ''),
                'user_ids'   => $ids_csv
            ];
        }

        // BV: use ROI token earnings posted for that date
        $q = $this->db->select('SUM(amount) AS vol')
            ->from('history')
            ->where_in('user_id', $user_ids)
            ->where('type', 'profit')                    // your ROI rows
            ->where('DATE(history_date)', $run_date)
            ->get()->row();

        return [
            'volume'     => (float)($q->vol ?? 0),
            'invest_ids' => '',
            'user_ids'   => $ids_csv
        ];
    }

    /** Get or create a carry row for user. */
    private function get_carry_row($user_id)
    {
        $row = $this->db->get_where('binary_carry', ['user_id'=>$user_id])->row();
        if (!$row) {
            $this->db->insert('binary_carry', ['user_id'=>$user_id, 'left_carry'=>0, 'right_carry'=>0, 'last_flush_at'=>null]);
            $row = $this->db->get_where('binary_carry', ['user_id'=>$user_id])->row();
        }
        return $row;
    }

    /** Update carry row. */
    private function save_carry($user_id, $left, $right, $run_date)
    {
        $this->db->update('binary_carry', [
            'left_carry'    => (float)$left,
            'right_carry'   => (float)$right,
            'last_flush_at' => $run_date
        ], ['user_id'=>$user_id]);
    }

    /** Convert token amount to USD with your helpers. */
    private function token_to_usd($token_amount)
    {
        $token_info    = token_info();
        if (empty($token_info->currency_value) || $token_info->currency_value <= 0) return 0;
        return (float)$token_amount / (float)$token_info->currency_value;
    }

    /** Credit history row (respects payout caps & threshold). */
    private function credit_binary_history($user_id, $run_date, $usd_amount, $token_amount, array $meta)
    {
        $token_info    = token_info();
        $currency_info = currency_info();

        // Global caps from config
        $cfg = $this->db->get_where('commission_config', ['id'=>1])->row();
        $usd_amount = (float)$usd_amount;

        // Daily cap
        if (!empty($cfg->payout_daily_cap) && $cfg->payout_daily_cap > 0) {
            // Sum today's credited binary_commission
            $sum = $this->db->select('SUM(amount) AS s')->from('history')
                ->where('user_id',$user_id)->where('type','binary_commission')
                ->where('DATE(history_date)',$run_date)->get()->row();
            $already = (float)($sum->s ?? 0);
            $room    = (float)$cfg->payout_daily_cap - $already;
            if ($room <= 0) return false;
            if ($usd_amount > $room) {
                $token_amount = $token_amount * ($room / $usd_amount);
                $usd_amount   = $room;
            }
        }

        // Min threshold
        if (!empty($cfg->payout_threshold_min) && $usd_amount < $cfg->payout_threshold_min) {
            return false;
        }

        $payload = array_merge([
            "user_id"       => $user_id,
            "amount"        => round($usd_amount, 6),
            "type"          => "binary_commission",
            "history_date"  => $run_date . ' 10:00:00',
            "date"          => $run_date . ' 10:00:00',
            "status"        => '1',
            "coin_type"     => '1',
            "token_amount"  => round($token_amount, 6),
            "description"   => "Binary Commission Earned",
            "coin_id"       => $currency_info->id ?? null,
            "token_id"      => $token_info->id ?? null,
        ], $meta);

        $this->db->insert('history', $payload);
        return true;
    }

    //************** matching bonus check  **************************/

    private function process_matching_bonus($run_date)
{
    $cfg = $this->db->get_where('commission_config', ['id'=>1])->row();
    if (!$cfg || !$cfg->matching_bonus_status) return;

    $levels   = (int)$cfg->matching_levels;
    if ($levels <= 0) return;

    $percents = array_map('floatval', array_filter(array_map('trim', explode(',', (string)$cfg->matching_percents))));
    if (empty($percents)) return;

    // Pull all binary commissions posted on run_date
    $rows = $this->db->select('user_id, amount, token_amount, id')
        ->from('history')
        ->where('type','binary_commission')
        ->where('DATE(history_date)', $run_date)
        ->get()->result();

    foreach ($rows as $row) {
        $earner_id   = $row->user_id;
        $bin_usd     = (float)$row->amount;
        $bin_token   = (float)$row->token_amount;

        // Walk up sponsor chain
        $upline_id = $this->get_sponsor_id($earner_id); // You must have this helper; see stub below
        for ($lv=1; $lv <= $levels && $upline_id; $lv++) {

            $percent = isset($percents[$lv-1]) ? (float)$percents[$lv-1] : 0;
            if ($percent <= 0) {
                $upline_id = $this->get_sponsor_id($upline_id);
                continue;
            }

            // Avoid duplicate if run twice
            $exists = $this->db->where('user_id', $upline_id)
                ->where('type','matching_bonus')
                ->where('ref_history_id', $row->id)      // reference child’s binary row
                ->count_all_results('history');
            if ($exists) {
                $upline_id = $this->get_sponsor_id($upline_id);
                continue;
            }

            $bonus_token = $bin_token * $percent / 100.0;
            $bonus_usd   = $this->token_to_usd($bonus_token);

            $meta = [
                "description"    => "Matching Bonus L{$lv} from #{$earner_id}",
                "ref_history_id" => $row->id,
                "source_user_id" => $earner_id,
                "level_no"       => $lv
            ];

            $this->credit_matching_history($upline_id, $run_date, $bonus_usd, $bonus_token, $meta);

            $upline_id = $this->get_sponsor_id($upline_id);
        }
    }
}

/** Minimal “credit” for matching (separate type). */
private function credit_matching_history($user_id, $run_date, $usd_amount, $token_amount, array $meta)
{
    $token_info    = token_info();
    $currency_info = currency_info();

    $cfg = $this->db->get_where('commission_config', ['id'=>1])->row();
    $usd_amount = (float)$usd_amount;

    // You may want to reuse payout caps; here we only check min threshold
    if (!empty($cfg->payout_threshold_min) && $usd_amount < $cfg->payout_threshold_min) {
        return false;
    }

    $payload = array_merge([
        "user_id"       => $user_id,
        "amount"        => round($usd_amount, 6),
        "type"          => "matching_bonus",
        "history_date"  => $run_date . ' 11:00:00',
        "date"          => $run_date . ' 11:00:00',
        "status"        => '1',
        "coin_type"     => '1',
        "token_amount"  => round($token_amount, 6),
        "coin_id"       => $currency_info->id ?? null,
        "token_id"      => $token_info->id ?? null,
    ], $meta);

    $this->db->insert('history', $payload);
    return true;
}

/** Stub: get sponsor/upline id; replace with your actual logic/column name. */
private function get_sponsor_id($user_id)
{
    $u = $this->db->select('sponser')->from('users')->where('id', $user_id)->get()->row();
    return $u ? (int)$u->sponser : null;
}
   
}

