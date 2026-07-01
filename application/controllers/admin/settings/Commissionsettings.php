<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Commissionsettings extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->library(['session', 'form_validation']);
        $this->load->helper(['url', 'security']);
        $this->load->model('Admin_model');

        if (!$this->session->userdata('admin_logged_in'))
            redirect('admin/login');

        $user = $this->Admin_model->get_user($this->session->userdata('admin_userid'));
        if ($user->admin_roll == '1') {
            $permissions = json_decode($user->permission_pages, true);
            if (empty($permissions['commission_settings'])) {
                $this->session->set_flashdata('error', 'Access Denied: You do not have permission.');
                redirect('admin');
            }
        }
        $this->load->model('settings/Commission_model');
    }

    public function index()
    {
        $this->data['title'] = 'Commission Settings';
        $this->data['card_title'] = 'Commission Settings';
        $this->data['commissioninfo'] = $this->Commission_model->get_settings(); // returns row id=1
        $this->load->view('admin/settings/commisssion-edit-settings', $this->data);
    }

    public function update()
    {
        // Only fields that exist in commission_config
        $this->form_validation->set_rules('binary_pair_type', 'Binary Pair Type', 'trim|required|in_list[percent,amount]');
        $this->form_validation->set_rules('binary_pair_ratio', 'Binary Pair Ratio', 'trim|required|regex_match[/^(1:1|1:2|2:1)$/]');
        $this->form_validation->set_rules('direct_commission_type', 'Direct Commission Type', 'trim|required|in_list[percent,amount]');

        if ($this->form_validation->run() === FALSE) {
            echo json_encode(['status' => false, 'message' => strip_tags(validation_errors())]);
            return;
        }

        $carry_forward_status = (int) $this->input->post('carry_forward_status');
        $carry_forward_mode = $this->input->post('carry_forward_mode') ?: 'LIFETIME';
        $carry_forward_cap = $this->input->post('carry_forward_cap');
        $carry_forward_cap = ($carry_forward_cap === '' || $carry_forward_cap === null) ? null : (float) $carry_forward_cap;

        $data = [
            'direct_commission_status' => $this->input->post('direct_commission_status') ? 1 : 0,
            'level_commission_status' => $this->input->post('level_commission_status') ? 1 : 0,
            'binary_commission_status' => $this->input->post('binary_commission_status') ? 1 : 0,
            'matching_bonus_status' => $this->input->post('matching_bonus_status') ? 1 : 0,
            'repurchase_commission_status' => $this->input->post('repurchase_commission_status') ? 1 : 0,
            'leadership_bonus_status' => $this->input->post('leadership_bonus_status') ? 1 : 0,
            'pool_bonus_status' => $this->input->post('pool_bonus_status') ? 1 : 0,
            'own_commission_status' => $this->input->post('own_commission_status') ? 1 : 0,

            'binary_pair_type' => $this->input->post('binary_pair_type', true),
            'binary_pair_ratio' => $this->input->post('binary_pair_ratio', true),
            'direct_commission_type' => $this->input->post('direct_commission_type', true),

            'carry_forward_status' => $carry_forward_status,
            'carry_forward_mode' => $carry_forward_mode,
            'carry_forward_cap' => $carry_forward_cap,

            'update_date' => date('Y-m-d H:i:s'),
        ];

        // Persist (expects model to update id=1)
        $ok = $this->Commission_model->save_settings($data);

        echo json_encode([
            'status' => (bool) $ok,
            'message' => $ok ? 'Commission settings updated successfully' : 'Commission settings update failed'
        ]);
    }
}
