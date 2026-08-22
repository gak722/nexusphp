<?php
declare(strict_types=1);

namespace Nexus\Database\Relations;

use Nexus\Database\Model;

/**
 * Abstract Relationship Loader Contract
 */
abstract class Relation
{
    public function __construct(
        protected Model $parent,
        protected string $relatedClass,
        protected string $foreignKey,
        protected string $localKey
    ) {}

    abstract public function get(): mixed;
}
