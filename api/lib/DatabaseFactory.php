<?php

class DatabaseFactory
{
    private static $db = null;

    public static function loadEnv(): void
    {
        $envFile = dirname(__DIR__, 2) . '/.env';
        if (!is_readable($envFile)) {
            return;
        }
        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            if (!str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\"'");
            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }

    public static function connectFromEnv(): void
    {
        self::loadEnv();
        $driver = $_ENV['DB_DRIVER'] ?? 'sqlite';

        if ($driver === 'mysql') {
            EpiDatabase::employ(
                'mysql',
                $_ENV['DB_NAME'] ?? 'epiella',
                $_ENV['DB_HOST'] ?? 'localhost',
                $_ENV['DB_USER'] ?? 'root',
                $_ENV['DB_PASS'] ?? '',
                (int) ($_ENV['DB_PORT'] ?? 3306)
            );
            self::$db = getDatabase();
            return;
        }

        $path = $_ENV['SQLITE_PATH'] ?? dirname(__DIR__, 2) . '/data/epiella.sqlite';
        if (!preg_match('#^(?:/|[A-Za-z]:[\\\\/])#', $path)) {
            $path = dirname(__DIR__, 2) . '/' . str_replace('\\', '/', $path);
        }
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        self::$db = new SqliteDatabase($path);
    }

    public static function getDb()
    {
        if (self::$db === null) {
            self::connectFromEnv();
        }
        return self::$db;
    }

    public static function driver(): string
    {
        self::loadEnv();
        return $_ENV['DB_DRIVER'] ?? 'sqlite';
    }
}

class SqliteDatabase
{
    private PDO $dbh;

    public function __construct(string $path)
    {
        $this->dbh = new PDO('sqlite:' . $path, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $this->dbh->exec('PRAGMA foreign_keys = ON');
    }

    public function execute(string $sql, array $params = [])
    {
        $sth = $this->prepare($sql, $params);
        if (preg_match('/insert/i', $sql)) {
            return $this->dbh->lastInsertId();
        }
        return $sth->rowCount();
    }

    public function insertId()
    {
        $id = $this->dbh->lastInsertId();
        return $id > 0 ? $id : false;
    }

    public function all(string $sql, array $params = [])
    {
        $sth = $this->prepare($sql, $params);
        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }

    public function one(string $sql, array $params = [])
    {
        $sth = $this->prepare($sql, $params);
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getPdo(): PDO
    {
        return $this->dbh;
    }

    private function prepare(string $sql, array $params = [])
    {
        $sth = $this->dbh->prepare($sql, [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);
        $sth->execute($params);
        return $sth;
    }
}
