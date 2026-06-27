<?php
// Database connection configuration

class Database {
    private static ?PDO $pdo = null;

    public static function connect(): PDO {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        // Default credentials (XAMPP standard configuration)
        $host = '127.0.0.1';
        $port = '3306';
        $dbname = 'edutrack';
        $user = 'root';
        $pass = '';

        // Load credentials from local .env if it exists
        $envPath = dirname(__DIR__) . '/.env';
        if (file_exists($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || str_starts_with($line, '#')) {
                    continue;
                }
                
                $parts = explode('=', $line, 2);
                if (count($parts) === 2) {
                    $key = trim($parts[0]);
                    $value = trim($parts[1]);
                    // Strip quotes if any
                    $value = trim($value, "\"'");
                    
                    $_ENV[$key] = $value;
                    putenv("{$key}={$value}");
                    
                    switch ($key) {
                        case 'DB_HOST':
                            $host = $value;
                            break;
                        case 'DB_PORT':
                            $port = $value;
                            break;
                        case 'DB_NAME':
                            $dbname = $value;
                            break;
                        case 'DB_USER':
                            $user = $value;
                            break;
                        case 'DB_PASS':
                            $pass = $value;
                            break;
                    }
                }
            }
        }

        try {
            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
            self::$pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            return self::$pdo;
        } catch (PDOException $e) {
            // Display clean explanation for developers
            throw new RuntimeException("Database Connection Error: " . $e->getMessage(), (int)$e->getCode());
        }
    }
}
