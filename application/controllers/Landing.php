<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Landing — public dynamic landing page.
 * Pulls all content from the landing_* tables and renders the Webze
 * template view. No design changes: same HTML/CSS/JS, dynamic content.
 */
class Landing extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('cms/Landing_model');
        $this->load->helper('landing');
        $this->load->helper('url');
    }

    public function index()
    {
        $m = $this->Landing_model;

        // singleton sections
        foreach (array('general','header','hero','features','marquee','token','work',
                       'exchange','crypto','faq','roadmap','team','footer','seo','social','scripts') as $s) {
            $this->data[$s] = $m->get_section($s);
        }

        // repeaters (active only, ordered)
        $this->data['menu']           = $m->items('menu', true);
        $this->data['brands']         = $m->items('brands', true);
        $this->data['feature_items']  = $m->items('features', true);
        $this->data['work_items']     = $m->items('work', true);
        $this->data['exchange_logos'] = $m->items('exchange_logos', true);
        $this->data['crypto_cards']   = $m->items('crypto', true);
        $this->data['faq_items']      = $m->items('faq', true);
        $this->data['roadmap_items']  = $m->items('roadmap', true);
        $this->data['team_members']   = $m->items('team', true);

        $this->load->view('user/landing/index', $this->data);
    }

    /**
     * Hero "Get Early Access" form -> sends a notification email using the
     * platform SMTP settings (email_config) and captures the lead.
     * Returns JSON {status, message, redirect?}.
     */
    public function early_access()
    {
        // allow the form to be posted from any site (config.php toggle) + preflight
        $this->_cors();

        // feature on/off switch (config.php)
        if (defined('LANDING_EARLY_ACCESS_ENABLED') && LANDING_EARLY_ACCESS_ENABLED !== true) {
            return $this->_json(false, 'Early access is currently closed.');
        }

        $email = trim((string)$this->input->post('email', true));
        $name  = trim((string)$this->input->post('name', true));
        $phone = trim((string)$this->input->post('phone', true));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->_json(false, 'Please enter a valid email address.');
        }

        // capture lead (only if the table exists — see db/landing_leads.sql)
        if ($this->db->table_exists('landing_leads')) {
            $this->db->insert('landing_leads', array(
                'name'         => $name,
                'email'        => $email,
                'phone'        => $phone,
                'source'       => 'hero_early_access',
                'landing_page' => 'landing',
                'status'       => 'new',
                'ip'           => $this->input->ip_address(),
                'created_at'   => date('Y-m-d H:i:s'),
            ));
        }

        // success message + optional redirect come from the Hero config
        $hero = $this->Landing_model->get_section('hero');
        $success = !empty($hero['success_message']) ? $hero['success_message']
                   : "Thank you! We'll be in touch soon.";
        $redirect = !empty($hero['button_link']) && $hero['button_link'] !== '#'
                   ? $hero['button_link'] : '';

        // send via SMTP (non-fatal: lead is already captured)
        $this->_send_smtp_notification($email, $name, $phone);

        return $this->_json(true, $success, array('redirect' => $redirect));
    }

    /** Send the lead notification through the configured SMTP / email_config. */
    private function _send_smtp_notification($email, $name = '', $phone = '')
    {
        $cfg = $this->db->query("SELECT * FROM email_config WHERE id = '1'")->row();
        if (!$cfg) return false;

        $admin_to = site_settings('company', 'email');
        if (!$admin_to) $admin_to = $cfg->from_mail;
        if (!$admin_to) return false;

        $subject = 'New Early-Access request';
        $body  = '<h3>New early-access lead</h3>';
        $body .= '<p><b>Email:</b> ' . html_escape($email) . '</p>';
        if ($name)  $body .= '<p><b>Name:</b> ' . html_escape($name) . '</p>';
        if ($phone) $body .= '<p><b>Phone:</b> ' . html_escape($phone) . '</p>';
        $body .= '<p><b>Source:</b> Landing page (Get Early Access)</p>';
        $body .= '<p><b>Time:</b> ' . date('Y-m-d H:i:s') . '</p>';

        // SMTP enabled -> PHPMailer, else PHP mail() fallback (same as Newsletter)
        if (isset($cfg->smtp_status) && $cfg->smtp_status > 0) {
            require_once APPPATH . 'libraries/smtp/vendor/phpmailer/phpmailer/src/Exception.php';
            require_once APPPATH . 'libraries/smtp/vendor/phpmailer/phpmailer/src/PHPMailer.php';
            require_once APPPATH . 'libraries/smtp/vendor/phpmailer/phpmailer/src/SMTP.php';

            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = $cfg->host;
                $mail->SMTPAuth   = $cfg->smtp_auth;
                $mail->Username   = $cfg->username;
                $mail->Password   = $cfg->password;
                $mail->SMTPSecure = $cfg->smtpsecure;
                $mail->Port       = $cfg->port;
                $mail->setFrom($cfg->from_mail, $cfg->from_name);
                $mail->addAddress($admin_to);
                $mail->addReplyTo($email);
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body    = $body;
                $mail->send();
                return true;
            } catch (\Exception $e) {
                log_message('error', 'early_access SMTP failed: ' . $e->getMessage());
                return false;
            }
        }

        $headers  = "From: " . ($cfg->php_mail ?: $cfg->from_mail) . "\r\n";
        $headers .= "Reply-To: " . $email . "\r\n";
        $headers .= "MIME-Version: 1.0\r\nContent-Type: text/html; charset=utf-8\r\n";
        return @mail($admin_to, $subject, $body, $headers);
    }

    /** CORS for the early-access endpoint (gated by config.php), incl. preflight. */
    private function _cors()
    {
        if (defined('LANDING_EARLY_ACCESS_ALLOW_ANY_ORIGIN') && LANDING_EARLY_ACCESS_ALLOW_ANY_ORIGIN === true) {
            $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*';
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Vary: Origin');
            header('Access-Control-Allow-Methods: POST, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
        }
        // answer the browser's preflight and stop
        if (strtoupper($this->input->method()) === 'OPTIONS') {
            $this->output->set_status_header(204);
            exit;
        }
    }

    private function _json($status, $message, $extra = array())
    {
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array_merge(
                array('status' => $status, 'message' => $message), $extra)));
    }
}
