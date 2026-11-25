<?php
/**
 * orders.php - order history view
 * Controller: customer/orders.php
 *
 * Variables:
 *   $orders  every order of this customer, newest first
 */

$orderCount = count($orders);
?>
<section class="page-head">
    <div class="container">
        <h1 class="h2 mb-1">My orders</h1>
        <p class="text-body-secondary mb-0">
            <?php if ($orderCount === 0): ?>
                You have not placed any orders yet.
            <?php else: ?>
                <?= e($orderCount) ?> <?= $orderCount === 1 ? 'order' : 'orders' ?>.
                You can edit or cancel an order while it is still pending.
            <?php endif; ?>
        </p>
    </div>
</section>

<section class="container py-4 py-lg-5">

    <?php if ($orderCount === 0): ?>

        <div class="empty-state">
            <span class="empty-icon" aria-hidden="true"><i class="bi bi-receipt"></i></span>
            <h2 class="h4">No orders yet</h2>
            <p class="text-body-secondary">
                When you place an order, it will appear here with its status.
            </p>
            <a class="btn btn-primary" href="<?= e(url('catalogue.php')) ?>">
                <i class="bi bi-search" aria-hidden="true"></i> Browse medicines
            </a>
        </div>

    <?php else: ?>

        <?php foreach ($orders as $order): ?>
            <?php
            $orderId = (int) $order['order_id'];
            $orderNumber = '#' . str_pad((string) $orderId, 4, '0', STR_PAD_LEFT);
            $itemCount = (int) $order['item_count'];
            $isPending = ($order['status'] === 'pending');
            ?>
            <article class="order-row">

                <div class="order-main">
                    <div class="order-head">
                        <h2 class="order-number"><?= e($orderNumber) ?></h2>
                        <?= statusBadge($order['status']) ?>
                    </div>

                    <p class="order-meta mb-0">
                        <i class="bi bi-calendar3" aria-hidden="true"></i>
                        <?= e(date('j M Y, g:i a', strtotime($order['order_date']))) ?>
                        &middot;
                        <?= e($itemCount) ?> <?= $itemCount === 1 ? 'item' : 'items' ?>
                        &middot;
                        Cash on delivery
                    </p>

                    <?php if ($order['status'] === 'cancelled' && !empty($order['cancel_reason'])): ?>
                        <p class="order-cancel-reason mb-0">
                            <i class="bi bi-info-circle" aria-hidden="true"></i>
                            Reason: <?= e($order['cancel_reason']) ?>
                        </p>
                    <?php endif; ?>
                </div>

                <div class="order-side">
                    <span class="order-total"><?= e(formatPrice((float) $order['total_amount'])) ?></span>

                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-soft btn-sm" href="<?= e(url('customer/order_details.php?id=' . $orderId)) ?>">
                            View
                        </a>

                        <?php if ($isPending): ?>
                            <!-- FR-31 and FR-33: only a pending order can be changed -->
                            <a class="btn btn-outline-primary btn-sm" href="<?= e(url('customer/order_edit.php?id=' . $orderId)) ?>">
                                Edit
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

            </article>
        <?php endforeach; ?>

    <?php endif; ?>
</section>