<?php

/**
 * admin/medicine_form.php - add or edit a medicine (controller)
 *
 * FR-34: add a medicine with name, SKU, DRAP category, manufacturer,
 *        unit price, description and low-stock reorder threshold.
 * FR-39: edit any medicine record.
 * FR-60: both actions are written to the audit log.
 *
 * One page does both jobs:
 *   medicine_form.php          -> add a new medicine
 *   medicine_form.php?id=7     -> edit medicine 7
 */

require_once __DIR__ . '/../app/bootstrap.php';

requireRole('admin');

$medicineModel = new Medicine($pdo);

// ---------------------------------------------------------------------
// Adding or editing?
// ---------------------------------------------------------------------
$medicineId = 0;

if (isset($_GET['id'])) {
    $medicineId = (int) $_GET['id'];
}

if (isPostRequest()) {
    $medicineId = (int) postValue('medicine_id');
}

$medicine = null;

if ($medicineId > 0) {
    $medicine = $medicineModel->findById($medicineId);

    if ($medicine === null) {
        showErrorPage(404, 'Medicine not found', 'That medicine is not in the catalogue.');
    }
}

$isEditing = ($medicine !== null);

// ---------------------------------------------------------------------
// What the form should show
// ---------------------------------------------------------------------
$form = [
    'name' => '',
    'sku' => '',
    'category' => '',
    'drap_category' => '',
    'manufacturer' => '',
    'description' => '',
    'price' => '',
    'reorder_threshold' => '10',
];

if ($isEditing) {
    $form = [
        'name' => $medicine['name'],
        'sku' => $medicine['sku'],
        'category' => $medicine['category'],
        'drap_category' => $medicine['drap_category'],
        'manufacturer' => $medicine['manufacturer'],
        'description' => $medicine['description'],
        'price' => $medicine['price'],
        'reorder_threshold' => $medicine['reorder_threshold'],
    ];
}

$errors = [];

if (isPostRequest()) {
    verifyCsrf();

    $input = getMedicineInput();
    $form = $input;

    // When editing, the medicine keeps its own SKU, so it is ignored in the check.
    $ignoreId = $isEditing ? $medicineId : null;

    $errors = validateMedicineInput($input, $medicineModel, $ignoreId);

    if (count($errors) === 0) {
        if ($isEditing) {
            $medicineModel->update($medicineId, $input);

            logAdminAction(
                $pdo,
                'medicine_update',
                'Updated medicine "' . $input['name'] . '" (' . $input['sku'] . ')',
                'medicines',
                $medicineId
            );

            setFlash('success', $input['name'] . ' has been updated.');
            redirect('admin/medicines.php');
        }

        $newMedicineId = $medicineModel->create($input);

        logAdminAction(
            $pdo,
            'medicine_create',
            'Added medicine "' . $input['name'] . '" (' . $input['sku'] . ')',
            'medicines',
            $newMedicineId
        );

        // A new medicine has no stock yet, so the next useful step is a batch.
        setFlash('success', $input['name'] . ' has been added. Now add a stock batch so customers can order it.');
        redirect('admin/batch_form.php?medicine_id=' . $newMedicineId);
    }
}

render('admin/medicine_form', [
    'pageTitle' => $isEditing ? 'Edit medicine' : 'Add medicine',
    'isEditing' => $isEditing,
    'medicine' => $medicine,
    'medicineId' => $medicineId,
    'form' => $form,
    'errors' => $errors,
    'categories' => $medicineModel->getAllCategories(),
    'manufacturers' => $medicineModel->getAllManufacturers(),
], 'admin');