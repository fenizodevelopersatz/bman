<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Rankservice
{
    protected $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->model('Rank_model');

    }

    public function get_rank_status($user_id)
    {
        $rank = $this->CI->Rank_model->get_user_rank($user_id);
        $current_order = $rank->rank_order ?? 0;

        $next_rank = $this->CI->Rank_model->get_next_rank($current_order);

        // Dates
        $start = date("Y-m-01 00:00:00");
        $end = date("Y-m-t 23:59:59");

        // Get legs (reuse your existing method)
        $left_users = $this->_get_leg_users($user_id, 'left');
        $right_users = $this->_get_leg_users($user_id, 'right');

        $left_volume = $this->CI->Rank_model->get_monthly_binary_volume($left_users, $start, $end);
        $right_volume = $this->CI->Rank_model->get_monthly_binary_volume($right_users, $start, $end);

        $weak_leg = min($left_volume, $right_volume);

        $progress = 0;
        if ($next_rank && $next_rank->rank_eligibel_amt > 0) {
            $progress = min(100, round(($weak_leg / $next_rank->rank_eligibel_amt) * 100));
        }

        return [
            'current_rank' => $rank->rank_name ?? 'NONE',
            'next_rank' => $next_rank->rank_name ?? 'MAX',
            'progress' => $progress,
            'weak_leg' => $weak_leg,
            'required' => $next_rank->rank_eligibel_amt ?? 0
        ];
    }

    /**
     * ----------------------------
     * LEG USERS (BFS traversal)
     * Assumptions:
     *  users table has:
     *   - sponser (upline id)  OR parent_id
     *   - position ('left'/'right') OR leg
     *
     * ✅ IMPORTANT:
     * Change these fields to match your DB if needed:
     *   sponsor column: `sponser`
     *   position column: `position`
     * ----------------------------
     */
    private function _get_leg_users($user_id, $side = 'left')
    {
        $side = strtolower($side) === 'right' ? 'right' : 'left';

        $all = [];
        $queue = [];

        // Direct children on that side
        $children = $this->CI->db->query("
        SELECT id FROM users
        WHERE status = '1'
          AND sponser = ?
          AND position = ?
    ", [$user_id, $side])->result();

        foreach ($children as $c) {
            $queue[] = (int) $c->id;
            $all[] = (int) $c->id;
        }

        // BFS downline
        while (!empty($queue)) {
            $parent = array_shift($queue);

            $kids = $this->CI->db->query("
            SELECT id FROM users
            WHERE status = '1'
              AND sponser = ?
        ", [$parent])->result();

            foreach ($kids as $k) {
                $kid = (int) $k->id;
                if (!in_array($kid, $all, true)) {
                    $all[] = $kid;
                    $queue[] = $kid;
                }
            }
        }

        return $all;
    }



}
