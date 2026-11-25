<?php

/**
 * customer/prescription_review.php - check the medicines OCR found (controller)
 *
 * FR-13: show the catalogue medicines matched from the prescription.
 * FR-14: show how confident each match is, and list names not found.
 * FR-15: the customer reviews and chooses before anything is added.
 * FR-16: the chosen medicines are added to the cart.
 *
 * Nothing is ever added to the cart automatically. OCR can misread, so the
 * customer always makes the final choice. That is the safety rule for a
 * pharmacy: a machine suggests, a person decides.
 */

require_once __DIR__ . '/../app/bootstrap.php';

requireRole('customer');

$prescriptionModel = new Prescription($pdo);
$inventoryModel = new Inventory($pdo);
$medicineModel = new Medicine($pdo);
$cartModel = new Cart($pdo);

$customerId = (int) $_SESSION['user_id'];

$prescriptionId = 0;

if (isset($_GET['id'])) {
    $prescriptionId = (int) $_GET['id'];
}

if (isPostRequest()) {
    $prescriptionId = (int) postValue('prescription_id');
}

$prescription = $prescriptionModel->findForCustomer($prescriptionId, $customerId);

if ($prescription === null) {
    showErrorPage(404, 'Prescription not found', 'That prescription is not on your account.');
}

// The medicine ids OCR suggested. Only these may be added from this page.
$suggestedIds = [];

foreach ($prescription['suggestions'] as $suggestion) {
    $suggestedIds[] = (int) $suggestion['medicine_id'];
}

// ---------------------------------------------------------------------
// Adding the chosen medicines to the cart (FR-15, FR-16)
// ---------------------------------------------------------------------
if (isPostRequest()) {
    verifyCsrf();

    $selected = $_POST['selected'] ?? [];
    $quantities = $_POST['quantity'] ?? [];

    if (!is_array($selected) || count($selected) === 0) {
        setFlash('warning', 'Please tick at least one medicine to add to your cart.');
        redirect('customer/prescription_review.php?id=' . $prescriptionId);
    }

    $added = [];
    $skipped = [];

    foreach ($selected as $selectedId) {
        $medicineId = (int) $selectedId;

        // Only a medicine that OCR actually suggested for this prescription.
        if (!in_array($medicineId, $suggestedIds, true)) {
            continue;
        }

        $medicine = $medicineModel->findActiveById($medicineId);

        if ($medicine === null) {
            continue;
        }

        $quantity = 1;

        if (is_array($quantities) && isset($quantities[$medicineId])) {
            $quantity = max(1, (int) $quantities[$medicineId]);
        }

        // The same stock rule as the normal Add button: never more than is available.
        $available = $inventoryModel->getAvailableStock($medicineId);
        $roomLeft = $available - $cartModel->getQuantity($customerId, $medicineId);

        if ($roomLeft <= 0) {
            $skipped[] = $medicine['name'];
            continue;
        }

        $cartModel->addItem($customerId, $medicineId, min($quantity, $roomLeft));
        $added[] = $medicine['name'];
    }

    // Remember what came from this prescription, together with anything added before.
    $prescriptionModel->saveAddedMedicines(
        $prescriptionId,
        array_unique(array_merge($prescription['added'], $added))
    );

    if (count($added) > 0) {
        $message = count($added) . ' ' . (count($added) === 1 ? 'medicine was' : 'medicines were')
            . ' added to your cart from your prescription.';

        if (count($skipped) > 0) {
            $message .= ' Out of stock: ' . implode(', ', $skipped) . '.';
        }

        setFlash('success', $message);
        redirect('customer/cart.php');
    }

    setFlash('warning', 'None of the chosen medicines are in stock right now.');
    redirect('customer/prescription_review.php?id=' . $prescriptionId);
}

// ---------------------------------------------------------------------
// Showing the page: refresh each suggestion with today's stock and price
// ---------------------------------------------------------------------
// The suggestions were saved when the photo was read. Stock and prices may
// have changed since, so they are read again now.
$suggestions = [];

foreach ($prescription['suggestions'] as $suggestion) {
    $medicine = $medicineModel->findActiveById((int) $suggestion['medicine_id']);

    if ($medicine === null) {
        continue;
    }

    $suggestions[] = [
        'medicine_id' => (int) $medicine['medicine_id'],
        'name' => $medicine['name'],
        'sku' => $medicine['sku'],
        'price' => (float) $medicine['price'],
        'available' => $inventoryModel->getAvailableStock((int) $medicine['medicine_id']),
        'confidence' => (float) $suggestion['confidence'],
        'source_line' => (string) ($suggestion['source_line'] ?? ''),
    ];
}

render('customer/prescription_review', [
    'pageTitle' => 'Review prescription',
    'prescription' => $prescription,
    'suggestions' => $suggestions,
]);