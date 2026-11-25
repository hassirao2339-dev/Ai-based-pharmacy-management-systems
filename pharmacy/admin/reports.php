<?php

/**
 * admin/reports.php - reports (controller)
 *
 * FR-51: sales report for a date range.
 * FR-52: inventory report with stock values.
 * FR-53: order report grouped by status.
 * FR-54: low-stock report.
 * FR-55: any report can be exported as a PDF.
 * FR-56, FR-60: every generated report is recorded.
 *
 * reports.php                              -> the report chooser
 * reports.php?type=sales&from=..&to=..     -> that report on screen
 * reports.php?type=sales&...&format=pdf    -> the same report as a PDF
 */

require_once __DIR__ . '/../app/bootstrap.php';

requireRole('admin');

$reportModel = new Report($pdo);

// ---------------------------------------------------------------------
// Which report, and for which dates?
// ---------------------------------------------------------------------
$reportTypes = ['sales', 'inventory', 'orders', 'low_stock'];

$type = '';

if (isset($_GET['type']) && in_array($_GET['type'], $reportTypes, true)) {
    $type = $_GET['type'];
}

// Default range: the start of this month up to today.
$fromDate = date('Y-m-01');
$toDate = date('Y-m-d');

if (isset($_GET['from']) && is_string($_GET['from']) && isValidDate($_GET['from'])) {
    $fromDate = $_GET['from'];
}

if (isset($_GET['to']) && is_string($_GET['to']) && isValidDate($_GET['to'])) {
    $toDate = $_GET['to'];
}

// A range that runs backwards is swapped rather than refused.
if (strtotime($fromDate) > strtotime($toDate)) {
    [$fromDate, $toDate] = [$toDate, $fromDate];
}

$wantsPdf = (isset($_GET['format']) && $_GET['format'] === 'pdf');

// ---------------------------------------------------------------------
// No report chosen: show the chooser
// ---------------------------------------------------------------------
if ($type === '') {
    render('admin/reports', [
        'pageTitle' => 'Reports',
        'fromDate' => $fromDate,
        'toDate' => $toDate,
        'recentReports' => $reportModel->getRecentReports(10),
        'pdfAvailable' => isPdfAvailable(),
    ], 'admin');

    exit;
}

// ---------------------------------------------------------------------
// Gather the data for the chosen report
// ---------------------------------------------------------------------
$reportTitles = [
    'sales' => 'Sales report',
    'inventory' => 'Inventory report',
    'orders' => 'Order report',
    'low_stock' => 'Low-stock report',
];

// Inventory and low-stock are a snapshot of right now, so they have no range.
$usesDateRange = in_array($type, ['sales', 'orders'], true);

$reportData = [
    'type' => $type,
    'title' => $reportTitles[$type],
    'fromDate' => $fromDate,
    'toDate' => $toDate,
    'usesDateRange' => $usesDateRange,
    'generatedAt' => date('j F Y, g:i a'),
    'generatedBy' => $_SESSION['user_name'],
];

if ($type === 'sales') {
    $reportData['summary'] = $reportModel->getSalesSummary($fromDate, $toDate);
    $reportData['byMedicine'] = $reportModel->getSalesByMedicine($fromDate, $toDate);
    $reportData['byDay'] = $reportModel->getSalesByDay($fromDate, $toDate);
} elseif ($type === 'inventory') {
    $reportData['rows'] = $reportModel->getInventoryReport();
} elseif ($type === 'orders') {
    $reportData['byStatus'] = $reportModel->getOrdersByStatus($fromDate, $toDate);
    $reportData['orders'] = $reportModel->getOrdersInRange($fromDate, $toDate);
} else {
    $reportData['rows'] = $reportModel->getLowStockReport();
}

// ---------------------------------------------------------------------
// Record that the report was generated (FR-56, FR-60)
// ---------------------------------------------------------------------
$recordedFrom = $usesDateRange ? $fromDate : null;
$recordedTo = $usesDateRange ? $toDate : null;

$reportId = $reportModel->recordGeneration(
    (int) $_SESSION['user_id'],
    $type,
    $recordedFrom,
    $recordedTo
);

$logDescription = 'Generated the ' . strtolower($reportTitles[$type]);

if ($usesDateRange) {
    $logDescription .= ' for ' . $fromDate . ' to ' . $toDate;
}

$logDescription .= $wantsPdf ? ' (PDF)' : ' (on screen)';

logAdminAction($pdo, 'report_generate', $logDescription, 'reports', $reportId);

// ---------------------------------------------------------------------
// PDF (FR-55) or screen
// ---------------------------------------------------------------------
if ($wantsPdf) {
    $html = captureView('admin/report_pdf', $reportData);

    $fileName = 'pharmacare-' . str_replace('_', '-', $type) . '-' . date('Y-m-d') . '.pdf';

    // The wider reports read better on a landscape page.
    $paperStyle = in_array($type, ['inventory', 'sales'], true) ? 'landscape' : 'portrait';

    sendPdf($html, $fileName, 'A4', $paperStyle);
}

$reportData['pageTitle'] = $reportTitles[$type];
$reportData['pdfAvailable'] = isPdfAvailable();

render('admin/report_view', $reportData, 'admin');