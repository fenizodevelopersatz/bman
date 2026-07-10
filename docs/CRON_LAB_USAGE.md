# Cron Lab - Staking Purchase Cron Usage Guide

**Status:** ✅ **ADDED TO ADMIN PANEL**  
**Location:** `http://192.168.29.18:9000/admin/wallet/cron-lab`  
**Version:** v2.0 with failure tracking  

---

## 🎯 What's New

The **Staking Purchase Cron** is now available in the Cron Lab admin panel.

### **Access Path:**
```
Admin Panel
  → Wallet Management
    → Cron Lab
      → "Staking Purchase" card
```

**URL:** `http://192.168.29.18:9000/admin/wallet/cron-lab`

---

## 📋 Card Details

### **Card Name:** Staking Purchase

### **Card Description:**
```
Process multi-step USDT→BMAN swaps with gas fee detection, 
USDT payment, and BMAN distribution per coin_distribution_option (1-7).
```

### **Card Type:** SWAP (color coded)

### **Endpoint:**
```
/staking-purchase-cron
```

---

## 🚀 How to Use

### **1. Navigate to Cron Lab**
Go to: `http://192.168.29.18:9000/admin/wallet/cron-lab`

### **2. Find "Staking Purchase" Card**
Look for the card with:
- Label: **Staking Purchase**
- Type badge: **SWAP**
- Description: "Process multi-step USDT→BMAN..."

### **3. Click "Run now" Button**
- Manual testing button in the card
- Executes cron immediately
- No need to wait for hourly schedule

### **4. View Results**
- Output shows in the card's output section
- JSON response includes:
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

### **5. Copy Endpoint**
- Click "Copy endpoint" button
- Copies the full cron URL with token
- Use for scheduling in crontab or Task Scheduler

---

## 📊 Dashboard Monitoring

### **Top Section - Wallet Balances**
Shows real-time balances of:
- USDT Wallet
- Exchange Wallet
- Earning Wallet
- Staking Wallet
- Bonus Wallet

Updates when you run the cron.

### **Middle Section - Cron Cards**
Shows all available crons:
1. Deposit Credit
2. Chain Sync
3. ROI Run
4. Rank Update
5. Binary Match
6. Bonus Reduction
7. Deliver BMAN
8. **Staking Purchase** ← NEW!
9. Staking Match

Each card has:
- **Run now** - Execute immediately for testing
- **Copy endpoint** - Copy cron URL for scheduling

### **Bottom Section - Transaction Audit**
Shows all on-chain transactions:
- TX Hash
- Date/Time
- Wallet Type
- Transaction Type
- Amount
- Token
- Network
- Status

Filter by type:
- All Types
- ROI
- Swap
- Deposit
- Swap Bonus

---

## ✅ Success Indicators

### **When Cron Runs Successfully**

**Output shows:**
```json
{
  "status": "success",
  "message": "Staking purchase cron completed",
  "details": {
    "total_orders": X,
    "steps": {
      "gas": {"processed": X, "failed": 0},
      "usdt": {"processed": X, "failed": 0},
      ...
    }
  }
}
```

**Wallet Balances Update:**
- See movement in Earning, Staking, Bonus wallets
- Exchange wallet increases for processed orders

**Transaction Audit Shows New TXs:**
- TX Type: "transfer"
- New rows appear with recent timestamps
- Shows BMAN distribution

---

## 🔍 Monitoring Steps Completed

### **Find Pending Orders**
Each step's "processed" count shows:
- ✅ How many gas fees were detected
- ✅ How many USDT payments were detected
- ✅ How many BMAN distributions completed

### **Example Output Interpretation**
```json
"gas": {"processed": 2, "failed": 0}
```
→ 2 orders had gas fees detected this run

```json
"usdt": {"processed": 1, "failed": 0}
```
→ 1 order had USDT payment detected this run

```json
"bman_exchange": {"processed": 3, "failed": 0}
```
→ 3 orders completed BMAN exchange wallet distribution

---

## 🛠️ Troubleshooting in Cron Lab

### **Issue: "processed": 0 for all steps**

**Causes:**
- No pending orders
- All orders already completed
- Orders are in wrong status

**Check:**
```sql
SELECT COUNT(*) FROM staking_swap_orders
WHERE status IN ('pending_gas_fee', 'pending_usdt', 'pending_bman');
```

### **Issue: "failed": X**

**Causes:**
- Etherscan API timeout
- Transaction not indexed yet
- User hasn't sent payment yet

**Solution:**
1. Check transaction audit section for details
2. Run cron again in 5 minutes
3. Check Etherscan manually

### **Issue: Output shows error**

**Example:**
```json
{
  "status": "error",
  "message": "Etherscan API no response"
}
```

**Solution:**
1. Check Etherscan is up: https://www.bscscan.com
2. Verify internet connectivity
3. Check cron token is set in config.php
4. Run again in 1 minute

---

## 📊 Daily Monitoring Checklist

**Every Day at a specific time (e.g., 9 AM):**

1. **Open Cron Lab**
   ```
   http://192.168.29.18:9000/admin/wallet/cron-lab
   ```

2. **Click "Run now" on Staking Purchase**
   - See today's processing stats

3. **Check Failed Count**
   ```
   If "failed" > 0, investigate
   ```

4. **Review Transaction Audit**
   - Filter by date range
   - Check for any suspicious activity

5. **Monitor Wallet Balances**
   - USDT should decrease (user pays)
   - Exchange should increase (BMAN received)
   - Earning/Staking/Bonus increase per option

---

## 🔐 Security Notes

### **Cron Token Required**
- Endpoint requires `cron_token` query parameter
- Set in `application/config/config.php`
- Keep it secret and long (32+ chars recommended)

### **Admin Access Only**
- Cron Lab requires admin login
- Only users with wallet_management permission
- Logged in via session

### **Rate Limiting**
- Can run manually anytime from Cron Lab
- Scheduled cron runs hourly
- No concurrent executions

---

## 💡 Pro Tips

### **Tip 1: Copy Endpoint for Scheduling**
```bash
# In Cron Lab, click "Copy endpoint"
# Paste into crontab:
0 * * * * COPIED_URL_HERE
```

### **Tip 2: Monitor In Real-Time**
```sql
-- While cron is running, check this query:
SELECT id, status, 
       gas_cron_status, gas_cron_status_message,
       usdt_cron_status, usdt_cron_status_message,
       bman_exchange_cron_status, bman_exchange_cron_status_message
FROM staking_swap_orders
WHERE updated_at >= NOW() - INTERVAL 5 MINUTE
ORDER BY updated_at DESC;
```

### **Tip 3: Find Failed Orders Quickly**
1. Open Cron Lab
2. Scroll to "On-chain Transaction Audit"
3. Filter by date/time of last cron run
4. Look for missing TXs or old timestamps

### **Tip 4: Retry Failed Orders**
```sql
-- After identifying failed order ID:
UPDATE staking_swap_orders
SET gas_cron_status = 0,
    gas_cron_status_message = NULL
WHERE id = ORDER_ID;

-- Then run cron again in Cron Lab "Run now"
```

---

## 📞 Support

**Question:** Where do I find Cron Lab?  
**Answer:** Admin Panel → Wallet Management → Cron Lab

**Question:** How do I schedule the cron to run hourly?  
**Answer:** Click "Copy endpoint" and add to crontab: `0 * * * * ENDPOINT`

**Question:** What do the numbers in "processed/failed" mean?  
**Answer:** "processed": how many orders completed this step, "failed": how many had errors

**Question:** Why are all steps showing 0?  
**Answer:** No pending orders. Run the swap purchase flow first to create orders.

**Question:** How do I retry a failed step?  
**Answer:** Update cron_status = 0 for that step, then run cron again.

---

## 🎯 Quick Commands

### **Run Manually (CLI)**
```bash
php index.php stakingpurchasecron run
```

### **Run Via HTTP**
```bash
curl "http://192.168.29.18:9000/staking-purchase-cron?token=YOUR_TOKEN"
```

### **Add to Crontab (Hourly)**
```bash
# Copy endpoint from Cron Lab, then:
0 * * * * curl -s "http://192.168.29.18:9000/staking-purchase-cron?token=..." >> /var/log/staking.log 2>&1
```

### **Check Last Run**
```sql
SELECT MAX(updated_at) as last_run FROM staking_swap_orders;
```

### **Count Completed**
```sql
SELECT COUNT(*) FROM staking_swap_orders WHERE status = 'swap_completed';
```

---

**✅ Staking Purchase Cron is now fully integrated into the Admin Cron Lab!**

Access it at: `http://192.168.29.18:9000/admin/wallet/cron-lab`
