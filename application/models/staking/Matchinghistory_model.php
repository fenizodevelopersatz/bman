<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Matchinghistory_model — the binary matching HISTORICAL ledger.
 * ---------------------------------------------------------------------------
 * One record per completed (user_id, level). Strictly read-only: nothing here
 * writes, credits, enqueues or broadcasts anything.
 *
 * THE RULE THIS MODEL EXISTS TO ENFORCE:
 * a completed level is reported EXACTLY as it was paid, never recomputed from
 * the genealogy tree as it stands today. Every money figure — left/right leg
 * volume, matched volume, raw bonus, the ceiling that applied, the 8/2 split,
 * the admin overflow — is read from the frozen columns on
 * staking_matching_payouts, written inside the transaction that closed the
 * level. The tree keeps moving (people stake, stakes mature, ceilings get
 * edited); history must not move with it.
 *
 * Concretely: `ceiling_applied` is the cap that was actually enforced. A JOIN
 * to staking_packages here supplies the package NAME only, for display; if the
 * admin has since edited that package's ceiling, detail() reports the drift
 * explicitly rather than quietly showing today's number as if it were history.
 *
 * The live/current picture belongs to the genealogy tree and the Blocked
 * Levels diagnostic — deliberately not this model.
 */
class Matchinghistory_model extends CI_Model
{
    /** Statuses that mean "the on-chain leg has not settled yet". */
    private $pendingChain = ['PENDING', 'RETRY', 'PROCESSING'];

    /**
     * Shared WHERE for rows() and summary() so the cards can never describe a
     * different set of records than the table below them.
     */
    private function _applyFilters($f)
    {
        if (!empty($f['user_id'])) $this->db->where('smp.user_id', (int)$f['user_id']);
        if (!empty($f['level']))   $this->db->where('smp.level', (int)$f['level']);
        if (!empty($f['from']))    $this->db->where('smp.created_at >=', $f['from'] . ' 00:00:00');
        if (!empty($f['to']))      $this->db->where('smp.created_at <=', $f['to'] . ' 23:59:59');

        if (!empty($f['q'])) {
            $q = $this->db->escape_like_str($f['q']);
            $this->db->where("(u.username LIKE '%{$q}%' OR u.referral_id LIKE '%{$q}%' OR u.email LIKE '%{$q}%')", null, false);
        }

        switch ($f['status'] ?? '') {
            case 'paid':      $this->db->where('(smp.earning_amount + smp.staking_amount) >', 0); break;
            case 'overflow':  $this->db->where('smp.admin_overflow >', 0); break;
            case 'forfeited': $this->db->where('smp.sponsor_eligible', 0); break;
            case 'pending':   // credited internally, on-chain leg not settled
                $this->db->where('(smp.earning_amount + smp.staking_amount) >', 0)
                         ->where("(q.status IS NULL OR q.status IN ('" . implode("','", $this->pendingChain) . "'))", null, false);
                break;
        }

        switch ($f['chain'] ?? '') {
            case 'queued':    $this->db->where("(q.status IS NULL OR q.status IN ('PENDING','RETRY'))", null, false); break;
            case 'sent':      $this->db->where('q.status', 'PROCESSING'); break;
            case 'confirmed': $this->db->where('q.status', 'CONFIRMED'); break;
            case 'failed':    $this->db->where('q.status', 'FAILED'); break;
        }
    }

    private function _baseFrom()
    {
        $this->db->from('staking_matching_payouts smp')
                 ->join('users u', 'u.id = smp.user_id', 'left')
                 ->join('blockchain_payout_queue q',
                        "q.reference_type = 'staking_matching_payout' AND q.reference_id = smp.id", 'left');
    }

    /** Historical ledger rows for the current filter. */
    public function rows($f = [])
    {
        $this->db->select(
            'smp.id, smp.user_id, smp.level, smp.created_at, smp.run_ref, '
          . 'smp.left_before, smp.right_before, smp.matched_volume, smp.total_percent, '
          . 'smp.raw_bonus, smp.ceiling_applied, smp.admin_overflow, smp.sponsor_eligible, '
          . 'smp.earning_amount, smp.staking_amount, smp.highest_package_id, '
          . 'u.username, u.referral_id, u.email, u.profile_img, u.image, '
          . 'sp.name AS package_name, sp.stake_amount AS package_stake, '
          . 'q.id AS queue_id, q.status AS chain_status, q.tx_hash, q.confirmations, '
          . 'q.required_confs, q.last_error AS chain_error, q.amount AS chain_amount'
        );
        $this->_baseFrom();
        $this->db->join('staking_packages sp', 'sp.id = smp.highest_package_id', 'left');
        $this->_applyFilters($f);
        $this->db->order_by('smp.id', 'DESC')
                 ->limit((int)($f['limit'] ?? 300), (int)($f['offset'] ?? 0));
        return $this->db->get()->result_array();
    }

    /** The seven summary cards — same filter set as rows(). */
    public function summary($f = [])
    {
        $this->db->select(
            'COUNT(*) AS levels, '
          . 'COALESCE(SUM(smp.matched_volume),0) AS matched, '
          . 'COALESCE(SUM(smp.earning_amount + smp.staking_amount),0) AS user_bonus, '
          . 'COALESCE(SUM(smp.earning_amount),0) AS earning, '
          . 'COALESCE(SUM(smp.staking_amount),0) AS staking, '
          . 'COALESCE(SUM(smp.admin_overflow),0) AS overflow, '
          . 'COALESCE(SUM(smp.raw_bonus),0) AS raw_bonus, '
          . "COUNT(CASE WHEN (smp.earning_amount + smp.staking_amount) > 0 "
          . "      AND (q.status IS NULL OR q.status IN ('" . implode("','", $this->pendingChain) . "')) THEN 1 END) AS pending_chain, "
          . 'COUNT(CASE WHEN smp.sponsor_eligible = 0 THEN 1 END) AS forfeited', false);
        $this->_baseFrom();
        $this->_applyFilters($f);
        $r = $this->db->get()->row_array() ?: [];

        return [
            'levels'        => (int)($r['levels'] ?? 0),
            'matched'       => (float)($r['matched'] ?? 0),
            'user_bonus'    => (float)($r['user_bonus'] ?? 0),
            'earning'       => (float)($r['earning'] ?? 0),
            'staking'       => (float)($r['staking'] ?? 0),
            'overflow'      => (float)($r['overflow'] ?? 0),
            'raw_bonus'     => (float)($r['raw_bonus'] ?? 0),
            'pending_chain' => (int)($r['pending_chain'] ?? 0),
            'forfeited'     => (int)($r['forfeited'] ?? 0),
        ];
    }

    /** Distinct levels actually present, for the filter dropdown. */
    public function levels()
    {
        $rows = $this->db->query(
            "SELECT DISTINCT level FROM staking_matching_payouts WHERE level IS NOT NULL ORDER BY level ASC"
        )->result_array();
        return array_map(function ($r) { return (int)$r['level']; }, $rows);
    }

    /**
     * Everything about one historical level, for the detail drawer: the frozen
     * calculation, the wallet ledger rows that carried the credit, and the
     * on-chain delivery record.
     */
    public function detail($id)
    {
        $this->db->select(
            'smp.*, u.username, u.referral_id, u.email, u.profile_img, u.image, '
          . 'sp.name AS package_name, sp.stake_amount AS package_stake, '
          . 'sp.group_ceiling AS package_ceiling_now, '
          . 'q.id AS queue_id, q.payout_ref, q.status AS chain_status, q.tx_hash, q.to_address, '
          . 'q.confirmations, q.required_confs, q.last_error AS chain_error, q.amount AS chain_amount, '
          . 'q.confirmed_at, q.last_attempt_at, q.retry_count, q.max_retries'
        )
        ->from('staking_matching_payouts smp')
        ->join('users u', 'u.id = smp.user_id', 'left')
        ->join('staking_packages sp', 'sp.id = smp.highest_package_id', 'left')
        ->join('blockchain_payout_queue q',
               "q.reference_type = 'staking_matching_payout' AND q.reference_id = smp.id", 'left')
        ->where('smp.id', (int)$id);
        $row = $this->db->get()->row_array();
        if (!$row) return null;

        // Wallet credits for THIS level.
        //   level-wise rows  -> per-level reference '<run_ref>-L<n>', with the
        //                       bare run_ref accepted too for rows written
        //                       before that format existed, narrowed by the
        //                       level tag the description has always carried.
        //   legacy rows      -> level IS NULL and the old carry engine wrote
        //                       neither a level tag nor a per-level reference,
        //                       so the run_ref alone is the only link there is.
        //                       Matching on '-L0' would wrongly show nothing.
        $ref = (string)$row['run_ref'];
        $this->db->select('id, wallet_type, credit, debit, balance_after, reference_id, description, created_at')
                 ->from('wallet_ledger')
                 ->where('user_id', (int)$row['user_id'])
                 ->where('reference_type', 'binary_matching');
        if ($row['level'] === null) {
            $this->db->where('reference_id', $ref);
        } else {
            $lvl = (int)$row['level'];
            $this->db->group_start()
                         ->where('reference_id', $ref . '-L' . $lvl)
                         ->or_group_start()
                             ->where('reference_id', $ref)
                             ->like('description', 'L' . $lvl . ' ')
                         ->group_end()
                     ->group_end();
        }
        $row['wallet_rows'] = $this->db->order_by('id', 'ASC')->get()->result_array();

        // Admin overflow ledger entry, if this level produced one.
        $row['admin_rows'] = [];
        if ((float)$row['admin_overflow'] > 0) {
            $this->db->select('id, credit, balance_after, description, created_at')
                     ->from('admin_wallet_ledger')
                     ->where('reference_type', 'binary_matching_overflow')
                     ->where('reference_user_id', (int)$row['user_id'])
                     ->like('description', '[payout #' . (int)$row['id'] . ']')
                     ->order_by('id', 'ASC');
            $row['admin_rows'] = $this->db->get()->result_array();
        }

        // Honesty check: has the package's configured ceiling changed since
        // this level was paid? If so, say so — never silently present today's
        // configuration as the one that applied historically.
        $row['ceiling_drifted'] = $row['package_ceiling_now'] !== null
            && abs((float)$row['package_ceiling_now'] - (float)$row['ceiling_applied']) > 0.00005;

        return $row;
    }
}
