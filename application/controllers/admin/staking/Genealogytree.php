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
        $this->load->model('staking/Stakingmatching_model', 'MB');
        $this->load->model('staking/Ceilingwallet_model', 'CW');
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

        $rows = $this->BinaryModel->getDownlineMembers($rootId, $depth + 1);
        $tree = $this->_buildTree($rows, $rootId, $depth);

        // Parent of the CURRENT root, so the view can offer an "Up one level"
        // button without a second round-trip — null at the true top of the tree.
        $parent = null;
        $pl = $this->db->select('parent_id')->get_where('binary_placement', ['user_id' => $rootId])->row_array();
        if ($pl && !empty($pl['parent_id'])) {
            $pu = $this->db->select('id, username, referral_id')->get_where('users', ['id' => (int)$pl['parent_id']])->row_array();
            if ($pu) $parent = ['id' => (int)$pu['id'], 'name' => $pu['username'], 'uid' => $pu['referral_id'] ?: ('#' . $pu['id'])];
        }

        echo json_encode(['status' => true, 'data' => $tree, 'parent' => $parent], JSON_UNESCAPED_SLASHES);
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
    protected function _buildTree($rows, $rootId, $maxDepth)
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
            $node += $this->_carryAndMatchingStats($id);
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
     * The REAL matching engine's view of one user: their own binary_carry
     * (reducible, what payMatching() actually reads) and ceiling/eligibility.
     * Deliberately duplicated from Genealogycontroller::getMatchingStats()
     * (same reasoning as the class docblock) plus binary_carry, which that
     * method doesn't need (the member page never shows raw carry).
     */
    protected function _carryAndMatchingStats($id)
    {
        $carry = $this->db->get_where('binary_carry', ['user_id' => $id])->row_array();
        $left = (float)($carry['left_carry'] ?? 0);
        $right = (float)($carry['right_carry'] ?? 0);

        $ceiling = $this->MB->userCeiling($id);
        $paid = $this->MB->matchingPaidToDate($id);
        $held = (float)($this->CW->balance($id)['held_balance'] ?? 0);
        $ownStake = (float)($this->db->select_sum('stake_amount')->where('user_id', $id)
                                      ->where('status', 'active')->get('user_stakes')->row()->stake_amount ?? 0);

        return [
            'left_carry' => round($left, 4),
            'right_carry' => round($right, 4),
            'potential_match' => round(min($left, $right), 4),
            'own_stake_amount' => round($ownStake, 4),
            'ceiling_amount' => round($ceiling, 4),
            'ceiling_paid' => round($paid, 4),
            'ceiling_remaining' => round(max(0.0, $ceiling - $paid), 4),
            'ceiling_wallet_held' => round($held, 4),
            'matching_eligible' => $ceiling > 0,
        ];
    }
}
