<?php
declare(strict_types=1);

namespace Nexus\Database\Relations;

use Nexus\Database\Model;

/**
 * Many-To-Many Relationship Loader
 */
class BelongsToMany extends Relation
{
    public function __construct(
        Model $parent,
        string $relatedClass,
        protected string $pivotTable,
        string $foreignKey,
        string $relatedKey
    ) {
        parent::__construct($parent, $relatedClass, $foreignKey, $relatedKey);
    }

    public function get(): mixed
    {
        $localValue = $this->parent->{$this->parent->getPrimaryKey()};
        if ($localValue === null) {
            return [];
        }

        $query = "SELECT r.* FROM " . (new $this->relatedClass())->getTable() . " r " .
                 "INNER JOIN {$this->pivotTable} p ON r.{$this->localKey} = p.{$this->relatedKey} " .
                 "WHERE p.{$this->foreignKey} = ?";

        $connection = Model::getConnectionResolver();
        return $connection->select($query, [$localValue]);
    }
}
