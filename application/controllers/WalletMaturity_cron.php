<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * WalletMaturity_cron — daily flip of is_matured on wallet_ledger credits.
 *
 * Run daily:
 *   CLI  : php index.php walletmaturity_cron run
 *   HTTP : /wallet-maturity-cron?token=<cron_token>
 */
class WalletMaturity_cron extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('WalletMaturity_model', 'maturity');
    }

    public function run()
    {
        if (!is_cli()) {
            $expected = $this->config->item('cron_token');
            if (!$expected || $this->input->get('token', true) !== $expected) show_404();
        }

        $updated = $this->maturity->process_daily_maturity();

        $this->output->set_content_type('application/json')
                     ->set_output(json_encode([
                         'status'  => 'success',
                         'message' => "Matured {$updated} ledger credit(s)",
                         'updated' => $updated,
                         'ran_at'  => date('Y-m-d H:i:s'),
                     ]));
    }
}
