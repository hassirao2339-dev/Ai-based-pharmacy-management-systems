<?php
/**
 * medicine_form.php - add or edit a medicine view
 * Controller: admin/medicine_form.php
 *
 * Variables:
 *   $isEditing      true when changing an existing medicine
 *   $medicine       the medicine being edited, or null when adding
 *   $medicineId     its id, or 0 when adding
 *   $form           what the fields should show
 *   $errors         error messages by field name
 *   $categories     existing category names, offered as suggestions
 *   $manufacturers  existing manufacturer names, offered as suggestions
 *
 * DRAP classes come from the Drug Regulatory Authority of Pakistan and decide
 * how a medicine may be sold, so they are a fixed list rather than free text.
 */

$drapCategories = ['General Sale', 'Pharmacy Only', 'Prescription Only', 'Controlled Drug'];
?>
<div class="admin-card-head">
    <p class="text-body-secondary mb-0">
        <?php if ($isEditing): ?>
            Changing the price here does not change past orders, because each order
            keeps the price it was placed at.
        <?php else: ?>
            A new medicine starts with no stock. Add a batch afterwards so customers can order it.
        <?php endif; ?>
    </p>

    <a class="btn btn-soft" href="<?= e(url('admin/medicines.php')) ?>">
        <i class="bi bi-arrow-left" aria-hidden="true"></i> Back to medicines
    </a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger" role="alert">
        <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
        Please fix the highlighted fields below.
    </div>
<?php endif; ?>

<form method="post" action="<?= e(url('admin/medicine_form.php')) ?>">
    <?= csrfField() ?>
    <input type="hidden" name="medicine_id" value="<?= e($medicineId) ?>">

    <div class="row g-4">

        <!-- ============ The medicine itself ============ -->
        <div class="col-lg-8">
            <div class="admin-card">
                <h2 class="admin-card-title mb-4">Medicine details</h2>

                <div class="mb-3">
                    <label for="name" class="form-label">Medicine name</label>
                    <input type="text" id="name" name="name" maxlength="150" required
                           class="form-control<?= isset($errors['name']) ? ' is-invalid' : '' ?>"
                           value="<?= e($form['name']) ?>" placeholder="Paracetamol 500 mg Tablets">
                    <?php if (isset($errors['name'])): ?>
                        <div class="invalid-feedback d-block"><?= e($errors['name']) ?></div>
                    <?php else: ?>
                        <div class="form-hint">Include the strength and form, so customers know what they are buying.</div>
                    <?php endif; ?>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-sm-5">
                        <label for="sku" class="form-label">SKU</label>
                        <input type="text" id="sku" name="sku" maxlength="50" required
                               class="form-control<?= isset($errors['sku']) ? ' is-invalid' : '' ?>"
                               value="<?= e($form['sku']) ?>" placeholder="PCM-500">
                        <?php if (isset($errors['sku'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['sku']) ?></div>
                        <?php else: ?>
                            <div class="form-hint">A short code, unique to this medicine.</div>
                        <?php endif; ?>
                    </div>

                    <div class="col-sm-7">
                        <label for="manufacturer" class="form-label">Manufacturer</label>
                        <input type="text" id="manufacturer" name="manufacturer" maxlength="150" required
                               list="manufacturerList"
                               class="form-control<?= isset($errors['manufacturer']) ? ' is-invalid' : '' ?>"
                               value="<?= e($form['manufacturer']) ?>" placeholder="Indus Pharma Labs">
                        <datalist id="manufacturerList">
                            <?php foreach ($manufacturers as $manufacturerName): ?>
                                <option value="<?= e($manufacturerName) ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                        <?php if (isset($errors['manufacturer'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['manufacturer']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <label for="category" class="form-label">Category</label>
                        <input type="text" id="category" name="category" maxlength="100" required
                               list="categoryList"
                               class="form-control<?= isset($errors['category']) ? ' is-invalid' : '' ?>"
                               value="<?= e($form['category']) ?>" placeholder="Analgesic">
                        <datalist id="categoryList">
                            <?php foreach ($categories as $categoryName): ?>
                                <option value="<?= e($categoryName) ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                        <?php if (isset($errors['category'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['category']) ?></div>
                        <?php else: ?>
                            <div class="form-hint">Customers filter the catalogue by this.</div>
                        <?php endif; ?>
                    </div>

                    <div class="col-sm-6">
                        <label for="drap_category" class="form-label">DRAP category</label>
                        <select id="drap_category" name="drap_category" required
                                class="form-select<?= isset($errors['drap_category']) ? ' is-invalid' : '' ?>">
                            <option value="">Choose one</option>
                            <?php foreach ($drapCategories as $drapName): ?>
                                <option value="<?= e($drapName) ?>"
                                    <?= $form['drap_category'] === $drapName ? 'selected' : '' ?>>
                                    <?= e($drapName) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['drap_category'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['drap_category']) ?></div>
                        <?php else: ?>
                            <div class="form-hint">How this medicine may be sold.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mb-0">
                    <label for="description" class="form-label">Description</label>
                    <textarea id="description" name="description" rows="4" maxlength="2000" required
                              class="form-control<?= isset($errors['description']) ? ' is-invalid' : '' ?>"
                              placeholder="What it treats, and how it is usually taken."><?= e($form['description']) ?></textarea>
                    <?php if (isset($errors['description'])): ?>
                        <div class="invalid-feedback d-block"><?= e($errors['description']) ?></div>
                    <?php else: ?>
                        <div class="form-hint">Customers read this on the medicine page.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ============ Price, threshold and stock ============ -->
        <div class="col-lg-4">
            <div class="admin-card mb-4">
                <h2 class="admin-card-title mb-4">Selling details</h2>

                <div class="mb-3">
                    <label for="price" class="form-label">Unit price (Rs)</label>
                    <input type="number" id="price" name="price" min="0" max="99999999" step="0.01" required
                           class="form-control<?= isset($errors['price']) ? ' is-invalid' : '' ?>"
                           value="<?= e($form['price']) ?>" placeholder="45.00">
                    <?php if (isset($errors['price'])): ?>
                        <div class="invalid-feedback d-block"><?= e($errors['price']) ?></div>
                    <?php else: ?>
                        <div class="form-hint">What the customer pays per unit.</div>
                    <?php endif; ?>
                </div>

                <div class="mb-0">
                    <label for="reorder_threshold" class="form-label">Low-stock threshold</label>
                    <input type="number" id="reorder_threshold" name="reorder_threshold" min="0" max="100000" step="1" required
                           class="form-control<?= isset($errors['reorder_threshold']) ? ' is-invalid' : '' ?>"
                           value="<?= e($form['reorder_threshold']) ?>">
                    <?php if (isset($errors['reorder_threshold'])): ?>
                        <div class="invalid-feedback d-block"><?= e($errors['reorder_threshold']) ?></div>
                    <?php else: ?>
                        <div class="form-hint">An alert appears when stock falls to this number or below.</div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($isEditing): ?>
                <div class="admin-card mb-4">
                    <h2 class="admin-card-title mb-3">Current stock</h2>

                    <dl class="info-list mb-3">
                        <div>
                            <dt>In stock</dt>
                            <dd><?= e($medicine['stock_qty']) ?> units</dd>
                        </div>
                        <div>
                            <dt>Earliest expiry</dt>
                            <dd>
                                <?php if ($medicine['expiry_date'] === null): ?>
                                    No usable stock
                                <?php else: ?>
                                    <?= e(date('j M Y', strtotime($medicine['expiry_date']))) ?>
                                <?php endif; ?>
                            </dd>
                        </div>
                    </dl>

                    <p class="form-hint mb-3">
                        <i class="bi bi-info-circle" aria-hidden="true"></i>
                        Stock is not edited here. It is worked out from the batches, so
                        the number always matches what is really on the shelf.
                    </p>

                    <a class="btn btn-soft w-100" href="<?= e(url('admin/batches.php?medicine_id=' . $medicineId)) ?>">
                        <i class="bi bi-boxes" aria-hidden="true"></i> Manage batches
                    </a>
                </div>
            <?php endif; ?>

            <div class="admin-card">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-check-lg" aria-hidden="true"></i>
                    <?= $isEditing ? 'Save changes' : 'Add medicine' ?>
                </button>
                <a class="btn btn-soft w-100 mt-2" href="<?= e(url('admin/medicines.php')) ?>">Cancel</a>
            </div>
        </div>

    </div>
</form>