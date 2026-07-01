<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Slider extends MY_Controller
{

    public function __construct()
    {
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
            if (empty($permissions['slider_cms'])) {
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
    public function index()
    {

        $this->data['title'] = "Slider Managment";
        $this->data['card_tilte'] = "Slider List";
        $this->load->view('admin/cms/slider_list', $this->data);

    }
    /*
    |--------------------------------------------------------------------------
    | Page List View
    |--------------------------------------------------------------------------
    */
    public function list()
    {

        $this->load->model('cms/Slider_model');
        $draw = $this->input->get('draw');
        $start = $this->input->get('start');
        $length = $this->input->get('length');

        $data = array();
        $total_records = $this->Slider_model->get_count();
        $users = $this->Slider_model->get_info($length, $start);

        $i = 0;
        foreach ($users as $user) {
            $i++;

            $currency_status = $user['status'] ? "checked" : "";
            $change_status_url = base_url() . "slider-status-update-cms/" . $user['id'];

            $data[] = array(
                'RecordID' => $i,
                'temp_name' => '<div class="d-flex justify-content-start flex-column">
        <div class="d-flex justify-content-start ">
        <span class="me-2 badge badge-light-primary">' . $user['title'] . '</span>
        </div>
        </div>',
                'temp_status' => '<div class="form-check form-switch form-check-custom form-check-success form-check-solid">
        <input class="form-check-input h-30px w-50px template_status" type="checkbox" value="" name="template_status"' .
                    $currency_status . '
        id="template_status" 
        data-payment="' . $user['id'] . '" 
        data-template_status-url="' . $change_status_url . '"/>
        <label class="form-check-label" for="template_status">
        </label>
        </div>',
                'temp_content' => '<div class="d-flex justify-content-center flex-row">
        <a class="btn btn-success btn-active-light-success btn-sm dropdown-toggle_sedit-summary text-center me-4" href="' . base_url() . 'edit-slider-cms/' . $user['id'] . '">
		 <i class="fa-solid fa-pen-to-square "></i> Edit
		</a>
        <a class="btn btn-danger btn-active-light-danger btn-sm text-center btn-delete" href="javascript:void(0);"  data-delete-url="' . base_url() . 'delete-slider-cms/' . $user['id'] . '">
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
    public function view_section($id)
    {

        $ai_report = $this->db->query("SELECT * FROM announcement where id = '" . $id . "' ")->row();
        ;

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
    public function edit_slider($id)
    {

        $template_info = $this->db->query("SELECT * FROM sliders_img where id = '" . $id . "' ")->row();

        $this->data['title'] = "Edit Slider Content";
        $this->data['card_title'] = "Edit Slider";
        $this->data['slider_title'] = $template_info->title;
        $this->data['slider_id'] = $template_info->id;
        $this->data['slider_type'] = $template_info->type;
        $this->data['slider_file'] = base_url() . "" . $template_info->image;
        $this->data['banner'] = $template_info;

        $this->load->view('admin/cms/add-slider', $this->data);

    }
    /*
   |--------------------------------------------------------------------------
   | Section Edit Page
   |--------------------------------------------------------------------------
   */
    public function add()
    {
        if ($this->input->post()) {
            $this->form_validation->set_rules('slider_title', 'Title', 'required');
            $this->form_validation->set_rules('label_text', 'Label Text', 'required');
            $this->form_validation->set_rules('heading', 'Main Heading', 'required');

            $slider_id = $this->input->post('slider_id');

            if ($slider_id <= 0 && empty($_FILES['userfile']['name'])) {
                $this->form_validation->set_rules('userfile', 'Banner File', 'required');
            }

            if ($this->form_validation->run() === FALSE) {
                echo json_encode(['status' => false, 'message' => "Validation failed. Please fill required fields."]);
                exit;
            }

            // Collect form data
            $slider_data = array(
                'title' => $this->input->post('slider_title'),
                'label_text' => $this->input->post('label_text'),
                'heading' => $this->input->post('heading'),
                'sub_heading' => $this->input->post('sub_heading'),
                'button_text' => $this->input->post('button_text'),
                'button_url' => $this->input->post('button_url'),
                'created_date' => date('Y-m-d H:i:s')
            );

            // Handle File Upload
            if (!empty($_FILES['userfile']['name'])) {

                if (ENABLE_SITE_UPLOAD_FUNCTION !== true) {
                    $this->_dbg('slider_upload_images', 'Slider Uploads disabled');
                    return false;
                }

                $this->load->library('upload');
                $mime = $_FILES['userfile']['type'];
                $filetype = '';

                if (strstr($mime, "video/")) {
                    $filetype = "video";
                } else if (strstr($mime, "image/")) {
                    $filetype = "image";
                } else {
                    echo json_encode(['status' => false, 'message' => "Only image or video files allowed."]);
                    exit;
                }

                $file_path = './assets/images/sliders/';
                $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9_.]/', '_', $_FILES["userfile"]["name"]);
                $upload_config = [
                    'upload_path' => $file_path,
                    'allowed_types' => 'jpg|jpeg|png|gif|mp4',
                    'file_name' => $filename,
                    'overwrite' => false,
                    'max_size' => 102400,
                ];

                $this->load->library('upload', $upload_config);
                $this->upload->initialize($upload_config);

                if (!$this->upload->do_upload('userfile')) {
                    echo json_encode(['status' => false, 'message' => $this->upload->display_errors()]);
                    exit;
                }

                $slider_data['image'] = 'assets/images/sliders/' . $filename;
                $slider_data['type'] = $filetype;
            }

            // Update or Insert
            if ($slider_id > 0) {
                $this->db->where('id', $slider_id);
                $this->db->update('sliders_img', $slider_data);
                echo json_encode(['status' => true, 'message' => "Slider updated successfully"]);
            } else {
                $slider_data['status'] = '1';
                $this->db->insert('sliders_img', $slider_data);
                echo json_encode(['status' => true, 'message' => "Slider created successfully"]);
            }
            exit;
        } else {
            // Form view load
            $this->data['title'] = "Add Slider";
            $this->data['card_title'] = "New Slider";
            $this->data['slider_title'] = "";
            $this->data['slider_id'] = "";
            $this->data['slider_type'] = "";
            $this->data['slider_file'] = "";
            $this->data['label_text'] = "";
            $this->data['heading'] = "";
            $this->data['sub_heading'] = "";
            $this->data['button_text'] = "";
            $this->data['button_url'] = "";

            $this->load->view('admin/cms/add-slider', $this->data);
        }
    }
    /*
    |--------------------------------------------------------------------------
    | Section Status Update
    |--------------------------------------------------------------------------
    */
    public function status_update($id)
    {

        if ($id) {

            $check_template = $this->db->query("SELECT * FROM `sliders_img` where id = '" . $id . "'")->num_rows();

            if ($check_template > 0) {

                $status = $this->input->post('template_status');
                $template_status = $status ? '1' : '0';

                $array_template = array(
                    "status" => $template_status,
                );

                $this->db->where('id', $id);
                $this->db->update('sliders_img', $array_template);

                $response = array(
                    'status' => "success",
                    'message' => "Status update successfully.."
                );
                echo json_encode($response);
                exit();
            } else {
                $response = array(
                    'status' => false,
                    'message' => "Invalide slider ID!"
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
    public function delete_slider($id)
    {

        if ($id) {

            $check_template = $this->db->query("SELECT * FROM `sliders_img` where id = '" . $id . "'")->num_rows();

            if ($check_template > 0) {

                $this->db->where('id', $id);
                $this->db->delete('sliders_img');

                $response = array(
                    'status' => "success",
                    'message' => "Slider Delete successfully.."
                );
                echo json_encode($response);
                exit();
            } else {
                $response = array(
                    'status' => false,
                    'message' => "Invalide Slider ID!"
                );
                echo json_encode($response);
                exit();
            }

        }

    }

}