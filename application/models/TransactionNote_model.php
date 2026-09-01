<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * TransactionNote_model — "own database" per-transaction notes.
 *
 * Deliberately narrow and self-contained: reads/writes ONLY the
 * transaction_notes table, and validates ownership against
 * onchain_transactions before touching anything. No other model, table, or
 * crediting/debiting logic is read or written here.
 *
 * Scope rule (explicit, do not widen): a note may only be attached to a row
 * that (a) genuinely belongs to onchain_transactions — never a
 * bonus_reduction_log-sourced Wallet History row, which has no real chain
 * backing — and (b) actually touches the requesting user's own custodial
 * wallet address, either as sender or recipient. Both are enforced in
 * _ownedOnchainRow() below, not left to the caller.
 */
class TransactionNote_model extends CI_Model
{
    /**
     * The onchain_transactions row, only if it's real (has a tx_hash) AND
     * belongs to this user (their custodial address is the from or to side).
     * Returns null otherwise — the caller must treat that as "not allowed",
     * never fall back to trusting the client-supplied id.
     */
    private function _ownedOnchainRow($onchainTxId, $userId)
    {
        $wallet = $this->db->select('wallet_address')->where('user_id', (int)$userId)
            ->order_by('id', 'ASC')->limit(1)->get('user_wallet')->row_array();
        if (empty($wallet['wallet_address'])) return null;
        $addr = strtolower($wallet['wallet_address']);

        $row = $this->db->select('id, tx_hash, from_address, to_address')
            ->where('id', (int)$onchainTxId)
            ->where('tx_hash IS NOT NULL', null, false)
            ->where("tx_hash != ''", null, false)
            ->get('onchain_transactions')->row_array();
        if (!$row) return null;

        $touchesUser = strtolower((string)$row['from_address']) === $addr
            || strtolower((string)$row['to_address']) === $addr;
        return $touchesUser ? $row : null;
    }

    /**
     * Dedicated, self-contained log — deliberately NOT the standard CI
     * log-YYYY-MM-DD.php files (those already run multi-megabyte and mix in
     * every other request's log lines, making "manually see the own comments
     * of each transaction" impractical). One line per fetch, newest last.
     * Logging failures are swallowed — this must never break the actual
     * note-fetch response.
     */
    private function _logNoteFetch($userId, $onchainTxId, $txHash, $note, $result)
    {
        try {
            $line = sprintf(
                "[%s] user_id=%d onchain_transactions_id=%d tx_hash=%s result=%s note=\"%s\"\n",
                date('Y-m-d H:i:s'),
                (int)$userId,
                (int)$onchainTxId,
                $txHash !== '' ? $txHash : '-',
                $result,
                str_replace(["\r", "\n", '"'], ['', ' ', "'"], (string)$note)
            );
            file_put_contents(APPPATH . 'logs/transaction_notes_fetch.log', $line, FILE_APPEND | LOCK_EX);
        } catch (\Throwable $e) {
            // never let logging break the feature
        }
    }

    /** @return array{ok:bool, note?:string, message?:string} */
    public function get($onchainTxId, $userId)
    {
        $owned = $this->_ownedOnchainRow($onchainTxId, $userId);
        if (!$owned) {
            $this->_logNoteFetch($userId, $onchainTxId, '', '', 'denied');
            return ['ok' => false, 'message' => 'Not an on-chain transaction on your own wallet.'];
        }

        $row = $this->db->select('note')
            ->where(['onchain_transactions_id' => (int)$onchainTxId, 'user_id' => (int)$userId])
            ->get('transaction_notes')->row_array();
        $note = $row['note'] ?? '';

        $this->_logNoteFetch($userId, $onchainTxId, $owned['tx_hash'], $note, 'ok');
        return ['ok' => true, 'note' => $note];
    }

    /** @return array{ok:bool, note?:string, message?:string} */
    public function save($onchainTxId, $userId, $note)
    {
        $owned = $this->_ownedOnchainRow($onchainTxId, $userId);
        if (!$owned) return ['ok' => false, 'message' => 'Not an on-chain transaction on your own wallet.'];

        $note = trim((string)$note);
        if (mb_strlen($note) > 100) return ['ok' => false, 'message' => 'Note must be 100 characters or fewer.'];

        $existing = $this->db->where(['onchain_transactions_id' => (int)$onchainTxId, 'user_id' => (int)$userId])
            ->get('transaction_notes')->row_array();

        if ($note === '') {
            // Empty save = clear the note (matches BscScan: an empty private
            // note simply removes it), rather than storing a blank row.
            if ($existing) $this->db->where('id', $existing['id'])->delete('transaction_notes');
            return ['ok' => true, 'note' => ''];
        }

        if ($existing) {
            $this->db->where('id', $existing['id'])->update('transaction_notes', ['note' => $note]);
        } else {
            $this->db->insert('transaction_notes', [
                'onchain_transactions_id' => (int)$onchainTxId,
                'tx_hash'  => $owned['tx_hash'],
                'user_id'  => (int)$userId,
                'note'     => $note,
            ]);
        }
        return ['ok' => true, 'note' => $note];
    }
}
