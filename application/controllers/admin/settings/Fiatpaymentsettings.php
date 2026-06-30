<?php 


class PaymentSettings extends CI_Controller {

public function __construct() {
    parent::__construct();
    $this->load->model('Payment_model');
    // Add admin authentication check here
}

public function index() {
    $data['stripe'] = $this->Payment_model->get_gateway('stripe');
    $data['paypal'] = $this->Payment_model->get_gateway('paypal');
    $data['title'] = "Payment Settings";
    $this->load->view('admin/payment_settings', $data);
}

public function save() {
    $gateway = $this->input->post('gateway');

    if ($gateway === 'stripe') {
        $data = [
            'publishable_key' => $this->input->post('publishable_key'),
            'secret_key'      => $this->input->post('secret_key'),
            'mode'            => $this->input->post('mode'),
            'status'          => $this->input->post('status') ? 1 : 0
        ];
    } elseif ($gateway === 'paypal') {
        $data = [
            'client_id'     => $this->input->post('client_id'),
            'client_secret' => $this->input->post('client_secret'),
            'mode'          => $this->input->post('mode'),
            'status'        => $this->input->post('status') ? 1 : 0
        ];
    } else {
        show_error("Invalid payment gateway");
    }

    $this->Payment_model->save_gateway($gateway, $data);
    $this->session->set_flashdata('success', ucfirst($gateway) . ' settings updated.');
    redirect('admin/payment-settings');
}
}
