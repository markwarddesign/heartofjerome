<?php
require_once __DIR__ . '/config.php';

/**
 * Returns a shared PDO connection (or null if the DB is unavailable).
 * The site degrades gracefully: pages still render, the counter falls back
 * to STARTING_COUNT, and submissions still email even if the insert fails.
 */
function db(): ?PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    try {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        return $pdo;
    } catch (Throwable $e) {
        if (DEBUG) {
            error_log('DB connection failed: ' . $e->getMessage());
        }
        return null;
    }
}

/**
 * Total acts of kindness = real-world starting tally + everything logged here.
 */
function total_acts(): int {
    $pdo = db();
    if (!$pdo) {
        return STARTING_COUNT;
    }
    try {
        $sum = (int) $pdo->query('SELECT COALESCE(SUM(num_acts), 0) FROM kindness_acts')->fetchColumn();
        return STARTING_COUNT + $sum;
    } catch (Throwable $e) {
        return STARTING_COUNT;
    }
}

/**
 * Insert one submission. Returns the new row id, or null on failure.
 */
function insert_act(array $data): ?int {
    $pdo = db();
    if (!$pdo) {
        return null;
    }
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO kindness_acts
                (num_acts, name, email, description, photo_path, logged_idaho, ip_address, created_at)
             VALUES
                (:num_acts, :name, :email, :description, :photo_path, :logged_idaho, :ip_address, NOW())'
        );
        $stmt->execute([
            ':num_acts'     => $data['num_acts'],
            ':name'         => $data['name'] !== '' ? $data['name'] : null,
            ':email'        => $data['email'],
            ':description'  => $data['description'] !== '' ? $data['description'] : null,
            ':photo_path'   => $data['photo_path'] !== '' ? $data['photo_path'] : null,
            ':logged_idaho' => $data['logged_idaho'] ? 1 : 0,
            ':ip_address'   => $data['ip_address'],
        ]);
        return (int) $pdo->lastInsertId();
    } catch (Throwable $e) {
        if (DEBUG) {
            error_log('Insert failed: ' . $e->getMessage());
        }
        return null;
    }
}
