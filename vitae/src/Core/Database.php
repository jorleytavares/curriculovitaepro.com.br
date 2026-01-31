<?php
namespace Core;

use PDO;
use PDOException;

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        $this->loadEnv();
        
        $dbConfig = [
            'host'     => $this->getEnvValue('DB_HOST', '127.0.0.1'),
            'port'     => $this->getEnvValue('DB_PORT', '3306'),
            'dbname'   => $this->getEnvValue('DB_NAME', $this->getEnvValue('DB_DATABASE', 'resume_saas')),
            'username' => $this->getEnvValue('DB_USER', $this->getEnvValue('DB_USERNAME', 'root')),
            'password' => $this->getEnvValue('DB_PASSWORD', ''),
            'charset'  => 'utf8mb4'
        ];

        try {
            $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => true,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$dbConfig['charset']} COLLATE {$dbConfig['charset']}_unicode_ci"
            ];

            $this->pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $options);
            
        } catch (PDOException $e) {
            error_log("[DB Error] " . $e->getMessage());
            // Em produção, você pode querer lançar uma exceção customizada ou lidar de outra forma
            throw $e; 
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }

    private function loadEnv() {
        // Lógica de carregamento de .env manual para garantir compatibilidade
        // Caminho relativo considerando que este arquivo está em src/Core/
        $envPath = __DIR__ . '/../../.env';
        
        if (file_exists($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if (strpos($line, '#') === 0) continue;
                
                if (strpos($line, '=') !== false) {
                    [$name, $value] = explode('=', $line, 2);
                    $name = trim($name);
                    $value = trim($value);
                    $value = trim($value, " \t\n\r\0\x0B\"'");
                    
                    putenv("{$name}={$value}");
                    $_ENV[$name] = $value;
                    $_SERVER[$name] = $value;
                }
            }
        }
    }

    private function getEnvValue($key, $default = '') {
        $val = getenv($key);
        if ($val !== false) return $val;
        if (isset($_ENV[$key])) return $_ENV[$key];
        return $default;
    }
    
    // Previne clonagem
    private function __clone() {}
    
    // Previne unserialize
    public function __wakeup() {}
}
