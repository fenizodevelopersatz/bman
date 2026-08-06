<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Genealogycontroller extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');
    
        if ($this->session->userdata('user_logged_in') && $this->session->userdata('user_login')) {
            $this->lang->load('common', $this->session->userdata('language'));
        } else {
            redirect('user/in');
        }

        $this->load->model('member/Users_model');
        $this->load->model('member/Mlm_model');
        $this->load->model('member/BinaryModel');
        $this->load->model('staking/Stakingmatching_model', 'MB');
        $this->load->model('staking/Ceilingwallet_model', 'CW');

        $language = $this->session->userdata('site_lang') ?? 'english';
        $this->config->set_item('language', $language);
        $this->lang->load('common', $language);
        $this->load->model('Chat_model');
    }
    /*
    |--------------------------------------------------------------------------
    | Index Page
    |--------------------------------------------------------------------------
    */
    public function index()
    {
        $id = (int) $this->session->userdata('user_userid');
        $this->data['user_id'] = $id;

        $this->data['title'] = "Your Genealogy";
        $this->data['card_tilte'] = "User Genealogy";

        $user = $this->db->query("SELECT id, username, referral_id, status, register_date FROM users WHERE id = ?", [$id])->row();
        $this->data['first_letter'] = !empty($user->username) ? substr($user->username, 0, 1) : 'U';

        $this->data['currency_info'] = currency_info();
        $this->data['token_info'] = token_info();

        $this->data['wallet_balance'] = site_wallet_balance($id);
        $this->data['token_wallet_balance'] = site_token_balance($id);

        // ✅ FIX: these were undefined before
        $this->data['lending_profit'] = 0;
        $this->data['direct_commission'] = 0;
        // ✅ Overall (Lifetime) Left/Right BV for this user (receiver)
        $bvRow = $this->db->select("
            COALESCE(SUM(CASE WHEN leg='left'  THEN amount ELSE 0 END),0) AS left_bv,
            COALESCE(SUM(CASE WHEN leg='right' THEN amount ELSE 0 END),0) AS right_bv
            ", false)
            ->from('history')
            ->where('user_id', (int) $id)
            ->where('type', 'bv_volume')
            ->where('status', '1')
            ->get()->row();

        $leftBV = (float) ($bvRow->left_bv ?? 0);
        $rightBV = (float) ($bvRow->right_bv ?? 0);


        $cfg = $this->db->get_where('commission_config', ['id' => 1])->row();
        $mode = !empty($cfg->carry_forward_mode) ? strtolower($cfg->carry_forward_mode) : 'lifetime';

        switch ($mode) {
            case 'daily':
                $scope_key = date('Y-m-d');
                break;

            case 'weekly':
                $scope_key = date('o-\\WW'); // 2026-W08
                break;

            case 'monthly':
                $scope_key = date('Y-m');
                break;

            default:
                $scope_key = 'lifetime';
                break;
        }
        $cfRow = $this->db->select('left_carry,right_carry')
            ->from('binary_carry_forward')
            ->where('user_id', (int) $id)
            ->where('scope_key', $scope_key)
            ->limit(1)
            ->get()->row();

        $leftCF = (float) ($cfRow->left_carry ?? 0);
        $rightCF = (float) ($cfRow->right_carry ?? 0);

        $pairs_lifetime = (int) $this->db->select('COALESCE(SUM(pairs_count),0) AS c', false)
            ->from('history')
            ->where('user_id', $id)
            ->where('type', 'pair_commission')
            ->where('status', '1')
            ->get()->row()->c;

        // ✅ Summary KPIs for top cards
        $binary_info = $this->BinaryModel->calculateLegInvestments($id);
        $this->data['user'] = (object) [
            'uid' => $user->referral_id ?? ('UID-' . $id),
            'name' => $user->username ?? 'User',
            'rank' => '—', // if you have rank column, map it here
            // 'left_bv' => (float) ($binary_info['left_leg_investment'] ?? 0),
            // 'right_bv' => (float) ($binary_info['right_leg_investment'] ?? 0),
            'left_bv' => (float) ($leftBV ?? 0),
            'right_bv' => (float) ($rightBV ?? 0),
            'left_cf' => (float) ($leftCF ?? 0),
            'right_cf' => (float) ($rightCF ?? 0),
            'pairs' => $pairs_lifetime ?? 0,
            // Exchange Wallet (BMAN) totals of each leg's downline team.
            'left_exchange' => (float) ($binary_info['left_exchange_wallet'] ?? 0),
            'right_exchange' => (float) ($binary_info['right_exchange_wallet'] ?? 0),
        ];

        // ✅ Do NOT inject TREE from PHP now. We'll load via AJAX.
        $this->load->view('user/member/view-genealogy', $this->data);
    }

    public function tree_json()
    {
        header('Content-Type: application/json');

        $sessionId = (int) $this->session->userdata('user_userid');
        $depth = (int) $this->input->get('depth');
        if ($depth <= 0)
            $depth = 10; // default limit: 10 levels of downline per page
        $depth = min(10, max(1, $depth));

        // Pagination: the binary tree can go arbitrarily deep, but a full level-by-level
        // fetch grows 2^depth, so instead of raising the depth cap we let the frontend
        // "drill into" any node at the current frontier and re-root the view there.
        // root_id must be the caller's own downline (or themselves) — never an arbitrary user.
        $rootId = (int) $this->input->get('root_id');
        if ($rootId <= 0) {
            $rootId = $sessionId;
        } elseif ($rootId !== $sessionId && !$this->BinaryModel->isDescendantOf($rootId, $sessionId)) {
            echo json_encode(['status' => false, 'message' => 'Not authorized for this member'], JSON_UNESCAPED_SLASHES);
            exit;
        }

        // Fetch one level deeper than requested purely to detect whether a frontier
        // node still has further downline, so the UI can show a "load more" affordance.
        $rows = $this->BinaryModel->getDownlineMembers($rootId, $depth + 1);

        $tree = $this->buildBinaryTreeFromFlat($rows, $rootId, $depth, false); // false = DO NOT fill empty nodes

        echo json_encode(['status' => true, 'data' => $tree], JSON_UNESCAPED_SLASHES);
        exit;
    }

    private function buildBinaryTreeFromFlat($rows, $rootId, $maxDepth = 3, $fillEmpty = false)
    {
        $map = [];

        foreach (($rows ?? []) as $r) {
            // ✅ SUPPORT array or object
            $id = (int) (is_array($r) ? ($r['id'] ?? 0) : ($r->id ?? 0));
            if ($id <= 0)
                continue;

            $mid = (int) (is_array($r) ? ($r['mid'] ?? 0) : ($r->mid ?? 0));

            $posRaw = is_array($r) ? ($r['position'] ?? '') : ($r->position ?? '');
            $posRaw = strip_tags((string) $posRaw);

            $name = is_array($r) ? ($r['name'] ?? ($r['username'] ?? 'User ' . $id)) : ($r->name ?? ($r->username ?? 'User ' . $id));
            $email = is_array($r) ? ($r['email'] ?? '') : ($r->email ?? '');
            $join = is_array($r) ? ($r['register_date'] ?? '—') : ($r->register_date ?? '—');

            // Same profile_img -> image -> (client-side default) priority as
            // user_profile_image(), inlined here since the columns are already
            // in $r and this runs once per row of a whole-tree fetch.
            $profileImg = is_array($r) ? ($r['profile_img'] ?? '') : ($r->profile_img ?? '');
            $legacyImg = is_array($r) ? ($r['image'] ?? '') : ($r->image ?? '');
            $avatar = !empty($profileImg)
                ? base_url('assets/images/' . $profileImg)
                : (!empty($legacyImg) ? base_url('assets/images/' . $legacyImg) : '');

            $map[$id] = [
                'id' => $id,
                'uid' => is_array($r) ? ($r['referral_id'] ?? ('UID-' . $id)) : ($r->referral_id ?? ('UID-' . $id)),
                'name' => $name,
                'email' => $email,
                'rank' => is_array($r) ? ($r['rank'] ?? '—') : ($r->rank ?? '—'),
                'avatar' => $avatar,
                'status' => is_array($r) ? ($r['status'] ?? 'ACTIVE') : ($r->status ?? 'ACTIVE'),
                'join_date' => !empty($join) ? date('Y-m-d', strtotime($join)) : '—',
                'left_bv' => (float) (is_array($r) ? ($r['left_bv'] ?? 0) : ($r->left_bv ?? 0)),
                'right_bv' => (float) (is_array($r) ? ($r['right_bv'] ?? 0) : ($r->right_bv ?? 0)),
                'exchange' => (float) (is_array($r) ? ($r['exchange'] ?? 0) : ($r->exchange ?? 0)),
                'mid' => $mid,
                'position' => (stripos($posRaw, 'right') !== false) ? 'RIGHT' : 'LEFT',
                'left' => null,
                'right' => null,
            ];
        }

        // ensure root exists
        if (!isset($map[$rootId])) {
            $u = $this->db->query("SELECT id, username, referral_id, register_date, profile_img, image FROM users WHERE id=?", [$rootId])->row();
            $rootAvatar = !empty($u->profile_img)
                ? base_url('assets/images/' . $u->profile_img)
                : (!empty($u->image) ? base_url('assets/images/' . $u->image) : '');
            $map[$rootId] = [
                'id' => $rootId,
                'uid' => $u->referral_id ?? ('UID-' . $rootId),
                'name' => $u->username ?? 'User',
                'rank' => '—',
                'avatar' => $rootAvatar,
                'status' => 'ACTIVE',
                'join_date' => !empty($u->register_date) ? date('Y-m-d', strtotime($u->register_date)) : '—',
                'left_bv' => 0,
                'right_bv' => 0,
                'exchange' => 0,
                'mid' => 0,
                'position' => '',
                'left' => null,
                'right' => null
            ];
        }

        // link children
        // NOTE: must link by reference. $rows is inserted in parent-before-child order
        // (fetchDownline appends a member, then immediately recurses into that member's
        // own children), so a plain by-value foreach copies a node into its parent's
        // 'left'/'right' slot BEFORE that node's own children get attached to it —
        // silently dropping every grandchild (and everything deeper), no matter the
        // requested depth. Binding $node (and the parent slot) by reference keeps them
        // pointing at the same live $map entry, so later mutations stay visible.
        foreach ($map as $id => &$node) {
            $pid = (int) $node['mid'];
            if ($pid > 0 && isset($map[$pid])) {
                if ($node['position'] === 'RIGHT')
                    $map[$pid]['right'] = &$node;
                else
                    $map[$pid]['left'] = &$node;
            }
        }
        unset($node);

        $root = $map[$rootId];
        return $this->pruneAndFill($root, 1, $maxDepth, $fillEmpty);
    }

    // private function pruneAndFill($node, $level, $max, $fillEmpty = false)
    // {
    //     if ($level > $max)
    //         return null;
    //     if (empty($node))
    //         return $fillEmpty ? [
    //             'id' => 0,
    //             'uid' => '—',
    //             'name' => 'Empty Position',
    //             'rank' => '—',
    //             'avatar' => '',
    //             'status' => 'EMPTY',
    //             'join_date' => '—',
    //             'left_bv' => 0,
    //             'right_bv' => 0,
    //             'left' => null,
    //             'right' => null
    //         ] : null;

    //     if ($level < $max) {
    //         $node['left'] = $this->pruneAndFill($node['left'], $level + 1, $max, $fillEmpty);
    //         $node['right'] = $this->pruneAndFill($node['right'], $level + 1, $max, $fillEmpty);

    //         // ✅ IMPORTANT:
    //         // If you DON'T want empty nodes, keep them as null (don’t inject placeholders)
    //         if ($fillEmpty) {
    //             if (!$node['left'])
    //                 $node['left'] = ['id' => 0, 'uid' => '—', 'name' => 'Empty Position', 'rank' => '—', 'avatar' => '', 'status' => 'EMPTY', 'join_date' => '—', 'left_bv' => 0, 'right_bv' => 0, 'left' => null, 'right' => null];
    //             if (!$node['right'])
    //                 $node['right'] = ['id' => 0, 'uid' => '—', 'name' => 'Empty Position', 'rank' => '—', 'avatar' => '', 'status' => 'EMPTY', 'join_date' => '—', 'left_bv' => 0, 'right_bv' => 0, 'left' => null, 'right' => null];
    //         }
    //     } else {
    //         $node['left'] = null;
    //         $node['right'] = null;
    //     }

    //     return $node;
    // }



    /**
     * Binary matching eligibility snapshot for one node — ceiling (own active
     * stake tier), how much of it is already paid/held, and whether they
     * currently qualify to RECEIVE matching income at all (must have staked
     * themselves; see Stakingmatching_model::payMatching()'s eligibility gate).
     */
    private function getMatchingStats(int $id): array
    {
        if ($id <= 0) {
            return [
                'ceiling_amount' => 0, 'ceiling_paid' => 0, 'ceiling_remaining' => 0,
                'ceiling_wallet_held' => 0, 'own_stake_amount' => 0, 'matching_eligible' => false,
            ];
        }

        $ceiling = $this->MB->userCeiling($id);
        $paid = $this->MB->matchingPaidToDate($id);
        $held = (float) ($this->CW->balance($id)['held_balance'] ?? 0);
        $ownStake = (float) ($this->db->select_sum('stake_amount')->where('user_id', $id)
                                       ->where('status', 'active')->get('user_stakes')->row()->stake_amount ?? 0);

        return [
            'ceiling_amount'      => round($ceiling, 4),
            'ceiling_paid'        => round($paid, 4),
            'ceiling_remaining'   => round(max(0.0, $ceiling - $paid), 4),
            'ceiling_wallet_held' => round($held, 4),
            'own_stake_amount'    => round($ownStake, 4),
            'matching_eligible'   => $ceiling > 0,
        ];
    }

    private function getNodeStats(int $id): array
    {
        if ($id <= 0) {
            return array_merge([
                'rank' => '—',
                'left_bv' => 0,
                'right_bv' => 0,
                'left_cf' => 0,
                'right_cf' => 0,
                'pairs' => 0,
            ], $this->getMatchingStats(0));
        }

        // ---------------------------
        // 1) LEFT/RIGHT BV (history)
        // ---------------------------
        $bvRow = $this->db->select("
        COALESCE(SUM(CASE WHEN leg='left'  THEN amount ELSE 0 END),0) AS left_bv,
        COALESCE(SUM(CASE WHEN leg='right' THEN amount ELSE 0 END),0) AS right_bv
    ", false)
            ->from('history')
            ->where('user_id', (int) $id)
            ->where('type', 'bv_volume')
            ->where('status', '1')
            ->get()->row();

        $leftBV = (float) ($bvRow->left_bv ?? 0);
        $rightBV = (float) ($bvRow->right_bv ?? 0);

        // ---------------------------
        // 2) scope_key (commission_config)
        // ---------------------------
        $cfg = $this->db->get_where('commission_config', ['id' => 1])->row();
        $mode = !empty($cfg->carry_forward_mode) ? strtolower((string) $cfg->carry_forward_mode) : 'lifetime';

        // IMPORTANT: avoid nested ternary (PHP 7+ error)
        if ($mode === 'daily') {
            $scope_key = date('Y-m-d');
        } elseif ($mode === 'weekly') {
            $scope_key = date('o-\\WW'); // 2026-W08
        } elseif ($mode === 'monthly') {
            $scope_key = date('Y-m');
        } else {
            $scope_key = 'lifetime';
        }

        // ---------------------------
        // 3) Carry forward (binary_carry_forward)
        // ---------------------------
        $leftCF = 0.0;
        $rightCF = 0.0;

        if ($this->db->table_exists('binary_carry_forward')) {
            $cfRow = $this->db->select('left_carry,right_carry')
                ->from('binary_carry_forward')
                ->where('user_id', (int) $id)
                ->where('scope_key', $scope_key)
                ->limit(1)
                ->get()->row();

            $leftCF = (float) ($cfRow->left_carry ?? 0);
            $rightCF = (float) ($cfRow->right_carry ?? 0);
        }

        // ---------------------------
        // 4) Pairs lifetime (history)
        // ---------------------------
        $pairs_lifetime = (int) $this->db->select('COALESCE(SUM(pairs_count),0) AS c', false)
            ->from('history')
            ->where('user_id', (int) $id)
            ->where('type', 'pair_commission')
            ->where('status', '1')
            ->get()->row()->c;

        // ---------------------------
        // 5) Rank (users.rank_id -> rank_config.rank_name)
        // ---------------------------
        $rankName = '—';
        if ($this->db->field_exists('rank_id', 'users')) {
            $u = $this->db->select('rank_id')->from('users')->where('id', (int) $id)->get()->row();
            $rank_id = (int) ($u->rank_id ?? 0);
            if ($rank_id > 0) {
                $r = $this->db->select('rank_name')->from('rank_config')->where('id', $rank_id)->get()->row();
                if ($r && !empty($r->rank_name))
                    $rankName = (string) $r->rank_name;
            }
        }

        return array_merge([
            'rank' => $rankName,
            'left_bv' => $leftBV,
            'right_bv' => $rightBV,
            'left_cf' => $leftCF,
            'right_cf' => $rightCF,
            'pairs' => $pairs_lifetime,
        ], $this->getMatchingStats($id));
    }

    private function emptyNode(): array
    {
        return array_merge([
            'id' => 0,
            'uid' => '—',
            'name' => 'Empty Position',
            'rank' => '—',
            'avatar' => '',
            'status' => 'EMPTY',
            'join_date' => '—',
            'left_bv' => 0,
            'right_bv' => 0,
            'left_cf' => 0,
            'right_cf' => 0,
            'pairs' => 0,
            'left' => null,
            'right' => null
        ], $this->getMatchingStats(0));
    }

    /**
     * ✅ FULL pruneAndFill
     * - Keeps only up to $max levels
     * - If $fillEmpty=true, fills empty positions
     * - Also attaches BV/CF/PAIRS/RANK for every real node
     */
    private function pruneAndFill($node, int $level, int $max, bool $fillEmpty = false)
    {
        if ($level > $max)
            return null;

        // empty slot
        if (empty($node) || empty($node['id'])) {
            return $fillEmpty ? $this->emptyNode() : null;
        }

        // ✅ attach stats to this node
        $id = (int) $node['id'];
        $st = $this->getNodeStats($id);

        $node['rank'] = $st['rank'];
        $node['left_bv'] = (float) $st['left_bv'];
        $node['right_bv'] = (float) $st['right_bv'];
        $node['left_cf'] = (float) $st['left_cf'];
        $node['right_cf'] = (float) $st['right_cf'];
        $node['pairs'] = (int) $st['pairs'];
        // Binary matching eligibility (Stakingmatching_model's own ceiling +
        // Ceilingwallet_model's held balance) — surfaced so admins/members can
        // see who currently qualifies to receive matching income and how much
        // headroom they have left, without leaving the tree view.
        $node['ceiling_amount'] = (float) $st['ceiling_amount'];
        $node['ceiling_paid'] = (float) $st['ceiling_paid'];
        $node['ceiling_remaining'] = (float) $st['ceiling_remaining'];
        $node['ceiling_wallet_held'] = (float) $st['ceiling_wallet_held'];
        $node['own_stake_amount'] = (float) $st['own_stake_amount'];
        $node['matching_eligible'] = (bool) $st['matching_eligible'];

        // cut children at max level -- flag (not fetch) one level further so the UI
        // can offer "load more downline" pagination on this node instead of pretending
        // the tree simply ends here.
        if ($level >= $max) {
            $node['left_has_more'] = !empty($node['left']['id'] ?? null);
            $node['right_has_more'] = !empty($node['right']['id'] ?? null);
            $node['left'] = null;
            $node['right'] = null;
            return $node;
        }

        // recurse
        $node['left'] = $this->pruneAndFill($node['left'] ?? null, $level + 1, $max, $fillEmpty);
        $node['right'] = $this->pruneAndFill($node['right'] ?? null, $level + 1, $max, $fillEmpty);

        if ($fillEmpty) {
            if (!$node['left'])
                $node['left'] = $this->emptyNode();
            if (!$node['right'])
                $node['right'] = $this->emptyNode();
        }

        return $node;
    }


    // public function member_json($id)
    // {
    //     header('Content-Type: application/json');

    //     $id = (int) $id;
    //     if ($id <= 0) {
    //         echo json_encode(['status' => false, 'message' => 'Invalid member']);
    //         exit;
    //     }

    //     $u = $this->db->query("SELECT id, username, email, referral_id, register_date, status, sponser FROM users WHERE id=?", [$id])->row();
    //     if (!$u) {
    //         echo json_encode(['status' => false, 'message' => 'Member not found']);
    //         exit;
    //     }

    //     $sponser = null;
    //     if (!empty($u->sponser)) {
    //         $sponser = $this->db->query("SELECT email, referral_id FROM users WHERE id=?", [(int) $u->sponser])->row();
    //     }

    //     $binary_info = $this->BinaryModel->calculateLegInvestments($id);

    //     // ✅ FIX: filter sums by this user only
    //     $binary_site_currency = (float) $this->db->query("SELECT COALESCE(SUM(amount),0) AS s FROM history WHERE user_id=? AND type='binary_commission'", [$id])->row()->s;
    //     $binary_token_currency = (float) $this->db->query("SELECT COALESCE(SUM(token_amount),0) AS s FROM history WHERE user_id=? AND type='binary_commission'", [$id])->row()->s;
    //     $roi_site_currency = (float) $this->db->query("SELECT COALESCE(SUM(amount),0) AS s FROM history WHERE user_id=? AND type='profit'", [$id])->row()->s;
    //     $roi_token_currency = (float) $this->db->query("SELECT COALESCE(SUM(token_amount),0) AS s FROM history WHERE user_id=? AND type='profit'", [$id])->row()->s;
    //     $direct_site_currency = (float) $this->db->query("SELECT COALESCE(SUM(amount),0) AS s FROM history WHERE user_id=? AND type='direct_commission'", [$id])->row()->s;
    //     $direct_token_currency = (float) $this->db->query("SELECT COALESCE(SUM(token_amount),0) AS s FROM history WHERE user_id=? AND type='direct_commission'", [$id])->row()->s;

    //     echo json_encode([
    //         'status' => true,
    //         'data' => [
    //             'id' => (int) $u->id,
    //             'name' => $u->username,
    //             'uid' => $u->referral_id,
    //             'email' => $u->email,
    //             'status' => ($u->status == 1 ? 'ACTIVE' : 'INACTIVE'),
    //             'join_date' => !empty($u->register_date) ? date('Y-m-d', strtotime($u->register_date)) : '—',
    //             'sponser' => $sponser ? ($sponser->email . " ( " . $sponser->referral_id . " )") : '—',
    //             'left_bv' => (float) ($binary_info['left_leg_investment'] ?? 0),
    //             'right_bv' => (float) ($binary_info['right_leg_investment'] ?? 0),

    //             'earn_binary_site' => $binary_site_currency,
    //             'earn_binary_token' => $binary_token_currency,
    //             'earn_roi_site' => $roi_site_currency,
    //             'earn_roi_token' => $roi_token_currency,
    //             'earn_direct_site' => $direct_site_currency,
    //             'earn_direct_token' => $direct_token_currency,
    //         ]
    //     ], JSON_UNESCAPED_SLASHES);
    //     exit;
    // }



    public function member_json($id)
    {
        header('Content-Type: application/json');

        $id = (int) $id;
        if ($id <= 0) {
            echo json_encode(['status' => false, 'message' => 'Invalid member']);
            exit;
        }

        // Same authorization tree_json() already enforces — a member may only
        // look up themselves or someone in their own downline. This endpoint
        // previously had no such check at all (any logged-in user could pull
        // any other user's rank/BV/earnings by id); now that it also returns
        // ceiling/staking figures, closing that gap matters even more.
        $sessionId = (int) $this->session->userdata('user_userid');
        if ($id !== $sessionId && !$this->BinaryModel->isDescendantOf($id, $sessionId)) {
            echo json_encode(['status' => false, 'message' => 'Not authorized for this member']);
            exit;
        }

        // -----------------------------
        // User
        // -----------------------------
        $u = $this->db->query("
        SELECT id, username, email, referral_id, register_date, status, sponser, rank_id
        FROM users
        WHERE id=?
    ", [$id])->row();

        if (!$u) {
            echo json_encode(['status' => false, 'message' => 'Member not found']);
            exit;
        }

        // Sponsor info
        $sponser = null;
        if (!empty($u->sponser)) {
            $sponser = $this->db->query("SELECT email, referral_id FROM users WHERE id=?", [(int) $u->sponser])->row();
        }

        // -----------------------------
        // Rank name
        // -----------------------------
        $rankName = '—';
        $rank_id = (int) ($u->rank_id ?? 0);
        if ($rank_id > 0) {
            $r = $this->db->select('rank_name')->from('rank_config')->where('id', $rank_id)->get()->row();
            if ($r && !empty($r->rank_name))
                $rankName = (string) $r->rank_name;
        }

        // -----------------------------
        // ✅ OVERALL LEFT/RIGHT BV (lifetime)
        // history.user_id = receiver (this user)
        // history.type = bv_volume
        // history.leg = left/right
        // -----------------------------
        $bvRow = $this->db->select("
        COALESCE(SUM(CASE WHEN leg='left'  THEN amount ELSE 0 END),0) AS left_bv,
        COALESCE(SUM(CASE WHEN leg='right' THEN amount ELSE 0 END),0) AS right_bv
    ", false)
            ->from('history')
            ->where('user_id', (int) $id)
            ->where('type', 'bv_volume')
            ->where('status', '1')
            ->get()->row();

        $left_bv = (float) ($bvRow->left_bv ?? 0);
        $right_bv = (float) ($bvRow->right_bv ?? 0);

        // -----------------------------
        // ✅ Carry Forward (current scope)
        // -----------------------------
        $left_cf = 0.0;
        $right_cf = 0.0;

        if ($this->db->table_exists('binary_carry_forward')) {

            $cfg = $this->db->get_where('commission_config', ['id' => 1])->row();
            $mode = !empty($cfg->carry_forward_mode) ? strtolower((string) $cfg->carry_forward_mode) : 'lifetime';

            if ($mode === 'daily') {
                $scope_key = date('Y-m-d');
            } elseif ($mode === 'weekly') {
                $scope_key = date('o-\\WW'); // 2026-W08
            } elseif ($mode === 'monthly') {
                $scope_key = date('Y-m');
            } else {
                $scope_key = 'lifetime';
            }

            $cfRow = $this->db->select('left_carry,right_carry')
                ->from('binary_carry_forward')
                ->where('user_id', (int) $id)
                ->where('scope_key', $scope_key)
                ->limit(1)
                ->get()->row();

            $left_cf = (float) ($cfRow->left_carry ?? 0);
            $right_cf = (float) ($cfRow->right_carry ?? 0);
        }

        // -----------------------------
        // Earnings (keep your existing)
        // NOTE: If your binary payout is now "pair_commission", sum both
        // -----------------------------
        $binary_site_currency = (float) $this->db->query("
        SELECT COALESCE(SUM(amount),0) AS s
        FROM history
        WHERE user_id=? AND type IN('binary_commission','pair_commission')
    ", [$id])->row()->s;

        $binary_token_currency = (float) $this->db->query("
        SELECT COALESCE(SUM(token_amount),0) AS s
        FROM history
        WHERE user_id=? AND type IN('binary_commission','pair_commission')
    ", [$id])->row()->s;

        $roi_site_currency = (float) $this->db->query("
        SELECT COALESCE(SUM(amount),0) AS s
        FROM history
        WHERE user_id=? AND type='profit'
    ", [$id])->row()->s;

        $roi_token_currency = (float) $this->db->query("
        SELECT COALESCE(SUM(token_amount),0) AS s
        FROM history
        WHERE user_id=? AND type='profit'
    ", [$id])->row()->s;

        $direct_site_currency = (float) $this->db->query("
        SELECT COALESCE(SUM(amount),0) AS s
        FROM history
        WHERE user_id=? AND type='direct_commission'
    ", [$id])->row()->s;

        $direct_token_currency = (float) $this->db->query("
        SELECT COALESCE(SUM(token_amount),0) AS s
        FROM history
        WHERE user_id=? AND type='direct_commission'
    ", [$id])->row()->s;

        echo json_encode([
            'status' => true,
            'data' => [
                'id' => (int) $u->id,
                'name' => $u->username,
                'uid' => $u->referral_id,
                'email' => $u->email,
                'status' => ((int) $u->status === 1 ? 'ACTIVE' : 'INACTIVE'),
                'join_date' => !empty($u->register_date) ? date('Y-m-d', strtotime($u->register_date)) : '—',
                'sponser' => $sponser ? ($sponser->email . " ( " . $sponser->referral_id . " )") : '—',

                // ✅ NEW fields for genealogy cards
                'rank' => $rankName,
                'left_bv' => $left_bv,
                'right_bv' => $right_bv,
                'left_cf' => $left_cf,
                'right_cf' => $right_cf,

                // earnings
                'earn_binary_site' => $binary_site_currency,
                'earn_binary_token' => $binary_token_currency,
                'earn_roi_site' => $roi_site_currency,
                'earn_roi_token' => $roi_token_currency,
                'earn_direct_site' => $direct_site_currency,
                'earn_direct_token' => $direct_token_currency,

                // binary matching eligibility (ceiling + staking + Ceiling Wallet)
            ] + $this->getMatchingStats($id)
        ], JSON_UNESCAPED_SLASHES);

        exit;
    }


    /*
   |--------------------------------------------------------------------------
   | Rank reward Page
   |--------------------------------------------------------------------------
   */
    public function rankreward()
    {

        $id = $this->session->userdata('user_userid');
        $this->data['user_id'] = $id;

        $this->data['title'] = "Your Genealogy";
        $this->data['card_tilte'] = "User Genealogy";
        $this->data['user_id'] = $id;
        $user_name = $this->db->query("SELECT * FROM users where id = '" . $id . "' ")->row()->username;
        $this->data['first_letter'] = substr($user_name, 0, 1);

        $this->data['currency_info'] = currency_info();
        $this->data['token_info'] = token_info();

        $site_wallet_balance = site_wallet_balance($id);
        $token_wallet_balance = site_token_balance($id);

        $this->data['wallet_balance'] = $site_wallet_balance;
        $this->data['token_wallet_balance'] = $token_wallet_balance;

        $this->data['lending_profit'] = $lending_profit ?? 0;
        $this->data['direct_commission'] = $direct_commission ?? 0;

        $this->load->view('user/member/rank-reward', $this->data);
    }
    /*
    |--------------------------------------------------------------------------
    | Withdraw  Page
    |--------------------------------------------------------------------------
    */
    // public function withdraw()
    // {

    //     $id = $this->session->userdata('user_userid');
    //     $this->data['user_id'] = $id;

    //     $this->data['title'] = "Your Genealogy";
    //     $this->data['card_tilte'] = "User Genealogy";
    //     $this->data['user_id'] = $id;
    //     $user_name = $this->db->query("SELECT * FROM users where id = '" . $id . "' ")->row()->username;
    //     $this->data['first_letter'] = substr($user_name, 0, 1);

    //     $this->data['currency_info'] = currency_info();
    //     $this->data['token_info'] = token_info();

    //     $site_wallet_balance = site_wallet_balance($id);
    //     $token_wallet_balance = site_token_balance($id);

    //     $this->data['wallet_balance'] = $site_wallet_balance;
    //     $this->data['token_wallet_balance'] = $token_wallet_balance;

    //     $this->data['lending_profit'] = $lending_profit ?? 0;
    //     $this->data['direct_commission'] = $direct_commission ?? 0;
    //     $this->data['user'] = $this->db->query("SELECT * FROM users where id = '" . $id . "' ")->row();
    //     // SELECT * FROM `user_bank`
    //     $this->data['user_bank'] = $this->db->query("SELECT * FROM user_bank where user_id = '" . $id . "' ")->row();
    //     $this->load->view('user/member/withdraw', $this->data);
    // }

    public function withdraw()
    {
        $id = (int) $this->session->userdata('user_userid');
        if (!$id) {
            redirect('login');
            return;
        }

        // ===== Basic user data =====
        $user = $this->db->get_where('users', ['id' => $id])->row();
        if (!$user) {
            redirect('login');
            return;
        }

        $user_name = $user->username ?? '';
        $first_letter = $user_name ? substr($user_name, 0, 1) : 'F';

        // ===== Settings (from admin Withdraw Settings) =====
        $this->load->model('withdraw/Bmanwithdraw_model', 'bmanwithdraw');
        $withdraw_settings = $this->bmanwithdraw->settings();
        $withdraw_status = (int) ($withdraw_settings['withdraw_status'] ?? 0); // 1 enabled, 0 disabled
        $min_withdraw = (float) ($withdraw_settings['min_withdraw'] ?? 0);
        $max_withdraw = (float) ($withdraw_settings['max_withdraw'] ?? 0);
        $withdraw_fee = (float) ($withdraw_settings['withdraw_fee'] ?? 0);
        $withdraw_daily_limit = (float) ($withdraw_settings['withdraw_daily_limit'] ?? 0);
        $withdraw_monthly_limit = (float) ($withdraw_settings['withdraw_monthly_limit'] ?? 0);
        $withdraw_amount_type = (int) ($withdraw_settings['withdraw_amount_type'] ?? 0); // 1=USD etc
        $auto_withdraw = (int) ($withdraw_settings['auto_withdraw'] ?? 0);
        $withdraw_allowed_exchange = (int) ($withdraw_settings['withdraw_allowed_exchange'] ?? 1);

        // ===== Wallets =====
        // Withdrawal conversion is Exchange-only. Keep all BMAN wallet cards
        // visible, but the available payout balance must come from the
        // exchange withdrawable figure only.
        $available_amount_raw = $this->bmanwithdraw->available_balance($id);
        $available_amount = (float) $available_amount_raw;
        $token_balance = (float) site_token_balance($id);
        $wallet_breakdown = $this->bmanwithdraw->maturity_breakdown($id);
        // custodial_wallets doesn't exist in this schema — user_wallet is the
        // real, current wallet-address table (confirmed live elsewhere, e.g.
        // Swapengine_model::userWallet()). A user with no row here simply
        // hasn't had an on-chain address generated yet; leave the address blank
        // rather than querying a nonexistent table (was a hard 500 for any such user).
        $platform_wallet = $this->db->select('wallet_address')->where('user_id', $id)->get('user_wallet')->row();
        $platform_address = $platform_wallet->wallet_address ?? '';
        $bman_rate_raw = (string) (token_info()->currency_value ?? '0'); // BMAN per 1 USDT
        $bman_price_raw = Money::cmp($bman_rate_raw, '0') > 0 ? Money::div('1', $bman_rate_raw) : '0'; // USDT per 1 BMAN
        $bman_rate = (float) $bman_rate_raw;
        $bman_price = (float) $bman_price_raw;
        $processing_fee_usdt = $withdraw_fee;
        $available_usdt_raw = Money::floor6(Money::mul($available_amount_raw, $bman_price_raw));
        $min_bman_required = ($bman_price > 0 && $min_withdraw > 0) ? (float) Money::div((string) $min_withdraw, $bman_price_raw, 4) : 0;
        $open_request = $this->bmanwithdraw->open_request($id, null);
        $limit_usage = $this->bmanwithdraw->limit_usage($id);
        $maturity_rules = $this->bmanwithdraw->maturity_rules();
        $source_allowed = $withdraw_allowed_exchange === 1;
        $daily_limit_reached = $withdraw_daily_limit > 0 && Money::cmp((string) ($limit_usage['daily_usdt'] ?? 0), (string) $withdraw_daily_limit) >= 0;
        $monthly_limit_reached = $withdraw_monthly_limit > 0 && Money::cmp((string) ($limit_usage['monthly_usdt'] ?? 0), (string) $withdraw_monthly_limit) >= 0;

        // ===== KYC status (your code uses string 'approved' in UI) =====
        $kyc_ok = false;
        if (!empty($user->kyc_status)) {
            $kyc_ok = (strtolower((string) $user->kyc_status) === 'approved' || (string) $user->kyc_status === '1');
        }
        $active_ok = true;
        if (isset($user->status)) {
            $active_ok = in_array(strtolower((string) $user->status), ['active', '1', 'approved'], true);
        }

        // ===== Pending + Paid totals + history =====
        // Source of truth: bman_withdraw_requests — the table the withdraw form
        // on this page actually submits to (via user/bman-withdraw/request).
        $totals = $this->bmanwithdraw->user_totals($id);
        $pending_amount = $totals['pending'];
        $paid_total = $totals['paid'];
        $payouts = $this->bmanwithdraw->user_payout_history($id, 200);

        // ===== Eligibility rules (you can tighten these) =====
        $eligible = true;

        if ($withdraw_status !== 1)
            $eligible = false;
        if (!$source_allowed)
            $eligible = false;
        if (!empty($open_request))
            $eligible = false;
        if (!$kyc_ok)
            $eligible = false;
        if (Money::cmp($available_usdt_raw, (string) $min_withdraw) < 0)
            $eligible = false;
        if (!$active_ok)
            $eligible = false;
        if ($daily_limit_reached || $monthly_limit_reached)
            $eligible = false;

        // ===== Next payout time label (simple) =====
        // If you have a cron schedule table, we can compute exactly. For now show "Tonight 10:00 PM".
        $next_date_label = NEXT_PAYOUT_TIME;

        // ===== Build $payout object for the UI =====
        $payout = (object) [
            'next_date' => $next_date_label,
            'min_withdraw' => $min_withdraw,
            'processing_fee' => $processing_fee_usdt,
            'eligibility' => $eligible,
            'kyc' => $kyc_ok,
            'pending_amount' => $pending_amount,
            'available_amount' => $available_amount,
            'bman_price' => $bman_price,
            'min_bman_required' => $min_bman_required,
            'paid_total' => $paid_total,
            'max_withdraw' => $max_withdraw,
            'daily_limit' => $withdraw_daily_limit,
            'monthly_limit' => $withdraw_monthly_limit,
            'amount_type' => $withdraw_amount_type,
            'auto_withdraw' => $auto_withdraw,
            'source_allowed' => $source_allowed,
            'available_usdt' => (float) $available_usdt_raw,
            'daily_used' => (float) ($limit_usage['daily_usdt'] ?? 0),
            'monthly_used' => (float) ($limit_usage['monthly_usdt'] ?? 0),
            'has_open_request' => !empty($open_request),
            'open_request_status' => !empty($open_request['status']) ? $open_request['status'] : null,
        ];

        $withdraw_rules = [
            'enabled' => $withdraw_status === 1,
            'source_allowed' => $source_allowed,
            'source_label' => 'Exchange Wallet only',
            'min_usdt' => $min_withdraw,
            'min_bman' => $min_bman_required,
            'max_usdt' => $max_withdraw,
            'fee_usdt' => $processing_fee_usdt,
            'daily_limit' => $withdraw_daily_limit,
            'monthly_limit' => $withdraw_monthly_limit,
            'daily_used' => (float) ($limit_usage['daily_usdt'] ?? 0),
            'monthly_used' => (float) ($limit_usage['monthly_usdt'] ?? 0),
            'available_bman' => $available_amount,
            'available_usdt' => (float) $available_usdt_raw,
            'maturity_enabled' => !empty($maturity_rules['enabled']),
            'exchange_lock_days' => (int) ($maturity_rules['exchange'] ?? 0),
        ];

        // ===== Pass to view =====
        $this->data['title'] = "Payouts";
        $this->data['card_tilte'] = "Payouts";
        $this->data['user_id'] = $id;
        $this->data['first_letter'] = $first_letter;

        $this->data['currency_info'] = currency_info();
        $this->data['token_info'] = token_info();

        $this->data['user'] = $user;

        // ✅ UI variables your new page needs
        $this->data['payout'] = $payout;
        $this->data['payouts'] = $payouts;
        $this->data['open_request'] = $open_request;
        $this->data['withdraw_rules'] = $withdraw_rules;

        // if you still need these old ones
        $this->data['wallet_balance'] = $available_amount;
        $this->data['token_wallet_balance'] = $token_balance;
        $this->data['wallet_usdt'] = (float) ($wallet_breakdown['usdt'] ?? 0);
        $this->data['wallet_bman'] = [
            'exchange' => (float) ($wallet_breakdown['exchange_withdrawable'] ?? 0),
            'earning'  => (float) ($wallet_breakdown['earning_withdrawable'] ?? 0),
            'staking'  => (float) ($wallet_breakdown['staking_withdrawable'] ?? 0),
            'bonus'    => (float) ($wallet_breakdown['bonus_withdrawable'] ?? 0),
        ];
        $this->data['platform_address'] = $platform_address;
        $this->data['bman_price'] = $bman_price;
        $this->data['bman_rate'] = $bman_rate;
        $this->data['processing_fee_usdt'] = $processing_fee_usdt;
        $this->data['min_bman_required'] = $min_bman_required;
        $this->data['faqs'] = [];
        if ($this->db->table_exists('faqs') && $this->db->field_exists('page_key', 'faqs')) {
            $this->data['faqs'] = $this->db
                ->select('id, question, answer')
                ->from('faqs')
                ->where('status', 1)
                ->where('page_key', 'payouts')
                ->order_by('id', 'DESC')
                ->get()
                ->result();
        }

        $this->load->view('user/member/withdraw', $this->data);
    }

    /*
    |--------------------------------------------------------------------------
    | Withdraw  Page
    |--------------------------------------------------------------------------
    */
    public function wallet_transfer()
    {

        $id = $this->session->userdata('user_userid');
        $this->data['user_id'] = $id;

        $this->data['title'] = "Your Genealogy";
        $this->data['card_tilte'] = "User Genealogy";
        $this->data['user_id'] = $id;
        $user_name = $this->db->query("SELECT * FROM users where id = '" . $id . "' ")->row()->username;
        $this->data['first_letter'] = substr($user_name, 0, 1);

        $this->data['currency_info'] = currency_info();
        $this->data['token_info'] = token_info();

        $site_wallet_balance = site_wallet_balance($id);
        $token_wallet_balance = site_token_balance($id);

        $this->data['wallet_balance'] = $site_wallet_balance;
        $this->data['token_wallet_balance'] = $token_wallet_balance;

        $this->data['lending_profit'] = $lending_profit ?? 0;
        $this->data['direct_commission'] = $direct_commission ?? 0;

        $this->load->view('user/member/wallet_transfer', $this->data);
    }
    /*
  |--------------------------------------------------------------------------
  | Withdraw  Page
  |--------------------------------------------------------------------------
  */
    public function all_rank()
    {

        $id = $this->session->userdata('user_userid');
        $this->data['user_id'] = $id;

        $this->data['title'] = "Your Genealogy";
        $this->data['card_tilte'] = "User Genealogy";
        $this->data['user_id'] = $id;
        $user_name = $this->db->query("SELECT * FROM users where id = '" . $id . "' ")->row()->username;
        $this->data['first_letter'] = substr($user_name, 0, 1);

        $this->data['currency_info'] = currency_info();
        $this->data['token_info'] = token_info();

        $site_wallet_balance = site_wallet_balance($id);
        $token_wallet_balance = site_token_balance($id);

        $this->data['wallet_balance'] = $site_wallet_balance;
        $this->data['token_wallet_balance'] = $token_wallet_balance;

        $this->data['lending_profit'] = $lending_profit;
        $this->data['direct_commission'] = $direct_commission;

        $this->load->view('user/member/all_rank', $this->data);
    }
    /*
 |--------------------------------------------------------------------------
 | Withdraw  Page
 |--------------------------------------------------------------------------
 */
    // public function chat()
    // {

    //     $id = $this->session->userdata('user_userid');
    //     $this->data['user_id'] = $id;

    //     $this->data['title'] = "Your Genealogy";
    //     $this->data['card_tilte'] = "User Genealogy";
    //     $this->data['user_id'] = $id;
    //     $user_name = $this->db->query("SELECT * FROM users where id = '" . $id . "' ")->row()->username;
    //     $this->data['first_letter'] = substr($user_name, 0, 1);

    //     $this->data['currency_info'] = currency_info();
    //     $this->data['token_info'] = token_info();

    //     $site_wallet_balance = site_wallet_balance($id);
    //     $token_wallet_balance = site_token_balance($id);

    //     $this->data['wallet_balance'] = $site_wallet_balance;
    //     $this->data['token_wallet_balance'] = $token_wallet_balance;

    //     $this->data['lending_profit'] = $lending_profit ?? 0;
    //     $this->data['direct_commission'] = $direct_commission ?? 0;

    //     $this->load->view('user/member/chat', $this->data);
    // }

    /*
    |--------------------------------------------------------------------------
    | CHATTING  Controller
    |-------------------------------------------------------------------------- */
    public function chat()
    {
        $id = $this->session->userdata('user_userid');
        if (!$id) {
            redirect('user/in');
            return;
        }

        $this->load->model('Chat_model');

        $userRow = $this->db->query("SELECT username FROM users WHERE id = ?", [$id])->row();
        $user_name = $userRow ? $userRow->username : 'User';

        $this->data['user_id'] = $id;
        $this->data['username'] = $user_name;
        $this->data['first_letter'] = substr($user_name, 0, 1);

        $this->data['title'] = "Chat";
        $this->data['card_tilte'] = "Chat";

        $this->data['chat_fetch_url'] = base_url('user/chat/fetch');
        $this->data['chat_send_url'] = base_url('user/chat/send');
        $this->data['chat_recent_url'] = base_url('user/chat/recent');

        // The people this member is allowed to DM: their genealogy path
        // (upline ∪ downline). Same set the send/fetch authorisation enforces,
        // so the picker can never offer someone the server would reject.
        $this->data['team_members'] = $this->Chat_model->getPathChatMembers($id);

        $this->load->view('user/member/chat', $this->data);
    }


    // POST: user/chat/send
    public function chat_send()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
            return;
        }

        $id = $this->session->userdata('user_userid');
        if (!$id) {
            return $this->output->set_content_type('application/json')
                ->set_output(json_encode(['ok' => false, 'message' => 'Not logged in']));
        }

        $room = $this->input->post('room', true);
        $message = $this->input->post('message', true);
        $peerId = (int) $this->input->post('peer_id', true);

        $allowedRooms = ['world', 'team', 'personal'];
        if (!in_array($room, $allowedRooms, true))
            $room = 'personal';

        if ($room === 'personal' && $peerId <= 0) {
            return $this->output->set_content_type('application/json')
                ->set_output(json_encode(['ok' => false, 'message' => 'Peer user is required for personal chat']));
        }

        // AUTHORISATION — personal chat is limited to the member's own genealogy
        // path (upline ∪ downline). Without this, any logged-in member could POST
        // an arbitrary peer_id and DM a complete stranger.
        if ($room === 'personal' && !$this->Chat_model->canChatWith($id, $peerId)) {
            return $this->output->set_status_header(403)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'ok' => false,
                    'message' => 'You can only message members in your own team path.',
                ]));
        }

        $userRow = $this->db->query("SELECT username FROM users WHERE id = ?", [$id])->row();
        $username = $userRow ? $userRow->username : 'User';

        $message = trim((string) $message);
        if (mb_strlen($message) > 500)
            $message = mb_substr($message, 0, 500);

        // Optional file upload
        $fileData = null;
        if (!empty($_FILES['chat_file']['name'])) {

            if (ENABLE_SITE_UPLOAD_FUNCTION !== true) {
                $this->_dbg('genealogy_upload_images', 'Uploads disabled');
                // Must be JSON: a bare `return false` sent an empty body, so the
                // client's res.json() threw and the member saw a misleading
                // "Network error while uploading" instead of the real reason.
                return $this->output->set_content_type('application/json')
                    ->set_output(json_encode([
                        'ok' => false,
                        'message' => 'File uploads are currently disabled.',
                    ]));
            }

            $uploadPath = FCPATH . 'uploads/chat/';
            if (!is_dir($uploadPath))
                @mkdir($uploadPath, 0777, true);

            $config['upload_path'] = $uploadPath;
            $config['allowed_types'] = 'jpg|jpeg|png|gif|webp|pdf|doc|docx|xls|xlsx|txt|zip';
            $config['max_size'] = 5120; // 5MB
            $config['encrypt_name'] = true;

            $this->load->library('upload', $config);

            if (!$this->upload->do_upload('chat_file')) {
                return $this->output->set_content_type('application/json')
                    ->set_output(json_encode(['ok' => false, 'message' => $this->upload->display_errors('', '')]));
            }

            $up = $this->upload->data();
            $mime = $up['file_type'];
            $isImage = (strpos($mime, 'image/') === 0);

            $fileData = [
                'message_type' => $isImage ? 'image' : 'file',
                // Store the RELATIVE path only — never bake the domain in. The
                // absolute URL is rebuilt at read time (chat_fetch) via
                // media_url(), so chat images survive a domain change.
                'file_url' => 'uploads/chat/' . $up['file_name'],
                'file_name' => $up['client_name'],
                'mime_type' => $mime,
                'file_size' => (int) ($up['file_size'] * 1024),
            ];

            // If file-only and no message
            if ($message === '') {
                $message = $isImage ? '📷 Image' : '📎 File';
            }
        }

        if ($message === '' && !$fileData) {
            return $this->output->set_content_type('application/json')
                ->set_output(json_encode(['ok' => false, 'message' => 'Message empty']));
        }

        $insert = [
            'room' => $room,
            'user_id' => (int) $id,
            'to_user_id' => ($room === 'personal') ? $peerId : null,
            'peer_id' => ($room === 'personal') ? $peerId : null,
            'username' => $username,
            'message' => $message,
            'message_type' => $fileData['message_type'] ?? 'text',
            'file_url' => $fileData['file_url'] ?? null,
            'file_name' => $fileData['file_name'] ?? null,
            'mime_type' => $fileData['mime_type'] ?? null,
            'file_size' => $fileData['file_size'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $ok = $this->Chat_model->insert_message($insert);
        if (!$ok) {
            return $this->output->set_content_type('application/json')
                ->set_output(json_encode(['ok' => false, 'message' => 'DB insert failed']));
        }

        return $this->output->set_content_type('application/json')
            ->set_output(json_encode(['ok' => true, 'id' => $this->db->insert_id()]));
    }

    // GET: user/chat/fetch?room=world&after=0&peer_id=123(optional)
    public function chat_fetch()
    {
        // allow fetch from normal requests too (avoid 404 issues)
        $userId = (int) $this->session->userdata('user_userid');
        if (!$userId) {
            return $this->output->set_content_type('application/json')
                ->set_output(json_encode(['ok' => false, 'message' => 'Not logged in']));
        }

        $room = $this->input->get('room', true) ?: 'personal';
        $after = (int) $this->input->get('after', true);
        $peerId = (int) $this->input->get('peer_id', true); // for personal chat

        $allowedRooms = ['world', 'team', 'personal'];
        if (!in_array($room, $allowedRooms, true))
            $room = 'personal';

        try {
            $this->load->model('Chat_model');

            // Presence heartbeat — piggybacks on this 2s poll rather than adding a
            // websocket/socket server. "Online" = active within the last ~15s.
            $this->Chat_model->touchActive($userId);

            // AUTHORISATION — reading a DM thread needs the same genealogy-path
            // check as sending one, or peer_id could be walked to read strangers'
            // conversations.
            if ($room === 'personal') {
                if ($peerId <= 0) {
                    return $this->output->set_content_type('application/json')
                        ->set_output(json_encode(['ok' => true, 'room' => $room, 'messages' => []]));
                }
                if (!$this->Chat_model->canChatWith($userId, $peerId)) {
                    return $this->output->set_status_header(403)
                        ->set_content_type('application/json')
                        ->set_output(json_encode([
                            'ok' => false,
                            'message' => 'You can only read conversations with members in your own team path.',
                        ]));
                }
            }

            // Team room is scoped to the member's genealogy path. Cached — this
            // runs on a 2-second poll and used to re-walk the whole tree each time.
            $allowedIds = null;
            if ($room === 'team') {
                $allowedIds = $this->Chat_model->getPathChatUserIdsCached($userId);
            }

            $rows = $this->Chat_model->fetchMessagesSafe($room, $after, 80, $userId, $peerId, $allowedIds);

            // NO html_escape here. The client renders every field as a React text
            // node, which escapes on its own. Escaping server-side too double-encoded
            // everything: "Tom & Jerry" displayed as "Tom &amp; Jerry", "<3" as "&lt;3".
            // JSON is not HTML — escape at the point of render, once.
            foreach ($rows as &$r) {
                // Re-root the stored path onto the CURRENT domain. Handles both
                // new relative paths and legacy rows that baked in an old host.
                $r['file_url'] = !empty($r['file_url']) ? media_url($r['file_url']) : null;
                $r['file_name'] = $r['file_name'] ?: null;
            }
            unset($r);

            // Viewing this room/peer clears its share of the unread badge —
            // advance the read cursor to whatever the client has now seen.
            $maxId = $after;
            foreach ($rows as $r) $maxId = max($maxId, (int) $r['id']);
            if ($maxId > 0) $this->Chat_model->markRead($userId, $room, $room === 'personal' ? $peerId : 0, $maxId);

            return $this->output->set_content_type('application/json')
                ->set_output(json_encode(['ok' => true, 'room' => $room, 'messages' => $rows]));
        } catch (Throwable $e) {

            // ✅ write to CI logs (application/logs/)
            log_message('error', 'chat_fetch 500: ' . $e->getMessage());
            log_message('error', 'chat_fetch trace: ' . $e->getTraceAsString());

            // ✅ show safe error to frontend
            return $this->output->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'ok' => false,
                    'message' => 'Server error (check CI logs)',
                ]));
        }
    }

    // GET/POST: user/heartbeat — site-wide presence ping, loaded from the
    // shared header on every authenticated page (not just Chat), so "online"
    // reflects general site activity rather than only the Chat page's own poll.
    public function heartbeat()
    {
        $userId = (int) $this->session->userdata('user_userid');
        if (!$userId) {
            return $this->output->set_content_type('application/json')
                ->set_output(json_encode(['ok' => false]));
        }
        $this->load->model('Chat_model');
        $this->Chat_model->touchActive($userId);
        // Piggybacks on the same 15s sitewide poll — no separate endpoint needed
        // for the header's chat unread badge.
        $unread = $this->Chat_model->unreadCount($userId);
        return $this->output->set_content_type('application/json')
            ->set_output(json_encode(['ok' => true, 'unread' => $unread]));
    }

    // GET: user/chat/recent
    public function chat_recent()
    {
        $userId = (int) $this->session->userdata('user_userid');
        if (!$userId) {
            return $this->output->set_content_type('application/json')
                ->set_output(json_encode(['ok' => false, 'message' => 'Not logged in']));
        }

        $limit = (int) $this->input->get('limit', true);
        if ($limit <= 0 || $limit > 50)
            $limit = 20;

        $this->load->model('Chat_model');
        $rows = $this->Chat_model->getRecentPeerChats($userId, $limit);

        // Plain values — the client escapes on render (see chat_fetch). Escaping
        // here as well double-encoded names and previews in the recent list.
        foreach ($rows as &$r) {
            $r['peer_name'] = (string) ($r['peer_name'] ?? '');
            $r['last_message'] = (string) ($r['last_message'] ?? '');
            $r['last_message_time'] = (string) ($r['last_message_time'] ?? '');
            $r['last_message_type'] = (string) ($r['last_message_type'] ?? 'text');
        }
        unset($r);

        // Online/offline for everyone this member can DM — reuses the same
        // cached genealogy-path id list the fetch/send authorisation checks use.
        $pathIds = $this->Chat_model->getPathChatUserIdsCached($userId);
        $online = $this->Chat_model->onlineMap($pathIds);

        return $this->output->set_content_type('application/json')
            ->set_output(json_encode(['ok' => true, 'items' => $rows, 'online' => (object) $online]));
    }

    /*
    |--------------------------------------------------------------------------
    | Add User Genealoy
    |--------------------------------------------------------------------------
    */
    public function genealogy($user_id)
    {
        $this->data['title'] = "Members Genealogy ";
        $this->data['card_title'] = "Genealogy List";
        $this->data['user_id'] = $user_id;
        $this->load->view('admin/member/genealogy_view', $this->data);
    }
    /*
    |--------------------------------------------------------------------------
    | View Genealoy
    |--------------------------------------------------------------------------
    */
    public function getTreeData($user_id)
    {
        $members = $this->BinaryModel->getDownlineMembers($user_id);
        echo json_encode($members);
    }
    /*
    |--------------------------------------------------------------------------
    | View User Details
    |--------------------------------------------------------------------------
    */


    public function viewuserinfo($id)
    {

        /******* BASIC INFO ***********/
        $userinfo = $this->db->query("SELECT * FROM users where id = '" . $id . "'")->row();
        $sponser_info = $this->db->query("SELECT * FROM users where id = '" . $userinfo->sponser . "'")->row();

        /******* Investment INFO ***********/
        $binary_info = $this->BinaryModel->calculateLegInvestments($id);

        $left_leg_count = count($binary_info['left_leg_users']);
        $right_leg_count = count($binary_info['right_leg_users']);

        $left_leg_investment = $binary_info['left_leg_investment'];
        $right_leg_investment = $binary_info['right_leg_investment'];
        $my_investment = $binary_info['my_investment'];
        $left_leg_investment_token = $binary_info['left_investment_token'];
        $right_leg_investment_token = $binary_info['right_investment_token'];
        $my_investment_token = $binary_info['my_investment_token'];

        /******* Earnings INFO ***********/
        $binary_site_currency = $this->db->query("SELECT sum(amount) as binary_site_amt FROM history where type = 'binary_commission' ")->row()->binary_site_amt;
        $binary_token_currency = $this->db->query("SELECT sum(token_amount) as binary_token_amt FROM history where type = 'binary_commission' ")->row()->binary_token_amt;
        $roi_site_currency = $this->db->query("SELECT sum(amount) as roi_site_amt FROM history where type = 'profit' ")->row()->roi_site_amt;
        $roi_token_currency = $this->db->query("SELECT sum(token_amount) as roi_token_amt FROM history where type = 'profit' ")->row()->roi_token_amt;
        $direct_site_currency = $this->db->query("SELECT sum(amount) as direc_site_amt FROM history where type = 'direct_commission' ")->row()->direc_site_amt;
        $direct_token_currency = $this->db->query("SELECT sum(token_amount) as direc_token_amt FROM history where type = 'direct_commission' ")->row()->direc_token_amt;


        $userinfo = array(
            "name" => $userinfo->username,
            "email" => $userinfo->email,
            "register_date" => $userinfo->register_date,
            "referral_id" => $userinfo->referral_id,
            "sponser" => $sponser_info->email . " ( " . $sponser_info->referral_id . " )",
            "my_investment" => currency_format($my_investment),
            "left_leg_count" => $left_leg_count,
            "right_leg_count" => $right_leg_count,
            "left_leg_investment" => currency_format($left_leg_investment),
            "right_leg_investment" => currency_format($right_leg_investment),
            'left_leg_investment_token' => currency_format($left_leg_investment),
            'right_leg_investment_token' => currency_format($right_leg_investment),
            'my_investment_token' => currency_format($my_investment),
            'binary_site_currency' => $binary_site_currency,
            'binary_token_currency' => $binary_token_currency,
            'roi_token_currency' => $roi_token_currency,
            'direct_site_currency' => $direct_site_currency,
            'direct_token_currency' => $direct_token_currency,
        );

        $return = array(
            'result' => true,
            'data' => $userinfo
        );

        echo json_encode($return);

    }
}
