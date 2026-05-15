<?php

declare(strict_types=1);

return [
"ALTER TABLE reminder_logs ADD COLUMN renewal_cycle_id INTEGER",
"ALTER TABLE reminder_logs ADD COLUMN reminder_key TEXT",
"ALTER TABLE reminder_logs ADD COLUMN reminder_type TEXT",
"ALTER TABLE reminder_logs ADD COLUMN recipient_email TEXT",
"ALTER TABLE reminder_logs ADD COLUMN related_deadline TEXT",
"ALTER TABLE reminder_logs ADD COLUMN sent_date TEXT",
"ALTER TABLE reminder_logs ADD COLUMN subject TEXT",
"INSERT OR IGNORE INTO app_settings (setting_key, setting_value) VALUES ('app_name','The Oaks Compliance Tracker')",
"INSERT OR IGNORE INTO app_settings (setting_key, setting_value) VALUES ('business_name','The Oaks')",
"INSERT OR IGNORE INTO app_settings (setting_key, setting_value) VALUES ('admin_recipient_email','')",
"INSERT OR IGNORE INTO app_settings (setting_key, setting_value) VALUES ('licensee_recipient_email','')",
"INSERT OR IGNORE INTO app_settings (setting_key, setting_value) VALUES ('reminder_schedule_enabled','1')",
"INSERT OR IGNORE INTO app_settings (setting_key, setting_value) VALUES ('monthly_summary_enabled','1')",
"INSERT OR IGNORE INTO app_settings (setting_key, setting_value) VALUES ('sender_email','')",
"INSERT OR IGNORE INTO app_settings (setting_key, setting_value) VALUES ('reminder_days_before_deadline','180,120,90,60,30,14,7,1,0')"
];
