<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Verification_model extends CI_Model
{
    public $table = 'email_verifications';

    // Latest record for this actor (user/admin)
    public function get_latest_today($actorType, $actorId)
    {
        return $this->db->where('actor_type', $actorType)
            ->where('actor_id', (int) $actorId)
            ->where('DATE(created_at) =', date('Y-m-d'))  // ✅ today
            ->order_by('created_at', 'DESC')
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get($this->table)
            ->row();
    }

    // show popup if latest verified is not true
    public function should_show_popup($actorType, $actorId)
    {
        $row = $this->get_latest_today($actorType, $actorId);
        if (!$row)
            return true;
        return ((int) $row->email_verified !== 1);
    }

    // ✅ INSERT ONLY
    public function insert_start($actorType, $actorId, $name, $email, $phone, $otpStore, $expireAt, $ttlSec)
    {
        $now = date('Y-m-d H:i:s');

        return $this->db->insert($this->table, [
            'actor_type' => $actorType,
            'actor_id' => (int) $actorId,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'email_verified' => 0,
            'otp_hash' => $otpStore,
            'otp_expire_at' => $expireAt,
            'ttl_sec' => (int) $ttlSec,
            'created_at' => $now,
        ]);
    }

    // ✅ mark verified for the latest row
    public function mark_verified_latest($actorType, $actorId)
    {
        $latest = $this->get_latest_today($actorType, $actorId);
        if (!$latest)
            return false;

        return $this->db->where('id', (int) $latest->id)->update($this->table, [
            'email_verified' => 1,
            'otp_hash' => NULL,
            'otp_expire_at' => NULL,
        ]);
    }
}
