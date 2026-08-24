<?php
declare(strict_types=1);

namespace Nexus\Security;

use Closure;
use RuntimeException;

class Gate
{
    protected static array $policies = [];
    protected static array $abilities = [];
    protected static ?Closure $userResolver = null;
    protected static ?Closure $beforeCallback = null;

    public static function setUserResolver(Closure $resolver): void
    {
        static::$userResolver = $resolver;
    }

    public static function before(Closure $callback): void
    {
        static::$beforeCallback = $callback;
    }

    protected static function resolveUser(): mixed
    {
        if (static::$userResolver) {
            return call_user_func(static::$userResolver);
        }

        return Auth::user();
    }

    public static function define(string $ability, callable|string $callback): void
    {
        static::$abilities[$ability] = $callback;
    }

    public static function policy(string $class, string $policy): void
    {
        static::$policies[$class] = $policy;
    }

    public static function allows(string $ability, mixed $arguments = []): bool
    {
        return static::check($ability, $arguments);
    }

    public static function denies(string $ability, mixed $arguments = []): bool
    {
        return !static::allows($ability, $arguments);
    }

    public static function authorize(string $ability, mixed $arguments = []): bool
    {
        if (!static::allows($ability, $arguments)) {
            throw new RuntimeException("This action is unauthorized.");
        }

        return true;
    }

    public static function check(string $ability, mixed $arguments = []): bool
    {
        $user = static::resolveUser();
        $arguments = is_array($arguments) ? $arguments : [$arguments];

        if (static::$beforeCallback) {
            $beforeResult = call_user_func(static::$beforeCallback, $user, $ability, $arguments);
            if ($beforeResult !== null) {
                return (bool) $beforeResult;
            }
        }

        if (isset(static::$abilities[$ability])) {
            return (bool) call_user_func(static::$abilities[$ability], $user, ...$arguments);
        }

        if (empty($arguments)) {
            return false;
        }

        $model = $arguments[0];
        $modelClass = is_object($model) ? get_class($model) : (is_string($model) ? $model : null);

        if ($modelClass && isset(static::$policies[$modelClass])) {
            $policy = static::$policies[$modelClass];
            $instance = new $policy();
            if (method_exists($instance, $ability)) {
                return (bool) $instance->{$ability}($user, ...$arguments);
            }
        }

        return false;
    }
}
