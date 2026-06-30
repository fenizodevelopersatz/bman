-- =====================================================================
--  Member Panel Theme (independent from the Landing Page theme).
--  Stored in site_settings under settings_type = 'member_theme'.
--  The landing theme lives in landing_settings — the two never mix.
--  Idempotent.
--    mysql -u user -p db < db/member_theme_seed.sql
-- =====================================================================

INSERT INTO `site_settings` (`settings_type`,`settings_name`,`settings_value`)
SELECT * FROM (
    SELECT 'member_theme' AS t, 'mode'              AS n, 'light'    AS v UNION ALL
    SELECT 'member_theme', 'user_switch',         '1'       UNION ALL
    SELECT 'member_theme', 'primary',            '#6D4AFF' UNION ALL
    SELECT 'member_theme', 'secondary',          '#FFC94A' UNION ALL
    SELECT 'member_theme', 'accent',             '#A855F7' UNION ALL
    SELECT 'member_theme', 'highlight_primary',  '#6D4AFF' UNION ALL
    SELECT 'member_theme', 'highlight_accent',   '#A855F7' UNION ALL
    SELECT 'member_theme', 'hover_highlight',    '#5a3df0' UNION ALL
    SELECT 'member_theme', 'active_highlight',   '#6D4AFF' UNION ALL
    SELECT 'member_theme', 'gradient_start',     '#6D4AFF' UNION ALL
    SELECT 'member_theme', 'gradient_end',       '#A855F7' UNION ALL
    SELECT 'member_theme', 'success',            '#1BC5BD' UNION ALL
    SELECT 'member_theme', 'warning',            '#FFA800' UNION ALL
    SELECT 'member_theme', 'danger',             '#F64E60' UNION ALL
    SELECT 'member_theme', 'info',               '#8950FC'
) AS seed
WHERE NOT EXISTS (
    SELECT 1 FROM `site_settings` s
     WHERE s.`settings_type` = seed.t AND s.`settings_name` = seed.n
);
