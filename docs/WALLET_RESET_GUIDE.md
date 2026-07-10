# Wallet & Staking Reset Guide

## Overview

This guide explains how to reset wallet balances and staking records in the Nexman platform.

### Three Methods Available:

1. **Admin UI** (Recommended) - Safe, logged, and user-friendly
2. **SQL Procedures** - For direct database access
3. **SQL Scripts** - For bulk operations

## Method 1: Admin UI (Recommended)

### Access
Navigate to: `http://your-domain/admin/staking/walletreset`

### Reset Single User
- Enter User ID
- Check "I understand this will DELETE..."
- Click "Reset User"

**What it does:**
- Deletes all staking swap orders for the user
- Resets ceiling wallet holds to 0
- Clears wallet ledger entries
- Sets all wallet balances to 0

### Reset Recent Staking
- Enter Days to Clear (default: 7)
- Check confirmation
- Click "Delete Recent"

**What it does:**
- Deletes orders from the last N days
- Preserves user wallet balances

### Mark Order as Completed
- Enter Order ID
- Click "Mark Completed"
- Does NOT delete (keeps history)

### Check User Wallets
- Enter User ID
- View all balances and staking order count

## Database Tables Reference

- `staking_swap_orders` - Tracks USDT ↔ BMAN swaps
- `user_wallets` - Per-user balance summary
- `wallet_ledger` - Double-entry ledger
- `ceiling_wallet` - System ceiling holds
- `ceiling_wallet_ledger` - Ceiling ledger

## Safety & Best Practices

### Before Any Reset:
1. **Back up your database**
   ```bash
   mysqldump -u root -p nexman > backup_$(date +%Y%m%d_%H%M%S).sql
   ```

2. **Check what you're resetting**
   - Use "Check User Wallets" first
   - Review "Recent Staking Activity" table

3. **All operations are logged**
   - Admin ID who performed the action
   - Target user ID
   - Timestamp and action details

## Quick Reference

### SQL Procedures (if needed)
```sql
-- Reset all staking for user 123
CALL reset_user_staking(123);

-- Reset orders from last 7 days
CALL reset_recent_staking(7);

-- Mark order 1 as completed
CALL mark_staking_complete(1);
```

## Support

If you encounter issues:
1. Check `admin_logs` table for what was done
2. Review database backups
3. Contact support with affected user ID and action taken
