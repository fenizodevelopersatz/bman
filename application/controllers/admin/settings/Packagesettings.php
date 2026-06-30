<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class  Packagesettings extends CI_Controller {

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
            if (empty($permissions['package_settings'])) {
                $this->session->set_flashdata('error', 'Access Denied: You do not have permission.');
                redirect('admin');
            }
        }

    }
    /*
    |--------------------------------------------------------------------------
    |  Page Index
    |--------------------------------------------------------------------------
    */
    public function index(){
        $this->data['title'] = 'Package Settings';
        $this->data['card_title'] = 'Package Settings List';
        $this->data['active_nav'] = 'package-settings';
        $this->load->view('admin/settings/package-settings', $this->data);
    }
    /*
    |--------------------------------------------------------------------------
    | Package List View
    |--------------------------------------------------------------------------
    */
    public function list(){
        
        $this->load->model('settings/Package_model');

        $draw = $this->input->get('draw');
        $start = $this->input->get('start');
        $length = $this->input->get('length');
        $search = $this->input->get('search')['value'];
        
        $data = array();
        $users = $this->Package_model->get_info($length, $start, $search);
        $total_records = $this->Package_model->get_count($search);

        $i = 0;
        foreach ($users as $user) {
        $i++;
        $call_status_class = 'badge-light-danger';
        $call_status = 'Not Interested';
        $duration = $user['days_duration']." Days";

        $package_status = $user['status'] ? "checked" : "";
        $change_status_url = base_url()."package-status/".$user['id'];
        $minimum = $user['minimum'] <= 0 ? 0 : $user['minimum'];
        $maximum = $user['maximum'] <= 0 ? "Unlimte" : currency_format($user['maximum']);
        $period = $user['days_duration']  ? $duration : "---";
        $total_invest = '0';
        $total_withdraw = '0';
        
        $data[] = array(
        'RecordID' => $i,
        'Minimum' => '    <div class="symbol symbol-50px me-3 mb-2">                                                   
        '.currency_format($minimum).'                                                 
        </div>',
        'Maximum' => '    <div class="symbol symbol-50px me-3 mb-2">                                                   
        '.$maximum.'                                                 
        </div>',
        'Period' => '    <div class="symbol symbol-50px me-3 mb-2">                                                   
        '.$period.'                                                 
        </div>',
        'total_invest' => '    <div class="symbol symbol-50px me-3 mb-2">                                                   
        '.currency_format($total_invest).'                                                 
        </div>',
        'total_withdraw' => '    <div class="symbol symbol-50px me-3 mb-2">                                                   
        '.currency_format($total_withdraw).'                                                 
        </div>',
        'paymentStatus' => '<div class="form-check form-switch form-check-custom form-check-success form-check-solid">
        <input class="form-check-input h-30px w-50px package_status" type="checkbox" value="" name="package_status"'.
        $package_status.'
        id="package_status" 
        data-payment="'.$user['id'].'" 
        data-package_status-url="'.$change_status_url.'"/>
        <label class="form-check-label" for="package_status">
        </label>
        </div>',
        'paymentid'=>$user['id']
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
    | Package Add
    |--------------------------------------------------------------------------
    */
    public function add()
    {
        if ($this->input->post()) {

            // ---------- 1) VALIDATION RULES ----------
            $this->form_validation->set_rules('package_name', 'Package Name', 'required|regex_match[/^[A-Za-z0-9\s\-_]+$/]');
            $this->form_validation->set_rules('minimum', 'Minimum Amount', 'required|numeric|greater_than[0]');
            $this->form_validation->set_rules('maximum', 'Maximum Amount', 'required|numeric');
            $this->form_validation->set_rules('period',   'Period',         'required|in_list[daily,weekly,monthly,yearly]');
            $this->form_validation->set_rules('duration', 'Duration',       'required|integer|greater_than[0]');
            $this->form_validation->set_rules('roi',      'ROI',            'required|numeric');

            // binary / plan extras
            $this->form_validation->set_rules('bv',                     'Business Volume',         'required|numeric|greater_than_equal_to[0]');
            $this->form_validation->set_rules('binary_commission',      'Binary Commission',       'required|numeric|greater_than_equal_to[0]');
            $this->form_validation->set_rules('binary_commission_type', 'Binary Commission Type',  'required|in_list[amount,percent]');
            $this->form_validation->set_rules('own_commission',         'Own Commission',          'required|numeric|greater_than_equal_to[0]');
            $this->form_validation->set_rules('direct_commission',      'Direct Commission',       'required|numeric|greater_than_equal_to[0]');

            $this->form_validation->set_rules('pair_commission_status', 'Pair Commission Status',  'required|in_list[0,1]');
            // Require pair fields only if switch is ON
            if ((int)$this->input->post('pair_commission_status') === 1) {
            $this->form_validation->set_rules('pair_commission',      'Pair Commission',     'required|numeric|greater_than_equal_to[0]');
            $this->form_validation->set_rules('pair_commission_type', 'Pair Commission Type','required|in_list[amount,percent]');
            $this->form_validation->set_rules('daily_max_pairs',      'Daily Maximum Pairs', 'required|integer|greater_than_equal_to[0]');
            } else {
            $this->form_validation->set_rules('pair_commission',      'Pair Commission',     'numeric|greater_than_equal_to[0]');
            $this->form_validation->set_rules('pair_commission_type', 'Pair Commission Type','in_list[amount,percent]');
            $this->form_validation->set_rules('daily_max_pairs',      'Daily Maximum Pairs', 'integer|greater_than_equal_to[0]');
            }

            // subscription (OPTIONAL – provide defaults later if not posted)
            $this->form_validation->set_rules('subscription_type',       'Subscription Type',        'trim|in_list[monthly,yearly]');
            $this->form_validation->set_rules('subscription_grace_days', 'Subscription Grace Period','trim|integer|greater_than_equal_to[0]');

            // optional misc
            $this->form_validation->set_rules('roi_made_by', 'ROI Made By', 'trim|in_list[currency,token]');
            $this->form_validation->set_rules('package_id',  'Package ID',  'trim|integer');

            // ---- Arrays need per-element rules in CI3 ----
            $mb = $this->input->post('matching_bonus');
            if (is_array($mb)) {
            foreach ($mb as $i => $v) {
            $this->form_validation->set_rules("matching_bonus[$i]", "Matching Bonus #".($i+1), 'numeric', [
            'numeric' => 'Matching Bonus values must be numeric.'
            ]);
            }
            }

            $lpv = $this->input->post('level_pv');
            if (is_array($lpv)) {
            foreach ($lpv as $i => $v) {
            $this->form_validation->set_rules("level_pv[$i]", "Level PV #".($i+1), 'numeric', [
            'numeric' => 'Level PV values must be numeric.'
            ]);
            }
            }

            $plac = $this->input->post('product_level_commission_amount');
            if (is_array($plac)) {
            foreach ($plac as $i => $v) {
            $this->form_validation->set_rules("product_level_commission_amount[$i]", "Product Level Commission #".($i+1), 'numeric', [
            'numeric' => 'Product Level Commission values must be numeric.'
            ]);
            }
            }


            if ($this->form_validation->run() === FALSE) {
                echo json_encode(['status' => false, 'errors' => $this->form_validation->error_array()]);
                return;
            }

            // ---------- 2) GATHER INPUTS ----------
            $package_id       = (int) ($this->input->post('package_id') ?? 0);
            $package_name     = trim($this->input->post('package_name', true));
            $minimum          = (float) $this->input->post('minimum', true);
            $maximum          = (float) $this->input->post('maximum', true);
            $period           = $this->input->post('period', true);              // daily/weekly/monthly/yearly
            $roi              = (float) $this->input->post('roi', true);
            $duration         = (int)   $this->input->post('duration', true);
            $retrun_principle = $this->input->post('retrun_principle') ? '1' : '0';
            $roi_made_by      = $this->input->post('roi_made_by', true) ?: 'currency';

            // binary extras
            $bv                      = (float) $this->input->post('bv', true);
            $binary_commission       = (float) $this->input->post('binary_commission', true);
            $binary_commission_type  = $this->input->post('binary_commission_type', true); // amount|percent
            $own_commission          = (float) $this->input->post('own_commission', true);
            $direct_commission       = (float) $this->input->post('direct_commission', true);

            $pair_commission_status  = (int)   $this->input->post('pair_commission_status', true); // 0/1
            $pair_commission         = (float) $this->input->post('pair_commission', true);
            $pair_commission_type    = $this->input->post('pair_commission_type', true); // amount|percent
            $daily_max_pairs         = (int)   $this->input->post('daily_max_pairs', true);

            $subscription_type       = $this->input->post('subscription_type', true); // monthly|yearly
            $subscription_grace_days = (int) $this->input->post('subscription_grace_days', true);

            // arrays → JSON (clean numeric only)
            $matching_bonus_arr   = $this->_clean_number_array($this->input->post('matching_bonus'));
            $level_pv_arr         = $this->_clean_number_array($this->input->post('level_pv'));
            $product_lvl_comm_arr = $this->_clean_number_array($this->input->post('product_level_commission_amount'));

            $matching_bonus_json   = json_encode($matching_bonus_arr ?: []);
            $level_pv_json         = json_encode($level_pv_arr ?: []);
            $product_lvl_comm_json = json_encode($product_lvl_comm_arr ?: []);

            // ---------- 3) DERIVED ----------
            $period_mapping = ['daily' => 1, 'weekly' => 7, 'monthly' => 30, 'yearly' => 365];
            $days_duration  = isset($period_mapping[$period]) ? ($duration * $period_mapping[$period]) : 0;

            // ---------- 4) DB WRITE (UPSERT WITH TXN) ----------
            $payload = [
                'package_name'        => $package_name,
                'minimum'             => $minimum,
                'maximum'             => $maximum,
                'period'              => $period,
                'duration'            => $duration,
                'days_duration'       => $days_duration,
                'roi'                 => $roi,
                'retrun_principle'    => $retrun_principle,
                'roi_made_by'         => $roi_made_by,

                // binary fields
                'bv'                      => $bv,
                'binary_commission'       => $binary_commission,
                'binary_commission_type'  => $binary_commission_type,
                'own_commission'          => $own_commission,
                'direct_commission'       => $direct_commission,
                'pair_commission_status'  => $pair_commission_status,
                'pair_commission'         => $pair_commission,
                'pair_commission_type'    => $pair_commission_type,
                'daily_max_pairs'         => $daily_max_pairs,

                // list fields as JSON
                'matching_bonus_json'     => $matching_bonus_json,
                'level_pv_json'           => $level_pv_json,
                'product_level_comm_json' => $product_lvl_comm_json,

                // subscription
                'subscription_type'       => $subscription_type,
                'subscription_grace_days' => $subscription_grace_days,
            ];

            $this->db->trans_begin();

            if ($package_id <= 0) {
                // Insert
                $payload['status']       = '1';
                $payload['created_date'] = date('Y-m-d H:i:s');
                $this->db->insert('package_config', $payload);
            } else {
                // Update
                $payload['update_date'] = date('Y-m-d H:i:s');
                $this->db->where('id', $package_id)->update('package_config', $payload);
            }

            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                echo json_encode(['status' => false, 'message' => 'Database error. Please try again.']);
                return;
            }
            $this->db->trans_commit();

            echo json_encode([
                'status'  => true,
                'message' => ($package_id <= 0) ? 'Package added successfully!' : 'Package updated successfully!'
            ]);
            return;

        } else {
            // ---------- GET: RENDER FORM WITH DEFAULTS ----------
            $this->data['title']            = 'Create Finance Package';
            $this->data['card_title']       = 'Create Package';
            $this->data['package_id']       = 0;
            $this->data['package_name']     = '';
            $this->data['minimum']          = '';
            $this->data['maximum']          = '';
            $this->data['period']           = '';
            $this->data['roi']              = '';
            $this->data['duration']         = '';
            $this->data['retrun_principle'] = '0';
            $this->data['roi_made_by']      = 'currency';

            // binary defaults for new form
            $this->data['bv']                      = '';
            $this->data['binary_commission']       = '';
            $this->data['binary_commission_type']  = 'percent';
            $this->data['own_commission']          = '';
            $this->data['direct_commission']       = '';
            $this->data['pair_commission_status']  = 1;
            $this->data['pair_commission']         = '';
            $this->data['pair_commission_type']    = 'percent';
            $this->data['daily_max_pairs']         = '';

            $this->data['matching_bonus']          = [];  // array for view
            $this->data['level_pv']                = [];
            $this->data['product_level_commission_amount'] = [];

            $this->data['subscription_type']       = 'yearly';
            $this->data['subscription_grace_days'] = 0;

            $this->data['currency_info'] = currency_info();
            $this->data['token_info']    = token_info();

            $this->load->view('admin/settings/edit-package', $this->data);
        }
    }

    /**
     * Validate number arrays (matching_bonus[], level_pv[], product_level_commission_amount[])
     */
    public function _validate_number_array($arr)
    {
        if ($arr === null) return true; // optional
        if (!is_array($arr)) {
            $this->form_validation->set_message('_validate_number_array', 'Invalid list format.');
            return false;
        }
        foreach ($arr as $v) {
            if ($v === '' || $v === null) continue; // allow blanks (will be dropped)
            if (!is_numeric($v)) {
                $this->form_validation->set_message('_validate_number_array', 'All list values must be numeric.');
                return false;
            }
        }
        return true;
    }

    /** Clean numeric array → float list */
    private function _clean_number_array($arr)
    {
        if (!is_array($arr)) return [];
        $out = [];
        foreach ($arr as $v) {
            if ($v === '' || $v === null) continue;
            if (is_numeric($v)) $out[] = 0 + $v;
        }
        return $out;
    }
    /*
    |--------------------------------------------------------------------
    | Package Edit
    |--------------------------------------------------------------------
    */
    public function edit($id)
    {
        $id = (int)$id;
        if ($id <= 0) {
            $this->session->set_flashdata('danger', 'Invalid request');
            return redirect('package-settings');
        }

        // Safer AR query + select all new columns with sane fallbacks
        $this->db->from('package_config')->where('id', $id);
        $package = $this->db->get()->row();

        if (!$package) {
            $this->session->set_flashdata('danger', 'Package not found');
            return redirect('package-settings');
        }

        // ---- Defaults in case older rows don’t have new columns yet ----
        $get = function($obj, $prop, $default = null) {
            return isset($obj->$prop) ? $obj->$prop : $default;
        };

        // Arrays may be stored as JSON/TEXT; normalize to PHP arrays for the view
        $jsonToArr = function($val) {
            if (is_array($val)) return $val;
            if (!is_string($val) || $val === '') return [];
            $d = json_decode($val, true);
            return is_array($d) ? $d : [];
        };

        $this->data['title']             = 'Update Finance Package';
        $this->data['card_title']        = 'Edit Package';

        // base package
        $this->data['package_id']        = $id;
        $this->data['package_name']      = $get($package, 'package_name', '');
        $this->data['minimum']           = $get($package, 'minimum', '');
        $this->data['maximum']           = $get($package, 'maximum', '');
        $this->data['period']            = $get($package, 'period', '');
        $this->data['roi']               = $get($package, 'roi', '');
        $this->data['duration']          = $get($package, 'duration', '');
        $this->data['retrun_principle']  = (int)$get($package, 'retrun_principle', 0);
        $this->data['roi_made_by']       = $get($package, 'roi_made_by', 'currency');

        // currency/token info already used in your view
        $this->data['currency_info']     = currency_info();
        $this->data['token_info']        = token_info();

        // ---- NEW FIELDS (binary / commissions / lists / subscription) ----
        $this->data['bv']                      = $get($package, 'bv', '');
        $this->data['binary_commission']       = $get($package, 'binary_commission', '');
        $this->data['binary_commission_type']  = $get($package, 'binary_commission_type', 'percent');
        $this->data['own_commission']          = $get($package, 'own_commission', '');
        $this->data['direct_commission']       = $get($package, 'direct_commission', '');

        $this->data['pair_commission_status']  = (int)$get($package, 'pair_commission_status', 1);
        $this->data['pair_commission']         = $get($package, 'pair_commission', '');
        $this->data['pair_commission_type']    = $get($package, 'pair_commission_type', 'percent');
        $this->data['daily_max_pairs']         = $get($package, 'daily_max_pairs', '');

        // Lists (as arrays for the dynamic inputs)
        $this->data['matching_bonus']                   = $jsonToArr($get($package, 'matching_bonus_json', $get($package,'matching_bonus','')));
        $this->data['level_pv']                         = $jsonToArr($get($package, 'level_pv_json', $get($package,'level_pv','')));
        $this->data['product_level_commission_amount']  = $jsonToArr($get($package, 'product_level_comm_json', $get($package,'product_level_commission_amount','')));

        // Subscription
        $this->data['subscription_type']       = $get($package, 'subscription_type', 'yearly');
        $this->data['subscription_grace_days'] = (int)$get($package, 'subscription_grace_days', 0);

        // Render
        $this->load->view('admin/settings/edit-package', $this->data);
    }

    /*
    |--------------------------------------------------------------------------
    | Package Delete
    |--------------------------------------------------------------------------
    */
    public function delete($id){

        if($id > 0){

            $package_data = $this->db->query("SELECT * FROM package_config WHERE id = '".$id."' ")->row();

            if($package_data){

                $check_live_invesment = $this->db->query("SELECT * FROM user_investment where status = '1' and package_id = '".$id."' ")->num_rows();

                if($check_live_invesment <= 0 ){
                    
                    $this->db->where('id',$id);
                    $delete = $this->db->delete("package_config");
                    
                    echo json_encode(['status' => true, 'message' => "Package deleted successfully."]);
                    exit;

                } else {

                    echo json_encode(['status' => false, 'message' => "this package is still runing!"]);
                    exit;

                }

            } else {

                echo json_encode(['status' => false, 'message' => "invalid pacakge data"]);
                exit;

            }

        } else {

            echo json_encode(['status' => false, 'message' => "invalid request"]);
            exit;
        }

    }
     /*
    |--------------------------------------------------------------------------
    | Package Status Change
    |--------------------------------------------------------------------------
    */
    public function status($id){

        if($id > 0){

            $pacakge_status = $this->input->post('package_status') ? '1' : '0';
            $package_data = $this->db->query("SELECT * FROM package_config WHERE id = '".$id."' ")->row();

            if($package_data){

                $update_package = array(
                    "status" => $pacakge_status
                );

                if($pacakge_status == '0'){

                    $check_live_invesment = $this->db->query("SELECT * FROM user_investment where status = '1' and package_id = '".$id."' ")->num_rows();

                    if($check_live_invesment <= 0 ){

                        $this->db->where('id',$id);
                        $this->db->update('package_config',$update_package);
                        echo json_encode(['status' => "success", 'message' => "Package  status update successfully."]);
                        exit;
    
                    } else {
    
                        echo json_encode(['status' => false, 'message' => "this package is still runing!"]);
                        exit;
    
                    }

                } else {

                    $this->db->where('id',$id);
                    $this->db->update('package_config',$update_package);
                    echo json_encode(['status' => "success", 'message' => "Package status update successfully."]);
                    exit;

                }
               

            } else {

                echo json_encode(['status' => false, 'message' => "invalid pacakge data"]);
                exit;

            }

        } else {

            echo json_encode(['status' => false, 'message' => "invalid request"]);
            exit;
        }

    }
}
