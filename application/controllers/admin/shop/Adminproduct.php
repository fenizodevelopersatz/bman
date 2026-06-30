<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Adminproduct extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('shop/Product_model');
        $this->load->library('session');
    }



    // Brand list
    public function brand_view()
    {
        $this->data['title'] = "All Brand List ";
        $this->data['card_tilte'] = "Brand List";
        $this->data['products'] = $this->Product_model->get_all_brand();
        $this->load->view('admin/shop/brand-list', $this->data);
    }


    // Brand list
    public function brand_list_view()
    {
        $draw = $this->input->get('draw');
        $start = $this->input->get('start');
        $length = $this->input->get('length');

        $data = array();
        $total_records = $this->Product_model->get_brand_count();
        $products = $this->Product_model->get_brand_list($length, $start);

        $i = $start;
        foreach ($products as $product) {
            $i++;

            $status_checked = $product['status'] == 1 ? 'checked' : '';
            $status_url = base_url() . 'admin/brand-toggle/' . $product['id'];
            $edit_url = base_url() . 'admin/brand-edit/' . $product['id'];
            $delete_url = base_url() . 'admin/brand-delete/' . $product['id'];

            if ($product['parent_id'] >= 1) {
                $category_type = '<div class="badge badge-light-primary">Sub Category</div>';
            } else {
                $category_type = '<div class="badge badge-light-success">Main Category</div>';
            }

            $data[] = array(
                'RecordID' => $i,
                'ProductInfo' => '
                <div class="d-flex align-items-center">
                    <a href="' . $edit_url . '" class="symbol symbol-50px">
                        <span class="symbol-label" style="background-image:url(' . base_url() . '' . $product['brand_img'] . ');"></span>
                    </a>
                    <div class="ms-5">
                        <!--begin::Title-->
                        <a href="' . $edit_url . '" class="text-gray-800 text-hover-primary fs-5 fw-bold" data-kt-ecommerce-product-filter="product_name">' . htmlspecialchars($product['name']) . '</a>
                        <!--end::Title-->
                        <div class="text-muted fs-7 fw-bold">' . strip_tags($product['description']) . '</div>
                    </div>
                </div>
                ',
                'CategoryType' => '' . $category_type . '',
                'Status' => '<div class="form-check form-switch form-check-custom form-check-success form-check-solid">
                <input class="form-check-input h-30px w-50px template_status" type="checkbox" value="1" name="template_status"' .
                    $status_checked . '
                id="template_status" 
                data-payment="' . $product['id'] . '" 
                data-template_status-url="' . $status_url . '"/>
                <label class="form-check-label" for="template_status">
                </label>
                </div>',
                'Action' => '
                    <div class="d-flex justify-content-start flex-row gap-3">
                        <a class="btn btn-info btn-active-light-info btn-sm  text-center"  href="' . $edit_url . '">
                            <i class="fa fa-edit"></i> Edit
                        </a>
                        <a class="btn btn-danger btn-active-light-danger btn-sm delete_user text-center"   data-payment="' . $product['id'] . '" 
                        data-delete_user-url="' . $delete_url . '" ">
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


    /*
  |--------------------------------------------------------------------------
  | STATUS Brand Update
  |--------------------------------------------------------------------------
  */
    public function brand_status_update($id)
    {

        if ($id) {

            $check_template = $this->db->query("SELECT * FROM `brands` where id = '" . $id . "'")->num_rows();

            if ($check_template > 0) {

                $status = $this->input->post('template_status');
                $template_status = $status == '1' ? '1' : '2';

                $array_template = array(
                    "status" => $template_status,
                );

                $this->db->where('id', $id);
                $this->db->update('brands', $array_template);

                $response = array(
                    'status' => "success",
                    'message' => "Status update successfully.."
                );
                echo json_encode($response);
                exit();
            } else {
                $response = array(
                    'status' => false,
                    'message' => "Invalide brand!"
                );
                echo json_encode($response);
                exit();
            }

        }

    }

    public function brand_delete($id)
    {
        if ($id) {
            // Check if brand exists
            $brand = $this->db->get_where('brands', ['id' => $id])->row();
            if (!$brand) {
                echo json_encode([
                    'status' => false,
                    'message' => "Invalid brand!"
                ]);
                exit;
            }

            // Check if brand is used in any products
            $usedInProducts = $this->db->where('brand_id', $id)->count_all_results('products');
            if ($usedInProducts > 0) {
                echo json_encode([
                    'status' => false,
                    'message' => "This brand is assigned to one or more products. Please reassign or delete those products first."
                ]);
                exit;
            }

            // Safe to delete
            $this->db->where('id', $id);
            $this->db->delete('brands');

            echo json_encode([
                'status' => "success",
                'message' => "Brand deleted successfully."
            ]);
            exit;
        }

        echo json_encode([
            'status' => false,
            'message' => "Invalid request"
        ]);
        exit;
    }



    // Category list
    public function category_view()
    {
        $this->data['title'] = "All Category List ";
        $this->data['card_tilte'] = "Category List";
        $this->data['products'] = $this->Product_model->get_all_category();
        $this->load->view('admin/shop/category-list', $this->data);
    }
    // Category list
    public function category_list_view()
    {
        $draw = $this->input->get('draw');
        $start = $this->input->get('start');
        $length = $this->input->get('length');

        $data = array();
        $total_records = $this->Product_model->get_category_count();
        $products = $this->Product_model->get_category_list($length, $start);

        $i = $start;
        foreach ($products as $product) {
            $i++;

            $status_checked = $product['status'] == 1 ? 'checked' : '';
            $status_url = base_url() . 'admin/category-toggle/' . $product['id'];
            $edit_url = base_url() . 'admin/category-edit/' . $product['id'];
            $delete_url = base_url() . 'admin/category-delete/' . $product['id'];

            if ($product['parent_id'] >= 1) {
                $category_type = '<div class="badge badge-light-primary">Sub Category</div>';
            } else {
                $category_type = '<div class="badge badge-light-success">Main Category</div>';
            }

            $data[] = array(
                'RecordID' => $i,
                'ProductInfo' => '
                <div class="d-flex align-items-center">
                    <a href="' . $edit_url . '" class="symbol symbol-50px">
                        <span class="symbol-label" style="background-image:url(' . base_url() . '' . $product['image'] . ');"></span>
                    </a>
                    <div class="ms-5">
                        <!--begin::Title-->
                        <a href="' . $edit_url . '" class="text-gray-800 text-hover-primary fs-5 fw-bold" data-kt-ecommerce-product-filter="product_name">' . htmlspecialchars($product['name']) . '</a>
                        <!--end::Title-->
                        <div class="text-muted fs-7 fw-bold">' . strip_tags($product['description']) . '</div>
                    </div>
                </div>
                ',
                'CategoryType' => '' . $category_type . '',
                'Status' => '<div class="form-check form-switch form-check-custom form-check-success form-check-solid">
                <input class="form-check-input h-30px w-50px template_status" type="checkbox" value="1" name="template_status"' .
                    $status_checked . '
                id="template_status" 
                data-payment="' . $product['id'] . '" 
                data-template_status-url="' . $status_url . '"/>
                <label class="form-check-label" for="template_status">
                </label>
                </div>',
                'Action' => '
                    <div class="d-flex justify-content-start flex-row gap-3">
                        <a class="btn btn-info btn-active-light-info btn-sm  text-center"  href="' . $edit_url . '">
                            <i class="fa fa-edit"></i> Edit
                        </a>
                        <a class="btn btn-danger btn-active-light-danger btn-sm delete_user text-center"   data-payment="' . $product['id'] . '" 
                        data-delete_user-url="' . $delete_url . '" ">
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

    /*
    |--------------------------------------------------------------------------
    | STATUS CATEGORY Update
    |--------------------------------------------------------------------------
    */
    public function category_status_update($id)
    {

        if ($id) {

            $check_template = $this->db->query("SELECT * FROM `product_categories` where id = '" . $id . "'")->num_rows();

            if ($check_template > 0) {

                $status = $this->input->post('template_status');
                $template_status = $status == '1' ? '1' : '2';

                $array_template = array(
                    "status" => $template_status,
                );

                $this->db->where('id', $id);
                $this->db->update('product_categories', $array_template);

                $response = array(
                    'status' => "success",
                    'message' => "Status update successfully.."
                );
                echo json_encode($response);
                exit();
            } else {
                $response = array(
                    'status' => false,
                    'message' => "Invalide category!"
                );
                echo json_encode($response);
                exit();
            }

        }

    }

    public function category_delete($id)
    {
        if ($id) {
            $category = $this->db->get_where('product_categories', ['id' => $id])->row();
            if (!$category) {
                echo json_encode([
                    'status' => false,
                    'message' => "Invalid Category!"
                ]);
                exit;
            }

            $child_count = $this->db->where('parent_id', $id)->count_all_results('product_categories');
            if ($child_count > 0) {
                echo json_encode([
                    'status' => false,
                    'message' => "This category has subcategories. Please delete them first."
                ]);
                exit;
            }

            $this->db->where('id', $id);
            $this->db->delete('product_categories');

            echo json_encode([
                'status' => "success",
                'message' => "Category deleted successfully."
            ]);
            exit;
        }

        echo json_encode([
            'status' => false,
            'message' => "Invalid request"
        ]);
        exit;
    }


    // Product list
    public function index()
    {
        $this->data['title'] = "All Product List ";
        $this->data['card_tilte'] = "Product List";
        $this->data['products'] = $this->Product_model->get_all_products();
        $this->load->view('admin/shop/list', $this->data);
    }

    // Product list
    public function product_list()
    {
        $draw = $this->input->get('draw');
        $start = $this->input->get('start');
        $length = $this->input->get('length');

        $data = array();
        $total_records = $this->Product_model->get_count();
        $products = $this->Product_model->get_list($length, $start);

        $i = $start;
        foreach ($products as $product) {
            $i++;

            $status_checked = $product['status'] == 1 ? 'checked' : '';
            $status_url = base_url() . 'admin-product/status-toggle/' . $product['id'];
            $edit_url = base_url() . 'admin/product-edit/' . $product['id'];
            $delete_url = base_url() . 'admin/product-delete/' . $product['id'];

            $data[] = array(
                'RecordID' => $i,
                'ProductInfo' => '
                <div class="d-flex align-items-center">
                    <a href="' . $edit_url . '" class="symbol symbol-50px">
                        <span class="symbol-label" style="background-image:url(' . base_url() . 'assets/images/' . $product['product_image'] . ');"></span>
                    </a>
                    <div class="ms-5">
                        <!--begin::Title-->
                        <a href="' . $edit_url . '" class="text-gray-800 text-hover-primary fs-5 fw-bold" data-kt-ecommerce-product-filter="product_name">' . htmlspecialchars($product['name']) . '</a>
                        <!--end::Title-->
                    </div>
                </div>
                ',
                'Price' => '
                    <div class="d-flex flex-column">
                        <span class="text-gray-800 fw-bold fs-6">' . currency_format($product['price']) . '</span>
                    </div>',
                'PriceStock' => '
                    <div class="d-flex flex-column">
                        ' . $product['stock'] . '
                    </div>',
                'Commission' => '
                    <div class="d-flex flex-column">
                        <span class="text-primary fw-semibold">' . currency_format($product['commission']) . ' per unit</span>
                    </div>',
                'Rating' => '
                    <div class="rating justify-content-start">
                    <div class="rating-label checked">
                    <i class="ki-duotone ki-star fs-6"></i>                            </div>
                    <div class="rating-label checked">
                    <i class="ki-duotone ki-star fs-6"></i>                            </div>
                    <div class="rating-label checked">
                    <i class="ki-duotone ki-star fs-6"></i>                            </div>
                    <div class="rating-label ">
                    <i class="ki-duotone ki-star fs-6"></i>                            </div>
                    <div class="rating-label ">
                    <i class="ki-duotone ki-star fs-6"></i>                            </div>
                    </div>
                ',
                'Status' => '<div class="form-check form-switch form-check-custom form-check-success form-check-solid">
                <input class="form-check-input h-30px w-50px template_status" type="checkbox" value="1" name="template_status"' .
                    $status_checked . '
                id="template_status" 
                data-payment="' . $product['id'] . '" 
                data-template_status-url="' . $status_url . '"/>
                <label class="form-check-label" for="template_status">
                </label>
                </div>',
                'Action' => '
                    <div class="d-flex justify-content-start flex-row gap-3">
                        <a class="btn btn-info btn-active-light-info btn-sm  text-center"  href="' . $edit_url . '">
                            <i class="fa fa-edit"></i> Edit
                        </a>
                        <a class="btn btn-danger btn-active-light-danger btn-sm delete_user text-center"   data-payment="' . $product['id'] . '" 
                        data-delete_user-url="' . $delete_url . '" ">
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


    // Add Product Form
    public function add()
    {


        $this->data['title'] = 'Add Product';
        $this->data['card_title'] = 'Add Product';
        $this->data['action'] = base_url() . 'admin/create-product';
        $this->data['redirect'] = base_url() . 'admin/product-list';
        $this->data['users'] = $this->db->query("SELECT * FROM users where status = '1' ")->result();
        $this->data['categories'] = $this->db->query("SELECT * FROM product_categories ")->result();
        $this->data['currency_info'] = currency_info();
        $this->data['brands'] = $this->db->query("SELECT * FROM `brands` ")->result();

        $this->load->view('admin/shop/add', $this->data);
    }



    // Add Product Form
    public function edit($id)
    {

        $this->data['title'] = 'Edit Product';
        $this->data['card_title'] = 'Edit Product';
        $this->data['action'] = base_url() . 'admin/create-product';
        $this->data['redirect'] = base_url() . 'admin/product-list';
        $this->data['users'] = $this->db->query("SELECT * FROM users where status = '1' ")->result();
        $this->data['categories'] = $this->db->query("SELECT * FROM product_categories ")->result();
        $this->data['currency_info'] = currency_info();
        $this->data['brands'] = $this->db->query("SELECT * FROM `brands` ")->result();
        $this->data['product_info'] = $this->db->query("SELECT * FROM `products` where id = '" . $id . "' ")->row();
        $this->data['product_meta'] = $this->db->get_where('product_meta', ['product_id' => $id])->result();
        $this->data['product_images'] = $this->db->get_where('product_images', ['product_id' => $id])->result();

        $this->load->view('admin/shop/add', $this->data);
    }

    public function save_product()
    {


        $product_id = $this->input->post('product_id');

        if ($product_id) {
            $this->form_validation->set_rules('sku', 'SKU', 'required|callback_validate_unique_sku[' . $product_id . ']');
        } else {
            $this->form_validation->set_rules('sku', 'SKU', 'required|is_unique[products.sku]');
        }

        $this->form_validation->set_rules('name', 'Product Name', 'required');
        $this->form_validation->set_rules('price', 'Price', 'required|numeric');
        $this->form_validation->set_rules('offer_price', 'Offer Price', 'required|numeric');
        $this->form_validation->set_rules('stock', 'Stock', 'required|integer');
        $this->form_validation->set_rules('commission', 'Commission', 'required|numeric');

        if ($this->form_validation->run() == FALSE) {
            echo json_encode([
                'status' => false,
                'message' => "Product add failed. Please check errors."
            ]);
            return;
        }

        // Handle primary product image
        $product_image = '';
        if (!empty($_FILES['product_log']['name'])) {

            if (ENABLE_SITE_UPLOAD_FUNCTION !== true) {
                $this->_dbg('site_settings_upload_images', 'Uploads disabled');
                return false;
            }

            $config['upload_path'] = './assets/images/';
            $config['allowed_types'] = 'jpg|jpeg|png';
            $config['file_name'] = time() . '_' . $_FILES['product_log']['name'];
            $this->load->library('upload', $config);

            if ($this->upload->do_upload('product_log')) {
                $uploadData = $this->upload->data();
                $product_image = $uploadData['file_name'];
            }
        } else {
            // Retain existing image if editing and no new upload
            if ($product_id) {
                $existing = $this->db->get_where('products', ['id' => $product_id])->row();
                $product_image = $existing ? $existing->product_image : '';
            }
        }

        $sizes = $this->input->post('product_sizes');
        $product_sizes_str = !empty($sizes) ? implode(',', $sizes) : '';

        $data = [
            'name' => $this->input->post('name'),
            'sku' => $this->input->post('sku'),
            'price' => $this->input->post('price'),
            'offer_price' => $this->input->post('offer_price'),
            'offer_percentage' => $this->input->post('offer_percentage'),
            'is_physical' => $this->input->post('is_physical'),
            'weight' => $this->input->post('weight'),
            'length' => $this->input->post('length'),
            'width' => $this->input->post('width'),
            'height' => $this->input->post('height'),
            'offer_status' => $this->input->post('offer_status'),
            'product_type' => $this->input->post('product_type'),
            'brand_id' => $this->input->post('product_brand'),
            'category_id' => $this->input->post('product_category'),
            'stock' => $this->input->post('stock'),
            'commission' => $this->input->post('commission'),
            'description' => $this->input->post('description'),
            'product_image' => $product_image,
            'product_warranty' => $this->input->post('product_warranty'),
            'status' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'product_size' => $product_sizes_str
        ];

        $data['pv'] = (float) $this->input->post('pv');
        $data['bv'] = (float) $this->input->post('bv');

        if ($product_id) {
            $this->Product_model->update_product($product_id, $data);
            $this->Product_model->delete_product_meta($product_id);
        } else {
            $product_id = $this->Product_model->insert_product($data);
        }

        $colors = $this->input->post('product_colors');
        if (!empty($colors)) {
            foreach ($colors as $color) {
                $this->Product_model->insert_product_meta($product_id, 'color', $color);
            }
        }

        $info_keys = $this->input->post('info_key');
        $info_values = $this->input->post('info_value');
        if (!empty($info_keys) && !empty($info_values)) {
            for ($i = 0; $i < count($info_keys); $i++) {
                if (!empty($info_keys[$i]) && !empty($info_values[$i])) {
                    $this->Product_model->insert_product_meta($product_id, $info_keys[$i], $info_values[$i]);
                }
            }
        }

        // Upload multiple images
        if (!empty($_FILES['product_image']['name'][0])) {

            if (ENABLE_SITE_UPLOAD_FUNCTION !== true) {
                $this->_dbg('save_pro_upload_images2', 'Uploads disabled');
                return false;
            }

            $files = $_FILES['product_image'];
            $count = count($files['name']);
            for ($i = 0; $i < $count; $i++) {
                $_FILES['file']['name'] = $files['name'][$i];
                $_FILES['file']['type'] = $files['type'][$i];
                $_FILES['file']['tmp_name'] = $files['tmp_name'][$i];
                $_FILES['file']['error'] = $files['error'][$i];
                $_FILES['file']['size'] = $files['size'][$i];

                $config['upload_path'] = './assets/images/';
                $config['allowed_types'] = 'jpg|jpeg|png';
                $config['file_name'] = time() . '_' . $files['name'][$i];
                $this->upload->initialize($config);

                if ($this->upload->do_upload('file')) {
                    $imgData = $this->upload->data();
                    $this->Product_model->insert_product_image($product_id, $imgData['file_name']);
                }
            }
        }

        echo json_encode([
            'status' => true,
            'message' => "Product saved successfully"
        ]);
    }


    public function validate_unique_sku($sku, $product_id)
    {
        $this->db->where('sku', $sku);
        $this->db->where('id !=', $product_id);
        $exists = $this->db->get('products')->num_rows();

        if ($exists > 0) {
            $this->form_validation->set_message('validate_unique_sku', 'The SKU already exists.');
            return false;
        }
        return true;
    }



    // Add Product Form
    public function add_brand()
    {

        $this->data['title'] = 'Add Brand';
        $this->data['card_title'] = 'Add Brand';
        $this->data['action'] = base_url() . 'admin/save-brand';
        $this->data['redirect'] = base_url() . 'admin/brand-list';
        $this->load->view('admin/shop/brand', $this->data);
    }

    // Add Brand Form
    public function brand_edit($id)
    {

        $this->data['title'] = 'Add Brand';
        $this->data['card_title'] = 'Add Brand';
        $this->data['action'] = base_url() . 'admin/save-brand';
        $this->data['redirect'] = base_url() . 'admin/brand-list';
        $this->data['category_info'] = $this->db->query("SELECT * FROM brands where id = '" . $id . "' ")->row();
        $this->load->view('admin/shop/brand', $this->data);
    }


    // Add Category Form
    public function add_category()
    {

        $this->data['title'] = 'Add Category';
        $this->data['card_title'] = 'Add Category';
        $this->data['action'] = base_url() . 'admin/create-category';
        $this->data['redirect'] = base_url() . 'admin/category-list';
        $this->data['categories'] = $this->db->query("SELECT * FROM product_categories ")->result();
        $this->load->view('admin/shop/category', $this->data);
    }

    // Edit Category Form
    public function category_edit($id)
    {

        $this->data['title'] = 'Add Category';
        $this->data['card_title'] = 'Add Category';
        $this->data['action'] = base_url() . 'admin/create-category';
        $this->data['redirect'] = base_url() . 'admin/category-list';
        $this->data['category_info'] = $this->db->query("SELECT * FROM product_categories where id = '" . $id . "' ")->row();
        $this->data['categories'] = $this->db->query("SELECT * FROM product_categories ")->result();
        $this->load->view('admin/shop/category', $this->data);
    }




    /*
    |--------------------------------------------------------------------------
    | SAVE BRAND
    |--------------------------------------------------------------------------
    */

    public function save_brand()
    {
        $this->form_validation->set_rules('name', 'Brand Name', 'required|trim');

        if ($this->form_validation->run() === FALSE) {
            echo json_encode([
                'status' => false,
                'message' => validation_errors()
            ]);
        }

        $category_name = $this->input->post('name', TRUE);
        $slug = url_title($category_name, 'dash', TRUE);
        $status = 1;
        $description = $this->input->post('description');
        $sort_order = 0;

        $category_data = [
            'name' => $category_name,
            'slug' => $slug,
            'status' => $status,
            'description' => $description,
            'sort_order' => $sort_order
        ];

        if (!empty($_FILES['brand_image']['name'])) {

            if (ENABLE_SITE_UPLOAD_FUNCTION !== true) {
                $this->_dbg('brand_upload_images', 'Uploads disabled');
                return false;
            }

            $config['upload_path'] = './uploads/category/';
            $config['allowed_types'] = 'jpg|jpeg|png';
            $config['file_name'] = time() . '_' . $_FILES['brand_image']['name'];
            $this->load->library('upload', $config);

            if ($this->upload->do_upload('brand_image')) {
                $upload_data = $this->upload->data();
                $category_data['brand_img'] = 'uploads/category/' . $upload_data['file_name'];
            } else {
                echo json_encode([
                    'status' => false,
                    'message' => $this->upload->display_errors()
                ]);

            }
        }

        $brand_id = $this->input->post('brand_id', TRUE);
        if (!empty($brand_id)) {
            $this->db->where('id', $brand_id);
            $this->db->update('brands', $category_data);
            echo json_encode([
                'status' => true,
                'message' => "Brand updated successfully."
            ]);
        } else {
            $this->db->insert('brands', $category_data);
            echo json_encode([
                'status' => true,
                'message' => "Brand added successfully"
            ]);
        }

    }



    /*
    |--------------------------------------------------------------------------
    | PRODUCT CATEGORY
    |--------------------------------------------------------------------------
    */

    public function create_category()
    {
        $this->form_validation->set_rules('category_name', 'Category Name', 'required|trim');

        if ($this->form_validation->run() === FALSE) {
            echo json_encode([
                'status' => false,
                'message' => validation_errors()
            ]);
        }

        $category_name = $this->input->post('category_name', TRUE);
        $parent_id = $this->input->post('parent_id', TRUE);
        $slug = url_title($category_name, 'dash', TRUE);
        $status = 1;
        $description = $this->input->post('description');
        $sort_order = 0;

        $category_data = [
            'name' => $category_name,
            'parent_id' => !empty($parent_id) ? $parent_id : NULL,
            'slug' => $slug,
            'status' => $status,
            'description' => $description,
            'sort_order' => $sort_order
        ];

        if (!empty($_FILES['category_image']['name'])) {

            if (ENABLE_SITE_UPLOAD_FUNCTION !== true) {
                $this->_dbg('category_upload_images', 'Uploads disabled');
                return false;
            }

            $config['upload_path'] = './uploads/category/';
            $config['allowed_types'] = 'jpg|jpeg|png';
            $config['file_name'] = time() . '_' . $_FILES['category_image']['name'];
            $this->load->library('upload', $config);

            if ($this->upload->do_upload('category_image')) {
                $upload_data = $this->upload->data();
                $category_data['image'] = 'uploads/category/' . $upload_data['file_name'];
            } else {
                echo json_encode([
                    'status' => false,
                    'message' => $this->upload->display_errors()
                ]);
            }
        }

        $category_id = $this->input->post('category_id', TRUE);
        if (!empty($category_id)) {
            $this->db->where('id', $category_id);
            $this->db->update('product_categories', $category_data);
            echo json_encode([
                'status' => true,
                'message' => "Category updated successfully."
            ]);
        } else {
            $this->db->insert('product_categories', $category_data);
            echo json_encode([
                'status' => true,
                'message' => "Category added successfully"
            ]);
        }

    }


    /*
    |--------------------------------------------------------------------------
    | STATUS Update
    |--------------------------------------------------------------------------
    */
    public function product_status_update($id)
    {

        if ($id) {

            $check_template = $this->db->query("SELECT * FROM `products` where id = '" . $id . "'")->num_rows();

            if ($check_template > 0) {

                $status = $this->input->post('template_status');
                $template_status = $status == '1' ? '1' : '2';

                $array_template = array(
                    "status" => $template_status,
                );

                $this->db->where('id', $id);
                $this->db->update('products', $array_template);

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
    | PRODUCT DELETE
    |--------------------------------------------------------------------------
    */
    public function product_delete($id)
    {

        if ($id) {

            $check_template = $this->db->query("SELECT * FROM `products` where id = '" . $id . "'")->num_rows();

            if ($check_template > 0) {

                $array_template = array(
                    "id" => $id,
                );

                $this->db->where('id', $id);
                $this->db->delete('products', $array_template);

                $response = array(
                    'status' => "success",
                    'message' => "Product Delete successfully.."
                );
                echo json_encode($response);
                exit();
            } else {
                $response = array(
                    'status' => false,
                    'message' => "Invalide Product!"
                );
                echo json_encode($response);
                exit();
            }

        }

    }



}


?>