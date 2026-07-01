<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Supportmanagement extends MY_Controller
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

        if ($user->admin_roll == '1') {
            $permissions = json_decode($user->permission_pages, true);
            if (empty($permissions['support_management'])) {
                $this->session->set_flashdata('error', 'Access Denied: You do not have permission.');
                redirect('admin');
            }
        }

    }
    /*
    |--------------------------------------------------------------------------
    | Index Page
    |--------------------------------------------------------------------------
    */
    public function index()
    {

        $this->data['title'] = "Support Ticket List";
        $this->data['card_tilte'] = "List Of Ticket";

        $query = $this->db->query("
        SELECT 
        COUNT(*) as all_count,
        SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as open_count,
        SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as closed_count,
        SUM(CASE WHEN DATE(date) = CURDATE() THEN 1 ELSE 0 END) as new_ticket_count
        FROM support
        ")->row();

        $this->data['all_ticket_count'] = $query->all_count;
        $this->data['pending_ticket_count'] = $query->pending_count;
        $this->data['open_ticket_count'] = $query->open_count;
        $this->data['closed_ticket_count'] = $query->closed_count;
        $this->data['new_ticket_count'] = $query->new_ticket_count;

        $this->load->view('admin/support/list', $this->data);

    }
    /*
    |--------------------------------------------------------------------------
    | List Page
    |--------------------------------------------------------------------------
    */
    public function list()
    {

        $this->load->model('support/Support_model');
        $draw = $this->input->get('draw');
        $start = $this->input->get('start');
        $length = $this->input->get('length');


        $type = $this->input->get('call_status');
        $clients = $this->input->get('client_filter');
        $from_date = $this->input->get('from_date') ? date('Y-m-d', strtotime($this->input->get('from_date'))) : '';
        $to_date = $this->input->get('to_date') ? date('Y-m-d', strtotime($this->input->get('to_date'))) : '';
        $filter_by = $this->input->get('filter_by');

        $data = array();
        $total_records = $this->Support_model->get_count($from_date, $to_date, $clients, $type, $filter_by);
        $users = $this->Support_model->get_info($length, $start, $from_date, $to_date, $clients, $type, $filter_by);

        $i = 0;
        foreach ($users as $user) {
            $i++;

            $tiket_subject = $user['subject'];
            $tiket_id = $user['ticket_id'];

            $user_infos = $this->db->query("SELECT * FROM users where id = '" . $user['user_id'] . "' ")->row();
            $user_email = $user_infos && $user_infos->email ? $user_infos->email : " Unkown User";
            $referral_id = $user_infos && $user_infos->referral_id ? $user_infos->referral_id : " Unkown User";

            $status = "";

            if ($user['status'] == '0') {
                $status = '<a href="#" class="btn btn-danger btn-active-light-danger btn-sm  text-center me-3">Pending</a>';
            }
            if ($user['status'] == '1') {
                $status = '<a href="#" class="btn btn-warning btn-active-light-warning btn-sm  text-center me-3">Open</a>';
            }
            if ($user['status'] == '2') {
                $status = '<a href="#" class="btn btn-success btn-active-light-success btn-sm  text-center me-3">Close</a>';
                ;
            }

            $data[] = array(
                'RecordID' => $i,
                'TicketInfo' => '<div class="d-flex align-items-center">
        <div class="symbol symbol-50px me-3">                                                   
        </div>
        <div class="d-flex justify-content-start flex-column">
        <a href="#" class="text-gray-800 fw-bold text-hover-primary mb-1 fs-6">' . $tiket_subject . '</a>
        <span class="text-gray-500 fw-semibold d-block fs-7">' . $tiket_id . '</span>
        </div>
        </div>',
                'UserInfo' => '<div class="d-flex align-items-center">
        <div class="symbol symbol-50px me-3">                                                   
        </div>
        <div class="d-flex justify-content-start flex-column">
        <a href="#" class="text-gray-800 fw-bold text-hover-primary mb-1 fs-6">' . $referral_id . '</a>
        <span class="text-gray-500 fw-semibold d-block fs-7">' . $user_email . '</span>
        </div>
        </div>',
                'DateInfo' => '<div class="d-flex align-items-center">
        <div class="symbol symbol-50px me-3">                                                   
        </div>
        <div class="d-flex justify-content-start flex-column">
        <a href="#" class="text-gray-800 fw-bold text-hover-primary mb-1 fs-6">' . $user['date'] . '</a>
        </div>
        </div>',
                'Status' => '<div class="d-flex align-items-center">
        <div class="symbol symbol-50px me-3">     
        ' . $status . '                                              
        </div>
        <div class="d-flex justify-content-start flex-column">
        
        </div>
        </div>',
                'Action' => '<div class="d-flex justify-content-center flex-row">
        <a class="btn btn-success btn-active-light-success btn-sm  text-center me-3" href="' . base_url() . 'edit-ticket/' . $user['id'] . '">
		View
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
    | Edit Ticket
    |--------------------------------------------------------------------------
    */
    public function edit($id)
    {

        if ($id) {

            $this->data['history'] = $this->db->query("SELECT * FROM support where id= '" . $id . "' ")->row();
            $this->data['title'] = "Ticket Management";
            $this->data['card_tilte'] = "Update Ticket";
            $this->data['user_info'] = $this->db->query("SELECT * FROM users where id = '" . $this->data['history']->user_id . "' ")->row();
            if ($this->data['history']->status == '0') {
                $status = "Pending";
            }
            if ($this->data['history']->status == '1') {
                $status = "Open";
            }
            if ($this->data['history']->status == '2') {
                $status = "Close";
            }
            $this->data['status'] = $status;
            $this->data['tikcet_id'] = $id;

            $this->load->view('admin/support/edit_support', $this->data);


        } else {

            $this->session->set_flashdata('danger', 'invalid Support Ticket..');
            redirect('advance-settings');

        }

    }
    /*
   |--------------------------------------------------------------------------
   | Update Ticket
   |--------------------------------------------------------------------------
   */
    public function update()
    {

        $ticket_id = $this->input->post('ticket_id');

        if ($ticket_id != "") {

            $message = array(
                "ticket_id" => $ticket_id,
                "created_date" => date('Y-m-d H:i:s'),
                "user_id" => '0',
                "admin" => '1',
            );


            if ($this->input->post('ticket_message') != "") {
                $message['message'] = $this->input->post('ticket_message');
            }

            if ($_FILES['ticketimage']["name"] != "") {

                if (ENABLE_SITE_UPLOAD_FUNCTION !== true) {
                    $this->_dbg('support_mgnt_upload_images', 'Uploads disabled');
                    return false;
                }

                $tempFile = $_FILES['ticketimage']['tmp_name'];
                $temp = $_FILES["ticketimage"]["name"];
                $targetFile = './assets/images/' . $_FILES["ticketimage"]['name'];
                move_uploaded_file($tempFile, $targetFile);
                $message['files'] = base_url() . "" . $targetFile;
            }

            $this->db->insert('support_message', $message);

            echo json_encode([
                'status' => true,
                'message' => "ticket update successfully"
            ]);
            exit();

        } else {

            echo json_encode([
                'status' => false,
                'message' => "ticket id is null"
            ]);
            exit();

        }

    }
    /*
    |--------------------------------------------------------------------------
    | Update Ticket Status
    |--------------------------------------------------------------------------
    */
    public function update_status($id)
    {

        $ticket_status = $this->input->post('ticket_updated_status');

        $message = array(
            "status" => $ticket_status,
        );

        $ticket = $this->db->get_where('support', ['ticket_id' => $id])->row();
        $user = $this->db->get_where('users', ['id' => $ticket->user_id])->row();
        $uname = $user && $user->username ? $user->username : 'Unkown User';
        $msg = "Hello $uname, Your support ticket (#$id) has been closed. Thank you, Adrox Support Team";

        if ($ticket->status != "2") {

            $this->db->where('ticket_id', $id);
            $this->db->update('support', $message);

            if ($ticket_status == "2") {

                $message = array(
                    "ticket_id" => $id,
                    "created_date" => date('Y-m-d H:i:s'),
                    "user_id" => '0',
                    "admin" => '1',
                    "message" => $msg
                );
                $this->db->insert('support_message', $message);

            }

            echo json_encode([
                'status' => true,
                'message' => "status successfully"
            ]);
            exit();

        } else {

            echo json_encode([
                'status' => false,
                'message' => "Already ticket is closed"
            ]);
            exit();

        }




    }

}