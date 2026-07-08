# 15 — "Stakings" Page Redesign (user side)

Status: 🟡 **In progress.** Redesign of the user **Package** page
(`user/lending` → `Lendingcontroller::index` → view
`user/wallet/lending_managment.php`) into a professional **Stakings** management
page. This doc is the grounded, phased plan; Phase 1 (the all-tabs investment
detail API + rename groundwork) is implemented.

Links: [6_STAKING_PACKAGES_PLANS_ROI.md](6_STAKING_PACKAGES_PLANS_ROI.md) ·
[13_ONCHAIN_TRANSACTIONS.md](13_ONCHAIN_TRANSACTIONS.md) ·
[14_ONCHAIN_SYNC_LIFECYCLE.md](14_ONCHAIN_SYNC_LIFECYCLE.md) ·
[0_INDEX.md](0_INDEX.md) · [3_CHANGELOG.md](3_CHANGELOG.md).

---

## 0. What exists today (grounding)

| Piece | Where |
|---|---|
| Page | route `user/lending` → `controllers/user/usersettings/Lendingcontroller.php::index()` → view `views/user/wallet/lending_managment.php` |
| Packages | `getPackagesForView()` (legacy `package_config`) + `getStakingPackagesForView()` (new `staking_packages` + ROI matrix) |
| Portfolio | `getUserInvestmentsForView()` over `user_investment` (+ `package_config`) |
| ROI history | `history` where `invest_id=?` and `hash_id='roi-made'` (type `profit`) |
| Details popup | `details_ajax()` — small paginated ROI list |
| Purchase | `stake_quote()` / `purchase_stake()` / `swap_purchase()` / `makeinvestment_post()` |
| Stat cards | wallet balances passed to the view (USDT/Invested/ROI/Next payout/Active) |

**These map onto the backend already built:** on-chain tx history →
`onchain_transactions`; ledger → `wallet_ledger`; audit → `onchain_tx_events`;
tx verification/gas/confirmations → `Chainsync_model`.

---

## 1. UI changes

- **Wallet strip (implemented).** The strip in `lending_managment.php` now leads
  with the **USDT Wallet** — the wallet staking purchases are actually funded from
  (`user_wallets.usd_balance`, ledger type `usdt`; debited on purchase in
  `Staking_model` via `L->debit($uid,'usdt',…,'stake_purchase')`). It shows the
  USDT balance + the ≈BMAN equivalent at the admin rate (`$wallet_usdt` /
  `$wallet_usdt_in_bman`, already supplied by the controller). The four BMAN
  wallets follow for context, with the **Earning Wallet** tagged *"ROI credited
  here"* — that is the ROI destination (`staking_roi_payouts.wallet = 'earning'`).
  ⚠️ ROI payout rows are inserted `pending` at purchase but are **not yet
  credited** — no ROI cron is registered (see `6_STAKING_PACKAGES_PLANS_ROI.md`
  §"ROI credit cron ⬜").
- **Remove the 5 stat cards** (Available USDT / Total Invested / Total ROI /
  Next Payout / Active Plans) — already on the Wallet/Dashboard. (Phase 2: delete
  the card block in `lending_managment.php` + stop passing the values.)
- **Rename Package → "Stakings"** everywhere: route (`user/stakings` alias added
  now; keep `user/lending` as a redirect for old links), sidebar menu label,
  breadcrumb, `title`, `card_title`. (Phase 2.)

## 2. Staking Packages section (cards)

Responsive cards per package showing: amount, name, Fixed ROI (2/3/5Y), Regular
ROI (2/3/5Y), lock period, estimated maturity return, monthly ROI (Regular),
one-time ROI (Fixed), purchase button, availability, min requirements, T&C popup.
Highlight a package the user already owns. Data source: `getStakingPackagesForView()`
(already returns the ROI matrix) + owned-flag from `user_investment`. (Phase 2.)

## 3. My Staking Portfolio (server-side table)

Columns: Package ID, Name, Stake Amount, Plan Type, Duration, Purchase Date,
Maturity Date, Days Remaining, Current ROI %, Total ROI Earned, Pending ROI, Next
ROI Date, Status (Active/Pending/Matured/Completed/Cancelled), Actions.
Server-side search / sort / filter / pagination + **CSV & Excel export**. New
endpoint `portfolio_list` (mirrors the admin on-chain grid pattern). (Phase 3.)

## 4. Details modal — 7 tabs (Phase 1 API ✅, UI Phase 4)

Full-screen modal / side drawer. One endpoint **`staking_detail(invest_id)`**
returns all tabs (implemented now):

1. **Package Information** — id, name, stake, plan type, ROI structure, purchase/
   activation/maturity dates, lock period, days remaining, status, wallet used,
   tx hash, explorer link. (`user_investment` + `package_config` + Token Settings.)
2. **ROI History** — date, cycle #, ROI %, amount, wallet credited, tx hash,
   chain status, gas fee, confirmations, created time + totals (earned / remaining
   / expected final). (`history` roi rows, enriched from `onchain_transactions`.)
3. **Transaction History** — every blockchain tx for the package (purchase, bonus,
   ROI, transfers, withdrawals, maturity payout): hash, block, date, type, wallet,
   amount, gas used, gas fee, status, explorer. (`onchain_transactions`.)
4. **Ledger History** — before/after balance, credit, debit, source, destination,
   reference id, description, created_by. (`wallet_ledger`.)
5. **Timeline** — Purchased → Activated → ROI Cycle 1…N → Matured → Completed,
   each with date/time/description/related tx. (computed from dates + ROI rows.)
6. **Documents** — purchase receipt, blockchain receipt, investment agreement,
   ROI schedule, tax report (future). (receipt endpoints; PDF later.)
7. **Audit Log** — created/updated/cron/ROI/bonus/wallet/admin events with
   timestamp, IP, actor, server, request id. (`onchain_tx_events` + a light
   package audit.)

## 5. Functionality — calculations

Per investment, computed centrally (a `Stakingcalc` helper, Phase 3):
purchase date, activation date, lock period, maturity date, remaining days, next
ROI date, completed/remaining ROI cycles, total ROI earned, pending ROI, expected
final return. **Fixed:** no monthly ROI, countdown to maturity, single maturity
payout after maturity (once). **Regular:** monthly payments, upcoming schedule,
duplicate-ROI prevention (the ROI cron already guards via `run_date`/`days_count`;
see [6](6_STAKING_PACKAGES_PLANS_ROI.md)).

## 6. Performance & security

- Server-side pagination + lazy tab loading (each modal tab fetches on open).
- Indexes: `history(invest_id,hash_id)`, `onchain_transactions(user_id,reference_*)`,
  `wallet_ledger(user_id)` (present).
- Read-only history; **immutable** ROI (`history`), blockchain (`onchain_transactions`)
  and audit (`onchain_tx_events`) records; double-entry ledger validation
  (`Walletledger_model`); per-user permission (session guard, ownership check on
  every query); audit logging.

---

## 7. Phased delivery

| Phase | Scope | Status | Commit |
|---|---|---|---|
| **1** | `staking_detail` all-tabs data API + `user/stakings` route alias | 🟢 done | 0cb7360 |
| **2** | Rename Package→Stakings (menu/title/hero) + remove 5 KPI cards + card redesign (owned/terms/T&C) | 🟢 done | 0cb7360 |
| **3** | Server-side portfolio table (search/sort/filter/paginate) + CSV/Excel export + verified calcs | 🟢 done | 0594901 |
| **4** | Full-screen 7-tab details modal (lazy tabs) wired to the Phase-1 API, real data only | 🟢 done | 0dab51f |
| **5** | Documents: Receipt / Agreement / ROI Schedule / Summary — branded printable HTML (print→PDF) + QR, owner/admin-only, metadata + audit | 🟢 done | 0f6902c |

**Verification per phase:** lint clean on every touched file; routes resolve
(guarded 303/307 → login); portfolio calculations verified against real
`user_investment` data (`expected = amount×roi/100×days`, earned = ROI-history
sum, days_remaining = `max(0, datediff)`); each phase committed separately for
review/rollback.

## 8. Files

**New:** `docs/15_STAKINGS_PAGE_REDESIGN.md`; Phase 1 adds
`Lendingcontroller::staking_detail()` + `user/stakings` route alias.
**Later (Phase 2-5):** edits to `lending_managment.php`, `admin`/user sidebar,
new `portfolio_list` + `Stakingcalc` helper + modal view partial + document/PDF
endpoints.
