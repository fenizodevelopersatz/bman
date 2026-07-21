-- ============================================================================
-- Lightweight presence heartbeat for Chat's Direct tab. No websocket/socket
-- server exists in this app (chat runs on a 2s/5s AJAX poll) — this piggybacks
-- on that existing poll instead of adding new real-time infrastructure.
-- A user is considered "online" if last_active_at is within the last ~15s.
-- ============================================================================

ALTER TABLE `users`
  ADD COLUMN `last_active_at` DATETIME NULL DEFAULT NULL;
