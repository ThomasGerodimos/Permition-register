# Permission Register Web App

Εφαρμογή καταχώρησης δικαιωμάτων πρόσβασης για εταιρικό περιβάλλον.
PHP 8.x + MySQL, τρέχει σε WAMP στο `http://localhost/permissions`.

---

## Εγκατάσταση

### 1. Δημιουργία βάσης δεδομένων

```sql
CREATE DATABASE permissions_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Εκτελέστε τα SQL αρχεία:
```
database/schema.sql   ← δημιουργία πινάκων
database/seed.sql     ← βασικά δεδομένα
```

### 2. Composer

```bash
cd C:\wamp64\www\permissions
composer install
```

### 3. Ρυθμίσεις

Επεξεργαστείτε τα αρχεία ρυθμίσεων:

| Αρχείο                  | Περιεχόμενο                          |
|------------------------|---------------------------------------|
| `config/config.php`    | DB credentials, URL εφαρμογής        |
| `config/ldap.php`      | AD server, base DN, service account  |
| `config/mail.php`      | SMTP server, credentials             |

### 4. Apache mod_rewrite

Βεβαιωθείτε ότι το `mod_rewrite` είναι ενεργοποιημένο στο WAMP.
Ο `DocumentRoot` πρέπει να έχει `AllowOverride All`.

---

## Πρόσβαση

```
http://localhost/permissions/
```

- Ανακατεύθυνση στη σελίδα login
- Σύνδεση με AD credentials
- Ρόλοι: **admin** (πλήρης CRUD) | **manager** (read-only, μόνο τμήμα τους)

---

## Δομή Project

```
permissions/
├── public/          ← Web root (index.php, assets/)
├── src/             ← PHP classes (App\ namespace)
│   ├── Auth/        ← LDAP, AuthController, IpRestriction, Middleware
│   ├── Controllers/ ← Dashboard, Permission, User, Audit, Export, Email, Settings, Api
│   ├── Core/        ← Database, Router, Session, Csrf, View
│   ├── Models/      ← Permission, User, Resource, AuditLog
│   └── Services/    ← AdService, ExportService (CSV/Excel/PDF), MailService
├── views/           ← PHP templates
├── config/          ← config.php, ldap.php, mail.php
├── database/        ← schema.sql, seed.sql
├── storage/logs/    ← Error logs
└── vendor/          ← Composer packages
```

---

## Βασικές Λειτουργίες

- **CRUD Δικαιωμάτων** — 3 τύποι: Εφαρμογές, Κοινόχρηστοι Φάκελοι, Κοινόχρηστα Mailbox
- **AD Autocomplete** — αναζήτηση χρήστη με auto-populate (email, τμήμα, θέση)
- **Audit Log** — καταγραφή κάθε αλλαγής με παλαιές/νέες τιμές
- **Export** — CSV, Excel (.xlsx), PDF
- **Email** — αποστολή report (PDF/xlsx) για χρήστη ή τμήμα
- **IP Restrictions** — διαχείριση μέσω UI ανά ρόλο
- **Pagination** — 25 εγγραφές/σελίδα (ρυθμίζεται)

---

## AD Groups

Δημιουργήστε 2 AD groups και ενημερώστε το `config/ldap.php`:

- `PermRegAdmins` → ρόλος `admin`
- `PermRegManagers` → ρόλος `manager`

---

## Dependencies (Composer)

| Package | Χρήση |
|---------|-------|
| `phpmailer/phpmailer` | Email αποστολή |
| `phpoffice/phpspreadsheet` | Excel export |
| `tecnickcom/tcpdf` | PDF export |
