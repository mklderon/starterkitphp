<?php
defined('BASEPATH') or exit('No direct script access allowed');

class PulseModel
{
    protected $db;
    protected $queryBuilder;

    public function __construct()
    {
        $this->db = new PulseDatabase();
        $this->queryBuilder = QueryBuilder::fromDatabase($this->db);
    }

    // Métodos tradicionales de PulseDatabase
    public function query($sql)
    {
        $this->db->query($sql);
    }

    public function bind($param, $value, $type = null)
    {
        $this->db->bind($param, $value, $type);
    }

    public function execute()
    {
        return $this->db->execute();
    }

    public function resultSet()
    {
        return $this->db->resultSet();
    }

    public function single()
    {
        return $this->db->single();
    }

    public function rowCount()
    {
        return $this->db->rowCount();
    }

    public function lastInsertId()
    {
        return $this->db->lastInsertId();
    }

    // Nuevo método para usar QueryBuilder
    public function table(string $tableName): QueryBuilder
    {
        return $this->queryBuilder->from($tableName);
    }

    // Método alternativo más corto
    public function qb(string $tableName): QueryBuilder
    {
        return $this->table($tableName);
    }
}