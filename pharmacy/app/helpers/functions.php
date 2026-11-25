<?php

/**
 * functions.php
 *
 * Small helper functions used on many pages.
 */

/**
 * Escape a value before printing it inside HTML.
 *
 * This prevents Cross-Site Scripting (XSS): if someone types <script> into
 * a form, it is displayed as plain text instead of running as code.
 *
 * @param mixed $value The value to print.
 * @return string Text that is safe to place inside HTML.
 */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Build a full link to a page inside the project.
 *
 * Example: url('auth/login.php') returns http://localhost/pharmacy/auth/login.php
 *
 * @param string $path Path relative to the project folder.
 * @return string The full URL.
 */
function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

/**
 * Build a full link to a file inside the assets folder.
 *
 * Example: asset('css/style.css') returns http://localhost/pharmacy/assets/css/style.css
 *
 * @param string $path Path inside the assets folder.
 * @return string The full URL.
 */
function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

/**
 * Send the browser to another page and stop running the current script.
 *
 * @param string $path Path relative to the project folder.
 * @return void
 */
function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

/**
 * Check whether the current request is a form submission (POST).
 *
 * @return bool True when the page was requested with POST.
 */
function isPostRequest(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Check whether a navigation link points to the page currently open.
 * Used to highlight the active link in the navigation bar.
 *
 * @param string $path Path relative to the project folder, for example 'index.php'.
 * @return bool True when the link matches the current page.
 */
function isCurrentPage(string $path): bool
{
    $linkPath = parse_url(url($path), PHP_URL_PATH);

    return $linkPath === $_SERVER['SCRIPT_NAME'];
}

/**
 * Save a one-time message to show on the next page the user sees.
 * Common use: set a message, then redirect.
 *
 * @param string $type    Bootstrap alert type: success, danger, warning or info.
 * @param string $message The message text.
 * @return void
 */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => $message,
    ];
}

/**
 * Get all saved flash messages and remove them, so each is shown only once.
 *
 * @return array List of messages, each with 'type' and 'message'.
 */
function getFlashMessages(): array
{
    $messages = $_SESSION['flash_messages'] ?? [];

    unset($_SESSION['flash_messages']);

    return $messages;
}

/**
 * Show a page: layout header, then the view file, then layout footer.
 *
 * Example: render('public/home', ['pageTitle' => 'Home']);
 * loads app/views/public/home.php between the main header and footer,
 * and inside the view the variable $pageTitle contains 'Home'.
 *
 * View names always come from our own code, never from user input.
 *
 * @param string $view   View file inside app/views, without ".php".
 * @param array  $data   Variables the view needs, as 'name' => value.
 * @param string $layout Layout name: 'main' now, 'admin' from Phase 7.
 * @return void
 */
function render(string $view, array $data = [], string $layout = 'main'): void
{
        // $pdo was created in bootstrap.php, outside any function, so it is not
    // visible inside this one by default. "global" lets the layout files use it,
    // for example to count the cart items shown in the navigation bar.
    global $pdo;
    // Turn each array key into a normal variable: ['pageTitle' => 'Home'] becomes $pageTitle.
    // EXTR_SKIP means it can never overwrite $view, $data or $layout.
    extract($data, EXTR_SKIP);

    require APP_PATH . '/views/layouts/' . $layout . '_header.php';
    require APP_PATH . '/views/' . $view . '.php';
    require APP_PATH . '/views/layouts/' . $layout . '_footer.php';
}

/**
 * Show a friendly error page with the correct HTTP status code, then stop.
 *
 * @param int         $statusCode       HTTP status, for example 403, 404 or 500.
 * @param string      $title            Short title shown to the user.
 * @param string      $message          What happened and what the user can do.
 * @param string|null $technicalDetails Developer details, shown only when APP_DEBUG is true.
 * @return void
 */
function showErrorPage(int $statusCode, string $title, string $message, ?string $technicalDetails = null): void
{
    if (!headers_sent()) {
        http_response_code($statusCode);
    }

    // Visitors must never see file paths or SQL errors in production.
    if (!APP_DEBUG) {
        $technicalDetails = null;
    }

    render('errors/error', [
        'pageTitle' => $title,
        'statusCode' => $statusCode,
        'errorTitle' => $title,
        'errorMessage' => $message,
        'technicalDetails' => $technicalDetails,
    ]);

    exit;
}

/**
 * Format an amount of money in Pakistani rupees.
 *
 * Whole amounts are shown without decimals (Rs 45),
 * other amounts keep two decimals (Rs 45.50).
 *
 * @param float $amount The amount, for example 1120.00
 * @return string Formatted price, for example "Rs 1,120"
 */
function formatPrice(float $amount): string
{
    $decimals = 2;

    if ($amount == floor($amount)) {
        $decimals = 0;
    }

    return 'Rs ' . number_format($amount, $decimals);
}

/**
 * Check a "go back to" path that came from a form.
 *
 * A form tells this project where to send the customer afterwards. Without a
 * check, someone could put another website's address there and use our site to
 * forward people to it (an "open redirect"). Only a plain path inside this
 * project is allowed; anything else falls back to a safe default.
 *
 * @param string $path     The path the form asked for.
 * @param string $fallback Where to go when the path is not acceptable.
 * @return string A path that is safe to use with redirect().
 */
function safeReturnPath(string $path, string $fallback): string
{
    $path = trim($path);

    if ($path === '') {
        return $fallback;
    }

    // Reject anything that starts a new address: http://, //evil.com, /other-site
    if (str_contains($path, '://') || str_starts_with($path, '/') || str_starts_with($path, '\\')) {
        return $fallback;
    }

    // Reject attempts to climb out of the project folder.
    if (str_contains($path, '..')) {
        return $fallback;
    }

    return $path;
}

/**
 * The path of the page being viewed, ready to put in a "return_to" field.
 * Example: "catalogue.php?search=syrup"
 *
 * @return string Path relative to the project folder.
 */
function currentPath(): string
{
    $basePath = parse_url(BASE_URL, PHP_URL_PATH) ?? '';
    $path = ltrim(substr($_SERVER['REQUEST_URI'], strlen($basePath)), '/');

    if ($path === '') {
        return 'index.php';
    }

    return $path;
}

/**
 * The five order statuses and how to show them (FR-32).
 *
 * Keeping the labels and colours in one place means every page shows the same
 * badge for the same status. The CSS classes are defined in style.css.
 *
 * @return array Status name => ['label' => ..., 'class' => ..., 'icon' => ...]
 */
function orderStatuses(): array
{
    return [
        'pending' => ['label' => 'Pending', 'class' => 'status-pending', 'icon' => 'hourglass-split'],
        'confirmed' => ['label' => 'Confirmed', 'class' => 'status-confirmed', 'icon' => 'check-circle'],
        'dispatched' => ['label' => 'Dispatched', 'class' => 'status-dispatched', 'icon' => 'truck'],
        'delivered' => ['label' => 'Delivered', 'class' => 'status-delivered', 'icon' => 'bag-check'],
        'cancelled' => ['label' => 'Cancelled', 'class' => 'status-cancelled', 'icon' => 'x-circle'],
    ];
}

/**
 * The coloured badge for one order status (FR-32).
 *
 * @param string $status The status saved in the orders table.
 * @return string Ready-made HTML for the badge.
 */
function statusBadge(string $status): string
{
    $statuses = orderStatuses();

    // An unknown status still shows something sensible rather than nothing.
    $details = $statuses[$status] ?? ['label' => ucfirst($status), 'class' => 'status-pending', 'icon' => 'question-circle'];

    return '<span class="status ' . e($details['class']) . '">'
        . '<i class="bi bi-' . e($details['icon']) . '" aria-hidden="true"></i> '
        . e($details['label'])
        . '</span>';
}