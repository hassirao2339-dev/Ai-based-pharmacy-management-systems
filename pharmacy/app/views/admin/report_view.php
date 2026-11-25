<?php
/**
 * report_view.php - a report on screen
 * Controller: admin/reports.php
 *
 * The tables come from report_tables.php, the same file the PDF uses, so the
 * screen and the file can never show different numbers.
 */
?>
<div class="admin-card-head">
    <p class="text-body-secondary mb-0">
        <?php if ($usesDateRange): ?>
            <?= e(date('j F Y', strtotime($fromDate))) ?>
            to <?= e(date('j F Y', strtotime($toDate))) ?>
        <?php else: ?>
            The position as at <?= e($generatedAt) ?>
        <?php endif; ?>
    </p>

    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-soft" href="<?= e(url('admin/reports.php')) ?>">
            <i class="bi bi-arrow-left" aria-hidden="true"></i> All reports
        </a>

        <?php if ($pdfAvailable): ?>
            <?php
            // The same address as this page, with format=pdf added.
            $pdfQuery = 'type=' . $type;

            if ($usesDateRange) {
                $pdfQuery .= '&from=' . urlencode($fromDate) . '&to=' . urlencode($toDate);
            }
            ?>
            <a class="btn btn-primary" href="<?= e(url('admin/reports.php?' . $pdfQuery . '&format=pdf')) ?>">
                <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> Download PDF
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="admin-card report-screen">
    <?php require APP_PATH . '/views/admin/report_tables.php'; ?>
</div>