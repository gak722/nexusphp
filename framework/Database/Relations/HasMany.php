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
            $model = new $relatedClass();
            $model->fill($row);
            $models[] = $model;
        }

        return $models;
    }
}
