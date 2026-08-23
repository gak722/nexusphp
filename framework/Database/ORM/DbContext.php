<?php
declare(strict_types=1);

namespace Nexus\Database\ORM;

use Nexus\Database\Connection;
use Nexus\Database\Exceptions\ConcurrencyException;
use Nexus\Database\Grammar\GrammarInterface;
use Nexus\Database\Grammar\MySqlGrammar;
use Nexus\Database\Grammar\PostgreSqlGrammar;
use Nexus\Database\Grammar\SqliteGrammar;
use Nexus\Database\ORM\Mapping\MetadataFactory;
use Nexus\Database\ORM\Query\EntityQueryBuilder;
use Nexus\Database\ORM\Tracking\ChangeTracker;
use Nexus\Database\ORM\Tracking\EntityState;

class DbContext
{
    protected ChangeTracker $changeTracker;
    protected GrammarInterface $grammar;

    public function __construct(protected Connection $connection)
    {
        $this->changeTracker = new ChangeTracker();
        $this->grammar = $this->resolveGrammar($connection);
    }

    protected function resolveGrammar(Connection $connection): GrammarInterface
    {
        $driver = $connection->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        return match ($driver) {
            'pgsql' => new PostgreSqlGrammar(),
            'sqlite' => new SqliteGrammar(),
            default => new MySqlGrammar(),
        };
    }

    public function getChangeTracker(): ChangeTracker
    {
        return $this->changeTracker;
    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }

    public function query(string $entityClass): EntityQueryBuilder
    {
        return new EntityQueryBuilder($entityClass, $this->connection, $this);
    }

    public function add(object $entity): void
    {
        $this->changeTracker->track($entity, EntityState::Added);
    }

    public function update(object $entity): void
    {
        $this->changeTracker->track($entity, EntityState::Modified);
    }

    public function remove(object $entity): void
    {
        $this->changeTracker->track($entity, EntityState::Deleted);
    }

    public function saveChanges(): int
    {
        $affectedRows = 0;
        $entries = $this->changeTracker->getEntries();

        $this->connection->transaction(function () use ($entries, &$affectedRows) {
            foreach ($entries as $entry) {
                if ($entry->state === EntityState::Unchanged) {
                    $this->changeTracker->detectChanges($entry);
                }

                if ($entry->state === EntityState::Added) {
                    $affectedRows += $this->insertEntity($entry);
                } elseif ($entry->state === EntityState::Modified) {
                    $affectedRows += $this->updateEntity($entry);
                } elseif ($entry->state === EntityState::Deleted) {
                    $affectedRows += $this->deleteEntity($entry);
                }
            }
        });

        return $affectedRows;
    }

    protected function insertEntity($entry): int
    {
        $entity = $entry->entity;
        $metadata = MetadataFactory::getMetadata($entry->className);
        $values = $this->changeTracker->extractValues($entity);

        $cols = [];
        $bindings = [];

        foreach ($metadata->properties as $prop) {
            if ($prop->isKey && $prop->autoIncrement) {
                continue;
            }
            if (array_key_exists($prop->propertyName, $values)) {
                $cols[] = $prop->columnName;
                $bindings[] = $values[$prop->propertyName];
            }
        }

        $sql = $this->grammar->compileInsert($metadata->tableName, $cols);
        $this->connection->statement($sql, $bindings);

        if ($metadata->autoIncrement) {
            $lastId = (int) $this->connection->getPdo()->lastInsertId();
            $refProp = (new \ReflectionClass($entry->className))->getProperty($metadata->primaryKeyProperty);
            $refProp->setValue($entity, $lastId);
            $this->changeTracker->registerInIdentityMap($entry->className, $lastId, $entity);
        }

        $entry->state = EntityState::Unchanged;
        $entry->originalValues = $this->changeTracker->extractValues($entity);
        return 1;
    }

    protected function updateEntity($entry): int
    {
        $entity = $entry->entity;
        $metadata = MetadataFactory::getMetadata($entry->className);
        $currentValues = $this->changeTracker->extractValues($entity);
        $changes = $this->changeTracker->detectChanges($entry);

        if (empty($changes) && $entry->state !== EntityState::Modified) {
            return 0;
        }

        $cols = [];
        $bindings = [];

        foreach ($currentValues as $propName => $val) {
            $propMeta = $metadata->properties[$propName] ?? null;
            if ($propMeta && !$propMeta->isKey) {
                if ($propMeta->isConcurrencyToken) {
                    $val++;
                    (new \ReflectionClass($entry->className))->getProperty($propName)->setValue($entity, $val);
                }
                $cols[] = $propMeta->columnName;
                $bindings[] = $val;
            }
        }

        $pkProp = $metadata->primaryKeyProperty;
        $pkCol = $metadata->primaryKeyColumn;
        $pkVal = $currentValues[$pkProp];

        $whereClause = $this->grammar->wrapIdentifier($pkCol) . ' = ?';
        $bindings[] = $pkVal;

        if ($metadata->concurrencyTokenProperty !== null) {
            $tokenProp = $metadata->concurrencyTokenProperty;
            $tokenMeta = $metadata->properties[$tokenProp];
            $origToken = $entry->getOriginalValue($tokenProp);
            $whereClause .= ' AND ' . $this->grammar->wrapIdentifier($tokenMeta->columnName) . ' = ?';
            $bindings[] = $origToken;
        }

        $sql = $this->grammar->compileUpdate($metadata->tableName, $cols, $whereClause);
        $affected = $this->connection->affectingStatement($sql, $bindings);

        if ($affected === 0 && $metadata->concurrencyTokenProperty !== null) {
            throw new ConcurrencyException("Optimistic concurrency conflict detected while updating {$entry->className} (ID: {$pkVal}).");
        }

        $entry->state = EntityState::Unchanged;
        $entry->originalValues = $this->changeTracker->extractValues($entity);
        return $affected;
    }

    protected function deleteEntity($entry): int
    {
        $entity = $entry->entity;
        $metadata = MetadataFactory::getMetadata($entry->className);
        $currentValues = $this->changeTracker->extractValues($entity);

        $pkProp = $metadata->primaryKeyProperty;
        $pkCol = $metadata->primaryKeyColumn;
        $pkVal = $currentValues[$pkProp];

        if ($metadata->softDeleteColumn !== null) {
            $sql = $this->grammar->compileUpdate($metadata->tableName, [$metadata->softDeleteColumn], $this->grammar->wrapIdentifier($pkCol) . ' = ?');
            $affected = $this->connection->affectingStatement($sql, [date('Y-m-d H:i:s'), $pkVal]);
        } else {
            $sql = $this->grammar->compileDelete($metadata->tableName, $this->grammar->wrapIdentifier($pkCol) . ' = ?');
            $affected = $this->connection->affectingStatement($sql, [$pkVal]);
        }

        $entry->state = EntityState::Detached;
        return $affected;
    }
}
