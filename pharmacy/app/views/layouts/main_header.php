<?php
/**
 * main_header.php
 *
 * Opens the HTML page and shows the dark top navigation bar.
 * Used by public and customer pages. The admin layout is added in Phase 7.
 *
 * Variables created here start with "layout" so they never clash with
 * variables that a page passes to render().
 */

$layoutUser = currentUser();
$layoutFlashMessages = getFlashMessages();
$layoutAllowedAlertTypes = ['success', 'danger', 'warning', 'info'];

$layoutIsCustomer = ($layoutUser !== null && $layoutUser['role'] === 'customer');
$layoutIsAdmin = ($layoutUser !== null && $layoutUser['role'] === 'admin');

$layoutDashboardPath = 'customer/dashboard.php';

if ($layoutIsAdmin) {
    $layoutDashboardPath = 'admin/dashboard.php';
}

// First letter of the user's name, shown in the avatar square.
$layoutUserInitial = '';

if ($layoutUser !== null) {
    $layoutUserInitial = mb_strtoupper(mb_substr($layoutUser['name'], 0, 1));
}

// Navigation links. Guests and customers see "Upload prescription";
// only customers see "My orders" and "Cart".
$layoutNavLinks = [
    ['path' => 'index.php', 'label' => 'Home'],
    ['path' => 'catalogue.php', 'label' => 'Medicines'],
];

if (!$layoutIsAdmin) {
    $layoutNavLinks[] = ['path' => 'customer/prescription_upload.php', 'label' => 'Upload prescription'];
}

if ($layoutIsCustomer) {
    $layoutNavLinks[] = ['path' => 'customer/orders.php', 'label' => 'My orders'];
}

// How many medicines are in the cart, for the red number on the Cart link.
$layoutCartCount = 0;

if ($layoutIsCustomer) {
    $layoutCartModel = new Cart($pdo);
    $layoutCartCount = $layoutCartModel->countItems((int) $_SESSION['user_id']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrfToken()) ?>">
    <title><?= e($pageTitle ?? 'Welcome') ?> | <?= e(APP_NAME) ?></title>

        <!-- Every library is a local copy in assets/vendor, so the site works
         without an internet connection (NFR Portability, the viva demo). -->
    <link rel="stylesheet" href="<?= e(asset('vendor/bootstrap/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('vendor/bootstrap-icons/bootstrap-icons.min.css')) ?>">

    <!-- Our own styles load last, so they can adjust Bootstrap. -->
    <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
</head>
<body class="d-flex flex-column min-vh-100">

<a class="visually-hidden-focusable skip-link" href="#main-content">Skip to main content</a>

<!-- data-bs-theme="dark" tells Bootstrap to use light text and a dark dropdown inside the navbar. -->
<nav class="navbar navbar-expand-lg sticky-top pms-navbar" id="mainNavbar" data-bs-theme="dark">
    <div class="container">
        <a class="navbar-brand pms-brand" href="<?= e(url('index.php')) ?>">
            <span class="brand-logo" aria-hidden="true"><i class="bi bi-capsule-pill"></i></span>
            <span class="brand-text">
                <span class="brand-name"><?= e(APP_NAME) ?></span>
                <span class="brand-tag">Smart pharmacy</span>
            </span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavLinks"
                aria-controls="mainNavLinks" aria-expanded="false" aria-label="Open menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavLinks">
            <ul class="navbar-nav mx-lg-auto">
                <?php foreach ($layoutNavLinks as $layoutLink): ?>
                    <?php $layoutIsActive = isCurrentPage($layoutLink['path']); ?>
                    <li class="nav-item">
                        <a class="nav-link<?= $layoutIsActive ? ' active' : '' ?>"
                           <?= $layoutIsActive ? 'aria-current="page"' : '' ?>
                           href="<?= e(url($layoutLink['path'])) ?>"><?= e($layoutLink['label']) ?></a>
                    </li>
                <?php endforeach; ?>
                                <?php if ($layoutIsCustomer): ?>
                    <!-- The Cart link carries the number of medicines in the cart. -->
                    <li class="nav-item">
                        <?php $layoutCartIsActive = isCurrentPage('customer/cart.php'); ?>
                        <a class="nav-link nav-cart<?= $layoutCartIsActive ? ' active' : '' ?>"
                           <?= $layoutCartIsActive ? 'aria-current="page"' : '' ?>
                           href="<?= e(url('customer/cart.php')) ?>">
                            Cart
                            <?php if ($layoutCartCount > 0): ?>
                                <span class="cart-count"><?= e($layoutCartCount) ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>

            <?php if ($layoutUser !== null): ?>
                <!-- Logged in: name, role and logout (SRS 4.1) -->
                <div class="dropdown py-2 py-lg-0">
                    <button class="btn nav-user dropdown-toggle" type="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="nav-avatar" aria-hidden="true"><?= e($layoutUserInitial) ?></span>
                        <span><?= e($layoutUser['name']) ?></span>
                        <span class="badge role-badge"><?= e(ucfirst($layoutUser['role'])) ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="<?= e(url($layoutDashboardPath)) ?>">
                                <i class="bi bi-grid me-2" aria-hidden="true"></i>Dashboard
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <!-- Logout is a POST form with a CSRF token, so other sites cannot log users out. -->
                            <form action="<?= e(url('auth/logout.php')) ?>" method="post">
                                <?= csrfField() ?>
                                <button type="submit" class="dropdown-item">
                                    <i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Log out
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            <?php else: ?>
                <!-- Guest: login and registration buttons -->
                <div class="d-flex gap-2 py-2 py-lg-0">
                    <a class="btn btn-ghost-light" href="<?= e(url('auth/login.php')) ?>">Log in</a>
                    <a class="btn btn-primary" href="<?= e(url('auth/register.php')) ?>">Create account</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>

<main id="main-content" class="flex-grow-1">

    <?php if (!empty($layoutFlashMessages)): ?>
        <div class="container pt-4 flash-area">
            <?php foreach ($layoutFlashMessages as $layoutFlash): ?>
                <?php
                // Only allow known Bootstrap alert types.
                $layoutAlertType = 'info';

                if (in_array($layoutFlash['type'], $layoutAllowedAlertTypes, true)) {
                    $layoutAlertType = $layoutFlash['type'];
                }
                ?>
                <div class="alert alert-<?= e($layoutAlertType) ?> alert-dismissible fade show" role="alert">
                    <?= e($layoutFlash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close message"></button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>