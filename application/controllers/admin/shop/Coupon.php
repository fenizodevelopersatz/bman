<?php 

class Coupon extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('shop/Coupon_model');
        $this->load->library(['form_validation']);
    }

    public function index() {
        $this->data['title'] = "All Coupen List ";
        $this->data['card_tilte'] = "Coupen List";
        $this->data['coupons'] = $this->Coupon_model->get_all();
        $this->load->view('admin/shop/coupon-list.php', $this->data);
    }

     // Brand list
   public function coupen_list_view() {
    $draw = $this->input->get('draw');
    $start = $this->input->get('start');
    $length = $this->input->get('length');

    $data = array();

    $total_records = $this->Coupon_model->get_coupon_count(); // Total count
    $coupons = $this->Coupon_model->get_coupon_list($length, $start); // Paginated list

    $i = $start;
    foreach ($coupons as $row) {
        $i++;

        $status_checked = $row['status'] == 'active' ? 'checked' : '';
        $status_url = base_url() . 'admin/coupon-status-toggle/' . $row['id'];
        $edit_url = base_url() . 'admin/coupon-edit/' . $row['id'];
        $delete_url = base_url() . 'admin/coupen-delete/' . $row['id'];

        $discount = $row['discount_type'] === 'percentage'
            ? $row['discount_value'] . '%'
            :  currency_info()->currency_symbol.' '.$row['discount_value'];

        $validity = '<div>' . $row['valid_from'] . ' to ' . $row['valid_to'] . '</div>';

        $data[] = array(
            'RecordID' => $i,
            'CouponInfo' => '
                <div class="d-flex flex-column">
                    <span class="fw-bold fs-6 text-dark">' . htmlspecialchars($row['code']) . '</span>
                    <small class="text-muted">Min Order: ' . $row['min_order_amount'] . '</small>
                    <small class="text-muted">Max Discount: ' . $row['max_discount'] . '</small>
                </div>
            ',
            'Discount' => '<span class="badge badge-light-primary">' . $discount . '</span>',
            'Validity' => $validity,
            'Status' => '
                <div class="form-check form-switch form-check-custom form-check-success form-check-solid">
                    <input class="form-check-input h-30px w-50px toggle_status template_status" type="checkbox" ' . $status_checked . '
                        data-id="' . $row['id'] . '" data-toggle-url="' . $status_url . '"/>
                </div>',
            'Action' => '
                <div class="d-flex justify-content-start flex-row gap-3">
                    <a class="btn btn-info btn-sm" href="' . $edit_url . '">
                        <i class="fa fa-edit"></i> Edit
                    </a>
                    <a class="btn btn-danger btn-sm delete_user" 
                        data-id="' . $row['id'] . '" 
                        data-url="' . $delete_url . '">
                        <i class="fa fa-trash"></i> Delete
                    </a>
                </div>'
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


    public function add_coupen() {

        $this->data['title'] = "Create Coupen";
        $this->data['card_title'] = "Coupen Create";
        $this->data['action'] = base_url()."admin/save_coupen";
        $this->data['redirect'] = base_url()."admin/coupen-list";
        $this->load->view('admin/shop/create_coupen', $this->data);

    }

     public function edit_coupen($id) {

        $this->data['title'] = "Edit Coupen";
        $this->data['card_title'] = "Edit Create";
        $this->data['action'] = base_url()."admin/save_coupen";
        $this->data['redirect'] = base_url()."admin/coupen-list";
        $this->data['coupon_info'] = $this->db->query("SELECT * FROM coupons where id = '".$id."' ")->row();
        $this->load->view('admin/shop/create_coupen', $this->data);
        
    }


    public function save_coupon()
    {
        $coupon_id = $this->input->post('coupon_id', TRUE);
        $code = strtoupper(trim($this->input->post('code', TRUE)));

        if (empty($coupon_id)) {
            $this->form_validation->set_rules('code', 'Coupon Code', 'required|is_unique[coupons.code]');
        } else {
            $this->form_validation->set_rules('code', 'Coupon Code', 'required');
        }

        $this->form_validation->set_rules('discount_type', 'Discount Type', 'required');
        $this->form_validation->set_rules('discount_value', 'Discount Value', 'required|numeric');

        if ($this->form_validation->run() === FALSE) {
            echo json_encode([
                'status' => false,
                'message' => validation_errors()
            ]);
            return;
        }

        $coupon_data = [
            'code'             => $code,
            'discount_type'    => $this->input->post('discount_type', TRUE),
            'discount_value'   => $this->input->post('discount_value', TRUE),
            'min_order_amount' => $this->input->post('min_order_amount', TRUE) ?: NULL,
            'max_discount'     => $this->input->post('max_discount', TRUE) ?: NULL,
            'usage_limit'      => $this->input->post('usage_limit', TRUE) ?: NULL,
            'usage_per_user'   => $this->input->post('usage_per_user', TRUE) ?: NULL,
            'valid_from'       => $this->input->post('valid_from', TRUE),
            'valid_to'         => $this->input->post('valid_to', TRUE),
            'status'           => $this->input->post('status', TRUE) ?? 'inactive',
            'updated_at'       => date('Y-m-d H:i:s')
        ];

        if (!empty($coupon_id)) {
            $this->db->where('id', $coupon_id);
            $this->db->update('coupons', $coupon_data);
            echo json_encode([
                'status' => true,
                'message' => 'Coupon updated successfully.'
            ]);
        } else {
            $coupon_data['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert('coupons', $coupon_data);
            echo json_encode([
                'status' => true,
                'message' => 'Coupon created successfully.'
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | STATUS Update
    |--------------------------------------------------------------------------
    */
    public function coupen_status_update($id){

        if($id){

            $check_template = $this->db->query("SELECT * FROM `coupons` where id = '".$id."'")->num_rows();

            if($check_template > 0){

                $status = $this->input->post('template_status');
                $template_status = $status == '1' ? '1':'2';

                $array_template = array(
                    "status" => $template_status,
                );

                $this->db->where('id',$id);
                $this->db->update('coupons',$array_template);

                $response = array(
                    'status' => "success",
                    'message' => "Status update successfully.."
                );
                echo json_encode($response);
                exit(); 
            } else {
                $response = array(
                    'status' => false,
                    'message' => "Invalide records!"
                );
                echo json_encode($response);
                exit(); 
            }

        }

    }
    /*
    |--------------------------------------------------------------------------
    | Coupen DELETE
    |--------------------------------------------------------------------------
    */
    public function coupen_delete($id){

        if($id){

            $check_template = $this->db->query("SELECT * FROM `coupons` where id = '".$id."'")->num_rows();

            if($check_template > 0){

                $array_template = array(
                    "id" => $id,
                );

                $this->db->where('id',$id);
                $this->db->delete('coupons',$array_template);

                $response = array(
                    'status' => "success",
                    'message' => "Coupen Delete successfully.."
                );
                echo json_encode($response);
                exit(); 
            } else {
                $response = array(
                    'status' => false,
                    'message' => "Invalide Coupen!"
                );
                echo json_encode($response);
                exit(); 
            }

        }

    }

    public function usage_report() {
        $data['usages'] = $this->Coupon_model->get_usage_logs();
        $this->load->view('admin/coupon/usage', $data);
    }
}
