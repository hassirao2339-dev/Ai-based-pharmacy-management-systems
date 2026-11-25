<?php

/**
 * customer/dashboard.php - customer dashboard (controller)
 *
 * Quick links, the customer's most recent orders (FR-30)
 * and most recent prescriptions (FR-17).
 */

require_once __DIR__ . '/../app/bootstrap.php';

requireRole('customer');

$inventoryModel = new Inventory($pdo);
$orderModel = new Order($pdo, $inventoryModel);
$cartModel = new Cart($pdo);
$prescriptionModel = new Prescription($pdo);

$customerId = (int) $_SESSION['user_id'];

render('customer/dashboard', [
    'pageTitle' => 'My dashboard',
    'user' => currentUser(),
    'recentOrders' => $orderModel->getRecentOrders($customerId, 3),
    'recentPrescriptions' => $prescriptionModel->getForCustomer($customerId, 3),
    'cartCount' => $cartModel->countItems($customerId),
]);