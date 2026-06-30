-- =====================================================================
--  Landing leads — captures "Get Early Access" (and future CTA) submissions.
--  The Landing controller only inserts if this table exists, so importing
--  it is optional but recommended.  (Roadmap Phase 3 will add a CRUD UI.)
-- =====================================================================
CREATE TABLE IF NOT EXISTS `landing_leads` (
  `id`           INT(11) NOT NULL AUTO_INCREMENT,
  `name`         VARCHAR(150) NULL,
  `email`        VARCHAR(190) NOT NULL,
  `phone`        VARCHAR(40)  NULL,
  `source`       VARCHAR(80)  NULL,      -- hero_early_access | newsletter | …
  `landing_page` VARCHAR(120) NULL,
  `status`       VARCHAR(30)  NOT NULL DEFAULT 'new',
  `notes`        TEXT         NULL,
  `ip`           VARCHAR(45)  NULL,
  `created_at`   DATETIME     NULL,
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
