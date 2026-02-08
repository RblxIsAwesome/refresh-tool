<?php
/**
 * Database Connection Handler
 * 
 * Provides PDO database connection with error handling,
 * connection pooling, and auto-reconnect features.
 * 
 * @package RobloxRefresher
 * @version 1.0.0
 */

class Database
{
    private static ?PDO $connection = null;
    private static ?self $instance = null;
    private static int $maxRetries = 3;
    private static int $retryDelay = 1; // seconds
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct()
    {
    }
    
    /**
     * Get singleton instance
     * 
     * @return self
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get database connection with auto-reconnect
     * 
     * @return PDO Database connection
     * @throws PDOException If connection fails after all retries
     */
    public static function getConnection(): PDO
    {
        // Try to ping existing connection
        if (self::$connection !== null) {
            try {
                self::$connection->query('SELECT 1');
                return self::$connection;
            } catch (PDOException $e) {
                // Connection lost, will reconnect below
                self::$connection = null;
            }
        }
        
        // Establish new connection with retries
        $attempt = 0;
        $lastException = null;
        
        while ($attempt < self::$maxRetries) {
            try {
                self::$connection = self::createConnection();
                return self::$connection;
            } catch (PDOException $e) {
                $lastException = $e;
                $attempt++;
                
                if ($attempt < self::$maxRetries) {
                    sleep(self::$retryDelay);
                }
                
                self::logError("Database connection attempt $attempt failed: " . $e->getMessage());
            }
        }
        
        throw new PDOException(
            "Failed to connect to database after " . self::$maxRetries . " attempts: " . 
            ($lastException ? $lastException->getMessage() : 'Unknown error')
        );
    }
    
    /**
     * Create new database connection
     * 
     * @return PDO
     * @throws PDOException
     */
    private static function createConnection(): PDO
    {
        $config = self::loadConfig();
        
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $config['host'],
            $config['port'],
            $config['database']
        );
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => self::getEnv('DB_PERSISTENT', 'true') === 'true', // Connection pooling (configurable)
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];
        
        return new PDO($dsn, $config['username'], $config['password'], $options);
    }
    
    /**
     * Load database configuration from environment
     * 
     * @return array Configuration array
     */
    private static function loadConfig(): array
    {
        // Try to load from .env file if it exists
        $envFile = __DIR__ . '/.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) {
                    continue;
                }
                
                $parts = explode('=', $line, 2);
                if (count($parts) === 2) {
                    $key = trim($parts[0]);
                    $value = trim($parts[1]);
                    $_ENV[$key] = $value;
                    putenv("$key=$value");
                }
            }
        }
        
        // Also try legacy env.txt
        $envTxtFile = __DIR__ . '/env.txt';
        if (file_exists($envTxtFile)) {
            $vars = parse_ini_file($envTxtFile, false, INI_SCANNER_RAW);
            if (is_array($vars)) {
                foreach ($vars as $key => $value) {
                    $_ENV[$key] = $value;
                    putenv("$key=$value");
                }
            }
        }
        
        return [
            'host' => self::getEnv('DB_HOST', 'localhost'),
            'port' => self::getEnv('DB_PORT', '3306'),
            'database' => self::getEnv('DB_NAME', 'refresh_tool'),
            'username' => self::getEnv('DB_USER', 'root'),
            'password' => self::getEnv('DB_PASS', ''),
        ];
    }
    
    /**
     * Get environment variable with fallback
     * 
     * @param string $key Environment variable key
     * @param mixed $default Default value if not found
     * @return mixed
     */
    private static function getEnv(string $key, $default = null)
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        return $value !== false ? $value : $default;
    }
    
    /**
     * Execute a prepared statement with parameters
     * 
     * @param string $sql SQL query
     * @param array $params Parameters for prepared statement
     * @return PDOStatement
     * @throws PDOException
     */
    public static function execute(string $sql, array $params = []): PDOStatement
    {
        $conn = self::getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    /**
     * Begin transaction
     * 
     * @return bool
     */
    public static function beginTransaction(): bool
    {
        return self::getConnection()->beginTransaction();
    }
    
    /**
     * Commit transaction
     * 
     * @return bool
     */
    public static function commit(): bool
    {
        return self::getConnection()->commit();
    }
    
    /**
     * Rollback transaction
     * 
     * @return bool
     */
    public static function rollback(): bool
    {
        return self::getConnection()->rollBack();
    }
    
    /**
     * Check if database is available
     * 
     * @return bool
     */
    public static function isAvailable(): bool
    {
        try {
            self::getConnection();
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Log error to file
     * 
     * @param string $message Error message
     */
    private static function logError(string $message): void
    {
        $logFile = __DIR__ . '/../logs/database_errors.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message" . PHP_EOL;
        
        @file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
        error_log($message);
    }
    
    /**
     * Close connection (mainly for testing)
     */
    public static function closeConnection(): void
    {
        self::$connection = null;
    }
}
