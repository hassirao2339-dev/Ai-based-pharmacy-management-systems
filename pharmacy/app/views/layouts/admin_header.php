<?php
/**
 * admin_header.php
 *
 * Opens an admin page: a dark sidebar of admin links on the left, and a top
 * bar with the page title and the admin's name (SRS 4.1).
 *
 * Used by every page that calls render(..., 'admin').
 *
 * Variables created here start with "layout" so they never clash with
 * variables that a page passes to render().
 */

$layoutUser = currentUser();
$layoutFlashMessages = getFlashMessages();
$layoutAllowedAlertTypes = ['success', 'danger', 'warning', 'info'];

$layoutUserInitial = mb_strtoupper(mb_substr($layoutUser['name'], 0, 1));

// How many orders the admin has not opened yet, for the badge (FR-29).
$layoutNewOrderCount = (int) $pdo->query(
    "SELECT COUNT(*) FROM orders WHERE notified_admin = 0 AND status = 'pending'"
)->fetchColumn();

// The sidebar. "badge" is only used by Orders.
$layoutAdminLinks = [
    ['path' => 'admin/dashboard.php', 'label' => 'Dashboard', 'icon' => 'speedometer2'],
    ['path' => 'admin/orders.php', 'label' => 'Orders', 'icon' => 'receipt', 'badge' => $layoutNewOrderCount],
    ['path' => 'admin/medicines.php', 'label' => 'Medicines', 'icon' => 'capsule'],
    ['path' => 'admin/batches.php', 'label' => 'Stock batches', 'icon' => 'boxes'],
    ['path' => 'admin/alerts.php', 'label' => 'Low-stock alerts', 'icon' => 'bell'],
        ['path' => 'admin/expiry.php', 'label' => 'Expiry warnings', 'icon' => 'hourglass-split'],
    ['path' => 'admin/customers.php', 'label' => 'Customers', 'icon' => 'people'],
    ['path' => 'admin/trending.php', 'label' => 'Trending', 'icon' => 'graph-up-arrow'],
    ['path' => 'admin/reports.php', 'label' => 'Reports', 'icon' => 'file-earmark-pdf'],
    ['path' => 'admin/audit_log.php', 'label' => 'Audit log', 'icon' => 'journal-text'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrfToken()) ?>">
    <title><?= e($pageTitle ?? 'Admin') ?> | <?= e(APP_NAME) ?> Admin</title>

       <!-- Local copies in assets/vendor, so the admin panel works offline too. -->
    <link rel="stylesheet" href="<?= e(asset('vendor/bootstrap/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('vendor/bootstrap-icons/bootstrap-icons.min.css')) ?>">

    <!-- DataTables gives admin tables sorting, paging and search (SRS 4.3, NFR Usability) -->
    <link rel="stylesheet" href="<?= e(asset('vendor/datatables/dataTables.bootstrap5.min.css')) ?>">

    <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
</head>
<body class="admin-body">

<a class="visually-hidden-focusable skip-link" href="#main-content">Skip to main content</a>

<!-- ============ Sidebar ============ -->
<!-- On small screens this slides in as a Bootstrap offcanvas panel. -->
<aside class="admin-sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="adminSidebar"
       aria-label="Admin navigation">

    <div class="admin-brand">
        <a class="pms-brand" href="<?= e(url('admin/dashboard.php')) ?>">
            <span class="brand-logo" aria-hidden="true"><i class="bi bi-capsule-pill"></i></span>
            <span class="brand-text">
                <span class="brand-name"><?= e(APP_NAME) ?></span>
                <span class="brand-tag">Administrator</span>
            </span>
        </a>

        <button type="button" class="btn-close btn-close-white d-lg-none"
                data-bs-dismiss="offcanvas" data-bs-target="#adminSidebar" aria-label="Close menu"></button>
    </div>

    <nav class="admin-nav">
        <?php foreach ($layoutAdminLinks as $layoutLink): ?>
            <?php $layoutIsActive = isCurrentPage($layoutLink['path']); ?>
            <a class="admin-nav-link<?= $layoutIsActive ? ' active' : '' ?>"
               <?= $layoutIsActive ? 'aria-current="page"' : '' ?>
               href="<?= e(url($layoutLink['path'])) ?>">
                <i class="bi bi-<?= e($layoutLink['icon']) ?>" aria-hidden="true"></i>
                <span><?= e($layoutLink['label']) ?></span>

                <?php if (!empty($layoutLink['badge'])): ?>
                    <span class="admin-nav-badge"><?= e($layoutLink['badge']) ?> new</span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="admin-sidebar-foot">
        <a class="admin-nav-link" href="<?= e(url('index.php')) ?>">
            <i class="bi bi-shop" aria-hidden="true"></i>
            <span>View storefront</span>
        </a>
    </div>
</aside>

<!-- ============ Everything to the right of the sidebar ============ -->
<div class="admin-main">

    <header class="admin-topbar">
        <button class="btn btn-sm admin-menu-button d-lg-none" type="button"
                data-bs-toggle="offcanvas" data-bs-target="#adminSidebar" aria-controls="adminSidebar"
                aria-label="Open menu">
            <i class="bi bi-list" aria-hidden="true"></i>
        </button>

        <h1 class="admin-page-title"><?= e($pageTitle ?? 'Admin') ?></h1>

        <div class="dropdown ms-auto">
            <button class="btn nav-user dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="nav-avatar" aria-hidden="true"><?= e($layoutUserInitial) ?></span>
                <span class="d-none d-sm-inline"><?= e($layoutUser['name']) ?></span>
                <span class="badge role-badge">Admin</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <a class="dropdown-item" href="<?= e(url('admin/profile.php')) ?>">
                        <i class="bi bi-person-gear me-2" aria-hidden="true"></i>My profile
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form action="<?= e(url('auth/logout.php')) ?>" method="post">
                        <?= csrfField() ?>
                        <button type="submit" class="dropdown-item">
                            <i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Log out
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </header>

    <main id="main-content" class="admin-content">

        <?php if (!empty($layoutFlashMessages)): ?>
            <?php foreach ($layoutFlashMessages as $layoutFlash): ?>
                <?php
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
        <?php endif; ?>