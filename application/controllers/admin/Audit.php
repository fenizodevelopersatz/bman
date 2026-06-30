<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Audit extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library(['session']);
        $this->load->helper(['url','security']);
        $this->load->model('Reports_model');
        $this->load->model('Admin_model');
    }
    // GET /admin/commission-audit?user_id=42&from=2025-08-01&to=2025-08-28
    public function commission_audit() {
        $user_id = (int)$this->input->get('user_id', true);
        $from    = $this->input->get('from', true) ?: date('Y-m-01');
        $to      = $this->input->get('to', true)   ?: date('Y-m-d');

        if (!$user_id) {
            $this->output->set_content_type('application/json')
                         ->set_output(json_encode(['status'=>false,'message'=>'Missing user_id']));
            return;
        }

        $cfg = $this->Reports_model->get_config();
        $user = $this->db->select('id, username, email, sponsor_id, status')->from('users')->where('id',$user_id)->get()->row();

        // summaries
        $summary   = $this->Reports_model->history_summary_by_type($user_id, $from, $to);
        $daily     = $this->Reports_model->history_daily_breakdown($user_id, $from, $to);

        // detail rows
        $roi       = $this->Reports_model->roi_rows($user_id, $from, $to);
        $direct    = $this->Reports_model->direct_rows($user_id, $from, $to);
        $level     = $this->Reports_model->level_rows($user_id, $from, $to);
        $binary    = $this->Reports_model->binary_rows($user_id, $from, $to);
        $matching  = $this->Reports_model->matching_rows($user_id, $from, $to);
        $others    = $this->Reports_model->other_bonus_rows($user_id, $from, $to);

        // carry status
        $carry     = $this->Reports_model->carry_row($user_id);

        // totals
        $tot_usd=0; $tot_tok=0;
        foreach ($summary as $s) { $tot_usd += (float)$s->usd_total; $tot_tok += (float)$s->token_total; }

        $out = [
            'status'   => true,
            'user'     => $user,
            'range'    => ['from'=>$from,'to'=>$to],
            'config'   => [
                'binary_pair_on'       => $cfg->binary_pair_on ?? null,
                'binary_pair_type'     => $cfg->binary_pair_type ?? null,
                'binary_pair_percent'  => $cfg->binary_pair_percent ?? null,
                'binary_pair_amount'   => $cfg->binary_pair_amount ?? null,
                'binary_pair_ratio'    => $cfg->binary_pair_ratio ?? null,
                'binary_carry_forward' => $cfg->binary_carry_forward ?? null,
                'binary_flush_rule'    => $cfg->binary_flush_rule ?? null,
                'binary_daily_cap'     => $cfg->binary_daily_cap ?? null,
                'payout_threshold_min' => $cfg->payout_threshold_min ?? null,
                'matching_status'      => $cfg->matching_bonus_status ?? null,
                'matching_levels'      => $cfg->matching_levels ?? null,
                'matching_percents'    => $cfg->matching_percents ?? null,
                'direct_status'        => $cfg->direct_commission_status ?? null,
                'level_status'         => $cfg->level_commission_status ?? null,
            ],
            'summary_by_type' => $summary,
            'daily_breakdown' => $daily,
            'details' => [
                'roi'       => $roi,
                'direct'    => $direct,
                'level'     => $level,
                'binary'    => $binary,
                'matching'  => $matching,
                'others'    => $others,
            ],
            'carry'    => $carry,
            'totals'   => ['usd'=>round($tot_usd,6),'token'=>round($tot_tok,6)],
        ];

        $this->output->set_content_type('application/json')->set_output(json_encode($out));
    }
}
