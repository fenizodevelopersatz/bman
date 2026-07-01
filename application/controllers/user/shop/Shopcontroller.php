<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Shopcontroller extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->model('shop/Product_model');
    }

    public function index()
    {

        $this->data['categories'] = $this->Product_model->get_category_tree_with_product_count();
        $this->data['brands'] = $this->Product_model->get_all_brands_with_count();
        $this->data['sizes'] = $this->Product_model->get_available_sizes();
        $this->data['products'] = $this->Product_model->get_products_all();
        $price_range = $this->Product_model->get_price_range();
        $this->data['price_min'] = $price_range['min_price'];
        $this->data['price_max'] = $price_range['max_price'] + 10;

        $this->load->view('user/shop/shop-list', $this->data);

    }

    public function filter_by_price()
    {
        $min = $this->input->post('min_price');
        $max = $this->input->post('max_price');
        $brands = $this->input->post('brands') ?? [];
        $sizes = $this->input->post('sizes') ?? [];
        $categories = $this->input->post('categories') ?? [];
        $sort = $this->input->post('sort') ?? '';

        $this->load->model('Product_model');
        $data['products'] = $this->Product_model->filter_products($min, $max, $brands, $sizes, $categories, $sort);

        $this->load->view('user/shop/product_list', $data);
    }


    public function view_product($id)
    {

        $product = $this->Product_model->get_product_by_id($id);
        if (!$product) {
            show_404();
        }
        $related_products = $this->Product_model->get_related_products($product['category_id'], $product['id']);
        $rating = $this->Product_model->get_product_rating_summary($product['id']);
        $product_images = $this->Product_model->get_product_images($id);
        $product_meta = $this->Product_model->get_product_meta($id);

        $this->data['reviews'] = $this->Product_model->get_reviews_by_product($product_id);
        $this->data['related_products'] = $related_products;
        $this->data['avg_rating'] = round($rating['avg_rating']);
        $this->data['total_reviews'] = $rating['total_reviews'];
        $this->data['available_sizes'] = $this->Product_model->get_available_sizes_single($id);
        $this->data['product'] = $product;

        $this->data['images'] = $product_images;
        $this->data['meta'] = $product_meta;

        $user_id = $this->session->userdata('user_userid');
        $this->db->select('product_id');
        $this->db->from('wishlist');
        $this->db->where('user_id', $user_id);
        $this->db->where('product_id', $id);
        $query = $this->db->get();
        $whitelist_count = $query->num_rows();
        $this->data['whitelist'] = $whitelist_count;

        $this->load->view('user/shop/shop-view', $this->data);
    }


    public function add_to_wishlist()
    {
        if (!$this->session->userdata('user_userid')) {
            echo json_encode(['status' => 'error', 'message' => 'Login required']);
            return;
        }

        $product_id = $this->input->post('product_id');
        $user_id = $this->session->userdata('user_userid');

        $exists = $this->db->get_where('wishlist', [
            'user_id' => $user_id,
            'product_id' => $product_id
        ])->row();

        if ($exists) {
            echo json_encode(['status' => 'warning', 'message' => 'Already in wishlist']);
            return;
        }

        $this->db->insert('wishlist', [
            'user_id' => $user_id,
            'product_id' => $product_id
        ]);

        echo json_encode(['status' => 'success', 'message' => 'Added to wishlist']);
    }

    public function add_to_cart()
    {
        if (!$this->session->userdata('user_userid')) {
            echo json_encode(['status' => 'error', 'message' => 'Login required']);
            return;
        }

        $product_id = $this->input->post('product_id');
        $user_id = $this->session->userdata('user_userid');
        $quantity = $this->input->post('quantity');
        $quantity = !empty($quantity) ? (int) $quantity : 1;

        $cartItem = $this->db->get_where('cart', [
            'user_id' => $user_id,
            'product_id' => $product_id
        ])->row();

        if ($cartItem) {
            $this->db->set('quantity', $cartItem->quantity + $quantity, false);
            $this->db->where('id', $cartItem->id);
            $this->db->update('cart');
        } else {
            // Insert new
            $this->db->insert('cart', [
                'user_id' => $user_id,
                'product_id' => $product_id,
                'quantity' => 1
            ]);
        }

        echo json_encode(['status' => 'success', 'message' => 'Added to cart']);
    }

    public function wishlist_count()
    {
        if (!$this->session->userdata('user_userid')) {
            echo json_encode(['count' => 0]);
            return;
        }

        $user_id = $this->session->userdata('user_userid');
        $count = $this->db->where('user_id', $user_id)->count_all_results('wishlist');

        echo json_encode(['count' => $count]);
    }


    public function ajax_get_wishlist()
    {
        $user_id = $this->session->userdata('user_userid');
        if (!$user_id) {
            echo json_encode(['status' => false, 'html' => '']);
            return;
        }

        $this->db->select('wishlist.*, products.name, products.price, products.stock, products.product_image');
        $this->db->from('wishlist');
        $this->db->join('products', 'products.id = wishlist.product_id');
        $this->db->where('wishlist.user_id', $user_id);
        $query = $this->db->get();

        $wishlist = $query->result();

        ob_start();
        foreach ($wishlist as $item): ?>
            <li class="wishlist-sidebar-list">
                <a href="<?= base_url('user/shop/product-view/' . $item->product_id) ?>" class="mn-pro-img">
                    <img src="<?= base_url('assets/images/' . $item->product_image) ?>" alt="<?= $item->name ?>">
                </a>
                <div class="mn-pro-content">
                    <a href="<?= base_url('user/shop/product-view/' . $item->product_id) ?>" class="wishlist-pro-title">
                        <?= $item->name ?>
                    </a>
                    <span class="wishlist-price">
                        <span>₹<?= $item->price ?></span>
                        <span class="stock">- <?= $item->stock ?> in Stock</span>
                    </span>
                    <a href="javascript:void(0)" class="wishlist-remove-item" data-id="<?= $item->id ?>">×</a>
                </div>
            </li>
        <?php endforeach;
        $html = ob_get_clean();

        echo json_encode(['status' => true, 'html' => $html]);
    }

    public function remove_wishlist_item()
    {
        $id = $this->input->post('id');
        $user_id = $this->session->userdata('user_userid');

        $this->db->where('id', $id);
        $this->db->where('user_id', $user_id);
        $this->db->delete('wishlist');

        echo json_encode(['status' => true]);
    }

    public function get_cart_items()
    {
        if (!$this->session->userdata('user_userid')) {
            echo json_encode(['status' => 'error', 'message' => 'Login required']);
            return;
        }

        $user_id = $this->session->userdata('user_userid');

        $this->db->select('cart.id as cart_id, cart.quantity, products.*');
        $this->db->from('cart');
        $this->db->join('products', 'products.id = cart.product_id');
        $this->db->where('cart.user_id', $user_id);
        $query = $this->db->get();

        $items = $query->result_array();

        $subtotal = 0;
        foreach ($items as &$item) {
            $price = ($item['offer_status'] == 1 && $item['offer_price']) ? (float) $item['offer_price'] : (float) $item['price'];
            $item['total'] = $price * $item['quantity'];
            $item['final_price'] = $price;
            $subtotal += $item['total'];
        }

        $vat = $subtotal * 0.2;
        $total = $subtotal + $vat;

        echo json_encode([
            'status' => 'success',
            'items' => $items,
            'summary' => [
                'subtotal' => $subtotal,
                'vat' => $vat,
                'total' => $total
            ]
        ]);
    }


    public function remove_item()
    {
        if (!$this->session->userdata('user_userid')) {
            echo json_encode(['status' => 'error', 'message' => 'Login required']);
            return;
        }

        $cart_id = $this->input->post('cart_id');
        $this->db->delete('cart', ['id' => $cart_id]);

        echo json_encode(['status' => 'success']);
    }

    public function get_cart_count()
    {
        if (!$this->session->userdata('user_userid')) {
            echo json_encode(['status' => 'error', 'count' => 0]);
            return;
        }

        $user_id = $this->session->userdata('user_userid');

        $this->db->select_sum('quantity');
        $this->db->where('user_id', $user_id);
        $count = $this->db->get('cart')->row()->quantity;

        echo json_encode(['status' => 'success', 'count' => $count ? $count : 0]);
    }


    public function get_cart_page()
    {

        $user_id = $this->session->userdata('user_userid');
        $this->db->select('cart.id as cart_id, cart.quantity, products.*');
        $this->db->from('cart');
        $this->db->join('products', 'products.id = cart.product_id');
        $this->db->where('cart.user_id', $user_id);
        $query = $this->db->get();
        $cart_items = $query->result();
        $this->data['cart_items'] = $cart_items;


        $subtotal = 0;
        $price = 0;

        foreach ($cart_items as $item) {
            $price = ($item->offer_status && $item->offer_price > 0) ? $item->offer_price : $item->price;
            $subtotal += $price * $item->quantity;
        }

        $delivery_charge = 0;
        $discount = 0;

        $vat_rate = 0.20;
        $vat = ($subtotal + $delivery_charge) * $vat_rate;

        $total = ($subtotal + $delivery_charge + $vat) - $discount;

        $this->data['subtotal'] = $subtotal;
        $this->data['delivery_charge'] = $delivery_charge;
        $this->data['vat'] = $vat;
        $this->data['discount'] = $discount;
        $this->data['total'] = $total;

        $this->load->view('user/shop/view-cart', $this->data);

    }


    public function update_cart_qty()
    {
        $user_id = $this->session->userdata('user_userid');
        $product_id = $this->input->post('product_id');
        $quantity = $this->input->post('quantity');

        $this->db->where('user_id', $user_id);
        $this->db->where('product_id', $product_id);
        $this->db->update('cart', ['quantity' => $quantity]);

        echo json_encode(['status' => true]);
    }

    public function remove_from_cart()
    {
        $user_id = $this->session->userdata('user_userid');
        $product_id = $this->input->post('product_id');

        $this->db->where('user_id', $user_id);
        $this->db->where('product_id', $product_id);
        $this->db->delete('cart');

        echo json_encode(['status' => true]);
    }



    public function get_checkout_page()
    {

        $user_id = $this->session->userdata('user_userid');
        $this->data['address_info'] = $this->Product_model->get_user_addresses($user_id);
        $this->db->select('cart.id as cart_id, cart.quantity, products.*');
        $this->db->from('cart');
        $this->db->join('products', 'products.id = cart.product_id');
        $this->db->where('cart.user_id', $user_id);
        $query = $this->db->get();
        $cart_items = $query->result();
        $this->data['cart_items'] = $cart_items;


        $subtotal = 0;
        $price = 0;

        foreach ($cart_items as $item) {
            $price = ($item->offer_status && $item->offer_price > 0) ? $item->offer_price : $item->price;
            $subtotal += $price * $item->quantity;
        }

        $delivery_charge = 0;
        $discount = 0;

        $vat_rate = 0.20;
        $vat = ($subtotal + $delivery_charge) * $vat_rate;

        $total = ($subtotal + $delivery_charge + $vat) - $discount;

        $this->data['subtotal'] = $subtotal;
        $this->data['delivery_charge'] = $delivery_charge;
        $this->data['vat'] = $vat;
        $this->data['discount'] = $discount;
        $this->data['total'] = $total;

        $this->data['user_id'] = $user_id;
        $this->data['payment_gateways'] = $this->Product_model->get_active_gateways();

        $this->load->view('user/shop/checkout-view', $this->data);

    }


    public function save_address()
    {

        $user_id = $this->session->userdata('user_userid');

        $data = array(
            'user_id' => $user_id,
            'address_type' => $this->input->post('address_type'),
            'first_name' => $this->input->post('firstname'),
            'last_name' => $this->input->post('lastname'),
            'address' => $this->input->post('address'),
            'city' => $this->input->post('city'),
            'state' => $this->input->post('state'),
            'postal_code' => $this->input->post('postalcode'),
            'country' => $this->input->post('country'),
            'is_default' => $this->input->post('is_default') ? 1 : 0
        );

        if ($data['is_default'] == 1) {
            $this->db->where('user_id', $user_id)->update('user_addresses', ['is_default' => 0]);
        }

        $this->Product_model->save_address($data);

    }



    public function save_order()
    {
        $payment_method = $this->input->post('payment_method');
        $shipping_id = $this->input->post('shipping_id');
        $user_id = $this->session->userdata('user_userid');

        $cart_items = $this->Product_model->get_user_cart($user_id);

        if (empty($cart_items)) {
            show_error('Cart is empty');
        }

        $subtotal = 0;
        foreach ($cart_items as $item) {
            $price = ($item->offer_status && $item->offer_price > 0) ? $item->offer_price : $item->price;
            $subtotal += $price * $item->quantity;
        }

        $vat_rate = 0.20;
        $vat_amount = $subtotal * $vat_rate;
        $total_amount = $subtotal + $vat_amount;

        // 👇 Check balance if paying via account balance
        if ($payment_method == 'cash_on') {
            $user_balance = site_wallet_balance_without_format($user_id);
            if ($user_balance < $total_amount) {
                $this->session->set_flashdata('error', 'Insufficient account balance. Please recharge your wallet.');
                redirect('user/shop/get_checkout_page');
                return;
            }
        }

        $order_code = $this->generate_order_code();

        $order_data = [
            'user_id' => $user_id,
            'total_amount' => $total_amount,
            'shipping_id' => $shipping_id,
            'payment_method' => $payment_method,
            'payment_status' => ($payment_method == 'cash_on') ? 'paid' : 'pending',
            'created_at' => date('Y-m-d H:i:s'),
            'order_code' => $order_code
        ];

        $this->db->insert('orders', $order_data);
        $order_id = $this->db->insert_id();

        foreach ($cart_items as $item) {
            $price = ($item->offer_status && $item->offer_price > 0) ? $item->offer_price : $item->price;
            $this->db->insert('order_items', [
                'order_id' => $order_id,
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'price' => $price
            ]);
        }


        if ($payment_method == 'cash_on') {

            $deduct_data = [
                'user_id' => $user_id,
                'amount' => $total_amount,
                'type' => 'purchase_product',
                'date' => date('Y-m-d H:i:s'),
                'history_date' => date('Y-m-d H:i:s'),
                'status' => '1',
                'coin_type' => '1',
                'invest_id' => "",
                'hash_id' => 'order_payment',
                'description' => 'Order payment from account balance',
                'token_amount' => 0,
                'coin_id' => 1,
                'token_id' => 1
            ];
            $this->db->insert("history", $deduct_data);
        }



        if ($payment_method == 'stripe') {
            redirect(base_url("user/shop/Shopcontroller/stripe_checkout/$order_id"));
        } elseif ($payment_method == 'paypal') {
            redirect(base_url("user/shop/Shopcontroller/paypal_checkout/$order_id"));
        } else {
            redirect(base_url("user/shop/order_success?oid=$order_id"));
        }
    }


    private function generate_order_code($length = 4)
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $random_string = '';
        for ($i = 0; $i < $length; $i++) {
            $random_string .= $characters[rand(0, strlen($characters) - 1)];
        }
        return 'ORD' . $random_string;
    }

    public function stripe_checkout($order_id)
    {
        require_once(APPPATH . 'third_party/stripe/vendor/autoload.php');
        \Stripe\Stripe::setApiKey('');

        $order = $this->db->get_where('orders', ['id' => $order_id])->row();

        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => ['name' => 'Order #' . $order_id],
                        'unit_amount' => $order->total_amount * 100,
                    ],
                    'quantity' => 1,
                ]
            ],
            'mode' => 'payment',
            'success_url' => base_url("user/shop/order_success?oid=$order_id"),
            'cancel_url' => base_url("user/shop/order_failed?oid=$order_id"),
        ]);

        redirect($session->url);
    }

    public function paypal_checkout($order_id)
    {
        $order = $this->db->get_where('orders', ['id' => $order_id])->row();

        $return_url = base_url("user/shop/order_success?oid=$order_id");
        $cancel_url = base_url("user/shop/order_failed?oid=$order_id");

        // Store order ID in session (optional)
        $this->session->set_userdata('paypal_order_id', $order_id);

        $paypal_url = "https://www.sandbox.paypal.com/cgi-bin/webscr";
        // Live: https://www.paypal.com/cgi-bin/webscr

        // Use your sandbox email
        $paypal_email = "nexman@business.example.com";

        echo '
                <form action="' . $paypal_url . '" method="post" id="paypal_form">
                    <input type="hidden" name="business" value="' . $paypal_email . '">
                    <input type="hidden" name="cmd" value="_xclick">
                    <input type="hidden" name="item_name" value="Order #' . $order_id . '">
                    <input type="hidden" name="amount" value="' . $order->total_amount . '">
                    <input type="hidden" name="currency_code" value="USD">
                    <input type="hidden" name="return" value="' . $return_url . '">
                    <input type="hidden" name="cancel_return" value="' . $cancel_url . '">
                </form>
                <script>
                    document.getElementById("paypal_form").submit();
                </script>';
    }

    // public function order_success()
    // {

    //     $order_id = $this->input->get('oid');

    //     if (!$order_id) {
    //         show_404(); 
    //     }

    //     $this->db->where('id', $order_id)->update('orders', ['payment_status' => 'paid']);

    //     $user_id = $this->session->userdata('user_userid');
    //      $this->Product_model->clear_cart($user_id);

    //     $order = $this->db->get_where('orders', ['id' => $order_id])->row();

    //     $this->db->select('oi.*, p.name, p.product_image');
    //     $this->db->from('order_items oi');
    //     $this->db->join('products p', 'oi.product_id = p.id');
    //     $this->db->where('oi.order_id', $order_id);
    //     $order_items = $this->db->get()->result();


    // if ($order && $order->commission_given == 0) {

    //     $this->db->select('oi.*, p.name, p.product_image, p.commission');
    //     $this->db->from('order_items oi');
    //     $this->db->join('products p', 'oi.product_id = p.id');
    //     $this->db->where('oi.order_id', $order_id);
    //     $order_items = $this->db->get()->result();

    //     $total_commission = 0;

    //     foreach ($order_items as $item) {


    //         if ($item->commission > 0) {
    //             $commission_amount = $item->commission * $item->quantity;
    //             $total_commission += $commission_amount;

    //             $deposit_data = array(
    //                 "user_id"       => $user_id,
    //                 "amount"        => $commission_amount,
    //                 "type"          => 'product_commission',
    //                 "date"          => date('Y-m-d H:i:s'),
    //                 "history_date"  => date('Y-m-d H:i:s'),
    //                 "status"        => '1',
    //                 "coin_type"     => '1', 
    //                 "invest_id"     => "",
    //                 "hash_id"       => $order_id,
    //                 "description"   => 'Commission for product: ' . $item->name,
    //                 "token_amount"  => 0,
    //                 "coin_id"       => 1,
    //                 "token_id"      => 1
    //             );

    //             $this->db->insert("history", $deposit_data);
    //         }

    //     }

    //     $this->db->where('id', $order_id)->update('orders', [
    //         'commission_given' => 1,
    //         'commission_amount' => $total_commission
    //     ]);

    // }

    //     $shipping = $this->db->get_where('user_addresses', ['id' => $order->shipping_id])->row();

    //     $this->data['order'] = $order;
    //     $this->data['order_items'] = $order_items;
    //     $this->data['shipping'] = $shipping;

    //     $this->data['payment_get'] = $order->payment_method; 


    //     $this->load->view('user/shop/order-success', $this->data);

    // }



    public function order_success()
    {
        $order_id = $this->input->get('oid');
        if (!$order_id) {
            show_404();
        }

        // 1) Mark paid
        $this->db->where('id', $order_id)->update('orders', ['payment_status' => 'paid']);

        // 2) Clear cart
        $user_id = $this->session->userdata('user_userid');
        $this->Product_model->clear_cart($user_id);

        // 3) Load order + items (you already have this)
        $order = $this->db->get_where('orders', ['id' => $order_id])->row();

        $this->db->select('oi.*, p.name, p.product_image');
        $this->db->from('order_items oi');
        $this->db->join('products p', 'oi.product_id = p.id');
        $this->db->where('oi.order_id', $order_id);
        $order_items = $this->db->get()->result();

        // 4) Existing buyer commission logic (unchanged)
        if ($order && (int) $order->commission_given === 0) {
            $this->db->select('oi.*, p.name, p.product_image, p.commission');
            $this->db->from('order_items oi');
            $this->db->join('products p', 'oi.product_id = p.id');
            $this->db->where('oi.order_id', $order_id);
            $order_items_c = $this->db->get()->result();

            $total_commission = 0;
            foreach ($order_items_c as $item) {
                if ((float) $item->commission > 0) {
                    $commission_amount = (float) $item->commission * (int) $item->quantity;
                    $total_commission += $commission_amount;

                    $deposit_data = [
                        "user_id" => $user_id,
                        "amount" => $commission_amount,
                        "type" => 'product_commission',
                        "date" => date('Y-m-d H:i:s'),
                        "history_date" => date('Y-m-d H:i:s'),
                        "status" => '1',
                        "coin_type" => '1',
                        "invest_id" => "",
                        "hash_id" => $order_id,
                        "description" => 'Commission for product: ' . $item->name,
                        "token_amount" => 0,
                        "coin_id" => 1,
                        "token_id" => 1
                    ];
                    $this->db->insert("history", $deposit_data);
                }
            }

            $this->db->where('id', $order_id)->update('orders', [
                'commission_given' => 1,
                'commission_amount' => $total_commission
            ]);
        }

        $this->load->model('finance/CommissionEngine_model', 'CommissionEngine');
        $this->CommissionEngine->post_product_pv((int) $user_id, (int) $order_id);

        // 6) Render success page
        $shipping = $this->db->get_where('user_addresses', ['id' => $order->shipping_id])->row();

        $this->data['order'] = $order;
        $this->data['order_items'] = $order_items;
        $this->data['shipping'] = $shipping;
        $this->data['payment_get'] = $order->payment_method;

        $this->load->view('user/shop/order-success', $this->data);
    }


    public function invoice($order_id)
    {
        $user_id = $this->session->userdata('user_userid');

        $order = $this->db->get_where('orders', ['id' => $order_id, 'user_id' => $user_id])->row();
        if (!$order)
            show_404();

        $this->db->select('oi.*, p.name, p.product_image');
        $this->db->from('order_items oi');
        $this->db->join('products p', 'oi.product_id = p.id');
        $this->db->where('oi.order_id', $order_id);
        $order_items = $this->db->get()->result();

        $shipping = $this->db->get_where('user_addresses', ['id' => $order->shipping_id])->row();

        $this->data['order'] = $order;
        $this->data['order_items'] = $order_items;
        $this->data['shipping'] = $shipping;


        // ✅ Get site settings (logo, company name, email, phone, address)
        $settings_q = $this->db->get('site_settings')->result();
        $site_settings = [];
        foreach ($settings_q as $row) {
            $site_settings[$row->settings_name] = $row->settings_value;
        }

        $this->data['order'] = $order;
        $this->data['order_items'] = $order_items;
        $this->data['shipping'] = $shipping;
        $this->data['settings'] = $site_settings;


        $this->load->view('user/shop/invoice', $this->data);
    }



    public function order_failed()
    {

        $order_id = $this->input->get('oid');
        if (!$order_id) {
            show_404();
        }
        $this->db->where('id', $order_id)->update('orders', ['payment_status' => 'failed']);
        redirect(base_url('user/shop/get_checkout_page'));

    }



}


?>