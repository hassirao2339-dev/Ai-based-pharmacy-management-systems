<?php

/**
 * customer/order_details.php - one order in full (controller)
 *
 * FR-30: show the items, quantities, total amount, delivery details and the
 *        current status of one order.
 *
 * findForCustomer() only returns the order when it belongs to the logged-in
 * customer, so changing the id in the address shows a "not found" page instead
 * of someone else's order.
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

render('customer/order_details', [
    'pageTitle' => 'Order details',
    'order' => $order,
    'items' => $orderModel->getItems($orderId),
    'isEditable' => $orderModel->isEditableByCustomer($order),
]);