# Cron Schedule — single source of truth

Every other cron doc in `docs/` predates this file and they contradict each other
(20+ files, conflicting cadences, several dead endpoints). **This file wins.**

Cadences below come from the controllers' own header comments (the only cron
documentation that has stayed in sync with the code), reconciled on
2026-08-05 against the final keep/merge/manual decision for each job. See
§0 for what changed in that pass.

---

## 0. 2026-08-05 changes

- **ROI Monthly / ROI Maturity "(leg only)" cards removed from Cron Lab.**
  Cron Lab now shows only the major/combined crons — one card per feature
  area — dropping the two granular debug-only ROI legs (jobs 5a/5b below)
  now that the combined "ROI Distribution (Monthly + Maturity)" card is the
  documented normal-run path. The underlying routes
  (`roi-monthly-distribution-process`, `roi-maturity-payment-process`)
  still exist and still work if you need to retry one leg directly — just
  hit them by URL (with `?token=`) or from ROI Distribution History; there's
  no other caller in the codebase that depended on the Cron Lab button, so
  removing the button and its `run()` case was a clean deletion, same as
  the bonus handler below. Rank Achievement / Rank Power were **not**
  touched the same way — they're each a first-class, independent stage (no
  "debug-only" version to drop), so both cards stay.
- **Bonus Wallet Reduction removed from Cron Lab.** `Cronlab::run()` had an
  orphaned `case 'bonus':` handler with no corresponding button in the
  `jobs` array — reachable only by POSTing `job=bonus` directly, not by
  anything in the UI. Confirmed nothing else in the codebase does that, so
  the handler was deleted outright. The feature itself is untouched: it has
  its own dedicated route (`bonus-reduction-cron`) and its own admin page
  (`admin/dashboard/bonus-reduction` → `Dashboard::bonus_reduction`) — manage
  it there, not through Cron Lab. See §1, job 11.
- **Rank Achievement + Rank Power now scheduled together, once daily.**
  Documentation/scheduling change only — **no PHP was changed.** The two
  endpoints (`rank-achievement-cron`, `rank-power-cron`) still exist
  separately and Cron Lab still shows two buttons; only the *recommended
  crontab cadence* changed; Achievement moves from hourly to daily so both
  run back-to-back, the same pattern already used for the four treasury
  broadcasters. Consequence: a new permanent-rank promotion now surfaces
  within a day instead of within an hour — idempotent either way, this is
  a latency trade-off only, not a correctness change. If you have an
  existing hourly crontab entry for `rank-achievement-cron`, replace it
  with the daily line in §2.
- **Binary Matching Payout split onto its own 5-min crontab line** (still
  under the same `flock` lock as the other treasury broadcasters, so it
  still can never overlap them). It was previously inside the 1-min
  treasury line, which ran it 5× more often than its own documented
  cadence.
- **Member Bulk Upload BMAN delivery taken off the automatic crontab.**
  It's manual/on-demand only now: trigger it from Cron Lab's "Run now" or
  the bulk-upload batch detail page's re-queue action when there's an
  actual batch to deliver for. It was previously in the 1-min treasury
  line, polling for work that (by nature — bulk uploads are infrequent
  admin actions) is almost never there.
- **Wallet Transfer Settlement's documented cadence corrected to every
  1 min**, matching what the crontab already actually ran it at (its own
  code comment said "every few minutes," which underrated the real polling
  frequency — the underlying logic already no-ops when there's nothing to
  settle).

---

## 1. Final schedule

| # | Job | Route (HTTP) | Cadence | Sends on-chain? | Notes |
|---|-----|--------------|---------|-----------------|-------|
| 1 | Staking Purchase (gas → USDT → BMAN → bonus) | `staking-purchase-cron` | every 1 min | **yes — Treasury** | Sole authority for crediting staking-purchase wallets. |
| 2 | Deposit credit (incoming USDT → USDT wallet) | `credit-deposits-cron` | every 1–3 min | no (read-only) | Not in Cron Lab's button list; still live infrastructure. |
| 3 | Chain sync (balances + pending-tx confirmations) | `chain-sync-cron` | every 1–5 min | no (read-only) | Backfills real gas_used/gas_price so Gas Fee Transactions shows real numbers. |
| 4 | Binary Matching Payout (engine + on-chain drain) | `binary-matching-payout-cron` | every 5 min | **yes — Treasury** | Idempotent, safe to click repeatedly. |
| 5 | ROI Distribution (Monthly → Maturity, in that order) | `roi-distribution-cron` | daily | no | Already one merged endpoint — runs both legs correctly ordered. The two leg-only routes below exist for targeted debugging only. |
| 5a | ↳ ROI Monthly (leg only) | `roi-monthly-distribution-process` | not scheduled | no | **Not in Cron Lab** (see §0) — debug/retry only, hit by URL. The combined job above already includes it. |
| 5b | ↳ ROI Maturity (leg only) | `roi-maturity-payment-process` | not scheduled | no | **Not in Cron Lab** (see §0) — debug/retry only, hit by URL. The combined job above already includes it. |
| 6 | Rank Calculation — Achievement (permanent ranks, §10) | `rank-achievement-cron` | **daily** (was hourly — see §0) | no | Scheduled together with Rank Power, back-to-back. Can only promote, never demote. |
| 6a | ↳ Rank Power (60-day cycle, §11) | `rank-power-cron` | daily | no | Must run **after** Achievement — see §3 ordering rule. |
| 7 | Lock Wallet Unlock (`is_matured` flip on `wallet_ledger`) | `wallet-maturity-cron` | daily | no | Required for withdrawal eligibility. |
| 8 | Wallet Settlement (on-chain transfer sweep) | `wallet-transfer-settlement-cron` | every 1 min | **yes — Treasury** | Disabled + dry-run by default — flip `wallet_transfer_settlement_settings.enabled`/`dry_run` to go live. |
| 9 | Bulk Upload — opening BMAN delivery | `member-bulk-bman-cron` | **manual / on-demand only** (was every 1 min — see §0) | **yes — Treasury** | Disabled + dry-run by default. Run from Cron Lab or the batch detail page only when a bulk-upload batch actually needs delivering. |
| 10 | Bonus Wallet 60-day reduction | `bonus-reduction-cron` | daily | optional | **Not in Cron Lab** (see §0) — managed at `admin/dashboard/bonus-reduction`. |

Jobs 5a/5b exist purely for targeted retry from ROI Distribution History —
**do not schedule them separately**, job 5 already runs both in the required
order. Same logic applies to 6/6a: schedule both, but never *only* 6a.

---

## 2. Crontab

Use the **HTTP form**. It resolves through `routes.php`, which carries the exact
class casing; the CLI form depends on CI3's `ucfirst()` file lookup matching the
on-disk filename, which several of these controllers do not satisfy on a
case-sensitive filesystem (`RoiDistribution_cron.php` vs `Roidistribution_cron`).

Replace `HOST` and `TOKEN` (`$config['cron_token']`).

```
# --- Treasury broadcasters: ONE lock file, shared across ALL of them -------
# All sign from the same Treasury address. See §4 — none of these may ever
# run concurrently with another, regardless of their own cadence, or they
# collide on the account nonce. flock -n makes a skipped run a no-op, not a
# queued one, so the faster line never waits on the slower one.
* * * * * flock -n /tmp/nexman-treasury.lock -c 'curl -s "HOST/staking-purchase-cron?token=TOKEN"; curl -s "HOST/wallet-transfer-settlement-cron?token=TOKEN"' >> cronlogs/treasury.log 2>&1
*/5 * * * * flock -n /tmp/nexman-treasury.lock -c 'curl -s "HOST/binary-matching-payout-cron?token=TOKEN"' >> cronlogs/binary.log 2>&1

# --- Read-only chain pollers (safe to run alongside anything) --------------
*/2 * * * * curl -s "HOST/credit-deposits-cron?token=TOKEN" >> cronlogs/deposits.log 2>&1
*/3 * * * * curl -s "HOST/chain-sync-cron?token=TOKEN" >> cronlogs/chainsync.log 2>&1

# --- Daily (order matters within each pair) ---------------------------------
0 1 * * * curl -s "HOST/roi-distribution-cron?token=TOKEN"        >> cronlogs/roi.log 2>&1
0 2 * * * curl -s "HOST/rank-achievement-cron?token=TOKEN"        >> cronlogs/rank.log 2>&1
0 2 * * * sleep 60 && curl -s "HOST/rank-power-cron?token=TOKEN"  >> cronlogs/rankpower.log 2>&1
0 3 * * * curl -s "HOST/bonus-reduction-cron?token=TOKEN"         >> cronlogs/bonus.log 2>&1
0 4 * * * curl -s "HOST/wallet-maturity-cron?token=TOKEN"         >> cronlogs/walletmaturity.log 2>&1

# --- Manual only — do NOT add a crontab line for this ------------------------
# member-bulk-bman-cron: trigger from Cron Lab or the bulk-upload batch page
# only when a batch actually needs its opening BMAN delivered.
```

If `flock` is unavailable on the host, keep the single-line sequential form and
accept the (smaller) risk that one pass runs long into the next minute.

---

## 3. How they interconnect

Data flows one way. Nothing downstream feeds back upstream.

```
Depositcron ──► USDT wallet
                   │
                   ▼
        StakingPurchasecron ──► stake created (roi_staking_management)
                   │                    │              │
                   ▼                    ▼              ▼
        BinaryMatchingPayout      RoiDistribution   Rank Achievement
                   │              (monthly→maturity)      │
                   ▼                    ▼                 ▼
                   └────────► wallet_ledger credits   Rank Power
                                        │
                                        ▼
                              WalletMaturity_cron (is_matured flip)
                                        │
                                        ▼
                              withdrawal eligibility
```

Chainsynccron sits beside all of it, refreshing balances and advancing
confirmations for whatever is in flight.

Three ordering rules actually matter:

1. **Monthly ROI before Maturity ROI.** Maturity skips regular/combo records
   whose monthly schedule is unfinished. `roi-distribution-cron` already
   enforces this — that is its whole purpose.
2. **Rank Achievement before Rank Power.** Not enforced by the code (they're
   still two independent endpoints — see §0) — enforced by *scheduling*
   them in that order, one minute apart, as shown in §2. Power computes
   from current-cycle staking volume and doesn't depend on same-day
   promotions to be correct, but running Achievement first means a same-day
   promotion is reflected everywhere consistently instead of looking stale
   for the rest of that day.
3. **Rank Power rolls the cycle before it computes power.** Enforced inside
   `Rankcron_model::runPower()`.

Everything else is order-independent and idempotent.

---

## 4. The one hard constraint: Treasury nonce

`Web3bman::buildSignSend()` fetches the nonce fresh per send:

```php
$nonce = $this->rpc('eth_getTransactionCount', [$from, 'pending']);
```

There is no local nonce reservation and no lock. Three crons sign from the same
Treasury address (jobs 1, 4, 8 — job 9 too, on the rare occasion it's run
manually while one of the others is mid-broadcast). If two broadcast in the
same window they both read the same pending nonce; the second transaction is
then rejected or silently replaces the first. The database records "sent, hash
X" for a transaction that never lands.

The crontab in §2 fixes this operationally with one shared `flock` lock file
across every treasury-signing line, regardless of cadence. The durable fix is
a shared advisory lock around treasury broadcasts, following the pattern already
used in `Matchingqueue_model` (`SELECT GET_LOCK(...)`).

---

## 5. Do not schedule these

| Endpoint / command | Why |
|---|---|
| `staking-roi-cron` | Route exists, controller `Staking_roi_cron.php` does not. 404. |
| `earn-cron-made`, `rank-cron-made`, `binary-cron-made` | All map to `Cron.php`, which is a 0-byte file. |
| `roi-maturity-process`, `roi-maturity-test` | Legacy `RoiMaturityCron`. **Double-pay risk — see below.** |
| `cron/roi_maturity/process` | Same legacy logic, second copy under `controllers/cron/`. |
| `cron binarymatchingcron_simple process` | Superseded by `binary-matching-payout-cron`. Still advertised in the admin Binary Matching dashboard UI. |
| `deliver-bman-cron`, admin "Send BMAN" button | Legacy pre-4-step-cron delivery path (`Swapengine_model::deliverBman()`). Guarded as of 2026-08-05 to refuse unless `staking-purchase-cron` has already confirmed payment, and no longer credits any wallet itself — but it's still not something to put on a schedule; `staking-purchase-cron` (job 1) already delivers BMAN. |
| Cron Lab `job=bonus` | Removed 2026-08-05 (§0) — was never wired to a button, and is superseded by the dedicated `bonus-reduction-cron` route (job 10). |
| `php index.php ... run` for the two ROI legs | Those controllers expose `process()`, not `run()`. Every doc showing `run` is wrong. |

### The double-pay risk

Legacy `RoiMaturityCron` and current `RoiMaturityPayment_cron` implement the
same maturity payout over **two disjoint state machines**:

| | Legacy | Current |
|---|---|---|
| Completion flag | `staking_swap_orders.roi_return_status` | `roi_staking_management.overall_status` |
| Credit path | direct insert into `roi_distribution` | `Walletledger_model` (`wallet_ledger`, idempotent on `tx_hash`+wallet) |
| Dedupe key | `onchain_transactions.tx_type='roi'` | `wallet_ledger` unique index |

Neither reads the other's flag. The current cron never sets
`roi_return_status`, so the legacy cron sees every matured order as unpaid and
pays it a second time through a separate ledger.

Several older docs still instruct scheduling the legacy endpoint. They are wrong
and should not be followed.

---

## 6. Dead files (safe to delete)

Not routed, not referenced by any controller, model or view:

- `application/controllers/DailyCommission.php` (685 lines)
- `application/controllers/RoiUnifiedCron_deleted.php`
- `application/controllers/RoiUnifiedCronV2_deleted.php`
- `application/controllers/cron/RoiMaturity_cron.php`
- `application/controllers/cron/BinaryMatchingCron.php`
- `application/controllers/cron/BinaryMatchingCron_Simple.php`
- `application/controllers/RoiMaturityCron.php` — plus its two routes

`Cron.php` is empty; delete it together with its three dead routes.

---

## 7. Cron Lab reachability vs. this schedule

Cron Lab (`admin/wallet/cron-lab`) is a developer testing surface, not the
schedule itself — it has "Run now" buttons for most, but not all, of §1:

- **Not in Cron Lab at all**: Deposit Credit (job 2), Chain Sync (job 3),
  ROI Monthly / ROI Maturity legs (jobs 5a/5b, intentionally — see §0),
  Bonus Wallet Reduction (job 10, intentionally — see §0). Trigger these
  from the crontab, their own dedicated page, or CLI/URL.
- **In Cron Lab but not on any automatic crontab line**: Member Bulk Upload
  BMAN delivery (job 9) is deliberately manual-only.
- **`case 'match':`** in `Cronlab::run()` (`Stakingmatching_model::run()`)
  has no corresponding card in the `jobs` array either — same shape as the
  bonus handler that was just removed. Left untouched since it wasn't part
  of this pass; worth a follow-up look if it's confirmed unused.
