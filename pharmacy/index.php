<?php

/**
 * index.php - homepage (controller)
 *
 * 1. Load the application: settings, session, helpers, database.
 * 2. Prepare the data the homepage needs.
 * 3. Show the homepage view inside the main layout.
 *
 * FR-49: the five most ordered medicines of the current month are shown here
 *        as "Popular this month". The figures come from the trending view, so
 *        they are always current (FR-50).
 */

require_once __DIR__ . '/app/bootstrap.php';

$medicineModel = new Medicine($pdo);
$trendingModel = new Trending($pdo);

render('public/home', [
    'pageTitle' => 'Order medicines online',
    'user' => currentUser(),   // null for guests; the view changes its buttons based on this

    // Categories for the chips under the search box, and the newest medicines
    // for the "Browse medicines" section.
    'categories' => $medicineModel->getCategories(),
    'latestMedicines' => $medicineModel->getLatest(4),

    // FR-49: top 5 of this month. The section hides itself while the list is
    // empty, which is what happens at the start of a month.
    'trendingMedicines' => $trendingModel->getTopActiveMedicines((int) date('Y'), (int) date('n'), 5),
]);