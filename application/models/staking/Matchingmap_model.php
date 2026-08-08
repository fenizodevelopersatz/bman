<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Matchingmap_model — read-only aggregation + audit behind the admin
 * Genealogy Tree map. Nothing here writes, credits, queues or broadcasts.
 *
 * Every matching figure comes from Binarylevelmatching_model (the engine that
 * actually pays) or from staking_matching_payouts (what it actually paid).
 * No formula, ceiling, package amount or percentage is defined here — this
 * model only assembles, labels and counts.
 */
class Matchingmap_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('staking/Binarylevelmatching_model', 'BLM');
        $this->load->model('Staking_model');
    }

    /* ------------------------------- KPIs -------------------------------- */

    /**
     * Header KPIs. Config-error count needs a live per-sponsor scan because a
     * blocked level writes no row by design — the same reason the Blocked
     * Levels panel on Distribution History recomputes rather than queries.
     */
    public function summary()
    {
        $m = $this->db->query(
            "SELECT COUNT(*) total,
                    COUNT(CASE WHEN status = '1' THEN 1 END) active
               FROM users"
        )->row_array();

        $staked = (int)($this->db->query(
            "SELECT COUNT(DISTINCT user_id) n FROM user_stakes
              WHERE status IN ('active','processing') AND maturity_date > CURDATE()"
        )->row_array()['n'] ?? 0);

        $p = $this->db->query(
            "SELECT COUNT(CASE WHEN level IS NOT NULL THEN 1 END)              levels,
                    COALESCE(SUM(matched_volume),0)                            matched,
                    COALESCE(SUM(earning_amount + staking_amount),0)           user_bonus,
                    COALESCE(SUM(admin_overflow),0)                            overflow
               FROM staking_matching_payouts"
        )->row_array();

        $pendingChain = (int)($this->db->query(
            "SELECT COUNT(*) n FROM staking_matching_payouts smp
               LEFT JOIN blockchain_payout_queue q
                      ON q.reference_type = 'staking_matching_payout' AND q.reference_id = smp.id
              WHERE (smp.earning_amount + smp.staking_amount) > 0
                AND (q.status IS NULL OR q.status IN ('PENDING','RETRY','PROCESSING'))"
        )->row_array()['n'] ?? 0);

        return [
            'total_members'    => (int)($m['total'] ?? 0),
            'active_members'   => (int)($m['active'] ?? 0),
            'staked_members'   => $staked,
            'levels_completed' => (int)($p['levels'] ?? 0),
            'total_matched'    => round((float)($p['matched'] ?? 0), 4),
            'user_bonus'       => round((float)($p['user_bonus'] ?? 0), 4),
            'admin_overflow'   => round((float)($p['overflow'] ?? 0), 4),
            'pending_onchain'  => $pendingChain,
            'config_errors'    => count($this->configErrors()),
            'generated_at'     => date('d-M-Y H:i:s'),
        ];
    }

    /** Sponsors whose next COMPLETE level cannot resolve a ceiling. */
    public function configErrors($limit = 200)
    {
        $sponsors = $this->db->query(
            "SELECT DISTINCT parent_id AS uid FROM binary_placement
              WHERE parent_id IS NOT NULL AND parent_id > 0 ORDER BY parent_id ASC"
        )->result_array();

        $out = [];
        foreach ($sponsors as $s) {
            $uid = (int)$s['uid'];
            $c = $this->BLM->sponsorCeiling($uid);
            if ($c['eligible'] || $c['status'] === 'no_stake') continue;
            $legs = $this->BLM->legVolumesByDepth($uid);
            $lvl  = $this->BLM->nextLevel($uid);
            if (!$legs || !$this->BLM->levelComplete($legs, $lvl)) continue;
            $out[] = ['user_id' => $uid, 'level' => $lvl, 'status' => $c['status'], 'detail' => $c['detail']];
            if (count($out) >= $limit) break;
        }
        return $out;
    }

    /* ------------------------------ timeline ------------------------------ */

    /** Per-level status strip for one member: paid / current / pending / error. */
    public function timeline($userId, $span = 6)
    {
        $userId = (int)$userId;
        $next   = (int)$this->BLM->nextLevel($userId);
        $legs   = $this->BLM->legVolumesByDepth($userId);
        $ceil   = $this->BLM->sponsorCeiling($userId);
        $paid   = [];
        foreach ($this->db->select('level')->where('user_id', $userId)->where('level IS NOT NULL', null, false)
                          ->get('staking_matching_payouts')->result_array() as $r) {
            $paid[(int)$r['level']] = true;
        }

        $max = max($next + 2, $span);
        $out = [];
        for ($l = 1; $l <= $max; $l++) {
            if (isset($paid[$l]))                                          $st = 'PAID';
            elseif (!$ceil['eligible'] && $ceil['status'] !== 'no_stake'
                    && $legs && $this->BLM->levelComplete($legs, $l))      $st = 'CONFIG_ERROR';
            elseif ($l === $next)                                          $st = 'CURRENT';
            else                                                           $st = 'NOT_COMPLETED';
            $out[] = ['level' => $l, 'status' => $st];
        }
        return $out;
    }

    /* ------------------------------- audit -------------------------------- */

    /**
     * "Why did this member receive / not receive?" — an ordered checklist plus
     * the engine's own result for the level.
     *
     * Every check reports a fact the engine itself uses; the verdict is never
     * re-derived from a parallel rule. A completed level reports what was
     * PAID; an open one reports the engine's projection.
     */
    public function audit($userId, $level = 0)
    {
        $userId = (int)$userId;
        $u = $this->db->select('id, username, referral_id, status')->get_where('users', ['id' => $userId])->row_array();
        if (!$u) return null;

        $next  = (int)$this->BLM->nextLevel($userId);
        $lvl   = $level > 0 ? (int)$level : $next;
        $legs  = $this->BLM->legVolumesByDepth($userId);
        $vol   = $legs ? $this->BLM->cumulativeVolume($legs, $lvl) : ['left' => 0.0, 'right' => 0.0];
        $done  = $legs ? $this->BLM->levelComplete($legs, $lvl) : false;
        $ceil  = $this->BLM->sponsorCeiling($userId);
        $lock  = (float)$this->Staking_model->lockWalletBalance($userId);
        $purch = (float)($this->db->select_sum('stake_amount', 's')->where('user_id', $userId)
                    ->get('user_stakes')->row()->s ?? 0);

        $paidRow = $this->db->where('user_id', $userId)->where('level', $lvl)
                            ->get('staking_matching_payouts')->row_array();

        $proj = $this->BLM->projectLevel($userId, $vol);

        $chk = function ($label, $pass, $note = '') {
            return ['label' => $label, 'pass' => (bool)$pass, 'note' => $note];
        };
        $checks = [
            $chk('User is active', (string)$u['status'] === '1',
                 (string)$u['status'] === '1' ? '' : 'Inactive members are skipped by the engine'),
            $chk('Has eligible staking package', $ceil['status'] !== 'no_stake',
                 $ceil['status'] === 'no_stake' ? 'No active, unmatured stake — the whole bonus goes to Admin' : ''),
            $chk('Lock Wallet volume available', $lock > 0,
                 $lock > 0 ? number_format($lock, 4) . ' BMAN eligible' : 'All stake matured or none held'),
            $chk('Level ' . $lvl . ' completed', $done,
                 $done ? '' : 'Both legs need eligible volume at depth ' . $lvl),
            $chk('Left volume available', $vol['left'] > 0, number_format($vol['left'], 4) . ' BMAN'),
            $chk('Right volume available', $vol['right'] > 0, number_format($vol['right'], 4) . ' BMAN'),
            $chk('Ceiling configuration valid', $ceil['status'] === 'ok' || $ceil['status'] === 'no_stake',
                 $ceil['status'] === 'ok' || $ceil['status'] === 'no_stake' ? '' : $ceil['detail']),
            $chk('Not already paid (no duplicate)', true,
                 $paidRow ? 'Level ' . $lvl . ' already closed — it can never be paid twice' : 'Level still open'),
            $chk('Matching bonus calculated', ($paidRow ? (float)$paidRow['raw_bonus'] : $proj['raw']) > 0,
                 ''),
        ];

        if ($paidRow) {
            $result = [
                'historical'     => true,
                'status'         => 'COMPLETED',
                'matched'        => (float)$paidRow['matched_volume'],
                'raw'            => (float)$paidRow['raw_bonus'],
                'ceiling'        => (float)$paidRow['ceiling_applied'],
                'user'           => (float)$paidRow['earning_amount'] + (float)$paidRow['staking_amount'],
                'earning'        => (float)$paidRow['earning_amount'],
                'staking'        => (float)$paidRow['staking_amount'],
                'admin'          => (float)$paidRow['admin_overflow'],
                'left'           => (float)$paidRow['left_before'],
                'right'          => (float)$paidRow['right_before'],
                'completed_at'   => $paidRow['created_at'],
                'run_ref'        => $paidRow['run_ref'],
                'payout_id'      => (int)$paidRow['id'],
            ];
            $q = $this->db->select('status, tx_hash, confirmations, required_confs')
                          ->where('reference_type', 'staking_matching_payout')
                          ->where('reference_id', (string)$paidRow['id'])
                          ->get('blockchain_payout_queue')->row_array();
            $result['chain_status'] = $q['status'] ?? null;
            $result['tx_hash']      = $q['tx_hash'] ?? null;
        } else {
            $result = [
                'historical'   => false,
                'status'       => $proj['config_error'] ? 'CONFIG_ERROR'
                                  : ($done ? ($ceil['status'] === 'no_stake' ? 'NOT_ELIGIBLE' : 'PENDING') : 'PENDING'),
                'matched'      => $proj['matched'], 'raw' => $proj['raw'], 'ceiling' => $proj['ceiling'],
                'user'         => $proj['user'], 'earning' => $proj['earning'],
                'staking'      => $proj['staking'], 'admin' => $proj['admin'],
                'left'         => $vol['left'], 'right' => $vol['right'],
                'completed_at' => null, 'run_ref' => null, 'payout_id' => null,
                'chain_status' => null, 'tx_hash' => null,
            ];
        }

        // Lifetime ceiling usage is NOT a cap (the ceiling resets per level) —
        // shown only as context, never as "remaining budget".
        $usedThis = $result['user'];
        $ceilAmt  = (float)$result['ceiling'];

        return [
            'member' => [
                'id' => $userId, 'name' => $u['username'], 'uid' => $u['referral_id'] ?: ('#' . $userId),
                'active' => (string)$u['status'] === '1',
                'lock_wallet' => round($lock, 4),
                'purchased_total' => round($purch, 4),
                'matured_total' => round(max(0, $purch - $lock), 4),
                'highest_stake' => round((float)$ceil['stake_amount'], 4),
                'ceiling' => round($ceilAmt, 4),
                'ceiling_used' => round($usedThis, 4),
                'ceiling_remaining' => round(max(0, $ceilAmt - $usedThis), 4),
                'ceiling_pct' => $ceilAmt > 0 ? round(min(100, $usedThis / $ceilAmt * 100), 2) : 0,
                'ceiling_status' => $ceil['status'],
                'ceiling_detail' => $ceil['detail'],
                'current_level' => $next,
            ],
            'level'   => $lvl,
            'checks'  => $checks,
            'result'  => $result,
            'unmatched_left'  => round(max(0, (float)$result['left'] - (float)$result['matched']), 4),
            'unmatched_right' => round(max(0, (float)$result['right'] - (float)$result['matched']), 4),
            'pct'     => [
                'total'   => $proj['pct']['total'],
                'earning' => $proj['pct']['total'] > 0 ? round($proj['pct']['earning'] / $proj['pct']['total'] * 100, 2) : 0,
                'staking' => $proj['pct']['total'] > 0 ? round($proj['pct']['staking'] / $proj['pct']['total'] * 100, 2) : 0,
            ],
        ];
    }

    /**
     * Who contributed to each leg at a level — the row-level breakdown behind
     * the same cumulative volume the engine matched on (legMembers()), so the
     * list always sums to the figure shown above it.
     */
    public function contributors($userId, $level)
    {
        $level = max(1, (int)$level);
        $rows  = $this->BLM->legMembers((int)$userId);

        $ids = array_map(function ($r) { return $r['user_id']; }, $rows);
        $names = [];
        if ($ids) {
            foreach ($this->db->select('id, username, referral_id')->where_in('id', $ids)->get('users')->result_array() as $u) {
                $names[(int)$u['id']] = ['name' => $u['username'], 'uid' => $u['referral_id'] ?: ('#' . $u['id'])];
            }
        }

        $out = ['left' => [], 'right' => [], 'left_total' => 0.0, 'right_total' => 0.0];
        foreach ($rows as $r) {
            if ($r['depth'] > $level) continue;              // cumulative 1..N only
            $side = $r['side'];
            $out[$side][] = [
                'user_id' => $r['user_id'],
                'name'    => $names[$r['user_id']]['name'] ?? ('#' . $r['user_id']),
                'uid'     => $names[$r['user_id']]['uid'] ?? '',
                'depth'   => $r['depth'],
                'volume'  => round($r['volume'], 4),
            ];
            $out[$side . '_total'] += $r['volume'];
        }
        $out['left_total']  = round($out['left_total'], 4);
        $out['right_total'] = round($out['right_total'], 4);
        $out['level'] = $level;
        return $out;
    }
}
