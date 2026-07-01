<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Earnings extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('user/Earnings_model', 'earnings');
        $this->load->database();
    }

    public function index()
    {
        $user_id = (int) $this->session->userdata('user_userid');
        if (!$user_id) {
            redirect('login');
            return;
        }

        $data = [];
        $data['title'] = 'Earnings';

        $data['user'] = $this->earnings->get_user($user_id);

        $kpi = $this->earnings->get_kpis($user_id);
        $data['kpi_today'] = $kpi['today'];
        $data['kpi_balance'] = $kpi['balance'];
        $data['kpi_pending'] = $kpi['pending'];
        $data['kpi_streak_percent'] = $kpi['streak_percent'];

        $data['methods'] = $this->earnings->get_methods_with_progress($user_id);
        $data['tasks'] = $this->earnings->get_tasks_with_claim_status($user_id);

        $this->load->view('user/member/earn_more', $data);
    }


    public function do_method($code)
    {
        $user_id = (int) $this->session->userdata('user_userid');
        if (!$user_id) {
            redirect('login');
            return;
        }

        $code = strtolower(trim($code));
        $method = $this->earnings->get_method_by_code($code);
        if (!$method) {
            $this->session->set_flashdata('error', 'Invalid method.');
            redirect('user/earnings');
            return;
        }

        // get today progress
        $progress = $this->earnings->get_today_method_progress($user_id, (int) $method->id);
        $done = (int) ($progress->completed_count ?? 0);

        if ($done >= (int) $method->daily_target) {
            $this->session->set_flashdata('error', 'Daily target completed for ' . $method->title);
            redirect('user/earnings');
            return;
        }

        // simulate 1 completion
        $this->earnings->increment_method_progress($user_id, (int) $method->id);

        // give small reward per completion (reward_usd / target)
        $perUnit = 0;
        if ((int) $method->daily_target > 0) {
            $perUnit = round(((float) $method->reward_usd) / (int) $method->daily_target, 2);
        }
        if ($perUnit <= 0)
            $perUnit = 0.10; // fallback

        $this->earnings->add_wallet_tx_and_credit($user_id, 'earn', $code, $perUnit, 'completed');

        $this->session->set_flashdata('success', 'Progress updated +$' . number_format($perUnit, 2));
        redirect('user/earnings');
    }

    public function claim_task($code)
    {
        $user_id = (int) $this->session->userdata('user_userid');
        if (!$user_id) {
            redirect('login');
            return;
        }

        $code = strtolower(trim($code));
        $task = $this->earnings->get_task_by_code($code);
        if (!$task) {
            $this->session->set_flashdata('error', 'Invalid task.');
            redirect('user/earnings');
            return;
        }

        if ($task->action_type !== 'claim') {
            $this->session->set_flashdata('error', 'This task is not claimable.');
            redirect('user/earnings');
            return;
        }

        $already = $this->earnings->has_claimed_task_today($user_id, (int) $task->id);
        if ($already) {
            $this->session->set_flashdata('error', 'Already claimed today.');
            redirect('user/earnings');
            return;
        }

        $this->earnings->create_task_claim($user_id, (int) $task->id, 'claimed');
        $this->earnings->update_streak_on_checkin($user_id); // updates streak data
        $this->earnings->add_wallet_tx_and_credit($user_id, 'bonus', $code, (float) $task->reward_usd, 'completed');

        $this->session->set_flashdata('success', 'Task claimed +$' . number_format((float) $task->reward_usd, 2));
        redirect('user/earnings');
    }

    public function verify_task($code)
    {
        $user_id = (int) $this->session->userdata('user_userid');
        if (!$user_id) {
            redirect('login');
            return;
        }

        $code = strtolower(trim($code));
        $task = $this->earnings->get_task_by_code($code);
        if (!$task) {
            $this->session->set_flashdata('error', 'Invalid task.');
            redirect('user/earnings');
            return;
        }

        if ($task->action_type !== 'verify') {
            $this->session->set_flashdata('error', 'This task is not verifiable.');
            redirect('user/earnings');
            return;
        }

        $already = $this->earnings->has_claimed_task_today($user_id, (int) $task->id);
        if ($already) {
            $this->session->set_flashdata('error', 'Already submitted today.');
            redirect('user/earnings');
            return;
        }

        // mark as pending (admin will approve later)
        $this->earnings->create_task_claim($user_id, (int) $task->id, 'pending');

        $this->session->set_flashdata('success', 'Task submitted for verification.');
        redirect('user/earnings');
    }

}
