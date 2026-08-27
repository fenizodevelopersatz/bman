<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class  Membermanagement extends CI_Controller {

    public function __construct() {
    parent::__construct();
    $this->load->library('session');
    $this->load->helper('url');
    $this->load->model('Admin_model');

    if (!$this->session->userdata('admin_logged_in')) {
        redirect('aaddmmiinn/login');
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
    $this->data['title'] = "All Users List ";
    $this->data['card_tilte'] = "Users List";
    // Opening the user list clears the sidebar "new registrations" badge.
    $this->load->model('admin/DashboardStats_model', 'dashstats');
    $this->dashstats->markSeen((int) $this->session->userdata('admin_userid'), 'users');
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

    // Current rank per member (user_ranks.current_rank_id); rank_cell_html() falls
    // back to the base rank when unset. One batch query for the whole page.
    $userIds = array_map(function ($u) { return (int) $u['id']; }, $users);
    $rankByUser = [];
    if ($userIds) {
        foreach ($this->db->select('user_id, current_rank_id')
                          ->where_in('user_id', $userIds)
                          ->get('user_ranks')->result_array() as $rk) {
            $rankByUser[(int) $rk['user_id']] = $rk['current_rank_id'];
        }
    }

    // Current Staking Summation (principal still processing/active, i.e. not yet
    // matured/withdrawn/cancelled) + a matured-package count, from user_stakes —
    // deliberately separate from 'purchased_staking' below, which only counts the
    // on-chain swap-purchase path (staking_swap_orders) and stays 0 for any stake
    // added through another route. One batch query for the whole page.
    $stakingByUser = [];
    if ($userIds) {
        foreach ($this->db->select('user_id,
                    SUM(CASE WHEN status IN ("processing","active") THEN stake_amount ELSE 0 END) AS current_total,
                    SUM(CASE WHEN status = "matured" THEN 1 ELSE 0 END) AS matured_count', false)
                          ->where_in('user_id', $userIds)
                          ->group_by('user_id')
                          ->get('user_stakes')->result_array() as $sk) {
            $stakingByUser[(int) $sk['user_id']] = [
                'current' => (float) $sk['current_total'],
                'matured' => (int) $sk['matured_count'],
            ];
        }
    }

    foreach ($users as $index => $user) {
        $fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        if ($fullName === '') $fullName = trim((string) ($user['name'] ?? ''));
        if ($fullName === '') $fullName = (string) ($user['username'] ?? 'Member');

        $avatar = $this->_profileAvatar($user['profile_img'] ?? '', $user['image'] ?? '');
        $profileUrl = base_url('view-user/' . (int) $user['id']);
        $avatarHtml = '<a href="' . $profileUrl . '" title="View member profile"><img src="' . html_escape($avatar) . '" alt="' . html_escape($fullName) . '" class="rounded-circle" width="44" height="44" style="object-fit:cover" onerror="this.onerror=null;this.src=\'' . html_escape(default_avatar_url()) . '\'"></a>';

        $sponsorReferral = $user['sponsor_referral'] ?: 'Main - Admin';
        $sponsorEmail = $user['sponsor_email'] ?: 'Main - Admin';
        $kyc = strtolower((string) ($user['kyc_status'] ?: 'none'));
        $kycClass = $kyc === 'approved' ? 'success' : (in_array($kyc, ['pending', 'under_review', 'resubmitted'], true) ? 'warning' : ($kyc === 'rejected' ? 'danger' : 'secondary'));
        $pendingCount = (int) $user['pending_withdraw_count'];
        $pendingAmount = (float) $user['pending_withdraw_amount'];
        $isFrozen = !empty($user['account_frozen']);
        $statusTitle = $isFrozen ? 'Frozen Account' : ((int) $user['status'] === 1 ? 'Active Account' : 'Inactive Account');
        $statusColor = $isFrozen ? '#ef4444' : ((int) $user['status'] === 1 ? '#17c964' : '#d1d5db');
        $frozenBadge = $isFrozen ? '<span class="badge badge-light-danger mt-1" title="Frozen Account">Frozen Account</span>' : '';
        $kycUrl = base_url('admin/kyc?user_id=' . (int) $user['id'] . '&open=1');
        $withdrawUrl = !empty($user['pending_withdraw_id'])
            ? base_url('admin/bman-withdrawals/view/' . (int) $user['pending_withdraw_id'])
            : '';
        $staking = $stakingByUser[(int) $user['id']] ?? ['current' => 0.0, 'matured' => 0];

        $data[] = [
            'RecordID' => $start + $index + 1,
            'UserInfo' => '<div class="d-flex align-items-center gap-3">' . $avatarHtml .
                '<div class="d-flex flex-column"><a class="text-gray-900 fw-bold text-hover-primary" href="' . $profileUrl . '">' . html_escape($fullName) . '</a>' .
                '<span class="text-gray-600 fw-semibold fs-7">' . html_escape($user['referral_id']) . ' · ' . html_escape($user['email']) . '</span>' .
                '<span class="text-muted fs-8">' . html_escape($user['register_date']) . '</span>' . $frozenBadge . '</div></div>',
            'SponserInfo' => '<div class="fw-bold text-gray-800">' . html_escape($sponsorReferral) . '</div><div class="text-muted fs-7">' . html_escape($sponsorEmail) . '</div>',
            'Rank' => rank_cell_html($rankByUser[(int) $user['id']] ?? null, 26),
            'StakingSummary' => '<div class="fw-bolder text-gray-900">' . number_format($staking['current'], 4) . ' BMAN</div><div class="text-muted fs-8">Matured: ' . $staking['matured'] . '</div>',
            'StakingTotal' => '<div class="fw-bolder text-gray-900">' . number_format((float) $user['purchased_staking'], 4) . ' BMAN</div><div class="text-muted fs-8">Completed purchases</div>',
            'KycStatus' => '<a href="' . $kycUrl . '" class="badge badge-light-' . $kycClass . ' text-hover-primary" title="Open KYC review">' . html_escape(strtoupper(str_replace('_', ' ', $kyc))) . '</a>',
            'WithdrawalRequest' => $pendingCount > 0
                ? '<a href="' . $withdrawUrl . '" class="d-block text-decoration-none" title="Open withdrawal request"><div class="fw-bold text-warning">' . $pendingCount . ' pending</div><div class="text-muted fs-7">' . number_format($pendingAmount, 4) . ' BMAN</div></a>'
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
            $out[] = [
                'id' => $nid,
                'parent_id' => $nid === $rootId ? null : (int) $node['parent_id'],
                'position' => strtolower((string) ($node['position'] ?? '')),
                'name' => $node['username'] ?: ('User #' . $nid),
                'email' => $node['email'],
                'referral_id' => $node['referral_id'] ?: ('#' . $nid),
                'status' => (int) $node['status'] === 1 ? 'active' : 'inactive',
                'avatar' => $this->_profileAvatar($node['profile_img'] ?? '', $node['image'] ?? ''),
                'wallet_total' => (float) ($wallet['exchange_balance'] ?? 0)
                    + (float) ($wallet['earning_balance'] ?? 0)
                    + (float) ($wallet['staking_balance'] ?? 0)
                    + (float) ($wallet['bonus_balance'] ?? 0),
                'exchange_balance' => (float) ($wallet['exchange_balance'] ?? 0),
                'earning_balance' => (float) ($wallet['earning_balance'] ?? 0),
                'staking_balance' => (float) ($wallet['staking_balance'] ?? 0),
                'bonus_balance' => (float) ($wallet['bonus_balance'] ?? 0),
            ];
        }

        $this->_profileJson(['status' => true, 'root_id' => $rootId, 'depth' => $depth, 'data' => $out]);
    }

    public function profile_roi($id)
    {
        $id = (int) $id;
        $page = max(1, (int) ($this->input->get('page') ?: 1));
        $limit = min(50, max(5, (int) ($this->input->get('limit') ?: 10)));
        $offset = ($page - 1) * $limit;
        $q = trim((string) $this->input->get('q', true));
        $status = trim((string) $this->input->get('status', true));

        $stake = $this->db->select(
            'COALESCE(SUM(principal_amount),0) investment,
             COALESCE(SUM(total_paid_amount),0) lifetime,
             COALESCE(SUM(remaining_to_pay),0) pending,
             MIN(CASE WHEN next_payment_date >= NOW() AND overall_status IN ("active","in_progress") THEN next_payment_date END) next_date,
             MIN(CASE WHEN fixed_maturity_date >= NOW() THEN fixed_maturity_date END) maturity_date,
             SUM(CASE WHEN overall_status IN ("active","in_progress") THEN 1 ELSE 0 END) running,
             SUM(CASE WHEN overall_status = "completed" THEN 1 ELSE 0 END) completed', false)
            ->where('user_id', $id)->get('roi_staking_management')->row_array() ?: [];
        $today = $this->db->select('COALESCE(SUM(amount),0) amount', false)->where('user_id', $id)
            ->where('status', 'paid')->where('credit_date', date('Y-m-d'))->get('staking_roi_payouts')->row_array();
        $month = $this->db->select('COALESCE(SUM(amount),0) amount', false)->where('user_id', $id)
            ->where('status', 'paid')->where('credit_date >=', date('Y-m-01'))->where('credit_date <=', date('Y-m-t'))
            ->get('staking_roi_payouts')->row_array();
        $next = $this->db->select('regular_payment_amount, fixed_payment_amount')->where('user_id', $id)
            ->where_in('overall_status', ['active', 'in_progress'])->where('next_payment_date >=', date('Y-m-d H:i:s'))
            ->order_by('next_payment_date', 'ASC')->limit(1)->get('roi_staking_management')->row_array() ?: [];

        $apply = function () use ($id, $q, $status) {
            $this->db->where('rp.user_id', $id);
            if ($status !== '') $this->db->where('rp.status', $status);
            if ($q !== '') $this->db->group_start()->like('sp.name', $q)->or_like('rp.tx_hash', $q)->or_like('us.plan_code', $q)->group_end();
        };
        $this->db->from('staking_roi_payouts rp')->join('user_stakes us', 'us.id=rp.stake_id', 'left')
            ->join('staking_packages sp', 'sp.id=us.package_id', 'left');
        $apply();
        $total = (int) $this->db->count_all_results();
        $this->db->select('rp.id,rp.amount,rp.credit_date,rp.wallet,rp.status,rp.tx_hash,rp.transfer_status,rp.network,us.plan_code,us.is_special,us.roi_percent,us.stake_amount,us.maturity_date,sp.name package_name')
            ->from('staking_roi_payouts rp')->join('user_stakes us', 'us.id=rp.stake_id', 'left')
            ->join('staking_packages sp', 'sp.id=us.package_id', 'left');
        $apply();
        $rows = $this->db->order_by('rp.id', 'DESC')->limit($limit, $offset)->get()->result_array();

        $investment = (float) ($stake['investment'] ?? 0);
        $lifetime = (float) ($stake['lifetime'] ?? 0);
        $maturity = $stake['maturity_date'] ?? null;
        $remainingDays = $maturity ? max(0, (int) floor((strtotime($maturity) - time()) / 86400)) : null;
        $this->_profileJson(['status' => true, 'summary' => [
            'total_roi_earned' => $lifetime, 'pending_roi' => (float) ($stake['pending'] ?? 0),
            'today_roi' => (float) ($today['amount'] ?? 0), 'monthly_roi' => (float) ($month['amount'] ?? 0),
            'lifetime_roi' => $lifetime, 'next_roi_date' => $stake['next_date'] ?? null,
            'next_roi_amount' => (float) (($next['regular_payment_amount'] ?? 0) ?: ($next['fixed_payment_amount'] ?? 0)),
            'maturity_date' => $maturity, 'remaining_days' => $remainingDays,
            'completion_percent' => $investment > 0 ? min(100, ($lifetime / $investment) * 100) : 0,
            'roi_status' => (int) ($stake['running'] ?? 0) > 0 ? 'Running' : ((int) ($stake['completed'] ?? 0) > 0 ? 'Completed' : 'Not started'),
        ], 'data' => $rows, 'pagination' => ['page' => $page, 'limit' => $limit, 'total' => $total, 'pages' => max(1, (int) ceil($total / $limit))]]);
    }

    public function profile_staking($id)
    {
        $rows = $this->db->select('us.id,sp.name package_name,us.stake_amount,us.plan_code,us.duration_years,us.is_special,us.start_date,us.maturity_date,us.status,us.tx_hash,us.block_number,us.confirmations,us.gas_fee,us.network,us.roi_percent,COALESCE(SUM(rp.amount),0) roi_earned', false)
            ->from('user_stakes us')->join('staking_packages sp', 'sp.id=us.package_id', 'left')
            ->join('staking_roi_payouts rp', 'rp.stake_id=us.id AND rp.status="paid"', 'left')
            ->where('us.user_id', (int) $id)->group_by('us.id')->order_by('us.id', 'DESC')->limit(100)->get()->result_array();
        foreach ($rows as &$row) {
            $row['certificate_pdf'] = null; // No staking-certificate source exists; do not expose an unrelated rank certificate.
            $row['remaining_days'] = $row['maturity_date'] ? max(0, (int) floor((strtotime($row['maturity_date']) - time()) / 86400)) : null;
            $duration = max(1, strtotime($row['maturity_date']) - strtotime($row['start_date']));
            $row['maturity_percent'] = min(100, max(0, ((time() - strtotime($row['start_date'])) / $duration) * 100));
        }

        // Lock Wallet total for this member — a separate aggregate query (not a
        // sum of the $rows above, which are capped at limit(100)) so a member
        // with more than 100 stakes is still totalled correctly. Same "still
        // locked" predicate as Staking_model::lockWalletBalance().
        $lockedTotal = (float) ($this->db->select_sum('stake_amount', 's')
            ->where('user_id', (int) $id)
            ->where_in('status', ['active', 'processing'])
            ->where('maturity_date >', date('Y-m-d'))
            ->get('user_stakes')->row()->s ?: 0);

        $this->_profileJson(['status' => true, 'summary' => ['locked_bman' => $lockedTotal], 'data' => $rows]);
    }

    public function profile_matching($id)
    {
        $id = (int) $id;
        $rows = $this->db->where('user_id', $id)->order_by('id', 'DESC')->limit(100)
            ->get('staking_matching_payouts')->result_array();
        $sum = ['today' => 0, 'weekly' => 0, 'monthly' => 0, 'lifetime' => 0];
        foreach ($rows as $row) {
            $amount = (float) $row['earning_amount'] + (float) $row['staking_amount'];
            $sum['lifetime'] += $amount;
            $ts = strtotime($row['created_at']);
            if (date('Y-m-d', $ts) === date('Y-m-d')) $sum['today'] += $amount;
            if ($ts >= strtotime('monday this week')) $sum['weekly'] += $amount;
            if (date('Y-m', $ts) === date('Y-m')) $sum['monthly'] += $amount;
        }
        $carry = $this->db->get_where('binary_carry', ['user_id' => $id])->row_array() ?: [];
        $pending = $this->db->select('COALESCE(SUM(bonus_amount_total),0) amount', false)->where('bonus_recipient_id', $id)
            ->where_in('status', ['CALCULATED', 'HELD_CEILING'])->get('binary_matching_bonus_ledger')->row_array();
        $this->_profileJson(['status' => true, 'summary' => [
            'total_matching_income' => $sum['lifetime'], 'today_matching' => $sum['today'],
            'weekly_matching' => $sum['weekly'], 'monthly_matching' => $sum['monthly'],
            'lifetime_matching' => $sum['lifetime'], 'pending_matching' => (float) ($pending['amount'] ?? 0),
            'carry_forward_left' => (float) ($carry['left_carry'] ?? 0),
            'carry_forward_right' => (float) ($carry['right_carry'] ?? 0),
        ], 'data' => $rows]);
    }

    public function profile_ranks($id)
    {
        $rows = $this->db->select('h.*,old.name previous_rank,new.name current_rank,new.badge_image,rr.reward_amount,rr.reward_status,rc.certificate_pdf')
            ->from('user_rank_history h')->join('staking_ranks old', 'old.id=h.old_rank_id', 'left')
            ->join('staking_ranks new', 'new.id=h.new_rank_id', 'left')
            ->join('rank_rewards rr', 'rr.user_id=h.user_id AND rr.rank_id=h.new_rank_id', 'left')
            ->join('rank_certificates rc', 'rc.user_id=h.user_id AND rc.rank_id=h.new_rank_id', 'left')
            ->where('h.user_id', (int) $id)->order_by('h.achieved_at', 'ASC')->get()->result_array();
        foreach ($rows as &$row) {
            if (!empty($row['badge_image'])) $row['badge_image'] = media_url($row['badge_image']);
            if (!empty($row['certificate_pdf'])) $row['certificate_pdf'] = media_url($row['certificate_pdf']);
        }
        $this->_profileJson(['status' => true, 'data' => $rows]);
    }

    public function profile_wallet_history($id)
    {
        $type = trim((string) ($this->input->get('type', true) ?: 'all'));
        $map = [
            'roi' => ['roi', 'profit'], 'binary' => ['binary'], 'direct' => ['direct'],
            'rank' => ['rank'], 'leadership' => ['leadership'], 'generation' => ['generation', 'level'],
            'withdrawals' => ['withdraw'], 'transfers' => ['transfer'],
        ];
        $this->db->select('id,wallet_type,credit,debit,balance_after,reference_type,reference_id,description,tx_hash,created_at')
            ->where('user_id', (int) $id);
        if ($type !== 'all' && isset($map[$type])) {
            $this->db->group_start();
            foreach ($map[$type] as $i => $term) $i ? $this->db->or_like('reference_type', $term) : $this->db->like('reference_type', $term);
            $this->db->group_end();
        }
        $rows = $this->db->order_by('id', 'DESC')->limit(100)->get('wallet_ledger')->result_array();
        $monthly = $this->db->select('DATE_FORMAT(created_at,"%Y-%m") month, SUM(credit-debit) net', false)
            ->where('user_id', (int) $id)->where('created_at >=', date('Y-m-01', strtotime('-11 months')))
            ->group_by('DATE_FORMAT(created_at,"%Y-%m")')->order_by('month', 'ASC')->get('wallet_ledger')->result_array();
        $roiMonthly = $this->db->select('DATE_FORMAT(credit_date,"%Y-%m") month, SUM(amount) total', false)
            ->where('user_id', (int) $id)->where('status', 'paid')->where('credit_date >=', date('Y-m-01', strtotime('-11 months')))
            ->group_by('DATE_FORMAT(credit_date,"%Y-%m")')->order_by('month', 'ASC')->get('staking_roi_payouts')->result_array();
        $matchingMonthly = $this->db->select('DATE_FORMAT(created_at,"%Y-%m") month, SUM(earning_amount+staking_amount) total', false)
            ->where('user_id', (int) $id)->where('created_at >=', date('Y-m-01', strtotime('-11 months')))
            ->group_by('DATE_FORMAT(created_at,"%Y-%m")')->order_by('month', 'ASC')->get('staking_matching_payouts')->result_array();
        $investmentMonthly = $this->db->select('DATE_FORMAT(start_date,"%Y-%m") month, SUM(stake_amount) total', false)
            ->where('user_id', (int) $id)->group_by('DATE_FORMAT(start_date,"%Y-%m")')->order_by('month', 'ASC')->get('user_stakes')->result_array();
        $rankProgress = $this->db->select('achieved_at, achieved_volume')->where('user_id', (int) $id)
            ->order_by('achieved_at', 'ASC')->get('user_rank_history')->result_array();
        $this->_profileJson(['status' => true, 'data' => $rows, 'charts' => [
            'wallet_growth' => $monthly, 'monthly_roi' => $roiMonthly,
            'matching_trend' => $matchingMonthly, 'investment' => $investmentMonthly,
            'rank_progress' => $rankProgress,
        ]]);
    }

    public function profile_transaction_detail($id, $ledgerId)
    {
        $ledger = $this->db->where(['id' => (int) $ledgerId, 'user_id' => (int) $id])->get('wallet_ledger')->row_array();
        if (!$ledger) return $this->_profileJson(['status' => false, 'message' => 'Transaction not found'], 404);
        $this->db->group_start()->where('wallet_ledger_id', (int) $ledgerId);
        if (!empty($ledger['tx_hash'])) $this->db->or_where('tx_hash', $ledger['tx_hash']);
        if (!empty($ledger['reference_id'])) $this->db->or_where('reference_id', $ledger['reference_id']);
        $this->db->group_end()->where('user_id', (int) $id);
        $chain = $this->db->order_by('id', 'DESC')->limit(1)->get('onchain_transactions')->row_array() ?: [];
        $user = $this->db->select('username,referral_id,profile_img,image')->get_where('users', ['id' => (int) $id])->row_array() ?: [];
        $this->_profileJson(['status' => true, 'member' => [
            'name' => $user['username'] ?? ('User #' . (int) $id), 'referral_id' => $user['referral_id'] ?? '',
            'avatar' => $this->_profileAvatar($user['profile_img'] ?? '', $user['image'] ?? ''),
        ], 'ledger' => $ledger, 'chain' => $chain]);
    }

    private function _profileAvatar($profileImg, $image)
    {
        $file = trim((string) ($profileImg ?: $image));
        if ($file === '') return default_avatar_url();
        if (preg_match('~^https?://~i', $file)) return $file;
        $relative = preg_match('~^(assets|uploads)/~i', $file) ? ltrim($file, '/') : 'assets/images/' . ltrim($file, '/');
        return is_file(FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $relative)) ? base_url($relative) : default_avatar_url();
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
                'avatar' => $this->_profileAvatar($user['profile_img'] ?? '', $user['image'] ?? ''),
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
        /**
         * Activate/deactivate a member. Audited via the generic
         * admin_settings_audit table (module='member_status') — same
         * mechanism Withdrawsettings.php uses — so the change shows up in
         * the unified Admin Audit Log alongside every other settings change.
         */
        public function statusupdate($id){
            $id = (int) $id;
            if (!$id) {
                echo json_encode(['status' => false, 'message' => 'Invalid user!']);
                return;
            }

            $user = $this->db->get_where('users', ['id' => $id])->row_array();
            if (!$user) {
                echo json_encode(['status' => false, 'message' => 'Invalid user!']);
                return;
            }

            $oldStatus = (string) $user['status'];
            $newStatus = ((string) $this->input->post('template_status') === '1') ? '1' : '2';

            if ($oldStatus === $newStatus) {
                echo json_encode([
                    'status' => true, 'message' => 'No change.',
                    'new_status' => $newStatus, 'new_status_label' => $newStatus === '1' ? 'Active' : 'Inactive',
                ]);
                return;
            }

            $this->db->where('id', $id)->update('users', ['status' => $newStatus]);

            $this->db->insert('admin_settings_audit', [
                'module'     => 'member_status',
                'field_name' => $user['username'] . ' (#' . $id . ')',
                'old_value'  => $oldStatus === '1' ? 'Active' : 'Inactive',
                'new_value'  => $newStatus === '1' ? 'Active' : 'Inactive',
                'changed_by' => (int) $this->session->userdata('admin_userid'),
            ]);

            echo json_encode([
                'status' => true,
                'message' => 'Status updated successfully.',
                'new_status' => $newStatus,
                'new_status_label' => $newStatus === '1' ? 'Active' : 'Inactive',
            ]);
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
