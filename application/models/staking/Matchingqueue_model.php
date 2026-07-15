<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Matchingqueue_model — queue-tracked wrapper around Stakingmatching_model::run().
 * ---------------------------------------------------------------------------
 * Stakingmatching_model itself is untouched: this only adds tracking (one
 * binary_matching_queue row per run, retried on failure, visible to admins)
 * around the existing, proven engine. A MySQL advisory lock guarantees only
 * one engine run is ever in flight at a time — payMatching() reads/consumes
 * binary_carry without row-level locking, so two concurrent runs could each
 * read the same carry and double-pay the same match; the lock closes that
 * gap regardless of whether the cron or an admin "Run Now" click triggers it.
 */
class Matchingqueue_model extends CI_Model
{
    private $lockName = 'binary_matching_engine';

    /**
     * Claim a PENDING row (if one exists — e.g. admin-enqueued) or mint one,
     * run the engine through it, and record the outcome. Returns immediately
     * with claimed=false if the engine is already running elsewhere.
     */
    public function runClaimed($opts = [])
    {
        $lock = $this->db->query("SELECT GET_LOCK(?, 0) AS ok", [$this->lockName])->row_array();
        if (empty($lock['ok'])) {
            return ['claimed' => false, 'reason' => 'engine already running (lock held)'];
        }

        try {
            $row = $this->_claimExistingPending();
            if (!$row) $row = $this->_mintAndClaim($opts);
            if (!$row) return ['claimed' => false, 'reason' => 'race: could not claim a queue row'];

            $this->load->model('staking/Stakingmatching_model', 'MB');
            try {
                $summary = $this->MB->run(['run_ref' => $row['run_ref']]);
                $this->db->where('id', $row['id'])->update('binary_matching_queue', [
                    'status'      => 'DONE',
                    'result_json' => json_encode($summary),
                    'finished_at' => date('Y-m-d H:i:s'),
                ]);
                return ['claimed' => true, 'run_ref' => $row['run_ref'], 'status' => 'DONE', 'summary' => $summary];
            } catch (Throwable $e) {
                $backToPending = (int)$row['attempts'] < (int)$row['max_attempts'];
                $upd = [
                    'status'     => $backToPending ? 'PENDING' : 'FAILED',
                    'last_error' => substr($e->getMessage(), 0, 255),
                ];
                if (!$backToPending) $upd['finished_at'] = date('Y-m-d H:i:s');
                $this->db->where('id', $row['id'])->update('binary_matching_queue', $upd);
                return ['claimed' => true, 'run_ref' => $row['run_ref'], 'status' => $upd['status'], 'error' => $e->getMessage()];
            }
        } finally {
            $this->db->query("SELECT RELEASE_LOCK(?)", [$this->lockName]);
        }
    }

    /** Claim the oldest existing PENDING row (e.g. one an admin just enqueued). */
    private function _claimExistingPending()
    {
        $candidate = $this->db->where('status', 'PENDING')->order_by('id', 'ASC')->limit(1)
                              ->get('binary_matching_queue')->row_array();
        return $candidate ? $this->_atomicClaim($candidate['id']) : null;
    }

    /** Mint a brand-new PENDING row (unique run_ref, same format the engine itself uses) and claim it. */
    private function _mintAndClaim($opts)
    {
        $ref = 'MB-'.date('Ymd-His').'-'.strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        $this->db->insert('binary_matching_queue', [
            'run_ref'     => $ref,
            'status'      => 'PENDING',
            'scope'       => $opts['scope'] ?? 'full',
            'enqueued_by' => $opts['enqueued_by'] ?? null,
        ]);
        return $this->_atomicClaim((int)$this->db->insert_id());
    }

    /** Atomically flip PENDING -> PROCESSING (attempts+1); null if raced away. */
    private function _atomicClaim($id)
    {
        $this->db->query(
            "UPDATE binary_matching_queue
             SET status = 'PROCESSING', attempts = attempts + 1, started_at = ?
             WHERE id = ? AND status = 'PENDING'",
            [date('Y-m-d H:i:s'), $id]
        );
        if ($this->db->affected_rows() !== 1) return null;
        return $this->db->get_where('binary_matching_queue', ['id' => $id])->row_array();
    }

    /* ------------------------------- reads -------------------------------- */

    public function recent($limit = 50)
    {
        return $this->db->order_by('id', 'DESC')->limit((int)$limit)
                        ->get('binary_matching_queue')->result_array();
    }
}
