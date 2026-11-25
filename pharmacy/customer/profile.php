<?php

/**
 * customer/profile.php - profile settings (controller)
 *
 * FR-61: update full name, phone number and delivery address.
 * FR-62: change the password after confirming the current one.
 *
 * One page, two separate forms. The hidden "form_name" field tells this
 * controller which form was submitted.
 */

require_once __DIR__ . '/../app/bootstrap.php';

requireRole('customer');

$userModel = new User($pdo);

$sessionUser = currentUser();

// The latest details come from the database, not from the session, so the page
// always shows what is really saved.
$user = $userModel->findById((int) $sessionUser['id']);

$profileErrors = [];
$passwordErrors = [];

// What the profile form should show. After a failed save it shows what was typed.
$profile = [
    'name' => $user['name'],
    'phone' => $user['phone'],
    'address' => $user['address'] ?? '',
];

if (isPostRequest()) {
    verifyCsrf();

    $formName = postValue('form_name');

    // ---------------------------------------------------------------------
    // Form 1: personal details (FR-61)
    // ---------------------------------------------------------------------
    if ($formName === 'profile') {
        $input = getProfileInput();
        $profile = $input;

        $profileErrors = validateProfileInput($input);

        if (count($profileErrors) === 0) {
            // The address is optional. An empty address is saved as NULL.
            $address = null;

            if ($input['address'] !== '') {
                $address = $input['address'];
            }

            $userModel->updateProfile((int) $user['user_id'], $input['name'], $input['phone'], $address);

            // Keep the navigation bar in step with the new name.
            $_SESSION['user_name'] = $input['name'];

            setFlash('success', 'Your details have been saved.');
            redirect('customer/profile.php');
        }
    }

    // ---------------------------------------------------------------------
    // Form 2: change password (FR-62)
    // ---------------------------------------------------------------------
    if ($formName === 'password') {
        $currentPassword = postValue('current_password');
        $newPassword = postValue('password');
        $confirmPassword = postValue('confirm_password');

        // FR-62: the current password must be confirmed first.
        if (!$userModel->passwordMatches($user, $currentPassword)) {
            $passwordErrors['current_password'] = 'Your current password is not correct.';
        }

        $passwordErrors = array_merge($passwordErrors, validateNewPassword($newPassword, $confirmPassword));

        // Changing nothing is almost always a mistake, so it is refused.
        if (!isset($passwordErrors['password']) && $newPassword === $currentPassword) {
            $passwordErrors['password'] = 'Please choose a password you have not used before.';
        }

        if (count($passwordErrors) === 0) {
            $userModel->updatePassword((int) $user['user_id'], $newPassword);

            // A new session ID after a password change, in case someone else
            // was using the old session.
            session_regenerate_id(true);

            setFlash('success', 'Your password has been changed.');
            redirect('customer/profile.php');
        }
    }
}

render('customer/profile', [
    'pageTitle' => 'My profile',
    'user' => $user,
    'profile' => $profile,
    'profileErrors' => $profileErrors,
    'passwordErrors' => $passwordErrors,
]);