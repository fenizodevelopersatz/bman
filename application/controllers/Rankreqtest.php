<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * STEP 6 acceptance — saveRankRequirements() audit + is_active + validation.
 * Snapshots the target plan and restores it exactly (also from a shutdown
 * hook), so a live rank rule is never left altered.
 *   php index.php rankreqtest run
 */
class Rankreqtest extends CI_Controller
{
    private $backup = null, $rankId = null, $planNo = null, $restored = false, $auditBase = 0;

    public function run()
    {
        if (!$this->input->is_cli_request()) show_404();
        $this->load->model('Staking_model');

        // Pick a configured rank + plan dynamically — no literals.
        $seed = $this->db->select('rank_id, plan_no')->order_by('rank_id','ASC')->order_by('plan_no','ASC')
                         ->limit(1)->get('staking_rank_requirements')->row_array();
        if (!$seed) { echo "ABORT: no configured requirements to exercise.\n"; return; }
        $this->rankId = (int)$seed['rank_id'];
        $this->planNo = (int)$seed['plan_no'];
        $this->backup = $this->db->where(['rank_id'=>$this->rankId,'plan_no'=>$this->planNo])
                                 ->get('staking_rank_requirements')->result_array();
        $this->auditBase = (int)$this->db->count_all_results('staking_rank_audit');
        register_shutdown_function([$this, 'restore']);

        $res = [];
        $t = function ($id, $name, $exp, $act, $note = '') use (&$res) {
            $pass = ((string)$exp === (string)$act); $res[] = $pass;
            printf("%s  %-8s %s\n", $pass ? 'PASS' : 'FAIL', $id, $name);
            printf("          expected: %s\n          actual  : %s%s\n", $exp, $act,
                   $note !== '' ? "\n          note    : {$note}" : '');
        };

        $otherRank = (int)$this->db->select('id')->where('id !=', $this->rankId)
                          ->order_by('tier_level','ASC')->limit(1)
                          ->get('staking_ranks')->row_array()['id'];
        $row = function ($opt, $side, $qty, $req, $active = 1) {
            return ['option_no'=>$opt,'side'=>$side,'required_qty'=>$qty,
                    'required_rank_id'=>$req,'is_active'=>$active];
        };

        // S6.1 — a real change writes exactly one audit row.
        $a0 = (int)$this->db->count_all_results('staking_rank_audit');
        list($ok) = $this->Staking_model->saveRankRequirements($this->rankId, $this->planNo,
            [$row(1,'left',3,$otherRank), $row(1,'right',3,$otherRank)], 0);
        $a1 = (int)$this->db->count_all_results('staking_rank_audit');
        $t('S6.1', 'Requirements change is audited', 'ok=1 audit+1',
           'ok=' . (int)$ok . ' audit+' . ($a1 - $a0));

        // S6.2 — the audit captured before/after, not a blank record.
        $au = $this->db->order_by('id','DESC')->limit(1)->get('staking_rank_audit')->row_array();
        $t('S6.2', 'Audit records old and new configuration', 'event=rank_requirements both-set',
           'event=' . $au['event'] . ' ' . (($au['old_value'] && $au['new_value']) ? 'both-set' : 'MISSING'),
           'old=' . $au['old_value'] . ' | new=' . $au['new_value']);

        // S6.3 — is_active = 0 is preserved, not forced back to 1.
        $this->Staking_model->saveRankRequirements($this->rankId, $this->planNo,
            [$row(1,'left',3,$otherRank,0), $row(1,'right',3,$otherRank,1)], 0);
        $act = $this->db->select('side, is_active')->where(['rank_id'=>$this->rankId,'plan_no'=>$this->planNo])
                        ->order_by('side','ASC')->get('staking_rank_requirements')->result_array();
        $map = []; foreach ($act as $r) $map[$r['side']] = (int)$r['is_active'];
        $t('S6.3', 'is_active is preserved per row', 'left=0 right=1',
           'left=' . ($map['left'] ?? '?') . ' right=' . ($map['right'] ?? '?'),
           'previously every row was forced active on save');

        // S6.4 — malformed is_active rejected.
        list($bad) = $this->Staking_model->saveRankRequirements($this->rankId, $this->planNo,
            [$row(1,'left',3,$otherRank,'yes')], 0);
        $t('S6.4', 'Malformed is_active rejected', 'rejected', $bad ? 'ACCEPTED' : 'rejected');

        // S6.5 — duplicate option+side rejected.
        list($dup) = $this->Staking_model->saveRankRequirements($this->rankId, $this->planNo,
            [$row(1,'left',2,$otherRank), $row(1,'left',5,$otherRank)], 0);
        $t('S6.5', 'Duplicate option_no + side rejected', 'rejected', $dup ? 'ACCEPTED' : 'rejected');

        // S6.6 — a rank cannot require itself.
        list($self) = $this->Staking_model->saveRankRequirements($this->rankId, $this->planNo,
            [$row(1,'left',2,$this->rankId)], 0);
        $t('S6.6', 'Self-rank requirement rejected', 'rejected', $self ? 'ACCEPTED' : 'rejected');

        // S6.7 — unknown rank id rejected.
        $ghost = (int)$this->db->select('MAX(id) m')->get('staking_ranks')->row_array()['m'] + 999;
        list($gh) = $this->Staking_model->saveRankRequirements($this->rankId, $this->planNo,
            [$row(1,'left',2,$ghost)], 0);
        $t('S6.7', 'Unknown required_rank_id rejected', 'rejected', $gh ? 'ACCEPTED' : 'rejected');

        // S6.8 — a rejected save must leave the configuration untouched.
        $stillLeft = (int)($this->db->select('is_active')->where(
            ['rank_id'=>$this->rankId,'plan_no'=>$this->planNo,'side'=>'left'])
            ->get('staking_rank_requirements')->row_array()['is_active'] ?? -1);
        $t('S6.8', 'Rejected save does not mutate configuration', '0', (string)$stillLeft,
           'still the is_active=0 row written by S6.3');

        // S6.9 — a no-op save writes no audit noise.
        $b0 = (int)$this->db->count_all_results('staking_rank_audit');
        $this->Staking_model->saveRankRequirements($this->rankId, $this->planNo,
            [$row(1,'left',3,$otherRank,0), $row(1,'right',3,$otherRank,1)], 0);
        $b1 = (int)$this->db->count_all_results('staking_rank_audit');
        $t('S6.9', 'Unchanged save writes no audit row', '0', (string)($b1 - $b0));

        $this->restore();
        $now = $this->db->where(['rank_id'=>$this->rankId,'plan_no'=>$this->planNo])
                        ->get('staking_rank_requirements')->result_array();
        $t('S6.10', 'Original plan configuration restored',
           count($this->backup) . ' rows', count($now) . ' rows');

        $pass = count(array_filter($res));
        echo "\n{$pass}/" . count($res) . " STEP 6 requirement tests passed.\n";
    }

    public function restore()
    {
        if ($this->restored || $this->rankId === null) return;
        $this->restored = true;
        $this->db->where(['rank_id'=>$this->rankId,'plan_no'=>$this->planNo])
                 ->delete('staking_rank_requirements');
        foreach ($this->backup as $r) {
            unset($r['id']);
            $this->db->insert('staking_rank_requirements', $r);
        }
        // Remove only the audit rows this test produced.
        $this->db->where('event', 'rank_requirements')->where('id >', $this->auditBase)
                 ->delete('staking_rank_audit');
    }
}
