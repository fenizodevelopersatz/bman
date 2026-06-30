<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Profit_model extends CI_Model
{
    // ✅ CHANGE THIS to your actual table name (the screenshot table)
    private $table = 'earnings'; // columns: id,user_id,tx_type,source,amount,status,created_at

    // map DB tx_type/source → UI types
    // You can adjust to match your app logic.
    private function mapType($tx_type, $source)
    {
        $tx_type = strtolower(trim((string) $tx_type));
        $source = strtolower(trim((string) $source));

        // Example mappings from your screenshot
        if ($tx_type === 'bonus')
            return 'RANK';     // or "RANK" / "LEADERSHIP" (choose what you want)
        if ($tx_type === 'earn')
            return 'DIRECT';   // earn from ads/videos = DIRECT

        // If you later add "pairing/matching" in tx_type
        if ($tx_type === 'pairing')
            return 'PAIRING';
        if ($tx_type === 'matching')
            return 'MATCHING';
        if ($tx_type === 'leadership')
            return 'LEADERSHIP';
        if ($tx_type === 'rank')
            return 'RANK';

        // Withdraw records if stored here
        if ($tx_type === 'withdraw')
            return 'WITHDRAW';

        return 'DIRECT';
    }

    private function mapStatus($status)
    {
        $s = strtolower(trim((string) $status));
        if ($s === 'completed' || $s === 'success' || $s === 'paid')
            return 'SUCCESS';
        if ($s === 'pending' || $s === 'hold')
            return 'PENDING';
        if ($s === 'rejected' || $s === 'failed' || $s === 'cancelled')
            return 'REJECTED';
        return strtoupper($status ?: 'PENDING');
    }

    // Apply filters to builder
    private function applyFilters($qb, $user_id, $filters)
    {
        $qb->where('user_id', $user_id);

        // date range
        if (!empty($filters['from']))
            $qb->where('DATE(created_at) >=', $filters['from']);
        if (!empty($filters['to']))
            $qb->where('DATE(created_at) <=', $filters['to']);

        // status filter (SUCCESS/PENDING/REJECTED) → DB status values
        if (!empty($filters['status'])) {
            $st = strtoupper($filters['status']);
            if ($st === 'SUCCESS')
                $qb->where_in('status', ['completed', 'success', 'paid']);
            elseif ($st === 'PENDING')
                $qb->where_in('status', ['pending', 'hold']);
            elseif ($st === 'REJECTED')
                $qb->where_in('status', ['rejected', 'failed', 'cancelled']);
        }

        // q filter searches source + tx_type (you can add more columns)
        if (!empty($filters['q'])) {
            $q = $filters['q'];
            $qb->group_start()
                ->like('source', $q)
                ->or_like('tx_type', $q)
                ->or_like('id', $q)
                ->group_end();
        }
    }

    public function sumPending($user_id, $filters)
    {
        $qb = $this->db->select('COALESCE(SUM(amount),0) AS amt', false)
            ->from($this->table);

        $tmp = $filters;
        $tmp['status'] = 'PENDING';
        $this->applyFilters($qb, $user_id, $tmp);

        $row = $qb->get()->row();
        return (float) ($row->amt ?? 0);
    }

    public function sumTotalEarned($user_id)
    {
        // lifetime success total
        $row = $this->db->select('COALESCE(SUM(amount),0) AS amt', false)
            ->from($this->table)
            ->where('user_id', $user_id)
            ->where_in('status', ['completed', 'success', 'paid'])
            ->get()->row();

        return (float) ($row->amt ?? 0);
    }

    public function sumPaidOut($user_id)
    {
        // if you store withdrawals in another table, replace this logic
        // Here we assume tx_type='withdraw' and status completed/paid
        $row = $this->db->select('COALESCE(SUM(amount),0) AS amt', false)
            ->from($this->table)
            ->where('user_id', $user_id)
            ->where('tx_type', 'withdraw')
            ->where_in('status', ['completed', 'paid', 'success'])
            ->get()->row();

        return (float) ($row->amt ?? 0);
    }

    public function getRows($user_id, $filters, $page, $per_page)
    {
        $page = max(1, (int) $page);
        $per_page = max(1, (int) $per_page);
        $offset = ($page - 1) * $per_page;

        // total count
        $qbCount = $this->db->from($this->table);
        $this->applyFilters($qbCount, $user_id, $filters);
        $total = (int) $qbCount->count_all_results();

        $pages = max(1, (int) ceil($total / $per_page));

        // data rows
        $qb = $this->db->select('id,user_id,tx_type,source,amount,status,created_at')
            ->from($this->table);
        $this->applyFilters($qb, $user_id, $filters);

        $rawRows = $qb->order_by('id', 'DESC')
            ->limit($per_page, $offset)
            ->get()->result();

        // map to view expected fields
        $rows = [];
        foreach ($rawRows as $r) {
            $type = $this->mapType($r->tx_type, $r->source);
            $rows[] = (object) [
                'type' => $type,
                'title' => ucfirst(strtolower($type)) . ' Income',
                'ref' => 'TXN#' . $r->id . ($r->source ? (' • ' . ucfirst($r->source)) : ''),
                'created_at' => $r->created_at,
                'amount' => (float) $r->amount,
                'status' => $this->mapStatus($r->status),
                'note' => 'Source: ' . ($r->source ?: '-'),
                'from_user' => '',     // keep empty if not available
                'level' => '',
                'order_id' => '',
            ];
        }

        // counts per type (for chips) - within date range (same filters but without type)
        $counts = $this->buildCounts($user_id, $filters);

        return [
            'rows' => $rows,
            'counts' => $counts,
            'paging' => [
                'page' => $page,
                'pages' => $pages,
                'total' => $total,
                'per_page' => $per_page,
            ]
        ];
    }

    private function buildCounts($user_id, $filters)
    {
        $counts = ['ALL' => 0, 'PAIRING' => 0, 'MATCHING' => 0, 'DIRECT' => 0, 'RANK' => 0, 'LEADERSHIP' => 0, 'WITHDRAW' => 0];

        // ALL count in range/status/q
        $qbAll = $this->db->from($this->table);
        $this->applyFilters($qbAll, $user_id, $filters);
        $counts['ALL'] = (int) $qbAll->count_all_results();

        // Fetch rows for counting by mapped type (simple + safe)
        $qb = $this->db->select('tx_type,source')
            ->from($this->table);
        $this->applyFilters($qb, $user_id, $filters);
        $list = $qb->get()->result();

        foreach ($list as $r) {
            $t = $this->mapType($r->tx_type, $r->source);
            if (isset($counts[$t]))
                $counts[$t]++;
        }

        return $counts;
    }
}
