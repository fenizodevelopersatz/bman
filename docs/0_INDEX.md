# 0 — Documentation Index & Status Dashboard

Master index for the **Landing Page / Home Page** work on the Nexman MLM
(CodeIgniter) platform. All docs are number-prefixed so future work stays
trackable. Update the **Status** column as features land.

> Convention: new docs get the next free `N_` prefix. Each feature phase has a
> checklist in [4_LANDING_ROADMAP.md](4_LANDING_ROADMAP.md); when a phase is
> delivered, log it in [3_CHANGELOG.md](3_CHANGELOG.md).

---

## Documents

| # | Doc | Purpose |
|---|-----|---------|
| 0 | [0_INDEX.md](0_INDEX.md) | This file — index + status dashboard |
| 1 | [1_BMAN_DEEP_ANALYSIS.md](1_BMAN_DEEP_ANALYSIS.md) | BMAN staking business analysis (separate track) |
| 2 | [2_LANDING_PAGE_MODULE.md](2_LANDING_PAGE_MODULE.md) | Setup & reference for the shipped module |
| 3 | [3_CHANGELOG.md](3_CHANGELOG.md) | Chronological log of every step done |
| 4 | [4_LANDING_ROADMAP.md](4_LANDING_ROADMAP.md) | Phased backlog (all 18 enhancement items) |
| 5 | [5_KYC_STATE_MACHINE.md](5_KYC_STATE_MACHINE.md) | KYC module + controlled status state machine |
| 6 | [6_STAKING_PACKAGES_PLANS_ROI.md](6_STAKING_PACKAGES_PLANS_ROI.md) | Pre-plan: staking packages, plans & ROI structure (fields, DDL, flowcharts) |
| 7 | [7_TOKEN_WALLET_INTEGRATION.md](7_TOKEN_WALLET_INTEGRATION.md) | Custodial vs on-chain: giving BMAN without a key, deposit→stake→withdraw, treasury-key handling |
| 8 | [8_WALLET_DEPOSIT_WITHDRAW.md](8_WALLET_DEPOSIT_WITHDRAW.md) | Production wallet: double-entry ledger, deposit listener deep-dive, statuses, "works with no private key" verification |
| 9 | [9_INTERNAL_WALLET_TRANSFER.md](9_INTERNAL_WALLET_TRANSFER.md) | Internal wallet transfer (user → wallet): 3-table schema, model, controller, validation, security, UI design, admin side |
| 10 | [10_BONUS_WALLET_REDUCTION_ADMIN_WALLET.md](10_BONUS_WALLET_REDUCTION_ADMIN_WALLET.md) | Pre-plan: Bonus Wallet 60-day/50% reduction engine + new Admin (Company) Wallet that receives the reclaimed amount |
| 11 | [11_ADMIN_WALLET_MANAGEMENT.md](11_ADMIN_WALLET_MANAGEMENT.md) | 🟢 **Implemented**: Bonus Wallet 60-day reduction → admin wallet. Every N days (60; **1 for testing**) 50% of a user's bonus is reduced to the admin wallet, per-user schedule from `register_date` (PHP), optional on-chain send (user→admin, BNB gas). `Bonusreductioncron` + admin **Admin Bonus Wallet** screen + `db/bonus_reduction.sql`. ROI is a later phase (out of scope) |
| 12 | [12_WINDOWS_CRON_SCHEDULER.md](12_WINDOWS_CRON_SCHEDULER.md) | 🟢 **Implemented**: Windows Task Scheduler cron replacement — `cron.php` + `scheduler/` fire due jobs each minute; drives `/bonus-reduction-cron` and other CI crons. Full ops guide in [../scheduler/README.md](../scheduler/README.md) |
| 13 | [13_ONCHAIN_TRANSACTIONS.md](13_ONCHAIN_TRANSACTIONS.md) | 🟢 **Implemented**: Admin ▸ Finance ▸ On-Chain Transactions — 5 balance cards + server-side filterable history (`onchain_transactions` table) + rich detail modal with **live RPC enrichment** (gas/nonce/block/logs). `admin/wallet/onchain-transactions` |
| 14 | [14_ONCHAIN_SYNC_LIFECYCLE.md](14_ONCHAIN_SYNC_LIFECYCLE.md) | 🟢 **Implemented + tested (live)**: withdrawal/swap on-chain lifecycle, RPC verification + reorg handling, and a **cost-optimized balance sync** (free RPC primary, BscScan only on a balance change). `Chainsync_model` + `chain-sync-cron`. 10/10 integration tests pass |
| 15 | [15_STAKINGS_PAGE_REDESIGN.md](15_STAKINGS_PAGE_REDESIGN.md) | 🟢 **Phases 1–5 done**: user "Package"→"Stakings" redesign — rename + removed KPI cards + redesigned package cards, server-side portfolio (search/sort/filter/paginate) + CSV/Excel, full-screen 7-tab investment modal (real data), and document generation (receipt/agreement/ROI schedule/summary, printable HTML+QR, owner/admin-only). 5 commits |
| 16 | [16_WALLET_TRANSFER_ENGINE.md](16_WALLET_TRANSFER_ENGINE.md) | 🟢 **Implemented + tested**: ONE centralized Wallet Transfer validation+execution engine used by both User & Admin panels — exact member (downline / bonus→sponsor) + internal (exchange source-only) rules, double-entry, idempotent, audit. 18/18 rule + 3/3 exec tests |
| 17 | [17_BINARY_MATCHING_PAYOUT_CRON.md](17_BINARY_MATCHING_PAYOUT_CRON.md) | 🟢 **Implemented + tested**: on-chain payout cron (treasury precheck, FIFO drain, retry, watch mode) + recipient-eligibility fix (must have an own active stake — 7/7 test) + verified multi-level cascading (17/17 test) + admin Genealogy Tree showing real binary_carry/ceiling for any member (12/12 test) + 2 payout admin screens + Cron Lab button. ⚠️ zero real payout has occurred yet — see doc for the `binary_volume_ledger` gap |
| 20 | [20_MEMBER_BULK_UPLOAD.md](20_MEMBER_BULK_UPLOAD.md) | 🟢 **Implemented + tested**: Admin ▸ Members ▸ **Bulk Upload** — one Excel/CSV sheet becomes many members (username/email/password, `reference_id`-driven binary placement, a generated on-chain wallet address per member). Two-phase stage→import so nothing reaches `users` until the admin confirms; plaintext passwords never touch disk. The `bman` column is queued and delivered by `member-bulk-bman-cron` from the admin hot (Treasury) wallet, then posted to the member's **Exchange wallet** via `Walletledger_model` — with a self-healing backfill for sends that landed without their ledger entry. Cron switches are backend-only (not on the page); the page carries the upload form + a per-batch transaction audit. Disabled + dry-run by default. ⚠️ the live on-chain send path is unverified until go-live step 2.3 |
| 20a | [20a_MEMBER_BULK_UPLOAD_SQL_RUNBOOK.md](20a_MEMBER_BULK_UPLOAD_SQL_RUNBOOK.md) | **SQL runbook** for the above — install, the staged go-live sequence (enable → verify dry-run → flip live), emergency stop, monitoring/reconciliation queries, re-queueing failed sends, tuning, and rollback. Every read-only query verified against the live schema |

---

## Status dashboard

| Area | Status | Notes |
|------|:------:|-------|
| KYC manual verification + controlled state machine | ✅ Done | See [5_KYC_STATE_MACHINE.md](5_KYC_STATE_MACHINE.md) |
| Staking packages / plans / ROI structure — **admin side** | ✅ Done | 4 screens under Admin → Staking Management; see [6_STAKING_PACKAGES_PLANS_ROI.md](6_STAKING_PACKAGES_PLANS_ROI.md) |
| Staking rank achievement (11 ranks + qualification matrix) — **admin side** | ✅ Done | Same module; evaluation cron pending |
| Rank Power system (§11) + Group Incentive Ceiling (§12) — **admin side** | ✅ Done | Settings, 60-day cycles, ceiling editor; evaluation engine pending |
| Bonus Coin (§7) + Binary Matching Bonus (§9) — **admin side** | ✅ Done | Bonus %, 60d/50% reduction, transfer rules, 10=8+2 matching split — §4–§12 admin setups complete |
| Binary Matching Bonus **payout engine** — scheduled + on-chain | 🟢 Done | `BinaryMatchingPayoutCron` (5-min cadence, watch mode) wraps `Stakingmatching_model` with queue-tracking, treasury-balance-checked on-chain BMAN payout, and admin retry. Matching History + Payout Queue + Genealogy Tree (any member, shows real `binary_carry`) admin screens + Cron Lab button. Fixed a real gap: recipients now need an own active stake to be paid (were previously paid uncapped with none). Multi-level cascading verified (no explicit "level order" needed — each ancestor's carry accumulates independently). ⚠️ zero real payout yet — `binary_volume_ledger` empty for all real users pending a normal purchase or backfill. See [17_BINARY_MATCHING_PAYOUT_CRON.md](17_BINARY_MATCHING_PAYOUT_CRON.md) |
| Coin Distribution Master (§3A) + purchase snapshot | ✅ Done | Master → Coin Distribution; 7 options, one-default rule, audit; Make-Investment credits 4 wallets + permanent history |
| Single Withdraw Settings page (global + staking plan rules) | ✅ Done | `withdraw-settings` is the only withdraw page; Plans page links there (no duplicate fields) |
| Token Settings Master (blockchain single source of truth) | ✅ Done | Master → Token Settings; network/tokens/rate/wallets/contracts, RPC test, IP-audited; active rate bridged to legacy `token_config` |
| Master menu restructure (9 items, responsibilities separated) | ✅ Done | Token · Coin Distribution · Packages · Plans · ROI · Bonus · Wallet · Blockchain · System; Staking Management keeps rank pages only |
| Web3 integration — BEP-20 wallet + signed transfers | ✅ Done | `Web3bman` library (reads Token Settings); generate wallet, balances, sign+send BMAN/USDT/BNB. Admin: check balance + generate wallet. Broadcast wired for the payout engine |
| Internal wallet transfer module (user → own wallets) — ledger, admin grid | ✅ Done | `wallet_internal_transfer` + `wallet_ledger`; `Transfer_wallet` user page, admin Finance → Internal Wallet Transfers. USDT excluded. See [9_INTERNAL_WALLET_TRANSFER.md](9_INTERNAL_WALLET_TRANSFER.md) |
| Token Settings §5/§6 simplify — Treasury+Deposit wallet, encrypted Treasury key, drop contracts | ✅ Done | USDT→BMAN signed by one Treasury key (AES-encrypted, never shown); gas/bonus/reserve/cold + smart-contract fields removed |
| Staking user purchase flow + ROI cron + reports | ⬜ Planned | Next phase of [6_STAKING_PACKAGES_PLANS_ROI.md](6_STAKING_PACKAGES_PLANS_ROI.md) |
| Bonus Wallet reduction cron + Admin (Company) Wallet | 📝 Plan written | Design only, no code yet — see [10_BONUS_WALLET_REDUCTION_ADMIN_WALLET.md](10_BONUS_WALLET_REDUCTION_ADMIN_WALLET.md) |
| Bonus Wallet 60-day reduction → admin wallet (per-user, from `register_date`) | 🟢 Done (internal) | `Bonusreductioncron` reduces 50% every N days (60; 1 for testing) to `admin_wallet`, double-entry via `Walletledger_model`; on-chain send (user→admin, Web3bman/BNB gas) wired, needs funded addresses. Admin **Admin Bonus Wallet** screen (`admin/wallet/admin-wallet`) shows balance + history. Tested on user 257. ROI = later. See [11_ADMIN_WALLET_MANAGEMENT.md](11_ADMIN_WALLET_MANAGEMENT.md) |
| Windows cron scheduler (`cron.php` + `scheduler/`) | 🟢 Done | Task Scheduler fires `cron.php`/min → dispatches due jobs; `run_cron.bat`/`.ps1`/`CronJobTask.xml`; drives the CI cron URLs. See [12_WINDOWS_CRON_SCHEDULER.md](12_WINDOWS_CRON_SCHEDULER.md) + [../scheduler/README.md](../scheduler/README.md) |
| On-Chain Transactions (admin dashboard + history + detail modal) | 🟢 Done | `admin/wallet/onchain-transactions`: 5 balance cards, server-side filter/sort/paginate over `onchain_transactions` (indexed), rich modal with live RPC enrichment (gas/nonce/block/logs). Backfilled from 38 deposits + reductions. See [13_ONCHAIN_TRANSACTIONS.md](13_ONCHAIN_TRANSACTIONS.md) |
| Dynamic landing module (17 sections, repeaters, versioning) | ✅ Done | Phase 1 |
| Admin editor `/landing-page-cms` + permission fallback | ✅ Done | Phase 1 |
| Public page `/landing` | ✅ Done | Phase 1 |
| Home `/` integrated to landing (old home → `/welcome`,`/shop-home`) | ✅ Done | Phase 1 |
| Dynamic Copyright on Site Settings | ✅ Done | Phase 1 |
| Shared meta (Site Settings ↔ landing SEO) | ✅ Done | Phase 1 |
| Global section visibility controls | ⬜ Planned | Phase 2 |
| Dynamic CTA module + dynamic forms + actions | ⬜ Planned | Phase 3 |
| Lead Management (Content Management → Landing Leads) | ⬜ Planned | Phase 3 |
| Hero advanced (video/typing/particles/stats/buttons) | ⬜ Planned | Phase 4 |
| Better live preview (dark/light/auto-refresh/draft) | ⬜ Planned | Phase 5 |
| Button library / Media manager / Theme | ⬜ Planned | Phase 5 |
| SEO+ (schema.org, verifications) / Analytics / Version compare | ⬜ Planned | Phase 6 |
| Announcement bar / Popup manager / Homepage statistics | ⬜ Planned | Phase 7 |

Legend: ✅ done · 🟡 in progress · ⬜ planned

---

## Task board — BMAN Staking project

Working task list for the staking build. When a task lands: tick it here,
log the detail in [3_CHANGELOG.md](3_CHANGELOG.md) (what / files / how to
apply), and update the module doc ([6_STAKING_PACKAGES_PLANS_ROI.md](6_STAKING_PACKAGES_PLANS_ROI.md)).
New tasks get added to the correct phase below.

### Phase A — Admin side (✅ complete, 2026-07-02)

- [x] DB migrations + seeds — `db/staking_module.sql`, `db/staking_rank_power.sql`,
      `db/staking_bonus_settings.sql`, `db/coin_distribution.sql` (all idempotent, applied)
- [x] Staking Packages screen (§4) — CRUD, bonus %, ceiling, enable/disable
- [x] Staking Plans screen (§5) — credit days, durations, combo split
- [x] ROI Structure grid (§6) — versioned + audited edits, history & audit viewers
- [x] Rank Achievement (§10) — 11 ranks, incentives, Plan-1/2/3 requirements editor
- [x] Rank Power & Group Incentive (§11/§12) — rules, 60-day cycles, ceiling editor
- [x] Bonus & Matching (§7/§9) — bonus %, reduction rule, transfer rules, 10=8+2 split
- [x] Coin Distribution Master (§3A) — list/filters/export, add/edit, one-default,
      audit log, purchase snapshot + 4-wallet credit in Make-Investment
- [x] Single Withdraw Settings page — global rules + staking plan windows/limits merged
- [x] Token Settings Master — network/BMAN/USDT/rate/wallets/contracts/chain params,
      one active config, RPC test, IP-audited, legacy `token_config` rate bridge
      (`db/token_settings.sql`)
- [x] Master menu restructure — 9 items (Token · Coin Distribution · Packages ·
      Plans · ROI · Bonus · Wallet · Blockchain · System); no duplicate entries
- [x] Web3 library (`Web3bman`) — isolated web3p/ethereum-tx stack; wallet gen,
      balances, offline-signed BEP-20/BNB sends, all from active Token Settings
      (`application/third_party/web3bman/`, `libraries/Web3bman.php`)
- [x] Custodial ledger (`Custodialwallet_model`) — give BMAN with NO private key
      (internal credit/debit/move); on-chain only at withdrawal. See
      [7_TOKEN_WALLET_INTEGRATION.md](7_TOKEN_WALLET_INTEGRATION.md)
- [x] Token Settings edit popup — concrete BSC placeholders on every field
- [x] Token logo preview fix — Edit modal shows the uploaded BMAN logo + live preview
- [x] Admin Member Profile card — all §1 fields (name/email/mobile/gender/DOB/
      address1/2/state/country/pin) on `view-user/{id}`; added `users.state` +
      `users.address_line2` (`db/user_profile_fields.sql`)
- [x] User profile form (`user/profile`) — captures gender/DOB/address1/2/state/
      pin; saves to `users`, shown on the admin Member Profile card
- [x] Custodial wallet management — unique BEP-20 deposit address per user
      (check-or-create, local gen + QR), 5-wallet balances, QR+copy on Bank tab,
      deposit/withdraw history + log; admin **Wallet Monitor** (on-chain vs DB,
      Scan All, Reconcile). `db/custodial_wallets.sql`; sets `encryption_key`
- [x] Production wallet architecture — double-entry `wallet_ledger` (unique
      tx_hash, balance_after, row-lock), `wallet_deposits` tracking, auto
      **deposit listener** (BscScan API / eth_getLogs) → confirm → credit
      Exchange @ rate, `Depositcron`, runtime client-side QR, admin **Detect
      Deposits**. Verified end-to-end **with no private key**.
      `db/wallet_production.sql`. See [8_WALLET_DEPOSIT_WITHDRAW.md](8_WALLET_DEPOSIT_WITHDRAW.md)

### Phase B — User side + engines (⬜ next)

- [ ] USDT deposit → BMAN conversion flow — read active Token Settings
      (`Tokenmaster_model::activeSettings()` / `convertUsdtToBman()`), credit
      Exchange Wallet; never hardcode the rate
- [ ] User stake purchase flow — resolve ROI cell, snapshot to `user_stakes`,
      credit 25% bonus coin, coin-distribution selection (§3A user side),
      move BMAN Exchange → Staking wallet
- [ ] ROI credit cron — Regular/Combo on 5/15/25 monthly + Fixed at maturity
      → `staking_roi_payouts`
- [ ] Bonus reduction cron — every `reduction_interval_days`, reduce Bonus Wallet
      by `reduction_percent` (reads `staking_bonus_settings`), credits the new
      **Admin (Company) Wallet** with the reclaimed amount — full design in
      [10_BONUS_WALLET_REDUCTION_ADMIN_WALLET.md](10_BONUS_WALLET_REDUCTION_ADMIN_WALLET.md)
- [ ] Bonus transfer flow — direct left/right only, email OTP + transfer password
- [x] Binary matching bonus payout — 10% split 8% Earning / 2% Staking
      (reads `staking_bonus_settings`) — engine was already correct; added
      `BinaryMatchingPayoutCron` (queue-tracked, on-chain, ceiling-restricted,
      admin retry). See [17_BINARY_MATCHING_PAYOUT_CRON.md](17_BINARY_MATCHING_PAYOUT_CRON.md)
- [ ] Rank achievement evaluation engine — scan binary tree, award permanent ranks
- [ ] Rank power evaluation + cycle auto-roll cron — fill `user_rank_power`,
      qualify group incentive, auto-open next cycle
- [ ] Group incentive payout — ceiling-capped (`staking_packages.group_ceiling`),
      gated by rank power qualification
- [ ] On-chain withdrawal payout — approve → `Web3bman::sendToken()` (BEP-20)
      from the treasury/gas wallet; store tx hash; retry per Token Settings
      (uses the web3 library, decrypt sending key just-in-time)
- [ ] Deposit auto-sweep + cron monitor — poll `custodial_deposits` addresses,
      auto-reconcile confirmed deposits, sweep user address → treasury
      (`Custodialwallet_model::monitor/reconcile` already power the manual tool)
- [ ] Frontend WalletConnect / MetaMask deposit (approve+transfer, push hash);
      full Wallet page tabs (Deposit/Withdraw/History/Statements) + PDF statements
- [ ] Withdraw request UI + admin approve-with-hash / reject-with-reason screens
      (backend `withdrawals` table + statuses exist; see [8_WALLET_DEPOSIT_WITHDRAW.md](8_WALLET_DEPOSIT_WITHDRAW.md) §5)
- [ ] Set BscScan/Etherscan API key in Token Settings to enable live auto-detect
      (or point scan mode at a log-capable RPC) — config, not code

### Phase C — Reports & polish (⬜ later)

- [ ] Staking report, ROI paid report, rank report, group incentive report,
      distribution reports (PDF/Excel/CSV per §18)
- [ ] User-facing rank badge / certificate / recognition pages (§10 benefits)
- [ ] Admin dashboard KPI cards for staking (§17)

---

## Decision log (key choices)

- **PHP server-rendered, not React-CDN.** A React-via-CDN landing was tried but
  failed to render (fell back to the PHP page). The shipped page is the PHP
  dynamic view: responsive (Bootstrap + AOS + Swiper), SEO-friendly (real HTML
  in source), no build step, and editable from the admin. React is **not** used.
  See [3_CHANGELOG.md](3_CHANGELOG.md) §2026-06-30.
- **Single source of truth for meta.** Landing SEO falls back to Site-Settings
  meta; the admin SEO card prefills from it. Editing either keeps them aligned.
- **Permission fallback.** `/landing-page-cms` accepts `landing_page_cms` OR the
  existing `website_content_cms` key, so Content-Management admins aren't locked
  out.
