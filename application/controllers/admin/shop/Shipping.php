<?php 

class Shipping extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('shop/Shipping_model');
        $this->load->library('form_validation');
    }

    public function index()
    {
        $data['zones'] = $this->Shipping_model->get_all_zones();
        $data['title'] = "Shipping Zones";
        $data['card_tilte'] = "Shipping Zones List";
        $this->load->view('admin/shop/shipping-list', $data);
    }

    public function shipping_zone_list_view()
    {
        $draw = $this->input->get('draw');
        $start = $this->input->get('start');
        $length = $this->input->get('length');

        $data = [];

        $total_records = $this->Shipping_model->get_zone_count();
        $zones = $this->Shipping_model->get_zone_list($length, $start);

        $i = $start;
        foreach ($zones as $zone) {
            $i++;

            $status_checked = $zone['status'] == '1' ? 'checked' : '';
            $status_url = base_url('admin/shipping-status-toggle/' . $zone['id']);
            $edit_url = base_url('admin/shipping-edit/' . $zone['id']);
            $delete_url = base_url('admin/shipping-delete/' . $zone['id']);

            $cod = $zone['cod_available'] ? '<span class="badge badge-success">COD Yes</span>' : '<span class="badge badge-danger">No COD</span>';

            $data[] = [
                'RecordID' => $i,
                'PincodeInfo' => '
                    <div class="d-flex flex-column">
                        <span class="fw-bold fs-6 text-dark">' . htmlspecialchars($zone['pincode']) . '</span>
                        <small class="text-muted">' . htmlspecialchars($zone['zone_name']) . '</small>
                    </div>
                ',
                'ShippingCharge' => '<span class="badge badge-light-primary">' . number_format($zone['shipping_charge'], 2) . '</span>',
                'COD' => $cod,
                'Status' => '
                    <div class="form-check form-switch form-check-custom form-check-success form-check-solid">
                        <input class="form-check-input h-30px w-50px toggle_status" type="checkbox" ' . $status_checked . '
                            data-id="' . $zone['id'] . '" data-toggle-url="' . $status_url . '"/>
                    </div>',
                'Action' => '
                    <div class="d-flex justify-content-start flex-row gap-3">
                        <a class="btn btn-info btn-sm" href="' . $edit_url . '">
                            <i class="fa fa-edit"></i> Edit
                        </a>
                        <a class="btn btn-danger btn-sm delete_user" data-id="' . $zone['id'] . '" data-url="' . $delete_url . '">
                            <i class="fa fa-trash"></i> Delete
                        </a>
                    </div>'
            ];
        }

        $response = [
            'draw' => intval($draw),
            'recordsTotal' => $total_records,
            'recordsFiltered' => $total_records,
            'data' => $data
        ];

        echo json_encode($response);
    }


     /*
    |--------------------------------------------------------------------------
    | STATUS Update
    |--------------------------------------------------------------------------
    */
    public function shipping_status_update($id){

        if($id){

            $check_template = $this->db->query("SELECT * FROM `shipping_zones` where id = '".$id."'")->num_rows();

            if($check_template > 0){

                $status = $this->input->post('template_status');
                $template_status = $status == '1' ? '1':'2';

                $array_template = array(
                    "status" => $template_status,
                );

                $this->db->where('id',$id);
                $this->db->update('shipping_zones',$array_template);

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
    public function shipping_delete($id){

        if($id){

            $check_template = $this->db->query("SELECT * FROM `shipping_zones` where id = '".$id."'")->num_rows();

            if($check_template > 0){

                $array_template = array(
                    "id" => $id,
                );

                $this->db->where('id',$id);
                $this->db->delete('shipping_zones',$array_template);

                $response = array(
                    'status' => "success",
                    'message' => "shipping zones Delete successfully.."
                );
                echo json_encode($response);
                exit(); 
            } else {
                $response = array(
                    'status' => false,
                    'message' => "Invalide shipping zones!"
                );
                echo json_encode($response);
                exit(); 
            }

        }

    }


     public function add_shipping() {
        $this->data['title'] = "Add Shipping";
        $this->data['card_title'] = "Shipping Create";
        $this->data['action'] = base_url()."admin/save_shipping";
        $this->data['redirect'] = base_url()."admin/shipping-list";
        $this->load->view('admin/shop/create_shipping', $this->data);
    }

    public function edit_shipping($id = null)
    {
        $this->data['title'] = $id ? 'Edit Shipping Zone' : 'Add Shipping Zone';
        $this->data['zone'] = $id ? $this->Shipping_model->get_zone($id) : null;
        $this->data['action'] = base_url()."admin/save_shipping";
        $this->data['redirect'] = base_url()."admin/shipping-list";
        $this->load->view('admin/shop/create_shipping', $this->data);
    }


    public function save_shipping()
    {
        $id = $this->input->post('id');
        $pincode = $this->input->post('pincode');

        if ($id) {
            $is_unique = $this->Shipping_model->is_pincode_unique($pincode, $id);
        } else {
            $is_unique = $this->Shipping_model->is_pincode_unique($pincode);
        }

        $this->form_validation->set_rules('pincode', 'Pincode', 'required|numeric' . ($is_unique ? '' : '|is_unique[shipping_zones.pincode]'));
        $this->form_validation->set_rules('shipping_charge', 'Shipping Charge', 'required|numeric');

        if ($this->form_validation->run() === FALSE) {
            echo json_encode([
                'status' => false,
                'message' => validation_errors()
            ]);
            return;
        }

        $data = [
            'pincode' => $pincode,
            'zone_name' => $this->input->post('zone_name'),
            'shipping_charge' => $this->input->post('shipping_charge'),
            'cod_available' => $this->input->post('cod_available') ? 1 : 0,
            'status' => $this->input->post('status') ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if (!$id) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }

        $this->Shipping_model->save_zone($data, $id);

        echo json_encode([
            'status' => true,
            'message' => $id ? 'Zone updated successfully' : 'Zone created successfully'
        ]);
    }

    public function delete($id)
    {
        $this->Shipping_model->delete_zone($id);
        echo json_encode(['status' => true, 'message' => 'Zone deleted']);
    }
}
