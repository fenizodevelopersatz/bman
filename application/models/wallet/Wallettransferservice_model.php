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

    /**
     * All user ids in $sourceId's downline (BFS down the sponsor tree). `sponser`
     * may store an id OR a referral id, so each level matches children by BOTH the
     * parent's id and referral id. Cycle-guarded; capped for safety.
     */
    public function downlineIds($sourceId, $cap = 5000)
    {
        $sourceId = (int)$sourceId;
        $src = $this->db->select('id, referral_id')->get_where('users', ['id' => $sourceId])->row_array();
        if (!$src) return [];
        $keys = [(string)$src['id']];
        if (!empty($src['referral_id'])) $keys[] = (string)$src['referral_id'];
        $seen = []; $depth = 0;
        while (!empty($keys) && $depth < 100 && count($seen) < $cap) {
            $rows = $this->db->select('id, referral_id')->where_in('sponser', $keys)->limit($cap)->get('users')->result_array();
            $keys = [];
            foreach ($rows as $r) {
                $id = (int)$r['id'];
                if ($id === $sourceId || isset($seen[$id])) continue;   // cycle / self guard
                $seen[$id] = true;
                $keys[] = (string)$id;
                if (!empty($r['referral_id'])) $keys[] = (string)$r['referral_id'];
            }
            $depth++;
        }
        return array_keys($seen);
    }

    /**
     * Valid recipient rows for a (source user, from-wallet) pair — the ONLY members
     * that pass the member rule, optionally filtered by a search term. Used to scope
     * the recipient pickers in BOTH panels so users can't pick an invalid recipient.
     *   exchange/earning/staking → the source's downline
     *   bonus                    → the source's direct sponsor only
     * @return array [ ['id','username','name','email','referral_id'], ... ]
     */
    public function recipientOptions($sourceId, $fromWallet, $q = '', $limit = 20)
    {
        $sourceId = (int)$sourceId;
        $rule = $this->memberRule((string)$fromWallet);
        if (!$sourceId || $rule === null) return [];
        $q = trim((string)$q);
        $sel = 'id, username, name, email, referral_id';

        if ($rule === 'direct_sponsor') {
            $spId = $this->directSponsorId($sourceId);
            if (!$spId) return [];
            $sp = $this->db->select($sel)->where('id', $spId)->where('status', '1')->get('users')->row_array();
            if (!$sp) return [];
            if ($q !== '') {                                    // honour the search box
                $hay = strtolower($sp['username'].' '.$sp['name'].' '.$sp['email'].' '.$sp['referral_id'].' '.$sp['id']);
                if (strpos($hay, strtolower($q)) === false) return [];
            }
            return [$sp];
        }

        // downline
        $ids = $this->downlineIds($sourceId);
        if (empty($ids)) return [];
        $this->db->select($sel)->from('users')->where('status', '1')->where_in('id', $ids);
        if ($q !== '') {
            $this->db->group_start()->like('username', $q)->or_like('email', $q)
                     ->or_like('referral_id', $q)->or_like('name', $q)->or_like('id', $q)->group_end();
        }
        return $this->db->order_by('id', 'ASC')->limit($limit)->get()->result_array();
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

    /* ---------------- UI support (read-only; engine unchanged) ----------- */

    /**
     * Pre-submit preview for the shared UI: runs the SAME rule/balance checks the
     * engine uses (via 'admin' → skips only the User-Panel KYC/password gates,
     * which the confirm dialog collects separately) and returns balances +
     * recipient info. Does NOT execute anything.
     */
    public function preview(array $c)
    {
        $ctx = array_merge($c, ['via' => 'admin']);        // rules + balance only
        $v   = $this->validate($ctx);
        $src = (int)($c['source_user_id'] ?? 0);
        $from = (string)($c['from_wallet'] ?? '');
        $fromBal = in_array($from, $this->wallets, true) ? $this->L->balance($src, $from) : '0';
        $amt   = is_numeric($c['amount'] ?? null) ? (string)$c['amount'] : '0';
        $after = (bccomp($amt, '0', 8) > 0 && bccomp($fromBal, $amt, 8) >= 0) ? bcsub($fromBal, $amt, 8) : null;

        $recipient = null;
        if (($c['mode'] ?? '') === 'member' && !empty($v['ctx']['recipient_id'])) {
            $r = $this->db->select('id, username, name, email, referral_id')->get_where('users', ['id' => $v['ctx']['recipient_id']])->row_array();
            if ($r) $recipient = ['id'=>(int)$r['id'],'name'=>$r['name'] ?: $r['username'],'email'=>$r['email'],'referral_id'=>$r['referral_id']];
        }
        // extra context flags for the User Panel (shown, not blocking the preview)
        $srcU = $this->db->select('kyc_status, transfer_password')->get_where('users', ['id'=>$src])->row_array() ?: [];
        return [
            'ok' => $v['ok'], 'code' => $v['code'], 'message' => $v['ok'] ? 'All business rules passed.' : $v['message'],
            'from_balance' => $fromBal, 'balance_after' => $after, 'recipient' => $recipient,
            'kyc_ok' => strtolower((string)($srcU['kyc_status'] ?? '')) === 'approved',
            'has_transfer_password' => !empty($srcU['transfer_password']),
            'to_wallet' => $v['ctx']['to_wallet'] ?? ($c['to_wallet'] ?? $from),
        ];
    }

    /** Full user identity for the detail modal (distinct username vs full name + gate flags). */
    private function _uname($id)
    {
        if (!$id) return null;
        $u = $this->db->select('id, username, name, first_name, last_name, email, referral_id, kyc_status, transfer_password')
                      ->get_where('users', ['id'=>(int)$id])->row_array();
        if (!$u) return null;
        $full = trim((string)$u['name']) !== '' ? $u['name'] : trim(($u['first_name'] ?? '').' '.($u['last_name'] ?? ''));
        return [
            'id'          => (int)$u['id'],
            'username'    => $u['username'],
            'name'        => $full !== '' ? $full : $u['username'],
            'full_name'   => $full,
            'email'       => $u['email'],
            'referral_id' => $u['referral_id'],
            'kyc_status'  => $u['kyc_status'],
            'kyc_ok'      => strtolower((string)$u['kyc_status']) === 'approved',
            'has_transfer_password' => !empty($u['transfer_password']),
        ];
    }

    /**
     * Enriched detail for the shared 7-tab modal — every field verified against the
     * live schema, real records only. Blocks: transaction, sender, receiver,
     * ledger_entries, blockchain (+ linked onchain_transactions), validation
     * (derived from the transfer's own record), users (sponsor/upline context),
     * plus the raw header/ledger/audit for full traceability.
     */
    public function detailEnriched($ref, $restrictUserId = 0)
    {
        $d = $this->detail($ref, $restrictUserId);
        if (!$d) return null;
        $h = $d['header'];
        $src = (int)$h['user_id']; $rcp = (int)($h['to_user_id'] ?? 0);
        $isMember = ($h['txn_type'] === 'member');
        $completed = ($h['status'] === 'completed');
        $sponsorId = $this->directSponsorId($src);
        $uplineId  = $sponsorId ? $this->directSponsorId($sponsorId) : 0;

        $senderU    = $this->_uname($src);
        $receiverU  = $rcp ? $this->_uname($rcp) : null;
        $d['users'] = [
            'sender'    => $senderU,
            'recipient' => $receiverU,
            'sponsor'   => $sponsorId ? $this->_uname($sponsorId) : null,
            'upline'    => $uplineId ? $this->_uname($uplineId) : null,
        ];

        // ---- transaction ----
        $d['transaction'] = [
            'txn_id'       => $h['txn_uid'] ?: (string)$h['id'],
            'reference'    => $h['ref'],
            'type'         => $isMember ? 'member' : 'self',
            'type_label'   => $isMember ? 'Member Transfer' : 'Internal Transfer',
            'via'          => $h['via'],
            'amount'       => $h['amount'],
            'fee'          => $h['fee'],
            'net_amount'   => $h['net_amount'],
            'token'        => 'BMAN',                          // internal wallets are BMAN-only (USDT excluded)
            'status'       => $h['status'],
            'created_at'   => $h['created_at'],
            'completed_at' => $completed ? $h['updated_at'] : null,
            'note'         => $h['description'],
            'failure_reason' => $h['failure_reason'] ?? null,
        ];

        // ---- sender / receiver (with wallet + balances from the authoritative transfer row) ----
        $d['sender'] = $senderU ? array_merge($senderU, [
            'wallet'         => $h['from_wallet'],
            'balance_before' => $h['from_before'],
            'balance_after'  => $h['from_after'],
        ]) : null;
        $d['receiver'] = $isMember ? ($receiverU ? array_merge($receiverU, [
            'wallet'         => $h['to_wallet'],
            'balance_before' => $h['to_before'],
            'balance_after'  => $h['to_after'],
        ]) : null) : [
            'self'           => true,
            'wallet'         => $h['to_wallet'],
            'balance_before' => $h['to_before'],
            'balance_after'  => $h['to_after'],
        ];

        // ---- ledger entries (debit = sender's from-wallet, credit = receiver's wallet) ----
        $d['ledger_entries'] = [
            'debit'  => ['ledger_id'=>$h['debit_ledger_id'],  'wallet'=>$h['from_wallet'], 'amount'=>$h['amount'],
                         'balance_before'=>$h['from_before'], 'balance_after'=>$h['from_after']],
            'credit' => ['ledger_id'=>$h['credit_ledger_id'], 'wallet'=>$h['to_wallet'],   'amount'=>$h['amount'],
                         'balance_before'=>$h['to_before'],   'balance_after'=>$h['to_after']],
            'reference' => $h['ref'],
            'rows'      => $d['ledger'],                        // raw wallet_ledger rows
        ];

        // ---- blockchain (only when a hash exists; enrich from onchain_transactions if linked) ----
        $chain = null;
        if (!empty($h['tx_hash']) || (!empty($h['network']) && !empty($h['block_number']))) {
            $oc = null;
            if ($this->db->table_exists('onchain_transactions')) {
                $this->db->from('onchain_transactions');
                if (!empty($h['tx_hash'])) $this->db->where('tx_hash', $h['tx_hash']);
                else $this->db->where('reference_id', $ref);
                $oc = $this->db->order_by('id','DESC')->limit(1)->get()->row_array();
            }
            $chain = [
                'tx_hash'       => $h['tx_hash'] ?: ($oc['tx_hash'] ?? null),
                'block_number'  => $h['block_number'] ?: ($oc['block_number'] ?? null),
                'confirmations' => $h['confirmations'] !== null ? $h['confirmations'] : ($oc['confirmation_count'] ?? null),
                'gas_used'      => $h['gas_used'] ?: ($oc['gas_used'] ?? null),
                'gas_fee'       => $h['gas_fee'] ?: ($oc['gas_fee_total'] ?? null),
                'network'       => $h['network'] ?: ($oc['network'] ?? 'BSC'),
                'status'        => $oc['status'] ?? null,
                'from_address'  => $oc['from_address'] ?? null,
                'to_address'    => $oc['to_address'] ?? null,
                'token_symbol'  => $oc['token_symbol'] ?? 'BMAN',
            ];
        }
        $d['blockchain'] = $chain;                              // null → off-chain internal ledger settlement

        // ---- validation (derived from THIS transfer's own record, not re-mocked) ----
        $memberRule = $this->memberRule($h['from_wallet']);
        $viaAdmin   = ($h['via'] === 'admin');
        $ok = $completed ? 'passed' : ($h['status'] === 'failed' ? 'failed' : 'n/a');
        $checks = [];
        // wallet-direction rule always applies
        $checks[] = ['key'=>'wallet_rule', 'label'=>'Wallet direction rule', 'applies'=>true, 'status'=>$ok,
                     'detail'=>$isMember ? (ucfirst($h['from_wallet']).' → recipient '.ucfirst($h['to_wallet']))
                                         : (ucfirst($h['from_wallet']).' → '.ucfirst($h['to_wallet']).' (Exchange source-only)')];
        // downline / sponsor only for member transfers
        $checks[] = ['key'=>'downline', 'label'=>'Downline validation',
                     'applies'=>($isMember && $memberRule === 'downline'), 'status'=>($isMember && $memberRule==='downline') ? $ok : 'n/a',
                     'detail'=>($isMember && $memberRule==='downline') ? 'Recipient is in the source user\'s downline' : 'Not applicable'];
        $checks[] = ['key'=>'direct_sponsor', 'label'=>'Direct sponsor validation',
                     'applies'=>($isMember && $memberRule === 'direct_sponsor'), 'status'=>($isMember && $memberRule==='direct_sponsor') ? $ok : 'n/a',
                     'detail'=>($isMember && $memberRule==='direct_sponsor') ? 'Recipient is the source user\'s direct sponsor' : 'Not applicable'];
        // user-panel gates
        $checks[] = ['key'=>'transfer_password', 'label'=>'Transfer password',
                     'applies'=>!$viaAdmin, 'status'=>$viaAdmin ? 'overridden' : $ok,
                     'detail'=>$viaAdmin ? 'Skipped (admin-initiated)' : 'Verified before execution'];
        $checks[] = ['key'=>'kyc', 'label'=>'KYC validation',
                     'applies'=>!$viaAdmin, 'status'=>$viaAdmin ? 'overridden' : $ok,
                     'detail'=>$viaAdmin ? 'Skipped (admin-initiated)' : ('Sender KYC: '.($senderU['kyc_status'] ?? '—'))];
        $checks[] = ['key'=>'admin_override', 'label'=>'Admin override',
                     'applies'=>$viaAdmin, 'status'=>$viaAdmin ? 'yes' : 'no',
                     'detail'=>$viaAdmin ? ('Admin'.($h['created_by'] ? ' #'.$h['created_by'] : '').' acted on behalf of the source user') : 'User-initiated transfer'];
        $d['validation'] = [
            'overall' => $completed ? 'passed' : $h['status'],
            'checks'  => $checks,
        ];

        return $d;
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
