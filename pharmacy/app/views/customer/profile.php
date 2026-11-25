<?php
/**
 * profile.php - profile settings view
 * Controller: customer/profile.php
 *
 * Variables:
 *   $user            the user's row from the database
 *   $profile         what the details form should show: name, phone, address
 *   $profileErrors   errors for the details form
 *   $passwordErrors  errors for the password form
 */

// First letter of the name, shown in the round avatar.
$initial = mb_strtoupper(mb_substr($user['name'], 0, 1));
?>
<section class="page-head">
    <div class="container">
        <div class="profile-head">
            <span class="profile-avatar" aria-hidden="true"><?= e($initial) ?></span>
            <div>
                <h1 class="h2 mb-1"><?= e($user['name']) ?></h1>
                <p class="text-body-secondary mb-0">
                    <?= e($user['email']) ?>
                    <span class="badge role-badge-light ms-1">Customer</span>
                </p>
            </div>
        </div>
    </div>
</section>

<section class="container py-5">
    <div class="row g-4">

        <!-- ============ Form 1: personal details (FR-61) ============ -->
        <div class="col-lg-7">
            <div class="settings-card">
                <h2 class="h5 mb-1">Personal details</h2>
                <p class="text-body-secondary small mb-4">
                    Your delivery address is used to fill in the checkout form.
                </p>

                <form method="post" action="<?= e(url('customer/profile.php')) ?>">
                    <?= csrfField() ?>
                    <input type="hidden" name="form_name" value="profile">

                    <!-- Full name -->
                    <div class="mb-3">
                        <label for="name" class="form-label">Full name</label>
                        <div class="input-icon">
                            <i class="bi bi-person" aria-hidden="true"></i>
                            <input type="text" id="name" name="name" maxlength="100" required autocomplete="name"
                                   class="form-control<?= isset($profileErrors['name']) ? ' is-invalid' : '' ?>"
                                   value="<?= e($profile['name']) ?>">
                        </div>
                        <?php if (isset($profileErrors['name'])): ?>
                            <div class="invalid-feedback d-block"><?= e($profileErrors['name']) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Phone -->
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone number</label>
                        <div class="input-icon">
                            <i class="bi bi-telephone" aria-hidden="true"></i>
                            <input type="tel" id="phone" name="phone" maxlength="20" required autocomplete="tel"
                                   class="form-control<?= isset($profileErrors['phone']) ? ' is-invalid' : '' ?>"
                                   value="<?= e($profile['phone']) ?>">
                        </div>
                        <?php if (isset($profileErrors['phone'])): ?>
                            <div class="invalid-feedback d-block"><?= e($profileErrors['phone']) ?></div>
                        <?php else: ?>
                            <div class="form-hint">The rider will call this number.</div>
                        <?php endif; ?>
                    </div>

                    <!-- Delivery address -->
                    <div class="mb-4">
                        <label for="address" class="form-label">
                            Delivery address <span class="fw-normal text-body-secondary">(optional)</span>
                        </label>
                        <textarea id="address" name="address" rows="3" maxlength="500" autocomplete="street-address"
                                  class="form-control<?= isset($profileErrors['address']) ? ' is-invalid' : '' ?>"
                                  placeholder="House 12, Street 4, Multan"><?= e($profile['address']) ?></textarea>
                        <?php if (isset($profileErrors['address'])): ?>
                            <div class="invalid-feedback d-block"><?= e($profileErrors['address']) ?></div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-primary">Save changes</button>
                </form>
            </div>
        </div>

        <!-- ============ Account information ============ -->
        <div class="col-lg-5">
            <div class="settings-card mb-4">
                <h2 class="h5 mb-4">Account</h2>

                <dl class="info-list mb-0">
                    <div>
                        <dt>Email address</dt>
                        <dd><?= e($user['email']) ?></dd>
                    </div>
                    <div>
                        <dt>Member since</dt>
                        <dd><?= e(date('j F Y', strtotime($user['created_at']))) ?></dd>
                    </div>
                </dl>

                <p class="form-hint mt-3 mb-0">
                    <i class="bi bi-info-circle" aria-hidden="true"></i>
                    Your email address identifies your account and cannot be changed here.
                    Contact the pharmacy if you need to change it.
                </p>
            </div>

            <!-- ============ Form 2: change password (FR-62) ============ -->
            <div class="settings-card">
                <h2 class="h5 mb-1">Change password</h2>
                <p class="text-body-secondary small mb-4">
                    Confirm your current password first.
                </p>

                <form method="post" action="<?= e(url('customer/profile.php')) ?>">
                    <?= csrfField() ?>
                    <input type="hidden" name="form_name" value="password">

                    <!-- Current password -->
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

                    <!-- New password -->
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

                    <!-- Confirm new password -->
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
</section>