# ROI Staking Management - Next Phase Work

## Current Status: ✅ VERTICAL SLICE COMPLETE

The core ROI infrastructure is **fully implemented and operational**:
- Database schema complete
- Model layer finished
- CRON jobs ready
- Routes configured
- Dashboard integrated

---

## 🔄 Remaining Integration Work

### Phase 2: UI Selection & Purchase Flow

#### 1. Add Plan Type Selection to Purchase Form

**File:** `application/views/user/wallet/lending_managment.php`

**What to add:**
```html
<!-- After principal amount input -->
<div class="form-group">
  <label for="plan_type">ROI Plan Type</label>
  <select id="plan_type" name="plan_type" class="form-control" required>
    <option value="">-- Select Plan Type --</option>
    <option value="fixed">
      Fixed Plan: Single payment at maturity (100% at end)
    </option>
    <option value="regular">
      Regular Plan: 3 monthly payments (days 5, 15, 25)
    </option>
    <option value="combo">
      Combo Plan: Monthly payments + final maturity payout
    </option>
  </select>
  <small class="form-text text-muted">
    Choose how you want to receive your ROI returns
  </small>
</div>
```

**Display ROI Distribution Preview:**
```html
<div id="roi_preview" class="alert alert-info" style="display:none;">
  <h6>Your ROI Distribution</h6>
  <div id="roi_preview_content"></div>
</div>

<script>
document.getElementById('plan_type').addEventListener('change', function() {
  const planType = this.value;
  const principal = parseFloat(document.getElementById('principal_amount').value);
  const roi = parseFloat(document.getElementById('roi_rate').value);
  const totalROI = (principal * roi / 100);
  
  let preview = '';
  
  if (planType === 'fixed') {
    preview = `<p><strong>Fixed Plan:</strong></p>
               <ul><li>Maturity Date: [calculated]</li>
                   <li>ROI Amount: ${totalROI.toLocaleString()} BMAN</li>
                   <li>Payment: Lump sum at maturity</li></ul>`;
  } else if (planType === 'regular') {
    preview = `<p><strong>Regular Plan:</strong></p>
               <ul><li>Day 5: ${(totalROI/3).toLocaleString()} BMAN</li>
                   <li>Day 15: ${(totalROI/3).toLocaleString()} BMAN</li>
                   <li>Day 25: ${(totalROI/3).toLocaleString()} BMAN</li>
                   <li>Total: ${totalROI.toLocaleString()} BMAN</li></ul>`;
  } else if (planType === 'combo') {
    preview = `<p><strong>Combo Plan:</strong></p>
               <ul><li>Days 5,15,25: ${(totalROI/4).toLocaleString()} BMAN each</li>
                   <li>Maturity: ${(totalROI/4).toLocaleString()} BMAN</li>
                   <li>Total: ${totalROI.toLocaleString()} BMAN</li></ul>`;
  }
  
  document.getElementById('roi_preview_content').innerHTML = preview;
  document.getElementById('roi_preview').style.display = 'block';
});
</script>
```

---

#### 2. Update Lending Controller

**File:** `application/controllers/user/usersettings/Lendingcontroller.php`

**Update `swap_purchase()` method:**

```php
public function swap_purchase()
{
    // ... existing validation ...
    
    $plan_type = $this->input->post('plan_type', true);
    
    // Validate plan type
    if (!in_array($plan_type, ['fixed', 'regular', 'combo'])) {
        return $this->_json(['error' => 'Invalid plan type'], 400);
    }
    
    // ... existing staking swap code ...
    
    // After successful swap, create ROI record
    $this->load->model('RoiStakingManagement_model', 'roi_mgmt');
    
    try {
        $roi_record = $this->roi_mgmt->createROIRecord([
            'staking_swap_orders_id' => $swap_id,
            'user_id' => $user_id,
            'plan_type' => $plan_type,
            'principal_amount' => $principal,
            'roi_rate' => $roi_rate,
            'duration_years' => $duration_years,
            'coin_distribution_option' => $coin_distribution_option,
        ]);
        
        // Store FK in staking_swap_orders
        $this->db->where('id', $swap_id)
                 ->update('staking_swap_orders', [
                     'roi_staking_management_id' => $roi_record['id']
                 ]);
        
        return $this->_json([
            'success' => true,
            'message' => 'Staking purchase successful',
            'roi_record_id' => $roi_record['id'],
            'plan_type' => $plan_type
        ]);
        
    } catch (Exception $e) {
        return $this->_json(['error' => $e->getMessage()], 500);
    }
}
```

---

### Phase 3: Modal Display Updates

#### Update ROI PROGRESS Tab to Show Plan Details

**File:** `application/views/user/wallet/lending_managment.php`

**Replace ROI section with:**

```html
<!-- ROI PROGRESS Tab -->
<div class="tab-pane fade" id="roi_tab" role="tabpanel">
  <div class="container-fluid">
    
    <!-- Plan Type Badge -->
    <div class="row mb-3">
      <div class="col-12">
        <span id="roi_plan_type" class="badge badge-primary" style="font-size: 14px;"></span>
      </div>
    </div>
    
    <!-- Quick Stats -->
    <div class="row">
      <div class="col-md-4 mb-3">
        <div class="card">
          <div class="card-body text-center">
            <h6 class="card-title">Days Staking</h6>
            <p class="h5" id="roi_days_staking">--</p>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-3">
        <div class="card">
          <div class="card-body text-center">
            <h6 class="card-title">Daily ROI</h6>
            <p class="h5" id="roi_daily_amount">--</p>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-3">
        <div class="card">
          <div class="card-body text-center">
            <h6 class="card-title">ROI Rate</h6>
            <p class="h5" id="roi_rate_percentage">--</p>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Timeline (Different for each plan) -->
    <div class="row">
      <div class="col-12">
        <h6 class="mb-3">Payment Schedule</h6>
        <div id="roi_payment_schedule"></div>
      </div>
    </div>
    
    <!-- Bottom Info -->
    <div class="row mt-4">
      <div class="col-md-6 mb-3">
        <div class="card">
          <div class="card-body">
            <h6 class="card-title">Purchase Date</h6>
            <p id="roi_purchase_date">--</p>
          </div>
        </div>
      </div>
      <div class="col-md-6 mb-3">
        <div class="card">
          <div class="card-body">
            <h6 class="card-title">Maturity Date</h6>
            <p id="roi_maturity_date">--</p>
          </div>
        </div>
      </div>
    </div>
    
    <div class="row">
      <div class="col-md-6 mb-3">
        <div class="card">
          <div class="card-body">
            <h6 class="card-title">Investment Amount</h6>
            <p id="roi_investment">--</p>
          </div>
        </div>
      </div>
      <div class="col-md-6 mb-3">
        <div class="card">
          <div class="card-body">
            <h6 class="card-title">Total ROI Return</h6>
            <p id="roi_total_return">--</p>
          </div>
        </div>
      </div>
    </div>
    
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-body">
            <h6 class="card-title">Distribution Status</h6>
            <div id="roi_distribution_status"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function displayROIDetails(data) {
  const planType = data.plan_type || 'unknown';
  const planLabels = {
    'fixed': '📍 Fixed Plan',
    'regular': '📅 Regular Plan (Monthly)',
    'combo': '🔄 Combo Plan (Mixed)'
  };
  
  document.getElementById('roi_plan_type').textContent = planLabels[planType] || planType;
  
  // Populate basic info
  document.getElementById('roi_purchase_date').textContent = data.created_at || '--';
  document.getElementById('roi_maturity_date').textContent = data.maturity_date || '--';
  document.getElementById('roi_investment').textContent = (data.principal_amount || 0).toLocaleString() + ' BMAN';
  document.getElementById('roi_total_return').textContent = (data.maturity_roi_amount || 0).toLocaleString() + ' BMAN';
  document.getElementById('roi_rate_percentage').textContent = (data.roi_rate || 0) + '%';
  
  // Display payment schedule based on plan type
  if (planType === 'fixed') {
    displayFixedSchedule(data);
  } else if (planType === 'regular') {
    displayRegularSchedule(data);
  } else if (planType === 'combo') {
    displayComboSchedule(data);
  }
}

function displayFixedSchedule(data) {
  const schedule = `
    <div class="alert alert-info">
      <h6>Single Maturity Payment</h6>
      <p><strong>Payment Date:</strong> ${data.maturity_date || '--'}</p>
      <p><strong>Amount:</strong> ${(data.maturity_roi_amount || 0).toLocaleString()} BMAN</p>
      <p><strong>Status:</strong> <span class="badge badge-${data.roi_return_status === 'completed' ? 'success' : 'warning'}">${data.roi_return_status || 'pending'}</span></p>
    </div>
  `;
  document.getElementById('roi_payment_schedule').innerHTML = schedule;
}

function displayRegularSchedule(data) {
  const amount = ((data.maturity_roi_amount || 0) / 3).toLocaleString();
  const schedule = `
    <div class="alert alert-info">
      <h6>3 Monthly Payments</h6>
      <div class="row">
        <div class="col-md-4"><strong>Day 5:</strong> ${amount} BMAN <span class="badge badge-${data.payment_day_5_status === 'completed' ? 'success' : 'warning'}">${data.payment_day_5_status || 'pending'}</span></div>
        <div class="col-md-4"><strong>Day 15:</strong> ${amount} BMAN <span class="badge badge-${data.payment_day_15_status === 'completed' ? 'success' : 'warning'}">${data.payment_day_15_status || 'pending'}</span></div>
        <div class="col-md-4"><strong>Day 25:</strong> ${amount} BMAN <span class="badge badge-${data.payment_day_25_status === 'completed' ? 'success' : 'warning'}">${data.payment_day_25_status || 'pending'}</span></div>
      </div>
    </div>
  `;
  document.getElementById('roi_payment_schedule').innerHTML = schedule;
}

function displayComboSchedule(data) {
  const monthlyAmount = ((data.maturity_roi_amount || 0) / 4).toLocaleString();
  const maturityAmount = ((data.maturity_roi_amount || 0) / 4).toLocaleString();
  const schedule = `
    <div class="alert alert-info">
      <h6>Monthly Payments + Maturity</h6>
      <div class="row mb-2">
        <div class="col-md-4"><strong>Day 5:</strong> ${monthlyAmount} BMAN <span class="badge badge-${data.payment_day_5_status === 'completed' ? 'success' : 'warning'}">${data.payment_day_5_status || 'pending'}</span></div>
        <div class="col-md-4"><strong>Day 15:</strong> ${monthlyAmount} BMAN <span class="badge badge-${data.payment_day_15_status === 'completed' ? 'success' : 'warning'}">${data.payment_day_15_status || 'pending'}</span></div>
        <div class="col-md-4"><strong>Day 25:</strong> ${monthlyAmount} BMAN <span class="badge badge-${data.payment_day_25_status === 'completed' ? 'success' : 'warning'}">${data.payment_day_25_status || 'pending'}</span></div>
      </div>
      <div class="row">
        <div class="col-md-12"><strong>Maturity (${data.maturity_date}):</strong> ${maturityAmount} BMAN <span class="badge badge-${data.fixed_status === 'completed' ? 'success' : 'warning'}">${data.fixed_status || 'pending'}</span></div>
      </div>
    </div>
  `;
  document.getElementById('roi_payment_schedule').innerHTML = schedule;
}
</script>
```

---

### Phase 4: Testing Verification

**What needs testing:**
1. [x] CRON endpoints respond correctly
2. [x] Database records create properly
3. [ ] Plan type saves in staking_swap_orders
4. [ ] AJAX returns plan_type and payment details
5. [ ] UI displays correct payment schedule
6. [ ] Payments credit on correct days (5, 15, 25)
7. [ ] Maturity payment triggers on correct date
8. [ ] Earning wallet balances update correctly
9. [ ] Transaction audit trail records all payments
10. [ ] All three plan types work end-to-end

---

## 📋 Implementation Roadmap

### Quick Wins (1-2 hours)
- [ ] Add plan_type dropdown to purchase form
- [ ] Display ROI preview on plan selection
- [ ] Update Lendingcontroller to save plan_type

### Medium Effort (2-4 hours)
- [ ] Update modal ROI tab display
- [ ] Show plan-specific payment schedules
- [ ] Display individual payment statuses
- [ ] Add progress bar for payments completed

### Testing & Polish (2-3 hours)
- [ ] Manual testing on payment days
- [ ] Verify CRON executions
- [ ] Check earning wallet credits
- [ ] Audit transaction records
- [ ] UI/UX refinements

---

## 🎯 Success Metrics

Once Phase 2-4 complete:

✅ Users can select plan type (Fixed/Regular/Combo) at purchase  
✅ ROI records create automatically with correct plan details  
✅ Modal displays plan-specific payment schedule  
✅ CRON jobs process payments automatically on correct dates  
✅ Earning wallet receives ROI credits on schedule  
✅ All transactions recorded with audit trail  
✅ System ready for production ROI distribution  

---

## 📚 Documentation Files

| File | Purpose |
|------|---------|
| ROI_IMPLEMENTATION_COMPLETE.md | Current architecture & implementation status |
| ROI_TESTING_CHECKLIST.md | Step-by-step testing procedures |
| ROI_NEXT_PHASE.md | This file - remaining work |

