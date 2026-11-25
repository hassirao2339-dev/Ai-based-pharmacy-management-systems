    <?php
/**
 * batches.php - stock batch list view
 * Controller: admin/batches.php
 *
 * Variables:
 *   $batches     the batches to show
 *   $medicine    the medicine being filtered on, or null for all
 *   $medicineId  its id, or 0
 */

$isFiltered = ($medicine !== null);
?>
<div class="admin-card-head">
    <p class="text-body-secondary mb-0">
        <?php if ($isFiltered): ?>
            <?= e(count($batches)) ?>
            <?= count($batches) === 1 ? 'batch' : 'batches' ?>
            of <strong><?= e($medicine['name']) ?></strong>.
            FIFO sells the lowest position first.
        <?php else: ?>
            <?= e(count($batches)) ?> batches in the pharmacy.
            Rows in red expire within <?= e(EXPIRY_WARNING_DAYS) ?> days.
        <?php endif; ?>
    </p>

    <div class="d-flex flex-wrap gap-2">
        <?php if ($isFiltered): ?>
            <a class="btn btn-soft" href="<?= e(url('admin/batches.php')) ?>">
                <i class="bi bi-x-lg" aria-hidden="true"></i> Show all batches
            </a>
            <a class="btn btn-primary" href="<?= e(url('admin/batch_form.php?medicine_id=' . $medicineId)) ?>">
                <i class="bi bi-plus-lg" aria-hidden="true"></i> Add batch
            </a>
        <?php else: ?>
            <a class="btn btn-primary" href="<?= e(url('admin/batch_form.php')) ?>">
                <i class="bi bi-plus-lg" aria-hidden="true"></i> Add batch
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if (empty($batches)): ?>

    <div class="admin-card">
        <div class="empty-state">
            <span class="empty-icon" aria-hidden="true"><i class="bi bi-boxes"></i></span>
            <h2 class="h5">No stock batches yet</h2>
            <p class="text-body-secondary">
                <?php if ($isFiltered): ?>
                    <?= e($medicine['name']) ?> has no stock. Add a batch so customers can order it.
                <?php else: ?>
                    Stock arrives as batches. Add one to get started.
                <?php endif; ?>
            </p>
            <a class="btn btn-primary"
               href="<?= e(url('admin/batch_form.php' . ($isFiltered ? '?medicine_id=' . $medicineId : ''))) ?>">
                Add the first batch
            </a>
        </div>
    </div>

<?php else: ?>

    <div class="admin-card">
        <div class="table-responsive">
            <table class="table admin-table js-datatable">
                <thead>
                    <tr>
                        <th scope="col">Medicine</th>
                        <th scope="col">FIFO</th>
                        <th scope="col">Stock left</th>
                        <th scope="col">Supplier</th>
                        <th scope="col">Cost</th>
                        <th scope="col">Received</th>
                        <th scope="col">Expires</th>
                        <th scope="col" data-dt-order="disable" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($batches as $batch): ?>
                        <?php
                        $batchId = (int) $batch['batch_id'];
                        $left = (int) $batch['quantity'];
                        $received = (int) $batch['quantity_received'];
                        $used = (int) $batch['used_units'];
                        $daysToExpiry = (int) $batch['days_to_expiry'];

                        $isExpired = ($daysToExpiry <= 0);
                        $isExpiringSoon = (!$isExpired && $daysToExpiry <= EXPIRY_WARNING_DAYS);
                        $isEmpty = ($left === 0);

                        // FR-38: red for expired or expiring soon; grey-ish for used up.
                        $rowClass = '';

                        if ($isExpired || $isExpiringSoon) {
                            $rowClass = 'row-expiring';
                        }
                        ?>
                        <tr class="<?= e($rowClass) ?>">
                            <td>
                                <a href="<?= e(url('admin/medicine_form.php?id=' . (int) $batch['medicine_id'])) ?>">
                                    <?= e($batch['medicine_name']) ?>
                                </a>
                                <span class="d-block text-body-secondary small"><?= e($batch['sku']) ?></span>
                            </td>

                            <td>
                                <span class="fifo-pill">#<?= e($batch['fifo_order']) ?></span>
                            </td>

                            <td>
                                <span class="fw-bold"><?= e($left) ?></span>
                                <span class="d-block text-body-secondary small">
                                    <?= e($used) ?> of <?= e($received) ?> sold
                                </span>
                            </td>

                            <td><?= e($batch['supplier_name']) ?></td>

                            <td class="text-nowrap"><?= e(formatPrice((float) $batch['cost_price'])) ?></td>

                            <td class="text-nowrap"><?= e(date('j M Y', strtotime($batch['received_date']))) ?></td>

                            <td class="text-nowrap">
                                <?= e(date('j M Y', strtotime($batch['expiry_date']))) ?>
                                <?php if ($isExpired): ?>
                                    <span class="d-block small fw-bold text-danger">
                                        <i class="bi bi-x-circle" aria-hidden="true"></i> Expired
                                    </span>
                                <?php elseif ($isExpiringSoon): ?>
                                    <span class="d-block small fw-bold text-danger">
                                        <i class="bi bi-hourglass-split" aria-hidden="true"></i>
                                        <?= e($daysToExpiry) ?> days left
                                    </span>
                                <?php elseif ($isEmpty): ?>
                                    <span class="d-block small text-body-secondary">
                                        <i class="bi bi-dash-circle" aria-hidden="true"></i> Used up
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <div class="table-actions">
                                    <a class="btn btn-outline-primary btn-sm"
                                       href="<?= e(url('admin/batch_form.php?id=' . $batchId)) ?>">
                                        Edit
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php endif; ?>