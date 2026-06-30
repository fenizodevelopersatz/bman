<?php

// defined('BASEPATH') OR exit('No direct script access allowed');

// class CommissionEngine_model extends CI_Model
// {
//     public function __construct()
//     {
//         parent::__construct();
//         if (!isset($this->binarycarryengine) || !$this->binarycarryengine) {
//             $this->load->library('BinaryCarryEngine', null, 'binarycarryengine');
//         }
//     }

//     private function _dbg($tag, $payload = null)
//     {
//         if (is_array($payload) || is_object($payload)) {
//             $payload = json_encode($payload);
//         }
//         log_message('debug', "[CommissionEngine][$tag] {$payload}");
//     }

//     /**
//      * Process commissions after investment.
//      * Pays Direct + Level commissions (skips Level 1 if Direct is paid).
//      */
//     public function process_investment(array $ctx)
//     {
//         // 0) Global switches
//         $cmisssion_settings = $this->db->where('id', 1)->get('commission_config')->row();
//         if (!$cmisssion_settings)
//             return false;

//         // 1) Package
//         $pkg = $this->db->where('id', (int) $ctx['package_id'])
//             ->where('status', '1')
//             ->get('package_config')->row();
//         if (!$pkg)
//             return false;

//         $get = function ($obj, $prop, $def = null) {
//             return isset($obj->$prop) ? $obj->$prop : $def;
//         };


//         $this->_dbg('CTX', $ctx);
//         $this->_dbg('PKG', ['id' => (int) $ctx['package_id']]);

//         // 2) Direct config
//         $direct_enabled = (int) $get($cmisssion_settings, 'direct_commission_status', 1);
//         $direct_val = (float) $get($pkg, 'direct_commission', 0);
//         // Prefer package-level type; fallback to a global direct_commission_type if you keep one
//         $direct_type = $get($pkg, 'direct_commission_type', $get($cmisssion_settings, 'direct_commission_type', 'percent')); // 'percent'|'amount'

//         // 3) Level config (array of %: level 1..N)
//         $level_pv_arr = json_decode($get($pkg, 'level_pv_json', '[]'), true);
//         if (!is_array($level_pv_arr))
//             $level_pv_arr = [];

//         // 4) PV/BV
//         $bv = (float) $get($pkg, 'bv', 0);
//         $pv = 0;
//         // (float)$get($pkg, 'pv', $bv);

//         // 5) Buyer + sponsor (ALIAS the misspelled column!)
//         $user = $this->db->select('id, sponser AS sponsor_id')
//             ->where('id', (int) $ctx['user_id'])
//             ->get('users')->row();
//         if (!$user)
//             return false;

//         $this->db->trans_begin();



//         // ---- OWN COMMISSION (to buyer) ----
//         // package_config has own_commission (assume PERCENT if no type column)
//         $own_val_enabled = (int) $get($cmisssion_settings, 'own_commission_status', 1);
//         $own_val = (float) $get($pkg, 'own_commission', 0);

//         if ($own_val > 0 && $own_val_enabled > 0) {
//             $own_amt = ($ctx['amount'] * $own_val / 100.0);
//             if ($own_amt > 0) {
//                 $this->_credit_history([
//                     'user_id' => (int) $user->id,
//                     'amount' => $own_amt,
//                     'token_amount' => ($ctx['earn_type'] == '2' ? $own_amt * (float) $ctx['csq_price'] : 0),
//                     'type' => 'own_commission',
//                     'description' => $ctx['note'] . ' - Own',
//                     'invest_id' => (int) $ctx['invest_id'],
//                     'from_id' => (int) $user->id,
//                     'earn_by' => (string) $ctx['earn_type'],
//                     'date' => $ctx['invest_date'],
//                     'level_type' => $this->_get_immediate_leg((int) $user->id)
//                 ]);
//             }
//         }

//         // ---- DIRECT COMMISSION (always preferred over Level-1) ----
//         // If enabled and sponsor exists, pay direct to sponsor
//         $paid_direct = false;
//         if ($direct_enabled && $direct_val > 0 && !empty($user->sponsor_id)) {
//             $direct_amt = ($direct_type === 'percent')
//                 ? ($ctx['amount'] * $direct_val / 100)
//                 : $direct_val;

//             if ($direct_amt > 0) {
//                 $leg_vs_sponsor = $this->_get_leg_relative_to((int) $user->sponsor_id, (int) $user->id); // left|right|null
//                 $this->_credit_history([
//                     'user_id' => (int) $user->sponsor_id,          // receiver = sponsor
//                     'amount' => $direct_amt,
//                     'token_amount' => ($ctx['earn_type'] == '2' ? $direct_amt * (float) $ctx['csq_price'] : 0),
//                     'type' => 'direct_commission',
//                     'description' => $ctx['note'] . ' - Direct',
//                     'invest_id' => (int) $ctx['invest_id'],
//                     'from_id' => (int) $user->id,                  // generator = buyer
//                     'earn_by' => (string) $ctx['earn_type'],       // '1' or '2'
//                     'date' => $ctx['invest_date'],
//                     'level_type' => $leg_vs_sponsor,                 // store leg relative to receiver
//                 ]);
//                 $paid_direct = true;
//             }
//         }


//         $this->_dbg('DIRECT_SETTINGS', [
//             'enabled' => $direct_enabled,
//             'val' => $direct_val,
//             'type' => $direct_type,
//             'sponsor' => isset($user->sponsor_id) ? (int) $user->sponsor_id : null
//         ]);



//         // ---- LEVEL COMMISSIONS ----
//         $upline_id = $user->sponsor_id;

//         // If direct was paid to sponsor, begin levels from sponsor's sponsor (Level-2)
//         if ($paid_direct && !empty($upline_id)) {
//             $s = $this->db->select('sponser AS sponsor_id')->where('id', $upline_id)->get('users')->row();
//             $upline_id = $s ? $s->sponsor_id : null;  // move to Level-2 receiver
//         }

//         // Level array indexes: 0 => Level-1, 1 => Level-2, ...
//         $start_idx = $paid_direct ? 1 : 0;

//         for ($i = $start_idx; $i < count($level_pv_arr); $i++) {
//             if (empty($upline_id))
//                 break;

//             $level_num = $i + 1; // human-readable level number
//             $pct = (float) $level_pv_arr[$i];
//             if ($pct > 0) {
//                 $amt = $ctx['amount'] * ($pct / 100);
//                 if ($amt > 0) {
//                     $leg_vs_upline = $this->_get_leg_relative_to((int) $upline_id, (int) $user->id);
//                     $this->_credit_history([
//                         'user_id' => (int) $upline_id,            // receiver = this upline
//                         'amount' => $amt,
//                         'token_amount' => ($ctx['earn_type'] == '2' ? $amt * (float) $ctx['csq_price'] : 0),
//                         'type' => 'level_commission',
//                         'description' => $ctx['note'] . " - Level {$level_num}",
//                         'invest_id' => (int) $ctx['invest_id'],
//                         'level_count' => $level_num,
//                         'from_id' => (int) $user->id,             // generator = buyer
//                         'earn_by' => (string) $ctx['earn_type'],
//                         'date' => $ctx['invest_date'],
//                         'level_type' => $leg_vs_upline,             // leg relative to receiver
//                     ]);
//                 }
//             }

//             // next upline
//             $s = $this->db->select('sponser AS sponsor_id')->where('id', $upline_id)->get('users')->row();
//             $upline_id = $s ? $s->sponsor_id : null;
//         }

//         $this->_dbg('LEVEL_SETTINGS', [
//             'levels' => $level_pv_arr
//         ]);


//         // ---- PV/BV LEDGER ----
//         // ---- PV/BV LEDGER + HISTORY LOGS ----
//         $this->_dbg('PV/BV LEDGER + HISTORY LOGS', ['pv' => $pv, 'bv' => $bv, 'boolean' => $pv > 0 || $bv > 0]);

//         if ($pv > 0 || $bv > 0) {
//             // a) single ledger row for the buyer (used by your matcher)
//             $this->_post_volume([
//                 'user_id' => (int) $user->id,
//                 'invest_id' => (int) $ctx['invest_id'],
//                 'pv' => $pv,
//                 'bv' => $bv,
//                 'source_amount' => (float) $ctx['amount'],
//                 'date' => $ctx['invest_date'],
//             ]);

//             // b) propagate PV/BV up the tree in history (receiver = ancestor, from_id = buyer)
//             $ancestor_id = $user->sponsor_id;
//             $level_hop = 1;
//             while (!empty($ancestor_id)) {
//                 $leg_vs_ancestor = $this->_get_leg_relative_to((int) $ancestor_id, (int) $user->id);

//                 if ($pv > 0) {
//                     $this->_history_volume([
//                         'user_id' => (int) $ancestor_id,             // receiver of volume
//                         'from_id' => (int) $user->id,                // generator = buyer
//                         'leg' => $leg_vs_ancestor,              // leg relative to receiver
//                         'amount' => (float) $pv,
//                         'type' => 'pv_volume',
//                         'description' => $ctx['note'] . " - PV Posted ({$pv}) from #{$user->id} [L{$level_hop}]",
//                         'invest_id' => (int) $ctx['invest_id'],
//                         'earn_by' => (string) $ctx['earn_type'],
//                         'date' => $ctx['invest_date'],
//                         'basis' => 'PV',
//                     ]);
//                 }

//                 // if ($bv > 0) {
//                 //     $this->_history_volume([
//                 //         'user_id' => (int) $ancestor_id,
//                 //         'from_id' => (int) $user->id,
//                 //         'leg' => $leg_vs_ancestor,
//                 //         'amount' => (float) $bv,
//                 //         'type' => 'bv_volume',
//                 //         'description' => $ctx['note'] . " - BV Posted ({$bv}) from #{$user->id} [L{$level_hop}]",
//                 //         'invest_id' => (int) $ctx['invest_id'],
//                 //         'earn_by' => (string) $ctx['earn_type'],
//                 //         'date' => $ctx['invest_date'],
//                 //         'basis' => 'BV',
//                 //         'method' => 'package'
//                 //     ]);
//                 // }

//                 $this->_dbg('BINARY_PV_BV', [
//                     'pv' => $pv,
//                     'bv' => $bv,
//                     'boolean' => $bv > 0 && !empty($leg_vs_ancestor)
//                 ]);
//                 // ✅ NEW: Add BV into carry table (this makes binary summary + pairing work)
//                 if ($bv > 0 && !empty($leg_vs_ancestor)) {

//                     $carry_enabled = $this->binarycarryengine->carryEnabled($cmisssion_settings);
//                     if ($carry_enabled) {
//                         $carry_mode = $this->binarycarryengine->carryMode($cmisssion_settings); // LIFETIME/DAILY/WEEKLY/MONTHLY
//                         $carry_cap = $this->binarycarryengine->carryCap($cmisssion_settings);  // float
//                         $this->_dbg('BINARY_CARRY', [
//                             'pv' => $pv,
//                             'bv' => $bv,
//                             'carry_cap' => $carry_cap,
//                             'carry_mode' => $carry_mode,
//                             'boolean' => $carry_enabled
//                         ]);
//                         // updates binary_carry_forward for that ancestor + leg
//                         $this->binarycarryengine->addBV(
//                             (int) $ancestor_id,
//                             (string) $leg_vs_ancestor,     // left/right
//                             (float) $bv,
//                             (string) $carry_mode,
//                             (float) $carry_cap,
//                             (string) $ctx['invest_date']
//                         );
//                     }
//                 }

//                 // climb one level up
//                 $row = $this->db->select('sponser AS sponsor_id')->where('id', $ancestor_id)->get('users')->row();
//                 $ancestor_id = $row ? $row->sponsor_id : null;
//                 $level_hop++;
//             }


//         }

//         // ---- BINARY PAIR SETTLEMENT (for all ancestors) ----
//         $this->_settle_pairs_for_ancestors([
//             'buyer_id' => (int) $user->id,              // <-- REQUIRED (not user_id)
//             'invest_id' => (int) $ctx['invest_id'],
//             'invest_date' => $ctx['invest_date'],
//             'note' => $ctx['note'],
//             'earn_type' => (string) $ctx['earn_type'],   // '1' or '2'
//             'csq_price' => (float) $ctx['csq_price'],
//         ]);




//         if ($this->db->trans_status() === FALSE) {
//             $this->db->trans_rollback();
//             return false;
//         }
//         $this->db->trans_commit();
//         return true;
//     }

//     /**
//      * Write a PV/BV audit row into `history`.
//      * amount = PV or BV units (not currency). token_amount kept 0.
//      * `basis` helps distinguish PV vs BV in reports.
//      */
//     private function _history_volume(array $h)
//     {
//         $currency = currency_info();
//         $token = token_info();

//         $row = [
//             'user_id' => (int) $h['user_id'],           // receiver
//             'type' => (string) $h['type'],           // 'pv_volume' | 'bv_volume'
//             'amount' => (float) $h['amount'],
//             'status' => '1',
//             'date' => $h['date'],
//             'description' => (string) $h['description'],
//             'coin_type' => 1,
//             'invest_id' => (int) $h['invest_id'],
//             'hash_id' => 'system',
//             'token_value' => 0,
//             'history_date' => $h['date'],

//             'level_type' => isset($h['leg']) ? $h['leg'] : NULL, // left|right
//             'level_count' => NULL,
//             'from_id' => isset($h['from_id']) ? (int) $h['from_id'] : NULL, // generator

//             'rank_type' => NULL,
//             'royality_received_by' => 0,
//             'token_amount' => 0,
//             'coin_id' => $currency->id,
//             'token_id' => $token->id,
//             'total_left_invest' => 0,
//             'total_right_invest' => 0,
//             'total_left_roi' => 0,
//             'total_right_roi' => 0,
//             'total_left_users' => 0,
//             'total_right_users' => 0,
//             'total_left_invest_ids' => NULL,
//             'total_right_invest_ids' => NULL,
//             'transaction_id' => NULL,
//             'deductionFromSiteWallet' => 0,
//             'remainingAmount' => 0,
//             'deductionFromWallet' => 0,
//             'pair_ratio_used' => 0,
//             'pairs_count' => 0,
//             'basis' => isset($h['basis']) ? (string) $h['basis'] : NULL,
//             'ref_history_id' => NULL,
//             'earn_by' => (string) $h['earn_by'],
//             'leg' => isset($h['leg']) ? $h['leg'] : NULL,
//             'method' => isset($h['method']) ? $h['method'] : NULL,
//         ];

//         return $this->db->insert('history', $row);
//     }



//     // private function _credit_history(array $h)
//     // {
//     //     $currency = currency_info();
//     //     $token    = token_info();

//     //     $row = [
//     //         'user_id'      => (int)$h['user_id'],           // receiver of commission
//     //         'amount'       => (float)$h['amount'],
//     //         'token_amount' => (float)$h['token_amount'],
//     //         'type'         => (string)$h['type'],           // direct_commission | level_commission | ...
//     //         'history_date' => $h['date'],
//     //         'date'         => $h['date'],
//     //         'status'       => '1',
//     //         'hash_id'      => 'system',
//     //         'invest_id'    => (int)$h['invest_id'],
//     //         'description'  => (string)$h['description'],
//     //         'coin_id'      => $currency->id,
//     //         'token_id'     => $token->id,
//     //         'from_id'      => isset($h['from_id']) ? (int)$h['from_id'] : NULL, // generator = buyer
//     //         'earn_by'      => (string)$h['earn_by'],        // '1' or '2'
//     //         'level_type'   => isset($h['level_type']) ? $h['level_type'] : NULL, // leg vs receiver
//     //         'level_count'   => isset($h['level_count']) ? $h['level_count'] : NULL, // leg vs receiver
//     //         'leg'          => isset($h['level_type']) ? $h['level_type'] : NULL, // keep both if you use both
//     //     ];
//     //     return $this->db->insert('history', $row);
//     // }

//     private function _credit_history(array $h)
//     {
//         $currency = currency_info();
//         $token = token_info();

//         $row = [
//             'user_id' => (int) $h['user_id'],           // receiver
//             'amount' => (float) $h['amount'],
//             'token_amount' => (float) $h['token_amount'],
//             'type' => (string) $h['type'],
//             'history_date' => $h['date'],
//             'date' => $h['date'],
//             'status' => '1',
//             'hash_id' => 'system',
//             'invest_id' => (int) $h['invest_id'],
//             'description' => (string) $h['description'],
//             'coin_id' => $currency->id,
//             'token_id' => $token->id,
//             'from_id' => isset($h['from_id']) ? (int) $h['from_id'] : NULL,
//             'earn_by' => (string) $h['earn_by'],
//             'level_type' => isset($h['level_type']) ? $h['level_type'] : NULL,
//             'leg' => isset($h['level_type']) ? $h['level_type'] : NULL, // keep mirrored if you use both
//             'level_count' => isset($h['level_count']) ? $h['level_count'] : NULL,

//             // optional auditing fields if you want to see pair stats in history:
//             'pairs_count' => isset($h['pairs_count']) ? (int) $h['pairs_count'] : 0,
//             'pair_ratio_used' => isset($h['pair_ratio_used']) ? (string) $h['pair_ratio_used'] : NULL,
//             'total_left_invest' => isset($h['total_left_invest']) ? (float) $h['total_left_invest'] : 0,
//             'total_right_invest' => isset($h['total_right_invest']) ? (float) $h['total_right_invest'] : 0,
//         ];
//         return $this->db->insert('history', $row);
//     }


//     private function _post_volume(array $v)
//     {
//         $row = [
//             'user_id' => (int) $v['user_id'],
//             'invest_id' => (int) $v['invest_id'],
//             'pv' => (float) $v['pv'],
//             'bv' => (float) $v['bv'],
//             'source_amount' => (float) $v['source_amount'],
//             'created_at' => $v['date'],
//         ];
//         return $this->db->insert('binary_volume_ledger', $row);
//     }


//     private function _get_immediate_leg($user_id)
//     {
//         $row = $this->db->select('position')
//             ->from('binary_placement')
//             ->where('user_id', (int) $user_id)
//             ->get()->row();
//         return $row ? strtolower($row->position) : null; // 'left'|'right'|null
//     }

//     /**
//      * Return leg side of $node_id relative to a specific ancestor ($ancestor_id).
//      * Walks up parents until it reaches $ancestor_id, and returns the FIRST step's position
//      * (i.e., whether the node sits in the ancestor's left or right subtree).
//      *
//      * Example use: what leg is BUYER relative to SPONSOR or higher uplines?
//      */
//     private function _get_leg_relative_to($ancestor_id, $node_id)
//     {
//         $ancestor_id = (int) $ancestor_id;
//         $current_id = (int) $node_id;

//         while ($current_id > 0) {
//             $row = $this->db->select('parent_id, position')
//                 ->from('binary_placement')
//                 ->where('user_id', $current_id)
//                 ->get()->row();
//             if (!$row)
//                 return null;
//             $this->_dbg("_get_leg_relative_to", ['row' => $this->db->last_query(), 'row_value' => ($row ?? ''), 'ancestor_id' => $ancestor_id, 'node_id' => $node_id]);
//             // If the parent of current node is the ancestor, the position here is the leg
//             if ((int) $row->parent_id === $ancestor_id) {
//                 return strtolower($row->position); // 'left'|'right'
//             }

//             // climb one level up
//             $current_id = (int) $row->parent_id;
//         }
//         return null;
//     }




//     /** Get a user's active package row (joins users.package_id -> package_config.id) */
//     private function _get_user_package($user_id)
//     {
//         $u = $this->db->select('u.id, u.package_id, p.*')
//             ->from('users u')
//             ->join('package_config p', 'p.id = u.package_id', 'left')
//             ->where('u.id', (int) $user_id)
//             ->get()->row();
//         $this->_dbg('__get_user_package', ['user_id' => $user_id, 'query' => $this->db->last_query()]);
//         return $u ?: null;
//     }

//     /** Parse ratio like "1:1" or "1:2" -> [1,1] or [1,2] (fallback 1:1) */
//     private function _parse_ratio($ratio)
//     {
//         if (!is_string($ratio) || strpos($ratio, ':') === false)
//             return [1, 1];
//         [$l, $r] = array_map('trim', explode(':', $ratio, 2));
//         $L = max(1, (int) $l);
//         $R = max(1, (int) $r);
//         return [$L, $R];
//     }

//     /** Get today's window (for daily caps) based on a timestamp (invest_date) */
//     private function _day_window($ts)
//     {
//         $start = date('Y-m-d 00:00:00', strtotime($ts));
//         $end = date('Y-m-d 23:59:59', strtotime($ts));
//         return [$start, $end];
//     }


//     /**
//      * For each ancestor of buyer, compute pairs using posted volumes, honor daily caps,
//      * pay pair commission, optionally pay matching bonus to further uplines.
//      *
//      * Uses:
//      * - commission_config.binary_commission_status (enable/disable binary payout)
//      * - commission_config.binary_pair_ratio (e.g. "1:1")
//      * - commission_config.binary_pair_type (percent|amount) [fallback if receiver package lacks type]
//      * - receiver's package: pair_commission_status, pair_commission, pair_commission_type, daily_max_pairs
//      * - receiver's package for matching bonus: matching_bonus_json (as percentages over pair income)
//      */
//     // private function _settle_pairs_for_ancestors(array $ctx)
//     // {

//     //     $this->_dbg('PAIR_START', [
//     //         'buyer' => $ctx['buyer_id'],
//     //         'date' => $ctx['invest_date'],
//     //         'ctx' => $ctx,
//     //     ]);

//     //     // load global settings
//     //     $conf = $this->db->where('id', 1)->get('commission_config')->row();
//     //     if (!$conf || (int) $conf->binary_commission_status !== 1)
//     //         return;

//     //     // ratio & type from global (individual receiver package can override type only)
//     //     [$Lratio, $Rratio] = $this->_parse_ratio($conf->binary_pair_ratio);
//     //     $global_pair_type = isset($conf->binary_pair_type) ? $conf->binary_pair_type : 'percent';

//     //     // walk ancestors upward
//     //     $ancestor_id = $this->db->select('sponser AS sponsor_id')
//     //         ->where('id', (int) $ctx['buyer_id'])
//     //         ->get('users')->row();
//     //     $ancestor_id = $ancestor_id ? (int) $ancestor_id->sponsor_id : 0;

//     //     [$dayStart, $dayEnd] = $this->_day_window($ctx['invest_date']);
//     //     $hop = 1;


//     //     while ($ancestor_id > 0) {

//     //         // receiver's package (limits & %)
//     //         $recv = $this->_get_user_package($ancestor_id); // includes package fields if set
//     //         $pair_enabled = $recv && isset($recv->pair_commission_status) ? (int) $recv->pair_commission_status : 1;
//     //         $pair_val = $recv && isset($recv->pair_commission) ? (float) $recv->pair_commission : 0;
//     //         $pair_type = $recv && isset($recv->pair_commission_type) ? $recv->pair_commission_type : $global_pair_type; // 'percent'|'amount'
//     //         $daily_cap = $recv && isset($recv->daily_max_pairs) ? (int) $recv->daily_max_pairs : 0; // 0 => unlimited


//     //         $this->_dbg('PAIR_ANCESTOR', [
//     //             'ancestor' => $ancestor_id,
//     //             'ratio' => "{$Lratio}:{$Rratio}",
//     //             'pair_type' => $pair_type,
//     //             'pair_val' => $pair_val,
//     //             'cap' => $daily_cap,
//     //             'boolean' => ($pair_enabled && $pair_val > 0),
//     //         ]);


//     //         if ($pair_enabled && $pair_val > 0) {
//     //             // compute today's available leg volumes under this ancestor (from history rows propagated earlier)
//     //             // Sum BV by leg for today (you can include PV if plan uses PV for pairing; below uses BV)
//     //             $bvLeft = (float) $this->db->select('COALESCE(SUM(amount),0) AS s', false)
//     //                 ->from('history')
//     //                 ->where('user_id', $ancestor_id)
//     //                 ->where('type', 'bv_volume')
//     //                 ->where('date >=', $dayStart)
//     //                 ->where('date <=', $dayEnd)
//     //                 ->where('leg', 'left')
//     //                 ->get()->row()->s;

//     //             $bv_left_query = $this->db->last_query();

//     //             $bvRight = (float) $this->db->select('COALESCE(SUM(amount),0) AS s', false)
//     //                 ->from('history')
//     //                 ->where('user_id', $ancestor_id)
//     //                 ->where('type', 'bv_volume')
//     //                 ->where('date >=', $dayStart)
//     //                 ->where('date <=', $dayEnd)
//     //                 ->where('leg', 'right')
//     //                 ->get()->row()->s;
//     //             $bv_Right_query = $this->db->last_query();

//     //             // pairs possible by ratio (e.g., 1:1 => min(left,right); 1:2 => floor(min(left/1, right/2)))
//     //             $pairs_possible = min(
//     //                 floor($bvLeft / $Lratio),
//     //                 floor($bvRight / $Rratio)
//     //             );

//     //             // already paid today?
//     //             $pairs_paid_today = (int) $this->db->select('COALESCE(SUM(pairs_count),0) AS c', false)
//     //                 ->from('history')
//     //                 ->where('user_id', $ancestor_id)
//     //                 ->where('type', 'pair_commission')
//     //                 ->where('date >=', $dayStart)
//     //                 ->where('date <=', $dayEnd)
//     //                 ->get()->row()->c;

//     //             $pairs_paid_today_query = $this->db->last_query();

//     //             // enforce daily cap
//     //             $remaining_cap = ($daily_cap > 0) ? max(0, $daily_cap - $pairs_paid_today) : $pairs_possible;
//     //             $pairs_to_pay = max(0, min($pairs_possible - $pairs_paid_today, $remaining_cap));

//     //             $this->_dbg('PAIR_BV_TODAY', [
//     //                 'ancestor' => $ancestor_id,
//     //                 'bvLeft' => $bvLeft,
//     //                 'bvRight' => $bvRight,
//     //                 'pairs_to_pay' => $pairs_to_pay,
//     //                 'pairs_possible' => $pairs_possible,
//     //                 'pairs_paid_today' => $pairs_paid_today,
//     //                 'remaining_cap' => $remaining_cap,
//     //                 'bv_left_query' => $bv_left_query,
//     //                 'bv_Right_query' => $bv_Right_query,
//     //                 '$pairs_paid_today_query' => $pairs_paid_today_query,
//     //             ]);

//     //             if ($pairs_to_pay > 0) {
//     //                 // matched BV that will be consumed
//     //                 $matched_left = $pairs_to_pay * $Lratio;
//     //                 $matched_right = $pairs_to_pay * $Rratio;

//     //                 // commission base = matched BV sum (you can choose min side only if required)
//     //                 $base_bv = ($matched_left + $matched_right); // or use min(...) * 2 for 1:1 same result

//     //                 // amount calculation
//     //                 $pair_amt = ($pair_type === 'percent')
//     //                     ? ($base_bv * ($pair_val / 100.0))
//     //                     : ($pair_val * $pairs_to_pay);

//     //                 if ($pair_amt > 0) {
//     //                     // record pair commission to receiver
//     //                     $this->_credit_history([
//     //                         'user_id' => $ancestor_id,
//     //                         'amount' => $pair_amt,
//     //                         'token_amount' => ($ctx['earn_type'] == '2' ? $pair_amt * (float) $ctx['csq_price'] : 0),
//     //                         'type' => 'pair_commission',
//     //                         'description' => $ctx['note'] . " - Binary Pair ({$pairs_to_pay} pairs, {$Lratio}:{$Rratio})",
//     //                         'invest_id' => (int) $ctx['invest_id'],
//     //                         'from_id' => (int) $ctx['buyer_id'],
//     //                         'earn_by' => (string) $ctx['earn_type'],
//     //                         'date' => $ctx['invest_date'],
//     //                         'level_type' => $this->_get_leg_relative_to($ancestor_id, (int) $ctx['buyer_id']), // where buyer sits under ancestor
//     //                         // audit extras into history columns you already have:
//     //                         'pairs_count' => $pairs_to_pay,
//     //                         'pair_ratio_used' => "{$Lratio}:{$Rratio}",
//     //                         'total_left_invest' => $matched_left,   // optional use fields
//     //                         'total_right_invest' => $matched_right,
//     //                     ]);
//     //                 }

//     //                 // (Optional) Matching Bonus over pair income → to further uplines
//     //                 $this->_matching_bonus_over_pair_income($ancestor_id, $pair_amt, $ctx);
//     //             }
//     //         }

//     //         // move to next ancestor
//     //         $row = $this->db->select('sponser AS sponsor_id')->where('id', $ancestor_id)->get('users')->row();
//     //         $ancestor_id = $row ? (int) $row->sponsor_id : 0;
//     //         $hop++;


//     //     }
//     // }



//     /* =========================================================
//      * ✅ DROP-IN REPLACEMENT: _settle_pairs_for_ancestors()
//      * Put below method inside CommissionEngine_model
//      * ========================================================= */


//     private function _settle_pairs_for_ancestors(array $ctx)
//     {
//         $this->_dbg('PAIR_START', ['buyer' => $ctx['buyer_id'], 'date' => $ctx['invest_date']]);

//         // global settings
//         $conf = $this->db->where('id', 1)->get('commission_config')->row();
//         if (!$conf || (int) $conf->binary_commission_status !== 1)
//             return;

//         // carry settings
//         $carry_enabled = $this->binarycarryengine->carryEnabled($conf);
//         $carry_mode = $this->binarycarryengine->carryMode($conf);
//         $carry_cap = $this->binarycarryengine->carryCap($conf);

//         // ratio + type
//         [$Lratio, $Rratio] = $this->binarycarryengine->parseRatio($conf->binary_pair_ratio ?? '1:1');
//         $global_pair_type = $conf->binary_pair_type ?? 'percent';

//         // start from buyer sponsor
//         $row = $this->db->select('sponser AS sponsor_id')->where('id', (int) $ctx['buyer_id'])->get('users')->row();
//         $ancestor_id = $row ? (int) $row->sponsor_id : 0;

//         [$dayStart, $dayEnd] = $this->binarycarryengine->dayWindow($ctx['invest_date']);

//         while ($ancestor_id > 0) {

//             // receiver package
//             $recv = $this->_get_user_package($ancestor_id);
//             $pair_enabled = $recv && isset($recv->pair_commission_status) ? (int) $recv->pair_commission_status : 1;
//             $pair_val = $recv && isset($recv->pair_commission) ? (float) $recv->pair_commission : 0;
//             $pair_type = $recv && isset($recv->pair_commission_type) ? $recv->pair_commission_type : $global_pair_type;
//             $daily_cap = $recv && isset($recv->daily_max_pairs) ? (int) $recv->daily_max_pairs : 0;

//             if ($pair_enabled && $pair_val > 0) {

//                 // ✅ read BV from carry table (scope-aware)
//                 if ($carry_enabled) {
//                     $carryRow = $this->binarycarryengine->getCarryRowScoped($ancestor_id, $carry_mode, $ctx['invest_date']);
//                     $bvLeft = (float) ($carryRow['left_carry'] ?? 0);
//                     $bvRight = (float) ($carryRow['right_carry'] ?? 0);

//                     // cap applied even on reading (extra safety)
//                     if ($carry_cap > 0) {
//                         $bvLeft = min($bvLeft, $carry_cap);
//                         $bvRight = min($bvRight, $carry_cap);
//                     }
//                 } else {
//                     // carry disabled => treat as 0 carry (pairs only happen from same-day volume in your old logic)
//                     $bvLeft = 0;
//                     $bvRight = 0;
//                 }

//                 $pairs_possible = (int) min(floor($bvLeft / $Lratio), floor($bvRight / $Rratio));

//                 // already paid today (for cap)
//                 $pairs_paid_today = (int) $this->db->select('COALESCE(SUM(pairs_count),0) AS c', false)
//                     ->from('history')
//                     ->where('user_id', $ancestor_id)
//                     ->where('type', 'pair_commission')
//                     ->where('date >=', $dayStart)
//                     ->where('date <=', $dayEnd)
//                     ->get()->row()->c;

//                 $remaining_cap = ($daily_cap > 0) ? max(0, $daily_cap - $pairs_paid_today) : $pairs_possible;
//                 $pairs_to_pay = max(0, min($pairs_possible, $remaining_cap));

//                 if ($pairs_to_pay > 0) {

//                     $matched_left = $pairs_to_pay * $Lratio;
//                     $matched_right = $pairs_to_pay * $Rratio;
//                     $base_bv = ($matched_left + $matched_right);

//                     $pair_amt = ($pair_type === 'percent')
//                         ? ($base_bv * ($pair_val / 100.0))
//                         : ($pair_val * $pairs_to_pay);

//                     if ($pair_amt > 0) {
//                         $this->_credit_history([
//                             'user_id' => $ancestor_id,
//                             'amount' => $pair_amt,
//                             'token_amount' => ($ctx['earn_type'] == '2' ? $pair_amt * (float) $ctx['csq_price'] : 0),
//                             'type' => 'pair_commission',
//                             'description' => $ctx['note'] . " - Binary Pair ({$pairs_to_pay} pairs, {$Lratio}:{$Rratio})",
//                             'invest_id' => (int) $ctx['invest_id'],
//                             'from_id' => (int) $ctx['buyer_id'],
//                             'earn_by' => (string) $ctx['earn_type'],
//                             'date' => $ctx['invest_date'],
//                             'level_type' => $this->_get_leg_relative_to($ancestor_id, (int) $ctx['buyer_id']),
//                             'pairs_count' => $pairs_to_pay,
//                             'pair_ratio_used' => "{$Lratio}:{$Rratio}",
//                             'total_left_invest' => $matched_left,
//                             'total_right_invest' => $matched_right,
//                         ]);

//                         // ✅ consume carry
//                         if ($carry_enabled) {
//                             $this->binarycarryengine->consumeBV($ancestor_id, $matched_left, $matched_right, $carry_mode, $ctx['invest_date']);
//                         }

//                         // matching bonus
//                         $this->_matching_bonus_over_pair_income($ancestor_id, $pair_amt, $ctx);
//                     }
//                 }
//             }

//             // next ancestor
//             $r = $this->db->select('sponser AS sponsor_id')->where('id', $ancestor_id)->get('users')->row();
//             $ancestor_id = $r ? (int) $r->sponsor_id : 0;
//         }
//     }




//     /**
//      * Matching bonus: pay percentages (package_config.matching_bonus_json) of the receiver's PAIR INCOME
//      * to that receiver's upline chain, if global matching is enabled.
//      */
//     private function _matching_bonus_over_pair_income($receiver_id, $pair_income, array $ctx)
//     {


//         if ($pair_income <= 0)
//             return;
//         $conf = $this->db->where('id', 1)->get('commission_config')->row();
//         if (!$conf || (int) $conf->matching_bonus_status !== 1)
//             return;

//         // receiver's package defines matching % levels
//         $recv_pkg = $this->_get_user_package($receiver_id);
//         if (!$recv_pkg || empty($recv_pkg->matching_bonus_json))
//             return;

//         $levels = json_decode($recv_pkg->matching_bonus_json, true);
//         if (!is_array($levels) || empty($levels))
//             return;

//         // start from receiver's sponsor and go up: Level-1 of matching = sponsor of the receiver (who earned pair)
//         $upline_id = $this->db->select('sponser AS sponsor_id')->where('id', $receiver_id)->get('users')->row();
//         $upline_id = $upline_id ? (int) $upline_id->sponsor_id : 0;


//         $this->_dbg('MB_START', [
//             'receiver' => (int) $receiver_id,
//             'pair_income' => $pair_income
//         ]);
//         $this->_dbg('MB_LEVELS', $levels);

//         $level_num = 1;
//         foreach ($levels as $pct) {
//             if ($upline_id <= 0)
//                 break;
//             $pct = (float) $pct;
//             if ($pct > 0) {
//                 $mb_amt = $pair_income * ($pct / 100.0);
//                 if ($mb_amt > 0) {
//                     $this->_credit_history([
//                         'user_id' => (int) $upline_id,
//                         'amount' => $mb_amt,
//                         'token_amount' => ($ctx['earn_type'] == '2' ? $mb_amt * (float) $ctx['csq_price'] : 0),
//                         'type' => 'matching_bonus',
//                         'description' => $ctx['note'] . " - Matching Bonus L{$level_num} (on {$receiver_id}'s pair income)",
//                         'invest_id' => (int) $ctx['invest_id'],
//                         'from_id' => (int) $receiver_id,                    // the one whose pair income we matched
//                         'earn_by' => (string) $ctx['earn_type'],
//                         'date' => $ctx['invest_date'],
//                         'level_type' => $this->_get_leg_relative_to((int) $upline_id, (int) $receiver_id), // leg of receiver vs this upline
//                     ]);
//                 }

//                 $this->_dbg('MB_PAY', [
//                     'to' => (int) $upline_id,
//                     'level' => $level_num,
//                     'pct' => $pct,
//                     'mb_amt' => $mb_amt
//                 ]);


//             }
//             // go to next upline
//             $row = $this->db->select('sponser AS sponsor_id')->where('id', $upline_id)->get('users')->row();
//             $upline_id = $row ? (int) $row->sponsor_id : 0;
//             $level_num++;
//         }
//     }


//     // Purchase the order(shop)
//     public function post_product_pv($buyer_id, $order_id)
//     {
//         if (!$buyer_id || !$order_id)
//             return;

//         // 1) Load buyer & package level config
//         $buyer = $this->db->get_where('users', ['id' => $buyer_id])->row();
//         if (!$buyer)
//             return;

//         $pkg = null;
//         $levelPerc = []; // default no distribution
//         if (!empty($buyer->package_id)) {
//             $pkg = $this->db->get_where('package_config', ['id' => (int) $buyer->package_id])->row();
//             if ($pkg && !empty($pkg->product_level_comm_json)) {
//                 $arr = json_decode($pkg->product_level_comm_json, true);
//                 if (is_array($arr))
//                     $levelPerc = $arr; // e.g., [10,5,4]
//             }
//         }

//         if (empty($levelPerc)) {
//             // No product level config — nothing to distribute.
//             return;
//         }

//         // 2) Compute total PRODUCT PV from this order
//         //    CHANGE `p.commission` below if your PV lives in another column (e.g., p.pv)
//         $this->db->select('oi.quantity, p.commission AS pv_per_unit');
//         $this->db->from('order_items oi');
//         $this->db->join('products p', 'p.id = oi.product_id', 'left');
//         $this->db->where('oi.order_id', $order_id);
//         $items = $this->db->get()->result();

//         $totalProductPV = 0.0;
//         foreach ($items as $it) {
//             $unitPV = (float) ($it->pv_per_unit ?? 0);
//             $qty = (int) ($it->quantity ?? 0);
//             if ($unitPV > 0 && $qty > 0)
//                 $totalProductPV += ($unitPV * $qty);
//         }
//         if ($totalProductPV <= 0)
//             return;

//         // 3) Resolve uplines with leg
//         $chain = $this->resolve_uplines_with_leg($buyer_id, count($levelPerc));
//         if (empty($chain))
//             return;

//         // 4) Distribute PV per level (percent of total PV)
//         $now = date('Y-m-d H:i:s');
//         $levelIdx = 0;
//         foreach ($chain as $node) {
//             if ($levelIdx >= count($levelPerc))
//                 break;

//             $pct = (float) $levelPerc[$levelIdx]; // e.g., 10 means 10%
//             if ($pct <= 0) {
//                 $levelIdx++;
//                 continue;
//             }

//             $pvForUpline = round(($totalProductPV * $pct) / 100, 4); // keep PV precision
//             if ($pvForUpline > 0) {
//                 $this->db->insert('history', [
//                     'user_id' => (int) $node['upline_id'],     // receiver
//                     'type' => 'bv_volume',         // ✅ NEW: Use "bv_volume" instead of "pv_volume"
//                     'amount' => $pvForUpline,                // PV credited
//                     'status' => 1,
//                     'date' => $now,
//                     'description' => 'Product PV Posted (' . $pvForUpline . ') from Order #' . $order_id,
//                     'coin_type' => 1,
//                     'invest_id' => null,
//                     'hash_id' => $order_id,                   // reference the order
//                     'token_value' => 0,
//                     'history_date' => $now,
//                     'level_type' => 'product',                   // you can keep 'left/right' text here if you need
//                     'level_count' => $levelIdx + 1,               // 1-based level number
//                     'from_id' => (int) $buyer_id,              // buyer who generated PV
//                     'rank_type' => null,
//                     'royality_received_by' => 0,
//                     'token_amount' => 0,
//                     'coin_id' => 1,
//                     'token_id' => 1,
//                     // Binary bookkeeping (mirrors your package BV row schema):
//                     'total_left_invest' => 0,
//                     'total_right_invest' => 0,
//                     'total_left_roi' => 0,
//                     'total_right_roi' => 0,
//                     'total_left_users' => 0,
//                     'total_right_users' => 0,
//                     'total_left_invest_ids' => 0,
//                     'total_right_invest_ids' => 0,
//                     'transaction_id' => null,
//                     'deductionFromSiteWallet' => 0,
//                     'remainingAmount' => 0,
//                     'deductionFromWallet' => 0,
//                     'pair_ratio_used' => 0,
//                     'pairs_count' => 0,
//                     'basis' => 'PV',                        // <-- DIFFERENT from package BV rows
//                     'ref_history_id' => null,
//                     'earn_by' => 'product',
//                     'leg' => $node['leg'] ?? null,        // 'left' or 'right' vs that upline
//                     'method' => 'product'                    // to separate from 'package'
//                 ]);
//             }

//             $levelIdx++;
//         }
//     }


//     public function resolve_uplines_with_leg($buyer_id, $max_levels = 20)
//     {
//         $chain = [];
//         $current_id = (int) $buyer_id;

//         $levels = 0;
//         while ($current_id > 0 && $levels < $max_levels) {
//             // Get parent of current node
//             $row = $this->db->select('parent_id')
//                 ->from('binary_placement')
//                 ->where('user_id', $current_id)
//                 ->get()->row();

//             if (!$row || !$row->parent_id) {
//                 break; // reached top
//             }

//             $upline_id = (int) $row->parent_id;

//             // Determine whether $buyer_id sits on left or right for this upline
//             $leg = $this->_get_leg_relative_to($upline_id, $buyer_id);
//             if (!$leg)
//                 $leg = null; // default if not found

//             $chain[] = [
//                 'upline_id' => $upline_id,
//                 'leg' => $leg
//             ];

//             // Climb one level up
//             $current_id = $upline_id;
//             $levels++;
//         }

//         return $chain; // array of ['upline_id'=>X,'leg'=>'left'|'right']
//     }

// }


































defined('BASEPATH') OR exit('No direct script access allowed');

class CommissionEngine_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        if (!isset($this->binarycarryengine) || !$this->binarycarryengine) {
            $this->load->library('BinaryCarryEngine', null, 'binarycarryengine');
        }
    }

    private function _dbg($tag, $payload = null)
    {
        if (is_array($payload) || is_object($payload))
            $payload = json_encode($payload);
        log_message('debug', "[CommissionEngine][$tag] {$payload}");
    }

    // =========================================================
    // PACKAGE PURCHASE: Direct + Level + Own + PV/BV + Carry + Pair + Matching
    // =========================================================
    public function process_investment(array $ctx)
    {
        $conf = $this->db->where('id', 1)->get('commission_config')->row();
        if (!$conf)
            return false;

        $pkg = $this->db->where('id', (int) $ctx['package_id'])
            ->where('status', '1')
            ->get('package_config')->row();
        if (!$pkg)
            return false;

        $get = function ($obj, $prop, $def = null) {
            return isset($obj->$prop) ? $obj->$prop : $def;
        };

        $user = $this->db->select('id, sponser AS sponsor_id')
            ->where('id', (int) $ctx['user_id'])
            ->get('users')->row();
        if (!$user)
            return false;

        // ✅ PV/BV
        $bv = (float) $get($pkg, 'bv', 0);
        $pv = (float) $get($pkg, 'pv', $bv); // if pv column not exists, pv=bv

        // toggles
        $direct_enabled = (int) $get($conf, 'direct_commission_status', 1);
        $direct_val = (float) $get($pkg, 'direct_commission', 0);
        $direct_type = $get($pkg, 'direct_commission_type', $get($conf, 'direct_commission_type', 'percent'));

        $own_enabled = (int) $get($conf, 'own_commission_status', 1);
        $own_val = (float) $get($pkg, 'own_commission', 0);

        $level_pct_arr = json_decode($get($pkg, 'level_pv_json', '[]'), true);
        if (!is_array($level_pct_arr))
            $level_pct_arr = [];

        $this->db->trans_begin();

        // ---------------- OWN ----------------
        if ($own_enabled && $own_val > 0) {
            $own_amt = ($ctx['amount'] * $own_val / 100.0);
            if ($own_amt > 0) {
                $this->_credit_history([
                    'user_id' => (int) $user->id,
                    'amount' => $own_amt,
                    'token_amount' => ($ctx['earn_type'] == '2' ? $own_amt * (float) $ctx['csq_price'] : 0),
                    'type' => 'own_commission',
                    'description' => $ctx['note'] . ' - Own',
                    'invest_id' => (int) $ctx['invest_id'],
                    'from_id' => (int) $user->id,
                    'earn_by' => (string) $ctx['earn_type'],
                    'date' => $ctx['invest_date'],
                    'level_type' => $this->_get_immediate_leg((int) $user->id),
                ]);
            }
        }

        // ---------------- DIRECT ----------------
        $paid_direct = false;
        if ($direct_enabled && $direct_val > 0 && !empty($user->sponsor_id)) {
            $direct_amt = ($direct_type === 'percent')
                ? ($ctx['amount'] * $direct_val / 100.0)
                : $direct_val;

            if ($direct_amt > 0) {
                $leg_vs_sponsor = $this->_get_leg_relative_to((int) $user->sponsor_id, (int) $user->id);
                $this->_credit_history([
                    'user_id' => (int) $user->sponsor_id,
                    'amount' => $direct_amt,
                    'token_amount' => ($ctx['earn_type'] == '2' ? $direct_amt * (float) $ctx['csq_price'] : 0),
                    'type' => 'direct_commission',
                    'description' => $ctx['note'] . ' - Direct',
                    'invest_id' => (int) $ctx['invest_id'],
                    'from_id' => (int) $user->id,
                    'earn_by' => (string) $ctx['earn_type'],
                    'date' => $ctx['invest_date'],
                    'level_type' => $leg_vs_sponsor,
                ]);
                $paid_direct = true;
            }
        }

        // ---------------- LEVEL COMMISSIONS ----------------
        $upline_id = $user->sponsor_id;
        if ($paid_direct && !empty($upline_id)) {
            $s = $this->db->select('sponser AS sponsor_id')->where('id', (int) $upline_id)->get('users')->row();
            $upline_id = $s ? $s->sponsor_id : null;
        }
        $start_idx = $paid_direct ? 1 : 0;

        for ($i = $start_idx; $i < count($level_pct_arr); $i++) {
            if (empty($upline_id))
                break;

            $level_num = $i + 1;
            $pct = (float) $level_pct_arr[$i];
            if ($pct > 0) {
                $amt = $ctx['amount'] * ($pct / 100.0);
                if ($amt > 0) {
                    $leg_vs_upline = $this->_get_leg_relative_to((int) $upline_id, (int) $user->id);
                    $this->_credit_history([
                        'user_id' => (int) $upline_id,
                        'amount' => $amt,
                        'token_amount' => ($ctx['earn_type'] == '2' ? $amt * (float) $ctx['csq_price'] : 0),
                        'type' => 'level_commission',
                        'description' => $ctx['note'] . " - Level {$level_num}",
                        'invest_id' => (int) $ctx['invest_id'],
                        'level_count' => $level_num,
                        'from_id' => (int) $user->id,
                        'earn_by' => (string) $ctx['earn_type'],
                        'date' => $ctx['invest_date'],
                        'level_type' => $leg_vs_upline,
                    ]);
                }
            }

            $s = $this->db->select('sponser AS sponsor_id')->where('id', (int) $upline_id)->get('users')->row();
            $upline_id = $s ? $s->sponsor_id : null;
        }

        // ---------------- BUYER LEDGER PV/BV ----------------
        if ($pv > 0 || $bv > 0) {

            // buyer ledger (optional but good)
            $this->_post_volume([
                'user_id' => (int) $user->id,
                'invest_id' => (int) $ctx['invest_id'],
                'pv' => (float) $pv,
                'bv' => (float) $bv,
                'source_amount' => (float) $ctx['amount'],
                'date' => $ctx['invest_date'],
            ]);

            // buyer PV history (report)
            if ($pv > 0) {
                $this->_history_volume([
                    'user_id' => (int) $user->id,
                    'from_id' => (int) $user->id,
                    'leg' => null,
                    'amount' => (float) $pv,
                    'type' => 'pv_volume',
                    'description' => $ctx['note'] . " - PV ({$pv})",
                    'invest_id' => (int) $ctx['invest_id'],
                    'earn_by' => (string) $ctx['earn_type'],
                    'date' => $ctx['invest_date'],
                    'basis' => 'PV',
                    'method' => 'package',
                ]);
            }

            // propagate BV to uplines carry
            $carry_enabled = $this->binarycarryengine->carryEnabled($conf);
            $carry_mode = $this->binarycarryengine->carryMode($conf);
            $carry_cap = $this->binarycarryengine->carryCap($conf);

            $ancestor_id = $user->sponsor_id;
            $level_hop = 1;

            while (!empty($ancestor_id)) {
                $leg = $this->_get_leg_relative_to((int) $ancestor_id, (int) $user->id);

                // audit BV history
                if ($bv > 0 && $leg) {
                    $this->_history_volume([
                        'user_id' => (int) $ancestor_id,
                        'from_id' => (int) $user->id,
                        'leg' => $leg,
                        'amount' => (float) $bv,
                        'type' => 'bv_volume',
                        'description' => $ctx['note'] . " - BV ({$bv}) from #{$user->id} [L{$level_hop}]",
                        'invest_id' => (int) $ctx['invest_id'],
                        'earn_by' => (string) $ctx['earn_type'],
                        'date' => $ctx['invest_date'],
                        'basis' => 'BV',
                        'method' => 'package',
                    ]);
                }

                // carry update
                if ($bv > 0 && $leg && $carry_enabled) {
                    $this->binarycarryengine->addBV(
                        (int) $ancestor_id,
                        (string) $leg,
                        (float) $bv,
                        (string) $carry_mode,
                        (float) $carry_cap,
                        (string) $ctx['invest_date']
                    );
                }

                $row = $this->db->select('sponser AS sponsor_id')->where('id', (int) $ancestor_id)->get('users')->row();
                $ancestor_id = $row ? $row->sponsor_id : null;
                $level_hop++;
            }
        }

        // ✅ instant pair settlement
        $this->_settle_pairs_for_ancestors([
            'buyer_id' => (int) $user->id,
            'invest_id' => (int) $ctx['invest_id'],
            'invest_date' => $ctx['invest_date'],
            'note' => $ctx['note'],
            'earn_type' => (string) $ctx['earn_type'],
            'csq_price' => (float) $ctx['csq_price'],
        ]);

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return false;
        }
        $this->db->trans_commit();
        return true;
    }

    // =========================================================
    // SHOP ORDER: PV (buyer) + BV (uplines) + instant pair settlement
    // =========================================================
    public function post_product_pv($buyer_id, $order_id)
    {
        $buyer_id = (int) $buyer_id;
        $order_id = (int) $order_id;
        if ($buyer_id <= 0 || $order_id <= 0)
            return;

        $buyer = $this->db->select('id, sponser')->where('id', $buyer_id)->get('users')->row();
        if (!$buyer)
            return;

        $conf = $this->db->where('id', 1)->get('commission_config')->row();
        if (!$conf)
            return;

        // ✅ products pv/bv points
        $this->db->select('oi.quantity, p.pv AS pv_per_unit, p.bv AS bv_per_unit');
        $this->db->from('order_items oi');
        $this->db->join('products p', 'p.id = oi.product_id', 'left');
        $this->db->where('oi.order_id', $order_id);
        $items = $this->db->get()->result();

        $totalPV = 0.0;
        $totalBV = 0.0;
        foreach ($items as $it) {
            $qty = (int) ($it->quantity ?? 0);
            $pvU = (float) ($it->pv_per_unit ?? 0);
            $bvU = (float) ($it->bv_per_unit ?? 0);
            if ($qty > 0) {
                if ($pvU > 0)
                    $totalPV += ($pvU * $qty);
                if ($bvU > 0)
                    $totalBV += ($bvU * $qty);
            }
        }
        $totalPV = round($totalPV, 4);
        $totalBV = round($totalBV, 4);

        if ($totalPV <= 0 && $totalBV <= 0)
            return;

        $now = date('Y-m-d H:i:s');

        $this->db->trans_begin();

        // buyer PV history
        if ($totalPV > 0) {
            $this->_history_volume([
                'user_id' => $buyer_id,
                'from_id' => $buyer_id,
                'leg' => null,
                'amount' => $totalPV,
                'type' => 'pv_volume',
                'description' => "Product PV ({$totalPV}) from Order #{$order_id}",
                'invest_id' => null,
                'earn_by' => 'product',
                'date' => $now,
                'basis' => 'PV',
                'method' => 'product',
            ]);
        }

        // uplines BV carry
        $carry_enabled = $this->binarycarryengine->carryEnabled($conf);
        $carry_mode = $this->binarycarryengine->carryMode($conf);
        $carry_cap = $this->binarycarryengine->carryCap($conf);

        $ancestor_id = (int) ($buyer->sponser ?? 0);
        $hop = 1;

        while ($ancestor_id > 0) {
            $leg = $this->_get_leg_relative_to($ancestor_id, $buyer_id);

            if ($totalBV > 0 && $leg) {
                // audit history
                $this->_history_volume([
                    'user_id' => $ancestor_id,
                    'from_id' => $buyer_id,
                    'leg' => $leg,
                    'amount' => $totalBV,
                    'type' => 'bv_volume',
                    'description' => "Product BV ({$totalBV}) from #{$buyer_id} Order #{$order_id} [L{$hop}]",
                    'invest_id' => null,
                    'earn_by' => 'product',
                    'date' => $now,
                    'basis' => 'BV',
                    'method' => 'product',
                ]);

                // carry update
                if ($carry_enabled) {
                    $this->binarycarryengine->addBV(
                        $ancestor_id,
                        (string) $leg,
                        (float) $totalBV,
                        (string) $carry_mode,
                        (float) $carry_cap,
                        (string) $now
                    );
                }
            }

            $row = $this->db->select('sponser')->where('id', $ancestor_id)->get('users')->row();
            $ancestor_id = $row ? (int) $row->sponser : 0;
            $hop++;
        }

        // ✅ settle pairs instantly
        $this->_settle_pairs_for_ancestors([
            'buyer_id' => $buyer_id,
            'invest_id' => null,
            'invest_date' => $now,
            'note' => "Shopping Commissions (Order #{$order_id})",
            'earn_type' => 'product',
            'csq_price' => 0,
        ]);

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return;
        }
        $this->db->trans_commit();
    }

    // =========================================================
    // HISTORY HELPERS (volume rows do NOT affect wallet)
    // =========================================================
    private function _history_volume(array $h)
    {
        $currency = currency_info();
        $token = token_info();

        $row = [
            'user_id' => (int) ($h['user_id'] ?? 0),
            'type' => (string) ($h['type'] ?? ''),
            'amount' => (float) ($h['amount'] ?? 0),
            'status' => '1',
            'date' => (string) ($h['date'] ?? date('Y-m-d H:i:s')),
            'description' => (string) ($h['description'] ?? ''),
            'coin_type' => 1,
            'invest_id' => $h['invest_id'] ?? null,
            'hash_id' => 'system',
            'token_value' => 0,
            'history_date' => (string) ($h['date'] ?? date('Y-m-d H:i:s')),
            'level_type' => $h['leg'] ?? NULL,
            'level_count' => NULL,
            'from_id' => isset($h['from_id']) ? (int) $h['from_id'] : NULL,
            'rank_type' => NULL,
            'royality_received_by' => 0,
            'token_amount' => 0,
            'coin_id' => $currency->id,
            'token_id' => $token->id,
            'pairs_count' => 0,
            'pair_ratio_used' => 0,
            'basis' => $h['basis'] ?? NULL,
            'ref_history_id' => NULL,
            'earn_by' => $h['earn_by'] ?? 'system',
            'leg' => $h['leg'] ?? NULL,
            'method' => $h['method'] ?? NULL,
        ];

        return $this->db->insert('history', $row);
    }

    private function _credit_history(array $h)
    {
        $currency = currency_info();
        $token = token_info();

        $row = [
            'user_id' => (int) $h['user_id'],
            'amount' => (float) $h['amount'],
            'token_amount' => (float) $h['token_amount'],
            'type' => (string) $h['type'],
            'history_date' => $h['date'],
            'date' => $h['date'],
            'status' => '1',
            'hash_id' => 'system',
            'invest_id' => $h['invest_id'] ?? null,
            'description' => (string) $h['description'],
            'coin_id' => $currency->id,
            'token_id' => $token->id,
            'from_id' => $h['from_id'] ?? NULL,
            'earn_by' => $h['earn_by'] ?? '1',
            'level_type' => $h['level_type'] ?? NULL,
            'leg' => $h['level_type'] ?? NULL,
            'level_count' => $h['level_count'] ?? NULL,

            'pairs_count' => $h['pairs_count'] ?? 0,
            'pair_ratio_used' => $h['pair_ratio_used'] ?? NULL,
            'total_left_invest' => $h['total_left_invest'] ?? 0,
            'total_right_invest' => $h['total_right_invest'] ?? 0,

            'coin_type' => 1,
            'rank_type' => NULL,
            'royality_received_by' => 0,
            'token_value' => 0,
            'total_left_roi' => 0,
            'total_right_roi' => 0,
            'total_left_users' => 0,
            'total_right_users' => 0,
            'total_left_invest_ids' => NULL,
            'total_right_invest_ids' => NULL,
            'transaction_id' => NULL,
            'deductionFromSiteWallet' => 0,
            'remainingAmount' => 0,
            'deductionFromWallet' => 0,
            'basis' => NULL,
            'ref_history_id' => $h['ref_history_id'] ?? NULL,
            'method' => NULL,
        ];

        return $this->db->insert('history', $row);
    }

    private function _post_volume(array $v)
    {
        return $this->db->insert('binary_volume_ledger', [
            'user_id' => (int) $v['user_id'],
            'invest_id' => $v['invest_id'] ?? null,
            'pv' => (float) $v['pv'],
            'bv' => (float) $v['bv'],
            'source_amount' => (float) $v['source_amount'],
            'created_at' => $v['date'],
        ]);
    }

    // =========================================================
    // TREE HELPERS
    // =========================================================
    private function _get_immediate_leg($user_id)
    {
        $row = $this->db->select('position')
            ->from('binary_placement')
            ->where('user_id', (int) $user_id)
            ->get()->row();
        return $row ? strtolower($row->position) : null;
    }

    private function _get_leg_relative_to($ancestor_id, $node_id)
    {
        $ancestor_id = (int) $ancestor_id;
        $current_id = (int) $node_id;

        while ($current_id > 0) {
            $row = $this->db->select('parent_id, position')
                ->from('binary_placement')
                ->where('user_id', $current_id)
                ->get()->row();
            if (!$row)
                return null;

            if ((int) $row->parent_id === $ancestor_id)
                return strtolower($row->position);
            $current_id = (int) $row->parent_id;
        }
        return null;
    }

    private function _get_user_package($user_id)
    {
        return $this->db->select('u.id, u.package_id, p.*')
            ->from('users u')
            ->join('package_config p', 'p.id = u.package_id', 'left')
            ->where('u.id', (int) $user_id)
            ->get()->row();
    }

    // // =========================================================
    // // PAIR SETTLEMENT (instant)
    // // =========================================================
    // // ✅ DROP-IN: replace your existing _settle_pairs_for_ancestors() with this one
    // // - Uses carry table for BV (binary_carry_forward)
    // // - Pays BOTH: pair_commission + binary_commission
    // // - Consumes carry immediately
    // // - Pays matching_bonus on PAIR INCOME (same as your current logic)
    // private function _settle_pairs_for_ancestors(array $ctx)
    // {
    //     $this->_dbg('PAIR_START', ['buyer' => $ctx['buyer_id'], 'date' => $ctx['invest_date']]);

    //     // global settings
    //     $conf = $this->db->where('id', 1)->get('commission_config')->row();
    //     if (!$conf || (int) ($conf->binary_commission_status ?? 0) !== 1)
    //         return;

    //     // carry settings
    //     $carry_enabled = $this->binarycarryengine->carryEnabled($conf);
    //     $carry_mode = $this->binarycarryengine->carryMode($conf);   // LIFETIME/DAILY/WEEKLY/MONTHLY
    //     $carry_cap = $this->binarycarryengine->carryCap($conf);    // float

    //     // ratio + global type fallback
    //     [$Lratio, $Rratio] = $this->binarycarryengine->parseRatio($conf->binary_pair_ratio ?? '1:1');
    //     $global_type = strtolower((string) ($conf->binary_pair_type ?? 'percent')); // percent|amount

    //     // start from buyer sponsor
    //     $row = $this->db->select('sponser AS sponsor_id')
    //         ->where('id', (int) $ctx['buyer_id'])
    //         ->get('users')->row();
    //     $ancestor_id = $row ? (int) $row->sponsor_id : 0;

    //     // day window for daily caps
    //     [$dayStart, $dayEnd] = $this->binarycarryengine->dayWindow($ctx['invest_date']);

    //     while ($ancestor_id > 0) {

    //         // receiver package
    //         $recv = $this->_get_user_package($ancestor_id);

    //         // must have pair enabled to settle anything (pairs_count depends on it)
    //         $pair_enabled = $recv && isset($recv->pair_commission_status) ? (int) $recv->pair_commission_status : 0;
    //         $pair_val = $recv && isset($recv->pair_commission) ? (float) $recv->pair_commission : 0;
    //         $pair_type = $recv && isset($recv->pair_commission_type) ? strtolower((string) $recv->pair_commission_type) : $global_type;
    //         $daily_cap = $recv && isset($recv->daily_max_pairs) ? (int) $recv->daily_max_pairs : 0;

    //         // binary commission settings (package based)
    //         $binary_val = $recv && isset($recv->binary_commission) ? (float) $recv->binary_commission : 0;
    //         $binary_type = $recv && isset($recv->binary_commission_type) ? strtolower((string) $recv->binary_commission_type) : $global_type;

    //         if ($pair_enabled && $pair_val > 0 && $carry_enabled) {

    //             // ✅ read current carry balances (scope-aware)
    //             $carryRow = $this->binarycarryengine->getCarryRowScoped($ancestor_id, $carry_mode, $ctx['invest_date']);
    //             $bvLeft = (float) ($carryRow['left_carry'] ?? 0);
    //             $bvRight = (float) ($carryRow['right_carry'] ?? 0);

    //             // cap per leg
    //             if ($carry_cap > 0) {
    //                 $bvLeft = min($bvLeft, $carry_cap);
    //                 $bvRight = min($bvRight, $carry_cap);
    //             }

    //             // how many pairs possible right now
    //             $pairs_possible = (int) min(floor($bvLeft / $Lratio), floor($bvRight / $Rratio));

    //             // already paid today (for daily cap)
    //             $pairs_paid_today = (int) $this->db->select('COALESCE(SUM(pairs_count),0) AS c', false)
    //                 ->from('history')
    //                 ->where('user_id', $ancestor_id)
    //                 ->where('type', 'pair_commission')
    //                 ->where('date >=', $dayStart)
    //                 ->where('date <=', $dayEnd)
    //                 ->get()->row()->c;

    //             // cap remaining
    //             $remaining_cap = ($daily_cap > 0) ? max(0, $daily_cap - $pairs_paid_today) : $pairs_possible;
    //             $pairs_to_pay = max(0, min($pairs_possible, $remaining_cap));

    //             $this->_dbg('PAIR_CALC', [
    //                 'ancestor' => $ancestor_id,
    //                 'bvLeft' => $bvLeft,
    //                 'bvRight' => $bvRight,
    //                 'pairs_possible' => $pairs_possible,
    //                 'pairs_paid_today' => $pairs_paid_today,
    //                 'daily_cap' => $daily_cap,
    //                 'pairs_to_pay' => $pairs_to_pay
    //             ]);

    //             if ($pairs_to_pay > 0) {

    //                 $matched_left = $pairs_to_pay * $Lratio;
    //                 $matched_right = $pairs_to_pay * $Rratio;

    //                 // base BV for commissions
    //                 $base_bv = ($matched_left + $matched_right);

    //                 // ---- 1) PAIR COMMISSION ----
    //                 $pair_amt = ($pair_type === 'percent')
    //                     ? ($base_bv * ($pair_val / 100.0))
    //                     : ($pair_val * $pairs_to_pay);

    //                 // ---- 2) BINARY COMMISSION ----
    //                 $binary_amt = 0.0;
    //                 if ($binary_val > 0) {
    //                     $binary_amt = ($binary_type === 'percent')
    //                         ? ($base_bv * ($binary_val / 100.0))
    //                         : ($binary_val * $pairs_to_pay);
    //                 }

    //                 // common audit fields
    //                 $leg_vs_ancestor = $this->_get_leg_relative_to($ancestor_id, (int) $ctx['buyer_id']);
    //                 $audit = [
    //                     'pairs_count' => $pairs_to_pay,
    //                     'pair_ratio_used' => "{$Lratio}:{$Rratio}",
    //                     'total_left_invest' => $matched_left,
    //                     'total_right_invest' => $matched_right,
    //                 ];

    //                 // insert pair_commission
    //                 if ($pair_amt > 0) {
    //                     $this->_credit_history([
    //                         'user_id' => $ancestor_id,
    //                         'amount' => $pair_amt,
    //                         'token_amount' => ($ctx['earn_type'] == '2' ? $pair_amt * (float) $ctx['csq_price'] : 0),
    //                         'type' => 'pair_commission',
    //                         'description' => $ctx['note'] . " - Pair Commission ({$pairs_to_pay} pairs, {$Lratio}:{$Rratio})",
    //                         'invest_id' => $ctx['invest_id'] ?? null,
    //                         'from_id' => (int) $ctx['buyer_id'],
    //                         'earn_by' => (string) $ctx['earn_type'],
    //                         'date' => $ctx['invest_date'],
    //                         'level_type' => $leg_vs_ancestor,
    //                     ] + $audit);

    //                     // matching bonus on pair income
    //                     $this->_matching_bonus_over_pair_income($ancestor_id, $pair_amt, $ctx);
    //                 }

    //                 // insert binary_commission
    //                 if ($binary_amt > 0) {
    //                     $this->_credit_history([
    //                         'user_id' => $ancestor_id,
    //                         'amount' => $binary_amt,
    //                         'token_amount' => ($ctx['earn_type'] == '2' ? $binary_amt * (float) $ctx['csq_price'] : 0),
    //                         'type' => 'binary_commission',
    //                         'description' => $ctx['note'] . " - Binary Commission ({$pairs_to_pay} pairs, {$Lratio}:{$Rratio})",
    //                         'invest_id' => $ctx['invest_id'] ?? null,
    //                         'from_id' => (int) $ctx['buyer_id'],
    //                         'earn_by' => (string) $ctx['earn_type'],
    //                         'date' => $ctx['invest_date'],
    //                         'level_type' => $leg_vs_ancestor,
    //                     ] + $audit);
    //                 }

    //                 // ✅ consume carry (always consume once per pairs_to_pay)
    //                 $this->binarycarryengine->consumeBV(
    //                     $ancestor_id,
    //                     $matched_left,
    //                     $matched_right,
    //                     $carry_mode,
    //                     $ctx['invest_date']
    //                 );
    //             }
    //         }

    //         // next ancestor
    //         $r = $this->db->select('sponser AS sponsor_id')->where('id', (int) $ancestor_id)->get('users')->row();
    //         $ancestor_id = $r ? (int) $r->sponsor_id : 0;
    //     }
    // }






    /**
     * =========================================================
     * SETTLE PAIRS (carry-based) + PAY pair_commission + binary_commission + matching_bonus
     * =========================================================
     * ✅ Uses binary_carry_forward as SOURCE OF TRUTH (left_carry/right_carry)
     * ✅ Pair count is based on:
     *      unitBV (rank_config.pair_value OR default 1)  + ratio (commission_config.binary_pair_ratio)
     *      1 pair consumes: (unitBV * Lratio) on LEFT and (unitBV * Rratio) on RIGHT
     * ✅ Applies daily_max_pairs from receiver PACKAGE (package_config.daily_max_pairs)
     * ✅ Consumes carry after paying
     * ✅ Prevents double payout for same day by checking (type + date)
     *
     * NOTE:
     * - If you do not use rank_config.pair_value, set $unitBV = 1 or use package bv.
     * - This function assumes carry_forward_status=1; if carry disabled, it will skip settlement.
     */
    private function _settle_pairs_for_ancestors(array $ctx)
    {
        $this->_dbg('PAIR_START', ['buyer' => $ctx['buyer_id'], 'date' => $ctx['invest_date']]);

        // ------------------ global settings ------------------
        $conf = $this->db->where('id', 1)->get('commission_config')->row();
        if (!$conf || (int) ($conf->binary_commission_status ?? 0) !== 1) {
            $this->_dbg('PAIR_SKIP', ['reason' => 'binary_commission_status disabled']);
            return;
        }

        // carry settings (if carry disabled, no settlement here)
        $carry_enabled = $this->binarycarryengine->carryEnabled($conf);
        if (!$carry_enabled) {
            $this->_dbg('PAIR_SKIP', ['reason' => 'carry_forward_status disabled']);
            return;
        }

        $carry_mode = $this->binarycarryengine->carryMode($conf);          // LIFETIME/DAILY/WEEKLY/MONTHLY
        $carry_cap = $this->binarycarryengine->carryCap($conf);           // per-leg cap (0 = no cap)

        // ratio + global type fallback
        [$Lratio, $Rratio] = $this->binarycarryengine->parseRatio($conf->binary_pair_ratio ?? '1:1');
        $global_type = strtolower((string) ($conf->binary_pair_type ?? 'percent')); // percent|amount

        // ------------------ choose unitBV (BV per 1 pair) ------------------
        // ✅ Recommended: take from user's current rank_config.pair_value
        // If you don't use ranks, keep it 1.
        $unitBV = 1.0;

        // Try rank rule (optional)
        $buyerRow = $this->db->select('rank_id')->where('id', (int) $ctx['buyer_id'])->get('users')->row();
        $rank_id = (int) ($buyerRow->rank_id ?? 0);
        if ($rank_id > 0) {
            $rank = $this->db->select('pair_value')->where('id', $rank_id)->get('rank_config')->row();
            if ($rank && (float) $rank->pair_value > 0) {
                $unitBV = (float) $rank->pair_value; // e.g., 20 BV = 1 pair
            }
        } else {
            // fallback first active rank
            $rank = $this->db->select('pair_value')->where('rank_status', 1)->order_by('rank_order', 'ASC')->get('rank_config')->row();
            if ($rank && (float) $rank->pair_value > 0) {
                $unitBV = (float) $rank->pair_value;
            }
        }

        if ($unitBV <= 0)
            $unitBV = 1.0;

        // ✅ 1 pair consumes this much BV on each leg
        $needLeftPerPair = $unitBV * (float) $Lratio;
        $needRightPerPair = $unitBV * (float) $Rratio;

        // start from buyer sponsor (ancestor chain)
        $row = $this->db->select('sponser AS sponsor_id')
            ->where('id', (int) $ctx['buyer_id'])
            ->get('users')->row();
        $ancestor_id = $row ? (int) $row->sponsor_id : 0;

        // day window (for daily cap + daily idempotence)
        [$dayStart, $dayEnd] = $this->binarycarryengine->dayWindow($ctx['invest_date']);

        while ($ancestor_id > 0) {

            // receiver package config
            $recv = $this->_get_user_package($ancestor_id);

            $pair_enabled = $recv && isset($recv->pair_commission_status) ? (int) $recv->pair_commission_status : 0;
            $pair_val = $recv && isset($recv->pair_commission) ? (float) $recv->pair_commission : 0;
            $pair_type = $recv && isset($recv->pair_commission_type) ? strtolower((string) $recv->pair_commission_type) : $global_type;
            $daily_cap = $recv && isset($recv->daily_max_pairs) ? (int) $recv->daily_max_pairs : 0;

            $binary_val = $recv && isset($recv->binary_commission) ? (float) $recv->binary_commission : 0;
            $binary_type = $recv && isset($recv->binary_commission_type) ? strtolower((string) $recv->binary_commission_type) : $global_type;

            // must have pair enabled to compute/payout pairs
            if (!($pair_enabled === 1 && $pair_val > 0)) {
                $this->_dbg('PAIR_SKIP_ANCESTOR', ['ancestor' => $ancestor_id, 'reason' => 'pair disabled or pair_val=0']);
                // move up
                $r = $this->db->select('sponser AS sponsor_id')->where('id', (int) $ancestor_id)->get('users')->row();
                $ancestor_id = $r ? (int) $r->sponsor_id : 0;
                continue;
            }

            // ------------------ read carry balances (scope-aware) ------------------
            $carryRow = $this->binarycarryengine->getCarryRowScoped($ancestor_id, $carry_mode, $ctx['invest_date']);
            $bvLeft = (float) ($carryRow['left_carry'] ?? 0);
            $bvRight = (float) ($carryRow['right_carry'] ?? 0);

            // cap per leg
            if ($carry_cap > 0) {
                $bvLeft = min($bvLeft, $carry_cap);
                $bvRight = min($bvRight, $carry_cap);
            }

            // ------------------ compute pairs possible using unitBV ------------------
            // ✅ FIX: BV/needPerPair (not BV/ratio only)
            $pairs_possible = (int) floor(min(
                $needLeftPerPair > 0 ? ($bvLeft / $needLeftPerPair) : 0,
                $needRightPerPair > 0 ? ($bvRight / $needRightPerPair) : 0
            ));

            if ($pairs_possible <= 0) {
                $this->_dbg('PAIR_ZERO', ['ancestor' => $ancestor_id, 'bvLeft' => $bvLeft, 'bvRight' => $bvRight, 'pairs_possible' => 0]);

                // move up
                $r = $this->db->select('sponser AS sponsor_id')->where('id', (int) $ancestor_id)->get('users')->row();
                $ancestor_id = $r ? (int) $r->sponsor_id : 0;
                continue;
            }

            // already paid today (for cap and to avoid duplicates per day if your function is called multiple times)
            $pairs_paid_today = (int) $this->db->select('COALESCE(SUM(pairs_count),0) AS c', false)
                ->from('history')
                ->where('user_id', $ancestor_id)
                ->where('type', 'pair_commission')
                ->where('date >=', $dayStart)
                ->where('date <=', $dayEnd)
                ->get()->row()->c;

            // apply daily max pairs from receiver package
            $remaining_cap = ($daily_cap > 0) ? max(0, $daily_cap - $pairs_paid_today) : $pairs_possible;
            $pairs_to_pay = max(0, min($pairs_possible, $remaining_cap));

            $this->_dbg('PAIR_CALC', [
                'ancestor' => $ancestor_id,
                'unitBV' => $unitBV,
                'needLeftPerPair' => $needLeftPerPair,
                'needRightPerPair' => $needRightPerPair,
                'bvLeft' => $bvLeft,
                'bvRight' => $bvRight,
                'pairs_possible' => $pairs_possible,
                'pairs_paid_today' => $pairs_paid_today,
                'daily_cap' => $daily_cap,
                'pairs_to_pay' => $pairs_to_pay,
            ]);

            if ($pairs_to_pay > 0) {

                // BV consumed for these pairs
                $consume_left = $pairs_to_pay * $needLeftPerPair;
                $consume_right = $pairs_to_pay * $needRightPerPair;

                // base volume (for % calc) – choose ONE:
                // Option A (recommended): base = min-side consumed (unitBV * pairs)
                // Option B: base = sum of both legs consumed
                $base_bv = $pairs_to_pay * $unitBV; // ✅ min/weak base (common in binary)
                // If your business wants sum base:
                // $base_bv = $consume_left + $consume_right;

                // ---- PAIR COMMISSION ----
                $pair_amt = ($pair_type === 'percent')
                    ? ($base_bv * ($pair_val / 100.0))
                    : ($pair_val * $pairs_to_pay);

                // ---- BINARY COMMISSION ----
                $binary_amt = 0.0;
                if ($binary_val > 0) {
                    $binary_amt = ($binary_type === 'percent')
                        ? ($base_bv * ($binary_val / 100.0))
                        : ($binary_val * $pairs_to_pay);
                }

                $leg_vs_ancestor = $this->_get_leg_relative_to($ancestor_id, (int) $ctx['buyer_id']);

                $audit = [
                    'pairs_count' => $pairs_to_pay,
                    'pair_ratio_used' => "{$Lratio}:{$Rratio}",
                    // store consumed BV (not USD) into existing columns for UI/debug
                    'total_left_invest' => $consume_left,
                    'total_right_invest' => $consume_right,
                ];

                // Insert pair_commission
                if ($pair_amt > 0) {
                    $this->_credit_history([
                        'user_id' => $ancestor_id,
                        'amount' => $pair_amt,
                        'token_amount' => ($ctx['earn_type'] == '2' ? $pair_amt * (float) $ctx['csq_price'] : 0),
                        'type' => 'pair_commission',
                        'description' => $ctx['note'] . " - Pair Commission ({$pairs_to_pay} pairs, UnitBV {$unitBV}, {$Lratio}:{$Rratio})",
                        'invest_id' => $ctx['invest_id'] ?? null,
                        'from_id' => (int) $ctx['buyer_id'],
                        'earn_by' => (string) $ctx['earn_type'],
                        'date' => $ctx['invest_date'],
                        'level_type' => $leg_vs_ancestor,
                    ] + $audit);

                    // matching bonus on pair income
                    $this->_matching_bonus_over_pair_income($ancestor_id, $pair_amt, $ctx);
                }

                // Insert binary_commission
                if ($binary_amt > 0) {
                    $this->_credit_history([
                        'user_id' => $ancestor_id,
                        'amount' => $binary_amt,
                        'token_amount' => ($ctx['earn_type'] == '2' ? $binary_amt * (float) $ctx['csq_price'] : 0),
                        'type' => 'binary_commission',
                        'description' => $ctx['note'] . " - Binary Commission ({$pairs_to_pay} pairs, UnitBV {$unitBV}, {$Lratio}:{$Rratio})",
                        'invest_id' => $ctx['invest_id'] ?? null,
                        'from_id' => (int) $ctx['buyer_id'],
                        'earn_by' => (string) $ctx['earn_type'],
                        'date' => $ctx['invest_date'],
                        'level_type' => $leg_vs_ancestor,
                    ] + $audit);
                }

                // ✅ consume carry ONCE
                $this->binarycarryengine->consumeBV(
                    $ancestor_id,
                    (float) $consume_left,
                    (float) $consume_right,
                    $carry_mode,
                    $ctx['invest_date']
                );
            }

            // next ancestor
            $r = $this->db->select('sponser AS sponsor_id')->where('id', (int) $ancestor_id)->get('users')->row();
            $ancestor_id = $r ? (int) $r->sponsor_id : 0;
        }
    }

    private function _matching_bonus_over_pair_income($receiver_id, $pair_income, array $ctx)
    {
        if ($pair_income <= 0)
            return;

        $conf = $this->db->where('id', 1)->get('commission_config')->row();
        if (!$conf || (int) ($conf->matching_bonus_status ?? 0) !== 1)
            return;

        $recv_pkg = $this->_get_user_package($receiver_id);
        if (!$recv_pkg || empty($recv_pkg->matching_bonus_json))
            return;

        $levels = json_decode($recv_pkg->matching_bonus_json, true);
        if (!is_array($levels) || empty($levels))
            return;

        $upline_row = $this->db->select('sponser AS sponsor_id')->where('id', (int) $receiver_id)->get('users')->row();
        $upline_id = $upline_row ? (int) $upline_row->sponsor_id : 0;

        $level_num = 1;
        foreach ($levels as $pct) {
            if ($upline_id <= 0)
                break;

            $pct = (float) $pct;
            if ($pct > 0) {
                $mb_amt = $pair_income * ($pct / 100.0);
                if ($mb_amt > 0) {
                    $this->_credit_history([
                        'user_id' => (int) $upline_id,
                        'amount' => $mb_amt,
                        'token_amount' => ($ctx['earn_type'] == '2' ? $mb_amt * (float) $ctx['csq_price'] : 0),
                        'type' => 'matching_bonus',
                        'description' => $ctx['note'] . " - Matching Bonus L{$level_num} (on {$receiver_id}'s pair income)",
                        'invest_id' => $ctx['invest_id'] ?? null,
                        'from_id' => (int) $receiver_id,
                        'earn_by' => (string) $ctx['earn_type'],
                        'date' => $ctx['invest_date'],
                        'level_type' => $this->_get_leg_relative_to((int) $upline_id, (int) $receiver_id),
                    ]);
                }
            }

            $row = $this->db->select('sponser AS sponsor_id')->where('id', $upline_id)->get('users')->row();
            $upline_id = $row ? (int) $row->sponsor_id : 0;
            $level_num++;
        }
    }
}