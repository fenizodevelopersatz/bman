# Wallet Instant Deposit — Integration Checklist

**Status:** Backend Complete ✅ | Frontend Pending 🔶

---

## 📋 Pre-Integration Checklist

- [ ] Database migration applied: `migration_roi_maturity_2026.sql` ✅
- [ ] Web3bman library configured ✅
- [ ] Token Settings updated with RPC & contracts ✅
- [ ] ChainSync cron running (every 5 min) ✅
- [ ] DepositListener cron running (every 5 min) ✅
- [ ] `Custodialwallet_model.php` updated ✅
- [ ] `Historycontroller.php` updated with `instant_credit_deposits()` ✅

---

## 🎯 Frontend Integration Steps

### **Step 1: Update Wallet History View**
**File:** `application/views/user/wallet/view_mywallet_management.php`

**Add this code AT THE TOP (after header):**

```html
<!-- 1.1: Pending Deposits Alert -->
<?php if (!empty($pending_deposits) && count($pending_deposits) > 0) { ?>
  <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
    <div class="d-flex align-items-center justify-content-between">
      <div>
        <h6 class="alert-heading mb-2">
          <i class="ki-duotone ki-information-5">
            <span class="path1"></span>
            <span class="path2"></span>
          </i>
          <?php echo count($pending_deposits); ?> Deposits Confirmed On-Chain
        </h6>
        <p class="mb-0 small text-muted">
          Your deposits are confirmed on the blockchain (15+ confirmations) but waiting to be credited to your wallet. 
          Click "Credit Now" to instantly credit them — no need to wait for the automatic process.
        </p>
      </div>
      <button id="creditPendingBtn" class="btn btn-sm btn-primary flex-shrink-0 ms-3" type="button">
        <i class="ki-duotone ki-check"></i>
        <span class="d-none d-sm-inline ms-2">Credit Now</span>
      </button>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php } ?>
```

---

### **Step 2: Update Deposit History Table**
**File:** Same as above

**Find the deposit history table and REPLACE it with:**

```html
<!-- 2.1: Deposit History Table -->
<div class="card">
  <div class="card-header">
    <h5 class="card-title">
      <i class="ki-duotone ki-check-double"></i>
      USDT Deposit History
      <?php if (!empty($pending_deposits)) { ?>
        <span class="badge bg-warning ms-2"><?php echo count($pending_deposits); ?> Pending</span>
      <?php } ?>
    </h5>
  </div>
  <div class="card-body">
    <?php if (empty($deposit_history)) { ?>
      <p class="text-muted">No deposits yet. Send USDT to your deposit wallet above.</p>
    <?php } else { ?>
      <div class="table-responsive">
        <table class="table table-striped table-hover table-sm">
          <thead class="table-light">
            <tr>
              <th>Date & Time</th>
              <th>Amount (USDT)</th>
              <th>Status</th>
              <th>TX Hash</th>
              <th>Blocks</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($deposit_history as $deposit) { 
              $isPending = ($deposit['status'] === 'pending_confirmation');
              $isConfirmed = ($deposit['status'] === 'credited');
            ?>
              <tr class="<?php echo $isPending ? 'table-warning' : ''; ?>">
                <!-- Date -->
                <td>
                  <small class="text-muted">
                    <?php 
                      $date = strtotime($deposit['detected_at'] ?? 'now');
                      echo date('M d, Y', $date) . '<br>' . date('H:i:s', $date);
                    ?>
                  </small>
                </td>

                <!-- Amount -->
                <td>
                  <strong>
                    <?php echo number_format((float)$deposit['amount_usdt'], 4); ?>
                  </strong>
                  <span class="text-muted"> USDT</span>
                </td>

                <!-- Status Badge -->
                <td>
                  <?php if ($isConfirmed) { ?>
                    <span class="badge bg-success">
                      <i class="ki-duotone ki-check"></i>
                      Credited
                    </span>
                  <?php } elseif ($isPending) { ?>
                    <span class="badge bg-warning text-dark">
                      <i class="ki-duotone ki-hourglass"></i>
                      Pending
                    </span>
                  <?php } else { ?>
                    <span class="badge bg-secondary">
                      <?php echo ucfirst($deposit['status'] ?? 'Unknown'); ?>
                    </span>
                  <?php } ?>
                </td>

                <!-- TX Hash -->
                <td>
                  <?php if (!empty($deposit['tx_hash'])) { ?>
                    <a href="https://bscscan.com/tx/<?php echo $deposit['tx_hash']; ?>" 
                       target="_blank" 
                       class="text-truncate d-inline-block text-decoration-none"
                       style="max-width: 140px;"
                       title="<?php echo $deposit['tx_hash']; ?>">
                      <code class="text-primary"><?php echo substr($deposit['tx_hash'], 0, 12); ?>...</code>
                    </a>
                  <?php } else { ?>
                    <span class="text-muted">-</span>
                  <?php } ?>
                </td>

                <!-- Confirmations -->
                <td>
                  <small>
                    <?php if (!empty($deposit['confirmations'])) { ?>
                      <?php echo (int)$deposit['confirmations']; ?> / 15
                      <?php if ((int)$deposit['confirmations'] >= 15) { ?>
                        <span class="badge bg-success ms-1" title="Confirmed">✓</span>
                      <?php } ?>
                    <?php } else { ?>
                      <span class="text-muted">-</span>
                    <?php } ?>
                  </small>
                </td>

                <!-- Action Button -->
                <td>
                  <?php if ($isPending) { ?>
                    <button class="btn btn-xs btn-primary credit-single-deposit"
                            data-tx-hash="<?php echo $deposit['tx_hash']; ?>"
                            data-amount="<?php echo number_format((float)$deposit['amount_usdt'], 4); ?>"
                            type="button"
                            title="Click to credit this deposit immediately">
                      <i class="ki-duotone ki-arrow-right"></i>
                      <span class="d-none d-sm-inline ms-1">Credit</span>
                    </button>
                  <?php } else { ?>
                    <span class="text-muted small">-</span>
                  <?php } ?>
                </td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    <?php } ?>
  </div>
</div>
```

---

### **Step 3: Add JavaScript Handler**
**File:** Same wallet view, at the BOTTOM (before closing body tag)

**Add this script:**

```html
<script>
document.addEventListener('DOMContentLoaded', function() {
  
  // ========== Credit All Pending Deposits ==========
  const creditPendingBtn = document.getElementById('creditPendingBtn');
  if (creditPendingBtn) {
    creditPendingBtn.addEventListener('click', creditAllDeposits);
  }

  // ========== Credit Individual Deposit ==========
  document.addEventListener('click', function(e) {
    if (e.target.closest('.credit-single-deposit')) {
      e.preventDefault();
      const btn = e.target.closest('.credit-single-deposit');
      const amount = btn.dataset.amount;
      const txHash = btn.dataset.txHash;
      
      if (confirm(`Credit ${amount} USDT?`)) {
        creditAllDeposits(btn);
      }
    }
  });
});

function creditAllDeposits(btn = null) {
  const button = btn || document.getElementById('creditPendingBtn');
  if (!button) return;

  const originalHTML = button.innerHTML;
  const originalDisabled = button.disabled;

  // Show loading state
  button.disabled = true;
  button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

  // AJAX POST
  fetch('/user/instant-credit-deposits', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    },
    body: JSON.stringify({})
  })
  .then(response => response.json())
  .then(data => {
    button.disabled = originalDisabled;
    button.innerHTML = originalHTML;

    if (data.success) {
      // Show success alert
      showAlert('success', 
        `<strong>✓ Success!</strong><br>` +
        `${data.credited_count} deposit(s) credited<br>` +
        `New balance: <strong>${parseFloat(data.new_balance_usdt).toFixed(4)} USDT</strong>`
      );

      // Reload page after 2 seconds
      setTimeout(() => {
        location.reload();
      }, 2000);

    } else {
      showAlert('danger', 
        `<strong>❌ Error</strong><br>` +
        `${data.message}`
      );
    }
  })
  .catch(error => {
    button.disabled = originalDisabled;
    button.innerHTML = originalHTML;
    
    console.error('Credit error:', error);
    showAlert('danger',
      `<strong>❌ Request Failed</strong><br>` +
      `${error.message}`
    );
  });
}

function showAlert(type, message) {
  const alertHTML = `
    <div class="alert alert-${type} alert-dismissible fade show" role="alert">
      ${message}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  `;

  // Insert at top of main content area
  const mainContent = document.querySelector('.app-main') || document.querySelector('.container');
  if (mainContent) {
    const alertDiv = document.createElement('div');
    alertDiv.innerHTML = alertHTML;
    mainContent.insertBefore(alertDiv.firstElementChild, mainContent.firstChild);

    // Auto-dismiss after 6 seconds
    setTimeout(() => {
      const alerts = document.querySelectorAll('.alert:first-of-type');
      if (alerts.length > 0) {
        const bsAlert = new bootstrap.Alert(alerts[0]);
        bsAlert.close();
      }
    }, 6000);
  }
}
</script>
```

---

## ✅ Testing After Integration

### **Test 1: Pending Deposits Alert**
- [ ] Deposit 0.10 USDT to custodial wallet
- [ ] Wait ~60 seconds (for 15 confirmations)
- [ ] Go to `/user/wallet`
- [ ] Should see blue alert: "1 Deposits Confirmed On-Chain"
- [ ] "Credit Now" button visible

### **Test 2: Deposit in History Table**
- [ ] Same deposit should appear in history table
- [ ] Status badge: "⏳ Pending"
- [ ] Confirmations: "15 / 15"
- [ ] TX Hash: Link to BscScan
- [ ] Action: "Credit" button visible

### **Test 3: Click Credit Button**
- [ ] Click "Credit Now"
- [ ] Loading state: "Processing..."
- [ ] Should show: "✓ 1 deposit(s) credited"
- [ ] Balance updates: "0.10 USDT"
- [ ] Page reloads automatically

### **Test 4: After Credit**
- [ ] Deposit status changes to "✓ Credited"
- [ ] "Credit" button disappears
- [ ] Alert disappears (only shows if pending exists)

### **Test 5: Multiple Pending**
- [ ] Deposit 3 separate USDT amounts (0.05, 0.03, 0.02)
- [ ] All appear as "⏳ Pending" after 60 sec
- [ ] Click "Credit Now" once
- [ ] All 3 should credit together
- [ ] Balance: 0.10 USDT
- [ ] All status badges change to "✓ Credited"

---

## 🔍 Verification Checklist

After integration, verify in **Admin → Wallet → Monitor**:

- [ ] No duplicate wallet_deposits entries (should be idempotent)
- [ ] wallet_ledger has credits for each deposit (tx_hash based)
- [ ] All deposits show in onchain_transactions
- [ ] No errors in application logs

**Database checks:**
```sql
-- Verify pending deposits are detected
SELECT COUNT(*) FROM onchain_transactions 
WHERE to_address = '0x...' AND status = 'confirmed' AND confirmation_count >= 15;

-- Verify no duplicates
SELECT tx_hash, COUNT(*) FROM wallet_deposits 
GROUP BY tx_hash HAVING COUNT(*) > 1;

-- Verify wallet ledger credits
SELECT * FROM wallet_ledger 
WHERE user_id = 1 AND reference_type = 'deposit' 
ORDER BY created_at DESC LIMIT 5;
```

---

## 🚀 Deployment Steps

### **1. Backup Current View**
```bash
cp application/views/user/wallet/view_mywallet_management.php \
   application/views/user/wallet/view_mywallet_management.php.backup
```

### **2. Update View File**
- Add Step 1 code (pending alert) at top
- Add Step 2 code (history table) in content area
- Add Step 3 code (JavaScript) at bottom

### **3. Test Locally**
- Follow "Testing After Integration" checklist
- Check browser console for JS errors
- Verify AJAX requests work

### **4. Deploy to Production**
- Clear browser cache (Ctrl+Shift+Delete)
- Test with small USDT amount first
- Monitor logs: `tail -f application/logs/log-*.php`

### **5. Announce to Users**
- Wallet history now shows pending deposits immediately
- One-click "Credit Now" button available
- No more waiting for 5-minute cron cycles

---

## 📞 Troubleshooting Integration

**Q: Alert not showing?**  
A: Check:
- [ ] `$pending_deposits` variable passed from controller
- [ ] Check browser console for JS errors
- [ ] Ensure deposit has 15+ confirmations

**Q: Credit button not working?**  
A: Check:
- [ ] `/user/instant-credit-deposits` route exists in routes.php
- [ ] `instant_credit_deposits()` method in Historycontroller
- [ ] Browser console for fetch errors
- [ ] Server logs for errors

**Q: Balance not updating?**  
A: Check:
- [ ] DepositListener cron running
- [ ] Walletledger_model configured correctly
- [ ] Database permissions for inserts
- [ ] No duplicate entries (check `wallet_deposits` tx_hash)

**Q: How do I rollback?**  
A: 
```bash
# Restore backup
cp application/views/user/wallet/view_mywallet_management.php.backup \
   application/views/user/wallet/view_mywallet_management.php
```

---

## 📊 Summary

| Component | Status | Reference |
|-----------|--------|-----------|
| Backend Model | ✅ Done | Custodialwallet_model.php |
| Controller Endpoint | ✅ Done | Historycontroller.php |
| Routes | ✅ Done | routes.php |
| View Integration | 🔶 **This Checklist** | view_mywallet_management.php |
| JavaScript | 🔶 **This Checklist** | Step 3 above |
| Testing | 🔶 **Follow Above** | Testing section |

---

**Status: Ready for Frontend Integration** ✅

All backend code is complete and tested. Use this checklist to integrate the instant deposit feature into your wallet view.

