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
        ];

        // ✅ Do NOT inject TREE from PHP now. We'll load via AJAX.
        $this->load->view('user/member/view-genealogy', $this->data);
    }

    public function tree_json()
    {
        header('Content-Type: application/json');

        $rootId = (int) $this->session->userdata('user_userid');
        $depth = (int) $this->input->get('depth');
        if ($depth <= 0)
            $depth = 3;
        $depth = min(7, max(1, $depth));

        // this returns array of arrays
        $rows = $this->BinaryModel->getDownlineMembers($rootId);

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

            $map[$id] = [
                'id' => $id,
                'uid' => is_array($r) ? ($r['referral_id'] ?? ('UID-' . $id)) : ($r->referral_id ?? ('UID-' . $id)),
                'name' => $name,
                'email' => $email,
                'rank' => is_array($r) ? ($r['rank'] ?? '—') : ($r->rank ?? '—'),
                'avatar' => is_array($r) ? ($r['avatar'] ?? '') : ($r->avatar ?? ''),
                'status' => is_array($r) ? ($r['status'] ?? 'ACTIVE') : ($r->status ?? 'ACTIVE'),
                'join_date' => !empty($join) ? date('Y-m-d', strtotime($join)) : '—',
                'left_bv' => (float) (is_array($r) ? ($r['left_bv'] ?? 0) : ($r->left_bv ?? 0)),
                'right_bv' => (float) (is_array($r) ? ($r['right_bv'] ?? 0) : ($r->right_bv ?? 0)),
                'mid' => $mid,
                'position' => (stripos($posRaw, 'right') !== false) ? 'RIGHT' : 'LEFT',
                'left' => null,
                'right' => null,
            ];
        }

        // ensure root exists
        if (!isset($map[$rootId])) {
            $u = $this->db->query("SELECT id, username, referral_id, register_date FROM users WHERE id=?", [$rootId])->row();
            $map[$rootId] = [
                'id' => $rootId,
                'uid' => $u->referral_id ?? ('UID-' . $rootId),
                'name' => $u->username ?? 'User',
                'rank' => '—',
                'avatar' => '',
                'status' => 'ACTIVE',
                'join_date' => !empty($u->register_date) ? date('Y-m-d', strtotime($u->register_date)) : '—',
                'left_bv' => 0,
                'right_bv' => 0,
                'mid' => 0,
                'position' => '',
                'left' => null,
                'right' => null
            ];
        }

        // link children
        foreach ($map as $id => $node) {
            $pid = (int) $node['mid'];
            if ($pid > 0 && isset($map[$pid])) {
                if ($node['position'] === 'RIGHT')
                    $map[$pid]['right'] = $node;
                else
                    $map[$pid]['left'] = $node;
            }
        }

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



    private function getNodeStats(int $id): array
    {
        if ($id <= 0) {
            return [
                'rank' => '—',
                'left_bv' => 0,
                'right_bv' => 0,
                'left_cf' => 0,
                'right_cf' => 0,
                'pairs' => 0,
            ];
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

        return [
            'rank' => $rankName,
            'left_bv' => $leftBV,
            'right_bv' => $rightBV,
            'left_cf' => $leftCF,
            'right_cf' => $rightCF,
            'pairs' => $pairs_lifetime,
        ];
    }

    private function emptyNode(): array
    {
        return [
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
        ];
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

        // cut children at max level
        if ($level >= $max) {
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
            ]
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

        // ===== Settings (from site_settings withdraw_settings) =====
        $withdraw_status = (int) site_settings('withdraw_settings', 'withdraw_status'); // 1 enabled, 0 disabled
        $min_withdraw = (float) str_replace(',', '', site_settings('withdraw_settings', 'min_withdraw'));
        $max_withdraw = (float) str_replace(',', '', site_settings('withdraw_settings', 'max_withdraw'));
        $withdraw_fee = (float) str_replace(',', '', site_settings('withdraw_settings', 'withdraw_fee'));
        $withdraw_daily_limit = (float) str_replace(',', '', site_settings('withdraw_settings', 'withdraw_daily_limit'));
        $withdraw_monthly_limit = (float) str_replace(',', '', site_settings('withdraw_settings', 'withdraw_monthly_limit'));
        $withdraw_amount_type = (int) site_settings('withdraw_settings', 'withdraw_amount_type'); // 1=USD etc
        $auto_withdraw = (int) site_settings('withdraw_settings', 'auto_withdraw');

        // ===== Wallets =====
        $available_amount = (float) site_wallet_balance($id);   // ✅ payout->available_amount        
        $token_balance = (float) site_token_balance($id);

        // ===== Bank status =====
        $user_bank = $this->db->get_where('user_bank', ['user_id' => $id])->row();
        $bank_ok = false;
        if ($user_bank && !empty($user_bank->status)) {
            $bank_ok = (strtolower($user_bank->status) === 'approved');
        }

        // ===== KYC status (your code uses string 'approved' in UI) =====
        $kyc_ok = false;
        if (!empty($user->kyc_status)) {
            $kyc_ok = (strtolower((string) $user->kyc_status) === 'approved' || (string) $user->kyc_status === '1');
        }

        // ===== Pending + Paid totals =====
        // We try common tables first. If your project uses different names, tell me your table name.
        $pending_amount = 0.0;
        $paid_total = 0.0;
        $payouts = [];

        // helper: check which table exists
        $has_withdrawals = $this->db->table_exists('withdrawals');
        $has_payouts_table = $this->db->table_exists('payouts');

        if ($has_withdrawals) {
            // Typical schema: withdrawals(user_id, amount, fee, status, created_at, payout_id/txn_id, remark, method, period, type)
            $pending_row = $this->db->select('IFNULL(SUM(amount),0) AS s', false)
                ->from('withdrawals')
                ->where('user_id', $id)
                ->where_in('status', ['pending', 'processing', 'approved_pending', 'under_review'])
                ->get()->row();
            $pending_amount = (float) ($pending_row->s ?? 0);

            $paid_row = $this->db->select('IFNULL(SUM(amount),0) AS s', false)
                ->from('withdrawals')
                ->where('user_id', $id)
                ->where_in('status', ['paid', 'success', 'completed', 'approved'])
                ->get()->row();
            $paid_total = (float) ($paid_row->s ?? 0);

            // history list
            $rows = $this->db->from('withdrawals')
                ->where('user_id', $id)
                ->order_by('id', 'DESC')
                ->limit(200)
                ->get()->result();

            // foreach ($rows as $r) {
            //     $payouts[] = (object) [
            //         'payout_id' => $r->payout_id ?? ($r->txn_id ?? ('WD-' . $r->id)),
            //         'period' => $r->period ?? ('—'),
            //         'type' => strtoupper($r->type ?? 'MANUAL'),
            //         'amount' => (float) ($r->amount ?? 0),
            //         'fee' => (float) ($r->fee ?? 0),
            //         'status' => strtoupper($r->status ?? 'PENDING'),
            //         'date' => !empty($r->created_at) ? date('Y-m-d', strtotime($r->created_at)) : ($r->date ?? date('Y-m-d')),
            //         'note' => $r->remark ?? ($r->note ?? ($r->method ?? '')),
            //     ];
            // }

            foreach ($rows as $r) {
                $payouts[] = (object) [
                    'id' => $r->id,
                    'payout_id' => $r->payout_id ?? ('WD-' . $r->id),
                    'txn_id' => $r->admin_txn_id,
                    'user_id' => $r->user_id,

                    'amount' => (float) ($r->amount ?? 0),
                    'fee' => (float) ($r->fee ?? 0),
                    'net_amount' => (float) ($r->net_amount ?? ($r->amount - $r->fee)),

                    'status' => strtoupper($r->status ?? 'PENDING'),
                    'method' => $r->method ?? 'BANK',
                    'type' => strtoupper($r->type ?? 'MANUAL'),
                    'period' => $r->period ?? '—',

                    'remark' => $r->remark,
                    'note' => $r->note,

                    'admin_review' => $r->admin_review,
                    'approved_at' => $r->approved_at,

                    'admin_proof_img' => $r->admin_proof_img
                        ? base_url('uploads/withdraw_proof/' . $r->admin_proof_img)
                        : null,

                    'created_at' => $r->created_at
                        ? date('d M Y H:i', strtotime($r->created_at))
                        : '—',
                ];
            }


        } else {
            // Fallback: if your project stores withdrawals in history table as type='withdraw' or similar
            if ($this->db->table_exists('history')) {
                $pending_amount = 0.0; // no reliable status mapping in history without your schema
                $paid_row = $this->db->select('IFNULL(SUM(amount),0) AS s', false)
                    ->from('history')
                    ->where('user_id', $id)
                    ->where_in('type', ['withdraw', 'withdrawal'])
                    ->where('status', '1')
                    ->get()->row();
                $paid_total = (float) ($paid_row->s ?? 0);
            }
        }

        // ===== Eligibility rules (you can tighten these) =====
        $eligible = true;

        if ($withdraw_status !== 1)
            $eligible = false;
        if (!$kyc_ok)
            $eligible = false;
        if (!$bank_ok)
            $eligible = false;
        if ($available_amount < $min_withdraw)
            $eligible = false;

        // ===== Next payout time label (simple) =====
        // If you have a cron schedule table, we can compute exactly. For now show "Tonight 10:00 PM".
        $next_date_label = NEXT_PAYOUT_TIME;

        // ===== Withdraw methods (UI expects array of objects) =====
        $withdraw_methods = [
            (object) [
                'key' => 'BANK',
                'name' => 'Bank Transfer',
                'desc' => 'Direct to your linked bank account',
            ]
        ];

        // ===== Build $payout object for the UI =====
        $payout = (object) [
            'next_date' => $next_date_label,
            'min_withdraw' => $min_withdraw,
            'processing_fee' => $withdraw_fee,
            'eligibility' => $eligible,
            'kyc' => $kyc_ok,
            'bank' => $bank_ok,
            'pending_amount' => $pending_amount,
            'available_amount' => $available_amount,
            'paid_total' => $paid_total,
            'max_withdraw' => $max_withdraw,
            'daily_limit' => $withdraw_daily_limit,
            'monthly_limit' => $withdraw_monthly_limit,
            'amount_type' => $withdraw_amount_type,
            'auto_withdraw' => $auto_withdraw,
        ];

        // ===== Pass to view =====
        $this->data['title'] = "Payouts";
        $this->data['card_tilte'] = "Payouts";
        $this->data['user_id'] = $id;
        $this->data['first_letter'] = $first_letter;

        $this->data['currency_info'] = currency_info();
        $this->data['token_info'] = token_info();

        $this->data['user'] = $user;
        $this->data['user_bank'] = $user_bank;

        // ✅ UI variables your new page needs
        $this->data['payout'] = $payout;
        $this->data['withdraw_methods'] = $withdraw_methods;
        $this->data['payouts'] = $payouts;

        // if you still need these old ones
        $this->data['wallet_balance'] = $available_amount;
        $this->data['token_wallet_balance'] = $token_balance;

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

        $this->data['currency_info'] = currency_info();
        $this->data['token_info'] = token_info();
        $this->data['wallet_balance'] = site_wallet_balance($id);
        $this->data['token_wallet_balance'] = site_token_balance($id);

        $this->data['chat_fetch_url'] = base_url('user/chat/fetch');
        $this->data['chat_send_url'] = base_url('user/chat/send');
        $this->data['chat_recent_url'] = base_url('user/chat/recent');

        // ✅ server-side rendered options for select2
        // $this->data['team_members'] = $this->Chat_model->getTeamMembers($id);
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
                return false;
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
                'file_url' => base_url('uploads/chat/' . $up['file_name']),
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

            // ✅ team access restriction: only downline (optional)
            $allowedIds = null;
            if ($room === 'team') {
                // $allowedIds = $this->Chat_model->getTeamUserIds($userId);
                $allowedIds = $this->Chat_model->getPathChatUserIds($userId);
            }

            // ✅ personal chat requires peerId
            $rows = $this->Chat_model->fetchMessagesSafe($room, $after, 80, $userId, $peerId, $allowedIds);

            // escape
            foreach ($rows as &$r) {
                $r['username'] = html_escape($r['username']);
                $r['message'] = html_escape($r['message']);
                $r['file_name'] = $r['file_name'] ? html_escape($r['file_name']) : null;
                $r['file_url'] = $r['file_url'] ?: null;
            }

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

        // escape output
        foreach ($rows as &$r) {
            $r['peer_name'] = html_escape((string) ($r['peer_name'] ?? ''));
            $r['last_message'] = html_escape((string) ($r['last_message'] ?? ''));
            $r['last_message_time'] = (string) ($r['last_message_time'] ?? '');
            $r['last_message_type'] = (string) ($r['last_message_type'] ?? 'text');
        }

        return $this->output->set_content_type('application/json')
            ->set_output(json_encode(['ok' => true, 'items' => $rows]));
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
    /*
   |--------------------------------------------------------------------------
   | STATUS Update
   |--------------------------------------------------------------------------
   */
    public function statusupdate($id)
    {

        if ($id) {

            $check_template = $this->db->query("SELECT * FROM `users` where id = '" . $id . "'")->num_rows();

            if ($check_template > 0) {

                $status = $this->input->post('template_status');
                $template_status = $status == '1' ? '1' : '2';

                $array_template = array(
                    "status" => $template_status,
                );

                $this->db->where('id', $id);
                $this->db->update('users', $array_template);

                $response = array(
                    'status' => "success",
                    'message' => "Status update successfully.."
                );
                echo json_encode($response);
                exit();
            } else {
                $response = array(
                    'status' => false,
                    'message' => "Invalide User!"
                );
                echo json_encode($response);
                exit();
            }

        }

    }
    /*
    |--------------------------------------------------------------------------
    | DELETE USER
    |--------------------------------------------------------------------------
    */
    public function deleteuser($id)
    {
        if ($id) {
            $check_user = $this->db->query("SELECT * FROM `users` WHERE id = '" . $id . "'")->num_rows();

            if ($check_user > 0) {
                $check_investment = $this->db->query("SELECT * FROM `user_investment` WHERE user_id = '" . $id . "' AND status = 1")->num_rows();

                $check_downline = $this->db->query("SELECT * FROM `binary_placement` WHERE sponsor_id = '" . $id . "' OR parent_id = '" . $id . "'")->num_rows();

                if ($check_investment > 0) {
                    $response = array(
                        'status' => false,
                        'message' => "User has an active investment. Cannot delete!"
                    );
                } elseif ($check_downline > 0) {
                    $response = array(
                        'status' => false,
                        'message' => "User has a downline. Cannot delete!"
                    );
                } else {
                    $this->db->query("DELETE FROM `history` WHERE user_id = '" . $id . "'");
                    $this->db->query("DELETE FROM `history` WHERE from_id = '" . $id . "'");
                    $this->db->query("DELETE FROM `user_investment` WHERE user_id = '" . $id . "'");
                    $this->db->query("DELETE FROM `users` WHERE id = '" . $id . "'");
                    $response = array(
                        'status' => 'success',
                        'message' => "User and related records deleted successfully."
                    );
                }
            } else {
                $response = array(
                    'status' => false,
                    'message' => "Invalid User!"
                );
            }

            echo json_encode($response);
            exit();
        }
    }


}
