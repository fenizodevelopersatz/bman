<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CLI smoke test for the Ceiling Wallet. Run:  php index.php ceilingwallettest run
 * Exercises hold / adjust / release + reads against the real DB, then cleans up.
 * Uses a high, unlikely-to-exist test user id so it never touches real members.
 */
class Ceilingwallettest extends CI_Controller
{
    public function run()
    {
        if (!$this->input->is_cli_request()) show_404();
        $this->load->model('staking/Ceilingwallet_model', 'CW');
        $uid = 999999001; // synthetic test user
        $pass = 0; $fail = 0;
        $ok = function ($cond, $label) use (&$pass, &$fail) {
            echo ($cond ? "  ok   " : "  FAIL ") . $label . "\n";
            $cond ? $pass++ : $fail++;
        };

        // clean slate
        $this->db->where('user_id', $uid)->delete('ceiling_wallet');
        $this->db->where('user_id', $uid)->delete('ceiling_wallet_ledger');

        // 1) hold 100
        list($h1) = $this->CW->hold($uid, 100, ['reference_type' => 'binary_matching', 'reference_id' => 'TEST-RUN']);
        $ok($h1, 'hold 100 succeeds');
        $ok(abs((float)$this->CW->balance($uid)['held_balance'] - 100) < 0.001, 'held_balance == 100');

        // 2) hold another 50 -> 150
        $this->CW->hold($uid, 50, ['reference_type' => 'binary_matching', 'reference_id' => 'TEST-RUN']);
        $b = $this->CW->balance($uid);
        $ok(abs((float)$b['held_balance'] - 150) < 0.001, 'held_balance == 150 after 2nd hold');
        $ok(abs((float)$b['total_held'] - 150) < 0.001, 'total_held == 150');

        // 3) adjust -30 -> 120
        list($a1) = $this->CW->adjust($uid, -30, ['reference_type' => 'admin_adjust']);
        $ok($a1, 'adjust -30 succeeds');
        $ok(abs((float)$this->CW->balance($uid)['held_balance'] - 120) < 0.001, 'held_balance == 120');

        // 4) over-release rejected
        list($r0, $rm) = $this->CW->release($uid, 999, ['credit_wallet' => false]);
        $ok(!$r0, 'over-release rejected: ' . $rm);

        // 5) release 20 (no user credit, just reduce hold) -> 100
        list($r1) = $this->CW->release($uid, 20, ['credit_wallet' => false, 'reference_type' => 'admin_release']);
        $ok($r1, 'release 20 succeeds');
        $b = $this->CW->balance($uid);
        $ok(abs((float)$b['held_balance'] - 100) < 0.001, 'held_balance == 100 after release');
        $ok(abs((float)$b['total_released'] - 20) < 0.001, 'total_released == 20');

        // 6) ledger has 4 rows (2 hold, 1 adjust, 1 release)
        $rows = $this->CW->history(['user_id' => $uid, 'limit' => 50]);
        $ok(count($rows) === 4, 'ledger has 4 rows (got ' . count($rows) . ')');

        // 7) totalHeld includes our test balance
        $ok($this->CW->totalHeld() >= 100, 'totalHeld >= 100');

        // cleanup
        $this->db->where('user_id', $uid)->delete('ceiling_wallet');
        $this->db->where('user_id', $uid)->delete('ceiling_wallet_ledger');

        echo "\nCeiling Wallet: {$pass} passed, {$fail} failed.\n";
    }
}
