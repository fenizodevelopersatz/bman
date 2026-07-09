# 🔧 Wallet Check Debug Guide

**Status:** Debugging 500 error on `/member/profile/wallet_check`

---

## ✅ What I Fixed:

1. **Removed enrichAllRecentDeposits() call** from wallet_check
   - This was causing the 500 error
   - Enrichment still happens automatically via Depositlistener::scan()

2. **Added comprehensive logging**
   - Now logs each step
   - Shows actual error details

3. **Simplified error handling**
   - Better error messages
   - File and line number in logs

---

## 🧪 Test Now

### Step 1: Clear Logs
```bash
# Delete old logs so we can see fresh output
rm -f "D:\SATZ\SATZ\php\Bman\admlm_responsive_26_02_26\admlm\application\logs\*.php"
```

### Step 2: Call the Endpoint
```bash
curl -X POST "http://192.168.29.18:9000/member/profile/wallet_check" \
  -H "X-Requested-With: XMLHttpRequest"
```

### Step 3: Check Logs
```bash
# View latest log
tail -50 "D:\SATZ\SATZ\php\Bman\admlm_responsive_26_02_26\admlm\application\logs\log-*.php"
```

You should see:
```
[INFO] [wallet_check] Scan result: credited=2, enriched=5
[INFO] [wallet_check] Monitor: RPC=0.20, DB=0.10
[INFO] [wallet_check] Fetched 10 on-chain transactions
```

---

## 🐛 If Still Getting 500 Error

Look for log entries like:
```
[ERROR] [wallet_check] Exception: [ERROR MESSAGE] at [FILE]:[LINE]
```

This will tell us exactly what's failing.

---

## 📋 What Should Work Now

✅ **Manual Wallet Check:**
1. User clicks "Check On-chain Balance"
2. Calls POST /member/profile/wallet_check
3. Detects and credits deposits (via scan)
4. Returns balance info + on-chain transactions
5. All transaction details enriched ✓

---

## ✨ Response Should Look Like:

```json
{
  "status": "success",
  "credited": 2,
  "enriched": 5,
  "message": "2 deposit(s) credited, 5 enriched.",
  "data": {
    "rpc_balance": 0.20,
    "db_balance": 0.10,
    "difference": 0.10
  },
  "onchain_transactions": {
    "rows": [
      {
        "tx_hash": "0xbbcc...",
        "from_address": "0xb4f0...",
        "to_address": "0xe837...",
        "value": 100000000000000000,
        "gas_used": 34503,
        "block_number": 108945953,
        "balance_before": 0.05,
        "balance_after": 0.15
      }
    ],
    "counts": {"ALL": 10, "INCOMING": 8, "OUTGOING": 2},
    "paging": {"page": 1, "pages": 1, "total": 10}
  }
}
```

---

**Report back with:**
1. The response status (200 or 500)
2. The error message if 500
3. What you see in the logs

This will help us fix the exact issue!
