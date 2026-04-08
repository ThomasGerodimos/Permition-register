-- Seed Data για Permission Register
-- Εκτελέστε μετά το schema.sql

SET NAMES utf8mb4;

-- --------------------------------------------------------
-- Resource Types με τα αντίστοιχα δικαιώματα
-- --------------------------------------------------------
INSERT INTO `resource_types` (`name`, `label`, `permissions_json`, `icon`) VALUES
(
  'application',
  'Εφαρμογή',
  '["Read","Write","Admin","No Access"]',
  'bi-app-indicator'
),
(
  'shared_folder',
  'Κοινόχρηστος Φάκελος',
  '["Read","Read/Write","Full Control","No Access"]',
  'bi-folder-shared'
),
(
  'shared_mailbox',
  'Κοινόχρηστο Mailbox',
  '["Full Access","Send As","Send On Behalf","Read Only"]',
  'bi-envelope-at'
);

-- --------------------------------------------------------
-- Default admin user (θα αντικατασταθεί από AD sync)
-- Χρησιμοποιείται μόνο για πρώτη σύνδεση / bootstrap
-- --------------------------------------------------------
INSERT INTO `users` (`username`, `full_name`, `email`, `department`, `job_title`, `role`) VALUES
('administrator', 'Administrator', 'admin@yourdomain.gr', 'IT', 'System Administrator', 'admin');

-- --------------------------------------------------------
-- Παράδειγμα IP restrictions (προσαρμόστε στο δίκτυό σας)
-- --------------------------------------------------------
INSERT INTO `ip_restrictions` (`role`, `ip_range`, `description`, `is_active`) VALUES
('admin',   '127.0.0.1',      'Localhost (development)',    1),
('admin',   '192.168.1.0/24', 'Εσωτερικό δίκτυο γραφείου', 1),
('manager', '127.0.0.1',      'Localhost (development)',    1),
('manager', '192.168.1.0/24', 'Εσωτερικό δίκτυο γραφείου', 1);
