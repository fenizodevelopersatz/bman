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
}
