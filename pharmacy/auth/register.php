<?php

/**
 * auth/register.php - customer registration (controller)
 *
 * FR-01: register with full name, email, password (8+ characters) and phone.
 *        The role is always "customer"; nobody can register as an admin here.
 * FR-02: the email address must not already be registered.
 * FR-04: after registering, the customer is logged in and sent to their dashboard.
 */

require_once __DIR__ . '/../app/bootstrap.php';

// Someone who is already logged in does not need this page.
redirectIfLoggedIn();

$userModel = new User($pdo);

// $old keeps what the visitor typed, so the form does not empty itself after an error.
// Passwords are never sent back to the browser.
$old = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'address' => '',
];

$errors = [];

if (isPostRequest()) {
    verifyCsrf();

    $input = getAccountInput();

    $old['name'] = $input['name'];
    $old['email'] = $input['email'];
    $old['phone'] = $input['phone'];
    $old['address'] = $input['address'];

    $errors = validateAccountInput($input, $userModel);

    if (count($errors) === 0) {
        // The address is optional. An empty address is saved as NULL.
        $address = null;

        if ($input['address'] !== '') {
            $address = $input['address'];
        }

        // No role is passed here, so the model uses its default role: "customer".
        $newUserId = $userModel->create(
            $input['name'],
            $input['email'],
            $input['password'],
            $input['phone'],
            $address
        );

        $newUser = $userModel->findById($newUserId);

        loginUser($newUser);

        setFlash('success', 'Welcome to ' . APP_NAME . ', ' . $newUser['name'] . '! Your account is ready.');
        redirect('customer/dashboard.php');
    }
}

render('auth/register', [
    'pageTitle' => 'Create account',
    'old' => $old,
    'errors' => $errors,
]);