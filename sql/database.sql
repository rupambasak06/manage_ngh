-- =============================================================================
-- Hotel Room Daily Checklist & Inventory System
-- MySQL 5.7+ / MySQL 8.0+ / MariaDB 10.3+ Schema & Seed Data
-- Charset: utf8mb4 (full Bengali Unicode support)
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- 1. Users & Authentication Rate Limiting
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(120) NOT NULL,
    `email` VARCHAR(190) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('owner', 'manager', 'staff') NOT NULL DEFAULT 'staff',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `last_login_at` DATETIME NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`),
    KEY `idx_users_role_active` (`role`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ip_address` VARCHAR(45) NOT NULL,
    `email` VARCHAR(190) NOT NULL DEFAULT '',
    `attempted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_login_attempts_ip_time` (`ip_address`, `attempted_at`),
    KEY `idx_login_attempts_email_time` (`email`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2. Floors & Rooms
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `floors` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_floors_order` (`sort_order`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rooms` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `floor_id` INT UNSIGNED NOT NULL,
    `room_number` VARCHAR(30) NOT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_rooms_number` (`room_number`),
    KEY `idx_rooms_floor_order` (`floor_id`, `sort_order`, `is_active`),
    CONSTRAINT `fk_rooms_floor` FOREIGN KEY (`floor_id`) REFERENCES `floors` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 3. Checklist Sections & Options (Entities)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sections` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(150) NOT NULL,
    `counts_toward_checkout` TINYINT(1) NOT NULL DEFAULT 0,
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_sections_order` (`sort_order`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `options` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `section_id` INT UNSIGNED NOT NULL,
    `label` VARCHAR(150) NOT NULL,
    `type` ENUM('checkbox', 'counter') NOT NULL DEFAULT 'checkbox',
    `max_count` SMALLINT UNSIGNED NOT NULL DEFAULT 10,
    `applies_to` ENUM('all', 'selected') NOT NULL DEFAULT 'all',
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_options_section_order` (`section_id`, `sort_order`, `is_active`),
    CONSTRAINT `fk_options_section` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `option_rooms` (
    `option_id` INT UNSIGNED NOT NULL,
    `room_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`option_id`, `room_id`),
    KEY `idx_option_rooms_room` (`room_id`),
    CONSTRAINT `fk_option_rooms_option` FOREIGN KEY (`option_id`) REFERENCES `options` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_option_rooms_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 4. Extras (Items Given Extra to Guests)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `extras` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `label` VARCHAR(150) NOT NULL,
    `max_count` SMALLINT UNSIGNED NOT NULL DEFAULT 20,
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_extras_order` (`sort_order`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 5. Versioned Room Defaults (effective_from preserves historical accuracy)
--    qty = 0 means excluded from default starting on effective_from date
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `room_defaults` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `room_id` INT UNSIGNED NOT NULL,
    `option_id` INT UNSIGNED NOT NULL,
    `qty` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    `effective_from` DATE NOT NULL,
    `updated_by` INT UNSIGNED NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_room_defaults_ver` (`room_id`, `option_id`, `effective_from`),
    KEY `idx_room_defaults_lookup` (`room_id`, `option_id`, `effective_from`),
    KEY `idx_room_defaults_eff` (`effective_from`),
    CONSTRAINT `fk_rd_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_rd_option` FOREIGN KEY (`option_id`) REFERENCES `options` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_rd_user` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 6. Daily Checklist Data (Idempotent Upsert Tables)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `daily_rooms` (
    `check_date` DATE NOT NULL,
    `room_id` INT UNSIGNED NOT NULL,
    `note` TEXT NULL,
    `updated_by` INT UNSIGNED NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`check_date`, `room_id`),
    KEY `idx_daily_rooms_date_updated` (`check_date`, `updated_at`),
    KEY `idx_daily_rooms_room` (`room_id`),
    CONSTRAINT `fk_dr_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_dr_user` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `daily_options` (
    `check_date` DATE NOT NULL,
    `room_id` INT UNSIGNED NOT NULL,
    `option_id` INT UNSIGNED NOT NULL,
    `qty` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `updated_by` INT UNSIGNED NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`check_date`, `room_id`, `option_id`),
    KEY `idx_daily_options_date_updated` (`check_date`, `updated_at`),
    KEY `idx_daily_options_option` (`option_id`),
    CONSTRAINT `fk_do_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_do_option` FOREIGN KEY (`option_id`) REFERENCES `options` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_do_user` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `daily_extras` (
    `check_date` DATE NOT NULL,
    `room_id` INT UNSIGNED NOT NULL,
    `extra_id` INT UNSIGNED NOT NULL,
    `qty` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `updated_by` INT UNSIGNED NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`check_date`, `room_id`, `extra_id`),
    KEY `idx_daily_extras_date_updated` (`check_date`, `updated_at`),
    KEY `idx_daily_extras_extra` (`extra_id`),
    CONSTRAINT `fk_de_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_de_extra` FOREIGN KEY (`extra_id`) REFERENCES `extras` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_de_user` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 7. Audit Log
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_log` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NULL DEFAULT NULL,
    `user_name` VARCHAR(120) NOT NULL DEFAULT '',
    `action` VARCHAR(60) NOT NULL,
    `entity_type` VARCHAR(40) NOT NULL,
    `entity_id` INT UNSIGNED NULL DEFAULT NULL,
    `check_date` DATE NULL DEFAULT NULL,
    `room_id` INT UNSIGNED NULL DEFAULT NULL,
    `room_number` VARCHAR(30) NULL DEFAULT NULL,
    `item_label` VARCHAR(180) NULL DEFAULT NULL,
    `old_value` TEXT NULL,
    `new_value` TEXT NULL,
    `ip_address` VARCHAR(45) NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_audit_created` (`created_at`),
    KEY `idx_audit_date_room` (`check_date`, `room_id`),
    KEY `idx_audit_user` (`user_id`),
    CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- SEED DATA (Bengali Labels)
-- Safe to run on fresh database (uses explicit IDs with INSERT IGNORE)
-- =============================================================================

-- Floors
INSERT IGNORE INTO `floors` (`id`, `name`, `sort_order`, `is_active`) VALUES
(1, 'গ্রাউন্ড ফ্লোর', 1, 1),
(2, '১ম তলা', 2, 1),
(3, '২য় তলা', 3, 1),
(4, '৩য় তলা', 4, 1),
(5, '৪র্থ তলা', 5, 1),
(6, '৫ম তলা', 6, 1),
(7, '৬ষ্ঠ তলা', 7, 1);

-- Rooms
-- Ground: 001, 002, 003, 005, 006, 007, 008
INSERT IGNORE INTO `rooms` (`id`, `floor_id`, `room_number`, `sort_order`, `is_active`) VALUES
(1,  1, '001', 1, 1),
(2,  1, '002', 2, 1),
(3,  1, '003', 3, 1),
(4,  1, '005', 4, 1),
(5,  1, '006', 5, 1),
(6,  1, '007', 6, 1),
(7,  1, '008', 7, 1),
-- 1st: 101-104
(8,  2, '101', 1, 1),
(9,  2, '102', 2, 1),
(10, 2, '103', 3, 1),
(11, 2, '104', 4, 1),
-- 2nd: 201-205
(12, 3, '201', 1, 1),
(13, 3, '202', 2, 1),
(14, 3, '203', 3, 1),
(15, 3, '204', 4, 1),
(16, 3, '205', 5, 1),
-- 3rd: 301-306
(17, 4, '301', 1, 1),
(18, 4, '302', 2, 1),
(19, 4, '303', 3, 1),
(20, 4, '304', 4, 1),
(21, 4, '305', 5, 1),
(22, 4, '306', 6, 1),
-- 4th: 400-405
(23, 5, '400', 1, 1),
(24, 5, '401', 2, 1),
(25, 5, '402', 3, 1),
(26, 5, '403', 4, 1),
(27, 5, '404', 5, 1),
(28, 5, '405', 6, 1),
-- 5th: 501-506
(29, 6, '501', 1, 1),
(30, 6, '502', 2, 1),
(31, 6, '503', 3, 1),
(32, 6, '504', 4, 1),
(33, 6, '505', 5, 1),
(34, 6, '506', 6, 1),
-- 6th: 601-604
(35, 7, '601', 1, 1),
(36, 7, '602', 2, 1),
(37, 7, '603', 3, 1),
(38, 7, '604', 4, 1);

-- Sections
INSERT IGNORE INTO `sections` (`id`, `title`, `counts_toward_checkout`, `sort_order`, `is_active`) VALUES
(1, 'কাস্টমার যাওয়ার পর', 1, 1, 1),
(2, 'রুম পরিষ্কারের পর', 0, 2, 1);

-- Options inside Section 1 ("কাস্টমার যাওয়ার পর")
INSERT IGNORE INTO `options` (`id`, `section_id`, `label`, `type`, `max_count`, `applies_to`, `sort_order`, `is_active`) VALUES
(1,  1, 'রঙিন বেডশিট',   'counter',  10, 'all',      1,  1),
(2,  1, 'বালিশের কভার',  'counter',  10, 'all',      2,  1),
(3,  1, 'সাদা বেডশিট',   'counter',  10, 'all',      3,  1),
(4,  1, 'কম্বল',         'counter',  10, 'all',      4,  1),
(5,  1, 'লাইট',          'counter',  10, 'all',      5,  1),
(6,  1, 'বাথরুম',        'checkbox', 1,  'all',      6,  1),
(7,  1, 'ক্লিন দেওয়াল', 'checkbox', 1,  'all',      7,  1),
(8,  1, 'আয়না',         'checkbox', 1,  'all',      8,  1),
(9,  1, 'এসি রিমোট',     'checkbox', 1,  'selected', 9,  1),
(10, 1, 'তালা চাবি',     'checkbox', 1,  'all',      10, 1);

-- Options inside Section 2 ("রুম পরিষ্কারের পর")
INSERT IGNORE INTO `options` (`id`, `section_id`, `label`, `type`, `max_count`, `applies_to`, `sort_order`, `is_active`) VALUES
(11, 2, 'জানালা বন্ধ',      'checkbox', 1, 'all', 1, 1),
(12, 2, 'লাইট ফ্যান বন্ধ',  'checkbox', 1, 'all', 2, 1),
(13, 2, 'বিন ও বিন ক্যারি',  'checkbox', 1, 'all', 3, 1),
(14, 2, 'সাবান',            'checkbox', 1, 'all', 4, 1),
(15, 2, 'রুম পরিষ্কার',     'checkbox', 1, 'all', 5, 1),
(16, 2, 'বাথরুম পরিষ্কার',  'checkbox', 1, 'all', 6, 1);

-- Option applicability: "এসি রিমোট" (option_id = 9) ONLY for rooms 204 (15), 205 (16), 305 (21), 306 (22)
INSERT IGNORE INTO `option_rooms` (`option_id`, `room_id`) VALUES
(9, 15),
(9, 16),
(9, 21),
(9, 22);

-- Extras ("এক্সট্রা যা যা দেওয়া হয়েছে")
INSERT IGNORE INTO `extras` (`id`, `label`, `max_count`, `sort_order`, `is_active`) VALUES
(1, 'বেডশিট',   20, 1, 1),
(2, 'ম্যাট্রেস', 20, 2, 1),
(3, 'বালিশ',    20, 3, 1),
(4, 'কম্বল',    20, 4, 1),
(5, 'গুড নাইট', 20, 5, 1),
(6, 'জল',       20, 6, 1),
(7, 'টাওয়েল',  20, 7, 1);

-- Initial Versioned Room Defaults (effective from 2020-01-01 so all dates have baseline defaults)
-- Counters: রঙিন বেডশিট=1, বালিশের কভার=2, সাদা বেডশিট=1, কম্বল=1, লাইট=2
-- Checkboxes: 1 (with এসি রিমোট only for rooms 204, 205, 305, 306)
INSERT IGNORE INTO `room_defaults` (`room_id`, `option_id`, `qty`, `effective_from`)
SELECT r.id, o.id,
       CASE
           WHEN o.id = 2 THEN 2
           WHEN o.id = 5 THEN 2
           ELSE 1
       END AS qty,
       '2020-01-01' AS effective_from
FROM `rooms` r
CROSS JOIN `options` o
WHERE o.applies_to = 'all'
   OR (o.applies_to = 'selected' AND EXISTS (
       SELECT 1 FROM `option_rooms` or_map
       WHERE or_map.option_id = o.id AND or_map.room_id = r.id
   ));
