<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * GasFeeLedger_model — the real, per-transaction gas payment audit trail.
 * One row per broadcast leg: 'pending' at broadcast time (policy-estimated
 * gas), backfilled to 'confirmed' with REAL post-confirmation numbers once
 * Chainsync_model::verifyTx() observes the mined receipt. Bookkeeping only —
 * nothing here moves money, parallel to (and linked with) onchain_transactions.
 */
class GasFeeLedger_model extends CI_Model
{
    /** Record a leg the moment it's broadcast (a real tx_hash exists). Idempotent on tx_hash. */
    public function recordBroadcast($txType, $refType, $refId, $userId, $txHash, $from, $to, array $policy = [])
    {
        if (empty($txHash)) return false;
        if ($this->db->where('tx_hash', $txHash)->count_all_results('gas_fee_ledger') > 0) return true;

        $gasPriceWei = !empty($policy['gas_price_gwei'])
            ? bcmul((string) $policy['gas_price_gwei'], '1000000000', 0)
            : null;

        $this->db->insert('gas_fee_ledger', [
            'tx_type'        => $txType,
            'reference_type' => $refType,
            'reference_id'   => $refId,
            'user_id'        => $userId ?: null,
            'from_address'   => $from ? strtolower((string) $from) : null,
            'to_address'     => $to ? strtolower((string) $to) : null,
            'tx_hash'        => $txHash,
            'status'         => 'pending',
            'gas_limit_used' => !empty($policy['gas_limit']) ? (int) $policy['gas_limit'] : null,
            'gas_price_wei'  => $gasPriceWei,
            'created_at'     => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->db->insert_id();
    }

    /** Link the ledger row to its onchain_transactions audit row once that row exists. */
    public function linkOnchainTx($txHash, $onchainTxId)
    {
        if (empty($txHash) || empty($onchainTxId)) return;
        $this->db->where('tx_hash', $txHash)->update('gas_fee_ledger', ['onchain_transaction_id' => (int) $onchainTxId]);
    }

    /** The ledger row linked to a given onchain_transactions.id, if any — used by Chainsync_model. */
    public function findByOnchainTxId($onchainTxId)
    {
        return $this->db->where('onchain_transaction_id', (int) $onchainTxId)->get('gas_fee_ledger')->row_array();
    }

    /**
     * Backfill the REAL numbers once Chainsync_model has verified the tx.
     * Pass exactly what Chainsync_model already computed (gas_used, gas_price
     * in wei, native_fee_total in BNB) so the two tables can never disagree.
     */
    public function recordConfirmed($txHash, $gasUsed, $gasPriceWei, $nativeFeeTotal)
    {
        if (empty($txHash)) return false;
        $row = $this->db->where('tx_hash', $txHash)->get('gas_fee_ledger')->row_array();
        if (!$row) return false;

        $this->db->where('tx_hash', $txHash)->update('gas_fee_ledger', [
            'status'           => 'confirmed',
            'gas_used'         => $gasUsed !== null ? (int) $gasUsed : null,
            'gas_price_wei'    => $gasPriceWei !== null ? $gasPriceWei : $row['gas_price_wei'],
            'native_fee_total' => $nativeFeeTotal,
            'confirmed_at'     => date('Y-m-d H:i:s'),
        ]);
        return true;
    }

    /** All-time real BNB spent on gas — the headline "Total gas spent" figure. */
    public function totalNativeSpent()
    {
        $row = $this->db->select_sum('native_fee_total', 's')->where('status', 'confirmed')->get('gas_fee_ledger')->row();
        return (float) ($row->s ?? 0);
    }
}
