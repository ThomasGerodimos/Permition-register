# Μητρώο Δικαιωμάτων — Τεχνική Τεκμηρίωση

## Περιεχόμενα

1. [Εισαγωγή](#1-εισαγωγή)
2. [Αρχιτεκτονική & Τεχνολογίες](#2-αρχιτεκτονική--τεχνολογίες)
3. [Δομή Αρχείων](#3-δομή-αρχείων)
4. [Βάση Δεδομένων](#4-βάση-δεδομένων)
5. [Ρόλοι & Δικαιώματα Πρόσβασης](#5-ρόλοι--δικαιώματα-πρόσβασης)
6. [Λειτουργίες Εφαρμογής](#6-λειτουργίες-εφαρμογής)
7. [Active Directory Integration](#7-active-directory-integration)
8. [Ασφάλεια](#8-ασφάλεια)
9. [API Endpoints](#9-api-endpoints)
10. [Εξαγωγές & Email](#10-εξαγωγές--email)
11. [Εγκατάσταση Development](#11-εγκατάσταση-development)
12. [Μεταφορά σε Production](#12-μεταφορά-σε-production)
13. [Ρυθμίσεις Παραγωγικού](#13-ρυθμίσεις-παραγωγικού)
14. [Συντήρηση & Troubleshooting](#14-συντήρηση--troubleshooting)
15. [Μελλοντικές Επεκτάσεις](#15-μελλοντικές-επεκτάσεις)

---

## 1. Εισαγωγή

### Σκοπός
Το **Μητρώο Δικαιωμάτων** είναι εσωτερική web εφαρμογή για την καταγραφή και διαχείριση των δικαιωμάτων πρόσβασης χρηστών σε εταιρικούς πόρους (εφαρμογές, κοινόχρηστους φακέλους, κοινόχρηστα mailbox).

### Οργανισμός
Αρχή Καταπολέμησης της Νομιμοποίησης Εσόδων από Εγκληματικές Δραστηριότητες (ΑΚΝΕΕΔ)

### Βασικά Χαρακτηριστικά
- Καταχώρηση / τροποποίηση / διαγραφή δικαιωμάτων πρόσβασης
- Μαζική ανάθεση δικαιωμάτων σε πολλούς χρήστες
- Ενσωμάτωση με Active Directory (LDAP) για αυθεντικοποίηση και αυτόματη άντληση στοιχείων
- Ρόλοι: Διαχειριστής (πλήρης CRUD) και Προϊστάμενος (read-only ανά τμήμα)
- IP restrictions ανά ρόλο
- Πλήρες audit log αλλαγών
- Εξαγωγές σε CSV, Excel, PDF
- Αποστολή αναφορών μέσω email
- Dashboard με στατιστικά
- Responsive σχεδιασμός (Bootstrap 5)

---

## 2. Αρχιτεκτονική & Τεχνολογίες

### Stack
| Τεχνολογία | Έκδοση | Χρήση |
|------------|--------|-------|
| PHP | >= 8.0 | Backend |
| MySQL | 5.7+ / 8.0+ | Βάση Δεδομένων |
| Apache | 2.4+ | Web Server (mod_rewrite) |
| Bootstrap | 5.3.3 | Frontend UI |
| Bootstrap Icons | 1.11.3 | Εικονίδια |
| PHPMailer | ^6.9 | Αποστολή email |
| PhpSpreadsheet | ^2.0 | Εξαγωγή Excel |
| TCPDF | ^6.7 | Εξαγωγή PDF |

### Αρχιτεκτονικό Μοτίβο
- **MVC** (Model-View-Controller) χωρίς framework
- **Front Controller**: Όλα τα requests περνούν από `public/index.php`
- **PSR-4 Autoloading**: Namespace `App\` → `src/`
- **PDO Singleton**: Λεπτός Database wrapper χωρίς ORM
- **PHP Templates**: Απλά `.php` αρχεία (χωρίς template engine)
- **Environment Variables**: `.env` αρχείο για credentials

### Request Flow
```
Browser → Apache (.htaccess rewrite)
       → public/index.php (Front Controller)
       → Env::load(.env)
       → Config::load()
       → Session::start()
       → Router::dispatch()
       → Middleware (auth check)
       → Controller → Model → Database
       → View::render() → HTML Response
```

---

## 3. Δομή Αρχείων

```
permissions/
│
├── public/                          ← Document Root (web-accessible)
│   ├── index.php                    ← Front Controller / Router
│   ├── .htaccess                    ← URL Rewriting
│   └── assets/
│       ├── css/
│       │   ├── app.css              ← Custom styles
│       │   └── print.css            ← Print styles
│       ├── js/
│       │   └── app.js               ← Frontend JavaScript
│       └── images/
│           └── logo.png             ← Λογότυπο ΑΚΝΕΕΔ
│
├── src/                             ← Application Code (namespace: App\)
│   ├── Core/
│   │   ├── Database.php             ← PDO singleton wrapper
│   │   ├── Router.php               ← URL router με pattern matching
│   │   ├── Session.php              ← Session management + impersonation
│   │   ├── Csrf.php                 ← CSRF token generation/validation
│   │   ├── View.php                 ← Template rendering + helpers
│   │   ├── Config.php               ← Configuration singleton
│   │   └── Env.php                  ← .env file parser
│   │
│   ├── Auth/
│   │   ├── LdapService.php          ← LDAP bind, search, authenticate
│   │   ├── AuthController.php       ← Login / Logout handlers
│   │   ├── IpRestriction.php        ← IP allowlist (CIDR support)
│   │   └── Middleware.php           ← Auth middleware (requireLogin, requireAdmin)
│   │
│   ├── Controllers/
│   │   ├── DashboardController.php  ← Dashboard + Department view + Impersonate
│   │   ├── PermissionController.php ← CRUD δικαιωμάτων + μαζική ανάθεση
│   │   ├── UserController.php       ← Λίστα χρηστών + AD sync
│   │   ├── AuditController.php      ← Ιστορικό αλλαγών
│   │   ├── SettingsController.php   ← IP restrictions + Resources management
│   │   ├── ExportController.php     ← CSV / Excel / PDF export
│   │   ├── EmailController.php      ← Αποστολή email αναφορών
│   │   └── ApiController.php        ← AJAX endpoints (AD search, permissions)
│   │
│   ├── Models/
│   │   ├── Permission.php           ← CRUD + stats + filters
│   │   ├── User.php                 ← User CRUD + AD sync
│   │   ├── Resource.php             ← Resources + types
│   │   └── AuditLog.php             ← Audit trail
│   │
│   └── Services/
│       ├── AdService.php            ← AD search + sync orchestrator
│       ├── ExportService.php        ← CSV / Excel / PDF generation
│       └── MailService.php          ← PHPMailer wrapper
│
├── views/                           ← PHP Templates
│   ├── layout/
│   │   └── main.php                 ← Main layout (header, sidebar, footer, clock)
│   ├── auth/
│   │   └── login.php                ← Σελίδα σύνδεσης
│   ├── dashboard/
│   │   ├── index.php                ← Dashboard με στατιστικά
│   │   └── department.php           ← Προβολή τμήματος
│   ├── permissions/
│   │   ├── index.php                ← Λίστα δικαιωμάτων (search, filter, pagination)
│   │   ├── form.php                 ← Φόρμα δημιουργίας / επεξεργασίας
│   │   ├── bulk-form.php            ← Μαζική ανάθεση
│   │   ├── users.php                ← Κάρτες χρηστών
│   │   └── user_view.php            ← Προφίλ χρήστη + δικαιώματα
│   ├── audit/
│   │   └── index.php                ← Ιστορικό αλλαγών
│   ├── settings/
│   │   ├── index.php                ← IP Restrictions
│   │   ├── resources.php            ← Διαχείριση πόρων
│   │   ├── resources-by-type.php    ← Πόροι ανά τύπο
│   │   └── resource-permissions.php ← Δικαιώματα πόρου
│   └── errors/
│       └── 403.php                  ← Forbidden page
│
├── config/
│   ├── config.php                   ← App + DB + Session (reads from .env)
│   ├── ldap.php                     ← LDAP/AD settings (reads from .env)
│   └── mail.php                     ← SMTP settings (reads from .env)
│
├── database/
│   ├── schema.sql                   ← Δημιουργία πινάκων
│   └── seed.sql                     ← Αρχικά δεδομένα
│
├── storage/
│   └── logs/                        ← Application logs
│
├── vendor/                          ← Composer dependencies
├── docs/
│   └── DOCUMENTATION.md             ← Αυτό το αρχείο
│
├── .env                             ← Environment variables (ΔΕΝ ανεβαίνει στο Git)
├── .env.example                     ← Template χωρίς credentials
├── .gitignore                       ← Εξαιρέσεις Git
├── .htaccess                        ← Redirect → public/
└── composer.json                    ← Dependencies + autoload
```

---

## 4. Βάση Δεδομένων

### Σχεσιακό Διάγραμμα

```
┌──────────────┐       ┌──────────────────┐       ┌──────────────┐
│   users      │       │   permissions    │       │  resources   │
├──────────────┤       ├──────────────────┤       ├──────────────┤
│ id (PK)      │──┐    │ id (PK)          │    ┌──│ id (PK)      │
│ username (UQ)│  ├───→│ user_id (FK)     │    │  │ resource_    │
│ full_name    │  │    │ resource_id (FK) │←───┘  │  type_id(FK) │──→ resource_types
│ email        │  │    │ permission_level │       │ name         │
│ department   │  ├───→│ granted_by (FK)  │       │ description  │
│ job_title    │  │    │ notes            │       │ location     │
│ phone        │  │    │ granted_at       │       │ is_active    │
│ manager      │  │    │ expires_at       │       └──────────────┘
│ role         │  │    │ is_active        │
│ is_active    │  │    └──────────────────┘       ┌──────────────┐
│ last_sync    │  │                               │resource_types│
└──────────────┘  │    ┌──────────────────┐       ├──────────────┤
                  │    │   audit_log      │       │ id (PK)      │
                  │    ├──────────────────┤       │ name (UQ)    │
                  └───→│ changed_by (FK)  │       │ label        │
                       │ action           │       │ permissions_ │
                       │ table_name       │       │   json       │
                       │ record_id        │       │ icon         │
                       │ old_values (JSON)│       │ is_active    │
                       │ new_values (JSON)│       └──────────────┘
                       │ description      │
                       │ ip_address       │       ┌──────────────────┐
                       └──────────────────┘       │ ip_restrictions  │
                                                  ├──────────────────┤
                                                  │ id (PK)          │
                                                  │ role             │
                                                  │ ip_range (CIDR)  │
                                                  │ description      │
                                                  │ is_active        │
                                                  │ created_by       │
                                                  └──────────────────┘
```

### Πίνακες

#### `users` — Χρήστες (cache από Active Directory)
| Στήλη | Τύπος | Περιγραφή |
|-------|-------|-----------|
| id | INT UNSIGNED PK | Αυτόματος αριθμός |
| username | VARCHAR(100) UNIQUE | AD username (sAMAccountName) |
| full_name | VARCHAR(200) | Εμφανιζόμενο όνομα (displayName) |
| email | VARCHAR(200) | Email (mail) |
| department | VARCHAR(200) | Τμήμα (department) |
| job_title | VARCHAR(200) | Θέση (title) |
| phone | VARCHAR(50) | Τηλέφωνο (telephoneNumber) |
| manager | VARCHAR(200) | Προϊστάμενος (manager → resolved name) |
| role | ENUM('admin','manager','viewer') | Ρόλος στην εφαρμογή |
| is_active | TINYINT(1) DEFAULT 1 | Ενεργός χρήστης |
| last_sync | DATETIME | Τελευταίος συγχρονισμός με AD |
| created_at | DATETIME | Ημ/νία δημιουργίας |
| updated_at | DATETIME | Τελευταία τροποποίηση |

#### `resource_types` — Τύποι Πόρων
| Στήλη | Τύπος | Περιγραφή |
|-------|-------|-----------|
| id | INT UNSIGNED PK | Αυτόματος αριθμός |
| name | VARCHAR(100) UNIQUE | Εσωτερικό όνομα (application, shared_folder, shared_mailbox) |
| label | VARCHAR(100) | Εμφανιζόμενο όνομα (Εφαρμογή, Κοινόχρηστος Φάκελος, κ.λπ.) |
| permissions_json | JSON | Διαθέσιμα δικαιώματα για αυτόν τον τύπο |
| icon | VARCHAR(50) | Bootstrap icon class |
| is_active | TINYINT(1) DEFAULT 1 | Ενεργός τύπος |

**Προεγκατεστημένοι τύποι:**
| Τύπος | Δικαιώματα |
|-------|------------|
| Εφαρμογή | Read, Write, Admin, No Access |
| Κοινόχρηστος Φάκελος | Read, Read/Write, Full Control, No Access |
| Κοινόχρηστο Mailbox | Full Access, Send As, Send On Behalf, Read Only |

#### `resources` — Πόροι
| Στήλη | Τύπος | Περιγραφή |
|-------|-------|-----------|
| id | INT UNSIGNED PK | Αυτόματος αριθμός |
| resource_type_id | INT UNSIGNED FK | Τύπος πόρου |
| name | VARCHAR(200) | Όνομα πόρου |
| description | TEXT | Περιγραφή |
| location | VARCHAR(500) | Διαδρομή / URL / server |
| is_active | TINYINT(1) DEFAULT 1 | Ενεργός πόρος |
| created_at | DATETIME | Ημ/νία δημιουργίας |

#### `permissions` — Δικαιώματα Πρόσβασης
| Στήλη | Τύπος | Περιγραφή |
|-------|-------|-----------|
| id | INT UNSIGNED PK | Αυτόματος αριθμός |
| user_id | INT UNSIGNED FK | Χρήστης |
| resource_id | INT UNSIGNED FK | Πόρος |
| permission_level | VARCHAR(100) | Επίπεδο δικαιώματος (π.χ. Read, Full Access) |
| granted_by | INT UNSIGNED FK | Χορηγήθηκε από (user ID) |
| notes | TEXT | Σημειώσεις |
| granted_at | DATETIME | Ημ/νία χορήγησης |
| expires_at | DATETIME NULL | Ημ/νία λήξης (NULL = χωρίς λήξη) |
| is_active | TINYINT(1) DEFAULT 1 | Ενεργό δικαίωμα |

**Unique Constraint:** `(user_id, resource_id, permission_level)` — Αποτρέπει διπλότυπα.

#### `audit_log` — Ιστορικό Αλλαγών
| Στήλη | Τύπος | Περιγραφή |
|-------|-------|-----------|
| id | INT UNSIGNED PK | Αυτόματος αριθμός |
| action | ENUM('create','update','delete') | Τύπος ενέργειας |
| table_name | VARCHAR(100) | Πίνακας που τροποποιήθηκε |
| record_id | INT UNSIGNED | ID εγγραφής |
| changed_by | INT UNSIGNED FK | Χρήστης που έκανε την αλλαγή |
| old_values | JSON | Παλιές τιμές |
| new_values | JSON | Νέες τιμές |
| description | VARCHAR(500) | Ανθρώπινη περιγραφή |
| ip_address | VARCHAR(45) | IP χρήστη |
| created_at | DATETIME | Ημ/νία αλλαγής |

#### `ip_restrictions` — Περιορισμοί IP
| Στήλη | Τύπος | Περιγραφή |
|-------|-------|-----------|
| id | INT UNSIGNED PK | Αυτόματος αριθμός |
| role | ENUM('admin','manager') | Ρόλος |
| ip_range | VARCHAR(50) | IP ή CIDR (π.χ. 192.168.1.0/24) |
| description | VARCHAR(200) | Περιγραφή |
| is_active | TINYINT(1) DEFAULT 1 | Ενεργός κανόνας |
| created_by | INT UNSIGNED FK | Δημιουργήθηκε από |

---

## 5. Ρόλοι & Δικαιώματα Πρόσβασης

### Ρόλοι

| Ρόλος | Ελληνικά | Πρόσβαση |
|-------|----------|----------|
| `admin` | Διαχειριστής | Πλήρης πρόσβαση σε όλα |
| `manager` | Προϊστάμενος | Read-only στο δικό του τμήμα |
| `viewer` | Χρήστης | Δεν μπορεί να συνδεθεί |

### Αντιστοίχιση με AD Groups
| AD Group | Ρόλος Εφαρμογής |
|----------|----------------|
| `LDAP_ADMIN_GROUP` (π.χ. CN=IT,...) | admin |
| `LDAP_MANAGER_GROUP` (π.χ. CN=Managers,...) | manager |
| Κανένα group | viewer (απαγόρευση σύνδεσης) |

### Πίνακας Δικαιωμάτων

| Λειτουργία | Admin | Manager |
|------------|-------|---------|
| Dashboard | ✅ Όλα τα τμήματα | ✅ Μόνο το τμήμα του |
| Δικαιώματα — Προβολή | ✅ Όλα | ✅ Μόνο τμήμα |
| Δικαιώματα — Δημιουργία | ✅ | ❌ |
| Δικαιώματα — Επεξεργασία | ✅ | ❌ |
| Δικαιώματα — Διαγραφή | ✅ | ❌ |
| Μαζική Ανάθεση | ✅ | ❌ |
| Χρήστες — Προβολή | ✅ Όλοι | ✅ Μόνο τμήμα |
| Χρήστες — AD Sync | ✅ | ❌ |
| Ιστορικό (Audit) | ✅ | ❌ |
| Πόροι — Διαχείριση | ✅ | ❌ |
| Ρυθμίσεις (IP) | ✅ | ❌ |
| Εξαγωγές (CSV/XLS/PDF) | ✅ Όλα | ✅ Μόνο τμήμα |
| Αποστολή Email | ✅ Όλα | ✅ Μόνο τμήμα |
| Impersonation | ✅ | ❌ |

### IP Restrictions
- Ορίζονται ανά ρόλο μέσω UI (Ρυθμίσεις)
- Υποστήριξη: μεμονωμένη IP και CIDR notation (π.χ. `192.168.1.0/24`)
- Αν **δεν** υπάρχουν κανόνες για έναν ρόλο → επιτρέπεται κάθε IP
- Αν **υπάρχουν** κανόνες → μόνο οι επιτρεπόμενες IPs

---

## 6. Λειτουργίες Εφαρμογής

### 6.1 Dashboard
- Κάρτες στατιστικών: Σύνολο δικαιωμάτων, Χρήστες
- Κάρτες ανά τύπο πόρου: Εφαρμογές, Κοινόχρηστοι Φάκελοι, Mailbox
- Τελευταίες εγγραφές (5 πιο πρόσφατα δικαιώματα)
- Κατανομή ανά τμήμα (admin only)
- Managers βλέπουν μόνο δεδομένα του τμήματός τους

### 6.2 Δικαιώματα
- **Λίστα**: Πίνακας με live search, φίλτρα (τμήμα, τύπος, επίπεδο), pagination
- **Δημιουργία**: Φόρμα με AD autocomplete χρήστη, δυναμικά δικαιώματα ανά τύπο πόρου
- **Επεξεργασία**: Αλλαγή πόρου, επιπέδου, σημειώσεων, ημ/νίας λήξης
- **Διαγραφή**: Soft delete (is_active = 0)
- **Μαζική Ανάθεση**: Επιλογή πολλών χρηστών, ίδιος πόρος + δικαίωμα

### 6.3 Χρήστες
- Κάρτες χρηστών με live search και φίλτρο τμήματος
- Προφίλ χρήστη: Στοιχεία + Προϊστάμενος (από AD) + δικαιώματα
- AD Sync: Μαζικός συγχρονισμός όλων των χρηστών από το Active Directory
- Export PDF/Excel + αποστολή email ανά χρήστη

### 6.4 Πόροι
- Λίστα πόρων με search, φίλτρο τύπου, pagination
- Προβολή ανά τύπο πόρου
- Προβολή δικαιωμάτων ανά πόρο
- Δημιουργία / Διαγραφή πόρων

### 6.5 Ιστορικό (Audit Log)
- Πλήρης καταγραφή: create, update, delete
- Φίλτρα: ενέργεια, χρήστης, ημερομηνία, αναζήτηση
- Εμφάνιση παλιών/νέων τιμών (JSON)
- IP address καταγραφή

### 6.6 Ρυθμίσεις
- Διαχείριση IP restrictions ανά ρόλο
- Ενεργοποίηση / Απενεργοποίηση κανόνων

### 6.7 Impersonation (Δοκιμή Ρόλων)
- Ο admin μπορεί να δει την εφαρμογή ως Προϊστάμενος συγκεκριμένου τμήματος
- Εμφανίζεται banner ότι η προβολή είναι impersonated
- Δεν χάνεται η admin πρόσβαση (realRole ελέγχεται ξεχωριστά)

---

## 7. Active Directory Integration

### Πώς λειτουργεί

```
Χρήστης → Login Form → LdapService::authenticate()
                              │
                              ├── 1. LDAP Connect (service account bind)
                              ├── 2. Search user by sAMAccountName
                              ├── 3. Bind with user credentials (verify password)
                              ├── 4. Re-bind as service account
                              ├── 5. Parse attributes (displayName, mail, department, ...)
                              ├── 6. Resolve manager DN → display name
                              ├── 7. Check AD group membership → determine role
                              └── 8. Sync to local DB (upsert users table)
```

### Attribute Mapping
| AD Attribute | Πεδίο Εφαρμογής |
|-------------|-----------------|
| sAMAccountName | username |
| displayName | full_name |
| mail | email |
| department | department |
| title | job_title |
| telephoneNumber | phone |
| manager (DN) | manager (resolved to name) |

### AD Autocomplete
Στη φόρμα δικαιωμάτων, πληκτρολογώντας >=2 χαρακτήρες:
1. AJAX request → `/api/ad/search?q=...`
2. Αναζήτηση στο AD κατά username + displayName
3. Εξαιρούνται disabled accounts
4. Αυτόματο sync στην τοπική βάση
5. Εμφάνιση dropdown με αποτελέσματα

### Μαζικός Συγχρονισμός (AD Sync)
- Κουμπί "Συγχρονισμός AD" στη σελίδα Χρηστών
- Για κάθε χρήστη στην τοπική βάση → `LdapService::findUser()` → update
- Ενημερώνει: full_name, email, department, job_title, phone, manager

### Απαιτήσεις AD
- Service Account με δικαίωμα Read στο Domain
- Port 389 (LDAP) ή 636 (LDAPS)
- Προαιρετικό: StartTLS
- AD Groups για αντιστοίχιση ρόλων

---

## 8. Ασφάλεια

### 8.1 Authentication
- **LDAP/AD**: Κανένα password αποθηκεύεται τοπικά
- **Session**: HTTPOnly cookies, SameSite=Lax
- **Session Regeneration**: Νέο session ID μετά κάθε login
- **Timeout**: 2 ώρες (configurable)

### 8.2 CSRF Protection
- Token generation: `random_bytes(32)` → hex
- Αποθήκευση στο session
- Hidden field `_csrf` σε κάθε POST form
- AJAX: header `X-CSRF-TOKEN`
- Validation: `hash_equals()` (timing-safe)

### 8.3 SQL Injection Prevention
- **100% prepared statements** (PDO) σε όλα τα queries
- Κανένα string concatenation σε SQL

### 8.4 XSS Prevention
- `View::e()` → `htmlspecialchars(ENT_QUOTES, 'UTF-8')` σε κάθε output
- Κανένα unescaped user input στο HTML

### 8.5 IP Restriction
- Per-role IP allowlists (CIDR + single IP)
- IPv4 και IPv6 υποστήριξη
- Έλεγχος κατά το login

### 8.6 Audit Trail
- Κάθε create/update/delete καταγράφεται
- Αποθηκεύονται: ενέργεια, πίνακας, εγγραφή, χρήστης, IP, παλιές/νέες τιμές

### 8.7 Environment Variables
- Credentials σε `.env` (εκτός version control)
- Config αρχεία χρησιμοποιούν `Env::get()` με defaults

---

## 9. API Endpoints

### Routes

| Method | URL | Controller | Περιγραφή | Auth |
|--------|-----|------------|-----------|------|
| GET | `/login` | AuthController::loginForm | Σελίδα σύνδεσης | Public |
| POST | `/login` | AuthController::login | Σύνδεση | Public |
| GET | `/logout` | AuthController::logout | Αποσύνδεση | Login |
| GET | `/` `/dashboard` | DashboardController::index | Dashboard | Login |
| GET | `/departments/view?dept=` | DashboardController::departmentView | Τμήμα | Login |
| GET | `/permissions` | PermissionController::index | Λίστα | Login |
| GET | `/permissions/create` | PermissionController::create | Φόρμα δημιουργίας | Admin |
| POST | `/permissions/store` | PermissionController::store | Αποθήκευση | Admin |
| GET | `/permissions/{id}/edit` | PermissionController::edit | Φόρμα επεξεργασίας | Admin |
| POST | `/permissions/{id}/update` | PermissionController::update | Ενημέρωση | Admin |
| POST | `/permissions/{id}/delete` | PermissionController::delete | Διαγραφή | Admin |
| GET | `/permissions/bulk` | PermissionController::bulk | Φόρμα μαζικής | Admin |
| POST | `/permissions/bulk` | PermissionController::bulkStore | Μαζική αποθήκευση | Admin |
| GET | `/users` | UserController::index | Λίστα χρηστών | Login |
| POST | `/users/sync-ad` | UserController::syncAd | AD Sync | Admin |
| GET | `/users/{id}` | UserController::show | Προφίλ χρήστη | Login |
| GET | `/audit` | AuditController::index | Ιστορικό | Admin |
| GET | `/export/csv` | ExportController::csv | Export CSV | Login |
| GET | `/export/excel` | ExportController::excel | Export Excel | Login |
| GET | `/export/pdf` | ExportController::pdf | Export PDF | Login |
| POST | `/email/send` | EmailController::send | Αποστολή email | Login |
| GET | `/settings` | SettingsController::index | Ρυθμίσεις IP | Admin |
| POST | `/settings/ip/store` | SettingsController::storeIp | Νέος κανόνας IP | Admin |
| POST | `/settings/ip/{id}/delete` | SettingsController::deleteIp | Διαγραφή κανόνα | Admin |
| POST | `/settings/ip/{id}/toggle` | SettingsController::toggleIp | Toggle κανόνα | Admin |
| GET | `/resources` | SettingsController::resources | Λίστα πόρων | Admin |
| POST | `/resources/store` | SettingsController::storeResource | Νέος πόρος | Admin |
| POST | `/resources/{id}/delete` | SettingsController::deleteResource | Διαγραφή πόρου | Admin |
| GET | `/resources/by-type/{id}` | SettingsController::resourcesByType | Πόροι ανά τύπο | Admin |
| GET | `/resources/{id}/permissions` | SettingsController::resourcePermissions | Δικαιώματα πόρου | Admin |
| POST | `/impersonate/start` | DashboardController::impersonateStart | Έναρξη impersonation | Admin |
| GET | `/impersonate/stop` | DashboardController::impersonateStop | Τέλος impersonation | Admin |
| GET | `/api/ad/search?q=` | ApiController::adSearch | Αναζήτηση AD (AJAX) | Login |
| GET | `/api/resource-types/{id}/permissions` | ApiController::resourceTypePermissions | Δικαιώματα τύπου (AJAX) | Login |

---

## 10. Εξαγωγές & Email

### Εξαγωγή CSV
- Delimiter: `;` (semicolon — για Excel compatibility)
- Encoding: UTF-8 με BOM
- Στήλες: Χρήστης, Ονοματεπώνυμο, Email, Τμήμα, Θέση, Τύπος Πόρου, Πόρος, Δικαίωμα, Εγκρίθηκε από, Ημ/νία, Λήξη, Σημειώσεις

### Εξαγωγή Excel
- Μορφοποίηση: Bold headers (λευκό σε μπλε), εναλλασσόμενα χρώματα γραμμών
- Frozen header row
- Auto-width στήλες
- Βιβλιοθήκη: PhpSpreadsheet

### Εξαγωγή PDF
- Landscape A4
- Header με τίτλο αναφοράς
- Πίνακας με εναλλασσόμενα χρώματα
- Βιβλιοθήκη: TCPDF

### Αποστολή Email
- Modal επιλογής: μορφή (PDF/Excel), παραλήπτης
- Δημιουργία temp αρχείου → attachment
- SMTP μέσω PHPMailer (Office 365 / Exchange Online)
- HTML email body

---

## 11. Εγκατάσταση Development

### Προαπαιτούμενα
- WAMP Server (ή XAMPP) με PHP >= 8.0
- MySQL 5.7+
- Composer
- PHP extensions: `ldap`, `pdo_mysql`, `gmp`, `mbstring`, `gd`

### Βήματα

#### 1. Clone του repository
```bash
cd C:\wamp64\www
git clone https://github.com/your-org/permissions.git
cd permissions
```

#### 2. Εγκατάσταση dependencies
```bash
composer install
```

#### 3. Ρύθμιση environment
```bash
copy .env.example .env
# Επεξεργασία .env με τα πραγματικά credentials
```

#### 4. Δημιουργία βάσης δεδομένων
```sql
CREATE DATABASE permissions_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE permissions_db;
SOURCE C:/wamp64/www/permissions/database/schema.sql;
SOURCE C:/wamp64/www/permissions/database/seed.sql;
```

#### 5. Ρύθμιση Apache
Βεβαιωθείτε ότι:
- Το `mod_rewrite` είναι ενεργοποιημένο
- Στο `httpd.conf` υπάρχει `AllowOverride All` στο directory
- Το PHP extension `php_ldap` είναι ενεργοποιημένο

```apache
# httpd.conf
<Directory "C:/wamp64/www">
    AllowOverride All
    Require all granted
</Directory>
```

#### 6. Ρύθμιση .env
```env
APP_ENV=development
APP_URL=http://localhost/permissions
DB_USER=root
DB_PASS=
LDAP_HOST=ldap://your-dc.domain.loc
LDAP_BIND_USER=CN=...
LDAP_BIND_PASS=...
LDAP_ADMIN_GROUP=CN=...
LDAP_MANAGER_GROUP=CN=...
```

#### 7. Πρόσβαση
Ανοίξτε `http://localhost/permissions/` → Σελίδα σύνδεσης

---

## 12. Μεταφορά σε Production

### Προετοιμασία Server

#### Απαιτήσεις Server
| Απαίτηση | Ελάχιστο |
|----------|----------|
| OS | Windows Server 2019+ ή Linux (Ubuntu 22.04+) |
| Web Server | Apache 2.4+ ή IIS 10+ |
| PHP | 8.0+ |
| MySQL | 5.7+ / 8.0+ |
| RAM | 2 GB |
| Disk | 1 GB (+ χώρος για logs) |

#### PHP Extensions (υποχρεωτικά)
```
php_ldap
php_pdo_mysql
php_gmp
php_mbstring
php_gd
php_openssl
php_fileinfo
```

### Βήματα Deployment

#### 1. Μεταφορά αρχείων
```bash
# Από το development machine
git clone https://github.com/your-org/permissions.git
cd permissions
composer install --no-dev --optimize-autoloader
```

> **Σημαντικό**: Χρησιμοποιήστε `--no-dev` για να αποκλείσετε dev dependencies.

#### 2. Δημιουργία .env στον server
```bash
copy .env.example .env
# Επεξεργασία με τα production values
```

```env
# .env (Production)
APP_ENV=production
APP_URL=http://permissions.hfiu.loc

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=permissions_db
DB_USER=permissions_user
DB_PASS=STRONG_PASSWORD_HERE

LDAP_HOST=ldap://APPINT01.hfiu.loc
LDAP_PORT=389
LDAP_USE_TLS=false
LDAP_DOMAIN=hfiu.loc
LDAP_BASE_DN=DC=hfiu,DC=loc
LDAP_USERS_OU=OU=Users,DC=hfiu,DC=loc
LDAP_BIND_USER="CN=svc-permreg,OU=Service Accounts,DC=hfiu,DC=loc"
LDAP_BIND_PASS=SERVICE_ACCOUNT_PASSWORD
LDAP_ADMIN_GROUP="CN=IT,OU=HFIU Users,DC=hfiu,DC=loc"
LDAP_MANAGER_GROUP="CN=Managers,OU=HFIU Users,DC=hfiu,DC=loc"

MAIL_FROM_ADDRESS=permissions@hfiu.loc
MAIL_FROM_NAME=Μητρώο Δικαιωμάτων
MAIL_REPLY_TO=helpdesk@hfiu.loc
MAIL_HOST=smtp.office365.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=permissions@hfiu.loc
MAIL_PASSWORD=SMTP_PASSWORD
MAIL_TIMEOUT=10
```

#### 3. Δημιουργία βάσης δεδομένων
```sql
-- Δημιουργία DB
CREATE DATABASE permissions_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

-- Δημιουργία dedicated DB user (principle of least privilege)
CREATE USER 'permissions_user'@'localhost'
  IDENTIFIED BY 'STRONG_PASSWORD_HERE';

GRANT SELECT, INSERT, UPDATE, DELETE
  ON permissions_db.* TO 'permissions_user'@'localhost';

FLUSH PRIVILEGES;

-- Import schema + seed
USE permissions_db;
SOURCE /path/to/permissions/database/schema.sql;
SOURCE /path/to/permissions/database/seed.sql;
```

#### 4. Ρύθμιση Apache (Production)

**Virtual Host:**
```apache
<VirtualHost *:80>
    ServerName permissions.hfiu.loc
    DocumentRoot "C:/inetpub/wwwroot/permissions/public"

    <Directory "C:/inetpub/wwwroot/permissions/public">
        AllowOverride All
        Require all granted
        Options -Indexes
    </Directory>

    # Block access to dotfiles
    <FilesMatch "^\.">
        Require all denied
    </FilesMatch>

    # Logging
    ErrorLog  "logs/permissions-error.log"
    CustomLog "logs/permissions-access.log" combined
</VirtualHost>
```

**Εναλλακτικά σε IIS:**
Δημιουργήστε `web.config` στον φάκελο `public/`:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<configuration>
  <system.webServer>
    <rewrite>
      <rules>
        <rule name="Route to index.php" stopProcessing="true">
          <match url="^(.*)$" ignoreCase="false" />
          <conditions>
            <add input="{REQUEST_FILENAME}" matchType="IsFile" negate="true" />
            <add input="{REQUEST_FILENAME}" matchType="IsDirectory" negate="true" />
          </conditions>
          <action type="Rewrite" url="index.php" appendQueryString="true" />
        </rule>
      </rules>
    </rewrite>
    <directoryBrowse enabled="false" />
  </system.webServer>
</configuration>
```

#### 5. Ρύθμιση Permissions αρχείων
```bash
# Ο web server χρειάζεται write access μόνο στο:
chmod 755 storage/logs/      # ή ανάλογα NTFS permissions σε Windows
```

#### 6. DNS Record
Δημιουργήστε A record στον internal DNS:
```
permissions.hfiu.loc → IP_OF_SERVER
```

---

## 13. Ρυθμίσεις Παραγωγικού

### Checklist Πριν το Go-Live

| # | Ενέργεια | Κατάσταση |
|---|----------|-----------|
| 1 | `.env` δημιουργημένο με production values | ☐ |
| 2 | `APP_ENV=production` | ☐ |
| 3 | `APP_URL` ρυθμισμένο σωστά | ☐ |
| 4 | DB user με minimal permissions (SELECT/INSERT/UPDATE/DELETE) | ☐ |
| 5 | DB password ισχυρό (>=16 χαρακτήρες) | ☐ |
| 6 | LDAP service account δημιουργημένο | ☐ |
| 7 | LDAP service account με minimal permissions (Read) | ☐ |
| 8 | AD groups δημιουργημένα (admins + managers) | ☐ |
| 9 | SMTP credentials ρυθμισμένα | ☐ |
| 10 | IP restrictions ρυθμισμένες (αν χρειάζονται) | ☐ |
| 11 | Apache mod_rewrite ενεργό | ☐ |
| 12 | PHP extensions ενεργά (ldap, gmp, mbstring, gd) | ☐ |
| 13 | DNS record δημιουργημένο | ☐ |
| 14 | `composer install --no-dev` εκτελέστηκε | ☐ |
| 15 | `display_errors = Off` στο php.ini | ☐ |
| 16 | `storage/logs/` writable από web server | ☐ |
| 17 | `.env` ΔΕΝ είναι accessible μέσω web | ☐ |
| 18 | Test login/logout | ☐ |
| 19 | Test CRUD δικαιωμάτων | ☐ |
| 20 | Test export (CSV, Excel, PDF) | ☐ |
| 21 | Test email sending | ☐ |

### Active Directory — Δημιουργία Service Account

```
1. Δημιουργήστε user: svc-permreg στο AD
2. Ρυθμίστε: "Password never expires"
3. Ρυθμίστε: "User cannot change password"
4. Δώστε Read permissions στο OU που είναι οι χρήστες
5. Βάλτε τα credentials στο .env
```

### Active Directory — Δημιουργία Groups

```
1. Δημιουργήστε Security Group: PermRegAdmins
   - Βάλτε τους IT admins ως members
2. Δημιουργήστε Security Group: PermRegManagers
   - Βάλτε τους Προϊσταμένους τμημάτων ως members
3. Ενημερώστε τα LDAP_ADMIN_GROUP / LDAP_MANAGER_GROUP στο .env
```

### PHP.ini — Ρυθμίσεις Production

```ini
; Απενεργοποίηση εμφάνισης errors
display_errors = Off
display_startup_errors = Off
log_errors = On
error_log = "C:/path/to/permissions/storage/logs/php_errors.log"

; Extensions
extension=ldap
extension=gmp
extension=mbstring
extension=gd
extension=openssl
extension=fileinfo
extension=pdo_mysql

; Performance
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000

; Upload limits (for future use)
upload_max_filesize = 10M
post_max_size = 12M

; Timezone
date.timezone = Europe/Athens
```

### Firewall Rules

| Πηγή | Προορισμός | Port | Πρωτόκολλο | Περιγραφή |
|------|-----------|------|------------|-----------|
| Web Server | LDAP Server | 389 | TCP | AD Authentication |
| Web Server | MySQL Server | 3306 | TCP | Database |
| Web Server | smtp.office365.com | 587 | TCP | Email (SMTP/TLS) |
| Clients | Web Server | 80 | TCP | HTTP Access |
| Clients | Web Server | 443 | TCP | HTTPS (προαιρετικό) |

---

## 14. Συντήρηση & Troubleshooting

### Logs
- **Application logs**: `storage/logs/`
- **PHP errors**: `php.ini → error_log`
- **Apache logs**: `logs/permissions-error.log`, `logs/permissions-access.log`

### Συνηθισμένα Προβλήματα

#### Δεν λειτουργεί η σύνδεση (LDAP)
```
Αιτία: LDAP host μη προσβάσιμο ή λάθος credentials
Λύση:
  1. Ελέγξτε LDAP_HOST στο .env
  2. Ελέγξτε ότι ο server βλέπει τον DC (ping, telnet port 389)
  3. Ελέγξτε LDAP_BIND_USER / LDAP_BIND_PASS
  4. Ελέγξτε ότι το php_ldap extension είναι ενεργό
```

#### 404 σε όλες τις σελίδες
```
Αιτία: mod_rewrite δεν είναι ενεργό ή AllowOverride Off
Λύση:
  1. Ενεργοποιήστε mod_rewrite: a2enmod rewrite
  2. AllowOverride All στο httpd.conf
  3. Restart Apache
```

#### 500 Internal Server Error
```
Αιτία: PHP error
Λύση:
  1. Ελέγξτε storage/logs/ ή Apache error log
  2. Ενεργοποιήστε display_errors προσωρινά στο .env: APP_ENV=development
  3. Ελέγξτε ότι composer install έχει τρέξει
```

#### Δεν στέλνει email
```
Αιτία: SMTP credentials ή firewall
Λύση:
  1. Ελέγξτε MAIL_HOST, MAIL_USERNAME, MAIL_PASSWORD στο .env
  2. Ελέγξτε firewall rule για port 587
  3. Ελέγξτε ότι ο SMTP server δέχεται connections
```

#### AD Sync δεν ενημερώνει τον manager
```
Αιτία: Ο χρήστης δεν έχει manager στο AD ή δεν υπάρχει η στήλη manager
Λύση:
  1. Ελέγξτε ότι τρέξατε: ALTER TABLE users ADD COLUMN manager VARCHAR(200) DEFAULT NULL AFTER phone;
  2. Ελέγξτε ότι ο χρήστης έχει manager στο AD (Active Directory Users and Computers)
  3. Πατήστε "Συγχρονισμός AD" στη σελίδα Χρηστών
```

### Backup

#### Database Backup (καθημερινό)
```bash
# Windows Task Scheduler
mysqldump -u permissions_user -p permissions_db > "C:\backups\permissions_db_%date%.sql"

# Linux crontab
0 2 * * * mysqldump -u permissions_user -pPASSWORD permissions_db > /backups/permissions_db_$(date +\%Y\%m\%d).sql
```

#### Application Backup
```bash
# Τα αρχεία εφαρμογής (χωρίς vendor/ και .env)
# Κρατήστε αντίγραφο του .env ξεχωριστά (ασφαλές μέρος)
```

---

## 15. Μελλοντικές Επεκτάσεις

### Προτεινόμενες βελτιώσεις

| Προτεραιότητα | Λειτουργία | Περιγραφή |
|---------------|-----------|-----------|
| Υψηλή | HTTPS | Εγκατάσταση SSL certificate |
| Υψηλή | Rate Limiting | Προστασία login από brute force |
| Μεσαία | Scheduled AD Sync | Αυτόματος συγχρονισμός κάθε βράδυ |
| Μεσαία | Notifications | Email ειδοποίηση πριν λήξει δικαίωμα |
| Μεσαία | Εισαγωγή δεδομένων | Import permissions από CSV/Excel |
| Χαμηλή | REST API | API endpoints για integration με άλλα συστήματα |
| Χαμηλή | Two-Factor Auth | Πρόσθετο επίπεδο ασφάλειας |
| Χαμηλή | Workflow Approval | Αίτημα → Έγκριση → Εφαρμογή δικαιώματος |

---

## Πληροφορίες Έκδοσης

| | |
|---|---|
| **Έκδοση** | 1.0 |
| **Ημερομηνία** | Μάρτιος 2026 |
| **Ανάπτυξη** | Τμήμα Ανάπτυξης και Υποστήριξης Εφαρμογών |
| **Υποδιεύθυνση** | Ψηφιακής Διακυβέρνησης |
| **Οργανισμός** | ΑΚΝΕΕΔ |
