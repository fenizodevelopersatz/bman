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

---

## Status dashboard

| Area | Status | Notes |
|------|:------:|-------|
| KYC manual verification + controlled state machine | ✅ Done | See [5_KYC_STATE_MACHINE.md](5_KYC_STATE_MACHINE.md) |
| Staking packages / plans / ROI structure — **admin side** | ✅ Done | 4 screens under Admin → Staking Management; see [6_STAKING_PACKAGES_PLANS_ROI.md](6_STAKING_PACKAGES_PLANS_ROI.md) |
| Staking rank achievement (11 ranks + qualification matrix) — **admin side** | ✅ Done | Same module; evaluation cron pending |
| Rank Power system (§11) + Group Incentive Ceiling (§12) — **admin side** | ✅ Done | Settings, 60-day cycles, ceiling editor; evaluation engine pending |
| Bonus Coin (§7) + Binary Matching Bonus (§9) — **admin side** | ✅ Done | Bonus %, 60d/50% reduction, transfer rules, 10=8+2 matching split — §4–§12 admin setups complete |
| Coin Distribution Master (§3A) + purchase snapshot | ✅ Done | Master → Coin Distribution; 7 options, one-default rule, audit; Make-Investment credits 4 wallets + permanent history |
| Single Withdraw Settings page (global + staking plan rules) | ✅ Done | `withdraw-settings` is the only withdraw page; Plans page links there (no duplicate fields) |
| Token Settings Master (blockchain single source of truth) | ✅ Done | Master → Token Settings; network/tokens/rate/wallets/contracts, RPC test, IP-audited; active rate bridged to legacy `token_config` |
| Master menu restructure (9 items, responsibilities separated) | ✅ Done | Token · Coin Distribution · Packages · Plans · ROI · Bonus · Wallet · Blockchain · System; Staking Management keeps rank pages only |
| Web3 integration — BEP-20 wallet + signed transfers | ✅ Done | `Web3bman` library (reads Token Settings); generate wallet, balances, sign+send BMAN/USDT/BNB. Admin: check balance + generate wallet. Broadcast wired for the payout engine |
| Staking user purchase flow + ROI cron + reports | ⬜ Planned | Next phase of [6_STAKING_PACKAGES_PLANS_ROI.md](6_STAKING_PACKAGES_PLANS_ROI.md) |
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
      by `reduction_percent` (reads `staking_bonus_settings`)
- [ ] Bonus transfer flow — direct left/right only, email OTP + transfer password
- [ ] Binary matching bonus payout — 10% split 8% Earning / 2% Staking
      (reads `staking_bonus_settings`)
- [ ] Rank achievement evaluation engine — scan binary tree, award permanent ranks
- [ ] Rank power evaluation + cycle auto-roll cron — fill `user_rank_power`,
      qualify group incentive, auto-open next cycle
- [ ] Group incentive payout — ceiling-capped (`staking_packages.group_ceiling`),
      gated by rank power qualification
- [ ] On-chain withdrawal payout — approve → `Web3bman::sendToken()` (BEP-20)
      from the treasury/gas wallet; store tx hash; retry per Token Settings
      (uses the web3 library, decrypt sending key just-in-time)

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
