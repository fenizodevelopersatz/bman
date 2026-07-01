<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User extends CI_Controller
{

	public function __construct()
	{
		parent::__construct();

		if ($this->session->userdata('user_logged_in') && $this->session->userdata('user_login')) {
			$this->lang->load('common', $this->session->userdata('language'));
		} else {
			redirect('user/in');
		}

		$this->load->model('member/BinaryModel');

		$language = $this->session->userdata('site_lang') ?? 'english';
		$this->config->set_item('language', $language);
		$this->lang->load('common', $language);
	}


	public function index()
	{

		if ($this->session->userdata('user_logged_in')) {

			$userid = $this->session->userdata('user_userid');
			$userinfo = $this->db->query("SELECT * FROM users where id = '" . $userid . "' ")->row();

			$username = $userinfo->username;

			$this->data['title'] = "Dashboard";
			$this->data['card_tilte'] = "Dashboard";
			$this->data['currency_info'] = currency_info();
			$this->data['token_info'] = token_info();
			$this->data['currency_info'] = currency_info();
			$this->data['token_info'] = token_info();


			$this->data['cryptos'] = $this->coin_list();

			$this->data['username'] = $username;
			$this->data['user_id'] = $userid;
			$this->data['last_login_ip'] = "192.12.11";

			$this->data['notification'] = $this->db->query("SELECT * FROM `announcement` where title_status = '1' ")->result();


			$this->db->where('user_id', $userid);
			$total_orders = $this->db->count_all_results('orders');

			$this->db->select_sum('total_amount');
			$this->db->where('user_id', $userid);
			$total_spent = $this->db->get('orders')->row()->total_amount;

			$this->db->where('user_id', $userid);
			$this->db->order_by('created_at', 'desc');
			$last_order = $this->db->get('orders')->row();

			$order_data = $this->db->query("
			SELECT DATE_FORMAT(created_at, '%b') AS month, COUNT(*) AS total
			FROM orders
			WHERE user_id = $userid
			GROUP BY month
			ORDER BY STR_TO_DATE(month, '%b')
			")->result();

			$this->data['total_orders'] = $total_orders;
			$this->data['total_spent'] = $total_spent;
			$this->data['last_order'] = $last_order;
			$this->data['chart_data'] = $order_data;
			$this->data['userinfo'] = $userinfo;
			$this->load->view('user/dashboard/index', $this->data);

		} else {
			redirect('user/in');
		}

	}

	public function logout()
	{
		// Clear only the user session keys so a logged-in admin in another tab stays logged in.
		$this->session->unset_userdata(array(
			'user_logged_in', 'user_userid', 'user_full_name',
			'user_email', 'user_login', 'user_logindate', 'user_ip_address'
		));
		redirect('/');
	}



	public function coin_list()
	{

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



	// public function viewuserinfo($id)
	// {
	// 	$userinfo = $this->db->query("SELECT * FROM users where id = '" . $id . "'")->row();
	// 	$sponser_info = $this->db->query("SELECT * FROM users where id = '" . $userinfo->sponser . "'")->row();

	// 	// Investment (existing)
	// 	$binary_info = $this->BinaryModel->calculateLegInvestments($id);

	// 	$left_leg_count = count($binary_info['left_leg_users'] ?? []);
	// 	$right_leg_count = count($binary_info['right_leg_users'] ?? []);

	// 	$left_leg_investment = $binary_info['left_leg_investment'] ?? 0;
	// 	$right_leg_investment = $binary_info['right_leg_investment'] ?? 0;
	// 	$my_investment = $binary_info['my_investment'] ?? 0;

	// 	$left_leg_investment_token = $binary_info['left_investment_token'] ?? 0;
	// 	$right_leg_investment_token = $binary_info['right_investment_token'] ?? 0;
	// 	$my_investment_token = $binary_info['my_investment_token'] ?? 0;

	// 	// ✅ NEW: BV (This Week)
	// 	list($ws, $we) = $this->BinaryModel->getWeekRange();
	// 	$bvWeek = $this->BinaryModel->getProductBVTotals($id, $ws, $we);

	// 	$left_bv = (float) ($bvWeek['left_bv'] ?? 0);
	// 	$right_bv = (float) ($bvWeek['right_bv'] ?? 0);

	// 	// carry forward = strong - weak (this week view)
	// 	$carry_forward = abs($left_bv - $right_bv);

	// 	// weak leg need to match strong leg
	// 	$need_to_match = ($left_bv > $right_bv) ? ($left_bv - $right_bv) : ($right_bv - $left_bv);

	// 	// strong/weak text
	// 	$left_strength = ($left_bv >= $right_bv) ? 'STRONG' : 'WEAK';
	// 	$right_strength = ($right_bv >= $left_bv) ? 'STRONG' : 'WEAK';

	// 	// Pairs today (optional)
	// 	list($ts, $te) = $this->BinaryModel->getTodayRange();
	// 	$pairs_today = $this->BinaryModel->getPairsCompleted($id, $ts, $te);

	// 	list($ws, $we) = $this->BinaryModel->getWeekRange();
	// 	$team = $this->BinaryModel->getTeamSnapshotWeekly($id, $ws, $we);

	// 	// Earnings (existing)
	// 	$binary_site_currency = $this->db->query("SELECT sum(amount) as binary_site_amt FROM history where type = 'binary_commission' and user_id = '" . $id . "' ")->row()->binary_site_amt;
	// 	$binary_token_currency = $this->db->query("SELECT sum(token_amount) as binary_token_amt FROM history where type = 'binary_commission' and user_id = '" . $id . "' ")->row()->binary_token_amt;

	// 	$roi_site_currency = $this->db->query("SELECT sum(amount) as roi_site_amt FROM history where type = 'profit' and user_id = '" . $id . "' ")->row()->roi_site_amt;
	// 	$roi_token_currency = $this->db->query("SELECT sum(token_amount) as roi_token_amt FROM history where type = 'profit' and user_id = '" . $id . "' ")->row()->roi_token_amt;

	// 	$direct_site_currency = $this->db->query("SELECT sum(amount) as direc_site_amt FROM history where type = 'direct_commission' and user_id = '" . $id . "' ")->row()->direc_site_amt;
	// 	$direct_token_currency = $this->db->query("SELECT sum(token_amount) as direc_token_amt FROM history where type = 'direct_commission' and user_id = '" . $id . "' ")->row()->direc_token_amt;

	// 	$level_site_currency = $this->db->query("SELECT sum(amount) as direc_site_amt FROM history where type = 'level_commission' and user_id = '" . $id . "' ")->row()->direc_site_amt;
	// 	$level_token_currency = $this->db->query("SELECT sum(token_amount) as direc_token_amt FROM history where type = 'level_commission' and user_id = '" . $id . "' ")->row()->direc_token_amt;

	// 	$user_usd_balance = site_wallet_balance($id);
	// 	$user_token_balance = site_token_balance($id);

	// 	// Investments table (dynamic)
	// 	// $investments = $this->getUserInvestmentsForView($id);

	// 	// Get referral_id safely
	// 	$row = $this->db->select('referral_id')
	// 		->from('users')
	// 		->where('id', $id)
	// 		->get()
	// 		->row_array();

	// 	$referral_id = $row['referral_id'] ?? '';

	// 	// Backend referral links
	// 	$left_link = $referral_id ? base_url('user/re?ref=L-' . $referral_id) : '';
	// 	$right_link = $referral_id ? base_url('user/re?ref=R-' . $referral_id) : '';

	// 	$userinfo = array(
	// 		"name" => $userinfo->username,
	// 		"email" => $userinfo->email,
	// 		"register_date" => $userinfo->register_date,
	// 		"referral_id" => $userinfo->referral_id,
	// 		"sponser" => $sponser_info->email . " ( " . $sponser_info->referral_id . " )",

	// 		"user_usd_balance" => $user_usd_balance,
	// 		"user_token_balance" => $user_token_balance,

	// 		"my_investment" => currency_format($my_investment),
	// 		"left_leg_count" => $left_leg_count > 0 ? $left_leg_count . ' Members' : "0 Member",
	// 		"right_leg_count" => $right_leg_count > 0 ? $right_leg_count . ' Members' : "0 Member",
	// 		"left_leg_investment" => currency_format($left_leg_investment),
	// 		"right_leg_investment" => currency_format($right_leg_investment),
	// 		'left_leg_investment_token' => token_format($left_leg_investment_token),
	// 		'right_leg_investment_token' => token_format($right_leg_investment_token),
	// 		'my_investment_token' => token_format($my_investment_token),

	// 		// ✅ NEW: BV summary (This Week)
	// 		"left_leg_bv" => round($left_bv, 2),
	// 		"right_leg_bv" => round($right_bv, 2),
	// 		"left_leg_strength" => $left_strength,
	// 		"right_leg_strength" => $right_strength,
	// 		"carry_forward_bv" => round($carry_forward, 2),
	// 		"need_bv" => round($need_to_match, 2),
	// 		"pairs_today" => (int) $pairs_today,

	// 		"team_snapshot" => [
	// 			"left_team" => $team['left_team'],
	// 			"right_team" => $team['right_team'],
	// 			"active_members" => $team['active_total'],     // OR use $team['active_week'] if you want weekly active only
	// 			"new_joins" => $team['new_joins_week'],
	// 		],

	// 		"weekly_progress" => 1 * 25,

	// 		'binary_site_currency' => $binary_site_currency,
	// 		'binary_token_currency' => $binary_token_currency,
	// 		'roi_token_currency' => $roi_token_currency,
	// 		'direct_site_currency' => currency_format($direct_site_currency),
	// 		'direct_token_currency' => token_format($direct_token_currency),
	// 		'level_site_currency' => currency_format($level_site_currency),
	// 		'level_token_currency' => token_format($level_token_currency),
	// 	);

	// 	echo json_encode(['result' => true, 'data' => $userinfo]);
	// }



	// public function viewuserinfo($id)
	// {
	// 	$id = (int) $id;

	// 	$user = $this->db->get_where('users', ['id' => $id])->row();
	// 	if (!$user) {
	// 		echo json_encode(['result' => false, 'message' => 'User not found']);
	// 		return;
	// 	}

	// 	$sponser_info = $this->db->get_where('users', ['id' => $user->sponser])->row();

	// 	// ========= Existing investment summary =========
	// 	$this->load->model('member/BinaryModel');
	// 	$binary_info = $this->BinaryModel->calculateLegInvestments($id);

	// 	$left_leg_count = count($binary_info['left_leg_users'] ?? []);
	// 	$right_leg_count = count($binary_info['right_leg_users'] ?? []);

	// 	$left_leg_investment = (float) ($binary_info['left_leg_investment'] ?? 0);
	// 	$right_leg_investment = (float) ($binary_info['right_leg_investment'] ?? 0);
	// 	$my_investment = (float) ($binary_info['my_investment'] ?? 0);

	// 	$left_leg_investment_token = (float) ($binary_info['left_investment_token'] ?? 0);
	// 	$right_leg_investment_token = (float) ($binary_info['right_investment_token'] ?? 0);
	// 	$my_investment_token = (float) ($binary_info['my_investment_token'] ?? 0);

	// 	// ========= Config (Commission Settings) =========
	// 	$cfg = $this->db->get_where('commission_config', ['id' => 1])->row();

	// 	// Pair ratio (ex: 1:1)
	// 	$ratio = !empty($cfg->binary_pair_ratio) ? $cfg->binary_pair_ratio : '1:1';
	// 	$parts = explode(':', $ratio);
	// 	$rL = isset($parts[0]) ? max(1, (int) $parts[0]) : 1;
	// 	$rR = isset($parts[1]) ? max(1, (int) $parts[1]) : 1;

	// 	// Carry forward settings
	// 	$carry_status = !empty($cfg->carry_forward_status) ? (int) $cfg->carry_forward_status : 0;
	// 	$carry_mode = !empty($cfg->carry_forward_mode) ? $cfg->carry_forward_mode : 'lifetime'; // lifetime/daily/weekly/monthly
	// 	$carry_cap = isset($cfg->carry_forward_cap_bv) ? (float) $cfg->carry_forward_cap_bv : 0; // 0 means no cap

	// 	// ========= BV for "This Week" (your existing history type bv_volume + leg left/right) =========
	// 	list($ws, $we) = $this->BinaryModel->getWeekRange();
	// 	$bvWeek = $this->BinaryModel->getProductBVTotals($id, $ws, $we);

	// 	$left_bv = (float) ($bvWeek['left_bv'] ?? 0);
	// 	$right_bv = (float) ($bvWeek['right_bv'] ?? 0);

	// 	// ========= Load Carry Forward row =========
	// 	$left_carry = 0.0;
	// 	$right_carry = 0.0;

	// 	if ($carry_status === 1) {
	// 		$carryRow = $this->BinaryModel->getOrCreateCarryRow($id);

	// 		// "scope_key" is used to flush carry when mode changes period
	// 		$currentScopeKey = $this->BinaryModel->getCarryScopeKey($carry_mode);

	// 		// If scope_key changed -> reset carry for new period (daily/weekly/monthly)
	// 		if ($carry_mode !== 'lifetime' && ($carryRow['scope_key'] ?? '') !== $currentScopeKey) {
	// 			$this->BinaryModel->updateCarryRow($id, 0, 0, $currentScopeKey);
	// 			$left_carry = 0;
	// 			$right_carry = 0;
	// 		} else {
	// 			$left_carry = (float) ($carryRow['left_carry'] ?? 0);
	// 			$right_carry = (float) ($carryRow['right_carry'] ?? 0);
	// 		}

	// 		// Apply CAP if provided (cap is per leg carry)
	// 		if ($carry_cap > 0) {
	// 			$left_carry = min($left_carry, $carry_cap);
	// 			$right_carry = min($right_carry, $carry_cap);
	// 		}
	// 	}

	// 	// ========= Totals used for pairing strength =========
	// 	$left_total = $left_bv + $left_carry;
	// 	$right_total = $right_bv + $right_carry;

	// 	// STRONG / WEAK badge based on totals
	// 	if ($left_total == $right_total) {
	// 		$left_strength = 'STRONG';
	// 		$right_strength = 'STRONG';
	// 	} else {
	// 		$left_strength = ($left_total > $right_total) ? 'STRONG' : 'WEAK';
	// 		$right_strength = ($right_total > $left_total) ? 'STRONG' : 'WEAK';
	// 	}

	// 	// ========= Pairs possible (This Week view) based on ratio =========
	// 	$pairs_possible = 0;
	// 	if ($left_total > 0 && $right_total > 0) {
	// 		$pairs_possible = (int) floor(min($left_total / $rL, $right_total / $rR));
	// 	}

	// 	// Remaining after pairing (this is the "carry forward" meaning)
	// 	$rem_left = max(0, $left_total - ($pairs_possible * $rL));
	// 	$rem_right = max(0, $right_total - ($pairs_possible * $rR));

	// 	// If carry disabled, show 0
	// 	$display_left_carry = ($carry_status === 1) ? $rem_left : 0;
	// 	$display_right_carry = ($carry_status === 1) ? $rem_right : 0;

	// 	// Apply cap to displayed carry too
	// 	if ($carry_status === 1 && $carry_cap > 0) {
	// 		$display_left_carry = min($display_left_carry, $carry_cap);
	// 		$display_right_carry = min($display_right_carry, $carry_cap);
	// 	}

	// 	// ========= Next Pair Target =========
	// 	// Required BV to reach NEXT pair (pairs_possible + 1)
	// 	$nextPairs = $pairs_possible + 1;

	// 	$need_left = max(0, ($nextPairs * $rL) - $left_total);
	// 	$need_right = max(0, ($nextPairs * $rR) - $right_total);

	// 	// Determine which leg is weak for message
	// 	$next_target_leg = '';
	// 	$next_target_bv = 0.0;

	// 	if ($need_left > 0 && $need_right <= 0) {
	// 		$next_target_leg = 'left';
	// 		$next_target_bv = $need_left;
	// 	} elseif ($need_right > 0 && $need_left <= 0) {
	// 		$next_target_leg = 'right';
	// 		$next_target_bv = $need_right;
	// 	} else {
	// 		// both missing (rare when both are low)
	// 		// show bigger need to make at least one full pair
	// 		if ($need_left >= $need_right) {
	// 			$next_target_leg = 'left';
	// 			$next_target_bv = $need_left;
	// 		} else {
	// 			$next_target_leg = 'right';
	// 			$next_target_bv = $need_right;
	// 		}
	// 	}

	// 	// ========= Pairs Today (actual credited pairs from history) =========
	// 	list($ts, $te) = $this->BinaryModel->getTodayRange();
	// 	$pairs_today = $this->BinaryModel->getPairsCompleted($id, $ts, $te);

	// 	// ========= Team snapshot =========
	// 	list($ws2, $we2) = $this->BinaryModel->getWeekRange();
	// 	$team = $this->BinaryModel->getTeamSnapshotWeekly($id, $ws2, $we2);

	// 	// ========= Earnings (existing) =========
	// 	$binary_site_currency = (float) ($this->db->query("SELECT IFNULL(SUM(amount),0) AS s FROM history WHERE type='binary_commission' AND user_id='{$id}'")->row()->s ?? 0);
	// 	$binary_token_currency = (float) ($this->db->query("SELECT IFNULL(SUM(token_amount),0) AS s FROM history WHERE type='binary_commission' AND user_id='{$id}'")->row()->s ?? 0);

	// 	$roi_site_currency = (float) ($this->db->query("SELECT IFNULL(SUM(amount),0) AS s FROM history WHERE type='profit' AND user_id='{$id}'")->row()->s ?? 0);
	// 	$roi_token_currency = (float) ($this->db->query("SELECT IFNULL(SUM(token_amount),0) AS s FROM history WHERE type='profit' AND user_id='{$id}'")->row()->s ?? 0);

	// 	$direct_site_currency = (float) ($this->db->query("SELECT IFNULL(SUM(amount),0) AS s FROM history WHERE type='direct_commission' AND user_id='{$id}'")->row()->s ?? 0);
	// 	$direct_token_currency = (float) ($this->db->query("SELECT IFNULL(SUM(token_amount),0) AS s FROM history WHERE type='direct_commission' AND user_id='{$id}'")->row()->s ?? 0);

	// 	$level_site_currency = (float) ($this->db->query("SELECT IFNULL(SUM(amount),0) AS s FROM history WHERE type='level_commission' AND user_id='{$id}'")->row()->s ?? 0);
	// 	$level_token_currency = (float) ($this->db->query("SELECT IFNULL(SUM(token_amount),0) AS s FROM history WHERE type='level_commission' AND user_id='{$id}'")->row()->s ?? 0);

	// 	$user_usd_balance = site_wallet_balance($id);
	// 	$user_token_balance = site_token_balance($id);

	// 	// Referral links
	// 	$referral_id = (string) ($user->referral_id ?? '');
	// 	$left_link = $referral_id ? base_url('user/re?ref=L-' . $referral_id) : '';
	// 	$right_link = $referral_id ? base_url('user/re?ref=R-' . $referral_id) : '';


	// 	// ========= BANK VERIFICATION (NEW) =========
	// 	$bank = $this->db->order_by('id', 'DESC')->get_where('user_bank', ['user_id' => $id])->row_array();

	// 	// weekly_progress
	// 	$weekly_progress_calc = 0;

	// 	$weekly_progress_calc += ($user && $user->kyc_status == 'approved') ? 1 : 0;

	// 	// Bank approved? (pending/approved)
	// 	$weekly_progress_calc += (strtolower($bank['status']) === 'approved') ? 1 : 0;

	// 	// ========= Response =========
	// 	$data = [
	// 		"name" => $user->username,
	// 		"email" => $user->email,
	// 		"register_date" => $user->register_date,
	// 		"referral_id" => $referral_id,
	// 		"sponser" => $sponser_info ? ($sponser_info->email . " ( " . $sponser_info->referral_id . " )") : "",

	// 		"user_usd_balance" => $user_usd_balance ? currency_format($user_usd_balance) : 0,
	// 		"user_token_balance" => $user_token_balance,

	// 		"my_investment" => currency_format($my_investment),
	// 		"left_leg_count" => $left_leg_count > 0 ? $left_leg_count . ' Members' : "0 Member",
	// 		"right_leg_count" => $right_leg_count > 0 ? $right_leg_count . ' Members' : "0 Member",
	// 		"left_leg_investment" => currency_format($left_leg_investment),
	// 		"right_leg_investment" => currency_format($right_leg_investment),
	// 		'left_leg_investment_token' => token_format($left_leg_investment_token),
	// 		'right_leg_investment_token' => token_format($right_leg_investment_token),
	// 		'my_investment_token' => token_format($my_investment_token),

	// 		// BV (This Week)
	// 		"left_leg_bv" => round($left_bv, 2),
	// 		"right_leg_bv" => round($right_bv, 2),

	// 		// Badge
	// 		"left_leg_strength" => $left_strength,
	// 		"right_leg_strength" => $right_strength,

	// 		// Carry forward (remaining after pairing, ratio-aware)
	// 		"carry_forward_status" => $carry_status,
	// 		"carry_forward_mode" => $carry_mode,
	// 		"carry_forward_cap_bv" => $carry_cap,
	// 		"left_carry_forward_bv" => round($display_left_carry, 2),
	// 		"right_carry_forward_bv" => round($display_right_carry, 2),

	// 		// Pair info
	// 		"pair_ratio" => "{$rL}:{$rR}",
	// 		"pairs_possible_week" => (int) $pairs_possible, // computed
	// 		"pairs_today" => (int) $pairs_today,           // from history (credited)

	// 		// Next target pair
	// 		"next_pair_target_bv" => round($next_target_bv, 2),
	// 		"next_pair_target_leg" => $next_target_leg, // left/right
	// 		"left_invite_link" => $left_link,
	// 		"right_invite_link" => $right_link,

	// 		"team_snapshot" => [
	// 			"left_team" => (int) ($team['left_team'] ?? 0),
	// 			"right_team" => (int) ($team['right_team'] ?? 0),
	// 			"active_members" => (int) ($team['active_total'] ?? 0),
	// 			"new_joins" => (int) ($team['new_joins_week'] ?? 0),
	// 		],

	// 		"weekly_progress" => $weekly_progress_calc,

	// 		"kyc_status" => ($user && $user->kyc_status == 'approved') ? 1 : 0,
	// 		"bank_verification_status" => (strtolower($bank['status']) === 'approved') ? 1 : 0,

	// 		// Earnings
	// 		'binary_site_currency' => $binary_site_currency,
	// 		'binary_token_currency' => $binary_token_currency,
	// 		'roi_site_currency' => $roi_site_currency,
	// 		'roi_token_currency' => $roi_token_currency,
	// 		'direct_site_currency' => currency_format($direct_site_currency),
	// 		'direct_token_currency' => token_format($direct_token_currency),
	// 		'level_site_currency' => currency_format($level_site_currency),
	// 		'level_token_currency' => token_format($level_token_currency),
	// 	];

	// 	echo json_encode(['result' => true, 'data' => $data]);
	// }


	// bf
	// public function viewuserinfo($id)
	// {
	// 	$id = (int) $id;

	// 	$user = $this->db->get_where('users', ['id' => $id])->row();
	// 	if (!$user) {
	// 		echo json_encode(['result' => false, 'message' => 'User not found']);
	// 		return;
	// 	}

	// 	$sponser_info = $this->db->get_where('users', ['id' => (int) $user->sponser])->row();

	// 	// ========= Models =========
	// 	$this->load->model('member/BinaryModel');

	// 	// ========= Existing investment summary =========
	// 	$binary_info = $this->BinaryModel->calculateLegInvestments($id);

	// 	$left_leg_users = $binary_info['left_leg_users'] ?? [];
	// 	$right_leg_users = $binary_info['right_leg_users'] ?? [];

	// 	$left_leg_count = count($left_leg_users);
	// 	$right_leg_count = count($right_leg_users);

	// 	$left_leg_investment = (float) ($binary_info['left_leg_investment'] ?? 0);
	// 	$right_leg_investment = (float) ($binary_info['right_leg_investment'] ?? 0);
	// 	$my_investment = (float) ($binary_info['my_investment'] ?? 0);

	// 	$left_leg_investment_token = (float) ($binary_info['left_investment_token'] ?? 0);
	// 	$right_leg_investment_token = (float) ($binary_info['right_investment_token'] ?? 0);
	// 	$my_investment_token = (float) ($binary_info['my_investment_token'] ?? 0);

	// 	// ========= Config (Commission Settings) =========
	// 	$cfg = $this->db->get_where('commission_config', ['id' => 1])->row();

	// 	// Pair ratio (ex: 1:1)
	// 	$ratio = !empty($cfg->binary_pair_ratio) ? (string) $cfg->binary_pair_ratio : '1:1';
	// 	$parts = explode(':', $ratio);
	// 	$rL = isset($parts[0]) ? max(1, (int) $parts[0]) : 1;
	// 	$rR = isset($parts[1]) ? max(1, (int) $parts[1]) : 1;

	// 	// Carry forward settings
	// 	$carry_status = !empty($cfg->carry_forward_status) ? (int) $cfg->carry_forward_status : 0;
	// 	$carry_mode = !empty($cfg->carry_forward_mode) ? strtolower((string) $cfg->carry_forward_mode) : 'lifetime';

	// 	// IMPORTANT: column is carry_forward_cap
	// 	$carry_cap = isset($cfg->carry_forward_cap) ? (float) $cfg->carry_forward_cap : 0;

	// 	// ========= User Package BV Unit (IMPORTANT for correct pair count) =========
	// 	// Pair unit = package.bv * ratio (so 20 BV & 1:1 => 20+20 makes 1 pair)
	// 	$pkg = null;
	// 	if (!empty($user->package_id)) {
	// 		$pkg = $this->db->get_where('package_config', ['id' => (int) $user->package_id, 'status' => 1])->row();
	// 	}
	// 	$unit_bv = (float) ($pkg->bv ?? 1);
	// 	if ($unit_bv <= 0)
	// 		$unit_bv = 1;

	// 	$needL_unit = $unit_bv * $rL;
	// 	$needR_unit = $unit_bv * $rR;

	// 	// ========= BV for "This Week" (audit only, from history) =========
	// 	// Display weekly BV generated by downline (history bv_volume)
	// 	[$ws, $we] = $this->BinaryModel->getWeekRange();
	// 	$bvWeek = $this->BinaryModel->getProductBVTotals($id, $ws, $we);

	// 	$left_bv_week = (float) ($bvWeek['left_bv'] ?? 0);
	// 	$right_bv_week = (float) ($bvWeek['right_bv'] ?? 0);

	// 	// ========= Carry Forward row (SOURCE OF TRUTH for pairing NOW) =========
	// 	$left_carry = 0.0;
	// 	$right_carry = 0.0;

	// 	if ($carry_status === 1) {
	// 		$carryRow = $this->BinaryModel->getOrCreateCarryRow($id);

	// 		// scope-based reset for DAILY/WEEKLY/MONTHLY
	// 		$currentScopeKey = $this->BinaryModel->getCarryScopeKey($carry_mode);

	// 		if ($carry_mode !== 'lifetime' && ($carryRow['scope_key'] ?? '') !== $currentScopeKey) {
	// 			// reset carry for new period
	// 			$this->BinaryModel->updateCarryRow($id, 0, 0, $currentScopeKey);
	// 			$left_carry = 0;
	// 			$right_carry = 0;
	// 		} else {
	// 			$left_carry = (float) ($carryRow['left_carry'] ?? 0);
	// 			$right_carry = (float) ($carryRow['right_carry'] ?? 0);
	// 		}

	// 		// Apply CAP if provided (cap per leg)
	// 		if ($carry_cap > 0) {
	// 			$left_carry = min($left_carry, $carry_cap);
	// 			$right_carry = min($right_carry, $carry_cap);
	// 		}
	// 	}

	// 	// ========= Totals for pairing/strength =========
	// 	// Pairing totals come ONLY from carry (because instant settlement consumes carry)
	// 	$left_total = $left_carry;
	// 	$right_total = $right_carry;

	// 	// STRONG / WEAK based on carry totals
	// 	if ($left_total == $right_total) {
	// 		$left_strength = 'STRONG';
	// 		$right_strength = 'STRONG';
	// 	} else {
	// 		$left_strength = ($left_total > $right_total) ? 'STRONG' : 'WEAK';
	// 		$right_strength = ($right_total > $left_total) ? 'STRONG' : 'WEAK';
	// 	}

	// 	// ========= Pairs possible NOW (based on carry + UNIT BV) =========
	// 	$pairs_possible_now = 0;
	// 	if ($left_total > 0 && $right_total > 0) {
	// 		$pairs_possible_now = (int) floor(min($left_total / $needL_unit, $right_total / $needR_unit));
	// 	}

	// 	// ========= Carry forward display =========
	// 	// Carry forward is what remains in carry table right now (do not recompute)
	// 	$display_left_carry = ($carry_status === 1) ? $left_carry : 0;
	// 	$display_right_carry = ($carry_status === 1) ? $right_carry : 0;

	// 	if ($carry_status === 1 && $carry_cap > 0) {
	// 		$display_left_carry = min($display_left_carry, $carry_cap);
	// 		$display_right_carry = min($display_right_carry, $carry_cap);
	// 	}

	// 	// ========= Next Pair Target (UNIT BV based) =========
	// 	$nextPairs = $pairs_possible_now + 1;

	// 	$need_left = max(0, ($nextPairs * $needL_unit) - $left_total);
	// 	$need_right = max(0, ($nextPairs * $needR_unit) - $right_total);

	// 	$next_target_leg = '';
	// 	$next_target_bv = 0.0;

	// 	if ($need_left > 0 && $need_right <= 0) {
	// 		$next_target_leg = 'left';
	// 		$next_target_bv = $need_left;
	// 	} elseif ($need_right > 0 && $need_left <= 0) {
	// 		$next_target_leg = 'right';
	// 		$next_target_bv = $need_right;
	// 	} else {
	// 		if ($need_left >= $need_right) {
	// 			$next_target_leg = 'left';
	// 			$next_target_bv = $need_left;
	// 		} else {
	// 			$next_target_leg = 'right';
	// 			$next_target_bv = $need_right;
	// 		}
	// 	}

	// 	// ========= Pairs Today (actual credited pairs from history) =========
	// 	[$ts, $te] = $this->BinaryModel->getTodayRange();
	// 	$pairs_today = $this->BinaryModel->getPairsCompleted($id, $ts, $te);

	// 	// ========= Team snapshot =========
	// 	[$ws2, $we2] = $this->BinaryModel->getWeekRange();
	// 	$team = $this->BinaryModel->getTeamSnapshotWeekly($id, $ws2, $we2);

	// 	// ========= Earnings =========
	// 	$binary_site_currency = (float) ($this->db->query("SELECT IFNULL(SUM(amount),0) AS s FROM history WHERE type='binary_commission' AND user_id='{$id}'")->row()->s ?? 0);
	// 	$binary_token_currency = (float) ($this->db->query("SELECT IFNULL(SUM(token_amount),0) AS s FROM history WHERE type='binary_commission' AND user_id='{$id}'")->row()->s ?? 0);

	// 	$roi_site_currency = (float) ($this->db->query("SELECT IFNULL(SUM(amount),0) AS s FROM history WHERE type='profit' AND user_id='{$id}'")->row()->s ?? 0);
	// 	$roi_token_currency = (float) ($this->db->query("SELECT IFNULL(SUM(token_amount),0) AS s FROM history WHERE type='profit' AND user_id='{$id}'")->row()->s ?? 0);

	// 	$direct_site_currency = (float) ($this->db->query("SELECT IFNULL(SUM(amount),0) AS s FROM history WHERE type='direct_commission' AND user_id='{$id}'")->row()->s ?? 0);
	// 	$direct_token_currency = (float) ($this->db->query("SELECT IFNULL(SUM(token_amount),0) AS s FROM history WHERE type='direct_commission' AND user_id='{$id}'")->row()->s ?? 0);

	// 	$level_site_currency = (float) ($this->db->query("SELECT IFNULL(SUM(amount),0) AS s FROM history WHERE type='level_commission' AND user_id='{$id}'")->row()->s ?? 0);
	// 	$level_token_currency = (float) ($this->db->query("SELECT IFNULL(SUM(token_amount),0) AS s FROM history WHERE type='level_commission' AND user_id='{$id}'")->row()->s ?? 0);

	// 	$user_usd_balance = site_wallet_balance($id);
	// 	$user_token_balance = site_token_balance($id);

	// 	// Referral links
	// 	$referral_id = (string) ($user->referral_id ?? '');
	// 	$left_link = $referral_id ? base_url('user/re?ref=L-' . $referral_id) : '';
	// 	$right_link = $referral_id ? base_url('user/re?ref=R-' . $referral_id) : '';

	// 	// ========= BANK VERIFICATION =========
	// 	$bank = $this->db->order_by('id', 'DESC')->get_where('user_bank', ['user_id' => $id])->row_array();
	// 	$weekly_progress_calc = 0;
	// 	$weekly_progress_calc += ($user && $user->kyc_status == 'approved') ? 1 : 0;
	// 	$weekly_progress_calc += (!empty($bank['status']) && strtolower((string) $bank['status']) === 'approved') ? 1 : 0;

	// 	// ========= Response =========
	// 	$data = [
	// 		"name" => $user->username,
	// 		"email" => $user->email,
	// 		"register_date" => $user->register_date,
	// 		"referral_id" => $referral_id,
	// 		"sponser" => $sponser_info ? ($sponser_info->email . " ( " . $sponser_info->referral_id . " )") : "",

	// 		"user_usd_balance" => $user_usd_balance ? currency_format($user_usd_balance) : 0,
	// 		"user_token_balance" => $user_token_balance,

	// 		"my_investment" => currency_format($my_investment),
	// 		"left_leg_count" => $left_leg_count > 0 ? $left_leg_count . ' Members' : "0 Member",
	// 		"right_leg_count" => $right_leg_count > 0 ? $right_leg_count . ' Members' : "0 Member",
	// 		"left_leg_investment" => currency_format($left_leg_investment),
	// 		"right_leg_investment" => currency_format($right_leg_investment),
	// 		'left_leg_investment_token' => token_format($left_leg_investment_token),
	// 		'right_leg_investment_token' => token_format($right_leg_investment_token),
	// 		'my_investment_token' => token_format($my_investment_token),

	// 		// Weekly BV generated (audit)
	// 		"left_leg_bv" => round($left_bv_week, 2),
	// 		"right_leg_bv" => round($right_bv_week, 2),

	// 		// Strength based on carry totals
	// 		"left_leg_strength" => $left_strength,
	// 		"right_leg_strength" => $right_strength,

	// 		// Carry forward (actual carry from carry table)
	// 		"carry_forward_status" => $carry_status,
	// 		"carry_forward_mode" => $carry_mode,
	// 		"carry_forward_cap_bv" => $carry_cap,
	// 		"left_carry_forward_bv" => round($display_left_carry, 2),
	// 		"right_carry_forward_bv" => round($display_right_carry, 2),

	// 		// Pair info (NOW)
	// 		"pair_ratio" => "{$rL}:{$rR}",
	// 		"pair_unit_bv" => round($unit_bv, 4), // ✅ important for UI/debug
	// 		"pairs_possible_now" => (int) $pairs_possible_now,
	// 		"pairs_today" => (int) $pairs_today,

	// 		// Next pair target
	// 		"next_pair_target_bv" => round($next_target_bv, 2),
	// 		"next_pair_target_leg" => $next_target_leg,

	// 		"left_invite_link" => $left_link,
	// 		"right_invite_link" => $right_link,

	// 		"team_snapshot" => [
	// 			"left_team" => (int) ($team['left_team'] ?? 0),
	// 			"right_team" => (int) ($team['right_team'] ?? 0),
	// 			"active_members" => (int) ($team['active_total'] ?? 0),
	// 			"new_joins" => (int) ($team['new_joins_week'] ?? 0),
	// 		],

	// 		"weekly_progress" => $weekly_progress_calc,
	// 		"kyc_status" => ($user && $user->kyc_status == 'approved') ? 1 : 0,
	// 		"bank_verification_status" => (!empty($bank['status']) && strtolower((string) $bank['status']) === 'approved') ? 1 : 0,

	// 		// Earnings
	// 		'binary_site_currency' => $binary_site_currency,
	// 		'binary_token_currency' => $binary_token_currency,
	// 		'roi_site_currency' => $roi_site_currency,
	// 		'roi_token_currency' => $roi_token_currency,
	// 		'direct_site_currency' => currency_format($direct_site_currency),
	// 		'direct_token_currency' => token_format($direct_token_currency),
	// 		'level_site_currency' => currency_format($level_site_currency),
	// 		'level_token_currency' => token_format($level_token_currency),
	// 	];

	// 	echo json_encode(['result' => true, 'data' => $data]);
	// }

	public function viewuserinfo($id)
	{
		$id = (int) $id;

		// -----------------------------
		// Helpers
		// -----------------------------
		$field_exists = function ($table, $field) {
			return $this->db->field_exists($field, $table);
		};

		$safeFloat = function ($v) {
			return (is_numeric($v) ? (float) $v : 0.0);
		};

		// "This Week" window (Mon..Sun)
		$today = date('Y-m-d');
		$weekStart = date('Y-m-d 00:00:00', strtotime('monday this week'));
		$weekEnd = date('Y-m-d 23:59:59', strtotime('sunday this week'));

		$dayStart = date('Y-m-d 00:00:00');
		$dayEnd = date('Y-m-d 23:59:59');

		$last7Start = date('Y-m-d 00:00:00', strtotime('-6 days')); // includes today
		$last7End = date('Y-m-d 23:59:59');
		$this->load->model('Wallet_model', 'wallet');
		// -----------------------------
		// BASIC USER
		// -----------------------------
		$uSel = ['id', 'username', 'email', 'register_date', 'referral_id', 'sponser'];
		// Optional columns (only if they exist in your users table)
		if ($field_exists('users', 'kyc_status'))
			$uSel[] = 'kyc_status';
		if ($field_exists('users', 'bank_verification_status'))
			$uSel[] = 'bank_verification_status';
		if ($field_exists('users', 'bank_status'))
			$uSel[] = 'bank_status';
		if ($field_exists('users', 'is_active'))
			$uSel[] = 'is_active';
		if ($field_exists('users', 'status'))
			$uSel[] = 'status';

		$userinfo = $this->db->select(implode(',', $uSel))
			->from('users')
			->where('id', $id)
			->get()->row();

		if (!$userinfo) {
			echo json_encode(['result' => false, 'data' => null, 'message' => 'User not found']);
			return;
		}

		$sponser_info = null;
		if (!empty($userinfo->sponser)) {
			$sponser_info = $this->db->select('id,email,referral_id,username')
				->from('users')
				->where('id', (int) $userinfo->sponser)
				->get()->row();
		}

		// -----------------------------
		// TEAM / INVESTMENTS (your existing BinaryModel)
		// -----------------------------
		$binary_info = $this->BinaryModel->calculateLegInvestments($id);

		$left_leg_users = $binary_info['left_leg_users'] ?? [];
		$right_leg_users = $binary_info['right_leg_users'] ?? [];

		$left_leg_count = is_array($left_leg_users) ? count($left_leg_users) : 0;
		$right_leg_count = is_array($right_leg_users) ? count($right_leg_users) : 0;

		$left_leg_investment = $safeFloat($binary_info['left_leg_investment'] ?? 0);
		$right_leg_investment = $safeFloat($binary_info['right_leg_investment'] ?? 0);
		$my_investment = $safeFloat($binary_info['my_investment'] ?? 0);

		$left_leg_investment_token = $safeFloat($binary_info['left_investment_token'] ?? 0);
		$right_leg_investment_token = $safeFloat($binary_info['right_investment_token'] ?? 0);
		$my_investment_token = $safeFloat($binary_info['my_investment_token'] ?? 0);

		// Team Snapshot (7 days)
		$allDownlineIds = array_values(array_unique(array_merge(
			is_array($left_leg_users) ? $left_leg_users : [],
			is_array($right_leg_users) ? $right_leg_users : []
		)));

		// Active members count (downline users status=1 if column exists)
		$active_members = 0;
		if (!empty($allDownlineIds) && $field_exists('users', 'status')) {
			$active_members = (int) $this->db->select('COUNT(*) AS c', false)
				->from('users')
				->where_in('id', $allDownlineIds)
				->where('status', '1')
				->get()->row()->c;
		}

		// New joins last 7 days (downline register_date in last 7 days)
		$new_joins = 0;
		if (!empty($allDownlineIds) && $field_exists('users', 'register_date')) {
			$new_joins = (int) $this->db->select('COUNT(*) AS c', false)
				->from('users')
				->where_in('id', $allDownlineIds)
				->where('register_date >=', $last7Start)
				->where('register_date <=', $last7End)
				->get()->row()->c;
		}

		$team_snapshot = [
			'left_team' => $left_leg_count,
			'right_team' => $right_leg_count,
			'active_members' => $active_members,
			'new_joins' => $new_joins,
		];

		// -----------------------------
		// EARNINGS TOTALS (your old fields)
		// -----------------------------
		// NOTE: if you moved binary to pair_commission, you may want to sum both.
		$binary_site_currency = $safeFloat($this->db->query(
			"SELECT COALESCE(SUM(amount),0) AS s FROM history WHERE user_id=? AND type IN('binary_commission','pair_commission')",
			[$id]
		)->row()->s);

		$binary_token_currency = $safeFloat($this->db->query(
			"SELECT COALESCE(SUM(token_amount),0) AS s FROM history WHERE user_id=? AND type IN('binary_commission','pair_commission')",
			[$id]
		)->row()->s);

		$roi_site_currency = $safeFloat($this->db->query(
			"SELECT COALESCE(SUM(amount),0) AS s FROM history WHERE user_id=? AND type='profit'",
			[$id]
		)->row()->s);

		$roi_token_currency = $safeFloat($this->db->query(
			"SELECT COALESCE(SUM(token_amount),0) AS s FROM history WHERE user_id=? AND type='profit'",
			[$id]
		)->row()->s);

		// $direct_site_currency = lifetime_income($id);
		$direct_site_currency = (float) $this->wallet->getTotalCommissionEarned($id);

		$direct_token_currency = $safeFloat($this->db->query(
			"SELECT COALESCE(SUM(token_amount),0) AS s FROM history WHERE user_id=? AND type='direct_commission'",
			[$id]
		)->row()->s);

		$level_site_currency = $safeFloat($this->db->query(
			"SELECT COALESCE(SUM(amount),0) AS s FROM history WHERE user_id=? AND type='level_commission'",
			[$id]
		)->row()->s);

		$level_token_currency = $safeFloat($this->db->query(
			"SELECT COALESCE(SUM(token_amount),0) AS s FROM history WHERE user_id=? AND type='level_commission'",
			[$id]
		)->row()->s);

		// -----------------------------
		// WALLET BALANCES
		// -----------------------------
		$user_usd_balance = site_wallet_balance($id);
		$user_token_balance = site_token_balance($id);

		// -----------------------------
		// DASHBOARD: BV THIS WEEK (LEFT/RIGHT) for THIS USER (receiver)
		// history rows were posted as: type=bv_volume and leg=left/right
		// -----------------------------
		$left_leg_bv = $safeFloat($this->db->select('COALESCE(SUM(amount),0) AS s', false)
			->from('history')
			->where('user_id', $id)
			->where('type', 'bv_volume')
			->where('leg', 'left')
			->where('date >=', $weekStart)
			->where('date <=', $weekEnd)
			->get()->row()->s);

		$right_leg_bv = $safeFloat($this->db->select('COALESCE(SUM(amount),0) AS s', false)
			->from('history')
			->where('user_id', $id)
			->where('type', 'bv_volume')
			->where('leg', 'right')
			->where('date >=', $weekStart)
			->where('date <=', $weekEnd)
			->get()->row()->s);

		// Strength + Need BV (match weak leg)
		$left_leg_strength = ($left_leg_bv >= $right_leg_bv) ? 'STRONG' : 'WEAK';
		$right_leg_strength = ($right_leg_bv > $left_leg_bv) ? 'STRONG' : 'WEAK';
		$need_bv = (float) abs($left_leg_bv - $right_leg_bv);

		// -----------------------------
		// Carry Forward (prefer binary_carry_forward -> fallback binary_carry)
		// -----------------------------
		$left_carry_forward_bv = 0;
		$right_carry_forward_bv = 0;

		if ($this->db->table_exists('binary_carry_forward')) {
			// pick lifetime if present; else first row
			$carryRow = $this->db->select('left_carry,right_carry')
				->from('binary_carry_forward')
				->where('user_id', $id)
				->order_by("CASE WHEN scope_key='lifetime' THEN 0 ELSE 1 END", "ASC", false)
				->limit(1)
				->get()->row();

			if ($carryRow) {
				$left_carry_forward_bv = $safeFloat($carryRow->left_carry);
				$right_carry_forward_bv = $safeFloat($carryRow->right_carry);
			}
		} elseif ($this->db->table_exists('binary_carry')) {
			$carryRow = $this->db->select('left_carry,right_carry')
				->from('binary_carry')
				->where('user_id', $id)
				->get()->row();

			if ($carryRow) {
				$left_carry_forward_bv = $safeFloat($carryRow->left_carry);
				$right_carry_forward_bv = $safeFloat($carryRow->right_carry);
			}
		}

		$carry_forward_bv = $left_carry_forward_bv + $right_carry_forward_bv;

		// -----------------------------
		// Pairs Today (pair_commission preferred; fallback binary_commission)
		// -----------------------------
		$pairs_today = (int) $this->db->select('COALESCE(SUM(pairs_count),0) AS c', false)
			->from('history')
			->where('user_id', $id)
			->where_in('type', ['pair_commission', 'binary_commission'])
			->where('date >=', $dayStart)
			->where('date <=', $dayEnd)
			->get()->row()->c;

		// -----------------------------
		// Weekly progress (simple + safe)
		// If you have a weekly target, use it here.
		// For now: progress = matched weak-leg BV / total BV (0..100)
		// -----------------------------
		$weak = min($left_leg_bv, $right_leg_bv);
		$sum = ($left_leg_bv + $right_leg_bv);
		$weekly_progress = ($sum > 0) ? round(($weak / $sum) * 100, 2) : 0;

		// -----------------------------
		// Checklist flags
		// -----------------------------
		$kyc_status = 0;
		if (isset($userinfo->kyc_status))
			$kyc_status = (int) $userinfo->kyc_status;

		$bank_verification_status = 0;
		if (isset($userinfo->bank_verification_status))
			$bank_verification_status = (int) $userinfo->bank_verification_status;
		elseif (isset($userinfo->bank_status))
			$bank_verification_status = (int) $userinfo->bank_status;

		// account_active_status: prefer users.status==1, else users.is_active==1
		$account_active_status = 1;
		if (isset($userinfo->status))
			$account_active_status = ((int) $userinfo->status === 1) ? 1 : 0;
		elseif (isset($userinfo->is_active))
			$account_active_status = ((int) $userinfo->is_active === 1) ? 1 : 0;

		// weak_leg_ok_status: 1 if both legs have some BV in this week (or you can change threshold)
		$weak_leg_ok_status = (min($left_leg_bv, $right_leg_bv) > 0) ? 1 : 0;

		// -----------------------------
		// Pending commission / Total withdrawn (optional, but your UI has ids)
		// Adjust types to your system if needed.
		// -----------------------------
		// $user_pending_commission = pending_commission_amount($id) ? pending_commission_amount($id) : 0.0; // if you store pending rows, calculate here

		$user_pending_commission = (float) $this->wallet->getPendingCommissionFromInvestments($id);
		$user_total_withdrawn = $paid_row = $this->db->select('IFNULL(SUM(amount),0) AS s', false)
			->from('withdrawals')
			->where('user_id', $id)
			->where_in('status', ['paid', 'success', 'completed', 'approved'])
			->get()->row();
		$user_total_withdrawn = (float) ($paid_row->s ?? 0);


		// -----------------------------
		// Build response payload (what JS expects)
		// -----------------------------
		$payload = [
			"name" => $userinfo->username,
			"email" => $userinfo->email,
			"register_date" => $userinfo->register_date,
			"referral_id" => $userinfo->referral_id,
			"sponser" => $sponser_info ? ($sponser_info->email . " ( " . $sponser_info->referral_id . " )") : "-",

			// balances
			"user_usd_balance" => currency_format($user_usd_balance),
			"user_token_balance" => $user_token_balance,

			// investments
			"my_investment" => currency_format($my_investment),
			"left_leg_count" => $left_leg_count,
			"right_leg_count" => $right_leg_count,
			"left_leg_investment" => currency_format($left_leg_investment),
			"right_leg_investment" => currency_format($right_leg_investment),
			"left_leg_investment_token" => token_format($left_leg_investment_token),
			"right_leg_investment_token" => token_format($right_leg_investment_token),
			"my_investment_token" => token_format($my_investment_token),

			// earnings totals
			"binary_site_currency" => $binary_site_currency,
			"binary_token_currency" => $binary_token_currency,
			"roi_token_currency" => $roi_token_currency,
			"direct_site_currency" => currency_format($direct_site_currency),
			"direct_token_currency" => token_format($direct_token_currency),
			"level_site_currency" => currency_format($level_site_currency),
			"level_token_currency" => token_format($level_token_currency),

			// dashboard binary summary (THIS WEEK)
			"left_leg_bv" => $left_leg_bv,
			"right_leg_bv" => $right_leg_bv,
			"left_leg_strength" => $left_leg_strength,
			"right_leg_strength" => $right_leg_strength,

			// carry forward
			"carry_forward_bv" => $carry_forward_bv,
			"left_carry_forward_bv" => $left_carry_forward_bv,
			"right_carry_forward_bv" => $right_carry_forward_bv,

			// targets
			"need_bv" => $need_bv,
			"pairs_today" => $pairs_today,
			"weekly_progress" => $weekly_progress,

			// checklist
			"kyc_status" => $kyc_status,
			"bank_verification_status" => $bank_verification_status,
			"account_active_status" => $account_active_status,
			"weak_leg_ok_status" => $weak_leg_ok_status,

			// optional KPI cards
			"user_pending_commission" => currency_format($user_pending_commission),
			"user_total_withdrawn" => currency_format($user_total_withdrawn),

			// team snapshot
			"team_snapshot" => $team_snapshot,
		];

		echo json_encode([
			'result' => true,
			'data' => $payload
		]);
	}



	// GET: /dashboard/recentOrdersAjax?limit=4
	public function recentOrdersAjax()
	{
		$user_id = (int) ($this->session->userdata('user_get_id') ?? 0);
		if ($user_id <= 0) {
			return $this->_json([
				'status' => false,
				'message' => 'Unauthorized'
			]);
		}

		$limit = (int) ($this->input->get('limit') ?? 4);
		if ($limit <= 0)
			$limit = 4;
		if ($limit > 20)
			$limit = 20;

		// Latest shipment status per order (if multiple shipment rows exist)
		$latestShipmentSub = "
      SELECT s1.*
      FROM order_shipments s1
      INNER JOIN (
        SELECT order_id, MAX(updated_at) AS mx
        FROM order_shipments
        GROUP BY order_id
      ) s2 ON s2.order_id = s1.order_id AND s2.mx = s1.updated_at
    ";

		/*
		  BV Earned calculation:
		  - If you have a real BV column, replace `p.commission` with that column.
		  - Here we assume product.commission is BV per item.
		*/
		$sql = "
      SELECT
        o.id,
        o.order_code,
        o.total_amount,
        o.created_at,
        o.payment_status,
        COALESCE(ls.status, 'processing') AS shipment_status,
        COALESCE(SUM(oi.quantity * COALESCE(p.commission, 0)), 0) AS bv_earned
      FROM orders o
      LEFT JOIN ($latestShipmentSub) ls ON ls.order_id = o.id
      LEFT JOIN order_items oi ON oi.order_id = o.id
      LEFT JOIN products p ON p.id = oi.product_id
      WHERE o.user_id = ?
      GROUP BY o.id
      ORDER BY o.id DESC
      LIMIT ?
    ";

		$rows = $this->db->query($sql, [$user_id, $limit])->result_array();

		$orders = [];
		foreach ($rows as $r) {
			$status = $r['shipment_status'] ?: 'processing';

			// You can map to your UI labels here:
			// packed/placed => PROCESSING, etc. OR keep raw.
			$orders[] = [
				'order_id' => (int) $r['id'],
				'order_code' => (string) $r['order_code'],
				'total_amount' => number_format((float) $r['total_amount'], 2),
				'bv_earned' => (int) round((float) $r['bv_earned']),
				'order_status' => (string) $status,
				'order_date' => date('d M Y', strtotime($r['created_at'])),
			];
		}

		$symbol = currency_info()->currency_symbol ?? '₹';

		return $this->_json([
			'status' => true,
			'currency_symbol' => $symbol,
			'orders' => $orders
		]);
	}


	public function recentCommissionsAjax()
	{
		$user_id = (int) ($this->session->userdata('user_userid') ?? 0);
		if ($user_id <= 0) {
			return $this->_json(['status' => false, 'message' => 'Unauthorized']);
		}

		$limit = (int) ($this->input->get('limit') ?? 4);
		if ($limit <= 0)
			$limit = 4;
		if ($limit > 20)
			$limit = 20;

		// Adjust column names if your history table differs.
		// Common columns: id, user_id, type, amount, token_amount, remarks/description, status, created_at
		$rows = $this->db->query("
        SELECT id, type, amount, status, date as created_at, description as remarks
        FROM history
        WHERE user_id = ?
          AND type IN ('binary_commission','level_commission','direct_commission','rank_reward','profit')
        ORDER BY id DESC
        LIMIT ?
    ", [$user_id, $limit])->result_array();

		$out = [];
		foreach ($rows as $r) {
			$type = (string) ($r['type'] ?? '');
			$remarks = trim((string) ($r['remarks'] ?? ''));

			// meta text (optional): you can store "Pair #1021" or "Level 1" in remarks
			$meta = $remarks;

			// if status empty, default success for commissions
			$status = trim((string) ($r['status'] ?? ''));
			if ($status === '')
				$status = 'success';

			$out[] = [
				'id' => (int) $r['id'],
				'type' => $type,
				'title' => $this->_commissionTitle($type),
				'meta' => $meta,
				'status' => $status,
				'amount' => number_format((float) ($r['amount'] ?? 0), 2),
				'date_text' => date('d M Y', strtotime($r['created_at'])),
			];
		}

		$symbol = currency_info()->currency_symbol ?? '₹';

		return $this->_json([
			'status' => true,
			'currency_symbol' => $symbol,
			'commissions' => $out,
		]);
	}

	private function _commissionTitle($type)
	{
		$t = strtolower(trim((string) $type));
		if ($t === 'binary_commission')
			return 'Pairing Bonus (Binary)';
		if ($t === 'level_commission')
			return 'Matching Bonus';
		if ($t === 'direct_commission')
			return 'Direct Referral Bonus';
		if ($t === 'rank_reward' || $t === 'rank')
			return 'Rank Reward';
		if ($t === 'profit' || $t === 'roi')
			return 'ROI / Profit';
		return 'Commission';
	}

	private function _json($data)
	{
		$this->output
			->set_content_type('application/json')
			->set_output(json_encode($data));
	}
}
