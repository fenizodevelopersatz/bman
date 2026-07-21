<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * One row per scheduled-job execution (start-to-finish), not per credit —
 * individual money movements already live in wallet_ledger / onchain_transactions.
 */
class CronLog_model extends CI_Model
{
    public function record($cron_name, $status, array $summary = [], $duration_ms = null)
    {
        $this->db->insert('cron_execution_log', [
            'cron_name'   => $cron_name,
            'status'      => (string) $status,
            'summary'     => json_encode($summary),
            'duration_ms' => $duration_ms !== null ? (int) $duration_ms : null,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    public function recent($limit = 100, $cron_name = null)
    {
        if ($cron_name) $this->db->where('cron_name', $cron_name);
        return $this->db->order_by('id', 'DESC')->limit((int) $limit)
                        ->get('cron_execution_log')->result_array();
    }
}
