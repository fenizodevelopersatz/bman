# 8 — Production Wallet: Deposits, Ledger & the Deposit Listener

Production custodial-wallet architecture for BMAN: how USDT deposits are
**detected automatically**, how balances are moved through a **double-entry
ledger**, and — the key question — **why the admin side works today with NO
Treasury private key set**. Config source of truth: **Master → Token
Settings**. Links: [0_INDEX.md](0_INDEX.md) · [3_CHANGELOG.md](3_CHANGELOG.md) ·
[7_TOKEN_WALLET_INTEGRATION.md](7_TOKEN_WALLET_INTEGRATION.md).

---

## 1. The rule: never touch balances directly

Every balance movement is a **ledger entry**. `Walletledger_model::post()`
appends one `wallet_ledger` row (credit XOR debit) carrying the resulting
`balance_after`, and updates the matching `user_wallets` column **in the same
transaction with `SELECT … FOR UPDATE`**. Two hard guarantees:

- **No double-credit:** `wallet_ledger` has `UNIQUE(tx_hash, wallet_type)`. The
  same on-chain transaction can never credit the same wallet twice. Internal
  moves (no tx_hash) are exempt (NULLs are distinct in a MySQL unique index).
- **No overdraw:** debits check the locked balance first and roll back if short.

Wallets: `usdt · exchange · earning · staking · bonus`.
Reference types: `deposit · withdrawal · stake_purchase · roi · bonus ·
binary_commission · rank_reward · wallet_transfer · admin_adjustment`.

```php
$this->load->model('Walletledger_model','L');
$L->credit($uid,'exchange','200','deposit',['tx_hash'=>$tx,'reference_id'=>$depId]);
$L->transfer($uid,'400','exchange','staking','stake_purchase');   // Exchange → Staking
$L->balances($uid);   // ['usdt'=>…, 'exchange'=>…, 'earning'=>…, 'staking'=>…, 'bonus'=>…]
```

---

## 2. Deep dive — how the deposit listener works

`Depositlistener_model` reads the chain **read-only** (no private key) and
credits confirmed USDT deposits. Two providers, chosen by Token Settings
`deposit_scan_mode`:

### 2A. BscScan / Etherscan API mode (recommended, default)

Public BSC "dataseed" RPCs **block `eth_getLogs`** ("limit exceeded" / archive
token required) — verified live. So the default detector uses the
**Etherscan-v2 unified token-transfer API** (free key, works from any host):

```
GET {explorer_api_url}?chainid={chain}&module=account&action=tokentx
    &contractaddress={USDT}&address={userAddr}&sort=desc&apikey={KEY}
```

For each result whose `to` == our address, we compute
`amount = value / 10^usdt_decimals` and insert a `wallet_deposits` row
(idempotent via `UNIQUE(tx_hash, log_index)`).

### 2B. RPC `eth_getLogs` mode (log-capable / archive node)

Every BEP-20 transfer emits `Transfer(address from, address to, uint256
value)`. Its signature hash (topic0) is constant:

```
keccak256("Transfer(address,address,uint256)")
 = 0xddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef
```

We call `eth_getLogs` on the USDT contract with
`topics = [Transfer, null, [our addresses]]` — topic[2] takes an **array** of
addresses, so one call matches **all** users. Each log yields `tx_hash`,
`blockNumber`, recipient (topic2) and `amount` (data ÷ 10^decimals). A
`wallet_scan_state` row remembers the last scanned block so the poller resumes.

### 2C. Confirm → credit (shared by both modes)

```
detected deposit (status=pending)
      │  confirmations = currentBlock − depositBlock
      ▼
 confirmations < minimum_confirmations  → status=confirming  (wait)
      │
 confirmations ≥ minimum_confirmations  → status=confirmed
      ▼
 credit USDT wallet (amount_usdt)         ┐ both via Walletledger, both keyed by
 convert USDT→BMAN @ active rate          │ the same tx_hash → each wallet credited
 credit Exchange wallet (amount_bman)     ┘ exactly once (unique tx_hash,wallet)
      ▼
 status=credited, credited_at set
```

`minimum_confirmations` and the exchange rate come from the active Token
Settings row (proposal example: 15 confirmations, 1 USDT = X BMAN). Re-running
the scan never double-credits — proven in tests (re-run credited = 0).

### 2D. Running it

- **Manual (admin):** Finance → Wallet Monitor → **Detect Deposits (auto)**.
- **Automatic (cron):** CLI controller `Depositcron::run` —
  `php index.php depositcron run` every ~15 s (Task Scheduler / cron). CLI-only.
- **Per-user (on demand):** `Depositlistener_model::scan($userId)`.

---

## 3. Deposit statuses

`pending → confirming → confirmed → credited` (happy path) · `failed` ·
`expired`. Stored on `wallet_deposits` with `confirmations`, `block_number`,
`amount_usdt`, `amount_bman`, `network`, `tx_hash` (unique).

---

## 4. Admin side works WITHOUT the Treasury private key — verified

The user asked to confirm this before setting any admin key. **It does.** The
entire deposit path is read-only chain access + DB writes:

| Admin capability | Needs private key? | Status |
|---|:--:|---|
| Read on-chain balances (`eth_call`) | ❌ No | ✅ works now |
| Detect USDT deposits (BscScan API / getLogs) | ❌ No | ✅ works (set a free API key **or** log-RPC) |
| Confirm + credit deposits (ledger) | ❌ No | ✅ verified (10 USDT → 200 BMAN, no double-credit) |
| Reconcile / adjust balances | ❌ No | ✅ works |
| **Send BMAN / process on-chain withdrawal** | ✅ **Yes** | ⬜ deferred to payout engine |

So the only thing that needs the **Treasury** private key is *sending tokens
out* (withdrawals) — and even that can start manual: the admin sends from their
own wallet and **pastes the tx hash** on approval (no server key). The Treasury
key is only required to *automate* outgoing sends.

**One config to enable auto-detect:** a free BscScan/Etherscan API key in
Token Settings (Section 1 → Explorer API Key), or switch scan mode to a
log-capable RPC. Without it, the scan returns a clear message instead of
failing silently.

---

## 5. Withdraw flow (no server key needed to start)

```
User: wallet + amount + transfer password + email OTP → withdraw_request (pending)
  → Admin queue: user / wallet / amount / balance / KYC / status
  → Approve: admin sends on-chain manually, PASTES tx hash → status=approved/completed, hash saved
  → Reject: reason required (Incorrect Wallet / Low Balance / KYC Pending / Suspicious / Manual / Other)
```

Statuses: `pending · under_review · approved · rejected · processing ·
completed`. The existing `withdrawals` table already holds
amount/fee/status/tx-hash/approver. KYC-approved + transfer-password + email-OTP
gates are required before a request is accepted. Automating the on-chain send
(vs pasting a hash) is the payout-engine task that needs the Treasury key.

---

## 6. Runtime QR

The deposit address QR is generated **in-browser at runtime** from the address
(`assets/js/vendor/qrcode.min.js`) — no dependency on a pre-generated PNG (the
server InfiQr PNG remains a fallback). Shown on the user wallet tab with the
address + Copy + network (BSC BEP20) + minimum-deposit context.

---

## 7. Status — built vs planned

**Built & verified (this phase):**
- `wallet_ledger` double-entry (unique tx_hash, balance_after, FOR UPDATE),
  `wallet_deposits` (unique tx), `user_wallets` balance columns, `wallet_scan_state`.
- `Walletledger_model`, `Depositlistener_model` (BscScan + RPC), `Depositcron`.
- Admin Wallet Monitor: balance scan, **Detect Deposits (auto)**, deposits list.
- Runtime client-side QR; Token Settings scan-mode + API-key fields.
- Verified: ledger guarantees, deposit detect→confirm→credit, **no private key**.

**Planned (next):**
- Frontend WalletConnect / MetaMask deposit (approve+transfer, hash to backend).
- Full Wallet page tabs (Deposit / Withdraw / History / Statements) + PDF statements.
- Withdraw request UI + admin approve-with-hash / reject-with-reason screens.
- On-chain withdrawal automation via `Web3bman::sendToken` (needs Treasury key).
- Deposit-address sweep to Treasury; cron scheduling.

> Task board: [0_INDEX.md](0_INDEX.md). Every change is logged in
> [3_CHANGELOG.md](3_CHANGELOG.md).
