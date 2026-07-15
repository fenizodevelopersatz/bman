<?php defined('BASEPATH') OR exit('No direct script access allowed');

// CI3 only auto-includes the one controller file matching the routed class;
// since this extends a sibling controller in a different file, it must be
// required explicitly before the class declaration below.
require_once APPPATH . 'controllers/BinaryMatchingPayoutCron.php';

/**
 * CLI smoke test for the binary matching on-chain payout pipeline. Run:
 *   php index.php binarymatchingpayouttest run
 *
 * Exercises enqueue -> treasury precheck -> dry-run broadcast -> confirm
 * against one synthetic payout row. Deliberately bypasses Stakingmatching_model
 * itself (already proven, out of scope here — see Ceilingwallettest.php for
 * that same "don't re-test the proven engine" convention) by seeding a
 * staking_matching_payouts row directly.
 *
 * Extends BinaryMatchingPayoutCron so it can call phases (b)/(c)/(d) directly
 * without ever invoking phase (a) (the real engine run) against live data, and
 * passes user_id-scoped opts throughout so this test can never sweep up or
 * dry-run-process any real user's pending payouts alongside its own.
 */
class Binarymatchingpayouttest extends BinaryMatchingPayoutCron
{
    /** $mode is unused here (this test only ever runs phases b/c/d once) but
     * must stay in the signature to match the parent after its watch-mode addition. */
    public function run($mode = null)
    {
        if (!$this->input->is_cli_request()) show_404();

        $this->load->model('Onchaintx_model', 'octx');

        $uid = 999999002; // synthetic test user, unlikely to exist
        $pass = 0; $fail = 0;
        $ok = function ($cond, $label) use (&$pass, &$fail) {
            echo ($cond ? "  ok   " : "  FAIL ") . $label . "\n";
            $cond ? $pass++ : $fail++;
        };

        $this->_cleanup($uid);

        // 1) synthetic custodial wallet (the payout's on-chain destination)
        $this->db->insert('user_wallet', [
            'user_id' => $uid, 'wallet_address' => '0x' . str_repeat('a1', 20),
            'mnemonic' => '', 'private_key' => '',
        ]);

        // 2) synthetic matching payout row — bypasses the engine directly
        $runRef = 'TEST-RUN-BMP';
        $this->db->insert('staking_matching_payouts', [
            'user_id' => $uid, 'matched_volume' => 100, 'total_percent' => 10,
            'earning_amount' => 8, 'staking_amount' => 2,
            'left_before' => 100, 'right_before' => 150, 'run_ref' => $runRef,
        ]);
        $smpId = (int)$this->db->insert_id();

        // 3) enqueue (phase b) — scoped to our synthetic user only
        $enqueue = $this->_enqueuePayouts(['user_id' => $uid]);
        $ok($enqueue['enqueued'] === 1, 'enqueue: exactly one payout enqueued (got ' . $enqueue['enqueued'] . ')');

        $row = $this->db->where(['reference_type' => 'staking_matching_payout', 'reference_id' => (string)$smpId])
                        ->get('blockchain_payout_queue')->row_array();
        $ok((bool)$row, 'enqueue: blockchain_payout_queue row exists for our synthetic payout');
        $ok($row && $row['status'] === 'PENDING', 'enqueue: status == PENDING');
        $ok($row && abs((float)$row['amount'] - 10) < 0.0001, 'enqueue: amount == earning_amount + staking_amount (10)');
        if (!$row) { echo "\nBinary Matching Payout: cannot continue, enqueue failed.\n"; $this->_cleanup($uid); return; }

        // 4) drain (phase c), forced dry-run — never touches the real chain/treasury
        $drain = $this->_drainPending(['force_dry_run' => true, 'user_id' => $uid]);
        $ok($drain['sent'] === 1, 'drain: dry-run broadcast sent (got ' . $drain['sent'] . ')');

        $row = $this->db->where('id', $row['id'])->get('blockchain_payout_queue')->row_array();
        $ok($row['status'] === 'PROCESSING', 'drain: status == PROCESSING');
        $ok(strpos((string)$row['tx_hash'], 'DRYRUN-') === 0, 'drain: tx_hash starts with DRYRUN-');

        // 5) confirm (phase d) — DRYRUN hashes auto-confirm regardless of chain reachability
        $confirm = $this->_confirmProcessing(['user_id' => $uid]);
        $ok($confirm['confirmed'] === 1, 'confirm: payout confirmed (got ' . $confirm['confirmed'] . ')');

        $row = $this->db->where('id', $row['id'])->get('blockchain_payout_queue')->row_array();
        $ok($row['status'] === 'CONFIRMED', 'confirm: status == CONFIRMED');
        $ok(!empty($row['onchain_tx_id']), 'confirm: onchain_tx_id linked');

        if (!empty($row['onchain_tx_id'])) {
            $octx = $this->db->where('id', (int)$row['onchain_tx_id'])->get('onchain_transactions')->row_array();
            $ok((bool)$octx, 'confirm: linked onchain_transactions row exists');
            $ok($octx && $octx['status'] === 'confirmed', 'confirm: onchain_transactions.status == confirmed');
            $ok($octx && $octx['user_id'] == $uid, 'confirm: onchain_transactions.user_id matches');
        }

        // 6) retry guard: a CONFIRMED row must not be retryable
        $this->load->model('staking/Blockchainpayout_model', 'PQ');
        list($retryOk, $retryMsg) = $this->PQ->retry($row['id'], 0);
        $ok(!$retryOk, 'retry: rejected for a CONFIRMED row (' . $retryMsg . ')');

        $this->_cleanup($uid);
        echo "\nBinary Matching Payout: {$pass} passed, {$fail} failed.\n";
    }

    private function _cleanup($uid)
    {
        $this->db->where('user_id', $uid)->delete('user_wallet');
        $ids = $this->db->select('id')->where('user_id', $uid)->get('staking_matching_payouts')->result_array();
        foreach ($ids as $r) {
            $this->db->where(['reference_type' => 'staking_matching_payout', 'reference_id' => (string)$r['id']])
                     ->delete('blockchain_payout_queue');
        }
        $this->db->where('user_id', $uid)->delete('staking_matching_payouts');
        $this->db->where('reference_type', 'binary_matching_payout')->where('user_id', $uid)->delete('onchain_transactions');
    }
}
