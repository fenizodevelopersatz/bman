# Dynamic Landing Page Module — Setup & Reference

Turns the static **Webze ICO/Crypto** `index.html` into a database-driven page
that the admin edits from **Content Management → Landing Page Settings**.
The frontend HTML/CSS/Bootstrap/animations/JS are **unchanged** — only the
content is dynamic.

---

## 1. Install (one time)

```bash
# 1) import the schema + seed (defaults taken from index.html)
mysql -u <user> -p <database> < db/landing_page_schema.sql
```

**Permissions:** the page is allowed for super admins automatically, and for
restricted admins (`admin_roll = '1'`) it accepts **either** the new
`landing_page_cms` key **or** the existing `website_content_cms`
(Content-Management) key — so anyone who can already open *Website Content* can
open *Landing Page Settings*. No SQL is required. To register the explicit key
anyway:

```sql
UPDATE users
   SET permission_pages = JSON_SET(permission_pages, '$.landing_page_cms', 1)
 WHERE admin_roll = '1';   -- restricted sub-admins (super admins skip the check)
```

> If `landing-page-cms` redirected you back to `/admin`, it was this permission
> guard. The fallback above fixes it without any DB change — just reload the page.

That's it — the Webze template assets (`assets/css`, `assets/js`,
`assets/img/...`) are **already present** in the app, so the page renders
immediately.

---

## 2. URLs

| Purpose | URL |
|---------|-----|
| Public landing page | `<base_url>/landing` |
| Admin editor | `<base_url>/landing-page-cms` (sidebar: Content Management → Landing Page Settings) |
| Export content JSON | `<base_url>/landing-export` |

To make the landing page the site root, set in
`application/config/routes.php`:
`$route['default_controller'] = 'Landing';` (currently `welcome`).

---

## 3. Files added

| Layer | Path |
|-------|------|
| Schema + seed | `db/landing_page_schema.sql` |
| Model | `application/models/cms/Landing_model.php` |
| Admin controller | `application/controllers/admin/cms/Landingpage.php` |
| Admin view | `application/views/admin/cms/landing-page.php` |
| Admin repeater partial | `application/views/admin/cms/_landing_repeater.php` |
| Admin JS | `assets/admin/js/custom/cms/landing-page.js` |
| Public controller | `application/controllers/Landing.php` |
| Public view | `application/views/user/landing/index.php` |
| Helper | `application/helpers/landing_helper.php` (autoloaded as `landing`) |
| Routes | added to `application/config/routes.php` |
| Sidebar | menu item added under Content Management |

---

## 4. Data model

**Singletons** live in one key/value table — the same pattern as the existing
`site_settings` table:

```
landing_settings(section, skey, svalue)
  section ∈ general | header | hero | features | marquee | token | work |
            exchange | crypto | faq | roadmap | team | footer | seo |
            social | scripts
```
Read in code with `$this->Landing_model->get('hero','main_title')` or in the
view with `lp($hero,'main_title')`.

**Repeaters** (unlimited rows, drag-sortable, enable/disable) each get a table:

```
landing_menu  landing_brands  landing_features  landing_work
landing_exchange_logos  landing_crypto  landing_faq
landing_roadmap  landing_team
```

**Versioning**: `landing_versions(snapshot JSON)` powers Save Version /
Restore / Export / Import.

---

## 5. Admin features mapped to the spec

| Spec requirement | Where |
|------------------|-------|
| Collapsible card per section | Bootstrap accordion (`#lpAccordion`) |
| 17 sections (General … Scripts) | one card each |
| Unlimited brands/features/steps/cards/faq/roadmap/team | repeater partial |
| Add / Delete / Drag-sort / Enable | repeater table + `landing-item-*` routes |
| Image upload + preview + validation (png/jpg/jpeg/gif/svg/webp ≤4 MB) | `upload_image()` in controller, live preview in JS |
| Save / Save & Preview | each card saves via AJAX and auto-refreshes the preview iframe |
| Publish / Draft | `is_publish` column + per-row `status` |
| Restore Default / Version History | `landing_versions` + Save Version / Restore |
| Export JSON / Import JSON | `landing-export` / `landing-import` |
| Desktop / Tablet / Mobile preview | device toggle resizes the preview iframe |
| Validation (required, URL, email, duplicate sort/menu) | controller validators + reorder dedupe check |
| Security (auth, role, CSRF, XSS, SQLi) | session+role guard, CI XSS clean, query-builder binding, image MIME sniff |

---

## 6. How rendering works

`Landing::index()` loads every section + active repeater rows and passes them to
`user/landing/index.php`. Two helpers keep the markup identical to the template:

- `lp($arr,'key',$default)` — safe value getter.
- `lp_hl($title,$word)` — wraps the highlighted word in `<span>…</span>`
  (every Webze heading highlights one word).
- `lp_asset($path)` — prefixes stored relative paths with `base_url()`.

Section visibility toggles (`marquee.enable`, `exchange.enable`,
`general.enable_preloader`) are respected at render time.

---

## 7. Extending

To add a new repeater section: create the table, add it to
`Landing_model::$repeaters`, add a `repeater_fields()` whitelist entry in the
controller, and drop one `$this->load->view('admin/cms/_landing_repeater', …)`
call into the admin view. No new JS needed — the partial is fully generic.
