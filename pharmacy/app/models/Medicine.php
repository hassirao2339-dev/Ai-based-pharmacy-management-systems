<?php

/**
 * Medicine.php - the Medicine model
 *
 * Every SQL query for the "medicines" table lives in this class.
 *
 * Only ACTIVE medicines (is_active = 1) are shown to customers. A medicine the
 * administrator soft-deletes (FR-39) disappears from the catalogue but stays in
 * the database, so old orders still show its name.
 *
 * stock_qty and expiry_date on this table are summaries. From Phase 5 a single
 * function recalculates them from inventory_batches after every stock change.
 */

class Medicine
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
    // The catalogue (FR-18, FR-19, FR-20)
    // ---------------------------------------------------------------------

    /**
     * Search and filter the catalogue.
     *
     * Both arguments are optional:
     *   search()                          -> every active medicine
     *   search('panadol')                 -> name, SKU or manufacturer contains "panadol"
     *   search('', 'Antibiotic')          -> one category only
     *   search('syrup', 'Antibiotic')     -> both at once
     *
     * The SQL is built up in pieces, and every piece adds a "?" placeholder
     * instead of the value itself, so this stays injection-safe.
     *
     * @param string $searchText Words typed in the search box (FR-19).
     * @param string $category   Exact category name, or '' for all (FR-20).
     * @return array Matching medicine rows, out-of-stock ones last.
     */
    public function search(string $searchText = '', string $category = ''): array
    {
        $sql = 'SELECT * FROM medicines WHERE is_active = 1';
        $values = [];

        if ($searchText !== '') {
            // LIKE with % on both sides means "contains". FR-19 allows name, SKU or manufacturer.
            $sql .= ' AND (name LIKE ? OR sku LIKE ? OR manufacturer LIKE ?)';

            $pattern = '%' . $this->escapeLike($searchText) . '%';
            $values[] = $pattern;
            $values[] = $pattern;
            $values[] = $pattern;
        }

        if ($category !== '') {
            $sql .= ' AND category = ?';
            $values[] = $category;
        }

        // Medicines that are in stock come first, then alphabetical order.
        $sql .= ' ORDER BY (stock_qty = 0), name';

        $statement = $this->pdo->prepare($sql);
        $statement->execute($values);

        return $statement->fetchAll();
    }

    /**
     * Get one active medicine for the details page (FR-21).
     *
     * @param int $medicineId The medicine to load.
     * @return array|null The medicine row, or null when it does not exist or is hidden.
     */
    public function findActiveById(int $medicineId): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM medicines WHERE medicine_id = ? AND is_active = 1');
        $statement->execute([$medicineId]);

        $medicine = $statement->fetch();

        if ($medicine === false) {
            return null;
        }

        return $medicine;
    }

    /**
     * Every category that has at least one active medicine, in alphabetical order.
     * Used for the category filter (FR-20) and the homepage chips.
     *
     * @return array A simple list of category names.
     */
    public function getCategories(): array
    {
        $statement = $this->pdo->query(
            'SELECT DISTINCT category FROM medicines WHERE is_active = 1 ORDER BY category'
        );

        return $statement->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * The newest medicines, for the "Browse medicines" section of the homepage.
     *
     * @param int $limit How many to return.
     * @return array Medicine rows, newest first.
     */
    public function getLatest(int $limit = 4): array
    {
        // The limit is a number from our own code, never from the user, and it is
        // forced to an integer here. LIMIT cannot use a "?" placeholder in MySQL.
        $limit = max(1, $limit);

        $statement = $this->pdo->query(
            'SELECT * FROM medicines WHERE is_active = 1 ORDER BY created_at DESC, medicine_id DESC LIMIT ' . (int) $limit
        );

        return $statement->fetchAll();
    }

    /**
     * Other medicines in the same category, shown at the bottom of a details page.
     *
     * @param int    $medicineId The medicine currently being viewed (left out of the list).
     * @param string $category   Its category.
     * @param int    $limit      How many to return.
     * @return array Medicine rows.
     */
    public function getRelated(int $medicineId, string $category, int $limit = 4): array
    {
        $limit = max(1, $limit);

        $statement = $this->pdo->prepare(
            'SELECT * FROM medicines
             WHERE is_active = 1 AND category = ? AND medicine_id <> ?
             ORDER BY (stock_qty = 0), name
             LIMIT ' . (int) $limit
        );
        $statement->execute([$category, $medicineId]);

        return $statement->fetchAll();
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    /**
     * Make a search word safe to use inside a LIKE pattern.
     *
     * In LIKE, % means "anything" and _ means "any single character". Someone
     * searching for "%" would otherwise match every medicine, so those
     * characters are turned into ordinary text.
     *
     * @param string $text The words typed by the customer.
     * @return string The text with LIKE wildcards escaped.
     */
    private function escapeLike(string $text): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $text);
    }

    // ---------------------------------------------------------------------
    // Administrator: listing and searching (FR-40)
    // ---------------------------------------------------------------------

    /**
     * Every medicine for the admin list, including soft-deleted ones.
     *
     * The customer catalogue only shows active medicines; the admin needs to
     * see hidden ones too, so they can bring them back (FR-39).
     * days_to_expiry lets the list flag stock expiring soon (FR-38).
     *
     * @return array Medicine rows with batch_count and days_to_expiry.
     */
    public function getAllForAdmin(): array
    {
        $statement = $this->pdo->query(
            'SELECT
                 m.*,
                 (SELECT COUNT(*) FROM inventory_batches b
                  WHERE b.medicine_id = m.medicine_id) AS batch_count,
                 DATEDIFF(m.expiry_date, CURDATE()) AS days_to_expiry
             FROM medicines m
             ORDER BY m.name'
        );

        return $statement->fetchAll();
    }

    /**
     * One medicine by id, whether it is active or hidden.
     * Used by the admin edit form; the customer pages use findActiveById().
     *
     * @param int $medicineId The medicine to load.
     * @return array|null The medicine row, or null when there is no such medicine.
     */
    public function findById(int $medicineId): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM medicines WHERE medicine_id = ?');
        $statement->execute([$medicineId]);

        $medicine = $statement->fetch();

        if ($medicine === false) {
            return null;
        }

        return $medicine;
    }

    /**
     * Whether an SKU is already used by another medicine.
     *
     * @param string   $sku              The SKU to check.
     * @param int|null $ignoreMedicineId When editing, the medicine keeps its own SKU.
     * @return bool True when another medicine already uses this SKU.
     */
    public function skuExists(string $sku, ?int $ignoreMedicineId = null): bool
    {
        if ($ignoreMedicineId === null) {
            $statement = $this->pdo->prepare('SELECT COUNT(*) FROM medicines WHERE sku = ?');
            $statement->execute([$sku]);
        } else {
            $statement = $this->pdo->prepare('SELECT COUNT(*) FROM medicines WHERE sku = ? AND medicine_id <> ?');
            $statement->execute([$sku, $ignoreMedicineId]);
        }

        return (int) $statement->fetchColumn() > 0;
    }

    // ---------------------------------------------------------------------
    // Administrator: adding and editing (FR-34, FR-39)
    // ---------------------------------------------------------------------

    /**
     * Add a new medicine (FR-34).
     *
     * A new medicine starts with no stock. Stock only arrives when the admin
     * adds an inventory batch (FR-35).
     *
     * @param array $data name, sku, category, drap_category, manufacturer,
     *                    description, price, reorder_threshold
     * @return int The new medicine_id.
     */
    public function create(array $data): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO medicines
                 (name, sku, category, drap_category, manufacturer, description, price, reorder_threshold)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $statement->execute([
            $data['name'],
            $data['sku'],
            $data['category'],
            $data['drap_category'],
            $data['manufacturer'],
            $data['description'],
            $data['price'],
            $data['reorder_threshold'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Change an existing medicine (FR-39).
     *
     * stock_qty and expiry_date are NOT set here: they are summaries that the
     * Inventory model recalculates from the batches.
     *
     * @param int   $medicineId The medicine to change.
     * @param array $data       The same fields as create().
     * @return void
     */
    public function update(int $medicineId, array $data): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE medicines
             SET name = ?, sku = ?, category = ?, drap_category = ?,
                 manufacturer = ?, description = ?, price = ?, reorder_threshold = ?
             WHERE medicine_id = ?'
        );

        $statement->execute([
            $data['name'],
            $data['sku'],
            $data['category'],
            $data['drap_category'],
            $data['manufacturer'],
            $data['description'],
            $data['price'],
            $data['reorder_threshold'],
            $medicineId,
        ]);
    }

    /**
     * Hide or show a medicine (FR-39: soft delete).
     *
     * The row is never deleted, because old orders point at it. Hiding it
     * removes it from the catalogue while keeping order history readable.
     *
     * @param int  $medicineId The medicine to change.
     * @param bool $isActive   true shows it in the catalogue, false hides it.
     * @return void
     */
    public function setActive(int $medicineId, bool $isActive): void
    {
        $statement = $this->pdo->prepare('UPDATE medicines SET is_active = ? WHERE medicine_id = ?');
        $statement->execute([$isActive ? 1 : 0, $medicineId]);
    }

    /**
     * Every category name already in use, including hidden medicines.
     * The add and edit form offers these as suggestions.
     *
     * @return array Category names.
     */
    public function getAllCategories(): array
    {
        $statement = $this->pdo->query('SELECT DISTINCT category FROM medicines ORDER BY category');

        return $statement->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Every manufacturer name already in use.
     *
     * @return array Manufacturer names.
     */
    public function getAllManufacturers(): array
    {
        $statement = $this->pdo->query('SELECT DISTINCT manufacturer FROM medicines ORDER BY manufacturer');

        return $statement->fetchAll(PDO::FETCH_COLUMN);
    }
}