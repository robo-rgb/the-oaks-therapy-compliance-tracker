# The Oaks Compliance Tracker (Phase 1)

Private PHP 8.2+ compliance tracker foundation with SQLite, Composer autoloading, secure login, migrations, seed data, and first-admin installer.

## Project Structure

- `private/license_tracker/` application core and private data.
- `public_html/license/` public web entrypoints.

## Phase 1 Includes

- Secure session-based authentication (no public registration).
- `password_hash()` storage and `password_verify()` login.
- CSRF helpers and HTML escaping helper.
- SQLite connection and migration runner.
- Core schema for users, licenses, renewal cycles, CE courses, documents, requirement rules, reminders, reminder logs, app settings.
- Georgia CSW baseline requirement seed (`135-9-.01`).
- CLI admin bootstrap installer.

## Local Setup

1. Install PHP 8.2+ and Composer.
2. Install dependencies:
   ```bash
   cd private/license_tracker
   composer install
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
6. Create first admin user:
   ```bash
   php cli_create_admin.php admin@example.com "Admin User" "ChangeMeNow!123"
   ```
7. Serve locally from repo root:
   ```bash
   php -S localhost:8000 -t public_html
   ```
8. Open `http://localhost:8000/license/login.php` and sign in.
9. Logout is POST-only via CSRF-protected form on dashboard.

## cPanel Deployment (Phase 1)

1. Upload repo contents so that:
   - `public_html/license` remains under cPanel public web root.
   - `private/license_tracker` remains outside (or inaccessible via web).
2. SSH into server and run:
   ```bash
   cd /path/to/private/license_tracker
   composer install --no-dev --optimize-autoloader
   php database/migrate.php
   php database/seed.php
   php cli_create_admin.php your-admin-email@example.com "Your Name" "StrongPassword!"
   ```
3. Copy config for production:
   ```bash
   cp config/config.example.php config/local.php
   ```
   Then edit DB path/base URL/env values.
4. Ensure file permissions allow PHP to write:
   - `private/license_tracker/database/`
   - `private/license_tracker/uploads/`
   - `private/license_tracker/logs/`
5. Enable HTTPS and verify login/logout flow.

## Security Notes

- No SMTP secrets stored.
- No PHI/client data included.
- Prepared statements used for DB operations.
- CSRF tokens required for forms.
- Escape all user-facing output with `e()`.


## Phase 2 (License + Cycles)
- New pages: `/license/license.php`, `/license/license_edit.php`, `/license/cycles.php`, `/license/cycle_create.php`, `/license/cycle_edit.php`.
- Dashboard now summarizes active license + active cycle.
- Renewal cycle validation check script: `php scripts/check_cycle_validation.php` from `private/license_tracker`.


## Phase 3 (CE Courses)
- CE routes: `/license/ce/index.php`, `/license/ce/create.php`, `/license/ce/edit.php?id=...`, `/license/ce/delete.php?id=...` (POST + CSRF).
- Dashboard includes CE totals for active cycle.
- Validation check: `php scripts/check_ce_course_validation.php` from `private/license_tracker`.


## Phase 4 (Documents)
- Routes: `/license/documents.php`, `/license/document_upload.php`, `/license/document_delete.php` (POST+CSRF), `/license/download.php?id=...`.
- Storage path: `private/license_tracker/uploads/` (outside `public_html`).
- Ensure web user can write to uploads directory.
- Validation check: `php scripts/check_document_validation.php` from `private/license_tracker`.
- cPanel: keep uploads outside `public_html` when possible.


## Phase 5 (Compliance Calculator)
- Added `App\Services\ComplianceCalculator` for cycle-level compliance evaluation and dashboard status cards.
- Dashboard now surfaces warnings/errors and overall compliance status.
- Check command: `php scripts/check_compliance_calculator.php` from `private/license_tracker`.
- Limitation: excess ethics hours are not auto-assigned to core in Phase 5.


## Phase 6 (Reports & Exports)
- Report routes: `/license/reports/index.php`, `/license/reports/ce-summary.php`, `/license/reports/ce-export-csv.php`, `/license/reports/ce-summary-pdf.php`, `/license/reports/audit-packet.php`.
- PDF uses Dompdf (already included in composer).
- CSV export uses `fputcsv` and formula-injection sanitization.
- Audit packet ZIP includes scoped cycle files only and skips missing files with warning text file.
- Check command: `php scripts/check_report_exports.php`.
- Browser test: sign in, open Reports, choose cycle, run each export.


## Phase 7 (SMTP + Reminders)
- SMTP secrets go in `private/license_tracker/config/local.php` under `smtp` keys (host/port/encryption/username/password/from_email/from_name).
- Configure reminder settings at `/license/settings.php`; send a CSRF-protected test email action there.
- Reminder log page: `/license/reminders_log.php`.
- Daily cron script: `private/license_tracker/cron/send_reminders.php`.
- Local run: `php cron/send_reminders.php` from `private/license_tracker`.
- cPanel cron example: `/usr/local/bin/php /home/ACCOUNT/private/license_tracker/cron/send_reminders.php >/dev/null 2>&1`.
- Reminder check command: `php scripts/check_reminder_logic.php`.
- Google Workspace SMTP relay example placeholders: host `smtp-relay.gmail.com`, port `587`, encryption `tls`, username/password placeholders in local config only.
