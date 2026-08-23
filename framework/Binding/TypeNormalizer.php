<?php
declare(strict_types=1);

namespace Nexus\Binding;

/**
 * Type Normalizer converting HTTP inputs (strings, scalars) into target typed PHP values.
 */
class TypeNormalizer
{
    public static function convert(mixed $value, \ReflectionType $type, string $propertyName = ''): mixed
    {
        if ($value === null) {
            if ($type->allowsNull()) {
                return null;
            }
            throw new BindingException("Property [{$propertyName}] cannot be null.");
        }

        if ($type instanceof \ReflectionUnionType) {
            foreach ($type->getTypes() as $unionType) {
                try {
                    return static::convertSingle($value, $unionType, $propertyName);
                } catch (\Throwable $e) {
                    continue;
                }
            }
            throw new BindingException("Value for [{$propertyName}] does not match any union type variant.");
        }

        if ($type instanceof \ReflectionNamedType) {
            return static::convertSingle($value, $type, $propertyName);
        }

        return $value;
    }

    protected static function convertSingle(mixed $value, \ReflectionNamedType $type, string $propertyName): mixed
    {
        $typeName = $type->getName();

        if ($type->isBuiltin()) {
            return match ($typeName) {
                'int' => static::toInt($value, $propertyName),
                'float' => static::toFloat($value, $propertyName),
                'string' => (string) $value,
                'bool' => static::toBool($value),
                'array' => (array) $value,
                'object' => (object) $value,
                'mixed' => $value,
                default => $value,
            };
        }

        // Backed Enum conversion
        if (enum_exists($typeName)) {
            if ($value instanceof $typeName) {
                return $value;
            }
            $enumRef = new \ReflectionEnum($typeName);
            if ($enumRef->isBacked()) {
                $backingType = (string) $enumRef->getBackingType();
                $typedVal = $backingType === 'int' ? (int) $value : (string) $value;
                $case = $typeName::tryFrom($typedVal);
                if ($case === null) {
                    throw new BindingException("Invalid enum value [{$value}] for [{$typeName}].");
                }
                return $case;
            }
        }

        // DateTimeInterface conversion
        if (is_a($typeName, \DateTimeInterface::class, true)) {
            if ($value instanceof \DateTimeInterface) {
                return $value;
            }
            if (is_string($value) || is_numeric($value)) {
                try {
                    return new \DateTimeImmutable((string)$value);
                } catch (\Throwable $e) {
                    throw new BindingException("Invalid date string [{$value}] for [{$propertyName}].");
                }
            }
        }

        return $value;
    }

    public static function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        if (is_string($value)) {
            $lower = strtolower(trim($value));
            if (in_array($lower, ['true', '1', 'on', 'yes'], true)) {
                return true;
            }
            if (in_array($lower, ['false', '0', 'off', 'no'], true)) {
                return false;
            }
        }

        return (bool) $value;
    }

    public static function toInt(mixed $value, string $propertyName): int
    {
        if (is_int($value)) return $value;
        if (is_numeric($value)) return (int) $value;

        throw new BindingException("Cannot convert value of type [" . gettype($value) . "] to integer for property [{$propertyName}].");
    }

    public static function toFloat(mixed $value, string $propertyName): float
    {
        if (is_float($value) || is_int($value)) return (float) $value;
        if (is_numeric($value)) return (float) $value;

        throw new BindingException("Cannot convert value of type [" . gettype($value) . "] to float for property [{$propertyName}].");
    }
}
