-- Add type_admin role to ip_restrictions
ALTER TABLE `ip_restrictions`
  MODIFY COLUMN `role` ENUM('admin','manager','type_admin') NOT NULL;
