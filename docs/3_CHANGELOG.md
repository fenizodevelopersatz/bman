# 3 — Changelog (every step, newest first)

Chronological record of work on the landing/home page module. Each entry lists
**what changed**, **files**, and **how to apply** (SQL/route/cache).

---

## 2026-08-08 (later) — Binary Matching: level-wise distribution engine

Replaced the binary matching payout leg with the level-by-level model. The old
`Stakingmatching_model::payMatching()` paid every ancestor in one depth-blind
pass off a purchase-time carry ledger; against the ten acceptance tests it
scored **2/10**. The new `Binarylevelmatching_model` scores **10/10**.

Business rules (user-ruled 2026-08-08, after the probe surfaced two
contradictions in the written spec): level N pays on the **cumulative** eligible
Lock Wallet volume of levels 1..N per leg (Option B — the only reading that
produces the spec's own 60,000 level-3 figure); ceiling from the **MAX** eligible
package, **reset fresh every level**, no lifetime cap; 10% split 8/2 unchanged;
excess **and** the whole bonus for an unstaked sponsor go to **Admin**, never
`ceiling_wallet` and never deferred; matured stakes stop counting the day they
mature; a completed level is never paid twice.

- **Idempotency is a DB guarantee, not a convention.** `UNIQUE(user_id, level)`
  on `staking_matching_payouts`, and the payout row is INSERTed **before** any
  wallet credit — a duplicate run collides with the key and abandons the level
  having moved nothing. (The old engine credited first and relied on carry
  subtraction, which is exactly why it failed the no-repeat test.)
- **Volume is recomputed live** from `user_stakes` + `binary_placement` via a
  recursive CTE — never consumed or decremented, so level N legitimately
  re-counts levels 1..N-1 and no paid level can erase volume a later level needs.
- **`admin_wallet` had no `id=1` row**, so `Bonusreduction_model`'s
  `UPDATE … WHERE id=1` has been silently crediting nothing. Seeded by the
  migration; the engine upserts so overflow can never evaporate that way.
- **Ceiling data disambiguated:** packages 45/46/47 (duplicate 50k/100k/200k
  rows whose ceiling equalled the stake amount) deactivated — guarded by a
  `NOT EXISTS` on `user_stakes`, verified zero stakes referenced them. Ids
  5/6/7 already held the correct mapping.
- **Untouched:** ROI, staking purchase, Lock Wallet creation, plans/packages,
  wallet distribution matrix, rank systems, and the entire on-chain leg —
  `BinaryMatchingPayoutCron` needed **no change** and still handles enqueue,
  gas precheck, broadcast, confirmations and admin retry off the same table.
  `propagate()` still runs so `binary_carry`/`staking_group_volume` keep
  feeding the dashboards and genealogy pages.
  - **Files:** `application/models/staking/Binarylevelmatching_model.php` (new),
    `application/models/staking/Matchingqueue_model.php` (one-line engine
    switch — also the revert), `db/binary_level_matching.sql`,
    `application/models/staking/Binarymatchingprobe_model.php` +
    `application/controllers/Binarymatchingrulesprobe.php` (audit + the 10-test
    acceptance suite), Cron Lab diagnostic card.
  - **Apply:** run `db/binary_level_matching.sql` (idempotent), then
    `php index.php binarymatchingrulesprobe tests` — expect 15/15.
  - **Ceiling config is 100% dynamic + fails safe.** The engine holds no
    ceiling literal (`grep -nE "30000|50000|..."` → no matches); every level
    re-reads `staking_packages.group_ceiling`, the same store written by
    Admin ▸ Staking ▸ Rank Power (`Staking_model::saveCeilings()`) and the
    per-package "Group ceiling" field. `stake_amount` only identifies WHICH
    config to look up — it is never used as the cap. If the config is missing
    or ambiguous (several eligible packages tied at the highest stake amount
    with different ceilings), the engine **skips and retries**: no payout row,
    no credit, no admin overflow, no transaction opened, level left OPEN and
    re-evaluated next run once an admin fixes it. An admin misconfiguration
    can never permanently consume a member's level, and the excess is not
    forfeited to Admin. `run()` reports `deferred_levels` so a stalled config
    is visible in Cron Lab / `cron_execution_log`. `no_stake` stays
    non-retryable — that is a real business outcome, not a fault.
  - ⚠️ **Incident, fixed:** the test harness initially called the engine
    unscoped and credited five real members before being reversed in full. See
    the plan doc §0.0c — the root cause is that the old preflight guard checked
    `binary_carry`, which the new engine never reads. The harness now passes an
    explicit `user_ids` whitelist and actively detects escapes.

---

## 2026-08-08 — Per-day ROI: cycle-1 anchor realignment + continuity guard

The first per-day shipment (97e1f62) anchored every cycle at `created_at +
cycle_no` months — cycle 1 always waited for NEXT month. Hours later (82b953b)
`cycleAnchorMonth()` introduced the intended rule: purchase day ≤ earliest
configured credit day → cycle 1 opens in the purchase month itself ("buy on
the 7th, get day 7's credit the same day" — the rule the member-facing
schedule popup previews). Records bought in the in-between window (#44, #46;
plus #48's `next_payment_date`) kept old-rule data: e.g. #46 (bought
2026-08-07, days 7,8,9) had cycle 1 in September, and on its completion the
new rule would have opened cycle 2 in September too — **two full months of
ROI in one calendar month**.

1. **`cycleOpenAnchor()` (new, RoiStakingManagement_model):** cycle N > 1 now
   anchors to (cycle N-1's REAL rows' month) + 1, falling back to
   `cycleAnchorMonth()` when no prior rows exist. Consecutive cycles land in
   consecutive months no matter how cycle 1 was seeded (legacy rule, hand-
   moved test rows). Used by `openCycleDays()` and the monthly cron's
   cycle-completion `next_payment_date` write, so schedule and display can
   never disagree again. Also hardened `cycleAnchorMonth()`'s month
   arithmetic to step from the 1st — `strtotime('+1 month')` on Aug 31
   lands on Oct 1, which silently skipped a month for month-end purchases.
2. **Data repair:** `db/roi_perday_cycle1_realign.sql` (idempotent, applied
   locally) pulls a legacy next-month cycle 1 back into the purchase month —
   only for per_day records bought on/before their earliest credit day, with
   cycle 1 entirely unpaid, rows sitting exactly one month late. Then
   re-points `next_payment_date` at the open cycle's first day-row for every
   per_day record with real rows (no-op when already consistent; feeds the
   admin "due in 7 days" panel + member pages). Verified: #46 → Aug 7/8/9
   (was Sept), #44 → Aug 10/20 (was Sept), #48 next date → Aug 7 (was
   Sept 7); #43/#45 (paid/hand-edited test rows) and #49 untouched; re-run
   matches 0 rows.
   - **Files:** `application/models/RoiStakingManagement_model.php`,
     `application/controllers/RoiMonthlyDistribution_cron.php`,
     `db/roi_perday_cycle1_realign.sql`
   - **Apply:** run the SQL once on any environment that served per_day
     purchases before 82b953b's deploy. Next monthly-cron tick catches up
     #46's now-due August days (by design — same catch-up path as a missed
     run).

---

## 2026-07-15 (later) — Recipient eligibility fix, verified level cascading, admin Genealogy Tree

Three follow-ups after a support scenario raised a real question: does a
recipient need their own stake to collect binary matching income?

1. **Bug found + fixed:** `Stakingmatching_model::payMatching()` only ever
   checked `users.status='1'` — never whether the recipient had staked
   themselves. Since `userCeiling()` returns 0 for "never staked" and 0 was
   treated as "no cap," a user with zero stakes but two funded legs would
   have been paid in full, uncapped. Added `if ($this->userCeiling($uid) <=
   0) continue;` right after the status check — skip without consuming
   carry (same as the existing inactive-user skip), so staking later pays
   retroactively for whatever accumulated. Verified against two real
   accounts (Admin: no stake, correctly skipped; Siva: 4 active stakes,
   correctly paid) — new test `stakingmatchingeligibilitytest`, 7/7.
2. **Level cascading verified, not assumed:** built the exact tree from the
   support scenario (A/B/C with B and A having no own stake, C and 4
   grandchildren all staking 10,000) as a fully synthetic, self-cleaning
   test. Confirmed empirically: C is paid at "level 1," B and A are both
   skipped (no own stake) but their carry is preserved untouched — no carry
   is lost, and no explicit "finish level 1 before checking level 2" step
   is needed, since each ancestor's carry already accumulated independently
   via `_walkUp()`'s single pass. Confirmed the retroactive-payment
   behavior too: staking B afterward pays B from the exact preserved carry
   on the very next run; same for A one level up. Test:
   `levelcascadetest`, 17/17.
3. **New admin screen:** `admin/staking/genealogy-tree` — an interactive
   tree for any member (search by username/UID/id), showing the REAL
   `binary_carry.left_carry`/`right_carry` the engine reads (not the
   member-facing tree's disconnected `user_wallets.exchange_balance` subtree
   sums), plus own stake, ceiling, Ceiling Wallet held balance, and an
   Eligible/Needs-Stake badge per node. Found (but left untouched, per
   instruction) that the existing "View Tree" admin link uses a third-party
   widget with a pre-existing, unrelated data-shape bug (`mid`/`pid` key
   mismatch) that likely renders as disconnected cards. Also added the same
   ceiling/staking/eligibility display to the member-facing `user/genealogy`
   tree, and closed a real, unrelated security gap while there:
   `Genealogycontroller::member_json()` had no ownership check at all (any
   logged-in member could pull any other member's rank/BV/earnings by id) —
   now matches `tree_json()`'s existing `isDescendantOf` guard. New test
   `genealogytreetest`, 12/12 (read-only against real users).

See [17_BINARY_MATCHING_PAYOUT_CRON.md](17_BINARY_MATCHING_PAYOUT_CRON.md) §7-9
for the full detail and the exact worked numbers.

## 2026-07-15 — Binary Matching Bonus: scheduled + on-chain payout cron

The matching **engine** (`Stakingmatching_model` — multi-level upline walk,
`min(left,right)×10%` = 8% Earning + 2% Staking, per-recipient ceiling cap
against their own `staking_packages.group_ceiling`) was already correct and
untouched. What was missing: nothing scheduled it, and every payout was only
ever an internal ledger credit — never a real blockchain transfer. Added
`BinaryMatchingPayoutCron` (root, token-gated, `*/5 * * * *` recommended, plus
a CLI `watch` mode) wrapping the engine in 4 phases: (a) queue-track one
engine run via new `Matchingqueue_model` (MySQL `GET_LOCK`-guarded so
concurrent ticks can't double-process the same carry), (b) idempotently
enqueue one combined on-chain BMAN send per newly-paid user into
`blockchain_payout_queue` (scans `staking_matching_payouts` for unlinked rows
— crash-safe across a restart), (c) drain the queue with a treasury
BNB(gas)+BMAN balance precheck, FIFO, stopping the batch (not skipping ahead)
at the first row the treasury can't afford, (d) confirm broadcasts using the
same algorithm `StakingPurchasecron` already uses. New admin screens
**Binary Matching History** (`admin/staking/matching-history` — first-ever UI
for `staking_matching_payouts` + `binary_matching_queue`, both existed before
but had zero UI) and **Payout Queue** (`admin/staking/payout-queue` — retry
button, never re-credits). Added a Cron Lab job button
(`admin/wallet/cron-lab`) and 3 sidebar links (also gave **Ceiling Wallet**,
Phase 1, its first-ever sidebar entry). No schema changes —
`binary_matching_queue`/`blockchain_payout_queue` already existed, unused,
since an earlier phase. New CLI smoke test `binarymatchingpayouttest` (14/14
pass, synthetic user, forced dry-run). Left a separate, already-broken
parallel "binary matching bonus" system untouched, per explicit instruction —
see [17_BINARY_MATCHING_PAYOUT_CRON.md](17_BINARY_MATCHING_PAYOUT_CRON.md) §6
for exactly which files.

Ran a full evidence-based audit against live code + the real database
afterward (not just a design review). Headline finding: **zero binary
matching payout has ever occurred on this system** — `binary_volume_ledger`
(the engine's required input) is empty for every real user in the live tree,
even though real placement and one real stake exist, because whatever created
those specific `user_stakes` rows didn't trigger `Staking_model`'s
volume-emission hooks. Also found the admin genealogy tree screen
(`BinaryModel::calculateLegInvestments()`) reads a completely different,
disconnected data source than the matching engine — a number displaying
there is not proof the engine has seen it. Also found `staking_packages`'
smallest ceiling tier is `1→1` live, not `5,000→5,000` as originally specced
(every other tier matches). Full 8-section report with code + live-query
citations delivered as an Artifact in-session (not persisted to `docs/` —
point-in-time DB snapshot, would go stale immediately).

## 2026-07-07 — Stakings page: show USDT wallet + clarify ROI destination / cron gap

Added the **USDT Wallet** card to the Stakings wallet strip
(`views/user/wallet/lending_managment.php`) — it leads the row as the staking
**funding source** (`user_wallets.usd_balance`, ledger `usdt`; debited on
purchase), showing the USDT balance + ≈BMAN equivalent (`$wallet_usdt` /
`$wallet_usdt_in_bman`, already passed by `Lendingcontroller::index`). Tagged the
**Earning Wallet** "ROI credited here" — verified the ROI destination:
`Staking_model::_generateRoiSchedule()` always inserts `staking_roi_payouts`
rows with `wallet='earning'`. **Documented the ROI-cron gap:** those payout rows
are inserted `pending` at purchase but never credited — nothing reads
`staking_roi_payouts` and the scheduler has no ROI job (only clear_cache /
send_emails / sync_database / generate_reports). Corrected the stale checklist in
`6_STAKING_PACKAGES_PLANS_ROI.md` (purchase flow ✅, ROI credit cron ⬜ with the
exact still-missing logic) and noted the strip change in
`15_STAKINGS_PAGE_REDESIGN.md`. No schema/logic change; UI + docs only.

Rebuilt the shared Transaction Details modal into a **7-tab** complete financial
audit trail (Summary · Sender · Receiver · Ledger · Blockchain · Validation ·
Audit Timeline), every field verified against the live schema — real records
only. Enhanced `detailEnriched()` to return structured blocks (transaction,
sender/receiver with User ID/Referral/Username/Full Name/Email/KYC/wallet/balance
before+after, ledger_entries debit+credit, blockchain enriched from
`onchain_transactions` or an explicit off-chain note, derived validation checks
for downline/sponsor/wallet-rule/transfer-password/KYC/admin-override, and the
`wallet_transfer_audit` timeline). Both history tables reshaped to **Reference ·
Sender · Receiver · From Wallet · To Wallet · Amount · Token · Status · Date ·
Details**; Details opens the modal. No new tables/records — reuses
`wallet_internal_transfer`, `wallet_ledger`, `wallet_transfer_audit`,
`onchain_transactions`, `users`. Verified against real self + member transfers
(`wallettransfertest detailjson`; Node render harness → 7 tabs/7 panes, no
errors); `ui` 7/7, `run` 18/18. See
[16_WALLET_TRANSFER_ENGINE.md](16_WALLET_TRANSFER_ENGINE.md) §8.

---

## 2026-07-07 — Recipient pickers scoped to valid members only

Fixed both "Send to a Member" pickers listing **all** members. They now show ONLY
the source user's valid recipients for the selected From wallet: the **downline**
for exchange/earning/staking, or the **direct sponsor** for bonus. New read-only
engine helpers `downlineIds($sourceId)` (BFS down the sponsor tree; `sponser` may
be an id or referral id, matched at each level) and
`recipientOptions($sourceId,$fromWallet,$q)`. User `search_recipients` now
requires `from_wallet` and delegates to the engine; new admin endpoint
`admin/finance/internal-transfers/recipients` (Select2, keyed on `sender_id` +
`from_wallet`). Both views clear + refetch the recipient when the source/From
wallet changes; the admin source-user picker still lists everyone (admin acts on
behalf of anyone). Tested `wallettransfertest ui` 7/7 (downline set, exchange =
downline-only excluding self, bonus = sponsor-only); engine `run` 18/18.

---

## 2026-07-07 — Shared Transfer UI layer (both panels, one module)

Connected the User Panel and Admin Panel to ONE shared transfer UI without
touching the backend engine (`validate`/`execute` unchanged). New
`assets/js/wallet_transfer_ui.js` (framework-agnostic; injects its own dialog +
modal DOM/CSS so both panels are pixel-identical) + shared partial
`application/views/shared/wallet_transfer_ui.php`. It (1) **disables invalid
wallet combinations** in the dropdowns (Exchange source-only for internal; member
mode enables all four + shows the downline/sponsor rule) instead of erroring
after selection, (2) runs a live **preview** (rules + balances) on submit, (3)
shows a shared **Confirmation Dialog** (Source User, Recipient, Type, From/To
Wallet, Amount, Available, Balance After, Validation Status) that only enables
Confirm when every rule — and, for users, KYC + transfer password — passes, and
(4) opens a shared **Transaction Details modal** (General / Users / Wallet /
Ledger double-entry / Blockchain when a tx_hash exists / Audit). Added additive
read-only engine helpers `preview()` and `detailEnriched()` and endpoints
`user/transfer_wallet/{preview,tx_detail}` +
`admin/finance/internal-transfers/{preview,tx-detail}`. **Apply:** none (no
schema change) — clear view cache if any. Tested: `wallettransfertest ui` 4/4;
engine regression `run` still 18/18. See
[16_WALLET_TRANSFER_ENGINE.md](16_WALLET_TRANSFER_ENGINE.md) §8.

---

## 2026-07-07 — Centralized Wallet Transfer Engine (one service, both panels)

New `Wallettransferservice_model` — the single validation+execution engine both
the User Panel and Admin Panel call (no duplicated logic). Rules enforced
identically: BMAN only; member exchange/earning/staking → downline, bonus →
direct sponsor only; internal exchange → bonus/earning/staking (Exchange
source-only, no reverse). Admin follows the same rules (via=admin only skips the
User-Panel KYC + transfer-password gates). Execution: validate → idempotency →
ACID txn → row-locked debit/credit (double-entry) → `wallet_internal_transfer`
history → immutable `wallet_transfer_audit` → commit; full rollback on failure.
Schema `db/wallet_transfer_service.sql`. Both `Transfer_wallet::do_transfer` and
`Internaltransfers::do_transfer` rerouted to it. Tested: 18/18 rule + 3/3
execution tests (real data; idempotent re-run no double-debit). Committed 6ad436e.
See [16_WALLET_TRANSFER_ENGINE.md](16_WALLET_TRANSFER_ENGINE.md).

---

## 2026-07-07 — Stakings redesign Phases 2–5 complete (committed separately)

- **Phase 2** (`0cb7360`): rename Package→Stakings (menu/title/hero), removed the
  5 KPI summary cards, redesigned package cards (owned ribbon, terms/lock/min +
  availability chips, T&C popup).
- **Phase 3** (`0594901`): server-side "My Stakings" portfolio (search/status
  filter/sortable/paginate) + CSV & Excel export; full computed columns;
  calculations verified vs real `user_investment` data.
- **Phase 4** (`0dab51f`): full-screen 7-tab investment modal (Package / ROI /
  Transactions / Ledger / Timeline / Documents / Audit), lazy-rendered, wired to
  the Phase-1 `staking_detail` API — real DB/blockchain data only.
- **Phase 5** (`0f6902c`): document generation — new `Stakingdocs` controller
  renders branded printable HTML (print/save-as-PDF, no PDF lib) for Purchase
  Receipt, Investment Agreement, ROI Schedule, and Summary Report from live data,
  with QR codes (explorer/verify). Owner-or-admin access (unauth→404 verified),
  metadata in `staking_documents` (deduped per invest+type + download counter),
  audit trail in `staking_document_log` (actor/IP/UA). `db/staking_documents.sql`.
- **Verification:** every touched file lints; all `user/stakings/*` routes resolve
  (page 307→login, AJAX 303, docs 404-denied); no cross-phase regressions.
  Each phase committed separately for review/rollback. Docs = `user/lending`
  kept as an alias. See [15_STAKINGS_PAGE_REDESIGN.md](15_STAKINGS_PAGE_REDESIGN.md).

---

## 2026-07-07 — Stakings page redesign: plan (doc 15) + Phase 1 (all-tabs investment detail API)

Started the user "Package"→"Stakings" redesign. It maps directly onto the
on-chain/ledger/audit backend built earlier.

- **Plan:** [15_STAKINGS_PAGE_REDESIGN.md](15_STAKINGS_PAGE_REDESIGN.md) — grounded
  in the real page (`user/lending` → `Lendingcontroller` → `lending_managment`
  view) + data (`user_investment`, `package_config`, `history`) and the
  `onchain_transactions` / `wallet_ledger` / `onchain_tx_events` backend. Phased
  (1 API → 2 rename+cards → 3 portfolio table+export → 4 modal UI → 5 documents).
- **Phase 1 (done):** `Lendingcontroller::staking_detail()` returns all 7 modal
  tabs from real data — Package Info, ROI History (+ totals), Transaction History
  (`onchain_transactions`), Ledger (`wallet_ledger`), Timeline, Documents,
  Audit Log (`onchain_tx_events`), plus computed calcs (maturity, days remaining,
  completed/remaining cycles, total/pending/expected-final ROI). Added
  `user/stakings` route alias (canonical) keeping `user/lending` for old links.
- **Verified:** lint clean; routes guarded (303/307 → login); data queries return
  correct structure against a real investment (ZEN, 0.1%/day × 30d); handles empty
  ROI/onchain gracefully.
- **Next (phases 2–5):** remove stat cards + rename in the view/menu/breadcrumb,
  card redesign, server-side portfolio table + CSV/Excel export + `Stakingcalc`
  helper, the 7-tab modal UI, and PDF documents.

---

## 2026-07-07 — Production hardening: mandatory withdrawal tx_hash + scalable batch rotation

- **Withdrawal approval:** `tx_hash` is now **mandatory to approve** — added to the
  admin form (`views/admin/withdraw/view.php`), validated server-side
  (`^0x[0-9a-fA-F]{64}$`), and **de-duplicated** across `withdrawals` +
  `onchain_transactions` before saving. On approve it is verified on-chain and
  linked (from the previous pass); balance is never debited twice.
- **Scalable batch rotation:** new singleton `wallet_sync_cursor`
  (`db/wallet_sync_cursor.sql`: `last_user_id`, configurable `batch_size`,
  `cycle_count`, worker lock). `Chainsync_model::syncBatch/claimBatch/pendingAddresses`:
  priority (pending-tx) addresses first, then an **atomically-claimed** rotation
  window (`SELECT … FOR UPDATE` → multi-worker safe, no double-processing),
  **resumable** cursor across runs/restarts, **wrap → new cycle** on exhaustion,
  RPC-first with BscScan only on a diff, full per-batch metrics to `rpc_sync_log`
  (`scope='batch'`). `Chainsynccron` rewritten to use it (`?batch=&worker=`).
- **Tested (live):** `chainsynctest` = **10/10 pass** (adds batch-rotation-advances,
  restart-recovery-from-cursor, wrap-to-new-cycle, withdrawal tx_hash
  format+dedupe). Live batch cron: 11 addresses, cursor→257, 0 BscScan calls
  (nothing changed).
- **Production reqs met:** configurable batch size (`?batch=` / cursor), idempotent
  (diff-gate + dedupe), duplicate-safe (atomic claim + seen-set), monitoring
  (`rpc_sync_log`), retry (pending re-verified each run), restart recovery
  (persistent cursor).
- **New:** `db/wallet_sync_cursor.sql`. **Touched:** `Chainsync_model.php`,
  `Chainsynccron.php`, `Chainsynctest.php`, `admin/withdraw/Withdraw.php`,
  `views/admin/withdraw/view.php`, [14_ONCHAIN_SYNC_LIFECYCLE.md](14_ONCHAIN_SYNC_LIFECYCLE.md).

---

## 2026-07-07 — On-chain lifecycle (withdrawal/swap) + verification + efficient RPC-first balance sync

Closed the two wiring gaps as a full lifecycle, added chain verification, and a
cost-optimized balance-sync engine.

- **Withdrawal lifecycle:** `withdrawals` gained tx_hash + `chain_status`
  (created→broadcasting→pending→confirmed→failed→reverted→cancelled) + block,
  confirmations, gas_used/price/fee, nonce, explorer_url, failure_reason,
  onchain_tx_id, balance_debited. `Withdraw::update` records the payout hash on
  approve, creates + **verifies** an `onchain_transactions` withdrawal row, marks
  `cancelled` on reject. No double-deduct (debit is request-time; refund guarded).
- **Swap lifecycle:** `Swapengine::deliverBman` attaches the **delivery** hash
  (+ request hash, stored separately) to the swap's on-chain rows via
  `attachSwapDelivery()` — no duplicates; logs a `broadcast` event.
- **Verification (`Chainsync_model::verifyTx`):** RPC tx+receipt+head → status,
  confirmations, gas, nonce, block; finalizes at `minimum_confirmations`; **reorg
  detection** (stored block ≠ chain block → `reorg_count++`, reconcile, event).
- **Efficient balance sync (`Chainsync_model::syncAddress`):** free RPC
  `eth_getBalance`/`balanceOf` primary; compares to `wallet_balance_sync`; **BscScan
  tokentx fallback ONLY on a balance diff**; imports new transfers deduped on
  tx_hash+log_index; caches last block/hash; multi-RPC failover; every attempt /
  balance change / RPC failure / API call → `rpc_sync_log`.
- **Cron:** `Chainsynccron` (`/chain-sync-cron?token=`) finalizes confirmations +
  syncs treasury + a bounded batch of user addresses.
- **Audit:** `onchain_tx_events` now records the RPC endpoint per change; engine
  emits broadcast/status_change/reverted/confirmation events.
- **Tested live (BSC mainnet):** `chainsynctest` = **6/6 pass** (duplicate
  protection, verify real tx = confirmed/733k conf, reorg detect+correct, 5-endpoint
  failover, balance reconciliation, RPC-first diff-gating). Live cron: 12 addresses,
  43 transfers imported, only 3 BscScan calls; 2nd run 0 BscScan calls.
- **New files:** `db/onchain_sync.sql`, `Chainsync_model.php`, `Chainsynccron.php`,
  `Chainsynctest.php`, [14_ONCHAIN_SYNC_LIFECYCLE.md](14_ONCHAIN_SYNC_LIFECYCLE.md).
  **Touched:** `Onchaintx_model.php`, `staking/Swapengine_model.php`,
  `admin/withdraw/Withdraw.php`, `config/routes.php`.
- **Follow-ups:** add a tx_hash input to the withdrawal-approve form; BNB native
  history import; rotate user-address batches for very large user counts. Real
  money-moving withdrawal/swap broadcasts run via their dry-run paths (not executed).

---

## 2026-07-07 — Live auto-capture: every wallet movement recorded on-chain + immutable audit

Wired all wallet flows to record into the On-Chain Transactions module
automatically, in real time, with an immutable audit trail.

- **Single observer hook:** `Walletledger_model::post()` now mirrors every
  committed movement into `onchain_transactions` (after commit, `try/catch` —
  cannot affect the real movement). Captures deposits, wallet transfers, bonus
  reductions, swap credits, withdrawals (ledger debits), ROI/rank/matching — with
  user, wallet, credit/debit, `balance_after`, `reference_type`, and `tx_hash`
  when present. Token metadata is statically cached to avoid per-movement queries.
- **Explicit wiring for ledger-bypassing flows:** legacy admin bonus grants
  (`Walletmanagement` add/deduct → `history`) now also record an
  `admin_adjustment` on-chain row.
- **Bonus reduction:** dropped its direct insert (now caught by the hook); after
  the on-chain send it calls `updateByLedgerId()` to attach the tx hash / mark
  `partial` on on-chain failure.
- **Immutable audit trail:** new append-only `onchain_tx_events`
  (`db/onchain_tx_events.sql`) — `created` / `status_change` / `confirmation` /
  `failed` … per tx, with actor + IP + timestamp. New fail-safe recorder API on
  `Onchaintx_model`: `capture`, `updateByLedgerId`, `upsertByReference`,
  `logEvent`, `events`.
- **Verified live:** an internal reduction auto-created an `onchain_transactions`
  row (via the hook) linked to its `wallet_ledger_id` + two audit events, with no
  direct recorder writes in the reduction code. Row count 40→41, events 0→2.
- **Files:** `db/onchain_tx_events.sql`; touched `Walletledger_model.php`,
  `Onchaintx_model.php`, `Bonusreduction_model.php`,
  `admin/wallet/Walletmanagement.php`; doc [13_ONCHAIN_TRANSACTIONS.md](13_ONCHAIN_TRANSACTIONS.md) §2a.
- **Known gaps (documented):** swap **delivery** hash and withdrawal **payout**
  hash aren't attached yet (the latter needs a `tx_hash` column on `withdrawals`).

---

## 2026-07-07 — On-Chain Transactions module (dashboard + history + detail modal)

Production-shaped On-Chain Admin Wallet Management: **Admin ▸ Finance ▸ On-Chain
Transactions** (`admin/wallet/onchain-transactions`).

- **Dashboard:** 5 live balance cards (USDT/Exchange/Earning/Staking/Bonus =
  `SUM(user_wallets.*)`).
- **History grid:** server-side filter/sort/paginate over a new indexed table
  `onchain_transactions` (`db/onchain_transactions.sql`). Filters: wallet, network,
  status, type, token, date range, block, tx hash, wallet address (from/to), user,
  reference, gas-fee min/max, + free-text search. Columns incl. block,
  confirmations, gas fee, colour-coded status badges, explorer links.
- **Detail modal:** General / Wallet / Token / Gas / Execution / Ledger / Failure /
  Partial / Related / Audit / Actions. Shows stored fields **plus live RPC
  enrichment** (`eth_getTransactionByHash` + receipt + block): real gas used,
  nonce, tx index, confirmations, receipt status, event-log count, method selector.
  Internal txs / execution trace / decoded params are labelled as needing a BscScan
  API key or trace node.
- **Data:** backfilled from `wallet_deposits` (38 real deposits) +
  `bonus_reduction_log`; `Bonusreduction_model` now mirrors every reduction into
  `onchain_transactions`. Deposits/withdrawals/swaps can be wired via
  `Onchaintx_model::record()`.
- **New files:** `db/onchain_transactions.sql`,
  `application/models/Onchaintx_model.php`,
  `application/controllers/admin/wallet/Onchaintx.php`,
  `application/views/admin/wallet/onchain_transactions.php`,
  [13_ONCHAIN_TRANSACTIONS.md](13_ONCHAIN_TRANSACTIONS.md). **Touched:**
  `config/routes.php`, `admin_sidebar.php`, `Bonusreduction_model.php`.
- **Verified:** migration + backfill (40 rows), route guarded (307→login), grid
  query uses indexes, **live RPC enrichment returns real mainnet data** for a
  deposit tx, mirror-insert validated. Gated by `permission_pages['wallet_management']`.
- **Honest limits:** deep fields (internal txs, execution trace, decoded event/param
  names, full ABI logs) require a BscScan API key or a debug/trace archive node —
  wired in the UI, populated when that's configured.

---

## 2026-07-07 — Windows cron scheduler brought into main + documented (was worktree-only)

The `cron.php` + `scheduler/` Windows Task Scheduler system (built earlier) lived
only in the git worktree and was undocumented — doc 11 / the changelog linked to a
`scheduler/README.md` that did not exist (broken link). Fixed.

- **Moved into main:** `cron.php`, the whole `scheduler/` module (Config, Logger,
  CronExpression, CronRunner, JobInterface, 4 example jobs, `task/run_cron.bat` +
  `run_cron.ps1` + `CronJobTask.xml`, `storage/`), and `logs/`.
- **Wrote the missing md:** `scheduler/README.md` — full ops guide (token, manual
  run, `schtasks` create/update/delete, XML import, manual-test checklist,
  "Task Scheduler doesn't fire" debugging, security) + how to point it at
  `/bonus-reduction-cron` and the other CI cron URLs.
- **New numbered doc:** [12_WINDOWS_CRON_SCHEDULER.md](12_WINDOWS_CRON_SCHEDULER.md);
  registered in [0_INDEX.md](0_INDEX.md) (Documents table + status dashboard). The
  doc-11 / changelog link to `../scheduler/README.md` now resolves.
- **What it does:** one Task Scheduler task fires `cron.php` every minute;
  `cron.php` matches each registered job's 5-field cron expression and runs the due
  ones (token + IP gated, flock lock, per-job timeout + exception isolation,
  rotating `logs/cron.log`). It also drives the platform's CI cron endpoints via
  `run_cron.bat`.

---

## 2026-07-07 — Admin Bonus Wallet screen + reduction history (admin UI)

Added the admin-side page that shows the reclaimed-bonus pool and every
reduction — answering "where is the history / admin wallet shown".

- **New screen:** **Admin ▸ Finance ▸ Admin Bonus Wallet** (`admin/wallet/admin-wallet`).
  Balance cards (admin pool, lifetime reclaimed, count, on-chain sent/failed),
  status badges (enabled / interval / % / dry-run / on-chain / admin address),
  and the full **reduction history** table from `bonus_reduction_log` (user,
  cycle, amount, status, tx-hash → BscScan). **Preview** (dry-run) and **Run
  Reduction Now** (super-admin) buttons.
- **Refactor:** reduction logic extracted into
  `application/models/Bonusreduction_model.php` (`run($opt)` + reads); both
  `Bonusreductioncron` (now a thin wrapper) and the admin controller use it — one
  tested code path. Re-verified the cron output is unchanged.
- **New files:** `application/models/Bonusreduction_model.php`,
  `application/controllers/admin/wallet/Adminwallet.php`,
  `application/views/admin/wallet/admin_wallet.php`. **Touched:** `config/routes.php`
  (page + run routes), `admin/Layout/admin_sidebar.php` (menu link),
  `controllers/Bonusreductioncron.php` (thinned).
- **Verified:** route resolves (`/admin/wallet/admin-wallet` → 307 → admin login
  when unauthenticated, i.e. guarded, not 404/500); model reads return live data
  (admin_wallet 0.125, one history row for user 257). Gated by
  `permission_pages['wallet_management']`; run is super-admin + AJAX only.

---

## 2026-07-07 — Bonus Wallet 60-day reduction → admin wallet: IMPLEMENTED (internal path, tested)

Built and tested the Bonus Wallet reduction (scope narrowed to bonus only — ROI
is a later phase and was removed from doc 11).

- **Rule:** every `reduction_interval_days` (60 prod; **1 for testing**),
  `reduction_percent` (50%) of a user's `user_wallets.bonus_balance` is reduced
  and credited to the admin wallet. **Per-user schedule** anchored on
  `users.register_date` (first cycle), then on the previous reduction (rolling).
- **New files:** `application/controllers/Bonusreductioncron.php`,
  `db/bonus_reduction.sql`, `user_cycle_info()` in `application/helpers/site_helper.php`,
  route `bonus-reduction-cron` in `config/routes.php`.
- **Reuses:** `Walletledger_model::debit(...,'bonus_reduction')` (double-entry,
  row-locked), existing `admin_wallet` (doc 10) for the reclaimed pool,
  `Web3bman::sendToken()` for the optional on-chain leg, `token_settings.bonus_wallet`
  → `treasury_wallet` as the admin address.
- **Config toggles** on `staking_bonus_settings`: added `reduction_dry_run`
  (preview; default 1) + `reduction_onchain` (broadcast; default 0). Also uses the
  existing `reduction_enabled/interval_days/percent`.
- **On-chain leg:** when `reduction_onchain=1`, sends BMAN from the user's
  custodial address to the admin wallet (gas in BNB); a failed broadcast is logged
  `failed` and the internal reduction still stands (retryable). Needs funded user
  addresses + a set admin wallet — not exercised on the local box.
- **Run:** CLI `php index.php bonusreductioncron run` or `/bonus-reduction-cron?token=`;
  driven daily by the Windows scheduler (scheduler/README.md).
- **Tested (DB e-commerce-mlm-v2, user 257):** dry-run preview → execute
  (bonus 0.25→0.125, admin 0→0.125, wallet_ledger + bonus_reduction_log rows) →
  idempotent same-day re-run skips → 1-day interval 2nd cycle 0.125→0.0625. Test
  data reset to clean baseline; handoff = interval 1, dry_run=1.
- **Apply:** run `db/bonus_reduction.sql` on the DB (idempotent). ROI table/engine
  intentionally deferred.

---

## 2026-07-07 — Doc 11 redesigned to FULLY ON-CHAIN, treasury-settled (owner decision)

Superseded the same-day internal-ledger draft of doc 11 with an on-chain model,
per owner decision — still plan-only, no code.

- **Model chosen:** *1 real BEP-20 address per user + treasury-settled*. Every
  value movement is a real on-chain BEP-20 transaction signed by `Web3bman` +
  the encrypted treasury key; the five wallet "types" are system labels on one
  on-chain balance, settled to/from the **company treasury**.
- **Internal transfers removed:** the doc-9 internal wallet-transfer module is
  **out of scope / retired** here — `wallet_transfer` reference_type retired, no
  wallet-to-wallet shuffling. `§3` table dropped the "Internal transfer?" column
  for an "On-chain settlement (to/from treasury)" column.
- **`wallet_ledger` becomes an on-chain index:** every value row must carry a real
  `tx_hash`; the confirmed tx *is* the record (idempotency needs no new column).
- **On-chain bonus reduction:** two-phase (broadcast → confirm) `Bonusreductioncron`;
  the per-user 60-day cycle (`user_cycle_info()` from `register_date`) only decides
  *when* it fires. Reclaimed BMAN moves on-chain user→treasury.
- **Company (Treasury) Wallet:** `admin_wallet` (on-chain balance cache: USDT/BMAN/
  BNB gas) + `admin_wallet_ledger` (index of real treasury txs, `tx_hash` required).
- **Gas strategy added (§10):** high-frequency ROI/commissions **accrue off-chain,
  settle on-chain in batches** (one tx per settlement window) to avoid
  gas-per-event; treasury gas tank + low-gas warning + dry-run/batch caps.
- **New reference_types:** `bonus_reduction`, `withdraw_fee`, `roi_settlement`,
  `principal_release`.
- **Files:** rewrote [11_ADMIN_WALLET_MANAGEMENT.md](11_ADMIN_WALLET_MANAGEMENT.md)
  (on-chain §3 table, §4 two-phase reduction, §6 treasury schema, §10 gas
  strategy, §12 files, §13 open decisions); [0_INDEX.md](0_INDEX.md) row 11 +
  status line updated to the on-chain model.

---

## 2026-07-07 — Admin Wallet Management + per-user 60-day cycle: PLAN ONLY (no code yet)

Wrote the implementation plan that supersedes/expands the doc-10 pre-plan into a
full **Admin (Company) Wallet Management** module and resolves its open anchor
question — **nothing executes yet**, docs-only.

- **Critical schema finding:** the `users` table has **no `created_at`/`updated_at`
  column** — it uses `register_date` (`e-commerce-mlm-v2_by_asok.sql:2548`) and
  `updated_date` (`:2556`). The per-user rolling 60-day cycle therefore anchors on
  **`register_date`** (no new column, no new table), which is exactly the account
  creation timestamp the brief intended.
- **Per-user rolling cycle (PHP-only):** new `user_cycle_info()` helper in
  `site_helper.php` computes each user's cycle from `register_date` with
  `DateTimeImmutable` — completed cycles, current cycle, day-in-cycle, next-cycle
  date. **Idempotency without a new column:** the reduction engine checks for an
  existing `bonus_reduction` row in `wallet_ledger` within the current cycle
  window, so the ledger *is* the cycle record.
- **Resolves doc-10 Open-Question #1:** per-user rolling (not the shared global
  Rank-Power cycle in `staking_rank_power_cycles`, which stays separate/untouched).
- **Admin (Company) Wallet expanded:** doc-10's single-balance `admin_wallet`
  becomes a **5-wallet company mirror** (`admin_wallets` + `admin_wallet_ledger`,
  USDT/Exchange/Earning/Staking/Bonus) fed by bonus reductions, withdrawal fees,
  and audited manual adjustments.
- **Also documented:** maturity period (per-investment `mature_date = start +
  package_config.days_duration`, enforced in `Cron.php::run_roi()`→`package_mature()`,
  reinvest vs release_deposit), a new read-only **Maturity Monitor**, a consolidated
  **Finance** admin menu, a unified **Wallet Statement**, and **PDF/Excel/CSV
  export** (none exists today — no mPDF/TCPDF/PhpSpreadsheet in the codebase).
- **Ledger consistency flagged:** `Walletmanagement` admin add/deduct currently
  writes the legacy `history` table, bypassing `wallet_ledger` — plan reroutes it
  through `Walletledger_model` so statements are complete (open decision).
- **Files:** new doc
  [11_ADMIN_WALLET_MANAGEMENT.md](11_ADMIN_WALLET_MANAGEMENT.md) (schema, PHP cycle
  helper, cron, flowcharts, screens, export, open decisions);
  [0_INDEX.md](0_INDEX.md) doc table + status dashboard updated to point at it.
- **Next (pending owner sign-off on 5 open decisions):** `db/admin_wallet.sql`,
  `Adminwallet_model.php`, `Bonusreductioncron.php`, `admin/finance/Companywallet`
  + `Maturity` screens, `Statement_export.php`, and the `site_helper` cycle helper.

---

## 2026-07-07 — Bonus Wallet 60-day/50% reduction + Admin Wallet: PLAN ONLY (no code yet)

Wrote the full design for the pending "Bonus reduction cron" item (flagged
since 2026-07-02 in [0_INDEX.md](0_INDEX.md) Phase B) — **nothing executes
yet**, this is planning only.

- **Scope clarified:** targets `user_wallets.bonus_balance` (the BMAN Staking
  Bonus Coin wallet, §7), **not** the unrelated legacy MLM `history`-table
  bonus in `admin/wallet/Walletmanagement.php`.
- **New concept — Admin (Company) Wallet:** none exists today. Plan adds
  `admin_wallet` (singleton balance) + `admin_wallet_ledger` (append-only,
  mirrors `wallet_ledger`), so every 50% debited from a user's Bonus wallet is
  matched by a credit into one company-owned account — same double-entry
  principle already used by `Walletledger_model`, instead of the money simply
  vanishing (forfeiture) with no audit trail.
- **Engine:** new `Bonusreductioncron` controller modeled on `Depositcron.php`
  (CLI `php index.php bonusreductioncron run` + HTTP
  `/bonus-reduction-cron?token=`), run daily, processes whichever users are
  due per a new `user_wallets.bonus_last_reduced_at` anchor column (backfilled
  to `NOW()` on migration so no one is hit retroactively on day one). Reads
  existing `staking_bonus_settings.reduction_enabled/interval_days/percent`
  (already admin-configurable at 60 days / 50%, built 2026-07-02).
- **Open questions logged for the business owner:** per-user rolling anchor
  vs. shared global 60-day cycle (like Rank Power's); whether the Admin
  Wallet balance is spendable or reporting-only; user notification on
  reduction; confirming Bonus Transfer (§7) is the intended escape valve.
- **Files:** new doc
  [10_BONUS_WALLET_REDUCTION_ADMIN_WALLET.md](10_BONUS_WALLET_REDUCTION_ADMIN_WALLET.md)
  (full schema, engine pseudocode, admin/user surfaces, rollout safety);
  [0_INDEX.md](0_INDEX.md) doc table + status dashboard + Phase B task line
  updated to point at it.
- **Next:** implement `db/admin_wallet.sql`, `Adminwallet_model.php`,
  `Bonusreductioncron.php`, and the admin "Admin Wallet" screen per §11 of the
  plan doc — none of that exists yet, this commit is docs-only.

---

## 2026-07-03 — On-chain BMAN delivery to the user's address (return the coin)

The swap credited BMAN internally (Exchange/Bonus) but wasn't sending it on-chain
to the user. Now it does — the internal credit backs an on-chain BMAN balance in
the user's custodial address (same model as USDT deposits).

- **Enabled:** `swap_bonus_onchain=1` → new swaps also send BMAN + 25% bonus
  on-chain (treasury → user address) after the USDT settles + internal credit.
- **Deliver method:** `Swapengine::deliverBman($orderId)` (idempotent — skips if
  `bman_tx_hash` set) + `deliverPending()` for a batch. Signed by the treasury
  key; **treasury must hold BMAN + BNB gas**.
- **Triggers for already-completed orders** (bman_tx_hash null):
  - **Admin:** Swap Orders page → **Send BMAN** button (per order).
  - **Auto/cron:** `/deliver-bman-cron?token=<cron_token>` (or
    `php index.php admin/staking/swaporders deliver_cron`).
- **Files:** `models/staking/Swapengine_model.php`,
  `controllers/admin/staking/Swaporders.php`,
  `views/admin/staking/swap_orders.php`, `config/routes.php`.
- **Verified (dry-run):** deliverBman sets bman/bonus tx and blocks a second
  delivery (idempotent).

---

## 2026-07-03 — Automatic deposit crediting + swap modal wording

- **Auto-credit deposits (no admin step):** `Depositcron` now runs the on-chain
  listener over CLI **or** HTTP (token-gated) at route `credit-deposits-cron`. It
  detects confirmed USDT deposits to custodial addresses and credits the internal
  USDT wallet idempotently — clearing "NEW DEPOSIT PENDING — admin will credit".
  Also, opening the Buy modal (`stake_quote`) now runs a best-effort per-user
  scan so a just-confirmed deposit appears immediately.
  - Verified: one cron run detected + credited 38 pending deposits; a stuck user
    (FENIZO557554) went from USDT 0 → **1.50**, unblocking their swap.
  - **Schedule it** every 1–3 min: `php index.php depositcron run` (Windows Task
    Scheduler / cron), or hit `/credit-deposits-cron?token=…` from an uptime pinger.
  - HTTP token set: `config['cron_token'] = 'dcron_9f27ab5c3e8140d6'` (change it).
    URL: `/credit-deposits-cron?token=dcron_9f27ab5c3e8140d6` (`&user_id=N` optional).
- **Manual "update wallet":** the profile's *Check On-chain Balance*
  (`member/profile/wallet_check`) now also **credits** the user's confirmed
  deposits (runs `scan($uid)`) and returns the credited count — one click funds
  the USDT wallet. File: `controllers/user/usersettings/Profile.php`.
- **Swap modal wording:** in swap mode the modal now reads "Buy BMAN (Swap)",
  "BMAN → Exchange Wallet" and "Confirm & Swap" (was staking language); the card
  button reads "Buy BMAN". Files: `views/user/wallet/_staking_packages.php`,
  `controllers/Depositcron.php`, `controllers/user/usersettings/Lendingcontroller.php`,
  `config/routes.php`.

---

## 2026-07-03 — Swap finalised: USDT→admin on-chain, BMAN→Exchange + 25%→Bonus (ENABLED)

Per the final spec ("admin sends USDT → returns equal BMAN to the user, shown on
the Exchange wallet, + 25% bonus BMAN"): the swap now moves real value one way
on-chain and delivers BMAN into the user's **internal wallets** so it's visible.

- **Swap now does, per purchase:** debit the user's **USDT wallet**; credit the
  equal **BMAN to the Exchange wallet**; credit **25% BMAN to the Bonus wallet**
  (all internal, one transaction — this is what the wallet page shows). The
  **USDT settles on-chain** user deposit address → admin wallet (`Web3bman`,
  user's key). Example at 1 USDT = 1 BMAN: 100 USDT → 100 BMAN (Exchange) + 25
  BMAN (Bonus).
- **BMAN stays custodial** (Exchange/Bonus wallets) by default —
  `swap_bonus_onchain=0` — so there is no double delivery. Set it to 1 to *also*
  push BMAN on-chain to the user's own address.
- **ENABLED:** `swap_enabled=1` (package purchase now routes through the swap),
  `swap_dry_run=1` (the on-chain **USDT** leg is still simulated until a live
  test — internal credits are real so the flow is fully usable now).
- **Files:** `models/staking/Swapengine_model.php` (internal Exchange/Bonus
  credits + USDT debit; on-chain BMAN gated off), `db/staking_swap.sql`.
- **Verified (CLI):** 5,000 BMAN package → USDT −5,000, Exchange +5,000 BMAN,
  Bonus +1,250 BMAN, order `completed`, on-chain USDT = `DRYRUN`; balances
  restored; DB clean.
- **To make the USDT leg real:** fund BNB gas on the user deposit addresses, then
  set `swap_dry_run=0`.

### LIVE (2026-07-03): `swap_dry_run=0`
- On-chain USDT sends are now **real** (mainnet). Safe ordering: the engine sends
  USDT on-chain FIRST; BMAN (Exchange) + 25% (Bonus) are credited **only after**
  the USDT send succeeds — a gas/RPC failure returns "USDT settlement failed —
  nothing credited" (no free BMAN). A ledger error *after* a settled USDT parks
  the order `failed_credit` for admin reconciliation.
- **PREREQUISITE — fund BNB gas** on each user deposit address (they sign their
  own USDT send). Without gas, every live swap fails cleanly at the USDT leg.
- Rollback to safe mode any time: `UPDATE token_settings SET swap_dry_run=1`.

### Auto BNB gas top-up (2026-07-03)
- Before the USDT leg, `Swapengine::_ensureGas()` checks the user deposit
  address's BNB. If below the cost of one BEP-20 transfer (gasLimit×gasPrice
  ×1.5), it sends BNB from the **treasury/gas wallet** (top-up to ~2×) and polls
  until spendable (~3s BSC blocks, capped ~24s). Users never hit the gas wall.
- Config `token_settings.swap_auto_gas` (default 1); the top-up tx is recorded in
  `staking_swap_orders.gas_tx_hash`. If gas can't be funded the swap aborts
  `failed_gas` **before** any USDT/BMAN moves (safe). **The treasury wallet must
  hold BNB** to fund top-ups.
- Verified (dry-run): gas step is skipped (`gas_tx=none`), swap completes, wallets
  credit; flags remain live (swap_enabled=1, swap_dry_run=0, swap_auto_gas=1).

### Admin Swap Orders screen + retry (2026-07-03)
- **Page:** `admin/staking/swap-orders` — lists every swap order (ref, user,
  USDT/BMAN/bonus, status, gas/usdt/bman tx, error) with a mode banner
  (LIVE / DRY-RUN / OFF, auto-gas on/off) and a **Retry** button on parked orders.
- **Resume:** `Swapengine_model::resume($id)` safely continues a parked order —
  re-sends USDT **only** if it never settled (`usdt_tx_hash` empty), and credits
  the wallets **only if not already credited** for that ref (idempotent). So
  `failed_credit` (USDT already on-chain) just re-applies the credit;
  `failed_gas`/`failed_usdt` redo the settlement.
- **Files:** `controllers/admin/staking/Swaporders.php`,
  `views/admin/staking/swap_orders.php`, `models/staking/Swapengine_model.php`
  (`resume()`), `config/routes.php`.
- **Verified (CLI):** a `failed_credit` order → retry credited Exchange +5,000,
  Bonus +1,250, marked `completed`, USDT **not** re-sent; a second retry was
  blocked ("already completed") with **no double-credit**; DB clean.

---

## 2026-07-03 — On-chain USDT⇄BMAN swap (model "A", dry-run gated)

Per the chosen model **A**: a package purchase is a **real two-leg on-chain swap**
(everything on-chain, no internal locking):
  - **Leg 1 — USDT:** user deposit address → Admin (treasury) wallet
  - **Leg 2 — BMAN:** Admin wallet → user deposit address (at the admin rate)
  - **Leg 3 — BMAN:** optional 25% bonus, Admin → user

- **Engine:** `Swapengine_model` — `quote()` (BMAN/USDT/bonus + addresses + real
  on-chain USDT balance) and `execute()` (validate → create order → Leg 1 signed
  with the **user's** encrypted deposit key → Leg 2/3 signed with the **treasury**
  key). Uses `Web3bman::sendToken`/`getTokenBalance`. Leg 2/3 fire only after
  Leg 1 succeeds; partial failures are parked (`failed_usdt`/`failed_bman`) with
  the tx hash for retry. New `Tokenmaster_model::convertBmanToUsdt()` reused.
- **SAFETY (critical):** gated by `token_settings.swap_enabled` (**default 0**)
  and `swap_dry_run` (**default 1**). In dry-run the engine records exactly what
  it *would* broadcast (`DRYRUN-…` tx hashes) and sends **nothing** on-chain. The
  real-balance guard is enforced for live swaps only. Nothing goes live until an
  admin flips both flags after a funded live test (gas on signers + BMAN
  liquidity in treasury). Real mainnet contracts are already set (USDT `0x55d3…`,
  BMAN `0xDe76…`, admin `0x3088…`).
- **Wiring:** `user/lending/swap_purchase` (POST) + `Lendingcontroller::swap_purchase()`;
  the package modal's Confirm posts to the swap endpoint when `swap_enabled=1`,
  else the internal staking purchase (so nothing breaks pre-launch).
- **Files:** `models/staking/Swapengine_model.php` (new),
  `models/Tokenmaster_model.php`, `controllers/user/usersettings/Lendingcontroller.php`,
  `views/user/wallet/_staking_packages.php`, `views/user/wallet/lending_managment.php`,
  `config/routes.php`, `db/staking_swap.sql` (new — `staking_swap_orders` +
  `token_settings.swap_enabled/swap_dry_run/swap_bonus_onchain`).
- **Verified (CLI, dry-run):** 5,000 BMAN package → order `completed`, dry_run=1:
  Leg 1 USDT 5,000 user `0x0861…`→admin `0x3088…`; Leg 2 BMAN 5,000 admin→user;
  Leg 3 bonus 1,250 BMAN; the real-balance guard correctly blocked a live swap on
  a 0-USDT address; flags remain 0 / 1 (off). No real transaction broadcast.
- **To go live:** fund BNB gas on user deposit addresses + the treasury signer,
  hold BMAN liquidity in the treasury wallet, run one real swap with a tiny
  amount, then set `swap_enabled=1`, `swap_dry_run=0`.

---

## 2026-07-03 — Purchase flow refinements: Treasury payment record + BMAN balances in modal

Confirming/tightening the intended flow: *USDT income → USDT Wallet → package →
plan → validate USDT → send to Admin Treasury → return BMAN (locked) → instant 25%*.

- **(1) No BMAN return on deposit — confirmed:** `convertUsdtToBman()` is now used
  only for the read-only "≈ BMAN" display on the lending card; nothing converts or
  returns BMAN at deposit time. Deposits credit the USDT wallet only.
- **(2) Validate USDT + show BMAN in the purchase modal:** `stake_quote` now also
  returns the four BMAN wallet balances; the modal lists **Exchange / Staking /
  Bonus / Earning** under the USDT balance so the user sees both before confirming.
- **(3) USDT → Admin Treasury recorded explicitly:** `purchaseStake()` writes a
  `staking_treasury_payments` row (user, stake, ref, USDT taken, BMAN returned,
  rate snapshot, treasury wallet, tx_hash) inside the purchase transaction — an
  admin-facing "money received" ledger alongside the user's USDT debit.
- **(4) Instant 25% bonus → Bonus wallet:** unchanged (already live).
- **Files:** `models/Staking_model.php`,
  `controllers/user/usersettings/Lendingcontroller.php`,
  `views/user/wallet/_staking_packages.php`, `db/staking_treasury.sql` (new table).
- **Verified (CLI):** purchase of 5,000 BMAN (fixed/3y) → 5,000 USDT debited, 5,000
  BMAN locked, 1,250 bonus, and a `staking_treasury_payments` row (usdt 5,000 →
  bman 5,000, rate 1, treasury `0x3088…d321`) linked to the stake; balances
  restored after the test.

---

## 2026-07-03 — Staking Binary Matching Bonus + rank-volume processor (separate)

- **What:** the separate engine that turns staking business volume into income.
  Runs after purchases (each purchase drops a `binary_volume_ledger` row):
  1. **Propagate** — walk each un-processed stake's BV up the `binary_placement`
     tree, adding it to every upline's LEFT/RIGHT leg in **`binary_carry`**
     (reducible, consumed by matching) and **`staking_group_volume`**
     (cumulative, for rank achievement); mark the row `processed=1` (idempotent).
  2. **Pay matching** — for each user whose two legs both carry volume, match
     `min(left,right)` and pay `matching_total_percent` (10%) split per
     `staking_bonus_settings`: **8% → Earning wallet, 2% → Staking wallet**; the
     matched volume is subtracted from both legs (carry forward). Audited in
     `staking_matching_payouts` + `wallet_ledger` (`binary_matching`).
- **Files:** `models/staking/Stakingmatching_model.php` (new),
  `controllers/admin/staking/Matching.php` (new — admin `run` [AJAX] + `cron`
  [CLI/token]), `config/routes.php`, `db/staking_matching.sql` (new — adds
  `binary_volume_ledger.processed`, tables `staking_group_volume`,
  `staking_matching_payouts`).
- **How to apply:** run `db/staking_matching.sql` (idempotent). Trigger:
  `admin/staking/matching/run` (POST, admin) or
  `php index.php admin/staking/matching cron` (schedule every N min/hours).
- **Verified (CLI):** 3-node tree (A=5,000 left, B=3,000 right under root R):
  propagated 2 rows; matched 3,000; paid R **240 BMAN earning + 60 BMAN staking**;
  carry → left 2,000 / right 0; cumulative group volume 5,000 / 3,000; a second
  run propagated 0 / paid 0 (idempotent); balances restored after the test.
- **Note:** this pays the binary **matching bonus** and accumulates **rank
  volume** (`staking_group_volume`). Final **rank achievement/qualification**
  (tier promotion, group incentive) remains the existing separate Rank Power
  cycle system (`admin/staking/rank-power`), which now has cumulative group
  volume to read.

---

## 2026-07-03 — Wallet/staking business rules: deposit = USDT-only, purchase = conversion point

Major reshaping of the money model per the new business rules. USDT and BMAN are
now cleanly separated: **deposits only ever touch the USDT Wallet**, and the
**only** place USDT converts to BMAN is a **Staking Package Purchase**.

- **Deposit → USDT Wallet ONLY:** `Depositlistener_model::creditConfirmed()` no
  longer converts USDT→BMAN or credits the Exchange wallet. A confirmed on-chain
  deposit credits just the USDT wallet (idempotent via unique tx_hash) and writes
  `wallet_deposits` (deposit history) + `wallet_ledger` (wallet transaction). No
  staking / ROI / bonus / binary / rank side effects at deposit time.
- **Staking wallet is un-transferable:** `Wallettransfer_model $allowed` now
  `[exchange, earning, bonus]` (staking removed). The Staking wallet holds LOCKED
  BMAN and can be credited only by a successful purchase. Staking option removed
  from the user (`transfer_wallet`) and admin (`internal_transfers`) forms.
- **Lending page wallet card:** "Available Balance" → **"Available USDT Balance"**
  (USDT + ≈ BMAN at the admin rate), with a strip of the four BMAN wallet
  balances (Exchange / Staking / Bonus / Earning) below. Only USDT buys packages.
- **Staking Purchase engine** — `Staking_model::purchaseStake($ctx)` (one MySQL
  transaction, full audit): validate account active + KYC + package/plan/term
  active + ROI cell + USDT balance + configured rate → **debit USDT** (payment
  routed to Treasury wallet, tx_hash optional) → **convert** BMAN package price at
  the admin `exchange_rate` → **create `user_stakes`** order → **credit LOCKED
  BMAN** to Staking wallet → **25% Bonus** to Bonus wallet → **ROI schedule** to
  `staking_roi_payouts` (fixed = total at maturity; regular = monthly on the
  plan's credit day; combo = 50/50) → **binary volume** to `binary_volume_ledger`
  (feeds the rank/matching cron) → activate. New helper
  `Tokenmaster_model::convertBmanToUsdt()`.
- **Endpoints (user):** `user/lending/stake_quote` (POST, price/bonus/balance) and
  `user/lending/purchase_stake` (POST) in `Lendingcontroller`; a **Stake Now**
  button + purchase modal (plan + term picker, live quote, insufficient-balance
  guard) on each staking package card.
- **Files:** `models/Depositlistener_model.php`, `models/wallet/Wallettransfer_model.php`,
  `models/Staking_model.php`, `models/Tokenmaster_model.php`,
  `controllers/user/usersettings/Lendingcontroller.php`,
  `views/user/wallet/lending_managment.php`, `views/user/wallet/_staking_packages.php`,
  `views/user/wallet/transfer_wallet.php`, `views/admin/wallet/internal_transfers.php`,
  `config/routes.php`. Reused existing tables (`user_stakes`, `staking_roi_payouts`,
  `binary_volume_ledger`, `wallet_ledger`, `wallet_deposits`) — no new tables.
- **Verified (CLI):** deposit credits USDT only (no exchange/BMAN); staking removed
  from transfers (validate rejects). Purchase engine end-to-end on the 5,000 BMAN
  package: **fixed/3y** → −5,000 USDT, +5,000 locked BMAN, +1,250 bonus, 1 ROI row
  = 10,000 BMAN at maturity, binary volume 5,000, Exchange untouched;
  **regular/2y** → 24 monthly rows × 115 (=2,760); **combo/5y** → 61 rows
  (10,000 fixed + 60×75 = 14,500); insufficient USDT correctly blocked; balances
  restored after tests.

---

## 2026-07-03 — /user/lending: show BMAN staking packages + explain details

- **What:** the lending page now displays the **BMAN staking packages** (from the
  new staking system) above the legacy invest grid, with their details explained.
  For each of the 9 active packages it shows the **stake amount**, one-time
  **Bonus %**, **Group Ceiling**, and a **ROI matrix** across terms (2 / 3 / 5
  years) and plans — **Fixed** (total % at maturity) vs **Regular** (% credited
  monthly). Three plan-explainer cards (Fixed / Regular / Combo) describe how ROI
  is credited & withdrawn (credit mode, credit days, term durations), pulled live
  from `staking_plans` / `staking_plan_terms`.
- **Data source:** `Staking_model::roiGrid()` (packages + active
  `staking_roi_structure` cells, keyed `fixed_2`,`regular_5`, …) and
  `Staking_model::plans(true)`. Read-only — no change to the existing invest flow.
- **Files:** `controllers/user/usersettings/Lendingcontroller.php`
  (`getStakingPackagesForView()`, `getStakingPlansForView()`; passes
  `staking_packages` / `staking_plans`), `views/user/wallet/lending_managment.php`
  (includes the block after the KPI grid),
  `views/user/wallet/_staking_packages.php` (new self-contained explainer partial,
  scoped `stk-` CSS, hides itself when no active packages).
- **Verified (CLI):** `roiGrid()` returns 9 active packages; sample 5,000 BMAN =
  25% bonus, ceiling 5,000, ROI Fixed 150/200/400% & Regular 2.3/2.5/3.0%/mo for
  2/3/5 yrs; 3 plans (fixed/regular/combo) each with 3 terms. Lint clean.

---

## 2026-07-03 — Admin: send any amount (overdraft override) + explicit txn_type

- **What:** an admin can now send **any amount** to another user even when the
  sender's wallet lacks the funds — the sender's balance goes negative and the
  recipient is credited the same amount, so the double-entry ledger stays
  balanced (sender −X / recipient +X). Regular members are **still** held to
  their available balance (and KYC). Applies to both modes (self & member).
- **Type clearly stored:** new `txn_type` column (`self` | `member`) records the
  movement type explicitly, alongside the already-stored **who sent**
  (`user_id`), **who received** (`to_user_id`), and **which wallet**
  (`from_wallet` / `to_wallet`) — plus `via` (`user`/`admin`). The admin detail
  modal now shows a **Type** badge and the **Direction** (User #A → User #B, or
  "own wallets").
- **Ledger override:** `Walletledger_model::post()` accepts
  `opts['allow_overdraw']` (skips the insufficient-balance guard). Only the admin
  path passes it (`skip_kyc` ⇒ `allow_overdraw`).
- **Files:** `models/Walletledger_model.php`,
  `models/wallet/Wallettransfer_model.php` (skip balance floor + pass overdraw +
  set `txn_type` in `execute()`/`sendToUser()`),
  `views/admin/wallet/internal_transfers.php` (detail modal Type + Direction),
  `db/wallet_internal_transfer.sql` (`txn_type`, backfilled).
- **How to apply:** re-run `db/wallet_internal_transfer.sql` (idempotent —
  adds `txn_type`, backfills existing rows from `to_user_id`).
- **Verified (CLI):** admin member over-send (sender 0 → −500, recipient +500,
  `txn_type=member`, `via=admin`, sender/recipient/wallet recorded); admin self
  over-move (`txn_type=self`); **non-admin over-send still blocked**; balances
  restored after the run.

---

## 2026-07-03 — Internal transfer: Select2 AJAX member picker (lazy load)

- **What:** the Source-User and Recipient dropdowns on `/internel-transfer` no
  longer dump every member into the DOM (was `limit 500` `<option>`s). They are
  now **Select2** inputs that fetch members **on demand via AJAX** — type to
  search by username / referral ID / email / id, empty box shows all members
  (paginated, 20/page, infinite scroll). Fixes page load time.
- **Endpoint:** `Internaltransfers::users()` — GET `q`, `page`; returns
  `{results:[{id,text}], pagination:{more}}`. Route
  `admin/finance/internal-transfers/users` (GET). Controller `index()` no longer
  runs the 500-user query.
- **Files:** `controllers/admin/wallet/Internaltransfers.php`,
  `views/admin/wallet/internal_transfers.php` (empty selects + Select2 init;
  Select2/jQuery come from the Metronic `plugins.bundle.js`), `config/routes.php`.
- **Verified (CLI):** empty term returns all active members; search by referral
  prefix and by numeric id both match; `more` flag computed correctly.

---

## 2026-07-03 — Admin internal transfer + global 8-digit TXN ID + balance snapshots

- **What:** the admin **Finance ▸ Internal Wallet Transfers** page can now
  *initiate* transfers (not just view them), with **no user-side gates** (KYC /
  transfer password bypassed — admins are trusted). Two modes: **Between User's
  Wallets** (any of a user's four wallets → another) and **To Another Member**
  (user A → user B, same wallet). Selecting a source user shows the live
  **four wallet balances** (Exchange / Earning / Staking / Bonus), mirroring the
  member page but validation-free.
- **Global tracking id:** every transfer now carries a **globally-unique 8-digit
  `txn_uid`** (shown on both admin + user grids and in the detail modal) for
  easy cross-referencing.
- **Balance snapshots:** each row stores `from_before / from_after / to_before /
  to_after` ("manage the previous amount"). The admin detail modal and the user
  history table now show *before → after* per transaction; the user grid also
  shows the TXN ID and a **Balance After** column (before shown on hover).
- **Model** `wallet/Wallettransfer_model`: `execute()` / `sendToUser()` accept
  `opts['skip_kyc']` + `opts['via']` ('user'|'admin'), capture before/after,
  generate `txn_uid` (`generateTxnUid()`); `validate($…, $skipKyc)`;
  `walletBalances($userId)`.
- **Controller** `admin/wallet/Internaltransfers`: `balances()` (AJAX),
  `do_transfer()` (AJAX, mode self/member, `via='admin'`, `skip_kyc`), passes
  `$users`; grid gains **TXN ID** + **Via** columns.
- **Files:** `models/wallet/Wallettransfer_model.php`,
  `controllers/admin/wallet/Internaltransfers.php`,
  `views/admin/wallet/internal_transfers.php`,
  `views/user/wallet/transfer_wallet.php`, `config/routes.php`,
  `db/wallet_internal_transfer.sql` (idempotent ALTERs: `txn_uid` UNIQUE,
  `to_user_id`, `from/to_before/after`, `via`).
- **How to apply:** re-run `db/wallet_internal_transfer.sql` (idempotent). New
  routes: `admin/finance/internal-transfers/balances` (POST),
  `admin/finance/internal-transfers/do-transfer` (POST).
- **Route repoint:** `internel-transfer` (the URL the admin actually uses) now
  serves this **4-wallet** page (`admin/wallet/Internaltransfers`). The **legacy**
  2-wallet (Currency/Token → `history`) transfer page is preserved at
  `internel-transfer-legacy` for rollback — no code deleted. Both require the
  same `wallet_management` permission, so no admin is locked out.
- **Verified (CLI):** admin self-transfer (skip_kyc on a non-KYC user, via=admin,
  100→90 ex / 0→10 staking, before/after correct); admin member-transfer
  (to_user_id set, sender 90→75 / recipient 0→15); member transfer **without**
  skip_kyc correctly **blocked** with the KYC message; `txn_uid` unique across
  transactions; balances restored after the test run.

---

## 2026-07-03 — Transfer page: recipient picker (default 20 members, name + email)

- **What:** the Recipient field on `user/transfer_wallet` is now a searchable
  dropdown. On focus it shows up to **20 active members** (username, referral
  ID · email); typing filters live (debounced). Picking a row fills the
  referral ID and shows a ✓ confirmation; the sender is excluded and manual
  entry of a valid ID still works (the server validates on submit).
- **Files:** `controllers/user/Transfer_wallet.php` (`search_recipients` — 20
  active users, optional query, excl. self), `config/routes.php`,
  `views/user/wallet/transfer_wallet.php` (dropdown UI + JS).
- **Verified (CLI):** default returns ≤ 20 (self excluded), query filters
  correctly; route guards to login.

---

## 2026-07-03 — Internal transfer changed to MEMBER → MEMBER (send to another user)

- **What:** per clarification, the internal wallet transfer is **not** self
  (own-wallet → own-wallet); it **sends a digital amount to another member's
  account**. Sender picks one of their wallets (Exchange / Earning / Bonus —
  Staking is excluded as it's locked in a stake) and a recipient (by referral
  ID / username / email); the recipient's **same wallet** is credited. Money
  moves via the double-entry ledger (atomic, row-locked).
- **Model** `wallet/Wallettransfer_model`: `sendToUser($from, $recipient,
  $wallet, $amount, …)`, `resolveRecipient()`. Validates: sendable wallet,
  amount > 0, recipient exists + active + not self, sufficient balance; debits
  sender + credits recipient (net of optional fee), records
  `wallet_internal_transfer` with the new `to_user_id`. `history()` now shows
  both **sent** and **received** with direction + counterparty; `adminList()`
  joins sender + recipient.
- **User page** (`user/transfer_wallet`): "To Wallet" replaced by a
  **Recipient** field with a live name lookup (`lookup_recipient`); From Wallet
  keeps only sendable wallets; preview reads "amount Wallet → recipient";
  history table shows Type (Sent/Received ±), Wallet, Counterparty.
- **Admin grid** (Finance → Internal Wallet Transfers): columns now Sender ·
  Recipient · Wallet · Amount.
- **DB:** `wallet_internal_transfer` gained `to_user_id` (recipient).
- **Verified (CLI):** send 120 Exchange A→B (A −120, B +120); self-send,
  unknown recipient, non-sendable Staking, insufficient balance all blocked;
  A's history = Sent→B, B's history = Received←A; admin list shows sender →
  recipient. DB restored.
- **Files:** `models/wallet/Wallettransfer_model.php`,
  `controllers/user/Transfer_wallet.php` (do_transfer → sendToUser +
  lookup_recipient), `views/user/wallet/transfer_wallet.php` (recipient UI +
  history), `views/admin/wallet/internal_transfers.php`, `config/routes.php`.

---

## 2026-07-03 — Internal transfer: dual mode (own wallets + to a member) per full spec

- **Why:** a new spec described own-wallet → own-wallet (Exchange → Bonus),
  while the prior instruction was send-to-another-member. To satisfy both, the
  page now has a **mode toggle**: **Between My Wallets** (self) and **Send to a
  Member**.
- **Self mode** (`execute`): any internal wallet → any OTHER internal wallet
  (Exchange/Earning/Staking/Bonus per the Wallet Rules Table — USDT excluded),
  from ≠ to, precision ≤ 8 dp, KYC-approved, account active, sufficient
  balance. Atomic double-entry ledger (debit + credit, balance_after),
  `wallet_internal_transfer` header (to_user_id NULL), `WTF-…` reference.
- **Member mode** (`sendToUser`): unchanged — send to another member (recipient
  picker of 20, same wallet credited, staking not sendable).
- **Validation now covers the spec checklist:** From≠To · Amount>0 · precision ·
  enough balance · wallet active (USDT excluded) · transfer password · KYC ·
  account active · MySQL transaction · two ledger entries · unique reference ·
  audit (IP/browser/device on the header). OTP + per-wallet "frozen" flag and
  a post-transfer notification remain optional/future (no frozen column yet).
- **History** now labels rows **Internal** (self, neutral), **Sent** (−) or
  **Received** (+); the admin grid shows Sender · Recipient (— for self).
- **As-built note (doc 9 §16A):** the spec's separate `wallet_transfer` /
  `wallet_transfer_ledger` / `wallet_transfer_audit` tables are consolidated to
  `wallet_internal_transfer` + the existing double-entry `wallet_ledger` (the
  spec itself says "reuse the existing transaction table if one exists").
- **Verified (CLI):** self exchange→bonus & earning→staking; same-wallet, USDT,
  >8-dp all blocked; member send works; KYC gate blocks both modes; history
  shows self + sent directions. DB restored.
- **Files:** `models/wallet/Wallettransfer_model.php` (any-to-any pairs,
  precision, KYC in validate), `controllers/user/Transfer_wallet.php` (mode
  branch), `views/user/wallet/transfer_wallet.php` (mode toggle, To-Wallet vs
  Recipient, history badges).

---

## 2026-07-03 — Internal transfer requires KYC approved (strict)

- **What:** a member can transfer funds only if their **KYC is approved**;
  otherwise it is blocked. Enforced strictly server-side at the money-mover so
  no path bypasses it.
- **Layers:**
  1. `Wallettransfer_model::sendToUser()` — rejects unless
     `users.kyc_status = 'approved'` (bulletproof; any caller enforced).
  2. `Transfer_wallet::do_transfer()` — early 403 with a clear message.
  3. UI (`transfer_wallet`) — amber "KYC required" banner + a link to
     `user/profile`, and the submit button is disabled and labelled
     "Complete KYC First" until KYC is approved.
- **Verified (CLI):** kyc=pending → blocked ("Your KYC must be approved before
  you can transfer funds."); kyc=approved → transfer succeeds. DB restored.
- **Files:** `models/wallet/Wallettransfer_model.php`,
  `controllers/user/Transfer_wallet.php`, `views/user/wallet/transfer_wallet.php`.

---

## 2026-07-03 — Browser Controls (§16) on common footers

- **What:** added the proposal §16 browser controls — **disable right-click,
  F12, Ctrl+Shift+I / J / C (dev tools), and Ctrl+U (view source)** — site-wide.
- **How:** one reusable partial `views/partials/browser_controls.php` (guarded
  with `window.__bmanBrowserGuard` so it binds only once) included from the
  three common footer/script partials: `admin/Layout/common_script.php`
  (admin), `user/layout/common_script.php` (member v1) and
  `user/layout/v2/user_header.php` (member v2) — covering all admin + member
  pages.
- **Note:** these are deterrents only (trivially bypassable by design of the
  web) but implement the requested §16 controls. Easy to gate behind a
  site-setting flag later if you want an admin on/off switch.
- **Files:** `views/partials/browser_controls.php` (new) + the three includes.

---

## 2026-07-03 — Security: stop the browser saving the Treasury private key

- **Problem:** Chrome's "Save password?" prompt appeared on the Treasury key
  field (it treated the derived address as a username and the key as a
  password) — a serious leak: the private key would land in the browser's
  password manager / sync.
- **Fix:** the Treasury secret is no longer a `type="password"` input. It is a
  `type="text"` field visually masked with CSS `-webkit-text-security:disc`
  (+ `autocomplete="off"`, `data-lpignore`, `data-form-type="other"`,
  `spellcheck="off"`; the form is `autocomplete="off"`). With no password field
  present, no browser offers to save it. The eye toggle now flips the CSS mask
  (reveal/hide) instead of the input type. Masking, live-derive and the last-5
  hint all still work.
- **Files:** `views/admin/master/token_settings.php`.

---

## 2026-07-03 — Treasury key field: show/hide, live derive, last-5 hint

- **Show/hide (eye) toggle** on the Treasury key/phrase input.
- **Instant address derivation** — as the admin types or pastes, once the value
  is a full 64-hex key or a 12/24-word phrase the wallet address auto-fills the
  read-only box below (debounced 350 ms), with a ✓ valid / ✗ invalid note. The
  Derive Address button still works too.
- **Last-5 hint after save** — a stored key is shown only as its **last 5
  characters** (`key stored ···2ff80`, and the input placeholder "current key
  ends in ···2ff80 — enter a new key/phrase to replace"). The full key is never
  sent to the browser. Model `publicRow()` decrypts server-side to expose only
  the last 5; the controller sanitises every row before render.
- **Update rule:** leaving the field blank keeps the current key; entering a
  new value updates it **only if it's a valid key/phrase** — an invalid entry
  makes the save fail (no overwrite). Verified: last-5 matches the stored key,
  encrypted key stripped from the browser payload.
- **Files:** `models/Tokenmaster_model.php` (`publicRow` last-5),
  `controllers/admin/master/Tokenmaster.php` (sanitise rows),
  `views/admin/master/token_settings.php` (eye toggle, live derive, hint).

---

## 2026-07-03 — Token Settings: Treasury by key/phrase, address auto-derived

- **What:** in Token Settings §5 the admin no longer types the Treasury wallet
  address. They enter **only** the Treasury **private key** (64-hex) **or a
  mnemonic phrase** (12/15/18/21/24 words); the wallet **address is derived
  automatically** and shown in a read-only field. A **Derive Address** button
  previews it live before saving.
- **How:** `Web3bman::importSecret($secret)` accepts a hex key or a phrase
  (phrase via the existing ETH_MASTER BIP39/BIP44 wallet, default Ethereum path)
  and returns `{private_key, address}`. `Tokenmaster_model::saveSetting` now
  takes `treasury_secret`, derives the address (sets `treasury_wallet`) and
  stores the AES-encrypted key (`treasury_pk_enc`) — the secret is never
  returned to the browser (a "key stored" badge shows instead; blank keeps the
  current key). New AJAX `derive_treasury` powers the live preview.
- **Verified (CLI):** phrase → checksummed address + 64-hex key; private key →
  matching address; invalid input rejected; saving a phrase derives + stores
  the address, and the decrypted key derives back to the same address. DB
  restored after test.
- **Files:** `libraries/Web3bman.php` (`importSecret`),
  `models/Tokenmaster_model.php` (treasury_secret → derive),
  `controllers/admin/master/Tokenmaster.php` (`derive_treasury`),
  `views/admin/master/token_settings.php` (key/phrase input + Derive button +
  read-only derived address), `config/routes.php`.

---

## 2026-07-03 — Fix: transfer page blank + transfer password on Security tab

- **Fix (blank transfer page):** `user/transfer_wallet` rendered blank because
  the view wrapped content in `.main-wrapper`/`.content-area` — classes the v2
  member theme doesn't define — so the fixed sidebar overlapped an unstyled
  content block. Changed to the theme's real structure
  (`<div class="app-container"> … <main class="main-content"> …`), matching the
  working KYC/dashboard pages. Verified: renders 50 KB with all content, no
  errors. Controller now also passes `$user`.
- **Transfer password on Profile → Security:** added a **Transfer Password**
  card (SET / NOT SET badge) that collects login password + new PIN + confirm
  and saves the hashed PIN to `users.transfer_password` via
  `member/profile/set_transfer_password` (verifies the login password first,
  min 4 chars). This is the PIN required to authorize internal wallet transfers
  (doc 9) — users can now set it from the profile Security tab as well as the
  transfer page.
- **Files:** `views/user/wallet/transfer_wallet.php` (wrapper fix),
  `controllers/user/Transfer_wallet.php` (`$user`),
  `views/user/profile/view.php` (Security-tab card + `saveTransferPassword()`),
  `controllers/user/usersettings/Profile.php` (`set_transfer_password`),
  `config/routes.php`.

---

## 2026-07-03 — Token Settings simplify (§5/§6) + Internal Wallet Transfer (doc 9)

> Reference: [9_INTERNAL_WALLET_TRANSFER.md](9_INTERNAL_WALLET_TRANSFER.md).

- **Token Settings §5 simplified** (per screenshot): Section 5 now has only the
  **Treasury** and **Deposit** wallets plus a **Treasury Private Key** field —
  AES-encrypted on save (`token_settings.treasury_pk_enc`), validated to match
  the Treasury address, **never returned to the browser** (a "key stored" badge
  shows instead), blank keeps the existing key. Removed Gas / Bonus / Reserve /
  Cold wallet fields and the whole **§6 Smart Contracts** section (USDT→BMAN is
  one flow signed by the Treasury key; per-purpose contracts aren't needed).
  Model exposes `treasuryPrivateKey()` (server-side signing only) + `publicRow()`
  (strips the secret). Verified: encrypt/validate/decrypt/no-leak/blank-keeps.
- **Recheck** (as requested): wallet transfer (Exchange→Staking, overdraw
  guarded) and **USDT→BMAN deposit** (25 USDT → 500 BMAN @ rate 20, credited
  once, no double-credit) both re-verified. Withdrawal confirmed as a **manual
  admin request** (doc 8 §5) — no server key needed to start (admin pastes the
  on-chain hash on approval).
- **Internal Wallet Transfer (doc 9)** — user moves balance between their OWN
  four internal wallets (exchange/earning/staking/bonus); **USDT excluded**
  (blockchain asset). Money moves via `Walletledger` (atomic, row-locked,
  `balance_after`), recorded in `wallet_internal_transfer` (`ref`
  `WTF-YYYYMMDD-XXXXXXXX`, links to the two ledger rows). Allowed-pair matrix
  enforced (Exchange→Earning/Staking/Bonus · Earning→Exchange/Bonus ·
  Bonus→Exchange/Staking · Staking→Exchange/Bonus). Transfer password (hashed
  PIN, new `users.transfer_password`) required.
  - Files: `db/wallet_internal_transfer.sql`,
    `models/wallet/Wallettransfer_model.php`,
    `controllers/user/Transfer_wallet.php` (index / do_transfer /
    set_transfer_password), `views/user/wallet/transfer_wallet.php` (two-tab UI),
    `controllers/admin/wallet/Internaltransfers.php` +
    `views/admin/wallet/internal_transfers.php` (Finance → Internal Wallet
    Transfers grid + detail modal), `config/routes.php`, sidebar.
  - As-built note: consolidated to one header table + the existing double-entry
    ledger (the separate `wallet_transfer_ledger`/`wallet_transfer_audit` mirror
    tables were redundant — the ledger already carries both rows + balance_after).
  - Validated (CLI): allowed/blocked pairs, USDT exclusion, same-wallet,
    insufficient-balance, WTF ref, history/detail/adminList — all pass.
- **How to apply:** run `db/wallet_internal_transfer.sql`; set the Treasury key
  in Token Settings when ready to automate BMAN sends (not needed for transfers
  or deposit detection).

---

## 2026-07-02 — Production wallet: double-entry ledger + auto deposit listener

> Full reference + deep-dive: [8_WALLET_DEPOSIT_WITHDRAW.md](8_WALLET_DEPOSIT_WITHDRAW.md).

- **Why:** production feedback — balances must never be updated ad-hoc; every
  deposit, chain tx, internal credit and admin action is tracked separately;
  deposits must be auto-detected (no manual hash entry) and never double-credited.
- **Double-entry ledger** (`wallet_ledger` + `Walletledger_model`): every
  movement appends a row (credit XOR debit) with `balance_after`, updating the
  `user_wallets` column in one transaction with `SELECT … FOR UPDATE`.
  `UNIQUE(tx_hash, wallet_type)` makes double-crediting a tx impossible; debits
  are overdraw-guarded. Wallets usdt/exchange/earning/staking/bonus; reference
  types deposit/withdrawal/stake_purchase/roi/bonus/binary_commission/
  rank_reward/wallet_transfer/admin_adjustment. `credit/debit/transfer/statement`.
- **Deposit listener** (`Depositlistener_model` + `Depositcron`): detects
  incoming USDT to custodial addresses and credits confirmed deposits — **no
  private key**. Two providers via Token Settings `deposit_scan_mode`:
  **bscscan** (Etherscan-v2 token-transfer API, free key — public dataseed RPCs
  block `eth_getLogs`, confirmed live) and **rpc** (`eth_getLogs` on a
  log-capable node, with `wallet_scan_state` cursor). Flow: detect
  (`wallet_deposits`, unique tx) → confirmations vs `minimum_confirmations` →
  credit USDT + convert USDT→BMAN @ active rate → credit Exchange, each keyed by
  tx_hash so it happens exactly once.
- **Admin Wallet Monitor:** added **Detect Deposits (auto)** (runs the listener,
  Super-Admin) + **Deposits** list (tx, USDT, BMAN, confirmations, status).
- **Runtime QR:** deposit-address QR now generated in-browser
  (`assets/js/vendor/qrcode.min.js`) — no reliance on a pre-generated PNG
  (server InfiQr PNG kept as fallback). Address + Copy + BSC network shown.
- **Token Settings:** new Section-1 fields — deposit scan mode, Explorer API URL,
  Explorer API Key (columns added to `token_settings`; wired into the model).
- **Admin-side works with NO private key — verified.** Balance reads, deposit
  detection and crediting are all read-only chain + DB. CLI proof: mock
  confirmed 10 USDT → 200 BMAN credited (rate 20), re-run credited 0 (no
  double-credit), ledger double-entry/overdraw/transfer correct, live scan
  returns a clear "set a BscScan API key" message. Only *sending BMAN out*
  (withdrawals) needs the Treasury key — deferred; admins can meanwhile approve
  by pasting the tx hash.
- **DB:** `db/wallet_production.sql` (idempotent, applied) — `wallet_ledger`,
  `wallet_deposits`, `wallet_scan_state`, `user_wallets` balance columns +
  lifetime totals; `token_settings` scan columns.
- **Files:** `models/Walletledger_model.php`, `models/Depositlistener_model.php`,
  `controllers/Depositcron.php`, `controllers/admin/wallet/Walletmonitor.php`
  (scan_deposits/deposits), `views/admin/wallet/wallet_monitor.php`,
  `views/admin/master/token_settings.php`, `models/Tokenmaster_model.php`,
  `views/user/profile/view.php` (runtime QR), `assets/js/vendor/qrcode.min.js`,
  `config/routes.php`.
- **How to apply:** run `db/wallet_production.sql`; add a free BscScan/Etherscan
  API key in Token Settings (or set scan mode to a log-capable RPC) to enable
  live auto-detect; schedule `php index.php depositcron run` (~15 s).

---

## 2026-07-02 — Custodial wallet management: unique deposit address, QR, on-chain monitor

- **Reviewed first:** wallet generation already exists (`Mlm_model::create_wallet`,
  ETH_MASTER BIP39) storing address/mnemonic/key/QR in `user_wallet`, but 5 of
  14 users had no wallet and it encrypts keys via an external ADROX API. New
  addresses now generate **locally** (no external dependency).
- **Unique deposit address (check-or-create):** `Custodialwallet_model::ensureAddress($uid)`
  returns the existing `user_wallet` row, or generates a fresh BEP-20 wallet via
  `Web3bman` (local secp256k1), AES-encrypts the key (CI `encryption_key`),
  renders a QR PNG with the existing **InfiQr** generator, and inserts with a
  uniqueness guard. A `UNIQUE` index was added on `user_wallet.wallet_address`.
  Called on the profile page load, so every user gets one when they open the
  Bank tab.
- **Five wallets (proposal §3):** `fiveWallets()` returns USDT (from
  `user_wallets.usd_balance`) + Exchange/Earning/Staking/Bonus (the §3A ledger).
- **User Bank tab** (`user/profile`): new **Wallet & Deposit Address** section —
  the 5 wallet balances, the unique deposit address with **QR + Copy**, a
  **Check On-chain Balance** button (live RPC read vs our DB, flags a new
  deposit), plus Deposit History, Withdraw History and the Wallet Monitor Log.
- **Admin Wallet Monitor** (Finance → **Wallet Monitor**, `admin/wallet-monitor`):
  lists every custodial wallet, **Check**/**Scan All** reads real on-chain USDT/BNB
  vs the DB record, highlights positive differences, and **Reconcile**
  (Super-Admin) credits the difference into `user_wallets.usd_balance`, records a
  `custodial_deposits` row and a `wallet_monitor_log` entry. This is the "free
  wallet monitor tool" — no third-party service.
- **On-chain reads only.** Nothing is broadcast; monitor/check are read-only,
  reconcile is a DB credit. Withdrawals (on-chain send) remain the future payout
  engine's job via `Web3bman::sendToken`.
- **DB:** `db/custodial_wallets.sql` (idempotent, applied) — `custodial_deposits`,
  `wallet_monitor_log`, unique index on `user_wallet.wallet_address`.
- **Config:** set `$config['encryption_key']` (was empty; the app uses ADROX
  externally and no CI Encryption elsewhere) — **required** for custodial key
  storage. **Deploy note:** set your own key in production before generating
  wallets; keys already stored are AES-encrypted with it.
- **Files:** `models/Custodialwallet_model.php` (ensureAddress/monitor/reconcile/
  fiveWallets/deposits/monitorLog), `controllers/user/usersettings/Profile.php`
  (wallet data + `wallet_check`), `views/user/profile/view.php` (Bank tab
  section), `controllers/admin/wallet/Walletmonitor.php`,
  `views/admin/wallet/wallet_monitor.php`, `config/routes.php`,
  `config/config.php` (encryption_key), sidebar (Finance → Wallet Monitor).
- **Validated:** CLI — ensureAddress generates a unique valid `0x…` address +
  QR, idempotent, key AES round-trips to the same address; monitor reads live
  BSC (confirmed real BNB dust on user 1's address), diff math + reconcile guard
  correct; admin list query returns 9 wallets. Test data removed; routes guard
  to user/admin login.
- **How to apply:** run `db/custodial_wallets.sql`; ensure `encryption_key` is
  set; set the USDT + BMAN contract addresses in Token Settings to enable
  on-chain reads.

---

## 2026-07-02 — User profile form: capture all §1 fields (→ shown in admin)

- **What:** the member **Profile Settings** page (`user/profile`) now captures
  the full proposal §1 profile: existing First/Last Name, Email, Phone,
  Country, Timezone **plus new** Gender, Date of Birth, Address Line 1,
  Address Line 2, State, Pin Code. Saving them writes to the `users` table,
  so they immediately appear on the admin **Member Profile** card
  (`view-user/{id}`) — completing the loop user-entry → admin-display.
- **Mapping:** Address Line 1 = `address`, Pin Code = `zipcode`,
  Mobile = `contact`; State + Address Line 2 = the columns added in
  `db/user_profile_fields.sql`. Gender normalised to male/female/other; Pin
  validated (3–12 alphanumerics). New fields are optional — existing required
  rules (name/contact/country/timezone) unchanged, fully backward compatible.
- **Files:** `controllers/user/usersettings/Profile.php` (`profile_update()`
  now saves gender/dob/address/address_line2/state/zipcode),
  `views/user/profile/view.php` (new form fields).
- **Validated:** DB round-trip via `Users_model::update_user()` — all six
  fields persist and restore. User page → `user/in`, admin pages → `admin/login`
  when unauthenticated (guards intact).
- **Admin history / user management:** already in place — Members Management
  (`network-member`) list → **View** → the Member Profile card renders every
  field. No further change needed there.

---

## 2026-07-02 — Admin: Member Profile card (all §1 profile fields)

- **What:** the admin **View User Profile** page (`view-user/{id}` →
  `admin/member/profile`) now shows a read-only **Member Profile** card at the
  top with every proposal §1 field: Full Name, Email ID, Mobile Number,
  Gender, Date of Birth, Address Line 1, Address Line 2, State, Country, Pin
  Code — plus Username/Referral ID, Sponsor, Placement, Registered date, and
  Active + KYC status badges. Previously the page was wallet/commission
  dashboards only and never displayed the personal fields.
- **DB:** `db/user_profile_fields.sql` (guarded, applied) adds the two missing
  columns `users.state` + `users.address_line2` (nullable, backward
  compatible). Full Name reads `first_name last_name`, falling back to
  `name`/`username`; Address Line 1 = existing `address`; Pin Code =
  `zipcode`; Mobile = `contact`.
- **Files:** `controllers/admin/member/Membermanagement.php` (`viewuser()`
  now passes the full user row + sponsor), `views/admin/member/profile.php`
  (new card), `db/user_profile_fields.sql`.
- **How to apply:** run `db/user_profile_fields.sql`; open any member via
  Members Management → View. No route change.

---

## 2026-07-02 — Fix: BMAN token logo preview in Token Settings edit modal

- **Bug:** the uploaded BMAN logo saved and served correctly (list showed it,
  file on disk, HTTP 200) but the **Edit modal never previewed it** — the file
  input is (correctly) skipped when re-filling the form and there was no
  `<img>` to show the existing `bman_logo`, so it looked like the upload was
  lost.
- **Fix:** added a logo preview thumbnail in section 2. `fillForm()` now sets
  it from the saved `bman_logo` (with a "choose a file only to replace it"
  note) on edit, clears it on Add, and a `change` listener shows a live local
  preview when a new file is picked. Upload/persist path was already correct —
  no controller/model change.
- **File:** `views/admin/master/token_settings.php`.

---

## 2026-07-02 — Custodial wallet ledger + Token Settings placeholders

- **Custodial question answered** ("give BMAN without the admin private key"):
  new doc [7_TOKEN_WALLET_INTEGRATION.md](7_TOKEN_WALLET_INTEGRATION.md)
  explains the model — BMAN is custodial, so giving a user tokens (purchase,
  ROI, bonus, matching, admin grant) is an **internal ledger credit with no
  private key and no blockchain**. A key is needed **only** at withdrawal, and
  it's the treasury/gas key (via `Web3bman::sendToken()`), never a per-user key.
- **New model** `Custodialwallet_model` — single place for internal BMAN
  balances across the four §3A wallets (exchange/earning/staking/bonus) on the
  existing `wallet_transactions` ledger: `credit()`, `debit()` (overdraw-
  guarded), `move()` (e.g. Exchange → Staking on stake purchase), `balance()`,
  `balances()`. Validated by CLI: +100 exchange → move 40 to staking →
  exchange 60 / staking 40, +25 bonus, overdraw blocked.
- **Token Settings edit popup** — concrete BSC placeholders on every field
  (RPC `https://bsc-dataseed.binance.org`, chain 56/97, USDT
  `0x55d3…7955`, decimals 18, rate `500`, gas 210000/5 gwei, wallet fields
  `0x…`, per-wallet purpose hints). No behaviour change; guidance only.
- **Files:** `models/Custodialwallet_model.php`,
  `views/admin/master/token_settings.php` (placeholders),
  `docs/7_TOKEN_WALLET_INTEGRATION.md` (+ index entry).

---

## 2026-07-02 — Web3 integration: BEP-20 wallet + signed transfers (reads Token Settings)

- **What:** installed a real web3 stack and a CI library so the platform can
  generate wallets, read on-chain balances and send BMAN/USDT (BEP-20) or BNB
  with a private key — all driven by the **active Token Settings** row (no
  hardcoded chain id, RPC, contract, decimals or gas).
- **Library stack (isolated):** `application/third_party/web3bman/` has its
  own composer vendor with `web3p/web3.php` + `web3p/ethereum-tx`
  (RLP + secp256k1 + EIP-155 signing), `simplito/elliptic-php`,
  `kornrunner/keccak`. Kept separate from the CI root and the existing
  `ETH_MASTER` vendor to avoid dependency conflicts.
- **CI library** `application/libraries/Web3bman.php`:
  - `generateWallet()` — offline address + private key (EIP-55 checksum).
  - `addressFromPrivate()`, `toChecksum()`.
  - `getBnbBalance($addr)`, `getTokenBalance($addr[, $contract])` — read-only.
  - `sendToken($fromPrivateKey, $to, $amount[, $contract])` — builds the
    `transfer(address,uint256)` call, fetches nonce + gas, signs OFFLINE with
    the configured chain id, broadcasts via `eth_sendRawTransaction`.
  - `sendBnb($fromPrivateKey, $to, $amount)` — native transfer (gas funding).
  - `toUnits()/fromUnits()` — bcmath scaling by token decimals.
  - `encryptKey()/decryptKey()` — AES-256 (CI `encryption_key`) for storing a
    sending key encrypted. **Keys are never persisted by the library**; the
    caller decrypts just-in-time. Wallet *addresses* (public) live in Token
    Settings; private keys do not.
- **Admin tools on Token Settings** (safe operations only): **Check Balance**
  (read-only BNB + BMAN for any address) and **Generate Wallet** (Super Admin;
  key shown once, never stored; audited as `wallet_generated`). No
  "send funds" button is wired — on-chain transfers belong to the future
  payout/withdrawal engine (which will call `sendToken()`), guarded and
  confirmed there rather than fired from a settings page.
- **Files:** `third_party/web3bman/composer.json` (+ vendor),
  `libraries/Web3bman.php`, `controllers/admin/master/Tokenmaster.php`
  (generate_wallet / check_balance), `views/admin/master/token_settings.php`
  (Wallet Tools card), `config/routes.php`.
- **Validated:** CLI self-test — wallet gen + address round-trip, known test
  vector `0x4f3e…3b1d → 0x90F8bf6A…c9C1`, amount round-trips (25 → 25×10¹⁸),
  offline BEP-20 transfer signing (valid 346-char EIP-155 raw tx). Live BSC
  read confirmed: `eth_getBalance` + `eth_call balanceOf` decode real
  BNB/USDT balances. Broadcast (`eth_sendRawTransaction`) intentionally not
  fired with real funds — signing path proven; sending is ready for the
  payout engine.
- **How to apply:** `composer install` in `application/third_party/web3bman/`
  (done); set `$config['encryption_key']` before storing any sending key;
  set the real BMAN contract address in Token Settings to enable token reads.
- **Note — Binary Matching Bonus (§9):** the *setting* (10% = 8% Earning + 2%
  Staking) is complete on **Bonus & Matching**; the payout *engine* that
  credits binary pairs is the Phase-B task (task board in
  [0_INDEX.md](0_INDEX.md)), not yet built.

---

## 2026-07-02 — Token Settings Master (blockchain single source of truth) + Master menu restructure

- **What:** new **Master → Token Settings** module
  (`admin/master/token-settings`) — the single source of truth for all
  blockchain configuration; no more hardcoded values. Plus the Master menu
  was restructured to the recommended 9-item layout.
- **List page:** ID, Network (blockchain + chain id), Token (BMAN/USDT with
  logo), Contract Address (shortened + one-click copy), Exchange Rate (with
  effective-from), Status (single ACTIVE badge), Last Updated, Updated By,
  Actions.
- **Edit form (7 sections):** ① Network (mainnet/testnet, blockchain, chain
  id, RPC URL, explorer URL, **Test RPC** button — live JSON-RPC
  `eth_chainId` check with latency + chain-id match), ② BMAN token (name,
  symbol, decimals, contract, logo upload, min/max transfer, enable),
  ③ USDT token (name, symbol, decimals, contract, min deposit, min/max
  withdrawal, enable), ④ Exchange rate (method: 1 USDT = X BMAN or
  1 BMAN = X USDT, rate, effective-from, live wording preview), ⑤ Wallets
  (treasury, deposit, gas, bonus, reserve, cold), ⑥ Smart contracts
  (staking, bonus, referral, ROI), ⑦ Blockchain params (min confirmations,
  gas limit, gas price, tx timeout, retry count).
- **Rules enforced server-side:** RPC/Explorer/decimals required; exchange
  rate > 0; every contract/wallet must be a valid `0x…` (40-hex) address;
  unique network + chain id; **only one active configuration** (activating
  one deactivates the rest); the active config cannot be disabled. Super
  Admin (`admin_roll = 1`) modifies rate/contracts/wallets/RPC/chain; other
  admins view + enable/disable (server 403 otherwise).
- **Rate bridge (backward compatibility):** the ACTIVE row's exchange rate
  is mirrored into the legacy `token_config.currency_value`, so every
  existing flow (`token_info()`, Make-Investment, commission engine) uses
  the latest active rate with **zero code changes**. Old purchases are
  unaffected — the rate is already snapshotted per purchase
  (`user_investment.csq_price`). New purchases use the latest active rate.
  Package module untouched (packages stay BMAN-only).
- **Model helpers for engines:** `Tokenmaster_model::activeSettings()` and
  `convertUsdtToBman($amount)` (handles both calculation methods) — the
  deposit → convert → credit flow must read these, never hardcode.
- **Audit:** every create / edit / rate_changed / activate writes
  `token_settings_audit` with old + new JSON, admin, **IP address** and
  date — viewable in the page's Audit Log modal.
- **Master menu restructured** (responsibilities separated, no duplicate
  entries): Master → Token Settings · Coin Distribution · Staking Packages ·
  Staking Plans · ROI Settings · Bonus Coin Settings · Wallet Settings
  (→ single withdraw page) · Blockchain Settings (→ token settings §1/§7) ·
  System Settings (→ site settings). **Staking Management** now holds only
  Rank Achievement + Rank Power & Incentive.
- **DB:** `db/token_settings.sql` (idempotent, applied) — `token_settings`
  (all spec fields; seeded with one active BSC-mainnet row whose rate was
  taken from the live `token_config.currency_value`, so nothing changed for
  users) + `token_settings_audit`.
- **Files:** `models/Tokenmaster_model.php`,
  `controllers/admin/master/Tokenmaster.php`,
  `views/admin/master/token_settings.php`, `config/routes.php`
  (`admin/master/token-settings*`), `views/admin/Layout/admin_sidebar.php`
  (Master 9 items, Staking Management slimmed).
- **Validated:** CLI smoke test — rate ≤ 0 / empty RPC / malformed address /
  duplicate network+chain all rejected; testnet config created + activated →
  single-active enforced, legacy rate bridged (20 → 500 → restored 20),
  conversion helper correct (100 USDT → 2000 BMAN @20, 50000 @500); active
  config can't be disabled; audit logs create/activate/rate_changed with IP.
  Test data fully removed; route redirects unauthenticated → `admin/login`.
- **How to apply:** run `db/token_settings.sql`, deploy the PHP files.
  Optional sub-admin key: `token_settings_master` (legacy `payment_settings`
  also grants page access).

---

## 2026-07-02 — Single Withdraw Settings page (merged staking plan rules)

- **What:** withdraw configuration was split confusingly across two pages —
  the global `withdraw-settings` (status, min/max, fee, daily/monthly limits,
  %-or-fiat fee type, user/admin notifications) and the per-plan withdraw
  fields on **Admin → Staking → Plans**. Now **`withdraw-settings` is the
  single withdraw page**:
  - It gained a **"Staking Plan Withdraw Rules (BMAN)"** card — one row per
    Regular/Combo plan with withdraw window (days) and min/max in BMAN and
    USDT, saved via the existing `admin/staking/plans/save/{id}` endpoint
    (same validation: min ≤ max, no negatives). Fixed plan needs no limits
    (withdraw after maturity only).
  - The Staking Plans page **no longer edits withdraw fields** — its cards
    keep credit days / durations / combo split and show the current withdraw
    rule read-only with a link to Withdraw Settings.
- All the existing withdraw features stay as-is on the same page: Withdraw
  Status, Min/Max Withdraw, Withdraw Fee, Daily Limit (0 = unlimited),
  Monthly Limit (0 = unlimited), Fee type Percentage/Fiat, Notification to
  User, Notification to Admin.
- **Files:** `controllers/admin/settings/Withdrawsettings.php` (passes the
  Regular/Combo plans), `views/admin/settings/withdraw-edit-settings.php`
  (new card + AJAX save), `views/admin/staking/plans.php` (withdraw fields
  removed, link added). No SQL, no route changes.

---

## 2026-07-02 — Coin Distribution Master (§3A) + purchase-flow integration

- **What:** new **Master → Coin Distribution** module
  (`admin/master/coin-distribution`) managing how purchased amounts split
  across the Exchange / Earning / Staking / Bonus wallets, plus integration
  into the existing purchase module. No new project, no architecture change —
  existing layout, auth, CRUD, wallet ledger and purchase flow reused.
- **List page:** ID, name (+description), the four wallet %, computed Total %,
  Default badge, Status switch, Created At, Actions — with Status / Default
  filters, debounced search, and CSV export honouring the current filters.
- **Add/Edit modal:** name, description, four percentages, Active, Default.
  Live total indicator; the Save button is disabled until the total is
  exactly 100.
- **Rules enforced server-side:** each % ≥ 0; total must equal exactly 100;
  unique name; only one default (setting a new default clears the old one
  atomically); the default cannot be disabled or deleted; an option already
  used by purchases cannot be deleted (disable instead). Role split: Super
  Admin (`admin_roll = 1`) may add / edit / delete / set default; other
  admins view + enable/disable only (server returns 403 otherwise).
- **Audit:** every create / edit / percentage_changed / enable / disable /
  default_changed / delete writes `coin_distribution_audit` (old + new JSON
  snapshots, admin, timestamp) — viewable from the page's Audit Log modal.
- **Purchase integration** (`Walletmanagement::makeinvestment_post`): the
  Make-Investment form gained a Coin Distribution selector (default option
  preselected) with a live preview (e.g. 100 → Exchange 80 · Earning 10 ·
  Staking 10 · Bonus 0). On confirmation the system snapshots option id +
  percentages + computed amounts into `coin_distribution_histories`
  (purchase_id = investment id) and credits the wallets through the existing
  `wallet_transactions` ledger (`source='coin_distribution'`). Requests
  without the field fall back to the default option — fully backward
  compatible; existing history/commission writes untouched.
- **DB:** `db/coin_distribution.sql` (idempotent, applied) —
  `coin_distribution_options` (7 options seeded from §3A, Option 1 default),
  `coin_distribution_histories`, `coin_distribution_audit`, plus a
  backward-compatible extension of `wallet_transactions.tx_type` enum
  (added `exchange`,`earning`,`staking`; existing values untouched, and
  bonus credits keep flowing into `Wallet_model::getBonusBalance()`).
- **Files:** `models/Coindistribution_model.php`,
  `controllers/admin/master/Coindistribution.php`,
  `views/admin/master/coin_distribution.php`,
  `controllers/admin/wallet/Walletmanagement.php` (selector data + snapshot/
  credit block), `views/admin/wallet/investment_management.php` (selector +
  preview), `config/routes.php`, sidebar: new **Master** group.
- **Also fixed:** ROI-structure edit gate — `admin_roll = 1` is this app's
  Super Admin (per `admin_members` seed), so ROI editing is now allowed for
  roll 1 and read-only for sub-admins (previously inverted).
- **Validated:** CLI smoke test — total≠100 / negative / duplicate-name
  rejected; create-as-default clears previous default (single default
  verified); disabling/deleting the default rejected; preview of 100 split
  60/20/10/10; history row + 4 ledger rows written; delete of a used option
  rejected; audit shows create → default_changed → percentage_changed. Test
  data fully cleaned; route redirects unauthenticated → `admin/login`.
- **How to apply:** run `db/coin_distribution.sql`, deploy the PHP files.
  Optional sub-admin permission key: `coin_distribution_master` (the
  existing `wallet_management` key also grants page access).

---

## 2026-07-02 — Staking module: Bonus Coin (§7) & Binary Matching (§9) admin

> Full reference: [6_STAKING_PACKAGES_PLANS_ROI.md](6_STAKING_PACKAGES_PLANS_ROI.md) §10.3.

- **What:** new screen **Admin → Staking Management → Bonus & Matching**
  (`admin/staking/bonus-settings`) — completes the admin-side setups for
  proposal §4–§12. Four cards:
  1. **Staking Bonus (§7)** — default bonus % (25) + "apply to all packages".
  2. **Bonus Coin Reduction (§7)** — enabled, interval days (60), reduction %
     (50); consumed by the future reduction cron.
  3. **Bonus Coin Transfer (§7)** — enabled, allowed recipients (direct
     Left/Right sponsored member), email-OTP + transfer-password toggles;
     guard against enabling transfer with no recipient side.
  4. **Binary Matching Bonus (§9)** — total % (10) = Earning % (8) +
     Staking % (2), live sum hint + server-side equality guard.
- **DB:** `db/staking_bonus_settings.sql` (idempotent, applied) —
  `staking_bonus_settings` single-row config seeded with proposal values.
- **Files:** `models/Staking_model.php` (bonusSettings/saveBonusSettings/
  applyBonusDefaultToPackages), `controllers/admin/staking/Bonussettings.php`,
  `views/admin/staking/bonus_settings.php`, `config/routes.php`
  (`admin/staking/bonus-settings*`), sidebar entry **Bonus & Matching**
  (permission: `staking_management` OR legacy `commission_settings`).
- **Validated:** CLI smoke test — bad matching split (7+2≠10) rejected,
  interval >365 rejected, transfer-without-recipient rejected, bonus >100
  rejected, apply-to-packages works; values reverted to proposal defaults;
  route redirects unauthenticated → `admin/login`.
- **How to apply:** run `db/staking_bonus_settings.sql`, deploy the PHP files.

---

## 2026-07-02 — Staking module: Rank Power (§11) & Group Incentive Ceiling (§12) admin

> Full reference: [6_STAKING_PACKAGES_PLANS_ROI.md](6_STAKING_PACKAGES_PLANS_ROI.md) §10.2.

- **What:** new screen **Admin → Staking Management → Rank Power & Incentive**
  (`admin/staking/rank-power`) with three cards:
  1. **Rank Power rules (§11)** — enable/disable, reset cycle days (default
     60), "controls group-incentive qualification" toggle, minimum power tier
     to qualify (Tier 0–10 dropdown from `staking_ranks`), auto-open-next-cycle
     flag for the future cron.
  2. **Power Cycle** — current-cycle card (window + days left), cycle history
     with per-cycle member counts, **Reset Now** button (closes the open cycle
     → power ranks reset, opens the next `cycle_days` window). Power rank is
     kept fully separate from the permanent Achievement Rank.
  3. **Group Incentive Ceiling (§12)** — inline stake → ceiling grid (amber
     unsaved cells, bulk AJAX save). Writes `staking_packages.group_ceiling` —
     the same field the Packages screen edits (single source of truth).
- **DB:** `db/staking_rank_power.sql` (idempotent, applied) —
  `staking_rank_power_settings` (seeded: enabled, 60 days, controls
  qualification), `staking_rank_power_cycles`, `user_rank_power` (per-user
  power rank per cycle; filled by the future evaluation engine).
- **Files:** `models/Staking_model.php` (powerSettings/savePowerSettings/
  currentPowerCycle/powerCycles/resetPowerCycle/saveCeilings),
  `controllers/admin/staking/Rankpower.php`,
  `views/admin/staking/rank_power.php`, `config/routes.php`
  (`admin/staking/rank-power*`), sidebar entry **Rank Power & Incentive**.
- **Validated:** CLI smoke test — settings guards (cycle 1–365), first-cycle
  start, reset (close #1 → open #2), ceiling update + negative-value guard +
  revert; route redirects to `admin/login` unauthenticated.
- **How to apply:** run `db/staking_rank_power.sql`, deploy the PHP files.

---

## 2026-07-02 — Staking module: admin side (Packages · Plans · ROI · Ranks)

> Full reference: [6_STAKING_PACKAGES_PLANS_ROI.md](6_STAKING_PACKAGES_PLANS_ROI.md).

- **What:** delivered the admin side of the staking proposal — four new screens
  under **Admin → Staking Management**:
  1. **Staking Packages** — CRUD for the 9 stake amounts (5,000 → 500,000
     BMAN) with bonus % (§7, default 25), group-incentive ceiling (§12),
     ▲▼ reorder, enable/disable; delete blocked while stakes exist.
  2. **Staking Plans** — Fixed / Regular / Combo cards: monthly credit days
     (5,15,25), 30-day withdraw window, min/max withdraw BMAN (3000/10000) &
     USDT (30/100) all admin-adjustable, combo 50/50 split (must total 100),
     duration ticks (2/3/5y), enable/disable.
  3. **ROI Structure** — inline-editable 9×6 matrix (Fixed 2/3/5Y total %,
     Regular 2/3/5Y monthly %). Edited cells turn amber; Save writes a **new
     effective-dated version** (old row kept, `is_active=0`) plus a
     `staking_roi_audit` entry (old → new, who, note, when). Per-cell version
     history modal + global audit-log modal. Editing gated: restricted
     sub-admins need the `staking_roi_edit` permission key (Super-Admin rule).
  4. **Rank Achievement** — the 11 permanent ranks (UN RANK → CHALLENGER) with
     group incentives, benefits (Badge/Certificate/Reward/Recognition), badge
     colour, enable/disable, and a Plan-1/2/3 qualification-requirements editor
     (left/right counts of lower ranks; OR options supported — PLATINUM P1).
- **DB:** `db/staking_module.sql` (idempotent) — 9 tables:
  `staking_packages`, `staking_plans`, `staking_plan_terms`,
  `staking_roi_structure`, `staking_roi_audit`, `user_stakes`,
  `staking_roi_payouts`, `staking_ranks`, `staking_rank_requirements` + full
  seed from the proposal (9 packages, 3 plans ×3 terms, 54 ROI cells,
  11 ranks, 58 requirement rows). Applied to `e-commerce-mlm-v2`.
- **Files:** `models/Staking_model.php`,
  `controllers/admin/staking/{Packages,Plans,Roistructure,Ranks}.php`,
  `views/admin/staking/{packages,plans,roi_structure,ranks}.php`,
  `config/routes.php` (`admin/staking/*`),
  `views/admin/Layout/admin_sidebar.php` (new **Staking Management** group).
- **Validated:** CLI smoke test — resolveRoi (incl. combo = fixed+regular
  halves), versioned save + audit + revert, guards (negative %, combo ≠ 100,
  duplicate stake amount, requirement dupes) all pass; pages behind admin
  login (unauthenticated → redirect `admin/login`).
- **How to apply:** run `db/staking_module.sql`, deploy the PHP files. New
  permission keys (optional for sub-admins): `staking_management`,
  `staking_roi_edit` (legacy `package_settings` / `rank_management` also
  accepted for page view).

---

## 2026-07-01 — Auth pages: use dynamic Site-Settings logo

- **What:** login (`user/in`) and register (`user/re`) brand panel now shows the
  logo configured in **Admin → Site Settings** (`site_settings('image','logo')`,
  served from `assets/images/`) instead of the hardcoded `assets/img/logo/logo.svg`.
  Same size (existing CSS `.lpx-brand-inner img{height:40px}` untouched). Filename
  is `rawurlencode()`d (admin logos can contain spaces, e.g. `image (3).png`) and
  an `onerror` fallback restores the bundled SVG if the file is missing.
- **Files:** `application/views/user/auth/login.php`,
  `application/views/user/auth/register.php`.
- **Side fix:** the asset `logo-whites.png` was on disk as `logo-whites.png.png`
  (double extension), so `assets/images/logo-whites.png` 404'd — breaking the
  shop invoice and the custom 404 page. Added a correctly-named copy
  `assets/images/logo-whites.png` (original kept).
- **How to apply:** no SQL/route. The logo is whatever Site Settings → Logo holds
  (currently `image (3).png`).

---

## 2026-07-01 — KYC: controlled state machine

> Full reference: [5_KYC_STATE_MACHINE.md](5_KYC_STATE_MACHINE.md).

- **What:** replaced ad-hoc KYC status changes with a controlled state machine.
  Canonical states `NOT_SUBMITTED · PENDING · UNDER_REVIEW · APPROVED ·
  RESUBMIT_REQUIRED` map onto the existing enum values (no schema change).
- **Rules:** users upload only in `NOT_SUBMITTED`/`RESUBMIT_REQUIRED` → auto
  `PENDING`; admin `Start Review` (`PENDING→UNDER_REVIEW`); from `UNDER_REVIEW`
  only `Approve` or `Request Resubmission` (reason mandatory → `RESUBMIT_REQUIRED`).
  All transitions validated **server-side**; invalid ones return 422 and don't
  mutate. Every transition logged to `kyc_audit_logs`.
- **Admin UI:** action buttons only (contextual per state); no status dropdown.
- **Files:** `models/Kyc_model.php` (state machine), `controllers/user/Kyc.php`,
  `controllers/admin/AdminKyc.php` (`decision()` now action-based, legacy
  `status=` still mapped), `views/user/account/kyc_form.php`,
  `views/admin/kyc_list.php`, `assets/admin/js/.../kyc-request-list.js`.
- **How to apply:** no SQL; hard-refresh admin KYC page (JS `?ver=3.1`).

---

## 2026-07-01 — KYC: manual verification form simplified

- **What:** user KYC form reduced to the required fields (Document Type →
  Aadhaar/Driving License/Passport, Document Number, Front/Back/Selfie). Formats
  limited to JPG/JPEG/PNG/TIFF/GIF, 4 MB/image. Admin list gained Status +
  Document Type filters and search by Name/Email/Phone/Doc No; rejection reason
  made mandatory; status history surfaced via the (previously unused)
  `kyc_audit_logs` table. Legacy NOT-NULL profile columns auto-filled server-side.
- **Files:** same KYC set as above (prior revision).

---

## 2026-06-30 — Fix: login crash "admin_email on null" (sender_otp)

- **Cause:** `user/auth/Login.php::sender_otp()` looked up the OTP recipient in
  `admin_members` using the member id, but member logins come from the `users`
  table — so `->row()` was `null` and `->admin_email` threw a Warning, crashing
  the login POST (the AJAX then received HTML instead of JSON).
- **Fix (controller, minimal):** read the email from `users` and guard nulls —
  only attempt the mail/template/log when an email is found; always set the
  `sender_otp` + `user_get_id` session so the OTP step proceeds.
- A view change couldn't fix this — it was a server-side null on the wrong table.

---

## 2026-06-30 — Auth views: full AJAX login+OTP and richer register (views only)

> **No controller / route / functionality changed — only the two view files.**

- `application/views/user/landing/login.php` — two-step AJAX, both to existing
  endpoints:
  1. credentials → `user/in` (returns `{status:true}`, sends OTP, sets session);
  2. OTP panel (prefilled `123456`) → `user/login-finel-verify` with `emailOTP`
     + `twofaOTP`; on `{status:true}` redirects to `user/main` (dashboard).
  Works because `twofachecker()` already returns true (2FA check commented out),
  so the dummy OTP `123456` is accepted by the existing code.
- `application/views/user/landing/register.php` — fields per request: Sponsor ID
  (referral prefill), Username, Email, Password (+ "8 or more characters…" hint),
  Repeat Password, "I Accept the Terms". Hidden `select_lg=left` keeps the
  existing registration logic intact. Client-side checks (password strength,
  match, terms) then AJAX POST to `user/re` → on success redirect to `/login`.

---

## 2026-06-30 — Early-access: config on/off switch + CORS (allow anywhere)

- `config.php` (root) — two new flags alongside `ENABLE_SITE_UPLOAD_FUNCTION`:
  - `LANDING_EARLY_ACCESS_ENABLED` (true) — set false to turn the endpoint off.
  - `LANDING_EARLY_ACCESS_ALLOW_ANY_ORIGIN` (true) — accept the POST from any
    site (CORS `*` / reflects the Origin).
- `application/controllers/Landing.php` — `early_access()` now calls `_cors()`
  first: emits the CORS headers when allowed and answers the browser's
  `OPTIONS` preflight with 204; then checks the enabled flag before processing.
- **Effect:** the form at `POST /landing/early-access` can be embedded/called
  from any origin, and the whole feature flips on/off from `config.php` with no
  code change.

---

## 2026-06-30 — Member panel: user-selectable sun/moon toggle

- Dashboard uses the **v2 layout** (`user/layout/v2/user_style.php`), so the
  member theme vars + default mode are injected there (in addition to
  `common_style.php`).
- Added a floating **sun/moon toggle** (bottom-left) so members switch
  light/dark themselves; choice persists in `localStorage['data-bs-theme']`.
  Default mode comes from **Settings → Member Panel Theme**.
- New admin option **"Allow members to switch theme"** (`member_theme.user_switch`)
  shows/hides the toggle. Controller + admin view + seed updated.
- Files: `v2/user_style.php` (vars + toggle), `Membertheme.php`,
  `admin/settings/member-theme.php`, `db/member_theme_seed.sql`.

---

## 2026-06-30 — Feature: Member Panel Theme (independent theme engine)

Two independent theme engines now exist: **Landing** (`landing_settings`) and
**Member Panel** (`site_settings` type `member_theme`). They never mix; both can
read shared Brand Settings (logo/name/favicon) from `site_settings`.

- `db/member_theme_seed.sql` — seeds `member_theme` (mode + palette).
- `application/controllers/admin/settings/Membertheme.php` — Settings → Member
  Panel Theme: index / update (AJAX) / reset_default; colour validation; saves to
  `site_settings`.
- `application/views/admin/settings/member-theme.php` — Light/Dark/Auto mode +
  palette pickers (primary/secondary/accent, highlight set, gradient, status),
  Reset to Default, Open Dashboard.
- `application/config/routes.php` — `member-theme`, `member-theme-update`,
  `member-theme-reset`.
- `application/views/admin/Layout/admin_sidebar.php` — menu item under Settings.
- `application/views/user/layout/common_style.php` — emits the member theme as
  **CSS variables** (`--mp-primary`, `--mp-highlight`, `--mp-gradient`, status
  colours…) and drives Metronic's `--bs-primary` + the default `data-bs-theme`
  mode (light/dark/auto) from the setting. So the member dashboard switches
  white/black and follows the palette; the landing page is unaffected.
- **Apply:** `mysql … < db/member_theme_seed.sql`

**Scope note (phased):** delivered the core — independent Light/Dark/Auto + a
central palette via CSS variables. The exhaustive per-component colour maps
(sidebar/header/cards/tables/forms/charts each fully itemised), the in-admin
multi-page preview, draft/publish, and automatic contrast validation are a
follow-on roadmap; every component already reads the central `--mp-*` / `--bs-*`
variables, so they extend without touching components.

---

## 2026-06-30 — Reverted: removed new auth pages; restyle the EXISTING ones

The standalone `/login` & `/register` pages caused a real bug: the existing
`user/in` endpoint replies with a **303 redirect** on success (not JSON), so the
AJAX page wrongly showed "Invalid username or password" even though login had
actually succeeded (and the OTP/2FA flow was bypassed). Decision: drop the new
pages and only change the **view/background** of the existing auth pages.

- **Removed:** `application/controllers/Auth.php`,
  `application/views/user/landing/login.php`,
  `application/views/user/landing/register.php`, and the `login` / `login/reset`
  / `register` routes.
- **View-only restyle:** `application/views/user/auth/login.php` and
  `register.php` — background changed to the Webze dark hero
  (`assets/img/banner/hero_bg.svg` over `#0b0b23`, cover/fixed). Nothing else
  in those pages changed — the existing AJAX, OTP/2FA, captcha and redirects
  are intact.
- **Nav reverted:** `db/landing_auth_nav_revert.sql` points Login → `user/in`
  and the header CTA → `user/re` again.
- **Apply:** `mysql … < db/landing_auth_nav_revert.sql`

### (Superseded) earlier entry — Webze-styled Login / Register pages

Non-destructive integration — the existing secure auth (validation, OTP, 2FA,
captcha, MLM registration) is **untouched**; only the entry screens are re-skinned.

- `application/controllers/Auth.php` (new) — `login()` / `register()` render the
  Webze-styled views with branding/theme from Landing Page Settings; register
  prefills the sponsor from the `?re=` referral param.
- `application/views/user/landing/login.php` (new) — Webze split layout, fields
  `useremail` / `password` / `remember`, AJAX POST to `user/in`; on success
  redirects to `user/in` so the existing **OTP/2FA** screen runs. Links to
  `/register` and `user/forgot`.
- `application/views/user/landing/register.php` (new) — fields `sponsor_id`
  (referral prefill, readonly when present), `username`, `useremail`,
  `password`, `select_lg` (left/right leg); AJAX POST to `user/re`; on success
  redirects to `/login`.
- `application/config/routes.php` — `login` → `Auth/login`, `register` → `Auth/register`.
- `db/landing_auth_nav.sql` — points the landing nav Login → `login` and the
  header CTA → `register`.
- **Apply:** `mysql … < db/landing_auth_nav.sql`
- **Note:** if site captcha is ON, the existing endpoints require a
  `g-recaptcha-response`; these pages don't render the widget yet (add it, or
  keep captcha off for the public auth pages).

---

## 2026-06-30 — Fix: theme toggle now persists; section audit

- **Theme toggle (sun/moon) wasn't persisting** — the view forced
  `localStorage['site-theme']` to the admin default on *every* load, so a click
  flipped the theme live but it snapped back on reload (looked like a stuck /
  "hanging" icon).
  - `application/views/user/landing/index.php` — only force the theme on the
    admin preview (`?theme=`); on normal loads `theme.js` applies the visitor's
    saved choice, else the admin default (`<html data-theme>`). Toggle persists.
- **Section audit** — all 13 sections render. Only three are gated:
  brand (hidden when the `landing_brands` table is empty), marquee and exchange
  (hidden when their `enable` switch = 0). Nothing else can disappear. Re-enable
  from Landing Page Settings → Marquee / Exchange, or add brand rows.
- **Brand logos** scroll via Swiper `autoplay` (steps every 2.5s, not a
  continuous marquee) — continuous scroll available on request.

---

## 2026-06-30 — Fix: marquee not scrolling (+ theme toggle confirmed)

- **Marquee** ("You will hold the way you love Webzo") was static because the
  jQuery `.marquee` plugin wasn't initialising on the page. Replaced with a
  **dependency-free CSS marquee** that scrolls right-to-left in both themes.
  - `application/views/user/landing/index.php` — marquee markup duplicated into
    two halves (seamless loop), `marquee_mode` class dropped so the JS plugin
    can't double-handle it, `animation-duration` driven by the CMS **speed**
    field (`repeat`/`text`/`enable` still apply); CSS keyframes added to the
    global inline `<style>`. Pauses on hover.
- **Theme toggle** (bottom-left sun/moon) verified working: dark shows the sun
  (→ switch to light), light shows the moon. `.theme-toggle .icon-sun/.icon-moon`
  rules in `main.css` are correct; no change needed.

---

## 2026-06-30 — Phase 1.4: Light-theme visual polish + dark default

### Light theme now reads as a designed interface (not a colour inversion)
- `assets/css/landing-light.css` (new) — scoped to `html[data-theme="light"]`,
  layered over `main.css`. Adds: **highlight words in the accent colour**
  (was grey), uppercase accent sub-labels, heading/paragraph hierarchy,
  hero contrast + rounded shadowed email box, **alternating section
  backgrounds** (white / `#f8f9fc`) for separation, brand-logo grayscale→colour
  on hover, **card depth** (white + border + soft shadow + hover-lift + accent
  border + icon motion) for features/crypto/team/exchange/token/FAQ, countdown
  chips + gradient progress bar, faded work numbers + image drop-shadow, FAQ
  active accent border, roadmap year accent + icon hover, team image shadow,
  light-grey footer, rounded buttons with lift. No HTML/JS/layout/CMS changes.
- `application/views/user/landing/index.php` — links `landing-light.css` after
  `main.css`.

### Dark is now the default theme
- `application/views/user/landing/index.php` — `theme_mode` default → **dark**.
- `application/views/admin/cms/landing-page.php` — General Theme Mode select
  default → dark; palette relabelled to **Highlight/Accent**, **Button**,
  **Button Hover**, **Background (page)** to match "background + button +
  highlight" model.
- `db/landing_set_dark_default.sql` — sets stored `theme_mode = dark`.
- **Apply:** `mysql … < db/landing_set_dark_default.sql`

### CMS-driven
Highlight/accent (`--tg-primary-color`), button, button-hover and page
background all still come from the **Landing Page Settings → General** palette;
the polish CSS only adds structure (radius/shadow/spacing/hover), not hardcoded
brand colours.

---

## 2026-06-30 — Fix: duplicate nav (Login) and team (founder) rows

- **Cause:** the seed `INSERT`s in `db/landing_page_schema.sql` were imported more
  than once, duplicating `landing_menu` and `landing_team` (and other repeater)
  rows → Login repeated in the top menu and the founder repeated in "Meet with
  our avengers!".
- **Fix:** `db/landing_cleanup_duplicates.sql` — dedupes every repeater (keeps
  the lowest id per group) and tidies the nav (removes the Register item that
  duplicates the header CTA; Home→`landing`, Login→`user/in`).
- **Apply:** `mysql … < db/landing_cleanup_duplicates.sql`
- **Also fixable in the UI:** Landing Page Settings → expand *Navigation Menu* /
  *Team Members* and click the trash icon on the extra rows.
- **Prevent recurrence:** do not re-run the seed section of
  `landing_page_schema.sql` on a DB that already has data.

---

## 2026-06-30 — Phase 1.3: Light theme system + nav cleanup

### Root cause of the "broken dark / missing diagonal" look
The template (`assets/css/main.css`) already ships a **full light theme**
(`html[data-theme="light"]`, line ~7676) and a switcher (`assets/js/theme.js`).
Two earlier bugs broke it: (a) my CSS painted `body` dark via `--lp-bg`, hiding
the hero title + diagonal dividers; (b) I injected `--tg-theme-primary` but the
template uses `--tg-primary-color`, so colours never applied. Both fixed.

### 1. Light/Dark theme driven by the template's own system
- `application/views/user/landing/index.php`
  - `<html data-theme="<?=theme_mode?>">` from the new **General → Theme Mode**
    setting (default **light**).
  - Style block rewritten: maps palette to real `--tg-*` vars; overrides the
    light page background via `html[data-theme="light"]{ --tg-color-dark }`;
    **removed** the dark `body` override. Button hover honours the palette.
  - `?theme=light|dark` query forces the theme (used by the admin preview) by
    pre-seeding `localStorage` before `theme.js`.
- `application/controllers/admin/cms/Landingpage.php` — `theme_mode` whitelisted.

### 2. Admin: theme control + preview light/dark
- `application/views/admin/cms/landing-page.php` — **Theme Mode** select in
  General; preview toolbar adds **Light/Dark** toggles + **Open in new tab**.
- `assets/admin/js/custom/cms/landing-page.js` — preview reloads iframe with
  `?theme=`; new-tab link tracks the chosen theme.

### 3. Approved palette + navigation cleanup
- `db/landing_light_theme.sql` (idempotent):
  - Palette → approved (primary `#FFC94A`, secondary `#6D4AFF`, button
    `#FFC94A`, hover `#6D4AFF`, background `#FFFFFF`).
  - `theme_mode = light` default.
  - Nav: Home→`landing`, Login→`user/in`, **Register nav item removed** (it was
    duplicated by the header CTA); header CTA → `user/re` (register).
- **Apply:** `mysql … < db/landing_light_theme.sql`

### Notes
- The diagonal black/white dividers reappear automatically once light theme is
  active (they need the light body for contrast — no template edits required).
- All content stays CMS-driven; the theme is a setting, no frontend code change
  needed to switch Light/Dark.

---

## 2026-06-30 — Phase 1.2: Footer copyright, SMTP early-access, meta unify, palette

### 1. Dynamic footer copyright on the home page
- `application/views/user/landing/index.php` — home footer now uses the **Site
  Settings → Copyright Text** value first (`$site_copyright`), falling back to
  the landing footer/general copyright.

### 2. "Get Early Access" sends email via SMTP + captures the lead
- `application/controllers/Landing.php` — new `early_access()`:
  validates email → inserts into `landing_leads` (if table exists) → sends a
  notification through the platform SMTP (`email_config` + PHPMailer 6.9.1,
  PHP `mail()` fallback when `smtp_status = 0`). Returns JSON + optional redirect.
- `application/views/user/landing/index.php` — hero form posts via fetch to
  `landing/early-access` and shows the Hero **Success Message**.
- `application/config/routes.php` — `landing/early-access` → `Landing/early_access`.
- `db/landing_leads.sql` — new leads table (optional but recommended).
- **Apply:** `mysql … < db/landing_leads.sql`; set SMTP in **Settings → Mail
  Settings**. (CSRF is disabled in config, so the public POST works.)

### 3. Unified meta — both places drive the landing page
- `application/controllers/admin/cms/Landingpage.php` — saving the **SEO** card
  mirrors title/description/keywords into `site_settings` (meta-settings).
- `application/controllers/admin/settings/Sitesettings.php` — saving Meta mirrors
  into `landing_settings` SEO (`landing_seo_mirror()`).
- **Effect:** edit meta in either screen → both stay identical; landing uses one
  source of truth.

### 4. Color palette — dynamic background + button hover
- `application/views/admin/cms/landing-page.php` — General colors now use a
  `lp_color()` **color-picker + hex/rgba** control; added **Button Hover Color**.
- `assets/admin/js/custom/cms/landing-page.js` — picker ↔ text sync.
- `application/controllers/admin/cms/Landingpage.php` — whitelisted
  `button_hover_color` (general) and `success_message` (hero).
- `application/views/user/landing/index.php` — emits CSS vars + rules for body
  background and `.tg-btn:hover` from the palette.
- `db/landing_palette_seed.sql` — seeds the two new keys.
- **Apply:** `mysql … < db/landing_palette_seed.sql`

---

## 2026-06-30 — Phase 1.1: Home integration, Copyright, shared meta

### 1. Home page `/` now renders the landing page (with backup)
- `application/config/routes.php`
  - `default_controller` changed `welcome` → `Landing`.
  - Added `home` → `Landing`; `shop-home` → `Welcome` (backup of old shop home).
- **Effect:** `http://<host>/` and `http://<host>/landing` show the same dynamic
  landing page. The previous e-commerce home is preserved at `/welcome` and
  `/shop-home` (no code deleted — `Welcome::index()` untouched).
- **React-CDN note:** a React-via-CDN attempt failed to display (showed the PHP
  page). Decision: keep the **PHP dynamic page** — responsive, SEO-friendly, no
  build step. React not used.

### 2. Dynamic Copyright on Site Settings
- `application/views/admin/settings/site-settings.php` — added **Copyright Text**
  field to the Meta Details card.
- `application/controllers/admin/settings/Sitesettings.php` — `index()` loads
  `site_copyright`; `update_meta_settings()` saves `meta-settings/copyright`.
- `db/landing_copyright_seed.sql` — seeds the default row (idempotent).
- **Apply:** `mysql … < db/landing_copyright_seed.sql`
- **Effect:** footer copyright is editable from Site Settings and used as the
  landing footer default.

### 3. Shared meta (Site Settings ↔ landing SEO)
- `application/views/user/landing/index.php` — SEO meta falls back to
  `site_settings('meta-settings', …)` (title/description/keywords) and copyright.
- `application/controllers/admin/cms/Landingpage.php` — SEO card prefills empty
  fields from Site-Settings meta (single source of truth).

---

## 2026-06-30 — Phase 1.0b: Permission fix for `/landing-page-cms`

- `application/controllers/admin/cms/Landingpage.php` — guard now allows
  `landing_page_cms` **or** `website_content_cms`, so Content-Management admins
  aren't redirected to `/admin`.
- `db/landing_page_schema.sql` — corrected grant SQL (`admin_roll = '1'` are the
  restricted admins, not `'0'`).
- **Symptom fixed:** clicking *Content Management → Landing Page Settings*
  bounced to the admin dashboard.

---

## 2026-06-30 — Phase 1.0: Dynamic landing page module (initial build)

Converted the static **Webze ICO/Crypto** `index.html` into a DB-driven page
managed from **Content Management → Landing Page Settings**. Design unchanged.

**Added files**
- `db/landing_page_schema.sql` — schema + seed (kv `landing_settings` +
  repeater tables + `landing_versions`).
- `application/models/cms/Landing_model.php` — kv get/set + repeater CRUD/sort +
  snapshot/restore.
- `application/controllers/admin/cms/Landingpage.php` — admin CRUD, upload,
  export/import/version.
- `application/views/admin/cms/landing-page.php` — 17 collapsible section cards +
  live preview (desktop/tablet/mobile).
- `application/views/admin/cms/_landing_repeater.php` — generic repeater partial.
- `assets/admin/js/custom/cms/landing-page.js` — AJAX save, drag-sort, preview,
  import, version.
- `application/controllers/Landing.php` + `application/views/user/landing/index.php`
  — public dynamic page.
- `application/helpers/landing_helper.php` — `lp()`, `lp_hl()`, `lp_asset()`.

**Edited**
- `application/config/routes.php` — landing public + admin routes.
- `application/config/autoload.php` — autoload `landing` helper.
- `application/views/admin/Layout/admin_sidebar.php` — menu item under Content
  Management.

**Apply:** `mysql … < db/landing_page_schema.sql`, then grant permission (see
[2_LANDING_PAGE_MODULE.md](2_LANDING_PAGE_MODULE.md)).

---

## Template for new entries

```
## YYYY-MM-DD — Phase X.Y: <title>
- <what changed> (`path`)
- Apply: <sql/route/cache step>
- Effect: <user-visible result>
```
