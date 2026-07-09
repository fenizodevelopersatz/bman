# ✅ Etherscan Transaction Enrichment Integration

**Purpose:** Populate missing `from_address` and `to_address` in wallet history by querying Etherscan API, but ONLY when balance mismatch is detected (to minimize API usage on paid accounts).

---

## 🎯 Complete Flow

### User Workflow

```
1. Visit /user/wallet
   ↓
2. Click "Check On-chain Balance" button
   ↓
3. System queries:
   - Database USDT balance (from user_wallets)
   - RPC balance (from blockchain via Web3)
   ↓
4. Compare balances:
   - If MATCH: Show "Up to date" ✓
   - If MISMATCH: Trigger Etherscan enrichment
   ↓
5. Etherscan Enrichment (if mismatch):
   - Find transactions with NULL from_address or to_address
   - For each TX:
     * Call Etherscan API: eth_getTransactionByHash
     * Extract from_address, to_address, value
     * Update onchain_transactions table
   - Small delay (0.1s) between API calls to avoid rate limiting
   ↓
6. Reload page
   ↓
7. Wallet history now displays complete from/to addresses ✓
```

---

## 🔧 Technical Implementation

### 1. Custodialwallet_model Methods

#### `enrichTransactionFromEtherscan($tx_hash)`
**Purpose:** Query Etherscan API for transaction details

```php
// Returns:
[
  'from' => '0xabcd1234...',  // Sender address
  'to'   => '0xefgh5678...',  // Recipient address
  'value' => 123456789         // Transaction value (wei)
]
```

**What it does:**
1. Takes transaction hash (e.g., `0x123abc...`)
2. Loads Etherscan API key from config
3. Makes HTTPS request to BscScan API
4. Parses JSON response
5. Extracts from/to addresses and value
6. Returns enriched data or false on error

**Rate limiting:** Uses cURL with 10-second timeout

#### `updateTransactionFromEtherscan($tx_hash)`
**Purpose:** Update database with enriched data

```php
// Updates onchain_transactions SET:
UPDATE onchain_transactions
SET from_address = '0x...',
    to_address = '0x...',
    value = 123456789,
    updated_at = NOW()
WHERE tx_hash = '0x...'
```

#### `reconcileWithEtherscan($user_id, $before_balance, $after_balance)`
**Purpose:** Smart trigger — only enrich if balance mismatch detected

**Flow:**
1. Compare before and after USDT balance
2. If they match → Return `['mismatch' => false]` (no enrichment needed)
3. If they differ → Find incomplete transactions (missing from/to)
4. For each incomplete transaction:
   - Call `updateTransactionFromEtherscan()`
   - Wait 0.1 seconds (avoid rate limiting)
5. Return list of updated transaction hashes

**Returns:**
```php
[
  'mismatch' => true,
  'updated'  => ['0x123', '0x456'],  // TX hashes that were enriched
  'count'    => 2
]
```

---

### 2. Controller Endpoint

#### `GET /user/wallet-check-enrich`

**Purpose:** AJAX endpoint to check balance and trigger enrichment

**Flow:**
1. Verify user is logged in
2. Load models: Custodialwallet_model, Walletledger_model
3. Get DB balance from `user_wallets.usd_balance`
4. Get RPC balance from `monitor()` (live blockchain check)
5. Compare balances
6. If mismatch → Call `reconcileWithEtherscan()` to enrich
7. Return JSON response

**Response on balance match:**
```json
{
  "success": true,
  "balance_match": true,
  "db_balance": 0.1,
  "rpc_balance": 0.1,
  "difference": 0,
  "updated_txs": [],
  "updated_count": 0,
  "message": "Balances match ✓"
}
```

**Response on mismatch (enrichment triggered):**
```json
{
  "success": true,
  "balance_match": false,
  "db_balance": 0.1,
  "rpc_balance": 0.2,
  "difference": 0.1,
  "updated_txs": ["0x123...", "0x456..."],
  "updated_count": 2,
  "message": "Enriching transaction data from Etherscan..."
}
```

---

### 3. Route

```php
$route['user/wallet-check-enrich'] = 'user/usersettings/historycontroller/wallet_check_enrich';
```

---

### 4. View (JavaScript Integration)

#### Original Function
```javascript
refreshWalletState()  // Checks balance
```

#### Enhanced Function
```javascript
async function refreshWalletState() {
  // 1. Check balance
  const res = await fetch(walletCheckUrl);
  const d = res.data;
  
  // 2. If mismatch detected
  if (d.difference > 0) {
    // Show status: "Fetching transaction details..."
    await enrichTransactionsFromEtherscan();
    // Reload page to show updated addresses
    location.reload();
  }
}

async function enrichTransactionsFromEtherscan() {
  // Calls /user/wallet-check-enrich endpoint
  const res = await fetch('/user/wallet-check-enrich');
  const json = await res.json();
  
  // Show: "✓ Enriched 2 transaction(s) with Etherscan data"
  toastMini(`✓ Enriched ${json.updated_count} transaction(s)`);
}
```

---

## 📊 Database Impact

### Before Enrichment
```
onchain_transactions:
├── tx_hash: 0x123abc...
├── from_address: NULL or empty
├── to_address: NULL or empty
├── value: NULL
└── status: confirmed
```

### After Enrichment
```
onchain_transactions:
├── tx_hash: 0x123abc...
├── from_address: 0xDEPOSIT123...  ✓ Populated from Etherscan
├── to_address: 0xUSER5678...      ✓ Populated from Etherscan
├── value: 100000000               ✓ Populated from Etherscan
└── status: confirmed
```

---

## ⚙️ Configuration

### Already Configured in Token Settings

The Etherscan API configuration is already set up in the admin panel:

**Location:** Admin → Master → Token Settings

**Fields Used:**
- `explorer_api_url` - Etherscan API endpoint (e.g., https://api.bscscan.com/api)
- `explorer_api_key` - API key for accessing the explorer API

The model automatically queries `token_settings` table to fetch these values at runtime.

```php
// From Custodialwallet_model::enrichTransactionFromEtherscan()
$settings = $this->db->select('explorer_api_url, explorer_api_key')
    ->from('token_settings')
    ->where('network', 'mainnet')
    ->limit(1)
    ->get()
    ->row_array();

$api_key = trim($settings['explorer_api_key']);
$api_url = trim($settings['explorer_api_url']) ?: 'https://api.bscscan.com/api';
```

---

## 🔑 Setup Steps

### 1. Verify Admin Configuration
- Go to: Admin → Master → Token Settings
- Look for "Explorer API URL" field
- Look for "Explorer API Key" field

### 2. If Not Set, Add API Key
- Get free API key from: https://bscscan.com/apis
- Click "Edit" on MAINNET (chain 56)
- Fill in:
  - **Explorer API URL:** `https://api.bscscan.com/api`
  - **Explorer API Key:** `YOUR_ACTUAL_KEY_FROM_BSCSCAN`
- Click Save

### 3. Verify Database
Check that values are stored:
```sql
SELECT explorer_api_url, explorer_api_key
FROM token_settings
WHERE network = 'mainnet' OR chain_id = 56
LIMIT 1;
```

Should return your configured API URL and key.

### 4. Test
- Go to `/user/wallet`
- Click "Check On-chain Balance"
- If balance mismatch exists:
  - System reads API key from token_settings
  - Calls Etherscan API
  - Updates onchain_transactions table
  - Reloads page
  - Transaction history now shows from/to addresses ✓

---

## 📈 When Is Enrichment Triggered?

**YES - Enrich Transactions:**
- Balance was 0.1 USDT (DB), now is 0.2 USDT (RPC)
- Difference detected → Call Etherscan for incomplete TXs
- Updates from/to addresses in database

**NO - Skip Enrichment:**
- Balance is 0.1 USDT (DB) and 0.1 USDT (RPC)
- No difference → No API calls
- Saves on API quota

---

## 💡 Cost Optimization

**Free API Tier:**
- 5 requests per second
- Unlimited requests per day
- Perfect for our use case

**Why Minimal Calls:**
1. Only call when balance mismatch detected
2. Most of the time balances match → No API calls
3. When difference found, fetch only incomplete TXs (< 10 recent ones)
4. 0.1s delay between requests avoids rate limits

**Example:**
- Day 1: 0 API calls (balances matched)
- Day 2: 3 API calls (balance mismatch, 3 incomplete TXs)
- Day 3: 0 API calls (balances matched)
- **Total:** 3 calls/day vs. potential 100+ if we checked every transaction

---

## ✅ Wallet History Display

### Before
```
TX Hash          Type        Amount    From Address       To Address
0xABC123...      Deposit     0.10      [empty]           [empty]     ❌
0xDEF456...      Transfer    0.05      [empty]           [empty]     ❌
```

### After Enrichment
```
TX Hash          Type        Amount    From Address      To Address
0xABC123...      Deposit     0.10      0xDEP...123       0xUSR...456  ✓
0xDEF456...      Transfer    0.05      0xUSR...456       0xEXT...789  ✓
```

---

## 🧪 Testing

### Test 1: Balance Match
1. Go to `/user/wallet`
2. Click "Check On-chain Balance"
3. Expected: "Up to date" ✓
4. Database logs: No Etherscan API calls

### Test 2: Balance Mismatch (Simulation)
1. Manually change `user_wallets.usd_balance` to incorrect value
2. Click "Check On-chain Balance"
3. Expected: "Fetching transaction details..."
4. Database: `onchain_transactions.from_address` populated ✓
5. After reload: Addresses visible in history

### Test 3: Transaction Details Modal
1. After enrichment, reload page
2. Click "View Details" on any transaction
3. Expected: Modal shows complete from/to addresses ✓

---

## 📋 Summary

| Aspect | Details |
|--------|---------|
| **Purpose** | Populate missing from/to addresses from blockchain |
| **Trigger** | Balance mismatch detection |
| **API Used** | BscScan (Etherscan clone for BSC) |
| **Cost** | Free (5 req/sec, unlimited/day) |
| **Frequency** | Only when balance differs (saves quota) |
| **Data Updated** | `onchain_transactions` table |
| **User Sees** | Complete wallet history with addresses |

---

**Status:** ✅ **Ready to Deploy**

**Next Step:** Set your Etherscan API key in `application/config/etherscan.php`
