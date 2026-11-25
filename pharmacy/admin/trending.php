<?php

/**
 * admin/trending.php - trending medicines (controller)
 *
 * FR-45: the top 10 most ordered medicines of the month.
 * FR-46: ranked by total quantity ordered.
 * FR-47: compared with the previous month.
 * FR-48: shown to the administrator as a ranked list with a chart.
 *
 * The month can be chosen in the address:  trending.php?year=2026&month=8
 * Anything missing or out of range falls back to the current month.
 */

require_once __DIR__ . '/../app/bootstrap.php';

requireRole('admin');

$trendingModel = new Trending($pdo);

// ---------------------------------------------------------------------
// Which month are we looking at?
// ---------------------------------------------------------------------
$year = (int) date('Y');
$month = (int) date('n');

if (isset($_GET['year']) && isset($_GET['month'])) {
    $chosenYear = (int) $_GET['year'];
    $chosenMonth = (int) $_GET['month'];

    // Only a sensible year and a real month number are accepted.
    if ($chosenYear >= 2020 && $chosenYear <= 2100 && $chosenMonth >= 1 && $chosenMonth <= 12) {
        $year = $chosenYear;
        $month = $chosenMonth;
    }
}

// The month before, for the comparison panel.
$previousMonth = $month - 1;
$previousYear = $year;

if ($previousMonth < 1) {
    $previousMonth = 12;
    $previousYear = $year - 1;
}

render('admin/trending', [
    'pageTitle' => 'Trending medicines',
    'medicines' => $trendingModel->getTopWithComparison($year, $month, 10),
    'monthTotals' => $trendingModel->getMonthTotals($year, $month),
    'previousTotals' => $trendingModel->getMonthTotals($previousYear, $previousMonth),
    'availableMonths' => $trendingModel->getAvailableMonths(12),
    'year' => $year,
    'month' => $month,
    'previousYear' => $previousYear,
    'previousMonth' => $previousMonth,
], 'admin');