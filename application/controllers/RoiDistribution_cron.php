<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * ROI Distribution — unified entry point.
 *
 * Thin orchestrator only: it does NOT reimplement ROI logic. It calls the two
 * independently-tested crons in the one order that lets a single pass fully
 * settle both — Monthly first, then Maturity:
 *
 *   1. RoiMonthlyDistribution_cron — credits monthly ROI for regular/combo
 *      plans whose next_payment_date is due, and marks the schedule complete
 *      once every month is paid.
 *   2. RoiMaturityPayment_cron — pays the fixed lump ROI and returns the
 *      principal for fixed/combo plans whose fixed_maturity_date is due.
 *      Skips regular/combo records whose monthly schedule isn't finished yet
 *      (which step 1, run first, minimizes).
 *
 * Each cron keeps its own route/CLI command for granular retry (see ROI
 * Distribution History's per-record "Send Now") — this just gives you one
 * button/command for the common case of "run everything that's due".
 *
 *   HTTP : /roi-distribution-cron?token=YOUR_CRON_TOKEN
 *   CLI  : php index.php roidistribution_cron run
 *          php index.php roidistribution_cron watch   (loop until nothing due)
 */
class RoiDistribution_cron extends CI_Controller
{
    private $cronRoutes = [
        'monthly'  => 'roi-monthly-distribution-process',
        'maturity' => 'roi-maturity-payment-process',
    ];

    public function run()
    {
        if (!is_cli()) {
            $expected = $this->config->item('cron_token');
            if (!$expected || $this->input->get('token', true) !== $expected) {
                show_404();
            }
        }

        $monthly  = $this->_call('monthly');
        $maturity = $this->_call('maturity');

        echo json_encode([
            'status'   => 'success',
            'message'  => 'ROI distribution (monthly then maturity) completed',
            'monthly'  => $monthly,
            'maturity' => $maturity,
            'ran_at'   => date('Y-m-d H:i:s'),
        ]) . PHP_EOL;
    }

    /** Call one leg's real route as a fresh top-level request — see Roihistory::_runCron() for why. */
    private function _call($key)
    {
        $token = $this->config->item('cron_token');
        $url = base_url($this->cronRoutes[$key]) . ($token ? '?' . http_build_query(['token' => $token]) : '');

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_HTTPHEADER     => ['X-Requested-With: XMLHttpRequest'],
        ]);
        $output = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($output === false) return ['status' => false, 'error' => "Internal request failed: {$err}"];
        $decoded = json_decode($output, true);
        return $decoded !== null ? $decoded : ['status' => false, 'error' => 'Non-JSON output', 'raw' => $output];
    }

    private function _dueCount()
    {
        $now = date('Y-m-d H:i:s');
        $monthly = $this->db->where_in('plan_type', ['regular', 'combo'])
            ->where('overall_status IN (\'active\',\'in_progress\')', null, false)
            ->where('regular_payments_completed < regular_payment_count', null, false)
            ->where('next_payment_date <=', $now)->count_all_results('roi_staking_management');
        $maturity = $this->db->where('overall_status !=', 'completed')
            ->where('fixed_maturity_date <=', $now)
            ->group_start()
                ->where('plan_type', 'fixed')
                ->or_where('regular_payments_completed >= regular_payment_count', null, false)
            ->group_end()
            ->count_all_results('roi_staking_management');
        return $monthly + $maturity;
    }

    /** CLI convenience: keep re-running both legs every few seconds until nothing is due. */
    public function watch($pollSeconds = 5, $maxSeconds = 300)
    {
        if (!is_cli()) show_404();
        $pollSeconds = (int)$pollSeconds ?: 5;
        $maxSeconds  = (int)$maxSeconds  ?: 300;
        $start = time();
        $pass = 0;
        $lastMonthly = null; $lastMaturity = null;

        do {
            $pass++;
            $lastMonthly  = $this->_call('monthly');
            $lastMaturity = $this->_call('maturity');
            $due = $this->_dueCount();

            fwrite(STDOUT, sprintf(
                "[pass %d, %ds elapsed] monthly credits:%d failed:%d | maturity matured:%d waiting:%d failed:%d — %d record(s) still due\n",
                $pass, time() - $start,
                $lastMonthly['credits_made'] ?? 0, $lastMonthly['failed'] ?? 0,
                $lastMaturity['matured'] ?? 0, $lastMaturity['waiting_on_monthly'] ?? 0, $lastMaturity['failed'] ?? 0,
                $due
            ));

            if ($due === 0) break;
            if (time() - $start + $pollSeconds < $maxSeconds) sleep($pollSeconds);
        } while (time() - $start < $maxSeconds);

        echo json_encode([
            'status' => $due === 0 ? 'success' : 'timeout',
            'message' => $due === 0 ? 'No records due' : "Timed out after {$maxSeconds}s — run again to continue.",
            'passes' => $pass, 'elapsed_seconds' => time() - $start,
            'last_monthly' => $lastMonthly, 'last_maturity' => $lastMaturity,
            'ran_at' => date('Y-m-d H:i:s'),
        ]) . PHP_EOL;
    }

    public function test()
    {
        echo json_encode(['status' => true, 'message' => 'ROI Distribution operational', 'records_due_now' => $this->_dueCount(), 'now' => date('Y-m-d H:i:s')]);
    }
}
