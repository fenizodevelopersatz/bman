<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Chat_service
{
    private $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model('Chat_model');
        $this->CI->load->database();
    }

    public function sanitize_room($room)
    {
        $allowed = ['world', 'team', 'personal'];
        return in_array($room, $allowed, true) ? $room : 'personal';
    }

    public function clean_message($msg)
    {
        $msg = trim((string) $msg);
        $msg = preg_replace("/\s+/", " ", $msg);
        if (mb_strlen($msg) > 500) {
            $msg = mb_substr($msg, 0, 500);
        }
        return $msg;
    }

    public function send($room, $user_id, $username, $message)
    {
        $room = $this->sanitize_room($room);
        $message = $this->clean_message($message);

        if (!$user_id || $username === '' || $message === '') {
            return ['ok' => false, 'message' => 'Missing fields'];
        }

        $data = [
            'room' => $room,
            'user_id' => (int) $user_id,
            'username' => $username,
            'message' => $message,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $ok = $this->CI->Chat_model->insert_message($data);
        if (!$ok) {
            return ['ok' => false, 'message' => 'DB insert failed'];
        }

        return ['ok' => true, 'id' => $this->CI->db->insert_id()];
    }

    public function fetch($room, $after_id = 0, $limit = 50)
    {
        $room = $this->sanitize_room($room);
        $rows = $this->CI->Chat_model->fetch_messages($room, (int) $after_id, (int) $limit);

        // Escape output safe (frontend renders text)
        foreach ($rows as &$r) {
            $r['username'] = html_escape($r['username']);
            $r['message'] = html_escape($r['message']);
        }

        return ['ok' => true, 'room' => $room, 'messages' => $rows];
    }
}
