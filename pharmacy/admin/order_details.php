<?php

/**
 * admin/order_details.php - manage one order (controller)
 *
 * FR-59: "The Administrator shall be able to update order status through the
 *        defined lifecycle and cancel any order with a mandatory reason."
 * FR-60: every status change is written to the audit log.
 * FR-33: cancelling puts the stock back into the same batches it came from.
 *
 * An order only moves forward (pending -> confirmed -> dispatched ->
 * delivered). The allowed next steps come from the Order model, so a status
 * cannot be skipped or reversed by editing the form.
 */

require_once __DIR__ . '/../app/bootstrap.php';

requireRole('admin');

$inventoryModel = new Inventory($pdo);
$orderModel = new Order($pdo, $inventoryModel);

$orderId = 0;

if (isset($_GET['id'])) {
    $orderId = (int) $_GET['id'];
}

if (isPostRequest()) {
    $orderId = (int) postValue('order_id');
}

$order = $orderModel->findForAdmin($orderId);

if ($order === null) {
    showErrorPage(404, 'Order not found', 'That order does not exist.');
}

if (isPostRequest()) {
    verifyCsrf();

    $newStatus = postValue('new_status');
    $allowedStatuses = $orderModel->getNextStatuses($order['status']);

    // Only a step the model allows from the CURRENT status is accepted.
    if (!in_array($newStatus, $allowedStatuses, true)) {
        setFlash('danger', 'That status change is not allowed for this order.');
        redirect('admin/order_details.php?id=' . $orderId);
    }

    $orderNumber = '#' . str_pad((string) $orderId, 4, '0', STR_PAD_LEFT);

    // ---------------------------------------------------------------------
    // Cancelling (FR-59: the reason is mandatory)
    // ---------------------------------------------------------------------
    if ($newStatus === 'cancelled') {
        $reason = trim(postValue('cancel_reason'));

        if ($reason === '') {
            setFlash('warning', 'Please give a reason before cancelling an order.');
            redirect('admin/order_details.php?id=' . $orderId);
        }

        if (mb_strlen($reason) > 500) {
            $reason = mb_substr($reason, 0, 500);
        }

        try {
            // cancel() also returns the stock to its batches (FR-33).
            $orderModel->cancel($orderId, $reason);

            logAdminAction(
                $pdo,
                'order_cancel',
                'Cancelled order ' . $orderNumber . ' for ' . $order['customer_name'] . '. Reason: ' . $reason,
                'orders',
                $orderId
            );

            setFlash('success', 'Order ' . $orderNumber . ' has been cancelled and the stock returned.');
        } catch (Throwable $exception) {
            error_log('Admin order cancel failed for order ' . $orderId . ': ' . $exception->getMessage());

            setFlash('danger', 'We could not cancel that order. Nothing has been changed.');
        }

        redirect('admin/order_details.php?id=' . $orderId);
    }

    // ---------------------------------------------------------------------
    // Moving forward: confirmed, dispatched or delivered
    // ---------------------------------------------------------------------
    $orderModel->updateStatus($orderId, $newStatus);

    logAdminAction(
        $pdo,
        'order_status_update',
        'Order ' . $orderNumber . ' set to ' . $newStatus,
        'orders',
        $orderId
    );

    setFlash('success', 'Order ' . $orderNumber . ' is now ' . $newStatus . '.');
    redirect('admin/order_details.php?id=' . $orderId);
}

// Opening an order counts as seeing it (FR-29).
$orderModel->markAsSeen($orderId);

render('admin/order_details', [
    'pageTitle' => 'Order #' . str_pad((string) $orderId, 4, '0', STR_PAD_LEFT),
    'order' => $order,
    'items' => $orderModel->getItems($orderId),
    'allocations' => $orderModel->getBatchAllocations($orderId),
    'nextStatuses' => $orderModel->getNextStatuses($order['status']),
], 'admin');