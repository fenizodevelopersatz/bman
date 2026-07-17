<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Walletsettlementcron_model — the on-chain settlement engine for
 * wallet_internal_transfer rows queued by Wallettransferservice_model::execute().
 *
 * Every completed internal transfer (self or member, user- or admin-initiated)
 * lands here with settlement_status='pending' unless its type was toggled off
 * or the destination had no on-chain wallet address at enqueue time (then it's
 * 'skipped'). This sweeps the pending queue and, from the single custodial
 * Treasury wallet, sends real BMAN to the resolved destination address —
 * self-transfers to the source's own address, member transfers to the
 * recipient's address (see Wallettransferservice_model's class doc for why).
 *
 * Gated by wallet_transfer_settlement_settings:
 *   enabled = 0  -> run() no-ops immediately; the queue just accumulates.
 *   dry_run = 1  -> synthetic DRYRUN- tx hash recorded, nothing broadcast.
 * A run-lock (wallet_settlement_cron_state) uses the same atomic conditional-
 * UPDATE pattern as Rankcron_model so overlapping invocations can never both
 * process the same batch.
 *
 * A row that fails to broadcast is left 'failed' — terminal, NOT auto-retried
 * by the next sweep (a money-moving queue should surface failures rather than
 * silently hammer a broken RPC/treasury). An admin resets it back to
 * 'pending' by hand via retry() after investigating.
 */
class Walletsettlementcron_model extends CI_Model
{
    const JOB = 'wallet_transfer_settlement';
    const STALE_LOCK = 1800; // seconds — a lock older than this is from a crashed run

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Tokenmaster_model', 'tokens');
        $this->load->model('Onchaintx_model', 'octx');
        $this->load->library('web3bman');
    }

    /* ================================ run ================================ */

    public function run()
    {
        $settings = $this->_settings();
        if (!(int)$settings['enabled']) {
            return ['status' => 'skipped', 'message' => 'Settlement is disabled (wallet_transfer_settlement_settings.enabled = 0).',
                    'processed' => 0, 'settled' => 0, 'failed' => 0, 'ran_at' => date('Y-m-d H:i:s')];
        }
        if (!$this->_lock(self::JOB)) {
            return ['status' => 'skipped', 'message' => 'Another settlement run is in progress.',
                    'ran_at' => date('Y-m-d H:i:s')];
        }

        $dry = (int)$settings['dry_run'] === 1;
        $processed = 0; $settled = 0; $failed = 0; $details = [];

        try {
            $treasuryKey = null; $treasuryAddress = null; $available = null;
            if (!$dry) {
                $cfg = $this->tokens->activeSettings();
                if (!$cfg || empty($cfg['treasury_wallet'])) throw new RuntimeException('Treasury wallet not configured.');
                $treasuryAddress = $cfg['treasury_wallet'];
                $treasuryKey = $this->tokens->treasuryPrivateKey();
                if (!$treasuryKey) throw new RuntimeException('Treasury key missing or failed to decrypt.');
                $balance = $this->web3bman->getTokenBalance($treasuryAddress);
                $available = bcsub($balance, (string)$settings['min_treasury_reserve'], 8);
            }

            $rows = $this->db->where('settlement_status', 'pending')
                ->order_by('id', 'ASC')->limit((int)$settings['max_batch_size'])
                ->get('wallet_internal_transfer')->result_array();

            foreach ($rows as $r) {
                $processed++;
                $res = $this->_settleOne($r, $dry, $treasuryKey, $available);
                if ($res['ok']) {
                    $settled++;
                    if (!$dry && $available !== null) $available = bcsub($available, (string)$r['amount'], 8);
                } else {
                    $failed++;
                }
                $details[] = $res;
            }

            $msg = "Processed {$processed}, settled {$settled}, failed {$failed}" . ($dry ? ' [DRY-RUN]' : '');
            $this->_unlock(self::JOB, $msg, $settled);
            return ['status' => 'success', 'message' => $msg, 'dry_run' => $dry,
                    'processed' => $processed, 'settled' => $settled, 'failed' => $failed,
                    'details' => $details, 'ran_at' => date('Y-m-d H:i:s')];
        } catch (Throwable $e) {
            $this->_unlock(self::JOB, 'Fatal: ' . $e->getMessage(), $settled);
            log_message('error', '[wallet_settlement_cron] fatal: ' . $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage(),
                    'processed' => $processed, 'settled' => $settled, 'failed' => $failed, 'ran_at' => date('Y-m-d H:i:s')];
        }
    }

    private function _settleOne(array $r, $dry, $treasuryKey, $available)
    {
        $id = (int)$r['id'];
        $this->db->where('id', $id)->update('wallet_internal_transfer', [
            'settlement_status'   => 'processing',
            'settlement_attempts' => (int)$r['settlement_attempts'] + 1,
        ]);

        if (empty($r['settlement_address'])) {
            $this->_fail($id, 'No settlement address on file.');
            return ['id' => $id, 'ref' => $r['ref'], 'ok' => false, 'reason' => 'no_address'];
        }

        try {
            if ($dry) {
                $tx = 'DRYRUN-' . $r['ref']; // $r['ref'] already carries the WTS- prefix
                $from = null; $to = $r['settlement_address'];
            } else {
                if ($available !== null && bccomp($available, (string)$r['amount'], 8) < 0) {
                    $this->_fail($id, 'Would breach minimum treasury reserve.');
                    return ['id' => $id, 'ref' => $r['ref'], 'ok' => false, 'reason' => 'reserve_guard'];
                }
                $sent = $this->web3bman->sendToken($treasuryKey, $r['settlement_address'], (string)$r['amount']);
                $tx = $sent['tx_hash']; $from = $sent['from']; $to = $sent['to'];
            }

            $this->db->where('id', $id)->update('wallet_internal_transfer', [
                'settlement_status' => 'completed', 'tx_hash' => $tx, 'network' => $this->_network(),
                'settlement_error' => null, 'settled_at' => date('Y-m-d H:i:s'),
            ]);

            if (!empty($r['credit_onchain_ledger_id'])) {
                $this->octx->updateByLedgerId($r['credit_onchain_ledger_id'], [
                    'tx_hash' => $tx, 'from_address' => $from, 'to_address' => $to, 'status' => 'confirmed',
                ], ['detail' => 'wallet transfer settlement' . ($dry ? ' [DRY-RUN]' : '')]);
            }

            return ['id' => $id, 'ref' => $r['ref'], 'ok' => true, 'tx_hash' => $tx];
        } catch (Throwable $e) {
            $this->_fail($id, substr($e->getMessage(), 0, 255));
            return ['id' => $id, 'ref' => $r['ref'], 'ok' => false, 'reason' => $e->getMessage()];
        }
    }

    private function _fail($id, $reason)
    {
        $this->db->where('id', $id)->update('wallet_internal_transfer', [
            'settlement_status' => 'failed', 'settlement_error' => substr((string)$reason, 0, 255),
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

    public function settings() { return $this->_settings(); }

    public function updateSettings(array $data, $adminId = null)
    {
        $allowed = array_intersect_key($data, array_flip([
            'enabled', 'dry_run', 'settle_self_transfers', 'settle_member_transfers',
            'min_treasury_reserve', 'max_batch_size',
        ]));
        if (empty($allowed)) return [false, 'No valid fields to update.'];
        $allowed['updated_by'] = $adminId;
        $this->db->where('id', 1)->update('wallet_transfer_settlement_settings', $allowed);
        return [true, 'Settings updated.'];
    }

    /** Manually reset a failed/skipped row back to the pending queue. */
    public function retry($transferId)
    {
        $row = $this->db->get_where('wallet_internal_transfer', ['id' => (int)$transferId])->row_array();
        if (!$row) return [false, 'Transfer not found.'];
        if (!in_array($row['settlement_status'], ['failed', 'skipped'], true))
            return [false, 'Only a failed or skipped settlement can be retried.'];
        if (empty($row['settlement_address']))
            return [false, 'No settlement address on file — cannot retry.'];
        $this->db->where('id', $row['id'])->update('wallet_internal_transfer', [
            'settlement_status' => 'pending', 'settlement_error' => null,
        ]);
        return [true, 'Queued for retry.'];
    }

    public function state($job = self::JOB)
    {
        $s = $this->db->get_where('wallet_settlement_cron_state', ['job' => $job])->row_array();
        if (!$s) {
            $this->db->insert('wallet_settlement_cron_state', ['job' => $job]);
            $s = $this->db->get_where('wallet_settlement_cron_state', ['job' => $job])->row_array();
        }
        return $s;
    }

    /** Release a stuck lock by hand (admin escape hatch). */
    public function releaseLock($job = self::JOB)
    {
        $this->db->where('job', $job)->update('wallet_settlement_cron_state', [
            'running' => 0, 'last_result' => 'Lock released by admin.',
        ]);
        return [true, 'Lock released.'];
    }

    /* ============================ settings & lock ========================= */

    private function _settings()
    {
        $row = $this->db->get_where('wallet_transfer_settlement_settings', ['id' => 1])->row_array();
        return $row ?: ['enabled' => 0, 'dry_run' => 1, 'settle_self_transfers' => 1, 'settle_member_transfers' => 1,
                         'min_treasury_reserve' => '0', 'max_batch_size' => 20];
    }

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

    private function _unlock($job, $result, $settledDelta = 0)
    {
        $this->db->query(
            'UPDATE wallet_settlement_cron_state
                SET running = 0, last_run_at = NOW(), last_result = ?,
                    total_settled = total_settled + ?
              WHERE job = ?',
            [substr((string)$result, 0, 255), (int)$settledDelta, $job]
        );
    }
}
