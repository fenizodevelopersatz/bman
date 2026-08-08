# Binary Level-Wise Matching — Implementation Record

Status: 🟢 **IMPLEMENTED — 15/15 acceptance tests pass.** Business rules locked
by the 2026-08-08 ruling: cumulative levels (Option B), MAX-package ceiling,
ceiling resets per level, no lifetime cap, 8/2 split kept, excess and unstaked
sponsors to Admin, Lock Wallet as the volume source, one payment per level ever.

Go-live switch: `Matchingqueue_model` line ~35 — it now loads
`staking/Binarylevelmatching_model`. Point it back at
`staking/Stakingmatching_model` to revert; that one line is the whole switch.

Derived strictly from the `binarymatchingrulesprobe` run (2 rules match, 11
mismatch findings) and the level-wise business spec. The engine is now LIVE in
code; nothing schedules it automatically on this machine — see §9 Rollout.

Related: [17_BINARY_MATCHING_PAYOUT_CRON.md](17_BINARY_MATCHING_PAYOUT_CRON.md) ·
[2026-08-06_binary_matching_distribution_review.md](2026-08-06_binary_matching_distribution_review.md) ·
[BINARY_CEILING_MANAGEMENT_DESIGN.md](BINARY_CEILING_MANAGEMENT_DESIGN.md)

---

## 0. Test results

### 0.0a FINAL — acceptance suite vs the NEW engine: **15 / 15 PASS**

`php index.php binarymatchingrulesprobe tests` — run repeatedly, stable, with a
before/after snapshot diff of 29 real-money counters showing zero drift.

| Test | Expected | Actual |
|---|---|---|
| 1 Unstaked sponsor → Admin | A=0, Admin=500 | A=0, Admin=500 · `sponsor_eligible=0`, forfeited not deferred ✅ |
| 2 Level 1 → 400/100 | earning=400, staking=100 | earning=400, staking=100 ✅ |
| 3 Completed level never repaid | 0 | 0 · B staked another 5,000 at level 1, correctly ignored ✅ |
| 4 L3 raw 6,000 → user 5,000 / admin 1,000 | raw=6000, user=5000, admin=1000 | identical · L1 500 + L2 2,500 + L3 6,000 paid as separate levels, `ceiling_wallet` untouched ✅ |
| 5 MAX package sets ceiling | 30,000 | 30,000 · highest eligible stake 100,000 (pkg #6); legacy would have summed to 65,000 ✅ |
| 6 / 7 / 8 Ceiling mapping | 30,000 / 30,000 / 50,000 | 30,000 / 30,000 / 50,000 — unambiguous ✅ |
| 9 Matured stake excluded | A paid=0 | A paid=0 · left leg has no eligible volume, level 1 never completes ✅ |
| 10 Run twice | adds 0 | adds 0 · `UNIQUE(user_id, level)` blocked the repeat **before** any credit ✅ |
| **11** Admin ceiling edit picked up immediately | 35,000 then original | 35,000 then original — written via `Staking_model::saveCeilings()`, the same call the admin screen uses ✅ |
| **12** Every package resolves to its OWN configured ceiling | 0 mismatches | 0 mismatches across 11 active packages; expectations read from the DB, **no literals in the test** ✅ |
| **13** Ambiguous config → level stays PENDING | level=1, payout_rows=0, paid=0, wallet=0, admin_delta=0 | identical — nothing paid, nothing forfeited, level still open ✅ |
| **14** Missing config → level stays PENDING | level=1, payout_rows=0, paid=0, wallet=0, admin_delta=0 | identical — no fallback ceiling substituted ✅ |
| **15** Fix config → the SAME level then pays | pending before fix, then 500 (400/100), admin_delta=0 | identical — level 1 survived the misconfiguration and paid in full ✅ |

Teardown verified every run: 0 synthetic rows, `admin_wallet` unchanged,
nothing escaped to real members, all 8 real volume rows restored, and every
`staking_packages` ceiling/is_active flag the ceiling tests mutate restored
exactly (asserted, not assumed — tests 11/13/14 edit real config rows).

### 0.0b Baseline — the same tests against the OLD engine (2 / 10)

Built and executed before any implementation, so these are measured, not
predicted: `php index.php binarymatchingrulesprobe tests`. Same guards as the
probe — aborts if a real member could be paid, parks real volume, shutdown-hook
teardown. Verified after: 0 synthetic rows left, all 8 real volume rows restored.

**2 passed, 8 failed.**

| Test | Expected | Actual (current engine) | |
|---|---|---|---|
| 1 Unstaked sponsor → Admin | A=0, Admin=500 | A=0, **Admin=0** — deferred, carry preserved | ❌ |
| 2 Level 1 = 500 → 400/100 | earning=400, staking=100 | earning=400, staking=100 | ✅ |
| 3 Completed level never repaid | 2nd payout = 0 | **500** — fresh level-1 volume re-pays | ❌ |
| 4 Raw 6,000 → user 5,000 / admin 1,000 | admin=1,000 | user=5,000 ✔ but **admin=0**; 1,000 → ceiling_wallet | ❌ |
| 5 Highest package sets ceiling | 30,000 | **65,000** (5,000+30,000+30,000 summed) | ❌ |
| 6 50,000 → 30,000 | 30,000 | **AMBIGUOUS(30,000 \| 50,000)** | ❌ |
| 7 100,000 → 30,000 | 30,000 | **AMBIGUOUS(30,000 \| 100,000)** | ❌ |
| 8 200,000 → 50,000 | 50,000 | **AMBIGUOUS(50,000 \| 200,000)** | ❌ |
| 9 Matured stake excluded | A paid=0 | **500** — purchase-time volume still counts | ❌ |
| 10 Run twice, no duplicate | 2nd run adds 0 | 2nd run adds 0 — *but only because carry was consumed, not because a level is recorded* | ✅ |

This suite is the acceptance gate: the new engine must turn all ten green, with
2 and 10 staying green (no regression).

### 0.0c ⚠️ Incident during testing — harness leaked outside its sandbox (fixed)

Recorded because it is the sharpest lesson in this whole change. On the first
post-migration test run, `_runEngine()` called `run()` **unscoped**. The new
engine's `_candidateSponsors()` sweeps *every* sponsor in `binary_placement`,
so it evaluated real members: three were credited real internal-wallet BMAN
(999999602 0.10, 999999606 0.20, 999999608 0.70) and two more (1, 999999504)
had level 1 consumed with 0.30 of admin overflow.

The old `_preflight()` guard could not catch it — it checks
`binary_carry` for qualifying members, and **the level engine never reads
carry**. A guard written for the old engine silently stopped protecting
anything the moment the engine changed.

The worst part was not the 1.00 BMAN. `UNIQUE(user_id, level)` is permanent, so
sponsors 1 and 999999504 could never have been paid level 1 again.

Nothing reached the chain (`BinaryMatchingPayoutCron` was never run; the payout
queue stayed at its one pre-existing row). Fully reversed by deriving the
amounts from the rows themselves (`run_ref LIKE 'PT-%'`), restoring balances,
deleting the probe payouts and admin ledger rows, and resetting `admin_wallet`
to 0 — 999999608's legitimate 2026-08-07 payout (0.16/0.04) preserved
untouched. Verified back to exact pre-work state.

**A second, quieter residue was found during the final pre-production
verification** (and is why that verification was worth doing): every
`Walletledger_model::credit()` call also mirrors the movement into
`onchain_transactions` — plus an `onchain_tx_events` audit row — via
`_captureOnchain()`. That fail-safe fires for synthetic credits exactly as it
does for real ones, and the probe's delete-by-`user_id` sweep never covered
those two tables. 416 mirror rows had accumulated (410 synthetic + the 6 from
the escape above). Removed by run_ref prefix, events first; total
`onchain_transactions` returned to its true pre-probe baseline of 269. Note the
74 orphaned `onchain_tx_events` rows in this database are unrelated —
they date from 2026-07-29 to 08-06, before this work.

Four fixes so it cannot recur:
1. `run()` accepts `user_ids` — an explicit whitelist. The harness always
   passes its synthetic set, so a sandbox escape is structurally impossible.
2. Teardown restores `admin_wallet.balance` to its opening value and deletes
   synthetic `admin_wallet_ledger` rows — the singleton has no `user_id`, so
   the delete-by-user sweep never covered it.
3. Teardown now actively hunts escapes: any `PT-`/`PROBE-` run_ref on a
   non-synthetic member fails the run loudly.
4. Cleanup covers `onchain_transactions` + `onchain_tx_events`, matched by
   run_ref prefix rather than user_id — so it also sweeps mirror rows written
   against a real member if a scope guard ever fails again.

**Verification method that caught it:** a 26-counter snapshot of every
real-money table taken immediately before and after a full suite run, then
diffed. Anything the harness fails to clean shows up as drift. Repeat this
before enabling the cron in production — the snapshot lives at
`docs/` history / the plan, and the check is simply "the diff must be empty".

### 0.1 ✅ RESOLVED — §10 vs §11: Option B (cumulative) ruled by the user 2026-08-08

You instructed: *"Do NOT silently change the calculation. Keep the formula
MIN(left,right) × 10%. Flag the discrepancy."* Flagging it — the discrepancy is
**not** 2,500-vs-2,000. It is that **§10 and §11 cannot both be satisfied by any
single formula**, because they sum different node sets.

| | §10 — level-N nodes only | §11 — cumulative levels 1..N |
|---|---|---|
| Level 2 left | `D+E = 20,000` ✔ **your §10 number** | `B+D+E = 25,000` |
| Level 3 left | `H+I+J+K = 35,000` | `B+D+E+H+I+J+K = 60,000` ✔ **your §11 number** |

`60,000` is arithmetically **only** reachable by including B, D and E — i.e.
cumulative. `20,000` is **only** reachable by excluding B — i.e. level-only.

**What each choice does to your own headline example:**

| | Option A — level-only (§10) | Option B — cumulative (§11) |
|---|---|---|
| Level 1 | 5,000 → **500** | 5,000 → **500** |
| Level 2 | 20,000 → **2,000** ✔§10 | 25,000 → **2,500** |
| Level 3 | 35,000 → **3,500** | 60,000 → **6,000** ✔§11 |
| A receives | 6,000 | 8,000 |
| Admin overflow | **0 — never triggers** | **1,000** ✔§8, §11 |
| Your TEST 4 (`raw 6000 / user 5000 / admin 1000`) | ❌ impossible | ✅ passes |

Under Option A the 5,000 ceiling is never reached in this tree, so §8's, §11's
and **your own TEST 4's** "A = 5,000, Admin = 1,000" can never occur.

**Recommendation: Option B (cumulative)** — it satisfies §8, §11 and TEST 4,
where Option A satisfies only §10. This is a money decision, so **nothing is
implemented until you rule.**

If Option B, one consequence must be stated plainly: the same downline volume
is paid on again at every deeper level. B's 5,000 earns at level 1, again
inside level 2, and again inside level 3.

> Minor, immaterial: your §11 right-hand sum writes L as 20,000 (total 115,000)
> but the tree labels L as 200,000 (total 295,000). `MIN` picks the 60,000 left
> leg either way. The plan uses the tree label.

### 0.2 A direct contradiction in the ceiling rule — needs your ruling

| Source | Two 5,000 packages ⇒ ceiling |
|---|---|
| Numbered rule #7: *"HIGHEST eligible staking package, not SUM"* | **5,000** |
| Day-by-day list: *"1st day 5,000 = ceiling 5,000; 2nd day 5,000 = ceiling 5,000"* | **5,000** |
| A(10000) tree narrative: *"A is staking day2 one 5000 again … so now user A is combine 5000+5000 = 10000 Bman limit"* | **10,000** |

Two of three say MAX; the tree narrative says SUM. **This plan implements MAX**
(rule #7 is explicit and repeated). Say the word if the narrative was the
intent instead.

> Also a wording slip, not treated as a conflict: *"3rd day 100000 = ceiling
> 100000"*. Your mapping table — restated in rule #8 — says 100,000 → **30,000**.
> The table is treated as authoritative.

### 0.3 ⚠️ Per-level ceiling has no lifetime bound — the biggest financial risk

You confirmed the ceiling **resets for every completed level**. Your example
stops at level 3, so the total is bounded at 8,000 for a 5,000-package member.
A real tree does not stop at level 3:

```
Lifetime payout to one sponsor  =  ceiling × number of levels ever completed
5,000 ceiling × 10 completed levels  =  50,000 BMAN to a member who staked 5,000
```

Nothing in the spec caps this. Under the current live engine the same member
can never exceed 5,000 total, so this is the single largest money-shape change
in the whole redesign. **Recommend adding one of:** a lifetime ceiling multiple
(e.g. `N ×` package ceiling), a maximum payable level, or an explicit "no cap —
intended" sign-off. This plan ships a `max_level` + `lifetime_cap_multiple`
setting defaulting to *unlimited* so the rule is enforceable without a code
change, but the value is yours to set.

---

## 1. Current flow (verified by probe, not assumed)

```
purchase ──► binary_volume_ledger (bv = stake_amount, written ONCE, never re-read)
                     │
              propagate()  walks binary_placement upward
                     ▼
         binary_carry.left_carry / right_carry   (reducible)
         staking_group_volume                    (cumulative, rank/display)
                     │
              payMatching()  ── one pass, EVERY ancestor at once, no levels
                     ▼
   match = MIN(left_carry, right_carry)      ceiling = SUM(group_ceiling) lifetime
   bonus = 10%  →  8% earning / 2% staking   excess  → ceiling_wallet (user escrow)
   carry -= match   (this is the ONLY thing preventing a repeat payout)
                     ▼
   staking_matching_payouts ──► BinaryMatchingPayoutCron phases b/c/d ──► on-chain
```

Probe evidence: 7 sponsors paid in one pass; A matched 60,000 on its first-ever
payout; excess 1,000 went to `ceiling_wallet`, admin balance moved 0; unstaked
sponsor Z kept its carry (deferred, not forfeited); `userCeiling()` summed
5,000+100,000+50,000 → 65,000.

## 2. Required flow

```
FOR each sponsor  (deterministic order, under the existing GET_LOCK)
     │
     ├─ nextLevel = MAX(level already paid) + 1          ← strict ordering
     │
     ├─ level complete?  (eligible node on BOTH sides at depth = nextLevel)
     │        no ──► stop this sponsor
     │
     ├─ leftVol  = Σ Lock Wallet BMAN of ALL left-leg descendants, depth 1..N
     │  rightVol = Σ Lock Wallet BMAN of ALL right-leg descendants, depth 1..N
     │             (Lock Wallet = user_stakes status active|processing
     │                            AND maturity_date > today)
     │
     ├─ matched  = MIN(leftVol, rightVol)
     │  rawBonus = matched × matching_total_percent (10%)
     │
     ├─ sponsor has eligible staking?
     │        NO  ──► user 0, admin = rawBonus                (never deferred)
     │        YES ──► ceiling = mapping[ MAX(eligible package amount) ]
     │                user  = MIN(rawBonus, ceiling)          ← FRESH each level
     │                admin = rawBonus − user
     │
     ├─ BEGIN TRANSACTION
     │     INSERT staking_matching_payouts (user_id, level, …)   ← UNIQUE(user_id,level)
     │     credit Earning  = user × 80%      via Walletledger_model
     │     credit Staking  = user × 20%      via Walletledger_model
     │     admin > 0 → UPDATE admin_wallet.balance + INSERT admin_wallet_ledger
     │  COMMIT                                                  ← level now "completed"
     │
     └─ repeat for nextLevel+1 while that level is also already complete
                     ▼
   unchanged: BinaryMatchingPayoutCron phases b/c/d enqueue → gas precheck →
              broadcast → confirm → admin retry   (member payouts only)
```

## 3. Database changes

**One table altered. One dormant table activated. No new tables.**

### 3.1 `staking_matching_payouts` — becomes the level-completion record

The audit row *is* the level lock. Adding a separate state table would create
two things that must agree; one row cannot disagree with itself.

```sql
ALTER TABLE staking_matching_payouts
  ADD COLUMN level              SMALLINT UNSIGNED NULL AFTER user_id,
  ADD COLUMN raw_bonus          DECIMAL(20,4) NOT NULL DEFAULT 0 AFTER total_percent,
  ADD COLUMN ceiling_applied    DECIMAL(20,4) NOT NULL DEFAULT 0 AFTER staking_amount,
  ADD COLUMN admin_overflow     DECIMAL(20,4) NOT NULL DEFAULT 0 AFTER ceiling_applied,
  ADD COLUMN highest_package_id INT NULL AFTER admin_overflow,
  ADD COLUMN sponsor_eligible   TINYINT(1) NOT NULL DEFAULT 1 AFTER highest_package_id,
  ADD UNIQUE KEY uq_user_level (user_id, level);
```

Every §15 audit field is then present: sponsor, level, `left_before`/
`right_before` (leg volumes), `matched_volume`, `raw_bonus`, paid split,
`ceiling_applied`, `admin_overflow`, `highest_package_id`, `run_ref`,
`created_at`.

`level` is NULL on the one pre-existing legacy row; MySQL/MariaDB permit
multiple NULLs in a UNIQUE index, so legacy rows neither block nor are blocked.

### 3.2 `admin_wallet_ledger` — activated, not created

Already exists with exactly the needed shape (`credit`, `debit`,
`balance_after`, `reference_type`, `reference_user_id`, `description`,
`created_at`) — **0 rows, 0 code references anywhere.** It was built and never
wired up. Admin overflow becomes its first writer.

⚠️ **`admin_wallet` is EMPTY — it has no `id = 1` row.** `Bonusreduction_model`
credits the admin with `UPDATE admin_wallet … WHERE id = 1`, which currently
matches **zero rows**, so that credit is a silent no-op today. This is a
pre-existing bug in a different module; I am not fixing it here (out of scope),
but the new engine must not inherit it. The migration seeds the singleton, and
the engine uses an upsert:

```sql
INSERT INTO admin_wallet (id, balance) VALUES (1, 0)
  ON DUPLICATE KEY UPDATE id = id;   -- seed once, never clobber a live balance
```

### 3.3 Ceiling data fix (rule #8)

Verified: ids 5/6/7 **already** carry your exact mapping (50k→30k, 100k→30k,
200k→50k). Ids 45/46/47 are duplicate rows whose ceiling equals the stake
amount, and **no `user_stakes` row references any of them** (0 stakes each) —
so deactivating is lossless and reversible, where editing them would leave two
identical active packages competing in the purchase UI.

```sql
UPDATE staking_packages SET is_active = 0
 WHERE id IN (45,46,47) AND NOT EXISTS (SELECT 1 FROM user_stakes WHERE package_id = staking_packages.id);
```

Resulting unambiguous mapping — matches your table exactly:

| Stake | Ceiling | Package id |
|---:|---:|---|
| 5,000 | 5,000 | 40 |
| 10,000 | 10,000 | 2 |
| 20,000 | 20,000 | 3 |
| 25,000 | 25,000 | 4 |
| 50,000 | **30,000** | 5 |
| 100,000 | **30,000** | 6 |
| 200,000 | **50,000** | 7 |
| 300,000 | 70,000 | 8 |
| 500,000 | 100,000 | 9 |

> The 1 BMAN (id 1) and 2 BMAN (id 44) test packages are outside your mapping
> and hold all 30 live stakes. They keep ceiling = stake amount. Flagging only
> so it is a decision, not an oversight.

## 4. Exact files and functions

| File | Change |
|---|---|
| `application/models/staking/Binarylevelmatching_model.php` | **NEW.** The level engine: `run()`, `nextLevel()`, `levelComplete()`, `legVolume()`, `sponsorCeiling()`, `payLevel()`. |
| `application/models/staking/Stakingmatching_model.php` | `payMatching()` **retired from the cron path** (kept, unreferenced, for the historical rows it wrote). `propagate()` **unchanged and still called** — `staking_group_volume`/`binary_carry` still feed dashboards & the genealogy page. `userCeiling()` unchanged (read by 3 admin pages); the new MAX-based ceiling is a new method, so no existing reader changes meaning silently. |
| `application/models/staking/Matchingqueue_model.php` | One line: call the new engine instead of `Stakingmatching_model::run()`. Keeps `GET_LOCK`, `binary_matching_queue`, `run_ref`, retry/attempts as-is. |
| `application/controllers/BinaryMatchingPayoutCron.php` | **No change.** Phases b/c/d already key off `staking_matching_payouts` and skip rows where `earning+staking = 0`, so admin-only levels correctly generate no on-chain send. |
| `db/binary_level_matching.sql` | **NEW.** §3.1 + §3.2 + §3.3, idempotent, in this repo's existing procedure-guard style. |
| `application/controllers/Binarymatchingrulesprobe.php` + its model | Re-run after implementation to prove all 11 mismatches close. |

Explicitly untouched: ROI, staking purchase, Lock Wallet creation, rank system,
wallet ledger, every other cron.

**Volume query.** MariaDB 10.4.32 confirmed — `WITH RECURSIVE` over
`binary_placement`, seeded from the sponsor's two direct children, carrying the
seed's side and a depth counter, joined to `user_stakes` for the Lock Wallet
sum. One set-based query per sponsor, not N per-node calls.

## 5. Exact calculations for this tree

A's ceiling = 5,000 (own package 5,000). B = 5,000. C = 10,000.

### Sponsor A

| Level | Left leg (cumulative Lock Wallet) | Right leg | MIN | ×10% | Ceiling | → A | → Admin |
|---|---|---|---:|---:|---:|---:|---:|
| 1 | B 5,000 | C 10,000 | 5,000 | **500** | 5,000 | 500 | 0 |
| 2 | B+D+E = 25,000 | C+F+G = 30,000 | 25,000 | **2,500** | 5,000 | 2,500 | 0 |
| 3 | B+D+E+H+I+J+K = 60,000 | C+F+G+L+M+N+O = 295,000 | 60,000 | **6,000** | 5,000 | **5,000** | **1,000** |

A totals **8,000** to member, **1,000** to Admin — matching your second scenario
line for line. Split per level: L1 400/100, L2 2,000/500, L3 4,000/1,000
(earning/staking).

### Sponsor B (ceiling 5,000)

| Level | Left | Right | MIN | ×10% | → B |
|---|---|---|---:|---:|---:|
| 1 | D 10,000 | E 10,000 | 10,000 | 1,000 | 1,000 |
| 2 | D+H+I = 20,000 | E+J+K = 35,000 | 20,000 | 2,000 | 2,000 |
| 3 | — no depth-3 descendants → level never completes | | | | 0 |

### Sponsor C (ceiling 10,000)

| Level | Left | Right | MIN | ×10% | → C |
|---|---|---|---:|---:|---:|
| 1 | F 10,000 | G 10,000 | 10,000 | 1,000 | 1,000 |
| 2 | F+L+M = 220,000 | G+N+O = 65,000 | 65,000 | 6,500 | 6,500 |
| 3 | — none | | | | 0 |

### Whole-tree effect vs today

| | Members paid | Admin | Ceiling wallet |
|---|---:|---:|---:|
| Current engine (probe, run 1) | 16,000 | 0 | 1,000 |
| Proposed level-wise | **21,000** | **1,000** | 0 |

(D 500, E 500, F 1,000, G 500 are identical under both.) The +5,000 is A
collecting three stacked levels instead of one capped lifetime payout.

## 5b. Ceiling configuration — single dynamic source

There is exactly ONE ceiling configuration in this system, and the engine
consumes it live on every call. Nothing is duplicated, cached or hard-coded.

```
Admin ▸ Staking ▸ Rank Power   (Group Incentive Ceiling editor, §12)
        └─ Staking_model::saveCeilings()
Admin ▸ Staking ▸ Packages     ("Group ceiling" field per package)
        └─ Packages.php:58 → Staking_model::savePackage()
                    │
                    ▼
        staking_packages.group_ceiling      ← the only store
                    │
                    ▼
   Binarylevelmatching_model::sponsorCeiling()   ← re-read every level
```

`grep -nE "30000|50000|100000|…" Binarylevelmatching_model.php` → **no matches.**
The engine contains no ceiling or stake literal of any kind.

**Two concepts, never conflated:** `stake_amount` identifies *which* ceiling
config to look up; `group_ceiling` *is* the cap. A 50,000 package does not
imply a 50,000 ceiling — it carries whatever the admin configured.

**Fails closed, never guesses.** `sponsorCeiling()` returns a `status`:

| status | meaning | outcome |
|---|---|---|
| `ok` | exactly one positive configured ceiling | paid, capped normally, level closed |
| `no_stake` | sponsor holds no eligible package | 0 → all to Admin, level **closed** (real business outcome) |
| `config_missing` | ceiling NULL or ≤ 0 | **SKIP & RETRY** — level stays open |
| `config_ambiguous` | several eligible packages tied at the highest stake amount carry *different* ceilings | **SKIP & RETRY** — level stays open |

The previous `ORDER BY sp.group_ceiling DESC LIMIT 1` silently resolved
ambiguity by taking the largest — a guess. It is gone.

### Config error = SKIP & RETRY (ruled 2026-08-08)

A missing or ambiguous ceiling is an **admin/system fault, never the member's**,
so it must not cost them the level. On a config error the engine does
**nothing at all** — and returns *before* `trans_begin()`, so not even a
rollback-only transaction locks the money tables:

- no payout row, so `UNIQUE(user_id, level)` stays free and the level is still
  "next" on the following run;
- no wallet credit, no admin overflow, no volume touched;
- an error is logged with sponsor_id, level, status, highest stake, package id,
  detail, and the unpaid raw bonus;
- `processSponsor()` stops at that level — levels are strictly ordered, so a
  pending level correctly blocks the deeper ones until an admin resolves it;
- once the ceiling is fixed, the very next cron re-evaluates **that same level**
  and pays it in full (proved by TEST 15).

**Visibility:** `run()` returns `deferred_levels` and a `deferred_detail`
sample, so a stalled configuration shows up in the cron result, Cron Lab output
and `cron_execution_log` rather than silently freezing a member's matching.

Contrast `no_stake`, which is deliberately NOT retryable: the sponsor genuinely
held no eligible package when the level completed, so by the agreed business
rule the bonus is forfeited to Admin and the level closes.

## 6. Admin overflow ledgering

Mirrors exactly what `Bonusreduction_model` already does for admin credits, so
there is one convention in the codebase, not two:

```php
UPDATE admin_wallet SET balance = balance + ?, updated_at = NOW() WHERE id = 1;

INSERT INTO admin_wallet_ledger
  (credit, debit, balance_after, reference_type, reference_user_id, description, created_at)
VALUES
  (:overflow, 0, :new_balance, 'binary_matching_overflow', :sponsor_id,
   'Level 3 overflow — raw 6000, ceiling 5000 (pkg 5,000), sponsor SATZ123', NOW());
```

Both statements sit **inside the same transaction** as the member credits and
the `staking_matching_payouts` insert — §16's requirement that one wallet is
never credited while the other is left unpaid.

Two deliberate calls:

- **No on-chain send for the admin share.** Member payouts leave the treasury;
  the admin share simply never leaves it, so a treasury→treasury transfer would
  burn real gas to move nothing. The obligation is recorded in
  `admin_wallet_ledger`; add a sweep later if you want it on-chain.
- **`ceiling_wallet` stops receiving new matching holds.** The module, its admin
  page, and any existing held balance stay exactly as they are so an admin can
  still release what is already there.

## 7. Idempotency

Four layers, strongest first:

1. **`UNIQUE(user_id, level)`** — the database physically cannot hold two
   payouts for the same sponsor+level. This is the guarantee; everything else
   is optimisation.
2. **Insert-first ordering** — the level row is inserted *before* any wallet
   credit. A duplicate run hits the unique violation and aborts that level
   before a single coin moves. (The current engine credits first and relies on
   carry subtraction — which is why the probe showed A paid twice across runs.)
3. **`GET_LOCK('binary_matching_engine')`** — unchanged; two ticks or an admin
   "Run now" can never overlap.
4. **On-chain leg unchanged** — `blockchain_payout_queue.payout_ref` is already
   `UNIQUE`, and enqueue is a `NOT EXISTS` scan against
   `staking_matching_payouts.id`, so one level = at most one broadcast.

Re-running the cron 100× with no new purchases produces: 0 new payouts, 0 new
credits, 0 new admin overflow rows, 0 new queue rows.

## 8. Open decisions

| # | Question | Recommendation |
|---|---|---|
| 1 | §0.2 — ceiling from MAX package or SUM? | **MAX** (rule #7) |
| 2 | §0.3 — lifetime bound on `ceiling × levels`? | Needs your number; ships as unlimited otherwise |
| 3 | "Level complete" = eligible node on both sides at that depth, or all 2ᴺ positions filled and staked? | **Both-sides-have-volume.** Strict 2ᴺ would permanently stall most real trees, since auto-placement fills left-first |
| 4 | If levels 1–3 are all already complete on the first run, pay all three in that one run (in order), or one level per run? | **All complete levels in one run, ascending.** Ordering is preserved; throttling would only delay money already earned |
| 5 | Existing test residue (1 paid payout, 7 carry rows, 8 unpropagated volume rows) | Reset before go-live so level 1 is genuinely level 1 |
| 6 | `binary_carry` keeps accumulating but is no longer consumed → the admin Genealogy Tree's carry figures drift upward forever | Follow-up task to repoint that page at level state; not bundled here |

## 9. Rollout

1. Apply `db/binary_level_matching.sql` (schema + package deactivation).
2. Ship the new engine with `Matchingqueue_model` still pointing at the OLD one
   — nothing changes behaviour yet.
3. Run `binarymatchingrulesprobe` against the new engine in isolation; expect
   all 11 mismatches to close.
4. Reset test residue (decision 5).
5. Flip `Matchingqueue_model` to the new engine — the single go-live switch,
   and the single line to revert.
6. First live run with `swap_dry_run = 1`, verify `staking_matching_payouts` +
   `admin_wallet_ledger`, then go live.
