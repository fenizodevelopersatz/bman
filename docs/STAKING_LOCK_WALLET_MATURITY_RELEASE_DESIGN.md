# Staking Lock Wallet → Maturity Release — Design Reference

**Status:** 📝 DESIGN / NOT YET IMPLEMENTED — captured for future reference, per user request (2026-08-05). Do not assume any of the "Intended behavior" section below is live; see "Current behavior" for what the code actually does today.

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
- **Nothing releases anything at maturity.** Confirmed by reading `RoiMaturityCron.php` (processes ROI *payouts* at maturity, not principal) and by this session's earlier Lock Wallet research: no cron, model, or trigger anywhere sets `user_stakes.status` away from `active`/`processing`, or moves BMAN out of "locked" into any wallet. The Lock Wallet's own `maturity_date > CURDATE()` filter is the only thing that currently reacts to maturity, and it only removes a stake from a *sum* — it doesn't move any real balance.

## Gap analysis — what would need to change

1. **Stop crediting Exchange/Earning/Staking at purchase time for Option 1.** `StakingPurchasecron.php::_stepBman()`'s onConfirmed callback would need to stop calling `_credit()` for `exchange`/`earning`/`staking` (keep the `bonus` credit — that part already matches the intended model). The principal simply becomes "locked" by virtue of the `user_stakes` row existing with `maturity_date` in the future — exactly what `lockWalletBalance()` already sums.
2. **Options 2-7 probably need no change to the debit side** — funding a stake by debiting existing wallets already removes that BMAN from general availability. What's missing for *this* path is only the maturity-release step (below), same as Option 1.
3. **A new maturity-release mechanism** (cron or triggered check) that finds `user_stakes` rows crossing from "locked" (`status IN ('active','processing') AND maturity_date > CURDATE()`) to matured (`maturity_date <= CURDATE()`), and for each one not yet released, credits the Exchange Wallet with `stake_amount` via `Walletledger_model::credit()` (reference_type something like `stake_maturity_release`, tx_hash not applicable — this is a pure internal ledger movement, no on-chain leg). Needs its own idempotency guard (e.g. a `released_at` timestamp or boolean column on `user_stakes`, since nothing there today distinguishes "matured and released" from "matured and still counted as locked" — recall `status` never actually flips to `'matured'` anywhere in the live code).
4. **The Lock Wallet's own SUM query stays correct as-is** once (3) exists and reliably flips whatever "released" marker gets added — a stake that's been released should stop being summed, which the existing `maturity_date > CURDATE()` filter already achieves on its own (no code change needed there specifically), *provided* the release mechanism runs at/after the same maturity boundary the Lock Wallet already uses.

## Open questions to resolve before implementing (not answered by the user's message)

- **Do Options 2-7's `earning_percentage`/`staking_percentage` splits still mean anything for the principal under this model**, or does *all* principal release to Exchange only, regardless of which option originally funded/would-have-distributed it? The user's diagram shows a single "Exchange Wallet (Principal Released)" box with no earning/staking branch, which reads as "everything releases to Exchange" — but that would make the Earning/Staking percentages in Options 3/4/6/7 meaningful only for the *bonus* share (if any), never the principal. Worth confirming explicitly, since it's a bigger behavior change than it first looks — it would mean Options 2-7 stop being "split my principal across these wallets" and become "fund my stake from these wallets, but always get the principal back into Exchange only, later."
- **Does the Bonus-wallet SHARE of the principal (Options 2/3/7's `bonus_percentage`, distinct from the flat 25% instant bonus)** still credit immediately like the instant bonus does, or does it also lock until maturity like the rest of the principal? The user's rule ("The only exception is the Bonus Wallet... any staking bonus") is ambiguous between "the flat 25% instant bonus only" and "anything that would end up in the Bonus wallet, including a distribution-option's principal share."
- **What exactly should mark a stake as "released"?** A new column (e.g. `user_stakes.released_at`), or finally start using the existing-but-never-set `status = 'matured'` enum value (which would also require checking nothing else assumes `status` only ever holds `active`/`processing`/`withdrawn`/`cancelled`).
- **Does this apply retroactively** to `user_stakes` rows created before this change ships (their principal was already credited to Exchange/Earning/Staking at purchase time under the old model) — if so, the release mechanism must not double-credit them at their maturity date.

## Separate, smaller item noted in the same message

**Special Offer tag visibility is currently unconditional, not admin-controlled.** The "★ SPECIAL" / "Special Offer" badge shown on staking packages, Recent Staking Activity rows, and the purchase details popup should be shown or hidden based on an admin setting, rather than always rendering whenever a package/stake's `is_special` flag is set. Needs: (a) a setting to control this (likely alongside the existing Staking Packages / Coin Distribution admin settings), (b) every current render site to check it — at minimum `_staking_packages.php`'s package cards, `Lendingcontroller::getRecentStakingActivityForView()`'s consumers in `lending_managment.php`, and `swap_order_details()`'s `is_special` field consumed by the transaction-steps popup. Not investigated further — noted here for when this doc's main topic is picked up, since it touches the same staking-purchase surface area.

## Files most likely involved when this is implemented

- `application/controllers/StakingPurchasecron.php` (`_stepBman()` — stop crediting exchange/earning/staking on confirm)
- `application/models/Staking_model.php` (`lockWalletBalance()`, `restakeFromWallets()`, possibly a new `releaseMaturedStakes()`)
- A new or existing cron controller for the maturity-release sweep (natural neighbor: `RoiMaturityCron.php`, though principal release and ROI payout are different concerns and probably shouldn't be merged into one method)
- `application/models/Walletledger_model.php` (reused as-is for the release credit — no changes anticipated)
- Wherever the Special Offer badge renders: `application/views/user/wallet/_staking_packages.php`, `application/views/user/wallet/lending_managment.php`, `Lendingcontroller::swap_order_details()`/`getRecentStakingActivityForView()`
