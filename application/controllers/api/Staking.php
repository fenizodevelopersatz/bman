<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * API ▸ Staking
 * REST endpoints for staking operations (ROI details, etc)
 */
class Staking extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('url');
        $this->load->model('Admin_model');
        $this->load->database();
        header('Content-Type: application/json');
    }

    private function _json($data, $code = 200)
    {
        $this->output->set_status_header($code)->set_output(json_encode($data));
    }

    /**
     * GET /api/staking/roi-details?order_id=SWP-xxx
     * Returns calculated ROI details for a staking order
     */
    public function roi_details()
    {
        $order_id = $this->input->get('order_id', true);

        if (!$order_id) {
            return $this->_json(['status' => 'error', 'message' => 'Missing order_id'], 400);
        }

        // Get staking order
        $order = $this->db->select('sso.*, sp.annual_roi_rate, sp.maturity_days, sp.name as package_name')
                          ->from('staking_swap_orders sso')
                          ->join('staking_packages sp', 'sso.package_id = sp.id', 'left')
                          ->where('sso.ref', $order_id)
                          ->get()
                          ->row_array();

        if (!$order) {
            return $this->_json(['status' => 'error', 'message' => 'Order not found'], 404);
        }

        // Calculate ROI
        $principal = (float)$order['bman_amount'] ?? 0;
        $rate = (float)$order['annual_roi_rate'] ?? 10;
        $maturity_days = (int)$order['maturity_days'] ?? 365;

        $annual_roi = $principal * ($rate / 100);
        $daily_roi = $annual_roi / 365;
        $hourly_roi = $daily_roi / 24;
        $total_roi_maturity = $principal * ($rate / 100) * ($maturity_days / 365);

        // Build response
        $response = [
            'status' => 'success',
            'data' => [
                'order_id' => $order['ref'],
                'user_id' => $order['user_id'],
                'created_at' => $order['created_at'],
                'updated_at' => $order['updated_at'],

                // Amounts
                'usdt_sent' => (float)$order['usdt_amount'],
                'bman_amount' => $principal,
                'bonus_bman' => (float)$order['bonus_bman'],

                // ROI Details
                'annual_roi_rate' => $rate,
                'maturity_days' => $maturity_days,
                'duration' => ceil($maturity_days / 365),

                // Calculations
                'annual_roi' => round($annual_roi, 8),
                'daily_roi' => round($daily_roi, 8),
                'hourly_roi' => round($hourly_roi, 10),
                'total_roi_at_maturity' => round($total_roi_maturity, 8),
                'maturity_date' => date('Y-m-d H:i:s', strtotime($order['created_at'] . ' + ' . $maturity_days . ' days')),

                // Distribution
                'coin_distribution_option_id' => $order['coin_distribution_option'] ?? 1,
                'distribution_breakdown' => $this->_getDistributionBreakdown($principal, $order['coin_distribution_option'] ?? 1),

                // Status
                'status' => $order['status'],
                'cron_status' => $order['cron_status'],
            ]
        ];

        return $this->_json($response);
    }

    /**
     * Calculate wallet distribution breakdown
     */
    private function _getDistributionBreakdown($amount, $option)
    {
        $options = [
            1 => ['exchange' => 100, 'earning' => 0, 'staking' => 0, 'bonus' => 0],
            2 => ['exchange' => 90, 'earning' => 0, 'staking' => 0, 'bonus' => 10],
            3 => ['exchange' => 80, 'earning' => 10, 'staking' => 0, 'bonus' => 10],
            4 => ['exchange' => 80, 'earning' => 10, 'staking' => 10, 'bonus' => 0],
            5 => ['exchange' => 90, 'earning' => 10, 'staking' => 0, 'bonus' => 0],
            6 => ['exchange' => 90, 'earning' => 0, 'staking' => 10, 'bonus' => 0],
            7 => ['exchange' => 70, 'earning' => 10, 'staking' => 10, 'bonus' => 10]
        ];

        $dist = $options[$option] ?? $options[1];

        return [
            'option' => $option,
            'exchange_wallet' => [
                'percentage' => $dist['exchange'],
                'amount' => round($amount * ($dist['exchange'] / 100), 8)
            ],
            'earning_wallet' => [
                'percentage' => $dist['earning'],
                'amount' => round($amount * ($dist['earning'] / 100), 8)
            ],
            'staking_wallet' => [
                'percentage' => $dist['staking'],
                'amount' => round($amount * ($dist['staking'] / 100), 8)
            ],
            'bonus_wallet' => [
                'percentage' => $dist['bonus'],
                'amount' => round($amount * ($dist['bonus'] / 100), 8)
            ]
        ];
    }

    /**
     * GET /api/staking/roi-timeline?order_id=SWP-xxx
     * Returns ROI distribution timeline (daily, weekly, monthly, yearly)
     */
    public function roi_timeline()
    {
        $order_id = $this->input->get('order_id', true);

        if (!$order_id) {
            return $this->_json(['status' => 'error', 'message' => 'Missing order_id'], 400);
        }

        $order = $this->db->select('sso.*, sp.annual_roi_rate, sp.maturity_days')
                          ->from('staking_swap_orders sso')
                          ->join('staking_packages sp', 'sso.package_id = sp.id', 'left')
                          ->where('sso.ref', $order_id)
                          ->get()
                          ->row_array();

        if (!$order) {
            return $this->_json(['status' => 'error', 'message' => 'Order not found'], 404);
        }

        $principal = (float)$order['bman_amount'];
        $rate = (float)$order['annual_roi_rate'];
        $maturity_days = (int)$order['maturity_days'];
        $created_at = strtotime($order['created_at']);

        $annual_roi = $principal * ($rate / 100);
        $daily_roi = $annual_roi / 365;

        // Generate timeline
        $timeline = [];
        $periods = [
            ['days' => 1, 'label' => 'Day 1'],
            ['days' => 7, 'label' => 'Week 1'],
            ['days' => 30, 'label' => 'Month 1'],
            ['days' => 90, 'label' => 'Quarter 1'],
            ['days' => 180, 'label' => 'Half Year'],
            ['days' => 365, 'label' => 'Year 1'],
            ['days' => $maturity_days, 'label' => 'Maturity']
        ];

        foreach ($periods as $period) {
            if ($period['days'] > $maturity_days && $period['days'] != $maturity_days) {
                continue;
            }

            $roi_accrued = $daily_roi * $period['days'];
            $date = date('Y-m-d', $created_at + ($period['days'] * 86400));

            $timeline[] = [
                'period' => $period['label'],
                'days' => $period['days'],
                'date' => $date,
                'roi_accrued' => round($roi_accrued, 8),
                'cumulative_roi' => round($roi_accrued, 8),
                'status' => $period['days'] <= $maturity_days ? 'active' : 'future',
                'is_matured' => $period['days'] >= $maturity_days
            ];
        }

        return $this->_json([
            'status' => 'success',
            'data' => [
                'order_id' => $order['ref'],
                'principal' => $principal,
                'annual_roi_rate' => $rate,
                'maturity_days' => $maturity_days,
                'timeline' => $timeline
            ]
        ]);
    }

    /**
     * GET /api/staking/download-roi-report?order_id=SWP-xxx
     * Downloads ROI report as PDF
     */
    public function download_roi_report()
    {
        $order_id = $this->input->get('order_id', true);

        if (!$order_id) {
            redirect('dashboard');
        }

        $order = $this->db->select('sso.*, sp.annual_roi_rate, sp.maturity_days, sp.name as package_name, u.username, u.email')
                          ->from('staking_swap_orders sso')
                          ->join('staking_packages sp', 'sso.package_id = sp.id', 'left')
                          ->join('users u', 'u.id = sso.user_id', 'left')
                          ->where('sso.ref', $order_id)
                          ->get()
                          ->row_array();

        if (!$order) {
            redirect('dashboard');
        }

        // Load PDF library
        $this->load->library('pdf');

        $principal = (float)$order['bman_amount'];
        $rate = (float)$order['annual_roi_rate'];
        $maturity_days = (int)$order['maturity_days'];

        $annual_roi = $principal * ($rate / 100);
        $total_roi = $principal * ($rate / 100) * ($maturity_days / 365);

        // HTML content
        $html = '
        <h2>ROI Report</h2>
        <p>Order ID: ' . $order['ref'] . '</p>
        <p>User: ' . $order['username'] . ' (' . $order['email'] . ')</p>

        <h3>Order Details</h3>
        <table border="1" cellpadding="5">
            <tr><td>USDT Sent:</td><td>' . $order['usdt_amount'] . ' USDT</td></tr>
            <tr><td>BMAN Received:</td><td>' . $principal . ' BMAN</td></tr>
            <tr><td>Bonus BMAN:</td><td>' . $order['bonus_bman'] . ' BMAN</td></tr>
            <tr><td>Package:</td><td>' . $order['package_name'] . '</td></tr>
            <tr><td>Created:</td><td>' . $order['created_at'] . '</td></tr>
        </table>

        <h3>ROI Calculation</h3>
        <table border="1" cellpadding="5">
            <tr><td>Principal:</td><td>' . $principal . ' BMAN</td></tr>
            <tr><td>Annual Rate:</td><td>' . $rate . '%</td></tr>
            <tr><td>Annual ROI:</td><td>' . round($annual_roi, 8) . ' BMAN</td></tr>
            <tr><td>Duration:</td><td>' . $maturity_days . ' days</td></tr>
            <tr><td>Total ROI at Maturity:</td><td>' . round($total_roi, 8) . ' BMAN</td></tr>
        </table>
        ';

        // Generate PDF
        $this->pdf->load_view('report', ['html' => $html], false);
        $filename = 'ROI_Report_' . $order['ref'] . '.pdf';
        $this->pdf->render();
        $this->pdf->download($filename);
    }
}
