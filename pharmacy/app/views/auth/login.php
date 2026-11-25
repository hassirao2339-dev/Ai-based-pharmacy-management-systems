<?php
/**
 * login.php - login page view
 * Controller: auth/login.php
 *
 * Variables:
 *   $email         the email typed before, so it stays in the box after an error
 *   $errorMessage  the error to show, or an empty string when there is none
 */
?>
<section class="auth-page">
    <span class="hero-glow hero-glow-blue" aria-hidden="true"></span>
    <span class="hero-glow hero-glow-purple" aria-hidden="true"></span>

    <div class="auth-card">
        <div class="auth-head">
            <span class="auth-logo" aria-hidden="true"><i class="bi bi-capsule-pill"></i></span>
            <h1 class="auth-title">Welcome back</h1>
            <p class="auth-sub">Log in to your <?= e(APP_NAME) ?> account</p>
        </div>

        <?php if ($errorMessage !== ''): ?>
            <div class="alert alert-danger d-flex gap-2" role="alert">
                <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
                <span><?= e($errorMessage) ?></span>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= e(url('auth/login.php')) ?>">
            <?= csrfField() ?>

            <!-- Email -->
            <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <div class="input-icon">
                    <i class="bi bi-envelope" aria-hidden="true"></i>
                    <input type="email" id="email" name="email" required autocomplete="email" autofocus
                           class="form-control" value="<?= e($email) ?>" placeholder="ali@gmail.com">
                </div>
            </div>

            <!-- Password -->
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-baseline">
                    <label for="password" class="form-label">Password</label>
                    <a class="auth-link small" href="<?= e(url('auth/forgot_password.php')) ?>">Forgot password?</a>
                </div>
                <div class="input-icon">
                    <i class="bi bi-lock" aria-hidden="true"></i>
                    <input type="password" id="password" name="password" required autocomplete="current-password"
                           class="form-control has-toggle">
                    <button type="button" class="password-toggle" data-password-toggle="password" aria-label="Show password">
                        <i class="bi bi-eye" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100">Log in</button>
        </form>

        <div class="auth-divider">or</div>

        <a class="btn btn-soft w-100" href="<?= e(url('catalogue.php')) ?>">
            <i class="bi bi-search" aria-hidden="true"></i> Browse medicines without an account
        </a>

        <p class="auth-footer">
            Don't have an account?
            <a class="auth-link" href="<?= e(url('auth/register.php')) ?>">Create one</a>
        </p>
    </div>
</section>