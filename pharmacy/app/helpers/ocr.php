<?php

/**
 * ocr.php - running the Python OCR script from PHP
 *
 * FR-11: extract text from an uploaded prescription image.
 * FR-12: return a confidence score with the text.
 *
 * PHP starts python/ocr_prescription.py as a separate program, waits for it,
 * and reads the one line of JSON it prints.
 *
 * Why proc_open with an ARRAY of arguments:
 *   Passing an array (PHP 7.4 and newer) starts Python directly, without a
 *   command shell in between. Nothing the user uploads can be read as a shell
 *   command, so this cannot be used for command injection.
 *
 * Why the output goes to temporary files:
 *   On Windows, PHP cannot wait on a pipe with a time limit. Writing the output
 *   to a file and checking whether the program is still running lets us stop a
 *   stuck OCR run after OCR_TIMEOUT_SECONDS.
 */

/**
 * Read the text from a prescription image (FR-11, FR-12).
 *
 * @param string $imagePath Full path of the image in private storage.
 * @return array success, text, lines, confidence, word_count, error
 */
function runPrescriptionOcr(string $imagePath): array
{
    $failure = function (string $message): array {
        return [
            'success' => false,
            'text' => '',
            'lines' => [],
            'confidence' => 0.0,
            'word_count' => 0,
            'error' => $message,
        ];
    };

    $scriptPath = ROOT_PATH . '/python/ocr_prescription.py';

    if (!is_file(PYTHON_PATH)) {
        return $failure('Python was not found at PYTHON_PATH in .env.');
    }

    if (!is_file($scriptPath)) {
        return $failure('The OCR script python/ocr_prescription.py is missing.');
    }

    if (!is_file($imagePath)) {
        return $failure('The uploaded image could not be found.');
    }

    // Where the script's output and error messages are written.
    $outputFile = tempnam(sys_get_temp_dir(), 'ocr_out_');
    $errorFile = tempnam(sys_get_temp_dir(), 'ocr_err_');

    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['file', $outputFile, 'w'],
        2 => ['file', $errorFile, 'w'],
    ];

    // Each argument is passed on its own, so no quoting or escaping is needed.
    $command = [PYTHON_PATH, $scriptPath, $imagePath, TESSERACT_PATH];

    $process = proc_open($command, $descriptors, $pipes);

    if (!is_resource($process)) {
        @unlink($outputFile);
        @unlink($errorFile);

        return $failure('The OCR program could not be started.');
    }

    fclose($pipes[0]);

    // ---------- Wait, but never longer than the time limit ----------
    $startedAt = microtime(true);
    $timedOut = false;

    while (true) {
        $status = proc_get_status($process);

        if (!$status['running']) {
            break;
        }

        if ((microtime(true) - $startedAt) > OCR_TIMEOUT_SECONDS) {
            proc_terminate($process);
            $timedOut = true;
            break;
        }

        // Check again in a tenth of a second.
        usleep(100000);
    }

    proc_close($process);

    $output = trim((string) file_get_contents($outputFile));
    $errorOutput = trim((string) file_get_contents($errorFile));

    @unlink($outputFile);
    @unlink($errorFile);

    if ($timedOut) {
        return $failure('Reading the prescription took too long. Please try a clearer photo.');
    }

    // ---------- Read the JSON the script printed ----------
    $result = json_decode($output, true);

    if (!is_array($result) || !array_key_exists('success', $result)) {
        error_log('OCR script gave unexpected output: ' . $output . ' ' . $errorOutput);

        return $failure('The OCR program gave an unexpected answer.');
    }

    // Only the fields we expect are passed on, with the types we expect.
    return [
        'success' => (bool) $result['success'],
        'text' => (string) ($result['text'] ?? ''),
        'lines' => array_values(array_map('strval', (array) ($result['lines'] ?? []))),
        'confidence' => (float) ($result['confidence'] ?? 0),
        'word_count' => (int) ($result['word_count'] ?? 0),
        'error' => isset($result['error']) ? (string) $result['error'] : null,
    ];
}