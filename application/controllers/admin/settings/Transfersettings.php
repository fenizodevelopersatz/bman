<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class  Transfersettings extends CI_Controller {

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
            if (empty($permissions['transfer_settings'])) {
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
        $this->data['title'] = 'Internel Transfer Settings';
        $this->data['card_title'] = 'Edit Transfer Settings';

        $this->data['withdraw_status'] = site_settings('transfer_settings','transfer_status');
    
        $this->data['min_withdraw'] = str_replace(',', '', site_settings('transfer_settings','min_transfer'));
        $this->data['max_withdraw'] = str_replace(',', '', site_settings('transfer_settings','max_transfer'));

        $this->data['withdraw_fee'] = site_settings('transfer_settings','transfer_fee');
        $this->data['withdraw_daily_limit'] = site_settings('transfer_settings','transfer_daily_limit');
        $this->data['withdraw_amount_type'] = site_settings('transfer_settings','transfer_amount_type');
        $this->data['withdraw_notification_user'] = site_settings('transfer_settings','transfer_notification_user');
        $this->data['withdraw_notification_admin'] = site_settings('transfer_settings','transfer_notification_admin');

        $this->data['currency_info'] = currency_info();
        $this->data['token_info'] = token_info();
        $this->data['withdraw_page'] = 'active';
        $this->data['token_withdraw_page'] = '';

        $this->data['action'] = base_url().'transfer-settings-update';
        $this->data['redirect'] = base_url().'transfer-settings';
        
        $this->load->view('admin/settings/transfer-edit-settings', $this->data);
    }
      /*
    |--------------------------------------------------------------------------
    | Swap List View
    |--------------------------------------------------------------------------
    */
    public function swap() {
        $this->data['title'] = 'Swap Settings';
        $this->data['card_title'] = 'Edit Swap Settings';

        $this->data['withdraw_status'] = site_settings('swap_settings','swap_status');
        $this->data['min_withdraw'] = str_replace(',', '', site_settings('swap_settings','min_swap'));
        $this->data['max_withdraw'] = str_replace(',', '', site_settings('swap_settings','max_swap'));
        $this->data['withdraw_fee'] = site_settings('swap_settings','swap_fee');
        $this->data['withdraw_daily_limit'] = site_settings('swap_settings','swap_daily_limit');
        $this->data['withdraw_amount_type'] = site_settings('swap_settings','swap_amount_type');
        $this->data['withdraw_notification_user'] = site_settings('swap_settings','swap_notification_user');
        $this->data['withdraw_notification_admin'] = site_settings('swap_settings','swap_notification_admin');

        $this->data['currency_info'] = currency_info();
        $this->data['token_info'] = token_info();
        $this->data['withdraw_page'] = 'active';
        $this->data['token_withdraw_page'] = '';

        $this->data['action'] = base_url().'swap-settings-update';
        $this->data['redirect'] = base_url().'swap-settings';
        
        $this->load->view('admin/settings/swap-edit-settings', $this->data);
    }
      /*
    |--------------------------------------------------------------------------
    |  Transfer Settings Update
    |--------------------------------------------------------------------------
    */
    public function swap_update()
    {
    
        $this->form_validation->set_rules('min_swap', 'Minimum Swap', 'required|numeric|greater_than[0]');
        $this->form_validation->set_rules('max_swap', 'Maximum Swap', 'required|numeric|greater_than[0]|callback_check_max_swap');
        $this->form_validation->set_rules('swap_fee', 'Swap Fee', 'required|numeric');
        $this->form_validation->set_rules('swap_daily_limit', 'Swap Daily Limit', 'required|numeric');
    
        if ($this->form_validation->run() == FALSE) {
          
            $errors = $this->form_validation->error_array();
            echo json_encode(['status' => false, 'errors' => $errors]);
            exit;


        } else {

                $withdraw_status            = $this->input->post('swap_status') ? 1 : 0;
                $min_withdraw               = $this->input->post('min_swap');
                $max_withdraw               = $this->input->post('max_swap');
                $withdraw_fee               = $this->input->post('swap_fee');
                $withdraw_daily_limit       = $this->input->post('swap_daily_limit');
                $withdraw_amount_type       = $this->input->post('swap_amount_type') ? 1 : 0;
                $withdraw_notification_user  = $this->input->post('swap_notification_user') ? 1 : 0;
                $withdraw_notification_admin = $this->input->post('swap_notification_admin') ? 1 : 0;
              
                $update_withdraw = $this->swap_update_table('swap_status',$withdraw_status);
                $update_withdraw = $this->swap_update_table('min_swap',$min_withdraw);
                $update_withdraw = $this->swap_update_table('max_swap',$max_withdraw);
                $update_withdraw = $this->swap_update_table('swap_fee',$withdraw_fee);
                $update_withdraw = $this->swap_update_table('swap_daily_limit',$withdraw_daily_limit);
                $update_withdraw = $this->swap_update_table('swap_amount_type',$withdraw_amount_type);
                $update_withdraw = $this->swap_update_table('swap_notification_user',$withdraw_notification_user);
                $update_withdraw = $this->swap_update_table('swap_notification_admin',$withdraw_notification_admin);

                echo json_encode(['status' => true, 'message' => "Swap Settings update successfully"]);
                exit;
        }
    }
    
    /*
    |--------------------------------------------------------------------------
    |  Transfer Settings Update
    |--------------------------------------------------------------------------
    */
    public function update()
    {
    
        $this->form_validation->set_rules('min_transfer', 'Minimum Transfer', 'required|numeric|greater_than[0]');
        $this->form_validation->set_rules('max_transfer', 'Maximum Transfer', 'required|numeric|greater_than[0]|callback_check_max_withdraw');
        $this->form_validation->set_rules('transfer_fee', 'Transfer Fee', 'required|numeric');
        $this->form_validation->set_rules('transfer_daily_limit', 'Withdraw Daily Limit', 'required|numeric');
    
        if ($this->form_validation->run() == FALSE) {
          
            $errors = $this->form_validation->error_array();
            echo json_encode(['status' => false, 'errors' => $errors]);
            exit;


        } else {

                $withdraw_status            = $this->input->post('transfer_status') ? 1 : 0;
                $min_withdraw               = $this->input->post('min_transfer');
                $max_withdraw               = $this->input->post('max_transfer');
                $withdraw_fee               = $this->input->post('transfer_fee');
                $withdraw_daily_limit       = $this->input->post('transfer_daily_limit');
                $withdraw_amount_type       = $this->input->post('transfer_amount_type') ? 1 : 0;
                $withdraw_notification_user  = $this->input->post('transfer_notification_user') ? 1 : 0;
                $withdraw_notification_admin = $this->input->post('transfer_notification_admin') ? 1 : 0;
              
                $update_withdraw = $this->witdraw_update('transfer_status',$withdraw_status);
                $update_withdraw = $this->witdraw_update('min_transfer',$min_withdraw);
                $update_withdraw = $this->witdraw_update('max_transfer',$max_withdraw);
                $update_withdraw = $this->witdraw_update('transfer_fee',$withdraw_fee);
                $update_withdraw = $this->witdraw_update('transfer_daily_limit',$withdraw_daily_limit);
                $update_withdraw = $this->witdraw_update('transfer_amount_type',$withdraw_amount_type);
                $update_withdraw = $this->witdraw_update('transfer_notification_user',$withdraw_notification_user);
                $update_withdraw = $this->witdraw_update('transfer_notification_admin',$withdraw_notification_admin);

                echo json_encode(['status' => true, 'message' => "Transfer Settings update successfully"]);
                exit;
        }
    }
    private function witdraw_update($label,$value){

        $update_value = array(
            'settings_value' => $value
        );

        $this->db->where('settings_type','transfer_settings');
        $this->db->where('settings_name',$label);
        $this->db->update('site_settings',$update_value);

    }


    private function swap_update_table($label,$value){

        $update_value = array(
            'settings_value' => $value
        );

        $this->db->where('settings_type','swap_settings');
        $this->db->where('settings_name',$label);
        $this->db->update('site_settings',$update_value);

    }


    public function check_max_withdraw($max_withdraw)
    {
        $min_withdraw = $this->input->post('min_transfer');
    
        if ($max_withdraw < $min_withdraw) {
            $this->form_validation->set_message('check_max_withdraw', 'The Maximum Transfer must be greater than Minimum Transfer.');
            return FALSE;
        }
        return TRUE;
    }

    public function check_max_swap($max_withdraw)
    {
        $min_withdraw = $this->input->post('min_swap');
    
        if ($max_withdraw < $min_withdraw) {
            $this->form_validation->set_message('check_max_withdraw', 'The Maximum Swap must be greater than Minimum Swap.');
            return FALSE;
        }
        return TRUE;
    }

}