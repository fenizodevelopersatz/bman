<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Blockchainpayout_model — admin reads + retry for blockchain_payout_queue.
 * ---------------------------------------------------------------------------
 * retry() only resets the row's on-chain state; it never touches any wallet
 * balance. By the time a row exists here, Stakingmatching_model::payMatching()
 * has already credited the user's earning/staking wallet synchronously — this
 * queue is purely the on-chain leg backing that credit. The next cron tick
 * (BinaryMatchingPayoutCron) rebroadcasts whatever is PENDING.
 */
class Blockchainpayout_model extends CI_Model
{
    /* ------------------------------ reads ------------------------------ */

    /** Status counts + today's confirmed volume, for admin KPI cards. */
    public function summary()
    {
        $counts = $this->db->select('status, COUNT(*) AS n')
                           ->group_by('status')->get('blockchain_payout_queue')->result_array();
        $byStatus = ['PENDING' => 0, 'PROCESSING' => 0, 'CONFIRMED' => 0, 'FAILED' => 0, 'RETRY' => 0];
        foreach ($counts as $c) $byStatus[$c['status']] = (int)$c['n'];

        $today = $this->db->select_sum('amount', 'total')
                          ->where('status', 'CONFIRMED')
                          ->where('DATE(confirmed_at)', date('Y-m-d'))
                          ->get('blockchain_payout_queue')->row_array();

        return [
            'by_status'          => $byStatus,
            'needs_attention'    => $byStatus['FAILED'] + $byStatus['RETRY'],
            'confirmed_today_amt'=> (float)($today['total'] ?? 0),
        ];
    }

    /** Filterable/paginated payout rows, joined to users for display. */
    public function list($opts = [])
    {
        $this->db->select('q.*, u.username, u.referral_id')
                 ->from('blockchain_payout_queue q')
                 ->join('users u', 'u.id = q.user_id', 'left');
        if (!empty($opts['status']))  $this->db->where('q.status', $opts['status']);
        if (!empty($opts['purpose'])) $this->db->where('q.purpose', $opts['purpose']);
        if (!empty($opts['user_id'])) $this->db->where('q.user_id', (int)$opts['user_id']);
        $this->db->order_by('q.id', 'DESC')->limit((int)($opts['limit'] ?? 300), (int)($opts['offset'] ?? 0));
        return $this->db->get()->result_array();
    }

    public function find($id)
    {
        return $this->db->get_where('blockchain_payout_queue', ['id' => (int)$id])->row_array();
    }

    /* ------------------------------ writes ------------------------------ */

    /**
     * Admin retry: reset a FAILED/RETRY row back to PENDING so the next cron
     * tick rebroadcasts it. Full state reset (including retry_count -> 0) so
     * a human fixing the underlying cause (e.g. funding the treasury) gives
     * the row a fresh automatic-retry budget. Never credits/debits anything.
     */
    public function retry($id, $adminId = null)
    {
        $id = (int)$id;
        $row = $this->find($id);
        if (!$row) return [false, 'Payout not found.'];
        if (!in_array($row['status'], ['FAILED', 'RETRY'], true)) {
            return [false, 'Only FAILED or RETRY payouts can be retried (current status: '.$row['status'].').'];
        }

        $this->db->where('id', $id)->where_in('status', ['FAILED', 'RETRY'])->update('blockchain_payout_queue', [
            'status'         => 'PENDING',
            'tx_hash'        => null,
            'block_number'   => null,
            'confirmations'  => 0,
            'confirmed_at'   => null,
            'last_error'     => null,
            'precheck_json'  => null,
            'retry_count'    => 0,
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);
        if ($this->db->affected_rows() !== 1) return [false, 'Payout state changed before retry could apply — refresh and try again.'];

        log_message('info', "[BINARY_MATCHING_PAYOUT] Admin #{$adminId} reset payout #{$id} ({$row['payout_ref']}) to PENDING for retry.");
        return [true, 'Payout queued for retry — the next cron run will rebroadcast it.'];
    }
}
