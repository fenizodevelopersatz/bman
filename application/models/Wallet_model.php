<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Wallet_model extends CI_Model
{
    // ✅ YOUR TABLES
    private $history_table = 'history';
    private $withdraw_table = 'withdrawals'; // <-- change if your table name differs

    private $bonus_table = 'wallet_transactions';

    private $b_user_id = 'user_id';
    private $b_tx_type = 'tx_type';
    private $b_amount = 'amount';
    private $b_status = 'status';

    // ✅ HISTORY columns (based on your insert)
    private $h_user_id = 'user_id';
    private $h_amount = 'amount';
    private $h_type = 'type';          // bonus / commission / withdraw / transfer / order etc
    private $h_status = 'status';        // '1' success, maybe '0' failed etc
    private $h_date = 'history_date';  // used as created_at for UI
    private $h_hash = 'hash_id';
    private $h_desc = 'description';

    // ✅ WITHDRAW columns (EDIT these to match your schema)
    private $w_user_id = 'user_id';
    private $w_amount = 'amount';
    private $w_status = 'status';
    private $w_date = 'created_at';

    // Map your withdraw status values here
    private $withdraw_pending = ['PENDING', '0'];             // edit
    private $withdraw_success = ['SUCCESS', 'APPROVED', '1'];   // edit

    // -------------------------
    // ✅ BONUS BALANCE (history type=bonus)
    // -------------------------
    public function getBonusBalance($user_id)
    {
        $row = $this->db
            ->select("COALESCE(SUM({$this->b_amount}),0) AS amt", false)
            ->from($this->bonus_table)
            ->where($this->b_user_id, $user_id)
            ->where($this->b_tx_type, 'bonus')
            ->where($this->b_status, 'completed')
            ->get()
            ->row();

        return (float) ($row->amt ?? 0);
    }

    public function getTotalEarnedBonusBalance($user_id)
    {
        $row = $this->db
            ->select("COALESCE(SUM({$this->b_amount}),0) AS amt", false)
            ->from($this->bonus_table)
            ->where($this->b_user_id, $user_id)
            ->where_in($this->b_tx_type, ['bonus', 'earn'])
            ->where($this->b_status, 'completed')
            ->get()
            ->row();

        return (float) ($row->amt ?? 0);
    }
    // -------------------------
    // ✅ COMMISSION BALANCE (history type=commission)
    // -------------------------
    public function getCommissionBalance($user_id)
    {
        $row = $this->db
            ->select("COALESCE(SUM({$this->h_amount}),0) AS amt", false)
            ->from($this->history_table)
            ->where($this->h_user_id, $user_id)
            ->like($this->h_type, 'commission')
            ->where($this->h_status, '1')
            ->get()
            ->row();

        return (float) ($row->amt ?? 0);
    }


    // -------------------------
    // ✅ TOTAL EARNED (bonus + commission)
    // -------------------------
    public function getTotalEarned($user_id)
    {
        $row = $this->db->query("
            SELECT COALESCE(SUM({$this->h_amount}),0) AS amt
            FROM {$this->history_table}
            WHERE {$this->h_user_id}=? AND {$this->h_status}='1'
              AND {$this->h_type} IN ('bonus','commission')
        ", [$user_id])->row();

        return (float) ($row->amt ?? 0);
    }

    // -------------------------
    // ✅ PENDING WITHDRAW (withdraw table)
    // -------------------------
    public function getPendingWithdraw($user_id)
    {
        if (!$this->db->table_exists($this->withdraw_table))
            return 0.0;

        $in = $this->sqlIn($this->withdraw_pending);
        $params = array_merge([$user_id], $this->withdraw_pending);

        $row = $this->db->query("
            SELECT COALESCE(SUM({$this->w_amount}),0) AS amt
            FROM {$this->withdraw_table}
            WHERE {$this->w_user_id}=? AND {$this->w_status} IN ($in)
        ", $params)->row();

        return (float) ($row->amt ?? 0);
    }

    // -------------------------
    // ✅ TOTAL WITHDRAWN (withdraw table)
    // -------------------------
    public function getTotalWithdrawn($user_id)
    {
        if (!$this->db->table_exists($this->withdraw_table))
            return 0.0;

        $in = $this->sqlIn($this->withdraw_success);
        $params = array_merge([$user_id], $this->withdraw_success);

        $row = $this->db->query("
            SELECT COALESCE(SUM({$this->w_amount}),0) AS amt
            FROM {$this->withdraw_table}
            WHERE {$this->w_user_id}=? AND {$this->w_status} IN ($in)
        ", $params)->row();

        return (float) ($row->amt ?? 0);
    }

    // -------------------------
    // ✅ WALLET HISTORY LIST FOR TABLE + FILTERS + COUNTS + PAGING
    // -------------------------
    // public function getWalletHistory($user_id, $filters, $page, $per_page)
    // {
    //     $page = max(1, (int) $page);
    //     $per_page = max(1, (int) $per_page);
    //     $offset = ($page - 1) * $per_page;

    //     $where = [];
    //     $params = [];

    //     $where[] = "{$this->h_user_id} = ?";
    //     $params[] = $user_id;

    //     // q search: title-ish fields
    //     if (!empty($filters['q'])) {
    //         $q = '%' . $filters['q'] . '%';
    //         $where[] = "( {$this->h_hash} LIKE ? OR {$this->h_desc} LIKE ? )";
    //         $params[] = $q;
    //         $params[] = $q;
    //     }

    //     // type mapping from UI -> history.type
    //     // UI sends CREDIT/DEBIT/WITHDRAW/TRANSFER/COMMISSION/ORDER
    //     if (!empty($filters['type'])) {
    //         $t = strtolower($filters['type']);

    //         // your history types are lowercase like 'bonus','commission'...
    //         // map UI types to your history.type
    //         $map = [
    //             'credit' => 'credit',
    //             'debit' => 'debit',
    //             'withdraw' => 'withdraw',
    //             'transfer' => 'transfer',
    //             'commission' => 'commission',
    //             'order' => 'order',
    //         ];

    //         if (isset($map[$t])) {
    //             $where[] = "{$this->h_type} = ?";
    //             $params[] = $map[$t];
    //         }
    //     }

    //     // status mapping (UI: SUCCESS/PENDING/FAILED)
    //     // Your history uses status '1' = success (based on your insert)
    //     if (!empty($filters['status'])) {
    //         $s = strtoupper($filters['status']);
    //         if ($s === 'SUCCESS') {
    //             $where[] = "{$this->h_status} = '1'";
    //         } elseif ($s === 'FAILED') {
    //             $where[] = "{$this->h_status} = '0'";
    //         } elseif ($s === 'PENDING') {
    //             // if you use '2' for pending, change here
    //             $where[] = "{$this->h_status} = '2'";
    //         }
    //     }

    //     // date range (history_date)
    //     if (!empty($filters['from'])) {
    //         $where[] = "DATE({$this->h_date}) >= ?";
    //         $params[] = $filters['from'];
    //     }
    //     if (!empty($filters['to'])) {
    //         $where[] = "DATE({$this->h_date}) <= ?";
    //         $params[] = $filters['to'];
    //     }

    //     $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    //     // total
    //     $total = (int) $this->db->query("
    //         SELECT COUNT(*) AS c
    //         FROM {$this->history_table}
    //         $whereSql
    //     ", $params)->row()->c;

    //     $pages = max(1, (int) ceil($total / $per_page));
    //     $rows = $this->db->query("
    //             SELECT
    //             id,
    //             {$this->h_amount} AS amount,

    //             CASE
    //                 WHEN {$this->h_type} = 'profit' THEN 'roi'
    //                 ELSE {$this->h_type}
    //             END AS type,

    //             {$this->h_status} AS status,
    //             {$this->h_date} AS created_at,
    //             {$this->h_hash} AS ref,
    //             {$this->h_desc} AS note,

    //             CONCAT(
    //                 UCASE(LEFT(
    //                 CASE
    //                     WHEN {$this->h_type} = 'profit' THEN 'roi'
    //                     ELSE {$this->h_type}
    //                 END, 1)),
    //                 SUBSTRING(
    //                 CASE
    //                     WHEN {$this->h_type} = 'profit' THEN 'roi'
    //                     ELSE {$this->h_type}
    //                 END, 2),
    //                 ' Transaction'
    //             ) AS title

    //             FROM {$this->history_table}
    //             $whereSql
    //             ORDER BY {$this->h_date} DESC
    //             LIMIT ? OFFSET ?
    //         ", array_merge($params, [$per_page, $offset]))->result();


    //     // counts for chips
    //     $counts = $this->buildCounts($user_id);

    //     return [
    //         'rows' => $rows,
    //         'counts' => $counts,
    //         'paging' => [
    //             'page' => $page,
    //             'pages' => $pages,
    //             'total' => $total,
    //             'per_page' => $per_page,
    //         ]
    //     ];
    // }


    // -------------------------
// ✅ WALLET HISTORY LIST FOR TABLE + FILTERS + COUNTS + PAGING
// -------------------------
    public function getWalletHistory($user_id, $filters, $page, $per_page)
    {
        $page = max(1, (int) $page);
        $per_page = max(1, (int) $per_page);
        $offset = ($page - 1) * $per_page;

        $where = [];
        $params = [];

        $where[] = "{$this->h_user_id} = ?";
        $params[] = $user_id;

        // q search: title-ish fields
        if (!empty($filters['q'])) {
            $q = '%' . $filters['q'] . '%';
            $where[] = "( {$this->h_hash} LIKE ? OR {$this->h_desc} LIKE ? )";
            $params[] = $q;
            $params[] = $q;
        }

        // type mapping from UI -> history.type
        // UI sends CREDIT/DEBIT/WITHDRAW/TRANSFER/COMMISSION/ORDER
        if (!empty($filters['type'])) {
            $t = strtolower($filters['type']);

            // ✅ filter must match same rules as buildCounts (LIKE variants)
            if ($t === 'credit') {
                $where[] = "({$this->h_type} = ? OR {$this->h_type} LIKE ?)";
                $params[] = 'credit';
                $params[] = '%credit%';
            } elseif ($t === 'debit') {
                $where[] = "({$this->h_type} = ? OR {$this->h_type} LIKE ?)";
                $params[] = 'debit';
                $params[] = '%debit%';
            } elseif ($t === 'withdraw') {
                $where[] = "({$this->h_type} = ? OR {$this->h_type} LIKE ?)";
                $params[] = 'withdraw';
                $params[] = '%withdraw%';
            } elseif ($t === 'transfer') {
                $where[] = "({$this->h_type} = ? OR {$this->h_type} LIKE ?)";
                $params[] = 'transfer';
                $params[] = '%transfer%';
            } elseif ($t === 'commission') {
                $where[] = "({$this->h_type} = ? OR {$this->h_type} LIKE ?)";
                $params[] = 'commission';
                $params[] = '%commission%';
            } elseif ($t === 'order') {
                $where[] = "({$this->h_type} = ? OR {$this->h_type} LIKE ?)";
                $params[] = 'order';
                $params[] = '%order%';
            }
        }

        // status mapping (UI: SUCCESS/PENDING/FAILED)
        // Your history uses status '1' = success (based on your insert)
        if (!empty($filters['status'])) {
            $s = strtoupper($filters['status']);
            if ($s === 'SUCCESS') {
                $where[] = "{$this->h_status} = '1'";
            } elseif ($s === 'FAILED') {
                $where[] = "{$this->h_status} = '0'";
            } elseif ($s === 'PENDING') {
                // if you use '2' for pending, change here
                $where[] = "{$this->h_status} = '2'";
            }
        }

        // date range (history_date)
        if (!empty($filters['from'])) {
            $where[] = "DATE({$this->h_date}) >= ?";
            $params[] = $filters['from'];
        }
        if (!empty($filters['to'])) {
            $where[] = "DATE({$this->h_date}) <= ?";
            $params[] = $filters['to'];
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        // total
        $total = (int) $this->db->query("
        SELECT COUNT(*) AS c
        FROM {$this->history_table}
        $whereSql
    ", $params)->row()->c;

        $pages = max(1, (int) ceil($total / $per_page));

        $rows = $this->db->query("
        SELECT
            id,
            {$this->h_amount} AS amount,

            CASE
                WHEN {$this->h_type} = 'profit' THEN 'roi'
                ELSE {$this->h_type}
            END AS type,

            {$this->h_status} AS status,
            {$this->h_date} AS created_at,
            {$this->h_hash} AS ref,
            {$this->h_desc} AS note,

            CONCAT(
                UCASE(LEFT(
                    CASE
                        WHEN {$this->h_type} = 'profit' THEN 'roi'
                        ELSE {$this->h_type}
                    END, 1
                )),
                SUBSTRING(
                    CASE
                        WHEN {$this->h_type} = 'profit' THEN 'roi'
                        ELSE {$this->h_type}
                    END, 2
                ),
                ' Transaction'
            ) AS title

        FROM {$this->history_table}
        $whereSql
        ORDER BY {$this->h_date} DESC
        LIMIT ? OFFSET ?
    ", array_merge($params, [$per_page, $offset]))->result();

        // counts for chips
        $counts = $this->buildCounts($user_id);

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

    private function buildCounts($user_id)
    {
        $user_id = (int) $user_id;

        $counts = [
            'ALL' => 0,
            'CREDIT' => 0,
            'DEBIT' => 0,
            'WITHDRAW' => 0,
            'TRANSFER' => 0,
            'COMMISSION' => 0,
            'ORDER' => 0,
        ];

        // ✅ One query: get ALL counts at once (includes type variants)
        $row = $this->db->query("
        SELECT
            COUNT(*) AS all_count,

            SUM(CASE WHEN {$this->h_type} = 'credit'     OR {$this->h_type} LIKE '%credit%'     THEN 1 ELSE 0 END) AS credit_count,
            SUM(CASE WHEN {$this->h_type} = 'debit'      OR {$this->h_type} LIKE '&debit%'      THEN 1 ELSE 0 END) AS debit_count,
            SUM(CASE WHEN {$this->h_type} = 'withdraw'   OR {$this->h_type} LIKE '%withdraw%'   THEN 1 ELSE 0 END) AS withdraw_count,
            SUM(CASE WHEN {$this->h_type} = 'transfer'   OR {$this->h_type} LIKE '%transfer%'   THEN 1 ELSE 0 END) AS transfer_count,
            SUM(CASE WHEN {$this->h_type} = 'commission' OR {$this->h_type} LIKE '%commission%' THEN 1 ELSE 0 END) AS commission_count,
            SUM(CASE WHEN {$this->h_type} = 'order'      OR {$this->h_type} LIKE '%order%'      THEN 1 ELSE 0 END) AS order_count

        FROM {$this->history_table}
        WHERE {$this->h_user_id} = ?
    ", [$user_id])->row();

        $counts['ALL'] = (int) ($row->all_count ?? 0);
        $counts['CREDIT'] = (int) ($row->credit_count ?? 0);
        $counts['DEBIT'] = (int) ($row->debit_count ?? 0);
        $counts['WITHDRAW'] = (int) ($row->withdraw_count ?? 0);
        $counts['TRANSFER'] = (int) ($row->transfer_count ?? 0);
        $counts['COMMISSION'] = (int) ($row->commission_count ?? 0);
        $counts['ORDER'] = (int) ($row->order_count ?? 0);

        return $counts;
    }




    private function sqlIn(array $arr)
    {
        return implode(',', array_fill(0, count($arr), '?'));
    }


    // ✅ Commission table source = history (bucketed types)
    public function getCommissionHistory($user_id, $filters, $page, $per_page)
    {
        $page = max(1, (int) $page);
        $per_page = max(1, (int) $per_page);
        $offset = ($page - 1) * $per_page;

        // ✅ UI -> history.type buckets
        $bucketMap = [
            'PAIRING' => ['binary_commission'],                 // Pairing chip
            'MATCHING' => ['level_commission', 'matching_bonus'],// Matching chip (add/remove as per your project)
            'DIRECT' => ['direct_commission'],                 // Direct chip
            'RANK' => ['rank_commission'],                   // Rank chip
            'WITHDRAW' => ['site_withdraw'],                     // Withdraw chip
        ];

        // all allowed types for this page
        $allowedTypes = array_values(array_unique(array_merge(...array_values($bucketMap))));

        $where = ["{$this->h_user_id}=?"];
        $params = [$user_id];

        // search
        if (!empty($filters['q'])) {
            $q = '%' . $filters['q'] . '%';
            $where[] = "({$this->h_hash} LIKE ? OR {$this->h_desc} LIKE ?)";
            $params[] = $q;
            $params[] = $q;
        }

        // ✅ Type filter (bucket)
        if (!empty($filters['type'])) {
            $t = strtoupper(trim($filters['type']));
            if (isset($bucketMap[$t])) {
                $types = $bucketMap[$t];
                $placeholders = implode(',', array_fill(0, count($types), '?'));
                $where[] = "{$this->h_type} IN ($placeholders)";
                foreach ($types as $x)
                    $params[] = $x;
            } else {
                // unknown type => fallback to allowed types
                $placeholders = implode(',', array_fill(0, count($allowedTypes), '?'));
                $where[] = "{$this->h_type} IN ($placeholders)";
                foreach ($allowedTypes as $x)
                    $params[] = $x;
            }
        } else {
            // default = only these buckets
            $placeholders = implode(',', array_fill(0, count($allowedTypes), '?'));
            $where[] = "{$this->h_type} IN ($placeholders)";
            foreach ($allowedTypes as $x)
                $params[] = $x;
        }

        // date
        if (!empty($filters['from'])) {
            $where[] = "DATE({$this->h_date}) >= ?";
            $params[] = $filters['from'];
        }
        if (!empty($filters['to'])) {
            $where[] = "DATE({$this->h_date}) <= ?";
            $params[] = $filters['to'];
        }

        // status UI -> history.status
        if (!empty($filters['status'])) {
            $s = strtoupper(trim($filters['status']));
            if ($s === 'SUCCESS')
                $where[] = "{$this->h_status}='1'";
            elseif ($s === 'PENDING')
                $where[] = "{$this->h_status}='2'";
            elseif ($s === 'REJECTED')
                $where[] = "{$this->h_status}='0'";
        }

        $whereSql = "WHERE " . implode(" AND ", $where);

        // ✅ bucket label for UI (CASE)
        $bucketCase = "
        CASE
            WHEN {$this->h_type} IN ('binary_commission') THEN 'PAIRING'
            WHEN {$this->h_type} IN ('level_commission','matching_bonus') THEN 'MATCHING'
            WHEN {$this->h_type} IN ('direct_commission') THEN 'DIRECT'
            WHEN {$this->h_type} IN ('rank_commission') THEN 'RANK'
            WHEN {$this->h_type} IN ('site_withdraw') THEN 'WITHDRAW'
            ELSE UPPER({$this->h_type})
        END
    ";

        // total
        $total = (int) $this->db->query("
        SELECT COUNT(*) AS c
        FROM {$this->history_table}
        $whereSql
    ", $params)->row()->c;

        $pages = max(1, (int) ceil($total / $per_page));

        // rows
        $rows = $this->db->query("
        SELECT
            id,
            ($bucketCase)           AS type,         -- ✅ bucketed type for UI
            {$this->h_type}         AS raw_type,     -- ✅ original history.type (optional)
            {$this->h_hash}         AS ref,
            {$this->h_date}         AS created_at,
            {$this->h_amount}       AS amount,
            {$this->h_status}       AS status,
            {$this->h_desc}         AS note
        FROM {$this->history_table}
        $whereSql
        ORDER BY {$this->h_date} DESC
        LIMIT ? OFFSET ?
    ", array_merge($params, [$per_page, $offset]))->result();

        // counts for chips
        $counts = $this->getCommissionCounts($user_id, $filters);

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

    public function getCommissionCounts($user_id, $filters)
    {
        $counts = [
            'ALL' => 0,
            'PAIRING' => 0,
            'MATCHING' => 0,
            'DIRECT' => 0,
            'RANK' => 0,
            'WITHDRAW' => 0,
        ];

        // same bucket map
        $bucketMap = [
            'PAIRING' => ['binary_commission'],
            'MATCHING' => ['level_commission', 'matching_bonus'],
            'DIRECT' => ['direct_commission'],
            'RANK' => ['rank_commission'],
            'WITHDRAW' => ['site_withdraw'],
        ];

        $allowedTypes = array_values(array_unique(array_merge(...array_values($bucketMap))));

        $where = ["{$this->h_user_id}=?"];
        $params = [$user_id];

        // limit counts to these buckets only
        $placeholders = implode(',', array_fill(0, count($allowedTypes), '?'));
        $where[] = "{$this->h_type} IN ($placeholders)";
        foreach ($allowedTypes as $x)
            $params[] = $x;

        if (!empty($filters['from'])) {
            $where[] = "DATE({$this->h_date}) >= ?";
            $params[] = $filters['from'];
        }
        if (!empty($filters['to'])) {
            $where[] = "DATE({$this->h_date}) <= ?";
            $params[] = $filters['to'];
        }

        $whereSql = "WHERE " . implode(" AND ", $where);

        // ALL
        $counts['ALL'] = (int) $this->db->query("
        SELECT COUNT(*) AS c
        FROM {$this->history_table}
        $whereSql
    ", $params)->row()->c;

        // ✅ group into buckets using CASE
        $bucketCase = "
        CASE
            WHEN {$this->h_type} IN ('binary_commission') THEN 'PAIRING'
            WHEN {$this->h_type} IN ('level_commission','matching_bonus') THEN 'MATCHING'
            WHEN {$this->h_type} IN ('direct_commission') THEN 'DIRECT'
            WHEN {$this->h_type} IN ('rank_commission') THEN 'RANK'
            WHEN {$this->h_type} IN ('site_withdraw') THEN 'WITHDRAW'
            ELSE 'OTHER'
        END
    ";

        $rows = $this->db->query("
        SELECT ($bucketCase) AS t, COUNT(*) AS c
        FROM {$this->history_table}
        $whereSql
        GROUP BY ($bucketCase)
    ", $params)->result();

        foreach ($rows as $r) {
            $k = strtoupper(trim($r->t));
            if (isset($counts[$k]))
                $counts[$k] = (int) $r->c;
        }

        return $counts;
    }


    // ✅ Summary cards values
    public function getPendingCommissionFromInvestments($user_id, $filters = '')
    {
        $from = !empty($filters['from']) ? $filters['from'] : date('Y-m-d', strtotime('-29 days'));
        $to = !empty($filters['to']) ? $filters['to'] : date('Y-m-d');

        // ✅ Pick which date column defines the "investment date window"
        // Options: created_date, starting_date, run_date
        // Usually created_date or starting_date is best.
        $date_col = 'created_date';

        // ✅ Pending investments:
        // In your data status=2 looks like matured/closed. If you want ONLY active pending use status=1.
        // I'll keep BOTH 1 and 2 because you didn't confirm logic.
        $statuses = [1, 2];

        // Build placeholders for IN()
        $in = implode(',', array_fill(0, count($statuses), '?'));
        $params = array_merge([$user_id], $statuses, [$from, $to]);

        // ✅ percentage wise sum
        // pending = SUM(invest_amount * profit / 100)
        $sql = "
        SELECT COALESCE(SUM(invest_amount * profit / 100), 0) AS amt
        FROM user_investment
        WHERE user_id = ?
          AND status IN ($in)
          AND DATE($date_col) BETWEEN ? AND ?
    ";

        $row = $this->db->query($sql, $params)->row();

        _dbg('getPendingCommissionFromInvestments', $this->db->last_query());
        return (float) ($row->amt ?? 0);
    }


    public function getTotalCommissionEarned($user_id)
    {
        $row = $this->db->query("
        SELECT COALESCE(SUM({$this->h_amount}),0) AS amt
        FROM {$this->history_table}
        WHERE {$this->h_user_id} = ?
          AND {$this->h_status} = '1'
          AND (
                {$this->h_type} LIKE '%commission%'
                OR {$this->h_type} = 'profit'
          )
    ", [$user_id])->row();

        return (float) ($row->amt ?? 0);
    }

    public function getTotalCommissionPaid($user_id)
    {
        // paid out = withdraw type success
        $row = $this->db->query("
            SELECT COALESCE(SUM({$this->h_amount}),0) AS amt
            FROM {$this->history_table}
            WHERE {$this->h_user_id}=? 
            AND {$this->h_type}='site_withdraw'
            AND {$this->h_status}='2'            
        ", [$user_id])->row();

        return (float) ($row->amt ?? 0);
    }

}
