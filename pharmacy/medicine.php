<?php

/**
 * medicine.php - medicine details (controller)
 *
 * FR-21: show name, SKU, DRAP category, manufacturer, price, stock quantity,
 *        expiry month and description for one medicine.
 *
 * The medicine is chosen by its id in the address:  medicine.php?id=7
 */

require_once __DIR__ . '/app/bootstrap.php';

$medicineModel = new Medicine($pdo);

// (int) turns anything that is not a number into 0, which finds no medicine.
$medicineId = 0;

if (isset($_GET['id'])) {
    $medicineId = (int) $_GET['id'];
}

$medicine = $medicineModel->findActiveById($medicineId);

// A medicine that does not exist, or that the admin has hidden, gives a normal
// "not found" page rather than an empty page (FR-39).
if ($medicine === null) {
    showErrorPage(
        404,
        'Medicine not found',
        'This medicine is not in our catalogue. It may have been removed. Please search the catalogue instead.'
    );
}

// Other medicines in the same category, shown at the bottom of the page.
$relatedMedicines = $medicineModel->getRelated(
    (int) $medicine['medicine_id'],
    $medicine['category']
);

render('public/medicine', [
    'pageTitle' => $medicine['name'],
    'user' => currentUser(),
    'medicine' => $medicine,
    'relatedMedicines' => $relatedMedicines,
]);