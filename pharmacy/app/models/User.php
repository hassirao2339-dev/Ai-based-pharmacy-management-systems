<?php

/**
 * User.php - the User model
 *
 * Every SQL query for the "users" table lives in this class.
 * Controllers (pages) never write SQL for users; they call these methods.
 * This is the "M" in MVC (SRS NFR Maintainability: queries isolated in Model classes).
 *
 * How a page uses it:
 *     $userModel = new User($pdo);
 *     $user = $userModel->findByEmail('ali@gmail.com');
 */

class User
{
    /**
     * The database connection. "private" means only code inside this class can use it.
     */
    private PDO $pdo;

    /**
     * Runs automatically when a page writes: new User($pdo)
     *
     * @param PDO $pdo The database connection created in bootstrap.php.
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ---------------------------------------------------------------------
    // Finding users
    // ---------------------------------------------------------------------

    /**
     * Find one user by their ID.
     *
     * @param int $userId The user_id to look for.
     * @return array|null The user's row, or null when no user has this ID.
     */
    public function findById(int $userId): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM users WHERE user_id = ?');
        $statement->execute([$userId]);

        $user = $statement->fetch();

        if ($user === false) {
            return null;
        }

        return $user;
    }

    /**
     * Find one user by their email address (used by login and password reset).
     *
     * @param string $email The email address, already trimmed and in lowercase.
     * @return array|null The user's row, or null when the email is not registered.
     */
    public function findByEmail(string $email): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM users WHERE email = ?');
        $statement->execute([$email]);

        $user = $statement->fetch();

        if ($user === false) {
            return null;
        }

        return $user;
    }

    /**
     * Check whether an email address is already registered (FR-02).
     *
     * @param string $email The email address to check.
     * @return bool True when an account already uses this email.
     */
    public function emailExists(string $email): bool
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
        $statement->execute([$email]);

        return (int) $statement->fetchColumn() > 0;
    }

    /**
     * Count how many administrator accounts exist.
     *
     * @return int Number of admins.
     */
    public function countAdmins(): int
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM users WHERE role = ?');
        $statement->execute(['admin']);

        return (int) $statement->fetchColumn();
    }

    // ---------------------------------------------------------------------
    // Creating accounts and changing passwords
    // ---------------------------------------------------------------------

    /**
     * Create a new account and return its new user_id.
     *
     * The registration page always uses the default role "customer" (FR-01).
     * Only the one-time admin setup tool passes "admin".
     *
     * @param string      $name     Full name.
     * @param string      $email    Email address in lowercase.
     * @param string      $password The plain password typed by the user (it is hashed here).
     * @param string      $phone    Phone number.
     * @param string|null $address  Delivery address, or null if not given.
     * @param string      $role     "customer" or "admin".
     * @return int The new user's ID.
     */
    public function create(string $name, string $email, string $password, string $phone, ?string $address, string $role = 'customer'): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO users (name, email, password_hash, phone, address, role)
             VALUES (?, ?, ?, ?, ?, ?)'
        );

        $statement->execute([
            $name,
            $email,
            $this->hashPassword($password),
            $phone,
            $address,
            $role,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Change a user's password.
     * Also deletes any reset link and unlocks the account.
     *
     * @param int    $userId      The user whose password changes.
     * @param string $newPassword The new plain password (it is hashed here).
     * @return void
     */
    public function updatePassword(int $userId, string $newPassword): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE users
             SET password_hash = ?,
                 reset_token_hash = NULL,
                 reset_expires_at = NULL,
                 failed_login_attempts = 0,
                 locked_until = NULL
             WHERE user_id = ?'
        );

        $statement->execute([$this->hashPassword($newPassword), $userId]);
    }

    /**
     * Turn a plain password into a bcrypt hash.
     * The real password is never saved (SRS NFR Security: bcrypt, work factor 12).
     *
     * @param string $password The plain password.
     * @return string A hash that starts with $2y$12$
     */
    private function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
    }

    // ---------------------------------------------------------------------
    // Login protection: 5 wrong passwords = locked for 15 minutes (FR-07)
    // ---------------------------------------------------------------------

    /**
     * Check whether the account is locked right now.
     *
     * @param array $user The user's row.
     * @return bool True while the lock time has not finished.
     */
    public function isLocked(array $user): bool
    {
        if ($user['locked_until'] === null) {
            return false;
        }

        return strtotime($user['locked_until']) > time();
    }

    /**
     * How many minutes are left until a locked account opens again.
     *
     * @param array $user The user's row.
     * @return int Minutes left (at least 1).
     */
    public function minutesUntilUnlock(array $user): int
    {
        $secondsLeft = strtotime($user['locked_until']) - time();

        return max(1, (int) ceil($secondsLeft / 60));
    }

    /**
     * Count one wrong password. When the count reaches LOGIN_MAX_ATTEMPTS,
     * lock the account for LOCKOUT_MINUTES and start counting from zero again.
     *
     * @param array $user The user's row.
     * @return bool True if this wrong password has just locked the account.
     */
    public function recordFailedLogin(array $user): bool
    {
        $failedAttempts = (int) $user['failed_login_attempts'] + 1;

        if ($failedAttempts >= LOGIN_MAX_ATTEMPTS) {
            $lockedUntil = date('Y-m-d H:i:s', time() + (LOCKOUT_MINUTES * 60));

            $statement = $this->pdo->prepare(
                'UPDATE users SET failed_login_attempts = 0, locked_until = ? WHERE user_id = ?'
            );
            $statement->execute([$lockedUntil, $user['user_id']]);

            return true;
        }

        $statement = $this->pdo->prepare(
            'UPDATE users SET failed_login_attempts = ? WHERE user_id = ?'
        );
        $statement->execute([$failedAttempts, $user['user_id']]);

        return false;
    }

    /**
     * Clear the wrong-password counter after a successful login.
     *
     * @param int $userId The user who logged in.
     * @return void
     */
    public function resetFailedLogins(int $userId): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE users SET failed_login_attempts = 0, locked_until = NULL WHERE user_id = ?'
        );
        $statement->execute([$userId]);
    }

    // ---------------------------------------------------------------------
    // Password reset links (FR-08)
    // ---------------------------------------------------------------------

    /**
     * Save a password reset token for a user.
     *
     * Only a SHA-256 hash of the token is stored. Even someone who can read
     * the database cannot use it, because the real token is only in the email link.
     *
     * @param int    $userId    The user who asked for a reset.
     * @param string $tokenHash hash('sha256', $token)
     * @param string $expiresAt Date and time the link stops working, 'Y-m-d H:i:s'.
     * @return void
     */
    public function saveResetToken(int $userId, string $tokenHash, string $expiresAt): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE users SET reset_token_hash = ?, reset_expires_at = ? WHERE user_id = ?'
        );
        $statement->execute([$tokenHash, $expiresAt, $userId]);
    }

    /**
     * Find the active user who owns a reset token that has not expired yet.
     *
     * @param string $tokenHash hash('sha256', $token from the link)
     * @return array|null The user's row, or null if the link is wrong, used or expired.
     */
    public function findByValidResetToken(string $tokenHash): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT * FROM users
             WHERE reset_token_hash = ?
               AND reset_expires_at > ?
               AND is_active = 1'
        );
        $statement->execute([$tokenHash, date('Y-m-d H:i:s')]);

        $user = $statement->fetch();

        if ($user === false) {
            return null;
        }

        return $user;
    }
    // ---------------------------------------------------------------------
    // Profile (FR-61, FR-62)
    // ---------------------------------------------------------------------

    /**
     * Update the details a user may change on their profile page (FR-61).
     * Email and role are NOT changed here: the email identifies the account,
     * and only an administrator can change a role.
     *
     * @param int         $userId  The user whose profile is being saved.
     * @param string      $name    Full name.
     * @param string      $phone   Phone number.
     * @param string|null $address Delivery address, or null when left empty.
     * @return void
     */
    public function updateProfile(int $userId, string $name, string $phone, ?string $address): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE users SET name = ?, phone = ?, address = ? WHERE user_id = ?'
        );

        $statement->execute([$name, $phone, $address, $userId]);
    }

    /**
     * Check a password against the saved hash (FR-62).
     * Used when a logged-in user changes their password.
     *
     * @param array  $user     The user's row.
     * @param string $password The password typed by the user.
     * @return bool True when the password is correct.
     */
    public function passwordMatches(array $user, string $password): bool
    {
        return password_verify($password, $user['password_hash']);
    }

    // ---------------------------------------------------------------------
    // Administrator: customer accounts (FR-58)
    // ---------------------------------------------------------------------

    /**
     * Every customer account, with how much each has ordered.
     *
     * Only orders the pharmacy accepted are counted, so a cancelled order does
     * not make a customer look like a bigger buyer than they are.
     *
     * @return array Customer rows with order_count and total_spent.
     */
    public function getAllCustomers(): array
    {
        $statement = $this->pdo->prepare(
            "SELECT
                 u.user_id,
                 u.name,
                 u.email,
                 u.phone,
                 u.address,
                 u.is_active,
                 u.created_at,
                 (SELECT COUNT(*) FROM orders o
                  WHERE o.customer_id = u.user_id
                    AND o.status IN ('confirmed', 'dispatched', 'delivered')) AS order_count,
                 (SELECT COALESCE(SUM(o.total_amount), 0) FROM orders o
                  WHERE o.customer_id = u.user_id
                    AND o.status IN ('confirmed', 'dispatched', 'delivered')) AS total_spent
             FROM users u
             WHERE u.role = ?
             ORDER BY u.name"
        );
        $statement->execute(['customer']);

        return $statement->fetchAll();
    }

    /**
     * Turn a customer account on or off (FR-58).
     *
     * A deactivated account cannot log in, and enforceActiveAccount() in
     * bootstrap.php logs them out on their very next page load. The row is
     * never deleted, because their orders point at it.
     *
     * @param int  $userId   The customer to change.
     * @param bool $isActive true lets them log in, false blocks them.
     * @return void
     */
    public function setActive(int $userId, bool $isActive): void
    {
        $statement = $this->pdo->prepare('UPDATE users SET is_active = ? WHERE user_id = ?');
        $statement->execute([$isActive ? 1 : 0, $userId]);
    }
}