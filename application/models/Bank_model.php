<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bank_model extends CI_Model
{
    private $table = 'user_bank';

    public function getQueue(array $statuses)
    {
        // If you don't have under_review/resubmitted in your DB, it's fine: it will just return matching rows
        return $this->db->select('b.*, u.email, u.username')
            ->from($this->table . ' b')
            ->join('users u', 'u.id = b.user_id', 'left')
            ->where_in('b.status', $statuses)
            ->order_by('b.submitted_at', 'DESC')
            ->order_by('b.created_at', 'DESC')
            ->get()->result_array();
    }

    public function getById(int $id)
    {
        return $this->db->select('b.*, u.email, u.username')
            ->from($this->table . ' b')
            ->join('users u', 'u.id = b.user_id', 'left')
            ->where('b.id', $id)
            ->get()->row_array();
    }

    public function setStatus(int $id, string $status, int $reviewer_id, ?string $note = null)
    {
        $now = date('Y-m-d H:i:s');

        $data = [
            'status' => $status,
            'note' => $note,
            'reviewed_at' => $now,
            'reviewer_id' => $reviewer_id ?: null,
            'updated_at' => $now,
        ];

        $this->db->where('id', $id);
        return $this->db->update($this->table, $data);
    }
}
