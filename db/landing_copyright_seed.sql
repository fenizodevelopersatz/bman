-- =====================================================================
--  Seed the dynamic Copyright row used by Site Settings (Meta Details)
--  and the landing/home footer.  Safe to run multiple times.
-- =====================================================================
INSERT INTO `site_settings` (`settings_type`, `settings_name`, `settings_value`)
SELECT 'meta-settings', 'copyright', 'Copyright & design by @ThemeAdapt - 2026'
WHERE NOT EXISTS (
    SELECT 1 FROM `site_settings`
     WHERE `settings_type` = 'meta-settings' AND `settings_name` = 'copyright'
);
