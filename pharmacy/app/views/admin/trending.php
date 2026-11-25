<?php
/**
 * trending.php - trending medicines view
 * Controller: admin/trending.php
 *
 * Variables:
 *   $medicines        the top 10, each with previous_ordered and change_percent
 *   $monthTotals      units and medicine count for this month
 *   $previousTotals   the same for last month
 *   $availableMonths  months that have any data, for the picker
 *   $year, $month     the month being shown
 *   $previousYear, $previousMonth  the month before it
 */

$monthLabel = date('F Y', mktime(0, 0, 0, $month, 1, $year));
$previousLabel = date('F Y', mktime(0, 0, 0, $previousMonth, 1, $previousYear));

// The bar chart is drawn with plain CSS widths, so each bar is a percentage
// of the best-selling medicine of the month.
$highestQuantity = 0;

foreach ($medicines as $medicine) {
    $highestQuantity = max($highestQuantity, (int) $medicine['total_ordered']);
}

// Change in total units, month on month (FR-47).
$totalChangePercent = null;

if ($previousTotals['units'] > 0) {
    $totalChangePercent = round(
        (($monthTotals['units'] - $previousTotals['units']) / $previousTotals['units']) * 100,
        1
    );
}
?>
<div class="admin-card-head">
    <p class="text-body-secondary mb-0">
        The most ordered medicines of <strong><?= e($monthLabel) ?></strong>,
        counting confirmed, dispatched and delivered orders only.
    </p>

    <!-- Month picker: plain links, so the month stays in the address -->
    <div class="d-flex flex-wrap gap-2">
        <?php
        // The current month is always offered, even when nothing was sold in it.
        $periods = [['year' => (int) date('Y'), 'month' => (int) date('n')]];

        foreach ($availableMonths as $availableMonth) {
            $periods[] = [
                'year' => (int) $availableMonth['order_year'],
                'month' => (int) $availableMonth['order_month'],
            ];
        }

        $seen = [];
        ?>
        <?php foreach ($periods as $period): ?>
            <?php
            $key = $period['year'] . '-' . $period['month'];

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $isSelected = ($period['year'] === $year && $period['month'] === $month);
            $periodLink = 'admin/trending.php?year=' . $period['year'] . '&month=' . $period['month'];
            ?>
            <a class="chip<?= $isSelected ? ' active' : '' ?>" href="<?= e(url($periodLink)) ?>">
                <?= e(date('M Y', mktime(0, 0, 0, $period['month'], 1, $period['year']))) ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- ============ Month summary (FR-47) ============ -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-4">
        <div class="metric-card">
            <span class="metric-icon metric-icon-blue" aria-hidden="true"><i class="bi bi-box-seam"></i></span>
            <span>
                <span class="metric-label">Units sold in <?= e(date('F', mktime(0, 0, 0, $month, 1, $year))) ?></span>
                <span class="metric-value"><?= e($monthTotals['units']) ?></span>
            </span>
        </div>
    </div>

    <div class="col-sm-6 col-xl-4">
        <div class="metric-card">
            <span class="metric-icon metric-icon-purple" aria-hidden="true"><i class="bi bi-capsule"></i></span>
            <span>
                <span class="metric-label">Different medicines sold</span>
                <span class="metric-value"><?= e($monthTotals['medicines']) ?></span>
            </span>
        </div>
    </div>

    <div class="col-12 col-xl-4">
        <div class="metric-card">
            <span class="metric-icon <?= ($totalChangePercent !== null && $totalChangePercent < 0) ? 'metric-icon-red' : 'metric-icon-green' ?>"
                  aria-hidden="true">
                <i class="bi bi-graph-up-arrow"></i>
            </span>
            <span>
                <span class="metric-label">Compared with <?= e($previousLabel) ?> (<?= e($previousTotals['units']) ?> units)</span>
                <span class="metric-value">
                    <?php if ($totalChangePercent === null): ?>
                        &mdash;
                    <?php else: ?>
                        <?= $totalChangePercent >= 0 ? '+' : '' ?><?= e($totalChangePercent) ?>%
                    <?php endif; ?>
                </span>
            </span>
        </div>
    </div>
</div>

<?php if (empty($medicines)): ?>

    <div class="admin-card">
        <div class="empty-state">
            <span class="empty-icon" aria-hidden="true"><i class="bi bi-graph-up-arrow"></i></span>
            <h2 class="h5">No sales in <?= e($monthLabel) ?></h2>
            <p class="text-body-secondary mb-0">
                Only confirmed, dispatched and delivered orders are counted.
                Pending orders appear here once you confirm them.
            </p>
        </div>
    </div>

<?php else: ?>

    <div class="row g-4">

        <!-- ============ The chart (FR-48) ============ -->
        <div class="col-xl-5">
            <div class="admin-card">
                <h2 class="admin-card-title mb-1">Units ordered</h2>
                <p class="text-body-secondary small mb-4">
                    Each bar is measured against the best seller of the month.
                </p>

                <?php foreach ($medicines as $rank => $medicine): ?>
                    <?php
                    $quantity = (int) $medicine['total_ordered'];
                    $barWidth = ($highestQuantity > 0) ? round(($quantity / $highestQuantity) * 100) : 0;
                    ?>
                    <div class="trend-bar-row">
                        <div class="trend-bar-head">
                            <span class="trend-bar-name">
                                <span class="rank-pill"><?= e($rank + 1) ?></span>
                                <?= e($medicine['name']) ?>
                            </span>
                            <span class="trend-bar-value"><?= e($quantity) ?></span>
                        </div>
                        <div class="trend-bar-track">
                            <span class="trend-bar-fill" style="width: <?= e($barWidth) ?>%"></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ============ The ranked list (FR-45, FR-46, FR-47) ============ -->
        <div class="col-xl-7">
            <div class="admin-card">
                <h2 class="admin-card-title mb-3">Top 10 in <?= e($monthLabel) ?></h2>

                <div class="table-responsive">
                    <table class="table admin-table">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Medicine</th>
                                <th scope="col">Units</th>
                                <th scope="col"><?= e(date('M', mktime(0, 0, 0, $previousMonth, 1, $previousYear))) ?></th>
                                <th scope="col">Change</th>
                                <th scope="col">Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($medicines as $rank => $medicine): ?>
                                <?php
                                $medicineId = (int) $medicine['medicine_id'];
                                $stock = (int) $medicine['stock_qty'];
                                $threshold = (int) $medicine['reorder_threshold'];
                                $change = $medicine['change_percent'];
                                $isLow = ($stock <= $threshold);
                                ?>
                                <tr class="<?= $isLow ? 'row-low' : '' ?>">
                                    <td><span class="rank-pill"><?= e($rank + 1) ?></span></td>

                                    <td>
                                        <a href="<?= e(url('admin/medicine_form.php?id=' . $medicineId)) ?>">
                                            <?= e($medicine['name']) ?>
                                        </a>
                                        <span class="d-block text-body-secondary small">
                                            <?= e($medicine['sku']) ?> &middot; <?= e($medicine['category']) ?>
                                        </span>
                                    </td>

                                    <td class="fw-bold"><?= e($medicine['total_ordered']) ?></td>

                                    <td><?= e($medicine['previous_ordered']) ?></td>

                                    <td class="text-nowrap">
                                        <?php if ($change === null): ?>
                                            <span class="trend-new">New</span>
                                        <?php elseif ($change > 0): ?>
                                            <span class="trend-up">
                                                <i class="bi bi-arrow-up" aria-hidden="true"></i> <?= e($change) ?>%
                                            </span>
                                        <?php elseif ($change < 0): ?>
                                            <span class="trend-down">
                                                <i class="bi bi-arrow-down" aria-hidden="true"></i> <?= e(abs($change)) ?>%
                                            </span>
                                        <?php else: ?>
                                            <span class="text-body-secondary">No change</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <span class="fw-bold"><?= e($stock) ?></span>
                                        <?php if ($isLow): ?>
                                            <span class="d-block small fw-bold text-warning-emphasis">Restock</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <p class="form-hint mt-3 mb-0">
                    <i class="bi bi-info-circle" aria-hidden="true"></i>
                    A medicine selling well with low stock is the first one to reorder.
                </p>
            </div>
        </div>

    </div>

<?php endif; ?>