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
                ['key' => 'roi', 'label' => 'ROI Run', 'type' => 'roi', 'endpoint' => 'earn-cron-made', 'method' => 'GET', 'description' => 'Credit daily ROI on active investments.'],
                ['key' => 'roi_maturity', 'label' => 'ROI Maturity Process', 'type' => 'roi', 'endpoint' => 'cron/roi_maturity/process', 'method' => 'GET', 'description' => 'Process matured staking investments and release final ROI payouts to user earning wallets.'],
                ['key' => 'rank', 'label' => 'Rank Update', 'type' => 'rank', 'endpoint' => 'rank-cron-made', 'method' => 'GET', 'description' => 'Update rank eligibility and rank payouts.'],
                ['key' => 'binary', 'label' => 'Binary Match', 'type' => 'binary', 'endpoint' => 'binary-cron-made', 'method' => 'GET', 'description' => 'Run binary matching commission settlement.'],
                ['key' => 'bonus', 'label' => 'Bonus Reduction', 'type' => 'bonus', 'endpoint' => 'bonus-reduction-cron', 'method' => 'GET', 'description' => 'Apply scheduled bonus reductions and admin credit.'],
                ['key' => 'match', 'label' => 'Staking Match', 'type' => 'staking', 'endpoint' => 'admin/staking/matching/run', 'method' => 'POST', 'description' => 'Trigger staking binary matching manually.'],
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
                case 'roi':
                    return $this->_json(['status' => 'error', 'message' => 'ROI run helper not wired in this environment. Use /earn-cron-made for now.'], 501);
                case 'rank':
                    return $this->_json(['status' => 'error', 'message' => 'Rank run helper not wired in this environment. Use /rank-cron-made for now.'], 501);
                case 'binary':
                    return $this->_json(['status' => 'error', 'message' => 'Binary run helper not wired in this environment. Use /binary-cron-made for now.'], 501);
                case 'stakingpurchase':
                    $this->load->model('staking/StakingSwap_model', 'staking_swap');
                    $this->load->controller('StakingPurchasecron');
                    $controller = new StakingPurchasecron();
                    ob_start();
                    $controller->run();
                    $output = ob_get_clean();
                    try {
                        $res = json_decode($output, true);
                    } catch (Exception $e) {
                        $res = ['status' => 'error', 'message' => 'Failed to parse response', 'raw' => $output];
                    }
                    return $this->_json(['status' => 'success', 'message' => 'staking purchase cron executed', 'data' => $res]);
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
