# 7 — Token & Wallet Integration (custodial vs on-chain)

How BMAN/USDT actually move on the BMAN platform: what needs a blockchain
private key and what does not. Reference for the deposit → convert → credit →
stake → withdraw flow. Config source of truth: **Master → Token Settings**
(`token_settings`). Links: [0_INDEX.md](0_INDEX.md) ·
[3_CHANGELOG.md](3_CHANGELOG.md) ·
[6_STAKING_PACKAGES_PLANS_ROI.md](6_STAKING_PACKAGES_PLANS_ROI.md).

---

## 1. The key question — "give BMAN without the admin private key"

**You do not send BMAN on-chain to give a user tokens.** BMAN is a custodial
platform: the exchange holds a few on-chain wallets (treasury / deposit / gas),
and each user's spendable BMAN is an **internal ledger number**, not an
on-chain balance. "Giving" a user BMAN — a purchase credit, ROI, bonus,
matching bonus, or an admin grant — is a **database ledger entry**. It needs
**no private key and touches no blockchain.**

> Blockchain fact: an ERC-20/BEP-20 balance can only be moved by the address
> that holds it, signing with its private key. There is no way to push tokens
> out of the admin's BMAN wallet without that key. The custodial model avoids
> the problem entirely — you don't move on-chain tokens per user; you move a
> ledger number. A key is needed **only** at real withdrawal, and it is the
> **treasury/gas** wallet key, never a per-user key.

```
Custodial model (what BMAN uses)
────────────────────────────────
Deposit  : user sends USDT to their custodial deposit address   (on-chain in)
Credit   : platform credits internal BMAN balance                (LEDGER, no key)
Purchase : ROI/bonus/matching all credit internal balances       (LEDGER, no key)
Stake    : move Exchange → Staking wallet                         (LEDGER, no key)
Withdraw : user pulls to their OWN external wallet                (on-chain out — treasury key)
```

So: the **only** step that ever signs with a key is **withdrawal**, handled by
`Web3bman::sendToken()` from the treasury wallet — see §4.

---

## 2. Giving BMAN internally — the ledger (no key)

`application/models/Custodialwallet_model.php` is the single place that
credits/debits the four §3A wallets (`exchange · earning · staking · bonus`)
in the existing `wallet_transactions` ledger.

```php
$this->load->model('Custodialwallet_model', 'cw');

// Give a user 25 BMAN into their Exchange wallet (purchase credit / admin grant)
$this->cw->credit($userId, '25', 'exchange', 'purchase_credit');   // NO private key

// ROI payout into Earning wallet
$this->cw->credit($userId, '2.30', 'earning', 'roi_payout');

// 25% staking bonus into Bonus wallet
$this->cw->credit($userId, '6.25', 'bonus', 'staking_bonus');

// Read balances
$bal = $this->cw->balances($userId);   // ['exchange'=>…, 'earning'=>…, 'staking'=>…, 'bonus'=>…]
```

This is exactly how the existing **Coin Distribution** purchase flow already
credits wallets (`Coindistribution_model::creditWallets()`), and how the
future ROI / bonus / matching engines should credit — never by sending
on-chain.

---

## 3. Full deposit → stake flow (custodial, one key-free path)

```php
// 1. User deposited USDT to their custodial address (watcher confirms on-chain).
//    Convert USDT → BMAN using the ACTIVE Token Settings rate (never hardcode):
$this->load->model('Tokenmaster_model', 'tokens');
$bman = $this->tokens->convertUsdtToBman($usdtAmount);   // e.g. 100 USDT → 50000 BMAN

// 2. Credit the user's Exchange wallet (LEDGER — no key):
$this->load->model('Custodialwallet_model', 'cw');
$this->cw->credit($userId, $bman, 'exchange', 'usdt_deposit');

// 3. User buys a staking package: move BMAN Exchange → Staking (LEDGER — no key):
$this->cw->move($userId, $stakeAmount, 'exchange', 'staking', 'stake_purchase');

// 4. Grant the 25% staking bonus (LEDGER — no key):
$this->cw->credit($userId, bcmul($stakeAmount, '0.25', 8), 'bonus', 'staking_bonus');
```

No private key appears anywhere above. The user "received BMAN" and staked it
entirely through internal ledger entries backed by the treasury's on-chain
reserve.

---

## 4. When a key IS required — withdrawal (on-chain out)

Only when a user withdraws to **their own external wallet** does the platform
sign an on-chain transfer, from the **treasury/gas** wallet, via the web3
library. The per-user custodial address is never swept with a per-user key.

```php
$this->load->library('web3bman');

// Sending key = the TREASURY wallet key, stored ENCRYPTED, decrypted just-in-time.
// (Never hardcode; never store plaintext. See §5.)
$treasuryPk = $this->web3bman->decryptKey($encryptedTreasuryKey);

// Debit the user's internal balance first (reserve the withdrawal):
$this->cw->debit($userId, $amount, 'exchange', 'withdrawal');

// Then pay out on-chain to the user's own address:
$res = $this->web3bman->sendToken($treasuryPk, $userExternalAddress, $amount);
// $res = ['tx_hash' => '0x…', 'from' => treasury, 'to' => user, 'amount' => …]
// store $res['tx_hash'] on the withdrawal row; confirm via minimum_confirmations.
```

`sendToken()` reads chain id, RPC, BMAN contract, decimals and gas from the
active Token Settings row — nothing is hardcoded.

---

## 5. Handling the treasury key safely (the one key you keep)

You still need **one** private key: the treasury/gas wallet that funds
withdrawals. Options, safest first:

1. **External signer / KMS / MPC** (Fireblocks, AWS KMS, Vault). The app calls
   a sign API; the key never lives in PHP. Best for production at scale.
2. **Encrypted at rest, decrypted just-in-time.** Store the key with
   `Web3bman::encryptKey()` (AES-256 via CI `encryption_key`) in a restricted
   settings row / env, decrypt only inside the withdrawal job, never log it.
   `token_settings` holds only public **addresses** — never put a key there.
3. **Hot/cold split.** Keep a small hot (gas/treasury) float for automated
   withdrawals; the bulk sits in the cold/reserve wallet (addresses in Token
   Settings §5) and is topped up manually.

Generate a fresh treasury/gas wallet from **Token Settings → Wallet Tools →
Generate Wallet** (key shown once, never stored), then store the key by option
1 or 2 and paste the **address** into the Treasury/Gas field.

---

## 6. Sample values for Token Settings (BSC)

| Field | Mainnet | Testnet |
|---|---|---|
| Network | Mainnet | Testnet |
| Blockchain | Binance Smart Chain (BEP20) | Binance Smart Chain (BEP20) |
| Chain ID | `56` | `97` |
| RPC URL | `https://bsc-dataseed.binance.org` | `https://data-seed-prebsc-1-s1.binance.org:8545` |
| Explorer URL | `https://bscscan.com` | `https://testnet.bscscan.com` |
| BMAN decimals | `18` | `18` |
| USDT contract | `0x55d398326f99059fF775485246999027B3197955` | *(testnet USDT of your choice)* |
| USDT decimals | `18` | `18` |
| Exchange rate | `500` (1 USDT = 500 BMAN) | `500` |
| Min confirmations | `15` | `10` |
| Gas limit | `210000` | `210000` |
| Gas price (gwei) | `5` | `10` |
| Tx timeout (s) | `300` | `300` |
| Retry count | `3` | `3` |

Wallet/contract fields take a full `0x…` (40-hex) address; leave contracts
blank until deployed. The Edit popup shows these as placeholders.

---

## 7. Summary

- **Give BMAN = ledger credit. No private key, no blockchain.** Use
  `Custodialwallet_model::credit()`.
- **Only withdrawal signs on-chain**, from the treasury wallet, via
  `Web3bman::sendToken()` — one key, stored encrypted / in a KMS.
- **Never hardcode** chain/contract/rate/gas — always the active
  `token_settings` row (`Tokenmaster_model::activeSettings()`).
- Old purchases keep their snapshotted rate; rate changes only affect new
  purchases.
