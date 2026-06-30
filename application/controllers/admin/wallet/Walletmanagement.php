<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Walletmanagement extends CI_Controller
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
            if (empty($permissions['wallet_management'])) {
                $this->session->set_flashdata('error', 'Access Denied: You do not have permission.');
                redirect('admin');
            }
        }

    }
    /*
    |--------------------------------------------------------------------------
    | Bonus Page Index
    |--------------------------------------------------------------------------
    */
    public function index()
    {

        $this->data['title'] = "Add Wallet";
        $this->data['card_title'] = "Add balance to user wallet";
        $this->data['users'] = $this->db->query("SELECT * FROM users where status = '1' ")->result();
        $currency = currency_info()->coin_name;
        $this->data['default_currency'] = $currency;
        $token = token_info()->coin_name;
        $this->data['default_token'] = $token;
        $this->data['action'] = base_url() . "add-wallet-post";
        $this->data['redirect_url'] = base_url() . "add-wallet";
        $this->load->view('admin/wallet/wallet_management', $this->data);

    }
    /*
    |--------------------------------------------------------------------------
    | Detact Page Index
    |--------------------------------------------------------------------------
    */
    public function detact_wallet()
    {

        $this->data['title'] = "Detact Wallet";
        $this->data['card_title'] = "Detact balance to user wallet";
        $this->data['users'] = $this->db->query("SELECT * FROM users where status = '1' ")->result();
        $currency = currency_info()->coin_name;
        $this->data['default_currency'] = $currency;
        $token = token_info()->coin_name;
        $this->data['default_token'] = $token;
        $this->data['action'] = base_url() . "detact-wallet-post";
        $this->data['redirect_url'] = base_url() . "detect-wallet";
        $this->load->view('admin/wallet/wallet_management', $this->data);

    }
    /*
    |--------------------------------------------------------------------------
    | Wallet Detact Post 
    |--------------------------------------------------------------------------
    */
    public function wallet_detact_post()
    {

        if ($this->input->post()) {

            $selected_member = $this->input->post('selected_members', true);
            $selected_payment = $this->input->post('selected_payment', true);
            $notes_by_user = $this->input->post('notes_by_user', true);
            $amount = $this->input->post('bonus_amount', true);

            if ($selected_member <= 0 && $amount <= 0) {

                $response = array(
                    'status' => false,
                    'message' => "Invalide Request."
                );

            } else {

                if ($selected_payment == "currency") {
                    $this->detact_currency($selected_member, $amount, $notes_by_user);
                } else {
                    $this->detact_token($selected_member, $amount, $notes_by_user);
                }

            }

        } else {
            $response = array(
                'status' => false,
                'message' => "Invalide Request."
            );
        }

    }
    /*
    |--------------------------------------------------------------------------
    | Wallet Add Post 
    |--------------------------------------------------------------------------
    */
    public function wallet_add_post()
    {

        if ($this->input->post()) {

            $selected_member = $this->input->post('selected_members', true);
            $selected_payment = $this->input->post('selected_payment', true);
            $notes_by_user = $this->input->post('notes_by_user', true);
            $amount = $this->input->post('bonus_amount', true);

            if ($selected_member <= 0 && $amount <= 0) {

                $response = array(
                    'status' => false,
                    'message' => "Invalide Request."
                );

            } else {

                if ($selected_payment == "currency") {
                    $this->add_currency($selected_member, $amount, $notes_by_user);
                } else {
                    $this->add_token($selected_member, $amount, $notes_by_user);
                }

            }

        } else {
            $response = array(
                'status' => false,
                'message' => "Invalide Request."
            );
        }

    }
    /*
    |--------------------------------------------------------------------------
    | Add User Wallet Currency balance
    |--------------------------------------------------------------------------
    */
    private function add_currency($userid, $amount, $notes)
    {

        if ($userid > 0 && $amount > 0) {

            $token_info = token_info();
            $currency_info = currency_info();
            $token_amount = $amount * $token_info->currency_value;

            $deposit_data = array(
                "user_id" => $userid,
                "amount" => $amount,
                "type" => 'bonus',
                "date" => date('Y-m-d H:i:s'),
                "history_date" => date('Y-m-d H:i:s'),
                "status" => '1',
                'coin_type' => '1',
                "invest_id" => "",
                'hash_id' => 'admin bonus',
                "description" => $notes,
                "token_amount" => $token_amount,
                "coin_id" => $currency_info->id,
                "token_id" => $token_info->id,
            );

            $insert = $this->db->insert("history", $deposit_data);

            $response = array(
                'status' => true,
                'message' => "Bonus Added Successfully !!"
            );

        } else {

            $response = array(
                'status' => false,
                'message' => "Invalide Request."
            );

        }

        echo json_encode($response);

    }
    /*
   |--------------------------------------------------------------------------
   | Add User Wallet Token  balance
   |--------------------------------------------------------------------------
   */
    private function add_token($userid, $amount, $notes)
    {

        if ($userid > 0 && $amount > 0) {

            $token_info = token_info();
            $currency_info = currency_info();
            if ($amount >= $token_info->currency_value) {
                $site_balance = $amount / $token_info->currency_value;
            } else {
                $site_balance = '0';
            }


            $deposit_data = array(
                "user_id" => $userid,
                "amount" => $site_balance ? $site_balance : '0',
                "type" => 'bonus',
                "date" => date('Y-m-d H:i:s'),
                "history_date" => date('Y-m-d H:i:s'),
                "status" => '1',
                'coin_type' => '2',
                "invest_id" => "",
                'hash_id' => 'admin bonus',
                "description" => $notes,
                "token_amount" => $amount,
                "coin_id" => $currency_info->id,
                "token_id" => $token_info->id,
            );

            $insert = $this->db->insert("history", $deposit_data);

            $response = array(
                'status' => true,
                'message' => "Bonus Added Successfully !!"
            );

        } else {

            $response = array(
                'status' => false,
                'message' => "Invalide Request."
            );

        }

        echo json_encode($response);

    }

    /*
       |--------------------------------------------------------------------------
       | Detact User Wallet Currency balance
       |--------------------------------------------------------------------------
       */
    private function detact_currency($userid, $amount, $notes)
    {

        if ($userid > 0 && $amount > 0) {

            $token_info = token_info();
            $currency_info = currency_info();
            $token_amount = $amount * $token_info->currency_value;

            $deposit_data = array(
                "user_id" => $userid,
                "amount" => $amount,
                "type" => 'site_withdraw',
                "date" => date('Y-m-d H:i:s'),
                "history_date" => date('Y-m-d H:i:s'),
                "status" => '1',
                'coin_type' => '1',
                "invest_id" => "",
                'hash_id' => 'admin withdraw',
                "token_amount" => $token_amount,
                "description" => $notes,
                "coin_id" => $currency_info->id,
                "token_id" => $token_info->id,
            );

            $insert = $this->db->insert("history", $deposit_data);

            $response = array(
                'status' => true,
                'message' => "Wallet detacted successfully !!"
            );

        } else {

            $response = array(
                'status' => false,
                'message' => "Invalide Request."
            );

        }

        echo json_encode($response);

    }
    /*
   |--------------------------------------------------------------------------
   | Detact User Wallet Token  balance
   |--------------------------------------------------------------------------
   */
    private function detact_token($userid, $amount, $notes)
    {

        if ($userid > 0 && $amount > 0) {

            $token_info = token_info();
            $currency_info = currency_info();

            if ($amount >= $token_info->currency_value) {
                $site_amount = $amount / $token_info->currency_value;
            } else {
                $site_amount = '0';
            }


            $deposit_data = array(
                "user_id" => $userid,
                "amount" => $site_amount,
                "type" => 'site_withdraw',
                "date" => date('Y-m-d H:i:s'),
                "history_date" => date('Y-m-d H:i:s'),
                "status" => '1',
                'coin_type' => '2',
                "invest_id" => "",
                'hash_id' => 'admin withdraw',
                "description" => $notes,
                "token_amount" => $amount,
                "coin_id" => $currency_info->id,
                "token_id" => $token_info->id,
            );

            $insert = $this->db->insert("history", $deposit_data);

            $response = array(
                'status' => true,
                'message' => "Wallet detacted successfully !!"
            );

        } else {

            $response = array(
                'status' => false,
                'message' => "Invalide Request."
            );

        }

        echo json_encode($response);

    }
    /*
    |--------------------------------------------------------------------------
    | User Wallet balance
    |--------------------------------------------------------------------------
    */
    public function user_currency_balance($userid)
    {

        $check_balance = '0';

        if ($userid > 0) {
            $check_balance = site_wallet_balance($userid);
        } else {
            echo '0';
        }

        echo $check_balance;

    }
    /*
    |--------------------------------------------------------------------------
    | User Toekn balance
    |--------------------------------------------------------------------------
    */
    public function user_token_balance($userid)
    {

        $check_balance = '0';

        if ($userid > 0) {
            $check_balance = site_token_balance($userid);
        } else {
            echo '0';
        }

        echo $check_balance;

    }
    /*
    |--------------------------------------------------------------------------
    | User Toekn balance
    |--------------------------------------------------------------------------
    */
    public function user_wallet_balance($userid)
    {

        $check_currency_balance = '0';
        $check_token_balance = '0';
        $investment_balance = '0';

        if ($userid > 0) {
            $check_currency_balance = site_wallet_balance($userid);
            $check_token_balance = site_token_balance($userid);
            $investment_balance = investment_balance($userid);
        } else {
            echo '0';
        }

        $data = array(
            "currency_balance" => $check_currency_balance,
            "token_balance" => $check_token_balance,
            "investment_balance" => $investment_balance
        );

        echo json_encode($data);
    }
    /*
    |--------------------------------------------------------------------------
    | User Make investment
    |--------------------------------------------------------------------------
    */
    public function makeinvestment()
    {

        $this->data['title'] = "Make Investment";
        $this->data['card_title'] = "Add Investment To User";
        $this->data['users'] = $this->db->query("SELECT * FROM users where status = '1' ")->result();
        $this->data['package'] = $this->db->query("SELECT * FROM package_config WHERE status = '1'")->result();
        $this->data['action'] = base_url() . "make-investment-post";
        $this->data['redirect_url'] = base_url() . "make-investment";
        $this->load->view('admin/wallet/investment_management', $this->data);

    }
    /*
    |--------------------------------------------------------------------------
    | User Internel Transfer
    |--------------------------------------------------------------------------
    */
    public function internel_transfer()
    {

        $this->data['title'] = "Internel User to User Transfer";
        $this->data['card_title'] = "User to User Transfer Currency";
        $this->data['users'] = $this->db->query("SELECT * FROM users where status = '1' ")->result();
        $this->data['currency_info'] = currency_info();
        $this->data['token_info'] = currency_info();

        $currency = currency_info()->coin_name;
        $this->data['default_currency'] = $currency;
        $token = token_info()->coin_name;
        $this->data['default_token'] = $token;

        $this->data['action'] = base_url() . "internel-transfer-post";
        $this->data['redirect_url'] = base_url() . "internel-transfer";
        $this->load->view('admin/wallet/internel_transfer', $this->data);

    }
    /*
   |--------------------------------------------------------------------------
   | User Internel Transfer Validation
   |--------------------------------------------------------------------------
   */
    public function validate_transfer_balance()
    {

        $sender_id = $this->input->post('sender_id');
        $receiver_id = $this->input->post('receiver_id');
        $currency = $this->input->post('selected_coin');
        $amount = $this->input->post('amount');

        if (empty($sender_id) || empty($receiver_id) || empty($currency) || empty($amount)) {
            echo json_encode(['status' => false, 'message' => 'All fields are required']);
            return;
        }

        if ($currency == "currency") {
            $sender_balance = site_wallet_balance_without_format($sender_id);
        } else {
            $sender_balance = site_token_balance_without_format($sender_id);
        }

        if ($sender_balance < $amount) {
            echo json_encode(['status' => false, 'message' => 'Insufficient balance']);
            return;
        }

        echo json_encode(['status' => true]);
    }
    /*
   |--------------------------------------------------------------------------
   | User Internel Transfer Post Method
   |--------------------------------------------------------------------------
   */
    public function internel_transfer_post()
    {

        if ($this->input->post()) {

            $sender_id = $this->input->post('selected_members');
            $receiver_id = $this->input->post('selected_members_receiver');
            $selected_package = $this->input->post('selected_package');
            $currency = $this->input->post('selected_coin');
            $amount = $this->input->post('bonus_amount');
            $notes_by_user = $this->input->post('notes_by_user');
            $token_info = token_info();
            $currency_info = currency_info();

            $sender_info = $this->db->query("SELECT * FROM users where id = '" . $sender_id . "'")->row();
            $receiver_info = $this->db->query("SELECT * FROM users where id = '" . $receiver_id . "'")->row();

            if ($sender_id == $receiver_id) {
                echo json_encode(['status' => false, 'message' => 'Sender And Receiver are same users !']);
                return;
            }


            if ($sender_info->status != '1') {
                echo json_encode(['status' => false, 'message' => 'Sender is in-active !']);
                return;
            }


            if ($receiver_info->status != '1') {
                echo json_encode(['status' => false, 'message' => 'Receiver is in-active !']);
                return;
            }


            if ($currency == "currency") {
                $sender_balance = site_wallet_balance_without_format($sender_id);
                $earn_type = '1';
                $usd_price = $amount;
                $token_price = $amount * $token_info->currency_value;
                $type_discription = 'Currency';

            } else {
                $sender_balance = site_token_balance_without_format($sender_id);
                $earn_type = '2';
                $usd_price = $amount / $token_info->currency_value;
                $token_price = $amount;
                $type_discription = 'Token';
            }

            $type_1 = 'internel_transfer_send';
            $type_2 = 'internel_transfer_received';


            if ($sender_balance < $amount) {
                echo json_encode(['status' => false, 'message' => 'Insufficient balance']);
                return;
            }

            $uniq_id = 'TXN' . strtoupper(bin2hex(random_bytes(4))) . time();
            $transaction_id = "transaction_id_" . $uniq_id;

            if ($amount > 0) {

                $sender_data = array(
                    "user_id" => $sender_id,
                    "amount" => $usd_price,
                    "type" => $type_1,
                    "history_date" => date('Y-m-d H:i:s'),
                    "date" => date('Y-m-d H:i:s'),
                    "status" => '1',
                    "hash_id" => "admin-made",
                    "transaction_id" => $transaction_id,
                    "coin_type" => $earn_type,
                    "invest_id" => '0',
                    "token_amount" => $token_price,
                    "description" => "Internel Transfer " . $type_discription . " Send To " . $receiver_info->email . " ( " . $receiver_info->referral_id . " )",
                    "coin_id" => $currency_info->id,
                    "token_id" => $token_info->id,
                    "from_id" => $receiver_id,
                );
                $insert = $this->db->insert("history", $sender_data);

                $receiver_data = array(
                    "user_id" => $receiver_id,
                    "amount" => $usd_price,
                    "type" => $type_2,
                    "history_date" => date('Y-m-d H:i:s'),
                    "date" => date('Y-m-d H:i:s'),
                    "status" => '1',
                    "hash_id" => "admin-made",
                    "transaction_id" => $transaction_id,
                    "coin_type" => $earn_type,
                    "invest_id" => '0',
                    "token_amount" => $token_price,
                    "description" => "Internel Transfer " . $type_discription . " Received From " . $sender_info->email . " ( " . $sender_info->referral_id . " )",
                    "coin_id" => $currency_info->id,
                    "token_id" => $token_info->id,
                    "from_id" => $sender_id,
                );
                $insert = $this->db->insert("history", $receiver_data);

                echo json_encode(['status' => true, 'message' => 'Internel Transfer Successfully']);
                return;

            }

        }

    }
    /*
     |--------------------------------------------------------------------------
     | User Internel Swap
     |--------------------------------------------------------------------------
     */
    public function internel_swap()
    {

        $this->data['title'] = "Internel Swap Users";
        $this->data['card_title'] = "User Swap Currency";
        $this->data['users'] = $this->db->query("SELECT * FROM users where status = '1' ")->result();
        $this->data['currency_info'] = currency_info();
        $this->data['token_info'] = currency_info();
        $this->data['action'] = base_url() . "make-swap-post";
        $this->data['redirect_url'] = base_url() . "internel-swap";

        $currency = currency_info()->coin_name;
        $this->data['default_currency'] = $currency;
        $token = token_info()->coin_name;
        $this->data['default_token'] = $token;
        $this->data['value_text'] = "<b>" . token_info()->currency_value . " " . token_info()->coin_name . " = 1 USDT  </b> ";

        $this->load->view('admin/wallet/internel_swap', $this->data);

    }
    /*
    |--------------------------------------------------------------------------
    | User Internel Transfer Validation
    |--------------------------------------------------------------------------
    */
    public function validate_swap_balance()
    {

        $sender_id = $this->input->post('sender_id');
        $amount = $this->input->post('amount');
        $amount = str_replace(',', '', $amount);
        $amount = floatval($amount);

        if (empty($sender_id) || empty($amount)) {
            echo json_encode(['status' => false, 'message' => 'All fields are required']);
            return;
        }

        $sender_balance = site_token_balance_without_format($sender_id);

        if ($sender_balance < $amount) {
            echo json_encode(['status' => false, 'message' => 'Insufficient balance']);
            return;
        }

        $token_info = token_info();
        $usd_price = $amount / $token_info->currency_value;

        echo json_encode(['status' => true, 'message' => $usd_price]);
    }
    /*
    |--------------------------------------------------------------------------
    | Internel Swap Post
    |--------------------------------------------------------------------------
    */
    public function internel_swap_post()
    {

        if ($this->input->post()) {

            $sender_id = $this->input->post('selected_members');
            $amount = $this->input->post('bonus_amount');
            $notes_by_user = $this->input->post('notes_by_user');

            $token_info = token_info();
            $currency_info = currency_info();

            $sender_info = $this->db->query("SELECT * FROM users where id = '" . $sender_id . "'")->row();

            if ($sender_info->status != '1') {
                echo json_encode(['status' => false, 'message' => 'User is in-active !']);
                return;
            }

            $sender_balance = site_token_balance_without_format($sender_id);
            $usd_price = $amount / $token_info->currency_value;
            $token_price = $amount;
            $type_discription = "Token";

            $type_1 = 'internel_swap_send';
            $type_2 = 'internel_swap_received';


            if ($sender_balance < $amount) {
                echo json_encode(['status' => false, 'message' => 'Insufficient balance']);
                return;
            }

            $uniq_id = 'TXN' . strtoupper(bin2hex(random_bytes(4))) . time();
            $transaction_id = "transaction_id_" . $uniq_id;

            if ($amount > 0) {

                $sender_data = array(
                    "user_id" => $sender_id,
                    "amount" => $usd_price,
                    "type" => $type_1,
                    "history_date" => date('Y-m-d H:i:s'),
                    "date" => date('Y-m-d H:i:s'),
                    "status" => '1',
                    "hash_id" => "admin-made",
                    "transaction_id" => $transaction_id,
                    "coin_type" => '2',
                    "invest_id" => '0',
                    "token_amount" => $token_price,
                    "description" => "Internel Swap " . $type_discription . " Send",
                    "coin_id" => $currency_info->id,
                    "token_id" => $token_info->id,
                    "from_id" => $sender_id,
                );
                $insert = $this->db->insert("history", $sender_data);

                $receiver_data = array(
                    "user_id" => $sender_id,
                    "amount" => $usd_price,
                    "type" => $type_2,
                    "history_date" => date('Y-m-d H:i:s'),
                    "date" => date('Y-m-d H:i:s'),
                    "status" => '1',
                    "hash_id" => "admin-made",
                    "transaction_id" => $transaction_id,
                    "coin_type" => '1',
                    "invest_id" => '0',
                    "token_amount" => $token_price,
                    "description" => "Internel Swap Currency Received",
                    "coin_id" => $currency_info->id,
                    "token_id" => $token_info->id,
                    "from_id" => $sender_id,
                );
                $insert = $this->db->insert("history", $receiver_data);

                echo json_encode(['status' => true, 'message' => 'Internel Swap Successfully']);
                return;

            }

        }

    }
    /*
   |--------------------------------------------------------------------------
   | Deposit Amount Validation
   |--------------------------------------------------------------------------
   */
    public function validate_package_amount()
    {
        if ($this->input->post()) {
            $package_id = $this->input->post('package_id', true);
            $amount = floatval($this->input->post('amount', true));

            $query = $this->db->query("SELECT * FROM package_config WHERE id = ? AND status = '1'", [$package_id]);
            $package = $query->row();

            if (!$package) {
                echo json_encode([
                    'status' => false,
                    'message' => "Invalid package."
                ]);
                exit;
            }

            if ($amount >= $package->minimum && $amount <= $package->maximum) {
                echo json_encode([
                    'status' => true,
                    'message' => "Amount is valid."
                ]);
            } else {
                $minimum_amount = currency_format($package->minimum);
                $maximum_amount = currency_format($package->maximum);
                echo json_encode([
                    'status' => false,
                    'message' => "Amount must be between {$minimum_amount} and {$maximum_amount}."
                ]);
            }
            exit;
        }

        echo json_encode([
            'status' => false,
            'message' => "Invalid request."
        ]);
        exit;
    }
    /*
    |--------------------------------------------------------------------------
    | Deposit Amount Validation
    |--------------------------------------------------------------------------
    */
    public function makeinvestment_post()
    {

        if ($this->input->post()) {

            $user_id = $this->input->post('selected_members');
            $selected_package = $this->input->post('selected_package');
            $bonus_amount = $this->input->post('bonus_amount');
            $notes_by_user = $this->input->post('notes_by_user');
            $invest_date = $this->input->post('invest_date');
            $token_info = token_info();
            $currency_info = currency_info();

            if ($selected_package > 0) {

                if ($bonus_amount > 0) {

                    $package_id = $this->input->post('selected_package');

                    $query = $this->db->query("SELECT * FROM package_config WHERE id = ? AND status = '1'", [$package_id]);
                    $package = $query->row();

                    if (!$package) {
                        echo json_encode([
                            'status' => false,
                            'message' => "Invalid package."
                        ]);
                        exit;
                    }

                    if ($bonus_amount >= $package->minimum && $bonus_amount <= $package->maximum) {

                        $days_count = $package->days_duration;

                        if ($invest_date == "") {
                            $rundate = date('Y-m-d', strtotime(' +1 day'));
                            $maturedate = date('Y-m-d  H:i:s', strtotime(' +' . $days_count . ' day'));
                            $ending_date = date('Y-m-d H:i:s', strtotime(' +' . $days_count . ' day'));
                            $invest_date = date('Y-m-d H:i:s');
                        } else {
                            $rundate = date('Y-m-d', strtotime($invest_date . ' +1 day'));
                            $maturedate = date('Y-m-d H:i:s', strtotime($invest_date . ' +' . $days_count . ' day'));
                            $ending_date = date('Y-m-d H:i:s', strtotime($invest_date . ' +' . $days_count . ' day'));
                        }


                        $csq_price = '0';
                        $csq_deposit = '0';
                        $earn_type = '2';

                        $csq_price = $token_info->currency_value;
                        $csq_deposit = $bonus_amount * $token_info->currency_value;

                        if ($package->roi_made_by == "token") {
                            $earn_type = '2';
                        } else {
                            $earn_type = '1';
                        }

                        $insert_data = array(
                            "user_id" => $user_id,
                            "invest_amount" => $bonus_amount,
                            "invest_network" => "BSC",
                            "status" => '1',
                            "created_date" => date('Y-m-d H:i:s', strtotime($invest_date)),
                            "days_count" => $days_count,
                            "profit" => $package->roi,
                            "hash_id" => "admin-made",
                            "run_date" => date('Y-m-d', strtotime($rundate)),
                            "starting_date" => date('Y-m-d  H:i:s', strtotime($invest_date)),
                            "ending_date" => $ending_date,
                            "mature_date" => $maturedate,
                            "type" => "mining",
                            "req_method" => "admin",
                            "approve_status" => '1',
                            "package_id" => $package_id,
                            "csq_price" => $csq_price,
                            "csq_deposit" => $csq_deposit,
                            "earn_by" => $package->roi_made_by,
                            "currency_id" => $currency_info->id,
                            "token_id" => $token_info->id,
                        );

                        $insert = $this->db->insert("user_investment", $insert_data);
                        $invest_id = $this->db->insert_id();

                        if ($invest_id) {

                            $deposit_data = array(
                                "user_id" => $user_id,
                                "amount" => $bonus_amount,
                                "type" => "mining",
                                "history_date" => date('Y-m-d H:i:s', strtotime($invest_date)),
                                "date" => date('Y-m-d H:i:s', strtotime($invest_date)),
                                "status" => '1',
                                "hash_id" => "admin-made",
                                "invest_id" => $invest_id,
                                "token_amount" => $csq_deposit,
                                "description" => "Investment Successfully ( " . $package->package_name . " )",
                                "coin_id" => $currency_info->id,
                                "token_id" => $token_info->id,
                            );
                            $insert = $this->db->insert("history", $deposit_data);


                            $this->load->model('finance/CommissionEngine_model', 'CommissionEngine');

                            // Update user package_id right after investment
                            $this->db->where('id', (int) $user_id)
                                ->update('users', ['package_id' => (int) $package_id]);

                            // Process commissions
                            $this->CommissionEngine->process_investment([
                                'user_id' => (int) $user_id,
                                'package_id' => (int) $package_id,
                                'invest_id' => (int) $invest_id,
                                'amount' => (float) $bonus_amount,
                                'csq_price' => (float) $csq_price,
                                'csq_deposit' => (float) $csq_deposit,
                                'earn_type' => (string) $earn_type, // '1'=currency, '2'=token
                                'invest_date' => date('Y-m-d H:i:s', strtotime($invest_date)),
                                'note' => "Investment Commissions (" . $package->package_name . ")"
                            ]);

                            echo json_encode([
                                'status' => TRUE,
                                'message' => "Package Investment Successfully"
                            ]);
                            exit();

                        } else {

                            echo json_encode([
                                'status' => false,
                                'message' => "investment is faild."
                            ]);
                            exit;
                        }

                    } else {

                        $minimum_amount = currency_format($package->minimum);
                        $maximum_amount = currency_format($package->maximum);
                        echo json_encode([
                            'status' => false,
                            'message' => "Amount must be between {$minimum_amount} and {$maximum_amount}."
                        ]);
                        exit;

                    }

                } else {

                    echo json_encode([
                        'status' => true,
                        'message' => "invalid amount."
                    ]);
                    exit();
                }

            } else {

                echo json_encode([
                    'status' => true,
                    'message' => "invalid package."
                ]);
                exit();
            }


        } else {

            echo json_encode([
                'status' => true,
                'message' => "invalid request."
            ]);
            exit();
        }

    }
    /*
    |--------------------------------------------------------------------------
    | Deposit Verify Admin
    |--------------------------------------------------------------------------
    */
    public function verify_investment()
    {

        $this->data['title'] = "Verify Investment";
        $this->data['card_tilte'] = "Investment Request List";
        $this->load->view('admin/wallet/request_investment_list', $this->data);

    }
    /*
    |--------------------------------------------------------------------------
    | Deposit Verify List Admin
    |--------------------------------------------------------------------------
    */
    public function verify_investment_list()
    {

        $this->load->model('wallet/Investment_model');
        $draw = $this->input->get('draw');
        $start = $this->input->get('start');
        $length = $this->input->get('length');

        $data = array();
        $total_records = $this->Investment_model->get_count();
        $users = $this->Investment_model->get_info($length, $start);


        $i = 0;
        foreach ($users as $user) {
            $i++;



            $currency_status = $user['status'] ? "checked" : "";
            $change_status_url = base_url() . "announcement-status-update-cms/" . $user['id'];
            $userinfo = $this->db->query("SELECT * FROM users where id = '" . $user['user_id'] . "' ")->row();
            $package_info = $this->db->query("SELECT * FROM `package_config` where id = '" . $user['package_id'] . "' ")->row();

            $today = date("Y-m-d H:i:s");
            $start = new DateTime($user['starting_date']);
            $mature = new DateTime($user['mature_date']);
            $now = new DateTime($today);
            $verify_link = "https://bscscan.com/tx/" . $user['hash_id'];

            $remaining_days = $now->diff($mature)->days;

            $data[] = array(
                'RecordID' => $i,
                'UserInfo' => '<div class="d-flex align-items-center">
        <div class="symbol symbol-50px me-3">                                                   
        </div>
        <div class="d-flex justify-content-start flex-column">
        <a href="#" class="text-gray-800 fw-bold text-hover-primary mb-1 fs-6">' . $userinfo->email . '</a>
        <span class="text-gray-500 fw-semibold d-block fs-7">' . $userinfo->referral_id . '</span>
        </div>
        </div>',
                'InvestInfo' => '<div class="d-flex align-items-center">
        <div class="symbol symbol-50px me-3"></div>
        <div class="d-flex justify-content-start flex-column">
        <a href="#" class="text-gray-800 fw-bold text-hover-primary mb-1 fs-6">'
                    . htmlspecialchars($package_info->package_name, ENT_QUOTES, 'UTF-8') . ' - Package</a>
        <span class="me-2 text-gray-600 fw-bold d-block fs-6">'
                    . currency_format($user['invest_amount']) . ' - ' . $package_info->days_duration . ' days
        </span> 
        </div>
        </div>',
                'DateInfo' => '<div class="d-flex align-items-center">
        <div class="symbol symbol-50px me-3">                                                   
        </div>
        <div class="d-flex justify-content-start flex-column">
        <span class="text-gray-800 fw-bold text-hover-primary mb-1 fs-6">' . $remaining_days . ' -  days Remaining</span>
        <a href="#" class="fs-7 text-muted fw-bold mb-1 fs-6">' . $user['starting_date'] . ' - ' . $user['mature_date'] . '</a>
        </div>
        </div>',
                'EndDate' => '<div class="d-flex justify-content-start flex-column">
        <div class="d-flex justify-content-start ">
         <a class="btn btn-info btn-active-light-info btn-sm text-center me-4" href="' . $verify_link . '">
		 <i class="fa-solid fa-eye  "></i> View
		</a>
        </div>
        </div>',
                'temp_content' => '<div class="d-flex justify-content-center flex-row">
        <a class="btn btn-success btn-active-light-success btn-sm btn-approve text-center me-4"  data-approve-url="' . base_url() . 'approve-investment/' . $user['id'] . '">
		 <i class="fa fa-check" aria-hidden="true"></i> Approve
		</a>
        <a class="btn btn-danger btn-active-light-danger btn-sm text-center btn-delete" href="javascript:void(0);"  data-reject-url="' . base_url() . 'reject-investment/' . $user['id'] . '">
		 <i class="fa fa-ban" aria-hidden="true"></i>  Reject
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
    | Deposit Verify Function
    |--------------------------------------------------------------------------
    */
    public function approve_investment($id)
    {

        if ($id) {

            $check_template = $this->db->query("SELECT * FROM `user_investment` where id = '" . $id . "'")->num_rows();

            if ($check_template > 0) {

                $template_status = '1';

                $array_template = array(
                    "approve_status" => $template_status,
                );

                $this->db->where('id', $id);
                $this->db->update('user_investment', $array_template);

                $response = array(
                    'status' => "success",
                    'message' => "Investment Verification Successfully."
                );
                echo json_encode($response);
                exit();
            } else {
                $response = array(
                    'status' => false,
                    'message' => "Invalide investment!"
                );
                echo json_encode($response);
                exit();
            }

        }

    }
    /*
    |--------------------------------------------------------------------------
    | Deposit Verify Function
    |--------------------------------------------------------------------------
    */
    public function reject_investment($id)
    {

        if ($id) {

            $check_template = $this->db->query("SELECT * FROM `user_investment` where id = '" . $id . "'")->num_rows();

            if ($check_template > 0) {

                $template_status = '2';

                $array_template = array(
                    "approve_status" => $template_status,
                );

                $this->db->where('id', $id);
                $this->db->update('user_investment', $array_template);

                $response = array(
                    'status' => "success",
                    'message' => "Investment Rejected Successfully."
                );
                echo json_encode($response);
                exit();
            } else {
                $response = array(
                    'status' => false,
                    'message' => "Invalide investment!"
                );
                echo json_encode($response);
                exit();
            }

        }

    }
    /*
    |--------------------------------------------------------------------------
    | Investment List
    |--------------------------------------------------------------------------
    */
    public function investmentlist()
    {

        $this->data['title'] = "Investment History";
        $this->data['card_tilte'] = "Investment  List";
        $this->load->view('admin/wallet/investment-list', $this->data);

    }
    /*
    |--------------------------------------------------------------------------
    | Deposit Verify List Admin
    |--------------------------------------------------------------------------
    */
    public function investment_list_get()
    {

        $this->load->model('wallet/Investment_model');
        $draw = $this->input->get('draw');
        $start = $this->input->get('start');
        $length = $this->input->get('length');

        $type = $this->input->get('call_status');
        $clients = $this->input->get('client_filter');
        $from_date = $this->input->get('from_date') ? date('Y-m-d', strtotime($this->input->get('from_date'))) : '';
        $to_date = $this->input->get('to_date') ? date('Y-m-d', strtotime($this->input->get('to_date'))) : '';


        $data = array();
        $total_records = $this->Investment_model->get_count_invest($from_date, $to_date, $clients, $type);
        $users = $this->Investment_model->get_info_invest($length, $start, $from_date, $to_date, $clients, $type);


        $i = 0;
        foreach ($users as $user) {
            $i++;

            $currency_status = $user['status'];
            $reinvest_status = $user['reinvest_status'] ? "checked" : "";
            $change_status_url = base_url() . "package-reinvest-status/" . $user['id'];
            $userinfo = $this->db->query("SELECT * FROM users where id = '" . $user['user_id'] . "' ")->row();
            $package_info = $this->db->query("SELECT * FROM `package_config` where id = '" . $user['package_id'] . "' ")->row();


            $verify_link = "https://bscscan.com/tx/" . $user['hash_id'];


            $today = date("Y-m-d H:i:s");
            $start = new DateTime($user['starting_date']);
            $mature = new DateTime($user['mature_date']);
            $now = new DateTime($today);

            $remaining_days = $now->diff($mature)->days;

            if ($now > $mature) {
                $remaining_days = 0;
            }

            if ($currency_status == "1") {
                $package_status = '';
                $package_color = 'text-gray-800';
                $delete_disabled = '';
            } else {
                $package_status = 'disabled';
                $package_color = 'text-danger';
                $delete_disabled = 'disabled';
            }

            $package_apporved = $user['approve_status'] ? '1' : '0';

            if ($package_apporved) {
                $package_approve_button = '';
            } else {
                $package_approve_button = '<a class="btn btn-success btn-active-light-success btn-sm btn-approve text-center me-4"  data-approve-url="' . base_url() . 'approve-investment/' . $user['id'] . '">
		 <i class="fa fa-check" aria-hidden="true"></i> Approve
		</a>';
            }


            $data[] = array(
                'RecordID' => $i,
                'UserInfo' => '<div class="d-flex align-items-center">
        <div class="symbol symbol-50px me-3">                                                   
        </div>
        <div class="d-flex justify-content-start flex-column">
        <a href="#" class="' . $package_color . ' fw-bold text-hover-primary mb-1 fs-6">' . $userinfo->email . '</a>
        <span class="text-gray-500 fw-semibold d-block fs-7">' . $userinfo->referral_id . '</span>
        </div>
        </div>',
                'InvestInfo' => '<div class="d-flex align-items-center">
        <div class="symbol symbol-50px me-3"></div>
        <div class="d-flex justify-content-start flex-column">
        <a href="#" class="' . $package_color . ' fw-bold text-hover-primary mb-1 fs-6">'
                    . htmlspecialchars($package_info->package_name, ENT_QUOTES, 'UTF-8') . ' - Package</a>
        <span class="me-2 text-gray-600 fw-bold d-block fs-6">'
                    . currency_format($user['invest_amount']) . ' - ' . $package_info->days_duration . ' days
        </span> 
        </div>
        </div>',
                'DateInfo' => '<div class="d-flex align-items-center">
        <div class="symbol symbol-50px me-3">                                                   
        </div>
        <div class="d-flex justify-content-start flex-column">
        <span class="' . $package_color . ' fw-bold text-hover-primary mb-1 fs-6">' . $user['days_count'] . ' -  days Remaining</span>
        <a href="#" class="fs-7 text-muted fw-bold mb-1 fs-6">' . $user['starting_date'] . ' - ' . $user['mature_date'] . '</a>
        </div>
        </div>',
                'EndDate' => '<div class="form-check form-switch form-check-custom form-check-success form-check-solid">
        <input class="form-check-input h-30px w-50px template_status" type="checkbox" ' . $package_status . ' value="1" name="template_status"' .
                    $reinvest_status . '
        id="template_status" 
        data-payment="' . $user['id'] . '" 
        data-template_status-url="' . $change_status_url . '"/>
        <label class="form-check-label" for="template_status">
        </label>
        </div>',
                'temp_content' => '<div class="d-flex justify-content-center flex-row">
        <a class="btn btn-info btn-active-light-info btn-sm  text-center me-4" href="' . base_url() . 'investment-info/' . $user['id'] . '">
         <i class="fa fa-eye" aria-hidden="true"></i> View More
        </a>
        ' . $package_approve_button . '
        <a class="btn btn-danger btn-active-light-danger btn-sm text-center btn-delete ' . $delete_disabled . '" href="javascript:void(0);" data-reject-url="' . base_url() . 'delete-investment/' . $user['id'] . '" ' . $delete_disabled . '>
         <i class="fa fa-trash" aria-hidden="true"></i> Delete 
        </a>
        </div>
		',
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
    | Package Reinvestment Status
    |--------------------------------------------------------------------------
    */
    public function package_reinvest_status($id)
    {

        if ($id) {

            $check_template = $this->db->query("SELECT * FROM `user_investment` where id = '" . $id . "' and status = '1' ")->num_rows();

            if ($check_template > 0) {

                $status = $this->input->post('reinvest_status');
                $template_status = $status == '1' ? '1' : '0';

                $array_template = array(
                    "reinvest_status" => $template_status,
                );

                $this->db->where('id', $id);
                $this->db->update('user_investment', $array_template);

                $response = array(
                    'status' => "success",
                    'message' => "Status update successfully.."
                );
                echo json_encode($response);
                exit();
            } else {
                $response = array(
                    'status' => false,
                    'message' => "Invalid investment or investment is matured !"
                );
                echo json_encode($response);
                exit();
            }

        }
    }
    /*
   |--------------------------------------------------------------------------
   | Package Delete
   |--------------------------------------------------------------------------
   */
    public function investment_delete($id)
    {

        if ($id) {

            $check_template = $this->db->query("SELECT * FROM `user_investment` where id = '" . $id . "' and status = '1' ")->num_rows();

            if ($check_template > 0) {

                /****************************** CHECK REINVESTED PACKAGE ******************************/
                $check_already_invested = $this->db->query("SELECT * FROM `user_investment` where id = '" . $id . "' and reinvest_id != '0' ")->row();

                if ($check_already_invested) {

                    $reinvested_info = $this->db->query("SELECT * FROM `user_investment` where id = '" . $check_already_invested->reinvest_id . "' ")->row();

                    $query = $this->db->query("SELECT * FROM package_config WHERE id = ? AND status = '1'", [$reinvested_info->package_id]);
                    $package = $query->row();

                    $deposit_data = array(
                        "user_id" => $reinvested_info->user_id,
                        "amount" => $reinvested_info->invest_amount,
                        "type" => "release_deposit",
                        "history_date" => date('Y-m-d H:i:s', strtotime($reinvested_info->mature_date)),
                        "date" => date('Y-m-d H:i:s', strtotime($reinvested_info->mature_date)),
                        "status" => '1',
                        "hash_id" => $reinvested_info->hash_id,
                        "invest_id" => $reinvested_info->id,
                        "token_amount" => $reinvested_info->csq_deposit,
                        "description" => "Package Matured Successfully ( " . $package->package_name . " )",
                        "coin_type" => '2',
                        "coin_id" => $reinvested_info->currency_id,
                        "token_id" => $reinvested_info->token_id,
                    );
                    $this->db->insert("history", $deposit_data);

                    /******************* INVESTMENT */
                    $this->db->where('id', $id);
                    $this->db->delete('user_investment');
                    /******************* HISTORY */
                    $this->db->where('invest_id', $id);
                    $this->db->delete('history');

                } else {

                    /******************* INVESTMENT */
                    $this->db->where('id', $id);
                    $this->db->delete('user_investment');
                    /******************* HISTORY */
                    $this->db->where('invest_id', $id);
                    $this->db->delete('history');

                }


                $response = array(
                    'status' => "success",
                    'message' => "Investment Deleted successfully.."
                );
                echo json_encode($response);
                exit();

            } else {
                $response = array(
                    'status' => false,
                    'message' => "Invalid investment or investment is matured !"
                );
                echo json_encode($response);
                exit();
            }

        }
    }
    /*
    |--------------------------------------------------------------------------
    | list Investment Amount
    |--------------------------------------------------------------------------
    */
    public function investment_amount_fetch()
    {

        $this->load->model('wallet/Investment_model');

        $total_amount = 0;
        $total_token_amount = 0;

        $type = $this->input->post('call_status');
        $clients = $this->input->post('client_filter');
        $from_date = $this->input->post('from_date') ? date('Y-m-d', strtotime($this->input->post('from_date'))) : '';
        $to_date = $this->input->post('to_date') ? date('Y-m-d', strtotime($this->input->post('to_date'))) : '';

        $data = array();
        $users = $this->Investment_model->get_info_amt($from_date, $to_date, $clients, $type);

        $i = 0;
        foreach ($users as $user) {
            $i++;

            $total_amount += str_replace(',', '', $user['invest_amount']);
            $total_token_amount += str_replace(',', '', $user['csq_deposit']);
        }

        $response = array(
            "total_amount" => $total_amount,
            "total_token_amount" => $total_token_amount,
        );

        echo json_encode($response);
    }
    /*
    |--------------------------------------------------------------------------
    | View Investment Info
    |--------------------------------------------------------------------------
    */
    public function investment_info($id)
    {

        if ($id > 0) {

            $wallet_info = $this->db->query("SELECT * FROM user_investment where id = '" . $id . "' ")->row();

            if ($wallet_info) {

                $this->data['title'] = "Investment Info";
                $this->data['card_tilte'] = "Investment  Info";
                $this->data['user_id'] = $wallet_info->user_id;
                $user_info = $this->db->query("SELECT * FROM users where id = '" . $wallet_info->user_id . "' ")->row();
                $this->data['currency_info'] = currency_info();
                $this->data['token_info'] = token_info();


                $today = date("Y-m-d H:i:s");
                $start = new DateTime($wallet_info->starting_date);
                $mature = new DateTime($wallet_info->mature_date);
                $now = new DateTime($today);
                $remaining_days = $now->diff($mature)->days;


                $check_hash = $this->db->query("SELECT * FROM history where invest_id = '" . $id . "' and type='mining' ")->row();

                if ($check_hash->hash_id == "admin-made") {
                    $verify_link = "----";
                    $payment_by = "Admin Made";
                } else {
                    $verify_link = '<a href=https://bscscan.com/tx/' . $check_hash->hash_id . ">View BSC Scan </a>";
                    $payment_by = "BSC SCAN";
                }


                $package_info = $this->db->query("SELECT * FROM `package_config` where id = '" . $wallet_info->package_id . "' ")->row();

                if ($package_info->maximum > 0) {
                    $maximum = currency_format($package_info->maximum);
                } else {
                    $maximum = "Unlimited";
                }

                $dateinfo = '<label class="w-150px mb-1">' . $wallet_info->days_count . '  -  days Remaining </label>
                 <div class="fw-normal text-gray-600"> Created Date : ' . $wallet_info->starting_date . '</div>
                 <div class="fw-normal text-gray-600"> Mature Date : ' . $wallet_info->mature_date . '</div>';

                $total_earnings_cu_get = $this->db->query("SELECT SUM(amount) as currency_amount FROM `history` where type = 'profit' and invest_id = '" . $id . "' and user_id = '" . $wallet_info->user_id . "' ")->row()->currency_amount;
                $total_earnings_token = $this->db->query("SELECT SUM(token_amount) as token_amount FROM `history` where type = 'profit' and invest_id = '" . $id . "' and user_id = '" . $wallet_info->user_id . "'  ")->row()->token_amount;



                if ($wallet_info->status == "1") {
                    $invest_status = '<span class="text-success fw-semibold"> <span class="p-5"> Active </span></span>';
                } else {
                    $invest_status = '<span class="text-danger fw-semibold"> <span class="p-5"> Matured </span></span>';
                }

                $this->data['dateinfo'] = $dateinfo;

                $this->data['username'] = $user_info->username;
                $this->data['useremail'] = $user_info->email;
                $this->data['userreferralid'] = $user_info->referral_id;
                $this->data['min_max_package'] = "Minimum : " . currency_format($package_info->minimum) . " -  Maxmum : " . $maximum;
                $this->data['packagename'] = $package_info->package_name ? $package_info->package_name : "Package Removed";
                $this->data['packageamount'] = currency_format($wallet_info->invest_amount);
                $this->data['packagetokenamount'] = token_format($wallet_info->csq_deposit);
                $this->data['pacakgeduration'] = $wallet_info->days_duration . " Days";
                $this->data['paymenturl'] = $verify_link;
                $this->data['remaining_days'] = $remaining_days;
                $this->data['payment_by'] = $payment_by;
                $this->data['invest_status'] = $invest_status;

                $this->data['total_earnings_currency'] = currency_format($total_earnings_cu_get);
                $this->data['total_earnings_token'] = token_format($total_earnings_token);

                $this->data['invest_id'] = $id;
                $this->load->view('admin/wallet/investment-info', $this->data);

            }


        } else {
            redirect('list-investment');
        }


    }
    /*
    |--------------------------------------------------------------------------
    | View Investment Profit
    |--------------------------------------------------------------------------
    */
    public function list_profit()
    {

        $this->load->model('transaction/Transaction_model');

        $total_amount = 0;
        $total_token_amount = 0;

        $draw = $this->input->get('draw');
        $start = $this->input->get('start');
        $length = $this->input->get('length');

        $type = 'profit';
        $invest_id = $this->input->get('invest_id');
        ;
        $clients = $this->input->get('client_filter');
        $from_date = $this->input->get('from_date') ? date('Y-m-d', strtotime($this->input->get('from_date'))) : '';
        $to_date = $this->input->get('to_date') ? date('Y-m-d', strtotime($this->input->get('to_date'))) : '';

        $data = array();
        $total_records = $this->Transaction_model->get_count_profit($from_date, $to_date, $clients, $type, $invest_id);
        $users = $this->Transaction_model->get_info_profit($length, $start, $from_date, $to_date, $clients, $type, $invest_id);


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
        <span class="fw-semibold fs-7 d-block text-start text-success ps-0">' . $token_amount . '</span>   
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


            if ($user['type'] == 'profit') {
                $default_icon = '<i class="ki-duotone ki-percentage text-info fs-2"><span class="path1"></span><span class="path2"></span></i>';
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
    | list Investment Amount
    |--------------------------------------------------------------------------
    */
    public function profit_amount_fetch()
    {

        $this->load->model('transaction/Transaction_model');

        $total_amount = 0;
        $total_token_amount = 0;

        $type = 'profit';
        $invest_id = $this->input->post('invest_id');
        $clients = $this->input->post('client_filter');
        $from_date = $this->input->post('from_date') ? date('Y-m-d', strtotime($this->input->post('from_date'))) : '';
        $to_date = $this->input->post('to_date') ? date('Y-m-d', strtotime($this->input->post('to_date'))) : '';
        $users = $this->Transaction_model->get_info_amtprofit($from_date, $to_date, $clients, $type, $invest_id);

        $data = array();

        $i = 0;
        foreach ($users as $user) {
            $i++;


            if ($user['amount'] > 0) {
                $total_amount += str_replace(',', '', $user['amount']);
            }

            if ($user['token_amount'] > 0) {
                $total_token_amount += str_replace(',', '', $user['token_amount']);
            }



        }

        $response = array(
            "total_amount" => $total_amount,
            "total_token_amount" => $total_token_amount,
        );

        echo json_encode($response);
    }
}