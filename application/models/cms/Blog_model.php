<?php 
class Blog_model extends CI_Model {

    public function get_all() {
        return $this->db->order_by('id', 'DESC')->get('blogs')->result();
    }

    public function get($id) {
        return $this->db->get_where('blogs', ['id' => $id])->row();
    }

    public function get_usage_logs() {
        $this->db->select('cu.*, c.code, c.discount_type, c.discount_value');
        $this->db->from('coupon_usage cu');
        $this->db->join('coupons c', 'cu.coupon_id = c.id');
        $this->db->order_by('cu.id', 'DESC');
        return $this->db->get()->result();
    }

    public function get_blog_count() {
    return $this->db->count_all('blogs');
    }

    public function get_blog_list($limit, $start) {
    $this->db->order_by('id', 'DESC');
    $this->db->limit($limit, $start);
    return $this->db->get('blogs')->result_array();
    }
    
    public function get_category_count() {
        return $this->db->count_all('blog_categories');
    }

    public function get_category_list($limit, $offset) {
        return $this->db->limit($limit, $offset)
            ->order_by('id', 'DESC')
            ->get('blog_categories')
            ->result_array();
    }
    

}
