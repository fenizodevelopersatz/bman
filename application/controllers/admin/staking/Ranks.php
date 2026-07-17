<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin ▸ Staking ▸ Rank Achievement
 * The 11 permanent ranks (UN RANK → CHALLENGER) from proposal §10:
 * group-incentive amount, benefits (badge / certificate / reward /
 * recognition), enable/disable, and the qualification matrix
 * (Plan-1 / Plan-2 / Plan-3, left/right counts of lower ranks).
 */
class Ranks extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper(['url','security']);
        $this->load->model('Admin_model');
        $this->load->model('Staking_model', 'staking');

        if (!$this->session->userdata('admin_logged_in')) {
            redirect('admin/login');
        }
        $user = $this->Admin_model->get_user($this->session->userdata('admin_userid'));
        if ($user && $user->admin_roll == '1') {
            $permissions = json_decode($user->permission_pages, true);
            // Accept the staking key OR the legacy rank-management key.
            if (empty($permissions['staking_management']) && empty($permissions['rank_management'])) {
                $this->session->set_flashdata('error', 'Access Denied: You do not have permission.');
                redirect('admin');
            }
        }
    }

    private function _json($data = [], $code = 200)
    {
        $this->output->set_status_header($code)
                     ->set_content_type('application/json')
                     ->set_output(json_encode($data));
    }

    /* ------------------------------- page ------------------------------- */
    public function index()
    {
        $data['title']      = 'Rank Achievement';
        $data['card_tilte'] = 'Rank Achievement System';
        $data['ranks']      = $this->staking->ranks();
        $this->load->view('admin/staking/ranks', $data);
    }

    /* ------- AJAX: save incentive / volume / rewards / benefits ---------- */
    public function save($id)
    {
        if (!$this->input->is_ajax_request()) show_404();
        list($ok, $msg) = $this->staking->saveRank((int)$id, [
            'group_incentive'       => $this->input->post('group_incentive', true),
            'required_group_volume' => $this->input->post('required_group_volume', true),
            'reward_bman'           => $this->input->post('reward_bman', true),
            'reward_usdt'           => $this->input->post('reward_usdt', true),
            'reward_description'    => $this->input->post('reward_description', true),
            'badge_color'           => $this->input->post('badge_color', true),
            'benefit_badge'         => (int)$this->input->post('benefit_badge'),
            'benefit_certificate'   => (int)$this->input->post('benefit_certificate'),
            'benefit_reward'        => (int)$this->input->post('benefit_reward'),
            'benefit_recognition'   => (int)$this->input->post('benefit_recognition'),
        ], (int)$this->session->userdata('admin_userid'));
        return $this->_json(['status' => $ok ? 'success' : 'error', 'message' => $msg], $ok ? 200 : 422);
    }

    /* ------------------- AJAX: upload a rank badge ---------------------- */
    public function badge($id)
    {
        if (!$this->input->is_ajax_request()) show_404();
        if (!$this->staking->rank((int)$id)) {
            return $this->_json(['status' => 'error', 'message' => 'Rank not found.'], 404);
        }

        $dir = FCPATH . 'uploads/rank_badges/';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);

        $this->load->library('upload', [
            'upload_path'   => $dir,
            'allowed_types' => 'png|jpg|jpeg|webp|svg',
            'max_size'      => 1024,          // KB
            'encrypt_name'  => true,
        ]);
        if (!$this->upload->do_upload('badge')) {
            return $this->_json(['status' => 'error',
                                 'message' => strip_tags($this->upload->display_errors('', ''))], 422);
        }
        $path = 'uploads/rank_badges/' . $this->upload->data('file_name');
        list($ok, $msg) = $this->staking->saveRank((int)$id, ['badge_image' => $path],
                                                   (int)$this->session->userdata('admin_userid'));
        return $this->_json(['status' => $ok ? 'success' : 'error',
                            'message' => $ok ? 'Badge uploaded.' : $msg,
                            'path' => base_url($path)], $ok ? 200 : 422);
    }

    /* ------------------------- AJAX: enable/disable --------------------- */
    public function toggle($id)
    {
        if (!$this->input->is_ajax_request()) show_404();
        $active = (int)$this->input->post('active');
        $this->staking->toggleRank((int)$id, $active);
        return $this->_json(['status' => 'success', 'message' => $active ? 'Rank enabled.' : 'Rank disabled.']);
    }

    /* ----- AJAX: replace one qualification plan's requirement rows ------ *
     * POST: plan_no=1|2|3, rows=JSON
     *       [{option_no, side:left|right, required_qty, required_rank_id}]  */
    public function requirements($id)
    {
        if (!$this->input->is_ajax_request()) show_404();
        $plan_no = (int)$this->input->post('plan_no');
        $rows = json_decode((string)$this->input->post('rows'), true);
        if (!is_array($rows)) {
            return $this->_json(['status' => 'error', 'message' => 'Invalid requirement rows.'], 422);
        }
        list($ok, $msg) = $this->staking->saveRankRequirements((int)$id, $plan_no, $rows);
        return $this->_json(['status' => $ok ? 'success' : 'error', 'message' => $msg], $ok ? 200 : 422);
    }
}
