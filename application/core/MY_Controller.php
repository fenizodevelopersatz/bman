<?php
defined('BASEPATH') OR exit('No direct script access allowed');


class MY_Controller extends CI_Controller
{
    protected function requireUploadsEnabled()
    {
        if (ENABLE_SITE_UPLOAD_FUNCTION !== true)
            show_error('Uploads disabled', 403);
    }

    public function _dbg($tag, $payload = null)
    {
        if (is_array($payload) || is_object($payload)) {
            $payload = json_encode($payload);
        }
        log_message('debug', "[CommissionEngine][$tag] {$payload}");
    }
}
