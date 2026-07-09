# ✅ Where Enrichment Is Called (Trigger Points)

**Status:** ✅ **AUTOMATIC ENRICHMENT ENABLED**  
**Date:** 2026-07-09

---

## 🔄 All Trigger Points for Transaction Enrichment

The `enrichAllRecentDeposits()` method is called automatically in **7 different places**:

---

## 1. **🤖 Automated Cron Route** (Background)

**Route:** `POST /credit-deposits-cron`  
**Controller:** `Depositcron.php`  
**When:** Every few minutes (configure in cron job)  
**Flow:**
```
Cron triggers → Depositcron::run()
    ↓
Calls Depositlistener::scan()
    ↓
Detects new deposits
    ↓
✓ Enriches with Etherscan data
    ↓
Credits confirmed deposits
```

**Log Output:**
```
[INFO] Cron executed
[INFO] [Depositlistener] Enriched 5 transaction(s) with Etherscan data
[INFO] Detected 5 new deposit(s), credited 5, enriched 5.
```

---

## 2. **⚙️ Admin Manual Trigger** (Admin Panel)

**Route:** `POST /admin/wallet-monitor` (Cronlab)  
**Controller:** `admin/wallet/Cronlab.php`  
**When:** Admin clicks "Run Scan Now" button  
**Flow:**
```
Admin clicks button → POST /cron-lab
    ↓
Calls Depositlistener::scan()
    ↓
Detects deposits for all users (or specific user)
    ↓
✓ Enriches with Etherscan data
    ↓
Credits confirmed deposits
```

**Response:**
```json
{
  "ok": true,
  "detected": 5,
  "credited": 5,
  "enriched": 5,
  "message": "Detected 5 new deposit(s), credited 5, enriched 5."
}
```

---

## 3. **👤 User: Credit Pending Deposits** (Wallet Page)

**Route:** `POST /user/instant-credit-deposits`  
**Controller:** `user/usersettings/Historycontroller::instant_credit_deposits()`  
**When:** User clicks "Credit Now" button on wallet page  
**Flow:**
```
User clicks "Credit Now"
    ↓
POST /user/instant-credit-deposits
    ↓
Calls Depositlistener::scan($user_id)
    ↓
Scans only THIS user's wallet
    ↓
Detects new deposits
    ↓
✓ Enriches with Etherscan data
    ↓
Credits confirmed deposits
```

**Response:**
```json
{
  "success": true,
  "message": "✓ 2 deposits credited",
  "credited_count": 2,
  "credited_amount": 0.20,
  "new_balance_usdt": 0.20,
  "enriched": 2
}
```

---

## 4. **🔍 User: Check On-Chain Balance** (NEW!)

**Route:** `GET /user/wallet-check-enrich`  
**Controller:** `user/usersettings/Historycontroller::wallet_check_enrich()`  
**When:** User clicks "Check On-chain Balance" button  
**Flow:**
```
User clicks "Check On-chain Balance"
    ↓
GET /user/wallet-check-enrich
    ↓
✓ Calls enrichAllRecentDeposits($user_id) DIRECTLY
    ↓
Queries Etherscan for all recent deposits
    ↓
Stores complete transaction data:
  ├─ from_address
  ├─ to_address
  ├─ gas_used
  ├─ timestamp
  ├─ block_number
  └─ status
    ↓
If balance mismatch: Fetch balance snapshots
    ├─ balance_before
    └─ balance_after
```

**Response:**
```json
{
  "success": true,
  "balance_match": false,
  "db_balance": 0.1,
  "rpc_balance": 0.2,
  "difference": 0.1,
  "enriched_count": 5,
  "updated_count": 2,
  "message": "✓ Enriched 5 transaction(s) with Etherscan data"
}
```

---

## 5. **📊 Admin Wallet Monitor Page**

**Route:** `GET /admin/wallet-monitor`  
**Controller:** `admin/wallet/Walletmonitor.php`  
**When:** Page loads or admin clicks refresh  
**Flow:**
```
Admin loads page
    ↓
Calls Depositlistener::scan()
    ↓
Detects deposits for all wallets
    ↓
✓ Enriches with Etherscan data
    ↓
Displays on dashboard
```

---

## 6. **💳 User Profile Page**

**Route:** `GET /member/profile/wallet_check` or similar  
**Controller:** `user/usersettings/Profile.php`  
**When:** User views profile/wallet section  
**Flow:**
```
User loads profile
    ↓
Calls Depositlistener::scan($uid)
    ↓
Detects deposits
    ↓
✓ Enriches with Etherscan data
    ↓
Displays wallet details
```

---

## 7. **🔄 Lending Controller**

**Route:** `GET /user/lending` or similar  
**Controller:** `user/usersettings/Lendingcontroller.php`  
**When:** User loads lending page  
**Flow:**
```
User loads lending page
    ↓
Calls Depositlistener::scan($userId)
    ↓
Detects deposits
    ↓
✓ Enriches with Etherscan data
    ↓
Updates wallet balance
```

---

## 📈 Complete Data Flow Diagram

```
                    ┌─────────────────────────┐
                    │  Deposit Detected via   │
                    │  Etherscan API (tokentx)│
                    └──────────┬──────────────┘
                               │
                    ┌──────────▼──────────┐
                    │ recordDeposit()     │
                    │ in wallet_deposits  │
                    └──────────┬──────────┘
                               │
                    ┌──────────▼──────────────────────┐
                    │ enrichAllRecentDeposits()       │
                    │ (NOW CALLED AUTOMATICALLY!)     │
                    │                                 │
                    │ ✓ Store in onchain_transactions:│
                    │  ├─ from_address               │
                    │  ├─ to_address                 │
                    │  ├─ value                      │
                    │  ├─ gas_used                   │
                    │  ├─ block_number               │
                    │  ├─ timestamp                  │
                    │  └─ status                     │
                    └──────────┬──────────────────────┘
                               │
                    ┌──────────▼──────────────────┐
                    │ If Balance Mismatch:        │
                    │ reconcileWithEtherscan()    │
                    │                             │
                    │ ✓ Fetch balance snapshots:  │
                    │  ├─ balance_before          │
                    │  └─ balance_after           │
                    └──────────┬──────────────────┘
                               │
                    ┌──────────▼─────────────────┐
                    │ Display in Wallet History  │
                    │ with complete details ✓    │
                    └────────────────────────────┘
```

---

## 🎯 Enrichment Happens When:

| Trigger | When | Where | Automatic? |
|---------|------|-------|-----------|
| **Cron Job** | Every N minutes | Depositcron controller | ✓ Auto |
| **Admin Button** | Admin clicks "Run Scan Now" | Cronlab controller | ✓ Click |
| **User Credit Button** | User clicks "Credit Now" | Historycontroller | ✓ Click |
| **Balance Check** | User clicks "Check Balance" | wallet_check_enrich | ✓ Click |
| **Admin Dashboard** | Page loads | Walletmonitor | ✓ Auto |
| **Profile Page** | Page loads | Profile controller | ✓ Auto |
| **Lending Page** | Page loads | Lendingcontroller | ✓ Auto |

---

## 📊 What Gets Enriched

### Each Time Enrichment Runs:

1. **Query Etherscan API:** `account/tokentx` endpoint
   - Gets all token transfers for each wallet
   - Filters for incoming transfers (to = wallet address)
   - Returns up to 50 recent transactions

2. **Store Complete Data:**
   - ✓ from_address (sender)
   - ✓ to_address (recipient)
   - ✓ value (amount in wei)
   - ✓ gas_used (gas consumed)
   - ✓ block_number (block height)
   - ✓ transaction_index (index in block)
   - ✓ status (1=success, 0=failed)
   - ✓ created_at (timestamp)

3. **If Balance Mismatch Detected:**
   - Fetch balance snapshots from RPC
   - Store balance_before (balance before TX)
   - Store balance_after (balance after TX)

---

## 🔍 Verify Enrichment is Working

### Check Logs

```bash
# View application logs:
tail -f application/logs/log-*.php

# You should see:
[INFO] [Depositlistener] Enriched 5 transaction(s) with Etherscan data
[INFO] Enriched TX: 0x123abc... (from: 0xdep..., to: 0xusr...)
[INFO] Enriched TX: 0x456def... (from: 0xdep..., to: 0xusr...)
```

### Check Database

```sql
-- After enrichment runs, check:
SELECT COUNT(*) as total,
       COUNT(from_address) as with_from,
       COUNT(to_address) as with_to
FROM onchain_transactions;

-- Should show:
-- total: 50 (up to 50 per wallet)
-- with_from: 50 ✓ (all populated)
-- with_to: 50 ✓ (all populated)
```

---

## 🚀 How to Trigger Enrichment Manually

### Option 1: User Wallet Page (Easiest)
```
1. Go to: http://192.168.29.18:9000/user/wallet
2. Click: "Check On-chain Balance" button
3. Wait: 2-3 seconds
4. ✓ Enrichment runs automatically!
```

### Option 2: Admin Panel
```
1. Go to: Admin → Wallet Management
2. Click: "Run Scan Now" or "Sync Deposits" button
3. ✓ Enriches all user wallets!
```

### Option 3: User Wallet
```
1. Go to: http://192.168.29.18:9000/user/wallet
2. Click: "Credit Now" button (if pending deposits)
3. ✓ Enriches during the credit process!
```

### Option 4: API Call (Test)
```bash
curl -X POST "http://192.168.29.18:9000/credit-deposits-cron" \
  -H "X-Requested-With: XMLHttpRequest"

# Response will show:
# {
#   "ok": true,
#   "detected": 5,
#   "credited": 5,
#   "enriched": 5
# }
```

---

## 📋 Enrichment Sequence

### When Cron Runs:

```
1. Cron job executes (e.g., every 5 minutes)
   ↓
2. Depositcron::run() triggered
   ↓
3. Depositlistener::scan() called
   ↓
4. detectViaBscscan() queries Etherscan API
   ↓
5. For each detected deposit:
   ├─ recordDeposit() stores in wallet_deposits
   └─ (detected count increases)
   ↓
6. ✓ enrichAllRecentDeposits() called
   ├─ Re-queries Etherscan API
   ├─ Gets full transaction details
   ├─ Stores in onchain_transactions
   └─ (enriched count increases)
   ↓
7. creditConfirmed() checks confirmations
   ├─ Moves to confirmed status
   └─ Credits user wallet
   ↓
8. Return result:
   {
     "detected": 5,     ← Found 5 deposits
     "enriched": 5,     ← Enriched 5 deposits
     "credited": 3      ← Credited 3 (rest not enough confirmations)
   }
```

---

## ✅ Verification Checklist

- [x] Enrichment called from Depositcron (auto cron)
- [x] Enrichment called from Cronlab (admin manual)
- [x] Enrichment called from instant_credit_deposits (user button)
- [x] Enrichment called from wallet_check_enrich (balance check)
- [x] Enrichment called from Walletmonitor (admin page)
- [x] Enrichment called from Profile (user page)
- [x] Enrichment called from Lendingcontroller (lending page)
- [x] Full data stored in onchain_transactions
- [x] Balance snapshots stored when mismatch
- [x] Logging implemented for debugging

---

## 🎯 Summary

**Enrichment is NOT a separate cron!**

Instead, it's automatically triggered **inside** the existing Depositlistener::scan() method that's already:
- Called by cron
- Called by admin buttons
- Called by user buttons
- Called by page loads

So enrichment happens **automatically and instantly** whenever deposits are detected!

---

**Status:** ✅ **AUTOMATIC ENRICHMENT ENABLED EVERYWHERE**

Transaction enrichment is now part of every deposit detection flow!
