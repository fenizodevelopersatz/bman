<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Earning_methods extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library(['session', 'form_validation']);
        $this->load->helper(['url', 'security']);
        $this->load->database();

        // ✅ you can add your admin auth + permission checks here (same like FAQ)
        if (!$this->session->userdata('logged_in')) {
            redirect('admin/login');
        }
    }

    /* ---------- helpers ---------- */
    private function _json($data = [], $code = 200)
    {
        $this->output
            ->set_status_header($code)
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }

    private function _csrf()
    {
        return [
            'name' => $this->security->get_csrf_token_name(),
            'hash' => $this->security->get_csrf_hash(),
        ];
    }

    /* ---------- page ---------- */
    public function index()
    {
        $data['title'] = 'Reward Settings';
        $data['card_tilte'] = 'Earning Methods';
        $this->load->view('admin/earnings/earning_methods_list', $data);
    }

    /* ---------- datatable source (serverSide compatible) ---------- */
    public function list()
    {
        $draw = (int) $this->input->get('draw');
        $start = (int) $this->input->get('start');
        $length = (int) $this->input->get('length');
        $search = trim((string) ($this->input->get('search')['value'] ?? ''));

        // count total
        $total_records = $this->db->count_all('earning_methods');

        // base query
        $this->db->from('earning_methods');

        if ($search !== '') {
            $this->db->group_start()
                ->like('code', $search)
                ->or_like('title', $search)
                ->or_like('subtitle', $search)
                ->group_end();
        }

        $filtered_count = $this->db->count_all_results('', false); // keep query

        $this->db->order_by('sort_order', 'ASC');
        $this->db->limit($length, $start);
        $rows = $this->db->get()->result_array();

        $data = [];
        $i = $start;

        foreach ($rows as $r) {
            $i++;

            $statusChecked = ((int) $r['is_active'] === 1) ? 'checked' : '';
            $statusUrl = base_url('admin/earning-methods/status/' . (int) $r['id']);

            $titleCol = '
                <div class="d-flex justify-content-start flex-column">
                    <span class="fw-bold text-gray-900">' . html_escape($r['title']) . '</span>
                    <span class="text-gray-500 fs-7">' . html_escape($r['subtitle']) . '</span>
                    <span class="badge mt-2" style="background:' . html_escape($r['badge_bg']) . ';color:' . html_escape($r['badge_color']) . '">' . html_escape($r['badge_text']) . '</span>
                </div>
            ';

            $rewardCol = '<span class="badge badge-light-success">$' . number_format((float) $r['reward_usd'], 2) . '</span>';
            $targetCol = '<span class="badge badge-light-primary">' . (int) $r['daily_target'] . '</span>';
            $timeCol = '<span class="badge badge-light-info">' . html_escape($r['est_time_label']) . '</span>';

            $btnPreview = '
                <div class="d-flex flex-column">
                    <div class="text-gray-700 fw-semibold">' . html_escape($r['btn_text']) . '</div>
                    <div class="mini-muted" style="font-size:12px;color:#7e8299;">' . html_escape($r['btn_gradient']) . '</div>
                </div>
            ';

            $statusCol = '
                <div class="form-check form-switch form-check-custom form-check-success form-check-solid">
                    <input class="form-check-input h-30px w-50px js-method-status"
                        type="checkbox" ' . $statusChecked . '
                        data-status-url="' . html_escape($statusUrl) . '">
                </div>
            ';

            $editBtn = '
                <button type="button"
                    class="btn btn-sm btn-primary js-edit"
                    data-id="' . (int) $r['id'] . '">
                    <i class="fa-solid fa-pen-to-square"></i> Edit
                </button>
            ';

            $data[] = [
                'RecordID' => $i,
                'temp_title' => $titleCol,
                'temp_reward' => $rewardCol,
                'temp_target' => $targetCol,
                'temp_time' => $timeCol,
                'temp_btn' => $btnPreview,
                'temp_sort' => '<span class="badge badge-light">' . (int) $r['sort_order'] . '</span>',
                'temp_status' => $statusCol,
                'temp_action' => $editBtn,
            ];
        }

        return $this->_json([
            'draw' => $draw,
            'recordsTotal' => $total_records,
            'recordsFiltered' => $filtered_count,
            'data' => $data,
            'csrf' => $this->_csrf(),
        ]);
    }

    /* ---------- get one row for modal ---------- */
    public function show($id)
    {
        if (!$this->input->is_ajax_request())
            show_404();

        $row = $this->db->get_where('earning_methods', ['id' => (int) $id])->row_array();
        if (!$row)
            return $this->_json(['status' => false, 'message' => 'Not found', 'csrf' => $this->_csrf()], 404);

        return $this->_json(['status' => true, 'item' => $row, 'csrf' => $this->_csrf()]);
    }

    /* ---------- update from modal ---------- */
    public function save($id)
    {
        if (!$this->input->is_ajax_request())
            show_404();
        header('Content-Type: application/json');

        $id = (int) $id;
        $exists = $this->db->get_where('earning_methods', ['id' => $id])->num_rows();
        if (!$exists)
            return $this->_json(['status' => false, 'message' => 'Invalid ID', 'csrf' => $this->_csrf()], 404);

        // ✅ validations
        $this->form_validation->set_rules('title', 'Title', 'required|trim');
        $this->form_validation->set_rules('subtitle', 'Subtitle', 'required|trim');
        $this->form_validation->set_rules('badge_text', 'Badge Text', 'required|trim');
        $this->form_validation->set_rules('badge_bg', 'Badge BG', 'required|trim');
        $this->form_validation->set_rules('badge_color', 'Badge Color', 'required|trim');
        $this->form_validation->set_rules('btn_text', 'Button Text', 'required|trim');
        $this->form_validation->set_rules('btn_gradient', 'Button Gradient', 'required|trim');
        $this->form_validation->set_rules('daily_target', 'Daily Target', 'required|integer');
        $this->form_validation->set_rules('reward_usd', 'Reward USD', 'required|numeric');
        $this->form_validation->set_rules('est_time_label', 'Est Time', 'required|trim');
        $this->form_validation->set_rules('sort_order', 'Sort Order', 'required|integer');

        if ($this->form_validation->run() === FALSE) {
            return $this->_json(['status' => false, 'message' => strip_tags(validation_errors()), 'csrf' => $this->_csrf()], 422);
        }

        $payload = [
            'title' => $this->input->post('title', true),
            'subtitle' => $this->input->post('subtitle', true),
            'icon' => $this->input->post('icon', true),
            'badge_text' => $this->input->post('badge_text', true),
            'badge_bg' => $this->input->post('badge_bg', true),
            'badge_color' => $this->input->post('badge_color', true),
            'progress_color' => $this->input->post('progress_color', true),
            'btn_text' => $this->input->post('btn_text', true),
            'btn_gradient' => $this->input->post('btn_gradient', true),
            'daily_target' => (int) $this->input->post('daily_target', true),
            'reward_usd' => (float) $this->input->post('reward_usd', true),
            'est_time_label' => $this->input->post('est_time_label', true),
            'is_active' => $this->input->post('is_active') ? 1 : 0,
            'sort_order' => (int) $this->input->post('sort_order', true),
        ];

        $this->db->where('id', $id)->update('earning_methods', $payload);

        return $this->_json(['status' => true, 'message' => 'Updated successfully', 'csrf' => $this->_csrf()]);
    }

    /* ---------- toggle status ---------- */
    public function status($id)
    {
        if (!$this->input->is_ajax_request())
            show_404();
        header('Content-Type: application/json');

        $id = (int) $id;
        $exists = $this->db->get_where('earning_methods', ['id' => $id])->num_rows();
        if (!$exists)
            return $this->_json(['status' => false, 'message' => 'Invalid ID', 'csrf' => $this->_csrf()], 404);

        $status = $this->input->post('template_status');
        $is_active = ($status ? 1 : 0);

        $this->db->where('id', $id)->update('earning_methods', ['is_active' => $is_active]);

        return $this->_json(['status' => true, 'message' => 'Status updated', 'csrf' => $this->_csrf()]);
    }
}
