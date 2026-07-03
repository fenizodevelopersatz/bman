# 9 — Internal Wallet Transfer (User → Wallet Transfer)

Module specification, database schema, business rules, security constraints, and
UI/UX design for **user-initiated internal balance transfers** between the four
BMAN platform wallets (`exchange · earning · staking · bonus`).

> ⚠️ **USDT Wallet is explicitly excluded.** USDT represents an on-chain
> blockchain asset. Internal ledger transfers must **never** move blockchain
> assets. Only the four internal ledger wallets may participate in this module.

Links: [0_INDEX.md](0_INDEX.md) · [3_CHANGELOG.md](3_CHANGELOG.md) ·
[7_TOKEN_WALLET_INTEGRATION.md](7_TOKEN_WALLET_INTEGRATION.md) ·
[8_WALLET_DEPOSIT_WITHDRAW.md](8_WALLET_DEPOSIT_WITHDRAW.md).

---

## 1. Module identity

| Property      | Value                                                                |
|---------------|----------------------------------------------------------------------|
| Module name   | User → Wallet Transfer                                               |
| Route         | `/user/transfer_wallet`                                              |
| Controller    | `application/controllers/user/User.php` (method `transfer_wallet`)  |
| Model         | `application/models/wallet/Wallettransfer_model.php` *(new)*         |
| Ledger model  | `Walletledger_model` (existing — reuse)                              |
| Balance table | `user_wallets` (existing — reuse)                                    |
| Admin route   | `admin/finance/internal-transfers`                                   |
| Admin menu    | Finance → Internal Wallet Transfers                                  |

---

## 2. Wallet scope — what is and is not allowed

### 2A. Wallet display rules

| Wallet          | Show in UI? | Reason                                          |
|-----------------|:-----------:|-------------------------------------------------|
| Exchange Wallet | ✅ Yes      | Internal ledger balance                         |
| Earning Wallet  | ✅ Yes      | Internal ledger balance                         |
| Staking Wallet  | ✅ Yes      | Internal ledger balance                         |
| Bonus Wallet    | ✅ Yes      | Internal ledger balance                         |
| USDT Wallet     | ❌ **NO**   | Blockchain asset — must never be transferred internally |

### 2B. Allowed transfer pairs

The matrix below defines every allowed `from → to` direction. All other
combinations (including any path involving USDT) are **blocked** at validation.

| From Wallet | To Wallet   | Allowed |
|-------------|-------------|:-------:|
| Exchange    | Earning     | ✅      |
| Exchange    | Staking     | ✅      |
| Exchange    | Bonus       | ✅      |
| Earning     | Exchange    | ✅      |
| Earning     | Bonus       | ✅      |
| Bonus       | Exchange    | ✅      |
| Bonus       | Staking     | ✅      |
| Staking     | Exchange    | ✅      |
| Staking     | Bonus       | ✅      |
| USDT        | Any         | ❌ Blocked — blockchain asset |
| Any         | USDT        | ❌ Blocked — blockchain asset |
| Same wallet | Same wallet | ❌ Blocked — no-op            |

> **Note:** Earning → Staking and Staking → Earning are not in the allowed list
> by default. Add them only if business rules explicitly permit it.

---

## 3. Database schema

### 3A. Existing tables — reuse, do NOT recreate

| Table           | Column(s) used                                          |
|-----------------|---------------------------------------------------------|
| `user_wallets`  | `exchange_balance`, `earning_balance`, `staking_balance`, `bonus_balance` |
| `wallet_ledger` | Reuse for every debit/credit via `Walletledger_model`   |

The `wallet_ledger` `reference_type` for all rows created by this module is
`'wallet_transfer'` and `reference_id` is the `wallet_transfer.reference_no`.

### 3B. New migration file — `db/wallet_internal_transfer.sql`

```sql
-- ============================================================================
-- Internal Wallet Transfer Module
-- Creates three new tables:
--   wallet_transfer        — one row per transfer (header)
--   wallet_transfer_ledger — two rows per transfer (debit + credit mirror)
--   wallet_transfer_audit  — full action audit trail
-- Idempotent: safe to re-run.
-- ============================================================================

-- --------------------------------------------------------------------------
-- 1. Transfer header — one row per user-initiated internal transfer.
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wallet_transfer` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reference_no` VARCHAR(40)     NOT NULL,
  `user_id`      BIGINT          NOT NULL,
  `from_wallet`  ENUM('exchange','earning','staking','bonus') NOT NULL,
  `to_wallet`    ENUM('exchange','earning','staking','bonus') NOT NULL,
  `amount`       DECIMAL(30,8)   NOT NULL,
  `remarks`      TEXT            DEFAULT NULL,
  `status`       ENUM('completed','cancelled','reversed') NOT NULL DEFAULT 'completed',
  `created_ip`   VARCHAR(50)     DEFAULT NULL,
  `browser`      TEXT            DEFAULT NULL,
  `device`       TEXT            DEFAULT NULL,
  `created_at`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_reference` (`reference_no`),
  KEY `idx_user`   (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_from`   (`from_wallet`),
  KEY `idx_to`     (`to_wallet`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------------------------
-- 2. Transfer ledger — two mirror rows per transfer (debit + credit).
--    Stores balance snapshots for full audit. Reference to wallet_ledger via
--    reference_no (wallet_ledger.reference_id = wallet_transfer.reference_no).
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wallet_transfer_ledger` (
  `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `transfer_id`    BIGINT UNSIGNED NOT NULL,
  `user_id`        BIGINT          NOT NULL,
  `wallet_type`    ENUM('exchange','earning','staking','bonus') NOT NULL,
  `entry_type`     ENUM('debit','credit') NOT NULL,
  `amount`         DECIMAL(30,8)   NOT NULL,
  `balance_before` DECIMAL(30,8)   NOT NULL,
  `balance_after`  DECIMAL(30,8)   NOT NULL,
  `reference_no`   VARCHAR(40)     NOT NULL,
  `created_at`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_transfer` (`transfer_id`),
  KEY `idx_user`     (`user_id`),
  KEY `idx_ref`      (`reference_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------------------------
-- 3. Audit log — every action on a transfer (create, cancel, reverse, view).
-- --------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `wallet_transfer_audit` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `transfer_id` BIGINT UNSIGNED NOT NULL,
  `user_id`     BIGINT          NOT NULL,
  `action`      VARCHAR(100)    NOT NULL,
  `description` TEXT            DEFAULT NULL,
  `ip_address`  VARCHAR(50)     DEFAULT NULL,
  `browser`     TEXT            DEFAULT NULL,
  `device`      TEXT            DEFAULT NULL,
  `created_by`  BIGINT          DEFAULT NULL,
  `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_transfer` (`transfer_id`),
  KEY `idx_user`     (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 3C. Reference number format

```
TRF + YYYYMMDD + 4-digit sequence (zero-padded, resets daily)
Example: TRF202607020001
```

Generation logic (PHP):

```php
private function generateReference(): string
{
    $prefix = 'TRF' . date('Ymd');
    $last   = $this->db->like('reference_no', $prefix, 'after')
                        ->order_by('id', 'DESC')
                        ->limit(1)
                        ->get('wallet_transfer')
                        ->row_array();
    $seq = $last ? (int)substr($last['reference_no'], -4) + 1 : 1;
    return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
}
```

---

## 4. Files — new and modified

### 4A. New files

| Path | Purpose |
|------|---------|
| `db/wallet_internal_transfer.sql` | Migration — creates the three new tables |
| `application/models/wallet/Wallettransfer_model.php` | All transfer business logic |
| `application/views/user/wallet/transfer_wallet.php` | User-facing two-tab UI |
| `application/views/admin/wallet/internal_transfers.php` | Admin grid + filters |
| `application/controllers/admin/wallet/Internaltransfers.php` | Admin controller |

### 4B. Modified files

| Path | Change |
|------|--------|
| `application/controllers/user/User.php` | Add `transfer_wallet()`, `transfer_wallet_post()`, `get_transfer_detail()` |
| `application/views/admin/Layout/admin_sidebar.php` | Add Finance → Internal Wallet Transfers menu item |
| `docs/0_INDEX.md` | Add row 9 to Documents table + Status Dashboard |

---

## 5. Model — `Wallettransfer_model`

```php
<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Wallettransfer_model
 * --------------------
 * Business logic for user-initiated internal wallet transfers.
 *
 * Rule: ALL balance changes go through Walletledger_model::transfer() which
 * runs inside a MySQL transaction with SELECT ... FOR UPDATE. This model
 * records the wallet_transfer header, the two mirror ledger rows, and the
 * audit entry.
 *
 * USDT wallet is never accepted as from_wallet or to_wallet.
 */
class Wallettransfer_model extends CI_Model
{
    /** Wallets eligible for internal transfer. USDT is intentionally absent. */
    private $allowed = ['exchange', 'earning', 'staking', 'bonus'];

    /** Permitted from => to pairs. */
    private $pairs = [
        'exchange' => ['earning', 'staking', 'bonus'],
        'earning'  => ['exchange', 'bonus'],
        'bonus'    => ['exchange', 'staking'],
        'staking'  => ['exchange', 'bonus'],
    ];

    // ------------------------------------------------------------------ //
    //  Public API                                                          //
    // ------------------------------------------------------------------ //

    /**
     * Validate and execute a transfer.
     * Returns [true, $reference_no] on success, [false, $error_message] on failure.
     */
    public function execute(array $params): array
    {
        $userId  = (int)$params['user_id'];
        $from    = $params['from_wallet'];
        $to      = $params['to_wallet'];
        $amount  = (string)$params['amount'];
        $remarks = isset($params['remarks']) ? trim($params['remarks']) : null;
        $ip      = $params['ip']      ?? null;
        $browser = $params['browser'] ?? null;
        $device  = $params['device']  ?? null;

        // --- Validation ---
        [$ok, $err] = $this->validate($userId, $from, $to, $amount);
        if (!$ok) return [false, $err];

        // --- Snapshots (before) ---
        $this->load->model('Walletledger_model', 'L');
        $balBefore = $this->L->balances($userId);

        // --- Generate reference ---
        $ref = $this->generateReference();

        // --- Atomic transfer via Walletledger_model ---
        [$ok2, $msg] = $this->L->transfer(
            $userId, $amount, $from, $to, 'wallet_transfer',
            ['reference_id' => $ref, 'description' => 'Internal transfer: '.$from.' to '.$to]
        );
        if (!$ok2) return [false, $msg];

        $balAfter = $this->L->balances($userId);

        // --- Write header ---
        $this->db->insert('wallet_transfer', [
            'reference_no' => $ref,
            'user_id'      => $userId,
            'from_wallet'  => $from,
            'to_wallet'    => $to,
            'amount'       => $amount,
            'remarks'      => $remarks,
            'status'       => 'completed',
            'created_ip'   => $ip,
            'browser'      => $browser,
            'device'       => $device,
            'created_at'   => date('Y-m-d H:i:s'),
        ]);
        $transferId = (int)$this->db->insert_id();

        // --- Write two mirror ledger rows ---
        foreach ([
            ['debit',  $from, $balBefore[$from], $balAfter[$from]],
            ['credit', $to,   $balBefore[$to],   $balAfter[$to]],
        ] as [$entry, $wallet, $before, $after]) {
            $this->db->insert('wallet_transfer_ledger', [
                'transfer_id'    => $transferId,
                'user_id'        => $userId,
                'wallet_type'    => $wallet,
                'entry_type'     => $entry,
                'amount'         => $amount,
                'balance_before' => $before,
                'balance_after'  => $after,
                'reference_no'   => $ref,
                'created_at'     => date('Y-m-d H:i:s'),
            ]);
        }

        // --- Audit ---
        $this->writeAudit(
            $transferId, $userId, 'transfer_created',
            "Transfer {$amount} from {$from} to {$to}. Ref: {$ref}",
            $ip, $browser, $device
        );

        return [true, $ref];
    }

    /**
     * Validate all business rules before executing the transfer.
     */
    public function validate(int $userId, string $from, string $to, string $amount): array
    {
        if (!in_array($from, $this->allowed, true))
            return [false, 'Invalid source wallet.'];
        if (!in_array($to, $this->allowed, true))
            return [false, 'Invalid destination wallet.'];
        if ($from === $to)
            return [false, 'Source and destination wallet must be different.'];
        if (!in_array($to, $this->pairs[$from] ?? [], true))
            return [false, "Transfer from {$from} to {$to} is not allowed."];
        if (!is_numeric($amount) || bccomp($amount, '0', 8) <= 0)
            return [false, 'Amount must be greater than zero.'];

        // Check user status
        $user = $this->db->select('status')->get_where('users', ['id' => $userId])->row_array();
        if (!$user || $user['status'] != '1')
            return [false, 'Account is inactive.'];

        // Check sufficient balance
        $this->load->model('Walletledger_model', 'L');
        $bal = $this->L->balance($userId, $from);
        if (bccomp($bal, $amount, 8) < 0)
            return [false, 'Insufficient balance in ' . ucfirst($from) . ' Wallet.'];

        return [true, ''];
    }

    // ------------------------------------------------------------------ //
    //  History / Detail queries                                            //
    // ------------------------------------------------------------------ //

    /** Paginated transfer history for a user. */
    public function history(int $userId, array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $this->db->where('user_id', $userId);
        if (!empty($filters['reference'])) $this->db->like('reference_no', $filters['reference']);
        if (!empty($filters['from']))      $this->db->where('from_wallet', $filters['from']);
        if (!empty($filters['to']))        $this->db->where('to_wallet',   $filters['to']);
        if (!empty($filters['status']))    $this->db->where('status',      $filters['status']);
        if (!empty($filters['date_from'])) $this->db->where('DATE(created_at) >=', $filters['date_from']);
        if (!empty($filters['date_to']))   $this->db->where('DATE(created_at) <=', $filters['date_to']);
        return $this->db->order_by('id', 'DESC')->limit($limit, $offset)->get('wallet_transfer')->result_array();
    }

    /** Count for pagination. */
    public function historyCount(int $userId, array $filters = []): int
    {
        $this->db->where('user_id', $userId);
        if (!empty($filters['reference'])) $this->db->like('reference_no', $filters['reference']);
        if (!empty($filters['from']))      $this->db->where('from_wallet', $filters['from']);
        if (!empty($filters['status']))    $this->db->where('status',      $filters['status']);
        return (int)$this->db->count_all_results('wallet_transfer');
    }

    /** Full detail for popup — header + ledger rows + audit trail. */
    public function detail(string $referenceNo, int $userId = 0): array
    {
        $qb = $this->db->where('reference_no', $referenceNo);
        if ($userId) $qb = $qb->where('user_id', $userId);
        $header = $this->db->get('wallet_transfer')->row_array();
        if (!$header) return [];

        $ledger = $this->db->where('transfer_id', $header['id'])
                            ->get('wallet_transfer_ledger')->result_array();
        $audit  = $this->db->where('transfer_id', $header['id'])
                            ->order_by('id', 'ASC')
                            ->get('wallet_transfer_audit')->result_array();
        return compact('header', 'ledger', 'audit');
    }

    // ------------------------------------------------------------------ //
    //  Admin queries                                                       //
    // ------------------------------------------------------------------ //

    /** Admin grid: all transfers across all users with optional filters. */
    public function adminList(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $this->db->select('wt.*, u.username, u.email, u.referral_id');
        $this->db->from('wallet_transfer wt');
        $this->db->join('users u', 'u.id = wt.user_id', 'left');
        if (!empty($filters['reference'])) $this->db->like('wt.reference_no', $filters['reference']);
        if (!empty($filters['user'])) {
            $this->db->group_start()
                     ->like('u.username',    $filters['user'])
                     ->or_like('u.referral_id', $filters['user'])
                     ->group_end();
        }
        if (!empty($filters['wallet'])) {
            $this->db->group_start()
                     ->where('wt.from_wallet', $filters['wallet'])
                     ->or_where('wt.to_wallet', $filters['wallet'])
                     ->group_end();
        }
        if (!empty($filters['status']))    $this->db->where('wt.status',            $filters['status']);
        if (!empty($filters['date_from'])) $this->db->where('DATE(wt.created_at) >=', $filters['date_from']);
        if (!empty($filters['date_to']))   $this->db->where('DATE(wt.created_at) <=', $filters['date_to']);
        return $this->db->order_by('wt.id', 'DESC')->limit($limit, $offset)->get()->result_array();
    }

    // ------------------------------------------------------------------ //
    //  Internals                                                           //
    // ------------------------------------------------------------------ //

    private function generateReference(): string
    {
        $prefix = 'TRF' . date('Ymd');
        $last   = $this->db->like('reference_no', $prefix, 'after')
                            ->order_by('id', 'DESC')
                            ->limit(1)
                            ->get('wallet_transfer')
                            ->row_array();
        $seq = $last ? (int)substr($last['reference_no'], -4) + 1 : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    private function writeAudit(
        int $transferId, int $userId, string $action, string $desc,
        ?string $ip, ?string $browser, ?string $device, int $createdBy = 0
    ): void {
        $this->db->insert('wallet_transfer_audit', [
            'transfer_id' => $transferId,
            'user_id'     => $userId,
            'action'      => $action,
            'description' => $desc,
            'ip_address'  => $ip,
            'browser'     => $browser,
            'device'      => $device,
            'created_by'  => $createdBy ?: null,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
    }
}
```

---

## 6. Controller — `User.php` (new methods)

```php
// -----------------------------------------------------------------------
// Route: GET  /user/transfer_wallet
// -----------------------------------------------------------------------
public function transfer_wallet()
{
    $userId = $this->session->userdata('user_userid');
    $this->load->model('Walletledger_model', 'L');

    // Wallet balances — USDT excluded from display
    $all = $this->L->balances($userId);
    $this->data['balances'] = [
        'exchange' => $all['exchange'],
        'earning'  => $all['earning'],
        'staking'  => $all['staking'],
        'bonus'    => $all['bonus'],
    ];

    // History (Tab 2)
    $this->load->model('wallet/Wallettransfer_model', 'WT');
    $filters = [
        'reference' => $this->input->get('reference', true),
        'from'      => $this->input->get('from', true),
        'status'    => $this->input->get('status', true),
        'date_from' => $this->input->get('date_from', true),
        'date_to'   => $this->input->get('date_to', true),
    ];
    $page   = max(1, (int)$this->input->get('page'));
    $limit  = 15;
    $offset = ($page - 1) * $limit;

    $this->data['history']    = $this->WT->history($userId, $filters, $limit, $offset);
    $this->data['total']      = $this->WT->historyCount($userId, $filters);
    $this->data['filters']    = $filters;
    $this->data['page']       = $page;
    $this->data['limit']      = $limit;
    $this->data['title']      = 'Internal Wallet Transfer';
    $this->data['active_tab'] = $this->input->get('tab') === 'history' ? 'history' : 'transfer';

    $this->load->view('user/layout/header', $this->data);
    $this->load->view('user/wallet/transfer_wallet', $this->data);
    $this->load->view('user/layout/footer');
}

// -----------------------------------------------------------------------
// Route: POST /user/transfer_wallet_post   (AJAX)
// -----------------------------------------------------------------------
public function transfer_wallet_post()
{
    if (!$this->input->is_ajax_request()) show_404();

    $userId = (int)$this->session->userdata('user_userid');
    $json   = function($ok, $msg, $data = []) {
        $this->output->set_content_type('application/json')
                     ->set_output(json_encode(compact('ok', 'msg', 'data')));
    };

    $from   = $this->input->post('from_wallet',       true);
    $to     = $this->input->post('to_wallet',         true);
    $amount = $this->input->post('amount',            true);
    $remarks= $this->input->post('remarks',           true);
    $tpass  = $this->input->post('transfer_password', true);

    // Verify transfer password
    $user = $this->db->get_where('users', ['id' => $userId])->row_array();
    if (!$user) return $json(false, 'User not found.');
    if (!password_verify($tpass, $user['transfer_password'] ?? ''))
        return $json(false, 'Incorrect transfer password.');

    // OTP check hookpoint (extend when OTP service is active)

    $this->load->model('wallet/Wallettransfer_model', 'WT');
    [$ok, $result] = $this->WT->execute([
        'user_id'     => $userId,
        'from_wallet' => $from,
        'to_wallet'   => $to,
        'amount'      => $amount,
        'remarks'     => $remarks,
        'ip'          => $this->input->ip_address(),
        'browser'     => $this->input->user_agent(),
        'device'      => $this->agent->platform().' '.$this->agent->browser(),
    ]);

    if ($ok) {
        $this->load->model('Walletledger_model', 'L');
        $balances = $this->L->balances($userId);
        unset($balances['usdt']); // never expose USDT in transfer context
        return $json(true, 'Transfer completed. Reference: '.$result, [
            'reference' => $result,
            'balances'  => $balances,
        ]);
    }
    return $json(false, $result);
}

// -----------------------------------------------------------------------
// Route: GET /user/get_transfer_detail?ref=TRF20260702xxxx  (AJAX/modal)
// -----------------------------------------------------------------------
public function get_transfer_detail()
{
    if (!$this->input->is_ajax_request()) show_404();
    $userId = (int)$this->session->userdata('user_userid');
    $ref    = $this->input->get('ref', true);

    $this->load->model('wallet/Wallettransfer_model', 'WT');
    $detail = $this->WT->detail($ref, $userId);

    $this->output->set_content_type('application/json')
                 ->set_output(json_encode(['ok' => (bool)$detail, 'data' => $detail]));
}
```

---

## 7. Transfer flow — step by step

```
User fills form
  │  from_wallet=exchange · to_wallet=bonus · amount=250 · transfer_password=***
  │
  ▼
POST /user/transfer_wallet_post
  │
  ├─ Verify transfer password ───────────── FAIL → error JSON
  ├─ Validate OTP (if enabled) ──────────── FAIL → error JSON
  │
  ├─ Wallettransfer_model::validate()
  │   ├─ USDT not in from/to ────────────── FAIL → error JSON
  │   ├─ from ≠ to ──────────────────────── FAIL → error JSON
  │   ├─ pair allowed ───────────────────── FAIL → error JSON
  │   ├─ amount > 0 ─────────────────────── FAIL → error JSON
  │   ├─ user.status = '1' ──────────────── FAIL → error JSON
  │   └─ exchange_balance >= 250 ────────── FAIL → error JSON
  │
  ├─ Generate reference_no: TRF20260702xxxx
  │
  ├─ BEGIN MySQL transaction (inside Walletledger_model::transfer)
  │   ├─ SELECT exchange_balance FOR UPDATE
  │   ├─ DEBIT  exchange: 1000 → 750  (wallet_ledger row)
  │   ├─ SELECT bonus_balance   FOR UPDATE
  │   └─ CREDIT bonus:    100  → 350  (wallet_ledger row)
  ├─ COMMIT
  │
  ├─ INSERT wallet_transfer            (header, status='completed')
  ├─ INSERT wallet_transfer_ledger     (debit  row: exchange before=1000 after=750)
  ├─ INSERT wallet_transfer_ledger     (credit row: bonus    before=100  after=350)
  ├─ INSERT wallet_transfer_audit      (action='transfer_created')
  │
  └─ JSON { ok: true, reference: 'TRF20260702xxxx', balances: {...} }
```

### Ledger entry example

| Table                    | entry_type | wallet_type | amount | balance_before | balance_after | reference              |
|--------------------------|:----------:|-------------|-------:|---------------:|--------------:|------------------------|
| `wallet_ledger`          | (debit)    | exchange    | 250    | 1000.00        | 750.00        | *(reference_id field)* |
| `wallet_ledger`          | (credit)   | bonus       | 250    | 100.00         | 350.00        | *(reference_id field)* |
| `wallet_transfer_ledger` | debit      | exchange    | 250    | 1000.00        | 750.00        | TRF202607020001        |
| `wallet_transfer_ledger` | credit     | bonus       | 250    | 100.00         | 350.00        | TRF202607020001        |

> The same `reference_no` appears as `reference_id` on both `wallet_ledger` rows,
> making it trivial to pull all ledger activity for one transfer from either table.

---

## 8. Validation rules

| Rule               | Detail                                                                           |
|--------------------|----------------------------------------------------------------------------------|
| `from_wallet`      | Must be `exchange`, `earning`, `staking`, or `bonus` — never `usdt`             |
| `to_wallet`        | Same constraint                                                                   |
| `from ≠ to`        | Transferring to the same wallet is blocked                                       |
| Pair allowed       | Must be in the permitted matrix (§2B)                                            |
| Amount > 0         | Zero, negative, or non-numeric values are rejected                               |
| Precision          | Amount must not exceed 8 decimal places (DECIMAL(30,8))                          |
| Balance check      | `from_wallet` balance must be ≥ amount (checked with row lock)                  |
| User active        | `users.status = '1'`                                                             |
| Transfer password  | Must match hashed `users.transfer_password` via `password_verify()`             |
| OTP                | Verified against OTP service if user has it enabled                             |
| Double submit      | CSRF token + unique `reference_no` enforce idempotency                          |
| Frozen wallet      | Extend `user_wallets` with a `frozen` flag if required by business rules        |

---

## 9. Business rules

| Rule                      | Behaviour                                                                  |
|---------------------------|----------------------------------------------------------------------------|
| USDT never an option      | Dropdown and server-side both exclude USDT                                |
| Atomic transaction        | All balance updates in a single `BEGIN … COMMIT`                          |
| Partial updates blocked   | Any failure triggers full ROLLBACK                                        |
| Zero transfer             | Blocked (`amount > 0`)                                                    |
| Negative amount           | Blocked at validation                                                     |
| Same-wallet transfer      | Blocked (`from ≠ to`)                                                     |
| Disabled / frozen wallet  | Returns error if wallet flag is inactive                                  |
| Unique reference number   | `UNIQUE KEY` on `reference_no` prevents duplicates                        |
| Audit always written      | Audit row inserted on every successful transfer                           |
| Notification on success   | In-app and/or email notification sent after successful transfer           |

---

## 10. Security checklist

| Control                    | Implementation                                                    |
|----------------------------|-------------------------------------------------------------------|
| ✅ Transfer password        | `password_verify()` against `users.transfer_password`            |
| ✅ User active check        | `users.status = '1'` before proceeding                           |
| ✅ USDT excluded            | Both client-side (dropdown) and server-side (validation)          |
| ✅ Balance row lock         | `SELECT … FOR UPDATE` prevents race conditions                    |
| ✅ MySQL transaction        | `BEGIN / COMMIT / ROLLBACK` — no partial updates ever             |
| ✅ CSRF protection          | CI CSRF token on POST form                                        |
| ✅ Double submit prevention | Unique `reference_no` + CSRF token                               |
| ✅ Unique reference         | `UNIQUE KEY uq_reference` on `wallet_transfer.reference_no`      |
| ✅ Audit log                | IP, browser, device, old/new balances stored on every transfer    |
| ✅ OTP (optional)           | Hook point in controller; activate when OTP service is wired      |
| ✅ No raw SQL               | All writes via model / CI query builder                           |
| ✅ Input sanitisation       | All POST input through `$this->input->post(…, TRUE)`             |

---

## 11. UI design — `/user/transfer_wallet`

### Two-tab layout

```
┌─────────────────────────────────────────────────────────────┐
│  [💸 New Transfer]   [📋 Transfer History]                  │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  TAB 1 — New Transfer                                       │
│  ┌─────────────────────────────────────────────────────┐    │
│  │  CARD 1 — Wallet Summary                           │    │
│  │  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌───────┐ │    │
│  │  │ Exchange │ │ Earning  │ │ Staking  │ │ Bonus │ │    │
│  │  │ 1000.00  │ │  500.00  │ │  250.00  │ │100.00 │ │    │
│  │  └──────────┘ └──────────┘ └──────────┘ └───────┘ │    │
│  └─────────────────────────────────────────────────────┘    │
│  ┌─────────────────────────────────────────────────────┐    │
│  │  CARD 2 — Transfer Form                            │    │
│  │  From Wallet *        [Exchange Wallet ▼]          │    │
│  │  To Wallet *          [Bonus Wallet    ▼]          │    │
│  │  Transfer Amount *    [_______________]            │    │
│  │  Transfer Password *  [_______________]            │    │
│  │  Remarks (Optional)   [_______________]            │    │
│  │  OTP (if enabled)     [_______________]            │    │
│  │  ℹ️  USDT Wallet is excluded — blockchain asset     │    │
│  │  [          Submit Transfer          ]             │    │
│  └─────────────────────────────────────────────────────┘    │
│                                                             │
│  TAB 2 — Transfer History                                   │
│  ┌───────────────────────────────────────────────────────┐  │
│  │  🔍 Reference | Wallet ▼ | Status ▼ | Date Range     │  │
│  ├───────────┬──────────┬──────────┬────────┬────────┬───┤  │
│  │ Reference │ From     │ To       │ Amount │ Status │ 👁 │  │
│  ├───────────┼──────────┼──────────┼────────┼────────┼───┤  │
│  │ TRF…0001  │ Exchange │ Bonus    │ 250.00 │ ✅Done │ 👁 │  │
│  └───────────┴──────────┴──────────┴────────┴────────┴───┘  │
└─────────────────────────────────────────────────────────────┘
```

### Card 1 — Wallet Summary

- Shows **four** wallet balance tiles (Exchange / Earning / Staking / Bonus).
- USDT wallet tile is **not rendered**.
- Balances refresh via AJAX after a successful transfer (no full page reload).
- Count-up animation when a tile balance updates.

### Card 2 — Transfer Form fields

| Field              | Type       | Required | Notes                                                     |
|--------------------|------------|:--------:|-----------------------------------------------------------|
| From Wallet        | `<select>` | ✅       | Options: Exchange, Earning, Staking, Bonus — **no USDT** |
| To Wallet          | `<select>` | ✅       | Filtered dynamically by allowed pairs based on From Wallet|
| Transfer Amount    | `number`   | ✅       | Min: 0.00000001; shows available balance below field      |
| Transfer Password  | `password` | ✅       | Server-side `password_verify()` comparison                |
| Remarks            | `textarea` | ❌       | Optional; stored in `wallet_transfer.remarks`             |
| OTP                | `text`     | ❌       | Visible only if user has OTP enabled                      |

### Info banner (always visible in Tab 1)

```
ℹ️  Internal Transfer Rules
• USDT Wallet is excluded — it represents a blockchain asset.
• Only internal ledger balances can be transferred.
• Allowed: Exchange ↔ Earning, Staking, Bonus | Earning ↔ Bonus | Bonus ↔ Staking.
```

### Tab 2 — History filter bar

| Filter     | Type                                            |
|------------|-------------------------------------------------|
| Reference  | Text search                                     |
| From Wallet| Select (Exchange / Earning / Staking / Bonus)   |
| Status     | Select (Completed / Cancelled / Reversed)       |
| Date From  | Date picker                                     |
| Date To    | Date picker                                     |

### History grid columns

| # | Column    | Notes                              |
|---|-----------|------------------------------------|
| 1 | Reference | Monospace, copy-to-clipboard button |
| 2 | From      | Wallet label badge                 |
| 3 | To        | Wallet label badge                 |
| 4 | Amount    | Right-aligned, 8 dp                |
| 5 | Status    | Coloured pill                      |
| 6 | Created   | Relative + absolute datetime       |
| 7 | View      | Opens detail modal (eye icon)      |

### View Detail modal

```
┌────────────────────────────────────────────────────────┐
│  Transfer Detail  ·  TRF202607020001                  ×│
├────────────────────────────────────────────────────────┤
│  Reference:    TRF202607020001                         │
│  Transfer ID:  42                                      │
│  Amount:       250.00000000 BMAN                       │
│  From Wallet:  Exchange                                │
│  To Wallet:    Bonus                                   │
│  Status:       ✅ Completed                            │
│  Remarks:      —                                       │
│  IP Address:   192.168.x.x                            │
│  Browser:      Chrome / Windows                       │
│  Created:      2026-07-02 14:35:22                    │
├──── Ledger Entries ────────────────────────────────────┤
│  DEBIT   Exchange   Before: 1000.00   After: 750.00   │
│  CREDIT  Bonus      Before:  100.00   After: 350.00   │
├──── Audit Log ─────────────────────────────────────────┤
│  2026-07-02 14:35:22  transfer_created                 │
│  "Transfer 250 from exchange to bonus. Ref: TRF…"     │
└────────────────────────────────────────────────────────┘
```

---

## 12. Admin side — Finance → Internal Wallet Transfers

### 12A. Menu addition (`admin_sidebar.php`)

Add under the **Finance** group (or create it if absent):

```php
<!-- Finance > Internal Wallet Transfers -->
<div class="menu-item">
    <a class="menu-link" href="<?= base_url('admin/finance/internal-transfers') ?>">
        <span class="menu-icon">
            <i class="ki-duotone ki-transfer fs-3">
                <span class="path1"></span><span class="path2"></span>
            </i>
        </span>
        <span class="menu-title">Internal Wallet Transfers</span>
    </a>
</div>
```

### 12B. Admin controller — `Internaltransfers.php`

Route: `admin/finance/internal-transfers`

```php
<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Internaltransfers extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Admin_model');
        if (!$this->session->userdata('admin_logged_in')) redirect('admin/login');
        $user = $this->Admin_model->get_user($this->session->userdata('admin_userid'));
        if ($user->admin_roll == '1') {
            $perms = json_decode($user->permission_pages, true);
            if (empty($perms['finance_wallet_transfer'])) {
                $this->session->set_flashdata('error', 'Access Denied.');
                redirect('admin');
            }
        }
        $this->load->model('wallet/Wallettransfer_model', 'WT');
    }

    public function index()
    {
        $filters = [
            'reference' => $this->input->get('reference', true),
            'user'      => $this->input->get('user',      true),
            'wallet'    => $this->input->get('wallet',    true),
            'status'    => $this->input->get('status',    true),
            'date_from' => $this->input->get('date_from', true),
            'date_to'   => $this->input->get('date_to',   true),
        ];
        $page   = max(1, (int)$this->input->get('page'));
        $limit  = 25;
        $offset = ($page - 1) * $limit;

        $this->data['transfers'] = $this->WT->adminList($filters, $limit, $offset);
        $this->data['filters']   = $filters;
        $this->data['title']     = 'Internal Wallet Transfers';
        $this->load->view('admin/wallet/internal_transfers', $this->data);
    }

    /** AJAX: transfer detail for admin modal — no user_id restriction. */
    public function detail()
    {
        $ref    = $this->input->get('ref', true);
        $detail = $this->WT->detail($ref);
        $this->output->set_content_type('application/json')
                     ->set_output(json_encode(['ok' => (bool)$detail, 'data' => $detail]));
    }
}
```

### 12C. Admin grid columns

| Column    | Source                                   |
|-----------|------------------------------------------|
| Reference | `wallet_transfer.reference_no`           |
| User      | `users.username (referral_id)`           |
| From      | `wallet_transfer.from_wallet` (badge)    |
| To        | `wallet_transfer.to_wallet`  (badge)     |
| Amount    | `wallet_transfer.amount`                 |
| Status    | Coloured pill                            |
| Created   | `wallet_transfer.created_at`             |
| Action    | View detail modal button                 |

### 12D. Admin filters

| Filter     | Type                                            |
|------------|-------------------------------------------------|
| Date range | Date pickers                                    |
| User       | Text (username or referral ID)                  |
| Wallet     | Select (Exchange / Earning / Staking / Bonus)   |
| Status     | Select (Completed / Cancelled / Reversed)       |
| Reference  | Text                                            |

---

## 13. Notification — user

After a successful transfer send an in-app notification and/or email:

```
Subject: Internal Wallet Transfer Completed — TRF202607020001

250.00000000 BMAN has been transferred from your Exchange Wallet
to your Bonus Wallet.

Reference:  TRF202607020001
Date:       2026-07-02 14:35:22

If you did not initiate this transfer, please contact support immediately.
```

---

## 14. Status values

| Status      | Meaning                                                                        |
|-------------|--------------------------------------------------------------------------------|
| `completed` | Transfer executed successfully — default for all user-initiated transfers      |
| `cancelled` | Admin-cancelled before crediting (pre-completion cancellation hook)            |
| `reversed`  | Admin-reversed after completion — creates offsetting ledger entries            |

---

## 15. Allowed transfer pairs — quick reference

| Wallet   | Can Transfer Out To …                   |
|----------|-----------------------------------------|
| Exchange | Earning ✅  Staking ✅  Bonus ✅         |
| Earning  | Exchange ✅  Bonus ✅                    |
| Bonus    | Exchange ✅  Staking ✅                  |
| Staking  | Exchange ✅  Bonus ✅                    |
| USDT     | ❌ Never — blockchain asset             |

---

## 16. Status — built vs planned

**Planned (this phase):**
- `db/wallet_internal_transfer.sql` — idempotent migration (3 tables)
- `Wallettransfer_model` — validate, execute, history, historyCount, detail, adminList
- `User::transfer_wallet()` + `transfer_wallet_post()` + `get_transfer_detail()` — user routes
- `user/wallet/transfer_wallet.php` — two-tab view (New Transfer + History + detail modal)
- `Internaltransfers.php` + `admin/wallet/internal_transfers.php` — admin grid + modal
- `admin_sidebar.php` — Finance → Internal Wallet Transfers menu item
- `docs/0_INDEX.md` — row 9 + status dashboard entry

**Deferred:**
- OTP service integration (hook point exists in controller, awaits OTP model)
- Admin reversal flow — creates offsetting `wallet_transfer` + `wallet_transfer_ledger` rows
- PDF statement export including transfer history
- Per-day / per-amount transfer caps (extend `validate()`)
- Rate-limiting on POST endpoint

> Task board: [0_INDEX.md](0_INDEX.md). Every shipped change is logged in
> [3_CHANGELOG.md](3_CHANGELOG.md).
