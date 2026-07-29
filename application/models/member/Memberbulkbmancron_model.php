<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Memberbulkbmancron_model — sends the opening BMAN balance to members created
 * by a bulk upload.
 *
 * Memberbulkupload_model::import() creates the accounts and leaves every row
 * that carried a `bman` amount at bman_status='pending'. This sweeps that
 * queue and sends real BMAN from the single custodial Treasury wallet to each
 * new member's on-chain address.
 *
 * Why a cron and not part of the import: import is a synchronous admin
 * request. Broadcasting one on-chain transfer per member inside it means a
 * 300-member file either times out half-finished or holds the browser for
 * minutes with money moving behind it. Queue-then-sweep also gives each
 * transfer its own retry surface and audit row.
 *
 * This is NEW money: nothing is debited from any internal ledger wallet. What
 * it IS credited to is the member's EXCHANGE wallet — the delivery is posted
 * through Walletledger_model exactly the way Depositlistener_model posts a
 * detected on-chain deposit, so the BMAN the member now holds on-chain is the
 * BMAN their dashboard shows. That single call locks the balance row, appends
 * a wallet_ledger entry with balance_after, updates user_wallets, applies the
 * normal exchange maturity rules, and mirrors the movement into
 * onchain_transactions. (Treasurysend_model deliberately does NOT do this —
 * it is a raw off-ledger airdrop. An imported member's opening balance is
 * different: it is meant to be spendable in the panel.)
 *
 * Set member_bulk_upload_settings.credit_exchange_wallet = 0 to deliver
 * on-chain only and leave dashboard balances untouched.
 *
 * Gated by member_bulk_upload_settings:
 *   enabled = 0  -> run() no-ops immediately; the queue just accumulates.
 *   dry_run = 1  -> synthetic DRYRUN- tx hash recorded, nothing broadcast.
 * The run-lock uses the shared wallet_settlement_cron_state table (keyed by
 * `job`) with the same atomic conditional-UPDATE pattern as the settlement
 * cron, so overlapping invocations can never both claim the same batch.
 *
 * A row that fails to broadcast is left 'failed' — terminal, NOT auto-retried
 * by the next sweep. A money-moving queue should surface its failures rather
 * than silently hammer a broken RPC or an empty treasury. An admin re-queues
 * it by hand from the batch detail page after investigating.
 */
class Memberbulkbmancron_model extends CI_Model
{
    const JOB = 'member_bulk_bman';
    const STALE_LOCK = 1800; // seconds — a lock older than this is from a crashed run

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Tokenmaster_model', 'tokens');
        $this->load->model('Walletledger_model', 'ledger');
        $this->load->model('member/Memberbulkupload_model', 'bulk');
        $this->load->library('web3bman');
    }

    /* ================================ run ================================ */

    public function run()
    {
        $settings = $this->bulk->settings();
        if (!(int)$settings['enabled']) {
            return ['status' => 'skipped', 'message' => 'Bulk BMAN sending is disabled (member_bulk_upload_settings.enabled = 0).',
                    'processed' => 0, 'sent' => 0, 'failed' => 0, 'ran_at' => date('Y-m-d H:i:s')];
        }
        if (!$this->_lock(self::JOB)) {
            return ['status' => 'skipped', 'message' => 'Another bulk BMAN run is in progress.',
                    'ran_at' => date('Y-m-d H:i:s')];
        }

        $dry = (int)$settings['dry_run'] === 1;
        $processed = 0; $sent = 0; $failed = 0; $details = [];

        try {
            // PHASE 1 — heal first. Rows whose BMAN reached the chain but whose
            // Exchange credit did not are settled before anything new is sent.
            // This runs BEFORE the treasury setup below on purpose: posting a
            // ledger entry for BMAN that has already moved needs no treasury
            // key, no balance, and no RPC, so a misconfigured or drained
            // treasury must not be able to block the self-healing.
            $backfilled = $dry ? 0 : $this->_backfillCredits($settings);

            // PHASE 2 — drain the pending send queue.
            $rows = $this->db->select('r.*, b.ref AS batch_ref')
                ->from('member_bulk_upload_rows r')
                ->join('member_bulk_upload_batches b', 'b.id = r.batch_id', 'left')
                ->where('r.bman_status', 'pending')
                ->order_by('r.id', 'ASC')
                ->limit((int)$settings['max_batch_size'])
                ->get()->result_array();

            // Nothing to send: skip the treasury handshake entirely rather than
            // spending an RPC round-trip (and a key decrypt) to discover that.
            $treasuryKey = null; $available = null;
            if ($rows && !$dry) {
                $cfg = $this->tokens->activeSettings();
                if (!$cfg || empty($cfg['treasury_wallet'])) throw new RuntimeException('Treasury wallet not configured.');
                $treasuryKey = $this->tokens->treasuryPrivateKey();
                if (!$treasuryKey) throw new RuntimeException('Treasury key missing or failed to decrypt.');
                $balance = $this->web3bman->getTokenBalance($cfg['treasury_wallet']);
                $available = bcsub($balance, (string)$settings['min_treasury_reserve'], 8);
            }

            foreach ($rows as $r) {
                $processed++;
                $res = $this->_sendOne($r, $dry, $treasuryKey, $available);
                if ($res['ok']) {
                    $sent++;
                    if (!$dry && $available !== null) $available = bcsub($available, (string)$r['bman_amount'], 8);
                } else {
                    $failed++;
                }
                $details[] = $res;
            }

            $msg = "Processed {$processed}, sent {$sent}, failed {$failed}"
                 . ($backfilled ? ", back-credited {$backfilled}" : '')
                 . ($dry ? ' [DRY-RUN]' : '');
            $this->_unlock(self::JOB, $msg, $sent);
            return ['status' => 'success', 'message' => $msg, 'dry_run' => $dry,
                    'processed' => $processed, 'sent' => $sent, 'failed' => $failed,
                    'back_credited' => $backfilled,
                    'details' => $details, 'ran_at' => date('Y-m-d H:i:s')];
        } catch (Throwable $e) {
            $this->_unlock(self::JOB, 'Fatal: '.$e->getMessage(), $sent);
            log_message('error', '[member_bulk_bman_cron] fatal: '.$e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage(),
                    'processed' => $processed, 'sent' => $sent, 'failed' => $failed, 'ran_at' => date('Y-m-d H:i:s')];
        }
    }

    private function _sendOne(array $r, $dry, $treasuryKey, $available)
    {
        $id = (int)$r['id'];

        // Claim the row atomically: the WHERE clause means a second run that
        // slipped past the lock cannot re-claim a row this one already took.
        $this->db->where('id', $id)->where('bman_status', 'pending')
            ->set('bman_attempts', 'bman_attempts + 1', false)
            ->update('member_bulk_upload_rows', ['bman_status' => 'processing']);
        if ($this->db->affected_rows() !== 1) {
            return ['id' => $id, 'ok' => false, 'reason' => 'already_claimed'];
        }

        if (empty($r['wallet_address'])) {
            $this->_fail($id, 'No on-chain wallet address on file for this member.');
            return ['id' => $id, 'ok' => false, 'reason' => 'no_address'];
        }
        if (bccomp((string)$r['bman_amount'], '0', 8) <= 0) {
            $this->_fail($id, 'Amount is not greater than zero.');
            return ['id' => $id, 'ok' => false, 'reason' => 'zero_amount'];
        }

        $ref = 'MBU-'.$id;

        try {
            if ($dry) {
                $tx = 'DRYRUN-'.$ref; $from = null; $to = $r['wallet_address'];
            } else {
                if ($available !== null && bccomp($available, (string)$r['bman_amount'], 8) < 0) {
                    $this->_fail($id, 'Would breach minimum treasury reserve.');
                    return ['id' => $id, 'ok' => false, 'reason' => 'reserve_guard'];
                }
                $out = $this->web3bman->sendToken($treasuryKey, $r['wallet_address'], (string)$r['bman_amount']);
                $tx = $out['tx_hash']; $from = $out['from']; $to = $out['to'];
            }

            $network = $this->_network();
            $this->db->where('id', $id)->update('member_bulk_upload_rows', [
                'bman_status'  => 'completed',
                'bman_tx_hash' => $tx,
                'bman_network' => $network,
                'bman_error'   => null,
                'bman_sent_at' => date('Y-m-d H:i:s'),
            ]);

            // The BMAN is now really on the member's address. Post it to their
            // Exchange wallet so the panel shows what they actually hold. This
            // also writes the onchain_transactions row (via the ledger's own
            // observer), so there is no separate capture() here — a second one
            // would duplicate the movement in transaction history.
            //
            // A dry run never credits: a spendable Exchange balance with no BMAN
            // behind it is exactly what dry-run exists to prevent.
            $credit = ['ok' => true, 'skipped' => 'dry_run'];
            if (!$dry) {
                $credit = $this->_creditExchange($r, $tx);
            }

            return ['id' => $id, 'ok' => true, 'tx_hash' => $tx,
                    'amount' => (string)$r['bman_amount'],
                    'exchange_credit' => $credit];
        } catch (Throwable $e) {
            $this->_fail($id, substr($e->getMessage(), 0, 255));
            return ['id' => $id, 'ok' => false, 'reason' => $e->getMessage()];
        }
    }

    /**
     * Post a delivered send to the member's Exchange wallet.
     *
     * Uses the real on-chain tx hash as the ledger's idempotency key: the
     * UNIQUE (tx_hash, wallet_type) index on wallet_ledger means calling this
     * twice for the same send credits once. That is what makes the backfill
     * sweep below safe to run on every pass.
     *
     * Maturity is left to the standard exchange rules (no skip_maturity) — an
     * admin-granted opening balance should not be more withdrawable than a
     * deposit of the same size.
     *
     * A failure here is NOT fatal to the row: the BMAN has already moved
     * on-chain, so the row stays 'completed' and the reason is recorded. The
     * next pass retries the credit.
     */
    private function _creditExchange(array $r, $txHash)
    {
        if (!(int)($this->bulk->settings()['credit_exchange_wallet'] ?? 1)) {
            return ['ok' => true, 'skipped' => 'credit_exchange_wallet=0'];
        }
        if (empty($r['user_id'])) {
            return ['ok' => false, 'error' => 'row has no user_id'];
        }

        try {
            list($ok, $info) = $this->ledger->credit(
                (int)$r['user_id'], 'exchange', (string)$r['bman_amount'], 'admin_adjustment',
                [
                    'tx_hash'      => $txHash,
                    'reference_id' => substr((string)($r['batch_ref'] ?: ('MBU-'.$r['id'])), 0, 64),
                    'description'  => 'Bulk member upload — opening BMAN balance',
                ]
            );

            if (!$ok) {
                $this->_creditError($r['id'], 'Exchange credit failed: '.$info);
                return ['ok' => false, 'error' => (string)$info];
            }

            // post() returns the ledger id, or the string 'already_posted' when
            // the unique index already had this (tx_hash, wallet) pair.
            $ledgerId = is_numeric($info) ? (int)$info : $this->_findLedgerId($txHash);

            $this->db->where('id', (int)$r['id'])->update('member_bulk_upload_rows', [
                'bman_ledger_id'   => $ledgerId ?: null,
                'bman_credited_at' => date('Y-m-d H:i:s'),
                'bman_error'       => null,
            ]);
            return ['ok' => true, 'ledger_id' => $ledgerId];
        } catch (Throwable $e) {
            $this->_creditError($r['id'], 'Exchange credit error: '.$e->getMessage());
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /** The ledger row for a hash already posted on a previous attempt. */
    private function _findLedgerId($txHash)
    {
        $row = $this->db->select('id')->where('tx_hash', $txHash)->where('wallet_type', 'exchange')
            ->limit(1)->get('wallet_ledger')->row_array();
        return $row ? (int)$row['id'] : 0;
    }

    /**
     * Phase 2: rows whose BMAN reached the chain but whose Exchange credit did
     * not (crash between the two steps, or a transient ledger failure). Dry-run
     * hashes are excluded — those represent no real money and must never be
     * credited.
     */
    private function _backfillCredits(array $settings)
    {
        if (!(int)($settings['credit_exchange_wallet'] ?? 1)) return 0;

        $rows = $this->db->select('r.*, b.ref AS batch_ref')
            ->from('member_bulk_upload_rows r')
            ->join('member_bulk_upload_batches b', 'b.id = r.batch_id', 'left')
            ->where('r.bman_status', 'completed')
            ->where('r.bman_ledger_id IS NULL', null, false)
            ->where('r.bman_tx_hash IS NOT NULL', null, false)
            ->where("r.bman_tx_hash NOT LIKE 'DRYRUN-%'", null, false)
            ->order_by('r.id', 'ASC')
            ->limit((int)$settings['max_batch_size'])
            ->get()->result_array();

        $done = 0;
        foreach ($rows as $r) {
            $res = $this->_creditExchange($r, $r['bman_tx_hash']);
            if (!empty($res['ok']) && empty($res['skipped'])) $done++;
        }
        if ($done) log_message('info', "[member_bulk_bman_cron] back-credited {$done} exchange wallet(s)");
        return $done;
    }

    /** Record a credit problem without disturbing the (successful) send status. */
    private function _creditError($id, $reason)
    {
        $this->db->where('id', (int)$id)->update('member_bulk_upload_rows', [
            'bman_error' => substr((string)$reason, 0, 255),
        ]);
        log_message('error', '[member_bulk_bman_cron] row '.$id.' '.$reason);
    }

    private function _fail($id, $reason)
    {
        $this->db->where('id', (int)$id)->update('member_bulk_upload_rows', [
            'bman_status' => 'failed', 'bman_error' => substr((string)$reason, 0, 255),
        ]);
    }

    private function _network()
    {
        static $n = null;
        if ($n === null) {
            $cfg = $this->tokens->activeSettings();
            $n = $cfg['network'] ?? 'BSC';
        }
        return $n;
    }

    /* ========================= admin escape hatches ======================= */

    public function state($job = self::JOB)
    {
        $s = $this->db->get_where('wallet_settlement_cron_state', ['job' => $job])->row_array();
        if (!$s) {
            $this->db->insert('wallet_settlement_cron_state', ['job' => $job]);
            $s = $this->db->get_where('wallet_settlement_cron_state', ['job' => $job])->row_array();
        }
        return $s;
    }

    /** Release a stuck lock by hand. */
    public function releaseLock($job = self::JOB)
    {
        $this->db->where('job', $job)->update('wallet_settlement_cron_state', [
            'running' => 0, 'last_result' => 'Lock released by admin.',
        ]);
        return [true, 'Lock released.'];
    }

    public function pendingCount()
    {
        return (int)$this->db->where('bman_status', 'pending')->count_all_results('member_bulk_upload_rows');
    }

    /* ================================ lock =============================== */

    /** Claim the run lock. The conditional UPDATE is atomic: two runs racing
     *  here cannot both see affected_rows() === 1. */
    private function _lock($job)
    {
        $this->state($job);
        $stale = date('Y-m-d H:i:s', time() - self::STALE_LOCK);
        $this->db->query(
            'UPDATE wallet_settlement_cron_state SET running = 1, heartbeat = NOW()
              WHERE job = ? AND (running = 0 OR heartbeat IS NULL OR heartbeat < ?)',
            [$job, $stale]
        );
        return $this->db->affected_rows() === 1;
    }

    private function _unlock($job, $result, $sentDelta = 0)
    {
        $this->db->query(
            'UPDATE wallet_settlement_cron_state
                SET running = 0, last_run_at = NOW(), last_result = ?,
                    total_settled = total_settled + ?
              WHERE job = ?',
            [substr((string)$result, 0, 255), (int)$sentDelta, $job]
        );
    }
}
