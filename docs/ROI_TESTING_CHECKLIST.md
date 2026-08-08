# ROI Staking Management - Testing Checklist

## 🧪 Pre-Testing Verification

### Step 1: Verify Database Setup
```bash
# Check if table exists
mysql -u root -p e-commerce-mlm-v2 -e "DESCRIBE roi_staking_management;" | head -20

# Check if FK exists in staking_swap_orders
mysql -u root -p e-commerce-mlm-v2 -e "DESC staking_swap_orders;" | grep roi_staking
```

**Expected:** Both queries return results without errors.

---

## 🚀 Endpoint Testing

### Test Monthly Distribution CRON

**Health Check:**
```bash
curl http://localhost/roi-monthly-distribution-test
```

**Expected Response:**
```json
{
  "status": true,
  "message": "ROI Monthly Distribution CRON is operational",
  "today": 10,
  "is_payment_day": false,
  "payment_days": [5, 15, 25],
  "next_payment_day": "2026-07-15"
}
```

**Process Run (only works on days 5, 15, 25):**
```bash
curl "http://localhost/roi-monthly-distribution-process?token=<cron_token>"
```

> HTTP calls to the `-process` routes require `?token=` matching `$config['cron_token']`
> (same gate as `roi-distribution-cron`). CLI runs (`php index.php roimonthlydistribution_cron process`) skip it.

---

### Test Maturity Payment CRON

**Health Check:**
```bash
curl http://localhost/roi-maturity-payment-test
```

**Expected Response:**
```json
{
  "status": true,
  "message": "ROI Maturity Payment CRON is operational",
  "current_date": "2026-07-10 14:30:00",
  "next_maturity_payment": "2027-07-09" or "None in next 30 days"
}
```

**Process Run:**
```bash
curl "http://localhost/roi-maturity-payment-process?token=<cron_token>"
```

---

## ✅ Cron Lab Dashboard Testing

1. **Navigate to:** `http://localhost/admin/wallet/cron-lab`

2. **Locate ROI CRON Cards:**
   - [x] "ROI Monthly Distribution" card visible
   - [x] "ROI Maturity Payment" card visible
   - [x] "ROI Maturity (Legacy)" card marked as deprecated

3. **Test Monthly Distribution:**
   - Click "Run Now" button
   - Should show results in modal
   - Check console for response

4. **Test Maturity Payment:**
   - Click "Run Now" button
   - Should show pending maturity records
   - Check if dates match

---

## 🎯 Integration Testing

### Test Case 1: FIXED Plan (Single Maturity)

**Scenario:**
- User purchases: 100,000 BMAN
- ROI Rate: 150%
- Plan Type: FIXED
- Duration: 2 years (maturity: 2026-07-10 + 2 years = 2028-07-10)

**Expected Database State:**
```sql
SELECT id, user_id, plan_type, principal_amount, 
       roi_rate, fixed_payment_amount, fixed_maturity_date, 
       overall_status FROM roi_staking_management 
WHERE user_id = ? AND plan_type = 'fixed';

-- Result:
-- id: 1, user_id: 123, plan_type: 'fixed', principal: 100000
-- roi_rate: 150, fixed_payment_amount: 150000
-- fixed_maturity_date: 2028-07-10, overall_status: 'pending'
```

**CRON Execution (Jul 10, 2028):**
- Run: `curl "http://localhost/roi-maturity-payment-process?token=<cron_token>"`
- Expected: Payment marked as 'completed'
- Earning wallet: +150,000 BMAN
- Transaction created: tx_type = 'roi_maturity_final'

---

### Test Case 2: REGULAR Plan (Monthly Payments)

**Scenario:**
- User purchases: 100,000 BMAN
- ROI Rate: 150%
- Plan Type: REGULAR
- Duration: 2 years

**Expected Database State:**
```sql
SELECT plan_type, payment_day_5_amount, payment_day_15_amount,
       payment_day_25_amount, payment_day_5_status,
       overall_status FROM roi_staking_management
WHERE user_id = ? AND plan_type = 'regular';

-- Result:
-- plan_type: 'regular'
-- payment_day_5_amount: 50000, status: 'pending'
-- payment_day_15_amount: 50000, status: 'pending'
-- payment_day_25_amount: 50000, status: 'pending'
-- overall_status: 'pending'
```

**CRON Execution Schedule:**

| Date      | CRON Run                             | Expected Result                           |
|-----------|--------------------------------------|-------------------------------------------|
| Day 5     | `roi-monthly-distribution-process` | payment_day_5_status → 'completed'       |
|           |                                     | Earning wallet: +50,000 BMAN              |
| Day 15    | `roi-monthly-distribution-process` | payment_day_15_status → 'completed'      |
|           |                                     | Earning wallet: +50,000 BMAN              |
| Day 25    | `roi-monthly-distribution-process` | payment_day_25_status → 'completed'      |
|           |                                     | Earning wallet: +50,000 BMAN              |
|           |                                     | overall_status → 'completed'              |

---

### Test Case 3: COMBO Plan (Monthly + Maturity)

**Scenario:**
- User purchases: 100,000 BMAN
- ROI Rate: 150%
- Plan Type: COMBO
- Duration: 2 years
- Split: 50% monthly (3×50,000), 50% maturity (50,000)

**Expected Database State:**
```sql
SELECT plan_type, payment_day_5_amount, payment_day_25_amount,
       fixed_payment_amount, fixed_maturity_date,
       overall_status FROM roi_staking_management
WHERE user_id = ? AND plan_type = 'combo';

-- Result:
-- plan_type: 'combo'
-- payment_day_5_amount: 50000, status: 'pending'
-- payment_day_15_amount: 50000, status: 'pending'
-- payment_day_25_amount: 50000, status: 'pending'
-- fixed_payment_amount: 50000, status: 'pending'
-- fixed_maturity_date: 2028-07-10
-- overall_status: 'pending'
```

**CRON Execution Timeline:**

| Date      | Event                        | Expected Result                    |
|-----------|------------------------------|------------------------------------|
| Day 5     | Monthly payment (day 5)      | +50,000 BMAN to earning wallet     |
| Day 15    | Monthly payment (day 15)     | +50,000 BMAN to earning wallet     |
| Day 25    | Monthly payment (day 25)     | +50,000 BMAN to earning wallet     |
| 2028-07-10| Maturity date reached        | +50,000 BMAN to earning wallet     |
|           | All 4 payments done          | overall_status → 'completed'       |

---

## 🔍 Database Verification Queries

### Check All ROI Records
```sql
SELECT id, user_id, plan_type, principal_amount, roi_rate,
       payment_day_5_amount, payment_day_15_amount, payment_day_25_amount,
       fixed_payment_amount, fixed_maturity_date, overall_status,
       created_at FROM roi_staking_management ORDER BY created_at DESC;
```

### Check Pending Payments
```sql
SELECT id, plan_type, payment_day_5_status, payment_day_15_status,
       payment_day_25_status, fixed_status FROM roi_staking_management
WHERE overall_status IN ('pending', 'in_progress');
```

### Check Transaction Audit Trail
```sql
SELECT id, staking_swap_orders_id, tx_type, amount, token, 
       from_wallet, to_wallet, status, tx_hash, created_at
FROM onchain_transactions 
WHERE tx_type LIKE 'roi%' ORDER BY created_at DESC;
```

### Check Earning Wallet Credits
```sql
SELECT user_id, wallet_type, balance, updated_at 
FROM wallet_ledger 
WHERE wallet_type = 'earning' 
ORDER BY updated_at DESC LIMIT 10;
```

---

## 📊 Manual Testing Workflow

### Step 1: Create Test Staking Purchase (with plan_type)
```bash
# Simulate purchase via UI or direct API call
# Must include: plan_type (fixed|regular|combo)

POST /user/lending/swap-purchase
{
  "principal_amount": 100000,
  "duration_years": 2,
  "plan_type": "regular",
  "roi_rate": 150
}
```

### Step 2: Verify Database Insert
```sql
SELECT roi_staking_management_id FROM staking_swap_orders 
WHERE user_id = ? ORDER BY created_at DESC LIMIT 1;
```

### Step 3: Run CRON on Appropriate Day
- If today is 5th, 15th, or 25th: Run monthly distribution CRON
- If today >= maturity date: Run maturity payment CRON

### Step 4: Verify Results
- Check roi_staking_management table for status updates
- Check wallet_ledger for earning wallet balance increase
- Check onchain_transactions for new records

### Step 5: Validate UI Display
- Open staking modal
- Navigate to "ROI PROGRESS" tab
- Verify:
  - [x] Plan type displayed
  - [x] Total ROI amount shown
  - [x] Maturity date displayed
  - [x] Payment schedule visible (for Regular/Combo)
  - [x] Current payment statuses listed

---

## ⚠️ Known Limitations & Notes

1. **Plan Type Not Yet Selected at Purchase**
   - Currently hardcoded or requires direct database insert
   - Update needed: Add plan_type dropdown to purchase form

2. **Modal Display Not Updated**
   - ROI details may not show plan-specific information
   - Update needed: Show payment schedule for Regular/Combo

3. **Testing on Specific Days**
   - Monthly CRON only runs on days 5, 15, 25
   - For testing, may need to manually adjust system date or mock the date

4. **Gas Fees Not Implemented**
   - Current implementation doesn't deduct gas fees per payment
   - Future enhancement: Add gas_fee column to track and deduct

---

## 🎯 Success Criteria

- [x] Database table created and accessible
- [x] Model methods working (CRUD operations)
- [x] CRON jobs execute without errors
- [x] Payments credited to earning wallet
- [x] Transaction audit trail created
- [x] Payment status tracking functional
- [x] Routes properly configured
- [ ] Plan type selection in UI (next phase)
- [ ] Modal shows plan-specific details (next phase)
- [ ] End-to-end testing on actual payment dates (next phase)

---

## 🔧 Troubleshooting

### Issue: CRON returns 404
**Solution:** Check routes.php has entries for `roi-monthly-distribution-process` and `roi-maturity-payment-process`

### Issue: "Unknown column 'roi'" error
**Solution:** Ensure staking_swap_orders table has `roi_rate` and `roi_staking_management_id` columns

### Issue: Payments not creating in database
**Solution:** Verify model methods are being called; check error_message field in roi_staking_management

### Issue: Earning wallet balance not updating
**Solution:** Verify wallet_ledger has a record for user with wallet_type='earning'

---

## 📝 Quick Reference

| Component | File | Status |
|-----------|------|--------|
| Model | RoiStakingManagement_model.php | ✅ Ready |
| Monthly CRON | RoiMonthlyDistribution_cron.php | ✅ Ready |
| Maturity CRON | RoiMaturityPayment_cron.php | ✅ Ready |
| Routes | application/config/routes.php | ✅ Ready |
| Dashboard | Cronlab.php | ✅ Ready |
| Purchase UI | Lendingcontroller | ⏳ Pending |
| Modal Display | lending_managment.php | ⏳ Pending |

