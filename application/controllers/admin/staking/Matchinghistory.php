<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin ▸ Binary Matching ▸ Distribution History.
 *
 * A STRICTLY READ-ONLY historical ledger: one row per completed
 * (user_id, level), reported exactly as it was paid. It never triggers
 * matching, never credits a wallet, never enqueues or broadcasts anything, and
 * never recomputes a completed payout from the genealogy tree as it stands
 * today — every money figure comes from the frozen columns on
 * staking_matching_payouts (see Matchinghistory_model's docblock).
 *
 * The old "Run Matching Now" button was REMOVED from this page: it drove the
 * engine, which contradicts the read-only guarantee this screen now makes.
 * Manual runs live in Cron Lab ▸ Binary Matching Payout, which is where the
 * other cron triggers already are.
 *
 * One deliberate exception to "historical only", kept clearly separated at the
 * bottom of the page: Blocked Levels. Those levels have NO row anywhere by
 * design (a config error writes nothing so the level stays payable), so they
 * can only be surfaced by a live read. It is still read-only — it calls the
 * engine's public inspection methods, never processSponsor().
 */
class Matchinghistory extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper(['url']);
        $this->load->library('session');
        $this->load->model('Admin_model');
        $this->load->model('staking/Matchinghistory_model', 'MH');
        $this->load->model('staking/Matchingqueue_model', 'MQ');
    }

    private function _requireAdmin()
    {
        if (!$this->session->userdata('admin_logged_in')) redirect('admin/login');
        $user = $this->Admin_model->get_user($this->session->userdata('admin_userid'));
        if ($user && $user->admin_roll == '1') {
            $perm = json_decode($user->permission_pages, true);
            // 'wallet_management', not 'finance_management': the latter is a
            // phantom key that exists nowhere else in the app and cannot be
            // granted by any admin screen, so gating on it made this page
            // unreachable for every admin_roll='1' account. Matches the
            // sibling Binary Matching ▸ Payout Queue check.
            if (empty($perm['staking_management']) && empty($perm['wallet_management'])) {
                $this->session->set_flashdata('error', 'Access Denied: You do not have permission.');
                redirect('admin');
            }
        }
    }

    private function _json($d, $c = 200)
    {
        $this->output->set_status_header($c)->set_content_type('application/json')->set_output(json_encode($d));
    }

    /** Same convention as Onchaintx::_explorer() / Withdraw.php. */
    private function _explorer()
    {
        $ts = $this->db->select('explorer_url')->get_where('token_settings', ['status' => 1])->row_array();
        return rtrim($ts['explorer_url'] ?? 'https://bscscan.com', '/');
    }

    private function _filters()
    {
        $status = $this->input->get('status', true);
        $chain  = $this->input->get('chain', true);
        return [
            'q'      => trim((string)$this->input->get('q', true)),
            'level'  => (int)$this->input->get('level', true) ?: null,
            'status' => in_array($status, ['paid', 'overflow', 'forfeited', 'pending', 'config'], true) ? $status : '',
            'chain'  => in_array($chain, ['queued', 'sent', 'confirmed', 'failed'], true) ? $chain : '',
            'from'   => trim((string)$this->input->get('from', true)),
            'to'     => trim((string)$this->input->get('to', true)),
            'limit'  => 300,
        ];
    }

    public function index()
    {
        $this->_requireAdmin();
        $f = $this->_filters();

        // 'config' is not a historical status — a config-blocked level writes
        // no row at all. Selecting it shows the live Blocked Levels diagnostic
        // instead of an empty (and misleading) history table.
        $configOnly = ($f['status'] === 'config');
        $f2 = $f; if ($configOnly) $f2['status'] = '';

        $this->load->view('admin/staking/matching_history', [
            'title'        => 'Binary Matching — Distribution History',
            'summary'      => $this->MH->summary($f2),
            'rows'         => $configOnly ? [] : $this->MH->rows($f2),
            'levels'       => $this->MH->levels(),
            'filters'      => $f,
            'config_only'  => $configOnly,
            'runs'         => $this->MQ->recent(25),
            'blocked'      => $this->_blockedLevels(),
            'explorer_url' => $this->_explorer(),
        ]);
    }

    /** Full detail for one historical level (AJAX, drives the drawer). */
    public function detail($id)
    {
        $this->_requireAdmin();
        if (!$this->input->is_ajax_request()) show_404();
        $row = $this->MH->detail((int)$id);
        if (!$row) return $this->_json(['status' => 'error', 'message' => 'Record not found.'], 404);
        return $this->_json(['status' => 'success', 'record' => $row, 'explorer_url' => $this->_explorer()]);
    }

    /**
     * Levels that are COMPLETE and owed, but which the engine refused to pay
     * because the Group Incentive Ceiling configuration could not be resolved.
     *
     * These deliberately have NO database row — skip-and-retry writes nothing,
     * which is exactly what keeps the level payable once an admin fixes the
     * config. That makes them invisible everywhere else, so this recomputes
     * them live. Self-clearing: fix the package config and the row disappears,
     * because the condition itself is gone.
     *
     * Read-only — it calls the engine's public inspection methods only, never
     * processSponsor(), so opening this page cannot pay anybody.
     */
    private function _blockedLevels($limit = 100)
    {
        $this->load->model('staking/Binarylevelmatching_model', 'BLM');

        $sponsors = $this->db->query(
            "SELECT DISTINCT bp.parent_id AS uid FROM binary_placement bp
              WHERE bp.parent_id IS NOT NULL AND bp.parent_id > 0 ORDER BY bp.parent_id ASC"
        )->result_array();

        $out = [];
        foreach ($sponsors as $s) {
            $uid = (int)$s['uid'];
            $ceil = $this->BLM->sponsorCeiling($uid);
            if ($ceil['eligible'] || $ceil['status'] === 'no_stake') continue; // fine, or a real forfeit

            $legs  = $this->BLM->legVolumesByDepth($uid);
            $level = $this->BLM->nextLevel($uid);
            if (!$legs || !$this->BLM->levelComplete($legs, $level)) continue; // not due yet

            $vol = $this->BLM->cumulativeVolume($legs, $level);
            $matched = min($vol['left'], $vol['right']);
            $u = $this->db->select('username, referral_id')->get_where('users', ['id' => $uid])->row_array();

            $out[] = [
                'user_id'      => $uid,
                'username'     => $u['username'] ?? ('#' . $uid),
                'referral_id'  => $u['referral_id'] ?? '',
                'level'        => $level,
                'status'       => $ceil['status'],
                'detail'       => $ceil['detail'],
                'package_id'   => $ceil['package_id'],
                'stake_amount' => $ceil['stake_amount'],
                'left_volume'  => round($vol['left'], 4),
                'right_volume' => round($vol['right'], 4),
                'matched'      => round($matched, 4),
                'unpaid_bonus' => round($matched * 0.10, 4),
            ];
            if (count($out) >= $limit) break;
        }
        return $out;
    }
}
