<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function get_user($identifier) {
        $this->db->where('id', $identifier);
        $query = $this->db->get('admin_members');
        return $query->row();
    }
}
