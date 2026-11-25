<?php

/**
 * admin/dashboard.php - administrator dashboard (controller)
 *
 * FR-57: show total orders today, pending orders, revenue this month,
 *        active low-stock alerts and total registered customers.
 * FR-42: show the medicines that need restocking.
 * FR-43: flag batches expiring within EXPIRY_WARNING_DAYS days.
 */

require_once __DIR__ . '/../app/bootstrap.php';

requireRole('admin');

$statsModel = new AdminStats($pdo);
$inventoryModel = new Inventory($pdo);

// FR-43: the soonest-expiring batches, so the admin sees them without
// having to open the expiry page.
$expiringBatches = $inventoryModel->getExpiringBatches(EXPIRY_WARNING_DAYS);

render('admin/dashboard', [
    'pageTitle' => 'Dashboard',
    'user' => currentUser(),
    'metrics' => $statsModel->getDashboardMetrics(),
    'latestOrders' => $statsModel->getLatestOrders(5),
    'lowStockMedicines' => $statsModel->getLowStockMedicines(5),
    'expiringBatches' => array_slice($expiringBatches, 0, 5),
    'expiringTotal' => count($expiringBatches),
], 'admin');