<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * STEP 8 — Achievement Verification. READ-ONLY: it inspects source and reads
 * data, never runs the achievement engine, so it cannot award a rank.
 *
 * Proves the existing Rankachievement_model qualifies members on exactly the
 * figures Rankprogress_model reports. If the two ever diverge, the admin/user
 * screens would promise a promotion the cron would not grant (or vice versa) —
 * the same class of split-brain already found in the gas configuration.
 *
 *   php index.php rankachievementverify run
 */
class Rankachievementverify extends CI_Controller
{
    public function run()
    {
        if (!$this->input->is_cli_request()) show_404();
        $this->load->model('staking/Rankprogress_model', 'RP');
        $this->load->model('staking/Rankcalculator_model', 'calc');

        $res = [];
        $t = function ($id, $name, $expected, $actual, $note = '') use (&$res) {
            $pass = ((string)$expected === (string)$actual);
            $res[] = $pass;
            printf("%s  %-8s %s\n", $pass ? 'PASS' : 'FAIL', $id, $name);
            printf("          expected: %s\n          actual  : %s%s\n", $expected, $actual,
                   $note !== '' ? "\n          note    : {$note}" : '');
        };

        $ach = file_get_contents(APPPATH . 'models/staking/Rankachievement_model.php');
        $prg = file_get_contents(APPPATH . 'models/staking/Rankprogress_model.php');

        // S8.1 — both consume the SAME volume function, not two derivations.
        $inAch = strpos($ach, 'calculateBonusVolume') !== false;
        $inPrg = strpos($prg, 'calculateBonusVolume') !== false;
        $t('S8.1', 'Achievement + Progress both use calculateBonusVolume()',
           'achievement=yes progress=yes',
           'achievement=' . ($inAch ? 'yes' : 'NO') . ' progress=' . ($inPrg ? 'yes' : 'NO'),
           'single shared source of the member lifetime figure');

        // S8.2 — the shared function sums ONLY credited earning+staking.
        $calcSrc = file_get_contents(APPPATH . 'models/staking/Rankcalculator_model.php');
        $ok = strpos($calcSrc, 'SUM(earning_amount)') !== false
           && strpos($calcSrc, 'SUM(staking_amount)') !== false
           && strpos($calcSrc, 'staking_matching_payouts') !== false;
        $t('S8.2', 'Qualifying volume = earning_amount + staking_amount', 'true', $ok ? 'true' : 'false',
           'from staking_matching_payouts');

        // S8.3 — admin_overflow / raw_bonus never enter the qualifying figure.
        $bad = [];
        foreach (['admin_overflow', 'raw_bonus'] as $col) {
            if (preg_match('/SUM\(\s*' . $col . '/i', $calcSrc)) $bad[] = $col;
            if (preg_match('/SUM\(\s*' . $col . '/i', $ach))     $bad[] = 'achievement:' . $col;
        }
        $t('S8.3', 'admin_overflow / raw_bonus never summed for qualification', '[]', json_encode($bad));

        // S8.4 — projected/pending excluded structurally: a payout row exists
        // only once a level is credited, so there is nothing to filter out.
        // Scoped to LEVEL-WISE rows: legacy carry-engine rows predate the
        // raw_bonus column and carry 0 there, so credited > raw for them —
        // real, harmless legacy data, not an accounting inversion.
        $counts = $this->db->query(
            "SELECT COUNT(*) rows_total,
                    COALESCE(SUM(earning_amount + staking_amount),0) credited,
                    COALESCE(SUM(admin_overflow),0) overflow,
                    COALESCE(SUM(raw_bonus),0) raw
               FROM staking_matching_payouts WHERE level IS NOT NULL"
        )->row_array();
        $legacy = $this->db->query(
            "SELECT COUNT(*) n, COALESCE(SUM(earning_amount + staking_amount),0) credited
               FROM staking_matching_payouts WHERE level IS NULL"
        )->row_array();
        // credited + overflow must reconcile to raw for every level-wise row.
        $delta = round((float)$counts['credited'] + (float)$counts['overflow'] - (float)$counts['raw'], 4);
        $t('S8.4', 'credited + admin_overflow reconciles to raw_bonus',
           '0', (string)$delta,
           'level-wise rows=' . $counts['rows_total'] . ' credited=' . $counts['credited']
           . ' overflow=' . $counts['overflow'] . ' raw=' . $counts['raw']
           . ' | legacy rows=' . $legacy['n'] . ' (credited ' . $legacy['credited']
           . ', raw_bonus absent before the level engine)');

        // S8.5 — no second/downline Group Incentive aggregation anywhere.
        $downline = [];
        foreach (['models/staking/Rankachievement_model.php', 'models/staking/Rankprogress_model.php'] as $f) {
            $src = file_get_contents(APPPATH . $f);
            // A downline aggregation would have to walk placement while summing payouts.
            if (strpos($src, 'binary_placement') !== false && strpos($src, 'staking_matching_payouts') !== false) {
                $downline[] = basename($f);
            }
        }
        $t('S8.5', 'No downline-generated Group Incentive aggregation exists', '[]', json_encode($downline));

        // S8.6 — engine threshold column stays in step with the canonical one.
        $div = (int)($this->db->query(
            "SELECT COALESCE(SUM(group_incentive <> required_group_volume),0) d FROM staking_ranks"
        )->row_array()['d'] ?? -1);
        $t('S8.6', 'group_incentive == required_group_volume for every rank', '0', (string)$div);

        // S8.7 — agreement on a real member: what the UI shows must equal what
        // the engine would test.
        $uid = (int)$this->db->query(
            "SELECT parent_id uid FROM binary_placement WHERE parent_id > 0 ORDER BY parent_id LIMIT 1"
        )->row_array()['uid'];
        $p = $this->RP->forUser($uid);
        $v = $this->calc->calculateBonusVolume($uid);
        $t('S8.7', 'UI figure equals the engine figure for a real member',
           (string)round((float)$v['total_volume'], 4), (string)$p['matching']['total'],
           'user ' . $uid . ' (' . $p['user']['name'] . ')');

        // S8.8 — this verification awarded nothing.
        $t('S8.8', 'Verification created no rank/history rows',
           'uranks=0 hist=0',
           'uranks=' . (int)$this->db->count_all_results('user_ranks')
           . ' hist=' . (int)$this->db->count_all_results('user_rank_history'));

        $pass = count(array_filter($res));
        echo "\n{$pass}/" . count($res) . " achievement verification checks passed.\n";
    }
}
