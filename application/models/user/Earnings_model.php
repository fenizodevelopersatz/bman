<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Earnings_model extends CI_Model
{

    private function _dbg($tag, $payload = null)
    {
        if (is_array($payload) || is_object($payload)) {
            $payload = json_encode($payload);
        }
        log_message('debug', "[CommissionEngine][$tag] {$payload}");
    }
    public function get_user($user_id)
    {
        return $this->db->select('id,name,email,username,image')
            ->from('users')
            ->where('id', $user_id)
            ->get()->row();
    }

    public function get_kpis($user_id)
    {
        // Wallet (balance + pending)
        $wallet = $this->db->from('user_wallets')->where('user_id', $user_id)->get()->row();
        if (!$wallet) {
            // auto create if missing
            $this->db->insert('user_wallets', [
                'user_id' => $user_id,
                'usd_balance' => 0,
                'usd_pending' => 0,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            $wallet = (object) ['usd_balance' => 0, 'usd_pending' => 0];
        }

        // Today earnings = sum completed transactions today
        $today_sum = $this->db->select('COALESCE(SUM(amount),0) AS total', false)
            ->from('wallet_transactions')
            ->where('user_id', $user_id)
            ->where('status', 'completed')
            ->where('DATE(created_at) = CURDATE()', null, false)
            ->get()->row();

        $today = $today_sum ? (float) $today_sum->total : 0;

        // Streak
        $streak = $this->db->from('user_streaks')->where('user_id', $user_id)->get()->row();
        $streak_percent = $streak ? (int) $streak->streak_bonus_percent : 0;

        return [
            'today' => $today,
            'balance' => (float) $wallet->usd_balance,
            'pending' => (float) $wallet->usd_pending,
            'streak_percent' => $streak_percent,
        ];
    }

    public function get_methods_with_progress($user_id)
    {
        $methods = $this->db->from('earning_methods')
            ->where('is_active', 1)
            ->order_by('sort_order', 'ASC')
            ->get()->result();

        if (!$methods)
            return [];

        $method_ids = array_map(function ($m) {
            return (int) $m->id;
        }, $methods);

        $progressRows = $this->db->from('user_method_progress')
            ->where('user_id', $user_id)
            ->where('progress_date', date('Y-m-d'))
            ->where_in('method_id', $method_ids)
            ->get()->result();

        $progressMap = [];
        foreach ($progressRows as $p) {
            $progressMap[(int) $p->method_id] = (int) $p->completed_count;
        }

        foreach ($methods as $m) {
            $done = $progressMap[(int) $m->id] ?? 0;
            $target = (int) $m->daily_target;
            $percent = ($target > 0) ? round(($done / $target) * 100) : 0;

            $this->_dbg('FULLDETAILREWARD', ['done' => $done, 'target' => $target, 'percent' => $percent, 'progressMap' => $progressMap]);
            $m->done = $done;
            $m->target = $target;
            $m->percent = max(0, min(100, $percent));
        }

        return $methods;
    }

    public function get_tasks_with_claim_status($user_id)
    {
        $tasks = $this->db->from('quick_tasks')
            ->where('is_active', 1)
            ->order_by('sort_order', 'ASC')
            ->get()->result();

        if (!$tasks)
            return [];

        $task_ids = array_map(function ($t) {
            return (int) $t->id;
        }, $tasks);

        $claims = $this->db->from('user_task_claims')
            ->where('user_id', $user_id)
            ->where('claim_date', date('Y-m-d'))
            ->where_in('task_id', $task_ids)
            ->get()->result();

        $claimMap = [];
        foreach ($claims as $c) {
            $claimMap[(int) $c->task_id] = $c->status; // claimed/pending/approved
        }

        foreach ($tasks as $t) {
            $t->today_status = $claimMap[(int) $t->id] ?? null;
            $t->is_done_today = ($t->today_status === 'claimed' || $t->today_status === 'approved');
        }

        return $tasks;
    }

    public function get_method_by_code($code)
    {
        return $this->db->from('earning_methods')
            ->where('code', $code)
            ->where('is_active', 1)
            ->get()->row();
    }

    public function get_task_by_code($code)
    {
        return $this->db->from('quick_tasks')
            ->where('code', $code)
            ->where('is_active', 1)
            ->get()->row();
    }

    public function get_today_method_progress($user_id, $method_id)
    {
        return $this->db->from('user_method_progress')
            ->where('user_id', $user_id)
            ->where('method_id', $method_id)
            ->where('progress_date', date('Y-m-d'))
            ->get()->row();
    }

    public function increment_method_progress($user_id, $method_id)
    {
        $row = $this->get_today_method_progress($user_id, $method_id);
        if ($row) {
            $this->db->where('id', $row->id)->update('user_method_progress', [
                'completed_count' => (int) $row->completed_count + 1,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            return;
        }

        $this->db->insert('user_method_progress', [
            'user_id' => $user_id,
            'method_id' => $method_id,
            'progress_date' => date('Y-m-d'),
            'completed_count' => 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function add_wallet_tx_and_credit($user_id, $tx_type, $source, $amount, $status = 'completed')
    {
        $amount = (float) $amount;
        if ($amount <= 0)
            return;

        $this->db->insert('wallet_transactions', [
            'user_id' => $user_id,
            'tx_type' => $tx_type,
            'source' => $source,
            'amount' => $amount,
            'status' => $status,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // ensure wallet
        $wallet = $this->db->from('user_wallets')->where('user_id', $user_id)->get()->row();
        if (!$wallet) {
            $this->db->insert('user_wallets', [
                'user_id' => $user_id,
                'usd_balance' => 0,
                'usd_pending' => 0,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            $wallet = (object) ['usd_balance' => 0, 'usd_pending' => 0];
        }

        if ($status === 'completed') {
            $this->db->where('user_id', $user_id)->update('user_wallets', [
                'usd_balance' => (float) $wallet->usd_balance + $amount,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        } else {
            $this->db->where('user_id', $user_id)->update('user_wallets', [
                'usd_pending' => (float) $wallet->usd_pending + $amount,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
    }

    public function has_claimed_task_today($user_id, $task_id)
    {
        return $this->db->from('user_task_claims')
            ->where('user_id', $user_id)
            ->where('task_id', $task_id)
            ->where('claim_date', date('Y-m-d'))
            ->count_all_results() > 0;
    }

    public function create_task_claim($user_id, $task_id, $status)
    {
        $this->db->insert('user_task_claims', [
            'user_id' => $user_id,
            'task_id' => $task_id,
            'claim_date' => date('Y-m-d'),
            'status' => $status,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function update_streak_on_checkin($user_id)
    {
        $row = $this->db->from('user_streaks')->where('user_id', $user_id)->get()->row();
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        if (!$row) {
            $this->db->insert('user_streaks', [
                'user_id' => $user_id,
                'streak_days' => 1,
                'streak_bonus_percent' => 5,
                'last_checkin_date' => $today,
            ]);
            return;
        }

        // already checked-in today
        if ($row->last_checkin_date === $today)
            return;

        $days = (int) $row->streak_days;
        if ($row->last_checkin_date === $yesterday) {
            $days++;
        } else {
            $days = 1; // reset
        }

        // simple bonus rule: 5% + (days-1)*2% max 30%
        $bonus = min(30, 5 + max(0, ($days - 1) * 2));

        $this->db->where('user_id', $user_id)->update('user_streaks', [
            'streak_days' => $days,
            'streak_bonus_percent' => $bonus,
            'last_checkin_date' => $today,
        ]);
    }

}
