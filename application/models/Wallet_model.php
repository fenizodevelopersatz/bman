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

    // ⚠️ DEPRECATED: BONUS BALANCE (wallet_transactions removed - on-chain only)
    public function getBonusBalance($user_id)
    {
        // wallet_transactions table no longer used
        // $row = $this->db
        //     ->select("COALESCE(SUM({$this->b_amount}),0) AS amt", false)
        //     ->from($this->bonus_table)
        //     ->where($this->b_user_id, $user_id)
        //     ->where($this->b_tx_type, 'bonus')
        //     ->where($this->b_status, 'completed')
        //     ->get()
        //     ->row();

        return 0.0;  // On-chain only - no internal ledger
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
    /**
     * Real commission taxonomy on this platform — wallet_ledger credits,
     * keyed by reference_type (set at the site where each is credited):
     *   binary_matching -> BinaryMatchingPayoutCron
     *   roi             -> RoiMonthlyDistribution_cron / RoiMaturityPayment_cron
     *   swap_bonus      -> Swapengine_model (instant 25% bonus at stake purchase)
     *   rank_reward     -> Rankreward_model (§10 rank achievement)
     * Replaces the old `history` table buckets (binary_commission/level_commission/
     * direct_commission/rank_commission/site_withdraw), none of which the pairing-
     * era history rows ever actually used — history only ever holds staking_purchase
     * rows, so every one of those buckets was permanently empty.
     */
    private $commissionBucketMap = [
        'BINARY'  => ['binary_matching'],
        'ROI'     => ['roi'],
        'INSTANT' => ['swap_bonus'],
        'RANK'    => ['rank_reward'],
    ];

    /** Human title + icon key per reference_type, for the row label. */
    private function commissionTitle($refType)
    {
        switch ($refType) {
            case 'binary_matching': return 'Binary Matching Bonus';
            case 'roi':              return 'ROI';
            case 'swap_bonus':       return 'Instant Bonus';
            case 'rank_reward':      return 'Rank Reward';
            default:                 return ucfirst(str_replace('_', ' ', $refType));
        }
    }

    public function getCommissionHistory($user_id, $filters, $page, $per_page)
    {
        $page = max(1, (int) $page);
        $per_page = max(1, (int) $per_page);
        $offset = ($page - 1) * $per_page;

        $allowedTypes = array_values(array_unique(array_merge(...array_values($this->commissionBucketMap))));

        $where = ['user_id = ?', 'credit > 0'];
        $params = [$user_id];

        if (!empty($filters['q'])) {
            $q = '%' . $filters['q'] . '%';
            $where[] = '(reference_id LIKE ? OR description LIKE ?)';
            $params[] = $q; $params[] = $q;
        }

        if (!empty($filters['type']) && isset($this->commissionBucketMap[strtoupper(trim($filters['type']))])) {
            $types = $this->commissionBucketMap[strtoupper(trim($filters['type']))];
        } else {
            $types = $allowedTypes;
        }
        $placeholders = implode(',', array_fill(0, count($types), '?'));
        $where[] = "reference_type IN ($placeholders)";
        foreach ($types as $x) $params[] = $x;

        if (!empty($filters['from'])) { $where[] = 'DATE(created_at) >= ?'; $params[] = $filters['from']; }
        if (!empty($filters['to']))   { $where[] = 'DATE(created_at) <= ?'; $params[] = $filters['to']; }

        // Every wallet_ledger credit is, by definition, already posted — there
        // is no pending/rejected state at this layer (that only exists one
        // step earlier, e.g. rank_rewards.reward_status before it's credited
        // here). So a Pending/Rejected filter here can only ever match zero
        // rows — which is honest, not a bug: nothing in this table qualifies.
        if (!empty($filters['status']) && strtoupper(trim($filters['status'])) !== 'SUCCESS') {
            $where[] = '1=0';
        }

        $whereSql = 'WHERE ' . implode(' AND ', $where);

        $bucketCase = "
        CASE
            WHEN reference_type = 'binary_matching' THEN 'BINARY'
            WHEN reference_type = 'roi'              THEN 'ROI'
            WHEN reference_type = 'swap_bonus'       THEN 'INSTANT'
            WHEN reference_type = 'rank_reward'       THEN 'RANK'
            ELSE UPPER(reference_type)
        END
    ";

        $total = (int) $this->db->query("
        SELECT COUNT(*) AS c FROM wallet_ledger $whereSql
    ", $params)->row()->c;

        $pages = max(1, (int) ceil($total / $per_page));

        $rows = $this->db->query("
        SELECT
            id,
            ($bucketCase)     AS type,
            reference_type    AS raw_type,
            reference_id      AS ref,
            created_at        AS created_at,
            credit            AS amount,
            'SUCCESS'         AS status,
            description       AS note,
            tx_hash,
            wallet_type,
            COALESCE(
                (SELECT COALESCE(NULLIF(r2.staking_swap_orders_id, 0), NULLIF(r2.user_stakes_id, 0))
                   FROM roi_staking_management r2
                  WHERE r2.ref = CAST(wallet_ledger.reference_id AS BINARY) LIMIT 1),
                CASE
                    WHEN reference_id REGEXP '^ORDER-[0-9]+'
                        THEN CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(reference_id, '-', 2), '-', -1) AS UNSIGNED)
                    ELSE NULL
                END
            )                 AS order_id
        FROM wallet_ledger
        $whereSql
        ORDER BY created_at DESC, id DESC
        LIMIT ? OFFSET ?
    ", array_merge($params, [$per_page, $offset]))->result();

        foreach ($rows as $r) { $r->title = $this->commissionTitle($r->raw_type); }

        // ROI rows: attach the staking record behind the credit so the details
        // popup can explain the earning (plan, principal, rate, cycle progress)
        // instead of showing only the bare ledger line. One IN() query for the
        // page — string literals adopt the column's collation, so this needs
        // no CAST, unlike the cross-table compare above.
        $roiRefs = [];
        foreach ($rows as $r) {
            if ($r->raw_type === 'roi' && !empty($r->ref)) $roiRefs[$r->ref] = true;
        }
        if ($roiRefs) {
            $recs = $this->db->select('ref, plan_type, is_special, duration_years, principal_amount,
                    roi_rate_percent, total_roi_amount, total_paid_amount,
                    regular_payments_completed, regular_payment_count,
                    next_payment_date, fixed_maturity_date, overall_status', false)
                ->where_in('ref', array_keys($roiRefs))
                ->get('roi_staking_management')->result_array();
            $byRef = array_column($recs, null, 'ref');
            foreach ($rows as $r) {
                $r->roi_staking = ($r->raw_type === 'roi' && isset($byRef[$r->ref])) ? $byRef[$r->ref] : null;
            }
        } else {
            foreach ($rows as $r) { $r->roi_staking = null; }
        }

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
        $counts = ['ALL' => 0, 'BINARY' => 0, 'ROI' => 0, 'INSTANT' => 0, 'RANK' => 0];

        $allowedTypes = array_values(array_unique(array_merge(...array_values($this->commissionBucketMap))));

        $where = ['user_id = ?', 'credit > 0'];
        $params = [$user_id];

        $placeholders = implode(',', array_fill(0, count($allowedTypes), '?'));
        $where[] = "reference_type IN ($placeholders)";
        foreach ($allowedTypes as $x) $params[] = $x;

        if (!empty($filters['from'])) { $where[] = 'DATE(created_at) >= ?'; $params[] = $filters['from']; }
        if (!empty($filters['to']))   { $where[] = 'DATE(created_at) <= ?'; $params[] = $filters['to']; }

        $whereSql = 'WHERE ' . implode(' AND ', $where);

        $counts['ALL'] = (int) $this->db->query("
        SELECT COUNT(*) AS c FROM wallet_ledger $whereSql
    ", $params)->row()->c;

        $bucketCase = "
        CASE
            WHEN reference_type = 'binary_matching' THEN 'BINARY'
            WHEN reference_type = 'roi'              THEN 'ROI'
            WHEN reference_type = 'swap_bonus'       THEN 'INSTANT'
            WHEN reference_type = 'rank_reward'       THEN 'RANK'
            ELSE 'OTHER'
        END
    ";

        $rows = $this->db->query("
        SELECT ($bucketCase) AS t, COUNT(*) AS c
        FROM wallet_ledger
        $whereSql
        GROUP BY ($bucketCase)
    ", $params)->result();

        foreach ($rows as $r) {
            $k = strtoupper(trim($r->t));
            if (isset($counts[$k])) $counts[$k] = (int) $r->c;
        }

        return $counts;
    }


    // ✅ Summary cards values
    /**
     * "Pending Commission" — the only genuinely pending commission state in
     * this architecture: a rank reward the achievement engine has recorded
     * but not yet credited to the wallet (rank_rewards.reward_status =
     * 'pending'). Binary matching / ROI / instant bonus are never pending —
     * each is credited to wallet_ledger the moment it's earned, so there is
     * nothing "awaiting payout" for those. (Old implementation summed
     * user_investment, a table from the old e-commerce/investment module
     * with no relationship to BMAN staking or commissions at all.)
     */
    public function getPendingCommissionFromInvestments($user_id, $filters = '')
    {
        $row = $this->db->select('COALESCE(SUM(reward_amount),0) AS amt', false)
            ->from('rank_rewards')
            ->where('user_id', $user_id)
            ->where('reward_status', 'pending')
            ->get()->row();
        return (float) ($row->amt ?? 0);
    }

    /** Lifetime BMAN commissions — same source/buckets as getCommissionHistory(). */
    public function getTotalCommissionEarned($user_id)
    {
        $allowedTypes = array_values(array_unique(array_merge(...array_values($this->commissionBucketMap))));
        $placeholders = implode(',', array_fill(0, count($allowedTypes), '?'));
        $row = $this->db->query("
        SELECT COALESCE(SUM(credit),0) AS amt
        FROM wallet_ledger
        WHERE user_id = ? AND credit > 0
          AND reference_type IN ($placeholders)
    ", array_merge([$user_id], $allowedTypes))->row();

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
