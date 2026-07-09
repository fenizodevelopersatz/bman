<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * RoiMaturity_model — Fully on-chain ROI payout engine.
 *
 * Detects mature ROI payouts (credit_date <= TODAY) and broadcasts them as
 * blockchain transfers from Treasury wallet to user's Earning wallet address.
 *
 * Flow:
 *   1. Find pending-broadcast mature ROIs
 *   2. Verify user has an earning wallet address configured
 *   3. Sign transfer with Treasury private key (stored encrypted in token_settings)
 *   4. Broadcast to blockchain via Web3bman::sendToken()
 *   5. Store tx_hash in staking_roi_payouts
 *   6. Mark transfer_status as 'pending_confirmation'
 *   7. ChainSync cron later confirms and updates status to 'confirmed'
 *   8. After confirmation, update internal wallet ledger (idempotent via tx_hash)
 *
 * Idempotency:
 *   - ROI payouts are unique by id, so re-running cron on same ROI is safe
 *   - tx_hash is unique in DB, so duplicate broadcasts are rejected
 *   - Ledger credits use tx_hash as unique key, so confirmations are idempotent
 *
 * Error handling:
 *   - If broadcast fails, error_message is logged and ROI stays pending_broadcast
 *   - Cron can retry next run
 *   - Failed transfers marked 'failed' after max retry attempts
 */
class RoiMaturity_model extends CI_Model
{
    const MAX_RETRY_ATTEMPTS = 5;
    const RETRY_DELAY_MINUTES = 30;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Walletledger_model', 'ledger');
        $this->load->model('Tokenmaster_model', 'tokens');
        $this->load->model('Chainsync_model', 'chainsync');
    }

    /**
     * Run the maturity check and process eligible ROIs.
     * Called by RoiMaturityCron::run()
     *
     * @return array ['ok'=>bool, 'message'=>string, 'processed'=>int, 'failed'=>int]
     */
    public function processMaturityCycle()
    {
        $result = ['ok' => true, 'message' => 'success', 'processed' => 0, 'failed' => 0, 'errors' => []];

        try {
            $matureRois = $this->getMaturePendingRois();
            if (empty($matureRois)) {
                $result['message'] = 'No mature ROIs to process';
                return $result;
            }

            log_message('info', '[ROI] Found '.count($matureRois).' mature ROIs to process');

            foreach ($matureRois as $roi) {
                try {
                    $this->processSingleRoi($roi);
                    $result['processed']++;
                } catch (Exception $e) {
                    $result['failed']++;
                    $result['errors'][] = "ROI ID {$roi['id']}: {$e->getMessage()}";
                    log_message('error', '[ROI] Failed to process ROI '.$roi['id'].': '.$e->getMessage());
                }
            }

            $result['message'] = "Processed {$result['processed']} ROIs, {$result['failed']} failed";
        } catch (Exception $e) {
            $result['ok'] = false;
            $result['message'] = $e->getMessage();
            log_message('error', '[ROI] Critical error in maturity cycle: '.$e->getMessage());
        }

        return $result;
    }

    /**
     * Get all pending-broadcast ROIs with credit_date <= TODAY.
     *
     * @return array ROI payout records
     */
    public function getMaturePendingRois()
    {
        return $this->db
            ->select('srp.*, us.user_id, us.stake_amount, sp.name as package_name')
            ->from('staking_roi_payouts srp')
            ->join('user_stakes us', 'srp.stake_id = us.id', 'left')
            ->join('staking_packages sp', 'us.package_id = sp.id', 'left')
            ->where('srp.credit_date <=', date('Y-m-d'))
            ->where('srp.status', 'pending')
            ->where('(srp.transfer_status IS NULL OR srp.transfer_status = "pending_broadcast")', null, false)
            ->get()
            ->result_array();
    }

    /**
     * Process a single ROI: sign transfer, broadcast, store hash.
     * Throws exception on failure.
     *
     * @param array $roi ROI payout record
     * @return array ['tx_hash'=>..., 'from'=>..., 'to'=>...]
     */
    private function processSingleRoi(array $roi)
    {
        $roiId = $roi['id'];
        $userId = $roi['user_id'];
        $amount = $roi['amount'];

        // 1. Get user's earning wallet address
        $userEarningWallet = $this->getUserEarningWallet($userId);
        if (!$userEarningWallet) {
            throw new RuntimeException("User {$userId} has no earning wallet configured");
        }

        // 2. Get token settings (RPC, chain, treasury wallet)
        $cfg = $this->tokens->activeSettings();
        if (!$cfg) {
            throw new RuntimeException('No active Token Settings configured');
        }

        // 3. Get Treasury private key (stored encrypted in token_settings)
        $treasuryPk = $this->getTreasuryPrivateKey($cfg);
        if (!$treasuryPk) {
            throw new RuntimeException('Treasury private key not configured or decryption failed');
        }

        // 4. Sign and broadcast transfer via Web3bman
        $this->load->library('web3bman');

        try {
            $txResult = $this->web3bman->sendToken(
                $treasuryPk,
                $userEarningWallet,
                (string)$amount,
                $cfg['bman_contract'] ?? null
            );
            $txHash = $txResult['tx_hash'];
        } catch (Exception $e) {
            // Mark as failed and log
            $this->db->update('staking_roi_payouts', [
                'error_message'    => substr($e->getMessage(), 0, 500),
                'transfer_status'  => 'failed',
            ], ['id' => $roiId]);

            $this->logTransferAttempt($roiId, $userId, $amount, null, 'failed', $e->getMessage());
            throw new RuntimeException("Failed to broadcast ROI transfer: {$e->getMessage()}");
        }

        // 5. Store tx_hash and mark as pending_confirmation
        $this->db->update('staking_roi_payouts', [
            'tx_hash'           => $txHash,
            'transfer_status'   => 'pending_confirmation',
            'transferred_at'    => date('Y-m-d H:i:s'),
            'network'           => 'bsc',
            'error_message'     => null,
        ], ['id' => $roiId]);

        // 6. Log the transfer attempt
        $this->logTransferAttempt(
            $roiId,
            $userId,
            $amount,
            $txHash,
            'pending_confirmation',
            null
        );

        log_message('info', "[ROI] Processed ROI {$roiId} for user {$userId}: tx={$txHash}");

        return $txResult;
    }

    /**
     * Retry failed transfers that are within the retry window.
     * Useful to call in a separate cron or as part of the main cycle.
     *
     * @return array ['retried'=>int, 'succeeded'=>int, 'failed'=>int]
     */
    public function retryFailedTransfers()
    {
        $result = ['retried' => 0, 'succeeded' => 0, 'failed' => 0];

        $failedRois = $this->db
            ->select('srp.*')
            ->from('staking_roi_payouts srp')
            ->where('srp.transfer_status', 'failed')
            ->where('srp.status', 'pending')
            ->get()
            ->result_array();

        if (empty($failedRois)) {
            return $result;
        }

        log_message('info', '[ROI] Retrying '.count($failedRois).' failed transfers');

        foreach ($failedRois as $roi) {
            try {
                // Reset transfer_status to pending_broadcast to allow retry
                $this->db->update('staking_roi_payouts', [
                    'transfer_status' => 'pending_broadcast',
                ], ['id' => $roi['id']]);

                $this->processSingleRoi($roi);
                $result['succeeded']++;
            } catch (Exception $e) {
                $result['failed']++;
                log_message('warn', '[ROI] Retry failed for ROI '.$roi['id'].': '.$e->getMessage());
            }
            $result['retried']++;
        }

        return $result;
    }

    /**
     * Get user's earning wallet address from the database.
     * Assumes a users table or wallet mapping table.
     *
     * @param int $userId
     * @return string|null Earning wallet address (0x...)
     */
    private function getUserEarningWallet($userId)
    {
        // Check if user has a custodial wallet configured
        $wallet = $this->db
            ->select('earning_wallet_address, wallet_address')
            ->from('users')
            ->where('id', $userId)
            ->get()
            ->row_array();

        if ($wallet && !empty($wallet['earning_wallet_address'])) {
            return $wallet['earning_wallet_address'];
        }

        // Fallback: check custodial_wallets table
        $custodial = $this->db
            ->select('address')
            ->from('custodial_wallets')
            ->where('user_id', $userId)
            ->where('wallet_type', 'earning')
            ->get()
            ->row_array();

        return $custodial['address'] ?? null;
    }

    /**
     * Decrypt Treasury private key from token_settings.
     * Assumes token_settings has treasury_pk_encrypted column (AES encrypted).
     *
     * @param array $cfg Active token settings row
     * @return string|null Decrypted private key (0x...)
     */
    private function getTreasuryPrivateKey(array $cfg)
    {
        if (empty($cfg['treasury_pk_encrypted'])) {
            return null;
        }

        try {
            // Decrypt using CodeIgniter's encryption library
            $this->load->library('encryption');
            $decrypted = $this->encryption->decrypt($cfg['treasury_pk_encrypted']);
            return $decrypted ?: null;
        } catch (Exception $e) {
            log_message('error', '[ROI] Failed to decrypt treasury key: '.$e->getMessage());
            return null;
        }
    }

    /**
     * Log a transfer attempt to audit table.
     *
     * @param int $roiPayoutId staking_roi_payouts.id
     * @param int $userId
     * @param string $amount
     * @param string|null $txHash
     * @param string $status
     * @param string|null $errorMessage
     */
    private function logTransferAttempt($roiPayoutId, $userId, $amount, $txHash, $status, $errorMessage = null)
    {
        $cfg = $this->tokens->activeSettings();
        $fromAddress = $cfg['treasury_wallet_address'] ?? 'unknown';
        $toAddress = $this->getUserEarningWallet($userId) ?? 'unknown';

        $logData = [
            'roi_payout_id'    => $roiPayoutId,
            'user_id'          => $userId,
            'amount'           => $amount,
            'from_address'     => $fromAddress,
            'to_address'       => $toAddress,
            'tx_hash'          => $txHash,
            'transfer_status'  => $status,
            'error_message'    => $errorMessage ? substr($errorMessage, 0, 500) : null,
        ];

        $this->db->insert('staking_roi_transfer_log', $logData);
    }

    /**
     * Confirm pending ROIs once their blockchain tx is confirmed.
     * Called by ChainSync cron after tx confirmation.
     *
     * @param string $txHash Confirmed transaction hash
     * @return bool
     */
    public function confirmRoiTransfer($txHash)
    {
        $roi = $this->db
            ->select('*')
            ->from('staking_roi_payouts')
            ->where('tx_hash', $txHash)
            ->get()
            ->row_array();

        if (!$roi) {
            log_message('warn', "[ROI] No ROI found for tx_hash {$txHash}");
            return false;
        }

        // Mark as confirmed
        $this->db->update('staking_roi_payouts', [
            'transfer_status'   => 'confirmed',
            'confirmed_at'      => date('Y-m-d H:i:s'),
            'status'            => 'paid',  // Mark as paid
        ], ['id' => $roi['id']]);

        // Credit the wallet ledger (idempotent via tx_hash)
        $this->ledger->credit(
            $roi['user_id'],
            'earning',
            (float)$roi['amount'],
            'roi_payout',
            $txHash,  // unique ref
            "ROI payout for stake {$roi['stake_id']}"
        );

        log_message('info', "[ROI] Confirmed ROI {$roi['id']}: tx={$txHash}");

        return true;
    }

    /**
     * Admin action: mark a failed transfer as reverted (don't retry).
     *
     * @param int $roiId
     * @param string $reason
     */
    public function markAsReverted($roiId, $reason = '')
    {
        $this->db->update('staking_roi_payouts', [
            'transfer_status'   => 'reverted',
            'error_message'     => substr($reason, 0, 500),
        ], ['id' => $roiId]);

        log_message('info', "[ROI] Marked ROI {$roiId} as reverted: {$reason}");
    }

    /**
     * Get ROI transfer statistics for admin dashboard.
     *
     * @return array [pending_broadcast, pending_confirmation, confirmed, failed]
     */
    public function getTransferStats()
    {
        $stats = [
            'pending_broadcast'      => 0,
            'pending_confirmation'   => 0,
            'confirmed'              => 0,
            'failed'                 => 0,
            'total_pending_amount'   => 0,
        ];

        $rows = $this->db
            ->select('transfer_status, COUNT(*) as cnt, SUM(amount) as total_amt')
            ->from('staking_roi_payouts')
            ->where('status', 'pending')
            ->group_by('transfer_status')
            ->get()
            ->result_array();

        foreach ($rows as $row) {
            $status = $row['transfer_status'] ?? 'pending_broadcast';
            if (isset($stats[$status])) {
                $stats[$status] = (int)$row['cnt'];
            }
        }

        $stats['total_pending_amount'] = (float)$this->db
            ->select_sum('amount')
            ->from('staking_roi_payouts')
            ->where('status', 'pending')
            ->get()
            ->row_array()['amount'] ?? 0;

        return $stats;
    }

    /**
     * Sync ROI confirmations from ChainSync on-chain transactions.
     * Called after ChainSync cron to confirm any ROI transfers that are now on-chain.
     *
     * @return array ['confirmed'=>int, 'failed'=>int]
     */
    public function syncConfirmationsFromChain()
    {
        $result = ['confirmed' => 0, 'failed' => 0];

        // Find ROI payouts pending confirmation with a tx_hash
        $pending = $this->db
            ->select('srp.*, octs.status as chain_status, octs.block_number, octs.confirmation_count')
            ->from('staking_roi_payouts srp')
            ->join('onchain_transactions octs', 'srp.tx_hash = octs.tx_hash', 'left')
            ->where('srp.transfer_status', 'pending_confirmation')
            ->where('srp.tx_hash IS NOT NULL', null, false)
            ->get()
            ->result_array();

        if (empty($pending)) {
            return $result;
        }

        log_message('info', '[ROI] Syncing '.count($pending).' pending ROI confirmations from chain');

        foreach ($pending as $roi) {
            try {
                // If on-chain status is confirmed, mark the ROI as confirmed
                if ($roi['chain_status'] === 'confirmed' || $roi['chain_status'] === 'success') {
                    $this->confirmRoiTransfer($roi['tx_hash']);
                    $result['confirmed']++;
                }
            } catch (Exception $e) {
                $result['failed']++;
                log_message('error', '[ROI] Failed to sync confirmation for ROI '.$roi['id'].': '.$e->getMessage());
            }
        }

        return $result;
    }
}
