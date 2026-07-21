-- Add password reset token columns to users table
ALTER TABLE `users` ADD COLUMN `password_reset_token` VARCHAR(255) NULL DEFAULT NULL AFTER `email`;
ALTER TABLE `users` ADD COLUMN `password_reset_expiry` DATETIME NULL DEFAULT NULL AFTER `password_reset_token`;

-- Add index for token lookup performance
ALTER TABLE `users` ADD INDEX `idx_password_reset_token` (`password_reset_token`);
