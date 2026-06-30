<?php
// defined('BASEPATH') OR exit('No direct script access allowed');

// class CommissionEngine_model extends CI_Model
// {
//     public function __construct()
//     {
//         parent::__construct();
//     }

//     private function _dbg($tag, $payload = null) {
//         if (is_array($payload) || is_object($payload)) {
//             $payload = json_encode($payload);
//         }
//         log_message('debug', "[CommissionEngine][$tag] {$payload}");
//     }

//    /**
//      * Process commissions after investment.
//      * Pays Direct + Level commissions (skips Level 1 if Direct is paid).
//      */
//     public function process_investment(array $ctx)
//     {
//         // 0) Global switches
//         $cmisssion_settings = $this->db->where('id', 1)->get('commission_config')->row();
//         if (!$cmisssion_settings) return false;

//         // 1) Package
//         $pkg = $this->db->where('id', (int)$ctx['package_id'])
//                         ->where('status', '1')
//                         ->get('package_config')->row();
//         if (!$pkg) return false;

//         $get = function($obj, $prop, $def=null){ return isset($obj->$prop) ? $obj->$prop : $def; };


//         $this->_dbg('CTX', $ctx);
//         $this->_dbg('PKG', ['id' => (int)$ctx['package_id']]);

//         // 2) Direct config
//         $direct_enabled = (int)$get($cmisssion_settings, 'direct_commission_status', 1);
//         $direct_val     = (float)$get($pkg, 'direct_commission', 0);
//         // Prefer package-level type; fallback to a global direct_commission_type if you keep one
//         $direct_type    = $get($pkg, 'direct_commission_type', $get($cmisssion_settings, 'direct_commission_type', 'percent')); // 'percent'|'amount'

//         // 3) Level config (array of %: level 1..N)
//         $level_pv_arr   = json_decode($get($pkg, 'level_pv_json', '[]'), true);
//         if (!is_array($level_pv_arr)) $level_pv_arr = [];

//         // 4) PV/BV
//         $bv = (float)$get($pkg, 'bv', 0);
//         $pv = 0;
//         // (float)$get($pkg, 'pv', $bv);

//         // 5) Buyer + sponsor (ALIAS the misspelled column!)
//         $user = $this->db->select('id, sponser AS sponsor_id')
//                         ->where('id', (int)$ctx['user_id'])
//                         ->get('users')->row();
//         if (!$user) return false;

//         $this->db->trans_begin();



//         // ---- OWN COMMISSION (to buyer) ----
//         // package_config has own_commission (assume PERCENT if no type column)
//         $own_val_enabled = (int)$get($cmisssion_settings, 'own_commission_status', 1);
//         $own_val = (float)$get($pkg, 'own_commission', 0);

//         if ($own_val > 0 && $own_val_enabled > 0) {
//             $own_amt = ($ctx['amount'] * $own_val / 100.0);
//             if ($own_amt > 0) {
//                 $this->_credit_history([
//                     'user_id'      => (int)$user->id,                         
//                     'amount'       => $own_amt,
//                     'token_amount' => ($ctx['earn_type']=='2' ? $own_amt * (float)$ctx['csq_price'] : 0),
//                     'type'         => 'own_commission',
//                     'description'  => $ctx['note'].' - Own',
//                     'invest_id'    => (int)$ctx['invest_id'],
//                     'from_id'      => (int)$user->id,                           
//                     'earn_by'      => (string)$ctx['earn_type'],
//                     'date'         => $ctx['invest_date'],
//                     'level_type'   => $this->_get_immediate_leg((int)$user->id)
//                 ]);
//             }
//         }

//         // ---- DIRECT COMMISSION (always preferred over Level-1) ----
//         // If enabled and sponsor exists, pay direct to sponsor
//          $paid_direct = false;
//         if ($direct_enabled && $direct_val > 0 && !empty($user->sponsor_id)) {
//             $direct_amt = ($direct_type === 'percent')
//                 ? ($ctx['amount'] * $direct_val / 100)
//                 : $direct_val;

//             if ($direct_amt > 0) {
//                 $leg_vs_sponsor = $this->_get_leg_relative_to((int)$user->sponsor_id, (int)$user->id); // left|right|null
//                 $this->_credit_history([
//                     'user_id'      => (int)$user->sponsor_id,          // receiver = sponsor
//                     'amount'       => $direct_amt,
//                     'token_amount' => ($ctx['earn_type']=='2' ? $direct_amt * (float)$ctx['csq_price'] : 0),
//                     'type'         => 'direct_commission',
//                     'description'  => $ctx['note'].' - Direct',
//                     'invest_id'    => (int)$ctx['invest_id'],
//                     'from_id'      => (int)$user->id,                  // generator = buyer
//                     'earn_by'      => (string)$ctx['earn_type'],       // '1' or '2'
//                     'date'         => $ctx['invest_date'],
//                     'level_type'   => $leg_vs_sponsor,                 // store leg relative to receiver
//                 ]);
//                 $paid_direct = true;
//             }
//         }


//         $this->_dbg('DIRECT_SETTINGS', [
//         'enabled' => $direct_enabled,
//         'val'     => $direct_val,
//         'type'    => $direct_type,
//         'sponsor' => isset($user->sponsor_id) ? (int)$user->sponsor_id : null
//         ]);



//          // ---- LEVEL COMMISSIONS ----
//         $upline_id = $user->sponsor_id;

//         // If direct was paid to sponsor, begin levels from sponsor's sponsor (Level-2)
//         if ($paid_direct && !empty($upline_id)) {
//             $s = $this->db->select('sponser AS sponsor_id')->where('id', $upline_id)->get('users')->row();
//             $upline_id = $s ? $s->sponsor_id : null;  // move to Level-2 receiver
//         }

//         // Level array indexes: 0 => Level-1, 1 => Level-2, ...
//         $start_idx = $paid_direct ? 1 : 0;

//         for ($i = $start_idx; $i < count($level_pv_arr); $i++) {
//             if (empty($upline_id)) break;

//             $level_num = $i + 1; // human-readable level number
//             $pct = (float)$level_pv_arr[$i];
//             if ($pct > 0) {
//                 $amt = $ctx['amount'] * ($pct / 100);
//                 if ($amt > 0) {
//                     $leg_vs_upline = $this->_get_leg_relative_to((int)$upline_id, (int)$user->id);
//                     $this->_credit_history([
//                         'user_id'      => (int)$upline_id,            // receiver = this upline
//                         'amount'       => $amt,
//                         'token_amount' => ($ctx['earn_type']=='2' ? $amt * (float)$ctx['csq_price'] : 0),
//                         'type'         => 'level_commission',
//                         'description'  => $ctx['note']." - Level {$level_num}",
//                         'invest_id'    => (int)$ctx['invest_id'],
//                         'level_count'  => $level_num,
//                         'from_id'      => (int)$user->id,             // generator = buyer
//                         'earn_by'      => (string)$ctx['earn_type'],
//                         'date'         => $ctx['invest_date'],
//                         'level_type'   => $leg_vs_upline,             // leg relative to receiver
//                     ]);
//                 }
//             }

//             // next upline
//             $s = $this->db->select('sponser AS sponsor_id')->where('id', $upline_id)->get('users')->row();
//             $upline_id = $s ? $s->sponsor_id : null;
//         }

//         $this->_dbg('LEVEL_SETTINGS', [
//         'levels' => $level_pv_arr
//         ]);


//         // ---- PV/BV LEDGER ----
//         // ---- PV/BV LEDGER + HISTORY LOGS ----
//         if ($pv > 0 || $bv > 0) {
//             // a) single ledger row for the buyer (used by your matcher)
//             $this->_post_volume([
//                 'user_id'       => (int)$user->id,
//                 'invest_id'     => (int)$ctx['invest_id'],
//                 'pv'            => $pv,
//                 'bv'            => $bv,
//                 'source_amount' => (float)$ctx['amount'],
//                 'date'          => $ctx['invest_date'],
//             ]);

//             // b) propagate PV/BV up the tree in history (receiver = ancestor, from_id = buyer)
//             $ancestor_id = $user->sponsor_id;
//             $level_hop   = 1;
//             while (!empty($ancestor_id)) {
//                 $leg_vs_ancestor = $this->_get_leg_relative_to((int)$ancestor_id, (int)$user->id);

//                 if ($pv > 0) {
//                     $this->_history_volume([
//                         'user_id'     => (int)$ancestor_id,             // receiver of volume
//                         'from_id'     => (int)$user->id,                // generator = buyer
//                         'leg'         => $leg_vs_ancestor,              // leg relative to receiver
//                         'amount'      => (float)$pv,
//                         'type'        => 'pv_volume',
//                         'description' => $ctx['note']." - PV Posted ({$pv}) from #{$user->id} [L{$level_hop}]",
//                         'invest_id'   => (int)$ctx['invest_id'],
//                         'earn_by'     => (string)$ctx['earn_type'],
//                         'date'        => $ctx['invest_date'],
//                         'basis'       => 'PV',
//                     ]);
//                 }

//                 if ($bv > 0) {
//                     $this->_history_volume([
//                         'user_id'     => (int)$ancestor_id,
//                         'from_id'     => (int)$user->id,
//                         'leg'         => $leg_vs_ancestor,
//                         'amount'      => (float)$bv,
//                         'type'        => 'bv_volume',
//                         'description' => $ctx['note']." - BV Posted ({$bv}) from #{$user->id} [L{$level_hop}]",
//                         'invest_id'   => (int)$ctx['invest_id'],
//                         'earn_by'     => (string)$ctx['earn_type'],
//                         'date'        => $ctx['invest_date'],
//                         'basis'       => 'BV',
//                         'method'      => 'package'
//                     ]);
//                 }

//                 // climb one level up
//                 $row = $this->db->select('sponser AS sponsor_id')->where('id', $ancestor_id)->get('users')->row();
//                 $ancestor_id = $row ? $row->sponsor_id : null;
//                 $level_hop++;
//             }

//             $this->_dbg('BINARY_PV_BV', [
//             'pv' => $pv, 'bv' => $bv
//             ]);

//         }

//         // ---- BINARY PAIR SETTLEMENT (for all ancestors) ----
//         $this->_settle_pairs_for_ancestors([
//             'buyer_id'    => (int)$user->id,              // <-- REQUIRED (not user_id)
//             'invest_id'   => (int)$ctx['invest_id'],
//             'invest_date' => $ctx['invest_date'],
//             'note'        => $ctx['note'],
//             'earn_type'   => (string)$ctx['earn_type'],   // '1' or '2'
//             'csq_price'   => (float)$ctx['csq_price'],
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
//         $token    = token_info();

//         $row = [
//             'user_id'       => (int)$h['user_id'],           // receiver
//             'type'          => (string)$h['type'],           // 'pv_volume' | 'bv_volume'
//             'amount'        => (float)$h['amount'],
//             'status'        => '1',
//             'date'          => $h['date'],
//             'description'   => (string)$h['description'],
//             'coin_type'     => 1,
//             'invest_id'     => (int)$h['invest_id'],
//             'hash_id'       => 'system',
//             'token_value'   => 0,
//             'history_date'  => $h['date'],

//             'level_type'    => isset($h['leg']) ? $h['leg'] : NULL, // left|right
//             'level_count'   => NULL,
//             'from_id'       => isset($h['from_id']) ? (int)$h['from_id'] : NULL, // generator

//             'rank_type'     => NULL,
//             'royality_received_by' => 0,
//             'token_amount'  => 0,
//             'coin_id'       => $currency->id,
//             'token_id'      => $token->id,
//             'total_left_invest'      => 0,
//             'total_right_invest'     => 0,
//             'total_left_roi'         => 0,
//             'total_right_roi'        => 0,
//             'total_left_users'       => 0,
//             'total_right_users'      => 0,
//             'total_left_invest_ids'  => NULL,
//             'total_right_invest_ids' => NULL,
//             'transaction_id'         => NULL,
//             'deductionFromSiteWallet'=> 0,
//             'remainingAmount'        => 0,
//             'deductionFromWallet'    => 0,
//             'pair_ratio_used'        => 0,
//             'pairs_count'            => 0,
//             'basis'                  => isset($h['basis']) ? (string)$h['basis'] : NULL,
//             'ref_history_id'         => NULL,
//             'earn_by'                => (string)$h['earn_by'],
//             'leg'                    => isset($h['leg']) ? $h['leg'] : NULL,
//             'method'                 =>  isset($h['method']) ? $h['method'] : NULL, 
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
//         $token    = token_info();

//         $row = [
//             'user_id'      => (int)$h['user_id'],           // receiver
//             'amount'       => (float)$h['amount'],
//             'token_amount' => (float)$h['token_amount'],
//             'type'         => (string)$h['type'],
//             'history_date' => $h['date'],
//             'date'         => $h['date'],
//             'status'       => '1',
//             'hash_id'      => 'system',
//             'invest_id'    => (int)$h['invest_id'],
//             'description'  => (string)$h['description'],
//             'coin_id'      => $currency->id,
//             'token_id'     => $token->id,
//             'from_id'      => isset($h['from_id']) ? (int)$h['from_id'] : NULL,
//             'earn_by'      => (string)$h['earn_by'],
//             'level_type'   => isset($h['level_type']) ? $h['level_type'] : NULL,
//             'leg'          => isset($h['level_type']) ? $h['level_type'] : NULL, // keep mirrored if you use both
//             'level_count'   => isset($h['level_count']) ? $h['level_count'] : NULL,

//             // optional auditing fields if you want to see pair stats in history:
//             'pairs_count'        => isset($h['pairs_count']) ? (int)$h['pairs_count'] : 0,
//             'pair_ratio_used'    => isset($h['pair_ratio_used']) ? (string)$h['pair_ratio_used'] : NULL,
//             'total_left_invest'  => isset($h['total_left_invest']) ? (float)$h['total_left_invest'] : 0,
//             'total_right_invest' => isset($h['total_right_invest']) ? (float)$h['total_right_invest'] : 0,
//         ];
//         return $this->db->insert('history', $row);
//     }


//     private function _post_volume(array $v)
//     {
//         $row = [
//             'user_id'       => (int)$v['user_id'],
//             'invest_id'     => (int)$v['invest_id'],
//             'pv'            => (float)$v['pv'],
//             'bv'            => (float)$v['bv'],
//             'source_amount' => (float)$v['source_amount'],
//             'created_at'    => $v['date'],
//         ];
//         return $this->db->insert('binary_volume_ledger', $row);
//     }


//     private function _get_immediate_leg($user_id)
//     {
//         $row = $this->db->select('position')
//                         ->from('binary_placement')
//                         ->where('user_id', (int)$user_id)
//                         ->get()->row();
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
//         $ancestor_id = (int)$ancestor_id;
//         $current_id  = (int)$node_id;

//         while ($current_id > 0) {
//             $row = $this->db->select('parent_id, position')
//                             ->from('binary_placement')
//                             ->where('user_id', $current_id)
//                             ->get()->row();
//             if (!$row) return null;

//             // If the parent of current node is the ancestor, the position here is the leg
//             if ((int)$row->parent_id === $ancestor_id) {
//                 return strtolower($row->position); // 'left'|'right'
//             }

//             // climb one level up
//             $current_id = (int)$row->parent_id;
//         }
//         return null;
//     }




//     /** Get a user's active package row (joins users.package_id -> package_config.id) */
//     private function _get_user_package($user_id) {
//         $u = $this->db->select('u.id, u.package_id, p.*')
//                     ->from('users u')
//                     ->join('package_config p', 'p.id = u.package_id', 'left')
//                     ->where('u.id', (int)$user_id)
//                     ->get()->row();
//         return $u ?: null;
//     }

//     /** Parse ratio like "1:1" or "1:2" -> [1,1] or [1,2] (fallback 1:1) */
//     private function _parse_ratio($ratio) {
//         if (!is_string($ratio) || strpos($ratio, ':') === false) return [1,1];
//         [$l,$r] = array_map('trim', explode(':', $ratio, 2));
//         $L = max(1, (int)$l); $R = max(1, (int)$r);
//         return [$L, $R];
//     }

//     /** Get today's window (for daily caps) based on a timestamp (invest_date) */
//     private function _day_window($ts) {
//         $start = date('Y-m-d 00:00:00', strtotime($ts));
//         $end   = date('Y-m-d 23:59:59', strtotime($ts));
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
//     private function _settle_pairs_for_ancestors(array $ctx)
//     {

//         $this->_dbg('PAIR_START', [
//         'buyer' => $ctx['buyer_id'],
//         'date'  => $ctx['invest_date']
//         ]);

//         // load global settings
//         $conf = $this->db->where('id', 1)->get('commission_config')->row();
//         if (!$conf || (int)$conf->binary_commission_status !== 1) return;

//         // ratio & type from global (individual receiver package can override type only)
//         [$Lratio, $Rratio] = $this->_parse_ratio($conf->binary_pair_ratio);
//         $global_pair_type  = isset($conf->binary_pair_type) ? $conf->binary_pair_type : 'percent';

//         // walk ancestors upward
//         $ancestor_id = $this->db->select('sponser AS sponsor_id')
//                                 ->where('id', (int)$ctx['buyer_id'])
//                                 ->get('users')->row();
//         $ancestor_id = $ancestor_id ? (int)$ancestor_id->sponsor_id : 0;

//         [$dayStart, $dayEnd] = $this->_day_window($ctx['invest_date']);
//         $hop = 1;


//         while ($ancestor_id > 0) {

//             // receiver's package (limits & %)
//             $recv = $this->_get_user_package($ancestor_id); // includes package fields if set
//             $pair_enabled = $recv && isset($recv->pair_commission_status) ? (int)$recv->pair_commission_status : 1;
//             $pair_val     = $recv && isset($recv->pair_commission)        ? (float)$recv->pair_commission : 0;
//             $pair_type    = $recv && isset($recv->pair_commission_type)   ? $recv->pair_commission_type   : $global_pair_type; // 'percent'|'amount'
//             $daily_cap    = $recv && isset($recv->daily_max_pairs)        ? (int)$recv->daily_max_pairs   : 0; // 0 => unlimited


//             $this->_dbg('PAIR_ANCESTOR', [
//             'ancestor'  => $ancestor_id,
//             'ratio'     => "{$Lratio}:{$Rratio}",
//             'pair_type' => $pair_type,
//             'pair_val'  => $pair_val,
//             'cap'       => $daily_cap
//             ]);


//             if ($pair_enabled && $pair_val > 0) {
//                 // compute today's available leg volumes under this ancestor (from history rows propagated earlier)
//                 // Sum BV by leg for today (you can include PV if plan uses PV for pairing; below uses BV)
//                 $bvLeft  = (float)$this->db->select('COALESCE(SUM(amount),0) AS s', false)
//                             ->from('history')
//                             ->where('user_id', $ancestor_id)
//                             ->where('type', 'bv_volume')
//                             ->where('date >=', $dayStart)
//                             ->where('date <=', $dayEnd)
//                             ->where('leg', 'left')
//                             ->get()->row()->s;

//                 $bvRight = (float)$this->db->select('COALESCE(SUM(amount),0) AS s', false)
//                             ->from('history')
//                             ->where('user_id', $ancestor_id)
//                             ->where('type', 'bv_volume')
//                             ->where('date >=', $dayStart)
//                             ->where('date <=', $dayEnd)
//                             ->where('leg', 'right')
//                             ->get()->row()->s;

//                 // pairs possible by ratio (e.g., 1:1 => min(left,right); 1:2 => floor(min(left/1, right/2)))
//                 $pairs_possible = min(
//                     floor($bvLeft  / $Lratio),
//                     floor($bvRight / $Rratio)
//                 );

//                 // already paid today?
//                 $pairs_paid_today = (int)$this->db->select('COALESCE(SUM(pairs_count),0) AS c', false)
//                     ->from('history')
//                     ->where('user_id', $ancestor_id)
//                     ->where('type', 'pair_commission')
//                     ->where('date >=', $dayStart)
//                     ->where('date <=', $dayEnd)
//                     ->get()->row()->c;

//                 // enforce daily cap
//                 $remaining_cap = ($daily_cap > 0) ? max(0, $daily_cap - $pairs_paid_today) : $pairs_possible;
//                 $pairs_to_pay  = max(0, min($pairs_possible - $pairs_paid_today, $remaining_cap));

//                 $this->_dbg('PAIR_BV_TODAY', [
//                 'ancestor' => $ancestor_id,
//                 'bvLeft'   => $bvLeft,
//                 'bvRight'  => $bvRight,
//                 'pairs_to_pay' => $pairs_to_pay,
//                 'pairs_possible' => $pairs_possible,
//                 'pairs_possible' => $pairs_paid_today,
//                 'remaining_cap' => $remaining_cap
//                 ]);

//                 if ($pairs_to_pay > 0) {
//                     // matched BV that will be consumed
//                     $matched_left  = $pairs_to_pay * $Lratio;
//                     $matched_right = $pairs_to_pay * $Rratio;

//                     // commission base = matched BV sum (you can choose min side only if required)
//                     $base_bv = ($matched_left + $matched_right); // or use min(...) * 2 for 1:1 same result

//                     // amount calculation
//                     $pair_amt = ($pair_type === 'percent')
//                         ? ($base_bv * ($pair_val / 100.0))
//                         : ($pair_val * $pairs_to_pay);

//                     if ($pair_amt > 0) {
//                         // record pair commission to receiver
//                         $this->_credit_history([
//                             'user_id'      => $ancestor_id,
//                             'amount'       => $pair_amt,
//                             'token_amount' => ($ctx['earn_type']=='2' ? $pair_amt * (float)$ctx['csq_price'] : 0),
//                             'type'         => 'pair_commission',
//                             'description'  => $ctx['note']." - Binary Pair ({$pairs_to_pay} pairs, {$Lratio}:{$Rratio})",
//                             'invest_id'    => (int)$ctx['invest_id'],
//                             'from_id'      => (int)$ctx['buyer_id'],
//                             'earn_by'      => (string)$ctx['earn_type'],
//                             'date'         => $ctx['invest_date'],
//                             'level_type'   => $this->_get_leg_relative_to($ancestor_id, (int)$ctx['buyer_id']), // where buyer sits under ancestor
//                             // audit extras into history columns you already have:
//                             'pairs_count'  => $pairs_to_pay,
//                             'pair_ratio_used' => "{$Lratio}:{$Rratio}",
//                             'total_left_invest'  => $matched_left,   // optional use fields
//                             'total_right_invest' => $matched_right,
//                         ]);
//                     }

//                     // (Optional) Matching Bonus over pair income → to further uplines
//                     $this->_matching_bonus_over_pair_income($ancestor_id, $pair_amt, $ctx);
//                 }
//             }

//             // move to next ancestor
//             $row = $this->db->select('sponser AS sponsor_id')->where('id', $ancestor_id)->get('users')->row();
//             $ancestor_id = $row ? (int)$row->sponsor_id : 0;
//             $hop++;


//         }
//     }


//     /**
//      * Matching bonus: pay percentages (package_config.matching_bonus_json) of the receiver's PAIR INCOME
//      * to that receiver's upline chain, if global matching is enabled.
//      */
//     private function _matching_bonus_over_pair_income($receiver_id, $pair_income, array $ctx)
//     {


//         if ($pair_income <= 0) return;
//         $conf = $this->db->where('id', 1)->get('commission_config')->row();
//         if (!$conf || (int)$conf->matching_bonus_status !== 1) return;

//         // receiver's package defines matching % levels
//         $recv_pkg = $this->_get_user_package($receiver_id);
//         if (!$recv_pkg || empty($recv_pkg->matching_bonus_json)) return;

//         $levels = json_decode($recv_pkg->matching_bonus_json, true);
//         if (!is_array($levels) || empty($levels)) return;

//         // start from receiver's sponsor and go up: Level-1 of matching = sponsor of the receiver (who earned pair)
//         $upline_id = $this->db->select('sponser AS sponsor_id')->where('id', $receiver_id)->get('users')->row();
//         $upline_id = $upline_id ? (int)$upline_id->sponsor_id : 0;


//         $this->_dbg('MB_START', [
//         'receiver'    => (int)$receiver_id,
//         'pair_income' => $pair_income
//         ]);
//         $this->_dbg('MB_LEVELS', $levels);

//         $level_num = 1;
//         foreach ($levels as $pct) {
//             if ($upline_id <= 0) break;
//             $pct = (float)$pct;
//             if ($pct > 0) {
//                 $mb_amt = $pair_income * ($pct / 100.0);
//                 if ($mb_amt > 0) {
//                     $this->_credit_history([
//                         'user_id'      => (int)$upline_id,
//                         'amount'       => $mb_amt,
//                         'token_amount' => ($ctx['earn_type']=='2' ? $mb_amt * (float)$ctx['csq_price'] : 0),
//                         'type'         => 'matching_bonus',
//                         'description'  => $ctx['note']." - Matching Bonus L{$level_num} (on {$receiver_id}'s pair income)",
//                         'invest_id'    => (int)$ctx['invest_id'],
//                         'from_id'      => (int)$receiver_id,                    // the one whose pair income we matched
//                         'earn_by'      => (string)$ctx['earn_type'],
//                         'date'         => $ctx['invest_date'],
//                         'level_type'   => $this->_get_leg_relative_to((int)$upline_id, (int)$receiver_id), // leg of receiver vs this upline
//                     ]);
//                 }

//                 $this->_dbg('MB_PAY', [
//                     'to'         => (int)$upline_id,
//                     'level'      => $level_num,
//                     'pct'        => $pct,
//                     'mb_amt'     => $mb_amt
//                 ]);


//             }
//             // go to next upline
//             $row = $this->db->select('sponser AS sponsor_id')->where('id', $upline_id)->get('users')->row();
//             $upline_id = $row ? (int)$row->sponsor_id : 0;
//             $level_num++;
//         }
//     }



//     public function post_product_pv($buyer_id, $order_id) {
//         if (!$buyer_id || !$order_id) return;

//         // 1) Load buyer & package level config
//         $buyer = $this->db->get_where('users', ['id' => $buyer_id])->row();
//         if (!$buyer) return;

//         $pkg = null;
//         $levelPerc = []; // default no distribution
//         if (!empty($buyer->package_id)) {
//         $pkg = $this->db->get_where('package_config', ['id' => (int)$buyer->package_id])->row();
//         if ($pkg && !empty($pkg->product_level_comm_json)) {
//             $arr = json_decode($pkg->product_level_comm_json, true);
//             if (is_array($arr)) $levelPerc = $arr; // e.g., [10,5,4]
//         }
//         }

//         if (empty($levelPerc)) {
//         // No product level config — nothing to distribute.
//         return;
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
//         $unitPV = (float)($it->pv_per_unit ?? 0);
//         $qty    = (int)($it->quantity ?? 0);
//         if ($unitPV > 0 && $qty > 0) $totalProductPV += ($unitPV * $qty);
//         }
//         if ($totalProductPV <= 0) return;

//         // 3) Resolve uplines with leg
//         $chain = $this->resolve_uplines_with_leg($buyer_id, count($levelPerc));
//         if (empty($chain)) return;

//         // 4) Distribute PV per level (percent of total PV)
//         $now = date('Y-m-d H:i:s');
//         $levelIdx = 0;
//         foreach ($chain as $node) {
//         if ($levelIdx >= count($levelPerc)) break;

//         $pct = (float)$levelPerc[$levelIdx]; // e.g., 10 means 10%
//         if ($pct <= 0) { $levelIdx++; continue; }

//         $pvForUpline = round(($totalProductPV * $pct) / 100, 4); // keep PV precision
//         if ($pvForUpline > 0) {
//             $this->db->insert('history', [
//             'user_id'              => (int)$node['upline_id'],     // receiver
//             'type'                 => 'bv_volume',
//             'amount'               => $pvForUpline,                // PV credited
//             'status'               => 1,
//             'date'                 => $now,
//             'description'          => 'Product PV Posted ('.$pvForUpline.') from Order #'.$order_id,
//             'coin_type'            => 1,
//             'invest_id'            => null,
//             'hash_id'              => $order_id,                   // reference the order
//             'token_value'          => 0,
//             'history_date'         => $now,
//             'level_type'           => 'product',                   // you can keep 'left/right' text here if you need
//             'level_count'          => $levelIdx + 1,               // 1-based level number
//             'from_id'              => (int)$buyer_id,              // buyer who generated PV
//             'rank_type'            => null,
//             'royality_received_by' => 0,
//             'token_amount'         => 0,
//             'coin_id'              => 1,
//             'token_id'             => 1,
//             // Binary bookkeeping (mirrors your package BV row schema):
//             'total_left_invest'    => 0,
//             'total_right_invest'   => 0,
//             'total_left_roi'       => 0,
//             'total_right_roi'      => 0,
//             'total_left_users'     => 0,
//             'total_right_users'    => 0,
//             'total_left_invest_ids'=> 0,
//             'total_right_invest_ids'=>0,
//             'transaction_id'       => null,
//             'deductionFromSiteWallet'=>0,
//             'remainingAmount'      => 0,
//             'deductionFromWallet'  => 0,
//             'pair_ratio_used'      => 0,
//             'pairs_count'          => 0,
//             'basis'                => 'PV',                        // <-- DIFFERENT from package BV rows
//             'ref_history_id'       => null,
//             'earn_by'              => 'product',
//             'leg'                  => $node['leg'] ?? null,        // 'left' or 'right' vs that upline
//             'method'               => 'product'                    // to separate from 'package'
//             ]);
//         }

//         $levelIdx++;
//         }
//     }


//     public function resolve_uplines_with_leg($buyer_id, $max_levels = 20)
// {
//     $chain = [];
//     $current_id = (int)$buyer_id;

//     $levels = 0;
//     while ($current_id > 0 && $levels < $max_levels) {
//         // Get parent of current node
//         $row = $this->db->select('parent_id')
//                         ->from('binary_placement')
//                         ->where('user_id', $current_id)
//                         ->get()->row();

//         if (!$row || !$row->parent_id) {
//             break; // reached top
//         }

//         $upline_id = (int)$row->parent_id;

//         // Determine whether $buyer_id sits on left or right for this upline
//         $leg = $this->_get_leg_relative_to($upline_id, $buyer_id);
//         if (!$leg) $leg = null; // default if not found

//         $chain[] = [
//             'upline_id' => $upline_id,
//             'leg'       => $leg
//         ];

//         // Climb one level up
//         $current_id = $upline_id;
//         $levels++;
//     }

//     return $chain; // array of ['upline_id'=>X,'leg'=>'left'|'right']
// }

// }

////////////////////////////////////////////////////
defined('BASEPATH') OR exit('No direct script access allowed');

class CommissionEngine_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    private function _dbg($tag, $payload = null)
    {
        if (is_array($payload) || is_object($payload)) {
            $payload = json_encode($payload);
        }
        log_message('debug', "[CommissionEngine][$tag] {$payload}");
    }

    /**
     * AFTER:
     * - Pays Own + Direct + Level
     * - Posts BV/PV volumes to history (bv_volume / pv_volume)
     * - DOES NOT pay pair commission here
     *   (pair_commission + carry forward is DAILY CRON)
     */
    public function process_investment(array $ctx)
    {
        // 0) Global switches
        $cmisssion_settings = $this->db->where('id', 1)->get('commission_config')->row();
        if (!$cmisssion_settings)
            return false;

        // 1) Package
        $pkg = $this->db->where('id', (int) $ctx['package_id'])
            ->where('status', '1')
            ->get('package_config')->row();
        if (!$pkg)
            return false;

        $get = function ($obj, $prop, $def = null) {
            return isset($obj->$prop) ? $obj->$prop : $def; };


        $this->_dbg('CTX', $ctx);
        $this->_dbg('PKG', ['id' => (int) $ctx['package_id']]);

        // 2) Direct config
        $direct_enabled = (int) $get($cmisssion_settings, 'direct_commission_status', 1);
        $direct_val = (float) $get($pkg, 'direct_commission', 0);
        // Prefer package-level type; fallback to a global direct_commission_type if you keep one
        $direct_type = $get($pkg, 'direct_commission_type', $get($cmisssion_settings, 'direct_commission_type', 'percent')); // 'percent'|'amount'

        // 3) Level config (array of %: level 1..N)
        $level_pv_arr = json_decode($get($pkg, 'level_pv_json', '[]'), true);
        if (!is_array($level_pv_arr))
            $level_pv_arr = [];

        // 4) PV/BV
        $bv = (float) $get($pkg, 'bv', 0);
        $pv = 0;

        // 5) Buyer + sponsor (ALIAS the misspelled column!)
        $user = $this->db->select('id, sponser AS sponsor_id')
            ->where('id', (int) $ctx['user_id'])
            ->get('users')->row();
        if (!$user)
            return false;

        $this->db->trans_begin();



        // ---- OWN COMMISSION (to buyer) ----
        // package_config has own_commission (assume PERCENT if no type column)
        $own_val_enabled = (int) $get($cmisssion_settings, 'own_commission_status', 1);
        $own_val = (float) $get($pkg, 'own_commission', 0);

        if ($own_val > 0 && $own_val_enabled > 0) {
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
                    'level_type' => $this->_get_immediate_leg((int) $user->id)
                ]);
            }
        }

        // ---- DIRECT COMMISSION (always preferred over Level-1) ----
        // If enabled and sponsor exists, pay direct to sponsor
        $paid_direct = false;
        if ($direct_enabled && $direct_val > 0 && !empty($user->sponsor_id)) {
            $direct_amt = ($direct_type === 'percent')
                ? ($ctx['amount'] * $direct_val / 100)
                : $direct_val;

            if ($direct_amt > 0) {
                $leg_vs_sponsor = $this->_get_leg_relative_to((int) $user->sponsor_id, (int) $user->id); // left|right|null
                $this->_credit_history([
                    'user_id' => (int) $user->sponsor_id,          // receiver = sponsor
                    'amount' => $direct_amt,
                    'token_amount' => ($ctx['earn_type'] == '2' ? $direct_amt * (float) $ctx['csq_price'] : 0),
                    'type' => 'direct_commission',
                    'description' => $ctx['note'] . ' - Direct',
                    'invest_id' => (int) $ctx['invest_id'],
                    'from_id' => (int) $user->id,                  // generator = buyer
                    'earn_by' => (string) $ctx['earn_type'],       // '1' or '2'
                    'date' => $ctx['invest_date'],
                    'level_type' => $leg_vs_sponsor,                 // store leg relative to receiver
                ]);
                $paid_direct = true;
            }
        }


        $this->_dbg('DIRECT_SETTINGS', [
            'enabled' => $direct_enabled,
            'val' => $direct_val,
            'type' => $direct_type,
            'sponsor' => isset($user->sponsor_id) ? (int) $user->sponsor_id : null
        ]);



        // ---- LEVEL COMMISSIONS ----
        $upline_id = $user->sponsor_id;

        // If direct was paid to sponsor, begin levels from sponsor's sponsor (Level-2)
        if ($paid_direct && !empty($upline_id)) {
            $s = $this->db->select('sponser AS sponsor_id')->where('id', $upline_id)->get('users')->row();
            $upline_id = $s ? $s->sponsor_id : null;  // move to Level-2 receiver
        }

        // Level array indexes: 0 => Level-1, 1 => Level-2, ...
        $start_idx = $paid_direct ? 1 : 0;

        for ($i = $start_idx; $i < count($level_pv_arr); $i++) {
            if (empty($upline_id))
                break;

            $level_num = $i + 1; // human-readable level number
            $pct = (float) $level_pv_arr[$i];
            if ($pct > 0) {
                $amt = $ctx['amount'] * ($pct / 100);
                if ($amt > 0) {
                    $leg_vs_upline = $this->_get_leg_relative_to((int) $upline_id, (int) $user->id);
                    $this->_credit_history([
                        'user_id' => (int) $upline_id,            // receiver = this upline
                        'amount' => $amt,
                        'token_amount' => ($ctx['earn_type'] == '2' ? $amt * (float) $ctx['csq_price'] : 0),
                        'type' => 'level_commission',
                        'description' => $ctx['note'] . " - Level {$level_num}",
                        'invest_id' => (int) $ctx['invest_id'],
                        'level_count' => $level_num,
                        'from_id' => (int) $user->id,             // generator = buyer
                        'earn_by' => (string) $ctx['earn_type'],
                        'date' => $ctx['invest_date'],
                        'level_type' => $leg_vs_upline,             // leg relative to receiver
                    ]);
                }
            }

            // next upline
            $s = $this->db->select('sponser AS sponsor_id')->where('id', $upline_id)->get('users')->row();
            $upline_id = $s ? $s->sponsor_id : null;
        }

        $this->_dbg('LEVEL_SETTINGS', [
            'levels' => $level_pv_arr
        ]);
        if ($pv > 0 || $bv > 0) {

            // b) propagate PV/BV up the tree in history (receiver = ancestor, from_id = buyer)
            $ancestor_id = $user->sponsor_id;
            $level_hop = 1;
            while (!empty($ancestor_id)) {
                $leg_vs_ancestor = $this->_get_leg_relative_to((int) $ancestor_id, (int) $user->id);

                if ($pv > 0) {
                    $this->_history_volume([
                        'user_id' => (int) $ancestor_id,             // receiver of volume
                        'from_id' => (int) $user->id,                // generator = buyer
                        'leg' => $leg_vs_ancestor,              // leg relative to receiver
                        'amount' => (float) $pv,
                        'type' => 'pv_volume',
                        'description' => $ctx['note'] . " - PV Posted ({$pv}) from #{$user->id} [L{$level_hop}]",
                        'invest_id' => (int) $ctx['invest_id'],
                        'earn_by' => (string) $ctx['earn_type'],
                        'date' => $ctx['invest_date'],
                        'basis' => 'PV',
                    ]);
                }

                if ($bv > 0) {
                    $this->_history_volume([
                        'user_id' => (int) $ancestor_id,
                        'from_id' => (int) $user->id,
                        'leg' => $leg_vs_ancestor,
                        'amount' => (float) $bv,
                        'type' => 'bv_volume',
                        'description' => $ctx['note'] . " - BV Posted ({$bv}) from #{$user->id} [L{$level_hop}]",
                        'invest_id' => (int) $ctx['invest_id'],
                        'earn_by' => (string) $ctx['earn_type'],
                        'date' => $ctx['invest_date'],
                        'basis' => 'BV',
                        'method' => 'package'
                    ]);
                }

                // climb one level up
                $row = $this->db->select('sponser AS sponsor_id')->where('id', $ancestor_id)->get('users')->row();
                $ancestor_id = $row ? $row->sponsor_id : null;
                $level_hop++;
            }

            $this->_dbg('BINARY_PV_BV', [
                'pv' => $pv,
                'bv' => $bv
            ]);

        }

        // ✅ AFTER: NO PAIRING HERE (Cron handles)
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return false;
        }
        $this->db->trans_commit();
        return true;
    }

    /**
     * Write a PV/BV audit row into `history`.
     * amount = PV or BV units (not currency). token_amount kept 0.
     * `basis` helps distinguish PV vs BV in reports.
     */
    private function _history_volume(array $h)
    {
        $currency = currency_info();
        $token = token_info();

        $row = [
            'user_id' => (int) $h['user_id'],           // receiver
            'type' => (string) $h['type'],           // 'pv_volume' | 'bv_volume'
            'amount' => (float) $h['amount'],
            'status' => '1',
            'date' => $h['date'],
            'description' => (string) $h['description'],
            'coin_type' => 1,
            'invest_id' => (int) $h['invest_id'],
            'hash_id' => 'system',
            'token_value' => 0,
            'history_date' => $h['date'],

            'level_type' => isset($h['leg']) ? $h['leg'] : NULL, // left|right
            'level_count' => NULL,
            'from_id' => isset($h['from_id']) ? (int) $h['from_id'] : NULL, // generator

            'rank_type' => NULL,
            'royality_received_by' => 0,
            'token_amount' => 0,
            'coin_id' => $currency->id,
            'token_id' => $token->id,
            'total_left_invest' => 0,
            'total_right_invest' => 0,
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
            'pair_ratio_used' => 0,
            'pairs_count' => 0,
            'basis' => isset($h['basis']) ? (string) $h['basis'] : NULL,
            'ref_history_id' => NULL,
            'earn_by' => (string) $h['earn_by'],
            'leg' => isset($h['leg']) ? $h['leg'] : NULL,
            'method' => isset($h['method']) ? $h['method'] : NULL,
        ];

        return $this->db->insert('history', $row);
    }

    private function _credit_history(array $h)
    {
        $currency = currency_info();
        $token = token_info();

        $row = [
            'user_id' => (int) $h['user_id'],           // receiver
            'amount' => (float) $h['amount'],
            'token_amount' => (float) $h['token_amount'],
            'type' => (string) $h['type'],
            'history_date' => $h['date'],
            'date' => $h['date'],
            'status' => '1',
            'hash_id' => 'system',
            'invest_id' => (int) $h['invest_id'],
            'description' => (string) $h['description'],
            'coin_id' => $currency->id,
            'token_id' => $token->id,
            'from_id' => isset($h['from_id']) ? (int) $h['from_id'] : NULL,
            'earn_by' => (string) $h['earn_by'],
            'level_type' => isset($h['level_type']) ? $h['level_type'] : NULL,
            'leg' => isset($h['level_type']) ? $h['level_type'] : NULL,
            'level_count' => isset($h['level_count']) ? $h['level_count'] : NULL,
        ];
        return $this->db->insert('history', $row);
    }

    private function _get_immediate_leg($user_id)
    {
        $row = $this->db->select('position')
            ->from('binary_placement')
            ->where('user_id', (int) $user_id)
            ->get()->row();
        return $row ? strtolower($row->position) : null; // 'left'|'right'|null
    }

    /**
     * Return leg side of $node_id relative to a specific ancestor ($ancestor_id).
     * Walks up parents until it reaches $ancestor_id, and returns the FIRST step's position
     * (i.e., whether the node sits in the ancestor's left or right subtree).
     *
     * Example use: what leg is BUYER relative to SPONSOR or higher uplines?
     */
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

            // If the parent of current node is the ancestor, the position here is the leg
            if ((int) $row->parent_id === $ancestor_id) {
                return strtolower($row->position); // 'left'|'right'
            }

            // climb one level up
            $current_id = (int) $row->parent_id;
        }
        return null;
    }




    /** Get a user's active package row (joins users.package_id -> package_config.id) */
    private function _get_user_package($user_id)
    {
        $u = $this->db->select('u.id, u.package_id, p.*')
            ->from('users u')
            ->join('package_config p', 'p.id = u.package_id', 'left')
            ->where('u.id', (int) $user_id)
            ->get()->row();
        return $u ?: null;
    }

    /** Parse ratio like "1:1" or "1:2" -> [1,1] or [1,2] (fallback 1:1) */
    private function _parse_ratio($ratio)
    {
        if (!is_string($ratio) || strpos($ratio, ':') === false)
            return [1, 1];
        [$l, $r] = array_map('trim', explode(':', $ratio, 2));
        $L = max(1, (int) $l);
        $R = max(1, (int) $r);
        return [$L, $R];
    }

    /** Get today's window (for daily caps) based on a timestamp (invest_date) */
    private function _day_window($ts)
    {
        $start = date('Y-m-d 00:00:00', strtotime($ts));
        $end = date('Y-m-d 23:59:59', strtotime($ts));
        return [$start, $end];
    }


    /**
     * For each ancestor of buyer, compute pairs using posted volumes, honor daily caps,
     * pay pair commission, optionally pay matching bonus to further uplines.
     *
     * Uses:
     * - commission_config.binary_commission_status (enable/disable binary payout)
     * - commission_config.binary_pair_ratio (e.g. "1:1")
     * - commission_config.binary_pair_type (percent|amount) [fallback if receiver package lacks type]
     * - receiver's package: pair_commission_status, pair_commission, pair_commission_type, daily_max_pairs
     * - receiver's package for matching bonus: matching_bonus_json (as percentages over pair income)
     */
    private function _settle_pairs_for_ancestors(array $ctx)
    {

        $this->_dbg('PAIR_START', [
            'buyer' => $ctx['buyer_id'],
            'date' => $ctx['invest_date']
        ]);

        // load global settings
        $conf = $this->db->where('id', 1)->get('commission_config')->row();
        if (!$conf || (int) $conf->binary_commission_status !== 1)
            return;

        // ratio & type from global (individual receiver package can override type only)
        [$Lratio, $Rratio] = $this->_parse_ratio($conf->binary_pair_ratio);
        $global_pair_type = isset($conf->binary_pair_type) ? $conf->binary_pair_type : 'percent';

        // walk ancestors upward
        $ancestor_id = $this->db->select('sponser AS sponsor_id')
            ->where('id', (int) $ctx['buyer_id'])
            ->get('users')->row();
        $ancestor_id = $ancestor_id ? (int) $ancestor_id->sponsor_id : 0;

        [$dayStart, $dayEnd] = $this->_day_window($ctx['invest_date']);
        $hop = 1;


        while ($ancestor_id > 0) {

            // receiver's package (limits & %)
            $recv = $this->_get_user_package($ancestor_id); // includes package fields if set
            $pair_enabled = $recv && isset($recv->pair_commission_status) ? (int) $recv->pair_commission_status : 1;
            $pair_val = $recv && isset($recv->pair_commission) ? (float) $recv->pair_commission : 0;
            $pair_type = $recv && isset($recv->pair_commission_type) ? $recv->pair_commission_type : $global_pair_type; // 'percent'|'amount'
            $daily_cap = $recv && isset($recv->daily_max_pairs) ? (int) $recv->daily_max_pairs : 0; // 0 => unlimited


            $this->_dbg('PAIR_ANCESTOR', [
                'ancestor' => $ancestor_id,
                'ratio' => "{$Lratio}:{$Rratio}",
                'pair_type' => $pair_type,
                'pair_val' => $pair_val,
                'cap' => $daily_cap
            ]);


            if ($pair_enabled && $pair_val > 0) {
                // compute today's available leg volumes under this ancestor (from history rows propagated earlier)
                // Sum BV by leg for today (you can include PV if plan uses PV for pairing; below uses BV)
                $bvLeft = (float) $this->db->select('COALESCE(SUM(amount),0) AS s', false)
                    ->from('history')
                    ->where('user_id', $ancestor_id)
                    ->where('type', 'bv_volume')
                    ->where('date >=', $dayStart)
                    ->where('date <=', $dayEnd)
                    ->where('leg', 'left')
                    ->get()->row()->s;

                $bvRight = (float) $this->db->select('COALESCE(SUM(amount),0) AS s', false)
                    ->from('history')
                    ->where('user_id', $ancestor_id)
                    ->where('type', 'bv_volume')
                    ->where('date >=', $dayStart)
                    ->where('date <=', $dayEnd)
                    ->where('leg', 'right')
                    ->get()->row()->s;

                // pairs possible by ratio (e.g., 1:1 => min(left,right); 1:2 => floor(min(left/1, right/2)))
                $pairs_possible = min(
                    floor($bvLeft / $Lratio),
                    floor($bvRight / $Rratio)
                );

                // already paid today?
                $pairs_paid_today = (int) $this->db->select('COALESCE(SUM(pairs_count),0) AS c', false)
                    ->from('history')
                    ->where('user_id', $ancestor_id)
                    ->where('type', 'pair_commission')
                    ->where('date >=', $dayStart)
                    ->where('date <=', $dayEnd)
                    ->get()->row()->c;

                // enforce daily cap
                $remaining_cap = ($daily_cap > 0) ? max(0, $daily_cap - $pairs_paid_today) : $pairs_possible;
                $pairs_to_pay = max(0, min($pairs_possible - $pairs_paid_today, $remaining_cap));

                $this->_dbg('PAIR_BV_TODAY', [
                    'ancestor' => $ancestor_id,
                    'bvLeft' => $bvLeft,
                    'bvRight' => $bvRight,
                    'pairs_to_pay' => $pairs_to_pay,
                    'pairs_possible' => $pairs_possible,
                    'pairs_possible' => $pairs_paid_today,
                    'remaining_cap' => $remaining_cap
                ]);

                if ($pairs_to_pay > 0) {
                    // matched BV that will be consumed
                    $matched_left = $pairs_to_pay * $Lratio;
                    $matched_right = $pairs_to_pay * $Rratio;

                    // commission base = matched BV sum (you can choose min side only if required)
                    $base_bv = ($matched_left + $matched_right); // or use min(...) * 2 for 1:1 same result

                    // amount calculation
                    $pair_amt = ($pair_type === 'percent')
                        ? ($base_bv * ($pair_val / 100.0))
                        : ($pair_val * $pairs_to_pay);

                    if ($pair_amt > 0) {
                        // record pair commission to receiver
                        $this->_credit_history([
                            'user_id' => $ancestor_id,
                            'amount' => $pair_amt,
                            'token_amount' => ($ctx['earn_type'] == '2' ? $pair_amt * (float) $ctx['csq_price'] : 0),
                            'type' => 'pair_commission',
                            'description' => $ctx['note'] . " - Binary Pair ({$pairs_to_pay} pairs, {$Lratio}:{$Rratio})",
                            'invest_id' => (int) $ctx['invest_id'],
                            'from_id' => (int) $ctx['buyer_id'],
                            'earn_by' => (string) $ctx['earn_type'],
                            'date' => $ctx['invest_date'],
                            'level_type' => $this->_get_leg_relative_to($ancestor_id, (int) $ctx['buyer_id']), // where buyer sits under ancestor
                            // audit extras into history columns you already have:
                            'pairs_count' => $pairs_to_pay,
                            'pair_ratio_used' => "{$Lratio}:{$Rratio}",
                            'total_left_invest' => $matched_left,   // optional use fields
                            'total_right_invest' => $matched_right,
                        ]);
                    }

                    // (Optional) Matching Bonus over pair income → to further uplines
                    $this->_matching_bonus_over_pair_income($ancestor_id, $pair_amt, $ctx);
                }
            }

            // move to next ancestor
            $row = $this->db->select('sponser AS sponsor_id')->where('id', $ancestor_id)->get('users')->row();
            $ancestor_id = $row ? (int) $row->sponsor_id : 0;
            $hop++;


        }
    }


    /**
     * Matching bonus: pay percentages (package_config.matching_bonus_json) of the receiver's PAIR INCOME
     * to that receiver's upline chain, if global matching is enabled.
     */
    private function _matching_bonus_over_pair_income($receiver_id, $pair_income, array $ctx)
    {


        if ($pair_income <= 0)
            return;
        $conf = $this->db->where('id', 1)->get('commission_config')->row();
        if (!$conf || (int) $conf->matching_bonus_status !== 1)
            return;

        // receiver's package defines matching % levels
        $recv_pkg = $this->_get_user_package($receiver_id);
        if (!$recv_pkg || empty($recv_pkg->matching_bonus_json))
            return;

        $levels = json_decode($recv_pkg->matching_bonus_json, true);
        if (!is_array($levels) || empty($levels))
            return;

        // start from receiver's sponsor and go up: Level-1 of matching = sponsor of the receiver (who earned pair)
        $upline_id = $this->db->select('sponser AS sponsor_id')->where('id', $receiver_id)->get('users')->row();
        $upline_id = $upline_id ? (int) $upline_id->sponsor_id : 0;


        $this->_dbg('MB_START', [
            'receiver' => (int) $receiver_id,
            'pair_income' => $pair_income
        ]);
        $this->_dbg('MB_LEVELS', $levels);

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
                        'invest_id' => (int) $ctx['invest_id'],
                        'from_id' => (int) $receiver_id,                    // the one whose pair income we matched
                        'earn_by' => (string) $ctx['earn_type'],
                        'date' => $ctx['invest_date'],
                        'level_type' => $this->_get_leg_relative_to((int) $upline_id, (int) $receiver_id), // leg of receiver vs this upline
                    ]);
                }

                $this->_dbg('MB_PAY', [
                    'to' => (int) $upline_id,
                    'level' => $level_num,
                    'pct' => $pct,
                    'mb_amt' => $mb_amt
                ]);


            }
            // go to next upline
            $row = $this->db->select('sponser AS sponsor_id')->where('id', $upline_id)->get('users')->row();
            $upline_id = $row ? (int) $row->sponsor_id : 0;
            $level_num++;
        }
    }



    public function post_product_pv($buyer_id, $order_id)
    {
        if (!$buyer_id || !$order_id)
            return;

        // 1) Load buyer & package level config
        $buyer = $this->db->get_where('users', ['id' => $buyer_id])->row();
        if (!$buyer)
            return;

        $pkg = null;
        $levelPerc = []; // default no distribution
        if (!empty($buyer->package_id)) {
            $pkg = $this->db->get_where('package_config', ['id' => (int) $buyer->package_id])->row();
            if ($pkg && !empty($pkg->product_level_comm_json)) {
                $arr = json_decode($pkg->product_level_comm_json, true);
                if (is_array($arr))
                    $levelPerc = $arr; // e.g., [10,5,4]
            }
        }

        if (empty($levelPerc)) {
            // No product level config — nothing to distribute.
            return;
        }

        // 2) Compute total PRODUCT PV from this order
        //    CHANGE `p.commission` below if your PV lives in another column (e.g., p.pv)
        $this->db->select('oi.quantity, p.commission AS pv_per_unit');
        $this->db->from('order_items oi');
        $this->db->join('products p', 'p.id = oi.product_id', 'left');
        $this->db->where('oi.order_id', $order_id);
        $items = $this->db->get()->result();

        $totalProductPV = 0.0;
        foreach ($items as $it) {
            $unitPV = (float) ($it->pv_per_unit ?? 0);
            $qty = (int) ($it->quantity ?? 0);
            if ($unitPV > 0 && $qty > 0)
                $totalProductPV += ($unitPV * $qty);
        }
        if ($totalProductPV <= 0)
            return;

        // 3) Resolve uplines with leg
        $chain = $this->resolve_uplines_with_leg($buyer_id, count($levelPerc));
        if (empty($chain))
            return;

        // 4) Distribute PV per level (percent of total PV)
        $now = date('Y-m-d H:i:s');
        $levelIdx = 0;
        foreach ($chain as $node) {
            if ($levelIdx >= count($levelPerc))
                break;

            $pct = (float) $levelPerc[$levelIdx]; // e.g., 10 means 10%
            if ($pct <= 0) {
                $levelIdx++;
                continue;
            }

            $pvForUpline = round(($totalProductPV * $pct) / 100, 4); // keep PV precision
            if ($pvForUpline > 0) {
                $this->db->insert('history', [
                    'user_id' => (int) $node['upline_id'],     // receiver
                    'type' => 'bv_volume',
                    'amount' => $pvForUpline,                // PV credited
                    'status' => 1,
                    'date' => $now,
                    'description' => 'Product PV Posted (' . $pvForUpline . ') from Order #' . $order_id,
                    'coin_type' => 1,
                    'invest_id' => null,
                    'hash_id' => $order_id,                   // reference the order
                    'token_value' => 0,
                    'history_date' => $now,
                    'level_type' => 'product',                   // you can keep 'left/right' text here if you need
                    'level_count' => $levelIdx + 1,               // 1-based level number
                    'from_id' => (int) $buyer_id,              // buyer who generated PV
                    'rank_type' => null,
                    'royality_received_by' => 0,
                    'token_amount' => 0,
                    'coin_id' => 1,
                    'token_id' => 1,
                    // Binary bookkeeping (mirrors your package BV row schema):
                    'total_left_invest' => 0,
                    'total_right_invest' => 0,
                    'total_left_roi' => 0,
                    'total_right_roi' => 0,
                    'total_left_users' => 0,
                    'total_right_users' => 0,
                    'total_left_invest_ids' => 0,
                    'total_right_invest_ids' => 0,
                    'transaction_id' => null,
                    'deductionFromSiteWallet' => 0,
                    'remainingAmount' => 0,
                    'deductionFromWallet' => 0,
                    'pair_ratio_used' => 0,
                    'pairs_count' => 0,
                    'basis' => 'PV',                        // <-- DIFFERENT from package BV rows
                    'ref_history_id' => null,
                    'earn_by' => 'product',
                    'leg' => $node['leg'] ?? null,        // 'left' or 'right' vs that upline
                    'method' => 'product'                    // to separate from 'package'
                ]);
            }

            $levelIdx++;
        }
    }


    public function resolve_uplines_with_leg($buyer_id, $max_levels = 20)
    {
        $chain = [];
        $current_id = (int) $buyer_id;

        $levels = 0;
        while ($current_id > 0 && $levels < $max_levels) {
            // Get parent of current node
            $row = $this->db->select('parent_id')
                ->from('binary_placement')
                ->where('user_id', $current_id)
                ->get()->row();

            if (!$row || !$row->parent_id) {
                break; // reached top
            }

            $upline_id = (int) $row->parent_id;

            // Determine whether $buyer_id sits on left or right for this upline
            $leg = $this->_get_leg_relative_to($upline_id, $buyer_id);
            if (!$leg)
                $leg = null; // default if not found

            $chain[] = [
                'upline_id' => $upline_id,
                'leg' => $leg
            ];

            // Climb one level up
            $current_id = $upline_id;
            $levels++;
        }

        return $chain; // array of ['upline_id'=>X,'leg'=>'left'|'right']
    }
}