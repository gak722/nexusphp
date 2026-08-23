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
        $results = $connection->select($query, [$localValue]);

        /** @var class-string<Model> $relatedClass */
        $relatedClass = $this->relatedClass;
        return array_map(fn($row) => $relatedClass::newFromBuilder($row), $results);
    }

    public function addEagerConstraints(array $models): void {}

    public function match(array $models, array $results, string $relationName): array
    {
        // Many-to-many eager match logic
        foreach ($models as $model) {
            $model->setRelation($relationName, $model->{$relationName}()->get());
        }
        return $models;
    }
}
