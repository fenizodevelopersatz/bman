<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Blog extends CI_Controller {

	public function __construct() {
		parent::__construct();
		$language = $this->session->userdata('site_lang') ?? 'english';
		$this->config->set_item('language', $language);
		$this->lang->load('common', $language);
	}

    public function matrix_mlm(){
        $this->load->view('user/blog/matrix');
    }

    public function austrilia_mlm(){
        $this->load->view('user/blog/austrilia');
    }

     public function gift_mlm(){
        $this->load->view('user/blog/gift-plan');
    }

    public function top_six_mlm(){
        $this->load->view('user/blog/top-six-plan');
    }
	
}