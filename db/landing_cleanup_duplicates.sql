-- =====================================================================
--  Remove duplicate landing repeater rows (caused by re-importing the
--  seed data) and tidy the navigation. Keeps the lowest id of each group.
--  Safe to run multiple times.
--    mysql -u user -p db < db/landing_cleanup_duplicates.sql
-- =====================================================================

-- 1) Navigation menu — dedupe by title+url ---------------------------
DELETE m1 FROM `landing_menu` m1
JOIN `landing_menu` m2
  ON m1.`title` = m2.`title` AND m1.`url` = m2.`url` AND m1.`id` > m2.`id`;

-- 2) Team — dedupe by member name (removes repeated founder, etc.) ---
DELETE t1 FROM `landing_team` t1
JOIN `landing_team` t2
  ON t1.`name` = t2.`name` AND t1.`id` > t2.`id`;

-- 3) Other repeaters — dedupe so a double-import can't show duplicates -
DELETE b1 FROM `landing_brands` b1
JOIN `landing_brands` b2 ON b1.`image` = b2.`image` AND b1.`id` > b2.`id`;

DELETE f1 FROM `landing_features` f1
JOIN `landing_features` f2 ON f1.`title` = f2.`title` AND f1.`id` > f2.`id`;

DELETE w1 FROM `landing_work` w1
JOIN `landing_work` w2 ON w1.`number` = w2.`number` AND w1.`title` = w2.`title` AND w1.`id` > w2.`id`;

DELETE e1 FROM `landing_exchange_logos` e1
JOIN `landing_exchange_logos` e2 ON e1.`image` = e2.`image` AND e1.`id` > e2.`id`;

DELETE c1 FROM `landing_crypto` c1
JOIN `landing_crypto` c2 ON c1.`title` = c2.`title` AND c1.`id` > c2.`id`;

DELETE q1 FROM `landing_faq` q1
JOIN `landing_faq` q2 ON q1.`question` = q2.`question` AND q1.`id` > q2.`id`;

DELETE r1 FROM `landing_roadmap` r1
JOIN `landing_roadmap` r2 ON r1.`year` = r2.`year` AND r1.`description` = r2.`description` AND r1.`id` > r2.`id`;

-- 4) Navigation tidy — drop the Register item (it is the header CTA),
--    point Home/Login at real pages. (No "repeat" in the top menu.) -----
DELETE FROM `landing_menu` WHERE `title` = 'Register';
UPDATE `landing_menu` SET `url` = 'landing'  WHERE `title` = 'Home';
UPDATE `landing_menu` SET `url` = 'user/in'  WHERE `title` = 'Login';
