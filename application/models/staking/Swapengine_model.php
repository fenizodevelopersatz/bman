<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Swapengine_model — on-chain USDT <-> BMAN swap (business model "A").
 * ---------------------------------------------------------------------------
 * A package purchase becomes a real two-leg on-chain swap:
 *   Leg 1  USDT  user deposit address --> Admin (treasury) wallet
 *   Leg 2  BMAN  Admin (treasury) wallet --> user deposit address
 *   Leg 3  BMAN  optional 25% bonus, Admin --> user
 *
 * SAFETY: gated by token_settings.swap_enabled. While swap_dry_run = 1 the
 * engine records exactly what it WOULD broadcast (tx hashes 'DRYRUN-…') and
 * sends NOTHING on-chain — so the orchestration + amounts + addresses can be
 * verified without moving real funds. Leg 2/3 fire only after Leg 1 succeeds;
 * a partial failure is parked (status failed_*) for advance()/manual retry.
 *
 * On-chain primitives come from the Web3bman library (sendToken/getTokenBalance)
 * and the encrypted keys already stored (per-user deposit key + treasury key).
 */
class Swapengine_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Tokenmaster_model', 'tokens');
        $this->load->library('web3bman');
    }

    /* ------------------------------ config -------------------------------- */

    /** Active chain config + swap flags, or null. */
    public function config()
    {
        $c = $this->tokens->activeSettings();
        if (!$c) return null;
        return $c;
    }

    /** The user's on-chain deposit address + encrypted key. */
    private function userWallet($userId)
    {
        return $this->db->get_where('user_wallet', ['user_id' => (int)$userId])->row_array() ?: null;
    }

    /* ------------------------------- quote -------------------------------- */

    /**
     * Price a package for the swap: BMAN out, USDT in, 25% bonus, addresses,
     * and (best-effort) the user's real on-chain USDT balance.
     */
    public function quote($userId, $packageId)
    {
        $cfg = $this->config();
        if (!$cfg) return [false, 'Token settings not configured.'];
        $pkg = $this->db->get_where('staking_packages', ['id' => (int)$packageId, 'is_active' => 1])->row_array();
        if (!$pkg) return [false, 'Package not available.'];

        $bman = (float)$pkg['stake_amount'];
        $usdt = $this->tokens->convertBmanToUsdt($bman);
        if ($usdt === null) return [false, 'Exchange rate not configured.'];
        $bonus = round($bman * (float)$pkg['bonus_percent'] / 100, 4);

        $uw = $this->userWallet($userId);
        $onchainUsdt = null;
        if ($uw && !empty($cfg['usdt_contract'])) {
            try { $onchainUsdt = (float)$this->web3bman->getTokenBalance($uw['wallet_address'], $cfg['usdt_contract']); }
            catch (Exception $e) { $onchainUsdt = null; } // RPC may be unreachable in dev
        }

        return [true, [
            'package'       => $pkg['name'],
            'bman'          => $bman,
            'usdt'          => round((float)$usdt, 8),
            'bonus_bman'    => $bonus,
            'rate'          => (float)$cfg['exchange_rate'],
            'user_address'  => $uw['wallet_address'] ?? null,
            'admin_address' => $cfg['treasury_wallet'] ?? null,
            'onchain_usdt'  => $onchainUsdt,
            'swap_enabled'  => (int)($cfg['swap_enabled'] ?? 0),
            'dry_run'       => (int)($cfg['swap_dry_run'] ?? 1),
        ]];
    }

    /* ------------------------------ execute ------------------------------- */

    /**
     * Run the swap. Returns [true, order] or [false, error].
     * $opts: force_dry_run(bool), allow_disabled(bool, for CLI dry tests).
     */
    public function execute($userId, $packageId, $opts = [])
    {
        $cfg = $this->config();
        if (!$cfg) return [false, 'Token settings not configured.'];

        $enabled = (int)($cfg['swap_enabled'] ?? 0) === 1;
        if (!$enabled && empty($opts['allow_disabled']))
            return [false, 'On-chain swap is disabled. Enable it in Token Settings after a live test.'];

        $dryRun = (int)($cfg['swap_dry_run'] ?? 1) === 1 || !empty($opts['force_dry_run']);

        // account + package
        $user = $this->db->select('status')->get_where('users', ['id' => (int)$userId])->row_array();
        if (!$user || (string)$user['status'] !== '1') return [false, 'Account is not active.'];
        $pkg = $this->db->get_where('staking_packages', ['id' => (int)$packageId, 'is_active' => 1])->row_array();
        if (!$pkg) return [false, 'Package not available.'];

        // amounts + addresses + contracts
        $bman  = (float)$pkg['stake_amount'];
        $usdt  = $this->tokens->convertBmanToUsdt($bman);
        if ($usdt === null || $usdt <= 0) return [false, 'Exchange rate not configured.'];
        $usdt  = round((float)$usdt, 8);
        $bonus = round($bman * (float)$pkg['bonus_percent'] / 100, 4);

        $uw = $this->userWallet($userId);
        if (!$uw || empty($uw['wallet_address'])) return [false, 'User has no on-chain deposit address.'];
        $userAddr  = $uw['wallet_address'];
        $adminAddr = $cfg['treasury_wallet'] ?? '';
        $usdtC     = $cfg['usdt_contract'] ?? '';
        $bmanC     = $cfg['bman_contract'] ?? '';
        if (!$adminAddr || !$usdtC || !$bmanC)
            return [false, 'Admin wallet / token contracts are not configured in Token Settings.'];

        // Real on-chain USDT balance guard — enforced for LIVE swaps only.
        // In dry-run we simulate the full flow regardless of the real balance.
        $onchainUsdt = null;
        try { $onchainUsdt = (float)$this->web3bman->getTokenBalance($userAddr, $usdtC); }
        catch (Exception $e) { $onchainUsdt = null; }
        if (!$dryRun) {
            if ($onchainUsdt === null)
                return [false, 'Could not read on-chain USDT balance — aborting live swap.'];
            if ($onchainUsdt + 1e-8 < $usdt)
                return [false, 'Insufficient on-chain USDT. Have '.$onchainUsdt.', need '.$usdt.'.'];
        }

        // Optionally ALSO push the BMAN to the user's external address on-chain
        // (default OFF — the BMAN lives in the custodial Exchange/Bonus wallets).
        $deliverBmanOnchain = (int)($cfg['swap_bonus_onchain'] ?? 0) === 1;
        $ref = 'SWP-'.date('Ymd').'-'.strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

        // Internal USDT wallet must cover the purchase (mirrors the on-chain USDT).
        $this->load->model('Walletledger_model', 'L');
        if ((float)$this->L->balance($userId, 'usdt') + 1e-8 < $usdt)
            return [false, 'Insufficient USDT wallet balance. Need '.$usdt.' USDT.'];

        $this->db->insert('staking_swap_orders', [
            'ref' => $ref, 'user_id' => (int)$userId, 'package_id' => (int)$packageId,
            'user_address' => $userAddr, 'admin_address' => $adminAddr,
            'usdt_amount' => $usdt, 'bman_amount' => $bman, 'bonus_bman' => $bonus,
            'exchange_rate' => (float)$cfg['exchange_rate'], 'status' => 'pending_gas_fee',
            'attempts' => 1,
            // 4-step cron state machine (StakingPurchasecron). dry_run is global now
            // (token_settings.swap_dry_run), not a per-order column.
            'gas_cron_status' => 0, 'usdt_cron_status' => 0, 'bonus_cron_status' => 0,
            'bman_cron_status' => 0,
            'coin_distribution_option' => 1,
        ]);
        $orderId = (int)$this->db->insert_id();

        // NOTE: Gas fee funding is now handled by StakingPurchasecron
        // The cron will detect admin's BNB transfer and mark gas_cron_status = 1
        // This allows order creation without requiring immediate gas availability

        // ---- LEG 1: USDT transfer is now detected by cron via Etherscan
        //      No on-chain sending here - user sends USDT manually or cron waits for detection
        //      Status moved to 'pending_usdt' for cron to detect ----
        // Skip actual transaction - let cron detect it via Etherscan
        // This allows order creation without requiring user to have BNB for gas
        $usdtTx = null; // Will be populated by cron when it detects the transfer

        // ---- INTERNAL LEDGER: Record the swap intent only (USDT debit).
        //      BMAN + bonus are credited by StakingPurchasecron after on-chain
        //      detection + confirmation — never at purchase time. ----
        $this->db->trans_begin();

        // Debit USDT (marks user's intention to swap - cron will verify on-chain)
        list($okU) = $this->L->debit($userId, 'usdt', $usdt, 'swap', [
            'reference_id' => $ref, 'description' => 'Swap: USDT pending transfer to admin '.$adminAddr.' ['.$ref.']']);

        // IMPORTANT: BMAN is intentionally NOT credited here.
        // StakingPurchasecron is the SOLE authority that credits wallets
        // (exchange/earning/staking/bonus per coin_distribution_option) and ONLY
        // after it detects + confirms the real on-chain BMAN transfer (admin → user)
        // with its tx_hash. Crediting here would (a) hand out BMAN with no on-chain
        // proof, (b) ignore the distribution option, and (c) double-credit vs the cron.

        if (!$okU || $this->db->trans_status() === false) {
            $this->db->trans_rollback();
            $this->_set($orderId, ['status' => 'failed_credit', 'error' => 'ledger error']);
            return [false, 'Failed to record swap in ledger - order parked '.$ref.'.'];
        }
        $this->db->trans_commit();

        // Order stays in 'pending_gas_fee' — StakingPurchasecron drives the state
        // machine: gas → usdt → bonus → bman (+ internal wallet split).
        // dry_run is surfaced from the global flag (no per-order column anymore).
        return [true, array_merge($this->order($orderId), ['dry_run' => $dryRun ? 1 : 0])];
    }

    /**
     * Resume a parked order (failed_gas / failed_usdt / failed_credit). Idempotent:
     * re-sends USDT only if it never settled (usdt_tx_hash empty) and credits the
     * wallets only if not already credited for this ref. Returns [ok, message].
     */
    public function resume($orderId)
    {
        $o = $this->order($orderId);
        if (!$o) return [false, 'Order not found.'];
        if ($o['status'] === 'completed') return [false, 'Order is already completed.'];

        $cfg = $this->config();
        if (!$cfg) return [false, 'Token settings not configured.'];
        $dryRun    = (int)($cfg['swap_dry_run'] ?? 1) === 1;
        $uid       = (int)$o['user_id'];
        $usdt      = (float)$o['usdt_amount'];
        $bman      = (float)$o['bman_amount'];
        $bonus     = (float)$o['bonus_bman'];
        $userAddr  = $o['user_address'];
        $adminAddr = $o['admin_address'];
        $ref       = $o['ref'];
        $usdtC     = $cfg['usdt_contract'] ?? '';

        $this->load->model('Walletledger_model', 'L');
        $this->_set($orderId, ['attempts' => (int)$o['attempts'] + 1, 'error' => null]);

        // (1) settle USDT on-chain if it never did
        if (empty($o['usdt_tx_hash'])) {
            $uw = $this->userWallet($uid);
            if (!$uw) return [false, 'User deposit wallet missing.'];
            list($gasOk, $gasMsg, $gasTx) = $this->_ensureGas($userAddr, $dryRun, $cfg);
            if ($gasTx) $this->_set($orderId, ['gas_tx_hash' => $gasTx]);
            if (!$gasOk) { $this->_set($orderId, ['status' => 'failed_gas', 'error' => substr($gasMsg,0,255)]); return [false, 'Gas: '.$gasMsg]; }
            try {
                $usdtTx = $dryRun ? 'DRYRUN-usdt-'.$ref
                    : $this->web3bman->sendToken($this->web3bman->decryptKey($uw['private_key']), $adminAddr, (string)$usdt, $usdtC)['tx_hash'];
                $this->_set($orderId, ['usdt_tx_hash' => $usdtTx, 'status' => 'usdt_sent']);
            } catch (Exception $e) {
                $this->_set($orderId, ['status' => 'failed_usdt', 'error' => substr($e->getMessage(),0,255)]);
                return [false, 'USDT settlement failed: '.$e->getMessage()];
            }
        }

        // (2) credit the wallets once (idempotent guard on ref)
        $already = $this->db->where(['reference_id' => $ref, 'reference_type' => 'swap', 'user_id' => $uid])
                            ->count_all_results('wallet_ledger');
        if (!$already) {
            $this->db->trans_begin();
            $this->L->debit($uid, 'usdt', $usdt, 'swap', ['reference_id'=>$ref,'description'=>'Swap USDT ['.$ref.'] (resume)']);
            $this->L->credit($uid, 'exchange', $bman, 'swap', ['reference_id'=>$ref,'description'=>'Swap '.$bman.' BMAN → Exchange ['.$ref.'] (resume)']);
            if ($bonus > 0) $this->L->credit($uid, 'bonus', $bonus, 'swap_bonus', ['reference_id'=>$ref,'description'=>'Swap 25% bonus ['.$ref.'] (resume)']);
            if ($this->db->trans_status() === false) {
                $this->db->trans_rollback();
                $this->_set($orderId, ['status' => 'failed_credit', 'error' => 'ledger error on resume']);
                return [false, 'Crediting failed on resume.'];
            }
            $this->db->trans_commit();
        }

        $this->_set($orderId, ['status' => 'completed']);
        return [true, 'Order '.$ref.' completed.'];
    }

    /**
     * Deliver the order's BMAN (+25% bonus) on-chain to the user's address,
     * signed by the treasury key. Idempotent: skips if already delivered.
     * Returns [ok, message]. Treasury must hold BMAN + BNB gas.
     */
    public function deliverBman($orderId)
    {
        $o = $this->order($orderId);
        if (!$o) return [false, 'Order not found.'];
        if (!empty($o['bman_tx_hash'])) return [false, 'BMAN already delivered ('.$o['bman_tx_hash'].').'];
        if ((float)$o['bman_amount'] <= 0) return [false, 'Nothing to deliver.'];

        $cfg   = $this->config();
        $dry   = (int)($cfg['swap_dry_run'] ?? 1) === 1; // global flag (no per-order dry_run column)
        $bmanC = $cfg['bman_contract'] ?? '';
        if (!$bmanC) return [false, 'BMAN contract not configured.'];
        $userAddr = $o['user_address'];
        $bman  = (float)$o['bman_amount'];
        $bonus = (float)$o['bonus_bman'];

        try {
            if ($dry) {
                $bmanTx  = 'DRYRUN-bman-'.$o['ref'];
                $bonusTx = $bonus > 0 ? 'DRYRUN-bonus-'.$o['ref'] : null;
            } else {
                $tk = $this->tokens->treasuryPrivateKey();
                if (!$tk) return [false, 'Treasury key missing.'];
                $bmanTx  = $this->web3bman->sendToken($tk, $userAddr, (string)$bman, $bmanC)['tx_hash'];
                $bonusTx = $bonus > 0 ? $this->web3bman->sendToken($tk, $userAddr, (string)$bonus, $bmanC)['tx_hash'] : null;
            }
            $this->_set($orderId, ['bman_tx_hash' => $bmanTx, 'bonus_tx_hash' => $bonusTx, 'error' => null]);

            // Attach the on-chain delivery hash to the swap's on-chain history rows
            // (created by the Walletledger observer at purchase). Fail-safe.
            try {
                $this->load->model('Onchaintx_model', 'octx');
                $this->octx->attachSwapDelivery($o['ref'], $bmanTx, $o['deposit_tx_hash'] ?? ($o['tx_hash'] ?? null));
            } catch (Throwable $e) { log_message('error', '[swap onchain link] '.$e->getMessage()); }

            return [true, 'BMAN delivered'.($dry ? ' (dry-run)' : '').': '.$bmanTx];
        } catch (Exception $e) {
            $this->_set($orderId, ['error' => 'BMAN delivery failed: '.substr($e->getMessage(),0,200)]);
            return [false, 'BMAN delivery failed: '.$e->getMessage()];
        }
    }

    /** Deliver BMAN for all completed orders that still need it (cron). */
    public function deliverPending($limit = 50)
    {
        // dry_run is global now — in dry-run mode nothing is delivered on-chain.
        $cfg = $this->config();
        if ((int)($cfg['swap_dry_run'] ?? 1) === 1) return ['processed' => 0, 'delivered' => 0, 'failed' => 0];

        $rows = $this->db->where('status', 'completed')->where('bman_amount >', 0)
                         ->group_start()->where('bman_tx_hash', null)->or_where('bman_tx_hash', '')->group_end()
                         ->order_by('id','ASC')->limit((int)$limit)->get('staking_swap_orders')->result_array();
        $delivered = 0; $failed = 0;
        foreach ($rows as $o) { list($ok) = $this->deliverBman((int)$o['id']); $ok ? $delivered++ : $failed++; }
        return ['processed' => count($rows), 'delivered' => $delivered, 'failed' => $failed];
    }

    /**
     * Make sure $userAddr can pay gas for one BEP-20 transfer. If short, send
     * BNB from the treasury (gas) wallet and wait for it to land. Returns
     * [ok, message, gas_tx_hash|null]. In dry-run nothing is sent.
     */
    private function _ensureGas($userAddr, $dryRun, $cfg)
    {
        if ((int)($cfg['swap_auto_gas'] ?? 1) !== 1) return [true, 'auto-gas off', null];

        // gas needed for the USDT transfer (BNB) = gasLimit * gasPrice, +50% buffer
        $gasLimit = (float)($cfg['gas_limit']  ?: 210000);
        $gwei     = (float)($cfg['gas_price']  ?: 5);
        $needBnb  = $gasLimit * $gwei * 1e-9 * 1.5;

        if ($dryRun) return [true, 'dry-run: would ensure '.rtrim(rtrim(sprintf('%.8f',$needBnb),'0'),'.').' BNB', null];

        try { $cur = (float)$this->web3bman->getBnbBalance($userAddr); }
        catch (Exception $e) { return [false, 'cannot read BNB balance: '.$e->getMessage(), null]; }
        if ($cur >= $needBnb) return [true, 'sufficient gas ('.$cur.' BNB)', null];

        $treasuryKey = $this->tokens->treasuryPrivateKey();
        if (!$treasuryKey) return [false, 'treasury/gas key missing', null];

        // top up to ~2x the requirement so the address keeps a small reserve
        $topup = ($needBnb * 2) - $cur;
        try {
            $tx = $this->web3bman->sendBnb($treasuryKey, $userAddr, sprintf('%.8f', $topup));
            $gasTx = $tx['tx_hash'];
        } catch (Exception $e) {
            return [false, 'gas send failed: '.$e->getMessage(), null];
        }

        // wait for the BNB to be spendable (BSC ~3s blocks) — cap ~24s
        for ($i = 0; $i < 8; $i++) {
            sleep(3);
            try { if ((float)$this->web3bman->getBnbBalance($userAddr) >= $needBnb) return [true, 'topped up', $gasTx]; }
            catch (Exception $e) { /* keep polling */ }
        }
        return [false, 'gas top-up broadcast ('.$gasTx.') but not confirmed in time — retry shortly', $gasTx];
    }

    private function _set($id, $data) { $this->db->where('id', (int)$id)->update('staking_swap_orders', $data); }

    /* ------------------------------- reads -------------------------------- */

    public function order($id)  { return $this->db->get_where('staking_swap_orders', ['id' => (int)$id])->row_array(); }
    public function orders($limit = 200)
    {
        return $this->db->order_by('id','DESC')->limit((int)$limit)->get('staking_swap_orders')->result_array();
    }
}
