<?php
declare(strict_types=1);

namespace Nexus\Validation\Rules;

use Nexus\Database\Model;

use Nexus\Validation\RuleInterface;

class Unique implements RuleInterface
{
    public function __construct(
        protected string $table,
        protected ?string $column = null,
        protected mixed $ignoreId = null,
        protected string $idColumn = 'id'
    ) {}

    public function passes(string $attribute, mixed $value, array $data = []): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        $column = $this->column ?? $attribute;
        $connection = Model::getConnectionResolver();
        if ($connection === null) {
            return true;
        }

        $query = "SELECT COUNT(*) as count FROM {$this->table} WHERE {$column} = ?";
        $bindings = [$value];

        if ($this->ignoreId !== null) {
            $query .= " AND {$this->idColumn} != ?";
            $bindings[] = $this->ignoreId;
        }

        $result = $connection->select($query, $bindings);
        $count = (int) ($result[0]['count'] ?? 0);

        return $count === 0;
    }

    public function message(string $attribute): string
    {
        return "The {$attribute} has already been taken.";
    }
}
