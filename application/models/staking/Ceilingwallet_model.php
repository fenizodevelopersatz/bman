<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Ceilingwallet_model — the system-only Ceiling Wallet ledger.
 * ---------------------------------------------------------------------------
 * Backend only. NOT a user wallet: it never touches user_wallets / wallet_ledger
 * and is never shown to, transferred by, or withdrawn by members. It captures
 * the EXCESS of binary/group income above a user's package earning ceiling
 * (staking_packages.group_ceiling — unchanged).
 *
 * Tables (db/ceiling_wallet.sql):
 *   - ceiling_wallet         : per-user rolled balance (held / total_held / total_released)
 *   - ceiling_wallet_ledger  : append-only HOLD / RELEASE / ADJUSTMENT rows
 *
 * All movements are transactional and row-locked. This is the ONLY writer of
 * the ceiling tables.
 */
class Ceilingwallet_model extends CI_Model
{
    /* ------------------------------ reads ------------------------------ */

    /** Per-user ceiling row (held/total_held/total_released), zero-safe. */
    public function balance($user_id)
    {
        $row = $this->db->get_where('ceiling_wallet', ['user_id' => (int)$user_id])->row_array();
        return $row ?: [
            'user_id' => (int)$user_id, 'held_balance' => '0',
            'total_held' => '0', 'total_released' => '0',
        ];
    }

    /** Total amount currently held across ALL users. */
    public function totalHeld()
    {
        $r = $this->db->select_sum('held_balance', 'held')->get('ceiling_wallet')->row_array();
        return (float)($r['held'] ?? 0);
    }

    /** User-wise held amounts (admin overview). */
    public function heldByUser($opts = [])
    {
        $this->db->select('cw.user_id, cw.held_balance, cw.total_held, cw.total_released, u.username, u.referral_id, ur.current_rank_id')
                 ->from('ceiling_wallet cw')
                 ->join('users u', 'u.id = cw.user_id', 'left')
                 ->join('user_ranks ur', 'ur.user_id = cw.user_id', 'left');
        if (empty($opts['include_zero'])) $this->db->where('cw.held_balance >', 0);
        $this->db->order_by('cw.held_balance', 'DESC');
        if (!empty($opts['limit'])) $this->db->limit((int)$opts['limit'], (int)($opts['offset'] ?? 0));
        return $this->db->get()->result_array();
    }

    /** Ledger history (optionally per user / per type). */
    public function history($opts = [])
    {
        $this->db->select('cwl.*, u.username, u.referral_id')
                 ->from('ceiling_wallet_ledger cwl')
                 ->join('users u', 'u.id = cwl.user_id', 'left');
        if (!empty($opts['user_id'])) $this->db->where('cwl.user_id', (int)$opts['user_id']);
        if (!empty($opts['tx_type'])) $this->db->where('cwl.tx_type', $opts['tx_type']);
        $this->db->order_by('cwl.id', 'DESC')->limit((int)($opts['limit'] ?? 200), (int)($opts['offset'] ?? 0));
        return $this->db->get()->result_array();
    }

    /* ------------------------------ writes ----------------------------- */

    /**
     * Move an amount INTO the ceiling wallet (excess above the user's ceiling).
     * Positive $amount. Returns [ok, ledger_id|message].
     */
    public function hold($user_id, $amount, $opts = [])
    {
        return $this->_move($user_id, (float)$amount, 'CEILING_HOLD', $opts);
    }

    /**
     * Release an amount OUT of the ceiling wallet (admin action). The released
     * funds are, by default, credited to the user's Earning wallet via the
     * normal ledger — pass ['credit_wallet' => false] to only reduce the hold
     * (pure adjustment-style release with no user credit).
     */
    public function release($user_id, $amount, $opts = [])
    {
        $amount = (float)$amount;
        if ($amount <= 0) return [false, 'Release amount must be positive.'];
        $cur = (float)$this->balance($user_id)['held_balance'];
        if ($amount > $cur + 1e-8) return [false, 'Release exceeds held balance.'];

        $creditWallet = array_key_exists('credit_wallet', $opts) ? $opts['credit_wallet'] : 'earning';

        $this->db->trans_begin();
        list($ok, $lid) = $this->_move($user_id, -$amount, 'CEILING_RELEASE', $opts, false);
        if (!$ok) { $this->db->trans_rollback(); return [false, $lid]; }

        // Optionally return the money to the member's wallet through the audited ledger.
        if ($creditWallet) {
            $this->load->model('Walletledger_model', 'L');
            list($cok, $cmsg) = $this->L->credit($user_id, $creditWallet, $amount, 'ceiling_release', [
                'reference_id' => $opts['reference_id'] ?? null,
                'description'  => $opts['description'] ?? 'Ceiling wallet release',
                'created_by'   => $opts['created_by'] ?? null,
            ]);
            if (!$cok) { $this->db->trans_rollback(); return [false, $cmsg]; }
        }

        if ($this->db->trans_status() === false) { $this->db->trans_rollback(); return [false, 'DB error during release.']; }
        $this->db->trans_commit();
        return [true, $lid];
    }

    /** Admin manual adjustment (signed): +credits into ceiling, -reduces it. */
    public function adjust($user_id, $amount, $opts = [])
    {
        $amount = (float)$amount;
        if ($amount == 0) return [false, 'Adjustment cannot be zero.'];
        if ($amount < 0) {
            $cur = (float)$this->balance($user_id)['held_balance'];
            if (abs($amount) > $cur + 1e-8) return [false, 'Adjustment exceeds held balance.'];
        }
        return $this->_move($user_id, $amount, 'CEILING_ADJUSTMENT', $opts);
    }

    /* ------------------------------ core ------------------------------- */

    /**
     * Apply a signed movement to the per-user ceiling balance and append a
     * ledger row. Runs in its own transaction unless $ownTx is false (caller
     * already opened one — used by release()).
     */
    private function _move($user_id, $signedAmount, $txType, $opts, $ownTx = true)
    {
        $user_id = (int)$user_id;
        if ($signedAmount == 0) return [false, 'Amount cannot be zero.'];

        if ($ownTx) $this->db->trans_begin();

        // Ensure + lock the row (read ALL rolled totals under the lock).
        $exists = $this->db->query(
            "SELECT held_balance, total_held, total_released FROM ceiling_wallet WHERE user_id = ? FOR UPDATE",
            [$user_id]
        )->row_array();
        if (!$exists) {
            $this->db->insert('ceiling_wallet', ['user_id' => $user_id, 'held_balance' => 0, 'total_held' => 0, 'total_released' => 0]);
            $exists = ['held_balance' => 0, 'total_held' => 0, 'total_released' => 0];
        }
        $cur = (float)$exists['held_balance'];

        $new = round($cur + $signedAmount, 4);
        if ($new < -1e-8) { if ($ownTx) $this->db->trans_rollback(); return [false, 'Ceiling balance cannot go negative.']; }

        // Lifetime counters track only genuine HOLD / RELEASE moves; a manual
        // ADJUSTMENT changes only the live held_balance, not the lifetime totals.
        $upd = ['held_balance' => $new, 'updated_at' => date('Y-m-d H:i:s')];
        if ($txType === 'CEILING_HOLD')    $upd['total_held']     = round((float)$exists['total_held'] + $signedAmount, 4);
        if ($txType === 'CEILING_RELEASE') $upd['total_released']  = round((float)$exists['total_released'] + abs($signedAmount), 4);
        $this->db->where('user_id', $user_id)->update('ceiling_wallet', $upd);

        $this->db->insert('ceiling_wallet_ledger', [
            'user_id'        => $user_id,
            'tx_type'        => $txType,
            'amount'         => round($signedAmount, 4),
            'held_after'     => $new,
            'source_wallet'  => $opts['source_wallet']  ?? null,
            'matched_volume' => $opts['matched_volume'] ?? null,
            'reference_type' => $opts['reference_type'] ?? null,
            'reference_id'   => $opts['reference_id']   ?? null,
            'description'    => isset($opts['description']) ? substr($opts['description'], 0, 255) : null,
            'created_by'     => $opts['created_by']      ?? null,
        ]);
        $lid = (int)$this->db->insert_id();

        if ($ownTx) {
            if ($this->db->trans_status() === false) { $this->db->trans_rollback(); return [false, 'DB error.']; }
            $this->db->trans_commit();
        }
        return [true, $lid];
    }
}
