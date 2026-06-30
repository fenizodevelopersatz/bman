<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Rank_model extends CI_Model
{
    public function get_user_rank($user_id)
    {
        return $this->db
            ->select('u.rank_id, r.rank_name, r.rank_order')
            ->from('users u')
            ->join('rank_config r', 'r.id = u.rank_id', 'left')
            ->where('u.id', $user_id)
            ->get()
            ->row();
    }

    public function get_next_rank($current_order)
    {
        return $this->db
            ->where('rank_order >', (int) $current_order)
            ->where('rank_status', 1)
            ->order_by('rank_order', 'ASC')
            ->limit(1)
            ->get('rank_config')
            ->row();
    }

    public function get_monthly_binary_volume($user_ids, $start, $end)
    {
        if (empty($user_ids))
            return 0;

        return (float) $this->db
            ->select_sum('amount')
            ->where('status', 1)
            ->where_in('user_id', $user_ids)
            ->where('history_date >=', $start)
            ->where('history_date <=', $end)
            ->get('roi_credits')
            ->row()
            ->amount;
    }

    public function get_info($limit, $start, $search)
    {
        if (!empty($search)) {
            $this->db->like('rank_name', $search);
        }
        $this->db->limit($limit, $start);
        $query = $this->db->get('rank_config');
        return $query->result_array();
    }

    public function get_count($search)
    {
        if (!empty($search)) {
            $this->db->like('rank_name', $search);
        }
        $query = $this->db->get('rank_config');
        return $query->num_rows();
    }
}
