<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class  Announcement extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');
        $this->load->model('Admin_model');

        if (!$this->session->userdata('admin_logged_in')) {
            redirect('admin/login');
        }

        $user = $this->Admin_model->get_user($this->session->userdata('admin_userid'));

        if (!$user) {
            redirect('admin/login');
        }

        if ($user->admin_roll == '1') {
            $permissions = json_decode($user->permission_pages, true);
            if (empty($permissions['annoucement_cms'])) {
                $this->session->set_flashdata('error', 'Access Denied: You do not have permission.');
                redirect('admin');
            }
        }

    }


   
    /*
    |--------------------------------------------------------------------------
    | Mail Index
    |--------------------------------------------------------------------------
    */
       public function index(){

        $this->data['title'] = "Announcement Contentmanagment";
        $this->data['card_tilte'] = "Announcement List";
		$this->load->view('admin/cms/announcement_list',$this->data);

    }
    /*
    |--------------------------------------------------------------------------
    | Page List View
    |--------------------------------------------------------------------------
    */
    public function list(){

        $this->load->model('cms/Announcement_model');
        $draw = $this->input->get('draw');
        $start = $this->input->get('start');
        $length = $this->input->get('length');
        
        $data = array();
        $total_records = $this->Announcement_model->get_count();
        $users = $this->Announcement_model->get_info($length, $start);

        $i = 0;
        foreach ($users as $user) {
        $i++;

        $currency_status = $user['title_status'] ? "checked" : "";
        $change_status_url = base_url()."announcement-status-update-cms/".$user['id'];

        $data[] = array(
        'RecordID' => $i,
        'temp_name' => '<div class="d-flex justify-content-start flex-column">
        <div class="d-flex justify-content-start ">
        <span class="me-2 badge badge-light-primary">'.$user['title'].'</span>
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
        <a class="btn btn-success btn-active-light-success btn-sm dropdown-toggle_sedit-summary text-center me-4" href="'.base_url().'edit-announcement-cms/'.$user['id'].'">
		 <i class="fa-solid fa-pen-to-square "></i> Edit
		</a>
        <a class="btn btn-danger btn-active-light-danger btn-sm text-center btn-delete" href="javascript:void(0);"  data-delete-url="'.base_url().'delete-announcement-cms/'.$user['id'].'">
		 <i class="fa fa-trash" aria-hidden="true"></i>  Delete
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

            $ai_report = $this->db->query("SELECT * FROM announcement where id = '".$id."' ")->row();;
        
            $output = '<div class="ai-report-container">';
            $output = $ai_report->title;
            $output .= '</div>';
        
            echo $output;
        }
        /*
        |--------------------------------------------------------------------------
        | Section Edit Page
        |--------------------------------------------------------------------------
        */
        public function edit_section($id){

            $template_info= $this->db->query("SELECT * FROM announcement where id = '".$id."' ")->row();
            $this->data['title'] = "Edit Website Content";
            $this->data['card_title'] = "Edit Section";
            $this->data['announcement_content'] = $template_info->title;
            $this->data['announcement_id'] = $template_info->id;
            $this->load->view('admin/cms/add-annoucement', $this->data);
    
        }
         /*
        |--------------------------------------------------------------------------
        | Section Edit Page
        |--------------------------------------------------------------------------
        */
        public function add(){

            if($this->input->post()){

            $announcement_id = $this->input->post("announcement_id");
            $announcement_content = $this->input->post("announcement_content");

            if($announcement_content != ""){

            $announcement_data = array(
            "title" => $announcement_content,
            "created_date" => date("y-m-d H:i:s")
            );

            if($announcement_id > 0){

            $this->db->where('id',$announcement_id);
            $this->db->update('announcement',$announcement_data);
            echo json_encode(['status' => true, 'message' => "announcement updated successfully"]);
            exit;

            } else {

            $this->db->insert('announcement',$announcement_data);
            echo json_encode(['status' => true, 'message' => "announcement created successfully"]);
            exit;

            }

            } else {
            echo json_encode(['status' => false, 'message' => "Please enter announcement"]);
            exit;
            }


            } else {

            $this->data['title'] = "Add Announcement";
            $this->data['card_title'] = "Announcement";
            $this->data['announcement_content'] = "";
            $this->data['announcement_id'] = "";
            $this->load->view('admin/cms/add-annoucement', $this->data);

            }
            
    
        }
        /*
        |--------------------------------------------------------------------------
        | Section Status Update
        |--------------------------------------------------------------------------
        */
        public function status_update($id){

            if($id){

                $check_template = $this->db->query("SELECT * FROM `announcement` where id = '".$id."'")->num_rows();

                if($check_template > 0){

                    $status = $this->input->post('template_status');
                    $template_status = $status ? '1':'0';

                    $array_template = array(
                        "title_status" => $template_status,
                    );

                    $this->db->where('id',$id);
                    $this->db->update('announcement',$array_template);

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
         /*
        |--------------------------------------------------------------------------
        | Section Delete
        |--------------------------------------------------------------------------
        */
        public function delete_section($id){

            if($id){

                $check_template = $this->db->query("SELECT * FROM `announcement` where id = '".$id."'")->num_rows();

                if($check_template > 0){

                    $this->db->where('id',$id);
                    $this->db->delete('announcement');

                    $response = array(
                        'status' => "success",
                        'message' => "Announcement Delete successfully.."
                    );
                    echo json_encode($response);
                    exit(); 
                } else {
                    $response = array(
                        'status' => false,
                        'message' => "Invalide Announcement ID!"
                    );
                    echo json_encode($response);
                    exit(); 
                }

            }

        }
        
}