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

        if (!$this->session->userdata('admin_logged_in')) redirect('admin/login');
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
            'options' => $this->tx->filterOptions(),
            'cron_token' => $this->config->item('cron_token'),
            'jobs' => [
                ['key' => 'stakingpurchase', 'label' => 'Staking Purchase', 'type' => 'swap', 'endpoint' => 'staking-purchase-cron', 'method' => 'GET', 'description' => 'Process multi-step USDT→BMAN swaps with gas fee detection, USDT payment, and BMAN distribution per coin_distribution_option (1-7).'],
                ['key' => 'roi_distribution', 'label' => 'ROI Distribution (Monthly + Maturity)', 'type' => 'roi', 'endpoint' => 'roi-distribution-cron', 'method' => 'GET', 'description' => 'Runs both ROI legs in the correct order: Monthly first (so Maturity can complete regular/combo records in the same pass), then Maturity. Use this for the normal daily run.'],
                ['key' => 'roi_monthly', 'label' => 'ROI Monthly Distribution (leg only)', 'type' => 'roi', 'endpoint' => 'roi-monthly-distribution-process', 'method' => 'GET', 'description' => 'Just the monthly leg — credits Regular/Combo records whose next_payment_date has arrived. Use this for targeted debugging; the combined button above already includes it.'],
                ['key' => 'roi_maturity', 'label' => 'ROI Maturity Payment (leg only)', 'type' => 'roi', 'endpoint' => 'roi-maturity-payment-process', 'method' => 'GET', 'description' => 'Just the maturity leg — pays the fixed lump ROI and returns principal for Fixed/Combo records whose fixed_maturity_date has arrived. Use this for targeted debugging; the combined button above already includes it.'],
                ['key' => 'wallet_maturity', 'label' => 'Wallet Maturity Unlock', 'type' => 'wallet', 'endpoint' => 'wallet-maturity-cron', 'method' => 'GET', 'description' => 'Daily flip of is_matured on wallet_ledger credits whose maturity_date has passed. Required for withdrawal eligibility calculations.'],
                ['key' => 'binary_matching_payout', 'label' => 'Binary Matching Payout (Engine + On-Chain)', 'type' => 'binary', 'endpoint' => 'binary-matching-payout-cron', 'method' => 'GET', 'description' => 'Runs the binary matching engine (queue-tracked via binary_matching_queue), enqueues one on-chain BMAN payout per newly-matched user, drains the treasury-balance-checked broadcast queue, and confirms pending transfers. Idempotent — safe to click repeatedly. See Matching History / Payout Queue for the resulting audit trail.'],
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
                case 'bonus':
                    $this->load->model('Bonusreduction_model', 'reduction');
                    $res = $this->reduction->run(['triggered_by' => 'cron']);
                    return $this->_json(['status' => !empty($res['status']) && $res['status'] === 'success' ? 'success' : 'success', 'message' => $res['message'] ?? 'done', 'data' => $res]);
                case 'roi_distribution':
                    $res = $this->_runViaHttp('roi-distribution-cron');
                    return $this->_json(['status' => 'success', 'message' => 'ROI distribution (monthly + maturity) executed', 'data' => $res]);
                case 'roi_monthly':
                    $res = $this->_runViaHttp('roi-monthly-distribution-process');
                    return $this->_json(['status' => 'success', 'message' => 'ROI monthly distribution executed', 'data' => $res]);
                case 'roi_maturity':
                    $res = $this->_runViaHttp('roi-maturity-payment-process');
                    return $this->_json(['status' => 'success', 'message' => 'ROI maturity payment executed', 'data' => $res]);
                case 'stakingpurchase':
                    $res = $this->_runViaHttp('staking-purchase-cron');
                    return $this->_json(['status' => 'success', 'message' => 'staking purchase cron executed', 'data' => $res]);
                case 'wallet_maturity':
                    $res = $this->_runViaHttp('wallet-maturity-cron');
                    return $this->_json(['status' => 'success', 'message' => 'Wallet maturity cron executed', 'data' => $res]);
                case 'binary_matching_payout':
                    $res = $this->_runViaHttp('binary-matching-payout-cron');
                    return $this->_json(['status' => 'success', 'message' => 'binary matching payout cron executed', 'data' => $res]);
                case 'match':
                    $this->load->model('staking/Stakingmatching_model', 'MB');
                    $res = $this->MB->run();
                    return $this->_json(['status' => 'success', 'message' => 'staking match run completed', 'data' => $res]);
                default:
                    return $this->_json(['status' => 'error', 'message' => 'Unknown job'], 422);
            }
        } catch (Throwable $e) {
            return $this->_json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
