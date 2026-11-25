<?php
/**
 * customers.php - customer accounts view
 * Controller: admin/customers.php
 *
 * Variables:
 *   $customers  every customer account, with order_count and total_spent
 */

$activeCount = 0;

foreach ($customers as $customer) {
    if ((int) $customer['is_active'] === 1) {
        $activeCount++;
    }
}
?>
<div class="admin-card-head">
    <p class="text-body-secondary mb-0">
        <?= e(count($customers)) ?> registered customers, <?= e($activeCount) ?> of them active.
        Deactivating an account blocks logging in but keeps every order.
    </p>
</div>

<div class="admin-card">
    <?php if (empty($customers)): ?>

        <div class="empty-state">
            <span class="empty-icon" aria-hidden="true"><i class="bi bi-people"></i></span>
            <h2 class="h5">No customers yet</h2>
            <p class="text-body-secondary mb-0">
                Accounts appear here as soon as people register.
            </p>
        </div>

    <?php else: ?>

        <div class="table-responsive">
            <table class="table admin-table js-datatable">
                <thead>
                    <tr>
                        <th scope="col">Customer</th>
                        <th scope="col">Phone</th>
                        <th scope="col">Member since</th>
                        <th scope="col">Orders</th>
                        <th scope="col">Spent</th>
                        <th scope="col">Status</th>
                        <th scope="col" data-dt-order="disable" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $customer): ?>
                        <?php
                        $customerId = (int) $customer['user_id'];
                        $isActive = ((int) $customer['is_active'] === 1);
                        ?>
                        <tr>
                            <td>
                                <?= e($customer['name']) ?>
                                <span class="d-block text-body-secondary small"><?= e($customer['email']) ?></span>
                            </td>

                            <td class="text-nowrap"><?= e($customer['phone']) ?></td>

                            <td class="text-nowrap"><?= e(date('j M Y', strtotime($customer['created_at']))) ?></td>

                            <td><?= e($customer['order_count']) ?></td>

                            <td class="fw-bold text-nowrap"><?= e(formatPrice((float) $customer['total_spent'])) ?></td>

                            <td>
                                <?php if ($isActive): ?>
                                    <span class="status status-delivered">
                                        <i class="bi bi-check-circle" aria-hidden="true"></i> Active
                                    </span>
                                <?php else: ?>
                                    <span class="status status-cancelled">
                                        <i class="bi bi-slash-circle" aria-hidden="true"></i> Deactivated
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <div class="table-actions">
                                    <!-- FR-58: activate or deactivate -->
                                    <form method="post" action="<?= e(url('admin/customer_toggle.php')) ?>"
                                          <?= $isActive ? 'onsubmit="return confirm(\'Deactivate this account? They will not be able to log in.\');"' : '' ?>>
                                        <?= csrfField() ?>
                                        <input type="hidden" name="customer_id" value="<?= e($customerId) ?>">
                                        <input type="hidden" name="is_active" value="<?= $isActive ? '0' : '1' ?>">
                                        <button type="submit"
                                                class="btn btn-sm <?= $isActive ? 'btn-outline-danger' : 'btn-outline-success' ?>">
                                            <?= $isActive ? 'Deactivate' : 'Activate' ?>
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