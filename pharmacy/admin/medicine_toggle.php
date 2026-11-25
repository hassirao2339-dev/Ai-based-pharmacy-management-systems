<?php

/**
 * admin/medicine_toggle.php - hide or show a medicine (controller)
 *
 * FR-39: "soft-delete a medicine (hidden from catalogue but retained in the
 *        database for historical order records)".
 * FR-60: the action is written to the audit log.
 *
 * This page has no HTML. It changes one flag and sends the admin back to the
 * medicine list.
 */

require_once __DIR__ . '/../app/bootstrap.php';

requireRole('admin');

if (!isPostRequest()) {
    redirect('admin/medicines.php');
}

verifyCsrf();

$medicineModel = new Medicine($pdo);

$medicineId = (int) postValue('medicine_id');
$medicine = $medicineModel->findById($medicineId);

if ($medicine === null) {
    setFlash('danger', 'That medicine no longer exists.');
    redirect('admin/medicines.php');
}

// The form sends 1 to show the medicine and 0 to hide it.
$shouldBeActive = (postValue('is_active') === '1');

$medicineModel->setActive($medicineId, $shouldBeActive);

if ($shouldBeActive) {
    logAdminAction(
        $pdo,
        'medicine_restore',
        'Showed "' . $medicine['name'] . '" in the catalogue again',
        'medicines',
        $medicineId
    );

    setFlash('success', $medicine['name'] . ' is visible in the catalogue again.');
} else {
    logAdminAction(
        $pdo,
        'medicine_delete',
        'Hid "' . $medicine['name'] . '" from the catalogue',
        'medicines',
        $medicineId
    );

    setFlash('success', $medicine['name'] . ' is now hidden from customers. Past orders still show it.');
}

redirect('admin/medicines.php');