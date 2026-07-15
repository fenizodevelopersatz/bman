<?php defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'controllers/admin/staking/Genealogytree.php';

/**
 * CLI verification for the admin Genealogytree controller's tree-building +
 * enrichment logic. Run: php index.php genealogytreetest run
 *
 * Read-only against the REAL existing tree (Admin/Satz/Siva/... — the same
 * users used throughout this project's testing) — _buildTree()/
 * _carryAndMatchingStats() never write anything, so this is safe to run
 * against production-shaped data without any cleanup step.
 */
class Genealogytreetest extends Genealogytree
{
    public function run()
    {
        if (!$this->input->is_cli_request()) show_404();
        $pass = 0; $fail = 0;
        $ok = function ($cond, $label) use (&$pass, &$fail) {
            echo ($cond ? '  ok   ' : '  FAIL ') . $label . "\n";
            $cond ? $pass++ : $fail++;
        };

        // Admin(1) -> Satz(2,left)/Siva(3,right) -> Lakmanan(4,left, under Satz) -> ...
        // The real tree is skewed (a long right-leaning chain under Lakmanan down
        // to nandhan at level 6), so depth=4 from Admin reaches exactly 7 of the
        // 9 real users, not all of them — confirmed by counting the actual rows
        // rather than assuming a balanced tree.
        $rows = $this->BinaryModel->getDownlineMembers(1, 4);
        $ok(is_array($rows) && count($rows) === 7, 'getDownlineMembers(1, depth 4) returns exactly 7 users (root + 2 + 1 + 2 + 1, per the real skewed tree) — got ' . count($rows));

        $tree = $this->_buildTree($rows, 1, 4);
        $ok(is_array($tree) && (int)$tree['id'] === 1, 'tree root id == 1 (Admin)');
        $ok(isset($tree['left']['id']) && (int)$tree['left']['id'] === 2, "root's left child == 2 (Satz)");
        $ok(isset($tree['right']['id']) && (int)$tree['right']['id'] === 3, "root's right child == 3 (Siva)");
        $ok(isset($tree['left']['left']['id']) && (int)$tree['left']['left']['id'] === 4, "Satz's left child == 4 (Lakmanan)");

        // Admin has no active stake -> ineligible, per the fix in Stakingmatching_model.
        $ok($tree['own_stake_amount'] == 0, 'Admin (root) own_stake_amount == 0');
        $ok($tree['matching_eligible'] === false, 'Admin (root) matching_eligible == false (no own stake)');

        // Siva (id 3) has 4 active 1-BMAN stakes -> eligible, own_stake_amount == 4.
        $siva = $tree['right'] ?? null;
        $ok((bool)$siva && (int)$siva['id'] === 3, 'located Siva node at root.right');
        if ($siva) {
            $ok(abs((float)$siva['own_stake_amount'] - 4.0) < 0.001, 'Siva own_stake_amount == 4 (4 x 1 BMAN active stakes)');
            $ok(abs((float)$siva['ceiling_amount'] - 4.0) < 0.001, 'Siva ceiling_amount == 4');
            $ok($siva['matching_eligible'] === true, 'Siva matching_eligible == true (has own stake)');
        }

        // Direct spot-check against the standalone stats helper for consistency.
        $direct = $this->_carryAndMatchingStats(3);
        $ok(abs($direct['own_stake_amount'] - ($siva['own_stake_amount'] ?? -1)) < 0.001,
            '_carryAndMatchingStats(3) matches the value embedded in the tree node');

        echo "\nGenealogy Tree (admin) verification: {$pass} passed, {$fail} failed.\n";
    }
}
