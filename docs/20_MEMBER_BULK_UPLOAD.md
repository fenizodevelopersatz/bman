# 20 — Member Bulk Upload (Excel/CSV) + Hot-Wallet BMAN Delivery Cron

🟢 **Implemented + tested.** Admin ▸ Members Management ▸ **Bulk Upload**.

Create many members from one spreadsheet. Each row becomes a full member:
login credentials, binary-tree placement, a freshly generated on-chain wallet
address, and an opening BMAN balance that is delivered from the admin hot
(Treasury) wallet by a cron and posted to the member's **Exchange wallet**.

| | |
|---|---|
| Admin page | `admin/member/bulk-upload` |
| Cron endpoint | `/member-bulk-bman-cron?token=<cron_token>` |
| Cron Lab | "Member Bulk Upload — Opening BMAN (Hot Wallet → Member)" |
| Migrations | `db/2026-07-29_member_bulk_upload.sql`, `db/2026-07-29_member_bulk_exchange_credit.sql` |
| Safety default | `enabled = 0`, `dry_run = 1` — no BMAN moves until an admin flips both |

---

## 1. End-to-end flow

```
   Excel / CSV
        |
        v
 [ STAGE ]  parse + validate every row
        |   writes ONLY member_bulk_upload_batches / _rows
        |   *** not one `users` row is touched ***
        v
   admin reviews the per-row verdict
        |
        v
 [ IMPORT ]  per row, in its own transaction:
        |      1. Mlm_model::registerUser()      -> users + binary_placement
        |      2. overwrite password with the staged bcrypt hash
        |      3. Custodialwallet_model::ensureAddress() -> user_wallet (address + QR)
        |      4. bman > 0 ?  bman_status = 'pending'
        v
   accounts exist and can log in immediately
        |
        v
 [ CRON ]  member-bulk-bman-cron, every few minutes
        |   phase 1  back-credit any send that landed without its ledger entry
        |   phase 2  drain the pending queue:
        |              Web3bman::sendToken(treasury -> member address)
        |              Walletledger_model::credit(member, 'exchange', amount)
        v
   member holds the BMAN on-chain AND sees it in their Exchange wallet
```

**Member creation never depends on the cron or on any on-chain configuration.**
Only the money movement is gated.

---

## 2. The sheet

Header row required. Column names are matched case-, space- and
punctuation-insensitively, so `Reference ID`, `reference_id` and
`REFERENCE-ID` all resolve to the same field.

| Column | Required | Notes |
|---|---|---|
| `username` | ✅ | Unique across `users` **and** within the file |
| `email` | ✅ | **This is the login identity** — `Common_model::userloginVerify()` matches on email, not username. Must be unique |
| `password` | — | Blank cells fall back to the form's **Default password** box. At least one of the two must be present |
| `reference_id` | ✅ | The **sponsor's** referral code. Drives binary placement. Accepts the `L-CODE` / `R-CODE` prefix used by public referral links |
| `leg` | — | `left` / `right` / `auto`. Overrides the `L-`/`R-` prefix and the form's default leg |
| `bman` | — | Opening balance. Queued for the cron; `0` or blank means no transfer |

Accepted formats: **`.xlsx`**, `.xlsm`, `.csv`, `.txt`. The legacy binary `.xls`
is rejected with a message telling the admin to re-save.

**Download Template** emits a real **`.xlsx`** workbook (bold header row),
seeded with a genuine sponsor code from the install so the example rows work
as-is. Append `?format=csv` for a CSV instead. **Export Result** on the batch
detail page is likewise `.xlsx`, with the same `?format=csv` escape hatch; if
`ext-zip` is unavailable both fall back to CSV rather than erroring.

In generated workbooks the `bman` column is written as a **number** (so Excel
right-aligns and sums it) while every other column is written as **text** —
that is what stops a referral code like `001234` from losing its leading zeros
the moment the file is opened.

> **The sponsor must already exist.** A new member's own referral code is
> generated (`NEXMAN######`), so it cannot be known in advance — you cannot
> reference a member created earlier in the same file. Import parent levels
> first, export to get their codes, then import the next level.

---

## 3. Wallet address generation

Addresses are **never read from the sheet**. During import each member gets one
via the existing `Custodialwallet_model::ensureAddress()`:

1. `Web3bman::generateWallet()` — a fresh secp256k1 BEP-20 keypair, generated
   **locally**; no external service is involved.
2. Uniqueness check against `user_wallet.wallet_address` (retried up to 5×).
3. Private key encrypted with `Web3bman::encryptKey()` (AES-256 via CI's
   `ENCRYPTION_KEY`) before storage.
4. QR PNG rendered to `assets/images/qr_image/<address>qr_code.png`
   (best-effort — a QR failure never blocks the address).
5. Row inserted into `user_wallet`.

`ensureAddress()` is idempotent: a member who already has an address keeps it.

---

## 4. Placement from `reference_id`

Placement goes through `Mlm_model::registerUser()` — the **same** call the
public signup uses — so the auto-placement counter and the last-leg walk stay
on one code path and cannot drift.

| Sheet value | Resulting leg |
|---|---|
| `leg` column = `left` / `right` | that leg |
| `reference_id` = `L-NEXMAN123456` | left |
| `reference_id` = `R-NEXMAN123456` | right |
| neither | the form's **Default leg**; `auto` lets the binary engine choose |

`auto` is passed to `registerUser()` as a `null` leg, which is its existing
"you decide" signal.

> ### ⚠️ `Mlm_model` is deliberately NOT modified
> An earlier revision added a 6th `$password_is_hash` parameter to
> `registerUser()`. It was reverted: `application/controllers/admin/withdraw/Withdraw.php:338`
> **already passes six arguments to that five-parameter method**. PHP silently
> discards the extra one today, but a new 6th parameter would *capture* it and
> change how that endpoint stores passwords.
>
> Import instead passes a throwaway secret and overwrites the password hash
> inside the same transaction, so the throwaway never reaches a committed row.
> **Do not add parameters to `registerUser()` without fixing that caller first.**

---

## 5. Password handling

Plaintext passwords never touch disk.

- The upload is read straight from PHP's temp path — it is **never moved under
  `uploads/`** — and PHP discards it when the request ends.
- Each row's effective password is bcrypt-hashed during **stage**, while the
  plaintext is still only in request memory.
- Only that hash is stored (`member_bulk_upload_rows.password_hash`), which is
  also what makes a staged batch importable later from any browser.
- The hash is **cleared** once copied into `users` — and on discard.
- `Memberbulkupload_model::rows()` selects an explicit column list that omits
  `password_hash`, because the stage endpoint JSON-encodes its result back to
  the browser.

---

## 6. BMAN delivery — hot wallet → member

### Why a cron and not part of the import

Import is a synchronous admin request. Broadcasting N on-chain transfers inside
one is how a 300-member file ends up half-finished on a timeout. Queue-then-
sweep also gives every transfer its own retry surface and audit row.

### What the cron does per row

1. **Claim** the row atomically (`bman_status` `pending` → `processing`, guarded
   by `WHERE bman_status='pending'`, so a second runner cannot re-claim it).
2. **Send** `Web3bman::sendToken(treasuryKey, memberAddress, amount)` — real
   BMAN, from the single custodial **Treasury (hot) wallet** configured in
   Master ▸ Token Settings.
3. **Credit the Exchange wallet** via
   `Walletledger_model::credit($user, 'exchange', $amount, 'admin_adjustment', ['tx_hash' => $tx, …])`.

That one ledger call — the same one `Depositlistener_model` uses for a detected
deposit — does all of:

- locks the balance row (`SELECT … FOR UPDATE`)
- appends a `wallet_ledger` entry carrying `balance_after`
- updates `user_wallets.exchange_balance`
- applies the normal **maturity rules** for an exchange credit (an
  admin-granted opening balance is no more withdrawable than a deposit of the
  same size)
- mirrors the movement into `onchain_transactions`

Because the ledger already captures the on-chain row, the cron does **not**
call `Onchaintx_model::capture()` itself — a second call would duplicate the
movement in transaction history.

### Self-healing backfill (phase 1)

A crash between "sent" and "credited" would leave real BMAN on the member's
address with no Exchange balance. Every pass therefore first sweeps rows that
are `bman_status='completed'` with a **real** (non-`DRYRUN-`) hash and a NULL
`bman_ledger_id`, and posts the missing credit.

This is safe to repeat forever: `wallet_ledger` has a
**UNIQUE (`tx_hash`, `wallet_type`)** index, so the same send can only ever be
credited once.

Phase 1 runs **before** the treasury handshake on purpose — posting a ledger
entry for BMAN that has already moved needs no key, no balance and no RPC, so a
misconfigured or drained treasury must not be able to block the healing.

When the pending queue is empty the cron **skips the treasury handshake
entirely** rather than spending an RPC round-trip and a key decrypt to discover
there is nothing to do.

### Dry run credits nothing

A dry run records a synthetic `DRYRUN-…` hash and **does not** post to the
Exchange wallet. A spendable balance with no BMAN behind it is exactly what
dry-run exists to prevent. Dry-run rows show `DRY-RUN` in the Exchange column.

### Failure policy

A failed **send** is terminal — it is *not* auto-retried by the next sweep. A
money-moving queue should surface its failures rather than silently hammer a
broken RPC or an empty treasury. An admin re-queues it by hand from the batch
detail page after investigating.

A failed **credit** is different: the BMAN has already moved, so the row stays
`completed`, the reason is recorded, and phase 1 retries it automatically.

---

## 7. Settings

`member_bulk_upload_settings` (row `id = 1`) — its own row, deliberately not a
reuse of `treasury_direct_send_settings` or
`wallet_transfer_settlement_settings`: flipping one on-chain feature live must
never accidentally flip another.

| Field | Default | Meaning |
|---|---|---|
| `enabled` | `0` | Master switch. `0` = the cron refuses to send; the queue just accumulates |
| `dry_run` | `1` | `1` = synthetic `DRYRUN-` hash, nothing broadcast, nothing credited |
| `credit_exchange_wallet` | `1` | `0` = deliver on-chain only, leave dashboard balances untouched |
| `min_treasury_reserve` | `0` | Refuses a send that would drop the treasury below this |
| `max_batch_size` | `20` | Rows claimed per cron pass (also caps the backfill sweep) |
| `max_rows_per_file` | `1000` | Guard against a runaway sheet |

---

## 8. Schema

### `member_bulk_upload_batches` — one row per uploaded file
`ref` (`MBU-YYYYMMDD-XXXXXXXX`) · `admin_id` · `original_name` · `status`
(`staged`/`importing`/`completed`/`failed`/`cancelled`) · row counters ·
`bman_queued` · `bman_total` · `default_leg` · `send_bman` · timestamps.

The file itself is never stored — only its name.

### `member_bulk_upload_rows` — one row per sheet row
Audit trail **and** the cron's pending queue.

| Group | Columns |
|---|---|
| Parsed input | `row_number`, `username`, `email`, `reference_id`, `sponsor_id`, `leg`, `bman_amount` |
| Credentials | `password_hash` (bcrypt only; cleared after import) |
| Validation | `status` (`valid`/`invalid`/`imported`/`failed`/`skipped`), `error_message` |
| Import result | `user_id`, `referral_id`, `wallet_address` |
| On-chain send | `bman_status` (`none`/`pending`/`processing`/`completed`/`failed`), `bman_attempts`, `bman_tx_hash`, `bman_network`, `bman_error`, `bman_sent_at` |
| Exchange credit | `bman_ledger_id`, `bman_credited_at` |

Indexes: `idx_bman_status (bman_status, id)` for the send queue,
`idx_bman_credit_backfill (bman_status, bman_ledger_id)` for the backfill sweep.

### Run lock
Reuses `wallet_settlement_cron_state` (keyed by `job`) with
`job = 'member_bulk_bman'` and the same atomic conditional-UPDATE lock as the
settlement cron, plus a 30-minute stale-lock takeover.

> **Migration style note:** both migrations use plain columns only (no
> `STORED`/generated columns) so a DB re-import from the master dump cannot
> silently drop them, and the second uses the `information_schema` + `PREPARE`
> guard pattern rather than a `DELIMITER` stored procedure — `DELIMITER` is a
> `mysql` CLI directive, so a migration using it cannot be applied
> programmatically.

---

## 9. Running the cron

```bash
php index.php memberbulkbmancron run
```

| Method | Command |
|---|---|
| HTTP | `/member-bulk-bman-cron?token=<cron_token>` |
| CLI | `php index.php memberbulkbmancron run` |
| Health check | `/member-bulk-bman-cron/test` — returns enabled/dry-run, queue depth, lock state |
| Cron Lab | Admin ▸ Finance ▸ Cron Lab → *Member Bulk Upload — Opening BMAN* |

Windows Task Scheduler drives this through `cron.php` like every other job —
see [12_WINDOWS_CRON_SCHEDULER.md](12_WINDOWS_CRON_SCHEDULER.md).

Run it **every few minutes**: each pass claims at most `max_batch_size` rows.

---

## 10. Go-live checklist

1. Apply both migrations (idempotent; safe to re-run).
2. Confirm `enabled = 0`, `dry_run = 1`.
3. Deploy. **The page is already usable — member creation needs no cron.**
4. Schedule the cron endpoint.
5. Verify Master ▸ Token Settings has a **funded** treasury wallet and a
   decryptable key.
6. Set `enabled = 1`, **keep `dry_run = 1`**. Run one pass. Confirm `DRYRUN-`
   hashes appear and **no** Exchange balances moved.
7. Clear `dry_run`. Validate with a **small real batch** first.
8. Optionally set `min_treasury_reserve` as a floor guard.

**Requires** PHP `zip` + `SimpleXML` for `.xlsx` (both standard; `.csv` works
without `zip`) and `bcmath`.

---

## 11. Rollback

| Scope | Action |
|---|---|
| **Instant kill switch** | `member_bulk_upload_settings.enabled = 0` — no deploy needed. The cron no-ops, the queue accumulates |
| **Stop broadcasting only** | `dry_run = 1` |
| **Stop internal credits only** | `credit_exchange_wallet = 0` |
| **Code** | Revert the merge — nothing else depends on this and no shared file's behaviour changed |
| **Schema** | Additive and unreferenced by existing code; can be left in place with no effect |

```sql
DROP TABLE `member_bulk_upload_rows`;
DROP TABLE `member_bulk_upload_batches`;
DROP TABLE `member_bulk_upload_settings`;
DELETE FROM `wallet_settlement_cron_state` WHERE `job` = 'member_bulk_bman';
```

Do **not** drop `wallet_settlement_cron_state` itself — the pre-existing
settlement cron uses it.

> **Data caveat.** Members already imported are ordinary members — reverting
> this code does **not** remove them, and any BMAN already broadcast is
> on-chain and irreversible. That is why the dry-run gate exists. Every batch
> stays fully auditable in `member_bulk_upload_batches` / `_rows` (per-row tx
> hashes and ledger ids) and exportable to CSV even after a code revert.

---

## 12. Testing performed

Verified against a live local database, then fully cleaned up.

**Spreadsheet reader** (`Sheetreader`) — 9/9 on a generated `.xlsx`: shared
strings, inline strings, cached formula results, **column-gap rebuilding**
(omitted cells must not shift later columns left), `workbook.xml.rels`
resolution so a renamed first tab is read rather than `sheet1.xml`, trailing
blank rows, semicolon sniffing, BOM stripping, `.xls`/unsupported rejection.

**Spreadsheet writer** (`Sheetwriter`) — 42/42 OOXML package assertions: all six
required parts present and well-formed, every part declared in
`[Content_Types].xml`, both `.rels` graphs resolving (package → workbook →
worksheet + styles), the workbook's `r:id` matching a declared relationship,
numeric vs inline-string cell typing, the bold header style, empty cells
omitted, illegal sheet-name characters stripped, XML-illegal control
characters stripped, column letters past Z (`AA`, `AD`), and a **round trip**
back through `Sheetreader`.

**Over real HTTP** — the template endpoint returns
`Content-Type: application/vnd.openxmlformats-…sheet`, a `.xlsx` filename and a
clean `PK\x03\x04` header with no stray bytes; the downloaded file opens as a
valid zip with all parts well-formed; `?format=csv` still returns CSV. The
generated workbook was then **uploaded back into the stage endpoint** and
validated 3/3 rows with the BMAN amounts read as numbers (`100`, `250.5`), and
the stage response carried no `password_hash`.

**Import** — 8-row sheet with aliased headers (`Username`, `E-Mail`,
`BMAN Balance`): 3 valid / 5 rejected (bad sponsor, malformed email, in-file
duplicate email, negative BMAN, short password) each with the right message; 0
users created during staging; correct placement including the `L-` prefix row;
distinct valid addresses; `password_verify` passing for both sheet-supplied and
default passwords; staged hashes cleared after import.

**Exchange credit** — balances moved exactly (`12.5`, `7`), `wallet_ledger`
rows written with maturity metadata, `bman_ledger_id` recorded; **second pass
did not double-credit** (still one ledger row each, balance unchanged),
confirming the `UNIQUE (tx_hash, wallet_type)` guard and the backfill's
idempotency. Run with an empty pending queue, so **zero RPC calls** were made.

**Not covered:** no real on-chain broadcast has been exercised — the live send
path is unverified until step 7 of the go-live checklist.

---

## 13. Related docs

- [7_TOKEN_WALLET_INTEGRATION.md](7_TOKEN_WALLET_INTEGRATION.md) — custodial model, treasury-key handling
- [8_WALLET_DEPOSIT_WITHDRAW.md](8_WALLET_DEPOSIT_WITHDRAW.md) — the double-entry ledger this credits into
- [16_WALLET_TRANSFER_ENGINE.md](16_WALLET_TRANSFER_ENGINE.md) — the settlement cron whose queue/lock shape this mirrors
- [12_WINDOWS_CRON_SCHEDULER.md](12_WINDOWS_CRON_SCHEDULER.md) — how crons are scheduled
