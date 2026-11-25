<?php

/**
 * admin/medicines.php - the medicine list (controller)
 *
 * FR-40: search and filter the inventory list by name, SKU, category or
 *        stock status. DataTables does that in the browser, so the whole
 *        list is sent once and searching feels instant.
 * FR-44: medicines at or below their reorder threshold are highlighted.
 * FR-38: stock expiring within 30 days is flagged.
 */

require_once __DIR__ . '/../app/bootstrap.php';

requireRole('admin');

$medicineModel = new Medicine($pdo);

render('admin/medicines', [
    'pageTitle' => 'Medicines',
    'medicines' => $medicineModel->getAllForAdmin(),
], 'admin');