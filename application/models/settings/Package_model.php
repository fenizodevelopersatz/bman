<?php
class Package_model extends CI_Model {

    public function __construct() {
        $this->load->database();
    }

    public function get_info($limit, $start, $search) {
        if(!empty($search)){
            $this->db->like('minimum', $search);
        }
        $this->db->limit($limit, $start);
        $query = $this->db->get('package_config');
        return $query->result_array();
    }

    public function get_count($search) {
        if(!empty($search)){
            $this->db->like('minimum', $search);
        }
        $query = $this->db->get('package_config');
        return $query->num_rows();
    }

    
}
?>
