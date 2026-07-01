<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class  Emailmarkettings extends CI_Controller {

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
            if (empty($permissions['email_markettings'])) {
                $this->session->set_flashdata('error', 'Access Denied: You do not have permission.');
                redirect('admin');
            }
        }

        $this->load->model('settings/Payment_model');
    }


   
    /*
    |--------------------------------------------------------------------------
    | Mail Index
    |--------------------------------------------------------------------------
    */
       public function index(){

        $this->data['title'] = "Email Templates";
        $this->data['card_tilte'] = "List Of Email Templates";
		$this->load->view('admin/email/index',$this->data);

    }
    /*
    |--------------------------------------------------------------------------
    | Mail List View
    |--------------------------------------------------------------------------
    */
     public function list(){

        $this->load->model('markettings/Email_model');
        $draw = $this->input->get('draw');
        $start = $this->input->get('start');
        $length = $this->input->get('length');
        
        $data = array();
        $total_records = $this->Email_model->get_count();
        $users = $this->Email_model->get_info($length, $start);

        $i = 0;
        foreach ($users as $user) {
        $i++;

        $currency_status = $user['temp_status'] ? "checked" : "";
        $change_status_url = base_url()."template-status-update/".$user['id'];

        $data[] = array(
        'RecordID' => $i,
        'temp_name' => '<div class="d-flex justify-content-start flex-column">
        <div class="d-flex justify-content-start ">
        <span class="me-2 badge badge-light-primary">'.$user['temp_name'].'</span>
        </div>
        </div>',
        'temp_status' => '<div class="form-check form-switch form-check-custom form-check-success form-check-solid">
        <input class="form-check-input h-30px w-50px template_status" type="checkbox" value="" name="template_status"'.
        $currency_status.'
        id="template_status" 
        data-payment="'.$user['id'].'" 
        data-template_status-url="'.$change_status_url.'"/>
        <label class="form-check-label" for="template_status">
        </label>
        </div>',
        'temp_content' => '<div class="d-flex justify-content-center flex-row">
		<button class="btn btn-light-info btn-active-light-primary btn-sm dropdown-toggle_s view-summary text-center me-4 " data-bs-toggle="modal" data-bs-target="#kt_modal_view_summary" data-summary="'.$user['id'].'">
		<i class="fa-solid fa-eye "></i> View
		</button>
        <a class="btn btn-success btn-active-light-success btn-sm dropdown-toggle_sedit-summary text-center" href="'.base_url().'edit-template/'.$user['id'].'">
		 <i class="fa-solid fa-pen-to-square "></i> Edit
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
        | Mail List View
        |--------------------------------------------------------------------------
        */
        public function view_template($id){

            $ai_report = $this->db->query("SELECT * FROM email_template where id = '".$id."' ")->row();;
        
            $output = '<div class="ai-report-container">';
            $output = $ai_report->temp_content;
            $output .= '</div>';
        
            echo $output;
        }
        /*
        |--------------------------------------------------------------------------
        | Mail Edit Page
        |--------------------------------------------------------------------------
        */
        public function edit_template($id){

            $this->data['title'] = "Edit Email Template";
            $this->data['card_title'] = "Edit Template";
            $this->data['template_info'] = $this->db->query("SELECT * FROM email_template where id = '".$id."' ")->row();
            $this->load->view('admin/email/edit', $this->data);
    
        }
        /*
        |--------------------------------------------------------------------------
        | Template Update
        |--------------------------------------------------------------------------
        */
        public function template_update(){

            if($this->input->post()){
          
                $template_id = $this->input->post('template_id');
                $content = $this->input->post('mail_content');
         
                if($template_id){
        
                    $update_template = array(
                        "subject" => $this->input->post('mail_subject'),
                        "temp_content" => $content
                    );
        
                    $this->db->where('id',$template_id);
                    $this->db->update('email_template',$update_template);
               
                    $response = array(
                        'status' => true,
                        'message' => "Email Tempalte update Successfully"
                    );
                    echo json_encode($response);
                    exit();

                } else {
                    $response = array(
                        'status' => false,
                        'message' => "Invalide Email Template ID"
                    );
                    echo json_encode($response);
                    exit();
                }
        
              }
        }
        /*
        |--------------------------------------------------------------------------
        | Template Update
        |--------------------------------------------------------------------------
        */
        public function template_status_update($id){

            if($id){

                $check_template = $this->db->query("SELECT * FROM `email_template` where id = '".$id."'")->num_rows();

                if($check_template > 0){

                    $status = $this->input->post('template_status');
                    $template_status = $status ? '1':'0';

                    $array_template = array(
                        "temp_status" => $template_status,
                    );

                    $this->db->where('id',$id);
                    $this->db->update('email_template',$array_template);

                    $response = array(
                        'status' => "success",
                        'message' => "Status update successfully.."
                    );
                    echo json_encode($response);
                    exit(); 
                } else {
                    $response = array(
                        'status' => false,
                        'message' => "Invalide Email Template ID!"
                    );
                    echo json_encode($response);
                    exit(); 
                }

            }

        }
        
}