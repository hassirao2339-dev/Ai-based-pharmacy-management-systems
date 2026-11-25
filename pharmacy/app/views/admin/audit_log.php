<?php
/**
 * audit_log.php - audit trail view
 * Controller: admin/audit_log.php
 *
 * Variables:
 *   $entries       the most recent log entries
 *   $totalEntries  how many entries the log holds in total
 *
 * Each action type gets an icon and a colour, so the list can be scanned
 * quickly. Anything not in this list falls back to a neutral icon.
 */

$actionStyles = [
    'order_status_update' => ['icon' => 'arrow-right-circle', 'class' => 'metric-icon-blue'],
    'order_cancel' => ['icon' => 'x-circle', 'class' => 'metric-icon-red'],
    'medicine_create' => ['icon' => 'plus-circle', 'class' => 'metric-icon-green'],
    'medicine_update' => ['icon' => 'pencil', 'class' => 'metric-icon-blue'],
    'medicine_delete' => ['icon' => 'eye-slash', 'class' => 'metric-icon-red'],
    'medicine_restore' => ['icon' => 'eye', 'class' => 'metric-icon-green'],
    'batch_create' => ['icon' => 'box-seam', 'class' => 'metric-icon-green'],
    'batch_update' => ['icon' => 'boxes', 'class' => 'metric-icon-blue'],
    'customer_activate' => ['icon' => 'person-check', 'class' => 'metric-icon-green'],
    'customer_deactivate' => ['icon' => 'person-slash', 'class' => 'metric-icon-red'],
    'report_generate' => ['icon' => 'file-earmark-pdf', 'class' => 'metric-icon-purple'],
];
?>
<div class="admin-card-head">
    <p class="text-body-secondary mb-0">
        <?= e($totalEntries) ?> recorded actions.
        <?php if ($totalEntries > count($entries)): ?>
            Showing the most recent <?= e(count($entries)) ?>.
        <?php endif; ?>
        Entries are never edited or deleted.
    </p>
</div>

<div class="admin-card">
    <?php if (empty($entries)): ?>

        <div class="empty-state">
            <span class="empty-icon" aria-hidden="true"><i class="bi bi-journal-text"></i></span>
            <h2 class="h5">Nothing logged yet</h2>
            <p class="text-body-secondary mb-0">
                Administrator actions such as status changes and stock updates appear here.
            </p>
        </div>

    <?php else: ?>

        <div class="table-responsive">
            <table class="table admin-table js-datatable">
                <thead>
                    <tr>
                        <th scope="col">Action</th>
                        <th scope="col">What happened</th>
                        <th scope="col">By</th>
                        <th scope="col">When</th>
                        <th scope="col">From</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($entries as $entry): ?>
                        <?php
                        $style = $actionStyles[$entry['action_type']]
                            ?? ['icon' => 'record-circle', 'class' => 'metric-icon-blue'];

                        // "order_status_update" reads better as "order status update".
                        $actionLabel = str_replace('_', ' ', $entry['action_type']);
                        ?>
                        <tr>
                            <td>
                                <span class="log-action">
                                    <span class="log-icon <?= e($style['class']) ?>" aria-hidden="true">
                                        <i class="bi bi-<?= e($style['icon']) ?>"></i>
                                    </span>
                                    <span class="text-capitalize"><?= e($actionLabel) ?></span>
                                </span>
                            </td>

                            <td><?= e($entry['description']) ?></td>

                            <td>
                                <?= e($entry['user_name']) ?>
                                <span class="d-block text-body-secondary small"><?= e($entry['user_email']) ?></span>
                            </td>

                            <td class="text-nowrap"><?= e(date('j M Y, g:i a', strtotime($entry['logged_at']))) ?></td>

                            <td class="text-body-secondary small"><?= e($entry['ip_address'] ?? 'unknown') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php endif; ?>
</div>