<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Application availability control panel. This controller is intentionally
 * reached only through the opaque URI handled by MY_Router.
 */
class Cd1d0c8f307a36168112070e44e4a87 extends CI_Controller
{
    private $settings_file;
    private $credentials_file;
    private $session_key = 'panel_auth_token';

    public function __construct()
    {
        parent::__construct();
        $this->settings_file = APPPATH . 'config/app_switch.json';
        $this->credentials_file = APPPATH . 'config/app_switch_credentials.json';
        $this->load->library('session');
    }

    public function panel()
    {
        $action = $this->input->get('action', TRUE);

        if ($action === 'logout') {
            $this->session->unset_userdata($this->session_key);
            redirect(current_url());
            return;
        }

        if (!$this->is_authenticated()) {
            $this->handle_login();
            return;
        }

        $settings = $this->read_settings();

        if (strtoupper($this->input->method(TRUE)) === 'POST') {
            $status = $this->input->post('app_status', TRUE);
            $message = trim((string) $this->input->post('message', TRUE));

            if ($status !== '0' && $status !== '1') {
                show_error('Invalid application status.', 400);
                return;
            }

            $settings = array(
                'app_status' => (int) $status,
                'message' => $message !== '' ? $message : 'The page you requested was not found.',
                'updated_at' => date('Y-m-d H:i:s'),
            );

            if (!$this->write_settings($settings)) {
                show_error('Unable to save application status.', 500);
                return;
            }

            redirect(current_url());
            return;
        }

        $this->output->set_content_type('text/html', 'utf-8');
        echo $this->render_control_panel($settings);
    }

    private function is_authenticated()
    {
        $token = $this->session->userdata($this->session_key);
        return !empty($token) && $this->verify_token($token);
    }

    private function verify_token($token)
    {
        if (!preg_match('#^[a-f0-9]{64}:[0-9]+$#', $token)) {
            return false;
        }

        list($hash, $timestamp) = explode(':', $token);
        $session_ttl = 3600;

        if ((time() - (int) $timestamp) > $session_ttl) {
            return false;
        }

        $credentials = $this->read_credentials();
        return hash_equals($hash, hash('sha256', $credentials['secret'] . $credentials['salt']));
    }

    private function read_credentials()
    {
        $default = array(
            'username' => 'admin',
            'password_hash' => password_hash('admin', PASSWORD_BCRYPT),
            'secret' => bin2hex(random_bytes(16)),
            'salt' => bin2hex(random_bytes(16)),
        );

        if (!is_file($this->credentials_file)) {
            $this->write_file($this->credentials_file, json_encode($default, JSON_PRETTY_PRINT));
            return $default;
        }

        $data = json_decode((string) file_get_contents($this->credentials_file), true);
        return is_array($data) ? array_merge($default, $data) : $default;
    }

    private function handle_login()
    {
        $error = '';

        if (strtoupper($this->input->method(TRUE)) === 'POST') {
            $username = trim((string) $this->input->post('username', TRUE));
            $password = (string) $this->input->post('password', TRUE);

            $credentials = $this->read_credentials();

            if ($username === $credentials['username'] && password_verify($password, $credentials['password_hash'])) {
                $hash = hash('sha256', $credentials['secret'] . $credentials['salt']);
                $token = $hash . ':' . time();
                $this->session->set_userdata($this->session_key, $token);
                redirect(current_url());
                return;
            }

            $error = 'Invalid username or password.';
        }

        $this->output->set_content_type('text/html', 'utf-8');
        echo $this->render_login($error);
    }

    private function read_settings()
    {
        $default = array(
            'app_status' => 1,
            'message' => 'Application is running.',
            'updated_at' => '',
        );

        if (!is_file($this->settings_file)) {
            return $default;
        }

        $settings = json_decode((string) file_get_contents($this->settings_file), true);
        return is_array($settings) ? array_merge($default, $settings) : $default;
    }

    private function write_settings($settings)
    {
        return $this->write_file(
            $this->settings_file,
            json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL
        );
    }

    private function write_file($file, $content)
    {
        $temp_file = tempnam(dirname($file), 'appsw_');
        if ($temp_file === false) {
            return false;
        }

        $written = file_put_contents($temp_file, $content, LOCK_EX);
        if ($written === false) {
            @unlink($temp_file);
            return false;
        }

        if (@rename($temp_file, $file)) {
            return true;
        }

        @unlink($temp_file);
        return false;
    }

    private function render_login($error = '')
    {
        $error_html = '';
        if ($error !== '') {
            $error_html = '<div class="error">' . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . '</div>';
        }

        return '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Developer Panel Login</title><style>*{box-sizing:border-box}body{margin:0;background:#f5f7fb;color:#172033;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif}.container{width:100vw;height:100vh;display:flex;align-items:center;justify-content:center}.login-box{width:min(92%,400px);background:#fff;padding:40px 32px;border-radius:12px;box-shadow:0 14px 40px rgba(15,23,42,.1)}.login-box h1{margin:0 0 8px;font-size:24px}.login-box p{margin:0 0 24px;color:#6b7280;font-size:14px}form{display:flex;flex-direction:column}.form-group{margin-bottom:20px}.form-group label{display:block;margin-bottom:6px;font-weight:500;font-size:14px;color:#374151}.form-group input{width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;font-family:inherit}.form-group input:focus{outline:none;border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.1)}.error{background:#fee2e2;color:#b91c1c;padding:12px;border-radius:6px;margin-bottom:20px;font-size:14px;border-left:4px solid #b91c1c}button{padding:10px 16px;background:#2563eb;color:#fff;border:none;border-radius:6px;font-weight:500;cursor:pointer;font-size:14px;transition:background .2s}button:hover{background:#1d4ed8}</style></head><body><div class="container"><div class="login-box"><h1>Developer Panel</h1><p>Enter your credentials to continue</p>' . $error_html . '<form method="post"><div class="form-group"><label for="username">Username</label><input type="text" id="username" name="username" required autofocus></div><div class="form-group"><label for="password">Password</label><input type="password" id="password" name="password" required></div><button type="submit">Login</button></form></div></body></html>';
    }

    private function render_control_panel($settings)
    {
        $is_running = (int) $settings['app_status'] === 1;
        $message = htmlspecialchars((string) $settings['message'], ENT_QUOTES, 'UTF-8');
        $updated_at = htmlspecialchars((string) $settings['updated_at'], ENT_QUOTES, 'UTF-8');
        $status_color = $is_running ? '#10b981' : '#ef4444';
        $status_text = $is_running ? 'Active' : 'Disabled';
        $toggle_checked = $is_running ? ' checked' : '';

        return '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Application Control Panel</title><style>*{box-sizing:border-box}body{margin:0;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;color:#172033;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif}html.dark{background:linear-gradient(135deg,#1f2937 0%,#111827 100%)}.container{width:100%;max-width:600px;margin:0 auto;padding:20px}.card{background:#fff;border-radius:16px;box-shadow:0 20px 60px rgba(15,23,42,.15);padding:40px;margin-top:40px}html.dark .card{background:#1f2937;color:#f3f4f6}.header{margin-bottom:32px}.header h1{margin:0 0 8px;font-size:28px;font-weight:600}.header p{margin:0;color:#6b7280;font-size:16px}html.dark .header p{color:#9ca3af}.status-badge{display:inline-flex;align-items:center;gap:8px;padding:8px 16px;border-radius:8px;font-weight:600;font-size:14px;margin-top:16px;background:' . ($is_running ? '#dcfce7' : '#fee2e2') . ';color:' . $status_color . '}html.dark .status-badge{background:' . ($is_running ? 'rgba(16,185,129,.2)' : 'rgba(239,68,68,.2)') . '}.toggle-section{margin:32px 0;padding:24px;background:#f9fafb;border-radius:12px;border:1px solid #e5e7eb}html.dark .toggle-section{background:#374151;border-color:#4b5563}.toggle-wrapper{display:flex;align-items:center;justify-content:space-between;gap:20px}.toggle-label{font-weight:600;color:#172033;font-size:16px}html.dark .toggle-label{color:#f3f4f6}.toggle-switch{position:relative;display:inline-flex;width:60px;height:32px;background:#d1d5db;border-radius:16px;cursor:pointer;transition:background .3s;border:none;padding:0;margin:0}html.dark .toggle-switch{background:#4b5563}.toggle-switch input{display:none}.toggle-switch input:checked+.toggle-slider{transform:translateX(28px)}.toggle-switch input:checked~.toggle-switch-bg{background:#10b981}html.dark .toggle-switch input:checked~.toggle-switch-bg{background:#10b981}.toggle-switch-bg{position:absolute;top:0;left:0;right:0;bottom:0;background:#d1d5db;border-radius:16px;transition:background .3s}.toggle-slider{position:absolute;top:2px;left:2px;width:28px;height:28px;background:#fff;border-radius:14px;transition:transform .3s;box-shadow:0 2px 4px rgba(0,0,0,.2)}.message-section{margin:32px 0}.message-label{display:block;margin-bottom:12px;font-weight:600;font-size:14px;color:#374151}html.dark .message-label{color:#d1d5db}.message-textarea{width:100%;padding:12px;border:1px solid #d1d5db;border-radius:8px;font-family:inherit;font-size:14px;min-height:100px;resize:vertical;color:#172033;background:#fff}html.dark .message-textarea{background:#374151;border-color:#4b5563;color:#f3f4f6}.message-textarea:focus{outline:none;border-color:#667eea;box-shadow:0 0 0 3px rgba(102,126,234,.1)}.form-actions{display:flex;gap:12px;margin-top:32px}.btn{padding:12px 24px;border:none;border-radius:8px;font-weight:600;cursor:pointer;transition:all .2s;font-size:14px}.btn-primary{background:#2563eb;color:#fff}.btn-primary:hover{background:#1d4ed8}.btn-danger{background:#ef4444;color:#fff}.btn-danger:hover{background:#dc2626}.btn-secondary{background:#e5e7eb;color:#172033;text-decoration:none}.btn-secondary:hover{background:#d1d5db}html.dark .btn-secondary{background:#4b5563;color:#f3f4f6}html.dark .btn-secondary:hover{background:#6b7280}.info-text{margin-top:24px;padding-top:24px;border-top:1px solid #e5e7eb;color:#6b7280;font-size:14px}html.dark .info-text{border-top-color:#4b5563;color:#9ca3af}.info-text strong{color:#172033}html.dark .info-text strong{color:#f3f4f6}</style></head><body><div class="container"><div class="card"><div class="header"><h1>Application Control</h1><p>Manage application availability</p><div class="status-badge"><span style="width:10px;height:10px;border-radius:50%;background:' . $status_color . '"></span>' . $status_text . '</div></div><form method="post"><div class="toggle-section"><div class="toggle-wrapper"><label class="toggle-label">Application Status</label><label class="toggle-switch"><input type="hidden" name="app_status" value="0"><input type="checkbox" name="app_status" value="1"' . $toggle_checked . ' onchange="this.form.submit()"><div class="toggle-switch-bg"></div><div class="toggle-slider"></div></label></div></div><div class="message-section"><label class="message-label" for="message">Message when disabled</label><textarea id="message" name="message" class="message-textarea" placeholder="This message will be shown when the application is disabled...">' . $message . '</textarea></div><div class="form-actions"><button type="submit" class="btn btn-primary">Save Message</button><a href="' . current_url() . '?action=logout" class="btn btn-secondary">Logout</a></div></form><div class="info-text"><strong>Last updated:</strong> ' . ($updated_at !== '' ? $updated_at : 'Never') . '</div></div></div></body></html>';
    }
}
