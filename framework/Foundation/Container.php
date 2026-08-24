<?php
declare(strict_types=1);

namespace Nexus\Foundation;

/**
 * Lightweight PSR-11 compatible Dependency Injection Container
 */
class Container
{
    /**
     * Array of bound abstractions and their factory/concrete callbacks.
     */
    protected array $bindings = [];

    /**
     * Shared singleton instances.
     */
    protected array $instances = [];

    /**
     * Stack of target classes currently being built to detect circular dependencies.
     */
    protected array $buildStack = [];

    /**
     * Bind an abstract type to a concrete implementation.
     */
    public function bind(string $abstract, mixed $concrete = null, bool $shared = false): void
    {
        if ($concrete === null) {
            $concrete = $abstract;
        }

        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'shared' => $shared,
        ];
    }

    /**
     * Bind a shared singleton instance into the container.
     */
    public function singleton(string $abstract, mixed $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * .NET ServiceCollection style: Register a transient dependency (new instance created per resolve request).
     */
    public function addTransient(string $abstract, mixed $concrete = null): static
    {
        $this->bind($abstract, $concrete, false);
        return $this;
    }

    /**
     * .NET ServiceCollection style: Register a scoped dependency (bound to current request/lifetime scope).
     */
    public function addScoped(string $abstract, mixed $concrete = null): static
    {
        $this->bind($abstract, $concrete, true);
        return $this;
    }

    /**
     * .NET ServiceCollection style: Register a singleton dependency (single shared instance across application).
     */
    public function addSingleton(string $abstract, mixed $concrete = null): static
    {
        $this->bind($abstract, $concrete, true);
        return $this;
    }

    /**
     * .NET ServiceCollection style: Register an existing instance as singleton.
     */
    public function addInstance(string $abstract, mixed $instance): static
    {
        $this->instance($abstract, $instance);
        return $this;
    }

    /**
     * Register an existing instance as shared in the container.
     */
    public function instance(string $abstract, mixed $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    /**
     * Determine if a given type has been bound or instantiated.
     */
    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }

    /**
     * Resolve the given type from the container.
     */
    public function make(string $abstract): mixed
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        if (!isset($this->bindings[$abstract])) {
            // Attempt auto-wiring if class exists
            if (class_exists($abstract)) {
                if (is_subclass_of($abstract, \Nexus\Validation\FormRequest::class)) {
                    $request = $this->has(\Nexus\Http\Request::class)
                        ? $this->make(\Nexus\Http\Request::class)
                        : \Nexus\Http\Request::createFromGlobals();
                    return $abstract::createFromRequest($request);
                }
                return $this->build($abstract);
            }
            throw new \InvalidArgumentException("Target binding [$abstract] does not exist.");
        }

        $binding = $this->bindings[$abstract];
        $concrete = $binding['concrete'];

        if ($concrete instanceof \Closure || (is_string($concrete) && is_callable($concrete))) {
            $object = $concrete($this);
        } elseif (is_string($concrete)) {
            $object = $this->build($concrete);
        } else {
            $object = $concrete;
        }

        if ($binding['shared']) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    /**
     * Instantiate a concrete instance with reflection dependency injection.
     */
    protected function build(string $concrete): mixed
    {
        if (in_array($concrete, $this->buildStack, true)) {
            $chain = implode(' -> ', array_merge($this->buildStack, [$concrete]));
            throw new \RuntimeException("Circular dependency detected: [$chain]");
        }

        $this->buildStack[] = $concrete;

        try {
            $reflector = new \ReflectionClass($concrete);
        } catch (\ReflectionException $e) {
            array_pop($this->buildStack);
            throw new \InvalidArgumentException("Target class [$concrete] does not exist.", 0, $e);
        }

        if (!$reflector->isInstantiable()) {
            array_pop($this->buildStack);
            throw new \RuntimeException("Target class [$concrete] is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            array_pop($this->buildStack);
            return new $concrete();
        }

        $parameters = $constructor->getParameters();
        $dependencies = [];

        try {
            foreach ($parameters as $parameter) {
                $paramName = $parameter->getName();
                $type = $parameter->getType();

                if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                    $dependencies[] = $this->make($type->getName());
                    continue;
                }

                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                    continue;
                }

                throw new \RuntimeException("Unresolvable dependency [$paramName] in class [$concrete].");
            }
        } finally {
            array_pop($this->buildStack);
        }

        return $reflector->newInstanceArgs($dependencies);
    }
}
