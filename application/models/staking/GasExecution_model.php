<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * GasExecution_model — single authority for whether a staking purchase needs
 * blockchain execution (gas) or is a pure internal ledger transfer.
 *
 * The decision lives on the DATA (coin_distribution_options.execution_mode),
 * not on hardcoded id ranges — after Option 2 (100% Exchange, internal
 * re-stake — see db/coin_distribution_option2_internal.sql) shipped, "id 1 is
 * onchain, ids 2-7 are internal" stopped being true: id 1 is still the only
 * onchain option, but internal options are no longer a contiguous id range
 * (the new Option 2 landed at whatever id the next auto-increment gave it,
 * NOT id 2 — id 2 kept its real id and just got relabeled "Option 3" for
 * display). Reading execution_mode straight off the row means adding another
 * option later needs zero changes here.
 *
 * Business rule, current state:
 *   execution_mode='onchain'  -> exactly one option (100% Exchange, id 1): the
 *                       ONLY option that buys NEW BMAN with USDT. Real on-chain
 *                       transfer: user -> Treasury (USDT), Treasury -> user
 *                       (BMAN), gas charged and recorded. Settles asynchronously
 *                       — StakingPurchasecron carries the order through its
 *                       gas -> usdt -> bonus -> bman legs.
 *   execution_mode='internal' -> every other active option: re-stakes BMAN the
 *                       user ALREADY holds inside the platform (Exchange/
 *                       Earning/Staking/Bonus, per each option's configured
 *                       split — including a pure 100% Exchange split, same as
 *                       the onchain option's percentages but funded from an
 *                       existing balance instead of a new purchase). Pure
 *                       ledger debits, no chain interaction, no gas, no cron:
 *                       Staking_model::restakeFromWallets() completes the
 *                       whole purchase synchronously inside one DB transaction.
 *
 * Every code path that has to answer "does this option need the blockchain?"
 * — the two purchase endpoints' gates in Lendingcontroller, Staking_model::
 * restakeFromWallets()'s own re-check, and the execution_mode/gas_required
 * columns written onto user_stakes — calls decide() instead of re-deriving
 * the split locally, so the rule exists in exactly one place.
 */
class GasExecution_model extends CI_Model
{
    // Fallback-only: the id range internal options occupied BEFORE Option 2
    // existed. Used solely when coin_distribution_options.execution_mode
    // hasn't been migrated in yet, so purchases don't hard-fail mid-rollout.
    const LEGACY_ONCHAIN_OPTION_ID   = 1;
    const LEGACY_INTERNAL_OPTION_MIN = 2;
    const LEGACY_INTERNAL_OPTION_MAX = 7;

    const MODE_ONCHAIN  = 'onchain';
    const MODE_INTERNAL = 'internal';

    /**
     * @param int $coinDistOptionId coin_distribution_options.id
     * @return array{ok:bool, mode:?string, gas_required:?bool, message:?string}
     */
    public function decide($coinDistOptionId)
    {
        $id = (int) $coinDistOptionId;
        if ($id <= 0) {
            return ['ok' => false, 'mode' => null, 'gas_required' => null, 'message' => 'Invalid coin distribution option: ' . $id];
        }

        if ($this->_hasOptionExecutionMode()) {
            $opt = $this->db->select('execution_mode')
                ->get_where('coin_distribution_options', ['id' => $id, 'status' => 1])
                ->row_array();
            if (!$opt) {
                return ['ok' => false, 'mode' => null, 'gas_required' => null, 'message' => 'Invalid coin distribution option: ' . $id];
            }
            $mode = ($opt['execution_mode'] === self::MODE_ONCHAIN) ? self::MODE_ONCHAIN : self::MODE_INTERNAL;
            return ['ok' => true, 'mode' => $mode, 'gas_required' => $mode === self::MODE_ONCHAIN, 'message' => null];
        }

        // Pre-migration fallback (see class docblock).
        if ($id === self::LEGACY_ONCHAIN_OPTION_ID) {
            return ['ok' => true, 'mode' => self::MODE_ONCHAIN, 'gas_required' => true, 'message' => null];
        }
        if ($id >= self::LEGACY_INTERNAL_OPTION_MIN && $id <= self::LEGACY_INTERNAL_OPTION_MAX) {
            return ['ok' => true, 'mode' => self::MODE_INTERNAL, 'gas_required' => false, 'message' => null];
        }
        return ['ok' => false, 'mode' => null, 'gas_required' => null, 'message' => 'Invalid coin distribution option: ' . $id];
    }

    public function isOnchain($coinDistOptionId)
    {
        $d = $this->decide($coinDistOptionId);
        return $d['ok'] && $d['mode'] === self::MODE_ONCHAIN;
    }

    public function isInternal($coinDistOptionId)
    {
        $d = $this->decide($coinDistOptionId);
        return $d['ok'] && $d['mode'] === self::MODE_INTERNAL;
    }

    /**
     * user_stakes.execution_mode / gas_required / gas_fee fields for an
     * internal (re-stake) row. Empty until db/user_stakes_execution_mode.sql
     * has run — same defensive pattern as the is_special snapshot elsewhere
     * in this file's callers, so a pending-migration environment degrades to
     * "column not written" instead of a hard SQL error on every purchase.
     */
    public function internalStakeColumns()
    {
        if (!$this->_hasUserStakesColumns()) return [];
        return ['execution_mode' => self::MODE_INTERNAL, 'gas_required' => 0, 'gas_fee' => 0];
    }

    /** user_stakes.execution_mode / gas_required fields for an onchain (swap-purchase) row. gas_fee is left for the cron/ledger to backfill once known. */
    public function onchainStakeColumns()
    {
        if (!$this->_hasUserStakesColumns()) return [];
        return ['execution_mode' => self::MODE_ONCHAIN, 'gas_required' => 1];
    }

    /** Whether db/user_stakes_execution_mode.sql has run in this environment. */
    public function hasColumns()
    {
        return $this->_hasUserStakesColumns();
    }

    private function _hasUserStakesColumns()
    {
        return $this->db->field_exists('execution_mode', 'user_stakes');
    }

    private function _hasOptionExecutionMode()
    {
        return $this->db->field_exists('execution_mode', 'coin_distribution_options');
    }
}
