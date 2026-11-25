<?php

/**
 * customer/cart.php - the shopping cart (controller)
 *
 * FR-23: show the cart and let the customer adjust quantities.
 * FR-24: remove one item or clear the cart.
 * FR-26: warn about anything that has gone out of stock since it was added.
 *
 * The forms on this page all post to customer/cart_action.php.
 */

require_once __DIR__ . '/../app/bootstrap.php';

requireRole('customer');

$cartModel = new Cart($pdo);
$inventoryModel = new Inventory($pdo);

$customerId = (int) $_SESSION['user_id'];

$items = $cartModel->getItems($customerId);

// Add the stock that can really be sold to each line, so the view can show a
// warning and cap the quantity box. The summary column is not trusted here.
foreach ($items as $index => $item) {
    $items[$index]['available'] = $inventoryModel->getAvailableStock((int) $item['medicine_id']);
}

// A line has a problem when the medicine was hidden by the admin, or when the
// cart asks for more than is available.
$hasProblems = false;

foreach ($items as $item) {
    if ((int) $item['is_active'] !== 1 || $item['available'] < (int) $item['quantity']) {
        $hasProblems = true;
        break;
    }
}

render('customer/cart', [
    'pageTitle' => 'My cart',
    'items' => $items,
    'total' => $cartModel->calculateTotal($items),
    'hasProblems' => $hasProblems,
]);