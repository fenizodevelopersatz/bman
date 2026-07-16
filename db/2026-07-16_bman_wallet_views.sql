-- ============================================================================
-- BMAN wallet reconciliation views.
--
-- Faithful SQL mirror of WalletMaturity_model::wallet_breakdown() so the two
-- can be cross-checked against each other:
--   total       = user_wallets.{wallet}_balance (lifetime credited, cached)
--   locked      = immature wallet_ledger credits (staking/earning maturity)
--   matured     = total - locked, floored at 0
--   holds       = active bman_wallet_ledger lock/debit entries for that wallet
--                 (pending/approved/processing requests + completed payouts)
--   available   = matured - holds, floored at 0
--
-- These are NOT (yet) the live read-path — the app computes the same figures
-- in PHP via BCMath (application/models/WalletMaturity_model.php). Use these
-- views to independently verify the app's numbers, e.g.:
--
--   SELECT * FROM v_bman_wallet_balances WHERE user_id = 123;
--   SELECT * FROM v_bman_user_available WHERE user_id = 123;
--
-- See db/reconcile_bman_wallets.php for the automated drift/negative-balance
-- checks described in the BMAN wallet-management spec.
-- ============================================================================

CREATE OR REPLACE VIEW v_bman_wallet_balances AS
SELECT
    uw.user_id,
    'exchange' AS wallet,
    uw.exchange_balance AS total,
    COALESCE(locked.amt, 0) AS locked,
    GREATEST(uw.exchange_balance - COALESCE(locked.amt, 0), 0) AS matured,
    COALESCE(held.amt, 0) AS holds,
    GREATEST(GREATEST(uw.exchange_balance - COALESCE(locked.amt, 0), 0) - COALESCE(held.amt, 0), 0) AS available
FROM user_wallets uw
LEFT JOIN (
    SELECT user_id, SUM(credit) AS amt FROM wallet_ledger
    WHERE wallet_type = 'exchange' AND is_matured = 0 AND credit > 0 GROUP BY user_id
) locked ON locked.user_id = uw.user_id
LEFT JOIN (
    SELECT user_id, SUM(amount) AS amt FROM bman_wallet_ledger
    WHERE wallet = 'exchange' AND entry_type IN ('lock', 'debit') AND status = 'active' GROUP BY user_id
) held ON held.user_id = uw.user_id

UNION ALL

SELECT
    uw.user_id,
    'earning' AS wallet,
    uw.earning_balance AS total,
    COALESCE(locked.amt, 0) AS locked,
    GREATEST(uw.earning_balance - COALESCE(locked.amt, 0), 0) AS matured,
    COALESCE(held.amt, 0) AS holds,
    GREATEST(GREATEST(uw.earning_balance - COALESCE(locked.amt, 0), 0) - COALESCE(held.amt, 0), 0) AS available
FROM user_wallets uw
LEFT JOIN (
    SELECT user_id, SUM(credit) AS amt FROM wallet_ledger
    WHERE wallet_type = 'earning' AND is_matured = 0 AND credit > 0 GROUP BY user_id
) locked ON locked.user_id = uw.user_id
LEFT JOIN (
    SELECT user_id, SUM(amount) AS amt FROM bman_wallet_ledger
    WHERE wallet = 'earning' AND entry_type IN ('lock', 'debit') AND status = 'active' GROUP BY user_id
) held ON held.user_id = uw.user_id

UNION ALL

SELECT
    uw.user_id,
    'staking' AS wallet,
    uw.staking_balance AS total,
    COALESCE(locked.amt, 0) AS locked,
    GREATEST(uw.staking_balance - COALESCE(locked.amt, 0), 0) AS matured,
    COALESCE(held.amt, 0) AS holds,
    GREATEST(GREATEST(uw.staking_balance - COALESCE(locked.amt, 0), 0) - COALESCE(held.amt, 0), 0) AS available
FROM user_wallets uw
LEFT JOIN (
    SELECT user_id, SUM(credit) AS amt FROM wallet_ledger
    WHERE wallet_type = 'staking' AND is_matured = 0 AND credit > 0 GROUP BY user_id
) locked ON locked.user_id = uw.user_id
LEFT JOIN (
    SELECT user_id, SUM(amount) AS amt FROM bman_wallet_ledger
    WHERE wallet = 'staking' AND entry_type IN ('lock', 'debit') AND status = 'active' GROUP BY user_id
) held ON held.user_id = uw.user_id

UNION ALL

SELECT
    uw.user_id,
    'bonus' AS wallet,
    uw.bonus_balance AS total,
    COALESCE(locked.amt, 0) AS locked,
    GREATEST(uw.bonus_balance - COALESCE(locked.amt, 0), 0) AS matured,
    COALESCE(held.amt, 0) AS holds,
    GREATEST(GREATEST(uw.bonus_balance - COALESCE(locked.amt, 0), 0) - COALESCE(held.amt, 0), 0) AS available
FROM user_wallets uw
LEFT JOIN (
    SELECT user_id, SUM(credit) AS amt FROM wallet_ledger
    WHERE wallet_type = 'bonus' AND is_matured = 0 AND credit > 0 GROUP BY user_id
) locked ON locked.user_id = uw.user_id
LEFT JOIN (
    SELECT user_id, SUM(amount) AS amt FROM bman_wallet_ledger
    WHERE wallet = 'bonus' AND entry_type IN ('lock', 'debit') AND status = 'active' GROUP BY user_id
) held ON held.user_id = uw.user_id;

CREATE OR REPLACE VIEW v_bman_user_available AS
SELECT user_id, SUM(available) AS available
FROM v_bman_wallet_balances
GROUP BY user_id;
