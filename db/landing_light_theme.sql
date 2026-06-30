-- =====================================================================
--  Approved LIGHT theme palette + theme_mode default + nav/URL cleanup.
--  Idempotent.  Run after the base landing schema.
--    mysql -u user -p db < db/landing_light_theme.sql
-- =====================================================================

-- 1) Approved palette (client-approved light design) -------------------
UPDATE `landing_settings` SET `svalue` = '#FFC94A' WHERE `section`='general' AND `skey`='primary_color';
UPDATE `landing_settings` SET `svalue` = '#6D4AFF' WHERE `section`='general' AND `skey`='secondary_color';
UPDATE `landing_settings` SET `svalue` = '#FFC94A' WHERE `section`='general' AND `skey`='button_color';
UPDATE `landing_settings` SET `svalue` = '#6D4AFF' WHERE `section`='general' AND `skey`='button_hover_color';
UPDATE `landing_settings` SET `svalue` = '#FFFFFF' WHERE `section`='general' AND `skey`='background_color';

-- 2) Default theme = light (insert if missing) ------------------------
INSERT INTO `landing_settings` (`section`,`skey`,`svalue`)
SELECT 'general','theme_mode','light'
WHERE NOT EXISTS (SELECT 1 FROM `landing_settings` WHERE `section`='general' AND `skey`='theme_mode');

UPDATE `landing_settings` SET `svalue`='light'
 WHERE `section`='general' AND `skey`='theme_mode' AND (`svalue` IS NULL OR `svalue`='');

-- ensure button_hover_color exists even if palette seed wasn't run
INSERT INTO `landing_settings` (`section`,`skey`,`svalue`)
SELECT 'general','button_hover_color','#6D4AFF'
WHERE NOT EXISTS (SELECT 1 FROM `landing_settings` WHERE `section`='general' AND `skey`='button_hover_color');

-- 3) Navigation: point to real app auth pages, drop the duplicate -----
--    user login  = /user/in     user register = /user/re
UPDATE `landing_menu` SET `url` = 'landing' WHERE `title` = 'Home';
UPDATE `landing_menu` SET `url` = 'user/in' WHERE `title` = 'Login';
-- remove the Register nav item (it is already the header CTA button -> no repeat)
DELETE FROM `landing_menu` WHERE `title` = 'Register';

-- 4) Header CTA button -> register page --------------------------------
UPDATE `landing_settings` SET `svalue` = 'user/re' WHERE `section`='header' AND `skey`='buy_btn_url';
UPDATE `landing_settings` SET `svalue` = 'Register' WHERE `section`='header' AND `skey`='buy_btn_text';
