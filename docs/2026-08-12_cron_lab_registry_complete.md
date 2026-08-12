# 2026-08-12 — Cron Lab: complete the registry, single place to manage all crons

Cron Lab (`admin/wallet/cron-lab`) already had "Run Now" buttons + endpoint
copy + descriptions for most crons, but it was missing 3 real, working
crons entirely, and had no schedule/CLI info at all — an admin still had to
go dig through each controller's own docblock (inconsistent, some stale)
to know how often to run something.

## What was missing

Cross-checked every `$route[...cron...]` in `routes.php` against Cron Lab's
`jobs` array. Found:
- `Depositcron` (`credit-deposits-cron`) — real, working, not listed.
- `Bonusreductioncron` (`bonus-reduction-cron`) — real, working, not listed.
- `Swaporders::deliver_cron` (`deliver-bman-cron`) — real, working, not listed.
- `Staking_roi_cron`, and `Cron::run_roi`/`update_all_users_rank`/
  `binary_commission_call` — routes exist but the underlying files are
  either missing entirely or a 0-byte empty file. **Dead routes, correctly
  never listed** — left out on purpose, not an oversight.

## What changed

- Added the 3 missing jobs to `Cronlab::index()`'s `jobs` array.
- Added a `schedule` field (human-readable recommended frequency) and a
  `cli` field (exact CLI invocation) to **every** job entry, all 14. Pulled
  from each controller's own docblock where one exists (`StakingPurchasecron`,
  `BmanWithdrawCollectCron`, `Depositcron`, `WalletMaturity_cron`,
  `RankAchievementCron`, `RankPowerCron`, `BinaryMatchingPayoutCron`, etc. all
  have one) rather than guessed — one case (`staking-purchase-cron`) turned
  up a stale inline comment in `routes.php` claiming "hourly" when the
  controller's own docblock says "every minute"; trusted the controller.
  `Swaporders::deliver_cron` has no documented cadence anywhere — flagged
  as "undocumented — verify" rather than presenting a guess as fact.
- Reordered the array so **Wallet Maturity comes before ROI Distribution**
  in the list (matches the required run order — ROI Maturity Payment reads
  `is_matured` flags Wallet Maturity just flipped).
- View (`cron_lab.php`): each card now shows a schedule pill and a `CLI:`
  line, plus a new "Copy CLI" button (mirrors the existing "Copy endpoint"
  button — clipboard write only, same limitation as the existing copy
  button on plain-HTTP/insecure-context pages).

## Verified

`php -l` on both files. Extracted the real `jobs` array from the live
controller file and iterated every entry through the exact same
`!empty($job['schedule'])`/`!empty($job['cli'])` logic the view uses —
confirmed all 14 jobs have both fields present (no undefined-key warnings)
and print correctly, including `binary_matching_probe`'s `cli => null`
(manual-only diagnostic, correctly shows no CLI line).

Did not attempt full browser/session-authenticated rendering — this repo's
established technique for that requires a temporary `ZzTestLogin.php`
auth-bypass controller, which may be blocked by this environment's file
classifier, and pure PHP-CLI array iteration already covers the actual
risk (new fields on existing, already-working card markup).

## Scope note

This is the "complete the existing Cron Lab page" option — no system
crontab access, no shell exec, no new security surface. Actually writing
the recommended schedules into the server's real crontab is still a manual
step for whoever operates the box.
