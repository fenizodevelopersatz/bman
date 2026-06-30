<?php
class Payment_model extends CI_Model {

    public function __construct() {
        $this->load->database();
    }

    // public function get_info($limit, $start, $search) {
    //     if(!empty($search)){
    //         $this->db->like('wallet_name', $search);
    //     }
    //     $this->db->limit($limit, $start);
    //     $query = $this->db->get('payment_controls');
    //     return $query->result_array();
    // }

    // public function get_count($search) {
    //     if(!empty($search)){
    //         $this->db->like('wallet_name', $search);
    //     }
    //     $query = $this->db->get('payment_controls');
    //     return $query->num_rows();
    // }

    public function get_all_gateways() {
    return $this->db->get('payment_settings')->result();
    }

    public function get_gateway($gateway) {
    return $this->db->get_where('payment_settings', ['gateway' => $gateway])->row();
    }

    public function save_gateway($gateway, $data) {
    $exists = $this->db->get_where('payment_settings', ['gateway' => $gateway])->row();
    if ($exists) {
    $this->db->where('gateway', $gateway);
    return $this->db->update('payment_settings', $data);
    } else {
    $data['gateway'] = $gateway;
    return $this->db->insert('payment_settings', $data);
    }
    }

     private $table = 'payment_settings';

    public function get_info($limit, $start, $search = '')
    {
        if ($search) {
            $this->db->group_start()
                     ->like('gateway', $search)
                     ->or_like('mode', $search)
                     ->group_end();
        }
        return $this->db->order_by('gateway', 'asc')
                        ->limit($limit, $start)
                        ->get($this->table)->result_array();
    }

    public function get_count($search = '')
    {
        if ($search) {
            $this->db->group_start()
                     ->like('gateway', $search)
                     ->or_like('mode', $search)
                     ->group_end();
        }
        return $this->db->count_all_results($this->table);
    }

    public function get_by_id($id)
    {
        return $this->db->get_where($this->table, ['id' => (int)$id])->row();
    }

    public function update($id, array $data)
    {
        if (empty($data)) return false;
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->where('id', (int)$id)->update($this->table, $data);
    }

    public function toggle_status($id, $status)
    {
        return $this->update($id, ['status' => (int)$status]);
    }
    
}
?>
