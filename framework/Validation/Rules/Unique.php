<?php
declare(strict_types=1);

namespace Nexus\Validation\Rules;

use Nexus\Database\Connection;
use Nexus\Database\Model;
use Nexus\Validation\RuleInterface;
use Nexus\Validation\ValidationContext;

class Unique implements RuleInterface
{
    public function __construct(
        protected string $table,
        protected ?string $column = null,
        protected mixed $ignoreId = null,
        protected string $idColumn = 'id',
        protected ?Connection $connection = null
    ) {}

    public function passes(string $attribute, mixed $value, array|ValidationContext $context = []): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        $column = $this->column ?? $attribute;
        $connection = $this->connection;
        if ($context instanceof ValidationContext && $context->dbConnection !== null) {
            $connection = $context->dbConnection;
        }
        if ($connection === null) {
            $connection = Model::getConnectionResolver();
        }

        if ($connection === null) {
            return false;
        }

        // Sanitize column and table names to prevent SQL injection in metadata strings
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $this->table) || !preg_match('/^[a-zA-Z0-9_]+$/', $column) || !preg_match('/^[a-zA-Z0-9_]+$/', $this->idColumn)) {
            return false;
        }

        $query = "SELECT COUNT(*) as count FROM {$this->table} WHERE {$column} = ?";
        $bindings = [$value];

        $ignoreId = $this->ignoreId;
        if ($ignoreId === 'NULL' || $ignoreId === 'null') {
            $ignoreId = null;
        }
        if ($ignoreId === null && $context instanceof ValidationContext && $context->targetModel instanceof Model) {
            $ignoreId = $context->targetModel->getKey();
        }

        if ($ignoreId !== null) {
            $query .= " AND {$this->idColumn} != ?";
            $bindings[] = $ignoreId;
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
