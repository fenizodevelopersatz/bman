<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class BinaryModel extends CI_Model
{

    public function getUserById($user_id)
    {
        return $this->db->get_where('users', ['id' => $user_id])->row();
    }

    public function getAllMembers($user_id)
    {

        $this->db->select('
            users.id, 
            users.username, 
            users.email, 
            users.register_date,
            binary_placement.parent_id as parent_id, 
            binary_placement.sponsor_id as sponsor_id, 
            binary_placement.position as position, 
            binary_placement.placement_type as placement_type
        ');
        $this->db->from('users');
        $this->db->join('binary_placement', 'users.id = binary_placement.user_id', 'left');
        return $this->db->get()->result();

    }

    // $maxDepth caps how many levels below $user_id are walked (null = unbounded).
    // Bounding this matters once trees get deep/wide: without it every call walks the
    // ENTIRE downline no matter what depth the caller actually wants to display.
    //
    // Fetches the whole bounded subtree in a fixed, small number of queries
    // (one recursive CTE for structure, then one batched lookup each for user
    // info / wallets / lock wallet / legacy investment) instead of the
    // previous per-node recursion — that walked one query per parent PLUS two
    // more per node (wallets, investment), which scales badly on a large team.
    public function getDownlineMembers($user_id, $maxDepth = null)
    {
        $user_id = (int) $user_id;

        $this->db->select('
        users.id,
        users.username,
        users.email,
        users.register_date,
        users.profile_img,
        users.image,
        binary_placement.parent_id as parent_id,
        binary_placement.sponsor_id as sponsor_id,
        binary_placement.position as position,
        binary_placement.placement_type as placement_type
        ');
        $this->db->from('users');
        $this->db->join('binary_placement', 'users.id = binary_placement.user_id', 'left');
        $this->db->where('users.id', $user_id);
        $direct_user = $this->db->get()->row();
        if (!$direct_user) return [];

        // Bounded subtree structure — one query regardless of team size/depth.
        $depthLimit = $maxDepth !== null ? (int) $maxDepth : 999999;
        $sql = "
            WITH RECURSIVE downline AS (
                SELECT bp.user_id, bp.parent_id, bp.sponsor_id, bp.position, bp.placement_type, 1 AS depth
                FROM binary_placement bp
                WHERE bp.parent_id = ?

                UNION ALL

                SELECT c.user_id, c.parent_id, c.sponsor_id, c.position, c.placement_type, d.depth + 1
                FROM binary_placement c
                JOIN downline d ON d.user_id = c.parent_id
                WHERE d.depth < ?
            )
            SELECT user_id, parent_id, sponsor_id, position, placement_type FROM downline
        ";
        $members = $this->db->query($sql, [$user_id, $depthLimit])->result();

        $allIds = array_merge([$user_id], array_map(function ($m) { return (int) $m->user_id; }, $members));

        $usersById = [];
        if ($allIds) {
            foreach ($this->db->select('id, username, email, register_date, profile_img, image')
                               ->where_in('id', $allIds)->get('users')->result() as $u) {
                $usersById[(int) $u->id] = $u;
            }
        }

        $walletsById = $this->_batchUserWallets($allIds);
        $lockWalletById = $this->_batchLockWallet($allIds);
        $investmentById = $this->_batchTotalInvestment($allIds);
        // currency_format() (site_helper.php) re-queries currency_config on
        // every single call — fine for a one-off, but this loop calls it once
        // per node below. Fetch the same global (not per-user) symbol/decimal
        // once here instead, matching currency_format()'s own logic exactly.
        $currencyPrefix = $this->_currencyPrefix();

        $downline = [];
        $rootUser = $usersById[$user_id] ?? null;
        $rootW = $walletsById[$user_id] ?? ['exchange' => 0.0, 'earning' => 0.0, 'staking' => 0.0, 'bonus' => 0.0];
        $downline[] = [
            'id' => $user_id,
            'mid' => null,
            'name' => $rootUser->username ?? '',
            'email' => $rootUser->email ?? '',
            'register_date' => !empty($rootUser->register_date) ? date('Y-m-d', strtotime($rootUser->register_date)) : '—',
            'position' => ucfirst((string) ($direct_user->position ?? '')),
            'placement_type' => ucfirst((string) ($direct_user->placement_type ?? '')),
            'exchange' => $rootW['exchange'],
            'earning' => $rootW['earning'],
            'staking' => $rootW['staking'],
            'bonus' => $rootW['bonus'],
            'lock_wallet' => $lockWalletById[$user_id] ?? 0.0,
            'profile_img' => $rootUser->profile_img ?? null,
            'image' => $rootUser->image ?? null,
        ];

        foreach ($members as $member) {
            $mid = (int) $member->user_id;
            $u = $usersById[$mid] ?? null;
            $w = $walletsById[$mid] ?? ['exchange' => 0.0, 'earning' => 0.0, 'staking' => 0.0, 'bonus' => 0.0];
            $my_investment = $investmentById[$mid] ?? 0.0;
            $downline[] = [
                'id' => $mid,
                'mid' => ((int) $member->parent_id === $mid) ? null : (int) $member->parent_id,
                'name' => $u->username ?? '',
                'email' => $u->email ?? '',
                'register_date' => !empty($u->register_date) ? date('Y-m-d', strtotime($u->register_date)) : '—',
                'position' => ucfirst((string) $member->position) . " ( " . $currencyPrefix['prefix'] . number_format($my_investment, $currencyPrefix['decimal']) . " )",
                'placement_type' => ucfirst((string) $member->placement_type),
                'exchange' => $w['exchange'],
                'earning' => $w['earning'],
                'staking' => $w['staking'],
                'bonus' => $w['bonus'],
                'lock_wallet' => $lockWalletById[$mid] ?? 0.0,
                'profile_img' => $u->profile_img ?? null,
                'image' => $u->image ?? null,
            ];
        }

        return $downline;
    }

    /** Same lookup + fallback/clamp logic as the global currency_format()
     *  helper (site_helper.php) — fetched once here instead of once per node. */
    private function _currencyPrefix()
    {
        $row = $this->db->query("
            SELECT currency_symbol, `decimal`
            FROM `currency_config`
            WHERE `currency_status` = '1'
            ORDER BY `id` DESC
            LIMIT 1
        ")->row();

        $symbol = '';
        $dec = 2;
        if ($row) {
            $symbol = isset($row->currency_symbol) ? trim((string) $row->currency_symbol) : '';
            if (isset($row->decimal) && is_numeric($row->decimal)) {
                $dec = (int) $row->decimal;
            }
        }
        if ($dec < 0) $dec = 0;
        if ($dec > 8) $dec = 8;

        return ['prefix' => ($symbol !== '' ? ($symbol . ' ') : ''), 'decimal' => $dec];
    }

    /** Batched wallet lookup — one query for any number of users. */
    private function _batchUserWallets(array $userIds)
    {
        $out = [];
        if (!$userIds) return $out;
        foreach ($this->db->where_in('user_id', $userIds)->get('user_wallets')->result() as $row) {
            $out[(int) $row->user_id] = [
                'exchange' => (float) ($row->exchange_balance ?? 0),
                'earning'  => (float) ($row->earning_balance ?? 0),
                'staking'  => (float) ($row->staking_balance ?? 0),
                'bonus'    => (float) ($row->bonus_balance ?? 0),
            ];
        }
        return $out;
    }

    /** Batched per-user Lock Wallet (active, unmatured staking principal) — same
     *  filter as Staking_model::lockWalletBalance(), one query for the whole set. */
    private function _batchLockWallet(array $userIds)
    {
        $out = [];
        if (!$userIds) return $out;
        $rows = $this->db->select('user_id, SUM(stake_amount) AS total', false)
            ->where_in('user_id', $userIds)
            ->where_in('status', ['active', 'processing'])
            ->where('maturity_date >', date('Y-m-d'))
            ->group_by('user_id')
            ->get('user_stakes')->result();
        foreach ($rows as $row) {
            $out[(int) $row->user_id] = (float) $row->total;
        }
        return $out;
    }

    /** Batched version of getTotalInvestment() applied per-user instead of
     *  summed over a whole leg — same legacy user_investment source, one query. */
    private function _batchTotalInvestment(array $userIds)
    {
        $out = [];
        if (!$userIds) return $out;
        $rows = $this->db->select('user_id, SUM(invest_amount) AS total', false)
            ->where_in('user_id', $userIds)
            ->group_by('user_id')
            ->get('user_investment')->result();
        foreach ($rows as $row) {
            $out[(int) $row->user_id] = (float) str_replace(',', '', (string) $row->total);
        }
        return $out;
    }

    // Walks parent_id up from $candidateId and returns true if $ancestorId is on that
    // chain. Used to authorize "drill into this node" pagination requests cheaply
    // (a handful of single-row lookups) without ever fetching the caller's whole downline.
    public function isDescendantOf($candidateId, $ancestorId): bool
    {
        $candidateId = (int) $candidateId;
        $ancestorId = (int) $ancestorId;
        if ($candidateId <= 0 || $ancestorId <= 0) {
            return false;
        }
        if ($candidateId === $ancestorId) {
            return true;
        }

        $current = $candidateId;
        for ($i = 0; $i < 100; $i++) {
            $row = $this->db->select('parent_id')->from('binary_placement')->where('user_id', $current)->get()->row();
            if (!$row || empty($row->parent_id)) {
                return false;
            }
            if ((int) $row->parent_id === $ancestorId) {
                return true;
            }
            $current = (int) $row->parent_id;
        }
        return false;
    }


    public function getChildNodes($parent_id)
    {
        return $this->db->get_where('binary_placement', ['parent_id' => $parent_id])->result();
    }

    public function registerUser($name, $email)
    {
        $data = ['username' => $name, 'email' => $email];
        $this->db->insert('users', $data);
        return $this->db->insert_id();
    }

    public function addPlacement($user_id, $sponsor_id, $parent_id, $position, $type)
    {
        $data = [
            'user_id' => $user_id,
            'sponsor_id' => $sponsor_id,
            'parent_id' => $parent_id,
            'position' => $position,
            'placement_type' => $type,
            'placed_at' => date('Y-m-d H:i:s'),
            'direct_placement' => ($type === 'direct') ? 1 : 0
        ];
        $this->db->insert('binary_placement', $data);
    }

    public function findPlacement($sponsor_id)
    {
        $left = $this->db->get_where('binary_placement', ['parent_id' => $sponsor_id, 'position' => 'left'])->row();
        $right = $this->db->get_where('binary_placement', ['parent_id' => $sponsor_id, 'position' => 'right'])->row();

        if (!$left)
            return ['parent_id' => $sponsor_id, 'position' => 'left'];
        if (!$right)
            return ['parent_id' => $sponsor_id, 'position' => 'right'];

        return false;
    }
    /*
    |--------------------------------------------------------------------------
    | Add Leg Users
    |--------------------------------------------------------------------------
    */
    private function getLegUsers($parent_id, $position)
    {
        $users = [];

        $child = $this->db->get_where('binary_placement', ['parent_id' => $parent_id, 'position' => $position])->row();

        if ($child) {
            $users[] = $child->user_id;

            $users = array_merge($users, $this->getLegUsers($child->user_id, 'left'));
            $users = array_merge($users, $this->getLegUsers($child->user_id, 'right'));
        }

        return $users;
    }
    /*
    |--------------------------------------------------------------------------
    | Add Get Total 
    |--------------------------------------------------------------------------
    */
    private function getTotalInvestment($user_ids)
    {
        if (empty($user_ids))
            return 0;

        $this->db->select_sum('invest_amount');
        $this->db->where_in('user_id', $user_ids);
        $result = $this->db->get('user_investment')->row();
        $user_investment = (float) str_replace(',', '', $result->invest_amount);
        return $user_investment ?? 0;
    }
    /*
   |--------------------------------------------------------------------------
   | Add Get Total Token 
   |--------------------------------------------------------------------------
   */
    private function getTotalInvestmentToken($user_ids)
    {
        if (empty($user_ids))
            return 0;

        $this->db->select_sum('csq_deposit');
        $this->db->where_in('user_id', $user_ids);
        $result = $this->db->get('user_investment')->row();
        $user_investment = (float) str_replace(',', '', $result->csq_deposit);
        return $user_investment ?? 0;
    }
    /*
   |--------------------------------------------------------------------------
   | Sum the Exchange Wallet (BMAN) balance across a set of team users
   |--------------------------------------------------------------------------
   */
    private function getTotalExchangeWallet($user_ids)
    {
        if (empty($user_ids))
            return 0;

        $this->db->select_sum('exchange_balance');
        $this->db->where_in('user_id', $user_ids);
        $result = $this->db->get('user_wallets')->row();
        return (float) ($result->exchange_balance ?? 0);
    }
    /*
    |--------------------------------------------------------------------------
    | Lock Wallet leg totals — SUM(user_stakes.stake_amount) across the whole
    | left/right subtree, restricted to still-locked principal only (status
    | active/processing AND not yet matured). Same "active, unmatured" filter
    | as Staking_model::lockWalletBalance(), applied per-leg instead of
    | per-user. One recursive CTE walks the whole subtree AND sums both legs
    | in a single round trip — avoids the getLegUsers()-style N+1 (one query
    | per downline member) for what can be an arbitrarily large team.
    | Pattern mirrors the existing getTeamSnapshotWeekly() CTE below.
    |--------------------------------------------------------------------------
    */
    public function calculateLegLockWallet($user_id)
    {
        $user_id = (int) $user_id;
        $sql = "
            WITH RECURSIVE downline AS (
                SELECT bp.user_id, bp.parent_id, bp.position AS root_leg
                FROM binary_placement bp
                WHERE bp.parent_id = ?

                UNION ALL

                SELECT c.user_id, c.parent_id, d.root_leg
                FROM binary_placement c
                JOIN downline d ON d.user_id = c.parent_id
            )
            SELECT
                d.root_leg,
                COALESCE(SUM(s.stake_amount), 0) AS lock_wallet_total
            FROM downline d
            JOIN user_stakes s
                ON s.user_id = d.user_id
                AND s.status IN ('active', 'processing')
                AND s.maturity_date > NOW()
            GROUP BY d.root_leg
        ";
        $rows = $this->db->query($sql, [$user_id])->result_array();

        $totals = ['left' => 0.0, 'right' => 0.0];
        foreach ($rows as $r) {
            if (isset($totals[$r['root_leg']])) {
                $totals[$r['root_leg']] = (float) $r['lock_wallet_total'];
            }
        }
        return $totals;
    }
    /*
    |--------------------------------------------------------------------------
    | Add Calculate Leg Investment
    |--------------------------------------------------------------------------
    */
    public function calculateLegInvestments($user_id)
    {

        $left_users = $this->getLegUsers($user_id, 'left');
        $left_investment = $this->getTotalInvestment($left_users);
        $right_users = $this->getLegUsers($user_id, 'right');
        $right_investment = $this->getTotalInvestment($right_users);

        $my_investment = $this->getTotalInvestment($user_id);

        $left_investment_token = $this->getTotalInvestmentToken($left_users);
        $right_investment_token = $this->getTotalInvestmentToken($right_users);
        $my_investment_token = $this->getTotalInvestmentToken($user_id);

        // Exchange Wallet (BMAN) totals across each leg's whole downline team.
        $left_exchange_wallet  = $this->getTotalExchangeWallet($left_users);
        $right_exchange_wallet = $this->getTotalExchangeWallet($right_users);

        // Lock Wallet (active, unmatured staking principal) totals — the real
        // basis for the Left/Right Leg Investment cards. Independent single-
        // query subtree walk (see calculateLegLockWallet()), not reusing
        // $left_users/$right_users above.
        $lockWallet = $this->calculateLegLockWallet($user_id);

        return [
            'left_leg_users' => $left_users,
            'left_leg_investment' => $left_investment,
            'right_leg_users' => $right_users,
            'right_leg_investment' => $right_investment,
            'my_investment' => $my_investment,
            'left_investment_token' => $left_investment_token,
            'right_investment_token' => $right_investment_token,
            'my_investment_token' => $my_investment_token,
            'left_exchange_wallet' => $left_exchange_wallet,
            'right_exchange_wallet' => $right_exchange_wallet,
            'left_lock_wallet' => $lockWallet['left'],
            'right_lock_wallet' => $lockWallet['right'],
        ];
    }

    public function getLegExchangeWalletBman($user_id, $from = null, $to = null)
    {
        $left_users = $this->getLegUsers($user_id, 'left');
        $right_users = $this->getLegUsers($user_id, 'right');

        if ($from || $to) {
            return [
                'left_bman' => $this->getExchangeWalletLedgerTotal($left_users, $from, $to),
                'right_bman' => $this->getExchangeWalletLedgerTotal($right_users, $from, $to),
            ];
        }

        return [
            'left_bman' => $this->getTotalExchangeWallet($left_users),
            'right_bman' => $this->getTotalExchangeWallet($right_users),
        ];
    }

    private function getExchangeWalletLedgerTotal($user_ids, $from = null, $to = null)
    {
        if (empty($user_ids)) {
            return 0.0;
        }

        $this->db->select('COALESCE(SUM(credit - debit),0) AS total_bman', false);
        $this->db->where_in('user_id', $user_ids);
        $this->db->where('wallet_type', 'exchange');
        if ($from) {
            $this->db->where('created_at >=', $from);
        }
        if ($to) {
            $this->db->where('created_at <=', $to);
        }

        $row = $this->db->get('wallet_ledger')->row();
        return (float) ($row->total_bman ?? 0);
    }





    /*
    |--------------------------------------------------------------------------
    | Count of staking purchases per leg (Binary Summary widget) — how many
    | user_stakes rows the whole left/right downline placed, optionally
    | windowed by date (e.g. "this week"). Reuses the same leg-walk as
    | calculateLegInvestments() rather than re-deriving leg membership.
    |--------------------------------------------------------------------------
    */
    public function countLegStakePurchases($user_id, $from = null, $to = null)
    {
        $left_users = $this->getLegUsers($user_id, 'left');
        $right_users = $this->getLegUsers($user_id, 'right');

        return [
            'left_count' => $this->_countStakesForUsers($left_users, $from, $to),
            'right_count' => $this->_countStakesForUsers($right_users, $from, $to),
        ];
    }

    private function _countStakesForUsers($user_ids, $from = null, $to = null)
    {
        if (empty($user_ids)) return 0;

        $this->db->where_in('user_id', $user_ids);
        $this->db->where('status !=', 'cancelled');
        if ($from) $this->db->where('created_at >=', $from);
        if ($to) $this->db->where('created_at <=', $to);

        return (int) $this->db->count_all_results('user_stakes');
    }

    /* ================================
       ✅ NEW: PRODUCT BV / PV TOTALS
       ================================ */

    public function getProductBVTotals($user_id, $from = null, $to = null)
    {
        $user_id = (int) $user_id;

        $this->db->select("
        IFNULL(SUM(CASE WHEN leg='left' THEN amount ELSE 0 END),0) AS left_bv,
        IFNULL(SUM(CASE WHEN leg='right' THEN amount ELSE 0 END),0) AS right_bv
    ", false);

        $this->db->from('history');
        $this->db->where('user_id', $user_id);
        $this->db->where('type', 'bv_volume');
        $this->db->where('status', '1');

        if ($from)
            $this->db->where('date >=', $from);
        if ($to)
            $this->db->where('date <=', $to);

        $res = $this->db->get()->row_array() ?: ['left_bv' => 0, 'right_bv' => 0];
        // echo $this->db->last_query();
        return $res;
    }

    public function getWeekRange()
    {
        $end = date('Y-m-d 23:59:59', strtotime('today'));
        $start = date('Y-m-d 00:00:00', strtotime('-7 days'));

        return [$start, $end];
    }

    public function getThisWeekRange()
    {
        $start = date('Y-m-d 00:00:00', strtotime('monday this week'));
        $end = date('Y-m-d 23:59:59', strtotime('sunday this week'));
        return [$start, $end];
    }

    public function getMonthRange()
    {
        $start = date('Y-m-01 00:00:00');
        $end = date('Y-m-t 23:59:59');
        return [$start, $end];
    }

    public function getTodayRange()
    {
        $start = date('Y-m-d 00:00:00');
        $end = date('Y-m-d 23:59:59');
        return [$start, $end];
    }

    public function getPairsCompleted($user_id, $from = null, $to = null)
    {
        $user_id = (int) $user_id;

        $this->db->select("IFNULL(SUM(pairs_count),0) AS pairs", false);
        $this->db->from('history');
        $this->db->where('user_id', $user_id);
        $this->db->where('type', 'pair_commission');
        $this->db->where('status', '1');

        if ($from)
            $this->db->where('date >=', $from);
        if ($to)
            $this->db->where('date <=', $to);

        $row = $this->db->get()->row();
        _dbg('getPairsCompleted', ['query' => $this->db->last_query(), 'result' => $row]);
        return (int) ($row->pairs ?? 0);
    }


    public function getTeamSnapshotWeekly($rootUserId, $ws, $we)
    {
        $rootUserId = (int) $rootUserId;

        $sql = "
    WITH RECURSIVE downline AS (
      SELECT bp.user_id, bp.parent_id, bp.position AS root_leg, bp.placed_at
      FROM binary_placement bp
      WHERE bp.parent_id = ?

      UNION ALL

      SELECT c.user_id, c.parent_id, d.root_leg, c.placed_at
      FROM binary_placement c
      JOIN downline d ON d.user_id = c.parent_id
    )
    SELECT
      SUM(CASE WHEN d.root_leg='left'  THEN 1 ELSE 0 END) AS left_team,
      SUM(CASE WHEN d.root_leg='right' THEN 1 ELSE 0 END) AS right_team,
      SUM(CASE WHEN u.status = 1 THEN 1 ELSE 0 END) AS active_total,
      SUM(CASE WHEN d.placed_at BETWEEN ? AND ? THEN 1 ELSE 0 END) AS new_joins_week,
      SUM(CASE WHEN u.status = 1 AND d.placed_at BETWEEN ? AND ? THEN 1 ELSE 0 END) AS active_week
    FROM downline d
    JOIN users u ON u.id = d.user_id
    ";

        $row = $this->db->query($sql, [$rootUserId, $ws, $we, $ws, $we])->row_array();

        return [
            'left_team' => (int) ($row['left_team'] ?? 0),
            'right_team' => (int) ($row['right_team'] ?? 0),
            'active_total' => (int) ($row['active_total'] ?? 0),
            'new_joins_week' => (int) ($row['new_joins_week'] ?? 0),
            'active_week' => (int) ($row['active_week'] ?? 0),
        ];
    }


    public function getOrCreateCarryRow($user_id)
    {
        $user_id = (int) $user_id;

        $row = $this->db->get_where('binary_carry_forward', ['user_id' => $user_id])->row_array();
        if ($row)
            return $row;

        $this->db->insert('binary_carry_forward', [
            'user_id' => $user_id,
            'left_carry' => 0,
            'right_carry' => 0,
            'scope_key' => null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->db->get_where('binary_carry_forward', ['user_id' => $user_id])->row_array();
    }

    public function updateCarryRow($user_id, $left, $right, $scope_key = null)
    {
        $user_id = (int) $user_id;
        $this->db->update('binary_carry_forward', [
            'left_carry' => (float) $left,
            'right_carry' => (float) $right,
            'scope_key' => $scope_key,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['user_id' => $user_id]);
    }

    public function getCarryScopeKey($mode)
    {
        $mode = strtolower(trim((string) $mode));
        $now = time();

        if ($mode === 'daily') {
            return date('Y-m-d', $now);
        }
        if ($mode === 'weekly') {
            return date('o-\WW', $now); // ISO week key like 2026-W05
        }
        if ($mode === 'monthly') {
            return date('Y-m', $now);
        }
        return 'lifetime';
    }


}
?>
