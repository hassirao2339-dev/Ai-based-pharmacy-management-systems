<?php

/**
 * auth/reset_password.php - choose a new password (controller)
 *
 * FR-08: this is the page the reset link opens.
 *
 * The token arrives in the link (?token=...). The database only stores its
 * SHA-256 hash, so the token from the link is hashed and compared with that.
 * findByValidResetToken() also checks that the link has not expired and that
 * the account is still active.
 */

require_once __DIR__ . '/../app/bootstrap.php';

redirectIfLoggedIn();

$userModel = new User($pdo);

// The token comes from the link on a GET request, and from a hidden field when
// the form is submitted, so it survives a validation error.
$token = '';

if (isPostRequest()) {
    $token = postValue('token');
} elseif (isset($_GET['token']) && is_string($_GET['token'])) {
    $token = $_GET['token'];
}

$user = null;

if ($token !== '') {
    $user = $userModel->findByValidResetToken(hash('sha256', $token));
}

$linkIsValid = ($user !== null);
$errors = [];

if ($linkIsValid && isPostRequest()) {
    verifyCsrf();

    $password = postValue('password');
    $confirmPassword = postValue('confirm_password');

    $errors = validateNewPassword($password, $confirmPassword);

    if (count($errors) === 0) {
        // Saves the new password and clears the reset token, so the link cannot be used twice.
        $userModel->updatePassword((int) $user['user_id'], $password);

        setFlash('success', 'Your password has been changed. Please log in with your new password.');
        redirect('auth/login.php');
    }
}

render('auth/reset_password', [
    'pageTitle' => 'Choose a new password',
    'linkIsValid' => $linkIsValid,
    'token' => $token,
    'errors' => $errors,
]);