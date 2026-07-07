<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Wallettransferservice_model — THE single Wallet Transfer Validation & Execution
 * engine. Both the User Panel and Admin Panel call validate()/execute() here so
 * the business rules are identical everywhere (no duplicated logic).
 *
 * Currency: BMAN only. Wallets: exchange · earning · staking · bonus.
 *
 * Member transfers (to another user):
 *   exchange / earning / staking → any member in the SOURCE user's downline
 *   bonus                        → ONLY the source user's DIRECT SPONSOR
 * Internal transfers (source user's own wallets):
 *   exchange → bonus | earning | staking   (Exchange is SOURCE-ONLY; never receives)
 *   no reverse, no other pairs
 *
 * Admin acts on behalf of a chosen source user and follows the EXACT same rules
 * (admin does NOT bypass wallet-movement or balance rules). KYC + transfer
 * password are User-Panel gates only.
 *
 * Every movement is a double-entry ledger post (row-locked, ACID) with an
 * immutable audit row. See docs/16.
 */
class Wallettransferservice_model extends CI_Model
{
    private $wallets = ['exchange', 'earning', 'staking', 'bonus'];
    /** Internal (own-wallet) allowed directions — Exchange source-only. */
    private $internalPairs = ['exchange' => ['bonus', 'earning', 'staking']];

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Walletledger_model', 'L');
    }

    public function wallets() { return $this->wallets; }
    public function internalPairs() { return $this->internalPairs; }
    /** Member transfer: which recipient constraint applies to each source wallet. */
    public function memberRule($wallet)
    {
        if (in_array($wallet, ['exchange','earning','staking'], true)) return 'downline';
        if ($wallet === 'bonus') return 'direct_sponsor';
        return null;
    }

    /* ------------------------- relationship helpers ---------------------- */

    private function resolveUser($idOrRef)
    {
        $idOrRef = trim((string)$idOrRef);
        if ($idOrRef === '') return null;
        if (ctype_digit($idOrRef)) {
            $u = $this->db->get_where('users', ['id' => (int)$idOrRef])->row_array();
            if ($u) return $u;
        }
        return $this->db->group_start()->where('referral_id', $idOrRef)->or_where('username', $idOrRef)
                        ->or_where('email', $idOrRef)->group_end()->get('users')->row_array() ?: null;
    }

    /** The direct sponsor's user id (users.sponser may hold an id or referral id). */
    public function directSponsorId($userId)
    {
        $u = $this->db->select('sponser')->get_where('users', ['id' => (int)$userId])->row_array();
        if (!$u || $u['sponser'] === null || $u['sponser'] === '') return 0;
        $sp = $this->resolveUser($u['sponser']);
        return $sp ? (int)$sp['id'] : 0;
    }

    /** True if $recipientId is in $sourceId's downline (walk the sponsor chain up). */
    public function isInDownline($sourceId, $recipientId)
    {
        $sourceId = (int)$sourceId; $cur = (int)$recipientId;
        for ($i = 0; $i < 200 && $cur > 0; $i++) {
            $up = $this->directSponsorId($cur);
            if ($up === $sourceId) return true;
            if ($up <= 0 || $up === $cur) break;
            $cur = $up;
        }
        return false;
    }

    private function precisionOk($amount)
    {
        $s = (string)$amount;
        return !(strpos($s, '.') !== false && strlen(substr($s, strpos($s, '.') + 1)) > 8);
    }

    /* ------------------------------ validate ---------------------------- */

    /**
     * @param array $c { mode:'member'|'internal', source_user_id, from_wallet,
     *   to_wallet?(internal), recipient?(member id/ref), amount, via:'user'|'admin',
     *   transfer_password?(user), require_kyc?(bool) }
     * @return array ['ok'=>bool,'code'=>str,'message'=>str,'ctx'=>array]
     */
    public function validate(array $c)
    {
        $mode   = ($c['mode'] ?? '') === 'member' ? 'member' : 'internal';
        $src    = (int)($c['source_user_id'] ?? 0);
        $from   = (string)($c['from_wallet'] ?? '');
        $amount = (string)($c['amount'] ?? '');
        $via    = ($c['via'] ?? 'user') === 'admin' ? 'admin' : 'user';

        // amount
        if (!is_numeric($amount) || bccomp($amount, '0', 8) <= 0) return $this->_no('invalid_amount', 'Amount must be greater than zero.');
        if (!$this->precisionOk($amount)) return $this->_no('precision', 'Amount has too many decimal places (max 8).');

        // source user
        $srcU = $this->db->get_where('users', ['id' => $src])->row_array();
        if (!$srcU) return $this->_no('source_not_found', 'Source user not found.');
        if ((string)$srcU['status'] !== '1') return $this->_no('source_inactive', 'Source account is inactive or blocked.');

        // from wallet (BMAN wallets only — USDT excluded)
        if (!in_array($from, $this->wallets, true)) return $this->_no('invalid_from_wallet', 'Invalid source wallet.');

        // User-Panel-only gates
        if ($via === 'user') {
            if (!empty($c['require_kyc']) && strtolower((string)($srcU['kyc_status'] ?? '')) !== 'approved')
                return $this->_no('kyc_required', 'Your KYC must be approved before transferring.');
            $pw = (string)($c['transfer_password'] ?? '');
            $hash = (string)($srcU['transfer_password'] ?? '');
            if ($hash === '') return $this->_no('transfer_password_unset', 'Set a transfer password first.');
            // password_verify for new hashes; md5 fallback for legacy stored PINs.
            if ($pw === '' || !(password_verify($pw, $hash) || md5($pw) === $hash))
                return $this->_no('transfer_password', 'Incorrect transfer password.');
        }

        $recipientId = null; $toWallet = $from;
        if ($mode === 'internal') {
            $to = (string)($c['to_wallet'] ?? '');
            if (!in_array($to, $this->wallets, true)) return $this->_no('invalid_to_wallet', 'Invalid destination wallet.');
            if ($from !== 'exchange') return $this->_no('internal_source_must_be_exchange', 'Only the Exchange wallet can move to other wallets.');
            if ($to === $from || !in_array($to, $this->internalPairs['exchange'], true))
                return $this->_no('internal_pair_not_allowed', 'This internal wallet direction is not allowed.');
            $toWallet = $to; $recipientId = $src; // self
        } else {
            $rec = $this->resolveUser($c['recipient'] ?? '');
            if (!$rec) return $this->_no('recipient_not_found', 'Recipient member not found.');
            $recipientId = (int)$rec['id'];
            if ($recipientId === $src) return $this->_no('self_transfer', 'Cannot transfer to yourself in a member transfer.');
            if ((string)$rec['status'] !== '1') return $this->_no('recipient_inactive', 'Recipient account is inactive or blocked.');
            $rule = $this->memberRule($from);
            if ($rule === 'direct_sponsor') {
                if ($recipientId !== $this->directSponsorId($src))
                    return $this->_no('bonus_only_to_sponsor', 'Bonus wallet can only be transferred to your direct sponsor.');
            } elseif ($rule === 'downline') {
                if (!$this->isInDownline($src, $recipientId))
                    return $this->_no('recipient_not_in_downline', 'Recipient must be in your downline.');
            } else {
                return $this->_no('wallet_not_transferable', 'This wallet cannot be transferred to a member.');
            }
        }

        // sufficient balance (enforced for BOTH panels — admin does not bypass)
        if (bccomp($this->L->balance($src, $from), $amount, 8) < 0)
            return $this->_no('insufficient_balance', 'Insufficient balance in the ' . ucfirst($from) . ' Wallet.');

        return ['ok' => true, 'code' => 'ok', 'message' => '',
                'ctx' => ['mode'=>$mode,'source_user_id'=>$src,'recipient_id'=>$recipientId,
                          'from_wallet'=>$from,'to_wallet'=>$toWallet,'amount'=>$amount,'via'=>$via]];
    }

    private function _no($code, $msg) { return ['ok'=>false,'code'=>$code,'message'=>$msg,'ctx'=>null]; }

    /* ------------------------------ execute ----------------------------- */

    public function execute(array $c)
    {
        $v = $this->validate($c);
        $this->audit($c, $v['ok'] ? 'validated' : 'rejected', $v['code'], $v['message']);
        if (!$v['ok']) return ['ok'=>false,'code'=>$v['code'],'message'=>$v['message']];
        $x = $v['ctx'];

        // Idempotency: a completed transfer with the same key returns its ref (retry-safe).
        $key = isset($c['idempotency_key']) ? substr(trim((string)$c['idempotency_key']), 0, 80) : null;
        if ($key) {
            $prev = $this->db->get_where('wallet_internal_transfer', ['idempotency_key'=>$key,'status'=>'completed'])->row_array();
            if ($prev) { $this->audit($c, 'idempotent_hit', 'ok', 'already processed', $prev['id'], $prev['ref']); return ['ok'=>true,'ref'=>$prev['ref'],'idempotent'=>true]; }
        }

        $src = $x['source_user_id']; $from = $x['from_wallet']; $to = $x['to_wallet'];
        $rcp = $x['recipient_id']; $amount = $x['amount']; $via = $x['via'];
        $isMember = $x['mode'] === 'member';
        $adminId  = ($via === 'admin') ? (int)($c['actor_id'] ?? 0) : null;

        $fromBefore = $this->L->balance($src, $from);
        $toBefore   = $this->L->balance($rcp, $isMember ? $from : $to);
        $ref  = 'WTS-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        $uid8 = (string)random_int(10000000, 99999999);

        $this->db->trans_begin();
        // 1) debit source (row-locked, re-verified inside post)
        list($okD, $rD) = $this->L->debit($src, $from, $amount, 'wallet_transfer',
            ['reference_id'=>$ref,'created_by'=>$adminId,'description'=>'Transfer '.$from.' ('.$x['mode'].') ['.$uid8.']']);
        if (!$okD) { $this->db->trans_rollback(); $this->audit($c,'failed','debit_failed',(string)$rD); return ['ok'=>false,'code'=>'debit_failed','message'=>$rD]; }
        // 2) credit destination (self→to_wallet ; member→recipient same wallet)
        list($okC, $rC) = $this->L->credit($rcp, $isMember ? $from : $to, $amount, 'wallet_transfer',
            ['reference_id'=>$ref,'created_by'=>$adminId,'description'=>'Transfer received '.$from.' ['.$uid8.']']);
        if (!$okC) { $this->db->trans_rollback(); $this->audit($c,'failed','credit_failed',(string)$rC); return ['ok'=>false,'code'=>'credit_failed','message'=>$rC]; }

        // 3) transfer history (double-entry linked via ledger ids)
        $this->db->insert('wallet_internal_transfer', [
            'ref'=>$ref,'txn_uid'=>$uid8,'user_id'=>$src,'to_user_id'=>$isMember ? $rcp : null,
            'from_wallet'=>$from,'to_wallet'=>$isMember ? $from : $to,
            'amount'=>$amount,'fee'=>0,'net_amount'=>$amount,'status'=>'completed',
            'txn_type'=>$isMember ? 'member' : 'self','via'=>$via,
            'from_before'=>$fromBefore,'from_after'=>bcsub($fromBefore,$amount,8),
            'to_before'=>$toBefore,'to_after'=>bcadd($toBefore,$amount,8),
            'description'=>isset($c['note']) ? substr((string)$c['note'],0,255) : null,
            'debit_ledger_id'=>(int)$rD,'credit_ledger_id'=>(int)$rC,
            'idempotency_key'=>$key,'created_by'=>$adminId,
            'ip_address'=>$c['ip'] ?? null,'user_agent'=>isset($c['ua']) ? substr((string)$c['ua'],0,255) : null,
        ]);
        $tid = (int)$this->db->insert_id();

        if ($this->db->trans_status() === false) { $this->db->trans_rollback(); $this->audit($c,'failed','db_error','database error'); return ['ok'=>false,'code'=>'db_error','message'=>'Database error.']; }
        $this->db->trans_commit();
        $this->audit($c, 'executed', 'ok', 'transfer completed', $tid, $ref);
        return ['ok'=>true,'ref'=>$ref,'transfer_id'=>$tid];
    }

    /* ------------------------------- audit ------------------------------ */

    private function audit(array $c, $action, $code, $msg, $tid = null, $ref = null)
    {
        try {
            $via = ($c['via'] ?? 'user') === 'admin' ? 'admin' : 'user';
            $this->db->insert('wallet_transfer_audit', [
                'transfer_id'=>$tid,'ref'=>$ref,'action'=>$action,'mode'=>$c['mode'] ?? null,'via'=>$via,
                'actor_type'=>$via,'actor_id'=>(int)($c['actor_id'] ?? 0) ?: null,
                'source_user_id'=>(int)($c['source_user_id'] ?? 0) ?: null,
                'recipient_id'=>isset($c['recipient']) && ctype_digit((string)$c['recipient']) ? (int)$c['recipient'] : null,
                'from_wallet'=>$c['from_wallet'] ?? null,'to_wallet'=>$c['to_wallet'] ?? null,
                'amount'=>is_numeric($c['amount'] ?? null) ? $c['amount'] : null,
                'result_code'=>$code,'message'=>substr((string)$msg,0,250),
                'ip_address'=>$c['ip'] ?? null,'user_agent'=>isset($c['ua']) ? substr((string)$c['ua'],0,250) : null,
                'request_id'=>$c['request_id'] ?? null,
            ]);
        } catch (Throwable $e) { /* audit must never break a transfer */ }
    }

    /** Full detail for the transaction view (header + ledger rows + audit). */
    public function detail($ref, $restrictUserId = 0)
    {
        $h = $this->db->get_where('wallet_internal_transfer', ['ref'=>$ref])->row_array();
        if (!$h) return null;
        if ($restrictUserId && (int)$h['user_id'] !== (int)$restrictUserId && (int)($h['to_user_id'] ?? 0) !== (int)$restrictUserId) return null;
        $ledger = $this->db->where_in('id', array_filter([$h['debit_ledger_id'] ?? 0, $h['credit_ledger_id'] ?? 0]))
                           ->get('wallet_ledger')->result_array();
        $audit  = $this->db->where('ref', $ref)->order_by('id','ASC')->get('wallet_transfer_audit')->result_array();
        return ['header'=>$h,'ledger'=>$ledger,'audit'=>$audit];
    }
}
