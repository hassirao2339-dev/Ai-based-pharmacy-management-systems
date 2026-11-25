<?php

/**
 * admin/profile.php - administrator profile (controller)
 *
 * FR-61: update full name and phone number.
 * FR-62: change the password after confirming the current one.
 *
 * The same two rules as the customer profile, because both requirements apply
 * to every user. An admin has no delivery address, so that field is not here.
 */

require_once __DIR__ . '/../app/bootstrap.php';

requireRole('admin');

$userModel = new User($pdo);

$sessionUser = currentUser();
$user = $userModel->findById((int) $sessionUser['id']);

$profileErrors = [];
$passwordErrors = [];

$profile = [
    'name' => $user['name'],
    'phone' => $user['phone'],
];

if (isPostRequest()) {
    verifyCsrf();

    $formName = postValue('form_name');

    // ---------------------------------------------------------------------
    // Form 1: personal details (FR-61)
    // ---------------------------------------------------------------------
    if ($formName === 'profile') {
        // getProfileInput() also reads an address; an admin form has none, so
        // the existing value is kept.
        $input = getProfileInput();
        $input['address'] = $user['address'] ?? '';

        $profile = ['name' => $input['name'], 'phone' => $input['phone']];

        $profileErrors = validateProfileInput($input);

        if (count($profileErrors) === 0) {
            $address = ($input['address'] === '') ? null : $input['address'];

            $userModel->updateProfile((int) $user['user_id'], $input['name'], $input['phone'], $address);

            $_SESSION['user_name'] = $input['name'];

            setFlash('success', 'Your details have been saved.');
            redirect('admin/profile.php');
        }
    }

    // ---------------------------------------------------------------------
    // Form 2: change password (FR-62)
    // ---------------------------------------------------------------------
    if ($formName === 'password') {
        $currentPassword = postValue('current_password');
        $newPassword = postValue('password');
        $confirmPassword = postValue('confirm_password');

        if (!$userModel->passwordMatches($user, $currentPassword)) {
            $passwordErrors['current_password'] = 'Your current password is not correct.';
        }

        $passwordErrors = array_merge($passwordErrors, validateNewPassword($newPassword, $confirmPassword));

        if (!isset($passwordErrors['password']) && $newPassword === $currentPassword) {
            $passwordErrors['password'] = 'Please choose a password you have not used before.';
        }

        if (count($passwordErrors) === 0) {
            $userModel->updatePassword((int) $user['user_id'], $newPassword);

            // A new session id, in case someone else was using the old session.
            session_regenerate_id(true);

            logAdminAction($pdo, 'admin_password_change', 'Changed their own password', 'users', (int) $user['user_id']);

            setFlash('success', 'Your password has been changed.');
            redirect('admin/profile.php');
        }
    }
}

render('admin/profile', [
    'pageTitle' => 'My profile',
    'user' => $user,
    'profile' => $profile,
    'profileErrors' => $profileErrors,
    'passwordErrors' => $passwordErrors,
], 'admin');