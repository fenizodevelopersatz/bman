<?php
// defined('BASEPATH') OR exit('No direct script access allowed');

// class Earnings_ads extends CI_Controller
// {
//     public function __construct()
//     {
//         parent::__construct();

//         $this->load->library(['session', 'form_validation']);
//         $this->load->helper(['url', 'form']);
//         $this->load->model('Admin_model');

//         if (!$this->session->userdata('admin_logged_in')) {
//             redirect('admin/login');
//         }

//         // ✅ permission like your FAQ cms
//         $user = $this->Admin_model->get_user($this->session->userdata('admin_userid'));
//         if ($user->admin_roll == '1') {
//             $permissions = json_decode($user->permission_pages, true);
//             if (empty($permissions['earning_ads'])) {
//                 $this->session->set_flashdata('error', 'Access Denied: You do not have permission.');
//                 redirect('admin');
//             }
//         }

//         $this->load->model('earnings/Earnings_ads_cms_model', 'M');

//     }

//     public function index()
//     {
//         $this->data['title'] = "Earning Ads Settings";
//         $this->data['card_tilte'] = "Ads List";
//         $this->load->view('admin/earnings/ads_list', $this->data);
//     }

//     // ✅ DataTable JSON
//     public function list()
//     {

//         $draw = (int) $this->input->get('draw');
//         $start = (int) $this->input->get('start');
//         $length = (int) $this->input->get('length');

//         $total = $this->M->get_count();
//         $rows = $this->M->get_info($length, $start);

//         $data = [];
//         $i = $start;

//         foreach ($rows as $r) {
//             $i++;

//             $checked = ((int) $r['is_active'] === 1) ? "checked" : "";
//             $change_status_url = base_url("admin/earning-ads/status/" . $r['id']);

//             $data[] = [
//                 'RecordID' => $i,
//                 'temp_name' => '
//                     <div class="d-flex justify-content-start flex-column">
//                         <div class="d-flex justify-content-start">
//                             <span class="me-2 badge badge-light-primary">' . htmlspecialchars($r['title']) . '</span>
//                         </div>
//                         <small class="text-muted mt-1">' . htmlspecialchars($r['ad_url']) . '</small>
//                     </div>
//                 ',

//                 'temp_reward' => '<span class="badge badge-light-success">$' . number_format((float) $r['reward_usd'], 2) . '</span>',
//                 'temp_duration' => '<span class="badge badge-light-info">' . (int) $r['duration_seconds'] . ' sec</span>',
//                 'temp_sort' => '<span class="badge badge-light-dark">' . (int) $r['sort_order'] . '</span>',

//                 'temp_status' => '
//                     <div class="form-check form-switch form-check-custom form-check-success form-check-solid">
//                         <input class="form-check-input h-30px w-50px template_status" type="checkbox"
//                             ' . $checked . '
//                             data-payment="' . $r['id'] . '"
//                             data-template_status-url="' . $change_status_url . '"/>
//                         <label class="form-check-label"></label>
//                     </div>
//                 ',

//                 'temp_content' => '
//                     <div class="d-flex justify-content-center flex-row">
//                         <a class="btn btn-success btn-active-light-success btn-sm me-4"
//                             href="' . base_url("admin/earning-ads/edit/" . $r['id']) . '">
//                             <i class="fa-solid fa-pen-to-square"></i> Edit
//                         </a>
//                         <a class="btn btn-danger btn-active-light-danger btn-sm btn-delete"
//                             href="javascript:void(0);"
//                             data-delete-url="' . base_url("admin/earning-ads/delete/" . $r['id']) . '">
//                             <i class="fa fa-trash"></i> Delete
//                         </a>
//                     </div>
//                 '
//             ];
//         }

//         echo json_encode([
//             'draw' => $draw,
//             'recordsTotal' => $total,
//             'recordsFiltered' => $total,
//             'data' => $data
//         ]);
//     }

//     // ✅ load add/edit page + save via AJAX
//     public function add()
//     {
//         $id = (int) $this->input->get('id');

//         if ($this->input->post()) {

//             $this->form_validation->set_rules('title', 'Title', 'required|trim');
//             $this->form_validation->set_rules('ad_url', 'Ad URL', 'required|trim');
//             $this->form_validation->set_rules('duration_seconds', 'Duration', 'required|integer');
//             $this->form_validation->set_rules('reward_usd', 'Reward', 'required|numeric');

//             if ($this->form_validation->run() === FALSE) {
//                 echo json_encode(['status' => false, 'message' => strip_tags(validation_errors())]);
//                 exit;
//             }

//             $payload = [
//                 'title' => $this->input->post('title', true),
//                 'description' => $this->input->post('description'),
//                 'ad_url' => $this->input->post('ad_url', true),
//                 'thumb_url' => $this->input->post('thumb_url', true),
//                 'duration_seconds' => (int) $this->input->post('duration_seconds', true),
//                 'reward_usd' => (float) $this->input->post('reward_usd', true),
//                 'sort_order' => (int) $this->input->post('sort_order', true),
//                 'is_active' => (int) $this->input->post('is_active') ? 1 : 0,
//                 'created_at' => date('Y-m-d H:i:s'),
//             ];

//             $ad_id = (int) $this->input->post('ad_id');

//             if ($ad_id > 0) {
//                 $this->db->where('id', $ad_id)->update('earning_ads', $payload);
//                 echo json_encode(['status' => true, 'message' => 'Ad updated successfully']);
//                 exit;
//             } else {
//                 $this->db->insert('earning_ads', $payload);
//                 echo json_encode(['status' => true, 'message' => 'Ad created successfully']);
//                 exit;
//             }
//         }

//         // ✅ load edit data
//         $row = null;
//         if ($id > 0) {
//             $row = $this->db->get_where('earning_ads', ['id' => $id])->row();
//         }

//         $this->data['title'] = ($id > 0) ? "Edit Ad" : "Add Ad";
//         $this->data['card_title'] = ($id > 0) ? "Edit Ad" : "New Ad";
//         $this->data['mode'] = ($id > 0) ? "edit" : "add";
//         $this->data['ad_id'] = $row->id ?? '';
//         $this->data['title_val'] = $row->title ?? '';
//         $this->data['description_val'] = $row->description ?? '';
//         $this->data['ad_url_val'] = $row->ad_url ?? '';
//         $this->data['thumb_url_val'] = $row->thumb_url ?? '';
//         $this->data['duration_val'] = $row->duration_seconds ?? 30;
//         $this->data['reward_val'] = $row->reward_usd ?? 0.50;
//         $this->data['sort_val'] = $row->sort_order ?? 1;
//         $this->data['active_val'] = isset($row->is_active) ? (int) $row->is_active : 1;

//         $this->load->view('admin/earnings/ad_add', $this->data);
//     }

//     public function edit($id)
//     {
//         $id = (int) $id;
//         $row = $this->M->get_by_id($id);
//         if (!$row) {
//             show_404();
//             return;
//         }

//         $this->data['mode'] = 'edit';
//         $this->data['title'] = "Edit Earning Ad";
//         $this->data['card_title'] = "Edit Ad";

//         $this->data['ad_id'] = (int) $row->id;
//         $this->data['title_val'] = $row->title ?? '';
//         $this->data['description_val'] = $row->description ?? '';
//         $this->data['ad_url_val'] = $row->ad_url ?? '';
//         $this->data['thumb_url_val'] = $row->thumb_url ?? '';
//         $this->data['duration_val'] = (int) $row->duration_seconds;
//         $this->data['reward_val'] = (float) $row->reward_usd;
//         $this->data['sort_val'] = (int) $row->sort_order;
//         $this->data['active_val'] = (int) $row->is_active;

//         $this->load->view('admin/earnings/ad_add', $this->data);
//     }

//     // ✅ Insert/Update (AJAX JSON) — used by your add page
//     public function save()
//     {
//         header('Content-Type: application/json');

//         $ad_id = (int) $this->input->post('ad_id');

//         // ✅ validation
//         $this->form_validation->set_rules('title', 'Title', 'required|trim');
//         $this->form_validation->set_rules('ad_url', 'Ad URL', 'required|trim');
//         $this->form_validation->set_rules('duration_seconds', 'Duration', 'required|integer');
//         $this->form_validation->set_rules('reward_usd', 'Reward', 'required|numeric');

//         if ($this->form_validation->run() === FALSE) {
//             echo json_encode(['status' => false, 'message' => strip_tags(validation_errors())]);
//             return;
//         }

//         $title = trim((string) $this->input->post('title', true));
//         $description = trim((string) $this->input->post('description', true));
//         $ad_url = trim((string) $this->input->post('ad_url', true));
//         $thumb_url = trim((string) $this->input->post('thumb_url', true));

//         $duration = (int) $this->input->post('duration_seconds');
//         $reward = (float) $this->input->post('reward_usd');

//         $sort_order = (int) $this->input->post('sort_order');
//         if ($sort_order <= 0)
//             $sort_order = 1;

//         $is_active = $this->input->post('is_active') ? 1 : 0;

//         // ✅ sanitize
//         $payload = [
//             'title' => $title,
//             'description' => $description,
//             'ad_url' => $ad_url,
//             'thumb_url' => $thumb_url,
//             'duration_seconds' => $duration,
//             'reward_usd' => $reward,
//             'sort_order' => $sort_order,
//             'is_active' => $is_active,
//         ];

//         // ✅ Update
//         if ($ad_id > 0) {
//             $exists = $this->db->get_where('earning_ads', ['id' => $ad_id])->num_rows();
//             if (!$exists) {
//                 echo json_encode(['status' => false, 'message' => 'Invalid Ad ID']);
//                 return;
//             }

//             $this->db->where('id', $ad_id)->update('earning_ads', $payload);
//             echo json_encode(['status' => true, 'message' => 'Ad updated successfully']);
//             return;
//         }

//         // ✅ Insert
//         $payload['created_at'] = date('Y-m-d H:i:s');
//         $this->db->insert('earning_ads', $payload);

//         echo json_encode(['status' => true, 'message' => 'Ad created successfully']);
//     }


//     // ✅ Toggle active (AJAX)
//     public function status_update($id)
//     {
//         $id = (int) $id;
//         $exists = $this->db->get_where('earning_ads', ['id' => $id])->num_rows();

//         if (!$exists) {
//             echo json_encode(['status' => false, 'message' => 'Invalid Ad ID']);
//             exit;
//         }

//         $status = $this->input->post('template_status'); // checkbox value
//         $new = $status ? 1 : 0;

//         $this->db->where('id', $id)->update('earning_ads', ['is_active' => $new]);

//         echo json_encode(['status' => "success", 'message' => "Status updated successfully"]);
//         exit;
//     }

//     public function delete($id)
//     {
//         $id = (int) $id;
//         $exists = $this->db->get_where('earning_ads', ['id' => $id])->num_rows();

//         if (!$exists) {
//             echo json_encode(['status' => false, 'message' => 'Invalid Ad ID']);
//             exit;
//         }

//         $this->db->where('id', $id)->delete('earning_ads');

//         echo json_encode(['status' => "success", 'message' => "Ad deleted successfully"]);
//         exit;
//     }
// }











defined('BASEPATH') OR exit('No direct script access allowed');

class Earnings_ads extends My_Controller
{
    public function __construct()
    {
        parent::__construct();

        // ✅ Same style as your FAQ controller
        $this->load->library(['session', 'form_validation']);
        $this->load->helper(['url', 'form', 'security']);
        $this->load->database();

        $this->load->model('Admin_model');

        // ✅ Admin login check
        if (!$this->session->userdata('admin_logged_in')) {
            redirect('admin/login');
        }

        // ✅ Permission check (change key name as per your permission JSON)
        $user = $this->Admin_model->get_user($this->session->userdata('admin_userid'));

        if ($user && $user->admin_roll == '1') {
            $permissions = json_decode($user->permission_pages, true);
            // change this key if you want: earning_ads, earnings_settings, etc.
            if (empty($permissions['earning_ads'])) {
                $this->session->set_flashdata('error', 'Access Denied: You do not have permission.');
                redirect('admin');
            }
        }
    }

    /* ============================================================
     *  ROUTES (suggested)
     *  GET  admin/earning-ads               -> index()
     *  GET  admin/earning-ads/list          -> list()
     *  GET  admin/earning-ads/add           -> add()   (add page)
     *  GET  admin/earning-ads/edit/{id}     -> edit($id) (edit page)
     *  POST admin/earning-ads/add           -> add()   (insert ajax)
     *  POST admin/earning-ads/save          -> save()  (update ajax OR insert if no id)
     *  POST admin/earning-ads/status/{id}   -> status_update($id) (optional)
     *  POST admin/earning-ads/delete/{id}   -> delete($id) (optional)
     * ============================================================ */

    // ✅ List page render
    public function index()
    {
        $this->data['title'] = "Earning Ads";
        $this->data['card_tilte'] = "Earning Ads List";
        $this->load->view('admin/earnings/ads_list', $this->data);
    }

    // ✅ DataTables JSON
    public function list()
    {
        header('Content-Type: application/json');

        $draw = (int) $this->input->get('draw');
        $start = (int) $this->input->get('start');
        $length = (int) $this->input->get('length');
        $search = trim((string) $this->input->get('search')['value']);

        if ($length <= 0)
            $length = 10;

        // total count
        $total_records = (int) $this->db->count_all('earning_ads');

        // filtered query
        $this->db->from('earning_ads');
        if ($search !== '') {
            $this->db->group_start()
                ->like('title', $search)
                ->or_like('ad_url', $search)
                ->or_like('thumb_url', $search)
                ->group_end();
        }
        $filtered_records = (int) $this->db->count_all_results('', false); // reuse query
        $this->db->order_by('sort_order', 'ASC');
        $this->db->order_by('id', 'DESC');
        $this->db->limit($length, $start);
        $rows = $this->db->get()->result_array();

        $data = [];
        $i = $start;
        foreach ($rows as $r) {
            $i++;

            $is_active = ((int) $r['is_active'] === 1);
            $status_checked = $is_active ? 'checked' : '';
            $status_url = base_url('admin/earning-ads/status/' . $r['id']);

            $edit_url = base_url('admin/earning-ads/edit/' . $r['id']);
            $del_url = base_url('admin/earning-ads/delete/' . $r['id']);

            $data[] = [
                'RecordID' => $i,
                'title' => '<div class="d-flex justify-content-start flex-column">
                              <span class="fw-bold text-gray-900">' . htmlspecialchars($r['title']) . '</span>
                              <span class="text-gray-500 fs-7">' . htmlspecialchars($r['ad_url']) . '</span>
                           </div>',
                'reward' => '<span class="badge badge-light-success">$' . number_format((float) $r['reward_usd'], 2) . '</span>',
                'duration' => '<span class="badge badge-light-primary">' . (int) $r['duration_seconds'] . 's</span>',
                'sort' => '<span class="badge badge-light-dark">' . (int) $r['sort_order'] . '</span>',
                'status' => '<div class="form-check form-switch form-check-custom form-check-success form-check-solid">
                                <input class="form-check-input h-30px w-50px js-ad-status" type="checkbox" ' . $status_checked . '
                                  data-status-url="' . $status_url . '">
                              </div>',
                'action' => '<div class="d-flex justify-content-center flex-row">
                              <a class="btn btn-success btn-active-light-success btn-sm me-3" href="' . $edit_url . '">
                                <i class="fa-solid fa-pen-to-square"></i> Edit
                              </a>
                              <a class="btn btn-danger btn-active-light-danger btn-sm btn-delete" href="javascript:void(0);"
                                 data-delete-url="' . $del_url . '">
                                <i class="fa fa-trash"></i> Delete
                              </a>
                            </div>',
            ];
        }

        echo json_encode([
            'draw' => $draw,
            'recordsTotal' => $total_records,
            'recordsFiltered' => ($search === '') ? $total_records : $filtered_records,
            'data' => $data,
        ]);
    }

    // ✅ load add/edit page + save via AJAX (ADD page render + optional insert)
    public function add()
    {
        // If you want only separate edit URL, keep add page clean
        if ($this->input->method(TRUE) === 'POST') {
            $this->_save_common(0); // insert
            return;
        }

        // render add page
        $this->data['mode'] = 'add';
        $this->data['title'] = "Add Ad";
        $this->data['card_title'] = "New Ad";

        $this->data['ad_id'] = '';
        $this->data['title_val'] = '';
        $this->data['description_val'] = '';
        $this->data['ad_url_val'] = '';
        $this->data['thumb_url_val'] = '';
        $this->data['duration_val'] = 30;
        $this->data['reward_val'] = 1.50;
        $this->data['sort_val'] = 1;
        $this->data['active_val'] = 1;

        $this->load->view('admin/earnings/ad_add', $this->data);
    }

    // ✅ render edit page (URL: admin/earning-ads/edit/{id})
    public function edit($id)
    {
        $id = (int) $id;
        $row = $this->db->get_where('earning_ads', ['id' => $id])->row();
        if (!$row) {
            show_404();
            return;
        }

        $this->data['mode'] = 'edit';
        $this->data['title'] = "Edit Ad";
        $this->data['card_title'] = "Edit Ad";

        $this->data['ad_id'] = (int) $row->id;
        $this->data['title_val'] = $row->title ?? '';
        $this->data['description_val'] = $row->description ?? '';
        $this->data['ad_url_val'] = $row->ad_url ?? '';
        $this->data['thumb_url_val'] = $row->thumb_url ?? '';
        $this->data['duration_val'] = (int) ($row->duration_seconds ?? 30);
        $this->data['reward_val'] = (float) ($row->reward_usd ?? 0);
        $this->data['sort_val'] = (int) ($row->sort_order ?? 1);
        $this->data['active_val'] = (int) ($row->is_active ?? 1);

        $this->load->view('admin/earnings/ad_add', $this->data);
    }

    // ✅ Insert/Update (AJAX JSON) — used by your add page in edit mode action=".../save"
    public function save()
    {
        $ad_id = (int) $this->input->post('ad_id');
        $this->_save_common($ad_id);
    }

    /* -----------------------------
     * OPTIONAL: status toggle
     * ----------------------------- */
    public function status_update($id)
    {
        header('Content-Type: application/json');
        $id = (int) $id;

        $exists = $this->db->get_where('earning_ads', ['id' => $id])->num_rows();
        if (!$exists) {
            echo json_encode(['status' => false, 'message' => 'Invalid ID']);
            return;
        }

        $val = $this->input->post('is_active');
        $is_active = ($val == '1' || $val === 1 || $val === true || $val === 'true') ? 1 : 0;

        $this->db->where('id', $id)->update('earning_ads', ['is_active' => $is_active]);

        echo json_encode(['status' => true, 'message' => 'Status updated']);
    }

    /* -----------------------------
     * OPTIONAL: delete
     * ----------------------------- */
    public function delete($id)
    {
        header('Content-Type: application/json');
        $id = (int) $id;

        $exists = $this->db->get_where('earning_ads', ['id' => $id])->num_rows();
        if (!$exists) {
            echo json_encode(['status' => false, 'message' => 'Invalid ID']);
            return;
        }

        $this->db->where('id', $id)->delete('earning_ads');
        echo json_encode(['status' => true, 'message' => 'Deleted successfully']);
    }

    /* ============================================================
     * ✅ Shared save logic:
     * - supports URL mode OR upload mode for video (saves path into ad_url)
     * - supports URL mode OR upload mode for thumb (saves path into thumb_url)
     * - returns JSON for your AJAX JS
     * ============================================================ */
    private function _save_common($ad_id = 0)
    {
        header('Content-Type: application/json');

        // ✅ validation
        $this->form_validation->set_rules('title', 'Title', 'required|trim');
        $this->form_validation->set_rules('duration_seconds', 'Duration', 'required|integer');
        $this->form_validation->set_rules('reward_usd', 'Reward', 'required|numeric');

        // ad_url required only if video_mode=url
        $video_mode = strtolower(trim((string) $this->input->post('video_mode')));
        if ($video_mode === '')
            $video_mode = 'url';

        if ($video_mode === 'url') {
            $this->form_validation->set_rules('ad_url', 'Ad URL', 'required|trim');
        }

        if ($this->form_validation->run() === FALSE) {
            echo json_encode(['status' => false, 'message' => strip_tags(validation_errors())]);
            return;
        }

        // ✅ base payload
        $payload = [
            'title' => trim((string) $this->input->post('title', true)),
            'description' => (string) $this->input->post('description'),
            'duration_seconds' => (int) $this->input->post('duration_seconds', true),
            'reward_usd' => (float) $this->input->post('reward_usd', true),
            'sort_order' => (int) $this->input->post('sort_order', true),
            'is_active' => $this->input->post('is_active') ? 1 : 0,
        ];
        if ($payload['sort_order'] <= 0)
            $payload['sort_order'] = 1;

        // ✅ VIDEO: URL or upload (save into ad_url)
        $ad_url = trim((string) $this->input->post('ad_url', true));

        if ($video_mode === 'upload') {

            // if file uploaded, store file path into ad_url
            if (!empty($_FILES['video_file']['name'])) {
                $video_path = $this->_upload_video('video_file');
                if ($video_path === false)
                    return; // error already echoed
                $ad_url = base_url() . $video_path;
            } else {
                // editing: keep existing if posted ad_url has existing path
                // add: if no file, error
                if ((int) $ad_id <= 0 && $ad_url === '') {
                    echo json_encode(['status' => false, 'message' => 'Please upload a video file']);
                    return;
                }
            }
        } else {
            // url mode must have URL (validated)
            if ($ad_url === '') {
                echo json_encode(['status' => false, 'message' => 'Ad URL is required']);
                return;
            }
        }

        // ✅ THUMB: URL or upload (save into thumb_url)
        $thumb_mode = strtolower(trim((string) $this->input->post('thumb_mode')));
        if ($thumb_mode === '')
            $thumb_mode = 'url';

        $thumb_url = trim((string) $this->input->post('thumb_url', true));
        if ($thumb_mode === 'upload') {
            if (!empty($_FILES['thumb_file']['name'])) {
                $thumb_path = $this->_upload_thumb('thumb_file');
                if ($thumb_path === false)
                    return;
                $thumb_url = base_url() . $thumb_path;
            }
        }

        $payload['ad_url'] = $ad_url;
        $payload['thumb_url'] = $thumb_url;

        // ✅ update vs insert
        if ((int) $ad_id > 0) {
            $exists = $this->db->get_where('earning_ads', ['id' => (int) $ad_id])->num_rows();
            if (!$exists) {
                echo json_encode(['status' => false, 'message' => 'Invalid Ad ID']);
                return;
            }
            $this->db->where('id', (int) $ad_id)->update('earning_ads', $payload);
            echo json_encode(['status' => true, 'message' => 'Ad updated successfully']);
            return;
        }

        $payload['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('earning_ads', $payload);
        echo json_encode(['status' => true, 'message' => 'Ad created successfully']);
    }

    // ✅ upload helpers (returns saved relative path like: assets/uploads/earning_ads/video_x.mp4)
    private function _upload_video($field)
    {

        if (ENABLE_SITE_UPLOAD_FUNCTION !== true) {
            $this->_dbg('earning_ads_upload_videos', 'Uploads disabled');
            return false;
        }

        if (empty($_FILES[$field]['name']))
            return false;

        $allowed_ext = ['mp4', 'webm', 'ogg'];
        $file_name = $_FILES[$field]['name'];
        $tmp = $_FILES[$field]['tmp_name'];
        $size = (int) $_FILES[$field]['size'];
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed_ext)) {
            echo json_encode(['status' => false, 'message' => 'Invalid video type. Allowed: mp4, webm, ogg']);
            return false;
        }

        // 50MB limit (change if needed)
        if ($size > 50 * 1024 * 1024) {
            echo json_encode(['status' => false, 'message' => 'Video too large. Max 50MB']);
            return false;
        }

        $dir = FCPATH . 'assets/uploads/earning_ads/videos/';
        if (!is_dir($dir))
            @mkdir($dir, 0777, true);

        $safe = preg_replace('/[^a-zA-Z0-9\._-]/', '_', $file_name);
        $new = 'video_' . time() . '_' . $safe;
        $full = $dir . $new;

        if (!move_uploaded_file($tmp, $full)) {
            echo json_encode(['status' => false, 'message' => 'Failed to upload video']);
            return false;
        }

        return 'assets/uploads/earning_ads/videos/' . $new;
    }

    private function _upload_thumb($field)
    {

        if (ENABLE_SITE_UPLOAD_FUNCTION !== true) {
            $this->_dbg('earning_upload_images', 'Uploads disabled');
            return false;
        }

        if (empty($_FILES[$field]['name']))
            return false;

        $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
        $file_name = $_FILES[$field]['name'];
        $tmp = $_FILES[$field]['tmp_name'];
        $size = (int) $_FILES[$field]['size'];
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed_ext)) {
            echo json_encode(['status' => false, 'message' => 'Invalid image type. Allowed: jpg, png, webp']);
            return false;
        }

        // 5MB limit
        if ($size > 5 * 1024 * 1024) {
            echo json_encode(['status' => false, 'message' => 'Image too large. Max 5MB']);
            return false;
        }

        $dir = FCPATH . 'assets/uploads/earning_ads/thumbs/';
        if (!is_dir($dir))
            @mkdir($dir, 0777, true);

        $safe = preg_replace('/[^a-zA-Z0-9\._-]/', '_', $file_name);
        $new = 'thumb_' . time() . '_' . $safe;
        $full = $dir . $new;

        if (!move_uploaded_file($tmp, $full)) {
            echo json_encode(['status' => false, 'message' => 'Failed to upload thumbnail']);
            return false;
        }

        return 'assets/uploads/earning_ads/thumbs/' . $new;
    }
}
