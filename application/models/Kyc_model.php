<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Kyc_model extends CI_Model
{
    /* =====================================================================
     * NEW: Controlled KYC state machine.
     * Canonical states are the single source of truth; they map onto the
     * existing kyc_applications.status / users.kyc_status enum values so no
     * schema change or data migration is needed (backward compatible).
     * ===================================================================== */

    // Canonical states
    const S_NOT_SUBMITTED     = 'NOT_SUBMITTED';
    const S_PENDING           = 'PENDING';
    const S_UNDER_REVIEW      = 'UNDER_REVIEW';
    const S_APPROVED          = 'APPROVED';
    const S_RESUBMIT_REQUIRED = 'RESUBMIT_REQUIRED';

    // canonical -> DB enum value stored in kyc_applications.status / users.kyc_status
    private $dbMap = [
        'NOT_SUBMITTED'     => 'none',         // no application row yet
        'PENDING'           => 'pending',
        'UNDER_REVIEW'      => 'under_review',
        'APPROVED'          => 'approved',
        'RESUBMIT_REQUIRED' => 'rejected',     // legacy enum value reused for "resubmit required"
    ];

    // Allowed transitions: action => [ from-states, to-state, reason-required ]
    private $transitions = [
        'submit'               => ['from' => ['NOT_SUBMITTED', 'RESUBMIT_REQUIRED'], 'to' => 'PENDING'],
        'start_review'         => ['from' => ['PENDING'],                            'to' => 'UNDER_REVIEW'],
        'approve'              => ['from' => ['UNDER_REVIEW'],                        'to' => 'APPROVED'],
        'request_resubmission' => ['from' => ['UNDER_REVIEW'],                        'to' => 'RESUBMIT_REQUIRED', 'reason' => true],
    ];

    // canonical state -> DB enum
    public function toDb($state){ return isset($this->dbMap[$state]) ? $this->dbMap[$state] : 'pending'; }

    // DB enum (incl. legacy 'resubmitted') -> canonical state
    public function fromDb($db){
        switch ($db) {
            case 'pending':      return self::S_PENDING;
            case 'under_review': return self::S_UNDER_REVIEW;
            case 'approved':     return self::S_APPROVED;
            case 'rejected':
            case 'resubmitted':  return self::S_RESUBMIT_REQUIRED;
            default:             return self::S_NOT_SUBMITTED; // none / null / ''
        }
    }

    // Users may only upload/edit when NOT_SUBMITTED or RESUBMIT_REQUIRED.
    public function canUserEdit($state){
        return in_array($state, [self::S_NOT_SUBMITTED, self::S_RESUBMIT_REQUIRED], true);
    }

    public function reasonRequired($action){
        return !empty($this->transitions[$action]['reason']);
    }

    // Validate a transition. Returns [true, toState] or [false, errorMessage].
    public function canApply($action, $fromState){
        if (!isset($this->transitions[$action])) return [false, 'Unknown action: ' . $action];
        $t = $this->transitions[$action];
        if (!in_array($fromState, $t['from'], true)) {
            return [false, 'Invalid transition: cannot "' . $action . '" from ' . $fromState . '.'];
        }
        return [true, $t['to']];
    }

    // Apply an admin transition (start_review / approve / request_resubmission):
    // validates against the state machine, updates status + reviewer/date, and logs the
    // transition to kyc_audit_logs. Returns [true, toState, user_id] or [false, errorMessage].
    public function applyAdminAction($kyc_id, $action, $actor_id, $reason = null){
        $row = $this->db->get_where('kyc_applications', ['id' => (int)$kyc_id])->row_array();
        if (!$row) return [false, 'KYC application not found.'];

        $from = $this->fromDb($row['status']);
        list($ok, $toOrErr) = $this->canApply($action, $from);
        if (!$ok) return [false, $toOrErr];
        $to = $toOrErr;

        if ($this->reasonRequired($action) && trim((string)$reason) === '') {
            return [false, 'A resubmission reason is required.'];
        }

        $update = [
            'status'      => $this->toDb($to),
            'reviewed_by' => $actor_id ? (int)$actor_id : null,
            'reviewed_at' => date('Y-m-d H:i:s'),
        ];
        if ($action === 'request_resubmission') {
            $update['review_notes'] = $reason; // shown to the user as the resubmission reason
        }
        $this->db->where('id', (int)$kyc_id)->update('kyc_applications', $update);

        // Log every status transition.
        $this->addAudit($kyc_id, $actor_id, $action, $from . ' -> ' . $to . ($reason ? ' | ' . $reason : ''));

        return [true, $to, (int)$row['user_id']];
    }

     public function getByUser($user_id){
        return $this->db->where('user_id', (int)$user_id)
                        ->order_by('id','DESC')
                        ->get('kyc_applications')->row_array();
    }

    public function createOrUpdate($user_id, array $payload){
        $cur = $this->getByUser($user_id);
        $payload['user_id'] = (int)$user_id;

        if ($cur && in_array($cur['status'], ['pending','under_review','resubmitted','rejected'])) {
            // NEW: resubmission is only allowed after a rejection; keep the simplified
            // 3-state model (pending/approved/rejected) so a resubmit re-enters the queue as 'pending'.
            $this->db->where('id', (int)$cur['id'])->update('kyc_applications', $payload);
            return (int)$cur['id'];
        } else {
            $this->db->insert('kyc_applications', $payload);
            return (int)$this->db->insert_id();
        }
    }

    // NEW: append an entry to the KYC status history (reuses the existing kyc_audit_logs table).
    public function addAudit($kyc_id, $actor_user_id, $action, $notes = null){
        return $this->db->insert('kyc_audit_logs', [
            'kyc_id'        => (int)$kyc_id,
            'actor_user_id' => $actor_user_id ? (int)$actor_user_id : null,
            'action'        => $action,
            'notes'         => $notes,
        ]);
    }

    // NEW: full status history for one KYC application (latest first).
    public function history($kyc_id){
        return $this->db->where('kyc_id', (int)$kyc_id)
                        ->order_by('id','DESC')
                        ->get('kyc_audit_logs')->result_array();
    }

    public function setStatus($kyc_id, $status, $reviewed_by=null, $notes=null, $rej_code=null) {
        return $this->db->where('id', (int)$kyc_id)->update('kyc_applications', [
            'status' => $status,
            'review_notes' => $notes,
            'rejection_code' => $rej_code,
            'reviewed_by' => $reviewed_by,
            'reviewed_at' => date('Y-m-d H:i:s'),
        ]);
    }


}
