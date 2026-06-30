<?php
// /**
//  * ✅ SINGLE SOURCE CODE (CI3) to implement:
//  * - Carry Forward Status (ON/OFF)
//  * - Carry Forward Mode (LIFETIME / DAILY / WEEKLY / MONTHLY)
//  * - Carry Forward Cap (BV)
//  * - Daily Maximum Pairs (per package)
//  * - Pair settlement using carry (NOT "today BV only")
//  *
//  * HOW TO USE:
//  * 1) Save this file as: application/libraries/BinaryCarryEngine.php
//  * 2) In CommissionEngine_model:
//  *      - $this->load->library('BinaryCarryEngine');
//  *      - When posting BV to each ancestor => call $this->binarycarryengine->addBV(...)
//  *      - Replace your _settle_pairs_for_ancestors() with the one below (it uses carry table)
//  * 3) Optional cron:
//  *      - Call BinaryCarryEngine->cronResetCarry() daily (recommended if DAILY/WEEKLY/MONTHLY)
//  *
//  * REQUIRED TABLES:
//  * - commission_config(id=1, carry_forward_status, carry_forward_mode, carry_forward_cap, binary_pair_ratio, binary_commission_status, binary_pair_type, matching_bonus_status)
//  * - binary_carry_forward(user_id, left_carry, right_carry, scope_key, updated_at)
//  * - history (bv_volume rows with leg left/right, pair_commission rows with pairs_count, total_left_invest, total_right_invest)
//  */

// defined('BASEPATH') OR exit('No direct script access allowed');

// class BinaryCarryEngine
// {
//     /** @var CI_Controller */
//     private $CI;

//     public function __construct()
//     {
//         $this->CI =& get_instance();
//     }

//     private function _dbg($tag, $payload = null)
//     {
//         if (is_array($payload) || is_object($payload)) {
//             $payload = json_encode($payload);
//         }
//         log_message('debug', "[CommissionEngine][$tag] {$payload}");
//     }


//     /* =========================================================
//      * ✅ CONFIG HELPERS
//      * ========================================================= */

//     public function getConfig()
//     {
//         return $this->CI->db->where('id', 1)->get('commission_config')->row();
//     }

//     public function carryEnabled($conf): bool
//     {
//         return $conf && (int) ($conf->carry_forward_status ?? 0) === 1;
//     }

//     public function carryMode($conf): string
//     {
//         $m = strtoupper(trim((string) ($conf->carry_forward_mode ?? 'LIFETIME')));
//         if (!in_array($m, ['LIFETIME', 'DAILY', 'WEEKLY', 'MONTHLY'], true))
//             $m = 'LIFETIME';
//         return $m;
//     }

//     public function carryCap($conf): float
//     {
//         return (float) ($conf->carry_forward_cap ?? 0);
//     }

//     public function parseRatio($ratio): array
//     {
//         if (!is_string($ratio) || strpos($ratio, ':') === false)
//             return [1, 1];
//         [$l, $r] = array_map('trim', explode(':', $ratio, 2));
//         $L = max(1, (int) $l);
//         $R = max(1, (int) $r);
//         return [$L, $R];
//     }

//     public function scopeKey(string $mode, ?string $ts = null): string
//     {
//         $mode = strtoupper($mode);
//         $t = $ts ? strtotime($ts) : time();

//         if ($mode === 'DAILY')
//             return date('Y-m-d', $t);
//         if ($mode === 'WEEKLY')
//             return date('o-\WW', $t); // ISO week key
//         if ($mode === 'MONTHLY')
//             return date('Y-m', $t);
//         return 'lifetime';
//     }

//     public function dayWindow(string $ts): array
//     {
//         $start = date('Y-m-d 00:00:00', strtotime($ts));
//         $end = date('Y-m-d 23:59:59', strtotime($ts));
//         return [$start, $end];
//     }

//     /* =========================================================
//      * ✅ CARRY TABLE HELPERS
//      * ========================================================= */

//     public function getOrCreateCarryRow(int $user_id): array
//     {
//         $row = $this->CI->db->get_where('binary_carry_forward', ['user_id' => $user_id])->row_array();
//         $this->_dbg('BINARY_CARRY_GET_OR_CREATE', [
//             'user_id' => $user_id,
//             'row' => $row,
//             'query' => $this->CI->db->last_query()
//         ]);

//         if ($row)
//             return $row;

//         $this->CI->db->insert('binary_carry_forward', [
//             'user_id' => $user_id,
//             'left_carry' => 0,
//             'right_carry' => 0,
//             'scope_key' => 'lifetime',
//             'updated_at' => date('Y-m-d H:i:s'),
//         ]);

//         $this->_dbg('BINARY_CARRY_INSERT', [
//             'user_id' => $user_id,
//             'row' => $row,
//             'query' => $this->CI->db->last_query()
//         ]);

//         return $this->CI->db->get_where('binary_carry_forward', ['user_id' => $user_id])->row_array();
//     }

//     public function updateCarryRow(int $user_id, float $left, float $right, string $scope_key): void
//     {
//         $this->CI->db->update('binary_carry_forward', [
//             'left_carry' => $left,
//             'right_carry' => $right,
//             'scope_key' => $scope_key,
//             'updated_at' => date('Y-m-d H:i:s'),
//         ], ['user_id' => $user_id]);

//         $this->_dbg('BINARY_CARRY_UPDATE', [
//             'user_id' => $user_id,
//             'left' => $left,
//             'right' => $right,
//             'scope_key' => $scope_key,
//             'query' => $this->CI->db->last_query()
//         ]);
//     }

//     /**
//      * ✅ Ensure carry scope is correct for mode.
//      * If mode is DAILY/WEEKLY/MONTHLY and scope changes => reset.
//      * If LIFETIME => keep scope_key = lifetime.
//      */
//     public function getCarryRowScoped(int $user_id, string $mode, string $ts_for_scope): array
//     {
//         $row = $this->getOrCreateCarryRow($user_id);
//         $mode = strtoupper($mode);
//         $currentKey = $this->scopeKey($mode, $ts_for_scope);

//         if ($mode === 'LIFETIME') {
//             if (($row['scope_key'] ?? '') !== 'lifetime') {
//                 $this->updateCarryRow($user_id, (float) $row['left_carry'], (float) $row['right_carry'], 'lifetime');
//                 $row = $this->getOrCreateCarryRow($user_id);
//             }
//             return $row;
//         }

//         // DAILY/WEEKLY/MONTHLY => reset when scope changes
//         if (($row['scope_key'] ?? '') !== $currentKey) {
//             $this->updateCarryRow($user_id, 0, 0, $currentKey);
//             $row = $this->getOrCreateCarryRow($user_id);
//         }

//         $this->_dbg('BINARY_CARRY_GET', [
//             'user_id' => $user_id,
//             'mode' => $mode,
//             'ts_for_scope' => $ts_for_scope,
//             'currentKey' => $currentKey,
//             'row' => $row,
//             'query' => $this->CI->db->last_query()
//         ]);
//         return $row;
//     }

//     /**
//      * ✅ Add BV into carry (cap per leg).
//      */
//     public function addBV(int $user_id, string $leg, float $bv, string $mode, float $cap, string $ts_for_scope): array
//     {
//         $leg = strtolower($leg);
//         if ($leg !== 'left' && $leg !== 'right')
//             return ['left' => 0, 'right' => 0];

//         $row = $this->getCarryRowScoped($user_id, $mode, $ts_for_scope);

//         $left = (float) $row['left_carry'];
//         $right = (float) $row['right_carry'];

//         if ($leg === 'left')
//             $left += $bv;
//         if ($leg === 'right')
//             $right += $bv;

//         // cap per leg
//         if ($cap > 0) {
//             $left = min($left, $cap);
//             $right = min($right, $cap);
//         }

//         $this->updateCarryRow($user_id, $left, $right, (string) $row['scope_key']);
//         return ['left' => $left, 'right' => $right];
//     }

//     /**
//      * ✅ Consume BV from carry after paying pairs.
//      */
//     public function consumeBV(int $user_id, float $consume_left, float $consume_right, string $mode, string $ts_for_scope): array
//     {
//         $row = $this->getCarryRowScoped($user_id, $mode, $ts_for_scope);

//         $left = max(0, (float) $row['left_carry'] - $consume_left);
//         $right = max(0, (float) $row['right_carry'] - $consume_right);

//         $this->updateCarryRow($user_id, $left, $right, (string) $row['scope_key']);
//         return ['left' => $left, 'right' => $right];
//     }

//     /**
//      * ✅ OPTIONAL CRON:
//      * If mode is DAILY/WEEKLY/MONTHLY and you want carry to reset even when no purchases happen,
//      * run this daily (or hourly).
//      */
//     public function cronResetCarry(): array
//     {
//         $conf = $this->getConfig();
//         if (!$this->carryEnabled($conf)) {
//             return ['ok' => true, 'message' => 'Carry disabled. Nothing to reset.'];
//         }

//         $mode = $this->carryMode($conf);
//         if ($mode === 'LIFETIME') {
//             return ['ok' => true, 'message' => 'Lifetime mode. No reset required.'];
//         }

//         $currentKey = $this->scopeKey($mode, date('Y-m-d H:i:s'));

//         // reset only rows with different scope_key
//         $this->CI->db->where('scope_key !=', $currentKey)->update('binary_carry_forward', [
//             'left_carry' => 0,
//             'right_carry' => 0,
//             'scope_key' => $currentKey,
//             'updated_at' => date('Y-m-d H:i:s')
//         ]);

//         return ['ok' => true, 'message' => "Carry reset applied for {$mode} scope {$currentKey}"];
//     }
// }



// /* =========================================================
//  * ✅ OPTIONAL CRON CONTROLLER METHOD (copy into any controller)
//  * URL: /cron/carry_reset
//  * ========================================================= */

// /*
// public function carry_reset()
// {
//     $this->load->library('BinaryCarryEngine');
//     $res = $this->binarycarryengine->cronResetCarry();
//     echo json_encode($res);
// }
// */














defined('BASEPATH') OR exit('No direct script access allowed');

class BinaryCarryEngine
{
    private $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    public function carryEnabled($conf): bool
    {
        return $conf && (int)($conf->carry_forward_status ?? 0) === 1;
    }

    public function carryMode($conf): string
    {
        $m = strtoupper(trim((string)($conf->carry_forward_mode ?? 'LIFETIME')));
        if (!in_array($m, ['LIFETIME','DAILY','WEEKLY','MONTHLY'], true)) $m = 'LIFETIME';
        return $m;
    }

    public function carryCap($conf): float
    {
        return (float)($conf->carry_forward_cap ?? 0);
    }

    public function parseRatio($ratio): array
    {
        if (!is_string($ratio) || strpos($ratio, ':') === false) return [1, 1];
        [$l, $r] = array_map('trim', explode(':', $ratio, 2));
        $L = max(1, (int)$l);
        $R = max(1, (int)$r);
        return [$L, $R];
    }

    public function dayWindow(string $ts): array
    {
        $start = date('Y-m-d 00:00:00', strtotime($ts));
        $end   = date('Y-m-d 23:59:59', strtotime($ts));
        return [$start, $end];
    }

    public function scopeKey(string $mode, ?string $ts = null): string
    {
        $mode = strtoupper($mode);
        $t = $ts ? strtotime($ts) : time();

        if ($mode === 'DAILY')   return date('Y-m-d', $t);
        if ($mode === 'WEEKLY')  return date('o-\WW', $t);
        if ($mode === 'MONTHLY') return date('Y-m', $t);

        return 'lifetime';
    }

    public function getOrCreateCarryRow(int $user_id): array
    {
        $row = $this->CI->db->get_where('binary_carry_forward', ['user_id' => $user_id])->row_array();
        if ($row) return $row;

        $this->CI->db->insert('binary_carry_forward', [
            'user_id' => $user_id,
            'left_carry' => 0,
            'right_carry' => 0,
            'scope_key' => 'lifetime',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->CI->db->get_where('binary_carry_forward', ['user_id' => $user_id])->row_array();
    }

    public function updateCarryRow(int $user_id, float $left, float $right, string $scope_key): void
    {
        $this->CI->db->update('binary_carry_forward', [
            'left_carry' => $left,
            'right_carry' => $right,
            'scope_key' => $scope_key,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['user_id' => $user_id]);
    }

    public function getCarryRowScoped(int $user_id, string $mode, string $ts_for_scope): array
    {
        $row = $this->getOrCreateCarryRow($user_id);

        $mode = strtoupper($mode);
        $currentKey = $this->scopeKey($mode, $ts_for_scope);

        if ($mode === 'LIFETIME') {
            if (($row['scope_key'] ?? '') !== 'lifetime') {
                $this->updateCarryRow($user_id, (float)$row['left_carry'], (float)$row['right_carry'], 'lifetime');
                $row = $this->getOrCreateCarryRow($user_id);
            }
            return $row;
        }

        if (($row['scope_key'] ?? '') !== $currentKey) {
            $this->updateCarryRow($user_id, 0, 0, $currentKey);
            $row = $this->getOrCreateCarryRow($user_id);
        }

        return $row;
    }

    public function addBV(int $user_id, string $leg, float $bv, string $mode, float $cap, string $ts_for_scope): array
    {
        $leg = strtolower($leg);
        if ($leg !== 'left' && $leg !== 'right') return ['left' => 0, 'right' => 0];

        $row = $this->getCarryRowScoped($user_id, $mode, $ts_for_scope);
        $left  = (float)($row['left_carry'] ?? 0);
        $right = (float)($row['right_carry'] ?? 0);

        if ($leg === 'left')  $left += $bv;
        if ($leg === 'right') $right += $bv;

        if ($cap > 0) {
            $left  = min($left, $cap);
            $right = min($right, $cap);
        }

        $this->updateCarryRow($user_id, $left, $right, (string)($row['scope_key'] ?? 'lifetime'));
        return ['left' => $left, 'right' => $right];
    }

    public function consumeBV(int $user_id, float $consume_left, float $consume_right, string $mode, string $ts_for_scope): array
    {
        $row = $this->getCarryRowScoped($user_id, $mode, $ts_for_scope);

        $left  = max(0, (float)($row['left_carry'] ?? 0) - $consume_left);
        $right = max(0, (float)($row['right_carry'] ?? 0) - $consume_right);

        $this->updateCarryRow($user_id, $left, $right, (string)($row['scope_key'] ?? 'lifetime'));
        return ['left' => $left, 'right' => $right];
    }

    public function cronResetCarry(): array
    {
        $conf = $this->CI->db->where('id', 1)->get('commission_config')->row();
        if (!$this->carryEnabled($conf)) return ['ok' => true, 'message' => 'Carry disabled'];

        $mode = $this->carryMode($conf);
        if ($mode === 'LIFETIME') return ['ok' => true, 'message' => 'Lifetime mode'];

        $currentKey = $this->scopeKey($mode, date('Y-m-d H:i:s'));

        $this->CI->db->where('scope_key !=', $currentKey)->update('binary_carry_forward', [
            'left_carry' => 0,
            'right_carry' => 0,
            'scope_key' => $currentKey,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return ['ok' => true, 'message' => "Carry reset for {$mode} scope {$currentKey}"];
    }
}