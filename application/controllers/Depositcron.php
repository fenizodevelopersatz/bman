<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Depositcron — automatic USDT deposit crediting (no admin step).
 *
 * Reads the chain (BscScan API or log-capable RPC per Token Settings), detects
 * incoming USDT to custodial deposit addresses, waits for the configured
 * confirmations and credits ONLY the user's internal USDT Wallet — idempotently
 * (unique tx_hash), WITHOUT any private key. After crediting, the user can swap
 * and the "NEW DEPOSIT PENDING — admin will credit" state clears itself.
 *
 * Run it on a schedule (every 1–3 min). Two ways:
 *   CLI  :  php index.php depositcron run
 *   HTTP :  /credit-deposits-cron?token=YOUR_CRON_TOKEN   (set $config['cron_token'])
 */
class Depositcron extends CI_Controller
{
    public function run()
    {
        // CLI always allowed; over HTTP require the configured cron token.
        if (!is_cli()) {
            $expected = $this->config->item('cron_token');
            if (!$expected || $this->input->get('token', true) !== $expected) show_404();
        }

        $this->load->model('Depositlistener_model', 'listener');
        $only = (int)$this->input->get('user_id');   // optional: scan one user
        try {
            $res = $this->listener->scan($only ?: null);
            $out = ['status' => !empty($res['ok']) ? 'success' : 'error',
                    'message' => $res['message'] ?? 'done', 'ran_at' => date('Y-m-d H:i:s')];
        } catch (Exception $e) {
            $out = ['status' => 'error', 'message' => $e->getMessage(), 'ran_at' => date('Y-m-d H:i:s')];
        }
        echo json_encode($out).PHP_EOL;
    }
}
