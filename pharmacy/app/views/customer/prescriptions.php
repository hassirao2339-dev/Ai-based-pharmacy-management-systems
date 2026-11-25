<?php
/**
 * prescriptions.php - prescription history view
 * Controller: customer/prescriptions.php
 *
 * Variables:
 *   $prescriptions  every prescription of this customer, newest first
 */

$count = count($prescriptions);
?>
<section class="page-head">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h1 class="h2 mb-1">My prescriptions</h1>
            <p class="text-body-secondary mb-0">
                <?php if ($count === 0): ?>
                    You have not uploaded a prescription yet.
                <?php else: ?>
                    <?= e($count) ?> <?= $count === 1 ? 'prescription' : 'prescriptions' ?>, newest first.
                <?php endif; ?>
            </p>
        </div>

        <a class="btn btn-primary" href="<?= e(url('customer/prescription_upload.php')) ?>">
            <i class="bi bi-file-earmark-arrow-up" aria-hidden="true"></i> Upload a new one
        </a>
    </div>
</section>

<section class="container py-4 py-lg-5">

    <?php if ($count === 0): ?>

        <div class="empty-state">
            <span class="empty-icon" aria-hidden="true"><i class="bi bi-file-earmark-medical"></i></span>
            <h2 class="h4">No prescriptions yet</h2>
            <p class="text-body-secondary">
                Upload a photo of your prescription and we will find the medicines for you.
            </p>
            <a class="btn btn-primary" href="<?= e(url('customer/prescription_upload.php')) ?>">Upload prescription</a>
        </div>

    <?php else: ?>

        <?php foreach ($prescriptions as $prescription): ?>
            <?php
            $prescriptionId = (int) $prescription['prescription_id'];
            $status = $prescription['status'];
            $suggestionCount = count($prescription['suggestions']);
            $addedCount = count($prescription['added']);
            $confidence = (float) ($prescription['ocr']['confidence'] ?? 0);
            ?>
            <article class="rx-row">
                <a class="rx-thumb" href="<?= e(url('customer/prescription_review.php?id=' . $prescriptionId)) ?>">
                    <img src="<?= e(url('customer/prescription_image.php?id=' . $prescriptionId)) ?>"
                         alt="Prescription uploaded on <?= e(date('j M Y', strtotime($prescription['uploaded_at']))) ?>"
                         loading="lazy">
                </a>

                <div class="rx-row-main">
                    <div class="order-head">
                        <h2 class="order-number">
                            <?= e(date('j M Y', strtotime($prescription['uploaded_at']))) ?>
                        </h2>

                        <?php if ($status === 'completed'): ?>
                            <span class="status status-delivered">
                                <i class="bi bi-check-circle" aria-hidden="true"></i> Read
                            </span>
                        <?php elseif ($status === 'failed'): ?>
                            <span class="status status-cancelled">
                                <i class="bi bi-x-circle" aria-hidden="true"></i> Could not read
                            </span>
                        <?php else: ?>
                            <span class="status status-pending">
                                <i class="bi bi-hourglass-split" aria-hidden="true"></i> Processing
                            </span>
                        <?php endif; ?>
                    </div>

                    <p class="order-meta mb-1">
                        <?= e(date('g:i a', strtotime($prescription['uploaded_at']))) ?>
                        <?php if ($status === 'completed'): ?>
                            &middot; reading quality <?= e(round($confidence)) ?>%
                            &middot; <?= e($suggestionCount) ?> <?= $suggestionCount === 1 ? 'match' : 'matches' ?>
                        <?php endif; ?>
                    </p>

                    <!-- FR-63: which medicines came from this prescription -->
                    <?php if ($addedCount > 0): ?>
                        <p class="rx-added mb-0">
                            <i class="bi bi-cart-check" aria-hidden="true"></i>
                            Added to cart: <?= e(implode(', ', $prescription['added'])) ?>
                        </p>
                    <?php elseif ($status === 'completed' && $suggestionCount > 0): ?>
                        <p class="rx-added text-body-secondary mb-0">Nothing added to the cart yet.</p>
                    <?php endif; ?>
                </div>

                <a class="btn btn-soft btn-sm ms-auto"
                   href="<?= e(url('customer/prescription_review.php?id=' . $prescriptionId)) ?>">
                    Open
                </a>
            </article>
        <?php endforeach; ?>

    <?php endif; ?>
</section>