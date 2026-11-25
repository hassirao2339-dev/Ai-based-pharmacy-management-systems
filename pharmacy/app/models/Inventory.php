<?php

/**
 * Inventory.php - the Inventory model
 *
 * Every SQL query for the "inventory_batches" table lives here, together with
 * the FIFO rules. This is the most important class in the project, because it
 * decides how stock leaves and returns to the shelves.
 *
 * SRS 2.6: "Inventory follows the FIFO principle - oldest batches are
 * dispensed before newer ones."
 *
 * How stock works here:
 *   - Real stock lives in inventory_batches, one row per delivery.
 *   - A batch is USABLE when quantity > 0 and expiry_date is in the future.
 *   - medicines.stock_qty and medicines.expiry_date are only summaries;
 *     recalculateMedicineStock() rewrites them after every change.
 *
 * Every method that changes stock must be called inside a database
 * transaction, which the checkout controller starts (see Order model).
 */

class Inventory
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
    // Reading stock
    // ---------------------------------------------------------------------

    /**
     * How many units of a medicine can actually be sold right now.
     *
     * Expired batches are left out, so an expired batch can never be sold even
     * if the summary column on medicines is out of date.
     *
     * @param int $medicineId The medicine to count.
     * @return int Units available.
     */
    public function getAvailableStock(int $medicineId): int
    {
        $statement = $this->pdo->prepare(
            'SELECT COALESCE(SUM(quantity), 0)
             FROM inventory_batches
             WHERE medicine_id = ? AND quantity > 0 AND expiry_date > CURDATE()'
        );
        $statement->execute([$medicineId]);

        return (int) $statement->fetchColumn();
    }

    /**
     * The usable batches of a medicine, oldest first (FIFO order).
     *
     * "FOR UPDATE" locks these rows until the transaction finishes. If two
     * customers check out at the same moment, the second one waits here, so
     * the same last tablet cannot be sold twice.
     *
     * @param int $medicineId The medicine to load batches for.
     * @return array Batch rows in FIFO order.
     */
    public function getUsableBatchesForUpdate(int $medicineId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT batch_id, quantity
             FROM inventory_batches
             WHERE medicine_id = ? AND quantity > 0 AND expiry_date > CURDATE()
             ORDER BY fifo_order, batch_id
             FOR UPDATE'
        );
        $statement->execute([$medicineId]);

        return $statement->fetchAll();
    }

    // ---------------------------------------------------------------------
    // Taking stock out (FR-27, FR-37)
    // ---------------------------------------------------------------------

    /**
     * Take a quantity out of stock, using the oldest batches first.
     *
     * Example: 30 units are ordered.
     *   Batch A (fifo_order 1) has 20 left -> take all 20, A is now depleted
     *   Batch B (fifo_order 2) has 50 left -> take the remaining 10
     * The method returns [['batch_id' => A, 'quantity' => 20],
     *                     ['batch_id' => B, 'quantity' => 10]]
     *
     * The caller saves that list in order_item_batches, so that cancelling the
     * order later can put the stock back into exactly the same batches (FR-33).
     *
     * Call this inside a transaction, after checking availability.
     *
     * @param int $medicineId The medicine being sold.
     * @param int $quantity   How many units to take.
     * @return array Which batch gave how many units.
     * @throws RuntimeException When there is not enough usable stock.
     */
    public function deductFifo(int $medicineId, int $quantity): array
    {
        $batches = $this->getUsableBatchesForUpdate($medicineId);

        $remaining = $quantity;
        $allocations = [];

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }

            $available = (int) $batch['quantity'];

            // Take everything this batch has, or only what is still needed.
            $takeFromThisBatch = min($available, $remaining);

            $this->changeBatchQuantity((int) $batch['batch_id'], -$takeFromThisBatch);

            $allocations[] = [
                'batch_id' => (int) $batch['batch_id'],
                'quantity' => $takeFromThisBatch,
            ];

            $remaining -= $takeFromThisBatch;
        }

        if ($remaining > 0) {
            // Someone else bought the stock a moment earlier. Throwing here rolls
            // the whole transaction back, so nothing is half-saved.
            throw new RuntimeException('Not enough stock for medicine ' . $medicineId);
        }

        $this->recalculateMedicineStock($medicineId);

        return $allocations;
    }

    // ---------------------------------------------------------------------
    // Putting stock back (FR-33)
    // ---------------------------------------------------------------------

    /**
     * Return stock to the exact batches it came from.
     * Used when a pending order is edited or cancelled.
     *
     * @param array $allocations Rows from order_item_batches: batch_id and quantity.
     * @return void
     */
    public function restoreAllocations(array $allocations): void
    {
        foreach ($allocations as $allocation) {
            $this->changeBatchQuantity((int) $allocation['batch_id'], (int) $allocation['quantity']);
        }
    }

    // ---------------------------------------------------------------------
    // Keeping the summary columns correct
    // ---------------------------------------------------------------------

    /**
     * Rewrite medicines.stock_qty and medicines.expiry_date from the batches.
     *
     * stock_qty   = total quantity of usable batches
     * expiry_date = the soonest expiry among usable batches (NULL when there are none)
     *
     * Because this always recalculates from the batches, the summary can never
     * drift away from the real stock.
     *
     * @param int $medicineId The medicine to refresh.
     * @return void
     */
    public function recalculateMedicineStock(int $medicineId): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE medicines m
             SET m.stock_qty = (
                     SELECT COALESCE(SUM(b.quantity), 0)
                     FROM inventory_batches b
                     WHERE b.medicine_id = m.medicine_id
                       AND b.quantity > 0
                       AND b.expiry_date > CURDATE()
                 ),
                 m.expiry_date = (
                     SELECT MIN(b.expiry_date)
                     FROM inventory_batches b
                     WHERE b.medicine_id = m.medicine_id
                       AND b.quantity > 0
                       AND b.expiry_date > CURDATE()
                 )
                      WHERE m.medicine_id = ?'
        );
        $statement->execute([$medicineId]);

        // FR-41: the moment stock drops to or below the threshold, an alert is
        // raised. Doing it here means EVERY stock change is covered: orders,
        // cancellations, edits and new batches, with no scheduled task.
        $stockAlert = new StockAlert($this->pdo);
        $stockAlert->checkAndCreate($medicineId);
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    /**
     * Add to or take from one batch, and keep its is_depleted flag correct.
     *
     * @param int $batchId The batch to change.
     * @param int $change  Negative to take stock out, positive to put it back.
     * @return void
     */
    private function changeBatchQuantity(int $batchId, int $change): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE inventory_batches
             SET quantity = quantity + ?,
                 is_depleted = IF(quantity + ? <= 0, 1, 0)
             WHERE batch_id = ?'
        );
        $statement->execute([$change, $change, $batchId]);
    }

    // ---------------------------------------------------------------------
    // Administrator: managing batches (FR-35, FR-36)
    // ---------------------------------------------------------------------

    /**
     * Batches for the admin list, newest FIFO position last.
     *
     * days_to_expiry lets the list flag batches expiring soon (FR-38), and
     * used_units shows how much of the batch has already been sold.
     *
     * @param int|null $medicineId Only this medicine's batches, or null for all.
     * @return array Batch rows with the medicine name and extra columns.
     */
    public function getBatchesForAdmin(?int $medicineId = null): array
    {
        $sql =
            'SELECT
                 b.*,
                 m.name AS medicine_name,
                 m.sku,
                 DATEDIFF(b.expiry_date, CURDATE()) AS days_to_expiry,
                 (b.quantity_received - b.quantity) AS used_units
             FROM inventory_batches b
             INNER JOIN medicines m ON m.medicine_id = b.medicine_id';

        $values = [];

        if ($medicineId !== null) {
            $sql .= ' WHERE b.medicine_id = ?';
            $values[] = $medicineId;
        }

        $sql .= ' ORDER BY m.name, b.fifo_order';

        $statement = $this->pdo->prepare($sql);
        $statement->execute($values);

        return $statement->fetchAll();
    }

    /**
     * One batch by id, with the medicine it belongs to.
     *
     * @param int $batchId The batch to load.
     * @return array|null The batch row, or null when there is no such batch.
     */
    public function findBatchById(int $batchId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT b.*, m.name AS medicine_name, m.sku
             FROM inventory_batches b
             INNER JOIN medicines m ON m.medicine_id = b.medicine_id
             WHERE b.batch_id = ?'
        );
        $statement->execute([$batchId]);

        $batch = $statement->fetch();

        if ($batch === false) {
            return null;
        }

        return $batch;
    }

    /**
     * The next free FIFO position for a medicine.
     *
     * A new delivery is always the newest stock, so it goes after every batch
     * the medicine already has.
     *
     * @param int $medicineId The medicine the batch belongs to.
     * @return int The next fifo_order value (1 for the first batch).
     */
    public function getNextFifoOrder(int $medicineId): int
    {
        $statement = $this->pdo->prepare(
            'SELECT COALESCE(MAX(fifo_order), 0) + 1 FROM inventory_batches WHERE medicine_id = ?'
        );
        $statement->execute([$medicineId]);

        return (int) $statement->fetchColumn();
    }

    /**
     * Add a new stock batch (FR-35).
     *
     * quantity_received never changes afterwards, so the admin can always see
     * how much of a delivery has been sold. quantity is what is left.
     *
     * @param int   $medicineId The medicine this delivery is for.
     * @param array $data       quantity, supplier_name, cost_price,
     *                          received_date, expiry_date
     * @return int The new batch_id.
     */
    public function createBatch(int $medicineId, array $data): int
    {
        $fifoOrder = $this->getNextFifoOrder($medicineId);

        $statement = $this->pdo->prepare(
            'INSERT INTO inventory_batches
                 (medicine_id, quantity, quantity_received, supplier_name, cost_price,
                  received_date, expiry_date, fifo_order)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $statement->execute([
            $medicineId,
            $data['quantity'],
            $data['quantity'],
            $data['supplier_name'],
            $data['cost_price'],
            $data['received_date'],
            $data['expiry_date'],
            $fifoOrder,
        ]);

        $batchId = (int) $this->pdo->lastInsertId();

        $this->recalculateMedicineStock($medicineId);

        return $batchId;
    }

    /**
     * Change a batch that is already on the shelf.
     *
     * The quantity here is what is LEFT, because part of the batch may already
     * have been sold. quantity_received is not changed, so the record of the
     * original delivery stays intact.
     *
     * @param int   $batchId The batch to change.
     * @param array $data    quantity, supplier_name, cost_price,
     *                       received_date, expiry_date
     * @return void
     */
    public function updateBatch(int $batchId, array $data): void
    {
        $batch = $this->findBatchById($batchId);

        if ($batch === null) {
            return;
        }

        $statement = $this->pdo->prepare(
            'UPDATE inventory_batches
             SET quantity = ?, supplier_name = ?, cost_price = ?,
                 received_date = ?, expiry_date = ?, is_depleted = IF(? <= 0, 1, 0)
             WHERE batch_id = ?'
        );

        $statement->execute([
            $data['quantity'],
            $data['supplier_name'],
            $data['cost_price'],
            $data['received_date'],
            $data['expiry_date'],
            $data['quantity'],
            $batchId,
        ]);

        $this->recalculateMedicineStock((int) $batch['medicine_id']);
    }

    /**
     * How many units of a batch are already promised to orders.
     *
     * A batch cannot be reduced below this, because those units have been sold
     * and would have to come from somewhere.
     *
     * @param int $batchId The batch to check.
     * @return int Units held by orders.
     */
    public function countAllocatedUnits(int $batchId): int
    {
        $statement = $this->pdo->prepare(
            'SELECT COALESCE(SUM(quantity), 0) FROM order_item_batches WHERE batch_id = ?'
        );
        $statement->execute([$batchId]);

        return (int) $statement->fetchColumn();
    }

    // ---------------------------------------------------------------------
    // Expiry warnings (FR-38, FR-43)
    // ---------------------------------------------------------------------

    /**
     * Batches that still hold stock and expire soon, or have already expired.
     *
     * FR-43: "The system shall flag medicines with batches expiring within 30
     *        days on the Administrator dashboard."
     *
     * Batches with no stock left are left out, because there is nothing to
     * lose. Expired batches are included, so the admin can remove them from
     * the shelf even though FIFO already refuses to sell them.
     *
     * @param int $withinDays How far ahead to look.
     * @return array Batch rows, soonest expiry first.
     */
    public function getExpiringBatches(int $withinDays): array
    {
        $withinDays = max(1, $withinDays);

        $statement = $this->pdo->prepare(
            'SELECT
                 b.*,
                 m.name AS medicine_name,
                 m.sku,
                 m.category,
                 m.price,
                 DATEDIFF(b.expiry_date, CURDATE()) AS days_to_expiry,
                 (b.quantity * b.cost_price) AS value_at_risk
             FROM inventory_batches b
             INNER JOIN medicines m ON m.medicine_id = b.medicine_id
             WHERE b.quantity > 0
               AND b.expiry_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
             ORDER BY b.expiry_date, m.name'
        );
        $statement->execute([$withinDays]);

        return $statement->fetchAll();
    }

    /**
     * Write off a batch that has expired.
     *
     * The quantity is set to 0 so the stock summary stops counting it, but the
     * row stays in the database as a record of what was lost.
     *
     * @param int $batchId The batch to write off.
     * @return void
     */
    public function writeOffBatch(int $batchId): void
    {
        $batch = $this->findBatchById($batchId);

        if ($batch === null) {
            return;
        }

        $statement = $this->pdo->prepare(
            'UPDATE inventory_batches SET quantity = 0, is_depleted = 1 WHERE batch_id = ?'
        );
        $statement->execute([$batchId]);

        $this->recalculateMedicineStock((int) $batch['medicine_id']);
    }
}