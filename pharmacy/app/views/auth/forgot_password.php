<?php
/**
 * forgot_password.php - "forgot password" view
 * Controller: auth/forgot_password.php
 *
 * Variables:
 *   $email                 the email typed before
 *   $requestWasSent        true after the form has been submitted correctly
 *   $errorMessage          error to show, or an empty string
 *   $developmentResetLink  the link shown on screen when email is switched off
 */
?>
<section class="auth-page">
    <span class="hero-glow hero-glow-blue" aria-hidden="true"></span>
    <span class="hero-glow hero-glow-purple" aria-hidden="true"></span>

    <div class="auth-card">
        <div class="auth-head">
            <span class="auth-logo" aria-hidden="true"><i class="bi bi-key"></i></span>
            <h1 class="auth-title">Forgot your password?</h1>
            <p class="auth-sub">We'll email you a link to choose a new one</p>
        </div>

        <?php if ($requestWasSent): ?>

            <div class="alert alert-success d-flex gap-2" role="alert">
                <i class="bi bi-check-circle" aria-hidden="true"></i>
                <span>
                    If an account exists for <strong><?= e($email) ?></strong>,
                    a reset link is on its way. The link works for <?= e(RESET_LINK_MINUTES) ?> minutes.
                </span>
            </div>

            <?php if ($developmentResetLink !== ''): ?>
                <div class="alert alert-warning small dev-link-box" role="alert">
                    <strong>Development mode.</strong>
                    Email sending is switched off in .env, so the link is shown here instead:
                    <br>
                    <a href="<?= e($developmentResetLink) ?>"><?= e($developmentResetLink) ?></a>
                </div>
            <?php endif; ?>

            <a class="btn btn-primary btn-lg w-100" href="<?= e(url('auth/login.php')) ?>">Back to log in</a>

        <?php else: ?>

            <?php if ($errorMessage !== ''): ?>
                <div class="alert alert-danger d-flex gap-2" role="alert">
                    <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
                    <span><?= e($errorMessage) ?></span>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= e(url('auth/forgot_password.php')) ?>">
                <?= csrfField() ?>

                <div class="mb-4">
                    <label for="email" class="form-label">Email address</label>
                    <div class="input-icon">
                        <i class="bi bi-envelope" aria-hidden="true"></i>
                        <input type="email" id="email" name="email" maxlength="150" required autofocus
                               autocomplete="email" class="form-control"
                               value="<?= e($email) ?>" placeholder="ali@gmail.com">
                    </div>
                    <div class="form-hint">Use the address you registered with.</div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-100">Send reset link</button>
            </form>

            <p class="auth-footer">
                Remembered it?
                <a class="auth-link" href="<?= e(url('auth/login.php')) ?>">Back to log in</a>
            </p>

        <?php endif; ?>
    </div>
</section>