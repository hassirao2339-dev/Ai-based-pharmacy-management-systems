<?php
/**
 * batch_form.php - add or edit a stock batch view
 * Controller: admin/batch_form.php
 *
 * Variables:
 *   $isEditing      true when changing an existing batch
 *   $batch          the batch being edited, or null when adding
 *   $batchId        its id, or 0
 *   $medicine       the medicine this batch is for, or null
 *   $medicineId     its id, or 0
 *   $minQuantity    the lowest quantity allowed when editing
 *   $form           what the fields should show
 *   $errors         error messages by field name
 *   $medicines      every medicine, for the dropdown
 *   $nextFifoOrder  the FIFO position a new batch would get
 */
?>
<div class="admin-card-head">
    <p class="text-body-secondary mb-0">
        <?php if ($isEditing): ?>
            Editing batch <span class="fifo-pill">#<?= e($batch['fifo_order']) ?></span>
            of <strong><?= e($batch['medicine_name']) ?></strong>.
            Changing the quantity updates the medicine's stock straight away.
        <?php else: ?>
            A batch is one delivery of stock. FIFO sells the oldest batch first,
            so this delivery goes to the back of the queue.
        <?php endif; ?>
    </p>

    <a class="btn btn-soft" href="<?= e(url('admin/batches.php' . ($medicineId > 0 ? '?medicine_id=' . $medicineId : ''))) ?>">
        <i class="bi bi-arrow-left" aria-hidden="true"></i> Back to batches
    </a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger" role="alert">
        <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
        Please fix the highlighted fields below.
    </div>
<?php endif; ?>

<form method="post" action="<?= e(url('admin/batch_form.php')) ?>">
    <?= csrfField() ?>
    <input type="hidden" name="batch_id" value="<?= e($batchId) ?>">

    <div class="row g-4">

        <div class="col-lg-8">
            <div class="admin-card">
                <h2 class="admin-card-title mb-4">Delivery details</h2>

                <!-- Which medicine (only when adding) -->
                <div class="mb-3">
                    <label for="medicine_id" class="form-label">Medicine</label>

                    <?php if ($isEditing): ?>
                        <input type="text" class="form-control" readonly
                               value="<?= e($batch['medicine_name']) ?> (<?= e($batch['sku']) ?>)">
                        <div class="form-hint">
                            A batch cannot be moved to another medicine. Add a new batch instead.
                        </div>
                    <?php else: ?>
                        <select id="medicine_id" name="medicine_id" required
                                class="form-select<?= isset($errors['medicine_id']) ? ' is-invalid' : '' ?>">
                            <option value="">Choose a medicine</option>
                            <?php foreach ($medicines as $medicineOption): ?>
                                <option value="<?= e($medicineOption['medicine_id']) ?>"
                                    <?= $medicineId === (int) $medicineOption['medicine_id'] ? 'selected' : '' ?>>
                                    <?= e($medicineOption['name']) ?> (<?= e($medicineOption['sku']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['medicine_id'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['medicine_id']) ?></div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <label for="quantity" class="form-label">
                            <?= $isEditing ? 'Units left in this batch' : 'Units received' ?>
                        </label>
                        <input type="number" id="quantity" name="quantity"
                               min="<?= e($minQuantity) ?>" max="1000000" step="1" required
                               class="form-control<?= isset($errors['quantity']) ? ' is-invalid' : '' ?>"
                               value="<?= e($form['quantity']) ?>" placeholder="100">
                        <?php if (isset($errors['quantity'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['quantity']) ?></div>
                        <?php elseif ($isEditing && $minQuantity > 1): ?>
                            <div class="form-hint">
                                At least <?= e($minQuantity) ?>, because that many units are promised to orders.
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="col-sm-6">
                        <label for="cost_price" class="form-label">Cost price per unit (Rs)</label>
                        <input type="number" id="cost_price" name="cost_price" min="0" max="99999999" step="0.01" required
                               class="form-control<?= isset($errors['cost_price']) ? ' is-invalid' : '' ?>"
                               value="<?= e($form['cost_price']) ?>" placeholder="27.50">
                        <?php if (isset($errors['cost_price'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['cost_price']) ?></div>
                        <?php else: ?>
                            <div class="form-hint">What the pharmacy paid, used by the inventory report.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="supplier_name" class="form-label">Supplier</label>
                    <input type="text" id="supplier_name" name="supplier_name" maxlength="150" required
                           class="form-control<?= isset($errors['supplier_name']) ? ' is-invalid' : '' ?>"
                           value="<?= e($form['supplier_name']) ?>" placeholder="Multan Medical Supplies">
                    <?php if (isset($errors['supplier_name'])): ?>
                        <div class="invalid-feedback d-block"><?= e($errors['supplier_name']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="row g-3 mb-0">
                    <div class="col-sm-6">
                        <label for="received_date" class="form-label">Received on</label>
                        <input type="date" id="received_date" name="received_date" required
                               max="<?= e(date('Y-m-d')) ?>"
                               class="form-control<?= isset($errors['received_date']) ? ' is-invalid' : '' ?>"
                               value="<?= e($form['received_date']) ?>">
                        <?php if (isset($errors['received_date'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['received_date']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="col-sm-6">
                        <label for="expiry_date" class="form-label">Expires on</label>
                        <!-- min stops a past date in the picker; PHP checks it again (FR-36) -->
                        <input type="date" id="expiry_date" name="expiry_date" required
                               min="<?= e(date('Y-m-d', strtotime('+1 day'))) ?>"
                               class="form-control<?= isset($errors['expiry_date']) ? ' is-invalid' : '' ?>"
                               value="<?= e($form['expiry_date']) ?>">
                        <?php if (isset($errors['expiry_date'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['expiry_date']) ?></div>
                        <?php else: ?>
                            <div class="form-hint">Expired stock cannot be added (FR-36).</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <?php if ($isEditing): ?>
                <div class="admin-card mb-4">
                    <h2 class="admin-card-title mb-3">This batch</h2>

                    <dl class="info-list mb-0">
                        <div>
                            <dt>FIFO position</dt>
                            <dd>#<?= e($batch['fifo_order']) ?></dd>
                        </div>
                        <div>
                            <dt>Originally received</dt>
                            <dd><?= e($batch['quantity_received']) ?> units</dd>
                        </div>
                        <div>
                            <dt>Sold so far</dt>
                            <dd><?= e((int) $batch['quantity_received'] - (int) $batch['quantity']) ?> units</dd>
                        </div>
                    </dl>
                </div>
            <?php else: ?>
                <div class="admin-card mb-4">
                    <h2 class="admin-card-title mb-3">FIFO position</h2>

                    <p class="mb-2">
                        This delivery will be
                        <span class="fifo-pill">#<?= e($nextFifoOrder) ?></span>
                        in the queue.
                    </p>
                    <p class="form-hint mb-0">
                        <i class="bi bi-info-circle" aria-hidden="true"></i>
                        Lower numbers are sold first, so older stock always leaves the shelf
                        before newer stock.
                    </p>
                </div>
            <?php endif; ?>

            <div class="admin-card">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-check-lg" aria-hidden="true"></i>
                    <?= $isEditing ? 'Save changes' : 'Add to stock' ?>
                </button>
                <a class="btn btn-soft w-100 mt-2"
                   href="<?= e(url('admin/batches.php' . ($medicineId > 0 ? '?medicine_id=' . $medicineId : ''))) ?>">
                    Cancel
                </a>
            </div>
        </div>

    </div>
</form>