<?php
/**
 * reset_password.php - "choose a new password" view
 * Controller: auth/reset_password.php
 *
 * Variables:
 *   $linkIsValid  false when the link is wrong, already used or expired
 *   $token        the token from the link, kept in a hidden field
 *   $errors       error messages by field name
 */
?>
<section class="auth-page">
    <span class="hero-glow hero-glow-blue" aria-hidden="true"></span>
    <span class="hero-glow hero-glow-purple" aria-hidden="true"></span>

    <div class="auth-card">
        <?php if (!$linkIsValid): ?>

            <div class="auth-head">
                <span class="auth-logo" aria-hidden="true"><i class="bi bi-link-45deg"></i></span>
                <h1 class="auth-title">This link no longer works</h1>
                <p class="auth-sub">Reset links expire after <?= e(RESET_LINK_MINUTES) ?> minutes and can only be used once</p>
            </div>

            <a class="btn btn-primary btn-lg w-100 mb-2" href="<?= e(url('auth/forgot_password.php')) ?>">
                Ask for a new link
            </a>
            <a class="btn btn-soft w-100" href="<?= e(url('auth/login.php')) ?>">Back to log in</a>

        <?php else: ?>

            <div class="auth-head">
                <span class="auth-logo" aria-hidden="true"><i class="bi bi-shield-lock"></i></span>
                <h1 class="auth-title">Choose a new password</h1>
                <p class="auth-sub">Pick something you have not used before</p>
            </div>

            <form method="post" action="<?= e(url('auth/reset_password.php')) ?>">
                <?= csrfField() ?>
                <input type="hidden" name="token" value="<?= e($token) ?>">

                <!-- New password -->
                <div class="mb-3">
                    <label for="password" class="form-label">New password</label>
                    <div class="input-icon">
                        <i class="bi bi-lock" aria-hidden="true"></i>
                        <input type="password" id="password" name="password" minlength="8" maxlength="72" required
                               autofocus autocomplete="new-password"
                               class="form-control has-toggle<?= isset($errors['password']) ? ' is-invalid' : '' ?>">
                        <button type="button" class="password-toggle" data-password-toggle="password" aria-label="Show password">
                            <i class="bi bi-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                    <?php if (isset($errors['password'])): ?>
                        <div class="invalid-feedback d-block"><?= e($errors['password']) ?></div>
                    <?php else: ?>
                        <div class="form-hint">At least 8 characters</div>
                    <?php endif; ?>
                </div>

                <!-- Confirm password -->
                <div class="mb-4">
                    <label for="confirm_password" class="form-label">Confirm new password</label>
                    <div class="input-icon">
                        <i class="bi bi-lock" aria-hidden="true"></i>
                        <input type="password" id="confirm_password" name="confirm_password" maxlength="72" required
                               autocomplete="new-password"
                               class="form-control has-toggle<?= isset($errors['confirm_password']) ? ' is-invalid' : '' ?>">
                        <button type="button" class="password-toggle" data-password-toggle="confirm_password" aria-label="Show password">
                            <i class="bi bi-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                    <?php if (isset($errors['confirm_password'])): ?>
                        <div class="invalid-feedback d-block"><?= e($errors['confirm_password']) ?></div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-100">Save new password</button>
            </form>

        <?php endif; ?>
    </div>
</section>