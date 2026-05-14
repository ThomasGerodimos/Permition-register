-- Migration 005: Ημερομηνία αποχώρησης υπαλλήλου
-- Αποθηκεύει πότε έγινε η αποχώρηση ώστε να εμφανίζεται badge στο profile

ALTER TABLE `users`
  ADD COLUMN `departed_at` DATE DEFAULT NULL
    COMMENT 'Ημερομηνία αποχώρησης — NULL = εν ενεργεία'
    AFTER `last_sync`;
