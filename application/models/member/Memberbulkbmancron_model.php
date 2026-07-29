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
 * This is NEW money, exactly like Treasurysend_model: nothing is debited from
 * any internal ledger wallet, and the member's dashboard wallet buckets
 * (Exchange/Earning/Staking/Bonus) are intentionally untouched — only their
 * real on-chain BMAN balance changes.
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
        $this->load->model('Onchaintx_model', 'octx');
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
            $treasuryKey = null; $available = null;
            if (!$dry) {
                $cfg = $this->tokens->activeSettings();
                if (!$cfg || empty($cfg['treasury_wallet'])) throw new RuntimeException('Treasury wallet not configured.');
                $treasuryKey = $this->tokens->treasuryPrivateKey();
                if (!$treasuryKey) throw new RuntimeException('Treasury key missing or failed to decrypt.');
                $balance = $this->web3bman->getTokenBalance($cfg['treasury_wallet']);
                $available = bcsub($balance, (string)$settings['min_treasury_reserve'], 8);
            }

            $rows = $this->db->select('r.*, b.ref AS batch_ref')
                ->from('member_bulk_upload_rows r')
                ->join('member_bulk_upload_batches b', 'b.id = r.batch_id', 'left')
                ->where('r.bman_status', 'pending')
                ->order_by('r.id', 'ASC')
                ->limit((int)$settings['max_batch_size'])
                ->get()->result_array();

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

            $msg = "Processed {$processed}, sent {$sent}, failed {$failed}" . ($dry ? ' [DRY-RUN]' : '');
            $this->_unlock(self::JOB, $msg, $sent);
            return ['status' => 'success', 'message' => $msg, 'dry_run' => $dry,
                    'processed' => $processed, 'sent' => $sent, 'failed' => $failed,
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

            // No wallet_ledger row exists for this movement (it is off-ledger new
            // money, same as a treasury direct send), so capture() rather than
            // updateByLedgerId().
            //
            // wallet_type is deliberately left NULL: the column is an ENUM of the
            // internal buckets (usdt/exchange/earning/staking/bonus) and this
            // movement belongs to none of them — that is the whole point of new
            // money. NULL is both accurate and schema-valid.
            $this->octx->capture([
                'tx_hash' => $tx, 'network' => $network,
                'tx_type' => 'member_bulk_bman', 'status' => 'confirmed',
                'user_id' => (int)$r['user_id'], 'token_symbol' => 'BMAN',
                'amount' => (string)$r['bman_amount'], 'from_address' => $from, 'to_address' => $to,
                'reference_type' => 'member_bulk_upload',
                'reference_id' => substr((string)($r['batch_ref'] ?: $ref), 0, 64),
                '_event' => ['detail' => 'bulk member upload opening balance'.($dry ? ' [DRY-RUN]' : '')],
            ]);

            return ['id' => $id, 'ok' => true, 'tx_hash' => $tx, 'amount' => (string)$r['bman_amount']];
        } catch (Throwable $e) {
            $this->_fail($id, substr($e->getMessage(), 0, 255));
            return ['id' => $id, 'ok' => false, 'reason' => $e->getMessage()];
        }
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
