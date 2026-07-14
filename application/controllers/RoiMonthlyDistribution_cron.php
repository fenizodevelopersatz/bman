<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * ROI Monthly Distribution CRON — monthly-for-full-term model.
 *
 * For every active regular/combo ROI record whose next_payment_date has arrived,
 * credit the monthly ROI (principal × monthly%) to the user's EARNING wallet,
 * once per month, until all duration_years×12 months are paid. Crediting goes
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
    }

    public function process($onlyId = null)
    {
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

        while ($completed < $count && $amount > 0 && $next && strtotime($next) <= strtotime($now)) {
            $cycle  = $completed + 1;
            $txHash = 'ROI-' . $r['ref'] . '-M' . $cycle; // idempotency key (unique per cycle)

            list($ok, $info) = $this->L->credit((int)$r['user_id'], 'earning', $amount, 'roi', [
                'tx_hash'      => $txHash,
                'reference_id' => $r['ref'],
                'description'  => "Monthly ROI {$cycle}/{$count} — {$amount} BMAN (order {$r['staking_swap_orders_id']})",
            ]);
            if (!$ok) {
                log_message('error', '[ROI_MONTHLY] ledger credit failed rec ' . $r['id'] . ' cycle ' . $cycle . ': ' . $info);
                $creditError = "Cycle {$cycle}/{$count} credit failed: {$info}";
                break;
            }

            // audit row
            $this->_recordOnchain($r, $txHash, $amount, 'roi_monthly', 'earning');

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
        $now = date('Y-m-d H:i:s');
        $due = $this->db->where_in('plan_type', ['regular', 'combo'])
            ->where('overall_status IN (\'active\',\'in_progress\')', null, false)
            ->where('regular_payments_completed < regular_payment_count', null, false)
            ->where('next_payment_date <=', $now)->count_all_results('roi_staking_management');
        echo json_encode(['status' => true, 'message' => 'ROI Monthly Distribution operational', 'records_due_now' => $due, 'now' => $now]);
    }
}
