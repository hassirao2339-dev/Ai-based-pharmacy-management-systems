<?php

/**
 * Prescription.php - the Prescription model
 *
 * Every SQL query for the "prescriptions" table lives here.
 *
 * FR-09: the customer uploads a prescription image.
 * FR-11 to FR-14: the OCR result and the matched medicines are saved with it.
 * FR-16: the medicines the customer chose are added to the cart.
 * FR-17, FR-63: the customer can see every prescription they have uploaded.
 *
 * What is stored in each JSON column:
 *   ocr_result           everything the OCR produced: text, lines, confidence,
 *                        and the suggested matches (so the review page can be
 *                        opened again later without running OCR a second time)
 *   medicines_found      the names the customer actually added to the cart
 *   medicines_not_found  lines that looked like medicines but were not matched
 *
 * The image itself is NOT in the database. Only its file name is, and the file
 * lives in private storage outside htdocs, so it cannot be opened by a link.
 */

class Prescription
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
    // Saving
    // ---------------------------------------------------------------------

    /**
     * Record a newly uploaded prescription, before OCR has run (FR-09).
     *
     * @param int    $customerId The customer who uploaded it.
     * @param string $fileName   The random file name it was saved under.
     * @return int The new prescription_id.
     */
    public function create(int $customerId, string $fileName): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO prescriptions (customer_id, image_path, status) VALUES (?, ?, ?)'
        );
        $statement->execute([$customerId, $fileName, 'processing']);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Save what OCR and the matcher produced (FR-11 to FR-14).
     *
     * @param int   $prescriptionId The prescription being processed.
     * @param array $ocr            The result of runPrescriptionOcr().
     * @param array $matches        The result of MedicineMatcher::match().
     * @return void
     */
    public function saveResult(int $prescriptionId, array $ocr, array $matches): void
    {
        $status = $ocr['success'] ? 'completed' : 'failed';

        $ocrResult = [
            'text' => $ocr['text'],
            'lines' => $ocr['lines'],
            'confidence' => $ocr['confidence'],
            'word_count' => $ocr['word_count'],
            'error' => $ocr['error'],
            'suggestions' => $matches['found'] ?? [],
        ];

        $statement = $this->pdo->prepare(
            'UPDATE prescriptions
             SET ocr_result = ?, medicines_not_found = ?, status = ?
             WHERE prescription_id = ?'
        );

        $statement->execute([
            json_encode($ocrResult),
            json_encode($matches['not_found'] ?? []),
            $status,
            $prescriptionId,
        ]);
    }

    /**
     * Remember which medicines the customer added to the cart (FR-16).
     *
     * @param int   $prescriptionId The prescription.
     * @param array $medicineNames  Names of the medicines that were added.
     * @return void
     */
    public function saveAddedMedicines(int $prescriptionId, array $medicineNames): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE prescriptions SET medicines_found = ? WHERE prescription_id = ?'
        );
        $statement->execute([json_encode(array_values($medicineNames)), $prescriptionId]);
    }

    // ---------------------------------------------------------------------
    // Reading
    // ---------------------------------------------------------------------

    /**
     * One prescription, but only if it belongs to this customer.
     *
     * The customer_id in the query stops anyone opening another customer's
     * prescription by changing the number in the address.
     *
     * @param int $prescriptionId The prescription to load.
     * @param int $customerId     The customer who must own it.
     * @return array|null The prescription with its JSON columns decoded, or null.
     */
    public function findForCustomer(int $prescriptionId, int $customerId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM prescriptions WHERE prescription_id = ? AND customer_id = ?'
        );
        $statement->execute([$prescriptionId, $customerId]);

        $prescription = $statement->fetch();

        if ($prescription === false) {
            return null;
        }

        return $this->decode($prescription);
    }

    /**
     * A customer's prescriptions, newest first (FR-17, FR-63).
     *
     * @param int      $customerId The customer.
     * @param int|null $limit      How many to return, or null for all of them.
     * @return array Prescriptions with their JSON columns decoded.
     */
    public function getForCustomer(int $customerId, ?int $limit = null): array
    {
        $sql = 'SELECT * FROM prescriptions WHERE customer_id = ? ORDER BY uploaded_at DESC, prescription_id DESC';

        if ($limit !== null) {
            $sql .= ' LIMIT ' . max(1, (int) $limit);
        }

        $statement = $this->pdo->prepare($sql);
        $statement->execute([$customerId]);

        return array_map([$this, 'decode'], $statement->fetchAll());
    }

    /**
     * The full path of a prescription image in private storage.
     *
     * basename() strips any folder part, so even a damaged database value can
     * never point outside the prescriptions folder.
     *
     * @param string $fileName The file name saved in image_path.
     * @return string Full path on disk.
     */
    public function imageFilePath(string $fileName): string
    {
        return PRESCRIPTION_PATH . '/' . basename($fileName);
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    /**
     * Turn the JSON columns into PHP arrays, with safe empty defaults.
     *
     * @param array $prescription A row from the prescriptions table.
     * @return array The same row with ocr, found and not_found arrays added.
     */
    private function decode(array $prescription): array
    {
        $ocr = json_decode((string) ($prescription['ocr_result'] ?? ''), true);
        $found = json_decode((string) ($prescription['medicines_found'] ?? ''), true);
        $notFound = json_decode((string) ($prescription['medicines_not_found'] ?? ''), true);

        $prescription['ocr'] = is_array($ocr) ? $ocr : [];
        $prescription['added'] = is_array($found) ? $found : [];
        $prescription['not_found'] = is_array($notFound) ? $notFound : [];
        $prescription['suggestions'] = $prescription['ocr']['suggestions'] ?? [];

        return $prescription;
    }
}