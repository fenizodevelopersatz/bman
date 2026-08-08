<?php defined('BASEPATH') OR exit('No direct script access allowed');

/** STEP 7 verification — read-only smoke of the rank progress service.
 *  php index.php rankprogresstest run */
class Rankprogresstest extends CI_Controller
{
    public function run()
    {
        if (!$this->input->is_cli_request()) show_404();
        $this->load->model('staking/Rankprogress_model', 'RP');

        $res = [];
        $t = function ($id, $name, $expected, $actual, $note = '') use (&$res) {
            $pass = ((string)$expected === (string)$actual);
            $res[] = $pass;
            printf("%s  %-8s %s\n", $pass ? 'PASS' : 'FAIL', $id, $name);
            printf("          expected: %s\n          actual  : %s%s\n", $expected, $actual,
                   $note !== '' ? "\n          note    : {$note}" : '');
        };

        $uid = (int)$this->db->query(
            "SELECT parent_id uid FROM binary_placement WHERE parent_id > 0 ORDER BY parent_id LIMIT 1"
        )->row_array()['uid'];

        $p = $this->RP->forUser($uid);
        if (!$p) { echo "ABORT: no payload for user {$uid}\n"; return; }

        // T1 — payload shape complete (every field the UIs consume).
        $need = ['current_rank','current_tier','current_rank_id','next_rank','next_tier','next_rank_id',
                 'group_incentive','matching','plans','qualifying_plan','history','at_max_rank','qualifies_now'];
        $missing = array_values(array_diff($need, array_keys($p)));
        $t('T1', 'Payload contains every documented field', '[]', json_encode($missing));

        // T2 — Group Incentive equals the reused calculator, not a re-derivation.
        $this->load->model('staking/Rankcalculator_model', 'calc');
        $v = $this->calc->calculateBonusVolume($uid);
        $t('T2', 'Group Incentive achieved == calculateBonusVolume total',
           (string)round((float)$v['total_volume'], 4), (string)$p['group_incentive']['achieved']);

        // T3 — earning + staking reconcile to the total.
        $t('T3', 'Earning + Staking == Total',
           (string)round($p['matching']['earning'] + $p['matching']['staking'], 4),
           (string)$p['matching']['total']);

        // T4 — admin overflow must not leak into the member figure.
        $ov = (float)($this->db->query(
            "SELECT COALESCE(SUM(admin_overflow),0) s FROM staking_matching_payouts WHERE user_id = ?", [$uid]
        )->row_array()['s'] ?? 0);
        $credited = (float)($this->db->query(
            "SELECT COALESCE(SUM(earning_amount+staking_amount),0) s FROM staking_matching_payouts WHERE user_id = ?", [$uid]
        )->row_array()['s'] ?? 0);
        $t('T4', 'Admin overflow excluded from Group Incentive',
           (string)round($credited, 4), (string)$p['matching']['total'],
           'admin_overflow for this member = ' . $ov);

        // T5 — thresholds come from the database, never a constant.
        $dbReq = (float)($this->db->query(
            "SELECT group_incentive g FROM staking_ranks WHERE id = ?", [$p['next_rank_id'] ?: $p['current_rank_id']]
        )->row_array()['g'] ?? 0);
        $t('T5', 'Requirement read from staking_ranks',
           (string)round($dbReq, 4), (string)$p['group_incentive']['required']);

        // T6 — hierarchy: next tier is strictly above current.
        $t('T6', 'Next rank is the next tier up',
           'true', ($p['at_max_rank'] || $p['next_tier'] > $p['current_tier']) ? 'true' : 'false',
           'current tier ' . $p['current_tier'] . ' -> next ' . var_export($p['next_tier'], true));

        // T7 — plans evaluated from configuration, with failures itemised.
        $reqRows = (int)$this->db->where('rank_id', $p['next_rank_id'] ?: $p['current_rank_id'])
                                 ->where('is_active', 1)->count_all_results('staking_rank_requirements');
        $items = 0; foreach ($p['plans'] as $pl) $items += count($pl['requirements']);
        $t('T7', 'Every configured requirement is evaluated and reported',
           (string)$reqRows, (string)$items,
           count($p['plans']) . ' plan(s); failures are itemised, not hidden');

        // T8 — no rank name or threshold hardcoded in the service.
        $src = file_get_contents(APPPATH . 'models/staking/Rankprogress_model.php');
        $bad = [];
        foreach (['IRON','BRONZE','SILVER','GOLD','PLATINUM','EMERALD','DIAMOND','MASTER','GRANDMASTER','CHALLENGER',
                  '7500','30000','150000','600000','2500000'] as $n) {
            if (strpos($src, $n) !== false) $bad[] = $n;
        }
        $t('T8', 'No hardcoded rank name or threshold in the service', '[]', json_encode($bad));

        printf("\nservice: user=%s current=%s next=%s incentive=%s/%s (%s%%) plans=%d qualifying=%s\n",
            $p['user']['name'], $p['current_rank'], $p['next_rank'] ?? '(max)',
            $p['group_incentive']['achieved'], $p['group_incentive']['required'],
            $p['group_incentive']['percentage'], count($p['plans']),
            var_export($p['qualifying_plan'], true));

        $d = $this->RP->rankDistribution();
        printf("distribution: %d users across %d ranks (baseline holds %d)\n",
            $d['total_users'], count($d['ranks']), $d['ranks'][0]['members']);

        $pass = count(array_filter($res));
        echo "\n{$pass}/" . count($res) . " rank progress tests passed.\n";
    }
}
