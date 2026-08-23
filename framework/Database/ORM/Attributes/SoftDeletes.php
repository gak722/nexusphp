<?php
declare(strict_types=1);

namespace Nexus\Database\ORM\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class SoftDeletes
{
    public function __construct(public string $column = 'deleted_at') {}
}
