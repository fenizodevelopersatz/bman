<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class CommissionCalculator extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library(['session','form_validation']);
        $this->load->helper(['url','security','date']);
        $this->load->database();

        // TODO: adapt to your admin auth
        if (!$this->session->userdata('admin_logged_in')) redirect('admin/login');
    }

    public function index() {
        $data['title'] = 'Commission Calculator';
        $data['card_title'] = 'Commission Calculator';
        $data['config'] = $this->db->get_where('commission_config',['id'=>1])->row();
        $this->load->view('admin/calculator/index', $data);
    }

    // ============ LIVE (from DB) ============
    public function live_calc() {
        $user_id = (int)$this->input->post('user_id', true);
        $from    = $this->input->post('from', true) ?: date('Y-m-01');
        $to      = $this->input->post('to', true)   ?: date('Y-m-d');

        if (!$user_id) return $this->_json(false,'Missing user_id');

        $cfg = $this->db->get_where('commission_config', ['id'=>1])->row();
        $basis = $cfg->binary_pair_on ?: 'PV';

        // Summaries by type
        $summary = $this->db->select('type, ROUND(SUM(amount),6) AS usd_total, ROUND(SUM(token_amount),6) AS token_total, COUNT(*) AS rows_count')
            ->from('history')
            ->where('user_id',$user_id)
            ->where('DATE(history_date) >=', $from)
            ->where('DATE(history_date) <=', $to)
            ->group_by('type')->get()->result();

        // ROI rows
        $roi = $this->db->select('DATE(history_date) AS day, ROUND(SUM(amount),6) AS usd, ROUND(SUM(token_amount),6) AS tokens')
            ->from('history')->where('user_id',$user_id)->where('type','profit')
            ->where('DATE(history_date) >=',$from)->where('DATE(history_date) <=',$to)
            ->group_by('DATE(history_date)')->order_by('day','ASC')->get()->result();

        // Direct / Level / Binary / Matching detail
        $details = [];
        foreach (['direct_commission','level_commission','binary_commission','matching_bonus'] as $t) {
            $details[$t] = $this->db->select('id, history_date, amount AS usd, token_amount AS tokens, description, pair_ratio_used, pairs_count, basis, ref_history_id, source_user_id, level_no')
                ->from('history')->where('user_id',$user_id)->where('type',$t)
                ->where('DATE(history_date) >=',$from)->where('DATE(history_date) <=',$to)
                ->order_by('history_date','ASC')->get()->result();
        }

        // Binary “inputs” for transparency on basis
        if (strtoupper($basis)==='PV') {
            $inputs = $this->_sum_leg_volume_live($user_id, $from, $to, 'PV');
        } else {
            $inputs = $this->_sum_leg_volume_live($user_id, $from, $to, 'BV');
        }

        // Totals
        $tot_usd=0; $tot_tok=0;
        foreach($summary as $s){ $tot_usd += (float)$s->usd_total; $tot_tok += (float)$s->token_total; }

        return $this->_json(true,'OK',[
            'range'=>['from'=>$from,'to'=>$to],
            'basis'=>$basis,
            'summary'=>$summary,
            'roi_daily'=>$roi,
            'details'=>$details,
            'binary_inputs'=>$inputs,
            'totals'=>['usd'=>round($tot_usd,6),'token'=>round($tot_tok,6)],
            'config'=>$cfg
        ]);
    }

    // ============ WHAT-IF (no DB writes) ============
    public function whatif_calc() {
        // Inputs
        $user_id = (int)$this->input->post('user_id', true); // optional in what-if
        $basis   = strtoupper($this->input->post('basis', true) ?: 'PV'); // PV|BV
        $ratio   = $this->input->post('ratio', true) ?: '1:1';
        $type    = $this->input->post('pair_type', true) ?: 'percent';    // percent|amount
        $percent = (float)$this->input->post('pair_percent', true);
        $amount  = (float)$this->input->post('pair_amount', true);
        $carry   = $this->input->post('carry_mode', true) ?: 'weak_leg';  // none|weak_leg|both
        $flush   = $this->input->post('flush_rule', true) ?: 'never';     // never|daily|weekly|monthly
        $levels  = (int)$this->input->post('matching_levels', true);
        $mperc   = trim($this->input->post('matching_percents', true) ?: ''); // e.g. "10,5,3"

        // Daily volumes arrays (CSV), e.g. "100,0,50" for 3 days
        $left_csv  = $this->input->post('left_vols', true) ?: '0';
        $right_csv = $this->input->post('right_vols', true) ?: '0';
        $left_vols  = array_map('floatval', array_filter(array_map('trim', explode(',', $left_csv)), 'strlen'));
        $right_vols = array_map('floatval', array_filter(array_map('trim', explode(',', $right_csv)), 'strlen'));
        $days = max(count($left_vols), count($right_vols));
        if ($days==0) return $this->_json(false,'No volumes provided');

        // Align arrays
        for($i=0;$i<$days;$i++){
            if (!isset($left_vols[$i]))  $left_vols[$i]=0;
            if (!isset($right_vols[$i])) $right_vols[$i]=0;
        }

        // Parse ratio
        [$rL,$rR] = array_map('intval', explode(':', $ratio)); if ($rL<=0 || $rR<=0) {$rL=1;$rR=1;}
        $weak_unit = min($rL,$rR);

        $token_rate = $this->_token_rate(); // from token_info()

        // Simulate carries across days (does not touch DB)
        $carryL=0; $carryR=0; $result=[];
        $tot_token=0; $tot_usd=0; $tot_pairs=0;

        for($d=0;$d<$days;$d++){
            $L = $left_vols[$d] + $carryL;
            $R = $right_vols[$d] + $carryR;

            $pairs = floor(min($L/$rL, $R/$rR));
            if ($pairs<=0){
                // update carry
                $carryL = $L; $carryR = $R;
                if ($carry==='none'){ $carryL=0; $carryR=0; }
                if ($carry==='weak_leg'){
                    if ($L<=$R){ $carryR=0; } else { $carryL=0; }
                }
                $result[] = ['day'=>$d+1,'left'=>$L,'right'=>$R,'pairs'=>0,'token'=>0,'usd'=>0,'post_note'=>'no pairs'];
                continue;
            }

            // matched
            $matchedL = $pairs*$rL; $matchedR = $pairs*$rR;
            $remL = $L-$matchedL;  $remR = $R-$matchedR;

            // set carry remainder
            if ($carry==='both'){ $carryL=max(0,$remL); $carryR=max(0,$remR); }
            elseif ($carry==='weak_leg'){
                if ($remL <= $remR){ $carryL=max(0,$remL); $carryR=0; }
                else { $carryR=max(0,$remR); $carryL=0; }
            } else { $carryL=0; $carryR=0; }

            // payout
            if ($type==='percent'){
                $token = ($pairs*$weak_unit*$percent)/100.0;
            } else {
                $token = $pairs * $amount;
            }
            $usd = ($token_rate>0) ? $token/$token_rate : 0;

            $tot_pairs += $pairs; $tot_token += $token; $tot_usd += $usd;
            $result[] = ['day'=>$d+1,'left'=>$L,'right'=>$R,'pairs'=>$pairs,'token'=>round($token,6),'usd'=>round($usd,6)];
        }

        // Matching (what-if): if you input “downline binary token totals” per level, we could apply percents.
        // Here we compute matching on THIS user’s downline daily binary token you provide optionally as CSV per level (L1 only for simplicity).
        $matching_tot = 0; $matching = [];
        $mpercs = array_map('floatval', array_filter(array_map('trim', explode(',',$mperc)), 'strlen'));
        // Optional: posted arrays `child_binary_tokens_L1`, `child_binary_tokens_L2`, ...
        for($lv=1; $lv<=max(1,$levels); $lv++){
            $key = 'child_binary_tokens_L'.$lv;
            $csv = $this->input->post($key, true);
            if (!$csv) continue;
            $tokens = array_sum(array_map('floatval', array_filter(array_map('trim', explode(',',$csv)), 'strlen')));
            $p = isset($mpercs[$lv-1]) ? $mpercs[$lv-1] : 0;
            $bonus_token = $tokens * $p / 100.0;
            $bonus_usd   = ($token_rate>0) ? $bonus_token/$token_rate : 0;
            $matching[] = ['level'=>$lv,'percent'=>$p,'child_tokens'=>$tokens,'bonus_token'=>round($bonus_token,6),'bonus_usd'=>round($bonus_usd,6)];
            $matching_tot += $bonus_usd;
        }

        return $this->_json(true,'OK',[
            'basis'=>$basis,'ratio'=>$ratio,'pair_type'=>$type,'pair_percent'=>$percent,'pair_amount'=>$amount,
            'carry_mode'=>$carry,'flush_rule'=>$flush,
            'binary_daily'=>$result,
            'binary_totals'=>['pairs'=>$tot_pairs,'token'=>round($tot_token,6),'usd'=>round($tot_usd,6)],
            'matching'=>$matching,
            'matching_total_usd'=>round($matching_tot,6),
            'token_rate'=>$token_rate
        ]);
    }

    // ---------- helpers ----------

    private function _json($ok,$msg,$data=null){
        $this->output->set_content_type('application/json')->set_output(json_encode([
            'status'=>$ok,'message'=>$msg,'data'=>$data
        ]));
    }

    private function _token_rate(){
        $ti = token_info();
        return (!empty($ti->currency_value) && $ti->currency_value>0) ? (float)$ti->currency_value : 0;
    }

    /**
     * For transparency: show per-day leg sums from DB by basis.
     * Returns ['days'=>[...], 'left'=>[day=>sum], 'right'=>[day=>sum]]
     */
    private function _sum_leg_volume_live($user_id,$from,$to,$basis='PV'){
        // Build a date list
        $days=[]; $start=strtotime($from); $end=strtotime($to);
        for($t=$start; $t<=$end; $t+=86400){ $days[] = date('Y-m-d',$t); }

        $left_users  = $this->get_leg_users($user_id,'left');
        $right_users = $this->get_leg_users($user_id,'right');

        $left = []; $right = [];

        foreach($days as $d){
            if ($basis==='PV'){
                $left[$d] = $this->_sum_history($left_users,  'mining', $d);
                $right[$d]= $this->_sum_history($right_users, 'mining', $d);
            } else {
                // BV: ROI tokens → use token_amount for day
                $left[$d] = $this->_sum_profit_tokens($left_users,  $d);
                $right[$d]= $this->_sum_profit_tokens($right_users, $d);
            }
        }
        return ['days'=>$days,'left'=>$left,'right'=>$right];
    }

    private function _sum_history($uids,$type,$day){
        if (empty($uids)) return 0;
        $q = $this->db->select('SUM(amount) AS s')->from('history')
            ->where_in('user_id',$uids)->where('type',$type)->where('DATE(history_date)',$day)->get()->row();
        return (float)($q->s ?? 0);
    }
    private function _sum_profit_tokens($uids,$day){
        if (empty($uids)) return 0;
        $q = $this->db->select('SUM(token_amount) AS s')->from('history')
            ->where_in('user_id',$uids)->where('type','profit')->where('DATE(history_date)',$day)->get()->row();
        return (float)($q->s ?? 0);
    }

    // You already have this in your binary engine:
    private function get_leg_users($user_id, $side){
        // EXPECTS you have a function like this already.
        // If not, build from binary_placement table.
        // Example:
        // return $this->Binary_model->get_all_downline_ids($user_id, $side);
        // Here we only return direct leg users as a minimal placeholder:
        $ids = [];
        $rows = $this->db->get_where('binary_placement', ['parent_id'=>$user_id,'position'=>$side])->result();
        foreach($rows as $r){ $ids[] = $r->user_id; }
        return $ids;
    }
}
