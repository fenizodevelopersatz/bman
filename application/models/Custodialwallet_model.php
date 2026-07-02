<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Custodialwallet_model — internal (off-chain) BMAN/USDT ledger.
 * -------------------------------------------------------------------
 * In a CUSTODIAL platform the exchange holds ONE set of on-chain wallets
 * (treasury / deposit / gas — configured in Master → Token Settings). Each
 * user has a *custodial* address for receiving deposits, but their spendable
 * balance is an INTERNAL ledger number, not an on-chain balance.
 *
 * Giving a user BMAN (purchase credit, ROI, bonus, matching, admin grant) is
 * therefore just a signed ledger entry in `wallet_transactions` — it needs
 * NO private key and touches NO blockchain. A private key is only ever needed
 * when a user WITHDRAWS to their own external wallet, and even then it is the
 * TREASURY/GAS wallet key (used by Web3bman::sendToken), never a per-user key.
 *
 * This model is the single place that credits/debits internal balances so the
 * math stays consistent with the existing Wallet_model reads.
 *
 * Wallet keys (map to the four §3A wallets): exchange · earning · staking · bonus.
 */
class Custodialwallet_model extends CI_Model
{
    private $wallets = ['exchange', 'earning', 'staking', 'bonus'];

    /* ----------------------------- balances ----------------------------- */

    /** Internal balance of one wallet = credits − debits in the ledger. */
    public function balance($user_id, $wallet = 'exchange')
    {
        if (!in_array($wallet, $this->wallets, true)) return '0';
        $row = $this->db->select("
                COALESCE(SUM(CASE WHEN status='completed' AND tx_type = ".$this->db->escape($wallet)." THEN amount ELSE 0 END), 0) AS credited,
                COALESCE(SUM(CASE WHEN status='completed' AND tx_type='withdraw' AND source = ".$this->db->escape($wallet."_withdraw")." THEN amount ELSE 0 END), 0) AS debited
            ", false)
            ->where('user_id', (int)$user_id)
            ->get('wallet_transactions')->row_array();
        return bcsub((string)($row['credited'] ?? 0), (string)($row['debited'] ?? 0), 8);
    }

    public function balances($user_id)
    {
        $out = [];
        foreach ($this->wallets as $w) $out[$w] = $this->balance($user_id, $w);
        return $out;
    }

    /* -------------------------- credit (NO key) ------------------------- */

    /**
     * Credit BMAN to a user's internal wallet. This is how the platform
     * "gives BMAN" for purchases, ROI, bonus, matching and admin grants —
     * a ledger insert, no blockchain, no private key.
     *
     * @param int    $user_id
     * @param string $amount  human BMAN, e.g. "25"
     * @param string $wallet  exchange|earning|staking|bonus
     * @param string $source  origin tag (e.g. 'roi_payout', 'matching_bonus')
     * @return int inserted ledger row id
     */
    public function credit($user_id, $amount, $wallet = 'exchange', $source = 'admin_credit')
    {
        if (!in_array($wallet, $this->wallets, true)) {
            throw new InvalidArgumentException('Unknown wallet: '.$wallet);
        }
        if (bccomp((string)$amount, '0', 8) <= 0) {
            throw new InvalidArgumentException('Amount must be greater than 0.');
        }
        $this->db->insert('wallet_transactions', [
            'user_id'    => (int)$user_id,
            'tx_type'    => $wallet,          // exchange|earning|staking|bonus
            'source'     => substr($source, 0, 50),
            'amount'     => (float)$amount,
            'status'     => 'completed',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return (int)$this->db->insert_id();
    }

    /**
     * Debit a user's internal wallet (e.g. when moving Exchange → Staking on
     * a stake purchase, or reserving a withdrawal). Guards against overdraw.
     */
    public function debit($user_id, $amount, $wallet = 'exchange', $source = 'internal')
    {
        if (!in_array($wallet, $this->wallets, true)) {
            throw new InvalidArgumentException('Unknown wallet: '.$wallet);
        }
        if (bccomp((string)$amount, '0', 8) <= 0) {
            throw new InvalidArgumentException('Amount must be greater than 0.');
        }
        if (bccomp($this->balance($user_id, $wallet), (string)$amount, 8) < 0) {
            throw new RuntimeException('Insufficient '.$wallet.' balance.');
        }
        $this->db->insert('wallet_transactions', [
            'user_id'    => (int)$user_id,
            'tx_type'    => 'withdraw',
            'source'     => substr($wallet.'_withdraw', 0, 50),
            'amount'     => (float)$amount,
            'status'     => 'completed',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return (int)$this->db->insert_id();
    }

    /** Internal wallet-to-wallet move for one user (e.g. Exchange → Staking). */
    public function move($user_id, $amount, $from_wallet, $to_wallet, $source = 'internal_move')
    {
        $this->db->trans_start();
        $this->debit($user_id, $amount, $from_wallet, $source);
        $this->credit($user_id, $amount, $to_wallet, $source);
        $this->db->trans_complete();
        return $this->db->trans_status();
    }
}
