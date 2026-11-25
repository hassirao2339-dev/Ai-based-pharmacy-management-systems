<?php
/**
 * alerts.php - low-stock alerts view
 * Controller: admin/alerts.php
 *
 * Variables:
 *   $alerts     the alerts to show
 *   $openCount  how many alerts are still waiting
 *   $showAll    true when acknowledged alerts are included
 */
?>
<div class="admin-card-head">
    <p class="text-body-secondary mb-0">
        <?php if ($openCount === 0): ?>
            <i class="bi bi-check-circle text-success" aria-hidden="true"></i>
            No medicine is below its reorder threshold right now.
        <?php else: ?>
            <strong><?= e($openCount) ?></strong>
            <?= $openCount === 1 ? 'medicine needs' : 'medicines need' ?> restocking.
            Alerts are raised automatically when stock falls to the threshold.
        <?php endif; ?>
    </p>

    <div class="d-flex flex-wrap gap-2">
        <?php if ($showAll): ?>
            <a class="btn btn-soft" href="<?= e(url('admin/alerts.php')) ?>">
                Show open only
            </a>
        <?php else: ?>
            <a class="btn btn-soft" href="<?= e(url('admin/alerts.php?show=all')) ?>">
                Show acknowledged too
            </a>
        <?php endif; ?>

        <?php if ($openCount > 0): ?>
            <form method="post" action="<?= e(url('admin/alerts.php')) ?>"
                  onsubmit="return confirm('Acknowledge every open alert?');">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="acknowledge_all">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check2-all" aria-hidden="true"></i> Acknowledge all
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="admin-card">
    <?php if (empty($alerts)): ?>

        <div class="empty-state">
            <span class="empty-icon" aria-hidden="true"><i class="bi bi-bell"></i></span>
            <h2 class="h5">
                <?= $showAll ? 'No alerts have ever been raised' : 'Nothing needs restocking' ?>
            </h2>
            <p class="text-body-secondary mb-0">
                An alert appears here as soon as a medicine reaches its reorder threshold.
            </p>
        </div>

    <?php else: ?>

        <div class="table-responsive">
            <table class="table admin-table js-datatable">
                <thead>
                    <tr>
                        <th scope="col">Medicine</th>
                        <th scope="col">Stock when raised</th>
                        <th scope="col">Stock now</th>
                        <th scope="col">Threshold</th>
                        <th scope="col">Raised</th>
                        <th scope="col">Status</th>
                        <th scope="col" data-dt-order="disable" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($alerts as $alert): ?>
                        <?php
                        $alertId = (int) $alert['alert_id'];
                        $medicineId = (int) $alert['medicine_id'];
                        $stockNow = (int) $alert['stock_now'];
                        $threshold = (int) $alert['reorder_threshold'];
                        $isOpen = ((int) $alert['is_acknowledged'] === 0);

                        // FR-44: still low, so the row keeps its warning colour.
                        $isStillLow = ($stockNow <= $threshold);
                        ?>
                        <tr class="<?= $isOpen && $isStillLow ? 'row-low' : '' ?>">
                            <td>
                                <a href="<?= e(url('admin/medicine_form.php?id=' . $medicineId)) ?>">
                                    <?= e($alert['medicine_name']) ?>
                                </a>
                                <span class="d-block text-body-secondary small">
                                    <?= e($alert['sku']) ?> &middot; <?= e($alert['category']) ?>
                                </span>
                            </td>

                            <td><?= e($alert['current_stock']) ?></td>

                            <td>
                                <span class="fw-bold"><?= e($stockNow) ?></span>
                                <?php if (!$isStillLow): ?>
                                    <span class="d-block small text-success">
                                        <i class="bi bi-check-circle" aria-hidden="true"></i> Restocked
                                    </span>
                                <?php elseif ($stockNow === 0): ?>
                                    <span class="d-block small fw-bold text-danger">Out of stock</span>
                                <?php endif; ?>
                            </td>

                            <td><?= e($threshold) ?></td>

                            <td class="text-nowrap"><?= e(date('j M Y, g:i a', strtotime($alert['alert_date']))) ?></td>

                            <td>
                                <?php if ($isOpen): ?>
                                    <span class="status status-pending">
                                        <i class="bi bi-exclamation-circle" aria-hidden="true"></i> Open
                                    </span>
                                <?php else: ?>
                                    <span class="status status-delivered">
                                        <i class="bi bi-check-circle" aria-hidden="true"></i> Acknowledged
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <div class="table-actions">
                                    <a class="btn btn-soft btn-sm"
                                       href="<?= e(url('admin/batch_form.php?medicine_id=' . $medicineId)) ?>">
                                        Add stock
                                    </a>

                                    <?php if ($isOpen): ?>
                                        <!-- FR-42: acknowledge one alert -->
                                        <form method="post" action="<?= e(url('admin/alerts.php')) ?>">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="acknowledge">
                                            <input type="hidden" name="alert_id" value="<?= e($alertId) ?>">
                                            <button type="submit" class="btn btn-outline-primary btn-sm">
                                                Acknowledge
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php endif; ?>
</div>