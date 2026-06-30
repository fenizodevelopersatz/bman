<?php 
class Coupon_model extends CI_Model {

    public function get_all() {
        return $this->db->order_by('id', 'DESC')->get('coupons')->result();
    }

    public function get($id) {
        return $this->db->get_where('coupons', ['id' => $id])->row();
    }

    public function create($data) {
        $insert = [
            'code' => strtoupper(trim($data['code'])),
            'discount_type' => $data['discount_type'],
            'discount_value' => $data['discount_value'],
            'usage_limit' => $data['usage_limit'] ?: NULL,
            'usage_per_user' => $data['usage_per_user'] ?: NULL,
            'min_order_amount' => $data['min_order_amount'] ?: NULL,
            'max_discount' => $data['max_discount'] ?: NULL,
            'valid_from' => $data['valid_from'],
            'valid_to' => $data['valid_to'],
            'status' => $data['status']
        ];
        return $this->db->insert('coupons', $insert);
    }

    public function update($id, $data) {
        $update = [
            'discount_type' => $data['discount_type'],
            'discount_value' => $data['discount_value'],
            'usage_limit' => $data['usage_limit'] ?: NULL,
            'usage_per_user' => $data['usage_per_user'] ?: NULL,
            'min_order_amount' => $data['min_order_amount'] ?: NULL,
            'max_discount' => $data['max_discount'] ?: NULL,
            'valid_from' => $data['valid_from'],
            'valid_to' => $data['valid_to'],
            'status' => $data['status']
        ];
        return $this->db->where('id', $id)->update('coupons', $update);
    }


    public function apply_coupon($code, $user_id, $order_amount) {
        $coupon = $this->db->get_where('coupons', ['code' => $code, 'status' => 'active'])->row();
        if (!$coupon) return ['status' => false, 'msg' => 'Invalid coupon'];

        $now = date('Y-m-d');
        if ($now < $coupon->valid_from || $now > $coupon->valid_to)
            return ['status' => false, 'msg' => 'Coupon expired or not yet valid'];

        if ($coupon->min_order_amount && $order_amount < $coupon->min_order_amount)
            return ['status' => false, 'msg' => 'Order amount too low'];

        // Usage limits
        if ($coupon->usage_limit) {
            $total_used = $this->db->where('coupon_id', $coupon->id)->count_all_results('coupon_usage');
            if ($total_used >= $coupon->usage_limit)
                return ['status' => false, 'msg' => 'Coupon usage limit exceeded'];
        }

        if ($coupon->usage_per_user) {
            $user_used = $this->db->where(['coupon_id' => $coupon->id, 'user_id' => $user_id])->count_all_results('coupon_usage');
            if ($user_used >= $coupon->usage_per_user)
                return ['status' => false, 'msg' => 'You have already used this coupon'];
        }

        // Calculate discount
        $discount = $coupon->discount_type == 'percentage'
            ? ($order_amount * $coupon->discount_value / 100)
            : $coupon->discount_value;

        if ($coupon->max_discount && $discount > $coupon->max_discount)
            $discount = $coupon->max_discount;

        return [
            'status' => true,
            'discount' => $discount,
            'coupon_id' => $coupon->id,
            'msg' => "Coupon applied successfully"
        ];
    }

    public function log_usage($coupon_id, $user_id, $order_id = NULL) {
        return $this->db->insert('coupon_usage', [
            'coupon_id' => $coupon_id,
            'user_id' => $user_id,
            'order_id' => $order_id
        ]);
    }

    public function get_usage_logs() {
        $this->db->select('cu.*, c.code, c.discount_type, c.discount_value');
        $this->db->from('coupon_usage cu');
        $this->db->join('coupons c', 'cu.coupon_id = c.id');
        $this->db->order_by('cu.id', 'DESC');
        return $this->db->get()->result();
    }

    public function get_coupon_count() {
        return $this->db->count_all('coupons');
    }

    public function get_coupon_list($limit, $start) {
        $this->db->order_by('id', 'DESC');
        $this->db->limit($limit, $start);
        return $this->db->get('coupons')->result_array();
    }

}
