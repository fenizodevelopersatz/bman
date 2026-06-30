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

    public function getDownlineMembers($user_id)
    {
        $downline = [];

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
        $this->db->where('users.id', $user_id);
        $direct_user = $this->db->get()->row();

        $downline[] = [
            'id' => $direct_user->id,
            'mid' => null,
            'name' => $direct_user->username,
            'email' => $direct_user->email,
            'register_date' => date('Y-m-d', strtotime($direct_user->register_date)),
            'position' => ucfirst($direct_user->position),
            'placement_type' => ucfirst($direct_user->placement_type)
        ];

        $this->fetchDownline($user_id, $downline);
        return $downline;
    }

    private function fetchDownline($parent_id, &$downline)
    {
        $this->db->select('
            users.id, 
            users.username, 
            users.email, 
            binary_placement.parent_id, 
            binary_placement.sponsor_id, 
            binary_placement.position, 
            binary_placement.placement_type,
            users.register_date
        ');
        $this->db->from('users');
        $this->db->join('binary_placement', 'users.id = binary_placement.user_id', 'left');
        $this->db->where('binary_placement.parent_id', $parent_id);
        $members = $this->db->get()->result();

        foreach ($members as $member) {
            $my_investment = $this->getTotalInvestment($member->id);
            $downline[] = [
                'id' => $member->id,
                'mid' => ($member->parent_id == $member->id) ? null : $member->parent_id,
                'name' => $member->username,
                'email' => $member->email,
                'register_date' => date('Y-m-d', strtotime($member->register_date)),
                'position' => ucfirst($member->position) . " ( " . currency_format($my_investment) . " )",
                'placement_type' => ucfirst($member->placement_type)
            ];

            $this->fetchDownline($member->id, $downline);
        }
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

        return [
            'left_leg_users' => $left_users,
            'left_leg_investment' => $left_investment,
            'right_leg_users' => $right_users,
            'right_leg_investment' => $right_investment,
            'my_investment' => $my_investment,
            'left_investment_token' => $left_investment_token,
            'right_investment_token' => $right_investment_token,
            'my_investment_token' => $my_investment_token,
        ];
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