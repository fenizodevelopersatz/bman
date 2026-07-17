<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Rank API — member-facing JSON for the new rank system.
 * ----------------------------------------------------------------------------
 * Session-authenticated, same as the rest of the member area. Every endpoint
 * answers for the LOGGED-IN member only — the user id is taken from the session
 * and never from the request, so one member cannot read another's rank data.
 * The one exception is badge($user_id), which exposes nothing private (rank
 * name + badge art) and is what the genealogy tree and leaderboard render.
 *
 * GET  api/rank            — my achievement rank, power rank, next-rank progress
 * GET  api/rank/progress   — just the progress card
 * GET  api/rank/history    — my promotions
 * GET  api/rank/rewards    — my rewards
 * GET  api/rank/certificates — my certificates
 * GET  api/rank/ladder     — all 11 ranks + requirements (public config)
 * GET  api/rank/leaderboard — top members by rank (badge + username only)
 * GET  api/rank/badge/(id) — one member's badge (for tree nodes)
 * GET  api/rank/notifications — my unread rank notifications
 * POST api/rank/notifications/read — mark them read
 *
 * These are READ-only. Nothing here can promote a member or move money — rank
 * changes come from the cron alone.
 */
class Rankapi extends CI_Controller
{
    private $user_id;

    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');
        $this->load->model('Staking_model', 'staking');
        $this->load->model('staking/Rankachievement_model', 'engine');
        $this->load->model('staking/Rankreward_model', 'rewards');
        $this->load->model('staking/Rankpower_model', 'power');

        $this->user_id = (int)$this->session->userdata('user_userid');
        if (!$this->user_id) {
            $this->output->set_status_header(401)
                         ->set_content_type('application/json')
                         ->set_output(json_encode(['status' => 'error', 'message' => 'Not logged in.']));
            exit;
        }
    }

    private function _json($data, $code = 200)
    {
        $this->output->set_status_header($code)
                     ->set_content_type('application/json')
                     ->set_output(json_encode($data));
    }

    /** Everything the dashboard rank card needs, in one call. */
    public function index()
    {
        $rank  = $this->engine->currentRank($this->user_id);
        $power = $this->power->userPower($this->user_id);

        return $this->_json([
            'status' => 'success',
            'achievement' => $rank ? [
                'rank'        => $rank['highest_rank'],
                'tier'        => (int)$rank['highest_tier'],
                'badge_image' => $rank['badge_image'] ? base_url($rank['badge_image']) : null,
                'badge_color' => $rank['badge_color'],
                'achieved_at' => $rank['achieved_at'],
                'permanent'   => true,
            ] : null,
            // Power is what Group Incentive actually pays on — surfaced next to
            // the permanent rank so the difference is visible to the member.
            'power' => $power ? [
                'rank'         => $power['power_rank'],
                'tier'         => $power['tier_level'] !== null ? (int)$power['tier_level'] : null,
                'badge_color'  => $power['badge_color'],
                'cycle_no'     => (int)$power['cycle_no'],
                'cycle_start'  => $power['start_date'],
                'cycle_end'    => $power['end_date'],
                'left_volume'  => $power['left_volume'],
                'right_volume' => $power['right_volume'],
                'total_volume' => $power['total_volume'],
                'qualified'    => (bool)$power['qualified'],
                'group_incentive' => !empty($power['qualified']) ? $power['group_incentive'] : '0',
                'resets_on'    => $power['end_date'],
            ] : null,
            'progress' => $this->engine->nextRankProgress($this->user_id),
        ]);
    }

    public function progress()
    {
        return $this->_json(['status' => 'success',
                             'progress' => $this->engine->nextRankProgress($this->user_id)]);
    }

    public function history()
    {
        return $this->_json(['status' => 'success',
                             'history' => $this->engine->userHistory($this->user_id)]);
    }

    public function rewards()
    {
        return $this->_json(['status' => 'success',
                             'rewards' => $this->rewards->rewards(['user_id' => $this->user_id], 100)]);
    }

    public function certificates()
    {
        $rows = $this->rewards->certificates(['user_id' => $this->user_id], 100);
        foreach ($rows as &$r) {
            $r['view_url'] = base_url('admin/staking/rank-certificate/' . rawurlencode($r['certificate_no']));
        }
        return $this->_json(['status' => 'success', 'certificates' => $rows]);
    }

    /** The full ladder — public rank config, for the "all ranks" page. */
    public function ladder()
    {
        $ranks = $this->staking->ranks(true);
        $out = [];
        foreach ($ranks as $r) {
            $plans = [];
            foreach ($r['requirements'] as $q) {
                $plans[$q['plan_no']][$q['option_no']][] = [
                    'side' => $q['side'],
                    'qty'  => (int)$q['required_qty'],
                    'rank' => $q['required_rank_name'],
                ];
            }
            $out[] = [
                'id'              => (int)$r['id'],
                'tier'            => (int)$r['tier_level'],
                'name'            => $r['name'],
                'required_volume' => $r['required_group_volume'],
                'group_incentive' => $r['group_incentive'],
                'reward_bman'     => $r['reward_bman'],
                'reward_usdt'     => $r['reward_usdt'],
                'reward_note'     => $r['reward_description'],
                'badge_image'     => $r['badge_image'] ? base_url($r['badge_image']) : null,
                'badge_color'     => $r['badge_color'],
                'benefits'        => [
                    'badge'       => (bool)$r['benefit_badge'],
                    'certificate' => (bool)$r['benefit_certificate'],
                    'reward'      => (bool)$r['benefit_reward'],
                    'recognition' => (bool)$r['benefit_recognition'],
                ],
                // plans are OR routes; options inside a plan are OR; the
                // conditions inside one option are AND.
                'plans' => $plans,
            ];
        }
        return $this->_json(['status' => 'success', 'ranks' => $out]);
    }

    /**
     * One member's badge — deliberately public to any logged-in member because
     * it carries nothing private. This is what genealogy tree nodes render.
     */
    public function badge($user_id)
    {
        $r = $this->engine->currentRank((int)$user_id);
        return $this->_json([
            'status' => 'success',
            'user_id' => (int)$user_id,
            'rank'    => $r ? $r['highest_rank'] : null,
            'tier'    => $r ? (int)$r['highest_tier'] : null,
            'badge_image' => ($r && $r['badge_image']) ? base_url($r['badge_image']) : null,
            'badge_color' => $r ? $r['badge_color'] : null,
        ]);
    }

    /** Recognition leaderboard — highest ranks first. Username + badge only. */
    public function leaderboard()
    {
        $limit = min(100, max(1, (int)$this->input->get('limit') ?: 25));
        $rows  = $this->engine->rankedMembers([], $limit, 0);
        $out = [];
        foreach ($rows as $i => $r) {
            $out[] = [
                'position'    => $i + 1,
                'user_id'     => (int)$r['user_id'],
                'username'    => $r['username'],
                'rank'        => $r['rank_name'],
                'tier'        => (int)$r['tier_level'],
                'badge_image' => $r['badge_image'] ? base_url($r['badge_image']) : null,
                'badge_color' => $r['badge_color'],
                'achieved_at' => $r['achieved_at'],
                'is_me'       => (int)$r['user_id'] === $this->user_id,
            ];
        }
        return $this->_json(['status' => 'success', 'leaderboard' => $out]);
    }

    public function notifications()
    {
        $rows = $this->db->where('user_id', $this->user_id)
                         ->where_in('type', ['rank_achievement','rank_reward','rank_certificate'])
                         ->order_by('id', 'DESC')->limit(50)
                         ->get('user_notifications')->result_array();
        $unread = 0;
        foreach ($rows as $r) if (!$r['is_read']) $unread++;
        return $this->_json(['status' => 'success', 'unread' => $unread, 'notifications' => $rows]);
    }

    /** Mark my rank notifications read. Scoped to the session user. */
    public function read()
    {
        $id = (int)$this->input->post('id');
        $this->db->where('user_id', $this->user_id)
                 ->where_in('type', ['rank_achievement','rank_reward','rank_certificate']);
        if ($id) $this->db->where('id', $id);
        $this->db->update('user_notifications', ['is_read' => 1]);
        return $this->_json(['status' => 'success', 'message' => 'Marked read.']);
    }
}
