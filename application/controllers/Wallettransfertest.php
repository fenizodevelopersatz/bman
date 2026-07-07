<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Wallettransfertest — CLI rule tests for the centralized transfer service.
 * Run: php index.php wallettransfertest run   (CLI only)
 *
 * Asserts validate() against REAL sponsor relationships (via=admin, which skips
 * only the User-Panel KYC/transfer-password gates; every wallet/downline/sponsor/
 * balance rule still applies). "Allowed" directions pass the rule and then hit
 * the balance floor, so the accepted codes for an allowed move are ok OR
 * insufficient_balance.
 */
class Wallettransfertest extends CI_Controller
{
    private $pass = 0; private $fail = 0;

    public function __construct()
    {
        parent::__construct();
        if (!is_cli()) show_404();
        $this->load->database();
        $this->load->model('wallet/Wallettransferservice_model', 'svc');
    }

    private function assertCode($name, $ctx, $expected)
    {
        $r = $this->svc->validate($ctx);
        $got = $r['code'];
        $ok = is_array($expected) ? in_array($got, $expected, true) : ($got === $expected);
        if ($ok) { $this->pass++; echo "  PASS  $name  (code=$got)\n"; }
        else     { $this->fail++; echo "  FAIL  $name  (got=$got, expected=".(is_array($expected)?implode('|',$expected):$expected).")\n"; }
    }

    public function run()
    {
        // discover real relationships: source U (has sponsor SU + a downline D), unrelated X
        $users = $this->db->select('id')->where('status','1')->order_by('id','ASC')->limit(400)->get('users')->result_array();
        $U=0;$SU=0;$D=0;$X=0;
        foreach ($users as $u) {
            $uid=(int)$u['id']; $sp=$this->svc->directSponsorId($uid); if ($sp<=0) continue;
            foreach ($users as $d) { $did=(int)$d['id']; if ($did!==$uid && $this->svc->isInDownline($uid,$did)) { $D=$did; break; } }
            if ($D) { $U=$uid; $SU=$sp; break; }
        }
        foreach ($users as $x) { $xid=(int)$x['id']; if ($xid!==$U && $xid!==$SU && $xid!==$D && !$this->svc->isInDownline($U,$xid)) { $X=$xid; break; } }

        echo "=== Wallet transfer rule tests ===\n";
        echo "  relationships: source=$U  sponsor=$SU  downline=$D  unrelated=$X\n";
        if (!$U || !$SU || !$D || !$X) { echo "  (could not find a full relationship set — abort)\n"; return; }

        $adm = function($m,$from,$extra=[]) use ($U) { return array_merge(['mode'=>$m,'source_user_id'=>$U,'from_wallet'=>$from,'amount'=>'1','via'=>'admin'],$extra); };
        $ALLOW = ['ok','insufficient_balance'];

        // internal (own wallets) — Exchange source-only
        $this->assertCode('internal exchange→bonus allowed',   $adm('internal','exchange',['to_wallet'=>'bonus']),   $ALLOW);
        $this->assertCode('internal exchange→earning allowed', $adm('internal','exchange',['to_wallet'=>'earning']), $ALLOW);
        $this->assertCode('internal exchange→staking allowed', $adm('internal','exchange',['to_wallet'=>'staking']), $ALLOW);
        $this->assertCode('internal exchange→exchange blocked', $adm('internal','exchange',['to_wallet'=>'exchange']), 'internal_pair_not_allowed');
        $this->assertCode('internal bonus→exchange blocked',    $adm('internal','bonus',['to_wallet'=>'exchange']),   'internal_source_must_be_exchange');
        $this->assertCode('internal earning→bonus blocked',     $adm('internal','earning',['to_wallet'=>'bonus']),    'internal_source_must_be_exchange');
        $this->assertCode('internal staking→exchange blocked',  $adm('internal','staking',['to_wallet'=>'exchange']), 'internal_source_must_be_exchange');

        // member transfers
        $this->assertCode('member exchange→downline allowed', $adm('member','exchange',['recipient'=>$D]), $ALLOW);
        $this->assertCode('member earning→downline allowed',  $adm('member','earning',['recipient'=>$D]),  $ALLOW);
        $this->assertCode('member staking→downline allowed',  $adm('member','staking',['recipient'=>$D]),  $ALLOW);
        $this->assertCode('member exchange→NON-downline blocked', $adm('member','exchange',['recipient'=>$X]), 'recipient_not_in_downline');
        $this->assertCode('member bonus→direct-sponsor allowed',  $adm('member','bonus',['recipient'=>$SU]), $ALLOW);
        $this->assertCode('member bonus→downline (not sponsor) blocked', $adm('member','bonus',['recipient'=>$D]), 'bonus_only_to_sponsor');
        $this->assertCode('member bonus→unrelated blocked', $adm('member','bonus',['recipient'=>$X]), 'bonus_only_to_sponsor');
        $this->assertCode('member exchange→self blocked', $adm('member','exchange',['recipient'=>$U]), 'self_transfer');

        // amount / precision / wallet validity
        $this->assertCode('zero amount blocked', $adm('internal','exchange',['to_wallet'=>'bonus','amount'=>'0']), 'invalid_amount');
        $this->assertCode('precision blocked', $adm('internal','exchange',['to_wallet'=>'bonus','amount'=>'1.123456789']), 'precision');
        $this->assertCode('usdt from-wallet blocked', $adm('internal','usdt',['to_wallet'=>'bonus']), 'invalid_from_wallet');

        echo "=== {$this->pass} passed, {$this->fail} failed ===\n";
    }

    /** Real execute + idempotency test (funds a test user, moves, re-runs, cleans up). */
    public function exec()
    {
        $this->load->model('Walletledger_model', 'L');
        $U = 247;
        echo "=== execute + idempotency (source $U) ===\n";
        $ex0 = $this->L->balance($U,'exchange'); $bo0 = $this->L->balance($U,'bonus'); // pre-test
        $this->L->credit($U, 'exchange', '5', 'admin_adjustment', ['reference_id'=>'wt-exec-test','description'=>'test fund']);
        $exBefore = $this->L->balance($U,'exchange'); $boBefore = $this->L->balance($U,'bonus');
        $key = 'itest-'.substr(bin2hex(random_bytes(4)),0,8);

        $r1 = $this->svc->execute(['mode'=>'internal','source_user_id'=>$U,'from_wallet'=>'exchange','to_wallet'=>'bonus',
            'amount'=>'2','via'=>'admin','actor_id'=>1,'idempotency_key'=>$key]);
        $this->assertCode2('execute internal exchange→bonus', !empty($r1['ok']), 'ref='.($r1['ref']??'-'));
        $exAfter = $this->L->balance($U,'exchange'); $boAfter = $this->L->balance($U,'bonus');
        $this->assertCode2('balances moved (-2 exchange / +2 bonus)',
            bccomp(bcsub($exBefore,$exAfter,8),'2',8)===0 && bccomp(bcsub($boAfter,$boBefore,8),'2',8)===0,
            "ex $exBefore→$exAfter bonus $boBefore→$boAfter");

        $r2 = $this->svc->execute(['mode'=>'internal','source_user_id'=>$U,'from_wallet'=>'exchange','to_wallet'=>'bonus',
            'amount'=>'2','via'=>'admin','actor_id'=>1,'idempotency_key'=>$key]);
        $ex2 = $this->L->balance($U,'exchange');
        $this->assertCode2('idempotent re-run (same ref, no double debit)',
            !empty($r2['idempotent']) && ($r2['ref']??'')===($r1['ref']??'') && bccomp($ex2,$exAfter,8)===0, 'ref='.($r2['ref']??'-'));

        // cleanup: restore exchange + bonus to their exact pre-test values
        $this->L->debit($U, 'exchange', bcsub($this->L->balance($U,'exchange'), $ex0, 8), 'admin_adjustment', ['reference_id'=>'wt-exec-cleanup','description'=>'test cleanup','allow_overdraw'=>true]);
        $this->L->debit($U, 'bonus',    bcsub($this->L->balance($U,'bonus'), $bo0, 8),    'admin_adjustment', ['reference_id'=>'wt-exec-cleanup','description'=>'test cleanup','allow_overdraw'=>true]);
        echo "  (test balances restored: exchange=$ex0 bonus=$bo0)\n=== {$this->pass} passed, {$this->fail} failed ===\n";
    }

    private function assertCode2($name, $cond, $info='')
    {
        if ($cond) { $this->pass++; echo "  PASS  $name  — $info\n"; }
        else { $this->fail++; echo "  FAIL  $name  — $info\n"; }
    }
}
