<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Lightweight AJAX beacons for the announcement banner/popup — view, click,
 * and popup-dismiss tracking. Fire-and-forget from the dashboard, doesn't
 * block navigation.
 */
class Announcementtrack extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('cms/Announcement_model');
    }

    private function _json($data = [])
    {
        $this->output->set_content_type('application/json')->set_output(json_encode($data));
    }

    public function view($id)
    {
        if (!$this->input->is_ajax_request()) show_404();
        $this->Announcement_model->trackView((int) $id);
        $this->_json(['status' => true]);
    }

    public function click($id)
    {
        if (!$this->input->is_ajax_request()) show_404();
        $this->Announcement_model->trackClick((int) $id);
        $this->_json(['status' => true]);
    }

    public function dismiss($id)
    {
        if (!$this->input->is_ajax_request()) show_404();
        $userId = (int) $this->session->userdata('user_userid');
        if (!$userId) return $this->_json(['status' => false]);
        $this->Announcement_model->trackDismiss((int) $id, $userId);
        $this->_json(['status' => true]);
    }
}
