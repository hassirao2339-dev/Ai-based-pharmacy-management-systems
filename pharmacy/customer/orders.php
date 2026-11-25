<?php

/**
 * customer/orders.php - order history (controller)
 *
 * FR-30: show every order of this customer with its ID, date, item count,
 *        total amount and current status.
 *
 * Only this customer's own orders are loaded, so nobody can see anyone else's.
 */

require_once __DIR__ . '/../app/bootstrap.php';

requireRole('customer');

$inventoryModel = new Inventory($pdo);
$orderModel = new Order($pdo, $inventoryModel);

$customerId = (int) $_SESSION['user_id'];

render('customer/orders', [
    'pageTitle' => 'My orders',
    'orders' => $orderModel->getCustomerOrders($customerId),
]);