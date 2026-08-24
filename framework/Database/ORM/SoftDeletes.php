<?php
declare(strict_types=1);

namespace Nexus\Database\ORM;

use Nexus\Database\QueryBuilder;

trait SoftDeletes
{
    public function getDeletedAtColumn(): string
    {
        return defined('static::DELETED_AT') ? static::DELETED_AT : 'deleted_at';
    }

    public static function query(): QueryBuilder
    {
        /** @var QueryBuilder $builder */
        $builder = parent::query();
        
        $column = (new \ReflectionClass(static::class))->newInstanceWithoutConstructor()->getDeletedAtColumn();
        $builder->whereNull($column);

        return $builder;
    }

    public static function withTrashed(): QueryBuilder
    {
        return parent::query();
    }

    public static function onlyTrashed(): QueryBuilder
    {
        /** @var QueryBuilder $builder */
        $builder = parent::query();
        
        $column = (new \ReflectionClass(static::class))->newInstanceWithoutConstructor()->getDeletedAtColumn();
        $builder->whereNotNull($column);

        return $builder;
    }

    public function trashed(): bool
    {
        return $this->getAttribute($this->getDeletedAtColumn()) !== null;
    }

    public function delete(): bool
    {
        if (!$this->exists || $this->getKey() === null) {
            return false;
        }

        $time = date('Y-m-d H:i:s');
        $deleted = parent::query()
            ->where($this->primaryKey, $this->getKey())
            ->update([$this->getDeletedAtColumn() => $time]) > 0;

        if ($deleted) {
            $this->setAttribute($this->getDeletedAtColumn(), $time);
            $this->syncOriginal();
        }

        return $deleted;
    }

    public function forceDelete(): bool
    {
        return parent::delete();
    }

    public function restore(): bool
    {
        if (!$this->exists || $this->getKey() === null) {
            return false;
        }

        $deleted = parent::query()
            ->where($this->primaryKey, $this->getKey())
            ->update([$this->getDeletedAtColumn() => null]) > 0;

        if ($deleted) {
            $this->setAttribute($this->getDeletedAtColumn(), null);
            $this->syncOriginal();
        }

        return $deleted;
    }
}
