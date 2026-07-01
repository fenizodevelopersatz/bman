<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class  Advancesettings extends CI_Controller {

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
            if (empty($permissions['advance_settings'])) {
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
        $this->data['title'] = 'Advance Settings';
        $this->data['card_title'] = 'Advance Settings List';
        $this->data['active_nav'] = 'currency-settings';
        $this->load->view('admin/settings/advance-settings', $this->data);
    }
    /*
    |--------------------------------------------------------------------------
    | IP Blocker View
    |--------------------------------------------------------------------------
    */
    public function ipblock_add() {

        
        if($this->input->post()){
           
        $this->form_validation->set_rules('ip_number', 'IP Number', 'required');

        if ($this->form_validation->run() == FALSE) {

        $errors = $this->form_validation->error_array();
        echo json_encode(['status' => false, 'errors' => $errors]);
        exit;

        } else {

        $ip_address              = $this->input->post('ip_number');
        $reason                  = $this->input->post('reason');

        $check_ip = $this->db->query("SELECT * FROM blocked_ips WHERE ip_address = '".$ip_address."' ")->num_rows();

        if($check_ip <= 0){

            $insert_db = array(
                "ip_address" => $ip_address,
                "reason" => $reason,
                "created_at" => date('Y-m-d H:i:s')
            );
    
            $insert = $this->db->insert("blocked_ips",$insert_db);

            echo json_encode(['status' => true, 'message' => "IP Added Successfully"]);
            exit;

        } else {

            echo json_encode(['status' => false, 'message' => "This IP already blocked !"]);
            exit;

        }
        

       

        }


        } else {

        $this->data['title'] = 'IP Blocker';
        $this->data['card_title'] = 'Add IP to block';
        $this->data['block_ip'] = 0;
        $this->data['ip_number'] = "";
        $this->data['reason'] = "";
        $this->load->view('admin/settings/edit-ip_block-settings', $this->data);
        
        }

    }
    /*
    |--------------------------------------------------------------------------
    | IP Blocker Delete
    |--------------------------------------------------------------------------
    */
    public function delete_ip($id) {

        if($id){

            $curency_info = $this->db->query("SELECT * FROM blocked_ips WHERE id = '".$id."' ")->num_rows();

            if($curency_info){

                    $this->db->where('id',$id);
                    $this->db->delete('blocked_ips');
                
                    echo json_encode(['status' => "success", 'message' => "selected IP delete successfully. "]);
                    exit;        

                } else {
                echo json_encode(['status' => false, 'message' => "incorrect IP delete request !"]);
                exit;    
            }

        } else {
            echo json_encode(['status' => false, 'message' => "incorrect IP delete request !"]);
            exit;
        }
        
    }   
    /*
    |--------------------------------------------------------------------------
    | IP Blocker
    |--------------------------------------------------------------------------
    */
    public function ip_block() {

            $this->data['title'] = 'Advance Settings';
            $this->data['card_title'] = 'Advance Settings List';
            $this->data['active_nav'] = 'ip-block';
            $this->load->view('admin/settings/ip-block-list', $this->data);
        
    }
     /*
    |--------------------------------------------------------------------------
    | Mail List View
    |--------------------------------------------------------------------------
    */
    public function captcha_settings_update() {

        if($this->input->post()){

            $this->form_validation->set_rules('sitekey', 'Site Key', 'required');
            $this->form_validation->set_rules('secretkey', 'Secret Key', 'required');

            if ($this->form_validation->run() == FALSE) {

            $errors = $this->form_validation->error_array();
            echo json_encode(['status' => false, 'errors' => $errors]);
            exit;

            } else {

            $sitekey                    = $this->input->post('sitekey');
            $secretkey                  = $this->input->post('secretkey');

            $update_captcha = $this->captchasettings_update('sitekey',$sitekey);
            $update_captcha = $this->captchasettings_update('secretkey',$secretkey);

            echo json_encode(['status' => true, 'message' => "Captcha Settings update successfully"]);
            exit;

            }
            

        } else {
            

        $this->data['title'] = 'Advance Settings';
        $this->data['card_title'] = 'Advance Settings List';
        $this->data['active_nav'] = 'captcha-settings';

        $this->data['sitekey'] = site_settings('captcha','sitekey');
        $this->data['secretkey'] = site_settings('captcha','secretkey');

        $this->data['currency_info'] = currency_info();

        $this->load->view('admin/settings/captcha-edit-settings', $this->data);

        }

    }
    /*
    |--------------------------------------------------------------------------
    | USER SETTINGS EDIT VIEW
    |--------------------------------------------------------------------------
    */
    public function user_settings_update(){

        if($this->input->post()){

            $this->form_validation->set_rules('min_password_length', 'Minimum Password', 'required|numeric|greater_than[0]');
            $this->form_validation->set_rules('max_password_length', 'Maximum Password', 'required|numeric|greater_than[0]|callback_check_max_limit');

            if ($this->form_validation->run() == FALSE) {

            $errors = $this->form_validation->error_array();
            echo json_encode(['status' => false, 'errors' => $errors]);
            exit;

            } else {

            $min_password_length               = $this->input->post('min_password_length');
            $max_password_length               = $this->input->post('max_password_length');

            $twofa_withdraw                    = $this->input->post('twofa_withdraw') ? 1 : 0;  //  Move to Dex
            $twofa_editprofile                 = $this->input->post('twofa_editprofile') ? 1 : 0;  // Dex Wallet Send Money
            $twofa_login                       = $this->input->post('twofa_login') ? 1 : 0;
            $twofa_internel_transfer           = $this->input->post('twofa_internel_transfer') ? 1 : 0; // Internel Transfer

            $update_withdraw = $this->usersettings_update('min_password_length',$min_password_length);
            $update_withdraw = $this->usersettings_update('max_password_length',$max_password_length);

            $update_withdraw = $this->usersettings_update('twofa_withdraw',$twofa_withdraw); //  Move to Dex
            $update_withdraw = $this->usersettings_update('twofa_editprofile',$twofa_editprofile); // Dex Wallet Send Money
            $update_withdraw = $this->usersettings_update('twofa_login',$twofa_login);
            $update_withdraw = $this->usersettings_update('twofa_internel_transfer',$twofa_internel_transfer);  // Internel Transfer

            echo json_encode(['status' => true, 'message' => "User Settings update successfully"]);
            exit;
            }
            

        } else {
            
        $this->data['title'] = 'Advance Settings';
        $this->data['card_title'] = 'Advance Settings List';
        $this->data['active_nav'] = 'user-settings';

        $this->data['twofa_login'] = site_settings('user_settings','twofa_login');
        $this->data['twofa_editprofile'] = site_settings('user_settings','twofa_editprofile');
        $this->data['twofa_withdraw'] = site_settings('user_settings','twofa_withdraw');
        $this->data['twofa_internel_transfer'] = site_settings('user_settings','twofa_internel_transfer');
        $this->data['min_password_length'] = site_settings('user_settings','min_password_length');
        $this->data['max_password_length'] = site_settings('user_settings','max_password_length');

        $this->data['currency_info'] = currency_info();
        $this->load->view('admin/settings/user-edit-settings', $this->data);

        }

    }
    /*
    |--------------------------------------------------------------------------
    | Captcha Settings Update
    |--------------------------------------------------------------------------
    */
    private function captchasettings_update($label,$value){

        $update_value = array(
            'settings_value' => $value
        );

        $this->db->where('settings_type','captcha');
        $this->db->where('settings_name',$label);
        $this->db->update('site_settings',$update_value);

    }
    /*
    |--------------------------------------------------------------------------
    | User Settings Update
    |--------------------------------------------------------------------------
    */
    private function usersettings_update($label,$value){

        $update_value = array(
            'settings_value' => $value
        );

        $this->db->where('settings_type','user_settings');
        $this->db->where('settings_name',$label);
        $this->db->update('site_settings',$update_value);

    }
     /*
    |--------------------------------------------------------------------------
    | Check Email Limit
    |--------------------------------------------------------------------------
    */
    public function check_max_limit($max_withdraw)
    {
        $min_withdraw = $this->input->post('min_password_length');
    
        if ($max_withdraw < $min_withdraw) {
            $this->form_validation->set_message('check_max_withdraw', 'The Maximum Password must be greater than Minimum Password.');
            return FALSE;
        }
        return TRUE;
    }
    /*
    |--------------------------------------------------------------------------
    | Currency Edi View
    |--------------------------------------------------------------------------
    */
   public function add(){
    $this->data['title'] = 'Currency Settings';
    $this->data['card_title'] = 'Add Currency Settings';
    $this->data['currency_id'] = 0;
    $this->data['coin_name'] = "";
    $this->data['decimal'] = "";
    $this->data['currency_symbol'] = "";
    $this->data['action'] =  base_url()."currency-update"; 
    $this->data['redirect'] =  base_url()."advance-settings";
    $this->load->view('admin/settings/edit-currency-settings', $this->data);
   }
    /*
    |--------------------------------------------------------------------------
    | Currency Edi View
    |--------------------------------------------------------------------------
    */
    public function edit($id){
        
        $currency_info = $this->db->query("SELECT * FROM currency_config where id= '".$id."' ")->row();

        if($currency_info){
            $this->data['title'] = 'Currency Settings';
            $this->data['card_title'] = 'Edit Currency Settings';
            $this->data['currency_id'] = $id;
            $this->data['coin_name'] = $currency_info->coin_name;
            $this->data['decimal'] = $currency_info->decimal;
            $this->data['currency_symbol'] = $currency_info->currency_symbol;
            $this->data['currency_value'] = $currency_info->currency_value;
            $this->data['value_text'] = "<b>".$currency_info->currency_value." ".$currency_info->coin_name." = 1 USDT  </b> ";
            
            $this->data['action'] =  base_url()."currency-update"; 
            $this->data['redirect'] =  base_url()."advance-settings";
            $this->load->view('admin/settings/edit-currency-settings', $this->data);
        } else {
            
            $this->session->set_flashdata('danger', 'Invalide Currency Please Try Again');
            redirect('advance-settings');
        }

    }
   /*
    |--------------------------------------------------------------------------
    | Currency UPDATE
    |--------------------------------------------------------------------------
    */
    public function update() {

        $this->form_validation->set_rules('coin_name', 'Coin Name', 'required|regex_match[/^[A-Za-z\s]+$/]', 
            array(
                'required' => 'The Coin Name is required.',
                'regex_match' => 'The Coin Name must only contain letters.'
            )
        );
    
        $this->form_validation->set_rules('decimal', 'Decimal', 'required|regex_match[/^\d+(\.\d+)?$/]', 
            array(
                'required' => 'The Decimal Value is required.',
                'regex_match' => 'Only numbers and decimal values are allowed.'
            )
        );
    
        $this->form_validation->set_rules('currency_symbol', 'Currency Symbol', 'required', 
            array(
                'required' => 'The Currency Symbol is required.'
            )
        );
    
        if ($this->form_validation->run() == FALSE) {
       
            $errors = $this->form_validation->error_array();
            echo json_encode(['status' => false, 'errors' => $errors]);
            exit;

        } else {

            $data = array(
                'coin_name' => $this->input->post('coin_name', true),
                'decimal' => $this->input->post('decimal', true),
                'currency_symbol' => $this->input->post('currency_symbol', true),
                'currency_value' => $this->input->post('currency_value', true),
            );

            $id = $this->input->post('currency_id');
    
            if ($id) { 
                $this->db->where('id', $id);
                $updated = $this->db->update('currency_config', $data);
    
                if ($updated) {
                    echo json_encode(['status' => true, 'message' => "Currency settings updated successfully!"]);
                    exit;
                } else {
                    echo json_encode(['status' => false, 'message' => "Error updating currency settings. Please try again."]);
                    exit;
                }
            } else { 
                $inserted = $this->db->insert('currency_config', $data);
    
                if ($inserted) {
                    echo json_encode(['status' => true, 'message' => "New currency added successfully!"]);
                    exit;
                } else {
                    echo json_encode(['status' => true, 'message' => "Error adding currency. Please try again."]);
                    exit;
                }
            }
    
        }
    }
    /*
    |--------------------------------------------------------------------------
    | Currency Delete
    |--------------------------------------------------------------------------
    */
    public function delete($id){

        if($id){

            $curency_info = $this->db->query("SELECT * FROM currency_config WHERE id = '".$id."' ")->num_rows();

            if($curency_info){

                $curency_active = $this->db->query("SELECT * FROM currency_config WHERE id = '".$id."' and currency_status = '1' ")->num_rows();

                if($curency_active){

                echo json_encode(['status' => false, 'message' => "selected currecny is active please choose another main currecny ! "]);
                exit;    

                } else {

                    $this->db->where('id',$id);
                    $this->db->delete('currency_config');
                
                    echo json_encode(['status' => "success", 'message' => "selected currecny delete successfully. "]);
                    exit;        

                }


            } else {
                echo json_encode(['status' => false, 'message' => "incorrect currency delete request !"]);
                exit;    
            }

        } else {
            echo json_encode(['status' => false, 'message' => "incorrect currency delete request !"]);
            exit;
        }

    }
    /*
    |--------------------------------------------------------------------------
    | Currency Status
    |--------------------------------------------------------------------------
    */
    public function status($id){

        if($id){

            $curency_info = $this->db->query("SELECT * FROM currency_config WHERE id = '".$id."' ")->num_rows();

            if($curency_info){

                $status = $this->input->post('currency_status');

                if($status == "0"){

                    $curency_active = $this->db->query("SELECT * FROM currency_config WHERE id = '".$id."' and currency_status = '1' ")->num_rows();
                    
                    if($curency_active == 1){

                        echo json_encode(['status' => false, 'message' => "Please choose another main currency !"]);
                        exit;    
        
                    } 

                } else{
                    $curency_active = $this->db->query("SELECT * FROM currency_config WHERE id != '".$id."' and currency_status = '1' ")->num_rows();

                    if($curency_active == 1){

                        $be_update = array(
                            "currency_status" => '0'
                        );
                        $this->db->update('currency_config',$be_update);

                        $ae_update = array(
                            "currency_status" => '1'
                        );
                        $this->db->where('id',$id);
                        $this->db->update('currency_config',$ae_update);

                        echo json_encode(['status' => "success", 'message' => "selected currecny status successfully. "]);
                        exit;   
        
                    } else {

                        echo json_encode(['status' => false, 'message' => "Please choose another main currency !"]);
                        exit;   

                    } 

                }
                

            } else {
                echo json_encode(['status' => false, 'message' => "incorrect currency status request !"]);
                exit;    
            }

        } else {
            echo json_encode(['status' => false, 'message' => "incorrect currency status request !"]);
            exit;
        }

    }
    /*
    |--------------------------------------------------------------------------
    | Currency List View
    |--------------------------------------------------------------------------
    */
    public function currency_list(){
        
        $this->load->model('settings/Currency_model');

        $draw = $this->input->get('draw');
        $start = $this->input->get('start');
        $length = $this->input->get('length');
        $search = $this->input->get('search')['value'];
        
        $data = array();
        $users = $this->Currency_model->get_info($length, $start, $search);
        $total_records = $this->Currency_model->get_count($search);

        $i = 0;
        foreach ($users as $user) {
        $i++;
        $call_status_class = 'badge-light-danger';
        $call_status = 'Not Interested';

        $currency_status = $user['currency_status'] ? "checked" : "";
        $decimal = $user['decimal'] ?  $user['decimal'] : 0;
        $coin_name = $user['coin_name'] ? $user['coin_name'] : "no mention";
        $currency_symbol = $user['currency_symbol'] ? $user['currency_symbol'] : "blank.svg";
        $change_status_url = base_url()."currency-status/".$user['id'];
        $data[] = array(
        'RecordID' => $i,
        'paymentImg' => '<div class="d-flex align-items-center">
        <div class="d-flex justify-content-start flex-column">
        <a href="#" class="text-gray-800 fw-bold text-hover-primary mb-1 fs-6">'.$coin_name.'</a>
        </div>
        </div>',
        'paymentSymbol' => '    <div class="symbol symbol-50px me-3 mb-2">                                                   
        '.$currency_symbol.'                                                 
        </div>',
        'paymentStatus' => '<div class="form-check form-switch form-check-custom form-check-success form-check-solid">
        <input class="form-check-input h-30px w-50px currency_status" type="checkbox" value="" name="currency_status"'.
        $currency_status.'
        id="currency_status" 
        data-payment="'.$user['id'].'" 
        data-currency_status-url="'.$change_status_url.'"/>
        <label class="form-check-label" for="currency_status">
        </label>
        </div>',
        'Decimal'=>$decimal,
        'paymentid'=>$user['id']
        );
        }

        $response = array(
        'draw' => intval($draw),
        'recordsTotal' => $total_records,
        'recordsFiltered' => $total_records,
        'data' => $data
        );

        echo json_encode($response);

    }
    /*
    |--------------------------------------------------------------------------
    | Currency List View
    |--------------------------------------------------------------------------
    */
    public function ipblock_list(){
        
        $this->load->model('settings/Ip_model');

        $draw = $this->input->get('draw');
        $start = $this->input->get('start');
        $length = $this->input->get('length');
        $search = $this->input->get('search')['value'];
        
        $data = array();
        $users = $this->Ip_model->get_info($length, $start, $search);
        $total_records = $this->Ip_model->get_count($search);

        $i = 0;
        foreach ($users as $user) {
        $i++;
        $call_status_class = 'badge-light-danger';
        $call_status = 'Not Interested';

        $reason = $user['reason'] ? $user['reason'] : "";
        $ip_address = $user['ip_address'] ?  $user['ip_address'] : 0;
        $change_status_url = base_url()."delete-ip/".$user['ip_address'];

        $data[] = array(
        'RecordID' => $i,
        'paymentSymbol' => '    <div class="symbol symbol-50px me-3 mb-2">                                                   
        '.$ip_address.'                                                 
        </div>',
        'Decimal'=>$reason,
        'paymentid'=>$user['id']
        );
        }

        $response = array(
        'draw' => intval($draw),
        'recordsTotal' => $total_records,
        'recordsFiltered' => $total_records,
        'data' => $data
        );

        echo json_encode($response);

    }
    /*
    |--------------------------------------------------------------------------
    | Token  View
    |--------------------------------------------------------------------------
    */
    public function token() {
        $this->data['title'] = 'Token Settings';
        $this->data['card_title'] = 'Token Settings List';
        $this->data['active_nav'] = 'token-settings';
        $this->load->view('admin/settings/token-settings', $this->data);
    }
   /*
    |--------------------------------------------------------------------------
    | Token List View
    |--------------------------------------------------------------------------
    */
    public function token_list() {
     
        $this->load->model('settings/Token_model');

        $draw = $this->input->get('draw');
        $start = $this->input->get('start');
        $length = $this->input->get('length');
        $search = $this->input->get('search')['value'];
        
        $data = array();
        $users = $this->Token_model->get_info($length, $start, $search);
        $total_records = $this->Token_model->get_count($search);

        $i = 0;
        foreach ($users as $user) {
        $i++;
        $call_status_class = 'badge-light-danger';
        $call_status = 'Not Interested';

        $currency_status = $user['currency_status'] ? "checked" : "";
        $decimal = $user['decimal'] ?  $user['decimal'] : 0;
        $coin_name = $user['coin_name'] ? $user['coin_name'] : "no mention";
        $currency_symbol = $user['currency_symbol'] ? $user['currency_symbol'] : "blank.svg";
        $change_status_url = base_url()."token-status/".$user['id'];
        $data[] = array(
        'RecordID' => $i,
        'paymentImg' => '<div class="d-flex align-items-center">
        <div class="d-flex justify-content-start flex-column">
        <a href="#" class="text-gray-800 fw-bold text-hover-primary mb-1 fs-6">'.$coin_name.'</a>
        </div>
        </div>',
        'paymentSymbol' => '    <div class="symbol symbol-50px me-3 mb-2">                                                   
        '.$currency_symbol.'                                                 
        </div>',
        'paymentStatus' => '<div class="form-check form-switch form-check-custom form-check-success form-check-solid">
        <input class="form-check-input h-30px w-50px currency_status" type="checkbox" value="" name="currency_status"'.
        $currency_status.'
        id="currency_status" 
        data-payment="'.$user['id'].'" 
        data-currency_status-url="'.$change_status_url.'"/>
        <label class="form-check-label" for="currency_status">
        </label>
        </div>',
        'Decimal'=>$decimal,
        'paymentid'=>$user['id']
        );
        }

        $response = array(
        'draw' => intval($draw),
        'recordsTotal' => $total_records,
        'recordsFiltered' => $total_records,
        'data' => $data
        );

        echo json_encode($response);

    }
    /*
    |--------------------------------------------------------------------------
    | Token Edi View
    |--------------------------------------------------------------------------
    */
    public function token_edit($id){
        
        $currency_info = $this->db->query("SELECT * FROM token_config where id= '".$id."' ")->row();

        if($currency_info){

            $this->data['title'] = 'Token Settings';
            $this->data['card_title'] = 'Edit Token Settings';
            $this->data['currency_id'] = $id;
            $this->data['coin_name'] = $currency_info->coin_name;
            $this->data['decimal'] = $currency_info->decimal;
            $this->data['currency_symbol'] = $currency_info->currency_symbol;
            $this->data['currency_value'] = $currency_info->currency_value;
            $this->data['value_text'] = "<b>".$currency_info->currency_value." ".$currency_info->coin_name." = 1 USDT  </b> ";
            $this->data['action'] =  base_url()."token-update"; 
            $this->data['redirect'] =  base_url()."token-settings";
            $this->load->view('admin/settings/edit-currency-settings', $this->data);

        } else {
            
            $this->session->set_flashdata('danger', 'Invalide Token Please Try Again');
            redirect('advance-settings');
        }

    }
     /*
    |--------------------------------------------------------------------------
    | Toekn UPDATE
    |--------------------------------------------------------------------------
    */
    public function token_update() {

        $this->form_validation->set_rules('coin_name', 'Coin Name', 'required|regex_match[/^[A-Za-z\s]+$/]', 
            array(
                'required' => 'The Coin Name is required.',
                'regex_match' => 'The Coin Name must only contain letters.'
            )
        );
    
        $this->form_validation->set_rules('decimal', 'Decimal', 'required|regex_match[/^\d+(\.\d+)?$/]', 
            array(
                'required' => 'The Decimal Value is required.',
                'regex_match' => 'Only numbers and decimal values are allowed.'
            )
        );
    
        $this->form_validation->set_rules('currency_symbol', 'Currency Symbol', 'required', 
            array(
                'required' => 'The Currency Symbol is required.'
            )
        );
    
        if ($this->form_validation->run() == FALSE) {
       
            $errors = $this->form_validation->error_array();
            echo json_encode(['status' => false, 'errors' => $errors]);
            exit;

        } else {

            $data = array(
                'coin_name' => $this->input->post('coin_name', true),
                'decimal' => $this->input->post('decimal', true),
                'currency_symbol' => $this->input->post('currency_symbol', true),
                'currency_value' => $this->input->post('currency_value', true),
            );

            $id = $this->input->post('currency_id');
    
            if ($id) { 
                $this->db->where('id', $id);
                $updated = $this->db->update('token_config', $data);
    
                if ($updated) {
                    echo json_encode(['status' => true, 'message' => "Token settings updated successfully!"]);
                    exit;
                } else {
                    echo json_encode(['status' => false, 'message' => "Error updating token settings. Please try again."]);
                    exit;
                }
            } else { 
                $inserted = $this->db->insert('token_config', $data);
    
                if ($inserted) {
                    echo json_encode(['status' => true, 'message' => "New token added successfully!"]);
                    exit;
                } else {
                    echo json_encode(['status' => true, 'message' => "Error adding token. Please try again."]);
                    exit;
                }
            }
    
        }
    }
    /*
    |--------------------------------------------------------------------------
    | Currency Edi View
    |--------------------------------------------------------------------------
    */
    public function token_add(){
    $this->data['title'] = 'Token Settings';
    $this->data['card_title'] = 'Add Token Settings';
    $this->data['currency_id'] = 0;
    $this->data['coin_name'] = "";
    $this->data['decimal'] = "";
    $this->data['currency_symbol'] = "";
    $this->data['action'] =  base_url()."token-update"; 
    $this->data['redirect'] =  base_url()."token-settings";
    $this->load->view('admin/settings/edit-currency-settings', $this->data);
    }
    /*
    |--------------------------------------------------------------------------
    | Token Delete
    |--------------------------------------------------------------------------
    */
    public function token_delete($id){

        if($id){

            $curency_info = $this->db->query("SELECT * FROM token_config WHERE id = '".$id."' ")->num_rows();

            if($curency_info){

                $curency_active = $this->db->query("SELECT * FROM token_config WHERE id = '".$id."' and currency_status = '1' ")->num_rows();

                if($curency_active){

                echo json_encode(['status' => false, 'message' => "selected currecny is active please choose another main currecny ! "]);
                exit;    

                } else {

                    $this->db->where('id',$id);
                    $this->db->delete('token_config');
                
                    echo json_encode(['status' => "success", 'message' => "selected currecny delete successfully. "]);
                    exit;        

                }


            } else {
                echo json_encode(['status' => false, 'message' => "incorrect currency delete request !"]);
                exit;    
            }

        } else {
            echo json_encode(['status' => false, 'message' => "incorrect currency delete request !"]);
            exit;
        }

    }
      /*
    |--------------------------------------------------------------------------
    | Currency Status
    |--------------------------------------------------------------------------
    */
    public function token_status($id){

        if($id){

            $curency_info = $this->db->query("SELECT * FROM token_config WHERE id = '".$id."' ")->num_rows();

            if($curency_info){

                $status = $this->input->post('currency_status');

                if($status == "0"){

                    $curency_active = $this->db->query("SELECT * FROM token_config WHERE id = '".$id."' and currency_status = '1' ")->num_rows();
                    
                    if($curency_active == 1){

                        echo json_encode(['status' => false, 'message' => "Please choose another main currency !"]);
                        exit;    
        
                    } 

                } else{
                    $curency_active = $this->db->query("SELECT * FROM token_config WHERE id != '".$id."' and currency_status = '1' ")->num_rows();

                    if($curency_active == 1){

                        $be_update = array(
                            "currency_status" => '0'
                        );
                        $this->db->update('currency_config',$be_update);

                        $ae_update = array(
                            "currency_status" => '1'
                        );
                        $this->db->where('id',$id);
                        $this->db->update('currency_config',$ae_update);

                        echo json_encode(['status' => "success", 'message' => "selected token status successfully. "]);
                        exit;   
        
                    } else {

                        echo json_encode(['status' => false, 'message' => "Please choose another main token !"]);
                        exit;   

                    } 

                }
                

            } else {
                echo json_encode(['status' => false, 'message' => "incorrect token status request !"]);
                exit;    
            }

        } else {
            echo json_encode(['status' => false, 'message' => "incorrect token status request !"]);
            exit;
        }

    }
}