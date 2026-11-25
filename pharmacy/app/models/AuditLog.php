<?php

/**
 * AuditLog.php - the AuditLog model
 *
 * FR-60: "maintain an audit log recording all critical Administrator actions
 * including account changes, order status updates, medicine deletions, and
 * report generation events."
 *
 * An audit log answers the question "who changed this, and when?". Rows are
 * only ever added here, never changed or deleted, so the record stays honest.
 */

class AuditLog
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
     * Record one administrator action (FR-60).
     *
     * @param int         $userId        The admin who did it.
     * @param string      $actionType    A short code, for example "order_status_update".
     * @param string      $description   What happened, in plain words.
     * @param string|null $affectedTable Which table it touched, if any.
     * @param int|null    $affectedId    Which row it touched, if any.
     * @return void
     */
    public function record(
        int $userId,
        string $actionType,
        string $description,
        ?string $affectedTable = null,
        ?int $affectedId = null
    ): void {
        $statement = $this->pdo->prepare(
            'INSERT INTO audit_log (user_id, action_type, description, affected_table, affected_id, ip_address)
             VALUES (?, ?, ?, ?, ?, ?)'
        );

        $statement->execute([
            $userId,
            $actionType,
            $description,
            $affectedTable,
            $affectedId,
            $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }

    /**
     * The most recent log entries, with the name of the admin who acted.
     *
     * @param int $limit How many entries to return.
     * @return array Log rows, newest first.
     */
    public function getRecent(int $limit = 100): array
    {
        $limit = max(1, $limit);

        $statement = $this->pdo->query(
            'SELECT a.*, u.name AS user_name, u.email AS user_email
             FROM audit_log a
             INNER JOIN users u ON u.user_id = a.user_id
             ORDER BY a.logged_at DESC, a.log_id DESC
             LIMIT ' . (int) $limit
        );

        return $statement->fetchAll();
    }

    /**
     * How many entries the log holds in total.
     *
     * @return int Number of log rows.
     */
    public function countAll(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM audit_log')->fetchColumn();
    }
}