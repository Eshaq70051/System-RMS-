<?php

namespace App\Models;

use PDO;
use PDOException;

class Database
{
    private static ?Database $instance = null;
    private ?PDO $pdo = null;

    private function __construct()
    {
        $config = require __DIR__ . '/../../config/database.php';
        $default = $config['default'] ?? 'sqlite';
        $dbConfig = $config['connections'][$default] ?? [];

        try {
            if ($default === 'sqlite') {
                $dbPath = $dbConfig['database'];
                $dir = dirname($dbPath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }

                $isNew = !file_exists($dbPath);

                $this->pdo = new PDO("sqlite:" . $dbPath);
                $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

                if (!empty($dbConfig['foreign_key_constraints'])) {
                    $this->pdo->exec('PRAGMA foreign_keys = ON;');
                }

                if ($isNew || filesize($dbPath) === 0) {
                    $this->initializeSchema();
                }
            } elseif ($default === 'mysql') {
                $dsn = sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                    $dbConfig['host'] ?? '127.0.0.1',
                    $dbConfig['port'] ?? '3306',
                    $dbConfig['database'] ?? 'restaurant_db',
                    $dbConfig['charset'] ?? 'utf8mb4'
                );

                $this->pdo = new PDO($dsn, $dbConfig['username'] ?? 'root', $dbConfig['password'] ?? '');
                $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            die("Database connection error: " . $e->getMessage());
        }
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    public function initializeSchema(): void
    {
        $schemaFile = __DIR__ . '/../../database/schema.sql';
        if (file_exists($schemaFile)) {
            $sql = file_get_contents($schemaFile);
            $this->pdo->exec($sql);
        }
    }

    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    public function fetch(string $sql, array $params = []): ?array
    {
        $res = $this->query($sql, $params)->fetch();
        return $res ?: null;
    }

    public function fetchValue(string $sql, array $params = [])
    {
        $res = $this->query($sql, $params)->fetchColumn();
        return $res !== false ? $res : null;
    }

    public function execute(string $sql, array $params = []): bool
    {
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $fields = implode(', ', $columns);
        $placeholders = ':' . implode(', :', $columns);

        $sql = "INSERT INTO {$table} ({$fields}) VALUES ({$placeholders})";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $setClauses = [];
        $params = [];
        foreach ($data as $col => $val) {
            $setClauses[] = "{$col} = :set_{$col}";
            $params["set_{$col}"] = $val;
        }

        $setString = implode(', ', $setClauses);
        $sql = "UPDATE {$table} SET {$setString} WHERE {$where}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_merge($params, $whereParams));

        return $stmt->rowCount();
    }

    public function delete(string $table, string $where, array $params = []): int
    {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }
}
