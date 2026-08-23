<?php
declare(strict_types=1);

namespace Nexus\Database\Schema;

use Nexus\Database\Grammar\GrammarInterface;

class TableBuilder
{
    /** @var ColumnDefinition[] */
    public array $columns = [];
    public array $foreignKeys = [];
    public array $indexes = [];

    public function __construct(public string $table, protected GrammarInterface $grammar) {}

    public function id(string $name = 'id'): ColumnDefinition
    {
        $col = new ColumnDefinition($name, 'INTEGER');
        $col->autoIncrement = true;
        $this->columns[] = $col;
        return $col;
    }

    public function string(string $name, int $length = 255): ColumnDefinition
    {
        $col = new ColumnDefinition($name, 'VARCHAR', ['length' => $length]);
        $this->columns[] = $col;
        return $col;
    }

    public function text(string $name): ColumnDefinition
    {
        $col = new ColumnDefinition($name, 'TEXT');
        $this->columns[] = $col;
        return $col;
    }

    public function integer(string $name): ColumnDefinition
    {
        $col = new ColumnDefinition($name, 'INTEGER');
        $this->columns[] = $col;
        return $col;
    }

    public function bigInteger(string $name): ColumnDefinition
    {
        $col = new ColumnDefinition($name, 'BIGINT');
        $this->columns[] = $col;
        return $col;
    }

    public function boolean(string $name): ColumnDefinition
    {
        $col = new ColumnDefinition($name, 'BOOLEAN');
        $this->columns[] = $col;
        return $col;
    }

    public function dateTime(string $name): ColumnDefinition
    {
        $col = new ColumnDefinition($name, 'DATETIME');
        $this->columns[] = $col;
        return $col;
    }

    public function decimal(string $name, int $precision = 10, int $scale = 2): ColumnDefinition
    {
        $col = new ColumnDefinition($name, 'DECIMAL', ['precision' => $precision, 'scale' => $scale]);
        $this->columns[] = $col;
        return $col;
    }

    public function float(string $name): ColumnDefinition
    {
        $col = new ColumnDefinition($name, 'FLOAT');
        $this->columns[] = $col;
        return $col;
    }

    public function double(string $name): ColumnDefinition
    {
        $col = new ColumnDefinition($name, 'DOUBLE');
        $this->columns[] = $col;
        return $col;
    }

    public function json(string $name): ColumnDefinition
    {
        $col = new ColumnDefinition($name, 'JSON');
        $this->columns[] = $col;
        return $col;
    }

    public function uuid(string $name = 'uuid'): ColumnDefinition
    {
        $col = new ColumnDefinition($name, 'VARCHAR', ['length' => 36]);
        $this->columns[] = $col;
        return $col;
    }

    public function date(string $name): ColumnDefinition
    {
        $col = new ColumnDefinition($name, 'DATE');
        $this->columns[] = $col;
        return $col;
    }

    public function time(string $name): ColumnDefinition
    {
        $col = new ColumnDefinition($name, 'TIME');
        $this->columns[] = $col;
        return $col;
    }

    public function timestamp(string $name): ColumnDefinition
    {
        $col = new ColumnDefinition($name, 'TIMESTAMP');
        $this->columns[] = $col;
        return $col;
    }

    public function char(string $name, int $length = 255): ColumnDefinition
    {
        $col = new ColumnDefinition($name, 'CHAR', ['length' => $length]);
        $this->columns[] = $col;
        return $col;
    }

    public function foreignId(string $name): ColumnDefinition
    {
        $col = new ColumnDefinition($name, 'BIGINT');
        $this->columns[] = $col;
        return $col;
    }

    public function foreign(string $column): ForeignKeyDefinition
    {
        $fk = new ForeignKeyDefinition($column);
        $this->foreignKeys[] = $fk;
        return $fk;
    }

    public function timestamps(): void
    {
        $this->dateTime('created_at')->nullable();
        $this->dateTime('updated_at')->nullable();
    }

    public array $primaryKeys = [];

    public function primary(string|array $columns): static
    {
        $this->primaryKeys = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    public function index(string|array $columns, ?string $name = null): static
    {
        $cols = is_array($columns) ? $columns : [$columns];
        $this->indexes[] = ['type' => 'INDEX', 'columns' => $cols, 'name' => $name];
        return $this;
    }

    public function uniqueIndex(string|array $columns, ?string $name = null): static
    {
        $cols = is_array($columns) ? $columns : [$columns];
        $this->indexes[] = ['type' => 'UNIQUE', 'columns' => $cols, 'name' => $name];
        return $this;
    }

    public function softDeletes(string $column = 'deleted_at'): ColumnDefinition
    {
        return $this->dateTime($column)->nullable();
    }

    public function toBlueprintArray(): array
    {
        $cols = [];
        foreach ($this->columns as $c) {
            $cols[] = [
                'name' => $c->name,
                'type' => $c->type,
                'nullable' => $c->nullable,
                'default' => $c->default,
                'unique' => $c->unique,
                'autoIncrement' => $c->autoIncrement,
                'length' => $c->attributes['length'] ?? null,
            ];
        }

        $fks = [];
        foreach ($this->foreignKeys as $fk) {
            $fks[] = [
                'column' => $fk->column,
                'on' => $fk->onTable,
                'references' => $fk->referencesColumn,
                'onDelete' => $fk->onDeleteAction,
                'onUpdate' => $fk->onUpdateAction,
            ];
        }

        return [
            'name' => $this->table,
            'ifNotExists' => true,
            'columns' => $cols,
            'primaryKeys' => $this->primaryKeys,
            'indexes' => $this->indexes,
            'foreignKeys' => $fks,
        ];
    }
}

class ForeignKeyDefinition
{
    public string $onTable;
    public string $referencesColumn;
    public ?string $onDeleteAction = null;
    public ?string $onUpdateAction = null;

    public function __construct(public string $column) {}

    public function references(string $column): static
    {
        $this->referencesColumn = $column;
        return $this;
    }

    public function on(string $table): static
    {
        $this->onTable = $table;
        return $this;
    }

    public function onDelete(string $action): static
    {
        $this->onDeleteAction = strtoupper($action);
        return $this;
    }

    public function onUpdate(string $action): static
    {
        $this->onUpdateAction = strtoupper($action);
        return $this;
    }

    public function cascadeOnDelete(): static
    {
        $this->onDeleteAction = 'CASCADE';
        return $this;
    }

    public function nullOnDelete(): static
    {
        $this->onDeleteAction = 'SET NULL';
        return $this;
    }

    public function restrictOnDelete(): static
    {
        $this->onDeleteAction = 'RESTRICT';
        return $this;
    }

    public function cascadeOnUpdate(): static
    {
        $this->onUpdateAction = 'CASCADE';
        return $this;
    }

    public function restrictOnUpdate(): static
    {
        $this->onUpdateAction = 'RESTRICT';
        return $this;
    }
}
