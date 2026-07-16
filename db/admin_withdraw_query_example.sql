-- Admin Withdrawal Requests Query with Bank Details
-- For displaying BMAN withdrawal requests on admin panel with user bank info

SELECT
    wr.id,
    wr.request_no,
    wr.user_id,
    wr.source_wallet,
    wr.request_amount,
    wr.fee_amount,
    wr.net_amount,
    wr.bman_usdt_rate,
    wr.usdt_amount,
    wr.withdraw_address,
    wr.remark,
    wr.tx_hash,
    wr.admin_remark,
    wr.status,
    wr.approved_by,
    wr.approved_at,
    wr.completed_at,
    wr.created_at,
    wr.updated_at,

    -- User Details
    u.id AS user_id_verified,
    u.username,
    u.email,
    u.referral_id,
    u.status AS user_status,

    -- Bank Details (for KYC verification)
    ub.id AS bank_id,
    ub.holder_name,
    ub.bank_name,
    ub.account_number,
    ub.ifsc,
    ub.upi_id,
    ub.status AS bank_status,

    -- Wallet Allocation Summary
    (SELECT GROUP_CONCAT(CONCAT(wallet, ':', amount) SEPARATOR '|')
     FROM bman_withdraw_allocations
     WHERE request_id = wr.id) AS wallet_allocations,

    -- Lock Status
    (SELECT COALESCE(SUM(amount), 0)
     FROM bman_wallet_ledger
     WHERE ref_type = 'withdrawal'
       AND ref_id = wr.id
       AND entry_type = 'lock'
       AND status = 'active') AS currently_locked

FROM bman_withdraw_requests wr
LEFT JOIN users u ON wr.user_id = u.id
LEFT JOIN user_bank ub ON wr.user_id = ub.user_id AND ub.status = 'approved'
ORDER BY wr.created_at DESC;

-- ============================================
-- FILTERING QUERIES
-- ============================================

-- Get pending requests with bank details
SELECT
    wr.request_no,
    wr.user_id,
    u.username,
    u.email,
    u.referral_id,
    wr.request_amount,
    wr.fee_amount,
    wr.net_amount,
    wr.usdt_amount,
    wr.source_wallet,
    wr.withdraw_address,
    wr.status,
    wr.created_at,
    ub.holder_name,
    ub.bank_name,
    ub.account_number,
    ub.ifsc,
    ub.upi_id
FROM bman_withdraw_requests wr
LEFT JOIN users u ON wr.user_id = u.id
LEFT JOIN user_bank ub ON wr.user_id = ub.user_id AND ub.status = 'approved'
WHERE wr.status = 'pending'
ORDER BY wr.created_at DESC;

-- ============================================
-- GET REQUEST DETAILS WITH ALLOCATIONS
-- ============================================

SELECT
    wr.id,
    wr.request_no,
    wr.user_id,
    wr.source_wallet,
    wr.request_amount,
    wr.fee_amount,
    wr.net_amount,
    wr.bman_usdt_rate,
    wr.usdt_amount,
    wr.withdraw_address,
    wr.tx_hash,
    wr.status,
    wr.approved_by,
    wr.approved_at,
    wr.completed_at,
    wr.created_at,

    -- User Info
    u.username,
    u.email,
    u.referral_id,

    -- Bank Details
    ub.holder_name,
    ub.bank_name,
    ub.account_number,
    ub.ifsc,
    ub.upi_id,

    -- Allocations
    ba.wallet,
    ba.amount AS allocated_amount,

    -- Current Lock Status
    (SELECT COALESCE(SUM(amount), 0)
     FROM bman_wallet_ledger
     WHERE ref_type = 'withdrawal'
       AND ref_id = wr.id
       AND entry_type = 'lock'
       AND status = 'active') AS locked_amount,

    -- Audit Trail
    (SELECT GROUP_CONCAT(
        CONCAT(action, ' (', new_status, ') by Admin #', admin_id, ' at ', created_at)
        SEPARATOR ' → '
     )
     FROM withdraw_audit_log
     WHERE request_id = wr.id
     ORDER BY created_at ASC) AS audit_trail

FROM bman_withdraw_requests wr
LEFT JOIN users u ON wr.user_id = u.id
LEFT JOIN user_bank ub ON wr.user_id = ub.user_id AND ub.status = 'approved'
LEFT JOIN bman_withdraw_allocations ba ON wr.id = ba.request_id
WHERE wr.id = :request_id
ORDER BY ba.wallet ASC;

-- ============================================
-- ADMIN DASHBOARD STATS
-- ============================================

SELECT
    COUNT(*) AS total_requests,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved,
    SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) AS processing,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS rejected,
    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failed,

    COALESCE(SUM(CASE WHEN status IN ('pending','approved','processing') THEN request_amount ELSE 0 END), 0) AS total_locked_amount,
    COALESCE(SUM(CASE WHEN status = 'completed' THEN net_amount ELSE 0 END), 0) AS total_paid_out,

    COUNT(DISTINCT user_id) AS unique_users
FROM bman_withdraw_requests;

-- ============================================
-- USER'S WITHDRAWAL HISTORY WITH BANK
-- ============================================

SELECT
    wr.request_no,
    wr.request_amount,
    wr.fee_amount,
    wr.net_amount,
    wr.usdt_amount,
    wr.source_wallet,
    wr.withdraw_address,
    wr.status,
    wr.tx_hash,
    wr.created_at,
    wr.approved_at,
    wr.completed_at,

    -- Latest approved bank details
    (SELECT holder_name FROM user_bank WHERE user_id = :user_id AND status = 'approved' ORDER BY id DESC LIMIT 1) AS holder_name,
    (SELECT bank_name FROM user_bank WHERE user_id = :user_id AND status = 'approved' ORDER BY id DESC LIMIT 1) AS bank_name,
    (SELECT account_number FROM user_bank WHERE user_id = :user_id AND status = 'approved' ORDER BY id DESC LIMIT 1) AS account_number

FROM bman_withdraw_requests wr
WHERE wr.user_id = :user_id
ORDER BY wr.created_at DESC;

-- ============================================
-- SEARCH QUERIES
-- ============================================

-- Search by request number
SELECT wr.*, u.username, u.referral_id, ub.holder_name, ub.bank_name
FROM bman_withdraw_requests wr
LEFT JOIN users u ON wr.user_id = u.id
LEFT JOIN user_bank ub ON wr.user_id = ub.user_id AND ub.status = 'approved'
WHERE wr.request_no LIKE :search_term
ORDER BY wr.created_at DESC;

-- Search by user
SELECT wr.*, u.username, u.referral_id, u.email, ub.holder_name, ub.bank_name
FROM bman_withdraw_requests wr
LEFT JOIN users u ON wr.user_id = u.id
LEFT JOIN user_bank ub ON wr.user_id = ub.user_id AND ub.status = 'approved'
WHERE u.username LIKE :search_term
   OR u.email LIKE :search_term
   OR u.referral_id LIKE :search_term
ORDER BY wr.created_at DESC;

-- Search by address
SELECT wr.*, u.username, u.referral_id, ub.holder_name, ub.bank_name
FROM bman_withdraw_requests wr
LEFT JOIN users u ON wr.user_id = u.id
LEFT JOIN user_bank ub ON wr.user_id = ub.user_id AND ub.status = 'approved'
WHERE wr.withdraw_address LIKE :search_term
ORDER BY wr.created_at DESC;
