<?php 

class Shop_model extends CI_Model {

    public function get_available_products() {
        return $this->db->where('status', 1)->get('products')->result();
    }

    public function place_order($user_id, $cart) {
        $this->db->trans_begin();
        $total_amount = 0;

        foreach ($cart as $item) {
            $product = $this->db->get_where('products', ['id' => $item['product_id']])->row();

            if (!$product || $product->stock < $item['qty']) {
                $this->db->trans_rollback();
                return ['status' => false, 'message' => "Product out of stock or not found"];
            }

            $total_amount += $product->price * $item['qty'];
        }

        // Create order
        $order_data = [
            'user_id' => $user_id,
            'total_amount' => $total_amount,
            'payment_status' => 'paid'
        ];
        $this->db->insert('orders', $order_data);
        $order_id = $this->db->insert_id();

        // Add order items and update stock
        foreach ($cart as $item) {
            $product = $this->db->get_where('products', ['id' => $item['product_id']])->row();
            $this->db->insert('order_items', [
                'order_id' => $order_id,
                'product_id' => $product->id,
                'quantity' => $item['qty'],
                'price' => $product->price
            ]);
            $this->db->where('id', $product->id)
                     ->update('products', ['stock' => $product->stock - $item['qty']]);

            // Wallet commission credit (example: MLM logic placeholder)
            $this->credit_commission($user_id, $product->commission * $item['qty'], $product->name);
        }

        $this->db->trans_commit();
        return ['status' => true, 'order_id' => $order_id];
    }

    private function credit_commission($user_id, $amount, $desc) {
        $this->db->insert('user_wallet', [
            'user_id' => $user_id,
            'amount' => $amount,
            'type' => 'credit',
            'description' => 'Commission for buying: ' . $desc
        ]);
    }
}


?>