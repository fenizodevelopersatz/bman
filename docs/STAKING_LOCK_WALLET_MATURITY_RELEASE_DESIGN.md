# Staking Lock Wallet → Maturity Release — Design Reference

**Status:** 📝 DESIGN / NOT YET IMPLEMENTED — captured for future reference, per user request (2026-08-05). Do not assume any of the "Intended behavior" section below is live; see "Current behavior" for what the code actually does today.

**Update 2026-08-05 (same day, later): this doc's original "Current behavior" section had a real error — "Nothing releases anything at maturity" was based on reading the wrong file (`RoiMaturityCron.php`, which turns out to be superseded/unscheduled). The live maturity cron is a different file, `RoiMaturityPayment_cron.php`, and it already returns principal today.** A user message the same day, with two worked scenarios, also confirmed the bonus-never-locks rule extends to Earning/Staking wallet credits too (binary matching bonus), not just the flat instant bonus. See "Correction — a third purchase path already does most of this" below before reading the rest of this doc; it changes the gap analysis materially.

## Why this doc exists

The user flagged (with a screenshot of `/user/stakings`) that the Exchange Wallet shows the full principal BMAN *immediately* after a staking purchase, while the Lock Wallet shows only the correct locked figure. Their stated correct model:

> When a user purchases a staking plan, the staked BMAN is transferred to the Lock Wallet and remains locked for the entire staking period. The principal is not available in the Exchange Wallet while the plan is active. Once the staking term reaches its maturity date, the locked principal is automatically released from the Lock Wallet and credited to the Exchange Wallet. The only exception is the Bonus Wallet — any staking bonus is distributed immediately after a successful staking purchase, credited directly to the Bonus Wallet.

## Intended behavior

```
Exchange Wallet (or USDT/other wallet, per the selected Coin Distribution Option)
       │
       │ Purchase Staking Plan
       ▼
Lock Wallet (Principal Locked)
       │
       │ Staking Period
       ▼
Maturity Date
       │
       ▼
Exchange Wallet (Principal Released)

Bonus (if applicable)
       │
       ▼
Bonus Wallet (Credited Immediately)
```

Reads as three rules:
1. **Principal BMAN → Lock Wallet, not Exchange/Earning/Staking, at purchase time.** Stays there for the whole term, regardless of which Coin Distribution Option (1–7) was chosen.
2. **Released principal → Exchange Wallet only, and only at maturity.** Not earning, not staking — Exchange specifically, per the user's diagram.
3. **Bonus BMAN → Bonus Wallet, immediately.** The only amount that credits at purchase time, not at maturity.

## Current behavior (as of this session, grounded in the actual code — not this doc's intended model)

This is a real behavior change from what's implemented and tested today, not a bug fix for a small oversight. Two purchase paths currently exist, and **both credit principal to Exchange/Earning/Staking immediately**, in proportions set by the chosen Coin Distribution Option:

- **Option 1 (new USDT → BMAN purchase)** — `StakingPurchasecron.php::_stepBman()` broadcasts principal + instant bonus as one on-chain transfer (merged into a single transfer as of 2026-08-05 — see that commit/session for details), then on confirmation calls `_walletShare($order, $wallet)` for `exchange`/`earning`/`staking`/`bonus` and credits each non-zero share via `_credit()` straight into `wallet_ledger`. For Option 1 specifically all of it goes to `exchange` (100%), so the *whole* principal appears in the Exchange Wallet the moment the transfer confirms — exactly what the screenshot shows.
- **Options 2-7 (re-staking from existing wallets)** — `Staking_model::restakeFromWallets()` *debits* the required share out of the user's existing Exchange/Earning/Staking/Bonus balances up front to fund the stake. It does not credit those wallets with new principal — the debit already removes the funding amount from wherever it came from, in exchange for creating the (nominally "locked") stake.
- **Lock Wallet today** (`Staking_model::lockWalletBalance()`) is a **read-only, always-computed** figure — `SUM(stake_amount) WHERE status IN ('active','processing') AND maturity_date > CURDATE()` over `user_stakes`. It was built this session specifically as an *informational* total, not a real wallet with its own ledger or balance. It already correctly reports "how much principal is currently locked" — nothing needs to change about *that* computation for the intended model above; the gap is entirely on the wallet-crediting side.
- **A third, live purchase path exists and was missed by this doc's first pass**: `Staking_model::purchaseStake()`, called from the routed, reachable `POST user/lending/purchase_stake` → `Lendingcontroller::purchase_stake()`. Unlike the two paths above, it already implements almost exactly the user's intended model: it credits the full principal into the **Staking** wallet type with an explicit per-row override — `credit($userId, 'staking', $bman, 'stake_purchase', ['maturity_date' => $maturity, 'is_matured' => 0])` — so `WalletMaturity_model` locks that specific credit until the *stake's own* maturity date, not a flat day-count. Its bonus credit passes `'skip_maturity' => true`, so the bonus is withdrawable immediately, no lock at all. **This is not currently what the live frontend calls for a new purchase** — the UI's Option 1 goes through `StakingPurchasecron.php` and Options 2-7 through `restakeFromWallets()` (see the "Simplify swap_purchase() back to Option-1-only" / "Update frontend: route options 2-7" work earlier this session) — so `purchaseStake()`'s correct behavior is effectively dormant from a real user's point of view, even though the route is live.

## Correction — a third purchase path already does most of this, and the maturity cron already runs (just at the wrong wallet, and only reliably for that dormant path)

**The original version of this doc said "nothing releases anything at maturity," based on reading `RoiMaturityCron.php`. That was the wrong file.** The cron actually wired into Cron Lab's "ROI Distribution (Monthly + Maturity)" button — the real, scheduled, live one — is `RoiDistribution_cron::run()` ([RoiDistribution_cron.php](application/controllers/RoiDistribution_cron.php)), which calls two legs in order: `roi-monthly-distribution-process`, then **`roi-maturity-payment-process` → `RoiMaturityPayment_cron::_mature()`**. That method, today, for every `roi_staking_management` record whose `fixed_maturity_date` has passed (and, for regular/combo, whose monthly schedule is fully paid):

1. Pays the fixed lump ROI on-chain, credits it to the **Exchange** wallet (`credit($uid, 'exchange', $fixedAmt, 'roi', ...)`) — fixed/combo only.
2. **Returns the principal** — `credit($uid, 'staking', $principalReturn, 'stake_maturity', ...)` — to the **Staking** wallet, not Exchange.

So principal release at maturity is **already live**, not a gap — but it credits the wrong wallet type per the user's own diagram (which shows principal landing in **Exchange** at maturity, not Staking), and it operates on `roi_staking_management` records, a table populated by `_generateRoiSchedule()` regardless of which of the three purchase paths created the stake — whether the *principal credit itself* was ever tagged with a stake-tied `maturity_date`/`is_matured=0` (so that it actually shows as "locked" in the interim, and so `WalletMaturity_model` has something correct to unlock) depends entirely on which purchase path ran, and only `purchaseStake()` does that tagging today. `StakingPurchasecron.php` and `restakeFromWallets()` both credit principal through the generic wallet-type default (Staking's default is 0 days = instantly matured) — so for those two paths, the principal was never actually "locked" in the ledger sense in the first place, and this cron's "return principal" credit would be adding a **second** principal amount on top of the (already unlocked, already spent) first one. **This needs to be traced precisely before touching either the cron or the purchase paths — do not assume today's maturity payout is idempotent against the two paths that don't tag maturity on the original credit.**

## Gap analysis — what would need to change

1. **Pick one purchase path to be authoritative, or make all three converge on the same crediting behavior.** Today `StakingPurchasecron.php` (Option 1), `restakeFromWallets()` (Options 2-7), and `purchaseStake()` (routed, but not called by the current frontend) each credit principal/bonus differently. The closest-to-intended one (`purchaseStake()`) isn't in the live purchase flow at all. This is a bigger decision than a bug fix — it's which of three live, working, differently-behaved code paths the platform actually wants, and probably means porting `purchaseStake()`'s maturity-tagging approach into `_stepBman()` and `restakeFromWallets()` rather than writing a fourth variant.
2. **Stop crediting Exchange/Earning/Staking untagged at purchase time.** Whichever path(s) remain live need to credit principal the way `purchaseStake()` already does: one credit into a wallet type, tagged with `'maturity_date' => $stakeMaturity, 'is_matured' => 0`, so `WalletMaturity_model` genuinely locks it until the stake's own term ends — not a flat wallet-level day count. Keep the bonus credit(s) — instant 25% *and* any distribution-option bonus share — tagged `'skip_maturity' => true`, matching what the user's message confirms should never lock. The same applies to binary matching bonus (`Stakingmatching_model::payMatching()`'s earning+staking credits) — the user's message extends "no lock" to those too, so those two `credit()` calls need `skip_maturity => true` added as well.
3. **Decide which wallet principal releases into.** `RoiMaturityPayment_cron.php` already credits matured principal back into the **Staking** wallet type (reusing the same wallet type it was locked in). The user's diagram wants it released into **Exchange** specifically. This is a one-line change to `_mature()`'s second `credit()` call (`'staking'` → `'exchange'`) *if* that's genuinely the intended final model — confirm before changing, since `RoiMaturityPayment_cron.php` is live and already running on real records today.
4. **Confirm idempotency across all three origins before changing anything.** Because `RoiMaturityPayment_cron.php` already runs live, any stake whose principal credit *wasn't* tagged with a maturity override (i.e., every stake created via the two paths the real UI actually uses today) will hit `_mature()`'s "return principal" step and receive a **second**, un-tracked principal credit on top of the first (already-unlocked-by-default) one, the moment its `roi_staking_management` record reaches `fixed_maturity_date`. This needs verifying against real data — how many existing stakes are already past maturity and may have already double-credited — before any schema/behavior change ships.
5. **The Lock Wallet's own SUM query stays correct as-is**, provided the maturity-tagging in (2) is in place — `lockWalletBalance()`'s `maturity_date > CURDATE()` filter already reflects a stake's un-lapsed term regardless of wallet-crediting mechanics.

## Open questions

**Resolved by the user's follow-up message (2026-08-05, same day, two worked scenarios):**
- ~~Does the Bonus-wallet share lock or credit immediately?~~ **Resolved: never locks, no exception-within-an-exception.** The message is explicit: "Only lock the principle bman only this is bonus this is no lock" — and extends the same "no lock" rule to **Earning Wallet and Staking Wallet credits from binary matching bonus**, not just the flat instant bonus. So `Stakingmatching_model::payMatching()`'s two `credit()` calls need `skip_maturity => true` added too, alongside every bonus credit site.
- ~~Do Options 2-7's earning/staking splits still mean anything for the principal?~~ **Leans resolved:** both worked scenarios show principal as a single locked lump released as a single amount at maturity ("Holding 5000 Bman on locked wallet" → one release), reinforcing that the per-option earning/staking percentages shouldn't apply to *principal* under the new model — only to whatever wallet the *bonus* share of a distribution option still routes to.

**Still open, and now bigger than originally scoped:**
- **Which of the three live purchase paths becomes authoritative** — port `purchaseStake()`'s maturity-tagging into `StakingPurchasecron.php`/`restakeFromWallets()`, retire the paths that don't match, or something else? See "Correction" section above.
- **Does principal release into Exchange (per the user's diagram) or Staking (what `RoiMaturityPayment_cron.php` already does live today)?** Needs an explicit decision before touching a cron that's already running against real records.
- **Are any stakes today already past maturity under paths that never tagged a maturity override on their principal credit?** If so, `RoiMaturityPayment_cron.php` may have already run its "return principal" step against them and created an untracked second credit — this needs a data check, not just a code fix, before anything changes.
- **What exactly should mark a stake as "released"?** A new column (e.g. `user_stakes.released_at`), or finally start using the existing-but-never-set `status = 'matured'` enum value (which would also require checking nothing else assumes `status` only ever holds `active`/`processing`/`withdrawn`/`cancelled`).
- **Does this apply retroactively** to `user_stakes` rows created before this change ships — if so, the release mechanism must not double-credit them at their maturity date.
- **Does the flat `wallet_maturity_settings` day-count (Earning 30d / Bonus 60d defaults) still apply to *non*-staking-purchase credits into those same wallet types** (e.g. a future referral bonus, an admin manual credit)? The user's rule reads as specific to staking-purchase-derived and matching-bonus-derived credits — removing the *default* maturity days for Bonus/Earning wallet types site-wide is a different, broader change than adding `skip_maturity` to the specific staking-related credit call sites, and only the latter is clearly asked for here.

## Separate, smaller item noted in the same message

**Special Offer tag visibility is currently unconditional, not admin-controlled.** The "★ SPECIAL" / "Special Offer" badge shown on staking packages, Recent Staking Activity rows, and the purchase details popup should be shown or hidden based on an admin setting, rather than always rendering whenever a package/stake's `is_special` flag is set. Needs: (a) a setting to control this (likely alongside the existing Staking Packages / Coin Distribution admin settings), (b) every current render site to check it — at minimum `_staking_packages.php`'s package cards, `Lendingcontroller::getRecentStakingActivityForView()`'s consumers in `lending_managment.php`, and `swap_order_details()`'s `is_special` field consumed by the transaction-steps popup. Not investigated further — noted here for when this doc's main topic is picked up, since it touches the same staking-purchase surface area.

## Files most likely involved when this is implemented

- `application/controllers/StakingPurchasecron.php` (`_stepBman()` — tag the principal credit with a stake-tied `maturity_date`/`is_matured=>0` instead of letting it fall through to the generic per-wallet default; add `skip_maturity` to the bonus credit if not already effectively immediate)
- `application/models/Staking_model.php` (`purchaseStake()` — the reference implementation to port from; `restakeFromWallets()` — needs the same tagging; `lockWalletBalance()` — no change anticipated)
- `application/models/staking/Stakingmatching_model.php` (`payMatching()` — add `skip_maturity => true` to both the earning and staking `credit()` calls, per the user's message extending "no lock" to matching bonus)
- `application/controllers/RoiMaturityPayment_cron.php` (`_mature()` — the *already-live* principal-return step; likely needs its target wallet type changed from `staking` to `exchange`, and needs auditing against stakes whose principal was never tagged with a maturity override in the first place)
- `application/controllers/RoiDistribution_cron.php` (orchestrator — confirm this stays the single entry point; no logic change anticipated)
- `application/models/WalletMaturity_model.php` (`resolve_credit_maturity()` — no change anticipated; already honors an explicit `maturity_date`/`skip_maturity` override exactly the way this design needs)
- Wherever the Special Offer badge renders: `application/views/user/wallet/_staking_packages.php`, `application/views/user/wallet/lending_managment.php`, `Lendingcontroller::swap_order_details()`/`getRecentStakingActivityForView()`
