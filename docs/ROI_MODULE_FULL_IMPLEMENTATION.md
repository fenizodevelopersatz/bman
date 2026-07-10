# ROI Module - Full Implementation Plan

## Overview

Complete ROI Management System with three plan types: **Fixed**, **Regular**, and **Combo**.

---

## 📊 Plan Types

### 1. FIXED Plan
```
Single payment at maturity

Example:
  Principal:     100,000 BMAN
  Duration:      2 Years
  ROI Rate:      150%
  Total ROI:     150,000 BMAN
  Payment:       150,000 BMAN on maturity date
  
Database Records: 1 record
  Status Track: fixed_status (pending → completed)
  Payment Dates: fixed_maturity_date
```

### 2. REGULAR Plan
```
Three monthly payments (5th, 15th, 25th of each month)

Example:
  Principal:     100,000 BMAN
  Duration:      1 Year
  ROI Rate:      150%
  Total ROI:     150,000 BMAN
  Monthly:       50,000 BMAN × 3 = 150,000 BMAN
  
  Month 1:  5th → 50,000 BMAN
  Month 2:  15th → 50,000 BMAN
  Month 3:  25th → 50,000 BMAN
  
Database Records: 1 record + 3 payment entries
  Status Track: payment_day_5/15/25_status
  Payment Dates: payment_day_5/15/25_date
```

### 3. COMBO Plan
```
Blend: Monthly payments (Regular) + Maturity payment (Fixed)

Example:
  Principal:     100,000 BMAN
  Duration:      2 Years
  ROI Rate:      150%
  Total ROI:     150,000 BMAN
  
  Monthly Split: 50,000 BMAN (3 times)
  Maturity Split: 50,000 BMAN (at end)
  
  Payments:
    Month 1: Day 5  → 50,000 BMAN
    Month 2: Day 15 → 50,000 BMAN
    Month 3: Day 25 → 50,000 BMAN
    Year 2 (Maturity): 50,000 BMAN
  
Database Records: 1 record + 3 payment entries + 1 fixed maturity
  Status Track: payment_day_X_status + fixed_status
  Payment Dates: All tracked separately
```

---

## 🗄️ Database Schema

### roi_staking_management Table

**Core Fields:**
```
id                          PK
staking_swap_orders_id      FK → staking_swap_orders
user_id                     User reference
ref                         Unique reference (e.g., ROI-STK-001)

plan_type                   'fixed' | 'regular' | 'combo'
principal_amount            Original BMAN staked
roi_rate_percent            ROI % (e.g., 150)
total_roi_amount            Total ROI to distribute
duration_years              2, 3, 5 years
```

**Fixed Payment (Used for Fixed & Combo):**
```
fixed_payment_amount        ROI paid at maturity
fixed_maturity_date         When to pay
fixed_status                pending/processing/completed/failed
fixed_paid_date             When actually paid
fixed_tx_hash               Transaction hash
```

**Regular Payment (Used for Regular & Combo):**
```
regular_payment_amount      Amount per monthly payment
regular_payment_count       Usually 3
regular_payments_completed  How many done
```

**Payment Schedule (3 Payment Dates):**
```
payment_day_5_amount        Amount for 5th
payment_day_5_date          Next 5th date
payment_day_5_status        pending/processing/completed/failed
payment_day_5_tx_hash       Transaction

payment_day_15_amount       Amount for 15th
payment_day_15_date         Next 15th date
payment_day_15_status       pending/processing/completed/failed
payment_day_15_tx_hash      Transaction

payment_day_25_amount       Amount for 25th
payment_day_25_date         Next 25th date
payment_day_25_status       pending/processing/completed/failed
payment_day_25_tx_hash      Transaction
```

**Overall Status:**
```
overall_status              active/in_progress/completed/failed
total_paid_amount           Sum of all payments made
remaining_to_pay            Still pending
next_payment_date           Next scheduled payment

gas_fee_amount              Gas deducted per payment
gas_paid_by                 admin/user/platform
total_gas_paid              Cumulative gas fees
```

---

## 🔄 CRON Jobs Needed

### CRON 1: Monthly Distribution CRON
**Runs:** Hourly (checks if today is 5th, 15th, or 25th)
**File:** `/application/controllers/cron/RoiMonthlyDistribution_cron.php`

```php
// Pseudo-code
if (date('d') == '05' OR date('d') == '15' OR date('d') == '25') {
  // Find all roi_staking_management records needing payment
  $records = db.where('next_payment_date <= NOW()')
             .where('overall_status', 'active')
             .get();
  
  foreach ($record as $r) {
    if ($r.plan_type == 'regular' or 'combo') {
      // Determine which payment (5/15/25)
      if (date('d') == '05' AND $r.payment_day_5_status == 'pending') {
        creditToWallet(user_id, payment_day_5_amount);
        update payment_day_5_status = 'completed';
      }
      // Similar for 15th and 25th
    }
  }
}
```

**Tasks:**
1. Find records with payment due today
2. Process each payment:
   - Deduct gas fee
   - Credit to earning wallet
   - Record in onchain_transactions
   - Update payment status
3. Calculate next payment date
4. Update overall_status

---

### CRON 2: Maturity Distribution CRON
**Runs:** Daily (checks for maturity dates reached)
**File:** `/application/controllers/cron/RoiMaturityDistribution_cron.php`

```php
// Find all records with maturity reached
$records = db.where('fixed_maturity_date <= NOW()')
            .where('fixed_status', 'pending')
            .get();

foreach ($record as $r) {
  creditToWallet(user_id, fixed_payment_amount);
  update fixed_status = 'completed';
  update overall_status = 'completed';
}
```

**Tasks:**
1. Find records with maturity_date <= today
2. For FIXED & COMBO: Pay fixed_payment_amount
3. Update fixed_status
4. If COMBO and all regular payments done → overall_status = 'completed'
5. Record transaction

---

## 📱 Modal Display

### For FIXED Plan
```
Plan Type:          FIXED
Payment Schedule:   Single payment at maturity
Total ROI:          150,000 BMAN

Maturity Date:      Jul 9, 2027
ROI Amount:         150,000 BMAN
Status:             PENDING

Payment Status:
  └─ Maturity: PENDING (Jul 9, 2027)
```

### For REGULAR Plan
```
Plan Type:          REGULAR
Payment Schedule:   Monthly on 5th, 15th, 25th
Total ROI:          150,000 BMAN

Monthly Payment:    50,000 BMAN × 3

Payment Status:
  ├─ Day 5:  50,000 BMAN - PENDING (Next: Jul 5)
  ├─ Day 15: 50,000 BMAN - PENDING (Next: Jul 15)
  └─ Day 25: 50,000 BMAN - PENDING (Next: Jul 25)
  
Overall Progress:   0/3 completed
```

### For COMBO Plan
```
Plan Type:          COMBO (Fixed + Regular)
Payment Schedule:   Monthly + Maturity
Total ROI:          150,000 BMAN

Monthly (3×):       50,000 BMAN each
Maturity:           50,000 BMAN

Payment Status:
  ├─ Day 5:         50,000 BMAN - PENDING (Jul 5)
  ├─ Day 15:        50,000 BMAN - PENDING (Jul 15)
  ├─ Day 25:        50,000 BMAN - PENDING (Jul 25)
  └─ Maturity:      50,000 BMAN - PENDING (Jul 9, 2027)
  
Overall Progress:   0/4 completed
Paid So Far:        0 BMAN
Remaining:          150,000 BMAN
```

---

## 🛠️ Implementation Checklist

### Phase 1: Database ✅
- [x] Create roi_staking_management table
- [x] Add indices for performance
- [x] Add FK to staking_swap_orders
- [ ] Seed sample data for testing

### Phase 2: Controllers
- [ ] Create ROIStakingManagement model
- [ ] Update Lendingcontroller.php:
  - [ ] Add plan_type selection to purchase
  - [ ] Create initial roi_staking_management record
  - [ ] Calculate payments based on plan_type
- [ ] Update swap_order_details AJAX:
  - [ ] Return plan_type
  - [ ] Return payment schedule
  - [ ] Return status for each payment

### Phase 3: CRON Jobs
- [ ] Create RoiMonthlyDistribution_cron.php
  - [ ] Check for 5th, 15th, 25th
  - [ ] Process pending payments
  - [ ] Update statuses
  - [ ] Calculate next dates
- [ ] Create RoiMaturityDistribution_cron.php
  - [ ] Check for maturity dates
  - [ ] Process fixed payments
  - [ ] Update overall status
- [ ] Add both to Cron Lab

### Phase 4: Modal Display
- [ ] Update lending_managment.php
  - [ ] Show plan_type
  - [ ] Conditional display based on type
  - [ ] Show payment schedule for Regular/Combo
  - [ ] Show progress bar
  - [ ] Track individual payment statuses

### Phase 5: Testing
- [ ] Test FIXED plan (single maturity payment)
- [ ] Test REGULAR plan (3 monthly payments)
- [ ] Test COMBO plan (3 + 1 payment)
- [ ] Test CRON timing (5th, 15th, 25th)
- [ ] Test status tracking
- [ ] Test gas fee deduction
- [ ] Test wallet crediting

---

## 📈 Status Workflow

### FIXED Plan
```
Purchase
  ↓
pending → in_progress (maturity date reached)
  ↓
completed (payment made)
  OR
failed (error during payment)
```

### REGULAR Plan
```
Purchase
  ↓
active (waiting for first payment)
  ↓
in_progress (first payment processing)
  ↓
active (between payments)
  ↓
in_progress (second payment)
  ↓
active
  ↓
in_progress (third payment)
  ↓
completed (all 3 paid)
  OR
failed (error on any payment)
```

### COMBO Plan
```
Purchase
  ↓
active (waiting)
  ↓
in_progress (regular payment 1)
  ↓
active (between)
  ↓
in_progress (regular payment 2)
  ↓
active
  ↓
in_progress (regular payment 3)
  ↓
in_progress (maturity payment)
  ↓
completed (all 4 paid)
  OR
failed (error on any)
```

---

## 💰 Example: Combo Plan Full Timeline

```
Purchase Date:      Jul 1, 2024
Principal:          100,000 BMAN
Duration:           2 Years
ROI Rate:           150%
Plan Type:          COMBO
Total ROI:          150,000 BMAN

Distribution:
  Monthly:  50,000 × 3 = 150,000 BMAN → 50,000 BMAN actual
  Maturity: 50,000 BMAN

Timeline:
  Jul 5, 2024:   50,000 BMAN (Day 5)   → Earning Wallet
  Jul 15, 2024:  50,000 BMAN (Day 15)  → Earning Wallet
  Jul 25, 2024:  50,000 BMAN (Day 25)  → Earning Wallet
  Jul 1, 2026:   50,000 BMAN (Maturity)→ Earning Wallet (if COMBO, else complete)

Total Paid:     200,000 BMAN
Status Journey:
  Jul 1: active
  Jul 5: in_progress → active
  Jul 15: in_progress → active
  Jul 25: in_progress → active
  Jul 1, 2026: in_progress → completed
```

---

## 🎯 Status: READY FOR IMPLEMENTATION

Database: ✅ Created
Design: ✅ Complete
CRON Jobs: ⏳ Pending
Controllers: ⏳ Pending
Modal: ⏳ Pending
Testing: ⏳ Pending

Next Step: Implement controllers and CRON jobs
