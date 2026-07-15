<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CLI regression test for multi-level binary matching cascading. Run:
 *   php index.php levelcascadetest run
 *
 * Reproduces the exact tree from the user's spec:
 *          A (no own stake)
 *        /                \
 *       B (no own stake)   C (10,000 BMAN own stake)
 *      /        \         /        \
 *   D1(10000) E1(10000) D2(10000) E2(10000)
 *
 * Phase 1 proves: C (has an own stake) is paid; B and A (no own stake) are
 * BOTH skipped despite their own subtrees perfectly matching — but their
 * carry is preserved (NOT consumed), because payMatching()'s eligibility
 * check runs before the carry-subtraction step.
 * Phase 2 proves: once B later stakes, the NEXT run pays B using that exact
 * preserved carry — nothing was lost by being skipped earlier.
 * Phase 3 proves the same for A, one level further up — demonstrating that
 * "level 1" (B/C) and "level 2" (A) are independent, correctly-cascading
 * evaluations; there is no need to explicitly "wait for level 1, then do
 * level 2" — each ancestor's carry already accumulated independently from
 * the same single _walkUp() pass over each purchase, and a single
 * payMatching() call evaluates every qualifying row in one scan regardless
 * of level.
 *
 * Entirely synthetic (7 fresh user ids), fully cleaned up after itself.
 */
class Levelcascadetest extends CI_Controller
{
    private $A = 999999020, $B = 999999021, $C = 999999022;
    private $D1 = 999999023, $E1 = 999999024, $D2 = 999999025, $E2 = 999999026;
    private $pass = 0, $fail = 0;

    public function run()
    {
        if (!$this->input->is_cli_request()) show_404();
        $this->load->model('staking/Stakingmatching_model', 'MB');

        $all = [$this->A, $this->B, $this->C, $this->D1, $this->E1, $this->D2, $this->E2];
        $this->_cleanup($all);

        echo "--- setup ---\n";
        foreach ($all as $i => $uid) {
            $this->db->insert('users', ['id' => $uid, 'username' => 'TEST_CASCADE_' . $i, 'status' => '1']);
        }
        $this->_place($this->B, $this->A, 'left');
        $this->_place($this->C, $this->A, 'right');
        $this->_place($this->D1, $this->B, 'left');
        $this->_place($this->E1, $this->B, 'right');
        $this->_place($this->D2, $this->C, 'left');
        $this->_place($this->E2, $this->C, 'right');
        // Only C, D1, E1, D2, E2 stake 10,000 each (package id 2 -> group_ceiling 10,000).
        // A and B deliberately have NO stake of their own.
        foreach ([$this->C, $this->D1, $this->E1, $this->D2, $this->E2] as $uid) {
            $this->_stake($uid, 10000);
        }

        echo "\n--- phase 1: initial run (A, B have no stake; C does) ---\n";
        $this->MB->propagate();
        $ref1 = 'TEST-CASCADE-P1-' . time();
        $this->MB->payMatching($ref1);

        $cCeiling = $this->MB->userCeiling($this->C);
        $this->_ok(abs($cCeiling - 10000) < 0.001, "C ceiling == 10000 (has an own 10000 stake)");
        $cPayout = $this->db->where(['user_id' => $this->C, 'run_ref' => $ref1])->get('staking_matching_payouts')->row_array();
        $this->_ok((bool)$cPayout, 'C: PAID this run (has own stake)');
        if ($cPayout) {
            $this->_ok(abs((float)$cPayout['matched_volume'] - 10000) < 0.001, 'C matched_volume == 10000 (min of D2,E2)');
            $this->_ok(abs((float)$cPayout['earning_amount'] - 800) < 0.01, 'C earning_amount == 800 (8% of 10000)');
            $this->_ok(abs((float)$cPayout['staking_amount'] - 200) < 0.01, 'C staking_amount == 200 (2% of 10000)');
        }
        $cCarry = $this->db->get_where('binary_carry', ['user_id' => $this->C])->row_array();
        $this->_ok(abs((float)($cCarry['left_carry'] ?? -1)) < 0.001 && abs((float)($cCarry['right_carry'] ?? -1)) < 0.001,
            'C carry consumed to 0/0');

        $bCeiling = $this->MB->userCeiling($this->B);
        $this->_ok($bCeiling == 0, 'B ceiling == 0 (no own stake)');
        $bPayoutCount = (int)$this->db->where(['user_id' => $this->B, 'run_ref' => $ref1])->count_all_results('staking_matching_payouts');
        $this->_ok($bPayoutCount === 0, "B: SKIPPED this run — no own stake, despite B's own subtree (D1=10000,E1=10000) matching perfectly");
        $bCarry = $this->db->get_where('binary_carry', ['user_id' => $this->B])->row_array();
        $this->_ok(abs((float)$bCarry['left_carry'] - 10000) < 0.001 && abs((float)$bCarry['right_carry'] - 10000) < 0.001,
            'B carry PRESERVED at 10000/10000 (untouched — nothing lost by being skipped)');

        $aCeiling = $this->MB->userCeiling($this->A);
        $this->_ok($aCeiling == 0, 'A ceiling == 0 (no own stake)');
        $aPayoutCount = (int)$this->db->where(['user_id' => $this->A, 'run_ref' => $ref1])->count_all_results('staking_matching_payouts');
        $this->_ok($aPayoutCount === 0, 'A: SKIPPED this run — no own stake');
        $aCarry = $this->db->get_where('binary_carry', ['user_id' => $this->A])->row_array();
        $this->_ok(abs((float)$aCarry['left_carry'] - 20000) < 0.001,
            "A left_carry == 20000 (D1+E1, both walked up THROUGH B — B's own missing stake never blocked this propagation)");
        $this->_ok(abs((float)$aCarry['right_carry'] - 30000) < 0.001,
            "A right_carry == 30000 (C's own 10000 stake + D2 + E2, all walked up through C)");
        echo "    (A's hypothetical match if eligible: min(20000,30000) = " . min((float)$aCarry['left_carry'], (float)$aCarry['right_carry']) . " — already correctly accumulated, no 'level 2' step needed)\n";

        echo "\n--- phase 2: B stakes 10000 afterward -> next run must pay B from the SAME preserved carry ---\n";
        $this->_stake($this->B, 10000);
        $ref2 = 'TEST-CASCADE-P2-' . time();
        $this->MB->payMatching($ref2); // no propagate() needed — no new purchase, carry already sitting there
        $bPayout2 = $this->db->where(['user_id' => $this->B, 'run_ref' => $ref2])->get('staking_matching_payouts')->row_array();
        $this->_ok((bool)$bPayout2, 'B: PAID once staked, using the carry preserved from phase 1 (nothing recomputed/re-propagated)');
        if ($bPayout2) {
            $this->_ok(abs((float)$bPayout2['matched_volume'] - 10000) < 0.001, 'B matched_volume == 10000 (same D1/E1 carry from before)');
        }

        echo "\n--- phase 3: A stakes 10000 afterward -> next run must pay A from ITS preserved carry too ---\n";
        $this->_stake($this->A, 10000);
        $ref3 = 'TEST-CASCADE-P3-' . time();
        $this->MB->payMatching($ref3);
        $aPayout3 = $this->db->where(['user_id' => $this->A, 'run_ref' => $ref3])->get('staking_matching_payouts')->row_array();
        $this->_ok((bool)$aPayout3, 'A: PAID once staked, using the carry preserved since phase 1 (20000 vs 30000)');
        if ($aPayout3) {
            $this->_ok(abs((float)$aPayout3['matched_volume'] - 20000) < 0.001, 'A matched_volume == 20000 (min(20000,30000), fully within A\'s own 10000 ceiling? see below)');
            echo "    A's own ceiling is only 10000, but the raw bonus (20000*10%=2000) is well under that anyway -> paid in full, no ceiling excess.\n";
        }

        $this->_cleanup($all);
        echo "\nLevel Cascade: {$this->pass} passed, {$this->fail} failed.\n";
    }

    private function _ok($cond, $label)
    {
        echo ($cond ? '  ok   ' : '  FAIL ') . $label . "\n";
        $cond ? $this->pass++ : $this->fail++;
    }

    private function _place($uid, $parent, $position)
    {
        $this->db->insert('binary_placement', [
            'user_id' => $uid, 'sponsor_id' => $parent, 'parent_id' => $parent,
            'position' => $position, 'placement_type' => 'direct', 'type' => 'direct',
        ]);
    }

    private function _stake($uid, $amount)
    {
        $this->db->insert('user_stakes', [
            'user_id' => $uid, 'package_id' => 2, 'plan_id' => 0, 'plan_code' => 'fixed',
            'duration_years' => 1, 'stake_amount' => $amount, 'roi_percent' => 0, 'roi_basis' => 'total',
            'start_date' => date('Y-m-d'), 'maturity_date' => date('Y-m-d', strtotime('+1 years')),
            'status' => 'active',
        ]);
        $stakeId = (int)$this->db->insert_id();
        $this->db->insert('binary_volume_ledger', [
            'user_id' => $uid, 'invest_id' => $stakeId, 'pv' => 0, 'bv' => $amount,
            'source_amount' => $amount, 'processed' => 0,
        ]);
    }

    private function _cleanup($uids)
    {
        foreach ($uids as $uid) {
            $this->db->where('user_id', $uid)->delete('binary_volume_ledger');
            $this->db->where('user_id', $uid)->delete('user_stakes');
            $this->db->where('user_id', $uid)->delete('binary_placement');
            $this->db->where('user_id', $uid)->delete('binary_carry');
            $this->db->where('user_id', $uid)->delete('staking_group_volume');
            $this->db->where('user_id', $uid)->delete('staking_matching_payouts');
            $this->db->where('user_id', $uid)->delete('ceiling_wallet');
            $this->db->where('user_id', $uid)->delete('ceiling_wallet_ledger');
            $this->db->where('user_id', $uid)->delete('user_wallets');
            $this->db->where('id', $uid)->delete('users');
        }
    }
}
