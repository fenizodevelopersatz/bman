<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Member ▸ Rank & Rewards
 *
 * Serves the BMAN Rank Achievement System (§10) + Rank Power (§11) page.
 *
 * The old pairing engine (application/models/user/RankModel.php) is no longer
 * used here — the new system has no pairs, PV, weak-leg BV, carry forward or
 * cycle matching. Rank is earned on downline group volume plus a left/right
 * team-rank matrix, and is permanent once achieved.
 */
class Rank_rewards extends CI_Controller
{
  public function __construct()
  {
    parent::__construct();
    if (!$this->session->userdata('user_userid'))
      redirect('login');

    $this->load->model('user/Memberrank_model', 'memberrank');
  }

  public function index()
  {
    $user_id = (int) $this->session->userdata('user_userid');
    $data = $this->memberrank->pageData($user_id);

    // Admin-uploaded downloadable files for this member: those flagged "all
    // ranks" plus any tied to a rank the member currently holds / has achieved.
    $this->load->model('staking/Rankfiles_model', 'rankfiles');
    $ur = $this->db->select('current_rank_id, highest_rank_id')
                   ->get_where('user_ranks', ['user_id' => $user_id])->row_array();
    $rankIds = [];
    if ($ur) {
      if (!empty($ur['current_rank_id'])) $rankIds[] = (int) $ur['current_rank_id'];
      if (!empty($ur['highest_rank_id'])) $rankIds[] = (int) $ur['highest_rank_id'];
    }
    $data['rank_files'] = $this->rankfiles->forUser($rankIds);

    $this->load->view('user/member/rank-reward', $data);
  }

  /**
   * A member's own certificate, print-ready.
   *
   * The admin route (admin/staking/rank-certificate/…) is behind the admin
   * session, so members cannot use it. This is the member-scoped equivalent:
   * it looks the certificate up BY user_id AND number, so a member can only
   * ever open their own — guessing another member's number returns 404.
   */
  public function certificate($cert_no)
  {
    $user_id = (int) $this->session->userdata('user_userid');
    $this->load->model('staking/Rankreward_model', 'rk_rewards');

    $c = $this->rk_rewards->certificate($cert_no);
    if (!$c || (int) $c['user_id'] !== $user_id)
      show_404();

    $this->load->view('user/member/rank_certificate_print', ['c' => $c]);
  }

  /**
   * Live refresh for the page (JSON). Lets a member re-check progress without a
   * full reload. Read-only — it cannot promote anyone; only the hourly cron does.
   */
  public function status()
  {
    if (!$this->input->is_ajax_request())
      show_404();

    $user_id = (int) $this->session->userdata('user_userid');
    $d = $this->memberrank->pageData($user_id);

    $this->output->set_content_type('application/json')->set_output(json_encode([
      'status'    => 'success',
      'rank'      => $d['rank'],
      'next'      => $d['next'],
      'volume'    => $d['volume'],
      'power'     => $d['power'],
      'incentive' => $d['incentive'],
    ]));
  }
}
