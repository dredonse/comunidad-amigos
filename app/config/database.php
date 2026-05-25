<?php

declare(strict_types=1);

class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?? 'db';
            $name = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?? 'comunidad';
            $user = $_ENV['DB_USER'] ?? getenv('DB_USER') ?? 'usuario';
            $pass = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?? 'password';

            $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";

            try {
                self::$instance = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                error_log('DB connection failed: ' . $e->getMessage());
                die(json_encode(['error' => 'Error de conexión a la base de datos']));
            }
        }

        return self::$instance;
    }

    // Evitar clonación
    private function __clone() {}
}
