<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Earnings_ads extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('user/Earnings_ads_model', 'ads');
        $this->load->model('user/Earnings_model', 'earnings'); // reuse wallet credit
        $this->load->database();
    }

    private function requireLogin()
    {
        $user_id = (int) $this->session->userdata('user_userid');
        if (!$user_id) {
            redirect('login');
            exit;
        }
        return $user_id;
    }

    public function index()
    {
        $user_id = $this->requireLogin();
        $data['title'] = "Watch Ads";
        $data['ads'] = $this->ads->list_ads_for_user($user_id);
        $this->load->view('user/member/ads_list', $data);
    }

    public function watch($ad_id)
    {
        $user_id = $this->requireLogin();
        $ad = $this->ads->get_ad((int) $ad_id);
        if (!$ad) {
            show_404();
            return;
        }

        if ($this->ads->is_ad_rewarded($user_id, (int) $ad_id)) {
            $this->session->set_flashdata('error', 'Already rewarded for this ad.');
            redirect('user/earnings/ads');
            return;
        }

        $data['title'] = "Watch Ad: " . $ad->title;
        $data['ad'] = $ad;
        $this->load->view('user/member/ad_watch', $data);
    }

    // AJAX start session
    public function start($ad_id)
    {
        $user_id = $this->requireLogin();
        header('Content-Type: application/json');

        $ad = $this->ads->get_ad((int) $ad_id);
        if (!$ad) {
            echo json_encode(['status' => false, 'message' => 'Invalid ad']);
            return;
        }

        if ($this->ads->is_ad_rewarded($user_id, (int) $ad_id)) {
            echo json_encode(['status' => false, 'message' => 'Already rewarded']);
            return;
        }

        $session = $this->ads->create_session($user_id, (int) $ad_id, (int) $ad->duration_seconds);
        echo json_encode(['status' => true, 'token' => $session['token'], 'duration' => $session['duration']]);
    }

    // AJAX complete reward
    public function complete()
    {
        $user_id = $this->requireLogin();
        header('Content-Type: application/json');

        $token = $this->input->post('token');
        if (!$token) {
            echo json_encode(['status' => false, 'message' => 'Missing token']);
            return;
        }

        $result = $this->ads->complete_session_and_reward($user_id, $token);
        echo json_encode($result);
    }
}
