# 2026-08-06 — Binary Matching Distribution: proposed model vs. live code

Reviews the worked example and rule set provided this session (10% of
`MIN(left, right)`, 80/20 split, ceiling → "Admin Wallet", per-node example
tree A..O) against the actual, currently-deployed engine:
`application/models/staking/Stakingmatching_model.php`, cross-checked with
[docs/17_BINARY_MATCHING_PAYOUT_CRON.md](17_BINARY_MATCHING_PAYOUT_CRON.md).

**Verdict: the final numbers in your worked example are correct — for one
specific scenario (a single cron pass, run once, after all 15 purchases have
already happened, with nothing paid out yet). The narrative around how those
numbers get produced is not.** Four claims don't match the live code. Details
below, then the same tree re-derived from the real mechanics to show exactly
where the two models agree and where they'd diverge on a second run.

---

## 1. What matches

| Claim | Verdict |
|---|---|
| Matching = 10% of `MIN(left volume, right volume)` | ✅ Matches `payMatching()`: `$match = min($left, $right)` |
| 10% splits into 80% Earning / 20% Staking | ✅ Matches — `staking_bonus_settings`: `matching_total_percent=10`, `matching_earning_percent=8`, `matching_staking_percent=2`. 8/10 = 80%, 2/10 = 20%: same math, just expressed against the reward instead of the matched volume directly. |
| Every qualifying ancestor gets evaluated independently in the same pass (not just the immediate parent) | ✅ Matches — `payMatching()` loops every row in `binary_carry` with both legs `> 0`; A, B, C, D, E, F, G in your tree are all paid in the same run, not staged across separate "Level 1 / Level 2 / Level 3" runs. |
| A ceiling cap exists per recipient | ✅ Matches, but see §2 for where the excess actually goes. |

## 2. What doesn't match

### 2.1 Volume source — not Lock Wallet, not Exchange wallet balance

Your narrative alternates between "checking the locked wallets" and
"minimum exchange wallet balance." Neither is the live source.

The real source is `binary_volume_ledger.bv = stake_amount` — written once,
permanently, the moment a stake is **purchased** — propagated up the tree by
`propagate()` into `binary_carry.{left,right}_carry`. It has nothing to do
with whether that stake is still locked, matured, or what any wallet's
current spendable balance is. A node's "5,000" in your diagram is best read
as "purchased a 5,000 BMAN package," not "currently has 5,000 locked."

```
Stakingmatching_model::_walkUp()
  binary_volume_ledger (bv = stake_amount, written at purchase)
    → binary_carry.left_carry / right_carry   (this is what payMatching() reads)
```

### 2.2 Ceiling excess → per-user escrow, not an Admin's wallet

> "If the member has reached the ceiling, the remaining amount goes to the
> Admin Wallet (user.role=admin)"

This is the biggest gap. The live code never credits any admin's personal
wallet. Excess goes to `Ceilingwallet_model::hold()` — a **system-only escrow
table** (`ceiling_wallet` / `ceiling_wallet_ledger`), keyed to the **same
user** who hit the ceiling, not to an admin. It sits there until an admin
manually calls `release()`, which by default credits it back to that same
user's Earning wallet. There's no automatic sweep to any "admin wallet," and
nothing in the schema associates held funds with `users.role`.

Worth noting separately: none of the 15 nodes in your worked example actually
hit a ceiling (no partial-pay rows appear in the Final Distribution Summary),
so the example doesn't exercise this rule either way — it's a stated
assumption, not something the numbers demonstrate.

### 2.3 Eligibility — carry on both legs, not literally "two children"

> "B: No two children. Not Eligible" / "C: No two children. Not Eligible"

Close, but the actual gate in `payMatching()` is:

```php
$rows = $this->db->where('left_carry >', 0)->where('right_carry >', 0)
                 ->get('binary_carry')->result_array();
...
$ceiling = $this->userCeiling($uid);
if ($ceiling <= 0) continue;   // must hold an OWN active stake
```

Two conditions: (a) `left_carry` **and** `right_carry` both above zero — that
usually correlates with "has purchasing activity on both sides," but it's
volume-based, not a literal child-count check, so a node could be "eligible"
with volume arriving from a grandchild on one side and no direct child there
at all. (b) the node must have an **own active stake** (`userCeiling() > 0`)
— someone who never bought a package themselves never gets paid, no matter
how much volume their downline generates. Your model doesn't mention this
second condition at all — worth confirming it's intentional to omit, since
it's a real, currently-enforced gate.

### 2.4 "Day 1 through Day 11, then Level 2" — not how runs work

The narrative frames this as sequential, staged days — Level 1 pays daily
for 10 days until a ceiling hits on day 11, *then* Level 2 begins. Live
behavior is different on two counts:

- **Cadence**: the cron runs every 5 minutes (`docs/17…md` §3), not once a
  day, and it isn't gated on "finishing" one level before moving to the next
  — every eligible ancestor across the whole tree is evaluated in the same
  pass, every run.
- **Carry is reducible, not a static balance to draw down daily.** Once
  `payMatching()` pays a match, it immediately subtracts that amount from
  **both** legs' `binary_carry`:

  ```php
  UPDATE binary_carry SET left_carry = left_carry - ?, right_carry = right_carry - ?
  ```

  There's no "give A 500/day for 10 days" — a single run pays whatever
  `MIN(left,right)` currently is, once, and that volume is gone from the
  carry afterward. The *same* reward repeating day after day only happens if
  **new purchases** keep replenishing both legs at a matching rate — it
  isn't a passive annuity off one static volume.

## 3. Your tree, re-derived from the real mechanics

Using your Level-3 tree (A/B/C/D/E/F/G branching to H..O, each value = the
package that node purchased), and running `propagate()` once for all 15
purchases before `payMatching()`'s first-ever pass:

| Node | left_carry | right_carry | MIN (matched) | Reward (10%) | Earning (8%) | Staking (2%) |
|---|---:|---:|---:|---:|---:|---:|
| A | 60,000 (B+D+E+H+I+J+K) | 295,000 (C+F+G+L+M+N+O) | 60,000 | 6,000 | 4,800 | 1,200 |
| B | 20,000 (D+H+I) | 35,000 (E+J+K) | 20,000 | 2,000 | 1,600 | 400 |
| C | 220,000 (F+L+M) | 65,000 (G+N+O) | 65,000 | 6,500 | 5,200 | 1,300 |
| D | 5,000 (H) | 5,000 (I) | 5,000 | 500 | 400 | 100 |
| E | 5,000 (J) | 20,000 (K) | 5,000 | 500 | 400 | 100 |
| F | 200,000 (L) | 10,000 (M) | 10,000 | 1,000 | 800 | 200 |
| G | 5,000 (N) | 50,000 (O) | 5,000 | 500 | 400 | 100 |
| H–O | 0 | 0 | 0 | 0 | 0 | 0 |

**These numbers are identical to your Final Distribution Summary.** That's
not a coincidence — a fresh `binary_carry` accumulated purely by addition
*is* mathematically the same as a subtree sum, as long as nothing has been
subtracted from it yet. Your worked example is implicitly the special case
where the cron runs for the very first time, after every purchase has
already landed, with zero prior payouts.

**Where it would diverge:** run `payMatching()` a second time with no new
purchases, and every one of these carries is now reduced by what was just
paid (A: 0 left / 235,000 right; B: 0/15,000; C: 155,000/0; D: 0/0; E: 0/15,000;
F: 190,000/0; G: 0/45,000). A "fresh resum of the whole subtree," which is
what your per-level narrative describes doing at each stage, would keep
reporting the *original* totals — 60,000/295,000 for A again — because it
never accounts for what's already been paid. The live carry would correctly
report 0 for A's next match (since one side is now fully drained), reflecting
that this volume has already been paid out once. This is the concrete
version of §2.4: matching income is a one-time draw against currently
available carry, not a recurring balance you can re-read the same way twice.

## 4. Corrected rule statement

```
Binary Matching Bonus = 10% of MIN(left_carry, right_carry)
  where left_carry / right_carry = binary_volume_ledger.bv (= stake_amount
  at purchase) propagated up the tree, REDUCED by every prior match paid
  (never by maturity, never by wallet spend)

Split: 8% of matched volume → Earning wallet
       2% of matched volume → Staking wallet

Eligible only if:
  - left_carry > 0 AND right_carry > 0, AND
  - the recipient holds an own active stake (group_ceiling > 0)

Ceiling: capped at SUM(group_ceiling) across the recipient's own active
  stakes, minus their lifetime paid matching income. Excess is proportionally
  short-paid and the difference is held in that SAME user's Ceiling Wallet
  (system escrow) — never sent to an admin's wallet, never auto-released.

Runs every 5 minutes via BinaryMatchingPayoutCron — not daily, not staged
  by tree level.
```

## 5. Not yet observed with real money

Per `docs/17_BINARY_MATCHING_PAYOUT_CRON.md`: no real binary match has ever
been paid on this system — `binary_volume_ledger` is empty for every real
user today. Everything above is verified by direct code reading against the
currently-deployed `Stakingmatching_model.php`, not by observing a live
payout.
