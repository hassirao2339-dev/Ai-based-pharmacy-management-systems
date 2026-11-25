<?php
/**
 * dashboard.php - administrator dashboard view
 * Controller: admin/dashboard.php
 *
 * Variables:
 *   $user               the logged-in admin
 *   $metrics            the numbers for the summary panel (FR-57)
 *   $latestOrders       the newest orders
 *   $lowStockMedicines  medicines that need restocking
 */
?>
<p class="text-body-secondary mb-4">
    Welcome back, <?= e($user['name']) ?>. Here is how the pharmacy is doing today.
</p>

<!-- ============ Summary panel (FR-57) ============ -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl">
        <div class="metric-card">
            <span class="metric-icon metric-icon-blue" aria-hidden="true"><i class="bi bi-receipt"></i></span>
            <span>
                <span class="metric-label">Orders today</span>
                <span class="metric-value"><?= e($metrics['today_orders']) ?></span>
            </span>
        </div>
    </div>

    <div class="col-6 col-xl">
        <div class="metric-card">
            <span class="metric-icon metric-icon-amber" aria-hidden="true"><i class="bi bi-hourglass-split"></i></span>
            <span>
                <span class="metric-label">Pending orders</span>
                <span class="metric-value"><?= e($metrics['pending_orders']) ?></span>
            </span>
        </div>
    </div>

    <div class="col-6 col-xl">
        <div class="metric-card">
            <span class="metric-icon metric-icon-green" aria-hidden="true"><i class="bi bi-cash-coin"></i></span>
            <span>
                <span class="metric-label">Revenue this month</span>
                <span class="metric-value"><?= e(formatPrice((float) $metrics['month_revenue'])) ?></span>
            </span>
        </div>
    </div>

    <div class="col-6 col-xl">
        <div class="metric-card">
            <span class="metric-icon metric-icon-red" aria-hidden="true"><i class="bi bi-bell"></i></span>
            <span>
                <span class="metric-label">Low-stock alerts</span>
                <span class="metric-value"><?= e($metrics['active_alerts']) ?></span>
            </span>
        </div>
    </div>

    <div class="col-12 col-xl">
        <div class="metric-card">
            <span class="metric-icon metric-icon-purple" aria-hidden="true"><i class="bi bi-people"></i></span>
            <span>
                <span class="metric-label">Registered customers</span>
                <span class="metric-value"><?= e($metrics['total_customers']) ?></span>
            </span>
        </div>
    </div>
</div>

<div class="row g-4">

    <!-- ============ Latest orders ============ -->
    <div class="col-xl-7">
        <div class="admin-card">
            <div class="admin-card-head">
                <h2 class="admin-card-title">Latest orders</h2>
                <a class="see-all" href="<?= e(url('admin/orders.php')) ?>">
                    Manage orders <i class="bi bi-arrow-right" aria-hidden="true"></i>
                </a>
            </div>

            <?php if (empty($latestOrders)): ?>
                <p class="text-body-secondary mb-0">No orders have been placed yet.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table admin-table">
                        <thead>
                            <tr>
                                <th scope="col">Order</th>
                                <th scope="col">Customer</th>
                                <th scope="col">Date</th>
                                <th scope="col">Total</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($latestOrders as $order): ?>
                                <?php $orderId = (int) $order['order_id']; ?>
                                <tr>
                                    <td>
                                        <a href="<?= e(url('admin/order_details.php?id=' . $orderId)) ?>">
                                            #<?= e(str_pad((string) $orderId, 4, '0', STR_PAD_LEFT)) ?>
                                        </a>
                                    </td>
                                    <td><?= e($order['customer_name']) ?></td>
                                    <td class="text-nowrap"><?= e(date('j M, g:i a', strtotime($order['order_date']))) ?></td>
                                    <td class="fw-bold"><?= e(formatPrice((float) $order['total_amount'])) ?></td>
                                    <td><?= statusBadge($order['status']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ============ Medicines to restock (FR-42) ============ -->
    <div class="col-xl-5">
        <div class="admin-card">
            <div class="admin-card-head">
                <h2 class="admin-card-title">Needs restocking</h2>
                <a class="see-all" href="<?= e(url('admin/medicines.php')) ?>">
                    All medicines <i class="bi bi-arrow-right" aria-hidden="true"></i>
                </a>
            </div>

            <?php if (empty($lowStockMedicines)): ?>
                <p class="text-body-secondary mb-0">
                    <i class="bi bi-check-circle text-success" aria-hidden="true"></i>
                    Every medicine is above its reorder threshold.
                </p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table admin-table">
                        <thead>
                            <tr>
                                <th scope="col">Medicine</th>
                                <th scope="col">Stock</th>
                                <th scope="col">Threshold</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lowStockMedicines as $medicine): ?>
                                <tr class="row-low">
                                    <td>
                                        <a href="<?= e(url('admin/medicine_form.php?id=' . (int) $medicine['medicine_id'])) ?>">
                                            <?= e($medicine['name']) ?>
                                        </a>
                                        <span class="d-block text-body-secondary small"><?= e($medicine['sku']) ?></span>
                                    </td>
                                    <td class="fw-bold"><?= e($medicine['stock_qty']) ?></td>
                                    <td><?= e($medicine['reorder_threshold']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

    <!-- ============ Stock expiring soon (FR-43) ============ -->
    <div class="col-12">
        <div class="admin-card">
            <div class="admin-card-head">
                <h2 class="admin-card-title">
                    Expiring within <?= e(EXPIRY_WARNING_DAYS) ?> days
                </h2>
                <a class="see-all" href="<?= e(url('admin/expiry.php')) ?>">
                    All expiry warnings <i class="bi bi-arrow-right" aria-hidden="true"></i>
                </a>
            </div>

            <?php if (empty($expiringBatches)): ?>
                <p class="text-body-secondary mb-0">
                    <i class="bi bi-check-circle text-success" aria-hidden="true"></i>
                    No batch expires in the next <?= e(EXPIRY_WARNING_DAYS) ?> days.
                </p>
            <?php else: ?>
                <p class="text-body-secondary small mb-3">
                    <?= e($expiringTotal) ?>
                    <?= $expiringTotal === 1 ? 'batch needs' : 'batches need' ?> attention.
                    FIFO sells the oldest stock first, so this is what leaves the shelf next.
                </p>

                <div class="table-responsive">
                    <table class="table admin-table">
                        <thead>
                            <tr>
                                <th scope="col">Medicine</th>
                                <th scope="col">Batch</th>
                                <th scope="col">Units</th>
                                <th scope="col">Expires</th>
                                <th scope="col">Days left</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($expiringBatches as $batch): ?>
                                <?php $daysLeft = (int) $batch['days_to_expiry']; ?>
                                <tr class="<?= $daysLeft <= 7 ? 'row-expiring' : 'row-low' ?>">
                                    <td>
                                        <a href="<?= e(url('admin/medicine_form.php?id=' . (int) $batch['medicine_id'])) ?>">
                                            <?= e($batch['medicine_name']) ?>
                                        </a>
                                        <span class="d-block text-body-secondary small"><?= e($batch['sku']) ?></span>
                                    </td>
                                    <td><span class="fifo-pill">#<?= e($batch['fifo_order']) ?></span></td>
                                    <td class="fw-bold"><?= e($batch['quantity']) ?></td>
                                    <td class="text-nowrap"><?= e(date('j M Y', strtotime($batch['expiry_date']))) ?></td>
                                    <td class="fw-bold">
                                        <?php if ($daysLeft <= 0): ?>
                                            <span class="text-danger">Expired</span>
                                        <?php else: ?>
                                            <?= e($daysLeft) ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
</div>