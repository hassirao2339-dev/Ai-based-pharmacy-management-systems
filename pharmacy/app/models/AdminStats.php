<?php

/**
 * AdminStats.php - numbers for the admin dashboard
 *
 * FR-57: the dashboard shows total orders today, pending orders, total revenue
 * this month, active low-stock alerts and total registered customers.
 *
 * These are read-only queries that only the admin panel uses, so they are kept
 * together here instead of being spread across the other models.
 *
 * Revenue only counts orders the pharmacy has accepted: confirmed, dispatched
 * or delivered. A pending order might still be cancelled, and a cancelled one
 * was never sold, so neither counts as money earned.
 */

class AdminStats
{
    private PDO $pdo;

    /**
     * Statuses that count as a real sale.
     * The trending view (Phase 9) and the reports (Phase 10) use the same list.
     */
    public const COUNTED_STATUSES = ['confirmed', 'dispatched', 'delivered'];

    /**
     * @param PDO $pdo The database connection created in bootstrap.php.
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Every number the dashboard panel shows (FR-57).
     *
     * @return array today_orders, pending_orders, month_revenue,
     *               active_alerts, total_customers, low_stock_medicines
     */
    public function getDashboardMetrics(): array
    {
        return [
            'today_orders' => $this->countOrdersToday(),
            'pending_orders' => $this->countOrdersByStatus('pending'),
            'month_revenue' => $this->getRevenueThisMonth(),
            'active_alerts' => $this->countActiveAlerts(),
            'total_customers' => $this->countCustomers(),
            'low_stock_medicines' => $this->countLowStockMedicines(),
        ];
    }

    /**
     * Orders placed today, whatever their status.
     *
     * @return int Number of orders.
     */
    public function countOrdersToday(): int
    {
        $statement = $this->pdo->query(
            'SELECT COUNT(*) FROM orders WHERE DATE(order_date) = CURDATE()'
        );

        return (int) $statement->fetchColumn();
    }

    /**
     * Orders with one particular status.
     *
     * @param string $status pending, confirmed, dispatched, delivered or cancelled.
     * @return int Number of orders.
     */
    public function countOrdersByStatus(string $status): int
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM orders WHERE status = ?');
        $statement->execute([$status]);

        return (int) $statement->fetchColumn();
    }

    /**
     * Money earned this calendar month.
     *
     * @return float The total.
     */
    public function getRevenueThisMonth(): float
    {
        // One "?" for each counted status, built from the list above.
        $placeholders = implode(', ', array_fill(0, count(self::COUNTED_STATUSES), '?'));

        $statement = $this->pdo->prepare(
            'SELECT COALESCE(SUM(total_amount), 0)
             FROM orders
             WHERE status IN (' . $placeholders . ')
               AND YEAR(order_date) = YEAR(CURDATE())
               AND MONTH(order_date) = MONTH(CURDATE())'
        );
        $statement->execute(self::COUNTED_STATUSES);

        return (float) $statement->fetchColumn();
    }

    /**
     * Low-stock alerts the admin has not acknowledged yet (FR-42).
     *
     * @return int Number of open alerts.
     */
    public function countActiveAlerts(): int
    {
        $statement = $this->pdo->query(
            'SELECT COUNT(*) FROM stock_alerts WHERE is_acknowledged = 0'
        );

        return (int) $statement->fetchColumn();
    }

    /**
     * Registered customer accounts (admins are not counted).
     *
     * @return int Number of customers.
     */
    public function countCustomers(): int
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM users WHERE role = ?');
        $statement->execute(['customer']);

        return (int) $statement->fetchColumn();
    }

    /**
     * Active medicines at or below their reorder threshold (FR-44).
     *
     * @return int Number of medicines.
     */
    public function countLowStockMedicines(): int
    {
        $statement = $this->pdo->query(
            'SELECT COUNT(*) FROM medicines WHERE is_active = 1 AND stock_qty <= reorder_threshold'
        );

        return (int) $statement->fetchColumn();
    }

    /**
     * The newest orders, for the dashboard list.
     *
     * @param int $limit How many to return.
     * @return array Order rows with the customer's name.
     */
    public function getLatestOrders(int $limit = 5): array
    {
        $limit = max(1, $limit);

        $statement = $this->pdo->query(
            'SELECT o.*, u.name AS customer_name
             FROM orders o
             INNER JOIN users u ON u.user_id = o.customer_id
             ORDER BY o.order_date DESC, o.order_id DESC
             LIMIT ' . (int) $limit
        );

        return $statement->fetchAll();
    }

    /**
     * Medicines that need restocking, for the dashboard list (FR-42).
     *
     * @param int $limit How many to return.
     * @return array Medicine rows, lowest stock first.
     */
    public function getLowStockMedicines(int $limit = 5): array
    {
        $limit = max(1, $limit);

        $statement = $this->pdo->query(
            'SELECT medicine_id, name, sku, stock_qty, reorder_threshold
             FROM medicines
             WHERE is_active = 1 AND stock_qty <= reorder_threshold
             ORDER BY stock_qty, name
             LIMIT ' . (int) $limit
        );

        return $statement->fetchAll();
    }

    /**
     * Mark the pending orders as seen, so the "new" badge goes away (FR-29).
     *
     * @return void
     */
    public function markOrdersAsSeen(): void
    {
        $this->pdo->exec('UPDATE orders SET notified_admin = 1 WHERE notified_admin = 0');
    }
}