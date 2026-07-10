# 🔧 Fix: Missing Payload Fields in Purchase Form

**Status:** ⚠️ **IN PROGRESS**  
**Date:** 2026-07-09  
**Issue:** Form sends only `package_id`, `plan_code`, `duration_years` but missing `coin_distribution_option_id` and `plan_id`

---

## 🔴 The Problem

**Form Data Sent (Current):**
```
package_id: 1
plan_code: fixed
duration_years: 2
```

**Form Data Missing:**
```
❌ coin_distribution_option_id: 6  (shown as "Option 6" in modal!)
❌ plan_id: 0
```

**Result in Database:**
```sql
INSERT staking_swap_orders:
  package_id: 1          ✅
  plan_code: fixed       ✅
  duration_years: 2      ✅
  coin_distribution_option: 1   ❌ (defaults to 1, not 6!)
  plan_id: NULL          ❌ (not saved)
```

---

## ✅ The Solution

### **Step 1: Identify Where Distribution Option is Selected**

In the modal showing "Final Purchase Summary", the distribution option **IS** being selected (shows "Option 6"), but the value isn't being sent.

**Location:** Look for where "Option 6" is being displayed in the modal

### **Step 2: Add Data Attributes to Modal Elements**

Update the HTML to include data attributes that the JavaScript can read:

```html
<!-- In the modal showing distribution selection -->
<div data-distribution="6">Option 6: Earning Focus</div>

<!-- OR in plan code display -->
<span data-plan-code="fixed">Fixed - 2 Years</span>

<!-- OR in duration display -->
<span data-duration-years="2">2 Years</span>

<!-- OR in plan ID field -->
<input type="hidden" data-plan-id="0" />
```

### **Step 3: JavaScript Captures These Values**

The updated JavaScript now looks for:
```javascript
// Try to find distribution option from multiple sources
const coinDistOption = 
    document.querySelector('input[name="coin_dist"]:checked')?.value ||  // Radio button
    document.querySelector('input[data-distribution]')?.value ||          // Hidden input
    document.querySelector('[data-distribution]')?.textContent ||         // Text element
    '1'; // Default

// Try to find plan code
const planCode = 
    document.querySelector('[data-plan-code]')?.textContent ||  // Text with plan code
    'fixed'; // Default

// Try to find duration
const durationYears = 
    document.querySelector('[data-duration-years]')?.textContent ||  // Element showing duration
    document.querySelector('[data-duration]')?.value ||              // Input field
    '1'; // Default

// Try to find plan ID
const planId = 
    document.querySelector('[data-plan-id]')?.value ||      // Input field
    document.querySelector('[data-plan-id]')?.textContent || // Text element
    '0'; // Default
```

### **Step 4: Values Are Now Sent**

Form POST now includes:
```
POST /user/usersettings/lending/swap_purchase

Body:
  package_id: 1
  coin_distribution_option_id: 6         ✅ (now sent!)
  plan_code: fixed                       ✅ (now sent!)
  plan_id: 0                             ✅ (now sent!)
  duration_years: 2                      ✅ (now sent!)
```

---

## 📝 Implementation Steps

### **Option A: Using the Existing View (view_swap_purchase.php)**

If using the distribution selector in view_swap_purchase.php:

1. The radio buttons are already named `coin_dist`
2. Selected value will be captured automatically
3. Just ensure the modal is displayed before form submission

**Code:**
```html
<!-- Already in place -->
<input class="form-check-input" type="radio" name="coin_dist" id="opt6" value="6">
<label class="form-check-label" for="opt6">Option 6: Earning Focus</label>
```

### **Option B: Using Hidden Fields (For Other Forms)**

If the distribution is selected elsewhere, add hidden fields:

```html
<!-- Add to your form -->
<input type="hidden" name="coin_distribution_option_id" id="coinDistField" value="1">
<input type="hidden" name="plan_id" id="planIdField" value="0">
<input type="hidden" name="plan_code" id="planCodeField" value="fixed">
<input type="hidden" name="duration_years" id="durationField" value="1">

<!-- Update these before form submission -->
<script>
function updateFormFields(distOption, planCode, planId, duration) {
    document.getElementById('coinDistField').value = distOption;
    document.getElementById('planCodeField').value = planCode;
    document.getElementById('planIdField').value = planId;
    document.getElementById('durationField').value = duration;
}

// Call before submitting purchase
updateFormFields(6, 'fixed', 0, 2);
</script>
```

### **Option C: Using Data Attributes (For Dynamic Content)**

If values are in the displayed modal:

```html
<!-- In your modal/form showing the selected options -->
<div class="final-summary">
    <span data-distribution="6">Distribution: Option 6</span>
    <span data-plan-code="fixed">Plan: Fixed</span>
    <span data-duration-years="2">Duration: 2 Years</span>
    <input type="hidden" data-plan-id="0" />
</div>
```

The JavaScript will automatically extract these values.

---

## 🧪 Testing the Fix

### **Step 1: Open DevTools**
```
F12 → Network tab
```

### **Step 2: Perform Purchase**
```
1. Go to /user/lending
2. Click "Buy BMAN"
3. Select Distribution Option (e.g., Option 6)
4. Submit form
```

### **Step 3: Check Form Data**

In DevTools, the POST request should now show:
```
✅ package_id: 1
✅ coin_distribution_option_id: 6
✅ plan_code: fixed
✅ plan_id: 0
✅ duration_years: 2
```

### **Step 4: Check Database**

```sql
SELECT id, package_id, coin_distribution_option, plan_code, plan_id, duration_years
FROM staking_swap_orders
WHERE id = (SELECT MAX(id) FROM staking_swap_orders);
```

**Expected:**
```
id: 3
package_id: 1
coin_distribution_option: 6        ✅ (Now saved!)
plan_code: fixed                   ✅ (Now saved!)
plan_id: 0                         ✅ (Now saved!)
duration_years: 2                  ✅ (Now saved!)
```

---

## 🔍 Console Debugging

The updated JavaScript logs form data to console:

```javascript
console.log('Form Data:', {
    package_id: 1,
    coin_distribution_option_id: 6,
    plan_code: 'fixed',
    plan_id: 0,
    duration_years: 2
});
```

**Check Console (F12):**
```
Open DevTools → Console tab
Form Data: {
  package_id: 1,
  coin_distribution_option_id: 6,
  plan_code: 'fixed',
  plan_id: 0,
  duration_years: 2
}
```

If `coin_distribution_option_id` shows as `1` instead of `6`, the selector isn't finding the value.

---

## 🎯 Quick Checklist

- [ ] Distribution option is displayed in modal (shows "Option 6")
- [ ] Modal has HTML element with distribution value (radio button, div, input, etc.)
- [ ] JavaScript can find the element using one of the selectors
- [ ] Console log shows correct values
- [ ] DevTools POST data includes all 5 fields
- [ ] Database saves all values correctly

---

## 📋 What Each Field Means

| Field | Example | Purpose | Where Used |
|-------|---------|---------|-----------|
| `package_id` | 1 | Which staking package | Links to staking_packages table |
| `coin_distribution_option_id` | 6 | How to split BMAN across 4 wallets | Cron uses to allocate BMAN |
| `plan_code` | fixed | Type of staking plan | Display, cron processing |
| `plan_id` | 0 | Specific plan identifier | Links to staking plans table |
| `duration_years` | 2 | How long stake lasts | Cron, ROI calculation |

---

## 🔗 Related Files

- `application/views/user/wallet/view_swap_purchase.php` - Has distribution selector
- `application/views/user/wallet/lending_managment.php` - Shows staking activity
- `application/views/user/wallet/_staking_packages.php` - Displays packages
- `application/controllers/user/usersettings/Lendingcontroller.php` - swap_purchase method
- `application/models/staking/Swapengine_model.php` - Creates order record

---

## 🚀 Next Steps

1. **Verify modal has the values** - Check the purchase modal HTML
2. **Identify value sources** - Find where distribution option is displayed
3. **Add data attributes** - If needed, add data-* attributes for JavaScript
4. **Test console log** - Check DevTools Console for correct values
5. **Verify POST request** - Check DevTools Network tab for all fields
6. **Confirm database** - Query staking_swap_orders to verify all fields saved

---

## 💡 If Still Not Working

If the values still aren't being sent:

1. **Check if radio buttons are visible:**
   ```javascript
   console.log(document.querySelector('input[name="coin_dist"]:checked'));
   // Should return the selected radio button element
   ```

2. **Check if modal has data attributes:**
   ```javascript
   console.log(document.querySelector('[data-distribution]'));
   // Should return the element with distribution value
   ```

3. **Manually set the value:**
   ```javascript
   // In the showPurchaseModal function, hardcode the value temporarily to test
   const coinDistOption = '6'; // Test value
   ```

4. **Check browser console for errors:**
   - Look for JavaScript errors in DevTools Console
   - Check if the querySelector is returning null

---

**Goal:** All 5 fields sent → All 5 fields saved in database → Cron can process correctly ✅
