<?php

/**
 * customer/prescription_image.php - show a prescription photo (controller)
 *
 * Prescription photos live in private storage OUTSIDE htdocs, so no link can
 * reach them directly. This page is the only way to see one, and it checks
 * that the photo belongs to the logged-in customer first.
 */

require_once __DIR__ . '/../app/bootstrap.php';

requireRole('customer');

$prescriptionModel = new Prescription($pdo);

$prescriptionId = 0;

if (isset($_GET['id'])) {
    $prescriptionId = (int) $_GET['id'];
}

$prescription = $prescriptionModel->findForCustomer($prescriptionId, (int) $_SESSION['user_id']);

if ($prescription === null) {
    showErrorPage(404, 'Not found', 'That prescription is not on your account.');
}

$filePath = $prescriptionModel->imageFilePath($prescription['image_path']);

if (!is_file($filePath)) {
    showErrorPage(404, 'Not found', 'The photo for this prescription is no longer available.');
}

// The type comes from the file's contents, and only images are ever sent.
$fileInfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = (string) $fileInfo->file($filePath);

if (!in_array($mimeType, ['image/jpeg', 'image/png'], true)) {
    showErrorPage(404, 'Not found', 'That file cannot be shown.');
}

header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($filePath));

// "nosniff" stops the browser from guessing a different type for the file.
header('X-Content-Type-Options: nosniff');

// Private: a shared computer or proxy must not keep a copy of a medical document.
header('Cache-Control: private, no-store');

readfile($filePath);
exit;