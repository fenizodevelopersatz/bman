<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Paymentsettings extends CI_Controller {

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
            if (empty($permissions['payment_settings'])) {
                $this->session->set_flashdata('error', 'Access Denied: You do not have permission.');
                redirect('admin');
            }
        }

        $this->load->model('settings/Payment_model');
    }

    
    /*
    |--------------------------------------------------------------------------
    | Payment List View
    |--------------------------------------------------------------------------
    */
    public function index() {
        $this->data['title'] = 'Payment Settings';
        $this->load->view('admin/settings/payment-list-settings', $this->data);
    }


    /*
    |--------------------------------------------------------------------------
    | Payment Edit Page
    |--------------------------------------------------------------------------
    */
    // public function edit($id) {

    //     if($this->input->post()){ 
	
    //         $this->form_validation->set_rules('wallet_adderss', 'wallet_adderss', 'required');
    //         $this->form_validation->set_rules('privat_key', 'privat_key', 'required');
        
    //         if($this->form_validation->run() == true){
                
    //             $this->load->model('member/Mlm_model');

    //             $wallet_adderss = $this->input->post('wallet_adderss');
    //             $private_apiKey = $this->input->post('privat_key');
    //             $private_apiSecret = $this->input->post('secret_key');

    //             $deposit_address = $this->Mlm_model->encrypt_account($wallet_adderss);
    //             $mec_apiKey = $this->Mlm_model->encrypt_account($private_apiKey);
    //             $mec_apiSecret = $this->Mlm_model->encrypt_account($private_apiSecret);

    //             $address_last = substr($wallet_adderss, -4); 
    //             $key_last = substr($private_apiKey, -4); 
    //             $private_last = substr($private_apiSecret, -4); 

    //             $payment_dat_without = array(
    //             'wallet_adderss' => $deposit_address,
    //             'privat_key' => $mec_apiKey,
    //             'secret_key' => $mec_apiSecret,
    //             'address_last' => $address_last,
    //             'key_last' => $key_last,
    //             'private_last' => $private_last,
    //             );

    //             $payment_id = $this->input->post('paymentid');
    //             $update_query =  $this->db->where('id',$payment_id)->update('payment_controls',$payment_dat_without);
        
    //             $this->session->set_flashdata('success', 'Payment Settings Update Successfully');
    //             redirect('payment-settings');
        
    //         }else{
        
    //             $this->session->set_flashdata('danger', 'All Fields is Required!');
    //             redirect('payment-settings');
        
    //         }
        
    //         } else {

    //         $verify_session = $this->session->userdata('verify_payment_page');
    //         $verify_session = "ok";
    //         if($verify_session =="ok"){

    //         $this->data['title'] = 'Payment Settings Edit';
    //         $this->data['verify_type'] = '1';
    //         $this->data['payment_id'] = $id;
    //         $this->data['payment'] = $this->db->query("SELECT * FROM payment_controls where id = '".$id."'")->row();
    //         $this->data['card_title'] = $this->data['payment']->wallet_name." Payment Details";

    //         $this->load->view('admin/settings/payment-edit-settings', $this->data);

    //         } else { 

    //         $send_otp = $this->session->userdata('sender_otp');
                
    //         if($send_otp == ""){
    //         $this->sender_otp();
    //         }

    //         $admin_id = $this->session->userdata('admin_userid');
    //         $this->data['verify_type'] = '0';
    //         $this->data['title'] = 'Payment Settings Verify Page';
    //         $this->data['admin_mail'] = $this->db->query("SELECT * FROM `admin_members` WHERE id = '".$admin_id."' ")->row()->admin_email;
    //         $this->data['payment_id'] = $id;
    //         $this->load->view('admin/settings/payment-edit-settings',$this->data);

    //         }

            
    //         }

           
    // }
        

    private function encryptWithoutLastFourDigits($data, $key, $iv) {
        $CI =& get_instance();

        $lastFourDigits = substr($data, -4); 
        return [
        'dataWithoutLastFourDigits' => $this->encryptData($data, $key, $iv),
        'lastFourDigits' => $lastFourDigits,
        ];
    }



    private function encryptData($data, $key, $iv) {
        $cipherText = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
        return base64_encode($cipherText);
    }

        
    public function sender_otp(){

        $random_number = sprintf("%06d", random_string('numeric', 6));

        if($random_number){
            $userid = $this->session->userdata('admin_userid');
         
            $this->load->model('member/Mlm_model');
            $useremail = $this->db->query("SELECT * FROM `admin_members` where id = '".$userid."' ")->row()->admin_email;
            $mailid = "7";
            $mail_subject_data = $this->db->query("SELECT * FROM email_template where id = '".$mailid."' ")->row();
            $createddate = date('Y-m-d H:i:s');
            $subject = $mail_subject_data->subject;
            $message  = str_replace('[temp_otp]', $random_number, $mail_subject_data->temp_content);

            $this->Mlm_model->sendmail($useremail, $subject, $message);

            email_log($random_number,$useremail,'email_verify');
            $this->session->set_userdata('sender_otp',$random_number);
            return true;
        
        }

    }


    /*
    |--------------------------------------------------------------------------
    | VERIFY  OTP
    |--------------------------------------------------------------------------
    */
    public function finelVerify() {
        // Check if request is POST
        if ($this->input->server('REQUEST_METHOD') === 'POST') {
    
            // Get raw POST data
            $postData = $this->input->post();
            
            if (!empty($postData)) {
             
                $admin_id = $this->session->userdata('admin_userid');
                $emailOTP = $this->input->post('emailOTP');
                $twofaOTP = $this->input->post('twofaOTP');

                $verify_1 = $this->twofachecker($admin_id,$twofaOTP);
                $verify_2 = emailVerify($admin_id,'email_verify',$emailOTP);

                if($verify_1 && $verify_2){

                    $this->session->set_userdata('verify_payment_page',"ok");
                    $this->session->set_userdata('sender_otp',"");
                    $response = array(
                        'status' => true,
                        'message' => "Verify Successfully"
                    );
                } else {

                    $this->session->set_flashdata('danger', 'Invalide OTP !');
                    $response = array(
                        'status' => false,
                        'message' => "Invalid OTP!"
                    );
                }
                

            } else {
                $response = array(
                    'status' => false,
                    'message' => "Invalid OTP!"
                );
            }

            echo json_encode($response);
        } else {
            // Handle invalid request
            $response = array(
                'status' => false,
                'message' => "Invalid request method!"
            );
            echo json_encode($response);
        }
    }
    


    /*
    |--------------------------------------------------------------------------
    | VERIFY  OTP
    |--------------------------------------------------------------------------
    */
    public function verifyotp(){
        
        if($this->input->post()){

            $raw = file_get_contents("php://input");
            $data = json_decode($raw);

            if($data){

                $otp = $data->otp;
                $method = $data->method;
                $admin_id = $this->session->userdata('admin_userid');

                if($otp){

                    if($method == "email_otp"){

                        $verify = emailVerify($admin_id,'email_verify',$otp);
                        
                    if($verify){

                        $return = array(
                            'status' => true,
                            'message' => "Verify Sucessfully"
                        );
                        
                    } else {

                        $return = array(
                            'status' => false,
                            'message' => "invalide Email OTP"
                        );

                    }

                    } else {

                        $verify = $this->twofachecker($admin_id,$otp);

                        
                    if($verify){

                        $return = array(
                            'status' => true,
                            'message' => "Verify Successfully"
                        );
                        
                    } else {

                        $return = array(
                            'status' => false,
                            'message' => "invalide Two-Factor OTP"
                        );

                    }

                    }


                } else {

                    $return = array(
                        'status' => false,
                        'message' => "invalide OTP"
                    );

                }

            } else {
                
                $return = array(
                    'status' => false,
                    'message' => "invalide OTP"
                );
            }


        } else {
        
            $return = array(
                'status' => false,
                'message' => "invalide request"
            );

        }

        echo json_encode($return);

    }



    private function twofachecker($admin_id,$oneCode){

            $this->load->library('Google_authendicator');
            $admin_auth = $this->db->query("SELECT * FROM `admin_members` where  id= '".$admin_id."' ")->row()->auth_key;
            $ga = new Google_authendicator();	
            $checkResult = $ga->verifyCode($admin_auth, $oneCode, 2);
            if($checkResult) {
            return true;
            } else {
            return false;
            }
    }

    /*
    |--------------------------------------------------------------------------
    | Payment List
    |--------------------------------------------------------------------------
    */
    // public function list(){

    //     $draw = $this->input->get('draw');
    //     $start = $this->input->get('start');
    //     $length = $this->input->get('length');
    //     $search = $this->input->get('search')['value'];
        
    //     $data = array();
    //     $users = $this->Payment_model->get_info($length, $start, $search);
    //     $total_records = $this->Payment_model->get_count($search);

    //     $i = 0;
    //     foreach ($users as $user) {
    //     $i++;
    //     $call_status_class = 'badge-light-danger';
    //     $call_status = 'Not Interested';
        

    //     $payment_status = $user['payment_status'] ? "checked" : "";
    //     $payment_mode = $user['payment_mode'] ? "checked" : "";
    //     $payment_name = $user['wallet_name'] ? $user['wallet_name'] : "no mention";
    //     $payment_img = $user['payment_image'] ? $user['payment_image'] : "blank.svg";

    //     $data[] = array(
    //     'RecordID' => $i,
    //     'paymentImg' => '<div class="d-flex align-items-center">
    //     <div class="symbol symbol-50px me-3 mb-2">                                                   
    //     <img src='.base_url()."assets/images/".$payment_img.' class="" alt="">                                                    
    //     </div>
    //     <div class="d-flex justify-content-start flex-column">
    //     <a href="#" class="text-gray-800 fw-bold text-hover-primary mb-1 fs-6">'.$payment_name.'</a>
    //     </div>
    //     </div>',
    //     'paymentMode' => '<div class="form-check form-switch form-check-custom form-check-success form-check-solid">
    //     <input class="form-check-input  h-30px w-50px" type="checkbox" value="" name="payment_mode"'.
    //     $payment_mode.'
    //     id="payment_mode"/>
    //     <label class="form-check-label" for="payment_mode">
    //     </label>
    //     </div>',
    //     'paymentStatus' => '<div class="form-check form-switch form-check-custom form-check-success form-check-solid">
    //     <input class="form-check-input h-30px w-50px" type="checkbox" value="" name="payment_status"'.
    //     $payment_status.'
    //     id="payment_status" data-payment="'.$user['id'].'"/>
    //     <label class="form-check-label" for="payment_status">
    //     </label>
    //     </div>',
    //     'paymentid'=>$user['id']
    //     );
    //     }

    //     $response = array(
    //     'draw' => intval($draw),
    //     'recordsTotal' => $total_records,
    //     'recordsFiltered' => $total_records,
    //     'data' => $data
    //     );

    //     echo json_encode($response);

    
    // }


    /* DataTables list (uses new table) */
    public function list(){
        $draw   = $this->input->get('draw');
        $start  = $this->input->get('start');
        $length = $this->input->get('length');
        $search = $this->input->get('search')['value'];

        $rows   = $this->Payment_model->get_info($length, $start, $search);
        $total  = $this->Payment_model->get_count($search);

        $logo = function($g){
            switch (strtolower($g)) {
                case 'stripe': return 'stripe.svg';
                case 'paypal': return 'paypal.svg';
                case 'cash_on': return 'cash.svg';
                default: return 'blank.svg';
            }
        };

        $data = [];
        $i = $start;
        foreach ($rows as $r) {
            $i++;
            $img = base_url('assets/images/'.$logo($r['gateway']));
            $badge = '<span class="badge badge-light">'.html_escape($r['mode']).'</span>';

            $status = ((int)$r['status']===1) ? 'checked' : '';
            $data[] = [
                'RecordID' => $i,
                'paymentImg' =>
                    '<div class="d-flex align-items-center">
                        <div class="symbol symbol-50px me-3 mb-2">
                            <img src="'.$img.'" alt="">
                        </div>
                        <div class="d-flex flex-column">
                            <span class="text-gray-800 fw-bold fs-6">'.html_escape(strtoupper($r['gateway'])).'</span>
                            <span class="text-muted fs-7">Updated: '.html_escape($r['updated_at']).'</span>
                        </div>
                    </div>',
                'paymentMode' => $badge,
                'paymentStatus' =>
                    '<div class="form-check form-switch form-check-custom form-check-success form-check-solid">
                        <input class="form-check-input h-30px w-50px js-toggle-status demo-block" type="checkbox" '.$status.'
                            data-id="'.$r['id'].'" />
                    </div>',
                'paymentid' => $r['id'],
            ];
        }

        $resp = [
            'draw' => (int)$draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'data' => $data
        ];
        echo json_encode($resp);
    }

    /* Edit page + Save (new table) */
    public function edit($id)
    {
        $id = (int)$id;
        $row = $this->Payment_model->get_by_id($id);
        if (!$row) {
            $this->session->set_flashdata('danger', 'Invalid gateway');
            return redirect('payment-settings');
        }

        // ---- POST: Save ----
        if ($this->input->server('REQUEST_METHOD') === 'POST') {

            $this->form_validation->set_rules('mode', 'Mode', 'trim|required|in_list[sandbox,live,none]');
            $this->form_validation->set_rules('status', 'Status', 'trim|in_list[0,1]');

            // gateway specific rules (keys optional: keep old if left blank)
            $gw = strtolower($row->gateway);
            if ($gw === 'stripe') {
                $this->form_validation->set_rules('publishable_key', 'Publishable Key', 'trim');
                $this->form_validation->set_rules('secret_key', 'Secret Key', 'trim');
            } elseif ($gw === 'paypal') {
                $this->form_validation->set_rules('client_id', 'Client ID', 'trim');
                $this->form_validation->set_rules('client_secret', 'Client Secret', 'trim');
            } else { // cash_on or others
                // no extra rules
            }

            if ($this->form_validation->run() === FALSE) {
                $this->session->set_flashdata('danger', strip_tags(validation_errors()));
                return redirect('payment-settings/edit/'.$id);
            }

            $data = [
                'mode'   => $this->input->post('mode', true),
                'status' => (int)$this->input->post('status') === 1 ? 1 : 0,
            ];

            // only overwrite secrets if provided
            if ($gw === 'stripe') {
                $pk = trim($this->input->post('publishable_key', true));
                $sk = trim($this->input->post('secret_key', true));
                if ($pk !== '') $data['publishable_key'] = $pk;
                if ($sk !== '') $data['secret_key']      = $sk;
                // clear PayPal fields to avoid stale data (optional):
                $data['client_id'] = NULL; $data['client_secret'] = NULL;
            } elseif ($gw === 'paypal') {
                $cid = trim($this->input->post('client_id', true));
                $cs  = trim($this->input->post('client_secret', true));
                if ($cid !== '') $data['client_id']     = $cid;
                if ($cs  !== '') $data['client_secret'] = $cs;
                // clear Stripe fields (optional):
                $data['publishable_key'] = NULL; $data['secret_key'] = NULL;
            } else { // cash_on
                // clear all keys (optional)
                $data['publishable_key'] = NULL;
                $data['secret_key']      = NULL;
                $data['client_id']       = NULL;
                $data['client_secret']   = NULL;
            }

            $ok = $this->Payment_model->update($id, $data);
            $this->session->set_flashdata($ok ? 'success':'danger', $ok ? 'Payment settings updated' : 'Update failed');
            return redirect('payment-settings');
        }

        $verify_session = "ok";
        $this->data['verify_type'] = '1';
        // ---- GET: Render form ----
        $this->data['title']      = 'Edit Payment: '.strtoupper($row->gateway);
        $this->data['card_title'] = 'Edit '.strtoupper($row->gateway).' Settings';
        $this->data['payment']    = $row; // pass row for view
        $this->load->view('admin/settings/payment-edit-settings', $this->data);
    }

    /* AJAX: toggle status from list switch */
    public function toggle_status()
    {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            return $this->_json(false,'Invalid request');
        }
        $id = (int)$this->input->post('id');
        $status = (int)$this->input->post('status');
        $row = $this->Payment_model->get_by_id($id);
        if (!$row) return $this->_json(false,'Invalid gateway');

        $ok = $this->Payment_model->toggle_status($id, $status ? 1 : 0);
        return $this->_json((bool)$ok, $ok?'OK':'Failed');
    }

    private function _json($ok,$msg,$data=[]){
        $this->output->set_content_type('application/json')
            ->set_output(json_encode(['status'=>$ok,'message'=>$msg,'data'=>$data]));
    }

}
