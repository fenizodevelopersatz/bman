# Wallet & Staking Reset - Quick Start Guide

**Version:** 1.0  
**Created:** 2026-07-10

## What's Ready

Three tools for resetting wallet balances and staking records:

| Method | Location | Access |
|--------|----------|--------|
| **Admin UI** (Recommended) | `admin/staking/walletreset` | Web Browser |
| **SQL Procedures** | `db/reset_staking_wallets.sql` | MySQL |
| **Documentation** | `docs/01_STAKING_WALLET_RESET_SETUP.md` | Read File |

---

## Installation (5 minutes)

### Step 1: Load SQL Procedures
```bash
# Via command line:
mysql -u root -p nexman < db/reset_staking_wallets.sql

# OR via phpMyAdmin:
1. Go to database > SQL tab
2. Paste contents of db/reset_staking_wallets.sql
3. Click Execute
```

### Step 2: Verify Files
Check these files exist:
```
✓ application/controllers/admin/staking/Walletreset.php
✓ application/views/admin/staking/wallet_reset.php
✓ db/reset_staking_wallets.sql
✓ docs/01_STAKING_WALLET_RESET_SETUP.md
```

### Step 3: Access Admin Panel
1. Log in to admin dashboard
2. Go to: **Staking > Wallet Reset**
   - Or: `http://your-domain/admin/staking/walletreset`
3. Should see stats dashboard

---

## Common Use Cases

### Reset User After Failed Transaction
```
1. Go to Admin Panel
2. Enter User ID
3. ✓ Check "I understand this will DELETE..."
4. Click "Reset User"
```

### Clear Test Data (Last 7 Days)
```
1. Go to Admin Panel
2. Set "Days to Clear" to: 7
3. ✓ Check confirmation
4. Click "Delete Recent"
```

### Manually Complete Stuck Order
```
1. Go to Admin Panel
2. Enter Order ID
3. Click "Mark Completed"
```

### Check User Balance Before Reset
```
1. Go to Admin Panel
2. Enter User ID
3. Click "Check Wallet"
4. View all balances
```

---

## What Gets Reset

### When Resetting a User:
- ✓ DELETE all staking swap orders
- ✓ Reset ceiling wallet holds to 0
- ✓ Clear staking wallet ledger entries
- ✓ Set all balances to 0 (exchange, earning, staking, bonus, USD)

### When Resetting Recent (Last N Days):
- ✓ DELETE staking orders from last N days
- ✓ Remove related ceiling wallet entries
- ⚠️ Keeps user wallet balances (only removes orders)

### When Marking as Completed:
- ✓ Change order status to `completed`
- ⚠️ Keep full history (no deletion)

---

## Safety Checklist

**BEFORE any reset:**

- [ ] **Backup your database**
  ```bash
  mysqldump -u root -p nexman > backup_2026_07_10.sql
  ```

- [ ] **Review the data**
  - Use "Check User Wallets" to verify
  - Look at "Recent Staking Activity" table

- [ ] **Confirm what you're resetting**
  - Check the user ID or date range
  - Verify affected wallets

- [ ] **All actions are logged**
  - Admin ID: recorded
  - User ID: recorded
  - Timestamp: recorded
  - Action details: recorded

---

## API Reference (Admin Only)

All endpoints require AJAX + authentication

### Get Recent Activity
```
GET /admin/staking/walletreset/get_activity?limit=50
```

### Get User Wallets
```
GET /admin/staking/walletreset/get_user_wallets?user_id=123
```

### Reset User
```
POST /admin/staking/walletreset/reset_user
Body: { user_id: 123, confirm: "yes" }
```

### Reset Recent
```
POST /admin/staking/walletreset/reset_recent
Body: { days: 7, confirm: "yes" }
```

### Mark Completed
```
POST /admin/staking/walletreset/mark_completed
Body: { order_id: 42 }
```

---

## Database Tables

| Table | Purpose | Reset Action |
|-------|---------|--------------|
| `staking_swap_orders` | Staking orders | DELETE |
| `user_wallets` | Balance cache | SET to 0 |
| `wallet_ledger` | Transaction history | DELETE staking entries |
| `ceiling_wallet` | Ceiling holds | SET to 0 |
| `ceiling_wallet_ledger` | Ceiling history | DELETE |

---

## Troubleshooting

**Q: Procedure not found error**  
A: Run: `mysql -u root -p nexman < db/reset_staking_wallets.sql`

**Q: Permission denied**  
A: User needs Super Admin (admin_roll='1') + staking_management permission

**Q: Balances still show after reset**  
A: `user_wallets` is cache. `wallet_ledger` is source of truth. Restore from backup if needed.

**Q: Can't see Activity table**  
A: Click "Refresh" button in the UI

---

## Files Created

```
✓ application/controllers/admin/staking/Walletreset.php     (312 lines)
✓ application/views/admin/staking/wallet_reset.php          (465 lines)
✓ db/reset_staking_wallets.sql                              (SQL procedures)
✓ docs/01_STAKING_WALLET_RESET_SETUP.md                     (Full guide)
✓ docs/WALLET_RESET_QUICK_START.md                          (This file)
```

---

## Next Steps

1. **Run SQL procedures** (Step 1 above)
2. **Access admin panel** at `admin/staking/walletreset`
3. **Backup database** before any reset
4. **Test on a user** with staking records
5. **Verify results** using "Check User Wallets"

---

## Support

See `docs/01_STAKING_WALLET_RESET_SETUP.md` for:
- Detailed installation
- Complete usage examples
- All database operations
- Logging & audit trails
- Complete troubleshooting

---

**Ready to use!** Access the admin panel at: `http://your-domain/admin/staking/walletreset`
