<?php
/**
 * orders.php - admin order list view
 * Controller: admin/orders.php
 *
 * Variables:
 *   $orders  every order, newest first
 */

// How many orders sit in each status, shown as a quick summary.
$statusCounts = [];

foreach (array_keys(orderStatuses()) as $statusName) {
    $statusCounts[$statusName] = 0;
}

foreach ($orders as $order) {
    if (isset($statusCounts[$order['status']])) {
        $statusCounts[$order['status']]++;
    }
}
?>
<div class="admin-card-head">
    <p class="text-body-secondary mb-0">
        <?= e(count($orders)) ?> orders in total. Open an order to change its status.
    </p>
</div>

<!-- A count for each status (FR-32 colours) -->
<div class="status-counts mb-4">
    <?php foreach (orderStatuses() as $statusName => $statusDetails): ?>
        <div class="status-count">
            <?= statusBadge($statusName) ?>
            <span class="status-count-value"><?= e($statusCounts[$statusName]) ?></span>
        </div>
    <?php endforeach; ?>
</div>

<div class="admin-card">
    <?php if (empty($orders)): ?>

        <div class="empty-state">
            <span class="empty-icon" aria-hidden="true"><i class="bi bi-receipt"></i></span>
            <h2 class="h5">No orders yet</h2>
            <p class="text-body-secondary mb-0">
                Customer orders will appear here as soon as they are placed.
            </p>
        </div>

    <?php else: ?>

        <div class="table-responsive">
            <table class="table admin-table js-datatable">
                <thead>
                    <tr>
                        <th scope="col">Order</th>
                        <th scope="col">Customer</th>
                        <th scope="col">Placed</th>
                        <th scope="col">Items</th>
                        <th scope="col">Total</th>
                        <th scope="col">Status</th>
                        <th scope="col" data-dt-order="disable" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <?php
                        $orderId = (int) $order['order_id'];
                        $isNew = ((int) $order['notified_admin'] === 0);
                        ?>
                        <tr>
                            <td>
                                <a href="<?= e(url('admin/order_details.php?id=' . $orderId)) ?>">
                                    #<?= e(str_pad((string) $orderId, 4, '0', STR_PAD_LEFT)) ?>
                                </a>
                                <?php if ($isNew): ?>
                                    <span class="admin-nav-badge">new</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?= e($order['customer_name']) ?>
                                <span class="d-block text-body-secondary small"><?= e($order['customer_phone']) ?></span>
                            </td>

                            <td class="text-nowrap"><?= e(date('j M Y, g:i a', strtotime($order['order_date']))) ?></td>

                            <td><?= e($order['item_count']) ?></td>

                            <td class="fw-bold text-nowrap"><?= e(formatPrice((float) $order['total_amount'])) ?></td>

                            <td><?= statusBadge($order['status']) ?></td>

                            <td>
                                <div class="table-actions">
                                    <a class="btn btn-outline-primary btn-sm"
                                       href="<?= e(url('admin/order_details.php?id=' . $orderId)) ?>">
                                        Manage
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php endif; ?>
</div>