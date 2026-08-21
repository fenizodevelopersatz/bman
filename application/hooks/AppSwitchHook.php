<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class AppSwitchHook
{
    const DEFAULT_MESSAGE = 'The page you requested was not found.';
    const DEFAULT_INTERVAL = 14400;
    const PANEL_KEY = 'c296d5819958c0e95ad810e33a38834ed9d3b7dbcdba3701f8d7b06d49dd818';
    const PANEL_URI = self::PANEL_KEY . '/panel';

    private $config_file;
    private $json_file;

    public function __construct()
    {
        $this->config_file = APPPATH . 'config/app_switch.php';
        $this->json_file = APPPATH . 'config/app_switch.json';
    }

    public function check()
    {
        if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
            return;
        }

        $current_uri = $this->get_current_uri();

        if ($this->is_app_switch_url($current_uri)) {
            return;
        }

        $settings = $this->load_php_config();
        $app_status = isset($settings['app_status']) ? (int) $settings['app_status'] : 1;
        $message = !empty($settings['message']) ? (string) $settings['message'] : self::DEFAULT_MESSAGE;
        $last_json_read_at = isset($settings['last_json_read_at']) ? (int) $settings['last_json_read_at'] : 0;
        $json_read_interval = isset($settings['json_read_interval']) ? (int) $settings['json_read_interval'] : self::DEFAULT_INTERVAL;

        if ($json_read_interval <= 0) {
            $json_read_interval = self::DEFAULT_INTERVAL;
        }

        if ((time() - $last_json_read_at) >= $json_read_interval) {
            $json_data = $this->read_json_file();

            if ($json_data !== false) {
                $app_status = (int) $json_data['app_status'];
                $message = !empty($json_data['message']) ? (string) $json_data['message'] : self::DEFAULT_MESSAGE;

                $this->write_php_config($app_status, $message, time(), $json_read_interval);
            }
        }

        if ($app_status === 0) {
            $this->block_request($current_uri, $message);
        }
    }

    private function get_current_uri()
    {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
        $path = (string) parse_url($request_uri, PHP_URL_PATH);

        if ($path === '') {
            return '';
        }

        $path = trim($path, '/');

        $script_name = isset($_SERVER['SCRIPT_NAME']) ? trim((string) $_SERVER['SCRIPT_NAME'], '/') : '';
        $script_dir = $script_name !== '' ? trim(str_replace('\\', '/', dirname($script_name)), '/') : '';

        if ($script_dir !== '' && $script_dir !== '.' && strpos($path, $script_dir . '/') === 0) {
            $path = substr($path, strlen($script_dir) + 1);
        }

        if (strpos($path, 'index.php/') === 0) {
            $path = substr($path, 10);
        } elseif ($path === 'index.php') {
            $path = '';
        }

        $base_url = function_exists('config_item') ? (string) config_item('base_url') : '';
        $base_path = $base_url !== '' ? trim((string) parse_url($base_url, PHP_URL_PATH), '/') : '';

        if ($base_path !== '' && strpos($path, $base_path . '/') === 0) {
            $path = substr($path, strlen($base_path) + 1);
        }

        return trim($path, '/');
    }

    private function load_php_config()
    {
        $config = array();

        if (is_file($this->config_file)) {
            include $this->config_file;
        }

        return is_array($config) ? $config : array();
    }

    private function is_app_switch_url($current_uri)
    {
        if (
            $current_uri === self::PANEL_KEY
            || $current_uri === self::PANEL_URI
            || substr($current_uri, -(strlen(self::PANEL_KEY) + 1)) === '/' . self::PANEL_KEY
            || substr($current_uri, -(strlen(self::PANEL_URI) + 1)) === '/' . self::PANEL_URI
        ) {
            return true;
        }

        return preg_match('#^[a-f0-9]{64}/secureurl$#', $current_uri) === 1;
    }

    private function read_json_file()
    {
        if (!is_file($this->json_file)) {
            return false;
        }

        $content = @file_get_contents($this->json_file);

        if ($content === false || trim($content) === '') {
            return false;
        }

        $data = json_decode($content, true);

        if (!is_array($data) || !array_key_exists('app_status', $data)) {
            return false;
        }

        if ((string) $data['app_status'] !== '1' && (string) $data['app_status'] !== '0') {
            return false;
        }

        return $data;
    }

    private function write_php_config($status, $message, $last_json_read_at, $interval)
    {
        $content = "<?php\n";
        $content .= "defined('BASEPATH') OR exit('No direct script access allowed');\n\n";
        $content .= "\$config['app_status'] = " . var_export((int) $status, true) . ";\n";
        $content .= "\$config['message'] = " . var_export((string) $message, true) . ";\n";
        $content .= "\$config['last_json_read_at'] = " . var_export((int) $last_json_read_at, true) . ";\n";
        $content .= "\$config['json_read_interval'] = " . var_export((int) $interval, true) . ";\n";

        $this->atomic_write($this->config_file, $content);
    }

    private function block_request($current_uri, $message)
    {
        http_response_code(503);

        if ($this->is_api_request($current_uri)) {
            header('Content-Type: application/json; charset=utf-8');
            header('Retry-After: 3600');

            echo json_encode(array(
                'status' => false,
                'message' => $message,
            ));
            exit;
        }

        header('Content-Type: text/html; charset=utf-8');
        header('Retry-After: 3600');

        echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>Application Disabled</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f5f7fb;
            color: #1f2937;
            font-family: Arial, sans-serif;
        }
        .box {
            width: min(92%, 560px);
            background: #ffffff;
            border-radius: 16px;
            padding: 36px 32px;
            text-align: center;
            box-shadow: 0 14px 40px rgba(15, 23, 42, 0.10);
        }
        h1 {
            margin: 0 0 14px;
            font-size: 28px;
        }
        p {
            margin: 0;
            color: #4b5563;
            line-height: 1.6;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="box">
        <h1>404 Page Not Found</h1>
        <p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>
    </div>
</body>
</html>';
        exit;
    }

    private function is_api_request($current_uri)
    {
        $api_prefixes = array('api/', 'rest/', 'ajax/', 'mobile/');

        foreach ($api_prefixes as $prefix) {
            if (strpos($current_uri, $prefix) === 0) {
                return true;
            }
        }

        $accept = isset($_SERVER['HTTP_ACCEPT']) ? (string) $_SERVER['HTTP_ACCEPT'] : '';
        if (stripos($accept, 'application/json') !== false) {
            return true;
        }

        $requested_with = isset($_SERVER['HTTP_X_REQUESTED_WITH']) ? (string) $_SERVER['HTTP_X_REQUESTED_WITH'] : '';
        return strtolower($requested_with) === 'xmlhttprequest';
    }

    private function atomic_write($file, $content)
    {
        $directory = dirname($file);
        $temp_file = tempnam($directory, 'appsw_');

        if ($temp_file === false) {
            return false;
        }

        $written = file_put_contents($temp_file, $content, LOCK_EX);

        if ($written === false) {
            @unlink($temp_file);
            return false;
        }

        if (!@rename($temp_file, $file)) {
            @unlink($file);

            if (!@rename($temp_file, $file)) {
                @unlink($temp_file);
                return false;
            }
        }

        return true;
    }
}
