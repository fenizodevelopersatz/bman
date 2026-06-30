-- =====================================================================
--  Point the landing nav / CTA at the new Webze-styled auth pages
--  (/login, /register), which post to the existing user/in & user/re.
--  Idempotent.
--    mysql -u user -p db < db/landing_auth_nav.sql
-- =====================================================================
UPDATE `landing_menu` SET `url` = 'login' WHERE `title` = 'Login';

UPDATE `landing_settings` SET `svalue` = 'register'
 WHERE `section` = 'header' AND `skey` = 'buy_btn_url';
