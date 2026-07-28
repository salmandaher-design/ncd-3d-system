-- =====================================================================
--  NCD 3D Print — upgrade for an EXISTING database
-- ---------------------------------------------------------------------
--  Run this ONCE in phpMyAdmin (Import or SQL tab) if you already
--  imported an older database.sql. It only ADDS the new columns needed
--  for: filament selection at approval, the registration transaction
--  number, and the spool count. Existing data is preserved.
--
--  Safe to run more than once (uses IF NOT EXISTS, supported by MariaDB).
-- =====================================================================

-- Make sure the Arabic text is read as UTF-8 by every client.
SET NAMES utf8mb4;

ALTER TABLE `requests`
    ADD COLUMN IF NOT EXISTS `transaction_no`    VARCHAR(100) DEFAULT NULL AFTER `color`,
    ADD COLUMN IF NOT EXISTS `filament_deducted` TINYINT(1) NOT NULL DEFAULT 0 AFTER `filament_id`;

ALTER TABLE `filament`
    ADD COLUMN IF NOT EXISTS `spools` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `remaining_weight`;

-- Number of prints required of each uploaded file.
-- Existing files (previous orders) automatically get 1 via the default.
ALTER TABLE `request_files`
    ADD COLUMN IF NOT EXISTS `quantity` INT UNSIGNED NOT NULL DEFAULT 1 AFTER `file_type`;
UPDATE `request_files` SET `quantity` = 1 WHERE `quantity` IS NULL OR `quantity` < 1;

-- Optional: give any EXISTING requests an automatic transaction number.
-- New requests get one automatically from the app. Change 'NCD' if you set a
-- different TRANSACTION_PREFIX in config/config.php.
UPDATE `requests`
SET `transaction_no` = CONCAT('NCD-', YEAR(`created_at`), '-', LPAD(`id`, 4, '0'))
WHERE `transaction_no` IS NULL OR `transaction_no` = '';

-- ---------------------------------------------------------------------
-- News / dashboard banner (added later — safe if it already exists)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `news` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`      VARCHAR(200) NOT NULL,
    `content`    TEXT DEFAULT NULL,
    `image_path` VARCHAR(255) DEFAULT NULL,
    `user_id`    INT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_news_created` (`created_at`),
    KEY `idx_news_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A first banner message so the dashboard isn't empty (only if the table is empty).
INSERT INTO `news` (`title`,`content`,`created_at`)
SELECT 'مرحباً بكم في نظام طلبات الطباعة ثلاثية الأبعاد',
       'يمكن لفرق الروبوتات تقديم طلبات الطباعة ومتابعة حالتها من خلال هذا النظام.',
       NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `news`);
