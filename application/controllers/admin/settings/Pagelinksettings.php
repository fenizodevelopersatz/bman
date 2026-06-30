<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pagelinksettings extends MY_Controller
{

    public function __construct()
    {
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
            if (empty($permissions['commission_settings'])) {
                $this->session->set_flashdata('error', 'Access Denied: You do not have permission.');
                redirect('admin');
            }
        }

        $this->load->model('settings/Commission_model');
    }
    /*
    |--------------------------------------------------------------------------
    | Commission Settings
    |--------------------------------------------------------------------------
    */
    public function index()
    {
        $this->data['title'] = 'Pagelink Settings';
        $this->data['card_title'] = 'Pagelink Settings';
        $query = $this->db->get('page_link_config');
        $sections = [];

        foreach ($query->result() as $row) {
            $sections[$row->title] = $row;
        }

        $this->data['sections'] = $sections;

        $this->load->view('admin/settings/pagelinks-edit-settings', $this->data);
    }
    /*
    |--------------------------------------------------------------------------
    | Update Commission Settings
    |--------------------------------------------------------------------------
    */


    private function uploadImageField($input_name)
    {
        if (ENABLE_SITE_UPLOAD_FUNCTION !== true) {
            $this->_dbg('page_link_settings_upload_images', 'Uploads disabled');
            return false;
        }
        if (!empty($_FILES[$input_name]["name"])) {
            $allowed_extensions = ['jpg', 'jpeg', 'png'];
            $original_name = $_FILES[$input_name]["name"];
            $file_ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

            if (!in_array($file_ext, $allowed_extensions)) {
                return null;
            }

            $fileName = time() . '_' . $original_name;
            $targetFile = './assets/images/' . $fileName;

            if (move_uploaded_file($_FILES[$input_name]['tmp_name'], $targetFile)) {
                return 'assets/images/' . $fileName;
            }
        }
        return null;
    }

    private function uploadDocumentField($input_name)
    {
        if (!empty($_FILES[$input_name]["name"])) {
            $allowed_extensions = ['pdf', 'doc', 'docx', 'txt'];
            $original_name = $_FILES[$input_name]["name"];
            $file_ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

            if (!in_array($file_ext, $allowed_extensions)) {
                return null;
            }

            $fileName = time() . '_' . $original_name;
            $targetFile = './assets/documents/' . $fileName;

            if (!is_dir('./assets/documents/')) {
                mkdir('./assets/documents/', 0777, true);
            }

            if (move_uploaded_file($_FILES[$input_name]['tmp_name'], $targetFile)) {
                return 'assets/documents/' . $fileName;
            }
        }
        return null;
    }

    public function update()
    {
        $this->load->library('form_validation');

        $sectionsToValidate = [
            'whitepaper' => 'whitepaper_status',
            'project' => 'project_status',
            'roadmap' => 'roadmap_status',
            'airobotics' => 'airobotics_status',
            'ecommerce' => 'ecommerce_status',
            'games' => 'games_status',
            'education' => 'education_status',
        ];

        foreach ($sectionsToValidate as $prefix => $statusField) {
            $status = $this->input->post($statusField);
            if ($status != '1') {
                $this->form_validation->set_rules("{$prefix}_title", ucfirst($prefix) . ' Title', 'required|trim');
                $this->form_validation->set_rules("{$prefix}_content", ucfirst($prefix) . ' Content', 'required|trim');
            }
        }

        $this->form_validation->set_rules('test_field', 'Test Field', 'required');

        if ($this->form_validation->run() == FALSE) {
            echo json_encode([
                'status' => false,
                'message' => validation_errors(),
            ]);
            return;
        }

        $status = true;
        $response_messages = [];

        $sections = [
            'White Paper' => 'whitepaper',
            'Project' => 'project',
            'Roadmap' => 'roadmap',
            'ai robotics' => 'airobotics',
            'E-commerce' => 'ecommerce',
            'Games' => 'games',
            'education' => 'education',
        ];

        foreach ($sections as $sectionTitle => $prefix) {

            $sectionStatus = $this->input->post("{$prefix}_status") ? 1 : 0;

            $updateData = [
                'page_status' => $sectionStatus
            ];

            if ($sectionStatus == 1) {

                $document = $this->uploadDocumentField("{$prefix}_document");
                if (!empty($document)) {
                    $updateData['page_document'] = $document;
                }


            } else {

                $title = $this->input->post("{$prefix}_title");
                $content = $this->input->post("{$prefix}_content");
                $image = $this->uploadImageField("{$prefix}_image");

                if (!empty($title))
                    $updateData['page_title'] = $title;
                if (!empty($content))
                    $updateData['page_content'] = $content;
                if (!empty($image))
                    $updateData['page_image'] = $image ?? '';

            }

            $updated = $this->db->update('page_link_config', $updateData, ['title' => $sectionTitle]);
            $response_messages[] = $updated ? "$sectionTitle updated successfully" : "$sectionTitle update failed";
            if (!$updated)
                $status = false;
        }

        echo json_encode([
            'status' => $status,
            'message' => implode(" | ", $response_messages),
        ]);
    }



}