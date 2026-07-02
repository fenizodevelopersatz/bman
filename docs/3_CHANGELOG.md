# 3 — Changelog (every step, newest first)

Chronological record of work on the landing/home page module. Each entry lists
**what changed**, **files**, and **how to apply** (SQL/route/cache).

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
