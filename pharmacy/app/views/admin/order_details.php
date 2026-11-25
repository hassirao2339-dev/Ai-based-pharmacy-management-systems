<?php
/**
 * order_details.php - manage one order view
 * Controller: admin/order_details.php
 *
 * Variables:
 *   $order         the order with the customer's details
 *   $items         its lines
 *   $allocations   which batches the stock came from (FIFO proof)
 *   $nextStatuses  the statuses this order may move to
 */

$orderId = (int) $order['order_id'];
$orderNumber = '#' . str_pad((string) $orderId, 4, '0', STR_PAD_LEFT);

// "cancelled" is handled by its own form, so it is taken out of the list here.
$forwardStatuses = array_values(array_diff($nextStatuses, ['cancelled']));
$canCancel = in_array('cancelled', $nextStatuses, true);
?>
<div class="admin-card-head">
    <div class="order-head">
        <span class="text-body-secondary">Placed <?= e(date('j M Y, g:i a', strtotime($order['order_date']))) ?></span>
        <?= statusBadge($order['status']) ?>
    </div>

    <a class="btn btn-soft" href="<?= e(url('admin/orders.php')) ?>">
        <i class="bi bi-arrow-left" aria-hidden="true"></i> Back to orders
    </a>
</div>

<div class="row g-4">

    <!-- ============ Items and FIFO proof ============ -->
    <div class="col-xl-7">

        <div class="admin-card mb-4">
            <h2 class="admin-card-title mb-3">Items</h2>

            <div class="table-responsive">
                <table class="table admin-table">
                    <thead>
                        <tr>
                            <th scope="col">Medicine</th>
                            <th scope="col">Qty</th>
                            <th scope="col">Unit price</th>
                            <th scope="col" class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <?= e($item['name']) ?>
                                    <span class="d-block text-body-secondary small"><?= e($item['sku']) ?></span>
                                </td>
                                <td class="fw-bold"><?= e($item['quantity']) ?></td>
                                <td><?= e(formatPrice((float) $item['unit_price'])) ?></td>
                                <td class="text-end fw-bold"><?= e(formatPrice((float) $item['subtotal'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end fw-bold">Total</td>
                            <td class="text-end fw-bold text-nowrap">
                                <?= e(formatPrice((float) $order['total_amount'])) ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Which batches the stock came from: this is FIFO, made visible -->
        <div class="admin-card">
            <h2 class="admin-card-title mb-1">Stock taken from</h2>
            <p class="text-body-secondary small mb-3">
                FIFO always takes the oldest batch first, so the lowest position is used up
                before the next one is touched.
            </p>

            <?php if (empty($allocations)): ?>
                <p class="text-body-secondary mb-0">
                    No batches are held by this order. Cancelled orders return their stock,
                    so their batch records are removed.
                </p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table admin-table">
                        <thead>
                            <tr>
                                <th scope="col">Medicine</th>
                                <th scope="col">Batch</th>
                                <th scope="col">Expires</th>
                                <th scope="col">Supplier</th>
                                <th scope="col" class="text-end">Units</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allocations as $allocation): ?>
                                <tr>
                                    <td><?= e($allocation['medicine_name']) ?></td>
                                    <td><span class="fifo-pill">#<?= e($allocation['fifo_order']) ?></span></td>
                                    <td class="text-nowrap"><?= e(date('j M Y', strtotime($allocation['expiry_date']))) ?></td>
                                    <td><?= e($allocation['supplier_name']) ?></td>
                                    <td class="text-end fw-bold"><?= e($allocation['quantity']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ============ Customer, delivery and status ============ -->
    <div class="col-xl-5">

        <div class="admin-card mb-4">
            <h2 class="admin-card-title mb-3">Customer</h2>

            <dl class="info-list mb-0">
                <div>
                    <dt>Name</dt>
                    <dd><?= e($order['customer_name']) ?></dd>
                </div>
                <div>
                    <dt>Email</dt>
                    <dd><?= e($order['customer_email']) ?></dd>
                </div>
                <div>
                    <dt>Phone</dt>
                    <dd><?= e($order['customer_phone']) ?></dd>
                </div>
                <div>
                    <dt>Delivery address</dt>
                    <dd><?= e($order['delivery_address']) ?></dd>
                </div>
                <div>
                    <dt>Payment</dt>
                    <dd><?= e($order['payment_method']) ?></dd>
                </div>
            </dl>
        </div>

        <!-- Change the status (FR-59) -->
        <div class="admin-card mb-4">
            <h2 class="admin-card-title mb-3">Order status</h2>

            <?php if (empty($nextStatuses)): ?>
                <p class="text-body-secondary mb-0">
                    This order is <?= e($order['status']) ?>, so it cannot change any further.
                </p>

                <?php if ($order['status'] === 'cancelled' && !empty($order['cancel_reason'])): ?>
                    <div class="cancelled-note mt-3">
                        <i class="bi bi-x-circle" aria-hidden="true"></i>
                        <div>
                            <strong>Cancelled</strong>
                            <span class="d-block text-body-secondary small">
                                Reason: <?= e($order['cancel_reason']) ?>
                            </span>
                        </div>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <?php foreach ($forwardStatuses as $statusName): ?>
                    <form method="post" action="<?= e(url('admin/order_details.php')) ?>" class="mb-2">
                        <?= csrfField() ?>
                        <input type="hidden" name="order_id" value="<?= e($orderId) ?>">
                        <input type="hidden" name="new_status" value="<?= e($statusName) ?>">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-<?= e(orderStatuses()[$statusName]['icon']) ?>" aria-hidden="true"></i>
                            Mark as <?= e(orderStatuses()[$statusName]['label']) ?>
                        </button>
                    </form>
                <?php endforeach; ?>

                <p class="form-hint mb-0">
                    <i class="bi bi-info-circle" aria-hidden="true"></i>
                    An order only moves forward. The customer sees this status straight away.
                </p>
            <?php endif; ?>
        </div>

        <!-- Cancel with a mandatory reason (FR-59) -->
        <?php if ($canCancel): ?>
            <div class="admin-card">
                <h2 class="admin-card-title mb-1">Cancel this order</h2>
                <p class="text-body-secondary small mb-3">
                    Cancelling returns every medicine to the batch it came from. A reason is required.
                </p>

                <form method="post" action="<?= e(url('admin/order_details.php')) ?>"
                      onsubmit="return confirm('Cancel this order? The stock will be returned.');">
                    <?= csrfField() ?>
                    <input type="hidden" name="order_id" value="<?= e($orderId) ?>">
                    <input type="hidden" name="new_status" value="cancelled">

                    <div class="mb-3">
                        <label for="cancel_reason" class="form-label">Reason</label>
                        <textarea id="cancel_reason" name="cancel_reason" rows="2" maxlength="500" required
                                  class="form-control"
                                  placeholder="Out of stock, customer asked to cancel, wrong address..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-outline-danger w-100">
                        <i class="bi bi-x-circle" aria-hidden="true"></i> Cancel order
                    </button>
                </form>
            </div>
        <?php endif; ?>

    </div>
</div>