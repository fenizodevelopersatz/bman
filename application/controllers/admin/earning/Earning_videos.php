<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Earning_videos extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->library(['session', 'form_validation', 'upload']);
        $this->load->helper(['url', 'form']);
        $this->load->model('Admin_model');

        if (!$this->session->userdata('admin_logged_in')) {
            redirect('admin/login');
        }

        // ✅ permission check example (optional - change key name as you need)
        $user = $this->Admin_model->get_user($this->session->userdata('admin_userid'));
        if ($user && $user->admin_roll == '1') {
            $permissions = json_decode($user->permission_pages, true);
            if (empty($permissions['earning_videos'])) {
                $this->session->set_flashdata('error', 'Access Denied: You do not have permission.');
                redirect('admin');
            }
        }
    }

    // ✅ page
    public function index()
    {
        $this->data['title'] = "Watch Videos (Premium)";
        $this->data['card_tilte'] = "Video List";
        $this->load->view('admin/earnings/video_list', $this->data);
    }

    // ✅ datatable server-side JSON
    public function list()
    {
        $draw = (int) $this->input->get('draw');
        $start = (int) $this->input->get('start');
        $length = (int) $this->input->get('length');

        $search = '';
        $searchArr = $this->input->get('search');
        if (is_array($searchArr) && isset($searchArr['value'])) {
            $search = trim($searchArr['value']);
        }

        // total
        $total_records = $this->db->count_all('earning_videos');

        // filtered
        if ($search !== '') {
            $this->db->group_start()
                ->like('title', $search)
                ->or_like('video_url', $search)
                ->or_like('description', $search)
                ->group_end();
        }
        $recordsFiltered = $this->db->count_all_results('earning_videos', false);

        // data
        $this->db->order_by('sort_order', 'ASC');
        $this->db->limit($length, $start);
        $rows = $this->db->get()->result_array();

        $data = [];
        $i = $start;

        foreach ($rows as $r) {
            $i++;

            $checked = ((int) $r['is_active'] === 1) ? 'checked' : '';
            $statusUrl = base_url("admin/earning-videos/status/" . (int) $r['id']);

            $data[] = [
                'RecordID' => $i,
                'title' =>
                    '<div class="d-flex justify-content-start flex-column">
                        <span class="fw-bold text-gray-900">' . htmlspecialchars($r['title']) . '</span>
                        <span class="text-gray-500 fs-7">' . htmlspecialchars($r['video_url']) . '</span>
                    </div>',
                'reward' => '<span class="badge badge-light-success">$' . number_format((float) $r['reward_usd'], 2) . '</span>',
                'duration' => '<span class="badge badge-light-primary">' . (int) $r['duration_seconds'] . 's</span>',
                'sort' => '<span class="badge badge-light">' . (int) $r['sort_order'] . '</span>',
                'status' =>
                    '<div class="form-check form-switch form-check-custom form-check-success form-check-solid">
                        <input class="form-check-input h-30px w-50px js-video-status" type="checkbox" ' . $checked . '
                          data-status-url="' . $statusUrl . '">
                    </div>',
                'action' =>
                    '<div class="d-flex justify-content-center flex-row">
                        <a class="btn btn-success btn-active-light-success btn-sm me-3" href="' . base_url('admin/earning-videos/add?id=' . (int) $r['id']) . '">
                          <i class="fa-solid fa-pen-to-square"></i> Edit
                        </a>
                        <a class="btn btn-danger btn-active-light-danger btn-sm btn-delete" href="javascript:void(0);"
                           data-delete-url="' . base_url('admin/earning-videos/delete/' . (int) $r['id']) . '">
                          <i class="fa fa-trash"></i> Delete
                        </a>
                    </div>',
            ];
        }

        echo json_encode([
            'draw' => $draw,
            'recordsTotal' => $total_records,
            'recordsFiltered' => ($search !== '') ? $recordsFiltered : $total_records,
            'data' => $data
        ]);
    }

    // ✅ load add/edit page
    public function add()
    {
        $id = (int) $this->input->get('id');

        $row = null;
        if ($id > 0) {
            $row = $this->db->get_where('earning_videos', ['id' => $id])->row();
            if (!$row) {
                show_404();
                return;
            }
        }

        $this->data['title'] = ($id > 0) ? "Edit Video" : "Add Video";
        $this->data['card_title'] = ($id > 0) ? "Edit Premium Video" : "New Premium Video";
        $this->data['mode'] = ($id > 0) ? "edit" : "add";

        $this->data['video_id'] = $row->id ?? '';
        $this->data['title_val'] = $row->title ?? '';
        $this->data['description_val'] = $row->description ?? '';
        $this->data['video_url_val'] = $row->video_url ?? '';
        $this->data['thumb_url_val'] = $row->thumb_url ?? '';
        $this->data['duration_val'] = $row->duration_seconds ?? 30;
        $this->data['reward_val'] = $row->reward_usd ?? 1.50;
        $this->data['sort_val'] = $row->sort_order ?? 1;
        $this->data['active_val'] = isset($row->is_active) ? (int) $row->is_active : 1;

        $this->load->view('admin/earnings/video_add', $this->data);
    }

    // ✅ save insert/update (AJAX FormData)
    public function save()
    {
        header('Content-Type: application/json');

        $video_id = (int) $this->input->post('video_id');

        $this->form_validation->set_rules('title', 'Title', 'required|trim');
        $this->form_validation->set_rules('duration_seconds', 'Duration', 'required|integer');
        $this->form_validation->set_rules('reward_usd', 'Reward', 'required|numeric');

        // NOTE: video_url required only when mode=url (handled in view JS),
        // but we still validate safely here.
        $video_mode = $this->input->post('video_mode');
        if ($video_mode === 'url') {
            $this->form_validation->set_rules('video_url', 'Video URL', 'required|trim');
        }

        if ($this->form_validation->run() === FALSE) {
            echo json_encode(['status' => false, 'message' => strip_tags(validation_errors())]);
            return;
        }

        $payload = [
            'title' => trim((string) $this->input->post('title', true)),
            'description' => $this->input->post('description'),
            'duration_seconds' => (int) $this->input->post('duration_seconds', true),
            'reward_usd' => (float) $this->input->post('reward_usd', true),
            'sort_order' => (int) $this->input->post('sort_order', true),
            'is_active' => $this->input->post('is_active') ? 1 : 0,
        ];

        // ✅ VIDEO: save into video_url (same field)
        if ($video_mode === 'upload' && isset($_FILES['video_file']) && !empty($_FILES['video_file']['name'])) {

            if (ENABLE_SITE_UPLOAD_FUNCTION !== true) {
                $this->_dbg('earning_videos_upload_images', 'Uploads disabled');
                return false;
            }

            $uploadPath = FCPATH . 'uploads/earning_videos/';
            if (!is_dir($uploadPath))
                @mkdir($uploadPath, 0755, true);

            $config = [
                'upload_path' => $uploadPath,
                'allowed_types' => 'mp4|webm|ogg',
                'max_size' => 0, // use php.ini limit
                'encrypt_name' => true
            ];
            $this->upload->initialize($config);

            if (!$this->upload->do_upload('video_file')) {
                echo json_encode(['status' => false, 'message' => strip_tags($this->upload->display_errors())]);
                return;
            }

            $u = $this->upload->data();
            $payload['video_url'] = base_url('uploads/earning_videos/' . $u['file_name']);
        } else {
            // url mode or keep existing
            $video_url = trim((string) $this->input->post('video_url', true));
            if ($video_url !== '')
                $payload['video_url'] = $video_url;
        }

        // ✅ THUMB: save into thumb_url (same field)
        $thumb_mode = $this->input->post('thumb_mode');
        if ($thumb_mode === 'upload' && isset($_FILES['thumb_file']) && !empty($_FILES['thumb_file']['name'])) {

            if (ENABLE_SITE_UPLOAD_FUNCTION !== true) {
                $this->_dbg('earning_videos_upload_images2', 'Uploads disabled');
                return false;
            }

            $uploadPath = FCPATH . 'uploads/earning_videos/thumbs/';
            if (!is_dir($uploadPath))
                @mkdir($uploadPath, 0755, true);

            $config = [
                'upload_path' => $uploadPath,
                'allowed_types' => 'jpg|jpeg|png|webp',
                'max_size' => 0,
                'encrypt_name' => true
            ];
            $this->upload->initialize($config);

            if (!$this->upload->do_upload('thumb_file')) {
                echo json_encode(['status' => false, 'message' => strip_tags($this->upload->display_errors())]);
                return;
            }

            $u = $this->upload->data();
            $payload['thumb_url'] = base_url('uploads/earning_videos/thumbs/' . $u['file_name']);
        } else {
            $thumb_url = trim((string) $this->input->post('thumb_url', true));
            if ($thumb_url !== '')
                $payload['thumb_url'] = $thumb_url;
        }

        if ($payload['sort_order'] <= 0)
            $payload['sort_order'] = 1;

        // update/insert
        if ($video_id > 0) {
            $exists = $this->db->get_where('earning_videos', ['id' => $video_id])->num_rows();
            if (!$exists) {
                echo json_encode(['status' => false, 'message' => 'Invalid Video ID']);
                return;
            }
            $this->db->where('id', $video_id)->update('earning_videos', $payload);
            echo json_encode(['status' => true, 'message' => 'Video updated successfully']);
            return;
        }

        $payload['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('earning_videos', $payload);
        echo json_encode(['status' => true, 'message' => 'Video created successfully']);
    }

    // ✅ status toggle
    public function status($id)
    {
        header('Content-Type: application/json');

        $id = (int) $id;
        $exists = $this->db->get_where('earning_videos', ['id' => $id])->num_rows();
        if (!$exists) {
            echo json_encode(['status' => false, 'message' => 'Invalid Video ID']);
            return;
        }

        $status = $this->input->post('is_active');
        $val = $status ? 1 : 0;

        $this->db->where('id', $id)->update('earning_videos', ['is_active' => $val]);
        echo json_encode(['status' => true, 'message' => 'Status updated']);
    }

    // ✅ delete
    public function delete($id)
    {
        header('Content-Type: application/json');

        $id = (int) $id;
        $exists = $this->db->get_where('earning_videos', ['id' => $id])->num_rows();
        if (!$exists) {
            echo json_encode(['status' => false, 'message' => 'Invalid Video ID']);
            return;
        }

        $this->db->where('id', $id)->delete('earning_videos');
        echo json_encode(['status' => true, 'message' => 'Video deleted successfully']);
    }
}
