-- =====================================================================
--  Seed new landing_settings keys added after the initial build:
--   - general.button_hover_color   (palette: button hover)
--   - hero.success_message         (early-access form response text)
--  Idempotent: only inserts when the key is missing.
-- =====================================================================
INSERT INTO `landing_settings` (`section`,`skey`,`svalue`)
SELECT 'general','button_hover_color','#5a3df0'
WHERE NOT EXISTS (SELECT 1 FROM `landing_settings` WHERE `section`='general' AND `skey`='button_hover_color');

INSERT INTO `landing_settings` (`section`,`skey`,`svalue`)
SELECT 'hero','success_message','Thank you! We will be in touch soon.'
WHERE NOT EXISTS (SELECT 1 FROM `landing_settings` WHERE `section`='hero' AND `skey`='success_message');
