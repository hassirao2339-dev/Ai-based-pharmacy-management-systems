<?php
/**
 * expiry.php - expiry warnings view
 * Controller: admin/expiry.php
 *
 * Variables:
 *   $expiredBatches       batches whose expiry date has passed
 *   $expiringSoonBatches  batches expiring within EXPIRY_WARNING_DAYS days
 *   $valueAtRisk          what all of that stock cost the pharmacy
 */

$expiredCount = count($expiredBatches);
$soonCount = count($expiringSoonBatches);
?>
<div class="admin-card-head">
    <p class="text-body-secondary mb-0">
        Stock expiring within <?= e(EXPIRY_WARNING_DAYS) ?> days.
        Expired batches are never sold, because FIFO only takes stock that is still in date.
    </p>
</div>

<!-- A quick summary of the situation -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-4">
        <div class="metric-card">
            <span class="metric-icon metric-icon-red" aria-hidden="true"><i class="bi bi-x-circle"></i></span>
            <span>
                <span class="metric-label">Already expired</span>
                <span class="metric-value"><?= e($expiredCount) ?></span>
            </span>
        </div>
    </div>

    <div class="col-sm-6 col-xl-4">
        <div class="metric-card">
            <span class="metric-icon metric-icon-amber" aria-hidden="true"><i class="bi bi-hourglass-split"></i></span>
            <span>
                <span class="metric-label">Expiring soon</span>
                <span class="metric-value"><?= e($soonCount) ?></span>
            </span>
        </div>
    </div>

    <div class="col-12 col-xl-4">
        <div class="metric-card">
            <span class="metric-icon metric-icon-purple" aria-hidden="true"><i class="bi bi-cash-stack"></i></span>
            <span>
                <span class="metric-label">Value at risk (cost)</span>
                <span class="metric-value"><?= e(formatPrice($valueAtRisk)) ?></span>
            </span>
        </div>
    </div>
</div>

<!-- ============ Already expired ============ -->
<div class="admin-card mb-4">
    <h2 class="admin-card-title mb-1">Expired stock</h2>
    <p class="text-body-secondary small mb-3">
        This stock cannot be sold. Writing it off sets the batch to zero and
        removes it from the stock figures, while keeping the record.
    </p>

    <?php if ($expiredCount === 0): ?>
        <p class="text-body-secondary mb-0">
            <i class="bi bi-check-circle text-success" aria-hidden="true"></i>
            Nothing on the shelf has expired.
        </p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table admin-table">
                <thead>
                    <tr>
                        <th scope="col">Medicine</th>
                        <th scope="col">Batch</th>
                        <th scope="col">Units left</th>
                        <th scope="col">Expired on</th>
                        <th scope="col">Cost value</th>
                        <th scope="col" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($expiredBatches as $batch): ?>
                        <tr class="row-expiring">
                            <td>
                                <a href="<?= e(url('admin/medicine_form.php?id=' . (int) $batch['medicine_id'])) ?>">
                                    <?= e($batch['medicine_name']) ?>
                                </a>
                                <span class="d-block text-body-secondary small"><?= e($batch['sku']) ?></span>
                            </td>
                            <td><span class="fifo-pill">#<?= e($batch['fifo_order']) ?></span></td>
                            <td class="fw-bold"><?= e($batch['quantity']) ?></td>
                            <td class="text-nowrap">
                                <?= e(date('j M Y', strtotime($batch['expiry_date']))) ?>
                                <span class="d-block small fw-bold text-danger">
                                    <?= e(abs((int) $batch['days_to_expiry'])) ?> days ago
                                </span>
                            </td>
                            <td class="text-nowrap"><?= e(formatPrice((float) $batch['value_at_risk'])) ?></td>
                            <td>
                                <div class="table-actions">
                                    <form method="post" action="<?= e(url('admin/expiry.php')) ?>"
                                          onsubmit="return confirm('Write off this expired batch? The units will be removed from stock.');">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="batch_id" value="<?= e($batch['batch_id']) ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm">
                                            Write off
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- ============ Expiring soon (FR-43) ============ -->
<div class="admin-card">
    <h2 class="admin-card-title mb-1">Expiring within <?= e(EXPIRY_WARNING_DAYS) ?> days</h2>
    <p class="text-body-secondary small mb-3">
        This stock can still be sold. FIFO already puts it at the front of the queue,
        because it is the oldest.
    </p>

    <?php if ($soonCount === 0): ?>
        <p class="text-body-secondary mb-0">
            <i class="bi bi-check-circle text-success" aria-hidden="true"></i>
            No batch expires in the next <?= e(EXPIRY_WARNING_DAYS) ?> days.
        </p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table admin-table js-datatable">
                <thead>
                    <tr>
                        <th scope="col">Medicine</th>
                        <th scope="col">Batch</th>
                        <th scope="col">Units left</th>
                        <th scope="col">Expires</th>
                        <th scope="col">Days left</th>
                        <th scope="col">Cost value</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($expiringSoonBatches as $batch): ?>
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
                            <td class="fw-bold"><?= e($daysLeft) ?></td>
                            <td class="text-nowrap"><?= e(formatPrice((float) $batch['value_at_risk'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>