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
        // discover real relationships: source U (has sponsor SU, a downline D,
        // and a full leg chain 3 deep: L1 leg child, L2 = L1's child, L3 = L2's
        // child) so the depth-2 boundary itself gets exercised, not just "has a
        // leg". Also an unrelated member X, outside both legs entirely.
        $users = $this->db->select('id')->where('status','1')->order_by('id','ASC')->limit(400)->get('users')->result_array();
        $U=0;$SU=0;$D=0;$X=0;$L1=0;$L2=0;$L3=0;
        foreach ($users as $u) {
            $uid=(int)$u['id']; $sp=$this->svc->directSponsorId($uid); if ($sp<=0) continue;
            $legs=$this->svc->directLegChildIds($uid); if (empty($legs)) continue;
            $chainLeg=0; $chainL2=0; $chainL3=0;
            foreach ($legs as $leg) {
                foreach ($this->svc->directLegChildIds($leg) as $gc) {          // depth 2 from $uid
                    $ggcIds = $this->svc->directLegChildIds($gc);              // depth 3 from $uid
                    if (!empty($ggcIds)) { $chainLeg=$leg; $chainL2=$gc; $chainL3=(int)$ggcIds[0]; break 2; }
                }
            }
            if (!$chainL3) continue;   // need the full 3-deep chain to test the boundary
            $D=0;
            foreach ($users as $d) { $did=(int)$d['id']; if ($did!==$uid && $this->svc->isInDownline($uid,$did)) { $D=$did; break; } }
            if ($D) { $U=$uid; $SU=$sp; $L1=$chainLeg; $L2=$chainL2; $L3=$chainL3; break; }
        }
        foreach ($users as $x) { $xid=(int)$x['id']; if ($xid!==$U && $xid!==$SU && $xid!==$D && !$this->svc->isInDownline($U,$xid)) { $X=$xid; break; } }

        echo "=== Wallet transfer rule tests ===\n";
        echo "  relationships: source=$U  sponsor=$SU  downline=$D  leg-depth1=$L1  leg-depth2=$L2  leg-depth3=$L3  unrelated=$X\n";
        if (!$U || !$SU || !$D || !$X || !$L3) { echo "  (could not find a full relationship set — abort)\n"; return; }

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
        // bonus = left/right binary leg, up to bonusLegDepth() levels down (2026-08-27: widened from 1 to 2)
        $this->assertCode('member bonus→leg depth 1 allowed', $adm('member','bonus',['recipient'=>$L1]), $ALLOW);
        $this->assertCode('member bonus→leg depth 2 allowed', $adm('member','bonus',['recipient'=>$L2]), $ALLOW);
        $this->assertCode('member bonus→leg depth 3 blocked (past the cap)', $adm('member','bonus',['recipient'=>$L3]), 'bonus_only_to_binary_leg_downline');
        $this->assertCode('member bonus→direct SPONSOR blocked', $adm('member','bonus',['recipient'=>$SU]), 'bonus_only_to_binary_leg_downline');
        $this->assertCode('member bonus→unrelated blocked', $adm('member','bonus',['recipient'=>$X]), 'bonus_only_to_binary_leg_downline');
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

    /**
     * Shared-UI support test: preview() (rules+balances, no execution) and
     * detailEnriched() (the shared transaction-details modal payload).
     * Run: php index.php wallettransfertest ui
     */
    public function ui()
    {
        echo "=== shared UI support (preview + detailEnriched) ===\n";

        // find a source with a downline (same discovery as run())
        $users = $this->db->select('id')->where('status','1')->order_by('id','ASC')->limit(400)->get('users')->result_array();
        $U=0;$D=0;
        foreach ($users as $u) {
            $uid=(int)$u['id']; if ($this->svc->directSponsorId($uid)<=0) continue;
            foreach ($users as $d) { $did=(int)$d['id']; if ($did!==$uid && $this->svc->isInDownline($uid,$did)) { $D=$did; break; } }
            if ($D) { $U=$uid; break; }
        }
        echo "  relationships: source=$U downline=$D\n";

        // preview: allowed internal move returns the full shape + balances
        $p = $this->svc->preview(['mode'=>'internal','source_user_id'=>$U,'from_wallet'=>'exchange','to_wallet'=>'bonus','amount'=>'1']);
        $this->assertCode2('preview returns shape (ok/from_balance/to_wallet keys)',
            is_array($p) && array_key_exists('ok',$p) && array_key_exists('from_balance',$p) && array_key_exists('to_wallet',$p),
            'to_wallet='.($p['to_wallet']??'-').' from_balance='.($p['from_balance']??'-').' msg='.($p['message']??''));

        // preview: blocked pair is reported (not thrown)
        $pb = $this->svc->preview(['mode'=>'internal','source_user_id'=>$U,'from_wallet'=>'bonus','to_wallet'=>'exchange','amount'=>'1']);
        $this->assertCode2('preview reports blocked pair', is_array($pb) && $pb['ok']===false && $pb['code']==='internal_source_must_be_exchange',
            'code='.($pb['code']??'-'));

        // preview: member move surfaces recipient info when valid
        if ($D) {
            $pm = $this->svc->preview(['mode'=>'member','source_user_id'=>$U,'from_wallet'=>'exchange','recipient'=>$D,'amount'=>'1']);
            $this->assertCode2('preview member surfaces recipient/kyc flags',
                is_array($pm) && array_key_exists('recipient',$pm) && array_key_exists('kyc_ok',$pm) && array_key_exists('has_transfer_password',$pm),
                'recipient='.(($pm['recipient']['name'] ?? '-')));
        }

        // detailEnriched: latest transfer → header + users(sender/recipient/sponsor/upline) + ledger + audit
        $row = $this->db->select('ref')->order_by('id','DESC')->limit(1)->get('wallet_internal_transfer')->row_array();
        if ($row) {
            $d = $this->svc->detailEnriched($row['ref']);
            $ok = is_array($d) && !empty($d['header']) && isset($d['users']) && array_key_exists('sender',$d['users'])
                  && array_key_exists('sponsor',$d['users']) && array_key_exists('upline',$d['users']) && isset($d['ledger']) && isset($d['audit']);
            $this->assertCode2('detailEnriched('.$row['ref'].') has header+users+ledger+audit', $ok,
                'sender='.(($d['users']['sender']['name'] ?? '-')).' ledgerRows='.(isset($d['ledger'])?count($d['ledger']):0));
        } else {
            echo "  (no transfers yet — skipped detailEnriched)\n";
        }

        // recipient scoping: the pickers must only ever offer VALID recipients
        if ($D) {
            $downIds = $this->svc->downlineIds($U);
            $this->assertCode2('downlineIds includes the known downline',
                in_array($D,$downIds,true) || in_array((string)$D,$downIds,true), 'count='.count($downIds));

            $optsEx = $this->svc->recipientOptions($U,'exchange','',50);
            $exIds  = array_map(function($r){ return (int)$r['id']; }, $optsEx);
            $this->assertCode2('recipientOptions(exchange) = downline only (has D, excludes self)',
                in_array($D,$exIds,true) && !in_array($U,$exIds,true), 'D='.$D.' rows='.count($optsEx));

            $optsBonus = $this->svc->recipientOptions($U,'bonus','',50);
            $bIds  = array_map(function($r){ return (int)$r['id']; }, $optsBonus);
            $legIds = $this->svc->binaryLegDownlineIds($U);
            $sp    = $this->svc->directSponsorId($U);
            $cap   = 2 * ((int)pow(2, $this->svc->bonusLegDepth()) - 1);   // full binary subtree ceiling
            $this->assertCode2('recipientOptions(bonus) = left/right binary leg downline only (depth-capped, never the sponsor)',
                count($optsBonus)<=$cap && !array_diff($bIds,$legIds) && !in_array($sp,$bIds,true),
                'legDownline=['.implode(',',$legIds).'] sponsor='.$sp.' rows=['.implode(',',$bIds).']');
        }

        echo "=== {$this->pass} passed, {$this->fail} failed ===\n";
    }

    /**
     * Emit the exact recipient-picker JSON BOTH panels return for a source user,
     * per wallet — the payload of user/transfer_wallet/search_recipients and of
     * admin/finance/internal-transfers/recipients, side by side.
     * Run: php index.php wallettransfertest pickers 2
     */
    public function pickers($userId = 2)
    {
        $userId = (int)$userId;
        $legs = $this->svc->binaryLegDownline($userId);
        echo "=== recipient pickers for source user #$userId ===\n";
        echo "  binary leg downline (depth ".$this->svc->bonusLegDepth()."): left=[".implode(',', $legs['left'])
             ."]  right=[".implode(',', $legs['right'])
             ."]   direct sponsor=".$this->svc->directSponsorId($userId)." (not a bonus recipient)\n";
        foreach ($this->svc->wallets() as $w) {
            $rows = $this->svc->recipientOptions($userId, $w, '', 20);
            echo "\n-- from_wallet=$w  (rule=".$this->svc->memberRule($w).", ".count($rows)." row(s)) --\n";
            echo "  USER  : ".json_encode(['status'=>'success','rows'=>$rows])."\n";
            $results = [];
            foreach ($rows as $u) {
                $label = '#'.$u['id'].' '.($u['name'] ?: $u['username']).' ('.$u['referral_id'].')';
                if (!empty($u['email'])) $label .= ' · '.$u['email'];
                $results[] = ['id' => (int)$u['id'], 'text' => $label];
            }
            echo "  ADMIN : ".json_encode(['results'=>$results,'pagination'=>['more'=>false]])."\n";
        }
    }

    /** Emit the exact tx_detail endpoint JSON for a ref ('member' → latest member, else latest). */
    public function detailjson($ref = null)
    {
        if ($ref === 'member') { $r = $this->db->select('ref')->where('to_user_id IS NOT NULL', null, false)->order_by('id','DESC')->limit(1)->get('wallet_internal_transfer')->row_array(); $ref = $r['ref'] ?? null; }
        if (!$ref) { $r = $this->db->select('ref')->order_by('id','DESC')->limit(1)->get('wallet_internal_transfer')->row_array(); $ref = $r['ref'] ?? null; }
        $d = $ref ? $this->svc->detailEnriched($ref) : null;
        echo json_encode(['ok'=>(bool)$d, 'data'=>$d]);
    }

    /** Dump live columns for the transfer-related tables. Run: php index.php wallettransfertest schema */
    public function schema()
    {
        foreach (['wallet_internal_transfer','wallet_ledger','wallet_transfer_audit','onchain_transactions','users'] as $t) {
            if (!$this->db->table_exists($t)) { echo "\n### $t — MISSING\n"; continue; }
            echo "\n### $t\n";
            $cols = $this->db->query("SHOW COLUMNS FROM `$t`")->result_array();
            foreach ($cols as $c) { echo "  ".str_pad($c['Field'],26)." ".$c['Type'].($c['Null']==='NO'?' NOT NULL':'').($c['Key']?'  ['.$c['Key'].']':'')."\n"; }
        }
    }
}
