# Phase 2: ROI Plan Type Selection & Controller Integration

## ✅ COMPLETE

All Phase 2 work has been completed and integrated.

---

## 1️⃣ Frontend: Staking Modal Update

**File:** `application/views/user/wallet/_staking_packages.php`

### What Was Added:

**New Step in Modal Flow:**
```
Step 1: Package Selection
Step 2: ✅ ROI PLAN TYPE (NEW)
Step 3: Staking Plan Selection (Fixed/Regular/Variable)
Step 4: Distribution Option
Step 5: Preview
Step 6: Confirm
```

### ROI Plan Type Selection:
```html
<div class="stkm-pane" data-step="2">
  <label>ROI Plan Type</label>
  <div class="stkm-seg" id="stkm-roi-plans"></div>
  <div id="stkm-roi-plan-desc">Description of selected plan</div>
</div>
```

### Plan Types Available:
1. **Fixed Plan** - ROI accrues as one total and is credited at maturity
2. **Regular Plan** - ROI credited monthly on days 5, 15, 25
3. **Combo Plan** - Blend of monthly + maturity payments

### JavaScript Changes:

**New ROI Plans Data:**
```javascript
const ROI_PLANS = [
  {code: 'fixed', name: 'Fixed Plan', desc: '...'},
  {code: 'regular', name: 'Regular Plan', desc: '...'},
  {code: 'combo', name: 'Combo Plan', desc: '...'}
];
```

**New Functions:**
```javascript
✅ stkPickROIPlan(code)     - Handle ROI plan selection
✅ renderStep(step)         - Updated for 6-step flow
✅ stkOpen()                - Render ROI plan buttons
```

**Form Submission Update:**
```javascript
// Now includes plan_type
fd.append('plan_type', cur.roi_plan);  // 'fixed' | 'regular' | 'combo'
```

**Summary Display:**
```javascript
// Added to final summary (step 6)
$('stkm-sum-roi-plan').textContent = roiPlanName;
```

---

## 2️⃣ Backend: Lendingcontroller Update

**File:** `application/controllers/user/usersettings/Lendingcontroller.php`

### swap_purchase() Method Changes:

**Step 1: Capture plan_type from POST**
```php
$planType = (string)($this->input->post('plan_type') ?? 'fixed');
// Values: 'fixed' | 'regular' | 'combo'
```

**Step 2: Validate plan_type**
```php
if (!in_array($planType, ['fixed', 'regular', 'combo'])) {
    echo json_encode(['status'=>false,'message'=>'Invalid ROI plan type']);
    return;
}
```

**Step 3: Create ROI Staking Management Record**
```php
$this->load->model('RoiStakingManagement_model', 'roi_mgmt');
$roiRecordId = $this->roi_mgmt->createROIRecord(
    $res['id'],                    // staking order ID
    $userId,                        // user ID
    'ORDER-' . $res['id'],         // reference
    $planType,                      // ✅ plan type
    [
        'principal_amount' => $res['bman_amount'],
        'roi_rate_percent' => $roiRate,
        'duration_years' => $durationYears,
        'maturity_date' => $maturityDate,
    ]
);
```

**Step 4: Link to Staking Order**
```php
if ($roiRecordId) {
    $this->db->where('id', $res['id'])
             ->update('staking_swap_orders', [
                 'roi_staking_management_id' => $roiRecordId
             ]);
}
```

**Step 5: Updated Response**
```php
// Response includes plan_type
[
    'status' => true,
    'message' => '...ROI Plan: Fixed...', // Includes plan label
    'data' => [
        'plan_type' => $planType,  // ✅ Plan type in response
        // ... other fields
    ]
]
```

### swap_order_details() Method:

**Added plan_type to response:**
```php
'plan_type' => $o['plan_type'] ?? 'fixed',
```

So when modal loads order details, it includes the ROI plan type.

---

## 3️⃣ Model: ROI Staking Management Update

**File:** `application/models/RoiStakingManagement_model.php`

### createROIRecord() Return Value:

**Before:**
```php
return $this->db->insert($this->table, $recordData);  // Returns true/false
```

**After:**
```php
if ($this->db->insert($this->table, $recordData)) {
    return $this->db->insert_id();  // Returns the inserted ID
}
return false;
```

Now controller can link the ROI record ID to the staking order.

---

## 📊 Database Structure

### staking_swap_orders (Updated)
```sql
ALTER TABLE staking_swap_orders
ADD COLUMN plan_type ENUM('fixed', 'regular', 'combo') DEFAULT 'fixed' AFTER plan_code,
ADD COLUMN roi_staking_management_id BIGINT UNSIGNED AFTER roi_staking_management_id;
```

Fields now set at purchase:
- `plan_type` - The ROI plan chosen
- `roi_staking_management_id` - FK to roi_staking_management record

### roi_staking_management (Existing)
```sql
Table already stores all plan-specific data:
- plan_type: enum('fixed', 'regular', 'combo')
- fixed_payment_amount: ROI for fixed plan
- payment_day_5/15/25_amount: ROI for monthly payments
- fixed_maturity_date: When ROI is paid
- Overall status tracking
```

---

## 🔄 Workflow: Purchase with Plan Type

### User Flow:

```
1. Click "Buy BMAN" button
   ↓
2. SELECT PACKAGE (Step 1)
   - Choose 100,000 BMAN package
   ↓
3. ✅ SELECT ROI PLAN TYPE (Step 2) — NEW
   - Choose "Fixed" | "Regular" | "Combo"
   - Shows plan description
   ↓
4. SELECT STAKING PLAN (Step 3)
   - Choose "Fixed/Regular/Variable"
   - Choose "2/3/5 Years"
   ↓
5. SELECT DISTRIBUTION (Step 4)
   - Choose wallet allocation (1-7)
   ↓
6. PREVIEW (Step 5)
   - Shows ROI calculation
   - Shows payment schedule
   ↓
7. CONFIRM (Step 6)
   - Submits with plan_type
```

### Backend Flow:

```
POST /user/lending/swap_purchase
{
  package_id: 1,
  plan_code: 'fixed',
  duration_years: 2,
  plan_type: 'fixed',  // ✅ NEW
  coin_distribution_option_id: 1
}
   ↓
1. Validate all fields
2. Execute USDT↔BMAN swap
3. Calculate ROI (principal × rate% / 100)
4. Create roi_staking_management record with:
   - plan_type: 'fixed' | 'regular' | 'combo'
   - fixed_payment_amount: 150,000 BMAN (for fixed)
   - OR monthly breakdown (for regular/combo)
5. Link to staking_swap_orders
6. Return success with plan_type
```

---

## 📝 Testing Checklist

### Step 1: Form Submission
- [ ] Modal shows new "ROI Plan" step
- [ ] Can select "Fixed", "Regular", or "Combo"
- [ ] Plan description updates on selection
- [ ] Plan type appears in form data (DevTools → Network)

### Step 2: Controller Processing
- [ ] POST includes `plan_type` parameter
- [ ] Validation passes for valid types
- [ ] Validation rejects invalid types
- [ ] ROI record created successfully

### Step 3: Database
- [ ] `roi_staking_management` record created
- [ ] Correct `plan_type` stored
- [ ] Correct `fixed_payment_amount` / monthly amounts
- [ ] `staking_swap_orders.roi_staking_management_id` set

### Step 4: Modal Display
- [ ] Order details show `plan_type`
- [ ] Can open staking order detail modal
- [ ] Plan type displays in ROI section

### Step 5: All Plan Types
- [ ] Test FIXED plan (single maturity payment)
- [ ] Test REGULAR plan (3 monthly payments on 5/15/25)
- [ ] Test COMBO plan (3 monthly + 1 maturity)

---

## 🚀 How to Test Manually

### Test Case 1: Fixed Plan

**Steps:**
1. Open Stakings page
2. Click "Buy BMAN" on any package
3. Select package → Click "Next"
4. **NEW: Select "Fixed Plan" → Click "Next"**
5. Select plan (Fixed), term (2 years) → Next
6. Select distribution → Next
7. Preview (shows ROI calculation) → Next
8. Confirm

**Expected:**
- Form data includes `plan_type: 'fixed'`
- `roi_staking_management` record created
- `fixed_payment_amount` = principal × ROI% / 100
- Status will be "pending"

### Test Case 2: Regular Plan

**Steps:** Same as above, but select "Regular Plan" in Step 4

**Expected:**
- `plan_type: 'regular'` in database
- `payment_day_5_amount` = ROI / 3
- `payment_day_15_amount` = ROI / 3
- `payment_day_25_amount` = ROI / 3
- All three payment statuses = "pending"

### Test Case 3: Combo Plan

**Steps:** Same as above, but select "Combo Plan" in Step 4

**Expected:**
- `plan_type: 'combo'` in database
- `payment_day_5_amount` = ROI / 4
- `payment_day_15_amount` = ROI / 4
- `payment_day_25_amount` = ROI / 4
- `fixed_payment_amount` = ROI / 4
- All four payment statuses = "pending"

---

## ✅ Verification Queries

### Check Plan Type Stored

```sql
SELECT 
  id, user_id, plan_type, 
  principal_amount, total_roi_amount,
  fixed_payment_amount,
  payment_day_5_amount,
  payment_day_15_amount,
  payment_day_25_amount
FROM roi_staking_management
ORDER BY created_at DESC
LIMIT 5;
```

Expected output shows different values based on plan_type.

### Check Staking Order Links

```sql
SELECT 
  id, roi_staking_management_id, plan_code,
  roi_rate, maturity_date, maturity_roi_amount
FROM staking_swap_orders
WHERE roi_staking_management_id IS NOT NULL
ORDER BY created_at DESC
LIMIT 5;
```

Expected: Both IDs should be set.

---

## 🎯 Phase 2 Complete Features

✅ **Frontend:**
- ROI plan type selection step in modal
- Plan descriptions displayed
- Form submission includes plan_type

✅ **Backend:**
- Receives and validates plan_type
- Creates roi_staking_management records
- Links to staking_swap_orders
- Returns plan_type in response

✅ **Model:**
- createROIRecord() returns inserted ID
- Supports all three plan types
- Calculates payment schedules

✅ **Database:**
- roi_staking_management stores all plan data
- Links to staking_swap_orders via FK
- Ready for CRON distribution

---

## 📋 Next Phase (Phase 3)

**Modal Display Enhancements:**
- Show plan-specific payment schedule
- Display individual payment statuses
- Add progress bar (X of Y payments)
- Show next payment date

**Testing & Validation:**
- Test CRON execution on payment days
- Verify earning wallet receives ROI
- Check transaction audit trail
- Validate all three plan types end-to-end

---

## 📚 Documentation Files

All comprehensive documentation is in:
- `ROI_IMPLEMENTATION_COMPLETE.md` - Architecture overview
- `ROI_DISTRIBUTION_FLOW.md` - Distribution logic validation
- `ROI_DATA_STORAGE_BREAKDOWN.md` - What stores where
- `ROI_TESTING_CHECKLIST.md` - Full testing procedures
- `ROI_NEXT_PHASE.md` - Remaining work

---

## 🎉 Status: Ready for Testing

Phase 2 is complete and ready to test. Users can now:

1. ✅ Select ROI plan type (Fixed/Regular/Combo) at purchase
2. ✅ Backend creates appropriate ROI management record
3. ✅ System ready for CRON-based distribution

**Next:** Run manual tests, then proceed to Phase 3 (Modal display enhancements).

