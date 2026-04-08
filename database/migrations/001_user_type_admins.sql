-- Migration: Add user_type_admins table
-- Allows assigning users as type-scoped permission admins
-- Run this on existing databases that already have the base schema

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
