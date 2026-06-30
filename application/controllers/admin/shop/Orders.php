<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Orders extends CI_Controller {

  public function __construct() {
    parent::__construct();
    // auth check here...
    $this->load->model('Order_model','orders');
    $this->load->helper(['url','form']);
  }

  public function index() {
    $q = $this->input->get('q', true);
    $data['q'] = $q;
    $data['rows'] = $this->orders->list(100, 0, $q);
    $data['title']      = 'Orders List';
    $data['card_tilte'] = 'Orders List';
    $this->load->view('admin/orders/list', $data);
  }


    public function view($id){
    $order = $this->orders->get_order($id);
    if(!$order) show_404();

    $data['order']     = $order;
    $data['user']      = $this->db->get_where('users', ['id'=>$order->user_id])->row();
    $data['shipping']  = $this->orders->get_shipping_address($order->shipping_id);
    $data['order_items']= $this->orders->get_items_with_products($id); // include product_image, sku, name
    $data['shipments'] = $this->orders->get_shipments($id);           // latest first
    $data['history']   = $this->orders->get_history($id);             // latest first
    $data['title']     = 'Order Detail';
    $data['card_tilte'] = 'Orders Detail';
    $this->load->view('admin/orders/view', $data);
    }


  // POST: update tracking (manual)
  public function update_tracking() {
    $order_id = (int)$this->input->post('order_id');
    $status   = $this->input->post('status', true);
    $courier  = $this->input->post('courier_name', true);
    $trackno  = $this->input->post('tracking_number', true);
    $exp_del  = $this->input->post('expected_delivery', true); // YYYY-MM-DD
    $remarks  = $this->input->post('remarks', true);

    if (!$order_id || !$status) {
      return $this->output->set_content_type('application/json')
        ->set_output(json_encode(['status'=>'error','message'=>'Missing order or status']));
    }

    $payload = [
      'status'         => $status,
      'courier_name'   => $courier ?: null,
      'tracking_number'=> $trackno ?: null,
      'expected_delivery' => $exp_del ?: null,
      'remarks'        => $remarks ?: null
    ];
    if ($status === 'shipped' && empty($payload['shipped_at'])) {
      $payload['shipped_at'] = date('Y-m-d H:i:s');
    }
    if ($status === 'delivered') {
      $payload['delivered_at'] = date('Y-m-d H:i:s');
    }

    $admin_id = 1; // replace with session admin id
    $this->orders->upsert_shipment($order_id, $payload, $admin_id);

    return $this->output->set_content_type('application/json')
      ->set_output(json_encode(['status'=>'success','message'=>'Tracking updated']));
  }

  // Generate or view invoice (creates snapshot if missing)
  public function invoice($order_id) {
    $inv = $this->orders->create_invoice_if_missing($order_id, 'USD', 0.00, 0.00, 0.00);
    if (!$inv) show_404();
    $invData = $this->orders->get_invoice($order_id);
    $data['inv']   = $invData['invoice'];
    $data['items'] = $invData['items'];
    $data['order'] = $this->orders->get_order($order_id);
    $this->load->view('admin/orders/invoice', $data); // printable HTML
  }
}
