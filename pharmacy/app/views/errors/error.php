<?php
/**
 * error.php - friendly error page view
 * Called by showErrorPage() in app/helpers/functions.php
 *
 * Variables: $statusCode, $errorTitle, $errorMessage, $technicalDetails
 */
?>
<section class="container py-5">
    <div class="content-panel mx-auto">
        <p class="error-code mb-2">Error <?= e($statusCode) ?></p>
        <h1 class="h2 mb-3"><?= e($errorTitle) ?></h1>
        <p class="mb-4"><?= e($errorMessage) ?></p>

        <?php if ($technicalDetails !== null): ?>
            <div class="alert alert-secondary small">
                <strong>Technical details</strong> (shown only because APP_DEBUG is true):
                <pre class="mb-0 mt-2 text-wrap"><?= e($technicalDetails) ?></pre>
            </div>
        <?php endif; ?>

        <a class="btn btn-primary" href="<?= e(url('index.php')) ?>">Go to homepage</a>
    </div>
</section>