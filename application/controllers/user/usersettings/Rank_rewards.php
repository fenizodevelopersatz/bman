<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Rank_rewards extends CI_Controller
{

  public function __construct()
  {
    parent::__construct();
    if (!$this->session->userdata('user_userid'))
      redirect('login');

    $this->load->model('user/RankModel', 'rankM');
  }

  public function index()
  {
    $user_id = (int) $this->session->userdata('user_userid');
    $data = $this->rankM->getRankPageData($user_id);
    $this->load->view('user/member/rank-reward', $data);
  }
}
