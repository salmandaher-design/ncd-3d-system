-- =====================================================================
--  NCD 3D Print — MySQL / MariaDB schema + sample data
-- ---------------------------------------------------------------------
--  HOW TO IMPORT
--  • InfinityFree: create a database in the control panel, open
--    phpMyAdmin for it, then Import this file. (Do NOT run the two
--    CREATE DATABASE / USE lines below — they are commented out.)
--  • Local (XAMPP/MAMP): uncomment the two lines below to auto-create.
--
--  SAMPLE LOGINS (change these after first sign-in):
--    Administrator :  admin@ncd.sy   / admin123
--    Team member   :  ahmad@ncd.sy   / member123
--    Team member   :  rana@ncd.sy    / member123
--    Team member   :  yousef@ncd.sy  / member123
-- =====================================================================

-- CREATE DATABASE IF NOT EXISTS `ncd_printing` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE `ncd_printing`;

-- Make sure the Arabic sample text is read as UTF-8 by every client.
SET NAMES utf8mb4;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `activity_logs`;
DROP TABLE IF EXISTS `news`;
DROP TABLE IF EXISTS `request_files`;
DROP TABLE IF EXISTS `requests`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `filament`;
DROP TABLE IF EXISTS `printers`;
DROP TABLE IF EXISTS `teams`;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- teams
-- ---------------------------------------------------------------------
CREATE TABLE `teams` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(120) NOT NULL,
    `competition` VARCHAR(160) DEFAULT NULL,
    `supervisor`  VARCHAR(120) DEFAULT NULL,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- printers  (the center has exactly two)
-- ---------------------------------------------------------------------
CREATE TABLE `printers` (
    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`             VARCHAR(80) NOT NULL,
    `status`           ENUM('Idle','Busy') NOT NULL DEFAULT 'Idle',
    `current_project`  VARCHAR(160) DEFAULT NULL,
    `current_team`     VARCHAR(120) DEFAULT NULL,
    `current_operator` VARCHAR(120) DEFAULT NULL,
    `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- filament inventory
-- ---------------------------------------------------------------------
CREATE TABLE `filament` (
    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `color`            VARCHAR(80) NOT NULL,
    `material`         VARCHAR(60) DEFAULT NULL,
    `remaining_weight` DECIMAL(8,1) NOT NULL DEFAULT 0,
    `spools`           INT UNSIGNED NOT NULL DEFAULT 1,
    `location`         VARCHAR(120) DEFAULT NULL,
    `notes`            TEXT DEFAULT NULL,
    `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- users  (accounts are created by the administrator only)
-- ---------------------------------------------------------------------
CREATE TABLE `users` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(120) NOT NULL,
    `email`      VARCHAR(160) NOT NULL,
    `password`   VARCHAR(255) NOT NULL,
    `role`       ENUM('admin','member') NOT NULL DEFAULT 'member',
    `team_id`    INT UNSIGNED DEFAULT NULL,
    `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`),
    KEY `idx_users_team` (`team_id`),
    CONSTRAINT `fk_users_team` FOREIGN KEY (`team_id`) REFERENCES `teams`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- requests
-- ---------------------------------------------------------------------
CREATE TABLE `requests` (
    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`          INT UNSIGNED DEFAULT NULL,
    `team_id`          INT UNSIGNED DEFAULT NULL,
    `project_name`     VARCHAR(160) NOT NULL,
    `description`      TEXT DEFAULT NULL,
    `priority`         ENUM('Low','Medium','High') NOT NULL DEFAULT 'Medium',
    `color`            VARCHAR(80) DEFAULT NULL,
    `transaction_no`   VARCHAR(100) DEFAULT NULL,
    `status`           ENUM('Submitted','Approved','Printing','Completed','Rejected','Cancelled') NOT NULL DEFAULT 'Submitted',
    `image_path`       VARCHAR(255) DEFAULT NULL,
    `estimated_weight` DECIMAL(8,1) DEFAULT NULL,
    `actual_weight`    DECIMAL(8,1) DEFAULT NULL,
    `admin_notes`      TEXT DEFAULT NULL,
    `printer_id`       INT UNSIGNED DEFAULT NULL,
    `filament_id`      INT UNSIGNED DEFAULT NULL,
    `filament_deducted` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_req_user`   (`user_id`),
    KEY `idx_req_team`   (`team_id`),
    KEY `idx_req_status` (`status`),
    KEY `idx_req_printer`(`printer_id`),
    KEY `idx_req_fil`    (`filament_id`),
    CONSTRAINT `fk_req_user`    FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)    ON DELETE SET NULL,
    CONSTRAINT `fk_req_team`    FOREIGN KEY (`team_id`)    REFERENCES `teams`(`id`)    ON DELETE SET NULL,
    CONSTRAINT `fk_req_printer` FOREIGN KEY (`printer_id`) REFERENCES `printers`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_req_fil`     FOREIGN KEY (`filament_id`)REFERENCES `filament`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- request_files
-- ---------------------------------------------------------------------
CREATE TABLE `request_files` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `request_id` INT UNSIGNED NOT NULL,
    `file_name`  VARCHAR(255) NOT NULL,
    `file_path`  VARCHAR(255) NOT NULL,
    `file_size`  INT UNSIGNED NOT NULL DEFAULT 0,
    `file_type`  VARCHAR(20)  DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_rf_request` (`request_id`),
    CONSTRAINT `fk_rf_request` FOREIGN KEY (`request_id`) REFERENCES `requests`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- news  (dashboard banner + news archive)
--   The most recent row is shown as the dashboard banner; older rows
--   automatically become "الأخبار القديمة".
-- ---------------------------------------------------------------------
CREATE TABLE `news` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title`      VARCHAR(200) NOT NULL,
    `content`    TEXT DEFAULT NULL,
    `image_path` VARCHAR(255) DEFAULT NULL,
    `user_id`    INT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_news_created` (`created_at`),
    KEY `idx_news_user` (`user_id`),
    CONSTRAINT `fk_news_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- activity_logs
-- ---------------------------------------------------------------------
CREATE TABLE `activity_logs` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`     INT UNSIGNED DEFAULT NULL,
    `action`      VARCHAR(60) NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_log_user` (`user_id`),
    CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
--  SAMPLE DATA
-- =====================================================================

INSERT INTO `teams` (`id`,`name`,`competition`,`supervisor`) VALUES
(1,'NCD Robotics A','FIRST Global Challenge','Dr. Sami Haddad'),
(2,'NCD Robotics B','VEX Robotics','Eng. Lina Khoury'),
(3,'NCD Falcons','FIRST Tech Challenge','Eng. Omar Nasser');

INSERT INTO `printers` (`id`,`name`,`status`,`current_project`,`current_team`,`current_operator`) VALUES
(1,'Printer 1','Busy','Wheel Hub Adapter','NCD Falcons','System Administrator'),
(2,'Printer 2','Idle',NULL,NULL,NULL);

INSERT INTO `filament` (`id`,`color`,`material`,`remaining_weight`,`spools`,`location`,`notes`) VALUES
(1,'Black','PLA',850.0,3,'Shelf A1','Main workhorse spool'),
(2,'White','PLA',250.0,1,'Shelf A2','Running low — reorder soon'),
(3,'Gray','PETG',80.0,1,'Shelf B1','Critical — almost empty'),
(4,'Custom','PLA',1200.0,2,'Shelf B2','Currently loaded: blue');

-- Passwords: admin = admin123 ; members = member123  (bcrypt, $2y$)
INSERT INTO `users` (`id`,`name`,`email`,`password`,`role`,`team_id`,`is_active`) VALUES
(1,'System Administrator','admin@ncd.sy','$2y$10$smqCZ4YQwFuFYVj7cjuOyupRwyu8HEbkTI65OtJKwHQait5eoieUS','admin',NULL,1),
(2,'Ahmad Saleh','ahmad@ncd.sy','$2y$10$sH/.cH7CrwkkehjUtchu5uvgRYKB.euUF0K93cvXzXwQpMe20nuQ2','member',1,1),
(3,'Rana Deeb','rana@ncd.sy','$2y$10$5G4nc8aWeY4u4IMqJRBAZ.SiPJ2juADdt5/mfiUa9UwE.k.CubA76','member',2,1),
(4,'Yousef Ali','yousef@ncd.sy','$2y$10$1O4rEpVeygGe6ByoPuiOA.WbnoBKNGh91LZ1q0q0cbuJbDtnrJnVK','member',3,1);

INSERT INTO `requests`
(`id`,`user_id`,`team_id`,`project_name`,`description`,`priority`,`color`,`status`,`image_path`,`estimated_weight`,`actual_weight`,`admin_notes`,`printer_id`,`filament_id`,`created_at`,`updated_at`) VALUES
(1,2,1,'Gripper Base Plate','Base plate for the new gripper assembly. Needs to be sturdy.','High','Black','Submitted',NULL,NULL,NULL,NULL,NULL,NULL,DATE_SUB(NOW(),INTERVAL 2 DAY),DATE_SUB(NOW(),INTERVAL 2 DAY)),
(2,3,2,'Sensor Mount','Small mount for the distance sensor.','Medium','White','Submitted',NULL,NULL,NULL,NULL,NULL,NULL,DATE_SUB(NOW(),INTERVAL 1 DAY),DATE_SUB(NOW(),INTERVAL 1 DAY)),
(3,2,1,'Robot Arm Gripper v2','Second revision of the gripper — tighter tolerances.','High','Black','Approved',NULL,60.0,NULL,'Looks good, approved for printing.',NULL,NULL,DATE_SUB(NOW(),INTERVAL 5 DAY),DATE_SUB(NOW(),INTERVAL 4 DAY)),
(4,4,3,'Wheel Hub Adapter','Adapter to fit the new wheels onto the drivetrain.','Medium','Gray','Printing',NULL,55.0,NULL,'Printing on Printer 1 with gray PETG.',1,3,DATE_SUB(NOW(),INTERVAL 3 DAY),DATE_SUB(NOW(),INTERVAL 6 HOUR)),
(5,3,2,'Cable Guide Clip','Clips to route cables along the chassis.','Low','White','Completed',NULL,45.0,42.0,'Printed fine, minor stringing.',NULL,2,DATE_SUB(NOW(),INTERVAL 35 DAY),DATE_SUB(NOW(),INTERVAL 32 DAY)),
(6,2,1,'Chassis Bracket','Corner bracket for the main chassis frame.','Medium','Black','Completed',NULL,110.0,118.0,'Used a bit more than estimated.',NULL,1,DATE_SUB(NOW(),INTERVAL 65 DAY),DATE_SUB(NOW(),INTERVAL 62 DAY)),
(7,4,3,'Old Prototype Cover','Cover for the very first prototype.','Low','Gray','Rejected',NULL,NULL,NULL,'Superseded by a newer design.',NULL,NULL,DATE_SUB(NOW(),INTERVAL 10 DAY),DATE_SUB(NOW(),INTERVAL 9 DAY)),
(8,3,2,'Duplicate Mount','Accidental duplicate submission.','Medium','White','Cancelled',NULL,NULL,NULL,'Cancelled — duplicate of Sensor Mount.',NULL,NULL,DATE_SUB(NOW(),INTERVAL 7 DAY),DATE_SUB(NOW(),INTERVAL 7 DAY));

-- Sample file rows (placeholders — the physical files are not shipped;
-- real files appear here once users upload through the app).
INSERT INTO `request_files` (`request_id`,`file_name`,`file_path`,`file_size`,`file_type`) VALUES
(3,'gripper_v2.stl','uploads/files/sample_gripper_v2.stl',1548200,'stl'),
(4,'wheel_hub.3mf','uploads/files/sample_wheel_hub.3mf',892400,'3mf');

-- News: the newest row is the dashboard banner, the rest are the archive.
INSERT INTO `news` (`title`,`content`,`image_path`,`user_id`,`created_at`) VALUES
('افتتاح مخبر الروبوت والذكاء الصنعي','يسر المركز الوطني للمتميزين الإعلان عن تجهيز مخبر الروبوت والذكاء الصنعي بطابعتين ثلاثيتي الأبعاد من نوع Bambu Lab، وهي متاحة الآن لجميع فرق الروبوتات عبر هذا النظام.',NULL,1,DATE_SUB(NOW(),INTERVAL 20 DAY)),
('تعليمات تسليم ملفات الطباعة','نرجو من جميع الفرق إرفاق ملفات STL أو 3MF بدقة، والتأكد من أبعاد القطعة قبل إرسال الطلب لتفادي إعادة الطباعة وهدر الفيلامنت.',NULL,1,DATE_SUB(NOW(),INTERVAL 7 DAY)),
('جاهزية المخبر لاستقبال طلبات الطباعة','المخبر جاهز لاستقبال طلباتكم لهذا الفصل. يُرجى تقديم الطلبات قبل موعد المسابقة بأسبوعين على الأقل لضمان إنجازها في الوقت المناسب.',NULL,1,DATE_SUB(NOW(),INTERVAL 1 DAY));

INSERT INTO `activity_logs` (`user_id`,`action`,`description`,`created_at`) VALUES
(1,'request_complete','Completed request #6 (118 g used)',DATE_SUB(NOW(),INTERVAL 62 DAY)),
(1,'request_complete','Completed request #5 (42 g used)',DATE_SUB(NOW(),INTERVAL 32 DAY)),
(1,'request_reject','Rejected request #7',DATE_SUB(NOW(),INTERVAL 9 DAY)),
(2,'request_create','Submitted request #3 — Robot Arm Gripper v2',DATE_SUB(NOW(),INTERVAL 5 DAY)),
(1,'request_print','Started printing request #4',DATE_SUB(NOW(),INTERVAL 6 HOUR)),
(1,'login','User signed in',DATE_SUB(NOW(),INTERVAL 2 HOUR));
