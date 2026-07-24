-- Admin-uploaded downloadable files (certificates, guides, images, PDFs) shown
-- on the member Rank & Rewards page. rank_id NULL = visible to every member;
-- a specific rank_id = only members who currently hold / have achieved that rank.
CREATE TABLE IF NOT EXISTS `rank_files` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`       VARCHAR(200) NOT NULL,
  `rank_id`     INT UNSIGNED NULL,
  `file_path`   VARCHAR(255) NOT NULL,          -- relative, e.g. uploads/rank_files/xxx.pdf
  `file_name`   VARCHAR(255) NOT NULL,          -- original filename (used for download)
  `mime_type`   VARCHAR(100) NULL,
  `file_size`   INT UNSIGNED NULL,              -- bytes
  `is_image`    TINYINT(1) NOT NULL DEFAULT 0,
  `status`      TINYINT(1) NOT NULL DEFAULT 1,  -- 1 active / 0 hidden
  `uploaded_by` INT NULL,
  `created_at`  DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_rank` (`rank_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
