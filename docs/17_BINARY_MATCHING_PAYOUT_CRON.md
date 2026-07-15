# 17 — Binary Matching Payout Cron (on-chain, ceiling-restricted)

Status: 🟢 **Implemented & tested (dry-run).** Schedules the existing binary
matching engine (`Stakingmatching_model` — tree-walk, `min(left,right)×10%`,
per-user ceiling cap) and takes every payout it produces **on-chain**, gated on
a treasury balance precheck, with admin-operable retry per transfer. §7 adds
one small, deliberate fix to the engine itself (recipient eligibility) — see
that section for why it was no longer "don't touch."

Links: [6_STAKING_PACKAGES_PLANS_ROI.md](6_STAKING_PACKAGES_PLANS_ROI.md) §9 ·
[0_INDEX.md](0_INDEX.md) · [3_CHANGELOG.md](3_CHANGELOG.md).

⚠️ **Before relying on this in production:** no real binary match has ever
been paid on this system — `binary_volume_ledger` (the engine's input) is
currently empty for every real user, so every table downstream is empty too.
See the audit report referenced in the changelog entry for full evidence
before assuming a real payout will happen automatically.

---

## 1. What's unchanged vs. what's new

**Unchanged:** the core shape of `Stakingmatching_model.php` —
`propagate()` walks every purchase's BV up the sponsor chain into every
ancestor's leg (`binary_carry`, reducible); `payMatching()` matches
`min(left,right)`, splits 10% into 8% Earning / 2% Staking
(`staking_bonus_settings`), caps each recipient at **their own** active
`user_stakes → staking_packages.group_ceiling`, diverts the excess to the
per-user backend **Ceiling Wallet** (`Ceilingwallet_model`, unchanged from
Phase 1), and logs one audit row per payout to `staking_matching_payouts`.
**One thing changed** in this same file — see §7 (recipient eligibility).

**New this phase:** nothing above ever ran on a schedule, and every payout was
only ever an internal ledger credit — never a real blockchain transfer,
despite the spec's requirement for on-chain transfers gated on a treasury
gas/token balance precheck with per-transfer retry. This doc covers that
wrapper.

## 2. The cron — 4 phases per run

`BinaryMatchingPayoutCron::run()` (root controller, token-gated like
`StakingPurchasecron`):

1. **Run the engine** — `Matchingqueue_model::runClaimed()`: claims or mints a
   `binary_matching_queue` row (MySQL `GET_LOCK`-guarded so two ticks can
   never process the same carry concurrently), runs
   `Stakingmatching_model::run(['run_ref' => ...])`, marks `DONE`/`FAILED`.
2. **Enqueue payouts** — scans `staking_matching_payouts` for any row not yet
   linked to a `blockchain_payout_queue` entry (idempotent `NOT EXISTS`, not
   just this run's in-memory result — safe across a crash between steps 1/2),
   enqueues **one combined** on-chain BMAN send per user (earning + staking
   together — one custodial address per user, same shape as a stake purchase's
   single on-chain transfer with an internal wallet split).
3. **Drain the queue** — one treasury BNB(gas)+BMAN balance snapshot per
   batch, broadcasts oldest-first, **stops the whole batch** at the first row
   the treasury can't afford (FIFO fairness — never skips ahead to a smaller
   later row), and honors `token_settings.swap_dry_run` for safe simulation.
4. **Confirm** — polls `PROCESSING` rows for confirmations (same algorithm as
   `StakingPurchasecron`), marks `CONFIRMED` (+ an `onchain_transactions`
   audit row) or `FAILED` (reverted, clears `tx_hash` so retry rebroadcasts).

## 3. Running it

```
CLI (one pass) : php index.php BinaryMatchingPayoutCron run
CLI (watch)    : php index.php BinaryMatchingPayoutCron run watch
HTTP           : /binary-matching-payout-cron?token=YOUR_CRON_TOKEN
Cron Lab       : admin/wallet/cron-lab → "Binary Matching Payout (Engine + On-Chain)"
```

**Watch mode** (`run watch`, CLI only) polls every 5s for up to 10 minutes
instead of exiting after one pass — useful for interactively watching a real
match carry through enqueue → broadcast → confirmations without re-invoking
the cron by hand each time. Not the default, for the same reason
`StakingPurchasecron`'s watch mode isn't: looping inside a scheduled tick
would stack overlapping long-running processes. It stops as soon as
`_countInFlight()` (queued engine runs + unlinked payouts + in-flight
broadcasts) reaches zero, or after the timeout.

**Recommended schedule:** every 5 minutes (`*/5 * * * * php index.php
BinaryMatchingPayoutCron run`). Unlike purchase settlement, nothing here is
time-sensitive — carry accumulates continuously regardless of cadence.

## 4. Admin screens (the "admin history" surface)

| Screen | Route | Shows |
|---|---|---|
| **Binary Matching History** | `admin/staking/matching-history` | KPI cards (matched volume / earning paid / staking paid / ceiling diverted); **Recent Engine Runs** table — first-ever UI for `binary_matching_queue` (run_ref, status, paid users/matched/earning/staking parsed from `result_json`), plus a **Run Matching Now** button; **Payout History** table joining `staking_matching_payouts` → `users` → `blockchain_payout_queue` (one row per level-wise payout, with its on-chain status and tx hash inline). |
| **Binary Matching Payout Queue** | `admin/staking/payout-queue` | `blockchain_payout_queue` browser — status/amount/to-address/tx-hash/confirmations/retry-count/last-error, status filter, and a **Retry** button (only enabled on `FAILED`/`RETRY` rows). |
| **Ceiling Wallet** (Phase 1) | `admin/staking/ceiling-wallet` | Per-user held balance + ledger (hold/release/adjust) — the excess-over-ceiling side. |
| **Cron Lab** | `admin/wallet/cron-lab` | Manual "Run now" button for every cron incl. this one, via the same token-gated HTTP route the real schedule uses — response shown inline as raw JSON. |

Retry (`Blockchainpayout_model::retry()`) only resets a `FAILED`/`RETRY` row's
on-chain state — it never re-credits any wallet, since the internal credit
already happened synchronously inside `payMatching()` before the payout row
even existed.

## 5. Data

No schema changes — `binary_matching_queue` and `blockchain_payout_queue`
(`db/backend_queues.sql`) already had every column this needed; they existed
unused before this phase. `blockchain_payout_queue.reference_type/reference_id`
carries `('staking_matching_payout', <staking_matching_payouts.id>)` for the
idempotent enqueue join — naturally 1:1 since each payout row produces at most
one on-chain send.

State machine: `PENDING → PROCESSING → CONFIRMED` (happy path) or `→ RETRY`
(balance shortfall — doesn't burn `retry_count`, not the row's fault) / `→
FAILED` (broadcast error, burns `retry_count`, terminal at `max_retries`).
Admin retry: `FAILED`/`RETRY` → `PENDING`, full reset.

## 7. Recipient eligibility — a real gap found and fixed

`payMatching()` originally checked only `users.status='1'` (account not
banned) before paying a match — it never required the **recipient** to have
staked anything themselves. Combined with `userCeiling()` returning `0` for
"never staked" (previously treated as "no cap"), a user with zero active
stakes but two funded legs would have received a full, **uncapped** matching
bonus. Fixed with one gate, right after the status check:

```php
$ceiling = $this->userCeiling($uid);
if ($ceiling <= 0) continue; // no active stake of their own -> not yet eligible
```

Skipped, not failed: the carry is **not** consumed (the `continue` runs before
the subtraction step), so once that user does stake, the very next run pays
them for everything that accumulated while they were unstaked — nothing is
lost. Verified against two real, pre-existing accounts (not synthetic):
Admin (never staked, ceiling 0) correctly skipped; Siva (4 active 1-BMAN
stakes, ceiling 4) correctly paid. Test: `php index.php
stakingmatchingeligibilitytest run` (7/7).

## 8. Level cascading — verified, no explicit "level order" needed

A natural follow-up question: does paying a lower ancestor (say B) need to
happen *before* a higher one (A) can be correctly evaluated? **No** — each
ancestor's carry already accumulates independently, because `_walkUp()` adds
every purchase's BV to **every** ancestor's leg in the same pass, not just the
immediate parent. `payMatching()`'s single SQL scan then evaluates every
qualifying `binary_carry` row in one call, in whatever order the query returns
them — there is no "level order" to get wrong.

Proven with the exact tree from a real support scenario:

```
           A (no own stake)
         /                  \
        B (no own stake)     C (10,000 BMAN own stake)
       /        \           /        \
   D1(10000) E1(10000)  D2(10000)  E2(10000)
```

One `payMatching()` run: **C** is paid (has an own stake) — matched 10,000,
800 Earning + 200 Staking. **B** is skipped (own subtree matches perfectly —
10,000/10,000 — but B has no own stake) — carry preserved at 10,000/10,000,
not consumed. **A** is also skipped (no own stake) — but its carry already
correctly shows **20,000** left (D1+E1, walked up *through* B regardless of
B's own stake status) and **30,000** right (C's own stake + D2 + E2, walked up
through C) — a hypothetical match of 20,000, computed with zero extra steps.

A second run, after B alone stakes: B is paid **10,000 matched**, from the
*exact same* carry preserved since the first run — nothing was recomputed. A
third run, after A also stakes: A is paid **20,000 matched**, same story.
Test: `php index.php levelcascadetest run` (17/17), fully synthetic, self-cleaning.

**One clarification for your original phrasing:** "A is not eligible because
B hasn't staked" isn't quite the mechanism — A's eligibility depends **only**
on A's own stake, never on B's. In this scenario A happens to also have no
stake, so A is skipped for its own, independent reason; if A had staked but B
still hadn't, A would still have been paid in full (20,000 matched), since
volume propagation through B is never gated by B's own stake status — only
the final *payment* to whichever ancestor is being evaluated is gated on
*that* ancestor's own stake.

## 9. Admin Genealogy Tree (any member)

`admin/staking/genealogy-tree` — an admin-only interactive tree, purpose-built
for verifying the matching engine rather than mirroring the member-facing
`user/genealogy` page. Every node shows **`binary_carry.left_carry`/
`right_carry`** directly — the actual figures `payMatching()` reads — plus
each member's own stake, ceiling (remaining/total), Ceiling Wallet held
balance, and an Eligible/Needs-Stake badge. Search any member by username,
UID, or numeric id to re-root the view there (no "own downline only"
restriction — an admin can inspect anyone). Confirmed distinct from the
member tree's numbers, which come from `user_wallets.exchange_balance`
subtree sums (see §1 of the audit) — the two are different figures on
purpose, and this screen exists specifically so an admin doesn't have to
guess which one reflects what the engine will actually do.

A second, older "View Tree" already existed
(`admin/member/Membermanagement::genealogy`/`getTreeData`, route
`user-genealogy/{id}`) using a third-party Balkan FamilyTree widget — found to
be broken (its data feed keys the parent link as `mid`, but the widget reads
`pid`, and `position` arrives as a formatted string like `"Left ( $1,234.56 )"`
that its `=== "Left"` checks never match) — likely renders as a flat pile of
disconnected, uncolored cards. Left untouched (pre-existing, unrelated to this
work); the new screen is additive, not a replacement.

## 10. Files

**New (this session, on-chain payout cron):**
`application/models/staking/Matchingqueue_model.php`,
`application/models/staking/Blockchainpayout_model.php`,
`application/controllers/BinaryMatchingPayoutCron.php`,
`application/controllers/admin/staking/Matchinghistory.php` (+
`views/admin/staking/matching_history.php`),
`application/controllers/admin/staking/Payoutqueue.php` (+
`views/admin/staking/payout_queue.php`),
`application/controllers/Binarymatchingpayouttest.php` (14/14).
**New (eligibility + level-cascading + admin tree):**
`application/controllers/Stakingmatchingeligibilitytest.php` (7/7),
`application/controllers/Levelcascadetest.php` (17/17),
`application/controllers/admin/staking/Genealogytree.php` (+
`views/admin/staking/genealogy_tree.php`),
`application/controllers/Genealogytreetest.php` (12/12, read-only against
real users).
**Touched:** `application/models/staking/Stakingmatching_model.php`
(recipient eligibility gate, §7), `application/config/routes.php` (cron + 5
admin routes total), `application/views/admin/Layout/admin_sidebar.php` (4
new links, additive — Ceiling Wallet had none before this work),
`application/controllers/admin/wallet/Cronlab.php` (new job button),
`application/controllers/user/usersettings/Genealogycontroller.php` +
`views/user/member/view-genealogy.php` (ceiling/staking display on the
member-facing tree; also closed a real pre-existing gap — `member_json()` had
no ownership check at all, now matches `tree_json()`'s `isDescendantOf` guard).
**Not touched, on purpose:** `Ceilingwallet_model.php`, and a separate,
already-broken parallel "binary matching bonus" system (dead models/
controllers under `member/`, `cron/`, `admin/BinaryMatchingAdmin.php` — fails
silently on every purchase today, left exactly as-is per explicit
instruction).
