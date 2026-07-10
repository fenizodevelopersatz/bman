# ROI Maturity Wallet Distribution Configuration

## Current Configuration ⚙️

**Status: ROI is credited to EARNING wallet only**

```
Maturity ROI → EARNING WALLET (100%)
```

Example:
```
Staking: 100 BMAN @ 150% ROI
Maturity ROI Amount: 150 BMAN
Destination: EARNING WALLET
```

---

## Available Wallet Types

| Wallet Type | Purpose | Current Use |
|-------------|---------|-------------|
| **exchange** | Trading/Selling | BMAN distribution |
| **earning** | Income/ROI | ROI maturity (current) |
| **staking** | Locked Stakes | Re-staking |
| **bonus** | Promotional | Bonus allocations |

---

## Current Purchase Distribution (coin_distribution_option 1-7)

When you stake, BMAN is distributed across wallets based on your chosen option:

### Option 1: Exchange Only
```
Exchange: 100% | Earning: 0% | Staking: 0% | Bonus: 0%
```

### Option 2: Exchange + Bonus
```
Exchange: 90% | Earning: 0% | Staking: 0% | Bonus: 10%
```

### Option 3: Exchange + Earning + Bonus
```
Exchange: 80% | Earning: 10% | Staking: 0% | Bonus: 10%
```

### Option 4: Exchange + Earning + Staking
```
Exchange: 80% | Earning: 10% | Staking: 10% | Bonus: 0%
```

### Option 5: Exchange + Earning
```
Exchange: 90% | Earning: 10% | Staking: 0% | Bonus: 0%
```

### Option 6: Exchange + Staking
```
Exchange: 90% | Earning: 0% | Staking: 10% | Bonus: 0%
```

### Option 7: All Wallets
```
Exchange: 70% | Earning: 10% | Staking: 10% | Bonus: 10%
```

---

## ROI Maturity Distribution Options

### Option A: Current (Earning Only) ✓
```
Maturity ROI → EARNING WALLET (100%)

Pros:
  ✓ Simple & transparent
  ✓ Easy to understand
  ✓ No complexity
  ✓ All ROI in one place

Cons:
  ✗ No flexibility
  ✗ Doesn't respect purchase distribution option
```

### Option B: Use coin_distribution_option (Like Purchases)
```
Use SAME distribution as initial BMAN purchase

Example:
  User chose Option 7 (All wallets) at purchase
  Initial BMAN: 100 BMAN distributed as 70|10|10|10
  Maturity ROI: 150 BMAN distributed as 70|10|10|10
  
  Earning wallet receives: 10 BMAN ROI (not 150)
  Exchange wallet receives: 105 BMAN ROI
  Staking wallet receives: 15 BMAN ROI
  Bonus wallet receives: 15 BMAN ROI

Pros:
  ✓ Consistent with initial distribution
  ✓ User's choice respected for both
  ✓ Balanced across wallets

Cons:
  ✗ More complexity
  ✗ ROI split across 4 wallets
  ✗ Harder to track
```

### Option C: Configurable Default (New Setting)
```
Admin sets a default wallet for all ROI maturity distributions

Example:
  Admin chooses: EARNING for all users
  OR: Exchange for re-investment strategy
  OR: Specific option per user tier

Pros:
  ✓ Flexible per business model
  ✓ Simple for users
  ✓ Admin has control

Cons:
  ✗ Requires additional configuration
  ✗ Not per-user customizable
```

---

## Recommendation

### For Most Users: Option A (Current - Earning Only)
```
Why:
  • ROI is income/earnings → Earning wallet is perfect fit
  • Clear & transparent for users
  • Easy to track and audit
  • Matches the wallet's purpose
  
Decision: KEEP THIS
```

---

## Database Fields

```sql
-- staking_swap_orders table
coin_distribution_option INT(11)  -- How initial BMAN was distributed
maturity_roi_amount DECIMAL      -- Total ROI at maturity
roi_return_status VARCHAR         -- pending/in_progress/completed

-- wallet_ledger table
wallet_type ENUM('exchange', 'earning', 'staking', 'bonus')
balance DECIMAL                   -- Current balance
updated_at TIMESTAMP
```

---

## Current Workflow

```
1. User stakes 100 BMAN
   coin_distribution_option = 7 (All wallets)
   Initial BMAN distributed per option 7

2. ROI calculated hourly
   Deposited to earning wallet

3. At Maturity (1 year)
   maturity_roi_amount = 150 BMAN
   Credited to EARNING WALLET (100%)
   
4. Status updates
   roi_return_status = 'completed'
   Recorded in onchain_transactions
```

---

## Code Location

**Current Implementation:**
```
File: application/controllers/cron/RoiMaturity_cron.php
Method: releaseROIToWallet()

Where to change:
  Line 273: ->where('wallet_type', 'earning')
```

---

## Question for You

Which approach do you prefer?

**A) EARNING WALLET ONLY (Recommended - Current)**
  - All ROI maturity → Earning wallet
  - Simple, transparent, matches wallet purpose
  - ✓ Implemented now

**B) RESPECT coin_distribution_option**
  - ROI distributed same as initial BMAN
  - Consistent approach
  - More complex but flexible

**C) ADMIN CONFIGURABLE DEFAULT**
  - Admin sets wallet for all ROI maturity
  - Flexible business model
  - Requires new config setting

---

## Implementation Status

✓ Option A: COMPLETE & DEPLOYED
  - ROI credited to earning wallet
  - Working in production
  - Simple & transparent

For Options B or C: Let me know and I'll implement!
