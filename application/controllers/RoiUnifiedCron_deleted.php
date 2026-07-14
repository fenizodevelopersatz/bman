<?php defined('BASEPATH') OR exit('No direct script access allowed');

class RoiUnifiedCron extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Staking_model');
        $this->load->model('member/StakingPurchase_model');
        $this->load->model('member/Walletledger_model');
        $this->load->model('Onchaintx_model', 'tx');
    }

    public function run()
    {
        $output = [
            'status' => 'success',
            'timestamp' => date('Y-m-d H:i:s'),
            'fixed_processed' => 0,
            'regular_processed' => 0,
            'combo_processed' => 0,
            'maturity_processed' => 0,
            'errors' => [],
            'details' => []
        ];

        try {
            $today = date('Y-m-d');
            $day_of_month = (int)date('d');
            $is_payment_day = in_array($day_of_month, [5, 15, 25]);

            // Process Regular & Combo monthly payments (on days 5, 15, 25)
            if ($is_payment_day) {
                $result = $this->processMonthlyROI($output);
                $output['regular_processed'] = $result['regular'];
                $output['combo_processed'] = $result['combo'];
            }

            // Process maturity-date payouts for Fixed & Combo plans
            $maturity_result = $this->processMaturityROI($output);
            $output['fixed_processed'] = $maturity_result['fixed'];
            $output['maturity_processed'] = $maturity_result['combo_maturity'];

            header('Content-Type: application/json');
            echo json_encode($output);
        } catch (Throwable $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'timestamp' => date('Y-m-d H:i:s'),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
    }

    /**
     * Process monthly ROI for Regular and Combo plans
     * Runs on days 5, 15, 25 of each month
     */
    private function processMonthlyROI(&$output)
    {
        $result = ['regular' => 0, 'combo' => 0];

        // Get all active stakes with Regular or Combo plan
        $query = "SELECT sp.*, p.stake_amount, pkg.roi
                  FROM staking_purchase sp
                  JOIN staking_plans p ON sp.plan_id = p.id
                  JOIN staking_packages pkg ON sp.package_id = pkg.id
                  WHERE sp.roi_plan IN ('regular', 'combo')
                  AND sp.purchase_status = 'active'
                  AND DATE(sp.purchase_date) <= DATE(NOW())";

        $stakes = $this->db->query($query)->result_array();

        foreach ($stakes as $stake) {
            try {
                $user_id = $stake['user_id'];
                $principal = (float)$stake['stake_amount'];
                $roi_plan = $stake['roi_plan'];
                $duration_years = (int)$stake['duration_years'];
                $roi_data = json_decode($stake['roi'], true) ?? [];

                // Get monthly rate
                $monthly_rate = $roi_data['regular_'.$duration_years]['pct'] ?? 0;
                if (!$monthly_rate) continue;

                // Monthly ROI = principal × (monthly_rate / 100)
                // Divided into 3 payments
                $monthly_roi = $principal * ($monthly_rate / 100);
                $payment_amount = $monthly_roi / 3;

                // Record this payment to earning wallet
                $ledger_data = [
                    'user_id' => $user_id,
                    'wallet_type' => 'earning',
                    'transaction_type' => 'roi_monthly',
                    'amount' => $payment_amount,
                    'reference_id' => $stake['id'],
                    'reference_type' => 'staking_purchase_'.$roi_plan,
                    'note' => "ROI Monthly Payment ({$roi_plan}) - Day ".date('d'),
                    'created_at' => date('Y-m-d H:i:s')
                ];

                $this->Walletledger_model->insert_ledger($ledger_data);

                if ($roi_plan === 'regular') {
                    $result['regular']++;
                } elseif ($roi_plan === 'combo') {
                    $result['combo']++;
                }

                $output['details'][] = [
                    'type' => 'monthly_'.$roi_plan,
                    'user_id' => $user_id,
                    'stake_id' => $stake['id'],
                    'amount' => $payment_amount,
                    'status' => 'processed'
                ];
            } catch (Throwable $e) {
                $output['errors'][] = 'Monthly ROI error: '.$e->getMessage();
            }
        }

        return $result;
    }

    /**
     * Process maturity-date ROI payouts
     * For Fixed plans: pay all ROI at maturity
     * For Combo plans: pay fixed portion at maturity
     */
    private function processMaturityROI(&$output)
    {
        $result = ['fixed' => 0, 'combo_maturity' => 0];

        $query = "SELECT sp.*, p.stake_amount, pkg.roi
                  FROM staking_purchase sp
                  JOIN staking_plans p ON sp.plan_id = p.id
                  JOIN staking_packages pkg ON sp.package_id = pkg.id
                  WHERE sp.roi_plan IN ('fixed', 'combo')
                  AND sp.purchase_status = 'active'
                  AND DATE(sp.maturity_date) <= DATE(NOW())";

        $stakes = $this->db->query($query)->result_array();

        foreach ($stakes as $stake) {
            try {
                $user_id = $stake['user_id'];
                $principal = (float)$stake['stake_amount'];
                $roi_plan = $stake['roi_plan'];
                $duration_years = (int)$stake['duration_years'];
                $roi_data = json_decode($stake['roi'], true) ?? [];

                if ($roi_plan === 'fixed') {
                    // Fixed plan: pay all ROI at maturity
                    $fixed_rate = $roi_data['fixed_'.$duration_years]['pct'] ?? 0;
                    if ($fixed_rate <= 0) continue;

                    $roi_amount = $principal * ($fixed_rate / 100);

                    // Record to earning wallet
                    $ledger_data = [
                        'user_id' => $user_id,
                        'wallet_type' => 'earning',
                        'transaction_type' => 'roi_maturity',
                        'amount' => $roi_amount,
                        'reference_id' => $stake['id'],
                        'reference_type' => 'staking_purchase_fixed',
                        'note' => "ROI Maturity Payout (Fixed Plan) - {$fixed_rate}%",
                        'created_at' => date('Y-m-d H:i:s')
                    ];

                    $this->Walletledger_model->insert_ledger($ledger_data);

                    // Update stake status
                    $this->db->update('staking_purchase', ['purchase_status' => 'matured'], ['id' => $stake['id']]);

                    $result['fixed']++;

                    $output['details'][] = [
                        'type' => 'maturity_fixed',
                        'user_id' => $user_id,
                        'stake_id' => $stake['id'],
                        'amount' => $roi_amount,
                        'status' => 'processed'
                    ];

                } elseif ($roi_plan === 'combo') {
                    // Combo plan: pay fixed portion at maturity
                    $fixed_rate = $roi_data['fixed_'.$duration_years]['pct'] ?? 0;
                    if ($fixed_rate <= 0) continue;

                    $fixed_roi_amount = $principal * ($fixed_rate / 100);

                    // Record to earning wallet
                    $ledger_data = [
                        'user_id' => $user_id,
                        'wallet_type' => 'earning',
                        'transaction_type' => 'roi_maturity',
                        'amount' => $fixed_roi_amount,
                        'reference_id' => $stake['id'],
                        'reference_type' => 'staking_purchase_combo_fixed',
                        'note' => "ROI Maturity Payout (Combo Fixed) - {$fixed_rate}%",
                        'created_at' => date('Y-m-d H:i:s')
                    ];

                    $this->Walletledger_model->insert_ledger($ledger_data);

                    // Update stake status
                    $this->db->update('staking_purchase', ['purchase_status' => 'matured'], ['id' => $stake['id']]);

                    $result['combo_maturity']++;

                    $output['details'][] = [
                        'type' => 'maturity_combo_fixed',
                        'user_id' => $user_id,
                        'stake_id' => $stake['id'],
                        'amount' => $fixed_roi_amount,
                        'status' => 'processed'
                    ];
                }

            } catch (Throwable $e) {
                $output['errors'][] = 'Maturity ROI error: '.$e->getMessage();
            }
        }

        return $result;
    }
}
