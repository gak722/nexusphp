<?php
declare(strict_types=1);

namespace Nexus\Database;

/**
 * Fluent Programmatic Database DDL Schema Blueprint
 */
class Blueprint
{
    protected array $columns = [];

    public function __construct(public readonly string $table, protected string $driver = 'mysql') {}

    public function id(string $name = 'id'): static
    {
        if ($this->driver === 'sqlite') {
            $this->columns[] = "{$name} INTEGER PRIMARY KEY AUTOINCREMENT";
        } else {
            $this->columns[] = "{$name} BIGINT AUTO_INCREMENT PRIMARY KEY";
        }
        return $this;
    }

    public function string(string $name, int $length = 255): static
    {
        $this->columns[] = "{$name} VARCHAR({$length}) NOT NULL";
        return $this;
    }

    public function text(string $name): static
    {
        $this->columns[] = "{$name} TEXT NOT NULL";
        return $this;
    }

    public function integer(string $name): static
    {
        $this->columns[] = "{$name} INT NOT NULL";
        return $this;
    }

    public function bigInteger(string $name): static
    {
        $this->columns[] = "{$name} BIGINT NOT NULL";
        return $this;
    }

    public function boolean(string $name): static
    {
        $this->columns[] = "{$name} TINYINT(1) NOT NULL DEFAULT 0";
        return $this;
    }

    public function foreignId(string $name): static
    {
        $this->columns[] = "{$name} BIGINT NOT NULL";
        return $this;
    }

    public function timestamps(): static
    {
        if ($this->driver === 'sqlite') {
            $this->columns[] = "created_at DATETIME DEFAULT CURRENT_TIMESTAMP";
            $this->columns[] = "updated_at DATETIME DEFAULT CURRENT_TIMESTAMP";
        } else {
            $this->columns[] = "created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
            $this->columns[] = "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
        }
        return $this;
    }

    public function toSql(): string
    {
        $cols = implode(", ", $this->columns);
        if ($this->driver === 'sqlite') {
            return "CREATE TABLE IF NOT EXISTS {$this->table} ({$cols});";
        }
        return "CREATE TABLE IF NOT EXISTS {$this->table} ({$cols}) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    }
}
