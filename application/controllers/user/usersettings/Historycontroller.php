<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class  Historycontroller extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');

        if($this->session->userdata('logged_in') && $this->session->userdata('user_login')) {
			$this->lang->load('common',$this->session->userdata('language'));
		} else {
			redirect('user/in');
		}

        $language = $this->session->userdata('site_lang') ?? 'english';
		$this->config->set_item('language', $language);
		$this->lang->load('common', $language);

    }

/*
|----------------------------------------------------------------------
| Bonus Page Index (User)
|----------------------------------------------------------------------
*/
// public function index()
// {
//     $userid = (int)$this->session->userdata('userid');

//     $this->data['user_id']    = $userid;
//     $this->data['title']      = "Earnings & Bonuses";
//     $this->data['card_title'] = "My Earnings";

//     // Default range: last 30 days
//     $this->data['from'] = date('Y-m-d', strtotime('-29 days'));
//     $this->data['to']   = date('Y-m-d');

//     $this->load->view('user/wallet/profit_management', $this->data);
// }


    public function index()
    {
        $userid = (int)$this->session->userdata('userid');
        if (!$userid) {
            redirect('user/login');
            return;
        }

        $this->load->model('Wallet_model', 'wallet');

        $this->data['user_id']    = $userid;
        $this->data['title']      = "Earnings & Bonuses";
        $this->data['card_title'] = "Commissions";

        // ✅ Default range: last 30 days
        $this->data['from'] = $this->input->get('from') ? $this->input->get('from') : '';
        $this->data['to']   = $this->input->get('to')   ? $this->input->get('to')   : '';

        // ✅ Filters (optional)
        $filters = [
            'q'      => trim((string)$this->input->get('q')),
            'type'   => strtoupper(trim((string)$this->input->get('type'))),   // PAIRING/MATCHING/DIRECT/RANK/LEADERSHIP/WITHDRAW
            'status' => strtoupper(trim((string)$this->input->get('status'))), // SUCCESS/PENDING/REJECTED
            'from'   => $this->data['from'],
            'to'     => $this->data['to'],
        ];

        $page     = max(1, (int)$this->input->get('page'));
        $per_page = 5;

        // ✅ Summary cards
        $this->data['wallet_balance']      = (float) site_wallet_balance($userid);
        $this->data['pending_commission']  = (float) $this->wallet->getPendingCommissionFromInvestments($userid);
        $this->data['total_earned']        = (float) $this->wallet->getTotalCommissionEarned($userid);
        // $this->data['total_earned']        = (float)lifetime_income($userid);
        // $this->data['paid_out']            = (float) $this->wallet->getTotalCommissionPaid($userid, $filters);

        $paid_row = $this->db->select('IFNULL(SUM(amount),0) AS s', false)
			->from('withdrawals')
			->where('user_id', $userid)
			->where_in('status', ['paid', 'success', 'completed', 'approved'])
			->get()->row();
		$this->data['paid_out']  = (float) ($paid_row->s ?? 0);

        // ✅ Table rows + counts + pagination
        $list = $this->wallet->getCommissionHistory($userid, $filters, $page, $per_page);

        $this->data['rows']   = $list['rows'];
        $this->data['counts'] = $list['counts'];
        $this->data['paging'] = $list['paging'];
        $this->data['filters']= $filters;


        // // Week range (Mon → Sun)
        // $weekStart = date('Y-m-d 00:00:00', strtotime('monday this week'));
        // $weekEnd   = date('Y-m-d 23:59:59', strtotime('sunday this week'));

        // // Total pairs matched this week (like commission page)
        // $total_pairs_this_week = (int) $this->db->select('COALESCE(SUM(pairs_count),0) AS c', false)
        //     ->from('history')
        //     ->where('user_id', $userid)
        //     ->where_in('type', ['pair_commission', 'binary_commission'])
        //     ->where('date >=', $weekStart)
        //     ->where('date <=', $weekEnd)
        //     ->get()->row()->c;

        // // Weekly target (match the same as commission page)
        // $weekly_target = 100; // example, replace with dynamic target if needed

        // $weekly_progress = ($weekly_target > 0)
        //     ? min(100, round(($total_pairs_this_week / $weekly_target) * 100, 2))
        //     : 0;

        // echo "<pre>";
        // print_r($this->data);
        // echo "</pre>";
        // exit;
        $this->load->view('user/wallet/profit_management', $this->data);
    }


/*
|----------------------------------------------------------------------
| Earnings API (JSON)
| GET /user/historyprofit?from=YYYY-MM-DD&to=YYYY-MM-DD
|----------------------------------------------------------------------
*/
public function historyprofit()
{
    try {
        $userId = (int)$this->session->userdata('userid');
        if (!$userId) {
            echo json_encode(['status'=>false,'message'=>'Unauthorized']); return;
        }

        // Range (defaults = last 30 days)
        $from = $this->input->get('from', true) ?: date('Y-m-d', strtotime('-29 days'));
        $to   = $this->input->get('to', true)   ?: date('Y-m-d');
        $todayDate = date('Y-m-d');

        $currency_info = currency_info();
        $curDec   = (int)($currency_info->decimal ?? 2);
        $curSym   = (string)($currency_info->currency_symbol ?? 'USD');

        // ---------- Totals / Cards (ALL USD via `amount`) ----------
      $totals = $this->db->query(
        "SELECT 
            SUM(CASE WHEN type='profit'            THEN amount ELSE 0 END) AS roi_usd,
            SUM(CASE WHEN type='direct_commission' THEN amount ELSE 0 END) AS direct_usd,
            SUM(CASE WHEN type='level_commission'  THEN amount ELSE 0 END) AS level_usd,
            SUM(CASE WHEN type='binary_commission' THEN amount ELSE 0 END) AS binary_usd,
            SUM(CASE WHEN type='matching_bonus'    THEN amount ELSE 0 END) AS matching_usd,
            SUM(CASE WHEN type='rank_commission'   THEN amount ELSE 0 END) AS rank_usd
        FROM history
        WHERE user_id=? AND DATE(COALESCE(history_date, `date`)) BETWEEN ? AND ?",
        [$userId, $from, $to]
    )->row();



        $lending = $this->db->query(
            "SELECT COALESCE(SUM(invest_amount),0) AS invest_total
             FROM user_investment WHERE user_id=? AND status='1'",
            [$userId]
        )->row();

        $todayBinary = $this->db->query(
            "SELECT COALESCE(SUM(amount),0) AS usd 
             FROM history WHERE user_id=? AND type='binary_commission' AND DATE(history_date)=?",
            [$userId, $todayDate]
        )->row();

        $rankRow = $this->db->query("SELECT rank_id FROM users WHERE id=? AND rank_id>0", [$userId])->row();
        $rankName = "No Rank";
        if (!empty($rankRow->rank_id)) {
            $r = $this->db->query("SELECT rank_name FROM rank_config WHERE id=?", [$rankRow->rank_id])->row();
            $rankName = $r->rank_name ?? "No Rank";
        }

        $teamStats       = $this->getTeamStats($userId);
        $investmentStats = $this->getInvestmentStats($userId);

        $roiUSD      = (float)($totals->roi_usd ?? 0);
        $directUSD   = (float)($totals->direct_usd ?? 0);
        $levelUSD    = (float)($totals->level_usd ?? 0);
        $binaryUSD   = (float)($totals->binary_usd ?? 0);
        $matchingUSD = (float)($totals->matching_usd ?? 0);
        $rankUSD     = (float)($totals->rank_usd ?? 0);

        $totalUSD = $roiUSD + $directUSD + $levelUSD + $binaryUSD + $matchingUSD + $rankUSD;

        // ---------- Series (daily, USD) ----------
        $roiDaily = $this->db->query(
            "SELECT DATE(history_date) d, COALESCE(SUM(amount),0) usd
             FROM history
             WHERE user_id=? AND type='profit' AND DATE(history_date) BETWEEN ? AND ?
             GROUP BY DATE(history_date) ORDER BY d", [$userId,$from,$to]
        )->result();

        $binaryDaily = $this->db->query(
            "SELECT DATE(history_date) d, COALESCE(SUM(amount),0) usd
             FROM history
             WHERE user_id=? AND type='binary_commission' AND DATE(history_date) BETWEEN ? AND ?
             GROUP BY DATE(history_date) ORDER BY d", [$userId,$from,$to]
        )->result();

        $breakdown = $this->db->query(
            "SELECT type, COALESCE(SUM(amount),0) AS usd
             FROM history
             WHERE user_id=? AND type IN ('profit','direct_commission','level_commission','binary_commission','matching_bonus','rank_commission')
               AND DATE(history_date) BETWEEN ? AND ?
             GROUP BY type", [$userId,$from,$to]
        )->result();

        // ---------- Payload ----------
        $data = [
            'meta' => [
                'currency_symbol' => $curSym,
                'currency_dec'    => $curDec,
            ],
            'range' => ['from'=>$from,'to'=>$to],
            'cards' => [
                'total_income_usd'   => number_format($totalUSD, $curDec),
                'roi_usd'            => number_format($roiUSD, $curDec),
                'direct_usd'         => number_format($directUSD, $curDec),
                'level_usd'          => number_format($levelUSD, $curDec),
                'binary_today_usd'   => number_format((float)$todayBinary->usd, $curDec),
                'binary_total_usd'   => number_format($binaryUSD, $curDec),
                'matching_usd'       => number_format($matchingUSD, $curDec),
                'rank_bonus_usd'     => number_format($rankUSD, $curDec),
                'rank_name'          => $rankName,
                'team_active'        => (int)($teamStats['total_active_users'] ?? 0),
                'team_inactive'      => (int)($teamStats['total_inactive_users'] ?? 0),
                'pool_left_usd'      => number_format((float)($investmentStats['leftLegInvestment'] ?? 0), $curDec),
                'pool_right_usd'     => number_format((float)($investmentStats['RightlegInvestment'] ?? 0), $curDec),
                'lending_total_usd'  => number_format((float)$lending->invest_total, $curDec),
            ],
            'series' => [
                'roi_daily' => [
                    'labels' => array_map(fn($r)=>$r->d, $roiDaily),
                    'usd'    => array_map(fn($r)=> round((float)$r->usd, 6), $roiDaily),
                ],
                'binary_daily' => [
                    'labels' => array_map(fn($r)=>$r->d, $binaryDaily),
                    'usd'    => array_map(fn($r)=> round((float)$r->usd, 6), $binaryDaily),
                ],
                'breakdown' => [
                    'labels' => array_map(function($r){
                        switch ($r->type) {
                            case 'profit': return 'ROI';
                            case 'direct_commission': return 'Direct';
                            case 'level_commission': return 'Level';
                            case 'binary_commission': return 'Binary';
                            case 'matching_bonus': return 'Matching';
                            case 'rank_commission': return 'Rank';
                            default: return $r->type;
                        }
                    }, $breakdown),
                    'usd'    => array_map(fn($r)=> round((float)$r->usd, 6), $breakdown),
                ]
            ],
            'last_updated' => date('Y-m-d H:i:s')
        ];

        echo json_encode(['status'=>true,'message'=>'OK','data'=>$data]); return;

    } catch (Throwable $e) {
        echo json_encode(['status'=>false,'message'=>'Error fetching earnings','error'=>$e->getMessage()]); return;
    }
}



public function lendingRankHistory(){
    $userId = $this->session->userdata('userid');

    try {

         $total_lending = $this->db->select('SUM(amount) as rank_amount')
            ->where(['user_id' => $user_id, 'type' => 'rank_commission'])
            ->get('history')
            ->row();

        $this->db->select('h.*, r.rank_name, r.rank_eligibel_amt');
        $this->db->from('history h');
        $this->db->join('rank_config r', 'r.id = h.hash_id');
        $this->db->where('h.user_id', $user_id);
        $this->db->where('h.type', 'rank_commission');
        $this->db->where('YEAR(h.history_date)', date('Y'));
        $this->db->where('MONTH(h.history_date)', date('m'));
        $thismonthrank = $this->db->get()->result();
        $Allhistory = $this->db->where(['user_id' => $user_id, 'type' => 'rank_commission'])
            ->order_by('id', 'DESC')
            ->get('history')
            ->result();

        $currency_info = currency_info(); // Define this helper function
        $decimalCurrency = isset($currency_info->decimal) ? $currency_info->decimal : 2;
        $currencySymbol = isset($currency_info->currency_symbol) ? $currency_info->currency_symbol : '';

        $cr_rank_name = "No Rank";
        $cr_rank_eligible_amt = "0.00 " . $currencySymbol;
        $cr_left_tot = "0.00 " . $currencySymbol;
        $cr_right_tot = "0.00 " . $currencySymbol;

        if (!empty($thismonthrank)) {
            $rank = $thismonthrank[0];
            $cr_rank_name = $rank->rank_name;
            $cr_rank_eligible_amt = $rank->rank_eligibel_amt > 0 ? number_format($rank->rank_eligibel_amt, $decimalCurrency) . " " . $currencySymbol : "0.00 " . $currencySymbol;
            $cr_left_tot = $rank->total_left_invest > 0 ? number_format($rank->total_left_invest, $decimalCurrency) . " " . $currencySymbol : "0.00 " . $currencySymbol;
            $cr_right_tot = $rank->total_right_invest > 0 ? number_format($rank->total_right_invest, $decimalCurrency) . " " . $currencySymbol : "0.00 " . $currencySymbol;
        }

        $total_rank_bonus = !empty($total_lending->rank_amount) ? number_format($total_lending->rank_amount, $decimalCurrency) . " " . $currencySymbol : "0.00 " . $currencySymbol;

        $modifiedHistory = [];

        $profitrank = [
            'Totalrankreward' => $total_rank_bonus,
            'Currentpoolmatch' => $cr_rank_eligible_amt,
            'Currentmonthrank' => $cr_rank_name,
            'leftpool' => $cr_left_tot,
            'rightpool' => $cr_right_tot
        ];

        $this->data['user_id'] = $userId;
        $this->data['Totalrankreward'] = $total_rank_bonus;
        $this->data['Currentpoolmatch']  = $cr_rank_eligible_amt;
        $this->data['Currentmonthrank'] = $cr_rank_name;
        $this->data['leftpool']  = $leftpool;
        $this->data['rightpool']  = $rightpool;
        
        $this->data['title'] = "View Rank information";
        $this->data['card_title'] = "Rank information";

        $this->load->view('user/wallet/view_rank_management',$this->data);


    } catch (Exception $e) {

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => false,
                'message' => 'Error fetching lending profit history data',
                'error' => $e->getMessage()
            ]));
    }
}



public function lendingTeamHistory(){
    $userId = $this->session->userdata('userid');

    try {

        $currency_info = currency_info();
        $decimalCurrency = isset($currency_info->decimal) ? $currency_info->decimal : 2;
        $currencySymbol = isset($currency_info->currency_symbol) ? $currency_info->currency_symbol : '';
        $stats = $this->getTeamStats($userId);
        $poolInvestment = $this->getInvestmentStats($userId);
        $totalLeftInvestment = $poolInvestment['leftLegInvestment'];
        $totalRightInvestment = $poolInvestment['RightlegInvestment'];
        $totalActiveUsers = $stats['total_active_users'];
        $totalinActiveUsers = $stats['total_inactive_users'];
        $TotalLending = floatval($totalLeftInvestment) + floatval($totalRightInvestment);
        $TotalLending_amt = $TotalLending ? number_format($TotalLending, $decimalCurrency) . " " . $currencySymbol : "0.00 " . $currencySymbol;
        $leftLegUsers = $this->getLegUsers($userId, 'left');
        $rightLegUsers = $this->getLegUsers($userId, 'right');
        $leftLegUsers = is_array($leftLegUsers) ? $leftLegUsers : [];
        $rightLegUsers = is_array($rightLegUsers) ? $rightLegUsers : [];
        $allUsers = array_merge($leftLegUsers, $rightLegUsers);
        $Allhistory = $this->getMiningHistory($allUsers, $decimalCurrency, $currencySymbol);


        $this->data['TotalLending_amt'] = $TotalLending_amt;
        $this->data['TotalLending'] = $totalActiveUsers;
        $this->data['inActiveUsers'] = $totalinActiveUsers;
        $this->data['Allhistory'] = $Allhistory;

        $this->data['title'] = "View Team information";
        $this->data['card_title'] = "Team information";

        $this->load->view('user/wallet/view_team_management',$this->data);


    } catch (Exception $e) {

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => false,
                'message' => 'Error fetching lending profit history data',
                'error' => $e->getMessage()
            ]));
    }
}

private function getMiningHistory($userIds, $decimalCurrency, $currencySymbol) {
        if (empty($userIds)) return [];

        $in_clause = implode(',', array_fill(0, count($userIds), '?'));

        $sql = "SELECT * FROM history WHERE user_id IN ($in_clause) AND type = 'mining' ORDER BY date DESC";
        $query = $this->db->query($sql, $userIds);
        $Allhistory = $query->result_array();

        $modifiedHistory = [];

        foreach ($Allhistory as $item) {
            $user_info = $this->db->get_where('users', ['id' => $item['user_id']])->row_array();

            $sponsor_username = "Unknown User";
            $referral_id = "Unknown ID";

            if (!empty($user_info)) {
                $sponsor_username = $user_info['username'];
                $referral_id = $user_info['referral_id'];
            }

            $displayTime = $item['date'];

            $modifiedHistory[] = [
                'Amount' => number_format($item['amount'], $decimalCurrency) . " " . $currencySymbol,
                'sponsor_username' => $sponsor_username,
                'referral_id' => $referral_id,
                'time' => $item['date'],
                'displayTime' => $displayTime,
                'description' => $item['description']
            ];
        }

        return $modifiedHistory;
    }
    
    public function lendingPoolHistory(){
        
         $user_id = $this->session->userdata('userid');

        try {

            $currency_info = currency_info();
            $decimalCurrency = isset($currency_info->decimal) ? $currency_info->decimal : 2;
            $currencySymbol = isset($currency_info->currency_symbol) ? $currency_info->currency_symbol : '';

            $poolInvestment = $this->getInvestmentStats($user_id);

            $totalLeftInvestment = $poolInvestment['leftLegInvestment'];
            $totalRightInvestment = $poolInvestment['RightlegInvestment'];
            
            $TotalLending = floatval($totalLeftInvestment);
            $TotalLending_amt = $TotalLending ? number_format($TotalLending, $decimalCurrency) . ' ' . $currencySymbol : '0.00 ' . $currencySymbol;

            $TotalLendingLeft = floatval($totalRightInvestment);
            $TotalLendingright_amt = $TotalLendingLeft ? number_format($TotalLendingLeft, $decimalCurrency) . ' ' . $currencySymbol : '0.00 ' . $currencySymbol;

            $leftLegUsers = $this->getLegUsers($user_id, 'left');
            $leftLegStatus = $this->getActiveInactiveUsers($leftLegUsers);
            $active_leg_count = count($leftLegStatus['activeUsers']);

            $rightLegUsers = $this->getLegUsers($user_id, 'right');
            $rightLegStatus = $this->getActiveInactiveUsers($rightLegUsers);
            $active_leg_count_right = count($rightLegStatus['activeUsers']);

            $validatedLeftLegUsers = is_array($leftLegUsers) ? $leftLegUsers : [];
            $Allhistory = $this->getMiningHistory($validatedLeftLegUsers, $decimalCurrency, $currencySymbol);
            
            $validatedrightLegUsers = is_array($rightLegUsers) ? $rightLegUsers : [];
            $Allhistoryright = $this->getMiningHistory($validatedrightLegUsers, $decimalCurrency, $currencySymbol);
            $mergedHistory = array_merge($Allhistory, $Allhistoryright);

            $this->data['total_left_mining'] = $TotalLending_amt;
            $this->data['total_left_active'] = $active_leg_count;
            $this->data['total_right_mining'] = $TotalLendingright_amt;
            $this->data['total_right_active'] = $active_leg_count_right;
            $this->data['Allhistory'] = $mergedHistory;

            $this->data['title'] = "View Leg Pool information";
            $this->data['card_title'] = "Leg Pool information";

            $this->load->view('user/wallet/view_pool_management',$this->data);


        } catch (Exception $e) {

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => false,
                    'message' => 'Error fetching lending profit history data',
                    'error' => $e->getMessage()
                ]));
        }
        
    }


    public function lendingReferralHistory(){

        $userId = $this->session->userdata('userid');

        $token_info = token_info(); 
        $decimalPlaces = isset($token_info->decimal) ? $token_info->decimal : 2;
        $tokensymbol = isset($token_info->currency_symbol) ? $token_info->currency_symbol : '';

        $totalLendingGet = $this->db->query("
            SELECT SUM(token_amount) as direct_commission  
            FROM history 
            WHERE user_id = ? AND type = 'direct_commission'
        ", array($userId))->row_array();

        $totalDirectGet = $this->db->query("
            SELECT count(*) as direct_count  
            FROM users 
            WHERE sponser = ?
        ", array($userId))->row_array();

        $level_token_currency = $this->db->query("SELECT sum(token_amount) as direc_token_amt FROM history where type = 'level_commission' and user_id = '".$userId."' ")->row()->direc_token_amt;
        $getDirectTotal = isset($totalDirectGet['direct_count']) ? $totalDirectGet['direct_count'] : 0;
        $total_direct_bonus = isset($totalLendingGet['direct_commission']) && $totalLendingGet['direct_commission']
            ? number_format((float) $totalLendingGet['direct_commission'], $decimalPlaces) . " " . $tokensymbol
            : "0.00 " . $tokensymbol;

        $this->data['total_direct_commission'] = $total_direct_bonus;
        $this->data['getDirectTotal'] = $getDirectTotal;
        $this->data['level_token_currency'] = currency_format($level_token_currency);

        $this->data['title'] = "View Referral information";
        $this->data['card_title'] = "Referral information";

        $this->load->view('user/wallet/view_referral_management',$this->data);

    }


    public function lendingBinaryHistory(){

        $user_id = $this->session->userdata('userid');

        $this->db->where(['user_id' => $user_id, 'type' => 'binary_commission']);
        $binary_history = $this->db->get('history')->result_array();

        // 2. Get total binary bonus
        $this->db->select_sum('token_amount', 'binary_bonus');
        $this->db->where(['user_id' => $user_id, 'type' => 'binary_commission']);
        $total_binary_bonus_row = $this->db->get('history')->row();

        // 3. Get last binary bonus
        $this->db->where(['user_id' => $user_id, 'type' => 'binary_commission']);
        $this->db->order_by('date', 'DESC');
        $this->db->limit(1);
        $last_binary_bonus = $this->db->get('history')->row_array();

        // 4. Token info
        $token_info = token_info(); // should return ['currency_symbol' => '', 'decimal' => 2]
        $tokensymbol = isset($token_info->currency_symbol) ? $token_info->currency_symbol : '';
        $decimalPlaces = isset($token_info->decimal) ? $token_info->decimal : 2;

        $total_binary_bonus = isset($total_binary_bonus_row->binary_bonus) && $total_binary_bonus_row->binary_bonus
        ? number_format((float)$total_binary_bonus_row->binary_bonus, $decimalPlaces) . " " . $tokensymbol
        : "0.00 " . $tokensymbol;

        // 5. Last binary ROI details
        $leftleg_amount_last = 0;
        $rightleg_amount_last = 0;
        $overall_rankeligible_last = 0;

        if (!empty($last_binary_bonus['total_left_roi']) && !empty($last_binary_bonus['total_right_roi'])) {
        $leftleg_amount_last = (float)$last_binary_bonus['total_left_roi'];
        $rightleg_amount_last = (float)$last_binary_bonus['total_right_roi'];
        $overall_rankeligible_last = min($leftleg_amount_last, $rightleg_amount_last);
        }

        $total_binary_bonus_last = $overall_rankeligible_last
        ? number_format($overall_rankeligible_last, $decimalPlaces) . " " . $tokensymbol
        : "0.00 " . $tokensymbol;

        // 6. Build history records
        $historyRecords = [];

        foreach ($binary_history as $row) {
        $leftleg_amount = (float)$row['total_left_roi'];
        $rightleg_amount = (float)$row['total_right_roi'];
        $overall_rankeligible = min($leftleg_amount, $rightleg_amount);
        $displayTime = display_time($row['date']);
        $historyRecords[] = [
        'rankname' => 'Collab Match Incentive',
        'amount' => number_format((float)$row['token_amount'], 2) . " " . $tokensymbol,
        'date' => $row['date'],
        'displayTime' => $displayTime,
        'overall_rankeligible' => number_format($overall_rankeligible, 2) . " " . $tokensymbol,
        'leftleg_amount' => number_format($leftleg_amount, 2) . " " . $tokensymbol,
        'riightleg_amount' => number_format($rightleg_amount, 2) . " " . $tokensymbol,
        'created_at' => $row['date'],
        ];
        }

        $this->data['Totalcollabincentives'] = $total_binary_bonus;
        $this->data['Currentpoolmatch'] = $total_binary_bonus_last;
        $this->data['history'] = $history;

        $this->data['title'] = "View Referral information";
        $this->data['card_title'] = "Referral information";

        $this->load->view('user/wallet/view_binary_management',$this->data);

    }


    // public function lendingMywalletHistory(){

    //     $user_id = $this->session->userdata('userid');

    //     $this->data['title'] = "View My wallet";
    //     $this->data['card_title'] = "Wallet information";

	// 	 $user_usd_balance = site_wallet_balance($user_id);
	// 	 $user_token_balance = site_token_balance($user_id);

    //     $this->data['user_usd_balance'] = $user_usd_balance;
    //     $this->data['user_token_balance'] = $user_token_balance;

    //     $this->load->view('user/wallet/view_mywallet_management',$this->data);
    // }

    public function lendingMywalletHistory()
    {
        $user_id = (int) $this->session->userdata('userid');
        if (!$user_id) {
            redirect('user/login');
            return;
        }

        $this->load->model('Wallet_model', 'wallet');

        $this->data['title'] = "View My wallet";
        $this->data['card_title'] = "Wallet information";

        // Filters from UI
        $filters = [
            'q'      => trim((string) $this->input->get('q')),
            'type'   => strtolower(trim((string) $this->input->get('type'))),   // CREDIT/DEBIT/WITHDRAW/TRANSFER/COMMISSION/ORDER
            'status' => trim((string) $this->input->get('status')),             // SUCCESS/PENDING/FAILED
            'from'   => trim((string) $this->input->get('from')),
            'to'     => trim((string) $this->input->get('to')),
        ];

        $page     = max(1, (int) $this->input->get('page'));
        $per_page = 5;

        // ✅ balances
        $this->data['main_balance']       = (float) site_wallet_balance($user_id); // user_wallets.usd_balance
        $this->data['commission_balance'] = (float) $this->wallet->getCommissionBalance($user_id); // history type='commission'
        $this->data['bonus_balance']      = (float) $this->wallet->getTotalEarnedBonusBalance($user_id);      // history type='bonus'

        // ✅ withdraw metrics (from withdraw table)
        $this->data['pending_withdraw'] = (float) $this->wallet->getPendingWithdraw($user_id);
        $this->data['total_withdrawn']  = (float) $this->wallet->getTotalWithdrawn($user_id);

        // ✅ total earned (bonus + commission credits)
        $this->data['total_earned'] = (float) $this->wallet->getTotalEarned($user_id);

        // ✅ history list + counts + paging
        $list = $this->wallet->getWalletHistory($user_id, $filters, $page, $per_page);

        $this->data['transactions'] = $list['rows'];
        $this->data['counts']       = $list['counts'];
        $this->data['paging']       = $list['paging'];
        
        $this->load->view('user/wallet/view_mywallet_management', $this->data);
    }


    public function myreferralHistory()
    {
        $user_id = (int)$this->session->userdata('userid');

        $this->data['title'] = "My Referral";
        $this->data['card_title'] = "Referral Information";

        $this->data['user_usd_balance']   = site_wallet_balance($user_id);
        $this->data['user_token_balance'] = site_token_balance($user_id);

        $this->load->model('member/BinaryModel');
        $binary_info = $this->BinaryModel->calculateLegInvestments($user_id);

        $left_leg_count  = !empty($binary_info['left_leg_users'])  ? count($binary_info['left_leg_users'])  : 0;
        $right_leg_count = !empty($binary_info['right_leg_users']) ? count($binary_info['right_leg_users']) : 0;

        $left_leg_investment_token  = (float)($binary_info['left_investment_token'] ?? 0);
        $right_leg_investment_token = (float)($binary_info['right_investment_token'] ?? 0);

        // Safe query
        $userRow = $this->db->select('referral_id')
                            ->from('users')
                            ->where('id', $user_id)
                            ->get()
                            ->row();

        $referral = $userRow->referral_id ?? '';

        // ✅ Correct link format (matches screenshot)
        $this->data['right_link'] = base_url('user/re?re=R-' . $referral);
        $this->data['left_link']  = base_url('user/re?re=L-' . $referral);

        // ✅ Pass variables that your view is using
        $this->data['left_users']  = $left_leg_count;
        $this->data['right_users'] = $right_leg_count;

        $this->data['left_invest']  = $left_leg_investment_token;
        $this->data['right_invest'] = $right_leg_investment_token;

        // (Optional) keep old names if other places use it
        $this->data['left_leg_count'] = $left_leg_count;
        $this->data['right_leg_count'] = $right_leg_count;
        $this->data['left_leg_investment_token'] = $left_leg_investment_token;
        $this->data['right_leg_investment_token'] = $right_leg_investment_token;

        $this->load->view('user/wallet/myreferral', $this->data);
    }



      public function mydexHistory(){

        $user_id = $this->session->userdata('userid');
        $this->data['title'] = "My Dex wallet";
        $this->data['card_title'] = "Dex wallet Information";
        $user_usd_balance = site_wallet_balance($user_id);
        $user_token_balance = site_token_balance($user_id);
        $this->data['user_usd_balance'] = $user_usd_balance;
        $this->data['user_token_balance'] = $user_token_balance;
        
        $this->load->model('member/BinaryModel');
        $binary_info = $this->BinaryModel->calculateLegInvestments($user_id);
    
        $left_leg_count = count($binary_info['left_leg_users']);  
        $right_leg_count = count($binary_info['right_leg_users']); 

        $left_leg_investment_token = $binary_info['left_investment_token']; 
        $right_leg_investment_token = $binary_info['right_investment_token'];

        $userinfo = $this->db->query("SELECT * FROM users where id = '".$user_id."' ")->row()->referral_id;

        $this->data['right_link'] = base_url().'user/re?R-'.$userinfo;
        $this->data['left_link'] = base_url().'user/re?L-'.$userinfo;

        $this->data['left_leg_count'] = $left_leg_count;
        $this->data['right_leg_count'] = $right_leg_count;

        $this->data['left_leg_investment_token'] = $left_leg_investment_token;
        $this->data['right_leg_investment_token'] = $right_leg_investment_token;

        $this->data['cryptos'] = $this->coin_list();

        $wallet_info = $this->db->query("SELECT * FROM `user_wallet` where user_id = '".$user_id."' ")->row();

        $this->data['wallet_address'] = $wallet_info->wallet_address;
        $this->data['cryptos_img'] = $wallet_info->wallet_qrimage;

        $this->load->view('user/wallet/mydexwallet',$this->data);

    }

    public function myllendinglist(){

        $user_id = $this->session->userdata('userid');
        $this->data['title'] = "My Lending Information";
        $this->data['card_title'] = "Lending Information";
         $this->data['user_id'] = $user_id;

        $this->load->view('user/wallet/lendinghistory',$this->data);

    }


     /*
    |--------------------------------------------------------------------------
    | Deposit Verify List Admin
    |--------------------------------------------------------------------------
    */
    public function investment_list_get(){

        $this->load->model('wallet/Investment_model');
        $draw = $this->input->get('draw');
        $start = $this->input->get('start');
        $length = $this->input->get('length');
        
        $type = $this->input->get('call_status');
        $clients = $this->input->get('client_filter');
        $from_date = $this->input->get('from_date') ? date('Y-m-d', strtotime($this->input->get('from_date'))) : '';
        $to_date = $this->input->get('to_date') ? date('Y-m-d', strtotime($this->input->get('to_date'))) : '';
     

        $data = array();
        $total_records = $this->Investment_model->get_count_invest($from_date,$to_date,$clients,$type);
        $users = $this->Investment_model->get_info_invest($length, $start,$from_date,$to_date,$clients,$type);

      
        $i = 0;
        foreach ($users as $user) {
        $i++;

        $currency_status = $user['status'];
        $reinvest_status = $user['reinvest_status'] ? "checked" : "";
        $change_status_url = base_url()."package-reinvest-status/".$user['id'];
        $userinfo = $this->db->query("SELECT * FROM users where id = '".$user['user_id']."' ")->row();
        $package_info = $this->db->query("SELECT * FROM `package_config` where id = '".$user['package_id']."' ")->row();

      
        $verify_link = "https://bscscan.com/tx/".$user['hash_id'];

    
        $today = date("Y-m-d H:i:s");
        $start = new DateTime($user['starting_date']);
        $mature = new DateTime($user['mature_date']);
        $now = new DateTime($today);

        $remaining_days = $now->diff($mature)->days;

        if ($now > $mature) {
        $remaining_days = 0; 
        }

        if($currency_status == "1"){
            $package_status = '';
            $package_color = 'text-gray-800';
            $delete_disabled = '';
        } else {
            $package_status = 'disabled';
            $package_color = 'text-danger';
            $delete_disabled = 'disabled';
        }

        $package_apporved = $user['approve_status'] ? '1' : '0';

        if($package_apporved){
            $package_approve_button = '';
        } else{
       $package_approve_button = '<a class="btn btn-success btn-active-light-success btn-sm btn-approve text-center me-4"  data-approve-url="'.base_url().'approve-investment/'.$user['id'].'">
		 <i class="fa fa-check" aria-hidden="true"></i> Approve
		</a>';
        }


        $data[] = array(
        'RecordID' => $i,
        'UserInfo' => '<div class="d-flex align-items-center">
        <div class="symbol symbol-50px me-3">                                                   
        </div>
        <div class="d-flex justify-content-start flex-column">
        <a href="#" class="'.$package_color.' fw-bold text-hover-primary mb-1 fs-6">'.$userinfo->email.'</a>
        <span class="text-gray-500 fw-semibold d-block fs-7">'.$userinfo->referral_id.'</span>
        </div>
        </div>',
        'InvestInfo' => '<div class="d-flex align-items-center">
        <div class="symbol symbol-50px me-3"></div>
        <div class="d-flex justify-content-start flex-column">
        <a href="#" class="'.$package_color.' fw-bold text-hover-primary mb-1 fs-6">'
        . htmlspecialchars($package_info->package_name, ENT_QUOTES, 'UTF-8') . ' - Package</a>
        <span class="me-2 text-gray-600 fw-bold d-block fs-6">'
        . currency_format($user['invest_amount']) . ' - ' . $package_info->days_duration . ' days
        </span> 
        </div>
        </div>',
        'DateInfo' => '<div class="d-flex align-items-center">
        <div class="symbol symbol-50px me-3">                                                   
        </div>
        <div class="d-flex justify-content-start flex-column">
        <span class="'.$package_color.' fw-bold text-hover-primary mb-1 fs-6">'.$user['days_count'].' -  days Remaining</span>
        <a href="#" class="fs-7 text-muted fw-bold mb-1 fs-6">'.$user['starting_date'].' - '.$user['mature_date'].'</a>
        </div>
        </div>',
        'EndDate' => '<div class="form-check form-switch form-check-custom form-check-success form-check-solid">
        <input class="form-check-input h-30px w-50px template_status" type="checkbox" '.$package_status.' value="1" name="template_status"'.
        $reinvest_status.'
        id="template_status" 
        data-payment="'.$user['id'].'" 
        data-template_status-url="'.$change_status_url.'"/>
        <label class="form-check-label" for="template_status">
        </label>
        </div>',
        'temp_content' => '<div class="d-flex justify-content-center flex-row">
        <a class="btn btn-info btn-active-light-info btn-sm  text-center me-4" href="'.base_url().'user/info/'.$user['id'].'">
         <i class="fa fa-eye" aria-hidden="true"></i>  
        </a>
        '.$package_approve_button.'
        <a class="btn btn-danger btn-active-light-danger btn-sm text-center btn-delete '.$delete_disabled.'" href="javascript:void(0);" data-reject-url="'.base_url().'delete-investment/'.$user['id'].'" '.$delete_disabled.'>
         <i class="fa fa-trash" aria-hidden="true"></i>  
        </a>
        </div>
		',
        );
        }
                               
        $response = array(
        'draw' => intval($draw),
        'recordsTotal' => $total_records,
        'recordsFiltered' => $total_records,
        'data' => $data
        );

        echo json_encode($response);
    }


    public function investment_info($id){

            $user_id = $this->session->userdata('userid');
            $this->data['title'] = "My Lending Earnings Information";
            $this->data['card_title'] = "Lending Earnings Information";
            $this->data['user_id'] = $user_id;

            $this->data['currency_info'] = currency_info();
            $this->data['token_info'] = token_info();
 
            $today = date("Y-m-d H:i:s");
            $wallet_info = $this->db->query("SELECT * FROM user_investment where id = '".$id."' ")->row();
            $user_info = $this->db->query("SELECT * FROM users where id = '".$wallet_info->user_id."' ")->row();
            $start = new DateTime($wallet_info->starting_date);
            $mature = new DateTime($wallet_info->mature_date);
            $now = new DateTime($today);
            $remaining_days = $now->diff($mature)->days;


            $check_hash = $this->db->query("SELECT * FROM history where invest_id = '".$id."' and type='mining' ")->row();

            // if($check_hash->hash_id == "admin-made"){
            //     $verify_link = "----";
            //     $payment_by ="Admin Made";
            // } else {
            //     $verify_link = '<a href=https://bscscan.com/tx/'.$check_hash->hash_id.">View BSC Scan </a>";
            //     $payment_by ="BSC SCAN";
            // }
                
            $payment_by = ucfirst($wallet_info->invest_network);

            $package_info = $this->db->query("SELECT * FROM `package_config` where id = '".$wallet_info->package_id."' ")->row();
            
            if($package_info->maximum > 0){
                $maximum = currency_format($package_info->maximum);
            } else {
                $maximum = "Unlimited";
            }

            $dateinfo = '<label class="w-150px mb-1">'.$wallet_info->days_count.'  -  days Remaining </label>
                <div class="fw-normal text-gray-600"> Created Date : '.$wallet_info->starting_date.'</div>
                <div class="fw-normal text-gray-600"> Mature Date : '.$wallet_info->mature_date.'</div>';

            $total_earnings_cu_get =  $this->db->query("SELECT SUM(amount) as currency_amount FROM `history` where type = 'profit' and invest_id = '".$id."' and user_id = '".$wallet_info->user_id."' ")->row()->currency_amount;
            $total_earnings_token =  $this->db->query("SELECT SUM(token_amount) as token_amount FROM `history` where type = 'profit' and invest_id = '".$id."' and user_id = '".$wallet_info->user_id."'  ")->row()->token_amount;
            

            if($wallet_info->status == "1"){
                $invest_status ='<span class="text-success fw-semibold"> <span class="p-5"> Active </span></span>';
            } else {
                $invest_status ='<span class="text-danger fw-semibold"> <span class="p-5"> Matured </span></span>';
            }
                
            $this->data['dateinfo'] = $dateinfo;

            $this->data['username'] = $user_info->username;
            $this->data['useremail'] = $user_info->email;
            $this->data['userreferralid'] = $user_info->referral_id;
            $this->data['min_max_package'] = "Minimum : ".currency_format($package_info->minimum)." -  Maxmum : ".$maximum; 
            $this->data['packagename'] = $package_info->package_name ? $package_info->package_name : "Package Removed";
            $this->data['packageamount'] = currency_format($wallet_info->invest_amount);
            $this->data['packagetokenamount'] = token_format($wallet_info->csq_deposit);
            $this->data['pacakgeduration'] = $wallet_info->days_count." Days";
            $this->data['paymenturl'] = $verify_link;
            $this->data['remaining_days'] = $remaining_days;
            $this->data['payment_by'] = $payment_by;
            $this->data['invest_status'] = $invest_status;

            $this->data['total_earnings_currency'] = currency_format($total_earnings_cu_get);
            $this->data['total_earnings_token'] = token_format($total_earnings_token);

            $this->data['invest_id'] = $id;
            
        $this->load->view('user/wallet/singlelendinghistory',$this->data);


    }

    public function coin_list() {

		$url = 'https://api.coingecko.com/api/v3/coins/markets';
		$queryParams = http_build_query([
			'vs_currency' => 'usd',
			'order' => 'market_cap_desc',
			'per_page' => 10,
			'page' => 1,
			'sparkline' => 'false'
		]);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url . '?' . $queryParams);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);

		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Accept: application/json',
			'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0 Safari/537.36'
		]);
		$response = curl_exec($ch);
		if (curl_errno($ch)) {
			log_message('error', 'CoinGecko API Request Error: ' . curl_error($ch));
			curl_close($ch);
			return [];
		}
		$httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		if ($httpStatus !== 200) {
			log_message('error', 'CoinGecko API HTTP Status: ' . $httpStatus);
			return [];
		}
		$cryptos = json_decode($response, true);
		return $cryptos ?? [];
	}


    public function lendingProfitHistory()
    {
        $userId = $this->session->userdata('userid');

        try {

            $lending_count = $this->db->select('COUNT(*) as total_lending')
                ->from('user_investment')
                ->where(['user_id' => $userId, 'status' => '1'])
                ->get()->row_array();

            $total_lending = $this->db->select('SUM(csq_deposit) as token_amount')
                ->from('user_investment')
                ->where(['user_id' => $userId, 'status' => '1'])
                ->get()->row_array();

            $token_info = token_info(); 
            $decimalPlaces = isset($token_info->decimal) ? $token_info->decimal : 2;
            $tokensymbol = isset($token_info->currency_symbol) ? $token_info->currency_symbol : '';

            $totLendingValues = $total_lending['token_amount']
                ? number_format($total_lending['token_amount'], $decimalPlaces) . ' ' . $tokensymbol
                : '0.00 ' . $tokensymbol;

            $totLendingCount =$lending_count['total_lending'];

            $modifiedHistory = [];

            $today = date('Y-m-d');
            $total_bonus = $this->db->query("SELECT SUM(amount) as earn_amt FROM history where type='profit' and user_id = '".$userId."' ")->row()->earn_amt;
            $today_bonus = $this->db->query("SELECT SUM(amount) as earn_amt FROM history where type='profit' and user_id = '".$userId."' and Date(date) = '".$today."' ")->row()->earn_amt;

            $this->data['Totalllendigpool'] = $totLendingValues;
            $this->data['lendingpools']  = $totLendingCount;

            $this->data['total_bonus'] = token_format($total_bonus);
            $this->data['today_bonus']  = token_format($today_bonus);
            
            $this->data['user_id'] = $userId;
            $this->data['title'] = "View Lending";
            $this->data['card_title'] = "View Lending";

            $this->load->view('user/wallet/view_lending_management',$this->data);


        } catch (Exception $e) {

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => false,
                    'message' => 'Error fetching lending profit history data',
                    'error' => $e->getMessage()
                ]));
        }
    }



    public function getTotalInvestmentAmount($user_ids = []) {

        if (!is_array($user_ids) || empty($user_ids)) {
            return 0;
        }

        $this->db->select_sum('invest_amount', 'total_investment');
        $this->db->from('user_investment');
        $this->db->where_in('user_id', $user_ids);
        $this->db->where('status', '1');
        $query = $this->db->get();

        $result = $query->row();
        return $result && $result->total_investment ? $result->total_investment : 0;
        
    }


    public function getInvestmentStats($userId)
    {
        $leftLegUsers = $this->getLegUsers($userId, 'left');
        $rightLegUsers = $this->getLegUsers($userId, 'right');

        $leftLegInvestment = $this->getTotalInvestmentAmount($leftLegUsers);
        $rightLegInvestment = $this->getTotalInvestmentAmount($rightLegUsers);

        return [
                'leftLegInvestment' => $leftLegInvestment,
                'RightlegInvestment' => $rightLegInvestment
        ];
    }


    public function getTeamStats($userId)
    {

        $leftLegUsers = $this->getLegUsers($userId, 'left');
        $rightLegUsers = $this->getLegUsers($userId, 'right');

        $leftLegStatus = $this->getActiveInactiveUsers($leftLegUsers);
        $rightLegStatus = $this->getActiveInactiveUsers($rightLegUsers);

    
        $totalUsers = count($leftLegUsers) + count($rightLegUsers);
        $totalActiveUsers = count($leftLegStatus['activeUsers']) + count($rightLegStatus['activeUsers']);

        $totalInactiveUsers = count($leftLegStatus['inactiveUsers']) + count($rightLegStatus['inactiveUsers']);

        return [
                'total_users' => $totalUsers,
                'total_right_leg_users' => count($rightLegUsers),
                'total_left_leg_users' => count($leftLegUsers),
                'total_active_users' => $totalActiveUsers,
                'total_inactive_users' => $totalInactiveUsers
        ];
    }

    public function getLegUsers($parentId, $position)
    {
        $users = [];

        $this->db->select('user_id');
        $this->db->from('binary_placement');
        $this->db->where('parent_id', $parentId);
        $this->db->where('position', $position);
        $query = $this->db->get();

        if ($query->num_rows() > 0) {
            $row = $query->row();
            $userId = $row->user_id;
            $users[] = $userId;

            $leftUsers = $this->getLegUsers($userId, 'left');
            $rightUsers = $this->getLegUsers($userId, 'right');

            $users = array_merge($users, $leftUsers, $rightUsers);
        }

        return $users;
    }

    public function getActiveInactiveUsers($userIds = [])
    {
        if (!is_array($userIds) || empty($userIds)) {
            return [
                'activeUsers' => [],
                'inactiveUsers' => $userIds ?: []
            ];
        }

        $uniqueUserIds = array_unique($userIds);

        $this->db->distinct();
        $this->db->select('user_id');
        $this->db->from('user_investment');
        $this->db->where_in('user_id', $uniqueUserIds);
        $this->db->where('status', '1');
        $query = $this->db->get();

        $activeUserIds = [];
        if ($query->num_rows() > 0) {
            foreach ($query->result() as $row) {
                $activeUserIds[] = $row->user_id;
            }
        }

        $inactiveUserIds = array_diff($uniqueUserIds, $activeUserIds);

        return [
            'activeUsers' => $activeUserIds,
            'inactiveUsers' => array_values($inactiveUserIds) 
        ];
    }

}