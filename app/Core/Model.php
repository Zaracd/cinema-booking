<?php
namespace App\Core;

use PDO;
use PDOStatement;

/** Base model with small helpers around prepared statements. */
abstract class Model
{
    protected static function db(): PDO
    {
        return Database::pdo();
    }

    protected static function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    protected static function all(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    protected static function one(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    protected static function value(string $sql, array $params = [])
    {
        return self::run($sql, $params)->fetchColumn();
    }

    protected static function insert(string $sql, array $params = []): int
    {
        self::run($sql, $params);
        return (int) self::db()->lastInsertId();
    }
}
