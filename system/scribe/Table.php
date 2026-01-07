<?php
namespace System\Scribe;

final class Table
{
    private array  $pieces = [];
    private string $driver;
    private ?string $lastCol = null;
    private string $table;
    private bool $isAlter = false;
    private string $alterColumn = '';
    private string $alterColumnType = '';
    private bool $alterColumnNullable = false;
    private ?string $alterColumnDefault = null;
    private ?string $alterPosition = null;

    private function __construct(string $table, string $driver = 'mysql', bool $isAlter = false)
    {
        $this->table   = $table;
        $this->driver  = $driver;
        $this->isAlter = $isAlter;
    }

    public static function create(string $table, string $driver = 'mysql'): self
    {
        return new self($table, $driver, false);
    }

    public static function alter(string $table, string $driver = 'mysql'): self
    {
        return new self($table, $driver, true);
    }

    // ==== MÉTODO STR UNIFICADO ====
    public function str($name = null, int $len = 255): self
    {
        if ($this->isAlter) {
            if ($name !== null && is_int($name)) {
                // Caso: ->str(100) en alter
                $len = $name;
            } elseif ($name !== null) {
                throw new \LogicException("str() en modo ALTER no acepta nombre de columna. Usa addColumn('nombre') primero.");
            }
            $this->alterColumnType = "VARCHAR($len)";
        } else {
            if ($name === null) {
                throw new \LogicException("str() en modo CREATE requiere nombre de columna.");
            }
            $this->pieces[] = "`$name` VARCHAR($len) NOT NULL";
            $this->lastCol = $name;
        }
        return $this;
    }

    // ==== OTROS MÉTODOS UNIFICADOS ====

    public function text($name = null): self
    {
        if ($this->isAlter) {
            if ($name !== null) {
                throw new \LogicException("text() en modo ALTER no acepta nombre de columna. Usa addColumn('nombre') primero.");
            }
            $this->alterColumnType = 'TEXT';
        } else {
            if ($name === null) {
                throw new \LogicException("text() en modo CREATE requiere nombre de columna.");
            }
            $this->pieces[] = "`$name` TEXT NOT NULL";
            $this->lastCol = $name;
        }
        return $this;
    }

    public function bigint($name = null): self
    {
        if ($this->isAlter) {
            if ($name !== null) {
                throw new \LogicException("bigint() en modo ALTER no acepta nombre de columna. Usa addColumn('nombre') primero.");
            }
            $this->alterColumnType = 'BIGINT';
        } else {
            if ($name === null) {
                throw new \LogicException("bigint() en modo CREATE requiere nombre de columna.");
            }
            $this->pieces[] = "`$name` BIGINT NOT NULL";
            $this->lastCol = $name;
        }
        return $this;
    }

    public function int($name = null): self
    {
        if ($this->isAlter) {
            if ($name !== null) {
                throw new \LogicException("int() en modo ALTER no acepta nombre de columna. Usa addColumn('nombre') primero.");
            }
            $this->alterColumnType = 'INT';
        } else {
            if ($name === null) {
                throw new \LogicException("int() en modo CREATE requiere nombre de columna.");
            }
            $this->pieces[] = "`$name` INT NOT NULL";
            $this->lastCol = $name;
        }
        return $this;
    }

    public function tinyint($name = null): self
    {
        if ($this->isAlter) {
            if ($name !== null) {
                throw new \LogicException("tinyint() en modo ALTER no acepta nombre de columna. Usa addColumn('nombre') primero.");
            }
            $this->alterColumnType = 'TINYINT';
        } else {
            if ($name === null) {
                throw new \LogicException("tinyint() en modo CREATE requiere nombre de columna.");
            }
            $this->pieces[] = "`$name` TINYINT NOT NULL";
            $this->lastCol = $name;
        }
        return $this;
    }

    public function smallint($name = null): self
    {
        if ($this->isAlter) {
            if ($name !== null) {
                throw new \LogicException("smallint() en modo ALTER no acepta nombre de columna. Usa addColumn('nombre') primero.");
            }
            $this->alterColumnType = 'SMALLINT';
        } else {
            if ($name === null) {
                throw new \LogicException("smallint() en modo CREATE requiere nombre de columna.");
            }
            $this->pieces[] = "`$name` SMALLINT NOT NULL";
            $this->lastCol = $name;
        }
        return $this;
    }

    public function ulid($name = null): self
    {
        if ($this->isAlter) {
            if ($name !== null) {
                throw new \LogicException("ulid() en modo ALTER no acepta nombre de columna. Usa addColumn('nombre') primero.");
            }
            $this->alterColumnType = 'VARCHAR(26)';
        } else {
            if ($name === null) {
                throw new \LogicException("ulid() en modo CREATE requiere nombre de columna.");
            }
            return $this->str($name, 26);
        }
        return $this;
    }

    public function json($name = null): self
    {
        if ($this->isAlter) {
            if ($name !== null) {
                throw new \LogicException("json() en modo ALTER no acepta nombre de columna. Usa addColumn('nombre') primero.");
            }
            $type = $this->driver === 'mysql' ? 'JSON' : 'TEXT';
            $this->alterColumnType = $type;
        } else {
            if ($name === null) {
                throw new \LogicException("json() en modo CREATE requiere nombre de columna.");
            }
            $type = $this->driver === 'mysql' ? 'JSON' : 'TEXT';
            $this->pieces[] = "`$name` $type NOT NULL";
            $this->lastCol = $name;
        }
        return $this;
    }

    public function enum($name = null, array $values = []): self
    {
        if ($this->isAlter) {
            if ($name !== null) {
                throw new \LogicException("enum() en modo ALTER no acepta nombre de columna. Usa addColumn('nombre') primero.");
            }
            $list = "'" . implode("','", $values) . "'";
            $def  = $this->driver === 'mysql' ? "ENUM($list)" : "TEXT CHECK ({$this->alterColumn} IN ($list))";
            $this->alterColumnType = $def;
        } else {
            if ($name === null) {
                throw new \LogicException("enum() en modo CREATE requiere nombre de columna.");
            }
            $list = "'" . implode("','", $values) . "'";
            $def  = $this->driver === 'mysql' ? "ENUM($list)" : "TEXT CHECK ($name IN ($list))";
            $this->pieces[] = "`$name` $def NOT NULL";
            $this->lastCol = $name;
        }
        return $this;
    }

    public function date($name = null): self
    {
        if ($this->isAlter) {
            if ($name !== null) {
                throw new \LogicException("date() en modo ALTER no acepta nombre de columna. Usa addColumn('nombre') primero.");
            }
            $this->alterColumnType = 'DATE';
        } else {
            if ($name === null) {
                throw new \LogicException("date() en modo CREATE requiere nombre de columna.");
            }
            $this->pieces[] = "`$name` DATE NOT NULL";
            $this->lastCol = $name;
        }
        return $this;
    }

    public function time($name = null): self
    {
        if ($this->isAlter) {
            if ($name !== null) {
                throw new \LogicException("time() en modo ALTER no acepta nombre de columna. Usa addColumn('nombre') primero.");
            }
            $this->alterColumnType = 'TIME';
        } else {
            if ($name === null) {
                throw new \LogicException("time() en modo CREATE requiere nombre de columna.");
            }
            $this->pieces[] = "`$name` TIME NOT NULL";
            $this->lastCol = $name;
        }
        return $this;
    }

    public function datetime($name = null): self
    {
        if ($this->isAlter) {
            if ($name !== null) {
                throw new \LogicException("datetime() en modo ALTER no acepta nombre de columna. Usa addColumn('nombre') primero.");
            }
            $this->alterColumnType = 'DATETIME';
        } else {
            if ($name === null) {
                throw new \LogicException("datetime() en modo CREATE requiere nombre de columna.");
            }
            $this->pieces[] = "`$name` DATETIME NOT NULL";
            $this->lastCol = $name;
        }
        return $this;
    }

    public function timestamp($name = null): self
    {
        if ($this->isAlter) {
            if ($name !== null) {
                throw new \LogicException("timestamp() en modo ALTER no acepta nombre de columna. Usa addColumn('nombre') primero.");
            }
            $this->alterColumnType = 'TIMESTAMP';
        } else {
            if ($name === null) {
                throw new \LogicException("timestamp() en modo CREATE requiere nombre de columna.");
            }
            $this->pieces[] = "`$name` TIMESTAMP NOT NULL";
            $this->lastCol = $name;
        }
        return $this;
    }

    public function decimal($name = null, int $precision = 10, int $scale = 2): self
    {
        if ($this->isAlter) {
            if ($name !== null) {
                throw new \LogicException("decimal() en modo ALTER no acepta nombre de columna. Usa addColumn('nombre') primero.");
            }
            $this->alterColumnType = "DECIMAL($precision,$scale)";
        } else {
            if ($name === null) {
                throw new \LogicException("decimal() en modo CREATE requiere nombre de columna.");
            }
            $this->pieces[] = "`$name` DECIMAL($precision,$scale) NOT NULL";
            $this->lastCol = $name;
        }
        return $this;
    }

    public function unsignedBigInt($name = null): self
    {
        if ($this->isAlter) {
            if ($name !== null) {
                throw new \LogicException("unsignedBigInt() en modo ALTER no acepta nombre de columna. Usa addColumn('nombre') primero.");
            }
            $this->alterColumnType = 'BIGINT UNSIGNED';
        } else {
            if ($name === null) {
                throw new \LogicException("unsignedBigInt() en modo CREATE requiere nombre de columna.");
            }
            $this->pieces[] = "`$name` BIGINT UNSIGNED NOT NULL";
            $this->lastCol = $name;
        }
        return $this;
    }

    public function unsignedInt($name = null): self
    {
        if ($this->isAlter) {
            if ($name !== null) {
                throw new \LogicException("unsignedInt() en modo ALTER no acepta nombre de columna. Usa addColumn('nombre') primero.");
            }
            $this->alterColumnType = 'INT UNSIGNED';
        } else {
            if ($name === null) {
                throw new \LogicException("unsignedInt() en modo CREATE requiere nombre de columna.");
            }
            $this->pieces[] = "`$name` INT UNSIGNED NOT NULL";
            $this->lastCol = $name;
        }
        return $this;
    }

    // === MÉTODOS ESPECIALES ===

    public function id(): self
    {
        $def = $this->driver === 'mysql'
               ? 'BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY'
               : 'INTEGER PRIMARY KEY AUTOINCREMENT';
        $this->pieces[] = "`id` $def";
        $this->lastCol = 'id';
        return $this;
    }

    // === ALTER METHODS ===

    public function addColumn(string $name): self
    {
        if (!$this->isAlter) {
            throw new \LogicException("addColumn() solo válido en alteraciones.");
        }
        $this->alterColumn = $name;
        $this->alterColumnType = '';
        $this->alterColumnNullable = false;
        $this->alterColumnDefault = null;
        $this->alterPosition = null;
        return $this;
    }

    public function modifyColumn(string $name): self
    {
        if (!$this->isAlter) {
            throw new \LogicException("modifyColumn() solo válido en alteraciones.");
        }
        // Para simplificar, no implementamos modifyColumn en esta versión
        throw new \LogicException("modifyColumn() no implementado aún.");
    }

    public function dropColumn(string $name): self
    {
        if (!$this->isAlter) {
            throw new \LogicException("dropColumn() solo válido en alteraciones.");
        }
        $this->pieces[] = "DROP COLUMN `$name`";
        return $this;
    }

    public function first(): self
    {
        if (!$this->isAlter) {
            throw new \LogicException("first() solo válido en alteraciones.");
        }
        $this->alterPosition = 'FIRST';
        return $this;
    }

    public function after(string $column): self
    {
        if (!$this->isAlter) {
            throw new \LogicException("after() solo válido en alteraciones.");
        }
        $this->alterPosition = $column;
        return $this;
    }

    public function before(string $column): self
    {
        if (!$this->isAlter) {
            throw new \LogicException("before() solo válido en alteraciones.");
        }
        // MySQL no soporta BEFORE en ALTER COLUMN, se simula con AFTER
        $this->alterPosition = $column; // El usuario debe asegurarse de que la columna exista
        return $this;
    }

    public function nullable(): self
    {
        if ($this->isAlter) {
            $this->alterColumnNullable = true;
        } else {
            $last = array_key_last($this->pieces);
            $this->pieces[$last] = preg_replace('/NOT NULL/', 'NULL', $this->pieces[$last]);
        }
        return $this;
    }

    public function default($val): self
    {
        if ($this->isAlter) {
            $this->alterColumnDefault = is_string($val) ? "'$val'" : $val;
        } else {
            $last = array_key_last($this->pieces);
            $val = is_string($val) ? "'$val'" : $val;
            $this->pieces[$last] .= " DEFAULT $val";
        }
        return $this;
    }

    public function index(): self
    {
        if ($this->isAlter) {
            $this->pieces[] = "ADD INDEX idx_{$this->alterColumn} ({$this->alterColumn})";
        } else {
            $col = $this->lastCol;
            $this->pieces[] = "INDEX idx_$col ($col)";
        }
        return $this;
    }

    public function unique(): self
    {
        if ($this->isAlter) {
            $this->pieces[] = "ADD UNIQUE INDEX uniq_{$this->alterColumn} ({$this->alterColumn})";
        } else {
            $col = $this->lastCol;
            $this->pieces[] = "UNIQUE INDEX uniq_$col ($col)";
        }
        return $this;
    }

    public function fk(string $refTable, string $refCol = 'id'): self
    {
        if ($this->isAlter) {
            $this->pieces[] = "ADD FOREIGN KEY ({$this->alterColumn}) REFERENCES $refTable($refCol)";
        } else {
            $col = $this->lastCol;
            $this->pieces[] = "FOREIGN KEY ($col) REFERENCES $refTable($refCol)";
        }
        return $this;
    }

    public function stamp(): self
    {
        if ($this->isAlter) {
            throw new \LogicException("stamp() no soportado en alteraciones.");
        }
        $this->pieces[] = "`created_at` DATETIME DEFAULT CURRENT_TIMESTAMP";
        $this->pieces[] = "`updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
        $this->lastCol = 'updated_at';
        return $this;
    }

    // === COMPILAR SQL ===

    public function toSql(): string
    {
        if ($this->isAlter) {
            $parts = [];
            
            // Construir ADD COLUMN si hay definición
            if ($this->alterColumn !== '' && $this->alterColumnType !== '') {
                $null = $this->alterColumnNullable ? 'NULL' : 'NOT NULL';
                $default = $this->alterColumnDefault !== null ? " DEFAULT {$this->alterColumnDefault}" : '';
                $position = '';
                if ($this->alterPosition === 'FIRST') {
                    $position = ' FIRST';
                } elseif ($this->alterPosition !== null) {
                    $position = " AFTER `{$this->alterPosition}`";
                }
                $parts[] = "ADD COLUMN `{$this->alterColumn}` {$this->alterColumnType} $null$default$position";
            }
            
            // Añadir otras partes (índices, FKs, DROP COLUMN, etc.)
            foreach ($this->pieces as $piece) {
                $parts[] = $piece;
            }
            
            $body = implode(', ', $parts);
            return "ALTER TABLE `{$this->table}` $body";
        } else {
            $body = implode(', ', $this->pieces);
            return "CREATE TABLE IF NOT EXISTS `{$this->table}` ($body)";
        }
    }

    public function isAlter(): bool
    {   
        return $this->isAlter;
    }
}