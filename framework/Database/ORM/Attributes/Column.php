<?php
declare(strict_types=1);

namespace Nexus\Database\ORM\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Column
{
    public function __construct(
        public ?string $name = null,
        public ?string $type = null,
        public bool $nullable = false,
        public mixed $default = null
    ) {}
}
