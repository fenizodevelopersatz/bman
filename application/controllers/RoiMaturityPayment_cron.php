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
    }

    public function process($onlyId = null)
    {
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

        // 1) fixed lump ROI -> earning wallet (fixed + combo)
        if ($fixedAmt > 0 && $r['fixed_status'] !== 'completed') {
            $tx = 'ROI-' . $r['ref'] . '-MATURITY';
            list($ok, $info) = $this->L->credit($uid, 'earning', $fixedAmt, 'roi', [
                'tx_hash' => $tx, 'reference_id' => $r['ref'],
                'description' => "Maturity ROI lump {$fixedAmt} BMAN (order {$r['staking_swap_orders_id']})",
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

        // 3) finalize record
        $this->db->where('id', $r['id'])->update('roi_staking_management', [
            'fixed_status'      => 'completed',
            'fixed_paid_date'   => date('Y-m-d H:i:s'),
            'fixed_tx_hash'     => $fixedAmt > 0 ? 'ROI-' . $r['ref'] . '-MATURITY' : null,
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
        $due = $this->db->where('overall_status !=', 'completed')
            ->where('fixed_maturity_date <=', $now)->count_all_results('roi_staking_management');
        echo json_encode(['status' => true, 'message' => 'ROI Maturity Payment operational', 'records_due_now' => $due, 'now' => $now]);
    }
}
