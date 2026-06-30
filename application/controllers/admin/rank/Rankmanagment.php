<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Rankmanagment extends MY_Controller
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
            if (empty($permissions['rank_management'])) {
                $this->session->set_flashdata('error', 'Access Denied: You do not have permission.');
                redirect('admin');
            }
        }

        $this->load->model('rank_model');

    }
    /*
    |--------------------------------------------------------------------------
    | Rank List View
    |--------------------------------------------------------------------------
    */
    public function index()
    {
        $this->data['title'] = 'Rank Settings';
        $this->data['card_title'] = 'Rank Settings List';
        $this->load->view('admin/settings/rank-settings', $this->data);
    }
    /*
    |--------------------------------------------------------------------------
    | Rank Edi View
    |--------------------------------------------------------------------------
    */
    public function token_add()
    {

        $this->data['title'] = 'Rank Settings';
        $this->data['card_title'] = 'Add Rank Settings';
        $this->data['rank_id'] = 0;
        $this->data['rank_name'] = "";
        $this->data['left_leg_investment'] = "";
        $this->data['right_leg_investment'] = "";
        $this->data['currency_info'] = currency_info();
        $this->data['action'] = base_url() . "rank-update";
        $this->data['redirect'] = base_url() . "rank-settings";
        $this->load->view('admin/settings/edit-rank-settings', $this->data);

    }
    /*
    |--------------------------------------------------------------------------
    | Rank List
    |--------------------------------------------------------------------------
    */
    public function list()
    {


        $this->load->model('rank/rank_model');

        $draw = $this->input->get('draw');
        $start = $this->input->get('start');
        $length = $this->input->get('length');
        $search = $this->input->get('search')['value'];

        $data = array();
        $users = $this->rank_model->get_info($length, $start, $search);
        $total_records = $this->rank_model->get_count($search);

        $token_info = token_info();


        $i = 0;
        foreach ($users as $user) {
            $i++;
            $call_status_class = 'badge-light-danger';
            $call_status = 'Not Interested';

            $currency_status = $user['rank_status'] ? "checked" : "";
            $decimal = isset($user['decimal']) ? $user['decimal'] : 0;
            $rank_name = isset($user['rank_name']) ? $user['rank_name'] : "no mention";

            $left_leg_investment = isset($user['left_leg_investment']) ? str_replace(',', '', $user['left_leg_investment']) : "0";
            $right_leg_investment = isset($user['right_leg_investment']) ? str_replace(',', '', $user['right_leg_investment']) : "0";
            $check_user_rank_count = $this->db->query("SELECT * FROM users where rank_id = '" . $user['id'] . "' and status = '1' ")->num_rows();
            $change_status_url = base_url() . "rank-status/" . $user['id'];

            $currency_info = currency_info();
            $rank_bonus_type = $user['rank_bonus_type'] ? '%' : $currency_info->currency_symbol;
            $rank_bonus = $user['rank_bonus'] ? $user['rank_bonus'] . ' ' . $rank_bonus_type : '0';

            $data[] = array(
                'RecordID' => $i,
                'RankName' => '<div class="d-flex align-items-center">
        <div class="d-flex justify-content-start flex-column">
        <a href="#" class="text-gray-800 fw-bold text-hover-primary mb-1 fs-6">' . $rank_name . '</a>
        </div>
        </div>',
                'RankAmount' => '<div class="symbol symbol-50px me-3 mb-2">  <span class="text-gray-800 fw-bold text-hover-primary mb-2 fs-6">' . currency_format($left_leg_investment) . ' </span> </div>',
                'RankStatus' => '<div class="form-check form-switch form-check-custom form-check-success form-check-solid">
        <input class="form-check-input h-30px w-50px currency_status" type="checkbox" value="" name="currency_status"' .
                    $currency_status . '
        id="currency_status" 
        data-payment="' . $user['id'] . '" 
        data-rank_status-url="' . $change_status_url . '"/>
        <label class="form-check-label" for="currency_status">
        </label>
        </div>',
                'RankBonus' => $rank_bonus,
                'Count' => $check_user_rank_count,
                'paymentid' => $user['id']
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
    | Rank Details Update
    |--------------------------------------------------------------------------
    */
    public function rank_update()
    {

        $id = $this->input->post('currency_id');


        $this->form_validation->set_rules(
            'rank_name',
            'Rank Name',
            'required|regex_match[/^[A-Za-z\s]+$/]',
            array(
                'required' => 'The Rank Name is required.',
                'regex_match' => 'The Rank Name must only contain letters.'
            )
        );

        // $this->form_validation->set_rules('left_leg_investment', 'Left Leg Investment', 'required|regex_match[/^\d+(\.\d+)?$/]', 
        //     array(
        //         'required' => 'The DecLeft Leg Investmentimal Value is required.',
        //         'regex_match' => 'Only numbers and decimal values are allowed.'
        //     )
        // );

        // $this->form_validation->set_rules('right_leg_investment', 'Right Leg Investment', 'required|regex_match[/^\d+(\.\d+)?$/]', 
        // array(
        //     'required' => 'The DecLeft Leg Investmentimal Value is required.',
        //     'regex_match' => 'Only numbers and decimal values are allowed.'
        // )
        // );

        if ($id) {

            if ($id == '1') {
                $this->form_validation->set_rules(
                    'rank_eligibel_amt',
                    'Rank Eligible Amount',
                    'required|regex_match[/^\d+(\.\d+)?$/]',
                    array(
                        'required' => 'The Rank Eligible Value is required.',
                        'regex_match' => 'Only numbers and decimal values are allowed.'
                    )
                );
            }

        }

        $this->form_validation->set_rules(
            'rank_bonus',
            'Rank Bonus',
            'required|regex_match[/^\d+(\.\d+)?$/]',
            array(
                'required' => 'The Rank Bonus Value is required.',
                'regex_match' => 'Only numbers and decimal values are allowed.'
            )
        );

        if ($this->form_validation->run() == FALSE) {

            $errors = $this->form_validation->error_array();
            echo json_encode(['status' => false, 'errors' => $errors]);
            exit;

        } else {

            $data = array(
                'rank_name' => $this->input->post('rank_name', true),
                'rank_eligibel_amt' => $this->input->post('rank_eligibel_amt', true),
                'rank_bonus' => $this->input->post('rank_bonus', true),
                'rank_bonus_type' => $this->input->post('rank_bonus_type', true) ? '1' : '0',
            );


            if ($id) {
                $this->db->where('id', $id);
                $updated = $this->db->update('rank_config', $data);

                if ($updated) {
                    echo json_encode(['status' => true, 'message' => "Rank settings updated successfully!"]);
                    exit;
                } else {
                    echo json_encode(['status' => false, 'message' => "Error updating Rank settings. Please try again."]);
                    exit;
                }
            } else {

                $data['create_date'] = date('Y-m-d H:i:s');
                $inserted = $this->db->insert('rank_config', $data);

                if ($inserted) {
                    echo json_encode(['status' => true, 'message' => "New Rank added successfully!"]);
                    exit;
                } else {
                    echo json_encode(['status' => true, 'message' => "Error adding Rank. Please try again."]);
                    exit;
                }
            }

        }
    }
    /*
    |--------------------------------------------------------------------------
    | Rank Status
    |--------------------------------------------------------------------------
    */
    public function rank_status($id)
    {

        if ($id) {

            $curency_info = $this->db->query("SELECT * FROM rank_config WHERE id = '" . $id . "' ")->num_rows();
            $check_user_rank = $this->db->query("SELECT * FROM users where rank_id = '" . $id . "' and status = '1' ")->num_rows();

            $check_log = false;

            if ($curency_info) {

                $status = $this->input->post('currency_status') == '0' ? '0' : '1';

                if ($check_user_rank > 0 && $status == '0') {
                    $check_log = false;
                } else {
                    $check_log = true;
                }

                if ($check_log) {

                    $ae_update = array(
                        "rank_status" => $status
                    );
                    $this->db->where('id', $id);
                    $this->db->update('rank_config', $ae_update);

                    echo json_encode(['status' => "success", 'message' => "selected rank status successfully. "]);
                    exit;

                } else {

                    echo json_encode(['status' => false, 'message' => "This Rank Already Users Achived Can't be disabled!"]);
                    exit;
                }


            } else {

                echo json_encode(['status' => false, 'message' => "Invalid Rank Details !"]);
                exit;

            }

        } else {
            echo json_encode(['status' => false, 'message' => "incorrect Rank status request !"]);
            exit;
        }

    }
    /*
   |--------------------------------------------------------------------------
   | Rank Edi View
   |--------------------------------------------------------------------------
   */
    public function rank_edit($id)
    {

        $currency_info = $this->db->query("SELECT * FROM rank_config where id= '" . $id . "' ")->row();

        if ($currency_info) {
            $this->data['title'] = 'Rank Settings';
            $this->data['card_title'] = 'Edit Rank Settings';
            $this->data['rank_id'] = $id;
            $this->data['rank_name'] = $currency_info->rank_name;
            $this->data['left_leg_investment'] = $currency_info->left_leg_investment;
            $this->data['right_leg_investment'] = $currency_info->right_leg_investment;
            $this->data['rank_eligibel_amt'] = $currency_info->rank_eligibel_amt;
            $this->data['currency_info'] = currency_info();
            $this->data['rank_bonus'] = $currency_info->rank_bonus;
            $this->data['rank_bonus_type'] = $currency_info->rank_bonus_type;
            $this->data['action'] = base_url() . "rank-update";
            $this->data['redirect'] = base_url() . "rank-settings";
            $this->load->view('admin/settings/edit-rank-settings', $this->data);
        } else {

            $this->session->set_flashdata('danger', 'Invalide Currency Please Try Again');
            redirect('advance-settings');
        }

    }
    /*
   |--------------------------------------------------------------------------
   | Rank Delete
   |--------------------------------------------------------------------------
   */
    public function rank_delete($id)
    {

        if ($id) {

            if ($id != '1') {

                $curency_info = $this->db->query("SELECT * FROM rank_config WHERE id = '" . $id . "' ")->num_rows();

                if ($curency_info) {

                    $curency_active = $this->db->query("SELECT * FROM users WHERE rank_id = '" . $id . "' and status = '1' ")->num_rows();

                    if ($curency_active) {

                        echo json_encode(['status' => false, 'message' => "selected rank is achived users can't be delete "]);
                        exit;

                    } else {

                        $this->db->where('id', $id);
                        $this->db->delete('rank_config');
                        echo json_encode(['status' => "success", 'message' => "selected rank delete successfully. "]);
                        exit;

                    }


                } else {
                    echo json_encode(['status' => false, 'message' => "incorrect rank delete request !"]);
                    exit;
                }

            } else {
                echo json_encode(['status' => false, 'message' => "incorrect rank delete request !"]);
                exit;
            }

        } else {
            echo json_encode(['status' => false, 'message' => "incorrect rank delete request !"]);
            exit;

        }

    }
}