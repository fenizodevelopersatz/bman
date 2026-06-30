<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class  Api extends CI_Controller {

    public function __construct() {
    parent::__construct();
    $this->load->library('session');
    $this->load->helper('url');
    $this->load->model('member/Mlm_model');

    $this->token = "";
    $this->baseurl = "https://node.adrox.ai";

    }

    public function switch_language($lang) {
        $this->session->set_userdata('site_lang', $lang);
        redirect($_SERVER['HTTP_REFERER']); 
    }

    /*
    |--------------------------------------------------------------------------
    | Image Generate
    |--------------------------------------------------------------------------
    */
    public function image_generate(){

        $json_data = file_get_contents('php://input');
        $request_data = json_decode($json_data, true);
        $image_code = $request_data['image_code'];
        $create_image  = $this->Mlm_model->online_image_generate($image_code);
        echo json_encode($create_image);
           
    }
    /*
    |--------------------------------------------------------------------------
    | Image Save
    |--------------------------------------------------------------------------
    */
    public function image_save(){

        $create_image  = $this->Mlm_model->image_save();
        echo json_encode($create_image);
        
    }
   /*
    |--------------------------------------------------------------------------
    | Get Coin List
    |--------------------------------------------------------------------------
    */
   public function get_coins_list() {

        $json_data = file_get_contents('php://input');
        $request_data = json_decode($json_data, true);

        $url = $this->baseurl."/api/user/home";

        $result = curl_get_with_token($url, $this->token);

        echo json_encode($result);
        
    }

    
}