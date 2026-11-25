<?php

/**
 * auth/login.php - log in (controller)
 *
 * FR-03: log in with email and password, then create a role-bound session.
 * FR-04: send the user to the dashboard for their role.
 * FR-07: lock the account for 15 minutes after 5 wrong passwords in a row.
 */

require_once __DIR__ . '/../app/bootstrap.php';

// Someone who is already logged in goes straight to their dashboard.
redirectIfLoggedIn();

$userModel = new User($pdo);

$email = '';
$errorMessage = '';

if (isPostRequest()) {
    verifyCsrf();

    $email = strtolower(trim(postValue('email')));
    $password = postValue('password');

    // The same message is used for "email not registered" and "wrong password",
    // so nobody can use this form to find out which emails have accounts.
    $wrongDetailsMessage = 'The email address or password is incorrect.';

    if ($email === '' || $password === '') {
        $errorMessage = 'Please enter your email address and password.';
    } else {
        $user = $userModel->findByEmail($email);

        if ($user === null) {
            $errorMessage = $wrongDetailsMessage;
        } elseif ($userModel->isLocked($user)) {
            // Checked BEFORE the password, so a locked account cannot keep guessing.
            $minutesLeft = $userModel->minutesUntilUnlock($user);
            $minuteWord = ($minutesLeft === 1) ? 'minute' : 'minutes';

            $errorMessage = 'This account is locked because of too many wrong passwords. Try again in '
                . $minutesLeft . ' ' . $minuteWord . '.';
        } elseif (!password_verify($password, $user['password_hash'])) {
            // password_verify() hashes the typed password and compares it with the saved hash.
            $accountIsNowLocked = $userModel->recordFailedLogin($user);

            if ($accountIsNowLocked) {
                $errorMessage = 'Too many wrong passwords. For your security, this account is locked for '
                    . LOCKOUT_MINUTES . ' minutes.';
            } else {
                $errorMessage = $wrongDetailsMessage;
            }
        } elseif ((int) $user['is_active'] !== 1) {
            // Checked AFTER the password, so only the real owner learns the account is deactivated.
            $errorMessage = 'This account has been deactivated. Please contact the pharmacy.';
        } else {
            // Everything is correct: clear the wrong-password counter and log in.
            $userModel->resetFailedLogins((int) $user['user_id']);
            loginUser($user);

            setFlash('success', 'Welcome back, ' . $user['name'] . '!');
            redirect(dashboardPath($user['role']));
        }
    }
}

render('auth/login', [
    'pageTitle' => 'Log in',
    'email' => $email,
    'errorMessage' => $errorMessage,
]);