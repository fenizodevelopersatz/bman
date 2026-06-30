<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Welcome extends CI_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see https://codeigniter.com/userguide3/general/urls.html
	 */
	public function index()
	{

		   $this->data['sliders'] = $this->db->where('status', 1)
                        ->order_by('id', 'DESC')
                        ->get('sliders_img')
                        ->result();

			$categories = $this->db->where('status', 1)->get('product_categories')->result();
			$limit = 2;
			$filtered_categories = [];

			foreach ($categories as $cat) {
				// Get max offer first
				$max_offer = $this->db
					->select_max('offer_percentage')
					->where('category_id', $cat->id)
					->where('status', 1)
					->where('offer_status', 1) // only if offer is active
					->get('products')
					->row()
					->offer_percentage ?? 0;

				$max_offer = (int)$max_offer;

				// Skip category if no active offer
				if ($max_offer <= 0) {
					continue;
				}

				// Get sample products for slider (only if offer exists)
				$products = $this->db
					->select('product_image, offer_percentage')
					->where('category_id', $cat->id)
					->where('status', 1)
					->limit($limit)
					->get('products')
					->result();

				if (!empty($products)) {
					$cat->products = $products;
					$cat->max_offer = $max_offer;
					$filtered_categories[] = $cat;
				}
			}

			$this->data['categories'] = $filtered_categories;

			$this->db->select('
			p.id, 
			p.name, 
			p.product_image, 
			p.offer_price, 
			p.offer_status,
			p.price, 
			p.product_size, 
			c.name as category_name, 
			b.name as brand_name
			');
			$this->db->from('products p');
			$this->db->join('product_categories c', 'c.id = p.category_id', 'left');
			$this->db->join('brands b', 'b.id = p.brand_id', 'left');
			$this->db->where('p.status', 1);
			$this->db->order_by('p.created_at', 'DESC');
			$this->db->limit(6);

			$new_arrivals = $this->db->get()->result();
			$this->data['new_arrivals'] = $new_arrivals;


			$this->db->select('
			p.id, 
			p.name, 
			p.product_image, 
			p.offer_price, 
			p.offer_status,
			p.price, 
			p.product_size, 
			c.name as category_name, 
			b.name as brand_name
			');
			$this->db->from('products p');
			$this->db->join('product_categories c', 'c.id = p.category_id', 'left');
			$this->db->join('brands b', 'b.id = p.brand_id', 'left');
			$this->db->where('p.status', 1);
			$this->db->order_by('p.created_at', 'ASC');
			$this->db->limit(6);

			$top_arrivals = $this->db->get()->result();
			$this->data['top_arrivals'] = $top_arrivals;


			$this->db->select('blogs.*, blog_categories.name as category_name');
			$this->db->from('blogs');
			$this->db->join('blog_categories', 'blog_categories.id = blogs.category', 'left');
			$this->db->where('blogs.status', 1); // published
			$this->db->order_by('blogs.created_at', 'DESC');
			$this->data['blogs'] = $this->db->get()->result();
 			
			$user_id = $this->session->userdata('userid');
			$this->load->model('shop/Product_model');
			$wishlist_ids = $this->Product_model->get_user_wishlist_ids($user_id);
			$cart_ids = $this->Product_model->get_user_cart_ids($user_id);


			$this->data['wishlist_ids'] = $wishlist_ids;
			$this->data['cart_ids'] = $cart_ids;


		   $this->load->view('user/shop/landing',$this->data);
	}


	public function commingsoon($id){

		$this->data['id'] = $id;
		$this->load->view('admin/comming-soon',$this->data);
	}
}
