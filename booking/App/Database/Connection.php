<?php declare(strict_types=1);

namespace App\Database;

use PDO;
use PDOException;

final class Connection {
    private static $instance = null;

    public static function pdo() {
        if (self::$instance instanceof PDO) {
            return self::$instance;
        }

        $host    = getenv('DB_HOST');
        $port    = getenv('DB_PORT');
        $name    = getenv('DB_NAME');
        $user    = getenv('DB_USER');
        $pass    = getenv('DB_PASS');
        $charset = getenv('DB_CHARSET');

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $host, $port, $name, $charset);

        try {
            self::$instance = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $exception) {
            throw new PDOException('Database connection failed: ' . $exception->getMessage(), (int)$exception->getCode(), $exception);
        }

        return self::$instance;
    }
}
