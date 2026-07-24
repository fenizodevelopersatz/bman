<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class  Membermanagement extends CI_Controller {

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
        if (empty($permissions['member_management'])) {
            $this->session->set_flashdata('error', 'Access Denied: You do not have permission.');
            redirect('admin');
        }
    }

    $this->load->model('member/Users_model');
    $this->load->model('member/Mlm_model');
    $this->load->model('member/BinaryModel');

    }
    public function export_members()
    {
        $clients = $this->input->get('client_filter');
        $fromDate = $this->input->get('from_date') ? date('Y-m-d', strtotime($this->input->get('from_date'))) : '';
        $toDate = $this->input->get('to_date') ? date('Y-m-d', strtotime($this->input->get('to_date'))) : '';
        $search = trim((string) $this->input->get('search', true));
        $rows = $this->Users_model->get_export($clients, $fromDate, $toDate, $search);

        $filename = 'members-' . date('Y-m-d-His') . '.csv';
        $this->output
            ->set_header('Content-Type: text/csv; charset=UTF-8')
            ->set_header('Content-Disposition: attachment; filename="' . $filename . '"')
            ->set_header('Cache-Control: no-store, no-cache, must-revalidate');

        $stream = fopen('php://output', 'w');
        fputs($stream, "\xEF\xBB\xBF");
        fputcsv($stream, ['Member ID', 'Name', 'Referral ID', 'Email', 'Sponsor ID', 'Sponsor Email', 'Purchased Staking (BMAN)', 'KYC Status', 'Pending Withdrawals', 'Pending Withdrawal Amount (BMAN)', 'Status', 'Registered']);
        foreach ($rows as $row) {
            $name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) ?: ($row['name'] ?: $row['username']);
            fputcsv($stream, [
                $row['id'],
                $name,
                $row['referral_id'],
                $row['email'],
                $row['sponsor_referral'] ?: 'Main - Admin',
                $row['sponsor_email'] ?: 'Main - Admin',
                number_format((float) $row['purchased_staking'], 8, '.', ''),
                strtoupper((string) $row['kyc_status']),
                (int) $row['pending_withdraw_count'],
                number_format((float) $row['pending_withdraw_amount'], 8, '.', ''),
                (int) $row['status'] === 1 ? 'Active' : 'Inactive',
                $row['register_date'],
            ]);
        }
        fclose($stream);
        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Index Page
    |--------------------------------------------------------------------------
    */
    public function index(){
    $this->data['title'] = "All Members List ";
    $this->data['card_tilte'] = "Members List";
    $this->load->view('admin/member/list',$this->data);
    }

    /** AJAX Select2 lookup for the member-list filter. */
    public function member_search()
    {
        $term = trim((string) ($this->input->get('q', true) ?: $this->input->get('term', true)));
        $page = max(1, (int) $this->input->get('page', true));
        $limit = 100;
        $offset = ($page - 1) * $limit;

        $this->db->select('id, username, name, first_name, last_name, email, referral_id, profile_img, image')
                 ->from('users')
                 ->where('status', 1);

        if ($term !== '') {
            $this->db->group_start()
                     ->like('username', $term)
                     ->or_like('name', $term)
                     ->or_like('first_name', $term)
                     ->or_like('last_name', $term)
                     ->or_like('email', $term)
                     ->or_like('referral_id', $term)
                     ->group_end();
        }

        $rows = $this->db->order_by('username', 'ASC')
                         ->limit($limit + 1, $offset)
                         ->get()
                         ->result_array();

        $more = count($rows) > $limit;
        if ($more) {
            array_pop($rows);
        }

        $results = [];
        foreach ($rows as $row) {
            $name = trim((string) $row['name']);
            if ($name === '') {
                $name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
            }
            if ($name === '') {
                $name = (string) $row['username'];
            }

            $profileImage = !empty($row['profile_img']) ? $row['profile_img'] : $row['image'];
            if (!$profileImage) {
                $avatar = default_avatar_url();
            } elseif (preg_match('~^(https?://|assets/|uploads/)~i', $profileImage)) {
                $avatar = media_url($profileImage);
            } else {
                $relativeImage = 'assets/images/' . ltrim($profileImage, '/');
                $avatar = is_file(FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $relativeImage))
                    ? base_url($relativeImage)
                    : default_avatar_url();
            }

            $results[] = [
                'id'          => (int) $row['id'],
                'text'        => $name . ' (' . $row['email'] . ' · ' . $row['referral_id'] . ')',
                'name'        => $name,
                'email'       => (string) $row['email'],
                'referral_id' => (string) $row['referral_id'],
                'avatar'      => $avatar,
            ];
        }

        return $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'results' => $results,
                        'pagination' => ['more' => $more],
                    ]));
    }
    /*
    |--------------------------------------------------------------------------
    | Image Generate
    |--------------------------------------------------------------------------
    */
    public function image_generate(){

        $json_data = file_get_contents('php://input');
        $request_data = json_decode($json_data, true);
        $image_code = $this->request_data['image_code'];
        $create_image  = $this->Mlm_model->online_image_generate($image_code);
        echo json_encode($create_image);
           
    }
    /*
    |--------------------------------------------------------------------------
    | list Page
    |--------------------------------------------------------------------------
    */
    public function list(){
    $draw = (int) $this->input->get('draw');
    $start = max(0, (int) $this->input->get('start'));
    $length = min(100, max(10, (int) ($this->input->get('length') ?: 10)));
    $clients = $this->input->get('client_filter');
    $from_date = $this->input->get('from_date') ? date('Y-m-d', strtotime($this->input->get('from_date'))) : '';
    $to_date = $this->input->get('to_date') ? date('Y-m-d', strtotime($this->input->get('to_date'))) : '';
    $searchInput = $this->input->get('search');
    $search = is_array($searchInput) ? trim((string) ($searchInput['value'] ?? '')) : trim((string) $searchInput);

    $total_records = $this->Users_model->get_count($clients, $from_date, $to_date, $search);
    $users = $this->Users_model->get_info($length, $start, $clients, $from_date, $to_date, $search);
    $data = [];

    foreach ($users as $index => $user) {
        $fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        if ($fullName === '') $fullName = trim((string) ($user['name'] ?? ''));
        if ($fullName === '') $fullName = (string) ($user['username'] ?? 'Member');

        $profileImage = $user['profile_img'] ?: $user['image'];
        $avatar = $profileImage ? media_url($profileImage) : default_avatar_url();
        $avatarHtml = '<img src="' . html_escape($avatar) . '" alt="" class="rounded-circle" width="44" height="44" style="object-fit:cover" onerror="this.onerror=null;this.src=\'' . html_escape(default_avatar_url()) . '\'">';

        $sponsorReferral = $user['sponsor_referral'] ?: 'Main - Admin';
        $sponsorEmail = $user['sponsor_email'] ?: 'Main - Admin';
        $kyc = strtolower((string) ($user['kyc_status'] ?: 'none'));
        $kycClass = $kyc === 'approved' ? 'success' : (in_array($kyc, ['pending', 'under_review', 'resubmitted'], true) ? 'warning' : ($kyc === 'rejected' ? 'danger' : 'secondary'));
        $pendingCount = (int) $user['pending_withdraw_count'];
        $pendingAmount = (float) $user['pending_withdraw_amount'];
        $statusTitle = (int) $user['status'] === 1 ? 'Active' : 'Inactive';
        $statusColor = (int) $user['status'] === 1 ? '#17c964' : '#d1d5db';

        $data[] = [
            'RecordID' => $start + $index + 1,
            'UserInfo' => '<div class="d-flex align-items-center gap-3">' . $avatarHtml .
                '<div class="d-flex flex-column"><a class="text-gray-900 fw-bold text-hover-primary" href="' . base_url('view-user/' . (int) $user['id']) . '">' . html_escape($fullName) . '</a>' .
                '<span class="text-gray-600 fw-semibold fs-7">' . html_escape($user['referral_id']) . ' · ' . html_escape($user['email']) . '</span>' .
                '<span class="text-muted fs-8">' . html_escape($user['register_date']) . '</span></div></div>',
            'SponserInfo' => '<div class="fw-bold text-gray-800">' . html_escape($sponsorReferral) . '</div><div class="text-muted fs-7">' . html_escape($sponsorEmail) . '</div>',
            'StakingTotal' => '<div class="fw-bolder text-gray-900">' . number_format((float) $user['purchased_staking'], 4) . ' BMAN</div><div class="text-muted fs-8">Completed purchases</div>',
            'KycStatus' => '<span class="badge badge-light-' . $kycClass . '">' . html_escape(strtoupper(str_replace('_', ' ', $kyc))) . '</span>',
            'WithdrawalRequest' => $pendingCount > 0
                ? '<div class="fw-bold text-warning">' . $pendingCount . ' pending</div><div class="text-muted fs-7">' . number_format($pendingAmount, 4) . ' BMAN</div>'
                : '<span class="text-muted">None</span>',
            'Status' => '<span title="' . $statusTitle . '" aria-label="' . $statusTitle . '" style="display:inline-block;width:12px;height:12px;border-radius:50%;background:' . $statusColor . ';box-shadow:0 0 0 4px ' . $statusColor . '22"></span>',
            'Action' => '<a class="btn btn-success btn-sm" href="' . base_url('view-user/' . (int) $user['id']) . '"><i class="fa fa-eye"></i> View</a>',
        ];
    }

    return $this->output->set_content_type('application/json')->set_output(json_encode([
        'draw' => $draw,
        'recordsTotal' => $total_records,
        'recordsFiltered' => $total_records,
        'data' => $data,
    ]));

    $draw = $this->input->get('draw');
    $start = $this->input->get('start');
    $length = $this->input->get('length');

    $clients = $this->input->get('client_filter');
    $from_date = $this->input->get('from_date') ? date('Y-m-d', strtotime($this->input->get('from_date'))) : '';
    $to_date = $this->input->get('to_date') ? date('Y-m-d', strtotime($this->input->get('to_date'))) : '';
    
    $data = array();
    $total_records = $this->Users_model->get_count($clients,$from_date,$to_date);
    $users = $this->Users_model->get_info($length, $start,$clients,$from_date,$to_date);

    $i = 0;
    foreach ($users as $user) {
    $i++;

    if($user['sponser'] > 0){
        $sponser_info = $this->db->query("SELECT * FROM users where id = '".$user['sponser']."' ")->row();
        $sponser_referral =  $sponser_info->referral_id ? $sponser_info->referral_id : " Unkown ";
        $sponser_email =  $sponser_info->email ? $sponser_info->email : " Unkown ";
    } else {
        $sponser_referral =  " Main - Admin ";
        $sponser_email =   " Main - Admin ";
    }

    $status = "";

    if($user['status'] == '0'){
        $status = "In-Active";
    } if($user['status']== '1') { 
        $status = "Active";
    }if($user['status'] == '2') { 
        $status = "In-Active";
    }

    $binary_info = $this->BinaryModel->calculateLegInvestments($user['id']);

    $left_leg_count = count($binary_info['left_leg_users']);  
    $right_leg_count = count($binary_info['right_leg_users']); 

    $left_leg_investment = $binary_info['left_leg_investment']; 
    $right_leg_investment = $binary_info['right_leg_investment'];
    $my_investment = $binary_info['my_investment'];

    
    $left_leg_investment_token = $binary_info['left_investment_token']; 
    $right_leg_investment_token = $binary_info['right_investment_token'];
    $my_investment_token = $binary_info['my_investment_token'];

    $tree_link = base_url().'user-genealogy/'.$user['id'];

    $currency_status = $user['status'] == '1'  ? "checked" : "";
    $change_status_url = base_url()."user-status-update/".$user['id'];
    $delete_url = base_url()."user-delete/".$user['id'];

    $data[] = array(
    'RecordID' => $i,
    'SponserInfo' => '<div class="d-flex align-items-center">
    <div class="symbol symbol-50px me-3">                                                   
    </div>
    <div class="d-flex justify-content-start flex-column">
    <a href="#" class="text-gray-800 fw-bold text-hover-primary mb-1 fs-6">'.$sponser_referral.'</a>
    <span class="text-gray-500 fw-semibold d-block fs-7">'.$sponser_email.'</span>
    </div>
    </div>',
    'UserInfo' => '<div class="d-flex align-items-center">
    <div class="symbol symbol-50px me-3">                                                   
    </div>
    <div class="d-flex justify-content-start flex-column">
    <a href="#" class="text-gray-800 fw-bold text-hover-primary mb-1 fs-6">'.$user['referral_id'].'</a>
    <span class="text-gray-500 fw-semibold d-block fs-7 mb-1">'.$user['email'].'</span>
    <span class="text-gray-500 fw-semibold d-block fs-7">'.$user['register_date'].'</span>
    </div>
    </div>',
    'BinaryInfo' => '<div class="d-flex align-items-center">
    <div class="symbol symbol-50px me-3">                                                   
    </div>
    <div class="d-flex justify-content-start flex-column me-4">
    <a href="#" class="text-gray-800 fw-bold text-hover-primary mb-1 fs-6">Total Left Leg '.$left_leg_count.'</a>
    <span class="text-gray-500 fw-semibold d-block fs-7">Total Right Leg '.$right_leg_count.'</span>
    </div>
    <div class="d-flex justify-content-start flex-column me-4">
    <a href="#" class="text-gray-800 fw-bold text-hover-primary mb-1 fs-6">Total Left Invest '.currency_format($left_leg_investment).'</a>
    <span class="text-gray-500 fw-semibold d-block fs-7">Total Right Invest '.currency_format($right_leg_investment).'</span>
    </div>
    </div>',
    'DateInfo' => '<div class="d-flex align-items-center">
    <div class="symbol symbol-50px me-3">       
    <a href="#" class="text-gray-800 fw-bold text-hover-primary mb-1 fs-6"> '.currency_format($my_investment).'</a>                                            
    </div>
    <div class="d-flex justify-content-start flex-column">
    </div>
    </div>',
    'Status' => '<div class="form-check form-switch form-check-custom form-check-success form-check-solid">
    <input class="form-check-input h-30px w-50px template_status" type="checkbox" value="1" name="template_status"'.
    $currency_status.'
    id="template_status" 
    data-payment="'.$user['id'].'" 
    data-template_status-url="'.$change_status_url.'"/>
    <label class="form-check-label" for="template_status">
    </label>
    </div>
    ',
    'Tree' => '<div class="d-flex justify-content-start flex-column">
    <a href="'.$tree_link.'" class="text-gray-800 fw-bold text-hover-primary mb-1 fs-6"> View Tree </a>
    </div>',
    'Action' => '<div class="d-flex justify-content-center flex-row">
    <a class="btn btn-success btn-active-light-success btn-sm dropdown-toggle_sedit-summary  text-center me-3" href="'.base_url().'view-user/'.$user['id'].'">
    <i class="fa fa-eye"></i> View
    </a>
    <a class="btn btn-danger btn-active-light-danger btn-sm delete_user text-center"   data-payment="'.$user['id'].'" 
        data-delete_user-url="'.$delete_url.'" ">
    <i class="fa fa-trash"></i> Delete
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
    | Add User
    |--------------------------------------------------------------------------
    */
    public function add_user(){

    $this->data['title'] = 'Add User';
    $this->data['card_title'] = 'Add Users';
    $this->data['action'] = base_url().'create-user';
    $this->data['redirect'] = base_url().'network-list';
    $this->data['users'] = $this->db->query("SELECT * FROM users where status = '1' ")->result();
    $this->load->view('admin/member/create-member', $this->data);
    
    }
    /*
    |--------------------------------------------------------------------------
    | Add User Post Method
    |--------------------------------------------------------------------------
    */
    public function create_user() {

        $sponsor_id = $this->input->post('sponsor_id'); 
        $username = $this->input->post('username');
        $email = $this->input->post('useremail');
        $sponser_leg = $this->input->post('select_lg');
        $password = $this->password_create();

        if ($this->Mlm_model->usernameExists($username)) {
            echo json_encode(["status" => "error", "message" => "Username already taken"]);
            exit();
        }

        $user_id = $this->Mlm_model->registerUser($username, $email, $sponsor_id,$sponser_leg,$password);

        if ($user_id) {
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
    public function password_create(){

        $uppercase = chr(rand(65, 90));              // A-Z
        $lowercase = chr(rand(97, 122));             // a-z
        $number    = chr(rand(48, 57));              // 0-9
        $special   = chr(rand(33, 47));              // Special chars like ! " # $ etc.

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
    public function genealogy($user_id){
    $this->data['title'] = "Members Genealogy ";
    $this->data['card_title'] = "Genealogy List";
    $this->data['user_id'] =$user_id;
    $this->load->view('admin/member/genealogy_view', $this->data);
    }
    /*
    |--------------------------------------------------------------------------
    | View Genealoy
    |--------------------------------------------------------------------------
    */
    public function getTreeData($user_id) {
        $members = $this->BinaryModel->getDownlineMembers($user_id);
        echo json_encode($members);
    }
    /*
    |--------------------------------------------------------------------------
    | View User Details
    |--------------------------------------------------------------------------
    */
    public function viewuser($id){
    $id = (int) $id;
    $snapshot = $this->_profileSnapshot($id);
    if (!$snapshot) {
        show_404();
        return;
    }
    $this->data['title'] = "View User Profile";
    $this->data['card_tilte'] = "Member Control Center";
    $this->data['user_id'] = $id;
    $this->data['snapshot'] = $snapshot;
    $this->load->view('admin/member/profile', $this->data);
    return;
    $this->data['title'] = "View User Profile";
    $this->data['card_tilte'] = "User Profile";
    $this->data['user_id'] = $id;
    $user_row = $this->db->query("SELECT * FROM users where id = '".$id."' ")->row();
    $user_name = $user_row ? $user_row->username : '';
    $this->data['first_letter'] = substr($user_name, 0, 1);

    // Full profile row (proposal §1 fields) for the read-only Profile card.
    $this->data['profile'] = $user_row;
    if ($user_row && (int)$user_row->sponser > 0) {
        $this->data['sponser_row'] = $this->db->query("SELECT referral_id, email FROM users where id = '".(int)$user_row->sponser."'")->row();
    } else {
        $this->data['sponser_row'] = null;
    }

    $this->data['currency_info'] = currency_info();
    $this->data['token_info'] = token_info();

    $site_wallet_balance = site_wallet_balance($id);
    $token_wallet_balance = site_token_balance($id);

    $lending_profit = get_transaction_currecy('profit',$id);
    $direct_commission = get_transaction_currecy('direct_commission',$id);
    $level_commissions = get_transaction_currecy('level_commission',$id);

    $level_commissions_1 = get_transaction_level_currency('level_commission',$id,'1');
    $level_commissions_2 = get_transaction_level_currency('level_commission',$id,'2');
    $level_commissions_3 = get_transaction_level_currency('level_commission',$id,'3');
    $level_commissions_4 = get_transaction_level_currency('level_commission',$id,'4');
    $level_commissions_5 = get_transaction_level_currency('level_commission',$id,'5');

    
    $this->data['level_commissions']  = $level_commissions;
    $this->data['level_commissions_1']  = $level_commissions_1;
    $this->data['level_commissions_2']  = $level_commissions_2;
    $this->data['level_commissions_3']  = $level_commissions_3;
    $this->data['level_commissions_4']  = $level_commissions_4;
    $this->data['level_commissions_5']  = $level_commissions_5;

    $this->data['wallet_balance']  = $site_wallet_balance;
    $this->data['token_wallet_balance']  = $token_wallet_balance;

    $this->data['lending_profit']  = $lending_profit;
    $this->data['direct_commission']  = $direct_commission;

    $this->load->view('admin/member/profile',$this->data);
    }

    public function profile_summary($id)
    {
        $snapshot = $this->_profileSnapshot((int) $id);
        $this->_profileJson($snapshot
            ? ['status' => true, 'data' => $snapshot]
            : ['status' => false, 'message' => 'Member not found'], $snapshot ? 200 : 404);
    }

    public function profile_transactions($id)
    {
        $id = (int) $id;
        if (!$this->db->get_where('users', ['id' => $id])->row()) {
            return $this->_profileJson(['status' => false, 'message' => 'Member not found'], 404);
        }

        $page = max(1, (int) $this->input->get('page'));
        $limit = min(100, max(5, (int) ($this->input->get('limit') ?: 10)));
        $offset = ($page - 1) * $limit;
        $type = trim((string) $this->input->get('type', true));
        $from = trim((string) $this->input->get('from', true));
        $to = trim((string) $this->input->get('to', true));
        $search = trim((string) $this->input->get('q', true));

        $applyFilters = function () use ($id, $type, $from, $to, $search) {
            $this->db->where('user_id', $id);
            if ($type !== '') $this->db->where('wallet_type', $type);
            if ($from !== '') $this->db->where('created_at >=', $from . ' 00:00:00');
            if ($to !== '') $this->db->where('created_at <=', $to . ' 23:59:59');
            if ($search !== '') {
                $this->db->group_start()
                    ->like('reference_type', $search)
                    ->or_like('reference_id', $search)
                    ->or_like('description', $search)
                    ->or_like('tx_hash', $search)
                    ->group_end();
            }
        };

        $this->db->from('wallet_ledger');
        $applyFilters();
        $total = (int) $this->db->count_all_results();

        $this->db->select('id, wallet_type, credit, debit, balance_after, reference_type, reference_id, description, tx_hash, maturity_date, is_matured, created_at')
            ->from('wallet_ledger');
        $applyFilters();
        $rows = $this->db->order_by('id', 'DESC')->limit($limit, $offset)->get()->result_array();

        $this->_profileJson([
            'status' => true,
            'data' => $rows,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => max(1, (int) ceil($total / $limit)),
            ],
        ]);
    }

    public function profile_tree($id)
    {
        $profileId = (int) $id;
        $rootId = (int) ($this->input->get('root_id') ?: $profileId);
        $depth = min(3, max(1, (int) ($this->input->get('depth') ?: 2)));

        if ($rootId !== $profileId && !$this->BinaryModel->isDescendantOf($rootId, $profileId)) {
            return $this->_profileJson(['status' => false, 'message' => 'Tree member is outside this profile'], 403);
        }

        $root = $this->db
            ->select('u.id, u.username, u.email, u.referral_id, u.status, u.profile_img, u.image, bp.parent_id, bp.position')
            ->from('users u')
            ->join('binary_placement bp', 'bp.user_id = u.id', 'left')
            ->where('u.id', $rootId)
            ->get()->row_array();
        if (!$root) {
            return $this->_profileJson(['status' => false, 'message' => 'Member not found'], 404);
        }

        $nodes = [$rootId => $root];
        $parents = [$rootId];
        for ($level = 1; $level <= $depth && $parents; $level++) {
            $children = $this->db
                ->select('u.id, u.username, u.email, u.referral_id, u.status, u.profile_img, u.image, bp.parent_id, bp.position')
                ->from('binary_placement bp')
                ->join('users u', 'u.id = bp.user_id', 'inner')
                ->where_in('bp.parent_id', $parents)
                ->order_by('bp.parent_id', 'ASC')
                ->order_by('bp.position', 'ASC')
                ->get()->result_array();
            $parents = [];
            foreach ($children as $child) {
                $cid = (int) $child['id'];
                $nodes[$cid] = $child;
                $parents[] = $cid;
            }
        }

        $nodeIds = array_keys($nodes);
        $wallets = [];
        if ($nodeIds) {
            foreach ($this->db->select('user_id, exchange_balance, earning_balance, staking_balance, bonus_balance')
                ->where_in('user_id', $nodeIds)->get('user_wallets')->result_array() as $wallet) {
                $wallets[(int) $wallet['user_id']] = $wallet;
            }
        }

        $out = [];
        foreach ($nodes as $node) {
            $nid = (int) $node['id'];
            $wallet = $wallets[$nid] ?? [];
            $image = $node['profile_img'] ?: $node['image'];
            $out[] = [
                'id' => $nid,
                'parent_id' => $nid === $rootId ? null : (int) $node['parent_id'],
                'position' => strtolower((string) ($node['position'] ?? '')),
                'name' => $node['username'] ?: ('User #' . $nid),
                'email' => $node['email'],
                'referral_id' => $node['referral_id'] ?: ('#' . $nid),
                'status' => (int) $node['status'] === 1 ? 'active' : 'inactive',
                'avatar' => $image ? media_url($image) : default_avatar_url(),
                'wallet_total' => (float) ($wallet['exchange_balance'] ?? 0)
                    + (float) ($wallet['earning_balance'] ?? 0)
                    + (float) ($wallet['staking_balance'] ?? 0)
                    + (float) ($wallet['bonus_balance'] ?? 0),
            ];
        }

        $this->_profileJson(['status' => true, 'root_id' => $rootId, 'depth' => $depth, 'data' => $out]);
    }

    private function _profileSnapshot($id)
    {
        $user = $this->db
            ->select('u.*, s.username AS sponsor_name, s.referral_id AS sponsor_referral, r.name AS rank_name, r.tier_level, r.required_group_volume, ur.group_volume, p.name AS package_name')
            ->from('users u')
            ->join('users s', 's.id = u.sponser', 'left')
            ->join('user_ranks ur', 'ur.user_id = u.id', 'left')
            ->join('staking_ranks r', 'r.id = ur.current_rank_id', 'left')
            ->join('staking_packages p', 'p.id = u.package_id', 'left')
            ->where('u.id', (int) $id)
            ->get()->row_array();
        if (!$user) return null;

        $wallet = $this->db->get_where('user_wallets', ['user_id' => (int) $id])->row_array() ?: [];
        $wallets = [
            'usdt' => (float) ($wallet['usd_balance'] ?? 0),
            'exchange' => (float) ($wallet['exchange_balance'] ?? 0),
            'earning' => (float) ($wallet['earning_balance'] ?? 0),
            'bonus' => (float) ($wallet['bonus_balance'] ?? 0),
            'staking' => (float) ($wallet['staking_balance'] ?? 0),
            'pending_usdt' => (float) ($wallet['usd_pending'] ?? 0),
        ];
        $wallets['bman_total'] = $wallets['exchange'] + $wallets['earning'] + $wallets['bonus'] + $wallets['staking'];

        $stake = $this->db
            ->select('COALESCE(SUM(principal_amount),0) total, COALESCE(SUM(CASE WHEN overall_status IN ("active","in_progress") THEN principal_amount ELSE 0 END),0) active', false)
            ->where('user_id', (int) $id)->get('roi_staking_management')->row_array() ?: [];
        $withdraw = $this->db
            ->select('COALESCE(SUM(CASE WHEN status = "completed" THEN request_amount ELSE 0 END),0) withdrawn, COALESCE(SUM(CASE WHEN status IN ("pending","approved","processing") THEN request_amount ELSE 0 END),0) pending', false)
            ->where('user_id', (int) $id)->get('bman_withdraw_requests')->row_array() ?: [];
        $deposit = $this->db->select('COALESCE(SUM(amount_usdt),0) total', false)
            ->where('user_id', (int) $id)->where('status', 'credited')->get('wallet_deposits')->row_array() ?: [];
        $incomeRows = $this->db
            ->select('type, COALESCE(SUM(CAST(REPLACE(amount, ",", "") AS DECIMAL(30,8))),0) total', false)
            ->where('user_id', (int) $id)
            ->where_in('type', ['profit', 'direct_commission', 'binary_commission', 'level_commission', 'rank_reward'])
            ->group_by('type')->get('history')->result_array();
        $income = [];
        foreach ($incomeRows as $row) $income[$row['type']] = (float) $row['total'];

        $lastLogin = $this->db->select_max('timestamp', 'last_login')
            ->where('user_id', (int) $id)->where('status', 'logged in')->get('user_activity_logs')->row_array();
        $nextRank = null;
        if ($user['tier_level'] !== null) {
            $nextRank = $this->db->select('name, required_group_volume')
                ->where('tier_level >', (int) $user['tier_level'])
                ->where('is_active', 1)->order_by('tier_level', 'ASC')->limit(1)
                ->get('staking_ranks')->row_array();
        }
        $image = $user['profile_img'] ?: $user['image'];

        return [
            'member' => [
                'id' => (int) $user['id'],
                'name' => trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: ($user['name'] ?: $user['username']),
                'username' => $user['username'],
                'referral_id' => $user['referral_id'],
                'email' => $user['email'],
                'mobile' => $user['contact'],
                'country' => $user['country'],
                'state' => $user['state'],
                'address' => trim(($user['address'] ?? '') . ' ' . ($user['address_line2'] ?? '')),
                'zipcode' => $user['zipcode'],
                'gender' => $user['gender'],
                'dob' => $user['dob'],
                'status' => (int) $user['status'] === 1 ? 'active' : 'inactive',
                'frozen' => !empty($user['account_frozen']),
                'kyc_status' => $user['kyc_status'] ?: 'none',
                'sponsor' => $user['sponsor_name'] ?: 'Main - Admin',
                'sponsor_referral' => $user['sponsor_referral'],
                'rank' => $user['rank_name'] ?: 'UN RANK',
                'package' => $user['package_name'] ?: 'No package',
                'registered_at' => $user['register_date'],
                'last_login' => $lastLogin['last_login'] ?? null,
                'last_active' => $user['last_active_at'],
                'avatar' => $image ? media_url($image) : default_avatar_url(),
            ],
            'wallets' => $wallets,
            'stats' => [
                'total_investment' => (float) ($stake['total'] ?? 0),
                'active_staking' => (float) ($stake['active'] ?? 0),
                'total_withdrawn' => (float) ($withdraw['withdrawn'] ?? 0),
                'pending_withdraw' => (float) ($withdraw['pending'] ?? 0),
                'total_deposits' => (float) ($deposit['total'] ?? 0),
                'roi_earned' => (float) ($income['profit'] ?? 0),
                'direct_income' => (float) ($income['direct_commission'] ?? 0),
                'binary_income' => (float) ($income['binary_commission'] ?? 0),
                'level_income' => (float) ($income['level_commission'] ?? 0),
                'rank_reward' => (float) ($income['rank_reward'] ?? 0),
            ],
            'rank' => [
                'current' => $user['rank_name'] ?: 'UN RANK',
                'group_volume' => (float) ($user['group_volume'] ?? 0),
                'required_volume' => (float) ($user['required_group_volume'] ?? 0),
                'next' => $nextRank['name'] ?? null,
                'next_required_volume' => (float) ($nextRank['required_group_volume'] ?? 0),
            ],
            'refreshed_at' => date('Y-m-d H:i:s'),
        ];
    }

    private function _profileJson($payload, $status = 200)
    {
        $this->output->set_status_header($status)
            ->set_content_type('application/json')
            ->set_output(json_encode($payload, JSON_UNESCAPED_SLASHES));
    }

    public function viewuserinfo($id){

        /******* BASIC INFO ***********/
        $userinfo = $this->db->query("SELECT * FROM users where id = '".$id."'")->row();
        $sponser_info = $this->db->query("SELECT * FROM users where id = '".$userinfo->sponser."'")->row();

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
            "sponser" => $sponser_info->email." ( ".$sponser_info->referral_id." )",
            "my_investment" => currency_format($my_investment),
            "left_leg_count" => $left_leg_count,
            "right_leg_count" => $right_leg_count,
            "left_leg_investment" => currency_format($left_leg_investment),
            "right_leg_investment" => currency_format($right_leg_investment),
            'left_leg_investment_token' => token_format($left_leg_investment_token),
            'right_leg_investment_token' => token_format($right_leg_investment_token),
            'my_investment_token' => token_format($my_investment_token),
            'binary_site_currency' =>$binary_site_currency,
            'binary_token_currency' =>$binary_token_currency,
            'roi_token_currency' =>$roi_token_currency,
            'direct_site_currency' =>$direct_site_currency,
            'direct_token_currency' =>$direct_token_currency,
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
        public function statusupdate($id){

            if($id){

                $check_template = $this->db->query("SELECT * FROM `users` where id = '".$id."'")->num_rows();

                if($check_template > 0){

                    $status = $this->input->post('template_status');
                    $template_status = $status == '1' ? '1':'2';

                    $array_template = array(
                        "status" => $template_status,
                    );

                    $this->db->where('id',$id);
                    $this->db->update('users',$array_template);

                    $response = array(
                        'status' => "success",
                        'message' => "Status update successfully.."
                    );
                    echo json_encode($response);
                    exit(); 
                } else {
                    $response = array(
                        'status' => false,
                        'message' => "Invalide User!"
                    );
                    echo json_encode($response);
                    exit(); 
                }

            }

        }
        /*
        |--------------------------------------------------------------------------
        | DELETE USER
        |--------------------------------------------------------------------------
        */
        public function deleteuser($id) {
            if ($id) {
                $check_user = $this->db->query("SELECT * FROM `users` WHERE id = '".$id."'")->num_rows();
                
                if ($check_user > 0) {
                    $check_investment = $this->db->query("SELECT * FROM `user_investment` WHERE user_id = '".$id."' AND status = 1")->num_rows();
                    
                    $check_downline = $this->db->query("SELECT * FROM `binary_placement` WHERE sponsor_id = '".$id."' OR parent_id = '".$id."'")->num_rows();
        
                    if ($check_investment > 0) {
                        $response = array(
                            'status' => false,
                            'message' => "User has an active investment. Cannot delete!"
                        );
                    } elseif ($check_downline > 0) {
                        $response = array(
                            'status' => false,
                            'message' => "User has a downline. Cannot delete!"
                        );
                    } else {
                        $this->db->query("DELETE FROM `history` WHERE user_id = '".$id."'");
                        $this->db->query("DELETE FROM `history` WHERE from_id = '".$id."'");
                        $this->db->query("DELETE FROM `user_investment` WHERE user_id = '".$id."'");
                        $this->db->query("DELETE FROM `users` WHERE id = '".$id."'");
                        $response = array(
                            'status' => 'success',
                            'message' => "User and related records deleted successfully."
                        );
                    }
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
        
        
}
