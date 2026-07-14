<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * ROI Maturity Payment CRON.
 *
 * When a staking term matures (fixed_maturity_date reached) this:
 *   - credits the fixed lump (principal × fixed%) to the EARNING wallet  [fixed + combo]
 *   - returns the PRINCIPAL to the STAKING wallet                        [all plans]
 *   - marks the ROI record completed.
 *
 * For regular/combo it waits until the monthly schedule is fully paid
 * (regular_payments_completed >= regular_payment_count) so principal is never
 * returned before all monthly ROI has been distributed. All credits go through
 * Walletledger_model (user_wallets + wallet_ledger, idempotent on tx_hash+wallet).
 *
 * Run it daily:  route roi-maturity-payment-process
 */
class RoiMaturityPayment_cron extends CI_Controller
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
                ->where('overall_status !=', 'completed')
                ->where('fixed_maturity_date <=', $now)
                ->where('fixed_maturity_date IS NOT NULL', null, false);
            if ($onlyId) $this->db->where('id', (int)$onlyId);
            $records = $this->db->get('roi_staking_management')->result_array();

            $processed = 0; $skipped = 0; $failed = 0; $details = [];
            foreach ($records as $r) {
                try {
                    // regular/combo: don't mature until every monthly credit is done
                    if ($r['plan_type'] !== 'fixed'
                        && (int)$r['regular_payments_completed'] < (int)$r['regular_payment_count']) {
                        $skipped++;
                        continue;
                    }
                    $details[] = $this->_mature($r);
                    $processed++;
                } catch (Exception $e) {
                    $failed++;
                    log_message('error', '[ROI_MATURITY] record ' . $r['id'] . ': ' . $e->getMessage());
                    $this->db->where('id', $r['id'])->update('roi_staking_management',
                        ['error_message' => substr($e->getMessage(), 0, 500)]);
                }
            }

            echo json_encode([
                'status' => true, 'message' => 'ROI maturity payment', 'due' => count($records),
                'matured' => $processed, 'waiting_on_monthly' => $skipped, 'failed' => $failed,
                'details' => $details, 'ran_at' => $now,
            ]);
        } catch (Exception $e) {
            echo json_encode(['status' => false, 'error' => $e->getMessage(), 'ran_at' => $now]);
        }
    }

    private function _mature($r)
    {
        $uid       = (int)$r['user_id'];
        $principal = (float)$r['principal_amount'];
        $fixedAmt  = (float)($r['fixed_payment_amount'] ?? 0); // lump for fixed/combo (0 for regular)
        $totalPaid = (float)$r['total_paid_amount'];
        $paidLump  = 0;

        // 1) fixed lump ROI -> real on-chain transfer, then mirror into the internal
        //    earning-wallet ledger (fixed + combo)
        if ($fixedAmt > 0 && $r['fixed_status'] !== 'completed') {
            // Idempotency: if a prior run already broadcast this on-chain but failed
            // before/at the internal credit, fixed_tx_hash is already set — reuse it
            // instead of sending a second real transfer.
            $tx = !empty($r['fixed_tx_hash']) ? $r['fixed_tx_hash'] : $this->_sendRoiOnchain($uid, $fixedAmt, $r['ref'] . '-MATURITY');
            if (empty($r['fixed_tx_hash'])) {
                $this->db->where('id', $r['id'])->update('roi_staking_management', ['fixed_tx_hash' => $tx]);
            }

            list($ok, $info) = $this->L->credit($uid, 'earning', $fixedAmt, 'roi', [
                'tx_hash' => $tx, 'reference_id' => $r['ref'],
                'description' => "Maturity ROI lump {$fixedAmt} BMAN (order {$r['staking_swap_orders_id']})" . (strpos($tx, 'DRYRUN') === 0 ? ' [DRY-RUN]' : ''),
            ]);
            if (!$ok) throw new RuntimeException('lump credit failed: ' . $info);
            $this->_recordOnchain($r, $tx, $fixedAmt, 'roi_maturity', 'earning');
            $totalPaid += $fixedAmt;
            $paidLump   = $fixedAmt;
        }

        // 2) return PRINCIPAL -> staking wallet (all plans)
        $txP = 'ROI-' . $r['ref'] . '-PRINCIPAL';
        list($okP, $infoP) = $this->L->credit($uid, 'staking', $principal, 'stake_maturity', [
            'tx_hash' => $txP, 'reference_id' => $r['ref'],
            'description' => "Principal returned {$principal} BMAN at maturity (order {$r['staking_swap_orders_id']})",
        ]);
        if (!$okP) throw new RuntimeException('principal return failed: ' . $infoP);
        $this->_recordOnchain($r, $txP, $principal, 'principal_return', 'staking');

        // 3) finalize record — fixed_tx_hash was already persisted (real or dry-run)
        //    at send time above; do not overwrite it here.
        $this->db->where('id', $r['id'])->update('roi_staking_management', [
            'fixed_status'      => 'completed',
            'fixed_paid_date'   => date('Y-m-d H:i:s'),
            'total_paid_amount' => $totalPaid,
            'remaining_to_pay'  => 0,
            'overall_status'    => 'completed',
            'error_message'     => null,
            'updated_at'        => date('Y-m-d H:i:s'),
        ]);

        return [
            'id' => (int)$r['id'], 'user_id' => $uid, 'plan_type' => $r['plan_type'],
            'lump_roi' => $paidLump, 'principal_returned' => $principal,
        ];
    }

    /**
     * Broadcast the ROI amount on-chain from the treasury wallet to the user's
     * custodial deposit address (user_wallet.wallet_address), returning the real
     * tx_hash. Gated by token_settings.swap_dry_run (shared with the purchase
     * swap engine) — in dry-run mode nothing is sent and a synthetic hash is
     * returned instead, matching Swapengine_model::deliverBman()'s pattern.
     */
    private function _sendRoiOnchain($userId, $amount, $refSuffix)
    {
        $cfg = $this->tokens->activeSettings();
        if (!$cfg) throw new RuntimeException('Token settings not configured.');
        $dry = (int)($cfg['swap_dry_run'] ?? 1) === 1;

        if ($dry) {
            return 'DRYRUN-ROI-' . $refSuffix;
        }

        $bmanC = $cfg['bman_contract'] ?? '';
        if (!$bmanC) throw new RuntimeException('BMAN contract not configured.');

        $wallet = $this->db->select('wallet_address')->get_where('user_wallet', ['user_id' => (int)$userId])->row_array();
        if (empty($wallet['wallet_address'])) {
            throw new RuntimeException("User {$userId} has no on-chain wallet address (user_wallet.wallet_address missing).");
        }

        $tk = $this->tokens->treasuryPrivateKey();
        if (!$tk) throw new RuntimeException('Treasury key missing or failed to decrypt.');

        $result = $this->web3bman->sendToken($tk, $wallet['wallet_address'], (string)$amount, $bmanC);
        return $result['tx_hash'];
    }

    private function _recordOnchain($r, $txHash, $amount, $txType, $wallet)
    {
        if ($this->db->where(['tx_hash' => $txHash, 'wallet_type' => $wallet])->count_all_results('onchain_transactions') > 0) return;
        $this->db->insert('onchain_transactions', [
            'tx_hash' => $txHash, 'wallet_type' => $wallet, 'tx_type' => $txType, 'status' => 'confirmed',
            'user_id' => $r['user_id'], 'amount' => $amount,
            'reference_type' => 'roi', 'reference_id' => $r['ref'],
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function test()
    {
        echo json_encode(['status' => true, 'message' => 'ROI Maturity Payment operational', 'records_due_now' => $this->_dueCount(), 'now' => date('Y-m-d H:i:s')]);
    }

    /** "Due" here means actually actionable by this cron — excludes regular/combo
     *  records whose monthly schedule isn't finished yet (process() skips those),
     *  so watch mode doesn't spin until timeout on something it can't resolve. */
    private function _dueCount()
    {
        return $this->db->where('overall_status !=', 'completed')
            ->where('fixed_maturity_date <=', date('Y-m-d H:i:s'))
            ->group_start()
                ->where('plan_type', 'fixed')
                ->or_where('regular_payments_completed >= regular_payment_count', null, false)
            ->group_end()
            ->count_all_results('roi_staking_management');
    }

    /**
     * CLI convenience: keep re-running process() every few seconds until no
     * record is currently due, or a timeout is hit. Like RoiMonthlyDistribution_
     * cron's watch mode, each on-chain send here commits synchronously within one
     * process() call — this isn't waiting on confirmations, it's catching records
     * that mature while you're at the terminal and auto-retrying failures.
     *
     *   CLI: php index.php roimaturitypayment_cron watch
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
                "[pass %d, %ds elapsed] due:%d matured:%d waiting_on_monthly:%d failed:%d — %d record(s) still due\n",
                $pass, time() - $start,
                $last['due'] ?? 0, $last['matured'] ?? 0, $last['waiting_on_monthly'] ?? 0, $last['failed'] ?? 0, $due
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
