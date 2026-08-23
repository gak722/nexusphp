<?php
declare(strict_types=1);

namespace Nexus\Database\Relations;

use Nexus\Database\Model;

/**
 * Many-To-One Relationship Loader
 */
class BelongsTo extends Relation
{
    public function get(): mixed
    {
        $foreignValue = $this->parent->{$this->foreignKey};
        if ($foreignValue === null) {
            return null;
        }

        /** @var class-string<Model> $relatedClass */
        $relatedClass = $this->relatedClass;
        return $relatedClass::find($foreignValue);
    }

    public function addEagerConstraints(array $models): void {}

    public function match(array $models, array $results, string $relationName): array
    {
        $dictionary = [];
        foreach ($results as $result) {
            $dictionary[$result->{$this->localKey}] = $result;
        }

        foreach ($models as $model) {
            $key = $model->{$this->foreignKey};
            $model->setRelation($relationName, $dictionary[$key] ?? null);
        }

        return $models;
    }
}
