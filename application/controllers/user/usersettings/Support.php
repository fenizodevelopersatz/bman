<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Support extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');

        if ($this->session->userdata('logged_in') && $this->session->userdata('user_login')) {
            $this->lang->load('common', $this->session->userdata('language'));
        } else {
            redirect('user/in');
        }

        // ✅ user must be logged in
        $this->user_id = (int) $this->session->userdata('userid');
        if (!$this->user_id) {
            redirect('user/in');
            exit;
        }


        $language = $this->session->userdata('site_lang') ?? 'english';
        $this->config->set_item('language', $language);
        $this->lang->load('common', $language);
    }
    /*
    |--------------------------------------------------------------------------
    | Index Page
    |--------------------------------------------------------------------------
    */
    // public function index()
    // {

    //     $this->data['title'] = lang('support_title');
    //     $this->data['card_tilte'] = lang('support_card_title');

    //     $userid = $this->session->userdata('userid');
    //     $this->data['user_id'] = $userid;
    //     $query = $this->db->query("
    //     SELECT 
    //     COUNT(*) as all_count,
    //     SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as pending_count,
    //     SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as open_count,
    //     SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as closed_count,
    //     SUM(CASE WHEN DATE(date) = CURDATE() THEN 1 ELSE 0 END) as new_ticket_count
    //     FROM support
    //     where user_id = '" . $userid . "'
    //     ")->row();

    //     $this->data['all_ticket_count'] = $query->all_count ? $query->all_count : 0;
    //     $this->data['pending_ticket_count'] = $query->pending_count ? $query->pending_count : 0;
    //     $this->data['open_ticket_count'] = $query->open_count ? $query->open_count : 0;
    //     $this->data['closed_ticket_count'] = $query->closed_count ? $query->closed_count : 0;
    //     $this->data['new_ticket_count'] = $query->new_ticket_count ? $query->new_ticket_count : 0;
    //     // echo "<pre>";print_r($this->data);exit;
    //     $this->load->view('user/support/list', $this->data);

    // }

    // ✅ Support List Page (your modern UI)
    public function index()
    {
        $userid = (int) $this->session->userdata('userid');

        $this->data['title'] = 'Support Ticket List';
        $this->data['card_tilte'] = 'List of Tickets';
        $this->data['user_id'] = $userid;

        // ✅ counts (based on your `support.status` 0/1/2)
        $row = $this->db->query("
            SELECT 
                COUNT(*) as all_count,
                SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as open_count,
                SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as closed_count,
                SUM(CASE WHEN DATE(date) = CURDATE() THEN 1 ELSE 0 END) as new_ticket_count
            FROM support
            WHERE user_id = ?
        ", [$userid])->row();

        $this->data['all_ticket_count'] = (int) ($row->all_count ?? 0);
        $this->data['pending_ticket_count'] = (int) ($row->pending_count ?? 0);
        $this->data['open_ticket_count'] = (int) ($row->open_count ?? 0);
        $this->data['closed_ticket_count'] = (int) ($row->closed_count ?? 0);
        $this->data['new_ticket_count'] = (int) ($row->new_ticket_count ?? 0);

        // ✅ supportStats object used by your UI
        $this->data['supportStats'] = (object) [
            'open' => $this->data['open_ticket_count'],
            'pending' => $this->data['pending_ticket_count'],
            'closed' => $this->data['closed_ticket_count'],
            'avg_response' => '--' // optional later we can compute
        ];

        // ✅ FAQs from DB
        $this->data['faqs'] = $this->db
            ->select('id, question, answer')
            ->from('faqs')
            ->where('status', 1)
            ->order_by('id', 'DESC')
            ->limit(10)
            ->get()
            ->result();

        // ✅ your view page
        $this->load->view('user/support/list', $this->data);
    }
    /*
    |--------------------------------------------------------------------------
    | Create Ticket
    |--------------------------------------------------------------------------
    */
    public function create()
    {

        $userid = $this->session->userdata('userid');

        if ($this->input->post()) {

            $user_info = $this->db->query("SELECT * FROM users where id= '" . $userid . "' ")->row();
            $discription = $this->input->post('ticket_discription');
            $subject = $this->input->post('ticket_message');
            $email = $user_info->email;
            $user_id = $user_info->id;

            if ($discription != "" && $subject != "" && $email != "") {

                $ticket_id = "TICKET_" . random_string('alnum', 5);

                $update_info = array(
                    "user_id" => $user_id,
                    "discription" => $discription,
                    "ticket_status" => 'Pending',
                    'subject' => $subject,
                    "email" => $email,
                    "date" => date('Y-m-d H:i:s'),
                    "ticket_id" => $ticket_id
                );

                if (!empty($_FILES['ticketimage']['name'])) {

                    if (ENABLE_SITE_UPLOAD_FUNCTION !== true) {
                        $this->_dbg('support_update_ticket_images', 'Uploads disabled');
                        $this->jsonOut(false, 'Uploads disabled', 400);
                        return;
                    }


                    $allowed_extensions = ['jpg', 'jpeg', 'png'];
                    $allowed_mime_types = ['image/jpeg', 'image/png'];

                    $file_name = $_FILES['ticketimage']['name'];
                    $file_tmp = $_FILES['ticketimage']['tmp_name'];
                    $file_size = $_FILES['ticketimage']['size'];
                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    $file_mime = $_FILES['ticketimage']['type'];

                    if (in_array($file_ext, $allowed_extensions) && in_array($file_mime, $allowed_mime_types)) {
                        if ($file_size <= 2 * 1024 * 1024) {
                            $target_directory = './assets/images/support/';
                            if (!is_dir($target_directory)) {
                                @mkdir($target_directory, 0755, true);
                            }
                            $new_file_name = time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $file_name);
                            $target_path = $target_directory . $new_file_name;

                            if (move_uploaded_file($file_tmp, $target_path)) {
                                $update_info['files'] = $new_file_name;
                            } else {
                                // $this->api_response->error('Failed to upload file', 500);
                                $this->jsonOut(false, 'Failed to upload file', 500);
                                return;
                            }
                        } else {
                            // $this->api_response->error('File size exceeds 2MB limit', 400);
                            $this->jsonOut(false, 'File size exceeds 2MB limit', 400);
                            return;
                        }
                    } else {
                        // $this->api_response->error('Invalid file type. Only JPG, PNG, and GIF allowed.', 400);
                        $this->jsonOut(false, 'Invalid file type. Only JPG, PNG, and GIF allowed.', 400);
                        return;
                    }
                }



                $update = $this->db->insert('support', $update_info);


                if ($update) {

                    $updates_info = array(
                        "user_id" => $user_id,
                        "message" => $discription,
                        "ticket_id" => $ticket_id,
                        "created_date" => date('Y-m-d H:i:s'),
                        "admin" => 0
                    );

                    $insert = $this->db->insert('support_message', $updates_info);

                }

                $return_notes = "Ticket Add Successfully";
                $title = "success";

                echo json_encode([
                    'status' => true,
                    'message' => "Ticket Add Successfully"
                ]);
                exit();

            } else {

                echo json_encode([
                    'status' => false,
                    'message' => "Invalide data !!! all fields are required"
                ]);
                exit();
            }




        } else {
            $this->data['title'] = "Create Support Ticket ";
            $this->data['card_tilte'] = "Create Ticket";
            $userid = $this->session->userdata('userid');
            $this->data['user_id'] = $userid;
            $this->load->view('user/support/create-ticket', $this->data);
        }

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
            $user_email = $user_infos->email ? $user_infos->email : " Unkown User";
            $referral_id = $user_infos->referral_id ? $user_infos->referral_id : " Unkown User";

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
        <a class="btn btn-success btn-active-light-success btn-sm  text-center me-3" href="' . base_url() . 'user/view-ticket/' . $user['id'] . '">
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

            $this->load->view('user/support/edit_support', $this->data);


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
                    $this->_dbg('support_update_fn_upload_images', 'Uploads disabled');
                    $this->jsonOut([
                        false,
                        'message' => "Uploads disabled"
                    ]);
                    exit;
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
        $msg = "Hello $user->username, Your support ticket (#$id) has been closed. Thank you, Adrox Support Team";

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




    // ✅ NEW JSON API for this support page (cards)
    // public function tickets_json()
    // {
    //     $userid = (int) $this->session->userdata('userid');
    //     if (!$userid) {
    //         echo json_encode(['ok' => false, 'message' => 'Session expired']);
    //         return;
    //     }

    //     $this->load->model('support/Support_model');

    //     $page = (int) $this->input->get('page');      // 1...
    //     $limit = (int) $this->input->get('limit');     // 5/10
    //     $q = trim((string) $this->input->get('q')); // search
    //     $tab = trim((string) $this->input->get('tab')); // ALL|PENDING|OPEN|CLOSED|NEW_TODAY

    //     if ($page <= 0)
    //         $page = 1;
    //     if ($limit <= 0)
    //         $limit = 5;
    //     if ($limit > 50)
    //         $limit = 50;

    //     $offset = ($page - 1) * $limit;

    //     $total = $this->Support_model->count_cards_for_user($userid, $q, $tab);
    //     $items = $this->Support_model->list_cards_for_user($userid, $limit, $offset, $q, $tab);

    //     echo json_encode([
    //         'ok' => true,
    //         'page' => $page,
    //         'limit' => $limit,
    //         'total' => (int) $total,
    //         'items' => $items,
    //     ]);
    // }

    public function tickets_json()
    {
        $userid = (int) $this->session->userdata('userid');
        if (!$userid) {
            $this->output->set_content_type('application/json')
                ->set_output(json_encode(['ok' => false, 'message' => 'Session expired']));
            return;
        }

        $this->load->model('support/Support_model');

        // ✅ support both "page/limit" and "draw/start/length"
        $draw = (int) $this->input->get('draw');
        $start = (int) $this->input->get('start');
        $length = (int) $this->input->get('length');

        $page = (int) $this->input->get('page');
        $limit = (int) $this->input->get('limit');

        if ($length > 0) {
            $limit = $length;
            $page = (int) floor(($start / max(1, $limit)) + 1);
        }

        if ($page <= 0)
            $page = 1;
        if ($limit <= 0)
            $limit = 5;
        if ($limit > 5)
            $limit = 5;

        $offset = ($page - 1) * $limit;

        // ✅ search: support both q= and datatable search[value]
        $q = trim((string) $this->input->get('q'));
        if ($q === '') {
            $q = trim((string) $this->input->get('search[value]'));
        }

        // ✅ filter: support both tab= and filter_by=
        $tab = trim((string) $this->input->get('tab'));         // ALL|PENDING|OPEN|CLOSED|NEW_TODAY
        $filter_by = (string) $this->input->get('filter_by');   // all_ticket|new_ticket|0|1|2

        if ($tab === '') {
            // map filter_by -> tab
            if ($filter_by === 'all_ticket' || $filter_by === '')
                $tab = 'ALL';
            else if ($filter_by === 'new_ticket')
                $tab = 'NEW_TODAY';
            else if ($filter_by === '0')
                $tab = 'PENDING';
            else if ($filter_by === '1')
                $tab = 'OPEN';
            else if ($filter_by === '2')
                $tab = 'CLOSED';
            else
                $tab = 'ALL';
        }

        $total = $this->Support_model->count_cards_for_user($userid, $q, $tab);
        $items = $this->Support_model->list_cards_for_user($userid, $limit, $offset, $q, $tab);

        // ✅ return BOTH formats (easy for any frontend)
        $out = [
            'ok' => true,
            'draw' => $draw,
            'page' => $page,
            'limit' => $limit,
            'total' => (int) $total,
            'recordsTotal' => (int) $total,
            'recordsFiltered' => (int) $total,
            'items' => $items,
            'data' => $items,
            'tab' => $tab,
            'q' => $q,
        ];

        $this->output->set_content_type('application/json')
            ->set_output(json_encode($out));
    }


    // ✅ optional: FAQs json API (if you want via ajax)
    public function faqs_json()
    {
        $rows = $this->db
            ->select('id, question, answer')
            ->from('faqs')
            ->where('status', 1)
            ->order_by('id', 'DESC')
            ->limit(20)
            ->get()
            ->result_array();

        echo json_encode(['ok' => true, 'items' => $rows]);
    }

    /* ---------------------------------------------------------
     | AJAX List API (returns RAW items[])
     * --------------------------------------------------------- */
    public function list_ajax()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $draw = (int) $this->input->get('draw');
        $start = max(0, (int) $this->input->get('start'));
        $length = (int) $this->input->get('length');
        if ($length <= 0 || $length > 100)
            $length = 10;

        // ✅ filter_by values:
        // all_ticket | 0 | 1 | 2 | new_ticket
        $filter_by = $this->input->get('filter_by', true);
        $q = trim((string) $this->input->get('q', true)); // search text

        $total_records = $this->Support_model->get_user_ticket_count($this->user_id, $filter_by, $q);
        $items = $this->Support_model->get_user_tickets($this->user_id, $filter_by, $q, $length, $start);

        $response = [
            'draw' => $draw,
            'recordsTotal' => $total_records,
            'recordsFiltered' => $total_records,
            'items' => $items, // ✅ raw tickets
        ];

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }

    public function ticket_view_api()
    {
        $userid = (int) $this->session->userdata('userid');
        if (!$userid) {
            $this->output->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Unauthorized']));
            return;
        }

        $id = (int) $this->input->get('id');
        if (!$id) {
            $this->output->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Invalid ticket id']));
            return;
        }

        // ticket must belong to this user
        $ticket = $this->db->where('id', $id)->where('user_id', $userid)->get('support')->row_array();
        if (!$ticket) {
            $this->output->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Ticket not found']));
            return;
        }

        // messages by ticket_id (your table uses ticket_id string)
        $msgs = $this->db->where('ticket_id', $ticket['ticket_id'])
            ->order_by('created_date', 'ASC')
            ->get('support_message')->result_array();

        $ticket['messages'] = $msgs;

        // attachment url (if file exists)
        $ticket['file_url'] = !empty($ticket['files'])
            ? base_url('assets/images/support/' . $ticket['files'])
            : '';

        $this->output->set_content_type('application/json')
            ->set_output(json_encode(['status' => true, 'data' => $ticket]));
    }

    private function jsonOut($status, $message, $code = 200, $extra = [])
    {
        $payload = array_merge([
            'status' => (bool) $status,
            'message' => (string) $message
        ], $extra);

        $this->output
            ->set_status_header($code)
            ->set_content_type('application/json')
            ->set_output(json_encode($payload));
    }

}