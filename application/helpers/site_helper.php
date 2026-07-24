<?php

function _dbg($tag, $payload = null)
{
    if (is_array($payload) || is_object($payload)) {
        $payload = json_encode($payload);
    }
    log_message('debug', "[CommissionEngine][$tag] {$payload}");
}

function site_settings($settings_type, $settings_name)
{
    $CI =& get_instance();
    $site_setting = $CI->db->query("SELECT * FROM site_settings where settings_type = '" . $settings_type . "' and settings_name = '" . $settings_name . "' ")->row();
    return $site_setting ? $site_setting->settings_value : '';
}


function get_transaction_level_token($type, $userid, $level)
{

    $CI =& get_instance();

    if ($userid) {

        $earningsInfo = $CI->db->query("SELECT sum(token_amount) as mybalance 
    FROM history where user_id = '" . $userid . "' and type = '" . $type . "' and coin_type = '2' and level_count = '" . $level . "' ")->row()->mybalance;

        $Earning = $earningsInfo;

        if ($Earning <= '0') {
            $balance = '0';
        } else {
            $balance = $Earning;
        }

        return token_format($balance, 4);

    } else {

        return '0';

    }
}



function get_transaction_level_currency($type, $userid, $level)
{

    $CI =& get_instance();

    if ($userid) {

        $earningsInfo = $CI->db->query("SELECT sum(amount) as mybalance 
    FROM history where user_id = '" . $userid . "' and type = '" . $type . "' and coin_type = '1' and level_count = '" . $level . "' ")->row()->mybalance;

        $Earning = $earningsInfo;

        if ($Earning <= '0') {
            $balance = '0';
        } else {
            $balance = $Earning;
        }

        return currency_format($balance, 4);

    } else {

        return '0';

    }
}

function get_transaction_level_token_admin($type, $level)
{

    $CI =& get_instance();

    $earningsInfo = $CI->db->query("SELECT sum(token_amount) as mybalance 
    FROM history where  type = '" . $type . "' and coin_type = '2' and level_count = '" . $level . "' ")->row()->mybalance;

    $Earning = $earningsInfo;

    if ($Earning <= '0') {
        $balance = '0';
    } else {
        $balance = $Earning;
    }

    return token_format($balance, 4);

}


function get_transaction_level_commission($type, $level)
{

    $CI =& get_instance();

    $earningsInfo = $CI->db->query("SELECT sum(amount) as mybalance , SUM(token_amount) AS total_token_amount 
    FROM history where  type = '" . $type . "' and level_count = '" . $level . "' ")->row();

    $Earning = $earningsInfo->mybalance ? $earningsInfo->mybalance : 0;
    $Earning_token = $earningsInfo->total_token_amount ? $earningsInfo->total_token_amount : 0;

    $array = array(
        'token_amount' => $Earning_token,
        'mybalance' => $Earning,
    );

    return $array;

}

function get_transaction_token($type, $userid)
{

    $CI =& get_instance();

    if ($userid) {

        $earningsInfo = $CI->db->query("SELECT sum(token_amount) as mybalance 
    FROM history where user_id = '" . $userid . "' and type = '" . $type . "' and coin_type = '2' ")->row()->mybalance;

        $Earning = $earningsInfo;

        if ($Earning <= '0') {
            $balance = '0';
        } else {
            $balance = $Earning;
        }

        return token_format($balance, 4);

    } else {

        return '0';

    }
}



function get_transaction_currecy($type, $userid)
{

    $CI =& get_instance();

    if ($userid) {

        $earningsInfo = $CI->db->query("SELECT sum(amount) as mybalance 
    FROM history where user_id = '" . $userid . "' and type = '" . $type . "' and coin_type = '1' ")->row()->mybalance;

        $Earning = $earningsInfo;

        if ($Earning <= '0') {
            $balance = '0';
        } else {
            $balance = $Earning;
        }

        return currency_format($balance, 4);

    } else {

        return '0';

    }
}


function get_transaction_token_admin($type)
{

    $CI =& get_instance();

    $earningsInfo = $CI->db->query("SELECT sum(token_amount) as mybalance 
    FROM history where  type = '" . $type . "' and coin_type = '2' ")->row()->mybalance;

    $Earning = $earningsInfo;

    if ($Earning <= '0') {
        $balance = '0';
    } else {
        $balance = $Earning;
    }

    return token_format($balance, 4);

}


function total_invest_token()
{

    $CI =& get_instance();

    $earningsInfo = $CI->db->query("SELECT sum(csq_deposit) as mybalance 
    FROM user_investment where status = '1' ")->row()->mybalance;

    if ($earningsInfo <= '0') {
        $balance = '0';
    } else {
        $balance = $earningsInfo;
    }

    return token_format($balance, 4);


}


function total_invest_currency()
{

    $CI =& get_instance();

    $earningsInfo = $CI->db->query("SELECT sum(invest_amount) as mybalance 
    FROM user_investment where status = '1'  ")->row()->mybalance;

    if ($earningsInfo <= '0') {
        $balance = '0';
    } else {
        $balance = $earningsInfo;
    }

    return currency_format($balance, 4);


}

function investment_balance($userid)
{

    $CI =& get_instance();

    if ($userid) {

        $earningsInfo = $CI->db->query("SELECT sum(invest_amount) as mybalance 
        FROM user_investment where user_id = '" . $userid . "' and  status = '1' and approve_status IN ('0','1') ")->row()->mybalance;

        if ($earningsInfo <= '0') {
            $balance = '0';
        } else {
            $balance = $earningsInfo;
        }

        return currency_format($balance, 4);

    } else {

        return '0';

    }

}

function email_log($random_number, $useremail, $type)
{

    $CI =& get_instance();

    $check_email = $CI->db->query("select * from email_log where email = '" . $useremail . "' and type= '" . $type . "'  ")->num_rows();

    $otp_data = array(
        'otp' => $random_number,
        'email' => $useremail,
        'type' => $type,
        'created_date' => date('Y-m-d H:i:s')
    );

    if ($check_email > 0) {
        $CI->db->where('type', $type);
        $CI->db->where('email', $useremail);
        $CI->db->update('email_log', $otp_data);
    } else {
        $CI->db->insert('email_log', $otp_data);
    }

}

function emailVerify($userid, $type, $otp)
{
    $CI =& get_instance();

    $query = $CI->db->query("SELECT admin_email FROM admin_members WHERE id = $userid");
    $user_email = $query->row()->admin_email ?? null;

    if (!$user_email) {
        return false;
    }

    $query = $CI->db->query("SELECT otp FROM email_log WHERE email = '$user_email' AND type = '$type'");
    $userotp = $query->row()->otp ?? null;

    return ($userotp === $otp);
}


function currency_info()
{

    $ci =& get_instance();
    $query = $ci->db->query("SELECT * FROM `currency_config` where currency_status = '1' ")->row();
    return $query;

}


function token_info()
{

    $ci =& get_instance();
    $query = $ci->db->query("SELECT * FROM `token_config` where currency_status = '1' ")->row();
    return $query;

}



// function currency_format($amount, $decimal = 2)
// {

//     $ci =& get_instance();
//     $query = $ci->db->query("SELECT * FROM `currency_config` where currency_status = '1' ")->row();
//     if ($query) {
//         $decimal = $query->decimal;
//     }
//     $send_amount = $query->currency_symbol . " " . number_format($amount, $decimal);
//     return $send_amount;

// }


function currency_format($amount, $decimal = 2)
{
    $ci =& get_instance();

    $amount = is_numeric($amount) ? (float) $amount : 0.0;

    $symbol = '';
    $dec = (int) $decimal;

    // ✅ escape reserved column `decimal`
    $row = $ci->db->query("
        SELECT currency_symbol, `decimal`
        FROM `currency_config`
        WHERE `currency_status` = '1'
        ORDER BY `id` DESC
        LIMIT 1
    ")->row();

    if ($row) {
        $symbol = isset($row->currency_symbol) ? trim((string) $row->currency_symbol) : '';
        if (isset($row->decimal) && is_numeric($row->decimal)) {
            $dec = (int) $row->decimal;
        }
    }

    if ($dec < 0)
        $dec = 0;
    if ($dec > 8)
        $dec = 8;

    return ($symbol !== '' ? ($symbol . ' ') : '') . number_format($amount, $dec);
}



function token_format($amount)
{

    $ci =& get_instance();
    $query = $ci->db->query("SELECT * FROM `token_config` where currency_status = '1' ")->row();
    $decimal = $query->decimal;
    $send_amount = $query->currency_symbol . " " . number_format($amount, $decimal);
    return $send_amount;

}

function currency_format_no_symbol($amount)
{

    $ci =& get_instance();
    $query = $ci->db->query("SELECT * FROM `currency_config` where currency_status = '1' ")->row();
    $decimal = $query->decimal;
    $send_amount = str_replace(',', '', $amount);
    return $send_amount;

}



// function site_wallet_balance_without_format($userid)
// {
//     $CI =& get_instance();

//     if ($userid) {

//         $EarningType = "('bonus','rank_commission','internel_transfer_received','internel_swap_received','product_commission','profit','direct_commission','level_commission','binary_commission')";
//         $MinisType = "('exchange','internel_transfer_debit','site_withdraw','internel_transfer_send','purchase_product')";

//         $earningsInfo = $CI->db->query("SELECT sum(amount) as mybalance FROM history where user_id = '" . $userid . "' and type in $EarningType and coin_type = '1' ")->row()->mybalance;

//         $minusInfo = $CI->db->query("SELECT sum(amount) as mybalance  FROM history where user_id = '" . $userid . "' and type in $MinisType and coin_type = '1' ")->row()->mybalance;

//         $seminusInfo = $CI->db->query("SELECT sum(amount) as mybalance FROM history where user_id = '" . $userid . "' and type = 'mining' and hash_id = 'user-wallet' ")->row()->mybalance;

//         $Earning = (float) $earningsInfo - (float) $minusInfo - (float) $seminusInfo;

//         $balance = max(0, $Earning);
//         return number_format($balance, 2, '.', '');

//     } else {

//         return '0';

//     }

// }



function site_wallet_balance_without_format($userid)
{
    $CI =& get_instance();

    $userid = (int) $userid;
    if ($userid <= 0) {
        return '0.00';
    }

    $earningTypes = [
        'bonus',
        'rank_commission',
        'internel_transfer_received',
        'internel_swap_received',
        'product_commission',
        'profit',
        'direct_commission',
        'level_commission',
        'binary_commission',
        'own_commission',
        'withdraw_refund'
    ];

    $minusTypes = [
        'exchange',
        'internel_transfer_debit',
        // 'site_withdraw',
        'internel_transfer_send',
        'purchase_product',
    ];

    // ✅ Earnings
    $earnings = (float) $CI->db->select('COALESCE(SUM(amount),0) AS total', false)
        ->from('history')
        ->where('user_id', $userid)
        ->where_in('type', $earningTypes)
        ->where('coin_type', '1')
        ->get()->row()->total;

    // ✅ Minus from history
    $minus = (float) $CI->db->select('COALESCE(SUM(amount),0) AS total', false)
        ->from('history')
        ->where('user_id', $userid)
        ->where_in('type', $minusTypes)
        ->where('coin_type', '1')
        ->get()->row()->total;

    // ✅ Mining deduction
    // $mining = (float) $CI->db->select('COALESCE(SUM(amount),0) AS total', false)
    //     ->from('history')
    //     ->where('user_id', $userid)
    //     ->where('type', 'mining')
    //     ->where('hash_id', 'user-wallet')
    //     ->get()->row()->total;
    $mining = 0;

    // ✅ Pending withdrawals
    $pendingWithdraw = 0.0;

    if ($CI->db->table_exists('withdrawals')) {

        $pendingWithdraw = (float) $CI->db->select('COALESCE(SUM(amount),0) AS total', false)
            ->from('withdrawals')
            ->where('user_id', $userid)
            ->where_in('status', ['pending', 'processing', 'under_review', 'approved'])
            ->get()->row()->total;

    } elseif ($CI->db->table_exists('withdraw_request')) {

        $pendingWithdraw = (float) $CI->db->select('COALESCE(SUM(amount),0) AS total', false)
            ->from('withdraw_request')
            ->where('user_id', $userid)
            ->where_in('status', ['pending', 'processing', 'under_review'])
            ->get()->row()->total;
    }

    // ✅ Final Balance
    $balance = $earnings - $minus - $mining - $pendingWithdraw;

    if ($balance < 0) {
        $balance = 0;
    }

    // ✅ Return formatted string (same behavior as original function)
    return number_format($balance, 2, '.', '');
}



// function site_wallet_balance($userid)
// {
//     $CI =& get_instance();

//     if ($userid) {

//         $EarningType = "('bonus','rank_commission','internel_transfer_received','internel_swap_received','product_commission','profit','direct_commission','level_commission','binary_commission')";
//         $MinisType = "('exchange','internel_transfer_debit','site_withdraw','internel_transfer_send','purchase_product')";

//         $earningsInfo = $CI->db->query("SELECT sum(amount) as mybalance FROM history where user_id = '" . $userid . "' and type in $EarningType and coin_type = '1' ")->row()->mybalance;

//         $minusInfo = $CI->db->query("SELECT sum(amount) as mybalance  FROM history where user_id = '" . $userid . "' and type in $MinisType and coin_type = '1' ")->row()->mybalance;

//         $seminusInfo = $CI->db->query("SELECT sum(amount) as mybalance  
//     FROM history where user_id = '" . $userid . "' and type = 'mining' and hash_id = 'user-wallet' ")->row()->mybalance;


//         $Earning = (float) $earningsInfo - (float) $minusInfo - (float) $seminusInfo;

//         if ($Earning <= '0') {
//             $balance = '0';
//         } else {
//             $balance = $Earning;
//         }

//         if ($balance > 0) {
//             return currency_format($balance, 4);
//         } else {
//             return '0';
//         }

//     } else {

//         return '0';

//     }

// }


function site_wallet_balance($userid)
{
    $CI =& get_instance();

    $userid = (int) $userid;
    if ($userid <= 0) {
        return 0.0;
    }

    $earningTypes = [
        'bonus',
        'rank_commission',
        'internel_transfer_received',
        'internel_swap_received',
        'product_commission',
        'profit',
        'direct_commission',
        'level_commission',
        'binary_commission',
        'own_commission',
        'withdraw_refund'
    ];

    $minusTypes = [
        'exchange',
        'internel_transfer_debit',
        // 'site_withdraw',
        'internel_transfer_send',
        'purchase_product',
    ];

    // ✅ earnings
    $earningsRow = $CI->db->select('COALESCE(SUM(amount),0) AS s', false)
        ->from('history')
        ->where('user_id', $userid)
        ->where_in('type', $earningTypes)
        ->where('coin_type', '1')
        ->get()->row();
    _dbg("earningsRow", $CI->db->last_query());
    // ✅ minus from history
    $minusRow = $CI->db->select('COALESCE(SUM(amount),0) AS s', false)
        ->from('history')
        ->where('user_id', $userid)
        ->where_in('type', $minusTypes)
        ->where('coin_type', '1')
        ->get()->row();
    _dbg("minusRow", $CI->db->last_query());

    // ✅ mining deduction
    // $miningRow = $CI->db->select('COALESCE(SUM(amount),0) AS s', false)
    //     ->from('history')
    //     ->where('user_id', $userid)
    //     ->where('type', 'mining')
    //     ->where('hash_id', 'user-wallet')
    //     ->get()->row();
    // _dbg("miningRow", $CI->db->last_query());

    // ✅ NEW: pending withdrawals (request applied but not paid yet)
    $pendingWithdraw = 0.0;

    if ($CI->db->table_exists('withdrawals')) {
        $wRow = $CI->db->select('COALESCE(SUM(amount),0) AS s', false)
            ->from('withdrawals')
            ->where('user_id', $userid)
            ->where_in('status', ['pending', 'processing', 'under_review', 'approved'])
            ->get()->row();
        $pendingWithdraw = (float) ($wRow->s ?? 0);
        _dbg("pendingWithdraw1", $pendingWithdraw);

    } elseif ($CI->db->table_exists('withdraw_request')) {
        $wRow = $CI->db->select('COALESCE(SUM(amount),0) AS s', false)
            ->from('withdraw_request')
            ->where('user_id', $userid)
            ->where_in('status', ['pending', 'processing', 'under_review'])
            ->get()->row();
        $pendingWithdraw = (float) ($wRow->s ?? 0);
    }
    _dbg("pendingWithdraw2", $pendingWithdraw);

    $earnings = (float) ($earningsRow->s ?? 0);
    $minus = (float) ($minusRow->s ?? 0);
    // $mining = (float) ($miningRow->s ?? 0);
    $mining = 0;

    _dbg("earnings", $earnings);
    _dbg("minus", $minus);
    _dbg("mining", $mining);
    _dbg("pendingWithdraw", $pendingWithdraw);

    // ✅ Apply pending withdraw requests also
    $balance = $earnings - $minus - $mining - $pendingWithdraw;

    if ($balance < 0)
        $balance = 0.0;

    return $balance;
}



function site_token_balance_without_format($userid)
{
    $CI =& get_instance();

    if ($userid) {

        $EarningType = "('bonus','internel_transfer_credit','direct_commission','profit','level_commission','binary_commission','internel_transfer_received')";
        $MinisType = "('exchange','internel_transfer_debit','site_withdraw','internel_transfer_send','internel_swap_send')";

        $earningsInfo = $CI->db->query("SELECT sum(token_amount) as mybalance FROM history where user_id = '" . $userid . "' and type in $EarningType and coin_type = '2' ")->row()->mybalance;

        $minusInfo = $CI->db->query("SELECT sum(token_amount) as mybalance  FROM history where user_id = '" . $userid . "' and type in $MinisType and coin_type = '2' ")->row()->mybalance;

        $Earning = floatval($earningsInfo) - floatval($minusInfo);
        $balance = max(0, $Earning);
        return number_format($balance, 2, '.', '');

    } else {

        return '0';

    }
}


function lifetime_income($userid)
{
    $CI =& get_instance();
    $userid = (int) $userid;
    if ($userid <= 0)
        return 0.0;

    $earningTypes = [
        'bonus',
        'rank_commission',
        'product_commission',
        'profit',
        'direct_commission',
        'level_commission',
        'binary_commission',
        'own_commission',
        'withdraw_refund'
    ];

    $row = $CI->db->select('COALESCE(SUM(amount),0) AS s', false)
        ->from('history')
        ->where('user_id', $userid)
        ->where_in('type', $earningTypes)
        ->where('coin_type', '1')
        ->where('status', '1')
        ->get()->row();

    return (float) ($row->s ?? 0);
}


if (!function_exists('pending_commission_amount')) {
    /**
     * Pending Commission (Display Only)
     * Because your system does not use status=0, we define "pending"
     * as commissions earned in the current payout window.
     *
     * Default: TODAY (00:00:00 to 23:59:59)
     * You can change to WEEK/MONTH by passing $mode.
     */
    function pending_commission_amount($user_id, $mode = 'today'): float
    {
        $CI =& get_instance();

        $user_id = (int) $user_id;
        if ($user_id <= 0)
            return 0.0;

        $mode = strtolower(trim((string) $mode));

        // payout window
        if ($mode === 'week') {
            $from = date('Y-m-d 00:00:00', strtotime('monday this week'));
            $to = date('Y-m-d 23:59:59', strtotime('sunday this week'));
        } elseif ($mode === 'month') {
            $from = date('Y-m-01 00:00:00');
            $to = date('Y-m-t 23:59:59');
        } else {
            // default today
            $from = date('Y-m-d 00:00:00');
            $to = date('Y-m-d 23:59:59');
        }

        // ✅ Your earning types (same as wallet)
        $types = [
            'profit',
            'direct_commission',
            'level_commission',
            'binary_commission',
            'pair_commission',
            'matching_bonus',
            'rank_commission',
            'bonus',
            'own_commission',
            'product_commission',
            'withdraw_refund'
        ];

        $row = $CI->db->select('COALESCE(SUM(amount),0) AS total', false)
            ->from('history')
            ->where('user_id', $user_id)
            ->where('coin_type', '1')
            ->where_in('type', $types)
            ->where('history_date >=', $from)
            ->where('history_date <=', $to)
            ->get()->row();
        _dbg("pending_commission_amount", [$CI->db->last_query(), $row]);
        return (float) ($row->total ?? 0);
    }
}

function site_token_balance($userid)
{
    $CI =& get_instance();

    if ($userid) {

        $EarningType = "('bonus','internel_transfer_credit','direct_commission','profit','level_commission','binary_commission','internel_transfer_received')";
        $MinisType = "('exchange','internel_transfer_debit','site_withdraw','internel_transfer_send','internel_swap_send')";

        $earningsInfo = $CI->db->query("SELECT sum(token_amount) as mybalance 
        FROM history where user_id = '" . $userid . "' and type in $EarningType and coin_type = '2' ")->row()->mybalance;

        $minusInfo = $CI->db->query("SELECT sum(token_amount) as mybalance  
        FROM history where user_id = '" . $userid . "' and type in $MinisType and coin_type = '2' ")->row()->mybalance;

        $Earning = $earningsInfo - $minusInfo;

        if ($Earning <= '0') {
            $balance = '0';
        } else {
            $balance = $Earning;
        }

        return token_format($balance, 4);

    } else {

        return '0';

    }
}

function cms_content($page_title)
{

    $CI =& get_instance();
    $page_content = $CI->db->query("SELECT * FROM page_content where page_name = '" . $page_title . "' ")->row()->content;
    return $page_content;

}

function social_link($page_title)
{
    $CI =& get_instance();
    $page_content = $CI->db->query("SELECT * FROM `sociallinks` where  social_name = '" . $page_title . "' ")->row()->link;
    return $page_content;
}


// --------------------- Helpers ---------------------
function moneyUSD($n)
{
    return '$ ' . number_format((float) $n, 2);
}

if (!function_exists('badgeClass')) {

    function badgeClass($st)
    {
        $s = strtoupper(trim((string) $st));
        if ($s === 'APPROVED' || $s === 'ACTIVE')
            return 'b-ok';
        if ($s === 'PENDING')
            return 'b-warn';
        if ($s === 'REJECTED' || $s === 'FAILED')
            return 'b-bad';
        if ($s === 'COMPLETED')
            return 'b-soft';
        return 'b-soft';
    }
    function pct($done, $need)
    {
        $need = max(1, (float) $need);
        $done = max(0, (float) $done);
        return (int) max(0, min(100, round(($done / $need) * 100)));
    }
}



function carry_period_key($mode, $date)
{
    $mode = strtolower((string) $mode);
    if ($mode === 'daily')
        return date('Ymd', strtotime($date));
    if ($mode === 'weekly')
        return date('oW', strtotime($date));   // ISO week
    if ($mode === 'monthly')
        return date('Ym', strtotime($date));
    return 'LIFETIME';
}

function get_carry_row($user_id)
{
    $CI =& get_instance();
    $row = $CI->db->get_where('binary_carry', ['user_id' => $user_id])->row();

    if (!$row) {
        $CI->db->insert('binary_carry', [
            'user_id' => $user_id,
            'left_carry' => 0,
            'right_carry' => 0,
            'period_key' => null,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        $row = $CI->db->get_where('binary_carry', ['user_id' => $user_id])->row();
    }

    return $row;
}


function is_withdraw_eligible($user_id)
{
    $CI =& get_instance();

    $user_id = (int) $user_id;
    if ($user_id <= 0) {
        return false;
    }

    // ===== User =====
    $user = $CI->db->get_where('users', ['id' => $user_id])->row();
    if (!$user) {
        return false;
    }

    // ===== Settings =====
    $withdraw_status = (int) site_settings('withdraw_settings', 'withdraw_status');
    $min_withdraw = (float) str_replace(',', '', site_settings('withdraw_settings', 'min_withdraw'));

    if ($withdraw_status !== 1) {
        return false;
    }

    // ===== KYC =====
    $kyc_ok = false;
    if (!empty($user->kyc_status)) {
        $kyc_ok = (
            strtolower((string) $user->kyc_status) === 'approved'
            || (string) $user->kyc_status === '1'
        );
    }
    if (!$kyc_ok) {
        return false;
    }

    // ===== Bank =====
    $bank = $CI->db->get_where('user_bank', ['user_id' => $user_id])->row();
    if (!$bank || strtolower($bank->status) !== 'approved') {
        return false;
    }

    // ===== Wallet =====
    $available_amount = (float) site_wallet_balance($user_id);
    if ($available_amount < $min_withdraw) {
        return false;
    }

    return true; // ✅ ALL PASSED
}


function profile_completion_percent($user_id)
{
    $CI =& get_instance();

    $user = $CI->db->get_where('users', ['id' => $user_id])->row();
    if (!$user)
        return 0;

    $score = 0;
    $step = 20; // each block = 20%

    // 1️⃣ Basic profile
    if (!empty($user->first_name) || !empty($user->name)) {
        if (!empty($user->email) && !empty($user->contact)) {
            $score += $step;
        }
    }

    // 2️⃣ Address details
    if (!empty($user->address) && !empty($user->country) && !empty($user->zipcode)) {
        $score += $step;
    }

    // 3️⃣ Profile image
    if (!empty($user->profile_img) || !empty($user->image)) {
        $score += $step;
    }

    // 4️⃣ KYC
    if (
        !empty($user->kyc_status) &&
        (strtolower($user->kyc_status) === 'approved' || $user->kyc_status == '1')
    ) {
        $score += $step;
    }

    // 5️⃣ Bank
    $bank = $CI->db->get_where('user_bank', [
        'user_id' => $user_id,
        'status' => 'approved'
    ])->row();

    if ($bank) {
        $score += $step;
    }

    return min(100, $score);
}



if (!function_exists('default_avatar_url')) {
    /**
     * Canonical fallback avatar shipped with the app. Used both as the server-side
     * default (see user_profile_image) and as the client-side onerror target, so a
     * missing or invalid avatar file never renders as a broken image.
     */
    function default_avatar_url()
    {
        return base_url('assets/default-user.png');
    }
}

if (!function_exists('avatar_onerror')) {
    /**
     * Reusable inline onerror handler for avatar <img> tags. Swaps a broken/missing
     * avatar to the local default exactly once (this.onerror=null prevents a loop if
     * the default itself were ever unavailable).
     *
     * Usage:  <img src="..." <?= avatar_onerror() ?> alt="">
     * In JS-built markup, use the DEFAULT_AVATAR constant instead (see the views).
     */
    function avatar_onerror()
    {
        return 'onerror="this.onerror=null;this.src=\'' . default_avatar_url() . '\';"';
    }
}

if (!function_exists('media_url')) {
    /**
     * Re-root a stored asset/upload URL onto the CURRENT base_url().
     *
     * Many image URLs across this app (wallet QR, chat attachments, KYC
     * documents) are persisted with the full base_url() baked in at write
     * time. When the site's domain later changes, every stored URL still
     * points at the OLD host and the image renders broken — even though the
     * file itself is perfectly valid on disk. This finds the first known
     * public-path segment ('uploads/' or 'assets/') and rebuilds the URL from
     * that relative path against the current base_url(), stripping whatever
     * scheme+host was stored (old OR new). A value that is already relative
     * (no scheme) is prefixed with base_url(); any other absolute URL is left
     * untouched. Non-destructive — the DB value is never modified. Returns ''
     * for empty input.
     *
     * Examples:
     *   https://old-host.com/uploads/chat/x.jpg   -> base_url('uploads/chat/x.jpg')
     *   https://old-host.com/assets/images/q.png  -> base_url('assets/images/q.png')
     *   uploads/kyc/12/x.png                       -> base_url('uploads/kyc/12/x.png')
     *   https://cdn.example.com/foo.png            -> unchanged (unknown absolute)
     */
    function media_url($stored)
    {
        $stored = trim((string) $stored);
        if ($stored === '') return '';
        foreach (['uploads/', 'assets/'] as $seg) {
            $pos = strpos($stored, $seg);
            if ($pos !== false) {
                return base_url(substr($stored, $pos));
            }
        }
        // Already relative (no scheme) -> anchor to the current base_url.
        if (!preg_match('~^https?://~i', $stored)) {
            return base_url(ltrim($stored, '/'));
        }
        // Unknown absolute URL -> leave as-is.
        return $stored;
    }
}

if (!function_exists('qr_public_url')) {
    /** Backwards-compatible alias — QR URLs are just another stored asset URL. */
    function qr_public_url($stored)
    {
        return media_url($stored);
    }
}

function user_profile_image($uid)
{
    $CI =& get_instance();

    $user = $CI->db->select('profile_img,image')
        ->where('id', (int) $uid)
        ->get('users')
        ->row();

    if (!empty($user->profile_img)) {
        return base_url('assets/images/' . $user->profile_img);
    }

    if (!empty($user->image)) {
        return base_url('assets/images/' . $user->image);
    }

    // Local default so avatars never depend on an external service (which fails
    // on offline/LAN deployments). A missing per-user file is handled client-side
    // via an img onerror fallback to this same default.
    return default_avatar_url();
}

if (!function_exists('user_cycle_info')) {
    /**
     * Per-user rolling cycle info, computed entirely in PHP from an anchor date
     * (e.g. users.register_date). Drives the Bonus Wallet 60-day reduction — each
     * user has an independent schedule based on when they registered. No SQL.
     *
     * @param string      $anchorDate any strtotime-parseable date (register_date)
     * @param int         $cycleDays  cycle length in days (default 60; 1 for testing)
     * @param string|null $asOf       evaluate "now" as this date (tests); null = today
     * @return array{anchor:string,cycle_days:int,elapsed_days:int,completed_cycles:int,
     *   current_cycle:int,day_in_cycle:int,cycle_start:string,cycle_end:string,
     *   next_cycle_date:string,is_boundary:bool}
     */
    function user_cycle_info($anchorDate, $cycleDays = 60, $asOf = null)
    {
        $cycleDays = max(1, (int) $cycleDays);
        $anchor = new DateTimeImmutable(date('Y-m-d', strtotime((string) $anchorDate)));
        $now    = new DateTimeImmutable($asOf ? date('Y-m-d', strtotime($asOf)) : 'today');

        $elapsedDays     = ($now < $anchor) ? 0 : (int) $anchor->diff($now)->days;
        $completedCycles = intdiv($elapsedDays, $cycleDays);
        $dayInCycle      = $elapsedDays % $cycleDays;

        $currentStart = $anchor->modify('+' . ($completedCycles * $cycleDays) . ' days');
        $currentEnd   = $currentStart->modify('+' . $cycleDays . ' days'); // exclusive

        return [
            'anchor'           => $anchor->format('Y-m-d'),
            'cycle_days'       => $cycleDays,
            'elapsed_days'     => $elapsedDays,
            'completed_cycles' => $completedCycles,      // 0,1,2,...
            'current_cycle'    => $completedCycles + 1,   // 1-based
            'day_in_cycle'     => $dayInCycle,            // 0 = a cycle just rolled today
            'cycle_start'      => $currentStart->format('Y-m-d'),
            'cycle_end'        => $currentEnd->modify('-1 day')->format('Y-m-d'),
            'next_cycle_date'  => $currentEnd->format('Y-m-d'),
            'is_boundary'      => $dayInCycle === 0,
        ];
    }
}

function kyc_completion_percent($uid)
{
    if (!$uid) return 0;

    $CI =& get_instance();
    $user = $CI->db->select('kyc_status')->from('users')->where('id', $uid)->get()->row();

    if (!$user) return 0;

    // users.kyc_status is an ENUM string ('none','pending','under_review',
    // 'resubmitted','approved','rejected') — NOT an integer. The old check
    // (== 1 && kyc_verified_at) was always false for the real string values,
    // and kyc_verified_at is frequently empty even when approved, so the ring
    // permanently read 0%. Map the status to a meaningful progress instead.
    switch (strtolower((string) $user->kyc_status)) {
        case 'approved':
            return 100;
        case 'pending':
        case 'under_review':
        case 'resubmitted':
            return 50;   // submitted, awaiting / in review
        default:
            return 0;    // none / rejected
    }
}