<?php
/**
 * cart.php - shopping cart view
 * Controller: customer/cart.php
 *
 * Variables:
 *   $items        cart lines, each with an extra "available" count
 *   $total        the cart total at today's prices
 *   $hasProblems  true when at least one line cannot be ordered as it is
 */

$itemCount = count($items);
?>
<section class="page-head">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h1 class="h2 mb-1">My cart</h1>
            <p class="text-body-secondary mb-0">
                <?php if ($itemCount === 0): ?>
                    Your cart is empty.
                <?php else: ?>
                    <?= e($itemCount) ?> <?= $itemCount === 1 ? 'medicine' : 'medicines' ?> in your cart
                <?php endif; ?>
            </p>
        </div>

        <?php if ($itemCount > 0): ?>
            <!-- Clear the whole cart (FR-24) -->
            <form action="<?= e(url('customer/cart_action.php')) ?>" method="post">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="clear">
                <input type="hidden" name="return_to" value="customer/cart.php">
                <button type="submit" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-trash" aria-hidden="true"></i> Clear cart
                </button>
            </form>
        <?php endif; ?>
    </div>
</section>

<section class="container py-4 py-lg-5">

    <?php if ($itemCount === 0): ?>

        <!-- Empty cart -->
        <div class="empty-state">
            <span class="empty-icon" aria-hidden="true"><i class="bi bi-cart3"></i></span>
            <h2 class="h4">Your cart is empty</h2>
            <p class="text-body-secondary">
                Browse the catalogue, or upload a prescription and we'll add the medicines for you.
            </p>
            <div class="d-flex flex-wrap justify-content-center gap-2">
                <a class="btn btn-primary" href="<?= e(url('catalogue.php')) ?>">
                    <i class="bi bi-search" aria-hidden="true"></i> Browse medicines
                </a>
                <a class="btn btn-soft" href="<?= e(url('customer/prescription_upload.php')) ?>">
                    <i class="bi bi-file-earmark-arrow-up" aria-hidden="true"></i> Upload prescription
                </a>
            </div>
        </div>

    <?php else: ?>

        <?php if ($hasProblems): ?>
            <div class="alert alert-warning d-flex gap-2" role="alert">
                <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                <span>
                    Some medicines in your cart are no longer available in the quantity you asked for.
                    Please update or remove them before checking out.
                </span>
            </div>
        <?php endif; ?>

        <div class="row g-4">

            <!-- ============ Cart lines ============ -->
            <div class="col-lg-8">
                <?php foreach ($items as $item): ?>
                    <?php
                    $available = (int) $item['available'];
                    $quantity = (int) $item['quantity'];
                    $isUnavailable = ((int) $item['is_active'] !== 1 || $available === 0);
                    $isTooMany = (!$isUnavailable && $available < $quantity);
                    $tone = ((int) $item['medicine_id'] % 4) + 1;
                    ?>
                    <div class="cart-line<?= ($isUnavailable || $isTooMany) ? ' has-problem' : '' ?>">

                        <span class="cart-thumb med-tone-<?= e($tone) ?>" aria-hidden="true">
                            <i class="bi bi-capsule"></i>
                        </span>

                        <div class="cart-info">
                            <h2 class="cart-name">
                                <a href="<?= e(url('medicine.php?id=' . (int) $item['medicine_id'])) ?>">
                                    <?= e($item['name']) ?>
                                </a>
                            </h2>
                            <p class="cart-meta mb-0">
                                <?= e($item['category']) ?>, <?= e($item['manufacturer']) ?>
                                &middot; <?= e(formatPrice((float) $item['price'])) ?> each
                            </p>

                            <?php if ($isUnavailable): ?>
                                <p class="cart-warning mb-0">
                                    <i class="bi bi-x-circle" aria-hidden="true"></i>
                                    Out of stock. Please remove this item.
                                </p>
                            <?php elseif ($isTooMany): ?>
                                <p class="cart-warning mb-0">
                                    <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
                                    Only <?= e($available) ?> available. Please lower the quantity.
                                </p>
                            <?php endif; ?>
                        </div>

                        <!-- Change the quantity (FR-23) -->
                        <form action="<?= e(url('customer/cart_action.php')) ?>" method="post" class="cart-qty">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="medicine_id" value="<?= e($item['medicine_id']) ?>">
                            <input type="hidden" name="return_to" value="customer/cart.php">

                            <label for="qty<?= e($item['medicine_id']) ?>" class="visually-hidden">
                                Quantity of <?= e($item['name']) ?>
                            </label>
                            <input type="number" id="qty<?= e($item['medicine_id']) ?>" name="quantity"
                                   class="form-control quantity-input" value="<?= e($quantity) ?>"
                                   min="1" max="<?= e(max(1, $available)) ?>" step="1"
                                   <?= $isUnavailable ? 'disabled' : '' ?>>

                            <button type="submit" class="btn btn-soft btn-sm" <?= $isUnavailable ? 'disabled' : '' ?>>
                                Update
                            </button>
                        </form>

                        <div class="cart-right">
                            <span class="cart-subtotal"><?= e(formatPrice((float) $item['subtotal'])) ?></span>

                            <!-- Remove one item (FR-24) -->
                            <form action="<?= e(url('customer/cart_action.php')) ?>" method="post">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="medicine_id" value="<?= e($item['medicine_id']) ?>">
                                <input type="hidden" name="return_to" value="customer/cart.php">
                                <button type="submit" class="btn-remove" aria-label="Remove <?= e($item['name']) ?>">
                                    <i class="bi bi-trash" aria-hidden="true"></i> Remove
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>

                <a class="see-all mt-3 d-inline-flex" href="<?= e(url('catalogue.php')) ?>">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i> Continue shopping
                </a>
            </div>

            <!-- ============ Summary ============ -->
            <div class="col-lg-4">
                <div class="summary-card">
                    <h2 class="h5 mb-3">Order summary</h2>

                    <dl class="summary-list">
                        <div>
                            <dt>Items</dt>
                            <dd><?= e($itemCount) ?></dd>
                        </div>
                        <div>
                            <dt>Delivery</dt>
                            <dd>Free</dd>
                        </div>
                        <div>
                            <dt>Payment</dt>
                            <dd>Cash on delivery</dd>
                        </div>
                    </dl>

                    <div class="summary-total">
                        <span>Total</span>
                        <span><?= e(formatPrice((float) $total)) ?></span>
                    </div>

                    <?php if ($hasProblems): ?>
                        <button type="button" class="btn btn-primary btn-lg w-100 mt-3" disabled>
                            Checkout
                        </button>
                        <p class="form-hint text-center mt-2 mb-0">
                            Fix the highlighted items first.
                        </p>
                    <?php else: ?>
                        <a class="btn btn-primary btn-lg w-100 mt-3" href="<?= e(url('customer/checkout.php')) ?>">
                            Proceed to checkout <i class="bi bi-arrow-right" aria-hidden="true"></i>
                        </a>
                    <?php endif; ?>

                    <p class="summary-note mb-0">
                        <i class="bi bi-cash-coin" aria-hidden="true"></i>
                        You pay in cash when your order is delivered.
                    </p>
                </div>
            </div>

        </div>

    <?php endif; ?>
</section>