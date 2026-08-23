<?php
declare(strict_types=1);

namespace Nexus\Binding;

use Nexus\Database\Model;
use Nexus\Validation\Validator;

/**
 * Production-Grade Model & DTO Binder with Reflection & Mass Assignment Safeguards
 */
class Binder implements BinderInterface
{
    /** Sensitive default guarded fields requiring explicit fillable authorization */
    protected const SENSITIVE_FIELDS = [
        'id', 'primary_key', 'is_admin', 'role', 'roles', 'permission', 'permissions',
        'created_at', 'updated_at', 'deleted_at', 'remember_token', 'password_hash'
    ];

    public function bind(
        object|string $target,
        array $data,
        ?BindingContext $context = null
    ): object {
        $context = $context ?? new BindingContext();

        if ($context->currentDepth > $context->maxDepth) {
            throw new BindingException("Maximum binding depth recursion limit [{$context->maxDepth}] exceeded.");
        }

        // Security check against arbitrary class instantiation
        if (is_string($target)) {
            if (!class_exists($target)) {
                throw new BindingException("Target class [{$target}] does not exist.");
            }
            $ref = new \ReflectionClass($target);
            if ($ref->isAbstract() || $ref->isInterface()) {
                throw new BindingException("Cannot instantiate abstract class or interface [{$target}].");
            }
            $target = $ref->newInstanceWithoutConstructor();
        }

        // Trigger beforeBind hook if defined
        if (method_exists($target, 'beforeBind')) {
            $target->beforeBind($data);
        }

        if ($target instanceof Model) {
            $this->bindModel($target, $data, $context);
        } else {
            $this->bindDto($target, $data, $context);
        }

        // Trigger afterBind hook if defined
        if (method_exists($target, 'afterBind')) {
            $target->afterBind();
        }

        return $target;
    }

    protected function bindModel(Model $model, array $data, BindingContext $context): void
    {
        $fillableData = [];

        foreach ($data as $key => $value) {
            // Mass assignment protection check
            if (!$model->isFillable($key)) {
                if ($context->onUnknownField === 'exception') {
                    throw new BindingException("Field [{$key}] is guarded or not fillable on model [" . get_class($model) . "].");
                }
                continue;
            }

            if (in_array(strtolower($key), static::SENSITIVE_FIELDS, true) && !$context->allowUnsafeFields) {
                if (!$model->isFillable($key)) {
                    continue;
                }
            }

            $fillableData[$key] = $value;
        }

        $model->fill($fillableData);
    }

    protected function bindDto(object $dto, array $data, BindingContext $context): void
    {
        $reflector = new \ReflectionClass($dto);

        foreach ($reflector->getProperties() as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $name = $property->getName();
            if (!array_key_exists($name, $data)) {
                continue;
            }

            $rawValue = $data[$name];
            $type = $property->getType();

            $boundValue = null;
            if ($type !== null) {
                if ($type instanceof \ReflectionNamedType && !$type->isBuiltin() && is_array($rawValue)) {
                    $nestedClass = $type->getName();
                    if (class_exists($nestedClass)) {
                        $boundValue = $this->bind($nestedClass, $rawValue, $context->nextLevel());
                    } else {
                        $boundValue = TypeNormalizer::convert($rawValue, $type, $name);
                    }
                } else {
                    $boundValue = TypeNormalizer::convert($rawValue, $type, $name);
                }
            } else {
                $boundValue = $rawValue;
            }

            $property->setAccessible(true);
            $property->setValue($dto, $boundValue);
        }
    }
}
