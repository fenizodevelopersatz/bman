<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Withdraw extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');
        $this->load->model('Admin_model');
        // $this->load->model('Mlm_service_model');
        // $this->load->model(['Mlm_service_model' => 'msm', 'member/Users_model' => 'um']);
        $this->load->model('member/Users_model', 'soft_delete');
        $this->load->library('upload');

        // if (!$this->session->userdata('admin_logged_in')) {
        //     redirect('admin/login');
        // }

        // if ($this->session->userdata('admin_logged_in') && $this->session->userdata('admin_logged_in')) {
        //     if ($this->input->is_ajax_request()) {
        //         echo json_encode(['status' => false, 'redirect' => base_url('admin/main')]);
        //         exit;
        //     }
        // }


        // $user = $this->Admin_model->get_user($this->session->userdata('admin_userid'));

        // if ($user->admin_roll == '1') {
        //     $permissions = json_decode($user->permission_pages, true);
        //     if (empty($permissions['member_management'])) {
        //         $this->session->set_flashdata('error', 'Access Denied: You do not have permission.');
        //         redirect('admin');
        //     }
        // }

        $this->load->model('member/Users_model');
        $this->load->model('member/Mlm_model');
        $this->load->model('member/BinaryModel');
        $this->load->model("member/Mlm_model", "em");
        $this->load->model('Withdraw_model');
    }
    /*
    |--------------------------------------------------------------------------
    | Index Page
    |--------------------------------------------------------------------------
    */
    public function index()
    {


        if (!$this->session->userdata('admin_logged_in')) {
            redirect('admin/login');
        }

        $this->data['title'] = "All Withdraw List ";
        $this->data['card_tilte'] = "Withdraw List";
        $this->load->model('admin/DashboardStats_model', 'dashstats');
        $this->dashstats->markSeen($this->session->userdata('admin_userid'), 'withdrawals');
        $this->load->view('admin/withdraw/list', $this->data);
    }
    /*
    |--------------------------------------------------------------------------
    | Image Generate
    |--------------------------------------------------------------------------
    */
    public function image_generate()
    {

        $json_data = file_get_contents('php://input');
        $request_data = json_decode($json_data, true);
        $image_code = $this->request_data['image_code'];
        $create_image = $this->Mlm_model->online_image_generate($image_code);
        echo json_encode($create_image);

    }
    /*
    |--------------------------------------------------------------------------
    | list Page
    |--------------------------------------------------------------------------
    */
    public function list()
    {
        $draw = $this->input->get('draw');
        $start = $this->input->get('start');
        $length = $this->input->get('length');
        $sponsor_id = $this->input->get('sponsor_id');
        $from_date = $this->input->get('from_date') ? date('Y-m-d', strtotime($this->input->get('from_date'))) : '';
        $to_date = $this->input->get('to_date') ? date('Y-m-d', strtotime($this->input->get('to_date'))) : '';

        $data = [];

        $this->load->model('withdraw/Bmanwithdraw_model', 'bmanwithdraw');

        // Total records
        $total_records = $this->db->count_all('bman_withdraw_requests');

        // Filtered query (built first so the same WHERE clauses drive both the
        // filtered count and the paginated data — the old code always reported
        // recordsFiltered = recordsTotal, which broke DataTables pagination
        // whenever a filter was applied)
        $this->db->from('bman_withdraw_requests wr');
        $this->db->join('users u', 'wr.user_id = u.id', 'left');

        if ($from_date)
            $this->db->where('DATE(wr.created_at) >=', $from_date);

        if ($to_date)
            $this->db->where('DATE(wr.created_at) <=', $to_date);

        if ($sponsor_id)
            $this->db->where('u.id', $sponsor_id);

        $filtered_records = $this->db->count_all_results('', false);

        $this->db->select('wr.*, u.referral_id, u.email');
        $this->db->order_by('wr.id', 'DESC');

        if ($length != -1)
            $this->db->limit($length, $start);

        $requests = $this->db->get()->result_array();

        // Batch-fetch wallet allocations for the returned requests (avoids N+1)
        $ids = array_column($requests, 'id');
        $allocations_by_request = [];
        if ($ids) {
            $alloc_rows = $this->db->select('request_id, wallet, amount')
                ->from('bman_withdraw_allocations')
                ->where_in('request_id', $ids)
                ->get()->result_array();
            foreach ($alloc_rows as $a) {
                $allocations_by_request[$a['request_id']][] = $a;
            }
        }

        $i = $start;

        foreach ($requests as $req) {
            $i++;

            // Wallet details: destination address + which wallet(s) it was locked from
            $wallet_details = '<strong>To:</strong> ' . htmlspecialchars($req['withdraw_address']) . '<br>';
            $allocations = $allocations_by_request[$req['id']] ?? [];
            if ($allocations) {
                $parts = [];
                foreach ($allocations as $a) {
                    $parts[] = ucfirst($a['wallet']) . ' ' . number_format((float) $a['amount'], 4);
                }
                $wallet_details .= '<strong>From:</strong> ' . implode(', ', $parts);
            } else {
                $wallet_details .= '<strong>From:</strong> ' . ucfirst($req['source_wallet']);
            }

            // Status badge (pending/approved/processing/completed/rejected/failed)
            switch ($req['status']) {
                case 'pending':
                    $status_badge = '<span class="badge bg-warning">Pending</span>';
                    break;
                case 'approved':
                    $status_badge = '<span class="badge bg-info">Approved</span>';
                    break;
                case 'processing':
                    $status_badge = '<span class="badge bg-primary">Processing</span>';
                    break;
                case 'completed':
                    $status_badge = '<span class="badge bg-success">Completed</span>';
                    break;
                case 'rejected':
                    $status_badge = '<span class="badge bg-danger">Rejected</span>';
                    break;
                case 'failed':
                    $status_badge = '<span class="badge bg-danger">Failed</span>';
                    break;
                default:
                    $status_badge = '<span class="badge bg-secondary">Unknown</span>';
            }

            // Action
            $view_url = base_url('admin/bman-withdrawals/view/' . $req['id']);
            $action = '<a class="btn btn-success btn-sm" href="' . $view_url . '">
                     <i class="fa fa-eye"></i> View
                   </a>';

            $data[] = [
                'RecordID' => $i,
                'UserInfo' => '<strong>' . ($req['referral_id'] ?? '-') . '</strong><br>' . ($req['email'] ?? '-'),
                'Balance' => number_format($this->bmanwithdraw->available_balance($req['user_id']), 4) . ' BMAN',
                'Wallet Details' => $wallet_details,
                'Amount' => number_format((float) $req['request_amount'], 4) . ' BMAN' .
                    '<br><small>' . date('d-m-Y H:i', strtotime($req['created_at'])) . '</small>',
                'Status' => $status_badge,
                'Approved At' => $req['approved_at'] ? date('d-m-Y H:i', strtotime($req['approved_at'])) : '-',
                'Action' => $action
            ];
        }

        echo json_encode([
            'draw' => intval($draw),
            'recordsTotal' => $total_records,
            'recordsFiltered' => $filtered_records,
            'data' => $data
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Add User
    |--------------------------------------------------------------------------
    */
    public function add_user()
    {

        $this->data['title'] = 'Add User';
        $this->data['card_title'] = 'Add Users';
        $this->data['action'] = base_url() . 'create-user';
        $this->data['redirect'] = base_url() . 'network-list';
        $this->data['package_list'] = $this->Mlm_model->get_packages();
        // $this->data['users'] = $this->db->query("SELECT * FROM users where status = '1' ")->result();
        $this->data['users'] = [];
        $this->load->view('admin/member/create-member', $this->data);

    }

    public function search_sponsor()
    {
        $q = $this->input->get('q'); // Query from Select2

        $this->db->select('id, username, referral_id');
        $this->db->from('users');
        $this->db->where('status', 1);

        if ($q) {
            $this->db->like('username', $q);
            $this->db->or_like('referral_id', $q);
        }

        $query = $this->db->get();
        $results = [];

        foreach ($query->result() as $row) {
            $results[] = [
                'id' => $row->id,
                'text' => $row->username . " (" . $row->referral_id . ")"
            ];
        }

        echo json_encode(['results' => $results]);
    }

    /*
    |--------------------------------------------------------------------------
    | Add User Post Method
    |--------------------------------------------------------------------------
    */
    public function create_user()
    {


        $sponsor_id = $this->input->post('sponsor_id');
        $username = $this->input->post('username');
        $email = $this->input->post('useremail');
        $sponser_leg = $this->input->post('select_lg');
        $package_id = $this->input->post('package_id');
        $epin_id = $epin = $this->input->post('epin_id');
        $password = $this->password_create();

        if ($this->Mlm_model->usernameExists($username)) {
            echo json_encode(["status" => "error", "message" => "Username already taken"]);
            exit();
        }

        // if ($this->Mlm_model->sponserExists($sponsor_id)) {
        //     echo json_encode(["status" => false, "message" => "Invalid sponsor id!."]);
        //     exit();
        // }

        if ($this->Mlm_model->usernameExists($username)) {
            echo json_encode(["status" => false, "message" => "Username already taken"]);
            exit();
        }

        if ($this->Mlm_model->emailExists($email)) {
            echo json_encode(["status" => false, "message" => "Email already taken"]);
            exit();
        }



        if ($this->Mlm_model->epinExists($epin_id)) {
            echo json_encode(["status" => false, "message" => "Epin is already taken"]);
            exit();
        }

        $package_id = $this->input->post('package_id', true) ?: 1;
        $package_arr = $this->db
            ->select('minimum, id')
            ->get_where('package_config', ['id' => $package_id])
            ->row();

        if (!$package_arr) {
            return;
        }

        // $epin_values = $this->db->get_where('epin_generate', ['id' => $epin])->row('epin');


        // if (!$this->Mlm_model->epinValid($epin_values)) {
        //     echo json_encode(["status" => false, "message" => "No valid epin"]);
        //     exit();
        // }


        $package_amount = $package_arr->minimum * 100;
        $package_id = $package_arr->id;

        // Example data to insert
        $data = [
            'user_id' => 1,
            'parent_id' => 1,
            'sponsor_id' => 1,
            'level' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ];

        // Check if a record exists
        $this->db->where('user_id', $data['user_id']);
        $query = $this->db->get('mlm5_placement');

        if ($query->num_rows() == 0) {
            // No record found, insert new
            $res = $this->Mlm_service_model->create_member_and_position($data['user_id'], $data['sponsor_id'], $package_amount, $package_id);
        }

        $user_id = $this->Mlm_model->registerUser($username, $email, $epin_id, $sponsor_id, $sponser_leg, $password);
        $res = $this->Mlm_service_model->create_member_and_position($user_id, $sponsor_id, $package_amount, $package_id);
        if ($user_id && $res) {

            $this->db->where('id', $user_id)->update('users', ['package_id' => $package_id, 'epin_id' => $epin]);
            $this->db->where('epin', $epin)->update('epin_generate', ['status' => '3']);
            echo json_encode(["status" => "success", "message" => "User registered successfully"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Registration failed"]);
        }

        exit();

    }
    /*
    |--------------------------------------------------------------------------
    |  User Passwrod create
    |--------------------------------------------------------------------------
    */
    public function password_create()
    {

        $uppercase = chr(rand(65, 90));              // A-Z
        $lowercase = chr(rand(97, 122));             // a-z
        $number = chr(rand(48, 57));              // 0-9
        $special = chr(rand(33, 47));              // Special chars like ! " # $ etc.

        $remaining = '';
        $all = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()';

        for ($i = 0; $i < 4; $i++) {
            $remaining .= $all[rand(0, strlen($all) - 1)];
        }

        $passwordArray = str_split($uppercase . $lowercase . $number . $special . $remaining);
        shuffle($passwordArray);
        $password = implode('', $passwordArray);


        return $password;
    }
    /*
    |--------------------------------------------------------------------------
    | Add User Genealoy
    |--------------------------------------------------------------------------
    */
    public function genealogy($user_id)
    {
        $this->data['title'] = "Members Genealogy ";
        $this->data['card_title'] = "Genealogy List";
        $this->data['user_id'] = $user_id;
        $this->load->view('admin/member/genealogy_view', $this->data);
    }
    /*
    |--------------------------------------------------------------------------
    | View Genealoy
    |--------------------------------------------------------------------------
    */
    // public function getTreeData($user_id) {
    //     // $members = $this->BinaryModel->getDownlineMembers($user_id);
    //     // echo json_encode($members);

    //     $members = [
    //             [
    //                 "id" => 1,
    //                 "mid" => null,
    //                 "name" => "yadu",
    //                 "email" => "admin@gmail.com",
    //                 "position" => 1,
    //                 "level" => 0,
    //                 "register_date" => "2025-12-16"
    //             ],
    //             [
    //                 "id" => 2,
    //                 "mid" => 1,
    //                 "name" => "user2",
    //                 "email" => "u2@gmail.com",
    //                 "position" => 1,
    //                 "level" => 1,
    //                 "register_date" => "2025-12-16"
    //             ],
    //             [
    //                 "id" => 3,
    //                 "mid" => 1,
    //                 "name" => "user3",
    //                 "email" => "u3@gmail.com",
    //                 "position" => 2,
    //                 "level" => 1
    //             ],
    //             [
    //                 "id" => 5,
    //                 "mid" => 2,
    //                 "name" => "user5",
    //                 "email" => "u5@gmail.com",
    //                 "position" => 1,
    //                 "level" => 2
    //             ],

    //              [
    //                 "id" => 4,
    //                 "mid" => 2,
    //                 "name" => "user4",
    //                 "email" => "u4@gmail.com",
    //                 "position" => 2,
    //                 "level" => 2
    //             ],

    //              [
    //                 "id" => 7,
    //                 "mid" => 2,
    //                 "name" => "user7",
    //                 "email" => "u7@gmail.com",
    //                 "position" => 2,
    //                 "level" => 2
    //             ],
    //             [
    //                 "id" => 6,
    //                 "mid" => 5,
    //                 "name" => "user6",
    //                 "email" => "u6@gmail.com",
    //                 "position" => 1,
    //                 "level" => 3
    //             ],
    //              [
    //                 "id" => 4,
    //                 "mid" => 2,
    //                 "name" => "user4",
    //                 "email" => "u4@gmail.com",
    //                 "position" => 2,
    //                 "level" => 2
    //             ],
    //              [
    //                 "id" => 8,
    //                 "mid" => 2,
    //                 "name" => "user8",
    //                 "email" => "u8@gmail.com",
    //                 "position" => 2,
    //                 "level" => 1
    //             ], [
    //                 "id" => 9,
    //                 "mid" => 7,
    //                 "name" => "user9",
    //                 "email" => "u9@gmail.com",
    //                 "position" => 1,
    //                 "level" => 2
    //             ], [
    //                 "id" => 10,
    //                 "mid" => 7,
    //                 "name" => "user10",
    //                 "email" => "u10@gmail.com",
    //                 "position" => 2,
    //                 "level" => 2
    //             ],
    //             [
    //                 "id" => 11,
    //                 "mid" => 7,
    //                 "name" => "user11",
    //                 "email" => "u11@gmail.com",
    //                 "position" => 2,
    //                 "level" => 2
    //             ],
    //             [
    //                 "id" => 12,
    //                 "mid" => 7,
    //                 "name" => "user12",
    //                 "email" => "u12@gmail.com",
    //                 "position" => 2,
    //                 "level" => 2
    //             ],
    //             [
    //                 "id" => 13,
    //                 "mid" => 7,
    //                 "name" => "user13",
    //                 "email" => "u13@gmail.com",
    //                 "position" => 2,
    //                 "level" => 2
    //             ],

    //         ];

    //         echo json_encode($members);
    // }

    function getTreeData($rootUserId, $maxLevel = 20)
    {
        $this->db->select(
            'p.user_id,
            p.parent_id,
            p.position,
            p.level,
            p.created_at,
            u.name,
            u.email,
            u.image',
        );

        $this->db->from('mlm5_placement as p');
        $this->db->join('users as u', 'u.id = p.user_id'); // ✅ correct CI join
        $this->db->where('p.level <=', $maxLevel);
        // $this->db->order_by('p.position', 'ASC');

        $placements = $this->db->get()->result();

        $result = [];

        foreach ($placements as $row) {

            if ($row->user_id == $rootUserId || $row->parent_id) {
                $result[] = [
                    "id" => (int) $row->user_id,
                    "mid" => $row->parent_id ? (int) $row->parent_id : null,
                    "name" => $row->name,
                    "email" => $row->email,
                    "position" => (int) $row->position,
                    "level" => (int) $row->level,
                    "register_date" => date('Y-m-d', strtotime($row->created_at)),
                    "photo" => $row->image ? base_url('assets/images/' . $row->image) : DEFAULTAVATARIMAGE,
                ];
            }
        }

        echo json_encode($result, JSON_PRETTY_PRINT);
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | View User Details
    |--------------------------------------------------------------------------
    */
    public function viewuser($id)
    {
        $this->data['title'] = "Withdrawal Requests";
        $this->data['card_title'] = "User Withdrawal Requests";

        // Fetch withdrawal request
        $withdraw_request = $this->db->where('id', $id)->get('withdrawals')->row();
        if (!$withdraw_request)
            show_404();

        $uid = $withdraw_request->user_id;

        // Fetch user bank/payment details
        $payment_request = $this->db->where('user_id', $uid)->get('user_bank')->row_array();

        // Map payment details with defaults
        $payment_details = [
            'account_holder' => $payment_request['holder_name'] ?? '-',
            'account_number' => $payment_request['account_number'] ?? '-',
            'branch' => $payment_request['bank_name'] ?? '-',
            'ifsc_code' => $payment_request['ifsc'] ?? '-',
            'upi_id' => $payment_request['upi_id'] ?? '-',
        ];

        // Fetch user info
        $user = $this->db->get_where('users', ['id' => $uid])->row();
        if (!$user)
            show_404();

        // Default avatar
        if (!defined('DEFAULTAVATARIMAGE')) {
            define('DEFAULTAVATARIMAGE', base_url('assets/images/default_avatar.png'));
        }

        // Prepare data array for view
        $this->data['withdraw'] = [
            'id' => $withdraw_request->id,

            'amount' => number_format((float) ($withdraw_request->amount ?? 0), 2),
            'fee' => number_format((float) ($withdraw_request->fee ?? 0), 2),
            'net_amount' => number_format((float) ($withdraw_request->net_amount ?? 0), 2),

            'status' => ucfirst($withdraw_request->status ?? '-'),

            'created_at' => !empty($withdraw_request->created_at)
                ? date('d M Y H:i', strtotime($withdraw_request->created_at))
                : '-',

            'approved_by' => $withdraw_request->approved_by ?? '-',

            'approved_at' => !empty($withdraw_request->approved_at)
                ? date('d M Y H:i', strtotime($withdraw_request->approved_at))
                : '-',

            'payment_details' => $payment_details,

            'user' => [
                'username' => $user->username ?? '-',
                'email' => $user->email ?? '-',
                'photo' => !empty($user->image)
                    ? base_url('assets/images/' . $user->image)
                    : DEFAULTAVATARIMAGE
            ]
        ];

        $this->load->view('admin/withdraw/view', $this->data);
    }


    public function viewuserinfo($id)
    {

        /******* BASIC INFO ***********/
        $userinfo = $this->db->query("SELECT * FROM users where id = '" . $id . "'")->row();
        $sponser_info = $this->db->query("SELECT * FROM users where id = '" . $userinfo->sponser . "'")->row();

        /******* Investment INFO ***********/
        $binary_info = $this->BinaryModel->calculateLegInvestments($id);

        $left_leg_count = count($binary_info['left_leg_users']);
        $right_leg_count = count($binary_info['right_leg_users']);

        $left_leg_investment = $binary_info['left_leg_investment'];
        $right_leg_investment = $binary_info['right_leg_investment'];
        $my_investment = $binary_info['my_investment'];
        $left_leg_investment_token = $binary_info['left_investment_token'];
        $right_leg_investment_token = $binary_info['right_investment_token'];
        $my_investment_token = $binary_info['my_investment_token'];

        $this->db->where('member_id', $id);
        $query = $this->db->get('member_stats')->row('total_downline_count');

        /******* Earnings INFO ***********/
        $binary_site_currency = $this->db->query("SELECT sum(amount) as binary_site_amt FROM history where type = 'binary_commission' ")->row()->binary_site_amt;
        $binary_token_currency = $this->db->query("SELECT sum(token_amount) as binary_token_amt FROM history where type = 'binary_commission' ")->row()->binary_token_amt;
        $roi_site_currency = $this->db->query("SELECT sum(amount) as roi_site_amt FROM history where type = 'profit' ")->row()->roi_site_amt;
        $roi_token_currency = $this->db->query("SELECT sum(token_amount) as roi_token_amt FROM history where type = 'profit' ")->row()->roi_token_amt;
        $direct_site_currency = $this->db->query("SELECT sum(amount) as direc_site_amt FROM history where type = 'direct_commission' ")->row()->direc_site_amt;
        $direct_token_currency = $this->db->query("SELECT sum(token_amount) as direc_token_amt FROM history where type = 'direct_commission' ")->row()->direc_token_amt;


        $userinfo = array(
            "name" => $userinfo->username,
            "email" => $userinfo->email,
            "register_date" => $userinfo->register_date,
            "referral_id" => $userinfo->referral_id,
            "sponser" => $sponser_info ? $sponser_info->email . " ( " . $sponser_info->referral_id . " )" : '',
            "my_investment" => currency_format($my_investment),
            "left_leg_count" => $left_leg_count,
            "right_leg_count" => $right_leg_count,
            "left_leg_investment" => currency_format($left_leg_investment),
            "right_leg_investment" => currency_format($right_leg_investment),
            'left_leg_investment_token' => token_format($left_leg_investment_token),
            'right_leg_investment_token' => token_format($right_leg_investment_token),
            'my_investment_token' => token_format($my_investment_token),
            'binary_site_currency' => $binary_site_currency,
            'binary_token_currency' => $binary_token_currency,
            'roi_token_currency' => $roi_token_currency,
            'direct_site_currency' => $direct_site_currency,
            'direct_token_currency' => $direct_token_currency,
            'total_downline_count' => $query ?? 0
        );

        $return = array(
            'result' => true,
            'data' => $userinfo
        );

        echo json_encode($return);

    }
    /*
   |--------------------------------------------------------------------------
   | STATUS Update
   |--------------------------------------------------------------------------
   */


    public function validate_proof_image()
    {
        if (empty($_FILES['proof_image']['name'])) {
            $this->form_validation->set_message(
                'validate_proof_image',
                'Proof image is required'
            );
            return false;
            exit;
        }

        if ($_FILES['proof_image']['size'] > 2097152) {
            $this->form_validation->set_message(
                'validate_proof_image',
                'Image size must be below 2MB'
            );
            return false;
            exit;
        }

        $allowed = ['image/jpeg', 'image/png', 'image/jpg'];
        $mime = mime_content_type($_FILES['proof_image']['tmp_name']);

        if (!in_array($mime, $allowed)) {
            $this->form_validation->set_message(
                'validate_receipt_image',
                'Only JPG, JPEG, PNG images are allowed'
            );
            return false;
            exit;
        }

        return true;
    }

    // public function update($id)
    // {
    //     if (!$id) {
    //         echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
    //         return;
    //     }

    //     try {
    //         // Load form validation library
    //         $this->load->library('form_validation');

    //         // Set validation rules
    //         $this->form_validation->set_rules('review', 'Review', '');
    //         $this->form_validation->set_rules('status', 'Status', 'required|in_list[pending,approved,rejected]');
    //         if (empty($_FILES['withdraw_proof']['name'])) {
    //             $this->form_validation->set_rules('withdraw_proof', 'Proof Image', 'callback_validate_proof_image');
    //         }

    //         // Check validation
    //         if ($this->form_validation->run() == FALSE) {
    //             $errors = validation_errors();
    //             echo json_encode(['status' => 'error', 'message' => strip_tags($errors)]);
    //             return;
    //         }

    //         // Handle file upload if file is provided
    //         $proof_file = null;
    //         if (!empty($_FILES['withdraw_proof']['name'])) {

    //             $upload_path = './uploads/withdraw_proof/';

    //             // Create folder if it doesn't exist
    //             if (!is_dir($upload_path)) {
    //                 mkdir($upload_path, 0755, true); // recursive mkdir with proper permissions
    //             }

    //             $config['upload_path'] = $upload_path;
    //             $config['allowed_types'] = 'jpg|jpeg|png';
    //             $config['max_size'] = 2048; // 2MB
    //             $config['file_name'] = 'withdraw_' . time() . '_' . rand(1000, 9999);
    //             $config['overwrite'] = false;

    //             // Load and re-initialize upload library
    //             $this->load->library('upload');
    //             $this->upload->initialize($config);

    //             if (!$this->upload->do_upload('withdraw_proof')) {
    //                 throw new Exception(strip_tags($this->upload->display_errors()));
    //             }

    //             $file_data = $this->upload->data();
    //             $proof_file = $file_data['file_name'];
    //         }

    //         // Prepare update data
    //         $data = [
    //             'admin_review' => $this->input->post('review', true),
    //             'status' => $this->input->post('status', true),
    //             'approved_at' => date('Y-m-d H:i:s'),
    //             'approved_by' => $this->session->userdata('admin_userid') ?? $this->session->userdata('user_get_id')
    //         ];

    //         if ($proof_file) {
    //             $data['admin_proof_img'] = $proof_file;
    //         }

    //         // Update withdraw request
    //         $this->db->where('id', $id)->update('withdrawals', $data);

    //         echo json_encode(['status' => 'success', 'message' => 'Withdraw request updated successfully.']);

    //     } catch (Exception $e) {
    //         echo json_encode(['status' => 'error', 'message' => strip_tags($e->getMessage())]);
    //     }
    // }

    /**
     * On-chain payout lifecycle for a withdrawal (fail-safe).
     *  - approved + a pasted `tx_hash` → store it on `withdrawals`, create a
     *    `withdrawal` row in onchain_transactions, verify it against the chain
     *    (status/confirmations/gas), and link both. Status flows
     *    broadcasting → pending → confirmed / reverted.
     *  - rejected → mark chain_status = cancelled.
     * The user balance is debited at request time and refunded-once on reject
     * (existing admin_txn_id guard) — this method never debits.
     */
    private function _recordWithdrawOnchain($id, $withdraw, $new_status)
    {
        try {
            $this->load->model('Onchaintx_model', 'octx');
            $adminId = (int) $this->session->userdata('admin_userid');
            $txHash  = $this->input->post('tx_hash', true);

            if ($new_status === 'approved' && $txHash) {
                $ts = $this->db->select('explorer_url,chain_id,usdt_contract')
                               ->get_where('token_settings', ['status' => 1])->row_array() ?: [];
                $explorer = rtrim($ts['explorer_url'] ?? 'https://bscscan.com', '/');

                $txId = $this->octx->capture([
                    'tx_hash'        => $txHash,
                    'network'        => 'mainnet',
                    'chain_id'       => (int)($ts['chain_id'] ?? 56),
                    'wallet_type'    => 'usdt',
                    'tx_type'        => 'withdrawal',
                    'status'         => 'processing',
                    'user_id'        => (int)$withdraw->user_id,
                    'admin_id'       => $adminId,
                    'token_symbol'   => 'USDT',
                    'token_contract' => $ts['usdt_contract'] ?? null,
                    'token_decimals' => 18,
                    'amount'         => (float)$withdraw->amount,
                    'debit_wallet'   => 'usdt',
                    'reference_type' => 'withdrawal',
                    'reference_id'   => (string)$id,
                    'created_by'     => $adminId,
                    'ip_address'     => $this->input->ip_address(),
                    '_event'         => ['actor_type'=>'admin','actor_id'=>$adminId,
                                         'ip_address'=>$this->input->ip_address(),'detail'=>'withdrawal payout broadcast'],
                ]);

                $this->db->where('id', $id)->update('withdrawals', [
                    'tx_hash'      => $txHash,
                    'chain_status' => 'broadcasting',
                    'broadcast_at' => date('Y-m-d H:i:s'),
                    'explorer_url' => $explorer.'/tx/'.$txHash,
                    'onchain_tx_id'=> $txId ?: null,
                ]);

                // Verify immediately against the chain.
                if ($txId) {
                    $this->load->model('Chainsync_model', 'chain');
                    $res = $this->chain->verifyTx($txId);
                    $st  = $res['status'] ?? 'pending';
                    $this->db->where('id', $id)->update('withdrawals', [
                        'chain_status'  => $st === 'confirmed' ? 'confirmed' : ($st === 'reverted' ? 'reverted' : 'pending'),
                        'block_number'  => $res['block'] ?? null,
                        'confirmations' => $res['confirmations'] ?? null,
                    ]);
                }
            } elseif ($new_status === 'rejected') {
                $this->db->where('id', $id)->update('withdrawals', ['chain_status' => 'cancelled']);
            }
        } catch (Throwable $e) {
            log_message('error', '[withdraw onchain] ' . $e->getMessage());
        }
    }

    public function update($id)
    {
        if (!$id) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
            return;
        }

        try {
            $this->load->library('form_validation');

            // ===== Validation rules =====
            $this->form_validation->set_rules('review', 'Review', '');
            $this->form_validation->set_rules(
                'status',
                'Status',
                'required|in_list[pending,approved,rejected]'
            );

            if ($this->form_validation->run() == FALSE) {
                echo json_encode([
                    'status' => 'error',
                    'message' => strip_tags(validation_errors())
                ]);
                return;
            }

            // ===== Fetch withdraw request =====
            $withdraw = $this->db->get_where('withdrawals', ['id' => $id])->row();
            if (!$withdraw) {
                throw new Exception('Withdraw request not found.');
            }

            $new_status = $this->input->post('status', true);
            $tx_hash    = trim((string) $this->input->post('tx_hash', true));

            // ===== Approve requires a valid, unique on-chain payout tx hash =====
            if ($new_status === 'approved' && empty($withdraw->tx_hash)) {
                if ($tx_hash === '') {
                    echo json_encode(['status' => 'error', 'message' => 'A payout transaction hash is required to approve this withdrawal.']);
                    return;
                }
                if (!preg_match('/^0x[0-9a-fA-F]{64}$/', $tx_hash)) {
                    echo json_encode(['status' => 'error', 'message' => 'Invalid transaction hash format (expected 0x followed by 64 hex characters).']);
                    return;
                }
                $dupW = $this->db->where('tx_hash', $tx_hash)->where('id !=', $id)->count_all_results('withdrawals');
                $dupO = $this->db->where('tx_hash', $tx_hash)
                                 ->where('reference_type', 'withdrawal')
                                 ->where('reference_id !=', (string)$id)
                                 ->count_all_results('onchain_transactions');
                if ($dupW > 0 || $dupO > 0) {
                    echo json_encode(['status' => 'error', 'message' => 'This transaction hash is already recorded for another withdrawal.']);
                    return;
                }
            }

            // ===== Upload proof (optional) =====
            $proof_file = null;
            if (!empty($_FILES['withdraw_proof']['name'])) {

                if (ENABLE_SITE_UPLOAD_FUNCTION !== true) {
                    $this->_dbg('withdraw_proof_upload_images', 'Uploads disabled');
                    return false;
                }

                $upload_path = './uploads/withdraw_proof/';
                if (!is_dir($upload_path)) {
                    mkdir($upload_path, 0755, true);
                }

                $config = [
                    'upload_path' => $upload_path,
                    'allowed_types' => 'jpg|jpeg|png',
                    'max_size' => 2048,
                    'file_name' => 'withdraw_' . time() . '_' . rand(1000, 9999),
                    'overwrite' => false
                ];

                $this->load->library('upload');
                $this->upload->initialize($config);

                if (!$this->upload->do_upload('withdraw_proof')) {
                    throw new Exception(strip_tags($this->upload->display_errors()));
                }

                $proof_file = $this->upload->data('file_name');
            }

            // ===== Refund if REJECTED and not refunded before =====
            if (
                $new_status === 'rejected' &&
                empty($withdraw->admin_txn_id)
            ) {
                $amount = (float) $withdraw->amount;
                $userid = (int) $withdraw->user_id;

                // Insert refund into history
                $transaction_id = 'WD-REFUND-' . time() . '-' . $userid;
                $data = [
                    'user_id' => $userid,
                    'amount' => $amount,
                    'type' => 'withdraw_refund', // earning type
                    'date' => date('Y-m-d H:i:s'),
                    'history_date' => date('Y-m-d H:i:s'),
                    'status' => '1',
                    'coin_type' => '1',
                    'hash_id' => $transaction_id,
                    'description' => 'Withdraw rejected – amount refunded',
                ];
                $this->db->insert('history', $data);

                // Optionally, use your _detact_currency_history function if needed
                // $this->_detact_currency_history($userid, $amount, 'Withdraw rejected – amount refunded', $transaction_id);

                // Save transaction ID in withdrawals table to prevent double refund
                $this->db->where('id', $id)->update('withdrawals', [
                    'admin_txn_id' => $transaction_id
                ]);
            }

            // ===== Update withdraw request =====
            $update = [
                'admin_review' => $this->input->post('review', true),
                'status' => $new_status,
                'approved_at' => date('Y-m-d H:i:s'),
                'approved_by' => $this->session->userdata('admin_userid')
            ];

            if ($proof_file) {
                $update['admin_proof_img'] = $proof_file;
            }

            $this->db->where('id', $id)->update('withdrawals', $update);

            // Record the on-chain payout lifecycle (fail-safe — never blocks the update).
            $this->_recordWithdrawOnchain($id, $withdraw, $new_status);

            echo json_encode([
                'status' => 'success',
                'message' => 'Withdraw request updated successfully.'
            ]);

        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => strip_tags($e->getMessage())
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE USER
    |--------------------------------------------------------------------------
    */

    public function deletebyuser()
    {
        $id = $this->input->post('id');
        if ($id) {
            $check_user = $this->db->query("SELECT * FROM `users` WHERE id = '" . $id . "'")->num_rows();

            if ($check_user > 0) {


                $user_id = $this->session->userdata('admin_userid') ?? $this->session->userdata('user_get_id');
                if ($this->um->soft_delete($id, $user_id)) {
                    echo json_encode([
                        'status' => true,
                        'message' => 'User deleted successfully'
                    ]);
                    exit;
                } else {
                    echo json_encode([
                        'status' => false,
                        'message' => 'Failed to delete user'
                    ]);
                    exit;
                }

            }
        }

        echo json_encode([
            'status' => false,
            'message' => 'User not found'
        ]);
    }

    public function deleteuserbyadmin($id)
    {
        if ($id) {
            $check_user = $this->db->query("SELECT * FROM `users` WHERE id = '" . $id . "'")->num_rows();

            if ($check_user > 0) {

                $user_id = $this->session->userdata('admin_userid') ?? $this->session->userdata('user_get_id');
                if ($this->um->soft_delete($id, $user_id)) {
                    echo json_encode([
                        'status' => true,
                        'message' => 'User deleted successfully'
                    ]);
                } else {
                    echo json_encode([
                        'status' => false,
                        'message' => 'Failed to delete user'
                    ]);
                }

                // Stop further execution
                exit;


                // $check_investment = $this->db->query("SELECT * FROM `user_investment` WHERE user_id = '" . $id . "' AND status = 1")->num_rows();

                // $check_downline = $this->db->query("SELECT * FROM `binary_placement` WHERE sponsor_id = '" . $id . "' OR parent_id = '" . $id . "'")->num_rows();

                // if ($check_investment > 0) {
                //     $response = array(
                //         'status' => false,
                //         'message' => "User has an active investment. Cannot delete!"
                //     );
                // } elseif ($check_downline > 0) {
                //     $response = array(
                //         'status' => false,
                //         'message' => "User has a downline. Cannot delete!"
                //     );
                // } else {
                //     $this->db->query("DELETE FROM `history` WHERE user_id = '" . $id . "'");
                //     $this->db->query("DELETE FROM `history` WHERE from_id = '" . $id . "'");
                //     $this->db->query("DELETE FROM `user_investment` WHERE user_id = '" . $id . "'");
                //     $this->db->query("DELETE FROM `users` WHERE id = '" . $id . "'");
                //     $response = array(
                //         'status' => 'success',
                //         'message' => "User and related records deleted successfully."
                //     );
                // }
            } else {
                $response = array(
                    'status' => false,
                    'message' => "Invalid User!"
                );
            }

            echo json_encode($response);
            exit();
        }
    }


    // public function get_all_epins_ajax() {
    //     if($this->input->get('q')){                
    //         $epins = $this->em->get_all_epins($this->input->get('q')); // Fetch all EPINs
    //     }else{
    //         $epins = $this->em->get_all_epins(); // Fetch all EPINs
    //     }

    //     $data = [];

    //     foreach($epins as $epin) {
    //         $data[] = [
    //             'id' => $epin->id,
    //             'text' => $epin->epin
    //         ];
    //     }

    //     echo json_encode($data);
    //     exit;
    // }

    public function get_all_epins_ajax()
    {

        $q = $this->input->get('q');
        $package_id = $this->input->get('package_id');

        $epins = $this->em->get_all_epins_filter($q, $package_id);

        $data = [];
        foreach ($epins as $epin) {
            $data[] = [
                'id' => $epin->epin,
                'text' => $epin->epin . ' - ' . get_username($epin->user_id)
            ];
        }

        echo json_encode($data);
        exit;
    }



}