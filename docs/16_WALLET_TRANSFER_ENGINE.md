# 16 — Centralized Wallet Transfer Engine

Status: 🟢 **Implemented & tested.** ONE validation + execution service
(`application/models/wallet/Wallettransferservice_model.php`) used by **both**
the User Panel (`user/transfer_wallet`) and Admin Panel
(`admin/finance/internal-transfers`). No duplicated business logic — both
controllers call `execute()`.

Links: [9_INTERNAL_WALLET_TRANSFER.md](9_INTERNAL_WALLET_TRANSFER.md) ·
[8_WALLET_DEPOSIT_WITHDRAW.md](8_WALLET_DEPOSIT_WITHDRAW.md) ·
[0_INDEX.md](0_INDEX.md) · [3_CHANGELOG.md](3_CHANGELOG.md).

---

## 1. Rules (enforced identically for both panels)

Currency: **BMAN only**. Wallets: `exchange · earning · staking · bonus` (USDT excluded).

**Member transfers** (to another user, same wallet on the recipient):
| Source wallet | Allowed recipient |
|---|---|
| exchange / earning / staking | any member in the **source's downline** (sponsor tree) |
| **bonus** | **the source's LEFT or RIGHT binary leg, up to `bonusLegDepth()` levels down** (currently **2**) |

The bonus rule reads the **binary tree** (`binary_placement.parent_id` +
`position`) — the same relationship the Binary Tree page draws — not the sponsor
chain in `users.sponser`. Originally exactly one level down (2026-08-27,
morning); **widened the same day to 2 levels** per explicit instruction, using a
recursive query (`binaryLegDownline()`) that tags every descendant with which
top-level leg it fell under, so "left" vs "right" stays meaningful past depth 1.
The direct sponsor, a sibling branch, and anyone past the depth cap are all
rejected (`bonus_only_to_binary_leg_downline`). A source with an empty or
shallow leg simply has fewer eligible bonus recipients — a leaf has zero, a leg
one level deep has fewer than the full 2-level set.

Worked example (live data, source `#2`, left leg → `#8`, right leg → `#5`):

| Level | Left leg | Right leg |
|---|---|---|
| 1 (leg child) | `#8` NEXMAN591373 | `#5` NEXMAN715985 |
| 2 (leg grandchild) | `#9` NEXMAN397920, `#10` NEXMAN471834 | `#6` NEXMAN831941, `#7` NEXMAN992152 |
| 3 — **rejected**, past the cap | `#11` NEXMAN767566 (child of `#10`) | — |

`bonusLegDepth` is a plain private property on the engine (not an admin
setting) — bump the one number to change the cap again. **Unrelated:** a
separate, pre-existing settings screen at Admin ▸ Staking ▸ Bonus & Matching
(`admin/staking/bonus-settings`, backed by `staking_bonus_settings` /
`Staking_model::bonusSettings()`) stores `transfer_enabled`,
`transfer_to_direct_left`, `transfer_to_direct_right` and two security toggles —
but nothing in this engine (or anywhere else) reads those columns. That panel is
dead UI today; it neither gates nor describes the rule actually enforced here.

**Internal transfers** (source user's own wallets):
| From | To |
|---|---|
| **exchange** | bonus · earning · staking |

Exchange is **source-only** (never receives). No reverse, no other pairs.

**Admin** acts on behalf of a chosen source user and follows the **exact same
rules** — `via=admin` skips only the User-Panel **KYC + transfer-password** gates,
never the wallet / downline / binary-leg-downline / balance rules.

## 2. Validation (`validate($ctx)` → `[ok, code, message, ctx]`)

Amount > 0, ≤ 8 dp · source user exists + active · from-wallet valid (BMAN) ·
(user panel) KYC approved + transfer password (`password_verify` + legacy md5) ·
internal direction allowed / Exchange-source-only · member: recipient exists +
active + not self + **downline** (walk sponsor chain) or **binary leg downline**
(`binary_placement` descendant, left/right, within `bonusLegDepth()` levels) for
bonus · sufficient balance (both
panels). First failing rule returns immediately with a machine `code`.

## 3. Execution (`execute($ctx)`)

Validate → **idempotency** (a completed transfer with the same `idempotency_key`
returns its ref, no re-charge) → `trans_begin` → `Walletledger::debit(source)`
(row-locked `SELECT … FOR UPDATE`, re-verifies balance) → `credit(destination)` →
insert `wallet_internal_transfer` (double-entry ledger ids, before/after balances,
via, created_by) → audit → commit. **Any failure rolls back everything.**

Double-entry, ACID, row-locked, retry-safe, immutable audit.

## 4. Data

- `wallet_internal_transfer` (existing) + new columns (`db/wallet_transfer_service.sql`):
  `idempotency_key` (unique), `created_by`, `failure_reason`, and blockchain
  columns (`tx_hash`, `block_number`, `confirmations`, `gas_used`, `gas_fee`,
  `network`) for the history/detail spec (internal moves leave them null).
- `wallet_transfer_audit` — append-only audit (action, mode, via, actor, source,
  recipient, wallets, amount, result code, IP, UA, request id).
- Detail view: `service->detail($ref)` returns header + the two `wallet_ledger`
  rows (debit/credit) + audit trail.

## 5. Both panels call the one service

- **User** `user/Transfer_wallet::do_transfer` → `execute([..., via=>'user', require_kyc=>true, transfer_password=>…])`.
- **Admin** `admin/wallet/Internaltransfers::do_transfer` → `execute([..., via=>'admin', actor_id=>adminId, source_user_id=>selected user])`.

The old `Wallettransfer_model` paths are no longer the source of truth for these
two entry points.

## 6. Files

**New:** `db/wallet_transfer_service.sql`,
`application/models/wallet/Wallettransferservice_model.php`,
`application/controllers/Wallettransfertest.php` (CLI tests).
**Touched:** `controllers/user/Transfer_wallet.php`,
`controllers/admin/wallet/Internaltransfers.php`.

## 7. Tests (`php index.php wallettransfertest run|exec|ui|pickers [uid]`)

- **20/20 rule tests** — relationships are discovered from live data (last run:
  source 2 · sponsor 1 · downline 5 · leg-depth1 8 · leg-depth2 10 · leg-depth3
  11 · unrelated 3): internal Exchange-source-only + allowed pairs, member
  downline rules, **bonus at depth 1 and depth 2 allowed, depth 3 rejected**
  (the actual boundary the depth cap draws — not just "has a leg"), sponsor and
  unrelated both rejected, self / amount / precision / USDT.
- **3/3 execution tests**: real transfer moved balances (−2 exchange / +2 bonus),
  **idempotent** re-run returned the same ref with no double debit, balances restored.
- **6/6 UI-support tests**: `preview` shape + blocked pairs, `detailEnriched`,
  and picker scoping (`recipientOptions(bonus)` = only the binary leg downline
  within the depth cap, never the sponsor).
- `pickers <uid>` prints the exact JSON **both** recipient endpoints return for
  that user, per wallet — the quickest way to eyeball a rule change.

## 8. Shared UI layer (both panels, one codebase)

🟢 **Implemented.** A single front-end module drives the transfer UX in BOTH
panels so validation, the confirmation dialog, and the details modal are
identical everywhere. It contains **no business logic** — the engine
(`validate`/`execute`) stays the single source of truth; the UI only mirrors the
rules to guide input and calls read-only endpoints.

- **Shared module:** `assets/js/wallet_transfer_ui.js` (framework-agnostic,
  injects its own dialog/modal DOM + CSS → pixel-identical in both panels).
- **Shared partial:** `application/views/shared/wallet_transfer_ui.php` — included
  by both views; boots `WalletTransferUI.init({panel, previewUrl, detailUrl, csrf})`.
- **Read-only support methods** (engine unchanged, additive):
  `preview($ctx)` runs the SAME rule+balance checks (via `admin` so it skips only
  the User-Panel KYC/password gates, which the dialog surfaces separately) and
  returns `{ok, code, message, from_balance, balance_after, recipient, kyc_ok,
  has_transfer_password, to_wallet}`; `detailEnriched($ref)` wraps `detail()` and
  adds `users{sender, recipient, sponsor, upline}`.
- **Endpoints:** `user/transfer_wallet/preview` (POST) · `…/tx_detail` (GET) ·
  `admin/finance/internal-transfers/preview` (POST) · `…/tx-detail` (GET).

What the shared layer does:
1. **Disables invalid combinations** up front (Exchange source-only for internal;
   all four selectable for member; impossible destinations hidden) instead of
   letting them be chosen then rejected. Member mode shows the recipient rule
   ("must be in downline" / "bonus → within 2 levels down the left or right
   binary leg").
   - **Recipient pickers are server-scoped**: both panels list ONLY the source's
     valid recipients for the chosen From wallet — the source's **downline** for
     exchange/earning/staking, or the **left/right binary leg downline, up to
     `bonusLegDepth()` levels** (currently 2; up to 6 members, shallowest and
     left-before-right first) for bonus (never the full member list).
     Backed by `recipientOptions($sourceId,$fromWallet,$q)` +
     `downlineIds($sourceId)` (BFS down the sponsor tree; `sponser` may hold an id
     or a referral id, so each level matches on both) and
     `binaryLegDownlineIds($sourceId)` (recursive binary-tree query, depth-capped). Endpoints:
     `user/transfer_wallet/search_recipients` (POST, needs `from_wallet`) and
     `admin/finance/internal-transfers/recipients` (GET `sender_id`,`from_wallet`).
     Changing the source/From wallet clears + refetches the recipient. The admin
     **source-user** picker still lists all members (admin acts for anyone).
2. **Live validation preview** — on submit it calls `preview`, showing available
   balance, balance-after, resolved recipient, and each gate, with a spinner
   while validating.
3. **Confirmation dialog** (identical markup) listing Source User, Recipient,
   Transfer Type, From/To Wallet, Amount, Available Balance, Balance After, and a
   Validation Status line — **Confirm is enabled only when every rule (and, for
   the User Panel, KYC + transfer-password) passes** ("✓ All business rules
   passed").
4. **Transaction Details modal** (identical in both panels) — a **7-tab** full
   financial audit trail, all fields verified against the live schema (real
   records only, no mock data):
   - **Summary** — a Sender → Receiver flow card + Transaction ID, Reference,
     Type, Token (BMAN), Amount, Fee, Net, Status, Created/Completed time, Notes.
   - **Sender** — User ID, Referral ID, Username, Full Name, Email, KYC status,
     From Wallet, Balance Before/After + the sponsor/upline tree.
   - **Receiver** — same identity set + To Wallet + Balance Before/After (or
     "Self" for internal own-wallet moves).
   - **Ledger** — Debit entry + Credit entry cards (wallet, ledger id, before →
     after) from the double-entry `wallet_ledger` rows, plus the raw rows table.
   - **Blockchain** — hash (BscScan link), block, confirmations, gas used/fee,
     network, addresses (enriched from `onchain_transactions` when linked); shows
     an explicit *off-chain internal ledger* note when there is no chain tx.
   - **Validation** — itemized checks derived from the transfer's OWN record:
     wallet-direction rule, downline / binary-leg-downline (whichever applied —
     the binary-leg-downline line names the actual side, left or right),
     transfer-password, KYC, and admin-override (with pass / n-a / overridden).
   - **Audit Timeline** — the `wallet_transfer_audit` rows as a vertical timeline
     (action · result code · message · actor · time) + IP, browser/device,
     request id, user agent.
   Sources: `wallet_internal_transfer` (balances before/after, blockchain cols),
   `wallet_ledger` (double entry), `wallet_transfer_audit` (timeline),
   `onchain_transactions` (chain enrichment), `users` (identities). No duplicate
   records are created — every tab reads existing tables.

### History tables (both panels)

Columns: **Reference · Sender · Receiver · From Wallet · To Wallet · Amount ·
Token · Status · Date · Details**. The Details button opens the shared 7-tab
modal. Data comes from `Wallettransfer_model::history()` (user, scoped to rows
where the member is sender or recipient) and `adminList()` (admin) — both already
join sender/recipient identities and carry `to_wallet`.
5. On success both panels refresh balances and reload the history immediately.

**Tests:** `php index.php wallettransfertest ui` — 4/4 (preview shape + balances,
blocked-pair reported not thrown, member recipient/gate flags, `detailEnriched`
header+users+ledger+audit). Engine regression `… run` still 18/18.

**Touched for the UI:** `assets/js/wallet_transfer_ui.js` (new),
`application/views/shared/wallet_transfer_ui.php` (new),
`controllers/user/Transfer_wallet.php`,
`controllers/admin/wallet/Internaltransfers.php`,
`views/user/wallet/transfer_wallet.php`,
`views/admin/wallet/internal_transfers.php`, `config/routes.php`,
`models/wallet/Wallettransferservice_model.php` (additive read-only methods).
