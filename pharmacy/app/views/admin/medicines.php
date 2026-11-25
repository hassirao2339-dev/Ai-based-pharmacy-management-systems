<?php
/**
 * medicines.php - admin medicine list view
 * Controller: admin/medicines.php
 *
 * Variables:
 *   $medicines  every medicine, active and hidden
 */
?>
<div class="admin-card-head">
    <p class="text-body-secondary mb-0">
        <?= e(count($medicines)) ?> medicines in the catalogue.
        Rows in orange need restocking, rows in red have stock expiring within
        <?= e(EXPIRY_WARNING_DAYS) ?> days.
    </p>

    <a class="btn btn-primary" href="<?= e(url('admin/medicine_form.php')) ?>">
        <i class="bi bi-plus-lg" aria-hidden="true"></i> Add medicine
    </a>
</div>

<div class="admin-card">
    <div class="table-responsive">
        <!-- js-datatable turns this into a sortable, searchable, paged table (admin.js) -->
        <table class="table admin-table js-datatable">
            <thead>
                <tr>
                    <th scope="col">Medicine</th>
                    <th scope="col">Category</th>
                    <th scope="col">Price</th>
                    <th scope="col">Stock</th>
                    <th scope="col">Expiry</th>
                    <th scope="col">Status</th>
                    <th scope="col" data-dt-order="disable" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($medicines as $medicine): ?>
                    <?php
                    $medicineId = (int) $medicine['medicine_id'];
                    $stock = (int) $medicine['stock_qty'];
                    $threshold = (int) $medicine['reorder_threshold'];
                    $isActive = ((int) $medicine['is_active'] === 1);

                    // days_to_expiry is null when there is no usable stock at all.
                    $daysToExpiry = $medicine['days_to_expiry'];
                    $isExpiringSoon = ($daysToExpiry !== null && (int) $daysToExpiry <= EXPIRY_WARNING_DAYS);

                    // FR-44 orange for low stock; FR-38 red wins when stock also expires soon.
                    $rowClass = '';

                    if ($isActive && $stock <= $threshold) {
                        $rowClass = 'row-low';
                    }

                    if ($isActive && $isExpiringSoon) {
                        $rowClass = 'row-expiring';
                    }
                    ?>
                    <tr class="<?= e($rowClass) ?>">
                        <td>
                            <a href="<?= e(url('admin/medicine_form.php?id=' . $medicineId)) ?>">
                                <?= e($medicine['name']) ?>
                            </a>
                            <span class="d-block text-body-secondary small">
                                <?= e($medicine['sku']) ?> &middot; <?= e($medicine['manufacturer']) ?>
                            </span>
                        </td>

                        <td>
                            <?= e($medicine['category']) ?>
                            <span class="d-block text-body-secondary small"><?= e($medicine['drap_category']) ?></span>
                        </td>

                        <td class="text-nowrap"><?= e(formatPrice((float) $medicine['price'])) ?></td>

                        <td>
                            <span class="fw-bold"><?= e($stock) ?></span>
                            <span class="d-block text-body-secondary small">
                                threshold <?= e($threshold) ?>
                                &middot; <?= e($medicine['batch_count']) ?>
                                <?= (int) $medicine['batch_count'] === 1 ? 'batch' : 'batches' ?>
                            </span>
                        </td>

                        <td class="text-nowrap">
                            <?php if ($medicine['expiry_date'] === null): ?>
                                <span class="text-body-secondary">No stock</span>
                            <?php else: ?>
                                <?= e(date('M Y', strtotime($medicine['expiry_date']))) ?>
                                <?php if ($isExpiringSoon): ?>
                                    <span class="d-block small fw-bold text-danger">
                                        <i class="bi bi-hourglass-split" aria-hidden="true"></i>
                                        <?= e(max(0, (int) $daysToExpiry)) ?> days left
                                    </span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php if ($isActive): ?>
                                <span class="status status-delivered">
                                    <i class="bi bi-check-circle" aria-hidden="true"></i> Active
                                </span>
                            <?php else: ?>
                                <span class="status status-cancelled">
                                    <i class="bi bi-eye-slash" aria-hidden="true"></i> Hidden
                                </span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <div class="table-actions">
                                <a class="btn btn-soft btn-sm"
                                   href="<?= e(url('admin/batches.php?medicine_id=' . $medicineId)) ?>">
                                    Batches
                                </a>
                                <a class="btn btn-outline-primary btn-sm"
                                   href="<?= e(url('admin/medicine_form.php?id=' . $medicineId)) ?>">
                                    Edit
                                </a>

                                <!-- Soft delete: hide or show (FR-39) -->
                                <form method="post" action="<?= e(url('admin/medicine_toggle.php')) ?>">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="medicine_id" value="<?= e($medicineId) ?>">
                                    <input type="hidden" name="is_active" value="<?= $isActive ? '0' : '1' ?>">
                                    <button type="submit"
                                            class="btn btn-sm <?= $isActive ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                                        <?= $isActive ? 'Hide' : 'Show' ?>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>