<?php

/**
 * customer/order_edit.php - edit or cancel a pending order (controller)
 *
 * FR-31: the customer may change quantities or remove items from an order
 *        whose status is "pending". Orders in any other status are read-only.
 * FR-33: the customer may cancel a pending order, and the stock is put back.
 *
 * Both actions work through the Order model, which does the whole change
 * inside a transaction so stock and order can never disagree.
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

// When the form is posted, the order id comes from the form instead.
if (isPostRequest()) {
    $orderId = (int) postValue('order_id');
}

$order = $orderModel->findForCustomer($orderId, $customerId);

if ($order === null) {
    showErrorPage(
        404,
        'Order not found',
        'We could not find that order on your account. Please check your order history.'
    );
}

// FR-31: anything that is not pending can no longer be changed by the customer.
if (!$orderModel->isEditableByCustomer($order)) {
    setFlash('info', 'This order has already been ' . $order['status'] . ', so it can no longer be changed.');
    redirect('customer/order_details.php?id=' . $orderId);
}

if (isPostRequest()) {
    verifyCsrf();

    $action = postValue('action');

    // ---------------------------------------------------------------------
    // Cancel the whole order (FR-33)
    // ---------------------------------------------------------------------
    if ($action === 'cancel') {
        try {
            $orderModel->cancel($orderId, 'Cancelled by the customer');

            setFlash('success', 'Your order has been cancelled and the medicines are back in our stock.');
            redirect('customer/order_details.php?id=' . $orderId);
        } catch (Throwable $exception) {
            error_log('Order cancel failed for order ' . $orderId . ': ' . $exception->getMessage());

            setFlash('danger', 'We could not cancel your order. Please try again.');
            redirect('customer/order_edit.php?id=' . $orderId);
        }
    }

    // ---------------------------------------------------------------------
    // Change the quantity of one line, or remove it (FR-31)
    // ---------------------------------------------------------------------
    if ($action === 'update_item' || $action === 'remove_item') {
        $itemId = (int) postValue('item_id');

        // "remove" is the same as setting the quantity to zero.
        $newQuantity = 0;

        if ($action === 'update_item') {
            $newQuantity = (int) postValue('quantity');

            if ($newQuantity < 1) {
                $newQuantity = 1;
            }
        }

        try {
            $orderModel->updateItemQuantity($orderId, $itemId, $newQuantity);

            // Removing the last item leaves an empty order, so it is cancelled.
            if ($orderModel->countItems($orderId) === 0) {
                $orderModel->cancel($orderId, 'All items were removed by the customer');

                setFlash('info', 'You removed the last item, so the order has been cancelled.');
                redirect('customer/order_details.php?id=' . $orderId);
            }

            if ($action === 'remove_item') {
                setFlash('success', 'The item was removed from your order.');
            } else {
                setFlash('success', 'Your order has been updated.');
            }
        } catch (Throwable $exception) {
            error_log('Order edit failed for order ' . $orderId . ': ' . $exception->getMessage());

            setFlash('warning', 'We could not change that item, because there is not enough stock for the new quantity. Your order has not changed.');
        }

        redirect('customer/order_edit.php?id=' . $orderId);
    }

    redirect('customer/order_edit.php?id=' . $orderId);
}

// ---------------------------------------------------------------------
// Show the page
// ---------------------------------------------------------------------
$items = $orderModel->getItems($orderId);

// How many more units of each medicine could be added: what is on the shelf
// now, plus what this order is already holding.
foreach ($items as $index => $item) {
    $onShelf = $inventoryModel->getAvailableStock((int) $item['medicine_id']);
    $items[$index]['max_quantity'] = $onShelf + (int) $item['quantity'];
}

render('customer/order_edit', [
    'pageTitle' => 'Edit order',
    'order' => $order,
    'items' => $items,
]);