<?php
/**
 * catalogue_results.php - the part of the catalogue that changes when searching
 *
 * Used twice:
 *   1. public/catalogue.php loads it when the page is first opened.
 *   2. ajax/catalogue_search.php returns it on its own during a live search.
 *
 * Keeping it in one file means the page looks the same either way.
 *
 * Needs: $medicines, $searchText, $category, $user, $medicineRank
 */

$resultCount = count($medicines);

// True when the customer is looking at everything, with no search or filter.
$showingEverything = ($searchText === '' && $category === '');
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <p class="result-count mb-0">
        <?php if ($showingEverything): ?>
            Showing all <strong><?= e($resultCount) ?></strong>
            <?= $resultCount === 1 ? 'medicine' : 'medicines' ?>
        <?php else: ?>
            <strong><?= e($resultCount) ?></strong>
            <?= $resultCount === 1 ? 'medicine' : 'medicines' ?> found
            <?php if ($searchText !== ''): ?>
                for &ldquo;<?= e($searchText) ?>&rdquo;
            <?php endif; ?>
            <?php if ($category !== ''): ?>
                in <?= e($category) ?>
            <?php endif; ?>
        <?php endif; ?>
    </p>

    <?php if (!$showingEverything): ?>
        <a class="see-all" href="<?= e(url('catalogue.php')) ?>">
            <i class="bi bi-x-lg" aria-hidden="true"></i> Clear search and filter
        </a>
    <?php endif; ?>
</div>

<?php if ($resultCount === 0): ?>

    <!-- Nothing matched -->
    <div class="empty-state">
        <span class="empty-icon" aria-hidden="true"><i class="bi bi-search"></i></span>
        <h2 class="h4">No medicines found</h2>
        <p class="text-body-secondary">
            We couldn't find anything matching your search. Check the spelling, try a shorter word,
            or upload your prescription and we'll look for you.
        </p>
        <div class="d-flex flex-wrap justify-content-center gap-2">
            <a class="btn btn-primary" href="<?= e(url('catalogue.php')) ?>">Show all medicines</a>
            <a class="btn btn-soft" href="<?= e(url('customer/prescription_upload.php')) ?>">
                <i class="bi bi-file-earmark-arrow-up" aria-hidden="true"></i> Upload prescription
            </a>
        </div>
    </div>

<?php else: ?>

    <div class="row g-3 g-lg-4">
        <?php foreach ($medicines as $medicine): ?>
            <div class="col-sm-6 col-lg-4 col-xl-3">
                <?php require APP_PATH . '/views/partials/medicine_card.php'; ?>
            </div>
        <?php endforeach; ?>
    </div>

<?php endif; ?>