<?php

/**
 * pdf.php - turning report HTML into a PDF
 *
 * FR-55: "The system shall allow the Administrator to export any report as a
 *        PDF document."
 * SRS 4.3: Dompdf is the library used for this.
 *
 * Dompdf takes ordinary HTML and CSS and draws it as a PDF, so a report is
 * written once as an HTML view and used for both the screen and the file.
 *
 * Dompdf is loaded only when a PDF is actually being made, because it is a
 * large library and every other page would pay for it otherwise.
 */

/**
 * Whether the Dompdf library is installed.
 *
 * The reports page uses this to explain what is missing instead of crashing.
 *
 * @return bool True when Dompdf can be loaded.
 */
function isPdfAvailable(): bool
{
    return is_file(APP_PATH . '/libraries/dompdf/autoload.inc.php');
}

/**
 * Send an HTML report to the browser as a PDF file (FR-55).
 *
 * This ends the script, because a PDF and a normal page cannot both be sent.
 *
 * @param string $html       The complete HTML of the report.
 * @param string $fileName   What the downloaded file should be called.
 * @param string $paperSize  A4 for most reports.
 * @param string $paperStyle 'portrait' or 'landscape'.
 * @return void
 */
function sendPdf(string $html, string $fileName, string $paperSize = 'A4', string $paperStyle = 'portrait'): void
{
    if (!isPdfAvailable()) {
        showErrorPage(
            500,
            'PDF export is not available',
            'The Dompdf library is missing from app/libraries/dompdf. Please install it and try again.'
        );
    }

    require_once APP_PATH . '/libraries/dompdf/autoload.inc.php';

    $options = new \Dompdf\Options();

    // The report HTML is built by this project, never by a user, so remote
    // content is switched off: a PDF should not fetch anything from the web.
    $options->set('isRemoteEnabled', false);
    $options->set('isHtml5ParserEnabled', true);
    $options->set('defaultFont', 'DejaVu Sans');
        $options->set('chroot', ROOT_PATH);

    $dompdf = new \Dompdf\Dompdf($options);

    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper($paperSize, $paperStyle);
    $dompdf->render();

    // "attachment" makes the browser download the file rather than show it.
    $dompdf->stream($fileName, ['Attachment' => true]);

    exit;
}

/**
 * Capture a view file as a string instead of printing it.
 *
 * Output buffering lets the same report view be used twice: printed on the
 * screen, or captured here and handed to Dompdf.
 *
 * @param string $view The view file under app/views, without the .php.
 * @param array  $data Variables the view needs.
 * @return string The view's HTML.
 */
function captureView(string $view, array $data = []): string
{
    extract($data, EXTR_SKIP);

    $viewPath = APP_PATH . '/views/' . $view . '.php';

    if (!is_file($viewPath)) {
        return '';
    }

    ob_start();
    require $viewPath;

    return (string) ob_get_clean();
}