<?php

class Migrator
{
    private $db;
    private string $driver;
    private string $migrationsDir;

    public function __construct()
    {
        DatabaseFactory::loadEnv();
        DatabaseFactory::connectFromEnv();
        $this->db = DatabaseFactory::getDb();
        $this->driver = DatabaseFactory::driver();
        $this->migrationsDir = dirname(__DIR__) . '/migrations/' . $this->driver;
    }

    public function status(): array
    {
        $applied = $this->getAppliedVersions();
        $pending = [];
        $all = [];

        foreach ($this->listMigrationFiles() as $file) {
            $version = $this->versionFromFile($file);
            $all[] = $version;
            if (!in_array($version, $applied, true)) {
                $pending[] = $version;
            }
        }

        return [
            'driver' => $this->driver,
            'applied' => $applied,
            'pending' => $pending,
            'all' => $all,
        ];
    }

    public function apply(): int
    {
        $applied = $this->getAppliedVersions();
        $count = 0;

        foreach ($this->listMigrationFiles() as $file) {
            $version = $this->versionFromFile($file);
            if (in_array($version, $applied, true)) {
                continue;
            }

            $sql = file_get_contents($file);
            $this->runInTransaction($sql);
            $this->db->execute(
                'INSERT INTO schema_migrations (version) VALUES (:version)',
                [':version' => $version]
            );
            $count++;
            echo "Applied: {$version}\n";
        }

        return $count;
    }

    private function getAppliedVersions(): array
    {
        if (!$this->tableExists('schema_migrations')) {
            return [];
        }
        $rows = $this->db->all('SELECT version FROM schema_migrations ORDER BY version');
        return array_column($rows ?: [], 'version');
    }

    private function tableExists(string $table): bool
    {
        if ($this->driver === 'sqlite') {
            $row = $this->db->one(
                "SELECT name FROM sqlite_master WHERE type='table' AND name = :name",
                [':name' => $table]
            );
            return $row !== null;
        }

        $row = $this->db->one(
            'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :name',
            [':name' => $table]
        );
        return $row !== null;
    }

    private function listMigrationFiles(): array
    {
        if (!is_dir($this->migrationsDir)) {
            throw new RuntimeException("Migrations directory not found: {$this->migrationsDir}");
        }
        $files = glob($this->migrationsDir . '/*.sql');
        sort($files, SORT_NATURAL);
        return $files;
    }

    private function versionFromFile(string $file): string
    {
        return pathinfo($file, PATHINFO_FILENAME);
    }

    private function runInTransaction(string $sql): void
    {
        $pdo = $this->getPdo();
        $pdo->beginTransaction();
        try {
            foreach ($this->splitStatements($sql) as $statement) {
                $statement = trim($statement);
                if ($statement !== '') {
                    $pdo->exec($statement);
                }
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private function splitStatements(string $sql): array
    {
        return preg_split('/;\s*\n/', $sql) ?: [];
    }

    private function getPdo(): PDO
    {
        if ($this->db instanceof SqliteDatabase) {
            return $this->db->getPdo();
        }
        return $this->db->dbh;
    }
}
