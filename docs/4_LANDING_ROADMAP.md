# 4 — Landing Page Enhancement Roadmap

Trackable backlog for turning the landing module into a full campaign builder.
Items map 1-to-1 to the client's enhancement list (1–18). Each has **scope**,
**proposed DB**, **acceptance criteria**, and a checklist. Tick boxes as work
lands and log it in [3_CHANGELOG.md](3_CHANGELOG.md).

Status: ✅ done · 🟡 in progress · ⬜ planned

---

## Phase 1 — Foundation ✅ (shipped)

- [x] Dynamic landing module (17 sections, repeaters, version history)
- [x] Public `/landing` + Home `/` integration (old home → `/welcome`,`/shop-home`)
- [x] Dynamic Copyright on Site Settings
- [x] Shared meta (Site Settings ↔ landing SEO)
- [x] Permission fallback for `/landing-page-cms`

---

## Phase 2 — Global Section Controls  ⬜  (client items 1 & 6)

**Goal:** every section gets the same control block so admins can disable/reorder
a section without deleting its content.

**Per-section controls**
- [ ] Enable Section · Show on Homepage · Show in Navigation
- [ ] Section Order (drag) · Animation (Fade Up/…) · Background Color · Background Image · Custom CSS Class
- [ ] Per-section sub-toggles, e.g.
  - Hero: Show Email Form · Show Countdown · Show Hero Images · Show Floating Shapes
  - Features: Show Heading · Show Icons · Show Description · Show Animation
  - FAQ: Collapse First · Allow Multiple Open · Search · Category Filter
  - Team: Show Social · Show Position · Show Description · Carousel/Grid

**Proposed DB** — one row per section:
```sql
CREATE TABLE landing_sections (
  id INT AUTO_INCREMENT PRIMARY KEY,
  section VARCHAR(50) UNIQUE,      -- hero | features | faq | team | …
  enabled TINYINT(1) DEFAULT 1,
  show_home TINYINT(1) DEFAULT 1,
  show_nav TINYINT(1) DEFAULT 0,
  sort_order INT DEFAULT 0,
  animation VARCHAR(40) DEFAULT 'fade-up',
  bg_color VARCHAR(20) NULL,
  bg_image VARCHAR(255) NULL,
  css_class VARCHAR(120) NULL,
  options_json LONGTEXT NULL       -- per-section sub-toggles
);
```
**Acceptance:** disabling a section hides it on the public page but keeps data;
reordering changes render order; sub-toggles drive the existing markup.
**Touches:** `Landing_model` (sections API), admin view (control block per card),
public view (wrap each `<section>` in `if section_enabled()`), JS (sort).

---

## Phase 3 — Dynamic CTA + Forms + Leads  🟡  (client items 2, 3, 4, 7, 18)

**Goal:** replace the fixed "Get Early Access" with a configurable CTA + capture.

> **Started (2026-06-30):** `landing_leads` table created; the hero
> "Get Early Access" form now captures the email and sends an SMTP notification
> (`Landing::early_access()`). Still TODO: configurable CTA module, dynamic form
> types, external integrations, and the Landing Leads admin CRUD/CSV.

### 3a. CTA module
- [ ] Enable CTA · Type (Register/Login/Contact/Newsletter/External/Popup/Custom Form)
- [ ] Button Text · URL · Open in (same/new) · Icon · Color · Size · Animation
- [ ] Unlimited **Hero Buttons** (repeater): text, url, style, target

### 3b. Dynamic form
- [ ] Form Type (Email only / Name+Email / Name+Phone / Full Reg / Contact / None)
- [ ] Placeholders (email/name/phone) · Success Message · Redirect URL

### 3c. CTA actions (on submit)
- [ ] Save Email · Register Lead · Webhook · Google Sheet · API · Custom PHP fn
- [ ] Integrations: Mailchimp · Brevo · generic CRM

### 3d. Lead Management — **Content Management → Landing Leads**
- [ ] List: Name · Email · Phone · Source · Landing Page · Date · Status · Notes
- [ ] Filters + **Export CSV**

**Proposed DB**
```sql
CREATE TABLE landing_cta (
  id INT AUTO_INCREMENT PRIMARY KEY,
  enabled TINYINT(1) DEFAULT 1,
  cta_type VARCHAR(30), button_text VARCHAR(120), button_url VARCHAR(255),
  open_in VARCHAR(10) DEFAULT 'same', icon VARCHAR(60), color VARCHAR(20),
  size VARCHAR(20), animation VARCHAR(40),
  form_type VARCHAR(30), placeholder_json LONGTEXT,
  success_message VARCHAR(255), redirect_url VARCHAR(255),
  action_type VARCHAR(40), action_config_json LONGTEXT
);
CREATE TABLE landing_buttons (        -- unlimited hero buttons
  id INT AUTO_INCREMENT PRIMARY KEY, section VARCHAR(40) DEFAULT 'hero',
  text VARCHAR(120), url VARCHAR(255), style VARCHAR(30), target VARCHAR(10),
  icon VARCHAR(60), sort_order INT DEFAULT 0, status TINYINT(1) DEFAULT 1
);
CREATE TABLE landing_leads (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150), email VARCHAR(190), phone VARCHAR(40),
  source VARCHAR(80), landing_page VARCHAR(120),
  status VARCHAR(30) DEFAULT 'new', notes TEXT,
  ip VARCHAR(45), created_at DATETIME
);
```
**Dynamic CTA examples** (item 18): Register→/register, Login→/login,
Investment→/investment, MLM→/register?sponsor=, Download→file, Contact→form,
Telegram→external, Video→popup, Custom→any.
**Acceptance:** admin builds any CTA without code; form submit stores a lead and
runs the chosen action; leads visible + exportable.

---

## Phase 4 — Hero Advanced  ⬜  (client item 5)

- [ ] Enable Video Background · Background Video upload
- [ ] Typing Animation · Particle Effect · Mouse Effect · Floating Shapes toggle
- [ ] Hero Cards (repeater) · Hero Statistics (repeater) · Hero Buttons (Phase 3b)

**Proposed DB:** extend `landing_settings` hero keys + `landing_hero_stats`,
`landing_hero_cards` repeaters. **Acceptance:** each effect toggles independently;
falls back to current static hero when all off.

---

## Phase 5 — Builder UX: Preview, Buttons, Media, Theme  ⬜  (items 8, 11, 12, 14)

### 5a. Better live preview (item 8)
- [x] Desktop/Tablet/Mobile · **Dark/Light** · Open New Tab · Refresh
- [ ] **Auto Refresh** · Preview **Draft** vs **Published**

### 5b. Button library (item 11)
- [ ] Styles: Primary · Secondary · Outline · Gradient · Ghost · Rounded · Square · Icon
- [ ] Reusable picker wherever a button is configured

### 5c. Media manager (item 12)
- [ ] Upload · Choose existing · Crop · Resize · Compress · WebP · Alt/Title · Lazy · Delete
- [ ] Backed by a `landing_media` table; GD/Intervention for crop/compress/webp

### 5d. Landing theme (item 14)  🟡
- [x] **Light/Dark** via `theme_mode` setting (drives template `data-theme`); palette → `--tg-*` CSS vars; default light
- [ ] Auto theme · Typography · Border radius · Container width

**Proposed DB**
```sql
CREATE TABLE landing_media (
  id INT AUTO_INCREMENT PRIMARY KEY, path VARCHAR(255), webp VARCHAR(255),
  alt VARCHAR(190), title VARCHAR(190), width INT, height INT,
  size_kb INT, created_at DATETIME
);
```

---

## Phase 6 — SEO+, Analytics, Version Control  ⬜  (items 9, 10, 13)

### 6a. SEO+ (item 9)
- [ ] Existing: title/description/keywords/OG/twitter/canonical/robots
- [ ] **Add:** Schema.org JSON-LD · Google verification · Bing verification

### 6b. Landing analytics (item 10)
- [ ] Views · Clicks · CTR · CTA clicks · Form submissions · Conversion rate
- [ ] Top section · Top device · Traffic source
- [ ] DB: `landing_events(type, section, device, source, created_at)` + dashboard card

### 6c. Version control upgrades (item 13)
- [ ] Existing: Draft/Published/Restore/Export/Import
- [ ] **Add:** Compare (diff two snapshots) · Clone · richer History UI

---

## Phase 7 — Conversion widgets  ⬜  (items 15, 16, 17)

### 7a. Announcement bar (item 15)
- [ ] Enable · Text · Button · Countdown · Close button · Sticky · Color
- [ ] DB: `landing_announcement` (single row)

### 7b. Popup manager (item 16)
- [ ] Newsletter · Exit-intent · Promotion · Video · Age verification · Cookie consent
- [ ] DB: `landing_popups(type, content_json, trigger, status)`

### 7c. Homepage statistics from MLM DB (item 17)
- [ ] Members · Invested · Withdrawn · Countries · Years · Daily earnings · Success rate
- [ ] **Live values** pulled from existing MLM tables (users, user_investment,
      withdraw, wallet_transactions); admin picks which stats to show + labels.
- [ ] DB: `landing_stats(label, source_key, manual_value, is_live, sort, status)`

---

## Cross-cutting (apply to every phase)

- [ ] CSRF on all admin POSTs · auth + role check · XSS clean · query-builder (SQLi-safe)
- [ ] Image validation: type (png/jpg/jpeg/gif/svg/webp), size ≤ 4 MB, MIME sniff
- [ ] Cache landing settings · lazy-load images · compress uploads · minify assets
- [ ] Every new repeater reuses `_landing_repeater.php`; every new section reuses
      the section-control block (Phase 2) — no bespoke JS per section.

---

## Suggested delivery order

1. **Phase 2** (section controls) — unlocks "disable without delete", foundation for the rest.
2. **Phase 3** (CTA + leads) — highest business value (campaign capture).
3. **Phase 7c** (homepage statistics) — quick win, uses existing MLM data.
4. **Phase 4 / 5 / 6** — polish, builder UX, growth instrumentation.
5. **Phase 7a/7b** (announcement/popups) — conversion widgets.
