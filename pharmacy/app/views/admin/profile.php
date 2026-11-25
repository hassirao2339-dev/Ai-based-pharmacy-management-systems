<?php
/**
 * profile.php - administrator profile view
 * Controller: admin/profile.php
 *
 * Variables:
 *   $user            the admin's row from the database
 *   $profile         what the details form should show
 *   $profileErrors   errors for the details form
 *   $passwordErrors  errors for the password form
 */
?>
<div class="admin-card-head">
    <p class="text-body-secondary mb-0">
        Your email address identifies the administrator account and cannot be changed here.
    </p>
</div>

<div class="row g-4">

    <!-- ============ Form 1: personal details (FR-61) ============ -->
    <div class="col-lg-7">
        <div class="admin-card">
            <h2 class="admin-card-title mb-4">Personal details</h2>

            <form method="post" action="<?= e(url('admin/profile.php')) ?>">
                <?= csrfField() ?>
                <input type="hidden" name="form_name" value="profile">

                <div class="mb-3">
                    <label for="name" class="form-label">Full name</label>
                    <input type="text" id="name" name="name" maxlength="100" required autocomplete="name"
                           class="form-control<?= isset($profileErrors['name']) ? ' is-invalid' : '' ?>"
                           value="<?= e($profile['name']) ?>">
                    <?php if (isset($profileErrors['name'])): ?>
                        <div class="invalid-feedback d-block"><?= e($profileErrors['name']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-4">
                    <label for="phone" class="form-label">Phone number</label>
                    <input type="tel" id="phone" name="phone" maxlength="20" required autocomplete="tel"
                           class="form-control<?= isset($profileErrors['phone']) ? ' is-invalid' : '' ?>"
                           value="<?= e($profile['phone']) ?>">
                    <?php if (isset($profileErrors['phone'])): ?>
                        <div class="invalid-feedback d-block"><?= e($profileErrors['phone']) ?></div>
                    <?php else: ?>
                        <div class="form-hint">Used if a customer or supplier needs to reach the pharmacy.</div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary">Save changes</button>
            </form>
        </div>
    </div>

    <div class="col-lg-5">

        <!-- ============ Account information ============ -->
        <div class="admin-card mb-4">
            <h2 class="admin-card-title mb-3">Account</h2>

            <dl class="info-list mb-0">
                <div>
                    <dt>Email address</dt>
                    <dd><?= e($user['email']) ?></dd>
                </div>
                <div>
                    <dt>Role</dt>
                    <dd><span class="badge role-badge-light">Administrator</span></dd>
                </div>
                <div>
                    <dt>Account created</dt>
                    <dd><?= e(date('j F Y', strtotime($user['created_at']))) ?></dd>
                </div>
            </dl>
        </div>

        <!-- ============ Form 2: change password (FR-62) ============ -->
        <div class="admin-card">
            <h2 class="admin-card-title mb-1">Change password</h2>
            <p class="text-body-secondary small mb-4">
                Confirm your current password first.
            </p>

            <form method="post" action="<?= e(url('admin/profile.php')) ?>">
                <?= csrfField() ?>
                <input type="hidden" name="form_name" value="password">

                <div class="mb-3">
                    <label for="current_password" class="form-label">Current password</label>
                    <div class="input-icon">
                        <i class="bi bi-lock" aria-hidden="true"></i>
                        <input type="password" id="current_password" name="current_password" required
                               autocomplete="current-password"
                               class="form-control has-toggle<?= isset($passwordErrors['current_password']) ? ' is-invalid' : '' ?>">
                        <button type="button" class="password-toggle" data-password-toggle="current_password" aria-label="Show password">
                            <i class="bi bi-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                    <?php if (isset($passwordErrors['current_password'])): ?>
                        <div class="invalid-feedback d-block"><?= e($passwordErrors['current_password']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">New password</label>
                    <div class="input-icon">
                        <i class="bi bi-shield-lock" aria-hidden="true"></i>
                        <input type="password" id="password" name="password" minlength="8" maxlength="72" required
                               autocomplete="new-password"
                               class="form-control has-toggle<?= isset($passwordErrors['password']) ? ' is-invalid' : '' ?>">
                        <button type="button" class="password-toggle" data-password-toggle="password" aria-label="Show password">
                            <i class="bi bi-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                    <?php if (isset($passwordErrors['password'])): ?>
                        <div class="invalid-feedback d-block"><?= e($passwordErrors['password']) ?></div>
                    <?php else: ?>
                        <div class="form-hint">At least 8 characters</div>
                    <?php endif; ?>
                </div>

                <div class="mb-4">
                    <label for="confirm_password" class="form-label">Confirm new password</label>
                    <div class="input-icon">
                        <i class="bi bi-shield-lock" aria-hidden="true"></i>
                        <input type="password" id="confirm_password" name="confirm_password" maxlength="72" required
                               autocomplete="new-password"
                               class="form-control has-toggle<?= isset($passwordErrors['confirm_password']) ? ' is-invalid' : '' ?>">
                        <button type="button" class="password-toggle" data-password-toggle="confirm_password" aria-label="Show password">
                            <i class="bi bi-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                    <?php if (isset($passwordErrors['confirm_password'])): ?>
                        <div class="invalid-feedback d-block"><?= e($passwordErrors['confirm_password']) ?></div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary">Change password</button>
            </form>
        </div>

    </div>
</div>