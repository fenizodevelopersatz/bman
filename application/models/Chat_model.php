<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Chat_model extends CI_Model
{
    private $table = 'chat_messages';

    public function insert_message(array $data)
    {
        return $this->db->insert($this->table, $data);
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

        // ✅ base select
        $this->db->select('id, room, user_id, to_user_id, username, message, message_type, file_url, file_name, mime_type, file_size, created_at');
        $this->db->from($this->table);
        $this->db->where('room', $room);

        if ($afterId > 0)
            $this->db->where('id >', $afterId);

        if ($room !== 'personal') {
            // world/team rooms should not include direct personal messages
            $this->db->where('to_user_id IS NULL', null, false);
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

    // union of (uplines + my downline subtree)
    public function getPathChatUserIds($userId)
    {
        $userId = (int) $userId;
        if ($userId <= 0)
            return [];

        $uplines = $this->getUplineUserIds($userId);   // [me, parent, root]
        $downlines = $this->getTeamUserIds($userId);   // [me + subtree]

        // merge unique
        $map = [];
        foreach ($uplines as $id)
            $map[(int) $id] = true;
        foreach ($downlines as $id)
            $map[(int) $id] = true;

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
        }

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


}
