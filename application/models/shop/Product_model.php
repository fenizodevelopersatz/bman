<?php 
class Product_model extends CI_Model {

    public function get_all_products() {
        return $this->db->order_by('id', 'DESC')->get('products')->result();
    }
    
    public function get_all_category() {
        return $this->db->order_by('id', 'DESC')->get('product_categories')->result();
    }

    public function get_all_brand() {
        return $this->db->order_by('id', 'DESC')->get('brands')->result();
    }

    public function get_brand_list($limit, $offset) {
        return $this->db->limit($limit, $offset)
            ->order_by('id', 'DESC')
            ->get('brands')
            ->result_array();
    }

     public function get_brand_count() {
        return $this->db->count_all('brands');
    }
    public function get_category_count() {
        return $this->db->count_all('product_categories');
    }
    public function get_category_list($limit, $offset) {
        return $this->db->limit($limit, $offset)
            ->order_by('id', 'DESC')
            ->get('product_categories')
            ->result_array();
    }
    public function insert_product($data) {
        $this->db->insert('products', $data);
        return $this->db->insert_id();
    }
    public function get_count() {
        return $this->db->count_all('products');
    }
    public function get_list($limit, $offset) {
        return $this->db->limit($limit, $offset)
            ->order_by('id', 'DESC')
            ->get('products')
            ->result_array();
    }
    public function insert_product_meta($product_id, $key, $value)
    {
        $this->db->insert('product_meta', [
            'product_id' => $product_id,
            'meta_key' => $key,
            'meta_value' => $value
        ]);
    }
    public function insert_product_image($product_id, $filename)
    {
        $this->db->insert('product_images', [
            'product_id' => $product_id,
            'image' => $filename
        ]);
    }
    public function update_product($id, $data) {
        $this->db->where('id', $id);
        return $this->db->update('products', $data);
    }
    public function delete_product_meta($product_id) {
    $this->db->where('product_id', $product_id)->delete('product_meta');
    }
    public function delete_product_images($product_id) {
    $this->db->where('product_id', $product_id)->delete('product_images');
    }


    public function get_category_tree_with_product_count()
    {
        // Get all categories
        $categories = $this->db->get('product_categories')->result_array();

        // Get product counts
        $counts = $this->db->query("
            SELECT category_id, COUNT(*) as total 
            FROM products 
            GROUP BY category_id
        ")->result_array();

        $productCountMap = [];
        foreach ($counts as $count) {
            $productCountMap[$count['category_id']] = $count['total'];
        }

        // Build tree
        $tree = [];
        foreach ($categories as &$category) {
            $category['product_count'] = isset($productCountMap[$category['id']]) ? $productCountMap[$category['id']] : 0;
            $category['children'] = [];
            $map[$category['id']] = &$category;
        }

        foreach ($categories as &$category) {
            if ($category['parent_id'] == NULL) {
                $tree[] = &$category;
            } else {
                $map[$category['parent_id']]['children'][] = &$category;
            }
        }

        return $tree;
    }


    public function get_all_brands_with_count()
    {
        $this->db->select('b.id, b.name, COUNT(p.id) as product_count');
        $this->db->from('brands b');
        $this->db->join('products p', 'p.brand_id = b.id', 'left');
        $this->db->group_by('b.id');
        $this->db->order_by('b.name', 'ASC');
        return $this->db->get()->result();
    }

    public function get_available_sizes()
    {
        $this->db->select('product_size');
        $this->db->from('products');
        $query = $this->db->get()->result();

        $sizes = [];
        foreach ($query as $row) {
            $productSizes = explode(',', $row->product_size);
            foreach ($productSizes as $size) {
                $trimmed = trim($size);
                if (!in_array($trimmed, $sizes) && $trimmed != '') {
                    $sizes[] = $trimmed;
                }
            }
        }
        return $sizes;
    }


        public function get_products_all()
    {
        $this->db->select('products.*, product_categories.name as category_name');
        $this->db->from('products');
        $this->db->join('product_categories', 'product_categories.id = products.category_id', 'left');
        $this->db->where('products.status', 1);
        $this->db->order_by('products.price', 'ASC');

        return $this->db->get()->result_array();
    }

        public function filter_products($min, $max, $brands = [], $sizes = [], $categories = [], $sort = '')
    {
        $this->db->select('products.*, product_categories.name as category_name');
        $this->db->from('products');
        $this->db->join('product_categories', 'product_categories.id = products.category_id', 'left');
        $this->db->where('products.status', 1);
        $this->db->where('products.price >=', $min);
        $this->db->where('products.price <=', $max);

        if (!empty($brands)) {
            $this->db->where_in('products.brand_id', $brands);
        }

        if (!empty($sizes)) {
            $this->db->group_start();
            foreach ($sizes as $size) {
                $this->db->or_like('products.product_size', $size);
            }
            $this->db->group_end();
        }

        if (!empty($categories)) {
            $this->db->where_in('products.category_id', $categories);
        }

        switch ($sort) {
            case 'name_asc':
                $this->db->order_by('products.name', 'asc');
                break;
            case 'name_desc':
                $this->db->order_by('products.name', 'desc');
                break;
            case 'price_asc':
                $this->db->order_by('products.price', 'asc');
                break;
            case 'price_desc':
                $this->db->order_by('products.price', 'desc');
                break;
            default:
                $this->db->order_by('products.id', 'desc'); 
        }

        return $this->db->get()->result_array();
    }


        public function get_price_range()
        {
            $this->db->select('MIN(price) as min_price, MAX(price) as max_price');
            $this->db->where('status', 1);
            $query = $this->db->get('products');
            return $query->row_array();
        }


        public function get_product_by_id($id)
        {
            return $this->db->select('products.*, product_categories.name as category_name')
            ->from('products')
            ->join('product_categories', 'product_categories.id = products.category_id', 'left')
            ->where('products.id', $id)
            ->get()
            ->row_array();
        }

        public function get_product_images($product_id)
        {
            return $this->db
            ->select('image')
            ->from('product_images')
            ->where('product_id', $product_id)
            ->get()
            ->result_array();
        }

        public function get_product_meta($product_id)
        {
            $result = $this->db
            ->select('meta_key, meta_value')
            ->from('product_meta')
            ->where('product_id', $product_id)
            ->get()
            ->result();

            $meta = [];
            foreach ($result as $row) {
                $meta[$row->meta_key] = $row->meta_value;
            }
            return $meta;
        }

        public function get_available_sizes_single($product_id) {
            $this->db->select('meta_value');
            $this->db->from('product_meta');
            $this->db->where('product_id', $product_id);
            $this->db->where('meta_key', 'size');
            return array_column($this->db->get()->result_array(), 'meta_value');
        }

        
        public function get_product_rating_summary($product_id) {
            $this->db->select('AVG(rating) as avg_rating, COUNT(id) as total_reviews');
            $this->db->from('product_reviews');
            $this->db->where('product_id', $product_id);
            return $this->db->get()->row_array();
        }

        public function get_reviews_by_product($product_id) {
            return $this->db
            ->select('product_reviews.*, users.name as user_name')
            ->from('product_reviews')
            ->join('users', 'users.id = product_reviews.user_id', 'left')
            ->where('product_reviews.product_id', $product_id)
            ->order_by('product_reviews.created_at', 'DESC')
            ->get()
            ->result_array();
        }

        public function clear_cart($user_id)
        {
            return $this->db->where('user_id', $user_id)->delete('cart');
        }

        public function get_related_products($category_id, $exclude_id = null)
        {
            $this->db->select('p.*, c.name as category_name');
            $this->db->from('products p');
            $this->db->join('product_categories c', 'c.id = p.category_id', 'left');
            $this->db->where('p.category_id', $category_id);
            $this->db->where('p.status', 1);
            if ($exclude_id) {
                $this->db->where('p.id !=', $exclude_id);
            }
            $this->db->limit(10);
            return $this->db->get()->result_array();
        }

        public function get_user_wishlist_ids($user_id)
        {
            $this->db->select('product_id');
            $this->db->from('wishlist');
            $this->db->where('user_id', $user_id);
            $query = $this->db->get();

            $result = $query->result_array();
            return array_column($result, 'product_id');
        }

            public function get_user_cart($user_id)
        {
            $this->db->select('cart.*, products.name, products.price, products.offer_price, products.offer_status, products.product_image');
            $this->db->from('cart');
            $this->db->join('products', 'products.id = cart.product_id', 'left');
            $this->db->where('cart.user_id', $user_id);
            return $this->db->get()->result();
        }


        public function get_user_cart_ids($user_id)
        {
            $this->db->select('product_id');
            $this->db->from('cart');
            $this->db->where('user_id', $user_id);
            $query = $this->db->get();

            $result = $query->result_array();
            return array_column($result, 'product_id');
        }

        public function save_address($data) {
            return $this->db->insert('user_addresses', $data);
        }

        public function get_user_addresses($user_id) {
            return $this->db
                ->where('user_id', $user_id)
                ->order_by('is_default', 'DESC')
                ->get('user_addresses')
                ->row();
        }

        public function get_address_by_id($id, $user_id) {
            return $this->db
                ->where(['id' => $id, 'user_id' => $user_id])
                ->get('user_addresses')
                ->row();
        }

        public function get_active_gateways()
        {
            return $this->db->where('status', 1)->get('payment_settings')->result();
        }


    public function count_all_orders($status = '', $category = '') {
            $this->db->from('orders');
            if ($status && $status != 'Show All') {
                $this->db->where('payment_status', $status);
            }

            if ($category && $category != 'Show All') {
                $this->db->join('order_items', 'order_items.order_id = orders.id');
                $this->db->join('products', 'products.id = order_items.product_id');
                $this->db->where('products.category_id', $category);
            }

            return $this->db->count_all_results();
        }

        public function get_orders($limit, $start, $status = '', $category = '') {
            $this->db->select('orders.*');
            $this->db->from('orders');

            if ($status && $status != 'Show All') {
                $this->db->where('orders.payment_status', $status);
            }

            if ($category && $category != 'Show All') {
                $this->db->join('order_items', 'order_items.order_id = orders.id');
                $this->db->join('products', 'products.id = order_items.product_id');
                $this->db->where('products.category_id', $category);
            }

            $this->db->order_by('orders.id', 'desc');
            $this->db->limit($limit, $start);

            return $this->db->get()->result_array();
        }

}
?>