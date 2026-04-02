-- Migration 002: Add expires_at to resources
-- Allows setting an expiration date on a resource (e.g. license expiry)

ALTER TABLE `resources`
  ADD COLUMN `expires_at` DATE DEFAULT NULL AFTER `location`;
