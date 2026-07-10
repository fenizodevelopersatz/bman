# Staking & Wallet Reset System

**Created:** 2026-07-10  
**Status:** Ready for Integration

## What's Included

This package provides **three methods** to reset wallet balances and staking records:

### 1. Admin UI Panel (Recommended) ⭐

**Location:** `admin/staking/walletreset`  
**Files:**
- `application/controllers/admin/staking/Walletreset.php` - Controller
- `application/views/admin/staking/wallet_reset.php` - View

**Features:**
- ✅ Web-based dashboard
- ✅ Safe confirmation prompts
- ✅ Real-time activity monitoring
- ✅ Automatic logging of all actions
- ✅ User-friendly interface

**Access Requirements:**
- Super Admin (admin_roll = '1')
- Permission: `staking_management` OR `wallet_management`

### 2. SQL Procedures

**Location:** `db/reset_staking_wallets.sql`  
**Procedures:**
- `reset_user_staking(user_id)` - Reset all staking for one user
- `reset_recent_staking(days)` - Clear orders from last N days
- `mark_staking_complete(order_id)` - Mark order as complete

**Access:** Direct MySQL access

### 3. Quick Reference

**View Recent Staking:**
```sql
SELECT o.id, o.ref, u.username, o.created_at, o.status, 
       o.usdt_amount, o.bman_amount, o.error
FROM staking_swap_orders o
JOIN users u ON u.id = o.user_id
ORDER BY o.created_at DESC LIMIT 50;
```

**View Wallet Balances:**
```sql
SELECT u.id, u.username, w.exchange_balance, w.earning_balance,
       w.staking_balance, w.bonus_balance, w.usd_balance
FROM users u
JOIN user_wallets w ON w.user_id = u.id
ORDER BY u.id DESC LIMIT 50;
```

---

## Installation

### Step 1: Create Database Procedures

Run the SQL script to create the reset procedures:

```bash
mysql -u root -p nexman < db/reset_staking_wallets.sql
```

Or via phpMyAdmin:
1. Go to your database > SQL tab
2. Copy-paste contents of `db/reset_staking_wallets.sql`
3. Click Execute

### Step 2: Create Admin Controller

The controller is already created at:
```
application/controllers/admin/staking/Walletreset.php
```

### Step 3: Create Admin View

The view is already created at:
```
application/views/admin/staking/wallet_reset.php
```

### Step 4: Create Menu Item (Optional)

Add this to your admin menu navigation:
```html
<!-- In your admin menu navigation -->
<li class="nav-item">
  <a class="nav-link" href="<?php echo base_url('admin/staking/walletreset'); ?>">
    <i class="fas fa-wallet"></i> Wallet Reset
  </a>
</li>
```

Or add under Staking menu if you have a dropdown.

### Step 5: Verify Installation

1. Log in to Admin Dashboard
2. Navigate to **Staking > Wallet Reset**
3. Should see dashboard with stats

---

## Usage Examples

### Example 1: Reset User After Failed Transaction

**Scenario:** User completed staking purchase but something went wrong

**Steps:**
1. Go to `admin/staking/walletreset`
2. Scroll to "Reset Single User"
3. Enter User ID: 123
4. ✓ Check "I understand this will DELETE..."
5. Click "Reset User"
6. Confirm deletion

**Result:** All staking records deleted, balances set to 0

---

### Example 2: Clear Test Data

**Scenario:** You ran 50 test staking transactions

**Steps:**
1. Go to `admin/staking/walletreset`
2. Scroll to "Reset Recent Staking"
3. Set "Days to Clear" to: 1
4. ✓ Check confirmation
5. Click "Delete Recent"

**Result:** All orders from last 24 hours deleted (balances preserved)

---

### Example 3: Manually Complete Stuck Order

**Scenario:** Order ID 42 is stuck in "failed_usdt" status

**Steps:**
1. Go to `admin/staking/walletreset`
2. Scroll to "Mark Order as Completed"
3. Enter Order ID: 42
4. Click "Mark Completed"

**Result:** Order status changed to `completed` (history preserved)

---

### Example 4: Audit User Wallet

**Scenario:** Check balance before resetting

**Steps:**
1. Go to `admin/staking/walletreset`
2. Scroll to "Check User Wallets"
3. Enter User ID: 123
4. Click "Check Wallet"
5. View all balances:
   - Exchange balance
   - Earning balance
   - Staking balance
   - Bonus balance
   - USD balance
   - Number of staking orders
   - Ceiling holds

**Result:** See detailed wallet state

---

## Database Tables Affected

### staking_swap_orders
Tracks USDT ↔ BMAN swap orders
- Columns: `id, user_id, usdt_amount, bman_amount, status, created_at, error`
- Reset action: DELETE matching records

### user_wallets
Per-user balance summary (cache)
- Columns: `exchange_balance, earning_balance, staking_balance, bonus_balance, usd_balance`
- Reset action: Set all to 0

### wallet_ledger
Double-entry ledger (source of truth)
- Columns: `user_id, wallet_type, credit, debit, balance_after, reference_type`
- Reset action: DELETE matching staking entries

### ceiling_wallet
System ceiling holds (backend only)
- Columns: `held_balance, total_held, total_released`
- Reset action: Set all to 0

### ceiling_wallet_ledger
Ledger of ceiling transactions
- Columns: `user_id, tx_type, amount, held_after`
- Reset action: DELETE matching entries

---

## Safety Checklist

Before performing ANY reset operation:

- [ ] **Backup Database**
  ```bash
  mysqldump -u root -p nexman > backup_$(date +%Y%m%d_%H%M%S).sql
  ```

- [ ] **Review Activity**
  - Use "Check User Wallets" to verify data
  - Review "Recent Staking Activity" table
  - Check for related transactions

- [ ] **Confirm Intent**
  - Second-check the user ID or date range
  - Verify which wallets will be affected

- [ ] **Log The Action**
  - All admin UI actions are auto-logged
  - Note reason in admin comments/notes
  - Keep timestamped backup

- [ ] **Test (if new)**
  - Try on a test user first
  - Verify balances are reset correctly
  - Check that order is removed/marked

---

## Troubleshooting

### Issue: Procedure Not Found
**Error:** `PROCEDURE reset_user_staking does not exist`

**Solution:**
```bash
mysql -u root -p nexman < db/reset_staking_wallets.sql
```

### Issue: User Balances Still Show
**Cause:** `user_wallets` is cache; `wallet_ledger` is truth

**Solution:** If ledger was cleared, recalculate from ledger or restore from backup

### Issue: Can't See Recent Activity
**Solution:** Click "Refresh" button in the UI

### Issue: Permission Denied
**Cause:** User is not Super Admin or lacks `staking_management`/`wallet_management` permission

**Solution:** Check user's `admin_roll` (must be '1') and `permission_pages` JSON

---

## API Endpoints (Admin Only)

All endpoints require AJAX request + admin authentication

### GET /admin/staking/walletreset/get_activity
**Params:** `limit=50` (max 500)

**Response:**
```json
{
  "status": "success",
  "data": [...order objects...],
  "count": 50
}
```

### GET /admin/staking/walletreset/get_user_wallets
**Params:** `user_id=123`

**Response:**
```json
{
  "status": "success",
  "user": {...},
  "wallet": {...},
  "staking_orders": 5,
  "ceiling": {...}
}
```

### POST /admin/staking/walletreset/reset_user
**Body:**
```json
{
  "user_id": 123,
  "confirm": "yes"
}
```

### POST /admin/staking/walletreset/reset_recent
**Body:**
```json
{
  "days": 7,
  "confirm": "yes"
}
```

### POST /admin/staking/walletreset/mark_completed
**Body:**
```json
{
  "order_id": 42
}
```

---

## Logging & Audit Trail

All operations via Admin UI are logged to `admin_logs` table:

**Columns:**
- `admin_id` - Who performed the action
- `action` - `WALLET_RESET_USER`, `WALLET_RESET_RECENT`, `WALLET_MARK_COMPLETED`
- `target_id` - User ID affected
- `details` - Description (e.g., "Deleted 5 orders...")
- `ip_address` - Admin's IP
- `created_at` - Timestamp

**Query audit trail:**
```sql
SELECT admin_id, action, target_id, details, created_at
FROM admin_logs
WHERE action LIKE 'WALLET_%'
ORDER BY created_at DESC
LIMIT 50;
```

---

## Support & Questions

If you need help:

1. **Check the logs**
   ```sql
   SELECT * FROM admin_logs WHERE action LIKE 'WALLET_%' ORDER BY created_at DESC LIMIT 20;
   ```

2. **Verify data state**
   ```sql
   -- For user 123
   SELECT * FROM staking_swap_orders WHERE user_id = 123;
   SELECT * FROM user_wallets WHERE user_id = 123;
   ```

3. **Review backups**
   - Locate database backup file (if made before reset)
   - Can restore if needed

4. **Contact Development**
   - User ID affected
   - Action taken (user reset vs. recent reset)
   - Expected vs. actual result
   - Screenshot of error (if any)

---

**Last Updated:** 2026-07-10  
**Version:** 1.0  
**Status:** Production Ready
