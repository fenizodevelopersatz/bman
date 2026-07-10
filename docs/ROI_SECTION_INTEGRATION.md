# ROI Details Section Integration Guide

**Purpose:** Add ROI calculation details to the existing staking purchase modal  
**File:** `application/views/user/staking/staking_purchase_roi_section.php`  
**Integration:** Add to existing staking purchase details modal

---

## What Gets Added

The ROI section displays:
- ✅ Principal Investment (LOCKED)
- ✅ Expected ROI Return (LIQUID)
- ✅ ROI Rate (150%, 200%, etc)
- ✅ Duration (2 Years, 3 Years)
- ✅ Bonus Received
- ✅ Key Points (Locked vs Liquid)
- ✅ ROI Breakdown Table (Yearly, Year 2, TOTAL VALUE)
- ✅ Purchase & Maturity Dates

---

## Integration Steps

### Step 1: Find Your Staking Purchase Modal

Look for the file where you show staking purchase details. This is likely:
```
application/views/user/staking/staking_purchase_details.php
OR
application/views/user/stakings/staking_purchase_details.php
```

### Step 2: Add the ROI Section Include

After the "Plan Details" card, add this line:

```php
<!-- Include ROI Details Section -->
<?php include('staking_purchase_roi_section.php'); ?>
```

**Example placement in your modal:**

```php
<!-- Existing Modal Content -->
<div class="modal-body">
    
    <!-- Existing: Order Details Card -->
    <div class="card mb-4">
        <div class="card-header">Order Summary</div>
        <div class="card-body">
            <!-- existing content -->
        </div>
    </div>
    
    <!-- Existing: Plan Details Card -->
    <div class="card mb-4">
        <div class="card-header">Plan Details</div>
        <div class="card-body">
            <!-- existing content -->
        </div>
    </div>
    
    <!-- NEW: ROI Details Section -->
    <?php include('staking_purchase_roi_section.php'); ?>
    
    <!-- Existing: Transaction Steps -->
    <div class="card mb-4">
        <div class="card-header">Transaction Steps</div>
        <div class="card-body">
            <!-- existing content -->
        </div>
    </div>
    
</div>
```

### Step 3: Pass Dynamic Data

When you load the modal via JavaScript, pass the staking order data:

**Current code (example):**
```javascript
// In your existing JavaScript
function showStakingPurchaseModal(orderId) {
    $.ajax({
        url: '<?php echo base_url("api/staking/details"); ?>',
        method: 'GET',
        data: { order_id: orderId },
        success: function(response) {
            // Populate existing fields
            $('#order_id').text(response.order_id);
            
            // NEW: Call populateROIDetails with the data
            populateROIDetails({
                principal: response.bman_amount,
                roi_total: response.total_roi_at_maturity,
                roi_rate: response.annual_roi_rate,
                duration_years: Math.ceil(response.maturity_days / 365),
                bonus: response.bonus_bman,
                purchase_date: response.created_at,
                maturity_date: response.maturity_date,
                annual_roi: (response.bman_amount * response.annual_roi_rate / 100)
            });
            
            $('#stakingModal').modal('show');
        }
    });
}
```

---

## What It Looks Like

### Visual Layout

```
┌─────────────────────────────────────────┐
│ ROI Details & Returns                   │
├─────────────────────────────────────────┤
│                                         │
│ PRINCIPAL         │    EXPECTED ROI     │
│ 100,000 BMAN      │    150,000 BMAN     │
│ 🔒 LOCKED         │    🔓 LIQUID        │
│                                         │
│ ROI Rate: 150% │ Duration: 2 Years │ Bonus: 25,000 │
│                                         │
│ ✓ Principal is LOCKED                  │
│ ✓ ROI is LIQUID (hourly)                │
│ ✓ At Maturity: 250,000 BMAN             │
│ ✓ Bonus 25,000 BMAN (yours to keep)     │
│                                         │
│ Period   │ ROI Earned  │ Cumulative     │
│ Yearly   │ 75,000      │ 75,000         │
│ Year 2   │ 75,000      │ 150,000        │
│ TOTAL    │ 100,000+    │ 250,000 BMAN   │
│          │ 150,000     │                │
│                                         │
│ Purchased: 2026-07-10 | Matures: 2028-07-10
│                                         │
└─────────────────────────────────────────┘
```

---

## Database Queries

Make sure your staking API endpoint returns:

```sql
SELECT 
    sso.ref as order_id,
    sso.user_id,
    sso.created_at,
    sso.bman_amount,
    sso.bonus_bman,
    sp.annual_roi_rate,
    sp.maturity_days,
    sp.name as package_name
FROM staking_swap_orders sso
LEFT JOIN staking_packages sp ON sso.package_id = sp.id
WHERE sso.ref = ?
```

---

## Calculated Fields

The section automatically calculates:

```javascript
// From your API data:
const principal = bman_amount;              // 100,000
const roi_rate = annual_roi_rate;           // 150
const duration_years = ceil(maturity_days / 365);  // 2
const roi_total = principal * (roi_rate / 100) * (maturity_days / 365);  // 150,000
const total_at_maturity = principal + roi_total;  // 250,000
const annual_roi = principal * (roi_rate / 100);  // 75,000
```

---

## Testing Checklist

- [ ] ROI section appears below Plan Details
- [ ] Numbers match your staking order (100,000 BMAN, 150%, etc)
- [ ] Colors display correctly (green for unlock, red for lock)
- [ ] Table shows yearly breakdown
- [ ] Key Points alert displays all 4 bullet points
- [ ] Dates are formatted correctly
- [ ] "ACTIVE & EARNING" badge shows green

---

## Troubleshooting

### ROI section not showing?
- Check file path is correct: `staking_purchase_roi_section.php`
- Verify include statement has correct path
- Check browser console for JavaScript errors

### Numbers not populating?
- Verify `populateROIDetails()` is called with correct data
- Check API endpoint returns all required fields
- Use browser DevTools to inspect data being passed

### Styling looks off?
- The section uses Bootstrap classes (card, badge, alert)
- Uses FontAwesome icons - ensure included in page
- CSS is inline in the included file

---

## Optional: Standalone Modal

If you want a separate detailed ROI modal (triggered by a "View ROI Details" button):

Use the existing file:
```
application/views/user/staking/roi_details_modal.php
```

Trigger with:
```javascript
showROIDetails(orderId);
```

---

## Your Current Staking Example

Based on your 100,000 BMAN purchase:

```php
populateROIDetails({
    principal: 100000,           // Your investment
    roi_total: 150000,           // What you'll earn
    roi_rate: 150,               // 150% return
    duration_years: 2,           // 2-year term
    bonus: 25000,                // Bonus BMAN
    purchase_date: '2026-07-10',
    maturity_date: '2028-07-10',
    annual_roi: 75000            // 75,000 per year
});
```

Result displayed:
- 🔒 100,000 BMAN (LOCKED until 2028-07-10)
- 🔓 150,000 BMAN (LIQUID - earned hourly)
- 💰 250,000 BMAN (Total at maturity)
- 🎁 25,000 BMAN (Bonus - yours to keep)

---

## Next Steps

1. **Add the include** to your staking purchase modal
2. **Update your AJAX** to pass the ROI data
3. **Test with a real order** - verify all numbers match
4. **Check styling** - adjust CSS if needed
5. **Monitor** - ensure ROI calculations remain accurate

That's it! Your users will now see full ROI details when viewing their staking purchases.
