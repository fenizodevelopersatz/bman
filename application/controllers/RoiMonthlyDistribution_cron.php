<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * ROI Monthly Distribution CRON — monthly-for-full-term model.
 *
 * For every active regular/combo ROI record whose next_payment_date has arrived,
 * credit the monthly ROI (principal × monthly%) to the user's EXCHANGE wallet
 * on-chain, once per month, until all duration_years×12 months are paid. Crediting goes
 * through Walletledger_model (updates user_wallets + appends a wallet_ledger row,
 * idempotent on tx_hash+wallet_type) — never a direct balance write.
 *
 * A single run catches up ALL months that are due (handles missed runs / backdated
 * testing). Once every month is paid, next_payment_date is set to the maturity date
 * so RoiMaturityPayment_cron can return principal + any fixed lump.
 *
 * Run it daily (or hourly):
 *   CLI  :  php index.php roimonthlydistribution_cron process   (route: roi-monthly-distribution-process)
 */
class RoiMonthlyDistribution_cron extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Walletledger_model', 'L');
        $this->load->model('Tokenmaster_model', 'tokens');
        $this->load->library('web3bman');
    }

    public function process($onlyId = null)
    {
        // allow scoping via ?record_id= too (HTTP-triggered single-record retry)
        if ($onlyId === null) {
            $qsId = $this->input->get('record_id');
            if ($qsId !== null && $qsId !== '') $onlyId = (int)$qsId;
        }

        $now = date('Y-m-d H:i:s');
        try {
            $this->db
                ->where_in('plan_type', ['regular', 'combo'])
                ->where('overall_status IN (\'active\',\'in_progress\')', null, false)
                ->where('regular_payment_count >', 0)
                ->where('regular_payments_completed < regular_payment_count', null, false)
                ->where('next_payment_date <=', $now)
                ->where('next_payment_date IS NOT NULL', null, false);
            if ($onlyId) $this->db->where('id', (int)$onlyId);
            $records = $this->db->get('roi_staking_management')->result_array();

            $processed = 0; $failed = 0; $details = [];
            foreach ($records as $r) {
                try {
                    $paid = $this->_payDueMonths($r, $now);
                    $processed += $paid['credited'];
                    if ($paid['credited'] > 0) $details[] = $paid['summary'];
                } catch (Exception $e) {
                    $failed++;
                    log_message('error', '[ROI_MONTHLY] record ' . $r['id'] . ': ' . $e->getMessage());
                    $this->db->where('id', $r['id'])->update('roi_staking_management',
                        ['error_message' => substr($e->getMessage(), 0, 500)]);
                }
            }

            echo json_encode([
                'status' => true, 'message' => 'Monthly ROI distribution',
                'due_records' => count($records), 'credits_made' => $processed,
                'failed' => $failed, 'details' => $details, 'ran_at' => $now,
            ]);
        } catch (Exception $e) {
            echo json_encode(['status' => false, 'error' => $e->getMessage(), 'ran_at' => $now]);
        }
    }

    /** Credit every month that is due for one record (catch-up). */
    private function _payDueMonths($r, $now)
    {
        $completed = (int)$r['regular_payments_completed'];
        $count     = (int)$r['regular_payment_count'];
        $amount    = (float)$r['regular_payment_amount'];
        $next      = $r['next_payment_date'];
        $totalPaid = (float)$r['total_paid_amount'];
        $remaining = (float)$r['remaining_to_pay'];
        $credited  = 0;
        $creditError = null;

        // SPECIAL OFFER: the monthly amount escalates by staking-year. Decode the
        // snapshotted year→monthly-% ramp; the per-cycle amount is recomputed
        // inside the loop from the current year (see below). Normal records keep
        // the flat regular_payment_amount.
        $isSpecial = !empty($r['is_special']);
        $special   = $isSpecial ? json_decode((string)$r['special_schedule_json'], true) : null;
        $principal = (float)$r['principal_amount'];

        // For special records the loop guard on $amount can't be the flat value
        // (it may be 0 for year 1 if mis-seeded) — allow entry and compute per cycle.
        while ($completed < $count && ($isSpecial || $amount > 0) && $next && strtotime($next) <= strtotime($now)) {
            $cycle    = $completed + 1;

            // Special: amount for THIS month = principal × that year's monthly %.
            if ($isSpecial) {
                $yearOfStake = intdiv($completed, 12) + 1;                 // 1-based staking year
                $pct = isset($special[$yearOfStake]) ? (float)$special[$yearOfStake]
                     : (isset($special[(string)$yearOfStake]) ? (float)$special[(string)$yearOfStake] : 0);
                $amount = round($principal * ($pct / 100), 8);
                if ($amount <= 0) { break; } // nothing configured for this year — stop safely
            }

            $cycleRef = $r['ref'] . '-M' . $cycle; // per-cycle idempotency key for the on-chain send

            try {
                $txHash = $this->_resolveOrSendRoiTx((int)$r['user_id'], $cycleRef, $amount, 'roi_monthly');
            } catch (Throwable $e) {
                log_message('error', '[ROI_MONTHLY] on-chain send failed rec ' . $r['id'] . ' cycle ' . $cycle . ': ' . $e->getMessage());
                $creditError = "Cycle {$cycle}/{$count} on-chain send failed: " . $e->getMessage();
                break;
            }

            list($ok, $info) = $this->L->credit((int)$r['user_id'], 'exchange', $amount, 'roi', [
                'tx_hash'      => $txHash,
                'reference_id' => $r['ref'],
                'description'  => "Monthly ROI {$cycle}/{$count} — {$amount} BMAN (order {$r['staking_swap_orders_id']})" . (strpos($txHash, 'DRYRUN') === 0 ? ' [DRY-RUN]' : ''),
            ]);
            if (!$ok) {
                log_message('error', '[ROI_MONTHLY] ledger credit failed rec ' . $r['id'] . ' cycle ' . $cycle . ': ' . $info);
                $creditError = "Cycle {$cycle}/{$count} credit failed: {$info}";
                break;
            }

            $completed  = $cycle;
            $totalPaid += $amount;
            $remaining  = max(0, $remaining - $amount);
            $next       = date('Y-m-d H:i:s', strtotime('+1 month', strtotime($next)));
            $credited++;
        }

        if ($credited > 0) {
            $allPaid = $completed >= $count;
            $this->db->where('id', $r['id'])->update('roi_staking_management', [
                'regular_payments_completed' => $completed,
                'total_paid_amount'          => $totalPaid,
                'remaining_to_pay'           => $remaining,
                // once monthly schedule is exhausted, hand off to the maturity cron
                'next_payment_date'          => $allPaid ? $r['fixed_maturity_date'] : $next,
                'overall_status'             => 'in_progress',
                'error_message'              => $creditError,
                'updated_at'                 => date('Y-m-d H:i:s'),
            ]);
        }

        // surface a credit failure so the caller logs error_message + admin can retry —
        // partial progress made above (if any) has already been persisted.
        if ($creditError !== null) {
            throw new RuntimeException($creditError);
        }

        return [
            'credited' => $credited,
            'summary'  => ['id' => (int)$r['id'], 'user_id' => (int)$r['user_id'],
                           'months_credited' => $credited, 'completed' => $completed . '/' . $count,
                           'amount_each' => $amount],
        ];
    }

    /**
     * Resolve the on-chain tx for one monthly cycle: reuse it if this exact
     * cycle was already broadcast (a prior run may have sent on-chain but then
     * failed before the internal credit), otherwise broadcast it now and
     * persist proof of the send immediately — before the internal credit is
     * attempted — so a retry can never send the same cycle twice. Gated by
     * token_settings.swap_dry_run (shared with the purchase swap engine).
     */
    private function _resolveOrSendRoiTx($userId, $cycleRef, $amount, $txType)
    {
        $existing = $this->db->select('tx_hash')
            ->where(['reference_type' => 'roi', 'tx_type' => $txType, 'reference_id' => $cycleRef])
            ->get('onchain_transactions')->row_array();
        if ($existing) return $existing['tx_hash'];

        $cfg = $this->tokens->activeSettings();
        if (!$cfg) throw new RuntimeException('Token settings not configured.');
        $dry = (int)($cfg['swap_dry_run'] ?? 1) === 1;

        if ($dry) {
            $txHash = 'DRYRUN-ROI-' . $cycleRef;
        } else {
            $bmanC = $cfg['bman_contract'] ?? '';
            if (!$bmanC) throw new RuntimeException('BMAN contract not configured.');

            $wallet = $this->db->select('wallet_address')->get_where('user_wallet', ['user_id' => $userId])->row_array();
            if (empty($wallet['wallet_address'])) {
                throw new RuntimeException("User {$userId} has no on-chain wallet address (user_wallet.wallet_address missing).");
            }

            $tk = $this->tokens->treasuryPrivateKey();
            if (!$tk) throw new RuntimeException('Treasury key missing or failed to decrypt.');

            $result = $this->web3bman->sendToken($tk, $wallet['wallet_address'], (string)$amount, $bmanC);
            $txHash = $result['tx_hash'];
        }

        $this->db->insert('onchain_transactions', [
            'tx_hash' => $txHash, 'wallet_type' => 'exchange', 'tx_type' => $txType, 'status' => 'confirmed',
            'user_id' => $userId, 'amount' => $amount,
            'reference_type' => 'roi', 'reference_id' => $cycleRef,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $txHash;
    }

    public function test()
    {
        echo json_encode(['status' => true, 'message' => 'ROI Monthly Distribution operational', 'records_due_now' => $this->_dueCount(), 'now' => date('Y-m-d H:i:s')]);
    }

    private function _dueCount()
    {
        $now = date('Y-m-d H:i:s');
        return $this->db->where_in('plan_type', ['regular', 'combo'])
            ->where('overall_status IN (\'active\',\'in_progress\')', null, false)
            ->where('regular_payments_completed < regular_payment_count', null, false)
            ->where('next_payment_date <=', $now)->count_all_results('roi_staking_management');
    }

    /**
     * CLI convenience: keep re-running process() every few seconds until no
     * record is currently due, or a timeout is hit. Unlike StakingPurchasecron's
     * watch mode, this isn't waiting on blockchain confirmations — each on-chain
     * ROI send here commits synchronously within one process() call. What watch
     * mode buys you instead: catching records that become newly due while you're
     * sitting at the terminal, and auto-retrying anything that failed last pass
     * (a failed record's next_payment_date is untouched, so it's still "due" and
     * gets picked up again next pass).
     *
     *   CLI: php index.php roimonthlydistribution_cron watch
     */
    public function watch($pollSeconds = 5, $maxSeconds = 300)
    {
        if (!is_cli()) show_404();
        $pollSeconds = (int)$pollSeconds ?: 5;
        $maxSeconds  = (int)$maxSeconds  ?: 300;
        $start = time();
        $pass = 0;
        $last = null;

        do {
            $pass++;
            ob_start();
            $this->process();
            $last = json_decode(ob_get_clean(), true);
            $due = $this->_dueCount();

            fwrite(STDOUT, sprintf(
                "[pass %d, %ds elapsed] due_records:%d credits_made:%d failed:%d — %d record(s) still due\n",
                $pass, time() - $start,
                $last['due_records'] ?? 0, $last['credits_made'] ?? 0, $last['failed'] ?? 0, $due
            ));

            if ($due === 0) break;
            if (time() - $start + $pollSeconds < $maxSeconds) sleep($pollSeconds);
        } while (time() - $start < $maxSeconds);

        echo json_encode([
            'status' => $due === 0 ? 'success' : 'timeout',
            'message' => $due === 0 ? 'No records due' : "Timed out after {$maxSeconds}s — run again to continue.",
            'passes' => $pass, 'elapsed_seconds' => time() - $start,
            'last_result' => $last, 'ran_at' => date('Y-m-d H:i:s'),
        ]) . PHP_EOL;
    }
}
