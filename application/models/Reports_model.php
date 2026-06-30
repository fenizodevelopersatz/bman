<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Reports_model extends CI_Model {

    public function get_config() {
        return $this->db->get_where('commission_config', ['id'=>1])->row();
    }

    public function history_summary_by_type($user_id, $from, $to) {
        return $this->db->select('type, COUNT(*) AS rows_count, ROUND(SUM(amount),6) AS usd_total, ROUND(SUM(token_amount),6) AS token_total')
            ->from('history')
            ->where('user_id', $user_id)
            ->where('DATE(history_date) >=', $from)
            ->where('DATE(history_date) <=', $to)
            ->group_by('type')
            ->order_by('type','ASC')
            ->get()->result();
    }

    public function history_daily_breakdown($user_id, $from, $to) {
        return $this->db->select('DATE(history_date) AS day, type, ROUND(SUM(amount),6) AS usd_total, ROUND(SUM(token_amount),6) AS token_total')
            ->from('history')
            ->where('user_id', $user_id)
            ->where('DATE(history_date) >=', $from)
            ->where('DATE(history_date) <=', $to)
            ->group_by('DATE(history_date), type')
            ->order_by('day ASC, type ASC')
            ->get()->result();
    }

    public function binary_rows($user_id, $from, $to) {
        return $this->db->select('id, history_date, amount AS usd, token_amount AS tokens, description, pair_ratio_used, pairs_count, basis, total_left_roi, total_right_roi, total_left_invest, total_right_invest, total_left_users, total_right_users, total_left_invest_ids, total_right_invest_ids')
            ->from('history')
            ->where('user_id', $user_id)
            ->where('type', 'binary_commission')
            ->where('DATE(history_date) >=', $from)
            ->where('DATE(history_date) <=', $to)
            ->order_by('history_date', 'ASC')
            ->get()->result();
    }

    public function matching_rows($user_id, $from, $to) {
        return $this->db->select('id, history_date, amount AS usd, token_amount AS tokens, description, source_user_id, level_no, ref_history_id')
            ->from('history')
            ->where('user_id', $user_id)
            ->where('type', 'matching_bonus')
            ->where('DATE(history_date) >=', $from)
            ->where('DATE(history_date) <=', $to)
            ->order_by('history_date', 'ASC')
            ->get()->result();
    }

    public function direct_rows($user_id, $from, $to) {
        return $this->db->select('id, history_date, amount AS usd, token_amount AS tokens, description')
            ->from('history')
            ->where('user_id', $user_id)
            ->where('type', 'direct_commission')
            ->where('DATE(history_date) >=', $from)
            ->where('DATE(history_date) <=', $to)
            ->order_by('history_date', 'ASC')
            ->get()->result();
    }

    public function level_rows($user_id, $from, $to) {
        return $this->db->select('id, history_date, amount AS usd, token_amount AS tokens, description, level_no')
            ->from('history')
            ->where('user_id', $user_id)
            ->where('type', 'level_commission')
            ->where('DATE(history_date) >=', $from)
            ->where('DATE(history_date) <=', $to)
            ->order_by('history_date', 'ASC')
            ->get()->result();
    }

    public function other_bonus_rows($user_id, $from, $to) {
        return $this->db->select('id, history_date, type, amount AS usd, token_amount AS tokens, description')
            ->from('history')
            ->where('user_id', $user_id)
            ->where_in('type', ['fast_start','repurchase_commission','leadership_bonus','pool_bonus'])
            ->where('DATE(history_date) >=', $from)
            ->where('DATE(history_date) <=', $to)
            ->order_by('history_date', 'ASC')
            ->get()->result();
    }

    public function roi_rows($user_id, $from, $to) {
        return $this->db->select('id, history_date, amount AS usd, token_amount AS tokens, invest_id, description')
            ->from('history')
            ->where('user_id', $user_id)
            ->where('type', 'profit')
            ->where('DATE(history_date) >=', $from)
            ->where('DATE(history_date) <=', $to)
            ->order_by('history_date', 'ASC')
            ->get()->result();
    }

    public function carry_row($user_id) {
        return $this->db->get_where('binary_carry', ['user_id'=>$user_id])->row();
    }
}
