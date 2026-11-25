<?php

/**
 * Order.php - the Order model
 *
 * Every SQL query for the "orders", "order_items" and "order_item_batches"
 * tables lives in this class.
 *
 * The important method is createFromCart(). It runs inside a database
 * TRANSACTION, so placing an order is all-or-nothing:
 *
 *   START TRANSACTION
 *     1. check stock for every item        (FR-26)
 *     2. save the order header             (FR-27, status "pending")
 *     3. save each order line              (price frozen at today's price)
 *     4. take stock out using FIFO         (FR-27, FR-37)
 *     5. record which batches were used    (needed by FR-33)
 *     6. empty the cart                    (FR-27)
 *   COMMIT
 *
 * If anything fails in the middle, ROLLBACK undoes every step, so the
 * database can never keep an order with no stock taken, or stock taken with
 * no order saved.
 */

class Order
{
    private PDO $pdo;
    private Inventory $inventory;

    /**
     * @param PDO       $pdo       The database connection created in bootstrap.php.
     * @param Inventory $inventory The inventory model, used for the FIFO rules.
     */
    public function __construct(PDO $pdo, Inventory $inventory)
    {
        $this->pdo = $pdo;
        $this->inventory = $inventory;
    }

    // ---------------------------------------------------------------------
    // Placing an order (FR-25, FR-26, FR-27)
    // ---------------------------------------------------------------------

    /**
     * Check every cart item against the stock that can really be sold (FR-26).
     *
     * This runs before checkout so the customer is warned early. The stock is
     * checked AGAIN inside the transaction, because it can change in between.
     *
     * @param array $items Cart rows from Cart::getItems().
     * @return array Problem lines: medicine name, wanted quantity and available stock.
     */
    public function findStockProblems(array $items): array
    {
        $problems = [];

        foreach ($items as $item) {
            $available = $this->inventory->getAvailableStock((int) $item['medicine_id']);

            if ((int) $item['is_active'] !== 1) {
                $problems[] = [
                    'name' => $item['name'],
                    'wanted' => (int) $item['quantity'],
                    'available' => 0,
                    'reason' => 'no longer available',
                ];
            } elseif ($available < (int) $item['quantity']) {
                $problems[] = [
                    'name' => $item['name'],
                    'wanted' => (int) $item['quantity'],
                    'available' => $available,
                    'reason' => ($available === 0) ? 'out of stock' : 'not enough stock',
                ];
            }
        }

        return $problems;
    }

    /**
     * Turn a customer's cart into an order.
     *
     * @param int    $customerId      The customer placing the order.
     * @param array  $items           Cart rows from Cart::getItems().
     * @param string $deliveryAddress Where the order should be delivered.
     * @return int The new order_id.
     * @throws RuntimeException When the cart is empty or stock ran out.
     */
    public function createFromCart(int $customerId, array $items, string $deliveryAddress): int
    {
        if (count($items) === 0) {
            throw new RuntimeException('The cart is empty.');
        }

        // From here on, either every step works or none of them do.
        $this->pdo->beginTransaction();

        try {
            // ---------- 1. The order header ----------
            // The total is 0 for now and is corrected in step 4, once every
            // line has been priced.
            $statement = $this->pdo->prepare(
                'INSERT INTO orders (customer_id, total_amount, status, delivery_address, payment_method)
                 VALUES (?, 0, ?, ?, ?)'
            );
            $statement->execute([$customerId, 'pending', $deliveryAddress, 'COD']);

            $orderId = (int) $this->pdo->lastInsertId();

            $orderTotal = 0.0;

            foreach ($items as $item) {
                $medicineId = (int) $item['medicine_id'];
                $quantity = (int) $item['quantity'];
                $unitPrice = (float) $item['price'];
                $subtotal = $unitPrice * $quantity;

                // ---------- 2. The order line ----------
                // unit_price is copied here, so a later price change never
                // changes what this customer was charged.
                $statement = $this->pdo->prepare(
                    'INSERT INTO order_items (order_id, medicine_id, quantity, unit_price, subtotal)
                     VALUES (?, ?, ?, ?, ?)'
                );
                $statement->execute([$orderId, $medicineId, $quantity, $unitPrice, $subtotal]);

                $itemId = (int) $this->pdo->lastInsertId();

                // ---------- 3. Take the stock, oldest batches first ----------
                // deductFifo() throws if the stock ran out a moment ago, which
                // rolls the whole order back.
                $allocations = $this->inventory->deductFifo($medicineId, $quantity);

                // ---------- 4. Remember which batches were used (FR-33) ----------
                foreach ($allocations as $allocation) {
                    $statement = $this->pdo->prepare(
                        'INSERT INTO order_item_batches (item_id, batch_id, quantity) VALUES (?, ?, ?)'
                    );
                    $statement->execute([$itemId, $allocation['batch_id'], $allocation['quantity']]);
                }

                $orderTotal += $subtotal;
            }

            // ---------- 5. Save the real total ----------
            $statement = $this->pdo->prepare('UPDATE orders SET total_amount = ? WHERE order_id = ?');
            $statement->execute([$orderTotal, $orderId]);

            // ---------- 6. Empty the cart (FR-27) ----------
            $statement = $this->pdo->prepare('DELETE FROM cart WHERE customer_id = ?');
            $statement->execute([$customerId]);

            $this->pdo->commit();

            return $orderId;
        } catch (Throwable $exception) {
            // Undo everything this transaction did, then let the controller
            // decide what to tell the customer.
            $this->pdo->rollBack();

            throw $exception;
        }
    }

    // ---------------------------------------------------------------------
    // Reading orders
    // ---------------------------------------------------------------------

    /**
     * One order, but only if it belongs to this customer.
     *
     * The customer_id is part of the query on purpose: without it, changing the
     * number in the address would show someone else's order.
     *
     * @param int $orderId    The order to load.
     * @param int $customerId The customer who must own it.
     * @return array|null The order row, or null when it is not theirs.
     */
    public function findForCustomer(int $orderId, int $customerId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM orders WHERE order_id = ? AND customer_id = ?'
        );
        $statement->execute([$orderId, $customerId]);

        $order = $statement->fetch();

        if ($order === false) {
            return null;
        }

        return $order;
    }

    /**
     * The lines of one order, with the medicine name as it is today.
     *
     * @param int $orderId The order to load lines for.
     * @return array Order line rows.
     */
    public function getItems(int $orderId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT
                 oi.item_id,
                 oi.medicine_id,
                 oi.quantity,
                 oi.unit_price,
                 oi.subtotal,
                 m.name,
                 m.sku,
                 m.category
             FROM order_items oi
             INNER JOIN medicines m ON m.medicine_id = oi.medicine_id
             WHERE oi.order_id = ?
             ORDER BY oi.item_id'
        );
        $statement->execute([$orderId]);

        return $statement->fetchAll();
    }

    /**
     * A customer's orders, newest first (FR-30).
     *
     * The item count comes from a sub-query, so the list page can show
     * "3 items" without loading every line of every order.
     *
     * @param int $customerId The customer whose orders to list.
     * @return array Order rows with an extra item_count.
     */
    public function getCustomerOrders(int $customerId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT
                 o.*,
                 (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.order_id) AS item_count
             FROM orders o
             WHERE o.customer_id = ?
             ORDER BY o.order_date DESC, o.order_id DESC'
        );
        $statement->execute([$customerId]);

        return $statement->fetchAll();
    }

    /**
     * The most recent orders of a customer, for the dashboard.
     *
     * @param int $customerId The customer.
     * @param int $limit      How many orders to return.
     * @return array Order rows with an extra item_count.
     */
    public function getRecentOrders(int $customerId, int $limit = 3): array
    {
        $limit = max(1, $limit);

        $statement = $this->pdo->prepare(
            'SELECT
                 o.*,
                 (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.order_id) AS item_count
             FROM orders o
             WHERE o.customer_id = ?
             ORDER BY o.order_date DESC, o.order_id DESC
             LIMIT ' . (int) $limit
        );
        $statement->execute([$customerId]);

        return $statement->fetchAll();
    }

    // ---------------------------------------------------------------------
    // Editing and cancelling a pending order (FR-31, FR-33)
    // ---------------------------------------------------------------------

    /**
     * Whether the customer may still change this order.
     *
     * FR-31: only orders that are still "pending" may be edited.
     * Once the pharmacy confirms an order, it is fixed for the customer.
     *
     * @param array $order The order row.
     * @return bool True while the order is pending.
     */
    public function isEditableByCustomer(array $order): bool
    {
        return $order['status'] === 'pending';
    }

    /**
     * Which batches one order line took its stock from.
     * Used to put the stock back into exactly the same batches (FR-33).
     *
     * @param int $itemId The order line.
     * @return array Rows with batch_id and quantity.
     */
    public function getItemAllocations(int $itemId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT batch_id, quantity FROM order_item_batches WHERE item_id = ?'
        );
        $statement->execute([$itemId]);

        return $statement->fetchAll();
    }

    /**
     * Change the quantity of one line of a pending order (FR-31).
     *
     * The simple and safe way: give back everything this line took, then take
     * the new quantity out again with FIFO. Trying to work out the difference
     * batch by batch would be much harder to get right.
     *
     * A new quantity of 0 removes the line completely.
     *
     * @param int $orderId    The order being edited.
     * @param int $itemId     The line being changed.
     * @param int $newQuantity The quantity the customer now wants (0 removes the line).
     * @return void
     * @throws RuntimeException When there is not enough stock for a larger quantity.
     */
    public function updateItemQuantity(int $orderId, int $itemId, int $newQuantity): void
    {
        $this->pdo->beginTransaction();

        try {
            // Read the line, and make sure it really belongs to this order.
            $statement = $this->pdo->prepare(
                'SELECT * FROM order_items WHERE item_id = ? AND order_id = ?'
            );
            $statement->execute([$itemId, $orderId]);
            $item = $statement->fetch();

            if ($item === false) {
                throw new RuntimeException('That item is not part of this order.');
            }

            $medicineId = (int) $item['medicine_id'];

            // ---------- 1. Give back the stock this line is holding ----------
            $allocations = $this->getItemAllocations($itemId);
            $this->inventory->restoreAllocations($allocations);

            // The allocation rows are gone now, because the stock is back.
            $statement = $this->pdo->prepare('DELETE FROM order_item_batches WHERE item_id = ?');
            $statement->execute([$itemId]);

            if ($newQuantity <= 0) {
                // ---------- 2a. Remove the line ----------
                $statement = $this->pdo->prepare('DELETE FROM order_items WHERE item_id = ?');
                $statement->execute([$itemId]);
            } else {
                // ---------- 2b. Take the new quantity out again ----------
                $newAllocations = $this->inventory->deductFifo($medicineId, $newQuantity);

                foreach ($newAllocations as $allocation) {
                    $statement = $this->pdo->prepare(
                        'INSERT INTO order_item_batches (item_id, batch_id, quantity) VALUES (?, ?, ?)'
                    );
                    $statement->execute([$itemId, $allocation['batch_id'], $allocation['quantity']]);
                }

                // The unit price stays as it was when the order was placed.
                $newSubtotal = (float) $item['unit_price'] * $newQuantity;

                $statement = $this->pdo->prepare(
                    'UPDATE order_items SET quantity = ?, subtotal = ? WHERE item_id = ?'
                );
                $statement->execute([$newQuantity, $newSubtotal, $itemId]);
            }

            $this->inventory->recalculateMedicineStock($medicineId);
            $this->refreshTotal($orderId);

            $this->pdo->commit();
        } catch (Throwable $exception) {
            $this->pdo->rollBack();

            throw $exception;
        }
    }

    /**
     * Cancel an order and put all of its stock back (FR-33).
     *
     * @param int         $orderId The order to cancel.
     * @param string|null $reason  Why it was cancelled (the admin must give one, FR-59).
     * @return void
     */
    public function cancel(int $orderId, ?string $reason = null): void
    {
        $this->pdo->beginTransaction();

        try {
            $items = $this->getItems($orderId);

            foreach ($items as $item) {
                $itemId = (int) $item['item_id'];

                // Put the stock back into the same batches it came from.
                $allocations = $this->getItemAllocations($itemId);
                $this->inventory->restoreAllocations($allocations);

                $statement = $this->pdo->prepare('DELETE FROM order_item_batches WHERE item_id = ?');
                $statement->execute([$itemId]);

                $this->inventory->recalculateMedicineStock((int) $item['medicine_id']);
            }

            // The order itself is kept, with status "cancelled", so the customer
            // and the pharmacy both still have the record.
            $statement = $this->pdo->prepare(
                'UPDATE orders SET status = ?, cancel_reason = ? WHERE order_id = ?'
            );
            $statement->execute(['cancelled', $reason, $orderId]);

            $this->pdo->commit();
        } catch (Throwable $exception) {
            $this->pdo->rollBack();

            throw $exception;
        }
    }

    /**
     * Add the order lines up again and save the new total.
     * Called after a line has been changed or removed.
     *
     * @param int $orderId The order to refresh.
     * @return void
     */
    private function refreshTotal(int $orderId): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE orders
             SET total_amount = COALESCE(
                 (SELECT SUM(subtotal) FROM order_items WHERE order_id = ?), 0
             )
             WHERE order_id = ?'
        );
        $statement->execute([$orderId, $orderId]);
    }

    /**
     * How many lines an order still has.
     * The edit page uses this to cancel an order whose last item was removed.
     *
     * @param int $orderId The order to check.
     * @return int Number of lines.
     */
    public function countItems(int $orderId): int
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM order_items WHERE order_id = ?');
        $statement->execute([$orderId]);

        return (int) $statement->fetchColumn();
    }

    // ---------------------------------------------------------------------
    // Administrator (FR-59)
    // ---------------------------------------------------------------------

    /**
     * Every order for the admin list, newest first.
     *
     * @return array Order rows with the customer's name, email and item count.
     */
    public function getAllForAdmin(): array
    {
        $statement = $this->pdo->query(
            'SELECT
                 o.*,
                 u.name AS customer_name,
                 u.email AS customer_email,
                 u.phone AS customer_phone,
                 (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.order_id) AS item_count
             FROM orders o
             INNER JOIN users u ON u.user_id = o.customer_id
             ORDER BY o.order_date DESC, o.order_id DESC'
        );

        return $statement->fetchAll();
    }

    /**
     * One order for the admin, whoever placed it.
     *
     * The customer version (findForCustomer) also checks the owner; the admin
     * may open any order, so only the id is needed here.
     *
     * @param int $orderId The order to load.
     * @return array|null The order row with the customer's details, or null.
     */
    public function findForAdmin(int $orderId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT o.*, u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone
             FROM orders o
             INNER JOIN users u ON u.user_id = o.customer_id
             WHERE o.order_id = ?'
        );
        $statement->execute([$orderId]);

        $order = $statement->fetch();

        if ($order === false) {
            return null;
        }

        return $order;
    }

    /**
     * Which statuses an order may move to next (FR-59).
     *
     * An order only moves forward: pending -> confirmed -> dispatched ->
     * delivered. It may be cancelled at any point until it is delivered,
     * because cancelling puts the stock back and delivered stock is gone.
     *
     * @param string $currentStatus The status the order has now.
     * @return array The statuses the admin may choose.
     */
    public function getNextStatuses(string $currentStatus): array
    {
        $nextStatuses = [
            'pending' => ['confirmed', 'cancelled'],
            'confirmed' => ['dispatched', 'cancelled'],
            'dispatched' => ['delivered', 'cancelled'],
            'delivered' => [],
            'cancelled' => [],
        ];

        return $nextStatuses[$currentStatus] ?? [];
    }

    /**
     * Move an order to its next status (FR-59).
     *
     * Cancelling is not done here: it goes through cancel(), which also puts
     * the stock back.
     *
     * @param int    $orderId   The order to change.
     * @param string $newStatus confirmed, dispatched or delivered.
     * @return void
     */
    public function updateStatus(int $orderId, string $newStatus): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE orders SET status = ?, notified_admin = 1 WHERE order_id = ?'
        );
        $statement->execute([$newStatus, $orderId]);
    }

    /**
     * Mark one order as seen, so it stops counting towards the "new" badge (FR-29).
     *
     * @param int $orderId The order the admin just opened.
     * @return void
     */
    public function markAsSeen(int $orderId): void
    {
        $statement = $this->pdo->prepare('UPDATE orders SET notified_admin = 1 WHERE order_id = ?');
        $statement->execute([$orderId]);
    }

    /**
     * Which batches an order took its stock from, for the admin order page.
     * This is what makes the FIFO deduction visible and checkable.
     *
     * @param int $orderId The order to look at.
     * @return array Rows with the medicine name, batch details and quantity.
     */
    public function getBatchAllocations(int $orderId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT
                 oib.quantity,
                 b.batch_id,
                 b.fifo_order,
                 b.expiry_date,
                 b.supplier_name,
                 m.name AS medicine_name
             FROM order_item_batches oib
             INNER JOIN order_items oi ON oi.item_id = oib.item_id
             INNER JOIN inventory_batches b ON b.batch_id = oib.batch_id
             INNER JOIN medicines m ON m.medicine_id = oi.medicine_id
             WHERE oi.order_id = ?
             ORDER BY m.name, b.fifo_order'
        );
        $statement->execute([$orderId]);

        return $statement->fetchAll();
    }
}