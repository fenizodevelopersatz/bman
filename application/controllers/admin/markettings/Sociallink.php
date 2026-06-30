<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class  Sociallink extends CI_Controller {

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
            if (empty($permissions['social_link'])) {
                $this->session->set_flashdata('error', 'Access Denied: You do not have permission.');
                redirect('admin');
            }
        }

        $this->load->model('settings/Payment_model');
    }
   
    /*
    |--------------------------------------------------------------------------
    | List View 
    |--------------------------------------------------------------------------
    */
       public function index(){

        $this->data['title'] = "Social Media Links";
        $this->data['card_title'] = "Update Social Media Links";
        $this->data['social_links'] = $this->db->get('sociallinks')->result_array();
		$this->load->view('admin/social/index',$this->data);

    }
    /*
    |--------------------------------------------------------------------------
    | UPDATE Social Link
    |--------------------------------------------------------------------------
    */
    public function update()
    {
        header('Content-Type: application/json');
    
        $social_ids = $this->input->post('social_id');
        $social_links = $this->input->post('social_link');
    
        if (empty($social_ids) || empty($social_links)) {
            echo json_encode([
                'status' => false,
                'message' => "Input fields cannot be empty."
            ]);
            return;
        }
    
        $errors = [];
        $updated = 0;
    
        foreach ($social_ids as $key => $id) {
            $link = trim($social_links[$key]);
    
            if (!is_numeric($id)) {
                $errors[] = "Invalid ID for entry $key.";
                continue;
            }
    
            if (!filter_var($link, FILTER_VALIDATE_URL)) {
                $errors[] = "Invalid URL format for ID $id.";
                continue;
            }
    
            $data = ['link' => $link];
            if ($this->update_social_link($id, $data)) {
                $updated++;
            }
        }
    
        if (!empty($errors)) {
            echo json_encode([
                'status' => false,
                'message' => "Validation failed.",
                'errors' => $errors
            ]);
        } elseif ($updated > 0) {
            echo json_encode([
                'status' => true,
                'message' => "$updated Social Media Links Updated Successfully"
            ]);
        } else {
            echo json_encode([
                'status' => false,
                'message' => "No changes were made."
            ]);
        }
    }
    

    /*
    |--------------------------------------------------------------------------
    | UPDATE Social Link
    |--------------------------------------------------------------------------
    */
    public function update_social_link($id, $data)
    {
        $this->db->where('id', $id);
        return $this->db->update('sociallinks', $data);
    }


}