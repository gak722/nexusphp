<?php
declare(strict_types=1);

namespace Nexus\Database\Relations;

use Nexus\Database\Model;

/**
 * One-To-Many Relationship Loader
 */
class HasMany extends Relation
{
    public function get(): mixed
    {
        $localValue = $this->parent->{$this->localKey};
        if ($localValue === null) {
            return [];
        }

        /** @var class-string<Model> $relatedClass */
        $relatedClass = $this->relatedClass;
        $results = $relatedClass::query()
            ->where($this->foreignKey, $localValue)
            ->get();

        $models = [];
        foreach ($results as $row) {
            $models[] = $relatedClass::newFromBuilder($row);
        }

        return $models;
    }

    public function addEagerConstraints(array $models): void
    {
        // Eager constraints will be handled directly in QueryBuilder eager load
    }

    public function match(array $models, array $results, string $relationName): array
    {
        $dictionary = [];
        foreach ($results as $result) {
            $dictionary[$result->{$this->foreignKey}][] = $result;
        }

        foreach ($models as $model) {
            $key = $model->{$this->localKey};
            $model->setRelation($relationName, $dictionary[$key] ?? []);
        }

        return $models;
    }
}
