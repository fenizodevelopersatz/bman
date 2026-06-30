<?php
class Investment_model extends CI_Model {

    public function __construct() {
        $this->load->database();
    }

    public function get_info($limit, $start) {
    $this->db->limit($limit, $start);
    $this->db->where('approve_status','0');
    $query = $this->db->get('user_investment');
    return $query->result_array();
    }

    public function get_count() {
    $this->db->where('approve_status','0');
    $query = $this->db->get('user_investment');
    return $query->num_rows();
    }


    public function get_info_invest($limit, $start,$from_date,$to_date,$client_filter,$type) {
    
    if (!empty($from_date) && !empty($to_date)) {
        $this->db->where('date(created_date) >=', $from_date);
        $this->db->where('date(created_date) <=', $to_date);
    }

    if (!empty($client_filter) && is_array($client_filter)) {
        $this->db->where_in('user_id', $client_filter);
    } elseif (!empty($client_filter)) { 
        $this->db->where('user_id', $client_filter);
    }

    if (!empty($type) && is_array($type)) {
        $this->db->where_in('status', $type);
    } elseif (!empty($type)) { 
        $this->db->where('status', $type);
    }

    $this->db->limit($limit, $start);
    $this->db->order_by('id', 'DESC');
    $query = $this->db->get('user_investment');
    return $query->result_array();
    }

    public function get_count_invest($from_date,$to_date,$client_filter,$type) {

    if (!empty($from_date) && !empty($to_date)) {
        $this->db->where('date(created_date) >=', $from_date);
        $this->db->where('date(created_date) <=', $to_date);
    }

    if (!empty($client_filter) && is_array($client_filter)) {
        $this->db->where_in('user_id', $client_filter);
    } elseif (!empty($client_filter)) { 
        $this->db->where('user_id', $client_filter);
    }

    if (!empty($type) && is_array($type)) {
        $this->db->where_in('status', $type);
    } elseif (!empty($type)) { 
        $this->db->where('status', $type);
    }
    $query = $this->db->get('user_investment');
    return $query->num_rows();
    }

    

    public function get_info_amt($from_date,$to_date,$client_filter,$agent_filter) {

    if (!empty($from_date) && !empty($to_date)) {
        $this->db->where('date(created_date) >=', $from_date);
        $this->db->where('date(created_date) <=', $to_date);
    }

    if (!empty($client_filter) && is_array($client_filter)) {
        $this->db->where_in('user_id', $client_filter);
    } elseif (!empty($client_filter)) { 
        $this->db->where('user_id', $client_filter);
    }
    
    if (!empty($agent_filter) && is_array($agent_filter)) {
        $this->db->where_in('status', $agent_filter);
    } elseif (!empty($agent_filter)) { 
        $this->db->where('status', $agent_filter);
    }
    
    $this->db->order_by('created_date', 'DESC');
    $query = $this->db->get('user_investment');

    return $query->result_array();
    }
}
?>
