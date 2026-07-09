<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Staking_roi_cron extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Custodialwallet_model', 'wallet');
    }

    /**
     * Hourly ROI Processing Cron
     * For each active staking, calculate and send ROI to user
     * Call via: curl http://admin:pass@192.168.29.18:9000/staking-roi-cron
     */
    public function index()
    {
        // Cron protection: require basic auth or secret token
        if (!$this->_authenticate()) {
            http_response_code(401);
            echo "Unauthorized\n";
            log_message('warning', 'Staking ROI cron unauthorized access attempt');
            return;
        }

        log_message('info', 'Staking ROI cron started');

        $processed = 0;
        $failed = 0;

        // Get all active stakings
        $stakings = $this->db->select('id, user_id, package_id, bman_amount, status, activated_at, created_at')
            ->where('status', 'active')
            ->get('user_stakes')
            ->result_array();

        foreach ($stakings as $staking) {
            try {
                if ($this->_processStakingRoi($staking)) {
                    $processed++;
                } else {
                    $failed++;
                }
            } catch (Exception $e) {
                log_message('error', 'ROI processing error for staking ' . $staking['id'] . ': ' . $e->getMessage());
                $failed++;
            }
        }

        $message = "Staking ROI cron completed: {$processed} processed, {$failed} failed, " . count($stakings) . " total";
        log_message('info', $message);

        echo json_encode([
            'status' => true,
            'message' => $message,
            'processed' => $processed,
            'failed' => $failed,
            'total' => count($stakings),
            'timestamp' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Process ROI for a single staking
     * Uses staking_swap_orders data if available (plan_code, duration_years)
     */
    private function _processStakingRoi($staking)
    {
        $staking_id = $staking['id'];
        $user_id = $staking['user_id'];
        $bman_amount = $staking['bman_amount'];

        // Try to get enhanced data from staking_swap_orders
        $swap_order = $this->db->select('plan_code, plan_id, duration_years, coin_distribution_option_id, cron_status')
            ->where('id', $staking_id)
            ->get('staking_swap_orders')
            ->row_array();

        // Get package ROI configuration
        $package = $this->db->select('roi, days_duration')
            ->get_where('package_config', ['id'=>$staking['package_id']])
            ->row_array();

        if (!$package) {
            log_message('error', "Package not found for staking {$staking_id}");
            return false;
        }

        // Calculate daily ROI: amount * (roi_percentage / 100)
        $roi_percentage = (float)$package['roi'] ?? 0;
        $daily_roi = $bman_amount * ($roi_percentage / 100);

        if ($daily_roi <= 0) {
            log_message('warning', "ROI is zero or negative for staking {$staking_id}: {$daily_roi}");
            return false;
        }

        // Hourly ROI = Daily ROI / 24
        $hourly_roi = $daily_roi / 24;

        // Get user address for sending ROI
        $user = $this->db->select('user_wallet_public_key')
            ->get_where('users', ['id'=>$user_id])
            ->row_array();

        if (!$user || !$user['user_wallet_public_key']) {
            log_message('error', "User wallet not found for user {$user_id}");
            return false;
        }

        $user_address = strtolower($user['user_wallet_public_key']);

        // Get admin wallet for sending ROI
        $config = $this->db->get_where('token_settings', ['status'=>1])->row_array();
        $admin_wallet = strtolower($config['contract_wallet'] ?? '');
        $rpc_url = $config['rpc_endpoint'] ?? '';

        if (!$admin_wallet || !$rpc_url) {
            log_message('error', 'Admin wallet or RPC not configured');
            return false;
        }

        // Determine which wallet column to credit based on coin_distribution_option_id
        // 1 = exchange, 2 = staking, 3 = earning, 4 = bonus
        $wallet_column = 'earning'; // default
        if ($swap_order) {
            $coin_dist_id = (int)($swap_order['coin_distribution_option_id'] ?? 1);
            $wallet_columns = [
                1 => 'exchange',
                2 => 'staking',
                3 => 'earning',
                4 => 'bonus',
            ];
            $wallet_column = $wallet_columns[$coin_dist_id] ?? 'earning';
        }

        // Mark swap order cron status if it exists
        if ($swap_order) {
            $this->db->where('id', $staking_id)
                ->update('staking_swap_orders', [
                    'cron_status' => 'processing',
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
        }

        // In production, you would:
        // 1. Broadcast a transaction from admin wallet to send ROI BMAN to user
        // 2. Wait for confirmation
        // 3. Record the transaction in onchain_transactions
        // 4. Update staking record with accumulated_roi

        // For now, simulate by recording in database
        $this->db->insert('staking_roi_ledger', [
            'staking_id' => $staking_id,
            'user_id' => $user_id,
            'roi_amount' => $hourly_roi,
            'roi_type' => 'hourly',
            'wallet_column' => $wallet_column,
            'processed_at' => date('Y-m-d H:i:s'),
        ]);

        // Update accumulated ROI
        $this->db->where('id', $staking_id)
            ->set('accumulated_roi', "accumulated_roi + {$hourly_roi}", false)
            ->update('user_stakes');

        // Credit wallet balance to appropriate column (in production, wait for on-chain confirmation)
        $this->db->where('user_id', $user_id)
            ->set($wallet_column, "{$wallet_column} + {$hourly_roi}", false)
            ->update('wallet_ledger');

        // Mark swap order cron as completed
        if ($swap_order) {
            $this->db->where('id', $staking_id)
                ->update('staking_swap_orders', [
                    'cron_status' => 'completed',
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
        }

        log_message('info', "ROI processed for staking {$staking_id}: {$hourly_roi} BMAN to user {$user_id} ({$wallet_column})");
        return true;
    }

    /**
     * Authenticate cron request
     * Check basic auth or secret token
     */
    private function _authenticate()
    {
        // Method 1: Basic Auth (admin:password)
        if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
            // You can verify against config or database
            // For now, just require it to be set
            return !empty($_SERVER['PHP_AUTH_USER']);
        }

        // Method 2: Secret token in header or query param
        $token = $this->input->get_request_header('X-Cron-Token') ??
                 $this->input->get('token') ?? '';

        if ($token) {
            $config = $this->db->get_where('token_settings', ['status'=>1])->row_array();
            $cron_token = $config['cron_secret_token'] ?? '';
            return $token === $cron_token;
        }

        // Method 3: CLI only (no HTTP)
        if (php_sapi_name() === 'cli') {
            return true;
        }

        return false;
    }
}
