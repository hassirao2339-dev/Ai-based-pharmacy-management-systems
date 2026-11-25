<?php
/**
 * order_edit.php - edit a pending order view
 * Controller: customer/order_edit.php
 *
 * Variables:
 *   $order  the pending order
 *   $items  its lines, each with an extra max_quantity
 */

$orderId = (int) $order['order_id'];
$orderNumber = '#' . str_pad((string) $orderId, 4, '0', STR_PAD_LEFT);
$total = 0.0;

foreach ($items as $item) {
    $total += (float) $item['subtotal'];
}
?>
<nav class="breadcrumb-bar" aria-label="Breadcrumb">
    <div class="container">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= e(url('customer/orders.php')) ?>">My orders</a></li>
            <li class="breadcrumb-item">
                <a href="<?= e(url('customer/order_details.php?id=' . $orderId)) ?>">Order <?= e($orderNumber) ?></a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">Edit</li>
        </ol>
    </div>
</nav>

<section class="page-head">
    <div class="container">
        <div class="order-head">
            <h1 class="h2 mb-0">Edit order <?= e($orderNumber) ?></h1>
            <?= statusBadge($order['status']) ?>
        </div>
        <p class="text-body-secondary mb-0 mt-1">
            You can change quantities or remove items while the order is pending.
        </p>
    </div>
</section>

<section class="container py-4 py-lg-5">
    <div class="row g-4">

        <!-- ============ The items ============ -->
        <div class="col-lg-8">
            <?php foreach ($items as $item): ?>
                <?php $tone = ((int) $item['medicine_id'] % 4) + 1; ?>
                <div class="cart-line">

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
                            <?= e(formatPrice((float) $item['unit_price'])) ?> each
                            &middot; up to <?= e($item['max_quantity']) ?> available
                        </p>
                    </div>

                    <!-- Change the quantity (FR-31) -->
                    <form method="post" action="<?= e(url('customer/order_edit.php')) ?>" class="cart-qty">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="update_item">
                        <input type="hidden" name="order_id" value="<?= e($orderId) ?>">
                        <input type="hidden" name="item_id" value="<?= e($item['item_id']) ?>">

                        <label for="qty<?= e($item['item_id']) ?>" class="visually-hidden">
                            Quantity of <?= e($item['name']) ?>
                        </label>
                        <input type="number" id="qty<?= e($item['item_id']) ?>" name="quantity"
                               class="form-control quantity-input" value="<?= e($item['quantity']) ?>"
                               min="1" max="<?= e($item['max_quantity']) ?>" step="1">

                        <button type="submit" class="btn btn-soft btn-sm">Update</button>
                    </form>

                    <div class="cart-right">
                        <span class="cart-subtotal"><?= e(formatPrice((float) $item['subtotal'])) ?></span>

                        <!-- Remove this item (FR-31) -->
                        <form method="post" action="<?= e(url('customer/order_edit.php')) ?>">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="remove_item">
                            <input type="hidden" name="order_id" value="<?= e($orderId) ?>">
                            <input type="hidden" name="item_id" value="<?= e($item['item_id']) ?>">
                            <button type="submit" class="btn-remove" aria-label="Remove <?= e($item['name']) ?>">
                                <i class="bi bi-trash" aria-hidden="true"></i> Remove
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>

            <a class="see-all mt-3 d-inline-flex" href="<?= e(url('customer/order_details.php?id=' . $orderId)) ?>">
                <i class="bi bi-arrow-left" aria-hidden="true"></i> Back to order details
            </a>
        </div>

        <!-- ============ Summary and cancel ============ -->
        <div class="col-lg-4">
            <div class="summary-card">
                <h2 class="h5 mb-3">Updated total</h2>

                <dl class="summary-list">
                    <div>
                        <dt>Items</dt>
                        <dd><?= e(count($items)) ?></dd>
                    </div>
                    <div>
                        <dt>Payment</dt>
                        <dd>Cash on delivery</dd>
                    </div>
                </dl>

                <div class="summary-total">
                    <span>Total</span>
                    <span><?= e(formatPrice($total)) ?></span>
                </div>

                <a class="btn btn-primary w-100 mt-3" href="<?= e(url('customer/order_details.php?id=' . $orderId)) ?>">
                    Done editing
                </a>

                <hr class="my-4">

                <h3 class="h6 mb-2">Cancel this order</h3>
                <p class="text-body-secondary small">
                    Cancelling returns every medicine to our stock. This cannot be undone.
                </p>

                <!-- Cancel the order (FR-33). The JavaScript confirm is a
                     convenience; the server does the real work either way. -->
                <form method="post" action="<?= e(url('customer/order_edit.php')) ?>"
                      onsubmit="return confirm('Cancel this order? This cannot be undone.');">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="cancel">
                    <input type="hidden" name="order_id" value="<?= e($orderId) ?>">
                    <button type="submit" class="btn btn-outline-danger w-100">
                        <i class="bi bi-x-circle" aria-hidden="true"></i> Cancel order
                    </button>
                </form>
            </div>
        </div>

    </div>
</section>