<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * STEP 14 TEST 16 / STEP 30 — Group Incentive canonical-config regression.
 * Drives the REAL admin save path (Staking_model::saveRank) and proves both
 * threshold columns move together. Restores the original values, also from a
 * shutdown hook so a fatal cannot strand an edited live rank threshold.
 *   php index.php ranksynctest run
 */
class Ranksynctest extends CI_Controller
{
    private $backup = null;
    private $restored = false;

    public function run()
    {
        if (!$this->input->is_cli_request()) show_404();
        $this->load->model('Staking_model');

        // Read the target rank dynamically — no hardcoded tier, name or amount.
        $rank = $this->db->where('tier_level', 1)->get('staking_ranks')->row_array();
        if (!$rank) { echo "ABORT: no tier-1 rank configured.\n"; return; }
        $this->backup = $rank;
        register_shutdown_function([$this, 'restore']);

        $res = [];
        $t = function ($id, $name, $expected, $actual, $note = '') use (&$res) {
            $pass = ((string)$expected === (string)$actual);
            $res[] = $pass;
            printf("%s  %-8s %s\n", $pass ? 'PASS' : 'FAIL', $id, $name);
            printf("          expected: %s\n          actual  : %s%s\n", $expected, $actual,
                   $note !== '' ? "\n          note    : {$note}" : '');
        };

        $orig = (float)$rank['group_incentive'];
        $probe = $orig + 1234;   // derived from live config, never a literal threshold

        // TEST 16 — one canonical input updates BOTH columns.
        list($ok, $msg) = $this->Staking_model->saveRank((int)$rank['id'], ['group_incentive' => $probe], 0);
        $after = $this->db->where('id', (int)$rank['id'])->get('staking_ranks')->row_array();
        $t('TEST 16', 'Group Incentive edit updates both columns',
           'ok=1 gi=' . $probe . ' rgv=' . $probe,
           'ok=' . (int)$ok . ' gi=' . (float)$after['group_incentive'] . ' rgv=' . (float)$after['required_group_volume'],
           $ok ? '' : $msg);

        // TEST 16b — the engine's column (required_group_volume) really moved,
        // so a threshold change actually affects qualification.
        $t('TEST 16b', 'Achievement engine column reflects the change',
           (string)$probe, (string)(float)$after['required_group_volume'],
           'Rankachievement_model reads required_group_volume');

        // TEST 16c — columns cannot be driven apart via the save path.
        $this->Staking_model->saveRank((int)$rank['id'],
            ['group_incentive' => $probe + 500, 'required_group_volume' => 999999], 0);
        $split = $this->db->where('id', (int)$rank['id'])->get('staking_ranks')->row_array();
        $t('TEST 16c', 'Columns cannot diverge even if both are posted',
           'equal', ((float)$split['group_incentive'] === (float)$split['required_group_volume']) ? 'equal' : 'DIVERGED',
           'posted a conflicting required_group_volume; canonical value must win');

        // Validation guards.
        list($nOk) = $this->Staking_model->saveRank((int)$rank['id'], ['group_incentive' => -5], 0);
        $t('TEST 16d', 'Negative Group Incentive rejected', 'rejected', $nOk ? 'ACCEPTED' : 'rejected');
        list($aOk) = $this->Staking_model->saveRank((int)$rank['id'], ['group_incentive' => 'abc'], 0);
        $t('TEST 16e', 'Non-numeric Group Incentive rejected', 'rejected', $aOk ? 'ACCEPTED' : 'rejected');

        $this->restore();
        $final = $this->db->where('id', (int)$rank['id'])->get('staking_ranks')->row_array();
        $t('TEST 16f', 'Original configuration restored',
           'gi=' . $orig . ' rgv=' . (float)$this->backup['required_group_volume'],
           'gi=' . (float)$final['group_incentive'] . ' rgv=' . (float)$final['required_group_volume']);

        $pass = count(array_filter($res));
        echo "\n{$pass}/" . count($res) . " rank config tests passed.\n";
    }

    public function restore()
    {
        if ($this->restored || !$this->backup) return;
        $this->restored = true;
        $this->db->where('id', (int)$this->backup['id'])->update('staking_ranks', [
            'group_incentive'       => $this->backup['group_incentive'],
            'required_group_volume' => $this->backup['required_group_volume'],
        ]);
    }
}
