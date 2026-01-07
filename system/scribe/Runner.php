<?php
namespace System\Scribe;

use PDO;
use PDOException;

final class Runner
{
    private PDO $pdo;
    private string $path;

    public function __construct(PDO $pdo, string $path)
    {
        $this->pdo  = $pdo;
        $this->path = $path;
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS _schema_history (
                file VARCHAR(190) PRIMARY KEY,
                batch INT NOT NULL,
                applied_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }

    public function up(): int
    {
        $batch = (int) $this->pdo->query('SELECT COALESCE(MAX(batch),0)+1 FROM _schema_history')->fetchColumn();
        $done  = array_column($this->pdo->query('SELECT file FROM _schema_history')->fetchAll(), 'file');
        $files = glob($this->path.'/*.table.php');
        $run   = 0;

        foreach ($files as $f) {
            $name = basename($f, '.table.php');
            if (in_array($name, $done, true)) continue;

            $migration = require $f;

            if ($migration instanceof Table) {
                $sql = $migration->toSql();
                $this->pdo->exec($sql);
            } elseif (is_callable($migration)) {
                $migration($this->pdo); // Ejecuta lógica personalizada
            } else {
                throw new \RuntimeException("Migración inválida en $f. Debe retornar Table o callable.");
            }

            $this->pdo->prepare('INSERT INTO _schema_history (file,batch) VALUES (?,?)')
                       ->execute([$name, $batch]);
            $run++;
        }
        return $run;
    }

    public function down(): int
    {
        $list = $this->pdo->query('SELECT file FROM _schema_history WHERE batch = (SELECT MAX(batch) FROM _schema_history) ORDER BY file DESC')->fetchAll();

        foreach ($list as $row) {
            $filename = $row['file'];
            $f = $this->path . "/$filename.table.php";

            if (!file_exists($f)) {
                echo "⚠️ Advertencia: Archivo de migración no encontrado: $filename\n";
                continue;
            }

            $migration = require $f;

            if ($migration instanceof Table && !$migration->isAlter()) {
                // Solo drop si es CREATE (no alter)
                $table = preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', $filename);
                $this->pdo->exec("DROP TABLE IF EXISTS `$table`");
            }
            // Si es alter, no revertimos automáticamente (¡demasiado riesgoso!)
            // En producción, deberías crear migraciones de reversión manual.

            $this->pdo->prepare('DELETE FROM _schema_history WHERE file = ?')->execute([$filename]);
        }
        return count($list);
    }
}