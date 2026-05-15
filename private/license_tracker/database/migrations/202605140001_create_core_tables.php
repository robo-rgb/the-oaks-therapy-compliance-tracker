<?php

declare(strict_types=1);

return [
    "CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT NOT NULL UNIQUE,
        full_name TEXT NOT NULL,
        password_hash TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT 'admin',
        is_active INTEGER NOT NULL DEFAULT 1,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS licenses (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        license_type TEXT NOT NULL,
        license_number TEXT,
        issued_on TEXT,
        expires_on TEXT,
        status TEXT NOT NULL DEFAULT 'active',
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS renewal_cycles (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        license_id INTEGER NOT NULL,
        cycle_start TEXT NOT NULL,
        cycle_end TEXT NOT NULL,
        required_hours REAL NOT NULL,
        ethics_required_hours REAL NOT NULL DEFAULT 0,
        status TEXT NOT NULL DEFAULT 'open',
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(license_id) REFERENCES licenses(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS ce_courses (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        license_id INTEGER NOT NULL,
        course_title TEXT NOT NULL,
        provider_name TEXT,
        completed_on TEXT,
        hours REAL NOT NULL,
        course_type TEXT,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(license_id) REFERENCES licenses(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS documents (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        license_id INTEGER NOT NULL,
        document_type TEXT NOT NULL,
        original_filename TEXT NOT NULL,
        storage_path TEXT NOT NULL,
        uploaded_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(license_id) REFERENCES licenses(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS requirement_rules (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        license_type TEXT NOT NULL,
        state_code TEXT NOT NULL,
        rule_code TEXT NOT NULL,
        description TEXT NOT NULL,
        cycle_length_months INTEGER NOT NULL,
        total_hours_required REAL NOT NULL,
        ethics_hours_required REAL NOT NULL DEFAULT 0,
        effective_date TEXT,
        is_active INTEGER NOT NULL DEFAULT 1,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS reminders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        license_id INTEGER,
        reminder_type TEXT NOT NULL,
        scheduled_for TEXT NOT NULL,
        payload_json TEXT,
        status TEXT NOT NULL DEFAULT 'pending',
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY(license_id) REFERENCES licenses(id) ON DELETE SET NULL
    )",
    "CREATE TABLE IF NOT EXISTS reminder_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        reminder_id INTEGER NOT NULL,
        attempted_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        status TEXT NOT NULL,
        response_message TEXT,
        FOREIGN KEY(reminder_id) REFERENCES reminders(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS app_settings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        setting_key TEXT NOT NULL UNIQUE,
        setting_value TEXT,
        updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS migrations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        migration TEXT NOT NULL UNIQUE,
        applied_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )"
];
