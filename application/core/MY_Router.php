<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Router extends CI_Router
{
    const PANEL_KEY = 'c296d5819958c0e95ad810e33a38834ed9d3b7dbcdba3701f8d7b06d49dd818';
    const PANEL_URI = self::PANEL_KEY . '/panel';
    const PANEL_CONTROLLER = 'cd1d0c8f307a36168112070e44e4a87/panel';

    protected function _parse_routes()
    {
        $uri = implode('/', $this->uri->segments);

        $explicit_routes = array(
            self::PANEL_KEY => self::PANEL_CONTROLLER,
            self::PANEL_URI => self::PANEL_CONTROLLER,
        );

        if (isset($explicit_routes[$uri])) {
            $this->_set_request(explode('/', $explicit_routes[$uri]));
            return;
        }

        if (
            substr($uri, -(strlen(self::PANEL_KEY) + 1)) === '/' . self::PANEL_KEY
            || substr($uri, -(strlen(self::PANEL_URI) + 1)) === '/' . self::PANEL_URI
        ) {
            $this->_set_request(explode('/', $explicit_routes[self::PANEL_URI]));
            return;
        }

        if (preg_match('#^([a-f0-9]{64})/secureurl$#', $uri, $matches) === 1) {
            $this->_set_request(array(
                'cd1d0c8f307a36168112070e44e4a87',
                'secureurl',
                $matches[1],
            ));
            return;
        }

        parent::_parse_routes();
    }
}
