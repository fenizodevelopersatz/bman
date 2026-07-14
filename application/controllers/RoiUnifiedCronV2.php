<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * ROI Unified Cron V2
 * Enhanced with gas fee management and failed transaction retry
 */
class RoiUnifiedCronV2 extends CI_Controller
{
    protected $output = [];
    protected $start_time = 0;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('RoiAudit_model');
        $this->load->model('RoiGasManagement_model');
        $this->load->model('member/Walletledger_model');
        $this->load->model('Onchaintx_model', 'tx');
        $this->start_time = microtime(true);
    }

    public function run()
    {
        $this->output = [
            'status'                    => 'success',
            'timestamp'                 => date('Y-m-d H:i:s'),
            'phase_monthly'             => ['processed' => 0, 'failed' => 0, 'gas_fees' => 0],
            'phase_maturity'            => ['processed' => 0, 'failed' => 0, 'gas_fees' => 0],
            'phase_retry'               => ['processed' => 0, 'failed' => 0, 'gas_fees' => 0],
            'total_amount_distributed'  => 0,
            'total_gas_fees'            => 0,
            'errors'                    => [],
            'details'                   => []
        ];

        try {
            // Log execution start
            $exec_id = $this->RoiAudit_model->logCronExecution([
                'execution_date' => date('Y-m-d'),
                'cron_type'      => 'monthly_payment',
                'status'         => 'running'
            ]);

            // Phase 1: Monthly ROI (Days 5, 15, 25)
            $this->processMonthlyROI();

            // Phase 2: Maturity ROI
            $this->processMaturityROI();

            // Phase 3: Retry Failed Transactions
            $this->processFailedRetries();

            // Calculate execution time
            $execution_time = round((microtime(true) - $this->start_time) * 1000);

            // Update execution log
            $this->RoiAudit_model->logCronExecution([
                'execution_date'           => date('Y-m-d'),
                'cron_type'                => 'monthly_payment',
                'status'                   => 'success',
                'total_stakes_processed'   => array_sum(array_column($this->output, 'processed')),
                'total_stakes_failed'      => array_sum(array_column($this->output, 'failed')),
                'total_amount_distributed' => $this->output['total_amount_distributed'],
                'execution_time_ms'        => $execution_time,
                'error_logs'               => !empty($this->output['errors']) ? json_encode($this->output['errors']) : null
            ]);

            $this->output['execution_time_ms'] = $execution_time;

            header('Content-Type: application/json');
            echo json_encode($this->output);

        } catch (Throwable $e) {
            $this->output['status'] = 'error';
            $this->output['message'] = $e->getMessage();
            $this->output['file'] = $e->getFile();
            $this->output['line'] = $e->getLine();

            header('Content-Type: application/json');
            echo json_encode($this->output);
        }
    }

    /**
     * Process monthly ROI (Days 5, 15, 25)
     */
    private function processMonthlyROI()
    {
        $day = (int)date('d');
        if (!in_array($day, [5, 15, 25])) {
            return;
        }

        $stakes = $this->db->query("
            SELECT sp.*, p.stake_amount, pkg.roi
            FROM staking_purchase sp
            JOIN staking_plans p ON sp.plan_id = p.id
            JOIN staking_packages pkg ON sp.package_id = pkg.id
            WHERE sp.roi_plan IN ('regular', 'combo')
            AND sp.purchase_status = 'active'
        ")->result_array();

        foreach ($stakes as $stake) {
            try {
                $user_id = $stake['user_id'];
                $principal = (float)$stake['stake_amount'];
                $duration_years = (int)$stake['duration_years'];
                $roi_data = json_decode($stake['roi'], true) ?? [];

                if (empty($roi_data)) {
                    $this->output['errors'][] = "Stake {$stake['id']}: Invalid ROI data";
                    continue;
                }

                $monthly_rate = $roi_data['regular_'.$duration_years]['pct'] ?? 0;
                if (!$monthly_rate) continue;

                // Calculate ROI
                $monthly_roi = $principal * ($monthly_rate / 100);
                $payment_amount = $monthly_roi / 3;

                // Check gas budget
                $gas_fee = 0.5; // Example: 0.5 USDT per transaction
                $budget_check = $this->RoiGasManagement_model->isBudgetExceeded($gas_fee);

                if ($budget_check['exceeded']) {
                    throw new Exception("Gas budget exceeded ({$budget_check['type']}): {$budget_check['remaining']} USDT remaining");
                }

                // Create wallet ledger entry
                $ledger_data = [
                    'user_id'           => $user_id,
                    'wallet_type'       => 'earning',
                    'transaction_type'  => 'roi_monthly',
                    'amount'            => $payment_amount,
                    'reference_id'      => $stake['id'],
                    'reference_type'    => 'staking_purchase_'.$stake['roi_plan'],
                    'note'              => "ROI Monthly Payment ({$stake['roi_plan']}) - Day {$day}",
                    'created_at'        => date('Y-m-d H:i:s')
                ];

                $ledger_id = $this->Walletledger_model->insert_ledger($ledger_data);

                // Log ROI distribution
                $roi_audit_id = $this->RoiAudit_model->logROIDistribution([
                    'user_id'              => $user_id,
                    'stake_id'             => $stake['id'],
                    'roi_type'             => 'monthly',
                    'plan_type'            => $stake['roi_plan'],
                    'duration_years'       => $duration_years,
                    'principal_amount'     => $principal,
                    'roi_rate_percent'     => $monthly_rate,
                    'roi_amount'           => $payment_amount,
                    'payment_date'         => date('Y-m-d'),
                    'wallet_type'          => 'earning',
                    'status'               => 'success',
                    'ledger_id'            => $ledger_id
                ]);

                // Record gas fee
                $this->RoiGasManagement_model->recordGasFee([
                    'user_id'           => $user_id,
                    'roi_audit_id'      => $roi_audit_id,
                    'transaction_type'  => 'monthly',
                    'gas_fee_usdt'      => $gas_fee,
                    'status'            => 'pending'
                ]);

                $this->output['phase_monthly']['processed']++;
                $this->output['total_amount_distributed'] += $payment_amount;
                $this->output['total_gas_fees'] += $gas_fee;

                $this->output['details'][] = [
                    'type'    => 'monthly_roi',
                    'user_id' => $user_id,
                    'amount'  => $payment_amount,
                    'status'  => 'success'
                ];

            } catch (Throwable $e) {
                $this->output['phase_monthly']['failed']++;
                $this->output['errors'][] = "User {$user_id}: ".$e->getMessage();

                // Log failed transaction
                $this->RoiGasManagement_model->logFailedTransaction([
                    'user_id'         => $user_id,
                    'roi_audit_id'    => $roi_audit_id ?? 0,
                    'roi_amount'      => $payment_amount ?? 0,
                    'failure_reason'  => $e->getMessage(),
                    'failure_code'    => 'MONTHLY_ROI_ERROR'
                ]);
            }
        }
    }

    /**
     * Process maturity ROI payouts
     */
    private function processMaturityROI()
    {
        $stakes = $this->db->query("
            SELECT sp.*, p.stake_amount, pkg.roi
            FROM staking_purchase sp
            JOIN staking_plans p ON sp.plan_id = p.id
            JOIN staking_packages pkg ON sp.package_id = pkg.id
            WHERE sp.roi_plan IN ('fixed', 'combo')
            AND sp.purchase_status = 'active'
            AND DATE(sp.maturity_date) <= DATE(NOW())
        ")->result_array();

        foreach ($stakes as $stake) {
            try {
                $user_id = $stake['user_id'];
                $principal = (float)$stake['stake_amount'];
                $duration_years = (int)$stake['duration_years'];
                $roi_data = json_decode($stake['roi'], true) ?? [];
                $roi_plan = $stake['roi_plan'];

                if (empty($roi_data)) {
                    $this->output['errors'][] = "Stake {$stake['id']}: Invalid ROI data";
                    continue;
                }

                // Calculate ROI based on plan type
                if ($roi_plan === 'fixed') {
                    $fixed_rate = $roi_data['fixed_'.$duration_years]['pct'] ?? 0;
                    if ($fixed_rate <= 0) continue;
                    $roi_amount = $principal * ($fixed_rate / 100);
                } else { // combo - includes BOTH fixed and regular ROI
                    $fixed_rate = $roi_data['fixed_'.$duration_years]['pct'] ?? 0;
                    $regular_rate = $roi_data['regular_'.$duration_years]['pct'] ?? 0;
                    if ($fixed_rate <= 0 && $regular_rate <= 0) continue;
                    $fixed_roi = $principal * ($fixed_rate / 100);
                    $regular_roi = $principal * ($regular_rate / 100) * 12 * $duration_years;
                    $roi_amount = $fixed_roi + $regular_roi;
                }

                // Check gas budget
                $gas_fee = 1.0; // Higher gas fee for maturity
                $budget_check = $this->RoiGasManagement_model->isBudgetExceeded($gas_fee);

                if ($budget_check['exceeded']) {
                    throw new Exception("Gas budget exceeded: {$budget_check['remaining']} USDT remaining");
                }

                // Create wallet ledger entry
                $ledger_data = [
                    'user_id'           => $user_id,
                    'wallet_type'       => 'earning',
                    'transaction_type'  => 'roi_maturity',
                    'amount'            => $roi_amount,
                    'reference_id'      => $stake['id'],
                    'reference_type'    => 'staking_purchase_'.$roi_plan,
                    'note'              => "ROI Maturity Payout ({$roi_plan}) - Term Complete",
                    'created_at'        => date('Y-m-d H:i:s')
                ];

                $ledger_id = $this->Walletledger_model->insert_ledger($ledger_data);

                // Update stake status
                $this->db->update('staking_purchase', ['purchase_status' => 'matured'], ['id' => $stake['id']]);

                // Log ROI distribution
                $roi_audit_id = $this->RoiAudit_model->logROIDistribution([
                    'user_id'              => $user_id,
                    'stake_id'             => $stake['id'],
                    'roi_type'             => 'maturity',
                    'plan_type'            => $roi_plan,
                    'duration_years'       => $duration_years,
                    'principal_amount'     => $principal,
                    'roi_rate_percent'     => ($fixed_rate ?? 0),
                    'roi_amount'           => $roi_amount,
                    'payment_date'         => date('Y-m-d'),
                    'wallet_type'          => 'earning',
                    'status'               => 'success',
                    'ledger_id'            => $ledger_id
                ]);

                // Record gas fee
                $this->RoiGasManagement_model->recordGasFee([
                    'user_id'           => $user_id,
                    'roi_audit_id'      => $roi_audit_id,
                    'transaction_type'  => 'maturity',
                    'gas_fee_usdt'      => $gas_fee,
                    'status'            => 'pending'
                ]);

                // Mark maturity as distributed
                $this->RoiAudit_model->markMaturityDistributed($stake['id']);

                $this->output['phase_maturity']['processed']++;
                $this->output['total_amount_distributed'] += $roi_amount;
                $this->output['total_gas_fees'] += $gas_fee;

            } catch (Throwable $e) {
                $this->output['phase_maturity']['failed']++;
                $this->output['errors'][] = "Maturity ROI error: ".$e->getMessage();

                $this->RoiGasManagement_model->logFailedTransaction([
                    'user_id'         => $user_id ?? 0,
                    'roi_audit_id'    => $roi_audit_id ?? 0,
                    'roi_amount'      => $roi_amount ?? 0,
                    'failure_reason'  => $e->getMessage(),
                    'failure_code'    => 'MATURITY_ROI_ERROR'
                ]);
            }
        }
    }

    /**
     * Process failed transaction retries
     */
    private function processFailedRetries()
    {
        $failed = $this->RoiGasManagement_model->getFailedTransactionsForRetry();

        foreach ($failed as $item) {
            try {
                $user_id = $item['user_id'];

                // Verify user still exists
                $user = $this->db->get_where('members', ['id' => $user_id])->row_array();
                if (!$user) {
                    $this->RoiGasManagement_model->markFailedTransactionResolved(
                        $item['id'],
                        'User no longer exists'
                    );
                    continue;
                }

                // Retry the distribution
                $ledger_data = [
                    'user_id'           => $user_id,
                    'wallet_type'       => 'earning',
                    'transaction_type'  => 'roi_retry',
                    'amount'            => $item['roi_amount'],
                    'reference_id'      => $item['roi_audit_id'],
                    'reference_type'    => 'roi_retry',
                    'note'              => "ROI Retry Attempt #" . ($item['retry_count'] + 1) . " - {$item['plan_type']}",
                    'created_at'        => date('Y-m-d H:i:s')
                ];

                $ledger_id = $this->Walletledger_model->insert_ledger($ledger_data);

                // Update audit record
                $this->db->update('roi_distribution_audit', [
                    'status'       => 'success',
                    'ledger_id'    => $ledger_id,
                    'retry_count'  => ($item['retry_count'] ?? 0) + 1
                ], ['id' => $item['roi_audit_id']]);

                // Mark as resolved
                $this->RoiGasManagement_model->markFailedTransactionResolved(
                    $item['id'],
                    'Retry successful'
                );

                $this->output['phase_retry']['processed']++;
                $this->output['total_amount_distributed'] += $item['roi_amount'];

            } catch (Throwable $e) {
                $this->output['phase_retry']['failed']++;

                // Update retry count
                $this->RoiGasManagement_model->updateFailedTransactionRetry(
                    $item['id'],
                    $item['retry_count'] ?? 0
                );
            }
        }
    }
}
