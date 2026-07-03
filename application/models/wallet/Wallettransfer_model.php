<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Wallettransfer_model — internal wallet-to-wallet transfer (doc 9).
 * -------------------------------------------------------------------
 * A user moves balance between their OWN four internal wallets
 * (exchange · earning · staking · bonus). USDT is NEVER a from/to wallet — it
 * is a blockchain asset and must not move on the internal ledger.
 *
 * The actual money movement is done by Walletledger_model::transfer(), which
 * runs inside one DB transaction with SELECT … FOR UPDATE (atomic, row-locked,
 * balance_after recorded). This model validates the request, records the
 * `wallet_internal_transfer` header (linking the two wallet_ledger rows) and
 * enforces the allowed from→to pairs.
 */
class Wallettransfer_model extends CI_Model
{
    /** Wallets eligible for internal transfer. USDT is intentionally absent. */
    private $allowed = ['exchange', 'earning', 'staking', 'bonus'];

    /** Permitted from => to pairs (doc §2B). */
    private $pairs = [
        'exchange' => ['earning', 'staking', 'bonus'],
        'earning'  => ['exchange', 'bonus'],
        'bonus'    => ['exchange', 'staking'],
        'staking'  => ['exchange', 'bonus'],
    ];

    public function pairs() { return $this->pairs; }
    public function allowed() { return $this->allowed; }

    /* ================= MEMBER → MEMBER TRANSFER (primary) ================ *
     * Send an internal balance to ANOTHER user's account. Sender's wallet is
     * debited; the recipient's SAME wallet is credited (net of fee). Staking is
     * excluded (it is locked in a stake). Money moves via Walletledger. */

    /** Wallets a user may send to another member. */
    private $sendable = ['exchange', 'earning', 'bonus'];
    /** Flat transfer fee percent (0 = none). */
    private $fee_percent = 0;

    public function sendableWallets() { return $this->sendable; }

    /** Resolve a recipient by referral_id / username / email (active only). */
    public function resolveRecipient($identifier)
    {
        $identifier = trim((string)$identifier);
        if ($identifier === '') return null;
        return $this->db->group_start()
                        ->where('referral_id', $identifier)
                        ->or_where('username', $identifier)
                        ->or_where('email', $identifier)
                        ->group_end()
                        ->where('status', '1')
                        ->get('users')->row_array() ?: null;
    }

    /**
     * Member → member transfer. Returns [true, $ref] or [false, $error].
     */
    public function sendToUser($fromUid, $recipientIdentifier, $wallet, $amount, $note = null, $ip = null, $ua = null)
    {
        $fromUid = (int)$fromUid;
        $amount  = (string)$amount;

        if (!in_array($wallet, $this->sendable, true))
            return [false, 'This wallet cannot be transferred to another member.'];
        if (!is_numeric($amount) || bccomp($amount, '0', 8) <= 0)
            return [false, 'Amount must be greater than zero.'];

        $sender = $this->db->select('status')->get_where('users', ['id' => $fromUid])->row_array();
        if (!$sender || $sender['status'] != '1') return [false, 'Your account is inactive.'];

        $to = $this->resolveRecipient($recipientIdentifier);
        if (!$to) return [false, 'Recipient not found. Check the referral ID / username / email.'];
        $toUid = (int)$to['id'];
        if ($toUid === $fromUid) return [false, 'You cannot transfer to your own account.'];

        $this->load->model('Walletledger_model', 'L');
        if (bccomp($this->L->balance($fromUid, $wallet), $amount, 8) < 0)
            return [false, 'Insufficient balance in your '.ucfirst($wallet).' Wallet.'];

        $fee = bcdiv(bcmul($amount, (string)$this->fee_percent, 8), '100', 8);
        $net = bcsub($amount, $fee, 8);
        if (bccomp($net, '0', 8) <= 0) return [false, 'Amount too small after fee.'];

        $ref = $this->generateReference();
        $this->db->trans_begin();
        // debit sender
        list($okD, $rD) = $this->L->debit($fromUid, $wallet, $amount, 'wallet_transfer',
            ['reference_id' => $ref, 'description' => 'Sent to user #'.$toUid.' ('.$wallet.')']);
        if (!$okD) { $this->db->trans_rollback(); return [false, $rD]; }
        // credit recipient (same wallet), net of fee
        list($okC, $rC) = $this->L->credit($toUid, $wallet, $net, 'wallet_transfer',
            ['reference_id' => $ref, 'description' => 'Received from user #'.$fromUid.' ('.$wallet.')']);
        if (!$okC) { $this->db->trans_rollback(); return [false, $rC]; }

        $this->db->insert('wallet_internal_transfer', [
            'ref' => $ref, 'user_id' => $fromUid, 'to_user_id' => $toUid,
            'from_wallet' => $wallet, 'to_wallet' => $wallet,
            'amount' => $amount, 'fee' => $fee, 'net_amount' => $net, 'status' => 'completed',
            'description' => $note ? substr($note, 0, 255) : null,
            'debit_ledger_id' => (int)$rD, 'credit_ledger_id' => (int)$rC,
            'ip_address' => $ip, 'user_agent' => $ua ? substr((string)$ua, 0, 255) : null,
        ]);
        if ($this->db->trans_status() === false) { $this->db->trans_rollback(); return [false, 'Database error.']; }
        $this->db->trans_commit();
        return [true, $ref];
    }

    /* ------------------------------ validate --------------------------- */

    public function validate($userId, $from, $to, $amount)
    {
        if (!in_array($from, $this->allowed, true)) return [false, 'Invalid source wallet.'];
        if (!in_array($to, $this->allowed, true))   return [false, 'Invalid destination wallet.'];
        if ($from === $to)                          return [false, 'Source and destination wallet must be different.'];
        if (!in_array($to, $this->pairs[$from] ?? [], true))
            return [false, 'Transfer from '.ucfirst($from).' to '.ucfirst($to).' is not allowed.'];
        if (!is_numeric($amount) || bccomp((string)$amount, '0', 8) <= 0)
            return [false, 'Amount must be greater than zero.'];

        $user = $this->db->select('status')->get_where('users', ['id' => (int)$userId])->row_array();
        if (!$user || $user['status'] != '1') return [false, 'Account is inactive.'];

        $this->load->model('Walletledger_model', 'L');
        if (bccomp($this->L->balance($userId, $from), (string)$amount, 8) < 0)
            return [false, 'Insufficient balance in '.ucfirst($from).' Wallet.'];

        return [true, ''];
    }

    /* ------------------------------ execute ---------------------------- */

    /** Returns [true, $ref] on success, [false, $error] on failure. */
    public function execute(array $p)
    {
        $userId  = (int)$p['user_id'];
        $from    = $p['from_wallet'];
        $to      = $p['to_wallet'];
        $amount  = (string)$p['amount'];
        $remarks = isset($p['remarks']) ? trim((string)$p['remarks']) : null;

        list($ok, $err) = $this->validate($userId, $from, $to, $amount);
        if (!$ok) return [false, $err];

        $this->load->model('Walletledger_model', 'L');
        $ref = $this->generateReference();

        // Atomic money move via the ledger (debit from, credit to). transfer()
        // wraps both ledger posts in one transaction; on any failure it rolls
        // back and nothing is recorded here.
        $this->db->trans_begin();
        list($okD, $rD) = $this->L->debit($userId, $from, $amount, 'wallet_transfer',
            ['reference_id' => $ref, 'description' => 'Internal transfer '.$from.' → '.$to]);
        if (!$okD) { $this->db->trans_rollback(); return [false, $rD]; }
        list($okC, $rC) = $this->L->credit($userId, $to, $amount, 'wallet_transfer',
            ['reference_id' => $ref, 'description' => 'Internal transfer '.$from.' → '.$to]);
        if (!$okC) { $this->db->trans_rollback(); return [false, $rC]; }

        $this->db->insert('wallet_internal_transfer', [
            'ref' => $ref, 'user_id' => $userId, 'from_wallet' => $from, 'to_wallet' => $to,
            'amount' => $amount, 'fee' => 0, 'net_amount' => $amount, 'status' => 'completed',
            'description' => $remarks, 'debit_ledger_id' => (int)$rD, 'credit_ledger_id' => (int)$rC,
            'ip_address' => $p['ip'] ?? null, 'user_agent' => isset($p['browser']) ? substr((string)$p['browser'],0,255) : null,
        ]);
        if ($this->db->trans_status() === false) { $this->db->trans_rollback(); return [false, 'Database error.']; }
        $this->db->trans_commit();
        return [true, $ref];
    }

    /* ---------------------------- reference ---------------------------- */

    private function generateReference()
    {
        // WTF-YYYYMMDD-XXXXXXXX (8 hex). Retry on the rare unique clash.
        for ($i = 0; $i < 5; $i++) {
            $ref = 'WTF-'.date('Ymd').'-'.strtoupper(substr(bin2hex(random_bytes(5)), 0, 8));
            if ($this->db->where('ref', $ref)->count_all_results('wallet_internal_transfer') === 0) return $ref;
        }
        return 'WTF-'.date('YmdHis').'-'.strtoupper(substr(bin2hex(random_bytes(5)), 0, 8));
    }

    /* ---------------------------- history ------------------------------ */

    public function history($userId, array $f = [], $limit = 25, $offset = 0)
    {
        $userId = (int)$userId;
        $this->db->select('t.*, us.referral_id AS sender_ref, us.username AS sender_name,
                           ur.referral_id AS recipient_ref, ur.username AS recipient_name', false)
                 ->from('wallet_internal_transfer t')
                 ->join('users us', 'us.id = t.user_id', 'left')
                 ->join('users ur', 'ur.id = t.to_user_id', 'left');
        $this->_histWhere($userId, $f);
        $rows = $this->db->order_by('t.id', 'DESC')->limit((int)$limit, (int)$offset)->get()->result_array();
        foreach ($rows as &$r) {
            $r['direction'] = ((int)$r['user_id'] === $userId) ? 'sent' : 'received';
            $r['wallet'] = $r['from_wallet'];
            $r['counterparty'] = $r['direction'] === 'sent'
                ? ($r['recipient_ref'] ?: ('#'.$r['to_user_id']))
                : ($r['sender_ref'] ?: ('#'.$r['user_id']));
        }
        return $rows;
    }

    public function historyCount($userId, array $f = [])
    {
        $this->db->from('wallet_internal_transfer t');
        $this->_histWhere((int)$userId, $f);
        return (int)$this->db->count_all_results();
    }

    private function _histWhere($userId, array $f)
    {
        // member-to-member: rows where I am the sender OR the recipient
        $this->db->group_start()->where('t.user_id', (int)$userId)
                 ->or_where('t.to_user_id', (int)$userId)->group_end();
        if (!empty($f['reference'])) $this->db->like('t.ref', $f['reference']);
        if (!empty($f['from']))      $this->db->where('t.from_wallet', $f['from']);
        if (!empty($f['status']))    $this->db->where('t.status', $f['status']);
        if (!empty($f['date_from'])) $this->db->where('DATE(t.created_at) >=', $f['date_from']);
        if (!empty($f['date_to']))   $this->db->where('DATE(t.created_at) <=', $f['date_to']);
    }

    public function detail($ref, $userId = 0)
    {
        $this->db->where('ref', $ref);
        if ($userId) $this->db->where('user_id', (int)$userId);
        $h = $this->db->get('wallet_internal_transfer')->row_array();
        if (!$h) return [];
        // the two backing ledger rows carry balance_before/after
        $ledger = $this->db->where_in('id', array_filter([$h['debit_ledger_id'], $h['credit_ledger_id']]))
                           ->get('wallet_ledger')->result_array();
        return ['header' => $h, 'ledger' => $ledger];
    }

    /* ----------------------------- admin ------------------------------- */

    public function adminList(array $f = [], $limit = 50, $offset = 0)
    {
        $this->db->select('t.*, us.username AS sender_name, us.referral_id AS sender_ref, us.email AS sender_email,
                           ur.username AS recipient_name, ur.referral_id AS recipient_ref', false)
                 ->from('wallet_internal_transfer t')
                 ->join('users us', 'us.id = t.user_id', 'left')
                 ->join('users ur', 'ur.id = t.to_user_id', 'left');
        if (!empty($f['reference'])) $this->db->like('t.ref', $f['reference']);
        if (!empty($f['user'])) {
            $this->db->group_start()
                     ->like('us.username', $f['user'])->or_like('us.referral_id', $f['user'])->or_like('us.email', $f['user'])
                     ->or_like('ur.username', $f['user'])->or_like('ur.referral_id', $f['user'])
                     ->group_end();
        }
        if (!empty($f['wallet']))    $this->db->where('t.from_wallet', $f['wallet']);
        if (!empty($f['status']))    $this->db->where('t.status', $f['status']);
        if (!empty($f['date_from'])) $this->db->where('DATE(t.created_at) >=', $f['date_from']);
        if (!empty($f['date_to']))   $this->db->where('DATE(t.created_at) <=', $f['date_to']);
        return $this->db->order_by('t.id', 'DESC')->limit((int)$limit, (int)$offset)->get()->result_array();
    }
}
