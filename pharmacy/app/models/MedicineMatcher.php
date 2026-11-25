<?php

/**
 * MedicineMatcher.php - finding catalogue medicines in OCR text
 *
 * FR-13: match the names read from a prescription to medicines in the catalogue.
 * FR-14: give each match a confidence score, and list names that could not
 *        be matched so the customer can search for them.
 *
 * OCR is never perfect. "Paracetamol" might come back as "Paracetam0l" or
 * "Paracetamoi". So names are compared by how SIMILAR they are, not whether
 * they are exactly equal.
 *
 * How one line of the prescription is scored against one medicine:
 *   1. Each word of the line is compared with the medicine's main name
 *      (its first word, for example "paracetamol"). The best word wins.
 *      Similarity uses the Levenshtein distance: how many letters would have
 *      to change to turn one word into the other.
 *   2. If both mention a strength (500 mg) and it is the same, the score goes
 *      up. If the strengths differ, it goes down, because 250 mg and 500 mg
 *      are different medicines.
 *
 * A match is only suggested when its score reaches MIN_CONFIDENCE. The
 * customer still reviews every suggestion before anything reaches the cart.
 */

class MedicineMatcher
{
    /** The lowest score that counts as a match, out of 100. */
    public const MIN_CONFIDENCE = 70;

    private PDO $pdo;

    /** Active medicines, loaded once per prescription. */
    private array $medicines = [];

    /**
     * @param PDO $pdo The database connection created in bootstrap.php.
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Match every line of OCR text to the catalogue (FR-13, FR-14).
     *
     * @param array $lines The lines the OCR script returned.
     * @return array 'found' => matched medicines, 'not_found' => lines with no match
     */
    public function match(array $lines): array
    {
        $this->loadMedicines();

        $found = [];
        $notFound = [];

        foreach ($lines as $line) {
            $line = trim((string) $line);

            if ($line === '') {
                continue;
            }

            $best = $this->findBestMatch($line);

            if ($best !== null && $best['confidence'] >= self::MIN_CONFIDENCE) {
                $medicineId = $best['medicine_id'];

                // The same medicine written twice keeps its most confident line.
                if (!isset($found[$medicineId]) || $found[$medicineId]['confidence'] < $best['confidence']) {
                    $best['source_line'] = $line;
                    $found[$medicineId] = $best;
                }

                continue;
            }

            // Only lines that look like a medicine are reported as "not found".
            // A doctor's name or a clinic address is simply ignored.
            if ($this->looksLikeMedicine($line)) {
                $notFound[] = $line;
            }
        }

        // Most confident first.
        usort($found, function (array $a, array $b): int {
            return $b['confidence'] <=> $a['confidence'];
        });

        return [
            'found' => array_values($found),
            'not_found' => array_values(array_unique($notFound)),
        ];
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    /**
     * Load the active medicines once, with the pieces the matcher compares.
     *
     * @return void
     */
    private function loadMedicines(): void
    {
        if (!empty($this->medicines)) {
            return;
        }

        $statement = $this->pdo->query(
            'SELECT medicine_id, name, sku, price, stock_qty FROM medicines WHERE is_active = 1'
        );

        foreach ($statement->fetchAll() as $medicine) {
            $words = $this->words($medicine['name']);

            $this->medicines[] = [
                'medicine_id' => (int) $medicine['medicine_id'],
                'name' => $medicine['name'],
                'sku' => $medicine['sku'],
                'price' => (float) $medicine['price'],
                'stock_qty' => (int) $medicine['stock_qty'],
                'main_word' => $words[0] ?? '',
                'strength' => $this->strength($medicine['name']),
            ];
        }
    }

    /**
     * The catalogue medicine that best fits one line, or null.
     *
     * @param string $line One line of OCR text.
     * @return array|null The best medicine with its confidence.
     */
    private function findBestMatch(string $line): ?array
    {
        $lineWords = $this->words($line);
        $lineStrength = $this->strength($line);

        $best = null;
        $bestScore = 0.0;

        foreach ($this->medicines as $medicine) {
            if ($medicine['main_word'] === '') {
                continue;
            }

            // 1. The closest word in the line to the medicine's main name.
            $wordScore = 0.0;

            foreach ($lineWords as $word) {
                // Short words ("mg", "od", "1") are never medicine names.
                if (strlen($word) < 4) {
                    continue;
                }

                $wordScore = max($wordScore, $this->similarity($word, $medicine['main_word']));
            }

            if ($wordScore === 0.0) {
                continue;
            }

            // 2. Strength agreement.
            $score = $wordScore;

            if ($lineStrength !== null && $medicine['strength'] !== null) {
                if ($lineStrength === $medicine['strength']) {
                    $score += 10;
                } else {
                    $score -= 25;
                }
            }

            $score = max(0.0, min(100.0, $score));

            if ($score > $bestScore) {
                $bestScore = $score;

                $best = [
                    'medicine_id' => $medicine['medicine_id'],
                    'name' => $medicine['name'],
                    'sku' => $medicine['sku'],
                    'price' => $medicine['price'],
                    'stock_qty' => $medicine['stock_qty'],
                    'confidence' => round($score, 1),
                ];
            }
        }

        return $best;
    }

    /**
     * How alike two words are, from 0 (nothing alike) to 100 (identical).
     *
     * Levenshtein counts the single-letter changes needed to turn one word into
     * the other. "paracetam0l" -> "paracetamol" is one change, so it scores high.
     *
     * @param string $a First word.
     * @param string $b Second word.
     * @return float Similarity as a percentage.
     */
    private function similarity(string $a, string $b): float
    {
        $longest = max(strlen($a), strlen($b));

        if ($longest === 0) {
            return 0.0;
        }

        $changes = levenshtein($a, $b);

        return (1 - ($changes / $longest)) * 100;
    }

    /**
     * Split text into simple lowercase words: letters and digits only.
     *
     * @param string $text Any text.
     * @return array The words.
     */
    private function words(string $text): array
    {
        $text = strtolower($text);

        // OCR often reads the letter O as the digit 0 inside words.
        $text = preg_replace('/(?<=[a-z])0|0(?=[a-z])/', 'o', $text);

        $text = preg_replace('/[^a-z0-9]+/', ' ', $text);

        return array_values(array_filter(explode(' ', trim($text)), 'strlen'));
    }

    /**
     * The strength written in some text, such as 500 from "500 mg".
     *
     * @param string $text Any text.
     * @return int|null The number, or null when there is none.
     */
    private function strength(string $text): ?int
    {
        if (preg_match('/(\d+)\s*(mg|mcg|ml|g)\b/i', $text, $parts) === 1) {
            return (int) $parts[1];
        }

        return null;
    }

    /**
     * Whether a line is probably a medicine rather than a name or address.
     *
     * @param string $line One line of OCR text.
     * @return bool True when it mentions a strength or a dosage form.
     */
    private function looksLikeMedicine(string $line): bool
    {
        return preg_match('/\d+\s*(mg|mcg|ml|g)\b|\b(tab|tabs|tablet|tablets|cap|caps|capsule|capsules|syrup|drops|inhaler|injection|cream|ointment)\b/i', $line) === 1;
    }
}