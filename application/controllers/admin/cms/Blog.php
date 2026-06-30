<?php

class Blog extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('cms/Blog_model');
        $this->load->library(['form_validation']);
    }

    public function index()
    {
        $this->data['title'] = "All Blog List ";
        $this->data['card_tilte'] = "Blog List";
        $this->data['coupons'] = $this->Blog_model->get_all();
        $this->load->view('admin/cms/blog-list.php', $this->data);
    }

    public function category()
    {

        $this->data['title'] = "All Blog Category List ";
        $this->data['card_tilte'] = "Blog Category List";
        $this->data['coupons'] = $this->Blog_model->get_all();
        $this->load->view('admin/cms/blog-category-list.php', $this->data);
    }

    public function add_blog_category()
    {

        $this->data['title'] = 'Add Blog Category';
        $this->data['card_title'] = 'Add Blog Category';
        $this->data['action'] = base_url() . 'admin/save_blog-category';
        $this->data['redirect'] = base_url() . 'admin/blog-category-list';
        $this->data['categories'] = $this->db->query("SELECT * FROM blog_categories ")->result();
        $this->load->view('admin/cms/add-blog-category', $this->data);

    }
    /*
    |--------------------------------------------------------------------------
    | PRODUCT CATEGORY
    |--------------------------------------------------------------------------
    */
    public function save_blog_category()
    {

        $this->form_validation->set_rules('category_name', 'Category Name', 'required|trim');

        if ($this->form_validation->run() === FALSE) {
            echo json_encode([
                'status' => false,
                'message' => validation_errors()
            ]);
        }

        $category_name = $this->input->post('category_name', TRUE);
        $status = 1;
        $description = $this->input->post('description');

        $category_data = [
            'name' => $category_name,
            'status' => $status,
            'description' => $description,
        ];

        if (!empty($_FILES['category_image']['name'])) {

            if (ENABLE_SITE_UPLOAD_FUNCTION !== true) {
                $this->_dbg('blog_upload_images1', 'Uploads disabled');
                echo json_encode([
                    'status' => false,
                    'message' => 'Uploads disabled'
                ]);
                exit;
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
            $this->db->update('blog_categories', $category_data);
            echo json_encode([
                'status' => true,
                'message' => "Category updated successfully."
            ]);

        } else {

            $category_data['created_date'] = date('Y-m-d H:i:s');
            $this->db->insert('blog_categories', $category_data);
            echo json_encode([
                'status' => true,
                'message' => "Category added successfully"
            ]);

        }

    }


    public function blog_category_list_view()
    {
        $draw = $this->input->get('draw');
        $start = $this->input->get('start');
        $length = $this->input->get('length');

        $data = array();
        $total_records = $this->Blog_model->get_category_count();
        $products = $this->Blog_model->get_category_list($length, $start);

        $i = $start;
        foreach ($products as $product) {
            $i++;

            $status_checked = $product['status'] == 1 ? 'checked' : '';
            $status_url = base_url() . 'admin/blog-category-status-toggle/' . $product['id'];
            $edit_url = base_url() . 'admin/blog-category-edit/' . $product['id'];
            $delete_url = base_url() . 'admin/blog-category-delete/' . $product['id'];

            $data[] = array(
                'RecordID' => $i,
                'ProductInfo' => '
                <div class="d-flex align-items-center">
                    <div class="ms-5">
                        <!--begin::Title-->
                        <a href="' . $edit_url . '" class="text-gray-800 text-hover-primary fs-5 fw-bold" data-kt-ecommerce-product-filter="product_name">' . htmlspecialchars($product['name']) . '</a>
                        <!--end::Title-->
                        <div class="text-muted fs-7 fw-bold">' . strip_tags($product['description']) . '</div>
                    </div>
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

    public function blog_list_view()
    {
        $draw = $this->input->get('draw');
        $start = $this->input->get('start');
        $length = $this->input->get('length');

        $data = [];

        $total_records = $this->Blog_model->get_blog_count();
        $blogs = $this->Blog_model->get_blog_list($length, $start);

        $i = $start;
        foreach ($blogs as $row) {
            $i++;

            $status_checked = $row['status'] == '1' ? 'checked' : '';
            $status_url = base_url() . 'admin/blog-status-toggle/' . $row['id'];
            $edit_url = base_url() . 'admin/blog-edit/' . $row['id'];
            $delete_url = base_url() . 'admin/blog-delete/' . $row['id'];

            $image = !empty($row['image'])
                ? '<img src="' . base_url('assets/blogs/' . $row['image']) . '" width="80" class="img-thumbnail">'
                : '<span class="text-muted">No Image</span>';

            $data[] = array(
                'RecordID' => $i,
                'BlogInfo' => '
                    <div class="d-flex flex-column">
                        <span class="fw-bold fs-6 text-dark">' . htmlspecialchars($row['title']) . '</span>
                        <small class="text-muted">' . htmlspecialchars($row['slug']) . '</small>
                    </div>',
                'Image' => $image,
                'CreatedAt' => date('d-m-Y', strtotime($row['created_at'])),
                'Status' => '
                    <div class="form-check form-switch form-check-custom form-check-success form-check-solid">
                        <input class="form-check-input h-30px w-50px toggle_status blog_status" type="checkbox" ' . $status_checked . '
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



    /*
    |--------------------------------------------------------------------------
    | SAVE Blog
    |--------------------------------------------------------------------------
    */

    public function save_blog()
    {

        $this->form_validation->set_rules('title', 'Blog Name', 'required|trim');
        $this->form_validation->set_rules('category', 'Blog Content', 'required|trim');

        if ($this->form_validation->run() === FALSE) {
            echo json_encode([
                'status' => false,
                'message' => validation_errors()
            ]);
        }

        $category_name = $this->input->post('title', TRUE);
        $slug = url_title($category_name, 'dash', TRUE);
        $status = 1;

        $description = $this->input->post('content');
        $sort_order = 0;

        $category_data = [
            'title' => $category_name,
            'slug' => $slug,
            'status' => $status,
            'content' => $description,
            'category' => $this->input->post('category'),
            'created_at' => date('Y-m-d')
        ];

        if (!empty($_FILES['brand_image']['name'])) {

            if (ENABLE_SITE_UPLOAD_FUNCTION !== true) {
                $this->_dbg('blog_upload_images2', 'Uploads disabled');
                echo json_encode([
                    'status' => false,
                    'message' => 'Uploads disabled'
                ]);
                exit;
            }

            $config['upload_path'] = './uploads/category/';
            $config['allowed_types'] = 'jpg|jpeg|png';
            $config['file_name'] = time() . '_' . $_FILES['brand_image']['name'];
            $this->load->library('upload', $config);

            if ($this->upload->do_upload('brand_image')) {
                $upload_data = $this->upload->data();
                $category_data['image'] = 'uploads/category/' . $upload_data['file_name'];
            } else {
                echo json_encode([
                    'status' => false,
                    'message' => $this->upload->display_errors()
                ]);
                exit;
            }
        }

        $brand_id = $this->input->post('blog_id', TRUE);
        if (!empty($brand_id)) {
            $this->db->where('id', $brand_id);
            $this->db->update('blogs', $category_data);
            echo json_encode([
                'status' => true,
                'message' => "Blogs updated successfully."
            ]);
        } else {
            $this->db->insert('blogs', $category_data);
            echo json_encode([
                'status' => true,
                'message' => "Blogs added successfully"
            ]);
        }

    }


    public function add_blog()
    {

        $this->data['title'] = "Create Blog";
        $this->data['card_title'] = "Blog Create";
        $this->data['action'] = base_url() . "admin/save_blog";
        $this->data['redirect'] = base_url() . "admin/blog-list";
        $this->data['category_info'] = $this->db->query("SELECT * FROM blog_categories where status = '1' ")->result();
        $this->load->view('admin/cms/add-blog', $this->data);

    }

    public function edit_blog($id)
    {

        $this->data['title'] = "Edit Coupen";
        $this->data['card_title'] = "Edit Create";
        $this->data['action'] = base_url() . "admin/save_blog";
        $this->data['redirect'] = base_url() . "admin/blog-list";
        $this->data['category_info'] = $this->db->query("SELECT * FROM blog_categories where status = '1' ")->result();
        $this->data['blog'] = $this->db->query("SELECT * FROM blogs where id = '" . $id . "' ")->row();
        $this->load->view('admin/cms/add-blog', $this->data);

    }

    public function edit_blog_category($id)
    {

        $this->data['title'] = "Edit Category";
        $this->data['card_title'] = "Edit Category";
        $this->data['action'] = base_url() . "admin/save_blog-category";
        $this->data['redirect'] = base_url() . "admin/blog-category-list";
        $this->data['category_info'] = $this->db->query("SELECT * FROM blog_categories where id = '" . $id . "' ")->row();
        $this->load->view('admin/cms/add-blog-category', $this->data);

    }
    /*
    |--------------------------------------------------------------------------
    | STATUS Update
    |--------------------------------------------------------------------------
    */
    public function blog_category_status_update($id)
    {

        if ($id) {

            $check_template = $this->db->query("SELECT * FROM `blog_categories` where id = '" . $id . "'")->num_rows();

            if ($check_template > 0) {

                $status = $this->input->post('template_status');
                $template_status = $status == '1' ? '1' : '2';

                $array_template = array(
                    "status" => $template_status,
                );

                $this->db->where('id', $id);
                $this->db->update('blog_categories', $array_template);

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
    public function blog_category_delete($id)
    {

        if ($id) {

            $check_template = $this->db->query("SELECT * FROM `blog_categories` where id = '" . $id . "'")->num_rows();

            if ($check_template > 0) {

                $array_template = array(
                    "id" => $id,
                );

                $this->db->where('id', $id);
                $this->db->delete('blog_categories', $array_template);

                $response = array(
                    'status' => "success",
                    'message' => "Blog Category Delete successfully.."
                );
                echo json_encode($response);
                exit();
            } else {
                $response = array(
                    'status' => false,
                    'message' => "Invalide Category!"
                );
                echo json_encode($response);
                exit();
            }

        }

    }

    /*
    |--------------------------------------------------------------------------
    | STATUS Update
    |--------------------------------------------------------------------------
    */
    public function blog_status_update($id)
    {

        if ($id) {

            $check_template = $this->db->query("SELECT * FROM `blogs` where id = '" . $id . "'")->num_rows();

            if ($check_template > 0) {

                $status = $this->input->post('template_status');
                $template_status = $status == '1' ? '1' : '2';

                $array_template = array(
                    "status" => $template_status,
                );

                $this->db->where('id', $id);
                $this->db->update('blogs', $array_template);

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
    public function blog_delete($id)
    {

        if ($id) {

            $check_template = $this->db->query("SELECT * FROM `blogs` where id = '" . $id . "'")->num_rows();

            if ($check_template > 0) {

                $array_template = array(
                    "id" => $id,
                );

                $this->db->where('id', $id);
                $this->db->delete('blogs', $array_template);

                $response = array(
                    'status' => "success",
                    'message' => "Blog Delete successfully.."
                );
                echo json_encode($response);
                exit();
            } else {
                $response = array(
                    'status' => false,
                    'message' => "Invalide Blog!"
                );
                echo json_encode($response);
                exit();
            }

        }

    }

}
