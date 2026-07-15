<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Walletledger_model — the SINGLE source of truth for wallet balances.
 * -------------------------------------------------------------------
 * Production rule: balances are NEVER updated ad-hoc. Every movement appends a
 * `wallet_ledger` row (credit XOR debit) carrying the resulting balance_after,
 * and the matching `user_wallets` column is updated in the SAME transaction
 * with a row lock. A UNIQUE (tx_hash, wallet_type) index makes it impossible to
 * credit the same on-chain transaction twice.
 *
 * Wallets: usdt · exchange · earning · staking · bonus.
 * Reference types: deposit · withdrawal · stake_purchase · roi · bonus ·
 *   binary_commission · rank_reward · wallet_transfer · admin_adjustment.
 */
class Walletledger_model extends CI_Model
{
    private $wallets = ['usdt','exchange','earning','staking','bonus'];
    private $col = [
        'usdt'     => 'usd_balance',
        'exchange' => 'exchange_balance',
        'earning'  => 'earning_balance',
        'staking'  => 'staking_balance',
        'bonus'    => 'bonus_balance',
    ];

    /* ------------------------------ reads ------------------------------ */

    private function ensureRow($user_id)
    {
        $row = $this->db->get_where('user_wallets', ['user_id' => (int)$user_id])->row_array();
        if (!$row) {
            $this->db->insert('user_wallets', ['user_id' => (int)$user_id, 'usd_balance' => 0]);
            $row = $this->db->get_where('user_wallets', ['user_id' => (int)$user_id])->row_array();
        }
        return $row;
    }

    public function balance($user_id, $wallet)
    {
        if (!isset($this->col[$wallet])) return '0';
        $row = $this->db->select($this->col[$wallet])
                        ->get_where('user_wallets', ['user_id' => (int)$user_id])->row_array();
        return $row ? (string)$row[$this->col[$wallet]] : '0';
    }

    public function balances($user_id)
    {
        $row = $this->db->get_where('user_wallets', ['user_id' => (int)$user_id])->row_array();
        $out = [];
        foreach ($this->wallets as $w) $out[$w] = $row ? (string)$row[$this->col[$w]] : '0';
        return $out;
    }

    /* ------------------------------ writes ----------------------------- */

    /**
     * Post one balance movement. Positive $amount credits, negative debits.
     * Returns [ok, message|ledger_id]. Enforces:
     *   - known wallet, non-zero amount
     *   - no overdraw on debit
     *   - no double-credit for a repeated tx_hash (unique index → caught)
     * All inside a transaction with SELECT … FOR UPDATE on the balance row.
     */
    public function post($user_id, $wallet, $amount, $reference_type, $opts = [])
    {
        $user_id = (int)$user_id;
        if (!isset($this->col[$wallet])) return [false, 'Unknown wallet: '.$wallet];
        $amount = (string)$amount;
        if (bccomp($amount, '0', 8) === 0) return [false, 'Amount cannot be zero.'];

        $tx_hash  = isset($opts['tx_hash']) && $opts['tx_hash'] !== '' ? $opts['tx_hash'] : null;
        $ref_id   = isset($opts['reference_id']) ? $opts['reference_id'] : null;
        $desc     = isset($opts['description']) ? substr($opts['description'], 0, 255) : null;
        $admin    = isset($opts['created_by']) ? (int)$opts['created_by'] : null;
        $col      = $this->col[$wallet];

        // Idempotency: if this tx already produced a ledger row for this wallet, skip.
        if ($tx_hash) {
            $dupe = $this->db->where(['tx_hash' => $tx_hash, 'wallet_type' => $wallet])
                             ->count_all_results('wallet_ledger');
            if ($dupe > 0) return [true, 'already_posted'];
        }

        $this->db->trans_begin();
        $this->ensureRow($user_id);
        // row lock to serialise concurrent credits/debits for this user
        $locked = $this->db->query(
            "SELECT `$col` AS bal FROM user_wallets WHERE user_id = ? FOR UPDATE", [$user_id]
        )->row_array();
        $current = (string)($locked['bal'] ?? '0');

        $credit = bccomp($amount, '0', 8) > 0 ? $amount : '0';
        $debit  = bccomp($amount, '0', 8) < 0 ? bcmul($amount, '-1', 8) : '0';

        // Overdraw guard. Admin overrides may pass allow_overdraw to let the
        // balance go negative (sender debited beyond available funds).
        if (empty($opts['allow_overdraw'])
            && bccomp($debit, '0', 8) > 0 && bccomp($current, $debit, 8) < 0) {
            $this->db->trans_rollback();
            return [false, 'Insufficient '.$wallet.' balance.'];
        }

        $new = bcadd($current, $amount, 8);

        // update the balance column + lifetime totals for USDT deposits/withdraws
        $upd = [$col => $new];
        if ($wallet === 'usdt' && $reference_type === 'deposit'    && bccomp($credit,'0',8) > 0) {
            $upd['total_deposit_usdt'] = bcadd((string)$this->_col($user_id,'total_deposit_usdt'), $credit, 8);
        }
        if ($wallet === 'usdt' && $reference_type === 'withdrawal' && bccomp($debit,'0',8) > 0) {
            $upd['total_withdraw_usdt'] = bcadd((string)$this->_col($user_id,'total_withdraw_usdt'), $debit, 8);
        }
        $upd['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('user_id', $user_id)->update('user_wallets', $upd);

        $ledger_row = [
            'user_id'        => $user_id,
            'wallet_type'    => $wallet,
            'credit'         => $credit,
            'debit'          => $debit,
            'balance_after'  => $new,
            'reference_type' => $reference_type,
            'reference_id'   => $ref_id,
            'description'    => $desc,
            'tx_hash'        => $tx_hash,
            'created_by'     => $admin,
        ];

        // Every credit carries maturity metadata for withdrawal eligibility.
        if (bccomp($credit, '0', 8) > 0 && $this->db->field_exists('maturity_date', 'wallet_ledger')) {
            $this->load->model('WalletMaturity_model', 'maturity');
            $mat = $this->maturity->resolve_credit_maturity($wallet, $opts);
            $ledger_row['maturity_date'] = $mat['maturity_date'];
            $ledger_row['is_matured']    = (int) $mat['is_matured'];
        }

        $this->db->insert('wallet_ledger', $ledger_row);
        $ledger_id = (int)$this->db->insert_id();

        if ($this->db->trans_status() === false) {
            $this->db->trans_rollback();
            return [false, 'Database error (possibly a duplicate tx_hash).'];
        }
        $this->db->trans_commit();

        // Observer: mirror this committed movement into the on-chain transaction
        // history. Runs AFTER commit and is fully guarded — a recording failure
        // can never roll back or affect the balance movement above.
        $this->_captureOnchain($user_id, $wallet, $credit, $debit, $new,
            $reference_type, $ref_id, $tx_hash, $admin, $desc, $ledger_id);

        return [true, $ledger_id];
    }

    /** Cached active-token metadata (avoids a query per movement). */
    private function _tokenMeta()
    {
        static $m = null;
        if ($m !== null) return $m;
        $r = $this->db->select('network,chain_id,usdt_name,usdt_contract,usdt_decimals,bman_name,bman_contract,bman_decimals')
                      ->get_where('token_settings', ['status' => 1])->row_array();
        $m = $r ?: ['network'=>'mainnet','chain_id'=>56,'usdt_name'=>'USDT','usdt_contract'=>null,'usdt_decimals'=>18,
                    'bman_name'=>'BMAN','bman_contract'=>null,'bman_decimals'=>18];
        return $m;
    }

    /** Fail-safe: record the movement in onchain_transactions (+ audit event). */
    private function _captureOnchain($user_id, $wallet, $credit, $debit, $new, $refType, $refId, $txHash, $admin, $desc, $ledgerId)
    {
        try {
            $meta   = $this->_tokenMeta();
            $isUsdt = ($wallet === 'usdt');
            $isCredit = bccomp((string)$credit, '0', 8) > 0;
            $this->load->model('Onchaintx_model', 'octx');
            $this->octx->capture([
                'tx_hash'          => $txHash ?: null,
                'network'          => $meta['network'],
                'chain_id'         => (int)$meta['chain_id'],
                'wallet_type'      => $wallet,
                'tx_type'          => $refType,
                'status'           => 'confirmed',
                'user_id'          => $user_id,
                'admin_id'         => $admin,
                'token_symbol'     => $isUsdt ? 'USDT' : 'BMAN',
                'token_name'       => $isUsdt ? $meta['usdt_name'] : $meta['bman_name'],
                'token_contract'   => $isUsdt ? $meta['usdt_contract'] : $meta['bman_contract'],
                'token_decimals'   => $isUsdt ? (int)$meta['usdt_decimals'] : (int)$meta['bman_decimals'],
                'amount'           => $isCredit ? $credit : $debit,
                'debit_wallet'     => $isCredit ? null : $wallet,
                'credit_wallet'    => $isCredit ? $wallet : null,
                'balance_after'    => $new,
                'wallet_ledger_id' => $ledgerId,
                'reference_type'   => $refType,
                'reference_id'     => $refId,
                'created_by'       => $admin,
            ]);
        } catch (Throwable $e) {
            log_message('error', '[wallet_ledger onchain hook] ' . $e->getMessage());
        }
    }

    private function _col($user_id, $col)
    {
        $row = $this->db->select($col)->get_where('user_wallets', ['user_id' => (int)$user_id])->row_array();
        return $row ? (string)$row[$col] : '0';
    }

    /** Convenience wrappers. */
    public function credit($user_id, $wallet, $amount, $reference_type, $opts = [])
    {
        return $this->post($user_id, $wallet, (string)$amount, $reference_type, $opts);
    }
    public function debit($user_id, $wallet, $amount, $reference_type, $opts = [])
    {
        return $this->post($user_id, $wallet, bcmul((string)$amount, '-1', 8), $reference_type, $opts);
    }

    /** Internal wallet-to-wallet transfer (e.g. Exchange → Staking on stake). */
    public function transfer($user_id, $amount, $from, $to, $reference_type = 'wallet_transfer', $opts = [])
    {
        $this->db->trans_begin();
        list($ok1, $r1) = $this->debit($user_id, $from, $amount, $reference_type, $opts);
        if (!$ok1) { $this->db->trans_rollback(); return [false, $r1]; }
        $credit_opts = array_merge($opts, ['is_matured' => 1, 'maturity_date' => date('Y-m-d H:i:s')]);
        list($ok2, $r2) = $this->credit($user_id, $to, $amount, $reference_type, $credit_opts);
        if (!$ok2) { $this->db->trans_rollback(); return [false, $r2]; }
        $this->db->trans_commit();
        return [true, 'Transferred '.$amount.' from '.$from.' to '.$to.'.'];
    }

    /* ---------------------------- statements --------------------------- */

    public function statement($user_id, $wallet = null, $limit = 100)
    {
        $this->db->where('user_id', (int)$user_id);
        if ($wallet) $this->db->where('wallet_type', $wallet);
        return $this->db->order_by('id', 'DESC')->limit((int)$limit)
                        ->get('wallet_ledger')->result_array();
    }
}
