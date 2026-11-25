<?php

/**
 * auth/logout.php - log out (controller)
 *
 * FR-06: log the user out and invalidate the session immediately.
 *
 * This page has no HTML. It only accepts the POST form from the navbar,
 * which carries a CSRF token. Opening it as a normal link (GET) does nothing,
 * so other websites cannot log people out with a hidden image or link.
 */

require_once __DIR__ . '/../app/bootstrap.php';

if (!isPostRequest()) {
    redirect('index.php');
}

verifyCsrf();

logoutUser();

setFlash('success', 'You have been logged out.');
redirect('auth/login.php');