<?php

/**
 * customer/cart_action.php - change the cart (controller)
 *
 * FR-23: add medicines and adjust quantities.
 * FR-24: remove one item or clear the whole cart.
 *
 * This page has no HTML. It changes the cart, then sends the customer back
 * where they came from with a flash message. Every request must be a POST
 * with a valid CSRF token.
 *
 * Actions: add, update, remove, clear
 */

require_once __DIR__ . '/../app/bootstrap.php';

requireRole('customer');

// Only POST may change data. Opening this page as a link does nothing.
if (!isPostRequest()) {
    redirect('customer/cart.php');
}

verifyCsrf();

$cartModel = new Cart($pdo);
$medicineModel = new Medicine($pdo);
$inventoryModel = new Inventory($pdo);

$customerId = (int) $_SESSION['user_id'];
$action = postValue('action');

// Where to send the customer afterwards. The form says where it came from,
// but only a path inside our own project is allowed (see safeReturnPath()).
$returnPath = safeReturnPath(postValue('return_to'), 'customer/cart.php');

// ---------------------------------------------------------------------
// Clear the whole cart (FR-24)
// ---------------------------------------------------------------------
if ($action === 'clear') {
    $cartModel->clear($customerId);

    setFlash('success', 'Your cart is now empty.');
    redirect($returnPath);
}

// ---------------------------------------------------------------------
// Every other action works on one medicine
// ---------------------------------------------------------------------
$medicineId = (int) postValue('medicine_id');
$medicine = $medicineModel->findActiveById($medicineId);

if ($medicine === null) {
    setFlash('danger', 'That medicine is no longer in our catalogue.');
    redirect($returnPath);
}

// ---------------------------------------------------------------------
// Remove one medicine (FR-24)
// ---------------------------------------------------------------------
if ($action === 'remove') {
    $cartModel->removeItem($customerId, $medicineId);

    setFlash('success', $medicine['name'] . ' was removed from your cart.');
    redirect($returnPath);
}

// ---------------------------------------------------------------------
// Add or update: both need a quantity and a stock check
// ---------------------------------------------------------------------
$quantity = (int) postValue('quantity');

// The details page has a quantity box; the card's Add button has none.
if ($quantity < 1) {
    $quantity = 1;
}

// Never trust the stock summary alone: count the usable batches (FR-22, FR-26).
$availableStock = $inventoryModel->getAvailableStock($medicineId);

if ($availableStock === 0) {
    setFlash('warning', $medicine['name'] . ' is out of stock right now.');
    redirect($returnPath);
}

if ($action === 'add') {
    // What is already in the cart counts towards the limit.
    $alreadyInCart = $cartModel->getQuantity($customerId, $medicineId);
    $roomLeft = $availableStock - $alreadyInCart;

    if ($roomLeft <= 0) {
        setFlash('warning', 'Your cart already has all ' . $availableStock . ' available units of ' . $medicine['name'] . '.');
        redirect($returnPath);
    }

    if ($quantity > $roomLeft) {
        $quantity = $roomLeft;
        setFlash('warning', 'Only ' . $roomLeft . ' more units of ' . $medicine['name'] . ' are available, so that is what we added.');
    } else {
        setFlash('success', $medicine['name'] . ' was added to your cart.');
    }

    $cartModel->addItem($customerId, $medicineId, $quantity);
    redirect($returnPath);
}

if ($action === 'update') {
    if ($quantity > $availableStock) {
        $quantity = $availableStock;
        setFlash('warning', 'Only ' . $availableStock . ' units of ' . $medicine['name'] . ' are available, so the quantity was reduced.');
    } else {
        setFlash('success', 'Your cart has been updated.');
    }

    $cartModel->setQuantity($customerId, $medicineId, $quantity);
    redirect($returnPath);
}

// An action we do not know: change nothing.
redirect($returnPath);