-- =====================================================================
--  Make DARK the default landing theme.
--  (The page also defaults to dark in code, but if a stored theme_mode
--   value exists it wins — so set it here.)  Idempotent.
--    mysql -u user -p db < db/landing_set_dark_default.sql
-- =====================================================================
INSERT INTO `landing_settings` (`section`,`skey`,`svalue`)
SELECT 'general','theme_mode','dark'
WHERE NOT EXISTS (SELECT 1 FROM `landing_settings` WHERE `section`='general' AND `skey`='theme_mode');

UPDATE `landing_settings` SET `svalue` = 'dark'
 WHERE `section`='general' AND `skey`='theme_mode';
