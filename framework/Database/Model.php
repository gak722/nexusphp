<?php
declare(strict_types=1);

namespace Nexus\Database;

use Nexus\Database\Relations\BelongsTo;
use Nexus\Database\Relations\BelongsToMany;
use Nexus\Database\Relations\HasMany;
use Nexus\Database\Relations\HasOne;
use Nexus\Database\Relations\Relation;
use Nexus\Binding\Binder;
use Nexus\Binding\BindingContext;
use Nexus\Validation\Validate;
use Nexus\Validation\Validator;

/**
 * Modern Laravel-Style Eloquent ActiveRecord & RedBeanPHP Dynamic Bean Model Engine
 */
abstract class Model implements \JsonSerializable, \ArrayAccess
{
    protected string $table = '';
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected array $guarded = ['*'];

    /** RedBeanPHP Bean Dynamic Attribute Storage & State Tracking */
    protected array $attributes = [];
    protected array $original = [];
    protected array $relations = [];
    protected bool $exists = false;

    protected static ?Connection $resolver = null;

    public function __construct(array $attributes = [])
    {
        $this->bootIfNotBooted();
        $this->fill($attributes);
        $this->syncOriginal();
    }

    protected function bootIfNotBooted(): void
    {
        static::boot();
    }

    protected static function boot(): void
    {
        // Extension hook for model event registration or global scopes
    }

    public static function setConnectionResolver(Connection $connection): void
    {
        static::$resolver = $connection;
    }

    public static function getConnectionResolver(): ?Connection
    {
        return static::$resolver;
    }

    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            }
        }
        return $this;
    }

    public function isFillable(string $key): bool
    {
        if (in_array($key, $this->fillable, true)) {
            return true;
        }

        if ($this->isGuarded($key)) {
            return false;
        }

        return empty($this->fillable) && $this->guarded === ['*'] ? false : empty($this->fillable);
    }

    public function isGuarded(string $key): bool
    {
        return in_array($key, $this->guarded, true) || $this->guarded === ['*'];
    }

    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    public function getKey(): mixed
    {
        return $this->getAttribute($this->primaryKey);
    }

    public function setAttribute(string $key, mixed $value): static
    {
        // Support custom mutator if available (e.g., setFirstNameAttribute)
        $mutator = 'set' . $this->studly($key) . 'Attribute';
        if (method_exists($this, $mutator)) {
            $this->{$mutator}($value);
            return $this;
        }

        $this->attributes[$key] = $value;
        return $this;
    }

    public function getAttribute(string $key): mixed
    {
        if (array_key_exists($key, $this->attributes)) {
            $value = $this->attributes[$key];
            // Support custom accessor if available (e.g., getFirstNameAttribute)
            $accessor = 'get' . $this->studly($key) . 'Attribute';
            if (method_exists($this, $accessor)) {
                return $this->{$accessor}($value);
            }
            return $value;
        }

        if (array_key_exists($key, $this->relations)) {
            return $this->relations[$key];
        }

        if (method_exists($this, $key)) {
            $relation = $this->{$key}();
            if ($relation instanceof Relation) {
                $results = $relation->get();
                $this->relations[$key] = $results;
                return $results;
            }
        }

        return null;
    }

    public function setRelation(string $relation, mixed $value): static
    {
        $this->relations[$relation] = $value;
        return $this;
    }

    public function getRelation(string $relation): mixed
    {
        return $this->relations[$relation] ?? null;
    }

    public function relationLoaded(string $relation): bool
    {
        return array_key_exists($relation, $this->relations);
    }

    public function __get(string $key): mixed
    {
        return $this->getAttribute($key);
    }

    public function __set(string $key, mixed $value): void
    {
        $this->setAttribute($key, $value);
    }

    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]) || isset($this->relations[$key]);
    }

    public function __unset(string $key): void
    {
        unset($this->attributes[$key], $this->relations[$key]);
    }

    // RedBeanPHP Dynamic Property & Dirty State Helpers
    public function isDirty(?string $attribute = null): bool
    {
        $dirty = $this->getDirty();
        if ($attribute === null) {
            return !empty($dirty);
        }
        return array_key_exists($attribute, $dirty);
    }

    public function getDirty(): array
    {
        $dirty = [];
        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) || $this->original[$key] !== $value) {
                $dirty[$key] = $value;
            }
        }
        return $dirty;
    }

    public function syncOriginal(): static
    {
        $this->original = $this->attributes;
        return $this;
    }

    public function exists(): bool
    {
        return $this->exists;
    }

    public function getTable(): string
    {
        if (!empty($this->table)) {
            return $this->table;
        }
        $class = basename(str_replace('\\', '/', static::class));
        return strtolower($class) . 's';
    }

    // Laravel Eloquent Static Gateway API
    public static function query(): QueryBuilder
    {
        if (static::$resolver === null) {
            throw new \RuntimeException("Database Connection Resolver has not been configured on Model.");
        }

        $instance = new static();
        $builder = new QueryBuilder(static::$resolver);
        return $builder->table($instance->getTable());
    }

    public static function __callStatic(string $method, array $parameters): mixed
    {
        return static::query()->$method(...$parameters);
    }

    public function __call(string $method, array $parameters): mixed
    {
        return static::query()->$method(...$parameters);
    }

    public static function where(string $column, mixed $operator = null, mixed $value = null): QueryBuilder
    {
        return static::query()->where(...func_get_args());
    }

    public static function find(mixed $id): ?static
    {
        $instance = new static();
        $data = static::query()->where($instance->primaryKey, $id)->first();
        if (!$data) {
            return null;
        }

        return static::newFromBuilder($data);
    }

    public static function findOrFail(mixed $id): static
    {
        $model = static::find($id);
        if (!$model) {
            throw new \RuntimeException("Model [" . static::class . "] not found for ID: {$id}");
        }
        return $model;
    }

    public static function all(): array
    {
        $results = static::query()->get();
        return array_map(fn($row) => static::newFromBuilder($row), $results);
    }

    public static function create(array $attributes = []): static
    {
        $model = new static($attributes);
        $model->save();
        return $model;
    }

    public static function firstOrCreate(array $attributes, array $values = []): static
    {
        $instance = new static();
        $query = static::query();
        foreach ($attributes as $field => $val) {
            $query->where($field, $val);
        }

        $row = $query->first();
        if ($row) {
            return static::newFromBuilder($row);
        }

        return static::create(array_merge($attributes, $values));
    }

    public static function newFromBuilder(array $attributes = []): static
    {
        $model = new static();
        $model->attributes = $attributes;
        $model->syncOriginal();
        $model->exists = true;
        return $model;
    }

    public function save(): bool
    {
        $builder = static::query();

        if ($this->exists) {
            $dirty = $this->getDirty();
            if (empty($dirty)) {
                return true;
            }

            $updated = $builder->where($this->primaryKey, $this->getKey())->update($dirty) > 0;
            if ($updated) {
                $this->syncOriginal();
            }
            return $updated;
        }

        $inserted = $builder->insert($this->attributes);
        if ($inserted) {
            $lastId = static::$resolver->getPdo()->lastInsertId();
            if ($lastId && !isset($this->attributes[$this->primaryKey])) {
                $this->attributes[$this->primaryKey] = is_numeric($lastId) ? (int) $lastId : $lastId;
            }
            $this->syncOriginal();
            $this->exists = true;
        }
        return $inserted;
    }

    public function delete(): bool
    {
        if (!$this->exists || $this->getKey() === null) {
            return false;
        }

        $deleted = static::query()->where($this->primaryKey, $this->getKey())->delete() > 0;
        if ($deleted) {
            $this->exists = false;
        }
        return $deleted;
    }

    public function fresh(): ?static
    {
        if (!$this->exists) {
            return null;
        }
        return static::find($this->getKey());
    }

    // Relationship Definitions
    public function belongsTo(string $related, ?string $foreignKey = null, ?string $ownerKey = 'id'): BelongsTo
    {
        $foreignKey = $foreignKey ?? (strtolower(basename(str_replace('\\', '/', $related))) . '_id');
        return new BelongsTo($this, $related, $foreignKey, $ownerKey);
    }

    public function hasMany(string $related, ?string $foreignKey = null, ?string $localKey = 'id'): HasMany
    {
        $foreignKey = $foreignKey ?? (strtolower(basename(str_replace('\\', '/', static::class))) . '_id');
        return new HasMany($this, $related, $foreignKey, $localKey);
    }

    public function hasOne(string $related, ?string $foreignKey = null, ?string $localKey = 'id'): HasOne
    {
        $foreignKey = $foreignKey ?? (strtolower(basename(str_replace('\\', '/', static::class))) . '_id');
        return new HasOne($this, $related, $foreignKey, $localKey);
    }

    public function belongsToMany(
        string $related,
        ?string $table = null,
        ?string $foreignKey = null,
        ?string $relatedKey = null
    ): BelongsToMany {
        $parentClass = strtolower(basename(str_replace('\\', '/', static::class)));
        $relatedClass = strtolower(basename(str_replace('\\', '/', $related)));

        if ($table === null) {
            $tables = [$parentClass, $relatedClass];
            sort($tables);
            $table = implode('_', $tables);
        }

        $foreignKey = $foreignKey ?? ($parentClass . '_id');
        $relatedKey = $relatedKey ?? ($relatedClass . '_id');

        return new BelongsToMany($this, $related, $table, $foreignKey, $relatedKey);
    }

    // ArrayAccess & Serialization Integration
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->{$offset});
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->getAttribute((string) $offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->setAttribute((string) $offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->attributes[(string) $offset]);
    }

    public function jsonSerialize(): mixed
    {
        return array_merge($this->attributes, $this->relations);
    }

    public function toArray(): array
    {
        return $this->jsonSerialize();
    }

    public function rules(): array
    {
        $rules = [];
        $reflector = new \ReflectionClass($this);
        foreach ($reflector->getProperties() as $property) {
            $attributes = $property->getAttributes(Validate::class);
            foreach ($attributes as $attribute) {
                /** @var Validate $inst */
                $inst = $attribute->newInstance();
                $rules[$property->getName()] = $inst->rules;
            }
        }
        return $rules;
    }

    public function messages(): array
    {
        return [];
    }

    public function attributes(): array
    {
        return [];
    }

    public function validate(array $data): array
    {
        if (method_exists($this, 'beforeValidate')) {
            $this->beforeValidate($data);
        }

        $validator = Validator::make($data, $this->rules(), $this->messages(), $this->attributes());
        $validator->setTargetModel($this);
        if (static::$resolver !== null) {
            $validator->setDbConnection(static::$resolver);
        }

        $validated = $validator->validate();

        if (method_exists($this, 'afterValidate')) {
            $this->afterValidate($validator->errors());
        }

        return $validated;
    }

    public function bind(array $data, ?BindingContext $context = null): static
    {
        $binder = new Binder();
        return $binder->bind($this, $data, $context);
    }

    public static function validateAndBind(array|\Nexus\Http\Request $data, ?BindingContext $context = null): static
    {
        $input = $data instanceof \Nexus\Http\Request ? array_merge($data->query, $data->post, $data->json() ?: []) : $data;
        $instance = new static();
        $validated = $instance->validate($input);
        return $instance->bind($validated, $context);
    }

    protected function studly(string $value): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $value)));
    }
}
