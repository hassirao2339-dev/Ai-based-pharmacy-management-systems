<?php
/**
 * dashboard.php - customer dashboard view
 * Controller: customer/dashboard.php
 *
 * Variables:
 *   $user          the logged-in customer
 *   $recentOrders  their three most recent orders
 *   $cartCount     how many medicines are in their cart
 *
 * Phase 11 adds a recent prescriptions section here.
 */
?>
<section class="dashboard-hero">
    <span class="hero-glow hero-glow-blue" aria-hidden="true"></span>
    <span class="hero-glow hero-glow-purple" aria-hidden="true"></span>

    <div class="container">
        <span class="hero-badge">
            <span class="hero-badge-dot" aria-hidden="true"></span>
            Customer account
        </span>
        <h1 class="dashboard-title">Welcome, <?= e($user['name']) ?></h1>
        <p class="mb-0">What would you like to do today?</p>
    </div>
</section>

<section class="container py-5">

    <!-- ============ Quick links ============ -->
    <div class="row g-3 g-lg-4">
        <div class="col-sm-6 col-lg-3">
            <a class="quick-link" href="<?= e(url('customer/prescription_upload.php')) ?>">
                <span class="quick-link-icon" aria-hidden="true"><i class="bi bi-file-earmark-arrow-up"></i></span>
                <span>
                    <span class="quick-link-title">Upload prescription</span>
                    <span class="quick-link-text">We find the medicines for you</span>
                </span>
            </a>
        </div>
        <div class="col-sm-6 col-lg-3">
            <a class="quick-link" href="<?= e(url('catalogue.php')) ?>">
                <span class="quick-link-icon" aria-hidden="true"><i class="bi bi-search"></i></span>
                <span>
                    <span class="quick-link-title">Browse medicines</span>
                    <span class="quick-link-text">Search the catalogue</span>
                </span>
            </a>
        </div>
        <div class="col-sm-6 col-lg-3">
            <a class="quick-link" href="<?= e(url('customer/cart.php')) ?>">
                <span class="quick-link-icon" aria-hidden="true"><i class="bi bi-cart3"></i></span>
                <span>
                    <span class="quick-link-title">My cart</span>
                    <span class="quick-link-text">
                        <?php if ($cartCount === 0): ?>
                            Your cart is empty
                        <?php else: ?>
                            <?= e($cartCount) ?> <?= $cartCount === 1 ? 'medicine' : 'medicines' ?> waiting
                        <?php endif; ?>
                    </span>
                </span>
            </a>
        </div>
        <div class="col-sm-6 col-lg-3">
            <a class="quick-link" href="<?= e(url('customer/profile.php')) ?>">
                <span class="quick-link-icon" aria-hidden="true"><i class="bi bi-person-gear"></i></span>
                <span>
                    <span class="quick-link-title">My profile</span>
                    <span class="quick-link-text">Address and password</span>
                </span>
            </a>
        </div>
    </div>

    <!-- ============ Recent orders (FR-30) ============ -->
    <div class="dashboard-section">
        <div class="dashboard-head">
            <h2 class="section-title mb-0">
                <span class="section-title-icon" aria-hidden="true"><i class="bi bi-receipt"></i></span>
                Recent orders
            </h2>

            <?php if (!empty($recentOrders)): ?>
                <a class="see-all" href="<?= e(url('customer/orders.php')) ?>">
                    See all orders <i class="bi bi-arrow-right" aria-hidden="true"></i>
                </a>
            <?php endif; ?>
        </div>

        <?php if (empty($recentOrders)): ?>

            <div class="empty-state">
                <span class="empty-icon" aria-hidden="true"><i class="bi bi-receipt"></i></span>
                <h3 class="h5">No orders yet</h3>
                <p class="text-body-secondary">
                    Your orders will appear here once you place one.
                </p>
                <a class="btn btn-primary" href="<?= e(url('catalogue.php')) ?>">Browse medicines</a>
            </div>

        <?php else: ?>

            <?php foreach ($recentOrders as $order): ?>
                <?php
                $orderId = (int) $order['order_id'];
                $orderNumber = '#' . str_pad((string) $orderId, 4, '0', STR_PAD_LEFT);
                $itemCount = (int) $order['item_count'];
                ?>
                <a class="mini-order" href="<?= e(url('customer/order_details.php?id=' . $orderId)) ?>">
                    <span class="mini-order-number"><?= e($orderNumber) ?></span>
                    <?= statusBadge($order['status']) ?>
                    <span class="mini-order-meta">
                        <?= e(date('j M Y', strtotime($order['order_date']))) ?>
                        &middot; <?= e($itemCount) ?> <?= $itemCount === 1 ? 'item' : 'items' ?>
                    </span>
                    <span class="mini-order-total"><?= e(formatPrice((float) $order['total_amount'])) ?></span>
                </a>
            <?php endforeach; ?>

        <?php endif; ?>
    </div>


    <!-- ============ Recent prescriptions (FR-17) ============ -->
    <div class="dashboard-section">
        <div class="dashboard-head">
            <h2 class="section-title mb-0">
                <span class="section-title-icon" aria-hidden="true"><i class="bi bi-file-earmark-medical"></i></span>
                Recent prescriptions
            </h2>

            <?php if (!empty($recentPrescriptions)): ?>
                <a class="see-all" href="<?= e(url('customer/prescriptions.php')) ?>">
                    See all prescriptions <i class="bi bi-arrow-right" aria-hidden="true"></i>
                </a>
            <?php endif; ?>
        </div>

        <?php if (empty($recentPrescriptions)): ?>

            <div class="empty-state">
                <span class="empty-icon" aria-hidden="true"><i class="bi bi-file-earmark-medical"></i></span>
                <h3 class="h5">No prescriptions yet</h3>
                <p class="text-body-secondary">
                    Upload a photo of your prescription and we will find the medicines for you.
                </p>
                <a class="btn btn-primary" href="<?= e(url('customer/prescription_upload.php')) ?>">Upload prescription</a>
            </div>

        <?php else: ?>

            <?php foreach ($recentPrescriptions as $prescription): ?>
                <?php
                $prescriptionId = (int) $prescription['prescription_id'];
                $matchCount = count($prescription['suggestions']);
                ?>
                <a class="mini-order" href="<?= e(url('customer/prescription_review.php?id=' . $prescriptionId)) ?>">
                    <span class="mini-order-number">
                        <?= e(date('j M Y', strtotime($prescription['uploaded_at']))) ?>
                    </span>

                    <?php if ($prescription['status'] === 'completed'): ?>
                        <span class="status status-delivered">
                            <i class="bi bi-check-circle" aria-hidden="true"></i> Read
                        </span>
                    <?php elseif ($prescription['status'] === 'failed'): ?>
                        <span class="status status-cancelled">
                            <i class="bi bi-x-circle" aria-hidden="true"></i> Could not read
                        </span>
                    <?php else: ?>
                        <span class="status status-pending">
                            <i class="bi bi-hourglass-split" aria-hidden="true"></i> Processing
                        </span>
                    <?php endif; ?>

                    <span class="mini-order-meta">
                        <?= e($matchCount) ?> <?= $matchCount === 1 ? 'medicine matched' : 'medicines matched' ?>
                    </span>
                    <span class="mini-order-total">Open <i class="bi bi-arrow-right" aria-hidden="true"></i></span>
                </a>
            <?php endforeach; ?>

        <?php endif; ?>
    </div>

</section>