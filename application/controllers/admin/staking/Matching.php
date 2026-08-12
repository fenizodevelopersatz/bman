<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin ▸ Staking ▸ Binary Matching Bonus processor.
 * Propagates stake volume up the binary tree and pays the matching bonus
 * (Stakingmatching_model). Triggerable by an admin (AJAX) or by cron/CLI.
 */
class Matching extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper(['url']);
        $this->load->model('staking/Stakingmatching_model', 'MB');
    }

    private function _requireAdmin()
    {
        $this->load->library('session');
        $this->load->model('Admin_model');
        if (!$this->session->userdata('admin_logged_in')) redirect('aaddmmiinn/login');
        $user = $this->Admin_model->get_user($this->session->userdata('admin_userid'));
        if ($user && $user->admin_roll == '1') {
            $perm = json_decode($user->permission_pages, true);
            if (empty($perm['staking_management']) && empty($perm['rank_management'])) {
                $this->session->set_flashdata('error', 'Access Denied: You do not have permission.');
                redirect('admin');
            }
        }
    }

    private function _json($d, $c = 200)
    {
        $this->output->set_status_header($c)->set_content_type('application/json')->set_output(json_encode($d));
    }

    /** Admin-triggered run (AJAX). Returns the run summary. */
    public function run()
    {
        $this->_requireAdmin();
        if (!$this->input->is_ajax_request()) show_404();
        $summary = $this->MB->run();
        return $this->_json(['status' => 'success', 'summary' => $summary]);
    }

    /**
     * Cron / CLI run. Allowed from the command line, or over HTTP only with the
     * correct token (?token=…) that matches config 'cron_token' (if configured).
     * Usage: php index.php admin/staking/matching cron
     */
    public function cron()
    {
        if (!$this->input->is_cli_request()) {
            $token = $this->input->get('token', true);
            $expected = $this->config->item('cron_token');
            if (!$expected || $token !== $expected) show_404();
        }
        $summary = $this->MB->run();
        echo json_encode(['status' => 'success', 'summary' => $summary])."\n";
    }
}
