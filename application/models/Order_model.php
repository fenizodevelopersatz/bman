<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Order_model extends CI_Model {

    public function list($limit = 50, $offset = 0, $q = null) {
        // Subquery: latest shipment row per order (by max id)
        $latestShipmentSql = "
            SELECT os.*
            FROM order_shipments os
            JOIN (
            SELECT order_id, MAX(id) AS max_id
            FROM order_shipments
            GROUP BY order_id
            ) t ON t.order_id = os.order_id AND t.max_id = os.id
        ";

        // Build main query
        $this->db->select("
            o.*,
            u.email AS user_email,
            COALESCE(s.status,
                CASE o.payment_status
                WHEN 'paid'   THEN 'paid'
                WHEN 'failed' THEN 'failed'
                ELSE 'placed'
                END
            ) AS ship_status
            ", false);

        $this->db->from('orders o');

        // join users for email
        $this->db->join('users u', 'u.id = o.user_id', 'left');

        // join ONLY the latest shipment (derived table) to avoid duplicates
        $this->db->join("($latestShipmentSql) s", 's.order_id = o.id', 'left', false);

        // Search: by order_code OR user email (no user_id)
        if (!empty($q)) {
            $this->db->group_start();
            $this->db->like('o.order_code', $q);
            $this->db->or_like('u.email', $q);
            $this->db->group_end();
        }

        $this->db->order_by('o.created_at', 'DESC');
        $this->db->limit($limit, $offset);

        return $this->db->get()->result();
        }

  public function get_order($order_id) {
    return $this->db->get_where('orders', ['id'=>$order_id])->row();
  }

  public function get_shipping_address($shipping_id) {
    return $this->db->get_where('user_addresses', ['id'=>$shipping_id])->row();
  }

  public function get_items_with_products($order_id) {
    $this->db->select('oi.*, p.name AS product_name, p.sku');
    $this->db->from('order_items oi');
    $this->db->join('products p','p.id=oi.product_id','left');
    $this->db->where('oi.order_id', $order_id);
    return $this->db->get()->result();
  }

  public function get_shipments($order_id) {
    return $this->db->order_by('id','DESC')->get_where('order_shipments', ['order_id'=>$order_id])->result();
  }

  public function get_history($order_id) {
    return $this->db->order_by('id','DESC')->get_where('order_status_history', ['order_id'=>$order_id])->result();
  }

  public function upsert_shipment($order_id, $data, $admin_id) {
    // If a shipment exists, update the latest; else create new.
    $last = $this->db->order_by('id','DESC')->get_where('order_shipments',['order_id'=>$order_id])->row();
    if ($last) {
      $this->db->where('id', $last->id)->update('order_shipments', $data);
      $ship_id = $last->id;
    } else {
      $data['order_id'] = $order_id;
      $this->db->insert('order_shipments', $data);
      $ship_id = $this->db->insert_id();
    }
    // history
    $this->db->insert('order_status_history', [
      'order_id' => $order_id,
      'status'   => $data['status'],
      'note'     => isset($data['remarks']) ? $data['remarks'] : null,
      'changed_by_admin_id' => $admin_id
    ]);
    return $ship_id;
  }

  public function create_invoice_if_missing($order_id, $currency='USD', $shipping_fee=0.00, $discount=0.00, $taxRatePct=0.00) {
    // Check existing
    $inv = $this->db->get_where('invoices', ['order_id'=>$order_id])->row();
    if ($inv) return $inv;

    $order = $this->get_order($order_id);
    if (!$order) return null;

    $addr  = $this->get_shipping_address($order->shipping_id);
    $items = $this->get_items_with_products($order_id);

    // Totals
    $subtotal = 0.00;
    foreach ($items as $it) { $subtotal += ((float)$it->price) * ((int)$it->quantity); }
    $tax = round($subtotal * ($taxRatePct/100), 2);
    $grand = max(0, $subtotal - (float)$discount + (float)$shipping_fee + $tax);

    // Generate invoice number
    $seq  = str_pad((string)$order_id, 5, '0', STR_PAD_LEFT);
    $invNo = 'INV'.date('Ymd').'-'.$seq;

    $bill_to = [
      'name'    => 'Customer #'.$order->user_id,  // adjust if you have a users table
      'email'   => null,
    ];
    $ship_to = $addr ? [
      'name'   => trim(($addr->first_name ?? '').' '.($addr->last_name ?? '')),
      'address'=> $addr->address,
      'city'   => $addr->city,
      'state'  => $addr->state,
      'zip'    => $addr->postal_code,
      'country'=> $addr->country
    ] : null;

    $this->db->insert('invoices', [
      'order_id'    => $order_id,
      'invoice_no'  => $invNo,
      'bill_to'     => $bill_to ? json_encode($bill_to) : null,
      'ship_to'     => $ship_to ? json_encode($ship_to) : null,
      'currency'    => $currency,
      'subtotal'    => $subtotal,
      'discount'    => $discount,
      'tax'         => $tax,
      'shipping_fee'=> $shipping_fee,
      'grand_total' => $grand,
      'notes'       => null
    ]);
    $invoice_id = $this->db->insert_id();

    foreach ($items as $it) {
      $line_total = ((float)$it->price) * ((int)$it->quantity);
      $tax_amount = round($line_total * ($taxRatePct/100), 2);
      $this->db->insert('invoice_items', [
        'invoice_id' => $invoice_id,
        'product_id' => $it->product_id,
        'name'       => $it->product_name ?: ('Product #'.$it->product_id),
        'sku'        => $it->sku,
        'qty'        => (int)$it->quantity,
        'unit_price' => (float)$it->price,
        'line_total' => $line_total,
        'tax_amount' => $tax_amount
      ]);
    }
    return $this->db->get_where('invoices',['id'=>$invoice_id])->row();
  }

  public function get_invoice($order_id) {
    $inv = $this->db->get_where('invoices', ['order_id'=>$order_id])->row();
    if (!$inv) return null;
    $items = $this->db->get_where('invoice_items', ['invoice_id'=>$inv->id])->result();
    return ['invoice'=>$inv, 'items'=>$items];
  }
}
