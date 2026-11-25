<?php

/**
 * customer/prescriptions.php - prescription history (controller)
 *
 * FR-17: the customer can see the prescriptions they uploaded.
 * FR-63: each one shows its date, whether it was read, and which medicines
 *        were added from it.
 */

require_once __DIR__ . '/../app/bootstrap.php';

requireRole('customer');

$prescriptionModel = new Prescription($pdo);

render('customer/prescriptions', [
    'pageTitle' => 'My prescriptions',
    'prescriptions' => $prescriptionModel->getForCustomer((int) $_SESSION['user_id']),
]);