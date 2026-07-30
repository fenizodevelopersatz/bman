# Cron Schedule — single source of truth

Every other cron doc in `docs/` predates this file and they contradict each other
(20+ files, conflicting cadences, several dead endpoints). **This file wins.**

Cadences below come from the controllers' own header comments, which are the
only cron documentation that has stayed in sync with the code.

---

## 1. Live crons

| # | Job | Route (HTTP) | Cadence | Sends on-chain? |
|---|-----|--------------|---------|-----------------|
| 1 | Staking Purchase (gas → USDT → BMAN → bonus) | `staking-purchase-cron` | every 1 min | **yes — Treasury** |
| 2 | Deposit credit (incoming USDT → USDT wallet) | `credit-deposits-cron` | every 1–3 min | no (read-only) |
| 3 | Chain sync (balances + pending-tx confirmations) | `chain-sync-cron` | every 1–5 min | no (read-only) |
| 4 | Binary Matching Payout (engine + on-chain drain) | `binary-matching-payout-cron` | every 5 min | **yes — Treasury** |
| 5 | Wallet Transfer Settlement | `wallet-transfer-settlement-cron` | every few min | **yes — Treasury** |
| 6 | Member Bulk Upload — opening BMAN | `member-bulk-bman-cron` | every few min | **yes — Treasury** |
| 7 | Rank Achievement (permanent ranks, §10) | `rank-achievement-cron` | hourly | no |
| 8 | ROI Distribution (monthly → maturity) | `roi-distribution-cron` | daily | no |
| 9 | Rank Power (60-day cycle, §11) | `rank-power-cron` | daily | no |
| 10 | Wallet Maturity Unlock (`is_matured` flip) | `wallet-maturity-cron` | daily | no |
| 11 | Bonus Wallet 60-day reduction | `bonus-reduction-cron` | daily | optional |

Jobs 8's two legs (`roi-monthly-distribution-process`,
`roi-maturity-payment-process`) also have their own routes. They exist for
targeted retry from ROI Distribution History — **do not schedule them
separately**, job 8 already runs both in the required order.

---

## 2. Crontab

Use the **HTTP form**. It resolves through `routes.php`, which carries the exact
class casing; the CLI form depends on CI3's `ucfirst()` file lookup matching the
on-disk filename, which several of these controllers do not satisfy on a
case-sensitive filesystem (`RoiDistribution_cron.php` vs `Roidistribution_cron`).

Replace `HOST` and `TOKEN` (`$config['cron_token']`).

```
# --- Treasury broadcasters: ONE lock, sequential, never overlapping ---------
# All four sign from the same Treasury address. See §4 — they must not run
# concurrently or they collide on the account nonce.
* * * * * flock -n /tmp/nexman-treasury.lock -c 'curl -s "HOST/staking-purchase-cron?token=TOKEN"; curl -s "HOST/binary-matching-payout-cron?token=TOKEN"; curl -s "HOST/wallet-transfer-settlement-cron?token=TOKEN"; curl -s "HOST/member-bulk-bman-cron?token=TOKEN"' >> cronlogs/treasury.log 2>&1

# --- Read-only chain pollers (safe to run alongside anything) --------------
*/2 * * * * curl -s "HOST/credit-deposits-cron?token=TOKEN" >> cronlogs/deposits.log 2>&1
*/3 * * * * curl -s "HOST/chain-sync-cron?token=TOKEN" >> cronlogs/chainsync.log 2>&1

# --- Hourly ----------------------------------------------------------------
0 * * * * curl -s "HOST/rank-achievement-cron?token=TOKEN" >> cronlogs/rank.log 2>&1

# --- Daily (order matters: credits first, maturity flip last) --------------
0 1 * * * curl -s "HOST/roi-distribution-cron?token=TOKEN"        >> cronlogs/roi.log 2>&1
0 2 * * * curl -s "HOST/rank-power-cron?token=TOKEN"              >> cronlogs/rankpower.log 2>&1
0 3 * * * curl -s "HOST/bonus-reduction-cron?token=TOKEN"         >> cronlogs/bonus.log 2>&1
0 4 * * * curl -s "HOST/wallet-maturity-cron?token=TOKEN"         >> cronlogs/walletmaturity.log 2>&1
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
                   │              (monthly→maturity)  Rank Power
                   ▼                    ▼              ▼
                   └────────► wallet_ledger credits ◄──┘
                                        │
                                        ▼
                              WalletMaturity_cron (is_matured flip)
                                        │
                                        ▼
                              withdrawal eligibility
```

Chainsynccron sits beside all of it, refreshing balances and advancing
confirmations for whatever is in flight.

Two ordering rules actually matter:

1. **Monthly ROI before Maturity ROI.** Maturity skips regular/combo records
   whose monthly schedule is unfinished. `roi-distribution-cron` already
   enforces this — that is its whole purpose.
2. **Rank Power rolls the cycle before it computes power.** Enforced inside
   `Rankcron_model::runPower()`. Rank Achievement is independent of it and
   never touches `user_ranks` from the power side.

Everything else is order-independent and idempotent.

---

## 4. The one hard constraint: Treasury nonce

`Web3bman::buildSignSend()` fetches the nonce fresh per send:

```php
$nonce = $this->rpc('eth_getTransactionCount', [$from, 'pending']);
```

There is no local nonce reservation and no lock. Four crons sign from the same
Treasury address (jobs 1, 4, 5, 6). If two broadcast in the same window they
both read the same pending nonce; the second transaction is then rejected or
silently replaces the first. The database records "sent, hash X" for a
transaction that never lands.

Their natural cadences collide on the minute — every-1-min against every-5-min
means a guaranteed overlap at :00, :05, :10 and so on.

The crontab above fixes this operationally with one `flock`. The durable fix is
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
