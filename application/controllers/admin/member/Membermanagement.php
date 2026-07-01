<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class  Membermanagement extends CI_Controller {

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
        if (empty($permissions['member_management'])) {
            $this->session->set_flashdata('error', 'Access Denied: You do not have permission.');
            redirect('admin');
        }
    }

    $this->load->model('member/Users_model');
    $this->load->model('member/Mlm_model');
    $this->load->model('member/BinaryModel');

    }
    /*
    |--------------------------------------------------------------------------
    | Index Page
    |--------------------------------------------------------------------------
    */
    public function index(){
    $this->data['title'] = "All Members List ";
    $this->data['card_tilte'] = "Members List";
    $this->load->view('admin/member/list',$this->data);
    }
    /*
    |--------------------------------------------------------------------------
    | Image Generate
    |--------------------------------------------------------------------------
    */
    public function image_generate(){

        $json_data = file_get_contents('php://input');
        $request_data = json_decode($json_data, true);
        $image_code = $this->request_data['image_code'];
        $create_image  = $this->Mlm_model->online_image_generate($image_code);
        echo json_encode($create_image);
           
    }
    /*
    |--------------------------------------------------------------------------
    | list Page
    |--------------------------------------------------------------------------
    */
    public function list(){
      
    $draw = $this->input->get('draw');
    $start = $this->input->get('start');
    $length = $this->input->get('length');

    $clients = $this->input->get('client_filter');
    $from_date = $this->input->get('from_date') ? date('Y-m-d', strtotime($this->input->get('from_date'))) : '';
    $to_date = $this->input->get('to_date') ? date('Y-m-d', strtotime($this->input->get('to_date'))) : '';
    
    $data = array();
    $total_records = $this->Users_model->get_count($clients,$from_date,$to_date);
    $users = $this->Users_model->get_info($length, $start,$clients,$from_date,$to_date);

    $i = 0;
    foreach ($users as $user) {
    $i++;

    if($user['sponser'] > 0){
        $sponser_info = $this->db->query("SELECT * FROM users where id = '".$user['sponser']."' ")->row();
        $sponser_referral =  $sponser_info->referral_id ? $sponser_info->referral_id : " Unkown ";
        $sponser_email =  $sponser_info->email ? $sponser_info->email : " Unkown ";
    } else {
        $sponser_referral =  " Main - Admin ";
        $sponser_email =   " Main - Admin ";
    }

    $status = "";

    if($user['status'] == '0'){
        $status = "In-Active";
    } if($user['status']== '1') { 
        $status = "Active";
    }if($user['status'] == '2') { 
        $status = "In-Active";
    }

    $binary_info = $this->BinaryModel->calculateLegInvestments($user['id']);

    $left_leg_count = count($binary_info['left_leg_users']);  
    $right_leg_count = count($binary_info['right_leg_users']); 

    $left_leg_investment = $binary_info['left_leg_investment']; 
    $right_leg_investment = $binary_info['right_leg_investment'];
    $my_investment = $binary_info['my_investment'];

    
    $left_leg_investment_token = $binary_info['left_investment_token']; 
    $right_leg_investment_token = $binary_info['right_investment_token'];
    $my_investment_token = $binary_info['my_investment_token'];

    $tree_link = base_url().'user-genealogy/'.$user['id'];

    $currency_status = $user['status'] == '1'  ? "checked" : "";
    $change_status_url = base_url()."user-status-update/".$user['id'];
    $delete_url = base_url()."user-delete/".$user['id'];

    $data[] = array(
    'RecordID' => $i,
    'SponserInfo' => '<div class="d-flex align-items-center">
    <div class="symbol symbol-50px me-3">                                                   
    </div>
    <div class="d-flex justify-content-start flex-column">
    <a href="#" class="text-gray-800 fw-bold text-hover-primary mb-1 fs-6">'.$sponser_referral.'</a>
    <span class="text-gray-500 fw-semibold d-block fs-7">'.$sponser_email.'</span>
    </div>
    </div>',
    'UserInfo' => '<div class="d-flex align-items-center">
    <div class="symbol symbol-50px me-3">                                                   
    </div>
    <div class="d-flex justify-content-start flex-column">
    <a href="#" class="text-gray-800 fw-bold text-hover-primary mb-1 fs-6">'.$user['referral_id'].'</a>
    <span class="text-gray-500 fw-semibold d-block fs-7 mb-1">'.$user['email'].'</span>
    <span class="text-gray-500 fw-semibold d-block fs-7">'.$user['register_date'].'</span>
    </div>
    </div>',
    'BinaryInfo' => '<div class="d-flex align-items-center">
    <div class="symbol symbol-50px me-3">                                                   
    </div>
    <div class="d-flex justify-content-start flex-column me-4">
    <a href="#" class="text-gray-800 fw-bold text-hover-primary mb-1 fs-6">Total Left Leg '.$left_leg_count.'</a>
    <span class="text-gray-500 fw-semibold d-block fs-7">Total Right Leg '.$right_leg_count.'</span>
    </div>
    <div class="d-flex justify-content-start flex-column me-4">
    <a href="#" class="text-gray-800 fw-bold text-hover-primary mb-1 fs-6">Total Left Invest '.currency_format($left_leg_investment).'</a>
    <span class="text-gray-500 fw-semibold d-block fs-7">Total Right Invest '.currency_format($right_leg_investment).'</span>
    </div>
    </div>',
    'DateInfo' => '<div class="d-flex align-items-center">
    <div class="symbol symbol-50px me-3">       
    <a href="#" class="text-gray-800 fw-bold text-hover-primary mb-1 fs-6"> '.currency_format($my_investment).'</a>                                            
    </div>
    <div class="d-flex justify-content-start flex-column">
    </div>
    </div>',
    'Status' => '<div class="form-check form-switch form-check-custom form-check-success form-check-solid">
    <input class="form-check-input h-30px w-50px template_status" type="checkbox" value="1" name="template_status"'.
    $currency_status.'
    id="template_status" 
    data-payment="'.$user['id'].'" 
    data-template_status-url="'.$change_status_url.'"/>
    <label class="form-check-label" for="template_status">
    </label>
    </div>
    ',
    'Tree' => '<div class="d-flex justify-content-start flex-column">
    <a href="'.$tree_link.'" class="text-gray-800 fw-bold text-hover-primary mb-1 fs-6"> View Tree </a>
    </div>',
    'Action' => '<div class="d-flex justify-content-center flex-row">
    <a class="btn btn-success btn-active-light-success btn-sm dropdown-toggle_sedit-summary  text-center me-3" href="'.base_url().'view-user/'.$user['id'].'">
    <i class="fa fa-eye"></i> View
    </a>
    <a class="btn btn-danger btn-active-light-danger btn-sm delete_user text-center"   data-payment="'.$user['id'].'" 
        data-delete_user-url="'.$delete_url.'" ">
    <i class="fa fa-trash"></i> Delete
    </a>
    </div>',
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
    | Add User
    |--------------------------------------------------------------------------
    */
    public function add_user(){

    $this->data['title'] = 'Add User';
    $this->data['card_title'] = 'Add Users';
    $this->data['action'] = base_url().'create-user';
    $this->data['redirect'] = base_url().'network-list';
    $this->data['users'] = $this->db->query("SELECT * FROM users where status = '1' ")->result();
    $this->load->view('admin/member/create-member', $this->data);
    
    }
    /*
    |--------------------------------------------------------------------------
    | Add User Post Method
    |--------------------------------------------------------------------------
    */
    public function create_user() {

        $sponsor_id = $this->input->post('sponsor_id'); 
        $username = $this->input->post('username');
        $email = $this->input->post('useremail');
        $sponser_leg = $this->input->post('select_lg');
        $password = $this->password_create();

        if ($this->Mlm_model->usernameExists($username)) {
            echo json_encode(["status" => "error", "message" => "Username already taken"]);
            exit();
        }

        $user_id = $this->Mlm_model->registerUser($username, $email, $sponsor_id,$sponser_leg,$password);

        if ($user_id) {
            echo json_encode(["status" => "success", "message" => "User registered successfully"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Registration failed"]);
        }

        exit();
    
    }
    /*
    |--------------------------------------------------------------------------
    |  User Passwrod create
    |--------------------------------------------------------------------------
    */
    public function password_create(){

        $uppercase = chr(rand(65, 90));              // A-Z
        $lowercase = chr(rand(97, 122));             // a-z
        $number    = chr(rand(48, 57));              // 0-9
        $special   = chr(rand(33, 47));              // Special chars like ! " # $ etc.

        $remaining = '';
        $all = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()';

        for ($i = 0; $i < 4; $i++) {
        $remaining .= $all[rand(0, strlen($all) - 1)];
        }

        $passwordArray = str_split($uppercase . $lowercase . $number . $special . $remaining);
        shuffle($passwordArray);
        $password = implode('', $passwordArray);


        return $password;
    }
    /*
    |--------------------------------------------------------------------------
    | Add User Genealoy
    |--------------------------------------------------------------------------
    */
    public function genealogy($user_id){
    $this->data['title'] = "Members Genealogy ";
    $this->data['card_title'] = "Genealogy List";
    $this->data['user_id'] =$user_id;
    $this->load->view('admin/member/genealogy_view', $this->data);
    }
    /*
    |--------------------------------------------------------------------------
    | View Genealoy
    |--------------------------------------------------------------------------
    */
    public function getTreeData($user_id) {
        $members = $this->BinaryModel->getDownlineMembers($user_id);
        echo json_encode($members);
    }
    /*
    |--------------------------------------------------------------------------
    | View User Details
    |--------------------------------------------------------------------------
    */
    public function viewuser($id){
    $this->data['title'] = "View User Profile";
    $this->data['card_tilte'] = "User Profile";
    $this->data['user_id'] = $id;
    $user_name = $this->db->query("SELECT * FROM users where id = '".$id."' ")->row()->username;
    $this->data['first_letter'] = substr($user_name, 0, 1);

    $this->data['currency_info'] = currency_info();
    $this->data['token_info'] = token_info();

    $site_wallet_balance = site_wallet_balance($id);
    $token_wallet_balance = site_token_balance($id);

    $lending_profit = get_transaction_currecy('profit',$id);
    $direct_commission = get_transaction_currecy('direct_commission',$id);
    $level_commissions = get_transaction_currecy('level_commission',$id);

    $level_commissions_1 = get_transaction_level_currency('level_commission',$id,'1');
    $level_commissions_2 = get_transaction_level_currency('level_commission',$id,'2');
    $level_commissions_3 = get_transaction_level_currency('level_commission',$id,'3');
    $level_commissions_4 = get_transaction_level_currency('level_commission',$id,'4');
    $level_commissions_5 = get_transaction_level_currency('level_commission',$id,'5');

    
    $this->data['level_commissions']  = $level_commissions;
    $this->data['level_commissions_1']  = $level_commissions_1;
    $this->data['level_commissions_2']  = $level_commissions_2;
    $this->data['level_commissions_3']  = $level_commissions_3;
    $this->data['level_commissions_4']  = $level_commissions_4;
    $this->data['level_commissions_5']  = $level_commissions_5;

    $this->data['wallet_balance']  = $site_wallet_balance;
    $this->data['token_wallet_balance']  = $token_wallet_balance;

    $this->data['lending_profit']  = $lending_profit;
    $this->data['direct_commission']  = $direct_commission;

    $this->load->view('admin/member/profile',$this->data);
    }

    public function viewuserinfo($id){

        /******* BASIC INFO ***********/
        $userinfo = $this->db->query("SELECT * FROM users where id = '".$id."'")->row();
        $sponser_info = $this->db->query("SELECT * FROM users where id = '".$userinfo->sponser."'")->row();

        /******* Investment INFO ***********/
        $binary_info = $this->BinaryModel->calculateLegInvestments($id);

        $left_leg_count = count($binary_info['left_leg_users']);  
        $right_leg_count = count($binary_info['right_leg_users']); 

        $left_leg_investment = $binary_info['left_leg_investment']; 
        $right_leg_investment = $binary_info['right_leg_investment'];
        $my_investment = $binary_info['my_investment'];
        $left_leg_investment_token = $binary_info['left_investment_token']; 
        $right_leg_investment_token = $binary_info['right_investment_token'];
        $my_investment_token = $binary_info['my_investment_token'];

         /******* Earnings INFO ***********/
         $binary_site_currency = $this->db->query("SELECT sum(amount) as binary_site_amt FROM history where type = 'binary_commission' ")->row()->binary_site_amt;
         $binary_token_currency = $this->db->query("SELECT sum(token_amount) as binary_token_amt FROM history where type = 'binary_commission' ")->row()->binary_token_amt;
         $roi_site_currency = $this->db->query("SELECT sum(amount) as roi_site_amt FROM history where type = 'profit' ")->row()->roi_site_amt;
         $roi_token_currency = $this->db->query("SELECT sum(token_amount) as roi_token_amt FROM history where type = 'profit' ")->row()->roi_token_amt;
         $direct_site_currency = $this->db->query("SELECT sum(amount) as direc_site_amt FROM history where type = 'direct_commission' ")->row()->direc_site_amt;
         $direct_token_currency = $this->db->query("SELECT sum(token_amount) as direc_token_amt FROM history where type = 'direct_commission' ")->row()->direc_token_amt;


        $userinfo = array(
            "name" => $userinfo->username,
            "email" => $userinfo->email,
            "register_date" => $userinfo->register_date,
            "referral_id" => $userinfo->referral_id,
            "sponser" => $sponser_info->email." ( ".$sponser_info->referral_id." )",
            "my_investment" => currency_format($my_investment),
            "left_leg_count" => $left_leg_count,
            "right_leg_count" => $right_leg_count,
            "left_leg_investment" => currency_format($left_leg_investment),
            "right_leg_investment" => currency_format($right_leg_investment),
            'left_leg_investment_token' => token_format($left_leg_investment_token),
            'right_leg_investment_token' => token_format($right_leg_investment_token),
            'my_investment_token' => token_format($my_investment_token),
            'binary_site_currency' =>$binary_site_currency,
            'binary_token_currency' =>$binary_token_currency,
            'roi_token_currency' =>$roi_token_currency,
            'direct_site_currency' =>$direct_site_currency,
            'direct_token_currency' =>$direct_token_currency,
        );

        $return = array(
            'result' => true,
            'data' => $userinfo
        );

        echo json_encode($return);

    }
         /*
        |--------------------------------------------------------------------------
        | STATUS Update
        |--------------------------------------------------------------------------
        */
        public function statusupdate($id){

            if($id){

                $check_template = $this->db->query("SELECT * FROM `users` where id = '".$id."'")->num_rows();

                if($check_template > 0){

                    $status = $this->input->post('template_status');
                    $template_status = $status == '1' ? '1':'2';

                    $array_template = array(
                        "status" => $template_status,
                    );

                    $this->db->where('id',$id);
                    $this->db->update('users',$array_template);

                    $response = array(
                        'status' => "success",
                        'message' => "Status update successfully.."
                    );
                    echo json_encode($response);
                    exit(); 
                } else {
                    $response = array(
                        'status' => false,
                        'message' => "Invalide User!"
                    );
                    echo json_encode($response);
                    exit(); 
                }

            }

        }
        /*
        |--------------------------------------------------------------------------
        | DELETE USER
        |--------------------------------------------------------------------------
        */
        public function deleteuser($id) {
            if ($id) {
                $check_user = $this->db->query("SELECT * FROM `users` WHERE id = '".$id."'")->num_rows();
                
                if ($check_user > 0) {
                    $check_investment = $this->db->query("SELECT * FROM `user_investment` WHERE user_id = '".$id."' AND status = 1")->num_rows();
                    
                    $check_downline = $this->db->query("SELECT * FROM `binary_placement` WHERE sponsor_id = '".$id."' OR parent_id = '".$id."'")->num_rows();
        
                    if ($check_investment > 0) {
                        $response = array(
                            'status' => false,
                            'message' => "User has an active investment. Cannot delete!"
                        );
                    } elseif ($check_downline > 0) {
                        $response = array(
                            'status' => false,
                            'message' => "User has a downline. Cannot delete!"
                        );
                    } else {
                        $this->db->query("DELETE FROM `history` WHERE user_id = '".$id."'");
                        $this->db->query("DELETE FROM `history` WHERE from_id = '".$id."'");
                        $this->db->query("DELETE FROM `user_investment` WHERE user_id = '".$id."'");
                        $this->db->query("DELETE FROM `users` WHERE id = '".$id."'");
                        $response = array(
                            'status' => 'success',
                            'message' => "User and related records deleted successfully."
                        );
                    }
                } else {
                    $response = array(
                        'status' => false,
                        'message' => "Invalid User!"
                    );
                }
        
                echo json_encode($response);
                exit();
            }
        }
        
        
}