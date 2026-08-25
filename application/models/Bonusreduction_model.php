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
        $this->load->model('Onchaintx_model', 'octx');

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

            $this->_ensureAdminWalletRow();
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

            // The ledger debit above already created the on-chain history row (via
            // the Walletledger observer). Enrich it with the on-chain send result:
            //   sent   → confirmed + tx hash
            //   failed → partial (internal reduction stands, on-chain leg failed)
            $this->octx->updateByLedgerId(
                is_numeric($ledgerRes) ? (int)$ledgerRes : 0,
                [
                    'tx_hash'        => $txHash,
                    'status'         => $status === 'sent' ? 'confirmed' : ($status === 'failed' ? 'partial' : 'confirmed'),
                    'from_address'   => $fromAddr,
                    'to_address'     => $adminAddr,
                    'credit_wallet'  => 'admin',
                    'failure_reason' => $status === 'failed' ? 'rpc_error' : null,
                    'revert_message' => $status === 'failed' ? $note : null,
                    'completed_steps'=> $status === 'failed' ? 'internal_reduction' : null,
                    'failed_steps'   => $status === 'failed' ? 'onchain_transfer' : null,
                ],
                ['actor_type' => 'cron', 'detail' => 'bonus reduction on-chain result: ' . $status]
            );

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
        $this->_ensureAdminWalletRow();
        return $this->db->get_where('admin_wallet', ['id' => 1])->row_array()
            ?: ['balance' => '0', 'lifetime_bonus_reduction_total' => '0', 'updated_at' => null];
    }

    /**
     * The admin_wallet table is a singleton (id=1) that nothing ever INSERTs —
     * every caller just UPDATEs it, which silently no-ops if the row was never
     * seeded. If that's happened, every completed reduction to date already
     * represents a real internal credit (see run()'s ledger debit above, which
     * always happens regardless of the on-chain leg's outcome) that this
     * balance should already reflect — so backfill from history instead of
     * starting at zero and losing it.
     */
    private function _ensureAdminWalletRow()
    {
        if ($this->db->get_where('admin_wallet', ['id' => 1])->row_array()) return;

        $sum = $this->db->select('COALESCE(SUM(amount),0) AS total', false)
            ->get('bonus_reduction_log')->row_array();
        $total = $sum['total'] ?? '0';

        $this->db->insert('admin_wallet', [
            'id'                              => 1,
            'balance'                         => $total,
            'lifetime_bonus_reduction_total'  => $total,
            'updated_at'                      => date('Y-m-d H:i:s'),
        ]);
    }

    /** Recent reduction history, joined to the user. */
    public function history($limit = 100, $offset = 0)
    {
        return $this->db->select('l.*, u.username, u.email, u.profile_img, u.image, u.first_name, u.last_name', false)
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

    /** Totals for the summary cards. `status` is kept as the historical record of
     *  the on-chain attempt's real outcome, so this is unaffected by later returns. */
    public function totals()
    {
        $row = $this->db->select("COUNT(*) AS cnt, COALESCE(SUM(amount),0) AS total, COALESCE(SUM(status='sent'),0) AS onchain_sent, COALESCE(SUM(status='failed'),0) AS onchain_failed", false)
            ->get('bonus_reduction_log')->row_array();
        return $row ?: ['cnt' => 0, 'total' => '0', 'onchain_sent' => 0, 'onchain_failed' => 0];
    }

    /** Failed rows that are still actionable (not already returned to the user) — drives the "Retry All Failed (N)" button. */
    public function retryableFailedCount()
    {
        return (int) $this->db->where('status', 'failed')->where('reverted_at', null)->count_all_results('bonus_reduction_log');
    }

    /* ----------------------------- reads for the user's own wallet page ------ */

    /** Lifetime reduced + cycle count for one member (for the filter chip badge). */
    public function totalsForUser($user_id)
    {
        $row = $this->db->select("COUNT(*) AS cnt, COALESCE(SUM(amount),0) AS total", false)
            ->where('user_id', (int) $user_id)
            ->get('bonus_reduction_log')->row_array();
        return $row ?: ['cnt' => 0, 'total' => '0'];
    }

    /**
     * This member's reduction history, shaped exactly like
     * Custodialwallet_model::getOnchainTransactions() so it can plug straight
     * into the Wallet History table as a filterable transaction type
     * ('BONUS_REDUCTION') instead of a separate section.
     *
     * A reverted row produces TWO entries — the original debit, and the
     * return credit (as its own real wallet_ledger row via returnToUser()).
     * Both come from this one bonus_reduction_log row's own columns, so
     * there's no separate wallet_ledger query needed. Since one row can
     * expand to two entries, pagination happens after expanding, not on the
     * raw row count — fine at this scale (a personal wallet, not a
     * ledger-wide report; see the same reasoning in the "All" merge above).
     */
    public function walletHistory($user_id, $page = 1, $per_page = 20)
    {
        $user_id  = (int) $user_id;
        $page     = max(1, (int) $page);
        $per_page = max(1, min((int) $per_page, 100));

        $rows = $this->db->where('user_id', $user_id)
            ->order_by('id', 'DESC')
            ->get('bonus_reduction_log')->result_array();

        $transactions = [];
        foreach ($rows as $r) {
            $balanceAfterReduction = bcsub((string) $r['bonus_before'], (string) $r['amount'], 8);

            $transactions[] = [
                'id'                 => (int) $r['id'],
                'tx_hash'            => $r['tx_hash'] ?? '',
                'type'               => 'DEBIT',
                'flow'               => 'DEBIT',
                'title'              => 'Bonus Wallet Reduction',
                'amount'             => $r['amount'],
                'token_symbol'       => 'BMAN',
                // The reduction itself always lands on the user's balance —
                // whether the matching on-chain leg to admin also confirmed
                // is an operational concern, not something to flag as
                // "failed" to the member whose balance really did go down.
                'status'             => 'SUCCESS',
                'from_address'       => $r['from_address'] ?? '',
                'to_address'         => $r['to_address'] ?? '',
                'block_number'       => 0,
                'confirmation_count' => 0,
                'created_at'         => $r['created_at'],
                'network'            => 'bsc',
                'tx_type'            => 'BONUS_REDUCTION',
                'balance_before'     => $r['bonus_before'],
                'balance_after'      => $balanceAfterReduction,
            ];

            if (!empty($r['reverted_at'])) {
                $transactions[] = [
                    'id'                 => -1 * (int) $r['id'], // distinct from the debit row above
                    'tx_hash'            => '',
                    'type'               => 'CREDIT',
                    'flow'               => 'CREDIT',
                    'title'              => 'Bonus Reduction Returned',
                    'amount'             => $r['amount'],
                    'token_symbol'       => 'BMAN',
                    'status'             => 'SUCCESS',
                    'from_address'       => $r['to_address'] ?? '',   // the admin pool it's reversing out of
                    'to_address'         => $r['from_address'] ?? '', // back to the member's own wallet
                    'block_number'       => 0,
                    'confirmation_count' => 0,
                    'created_at'         => $r['reverted_at'],
                    'network'            => 'bsc',
                    'tx_type'            => 'BONUS_REDUCTION_RETURN',
                    'balance_before'     => $balanceAfterReduction,
                    'balance_after'      => $r['bonus_before'],
                ];
            }
        }

        usort($transactions, function ($a, $b) { return strtotime($b['created_at']) <=> strtotime($a['created_at']); });

        $total = count($transactions);
        $pages = max(1, (int) ceil($total / $per_page));
        $page  = min($page, $pages);

        return [
            'rows'   => array_slice($transactions, ($page - 1) * $per_page, $per_page),
            'counts' => ['BONUS_REDUCTION' => $total],
            'paging' => ['page' => $page, 'pages' => $pages, 'total' => $total],
        ];
    }

    /* ----------------------------- admin: retry a stuck on-chain leg --------- */

    /**
     * Retry the on-chain leg of ONE stuck reduction (status='failed'). Resends
     * that row's own already-computed `amount` — never recomputes a fresh
     * percentage off the user's current (already-reduced) balance, since the
     * internal reduction already happened once and must not happen twice.
     * Admin-triggered only (see admin/wallet/Adminwallet::retry()).
     *
     * @return array{status:string, message?:string, tx_hash?:string}
     */
    public function retryOnchain($logId)
    {
        $row = $this->db->get_where('bonus_reduction_log', ['id' => (int) $logId])->row_array();
        if (!$row) return ['status' => 'error', 'message' => 'Reduction row not found.'];
        if ($row['status'] !== 'failed') {
            return ['status' => 'error', 'message' => 'Only a failed on-chain leg can be retried (this row is "' . $row['status'] . '").'];
        }
        if (!empty($row['reverted_at'])) {
            return ['status' => 'error', 'message' => 'This reduction was already returned to the user — nothing to retry.'];
        }

        $adminAddr = $row['to_address'] ?: $this->adminAddress();
        if (empty($adminAddr)) {
            return ['status' => 'error', 'message' => 'No admin bonus/treasury wallet set in Token Settings.'];
        }

        $uw = $this->db->get_where('user_wallet', ['user_id' => (int) $row['user_id']])->row_array();
        $fromAddr = $row['from_address'] ?: ($uw['wallet_address'] ?? null);
        if (empty($uw['private_key']) || empty($fromAddr)) {
            $this->db->where('id', $row['id'])->update('bonus_reduction_log', ['note' => '[retry] no custodial key/address']);
            return ['status' => 'error', 'message' => 'No custodial key/address for this user.'];
        }

        $this->load->library('web3bman');

        list($gasOk, $gasMsg) = $this->_ensureGas($fromAddr);
        if (!$gasOk) {
            $note = substr('[retry] gas unavailable: ' . $gasMsg, 0, 255);
            $this->db->where('id', $row['id'])->update('bonus_reduction_log', ['note' => $note]);
            $this->_mirrorRetryResult($row, 'failed', null, $note);

            // Neither the user's own wallet nor a treasury top-up could cover
            // gas — there's no path to a real on-chain send right now. Rather
            // than leave this stuck FAILED with no way forward, resolve it
            // the same way a manual Return would: credit the user back
            // internally. returnToUser() re-validates from a fresh read, so
            // this is safe even though we already hold $row here.
            $returnResult = $this->returnToUser($row['id'], null);
            if (($returnResult['status'] ?? '') === 'success') {
                return [
                    'status'  => 'auto_returned',
                    'message' => 'Gas unavailable (' . $gasMsg . ') — automatically returned ' . $returnResult['amount'] . ' BMAN to the user.',
                    'amount'  => $returnResult['amount'],
                ];
            }
            return ['status' => 'error', 'message' => 'Gas top-up failed: ' . $gasMsg];
        }

        $txHash = null;
        $err = null;
        try {
            $pk  = $this->web3bman->decryptKey($uw['private_key']);
            $res = $this->web3bman->sendToken($pk, $adminAddr, $row['amount']);
            $txHash = $res['tx_hash'] ?? null;
            if (!$txHash) $err = 'broadcast returned no tx hash';
        } catch (Throwable $e) {
            $err = substr($e->getMessage(), 0, 200);
        }

        if (!$txHash) {
            $note = substr('[retry] ' . $err . ' (gas: ' . $gasMsg . ')', 0, 255);
            $this->db->where('id', $row['id'])->update('bonus_reduction_log', ['note' => $note]);
            $this->_mirrorRetryResult($row, 'failed', null, $note);
            return ['status' => 'error', 'message' => 'On-chain send failed: ' . $err];
        }

        // NOTE: admin_wallet.balance is NOT touched here — it was already
        // credited when the internal reduction first ran (run()'s ledger debit
        // always fires, on-chain outcome or not; see _ensureAdminWalletRow()).
        // Crediting it again here would double-count this cycle.
        $this->db->where('id', $row['id'])->update('bonus_reduction_log', [
            'status'  => 'sent',
            'tx_hash' => $txHash,
            'note'    => substr('[retry] sent (gas: ' . $gasMsg . ')', 0, 255),
        ]);
        $this->_mirrorRetryResult($row, 'sent', $txHash, null);

        return ['status' => 'success', 'tx_hash' => $txHash, 'amount' => $row['amount']];
    }

    /** Retry every currently-failed, not-yet-returned row. Returns a summary; see retryOnchain() for per-row semantics. */
    public function retryAllFailed()
    {
        $ids = $this->db->select('id')->where('status', 'failed')->where('reverted_at', null)
            ->get('bonus_reduction_log')->result_array();

        $out = ['status' => 'success', 'attempted' => count($ids), 'sent' => 0, 'auto_returned' => 0, 'still_failed' => 0, 'details' => []];
        foreach ($ids as $r) {
            $res = $this->retryOnchain((int) $r['id']);
            $st  = $res['status'] ?? 'error';
            if ($st === 'success') $out['sent']++;
            elseif ($st === 'auto_returned') $out['auto_returned']++;
            else $out['still_failed']++;
            $out['details'][] = ['id' => (int) $r['id'], 'status' => $st, 'message' => $res['message'] ?? null];
        }
        return $out;
    }

    /**
     * Reverse ONE reduction internally: credit the user's bonus_balance back
     * by this row's amount, debit admin_wallet.balance back down, mark it
     * reverted. Off-chain only — refused once a row is 'sent' (tokens really
     * left the user's wallet on-chain; reversing that needs a genuine
     * on-chain send-back, not implemented here). `status` itself is left
     * untouched (see totals()'s docblock) — reverted-ness lives in
     * reverted_at/reverted_by instead.
     */
    public function returnToUser($logId, $adminId = null)
    {
        $row = $this->db->get_where('bonus_reduction_log', ['id' => (int) $logId])->row_array();
        if (!$row) return ['status' => 'error', 'message' => 'Reduction row not found.'];
        if (!empty($row['reverted_at'])) {
            return ['status' => 'error', 'message' => 'This reduction was already returned to the user.'];
        }
        if ($row['status'] === 'sent') {
            return ['status' => 'error', 'message' => "This reduction already confirmed on-chain — the tokens really left the user's wallet, so it can't be reversed as a simple internal credit."];
        }

        list($ok, $ledgerRes) = $this->ledger->credit(
            (int) $row['user_id'], 'bonus', $row['amount'], 'bonus_reduction_return',
            [
                'reference_id'  => $row['id'],
                'description'   => "Bonus reduction returned — cycle {$row['cycle_no']} reversed",
                'created_by'    => $adminId,
                // This reverses a debit of funds that already existed (and had
                // already cleared their own maturity) — it isn't a new bonus
                // grant, so it must NOT restart a fresh 60-day lock. Same
                // pattern Walletledger_model::transfer() already uses for its
                // credit leg. Without this, the money is back in
                // user_wallets.bonus_balance immediately, but WalletMaturity_model
                // treats it as locked until the new maturity_date — the
                // wallet page's balance tile (which shows *withdrawable*, not
                // raw balance) then keeps showing the pre-return figure.
                'skip_maturity' => true,
            ]
        );
        if (!$ok) return ['status' => 'error', 'message' => 'Could not credit the user: ' . $ledgerRes];

        $this->_ensureAdminWalletRow();
        $this->db->query(
            "UPDATE admin_wallet SET balance = GREATEST(balance - ?, 0), updated_at = NOW() WHERE id = 1",
            [$row['amount']]
        );

        $this->db->where('id', $row['id'])->update('bonus_reduction_log', [
            'reverted_at' => date('Y-m-d H:i:s'),
            'reverted_by' => $adminId,
            'note'        => substr('[returned] credited back to user' . (!empty($row['note']) ? ' — was: ' . $row['note'] : ''), 0, 255),
        ]);

        return ['status' => 'success', 'amount' => $row['amount']];
    }

    /**
     * Make sure $userAddr can pay gas for one BEP-20 transfer (the reclaimed
     * bonus send to admin) — tops up from the treasury if short. Mirrors the
     * pattern in Swapengine_model::_ensureGas(), sourced via the centralized
     * GasFeeSettings_model instead of a second hand-rolled gas estimate.
     * Returns [ok(bool), message(string)].
     */
    private function _ensureGas($userAddr)
    {
        $this->load->model('GasFeeSettings_model', 'gasPolicy');
        $est = $this->gasPolicy->estimateBnb('gas_funding');
        $needBnb = $est['bnb'];
        if ($needBnb === null) return [false, 'gas price unknown — cannot size the top-up'];

        try {
            $cur = (float) $this->web3bman->getBnbBalance($userAddr);
        } catch (Throwable $e) {
            return [false, 'cannot read BNB balance: ' . $e->getMessage()];
        }
        if ($cur >= $needBnb) return [true, 'sufficient gas (' . $cur . ' BNB)'];

        $this->load->model('Tokenmaster_model', 'tokens');
        $treasuryKey = $this->tokens->treasuryPrivateKey();
        if (!$treasuryKey) return [false, 'treasury/gas key missing'];

        $topup = $needBnb * 2; // top up to ~2x the requirement, keep a small reserve
        try {
            $tx = $this->web3bman->sendBnb($treasuryKey, $userAddr, sprintf('%.8f', $topup));
            $gasTx = $tx['tx_hash'];
        } catch (Throwable $e) {
            return [false, 'gas send failed: ' . $e->getMessage()];
        }

        for ($i = 0; $i < 8; $i++) {
            sleep(3);
            try {
                if ((float) $this->web3bman->getBnbBalance($userAddr) >= $needBnb) return [true, 'topped up (' . $gasTx . ')'];
            } catch (Throwable $e) { /* keep polling */ }
        }
        return [false, 'gas top-up broadcast (' . $gasTx . ') but not confirmed in time — retry shortly'];
    }

    /** Push a retry's outcome onto the mirrored onchain_transactions row (same convention as run()). */
    private function _mirrorRetryResult(array $row, $status, $txHash, $note)
    {
        $this->load->model('Onchaintx_model', 'octx');
        $this->octx->updateByLedgerId(
            (int) ($row['wallet_ledger_id'] ?? 0),
            [
                'tx_hash'         => $txHash,
                'status'          => $status === 'sent' ? 'confirmed' : 'partial',
                'from_address'    => $row['from_address'],
                'to_address'      => $row['to_address'],
                'credit_wallet'   => 'admin',
                'failure_reason'  => $status === 'sent' ? null : 'rpc_error',
                'revert_message'  => $status === 'sent' ? null : $note,
                'completed_steps' => $status === 'sent' ? null : 'internal_reduction',
                'failed_steps'    => $status === 'sent' ? null : 'onchain_transfer',
            ],
            ['actor_type' => 'admin', 'detail' => 'bonus reduction on-chain retry: ' . $status]
        );
    }
}
