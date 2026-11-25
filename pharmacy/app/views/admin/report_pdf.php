<?php
/**
 * report_pdf.php - a report as a PDF page
 * Controller: admin/reports.php (through captureView() and sendPdf())
 *
 * This is a complete HTML document, because Dompdf turns HTML into a PDF and
 * needs the whole page, not a fragment.
 *
 * The CSS here is deliberately plain: Dompdf understands basic CSS well, but
 * not flexbox, grid or CSS variables. So this file has its own styles instead
 * of loading style.css.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= e($title) ?></title>
    <style>
        @page {
            margin: 18mm 14mm 20mm 14mm;
        }

        body {
            font-family: "DejaVu Sans", sans-serif;
            font-size: 9pt;
            color: #1a2233;
        }

        /* ---------- The letterhead ---------- */
        .head {
            border-bottom: 2px solid #2563eb;
            padding-bottom: 8px;
            margin-bottom: 14px;
        }

        .brand {
            font-size: 16pt;
            font-weight: bold;
            color: #2563eb;
        }

        .brand-sub {
            font-size: 8pt;
            color: #5b6478;
        }

        .doc-title {
            font-size: 13pt;
            font-weight: bold;
            margin-top: 10px;
        }

        .doc-meta {
            font-size: 8pt;
            color: #5b6478;
            margin-top: 2px;
        }

        /* ---------- Summary strip ---------- */
        .report-summary {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        .report-summary td {
            width: 25%;
            padding: 8px 10px;
            background-color: #f5f7fb;
            border: 1px solid #e3e8f0;
        }

        .report-summary .label {
            display: block;
            font-size: 7.5pt;
            color: #5b6478;
        }

        .report-summary .value {
            display: block;
            font-size: 12pt;
            font-weight: bold;
            color: #2563eb;
        }

        /* ---------- Data tables ---------- */
        h3 {
            font-size: 10.5pt;
            margin: 14px 0 6px;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .report-table th,
        .report-table td {
            padding: 5px 7px;
            border: 1px solid #e3e8f0;
            text-align: left;
        }

        .report-table th {
            font-size: 8pt;
            text-transform: uppercase;
            background-color: #eef2ff;
        }

        .report-table .num {
            text-align: right;
        }

        .report-table tfoot td {
            font-weight: bold;
            background-color: #f5f7fb;
        }

        /* Rows that need attention */
        .report-table tr.warn td {
            background-color: #fff7ed;
        }

        .report-table tr.danger td {
            background-color: #fef2f2;
        }

        .capitalise {
            text-transform: capitalize;
        }

        .muted {
            font-size: 8pt;
            color: #5b6478;
        }

        /* ---------- Footer on every page ---------- */
        .foot {
            position: fixed;
            bottom: -14mm;
            left: 0;
            right: 0;
            font-size: 7.5pt;
            color: #5b6478;
            border-top: 1px solid #e3e8f0;
            padding-top: 4px;
        }

        .foot .right {
            float: right;
        }
    </style>
</head>
<body>

<div class="head">
    <div class="brand"><?= e(APP_NAME) ?></div>
    <div class="brand-sub">Smart pharmacy &middot; Multan, Pakistan</div>

    <div class="doc-title"><?= e($title) ?></div>
    <div class="doc-meta">
        <?php if ($usesDateRange): ?>
            Period: <?= e(date('j F Y', strtotime($fromDate))) ?>
            to <?= e(date('j F Y', strtotime($toDate))) ?> &middot;
        <?php endif; ?>
        Generated <?= e($generatedAt) ?> by <?= e($generatedBy) ?>
    </div>
</div>

<?php require APP_PATH . '/views/admin/report_tables.php'; ?>

<div class="foot">
    <?= e(APP_NAME) ?> &middot; <?= e($title) ?>
    <span class="right">Generated <?= e($generatedAt) ?></span>
</div>

</body>
</html>