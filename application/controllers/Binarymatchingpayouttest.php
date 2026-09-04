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
        $level = 1;
        $this->db->insert('staking_matching_payouts', [
            'user_id' => $uid, 'level' => $level, 'matched_volume' => 100, 'total_percent' => 10,
            'earning_amount' => 8, 'staking_amount' => 2,
            'left_before' => 100, 'right_before' => 150, 'run_ref' => $runRef,
        ]);
        $smpId = (int)$this->db->insert_id();

        // 2b) the two wallet_ledger credits the real engine would have posted
        // for this level — through Walletledger_model::credit() (not a raw
        // insert), so this also exercises the "no false CONFIRMED shadow row"
        // guard in Walletledger_model::_captureOnchain() end to end.
        $ledgerRef = $runRef . '-L' . $level;
        $this->load->model('Walletledger_model', 'ledger');
        $this->ledger->credit($uid, 'earning', 8, 'binary_matching', ['reference_id' => $ledgerRef]);
        $this->ledger->credit($uid, 'staking', 2, 'binary_matching', ['reference_id' => $ledgerRef]);

        $shadow = $this->db->where(['reference_type' => 'binary_matching', 'reference_id' => $ledgerRef])
                           ->count_all_results('onchain_transactions');
        $ok($shadow === 0, 'ledger credit: no false-CONFIRMED shadow row created while tx_hash is still empty (got ' . $shadow . ')');

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
            // DRYRUN never had a real broadcast, so there's no real gas to
            // report — must stay null, not a fabricated 0.
            $ok($octx && $octx['gas_fee_total'] === null, 'confirm: gas_fee_total stays null for a DRYRUN hash (no real gas spent)');
        }

        // 5b) wallet_ledger backfill — the two rows credited in step 2b must
        // now carry the same tx_hash confirm just recorded, via UPDATE only
        // (never a third wallet_ledger row for this payout).
        $ledgerRows = $this->db->where(['reference_type' => 'binary_matching', 'reference_id' => $ledgerRef])
                               ->get('wallet_ledger')->result_array();
        $ok(count($ledgerRows) === 2, 'backfill: still exactly 2 wallet_ledger rows for this payout, none added (got ' . count($ledgerRows) . ')');
        $bothStamped = count($ledgerRows) === 2
            && $ledgerRows[0]['tx_hash'] === $row['tx_hash'] && $ledgerRows[1]['tx_hash'] === $row['tx_hash'];
        $ok($bothStamped, 'backfill: both earning + staking wallet_ledger rows carry the confirmed tx_hash');

        // 5c) idempotency: re-running the backfill for the same payout must be
        // a no-op — it can never overwrite or duplicate what's already there.
        $affected = $this->ledger->backfillTxHash('binary_matching', $ledgerRef, $row['tx_hash']);
        $ok($affected === 0, 'backfill: retry affects 0 rows once already stamped (got ' . $affected . ')');
        $afterRetry = $this->db->where(['reference_type' => 'binary_matching', 'reference_id' => $ledgerRef])
                               ->get('wallet_ledger')->result_array();
        $stillIntact = count($afterRetry) === 2
            && $afterRetry[0]['tx_hash'] === $row['tx_hash'] && $afterRetry[1]['tx_hash'] === $row['tx_hash'];
        $ok($stillIntact, 'backfill: hash unchanged and no duplicate rows after the retry');

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
        $this->db->where('user_id', $uid)->delete('user_wallets'); // balance row created by Walletledger_model::ensureRow()
        $ids = $this->db->select('id')->where('user_id', $uid)->get('staking_matching_payouts')->result_array();
        foreach ($ids as $r) {
            $this->db->where(['reference_type' => 'staking_matching_payout', 'reference_id' => (string)$r['id']])
                     ->delete('blockchain_payout_queue');
        }
        $this->db->where('user_id', $uid)->delete('staking_matching_payouts');
        $this->db->where('user_id', $uid)->delete('wallet_ledger');
        $this->db->where('user_id', $uid)
                 ->where_in('reference_type', ['binary_matching_payout', 'binary_matching'])
                 ->delete('onchain_transactions');
    }
}
