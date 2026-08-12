<?php
class Announcement_model extends CI_Model {

    public function __construct() {
        $this->load->database();
    }

    public function get_info($limit, $start) {
    $this->db->limit($limit, $start);
    $query = $this->db->get('announcement');
    return $query->result_array();
    }

    public function get_count() {
    $query = $this->db->get('announcement');
    return $query->num_rows();
    }

    /**
     * Active announcements visible to $userId — filters by status, schedule
     * window, and audience targeting, sorted by priority (critical first).
     * Targeting is resolved in PHP over the (always small) active-row set
     * rather than one mega-join, since each target_type needs a different
     * lookup table.
     */
    public function getActiveForUser($userId)
    {
        $today = date('Y-m-d');
        $rows = $this->db->where('title_status', 1)
            ->group_start()
                ->where('start_date IS NULL', null, false)
                ->or_where('start_date <=', $today)
            ->group_end()
            ->group_start()
                ->where('end_date IS NULL', null, false)
                ->or_where('end_date >=', $today)
            ->group_end()
            ->get('announcement')->result_array();

        $rows = array_values(array_filter($rows, function ($r) use ($userId) {
            return $this->_matchesTarget($r, $userId);
        }));

        $rank = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];
        usort($rows, function ($a, $b) use ($rank) {
            $pa = $rank[$a['priority']] ?? 2;
            $pb = $rank[$b['priority']] ?? 2;
            if ($pa !== $pb) return $pa <=> $pb;
            return strcmp($b['created_date'] ?? '', $a['created_date'] ?? '');
        });

        return $rows;
    }

    private function _matchesTarget(array $row, $userId)
    {
        $type = $row['target_type'] ?? 'all';
        $value = $row['target_value'] ?? '';
        if ($type === 'all' || $type === '') return true;

        if ($type === 'active' || $type === 'inactive') {
            $status = (int) ($this->db->select('status')->where('id', (int) $userId)->get('users')->row()->status ?? 0);
            return $type === 'active' ? $status === 1 : $status !== 1;
        }

        if ($type === 'kyc_pending' || $type === 'kyc_approved') {
            $kycStatus = $this->db->select('status')->where('user_id', (int) $userId)
                ->order_by('id', 'DESC')->get('kyc_applications')->row();
            $status = $kycStatus->status ?? null;
            // "KYC Pending" covers both users mid-review AND users who never
            // submitted an application at all — from the platform's
            // perspective both are simply "not yet KYC-approved".
            if ($type === 'kyc_pending') return $status === null || in_array($status, ['pending', 'under_review'], true);
            return $status === 'approved';
        }

        if ($type === 'rank') {
            $row2 = $this->db->select('sr.name, sr.tier_level')
                ->from('user_ranks ur')
                ->join('staking_ranks sr', 'sr.id = ur.highest_rank_id')
                ->where('ur.user_id', (int) $userId)->get()->row();
            if (!$row2) return false;
            return strcasecmp((string) $row2->name, (string) $value) === 0
                || (string) $row2->tier_level === (string) $value;
        }

        if ($type === 'package') {
            return (bool) $this->db->where('user_id', (int) $userId)
                ->where('package_id', (int) $value)
                ->where_in('status', ['active', 'processing'])
                ->count_all_results('user_stakes');
        }

        if ($type === 'country') {
            $country = $this->db->select('country')->where('id', (int) $userId)->get('users')->row()->country ?? '';
            return $value !== '' && stripos((string) $country, (string) $value) !== false;
        }

        return true;
    }

    public function trackView($id)
    {
        $this->db->where('id', (int) $id)->set('views', 'views + 1', false)->update('announcement');
    }

    public function trackClick($id)
    {
        $this->db->where('id', (int) $id)->set('clicks', 'clicks + 1', false)->update('announcement');
    }

    public function trackDismiss($id, $userId)
    {
        $id = (int) $id;
        $userId = (int) $userId;
        if (!$id || !$userId) return;
        $this->db->query(
            "INSERT INTO announcement_dismissals (announcement_id, user_id, dismissed_at) VALUES (?, ?, NOW())
             ON DUPLICATE KEY UPDATE dismissed_at = NOW()",
            [$id, $userId]
        );
    }

    public function isDismissed($id, $userId)
    {
        return (bool) $this->db->where(['announcement_id' => (int) $id, 'user_id' => (int) $userId])
            ->count_all_results('announcement_dismissals');
    }

    /** Views/clicks/CTR for the admin list — one row per announcement. */
    public function analytics()
    {
        $rows = $this->db->select('id, views, clicks')->get('announcement')->result_array();
        $out = [];
        foreach ($rows as $r) {
            $views = (int) $r['views'];
            $clicks = (int) $r['clicks'];
            $out[(int) $r['id']] = [
                'views' => $views,
                'clicks' => $clicks,
                'ctr' => $views > 0 ? round($clicks * 100 / $views, 1) : 0,
            ];
        }
        return $out;
    }

}
?>
