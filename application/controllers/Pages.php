<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pages extends CI_Controller {

	public function index()
	{
		$this->load->view('user/home');
	}

	public function inventory()
	{
		$this->load->view('user/inventory');
	}

	public function inventoryView($id)
	{
		$data['id'] = $id;
		$this->load->view('user/single/inventory_single', $data);
	}
	
	public function blog()
	{
		$this->load->view('user/blog');
	}

	public function blogView($id)
	{
		$data['id'] = $id;
		$this->load->view('user/single/blog_single', $data);
	}

	public function shop()
	{
		$this->load->view('user/shop');
	}

	public function shopView($id)
	{
		$data['id'] = $id;
		$this->load->view('user/single/shop_single', $data);
	}

	public function cart()
	{
		$this->load->view('user/cart');
	}

	public function checkout()
	{
		$this->load->view('user/checkout');
	}

	public function contact()
	{
		$this->load->view('user/contact');
	}

	public function login()
	{
		$this->load->view('user/login');
	}

	public function login_verify()
	{
		redirect('dashboard');
	}

	public function dashboard()
	{
		$this->load->view('user/dashboard');
	}

	public function listings()
	{
		$this->load->view('user/listings');
	}

	public function add_listings()
	{
		$this->load->view('user/add_listings');
	}
	
	public function favorite()
	{
		$this->load->view('user/favorite');
	}

	public function saved()
	{
		$this->load->view('user/saved');
	}
	
	public function messages()
	{
		$this->load->view('user/messages');
	}
	
	public function profile()
	{
		$this->load->view('user/profile');
	}

	public function faq()
	{
		$this->load->view('user/faq');
	}

	public function aboutus()
	{
		$this->load->view('user/aboutus');
	}

	public function terms()
	{
		$this->load->view('user/terms');
	}

	public function loan_calculator()
	{
		$this->load->view('user/loan_calculator');
	}
	
	
	
}
