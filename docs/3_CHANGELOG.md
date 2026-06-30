# 3 — Changelog (every step, newest first)

Chronological record of work on the landing/home page module. Each entry lists
**what changed**, **files**, and **how to apply** (SQL/route/cache).

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
