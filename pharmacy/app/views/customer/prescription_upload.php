<?php
/**
 * prescription_upload.php - upload a prescription view
 * Controller: customer/prescription_upload.php
 *
 * Variables:
 *   $errorMessage  what went wrong with the last upload, or ''
 */
?>
<section class="page-head">
    <div class="container">
        <h1 class="h2 mb-1">Upload your prescription</h1>
        <p class="text-body-secondary mb-0">
            Our system reads the medicine names for you and suggests them from our catalogue.
            You check everything before it goes in your cart.
        </p>
    </div>
</section>

<section class="container py-4 py-lg-5">
    <div class="row g-4">

        <!-- ============ The upload form (FR-09) ============ -->
        <div class="col-lg-7">
            <div class="settings-card">

                <?php if ($errorMessage !== ''): ?>
                    <div class="alert alert-danger d-flex gap-2" role="alert">
                        <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
                        <span><?= e($errorMessage) ?></span>
                    </div>
                <?php endif; ?>

                <!-- enctype is required, or the browser sends no file at all -->
                <form method="post" action="<?= e(url('customer/prescription_upload.php')) ?>"
                      enctype="multipart/form-data" id="prescriptionForm">
                    <?= csrfField() ?>

                    <label for="prescription" class="upload-drop">
                        <span class="upload-icon" aria-hidden="true"><i class="bi bi-cloud-arrow-up"></i></span>
                        <span class="upload-title">Choose a photo of your prescription</span>
                        <span class="upload-text">JPEG or PNG, up to <?= e(UPLOAD_MAX_MB) ?> MB</span>

                        <input type="file" id="prescription" name="prescription" required
                               accept="image/jpeg,image/png" class="form-control mt-3">
                    </label>

                    <button type="submit" class="btn btn-primary btn-lg w-100 mt-4" id="prescriptionSubmit">
                        <i class="bi bi-magic" aria-hidden="true"></i> Read my prescription
                    </button>

                    <p class="form-hint text-center mt-2 mb-0">
                        Reading usually takes a few seconds.
                    </p>
                </form>
            </div>

            <a class="see-all mt-3 d-inline-flex" href="<?= e(url('customer/prescriptions.php')) ?>">
                <i class="bi bi-clock-history" aria-hidden="true"></i> My past prescriptions
            </a>
        </div>

        <!-- ============ How to take a good photo ============ -->
        <div class="col-lg-5">
            <div class="settings-card mb-4">
                <h2 class="h5 mb-3">For the best result</h2>

                <ul class="tip-list">
                    <li><i class="bi bi-brightness-high" aria-hidden="true"></i> Use good light, with no shadow across the page.</li>
                    <li><i class="bi bi-phone" aria-hidden="true"></i> Hold the phone straight above the page.</li>
                    <li><i class="bi bi-aspect-ratio" aria-hidden="true"></i> Fill the photo with the prescription.</li>
                    <li><i class="bi bi-type" aria-hidden="true"></i> Printed prescriptions read best. Handwriting may be missed.</li>
                </ul>
            </div>

            <div class="settings-card">
                <h2 class="h5 mb-3">What happens next</h2>

                <ol class="track-steps">
                    <li class="is-current">
                        <span class="track-dot" aria-hidden="true"></span>
                        <div>
                            <strong>We read the photo</strong>
                            <span class="track-meta">The medicine names are picked out of the text.</span>
                        </div>
                    </li>
                    <li class="is-upcoming">
                        <span class="track-dot" aria-hidden="true"></span>
                        <div>
                            <strong>You check the matches</strong>
                            <span class="track-meta">Each one shows how sure we are.</span>
                        </div>
                    </li>
                    <li class="is-upcoming">
                        <span class="track-dot" aria-hidden="true"></span>
                        <div>
                            <strong>They go in your cart</strong>
                            <span class="track-meta">Only the ones you tick.</span>
                        </div>
                    </li>
                </ol>

                <p class="form-hint mt-3 mb-0">
                    <i class="bi bi-shield-lock" aria-hidden="true"></i>
                    Your prescription is stored privately and only you can see it.
                </p>
            </div>
        </div>

    </div>
</section>