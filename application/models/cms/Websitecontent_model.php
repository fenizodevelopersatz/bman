<?php
class Websitecontent_model extends CI_Model {

    public function __construct() {
        $this->load->database();
    }

    public function get_info($limit, $start) {
    $this->db->limit($limit, $start);
    $query = $this->db->get('page_content');
    return $query->result_array();
    }

    public function get_count() {
    $query = $this->db->get('page_content');
    return $query->num_rows();
    }


}
?>
