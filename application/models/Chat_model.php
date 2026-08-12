<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Chat_model extends CI_Model
{
    private $table = 'chat_messages';

    public function insert_message(array $data)
    {
        return $this->db->insert($this->table, $data);
    }

    /* ---------------------------
     * PRESENCE (heartbeat-based — no websocket server in this app)
     * --------------------------- */

    /** Mark $userId active right now. Called on every chat/fetch poll (2s). */
    public function touchActive($userId)
    {
        $userId = (int) $userId;
        if ($userId <= 0) return;
        $this->db->where('id', $userId)->update('users', ['last_active_at' => date('Y-m-d H:i:s')]);
    }

    /** [$id => bool] online (active within $windowSeconds) for the given user ids. */
    public function onlineMap(array $userIds, $windowSeconds = 30)
    {
        $userIds = array_values(array_unique(array_map('intval', $userIds)));
        if (empty($userIds)) return [];

        $cutoff = date('Y-m-d H:i:s', time() - (int) $windowSeconds);
        $rows = $this->db->select('id, last_active_at')
                         ->from('users')
                         ->where_in('id', $userIds)
                         ->get()->result_array();

        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r['id']] = !empty($r['last_active_at']) && $r['last_active_at'] >= $cutoff;
        }
        return $map;
    }

    // ---------------------------
    // TEAM USERS (binary downline)
    // ---------------------------
    public function getTeamUserIds($rootUserId)
    {
        $rootUserId = (int) $rootUserId;
        $team = [$rootUserId];

        $queue = [$rootUserId];
        $seen = [$rootUserId => true];

        while (!empty($queue)) {
            $parent = array_shift($queue);

            $rows = $this->db
                ->select('user_id')
                ->from('binary_placement')
                ->where('parent_id', (int) $parent)
                ->get()
                ->result();

            foreach ($rows as $r) {
                $uid = (int) $r->user_id;
                if ($uid > 0 && !isset($seen[$uid])) {
                    $seen[$uid] = true;
                    $team[] = $uid;
                    $queue[] = $uid;
                }
            }
        }

        return $team;
    }

    // ✅ Used for Select2 dropdown list (server-side render options)
    public function getTeamMembers($rootUserId)
    {
        $ids = $this->getTeamUserIds($rootUserId);

        // remove self
        $ids = array_values(array_filter($ids, function ($x) use ($rootUserId) {
            return (int) $x !== (int) $rootUserId;
        }));
        if (empty($ids))
            return [];

        return $this->db
            ->select('id, username')
            ->from('users')
            ->where_in('id', $ids)
            ->order_by('username', 'ASC')
            ->get()
            ->result_array();
    }

    // ---------------------------
    // WORLD / TEAM messages
    // ---------------------------
    public function fetchRoomMessages($room, $afterId = 0, $limit = 60, $allowedUserIds = null)
    {
        $room = in_array($room, ['world', 'team'], true) ? $room : 'world';
        $afterId = (int) $afterId;
        $limit = (int) $limit;
        if ($limit <= 0 || $limit > 100)
            $limit = 60;

        $this->db->select('id, room, user_id, to_user_id, username, message, message_type, file_url, file_name, mime_type, file_size, created_at');
        $this->db->from($this->table);
        $this->db->where('room', $room);
        $this->db->where('id >', $afterId);
        $this->db->where('to_user_id IS NULL', null, false);

        if (is_array($allowedUserIds) && !empty($allowedUserIds)) {
            $this->db->where_in('user_id', $allowedUserIds);
        }

        return $this->db->order_by('id', 'ASC')->limit($limit)->get()->result_array();
    }

    // ---------------------------
    // ✅ PERSONAL (1-to-1) messages
    // ---------------------------
    public function fetchPersonalMessages($userId, $peerId, $afterId = 0, $limit = 60)
    {
        $userId = (int) $userId;
        $peerId = (int) $peerId;
        $afterId = (int) $afterId;
        $limit = (int) $limit;
        if ($limit <= 0 || $limit > 100)
            $limit = 60;

        $this->db->select('id, room, user_id, to_user_id, username, message, message_type, file_url, file_name, mime_type, file_size, created_at');
        $this->db->from($this->table);
        $this->db->where('room', 'personal');
        $this->db->where('id >', $afterId);

        // (me -> peer) OR (peer -> me)
        $this->db->group_start()
            ->group_start()
            ->where('user_id', $userId)
            ->where('to_user_id', $peerId)
            ->group_end()
            ->or_group_start()
            ->where('user_id', $peerId)
            ->where('to_user_id', $userId)
            ->group_end()
            ->group_end();

        return $this->db->order_by('id', 'ASC')->limit($limit)->get()->result_array();
    }

    public function fetchMessagesSafe($room, $afterId, $limit, $currentUserId, $peerId = 0, $allowedUserIds = null)
    {
        $room = in_array($room, ['world', 'team', 'personal'], true) ? $room : 'personal';
        $afterId = (int) $afterId;
        $limit = (int) $limit;
        if ($limit <= 0 || $limit > 100)
            $limit = 50;

        // Resolve BEFORE starting the main query's builder chain below.
        // _registeredAt() runs its own select()/from()/where()/get() against
        // the SAME $this->db object; calling it while the main query's clauses
        // are already staged (select/from/where set but get() not yet called)
        // makes CI3's query builder merge the two — producing a broken
        // `FROM chat_messages, users` cross join with an ambiguous `id` column
        // and a 500 on every world/team fetch. Must fully resolve first.
        $registeredAt = ($room !== 'personal') ? $this->_registeredAt($currentUserId) : null;

        // ✅ base select
        $this->db->select('id, room, user_id, to_user_id, username, message, message_type, file_url, file_name, mime_type, file_size, created_at');
        $this->db->from($this->table);
        $this->db->where('room', $room);

        if ($afterId > 0)
            $this->db->where('id >', $afterId);

        if ($room !== 'personal') {
            // world/team rooms should not include direct personal messages
            $this->db->where('to_user_id IS NULL', null, false);
            // A member only sees room traffic from after they joined — a new
            // account must not inherit the room's whole pre-registration history.
            if ($registeredAt !== null) {
                $this->db->where('created_at >=', $registeredAt);
            }
        }

        // ✅ TEAM: restrict allowed users list
        if ($room === 'team' && is_array($allowedUserIds) && !empty($allowedUserIds)) {
            $this->db->where_in('user_id', $allowedUserIds);
        }

        // ✅ PERSONAL: conversation between currentUserId and peerId
        if ($room === 'personal') {
            $currentUserId = (int) $currentUserId;
            $peerId = (int) $peerId;

            if ($peerId <= 0) {
                // no peer selected => return empty (avoid error)
                return [];
            }

            // conversation filter: (me -> peer) OR (peer -> me)
            $this->db->group_start()
                ->group_start()
                ->where('user_id', $currentUserId)
                ->where('to_user_id', $peerId)
                ->group_end()
                ->or_group_start()
                ->where('user_id', $peerId)
                ->where('to_user_id', $currentUserId)
                ->group_end()
                ->group_end();
        }

        $this->db->order_by('id', 'ASC');
        $this->db->limit($limit);

        $q = $this->db->get();

        // ✅ if db error, throw (controller will catch)
        if (!$q) {
            $err = $this->db->error();
            throw new Exception('DB error: ' . ($err['message'] ?? 'unknown'));
        }

        return $q->result_array();
    }



    // ---------------------------
// ✅ PATH BASED USERS (upline + downline)
// ---------------------------

    // upline chain: user -> parent -> parent ... until top
    public function getUplineUserIds($userId)
    {
        $userId = (int) $userId;
        if ($userId <= 0)
            return [];

        $uplines = [$userId];
        $current = $userId;

        $guard = 0;
        $seen = [$current => true];

        while ($guard++ < 500) {
            $row = $this->db
                ->select('parent_id')
                ->from('binary_placement')
                ->where('user_id', $current)
                ->get()
                ->row();

            if (!$row)
                break;

            $parentId = (int) ($row->parent_id ?? 0);
            if ($parentId <= 0)
                break;

            if (isset($seen[$parentId]))
                break; // safety loop
            $seen[$parentId] = true;

            $uplines[] = $parentId;
            $current = $parentId;
        }

        return $uplines; // includes self + uplines
    }

    // direct parent only (one level up), not the whole upline chain
    public function getDirectParentId($userId)
    {
        $userId = (int) $userId;
        if ($userId <= 0)
            return 0;

        $row = $this->db
            ->select('parent_id')
            ->from('binary_placement')
            ->where('user_id', $userId)
            ->get()
            ->row();

        return $row ? (int) ($row->parent_id ?? 0) : 0;
    }

    // union of (my downline subtree + my single direct parent) — a member's
    // chat contacts are their team plus the one person directly above them,
    // not the whole upline chain up to the root.
    public function getPathChatUserIds($userId)
    {
        $userId = (int) $userId;
        if ($userId <= 0)
            return [];

        $downlines = $this->getTeamUserIds($userId);      // [me + subtree]
        $parentId = $this->getDirectParentId($userId);    // one level up, or 0

        // merge unique
        $map = [];
        foreach ($downlines as $id)
            $map[(int) $id] = true;
        if ($parentId > 0) {
            $map[$parentId] = true;
        }

        return array_keys($map); // unique list
    }

    // dropdown members for path chat
    public function getPathChatMembers($userId)
    {
        $ids = $this->getPathChatUserIds($userId);

        // remove self
        $ids = array_values(array_filter($ids, function ($x) use ($userId) {
            return (int) $x !== (int) $userId;
        }));

        if (empty($ids))
            return [];

        return $this->db
            ->select('id, username')
            ->from('users')
            ->where_in('id', $ids)
            ->order_by('username', 'ASC')
            ->get()
            ->result_array();
    }


    /* =====================================================================
     * ACCESS CONTROL + CACHING
     * ---------------------------------------------------------------------
     * getPathChatUserIds() is EXPENSIVE: it walks the upline one query per
     * level and BFSes the entire downline one query per node. The chat page
     * polls every 2 seconds, so calling it raw on each poll issued hundreds of
     * queries per user per poll.
     *
     * Two layers of cache:
     *   - per-request static, so one request never computes it twice;
     *   - session cache with a short TTL, so a 2s poll re-uses the last result.
     * Genealogy placement changes rarely, and the TTL bounds how stale the
     * allowlist can be. New team members become chattable within TTL seconds.
     * ===================================================================== */

    /** @var array<int,int[]> per-request memo */
    private $path_ids_memo = [];

    const PATH_CACHE_TTL = 60;   // seconds

    /**
     * Cached upline ∪ downline ids for $userId.
     * Pass $force = true after a placement change to bypass the cache.
     */
    public function getPathChatUserIdsCached($userId, $force = false)
    {
        $userId = (int) $userId;
        if ($userId <= 0)
            return [];

        if (!$force && isset($this->path_ids_memo[$userId])) {
            return $this->path_ids_memo[$userId];
        }

        if (!$force && isset($this->session)) {
            $c = $this->session->userdata('chat_path_ids');
            if (is_array($c) && isset($c['uid'], $c['at'], $c['ids'])
                && (int) $c['uid'] === $userId
                && (time() - (int) $c['at']) < self::PATH_CACHE_TTL
            ) {
                return $this->path_ids_memo[$userId] = $c['ids'];
            }
        }

        $ids = array_values(array_unique(array_map('intval', $this->getPathChatUserIds($userId))));
        $this->path_ids_memo[$userId] = $ids;

        if (isset($this->session)) {
            $this->session->set_userdata('chat_path_ids', [
                'uid' => $userId, 'at' => time(), 'ids' => $ids,
            ]);
        }
        return $ids;
    }

    /**
     * May $userId direct-message $peerId?
     *
     * Personal chat is limited to the member's own genealogy path (upline ∪
     * downline) — the same set the Team room uses. Without this check any
     * logged-in member could POST an arbitrary peer_id and DM a total stranger,
     * and could read that thread back via chat/fetch. Both the send and fetch
     * paths call this.
     */
    public function canChatWith($userId, $peerId)
    {
        $userId = (int) $userId;
        $peerId = (int) $peerId;
        if ($peerId <= 0 || $userId <= 0 || $peerId === $userId)
            return false;

        return in_array($peerId, $this->getPathChatUserIdsCached($userId), true);
    }

    // -------------------------------------------
// ✅ Recent personal chats list (like WhatsApp)
// -------------------------------------------
    public function getRecentPersonalChats($userId, $limit = 20)
    {
        $userId = (int) $userId;
        $limit = (int) $limit;
        if ($limit <= 0 || $limit > 100)
            $limit = 20;

        // Subquery: latest message id per peer for this user
        // peer_id = (if I sent => to_user_id else sender user_id)
        $sub = "
        SELECT 
            MAX(id) AS last_id,
            CASE 
                WHEN user_id = {$userId} THEN to_user_id
                ELSE user_id
            END AS peer_id
        FROM {$this->table}
        WHERE room = 'personal'
          AND (user_id = {$userId} OR to_user_id = {$userId})
          AND to_user_id IS NOT NULL
        GROUP BY peer_id
    ";

        $rows = $this->db
            ->select("m.id, m.user_id, m.to_user_id, m.message, m.message_type, m.file_url, m.file_name, m.created_at, t.peer_id, u.username AS peer_username")
            ->from("({$sub}) t", false)
            ->join("{$this->table} m", "m.id = t.last_id", "inner", false)
            ->join("users u", "u.id = t.peer_id", "left")
            ->order_by("m.id", "DESC")
            ->limit($limit)
            ->get()
            ->result_array();

        // normalize + safety
        foreach ($rows as &$r) {
            $r['peer_id'] = (int) ($r['peer_id'] ?? 0);
            $r['peer_username'] = (string) ($r['peer_username'] ?? 'User');
            $r['is_me'] = ((int) $r['user_id'] === $userId) ? 1 : 0;
            // Domain-agnostic image URL (handles legacy rows with a baked-in host).
            if (!empty($r['file_url'])) $r['file_url'] = media_url($r['file_url']);
        }
        unset($r);

        return $rows;
    }

    /**
     * Recent peer list with last message summary for sidebar usage.
     * Returns: peer_id, peer_name, last_message_time, last_message_type, last_message
     */
    public function getRecentPeerChats($userId, $limit = 20)
    {
        $rows = $this->getRecentPersonalChats($userId, $limit);
        $out = [];

        foreach ($rows as $r) {
            $out[] = [
                'peer_id' => (int) ($r['peer_id'] ?? 0),
                'peer_name' => (string) ($r['peer_username'] ?? ''),
                'last_message_time' => (string) ($r['created_at'] ?? ''),
                'last_message_type' => (string) ($r['message_type'] ?? 'text'),
                'last_message' => (string) $this->formatRecentPreview($r),
            ];
        }

        return $out;
    }

    /**
     * Optional helper: mark "preview message" for attachments if message is empty
     */
    public function formatRecentPreview($row)
    {
        $type = $row['message_type'] ?? 'text';
        $msg = trim((string) ($row['message'] ?? ''));

        if ($msg !== '')
            return $msg;

        if ($type === 'image')
            return '📷 Image';
        if ($type === 'file')
            return '📎 File';
        return '';
    }

    /* ---------------------------
     * UNREAD COUNT (header badge) — reuses the same room/peer scoping as
     * fetchMessagesSafe()/canChatWith() rather than re-deriving visibility.
     * --------------------------- */

    /** Advance the read cursor for a room (+ peer for personal). Never regresses it. */
    public function markRead($userId, $room, $peerId, $lastId)
    {
        $userId = (int) $userId;
        $peerId = (int) $peerId;
        $lastId = (int) $lastId;
        if ($userId <= 0 || $lastId <= 0) return;

        $this->db->query(
            "INSERT INTO chat_read_state (user_id, room, peer_id, last_read_id) VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE last_read_id = GREATEST(last_read_id, VALUES(last_read_id))",
            [$userId, (string) $room, $peerId, $lastId]
        );
    }

    private function _lastReadId($userId, $room, $peerId = 0)
    {
        $row = $this->db->get_where('chat_read_state', [
            'user_id' => (int) $userId, 'room' => (string) $room, 'peer_id' => (int) $peerId,
        ])->row_array();
        return (int) ($row['last_read_id'] ?? 0);
    }

    /** Registration datetime for room-history scoping; null when unknown. */
    private function _registeredAt($userId)
    {
        $userId = (int) $userId;
        if ($userId <= 0) return null;
        $row = $this->db->select('register_date')->from('users')
            ->where('id', $userId)->get()->row_array();
        $val = trim((string) ($row['register_date'] ?? ''));
        return ($val !== '' && $val !== '0000-00-00 00:00:00') ? $val : null;
    }

    /**
     * Total unread across world (everyone), team (genealogy path), and
     * personal (DMs addressed to this user) — same visibility rules the
     * fetch/send authorisation already enforces, just counted instead of read.
     */
    public function unreadCount($userId)
    {
        $userId = (int) $userId;
        if ($userId <= 0) return 0;

        // Same scoping as fetchMessagesSafe: pre-registration room history is
        // invisible to this member, so it must not count as unread either.
        $registeredAt = $this->_registeredAt($userId);

        $worldSince = $this->_lastReadId($userId, 'world');
        $this->db->where('room', 'world')->where('user_id !=', $userId)
            ->where('id >', $worldSince);
        if ($registeredAt !== null) $this->db->where('created_at >=', $registeredAt);
        $world = (int) $this->db->count_all_results($this->table);

        $team = 0;
        $pathIds = $this->getPathChatUserIdsCached($userId);
        if (!empty($pathIds)) {
            $teamSince = $this->_lastReadId($userId, 'team');
            $this->db->where('room', 'team')->where_in('user_id', $pathIds)
                ->where('id >', $teamSince);
            if ($registeredAt !== null) $this->db->where('created_at >=', $registeredAt);
            $team = (int) $this->db->count_all_results($this->table);
        }

        $personal = (int) $this->db->select('COUNT(*) AS n', false)
            ->from($this->table . ' cm')
            ->join('chat_read_state rs', "rs.user_id = {$userId} AND rs.room = 'personal' AND rs.peer_id = cm.user_id", 'left')
            ->where('cm.room', 'personal')
            ->where('cm.to_user_id', $userId)
            ->where('cm.id > COALESCE(rs.last_read_id, 0)', null, false)
            ->get()->row()->n ?? 0;

        return $world + $team + (int) $personal;
    }

}
