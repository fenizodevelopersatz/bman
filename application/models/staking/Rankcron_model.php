<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Rankcron_model — the batch runner behind both rank crons.
 * ----------------------------------------------------------------------------
 * Holds the sweep loop, the batch cursor and the run lock so the cron
 * controllers stay thin (no SQL in controllers) and Cron Lab / admin can drive
 * the same code path without an HTTP round-trip.
 *
 * BATCHING
 * Active members are processed BATCH (500) at a time. `rank_cron_state` carries
 * the cursor across invocations, so an hourly run resumes where the last one
 * stopped and wraps to a fresh sweep at the end. A time budget ends the run
 * cleanly instead of letting PHP kill it mid-batch.
 *
 * RUN LOCK
 * A conditional UPDATE claims the lock atomically — two overlapping runs can
 * never both proceed. A lock whose heartbeat is older than STALE_LOCK belonged
 * to a crashed run and is reclaimed.
 *
 * BOTTOM-UP ORDERING (achievement only)
 * A member's rank depends on the ranks below them, so members are ordered
 * DEEPEST-FIRST. A promotion at depth 12 is visible when depth 11 is evaluated
 * in the same pass, so a chain settles in ONE sweep instead of one per level.
 */
class Rankcron_model extends CI_Model
{
    const BATCH       = 500;   // members per invocation
    const TIME_BUDGET = 240;   // seconds before a sweep stops early
    const STALE_LOCK  = 1800;  // a lock older than this is from a crashed run

    private $started;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('staking/Rankachievement_model', 'engine');
        $this->load->model('staking/Rankcalculator_model', 'calc');
        $this->load->model('staking/Rankpower_model', 'power');
        $this->load->model('staking/Rankaudit_model', 'audit');
        $this->started = time();
    }

    /* ================== ACHIEVEMENT SWEEP (hourly, §10) ================== */

    /**
     * @param bool $all  true = keep going until everyone is done or time runs out
     * @return array
     */
    public function runAchievement($all = false)
    {
        $this->started = time();

        if (!$this->_lock('rank_achievement')) {
            return ['status' => 'skipped', 'message' => 'Another rank run is in progress.',
                    'ran_at' => date('Y-m-d H:i:s')];
        }

        $processed = 0; $promoted = 0; $errors = 0; $batches = 0; $promotions = [];

        try {
            $this->calc->reset();
            $this->engine->reset();

            $users = $this->orderedUsers();
            $total = count($users);
            if ($total === 0) {
                $this->_unlock('rank_achievement', 'No active members.');
                return ['status' => 'success', 'message' => 'No active members.',
                        'processed' => 0, 'promoted' => 0, 'ran_at' => date('Y-m-d H:i:s')];
            }

            $state  = $this->state('rank_achievement');
            $cursor = (int)$state['cursor_pos'];
            if ($cursor >= $total) $cursor = 0;         // last sweep finished
            $sweep  = (int)$state['sweep_no'];

            do {
                $slice = array_slice($users, $cursor, self::BATCH);
                if (!$slice) break;

                foreach ($slice as $uid) {
                    try {
                        $r = $this->engine->processUserRank((int)$uid, ['source' => 'cron']);
                        $processed++;
                        if (!empty($r['promoted'])) {
                            $promoted++;
                            $promotions[] = ['user_id' => (int)$uid, 'rank' => $r['new_rank'],
                                             'plan' => $r['plan'], 'volume' => $r['total_volume']];
                            log_message('debug', "[rank_cron] user {$uid} → {$r['new_rank']} ({$r['plan']})");
                        } elseif ($r['status'] === 'error') {
                            $errors++;
                            log_message('error', "[rank_cron] user {$uid}: {$r['message']}");
                        }
                    } catch (Throwable $e) {
                        // One bad member must never abort the whole sweep.
                        $errors++;
                        log_message('error', "[rank_cron] user {$uid} threw: " . $e->getMessage());
                    }
                }

                $cursor += count($slice);
                $batches++;
                $this->_saveCursor('rank_achievement', $cursor, $sweep);

                if ($cursor >= $total) {                // sweep done → wrap
                    $cursor = 0; $sweep++;
                    $this->_saveCursor('rank_achievement', $cursor, $sweep);
                    break;
                }
            } while ($all && !$this->_outOfTime());

            $msg = "Processed {$processed} member(s), promoted {$promoted}"
                   . ($errors ? ", {$errors} error(s)" : '') . '.';
            $this->_unlock('rank_achievement', $msg, $promoted);

            return [
                'status' => 'success', 'message' => $msg,
                'processed' => $processed, 'promoted' => $promoted, 'errors' => $errors,
                'batches' => $batches, 'batch_size' => self::BATCH,
                'cursor' => $cursor, 'total' => $total, 'sweep_no' => $sweep,
                'promotions' => array_slice($promotions, 0, 50),
                'elapsed' => time() - $this->started, 'ran_at' => date('Y-m-d H:i:s'),
            ];
        } catch (Throwable $e) {
            $this->_unlock('rank_achievement', 'Fatal: ' . $e->getMessage());
            log_message('error', '[rank_cron] fatal: ' . $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage(),
                    'processed' => $processed, 'promoted' => $promoted,
                    'ran_at' => date('Y-m-d H:i:s')];
        }
    }

    /* =================== POWER SWEEP (daily, §11) ======================= */

    public function runPower($all = false, $cycle_only = false)
    {
        $this->started = time();

        $s = $this->power->settings();
        if (empty($s['is_enabled'])) {
            return ['status' => 'skipped', 'message' => 'Rank Power is disabled in settings.',
                    'ran_at' => date('Y-m-d H:i:s')];
        }
        if (!$this->_lock('rank_power')) {
            return ['status' => 'skipped', 'message' => 'Another rank power run is in progress.',
                    'ran_at' => date('Y-m-d H:i:s')];
        }

        try {
            // ---- 1. cycle housekeeping (open first / roll expired) ----
            $cyc   = $this->power->ensureCycle(null);
            $cycle = $cyc['cycle'];
            if (!$cycle) {
                $this->_unlock('rank_power', $cyc['message']);
                return ['status' => 'error', 'message' => $cyc['message'],
                        'ran_at' => date('Y-m-d H:i:s')];
            }
            // A fresh cycle starts empty — restart the sweep for the new window.
            if (!empty($cyc['opened'])) $this->_saveCursor('rank_power', 0);

            if ($cycle_only) {
                $this->_unlock('rank_power', $cyc['message']);
                return ['status' => 'success', 'message' => $cyc['message'], 'cycle' => $cycle,
                        'cycle_rolled' => (bool)$cyc['opened'], 'ran_at' => date('Y-m-d H:i:s')];
            }

            // ---- 2. per-member power for THIS cycle window only ----
            $users = $this->activeUsers();
            $total = count($users);
            if ($total === 0) {
                $this->_unlock('rank_power', 'No active members.');
                return ['status' => 'success', 'message' => 'No active members.', 'cycle' => $cycle,
                        'processed' => 0, 'ran_at' => date('Y-m-d H:i:s')];
            }

            // Prime the calculator ONCE for the cycle window; every member's legs
            // are then walked in memory instead of re-querying volume per user.
            $this->calc->reset();
            $this->calc->prime($cycle['start_date'] . ' 00:00:00', $cycle['end_date'] . ' 23:59:59');
            $ranks = $this->power->rankLadder();

            $state  = $this->state('rank_power');
            $cursor = (int)$state['cursor_pos'];
            if ($cursor >= $total) $cursor = 0;

            $processed = 0; $qualified = 0; $errors = 0;

            do {
                $slice = array_slice($users, $cursor, self::BATCH);
                if (!$slice) break;

                foreach ($slice as $uid) {
                    try {
                        $r = $this->power->processUserPower((int)$uid, $cycle, $ranks);
                        $processed++;
                        if (!empty($r['qualified'])) $qualified++;
                    } catch (Throwable $e) {
                        $errors++;
                        log_message('error', "[rank_power_cron] user {$uid}: " . $e->getMessage());
                    }
                }

                $cursor += count($slice);
                $this->_saveCursor('rank_power', $cursor);
                if ($cursor >= $total) { $cursor = 0; $this->_saveCursor('rank_power', 0); break; }
            } while ($all && !$this->_outOfTime());

            $msg = "Cycle #{$cycle['cycle_no']}: {$processed} member(s) calculated, "
                   . "{$qualified} qualified for group incentive"
                   . ($errors ? ", {$errors} error(s)" : '') . '.';
            $this->_unlock('rank_power', $msg);
            $this->audit->log('power_calculated', [
                'new_value' => 'Cycle #' . $cycle['cycle_no'], 'note' => $msg,
            ]);

            return [
                'status' => 'success', 'message' => $msg, 'cycle' => $cycle,
                'cycle_rolled' => (bool)$cyc['opened'], 'closed_cycle' => $cyc['closed'],
                'processed' => $processed, 'qualified' => $qualified, 'errors' => $errors,
                'cursor' => $cursor, 'total' => $total, 'batch_size' => self::BATCH,
                'elapsed' => time() - $this->started, 'ran_at' => date('Y-m-d H:i:s'),
            ];
        } catch (Throwable $e) {
            $this->_unlock('rank_power', 'Fatal: ' . $e->getMessage());
            log_message('error', '[rank_power_cron] fatal: ' . $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage(),
                    'ran_at' => date('Y-m-d H:i:s')];
        }
    }

    /* ============================ user lists ============================ */

    public function activeUsers()
    {
        $rows = $this->db->select('id')->where('status', 1)
                         ->order_by('id', 'ASC')->get('users')->result_array();
        return array_map('intval', array_column($rows, 'id'));
    }

    /**
     * Active members, deepest node first. Members with no placement row sort
     * last (depth 0) — they are tree roots and depend on everyone below anyway.
     * The tie-break on id keeps the order stable, which cursor paging needs.
     */
    public function orderedUsers()
    {
        $ids   = $this->activeUsers();
        $depth = $this->calc->depthMap();
        usort($ids, function ($a, $b) use ($depth) {
            $da = isset($depth[$a]) ? $depth[$a] : 0;
            $db = isset($depth[$b]) ? $depth[$b] : 0;
            if ($da === $db) return $a <=> $b;
            return $db <=> $da;                 // deepest first
        });
        return $ids;
    }

    private function _outOfTime()
    {
        return (time() - $this->started) >= self::TIME_BUDGET;
    }

    /* ========================= state & run lock ========================= */

    public function state($job)
    {
        $s = $this->db->get_where('rank_cron_state', ['job' => $job])->row_array();
        if (!$s) {
            $this->db->insert('rank_cron_state', ['job' => $job]);
            $s = $this->db->get_where('rank_cron_state', ['job' => $job])->row_array();
        }
        return $s;
    }

    /** Both jobs' state — the admin Rank Power / Reports pages show this. */
    public function states()
    {
        return $this->db->get('rank_cron_state')->result_array();
    }

    /**
     * Claim the run lock. The conditional UPDATE is atomic: two runs racing here
     * cannot both see affected_rows() === 1.
     */
    private function _lock($job)
    {
        $this->state($job);
        $stale = date('Y-m-d H:i:s', time() - self::STALE_LOCK);
        $this->db->query(
            'UPDATE rank_cron_state SET running = 1, heartbeat = NOW()
              WHERE job = ? AND (running = 0 OR heartbeat IS NULL OR heartbeat < ?)',
            [$job, $stale]
        );
        return $this->db->affected_rows() === 1;
    }

    private function _unlock($job, $result, $promoted = 0)
    {
        $this->db->query(
            'UPDATE rank_cron_state
                SET running = 0, last_run_at = NOW(), last_result = ?,
                    total_promoted = total_promoted + ?
              WHERE job = ?',
            [substr((string)$result, 0, 255), (int)$promoted, $job]
        );
    }

    private function _saveCursor($job, $cursor, $sweep = null)
    {
        $row = ['cursor_pos' => (int)$cursor, 'heartbeat' => date('Y-m-d H:i:s')];
        if ($sweep !== null) $row['sweep_no'] = (int)$sweep;
        $this->db->where('job', $job)->update('rank_cron_state', $row);
    }

    /** Release a stuck lock by hand (admin escape hatch). */
    public function releaseLock($job, $admin_id = null)
    {
        $this->db->where('job', $job)->update('rank_cron_state', [
            'running' => 0, 'last_result' => 'Lock released by admin.',
        ]);
        $this->audit->log('rank_config_changed', [
            'changed_by' => $admin_id, 'new_value' => $job, 'note' => 'Cron lock released by hand.',
        ]);
        return [true, 'Lock released for ' . $job . '.'];
    }
}
