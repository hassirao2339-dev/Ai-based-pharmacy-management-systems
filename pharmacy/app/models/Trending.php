<?php

/**
 * Trending.php - the Trending model
 *
 * FR-45: identify the top 10 most ordered medicines in the current month.
 * FR-46: rank them by total quantity ordered.
 * FR-47: compare this month with the previous month.
 * FR-49: show the top 5 on the customer homepage.
 * FR-50: the data updates automatically when an order is placed.
 *
 * Everything here reads the order_trending_summary VIEW created in
 * schema.sql. A view is a saved query, not a stored table: MySQL runs it
 * fresh every time, so there is nothing to keep in step and nothing to
 * recalculate. That is how FR-50 is satisfied with no extra code.
 *
 * The view only counts orders with status confirmed, dispatched or delivered,
 * the same rule the dashboard revenue uses. A pending order might still be
 * cancelled, and a cancelled order was never really sold.
 */

class Trending
{
    private PDO $pdo;

    /**
     * @param PDO $pdo The database connection created in bootstrap.php.
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * The most ordered medicines of one month (FR-45, FR-46).
     *
     * @param int $year  Which year, for example 2026.
     * @param int $month Which month, 1 to 12.
     * @param int $limit How many to return (10 for the admin page, 5 for the homepage).
     * @return array Medicine rows with total_ordered, most ordered first.
     */
    public function getTopMedicines(int $year, int $month, int $limit = 10): array
    {
        $limit = max(1, $limit);

        $statement = $this->pdo->prepare(
            'SELECT
                 t.medicine_id,
                 t.total_ordered,
                 m.name,
                 m.sku,
                 m.category,
                 m.manufacturer,
                 m.price,
                 m.stock_qty,
                 m.reorder_threshold,
                 m.is_active,
                 (t.total_ordered * m.price) AS revenue
             FROM order_trending_summary t
             INNER JOIN medicines m ON m.medicine_id = t.medicine_id
             WHERE t.order_year = ? AND t.order_month = ?
             ORDER BY t.total_ordered DESC, m.name
             LIMIT ' . (int) $limit
        );
        $statement->execute([$year, $month]);

        return $statement->fetchAll();
    }

    /**
     * The medicines customers can actually buy right now, for the homepage (FR-49).
     *
     * Hidden medicines are left out, because a customer clicking a trending
     * card should always reach a real page.
     *
     * @param int $year  Which year.
     * @param int $month Which month.
     * @param int $limit How many to return.
     * @return array Medicine rows.
     */
    public function getTopActiveMedicines(int $year, int $month, int $limit = 5): array
    {
        $limit = max(1, $limit);

        $statement = $this->pdo->prepare(
            'SELECT
                 t.medicine_id,
                 t.total_ordered,
                 m.name,
                 m.sku,
                 m.category,
                 m.manufacturer,
                 m.price,
                 m.stock_qty,
                 m.reorder_threshold,
                 m.description
             FROM order_trending_summary t
             INNER JOIN medicines m ON m.medicine_id = t.medicine_id
             WHERE t.order_year = ? AND t.order_month = ? AND m.is_active = 1
             ORDER BY t.total_ordered DESC, m.name
             LIMIT ' . (int) $limit
        );
        $statement->execute([$year, $month]);

        return $statement->fetchAll();
    }

    /**
     * How much of one medicine was ordered in one month.
     * Used to work out the month-on-month change (FR-47).
     *
     * @param int $medicineId The medicine to look up.
     * @param int $year       Which year.
     * @param int $month      Which month.
     * @return int Units ordered, or 0 when there were none.
     */
    public function getMonthlyQuantity(int $medicineId, int $year, int $month): int
    {
        $statement = $this->pdo->prepare(
            'SELECT COALESCE(total_ordered, 0)
             FROM order_trending_summary
             WHERE medicine_id = ? AND order_year = ? AND order_month = ?'
        );
        $statement->execute([$medicineId, $year, $month]);

        return (int) $statement->fetchColumn();
    }

    /**
     * The top medicines of a month, each with last month's figure beside it (FR-47).
     *
     * change_percent is null when the medicine sold nothing last month, because
     * "up 100%" from zero would be misleading. The view shows "New" instead.
     *
     * @param int $year  Which year.
     * @param int $month Which month.
     * @param int $limit How many to return.
     * @return array Medicine rows with previous_ordered and change_percent.
     */
    public function getTopWithComparison(int $year, int $month, int $limit = 10): array
    {
        $medicines = $this->getTopMedicines($year, $month, $limit);

        // Step back one month, rolling the year over in January.
        $previousMonth = $month - 1;
        $previousYear = $year;

        if ($previousMonth < 1) {
            $previousMonth = 12;
            $previousYear = $year - 1;
        }

        foreach ($medicines as $index => $medicine) {
            $previous = $this->getMonthlyQuantity((int) $medicine['medicine_id'], $previousYear, $previousMonth);
            $current = (int) $medicine['total_ordered'];

            $medicines[$index]['previous_ordered'] = $previous;
            $medicines[$index]['change_percent'] = null;

            if ($previous > 0) {
                $medicines[$index]['change_percent'] = round((($current - $previous) / $previous) * 100, 1);
            }
        }

        return $medicines;
    }

    /**
     * Totals for one month: how many units were sold and what they were worth.
     *
     * @param int $year  Which year.
     * @param int $month Which month.
     * @return array units and medicines (how many different medicines sold).
     */
    public function getMonthTotals(int $year, int $month): array
    {
        $statement = $this->pdo->prepare(
            'SELECT
                 COALESCE(SUM(total_ordered), 0) AS units,
                 COUNT(*) AS medicines
             FROM order_trending_summary
             WHERE order_year = ? AND order_month = ?'
        );
        $statement->execute([$year, $month]);

        $totals = $statement->fetch();

        return [
            'units' => (int) $totals['units'],
            'medicines' => (int) $totals['medicines'],
        ];
    }

    /**
     * Which months have any trending data, newest first.
     * The admin page uses this to fill its month picker.
     *
     * @param int $limit How many months to return.
     * @return array Rows with order_year and order_month.
     */
    public function getAvailableMonths(int $limit = 12): array
    {
        $limit = max(1, $limit);

        $statement = $this->pdo->query(
            'SELECT DISTINCT order_year, order_month
             FROM order_trending_summary
             ORDER BY order_year DESC, order_month DESC
             LIMIT ' . (int) $limit
        );

        return $statement->fetchAll();
    }
}