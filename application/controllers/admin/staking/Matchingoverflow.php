<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin ▸ Binary Matching ▸ Admin Overflow (Excess BMAN).
 *
 * Every BMAN the level engine calculated but did not pay to the sponsor —
 * either the excess above their Group Incentive Ceiling, or the whole bonus
 * when they held no eligible staking package. Read-only: this screen reports
 * money that has already moved, and moves none of its own.
 *
 * CSV export deliberately streams the same rows the table shows (same model
 * call, same filters) rather than re-querying with its own SQL, so the export
 * can never disagree with what the admin is looking at.
 */
class Matchingoverflow extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper(['url']);
        $this->load->library('session');
        $this->load->model('Admin_model');
        $this->load->model('staking/Matchingoverflow_model', 'OF');
    }

    private function _requireAdmin()
    {
        if (!$this->session->userdata('admin_logged_in')) redirect('aaddmmiinn/login');
        $user = $this->Admin_model->get_user($this->session->userdata('admin_userid'));
        if ($user && $user->admin_roll == '1') {
            $perm = json_decode($user->permission_pages, true);
            // See Matchinghistory::_requireAdmin() — 'finance_management' is a
            // phantom permission no admin screen can grant, so it locked this
            // page out entirely. Same gate as the sibling Payout Queue.
            if (empty($perm['staking_management']) && empty($perm['wallet_management'])) {
                $this->session->set_flashdata('error', 'Access Denied: You do not have permission.');
                redirect('admin');
            }
        }
    }

    private function _filters()
    {
        $reason = $this->input->get('reason', true);
        return [
            'reason' => in_array($reason, ['over_ceiling', 'forfeited'], true) ? $reason : null,
            'q'      => trim((string)$this->input->get('q', true)),
            'from'   => trim((string)$this->input->get('from', true)),
            'to'     => trim((string)$this->input->get('to', true)),
            'limit'  => 300,
        ];
    }

    public function index()
    {
        $this->_requireAdmin();
        $f = $this->_filters();

        $this->load->view('admin/staking/matching_overflow', [
            'title'          => 'Binary Matching — Admin Overflow',
            'summary'        => $this->OF->summary(),
            'reconciliation' => $this->OF->reconciliation(),
            'custody'        => $this->OF->custodyNote(),
            'rows'           => $this->OF->ledger($f),
            'top'            => $this->OF->topSponsors(10),
            'wallet_ledger'  => $this->OF->walletLedger(50),
            'filters'        => $f,
        ]);
    }

    /** CSV of the CURRENT filter selection. */
    public function export()
    {
        $this->_requireAdmin();
        $f = $this->_filters();
        $f['limit'] = 5000;
        $rows = $this->OF->ledger($f);

        $name = 'binary-matching-admin-overflow-' . date('Ymd-His') . '.csv';
        $this->output->set_content_type('text/csv')
                     ->set_header('Content-Disposition: attachment; filename="' . $name . '"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['Date', 'Sponsor', 'Referral ID', 'Level', 'Left Volume', 'Right Volume',
                       'Matched Volume', 'Rate %', 'Raw Bonus', 'Member Paid', 'Ceiling Applied',
                       'Admin Overflow', 'Reason', 'Package', 'Run Ref']);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['created_at'],
                $r['username'] ?: ('#' . $r['user_id']),
                $r['referral_id'],
                $r['level'] === null ? 'legacy' : ('L' . $r['level']),
                $r['left_before'], $r['right_before'], $r['matched_volume'], $r['total_percent'],
                $r['raw_bonus'],
                (float)$r['earning_amount'] + (float)$r['staking_amount'],
                $r['ceiling_applied'], $r['admin_overflow'],
                (int)$r['sponsor_eligible'] === 0 ? 'No eligible staking package' : 'Over ceiling',
                $r['package_name'] ?: '', $r['run_ref'],
            ]);
        }
        fclose($out);
    }
}
