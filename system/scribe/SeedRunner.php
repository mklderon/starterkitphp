<?php
namespace System\Scribe;

use PDO;

final class SeedRunner
{
    private PDO $pdo;
    private string $path;

    public function __construct(PDO $pdo, string $path)
    {
        $this->pdo = $pdo;
        $this->path = $path;
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS _seed_history (
                file VARCHAR(190) PRIMARY KEY,
                executed_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }

    public function run(): int
    {
        // Obtener seeds ya ejecutados
        $done = [];
        try {
            $done = array_column($this->pdo->query('SELECT file FROM _seed_history')->fetchAll(), 'file');
        } catch (\Exception $e) {
            // Si la tabla acaba de ser creada, no debería fallar, pero por seguridad
        }

        $files = glob($this->path . '/*.seed.php');
        $count = 0;

        foreach ($files as $file) {
            $name = basename($file, '.seed.php');
            if (in_array($name, $done, true))
                continue;

            echo "🌱 Seed: $name\n";

            $seeder = require $file;
            if (is_callable($seeder)) {
                $seeder($this->pdo);
            } elseif (is_array($seeder)) {
                if (!isset($seeder['table']) || !isset($seeder['data'])) {
                    throw new \RuntimeException("Seed inválido en $file. Debe tener 'table' y 'data'.");
                }
                Seeder::table($this->pdo, $seeder['table'])->insert($seeder['data']);
            } else {
                throw new \RuntimeException("Seed inválido en $file. Debe retornar array o callable.");
            }

            // Registrar ejecución
            $this->pdo->prepare('INSERT INTO _seed_history (file) VALUES (?)')->execute([$name]);
            $count++;
        }
        return $count;
    }
}
