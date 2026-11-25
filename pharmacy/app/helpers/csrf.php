<?php

/**
 * csrf.php
 *
 * Protection against CSRF (Cross-Site Request Forgery).
 *
 * The attack: another website secretly submits a form to OUR site while the
 * victim is logged in, for example to cancel their order.
 *
 * The defence: every form on our site includes a random secret token that is
 * also stored in the session. The other website cannot know this token, so
 * its fake form is rejected.
 */

/**
 * Get the CSRF token for this session, creating it the first time.
 *
 * @return string A 64-character random token.
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        // 32 random bytes shown as 64 hexadecimal characters.
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Create the hidden form field that carries the token.
 *
 * Print this inside every form that changes data, straight after the
 * opening <form method="post"> tag, using: echo csrfField();
 *
 * @return string HTML for a hidden input.
 */
function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

/**
 * Check the token sent with a POST request. Stops with an error page when it is wrong.
 *
 * Normal forms send the token as a hidden field. jQuery AJAX requests
 * (from Phase 4) send it in the X-CSRF-Token header, set up in main.js.
 *
 * @return void
 */
function verifyCsrf(): void
{
    $submittedToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

    // hash_equals() compares safely, without leaking timing clues to attackers.
    if (!is_string($submittedToken) || !hash_equals(csrfToken(), $submittedToken)) {
        showErrorPage(
            403,
            'Security check failed',
            'This form was not accepted because its security token is missing or out of date. Go back, refresh the page and try again.'
        );
    }
}