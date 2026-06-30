<?php
// defined('BASEPATH') OR exit('No direct script access allowed');

// class Earnings_ads_cms_model extends CI_Model
// {
//     public function __construct()
//     {
//         parent::__construct();
//         $this->load->database();
//     }

//     public function get_info($limit, $start)
//     {
//         return $this->db->limit((int) $limit, (int) $start)
//             ->order_by('sort_order', 'ASC')
//             ->order_by('id', 'DESC')
//             ->get('earning_ads')
//             ->result_array();
//     }

//     public function get_count()
//     {
//         return $this->db->count_all('earning_ads');
//     }
// }






defined('BASEPATH') OR exit('No direct script access allowed');

class Earnings_ads_cms_model extends CI_Model
{
    private $table = 'earning_ads';

    public function get_by_id($id)
    {
        return $this->db->from($this->table)->where('id', (int) $id)->get()->row();
    }

    public function get_count($search = '')
    {
        $this->db->from($this->table);
        if ($search !== '') {
            $this->db->group_start()
                ->like('title', $search)
                ->or_like('ad_url', $search)
                ->or_like('description', $search)
                ->group_end();
        }
        return (int) $this->db->count_all_results();
    }

    public function get_info($limit, $start, $search = '')
    {
        $this->db->from($this->table);
        if ($search !== '') {
            $this->db->group_start()
                ->like('title', $search)
                ->or_like('ad_url', $search)
                ->or_like('description', $search)
                ->group_end();
        }
        return $this->db
            ->order_by('sort_order', 'ASC')
            ->order_by('id', 'DESC')
            ->limit((int) $limit, (int) $start)
            ->get()
            ->result_array();
    }

    public function insert($data)
    {
        $this->db->insert($this->table, $data);
        return (int) $this->db->insert_id();
    }

    public function update($id, $data)
    {
        $this->db->where('id', (int) $id)->update($this->table, $data);
        return $this->db->affected_rows() >= 0;
    }

    public function delete($id)
    {
        $this->db->where('id', (int) $id)->delete($this->table);
        return $this->db->affected_rows() > 0;
    }

}
