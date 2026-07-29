# 20a — Member Bulk Upload: SQL Runbook

Every SQL statement needed to install, go live with, monitor, tune, and stop
the Member Bulk Upload module. Companion to
[20_MEMBER_BULK_UPLOAD.md](20_MEMBER_BULK_UPLOAD.md), which explains *why* each
piece works the way it does.

> **Nothing here runs itself.** The cron settings are **not** editable from the
> admin page — they gate real money movement. Every change below is a
> deliberate, manual SQL execution.

---

## 0. Safety posture (shipped defaults — do not weaken casually)

The migration seeds the module **off**:

| Setting | Ships as | Effect |
|---|---|---|
| `enabled` | `0` | The cron refuses to send. The queue just accumulates. |
| `dry_run` | `1` | Even once enabled, records a synthetic `DRYRUN-` hash and broadcasts nothing. |
| `credit_exchange_wallet` | `1` | A delivered send is also posted to the member's Exchange wallet. |

**Both `enabled=1` and `dry_run=0` are required before a single real BMAN
moves.** Member accounts are created at import regardless — these switches gate
only the on-chain money movement.

Verify the posture at any time:

```sql
SELECT enabled, dry_run, credit_exchange_wallet,
       min_treasury_reserve, max_batch_size, max_rows_per_file
FROM member_bulk_upload_settings
WHERE id = 1;
```

Expected on a fresh deploy: `enabled=0, dry_run=1, credit_exchange_wallet=1`.

---

## 1. Install (run once per environment)

Apply in this order. Both are **idempotent** — safe to re-run.

```bash
mysql -u <user> -p <database> < db/2026-07-29_member_bulk_upload.sql
mysql -u <user> -p <database> < db/2026-07-29_member_bulk_exchange_credit.sql
```

Confirm all objects exist:

```sql
SELECT TABLE_NAME
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('member_bulk_upload_batches',
                     'member_bulk_upload_rows',
                     'member_bulk_upload_settings',
                     'wallet_settlement_cron_state');
-- expect 4 rows

SELECT COLUMN_NAME
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'member_bulk_upload_rows'
  AND COLUMN_NAME IN ('bman_ledger_id', 'bman_credited_at');
-- expect 2 rows

SELECT * FROM wallet_settlement_cron_state WHERE job = 'member_bulk_bman';
-- expect 1 row
```

At this point the admin page is **fully usable**: uploading, validating and
importing members needs no cron and no on-chain configuration.

---

## 2. Go live — the deliberate sequence

Do **not** skip steps 2.1–2.3. The live send path has never been exercised
against a real treasury.

### 2.1 — Pre-flight: confirm the treasury is ready

```sql
SELECT id, status, network, treasury_wallet,
       IF(treasury_pk_enc IS NULL OR treasury_pk_enc = '', 'MISSING', 'present') AS treasury_key
FROM token_settings
WHERE status = 1;
```

You need exactly one active row, a `treasury_wallet` address, and
`treasury_key = present`. Separately confirm that wallet actually **holds
enough BMAN** (plus BNB for gas) for the batches you intend to send.

### 2.2 — Enable, but stay in dry-run

```sql
UPDATE member_bulk_upload_settings
   SET enabled = 1,
       dry_run = 1
 WHERE id = 1;
```

Now run one cron pass (`/member-bulk-bman-cron?token=…`, or Cron Lab ▸
*Member Bulk Upload — Opening BMAN*) and verify:

```sql
-- rows should be 'completed' with a DRYRUN- hash, and NOT credited
SELECT bman_status, bman_tx_hash, bman_ledger_id, bman_credited_at
FROM member_bulk_upload_rows
WHERE bman_status = 'completed'
ORDER BY id DESC
LIMIT 10;
```

✅ Expect `bman_tx_hash` beginning `DRYRUN-`, and `bman_ledger_id` **NULL** —
a dry run deliberately credits nothing.

Also confirm no Exchange balance moved:

```sql
SELECT COUNT(*) AS should_be_zero
FROM wallet_ledger
WHERE reference_type = 'admin_adjustment'
  AND tx_hash LIKE 'DRYRUN-%';
```

### 2.3 — Go live

Only after 2.2 looks right:

```sql
UPDATE member_bulk_upload_settings
   SET dry_run = 0
 WHERE id = 1;
```

⚠️ **From this moment the next cron pass broadcasts real BMAN on-chain.
On-chain transfers are irreversible.**

Start with a **small real batch** (2–3 members, small amounts) before running a
large file.

### 2.4 — Optional: a treasury floor guard

Refuses any send that would drop the treasury's on-chain BMAN below this:

```sql
UPDATE member_bulk_upload_settings
   SET min_treasury_reserve = 1000.00000000
 WHERE id = 1;
```

---

## 3. Emergency stop

```sql
-- hard stop: the cron refuses to send; the queue accumulates safely
UPDATE member_bulk_upload_settings SET enabled = 0 WHERE id = 1;

-- softer: keep the cron working the queue but broadcast nothing
UPDATE member_bulk_upload_settings SET dry_run = 1 WHERE id = 1;

-- stop only the internal Exchange credit, keep sending on-chain
UPDATE member_bulk_upload_settings SET credit_exchange_wallet = 0 WHERE id = 1;
```

Neither affects member creation — imported members keep working normally.

Also release a stuck run-lock (e.g. after a crashed pass; the cron self-clears
a lock older than 30 minutes anyway):

```sql
UPDATE wallet_settlement_cron_state
   SET running = 0, last_result = 'Lock released manually'
 WHERE job = 'member_bulk_bman';
```

---

## 4. Monitoring

### Cron health

```sql
SELECT job, running, heartbeat, last_run_at, last_result, total_settled
FROM wallet_settlement_cron_state
WHERE job = 'member_bulk_bman';
```

### Queue depth and delivery breakdown

```sql
SELECT bman_status, COUNT(*) AS rows_, SUM(bman_amount) AS bman
FROM member_bulk_upload_rows
GROUP BY bman_status;
```

### Per-batch audit (mirrors the admin page)

```sql
SELECT b.ref,
       b.original_name,
       b.status,
       b.total_rows,
       b.imported_rows,
       SUM(r.bman_status = 'pending')     AS queued,
       SUM(r.bman_status = 'completed')   AS sent,
       SUM(r.bman_status = 'failed')      AS failed,
       SUM(r.bman_ledger_id IS NOT NULL)  AS credited,
       SUM(CASE WHEN r.bman_status = 'completed' THEN r.bman_amount ELSE 0 END) AS bman_sent,
       MAX(r.bman_sent_at)                AS last_sent_at
FROM member_bulk_upload_batches b
LEFT JOIN member_bulk_upload_rows r ON r.batch_id = b.id
GROUP BY b.id, b.ref, b.original_name, b.status, b.total_rows, b.imported_rows
ORDER BY b.id DESC;
```

### Sends that landed on-chain but were never credited

These are what the cron's phase-1 backfill fixes automatically on its next
pass. A persistently non-empty result means the credit is failing — read
`bman_error`.

```sql
SELECT id, batch_id, user_id, bman_amount, bman_tx_hash, bman_sent_at, bman_error
FROM member_bulk_upload_rows
WHERE bman_status = 'completed'
  AND bman_ledger_id IS NULL
  AND bman_tx_hash IS NOT NULL
  AND bman_tx_hash NOT LIKE 'DRYRUN-%';
```

### Failed sends and why

```sql
SELECT r.id, b.ref, r.username, r.email, r.bman_amount,
       r.bman_attempts, r.bman_error
FROM member_bulk_upload_rows r
JOIN member_bulk_upload_batches b ON b.id = r.batch_id
WHERE r.bman_status = 'failed'
ORDER BY r.id DESC;
```

### Reconcile a member: sheet row → ledger → on-chain

```sql
SELECT r.username, r.email, r.wallet_address, r.bman_amount,
       r.bman_status, r.bman_tx_hash, r.bman_credited_at,
       l.id AS ledger_id, l.credit, l.balance_after, l.is_matured,
       w.exchange_balance
FROM member_bulk_upload_rows r
LEFT JOIN wallet_ledger l ON l.tx_hash = r.bman_tx_hash AND l.wallet_type = 'exchange'
LEFT JOIN user_wallets  w ON w.user_id = r.user_id
WHERE r.user_id = <USER_ID>;
```

---

## 5. Recovery

### Re-queue a failed send

A failed send is **terminal on purpose** — a money-moving queue should surface
failures rather than silently retry against a broken RPC or an empty treasury.
Fix the cause first, then:

```sql
UPDATE member_bulk_upload_rows
   SET bman_status = 'pending',
       bman_error  = NULL
 WHERE id = <ROW_ID>
   AND bman_status = 'failed'
   AND wallet_address IS NOT NULL;
```

(The **Re-queue** button on the batch detail page does exactly this.)

Re-queue every failed row of one batch:

```sql
UPDATE member_bulk_upload_rows
   SET bman_status = 'pending', bman_error = NULL
 WHERE batch_id = <BATCH_ID>
   AND bman_status = 'failed'
   AND wallet_address IS NOT NULL;
```

### Clear a row stuck in 'processing'

Only if you have confirmed the cron is not currently running (`running = 0` in
§4) **and** that no transaction was actually broadcast for it — check
`bman_tx_hash` first. If a hash is present, the money moved; set it to
`completed` instead and let the backfill credit it.

```sql
-- no hash recorded => nothing was broadcast => safe to re-queue
UPDATE member_bulk_upload_rows
   SET bman_status = 'pending'
 WHERE id = <ROW_ID>
   AND bman_status = 'processing'
   AND bman_tx_hash IS NULL;
```

---

## 6. Tuning

```sql
-- rows claimed per cron pass (also caps the backfill sweep)
UPDATE member_bulk_upload_settings SET max_batch_size = 50 WHERE id = 1;

-- guard against a runaway sheet
UPDATE member_bulk_upload_settings SET max_rows_per_file = 2000 WHERE id = 1;
```

Raising `max_rows_per_file` raises staging cost too: every valid row is
bcrypt-hashed during validation, so a 2000-row sheet spends real CPU time
before the preview appears.

---

## 7. Rollback

**Code:** revert the merge. Nothing else depends on this module and no shared
file's behaviour was changed.

**Schema:** the objects are additive and unreferenced by existing code — they
can simply be left in place. If they must go:

```sql
DROP TABLE `member_bulk_upload_rows`;
DROP TABLE `member_bulk_upload_batches`;
DROP TABLE `member_bulk_upload_settings`;
DELETE FROM `wallet_settlement_cron_state` WHERE `job` = 'member_bulk_bman';
```

⚠️ Do **not** drop `wallet_settlement_cron_state` itself — the pre-existing
wallet-transfer settlement cron uses it.

> **Data caveat.** Members already imported are ordinary members: reverting the
> code does **not** remove them, and any BMAN already broadcast is on-chain and
> irreversible. Dropping the tables above destroys the audit trail for those
> transfers — export each batch to `.xlsx` from the admin page first if you
> may ever need to reconcile them.

---

## 8. Quick reference

> ⚠️ Every statement below sets **both** switches. `enabled` is checked first
> and short-circuits, so `SET dry_run = 0` **on its own does nothing** — the
> cron still answers `"skipped"` — while quietly disarming the dry-run guard.
> Use the self-contained forms here.

| Intent | Statement |
|---|---|
| Check posture | `SELECT enabled, dry_run FROM member_bulk_upload_settings WHERE id = 1;` |
| Enable, still safe | `UPDATE member_bulk_upload_settings SET enabled = 1, dry_run = 1 WHERE id = 1;` |
| **Go live** (after a verified dry-run pass) | `UPDATE member_bulk_upload_settings SET enabled = 1, dry_run = 0 WHERE id = 1;` |
| Emergency stop | `UPDATE member_bulk_upload_settings SET enabled = 0 WHERE id = 1;` |
| Queue depth | `SELECT COUNT(*) FROM member_bulk_upload_rows WHERE bman_status = 'pending';` |
| Release stuck lock | `UPDATE wallet_settlement_cron_state SET running = 0 WHERE job = 'member_bulk_bman';` |

---

## 9. "The cron says skipped / processed 0" — troubleshooting

Work down this list; it is ordered by how often each one is the cause.

### `"status":"skipped"` … `enabled = 0`

The master switch is off. `enabled` is evaluated **before** `dry_run`, so no
amount of `dry_run` tinkering changes this message. Fix with the
"Enable, still safe" statement above.

### `"status":"success"` but `processed: 0`

The cron ran and found **nothing to send**. It only ever claims rows whose
`bman_status = 'pending'`. Check what actually exists:

```sql
SELECT bman_status, COUNT(*) FROM member_bulk_upload_rows GROUP BY bman_status;
```

- All `none` → **the batch was never imported.** Validating a file only stages
  it; the accounts and the queue are created when you press *Import valid rows*.
  Confirm with:
  ```sql
  SELECT ref, status, imported_rows FROM member_bulk_upload_batches ORDER BY id DESC;
  ```
  A `status` of `staged` with `imported_rows = 0` means nothing was created.
- All `completed` → already delivered. A previous pass (including a **dry run**)
  marked them done, and the idempotency guard stops a resend. To genuinely
  re-send after a dry run:
  ```sql
  UPDATE member_bulk_upload_rows
     SET bman_status = 'pending', bman_tx_hash = NULL, bman_ledger_id = NULL
   WHERE batch_id = <BATCH_ID> AND bman_tx_hash LIKE 'DRYRUN-%';
  ```
  ⚠️ Only ever match `DRYRUN-%` here. Clearing a real hash would re-broadcast
  BMAN that has already been sent.

### `"status":"skipped"` … "Another bulk BMAN run is in progress"

A previous pass crashed holding the lock. It self-clears after 30 minutes, or
release it manually with the statement in the quick reference.

### The batch page says "BMAN to send once imported"

That is a projection, not a queue. Nothing is pending until the batch is
imported.
