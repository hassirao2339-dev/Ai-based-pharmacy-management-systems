<?php
/**
 * order_details.php - single order view
 * Controller: customer/order_details.php
 *
 * Variables:
 *   $order       the order row
 *   $items       the order lines
 *   $isEditable  true while the order is still pending (FR-31)
 */

$orderId = (int) $order['order_id'];
$orderNumber = '#' . str_pad((string) $orderId, 4, '0', STR_PAD_LEFT);

// The steps an order moves through. A cancelled order is shown differently,
// because it leaves this path.
$progressSteps = ['pending', 'confirmed', 'dispatched', 'delivered'];
$isCancelled = ($order['status'] === 'cancelled');
$currentStep = array_search($order['status'], $progressSteps, true);

if ($currentStep === false) {
    $currentStep = 0;
}
?>
<nav class="breadcrumb-bar" aria-label="Breadcrumb">
    <div class="container">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= e(url('customer/dashboard.php')) ?>">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= e(url('customer/orders.php')) ?>">My orders</a></li>
            <li class="breadcrumb-item active" aria-current="page">Order <?= e($orderNumber) ?></li>
        </ol>
    </div>
</nav>

<section class="page-head">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <div class="order-head">
                <h1 class="h2 mb-0">Order <?= e($orderNumber) ?></h1>
                <?= statusBadge($order['status']) ?>
            </div>
            <p class="text-body-secondary mb-0 mt-1">
                Placed on <?= e(date('j F Y \a\t g:i a', strtotime($order['order_date']))) ?>
            </p>
        </div>

        <?php if ($isEditable): ?>
            <a class="btn btn-primary" href="<?= e(url('customer/order_edit.php?id=' . $orderId)) ?>">
                <i class="bi bi-pencil" aria-hidden="true"></i> Edit or cancel
            </a>
        <?php endif; ?>
    </div>
</section>

<section class="container py-4 py-lg-5">
    <div class="row g-4">

        <!-- ============ Items and progress ============ -->
        <div class="col-lg-7">

            <!-- Where the order has reached (FR-32) -->
            <div class="settings-card mb-4">
                <h2 class="h5 mb-4">Order progress</h2>

                <?php if ($isCancelled): ?>
                    <div class="cancelled-note">
                        <i class="bi bi-x-circle" aria-hidden="true"></i>
                        <div>
                            <strong>This order was cancelled.</strong>
                            <?php if (!empty($order['cancel_reason'])): ?>
                                <span class="d-block text-body-secondary small">
                                    Reason: <?= e($order['cancel_reason']) ?>
                                </span>
                            <?php endif; ?>
                            <span class="d-block text-body-secondary small">
                                The medicines have been returned to our stock.
                            </span>
                        </div>
                    </div>
                <?php else: ?>
                    <ol class="track-steps">
                        <?php foreach ($progressSteps as $stepIndex => $stepName): ?>
                            <?php
                            $stepDetails = orderStatuses()[$stepName];

                            $stepClass = 'is-upcoming';

                            if ($stepIndex < $currentStep) {
                                $stepClass = 'is-done';
                            } elseif ($stepIndex === $currentStep) {
                                $stepClass = 'is-current';
                            }

                            $stepNotes = [
                                'pending' => 'Waiting for the pharmacy to confirm',
                                'confirmed' => 'The pharmacy is preparing your medicines',
                                'dispatched' => 'On the way to your address',
                                'delivered' => 'Pay in cash when you receive it',
                            ];
                            ?>
                            <li class="<?= e($stepClass) ?>">
                                <span class="track-dot" aria-hidden="true">
                                    <?php if ($stepIndex < $currentStep): ?>
                                        <i class="bi bi-check"></i>
                                    <?php endif; ?>
                                </span>
                                <div>
                                    <strong><?= e($stepDetails['label']) ?></strong>
                                    <span class="track-meta"><?= e($stepNotes[$stepName]) ?></span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>
            </div>

            <!-- The medicines in this order -->
            <div class="settings-card">
                <h2 class="h5 mb-3">Items</h2>

                <?php if (count($items) === 0): ?>
                    <p class="text-body-secondary mb-0">This order has no items left.</p>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                        <?php $tone = ((int) $item['medicine_id'] % 4) + 1; ?>
                        <div class="detail-item">
                            <span class="cart-thumb med-tone-<?= e($tone) ?>" aria-hidden="true">
                                <i class="bi bi-capsule"></i>
                            </span>

                            <div class="cart-info">
                                <h3 class="cart-name">
                                    <a href="<?= e(url('medicine.php?id=' . (int) $item['medicine_id'])) ?>">
                                        <?= e($item['name']) ?>
                                    </a>
                                </h3>
                                <p class="cart-meta mb-0">
                                    <?= e($item['quantity']) ?> &times;
                                    <?= e(formatPrice((float) $item['unit_price'])) ?>
                                    &middot; <?= e($item['sku']) ?>
                                </p>
                            </div>

                            <span class="cart-subtotal"><?= e(formatPrice((float) $item['subtotal'])) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- ============ Summary and delivery ============ -->
        <div class="col-lg-5">
            <div class="summary-card">
                <h2 class="h5 mb-3">Summary</h2>

                <dl class="summary-list">
                    <div>
                        <dt>Order ID</dt>
                        <dd><?= e($orderNumber) ?></dd>
                    </div>
                    <div>
                        <dt>Items</dt>
                        <dd><?= e(count($items)) ?></dd>
                    </div>
                    <div>
                        <dt>Payment</dt>
                        <dd><?= e($order['payment_method']) ?></dd>
                    </div>
                    <div>
                        <dt>Status</dt>
                        <dd><?= statusBadge($order['status']) ?></dd>
                    </div>
                </dl>

                <div class="summary-total">
                    <span><?= $order['status'] === 'delivered' ? 'Total paid' : 'Total' ?></span>
                    <span><?= e(formatPrice((float) $order['total_amount'])) ?></span>
                </div>

                <h3 class="h6 mt-4 mb-2">
                    <i class="bi bi-truck" aria-hidden="true"></i> Delivery address
                </h3>
                <p class="text-body-secondary mb-0"><?= e($order['delivery_address']) ?></p>

                <?php if ($isEditable): ?>
                    <p class="summary-note mb-0">
                        <i class="bi bi-pencil" aria-hidden="true"></i>
                        This order is still pending, so you can change the quantities or cancel it.
                    </p>
                <?php endif; ?>
            </div>

            <a class="see-all mt-3 d-inline-flex" href="<?= e(url('customer/orders.php')) ?>">
                <i class="bi bi-arrow-left" aria-hidden="true"></i> Back to my orders
            </a>
        </div>

    </div>
</section>