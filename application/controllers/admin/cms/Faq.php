<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Faq extends CI_Controller
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
            if (empty($permissions['faq_cms'])) {
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

        $this->data['title'] = "FAQ management";
        $this->data['card_tilte'] = "FAQ List";
        $this->load->view('admin/cms/faq_list', $this->data);

    }
    /*
    |--------------------------------------------------------------------------
    | Page List View
    |--------------------------------------------------------------------------
    */
    public function list()
    {

        $this->load->model('cms/Faq_model');
        $draw = $this->input->get('draw');
        $start = $this->input->get('start');
        $length = $this->input->get('length');

        $data = array();
        $total_records = $this->Faq_model->get_count();
        $users = $this->Faq_model->get_info($length, $start);

        $i = 0;
        foreach ($users as $user) {
            $i++;

            $currency_status = $user['status'] ? "checked" : "";
            $change_status_url = base_url() . "faq-status-update-cms/" . $user['id'];

            $data[] = array(
                'RecordID' => $i,
                'temp_name' => '<div class="d-flex justify-content-start flex-column">
        <div class="d-flex justify-content-start ">
        <span class="me-2 badge badge-light-primary">' . $user['question'] . '</span>
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
        <a class="btn btn-success btn-active-light-success btn-sm dropdown-toggle_sedit-summary text-center me-4" href="' . base_url() . 'edit-faq-cms/' . $user['id'] . '">
		 <i class="fa-solid fa-pen-to-square "></i> Edit
		</a>
        <a class="btn btn-danger btn-active-light-danger btn-sm text-center btn-delete" href="javascript:void(0);"  data-delete-url="' . base_url() . 'delete-faq-cms/' . $user['id'] . '">
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

        $ai_report = $this->db->query("SELECT * FROM faqs where id = '" . $id . "' ")->row();
        ;

        $output = '<div class="ai-report-container">';
        $output = $ai_report->answer;
        $output .= '</div>';

        echo $output;
    }
    /*
    |--------------------------------------------------------------------------
    | Section Edit Page
    |--------------------------------------------------------------------------
    */
    public function edit_faq($id)
    {

        $template_info = $this->db->query("SELECT * FROM faqs where id = '" . $id . "' ")->row();

        $this->data['title'] = "Edit FAQ Content";
        $this->data['card_title'] = "Edit FAQ";

        $this->data['faq_question'] = $template_info->question;
        $this->data['faq_answer'] = $template_info->answer;
        $this->data['faq_id'] = $template_info->id;

        $this->load->view('admin/cms/add-faq', $this->data);

    }
    /*
   |--------------------------------------------------------------------------
   | Section Edit Page
   |--------------------------------------------------------------------------
   */
    public function add()
    {

        if ($this->input->post()) {

            $this->form_validation->set_rules('faq_question', 'Question', 'required');
            $this->form_validation->set_rules('faq_answer', 'Answer', 'required');

            if ($this->form_validation->run() === FALSE) {

                echo json_encode(['status' => false, 'message' => "Please Check FAQ Question and Answer "]);
                exit;

            } else {


                $faq_id = $this->input->post('faq_id', true);
                $faq_question = $this->input->post('faq_question', true);
                $faq_answer = $this->input->post('faq_answer');

                $faqs_data = array(
                    'question' => $faq_question,
                    'answer' => $faq_answer,
                    'datetime' => date('Y-m-d H:i:s')
                );


                if ($faq_id > 0) {

                    $this->db->where('id', $faq_id);
                    $this->db->update('faqs', $faqs_data);
                    echo json_encode(['status' => true, 'message' => "FAQ data updated successfully"]);
                    exit;

                } else {

                    $faqs_data['status'] = '1';
                    $this->db->insert('faqs', $faqs_data);
                    echo json_encode(['status' => true, 'message' => "FAQ data created successfully"]);
                    exit;

                }


            }

        } else {

            $this->data['title'] = "Add FAQ";
            $this->data['card_title'] = "New FAQ";
            $this->data['faq_question'] = "";
            $this->data['faq_answer'] = "";
            $this->data['faq_id'] = "";
            $this->load->view('admin/cms/add-faq', $this->data);

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

            $check_template = $this->db->query("SELECT * FROM `faqs` where id = '" . $id . "'")->num_rows();

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
                    'message' => "Invalide FAQ ID!"
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
    public function delete_faq($id)
    {

        if ($id) {

            $check_template = $this->db->query("SELECT * FROM `faqs` where id = '" . $id . "'")->num_rows();

            if ($check_template > 0) {

                $this->db->where('id', $id);
                $this->db->delete('faqs');

                $response = array(
                    'status' => "success",
                    'message' => "faq Delete successfully.."
                );
                echo json_encode($response);
                exit();
            } else {
                $response = array(
                    'status' => false,
                    'message' => "Invalide faq ID!"
                );
                echo json_encode($response);
                exit();
            }

        }

    }

}