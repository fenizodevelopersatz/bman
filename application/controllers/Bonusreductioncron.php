<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Bonusreductioncron — scheduled Bonus Wallet 60-day reduction → admin wallet.
 * ---------------------------------------------------------------------------
 * Thin wrapper: all logic lives in Bonusreduction_model::run() so the cron and
 * the admin "Run now" button share one tested code path.
 *
 * Every `staking_bonus_settings.reduction_interval_days` (default 60, set to 1
 * for testing), `reduction_percent` (default 50%) of each user's Bonus Wallet
 * (`user_wallets.bonus_balance`) is reduced and credited to the admin wallet —
 * per-user schedule anchored on `users.register_date`. Optional on-chain send
 * (user → admin bonus/treasury wallet) when reduction_onchain=1.
 *
 * Run it:
 *   CLI  :  php index.php bonusreductioncron run
 *   HTTP :  /bonus-reduction-cron?token=<cron_token>
 *
 * See docs/11_ADMIN_WALLET_MANAGEMENT.md.
 */
class Bonusreductioncron extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->model('Bonusreduction_model', 'reduction');
    }

    public function run()
    {
        // CLI always allowed; over HTTP require the configured cron token.
        if (!is_cli()) {
            $expected = $this->config->item('cron_token');
            if (!$expected || $this->input->get('token', true) !== $expected) show_404();
        }

        $out = $this->reduction->run(['triggered_by' => 'cron']);

        $this->output->set_content_type('application/json')
                     ->set_output(json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
    }
}
