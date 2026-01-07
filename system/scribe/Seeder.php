<?php
namespace System\Scribe;

use PDO;

final class Seeder
{
    private PDO $pdo;
    private string $table;
    private array $data = [];

    private function __construct(PDO $pdo, string $table)
    {
        $this->pdo   = $pdo;
        $this->table = $table;
    }

    public static function table(PDO $pdo, string $table): self
    {
        return new self($pdo, $table);
    }

    public function insert(array $rows): self
    {
        if (empty($rows)) return $this;

        // Asegurar que todos los rows tengan las mismas claves
        $keys = array_keys($rows[0]);
        $placeholders = ':' . implode(', :', $keys);
        $sql = "INSERT INTO `{$this->table}` (`" . implode('`, `', $keys) . "`) VALUES ($placeholders)";

        $stmt = $this->pdo->prepare($sql);
        foreach ($rows as $row) {
            $stmt->execute($row);
        }
        return $this;
    }
}