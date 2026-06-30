<?php
class Support_model extends CI_Model
{

    public function __construct()
    {
        $this->load->database();
    }

    public function get_info($limit, $start, $from_date, $to_date, $client_filter, $agent_filter, $filter_by)
    {


        if (!empty($from_date) && !empty($to_date)) {
            $this->db->where('date(date) >=', $from_date);
            $this->db->where('date(date) <=', $to_date);
        }

        if (!empty($client_filter) && is_array($client_filter)) {
            $this->db->where_in('user_id', $client_filter);
        } elseif (!empty($client_filter)) {
            $this->db->where('user_id', $client_filter);
        }

        // if (!empty($agent_filter) && is_array($agent_filter)) {
        //     $this->db->where_in('type', $agent_filter);
        // } elseif (!empty($agent_filter)) { 
        //     $this->db->where('type', $agent_filter);
        // }


        if ($filter_by != "all_ticket" && $filter_by != "new_ticket") {
            $this->db->where('status', $filter_by);
        }

        if ($filter_by == "new_ticket") {
            $today = date('Y-m-d');
            $this->db->where('date(date) =', $today);
        }

        $this->db->limit($limit, $start);
        $query = $this->db->get('support');
        return $query->result_array();
    }

    public function get_count($from_date, $to_date, $client_filter, $agent_filter, $filter_by)
    {


        if (!empty($from_date) && !empty($to_date)) {
            $this->db->where('date(date) >=', $from_date);
            $this->db->where('date(date) <=', $to_date);
        }

        if (!empty($client_filter) && is_array($client_filter)) {
            $this->db->where_in('user_id', $client_filter);
        } elseif (!empty($client_filter)) {
            $this->db->where('user_id', $client_filter);
        }

        // if (!empty($agent_filter) && is_array($agent_filter)) {
        //     $this->db->where_in('type', $agent_filter);
        // } elseif (!empty($agent_filter)) { 
        //     $this->db->where('type', $agent_filter);
        // }

        if ($filter_by != "all_ticket" && $filter_by != "new_ticket") {
            $this->db->where('status', $filter_by);
        }

        if ($filter_by == "new_ticket") {
            $today = date('Y-m-d');
            $this->db->where('date(date) =', $today);
        }

        $query = $this->db->get('support');
        return $query->num_rows();
    }

    // ✅ COUNT for modern card UI
    public function count_cards_for_user($user_id, $q = '', $tab = 'ALL')
    {
        $this->db->from('support s');
        $this->db->where('s.user_id', (int) $user_id);

        $tab = strtoupper($tab ?: 'ALL');
        if ($tab === 'PENDING')
            $this->db->where('s.status', 0);
        if ($tab === 'OPEN')
            $this->db->where('s.status', 1);
        if ($tab === 'CLOSED')
            $this->db->where('s.status', 2);
        if ($tab === 'NEW_TODAY')
            $this->db->where('DATE(s.date) =', date('Y-m-d'));

        if ($q !== '') {
            $this->db->group_start()
                ->like('s.ticket_id', $q)
                ->or_like('s.subject', $q)
                ->or_like('s.discription', $q)
                ->group_end();
        }

        return (int) $this->db->count_all_results();
    }

    // ✅ LIST for modern card UI (includes last message + updated_at)
    public function list_cards_for_user($user_id, $limit, $offset, $q = '', $tab = 'ALL')
    {
        // Subquery to get last message per ticket
        $lastMsgSub = "
            SELECT sm.ticket_id, sm.message, sm.created_date, sm.admin
            FROM support_message sm
            INNER JOIN (
                SELECT ticket_id, MAX(created_date) AS max_date
                FROM support_message
                GROUP BY ticket_id
            ) x ON x.ticket_id = sm.ticket_id AND x.max_date = sm.created_date
        ";

        $this->db->select("
            s.id,
            s.ticket_id,
            s.subject,
            s.discription,
            s.status,
            s.ticket_status,
            s.date,
            s.email,
            lm.message AS last_msg,
            lm.created_date AS updated_at,
            lm.admin AS last_by_admin
        ", false);

        $this->db->from('support s');
        $this->db->join("($lastMsgSub) lm", "lm.ticket_id = s.ticket_id", "left", false);

        $this->db->where('s.user_id', (int) $user_id);

        $tab = strtoupper($tab ?: 'ALL');
        if ($tab === 'PENDING')
            $this->db->where('s.status', 0);
        if ($tab === 'OPEN')
            $this->db->where('s.status', 1);
        if ($tab === 'CLOSED')
            $this->db->where('s.status', 2);
        if ($tab === 'NEW_TODAY')
            $this->db->where('DATE(s.date) =', date('Y-m-d'));

        if ($q !== '') {
            $this->db->group_start()
                ->like('s.ticket_id', $q)
                ->or_like('s.subject', $q)
                ->or_like('s.discription', $q)
                ->group_end();
        }

        $this->db->order_by('COALESCE(lm.created_date, s.date)', 'DESC', false);
        $this->db->limit((int) $limit, (int) $offset);

        $rows = $this->db->get()->result_array();

        // ✅ map status into labels used by UI
        foreach ($rows as &$r) {
            $st = (string) $r['status'];
            if ($st === '0')
                $r['status_label'] = 'PENDING';
            else if ($st === '1')
                $r['status_label'] = 'OPEN';
            else
                $r['status_label'] = 'CLOSED';

            if (empty($r['last_msg']))
                $r['last_msg'] = $r['discription'];
            if (empty($r['updated_at']))
                $r['updated_at'] = $r['date'];
        }

        return $rows;
    }

    public function get_user_ticket_count($user_id, $filter_by, $q = '')
    {
        $this->_apply_user_filters($user_id, $filter_by, $q);
        return (int) $this->db->count_all_results($this->table);
    }

    // public function get_user_tickets($user_id, $filter_by, $q, $limit, $offset)
    // {
    //     $this->_apply_user_filters($user_id, $filter_by, $q);

    //     $rows = $this->db
    //         ->order_by('id', 'DESC')
    //         ->limit($limit, $offset)
    //         ->get($this->table)
    //         ->result_array();

    //     // ✅ return raw fields needed by frontend
    //     $items = [];
    //     foreach ($rows as $r) {
    //         $items[] = [
    //             'id' => (int) $r['id'],
    //             'ticket_id' => $r['ticket_id'] ?? '',
    //             'subject' => $r['subject'] ?? '',
    //             'category' => $r['category'] ?? '',
    //             'status' => (int) ($r['status'] ?? 0),      // 0/1/2
    //             'priority' => $r['priority'] ?? '',         // if you store
    //             'updated_at' => !empty($r['date']) ? $r['date'] : '',
    //             'last_msg' => $this->_get_last_message_text($r), // fallback
    //         ];
    //     }

    //     return $items;
    // }


    public function get_user_counts($user_id)
    {
        $all = $this->db->where('user_id', $user_id)->count_all_results($this->table);

        $pending = $this->db->where(['user_id' => $user_id, 'status' => 0])->count_all_results($this->table);
        $open = $this->db->where(['user_id' => $user_id, 'status' => 1])->count_all_results($this->table);
        $closed = $this->db->where(['user_id' => $user_id, 'status' => 2])->count_all_results($this->table);

        // ✅ New Today
        $today = date('Y-m-d');
        $new_today = $this->db
            ->where('user_id', $user_id)
            ->where('DATE(date)', $today, false)
            ->count_all_results($this->table);

        return [
            'all' => $all,
            'pending' => $pending,
            'open' => $open,
            'closed' => $closed,
            'new_today' => $new_today,
        ];
    }


    public function get_user_tickets($user_id, $filter_by, $q, $limit, $offset)
    {
        $this->_apply_user_filters($user_id, $filter_by, $q);

        $rows = $this->db
            ->order_by('id', 'DESC')
            ->limit($limit, $offset)
            ->get($this->table)
            ->result_array();

        // ✅ return raw fields needed by frontend
        $items = [];
        foreach ($rows as $r) {
            $items[] = [
                'id' => (int) $r['id'],
                'ticket_id' => $r['ticket_id'] ?? '',
                'subject' => $r['subject'] ?? '',
                'category' => $r['category'] ?? '',
                'status' => (int) ($r['status'] ?? 0),      // 0/1/2
                'priority' => $r['priority'] ?? '',         // if you store
                'updated_at' => !empty($r['date']) ? $r['date'] : '',
                'last_msg' => $this->_get_last_message_text($r), // fallback
            ];
        }

        return $items;
    }

    // ✅ FAQs from your table
    public function get_active_faqs()
    {
        return $this->db
            ->where('status', 1)
            ->order_by('id', 'DESC')
            ->get('faqs')
            ->result();
    }

    private function _apply_user_filters($user_id, $filter_by, $q = '')
    {
        $this->db->from($this->table);
        $this->db->where('user_id', (int) $user_id);

        // filter_by: all_ticket | new_ticket | 0 | 1 | 2
        if ($filter_by === 'new_ticket') {
            $today = date('Y-m-d');
            $this->db->where('DATE(date)', $today, false);
        } elseif ($filter_by !== '' && $filter_by !== null && $filter_by !== 'all_ticket') {
            // numeric status
            if (is_numeric($filter_by)) {
                $this->db->where('status', (int) $filter_by);
            }
        }

        // search
        if ($q !== '') {
            $this->db->group_start()
                ->like('subject', $q)
                ->or_like('ticket_id', $q)
                ->or_like('category', $q)
                ->group_end();
        }
    }

    private function _get_last_message_text($ticketRow)
    {
        // If you have a separate messages table, replace this logic.
        // Fallback: use ticket description/message
        if (!empty($ticketRow['discription']))
            return $ticketRow['discription'];
        if (!empty($ticketRow['ticket_discription']))
            return $ticketRow['ticket_discription'];
        if (!empty($ticketRow['message']))
            return $ticketRow['message'];
        return '';
    }
}
?>