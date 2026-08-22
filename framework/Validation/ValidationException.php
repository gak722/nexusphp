<?php
declare(strict_types=1);

namespace Nexus\Validation;

/**
 * Validation Exception carrying 422 error bags
 */
class ValidationException extends \RuntimeException
{
    public function __construct(public readonly array $errors)
    {
        parent::__construct('The given data was invalid.', 422);
    }
}
