-- =====================================================================
--  DYNAMIC LANDING PAGE  —  schema + seed
--  Webze ICO/Crypto template  ->  CodeIgniter admin-managed content
--
--  Design note (follows existing architecture):
--    * Singleton fields use a key/value table `landing_settings`
--      exactly like the existing `site_settings` table
--      (section, skey, svalue)  ->  helper landing('section','key').
--    * Repeater sections (menu, brands, features, work, exchange logos,
--      crypto, faq, roadmap, team) get their own row tables.
--    * `landing_versions` stores JSON snapshots for
--      Version History / Export / Import / Restore Default.
--
--  Import:  mysql -u root yourdb < db/landing_page_schema.sql
-- =====================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- 1) Key/value singleton settings (General, Header, Hero, Marquee,
--    Token, Exchange, Footer, SEO, Social, Scripts, section headings)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `landing_settings` (
  `id`         INT(11) NOT NULL AUTO_INCREMENT,
  `section`    VARCHAR(50)  NOT NULL,           -- general | header | hero | marquee | token | exchange | footer | seo | social | scripts | features | work | crypto | roadmap | team | faq | brands
  `skey`       VARCHAR(80)  NOT NULL,
  `svalue`     LONGTEXT     NULL,
  `is_publish` TINYINT(1)   NOT NULL DEFAULT 1, -- 0 = draft, 1 = published
  `update_date` DATETIME    NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_section_key` (`section`,`skey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 2) Header navigation (repeater, supports dropdown via parent_id)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `landing_menu` (
  `id`        INT(11) NOT NULL AUTO_INCREMENT,
  `parent_id` INT(11) NOT NULL DEFAULT 0,
  `title`     VARCHAR(120) NOT NULL,
  `url`       VARCHAR(255) NOT NULL DEFAULT '#',
  `new_tab`   TINYINT(1)   NOT NULL DEFAULT 0,
  `is_external` TINYINT(1) NOT NULL DEFAULT 0,
  `sort_order` INT(11)     NOT NULL DEFAULT 0,
  `status`    TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 3) Brands (logos carousel)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `landing_brands` (
  `id`        INT(11) NOT NULL AUTO_INCREMENT,
  `image`     VARCHAR(255) NOT NULL,
  `alt`       VARCHAR(120) NULL,
  `sort_order` INT(11)     NOT NULL DEFAULT 0,
  `status`    TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 4) Features
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `landing_features` (
  `id`          INT(11) NOT NULL AUTO_INCREMENT,
  `title`       VARCHAR(150) NOT NULL,
  `highlight`   VARCHAR(150) NULL,        -- the <span> highlighted word
  `description` TEXT         NULL,
  `icon`        VARCHAR(255) NULL,        -- image path
  `sort_order`  INT(11)      NOT NULL DEFAULT 0,
  `status`      TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 5) How It Works steps
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `landing_work` (
  `id`          INT(11) NOT NULL AUTO_INCREMENT,
  `number`      VARCHAR(10)  NULL,
  `title`       VARCHAR(150) NOT NULL,
  `highlight`   VARCHAR(150) NULL,
  `description` TEXT         NULL,
  `image`       VARCHAR(255) NULL,
  `sort_order`  INT(11)      NOT NULL DEFAULT 0,
  `status`      TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 6) Exchange logos (the small icon row)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `landing_exchange_logos` (
  `id`        INT(11) NOT NULL AUTO_INCREMENT,
  `image`     VARCHAR(255) NOT NULL,
  `sort_order` INT(11)     NOT NULL DEFAULT 0,
  `status`    TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 7) Crypto cards
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `landing_crypto` (
  `id`          INT(11) NOT NULL AUTO_INCREMENT,
  `title`       VARCHAR(150) NOT NULL,
  `highlight`   VARCHAR(150) NULL,
  `button_text` VARCHAR(120) NULL,
  `button_link` VARCHAR(255) NULL,
  `icon`        VARCHAR(255) NULL,
  `sort_order`  INT(11)      NOT NULL DEFAULT 0,
  `status`      TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 8) FAQ
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `landing_faq` (
  `id`         INT(11) NOT NULL AUTO_INCREMENT,
  `question`   VARCHAR(255) NOT NULL,
  `answer`     TEXT         NULL,
  `sort_order` INT(11)      NOT NULL DEFAULT 0,
  `status`     TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 9) Roadmap
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `landing_roadmap` (
  `id`          INT(11) NOT NULL AUTO_INCREMENT,
  `year`        VARCHAR(20)  NULL,
  `title`       VARCHAR(150) NULL,
  `description` TEXT         NULL,
  `icon`        VARCHAR(255) NULL,
  `sort_order`  INT(11)      NOT NULL DEFAULT 0,
  `status`      TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 10) Team
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `landing_team` (
  `id`        INT(11) NOT NULL AUTO_INCREMENT,
  `photo`     VARCHAR(255) NULL,
  `name`      VARCHAR(150) NOT NULL,
  `position`  VARCHAR(120) NULL,
  `facebook`  VARCHAR(255) NULL,
  `twitter`   VARCHAR(255) NULL,
  `telegram`  VARCHAR(255) NULL,
  `discord`   VARCHAR(255) NULL,
  `linkedin`  VARCHAR(255) NULL,
  `sort_order` INT(11)     NOT NULL DEFAULT 0,
  `status`    TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 11) Version history (snapshot JSON for export/import/restore)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `landing_versions` (
  `id`        INT(11) NOT NULL AUTO_INCREMENT,
  `label`     VARCHAR(150) NULL,
  `snapshot`  LONGTEXT     NOT NULL,     -- full JSON of every section
  `created_by` INT(11)     NULL,
  `created_at` DATETIME    NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
--  SEED  —  defaults transcribed from the provided index.html so the
--  page renders identically before the admin edits anything.
-- =====================================================================

-- General -------------------------------------------------------------
INSERT INTO `landing_settings` (`section`,`skey`,`svalue`) VALUES
('general','site_name','Webze'),
('general','logo','assets/img/logo/logo.svg'),
('general','logo_dark','assets/img/logo/logo.svg'),
('general','favicon','assets/img/favicon.png'),
('general','primary_color','#7857FE'),
('general','secondary_color','#0B0B23'),
('general','button_color','#7857FE'),
('general','background_color','#0B0B23'),
('general','font_family','Inter'),
('general','enable_preloader','1'),
('general','enable_dark_mode','1'),
('general','copyright','Copyright & design by @ThemeAdapt - 2026'),
('general','footer_text','Built on web3. Powered by You')
ON DUPLICATE KEY UPDATE svalue=VALUES(svalue);

-- Header --------------------------------------------------------------
INSERT INTO `landing_settings` (`section`,`skey`,`svalue`) VALUES
('header','logo','assets/img/logo/logo.svg'),
('header','mobile_logo','assets/img/logo/logo.svg'),
('header','buy_btn_text','Register'),
('header','buy_btn_url','register.html'),
('header','sticky_header','1'),
('header','transparent_header','1')
ON DUPLICATE KEY UPDATE svalue=VALUES(svalue);

-- Hero ----------------------------------------------------------------
INSERT INTO `landing_settings` (`section`,`skey`,`svalue`) VALUES
('hero','small_title','Built on web3. Powered by You'),
('hero','main_title','The future of leverage is here'),
('hero','highlight_text','future'),
('hero','description','Leverage on any tokens with a protocol trusted with billions for its performance and reliability.'),
('hero','email_placeholder','Business email'),
('hero','button_text','get early access'),
('hero','button_link','#'),
('hero','bottom_text','Start monitoring for free or'),
('hero','bottom_link_text','message us!'),
('hero','bottom_link','contact.html'),
('hero','bg_image','assets/img/banner/hero_bg.svg'),
('hero','hero_img1','assets/img/banner/hero_img01.png'),
('hero','hero_img2','assets/img/banner/hero_img02.png'),
('hero','hero_img3','assets/img/banner/hero_img03.png')
ON DUPLICATE KEY UPDATE svalue=VALUES(svalue);

-- Features section heading -------------------------------------------
INSERT INTO `landing_settings` (`section`,`skey`,`svalue`) VALUES
('features','sub_title','accessible for everyone'),
('features','title','Crypto development accessible'),
('features','highlight','development')
ON DUPLICATE KEY UPDATE svalue=VALUES(svalue);

-- Marquee -------------------------------------------------------------
INSERT INTO `landing_settings` (`section`,`skey`,`svalue`) VALUES
('marquee','text','You will hold the way you love Webzo'),
('marquee','speed','50'),
('marquee','repeat','2'),
('marquee','enable','1')
ON DUPLICATE KEY UPDATE svalue=VALUES(svalue);

-- Token sale ----------------------------------------------------------
INSERT INTO `landing_settings` (`section`,`skey`,`svalue`) VALUES
('token','sub_title','accessible for everyone'),
('token','title','Trading platform of the future!'),
('token','highlight','platform'),
('token','description','Webzi brings our love for cryptocurrency into Web3! Like a frog''s leap, the chart can jump at any moment. Boom!'),
('token','button_text','purchase now'),
('token','button_link','#'),
('token','countdown_date','2026/12/30'),
('token','received_text','contribution received'),
('token','contribution_amount','$49,222,300'),
('token','min_goal','$5M'),
('token','max_goal','$99M'),
('token','wallet_address','0x2170Ed0880ac9A755fd29B2688956BD959F933F8'),
('token','progress_percentage','50')
ON DUPLICATE KEY UPDATE svalue=VALUES(svalue);

-- Work section heading -----------------------------------------------
INSERT INTO `landing_settings` (`section`,`skey`,`svalue`) VALUES
('work','sub_title','how it works?'),
('work','title','Core asset of the crypto marketplace'),
('work','highlight','crypto'),
('work','image','assets/img/images/work_img.png')
ON DUPLICATE KEY UPDATE svalue=VALUES(svalue);

-- Exchange ------------------------------------------------------------
INSERT INTO `landing_settings` (`section`,`skey`,`svalue`) VALUES
('exchange','title','Exchange availability'),
('exchange','highlight','availability'),
('exchange','description','AI-powered tools to detect and prevent fraudulent activities.'),
('exchange','main_image','assets/img/images/exchange_img.png'),
('exchange','enable','1')
ON DUPLICATE KEY UPDATE svalue=VALUES(svalue);

-- Crypto section heading ---------------------------------------------
INSERT INTO `landing_settings` (`section`,`skey`,`svalue`) VALUES
('crypto','sub_title','crypto direction'),
('crypto','title','Goods & assets according to users interests.'),
('crypto','highlight','according')
ON DUPLICATE KEY UPDATE svalue=VALUES(svalue);

-- FAQ section heading -------------------------------------------------
INSERT INTO `landing_settings` (`section`,`skey`,`svalue`) VALUES
('faq','sub_title','faq & ans'),
('faq','title','Get every single answer'),
('faq','highlight','single'),
('faq','image','assets/img/images/faq_img.png')
ON DUPLICATE KEY UPDATE svalue=VALUES(svalue);

-- Roadmap section heading --------------------------------------------
INSERT INTO `landing_settings` (`section`,`skey`,`svalue`) VALUES
('roadmap','sub_title','roadmap'),
('roadmap','title','Our strategy & Planning'),
('roadmap','highlight','strategy')
ON DUPLICATE KEY UPDATE svalue=VALUES(svalue);

-- Team section heading -----------------------------------------------
INSERT INTO `landing_settings` (`section`,`skey`,`svalue`) VALUES
('team','sub_title','our avengers'),
('team','title','Meet with our avengers!'),
('team','highlight','our'),
('team','description','Webzi brings our love for cryptocurrency into Web3! Like a frog''s leap, the chart can jump at any moment. Boom!')
ON DUPLICATE KEY UPDATE svalue=VALUES(svalue);

-- Footer --------------------------------------------------------------
INSERT INTO `landing_settings` (`section`,`skey`,`svalue`) VALUES
('footer','logo','assets/img/logo/logo.svg'),
('footer','sub_title','Built on web3. Powered by You'),
('footer','title','Join with our future of Webzo currency'),
('footer','highlight','future'),
('footer','copyright','Copyright & design by @ThemeAdapt - 2026'),
('footer','bg_image1','assets/img/images/footer_shape01.png'),
('footer','bg_image2','assets/img/images/footer_shape02.png')
ON DUPLICATE KEY UPDATE svalue=VALUES(svalue);

-- SEO -----------------------------------------------------------------
INSERT INTO `landing_settings` (`section`,`skey`,`svalue`) VALUES
('seo','meta_title','Webze - ICO & Crypto Landing Page Template'),
('seo','meta_description','Webze - ICO & Crypto Landing Page Template'),
('seo','meta_keywords','crypto, ico, token, web3, blockchain'),
('seo','og_image','assets/img/favicon.png'),
('seo','twitter_card','summary_large_image'),
('seo','robots','index, follow'),
('seo','canonical','')
ON DUPLICATE KEY UPDATE svalue=VALUES(svalue);

-- Social --------------------------------------------------------------
INSERT INTO `landing_settings` (`section`,`skey`,`svalue`) VALUES
('social','facebook','#'),
('social','twitter','#'),
('social','telegram','#'),
('social','discord','#'),
('social','instagram','#'),
('social','linkedin','#'),
('social','youtube','#'),
('social','github','')
ON DUPLICATE KEY UPDATE svalue=VALUES(svalue);

-- Custom scripts ------------------------------------------------------
INSERT INTO `landing_settings` (`section`,`skey`,`svalue`) VALUES
('scripts','header_scripts',''),
('scripts','footer_scripts',''),
('scripts','google_analytics',''),
('scripts','facebook_pixel',''),
('scripts','custom_css',''),
('scripts','custom_js','')
ON DUPLICATE KEY UPDATE svalue=VALUES(svalue);

-- Navigation menu -----------------------------------------------------
INSERT INTO `landing_menu` (`title`,`url`,`new_tab`,`sort_order`,`status`) VALUES
('Home','index.html',0,1,1),
('Login','login.html',0,2,1),
('Register','register.html',0,3,1);

-- Brands --------------------------------------------------------------
INSERT INTO `landing_brands` (`image`,`sort_order`) VALUES
('assets/img/brand/brand_01.svg',1),
('assets/img/brand/brand_02.svg',2),
('assets/img/brand/brand_03.svg',3),
('assets/img/brand/brand_04.svg',4),
('assets/img/brand/brand_05.svg',5),
('assets/img/brand/brand_06.svg',6);

-- Features ------------------------------------------------------------
INSERT INTO `landing_features` (`title`,`highlight`,`description`,`icon`,`sort_order`) VALUES
('Crypto management','management','Automated identity verification and anti-money','assets/img/icon/features_icon01.png',1),
('Crypto exchange','exchange','A built-in explorer to track transactions','assets/img/icon/features_icon02.png',2),
('Real-time data','data','Global reach with content available in multiple','assets/img/icon/features_icon03.png',3),
('Advanced trading','trading','Visual dashboards for trade performance','assets/img/icon/features_icon04.png',4),
('Blockchain compliance','compliance','Exportable reports for tax and accounting purposes','assets/img/icon/features_icon05.png',5);

-- How It Works --------------------------------------------------------
INSERT INTO `landing_work` (`number`,`title`,`highlight`,`description`,`sort_order`) VALUES
('01','Currency conversion','conversion','Exportable reports for tax and accounting purposes.',1),
('02','Data encryption','encryption','Visual dashboards for trade performance.',2),
('03','Cold wallet storage','storage','Regular updates on crypto trends and platform features.',3),
('04','Transfer crypto & data','& data','Guides for beginners on crypto basics and trading.',4);

-- Exchange logos ------------------------------------------------------
INSERT INTO `landing_exchange_logos` (`image`,`sort_order`) VALUES
('assets/img/icon/exchange_icon01.svg',1),
('assets/img/icon/exchange_icon02.svg',2),
('assets/img/icon/exchange_icon03.svg',3),
('assets/img/icon/exchange_icon04.svg',4);

-- Crypto cards --------------------------------------------------------
INSERT INTO `landing_crypto` (`title`,`highlight`,`button_text`,`button_link`,`icon`,`sort_order`) VALUES
('Read our white paper','white paper','open whitepaper','#','assets/img/icon/crypto_icon01.svg',1),
('1 CRN token price 0.00014 BTC','0.00014 BTC','Buy tokens (-25%)','#','assets/img/icon/crypto_icon02.svg',2),
('ICO Participants 370,000+','370,000+','join our telegram','#','assets/img/icon/crypto_icon03.svg',3);

-- FAQ -----------------------------------------------------------------
INSERT INTO `landing_faq` (`question`,`answer`,`sort_order`) VALUES
('Main purpose of a cryptocurrency','The private key, stored securely in the wallet, allows you to sign transactions and prove ownership of the funds cryptocurrency wallet.',1),
('How can I make refund?','The private key, stored securely in the wallet, allows you to sign transactions and prove ownership of the funds cryptocurrency wallet.',2),
('How do they operate on blockchain?','The private key, stored securely in the wallet, allows you to sign transactions and prove ownership of the funds cryptocurrency wallet.',3);

-- Roadmap -------------------------------------------------------------
INSERT INTO `landing_roadmap` (`year`,`description`,`icon`,`sort_order`) VALUES
('2014','Definitions of key terms in cryptocurrency','assets/img/icon/roadmap_icon01.png',1),
('2017','Automated tools for executing strategies','assets/img/icon/roadmap_icon02.png',2),
('2022','APIs for developers to build custom tools','assets/img/icon/roadmap_icon03.png',3),
('2026','A space for users to discuss trends','assets/img/icon/roadmap_icon04.png',4);

-- Team ----------------------------------------------------------------
INSERT INTO `landing_team` (`photo`,`name`,`position`,`telegram`,`facebook`,`twitter`,`sort_order`) VALUES
('assets/img/team/team_img01.png','Rosalina William','founder','','#','',1),
('assets/img/team/team_img02.png','Alonso Dowson','ceo','#','','',2),
('assets/img/team/team_img03.png','Elson Nelzoon','Designer','','','#',3),
('assets/img/team/team_img04.png','Miranda Halim','developer','#','','',4);

-- =====================================================================
--  Grant the menu permission key used by the controller's role check.
--  NOTE: admin_roll = '1' are the RESTRICTED sub-admins that are checked
--  against permission_pages (super admins have admin_roll <> '1' and skip
--  the check).  The controller also accepts the existing
--  'website_content_cms' key, so this is only needed if you want the key
--  to appear explicitly.  Run against the restricted admins:
-- =====================================================================
-- UPDATE users
--    SET permission_pages = JSON_SET(permission_pages, '$.landing_page_cms', 1)
--  WHERE admin_roll = '1';
