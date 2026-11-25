<?php

/**
 * customer/prescription_upload.php - upload a prescription (controller)
 *
 * FR-09: upload a prescription image, JPEG or PNG, up to UPLOAD_MAX_MB.
 * FR-10: refuse any other file type with a clear message.
 * FR-11 to FR-14: read it with OCR and match the medicines.
 *
 * An uploaded file is never trusted. It is checked four ways before it is kept:
 *   1. PHP's own upload error code
 *   2. its size
 *   3. its real type, read from the file's contents (not its name)
 *   4. that it actually opens as an image
 * Then it is saved under a random name in private storage, outside htdocs.
 */

require_once __DIR__ . '/../app/bootstrap.php';

requireRole('customer');

$errorMessage = '';

if (isPostRequest()) {
    verifyCsrf();

    $upload = $_FILES['prescription'] ?? null;
    $maxBytes = UPLOAD_MAX_MB * 1024 * 1024;

    // The only types allowed, mapped to the extension the file will be saved with.
    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    // ---------- 1. Did the upload itself work? ----------
    if (!is_array($upload) || !isset($upload['error']) || is_array($upload['error'])) {
        $errorMessage = 'Please choose a photo of your prescription.';
    } elseif ($upload['error'] === UPLOAD_ERR_NO_FILE) {
        $errorMessage = 'Please choose a photo of your prescription.';
    } elseif ($upload['error'] === UPLOAD_ERR_INI_SIZE || $upload['error'] === UPLOAD_ERR_FORM_SIZE) {
        $errorMessage = 'That photo is too large. The limit is ' . UPLOAD_MAX_MB . ' MB.';
    } elseif ($upload['error'] !== UPLOAD_ERR_OK) {
        $errorMessage = 'The upload did not finish. Please try again.';
    }

    // ---------- 2. Size ----------
    if ($errorMessage === '' && ($upload['size'] <= 0 || $upload['size'] > $maxBytes)) {
        $errorMessage = 'That photo is too large. The limit is ' . UPLOAD_MAX_MB . ' MB.';
    }

    // ---------- 3. The real file type, read from its contents (FR-10) ----------
    // A file called "photo.jpg" could really be anything. finfo looks inside it.
    $realType = '';

    if ($errorMessage === '') {
        if (!is_uploaded_file($upload['tmp_name'])) {
            $errorMessage = 'The upload did not finish. Please try again.';
        } else {
            $fileInfo = new finfo(FILEINFO_MIME_TYPE);
            $realType = (string) $fileInfo->file($upload['tmp_name']);

            if (!isset($allowedTypes[$realType])) {
                $errorMessage = 'Only JPEG and PNG photos can be uploaded. Please take a photo of your prescription and try again.';
            }
        }
    }

    // ---------- 4. It must really open as an image ----------
    if ($errorMessage === '') {
        $imageSize = @getimagesize($upload['tmp_name']);

        if ($imageSize === false || $imageSize[0] < 200 || $imageSize[1] < 200) {
            $errorMessage = 'That file could not be read as a photo, or it is too small to read. Please try a clearer photo.';
        }
    }

    // ---------- Save it in private storage ----------
    if ($errorMessage === '') {
        if (!is_dir(PRESCRIPTION_PATH)) {
            @mkdir(PRESCRIPTION_PATH, 0750, true);
        }

        // A random name: the customer's own file name is never used, so it can
        // never overwrite another file or carry a dangerous name.
        $fileName = bin2hex(random_bytes(16)) . '.' . $allowedTypes[$realType];
        $destination = PRESCRIPTION_PATH . '/' . $fileName;

        if (!move_uploaded_file($upload['tmp_name'], $destination)) {
            error_log('Could not save prescription to ' . $destination);
            $errorMessage = 'We could not save your photo. Please try again.';
        }
    }

    // ---------- Read it (FR-11 to FR-14) ----------
    if ($errorMessage === '') {
        $prescriptionModel = new Prescription($pdo);
        $prescriptionId = $prescriptionModel->create((int) $_SESSION['user_id'], $fileName);

        // OCR can take several seconds on a phone photo.
        set_time_limit(OCR_TIMEOUT_SECONDS + 30);

        $ocr = runPrescriptionOcr($destination);

        $matches = ['found' => [], 'not_found' => []];

        if ($ocr['success']) {
            $matcher = new MedicineMatcher($pdo);
            $matches = $matcher->match($ocr['lines']);
        } else {
            error_log('OCR failed for prescription ' . $prescriptionId . ': ' . $ocr['error']);
        }

        $prescriptionModel->saveResult($prescriptionId, $ocr, $matches);

        redirect('customer/prescription_review.php?id=' . $prescriptionId);
    }
}

render('customer/prescription_upload', [
    'pageTitle' => 'Upload prescription',
    'errorMessage' => $errorMessage,
]);