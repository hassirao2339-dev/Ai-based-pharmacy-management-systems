<?php

/**
 * customer/checkout.php - place the order (controller)
 *
 * FR-25: enter a delivery address, review the order and place it with Cash on
 *        Delivery as the only payment method.
 * FR-26: validate stock for every cart item at the moment the order is sent.
 * FR-27: save the order as "pending", deduct stock via FIFO, clear the cart.
 *
 * The real stock check happens inside the transaction in Order::createFromCart().
 * The check here is an early warning, so the customer is not surprised.
 */

require_once __DIR__ . '/../app/bootstrap.php';

requireRole('customer');

$cartModel = new Cart($pdo);
$userModel = new User($pdo);
$inventoryModel = new Inventory($pdo);
$orderModel = new Order($pdo, $inventoryModel);

$customerId = (int) $_SESSION['user_id'];

$items = $cartModel->getItems($customerId);

// Nothing to check out.
if (count($items) === 0) {
    setFlash('info', 'Your cart is empty. Add some medicines first.');
    redirect('customer/cart.php');
}

$stockProblems = $orderModel->findStockProblems($items);

// Something in the cart cannot be ordered: send the customer back to fix it.
if (count($stockProblems) > 0) {
    setFlash('warning', 'Some medicines are no longer available in the quantity you asked for. Please update your cart.');
    redirect('customer/cart.php');
}

$user = $userModel->findById($customerId);

// The address box starts with the address saved in the profile (FR-61).
$deliveryAddress = $user['address'] ?? '';
$phone = $user['phone'];

$errors = [];

if (isPostRequest()) {
    verifyCsrf();

    $deliveryAddress = trim(postValue('delivery_address'));
    $phone = trim(postValue('phone'));

    // ---------- Check the delivery details ----------
    if ($deliveryAddress === '') {
        $errors['delivery_address'] = 'Please enter the address where we should deliver.';
    } elseif (mb_strlen($deliveryAddress) < 10) {
        $errors['delivery_address'] = 'Please give a full address, so the rider can find you.';
    } elseif (mb_strlen($deliveryAddress) > 500) {
        $errors['delivery_address'] = 'Your address must be 500 characters or fewer.';
    }

    $phoneDigitCount = strlen(preg_replace('/[^0-9]/', '', $phone));

    if ($phone === '') {
        $errors['phone'] = 'Please enter a phone number for the rider.';
    } elseif ($phoneDigitCount < 10 || $phoneDigitCount > 15 || strlen($phone) > 20) {
        $errors['phone'] = 'Please enter a valid phone number, for example 0300 1234567.';
    }

    if (count($errors) === 0) {
        try {
            // Everything inside here is one transaction: order saved, stock
            // deducted by FIFO and cart emptied, or nothing at all.
            $orderId = $orderModel->createFromCart($customerId, $items, $deliveryAddress);

            // Keep the profile up to date with the details just used (FR-61).
            $userModel->updateProfile($customerId, $user['name'], $phone, $deliveryAddress);

            redirect('customer/order_confirmation.php?id=' . $orderId);
        } catch (Throwable $exception) {
            // The transaction has already been rolled back, so nothing was saved.
            error_log('Order failed for customer ' . $customerId . ': ' . $exception->getMessage());

            setFlash('danger', 'We could not place your order, because some stock ran out while you were checking out. Your cart has not changed. Please try again.');
            redirect('customer/cart.php');
        }
    }
}

render('customer/checkout', [
    'pageTitle' => 'Checkout',
    'items' => $items,
    'total' => $cartModel->calculateTotal($items),
    'deliveryAddress' => $deliveryAddress,
    'phone' => $phone,
    'customerName' => $user['name'],
    'errors' => $errors,
]);