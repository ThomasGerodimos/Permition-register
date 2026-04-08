# Μητρώο Δικαιωμάτων (Permission Register)

Web application for managing user access permissions to organizational resources, with Active Directory integration.


## Features

- **Active Directory Authentication** - LDAP login with automatic user sync (name, email, department, job title, manager)
- **Role-Based Access** - Admin (full CRUD), Manager (department-scoped read), Viewer (no access)
- **Permission Management** - Assign permissions to users for Applications, Shared Folders, and Shared Mailboxes
- **Bulk Assignment** - Assign permissions to multiple users across multiple resources at once
- **Network Graph** - Interactive vis.js graph showing user-resource access relationships
- **Audit Log** - Full history of all permission changes with old/new values and IP tracking
- **Export** - CSV, Excel (.xlsx), PDF reports per user or department
- **Email Reports** - Send permission reports via email with PDF/Excel attachments
- **IP Restrictions** - Restrict login access by IP/CIDR per role
- **Live Filtering** - Real-time search with debounce on all list pages (no page reload)
- **Sortable Tables** - Click column headers to sort (supports text, numbers, dates, Greek locale)
- **Smart Pagination** - Ellipsis-based pagination across all listing pages
- **Impersonate Mode** - Admins can preview the manager view for testing
- **Responsive UI** - Bootstrap 5 with sidebar navigation

## Tech Stack

| Component | Technology |
|-----------|-----------|
| Backend | PHP 8.0+ (custom MVC, no framework) |
| Database | MySQL 5.7+ / MariaDB 10.3+ |
| Frontend | Bootstrap 5, Bootstrap Icons, vanilla JS |
| Auth | LDAP / Active Directory |
| Email | PHPMailer (SMTP) |
| Excel | PhpSpreadsheet |
| PDF | TCPDF |
| Word | PhpWord |
| Graph | vis.js Network |
| Server | Apache (WAMP/LAMP) with mod_rewrite |

## Project Structure

```
permissions/
├── config/              # Configuration (reads from .env)
│   ├── config.php       # App, DB, session, pagination settings
│   ├── ldap.php         # LDAP/AD connection settings
│   └── mail.php         # SMTP mail settings
├── database/
│   ├── schema.sql       # Full database schema
│   └── seed.sql         # Default resource types & seed data
├── docs/                # Documentation (MD + DOCX)
├── public/              # Web root (document root points here)
│   ├── index.php        # Front controller / router
│   ├── .htaccess        # URL rewriting rules
│   └── assets/
│       ├── css/app.css  # Application styles
│       ├── js/app.js    # Sidebar toggle, table sorting
│       └── images/      # Logo and static images
├── src/                 # Application source (PSR-4: App\)
│   ├── Auth/            # LdapService, AuthController, IpRestriction, Middleware
│   ├── Controllers/     # Dashboard, Permission, User, Audit, Export, Email, Settings, Api
│   ├── Core/            # Database, Router, Session, Csrf, View, Config, Env
│   ├── Models/          # Permission, User, Resource, AuditLog
│   └── Services/        # AdService, ExportService, MailService
├── storage/logs/        # Application logs
├── vendor/              # Composer dependencies
├── views/               # PHP view templates
│   ├── auth/login.php
│   ├── dashboard/       # Dashboard with stats cards
│   ├── permissions/     # List, form, bulk-form, user_view
│   ├── settings/        # Resources, IP restrictions
│   ├── audit/           # Audit log viewer
│   ├── network-graph/   # vis.js network visualization
│   └── layout/main.php  # Main layout (header, sidebar, footer)
├── .env.example         # Environment template
├── composer.json        # PHP dependencies
└── .gitignore
```

## Requirements

- PHP 8.0 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Apache with `mod_rewrite` enabled
- PHP extensions: `ldap`, `pdo_mysql`, `mbstring`, `gd`, `zip`, `xml`
- Composer
- Active Directory domain (for authentication)
- SMTP server (for email reports)

## Installation

### 1. Clone the repository

```bash
git clone https://github.com/ThomasGerodimos/Permition-register.git permissions
cd permissions
```

### 2. Install dependencies

```bash
composer install
```

### 3. Configure environment

```bash
cp .env.example .env
```

Edit `.env` with your settings:

```env
# Database
DB_HOST=127.0.0.1
DB_NAME=permissions_db
DB_USER=your_db_user
DB_PASS=your_db_password

# Active Directory
LDAP_HOST=ldap://your-dc.domain.loc
LDAP_DOMAIN=domain.loc
LDAP_BASE_DN=DC=domain,DC=loc
LDAP_BIND_USER=CN=ServiceAccount,OU=Service Accounts,DC=domain,DC=loc
LDAP_BIND_PASS=your_bind_password
LDAP_ADMIN_GROUP=CN=AppAdmins,OU=Groups,DC=domain,DC=loc
LDAP_MANAGER_GROUP=CN=Managers,OU=Groups,DC=domain,DC=loc

# Mail
MAIL_HOST=smtp.office365.com
MAIL_USERNAME=permissions@yourdomain.gr
MAIL_PASSWORD=your_mail_password
```

### 4. Create the database

```sql
CREATE DATABASE permissions_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Import the schema and seed data:

```bash
mysql -u root -p permissions_db < database/schema.sql
mysql -u root -p permissions_db < database/seed.sql
```

### 5. Configure Apache

Point your document root to the `public/` directory:

```apache
<VirtualHost *:80>
    ServerName permissions.yourdomain.loc
    DocumentRoot "C:/path/to/permissions/public"

    <Directory "C:/path/to/permissions/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Or for WAMP, place the project in `C:\wamp64\www\permissions` and access via `http://localhost/permissions`.

### 6. Set permissions

Ensure the `storage/logs/` directory is writable by the web server.

## Configuration

### Roles

| Role | Access |
|------|--------|
| **Admin** | Full CRUD on all permissions, resources, settings. Access to audit log, exports, bulk assignment, network graph. |
| **Manager** | Read-only view of their department's permissions and users. Limited dashboard. |
| **Viewer** | Cannot login (no access to the application). |

Roles are determined by **AD group membership** during login:
- Members of `LDAP_ADMIN_GROUP` become **admin**
- Members of `LDAP_MANAGER_GROUP` become **manager**
- Others are **viewer** (denied login)

### IP Restrictions

Admins can configure IP/CIDR rules per role via **Settings > IP Restrictions**. If no rules exist for a role, all IPs are allowed. If rules exist, only matching IPs can login.

Examples:
- `192.168.1.0/24` - Allow entire subnet
- `10.0.0.50` - Allow single IP
- `127.0.0.1` - Allow localhost

### Resource Types

Three built-in resource types with configurable permission levels:

| Type | Permissions |
|------|------------|
| Application | Read, Write, Read/Write, Admin, No Access |
| Shared Folder | Read, Read/Write, Full Control, No Access |
| Shared Mailbox | Full Access, Send As, Send On Behalf, Read Only |

Permission levels per type are stored as JSON in the `resource_types` table and can be modified via SQL.

## Usage

### Adding Permissions

1. Go to **Permissions > New Permission**
2. Select resource type, resource, and permission level
3. Search for user (autocomplete from Active Directory)
4. Set optional expiry date and notes
5. Submit

### Bulk Assignment

1. Go to **Bulk Assignment**
2. Select resource type
3. Check one or more resources (use "Select All")
4. Choose permission level
5. Search and add multiple users
6. Review summary and confirm

### Exports

Available from the permissions list page:
- **CSV** - Plain text, importable to Excel
- **Excel** - Formatted .xlsx with headers and auto-width columns
- **PDF** - Printable report with organization branding

### Email Reports

Send permission reports (per user or per department) as PDF or Excel attachments via the organization's SMTP server.

## Deployment to Production

### Checklist

1. Set `APP_ENV=production` and `APP_URL` in `.env`
2. Configure production database credentials
3. Configure LDAP settings for production AD
4. Configure SMTP settings
5. Set up IP restrictions for admin/manager roles
6. Ensure `storage/logs/` is writable
7. Ensure `.env` is **NOT** accessible from the web
8. Run `composer install --no-dev --optimize-autoloader`
9. Import `database/schema.sql` and `database/seed.sql`
10. Configure Apache VirtualHost pointing to `public/`

### Security Notes

- The `.env` file contains sensitive credentials - never commit it to version control
- All forms use CSRF protection tokens
- All user input is escaped with `htmlspecialchars()`
- All database queries use prepared statements (PDO)
- Sessions are bound to IP and have configurable lifetime
- Audit log tracks all permission changes with IP address

## Screenshots

*Coming soon*

