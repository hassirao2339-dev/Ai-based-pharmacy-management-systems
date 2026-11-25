<?php

/**
 * bootstrap.php
 *
 * Every page (controller) loads this file on its first line, for example:
 *     require_once __DIR__ . '/app/bootstrap.php';
 *
 * It prepares everything a page needs, always in the same order:
 *   1. Folder paths
 *   2. Settings from .env
 *   3. Error reporting and time zone
 *   4. Function files and models
 *   5. Session
 *   6. Friendly page for unexpected errors
 *   7. Database connection ($pdo)
 *   8. Account check for logged-in users
 */

// ---------------------------------------------------------------------
// 1. Folder paths
// ---------------------------------------------------------------------
// __DIR__ is the folder this file is in: .../htdocs/pharmacy/app
define('APP_PATH', __DIR__);
define('ROOT_PATH', dirname(__DIR__));

// ---------------------------------------------------------------------
// 2. Settings from .env (creates constants such as DB_NAME and APP_DEBUG)
// ---------------------------------------------------------------------
require_once APP_PATH . '/config/config.php';

// ---------------------------------------------------------------------
// 3. Error reporting and time zone
// ---------------------------------------------------------------------
error_reporting(E_ALL);

// Show PHP errors on the screen only while developing.
ini_set('display_errors', APP_DEBUG ? '1' : '0');

// Always write errors to a log file that the browser cannot open.
ini_set('log_errors', '1');
ini_set('error_log', LOG_PATH . '/php_errors.log');

date_default_timezone_set(APP_TIMEZONE);

// ---------------------------------------------------------------------
// 4. Function files and models
// ---------------------------------------------------------------------
require_once APP_PATH . '/config/database.php';
require_once APP_PATH . '/helpers/functions.php';
require_once APP_PATH . '/helpers/csrf.php';
require_once APP_PATH . '/helpers/auth.php';
require_once APP_PATH . '/helpers/validation.php';
require_once APP_PATH . '/helpers/mailer.php';
require_once APP_PATH . '/helpers/pdf.php';
require_once APP_PATH . '/helpers/ocr.php';

// Models: one class per database table (the "M" in MVC).
// New models are added to this list in later phases.
require_once APP_PATH . '/models/User.php';
require_once APP_PATH . '/models/Medicine.php';
require_once APP_PATH . '/models/Inventory.php';
require_once APP_PATH . '/models/Cart.php';
require_once APP_PATH . '/models/Order.php';
require_once APP_PATH . '/models/AuditLog.php';
require_once APP_PATH . '/models/AdminStats.php';
require_once APP_PATH . '/models/StockAlert.php';
require_once APP_PATH . '/models/Trending.php';
require_once APP_PATH . '/models/Report.php';
require_once APP_PATH . '/models/MedicineMatcher.php';
require_once APP_PATH . '/models/Prescription.php';

// ---------------------------------------------------------------------
// 5. Security headers, then the session
// ---------------------------------------------------------------------
// These tell the browser to switch on protections it already has.
// They must be sent before any page content.

// Do not tell visitors which PHP version runs the site.
header_remove('X-Powered-By');

// No other website may show our pages inside a frame (stops "clickjacking").
header('X-Frame-Options: DENY');

// The browser must use the file type we send, never guess a different one.
header('X-Content-Type-Options: nosniff');

// Other websites only learn our domain, never the full address of a page.
header('Referrer-Policy: same-origin');

// The site never needs the camera, microphone or location of the visitor.
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

session_name(SESSION_NAME);

session_set_cookie_params([
    'lifetime' => 0,          // delete the cookie when the browser closes
    'path' => '/',
    'secure' => APP_HTTPS,    // true = only send the cookie over HTTPS
    'httponly' => true,       // JavaScript cannot read the session cookie
    'samesite' => 'Lax',      // other websites cannot send it with their forms
]);

// Refuse session IDs that this server did not create itself.
ini_set('session.use_strict_mode', '1');

session_start();

// Log out users who have been inactive for too long (see helpers/auth.php).
enforceSessionTimeout();

// ---------------------------------------------------------------------
// 6. Friendly page for unexpected errors
// ---------------------------------------------------------------------
// If code throws an exception that nothing catches, show our error page
// instead of raw PHP output. The full details go into the log file.
set_exception_handler(function (Throwable $exception) {
    $location = $exception->getFile() . ', line ' . $exception->getLine();

    error_log($exception->getMessage() . ' (' . $location . ')');

    showErrorPage(
        500,
        'Something went wrong',
        'The page could not be loaded. Please try again, and contact the pharmacy if the problem continues.',
        $exception->getMessage() . ' (' . $location . ')'
    );
});

// ---------------------------------------------------------------------
// 7. Database connection
// ---------------------------------------------------------------------
// $pdo is the connection that pages and models use to talk to MySQL.
try {
    $pdo = connectDatabase();
} catch (PDOException $exception) {
    error_log('Database connection failed: ' . $exception->getMessage());

    showErrorPage(
        503,
        'Service temporarily unavailable',
        'We cannot reach the database right now. Please try again in a few minutes.',
        $exception->getMessage()
    );
}

// ---------------------------------------------------------------------
// 8. Account check for logged-in users
// ---------------------------------------------------------------------
// If the admin deactivated this account, log the user out now (FR-58).
enforceActiveAccount(new User($pdo));