<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Earnings_ads_model extends CI_Model
{
    public function list_ads_for_user($user_id)
    {
        $ads = $this->db->from('earning_ads')
            ->where('is_active', 1)
            ->order_by('sort_order','ASC')
            ->get()->result();

        $rewarded = $this->db->select('ad_id')->from('user_ad_rewards')
            ->where('user_id', $user_id)->get()->result();

        $map = [];
        foreach($rewarded as $r){ $map[(int)$r->ad_id]=true; }

        foreach($ads as $a){
            $a->is_rewarded = isset($map[(int)$a->id]);
        }
        return $ads;
    }

    public function get_ad($id)
    {
        return $this->db->from('earning_ads')
            ->where('id', $id)
            ->where('is_active', 1)
            ->get()->row();
    }

    public function is_ad_rewarded($user_id, $ad_id)
    {
        return $this->db->from('user_ad_rewards')
            ->where('user_id', $user_id)
            ->where('ad_id', $ad_id)
            ->count_all_results() > 0;
    }

    public function create_session($user_id, $ad_id, $duration_seconds)
    {
        $token = bin2hex(random_bytes(16));
        $now = date('Y-m-d H:i:s');
        $expected = date('Y-m-d H:i:s', time() + $duration_seconds);

        $this->db->insert('user_ad_sessions', [
            'user_id' => $user_id,
            'ad_id' => $ad_id,
            'token' => $token,
            'status' => 'started',
            'start_at' => $now,
            'expected_end_at' => $expected
        ]);

        return ['token'=>$token,'duration'=>$duration_seconds];
    }

    public function complete_session_and_reward($user_id, $token)
    {
        $session = $this->db->from('user_ad_sessions')
            ->where('token', $token)
            ->where('user_id', $user_id)
            ->get()->row();

        if (!$session) return ['status'=>false,'message'=>'Invalid session'];
        if ($session->status !== 'started') return ['status'=>false,'message'=>'Session already used'];

        if (time() < strtotime($session->expected_end_at)) {
            return ['status'=>false,'message'=>'Watch time not completed'];
        }

        if ($this->is_ad_rewarded($user_id, (int)$session->ad_id)) {
            $this->db->where('id', $session->id)->update('user_ad_sessions', ['status'=>'expired']);
            return ['status'=>false,'message'=>'Already rewarded'];
        }

        $ad = $this->get_ad((int)$session->ad_id);
        if (!$ad) return ['status'=>false,'message'=>'Ad removed'];

        $this->db->where('id', $session->id)->update('user_ad_sessions', [
            'status'=>'completed',
            'completed_at'=>date('Y-m-d H:i:s')
        ]);

        $this->db->insert('user_ad_rewards', [
            'user_id'=>$user_id,
            'ad_id'=>(int)$ad->id,
            'reward_amount'=>(float)$ad->reward_usd,
            'created_at'=>date('Y-m-d H:i:s'),
        ]);

        // credit wallet
        $this->load->model('user/Earnings_model', 'earnings');
        $this->earnings->add_wallet_tx_and_credit($user_id, 'earn', 'ads', (float)$ad->reward_usd, 'completed');

        return ['status'=>true,'message'=>'Reward credited', 'amount'=>(float)$ad->reward_usd];
    }
}
