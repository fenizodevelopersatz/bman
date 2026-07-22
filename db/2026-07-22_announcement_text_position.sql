-- Adds admin-controllable text placement over the banner image, and lets
-- "Image Only" announcements render with truly zero text overlay (the title
-- previously always rendered regardless of type).
ALTER TABLE `announcement`
  ADD COLUMN `text_position` ENUM('middle-left','top-left','bottom-left','center')
    NOT NULL DEFAULT 'middle-left' AFTER `text_color`;
