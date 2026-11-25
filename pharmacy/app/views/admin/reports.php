<?php
/**
 * reports.php - the report chooser
 * Controller: admin/reports.php
 *
 * Variables:
 *   $fromDate, $toDate  the dates the range reports start with
 *   $recentReports      the reports generated so far
 *   $pdfAvailable       false when the Dompdf library is missing
 */

// The four reports, and what each of them answers.
$reports = [
    [
        'type' => 'sales',
        'title' => 'Sales report',
        'text' => 'What was sold, what it earned, and which days were busiest.',
        'icon' => 'graph-up',
        'class' => 'metric-icon-green',
        'range' => true,
        'fr' => 'FR-51',
    ],
    [
        'type' => 'inventory',
        'title' => 'Inventory report',
        'text' => 'Current stock of every medicine, with cost and retail value.',
        'icon' => 'boxes',
        'class' => 'metric-icon-blue',
        'range' => false,
        'fr' => 'FR-52',
    ],
    [
        'type' => 'orders',
        'title' => 'Order report',
        'text' => 'Orders grouped by status, with the full list for the period.',
        'icon' => 'receipt',
        'class' => 'metric-icon-purple',
        'range' => true,
        'fr' => 'FR-53',
    ],
    [
        'type' => 'low_stock',
        'title' => 'Low-stock report',
        'text' => 'What to reorder, how much, and from which supplier.',
        'icon' => 'bell',
        'class' => 'metric-icon-amber',
        'range' => false,
        'fr' => 'FR-54',
    ],
];
?>
<div class="admin-card-head">
    <p class="text-body-secondary mb-0">
        Four reports, each one viewable on screen or downloadable as a PDF.
        Sales figures use the price each order was placed at.
    </p>
</div>

<?php if (!$pdfAvailable): ?>
    <div class="alert alert-warning d-flex gap-2" role="alert">
        <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
        <span>
            <strong>PDF export is switched off.</strong>
            The Dompdf library is not in <code>app/libraries/dompdf</code>.
            Reports still work on screen.
        </span>
    </div>
<?php endif; ?>

<!-- ============ Date range for the two range reports ============ -->
<div class="admin-card mb-4">
    <h2 class="admin-card-title mb-1">Reporting period</h2>
    <p class="text-body-secondary small mb-3">
        Used by the sales and order reports. The other two always show today's position.
    </p>

    <form method="get" action="<?= e(url('admin/reports.php')) ?>" class="row g-3 align-items-end" id="reportRange">
        <div class="col-sm-4">
            <label for="from" class="form-label">From</label>
            <input type="date" id="from" name="from" class="form-control"
                   value="<?= e($fromDate) ?>" max="<?= e(date('Y-m-d')) ?>">
        </div>

        <div class="col-sm-4">
            <label for="to" class="form-label">To</label>
            <input type="date" id="to" name="to" class="form-control"
                   value="<?= e($toDate) ?>" max="<?= e(date('Y-m-d')) ?>">
        </div>

        <div class="col-sm-4">
            <div class="d-flex flex-wrap gap-2">
                <a class="chip" href="<?= e(url('admin/reports.php?from=' . date('Y-m-d') . '&to=' . date('Y-m-d'))) ?>">Today</a>
                <a class="chip" href="<?= e(url('admin/reports.php?from=' . date('Y-m-01') . '&to=' . date('Y-m-d'))) ?>">This month</a>
                <a class="chip" href="<?= e(url('admin/reports.php?from=' . date('Y-m-d', strtotime('-30 days')) . '&to=' . date('Y-m-d'))) ?>">Last 30 days</a>
            </div>
        </div>
    </form>
</div>

<!-- ============ The four reports ============ -->
<div class="row g-3 g-lg-4 mb-4">
    <?php foreach ($reports as $report): ?>
        <?php
        // Range reports carry the chosen dates; snapshot reports do not need them.
        $query = 'type=' . $report['type'];

        if ($report['range']) {
            $query .= '&from=' . urlencode($fromDate) . '&to=' . urlencode($toDate);
        }
        ?>
        <div class="col-md-6">
            <div class="admin-card h-100">
                <div class="d-flex gap-3 mb-3">
                    <span class="metric-icon <?= e($report['class']) ?>" aria-hidden="true">
                        <i class="bi bi-<?= e($report['icon']) ?>"></i>
                    </span>
                    <div>
                        <h2 class="admin-card-title mb-1"><?= e($report['title']) ?></h2>
                        <p class="text-body-secondary small mb-0"><?= e($report['text']) ?></p>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-primary btn-sm" href="<?= e(url('admin/reports.php?' . $query)) ?>">
                        <i class="bi bi-eye" aria-hidden="true"></i> View
                    </a>

                    <?php if ($pdfAvailable): ?>
                        <a class="btn btn-soft btn-sm" href="<?= e(url('admin/reports.php?' . $query . '&format=pdf')) ?>">
                            <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> Download PDF
                        </a>
                    <?php endif; ?>

                    <span class="ms-auto align-self-center text-body-secondary small"><?= e($report['fr']) ?></span>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- ============ What has been generated (FR-56) ============ -->
<div class="admin-card">
    <h2 class="admin-card-title mb-1">Recently generated</h2>
    <p class="text-body-secondary small mb-3">
        Every report that is opened or downloaded is recorded here and in the audit log.
    </p>

    <?php if (empty($recentReports)): ?>
        <p class="text-body-secondary mb-0">No reports have been generated yet.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table admin-table">
                <thead>
                    <tr>
                        <th scope="col">Report</th>
                        <th scope="col">Period</th>
                        <th scope="col">Generated by</th>
                        <th scope="col">When</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentReports as $recent): ?>
                        <tr>
                            <td class="text-capitalize"><?= e(str_replace('_', ' ', $recent['report_type'])) ?></td>
                            <td class="text-nowrap">
                                <?php if ($recent['date_from'] === null): ?>
                                    <span class="text-body-secondary">Snapshot</span>
                                <?php else: ?>
                                    <?= e(date('j M', strtotime($recent['date_from']))) ?>
                                    &ndash;
                                    <?= e(date('j M Y', strtotime($recent['date_to']))) ?>
                                <?php endif; ?>
                            </td>
                            <td><?= e($recent['admin_name']) ?></td>
                            <td class="text-nowrap"><?= e(date('j M Y, g:i a', strtotime($recent['generated_at']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>