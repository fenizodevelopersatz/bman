<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Rankaudit_model — audit trail + member notifications for the rank system.
 * ----------------------------------------------------------------------------
 * Both writes are FAIL-SAFE by design: a logging or notification failure must
 * never roll back or block a promotion / reward that already succeeded. Every
 * method swallows its exceptions into application/logs and returns false.
 *
 * Audit rows land in `staking_rank_audit` (same shape as `staking_roi_audit`).
 * Notifications land in `user_notifications` — the legacy `notification` table
 * is (id, message, created_at) with no user_id and cannot carry these.
 */
class Rankaudit_model extends CI_Model
{
    /**
     * Append an audit row.
     *
     * @param string $event  rank_promoted | reward_paid | certificate_issued |
     *                       rank_config_changed | power_calculated | cycle_opened
     * @param array  $data   user_id, rank_id, old_value, new_value, changed_by, note
     */
    public function log($event, $data = [])
    {
        try {
            $this->db->insert('staking_rank_audit', [
                'event'      => substr((string)$event, 0, 40),
                'user_id'    => isset($data['user_id'])    ? (int)$data['user_id'] : null,
                'rank_id'    => isset($data['rank_id'])    ? (int)$data['rank_id'] : null,
                'old_value'  => isset($data['old_value'])  ? substr((string)$data['old_value'], 0, 120) : null,
                'new_value'  => isset($data['new_value'])  ? substr((string)$data['new_value'], 0, 120) : null,
                'changed_by' => isset($data['changed_by']) && $data['changed_by'] !== null
                                    ? (int)$data['changed_by'] : null,
                'note'       => isset($data['note'])       ? substr((string)$data['note'], 0, 255) : null,
            ]);
            return true;
        } catch (Throwable $e) {
            log_message('error', '[rank_audit] ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Notify one member. Idempotent through UNIQUE
     * (user_id, type, reference_type, reference_id) — the hourly cron can call
     * this repeatedly for the same promotion without spamming the member.
     *
     * INSERT IGNORE, not try/catch: CI3's driver does not throw on a duplicate
     * key — with db_debug on it halts the request via show_error(), and with it
     * off it returns FALSE. Neither is catchable, so the dedupe has to happen in
     * the statement itself.
     *
     * @return bool true when a NEW notification was created
     */
    public function notify($user_id, $type, $title, $message, $ref_type = null, $ref_id = null)
    {
        $this->db->query(
            'INSERT IGNORE INTO user_notifications
                (user_id, type, title, message, reference_type, reference_id)
             VALUES (?, ?, ?, ?, ?, ?)',
            [
                (int)$user_id,
                substr((string)$type, 0, 40),
                substr((string)$title, 0, 120),
                substr((string)$message, 0, 500),
                $ref_type !== null ? substr((string)$ref_type, 0, 40) : null,
                $ref_id !== null ? (int)$ref_id : null,
            ]
        );
        return $this->db->affected_rows() > 0;
    }

    /* ============================== reads =============================== */

    /** Audit feed for the admin page. $filters: event, user_id, from, to. */
    public function feed($filters = [], $limit = 100, $offset = 0)
    {
        $this->_applyFilters($filters);
        return $this->db->select('a.*, u.username, u.email, r.name AS rank_name')
                        ->from('staking_rank_audit a')
                        ->join('users u', 'u.id = a.user_id', 'left')
                        ->join('staking_ranks r', 'r.id = a.rank_id', 'left')
                        ->order_by('a.id', 'DESC')
                        ->limit((int)$limit, (int)$offset)
                        ->get()->result_array();
    }

    public function feedCount($filters = [])
    {
        $this->_applyFilters($filters, 'a');
        return $this->db->from('staking_rank_audit a')->count_all_results();
    }

    private function _applyFilters($f, $alias = 'a')
    {
        if (!empty($f['event']))   $this->db->where($alias.'.event', $f['event']);
        if (!empty($f['user_id'])) $this->db->where($alias.'.user_id', (int)$f['user_id']);
        if (!empty($f['from']))    $this->db->where($alias.'.created_at >=', $f['from'] . ' 00:00:00');
        if (!empty($f['to']))      $this->db->where($alias.'.created_at <=', $f['to'] . ' 23:59:59');
    }

    /** Distinct event names — populates the admin filter dropdown. */
    public function events()
    {
        $rows = $this->db->distinct()->select('event')->order_by('event')
                         ->get('staking_rank_audit')->result_array();
        return array_column($rows, 'event');
    }
}
