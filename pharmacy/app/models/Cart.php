<?php

/**
 * Cart.php - the Cart model
 *
 * Every SQL query for the "cart" table lives in this class.
 *
 * FR-23: the cart is PERSISTENT. Items are saved in the database against the
 * customer's account, not in the session, so the cart survives logging out and
 * is the same on any device.
 *
 * The cart never stores prices. Prices are read from the medicines table every
 * time the cart is shown, so the customer always sees today's price. The price
 * is only frozen into order_items when the order is placed.
 */

class Cart
{
    private PDO $pdo;

    /**
     * @param PDO $pdo The database connection created in bootstrap.php.
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ---------------------------------------------------------------------
    // Reading the cart
    // ---------------------------------------------------------------------

    /**
     * Every item in a customer's cart, with the details needed to show it.
     *
     * The join brings in the medicine's current name, price and stock, so the
     * cart page can warn about anything that has gone out of stock (FR-26).
     *
     * @param int $customerId The customer whose cart to load.
     * @return array Cart rows with medicine details and a subtotal per line.
     */
    public function getItems(int $customerId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT
                 c.cart_id,
                 c.medicine_id,
                 c.quantity,
                 m.name,
                 m.sku,
                 m.category,
                 m.manufacturer,
                 m.price,
                 m.stock_qty,
                 m.is_active,
                 (c.quantity * m.price) AS subtotal
             FROM cart c
             INNER JOIN medicines m ON m.medicine_id = c.medicine_id
             WHERE c.customer_id = ?
             ORDER BY c.added_at, c.cart_id'
        );
        $statement->execute([$customerId]);

        return $statement->fetchAll();
    }

    /**
     * How many separate medicines are in the cart.
     * Used for the number on the cart button in the navigation bar.
     *
     * @param int $customerId The customer whose cart to count.
     * @return int Number of cart lines.
     */
    public function countItems(int $customerId): int
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM cart WHERE customer_id = ?');
        $statement->execute([$customerId]);

        return (int) $statement->fetchColumn();
    }

    /**
     * The quantity of one medicine already in the cart.
     *
     * @param int $customerId The customer.
     * @param int $medicineId The medicine to look for.
     * @return int The quantity, or 0 when the medicine is not in the cart.
     */
    public function getQuantity(int $customerId, int $medicineId): int
    {
        $statement = $this->pdo->prepare(
            'SELECT quantity FROM cart WHERE customer_id = ? AND medicine_id = ?'
        );
        $statement->execute([$customerId, $medicineId]);

        return (int) $statement->fetchColumn();
    }

    // ---------------------------------------------------------------------
    // Changing the cart (FR-23, FR-24)
    // ---------------------------------------------------------------------

    /**
     * Add a medicine to the cart, or add to the quantity already there.
     *
     * "ON DUPLICATE KEY UPDATE" uses the UNIQUE key on (customer_id, medicine_id):
     * if the row already exists, MySQL updates it instead of failing. That is
     * why the same medicine can never appear twice in one cart.
     *
     * @param int $customerId The customer.
     * @param int $medicineId The medicine to add.
     * @param int $quantity   How many units to add.
     * @return void
     */
    public function addItem(int $customerId, int $medicineId, int $quantity): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO cart (customer_id, medicine_id, quantity)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)'
        );
        $statement->execute([$customerId, $medicineId, $quantity]);
    }

    /**
     * Set the quantity of one cart line to an exact number (FR-23).
     *
     * @param int $customerId The customer.
     * @param int $medicineId The medicine to change.
     * @param int $quantity   The new quantity (must be 1 or more).
     * @return void
     */
    public function setQuantity(int $customerId, int $medicineId, int $quantity): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE cart SET quantity = ? WHERE customer_id = ? AND medicine_id = ?'
        );
        $statement->execute([$quantity, $customerId, $medicineId]);
    }

    /**
     * Remove one medicine from the cart (FR-24).
     *
     * @param int $customerId The customer.
     * @param int $medicineId The medicine to remove.
     * @return void
     */
    public function removeItem(int $customerId, int $medicineId): void
    {
        $statement = $this->pdo->prepare(
            'DELETE FROM cart WHERE customer_id = ? AND medicine_id = ?'
        );
        $statement->execute([$customerId, $medicineId]);
    }

    /**
     * Empty the whole cart (FR-24), and after a successful order (FR-27).
     *
     * @param int $customerId The customer.
     * @return void
     */
    public function clear(int $customerId): void
    {
        $statement = $this->pdo->prepare('DELETE FROM cart WHERE customer_id = ?');
        $statement->execute([$customerId]);
    }

    // ---------------------------------------------------------------------
    // Totals
    // ---------------------------------------------------------------------

    /**
     * Add up the cart at today's prices.
     *
     * @param array $items Rows from getItems().
     * @return float The total amount.
     */
    public function calculateTotal(array $items): float
    {
        $total = 0.0;

        foreach ($items as $item) {
            $total += (float) $item['subtotal'];
        }

        return $total;
    }
}