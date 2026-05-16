# The Oaks Compliance Tracker

Private PHP 8.2+ compliance tracker for Georgia Clinical Social Worker (CSW/LCSW) license renewal, continuing education tracking, CE documentation, reports, and reminder emails.

The app is intended for internal administrative use only. It must not store client names, diagnoses, treatment notes, appointment details, insurance information, payment records, or other PHI.

## Current Version

V1 includes Phases 1–7:

- Secure login/session foundation.
- License profile management.
- Renewal cycle management.
- CE course tracking.
- Document upload/download management.
- Georgia CSW compliance calculator.
- Dashboard status cards.
- Printable, CSV, PDF, and audit ZIP reports.
- SMTP/test email setup.
- Reminder settings, reminder log, and daily cron reminder processing.

## Project Structure

```text
private/license_tracker/
  app/
  config/
  cron/
  database/
  scripts/
  uploads/
  vendor/

public_html/license/
  index.php
  login.php
  dashboard.php
  ...
```

`private/license_tracker/` contains application code, configuration, SQLite database files, uploads, logs, Composer dependencies, and scripts.

`public_html/license/` contains only public web entrypoints.

Do not place the entire app inside `public_html`.

## Local Setup

1. Install PHP 8.2+.

   Required/recommended PHP extensions:

   ```text
   pdo
   pdo_sqlite
   sqlite3
   fileinfo
   mbstring
   zip
   openssl
   curl
   ```

2. Install Composer dependencies:

   ```bash
   cd private/license_tracker
   composer install
   ```

   If Composer is not installed globally but `composer.phar` is present:

   ```bash
   php composer.phar install
   ```

3. Optional local overrides:

   ```bash
   cp config/config.example.php config/local.php
   ```

4. Run migrations:

   ```bash
   php database/migrate.php
   ```

5. Run seed data:

   ```bash
   php database/seed.php
   ```

6. Create the first admin user:

   ```bash
   php cli_create_admin.php admin@example.com "Admin User" "ChangeMeNow!123"
   ```

7. Serve locally from the repo root:

   ```bash
   php -S localhost:8000 -t public_html
   ```

8. Open:

   ```text
   http://localhost:8000/license/login.php
   ```

9. Logout is POST-only through a CSRF-protected form.

## Production Deployment Notes

### Current production test path

Current production test path:

```text
https://theoakstherapy.com/license
```

For this path, production config should use:

```php
'app' => [
    'base_url' => 'https://theoakstherapy.com/license',
    'base_path' => '/license',
]
```

### Future subdomain path

If deployed later to:

```text
https://license.theoakstherapy.com
```

use:

```php
'app' => [
    'base_url' => 'https://license.theoakstherapy.com',
    'base_path' => '',
]
```

### Recommended server layout

The app should be deployed so private files are outside the public web root:

```text
/data0/theoakstherapy.com/
  private/
    license_tracker/
      app/
      config/
      cron/
      database/
      scripts/
      uploads/
      vendor/
  public_html/
    license/
      index.php
      login.php
      dashboard.php
      ...
```

### Required writable paths

The following server paths must exist and be writable by PHP:

```text
private/license_tracker/database/
private/license_tracker/uploads/
private/license_tracker/logs/
```

Typical permissions:

```text
database/ 755
uploads/ 755
logs/ 755
database/license_tracker.sqlite 644 or 664
config/local.php 600 if supported
```

Use the least-permissive setting that still allows the app to function on the host.

### Production config

Create:

```text
private/license_tracker/config/local.php
```

from:

```text
private/license_tracker/config/config.example.php
```

Do not commit `config/local.php`.

Example structure:

```php
<?php

declare(strict_types=1);

return [
    'app' => [
        'name' => 'The Oaks Compliance Tracker',
        'env' => 'production',
        'debug' => false,
        'base_url' => 'https://theoakstherapy.com/license',
        'base_path' => '/license',
        'session_name' => 'oaks_compliance_session',
    ],
    'database' => [
        'driver' => 'sqlite',
        'path' => __DIR__ . '/../database/license_tracker.sqlite',
    ],
    'smtp' => [
        'host' => '',
        'port' => 587,
        'encryption' => 'tls',
        'username' => '',
        'password' => '',
        'from_email' => 'notifications@theoakstherapy.com',
        'from_name' => 'The Oaks Compliance Tracker',
    ],
];
```

## Production Schema Compatibility Notes

The current SQLite schema includes some legacy/compatibility columns that the service layer must continue to populate.

### Renewal cycles

`RenewalCycleService` must insert/update values for:

```text
required_hours
ethics_required_hours
```

These are required by the current `renewal_cycles` table.

For the current Georgia CSW cycle, the expected values are:

```text
required_hours = 35.0
ethics_required_hours = 5.0
```

### Documents

`DocumentService` must insert values for both:

```text
file_path
storage_path
```

Both should point to the same private relative storage path, for example:

```text
uploads/generated-safe-filename.pdf
```

These fields are required by the current `documents` table.

## cPanel / Managed Hosting Deployment

If the host does not provide SSH or Terminal access, prepare dependencies and the SQLite database locally, then upload them through File Manager.

### Local build before upload

From the local project:

```bash
cd private/license_tracker
composer install --no-dev --optimize-autoloader
php database/migrate.php
php database/seed.php
php cli_create_admin.php your-admin-email@example.com "Your Name" "StrongPassword!"
```

Then upload:

```text
private/license_tracker/app/
private/license_tracker/config/
private/license_tracker/cron/
private/license_tracker/database/
private/license_tracker/scripts/
private/license_tracker/uploads/
private/license_tracker/vendor/
private/license_tracker/cli_create_admin.php
private/license_tracker/composer.json
private/license_tracker/composer.lock
```

to:

```text
/data0/theoakstherapy.com/private/license_tracker/
```

Upload:

```text
public_html/license/
```

to:

```text
/data0/theoakstherapy.com/public_html/license/
```

Do not upload:

```text
.git/
config/local.php from Git
composer.phar
error_log
logs/
uploaded test documents unless intentional
```

The live server should have its own `config/local.php`.

## Security Notes

- No public registration.
- Login required for protected pages.
- Passwords stored with `password_hash()`.
- Login verified with `password_verify()`.
- CSRF tokens required for forms.
- Logout is POST-only and CSRF-protected.
- Prepared statements used for database operations.
- User-facing output should be escaped with `e()`.
- Uploaded files are stored outside `public_html`.
- Downloads are served through authenticated routes.
- SMTP secrets must remain in `config/local.php`.
- No client PHI should be stored.

## Phase 1: Foundation

Includes:

- Secure session-based authentication.
- SQLite connection and migration runner.
- Core schema for users, licenses, renewal cycles, CE courses, documents, requirement rules, reminders, reminder logs, and app settings.
- Georgia CSW baseline requirement seed.
- CLI admin bootstrap installer.

Primary routes:

```text
/license/login.php
/license/logout.php
/license/dashboard.php
```

## Phase 2: License Profile and Renewal Cycles

Routes:

```text
/license/license.php
/license/license_edit.php
/license/cycles.php
/license/cycle_create.php
/license/cycle_edit.php
```

Features:

- License profile create/edit.
- Renewal cycle create/edit.
- One active renewal cycle per license.
- Validation for renewal cycle date ranges and submitted/paid date fields.

Validation check:

```bash
php scripts/check_cycle_validation.php
```

## Phase 3: CE Courses

Routes:

```text
/license/ce/index.php
/license/ce/create.php
/license/ce/edit.php?id=...
/license/ce/delete.php?id=...
```

Features:

- CE course CRUD.
- Category, format, delivery-mode, date, and hour validation.
- POST + CSRF delete.
- Dashboard CE totals for the active cycle.

Validation check:

```bash
php scripts/check_ce_course_validation.php
```

## Phase 4: Documents

Routes:

```text
/license/documents.php
/license/document_upload.php
/license/document_delete.php
/license/download.php?id=...
```

Features:

- Upload CE certificates and renewal documents.
- Store files under `private/license_tracker/uploads/`.
- Authenticated downloads.
- POST + CSRF delete.
- CE certificate status integration.

Validation check:

```bash
php scripts/check_document_validation.php
```

## Phase 5: Compliance Calculator

Adds:

```text
App\Services\ComplianceCalculator
```

Features:

- Cycle-level Georgia CSW compliance evaluation.
- Dashboard requirement cards.
- Warnings and errors.
- Missing documentation detection.
- CE issue indicators.

Rules enforced:

```text
Total CE minimum: 35
Ethics minimum: 5 synchronous hours
Core minimum: 15
Related maximum: 15
Asynchronous maximum: 10
Independent study maximum: 5
Single activity maximum: 20 unless professional conference
Counted CE courses require documentation
```

Validation check:

```bash
php scripts/check_compliance_calculator.php
```

Current limitation:

- Excess ethics hours are not automatically reassigned toward core hours unless manually categorized or supported in a later version.

## Phase 6: Reports and Exports

Routes:

```text
/license/reports/index.php
/license/reports/ce-summary.php
/license/reports/ce-export-csv.php
/license/reports/ce-summary-pdf.php
/license/reports/audit-packet.php
```

Features:

- Printable HTML report.
- CSV export using `fputcsv`.
- Formula-injection mitigation for CSV cells beginning with `=`, `+`, `-`, `@`, tab, or carriage return.
- PDF report using Dompdf.
- Audit ZIP packet with scoped cycle documents.
- Missing physical files are skipped and recorded in a warning text file.

Validation check:

```bash
php scripts/check_report_exports.php
```

Browser test:

```text
Sign in → Reports → choose cycle → run each export.
```

## Phase 7: SMTP and Reminders

Routes/scripts:

```text
/license/settings.php
/license/reminders_log.php
private/license_tracker/cron/send_reminders.php
```

Features:

- SMTP config loaded from `config/local.php`.
- Non-secret reminder settings stored in app settings.
- CSRF-protected test email action.
- Daily reminder cron.
- Reminder duplicate prevention.
- Reminder log UI.

Local cron run:

```bash
php cron/send_reminders.php
```

cPanel cron example:

```bash
/usr/local/bin/php /data0/theoakstherapy.com/private/license_tracker/cron/send_reminders.php >/dev/null 2>&1
```

If the cPanel PHP path is different, use the host-specific PHP binary path.

Reminder check:

```bash
php scripts/check_reminder_logic.php
```

Google Workspace SMTP examples:

```text
Gmail SMTP:
host: smtp.gmail.com
port: 587
encryption: tls
username: full Workspace email address
password: Google app password

Google SMTP relay:
host: smtp-relay.gmail.com
port: 587
encryption: tls
```

## Full Validation Checklist

Run from:

```bash
cd private/license_tracker
```

Commands:

```bash
composer install
php database/migrate.php
php database/seed.php
php scripts/check_cycle_validation.php
php scripts/check_ce_course_validation.php
php scripts/check_document_validation.php
php scripts/check_compliance_calculator.php
php scripts/check_report_exports.php
php scripts/check_reminder_logic.php
```

Optional syntax checks:

```bash
php -l app/Services/RenewalCycleService.php
php -l app/Services/DocumentService.php
php -l app/Services/ComplianceCalculator.php
php -l app/Services/ReportService.php
php -l app/Services/EmailService.php
php -l app/Services/ReminderService.php
php -l cron/send_reminders.php
```

## Known V1 Limitations

- Designed for a single primary license/admin workflow.
- SMTP credentials are configured manually in `config/local.php`, not managed through the UI.
- Reminder processing follows the V1 single-license structure.
- Excess ethics hours are not automatically reassigned toward core hours.
- No two-factor authentication yet.
- No encrypted document storage yet.
- CE certificate upload currently exists as a document workflow; a future UX pass should allow uploading a certificate directly during CE course creation/editing.
- Global navigation and form labeling need a polish pass for consistent usability across all authenticated pages.

## Recommended Next Improvements

1. Add password reset/change tooling.
2. Add CE certificate upload directly to CE create/edit.
3. Add a shared global navigation partial across all authenticated pages.
4. Improve form labels and field descriptions.
5. Add stronger production diagnostics that do not expose sensitive paths publicly.
6. Add database backup/export instructions.
7. Add optional two-factor authentication.
8. Add encrypted document storage if needed.
