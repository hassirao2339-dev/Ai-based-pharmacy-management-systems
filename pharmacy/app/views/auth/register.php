<?php
/**
 * register.php - customer registration view
 * Controller: auth/register.php
 *
 * Variables:
 *   $old     what the visitor typed before: name, email, phone, address
 *   $errors  error messages by field name (empty when there are none)
 */
?>
<section class="auth-page">
    <span class="hero-glow hero-glow-blue" aria-hidden="true"></span>
    <span class="hero-glow hero-glow-purple" aria-hidden="true"></span>

    <div class="auth-card auth-card-wide">
        <div class="auth-head">
            <span class="auth-logo" aria-hidden="true"><i class="bi bi-person-plus"></i></span>
            <h1 class="auth-title">Create your account</h1>
            <p class="auth-sub">Order medicines online and pay cash on delivery</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
                Please fix the highlighted fields below.
            </div>
        <?php endif; ?>

        <form method="post" action="<?= e(url('auth/register.php')) ?>">
            <?= csrfField() ?>

            <!-- Full name -->
            <div class="mb-3">
                <label for="name" class="form-label">Full name</label>
                <div class="input-icon">
                    <i class="bi bi-person" aria-hidden="true"></i>
                    <input type="text" id="name" name="name" maxlength="100" required autocomplete="name"
                           class="form-control<?= isset($errors['name']) ? ' is-invalid' : '' ?>"
                           value="<?= e($old['name']) ?>" placeholder="Ali Khan">
                </div>
                <?php if (isset($errors['name'])): ?>
                    <div class="invalid-feedback d-block"><?= e($errors['name']) ?></div>
                <?php endif; ?>
            </div>

            <div class="row g-3 mb-3">
                <!-- Email -->
                <div class="col-sm-7">
                    <label for="email" class="form-label">Email address</label>
                    <div class="input-icon">
                        <i class="bi bi-envelope" aria-hidden="true"></i>
                        <input type="email" id="email" name="email" maxlength="150" required autocomplete="email"
                               class="form-control<?= isset($errors['email']) ? ' is-invalid' : '' ?>"
                               value="<?= e($old['email']) ?>" placeholder="ali@gmail.com">
                    </div>
                    <?php if (isset($errors['email'])): ?>
                        <div class="invalid-feedback d-block"><?= e($errors['email']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Phone -->
                <div class="col-sm-5">
                    <label for="phone" class="form-label">Phone number</label>
                    <div class="input-icon">
                        <i class="bi bi-telephone" aria-hidden="true"></i>
                        <input type="tel" id="phone" name="phone" maxlength="20" required autocomplete="tel"
                               class="form-control<?= isset($errors['phone']) ? ' is-invalid' : '' ?>"
                               value="<?= e($old['phone']) ?>" placeholder="0300 1234567">
                    </div>
                    <?php if (isset($errors['phone'])): ?>
                        <div class="invalid-feedback d-block"><?= e($errors['phone']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <!-- Password -->
                <div class="col-sm-6">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-icon">
                        <i class="bi bi-lock" aria-hidden="true"></i>
                        <input type="password" id="password" name="password" minlength="8" maxlength="72" required
                               autocomplete="new-password"
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
                <div class="col-sm-6">
                    <label for="confirm_password" class="form-label">Confirm password</label>
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
            </div>

            <!-- Delivery address (optional) -->
            <div class="mb-4">
                <label for="address" class="form-label">
                    Delivery address <span class="fw-normal text-body-secondary">(optional)</span>
                </label>
                <textarea id="address" name="address" rows="2" maxlength="500" autocomplete="street-address"
                          class="form-control<?= isset($errors['address']) ? ' is-invalid' : '' ?>"
                          placeholder="House 12, Street 4, Multan"><?= e($old['address']) ?></textarea>
                <?php if (isset($errors['address'])): ?>
                    <div class="invalid-feedback d-block"><?= e($errors['address']) ?></div>
                <?php else: ?>
                    <div class="form-hint">You can also add or change it later, or at checkout.</div>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100">Create account</button>
        </form>

        <p class="auth-footer">
            Already have an account?
            <a class="auth-link" href="<?= e(url('auth/login.php')) ?>">Log in</a>
        </p>
    </div>
</section>