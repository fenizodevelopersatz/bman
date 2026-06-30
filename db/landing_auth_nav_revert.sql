-- =====================================================================
--  Revert the landing nav / CTA back to the EXISTING auth pages
--  (the new /login & /register pages were removed). Idempotent.
--    mysql -u user -p db < db/landing_auth_nav_revert.sql
-- =====================================================================
UPDATE `landing_menu` SET `url` = 'user/in' WHERE `title` = 'Login';

UPDATE `landing_settings` SET `svalue` = 'user/re'
 WHERE `section` = 'header' AND `skey` = 'buy_btn_url';
