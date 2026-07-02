<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class  Withdrawsettings extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');
        $this->load->model('Admin_model');

        if (!$this->session->userdata('admin_logged_in')) {
            redirect('admin/login');
        }

        $user = $this->Admin_model->get_user($this->session->userdata('admin_userid'));

        if ($user->admin_roll == '1') {
            $permissions = json_decode($user->permission_pages, true);
            if (empty($permissions['mail_settings'])) {
                $this->session->set_flashdata('error', 'Access Denied: You do not have permission.');
                redirect('admin');
            }
        }

        $this->load->model('settings/Payment_model');
    }


   
    /*
    |--------------------------------------------------------------------------
    | Mail List View
    |--------------------------------------------------------------------------
    */
    public function index() {
        $this->data['title'] = 'Withdraw Settings';
        $this->data['card_title'] = 'Edit Withdraw Settings';

        $this->data['withdraw_status'] = site_settings('withdraw_settings','withdraw_status');
        $this->data['min_withdraw'] = str_replace(',', '', site_settings('withdraw_settings','min_withdraw'));
        $this->data['max_withdraw'] = str_replace(',', '', site_settings('withdraw_settings','max_withdraw'));
        $this->data['withdraw_fee'] = site_settings('withdraw_settings','withdraw_fee');
        $this->data['withdraw_monthly_limit'] = site_settings('withdraw_settings','withdraw_monthly_limit');
        $this->data['withdraw_daily_limit'] = site_settings('withdraw_settings','withdraw_daily_limit');
        $this->data['withdraw_amount_type'] = site_settings('withdraw_settings','withdraw_amount_type');
        $this->data['auto_withdraw'] = site_settings('withdraw_settings','auto_withdraw');
        $this->data['withdraw_notification_user'] = site_settings('withdraw_settings','withdraw_notification_user');
        $this->data['withdraw_notification_admin'] = site_settings('withdraw_settings','withdraw_notification_admin');

        $this->data['currency_info'] = currency_info();
        $this->data['token_info'] = token_info();
        $this->data['withdraw_page'] = 'active';
        $this->data['token_withdraw_page'] = '';

        // Staking plan withdraw rules (Regular / Combo) — managed here so all
        // withdraw configuration lives on ONE page (the Staking Plans page
        // links here instead of duplicating these fields).
        $this->load->model('Staking_model', 'staking');
        $this->data['staking_plans'] = array_values(array_filter(
            $this->staking->plans(),
            function ($p) { return in_array($p['code'], ['regular', 'combo'], true); }
        ));

        $this->data['action'] = base_url().'withdraw-settings-update';
        $this->data['redirect'] = base_url().'withdraw-settings';

        $this->load->view('admin/settings/withdraw-edit-settings', $this->data);
    }
    /*
    |--------------------------------------------------------------------------
    | TOKEN Withdraw Settings
    |--------------------------------------------------------------------------
    */
    public function token_settings(){

        $this->data['title'] = 'Withdraw Settings';
        $this->data['card_title'] = 'Edit Withdraw Settings';

        $this->data['withdraw_status'] = site_settings('token_withdraw_settings','withdraw_status');
        $this->data['min_withdraw'] = str_replace(',', '', site_settings('token_withdraw_settings','min_withdraw'));
        $this->data['max_withdraw'] = str_replace(',', '', site_settings('token_withdraw_settings','max_withdraw'));
        $this->data['withdraw_fee'] = site_settings('token_withdraw_settings','withdraw_fee');
        $this->data['withdraw_monthly_limit'] = site_settings('token_withdraw_settings','withdraw_monthly_limit');
        $this->data['withdraw_daily_limit'] = site_settings('token_withdraw_settings','withdraw_daily_limit');
        $this->data['withdraw_amount_type'] = site_settings('token_withdraw_settings','withdraw_amount_type');
        $this->data['auto_withdraw'] = site_settings('token_withdraw_settings','auto_withdraw');
        $this->data['withdraw_notification_user'] = site_settings('token_withdraw_settings','withdraw_notification_user');
        $this->data['withdraw_notification_admin'] = site_settings('token_withdraw_settings','withdraw_notification_admin');

        $this->data['action'] = base_url().'update-token-withdraw-settings';
        $this->data['redirect'] = base_url().'token-withdraw-settings';


        $this->data['currency_info'] = token_info();
        $this->data['token_info'] = token_info();
        $this->data['withdraw_page'] = '';
        $this->data['token_withdraw_page'] = 'active';
        
        $this->load->view('admin/settings/withdraw-edit-settings', $this->data);
    }
    /*
    |--------------------------------------------------------------------------
    |  Withdraw Settings Update
    |--------------------------------------------------------------------------
    */
    public function update()
    {
    
        $this->form_validation->set_rules('min_withdraw', 'Minimum Withdraw', 'required|numeric|greater_than[0]');
        $this->form_validation->set_rules('max_withdraw', 'Maximum Withdraw', 'required|numeric|greater_than[0]|callback_check_max_withdraw');
        $this->form_validation->set_rules('withdraw_fee', 'Withdraw Fee', 'required|numeric');
        $this->form_validation->set_rules('withdraw_daily_limit', 'Withdraw Daily Limit', 'required|numeric');
        $this->form_validation->set_rules('withdraw_monthly_limit', 'Withdraw Monthly Limit', 'required|numeric');
    
        if ($this->form_validation->run() == FALSE) {
          
            $errors = $this->form_validation->error_array();
            echo json_encode(['status' => false, 'errors' => $errors]);
            exit;


        } else {

                $withdraw_status            = $this->input->post('withdraw_status') ? 1 : 0;
                $min_withdraw               = $this->input->post('min_withdraw');
                $max_withdraw               = $this->input->post('max_withdraw');
                $withdraw_fee               = $this->input->post('withdraw_fee');
                $withdraw_daily_limit       = $this->input->post('withdraw_daily_limit');
                $withdraw_monthly_limit     = $this->input->post('withdraw_monthly_limit');
                $withdraw_amount_type       = $this->input->post('withdraw_amount_type') ? 1 : 0;
                $auto_withdraw              = $this->input->post('auto_withdraw') ? 1 : 0;
                $withdraw_notification_user  = $this->input->post('withdraw_notification_user') ? 1 : 0;
                $withdraw_notification_admin = $this->input->post('withdraw_notification_admin') ? 1 : 0;
              
                $update_withdraw = $this->witdraw_update('withdraw_status',$withdraw_status);
                $update_withdraw = $this->witdraw_update('min_withdraw',$min_withdraw);
                $update_withdraw = $this->witdraw_update('max_withdraw',$max_withdraw);
                $update_withdraw = $this->witdraw_update('withdraw_fee',$withdraw_fee);
                $update_withdraw = $this->witdraw_update('withdraw_daily_limit',$withdraw_daily_limit);
                $update_withdraw = $this->witdraw_update('withdraw_monthly_limit',$withdraw_monthly_limit);
                $update_withdraw = $this->witdraw_update('withdraw_amount_type',$withdraw_amount_type);
                $update_withdraw = $this->witdraw_update('auto_withdraw',$auto_withdraw);
                $update_withdraw = $this->witdraw_update('withdraw_notification_user',$withdraw_notification_user);
                $update_withdraw = $this->witdraw_update('withdraw_notification_admin',$withdraw_notification_admin);

                echo json_encode(['status' => true, 'message' => "withdraw Settings update successfully"]);
                exit;
        }
    }
    /*
    |--------------------------------------------------------------------------
    |  Withdraw Token Settings Update
    |--------------------------------------------------------------------------
    */
    public function update_token_settings(){

        $this->form_validation->set_rules('min_withdraw', 'Minimum Withdraw', 'required|numeric|greater_than[0]');
        $this->form_validation->set_rules('max_withdraw', 'Maximum Withdraw', 'required|numeric|greater_than[0]|callback_check_max_withdraw');
        $this->form_validation->set_rules('withdraw_fee', 'Withdraw Fee', 'required|numeric');
        $this->form_validation->set_rules('withdraw_daily_limit', 'Withdraw Daily Limit', 'required|numeric');
        $this->form_validation->set_rules('withdraw_monthly_limit', 'Withdraw Monthly Limit', 'required|numeric');
    
        if ($this->form_validation->run() == FALSE) {
          
            $errors = $this->form_validation->error_array();
            echo json_encode(['status' => false, 'errors' => $errors]);
            exit;

        } else {

                $withdraw_status            = $this->input->post('withdraw_status') ? 1 : 0;
                $min_withdraw               = $this->input->post('min_withdraw');
                $max_withdraw               = $this->input->post('max_withdraw');
                $withdraw_fee               = $this->input->post('withdraw_fee');
                $withdraw_daily_limit       = $this->input->post('withdraw_daily_limit');
                $withdraw_monthly_limit     = $this->input->post('withdraw_monthly_limit');
                $withdraw_amount_type       = $this->input->post('withdraw_amount_type') ? 1 : 0;
                $auto_withdraw              = $this->input->post('auto_withdraw') ? 1 : 0;
                $withdraw_notification_user  = $this->input->post('withdraw_notification_user') ? 1 : 0;
                $withdraw_notification_admin = $this->input->post('withdraw_notification_admin') ? 1 : 0;
              
                $update_withdraw = $this->token_witdraw_update('withdraw_status',$withdraw_status);
                $update_withdraw = $this->token_witdraw_update('min_withdraw',$min_withdraw);
                $update_withdraw = $this->token_witdraw_update('max_withdraw',$max_withdraw);
                $update_withdraw = $this->token_witdraw_update('withdraw_fee',$withdraw_fee);
                $update_withdraw = $this->token_witdraw_update('withdraw_daily_limit',$withdraw_daily_limit);
                $update_withdraw = $this->token_witdraw_update('withdraw_monthly_limit',$withdraw_monthly_limit);
                $update_withdraw = $this->token_witdraw_update('withdraw_amount_type',$withdraw_amount_type);
                $update_withdraw = $this->token_witdraw_update('auto_withdraw',$auto_withdraw);
                $update_withdraw = $this->token_witdraw_update('withdraw_notification_user',$withdraw_notification_user);
                $update_withdraw = $this->token_witdraw_update('withdraw_notification_admin',$withdraw_notification_admin);

                echo json_encode(['status' => true, 'message' => "Token withdraw Settings update successfully"]);
                exit;
        }

    }

    private function witdraw_update($label,$value){

        $update_value = array(
            'settings_value' => $value
        );

        $this->db->where('settings_type','withdraw_settings');
        $this->db->where('settings_name',$label);
        $this->db->update('site_settings',$update_value);

    }

    private function token_witdraw_update($label,$value){

        $update_value = array(
            'settings_value' => $value
        );

        $this->db->where('settings_type','token_withdraw_settings');
        $this->db->where('settings_name',$label);
        $this->db->update('site_settings',$update_value);

    }


    public function check_max_withdraw($max_withdraw)
    {
        $min_withdraw = $this->input->post('min_withdraw');
    
        if ($max_withdraw < $min_withdraw) {
            $this->form_validation->set_message('check_max_withdraw', 'The Maximum Withdraw must be greater than Minimum Withdraw.');
            return FALSE;
        }
        return TRUE;
    }

}