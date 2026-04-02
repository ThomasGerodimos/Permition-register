-- Permission Register Database Schema
-- Charset: utf8mb4 / Collation: utf8mb4_unicode_ci

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- Users (cached from Active Directory)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `username`    VARCHAR(100)    NOT NULL,
  `full_name`   VARCHAR(200)    DEFAULT NULL,
  `email`       VARCHAR(200)    DEFAULT NULL,
  `department`  VARCHAR(200)    DEFAULT NULL,
  `job_title`   VARCHAR(200)    DEFAULT NULL,
  `phone`       VARCHAR(50)     DEFAULT NULL,
  `manager`     VARCHAR(200)    DEFAULT NULL COMMENT 'Προϊστάμενος (από AD)',
  `role`        ENUM('admin','manager','viewer') NOT NULL DEFAULT 'viewer',
  `is_active`   TINYINT(1)      NOT NULL DEFAULT 1,
  `last_sync`   DATETIME        DEFAULT NULL,
  `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_username` (`username`),
  KEY `idx_department` (`department`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Resource Types (εφαρμογή, κοινόχρηστος φάκελος, mailbox)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `resource_types` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`             VARCHAR(100) NOT NULL COMMENT 'machine name: application, shared_folder, shared_mailbox',
  `label`            VARCHAR(100) NOT NULL COMMENT 'Εμφανιζόμενο όνομα',
  `permissions_json` JSON         NOT NULL COMMENT 'Διαθέσιμα δικαιώματα για αυτόν τον τύπο',
  `icon`             VARCHAR(50)  DEFAULT 'bi-file-earmark' COMMENT 'Bootstrap icon class',
  `is_active`        TINYINT(1)  NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Resources (συγκεκριμένοι πόροι)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `resources` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `resource_type_id` INT UNSIGNED NOT NULL,
  `name`             VARCHAR(200) NOT NULL,
  `description`      TEXT         DEFAULT NULL,
  `location`         VARCHAR(500) DEFAULT NULL COMMENT 'Path / URL / server name',
  `is_active`        TINYINT(1)  NOT NULL DEFAULT 1,
  `created_at`       DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`resource_type_id`),
  CONSTRAINT `fk_res_type` FOREIGN KEY (`resource_type_id`) REFERENCES `resource_types` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Permissions (κεντρικός πίνακας δικαιωμάτων)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `permissions` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`          INT UNSIGNED NOT NULL,
  `resource_id`      INT UNSIGNED NOT NULL,
  `permission_level` VARCHAR(100) NOT NULL COMMENT 'Read, Write, Full Access, Send As, κλπ',
  `granted_by`       INT UNSIGNED DEFAULT NULL COMMENT 'user_id του διαχειριστή που έδωσε το δικαίωμα',
  `notes`            TEXT         DEFAULT NULL,
  `granted_at`       DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at`       DATETIME    DEFAULT NULL COMMENT 'NULL = χωρίς λήξη',
  `is_active`        TINYINT(1)  NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_resource_permission` (`user_id`, `resource_id`, `permission_level`),
  KEY `idx_user` (`user_id`),
  KEY `idx_resource` (`resource_id`),
  KEY `idx_active` (`is_active`),
  CONSTRAINT `fk_perm_user`     FOREIGN KEY (`user_id`)     REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_perm_resource` FOREIGN KEY (`resource_id`) REFERENCES `resources` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_perm_granted`  FOREIGN KEY (`granted_by`)  REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Audit Log (ιστορικό αλλαγών)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_log` (
  `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `action`      ENUM('create','update','delete') NOT NULL,
  `table_name`  VARCHAR(100)    NOT NULL,
  `record_id`   INT UNSIGNED    NOT NULL,
  `changed_by`  INT UNSIGNED    NOT NULL,
  `old_values`  JSON            DEFAULT NULL,
  `new_values`  JSON            DEFAULT NULL,
  `description` VARCHAR(500)    DEFAULT NULL COMMENT 'Ανθρώπινη περιγραφή αλλαγής',
  `ip_address`  VARCHAR(45)     DEFAULT NULL,
  `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_action`     (`action`),
  KEY `idx_audit_table`      (`table_name`, `record_id`),
  KEY `idx_audit_changed_by` (`changed_by`),
  KEY `idx_audit_created`    (`created_at`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- User Type Admins (διαχειριστές ανά τύπο πόρου)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_type_admins` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`          INT UNSIGNED NOT NULL,
  `resource_type_id` INT UNSIGNED NOT NULL,
  `created_by`       INT UNSIGNED DEFAULT NULL,
  `created_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_type` (`user_id`, `resource_type_id`),
  KEY `idx_uta_user` (`user_id`),
  KEY `idx_uta_type` (`resource_type_id`),
  CONSTRAINT `fk_uta_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_uta_type` FOREIGN KEY (`resource_type_id`) REFERENCES `resource_types` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_uta_created` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- IP Restrictions (ανά ρόλο, διαχειρίζεται από UI)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ip_restrictions` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role`        ENUM('admin','manager') NOT NULL,
  `ip_range`    VARCHAR(50)  NOT NULL COMMENT 'Single IP ή CIDR (192.168.1.0/24)',
  `description` VARCHAR(200) DEFAULT NULL,
  `is_active`   TINYINT(1)  NOT NULL DEFAULT 1,
  `created_by`  INT UNSIGNED DEFAULT NULL,
  `created_at`  DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ip_role` (`role`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
