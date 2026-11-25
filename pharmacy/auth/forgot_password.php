<?php

/**
 * auth/forgot_password.php - ask for a password reset link (controller)
 *
 * FR-08: send a time-limited reset link to the registered email address.
 *
 * How the token works:
 *   1. A random token is created and put inside the link that is emailed.
 *   2. Only hash('sha256', $token) is saved in the database. Someone who could
 *      read the database still could not build a working link.
 *   3. The link stops working after RESET_LINK_MINUTES minutes.
 *
 * While MAIL_ENABLED is false, development mode shows the link on screen
 * so the feature can be tested without an email account.
 */

require_once __DIR__ . '/../app/bootstrap.php';

redirectIfLoggedIn();

$userModel = new User($pdo);

$email = '';
$requestWasSent = false;
$errorMessage = '';

// Only filled in development mode when email sending is switched off.
$developmentResetLink = '';

if (isPostRequest()) {
    verifyCsrf();

    $email = strtolower(trim(postValue('email')));

    if ($email === '') {
        $errorMessage = 'Please enter your email address.';
    } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $errorMessage = 'Please enter a valid email address.';
    } else {
        $user = $userModel->findByEmail($email);

        // The page always shows the same "check your email" message, even when the
        // address is not registered. Otherwise this form could be used to find out
        // which email addresses have accounts.
        $requestWasSent = true;

        if ($user !== null && (int) $user['is_active'] === 1) {
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', time() + (RESET_LINK_MINUTES * 60));

            $userModel->saveResetToken((int) $user['user_id'], hash('sha256', $token), $expiresAt);

            $resetLink = url('auth/reset_password.php?token=' . $token);

            $emailWasSent = sendPasswordResetEmail($user['email'], $user['name'], $resetLink);

            if (!$emailWasSent && APP_ENV === 'development') {
                $developmentResetLink = $resetLink;
            }
        }
    }
}

render('auth/forgot_password', [
    'pageTitle' => 'Forgot password',
    'email' => $email,
    'requestWasSent' => $requestWasSent,
    'errorMessage' => $errorMessage,
    'developmentResetLink' => $developmentResetLink,
]);