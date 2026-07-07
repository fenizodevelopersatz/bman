<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Bonusreduction_model — the one place the Bonus Wallet 60-day reduction runs.
 * ---------------------------------------------------------------------------
 * Shared by Bonusreductioncron (scheduled) and admin/wallet/Adminwallet (the
 * "Run now" button), so there is a single tested code path.
 *
 * See docs/11_ADMIN_WALLET_MANAGEMENT.md.
 */
class Bonusreduction_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Walletledger_model', 'ledger');
    }

    /** The admin bonus wallet address: token_settings.bonus_wallet → treasury_wallet. */
    public function adminAddress()
    {
        $ts = $this->db->select("COALESCE(NULLIF(bonus_wallet,''), treasury_wallet) AS admin_addr", false)
                       ->get_where('token_settings', ['status' => 1])->row_array();
        return $ts['admin_addr'] ?? null;
    }

    /**
     * Run the reduction.
     *
     * @param array $opt {
     *   force_dry_run?: bool  // override the settings' dry_run (admin "Preview")
     *   triggered_by?:  string // 'cron' | 'admin'
     * }
     * @return array summary (same shape the cron echoes)
     */
    public function run(array $opt = [])
    {
        $started = microtime(true);
        $today   = new DateTimeImmutable('today');

        $cfg = $this->db->get_where('staking_bonus_settings', ['id' => 1])->row_array();
        if (!$cfg) {
            return ['status' => 'error', 'message' => 'staking_bonus_settings row missing'];
        }
        if ((int)$cfg['reduction_enabled'] !== 1) {
            return ['status' => 'success', 'processed' => 0, 'reason' => 'reduction_disabled'];
        }

        $interval = max(1, (int)$cfg['reduction_interval_days']);
        $percent  = (string)$cfg['reduction_percent'];
        $dryRun   = array_key_exists('force_dry_run', $opt)
                    ? (bool)$opt['force_dry_run']
                    : ((int)($cfg['reduction_dry_run'] ?? 1) === 1);
        $onchain  = (int)($cfg['reduction_onchain'] ?? 0) === 1;

        $adminAddr = $this->adminAddress();

        // token metadata once (for the onchain_transactions mirror rows)
        $ts = $this->db->select('chain_id, bman_name, bman_contract, bman_decimals')
                       ->get_where('token_settings', ['status' => 1])->row_array() ?: [];

        $web3 = null;
        if ($onchain && !$dryRun) {
            if (empty($adminAddr)) {
                return ['status' => 'error',
                    'message' => 'reduction_onchain=1 but no admin bonus/treasury wallet is set in Token Settings'];
            }
            try {
                $this->load->library('web3bman');
                $web3 = $this->web3bman;
            } catch (Throwable $e) {
                return ['status' => 'error', 'message' => 'Web3 unavailable: ' . $e->getMessage()];
            }
        }

        $rows = $this->db->select('u.id, u.register_date, uw.bonus_balance', false)
            ->from('users u')
            ->join('user_wallets uw', 'uw.user_id = u.id')
            ->where('u.status', '1')
            ->where('uw.bonus_balance >', 0)
            ->get()->result_array();

        $processed = 0; $skipped = 0; $reducedTotal = '0'; $preview = [];

        foreach ($rows as $r) {
            $uid   = (int)$r['id'];
            $bonus = (string)$r['bonus_balance'];

            $last = $this->db->select('created_at')->where('user_id', $uid)
                        ->order_by('id', 'DESC')->limit(1)
                        ->get('bonus_reduction_log')->row_array();
            $priorCount = (int)$this->db->where('user_id', $uid)->count_all_results('bonus_reduction_log');

            $anchorRaw = $last['created_at'] ?? $r['register_date'];
            if (empty($anchorRaw)) { $skipped++; continue; }
            $anchor    = new DateTimeImmutable(date('Y-m-d', strtotime($anchorRaw)));
            $daysSince = (int)$anchor->diff($today)->days;
            if ($daysSince < $interval) { $skipped++; continue; }

            $amount = bcdiv(bcmul($bonus, $percent, 8), '100', 8);
            if (bccomp($amount, '0', 8) <= 0) { $skipped++; continue; }

            $cycleNo = $priorCount + 1;

            if ($dryRun) {
                if (count($preview) < 200) {
                    $preview[] = [
                        'user_id' => $uid, 'cycle_no' => $cycleNo,
                        'bonus_before' => $bonus, 'would_reduce' => $amount,
                        'days_since_anchor' => $daysSince, 'anchor' => $anchor->format('Y-m-d'),
                    ];
                }
                $reducedTotal = bcadd($reducedTotal, $amount, 8);
                $processed++;
                continue;
            }

            // internal double-entry reduction
            list($ok, $ledgerRes) = $this->ledger->debit(
                $uid, 'bonus', $amount, 'bonus_reduction',
                ['description' => "Bonus {$interval}-day reduction ({$percent}%, cycle {$cycleNo})"]
            );
            if (!$ok) { $skipped++; continue; }

            $this->db->query(
                "UPDATE admin_wallet
                    SET balance = balance + ?,
                        lifetime_bonus_reduction_total = lifetime_bonus_reduction_total + ?,
                        updated_at = NOW()
                  WHERE id = 1", [$amount, $amount]
            );

            $status = 'internal'; $txHash = null; $note = null; $fromAddr = null;
            if ($onchain && $web3) {
                $uw = $this->db->get_where('user_wallet', ['user_id' => $uid])->row_array();
                $fromAddr = $uw['wallet_address'] ?? null;
                if (empty($uw['private_key']) || empty($fromAddr)) {
                    $status = 'failed'; $note = 'no custodial key/address';
                } else {
                    try {
                        $pk  = $this->web3bman->decryptKey($uw['private_key']);
                        $res = $this->web3bman->sendToken($pk, $adminAddr, $amount);
                        $txHash = $res['tx_hash'] ?? null;
                        $status = $txHash ? 'sent' : 'failed';
                        if (!$txHash) $note = 'broadcast returned no tx hash';
                    } catch (Throwable $e) {
                        $status = 'failed';
                        $note = substr($e->getMessage(), 0, 240);
                    }
                }
            }

            $this->db->insert('bonus_reduction_log', [
                'user_id'          => $uid,
                'cycle_no'         => $cycleNo,
                'anchor_date'      => date('Y-m-d H:i:s', strtotime($anchorRaw)),
                'bonus_before'     => $bonus,
                'reduce_percent'   => $percent,
                'amount'           => $amount,
                'from_address'     => $fromAddr,
                'to_address'       => $adminAddr,
                'wallet_ledger_id' => is_numeric($ledgerRes) ? (int)$ledgerRes : null,
                'tx_hash'          => $txHash,
                'status'           => $status,
                'note'             => $note,
                'created_at'       => date('Y-m-d H:i:s'),
            ]);

            // Mirror into the unified on-chain transaction history (doc 13).
            $this->db->insert('onchain_transactions', [
                'tx_hash'        => $txHash,
                'network'        => 'mainnet',
                'chain_id'       => (int)($ts['chain_id'] ?? 56),
                'wallet_type'    => 'bonus',
                'tx_type'        => 'bonus_reduction',
                'status'         => $status === 'sent' ? 'confirmed' : ($status === 'failed' ? 'failed' : 'confirmed'),
                'from_address'   => $fromAddr,
                'to_address'     => $adminAddr,
                'user_id'        => $uid,
                'token_symbol'   => 'BMAN',
                'token_name'     => $ts['bman_name'] ?? 'BMAN Token',
                'token_contract' => $ts['bman_contract'] ?? null,
                'token_decimals' => isset($ts['bman_decimals']) ? (int)$ts['bman_decimals'] : 18,
                'amount'         => $amount,
                'debit_wallet'   => 'bonus',
                'credit_wallet'  => 'admin',
                'wallet_ledger_id' => is_numeric($ledgerRes) ? (int)$ledgerRes : null,
                'reference_type' => 'bonus_reduction',
                'failure_reason' => $status === 'failed' ? 'rpc_error' : null,
                'revert_message' => $status === 'failed' ? $note : null,
                'created_at'     => date('Y-m-d H:i:s'),
            ]);

            $reducedTotal = bcadd($reducedTotal, $amount, 8);
            $processed++;
        }

        return [
            'status'             => 'success',
            'mode'               => $dryRun ? 'dry_run' : ($onchain ? 'execute_onchain' : 'execute_internal'),
            'triggered_by'       => $opt['triggered_by'] ?? 'cron',
            'interval_days'      => $interval,
            'reduction_percent'  => $percent,
            'admin_bonus_wallet' => $adminAddr,
            'candidates'         => count($rows),
            'processed'          => $processed,
            'skipped_not_due'    => $skipped,
            'reduced_total_bman' => $reducedTotal,
            'preview'            => $dryRun ? $preview : null,
            'took_ms'            => round((microtime(true) - $started) * 1000, 2),
            'ran_at'             => date('Y-m-d H:i:s'),
        ];
    }

    /* ----------------------------- reads for the admin page ------------------ */

    /** The admin wallet singleton row. */
    public function adminWallet()
    {
        return $this->db->get_where('admin_wallet', ['id' => 1])->row_array()
            ?: ['balance' => '0', 'lifetime_bonus_reduction_total' => '0', 'updated_at' => null];
    }

    /** Recent reduction history, joined to the user. */
    public function history($limit = 100, $offset = 0)
    {
        return $this->db->select('l.*, u.username, u.email', false)
            ->from('bonus_reduction_log l')
            ->join('users u', 'u.id = l.user_id', 'left')
            ->order_by('l.id', 'DESC')
            ->limit((int)$limit, (int)$offset)
            ->get()->result_array();
    }

    public function historyCount()
    {
        return (int)$this->db->count_all('bonus_reduction_log');
    }

    /** Totals for the summary cards. */
    public function totals()
    {
        $row = $this->db->select("COUNT(*) AS cnt, COALESCE(SUM(amount),0) AS total, COALESCE(SUM(status='sent'),0) AS onchain_sent, COALESCE(SUM(status='failed'),0) AS onchain_failed", false)
            ->get('bonus_reduction_log')->row_array();
        return $row ?: ['cnt' => 0, 'total' => '0', 'onchain_sent' => 0, 'onchain_failed' => 0];
    }
}
