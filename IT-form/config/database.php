<?php
/**
 * Conexión PDO (MySQL o SQLite) vía variables de entorno.
 */
require_once __DIR__ . '/env.php';

class Database
{
    private static $instance = null;
    /** @var PDO */
    private $connection;
    /** @var string */
    private $driver;

    private function __construct()
    {
        $this->driver = strtolower(itform_env('DB_DRIVER', 'mysql'));
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            if ($this->driver === 'sqlite') {
                $path = itform_env('DB_PATH', dirname(__DIR__) . '/storage/db/itform.sqlite');
                $dir = dirname($path);
                if (!is_dir($dir)) {
                    mkdir($dir, 0750, true);
                }
                $this->connection = new PDO('sqlite:' . $path, null, null, $options);
                $this->connection->exec('PRAGMA foreign_keys = ON');
            } else {
                $host = itform_env('DB_HOST', '127.0.0.1');
                $port = itform_env('DB_PORT', '3306');
                $name = itform_env('DB_NAME', 'itformdb');
                $user = itform_env('DB_USER', 'itform_usr');
                $pass = itform_env('DB_PASS', '');
                $charset = itform_env('DB_CHARSET', 'utf8mb4');
                $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
                $this->connection = new PDO($dsn, $user, $pass, $options);
            }
        } catch (PDOException $e) {
            error_log('IT-form DB connection error: ' . $e->getMessage());
            http_response_code(500);
            // Nunca filtrar detalles al cliente
            die('Error de conexión a la base de datos');
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /** Reinicia la conexión (útil en scripts CLI de init). */
    public static function resetInstance(): void
    {
        self::$instance = null;
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }

    public function getDriver(): string
    {
        return $this->driver;
    }

    private function __clone() {}

    public function __wakeup()
    {
        throw new Exception('Cannot unserialize singleton');
    }
}

function getDB(): PDO
{
    return Database::getInstance()->getConnection();
}

function itform_db_driver(): string
{
    return Database::getInstance()->getDriver();
}

/**
 * Genera número AAAA-MM-NNNN (app-side MySQL y SQLite; no depende de triggers).
 */
function itform_next_sequence(PDO $db): string
{
    $year = date('Y');
    $month = date('m');
    $prefix = $year . '-' . $month . '-';

    $stmt = $db->prepare('SELECT numero_secuencia FROM servicios WHERE numero_secuencia LIKE :p ORDER BY id DESC LIMIT 1');
    $stmt->execute([':p' => $prefix . '%']);
    $last = $stmt->fetchColumn();
    $n = 1;
    if ($last) {
        $parts = explode('-', (string) $last);
        $n = ((int) end($parts)) + 1;
    }
    return $prefix . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
}
