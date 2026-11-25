<?php

/**
 * ajax/catalogue_search.php - live catalogue search (AJAX endpoint)
 *
 * SRS 4.4: "AJAX requests using jQuery exchange data in JSON format with PHP
 * endpoint scripts for catalogue filtering."
 *
 * This page has no layout. It returns only the medicine cards as HTML, and
 * main.js drops them into the catalogue page without reloading it.
 *
 * The catalogue still works completely without JavaScript, because the search
 * form and the category links are ordinary GET links. This is called
 * progressive enhancement: JavaScript makes it nicer, it is not required.
 */

require_once __DIR__ . '/../app/bootstrap.php';

$medicineModel = new Medicine($pdo);

// ---------------------------------------------------------------------
// Same reading and checking as catalogue.php
// ---------------------------------------------------------------------
$searchText = '';

if (isset($_GET['search']) && is_string($_GET['search'])) {
    $searchText = trim($_GET['search']);
}

$category = '';

if (isset($_GET['category']) && is_string($_GET['category'])) {
    $category = trim($_GET['category']);
}

if ($category !== '' && !in_array($category, $medicineModel->getCategories(), true)) {
    $category = '';
}

$medicines = $medicineModel->search($searchText, $category);

// The medicine card partial needs these two variables.
$user = currentUser();
$medicineRank = null;

// ---------------------------------------------------------------------
// Send back only the part of the page that changes
// ---------------------------------------------------------------------
header('Content-Type: text/html; charset=utf-8');

require APP_PATH . '/views/partials/catalogue_results.php';