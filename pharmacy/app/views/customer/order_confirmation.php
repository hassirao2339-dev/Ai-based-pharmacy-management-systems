<?php
/**
 * order_confirmation.php - order confirmation view
 * Controller: customer/order_confirmation.php
 *
 * Variables:
 *   $order  the order row
 *   $items  the order lines
 */

$orderNumber = '#' . str_pad((string) $order['order_id'], 4, '0', STR_PAD_LEFT);
?>
<section class="container py-5">
    <div class="confirm-card mx-auto">

        <div class="confirm-head">
            <span class="confirm-tick" aria-hidden="true"><i class="bi bi-check-lg"></i></span>
            <h1 class="h2 mb-2">Thank you, your order is placed</h1>
            <p class="text-body-secondary mb-0">
                The pharmacy has received your order and will confirm it shortly.
            </p>
        </div>

        <!-- FR-28: unique Order ID -->
        <div class="confirm-id">
            <span class="text-body-secondary">Order ID</span>
            <strong><?= e($orderNumber) ?></strong>
        </div>

        <!-- FR-28: itemised order list -->
        <h2 class="h5 mt-4 mb-3">What you ordered</h2>
        <ul class="order-lines">
            <?php foreach ($items as $item): ?>
                <li>
                    <span class="order-line-qty"><?= e($item['quantity']) ?> &times;</span>
                    <span class="order-line-name">
                        <?= e($item['name']) ?>
                        <span class="d-block text-body-secondary small">
                            <?= e(formatPrice((float) $item['unit_price'])) ?> each
                        </span>
                    </span>
                    <span class="order-line-price"><?= e(formatPrice((float) $item['subtotal'])) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>

        <!-- FR-28: total amount -->
        <div class="summary-total">
            <span>Total to pay on delivery</span>
            <span><?= e(formatPrice((float) $order['total_amount'])) ?></span>
        </div>

        <!-- FR-28: delivery note -->
        <div class="confirm-note">
            <h2 class="h6 mb-2">
                <i class="bi bi-truck" aria-hidden="true"></i> Delivery
            </h2>
            <p class="mb-2"><?= e($order['delivery_address']) ?></p>
            <p class="mb-0 text-body-secondary small">
                Please keep <?= e(formatPrice((float) $order['total_amount'])) ?> in cash ready.
                The rider will call you before arriving. Your order is
                <span class="status status-pending">Pending</span> until the pharmacy confirms it,
                and you can still edit or cancel it until then.
            </p>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-4">
            <a class="btn btn-primary" href="<?= e(url('customer/orders.php')) ?>">
                <i class="bi bi-receipt" aria-hidden="true"></i> View my orders
            </a>
            <a class="btn btn-soft" href="<?= e(url('catalogue.php')) ?>">
                Continue shopping
            </a>
        </div>

    </div>
</section>