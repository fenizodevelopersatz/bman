<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Blockchainpayout_model — admin reads + retry for blockchain_payout_queue.
 * ---------------------------------------------------------------------------
 * retry() only resets the row's on-chain state; it never touches any wallet
 * balance. By the time a row exists here, Stakingmatching_model::payMatching()
 * has already credited the user's earning/staking wallet synchronously — this
 * queue is purely the on-chain leg backing that credit. The next cron tick
 * (BinaryMatchingPayoutCron) rebroadcasts whatever is PENDING.
 */
class Blockchainpayout_model extends CI_Model
{
    /* ------------------------------ reads ------------------------------ */

    /** Status counts + today's confirmed volume, for admin KPI cards. */
    public function summary()
    {
        $counts = $this->db->select('status, COUNT(*) AS n')
                           ->group_by('status')->get('blockchain_payout_queue')->result_array();
        $byStatus = ['PENDING' => 0, 'PROCESSING' => 0, 'CONFIRMED' => 0, 'FAILED' => 0, 'RETRY' => 0];
        foreach ($counts as $c) $byStatus[$c['status']] = (int)$c['n'];

        $today = $this->db->select_sum('amount', 'total')
                          ->where('status', 'CONFIRMED')
                          ->where('DATE(confirmed_at)', date('Y-m-d'))
                          ->get('blockchain_payout_queue')->row_array();

        return [
            'by_status'          => $byStatus,
            'needs_attention'    => $byStatus['FAILED'] + $byStatus['RETRY'],
            'confirmed_today_amt'=> (float)($today['total'] ?? 0),
        ];
    }

    /** Filterable/paginated payout rows, joined to users for display. */
    public function list($opts = [])
    {
        $this->db->select('q.*, u.username, u.referral_id, u.profile_img, u.image, '
                        . 'smp.level AS match_level, smp.run_ref AS match_run_ref, '
                        . 'smp.earning_amount, smp.staking_amount, smp.admin_overflow')
                 ->from('blockchain_payout_queue q')
                 ->join('users u', 'u.id = q.user_id', 'left')
                 // The matching level behind each transfer — reference_id is
                 // the staking_matching_payouts row id for binary matching.
                 ->join('staking_matching_payouts smp',
                        "q.reference_type = 'staking_matching_payout' AND smp.id = q.reference_id", 'left');
        if (!empty($opts['status']))  $this->db->where('q.status', $opts['status']);
        if (!empty($opts['purpose'])) $this->db->where('q.purpose', $opts['purpose']);
        if (!empty($opts['user_id'])) $this->db->where('q.user_id', (int)$opts['user_id']);
        $this->db->order_by('q.id', 'DESC')->limit((int)($opts['limit'] ?? 300), (int)($opts['offset'] ?? 0));
        return $this->db->get()->result_array();
    }

    public function find($id)
    {
        return $this->db->get_where('blockchain_payout_queue', ['id' => (int)$id])->row_array();
    }

    /**
     * Everything behind one queued payout, for the detail drawer.
     *
     * Answers the two questions the list cannot: WHERE the money came from and
     * WHY it has not arrived. `precheck_json` is the key — the cron records
     * the treasury's actual BNB/BMAN balance at each attempt, so a held row
     * explains its own shortfall instead of leaving an admin to guess.
     *
     * Also links back to the binary matching level that created it, so the
     * on-chain leg and the internal credit can be read as one story.
     */
    public function detail($id)
    {
        $row = $this->db->select('q.*, u.username, u.referral_id, u.email, u.profile_img, u.image')
                        ->from('blockchain_payout_queue q')
                        ->join('users u', 'u.id = q.user_id', 'left')
                        ->where('q.id', (int)$id)->get()->row_array();
        if (!$row) return null;

        // The matching level this transfer is settling (reference_id is the
        // staking_matching_payouts row id).
        $row['payout'] = null;
        if ($row['reference_type'] === 'staking_matching_payout' && $row['reference_id'] !== null) {
            $row['payout'] = $this->db->select('id, level, matched_volume, raw_bonus, ceiling_applied, '
                                             . 'earning_amount, staking_amount, admin_overflow, run_ref, created_at')
                                      ->where('id', (int)$row['reference_id'])
                                      ->get('staking_matching_payouts')->row_array();
        }

        // Treasury side of the transfer.
        $cfg = $this->db->select('treasury_wallet, explorer_url, bman_contract', false)
                        ->get_where('token_settings', ['status' => 1])->row_array() ?: [];
        $row['treasury_wallet'] = $cfg['treasury_wallet'] ?? null;
        $row['explorer_url']    = rtrim($cfg['explorer_url'] ?? 'https://bscscan.com', '/');

        // Gas actually spent, if the chain-sync sweep has filled it in.
        $row['gas'] = null;
        if (!empty($row['tx_hash'])) {
            $row['gas'] = $this->db->select('gas_used, gas_price, gas_fee_total, status, block_number, confirmation_count')
                                   ->where('tx_hash', $row['tx_hash'])
                                   ->get('onchain_transactions')->row_array();
        }

        $row['precheck'] = $row['precheck_json'] ? json_decode($row['precheck_json'], true) : null;
        return $row;
    }

    /* ------------------------- treasury funding ------------------------- */

    /**
     * Can the treasury actually pay what is queued?
     *
     * This is the question the payout queue could not answer before: a row
     * sitting in RETRY says "insufficient balance" in last_error, but nothing
     * told an admin HOW MUCH to top up, or how many payouts the current
     * balance covers. BinaryMatchingPayoutCron drains oldest-first and stops
     * at the first row it cannot afford (FIFO fairness), so the useful figure
     * is not "total outstanding vs balance" but "how far down the queue can we
     * get before it stalls" — computed here the same way the drain does it.
     *
     * IMPORTANT CONTEXT for the UI: a shortfall never loses money and never
     * costs a member anything. The internal wallet credit already happened
     * inside the matching engine, in the same transaction that closed the
     * level. This queue is only the on-chain delivery leg, so a dry treasury
     * delays a transfer — it does not reverse, forfeit or double-pay it.
     *
     * RPC failure is reported, never guessed: balances come back null and
     * `rpc_ok` is false, so the view shows "unknown" instead of a fake 0 that
     * would read as "treasury empty".
     */
    public function treasuryStatus()
    {
        $cfg = $this->db->get_where('token_settings', ['status' => 1])->row_array() ?: [];
        $addr = trim((string)($cfg['treasury_wallet'] ?? ''));

        // SAME resolver the cron broadcasts with — not a copy of the formula.
        // Treasury Safety and the actual payout must agree, and they only do
        // if both call GasFeeSettings_model. A null estimate means the gas
        // price is genuinely unknown; it is reported as such, never as 0.
        $this->load->model('GasFeeSettings_model', 'gasCfg');
        $est = $this->gasCfg->estimateBnb('binary_matching');
        $gasPerSend = $est['bnb'];
        $gasKnown = $gasPerSend !== null;

        $queued = $this->db->select('id, amount', false)
                           ->where_in('status', ['PENDING', 'RETRY'])
                           ->order_by('created_at', 'ASC')->order_by('id', 'ASC')
                           ->get('blockchain_payout_queue')->result_array();

        $outstanding = 0.0;
        foreach ($queued as $q) $outstanding += (float)$q['amount'];
        $gasNeeded = $gasKnown ? $gasPerSend * count($queued) : null;

        $out = [
            'treasury_address' => $addr,
            'rpc_ok'           => false,
            'bnb_balance'      => null,
            'bman_balance'     => null,
            'queued_count'     => count($queued),
            'outstanding_bman' => round($outstanding, 8),
            'gas_per_send_bnb' => $gasKnown ? round($gasPerSend, 8) : null,
            'gas_needed_bnb'   => $gasKnown ? round($gasNeeded, 8) : null,
            // Where the estimate came from, so the admin page can show that a
            // change on the gas settings screen is actually in force here.
            'gas_source'       => $est['source'],
            'gas_price_source' => $est['price_source'],
            'gas_limit'        => $est['gas_limit'],
            'gas_price_gwei'   => $est['gas_price_gwei'],
            'gas_buffer'       => $est['buffer_multiplier'],
            'covers_count'     => 0,
            'shortfall_bman'   => null,
            'shortfall_bnb'    => null,
            'blocked_reason'   => null,
            'dry_run'          => (int)($cfg['swap_dry_run'] ?? 1) === 1,
            'swap_enabled'     => (int)($cfg['swap_enabled'] ?? 0) === 1,
        ];

        if ($addr === '') { $out['blocked_reason'] = 'No treasury wallet configured in Token Settings.'; return $out; }
        if (!$gasKnown) {
            $out['blocked_reason'] = 'Gas price is not configured for binary_matching and no live price is available — '
                                   . 'the cost per transfer is UNKNOWN, so coverage cannot be verified.';
            return $out;
        }
        if (!$queued)     { $out['rpc_ok'] = null; $out['blocked_reason'] = null; }

        try {
            $this->load->library('web3bman');
            $bnb  = (float)$this->web3bman->getBnbBalance($addr);
            $bman = (float)$this->web3bman->getTokenBalance($addr);
        } catch (Throwable $e) {
            $out['blocked_reason'] = 'RPC unreachable — treasury balance unknown: ' . $e->getMessage();
            return $out;
        }

        $out['rpc_ok'] = true;
        $out['bnb_balance']  = round($bnb, 8);
        $out['bman_balance'] = round($bman, 8);

        // Walk the queue exactly as the drain does: oldest-first, stop at the
        // first row we cannot afford. That row's shortfall is what to top up.
        $remBnb = $bnb; $remBman = $bman; $covers = 0;
        foreach ($queued as $q) {
            $need = (float)$q['amount'];
            if ($remBnb < $gasPerSend || $remBman < $need) {
                $out['shortfall_bman'] = round(max(0, $need - $remBman), 8);
                $out['shortfall_bnb']  = round(max(0, $gasPerSend - $remBnb), 8);
                $out['blocked_reason'] = $remBnb < $gasPerSend
                    ? 'Not enough BNB for gas — the queue stalls after ' . $covers . ' payout(s).'
                    : 'Not enough BMAN — the queue stalls after ' . $covers . ' payout(s).';
                break;
            }
            $remBnb -= $gasPerSend; $remBman -= $need; $covers++;
        }
        $out['covers_count'] = $covers;

        if ($queued && $covers === count($queued)) {
            $out['shortfall_bman'] = 0.0; $out['shortfall_bnb'] = 0.0;
        }
        if (!$out['swap_enabled'] && !$out['dry_run']) {
            $out['blocked_reason'] = 'swap_enabled = 0 in Token Settings — nothing will broadcast.';
        }
        return $out;
    }

    /**
     * Bulk retry: reset every FAILED/RETRY row to PENDING at once.
     *
     * The single-row retry is unusable after a treasury outage, which is
     * exactly when retry matters most — one shortfall can park dozens of rows
     * simultaneously and an admin should not click through them one by one
     * after topping up. Same guarantees as retry(): on-chain state only, never
     * a wallet credit, so it cannot double-pay however many times it is run.
     *
     * Returns the affected count rather than a per-row report — the queue list
     * right below the button is the report.
     */
    public function retryAll($adminId = null, $onlyStatus = null)
    {
        $statuses = $onlyStatus ? [$onlyStatus] : ['FAILED', 'RETRY'];
        $statuses = array_values(array_intersect($statuses, ['FAILED', 'RETRY']));
        if (!$statuses) return [false, 'Only FAILED or RETRY payouts can be retried.'];

        $n = (int)$this->db->where_in('status', $statuses)->count_all_results('blockchain_payout_queue');
        if ($n === 0) return [true, 'Nothing to retry — no FAILED or RETRY payouts.', 0];

        $this->db->where_in('status', $statuses)->update('blockchain_payout_queue', [
            'status'        => 'PENDING',
            'tx_hash'       => null,
            'block_number'  => null,
            'confirmations' => 0,
            'confirmed_at'  => null,
            'last_error'    => null,
            'precheck_json' => null,
            'retry_count'   => 0,
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);
        $affected = $this->db->affected_rows();

        log_message('info', "[BINARY_MATCHING_PAYOUT] Admin #{$adminId} bulk-reset {$affected} payout(s) ("
            . implode('/', $statuses) . ") to PENDING.");
        return [true, $affected . ' payout(s) queued for retry — the next cron run will rebroadcast them.', $affected];
    }

    /* ------------------------------ writes ------------------------------ */

    /**
     * Admin retry: reset a FAILED/RETRY row back to PENDING so the next cron
     * tick rebroadcasts it. Full state reset (including retry_count -> 0) so
     * a human fixing the underlying cause (e.g. funding the treasury) gives
     * the row a fresh automatic-retry budget. Never credits/debits anything.
     */
    public function retry($id, $adminId = null)
    {
        $id = (int)$id;
        $row = $this->find($id);
        if (!$row) return [false, 'Payout not found.'];
        if (!in_array($row['status'], ['FAILED', 'RETRY'], true)) {
            return [false, 'Only FAILED or RETRY payouts can be retried (current status: '.$row['status'].').'];
        }

        $this->db->where('id', $id)->where_in('status', ['FAILED', 'RETRY'])->update('blockchain_payout_queue', [
            'status'         => 'PENDING',
            'tx_hash'        => null,
            'block_number'   => null,
            'confirmations'  => 0,
            'confirmed_at'   => null,
            'last_error'     => null,
            'precheck_json'  => null,
            'retry_count'    => 0,
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);
        if ($this->db->affected_rows() !== 1) return [false, 'Payout state changed before retry could apply — refresh and try again.'];

        log_message('info', "[BINARY_MATCHING_PAYOUT] Admin #{$adminId} reset payout #{$id} ({$row['payout_ref']}) to PENDING for retry.");
        return [true, 'Payout queued for retry — the next cron run will rebroadcast it.'];
    }
}
