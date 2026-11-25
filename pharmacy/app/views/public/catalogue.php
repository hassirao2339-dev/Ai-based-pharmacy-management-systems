<?php
/**
 * catalogue.php - medicine catalogue view
 * Controller: catalogue.php
 *
 * Variables:
 *   $user        the logged-in user, or null for a guest
 *   $medicines   the medicines to show
 *   $categories  every category that has medicines
 *   $searchText  what was typed in the search box
 *   $category    the chosen category, or '' for all
 *
 * $medicineRank is set to null so the shared medicine card does not show a
 * position number here. It is only used by the trending lists.
 */

$medicineRank = null;
?>
<section class="page-head">
    <div class="container">
        <h1 class="h2 mb-1">Medicines</h1>
        <p class="text-body-secondary mb-4">
            Search by name, SKU or manufacturer, or choose a category.
        </p>

        <!-- Search box (FR-19). GET keeps the search in the address bar.
             main.js also searches as you type, using the AJAX endpoint. -->
        <form action="<?= e(url('catalogue.php')) ?>" method="get" role="search" class="mb-3" id="catalogueForm">
            <?php if ($category !== ''): ?>
                <!-- Searching inside a category keeps that category selected. -->
                <input type="hidden" name="category" value="<?= e($category) ?>">
            <?php endif; ?>

            <label for="catalogueSearch" class="visually-hidden">Search medicines</label>
            <div class="search-box">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input id="catalogueSearch" type="search" name="search" maxlength="100"
                       value="<?= e($searchText) ?>"
                       placeholder="Search by medicine name, SKU or manufacturer">
                <button class="btn btn-primary" type="submit">Search</button>
            </div>
        </form>

        <!-- Category filter (FR-20) -->
        <div class="chip-row" aria-label="Filter by category">
            <a class="chip<?= $category === '' ? ' active' : '' ?>"
               href="<?= e(url('catalogue.php' . ($searchText !== '' ? '?search=' . urlencode($searchText) : ''))) ?>">
                All
            </a>

            <?php foreach ($categories as $categoryName): ?>
                <?php
                // Keep the search words when switching category.
                $categoryLink = 'catalogue.php?category=' . urlencode($categoryName);

                if ($searchText !== '') {
                    $categoryLink .= '&search=' . urlencode($searchText);
                }
                ?>
                <a class="chip<?= $category === $categoryName ? ' active' : '' ?>"
                   href="<?= e(url($categoryLink)) ?>"><?= e($categoryName) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Everything inside #catalogueResults is replaced during a live search. -->
<section class="container py-4 py-lg-5">
    <div id="catalogueResults" data-category="<?= e($category) ?>">
        <?php require APP_PATH . '/views/partials/catalogue_results.php'; ?>
    </div>
</section>