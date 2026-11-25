<?php
/**
 * main_footer.php
 *
 * Closes the page: dark footer, JavaScript files and the closing HTML tags.
 *
 * $layoutUser, $layoutIsCustomer and $layoutIsAdmin were created in
 * main_header.php. Both files are loaded inside the same render() call,
 * so the footer can use them too.
 */
?>
</main>

<footer class="pms-footer">
    <div class="container pt-5 pb-4">
        <div class="row g-4 g-lg-5">
            <div class="col-lg-4">
                <a class="pms-brand" href="<?= e(url('index.php')) ?>">
                    <span class="brand-logo" aria-hidden="true"><i class="bi bi-capsule-pill"></i></span>
                    <span class="brand-text">
                        <span class="brand-name"><?= e(APP_NAME) ?></span>
                        <span class="brand-tag">Smart pharmacy</span>
                    </span>
                </a>
                <p class="footer-about">
                    Upload your prescription and we'll add the medicines to your cart. Browse the catalogue,
                    track your orders and pay cash on delivery.
                </p>
            </div>

            <div class="col-6 col-md-4 col-lg-2">
                <h2 class="footer-heading">Shop</h2>
                <ul class="footer-links">
                    <li><a href="<?= e(url('catalogue.php')) ?>">Medicines</a></li>
                    <?php if (!$layoutIsAdmin): ?>
                        <li><a href="<?= e(url('customer/prescription_upload.php')) ?>">Upload prescription</a></li>
                        <li><a href="<?= e(url('customer/cart.php')) ?>">Cart</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="col-6 col-md-4 col-lg-2">
                <h2 class="footer-heading">Your account</h2>
                <ul class="footer-links">
                    <?php if ($layoutUser === null): ?>
                        <li><a href="<?= e(url('auth/login.php')) ?>">Log in</a></li>
                        <li><a href="<?= e(url('auth/register.php')) ?>">Create account</a></li>
                    <?php elseif ($layoutIsCustomer): ?>
                        <li><a href="<?= e(url('customer/dashboard.php')) ?>">Dashboard</a></li>
                        <li><a href="<?= e(url('customer/orders.php')) ?>">My orders</a></li>
                        <li><a href="<?= e(url('customer/prescriptions.php')) ?>">Prescription history</a></li>
                        <li><a href="<?= e(url('customer/profile.php')) ?>">Profile</a></li>
                    <?php else: ?>
                        <li><a href="<?= e(url('admin/dashboard.php')) ?>">Admin dashboard</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="col-md-4 col-lg-4">
                <h2 class="footer-heading">Ordering</h2>
                <ul class="footer-info">
                    <li><i class="bi bi-clock" aria-hidden="true"></i><span>Open 8 AM to 10 PM (PKT)</span></li>
                    <li><i class="bi bi-cash-coin" aria-hidden="true"></i><span>Cash on delivery only</span></li>
                    <li><i class="bi bi-file-earmark-image" aria-hidden="true"></i><span>Prescriptions in JPEG or PNG, up to <?= e(UPLOAD_MAX_MB) ?> MB</span></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mt-5 pt-4">
            <span>&copy; <?= e(date('Y')) ?> <?= e(APP_NAME) ?>. Final Year Project, University of Southern Punjab, Multan.</span>
            <div class="d-flex flex-wrap gap-2">
                <span class="footer-badge"><i class="bi bi-shield-lock" aria-hidden="true"></i> Secure login</span>
                <span class="footer-badge"><i class="bi bi-cash-coin" aria-hidden="true"></i> Cash on delivery</span>
            </div>
        </div>
    </div>
</footer>

<!-- jQuery first, then Bootstrap (includes Popper for dropdowns), then our own script. -->
<script src="<?= e(asset('vendor/jquery/jquery.min.js')) ?>"></script>
<script src="<?= e(asset('vendor/bootstrap/bootstrap.bundle.min.js')) ?>"></script>
<script src="<?= e(asset('js/main.js')) ?>"></script>
</body>
</html>