<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * QueryBuilder súper minimalista para Pulse Framework
 * - Fluent interface elegante
 * - SQL seguro con parámetros
 * - Operaciones CRUD simples
 * - Integrado con PulseDatabase
 */
class QueryBuilder
{
    private $pdo;
    private $query = [];
    private $bindings = [];
    private $table = '';
    
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->reset();
    }

    /* ================================================================
       FACTORY & STATIC METHODS
    ================================================================= */
    
    /**
     * Crear una instancia desde PulseDatabase
     */
    public static function fromDatabase(PulseDatabase $db): self
    {
        return new self($db->getPdo());
    }
    
    /**
     * Crear instancia directa con tabla
     */
    public static function table(string $table, $pdo): self
    {
        return (new self($pdo))->from($table);
    }

    /* ================================================================
       FLUENT INTERFACE - SELECT
    ================================================================= */
    
    public function from(string $table): self
    {
        $this->table = $table;
        $this->query['type'] = 'SELECT';
        return $this;
    }
    
    public function select(string ...$columns): self
    {
        $this->query['select'] = empty($columns) ? ['*'] : $columns;
        return $this;
    }
    
    public function where(string $column, $operator = null, $value = null): self
    {
        // where('name', 'John') -> where('name', '=', 'John')
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        
        $placeholder = $this->addBinding($value);
        $condition = "$column $operator $placeholder";
        
        $this->query['where'][] = isset($this->query['where']) ? "AND $condition" : $condition;
        return $this;
    }
    
    public function whereIn(string $column, array $values): self
    {
        $placeholders = array_map(function($val) {
            return $this->addBinding($val);
        }, $values);
        $condition = "$column IN (" . implode(',', $placeholders) . ")";
        
        $this->query['where'][] = isset($this->query['where']) ? "AND $condition" : $condition;
        return $this;
    }
    
    public function whereNotIn(string $column, array $values): self
    {
        $placeholders = array_map(function($val) {
            return $this->addBinding($val);
        }, $values);
        $condition = "$column NOT IN (" . implode(',', $placeholders) . ")";
        
        $this->query['where'][] = isset($this->query['where']) ? "AND $condition" : $condition;
        return $this;
    }

    public function whereNull(string $column): self
    {
        $condition = "$column IS NULL";
        $this->query['where'][] = isset($this->query['where']) ? "AND $condition" : $condition;
        return $this;
    }

    public function whereNotNull(string $column): self
    {
        $condition = "$column IS NOT NULL";
        $this->query['where'][] = isset($this->query['where']) ? "AND $condition" : $condition;
        return $this;
    }
    
    public function orWhere(string $column, $operator = null, $value = null): self
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        
        $placeholder = $this->addBinding($value);
        $this->query['where'][] = "OR $column $operator $placeholder";
        return $this;
    }
    
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->query['order'][] = "$column " . strtoupper($direction);
        return $this;
    }
    
    public function limit(int $count, int $offset = 0): self
    {
        $this->query['limit'] = $count;
        if ($offset > 0) {
            $this->query['offset'] = $offset;
        }
        return $this;
    }
    
    public function join(string $table, string $first, string $operator, string $second): self
    {
        $this->query['joins'][] = "JOIN $table ON $first $operator $second";
        return $this;
    }
    
    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        $this->query['joins'][] = "LEFT JOIN $table ON $first $operator $second";
        return $this;
    }

    public function groupBy(string ...$columns): self
    {
        $this->query['group'] = $columns;
        return $this;
    }

    public function having(string $column, $operator = null, $value = null): self
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        
        $placeholder = $this->addBinding($value);
        $condition = "$column $operator $placeholder";
        
        $this->query['having'][] = isset($this->query['having']) ? "AND $condition" : $condition;
        return $this;
    }

    /* ================================================================
       FLUENT INTERFACE - INSERT/UPDATE/DELETE
    ================================================================= */
    
    public function insert(array $data): int
    {
        $columns = array_keys($data);
        // Convertir booleanos a enteros antes de crear los placeholders
        $values = array_map(function($value) {
            return is_bool($value) ? (int)$value : $value;
        }, $data);

        $placeholders = array_map(function($val) {
            return $this->addBinding($val);
        }, $values);
        
        $sql = "INSERT INTO {$this->table} (" . 
            implode(',', $columns) . 
            ") VALUES (" . 
            implode(',', $placeholders) . ")";
        
        $this->execute($sql);
        return (int) $this->pdo->lastInsertId();
    }
    
    public function update(array $data): int
    {
        $sets = [];
        foreach ($data as $column => $value) {
            $placeholder = $this->addBinding($value);
            $sets[] = "$column = $placeholder";
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(',', $sets);
        
        if (isset($this->query['where'])) {
            $sql .= " WHERE " . implode(' ', $this->query['where']);
        }
        
        return $this->execute($sql)->rowCount();
    }
    
    public function delete(): int
    {
        $sql = "DELETE FROM {$this->table}";
        
        if (isset($this->query['where'])) {
            $sql .= " WHERE " . implode(' ', $this->query['where']);
        }
        
        $stmt = $this->execute($sql);
        return $stmt->rowCount();
    }

    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    /* ================================================================
       TRANSACCIONES
    ================================================================= */
    
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    public function transaction(callable $callback)
    {
        try {
            $this->beginTransaction();
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (Exception $e) {
            $this->rollBack();
            throw $e;
        }
    }

    /* ================================================================
       EXECUTION METHODS
    ================================================================= */
    
    public function get(): array
    {
        return $this->execute($this->buildSelectQuery())->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getObjects(): array
    {
        return $this->execute($this->buildSelectQuery())->fetchAll(PDO::FETCH_OBJ);
    }
    
    public function first()
    {
        $this->limit(1);
        $result = $this->getObjects(); // Cambiar a objetos
        return !empty($result) ? $result[0] : null;
    }
    
    public function find($id, string $column = 'id')
    {
        return $this->where($column, $id)->first();
    }

    public function findOrFail($id, string $column = 'id'): array
    {
        $result = $this->find($id, $column);
        if ($result === null) {
            throw new Exception("Record not found with $column = $id");
        }
        return $result;
    }
    
    public function count(): int
    {
        $this->query['select'] = ['COUNT(*) as count'];
        $result = $this->execute($this->buildSelectQuery())->fetch(PDO::FETCH_ASSOC);
        return (int) $result['count'];
    }
    
    public function exists(): bool
    {
        return $this->count() > 0;
    }
    
    public function pluck(string $column): array
    {
        $this->query['select'] = [$column];
        $results = $this->get();
        return array_column($results, $column);
    }

    public function value(string $column)
    {
        $this->query['select'] = [$column];
        $result = $this->first();
        return isset($result[$column]) ? $result[$column] : null;
    }

    /* ================================================================
       RAW QUERIES
    ================================================================= */
    
    public function raw(string $sql, array $bindings = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function rawFirst(string $sql, array $bindings = [])
    {
        $results = $this->raw($sql, $bindings);
        return !empty($results) ? $results[0] : null;
    }

    /**
     * Crea una copia independiente de la consulta actual.
     */
    public function cloneQuery(): self
    {
        $clone = new self($this->pdo);
        $clone->table = $this->table;
        $clone->query = $this->query;
        $clone->bindings = $this->bindings;
        return $clone;
    }

    /* ================================================================
       INTERNAL HELPERS
    ================================================================= */
    
    private function buildSelectQuery(): string
    {
        $sql = "SELECT " . implode(',', isset($this->query['select']) ? $this->query['select'] : ['*']);
        $sql .= " FROM {$this->table}";
        
        if (isset($this->query['joins'])) {
            $sql .= " " . implode(' ', $this->query['joins']);
        }
        
        if (isset($this->query['where'])) {
            $sql .= " WHERE " . implode(' ', $this->query['where']);
        }

        if (isset($this->query['group'])) {
            $sql .= " GROUP BY " . implode(',', $this->query['group']);
        }

        if (isset($this->query['having'])) {
            $sql .= " HAVING " . implode(' ', $this->query['having']);
        }
        
        if (isset($this->query['order'])) {
            $sql .= " ORDER BY " . implode(',', $this->query['order']);
        }
        
        if (isset($this->query['limit'])) {
            $sql .= " LIMIT " . $this->query['limit'];
            if (isset($this->query['offset'])) {
                $sql .= " OFFSET " . $this->query['offset'];
            }
        }
        
        return $sql;
    }
    
    private function addBinding($value): string
    {
        $placeholder = ':param' . count($this->bindings);
        $this->bindings[$placeholder] = $value;
        return $placeholder;
    }
    
    private function execute(string $sql)
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->bindings);

        return $stmt;
    }
    
    public function reset(): self
    {
        $this->query = [];
        $this->bindings = [];
        return $this;
    }
    
    /* ================================================================
       DEBUGGING
    ================================================================= */
    
    public function toSql(): string
    {
        return $this->buildSelectQuery();
    }
    
    public function dd()
    {
        echo "<pre>";
        echo "SQL: " . $this->toSql() . "\n";
        echo "Bindings: " . json_encode($this->bindings, JSON_PRETTY_PRINT) . "\n";
        echo "</pre>";
        die();
    }
}