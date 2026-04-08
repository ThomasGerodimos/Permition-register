-- ============================================================
-- Test Type Admin Setup
-- Τρέξε αυτό στο phpMyAdmin ή MySQL CLI
-- ============================================================

-- 1. Δημιούργησε τον πίνακα (αν δεν υπάρχει)
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

-- 2. Εισαγωγή test user (manager χωρίς admin δικαιώματα)
INSERT INTO users (username, full_name, email, department, job_title, role, is_active)
VALUES ('test.typeadmin', 'Test Type Admin', 'test@example.com', 'Τμήμα Δοκιμών', 'Δοκιμαστής', 'manager', 1)
ON DUPLICATE KEY UPDATE role = 'manager', is_active = 1;

-- 3. Ανάθεση type-admin για Εφαρμογές
INSERT INTO user_type_admins (user_id, resource_type_id, created_by)
SELECT
    u.id,
    rt.id,
    (SELECT id FROM users WHERE role = 'admin' LIMIT 1)
FROM users u, resource_types rt
WHERE u.username = 'test.typeadmin'
  AND rt.name = 'application'
ON DUPLICATE KEY UPDATE created_at = NOW();

-- 4. Επιβεβαίωση
SELECT u.username, u.full_name, u.role, rt.label AS type_admin_for
FROM user_type_admins uta
JOIN users u ON u.id = uta.user_id
JOIN resource_types rt ON rt.id = uta.resource_type_id
WHERE u.username = 'test.typeadmin';

-- ============================================================
-- CLEANUP (τρέξε αυτά μετά το test)
-- ============================================================
-- DELETE FROM user_type_admins WHERE user_id = (SELECT id FROM users WHERE username = 'test.typeadmin');
-- DELETE FROM users WHERE username = 'test.typeadmin';
