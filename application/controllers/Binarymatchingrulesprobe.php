<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CLI front-end for the binary-matching spec probe. Run:
 *   php index.php binarymatchingrulesprobe run
 *
 * All logic (and every safety guard) lives in
 * application/models/staking/Binarymatchingprobe_model.php, so Cron Lab ▸
 * "Binary Matching — Spec Compliance Probe" runs the exact same code path
 * in-process. This controller only formats the result for a terminal.
 *
 * Read-only w.r.t. real data: it builds a synthetic tree, drives the REAL
 * matching engine, prints what happened per business rule, and deletes
 * everything it created. It never invokes BinaryMatchingPayoutCron, so it
 * cannot broadcast on-chain.
 */
class Binarymatchingrulesprobe extends CI_Controller
{
    /** The spec's ten named acceptance tests (§18) against the CURRENT engine.
     *  CLI: php index.php binarymatchingrulesprobe tests */
    public function tests()
    {
        if (!$this->input->is_cli_request()) show_404();

        $this->load->model('staking/Binarymatchingprobe_model', 'probe');
        $res = $this->probe->tests();

        foreach ($res['log'] as $line) echo $line . "\n";
        if ($res['status'] !== 'success') { echo "\n" . $res['message'] . "\n"; return; }

        echo "\n================ TEST SUMMARY ================\n";
        foreach ($res['results'] as $r) {
            printf("%-4s %-8s %s\n", $r['pass'] ? 'PASS' : 'FAIL', $r['test'], $r['name']);
        }
        echo "\n{$res['totals']['pass']} passed, {$res['totals']['fail']} failed.\n";
    }

    public function run()
    {
        if (!$this->input->is_cli_request()) show_404();

        $this->load->model('staking/Binarymatchingprobe_model', 'probe');
        $res = $this->probe->probe();

        foreach ($res['log'] as $line) echo $line . "\n";

        if ($res['status'] !== 'success') {
            echo "\n" . $res['message'] . "\n";
            return;
        }

        echo "\n================ MISMATCH REPORT ================\n";
        $byRule = [];
        foreach ($res['findings'] as $f) $byRule[$f['rule']][] = $f;
        ksort($byRule);
        foreach ($byRule as $rule => $rows) {
            echo "\nRULE {$rule}\n";
            foreach ($rows as $f) echo "  [{$f['verdict']}] {$f['detail']}\n";
        }
        $t = $res['totals'];
        echo "\nTotals: {$t['MATCH']} match, {$t['PARTIAL']} partial, {$t['MISMATCH']} mismatch.\n";
    }
}
