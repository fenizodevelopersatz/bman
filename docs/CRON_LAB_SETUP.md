# ROI Maturity CRON - Cron Lab Setup

## ⚡ Quick Setup for Cron Lab

### 📋 Copy-Paste Ready URLs

**For Cron Lab - ROI Maturity Process:**
```
http://yoursite.com/cron/roi_maturity/process
```

**Full Example with Domain:**
```
http://192.168.29.185:8000/cron/roi_maturity/process
```

---

## 🎯 Cron Lab Setup (3 Steps)

### Step 1: Open Cron Lab
Navigate to: `http://192.168.29.185:8000/admin/wallet/cron-lab`

### Step 2: Click "Add CRON Job"

### Step 3: Fill in Details
```
CRON Name:     ROI Maturity Process
Endpoint URL:  http://192.168.29.185:8000/cron/roi_maturity/process
Schedule:      0 * * * * (every hour)
Timeout:       300 seconds
```

### Step 4: Save & Monitor

---

## Example Responses

### Process Endpoint Response

**URL:**
```
GET /cron/roi_maturity/process
```

**Response:**
```json
{
    "status": true,
    "message": "ROI maturity processing completed",
    "timestamp": "2026-07-10 12:16:13",
    "stats": {
        "found": 3,
        "processed": 2,
        "failed": 1
    },
    "orders": [
        {
            "id": 1,
            "user_id": 5,
            "principal": 100,
            "roi_remaining": 5.25
        }
    ]
}
```

### Test Endpoint Response

**URL:**
```
GET /cron/roi_maturity/test
```

**Response:**
```json
{
    "status": true,
    "message": "ROI Maturity CRON System is operational",
    "timestamp": "2026-07-10 12:16:13",
    "database": "Connected",
    "roi_distribution_table": "exists",
    "matured_orders_pending": 0
}
```

---

## Cron Lab Configuration

### RECOMMENDED: Add ROI Maturity Process Job

**In Cron Lab UI:**
1. Click "**Add New CRON**"
2. **CRON Name:** `ROI Maturity Process`
3. **Endpoint:** `http://192.168.29.185:8000/cron/roi_maturity/process`
4. **Interval:** Every 1 hour (recommended: `0 * * * *`)
5. **Expected Response:** JSON with `"status": true` and statistics
6. **Timeout:** 300 seconds (to allow processing time)

**Purpose:** Automatically processes matured staking investments and releases ROI to users

---

### Alternative: Add Test CRON Job (Optional)

**In Cron Lab UI:**
1. Click "**Add New CRON**"
2. **CRON Name:** `ROI Maturity Test`
3. **Endpoint:** `http://192.168.29.185:8000/cron/roi_maturity/test`
4. **Interval:** Every 5 minutes (for health monitoring)
5. **Expected Response:** JSON with `"status": true`

**Purpose:** Verifies the system is operational (optional for monitoring)

---

## How It Works

### Test Endpoint Flow
```
Test Request
    ↓
Check Database Connection
    ↓
Verify roi_distribution Table Exists
    ↓
Count Pending Matured Orders
    ↓
Return Status JSON
```

### Process Endpoint Flow
```
Process Request
    ↓
Find All Orders with maturity_date <= NOW()
    ↓
Calculate Remaining ROI for Each
    ↓
Update roi_return_status
    ↓
Credit BMAN to User Wallet
    ↓
Record in onchain_transactions
    ↓
Return Statistics JSON
```

---

## Monitoring in Cron Lab

### Expected Status
- ✅ Test: `"status": true, "database": "Connected"`
- ✅ Process: `"status": true, "processed": X`

### Error Responses
- ❌ Database Error: `"status": false, "error": "Connection failed"`
- ❌ Invalid Action: `"status": false, "error": "Invalid action"`

### Dashboard Metrics
- **Found:** Number of matured orders
- **Processed:** Successfully processed
- **Failed:** Orders with errors
- **Pending:** Orders still in progress

---

## URL Reference

### 🚀 RECOMMENDED for Cron Lab

| Purpose | URL | Format |
|---------|-----|--------|
| **Process** | `/cron/roi_maturity/process` | Routed ✅ |
| **Test** | `/cron/roi_maturity/test` | Routed ✅ |

### Alternative URLs (Standalone)

| Purpose | URL | Format |
|---------|-----|--------|
| Test (JSON) | `/roi_maturity_test.php?action=test&format=json` | JSON |
| Process (JSON) | `/roi_maturity_test.php?action=process&format=json` | JSON |

**For Cron Lab:** Use the routed URLs (recommended)

---

## Testing Locally

### Test with cURL
```bash
# Test endpoint
curl -s "http://192.168.29.185:8000/roi_maturity_test.php?action=test&format=json" | jq

# Process endpoint
curl -s "http://192.168.29.185:8000/roi_maturity_test.php?action=process&format=json" | jq
```

### Test with PHP
```php
<?php
$response = file_get_contents(
    'http://192.168.29.185:8000/roi_maturity_test.php?action=test&format=json'
);
$data = json_decode($response, true);
echo $data['message']; // "ROI Maturity CRON System is operational"
?>
```

---

## Troubleshooting

### "404 Not Found"
**Cause:** File path incorrect
**Solution:** 
- Verify `roi_maturity_test.php` is in project root
- Check URL uses correct domain/port (192.168.29.185:8000)

### "status": false, "error": "Database connection failed"
**Cause:** Database credentials incorrect
**Solution:**
- Update $dbConfig in `roi_maturity_test.php`
- Verify MySQL server is running
- Check credentials (user: root, password: empty)

### "roi_distribution_table": "not found"
**Cause:** Migration not run
**Solution:**
```bash
cd db/
php run_roi_distribution_migration.php
```

### "matured_orders_pending": 0
**Cause:** No orders with past maturity dates
**Solution:**
- This is normal - will process orders once their maturity_date passes
- For testing, manually update an order's maturity_date to past:
```sql
UPDATE staking_swap_orders
SET maturity_date = '2025-01-01'
WHERE id = 1;
```

---

## Files Involved

- **Standalone Test:** `roi_maturity_test.php` (root directory)
- **Controller:** `application/controllers/RoiMaturityCron.php`
- **Routes:** `application/config/routes.php`
- **Database:** `roi_distribution` table
- **Database:** `staking_swap_orders` table (maturity_date column)

---

## Next Steps

1. ✅ Add Test CRON Job to Cron Lab
2. ✅ Run test to verify endpoint works
3. ✅ Add Process CRON Job to Cron Lab
4. ✅ Set hourly schedule
5. ✅ Monitor dashboard for stats

---

## Support

**Issue:** Endpoint not responding  
**Check:** 
- Server is running
- Port 8000 is accessible
- File exists at project root

**Issue:** Database errors  
**Check:**
- MySQL is running
- Database credentials are correct
- Tables exist (staking_swap_orders, roi_distribution)

**Issue:** No orders being processed  
**Check:**
- Orders exist with maturity_date <= NOW()
- roi_return_status is not already 'completed'
- Wallet ledger has earning wallet for user

---

**Status: ✅ READY TO USE IN CRON LAB**

Add the URLs above to your Cron Lab dashboard and monitor ROI maturity processing!
