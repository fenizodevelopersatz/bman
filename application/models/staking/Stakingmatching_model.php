<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Stakingmatching_model — Staking Binary Matching Bonus + rank-volume processor.
 * ---------------------------------------------------------------------------
 * Runs AFTER stakes are purchased (each purchase drops a row in
 * binary_volume_ledger with processed=0). This engine, in two passes:
 *
 *  1) propagate(): walks each un-processed stake's BV up the binary_placement
 *     tree, adding it to every upline's LEFT/RIGHT leg — into
 *       - binary_carry            (REDUCIBLE — consumed by matching payouts)
 *       - staking_group_volume    (CUMULATIVE — rank-volume, never reduced)
 *     then marks the ledger row processed=1 (idempotent, no double count).
 *
 *  2) payMatching(): for every user whose two legs both carry volume, matches
 *     min(left,right) and pays matching_total_percent (default 10%), split per
 *     staking_bonus_settings into the Earning wallet (8%) and Staking wallet
 *     (2%). The matched volume is subtracted from both carries (carry forward).
 *
 * Money moves through Walletledger_model (double-entry, audited). Everything is
 * transactional. This is the ONLY place binary matching income is created.
 */
class Stakingmatching_model extends CI_Model
{
    private $maxDepth = 200; // safety bound for the upline walk

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Walletledger_model', 'L');
        $this->load->model('staking/Ceilingwallet_model', 'CW');
    }

    /* --------------------------- ceiling helpers -------------------------- */

    /**
     * The user's package earning ceiling = sum of group_ceiling across their
     * ACTIVE staking packages (staking_packages.group_ceiling — unchanged).
     * Returns 0.0 when the user has no active ceiling configured, which the
     * caller treats as "no cap" so existing matching payouts are never broken.
     */
    public function userCeiling($userId)
    {
        $row = $this->db->select_sum('sp.group_ceiling', 'ceil')
                        ->from('user_stakes us')
                        ->join('staking_packages sp', 'sp.id = us.package_id', 'inner')
                        ->where('us.user_id', (int)$userId)
                        ->where('us.status', 'active')
                        ->get()->row_array();
        return (float)($row['ceil'] ?? 0);
    }

    /** Binary matching income already PAID to the user (counts toward ceiling). */
    public function matchingPaidToDate($userId)
    {
        $row = $this->db->select('COALESCE(SUM(earning_amount + staking_amount),0) AS paid', false)
                        ->where('user_id', (int)$userId)
                        ->get('staking_matching_payouts')->row_array();
        return (float)($row['paid'] ?? 0);
    }

    /** Full run: propagate new volume, then pay matching. Returns a summary. */
    public function run($opts = [])
    {
        $ref = !empty($opts['run_ref']) ? $opts['run_ref']
             : 'MB-'.date('Ymd-His').'-'.strtoupper(substr(bin2hex(random_bytes(3)),0,6));
        $propagated = $this->propagate();
        $paid       = $this->payMatching($ref, $opts);
        return array_merge(['run_ref' => $ref, 'propagated' => $propagated], $paid);
    }

    /* --------------------------- pass 1: volume --------------------------- */

    /** Propagate every un-processed stake's BV up the binary tree. */
    public function propagate()
    {
        $rows = $this->db->where('processed', 0)->order_by('id','ASC')
                         ->get('binary_volume_ledger')->result_array();
        $count = 0;
        foreach ($rows as $r) {
            $this->db->trans_begin();
            $this->_walkUp((int)$r['user_id'], (float)$r['bv']);
            $this->db->where('id', $r['id'])->update('binary_volume_ledger', ['processed' => 1]);
            if ($this->db->trans_status() === false) { $this->db->trans_rollback(); continue; }
            $this->db->trans_commit();
            $count++;
        }
        return $count;
    }

    /** Add $bv to each ancestor's leg (the side the source user sits on). */
    private function _walkUp($sourceUserId, $bv)
    {
        if ($bv <= 0) return;
        $cur = $sourceUserId;
        for ($d = 0; $d < $this->maxDepth; $d++) {
            $pl = $this->db->select('parent_id, position')
                           ->get_where('binary_placement', ['user_id' => $cur])->row_array();
            if (!$pl || empty($pl['parent_id'])) break;
            $parent = (int)$pl['parent_id'];
            $side   = $pl['position'] === 'right' ? 'right' : 'left';
            $this->_addCarry($parent, $side, $bv);
            $this->_addGroupVolume($parent, $side, $bv);
            $cur = $parent;
        }
    }

    private function _addCarry($userId, $side, $bv)
    {
        $col = $side === 'right' ? 'right_carry' : 'left_carry';
        $this->db->query(
            "INSERT INTO binary_carry (user_id, `$col`) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE `$col` = `$col` + VALUES(`$col`)",
            [(int)$userId, $bv]
        );
    }

    private function _addGroupVolume($userId, $side, $bv)
    {
        $col = $side === 'right' ? 'right_volume' : 'left_volume';
        $this->db->query(
            "INSERT INTO staking_group_volume (user_id, `$col`) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE `$col` = `$col` + VALUES(`$col`)",
            [(int)$userId, $bv]
        );
    }

    /* -------------------------- pass 2: matching -------------------------- */

    /** Match min(left,right) per user and pay the split matching bonus. */
    public function payMatching($runRef, $opts = [])
    {
        $s = $this->db->get_where('staking_bonus_settings', ['id' => 1])->row_array();
        $totalPct   = $s ? (float)$s['matching_total_percent']   : 10.0;
        $earningPct = $s ? (float)$s['matching_earning_percent'] : 8.0;
        $stakingPct = $s ? (float)$s['matching_staking_percent'] : 2.0;
        $minMatch   = isset($opts['min_match']) ? (float)$opts['min_match'] : 0.0;

        $rows = $this->db->where('left_carry >', 0)->where('right_carry >', 0)
                         ->get('binary_carry')->result_array();

        $users = 0; $totalMatched = 0.0; $totalEarning = 0.0; $totalStaking = 0.0; $totalCeiling = 0.0;
        foreach ($rows as $c) {
            $uid   = (int)$c['user_id'];
            $left  = (float)$c['left_carry'];
            $right = (float)$c['right_carry'];
            $match = min($left, $right);
            if ($match <= 0 || $match < $minMatch) continue;

            // pay only to active users (skip but still flush carry for inactive?)
            $u = $this->db->select('status')->get_where('users', ['id' => $uid])->row_array();
            if (!$u || (string)$u['status'] !== '1') continue;

            $earnAmt = round($match * $earningPct / 100, 4);
            $stkAmt  = round($match * $stakingPct / 100, 4);
            $bonus   = round($earnAmt + $stkAmt, 4);

            // ---- Ceiling cap ----------------------------------------------
            // Cap the user's binary income at their package earning ceiling
            // (staking_packages.group_ceiling). Only applies when a positive
            // ceiling is configured — otherwise pay full (existing behaviour,
            // never broken). Excess is diverted to the system Ceiling Wallet.
            $ceiling = $this->userCeiling($uid);
            $eligibleRatio = 1.0; $excess = 0.0;
            if ($ceiling > 0) {
                $paid      = $this->matchingPaidToDate($uid);
                $remaining = max(0.0, round($ceiling - $paid, 4));
                if ($bonus > $remaining) {
                    $excess        = round($bonus - $remaining, 4);
                    $eligibleRatio = $bonus > 0 ? ($remaining / $bonus) : 0.0;
                }
            }
            // Proportionally split the payable part across earning (8) / staking (2).
            $payEarn = round($earnAmt * $eligibleRatio, 4);
            $payStk  = round($stkAmt  * $eligibleRatio, 4);

            $this->db->trans_begin();
            if ($payEarn > 0) {
                list($ok1) = $this->L->credit($uid, 'earning', $payEarn, 'binary_matching', [
                    'reference_id' => $runRef,
                    'description'  => 'Binary matching '.$earningPct.'% on '.number_format($match).' matched BV',
                ]);
                if (!$ok1) { $this->db->trans_rollback(); continue; }
            }
            if ($payStk > 0) {
                list($ok2) = $this->L->credit($uid, 'staking', $payStk, 'binary_matching', [
                    'reference_id' => $runRef,
                    'description'  => 'Binary matching '.$stakingPct.'% on '.number_format($match).' matched BV',
                ]);
                if (!$ok2) { $this->db->trans_rollback(); continue; }
            }

            // Divert the excess above the ceiling into the system Ceiling Wallet.
            if ($excess > 0) {
                list($okC) = $this->CW->hold($uid, $excess, [
                    'source_wallet'  => 'earning+staking',
                    'matched_volume' => $match,
                    'reference_type' => 'binary_matching',
                    'reference_id'   => $runRef,
                    'description'    => 'Excess over package ceiling ('.number_format($ceiling).') held',
                ]);
                if (!$okC) { $this->db->trans_rollback(); continue; }
            }

            // carry forward: subtract the matched volume from both legs
            $this->db->query(
                "UPDATE binary_carry SET left_carry = left_carry - ?, right_carry = right_carry - ?,
                        last_flush_at = ? WHERE user_id = ?",
                [$match, $match, date('Y-m-d'), $uid]
            );

            // Log the payout: earning_amount/staking_amount reflect what was
            // actually PAID (so matchingPaidToDate stays consistent with ceiling).
            $this->db->insert('staking_matching_payouts', [
                'user_id' => $uid, 'matched_volume' => $match, 'total_percent' => $totalPct,
                'earning_amount' => $payEarn, 'staking_amount' => $payStk,
                'left_before' => $left, 'right_before' => $right, 'run_ref' => $runRef,
            ]);

            if ($this->db->trans_status() === false) { $this->db->trans_rollback(); continue; }
            $this->db->trans_commit();

            $users++; $totalMatched += $match; $totalEarning += $payEarn; $totalStaking += $payStk;
            $totalCeiling = ($totalCeiling ?? 0) + $excess;
        }

        return [
            'paid_users'     => $users,
            'matched_volume' => round($totalMatched, 4),
            'earning_paid'   => round($totalEarning, 4),
            'staking_paid'   => round($totalStaking, 4),
            'ceiling_held'   => round($totalCeiling, 4),
        ];
    }

    /* ------------------------------- reads -------------------------------- */

    public function groupVolume($userId)
    {
        return $this->db->get_where('staking_group_volume', ['user_id' => (int)$userId])->row_array()
            ?: ['user_id' => (int)$userId, 'left_volume' => 0, 'right_volume' => 0];
    }

    public function payouts($limit = 200)
    {
        return $this->db->order_by('id','DESC')->limit((int)$limit)
                        ->get('staking_matching_payouts')->result_array();
    }
}
