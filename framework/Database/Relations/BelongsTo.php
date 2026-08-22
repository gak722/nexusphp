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
}
