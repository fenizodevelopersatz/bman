# Centralized Gas Fee Manager — Design Reference (Platform-Wide)

**Status:** 📝 DESIGN / NOT YET IMPLEMENTED — captured for future reference per user request (2026-08-05). This doc's job is to reconcile the user's full specification against what already exists in the codebase (built earlier the same day — see "What already exists" below) so a future implementation pass builds on top of it instead of creating a second, colliding system.

## The ask, as specified

One central function every transaction type calls instead of calculating gas inline — `GasFeeService::process()`, e.g.:

```
processGas(transaction_type, network, token, amount, user_id, order_id)
```

Backed by exactly one settings table and one audit table, covering every financial operation platform-wide — not just staking purchases:

**`gas_fee_settings`** (policy, one row per transaction_type/network/token): `id, transaction_type, network, token, gas_limit, gas_multiplier, priority, minimum_fee, maximum_fee, payer, enabled, updated_at`

**`gas_fee_history`** (audit, one row per real gas payment): `id, transaction_id, order_id, user_id, transaction_type, network, gas_used, gas_price, gas_fee, payer, status, tx_hash, created_at`

**Transaction types to support:** `swap_purchase, restake_purchase, withdraw, deposit, roi_monthly, roi_maturity, bonus, exchange_credit, earning_credit, staking_credit, bonus_wallet_credit, wallet_transfer, admin_send, treasury_send, refund, airdrop, rank_reward, binary_matching`

**Internal (no gas, DB-only) vs on-chain (needs gas):**
- Internal: wallet transfer, exchange→earning, earning→staking, bonus→exchange, admin adjustment, ledger update, lock wallet update.
- On-chain: USDT deposit, BNB gas payment, BMAN transfer, withdraw, treasury send, ROI payout, bonus payout, staking purchase, maturity payout.

**Gas responsibility (payer):**

| Transaction | Payer |
|---|---|
| User staking purchase | User |
| Restake | No gas (internal) |
| Wallet transfer | No gas |
| Withdraw | User |
| ROI monthly | Treasury |
| ROI maturity | Treasury |
| Bonus | Treasury |
| Rank reward | Treasury |
| Binary reward | Treasury |
| Treasury send | Treasury |

**Decision flow:** transaction type → is it on-chain? → (no → skip gas, done) / (yes → load gas setting → estimate gas → enough BNB? → (no → pending/retry later) / (yes → broadcast → confirmed → update DB → done))

**Design rules (verbatim intent):** never hardcode gas values; never duplicate gas calculation; one service manages all gas fees; one configuration table controls all transaction types; internal wallet operations never consume gas; every on-chain transaction must have a gas history record; all blockchain functions must call the centralized Gas Fee Manager before broadcasting.

## What already exists (built earlier this same session, 2026-08-05 — read this before implementing anything above)

A gas-fee-tracking system already ships and is live-verified working, but it is **narrower in scope and different in schema** from what's specified above:

- **`gas_fee_settings`** (already exists, different columns): `id, tx_type, gas_limit, gas_price_gwei, buffer_multiplier, is_active, updated_by, created_at, updated_at`. Only two seeded rows today — `tx_type='gas_funding'` (native BNB sends) and `tx_type='token_transfer'` (BEP-20 sends). No `network`, `token`, `priority`, `minimum_fee`, `maximum_fee`, or `payer` columns yet.
- **`gas_fee_ledger`** (already exists, different name and columns from the spec's `gas_fee_history`): `id, tx_type, reference_type, reference_id, user_id, from_address, to_address, tx_hash, onchain_transaction_id, status, gas_limit_used, gas_price_wei, gas_used, native_fee_total, created_at, confirmed_at`. Live-verified: real rows exist with real backfilled gas data as of today.
- **`GasFeeSettings_model::resolve($txType)`** (`application/models/GasFeeSettings_model.php`) — the policy reader. Falls back to `token_settings.gas_limit`/`gas_price` if no active row matches, so it never returns a bare hardcoded literal.
- **`GasFeeLedger_model`** (`application/models/GasFeeLedger_model.php`) — `recordBroadcast()` (pending row at broadcast time), `linkOnchainTx()`, `recordConfirmed()` (backfilled with real numbers once `Chainsync_model::verifyTx()` observes the mined receipt), `totalNativeSpent()`.
- **Wired into exactly one place:** `application/controllers/StakingPurchasecron.php` — its `_gasNeededBnb()`, `_broadcast()`, and `_recordOnchain()` all call through these two models. This covers the staking-purchase cron's on-chain legs only (gas-funding + the merged principal/bonus BEP-20 transfer — see the separate "merged bonus/bman" design note from earlier today). **Nothing else** — withdraw, ROI, treasury send, bonus, rank reward, and binary matching all still calculate/broadcast gas independently today, exactly the duplication this new spec is asking to eliminate.
- **Admin UI already live:** `/admin/finance/gas-fee-settings` (edit policies, audited via the shared `admin_settings_audit` table) and `/admin/finance/gas-fee-transactions` (real transaction list, now showing live data — the `chain-sync-cron` backfill mechanism this depends on was fixed and verified working today, see the `wallet_sync_cursor` migration).
- **A related, currently-open gap:** `Swapengine_model::deliverBman()` and `Walletreset::mark_completed()` are a separate, legacy delivery path that computes its own gas need independently (`Swapengine_model::_ensureGas()`, reading `token_settings` directly, bypassing `GasFeeSettings_model` entirely) — flagged as a background task, **currently being worked on in a separate session** as of this writing. Any Gas Fee Manager implementation should coordinate with (not duplicate) that effort, since both touch treasury-signed sends.

## Reconciliation — do not create a second, colliding system

Implementing the spec's tables literally, by those exact names, would collide with what's already live (`gas_fee_settings` already exists with different columns; a same-named-but-differently-shaped table is not possible, and `gas_fee_history` alongside the existing `gas_fee_ledger` would just be two audit tables answering the same question). Recommended path when this is picked up:

1. **Extend, don't duplicate.** Add the missing columns to the *existing* `gas_fee_settings` (`network`, `token`, `priority`, `minimum_fee`, `maximum_fee`, `payer`) and *existing* `gas_fee_ledger` (`transaction_id`/`order_id` if not already reachable via `reference_id`, `payer`) rather than creating `gas_fee_history` as a second table.
2. **Build `GasFeeService::process()` as a new, thin orchestration class** that wraps the *existing* `GasFeeSettings_model`/`GasFeeLedger_model` (extended per #1) rather than reimplementing their logic — giving every caller the single entry point the spec asks for, backed by infrastructure that's already tested and live.
3. **Migrate `StakingPurchasecron.php` to call the new service** instead of `GasFeeSettings_model`/`GasFeeLedger_model` directly, so there is exactly one call path once this is done (matches "never duplicate gas calculation").
4. **Extend one transaction type at a time** to the remaining flows (withdraw → ROI monthly/maturity → treasury send → bonus → rank reward → binary matching), each following the same on-chain/internal classification and payer table above. Each of these is its own real, separate change to a live financial cron — treat each as its own careful pass (research the current flow, plan, implement, test with real data, redeploy), not one giant rewrite.
5. **Reconcile with the in-progress `deliverBman()`/`mark_completed()` work first** (or in lockstep) — implementing the centralized service while that separate effort is independently changing treasury-send gas handling risks the two conflicting.

## Open questions (not resolved by the spec as given)

- **`minimum_fee`/`maximum_fee`** — are these a hard floor/ceiling that *adjusts* the amount actually sent (e.g., clamp the estimated fee), or a sanity-check/alert threshold that flags an anomaly without changing behavior? The existing `buffer_multiplier` concept (multiply the raw estimate) is a different mechanism from a min/max clamp — worth confirming which is intended, or whether both should coexist.
- **`priority`** — is this "slow/standard/fast" gas-price tiers (matching the live gas ticker already on `/admin/finance/gas-fee-transactions`, which shows all three), or a queue-priority for broadcast ordering? Both are plausible readings of one word.
- **Per-`network`/`token` policy rows** — today's two seeded rows (`gas_funding`, `token_transfer`) implicitly assume one network (BSC mainnet) and one token (BMAN/BNB). Does this need to support multiple networks/tokens concurrently now, or is the column forward-looking for a future multi-chain state?
- **`gas_fee_history.payer`** — for `wallet_transfer`/`internal_transfer` (marked "no gas" in the spec's own table), should a history row be written at all (with `gas_fee=0`), or skipped entirely since "every on-chain transaction must have a gas history record" implies internal ones are exempt? Reads as the latter, worth confirming since it affects whether reports need to filter internal rows out or whether they simply never appear.

## Related screenshot items (same session, not part of this doc's main topic)

Two smaller items flagged on `/user/stakings` in the same message as this spec — both already captured in `docs/STAKING_LOCK_WALLET_MATURITY_RELEASE_DESIGN.md`, not duplicated here:
- Exchange Wallet showing principal before maturity (the design doc's whole subject).
- Special Offer badge missing from the Recent Staking Activity "Type" column (noted there as a separate, smaller item).

Both remain unimplemented pending a decision on the open questions in that doc.
