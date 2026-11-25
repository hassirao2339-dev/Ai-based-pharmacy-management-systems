<?php

/**
 * Report.php - the Report model
 *
 * All the queries behind the four reports, plus the record of every report
 * that was generated.
 *
 * FR-51: sales report for a date range.
 * FR-52: inventory report: current stock, batch details and total stock value.
 * FR-53: order report grouped by status for a date range.
 * FR-54: low-stock report of medicines at or below their threshold.
 * FR-56: each generated report is saved in the reports table (and the audit log).
 *
 * Money figures come from order_items.unit_price, the price AT THE TIME of the
 * order, not today's price. A report of last month must not change when the
 * admin edits a price today.
 */

class Report
{
    private PDO $pdo;

    /** Statuses that count as a real sale, the same list the dashboard uses. */
    public const COUNTED_STATUSES = ['confirmed', 'dispatched', 'delivered'];

    /**
     * @param PDO $pdo The database connection created in bootstrap.php.
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ---------------------------------------------------------------------
    // 1. Sales report (FR-51)
    // ---------------------------------------------------------------------

    /**
     * Totals for a date range: orders, units and revenue.
     *
     * @param string $fromDate Start of the range, YYYY-MM-DD.
     * @param string $toDate   End of the range, YYYY-MM-DD (included).
     * @return array orders, units, revenue, average_order
     */
    public function getSalesSummary(string $fromDate, string $toDate): array
    {
        $placeholders = implode(', ', array_fill(0, count(self::COUNTED_STATUSES), '?'));

        $statement = $this->pdo->prepare(
            'SELECT
                 COUNT(DISTINCT o.order_id) AS orders,
                 COALESCE(SUM(oi.quantity), 0) AS units,
                 COALESCE(SUM(oi.subtotal), 0) AS revenue
             FROM orders o
             INNER JOIN order_items oi ON oi.order_id = o.order_id
             WHERE o.status IN (' . $placeholders . ')
               AND DATE(o.order_date) BETWEEN ? AND ?'
        );

        $statement->execute(array_merge(self::COUNTED_STATUSES, [$fromDate, $toDate]));

        $summary = $statement->fetch();

        $orders = (int) $summary['orders'];
        $revenue = (float) $summary['revenue'];

        return [
            'orders' => $orders,
            'units' => (int) $summary['units'],
            'revenue' => $revenue,
            'average_order' => ($orders > 0) ? ($revenue / $orders) : 0.0,
        ];
    }

    /**
     * What sold in a date range, medicine by medicine (FR-51).
     *
     * @param string $fromDate Start of the range.
     * @param string $toDate   End of the range.
     * @return array Rows with the medicine, units sold and revenue.
     */
    public function getSalesByMedicine(string $fromDate, string $toDate): array
    {
        $placeholders = implode(', ', array_fill(0, count(self::COUNTED_STATUSES), '?'));

        $statement = $this->pdo->prepare(
            'SELECT
                 m.medicine_id,
                 m.name,
                 m.sku,
                 m.category,
                 SUM(oi.quantity) AS units,
                 SUM(oi.subtotal) AS revenue
             FROM order_items oi
             INNER JOIN orders o ON o.order_id = oi.order_id
             INNER JOIN medicines m ON m.medicine_id = oi.medicine_id
             WHERE o.status IN (' . $placeholders . ')
               AND DATE(o.order_date) BETWEEN ? AND ?
             GROUP BY m.medicine_id, m.name, m.sku, m.category
             ORDER BY revenue DESC, m.name'
        );

        $statement->execute(array_merge(self::COUNTED_STATUSES, [$fromDate, $toDate]));

        return $statement->fetchAll();
    }

    /**
     * Revenue day by day, so the report can show the busiest days (FR-51).
     *
     * @param string $fromDate Start of the range.
     * @param string $toDate   End of the range.
     * @return array Rows with sale_date, orders and revenue.
     */
    public function getSalesByDay(string $fromDate, string $toDate): array
    {
        $placeholders = implode(', ', array_fill(0, count(self::COUNTED_STATUSES), '?'));

        $statement = $this->pdo->prepare(
            'SELECT
                 DATE(o.order_date) AS sale_date,
                 COUNT(DISTINCT o.order_id) AS orders,
                 SUM(oi.subtotal) AS revenue
             FROM orders o
             INNER JOIN order_items oi ON oi.order_id = o.order_id
             WHERE o.status IN (' . $placeholders . ')
               AND DATE(o.order_date) BETWEEN ? AND ?
             GROUP BY DATE(o.order_date)
             ORDER BY sale_date'
        );

        $statement->execute(array_merge(self::COUNTED_STATUSES, [$fromDate, $toDate]));

        return $statement->fetchAll();
    }

    // ---------------------------------------------------------------------
    // 2. Inventory report (FR-52)
    // ---------------------------------------------------------------------

    /**
     * Current stock of every active medicine, with what it is worth (FR-52).
     *
     * Two values are shown for each medicine:
     *   cost value    - what the pharmacy paid (from the batches)
     *   retail value  - what it would sell for (stock x price)
     *
     * @return array Medicine rows with batch counts and values.
     */
    public function getInventoryReport(): array
    {
        $statement = $this->pdo->query(
            'SELECT
                 m.medicine_id,
                 m.name,
                 m.sku,
                 m.category,
                 m.manufacturer,
                 m.price,
                 m.stock_qty,
                 m.reorder_threshold,
                 m.expiry_date,
                 (m.stock_qty * m.price) AS retail_value,
                 (SELECT COUNT(*) FROM inventory_batches b
                  WHERE b.medicine_id = m.medicine_id AND b.quantity > 0) AS live_batches,
                 (SELECT COALESCE(SUM(b.quantity * b.cost_price), 0) FROM inventory_batches b
                  WHERE b.medicine_id = m.medicine_id
                    AND b.quantity > 0
                    AND b.expiry_date > CURDATE()) AS cost_value
             FROM medicines m
             WHERE m.is_active = 1
             ORDER BY m.name'
        );

        return $statement->fetchAll();
    }

    // ---------------------------------------------------------------------
    // 3. Order report (FR-53)
    // ---------------------------------------------------------------------

    /**
     * How many orders sit in each status for a date range (FR-53).
     *
     * Every status is counted here, including pending and cancelled, because
     * this report is about orders rather than money.
     *
     * @param string $fromDate Start of the range.
     * @param string $toDate   End of the range.
     * @return array Rows with status, orders and total_value.
     */
    public function getOrdersByStatus(string $fromDate, string $toDate): array
    {
        $statement = $this->pdo->prepare(
            'SELECT
                 status,
                 COUNT(*) AS orders,
                 COALESCE(SUM(total_amount), 0) AS total_value
             FROM orders
             WHERE DATE(order_date) BETWEEN ? AND ?
             GROUP BY status
             ORDER BY FIELD(status, \'pending\', \'confirmed\', \'dispatched\', \'delivered\', \'cancelled\')'
        );
        $statement->execute([$fromDate, $toDate]);

        return $statement->fetchAll();
    }

    /**
     * The orders themselves for a date range (FR-53).
     *
     * @param string $fromDate Start of the range.
     * @param string $toDate   End of the range.
     * @return array Order rows with the customer's name.
     */
    public function getOrdersInRange(string $fromDate, string $toDate): array
    {
        $statement = $this->pdo->prepare(
            'SELECT
                 o.order_id,
                 o.order_date,
                 o.status,
                 o.total_amount,
                 u.name AS customer_name,
                 (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.order_id) AS item_count
             FROM orders o
             INNER JOIN users u ON u.user_id = o.customer_id
             WHERE DATE(o.order_date) BETWEEN ? AND ?
             ORDER BY o.order_date DESC, o.order_id DESC'
        );
        $statement->execute([$fromDate, $toDate]);

        return $statement->fetchAll();
    }

    // ---------------------------------------------------------------------
    // 4. Low-stock report (FR-54)
    // ---------------------------------------------------------------------

    /**
     * Medicines at or below their reorder threshold (FR-54).
     *
     * suggested_order is a simple restocking hint: enough to reach twice the
     * threshold, which leaves room before the next alert.
     *
     * @return array Medicine rows, lowest stock first.
     */
    public function getLowStockReport(): array
    {
        $statement = $this->pdo->query(
            'SELECT
                 m.medicine_id,
                 m.name,
                 m.sku,
                 m.category,
                 m.manufacturer,
                 m.price,
                 m.stock_qty,
                 m.reorder_threshold,
                 GREATEST((m.reorder_threshold * 2) - m.stock_qty, 0) AS suggested_order,
                 (SELECT b.supplier_name FROM inventory_batches b
                  WHERE b.medicine_id = m.medicine_id
                  ORDER BY b.received_date DESC, b.batch_id DESC
                  LIMIT 1) AS last_supplier
             FROM medicines m
             WHERE m.is_active = 1 AND m.stock_qty <= m.reorder_threshold
             ORDER BY m.stock_qty, m.name'
        );

        return $statement->fetchAll();
    }

    // ---------------------------------------------------------------------
    // The record of generated reports (FR-56)
    // ---------------------------------------------------------------------

    /**
     * Save the fact that a report was generated (FR-56).
     *
     * @param int         $userId     The admin who generated it.
     * @param string      $reportType sales, inventory, orders or low_stock.
     * @param string|null $fromDate   Start of the range, or null for a snapshot report.
     * @param string|null $toDate     End of the range, or null.
     * @param string|null $filePath   Where the PDF was saved, if it was saved.
     * @return int The new report_id.
     */
    public function recordGeneration(
        int $userId,
        string $reportType,
        ?string $fromDate = null,
        ?string $toDate = null,
        ?string $filePath = null
    ): int {
        $statement = $this->pdo->prepare(
            'INSERT INTO reports (report_type, generated_by, date_from, date_to, file_path)
             VALUES (?, ?, ?, ?, ?)'
        );
        $statement->execute([$reportType, $userId, $fromDate, $toDate, $filePath]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * The reports generated so far, newest first.
     *
     * @param int $limit How many to return.
     * @return array Report rows with the admin's name.
     */
    public function getRecentReports(int $limit = 20): array
    {
        $limit = max(1, $limit);

        $statement = $this->pdo->query(
            'SELECT r.*, u.name AS admin_name
             FROM reports r
             INNER JOIN users u ON u.user_id = r.generated_by
             ORDER BY r.generated_at DESC, r.report_id DESC
             LIMIT ' . (int) $limit
        );

        return $statement->fetchAll();
    }
}