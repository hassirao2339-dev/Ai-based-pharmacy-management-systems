<?php
/**
 * report_tables.php - the tables of a report
 *
 * Used twice:
 *   1. report_view.php shows it on screen
 *   2. report_pdf.php puts it inside the PDF
 *
 * Writing the tables once means the PDF can never drift away from the screen
 * version. The two wrappers only differ in their styling.
 *
 * Needs: $type and whichever data that report uses.
 */
?>
<?php if ($type === 'sales'): ?>

    <!-- ============ Sales report (FR-51) ============ -->
    <table class="report-summary">
        <tr>
            <td><span class="label">Orders</span><span class="value"><?= e($summary['orders']) ?></span></td>
            <td><span class="label">Units sold</span><span class="value"><?= e($summary['units']) ?></span></td>
            <td><span class="label">Revenue</span><span class="value"><?= e(formatPrice((float) $summary['revenue'])) ?></span></td>
            <td><span class="label">Average order</span><span class="value"><?= e(formatPrice((float) $summary['average_order'])) ?></span></td>
        </tr>
    </table>

    <h3>Sales by medicine</h3>

    <?php if (empty($byMedicine)): ?>
        <p class="muted">Nothing was sold in this period.</p>
    <?php else: ?>
        <table class="report-table">
            <thead>
                <tr>
                    <th>Medicine</th>
                    <th>SKU</th>
                    <th>Category</th>
                    <th class="num">Units</th>
                    <th class="num">Revenue</th>
                    <th class="num">Share</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($byMedicine as $row): ?>
                    <?php
                    $share = ((float) $summary['revenue'] > 0)
                        ? round(((float) $row['revenue'] / (float) $summary['revenue']) * 100, 1)
                        : 0;
                    ?>
                    <tr>
                        <td><?= e($row['name']) ?></td>
                        <td><?= e($row['sku']) ?></td>
                        <td><?= e($row['category']) ?></td>
                        <td class="num"><?= e($row['units']) ?></td>
                        <td class="num"><?= e(formatPrice((float) $row['revenue'])) ?></td>
                        <td class="num"><?= e($share) ?>%</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3">Total</td>
                    <td class="num"><?= e($summary['units']) ?></td>
                    <td class="num"><?= e(formatPrice((float) $summary['revenue'])) ?></td>
                    <td class="num">100%</td>
                </tr>
            </tfoot>
        </table>
    <?php endif; ?>

    <?php if (!empty($byDay)): ?>
        <h3>Day by day</h3>
        <table class="report-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th class="num">Orders</th>
                    <th class="num">Revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($byDay as $day): ?>
                    <tr>
                        <td><?= e(date('j M Y (D)', strtotime($day['sale_date']))) ?></td>
                        <td class="num"><?= e($day['orders']) ?></td>
                        <td class="num"><?= e(formatPrice((float) $day['revenue'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

<?php elseif ($type === 'inventory'): ?>

    <!-- ============ Inventory report (FR-52) ============ -->
    <?php
    $totalUnits = 0;
    $totalCost = 0.0;
    $totalRetail = 0.0;

    foreach ($rows as $row) {
        $totalUnits += (int) $row['stock_qty'];
        $totalCost += (float) $row['cost_value'];
        $totalRetail += (float) $row['retail_value'];
    }
    ?>

    <table class="report-summary">
        <tr>
            <td><span class="label">Medicines</span><span class="value"><?= e(count($rows)) ?></span></td>
            <td><span class="label">Units in stock</span><span class="value"><?= e($totalUnits) ?></span></td>
            <td><span class="label">Stock at cost</span><span class="value"><?= e(formatPrice($totalCost)) ?></span></td>
            <td><span class="label">Stock at retail</span><span class="value"><?= e(formatPrice($totalRetail)) ?></span></td>
        </tr>
    </table>

    <h3>Stock by medicine</h3>

    <table class="report-table">
        <thead>
            <tr>
                <th>Medicine</th>
                <th>SKU</th>
                <th>Category</th>
                <th class="num">Stock</th>
                <th class="num">Threshold</th>
                <th class="num">Batches</th>
                <th>Next expiry</th>
                <th class="num">Cost value</th>
                <th class="num">Retail value</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
                <?php $isLow = ((int) $row['stock_qty'] <= (int) $row['reorder_threshold']); ?>
                <tr class="<?= $isLow ? 'warn' : '' ?>">
                    <td><?= e($row['name']) ?></td>
                    <td><?= e($row['sku']) ?></td>
                    <td><?= e($row['category']) ?></td>
                    <td class="num"><?= e($row['stock_qty']) ?><?= $isLow ? ' *' : '' ?></td>
                    <td class="num"><?= e($row['reorder_threshold']) ?></td>
                    <td class="num"><?= e($row['live_batches']) ?></td>
                    <td>
                        <?php if ($row['expiry_date'] === null): ?>
                            &mdash;
                        <?php else: ?>
                            <?= e(date('M Y', strtotime($row['expiry_date']))) ?>
                        <?php endif; ?>
                    </td>
                    <td class="num"><?= e(formatPrice((float) $row['cost_value'])) ?></td>
                    <td class="num"><?= e(formatPrice((float) $row['retail_value'])) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">Total</td>
                <td class="num"><?= e($totalUnits) ?></td>
                <td colspan="3"></td>
                <td class="num"><?= e(formatPrice($totalCost)) ?></td>
                <td class="num"><?= e(formatPrice($totalRetail)) ?></td>
            </tr>
        </tfoot>
    </table>

    <p class="muted">* at or below the reorder threshold</p>

<?php elseif ($type === 'orders'): ?>

    <!-- ============ Order report (FR-53) ============ -->
    <?php
    $totalOrders = 0;
    $totalValue = 0.0;

    foreach ($byStatus as $statusRow) {
        $totalOrders += (int) $statusRow['orders'];
        $totalValue += (float) $statusRow['total_value'];
    }
    ?>

    <h3>Orders by status</h3>

    <?php if (empty($byStatus)): ?>
        <p class="muted">No orders were placed in this period.</p>
    <?php else: ?>
        <table class="report-table">
            <thead>
                <tr>
                    <th>Status</th>
                    <th class="num">Orders</th>
                    <th class="num">Share</th>
                    <th class="num">Value</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($byStatus as $statusRow): ?>
                    <?php
                    $share = ($totalOrders > 0)
                        ? round(((int) $statusRow['orders'] / $totalOrders) * 100, 1)
                        : 0;
                    ?>
                    <tr>
                        <td class="capitalise"><?= e($statusRow['status']) ?></td>
                        <td class="num"><?= e($statusRow['orders']) ?></td>
                        <td class="num"><?= e($share) ?>%</td>
                        <td class="num"><?= e(formatPrice((float) $statusRow['total_value'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td>Total</td>
                    <td class="num"><?= e($totalOrders) ?></td>
                    <td class="num">100%</td>
                    <td class="num"><?= e(formatPrice($totalValue)) ?></td>
                </tr>
            </tfoot>
        </table>

        <h3>All orders in this period</h3>
        <table class="report-table">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th class="num">Items</th>
                    <th>Status</th>
                    <th class="num">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>#<?= e(str_pad((string) $order['order_id'], 4, '0', STR_PAD_LEFT)) ?></td>
                        <td><?= e(date('j M Y', strtotime($order['order_date']))) ?></td>
                        <td><?= e($order['customer_name']) ?></td>
                        <td class="num"><?= e($order['item_count']) ?></td>
                        <td class="capitalise"><?= e($order['status']) ?></td>
                        <td class="num"><?= e(formatPrice((float) $order['total_amount'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

<?php else: ?>

    <!-- ============ Low-stock report (FR-54) ============ -->
    <?php
    $suggestedTotal = 0;
    $estimatedCost = 0.0;

    foreach ($rows as $row) {
        $suggestedTotal += (int) $row['suggested_order'];

        // A rough idea only: the real cost depends on what the supplier charges.
        $estimatedCost += (int) $row['suggested_order'] * (float) $row['price'] * 0.6;
    }
    ?>

    <table class="report-summary">
        <tr>
            <td><span class="label">Medicines to reorder</span><span class="value"><?= e(count($rows)) ?></span></td>
            <td><span class="label">Units suggested</span><span class="value"><?= e($suggestedTotal) ?></span></td>
            <td><span class="label">Rough cost estimate</span><span class="value"><?= e(formatPrice($estimatedCost)) ?></span></td>
        </tr>
    </table>

    <h3>What to reorder</h3>

    <?php if (empty($rows)): ?>
        <p class="muted">Every medicine is above its reorder threshold.</p>
    <?php else: ?>
        <table class="report-table">
            <thead>
                <tr>
                    <th>Medicine</th>
                    <th>SKU</th>
                    <th>Category</th>
                    <th class="num">In stock</th>
                    <th class="num">Threshold</th>
                    <th class="num">Suggested order</th>
                    <th>Last supplier</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr class="<?= (int) $row['stock_qty'] === 0 ? 'danger' : 'warn' ?>">
                        <td><?= e($row['name']) ?></td>
                        <td><?= e($row['sku']) ?></td>
                        <td><?= e($row['category']) ?></td>
                        <td class="num"><?= e($row['stock_qty']) ?></td>
                        <td class="num"><?= e($row['reorder_threshold']) ?></td>
                        <td class="num"><?= e($row['suggested_order']) ?></td>
                        <td><?= e($row['last_supplier'] ?? '—') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p class="muted">
            The suggested order brings each medicine up to twice its threshold.
            The cost estimate assumes a cost price of about 60% of the retail price.
        </p>
    <?php endif; ?>

<?php endif; ?>