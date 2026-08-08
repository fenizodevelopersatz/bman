<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin ▸ Staking ▸ Genealogy Tree — an admin-facing interactive binary tree
 * for ANY member (not just self-or-downline), purpose-built for verifying the
 * binary matching engine: unlike the member-facing user/genealogy tree (which
 * shows user_wallets.exchange_balance subtree sums — a display figure
 * disconnected from the matching engine, see docs/17), every node here shows
 * the REAL binary_carry.left_carry/right_carry the engine actually matches
 * against, plus each member's own ceiling/eligibility snapshot.
 *
 * Deliberately a fresh, small build rather than a clone of Genealogycontroller
 * — that page's view is a large, fully self-contained HTML/CSS design system
 * of its own (not the KTUI admin Layout this panel otherwise uses), and the
 * data this admin screen needs (binary_carry directly) is simpler than that
 * page's subtree-BV-summation approach, not a superset of it.
 *
 * A separate, older "View Tree" already exists (admin/member/Membermanagement
 * ::genealogy / ::getTreeData, route user-genealogy/{id}) using a third-party
 * Balkan FamilyTree widget — confirmed broken (mid/pid key mismatch + a
 * formatted "Left ( $1,234 )" position string breaks its `=== "Left"`
 * checks), likely rendering as a flat pile of disconnected, uncolored cards.
 * Left untouched; this is an unrelated, additional screen.
 */
class Genealogytree extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper(['url']);
        $this->load->library('session');
        $this->load->model('Admin_model');
        $this->load->model('member/BinaryModel');
        // The LIVE level engine is the single source of truth for every
        // matching figure on this map. Stakingmatching_model (carry, SUM
        // ceiling, lifetime paid) is the retired engine and is no longer read
        // here — it disagreed with what actually pays.
        $this->load->model('staking/Binarylevelmatching_model', 'BLM');
        $this->load->model('Staking_model');
    }

    private function _requireAdmin()
    {
        if (!$this->session->userdata('admin_logged_in')) redirect('admin/login');
        $user = $this->Admin_model->get_user($this->session->userdata('admin_userid'));
        if ($user && $user->admin_roll == '1') {
            $perm = json_decode($user->permission_pages, true);
            if (empty($perm['staking_management']) && empty($perm['member_management'])) {
                $this->session->set_flashdata('error', 'Access Denied: You do not have permission.');
                redirect('admin');
            }
        }
    }

    private function _requireAdminAjax()
    {
        if (!$this->session->userdata('admin_logged_in')) {
            echo json_encode(['status' => false, 'message' => 'Not authorized']);
            exit;
        }
    }

    /** Tree page. ?uid=<user id> picks who to root the view at (defaults to the lowest real user id — usually the admin/root account). */
    public function index()
    {
        $this->_requireAdmin();
        $id = (int)$this->input->get('uid');
        if ($id <= 0) {
            $first = $this->db->select('id')->order_by('id', 'ASC')->limit(1)->get('users')->row();
            $id = (int)($first->id ?? 0);
        }
        $u = $this->db->select('id, username, referral_id, status')->get_where('users', ['id' => $id])->row();

        $data['title'] = 'Genealogy Tree';
        $data['start_id'] = $id;
        $data['start_name'] = $u->username ?? ('User #' . $id);
        $data['start_uid'] = $u->referral_id ?? ('#' . $id);
        $this->load->view('admin/staking/genealogy_tree', $data);
    }

    /** Directory search for the "switch member" box. */
    public function search_users()
    {
        header('Content-Type: application/json');
        $this->_requireAdminAjax();
        $q = trim((string)$this->input->get('q'));
        if ($q === '') { echo json_encode(['status' => true, 'data' => []]); exit; }
        $this->db->select('id, username, referral_id')->from('users')
                 ->group_start()->like('username', $q)->or_like('referral_id', $q);
        if (ctype_digit($q)) $this->db->or_where('id', (int)$q);
        $this->db->group_end()->order_by('id', 'ASC')->limit(20);
        echo json_encode(['status' => true, 'data' => $this->db->get()->result_array()]);
        exit;
    }

    /** Tree data — admin may root at ANY user, no downline-ownership restriction. */
    public function tree_json()
    {
        header('Content-Type: application/json');
        $this->_requireAdminAjax();

        $depth = (int)$this->input->get('depth');
        $depth = min(8, max(1, $depth ?: 6)); // shallower default than the member page — every node here does 4 extra queries

        $rootId = (int)$this->input->get('root_id');
        if ($rootId <= 0) { echo json_encode(['status' => false, 'message' => 'root_id required']); exit; }
        if (!$this->db->get_where('users', ['id' => $rootId])->row()) {
            echo json_encode(['status' => false, 'message' => 'User not found']); exit;
        }

        // level=0 (or absent) means "each member's own current level"; a
        // positive value pins every node to that level so the whole map can be
        // read at Level 1, 2, 3… Cumulative 1..N volumes come from the engine.
        $levelSel = max(0, (int)$this->input->get('level'));

        $rows = $this->BinaryModel->getDownlineMembers($rootId, $depth + 1);
        $tree = $this->_buildTree($rows, $rootId, $depth, $levelSel);

        // Parent of the CURRENT root, so the view can offer an "Up one level"
        // button without a second round-trip — null at the true top of the tree.
        $parent = null;
        $pl = $this->db->select('parent_id')->get_where('binary_placement', ['user_id' => $rootId])->row_array();
        if ($pl && !empty($pl['parent_id'])) {
            $pu = $this->db->select('id, username, referral_id')->get_where('users', ['id' => (int)$pl['parent_id']])->row_array();
            if ($pu) $parent = ['id' => (int)$pu['id'], 'name' => $pu['username'], 'uid' => $pu['referral_id'] ?: ('#' . $pu['id'])];
        }

        echo json_encode([
            'status'  => true,
            'data'    => $tree,
            'parent'  => $parent,
            'level'   => $levelSel,
            'summary' => $this->_levelSummary($rootId, $levelSel),
        ], JSON_UNESCAPED_SLASHES);
        exit;
    }

    /** Member Details refresh — same shape Genealogycontroller::member_json returns for the matching-stats keys. */
    public function member_json($id)
    {
        header('Content-Type: application/json');
        $this->_requireAdminAjax();
        $id = (int)$id;
        $u = $this->db->select('id, username, referral_id, status, register_date')->get_where('users', ['id' => $id])->row();
        if (!$u) { echo json_encode(['status' => false, 'message' => 'Member not found']); exit; }

        echo json_encode([
            'status' => true,
            'data' => array_merge([
                'id' => (int)$u->id, 'name' => $u->username, 'uid' => $u->referral_id ?? ('#' . $id),
                'status' => ((int)$u->status === 1 ? 'ACTIVE' : 'INACTIVE'),
                'join_date' => !empty($u->register_date) ? date('Y-m-d', strtotime($u->register_date)) : '—',
            ], $this->_carryAndMatchingStats($id)),
        ], JSON_UNESCAPED_SLASHES);
        exit;
    }

    /* ------------------------------- tree building ------------------------------- */

    /* protected (not private): Genealogytreetest calls these directly to
     * verify the tree-building + enrichment logic without needing a real
     * admin session (both are pure reads, safe to exercise against real
     * users). */
    protected function _buildTree($rows, $rootId, $maxDepth, $levelSel = 0)
    {
        $map = [];
        foreach (($rows ?? []) as $r) {
            $id = (int)($r['id'] ?? 0);
            if ($id <= 0) continue;
            $mid = isset($r['mid']) ? (int)$r['mid'] : 0;
            $posRaw = (string)($r['position'] ?? '');
            $map[$id] = [
                'id' => $id,
                'uid' => null, // filled below from a single batched query (getDownlineMembers doesn't return referral_id)
                'name' => $r['name'] ?? ('User ' . $id),
                'status' => 'ACTIVE',
                'join_date' => !empty($r['register_date']) ? $r['register_date'] : '—',
                'mid' => $mid,
                'position' => (stripos($posRaw, 'right') !== false) ? 'RIGHT' : 'LEFT',
                'left' => null, 'right' => null,
            ];
        }
        if (!isset($map[$rootId])) return null;

        // Batch-fill uid/status/matching-stats for every node in one pass (avoids
        // re-querying users per node on top of the per-node matching-stats queries).
        $ids = array_keys($map);
        if ($ids) {
            $users = $this->db->select('id, referral_id, status')->where_in('id', $ids)->get('users')->result_array();
            foreach ($users as $u) {
                $uid = (int)$u['id'];
                if (isset($map[$uid])) {
                    $map[$uid]['uid'] = $u['referral_id'] ?: ('#' . $uid);
                    $map[$uid]['status'] = ((int)$u['status'] === 1) ? 'ACTIVE' : 'INACTIVE';
                }
            }
        }
        foreach ($map as $id => &$node) {
            $node += $this->_carryAndMatchingStats($id, $levelSel);
        }
        unset($node);

        foreach ($map as $id => &$node) {
            $pid = (int)$node['mid'];
            if ($pid > 0 && isset($map[$pid])) {
                if ($node['position'] === 'RIGHT') $map[$pid]['right'] = &$node;
                else $map[$pid]['left'] = &$node;
            }
        }
        unset($node);

        return $this->_prune($map[$rootId], 1, $maxDepth);
    }

    private function _prune($node, $level, $max)
    {
        if (!$node) return null;
        if ($level >= $max) {
            $node['left_has_more'] = !empty($node['left']['id'] ?? null);
            $node['right_has_more'] = !empty($node['right']['id'] ?? null);
            $node['left'] = null; $node['right'] = null;
            return $node;
        }
        $node['left'] = $this->_prune($node['left'] ?? null, $level + 1, $max);
        $node['right'] = $this->_prune($node['right'] ?? null, $level + 1, $max);
        return $node;
    }

    /**
     * One node's complete binary matching picture, entirely from the LIVE
     * level engine — never a formula of this controller's own.
     *
     * $levelSel: a specific level to inspect, or 0 for "the member's own
     * current level" (their next unpaid one). Volumes are CUMULATIVE levels
     * 1..N per leg, exactly as the engine computes them.
     *
     * Historical levels are read from staking_matching_payouts (what actually
     * happened); the current/pending level is a read-only projection via
     * projectLevel() — the same method _payLevel() uses, so the map can never
     * show a number the engine would not pay. Nothing here writes.
     */
    protected function _carryAndMatchingStats($id, $levelSel = 0)
    {
        $id = (int)$id;

        // Lock Wallet — active AND not-yet-matured principal — the platform's
        // single definition (Staking_model::lockWalletBalance()). Kept
        // deliberately distinct from lifetime purchases below: matured stake
        // must never count toward matching volume.
        $lockWallet = (float)$this->Staking_model->lockWalletBalance($id);
        $purchased  = (float)($this->db->select_sum('stake_amount', 's')->where('user_id', $id)
                        ->get('user_stakes')->row()->s ?? 0);

        $ceil    = $this->BLM->sponsorCeiling($id);
        $nextLvl = (int)$this->BLM->nextLevel($id);
        $level   = $levelSel > 0 ? (int)$levelSel : $nextLvl;

        $legs     = $this->BLM->legVolumesByDepth($id);
        $vol      = $legs ? $this->BLM->cumulativeVolume($legs, $level) : ['left' => 0.0, 'right' => 0.0];
        $complete = $legs ? $this->BLM->levelComplete($legs, $level) : false;

        // Was this level already paid? If so the historical row wins — a
        // completed level must be reported as it was paid, never restated from
        // today's tree (volumes and ceilings both move).
        $paidRow = $this->db->where('user_id', $id)->where('level', $level)
                            ->get('staking_matching_payouts')->row_array();

        if ($paidRow) {
            $userPaid = (float)$paidRow['earning_amount'] + (float)$paidRow['staking_amount'];
            $stats = [
                'level_status'   => 'COMPLETED',
                'level_reason'   => 'Paid ' . $paidRow['created_at'],
                'left_volume'    => round((float)$paidRow['left_before'], 4),
                'right_volume'   => round((float)$paidRow['right_before'], 4),
                'matched_volume' => round((float)$paidRow['matched_volume'], 4),
                'raw_bonus'      => round((float)$paidRow['raw_bonus'], 4),
                'ceiling_used'   => round((float)$paidRow['ceiling_applied'], 4),
                'user_payout'    => round($userPaid, 4),
                'earning_amount' => round((float)$paidRow['earning_amount'], 4),
                'staking_amount' => round((float)$paidRow['staking_amount'], 4),
                'admin_overflow' => round((float)$paidRow['admin_overflow'], 4),
                'payout_id'      => (int)$paidRow['id'],
                'run_ref'        => $paidRow['run_ref'],
                'completed_at'   => $paidRow['created_at'],
                'historical'     => true,
            ];
        } else {
            $p = $this->BLM->projectLevel($id, $vol);
            if ($p['config_error'])      { $status = 'CONFIG_ERROR'; $reason = $ceil['detail']; }
            elseif (!$complete)          { $status = 'PENDING';      $reason = ($vol['left'] <= 0 || $vol['right'] <= 0)
                                                                        ? 'Both legs need eligible Lock Wallet volume at this depth'
                                                                        : 'Level not complete yet'; }
            elseif ($ceil['status'] === 'no_stake') { $status = 'NOT_ELIGIBLE'; $reason = 'Sponsor holds no eligible staking package'; }
            else                         { $status = 'PENDING';      $reason = 'Due — awaiting the next cron run'; }

            $stats = [
                'level_status'   => $status,
                'level_reason'   => $reason,
                'left_volume'    => round((float)$vol['left'], 4),
                'right_volume'   => round((float)$vol['right'], 4),
                'matched_volume' => round((float)$p['matched'], 4),
                'raw_bonus'      => round((float)$p['raw'], 4),
                'ceiling_used'   => round((float)$p['ceiling'], 4),
                'user_payout'    => round((float)$p['user'], 4),
                'earning_amount' => round((float)$p['earning'], 4),
                'staking_amount' => round((float)$p['staking'], 4),
                'admin_overflow' => round((float)$p['admin'], 4),
                'payout_id'      => null,
                'run_ref'        => null,
                'completed_at'   => null,
                'historical'     => false,
            ];
        }

        // Most recent payout of ANY level — "Last Matching Bonus" on the card.
        $last = $this->db->where('user_id', $id)->where('level IS NOT NULL', null, false)
                         ->order_by('level', 'DESC')->limit(1)
                         ->get('staking_matching_payouts')->row_array();

        return $stats + [
            'lock_wallet'        => round($lockWallet, 4),
            'purchased_total'    => round($purchased, 4),
            'own_stake_amount'   => round($lockWallet, 4), // legacy key kept for the existing view/test
            'highest_stake'      => round((float)$ceil['stake_amount'], 4),
            'ceiling_amount'     => round((float)$ceil['ceiling'], 4),
            'ceiling_status'     => $ceil['status'],
            'ceiling_detail'     => $ceil['detail'],
            'matching_eligible'  => (bool)$ceil['eligible'],
            'current_level'      => $nextLvl,
            'shown_level'        => $level,
            'level_complete'     => (bool)$complete,
            'potential_match'    => round(min((float)$vol['left'], (float)$vol['right']), 4),
            'last_bonus'         => $last ? round((float)$last['earning_amount'] + (float)$last['staking_amount'], 4) : 0.0,
            'last_bonus_level'   => $last ? (int)$last['level'] : null,
            'node_state'         => $this->_nodeState($ceil, $stats, $vol),
        ];
    }

    /**
     * One badge per node, in priority order — the worst/most actionable state
     * wins, so a config error is never hidden behind a green "eligible".
     */
    private function _nodeState($ceil, $stats, $vol)
    {
        if ($stats['level_status'] === 'CONFIG_ERROR')                 return 'CONFIG_ERROR';   // red
        if ($stats['level_status'] === 'COMPLETED')                    return 'COMPLETED';      // blue
        if ($ceil['status'] === 'no_stake')                            return 'NEEDS_STAKE';    // yellow
        if ((float)$vol['left'] <= 0 && (float)$vol['right'] <= 0)     return 'NO_VOLUME';      // gray
        if ($stats['level_status'] === 'PENDING')                      return 'PENDING';        // yellow
        return 'ELIGIBLE';                                                                      // green
    }

    /**
     * The level summary panel: the selected level for the ROOT member, using
     * the same numbers their node card shows (one calculation, two placements).
     */
    protected function _levelSummary($rootId, $levelSel = 0)
    {
        $s = $this->_carryAndMatchingStats($rootId, $levelSel);
        $u = $this->db->select('username, referral_id')->get_where('users', ['id' => (int)$rootId])->row_array();
        $bs = $this->db->get_where('staking_bonus_settings', ['id' => 1])->row_array();

        return $s + [
            'user_id'       => (int)$rootId,
            'username'      => $u['username'] ?? ('#' . (int)$rootId),
            'referral_id'   => $u['referral_id'] ?? '',
            'total_percent' => $total = ($bs ? (float)$bs['matching_total_percent']   : 10.0),
            'earn_percent'  => $earn  = ($bs ? (float)$bs['matching_earning_percent'] : 8.0),
            'stk_percent'   => $stk   = ($bs ? (float)$bs['matching_staking_percent'] : 2.0),
            // Share OF THE BONUS (8/10 -> 80%), so the panel can label the
            // split without doing arithmetic in the browser.
            'earn_share_pct' => $total > 0 ? round($earn / $total * 100, 2) : 0,
            'stk_share_pct'  => $total > 0 ? round($stk  / $total * 100, 2) : 0,
        ];
    }

    /** Full level-by-level history for one member — drives the detail drawer. */
    public function member_levels_json($id)
    {
        header('Content-Type: application/json');
        $this->_requireAdminAjax();
        $id = (int)$id;

        $u = $this->db->select('id, username, referral_id, status')->get_where('users', ['id' => $id])->row_array();
        if (!$u) { echo json_encode(['status' => false, 'message' => 'Member not found']); exit; }

        $ceil    = $this->BLM->sponsorCeiling($id);
        $nextLvl = (int)$this->BLM->nextLevel($id);

        // Completed levels, exactly as paid.
        $rows = $this->db->select('id, level, left_before, right_before, matched_volume, total_percent, '
                                . 'raw_bonus, ceiling_applied, earning_amount, staking_amount, admin_overflow, '
                                . 'sponsor_eligible, run_ref, created_at')
                         ->where('user_id', $id)->where('level IS NOT NULL', null, false)
                         ->order_by('level', 'ASC')->get('staking_matching_payouts')->result_array();

        $levels = [];
        foreach ($rows as $r) {
            $levels[] = [
                'level'          => (int)$r['level'],
                'left_volume'    => (float)$r['left_before'],
                'right_volume'   => (float)$r['right_before'],
                'matched_volume' => (float)$r['matched_volume'],
                'total_percent'  => (float)$r['total_percent'],
                'raw_bonus'      => (float)$r['raw_bonus'],
                'ceiling_used'   => (float)$r['ceiling_applied'],
                'user_payout'    => (float)$r['earning_amount'] + (float)$r['staking_amount'],
                'earning_amount' => (float)$r['earning_amount'],
                'staking_amount' => (float)$r['staking_amount'],
                'admin_overflow' => (float)$r['admin_overflow'],
                'eligible'       => (int)$r['sponsor_eligible'] === 1,
                'status'         => 'COMPLETED',
                'completed_at'   => $r['created_at'],
                'run_ref'        => $r['run_ref'],
                'payout_id'      => (int)$r['id'],
            ];
        }

        // Plus the current (unpaid) level as a projection, clearly marked.
        $cur = $this->_carryAndMatchingStats($id, $nextLvl);
        if (!$cur['historical']) {
            $levels[] = [
                'level'          => $nextLvl,
                'left_volume'    => $cur['left_volume'],
                'right_volume'   => $cur['right_volume'],
                'matched_volume' => $cur['matched_volume'],
                'total_percent'  => null,
                'raw_bonus'      => $cur['raw_bonus'],
                'ceiling_used'   => $cur['ceiling_used'],
                'user_payout'    => $cur['user_payout'],
                'earning_amount' => $cur['earning_amount'],
                'staking_amount' => $cur['staking_amount'],
                'admin_overflow' => $cur['admin_overflow'],
                'eligible'       => $cur['matching_eligible'],
                'status'         => $cur['level_status'],
                'reason'         => $cur['level_reason'],
                'completed_at'   => null,
                'run_ref'        => null,
                'payout_id'      => null,
            ];
        }

        echo json_encode([
            'status' => true,
            'member' => [
                'id' => $id, 'name' => $u['username'], 'uid' => $u['referral_id'] ?: ('#' . $id),
                'active' => ((int)$u['status'] === 1),
                'lock_wallet'    => round((float)$this->Staking_model->lockWalletBalance($id), 4),
                'purchased_total'=> round((float)($this->db->select_sum('stake_amount', 's')->where('user_id', $id)
                                        ->get('user_stakes')->row()->s ?? 0), 4),
                'highest_stake'  => round((float)$ceil['stake_amount'], 4),
                'ceiling'        => round((float)$ceil['ceiling'], 4),
                'ceiling_status' => $ceil['status'],
                'ceiling_detail' => $ceil['detail'],
                'current_level'  => $nextLvl,
            ],
            'levels' => $levels,
        ], JSON_UNESCAPED_SLASHES);
        exit;
    }
}
