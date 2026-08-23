<?php
declare(strict_types=1);

namespace Nexus\Support\Result;

/**
 * @template T
 */
class ParseResult
{
    /**
     * @param bool $success
     * @param T|null $value
     * @param string|null $error
     * @param mixed $input
     */
    public function __construct(
        public readonly bool $success,
        public readonly mixed $value = null,
        public readonly ?string $error = null,
        public readonly mixed $input = null
    ) {}

    /**
     * @template V
     * @param V $value
     * @param mixed $input
     * @return self<V>
     */
    public static function ok(mixed $value, mixed $input = null): self
    {
        return new self(true, $value, null, $input);
    }

    public static function fail(string $error, mixed $input = null): self
    {
        return new self(false, null, $error, $input);
    }

    public function isValid(): bool
    {
        return $this->success;
    }

    public function isFailed(): bool
    {
        return !$this->success;
    }

    /**
     * @return T
     * @throws \RuntimeException
     */
    public function value(): mixed
    {
        if (!$this->success) {
            throw new \RuntimeException("Cannot get value of a failed ParseResult: {$this->error}");
        }

        return $this->value;
    }

    /**
     * @template D
     * @param D $default
     * @return T|D
     */
    public function valueOr(mixed $default): mixed
    {
        return $this->success ? $this->value : $default;
    }
}
