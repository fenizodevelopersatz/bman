# Instant Deposit Crediting — Frontend Integration

## Problem Solved ✅

**Before:** User deposits USDT → Must wait 5+ minutes for DepositListener cron to detect & credit
- ❌ Wallet history shows nothing
- ❌ Balance not updated
- ❌ User confusion

**After:** User deposits USDT → Appears in wallet history immediately + can click "Credit" button
- ✅ Pending deposits visible with "PENDING CONFIRMATION" status
- ✅ One-click "Credit Deposits" button to credit instantly
- ✅ Balance updates immediately after confirming

---

## What Changed

### Backend Enhancements

**1. Custodialwallet_model::deposits()** — Now returns BOTH:
- ✅ Confirmed deposits (already credited from `wallet_deposits` table)
- ✅ **Pending deposits** (on-chain confirmed, 15+ blocks, but not yet credited)

**2. New Endpoint:** `POST /user/instant-credit-deposits`
- Manually triggers deposit scan for the user
- Credits any pending (on-chain confirmed) deposits immediately
- Returns updated balance + count of credited deposits

---

## Frontend Implementation

### Step 1: Show Pending Deposits Alert

In your wallet view (e.g., `view_mywallet_management.php`), add this alert near the top:

```html
<!-- Pending Deposits Alert & Quick Action -->
<?php if (!empty($pending_deposits) && count($pending_deposits) > 0) { ?>
  <div class="alert alert-info mb-3" role="alert">
    <div class="d-flex align-items-center justify-content-between">
      <div>
        <h6 class="alert-heading mb-1">
          <i class="ki-duotone ki-information-5">
            <span class="path1"></span>
            <span class="path2"></span>
          </i>
          <?php echo count($pending_deposits); ?> Pending Deposits Confirmed On-Chain
        </h6>
        <p class="mb-0 small">
          Your deposits are confirmed on the blockchain but waiting to be credited to your account.
          Click "Credit Now" to instantly credit them.
        </p>
      </div>
      <button id="creditPendingBtn" class="btn btn-sm btn-primary flex-shrink-0 ms-2">
        <i class="ki-duotone ki-check"></i>
        Credit Now
      </button>
    </div>
  </div>
<?php } ?>
```

### Step 2: Display Pending Deposits in History Table

Modify the deposit history table to show both confirmed and pending:

```html
<table class="table table-striped table-hover">
  <thead>
    <tr>
      <th>Date</th>
      <th>Amount (USDT)</th>
      <th>Status</th>
      <th>TX Hash</th>
      <th>Confirmations</th>
      <th>Action</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($deposit_history as $deposit) { ?>
      <tr class="<?php echo ($deposit['status'] === 'pending_confirmation') ? 'table-warning' : ''; ?>">
        <td>
          <small>
            <?php echo date('Y-m-d H:i', strtotime($deposit['detected_at'] ?? 'now')); ?>
          </small>
        </td>
        <td>
          <strong><?php echo number_format((float)$deposit['amount_usdt'], 4); ?> USDT</strong>
        </td>
        <td>
          <?php if ($deposit['status'] === 'credited') { ?>
            <span class="badge bg-success">
              <i class="ki-duotone ki-check"></i> Credited
            </span>
          <?php } elseif ($deposit['status'] === 'pending_confirmation') { ?>
            <span class="badge bg-warning text-dark">
              <i class="ki-duotone ki-hourglass"></i> Pending Confirmation
            </span>
          <?php } else { ?>
            <span class="badge bg-secondary"><?php echo ucfirst($deposit['status']); ?></span>
          <?php } ?>
        </td>
        <td>
          <?php if (!empty($deposit['tx_hash'])) { ?>
            <a href="https://bscscan.com/tx/<?php echo $deposit['tx_hash']; ?>" 
               target="_blank" class="text-truncate d-inline-block" style="max-width: 150px;"
               title="<?php echo $deposit['tx_hash']; ?>">
              <code><?php echo substr($deposit['tx_hash'], 0, 10); ?>...</code>
            </a>
          <?php } else { ?>
            <span class="text-muted">-</span>
          <?php } ?>
        </td>
        <td>
          <?php if (!empty($deposit['confirmations'])) { ?>
            <small class="text-muted">
              <?php echo (int)$deposit['confirmations']; ?> blocks
            </small>
          <?php } else { ?>
            <span class="text-muted">-</span>
          <?php } ?>
        </td>
        <td>
          <?php if ($deposit['status'] === 'pending_confirmation') { ?>
            <button class="btn btn-xs btn-primary credit-single-deposit" 
                    data-tx-hash="<?php echo $deposit['tx_hash']; ?>"
                    data-amount="<?php echo $deposit['amount_usdt']; ?>">
              <i class="ki-duotone ki-check"></i> Credit
            </button>
          <?php } else { ?>
            <span class="text-muted small">-</span>
          <?php } ?>
        </td>
      </tr>
    <?php } ?>
  </tbody>
</table>
```

### Step 3: JavaScript for Instant Crediting

Add this script to handle the "Credit" button click:

```html
<script>
$(document).ready(function() {
  // Credit all pending deposits
  $('#creditPendingBtn').click(function() {
    creditAllPendingDeposits();
  });

  // Credit individual deposit
  $(document).on('click', '.credit-single-deposit', function(e) {
    e.preventDefault();
    const btn = $(this);
    const amount = btn.data('amount');
    
    // Optional: ask for confirmation
    if (!confirm(`Credit ${amount} USDT?`)) return;
    
    creditAllPendingDeposits(btn);
  });
});

function creditAllPendingDeposits(btn) {
  const button = btn || $('#creditPendingBtn');
  const originalHtml = button.html();
  
  // Show loading state
  button
    .prop('disabled', true)
    .html('<span class="spinner-border spinner-border-sm me-2"></span>Processing...');
  
  $.post('/user/instant-credit-deposits', {}, function(response) {
    button.prop('disabled', false).html(originalHtml);
    
    if (response.success) {
      // Success!
      showAlert('success', `✓ ${response.credited_count} deposits credited`);
      showAlert('info', `New USDT balance: <strong>${parseFloat(response.new_balance_usdt).toFixed(4)}</strong>`);
      
      // Refresh wallet page after 2 seconds
      setTimeout(function() {
        location.reload();
      }, 2000);
      
    } else {
      showAlert('danger', response.message);
    }
  }).fail(function(err) {
    button.prop('disabled', false).html(originalHtml);
    showAlert('danger', 'Credit failed: ' + (err.responseJSON?.message || 'Unknown error'));
  });
}

function showAlert(type, message) {
  const alertHtml = `
    <div class="alert alert-${type} alert-dismissible fade show" role="alert">
      ${message}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  `;
  
  // Insert at top of content area
  $('.app-main').prepend(alertHtml);
  
  // Auto-dismiss after 5 seconds
  setTimeout(function() {
    $('.alert:first').fadeOut(function() { $(this).remove(); });
  }, 5000);
}
</script>
```

---

## User Experience Flow

### Scenario: User deposits 0.10 USDT

**Timeline:**

| Time | What Happens | User Sees |
|------|--------------|-----------|
| 0 sec | User sends USDT from MetaMask | Loading... |
| 3 sec | TX confirmed on BSC | "Pending 0/15 confirmations" |
| 30 sec | 5+ block confirmations | "Pending 5/15 confirmations" |
| ~60 sec | 15+ block confirmations (fully confirmed) | **⚠️ PENDING CONFIRMATION alert appears** |
| 60 sec + | User clicks "Credit Now" | ✅ **0.10 USDT appears in wallet history** |
| 300 sec (5 min) | DepositListener cron runs (backup) | Still shows as credited (no duplicate) |

**Key improvement:** User doesn't need to wait for cron — they can credit deposits immediately after 15 blocks (~60 seconds).

---

## API Reference

### Instant Credit Deposits Endpoint

**Route:** `POST /user/instant-credit-deposits`  
**Auth:** User session required  
**Method:** AJAX POST

**Response (Success):**
```json
{
  "success": true,
  "message": "✓ 1 deposits credited",
  "credited_count": 1,
  "credited_amount": 0.1,
  "new_balance_usdt": 0.15,
  "tx_hashes": ["0xabc123..."]
}
```

**Response (Error):**
```json
{
  "success": false,
  "message": "Credit failed: ...",
  "error": "Detailed error message"
}
```

---

## Wallet Display States

### State 1: No Deposits
```
[No deposits yet. Send USDT to your deposit address above.]
```

### State 2: Pending Confirmation (15+ blocks confirmed on-chain)
```
⚠️ 1 Pending Deposits Confirmed On-Chain
Your deposits are confirmed on the blockchain but waiting to be credited.
[Credit Now Button]

HISTORY:
Date         | Amount   | Status                    | TX Hash    | Confirmations | Action
2026-02-26   | 0.10 USD | ⏳ Pending Confirmation  | 0xabc123   | 15 blocks    | [Credit]
```

### State 3: After Credit Click
```
✅ 1 deposits credited
New USDT balance: 0.10
[Page reloads]

HISTORY:
Date         | Amount   | Status          | TX Hash    | Confirmations
2026-02-26   | 0.10 USD | ✓ Credited      | 0xabc123   | 15 blocks
```

---

## FAQ

**Q: What if the user's deposit hasn't reached 15 confirmations yet?**  
A: It won't appear in the pending deposits list. Once it hits 15 confirmations, it will show with "PENDING CONFIRMATION" status.

**Q: What if they click "Credit" twice?**  
A: Safe! The `wallet_deposits` table uses `tx_hash` as a unique key, so duplicate credits are prevented.

**Q: Does this replace the DepositListener cron?**  
A: No! The cron still runs as a backup. This gives users instant control — they can credit deposits manually without waiting for the cron to run.

**Q: Why 15 block confirmations?**  
A: This is configured in `Token Settings.minimum_confirmations`. On BSC, 15 blocks = ~45 seconds = cryptographically secure.

---

## Testing

### Test Scenario: Missing 0.10 USDT

**Steps:**
1. User deposits 0.10 USDT from MetaMask
2. Wait ~60 seconds (for 15+ block confirmations)
3. User navigates to `/user/wallet`
4. Should see: **"1 Pending Deposits Confirmed On-Chain"** alert
5. Click **"Credit Now"** button
6. Should see: **"✓ 1 deposits credited"** success message
7. Wallet history table should show: **0.10 USD | ✓ Credited**
8. Balance should update: **0.10 USDT**

**Before this fix:**
- ❌ Wallet showed 0 USDT
- ❌ No pending deposits visible
- ❌ Had to wait 5+ minutes for cron

**After this fix:**
- ✅ Pending deposits visible immediately
- ✅ One-click credit button
- ✅ Balance updates instantly

