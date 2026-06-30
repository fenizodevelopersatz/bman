<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Earnings_videos extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('user/Earnings_videos_model', 'vid');
        $this->load->model('user/Earnings_model', 'earnings'); // for wallet credit function
        $this->load->database();
    }

    private function requireLogin()
    {
        $user_id = (int) $this->session->userdata('userid');
        if (!$user_id) {
            redirect('login');
            exit;
        }
        return $user_id;
    }

    public function index()
    {
        $user_id = $this->requireLogin();

        $data['title'] = "Watch Videos";
        $data['videos'] = $this->vid->list_videos_for_user($user_id);

        $this->load->view('user/member/videos_list', $data);
    }

    public function watch($video_id)
    {
        $user_id = $this->requireLogin();

        $video = $this->vid->get_video((int) $video_id);
        if (!$video) {
            show_404();
            return;
        }

        // block if already rewarded
        if ($this->vid->is_video_rewarded($user_id, (int) $video_id)) {
            $this->session->set_flashdata('error', 'Already rewarded for this video.');
            redirect('user/earnings/videos');
            return;
        }

        $data['title'] = "Watch: " . $video->title;
        $data['video'] = $video;

        $this->load->view('user/member/video_watch', $data);
    }

    // AJAX: start session (creates token + expected end time)
    public function start($video_id)
    {
        $user_id = $this->requireLogin();
        header('Content-Type: application/json');

        $video = $this->vid->get_video((int) $video_id);
        if (!$video) {
            echo json_encode(['status' => false, 'message' => 'Invalid video']);
            return;
        }

        if ($this->vid->is_video_rewarded($user_id, (int) $video_id)) {
            echo json_encode(['status' => false, 'message' => 'Already rewarded']);
            return;
        }

        $session = $this->vid->create_session($user_id, (int) $video_id, (int) $video->duration_seconds);
        echo json_encode(['status' => true, 'token' => $session['token'], 'duration' => $session['duration']]);
    }

    // AJAX: complete session after timer ends
    public function complete()
    {
        $user_id = $this->requireLogin();
        header('Content-Type: application/json');

        $token = $this->input->post('token');
        if (!$token) {
            echo json_encode(['status' => false, 'message' => 'Missing token']);
            return;
        }

        $result = $this->vid->complete_session_and_reward($user_id, $token);
        echo json_encode($result);
    }
}
