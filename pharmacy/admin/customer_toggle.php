<?php

/**
 * admin/customer_toggle.php - activate or deactivate a customer (controller)
 *
 * FR-58: activate or deactivate customer accounts.
 * FR-60: the change is written to the audit log.
 *
 * A deactivated customer cannot log in, and if they are already logged in,
 * enforceActiveAccount() in bootstrap.php logs them out on their next click.
 */

require_once __DIR__ . '/../app/bootstrap.php';

requireRole('admin');

if (!isPostRequest()) {
    redirect('admin/customers.php');
}

verifyCsrf();

$userModel = new User($pdo);

$customerId = (int) postValue('customer_id');
$customer = $userModel->findById($customerId);

if ($customer === null) {
    setFlash('danger', 'That customer account no longer exists.');
    redirect('admin/customers.php');
}

// Only customer accounts may be switched off here, never an administrator.
if ($customer['role'] !== 'customer') {
    setFlash('danger', 'Only customer accounts can be changed here.');
    redirect('admin/customers.php');
}

$shouldBeActive = (postValue('is_active') === '1');

$userModel->setActive($customerId, $shouldBeActive);

if ($shouldBeActive) {
    logAdminAction(
        $pdo,
        'customer_activate',
        'Activated the account of ' . $customer['name'] . ' (' . $customer['email'] . ')',
        'users',
        $customerId
    );

    setFlash('success', $customer['name'] . ' can log in again.');
} else {
    logAdminAction(
        $pdo,
        'customer_deactivate',
        'Deactivated the account of ' . $customer['name'] . ' (' . $customer['email'] . ')',
        'users',
        $customerId
    );

    setFlash('success', $customer['name'] . ' can no longer log in. Their orders are kept.');
}

redirect('admin/customers.php');