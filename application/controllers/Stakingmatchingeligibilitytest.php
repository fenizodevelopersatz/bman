<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CLI regression test for the "must have an active stake to be eligible for
 * binary matching" gate in Stakingmatching_model::payMatching(). Run:
 *   php index.php stakingmatchingeligibilitytest run
 *
 * Uses two REAL, already-existing users rather than synthetic accounts, since
 * payMatching() requires a real `users` row to check status — fabricating a
 * throwaway user account is far more invasive than temporarily seeding
 * `binary_carry` (the only table this test writes to) for two accounts that
 * already exist and whose ceiling state it re-verifies live rather than
 * assuming, so a false result is impossible if the real data ever changes.
 */
class Stakingmatchingeligibilitytest extends CI_Controller
{
    public function run()
    {
        if (!$this->input->is_cli_request()) show_404();
        $this->load->model('staking/Stakingmatching_model', 'MB');

        $noStakeUid = 1; // Admin — expected: no active user_stakes row
        $stakedUid  = 3; // Siva — expected: at least one active user_stakes row

        $pass = 0; $fail = 0;
        $ok = function ($cond, $label) use (&$pass, &$fail) {
            echo ($cond ? "  ok   " : "  FAIL ") . $label . "\n";
            $cond ? $pass++ : $fail++;
        };

        $ceilNoStake = $this->MB->userCeiling($noStakeUid);
        $ceilStaked  = $this->MB->userCeiling($stakedUid);
        $ok($ceilNoStake == 0, "precondition: user {$noStakeUid} has zero ceiling / no active stake (got {$ceilNoStake})");
        $ok($ceilStaked > 0, "precondition: user {$stakedUid} has a positive ceiling / active stake (got {$ceilStaked})");
        if ($ceilNoStake != 0 || $ceilStaked <= 0) {
            echo "\nPreconditions not met against current real data — aborting rather than risk a false result.\n";
            echo "{$pass} passed, {$fail} failed.\n";
            return;
        }

        $this->_cleanup([$noStakeUid, $stakedUid]);

        // Seed a qualifying carry for both: match = min(10,5) = 5 either way.
        $this->db->insert('binary_carry', ['user_id' => $noStakeUid, 'left_carry' => 10, 'right_carry' => 5]);
        $this->db->insert('binary_carry', ['user_id' => $stakedUid,  'left_carry' => 10, 'right_carry' => 5]);

        $runRef = 'TEST-ELIGIBILITY-' . time();
        $this->MB->payMatching($runRef);

        // No active stake -> must be skipped entirely: no payout row, carry left untouched.
        $payoutNoStake = (int)$this->db->where(['user_id' => $noStakeUid, 'run_ref' => $runRef])
                                        ->count_all_results('staking_matching_payouts');
        $carryNoStake = $this->db->get_where('binary_carry', ['user_id' => $noStakeUid])->row_array();
        $ok($payoutNoStake === 0, 'user with NO active stake: no staking_matching_payouts row created');
        $ok(abs((float)$carryNoStake['left_carry'] - 10.0) < 0.001 && abs((float)$carryNoStake['right_carry'] - 5.0) < 0.001,
            'user with NO active stake: carry completely untouched (still 10/5)');

        // Active stake -> must be paid (capped at their own ceiling, whatever it is).
        $payoutStaked = $this->db->where(['user_id' => $stakedUid, 'run_ref' => $runRef])
                                 ->get('staking_matching_payouts')->row_array();
        $ok((bool)$payoutStaked, 'user WITH an active stake: staking_matching_payouts row created');
        if ($payoutStaked) {
            $ok(abs((float)$payoutStaked['matched_volume'] - 5.0) < 0.001, 'matched_volume == min(10,5) == 5');
        }
        $carryStaked = $this->db->get_where('binary_carry', ['user_id' => $stakedUid])->row_array();
        $ok(abs((float)$carryStaked['left_carry'] - 5.0) < 0.001 && abs((float)$carryStaked['right_carry'] - 0.0) < 0.001,
            'user WITH an active stake: carry consumed (match=5 subtracted from both legs)');

        $this->_cleanup([$noStakeUid, $stakedUid], $runRef);
        echo "\nStaking Matching Eligibility: {$pass} passed, {$fail} failed.\n";
    }

    private function _cleanup($uids, $runRef = null)
    {
        foreach ($uids as $uid) {
            $this->db->where('user_id', $uid)->delete('binary_carry');
            $this->db->where('user_id', $uid)->delete('staking_matching_payouts');
            if ($runRef) {
                $this->db->where(['user_id' => $uid, 'reference_id' => $runRef])->delete('ceiling_wallet_ledger');
            }
        }
    }
}
