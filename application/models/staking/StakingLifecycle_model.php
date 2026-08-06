<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * StakingLifecycle_model — shared rules for every "buy a staking plan" path
 * (USDT purchase via StakingPurchasecron, wallet re-stake via
 * restakeFromWallets(), direct purchase via purchaseStake()). Each path keeps
 * its own funding/debit logic (they genuinely differ), but all three route
 * through here for the parts that must behave identically:
 *
 *  - validatePurchase()        shared package/plan/term/ROI lookup
 *  - creditImmediateBonus()    any bonus (instant 25%, distribution-option
 *                              bonus share, binary matching, rank reward,
 *                              ceiling release) — always skip_maturity
 *  - createRoiRecord()         the ROI/maturity schedule, always on the one
 *                              real, scheduled system (roi_staking_management)
 *                              regardless of which path created the stake
 *  - releaseMaturedPrincipal() the maturity-time release, wallet destination
 *                              read from an admin setting, not hardcoded
 */
class StakingLifecycle_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Walletledger_model', 'L');
        $this->load->model('RoiStakingManagement_model', 'roiMgmt');
    }

    /** Shared package/plan/term/ROI validation (identical logic today
     *  duplicated across purchaseStake(), restakeFromWallets(), and
     *  swap_purchase()'s pre-cron computation). */
    public function validatePurchase($packageId, $planCode, $years)
    {
        $pkg = $this->db->get_where('staking_packages', ['id' => (int)$packageId, 'is_active' => 1])->row_array();
        if (!$pkg) return [false, 'Selected package is not available.'];
        if (!in_array($planCode, ['fixed', 'regular', 'combo'], true)) return [false, 'Invalid plan.'];
        if (!in_array((int)$years, [2, 3, 5], true)) return [false, 'Invalid term.'];

        $plan = $this->db->get_where('staking_plans', ['code' => $planCode, 'is_active' => 1])->row_array();
        if (!$plan) return [false, ucfirst($planCode) . ' plan is not available.'];

        $term = $this->db->get_where('staking_plan_terms',
            ['plan_id' => $plan['id'], 'duration_years' => (int)$years, 'is_active' => 1])->row_array();
        if (!$term) return [false, ucfirst($planCode) . ' plan does not offer a ' . $years . '-year term.'];

        $this->load->model('Staking_model');
        $roi = $this->Staking_model->resolveRoi((int)$pkg['id'], $planCode, (int)$years);
        if (!$roi) return [false, 'ROI is not configured for this package / plan / term.'];

        return [true, ['package' => $pkg, 'plan' => $plan, 'term' => $term, 'roi' => $roi]];
    }

    /** Any staking-derived bonus — instant 25%, a distribution option's bonus
     *  share, binary matching, rank reward, ceiling release. Always
     *  immediately withdrawable, never wallet-maturity-locked. */
    public function creditImmediateBonus($userId, $walletType, $amount, $referenceType, $opts = [])
    {
        if ($amount <= 0) return [true, 'nothing to credit'];
        $opts['skip_maturity'] = true;
        return $this->L->credit((int)$userId, $walletType, $amount, $referenceType, $opts);
    }

    /** Create the ROI/maturity schedule on the one real, live, scheduled
     *  system (roi_staking_management) regardless of which purchase path
     *  created the stake. Pass exactly one of $stakingSwapOrdersId (the
     *  USDT-purchase path) / $userStakesId (any other path). */
    public function createRoiRecord($orderRef, $userId, $planType, array $data, $stakingSwapOrdersId = null, $userStakesId = null)
    {
        if (empty($stakingSwapOrdersId) && empty($userStakesId)) {
            log_message('error', '[StakingLifecycle] createRoiRecord called with neither staking_swap_orders_id nor user_stakes_id — cannot link.');
            return false;
        }
        $id = $this->roiMgmt->createROIRecord($stakingSwapOrdersId ?: null, $userId, $orderRef, $planType, $data);
        if ($id && $userStakesId) {
            $this->db->where('id', $id)->update('roi_staking_management', ['user_stakes_id' => (int)$userStakesId]);
        }
        return $id;
    }

    /** Admin-configurable destination wallet for matured principal (default
     *  'exchange'). Constrained to the three principal-eligible wallet types —
     *  'usdt'/'bonus' are never sensible release targets for locked BMAN. */
    public function maturityReleaseWallet()
    {
        return site_settings('staking_lifecycle_settings', 'maturity_release_wallet') ?: 'exchange';
    }

    public function saveMaturityReleaseWallet($wallet)
    {
        if (!in_array($wallet, ['exchange', 'earning', 'staking'], true)) {
            return [false, 'Invalid release wallet.'];
        }
        $exists = $this->db->get_where('site_settings', [
            'settings_type' => 'staking_lifecycle_settings',
            'settings_name' => 'maturity_release_wallet',
        ])->row_array();
        if ($exists) {
            $this->db->where('id', $exists['id'])->update('site_settings', ['settings_value' => $wallet]);
        } else {
            $this->db->insert('site_settings', [
                'settings_type'  => 'staking_lifecycle_settings',
                'settings_name'  => 'maturity_release_wallet',
                'settings_value' => $wallet,
            ]);
        }
        return [true, 'Saved.'];
    }

    /** Release matured principal for a stake whose principal_release_mode is
     *  'credited_at_maturity' — reads the admin-configurable destination
     *  wallet instead of a hardcoded one, sets user_stakes.status='matured'.
     *  Called once per record by RoiMaturityPayment_cron, which finalizes
     *  overall_status='completed' in the same pass — not idempotency-guarded
     *  here, since the caller already gates on principal_release_mode and
     *  advances overall_status so it can never re-select the same record. */
    public function releaseMaturedPrincipal($userId, $stakeId, $amount, $referenceId)
    {
        if ($amount <= 0) return [true, 'nothing to release'];
        $wallet = $this->maturityReleaseWallet();
        list($ok, $res) = $this->L->credit((int)$userId, $wallet, $amount, 'stake_maturity', [
            'reference_id'  => $referenceId,
            'description'   => 'Principal released at maturity — ' . number_format($amount, 4) . ' BMAN',
            'skip_maturity' => true,
        ]);
        if ($ok && $stakeId) {
            $this->db->where('id', (int)$stakeId)->update('user_stakes', ['status' => 'matured']);
        }
        return [$ok, $res];
    }
}
