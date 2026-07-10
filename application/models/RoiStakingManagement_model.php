<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class RoiStakingManagement_model extends CI_Model
{
    private $table = 'roi_staking_management';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Create ROI staking record for purchase
     */
    public function createROIRecord($stakingOrderId, $userId, $orderRef, $planType, $data)
    {
        $principal = (float)$data['principal_amount'];
        $roiRate = (float)$data['roi_rate_percent'];
        $durationYears = (int)$data['duration_years'];
        $totalROI = $principal * ($roiRate / 100);

        $recordData = [
            'staking_swap_orders_id' => $stakingOrderId,
            'user_id' => $userId,
            'ref' => $orderRef . '-ROI',
            'plan_type' => $planType,
            'principal_amount' => $principal,
            'roi_rate_percent' => $roiRate,
            'total_roi_amount' => $totalROI,
            'duration_years' => $durationYears,
        ];

        // Plan-specific calculations
        switch ($planType) {
            case 'fixed':
                $recordData['fixed_payment_amount'] = $totalROI;
                $recordData['fixed_maturity_date'] = $data['maturity_date'];
                $recordData['next_payment_date'] = $data['maturity_date'];
                break;

            case 'regular':
                $monthlyPayment = $totalROI / 3;
                $recordData['regular_payment_amount'] = $monthlyPayment;
                $recordData['payment_day_5_amount'] = $monthlyPayment;
                $recordData['payment_day_15_amount'] = $monthlyPayment;
                $recordData['payment_day_25_amount'] = $monthlyPayment;
                $recordData['next_payment_date'] = $this->getNextPaymentDate(5);
                break;

            case 'combo':
                $monthlyPayment = $totalROI / 4;
                $maturityPayment = $totalROI / 4;
                $recordData['regular_payment_amount'] = $monthlyPayment;
                $recordData['fixed_payment_amount'] = $maturityPayment;
                $recordData['fixed_maturity_date'] = $data['maturity_date'];
                $recordData['payment_day_5_amount'] = $monthlyPayment;
                $recordData['payment_day_15_amount'] = $monthlyPayment;
                $recordData['payment_day_25_amount'] = $monthlyPayment;
                $recordData['next_payment_date'] = $this->getNextPaymentDate(5);
                break;
        }

        $recordData['remaining_to_pay'] = $totalROI;
        $recordData['created_at'] = date('Y-m-d H:i:s');

        if ($this->db->insert($this->table, $recordData)) {
            return $this->db->insert_id();
        }
        return false;
    }

    /**
     * Get next payment date (5th, 15th, or 25th)
     */
    private function getNextPaymentDate($day)
    {
        $today = date('d');
        $currentMonth = date('Y-m-');

        if ($today < $day) {
            return $currentMonth . str_pad($day, 2, '0', STR_PAD_LEFT);
        } else {
            return date('Y-m-', strtotime('+1 month')) . str_pad($day, 2, '0', STR_PAD_LEFT);
        }
    }

    /**
     * Get ROI record by staking order ID
     */
    public function getByStakingOrderId($stakingOrderId)
    {
        return $this->db->where('staking_swap_orders_id', $stakingOrderId)
                        ->get($this->table)
                        ->row_array();
    }

    /**
     * Get ROI record by user and date range
     */
    public function getByUserAndDate($userId, $fromDate, $toDate)
    {
        return $this->db->where('user_id', $userId)
                        ->where('created_at >=', $fromDate)
                        ->where('created_at <=', $toDate)
                        ->get($this->table)
                        ->result_array();
    }

    /**
     * Get pending monthly payments for today
     */
    public function getPendingMonthlyPayments($day)
    {
        $this->db->where('plan_type !=', 'fixed')
                 ->where('overall_status !=', 'completed');

        $dayColumn = 'payment_day_' . $day . '_status';

        $this->db->where($dayColumn, 'pending')
                 ->where($dayColumn . ' IS NOT NULL', NULL, FALSE);

        return $this->db->get($this->table)->result_array();
    }

    /**
     * Get pending maturity payments
     */
    public function getPendingMaturityPayments()
    {
        return $this->db->where('plan_type !=', 'regular')
                        ->where('fixed_status', 'pending')
                        ->where('fixed_maturity_date <=', date('Y-m-d H:i:s'))
                        ->get($this->table)
                        ->result_array();
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus($recordId, $paymentDay, $status, $txHash = null)
    {
        $updateData = [
            'payment_day_' . $paymentDay . '_status' => $status,
            'payment_day_' . $paymentDay . '_date' => date('Y-m-d H:i:s'),
        ];

        if ($txHash) {
            $updateData['payment_day_' . $paymentDay . '_tx_hash'] = $txHash;
        }

        return $this->db->where('id', $recordId)
                        ->update($this->table, $updateData);
    }

    /**
     * Update fixed maturity payment status
     */
    public function updateMaturityStatus($recordId, $status, $txHash = null)
    {
        $updateData = [
            'fixed_status' => $status,
            'fixed_paid_date' => date('Y-m-d H:i:s'),
        ];

        if ($txHash) {
            $updateData['fixed_tx_hash'] = $txHash;
        }

        // Check if all payments done
        if ($status === 'completed') {
            $record = $this->db->where('id', $recordId)->get($this->table)->row_array();

            $allMonthlyDone = true;
            if ($record['plan_type'] !== 'fixed') {
                $allMonthlyDone = ($record['payment_day_5_status'] === 'completed' &&
                                  $record['payment_day_15_status'] === 'completed' &&
                                  $record['payment_day_25_status'] === 'completed');
            }

            if ($allMonthlyDone) {
                $updateData['overall_status'] = 'completed';
            }
        }

        return $this->db->where('id', $recordId)
                        ->update($this->table, $updateData);
    }

    /**
     * Update total paid amount
     */
    public function updateTotalPaid($recordId, $amount)
    {
        return $this->db->where('id', $recordId)
                        ->update($this->table, [
                            'total_paid_amount' => $this->db->raw('total_paid_amount + ' . $amount),
                            'remaining_to_pay' => $this->db->raw('remaining_to_pay - ' . $amount),
                        ]);
    }

    /**
     * Calculate next payment date
     */
    public function calculateNextPayment($recordId, $planType)
    {
        $record = $this->db->where('id', $recordId)->get($this->table)->row_array();

        $today = (int)date('d');
        $daysToCheck = [5, 15, 25];

        foreach ($daysToCheck as $day) {
            $statusColumn = 'payment_day_' . $day . '_status';
            if ($record[$statusColumn] === 'pending') {
                $nextDate = $this->getNextPaymentDate($day);
                $this->db->where('id', $recordId)
                         ->update($this->table, ['next_payment_date' => $nextDate]);
                return $nextDate;
            }
        }

        // All monthly done, check maturity
        if ($record['plan_type'] !== 'fixed' && $record['fixed_status'] === 'pending') {
            return $record['fixed_maturity_date'];
        }

        return null;
    }
}
?>
