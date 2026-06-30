<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Landingpage  (Content Management -> Landing Page Settings)
 * -------------------------------------------------------------------
 * Admin CRUD for the dynamic public landing page.
 * Follows the existing settings-controller conventions:
 *   - extends CI_Controller, session/admin guard in __construct
 *   - AJAX endpoints echo json_encode(['status'=>bool,'message'=>...])
 *   - image uploads land in ./assets/img/landing/
 */
class Landingpage extends CI_Controller
{
    /** Allowed singleton sections (whitelist => allowed keys) */
    private $sections = array(
        'general'  => array('site_name','logo','logo_dark','favicon','theme_mode','primary_color','secondary_color','button_color','button_hover_color','background_color','font_family','enable_preloader','enable_dark_mode','copyright','footer_text'),
        'header'   => array('logo','mobile_logo','buy_btn_text','buy_btn_url','sticky_header','transparent_header'),
        'hero'     => array('small_title','main_title','highlight_text','description','email_placeholder','button_text','button_link','bottom_text','bottom_link_text','bottom_link','success_message','bg_image','hero_img1','hero_img2','hero_img3'),
        'features' => array('sub_title','title','highlight'),
        'marquee'  => array('text','speed','repeat','enable'),
        'token'    => array('sub_title','title','highlight','description','button_text','button_link','countdown_date','received_text','contribution_amount','min_goal','max_goal','wallet_address','progress_percentage'),
        'work'     => array('sub_title','title','highlight','image'),
        'exchange' => array('title','highlight','description','main_image','enable'),
        'crypto'   => array('sub_title','title','highlight'),
        'faq'      => array('sub_title','title','highlight','image'),
        'roadmap'  => array('sub_title','title','highlight'),
        'team'     => array('sub_title','title','highlight','description'),
        'footer'   => array('logo','sub_title','title','highlight','copyright','bg_image1','bg_image2'),
        'seo'      => array('meta_title','meta_description','meta_keywords','og_image','twitter_card','robots','canonical'),
        'social'   => array('facebook','twitter','telegram','discord','instagram','linkedin','youtube','github'),
        'scripts'  => array('header_scripts','footer_scripts','google_analytics','facebook_pixel','custom_css','custom_js'),
    );

    /** image field => yes, every other field is plain text */
    private $image_fields = array('logo','logo_dark','favicon','mobile_logo','bg_image','hero_img1','hero_img2','hero_img3','image','main_image','bg_image1','bg_image2','og_image');

    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');
        $this->load->model('Admin_model');
        $this->load->model('cms/Landing_model');

        if (!$this->session->userdata('logged_in')) {
            redirect('admin/login');
        }

        $user = $this->Admin_model->get_user($this->session->userdata('userid'));
        if ($user && $user->admin_roll == '1') {
            $permissions = json_decode($user->permission_pages, true);
            // Landing Page Settings lives under Content Management, so allow
            // either its own key OR the existing Content-Management key.
            if (empty($permissions['landing_page_cms']) && empty($permissions['website_content_cms'])) {
                $this->session->set_flashdata('error', 'Access Denied: You do not have permission.');
                redirect('admin');
            }
        }
    }

    /* ----------------------------- VIEW ----------------------------- */
    public function index()
    {
        $this->data['title']      = 'Landing Page Settings';
        $this->data['card_tilte'] = 'Landing Page Settings';

        // singleton sections
        foreach ($this->sections as $s => $keys) {
            $this->data[$s] = $this->Landing_model->get_section($s);
        }

        // item 4: SEO is shared with global Site Settings meta — prefill any
        // empty landing SEO field from site_settings so both stay in sync.
        $seo_defaults = array(
            'meta_title'       => site_settings('meta-settings', 'site-title'),
            'meta_description' => site_settings('meta-settings', 'site-description'),
            'meta_keywords'    => site_settings('meta-settings', 'site-keyword'),
        );
        foreach ($seo_defaults as $k => $v) {
            if (empty($this->data['seo'][$k]) && $v !== '') {
                $this->data['seo'][$k] = $v;
            }
        }
        // repeaters
        foreach ($this->Landing_model->repeaters as $key => $table) {
            $this->data['rep_' . $key] = $this->Landing_model->items($key);
        }
        $this->data['versions'] = $this->Landing_model->versions();

        $this->load->view('admin/cms/landing-page', $this->data);
    }

    /* ----------------------- SAVE SINGLETON ------------------------- */
    public function save_section()
    {
        $section = $this->input->post('section', true);
        if (!isset($this->sections[$section])) {
            return $this->json(false, 'Unknown section');
        }

        $fields = array();
        foreach ($this->sections[$section] as $key) {
            // checkbox toggles arrive only when checked
            if (in_array($key, array('enable','sticky_header','transparent_header','enable_preloader','enable_dark_mode'))) {
                $fields[$key] = $this->input->post($key) ? '1' : '0';
                continue;
            }
            // raw (allow scripts/css/html) vs xss-clean text
            $raw = in_array($section, array('scripts')) || in_array($key, array('description','answer'));
            $val = $this->input->post($key, !$raw);
            if ($val !== null) $fields[$key] = $val;
        }

        // handle any uploaded files for this section
        foreach ($this->sections[$section] as $key) {
            if (in_array($key, $this->image_fields) && !empty($_FILES[$key]['name'])) {
                $path = $this->upload_image($key);
                if ($path === false) return $this->json(false, 'Invalid image for ' . $key . ' (png/jpg/jpeg/svg/webp, max 4MB)');
                $fields[$key] = $path;
            }
        }

        // light validation
        foreach (array('button_link','buy_btn_url','canonical') as $u) {
            if (!empty($fields[$u]) && $fields[$u] !== '#' && !$this->valid_url_or_path($fields[$u])) {
                return $this->json(false, 'Invalid URL in ' . $u);
            }
        }

        $this->Landing_model->save_section($section, $fields);

        // item: unified meta — mirror SEO into global Site Settings so both
        // places stay identical and the landing page uses one source.
        if ($section === 'seo') {
            $map = array(
                'meta_title'       => 'site-title',
                'meta_description' => 'site-description',
                'meta_keywords'   => 'site-keyword',
            );
            foreach ($map as $lkey => $skey) {
                if (isset($fields[$lkey])) {
                    $this->db->where('settings_type', 'meta-settings')
                             ->where('settings_name', $skey)
                             ->update('site_settings', array('settings_value' => $fields[$lkey]));
                }
            }
        }

        return $this->json(true, ucfirst($section) . ' settings saved');
    }

    /* ------------------------- REPEATER CRUD ------------------------ */
    public function item_save($repeater)
    {
        if (!isset($this->Landing_model->repeaters[$repeater])) {
            return $this->json(false, 'Unknown section');
        }
        $id     = (int)$this->input->post('id');
        $fields = $this->repeater_fields($repeater);

        // images
        foreach (array('image','icon','photo') as $imgf) {
            if (!empty($_FILES[$imgf]['name'])) {
                $path = $this->upload_image($imgf);
                if ($path === false) return $this->json(false, 'Invalid image (png/jpg/jpeg/svg/webp, max 4MB)');
                $fields[$imgf] = $path;
            }
        }

        if (empty($fields)) return $this->json(false, 'Nothing to save');

        $newid = $this->Landing_model->save_item($repeater, $fields, $id);
        return $this->json(true, 'Saved', array('id' => $newid));
    }

    public function item_delete($repeater, $id)
    {
        if (!isset($this->Landing_model->repeaters[$repeater])) return $this->json(false, 'Unknown section');
        $this->Landing_model->delete_item($repeater, (int)$id);
        return $this->json(true, 'Deleted');
    }

    public function item_status($repeater, $id)
    {
        if (!isset($this->Landing_model->repeaters[$repeater])) return $this->json(false, 'Unknown section');
        $new = $this->Landing_model->toggle_status($repeater, (int)$id);
        return $this->json(true, 'Status updated', array('status' => $new));
    }

    public function item_reorder($repeater)
    {
        if (!isset($this->Landing_model->repeaters[$repeater])) return $this->json(false, 'Unknown section');
        $order = $this->input->post('order');               // array of ids
        if (!is_array($order)) $order = json_decode($order, true);
        if (!is_array($order)) return $this->json(false, 'Bad order payload');
        // guard against duplicate sort values
        if (count($order) !== count(array_unique($order))) {
            return $this->json(false, 'Duplicate sort order detected');
        }
        $this->Landing_model->reorder($repeater, $order);
        return $this->json(true, 'Order saved');
    }

    /** Whitelisted column extraction per repeater */
    private function repeater_fields($repeater)
    {
        $map = array(
            'menu'           => array('parent_id','title','url','new_tab','is_external','sort_order','status'),
            'brands'         => array('image','alt','sort_order','status'),
            'features'       => array('title','highlight','description','icon','sort_order','status'),
            'work'           => array('number','title','highlight','description','image','sort_order','status'),
            'exchange_logos' => array('image','sort_order','status'),
            'crypto'         => array('title','highlight','button_text','button_link','icon','sort_order','status'),
            'faq'            => array('question','answer','sort_order','status'),
            'roadmap'        => array('year','title','description','icon','sort_order','status'),
            'team'           => array('photo','name','position','facebook','twitter','telegram','discord','linkedin','sort_order','status'),
        );
        $out = array();
        foreach ($map[$repeater] as $col) {
            $val = $this->input->post($col);
            if ($val === null) continue;
            if (in_array($col, array('new_tab','is_external','status'))) $val = $val ? 1 : 0;
            $out[$col] = ($col === 'answer' || $col === 'description') ? $val : $this->security->xss_clean($val);
        }
        return $out;
    }

    /* --------------------- VERSION / IMPORT / EXPORT ---------------- */
    public function export()
    {
        $snap = $this->Landing_model->snapshot();
        $this->output
            ->set_content_type('application/json')
            ->set_header('Content-Disposition: attachment; filename="landing-export-' . date('Ymd-His') . '.json"')
            ->set_output(json_encode($snap, JSON_PRETTY_PRINT));
    }

    public function import()
    {
        if (empty($_FILES['import_file']['tmp_name'])) return $this->json(false, 'No file uploaded');
        $json = file_get_contents($_FILES['import_file']['tmp_name']);
        $data = json_decode($json, true);
        if (!is_array($data)) return $this->json(false, 'Invalid JSON file');
        $this->Landing_model->save_version('Auto-backup before import');
        $this->Landing_model->restore($data);
        return $this->json(true, 'Imported successfully');
    }

    public function save_version()
    {
        $label = $this->input->post('label', true) ?: ('Snapshot ' . date('Y-m-d H:i'));
        $id = $this->Landing_model->save_version($label, $this->session->userdata('userid'));
        return $this->json(true, 'Version saved', array('id' => $id));
    }

    public function restore_version($id)
    {
        $v = $this->Landing_model->version((int)$id);
        if (!$v) return $this->json(false, 'Version not found');
        $this->Landing_model->save_version('Auto-backup before restore');
        $this->Landing_model->restore(json_decode($v->snapshot, true));
        return $this->json(true, 'Version restored');
    }

    /* ------------------------------ HELPERS ------------------------- */

    /** Validates + moves an uploaded image, returns web path or false */
    private function upload_image($field)
    {
        if (empty($_FILES[$field]['name'])) return '';

        $allowed_ext  = array('png','jpg','jpeg','gif','svg','webp');
        $allowed_mime = array('image/png','image/jpeg','image/gif','image/svg+xml','image/webp');
        $max          = 4 * 1024 * 1024; // 4 MB

        $name = $_FILES[$field]['name'];
        $size = $_FILES[$field]['size'];
        $tmp  = $_FILES[$field]['tmp_name'];
        $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed_ext)) return false;
        if ($size > $max) return false;

        // mime sniff (svg has no finfo image type, so allow by ext)
        if ($ext !== 'svg' && function_exists('finfo_file')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $tmp);
            finfo_close($finfo);
            if (!in_array($mime, $allowed_mime)) return false;
        }

        $dir = './assets/img/landing/';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);

        $safe = 'lp_' . time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $name);
        if (!move_uploaded_file($tmp, $dir . $safe)) return false;

        return 'assets/img/landing/' . $safe;
    }

    private function valid_url_or_path($v)
    {
        if (preg_match('#^(https?:)?//#', $v)) return filter_var($v, FILTER_VALIDATE_URL) !== false || strpos($v, '//') === 0;
        // relative path / anchor allowed
        return (bool)preg_match('#^[a-zA-Z0-9_\-./#?=&%]+$#', $v);
    }

    private function json($status, $message, $extra = array())
    {
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array_merge(
                array('status' => $status, 'message' => $message), $extra)));
    }
}
