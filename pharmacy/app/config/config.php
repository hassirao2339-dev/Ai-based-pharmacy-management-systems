<?php

/**
 * config.php
 *
 * Reads the .env file and turns every setting into a PHP constant.
 * A constant (for example DB_NAME) can be used anywhere in the project
 * and cannot be changed by accident while a page is running.
 */

$envFilePath = ROOT_PATH . '/.env';

if (!file_exists($envFilePath)) {
    // The normal error page is not loaded yet, so we show plain text.
    http_response_code(500);
    exit('Setup error: the .env file was not found in the project folder.');
}

// INI_SCANNER_TYPED turns true/false into booleans and numbers into integers.
$env = parse_ini_file($envFilePath, false, INI_SCANNER_TYPED);

if ($env === false) {
    http_response_code(500);
    exit('Setup error: the .env file has a mistake. Text values need double quotes, and comments must start with a semicolon.');
}

// ---------- Application ----------
// If a setting is missing, the value after ?? is used. The defaults are
// the SAFE choices (production mode, debug off).
define('APP_NAME', (string) ($env['APP_NAME'] ?? 'Pharmacy'));
define('APP_ENV', (string) ($env['APP_ENV'] ?? 'production'));
define('APP_DEBUG', (bool) ($env['APP_DEBUG'] ?? false));
define('APP_HTTPS', (bool) ($env['APP_HTTPS'] ?? false));
define('APP_TIMEZONE', (string) ($env['APP_TIMEZONE'] ?? 'Asia/Karachi'));
define('BASE_URL', rtrim((string) ($env['BASE_URL'] ?? ''), '/'));

// ---------- Database ----------
define('DB_HOST', (string) ($env['DB_HOST'] ?? '127.0.0.1'));
define('DB_PORT', (int) ($env['DB_PORT'] ?? 3306));
define('DB_NAME', (string) ($env['DB_NAME'] ?? 'pharmacy_db'));
define('DB_USER', (string) ($env['DB_USER'] ?? 'root'));
define('DB_PASS', (string) ($env['DB_PASS'] ?? ''));

// ---------- Session ----------
define('SESSION_NAME', (string) ($env['SESSION_NAME'] ?? 'PMSSESSID'));
define('SESSION_TIMEOUT_MINUTES', (int) ($env['SESSION_TIMEOUT_MINUTES'] ?? 30));

// ---------- Private storage (outside the public web folder) ----------
define('STORAGE_PATH', rtrim((string) ($env['STORAGE_PATH'] ?? ''), '/'));
define('PRESCRIPTION_PATH', STORAGE_PATH . '/prescriptions');
define('REPORT_PATH', STORAGE_PATH . '/reports');
define('LOG_PATH', STORAGE_PATH . '/logs');

// ---------- Security (used from Phase 2) ----------
define('BCRYPT_COST', (int) ($env['BCRYPT_COST'] ?? 12));
define('LOGIN_MAX_ATTEMPTS', (int) ($env['LOGIN_MAX_ATTEMPTS'] ?? 5));
define('LOCKOUT_MINUTES', (int) ($env['LOCKOUT_MINUTES'] ?? 15));
define('RESET_LINK_MINUTES', (int) ($env['RESET_LINK_MINUTES'] ?? 30));

// ---------- Inventory (used from Phase 5) ----------
define('EXPIRY_WARNING_DAYS', (int) ($env['EXPIRY_WARNING_DAYS'] ?? 30));

// ---------- Prescription upload and OCR (used in Phase 11) ----------
define('UPLOAD_MAX_MB', (int) ($env['UPLOAD_MAX_MB'] ?? 5));
define('PYTHON_PATH', (string) ($env['PYTHON_PATH'] ?? ''));
define('TESSERACT_PATH', (string) ($env['TESSERACT_PATH'] ?? ''));
define('OCR_TIMEOUT_SECONDS', (int) ($env['OCR_TIMEOUT_SECONDS'] ?? 8));

// ---------- Email (used in Phase 2) ----------
define('MAIL_ENABLED', (bool) ($env['MAIL_ENABLED'] ?? false));
define('SMTP_HOST', (string) ($env['SMTP_HOST'] ?? ''));
define('SMTP_PORT', (int) ($env['SMTP_PORT'] ?? 587));
define('SMTP_USERNAME', (string) ($env['SMTP_USERNAME'] ?? ''));
define('SMTP_PASSWORD', (string) ($env['SMTP_PASSWORD'] ?? ''));
define('MAIL_FROM_ADDRESS', (string) ($env['MAIL_FROM_ADDRESS'] ?? ''));
define('MAIL_FROM_NAME', (string) ($env['MAIL_FROM_NAME'] ?? APP_NAME));

// The raw array is no longer needed, so passwords are not left lying around in variables.
unset($env, $envFilePath);