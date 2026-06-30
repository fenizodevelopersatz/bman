<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Earnings_videos_model extends CI_Model
{
    public function list_videos_for_user($user_id)
    {
        $videos = $this->db->from('earning_videos')
            ->where('is_active', 1)
            ->order_by('sort_order', 'ASC')
            ->get()->result();

        // mark rewarded
        $rewarded = $this->db->select('video_id')->from('user_video_rewards')
            ->where('user_id', $user_id)->get()->result();
        $map = [];
        foreach ($rewarded as $r) {
            $map[(int) $r->video_id] = true;
        }

        foreach ($videos as $v) {
            $v->is_rewarded = isset($map[(int) $v->id]);
        }
        return $videos;
    }

    public function get_video($id)
    {
        return $this->db->from('earning_videos')
            ->where('id', $id)
            ->where('is_active', 1)
            ->get()->row();
    }

    public function is_video_rewarded($user_id, $video_id)
    {
        return $this->db->from('user_video_rewards')
            ->where('user_id', $user_id)
            ->where('video_id', $video_id)
            ->count_all_results() > 0;
    }

    public function create_session($user_id, $video_id, $duration_seconds)
    {
        $token = bin2hex(random_bytes(16)); // 32 chars
        $now = date('Y-m-d H:i:s');
        $expected = date('Y-m-d H:i:s', time() + $duration_seconds);

        $this->db->insert('user_video_sessions', [
            'user_id' => $user_id,
            'video_id' => $video_id,
            'token' => $token,
            'status' => 'started',
            'start_at' => $now,
            'expected_end_at' => $expected
        ]);

        return ['token' => $token, 'duration' => $duration_seconds];
    }

    public function complete_session_and_reward($user_id, $token)
    {
        $session = $this->db->from('user_video_sessions')
            ->where('token', $token)
            ->where('user_id', $user_id)
            ->get()->row();

        if (!$session)
            return ['status' => false, 'message' => 'Invalid session'];
        if ($session->status !== 'started')
            return ['status' => false, 'message' => 'Session already used'];

        // server-side time validation (user must wait until expected_end_at)
        if (time() < strtotime($session->expected_end_at)) {
            return ['status' => false, 'message' => 'Watch time not completed'];
        }

        // check already rewarded
        if ($this->is_video_rewarded($user_id, (int) $session->video_id)) {
            $this->db->where('id', $session->id)->update('user_video_sessions', [
                'status' => 'expired'
            ]);
            return ['status' => false, 'message' => 'Already rewarded'];
        }

        $video = $this->get_video((int) $session->video_id);
        if (!$video)
            return ['status' => false, 'message' => 'Video removed'];

        // mark session completed
        $this->db->where('id', $session->id)->update('user_video_sessions', [
            'status' => 'completed',
            'completed_at' => date('Y-m-d H:i:s')
        ]);

        // insert reward record (unique per user/video)
        $this->db->insert('user_video_rewards', [
            'user_id' => $user_id,
            'video_id' => (int) $video->id,
            'reward_amount' => (float) $video->reward_usd,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // add wallet tx + credit balance (reuse your earnings model function)
        $this->load->model('user/Earnings_model', 'earnings');
        $this->earnings->add_wallet_tx_and_credit($user_id, 'earn', 'videos', (float) $video->reward_usd, 'completed');

        return ['status' => true, 'message' => 'Reward credited', 'amount' => (float) $video->reward_usd];
    }
}
