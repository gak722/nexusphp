<?php
declare(strict_types=1);

namespace Nexus\Validation;

/**
 * Validation Exception carrying structured ValidationErrors
 */
class ValidationException extends \RuntimeException
{
    public readonly ValidationErrors $errors;

    public function __construct(array|ValidationErrors $errors, string $message = 'The given data was invalid.')
    {
        if (is_array($errors)) {
            $this->errors = new ValidationErrors($errors);
        } else {
            $this->errors = $errors;
        }

        parent::__construct($message, 422);
    }
}
