# 5 — KYC Verification & Controlled State Machine

Reference for the **KYC module** on the Nexman/BMAN MLM (CodeIgniter) platform.
Covers the manual-KYC form, the admin review workflow, and the **controlled
state machine** that now governs every status change. No DB schema change was
needed — canonical states map onto the existing enum values.

> Related: log entries in [3_CHANGELOG.md](3_CHANGELOG.md). Index in
> [0_INDEX.md](0_INDEX.md).

---

## 1. Statuses

Five canonical states are the single source of truth (used in the API, audit log
and both UIs). They map onto the pre-existing `kyc_applications.status` /
`users.kyc_status` enum values, so **no migration is required**.

| Canonical state | DB enum value | Meaning |
|---|---|---|
| `NOT_SUBMITTED` | *(no row)* / `none` | User has never submitted |
| `PENDING` | `pending` | Submitted, awaiting review |
| `UNDER_REVIEW` | `under_review` | Admin is verifying |
| `APPROVED` | `approved` | Verified (terminal) |
| `RESUBMIT_REQUIRED` | `rejected` | Admin asked user to resubmit (reason attached) |

> Legacy value `resubmitted` is also read as `RESUBMIT_REQUIRED` for old rows.

---

## 2. Transitions

Defined once in `Kyc_model::$transitions` and enforced on **every** change.

| Action | From | To | Reason required |
|---|---|---|:--:|
| `submit` | `NOT_SUBMITTED`, `RESUBMIT_REQUIRED` | `PENDING` | — |
| `start_review` | `PENDING` | `UNDER_REVIEW` | — |
| `approve` | `UNDER_REVIEW` | `APPROVED` | — |
| `request_resubmission` | `UNDER_REVIEW` | `RESUBMIT_REQUIRED` | ✅ |

```
NOT_SUBMITTED ──submit──▶ PENDING ──start_review──▶ UNDER_REVIEW ──approve──▶ APPROVED
       ▲                                                  │
       └──────────── submit ◀── RESUBMIT_REQUIRED ◀── request_resubmission (reason)
```

Any action attempted from a state not listed in **From** is rejected with
HTTP 422 and never mutates data.

---

## 3. Rules enforced

- Users can upload **only** when `NOT_SUBMITTED` or `RESUBMIT_REQUIRED`
  (`Kyc_model::canUserEdit()`); editing is blocked while `PENDING`,
  `UNDER_REVIEW`, `APPROVED`.
- Any user upload auto-transitions to `PENDING`.
- Resubmission overwrites the previous document URLs on the same row (no separate
  version table; the transition trail lives in the audit log).
- Admin `PENDING → UNDER_REVIEW` via **Start Review**.
- From `UNDER_REVIEW` admin may only **Approve** or **Request Resubmission**.
- **Request Resubmission** requires a reason (stored in `review_notes`, shown to
  the user).
- The admin UI uses **action buttons only** — no manual status dropdown. The only
  status `<select>` on the page is the list *filter*, which does not change data.
- **Every** transition is written to `kyc_audit_logs` (`action`, `from -> to`,
  reason, actor, timestamp).

---

## 4. Files changed

| File | Change |
|---|---|
| `application/models/Kyc_model.php` | State machine: constants, `$dbMap`, `$transitions`, `toDb()`, `fromDb()`, `canUserEdit()`, `canApply()`, `reasonRequired()`, `applyAdminAction()` |
| `application/controllers/user/Kyc.php` | `submit()` guarded by `canUserEdit()`; logs `submit` transition; `index()` passes canonical `state` + read-only |
| `application/controllers/admin/AdminKyc.php` | `decision()` is now **action-based** (`start_review` / `approve` / `request_resubmission`), validated by the state machine; legacy `status=` still accepted (mapped) |
| `application/views/user/account/kyc_form.php` | Status pill shows the 5 canonical states; resubmission banner; read-only per state |
| `application/views/admin/kyc_list.php` | JS cache-buster `ver=3.1` |
| `assets/admin/js/custom/authentication/sign-in/kyc-request-list.js` | Contextual action buttons per state; sends `action`; canonical badges; mandatory resubmission reason |

**No SQL / schema change.** Reuses `kyc_applications`, `users`, `kyc_audit_logs`.

---

## 5. API

`POST admin/kyc/decision/{id}` *(AJAX)* — body:

- `action` = `start_review` | `approve` | `request_resubmission` (preferred), **or**
- legacy `status` = `under_review` | `approved` | `rejected` (auto-mapped)
- `notes` = reason (required for `request_resubmission`)

Responses: `200 {status:success,state:<CANONICAL>}` · `422 {status:error,message}`
for an invalid transition or a missing reason.

`POST user/kyc/submit` *(AJAX)* — allowed only from `NOT_SUBMITTED` /
`RESUBMIT_REQUIRED`; on success the row moves to `PENDING`.

---

## 6. Validation (live, `localhost:9000`)

| Test | Result |
|---|---|
| `approve` from `PENDING` | 422 "Invalid transition: cannot approve from PENDING." |
| `start_review` (`PENDING → UNDER_REVIEW`) | 200 `UNDER_REVIEW` |
| `start_review` from `UNDER_REVIEW` | 422 invalid |
| `request_resubmission` without reason | 422 "A resubmission reason is required." |
| `approve` (`UNDER_REVIEW → APPROVED`) | 200 `APPROVED` |
| Legacy `status=rejected` + reason | 200 `RESUBMIT_REQUIRED`, `review_notes` + user sync set |
| Audit log | Each transition recorded (`action`, `from -> to`, reason) |

---

## 7. How to apply

- No migration. Deploy the PHP/JS files.
- Hard-refresh the admin KYC page (JS bumped to `?ver=3.1`).
- Existing rows keep working: their enum values map to canonical states
  automatically via `fromDb()`.
