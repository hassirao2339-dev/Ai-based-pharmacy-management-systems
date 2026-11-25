<?php

/**
 * database.php
 *
 * Creates the connection between PHP and MySQL using PDO (PHP Data Objects).
 */

/**
 * Connect to the MySQL database and return the connection.
 *
 * @return PDO The database connection object.
 * @throws PDOException When the connection fails (MySQL stopped, wrong password, missing database).
 */
function connectDatabase(): PDO
{
    // DSN means "Data Source Name": which driver, server, port and database to use.
    $dsn = 'mysql:host=' . DB_HOST
        . ';port=' . DB_PORT
        . ';dbname=' . DB_NAME
        . ';charset=utf8mb4';

    $options = [
        // Throw an exception when a query fails, instead of failing silently.
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

        // Return each row as an associative array: $row['name'] instead of $row[0].
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

        // Use real prepared statements: the SQL and the user's data are sent separately.
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

    // Make MySQL use the same time zone as PHP (Pakistan is +05:00), so NOW()
    // in SQL and date() in PHP always agree. date('P') comes from PHP itself,
    // not from a user, so it is safe to place directly in this query.
    $pdo->exec("SET time_zone = '" . date('P') . "'");

    return $pdo;
}