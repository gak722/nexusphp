<?php
declare(strict_types=1);

namespace Nexus\Binding;

interface BinderInterface
{
    public function bind(
        object|string $target,
        array $data,
        ?BindingContext $context = null
    ): object;
}
