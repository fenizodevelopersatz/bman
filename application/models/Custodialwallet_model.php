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

    /** All five wallet balances (proposal §3): USDT + the four §3A buckets. */
    public function fiveWallets($user_id)
    {
        return array_merge(
            ['usdt' => $this->usdtBalance($user_id)],
            $this->balances($user_id)
        );
    }

    /* =================== CUSTODIAL ON-CHAIN ADDRESS ==================== */

    /** The user's custodial deposit address row (user_wallet), or null. */
    public function walletRow($user_id)
    {
        return $this->db->get_where('user_wallet', ['user_id' => (int)$user_id])->row_array() ?: null;
    }

    /**
     * Ensure the user has ONE unique on-chain deposit address. Returns the
     * user_wallet row. If a row already exists it is returned untouched
     * (existing ETH_MASTER wallets are respected). If none exists a fresh
     * BEP-20 wallet is generated LOCALLY via Web3bman (no external ADROX
     * dependency), its key AES-encrypted, a QR PNG rendered with the existing
     * InfiQr generator, and the row inserted with a uniqueness guard.
     */
    public function ensureAddress($user_id)
    {
        $user_id = (int)$user_id;
        $existing = $this->walletRow($user_id);
        if ($existing) return $existing;

        $this->load->library('web3bman');

        // generate until the address is unique in user_wallet (practically 1 try)
        $tries = 0;
        do {
            $w = $this->web3bman->generateWallet();      // ['address','private_key']
            $addr = $w['address'];
            $dupe = $this->db->where('wallet_address', $addr)->count_all_results('user_wallet');
            $tries++;
        } while ($dupe > 0 && $tries < 5);
        if ($dupe > 0) return null;

        // QR (reuse the existing InfiQr wrapper) — assets/images/qr_image/<addr>qr_code.png
        $qr_url = '';
        try {
            $this->load->library('gloabals');
            $dir = 'assets/images/qr_image/';
            if (!is_dir('./'.$dir)) @mkdir('./'.$dir, 0777, true);
            $file = $dir.$addr.'qr_code.png';
            $this->gloabals->generate($addr, 'png', $file);
            $qr_url = base_url($file);
        } catch (Exception $e) {
            $qr_url = ''; // QR is best-effort; address still stored
        }

        $this->db->insert('user_wallet', [
            'user_id'        => $user_id,
            'wallet_address' => $addr,
            'mnemonic'       => '',                                   // local wallet: no mnemonic stored
            'private_key'    => $this->web3bman->encryptKey($w['private_key']), // AES, not ADROX
            'wallet_qrimage' => $qr_url,
        ]);
        return $this->walletRow($user_id);
    }

    /* ==================== ON-CHAIN vs DB MONITOR ===================== */

    /** Internal USDT balance we credited to the user (our DB record). */
    public function usdtBalance($user_id)
    {
        $row = $this->db->get_where('user_wallets', ['user_id' => (int)$user_id])->row_array();
        return $row ? (string)$row['usd_balance'] : '0';
    }

    /**
     * Read the real on-chain balances of the user's address (USDT, BMAN, BNB)
     * and compare the USDT balance against our DB record. Returns null when the
     * user has no address. Logs the scan to wallet_monitor_log.
     */
    public function monitor($user_id, $changed_by = null)
    {
        $wallet = $this->walletRow($user_id);
        if (!$wallet) return null;
        $addr = $wallet['wallet_address'];

        $this->load->library('web3bman');
        $this->load->model('Tokenmaster_model', 'tokens');
        $cfg = $this->tokens->activeSettings();
        $usdt_contract = $cfg['usdt_contract'] ?? null;

        $onchain_usdt = $usdt_contract ? $this->web3bman->getTokenBalance($addr, $usdt_contract) : '0';
        $onchain_bnb  = $this->web3bman->getBnbBalance($addr);
        $onchain_bman = ($cfg['bman_contract'] ?? null) ? $this->web3bman->getTokenBalance($addr) : '0';

        $db_usdt = $this->usdtBalance($user_id);
        $diff = bcsub((string)$onchain_usdt, (string)$db_usdt, 8);

        $this->db->insert('wallet_monitor_log', [
            'user_id'         => (int)$user_id,
            'address'         => $addr,
            'token'           => 'USDT',
            'onchain_balance' => $onchain_usdt,
            'db_balance'      => $db_usdt,
            'difference'      => $diff,
            'action'          => 'scan',
            'changed_by'      => $changed_by !== null ? (int)$changed_by : null,
        ]);

        return [
            'address'       => $addr,
            'qr'            => $wallet['wallet_qrimage'],
            'onchain_usdt'  => (string)$onchain_usdt,
            'onchain_bnb'   => (string)$onchain_bnb,
            'onchain_bman'  => (string)$onchain_bman,
            'db_usdt'       => (string)$db_usdt,
            'difference'    => $diff,        // >0 => on-chain funds not yet credited
            'has_pending'   => bccomp($diff, '0', 8) > 0,
        ];
    }

    /**
     * Reconcile a positive difference: credit it to the user's internal USDT
     * balance (user_wallets.usd_balance), record a custodial_deposits row and a
     * reconcile log entry. Idempotent-ish: only credits a strictly positive
     * difference. Returns [ok, message].
     */
    public function reconcile($user_id, $changed_by, $note = null)
    {
        $m = $this->monitor($user_id, $changed_by);
        if (!$m) return [false, 'User has no custodial address.'];
        if (bccomp($m['difference'], '0', 8) <= 0) {
            return [true, 'No positive difference to credit (already reconciled).'];
        }
        $amount = $m['difference'];

        $this->db->trans_start();
        // upsert user_wallets.usd_balance += amount
        $row = $this->db->get_where('user_wallets', ['user_id' => (int)$user_id])->row_array();
        if ($row) {
            $this->db->where('user_id', (int)$user_id)->update('user_wallets', [
                'usd_balance' => bcadd((string)$row['usd_balance'], $amount, 8),
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);
        } else {
            $this->db->insert('user_wallets', [
                'user_id' => (int)$user_id, 'usd_balance' => $amount,
                'usd_pending' => 0, 'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
        $this->db->insert('custodial_deposits', [
            'user_id' => (int)$user_id, 'address' => $m['address'], 'token' => 'USDT',
            'amount'  => $amount, 'onchain_confirmed' => 1, 'credited' => 1,
            'source'  => 'monitor', 'note' => $note ? substr($note, 0, 255) : 'reconciled from on-chain',
            'credited_at' => date('Y-m-d H:i:s'),
        ]);
        $this->db->insert('wallet_monitor_log', [
            'user_id' => (int)$user_id, 'address' => $m['address'], 'token' => 'USDT',
            'onchain_balance' => $m['onchain_usdt'], 'db_balance' => $m['db_usdt'],
            'difference' => $amount, 'action' => 'reconcile',
            'changed_by' => $changed_by !== null ? (int)$changed_by : null,
            'note' => $note ? substr($note, 0, 255) : null,
        ]);
        $this->db->trans_complete();
        if (!$this->db->trans_status()) return [false, 'Database error.'];
        return [true, 'Credited '.$amount.' USDT to the internal balance.'];
    }

    /* -------------------------- histories -------------------------- */

    public function deposits($user_id, $limit = 50)
    {
        $user_id = (int)$user_id;
        $limit = (int)$limit;

        // Primary source: wallet_deposits written by Depositlistener_model.
        $rows = $this->db->where('user_id', $user_id)
                         ->order_by('id', 'DESC')
                         ->limit($limit)
                         ->get('wallet_deposits')
                         ->result_array();

        if (!empty($rows)) {
            return array_map(function ($r) {
                return [
                    'id' => $r['id'] ?? null,
                    'token' => $r['token'] ?? 'USDT',
                    'amount_usdt' => $r['amount_usdt'] ?? 0,
                    'status' => $r['status'] ?? '',
                    'tx_hash' => $r['tx_hash'] ?? '',
                    'detected_at' => $r['credited_at'] ?? ($r['created_at'] ?? ''),
                    'confirmed_at' => $r['credited_at'] ?? '',
                    'wallet_address' => $r['wallet_address'] ?? '',
                    'network' => $r['network'] ?? '',
                    'block_number' => $r['block_number'] ?? null,
                    'confirmations' => $r['confirmations'] ?? null,
                ];
            }, $rows);
        }

        // Backward-compatible fallback for older rows if any exist.
        return $this->db->where('user_id', $user_id)
                        ->order_by('detected_at', 'DESC')->limit($limit)
                        ->get('custodial_deposits')->result_array();
    }

    public function monitorLog($user_id = 0, $limit = 100)
    {
        if ($user_id) $this->db->where('user_id', (int)$user_id);
        return $this->db->order_by('created_at', 'DESC')->limit((int)$limit)
                        ->get('wallet_monitor_log')->result_array();
    }
}
