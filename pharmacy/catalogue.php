<?php

/**
 * catalogue.php - the medicine catalogue (controller)
 *
 * FR-18: show every active medicine with name, category, manufacturer,
 *        price and stock availability.
 * FR-19: search by name, SKU or manufacturer.
 * FR-20: filter by category.
 *
 * The search box and the category links use GET, so a search can be
 * bookmarked and shared:  catalogue.php?search=syrup&category=Antibiotic
 */

require_once __DIR__ . '/app/bootstrap.php';

$medicineModel = new Medicine($pdo);

// ---------------------------------------------------------------------
// Read the search box and the chosen category from the address
// ---------------------------------------------------------------------
$searchText = '';

if (isset($_GET['search']) && is_string($_GET['search'])) {
    $searchText = trim($_GET['search']);
}

$category = '';

if (isset($_GET['category']) && is_string($_GET['category'])) {
    $category = trim($_GET['category']);
}

$categories = $medicineModel->getCategories();

// Only a category that really exists is used. Anything else is ignored,
// so a made-up category in the address cannot produce a strange page.
if ($category !== '' && !in_array($category, $categories, true)) {
    $category = '';
}

// ---------------------------------------------------------------------
// Ask the model for the medicines
// ---------------------------------------------------------------------
$medicines = $medicineModel->search($searchText, $category);

render('public/catalogue', [
    'pageTitle' => 'Medicines',
    'user' => currentUser(),
    'medicines' => $medicines,
    'categories' => $categories,
    'searchText' => $searchText,
    'category' => $category,
]);