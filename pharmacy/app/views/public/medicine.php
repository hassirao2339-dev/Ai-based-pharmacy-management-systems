<?php
/**
 * medicine.php - medicine details view
 * Controller: medicine.php
 *
 * Variables:
 *   $user              the logged-in user, or null for a guest
 *   $medicine          the medicine to show
 *   $relatedMedicines  other medicines in the same category
 */

$stock = (int) $medicine['stock_qty'];
$isOutOfStock = ($stock === 0);
$isLowStock = (!$isOutOfStock && $stock <= (int) $medicine['reorder_threshold']);

// The same soft colour the medicine cards use, so a card and its page match.
$tone = ((int) $medicine['medicine_id'] % 4) + 1;

// Admins manage medicines; only guests and customers see the Add button.
$showAddButton = ($user === null || $user['role'] === 'customer');

// FR-21 asks for the expiry MONTH, not the exact day. Stock with no usable
// batch has no expiry date at all.
$expiryMonth = 'Not in stock';

if ($medicine['expiry_date'] !== null) {
    $expiryMonth = date('F Y', strtotime($medicine['expiry_date']));
}
?>
<nav class="breadcrumb-bar" aria-label="Breadcrumb">
    <div class="container">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= e(url('index.php')) ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= e(url('catalogue.php')) ?>">Medicines</a></li>
            <li class="breadcrumb-item">
                <a href="<?= e(url('catalogue.php?category=' . urlencode($medicine['category']))) ?>">
                    <?= e($medicine['category']) ?>
                </a>
            </li>
            <li class="breadcrumb-item active" aria-current="page"><?= e($medicine['name']) ?></li>
        </ol>
    </div>
</nav>

<section class="container py-4 py-lg-5">
    <div class="row g-4 g-lg-5">

        <!-- ============ Picture area ============ -->
        <div class="col-lg-5">
            <div class="detail-media med-tone-<?= e($tone) ?>">
                <i class="bi bi-capsule" aria-hidden="true"></i>

                <?php if ($isOutOfStock): ?>
                    <span class="detail-flag flag-out">Out of stock</span>
                <?php elseif ($isLowStock): ?>
                    <span class="detail-flag flag-low">Only <?= e($stock) ?> left</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- ============ Details (FR-21) ============ -->
        <div class="col-lg-7">
            <span class="badge cat-badge mb-2"><?= e($medicine['category']) ?></span>

            <h1 class="detail-title"><?= e($medicine['name']) ?></h1>
            <p class="detail-maker mb-4">
                <i class="bi bi-building" aria-hidden="true"></i> <?= e($medicine['manufacturer']) ?>
            </p>

            <div class="detail-price-row">
                <span class="detail-price"><?= e(formatPrice((float) $medicine['price'])) ?></span>

                <?php if ($isOutOfStock): ?>
                    <span class="stock-line stock-out">
                        <i class="bi bi-x-circle" aria-hidden="true"></i> Out of stock
                    </span>
                <?php elseif ($isLowStock): ?>
                    <span class="stock-line stock-low">
                        <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
                        Only <?= e($stock) ?> left in stock
                    </span>
                <?php else: ?>
                    <span class="stock-line stock-in">
                        <i class="bi bi-check-circle" aria-hidden="true"></i>
                        In stock (<?= e($stock) ?> available)
                    </span>
                <?php endif; ?>
            </div>

            <!-- Add to cart (FR-22: disabled when there is no stock) -->
            <div class="mb-4">
                <?php if ($isOutOfStock): ?>
                    <button type="button" class="btn btn-lg btn-out-of-stock" disabled>
                        <i class="bi bi-slash-circle" aria-hidden="true"></i> Out of stock
                    </button>
                    <p class="form-hint mt-2 mb-0">
                        This medicine is being restocked. Please check again in a few days.
                    </p>
                <?php elseif ($showAddButton): ?>
                    <!-- The cart handler (customer/cart_action.php) is built in Phase 5. -->
                    <form action="<?= e(url('customer/cart_action.php')) ?>" method="post"
                          class="d-flex flex-wrap align-items-end gap-3">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="medicine_id" value="<?= e($medicine['medicine_id']) ?>">

                        <div>
                            <label for="quantity" class="form-label">Quantity</label>
                            <input type="number" id="quantity" name="quantity" class="form-control quantity-input"
                                   value="1" min="1" max="<?= e($stock) ?>" step="1">
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-cart-plus" aria-hidden="true"></i> Add to cart
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- Description -->
            <h2 class="h5">About this medicine</h2>
            <p class="detail-description"><?= e($medicine['description']) ?></p>

            <!-- The facts FR-21 asks for -->
            <h2 class="h5 mt-4">Product details</h2>
            <dl class="detail-facts">
                <div>
                    <dt>SKU</dt>
                    <dd><?= e($medicine['sku']) ?></dd>
                </div>
                <div>
                    <dt>DRAP category</dt>
                    <dd><?= e($medicine['drap_category']) ?></dd>
                </div>
                <div>
                    <dt>Manufacturer</dt>
                    <dd><?= e($medicine['manufacturer']) ?></dd>
                </div>
                <div>
                    <dt>Category</dt>
                    <dd><?= e($medicine['category']) ?></dd>
                </div>
                <div>
                    <dt>Stock quantity</dt>
                    <dd><?= e($stock) ?></dd>
                </div>
                <div>
                    <dt>Expires</dt>
                    <dd><?= e($expiryMonth) ?></dd>
                </div>
            </dl>

            <p class="detail-note">
                <i class="bi bi-info-circle" aria-hidden="true"></i>
                Always follow your doctor's instructions. Medicines marked
                &ldquo;Prescription Only&rdquo; are dispensed against a valid prescription.
            </p>
        </div>
    </div>
</section>

<!-- ============ Related medicines ============ -->
<?php if (!empty($relatedMedicines)): ?>
    <section class="home-section pt-0">
        <div class="container">
            <div class="section-head">
                <div>
                    <h2 class="section-title">
                        <span class="section-title-icon" aria-hidden="true"><i class="bi bi-grid"></i></span>
                        More in <?= e($medicine['category']) ?>
                    </h2>
                </div>
                <a class="see-all" href="<?= e(url('catalogue.php?category=' . urlencode($medicine['category']))) ?>">
                    See all <i class="bi bi-arrow-right" aria-hidden="true"></i>
                </a>
            </div>

            <div class="row g-3 g-lg-4">
                <?php foreach ($relatedMedicines as $medicine): ?>
                    <div class="col-sm-6 col-lg-3">
                        <?php
                        $medicineRank = null;
                        require APP_PATH . '/views/partials/medicine_card.php';
                        ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>