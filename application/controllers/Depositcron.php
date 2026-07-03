<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Depositcron — background USDT deposit listener.
 * Run every ~15s from a scheduler:
 *   * * * * * php index.php depositcron run   (loop 4× with sleeps for 15s cadence)
 * or a Windows Task Scheduler / cron hitting the CLI. CLI-only (not web).
 *
 * It reads the chain (BscScan API or log-capable RPC per Token Settings),
 * detects incoming USDT to custodial addresses, and credits confirmed
 * deposits — all WITHOUT any private key.
 */
class Depositcron extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!is_cli()) show_404();               // never web-accessible
        $this->load->model('Depositlistener_model', 'listener');
    }

    public function run()
    {
        $res = $this->listener->scan();
        echo date('Y-m-d H:i:s').' '.($res['message'] ?? 'done').PHP_EOL;
    }
}
