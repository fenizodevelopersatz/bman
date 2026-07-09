# Wallet Sync — Frontend Integration Guide

## Overview

The new `Wallet_sync` controller provides real-time on-chain balance checking and instant deposit detection. Users can now:

1. **Check On-Chain Balance** — Compare blockchain vs. database balances instantly
2. **Manually Scan Deposits** — Trigger deposit detection without waiting for cron
3. **View Wallet History** — See transaction history with on-chain confirmation status

## API Endpoints

### 1. Check On-Chain Balance

**Endpoint:** `/user/wallet/check-balance`  
**Method:** GET (AJAX)  
**Auth:** User session required

**Response:**
```json
{
  "success": true,
  "data": {
    "on_chain": {
      "usdt": "150.5000",
      "bnb": "0.25",
      "bman": "1500.0000"
    },
    "database": {
      "usdt": "100.5000",
      "exchange": "0",
      "earning": "500",
      "staking": "1000",
      "bonus": "0"
    },
    "wallet_address": "0x742d35Cc6634C0532925a3b844Bc9e7595f0bEb",
    "synced": false,
    "pending_deposits": [
      {
        "tx_hash": "0xabc...",
        "amount": "50.0000",
        "blocks_until_confirmed": 0,
        "confirmation_count": 15,
        "status": "confirmed"
      }
    ],
    "last_sync": "2026-02-26 14:30:45",
    "sync_now_available": true
  }
}
```

**Usage (jQuery):**
```javascript
$.get('/user/wallet/check-balance', function(response) {
  if (response.success) {
    console.log('On-chain USDT:', response.data.on_chain.usdt);
    console.log('DB USDT:', response.data.database.usdt);
    console.log('Synced:', response.data.synced);
    
    if (!response.data.synced) {
      alert('Balance mismatch! You may have pending deposits.');
      // Show pending deposits UI
    }
  }
});
```

---

### 2. Manually Scan for Deposits

**Endpoint:** `/user/wallet/scan-deposits`  
**Method:** POST (AJAX)  
**Auth:** User session required

**Response:**
```json
{
  "success": true,
  "data": {
    "status": "success",
    "message": "Found 2 confirmed deposits, credited 2",
    "deposits_found": 2,
    "deposits_credited": 2,
    "new_balance_usdt": "150.5000",
    "new_balance_bman": "1500.0000",
    "tx_hashes": ["0xabc...", "0xdef..."]
  }
}
```

**Usage (jQuery):**
```javascript
$.post('/user/wallet/scan-deposits', {}, function(response) {
  if (response.success) {
    console.log('Deposits found:', response.data.deposits_found);
    console.log('Deposits credited:', response.data.deposits_credited);
    console.log('New balance:', response.data.new_balance_usdt);
    
    // Refresh wallet display
    location.reload(); // or update UI dynamically
  }
});
```

---

### 3. Get Wallet History with Sync Status

**Endpoint:** `/user/wallet/history-json`  
**Method:** GET (AJAX)

**Query Params:**
- `page` (int) — Page number (default: 1)
- `limit` (int) — Records per page (default: 20)
- `type` (string) — Filter: credit, debit, deposit, transfer, etc.
- `status` (string) — Filter: pending, success, failed
- `from` (date) — Date range start (YYYY-MM-DD)
- `to` (date) — Date range end (YYYY-MM-DD)

**Response:**
```json
{
  "success": true,
  "data": {
    "history": [
      {
        "id": 123,
        "user_id": 1,
        "wallet": "usdt",
        "amount": "50.0000",
        "reference_type": "deposit",
        "reference_id": "0xabc123...",
        "status": "success",
        "created_at": "2026-02-26 14:35:00",
        "block_number": 35000000,
        "confirmation_count": 15,
        "onchain_status": "confirmed",
        "gas_fee": "0.00123"
      }
    ],
    "balance_summary": {
      "usdt": "150.5000",
      "exchange": "0",
      "earning": "500",
      "staking": "1000",
      "bonus": "0"
    },
    "wallet_synced": true,
    "last_sync": "2026-02-26 14:35:00",
    "page": 1,
    "limit": 20
  }
}
```

**Usage (jQuery):**
```javascript
$.get('/user/wallet/history-json?type=deposit&status=success&limit=10', function(response) {
  if (response.success) {
    response.data.history.forEach(tx => {
      console.log(tx.reference_id, tx.amount, tx.onchain_status);
    });
  }
});
```

---

## Frontend UI Components

### Button: "Check On-Chain Balance"

Add to wallet page (e.g., next to balance cards):

```html
<button id="checkBalanceBtn" class="btn btn-sm btn-info">
  <i class="ki-duotone ki-arrows-circular">
    <span class="path1"></span>
    <span class="path2"></span>
  </i>
  Check On-Chain Balance
</button>

<div id="balanceCheckResult" style="display:none;" class="alert mt-3">
  <!-- Results shown here -->
</div>

<script>
$('#checkBalanceBtn').click(function() {
  $(this).disabled = true;
  $.get('/user/wallet/check-balance', function(response) {
    if (response.success) {
      const data = response.data;
      const html = `
        <div class="row">
          <div class="col-md-6">
            <h6>On-Chain Balance</h6>
            <p>USDT: <strong>${data.on_chain.usdt}</strong></p>
            <p>BMAN: <strong>${data.on_chain.bman}</strong></p>
            <p>BNB: <strong>${data.on_chain.bnb}</strong></p>
          </div>
          <div class="col-md-6">
            <h6>Database Balance</h6>
            <p>USDT: <strong>${data.database.usdt}</strong></p>
            <p>BMAN: <strong>${data.database.earning + data.database.staking + data.database.bonus}</strong></p>
          </div>
        </div>
        ${data.synced ? '<p class="text-success">✓ Balances synced</p>' : '<p class="text-warning">⚠ Mismatch detected</p>'}
        ${data.pending_deposits.length > 0 ? `<p class="text-info">${data.pending_deposits.length} pending deposits</p>` : ''}
      `;
      $('#balanceCheckResult').html(html).show();
    }
  }).always(function() {
    $('#checkBalanceBtn').disabled = false;
  });
});
</script>
```

---

### Modal: "Scan Deposits Now"

```html
<button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#scanDepositsModal">
  Scan for Deposits
</button>

<div class="modal fade" id="scanDepositsModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Scan for Deposits</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Check blockchain for any unconfirmed deposits to your wallet and credit them immediately.</p>
        <div id="scanProgress" style="display:none;">
          <div class="spinner-border" role="status">
            <span class="visually-hidden">Scanning...</span>
          </div>
          <p>Scanning blockchain...</p>
        </div>
        <div id="scanResult" style="display:none;">
          <!-- Results shown here -->
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" id="scanNowBtn" class="btn btn-primary">Scan Now</button>
      </div>
    </div>
  </div>
</div>

<script>
$('#scanNowBtn').click(function() {
  $('#scanProgress').show();
  $('#scanResult').hide();
  
  $.post('/user/wallet/scan-deposits', {}, function(response) {
    $('#scanProgress').hide();
    
    if (response.success) {
      const data = response.data;
      const html = `
        <div class="alert alert-success">
          <h6>${data.deposits_credited} deposits credited</h6>
          <p>New USDT balance: <strong>${data.new_balance_usdt}</strong></p>
          <p>New BMAN balance: <strong>${data.new_balance_bman}</strong></p>
          ${data.tx_hashes.length > 0 ? `
            <h6>Transaction hashes:</h6>
            <ul>
              ${data.tx_hashes.map(h => `<li><code>${h}</code></li>`).join('')}
            </ul>
          ` : ''}
        </div>
      `;
      $('#scanResult').html(html).show();
      
      // Auto-close modal after 3 seconds and reload
      setTimeout(function() {
        location.reload();
      }, 3000);
    } else {
      $('#scanResult').html(`<div class="alert alert-danger">${response.message}</div>`).show();
    }
  });
});
</script>
```

---

## Integration with Existing Wallet Page

### In `user/wallet/lendinghistory.php` or similar:

```php
<!-- Add sync controls -->
<div class="card mb-4">
  <div class="card-header">
    <h5 class="card-title">Wallet Status
      <span id="syncStatus" class="badge bg-info ms-2">
        Synced <i class="ki-duotone ki-check"></i>
      </span>
    </h5>
  </div>
  <div class="card-body">
    <div class="row mb-3">
      <div class="col-md-6">
        <p><strong>On-Chain USDT:</strong> <span id="onchainUsdt">Loading...</span></p>
        <p><strong>On-Chain BMAN:</strong> <span id="onchainBman">Loading...</span></p>
      </div>
      <div class="col-md-6">
        <p><strong>Database USDT:</strong> <span id="dbUsdt"><?= $wallet_usdt ?></span></p>
        <p><strong>Database BMAN:</strong> <span id="dbBman"><?= $wallet_bman['total'] ?? 0 ?></span></p>
      </div>
    </div>
    
    <button id="checkBalanceBtn" class="btn btn-info btn-sm me-2">
      Check On-Chain
    </button>
    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#scanDepositsModal">
      Scan for Deposits
    </button>
    <span id="lastSync" class="text-muted ms-2 small">
      Last sync: <?= $last_sync_time ?>
    </span>
  </div>
</div>

<!-- Existing wallet history grid follows -->
<div class="card">
  <div class="card-header">
    <h5 class="card-title">Wallet History</h5>
  </div>
  <div class="card-body">
    <!-- Existing history table with new columns: block_number, confirmation_count, onchain_status -->
    <table class="table table-striped">
      <thead>
        <tr>
          <th>Date</th>
          <th>Type</th>
          <th>Amount</th>
          <th>Status</th>
          <th>TX Hash</th>
          <th>Confirmations</th>
          <th>On-Chain Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($history as $tx) { ?>
          <tr>
            <td><?= $tx['created_at'] ?></td>
            <td><?= ucfirst($tx['reference_type']) ?></td>
            <td><?= $tx['amount'] ?></td>
            <td><?= ucfirst($tx['status']) ?></td>
            <td>
              <?php if ($tx['tx_hash']) { ?>
                <a href="https://bscscan.com/tx/<?= $tx['tx_hash'] ?>" target="_blank" class="text-truncate d-inline-block" style="max-width: 150px;">
                  <?= substr($tx['tx_hash'], 0, 10) ?>...
                </a>
              <?php } ?>
            </td>
            <td><?= $tx['confirmation_count'] ?? '-' ?></td>
            <td>
              <span class="badge bg-<?= $tx['onchain_status'] === 'confirmed' ? 'success' : 'warning' ?>">
                <?= ucfirst($tx['onchain_status'] ?? 'Pending') ?>
              </span>
            </td>
          </tr>
        <?php } ?>
      </tbody>
    </table>
  </div>
</div>

<script>
// Auto-load on-chain balance on page load
$(document).ready(function() {
  loadOnchainBalance();
});

// Check balance button click
$('#checkBalanceBtn').click(function() {
  $(this).prop('disabled', true);
  loadOnchainBalance();
  $(this).prop('disabled', false);
});

function loadOnchainBalance() {
  $.get('/user/wallet/check-balance', function(response) {
    if (response.success) {
      const data = response.data;
      $('#onchainUsdt').text(data.on_chain.usdt);
      $('#onchainBman').text(data.on_chain.bman);
      $('#dbUsdt').text(data.database.usdt);
      
      if (!data.synced) {
        $('#syncStatus').html('⚠ Out of Sync <i class="ki-duotone ki-warning"></i>').removeClass('bg-info').addClass('bg-warning');
      } else {
        $('#syncStatus').html('✓ Synced <i class="ki-duotone ki-check"></i>').removeClass('bg-warning').addClass('bg-success');
      }
      
      if (data.pending_deposits.length > 0) {
        $('#syncStatus').append(`<br><small>${data.pending_deposits.length} pending deposits</small>`);
      }
    }
  });
}
</script>
```

---

## User Experience Flow

### When User Deposits USDT:

1. **User sends USDT** → Transaction appears on blockchain (0-3 seconds on BSC)
2. **DepositCron runs** (every 5 min) → Detects & credits (15+ confirmations wait)
3. **User clicks "Check On-Chain"** → Sees deposit immediately (confirms it's on-chain)
4. **User clicks "Scan for Deposits"** → Manual trigger to credit (skip the 5-min wait)
5. **Wallet history updates** → Shows new deposit with tx_hash and confirmation count

### Benefits:

- ✅ **Instant visibility**: User sees their deposit on-chain immediately
- ✅ **No waiting**: Don't need to wait for cron to run
- ✅ **Real-time status**: See blockchain confirmations in real-time
- ✅ **Confidence**: User knows transaction is confirmed

---

## Troubleshooting

**"On-Chain balance shows ?, Database shows 0"**
- RPC is unreachable or Web3bman decryption failed
- Check `Token Settings` configuration
- Check application logs for errors

**"On-Chain shows deposit, Database doesn't"**
- Deposit hasn't reached minimum confirmations yet (15 by default)
- Click "Scan for Deposits" to force scan
- Or wait for next DepositCron run

**"Balances don't match"**
- This is normal immediately after deposit
- On-chain updates are live
- Database updates after 15 confirmations + cron run
- Click "Check On-Chain" to see latest status

