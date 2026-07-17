<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin ▸ Compensation ▸ Rank Management
 * ----------------------------------------------------------------------------
 * The read/operate side of the rank system. Rank *configuration* stays where it
 * already lives (admin/staking/Ranks for definitions + the qualification
 * matrix, admin/staking/Rankpower for cycle settings) — this controller adds
 * the pages that had no home:
 *
 *   history()      Rank History      — every promotion ever, filterable
 *   rewards()      Rank Rewards      — issuance + retry/fulfil
 *   certificates() Rank Certificates — issued certificates
 *   certificate()  printable certificate (print-to-PDF)
 *   power()        Rank Power        — per-member power for a cycle
 *   reports()      Rank Reports      — the six reports + CSV/Excel/PDF export
 *   member()       one member's rank drawer (JSON)
 *   run_cron()     fire either cron by hand
 *
 * No SQL here — everything goes through the staking/Rank* models.
 */
class Rankmanagement extends CI_Controller
{
    private $admin_id;

    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper(['url','security','download']);
        $this->load->model('Admin_model');
        $this->load->model('Staking_model', 'staking');
        $this->load->model('staking/Rankachievement_model', 'engine');
        $this->load->model('staking/Rankreward_model', 'rewards');
        $this->load->model('staking/Rankpower_model', 'power');
        $this->load->model('staking/Rankaudit_model', 'audit');
        $this->load->model('staking/Rankreport_model', 'reports');
        $this->load->model('staking/Rankcron_model', 'rankcron');

        if (!$this->session->userdata('admin_logged_in')) {
            redirect('admin/login');
        }
        $this->admin_id = (int)$this->session->userdata('admin_userid');

        $user = $this->Admin_model->get_user($this->admin_id);
        if ($user && $user->admin_roll == '1') {
            $permissions = json_decode($user->permission_pages, true);
            // Accept the staking key OR the legacy rank-management key.
            if (empty($permissions['staking_management']) && empty($permissions['rank_management'])) {
                $this->session->set_flashdata('error', 'Access Denied: You do not have permission.');
                redirect('admin');
            }
        }
    }

    private function _json($data = [], $code = 200)
    {
        $this->output->set_status_header($code)
                     ->set_content_type('application/json')
                     ->set_output(json_encode($data));
    }

    /** Page number → [limit, offset]. */
    private function _page($per = 50)
    {
        $p = max(1, (int)$this->input->get('page'));
        return [$per, ($p - 1) * $per, $p];
    }

    private function _filters(array $keys)
    {
        $out = [];
        foreach ($keys as $k) {
            $v = $this->input->get($k, true);
            if ($v !== null && $v !== '') $out[$k] = $v;
        }
        return $out;
    }

    /* ============================ RANK HISTORY ========================== */

    public function history()
    {
        list($limit, $offset, $page) = $this->_page(50);
        $filters = $this->_filters(['user_id','rank_id','source','plan','from','to','q']);

        $data = [
            'title'      => 'Rank History',
            'card_tilte' => 'Every rank promotion, newest first',
            'rows'       => $this->engine->history($filters, $limit, $offset),
            'total'      => $this->engine->historyCount($filters),
            'ranks'      => $this->staking->ranks(),
            'filters'    => $filters,
            'page'       => $page,
            'per_page'   => $limit,
        ];
        $this->load->view('admin/staking/rank_history', $data);
    }

    /* ============================ RANK REWARDS ========================== */

    public function rewards()
    {
        list($limit, $offset, $page) = $this->_page(50);
        $filters = $this->_filters(['status','type','rank_id','user_id','from','to']);

        $data = [
            'title'      => 'Rank Rewards',
            'card_tilte' => 'Reward issuance — paid once per member per rank',
            'rows'       => $this->rewards->rewards($filters, $limit, $offset),
            'total'      => $this->rewards->rewardsCount($filters),
            'ranks'      => $this->staking->ranks(),
            'filters'    => $filters,
            'page'       => $page,
            'per_page'   => $limit,
            'headline'   => $this->reports->headline(),
        ];
        $this->load->view('admin/staking/rank_rewards', $data);
    }

    /** Retry every failed/pending cash reward (e.g. after a treasury top-up). */
    public function retry_rewards()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $done = $this->rewards->retryFailed(200);
        $this->audit->log('reward_paid', [
            'changed_by' => $this->admin_id,
            'note' => 'Admin retried failed rewards — ' . $done . ' settled.',
        ]);
        return $this->_json(['status' => 'success',
                             'message' => $done . ' reward(s) settled.']);
    }

    /** Mark a non-cash reward fulfilled. */
    public function fulfil($id)
    {
        if (!$this->input->is_ajax_request()) show_404();
        list($ok, $msg) = $this->rewards->markFulfilled(
            (int)$id, $this->admin_id, $this->input->post('note', true)
        );
        return $this->_json(['status' => $ok ? 'success' : 'error', 'message' => $msg], $ok ? 200 : 422);
    }

    /* ========================= RANK CERTIFICATES ======================== */

    public function certificates()
    {
        list($limit, $offset, $page) = $this->_page(50);
        $filters = $this->_filters(['user_id','rank_id','q']);

        $data = [
            'title'      => 'Rank Certificates',
            'card_tilte' => 'Issued certificates — one per member per rank',
            'rows'       => $this->rewards->certificates($filters, $limit, $offset),
            'total'      => $this->rewards->certificatesCount($filters),
            'ranks'      => $this->staking->ranks(),
            'filters'    => $filters,
            'page'       => $page,
            'per_page'   => $limit,
        ];
        $this->load->view('admin/staking/rank_certificates', $data);
    }

    /**
     * Printable certificate. Rendered as a standalone print-ready page rather
     * than a generated PDF binary — the app has no PDF library, and the browser
     * print dialog produces the same artefact with no new dependency. Save the
     * resulting file path into rank_certificates.certificate_pdf if you later
     * archive them.
     */
    public function certificate($cert_no)
    {
        $row = $this->rewards->certificate($cert_no);
        if (!$row) show_404();
        $this->load->view('admin/staking/rank_certificate_print', ['c' => $row]);
    }

    /* ============================ RANK POWER =========================== */

    /** Per-member power for one cycle (defaults to the open cycle). */
    public function power()
    {
        list($limit, $offset, $page) = $this->_page(50);
        $filters = $this->_filters(['rank_id','qualified','user_id']);

        $cycle_id = (int)$this->input->get('cycle_id');
        $cycle    = $cycle_id ? $this->power->cycle($cycle_id) : $this->power->currentCycle();

        $data = [
            'title'      => 'Rank Power',
            'card_tilte' => 'Dynamic power rank — resets every cycle, drives Group Incentive',
            'cycle'      => $cycle,
            'cycles'     => $this->power->cycles(50),
            'settings'   => $this->power->settings(),
            'rows'       => $cycle ? $this->power->cyclePowers($cycle['id'], $filters, $limit, $offset) : [],
            'total'      => $cycle ? $this->power->cyclePowersCount($cycle['id'], $filters) : 0,
            'ranks'      => $this->staking->ranks(),
            'filters'    => $filters,
            'page'       => $page,
            'per_page'   => $limit,
            'cron'       => $this->rankcron->states(),
        ];
        $this->load->view('admin/staking/rank_power_users', $data);
    }

    /* ============================== AUDIT ============================== */

    public function audit()
    {
        list($limit, $offset, $page) = $this->_page(100);
        $filters = $this->_filters(['event','user_id','from','to']);

        $data = [
            'title'      => 'Rank Audit Log',
            'card_tilte' => 'Every rank action — promotions, rewards, certificates, config changes',
            'rows'       => $this->audit->feed($filters, $limit, $offset),
            'total'      => $this->audit->feedCount($filters),
            'events'     => $this->audit->events(),
            'filters'    => $filters,
            'page'       => $page,
            'per_page'   => $limit,
        ];
        $this->load->view('admin/staking/rank_audit', $data);
    }

    /* ============================= REPORTS ============================= */

    public function reports()
    {
        $key       = $this->input->get('report', true) ?: 'distribution';
        $catalogue = $this->reports->catalogue();
        if (!isset($catalogue[$key])) $key = 'distribution';

        $filters = $this->_filters(['from','to','rank_id','cycle_id','qualified']);

        $data = [
            'title'      => 'Rank Reports',
            'card_tilte' => $catalogue[$key]['label'],
            'catalogue'  => $catalogue,
            'report'     => $key,
            'meta'       => $catalogue[$key],
            'rows'       => $this->reports->run($key, $filters, 500),
            'headline'   => $this->reports->headline(),
            'ranks'      => $this->staking->ranks(),
            'cycles'     => $this->power->cycles(50),
            'filters'    => $filters,
        ];
        $this->load->view('admin/staking/rank_reports', $data);
    }

    /**
     * Export any report as CSV / Excel / PDF.
     *   csv   — real CSV
     *   excel — an Excel-readable HTML table sent as .xls (no PhpSpreadsheet
     *           in this project; Excel opens this natively)
     *   pdf   — a print-ready HTML page; use the browser print dialog to save
     *           as PDF (no PDF library is vendored here)
     */
    public function export($key, $format = 'csv')
    {
        $catalogue = $this->reports->catalogue();
        if (!isset($catalogue[$key])) show_404();

        $filters = $this->_filters(['from','to','rank_id','cycle_id','qualified']);
        $rows    = $this->reports->run($key, $filters, 10000);
        $cols    = $catalogue[$key]['columns'];
        $label   = $catalogue[$key]['label'];
        $stamp   = date('Y-m-d_His');

        if ($format === 'csv') {
            $out = fopen('php://temp', 'r+');
            fputcsv($out, array_values($cols));
            foreach ($rows as $r) {
                $line = [];
                foreach (array_keys($cols) as $c) $line[] = isset($r[$c]) ? $r[$c] : '';
                fputcsv($out, $line);
            }
            rewind($out);
            $csv = stream_get_contents($out);
            fclose($out);
            return force_download($this->_slug($label) . '_' . $stamp . '.csv', $csv);
        }

        if ($format === 'excel') {
            $html = $this->load->view('admin/staking/rank_report_export',
                        ['rows' => $rows, 'cols' => $cols, 'label' => $label, 'print' => false], true);
            return force_download($this->_slug($label) . '_' . $stamp . '.xls', $html);
        }

        if ($format === 'pdf') {
            // Print-ready page: browser print → Save as PDF.
            return $this->load->view('admin/staking/rank_report_export',
                        ['rows' => $rows, 'cols' => $cols, 'label' => $label, 'print' => true]);
        }

        show_404();
    }

    private function _slug($s)
    {
        return strtolower(preg_replace('/[^a-z0-9]+/i', '_', trim($s)));
    }

    /* ====================== member drawer (JSON) ======================= */

    public function member($user_id)
    {
        if (!$this->input->is_ajax_request()) show_404();
        $user_id = (int)$user_id;
        return $this->_json([
            'status'      => 'success',
            'rank'        => $this->engine->currentRank($user_id),
            'progress'    => $this->engine->nextRankProgress($user_id),
            'history'     => $this->engine->userHistory($user_id),
            'rewards'     => $this->rewards->rewards(['user_id' => $user_id], 50),
            'certificates'=> $this->rewards->certificates(['user_id' => $user_id], 50),
            'power'       => $this->power->userPower($user_id),
        ]);
    }

    /**
     * Re-evaluate one member on demand. Cannot demote — the engine only ever
     * promotes — so this is safe to hand to support staff.
     */
    public function recalculate($user_id)
    {
        if (!$this->input->is_ajax_request()) show_404();
        $this->engine->reset();
        $r = $this->engine->processUserRank((int)$user_id, [
            'source' => 'admin', 'admin_id' => $this->admin_id,
        ]);
        return $this->_json(['status' => $r['status'], 'message' => $r['message'], 'data' => $r],
                            $r['status'] === 'success' ? 200 : 422);
    }

    /* ============================ run crons ============================ */

    /** Fire either cron by hand. POST job=achievement|power|power_cycle */
    public function run_cron()
    {
        if (!$this->input->is_ajax_request()) show_404();
        $job = $this->input->post('job', true);

        switch ($job) {
            case 'achievement':
                $res = $this->rankcron->runAchievement(true);
                break;
            case 'power':
                $res = $this->rankcron->runPower(true);
                break;
            case 'power_cycle':
                $res = $this->rankcron->runPower(false, true);
                break;
            default:
                return $this->_json(['status' => 'error', 'message' => 'Unknown job.'], 422);
        }
        return $this->_json(['status' => $res['status'], 'message' => $res['message'], 'data' => $res]);
    }

    /** Release a stuck run lock. */
    public function release_lock($job)
    {
        if (!$this->input->is_ajax_request()) show_404();
        if (!in_array($job, ['rank_achievement','rank_power'], true)) {
            return $this->_json(['status' => 'error', 'message' => 'Unknown job.'], 422);
        }
        list($ok, $msg) = $this->rankcron->releaseLock($job, $this->admin_id);
        return $this->_json(['status' => 'success', 'message' => $msg]);
    }
}
