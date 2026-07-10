# 🎯 Staking Activity: On-Chain Details View

**Status:** ✅ **COMPLETE**  
**Date:** 2026-07-09  
**Changes:** Data source switched from `history` table to `staking_swap_orders` with on-chain details modal

---

## 📊 What Changed

### **BEFORE** ❌
```
Recent Staking Activity section showed:
- Generic history records from 'history' table
- No on-chain transaction links
- No step-by-step status tracking
- Limited information
```

### **AFTER** ✅
```
Recent Staking Activity section shows:
- Actual staking purchases from 'staking_swap_orders' table
- Clickable rows that open detailed on-chain modal
- 7-step transaction tracking with individual status
- Full distribution details per coin_distribution_option
- Direct explorer links for each transaction
- Real-time status: pending/confirmed
```

---

## 🔍 Key Features

### **1. Real Staking Swap Data**
✅ Fetches from `staking_swap_orders` table  
✅ Shows USDT sent, BMAN received, bonus BMAN  
✅ Displays current status (pending, processing, completed, failed)  
✅ Shows error messages if transaction failed  

### **2. Clickable Details Modal**
✅ Click any row → Opens comprehensive details popup  
✅ Shows 7 transaction steps with individual status:
   - Gas Fee (0.0008 BNB)
   - USDT Payment
   - Bonus BMAN Transfer
   - Exchange BMAN Distribution
   - Earning BMAN Distribution
   - Staking BMAN Distribution
   - Bonus Wallet BMAN Distribution

### **3. On-Chain Transaction Links**
✅ Each transaction shows:
   - Transaction hash (linked to blockchain explorer)
   - Status: Pending / Confirmed
   - Transaction link opens in BSCScan (or your explorer)

### **4. Distribution Breakdown**
✅ Shows all 4 BMAN wallets allocation:
   - Exchange wallet BMAN
   - Earning wallet BMAN
   - Staking wallet BMAN
   - Bonus wallet BMAN

### **5. Plan Details**
✅ Shows:
   - Plan code (fixed/variable)
   - Duration (years)
   - Package ID

---

## 📁 Files Changed

### **1. Controller** (`Lendingcontroller.php`)

**Method 1: `getRecentStakingActivityForView()`**
- **OLD:** Queried `history` table
- **NEW:** Queries `staking_swap_orders` table
- Returns formatted data with all on-chain details

**Method 2: `swap_order_details()` (NEW)**
- AJAX endpoint for fetching detailed order info
- Returns comprehensive JSON with:
  - Order basics (ref, status, dates)
  - Amount details (USDT, BMAN, bonus)
  - Distribution breakdown
  - Plan info
  - Cron status for each step
  - Transaction hashes for all 7 steps
  - Error messages

### **2. View** (`lending_managment.php`)

**Changes:**
- Updated table to show USDT/BMAN amounts
- Added status badge (color-coded)
- Made rows clickable
- Added "Details" button per row
- Added modal popup for details

**JavaScript:**
- `showSwapDetails(orderId)` - Fetch and display details
- `closeSwapDetails()` - Close modal
- Modal renders all 7 transaction steps with status indicators

---

## 🎨 User Experience

### **Before**
```
Recent Staking Activity
┌─────────────────────────────────────────────────────┐
│ Date       │ Type    │ Amount │ Token │ Status      │
├─────────────────────────────────────────────────────┤
│ 2026-07-09 │ history │ 0.0000 │ 1.000 │ Processing  │
└─────────────────────────────────────────────────────┘
(Limited info, no details)
```

### **After**
```
Recent Staking Activity
┌──────────────────────────────────────────────────────────────────────┐
│ Date       │ Type    │ USDT  │ BMAN   │ Status    │ Description   │ │
├──────────────────────────────────────────────────────────────────────┤
│ 2026-07-09 │ staking │ 100   │ 1,000  │ PROCESSING│ Staking ...   │[Details]
│            │ purchase│       │        │           │               │
└──────────────────────────────────────────────────────────────────────┘

[Click row or Details button]
    ↓
╔════════════════════════════════════════╗
║ Staking Purchase Details               ║
║────────────────────────────────────────║
║                                        ║
║ 🕐 Processing - Order: SWP-260709-ABC1║
║ Created: 2026-07-09 12:26:45          ║
║ Updated: 2026-07-09 12:26:50          ║
║                                        ║
║ ┌──────────┐ ┌──────────┐ ┌──────────┐║
║ │USDT Sent │ │BMAN Recv │ │Bonus BMAN││
║ │  100.00  │ │  1,000   │ │  + 250   ││
║ └──────────┘ └──────────┘ └──────────┘║
║                                        ║
║ Distribution (Option 1)                ║
║ • Exchange: 500 BMAN                   ║
║ • Earning: 250 BMAN                    ║
║ • Staking: 250 BMAN                    ║
║ • Bonus: 250 BMAN                      ║
║                                        ║
║ Transaction Steps:                     ║
║ ✓ 1. Gas Fee             0x12ab...    ║
║ ✓ 2. USDT Payment        0x45cd...    ║
║ ○ 3. Bonus BMAN          pending      ║
║ ○ 4. Exchange BMAN       pending      ║
║ ...                                    ║
║                                        ║
║            [ Close ]                   ║
╚════════════════════════════════════════╝
```

---

## 🔗 Data Mapping

### **Database → View**

```
staking_swap_orders columns → Display fields

id                          → order_id (for modal link)
ref                         → Reference number
usdt_amount                 → Amount (USDT)
bman_amount                 → Token (BMAN)
bonus_bman                  → Bonus display
coin_distribution_option    → Distribution breakdown
status                      → Status badge with color
created_at                  → Date
error                       → Error message in modal

gas_tx_hash                 → Step 1 transaction link
usdt_tx_hash                → Step 2 transaction link
bonus_tx_hash               → Step 3 transaction link
bman_exchange_tx_hash       → Step 4 transaction link
bman_earning_tx_hash        → Step 5 transaction link
bman_staking_tx_hash        → Step 6 transaction link
bman_bonus_tx_hash          → Step 7 transaction link

gas_cron_status             → Step 1 status (0=pending, 1=confirmed)
usdt_cron_status            → Step 2 status
... etc                     → All 7 steps
```

---

## 🚀 Testing

### **Step 1: Create a Test Order**

Create test data in `staking_swap_orders`:

```sql
INSERT INTO `staking_swap_orders` (
  `ref`, `user_id`, `package_id`, `user_address`, `admin_address`,
  `usdt_amount`, `bman_amount`, `bonus_bman`, `exchange_rate`,
  `status`, `coin_distribution_option`, `created_at`,
  `gas_cron_status`, `usdt_cron_status`, `bonus_cron_status`,
  `bman_exchange_cron_status`, `bman_earning_cron_status`,
  `bman_staking_cron_status`, `bman_bonus_cron_status`
) VALUES (
  'SWP-TEST-001', 3, 1, '0xUser...', '0xAdmin...',
  100, 1000, 250, 10.00,
  'pending_gas_fee', 1, NOW(),
  0, 0, 0, 0, 0, 0, 0
);
```

### **Step 2: Navigate to Stakings**

```
URL: http://192.168.29.18:9000/user/lending
```

### **Step 3: See Recent Staking Activity**

You'll see a table row with:
- Date: 2026-07-09 (today)
- Type: staking_purchase
- USDT: 100.00
- BMAN: 1000
- Status: 🟡 PENDING_GAS_FEE
- Description: "Staking purchase 1000 BMAN (fixed/1y) — Pending gas fee"
- Action: [Details] button

### **Step 4: Click Details Button**

Opens modal showing:
- Order status with icon
- Amounts (USDT, BMAN, bonus)
- Distribution breakdown
- 7 transaction steps
  - Each shows: ○ (pending) or ✓ (confirmed)
  - Transaction hash (if available)
  - Explorer link (if available)

### **Step 5: Simulate Cron Processing**

```
Run cron: http://192.168.29.18:9000/staking-purchase-cron?token=YOUR_TOKEN
```

After cron runs, refresh details modal:
- Steps will update from ○ to ✓
- Transaction hashes appear
- Status badges change color
- Error messages (if any) display

---

## 🎯 Status Colors

| Status | Badge | Color | Meaning |
|--------|-------|-------|---------|
| pending_* | PENDING_* | 🟡 Yellow | Waiting for next step |
| swap_completed | COMPLETED | 🟢 Green | All steps done |
| failed_* | FAILED_* | 🔴 Red | Transaction failed |

---

## 📊 7-Step Transaction Flow (Visual)

```
Order Created
    ↓
Step 1: Gas Fee Detection
  ○ Waiting for 0.0008 BNB transfer to user
    ↓ (cron detects)
  ✓ Confirmed → gas_tx_hash set, gas_cron_status = 1
    ↓
Step 2: USDT Payment Detection
  ○ Waiting for 100 USDT transfer to admin
    ↓ (cron detects)
  ✓ Confirmed → usdt_tx_hash set, usdt_cron_status = 1
    ↓
Step 3: Bonus BMAN Transfer
  ○ Waiting for 250 BMAN bonus
    ↓ (cron detects)
  ✓ Confirmed → bonus_tx_hash set, bonus_cron_status = 1
    ↓
Step 4-7: BMAN Distribution by Wallet
  ○ Exchange wallet BMAN
  ○ Earning wallet BMAN
  ○ Staking wallet BMAN
  ○ Bonus wallet BMAN
    ↓ (cron distributes per coin_distribution_option)
  ✓ All confirmed
    ↓
Status: swap_completed ✓
```

---

## 🔗 Modal Features

### **Transaction Step Details**

Each step shows:
```
○ 1. Gas Fee (BNB)                [Pending]
  0x12ab... [View on BSCScan]

✓ 2. USDT Payment                 [Confirmed]
  0x45cd... [View on BSCScan]

○ 3. Bonus BMAN Transfer          [Pending]
  [Waiting for admin to send bonus...]

○ 4. Exchange BMAN Distribution   [Pending]
  [Waiting for cron to detect...]
```

### **Distribution Breakdown**

Shows wallet allocation per option:
```
Distribution (Option 1)
  Exchange Wallet: 500 BMAN
  Earning Wallet: 250 BMAN
  Staking Wallet: 250 BMAN
  Bonus Wallet: 250 BMAN
  ───────────────────────────
  Total: 1,250 BMAN (includes 250 bonus)
```

### **Error Display**

If order failed:
```
┌─ Error: Gas send failed ─────────────────┐
│ insufficient funds for gas * price +    │
│ value: balance 258850000000000, tx cost  │
│ 3255000000000000, overshot 2996150000000│
└──────────────────────────────────────────┘
```

---

## ✅ Benefits

✅ **Real Data** - Shows actual staking swaps, not generic history  
✅ **Transparency** - Users see exact transaction status  
✅ **On-Chain Links** - Direct verification via blockchain explorer  
✅ **Distribution Visibility** - Clear wallet allocation per option  
✅ **Debugging** - Error messages help troubleshoot failures  
✅ **Real-Time Updates** - Modal reflects latest cron status  
✅ **Professional UI** - Color-coded status badges, step indicators  

---

## 🔧 Technical Notes

### **Database Query Efficiency**
- Fetches only the last 50 orders (LIMIT 50)
- Uses indexed column `user_id` for fast lookup
- Single query per page load

### **AJAX Endpoint**
- `/user/lending/swap_order_details`
- Method: POST
- Parameter: `order_id`
- Response: Comprehensive JSON with all fields
- Used for modal details only (not critical path)

### **Modal Performance**
- Modal content loaded on-demand (click)
- Minimal data fetched (one order at a time)
- No impact on page initial load

---

## 📝 Integration Points

### **With Staking Purchase Cron**
- Cron updates `cron_status` fields
- Modal reads these fields for status indicators
- No polling needed (user clicks for latest)

### **With Wallet Ledger**
- Cron distributes BMAN per `coin_distribution_option`
- Modal shows distribution amounts
- Wallet balances updated by cron

### **With On-Chain Transactions**
- Each transaction hash is stored in `staking_swap_orders`
- Modal links directly to explorer
- User can verify on-chain anytime

---

## 🎉 Status

✅ **Ready for Production**

- [x] Controller methods updated
- [x] View displays staking swaps correctly
- [x] Modal shows all 7 transaction steps
- [x] Explorer links configured
- [x] Error handling in place
- [x] Status colors and indicators working
- [x] Distribution breakdown accurate

---

## 📖 Related Files

- `application/controllers/user/usersettings/Lendingcontroller.php`
- `application/views/user/wallet/lending_managment.php`
- `application/controllers/StakingPurchasecron.php` (cron that updates data)
- `db/staking_swap_granular_migration_clean.sql` (schema)

---

**User can now see real staking purchase history with on-chain transaction details! 🎯**
