<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Sitesettings extends MY_Controller
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

        if ($user && $user->admin_roll == '1') {
            $permissions = json_decode($user->permission_pages, true);
            if (empty($permissions['site_settings'])) {
                $this->session->set_flashdata('error', 'Access Denied: You do not have permission.');
                redirect('admin');
            }
        }
    }

    public function index()
    {

        $this->data['logo_image'] = site_settings('image', 'logo');
        $this->data['footer_log'] = site_settings('image', 'footer_logo');
        $this->data['logo_dark_image'] = site_settings('image', 'dark_logo');
        $this->data['footer_dark_log'] = site_settings('image', 'dark_footer_logo');

        $this->data['fav_img'] = site_settings('image', 'favicon');
        $this->data['og_img'] = site_settings('image', 'og-img');

        $this->data['site_name'] = site_settings('meta-settings', 'site-name');
        $this->data['site_url'] = site_settings('meta-settings', 'site-url');
        $this->data['site_title'] = site_settings('meta-settings', 'site-title');
        $this->data['site_metakeyword'] = site_settings('meta-settings', 'site-keyword');
        $this->data['site_metadescription'] = site_settings('meta-settings', 'site-description');

        $this->data['contact_email'] = site_settings('company', 'email');
        $this->data['contact_number'] = site_settings('company', 'contact_number');
        $this->data['company_address'] = site_settings('company', 'address');

        $this->data['site_copyright'] = site_settings('meta-settings', 'copyright');

        $this->data['landing_status'] = site_settings('site_settings', 'landing_status');
        $this->data['kyc_status'] = site_settings('site_settings', 'kyc_status');
        $this->data['email_verify'] = site_settings('site_settings', 'email_verify');
        $this->data['use_captcha'] = site_settings('captcha', 'status');
        $this->data['two_fa_status'] = site_settings('site_settings', 'two_fa_status');
        $this->data['register_status'] = site_settings('site_settings', 'register_status');
        $this->data['allow_login'] = site_settings('site_settings', 'allow_login');
        $this->data['unique_ip'] = site_settings('site_settings', 'unique_ip');
        $this->data['unique_mobile'] = site_settings('site_settings', 'unique_mobile');
        $this->data['unique_email'] = site_settings('site_settings', 'unique_email');
        $this->data['allow_referral_only'] = site_settings('site_settings', 'allow_referral_only');



        $this->load->view('admin/settings/site-settings', $this->data);
    }


    public function update()
    {
        if ($this->input->post()) {
            $uploaded_images = $this->upload_images();

            $response = array(
                'status' => true,
                'message' => "Settings updated successfully"
            );
            echo json_encode($response);
        }
    }

    protected function upload_images()
    {
        if (ENABLE_SITE_UPLOAD_FUNCTION !== true) {
            $this->_dbg('site_settings_upload_images', 'Uploads disabled');
            return false;
        }

        if ($_FILES['header_log']["name"] != "") {
            $tempFile = $_FILES['header_log']['tmp_name'];
            $temp = $_FILES["header_log"]["name"];
            $targetFile = './assets/images/' . $_FILES["header_log"]['name'];
            move_uploaded_file($tempFile, $targetFile);
            $datas_logo = array('settings_value' => $temp);
            $this->db->where('settings_type', 'image');
            $this->db->where('settings_name', 'logo');
            $update = $this->db->update('site_settings', $datas_logo);
        }

        if ($_FILES['footer_log']["name"] != "") {
            $tempFile = $_FILES['footer_log']['tmp_name'];
            $temp = $_FILES["footer_log"]["name"];
            $targetFile = './assets/images/' . $_FILES["footer_log"]['name'];
            move_uploaded_file($tempFile, $targetFile);
            $datas_logo = array('settings_value' => $temp);
            $this->db->where('settings_type', 'image');
            $this->db->where('settings_name', 'footer_logo');
            $update = $this->db->update('site_settings', $datas_logo);
        }

        if ($_FILES['header_dark_log']["name"] != "") {
            $tempFile = $_FILES['header_dark_log']['tmp_name'];
            $temp = $_FILES["header_dark_log"]["name"];
            $targetFile = './assets/images/' . $_FILES["header_dark_log"]['name'];
            move_uploaded_file($tempFile, $targetFile);
            $datas_logo = array('settings_value' => $temp);
            $this->db->where('settings_type', 'image');
            $this->db->where('settings_name', 'dark_logo');
            $update = $this->db->update('site_settings', $datas_logo);
        }


        if ($_FILES['footer_dark_log']["name"] != "") {
            $tempFile = $_FILES['footer_dark_log']['tmp_name'];
            $temp = $_FILES["footer_dark_log"]["name"];
            $targetFile = './assets/images/' . $_FILES["footer_dark_log"]['name'];
            move_uploaded_file($tempFile, $targetFile);
            $datas_logo = array('settings_value' => $temp);
            $this->db->where('settings_type', 'image');
            $this->db->where('settings_name', 'dark_footer_logo');
            $update = $this->db->update('site_settings', $datas_logo);
        }

        if ($_FILES['fav_logo']["name"] != "") {
            $tempFile = $_FILES['fav_logo']['tmp_name'];
            $temp = $_FILES["fav_logo"]["name"];
            $targetFile = './assets/images/' . $_FILES["fav_logo"]['name'];
            move_uploaded_file($tempFile, $targetFile);
            $datas_logo = array('settings_value' => $temp);
            $this->db->where('settings_type', 'image');
            $this->db->where('settings_name', 'favicon');
            $update = $this->db->update('site_settings', $datas_logo);
        }

        if ($_FILES['og_img']["name"] != "") {
            $tempFile = $_FILES['og_img']['tmp_name'];
            $temp = $_FILES["og_img"]["name"];
            $targetFile = './assets/images/' . $_FILES["og_img"]['name'];
            move_uploaded_file($tempFile, $targetFile);
            $datas_logo = array('settings_value' => $temp);
            $this->db->where('settings_type', 'image');
            $this->db->where('settings_name', 'og-img');
            $update = $this->db->update('site_settings', $datas_logo);
        }



    }

    public function update_contact_settings()
    {
        $contact_email = $this->input->post('contact_email', true);
        $contact_number = $this->input->post('contact_number', true);
        $address = $this->input->post('company_address', true);

        if (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['status' => false, 'message' => "Invalid email format"]);
            exit();
        }

        if (!preg_match('/^[0-9+\s\-()]+$/', $contact_number)) {
            echo json_encode(['status' => false, 'message' => "Invalid contact number"]);
            exit();
        }

        $this->site_settings_update('company', 'email', $contact_email);
        $this->site_settings_update('company', 'contact_number', $contact_number);
        $this->site_settings_update('company', 'address', $address);

        echo json_encode([
            'status' => true,
            'message' => "Settings updated successfully"
        ]);
        exit();
    }


    public function update_meta_settings()
    {

        $site_name = $this->input->post('site_name', true);
        $site_url = $this->input->post('site_url', true);
        $site_title = $this->input->post('site_title', true);
        $site_metakeyword = $this->input->post('meta_keyword', true);
        $site_metadescription = $this->input->post('meta_discription', true);
        $site_copyright = $this->input->post('site_copyright', true);


        $this->site_settings_update('meta-settings', 'site-title', $site_title);
        $this->site_settings_update('meta-settings', 'site-url', $site_url);
        $this->site_settings_update('meta-settings', 'site-keyword', $site_metakeyword);
        $this->site_settings_update('meta-settings', 'site-description', $site_metadescription);
        $this->site_settings_update('meta-settings', 'site-name', $site_name);
        $this->site_settings_update('meta-settings', 'copyright', $site_copyright);

        // unified meta — mirror into the landing SEO section so the landing
        // page and Site Settings always show the same meta details.
        $this->landing_seo_mirror(array(
            'meta_title'       => $site_title,
            'meta_description' => $site_metadescription,
            'meta_keywords'    => $site_metakeyword,
        ));

        echo json_encode([
            'status' => true,
            'message' => "Settings updated successfully"
        ]);
        exit();
    }

    private function site_settings_update($type, $key, $value)
    {
        $this->db->where('settings_type', $type);
        $this->db->where('settings_name', $key);
        $update = $this->db->update('site_settings', ['settings_value' => $value]);

        if (!$update) {
            log_message('error', 'Failed to update setting: ' . $key);
        }
    }

    /** Mirror meta into the landing SEO section (landing_settings). Upsert. */
    private function landing_seo_mirror(array $fields)
    {
        // skip silently if the landing module isn't installed yet
        if (!$this->db->table_exists('landing_settings')) {
            return;
        }
        foreach ($fields as $key => $value) {
            $exists = $this->db->get_where('landing_settings',
                array('section' => 'seo', 'skey' => $key))->row();
            if ($exists) {
                $this->db->where('id', $exists->id)
                         ->update('landing_settings', array('svalue' => $value, 'update_date' => date('Y-m-d H:i:s')));
            } else {
                $this->db->insert('landing_settings', array(
                    'section' => 'seo', 'skey' => $key, 'svalue' => $value,
                    'update_date' => date('Y-m-d H:i:s'),
                ));
            }
        }
    }

    public function update_config_settings()
    {

        $landing_status = $this->input->post('landing_id') ? '1' : '0';
        $kyc_status = $this->input->post('kyc_status') ? '1' : '0';
        $email_verify = $this->input->post('email_verify') ? '1' : '0';
        $two_fa_status = $this->input->post('two_fa_status') ? '1' : '0';
        $register_status = $this->input->post('register_status') ? '1' : '0';
        $allow_login = $this->input->post('allow_login') ? '1' : '0';
        $unique_ip = $this->input->post('unique_ip') ? '1' : '0';
        $unique_mobile = $this->input->post('unique_mobile') ? '1' : '0';
        $unique_email = $this->input->post('unique_email') ? '1' : '0';
        $allow_referral_only = $this->input->post('allow_referral_only') ? '1' : '0';
        $use_captcha = $this->input->post('use_captcha') ? '1' : '0';

        $this->site_settings_update('site_settings', 'landing_status', $landing_status);
        $this->site_settings_update('site_settings', 'kyc_status', $kyc_status);
        $this->site_settings_update('site_settings', 'email_verify', $email_verify);
        $this->site_settings_update('site_settings', 'two_fa_status', $two_fa_status);
        $this->site_settings_update('site_settings', 'register_status', $register_status);
        $this->site_settings_update('site_settings', 'allow_login', $allow_login);
        $this->site_settings_update('site_settings', 'unique_ip', $unique_ip);
        $this->site_settings_update('site_settings', 'unique_mobile', $unique_mobile);
        $this->site_settings_update('site_settings', 'unique_email', $unique_email);
        $this->site_settings_update('site_settings', 'allow_referral_only', $allow_referral_only);
        $this->site_settings_update('captcha', 'status', $use_captcha);

        echo json_encode([
            'status' => true,
            'message' => "Settings updated successfully"
        ]);
        exit();
    }

    public function logout()
    {

        $this->session->unset_userdata('admin_login');
        $this->session->unset_userdata('login');
        $this->session->unset_userdata('user_id');
        $this->session->unset_userdata('username');
        $this->session->unset_userdata('role_id');
        $this->session->unset_userdata('email');
        $this->session->unset_userdata('image');
        $this->session->unset_userdata('site_logo');

        //Set Message
        $this->session->set_flashdata('success', 'You are logged out.');
        redirect(base_url() . 'admin');
    }
    /*
    |--------------------------------------------------------------------------
    | Transaction Index Page
    |--------------------------------------------------------------------------
    */
    public function transaction()
    {
        $this->data['title'] = "All Transaction List ";
        $this->data['card_tilte'] = "Transaction List";
        $this->data['currency_info'] = currency_info();
        $this->data['token_info'] = token_info();
        $this->load->view('admin/transaction/list', $this->data);

    }
    /*
    |--------------------------------------------------------------------------
    | list Page
    |--------------------------------------------------------------------------
    */
    public function transactionlist()
    {

        $this->load->model('transaction/Transaction_model');

        $total_amount = 0;
        $total_token_amount = 0;

        $draw = $this->input->get('draw');
        $start = $this->input->get('start');
        $length = $this->input->get('length');

        $type = $this->input->get('call_status');
        $clients = $this->input->get('client_filter');
        $from_date = $this->input->get('from_date') ? date('Y-m-d', strtotime($this->input->get('from_date'))) : '';
        $to_date = $this->input->get('to_date') ? date('Y-m-d', strtotime($this->input->get('to_date'))) : '';


        $data = array();
        $total_records = $this->Transaction_model->get_count($from_date, $to_date, $clients, $type);
        $users = $this->Transaction_model->get_info($length, $start, $from_date, $to_date, $clients, $type);


        $i = 0;
        foreach ($users as $user) {
            $i++;

            $status = "";

            if ($user['status'] == '0') {
                $status = "In-Active";
            }
            if ($user['status'] == '1') {
                $status = "Active";
            }
            if ($user['status'] == '2') {
                $status = "In-Active";
            }

            $user_info = $this->db->query("SELECT * FROM users where id = '" . $user['user_id'] . "' ")->row();
            $user_email = $user_info->email;
            $user_referral = $user_info->referral_id;

            $currency_price = 0;
            $token_amount = 0;

            if ($user['amount'] > 0) {
                $currency_amount = (float) str_replace(',', '', $user['amount']);
                $currency_price = currency_format($currency_amount);
            }

            if ($user['token_amount'] > 0) {
                $token_amount = (float) str_replace(',', '', $user['token_amount']);
                $token_amount = token_format($token_amount);
            }


            $entry_date = $user['date'];
            $description = $user['description'];

            if ($user['status'] == '1') {
                $status = "Active";
            } else {
                $status = "Pending";
            }

            $formattedType = str_replace('_', ' ', $user['type']);
            $formattedType = ucwords(strtolower($formattedType));
            $formattedType = ucfirst($formattedType);

            $type_symbol = '
        <div class="symbol symbol-40px me-3 mb-1">
        <span class="symbol-label bg-light-success">
        <i class="ki-duotone ki-flask fs-2 text-success"><span class="path1"></span><span class="path2"></span></i></span>
        </div>
        ';

            $currency_list = '<div class="d-flex align-items-center me-5">
        <div class="me-5">
        <a href="#" class="text-gray-800 fw-bold text-hover-primary fs-6">' . $currency_price . '</a>
        </div>
        </div>';

            $extra_dis = "";



            if ($user['type'] == 'binary_commission') {
                $extra_dis .= '<span class="text-gray-500 fw-semibold d-block fs-7 mb-1 mt-1">Total Left Inest <span class="badge badge-light-default  fs-8"> ' . token_format($user['total_left_invest']) . ' </span> </span>';
                $extra_dis .= '<span class="text-gray-500 fw-semibold d-block fs-7 mb-1">Total Right Inest  <span class="badge badge-light-default  fs-8"> ' . token_format($user['total_right_invest']) . '</span></span>';
                $extra_dis .= '<span class="text-gray-500 fw-semibold d-block fs-7 mb-1">Total Left Profit  <span class="badge badge-light-default  fs-8"> ' . token_format($user['total_left_roi']) . '</span></span>';
                $extra_dis .= '<span class="text-gray-500 fw-semibold d-block fs-7 mb-1">Total Right Profit <span class="badge badge-light-default  fs-8">  ' . token_format($user['total_right_roi']) . '</span></span>';
            }


            $default_icon = '<i class="ki-duotone ki-flask fs-2 text-success"><span class="path1"></span><span class="path2"></span></i>';
            $default_bg = 'bg-light-success';

            if ($user['type'] == 'binary_commission') {
                $default_icon = '<i class="ki-duotone ki-abstract-23 fs-2 text-primary"><span class="path1"></span><span class="path2"></span></i>';
                $default_bg = 'bg-light-primary';
            }

            if ($user['type'] == 'mining') {
                $default_icon = '<i class="ki-duotone ki-bookmark fs-2 text-info"><span class="path1"></span><span class="path2"></span></i>';
                $default_bg = 'bg-light-info';
            }

            if ($user['type'] == 'direct_commission') {
                $default_icon = '<i class="ki-duotone ki-profile-user fs-2 text-warning"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>';
                $default_bg = 'bg-light-warning';
            }

            if ($user['type'] == 'level_commission') {
                $default_icon = '<i class="ki-duotone ki-people fs-2 text-danger"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>';
                $default_bg = 'bg-light-danger';
            }

            if ($user['type'] == 'bonus') {
                $default_icon = '<i class="ki-duotone ki-percentage text-info fs-2"><span class="path1"></span><span class="path2"></span></i>';
                $default_bg = 'bg-light-info';
            }

            if ($user['type'] == 'internel_transfer_send') {
                $default_icon = '<i class="ki-duotone ki-wallet fs-2 text-danger"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>';
                $default_bg = 'bg-light-danger';
            }

            if ($user['type'] == 'internel_transfer_received') {
                $default_icon = '<i class="ki-duotone ki-wallet fs-2 text-info"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>';
                $default_bg = 'bg-light-info';
            }

            if ($user['type'] == 'internel_swap_send') {
                $default_icon = '<i class="ki-duotone ki-dollar fs-2 text-danger"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>';
                $default_bg = 'bg-light-danger';
            }

            if ($user['type'] == 'internel_swap_received') {
                $default_icon = '<i class="ki-duotone ki-dollar fs-2 text-info"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>';
                $default_bg = 'bg-light-info';
            }


            $type_symbol = '<div class="d-flex align-items-center me-5">
        <div class="symbol symbol-40px me-3">
        <span class="symbol-label  ' . $default_bg . ' ">
        ' . $default_icon . '
        </span>
        </div>
        <div class="me-5">
        <a href="#" class="text-gray-900 fw-bold text-hover-primary fs-6">' . $formattedType . '</a>
        <span class="fw-semibold fs-7 d-block text-start text-success ps-0 mt-1">' . $entry_date . '</span>   
        <span class="fw-semibold fs-7 d-block text-start text-gray-500 ps-0 mt-1 mb-1">' . $description . '</span>   
        ' . $extra_dis . '
        </div>
        </div>';

            $total_amount += str_replace(',', '', $user['amount']);
            $total_token_amount += str_replace(',', '', $user['token_amount']);


            $data[] = array(
                'RecordID' => $i,
                'UserInfo' => '<div class="d-flex align-items-center">
        <div class="symbol symbol-50px me-3">                                                   
        </div>
        <div class="d-flex justify-content-start flex-column">
        <a href="#" class="text-gray-800 fw-bold text-hover-primary mb-1 fs-6">' . $user_email . '</a>
        <span class="text-gray-500 fw-semibold d-block fs-7 mb-1">' . $user_referral . '</span>
        </div>
        </div>',
                'TransactionInfo' => '' . $type_symbol . '',
                'CurrencyInfo' => '' . $currency_list . '',
                'Status' => '<div class="d-flex align-items-center">
        <div class="symbol symbol-50px me-3">                                                   
        </div>
        <div class="d-flex justify-content-start flex-column">
        <a href="#" class="text-gray-800 fw-bold text-hover-primary mb-1 fs-6">' . $status . '</a>
        </div>
        </div>',
            );
        }

        $response = array(
            'draw' => intval($draw),
            'recordsTotal' => $total_records,
            'recordsFiltered' => $total_records,
            'data' => $data,
            "total_amount" => $total_amount,
            "total_token_amount" => $total_token_amount,
        );

        echo json_encode($response);
    }
    /*
    |--------------------------------------------------------------------------
    | list Page Amount
    |--------------------------------------------------------------------------
    */
    public function transactionlistAmount()
    {

        $this->load->model('transaction/Transaction_model');

        $total_amount = 0;
        $total_token_amount = 0;

        $type = $this->input->post('call_status');
        $clients = $this->input->post('client_filter');
        $from_date = $this->input->post('from_date') ? date('Y-m-d', strtotime($this->input->post('from_date'))) : '';
        $to_date = $this->input->post('to_date') ? date('Y-m-d', strtotime($this->input->post('to_date'))) : '';

        $data = array();
        $users = $this->Transaction_model->get_info_amt($from_date, $to_date, $clients, $type);

        $response = array(
            "total_amount" => $users[0]["mybalance"],
            "total_token_amount" => $users[0]["total_token_amount"],
        );

        echo json_encode($response);
    }

}
