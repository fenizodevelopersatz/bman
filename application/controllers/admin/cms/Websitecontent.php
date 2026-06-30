<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class  Websitecontent extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');
        $this->load->model('Admin_model');

        if (!$this->session->userdata('logged_in')) {
            redirect('admin/login');
        }

        $user = $this->Admin_model->get_user($this->session->userdata('userid'));

        if ($user->admin_roll == '1') {
            $permissions = json_decode($user->permission_pages, true);
            if (empty($permissions['website_content_cms'])) {
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

        $this->data['title'] = "Website Contentmanagment";
        $this->data['card_tilte'] = "Page Content List";
		$this->load->view('admin/cms/website_content',$this->data);

    }
    /*
    |--------------------------------------------------------------------------
    | Page List View
    |--------------------------------------------------------------------------
    */
    public function list(){

        $this->load->model('cms/Websitecontent_model');
        $draw = $this->input->get('draw');
        $start = $this->input->get('start');
        $length = $this->input->get('length');
        
        $data = array();
        $total_records = $this->Websitecontent_model->get_count();
        $users = $this->Websitecontent_model->get_info($length, $start);

        $i = 0;
        foreach ($users as $user) {
        $i++;

        $currency_status = $user['temp_status'] ? "checked" : "";
        $change_status_url = base_url()."websitecontent-status-update-cms/".$user['id'];

        $data[] = array(
        'RecordID' => $i,
        'temp_name' => '<div class="d-flex justify-content-start flex-column">
        <div class="d-flex justify-content-start ">
        <span class="me-2 badge badge-light-primary">'.$user['page_name'].'</span>
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
        <a class="btn btn-success btn-active-light-success btn-sm dropdown-toggle_sedit-summary text-center" href="'.base_url().'edit-websitecontent-cms/'.$user['id'].'">
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
        public function view_section($id){

            $ai_report = $this->db->query("SELECT * FROM page_content where id = '".$id."' ")->row();;
        
            $output = '<div class="ai-report-container">';
            $output = $ai_report->content;
            $output .= '</div>';
        
            echo $output;
        }
        /*
        |--------------------------------------------------------------------------
        | Section Edit Page
        |--------------------------------------------------------------------------
        */
        public function edit_section($id){

            $this->data['title'] = "Edit Website Content";
            $this->data['card_title'] = "Edit Section";
            $this->data['template_info'] = $this->db->query("SELECT * FROM page_content where id = '".$id."' ")->row();
            $this->load->view('admin/cms/edit_website_content', $this->data);
    
        }
        /*
        |--------------------------------------------------------------------------
        | Section Update
        |--------------------------------------------------------------------------
        */
        public function update_section(){

            if($this->input->post()){
                $template_id = $this->input->post('template_id');
                $content = $this->input->post('content');
        
                if($template_id){
        
                    $update_template = array(
                        "content" => htmlspecialchars_decode($content)
                    );
        
                    $this->db->where('id', $template_id);
                    $this->db->update('page_content', $update_template);
        
                    $response = array(
                        'status' => true,
                        'message' => "Website Content updated Successfully"
                    );
                    echo json_encode($response);
                    exit();
        
                } else {
                    $response = array(
                        'status' => false,
                        'message' => "Invalid content section ID"
                    );
                    echo json_encode($response);
                    exit();
                }
            }
        }
        
        /*
        |--------------------------------------------------------------------------
        | Section Status Update
        |--------------------------------------------------------------------------
        */
        public function status_update($id){

            if($id){

                $check_template = $this->db->query("SELECT * FROM `page_content` where id = '".$id."'")->num_rows();

                if($check_template > 0){

                    $status = $this->input->post('template_status');
                    $template_status = $status ? '1':'0';

                    $array_template = array(
                        "temp_status" => $template_status,
                    );

                    $this->db->where('id',$id);
                    $this->db->update('page_content',$array_template);

                    $response = array(
                        'status' => "success",
                        'message' => "Status update successfully.."
                    );
                    echo json_encode($response);
                    exit(); 
                } else {
                    $response = array(
                        'status' => false,
                        'message' => "Invalide section ID!"
                    );
                    echo json_encode($response);
                    exit(); 
                }

            }

        }
}