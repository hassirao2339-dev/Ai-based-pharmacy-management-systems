<?php

/**
 * admin/alerts.php - low-stock alerts (controller)
 *
 * FR-41: alerts are created automatically when stock falls to or below the
 *        reorder threshold (see Inventory::recalculateMedicineStock).
 * FR-42: the administrator sees open alerts and acknowledges them.
 * FR-44: medicines at or below their threshold are highlighted.
 *
 * alerts.php            -> alerts still waiting
 * alerts.php?show=all   -> every alert, including the ones dealt with
 */

require_once __DIR__ . '/../app/bootstrap.php';

requireRole('admin');

$alertModel = new StockAlert($pdo);

// ---------------------------------------------------------------------
// Acknowledging (FR-42)
// ---------------------------------------------------------------------
if (isPostRequest()) {
    verifyCsrf();

    $action = postValue('action');

    if ($action === 'acknowledge_all') {
        $closedCount = $alertModel->acknowledgeAll();

        logAdminAction($pdo, 'alert_acknowledge', 'Acknowledged all ' . $closedCount . ' open low-stock alerts');

        setFlash('success', $closedCount . ' alerts have been acknowledged.');
        redirect('admin/alerts.php');
    }

    if ($action === 'acknowledge') {
        $alertId = (int) postValue('alert_id');
        $alert = $alertModel->findById($alertId);

        if ($alert === null) {
            setFlash('danger', 'That alert no longer exists.');
            redirect('admin/alerts.php');
        }

        $alertModel->acknowledge($alertId);

        logAdminAction(
            $pdo,
            'alert_acknowledge',
            'Acknowledged the low-stock alert for "' . $alert['medicine_name'] . '"',
            'stock_alerts',
            $alertId
        );

        setFlash('success', 'The alert for ' . $alert['medicine_name'] . ' has been acknowledged.');
        redirect('admin/alerts.php');
    }

    redirect('admin/alerts.php');
}

// ---------------------------------------------------------------------
// Showing the page
// ---------------------------------------------------------------------
$showAll = (isset($_GET['show']) && $_GET['show'] === 'all');

render('admin/alerts', [
    'pageTitle' => 'Low-stock alerts',
    'alerts' => $alertModel->getAlerts(!$showAll),
    'openCount' => $alertModel->countOpen(),
    'showAll' => $showAll,
], 'admin');