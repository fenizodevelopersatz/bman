# 🧪 Swap Cron Testing & Pagination Guide

**Status:** ✅ **READY TO TEST**  
**Date:** 2026-07-09  
**Features:** Staking Purchase Cron + Transaction Pagination  

---

## 🎯 Part 1: Set Up & Test Swap Cron

### **Step 1: Access Cron Lab**
```
URL: http://192.168.29.18:9000/admin/wallet/cron-lab
```

### **Step 2: Find "Staking Purchase" Card**
Look for the card labeled:
```
STAKING PURCHASE          [SWAP]
Process multi-step USDT→BMAN swaps...
Endpoint: /staking-purchase-cron
```

### **Step 3: Run the Cron**

**Click "Run now" button**

You'll see output like:
```json
{
  "status": "success",
  "message": "Staking purchase cron completed",
  "details": {
    "total_orders": 5,
    "steps": {
      "gas": {"processed": 2, "failed": 0},
      "usdt": {"processed": 1, "failed": 0},
      "bonus": {"processed": 0, "failed": 0},
      "bman_exchange": {"processed": 1, "failed": 0},
      "bman_earning": {"processed": 1, "failed": 0},
      "bman_staking": {"processed": 0, "failed": 0},
      "bman_bonus": {"processed": 1, "failed": 0}
    }
  },
  "ran_at": "2026-07-09 16:00:00"
}
```

---

## 📊 Part 2: Understanding the Output

### **Status Field**
- ✅ `"status": "success"` = Cron ran without errors
- ❌ `"status": "error"` = Something went wrong

### **Steps Breakdown**

Each step shows: `{"processed": X, "failed": Y}`

| Step | Meaning | Expected |
|------|---------|----------|
| **gas** | Gas fees detected | Should increase if orders pending gas |
| **usdt** | USDT payments detected | Should increase if users sent USDT |
| **bonus** | Bonus BMAN detected | May be 0 if no bonus configured |
| **bman_exchange** | BMAN to exchange wallet | Should increase when exchange gets BMAN |
| **bman_earning** | BMAN to earning wallet | Depends on coin_distribution_option |
| **bman_staking** | BMAN to staking wallet | Depends on coin_distribution_option |
| **bman_bonus** | BMAN to bonus wallet | Depends on coin_distribution_option |

### **Interpretation Examples**

**Example 1: All zeros**
```json
"steps": {
  "gas": {"processed": 0, "failed": 0},
  "usdt": {"processed": 0, "failed": 0},
  ...
}
```
✅ **Normal** - No pending orders or all completed

**Example 2: Gas detected, others pending**
```json
"gas": {"processed": 2, "failed": 0},      ✓ Gas detected
"usdt": {"processed": 0, "failed": 0},     ⏳ Waiting for USDT
"bman_exchange": {"processed": 0, "failed": 0}  ⏳ Waiting for BMAN
```
✅ **Normal** - Flow progressing step by step

**Example 3: Failures**
```json
"gas": {"processed": 1, "failed": 1}       ⚠️ 1 order failed gas detection
```
❌ **Issue** - Check cron logs for error message

---

## 🔍 Part 3: Transaction Audit with Pagination

### **What's Changed**

The **On-chain Transaction Audit** section now has:
- ✅ Transaction table with all details
- ✅ Filter by transaction type
- ✅ **NEW: Pagination controls**
- ✅ **NEW: Row counter**
- ✅ **NEW: Page indicator**

### **Pagination Controls**

Located at the bottom of the audit table:

```
Showing 25 rows | Page 1
[← Previous]  [Next →]
```

**Controls:**
- **← Previous** - Go to previous page (disabled on page 1)
- **Next →** - Go to next page (disabled when < 25 rows)
- **Showing X rows** - Count of rows on current page
- **Page N** - Current page number

### **How to Use Pagination**

#### **Navigate Pages**
1. View first 25 transactions
2. Click **"Next →"** to see more
3. Click **"← Previous"** to go back
4. Repeat until you find what you need

#### **Filter by Type**
1. Select a type from dropdown:
   - All Types
   - Gas Fee
   - Deposit
   - Transfer
   - Swap
   - Swap Bonus
   - ROI
2. Results automatically paginate
3. Page resets to 1 on filter change

#### **Refresh Data**
1. Click **"Refresh"** button
2. Table reloads with latest data
3. Stays on current page

---

## 📋 Example Workflow: Test Staking Purchase

### **Step-by-Step Testing**

**1. Create a Test Order**
```sql
INSERT INTO staking_swap_orders 
(user_id, user_address, admin_address, usdt_amount, bman_amount, 
 coin_distribution_option, status, created_at)
VALUES 
(1, '0xuser...', '0xadmin...', '100', '1000', 3, 'pending_gas_fee', NOW());
```

**2. Run Swap Cron**
- Go to Cron Lab
- Click "Run now" on Staking Purchase
- Observe output

**3. Check Audit Trail**
- Scroll to "On-chain Transaction Audit"
- Filter by type: "Gas Fee"
- Should see new transaction if gas detected

**4. Verify Wallet Updates**
- Check top of Cron Lab
- USDT Wallet should reflect user's payment
- Exchange Wallet should reflect BMAN received

**5. Navigate Audit Results**
- If many transactions, use pagination
- Click "Next →" to see older transactions
- Click "← Previous" to go back

---

## 🛠️ Troubleshooting

### **Problem: "processed": 0 for all steps**

**Possible Causes:**
1. No pending orders
2. All orders already completed
3. Orders in wrong status

**Solution:**
```sql
-- Check pending orders
SELECT COUNT(*) FROM staking_swap_orders 
WHERE status IN ('pending_gas_fee', 'pending_usdt', 'pending_bman');

-- If 0, create test order (see above)
```

### **Problem: Next/Previous buttons disabled**

**Cause:** Only one page of data

**Expected Behavior:**
- ← Previous disabled on page 1 (correct)
- Next → disabled when < 25 rows (correct)

**Fix:** Add more transactions to see multiple pages

### **Problem: Pagination not working**

**Solution:**
1. Refresh browser (F5)
2. Clear cache (Ctrl+Shift+Delete)
3. Check browser console for errors (F12)

---

## 📊 Audit Table Columns

| Column | Description | Example |
|--------|-------------|---------|
| **TX Hash** | Blockchain transaction hash | 0xabc123... |
| **Date** | When TX was recorded | 2026-07-09 11:42 |
| **Wallet** | Wallet receiving funds | usdt, exchange, earning |
| **Type** | Transaction type | gas_fee, deposit, transfer |
| **Amount** | Amount transferred | 100.00000000 |
| **Token** | Token symbol | USDT, BNB, BMAN |
| **Network** | Blockchain network | bsc (Binance Smart Chain) |
| **Status** | Transaction status | processing, confirmed |

---

## 🎯 Testing Checklist

- [ ] Staking Purchase cron shows in Cron Lab
- [ ] "Run now" button works
- [ ] Output shows success
- [ ] Wallet balances update
- [ ] Transaction audit populates
- [ ] Can filter by transaction type
- [ ] Pagination buttons appear
- [ ] Can navigate to next page
- [ ] Row counter updates correctly
- [ ] Previous button disabled on page 1

---

## 🚀 Quick Test Command

**Via CLI:**
```bash
php index.php stakingpurchasecron run
```

**Via HTTP:**
```bash
curl "http://192.168.29.18:9000/staking-purchase-cron?token=YOUR_TOKEN"
```

---

## 📈 Monitoring Steps

1. **Hourly Check** (add to crontab)
   ```bash
   0 * * * * curl -s "http://192.168.29.18:9000/staking-purchase-cron?token=YOUR_TOKEN"
   ```

2. **Daily Review** (check Cron Lab)
   - Run "Staking Purchase" cron
   - Check audit for new transactions
   - Verify pagination works
   - Review wallet balances

3. **Weekly Summary** (database query)
   ```sql
   SELECT 
     DATE(created_at) as date,
     COUNT(*) as txs,
     SUM(CASE WHEN tx_type='gas_fee' THEN 1 END) as gas_fees,
     SUM(CASE WHEN tx_type='deposit' THEN 1 END) as deposits,
     SUM(CASE WHEN tx_type='transfer' THEN 1 END) as transfers
   FROM onchain_transactions
   WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
   GROUP BY DATE(created_at);
   ```

---

## ✅ Features Summary

✅ **Swap Cron (Staking Purchase)**
- Detects gas fees
- Detects USDT payments
- Detects bonus BMAN
- Distributes to 4 wallet types
- Tracks all steps independently

✅ **Transaction Audit**
- Shows all on-chain transactions
- Filters by type
- Displays in real-time
- **NEW: Pagination support**
- **NEW: Row counter**
- **NEW: Page indicator**

---

## 🎉 You're All Set!

**Go to:** `http://192.168.29.18:9000/admin/wallet/cron-lab`

**Try it now:**
1. Click "Run now" on Staking Purchase
2. Scroll to On-chain Transaction Audit
3. Navigate pages with Previous/Next buttons
4. Filter by Gas Fee to see detected gas fees

---

**✅ Swap Cron is ready for production testing!**
