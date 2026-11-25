<?php

/**
 * StockAlert.php - the StockAlert model
 *
 * Every SQL query for the "stock_alerts" table lives here.
 *
 * FR-41: "The system shall automatically generate a low-stock alert when a
 *        medicine's stock quantity falls to or below its reorder threshold."
 * FR-42: the administrator can see open alerts and acknowledge them.
 *
 * How an alert is created:
 *   Inventory::recalculateMedicineStock() runs after every stock change, and
 *   calls checkAndCreate() straight afterwards. So an alert appears the moment
 *   an order pushes stock below the threshold, without any scheduled task.
 *
 * Only ONE open alert exists per medicine at a time. Without that rule, every
 * order of a low medicine would add another identical alert.
 */

class StockAlert
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
    // Creating alerts (FR-41)
    // ---------------------------------------------------------------------

    /**
     * Create a low-stock alert for a medicine if one is needed (FR-41).
     *
     * Nothing happens when:
     *   - the medicine is hidden from the catalogue
     *   - its stock is still above the threshold
     *   - an open alert for it already exists
     *
     * @param int $medicineId The medicine to check.
     * @return void
     */
    public function checkAndCreate(int $medicineId): void
    {
        $statement = $this->pdo->prepare(
            'SELECT stock_qty, reorder_threshold, is_active FROM medicines WHERE medicine_id = ?'
        );
        $statement->execute([$medicineId]);

        $medicine = $statement->fetch();

        if ($medicine === false || (int) $medicine['is_active'] !== 1) {
            return;
        }

        $stock = (int) $medicine['stock_qty'];
        $threshold = (int) $medicine['reorder_threshold'];

        // Stock is healthy again: close any open alert instead of raising one.
        if ($stock > $threshold) {
            $this->resolveForMedicine($medicineId);

            return;
        }

        if ($this->hasOpenAlert($medicineId)) {
            return;
        }

        $statement = $this->pdo->prepare(
                        'INSERT INTO stock_alerts (medicine_id, current_stock, threshold)
             VALUES (?, ?, ?)'
        );
        $statement->execute([$medicineId, $stock, $threshold]);
    }

    /**
     * Whether a medicine already has an alert the admin has not dealt with.
     *
     * @param int $medicineId The medicine to check.
     * @return bool True when an open alert exists.
     */
    public function hasOpenAlert(int $medicineId): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*) FROM stock_alerts WHERE medicine_id = ? AND is_acknowledged = 0'
        );
        $statement->execute([$medicineId]);

        return (int) $statement->fetchColumn() > 0;
    }

    /**
     * Close the open alerts of a medicine, because its stock is healthy again.
     *
     * This happens on its own when the admin adds a batch, so the admin does
     * not have to tidy up alerts that have already been dealt with.
     *
     * @param int $medicineId The medicine that was restocked.
     * @return void
     */
    public function resolveForMedicine(int $medicineId): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE stock_alerts
             SET is_acknowledged = 1, acknowledged_at = NOW()
             WHERE medicine_id = ? AND is_acknowledged = 0'
        );
        $statement->execute([$medicineId]);
    }

    // ---------------------------------------------------------------------
    // Reading alerts (FR-42)
    // ---------------------------------------------------------------------

    /**
     * Alerts for the admin page, newest first.
     *
     * current_stock in the row is what the stock WAS when the alert was raised.
     * The medicine's stock_qty is what it is NOW, so the page can show both.
     *
     * @param bool $onlyOpen true for alerts still waiting, false for all.
     * @return array Alert rows with the medicine's details.
     */
    public function getAlerts(bool $onlyOpen = true): array
    {
        $sql =
            'SELECT
                                  a.*,
                 a.triggered_at AS alert_date,
                 m.name AS medicine_name,
                 m.sku,
                 m.category,
                 m.stock_qty AS stock_now,
                 m.reorder_threshold,
                 m.is_active
             FROM stock_alerts a
             INNER JOIN medicines m ON m.medicine_id = a.medicine_id';

        if ($onlyOpen) {
            $sql .= ' WHERE a.is_acknowledged = 0';
        }

        $sql .= ' ORDER BY a.is_acknowledged, a.triggered_at DESC, a.alert_id DESC';

        return $this->pdo->query($sql)->fetchAll();
    }

    /**
     * One alert by id.
     *
     * @param int $alertId The alert to load.
     * @return array|null The alert row, or null when it does not exist.
     */
    public function findById(int $alertId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT a.*, m.name AS medicine_name
             FROM stock_alerts a
             INNER JOIN medicines m ON m.medicine_id = a.medicine_id
             WHERE a.alert_id = ?'
        );
        $statement->execute([$alertId]);

        $alert = $statement->fetch();

        if ($alert === false) {
            return null;
        }

        return $alert;
    }

    /**
     * How many alerts are still waiting for the admin (FR-44).
     *
     * @return int Number of open alerts.
     */
    public function countOpen(): int
    {
        return (int) $this->pdo->query(
            'SELECT COUNT(*) FROM stock_alerts WHERE is_acknowledged = 0'
        )->fetchColumn();
    }

    // ---------------------------------------------------------------------
    // Acknowledging (FR-42)
    // ---------------------------------------------------------------------

    /**
     * Mark one alert as dealt with (FR-42).
     *
     * @param int $alertId The alert to acknowledge.
     * @return void
     */
    public function acknowledge(int $alertId): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE stock_alerts
             SET is_acknowledged = 1, acknowledged_at = NOW()
             WHERE alert_id = ? AND is_acknowledged = 0'
        );
        $statement->execute([$alertId]);
    }

    /**
     * Mark every open alert as dealt with.
     *
     * @return int How many alerts were closed.
     */
    public function acknowledgeAll(): int
    {
        $statement = $this->pdo->query(
            'UPDATE stock_alerts
             SET is_acknowledged = 1, acknowledged_at = NOW()
             WHERE is_acknowledged = 0'
        );

        return $statement->rowCount();
    }
}