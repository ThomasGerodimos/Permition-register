-- Migration 004: Στοιχεία υπευθύνων πόρου
-- Προσθήκη 3 ρόλων × 2 πεδία (όνομα + στοιχεία επικοινωνίας)

ALTER TABLE `resources`
  ADD COLUMN `owner_company_name`      VARCHAR(200) DEFAULT NULL COMMENT 'Εταιρικός υπεύθυνος — ονοματεπώνυμο'        AFTER `expires_at`,
  ADD COLUMN `owner_company_contact`   VARCHAR(300) DEFAULT NULL COMMENT 'Εταιρικός υπεύθυνος — τηλ/email'            AFTER `owner_company_name`,
  ADD COLUMN `owner_technical_name`    VARCHAR(200) DEFAULT NULL COMMENT 'Τεχνικός υπεύθυνος — ονοματεπώνυμο/εταιρεία' AFTER `owner_company_contact`,
  ADD COLUMN `owner_technical_contact` VARCHAR(300) DEFAULT NULL COMMENT 'Τεχνικός υπεύθυνος — τηλ/email'             AFTER `owner_technical_name`,
  ADD COLUMN `owner_business_name`     VARCHAR(200) DEFAULT NULL COMMENT 'Επιχειρησιακός υπεύθυνος — ονοματεπώνυμο'   AFTER `owner_technical_contact`,
  ADD COLUMN `owner_business_contact`  VARCHAR(300) DEFAULT NULL COMMENT 'Επιχειρησιακός υπεύθυνος — τηλ/email'       AFTER `owner_business_name`;
