<?php

/**
 * admin/batch_form.php - add or edit a stock batch (controller)
 *
 * FR-35: add inventory batches with quantity, supplier name, cost price,
 *        received date, expiry date and a FIFO order sequence.
 * FR-36: a batch with an expiry date in the past is refused.
 * FR-60: both actions are written to the audit log.
 *
 * batch_form.php                  -> add a batch, medicine chosen in a dropdown
 * batch_form.php?medicine_id=7    -> add a batch for medicine 7
 * batch_form.php?id=12            -> edit batch 12
 */

require_once __DIR__ . '/../app/bootstrap.php';

requireRole('admin');

$inventoryModel = new Inventory($pdo);
$medicineModel = new Medicine($pdo);

// ---------------------------------------------------------------------
// Adding or editing?
// ---------------------------------------------------------------------
$batchId = 0;

if (isset($_GET['id'])) {
    $batchId = (int) $_GET['id'];
}

if (isPostRequest()) {
    $batchId = (int) postValue('batch_id');
}

$batch = null;

if ($batchId > 0) {
    $batch = $inventoryModel->findBatchById($batchId);

    if ($batch === null) {
        showErrorPage(404, 'Batch not found', 'That stock batch does not exist.');
    }
}

$isEditing = ($batch !== null);

// ---------------------------------------------------------------------
// Which medicine is this batch for?
// ---------------------------------------------------------------------
$medicineId = 0;

if ($isEditing) {
    $medicineId = (int) $batch['medicine_id'];
} elseif (isset($_GET['medicine_id'])) {
    $medicineId = (int) $_GET['medicine_id'];
}

if (isPostRequest() && !$isEditing) {
    $medicineId = (int) postValue('medicine_id');
}

$medicine = null;

if ($medicineId > 0) {
    $medicine = $medicineModel->findById($medicineId);
}

// ---------------------------------------------------------------------
// What the form should show
// ---------------------------------------------------------------------
$form = [
    'quantity' => '',
    'supplier_name' => '',
    'cost_price' => '',
    'received_date' => date('Y-m-d'),   // a delivery is usually logged the day it arrives
    'expiry_date' => '',
];

if ($isEditing) {
    $form = [
        'quantity' => $batch['quantity'],
        'supplier_name' => $batch['supplier_name'],
        'cost_price' => $batch['cost_price'],
        'received_date' => $batch['received_date'],
        'expiry_date' => $batch['expiry_date'],
    ];
}

// When editing, the batch cannot drop below the units orders already hold.
$minQuantity = 1;

if ($isEditing) {
    $minQuantity = max(1, $inventoryModel->countAllocatedUnits($batchId));
}

$errors = [];

if (isPostRequest()) {
    verifyCsrf();

    $input = getBatchInput();
    $form = $input;

    $errors = validateBatchInput($input, $minQuantity);

    // Adding a batch needs a medicine to attach it to.
    if (!$isEditing && $medicine === null) {
        $errors['medicine_id'] = 'Please choose the medicine this batch is for.';
    }

    if (count($errors) === 0) {
        if ($isEditing) {
            $inventoryModel->updateBatch($batchId, $input);

            logAdminAction(
                $pdo,
                'batch_update',
                'Updated batch #' . $batch['fifo_order'] . ' of "' . $batch['medicine_name']
                    . '" (now ' . $input['quantity'] . ' units)',
                'inventory_batches',
                $batchId
            );

            setFlash('success', 'The batch has been updated and the stock recalculated.');
            redirect('admin/batches.php?medicine_id=' . $medicineId);
        }

        $newBatchId = $inventoryModel->createBatch($medicineId, $input);

        logAdminAction(
            $pdo,
            'batch_create',
            'Added ' . $input['quantity'] . ' units of "' . $medicine['name']
                . '" from ' . $input['supplier_name'],
            'inventory_batches',
            $newBatchId
        );

        setFlash('success', $input['quantity'] . ' units of ' . $medicine['name'] . ' have been added to stock.');
        redirect('admin/batches.php?medicine_id=' . $medicineId);
    }
}

render('admin/batch_form', [
    'pageTitle' => $isEditing ? 'Edit batch' : 'Add stock batch',
    'isEditing' => $isEditing,
    'batch' => $batch,
    'batchId' => $batchId,
    'medicine' => $medicine,
    'medicineId' => $medicineId,
    'minQuantity' => $minQuantity,
    'form' => $form,
    'errors' => $errors,
    'medicines' => $medicineModel->getAllForAdmin(),
    'nextFifoOrder' => $medicineId > 0 ? $inventoryModel->getNextFifoOrder($medicineId) : 1,
], 'admin');