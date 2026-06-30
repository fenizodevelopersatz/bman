<?php
class Transaction_model extends CI_Model {

    public function __construct() {
        $this->load->database();
    }

    public function get_info($limit, $start,$from_date,$to_date,$client_filter,$agent_filter) {

    if (!empty($from_date) && !empty($to_date)) {
        $this->db->where('date(date) >=', $from_date);
        $this->db->where('date(date) <=', $to_date);
    }

    if (!empty($client_filter) && is_array($client_filter)) {
        $this->db->where_in('user_id', $client_filter);
    } elseif (!empty($client_filter)) { 
        $this->db->where('user_id', $client_filter);
    }

    if (!empty($agent_filter) && is_array($agent_filter)) {
        $this->db->where_in('type', $agent_filter);
    } elseif (!empty($agent_filter)) { 
        $this->db->where('type', $agent_filter);
    }
   

    $this->db->limit($limit, $start);
    $this->db->order_by('date', 'DESC');
    $query = $this->db->get('history');

    return $query->result_array();
    }

    public function get_count($from_date,$to_date,$client_filter,$agent_filter) {

    if (!empty($from_date) && !empty($to_date)) {
        $this->db->where('date(date) >=', $from_date);
        $this->db->where('date(date) <=', $to_date);
    }

    if (!empty($client_filter) && is_array($client_filter)) {
        $this->db->where_in('user_id', $client_filter);
    } elseif (!empty($client_filter)) { 
        $this->db->where('user_id', $client_filter);
    }
   
    if (!empty($agent_filter) && is_array($agent_filter)) {
        $this->db->where_in('type', $agent_filter);
    } elseif (!empty($agent_filter)) { 
        $this->db->where('type', $agent_filter);
    }

    $query = $this->db->get('history');
    return $query->num_rows();
    }


    public function get_info_amt($from_date,$to_date,$client_filter,$agent_filter) {


        $this->db->select("sum(amount) as mybalance , SUM(token_amount) AS total_token_amount");

        if (!empty($from_date) && !empty($to_date)) {
            $this->db->where('date(date) >=', $from_date);
            $this->db->where('date(date) <=', $to_date);
        }
    
        if (!empty($client_filter) && is_array($client_filter)) {
            $this->db->where_in('user_id', $client_filter);
        } elseif (!empty($client_filter)) { 
            $this->db->where('user_id', $client_filter);
        }
        
        if (!empty($agent_filter) && is_array($agent_filter)) {
            $this->db->where_in('type', $agent_filter);
        } elseif (!empty($agent_filter)) { 
            $this->db->where('type', $agent_filter);
        }
        

        $this->db->order_by('date', 'DESC');
        $query = $this->db->get('history');

        return $query->result_array();
        }


        
    public function get_info_profit($limit, $start,$from_date,$to_date,$client_filter,$agent_filter,$invest_id) {

        if (!empty($from_date) && !empty($to_date)) {
            $this->db->where('date(date) >=', $from_date);
            $this->db->where('date(date) <=', $to_date);
        }
    
        if (!empty($client_filter) && is_array($client_filter)) {
            $this->db->where_in('user_id', $client_filter);
        } elseif (!empty($client_filter)) { 
            $this->db->where('user_id', $client_filter);
        }
    
        if (!empty($agent_filter) && is_array($agent_filter)) {
            $this->db->where_in('type', $agent_filter);
        } elseif (!empty($agent_filter)) { 
            $this->db->where('type', $agent_filter);
        }
        
        $this->db->where('invest_id',$invest_id);
        $this->db->limit($limit, $start);
        $this->db->order_by('date', 'DESC');
        $query = $this->db->get('history');
    
        return $query->result_array();
        }
    
        public function get_count_profit($from_date,$to_date,$client_filter,$agent_filter,$invest_id) {
    
        if (!empty($from_date) && !empty($to_date)) {
            $this->db->where('date(date) >=', $from_date);
            $this->db->where('date(date) <=', $to_date);
        }
    
        if (!empty($client_filter) && is_array($client_filter)) {
            $this->db->where_in('user_id', $client_filter);
        } elseif (!empty($client_filter)) { 
            $this->db->where('user_id', $client_filter);
        }
       
        if (!empty($agent_filter) && is_array($agent_filter)) {
            $this->db->where_in('type', $agent_filter);
        } elseif (!empty($agent_filter)) { 
            $this->db->where('type', $agent_filter);
        }
    
        $this->db->where('invest_id',$invest_id);
        $query = $this->db->get('history');
        return $query->num_rows();
        }
    

        
    public function get_info_amtprofit($from_date,$to_date,$client_filter,$agent_filter,$invest_id) {

        if (!empty($from_date) && !empty($to_date)) {
            $this->db->where('date(date) >=', $from_date);
            $this->db->where('date(date) <=', $to_date);
        }
    
        if (!empty($client_filter) && is_array($client_filter)) {
            $this->db->where_in('user_id', $client_filter);
        } elseif (!empty($client_filter)) { 
            $this->db->where('user_id', $client_filter);
        }
        
        if (!empty($agent_filter) && is_array($agent_filter)) {
            $this->db->where_in('type', $agent_filter);
        } elseif (!empty($agent_filter)) { 
            $this->db->where('type', $agent_filter);
        }
        
        $this->db->where('invest_id',$invest_id);
        $this->db->order_by('date', 'DESC');
        $query = $this->db->get('history');
    
        return $query->result_array();
        }


}
?>
