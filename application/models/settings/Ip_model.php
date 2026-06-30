<?php
class Ip_model extends CI_Model {

    public function __construct() {
        $this->load->database();
    }

    public function get_info($limit, $start, $search) {
        if(!empty($search)){
            $this->db->like('ip_address', $search);
        }
        $this->db->limit($limit, $start);
        $query = $this->db->get('blocked_ips');
        return $query->result_array();
    }

    public function get_count($search) {
        if(!empty($search)){
            $this->db->like('ip_address', $search);
        }
        $query = $this->db->get('blocked_ips');
        return $query->num_rows();
    }

    
}
?>
