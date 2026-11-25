<?php

/**
 * admin/orders.php - all customer orders (controller)
 *
 * FR-59: the administrator can see every order and manage it.
 * FR-29: opening this page clears the "new orders" badge, because the admin
 *        has now seen them.
 */

require_once __DIR__ . '/../app/bootstrap.php';

requireRole('admin');

$inventoryModel = new Inventory($pdo);
$orderModel = new Order($pdo, $inventoryModel);
$statsModel = new AdminStats($pdo);

$orders = $orderModel->getAllForAdmin();

// The admin is looking at the list now, so nothing is "new" any more.
$statsModel->markOrdersAsSeen();

render('admin/orders', [
    'pageTitle' => 'Orders',
    'orders' => $orders,
], 'admin');