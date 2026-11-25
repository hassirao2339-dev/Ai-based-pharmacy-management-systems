<?php
/**
 * checkout.php - checkout view
 * Controller: customer/checkout.php
 *
 * Variables:
 *   $items            the cart lines being ordered
 *   $total            the order total
 *   $deliveryAddress  address to show in the box
 *   $phone            phone number to show in the box
 *   $customerName     the customer's name, shown in the summary
 *   $errors           error messages by field name
 */
?>
<section class="page-head">
    <div class="container">
        <h1 class="h2 mb-1">Checkout</h1>
        <p class="text-body-secondary mb-0">
            Check your delivery details and place your order.
        </p>
    </div>
</section>

<section class="container py-4 py-lg-5">
    <form method="post" action="<?= e(url('customer/checkout.php')) ?>">
        <?= csrfField() ?>

        <div class="row g-4">

            <!-- ============ Delivery details (FR-25) ============ -->
            <div class="col-lg-7">
                <div class="settings-card">
                    <h2 class="h5 mb-1">Delivery details</h2>
                    <p class="text-body-secondary small mb-4">
                        The rider will use these details, and they are saved to your profile.
                    </p>

                    <div class="mb-3">
                        <label for="delivery_address" class="form-label">Delivery address</label>
                        <textarea id="delivery_address" name="delivery_address" rows="3" maxlength="500" required
                                  autocomplete="street-address"
                                  class="form-control<?= isset($errors['delivery_address']) ? ' is-invalid' : '' ?>"
                                  placeholder="House 12, Street 4, Gulgasht Colony, Multan"><?= e($deliveryAddress) ?></textarea>
                        <?php if (isset($errors['delivery_address'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['delivery_address']) ?></div>
                        <?php else: ?>
                            <div class="form-hint">Include the house number, street and area.</div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-0">
                        <label for="phone" class="form-label">Phone number</label>
                        <div class="input-icon">
                            <i class="bi bi-telephone" aria-hidden="true"></i>
                            <input type="tel" id="phone" name="phone" maxlength="20" required autocomplete="tel"
                                   class="form-control<?= isset($errors['phone']) ? ' is-invalid' : '' ?>"
                                   value="<?= e($phone) ?>">
                        </div>
                        <?php if (isset($errors['phone'])): ?>
                            <div class="invalid-feedback d-block"><?= e($errors['phone']) ?></div>
                        <?php else: ?>
                            <div class="form-hint">The rider will call before delivering.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ============ Payment method (FR-25: COD only) ============ -->
                <div class="settings-card mt-4">
                    <h2 class="h5 mb-3">Payment method</h2>

                    <div class="payment-option">
                        <span class="payment-icon" aria-hidden="true"><i class="bi bi-cash-coin"></i></span>
                        <div>
                            <strong>Cash on delivery</strong>
                            <span class="d-block text-body-secondary small">
                                Pay the rider in cash when your order arrives.
                            </span>
                        </div>
                        <i class="bi bi-check-circle-fill payment-check" aria-hidden="true"></i>
                    </div>

                    <p class="form-hint mt-3 mb-0">
                        Online payment is not available yet, so every order is cash on delivery.
                    </p>
                </div>
            </div>

            <!-- ============ Order summary ============ -->
            <div class="col-lg-5">
                <div class="summary-card">
                    <h2 class="h5 mb-3">Your order</h2>

                    <ul class="order-lines">
                        <?php foreach ($items as $item): ?>
                            <li>
                                <span class="order-line-qty"><?= e($item['quantity']) ?> &times;</span>
                                <span class="order-line-name"><?= e($item['name']) ?></span>
                                <span class="order-line-price"><?= e(formatPrice((float) $item['subtotal'])) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <div class="summary-total">
                        <span>Total to pay</span>
                        <span><?= e(formatPrice((float) $total)) ?></span>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100 mt-3">
                        <i class="bi bi-bag-check" aria-hidden="true"></i> Place order
                    </button>

                    <a class="btn btn-soft w-100 mt-2" href="<?= e(url('customer/cart.php')) ?>">
                        Back to cart
                    </a>

                    <p class="summary-note mb-0">
                        <i class="bi bi-shield-check" aria-hidden="true"></i>
                        Your order goes to the pharmacy as <strong>Pending</strong>. You can still
                        edit or cancel it until it is confirmed.
                    </p>
                </div>
            </div>

        </div>
    </form>
</section>