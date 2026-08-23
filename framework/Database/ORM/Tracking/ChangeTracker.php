<?php
declare(strict_types=1);

namespace Nexus\Database\ORM\Tracking;

enum EntityState: string
{
    case Added = 'Added';
    case Unchanged = 'Unchanged';
    case Modified = 'Modified';
    case Deleted = 'Deleted';
    case Detached = 'Detached';
}

class EntityEntry
{
    public EntityState $state = EntityState::Detached;
    public array $originalValues = [];

    public function __construct(public object $entity, public string $className) {}

    public function getOriginalValue(string $property): mixed
    {
        return $this->originalValues[$property] ?? null;
    }
}

class ChangeTracker
{
    /** @var array<string, EntityEntry> Keyed by object hash */
    private array $entries = [];
    /** @var array<string, object> Keyed by Class:ID */
    private array $identityMap = [];

    public function track(object $entity, EntityState $state = EntityState::Unchanged, array $originalValues = []): EntityEntry
    {
        $hash = spl_object_hash($entity);
        if (!isset($this->entries[$hash])) {
            $entry = new EntityEntry($entity, get_class($entity));
            $this->entries[$hash] = $entry;
        } else {
            $entry = $this->entries[$hash];
        }

        $entry->state = $state;
        if (!empty($originalValues) || $state === EntityState::Unchanged) {
            $entry->originalValues = !empty($originalValues) ? $originalValues : $this->extractValues($entity);
        }

        return $entry;
    }

    public function getEntry(object $entity): ?EntityEntry
    {
        $hash = spl_object_hash($entity);
        return $this->entries[$hash] ?? null;
    }

    public function findInIdentityMap(string $class, mixed $id): ?object
    {
        $key = "{$class}:{$id}";
        return $this->identityMap[$key] ?? null;
    }

    public function registerInIdentityMap(string $class, mixed $id, object $entity): void
    {
        $key = "{$class}:{$id}";
        $this->identityMap[$key] = $entity;
    }

    public function getEntries(): array
    {
        return array_values($this->entries);
    }

    public function extractValues(object $entity): array
    {
        $ref = new \ReflectionClass($entity);
        $values = [];
        foreach ($ref->getProperties() as $prop) {
            if ($prop->isInitialized($entity)) {
                $val = $prop->getValue($entity);
                if ($val instanceof \DateTimeInterface) {
                    $val = $val->format('Y-m-d H:i:s');
                } elseif (is_object($val) && enum_exists(get_class($val))) {
                    $val = $val->value ?? $val->name;
                }
                $values[$prop->getName()] = $val;
            }
        }
        return $values;
    }

    public function detectChanges(EntityEntry $entry): array
    {
        if ($entry->state !== EntityState::Unchanged) {
            return [];
        }

        $currentValues = $this->extractValues($entry->entity);
        $changes = [];

        foreach ($currentValues as $prop => $value) {
            $orig = $entry->originalValues[$prop] ?? null;
            if ($orig !== $value) {
                $changes[$prop] = $value;
            }
        }

        if (!empty($changes)) {
            $entry->state = EntityState::Modified;
        }

        return $changes;
    }
}
