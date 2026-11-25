<?php
/**
 * prescription_review.php - review the OCR result view
 * Controller: customer/prescription_review.php
 *
 * Variables:
 *   $prescription  the prescription with ocr, added and not_found arrays
 *   $suggestions   matched medicines with today's stock and price
 */

$prescriptionId = (int) $prescription['prescription_id'];
$isFailed = ($prescription['status'] === 'failed');
$ocr = $prescription['ocr'];
$ocrConfidence = (float) ($ocr['confidence'] ?? 0);

/**
 * How a confidence score is shown (FR-14).
 * The bands are deliberately simple so a customer understands them at a glance.
 */
$confidenceBand = function (float $score): array {
    if ($score >= 90) {
        return ['class' => 'conf-high', 'label' => 'Very likely'];
    }

    if ($score >= 80) {
        return ['class' => 'conf-mid', 'label' => 'Likely'];
    }

    return ['class' => 'conf-low', 'label' => 'Please check'];
};
?>
<nav class="breadcrumb-bar" aria-label="Breadcrumb">
    <div class="container">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= e(url('customer/prescriptions.php')) ?>">My prescriptions</a></li>
            <li class="breadcrumb-item active" aria-current="page">
                Uploaded <?= e(date('j M Y, g:i a', strtotime($prescription['uploaded_at']))) ?>
            </li>
        </ol>
    </div>
</nav>

<section class="container py-4 py-lg-5">
    <div class="row g-4">

        <!-- ============ The photo and what was read ============ -->
        <div class="col-lg-5">
            <div class="settings-card mb-4">
                <h2 class="h5 mb-3">Your prescription</h2>

                <!-- Served through a checked page, because the file is private -->
                <img class="rx-photo" alt="Your uploaded prescription"
                     src="<?= e(url('customer/prescription_image.php?id=' . $prescriptionId)) ?>">
            </div>

            <?php if (!$isFailed): ?>
                <div class="settings-card">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h2 class="h5 mb-0">Text we read</h2>
                        <span class="text-body-secondary small">
                            Reading quality <?= e(round($ocrConfidence)) ?>%
                        </span>
                    </div>

                    <!-- FR-12: the overall confidence of the OCR -->
                    <div class="conf-meter mb-3" aria-hidden="true">
                        <span style="width: <?= e(max(0, min(100, round($ocrConfidence)))) ?>%"></span>
                    </div>

                    <pre class="rx-text"><?= e($ocr['text'] ?? '') ?></pre>
                </div>
            <?php endif; ?>
        </div>

        <!-- ============ The matches (FR-13 to FR-16) ============ -->
        <div class="col-lg-7">

            <?php if ($isFailed): ?>

                <div class="settings-card">
                    <div class="empty-state">
                        <span class="empty-icon" aria-hidden="true"><i class="bi bi-eye-slash"></i></span>
                        <h2 class="h4">We could not read this photo</h2>
                        <p class="text-body-secondary">
                            The text was not clear enough. A brighter, sharper photo taken straight above
                            the page usually works. You can also search the catalogue yourself.
                        </p>
                        <div class="d-flex flex-wrap justify-content-center gap-2">
                            <a class="btn btn-primary" href="<?= e(url('customer/prescription_upload.php')) ?>">
                                Try another photo
                            </a>
                            <a class="btn btn-soft" href="<?= e(url('catalogue.php')) ?>">Search medicines</a>
                        </div>
                    </div>
                </div>

            <?php else: ?>

                <div class="settings-card mb-4">
                    <h2 class="h5 mb-1">Medicines we found</h2>
                    <p class="text-body-secondary small mb-4">
                        Tick the ones you want and set how many. Please compare each one with your
                        prescription: the percentage shows how sure we are of the match.
                    </p>

                    <?php if (empty($suggestions)): ?>

                        <p class="text-body-secondary mb-0">
                            We could not match anything on this prescription to our catalogue.
                            Please search for the medicines yourself.
                        </p>

                    <?php else: ?>

                        <form method="post" action="<?= e(url('customer/prescription_review.php')) ?>">
                            <?= csrfField() ?>
                            <input type="hidden" name="prescription_id" value="<?= e($prescriptionId) ?>">

                            <?php foreach ($suggestions as $suggestion): ?>
                                <?php
                                $medicineId = $suggestion['medicine_id'];
                                $inStock = ($suggestion['available'] > 0);
                                $band = $confidenceBand($suggestion['confidence']);
                                $wasAdded = in_array($suggestion['name'], $prescription['added'], true);

                                // A strong, in-stock match is ticked for the customer; anything
                                // doubtful is left for them to decide.
                                $tickByDefault = ($inStock && $suggestion['confidence'] >= 80 && !$wasAdded);
                                ?>
                                <div class="rx-match<?= $inStock ? '' : ' is-out' ?>">
                                    <input class="form-check-input rx-check" type="checkbox"
                                           id="pick<?= e($medicineId) ?>" name="selected[]" value="<?= e($medicineId) ?>"
                                           <?= $tickByDefault ? 'checked' : '' ?> <?= $inStock ? '' : 'disabled' ?>>

                                    <label class="rx-match-info" for="pick<?= e($medicineId) ?>">
                                        <span class="rx-match-name"><?= e($suggestion['name']) ?></span>
                                        <span class="rx-match-meta">
                                            <?= e(formatPrice($suggestion['price'])) ?> each
                                            <?php if ($suggestion['source_line'] !== ''): ?>
                                                &middot; read as &ldquo;<?= e($suggestion['source_line']) ?>&rdquo;
                                            <?php endif; ?>
                                        </span>

                                        <?php if (!$inStock): ?>
                                            <span class="rx-match-warning">Out of stock right now</span>
                                        <?php elseif ($wasAdded): ?>
                                            <span class="rx-match-added">
                                                <i class="bi bi-check-circle" aria-hidden="true"></i> Already added once
                                            </span>
                                        <?php endif; ?>
                                    </label>

                                    <!-- FR-14: confidence of this match -->
                                    <span class="conf-badge <?= e($band['class']) ?>">
                                        <?= e(round($suggestion['confidence'])) ?>%
                                        <span class="d-block conf-badge-label"><?= e($band['label']) ?></span>
                                    </span>

                                    <label for="qty<?= e($medicineId) ?>" class="visually-hidden">
                                        Quantity of <?= e($suggestion['name']) ?>
                                    </label>
                                    <input type="number" id="qty<?= e($medicineId) ?>" name="quantity[<?= e($medicineId) ?>]"
                                           class="form-control quantity-input" value="1" step="1"
                                           min="1" max="<?= e(max(1, $suggestion['available'])) ?>"
                                           <?= $inStock ? '' : 'disabled' ?>>
                                </div>
                            <?php endforeach; ?>

                            <button type="submit" class="btn btn-primary btn-lg w-100 mt-3">
                                <i class="bi bi-cart-plus" aria-hidden="true"></i> Add ticked medicines to cart
                            </button>

                            <p class="form-hint text-center mt-2 mb-0">
                                You can still change quantities or remove items in your cart.
                            </p>
                        </form>

                    <?php endif; ?>
                </div>

                <!-- ============ Names we could not match (FR-14) ============ -->
                <?php if (!empty($prescription['not_found'])): ?>
                    <div class="settings-card">
                        <h2 class="h5 mb-1">Not found in our catalogue</h2>
                        <p class="text-body-secondary small mb-3">
                            These lines look like medicines, but we could not match them.
                            Try searching by name, or ask us to stock them.
                        </p>

                        <ul class="rx-missing">
                            <?php foreach ($prescription['not_found'] as $line): ?>
                                <?php
                                // Search using the first word, which is usually the medicine name.
                                $firstWord = strtok((string) $line, ' ');
                                ?>
                                <li>
                                    <span>&ldquo;<?= e($line) ?>&rdquo;</span>
                                    <a class="btn btn-soft btn-sm"
                                       href="<?= e(url('catalogue.php?search=' . urlencode((string) $firstWord))) ?>">
                                        <i class="bi bi-search" aria-hidden="true"></i> Search
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        </div>

    </div>
</section>