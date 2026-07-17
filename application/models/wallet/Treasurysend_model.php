<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Treasurysend_model — admin sends BMAN straight from the Treasury wallet to
 * a chosen user's on-chain address. This is NEW money (manual reward /
 * airdrop / adjustment) — unlike Wallettransferservice_model, nothing is
 * debited from any internal ledger wallet; the recipient's platform
 * dashboard balances are intentionally untouched, only their real on-chain
 * balance changes. Own audit trail (treasury_direct_send), gated by
 * treasury_direct_send_settings (enabled=0, dry_run=1 by default — same
 * safety convention as every other on-chain feature in this codebase).
 */
class Treasurysend_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Tokenmaster_model', 'tokens');
        $this->load->model('Onchaintx_model', 'octx');
        $this->load->library('web3bman');
    }

    public function settings()
    {
        $row = $this->db->get_where('treasury_direct_send_settings', ['id' => 1])->row_array();
        return $row ?: ['enabled' => 0, 'dry_run' => 1, 'min_treasury_reserve' => '0'];
    }

    public function updateSettings(array $data, $adminId = null)
    {
        $allowed = array_intersect_key($data, array_flip(['enabled', 'dry_run', 'min_treasury_reserve']));
        if (empty($allowed)) return [false, 'No valid fields to update.'];
        $allowed['updated_by'] = $adminId;
        $this->db->where('id', 1)->update('treasury_direct_send_settings', $allowed);
        return [true, 'Settings updated.'];
    }

    public function history($limit = 100, $offset = 0)
    {
        return $this->db->select('t.*, u.username, u.referral_id, u.email')
            ->from('treasury_direct_send t')
            ->join('users u', 'u.id = t.user_id', 'left')
            ->order_by('t.id', 'DESC')->limit($limit, $offset)->get()->result_array();
    }

    /**
     * Send $amount BMAN to $userId's on-chain address, synchronously (this is
     * a deliberate one-off admin action, not a background sweep).
     * @return array ['ok'=>bool,'ref'=>str|null,'status'=>str,'tx_hash'=>str|null,'message'=>str]
     */
    public function send($userId, $amount, $reason, $adminId)
    {
        $userId = (int)$userId;
        if (!$userId) return ['ok' => false, 'message' => 'Select a recipient.'];
        if (!is_numeric($amount) || bccomp((string)$amount, '0', 8) <= 0)
            return ['ok' => false, 'message' => 'Amount must be greater than zero.'];

        $u = $this->db->get_where('users', ['id' => $userId])->row_array();
        if (!$u) return ['ok' => false, 'message' => 'User not found.'];
        if ((string)$u['status'] !== '1') return ['ok' => false, 'message' => 'User account is inactive or blocked.'];

        $wallet = $this->db->select('wallet_address')->where('user_id', $userId)
            ->order_by('id', 'ASC')->limit(1)->get('user_wallet')->row_array();
        if (empty($wallet['wallet_address']))
            return ['ok' => false, 'message' => 'This user has no on-chain wallet address on file.'];

        $settings = $this->settings();
        if (!(int)$settings['enabled'])
            return ['ok' => false, 'message' => 'Treasury Direct Send is disabled — enable it in settings first.'];
        $dry = (int)$settings['dry_run'] === 1;

        $ref = 'TDS-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        $reasonTrim = $reason !== null ? substr((string)$reason, 0, 255) : null;
        $this->db->insert('treasury_direct_send', [
            'ref' => $ref, 'admin_id' => (int)$adminId, 'user_id' => $userId,
            'to_address' => $wallet['wallet_address'], 'amount' => (string)$amount,
            'reason' => $reasonTrim, 'status' => 'pending', 'dry_run' => $dry ? 1 : 0,
        ]);
        $id = (int)$this->db->insert_id();

        try {
            if ($dry) {
                $tx = 'DRYRUN-' . $ref; $from = null; $to = $wallet['wallet_address'];
            } else {
                $cfg = $this->tokens->activeSettings();
                if (!$cfg || empty($cfg['treasury_wallet'])) throw new RuntimeException('Treasury wallet not configured.');
                $balance = $this->web3bman->getTokenBalance($cfg['treasury_wallet']);
                $available = bcsub($balance, (string)$settings['min_treasury_reserve'], 8);
                if (bccomp($available, (string)$amount, 8) < 0) throw new RuntimeException('Would breach minimum treasury reserve.');
                $key = $this->tokens->treasuryPrivateKey();
                if (!$key) throw new RuntimeException('Treasury key missing or failed to decrypt.');
                $sent = $this->web3bman->sendToken($key, $wallet['wallet_address'], (string)$amount);
                $tx = $sent['tx_hash']; $from = $sent['from']; $to = $sent['to'];
            }

            $network = $this->tokens->activeSettings()['network'] ?? 'BSC';
            $this->db->where('id', $id)->update('treasury_direct_send', [
                'status' => 'completed', 'tx_hash' => $tx, 'network' => $network,
                'completed_at' => date('Y-m-d H:i:s'),
            ]);

            // No wallet_ledger row exists for this movement (it's off-ledger new
            // money), so capture() rather than updateByLedgerId().
            $this->octx->capture([
                'tx_hash' => $tx, 'network' => $network, 'wallet_type' => 'treasury',
                'tx_type' => 'treasury_direct_send', 'status' => 'confirmed',
                'user_id' => $userId, 'admin_id' => (int)$adminId, 'token_symbol' => 'BMAN',
                'amount' => (string)$amount, 'from_address' => $from, 'to_address' => $to,
                'reference_type' => 'treasury_direct_send', 'reference_id' => $ref,
                'created_by' => (int)$adminId,
            ]);

            return ['ok' => true, 'ref' => $ref, 'status' => 'completed', 'tx_hash' => $tx,
                     'message' => 'Sent' . ($dry ? ' (dry-run — no real BMAN moved).' : '.')];
        } catch (Throwable $e) {
            $this->db->where('id', $id)->update('treasury_direct_send', [
                'status' => 'failed', 'error_message' => substr($e->getMessage(), 0, 255),
            ]);
            return ['ok' => false, 'ref' => $ref, 'status' => 'failed', 'message' => $e->getMessage()];
        }
    }
}
