<?php

/**
 * admin/batches.php - stock batches (controller)
 *
 * FR-35: list the inventory batches of every medicine.
 * FR-38: flag any batch expiring within EXPIRY_WARNING_DAYS days.
 *
 * batches.php                  -> every batch in the pharmacy
 * batches.php?medicine_id=7    -> only the batches of medicine 7
 */

require_once __DIR__ . '/../app/bootstrap.php';

requireRole('admin');

$inventoryModel = new Inventory($pdo);
$medicineModel = new Medicine($pdo);

// ---------------------------------------------------------------------
// Are we looking at one medicine, or all of them?
// ---------------------------------------------------------------------
$medicineId = 0;

if (isset($_GET['medicine_id'])) {
    $medicineId = (int) $_GET['medicine_id'];
}

$medicine = null;

if ($medicineId > 0) {
    $medicine = $medicineModel->findById($medicineId);

    if ($medicine === null) {
        showErrorPage(404, 'Medicine not found', 'That medicine is not in the catalogue.');
    }
}

$batches = $inventoryModel->getBatchesForAdmin($medicine === null ? null : $medicineId);

render('admin/batches', [
    'pageTitle' => 'Stock batches',
    'batches' => $batches,
    'medicine' => $medicine,
    'medicineId' => $medicineId,
], 'admin');