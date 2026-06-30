<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Kyc_model extends CI_Model
{

     public function getByUser($user_id){
        return $this->db->where('user_id', (int)$user_id)
                        ->order_by('id','DESC')
                        ->get('kyc_applications')->row_array();
    }

    public function createOrUpdate($user_id, array $payload){
        $cur = $this->getByUser($user_id);
        $payload['user_id'] = (int)$user_id;

        if ($cur && in_array($cur['status'], ['pending','under_review','resubmitted','rejected'])) {
            // If previously rejected, mark resubmitted
            if ($cur['status']==='rejected') $payload['status']='resubmitted';
            $this->db->where('id', (int)$cur['id'])->update('kyc_applications', $payload);
            return (int)$cur['id'];
        } else {
            $this->db->insert('kyc_applications', $payload);
            return (int)$this->db->insert_id();
        }
    }

    public function setStatus($kyc_id, $status, $reviewed_by=null, $notes=null, $rej_code=null) {
        return $this->db->where('id', (int)$kyc_id)->update('kyc_applications', [
            'status' => $status,
            'review_notes' => $notes,
            'rejection_code' => $rej_code,
            'reviewed_by' => $reviewed_by,
            'reviewed_at' => date('Y-m-d H:i:s'),
        ]);
    }


}
