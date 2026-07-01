<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Global 404 handler.
 *
 * Wired via  $route['404_override'] = 'notfound';  in application/config/routes.php
 * so that ANY unknown URL renders the custom, responsive 404 page below.
 */
class Notfound extends CI_Controller {

    public function index()
    {
        // Always return a proper 404 status so browsers / bots / SEO see it correctly.
        $this->output->set_status_header(404);
        $this->load->view('error404');
    }

    /**
     * Route every requested method/segment to index() so the 404 page shows
     * no matter what the (non-existent) URL was.
     */
    public function _remap($method, $params = array())
    {
        $this->index();
    }
}
