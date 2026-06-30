<?php 
class Shipping_model extends CI_Model
{
    public function get_all_zones()
    {
        return $this->db->order_by('id', 'DESC')->get('shipping_zones')->result_array();
    }

    public function get_zone($id)
    {
        return $this->db->get_where('shipping_zones', ['id' => $id])->row();
    }

    public function save_zone($data, $id = null)
    {
        if ($id) {
            $this->db->where('id', $id)->update('shipping_zones', $data);
            return $id;
        } else {
            $this->db->insert('shipping_zones', $data);
            return $this->db->insert_id();
        }
    }

    public function delete_zone($id)
    {
        return $this->db->delete('shipping_zones', ['id' => $id]);
    }

    public function is_pincode_unique($pincode, $id = null)
    {
        $this->db->where('pincode', $pincode);
        if ($id) $this->db->where('id !=', $id);
        return $this->db->count_all_results('shipping_zones') == 0;
    }

    public function get_zone_count()
    {
        return $this->db->count_all('shipping_zones');
    }

    public function get_zone_list($limit, $start)
    {
        return $this->db
            ->order_by('id', 'DESC')
            ->limit($limit, $start)
            ->get('shipping_zones')
            ->result_array();
    }

}

?>