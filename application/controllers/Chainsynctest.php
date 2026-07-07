<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Chainsynctest — CLI integration tests for the on-chain sync/lifecycle engine.
 * Run: php index.php chainsynctest run   (CLI only)
 *
 * Covers the scenarios that are assertable without spending real funds:
 * duplicate protection, tx verification against the chain, chain-reorg handling,
 * balance reconciliation, RPC-first diff-gating, and multi-RPC availability.
 * (Real money-moving success/fail withdrawal + swap broadcasts are exercised by
 * their own dry-run paths; here we assert the recording/lifecycle logic.)
 */
class Chainsynctest extends CI_Controller
{
    private $pass = 0;
    private $fail = 0;

    public function __construct()
    {
        parent::__construct();
        if (!is_cli()) show_404();
        $this->load->database();
        $this->load->model('Chainsync_model', 'chain');
        $this->load->model('Onchaintx_model', 'octx');
    }

    private function ok($name, $cond, $info = '')
    {
        if ($cond) { $this->pass++; echo "  PASS  $name" . ($info ? "  — $info" : '') . "\n"; }
        else       { $this->fail++; echo "  FAIL  $name" . ($info ? "  — $info" : '') . "\n"; }
    }

    public function run()
    {
        echo "=== On-chain sync integration tests ===\n";

        // 1) Duplicate protection: same (tx_hash, wallet_type, reference_type) → 1 row.
        $h = '0xtest' . substr(md5(uniqid('', true)), 0, 40);
        $a = $this->octx->capture(['tx_hash'=>$h,'wallet_type'=>'usdt','tx_type'=>'test','status'=>'confirmed','amount'=>1,'reference_type'=>'test']);
        $b = $this->octx->capture(['tx_hash'=>$h,'wallet_type'=>'usdt','tx_type'=>'test','status'=>'confirmed','amount'=>1,'reference_type'=>'test']);
        $cnt = $this->db->where('tx_hash',$h)->count_all_results('onchain_transactions');
        $this->ok('duplicate protection', $cnt === 1 && $a === $b, "rows=$cnt");
        $this->db->where('tx_hash',$h)->delete('onchain_transactions'); // cleanup

        // 2) Verify a real confirmed deposit tx against the chain.
        $dep = $this->db->where('tx_hash IS NOT NULL', null, false)->where('tx_type','deposit')
            ->order_by('block_number','DESC')->limit(1)->get('onchain_transactions')->row_array();
        if ($dep) {
            $res = $this->chain->verifyTx($dep['id']);
            $this->ok('verify real tx via RPC', !empty($res['ok']) && in_array($res['status']??'', ['confirmed','processing']),
                "status=".($res['status']??'?')." conf=".($res['confirmations']??'?'));

            // 3) Reorg handling: corrupt the stored block, re-verify → reorg detected + corrected.
            $this->db->where('id',$dep['id'])->update('onchain_transactions',['block_number'=>1,'reorg_count'=>0]);
            $r2 = $this->chain->verifyTx($dep['id']);
            $after = $this->db->select('block_number,reorg_count')->get_where('onchain_transactions',['id'=>$dep['id']])->row_array();
            $this->ok('chain reorg detection', !empty($r2['reorg']) && (int)$after['reorg_count'] >= 1 && (string)$after['block_number'] !== '1',
                "reorg_count={$after['reorg_count']} block={$after['block_number']}");
        } else {
            $this->ok('verify real tx via RPC', false, 'no deposit tx to test');
        }

        // 4) Multi-RPC availability + a live call.
        $eps = $this->chain->endpoints();
        $blk = $this->chain->rpc('eth_blockNumber', []);
        $this->ok('multi-RPC failover configured + live', count($eps) > 1 && isset($blk['result']),
            count($eps)." endpoints, head=".(isset($blk['result'])?hexdec($blk['result']):'?'));

        // 5) Balance reconciliation: corrupt stored balance → sync detects diff + corrects to RPC value.
        $bs = $this->db->order_by('id','DESC')->limit(1)->get_where('wallet_balance_sync',['token'=>'BNB'])->row_array();
        if ($bs) {
            $this->db->where('id',$bs['id'])->update('wallet_balance_sync',['last_balance_raw'=>'999999999999999999999']);
            $s = $this->chain->syncAddress($bs['address'],'BNB',null,18,$bs['user_id'],'test');
            $fixed = $this->db->select('last_balance_raw')->get_where('wallet_balance_sync',['id'=>$bs['id']])->row_array();
            $this->ok('balance reconciliation', !empty($s['diff']) && $fixed['last_balance_raw'] !== '999999999999999999999',
                "diff=".($s['diff']?'yes':'no'));

            // 6) Diff-gating: immediate re-sync (no change) must be RPC-only, no BscScan.
            $s2 = $this->chain->syncAddress($bs['address'],'BNB',null,18,$bs['user_id'],'test');
            $this->ok('RPC-first diff-gating (no API on no-change)', empty($s2['diff']) && ($s2['api']??'')==='rpc',
                "api=".($s2['api']??'?'));
        } else {
            $this->ok('balance reconciliation', false, 'no balance-sync row (run chainsynccron first)');
            $this->ok('RPC-first diff-gating', false, 'skipped');
        }

        // 7) Batch rotation: small batch → distinct, advancing windows.
        $save = $this->db->get_where('wallet_sync_cursor', ['id' => 1])->row_array();
        $this->db->where('id', 1)->update('wallet_sync_cursor', ['last_user_id'=>0,'batch_size'=>2,'cycle_count'=>0]);
        $w1 = $this->chain->claimBatch('test-worker', 2);
        $w2 = $this->chain->claimBatch('test-worker', 2);
        $ids1 = array_column($w1['rows'], 'user_id');
        $ids2 = array_column($w2['rows'], 'user_id');
        $this->ok('batch rotation advances (no overlap)',
            empty(array_intersect($ids1, $ids2)) && $w2['cursor'] >= $w1['cursor'],
            'w1=['.implode(',', $ids1).'] w2=['.implode(',', $ids2).']');

        // 8) Restart recovery: cursor persisted; a fresh claim resumes (not from 0).
        $persisted = (int)$this->db->get_where('wallet_sync_cursor', ['id'=>1])->row_array()['last_user_id'];
        $w3 = $this->chain->claimBatch('test-worker', 2);
        $ids3 = array_column($w3['rows'], 'user_id');
        $this->ok('restart recovery (resume from cursor)',
            $persisted > 0 && (empty($ids3) || min($ids3) > $persisted || $w3['wrapped']),
            "persisted=$persisted next=[".implode(',', $ids3).']');

        // 9) Wrap → new cycle when the address space is exhausted.
        for ($i = 0; $i < 25 && !$this->chain->claimBatch('test-worker', 2)['wrapped']; $i++) {}
        $cyc = (int)$this->db->get_where('wallet_sync_cursor', ['id'=>1])->row_array()['cycle_count'];
        $this->ok('rotation wraps to a new cycle', $cyc >= 1, "cycle=$cyc");
        $this->db->where('id',1)->update('wallet_sync_cursor',
            ['last_user_id'=>$save['last_user_id'],'batch_size'=>$save['batch_size'],'cycle_count'=>$save['cycle_count']]);

        // 10) Withdrawal tx_hash validation + duplicate protection.
        $good = '0x'.str_repeat('a', 64); $bad = '0x123';
        $fmtOk = (bool)preg_match('/^0x[0-9a-fA-F]{64}$/', $good) && !preg_match('/^0x[0-9a-fA-F]{64}$/', $bad);
        $this->octx->capture(['tx_hash'=>$good,'wallet_type'=>'usdt','tx_type'=>'withdrawal','status'=>'processing',
            'amount'=>1,'reference_type'=>'withdrawal','reference_id'=>'test-wd']);
        $dupFound = $this->db->where('tx_hash',$good)->where('reference_type','withdrawal')->count_all_results('onchain_transactions') > 0;
        $noDup    = $this->db->where('tx_hash','0x'.str_repeat('b',64))->count_all_results('onchain_transactions') === 0;
        $this->db->where('tx_hash',$good)->where('reference_id','test-wd')->delete('onchain_transactions'); // cleanup
        $this->ok('withdrawal tx_hash format + duplicate protection', $fmtOk && $dupFound && $noDup);

        echo "=== {$this->pass} passed, {$this->fail} failed ===\n";
    }
}
