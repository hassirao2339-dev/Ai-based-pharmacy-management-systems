<?php
/**
 * home.php - homepage view
 * Controller: index.php
 *
 * Variables:
 *   $user               null for guests, otherwise ['id', 'name', 'role']
 *   $categories         list of category names           (filled in Phase 4)
 *   $latestMedicines    list of medicine rows             (filled in Phase 4)
 *   $trendingMedicines  list of the month's top medicines (filled in Phase 9)
 *
 * Sections:
 *   1. Hero (dark, with example scan card)
 *   2. Search box and category chips
 *   3. Popular this month   (only when $trendingMedicines has rows)
 *   4. Browse medicines     (only when $latestMedicines has rows)
 *   5. How it works
 *   6. Call to action (changes for guest / customer / admin)
 */

$isGuest = ($user === null);
$isCustomer = (!$isGuest && $user['role'] === 'customer');
$isAdmin = (!$isGuest && $user['role'] === 'admin');
?>

<!-- =========================== 1. HERO =========================== -->
<section class="home-hero">
    <span class="hero-glow hero-glow-blue" aria-hidden="true"></span>
    <span class="hero-glow hero-glow-purple" aria-hidden="true"></span>

    <div class="container">
        <div class="row align-items-center g-5">

            <div class="col-lg-7">
                <span class="hero-badge">
                    <span class="hero-badge-dot" aria-hidden="true"></span>
                    AI prescription scanning
                </span>

                <h1 class="hero-title">
                    Order medicines with <span class="text-gradient">smart prescription AI</span>
                </h1>

                <p class="hero-lead">
                    Upload a photo of your prescription. We read the medicine names, find them in our stock
                    and add them to your cart. Check everything, then pay cash when your order arrives.
                </p>

                <div class="d-flex flex-wrap gap-3">
                    <?php if ($isAdmin): ?>
                        <a class="btn btn-primary btn-lg" href="<?= e(url('admin/dashboard.php')) ?>">
                            <i class="bi bi-grid" aria-hidden="true"></i> Open admin dashboard
                        </a>
                    <?php else: ?>
                        <a class="btn btn-primary btn-lg" href="<?= e(url('customer/prescription_upload.php')) ?>">
                            <i class="bi bi-file-earmark-arrow-up" aria-hidden="true"></i> Upload prescription
                        </a>
                    <?php endif; ?>
                    <a class="btn btn-ghost-light btn-lg" href="<?= e(url('catalogue.php')) ?>">
                        <i class="bi bi-search" aria-hidden="true"></i> Browse medicines
                    </a>
                </div>

                <div class="hero-stats">
                    <div class="hero-stat">
                        <span class="hero-stat-value">AI OCR</span>
                        <span class="hero-stat-label">Prescription scanning</span>
                    </div>
                    <div class="hero-stat">
                        <span class="hero-stat-value">COD</span>
                        <span class="hero-stat-label">Cash on delivery</span>
                    </div>
                    <div class="hero-stat">
                        <span class="hero-stat-value">Live</span>
                        <span class="hero-stat-label">Stock availability</span>
                    </div>
                    <div class="hero-stat">
                        <span class="hero-stat-value">Track</span>
                        <span class="hero-stat-label">Every order's status</span>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <!-- A static example of the scan summary page (FR-14). Not real data. -->
                <div class="scan-card" aria-label="Example of a prescription scan result">
                    <div class="scan-card-head">
                        <span class="scan-card-icon" aria-hidden="true"><i class="bi bi-file-earmark-medical"></i></span>
                        <div>
                            <span class="scan-card-title">Scan complete</span>
                            <span class="scan-card-sub">Example result from prescription.jpg</span>
                        </div>
                    </div>

                    <ul class="list-unstyled mb-0">
                        <li class="scan-line">
                            <span class="scan-med">
                                <span class="scan-med-icon" aria-hidden="true"><i class="bi bi-capsule"></i></span>
                                <span><span class="scan-med-name">Paracetamol 500 mg</span><span class="scan-med-meta">Analgesic, Rs 45</span></span>
                            </span>
                            <span class="scan-tag scan-tag-found">Added to cart</span>
                        </li>
                        <li class="scan-line">
                            <span class="scan-med">
                                <span class="scan-med-icon" aria-hidden="true"><i class="bi bi-capsule"></i></span>
                                <span><span class="scan-med-name">Amoxicillin 250 mg</span><span class="scan-med-meta">Antibiotic, Rs 185</span></span>
                            </span>
                            <span class="scan-tag scan-tag-found">Added to cart</span>
                        </li>
                        <li class="scan-line">
                            <span class="scan-med">
                                <span class="scan-med-icon scan-med-icon-missing" aria-hidden="true"><i class="bi bi-question-lg"></i></span>
                                <span><span class="scan-med-name">Unreadable line</span><span class="scan-med-meta">Handwriting unclear</span></span>
                            </span>
                            <span class="scan-tag scan-tag-missing">Not found</span>
                        </li>
                    </ul>

                    <div class="scan-card-foot">
                        <i class="bi bi-cart-check" aria-hidden="true"></i>
                        2 medicines added. Review quantities before checkout.
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- =========================== 2. SEARCH =========================== -->
<section class="home-search">
    <div class="container">
        <div class="search-card">
            <!-- Sends the visitor to catalogue.php?search=... (the catalogue is built in Phase 4) -->
            <form action="<?= e(url('catalogue.php')) ?>" method="get" role="search">
                <label for="homeSearch" class="visually-hidden">Search medicines</label>
                <div class="search-box">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input id="homeSearch" type="search" name="search" maxlength="100"
                           placeholder="Search by medicine name, SKU or manufacturer">
                    <button class="btn btn-primary" type="submit">Search</button>
                </div>
            </form>

            <?php if (!empty($categories)): ?>
                <div class="chip-row" aria-label="Browse by category">
                    <a class="chip active" href="<?= e(url('catalogue.php')) ?>">All</a>
                    <?php foreach ($categories as $category): ?>
                        <a class="chip" href="<?= e(url('catalogue.php?category=' . urlencode($category))) ?>"><?= e($category) ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- =========================== 3. POPULAR THIS MONTH =========================== -->
<?php if (!empty($trendingMedicines)): ?>
    <section class="home-section pb-0">
        <div class="container">
            <div class="section-head">
                <div>
                    <h2 class="section-title">
                        <span class="section-title-icon" aria-hidden="true"><i class="bi bi-graph-up-arrow"></i></span>
                        Popular this month
                    </h2>
                    <p class="section-intro">The medicines customers ordered most this month.</p>
                </div>
                <a class="see-all" href="<?= e(url('catalogue.php')) ?>">See all <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            </div>

            <div class="row g-3 g-lg-4">
                <?php foreach ($trendingMedicines as $index => $medicine): ?>
                    <div class="col-sm-6 col-lg-3">
                        <?php
                        $medicineRank = $index + 1;
                        require APP_PATH . '/views/partials/medicine_card.php';
                        ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<!-- =========================== 4. BROWSE MEDICINES =========================== -->
<?php if (!empty($latestMedicines)): ?>
    <section class="home-section pb-0">
        <div class="container">
            <div class="section-head">
                <div>
                    <h2 class="section-title">
                        <span class="section-title-icon" aria-hidden="true"><i class="bi bi-grid"></i></span>
                        Browse medicines
                    </h2>
                    <p class="section-intro">Recently added to our catalogue.</p>
                </div>
                <a class="see-all" href="<?= e(url('catalogue.php')) ?>">View catalogue <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            </div>

            <div class="row g-3 g-lg-4">
                <?php foreach ($latestMedicines as $medicine): ?>
                    <div class="col-sm-6 col-lg-3">
                        <?php
                        $medicineRank = null;
                        require APP_PATH . '/views/partials/medicine_card.php';
                        ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<!-- =========================== 5. HOW IT WORKS =========================== -->
<section class="home-section">
    <div class="container">
        <div class="section-head">
            <div>
                <h2 class="section-title">
                    <span class="section-title-icon" aria-hidden="true"><i class="bi bi-lightning-charge"></i></span>
                    How ordering works
                </h2>
                <p class="section-intro">Three steps from prescription to your door.</p>
            </div>
        </div>

        <ol class="row g-3 g-lg-4 list-unstyled mb-0">
            <li class="col-md-4">
                <div class="step-card">
                    <span class="step-number" aria-hidden="true">1</span>
                    <h3 class="h5">Upload your prescription</h3>
                    <p class="step-text">Take a clear photo in JPEG or PNG format, up to <?= e(UPLOAD_MAX_MB) ?> MB, and upload it from your account.</p>
                </div>
            </li>
            <li class="col-md-4">
                <div class="step-card">
                    <span class="step-number" aria-hidden="true">2</span>
                    <h3 class="h5">Check your cart</h3>
                    <p class="step-text">Medicines we find are added with quantity 1. Change quantities or add anything we missed.</p>
                </div>
            </li>
            <li class="col-md-4">
                <div class="step-card">
                    <span class="step-number" aria-hidden="true">3</span>
                    <h3 class="h5">Pay cash on delivery</h3>
                    <p class="step-text">Enter your delivery address and place the order. Edit or cancel it while it's still pending.</p>
                </div>
            </li>
        </ol>
    </div>
</section>

<!-- =========================== 6. CALL TO ACTION =========================== -->
<section class="home-cta">
    <div class="container">
        <div class="cta-band">
            <span class="cta-glow" aria-hidden="true"></span>
            <div class="cta-content d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">

                <?php if ($isGuest): ?>
                    <div>
                        <h2 class="cta-title">Have your prescription ready?</h2>
                        <p class="mb-0">Create a free account, upload it, and check your cart before you order.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-primary btn-lg" href="<?= e(url('auth/register.php')) ?>">Create free account</a>
                        <a class="btn btn-ghost-light btn-lg" href="<?= e(url('auth/login.php')) ?>">Log in</a>
                    </div>
                <?php elseif ($isCustomer): ?>
                    <div>
                        <h2 class="cta-title">Welcome back, <?= e($user['name']) ?></h2>
                        <p class="mb-0">Upload a new prescription or check on your recent orders.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-primary btn-lg" href="<?= e(url('customer/prescription_upload.php')) ?>">Upload prescription</a>
                        <a class="btn btn-ghost-light btn-lg" href="<?= e(url('customer/orders.php')) ?>">My orders</a>
                    </div>
                <?php else: ?>
                    <div>
                        <h2 class="cta-title">Signed in as administrator</h2>
                        <p class="mb-0">Manage orders, stock and reports from the admin panel.</p>
                    </div>
                    <div>
                        <a class="btn btn-primary btn-lg" href="<?= e(url('admin/dashboard.php')) ?>">Open admin dashboard</a>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</section>