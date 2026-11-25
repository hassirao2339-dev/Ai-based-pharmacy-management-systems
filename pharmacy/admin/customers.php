<?php

/**
 * admin/customers.php - customer accounts (controller)
 *
 * FR-58: "The Administrator shall be able to view all registered customers and
 *        activate or deactivate accounts."
 *
 * Administrator accounts are not listed here. There is only one admin in this
 * version, and an admin must never be able to lock themselves out.
 */

require_once __DIR__ . '/../app/bootstrap.php';

requireRole('admin');

$userModel = new User($pdo);

render('admin/customers', [
    'pageTitle' => 'Customers',
    'customers' => $userModel->getAllCustomers(),
], 'admin');