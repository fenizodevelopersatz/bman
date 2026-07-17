<?php defined('BASEPATH') OR exit('No direct script access allowed');
/** TEMPORARY harness for the chat fixes + redesign. Deleted after the run. */
class Zchatcheck extends CI_Controller
{
    public function run()
    {
        if (!is_cli()) show_404();
        $this->load->library('session');
        $this->load->model('Chat_model');

        $ok = 0; $fail = 0;
        $check = function ($label, $cond, $extra = '') use (&$ok, &$fail) {
            if ($cond) { $ok++; echo "  PASS  {$label}\n"; }
            else { $fail++; echo "  FAIL  {$label}" . ($extra ? "  ({$extra})" : '') . "\n"; }
        };

        echo "\n=== A. SCHEMA (the migration that never existed) ===\n";
        $check('chat_messages exists', $this->db->table_exists('chat_messages'));
        foreach (['id','room','user_id','to_user_id','username','message','message_type',
                  'file_url','file_name','mime_type','file_size','created_at'] as $c) {
            $check("column {$c}", $this->db->field_exists($c, 'chat_messages'));
        }
        $idx = $this->db->query("SHOW INDEX FROM chat_messages")->result_array();
        $names = array_unique(array_column($idx, 'Key_name'));
        $check('polling index idx_room_id', in_array('idx_room_id', $names, true));
        $check('DM index idx_room_user_to', in_array('idx_room_user_to', $names, true));
        $check('DM index idx_room_to_user', in_array('idx_room_to_user', $names, true));

        echo "\n=== B. GENEALOGY PATH (who may DM whom) ===\n";
        // Fixture tree: 1 → 100/101 → 110/111,120/121 → 130/131,140/141
        $path1 = $this->Chat_model->getPathChatUserIdsCached(1);
        sort($path1);
        $check('user 1 path = self + whole downline (11)', count($path1) === 11, implode(',', $path1));
        $path130 = $this->Chat_model->getPathChatUserIdsCached(130);
        sort($path130);
        // 130's path = self + upline (110,100,1). No siblings, no cousins.
        $check('leaf 130 path = self + upline only', implode(',', $path130) === '1,100,110,130',
               implode(',', $path130));

        echo "\n=== C. AUTHORISATION — the IDOR fix ===\n";
        $check('130 MAY DM its upline 110', $this->Chat_model->canChatWith(130, 110) === true);
        $check('130 MAY DM the root 1',     $this->Chat_model->canChatWith(130, 1) === true);
        $check('1 MAY DM its downline 141', $this->Chat_model->canChatWith(1, 141) === true);
        // 131 is in the other branch under 111 — NOT on 130's path.
        $check('130 may NOT DM cousin 131 (was possible before)',
               $this->Chat_model->canChatWith(130, 131) === false);
        $check('140 may NOT DM cousin 130', $this->Chat_model->canChatWith(140, 130) === false);
        $check('nobody may DM themselves', $this->Chat_model->canChatWith(1, 1) === false);
        $check('peer 0 rejected', $this->Chat_model->canChatWith(1, 0) === false);
        $check('unknown peer rejected', $this->Chat_model->canChatWith(1, 999999) === false);

        echo "\n=== D. CACHE (the N+1 that ran every 2s) ===\n";
        $this->db->query("SET @x=1"); // no-op, keeps the profiler honest
        $before = count($this->db->queries);
        $this->Chat_model->getPathChatUserIdsCached(1);   // memo hit
        $this->Chat_model->getPathChatUserIdsCached(1);
        $this->Chat_model->getPathChatUserIdsCached(1);
        $after = count($this->db->queries);
        $check('3 repeat calls issue 0 extra queries', ($after - $before) === 0, ($after - $before) . ' queries');

        $fresh = new Chat_model();
        $b2 = count($this->db->queries);
        $fresh->getPathChatUserIds(1);                     // raw, uncached
        $raw = count($this->db->queries) - $b2;
        $check('raw uncached walk is expensive (proves the cache matters)', $raw >= 5, $raw . ' queries');

        echo "\n=== E. SEND + FETCH ROUND TRIP ===\n";
        $this->db->query("DELETE FROM chat_messages");
        $mk = function ($room, $uid, $to, $msg) {
            return $this->Chat_model->insert_message([
                'room' => $room, 'user_id' => $uid, 'to_user_id' => $to, 'peer_id' => $to,
                'username' => 'u' . $uid, 'message' => $msg, 'message_type' => 'text',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        };
        $check('insert world message', $mk('world', 1, null, 'hello world') === true);
        $check('insert team message',  $mk('team', 110, null, 'team hi') === true);
        $check('insert DM 1→100',      $mk('personal', 1, 100, 'dm one') === true);
        $check('insert DM 100→1',      $mk('personal', 100, 1, 'dm two') === true);
        $mk('personal', 130, 131, 'sneaky cross-branch dm');

        $world = $this->Chat_model->fetchMessagesSafe('world', 0, 80, 1, 0, null);
        $check('world returns 1 message', count($world) === 1, count($world));
        $check('world excludes DMs', !array_filter($world, function ($m) { return $m['to_user_id'] !== null; }));

        $dm = $this->Chat_model->fetchMessagesSafe('personal', 0, 80, 1, 100, null);
        $check('DM thread 1↔100 returns both directions', count($dm) === 2, count($dm));

        $teamAllowed = $this->Chat_model->getPathChatUserIdsCached(1);
        $team = $this->Chat_model->fetchMessagesSafe('team', 0, 80, 1, 0, $teamAllowed);
        $check('team room scoped to path', count($team) === 1, count($team));

        echo "\n=== F. NO DOUBLE-ESCAPING (the visible bug) ===\n";
        $this->db->query("DELETE FROM chat_messages");
        $mk('world', 1, null, 'Tom & Jerry <3 "quotes"');
        $rows = $this->Chat_model->fetchMessagesSafe('world', 0, 80, 1, 0, null);
        $check('message stored verbatim', $rows[0]['message'] === 'Tom & Jerry <3 "quotes"', $rows[0]['message']);
        // Mimic the controller's post-fetch pass (it must no longer html_escape).
        $out = $rows;
        foreach ($out as &$r) { $r['file_url'] = $r['file_url'] ?: null; $r['file_name'] = $r['file_name'] ?: null; }
        unset($r);
        $json = json_encode(['ok' => true, 'messages' => $out]);
        $check('JSON carries raw text, not &amp;', strpos($json, '&amp;') === false);
        $check('JSON carries raw text, not &lt;', strpos($json, '&lt;') === false);
        $decoded = json_decode($json, true);
        $check('client receives exactly what was typed',
               $decoded['messages'][0]['message'] === 'Tom & Jerry <3 "quotes"');

        echo "\n=== G. RECENT LIST ===\n";
        $this->db->query("DELETE FROM chat_messages");
        $mk('personal', 1, 100, 'first');
        $mk('personal', 100, 1, 'latest reply');
        $rec = $this->Chat_model->getRecentPeerChats(1, 50);
        $check('recent shows the thread', count($rec) === 1, count($rec));
        $check('recent names the peer', ($rec[0]['peer_id'] ?? 0) == 100, $rec[0]['peer_id'] ?? '');
        $check('recent shows the latest message',
               ($rec[0]['last_message'] ?? '') === 'latest reply', $rec[0]['last_message'] ?? '');

        echo "\n=== H. PAGE RENDERS ===\n";
        $this->session->set_userdata('user_userid', 1);
        $this->session->set_userdata('user_full_name', 'Root Member');
        $data = [
            'user_id' => 1, 'username' => 'root', 'first_letter' => 'r',
            'title' => 'Chat', 'card_tilte' => 'Chat',
            'chat_fetch_url' => base_url('user/chat/fetch'),
            'chat_send_url' => base_url('user/chat/send'),
            'chat_recent_url' => base_url('user/chat/recent'),
            'team_members' => $this->Chat_model->getPathChatMembers(1),
        ];
        $html = '';
        try {
            $html = $this->load->view('user/member/chat', $data, true);
            $check('chat view renders', strlen($html) > 8000, strlen($html) . ' bytes');
        } catch (Throwable $e) {
            $fail++; echo '  FAIL  render threw: ' . $e->getMessage() . "\n";
        }

        if ($html) {
            $check('mounts the React root', strpos($html, 'id="chatApp"') !== false);
            $check('sidebar + header + right panel included',
                   strpos($html, 'app-container') !== false && strpos($html, 'right-panel') !== false);
            $check('peers injected for the DM picker', strpos($html, 'CHAT_PEERS') !== false);
            $check('peers list is real JSON', preg_match('/CHAT_PEERS = \[\{"id":/', $html) === 1);
            $check('all three rooms present',
                   strpos($html, "'world'") !== false && strpos($html, "'team'") !== false
                   && strpos($html, "'personal'") !== false);
            $check('Shift+Enter newline supported', strpos($html, 'shiftKey') !== false);
            $check('composer is a textarea (multi-line)', strpos($html, "E('textarea'") !== false);
            $check('dark mode handled', strpos($html, 'data-bs-theme="dark"') !== false);

            echo "\n=== I. REGRESSIONS THE OLD PAGE HAD ===\n";
            // The killer: a later :root{--primary} clobbered the admin's theme colour
            // for the whole page (sidebar + header included).
            $chatCss = substr($html, strpos($html, '.chatx'));
            $check('page does NOT redeclare :root (theme no longer clobbered)',
                   !preg_match('/^\s*:root\s*\{/m', $chatCss));
            $check('no hardcoded --primary override',
                   !preg_match('/:root\s*\{[^}]*--primary\s*:/s', $html));
            $check('chat derives from var(--primary)', strpos($html, '--cx-p: var(--primary') !== false);
            $check('no pravatar (user ids no longer leak to a third party)',
                   stripos($html, 'pravatar') === false);
            $check('no dead gear/settings button', stripos($html, 'ph-gear-six') === false);
        }

        echo "\n" . str_repeat('=', 62) . "\n";
        echo ($fail === 0 ? "ALL {$ok} CHECKS PASSED" : "{$ok} passed, {$fail} FAILED") . "\n";
        echo str_repeat('=', 62) . "\n";
    }
}
