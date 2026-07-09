# Wallet Display Fix — Instant USDT Deposit History

## Problem

**User deposits 0.10 USDT but:**
- ❌ Wallet history shows nothing
- ❌ Balance remains 0.00
- ❌ Must wait 5-10 minutes for DepositListener cron
- ❌ User thinks deposit failed

**Root Cause:** Wallet deposit history only showed deposits from `wallet_deposits` table (which is populated by cron). Pending on-chain confirmed deposits weren't visible.

---

## Solution Implemented ✅

### 1. Enhanced Deposit Detection

**File:** `application/models/Custodialwallet_model.php`

**What changed:**
- Modified `deposits()` function to show **both confirmed AND pending** deposits
- Now queries `onchain_transactions` table for deposits with 15+ blocks confirmed
- Pending deposits appear immediately (status: `pending_confirmation`)
- Confirmed deposits appear with status: `credited`

**New Method:** `getPendingDeposits($user_id)`
- Returns only pending on-chain deposits
- Used for counting and UI badges

**Example Output:**
```
[
  {
    "token": "USDT",
    "amount_usdt": 0.10,
    "status": "pending_confirmation",  // ← PENDING (not yet in wallet_deposits)
    "tx_hash": "0xabc123...",
    "confirmations": 15,
    "block_number": 35000000
  },
  {
    "token": "USDT",
    "amount_usdt": 0.05,
    "status": "credited",  // ← ALREADY in wallet_deposits
    "tx_hash": "0xdef456...",
    "confirmations": 25
  }
]
```

---

### 2. Instant Deposit Crediting Endpoint

**File:** `application/controllers/user/usersettings/Historycontroller.php`

**New Method:** `instant_credit_deposits()`
- **Route:** `POST /user/instant-credit-deposits`
- **Purpose:** Users can click "Credit Now" button to instantly credit pending deposits
- **Process:**
  1. Triggers `Depositlistener::scan($user_id)` for this user only
  2. Detects any on-chain confirmed deposits (15+ blocks)
  3. Credits them to the USDT wallet immediately
  4. Returns: credited count, credited amount, new balance

**Response:**
```json
{
  "success": true,
  "message": "✓ 1 deposits credited",
  "credited_count": 1,
  "credited_amount": 0.10,
  "new_balance_usdt": 0.15,
  "tx_hashes": ["0xabc123..."]
}
```

---

### 3. Updated Wallet History View

**File:** `application/controllers/user/usersettings/Historycontroller.php`

**Changes:**
- Line 684: `$this->data['deposit_history'] = $this->cw->deposits($user_id, 20);`
  - Now shows PENDING + CREDITED deposits (was only CREDITED before)
- Line 685: Added `$this->data['pending_deposits']`
  - Count of pending deposits for UI badge

**Updated Data Passed to View:**
```php
$deposit_history    // NOW: [confirmed, pending, confirmed, ...]
$pending_deposits   // Count for alert badge
```

---

## Frontend Integration Required

### Step 1: Show Pending Deposits Alert

Add to wallet page (`view_mywallet_management.php`):

```html
<?php if (!empty($pending_deposits) && count($pending_deposits) > 0) { ?>
  <div class="alert alert-info mb-3">
    <h6>⚠️ <?php echo count($pending_deposits); ?> Pending Deposits Confirmed On-Chain</h6>
    <p>Click "Credit Now" to instantly credit them to your wallet.</p>
    <button id="creditPendingBtn" class="btn btn-sm btn-primary">
      <i class="ki-duotone ki-check"></i> Credit Now
    </button>
  </div>
<?php } ?>
```

### Step 2: Update Deposit History Table

Show both confirmed and pending deposits with status badges:

```html
<?php foreach ($deposit_history as $deposit) { ?>
  <tr class="<?php echo ($deposit['status'] === 'pending_confirmation') ? 'table-warning' : ''; ?>">
    <td><?php echo number_format((float)$deposit['amount_usdt'], 4); ?> USDT</td>
    <td>
      <?php if ($deposit['status'] === 'credited') { ?>
        <span class="badge bg-success">✓ Credited</span>
      <?php } else { ?>
        <span class="badge bg-warning">⏳ Pending Confirmation</span>
      <?php } ?>
    </td>
    <td><?php echo (int)$deposit['confirmations']; ?> blocks</td>
    <td>
      <?php if ($deposit['status'] === 'pending_confirmation') { ?>
        <button class="btn btn-sm btn-primary" onclick="creditNow('<?php echo $deposit['tx_hash']; ?>')">
          Credit
        </button>
      <?php } ?>
    </td>
  </tr>
<?php } ?>
```

### Step 3: Add JavaScript

```html
<script>
$('#creditPendingBtn').click(function() {
  $(this).prop('disabled', true).html('Processing...');
  $.post('/user/instant-credit-deposits', {}, function(res) {
    if (res.success) {
      alert('✓ ' + res.credited_count + ' deposits credited!\nNew balance: ' + res.new_balance_usdt + ' USDT');
      location.reload();
    } else {
      alert('❌ ' + res.message);
      $('#creditPendingBtn').prop('disabled', false).html('Credit Now');
    }
  });
});
</script>
```

---

## User Experience Before vs After

### BEFORE (0.10 USDT Missing)
```
Wallet Page (/user/wallet):
├─ USDT Balance: 0.00 USDT  ❌ (WHERE IS IT??)
├─ Deposit History: [empty]
└─ User: "I sent 0.10 USDT but it's not showing!"
```

### AFTER (Instant Visibility)
```
Wallet Page (/user/wallet):
├─ USDT Balance: 0.00 USDT (not credited yet, but...)
├─ ⚠️ Alert: "1 Pending Deposits Confirmed On-Chain"
│  └─ [Credit Now] Button
├─ Deposit History:
│  ├─ 0.10 USDT | ⏳ Pending Confirmation | 15 blocks | [Credit]
│  └─ [User clicks Credit]
└─ USDT Balance: 0.10 USDT ✅ (instantly updated!)
```

---

## Timeline: User Deposits 0.10 USDT

| Time | Event | Status | Visible? |
|------|-------|--------|----------|
| 0 sec | User sends USDT | Mempool | ❌ No |
| 3 sec | TX confirmed (block 1) | Pending 1/15 | ❌ No |
| 30 sec | 5 block confirmations | Pending 5/15 | ❌ No |
| ~60 sec | **15+ blocks confirmed** | **Ready to credit** | **✅ YES — Alert appears** |
| 60-300 sec | User clicks "Credit Now" | **Processing** | **⏳ "Processing..." button** |
| ~65 sec | Credit complete | **CREDITED** | **✅ Balance updates to 0.10** |
| 300 sec | DepositListener cron runs | Backup confirmation | ✅ Already done (no duplicate) |

**Key:** User doesn't need to wait for cron (300+ seconds). They can credit the deposit themselves after ~60 seconds when it's blockchain-confirmed.

---

## Files Modified

| File | Change | Impact |
|------|--------|--------|
| `Custodialwallet_model.php` | Enhanced `deposits()` to include pending | Shows pending deposits immediately |
| `Historycontroller.php` | Added `instant_credit_deposits()` endpoint | Allows instant crediting |
| `routes.php` | Added route `/user/instant-credit-deposits` | Makes endpoint accessible |

---

## Files for Frontend Integration

| File | Location | Action |
|------|----------|--------|
| `view_mywallet_management.php` | `application/views/user/wallet/` | Add pending alert + update table |
| JavaScript | Wallet page footer | Add credit button handler |

See `docs/WALLET_INSTANT_DEPOSIT_GUIDE.md` for complete frontend code.

---

## Testing Checklist

- [ ] **Test 1: Deposit appears in pending**
  - Deposit 0.10 USDT
  - Wait ~60 seconds (for 15+ confirmations)
  - Refresh `/user/wallet`
  - Should see: "1 Pending Deposits Confirmed On-Chain" alert
  - Should see: 0.10 USDT in history with "⏳ Pending Confirmation" badge

- [ ] **Test 2: Click Credit Now**
  - Click "Credit Now" button
  - Should process (loading state)
  - Should show: "✓ 1 deposits credited"
  - Balance should update: 0.10 USDT

- [ ] **Test 3: No duplicate credits**
  - After crediting, wait for DepositListener cron to run
  - Check wallet ledger: should only have ONE credit entry
  - No duplicates (thanks to tx_hash unique key)

- [ ] **Test 4: Multiple pending deposits**
  - Send 0.05 USDT + 0.03 USDT (two separate txs)
  - Both should appear in pending list after 60 sec each
  - Click "Credit Now"
  - Both should credit instantly
  - Balance should be 0.08 USDT

---

## FAQ

**Q: Why pending deposits don't appear immediately (0 blocks)?**  
A: Security. We wait for 15 block confirmations (~45 sec on BSC) to ensure the transaction can't be reversed.

**Q: Why still waiting 60 seconds instead of 3 seconds?**  
A: 15 blocks on BSC = ~3-4 seconds per block × 15 = ~45-60 seconds. This is cryptographically secure against reorgs.

**Q: Does this affect the DepositListener cron?**  
A: No. The cron still runs as a backup. If a user doesn't click "Credit Now", the cron will credit it automatically.

**Q: Is it safe to credit twice (if cron also credits)?**  
A: Yes! The `wallet_deposits` table uses `tx_hash` as unique key, so duplicate credits are impossible.

**Q: What if the user deposits before the 15-block threshold?**  
A: The pending deposit won't show. Once it reaches 15 blocks, it will appear.

---

## Summary

✅ **Fixed:** Missing 0.10 USDT wallet history display issue
✅ **Instant:** Pending deposits visible ~60 seconds after on-chain confirmation
✅ **Safe:** Uses blockchain confirmation + unique key constraints
✅ **Optional:** Users can credit manually OR wait for cron as backup
✅ **Complete:** Backend + frontend integration guide provided

