<?php
declare(strict_types=1);

namespace Nexus\Database;

use Nexus\Database\Relations\BelongsTo;
use Nexus\Database\Relations\BelongsToMany;
use Nexus\Database\Relations\HasMany;
use Nexus\Database\Relations\HasOne;

/**
 * ActiveRecord Model Implementation
 */
abstract class Model implements \JsonSerializable
{
    protected string $table = '';
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected array $guarded = ['*'];

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
        $this->original = $this->attributes;
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
                $this->attributes[$key] = $value;
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

    public function __get(string $key): mixed
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }

        if (method_exists($this, $key)) {
            $relation = $this->{$key}();
            if ($relation instanceof Relations\Relation) {
                return $relation->get();
            }
        }

        return null;
    }

    public function __set(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public static function query(): QueryBuilder
    {
        if (static::$resolver === null) {
            throw new \RuntimeException("Database Connection Resolver has not been configured on Model.");
        }

        $instance = new static();
        $builder = new QueryBuilder(static::$resolver);
        return $builder->table($instance->getTable());
    }

    public function getTable(): string
    {
        if (!empty($this->table)) {
            return $this->table;
        }
        $class = basename(str_replace('\\', '/', static::class));
        return strtolower($class) . 's';
    }

    public static function find(mixed $id): ?static
    {
        $instance = new static();
        $data = static::query()->where($instance->primaryKey, $id)->first();
        if (!$data) {
            return null;
        }

        $model = new static();
        $model->attributes = $data;
        $model->original = $data;
        return $model;
    }

    public static function all(): array
    {
        $results = static::query()->get();
        $models = [];
        foreach ($results as $row) {
            $model = new static();
            $model->attributes = $row;
            $model->original = $row;
            $models[] = $model;
        }
        return $models;
    }

    public function save(): bool
    {
        $builder = static::query();
        if (isset($this->attributes[$this->primaryKey])) {
            $id = $this->attributes[$this->primaryKey];
            $dirty = array_diff_assoc($this->attributes, $this->original);
            if (empty($dirty)) {
                return true;
            }
            $updated = $builder->where($this->primaryKey, $id)->update($dirty) > 0;
            if ($updated) {
                $this->original = $this->attributes;
            }
            return $updated;
        }

        $inserted = $builder->insert($this->attributes);
        if ($inserted) {
            $this->attributes[$this->primaryKey] = (int) static::$resolver->getPdo()->lastInsertId();
            $this->original = $this->attributes;
        }
        return $inserted;
    }

    public function delete(): bool
    {
        if (!isset($this->attributes[$this->primaryKey])) {
            return false;
        }

        $id = $this->attributes[$this->primaryKey];
        return static::query()->where($this->primaryKey, $id)->delete() > 0;
    }

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

    public function jsonSerialize(): mixed
    {
        return $this->attributes;
    }
}
