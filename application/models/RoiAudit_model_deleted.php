<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * ROI Audit Model
 * Tracks all ROI distributions, validations, and missed executions
 */
class RoiAudit_model extends CI_Model
{
    /**
     * Record ROI distribution in audit log
     */
    public function logROIDistribution($data)
    {
        $audit_data = [
            'user_id'              => (int)$data['user_id'],
            'stake_id'             => (int)($data['stake_id'] ?? 0),
            'roi_type'             => $data['roi_type'], // monthly, maturity, retry
            'plan_type'            => $data['plan_type'], // fixed, regular, combo
            'duration_years'       => (int)$data['duration_years'],
            'principal_amount'     => (float)$data['principal_amount'],
            'roi_rate_percent'     => (float)$data['roi_rate_percent'],
            'roi_amount'           => (float)$data['roi_amount'],
            'payment_date'         => $data['payment_date'],
            'actual_payment_date'  => date('Y-m-d H:i:s'),
            'execution_date'       => date('Y-m-d'),
            'wallet_type'          => $data['wallet_type'] ?? 'earning',
            'tx_hash'              => $data['tx_hash'] ?? null,
            'status'               => $data['status'] ?? 'success',
            'error_message'        => $data['error_message'] ?? null,
            'ledger_id'            => (int)($data['ledger_id'] ?? 0),
            'notes'                => $data['notes'] ?? null
        ];

        $this->db->insert('roi_distribution_audit', $audit_data);
        return $this->db->insert_id();
    }

    /**
     * Get ROI distribution history for admin view
     */
    public function getROIHistory($filters = [], $limit = 100, $offset = 0)
    {
        $query = $this->db->select('
            rda.*,
            m.username,
            m.email,
            m.first_name,
            m.last_name
        ')
        ->from('roi_distribution_audit rda')
        ->join('members m', 'rda.user_id = m.id', 'left');

        // Apply filters
        if (!empty($filters['user_id'])) {
            $query->where('rda.user_id', (int)$filters['user_id']);
        }
        if (!empty($filters['plan_type'])) {
            $query->where('rda.plan_type', $filters['plan_type']);
        }
        if (!empty($filters['roi_type'])) {
            $query->where('rda.roi_type', $filters['roi_type']);
        }
        if (!empty($filters['status'])) {
            $query->where('rda.status', $filters['status']);
        }
        if (!empty($filters['from_date'])) {
            $query->where('DATE(rda.actual_payment_date) >=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $query->where('DATE(rda.actual_payment_date) <=', $filters['to_date']);
        }

        return $query->order_by('rda.created_at', 'DESC')
                     ->limit($limit, $offset)
                     ->get()
                     ->result_array();
    }

    /**
     * Count ROI distribution records
     */
    public function countROIHistory($filters = [])
    {
        $query = $this->db->from('roi_distribution_audit');

        if (!empty($filters['user_id'])) {
            $query->where('user_id', (int)$filters['user_id']);
        }
        if (!empty($filters['plan_type'])) {
            $query->where('plan_type', $filters['plan_type']);
        }
        if (!empty($filters['roi_type'])) {
            $query->where('roi_type', $filters['roi_type']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->count_all_results();
    }

    /**
     * Get missed executions for a date range
     */
    public function getMissedExecutions($from_date = null, $to_date = null)
    {
        $from = $from_date ?? date('Y-m-d', strtotime('-30 days'));
        $to   = $to_date ?? date('Y-m-d');

        return $this->db
            ->where('DATE(execution_date) >=', $from)
            ->where('DATE(execution_date) <=', $to)
            ->where('status !=', 'success')
            ->order_by('execution_date', 'DESC')
            ->get('roi_cron_execution')
            ->result_array();
    }

    /**
     * Log cron execution
     */
    public function logCronExecution($data)
    {
        $exec_data = [
            'execution_date'           => $data['execution_date'] ?? date('Y-m-d'),
            'execution_day'            => (int)date('d', strtotime($data['execution_date'] ?? 'now')),
            'cron_type'                => $data['cron_type'],
            'status'                   => $data['status'] ?? 'running',
            'total_stakes_processed'   => (int)($data['total_stakes_processed'] ?? 0),
            'total_stakes_failed'      => (int)($data['total_stakes_failed'] ?? 0),
            'total_amount_distributed' => (float)($data['total_amount_distributed'] ?? 0),
            'error_logs'               => $data['error_logs'] ?? null,
            'execution_time_ms'        => (int)($data['execution_time_ms'] ?? 0),
            'started_at'               => date('Y-m-d H:i:s')
        ];

        $existing = $this->db
            ->where('execution_date', $exec_data['execution_date'])
            ->where('cron_type', $exec_data['cron_type'])
            ->get('roi_cron_execution')
            ->row_array();

        if ($existing) {
            $exec_data['retry_count'] = $existing['retry_count'] + 1;
            $this->db->where('id', $existing['id'])
                     ->update('roi_cron_execution', $exec_data);
            return $existing['id'];
        } else {
            $this->db->insert('roi_cron_execution', $exec_data);
            return $this->db->insert_id();
        }
    }

    /**
     * Get pending/failed ROI distributions for retry
     */
    public function getPendingROIForRetry()
    {
        return $this->db
            ->where_in('status', ['pending', 'failed'])
            ->where('retry_count <', 3)
            ->order_by('created_at', 'ASC')
            ->limit(100)
            ->get('roi_distribution_audit')
            ->result_array();
    }

    /**
     * Get ROI summary by user
     */
    public function getUserROISummary($user_id)
    {
        $result = $this->db
            ->select('
                plan_type,
                roi_type,
                COUNT(*) as count,
                SUM(roi_amount) as total_amount,
                MAX(actual_payment_date) as last_payment
            ')
            ->where('user_id', (int)$user_id)
            ->group_by('plan_type', 'roi_type')
            ->get('roi_distribution_audit')
            ->result_array();

        return array_reduce($result, function($carry, $item) {
            $key = $item['plan_type'].'_'.$item['roi_type'];
            $carry[$key] = $item;
            return $carry;
        }, []);
    }

    /**
     * Get ROI summary by date
     */
    public function getROISummaryByDate($from_date = null, $to_date = null)
    {
        $from = $from_date ?? date('Y-m-d', strtotime('-30 days'));
        $to   = $to_date ?? date('Y-m-d');

        return $this->db
            ->select('
                DATE(actual_payment_date) as payment_date,
                plan_type,
                roi_type,
                COUNT(*) as count,
                SUM(roi_amount) as total_amount
            ')
            ->where('DATE(actual_payment_date) >=', $from)
            ->where('DATE(actual_payment_date) <=', $to)
            ->where('status', 'success')
            ->group_by('payment_date', 'plan_type', 'roi_type')
            ->order_by('payment_date', 'DESC')
            ->get('roi_distribution_audit')
            ->result_array();
    }

    /**
     * Mark ROI as distributed in maturity schedule
     */
    public function markMaturityDistributed($stake_id, $tx_hash = null)
    {
        $this->db->where('stake_id', (int)$stake_id)
                 ->update('roi_maturity_schedule', [
                     'distributed'   => 1,
                     'distributed_at' => date('Y-m-d H:i:s'),
                     'tx_hash'        => $tx_hash
                 ]);
        return $this->db->affected_rows();
    }

    /**
     * Get pending maturity payouts
     */
    public function getPendingMaturityPayouts()
    {
        return $this->db
            ->where('maturity_date <=', date('Y-m-d'))
            ->where('distributed', 0)
            ->get('roi_maturity_schedule')
            ->result_array();
    }

    /**
     * Get upcoming maturity dates (next 30 days)
     */
    public function getUpcomingMaturityDates()
    {
        $today = date('Y-m-d');
        $future = date('Y-m-d', strtotime('+30 days'));

        return $this->db
            ->where('maturity_date >=', $today)
            ->where('maturity_date <=', $future)
            ->where('distributed', 0)
            ->order_by('maturity_date', 'ASC')
            ->get('roi_maturity_schedule')
            ->result_array();
    }
}
