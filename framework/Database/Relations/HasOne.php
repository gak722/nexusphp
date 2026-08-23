<?php
declare(strict_types=1);

namespace Nexus\Database\Relations;

use Nexus\Database\Model;

/**
 * One-To-One Relationship Loader
 */
class HasOne extends Relation
{
    public function get(): mixed
    {
        $localValue = $this->parent->{$this->localKey};
        if ($localValue === null) {
            return null;
        }

        /** @var class-string<Model> $relatedClass */
        $relatedClass = $this->relatedClass;
        $row = $relatedClass::query()
            ->where($this->foreignKey, $localValue)
            ->first();

        if (!$row) {
            return null;
        }

        return $relatedClass::newFromBuilder($row);
    }

    public function addEagerConstraints(array $models): void {}

    public function match(array $models, array $results, string $relationName): array
    {
        $dictionary = [];
        foreach ($results as $result) {
            $dictionary[$result->{$this->foreignKey}] = $result;
        }

        foreach ($models as $model) {
            $key = $model->{$this->localKey};
            $model->setRelation($relationName, $dictionary[$key] ?? null);
        }

        return $models;
    }
}
