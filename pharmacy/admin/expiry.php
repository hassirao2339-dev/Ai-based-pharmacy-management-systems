<?php

/**
 * admin/expiry.php - stock expiring soon (controller)
 *
 * FR-43: "The system shall flag medicines with batches expiring within 30 days
 *        on the Administrator dashboard."
 * FR-37: expired stock is never sold, because FIFO only looks at batches whose
 *        expiry date is in the future. This page lets the admin take the
 *        expired stock off the shelf as well.
 */

require_once __DIR__ . '/../app/bootstrap.php';

requireRole('admin');

$inventoryModel = new Inventory($pdo);

// ---------------------------------------------------------------------
// Writing off an expired batch
// ---------------------------------------------------------------------
if (isPostRequest()) {
    verifyCsrf();

    $batchId = (int) postValue('batch_id');
    $batch = $inventoryModel->findBatchById($batchId);

    if ($batch === null) {
        setFlash('danger', 'That batch no longer exists.');
        redirect('admin/expiry.php');
    }

    // Only stock that has actually expired may be written off. Stock that is
    // still good should be sold, not thrown away.
    if (strtotime($batch['expiry_date']) > strtotime(date('Y-m-d'))) {
        setFlash('warning', 'That batch has not expired yet, so it cannot be written off.');
        redirect('admin/expiry.php');
    }

    $lostUnits = (int) $batch['quantity'];

    $inventoryModel->writeOffBatch($batchId);

    logAdminAction(
        $pdo,
        'batch_write_off',
        'Wrote off ' . $lostUnits . ' expired units of "' . $batch['medicine_name']
            . '" (batch #' . $batch['fifo_order'] . ', expired '
            . date('j M Y', strtotime($batch['expiry_date'])) . ')',
        'inventory_batches',
        $batchId
    );

    setFlash('success', $lostUnits . ' expired units of ' . $batch['medicine_name'] . ' have been written off.');
    redirect('admin/expiry.php');
}

// ---------------------------------------------------------------------
// Showing the page
// ---------------------------------------------------------------------
$batches = $inventoryModel->getExpiringBatches(EXPIRY_WARNING_DAYS);

// Split the list, because expired stock needs a different action from stock
// that can still be sold.
$expiredBatches = [];
$expiringSoonBatches = [];
$valueAtRisk = 0.0;

foreach ($batches as $batch) {
    $valueAtRisk += (float) $batch['value_at_risk'];

    if ((int) $batch['days_to_expiry'] <= 0) {
        $expiredBatches[] = $batch;
    } else {
        $expiringSoonBatches[] = $batch;
    }
}

render('admin/expiry', [
    'pageTitle' => 'Expiry warnings',
    'expiredBatches' => $expiredBatches,
    'expiringSoonBatches' => $expiringSoonBatches,
    'valueAtRisk' => $valueAtRisk,
], 'admin');