<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CLI test for immediate stake visibility + PROCESSING→ACTIVE lifecycle.
 *   php index.php stakevisibilitytest run
 * Exercises Staking_model::materializeStake / activateStake / cancelStake and
 * the portfolio query, on a synthetic test user. Cleans up after itself.
 */
class Stakevisibilitytest extends CI_Controller
{
    public function run()
    {
        if (!$this->input->is_cli_request()) show_404();
        $this->load->model('Staking_model');
        $this->load->model('Walletledger_model', 'L');

        $uid = 999999002;
        $pass = 0; $fail = 0;
        $ok = function ($c, $l) use (&$pass, &$fail) { echo ($c?"  ok   ":"  FAIL ").$l."\n"; $c?$pass++:$fail++; };

        // pick a real active package/plan/term so ROI resolves
        $pkg = $this->db->get_where('staking_packages', ['is_active'=>1])->row_array();
        if (!$pkg) { echo "no active package — cannot test\n"; return; }
        $pkgId = (int)$pkg['id'];
        // find a plan+term with ROI configured
        $planCode = 'fixed'; $years = 2;
        $roi = $this->Staking_model->resolveRoi($pkgId, $planCode, $years);
        if (!$roi) { foreach ([['fixed',2],['fixed',3],['regular',2],['combo',2]] as $pt) {
            if ($this->Staking_model->resolveRoi($pkgId, $pt[0], $pt[1])) { $planCode=$pt[0]; $years=$pt[1]; break; }
        }}

        // clean slate + seed an exchange balance (simulating post-swap BMAN)
        $this->db->where('user_id',$uid)->delete('user_stakes');
        $this->db->where('user_id',$uid)->delete('staking_roi_payouts');
        $this->db->where('user_id',$uid)->delete('binary_volume_ledger');
        $this->db->where('user_id',$uid)->delete('wallet_ledger');
        $this->db->where('user_id',$uid)->delete('user_wallets');
        $bman = (float)$pkg['stake_amount'];
        $this->db->insert('user_wallets', ['user_id'=>$uid,'usd_balance'=>0,'exchange_balance'=>$bman]);

        // 1) materialize PROCESSING
        list($m1,$stake) = $this->Staking_model->materializeStake([
            'user_id'=>$uid,'package_id'=>$pkgId,'plan_code'=>$planCode,'duration_years'=>$years,
            'status'=>'processing','lock'=>false,'emit_volume'=>false,
        ]);
        $ok($m1, 'materializeStake PROCESSING ok'.($m1?'':' — '.$stake));
        if (!$m1) { echo "\nStake visibility: {$pass} passed, {$fail} failed.\n"; return; }
        $sid = (int)$stake['stake_id'];

        $row = $this->db->get_where('user_stakes', ['id'=>$sid])->row_array();
        $ok($row && $row['status']==='processing', 'stake row status = processing');
        $ok((int)$this->db->where('stake_id',$sid)->count_all_results('staking_roi_payouts') > 0, 'ROI schedule created');
        $ok((int)$this->db->where('invest_id',$sid)->count_all_results('binary_volume_ledger') === 0, 'no binary volume yet (processing)');
        $ok(abs((float)$this->L->balance($uid,'staking')) < 0.0001, 'staking wallet still 0 (not locked yet)');

        // portfolio query should surface the PROCESSING stake
        $this->db->from('user_stakes us')->join('staking_packages sp','sp.id=us.package_id','left')->where('us.user_id',$uid);
        $pf = $this->db->select('us.*, sp.name')->get()->result_array();
        $ok(count($pf) === 1 && strtolower($pf[0]['status'])==='processing', 'portfolio query returns 1 PROCESSING stake');

        // 2) activate → lock exchange→staking, emit volume, status active
        list($a1,$am) = $this->Staking_model->activateStake($sid, $stake['ref']);
        $ok($a1, 'activateStake ok'.($a1?'':' — '.$am));
        $row = $this->db->get_where('user_stakes', ['id'=>$sid])->row_array();
        $ok($row['status']==='active', 'stake status = active after activation');
        $ok(abs((float)$this->L->balance($uid,'staking') - $bman) < 0.0001, 'staking wallet locked = stake_amount');
        $ok(abs((float)$this->L->balance($uid,'exchange')) < 0.0001, 'exchange wallet drained to 0');
        $ok((int)$this->db->where('invest_id',$sid)->count_all_results('binary_volume_ledger') === 1, 'binary volume emitted on activation');

        // idempotent re-activate
        list($a2) = $this->Staking_model->activateStake($sid, $stake['ref']);
        $ok($a2, 're-activate is idempotent (no error)');
        $ok((int)$this->db->where('invest_id',$sid)->count_all_results('binary_volume_ledger') === 1, 'no duplicate binary volume');

        // 3) cancel path on a fresh processing stake
        list($m2,$stake2) = $this->Staking_model->materializeStake([
            'user_id'=>$uid,'package_id'=>$pkgId,'plan_code'=>$planCode,'duration_years'=>$years,
            'status'=>'processing','lock'=>false,'emit_volume'=>false,
        ]);
        $sid2 = (int)$stake2['stake_id'];
        list($c1) = $this->Staking_model->cancelStake($sid2, 'test');
        $ok($c1, 'cancelStake ok');
        $row2 = $this->db->get_where('user_stakes', ['id'=>$sid2])->row_array();
        $ok($row2['status']==='cancelled', 'cancelled stake status = cancelled');
        $ok((int)$this->db->where('stake_id',$sid2)->where('status','pending')->count_all_results('staking_roi_payouts') === 0, 'pending ROI rows removed on cancel');

        // cleanup
        foreach (['user_stakes'=>'id','staking_roi_payouts'=>'user_id','binary_volume_ledger'=>'user_id','wallet_ledger'=>'user_id','user_wallets'=>'user_id'] as $t=>$col) {
            $this->db->where($col === 'id' ? 'user_id' : $col, $uid)->delete($t);
        }

        echo "\nStake visibility: {$pass} passed, {$fail} failed.\n";
    }
}
