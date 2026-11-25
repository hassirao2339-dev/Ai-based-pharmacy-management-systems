<?php

/**
 * auth.php
 *
 * Helper functions for logging in, logging out, and checking
 * WHO the visitor is and WHICH pages they may open.
 *
 * After a successful login, the session contains:
 *
 *   $_SESSION['user_id']        the user's ID from the users table
 *   $_SESSION['user_name']      shown in the navigation bar
 *   $_SESSION['user_role']      'customer' or 'admin'
 *   $_SESSION['last_activity']  time of the last request, for the idle timeout
 */

// ---------------------------------------------------------------------
// Who is visiting?
// ---------------------------------------------------------------------

/**
 * Check whether a user is logged in.
 *
 * @return bool True when the session contains a user ID and a role.
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id'], $_SESSION['user_role']);
}

/**
 * Get basic details of the logged-in user from the session.
 *
 * @return array|null ['id', 'name', 'role'], or null for a guest.
 */
function currentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }

    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'] ?? '',
        'role' => $_SESSION['user_role'],
    ];
}

/**
 * The dashboard page for a role (FR-04).
 *
 * @param string $role 'customer' or 'admin'.
 * @return string Path of the dashboard page.
 */
function dashboardPath(string $role): string
{
    if ($role === 'admin') {
        return 'admin/dashboard.php';
    }

    return 'customer/dashboard.php';
}

// ---------------------------------------------------------------------
// Logging in and out
// ---------------------------------------------------------------------

/**
 * Log a user in by saving their details in the session (FR-03).
 * Call this ONLY after the password has been checked.
 *
 * @param array $user The user's row from the users table.
 * @return void
 */
function loginUser(array $user): void
{
    // A new session ID at login stops "session fixation" attacks.
    session_regenerate_id(true);

    $_SESSION['user_id'] = (int) $user['user_id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['last_activity'] = time();

    // Start the logged-in session with a fresh CSRF token.
    unset($_SESSION['csrf_token']);
}

/**
 * Log the current user out (FR-06).
 *
 * The session is emptied and the old session ID is deleted, so it stops working
 * immediately. The browser gets a new, empty session, which can still carry a
 * "You have been logged out" message.
 *
 * @return void
 */
function logoutUser(): void
{
    $_SESSION = [];
    session_regenerate_id(true);
}

// ---------------------------------------------------------------------
// Protecting pages
// ---------------------------------------------------------------------

/**
 * Stop guests from opening a page. Guests are sent to the login page (FR-05).
 *
 * @return void
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        setFlash('warning', 'Please log in to continue.');
        redirect('auth/login.php');
    }
}

/**
 * Allow only users with one specific role to open a page (RBAC).
 *
 * Put this near the top of a controller:
 *     requireRole('admin');
 *
 * Guest         -> redirected to the login page
 * Wrong role    -> "Access denied" page (HTTP 403)
 * Correct role  -> the page continues normally
 *
 * @param string $role The role the page needs: 'customer' or 'admin'.
 * @return void
 */
function requireRole(string $role): void
{
    requireLogin();

    if ($_SESSION['user_role'] !== $role) {
        showErrorPage(403, 'Access denied', 'Your account does not have permission to open this page.');
    }
}

/**
 * Send logged-in users away from the login and registration pages,
 * straight to their own dashboard.
 *
 * @return void
 */
function redirectIfLoggedIn(): void
{
    if (isLoggedIn()) {
        redirect(dashboardPath($_SESSION['user_role']));
    }
}

// ---------------------------------------------------------------------
// Checks that bootstrap.php runs on every request
// ---------------------------------------------------------------------

/**
 * Log out a user who has been inactive for longer than SESSION_TIMEOUT_MINUTES.
 *
 * @return void
 */
function enforceSessionTimeout(): void
{
    if (!isLoggedIn()) {
        return;
    }

    $timeoutInSeconds = SESSION_TIMEOUT_MINUTES * 60;
    $lastActivity = $_SESSION['last_activity'] ?? time();

    if (time() - $lastActivity > $timeoutInSeconds) {
        logoutUser();

        $minuteWord = (SESSION_TIMEOUT_MINUTES === 1) ? 'minute' : 'minutes';
        setFlash('warning', 'You were logged out after ' . SESSION_TIMEOUT_MINUTES . ' ' . $minuteWord . ' of inactivity.');

        return;
    }

    // The user is active, so restart the countdown.
    $_SESSION['last_activity'] = time();
}

/**
 * Log out a user whose account was deleted or deactivated by the admin (FR-58),
 * and keep the name and role in the session up to date.
 *
 * @param User $userModel The User model, used to read the latest account details.
 * @return void
 */
function enforceActiveAccount(User $userModel): void
{
    if (!isLoggedIn()) {
        return;
    }

    $user = $userModel->findById((int) $_SESSION['user_id']);

    if ($user === null || (int) $user['is_active'] !== 1) {
        logoutUser();
        setFlash('warning', 'Your session has ended. Please log in again.');

        return;
    }

    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_role'] = $user['role'];
}

/**
 * Record an administrator action in the audit log (FR-60).
 *
 * A short wrapper so controllers do not have to build the model themselves:
 *     logAdminAction($pdo, 'order_status_update', 'Order #12 set to confirmed', 'orders', 12);
 *
 * @param PDO         $pdo           The database connection.
 * @param string      $actionType    A short code for the kind of action.
 * @param string      $description   What happened, in plain words.
 * @param string|null $affectedTable Which table it touched, if any.
 * @param int|null    $affectedId    Which row it touched, if any.
 * @return void
 */
function logAdminAction(
    PDO $pdo,
    string $actionType,
    string $description,
    ?string $affectedTable = null,
    ?int $affectedId = null
): void {
    if (!isLoggedIn()) {
        return;
    }

    $auditLog = new AuditLog($pdo);
    $auditLog->record((int) $_SESSION['user_id'], $actionType, $description, $affectedTable, $affectedId);
}