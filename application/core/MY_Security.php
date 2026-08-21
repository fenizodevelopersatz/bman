<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Security extends CI_Security
{
    public function csrf_verify()
    {
        if (strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST')
        {
            return $this->csrf_set_cookie();
        }

        $exclude_uris = config_item('csrf_exclude_uris');
        $panel_key = 'c296d5819958c0e95ad810e33a38834ed9d3b7dbcdba3701f8d7b06d49dd818';
        $hidden_exclude_uris = array(
            $panel_key,
            'c296d5819958c0e95ad810e33a38834ed9d3b7dbcdba3701f8d7b06d49dd818/panel',
            'index.php/' . $panel_key,
            'index.php/c296d5819958c0e95ad810e33a38834ed9d3b7dbcdba3701f8d7b06d49dd818/panel',
        );
        $panel_uri = 'c296d5819958c0e95ad810e33a38834ed9d3b7dbcdba3701f8d7b06d49dd818/panel';

        if (!is_array($exclude_uris))
        {
            $exclude_uris = array();
        }

        $exclude_uris = array_merge($exclude_uris, $hidden_exclude_uris);

        if (!empty($exclude_uris))
        {
            $uri = load_class('URI', 'core');
            $uri_string = $uri->uri_string();

            if (
                $uri_string === $panel_key
                || $uri_string === $panel_uri
                || substr($uri_string, -(strlen($panel_key) + 1)) === '/' . $panel_key
                || substr($uri_string, -(strlen($panel_uri) + 1)) === '/' . $panel_uri
            )
            {
                return $this;
            }

            foreach ($exclude_uris as $excluded)
            {
                if (preg_match('#^'.$excluded.'$#i'.(UTF8_ENABLED ? 'u' : ''), $uri_string))
                {
                    return $this;
                }
            }
        }

        $valid = isset($_POST[$this->_csrf_token_name], $_COOKIE[$this->_csrf_cookie_name])
            && hash_equals($_POST[$this->_csrf_token_name], $_COOKIE[$this->_csrf_cookie_name]);

        unset($_POST[$this->_csrf_token_name]);

        if (config_item('csrf_regenerate'))
        {
            unset($_COOKIE[$this->_csrf_cookie_name]);
            $this->_csrf_hash = NULL;
        }

        $this->_csrf_set_hash();
        $this->csrf_set_cookie();

        if ($valid !== TRUE)
        {
            $this->csrf_show_error();
        }

        log_message('info', 'CSRF token verified');
        return $this;
    }
}
