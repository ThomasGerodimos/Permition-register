<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    private function __construct(array $config)
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['dbname'],
            $config['charset']
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ];

        try {
            $this->pdo = new PDO($dsn, $config['user'], $config['pass'], $options);
        } catch (PDOException $e) {
            error_log('DB Connection failed: ' . $e->getMessage());
            throw new \RuntimeException('Database connection failed. Please check configuration.');
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            $config = Config::load();
            self::$instance = new self($config['db']);
        }
        return self::$instance;
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    /** Execute a prepared statement and return the statement */
    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /** Fetch all rows */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    /** Fetch single row */
    public function fetchOne(string $sql, array $params = []): array|false
    {
        return $this->query($sql, $params)->fetch();
    }

    /** Fetch single column value */
    public function fetchColumn(string $sql, array $params = []): mixed
    {
        return $this->query($sql, $params)->fetchColumn();
    }

    /** Insert and return last insert id */
    public function insert(string $sql, array $params = []): int
    {
        $this->query($sql, $params);
        return (int) $this->pdo->lastInsertId();
    }

    /** Execute UPDATE/DELETE and return affected rows */
    public function execute(string $sql, array $params = []): int
    {
        return $this->query($sql, $params)->rowCount();
    }

    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollBack(): void
    {
        $this->pdo->rollBack();
    }
}
