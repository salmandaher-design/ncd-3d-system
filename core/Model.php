<?php
/**
 * Base model. Provides a shared PDO connection and small query helpers.
 * All queries use prepared statements.
 */
abstract class Model
{
    protected PDO $db;
    protected string $table = '';

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /** Run a prepared statement and return it. */
    protected function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /** Fetch a single row (or null). */
    protected function fetch(string $sql, array $params = []): ?array
    {
        $row = $this->run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    /** Fetch all rows. */
    protected function fetchAll(string $sql, array $params = []): array
    {
        return $this->run($sql, $params)->fetchAll();
    }

    /** Fetch a single scalar value. */
    protected function scalar(string $sql, array $params = [])
    {
        return $this->run($sql, $params)->fetchColumn();
    }

    /** Generic find-by-id for the model's table. */
    public function find(int $id): ?array
    {
        return $this->fetch("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
    }

    /** Generic list-all for the model's table. */
    public function all(string $orderBy = 'id DESC'): array
    {
        return $this->fetchAll("SELECT * FROM {$this->table} ORDER BY {$orderBy}");
    }

    /** Delete a row by id. */
    public function delete(int $id): bool
    {
        return $this->run("DELETE FROM {$this->table} WHERE id = ?", [$id])->rowCount() > 0;
    }

    public function lastId(): int
    {
        return (int) $this->db->lastInsertId();
    }
}
