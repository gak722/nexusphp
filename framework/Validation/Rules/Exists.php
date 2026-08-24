<?php
declare(strict_types=1);

namespace Nexus\Validation\Rules;

use Nexus\Database\Connection;
use Nexus\Database\Model;
use Nexus\Validation\RuleInterface;
use Nexus\Validation\ValidationContext;

class Exists implements RuleInterface
{
    public function __construct(
        protected string $table,
        protected ?string $column = null,
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

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $this->table) || !preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
            return false;
        }

        $query = "SELECT COUNT(*) as count FROM {$this->table} WHERE {$column} = ?";
        $result = $connection->select($query, [$value]);
        $count = (int) ($result[0]['count'] ?? 0);

        return $count > 0;
    }

    public function message(string $attribute): string
    {
        return "The selected {$attribute} is invalid.";
    }
}
