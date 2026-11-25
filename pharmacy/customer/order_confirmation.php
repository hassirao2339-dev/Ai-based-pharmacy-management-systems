<?php

/**
 * customer/order_confirmation.php - order placed (controller)
 *
 * FR-28: show a unique Order ID, the itemised order list, the total amount
 *        and a delivery note after a successful order.
 *
 * findForCustomer() only returns the order when it belongs to the logged-in
 * customer, so changing the number in the address cannot show someone else's
 * order.
 */

require_once __DIR__ . '/../app/bootstrap.php';

requireRole('customer');

$inventoryModel = new Inventory($pdo);
$orderModel = new Order($pdo, $inventoryModel);

$customerId = (int) $_SESSION['user_id'];

$orderId = 0;

if (isset($_GET['id'])) {
    $orderId = (int) $_GET['id'];
}

$order = $orderModel->findForCustomer($orderId, $customerId);

if ($order === null) {
    showErrorPage(
        404,
        'Order not found',
        'We could not find that order on your account. Please check your order history.'
    );
}

render('customer/order_confirmation', [
    'pageTitle' => 'Order confirmed',
    'order' => $order,
    'items' => $orderModel->getItems($orderId),
]);