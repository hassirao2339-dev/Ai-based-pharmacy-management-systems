<?php
/**
 * medicine_card.php - one medicine shown as a card
 * Used by: public/home.php now, and the catalogue page in Phase 4.
 *
 * Needs:
 *   $medicine       one row from the medicines table
 *                   (medicine_id, name, category, manufacturer, price, stock_qty, reorder_threshold)
 *   $user           the logged-in user, or null for a guest
 * Optional:
 *   $medicineRank   a position number for trending lists (1, 2, 3...), or null
 *
 * Variables made here start with "card" so they don't clash with the page's variables.
 */

$cardStock = (int) $medicine['stock_qty'];
$cardIsOutOfStock = ($cardStock === 0);
$cardIsLowStock = (!$cardIsOutOfStock && $cardStock <= (int) $medicine['reorder_threshold']);

// Gives neighbouring cards different soft colours (tone 1 to 4).
$cardTone = ((int) $medicine['medicine_id'] % 4) + 1;

$cardDetailsUrl = url('medicine.php?id=' . (int) $medicine['medicine_id']);

// Admins manage medicines; only guests and customers see the Add button.
$cardShowAddButton = ($user === null || $user['role'] === 'customer');
?>
<article class="med-card">
    <div class="med-media med-tone-<?= e($cardTone) ?>">
        <i class="bi bi-capsule" aria-hidden="true"></i>

        <?php if (!empty($medicineRank)): ?>
            <span class="med-rank">#<?= e($medicineRank) ?></span>
        <?php endif; ?>

        <?php if ($cardIsLowStock): ?>
            <span class="med-stock-badge">Low stock</span>
        <?php endif; ?>
    </div>

    <div class="med-body">
        <h3 class="med-name">
            <!-- stretched-link makes the whole card clickable -->
            <a class="stretched-link" href="<?= e($cardDetailsUrl) ?>"><?= e($medicine['name']) ?></a>
        </h3>
        <p class="med-meta"><?= e($medicine['category']) ?>, <?= e($medicine['manufacturer']) ?></p>

        <div class="med-footer">
            <span class="med-price"><?= e(formatPrice((float) $medicine['price'])) ?></span>

            <?php if ($cardIsOutOfStock): ?>
                <!-- FR-22: disabled button with an Out of stock label -->
                <button type="button" class="btn btn-add btn-out-of-stock" disabled>Out of stock</button>
            <?php elseif ($cardShowAddButton): ?>
                <!-- The cart handler (customer/cart_action.php) is built in Phase 5. -->
                <form action="<?= e(url('customer/cart_action.php')) ?>" method="post" class="med-add-form">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="return_to" value="<?= e(currentPath()) ?>">
                    <input type="hidden" name="medicine_id" value="<?= e($medicine['medicine_id']) ?>">
                    <button type="submit" class="btn btn-primary btn-add">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i> Add
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</article>