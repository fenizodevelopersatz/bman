<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Cronlab extends CI_Controller
{
    private $is_super = false;

    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper(['url']);
        $this->load->model('Admin_model');
        $this->load->model('Onchaintx_model', 'tx');

        if (!$this->session->userdata('admin_logged_in')) redirect('aaddmmiinn/login');
        $user = $this->Admin_model->get_user($this->session->userdata('admin_userid'));
        if ($user && $user->admin_roll == '1') {
            $perm = json_decode($user->permission_pages, true);
            if (empty($perm['wallet_management']) && empty($perm['staking_management']) && empty($perm['rank_management'])) {
                $this->session->set_flashdata('error', 'Access Denied: You do not have permission.');
                redirect('admin');
            }
        }
        $this->is_super = ($user && $user->admin_roll == '1');
    }

    private function _json($data, $code = 200)
    {
        $this->output->set_status_header($code)
                     ->set_content_type('application/json')
                     ->set_output(json_encode($data));
    }

    /**
     * Trigger another cron controller via an internal HTTP call to its own
     * route, rather than instantiating a second CI_Controller in-process.
     * CodeIgniter 3's CI_Controller::__construct() rebuilds every already-loaded
     * "superobject" class from a global is_loaded() registry — once Session is
     * loaded (every admin controller loads it), a second controller instance
     * fails to re-resolve it ("Unable to locate the specified class:
     * Session.php"). Hitting the existing route as a fresh top-level request
     * avoids that entirely.
     */
    private function _runViaHttp($endpoint)
    {
        $token = $this->config->item('cron_token');
        $url = base_url($endpoint) . ($token ? '?' . http_build_query(['token' => $token]) : '');

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_HTTPHEADER     => ['X-Requested-With: XMLHttpRequest'],
        ]);
        $output = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($output === false) return ['status' => 'error', 'message' => "Internal request failed: {$err}"];
        $decoded = json_decode($output, true);
        return $decoded !== null ? $decoded : ['status' => 'error', 'message' => 'Failed to parse response', 'raw' => $output];
    }

    public function index()
    {
        $this->load->model('Depositlistener_model', 'listener');
        $data = [
            'title' => 'Cron Lab',
            'card_tilte' => 'Developer Testing Page for Crons + Transaction Audit',
            'is_super' => $this->is_super,
            'balances' => $this->tx->walletTotals(),
            'cron_token' => $this->config->item('cron_token'),
            'jobs' => [
                ['key' => 'stakingpurchase', 'label' => 'Staking Purchase', 'type' => 'swap', 'endpoint' => 'staking-purchase-cron', 'method' => 'GET', 'description' => 'Process multi-step USDT→BMAN swaps with gas fee detection, USDT payment, and BMAN distribution per coin_distribution_option (1-7).'],
                ['key' => 'roi_distribution', 'label' => 'ROI Distribution (Monthly + Maturity)', 'type' => 'roi', 'endpoint' => 'roi-distribution-cron', 'method' => 'GET', 'description' => 'Runs both ROI legs in the correct order: Monthly first (so Maturity can complete regular/combo records in the same pass), then Maturity. Use this for the normal daily run.'],
                ['key' => 'wallet_maturity', 'label' => 'Wallet Maturity Unlock', 'type' => 'wallet', 'endpoint' => 'wallet-maturity-cron', 'method' => 'GET', 'description' => 'Daily flip of is_matured on wallet_ledger credits whose maturity_date has passed. Required for withdrawal eligibility calculations.'],
                ['key' => 'binary_matching_payout', 'label' => 'Binary Matching Payout (Engine + On-Chain)', 'type' => 'binary', 'endpoint' => 'binary-matching-payout-cron', 'method' => 'GET', 'description' => 'Runs the binary matching engine (queue-tracked via binary_matching_queue), enqueues one on-chain BMAN payout per newly-matched user, drains the treasury-balance-checked broadcast queue, and confirms pending transfers. Idempotent — safe to click repeatedly. See Matching History / Payout Queue for the resulting audit trail.'],
                // inproc: has no token-gated cron route of its own — the view
                // POSTs it to cron-lab/run so it stays behind this page's admin
                // gate. A diagnostic that writes synthetic rows should not be
                // reachable by URL+token the way the real crons are.
                ['key' => 'binary_matching_probe', 'label' => 'Binary Matching — Spec Compliance Probe (Diagnostic)', 'type' => 'binary', 'endpoint' => '', 'inproc' => true, 'method' => 'POST', 'description' => 'DIAGNOSTIC, not a cron — it moves no real money and pays no real member. Builds the level-wise spec\'s example A..O tree out of 19 SYNTHETIC users, drives the real matching engine against it, reports rule-by-rule whether the live engine matches the level-wise business spec (level-by-level distribution, Lock Wallet volume source, 10% split 8/2, completed-level lock, highest-package ceiling, excess-to-admin), then deletes every row it created. Three guards: it refuses to run if any REAL member currently qualifies for matching; it parks real unprocessed binary volume so the engine cannot sweep it; and teardown runs from a shutdown hook so even a DB error cannot strand test data. Never touches the on-chain payout cron. Safe to click repeatedly.'],
                ['key' => 'rank_achievement', 'label' => 'Rank Achievement (Permanent Ranks)', 'type' => 'rank', 'endpoint' => 'rank-achievement-cron', 'method' => 'GET', 'description' => 'Evaluates every active member against the 11-rank qualification matrix (§10) and promotes those who qualify, then issues the rank reward via the wallet ledger and mints the certificate. Members are processed deepest-first so a whole chain of promotions settles in one pass. Ranks are PERMANENT — this job can only ever promote, never demote. Idempotent: unique indexes on rank_rewards / rank_certificates make double-payment impossible, and a previously failed payout is retried. Run hourly. See Rank Management ▸ Rank History for the audit trail.'],
                ['key' => 'rank_power', 'label' => 'Rank Power (60-day Cycle + Group Incentive)', 'type' => 'rank', 'endpoint' => 'rank-power-cron', 'method' => 'GET', 'description' => 'Rolls the 60-day Rank Power cycle when it expires (closing the old one and opening the next the very next day) and recalculates every member\'s power rank from CURRENT-CYCLE staking volume only. Power drives Group Incentive qualification and is separate from the permanent achievement rank — this job never touches user_ranks. Run daily. See Rank Management ▸ Rank Power.'],
                ['key' => 'wallet_transfer_settlement', 'label' => 'Wallet Transfer Settlement (On-Chain)', 'type' => 'wallet', 'endpoint' => 'wallet-transfer-settlement-cron', 'method' => 'GET', 'description' => 'Sweeps completed wallet_internal_transfer rows (member-to-member and self-moves, user- or admin-initiated) and sends real BMAN from the Treasury wallet to each resolved destination address, up to wallet_transfer_settlement_settings.max_batch_size per run. Disabled and dry-run by default — flip wallet_transfer_settlement_settings.enabled / dry_run to go live. Run every few minutes.'],
                ['key' => 'member_bulk_bman', 'label' => 'Member Bulk Upload — Opening BMAN (Hot Wallet → Member)', 'type' => 'wallet', 'endpoint' => 'member-bulk-bman-cron', 'method' => 'GET', 'description' => 'Delivers the opening BMAN balance to members created by Members Management ▸ Bulk Upload. The import creates the accounts and leaves each row that carried a bman amount at bman_status=pending. Phase 1 drains that queue: it sends real BMAN from the admin hot (Treasury) wallet to each member\'s generated on-chain address, then posts the same amount to their EXCHANGE wallet through Walletledger_model — so user_wallets.exchange_balance, wallet_ledger and onchain_transactions all reflect it and the member sees the balance in their panel. Phase 2 back-credits any send that reached the chain without its ledger entry (a crash between the two steps); it is idempotent via the UNIQUE (tx_hash, wallet_type) index, so it can never double-credit. This is NEW money — nothing is debited from any internal wallet. Disabled and dry-run by default; a dry run sends nothing and deliberately credits nothing. Set credit_exchange_wallet=0 for on-chain-only delivery. A failed send stays failed until an admin re-queues it from the batch detail page. Run every few minutes.'],
                ['key' => 'chain_sync', 'label' => 'Chain Sync (Confirmations + Balances + Gas)', 'type' => 'wallet', 'endpoint' => 'chain-sync-cron', 'method' => 'GET', 'description' => 'Re-verifies every onchain_transactions row still pending/processing/broadcasting against the real chain (status, confirmations, reorgs) and backfills the real gas_used/gas_price/gas_fee_total from the mined receipt — this is what makes Gas Fee Transactions and the gas_fee_ledger audit trail show real numbers instead of staying empty. Also syncs priority + rotation-window addresses and refreshes the treasury balance. Safe to click repeatedly; cursor-based, resumable. Run every minute alongside Staking Purchase.'],
            ],
        ];
        $this->load->view('admin/wallet/cron_lab', $data);
    }

    public function run()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $job = $this->input->post('job', true);
        $userId = (int)$this->input->post('user_id');

        try {
            switch ($job) {
                case 'roi_distribution':
                    $res = $this->_runViaHttp('roi-distribution-cron');
                    return $this->_json(['status' => 'success', 'message' => 'ROI distribution (monthly + maturity) executed', 'data' => $res]);
                case 'stakingpurchase':
                    $res = $this->_runViaHttp('staking-purchase-cron');
                    return $this->_json(['status' => 'success', 'message' => 'staking purchase cron executed', 'data' => $res]);
                case 'wallet_maturity':
                    $res = $this->_runViaHttp('wallet-maturity-cron');
                    return $this->_json(['status' => 'success', 'message' => 'Wallet maturity cron executed', 'data' => $res]);
                case 'binary_matching_payout':
                    $res = $this->_runViaHttp('binary-matching-payout-cron');
                    return $this->_json(['status' => 'success', 'message' => 'binary matching payout cron executed', 'data' => $res]);
                case 'wallet_transfer_settlement':
                    $res = $this->_runViaHttp('wallet-transfer-settlement-cron');
                    return $this->_json(['status' => 'success', 'message' => 'wallet transfer settlement cron executed', 'data' => $res]);
                case 'member_bulk_bman':
                    $res = $this->_runViaHttp('member-bulk-bman-cron');
                    return $this->_json(['status' => 'success', 'message' => 'member bulk BMAN cron executed', 'data' => $res]);
                case 'chain_sync':
                    $res = $this->_runViaHttp('chain-sync-cron');
                    return $this->_json(['status' => 'success', 'message' => 'chain sync cron executed', 'data' => $res]);
                // Diagnostic, run in-process for the same reason the rank jobs
                // are: it must not be reachable over HTTP without this page's
                // admin gate, and it is not a token-gated cron endpoint.
                case 'binary_matching_probe':
                    $this->load->model('staking/Binarymatchingprobe_model', 'probe');
                    $res = $this->probe->probe();
                    return $this->_json(['status' => $res['status'], 'message' => $res['message'], 'data' => $res]);
                case 'match':
                    $this->load->model('staking/Stakingmatching_model', 'MB');
                    $res = $this->MB->run();
                    return $this->_json(['status' => 'success', 'message' => 'staking match run completed', 'data' => $res]);
                // Rank jobs run in-process (not over HTTP) so a long sweep is not
                // bound by the curl timeout; the model is the same code path the
                // token-gated cron endpoints use.
                case 'rank_achievement':
                    $this->load->model('staking/Rankcron_model', 'rankcron');
                    $res = $this->rankcron->runAchievement(true);
                    return $this->_json(['status' => $res['status'], 'message' => $res['message'], 'data' => $res]);
                case 'rank_power':
                    $this->load->model('staking/Rankcron_model', 'rankcron');
                    $res = $this->rankcron->runPower(true);
                    return $this->_json(['status' => $res['status'], 'message' => $res['message'], 'data' => $res]);
                default:
                    return $this->_json(['status' => 'error', 'message' => 'Unknown job'], 422);
            }
        } catch (Throwable $e) {
            return $this->_json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
