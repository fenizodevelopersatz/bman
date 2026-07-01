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

---

## Status dashboard

| Area | Status | Notes |
|------|:------:|-------|
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
