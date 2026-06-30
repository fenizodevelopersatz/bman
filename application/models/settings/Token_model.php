<?php
class Token_model extends CI_Model {

    public function __construct() {
        $this->load->database();
    }

    public function get_info($limit, $start, $search) {
        if(!empty($search)){
            $this->db->like('coin_name', $search);
        }
        $this->db->limit($limit, $start);
        $query = $this->db->get('token_config');
        return $query->result_array();
    }

    public function get_count($search) {
        if(!empty($search)){
            $this->db->like('coin_name', $search);
        }
        $query = $this->db->get('token_config');
        return $query->num_rows();
    }

    
}
?>
