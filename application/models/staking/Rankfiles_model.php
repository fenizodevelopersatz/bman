<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Rankfiles_model — admin-uploaded downloadable files (certificates, guides,
 * images, PDFs) surfaced on the member Rank & Rewards page.
 *
 * Storage: file_path holds a RELATIVE path (uploads/rank_files/…) — never a
 * full URL — so the download link is rebuilt against the current base_url()
 * via media_url() and survives a domain change.
 */
class Rankfiles_model extends CI_Model
{
    private $table = 'rank_files';

    /** All files for the admin list, newest first, with rank name joined. */
    public function allForAdmin()
    {
        return $this->db->select('rf.*, sr.name AS rank_name')
                        ->from($this->table . ' rf')
                        ->join('staking_ranks sr', 'sr.id = rf.rank_id', 'left')
                        ->order_by('rf.id', 'DESC')
                        ->get()->result_array();
    }

    public function get($id)
    {
        return $this->db->get_where($this->table, ['id' => (int) $id])->row_array();
    }

    public function create(array $data)
    {
        $this->db->insert($this->table, $data);
        return (int) $this->db->insert_id();
    }

    /** Delete a row and unlink its physical file. Returns true on delete. */
    public function delete($id)
    {
        $row = $this->get($id);
        if (!$row) return false;
        if (!empty($row['file_path'])) {
            $abs = FCPATH . ltrim($row['file_path'], '/');
            if (is_file($abs)) @unlink($abs);
        }
        $this->db->where('id', (int) $id)->delete($this->table);
        return true;
    }

    /**
     * Active files visible to a member: those flagged for all ranks (rank_id
     * NULL) plus any tied to a rank the member currently holds / has achieved.
     * $rankIds is the member's current + highest rank ids.
     */
    public function forUser(array $rankIds)
    {
        $rankIds = array_values(array_filter(array_map('intval', $rankIds)));
        $this->db->from($this->table)
                 ->where('status', 1)
                 ->group_start()
                     ->where('rank_id IS NULL', null, false);
        if (!empty($rankIds)) {
            $this->db->or_where_in('rank_id', $rankIds);
        }
        $this->db->group_end();
        return $this->db->order_by('id', 'DESC')->get()->result_array();
    }
}
